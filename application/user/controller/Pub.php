<?php
namespace app\user\controller;
use think\Controller;
use Core\commonfun;
use think\Db;
use Mem\mem;

class Pub extends Controller
{   
    //生成验证码链接
    public function captcha($id){
        return captcha_src($id);
    }

    //验证验证码
    public function cap_check(){

        $id = input('post.id');
        $data = array('captcha'=>input('post.captcha'));
        $result = $this->validate($data,['captcha|验证码'=>'require|captcha:'.$id]);
        return $result;
    }
	public function Login(){
		$result=array('errno'=>0,'msg'=>'');
        $data['Uemail'] = input('post.Uemail');
        $osUser=db('user');
        $res=$osUser->where($data)->count();
        if(!$res){
            $result=array('errno'=>1,'msg'=>'邮箱未注册！');
            return $result;
        }
        $data['Upwd']=md5(input('post.Upwd'));
        $res=$osUser->where($data)->find();
        if(!$res){
            $result=array('errno'=>2,'msg'=>'密码错误！');
            return $result;
        }

        $osUser->where($data)->setInc('UloginTimes');
        $osUser->where($data)->setField('UupTime',date('Y-m-d H:i:s'));
        session('uid',$res['Uid']);
        //Session('uname',$res['Uname']);
        $result = array('errno'=>4,'msg'=>url("User/User/index"));
        return $result;
	}

    public function exitSys(){
        session('uid',null);
        $this->redirect('index/index/index');
    }

    /*  注册  */
    
	public function register(){
		 $result=array('errno'=>0,'msg'=>'');
        //$data['Uname']=input('post.name');
        //$data['Upwd']=md5(input('post.pwd');
        //$data['Uemail']=input('post.email');
        //$data['Ustatus']=1;
        $data = input('post.');
        $data['Utoken'] = input('post.Uname').time(); //.md5($_PuploadVideouploadVideoOST['pwd'])
        $token = md5(input('post.Uname').md5(input('post.Upwd')).time());
        //$data['Utype']=0;//只注册学生身份
        $data['Uimg']=$this->makerangimage();
        //$data['Ubeans'] = 20;
        //$data['UupTime']=date('Y-m-d H:i:s');
        $osUser=model('User');
        $res=$osUser->where('Uemail=\''.input('post.Uemail').'\'')->count();
        if($res){
            $result=array('errno'=>1,'msg'=>'邮箱已经注册过了,赶紧换个邮箱试试吧！');
            return $result;
        }
        $res1 = $osUser->save($data);
        $data1['Mdesc']='欢迎注册ViVi视频，如有疑问，请给系统管理员留言,我们会尽快为你解答！';
        $data1['Mdate']=date('Y-m-d H:i:s',time());
        $data1['Muid']=1;
        $data1['Mtouid']=$res1;
        $res = db('message')->insert($data1);
        if($res1 && $res){
            // $mail = input('post.Uemail'];
            // $url='http://'.C('WEBSITE').'/index.php/Public/checkMail?verify='.$token.'&from='.base64_encode($mail);
            // $content="欢迎注册ViVi视频，请点击以下链接进行邮箱验证。<br/>";
            // $content.="<a href='$url' >$url</a>";
            // $content.="<br/>如果您的邮箱不支持链接点击，请将以上链接地址拷贝到你的浏览器地址栏中。";
            // $subject = "vivi视频注册邮箱验证";
            $ok= true;//sendMail($mail,$subject,$content);
            if($ok){
                //$href='http://mail.'.substr($mail, strpos($mail, '@')+1);
                //$result=array('errno'=>0,'msg'=>$href);
                return $result;
            }else{
                return $result;
            }
        }else{
            return $result;
        }
	}

