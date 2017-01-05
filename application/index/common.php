<?php

function handleZJ($zhang,$jie,&$sublesson,$i){
	$index = 0;
	foreach ($zhang as $key => $value) {//搜索的数据必须确保有序，才能执行此算法
            $sublesson[$i]['Lid']=$value['Lid'];
            $sublesson[$i]['Ltitle']=$value['Ltitle'];
            for(;$index<count($jie);$index++){
                if($jie[$index]['Lpid']!=$value['Lid']){
                    break;
                }
                $sublesson[$i]['subcourse'][]=array('Lid'=>$jie[$index]['Lid'],'Ltitle'=>$jie[$index]['Ltitle'],'Ltime'=>$jie[$index]['Ltime']);
            }
            $i++;
        }
}

//截取字符串
function getSubstr($str,$len){
    if(!is_string($str)||$len<0){
        return '';
    }else{
        preg_match_all('/./us', $str, $match);
        $tLen=count($match[0]);
        if($tLen>$len){
            return mb_substr($str,0,$len-1,'utf-8').'...';
        }
        return $str;
    }
}