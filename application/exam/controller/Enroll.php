<?php
namespace app\exam\controller;

use think\Controller;
use Think\Db;


header("Content-type: text/html; charset=utf-8");
class Enroll extends Controller {

 	public $uid;
	private $saveDirectory;
	public $urlArr;
	public function __construct () {
		
		parent::__construct();
		$this->uid = session("uid");

		$this->saveDirectory = './Public/upimages/'.date('Y-m-d', time())."/";
		
		$path = ROOT_PATH."/index.php/Exam/Enroll/";
		$this->urlArr = array(
			"profile"     =>   $path."profile.html",
			"eduback"     =>   $path."eduback.html",
			"ability"     =>   $path."ability.html",
			"family"     	=>   $path."family.html",
			"practice"    =>   $path."practice.html",
			"academy"     =>   $path."academy.html",
			"award"       =>   $path."award.html",
			"attachment"  =>   $path."attachment.html",
			"othercase"   =>   $path."othercase.html"
		);
		$this->assign("uid", $this->uid);
		$this->assign("urlinfo", $this->urlArr);
	}
	private function guid() {
		return preg_replace_callback("/[xy]/u",function ($c) {
			$r = rand(0, 15);
			$v = $c == "x" ? $r : ($r & 0x3);
			return hexdec($v);
		}, "xxx5yx");
	}
	public function validatee() {
		$mobile = input("post.")["mobile"];
		$guid = $this->guid();

		require ROOT_PATH."submail/app_config.php";
		require_once(ROOT_PATH."submail/SUBdbAinputLAutoload.php");

		$submail=new \dbESSAGEXsend($message_configs);
		$submail->setTo($mobile);
		$submail->AddVar("code", $guid);
		$submail->SetProject("DBSKq2");
		$xsend = $submail->xsend();
		if ($xsend["status"] == "success") {
			session_start(); 
			$lifeTime = 120; 
			setcookie("validate", $guid, time() + $lifeTime); 
			return 1;
		} else {
			return 0;
		}
	}

	/*上传*/
	private function uploadFile($name){
  	if (!file_exists($this->saveDirectory)) {
  		mkdir($this->saveDirectory);
  	}
    // $upload = new \Think\Upload();
    // $upload->rootPath = $this->saveDirectory;
    // $upload->saveName = array('uniqid','');
    // $arr = $upload->upload();
    $arr = request()->file()->move($this->saveDirectory);
    $arr = $arr[$name];
    //$result = $this->saveDirectory.$arr["savepath"].$arr["savename"];
    $result = $this->saveDirectory.$arr->getPath().$arr->getSaveName;
  	$result = preg_replace("/\.\//", __ROOT__."/", $result);
  	return $result;
	}
	/*判断是否允许*/
	private function allow($num) {
		db("registers_info")->where("uid=$this->uid")->setField("status", $num);
	}
	/*注册成功后,登录网站*/
	public function login ($email) {
		$data['Uemail'] = $email; 
		$res = db("user")->where($data)->find();
  		session('uid',$res['Uid']);
  		return session("uid");
	}
	/*用户注册*/
	public function register () {
		if (!empty($this->uid)) {
			if ($this->uid == 11) {
				$this->redirect("User/Teacher/showstu");
			}
			$data = db("registers_info")->where("uid=".$this->uid)->find();
			if (!empty($data)) {
				$this->redirect("Exam/Enroll/profile");
			}
		}
		$this->assign("title", "新用户注册-");
		return $this->fetch();
	}
	/*接受注册信息*/
	public function acceptRegister () {
		$arr = input("post.");
		$arr["password"] = md5($arr["password"]);
		if (model("Registersinputnfo")->addBasicinputnfo($arr)) {
			$this->login($arr['email']);
			$this->redirect("Home/inputndex/index");
		}
	}
	/*更新数据*/
	public function saveProfile () {
		$arr = input("post.");
		$arr["updatetime"] = date('Y-m-d H:i:s');
		$arr["photo"] = $this->uploadFile("photo");
		if (db("registers_info")->where("uid=".$this->uid)->insert($arr)) {
    	$this->redirect("Exam/Enroll/eduback");
    }
	}
	/*接受基本信息*/
	public function acceptProfile () {
		$arr = input("post.");
		$arr["photo"] = $this->uploadFile("photo");
		$result = model("Registersinputnfo")->updateinputnfo($arr);
		if ($result) {
			$this->redirect("Exam/Enroll/eduback");
		}
	}
	public function profile () {
		$data = db("registers_info")->where('uid='.$this->uid)->find();
		$political = db("political")->select();
		$nation = db("nation")->select();
		$marry = db("marry")->select();
		$province = db("province")->select();

		if (!empty($data['city'])) {
			$city = db("city")->where("CUpinputd=".$data["province"])->select();
			$town = db("town")->where("TUpinputd=".$data["city"])->select();
			$this->assign("city", $city);
			$this->assign("town", $town);
		}
 
		$this->assign("marry", $marry);
		$this->assign("data", $data);
		$this->assign("nation", $nation);
		$this->assign("political", $political);
		$this->assign("province", $province);
		return $this->fetch();
	}

