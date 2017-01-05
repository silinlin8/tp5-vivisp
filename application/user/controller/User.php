<?php
namespace app\user\controller;
use think\Controller;
use Core\commonfun;
use think\Db;
use Mem\mem;
class User extends Controller
{	
	/*检查是否登陆*/
	private function checkLogin(){
        if(!session('uid')){
            $this->redirect('Index/Index/index');
        }
    }
    protected $m;
    public function _initialize(){
        if(!isset($this->m)){
            $this->m = new mem();
        }
    }
    public function index(){
    	$uid=input('get.Uid');
        if(!$uid){
            $this->checkLogin();
            $uid=session('uid');
        }
        $type=db('user')->where('Uid='.$uid)->value('Utype');
        if($type==1){
            $this->redirect('Teacher/index');
        }
        $courses = Db::view('Ruserlesson',['Rid','RUid','Rstarttime','Rstudytime'])->
                view(['Lesson','plesson'],['Lid'=>'plid','Ltitle'=>'pltitle','Ldesc'=>'pldesc','Limage'=>'plimage','Ltime'=>'pltime','Luptime'=>'pluptime'],
                'plesson.Lid=Ruserlesson.Rlppid')->
                view(['Lesson','sublesson'],["Lid"=>"sublid","Ltitle"=>"subltitle","Ldesc"=>"subldesc","Ltime"=>"subltime","Luptime"=>"subluptime"],'sublesson.Lid=Ruserlesson.RLid')->
                where('Ruid='.$uid)->order('Rstarttime DESC')->limit(0,10)->select();
        if(!($this->m->get(['index_info','index_notecount','index_studytime']))){
            
            $info=db('user')->find($uid);
            $notecount=db('note')->where('Nuid='.$uid)->count();     
            $studytime = db('ruserlesson')->where('Rid in(SELECT Rid FROM os_ruserlesson WHERE RUid='.session('uid').')')->sum('Rstudytime');
            $this->m->set(['index_courses'=>$courses,'index_info'=>$info,'index_notecount'=>$notecount,'index_studytime'=>$studytime]);
        }
        $result = $this->m->get(['index_info','index_notecount','index_studytime']);
        $studytime = $result['index_studytime'] ? $result['index_studytime'] : 0;
        $this->assign('title','个人中心_');
        $this->assign('indexactive',' myspaceactive');
        $this->assign('noteactive','');
        $this->assign('courses',$courses);
        $this->assign('info',$result['index_info']);
        $this->assign('notecount',$result['index_notecount']);
        $this->assign('studytime',$studytime);
        return $this->fetch();
    }

    public function checkIdentity(){
        $uid=session('uid');
        $type=db('user')->where('Uid='.$uid)->value('Utype');
        if($type==0){
            $this->redirect('User/user/index');
        }elseif($type==2){
            $this->redirect('User/user/message');
        }else{
            $this->redirect('User/Teacher/index');
        }
    }

    public function message(){
        $user=db('user');
        $type=$user->where('Uid='.session('uid'))->value('Utype'); 
        $userinfo=$user->find(session('uid'));
        $datefrom=0;
        $mtouid=1;//默认给管理员发消息
        if($type==2){//管理员
            if(!$this->m->get('userlist')){
                $userlist = Db::view('User','Uid,Uname,Uimg')->
                	view('Message',['COUNT(Mid)'=>'mcount'],'Message.Muid=User.Uid AND Mtouid=1 AND Mislook=0','LEFT')->
                	where(array('Uid'=>array('neq',1)))->group('Uid')->order('mcount DESC')->select();
                    $this->m->set('userlist',$userlist);
            }
            $userlist = $this->m->get('userlist');
            $uid=input('?get.uid')?input('get.uid'):$userlist[0]['Uid'];//默认和第一个对话
            $where['Muid|Mtouid']=$uid;
            db('message')->where('Mtouid='.session('uid').' And Mislook=0 And Muid='.$uid)->setField('Mislook',1);
            $usermessage=db('message');
            $messageInfo=$usermessage->where($where)->order('Mdate')->select();
            $admin=$user->find($uid);
            $mtouid=$uid;
            $this->assign('userlist',$userlist);
        }else{
            $uid=session('uid');
            $where['Muid|Mtouid']=$uid;
            db('message')->where('Mtouid='.$uid.' And Mislook=0')->setField('Mislook',1);
            $usermessage=db('message');
            $messageInfo=$usermessage->where($where)->order('Mdate')->select();
            $admin=$user->where('Utype=2')->find();
        }
        $this->assign('title','我的消息_');
        $this->assign('admin',$admin);
        $this->assign('mtouid',$mtouid);
        $this->assign('uid',Session('uid'));
        $this->assign('messageInfo',$messageInfo);
        $this->assign('utype',$type);
        $this->assign('datefrom',$datefrom);
        $this->assign('userinfo',$userinfo);
        return $this->fetch();
    }

