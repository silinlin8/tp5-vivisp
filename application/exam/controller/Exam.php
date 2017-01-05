<?php
namespace app\exam\controller;

use think\Controller;

header("Content-type: text/html; charset=utf-8");
class Exam extends Controller {
	public function __construct () {
		parent::__construct();
		
		if (!session("guid")) {
			session("guid", $this->guid());
		}
		$this->assign("orders", session("guid"));

		$this->uid = session("uid");
		$this->assign("uid", $this->uid);
	}
	private function guid() {
		return preg_replace_callback("/[xy]/u",function ($c) {
			$r = rand(0, 15);
			$v = $c == "x" ? $r : ($r & 0x3);
			return hexdec($v);
		}, "xxxx5xxxyx");
	}
	public function login () {
		return $this->fetch();
	}
	public function burn_session() {
		$arr = input("post.");
		foreach ($arr as $key => $value) {
			session($key, $value);
		}
	}
	public function wait() {
		if (!session("name")) {
			$this->redirect("/Exam/Exam/login");
		}
		$this->assign("name", session("name")); 
		$this->assign("identity", session("identity"));
		return $this->fetch();
	}
	public function show () {
		if (!session("name")) {
			$this->redirect("/Exam/Exam/login");
		}
		if (!session("paper")) {
			$arr = db("paper")->where("status = 1")->select();
			$paper = $arr[mt_rand(0, count($arr) - 1)]['id'];
			session("paper", $paper);
		}
		$paper = session("paper");
        $data1 = db("exam")->field("grade,question")->where("paper = $paper and type = '1'")->select();
        $data2 = db("exam")->field("grade,question")->where("paper = $paper and type = '2'")->select();
        $data3 = db("exam")->field("grade,question")->where("paper = $paper and type = '3'")->select();
        $data4 = db("exam")->field("grade,question")->where("paper = $paper and type = '4'")->select();
        $data5 = db("exam")->field("grade,question")->where("paper = $paper and type = '5'")->select();
        $data = array_merge($data1, $data2, $data3, $data4, $data5);
        $this->assign("paper", $paper);
        $this->assign("length", 156);
        $this->assign("name", session("name")); 
		$this->assign("identity", session("identity"));
        $this->assign("data", $data);
        return $this->fetch();
    }
    public function grade () {
    	if (empty(session("name"))) {
    		$this->redirect("/Exam/Exam/login");
    	}
    	$arr = input("post.");
    	$questions = db("exam")->field("id,answer,grade")->where("paper=".$arr["paper"])->select();
/*    	$result = 0;
    	foreach ($questions as $key => $value) {
    		if (!empty($arr[$value["id"]])) {
    			$temp = $arr[$value["id"]];
    			$temp = is_array($temp)? implode(',',$temp):$temp;
    			if ($temp === $value['answer']) {
    				$result += $value["grade"];
    			}
    		}
    	}*/
    	$result = handleScore($arr,$questions);
    	$name = session("name");
    	$identity = session("identity");
    	session("name", null);
    	session("identity", null);
    	session("guid", null);
    	session("paper", null);
    	$this->assign("result", $result);
    	$this->assign("identity", $identity);
    	$this->assign("name", $name);
    	return $this->fetch();
    }
}