    /*接收邮箱验证*/
    public function checkMail(){
        header('Content-type:text/html;charset=utf-8');
        $token = input('get.verify');
        $from = base64_decode(input('get.from'));
        $data['Uemail'] = $from;
        $user = db('user');
        $message = $user->where($data)->find();
        if($message['Utoken'] == $token){
            $user->Ustatus = '1';
            $user->where($data)->save();//更新用户状态
            echo "<script>alert('邮箱验证成功，点击“确定”开启你的学习之旅吧！！');window.location.href='http://".C('WEBSITE')."/index.php/Index/index.html';</script>";
        }else{
            $user->where($data)->delete();//删除邮箱无效的用户
            echo "<script>alert('邮箱验证失败，点击“确定”重新注册！！');window.location.href='http://".C('WEBSITE')."/index.php/Index/index.html';</script>";
        }
    }

    /*忘记密码*/
    public function forgetpwd(){
        $email = input('post.email');
        $user = db('user');
        $data['Uemail'] = $email;
        $have = $user->where($data)->find();
        
        if($have){
            $token = $have['Utoken'];
            $content = "欢迎注册ViVi视频，请点击以下链接进行密码重置。<br/>";
            $content.="<a href=http://".config('WEBSITE')."/index.php/Public/findbackpwd?verify=".$token."&from=".base64_encode($email).">";
            $content.="http://".config('WEBSITE')."/index.php/Public/findbackpwd?verify=".$token."&from=".base64_encode($email)."</a>";
            $content.="<br/>如果您的邮箱不支持链接点击，请将以上链接地址拷贝到你的浏览器地址栏中。";
            $subject = "vivi视频找回密码邮箱验证";
            sendMail($email,$subject,$content);
            $href='http://mail.'.substr($email, strpos($email, '@')+1);
            $result=array('errno'=>1,'msg'=>$href);
            return $result;
        }else{
            $result=array('errno'=>0,'msg'=>"http://".config('WEBSITE')."/index.php/index");
            return $result;
        }
    }

    /*找回密码*/
    public function findbackpwd(){
        $token = input('get.verify');
        $from = base64_decode(input('get.from'));
        $data['Uemail'] = $from;
        $user = db('user');
        $message = $user->where($data)->find();
        if($message['Utoken'] == $token){
            $this->assign('email',$from);
            return $this->fetch('../view/user/findbackpwd');
        }else{
            echo "<script>alert('找回密码失败，点击“确定”重新注册！！');window.location.href='http://".C('WEBSITE')."/index.php/Index/index.html';</script>";
        }
    }

    /*重置密码*/
    public function changepwd(){
        $pwd = md5(input('post.newpwd'));
        $user = db('user');
        $data1['Uemail'] = input('post.re_email');
        $data2['Upwd'] = $pwd;
        $ok = $user->where($data1)->save($data2);
        if($ok){
            $result = array('errno'=>1,'msg'=>"http://".config('WEBSITE')."/index.php/index");
            return $result;
        }else{
            $result = array('errno'=>0,'msg'=>"");
            return $result;
        }
    }

    /*生成随机头像*/
    private function makerangimage(){
       $url = 'default/default';
       $url.= mt_rand(1,16);
       return $url.'.jpg'; 
    }