    /*添加消息*/
    public function addMessage(){
        $data['Mdesc']=input('post.msg');
        $data['Mdate']=input('post.now');
        $data['Muid']=session('uid');
        $data['Mtouid']=input('post.mtouid');
        $res = db('Message')->insert($data);
        if($res){
            return 1;
        }else{
            return 0;
        }
    }
    /*删除课程*/
    public function deleteLesson(){
        $where['Rlpid|RLid']=input('get.Cid');
        $where['RUid']=session('uid');
        $ok = db('ruserlesson')->where($where)->delete();
        if($ok){
            echo "xcmsihfsvfs";
        }else{
            echo "xcishfa";
        }
        $this->redirect('index');
    }

    public function setperfile(){//设置个人信息
        $this->checkLogin();
        $department=db('province')->select();
        $this->assign('province',$department);
        $user=db('User');
        $info=$user->find(session('uid'));
        $this->assign('title','个人资料_');
        $this->assign('info',$info);
        return $this->fetch();
    }
    public function setavator(){//设置头像也显示
        $this->checkLogin();
        $uid=session('uid');
        $headimg=db('user')->where('Uid='.$uid)->value('Uimg');
        $this->assign('title','头像设置_');
        $this->assign('headimg',$headimg);
        return $this->fetch();
    }
    public function setverifyemail(){//设置邮箱
        $this->checkLogin();
        $uid=session('uid');
        $uemail=db('user')->where('Uid='.$uid)->value('Uemail');
        $this->assign('title','邮箱验证_');
        $this->assign('email',$uemail);
        return $this->fetch();
    }

    public function setresetpwd(){
        $this->assign('title','修改密码_');
        return $this->fetch();
    }