	public function city () {
		$data['CUpinputd'] = input("post.code");
		$arr = model("Registersinputnfo")->chooseCity($data);
		return $arr;
	}

	public function town () {
		$data['TUpinputd'] = input("post.code");
		$arr = model("Registersinputnfo")->chooseTown($data);
		return $arr;
	}
	public function eduback() {
	    $data = db("education")->where("id=$this->uid")->find();

	    $learning_methods = db("learning_methods")->select();
	    $education_history = db("education_history")->select();
	    $Enrollment_batch = db("Exam/Enrollment_batch")->select();
	    $highest_education = db("highest_education")->select();
	    $degree = db("degree")->select();
	    $discipline_level = db("discipline_level")->select();
	    
	    $this->assign("data", $data);
	    $this->assign("learning_methods", $learning_methods);
		$this->assign("education_history", $education_history);
		$this->assign("Enrollment_batch", $Enrollment_batch);
		$this->assign("highest_education", $highest_education);
		$this->assign("degree", $degree);
		$this->assign("discipline_level", $discipline_level);
		return $this->fetch();
	}
	public function addedu() {
	  	$map = input("post.");
	  	$user = db("education");
	    $map['updatetime'] = time();
	    $map['id'] = $this->uid;
	    $this->allow(2);
	    if ($user->insert($map)) {
	    	$this->redirect("Exam/Enroll/ability");
	    }
	}
	public function savedu() {
	  	$map = input("post.");
	  	$user = db("education");
	    $map['updatetime'] = time();
	    $map['id'] = $this->uid;
	    if ($user->insert($map)) {
	    	$this->redirect("Exam/Enroll/ability");
	    }
	}
	public function ability() {
		$data = db("language")->where("id=$this->uid")->find();
		$language_name = db("language_name")->select();
		$language_level = db("language_level")->select();
		$skill_level = db("skill_level")->select();

	    $this->assign("data",$data);
	    $this->assign("language_name",$language_name);
	    $this->assign("language_level",$language_level);
	    $this->assign("skill_level",$skill_level);
	    return $this->fetch();
	}
	public function addabi() {
	  	$map = input("post.");
	  	$user = db("language");
	    $map['updatetime'] = time();
	    $map['id'] = $this->uid;
	    $this->allow(3);
	    if ($user->insert($map)) {
	    	$this->redirect("Exam/Enroll/ability");
	    }
	}
	public function family() {
		$data = db("family")->where("id=$this->uid")->find();
		$this->assign("data",$data);
		return $this->fetch();
	}
	public function addfam() {
	  	$map = input("post.");
	  	$user = db("family");
	    $map['updatetime'] = time();
	    $map['id'] = $this->uid;
	    $this->allow(4);
	    if ($user->insert($map)) {
	    	$this->redirect("Exam/Enroll/practice");
	    }
	}
	public function savefam() {
	  	$map = input("post.");
	  	$user = db("family");
	    $map['updatetime'] = time();
	    $map['id'] = $this->uid;
	    if ($user->insert($map)) {
	    	$this->redirect("Exam/Enroll/practice");
	    }
	}
	public function practice() {
		$data = db("practice")->where("id=$this->uid")->find();
		$work_form = db("work_form")->select();
		$this->assign("data",$data);
		$this->assign("work_form",$work_form);
		return $this->fetch();
	}
	public function addpra() {
	  	$map = input("post.");
	  	$user = db("practice");
	    $map['updatetime'] = time();
	    $map['id'] = $this->uid;
	    $this->allow(5);
	    if ($user->insert($map)) {
	    	$this->redirect("Exam/Enroll/academy");
	    }
	}
	public function savepra() {
	  	$map = input("post.");
	  	$user = db("practice");
	    $map['id'] = $this->uid;
	    $map['updatetime'] = time();
	    if ($user->insert($map)) {
	    	$this->redirect("Exam/Enroll/academy");
	    }
	}
	public function academy() {//学术成果
		$data = db("academy")->where("id=$this->uid")->find();
		$paper_level = db("paper_level")->select();
		$project_level = db("project_level")->select();

		$this->assign("data",$data);
		$this->assign("paper_level",$paper_level);
		$this->assign("project_level",$project_level);
		return $this->fetch();
	}
	public function addaca() {
	  	$map = input("post.");
	  	$user = db("academy");
	    $map['updatetime'] = time();
	    $map['id'] = $this->uid;
	    $this->allow(6);
	    if ($user->insert($map)) {
	    	$this->redirect("Exam/Enroll/award");
	    }
	}
	public function saveaca() {
	  	$map = input("post.");
	  	$user = db("academy");
	    $map['updatetime'] = time();
	    $map['id'] = $this->uid;
	    if ($user->insert($map)) {
	    	$this->redirect("Exam/Enroll/award");
	    }
	}
	public function award() {
		$data = db("award")->where("id=$this->uid")->find();
		$reward_name = db("reward_level")->select();

		$this->assign("data",$data);
		$this->assign("reward_level",$reward_name);
		return $this->fetch();
	}
	public function addawa() {
	  	$map = input("post.");
	  	$user = db("award");
	    $map['updatetime'] = time();
	    $map['id'] = $this->uid;
	    $this->allow(7);
		$map["uploadcertificate"] = $this->uploadFile("uploadcertificate");
	    if ($user->insert($map)) {
	    	$this->redirect("Exam/Enroll/attachment");
	    }
	}
	public function saveawa() {
	  	$map = input("post.");
	  	$user = db("award");
	    $map['updatetime'] = time();
	    $map['id'] = $this->uid;
			$map["uploadcertificate"] = $this->uploadFile("uploadcertificate");
	    if ($user->insert($map)) {
	    	$this->redirect("Exam/Enroll/attachment");
	    }
	}
	public function attachment() {
		$data = db("attachment")->where("id=$this->uid")->find();
		$attachment_type = db("attachment_type")->select();
		$this->assign("data",$data);
		$this->assign("attachment_type",$attachment_type);
		return $this->fetch();
	}
	public function addattach() {
		$arr = input("post.");
		$arr["id"] = $this->uid;
		$arr["updatetime"] = time();
		$arr["uploadcertificate"] = $this->uploadFile("uploadcertificate");
		$result = db("attachment")->insert($arr);
	    $this->allow(8);
		if ($result) {
			$this->redirect("Exam/Enroll/othercase");
		}
	}
	public function saveattach() {
		$arr = input("post.");
		$arr["id"] = $this->uid;
		$arr["updatetime"] = time();
		$arr["uploadcertificate"] = $this->uploadFile("uploadcertificate");
		$data = $_SERVER['DOCUdbENT_ROOT'].db("attachment")->where("id=$this->uid")->value("uploadcertificate");
		if (file_exists($data)) {
			@unlink($data);
		}
		$result = db("attachment")->insert($arr);
		if ($result) {
			$this->redirect("Exam/Enroll/othercase");
		}
	}
	/*显示其他情况*/
	public function othercase() {
		$user = db("othercase");
		$data = $user->where("id=$this->uid")->find();
		$this->assign("data",$data);
		return $this->fetch();
	}
	public function addcase() {
		$arr = input("post.");
		$arr["updatetime"] = time();
		$arr['id'] = $this->uid;
	    $this->allow(8);
		$result = db("othercase")->insert($arr);
		if ($result) {
			$this->redirect("Exam/Enroll/show");
		}
	}
	public function savecase() {
		$arr = input("post.");
		$arr["updatetime"] = time();
		$arr['id'] = $this->uid;
		$result = db("othercase")->insert($arr);
		if ($result) {
			$this->redirect("Exam/Enroll/othercase");
		}
	}
	/*显示填写完信息*/
	public function show () {
		$allow = db("registers_info")->where("uid=$this->uid")->value("status");
		if ($allow <= 2) {
			echo "<script>alert('请先完成个人信息与学历经历填写');window.location.href='http://www.vivisp.com/index.php/Enroll/eduback.html';</script>";

		}
		$sql = "select * from os_registers_info r, os_education e, os_political po, ".
					 "os_nation na, os_town t ".	
					 "where r.uid = e.id and e.id = $this->uid and r.political = po.id ".
					 "and r.nation = na.id and t.Tcode = r.town";
		$data = Db::query($sql);
		$this->assign('data', $data[0]);
		return $this->fetch();
	}
}

?>
