<?php

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
/**
 * 邮件发送函数
 */
function sendMail($to,$subject,$content) {
    vendor('PHPMailer.class#phpmailer');

    $mail = new PHPMailer(true); //New instance, with exceptions enabled

    $mail->IsSMTP();                           // tell the class to use SMTP
    $mail->SMTPAuth   = true;                  // enable SMTP authentication
    $mail->Port       = 25;                    // set the SMTP server port
    $mail->Host       = config('MAIL_HOST');//"smtp.sina.com"; // SMTP server
    $mail->Username   = config('MAIL_USERNAME');//"youlinyijia@sina.com";     // SMTP服务器用户名
    $mail->Password   = config('MAIL_PASSWORD');//"ncepulsgogroup";            // SMTP服务器密码
    //$mail->IsSendmail();  // tell the class to use Sendmail
    $mail->AddReplyTo($to,"vivi视频");
    $mail->From       = config('MAIL_USERNAME');
    $mail->FromName   = "vivi视频";
    $mail->Subject    = $subject;
    $mail->AltBody    = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test
    $mail->CharSet    = config('MAIL_CHARSET');
    $mail->WordWrap   = 80; // set word wrap
    $mail->AddAddress($to);
    $mail->MsgHTML($content);
    $mail->IsHTML(true); // send as HTML
    if($mail->Send()){
        return true;
    }else{
        return false;
    }
}
function handleScore($arr,$questions){
        $result = 0;
        foreach ($questions as $key => $value) {
            if (!empty($arr[$value["id"]])) {
                $temp = $arr[$value["id"]];
                $temp = is_array($temp)? implode(',',$temp):$temp;
                if ($temp === $value['answer']) {
                    $result += $value["grade"];
                }
            }
        }
        return $result;
    }