    public function note(){
        $this->checkLogin();
        $uid=session('uid');
        $user=db('User');
        $pageIndex=input('?get.page')?input('get.page'):1;
        $pagesize = 8;
        $info=$user->find($uid);
        $lcount=db('Ruserlesson')->where('Ruid='.$uid)->count();
        $ntype=input('?get.type')?input('get.type'):0;
        $lid=input('?get.lid')?input('get.lid'):0;
        if($ntype==1){//自创
            $where['Ntype']=0;
        }else if($ntype==2){//采集
            $where['Ntype']=array('neq',0);
        }
        $where['Nuid']=$uid;
        if($lid!=0){
            $where['_string']='Lid='.$lid.' OR Lpid='.$lid;
        }
        $note=db('note');
        $notecount=$note->where('Nuid='.$uid)->count();
        $priNcount=$note->where('Nuid='.$uid.' and Ntype=0')->count();
        $getNcount=$note->where('Nuid='.$uid.' and Ntype<>0')->count();
        if(!$this->m->get(['note_notes','note_count'])){
            $usernote = Db::view('Note',['Nid','Ndesc','Nlid','Ndate','Ntype','Nuid'])->
                view('User',['Uimg'],'User.Uid=Note.Nuid')->
                view('Lesson',['Ltitle','Lpid'],'Lesson.Lid=Note.Nlid');
            $notes=$usernote->where($where)->page($pageIndex,$pagesize)->select();
            $count= Db::view('Note',['Nid','Ndesc','Nlid','Ndate','Ntype','Nuid'])->
                view('User',['Uimg'],'User.Uid=Note.Nuid')->
                view('Lesson',['Ltitle','Lpid'],'Lesson.Lid=Note.Nlid')->where($where)->count();
            $this->m->set(['note_notes'=>$notes,'note_count'=>$count]);
        }
        $notes = $this->m->get('note_notes');
        $count = $this->m->get('note_count');
        $lessons=array();
        for($i=0;$i<count($notes);$i++){
            $tarr=array('Lid'=>$notes[$i]['Lid'],'Ltitle'=>$notes[$i]['Ltitle']);
            if(!in_array($tarr,$lessons)){
                $lessons[]=$tarr;
            }
            if($notes[$i]['Ntype']!=0){
                $notes[$i]['NSinfo']=$usernote->where('Nid='.$notes[$i]['Ntype'])->find();
            }else{
                $notes[$i]['NSinfo']='';
            }
        }
        $ruserlesson = db('Ruserlesson');
        $studytime = $ruserlesson->where('Rid in(SELECT Rid FROM os_ruserlesson WHERE RUid='.session('uid').')')->sum('Rstudytime');
        $studytime=$studytime?$studytime:0;
        $cf = new commonfun();
        $url='/index.php/User/User/note?type='.$ntype.'&page=__PAGE__';
        $pageshow=$cf->Pagelist($pageIndex,$count,$url,$pagesize);

        $this->assign('pageshow',$pageshow);
        $this->assign('title','我的笔记_');
        $this->assign('noteactive',' noteactive');
        $this->assign('indexactive','');
        $this->assign('info',$info);
        $this->assign('notecount',$notecount);
        $this->assign('priNcount',$priNcount);
        $this->assign('getNcount',$getNcount);
        $this->assign('lessons',$lessons);
        $this->assign('notes',$notes);
        $this->assign('lcount',$lcount);
        $this->assign('type',$ntype);
        $this->assign('lid',$lid);
        $this->assign('studytime',$studytime);
        return $this->fetch();
    }
    //购买记录
    public function buyrecord(){
        $uid = session('uid');
        $user = db('User');
        $note = db('note');
        $buy = db('buyrecord');
        $pageIndex=input('get.page')?input('get.page'):1;
        $pagesize = 8;

        $notecount=$note->where('Nuid='.$uid)->count();
        $info1 = $user->where("Uid = $uid")->find();
        $ruserlesson = db('Ruserlesson');
        $studytime = $ruserlesson->where('Rid in(SELECT Rid FROM os_ruserlesson WHERE RUid='.Session('uid').')')->sum('Rstudytime');
        $studytime=$studytime?$studytime:0;
        $buyrecord = $buy->where("Buid = $uid")->page($pageIndex,$pagesize)->order('Btime desc')->select();
        $count = $buy->where("Buid = $uid")->count();
        $lesson = array();
        for($i=0;$i<count($buyrecord);$i++){
             $kecheng = db('lesson')->where('Lid='.$buyrecord[$i]['Blppid'])->value('Ltitle');
             $zhang = db('lesson')->where('Lpid='.$buyrecord[$i]['Blppid'].' and lid='.$buyrecord[$i]['Blpid'] )->value('Ltitle');
             $jie = db('lesson')->where('Lppid='.$buyrecord[$i]['Blppid'].' and Lpid='.$buyrecord[$i]['Blpid'].' and lid='.$buyrecord[$i]['Blid'])->value('Ltitle');
             $lesson[$i]=array(
             $kecheng,$zhang,$jie,$buyrecord[$i]['Btime'],$buyrecord[$i]['Bbeans']
                 );
        }

        $cf=new commonfun();
        $page = input("get.page");
        $url='/index.php/User/User/buyRecord?page=__PAGE__';
        $pageshow=$cf->Pagelist($pageIndex,$count,$url,$pagesize);
        $this->assign("count",$count);
        $this->assign("page",$page);
        $this->assign("pagesize",$pagesize);
        $this->assign("info",$info1);
        $this->assign("notecount",$notecount);
        $this->assign("studytime",$studytime);
        $this->assign("buyrecord",$lesson);
        $this->assign("title","购买记录_");
        $this->assign('pageshow',$pageshow);
        return $this->fetch();
    }
    //充值记录
    public function rechargeHistory(){
        $uid=session('uid');
        $user=db('User');
        $note=db('note');
        $recharge = db('rechargerecord');
        $pageIndex=input('get.page')?input('get.page'):1;
        $pagesize = 8;
        
        $notecount=$note->where('Nuid='.$uid)->count();
        $info1 = $user->where("Uid = $uid")->find();
        /*$model=new \Think\Model();
        $sql='SELECT SUM(Rstudytime) AS studytime FROM os_ruserlesson WHERE Rid in(SELECT Rid FROM os_ruserlesson WHERE RUid='.Session('uid').');';
        $studytimeArr=$model->query($sql);
        $studytime=$studytimeArr[0]['studytime'];*/
        $ruserlesson = db('Ruserlesson');
        $studytime = $ruserlesson->where('Rid in(SELECT Rid FROM os_ruserlesson WHERE RUid='.Session('uid').')')->sum('Rstudytime');
        $studytime=$studytime?$studytime:0;
        $rechargerecord = $recharge->where("Ruid = $uid")->page($pageIndex,$pagesize)->order('Rtime desc')->select();
        $recharge->where("Ruid = $uid AND Rtrade_status = 0")->delete();
        $count = $recharge->where("Ruid = $uid")->count();
        $cf=new commonfun();
        $url='/index.php/User/User/rechargeHistory?page=__PAGE__';
        $pageshow=$cf->Pagelist($pageIndex,$count,$url,$pagesize);
        $page = input('get.page');
        $this->assign('count',$count);
        $this->assign('pagesize',$pagesize);
        $this->assign('page',$page);
        $this->assign("info",$info1);
        $this->assign("notecount",$notecount);
        $this->assign("studytime",$studytime);
        $this->assign("rechargerecord",$rechargerecord);
        $this->assign("title","充值记录_");
        $this->assign('pageshow',$pageshow);
        return $this->fetch();
    }
    public function recharge(){
        $uid=session('uid');
        $user = db('User');
        $name = $user->where("Uid = $uid")->value('Uname');
        $this->assign("Uid",$uid);
        $this->assign("name",$name);
        $this->assign("title","账户充值_");
        return $this->fetch();
    }
    
}
