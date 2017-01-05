<?php

namespace app\index\controller;

use think\Controller;

header("Content-type:text/html;charset=utf-8");

class PayController extends Controller{

	//平台自动发货接口函数
	public function auto_deliver_goods($trade_no,$invoice_no){

		$alipay_config = config('alipay_config');
		/*//支付宝交易号
        $trade_no = $trade_no;
        //必填*/

        //物流公司名称
        $logistics_name = "无物流信息";
        //必填

        // //物流发货单号
        // $invoice_no = $out_trade_no;
        // //物流运输类型
        $transport_type = "POST";
        //三个值可选：POST（平邮）、EXPRESS（快递）、EMS（EMS）


		/************************************************************/

		//构造要请求的参数数组，无需改动
		$parameter = array(
			"service" => "send_goods_confirm_by_platform",
			"partner" => trim($alipay_config['partner']),
			"trade_no"	=> $trade_no,
			"logistics_name"	=> $logistics_name,
			"invoice_no"	=> $invoice_no,
			"transport_type"	=> $transport_type,
			"_input_charset"	=> trim(strtolower($alipay_config['input_charset']))
		);

		//建立请求
		$alipaySubmit = new Util\AlipaySubmit($alipay_config);
		$html_text = $alipaySubmit->buildRequestHttp($parameter);
		return $html_text;
	}

	//交易后的数据库操作
	public function update($out_trade_no,$trade_no,$status){
		$message = db('rechargerecord');
		$user = db('user');
		$data['Ralipay_trade_no'] = $trade_no;
		$data['Rtrade_status'] = $status;
		$ok1 = $message->where("Rout_trade_no=".$out_trade_no)->save($data);
		//交易完成，把购买的积分加到已有积分去
		if($status == 4){
			$have = $message->where("Rout_trade_no=".$out_trade_no)->find();
			$uid = $have['Ruid'];
			$beans1 = $have['Rbeans'];
			$beans2 = $user->where("Uid=".$uid)->value("Ubeans");
			$all_beans = $beans1 + $beans2;
			$ok2 = $user->where("Uid=".$uid)->setField("Ubeans",$all_beans);
		}
	}


