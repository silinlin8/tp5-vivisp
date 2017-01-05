<?php

namespace app\user\model;

use think\Model;

class Note extends Model
{
	public function user(){
		return $this->hasMany('User','Uid','Nuid')->field('Uimg');
	}

	public function lesson(){
		return $this->hasMany('Lesson','Lid','Nlid')->field('Ltitle,Lpid');
	}
}