        /*换一换*/
    public function makeRandImg(){
        $headimg=$this->makerangimage();
        $uid=session('uid');
        db('user')->where('Uid='.$uid)->setField('Uimg',$headimg);
        return  $headimg;
    }
    /*上传*/
    private function uploadFile($saveDirectory='../../../public/static/upimages/',$upfile="upfile"){
        $file = request()->file('upfile');
        $info = $file->validate(['size'=>2145728,'ext'=>'jpg,gif,png,jpeg'])->move($saveDirectory);

        // $upload = new \Think\Upload();// 实例化上传类
        // $upload->maxSize  = 3145728;// 设置附件上传大小
        // $upload->exts  = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        // $upload->rootPath =$saveDirectory;// 设置附件上传目录
        // $upload->savePath  =''; // 设置附件上传（子）目录
        // $upload->saveName = array('uniqid','');
        // $upload->autoSub  = false;
        if(!$info) {// 上传错误提示错误信息
            $res['state']=0;
            $res['msg']=$info->getError();
        }else{// 上传成功 获取上传文件信息
            $res['state']=1;
            $res['msg']=$info->getSaveName();
        }
        return $res;
    }
    /*上传头像*/
    public function uploadImg(){
        $res=$this->uploadFile();
        if($res['state']){
            $uid=session('uid');
            db('user')->where('Uid='.$uid)->setField('Uimg',$res['msg']);
        }
        return $res;
    }
    /*获取头像信息*/
    public function getHeadInfo(){
        $uid=session('uid');
        $data=db('user')->where('Uid='.$uid)->field('Uimg,Uname')->find();
        $msg=db('message')->where('Mtouid='.$uid.' And MisLook=0')->count();
        $data['msg']=$msg;
        if($data){
            return $data;
        }
    }
    /*获取城市*/
    public function getCity(){
        $pcode=input('post.Pcode');
        $res=db('city')->where('CUpId='.$pcode)->select();
        return $res;
    }
    /*获取县区*/
    public function getTown(){
        $Ccode=input('post.Ccode');
        $res=db('town')->where('TUpId='.$Ccode)->select();
        return $res;
    }
    /*保存用户设置*/
    public function saveSetting(){
        $data['UupTime']=date('Y-m-d H:i:s');
        $data['Uname']=input('post.name');
        $data['Uprovince']=input('post.province');
        $data['Ucity']=input('post.city');
        $data['Utown']=input('post.town');
        $data['Usex']=input('post.sex');
        $data['Udesc']=input('post.desc');
        $data['Udefault']=input('post.yes');
        $uid=session('uid');
        $res=db('user')->where('Uid='.$uid)->save($data);
        if($res){
            return 1;
        }else{
            return 0;
        }
    }
    /*修改密码*/
    public function resetPwd(){
        $pwd=input('post.pwd');
        $npwd=input('post.npwd');
        $user=db('user');
        $uid=session('uid');
        $upwd=$user->where('Uid='.$uid)->value('Upwd');
        if(md5($pwd)==$upwd){
            $user->where('Uid='.$uid)->setField('Upwd',md5($npwd));
            return 1;
        }else{
           return 0;
        }
    }
    public function emailcheck(){

    }
    /*设置个人描述*/
    public function setDesc(){
        $data['Udesc']=input('post.desc');
        $data['UupTime']=date('Y-m-d h:i:s',time());
        $where['Uid']=session('uid');
        $res=db('user')->where($where)->update($data);
        if($res){
            return $data['UupTime'];
        }else{
            return 0;
        }
    }
    /*添加笔记*/
    public function addnote(){
        $data['Ndesc']=$_POST['content'];
        $data['Nlid']=$_POST['cid'];
        $data['Ndate']=date('Y-m-d H:i:s',time());
        $data['Ntype']=0;
        $data['Nuid']=session('uid');
        $res=db('note')->insert($data);
        if($res){
            return 1;
        }else{
            return 0;
        }
    }
    // 添加评论
    public function addcomment(){
        $data['Ddesc']=$_POST['content'];
        $data['Dlid']=$_POST['cid'];
        $data['Ddate']=date('Y-m-d H:i:s',time());
        $data['Duid']=session('uid');
        $res=db('comment')->insert($data);
        if($res){
            return 1;
        }else{
            return 0;
        }
    }
    // 添加问答
    public function addquestion(){
        $data['Qques']=$_POST['content'];
        $data['Qtit']=$_POST['tit'];
        $data['Qlid']=$_POST['cid'];
        $data['Qdate']=date('Y-m-d H:i:s',time());
        $data['Quid']=session('uid');
        $data['Qpid']=0;
        $res=db('question')->insert($data);
        if($res){
            return 1;
        }else{
            return 0;
        }
    }
    // 添加回答
    public function addanswer(){
        $arr = input('post.');
        $arr['Qanswer'] = $arr['content'];
        $arr['Quid'] = session('uid');
        $arr['Qlid'] = $arr['cid'];
        $arr['Qdate'] = date("Y-m-d H:i:s",time());
        $judge = db('question')->insert($arr);
        if ($judge) {
            return 1;
        } else {
            return 0;
        }
    }
    /*统计时间*/
    public function counttime(){
        $where['RUid']=session('uid');
        $where['RLid']=$_POST['cid'];
        db('ruserlesson')->where($where)->setInc('Rstudytime');
    }