	public function alipayapi(){
		header("Content-type:text/html;charset=utf-8");
		//先生成购买记录
		$message = db("rechargerecord");
		$uid = input('get.uid');

		//付款金额
        $price = input('get.WIDprice');
        $name = input('get.name');
        //必填

        //商户订单号
        $out_trade_no = date("YmdHis",time()).rand(100000,999999).$uid;//$_POST['WIDout_trade_no'];
        //商户网站订单系统中唯一订单号，必填

        //把生成的购买信息存入数据库
        //购买的积分数
        $beans = $price;
        $time = date("Y-m-d H:i:s",time());
        $data['Ruid'] = $uid;
        $data['Rcash'] = $price;
        $data['Rbeans'] = $beans;
        $data['Rtime'] = $time;
        $data['Rout_trade_no'] = $out_trade_no;

        $ok = $message->insert($data);
        if($ok){
        	$alipay_config = config('alipay_config');
			//支付类型
	        $payment_type = "1";
	        //必填，不能修改
	        //服务器异步通知页面路径
	        $notify_url = $alipay_config['notify_url'];
	        //需http://格式的完整路径，不能加?id=123这类自定义参数

	        //页面跳转同步通知页面路径
	        $return_url = $alipay_config['return_url'];
	        //需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/

	        //订单名称
	        $subject = $alipay_config['subject'];//$_POST['WIDsubject'];
	        //必填

	        //商品数量
	        $quantity = "1";
	        //必填，建议默认为1，不改变值，把一次交易看成是一次下订单而非购买一件商品
	        //物流费用
	        $logistics_fee = "0.00";
	        //必填，即运费
	        //物流类型
	        $logistics_type = "EXPRESS";
	        //必填，三个值可选：EXPRESS（快递）、POST（平邮）、EMS（EMS）
	        //物流支付方式
	        $logistics_payment = "SELLER_PAY";
	        //必填，两个值可选：SELLER_PAY（卖家承担运费）、BUYER_PAY（买家承担运费）
	        //订单描述
	        $body = $alipay_config['body'];
	        //商品展示地址
	        $show_url = "";//$_POST['WIDshow_url'];
	        //需以http://开头的完整路径，如：http://www.商户网站.com/myorder.html

	        //收货人姓名
	        $receive_name = $name;//$_GET['name'];
	        //如：张三

	        //收货人地址
	        $receive_address = "";//$_POST['WIDreceive_address'];
	        //如：XX省XXX市XXX区XXX路XXX小区XXX栋XXX单元XXX号

	        //收货人邮编
	        $receive_zip = "";//$_POST['WIDreceive_zip'];
	        //如：123456

	        //收货人电话号码
	        $receive_phone = "";//$_POST['WIDreceive_phone'];
	        //如：0571-88158090

	        //收货人手机号码
	        $receive_mobile = "";//$_POST['WIDreceive_mobile'];
	        //如：13312341234

	        //把生成的购买信息存入数据库



	/************************************************************/

			//构造要请求的参数数组，无需改动
			$parameter = array(
				"service" => "create_partner_trade_by_buyer",
				"partner" => trim($alipay_config['partner']),
				"seller_email" => trim($alipay_config['seller_email']),
				"payment_type"	=> $payment_type,
				"notify_url"	=> $notify_url,
				"return_url"	=> $return_url,
				"out_trade_no"	=> $out_trade_no,
				"subject"	=> $subject,
				"price"	=> $price,
				"quantity"	=> $quantity,
				"logistics_fee"	=> $logistics_fee,
				"logistics_type"	=> $logistics_type,
				"logistics_payment"	=> $logistics_payment,
				"body"	=> $body,
				"show_url"	=> $show_url,
				"receive_name"	=> $receive_name,
				"receive_address"	=> $receive_address,
				"receive_zip"	=> $receive_zip,
				"receive_phone"	=> $receive_phone,
				"receive_mobile"	=> $receive_mobile,
				"_input_charset"	=> trim(strtolower($alipay_config['input_charset']))
			);

			//建立请求
			$alipaySubmit = new Util\AlipaySubmit($alipay_config);
			$html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
			echo $html_text;
        }else{
        	echo "<script>
        	alert('订单生成失败，请重新进行购买。');
        	history.back();
        		  </script>";
        }

		

	}
	public function notify_url(){
		header("Content-type:text/html;charset=utf-8");
		$alipay_config = config('alipay_config');
		//计算得出通知验证结果
		$alipayNotify = new Util\AlipayNotify($alipay_config);
		$verify_result = $alipayNotify->verifyNotify();

		if($verify_result) {
			//验证成功
			/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			//请在这里加上商户的业务逻辑程序代

			
			//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
			
		    //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
			
			//商户订单号

			$out_trade_no = $_POST['out_trade_no'];

			//支付宝交易号

			$trade_no = $_POST['trade_no'];

			//交易状态
			$trade_status = $_POST['trade_status'];


			if($_POST['trade_status'] == 'WAIT_BUYER_PAY') {
			//该判断表示买家已在支付宝交易管理中产生了交易记录，但没有付款
			
				//判断该笔订单是否在商户网站中已经做过处理
					//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
					//如果有做过处理，不执行商户的业务程序
				$status = 1;
				$this->update($out_trade_no,$trade_no,$status);
		        echo "success";		//请不要修改或删除

		        //调试用，写文本函数记录程序运行情况是否正常
		        //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
		    }else if($_POST['trade_status'] == 'WAIT_SELLER_SEND_GOODS') {
			//该判断表示买家已在支付宝交易管理中产生了交易记录且付款成功，但卖家没有发货
			
				//判断该笔订单是否在商户网站中已经做过处理
					//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
					//如果有做过处理，不执行商户的业务程序
				$status = 2;
				$this->update($out_trade_no,$trade_no,$status);
		        echo "success";		//请不要修改或删除

		        //调试用，写文本函数记录程序运行情况是否正常
		        //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
		    }else if($_POST['trade_status'] == 'WAIT_BUYER_CONFIRM_GOODS') {
			//该判断表示卖家已经发了货，但买家还没有做确认收货的操作
			
				//判断该笔订单是否在商户网站中已经做过处理
					//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
					//如果有做过处理，不执行商户的业务程序
				$status = 3;
				$this->update($out_trade_no,$trade_no,$status);
		        echo "success";		//请不要修改或删除

		        //调试用，写文本函数记录程序运行情况是否正常
		        //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
		    }else if($_POST['trade_status'] == 'TRADE_FINISHED') {
			//该判断表示买家已经确认收货，这笔交易完成
			
				//判断该笔订单是否在商户网站中已经做过处理
					//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
					//如果有做过处理，不执行商户的业务程序
				$status = 4;
				$this->update($out_trade_no,$trade_no,$status);
		        echo "success";		//请不要修改或删除

		        //调试用，写文本函数记录程序运行情况是否正常
		        //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
		    }else {
				//（交易中途关闭（已结束，未成功完成））
				$status = 5;
				$this->update($out_trade_no,$trade_no,$status);
		        echo "success";

		        //调试用，写文本函数记录程序运行情况是否正常
		        //logResult ("这里写入想要调试的代码变量值，或其他运行的结果记录");
		    }

			//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
			
			/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		}else {
		    //验证失败
		    echo "fail";

		    //调试用，写文本函数记录程序运行情况是否正常
		    //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
		}
	}
	public function return_url(){
		header("Content-type:text/html;charset=utf-8");
		$alipay_config = config('alipay_config');
		//计算得出通知验证结果
		$alipayNotify = new \Org\Util\AlipayNotify($alipay_config);
		$verify_result = $alipayNotify->verifyReturn();
		if($verify_result) {//验证成功
			/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			//请在这里加上商户的业务逻辑程序代码
			
			//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
		    //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
		   
			//商户订单号(随机生成)
			$out_trade_no = $_GET['out_trade_no'];

			//支付宝交易号
			$trade_no = $_GET['trade_no'];

			//交易状态
			$trade_status = $_GET['trade_status'];


		    if($_GET['trade_status'] == 'WAIT_SELLER_SEND_GOODS') {

		    	//自动发货
				$html_text = $this->auto_deliver_goods($trade_no,$out_trade_no);
				//访问特定节点元素和属性
				$xml = simplexml_load_string($html_text);
				if($xml->is_success == "T"){
					$this->success('充值积分成功!在你点击确认收货后才能正常使用。','http://www.vivisp.com/index.php/User/rechargeHistory',5);
				}else{
					if($xml->error == "TRADE_NOT_EXIST"){
						$this->error('该笔交易不存在，请确定你已经购买！','http://www.vivisp.com/index.php/User/recharge',5);
					}elseif($xml->error == "SYSTEM_ERROR"){
						$this->error('支付宝系统错误！请及时联系卖家或网页管理员。','http://www.vivisp.com/index.php/User/recharge',5);
					}else{
						$this->error('出现未知错误！请及时联系卖家或网页管理员。','http://www.vivisp.com/index.php/User/recharge',5);
					}
				}
				
		    }else{
		      //echo "trade_status=".$_GET['trade_status'];
		    	$this->error('充值积分失败，请重试！','http://www.vivisp.com/index.php/User/recharge',5);
		    }
			//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
			
			/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		}else {
		    //验证失败
		    //如要调试，请看alipay_notify.php页面的verifyReturn函数
		    $this->error('充值积分失败，请重试！','http://www.vivisp.com/index.php/User/recharge',5);
		}
	}
}
?>