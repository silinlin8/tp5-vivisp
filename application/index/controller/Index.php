<?php
namespace app\index\controller;
use think\Controller;
use Core\commonfun;
use think\Db;
use Mem\mem; //memcached类

class Index extends Controller
{   
    protected $m;
    public function _initialize(){
        $this->m = new mem();
    }
    public function index()
    {
    	if(session('uid')){
    		action('user/User/checkIdentity'); 
    	}
    	
        if(!$this->m->get('courselist')){
           $courselist = Db::view('Lesson','Lid,Ltitle,Ldesc,Limage,Ldifficulty,Ltime,Luid,Lpid,Luptime')->
            view('Buyrecord',['COUNT(DISTINCT Buyrecord.Buid)'=>'Lnum'],'Buyrecord.Blppid=Lesson.Lid','LEFT')->
            where('Lpid=0')->group('Lid')->order('Luptime DESC')
                ->limit(6)->select();
            $this->m->set('courselist',$courselist); 
        }
        $courselist = $this->m->get('courselist');
        $num = count(db('user')->select()) - 4;
        $randNum = mt_rand(0,$num);
        $userlist=db('user')->limit($randNum,4)->select();
        $this->assign('courselist',$courselist);
        $this->assign('userlist',$userlist);
        return $this->fetch();
    }

    public function courselist(){
    	$view_type = input('?get.view_type')?input('get.view_type'):'card';
        $clabel = input('?get.clabel')?input('get.clabel'):1;
        $easy = input('?get.easy')?input('get.easy'):0;
        $pageIndex = input('?get.page')?input('get.page'):1;
        $sorttype = input('?get.sorttype')?input('get.sorttype'):'late';
        $keyword = input('get.keyword');
        $where['Lpid'] = 0;
        if($easy>0){
            $where['Ldifficulty']=$easy-1;
        }
        if($clabel>1){
            $where['Lcid']=$clabel;
        }
        if($keyword){
            $where['Ltitle|Ldesc'] = ['like',"%{$keyword}%"];
            $this->assign('havkey',1);
            $this->assign('keyword',$keyword);
        }
        $orderfield = $sorttype == 'late'?'Luptime' : 'Lnum';
        $count=db('lesson')->field('Lid')->where($where)->count();
        $courselist = Db::view('Lesson','Lid,Ltitle,Ldesc,Limage,Ldifficulty,Ltime,Luid,Lpid,Luptime')->
            view('Buyrecord',['COUNT(DISTINCT Buyrecord.Buid)'=>'Lnum'],'Buyrecord.Blppid=Lesson.Lid','LEFT')->
            where($where)->group('Lid')->order(array($orderfield=>'desc'))->page($pageIndex,9)->select(); 
        $clist=db('category')->where('CShow=1')->order('Cpid,Cid')->select();

        $catShow=array();
        foreach ($clist as $value) {
            if($value['Cpid']==0){
                $catShow[$value['Cid']]=array('title'=>$value['Cname'],'data'=>array());
            }else{
                $catShow[$value['Cpid']]['data'][]=array('Cid'=>$value['Cid'],'title'=>$value['Cname']);
            }
        }
        $cf=new commonfun();
        $pagesize = 9;
        $url='courselist'."?clabel=$clabel&page=__PAGE__&easy=$easy&sorttype=$sorttype&view_type=$view_type";
        $pageshow=$cf->Pagelist($pageIndex,$count,$url,$pagesize);
        $page = input('get.page');
        $this->assign('count',$count);
        $this->assign('pagesize',$pagesize);
        $this->assign('page',$page);
        $this->assign('uid',Session('uid'));
        $this->assign('clabel',$clabel);
        $this->assign('easy',$easy);
        $this->assign('count',$count);
        $this->assign('sorttype',$sorttype);
        $this->assign('view_type',$view_type);
        $this->assign('catShow',$catShow);
        $this->assign('couselist',$courselist);
        $this->assign('pageshow',$pageshow);
        return $this->fetch();
    }