        /*添加课程*/
    public function Addcourse(){
        $data['Luid']=Session('uid');
        $data['Ltitle']=$_POST['title'];
        $data['Ldesc']=$_POST['desc'];
        $data['Ltime']=$_POST['time'];
        $data['Ldifficulty']=$_POST['easy'];
        $data['Lcid']=$_POST['category'];
        $data['Luptime']=date('Y-m-d H:i:s');
        $res=db('lesson')->insert($data);
        if($res){
            return $res;
        }else{
            return 0;
        }
    }
    /*对课程进行单项设置*/
    private function setLessonKeyValue($cid,$key,$val){
        $data['Luptime']=date('Y-m-d H:i:s');
        $data[$key]=$val;
        $res = db('lesson')->where('Lid='.$cid)->save($data);
        if($res){
            return 1;
        }else{
            return 0;
        }
    }
    /*修改课程标题*/
    public function setCtitle(){
        return $this->setLessonKeyValue($_POST['Cid'],'Ltitle',$_POST['title']);
    }
    /*修改课程标题*/
    public function setCdesc(){
        return $this->setLessonKeyValue($_POST['Cid'],'Ldesc',$_POST['desc']);
    }
    /*修改课时*/
    public function setCtime(){
        return $this->setLessonKeyValue($_POST['Cid'],'Ltime',$_POST['ctime']);
    }
    //修改课程积分
    public function setCbeans(){
        return $this->setLessonKeyValue($_POST['Cid'],'Lbeans',$_POST['cbeans']);
    }
    /*修改课程封面图*/
    public function uploadCImg(){
        $res=$this->uploadFile('../../../public/static/upclassimages/');
        if($res['state']){
            db('lesson')->where('Lid='.$_POST['Cid'])->setField('Limage',$res['msg']);
        }
        return $res;
    }
    /*上传文件*/
    public function uploadCfile(){
        $where['Lid']=$_POST['Cid'];
        // $upload=new \Think\Upload();    //实例化上传类
        // $upload->maxSize  = 500000000;     //设置附件上传大小
        // $upload->exts  = array('ppt','txt','zip','doc','xls','gif','jpg','jpeg');   //设置附件上传类型
        // $upload->rootPath ='./Public/uploadSource/';      //文件上传保存的根目录
        // $upload->savePath='';    //文件上传的保存路径
        // $upload->saveName = array('uniqid');    //采用uniqid函数生成一个唯一的字符串序列
        // $upload->autoSub  = false;  
        $file= request()->file();
        $info = $file->validate(['size'=>500000000,'ext'=>'ppt,txt,zip,doc,xls,gif,jpg,jpeg'])->move('../../../public/static/uploadSource');

        if(!$info) {// 上传错误提示错误信息
            $res['state']=0;
            $res['msg']=$info->getError();
        }else{// 上传成功 获取上传文件信息
            $res['state']=1;
            $saveimg = $info->getSaveName();
            db('Lesson')->where($where)->setField('Lcfile',$saveimg);
            $res['msg']=$saveimg;
        }
        return $res;
    }

