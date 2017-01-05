<?php

namespace app\index\controller;
use think\Controller;

class About extends Controller
{
	// public function index(){
	// 	return $this->fetch('index',['uid'=>session('uid')]);
	// }

	// public function explain(){
	// 	return $this->fetch('explain',['uid'=>session('uid')]);
	// }

	// public function contact(){
	// 	return $this->fetch('contact',['uid'=>session('uid')]);
	// }

	public function _empty($name){
		if(in_array($name,['index','explain','contact'])){
			return $this->fetch($name,['uid'=>session('uid')]);
		}else{
			return $this->fetch('index',['uid'=>session('uid')]);
		}
	}

}