    public function view(){
    	$cid=isset($_GET['Cid'])&&is_numeric($_GET['Cid'])?$_GET['Cid']:-1;
        if($cid==-1){
            $this->redirect('Index/Index/Errors');
        }
        $where['Lid']=$cid;
        $cInfo=db('lesson')->where($where)->find();
        $sublesson=db('lesson')->where('Lpid='.$cInfo['Lid'])->select();            
        $tInfo=db('user')->where('Uid='.$cInfo['Luid'])->find();
        $tlearners = db('Buyrecord')->field('DISTINCT Buid as num')->where('Blppid='.$cid)->select();
        $num = $tlearners? $tlearners[0]['num'] : 0;
        $this->assign('uid',session('uid'));
        $this->assign('cInfo',$cInfo);
        $this->assign('tInfo',$tInfo);
        $this->assign('num',$num);
        $this->assign('sublesson',$sublesson);
        return $this->fetch();
    }

    public function Errors(){
    	return $this->fetch();
    }

    public function learn(){
    	if(!session('uid')){
    		$this->error('尚未登陆','index'); //登陆页面
    	}
    	$where['Lid']=input('get.Cid');
        $lesson=db('lesson');  
        $user=db('user');
        $cInfo=$lesson->where($where)->find();

        $cLabel=db('category')->where('Cid='.$cInfo['Lcid'])->value('Cname');
        $tInfo=db('user')->where('Uid='.$cInfo['Luid'])->find();
        $sublesson=db('lesson')->where('Lpid='.$cInfo['Lid'])->select();

        $len=count($sublesson);
        for($i=0;$i<$len;$i++){
            $sublesson[$i]['subcourse']=$lesson->where('Lpid='.$sublesson[$i]['Lid'])->field('Lid,Ltitle,Ltime,Lbeans')->select();
        }
        $tlearners = $user->where('Uid in(SELECT DISTINCT Ruid FROM os_ruserlesson WHERE RLpid='.$_GET['Cid'].')')->field('Uid,Uname,Uimg,Uprovince')->select();
        $len=count($tlearners);
        $lindex = $len>4? array_rand($tlearners,4) : $tlearners;//从数组中随机读取四个
        $learners = array();
        for($i=0;$i<count($lindex);$i++){
            $learners[$i]=$tlearners[$lindex[$i]];
        }
        $ruserlesson = db('ruserlesson');
        $studytime = $ruserlesson->where('Rid in(SELECT Rid FROM os_ruserlesson WHERE RUid='.session('uid').' AND Rlppid='.$_GET['Cid'].')')->sum('Rstudytime');
       // $studytime = $studytime?$studytime:0;
        $num = $ruserlesson->distinct(true)->field('RUid')->where('Rid in(SELECT Rid FROM os_ruserlesson WHERE Rlppid='.$_GET['Cid'].')')->count();

        $tlids = $ruserlesson->field('DISTINCT RLid,Rstudytime')->where('Rid in(SELECT Rid FROM os_ruserlesson WHERE RUid='.session('uid').' AND Rlppid='.$_GET['Cid'].')')->select();
        $len=count($tlids);
        $all = $lids = array();
        for($i=0;$i<$len;$i++){
            $tlids[$i]['studytime'] = $lesson->field('Ltime')->where('Lid='.$tlids[$i]['RLid'])->select();
            if(!in_array($tlids[$i]['RLid'],$all)){
                $all[] = $tlids[$i]['RLid'];
                if ($tlids[$i]['Rstudytime'] < $tlids[$i]['studytime']) {
                    // 未学完
                    $lids[] = $tlids[$i]['RLid'];
                } else {
                    $haslids[] = $tlids[$i]['RLid'];
                }
            } 
        }
        $this->assign('uid',session('uid'));
        $this->assign('fcid',$sublesson ? $sublesson[0]['subcourse'][0]['Lid']:0);
        $this->assign('cInfo',$cInfo);
        $this->assign('studytime',$studytime);
        $this->assign('cLabel',$cLabel);
        $this->assign('tInfo',$tInfo);
        $this->assign('learners',$learners);
        $this->assign('num',$num);
        $this->assign('all',$all);
        $this->assign('lids',$lids);
        $this->assign('haslids',$lids);
        $this->assign('sublesson',$sublesson);
        return $this->fetch();
    }