    /*创建课程*/
    public function createCourse(){
        $data['Lpid']=$_POST['cpid'];
        $data['Lppid']=empty($_POST['cppid'])?0:$_POST['cppid'];
        $data['Lbeans']=empty($_POST['cbeans'])?0:$_POST['cbeans'];
        $data['Ltitle']=$_POST['ctitle'];
        $data['Ldesc']=$_POST['cdesc'];
        $data['Luptime']=date('Y-m-d H:i:s',time());
        $data['Limage']='';
        $data['Ltime']=$_POST['ctime'];
        $data['Luid']=session('uid');
        $res=db('lesson')->insert($data);
        if($res){
            return $res;
        }else{
            return 0;
        }
    }
    /*修改课程*/
    public function updateChapter(){
        $data['Ltitle']=$_POST['ctitle'];
        $data['Ldesc']=$_POST['cdesc'];
        $data['Luptime']=date('Y-m-d H:i:s');
        $data['Luid']=session('uid');
        $res=db('lesson')->where('Lid='.$_POST['cid'])->update($data);
        echo $res;
        if($res){
            return $res;
        }else{
            return 0;
        }
    }
    /*获取节的内容*/
    public function getJies(){
        $where['Lpid']=$_POST['jiepid'];
        $res=db('lesson')->where($where)->select();
        if($res){
            return $res;
        }else{
             return 0;
        }
    }
    /*获取客课程*/
    public function getCousre(){
        $where['Lid']=$_POST['lxh'];
        $res=db('lesson')->where($where)->find();
        if($res){
            return $res;
        }else{
            return 0;
        }
    }
    /*删除课程*/
    public function del_course(){
        $where['Lid']=$_POST['cid'];
        $where['Lpid']=$_POST['cid'];
        $where['Lppid']=$_POST['cid'];
        $where['_logic']='OR';
        // 找到所有保存的文件路径
        $filepaths = db('lesson')->where($where)->field('Limage')->select();
        if ($this->delete_file($filepaths)) {
            $res=db('lesson')->where($where)->delete();
            if($res){
                return 1;
            }else{
                return 0;
            }
        } else {
            return 0;
        }
    }
    /*删除所有课程文件*/
    private function delete_file($arr){
        foreach ($arr as &$val) {
            if ($val['Limage'] !== '' && strpos($val['Limage'],'default.jpg') === false) {
                if (strpos($val['Limage'],'mp4') || strpos($val['Limage'],'flv')) {
                    $path = $_SERVER['DOCUMENT_ROOT']."Public/videoSource/videoSource/".$val['Limage']; 
                } else {
                    $path = $_SERVER['DOCUMENT_ROOT']."Public/upclassimages/".$val['Limage'];
                } 
                if (file_exists($path) && !unlink($path)) {
                    return false;
                }
            }
        }
        return true;
    }
    /*删除小节*/
    public function delete_abridge(){
        $where['Lid']=$_POST['cid'];
        // 定义删除文件路径
        $filepath = $_SERVER['DOCUMENT_ROOT']."Public/videoSource/videoSource/";
        $video = db('lesson')->where($where)->value('Limage');
        if (unlink($filepath.$video)) {
            $res=db('lesson')->where($where)->delete();
            if($res){
                return 1;
            }else{
                return 0;
            }
        } else {
            return 0;
        }
    }
    /*上传视频*/
    public function uploadVideo(){
        set_time_limit(0);
        $where['Lid']=$_POST['Cid'];
        // $upload = new \Think\Upload();// 实例化上传类
        // $upload->maxSize  = 50000000000;// 设置附件上传大小
        // $upload->exts  = array('flv','mp4', 'webm', 'ogv');// 设置附件上传类型
        // $upload->rootPath ='./Public/videoSource/videoSource/';
        // $upload->savePath='';
        // $upload->saveName = array('uniqid');
        // $upload->autoSub  = false;
        $file = request()->file();
        $info = $file->validate(['size'=>50000000000,'ext'=>'flv,mp4,webm,ogv'])->move('../../../public/static/videoSource/videoSource/');
        if(!$info) {// 上传错误提示错误信息
            $res['state']=0;
            $res['msg']=$info->getError();
        }else{// 上传成功 获取上传文件信息
            $res['state']=1;
            $saveimg=$info->getSaveName();
            db('Lesson')->where($where)->setField('Limage',$saveimg);
            $res['msg']=$saveimg;
        }
        return $res;
    }
    /*换学生*/
    public function changegroup(){
        $cid=$_POST['Cid'];
        // $model=new \Think\Model();
        // $sql='SELECT Uid,Uname,Uimg,Uprovince FROM os_user WHERE Uid in(SELECT Ruid FROM os_ruserlesson WHERE RLpid='.$cid.');';
        // $tlearners=$model->query($sql);
        $tlearners = db('user')->field('Uid,Uname,Uimg,Uprovince')->where('Uid','IN',db('Ruserlesson')->where('RLpid='.$cid.'')->value('Ruid'))->select();
        $lindex=array_rand($tlearners,4);//从数组中随机读取四个
        $len=count($lindex);
        for($i=0;$i<$len;$i++){
            $learners[$i]=$tlearners[$lindex[$i]];
        }
        return $learners;
    }
    /*采集笔记*/
    public function collectNote(){
        $where['Nid']=$_POST['Nid'];
        $note=db('note');
        $data=$note->where($where)->find();
        $uid=$data['Nuid'];
        if($uid==session('uid')){//采集自己的
            $this->ajaxReturn(2,'json');
        }
        $where2['Ntype']=$where['Nid'];
        $where2['Nuid']=session('uid');
        $len=$note->where($where2)->count();
        if($len>0){//已采集
            return 3;
        }
        $data['Ntype']=$data['Nid'];
        unset($data['Nid']);
        $data['Nuid']=session('uid');
        $data['Ndate']=date();
        $res=$note->insert($data);
        if($res){
            return 1;
        }else{
            return 4;
        }
    }
    /*获取用户信息*/
    public function getLUinfo(){
        $cid=$_POST['Cid'];
        // $sql='SELECT Uid,Uname,Udepartment,Usex,Uclass,UloginTimes,Rstudytime,Rwebjt,Rsjtp1,Rsjtp2 from  os_ruserlesson JOIN os_user ON os_ruserlesson.RUid=os_user.Uid WHERE RLid=__Cid__;';
        
        // $sql=str_replace('__Cid__',$cid,$sql);

        // $model=new Model();
        // $res=$model->query($sql);

        $res = Db::view('Ruserlesson','Uid,Uname,Udepartment,Usex,Uclass,UloginTimes,Rstudytime,Rwebjt,Rsjtp1,Rsjtp2')->
            view('User','','Ruserlesson.RUid=User.Uid')->where('RLid='.$cid)->select();
        if($res){
            return $res;
        }else{
            return 0;
        }
    }

