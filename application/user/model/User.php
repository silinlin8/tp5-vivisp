<?php
namespace app\user\model;

use think\Model;

class User extends Model
{

  	protected $auto = ['Upwd','Utoken','UupTime','Ustatus'=>1,'Utype'=>0,'Ubeans'=>20];

  	protected function setUpwdAttr($value){
  		return md5($value);
  	}

  	protected function setUtokenAttr($value){
  		return md5($value);
  	}

  	protected function setUuptimeAttr($value){
  		return date("Y-m-d H:i:s");
  	}


}