    public function video(){
        //未登录跳转首页
        $uid=session('uid');
        if(!isset($uid)||empty($uid)){
            $this->redirect('index');
        }
        $user=db('user');
        $userinfo=$user->where('Uid='.$uid)->find();//获取用户信息
        
        //视频逻辑处理
        $vid=empty($_GET['Vid'])||!is_numeric($_GET['Vid'])?0:$_GET['Vid'];//视频id
        if($vid==0){//跳转404
            $this->redirect('Errors');
        }
        $lessonModel=db('lesson');
        $cInfo=$lessonModel->where('Lid='.$vid)->find();//该节的信息
        if(empty($cInfo)){//非法vid
            $this->redirect('Errors');
        }
        $videoType=substr($cInfo['Limage'],strrpos($cInfo['Limage'], '.')+1);
        //付费处理
        $buyrecordModel=db('buyrecord');
        // $buyrecord=$buyrecordModel->where('Buid='.$uid.' AND Blid='.$vid)->find();
        $status = array('statno'=>0,'msg'=>'');//返回状态，1积分不够，2积分够，且开启不再提醒，3积分够，提醒
        // if(empty($buyrecord)){//没购买过
            $userbeans=$userinfo['Ubeans'];
            $userdefault=$userinfo['Udefault'];
            $lessonbeans=$cInfo['Lbeans'];
            // if($userbeans>=$lessonbeans&&$userdefault==1){//余额够，并且设置不再提醒
                // $userModel->where('Uid='.$uid)->setDec('Ubeans',$lessonbeans);//扣虚拟币
                // $Bdata['Buid']=$uid;
                // $Bdata['Blid']=$vid;
                // $Bdata['Blpid']=$cInfo['Lpid'];
                // $Bdata['Blppid']=$cInfo['Lppid'];
                // $Bdata['Bbeans']=$lessonbeans;
                // $Bdata['Btime']=date('Y-m-d H:i:s');
                // $Bdata['Bstatus']=1;
                // $buyrecordModel->add($Bdata);
            // }else
            if($userbeans>$lessonbeans){//Aidan 一直提醒扣除积分
                $status=array('statno'=>3,'msg'=>'每次观看本课程将扣除你'.$lessonbeans.'个积分');
            }else{
                $status=array('statno'=>1,'msg'=>'你当前的积分不够了');
            }
        // }
        $tInfo=$user->where('Uid='.$cInfo['Luid'])->find();//老师相关的信息
        $lessonInfo=$lessonModel->where('Lid='.$cInfo['Lppid'])->find();//该课程的信息
        $userlesson=db('ruserlesson');//学习课程
        $havlen=$userlesson->where('RUid='.$uid.' And '.'RLid='.$vid)->count('Rid');
        if($havlen==0&&$status['statno']==0){//没有学习，则添加学习记录
            $data['RUid']=$uid;
            $data['RLid']=$vid;
            $data['Rstarttime']=date('Y-m-d H:i:s');
            $data['Rlpid']=$cInfo['Lpid'];
            $data['Rlppid']=$cInfo['Lppid'];
            $userlesson->add($data);
        }

        //共同学习者
        $tlearners = db('User')->
            where('Uid in(SELECT Ruid FROM os_ruserlesson WHERE RLid='.$vid.')')->
            field('Uid,Uname,Uimg,Uprovince')->select();
        $tlearners = count($tlearners)>4? array_rand($tlearners,4) : $tlearners;
        //笔记
        
        if(!$this->m->get('notes')){
            $where1['Nlid']=$vid;
            $where1['Ntype']=0;
            $notes = Db::view('Note','Nid,Ndesc,Nlid,Ndate,Ntype,Nuid')->
            	view('User','Uname,Uimg','User.Uid=Note.Nuid')->
            	view('Lesson','Lid,Ltitle,Lpid','Lesson.Lid=Note.Nlid')->
            	where($where1)->order('Ndate DESC')->select();
            $this->m->set('notes',$notes);
        }
        $notes = $this->m->get('notes');
        // 问答
        // $where3['Qlid']=$vid;
        // $questions=D('UserquestionView')->where('Qlid='.$vid.' and Qpid = 0')->order('Qdate DESC')->select();
        // foreach ($questions as &$val) {
        //     $val['answers'] = M('question')->where('Qpid='.$val['Qid'])->select();
        //     // foreach ($val as &$value) {
        //     //     $value['Quid'] = M('user')->where('Uid ='.$value['Quid'])->field('Uname,Uimg')->find();
        //     //     var_dump($value['Quid']);
        //     // }
        // }
        //评论
        // $where2['Dlid']=$vid;
        // $comments=D('UsercommentView')->where($where2)->order('Ddate DESC')->select();
        
        //目录结构，
        
        if(!$this->m->get(['zhang','jie'])){
            $zhang=$lessonModel->where('Lpid='.$cInfo['Lppid'])->order('Lid ASC')->select();//获取章信息
            $jie=$lessonModel->where('Lppid='.$cInfo['Lppid'])->order('Lpid,Lid ASC')->select();//获取节信息
            $this->m->set(['zhang'=>$zhang,'jie'=>$jie]);
        }
        $result = $this->m->get(['zhang','jie']);
        $sublesson=array();
        $num = count($tlearners);
        handleZJ($result['zhang'], $result['jie'], $sublesson, $num); //common function

        //前端赋值
        $this->assign('title','vivi视频');
        $this->assign('uid',$uid);
        $this->assign('lid',$vid);
        $this->assign('status',$status);
        $this->assign('course_id',$lessonInfo['Lid']); //$pid
        $this->assign('cInfo',$lessonInfo);
        $this->assign('nowlesson',$cInfo);
        $this->assign('videotype',$videoType);
        $this->assign('sublesson',$sublesson);
        $this->assign('tInfo',$tInfo);
        $this->assign('learners',$tlearners); 
        $this->assign('num',$num);
        $this->assign('notes',$notes);
        //$this->assign('comments',$comments);
        //$this->assign('questions',$questions);
        return $this->fetch();
    }