    /*编辑笔记*/
    public function editnote(){
        $where['Nid']=$_POST['id'];
        $content=$_POST['content'];
        $res=db('note')->where($where)->setField('Ndesc',$content);
        if($res){
            return 1;
        }else{
            return 0;

        }
    }
    /*编辑评论*/
    public function editcomment(){
        $where['Did']=$_POST['id'];
        $content=$_POSt['content'];
        $res=db('comment')->where($where)->setField('Ddesc',$content);
        if($res){
            return 1;
        }else{
            return 0;
        }
    }
    /*删除笔记*/
    public function delnote(){
        $where['Nid']=$_POST['id'];
        $res=db('note')->where($where)->delete();
        if($res){
            return 1;
        }else{
            return 0;
        }
    }
    /*删除评论*/
    public function delcomment(){
        $where['Did']=$_POST['id'];
        $res=db('comment')->where($where)->delete();
        if($res){
            return 1;
        }else{
            return 0;
        }
    }

        /*获取学生列表*/
    public function getStudentlst(){
        $cid=$_POST['cid'];
        //$model=new \Think\Model();
        //$res=$model->query('SELECT Uid,Uname,Uimg FROM os_user WHERE Uid IN(SELECT DISTINCT RUid FROM os_ruserlesson WHERE os_ruserlesson.RLid='.$cid.');');
        $res = db('user')->field('Uid,Uname,Uimg')->where('Uid','IN',db('Ruserlesson')->where('RLid','EQ',$cid)->distinct(true)->filed('Ruid')->select());
        if($res){
            return $res;
        }else{
            return 0;
        }
    }
}
