<?php
namespace app\exam\model;

use think\Model;

class RegistersInfo extends Model{

	public function addBasicInfo ($arr) {
		return $this->addUser($arr) 
				&& $this->addRegisterInfo($arr) ;
	}
	private function addRegisterInfo ($arr) {
		$result = db("registers_info")->add($arr);
		return $result;
	}
	private function addUser (&$arr) {
		$rand = floor(rand(1, 16));
		$data = array(
			"Uname"     =>  $arr['realname'],
			"Upwd"			=>  $arr['password'],
			"Utype"			=>  0,
			"Usex"      =>  $arr['sex'],
			"Uimg"			=>  "default/default$rand.jpg",
 			"Udesc"     =>  "这个学生很懒，什么都还木有留下……",
 			"Uprovince" =>  "河北",
 			"Ucity"     =>  "保定",
 			"Utown"			=>  "北市",
 			"Uemail"		=>  $arr["email"],
 			"UupTime"		=>  date("Y-m-d H:i:s", time()),
 			"Ustatus"   =>  1
 		);
 		return $arr['uid'] = db("user")->add($data);;
	}
	public function chooseCity ($arr) {
		$arr = db("city")->field("Ccode,CName")->where($arr)->select();
		return $arr;
	}
	public function chooseTown ($arr) {
		$arr = db("town")->field("Tcode,TName")->where($arr)->select();
		return $arr;
	}
	private function cancelArr ($arr) {
		$arr['uid'] = session("uid");

		$arr['gradtime'] = $arr['graduateTime'];
		$arr['gradtype'] = $arr['graduateType'];
		$arr['postkod'] = $arr['postalcode'];

		$arr["data"] = array(
			"Uprovince"   =>   db("province")->where("Pcode=".$arr['province'])->value("Pname"),
			"Ucity"       =>   db("city")->where("Ccode=".$arr['city'])->value("CName"),
			"Utown"				=>   db("town")->where("Tcode=".$arr['town'])->value("TName")
		);
		return $arr;
	}
	private function updateUser ($arr) {
		return db("user")->where("uid=".$arr['uid'])->insert($arr['data']);
	}
	public function updateInfo ($arr) {
		$arr = $this->cancelArr($arr);
		return $this->updateUser($arr) ||
					 db("registers_info")->where("uid=".$arr['uid'])->insert($arr); 
	}
}