    public function note(){
        $where['Lid']=$_GET['Cid'];    //每个课程的id
        $lesson=db('lesson');
        $user=db('user');
        $cInfo=$lesson->where($where)->find();      //每节课程的相关信息
        $cLabel=db('category')->where('Cid='.$cInfo['Lcid'])->column('Cname');//课程所属的类别
        $tInfo=$user->where('Uid='.$cInfo['Luid'])->find();  //课堂对应老师的信息

        $tlearners = $user->field('Uid','Uname','Uimg','Uprovince')->
            where('Uid in(SELECT Distinct Ruid FROM os_ruserlesson WHERE RLpid='.$_GET['Cid'].')')->select();

        $tlearners = count($tlearners)>4? array_rand($tlearners,4) : $tlearners;
        for($i=0;$i<count($tlearners);$i++){
              $learners[$i]=$tlearners[$lindex[$i]];
        }
        if(!$this->m->get('notes1')){
            $notes1=Db::view('Note','Nid,Ndesc,Nlid,Ndate,Ntype,Nuid')->
                view('User','Uname,Uimg','User.Uid=Note.Nuid')->
                view('Lesson','Lid,Ltitle,Lpid','Lesson.Lid=Note.Nlid')->
                where('Lpid IN(SELECT Lid FROM os_lesson WHERE Lpid='.$_GET['Cid'].')')->select();
            $this->m->set('notes1',$notes1);
        }
        $notes = $this->m->get('notes1');
        $this->assign('uid',session('uid'));
        $zj=$_GET['zj'];
        if(!(isset($zj)&&$zj)){
            $zj='1-1';
        }
        $jdt=$_GET['jdt']?$_GET['jdt']:0;
        $studytime = db('Ruserlesson')->where('Rid in(SELECT Rid FROM os_ruserlesson WHERE RUid='.session('uid').' AND Rlppid='.$_GET['Cid'].')')->sum('Rstudytime');
        $studytime=$studytime?$studytime:0;
        $num = M('Ruserlesson')->distinct(true)->field('RUid')->where('Rid in(SELECT Rid FROM os_ruserlesson WHERE Rlppid='.$_GET['Cid'].')')->count();
        $this->assign('cInfo',$cInfo);
        $this->assign('zj',$zj);
        $this->assign('jdt',$jdt);
        $this->assign('cLabel',$cLabel);
        $this->assign('tInfo',$tInfo);
        $this->assign('learners',$learners);
        $this->assign('num',$num);
        $this->assign('studytime',$studytime);
        $this->assign('notes',$notes);
        return $this->fetch();
    }

    public function addData() {
        $province = '河北';
        $data = db('user')->where('Uprovince="未填写"')->select();
        foreach ($data as &$val) {
            $val['Uprovince'] = '河北';
            $val['Ucity'] = '保定';
        }
        for ($i = 0; $i < count($data); $i++) {
            $judge = db("user")->where("Uid=".$data[$i]['Uid'])->save($data[$i]);
            if (!$judge) {
                break;
            }
        }
    } 


}
