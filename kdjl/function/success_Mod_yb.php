<?php
/*
 这个页面有四个功能：
     1. 用户用51币购买元宝后51通知我们，我们的处理
     2. 用户通过银行购买元宝后51通知我们，我们的处理
     3. 用户用51币购买元宝后返回看到结果
     4. 用户通过银行购买元宝后点“返回小应用”看到的页面
	 
 也可以说是说是三个功能：3，4处理是一样的
     5. 用户取消返回
	  */

if(isset($_GET['cancel'])&&$_GET['cancel']==1)die('
		<script language="javascript">
		window.opener.location = "pay_Mod_yb.php";
		window.close();
		</script>
		');


$Flag = false;
function decode($params, $secret) {
	//global $dbg;
	$prefix = '51_sig_'; $prefix_len = strlen($prefix);

	$ret = array();
	foreach ($params as $key => $val) {
		if (strncmp($key, $prefix, $prefix_len) === 0) {
			$ret[substr($key, $prefix_len)] = $val;
		}
	}
	if (empty($ret)) return false;

	
	$str = '';
	ksort($ret);
	foreach ($ret as $k=>$v) $str .= "$k=$v";
	$str .= $secret;
	//$dbg = '<br/>$str='.$str.'<br/>md5='.md5($str)."<br/>params['51_sig']=".$params['51_sig'];
	if ($params['51_sig'] != md5($str)) {
		return false;
	} else {
		return $ret;
	}
}

/*51 支付通知部分，此时本程序为order_check_url 开始 */
//银行或51币支付成功后，51通知我们，程序在这里处理
define("FIVEONE_OP_API_DOMAIN", "api");

define("POST_TIMEOUT",300);
define("GET_TIMEOUT",300);
define("COOKIE_TIMEOUT",36000);
define("CONNECT_TIMEOUT",5);
define("READ_TIMEOUT",10);
require_once dirname(dirname(__FILE__)).'/sdk/openapp_51.php';
$appapikey = '39d4d8d96e3d64d98f4e5eebc9ab890a';
$appsecret = '4220a08009eff4115151728a885a44e9';


$notice = decode($_POST, $appsecret);
require_once('../config/config.game.php');

$_pm['mem']->set(array('k'=>'bankpaytets','v'=>unserialize($_pm['mem']->get('bankpaytets'))."<hr>".date("Y-m-d H:i:s")."<br>Line=".__LINE__."<br>_POST=".print_r($_POST,1)."<br>_GET=".print_r($_GET,1)));
$db = &$_pm['mysql'];

if($notice){//支付成功后51post数据过来
	$_pm['mem']->set(array('k'=>'bankpaytets','v'=>unserialize($_pm['mem']->get('bankpaytets'))."<hr>"."<br>Line=".__LINE__.print_r($notice,1)));
	$price = intval($notice['order_price'])/10;	
	
	$return_str = "0";
	if(isset($notice['order_id']))//51币支付 else 银行支付
	{
		$notice['sn_app'] = $notice['order_id'];
		$notice['time_pay'] = $notice['time'];
		$notice['sn_platform'] = $notice['session_key']."_".$notice['sandbox'];
		$params = array('order_code'=>1, 'order_id'=>$notice['order_id'], 'order_price'=>$notice['order_price'], 'order_num'=>$notice['order_num']);
		$OpenApp_51 = new OpenApp_51($appapikey, $appsecret);
		$OpenApp_51->api_client->set_encoding("GBK");
		$return_str = $OpenApp_51->api_client->create_post_string('51_pay', $params);
		if($notice['app_key']!=$appapikey){
			$_pm['mem']->set(array('k'=>'bankpaytets','v'=>unserialize($_pm['mem']->get('bankpaytets'))."<hr><font color=#ff0000>Line=".__LINE__."</font><br>"));
			die("ERR_app_key");	
		}
	}
	$orderInfo = $_pm['mysql']->getOneRecord("SELECT Id,getyb,user_id FROM yb WHERE orderid = '".$notice['sn_app']."'");
	if($orderInfo){		
		$_pm['mem']->set(array('k'=>'bankpaytets','v'=>unserialize($_pm['mem']->get('bankpaytets'))."<hr><font color=#ff0000>Line=".__LINE__."</font><br>"));		
		
		$_pm['mysql']->query("update yb set paytime='".$notice['time_pay']."',sn_platform='".$notice['sn_platform']."' where orderid='".$notice['sn_app']."'");		
		$_pm['mem']->set(array('k'=>'pany51_'.$notice['sn_app'],'v'=>$notice));		
		$_pm['mysql']->query("update player set yb=yb+".intval($orderInfo['getyb'])." where id=".$orderInfo['user_id']);		
		if($e=mysql_error()){	
			die("Q_ERROR_".($orderInfo['user_id'])."_".$price);
		}
		die($return_str);
	}else{
		$_pm['mem']->set(array('k'=>'bankpaytets','v'=>unserialize($_pm['mem']->get('bankpaytets'))."<hr><font color=#ff0000>Line=".__LINE__."</font><br>"));
		die("Order not found.");
	}
}
/*51 支付通知部分，此时本程序为order_check_url 结束 */



/* 以下是使用51币和银行支付结束用户点返回小程序(用户看到结果)的处理 */
session_start();
if(!isset($OpenApp_51)) $OpenApp_51 = new OpenApp_51($appapikey, $appsecret);
$user = $OpenApp_51->require_login();
//include_once("../sdk/appinclude.php");

$m	= $_pm['mem'];
$u	= $_pm['user'];
secStart($m);
$user	= $u->getUserById($_SESSION['id']);
$bags    = $u->getUserBagById($_SESSION['id']);
$props = unserialize($m->get(MEM_PROPS_KEY));

$_51orderid = isset($_GET['order_id'])?$_GET['order_id']:(isset($_GET['51_sig_order_id'])?$_GET['51_sig_order_id']:-1);

if($user===FALSE)
{
	die("信息错误！");
}
	

if(!isset($_SESSION['buyyb_info']))
{
	die("您还没有够买元宝！");
}else if(!isset($_SESSION['buyyb_info'][$_51orderid])){
	die($msg = "定单：{$_51orderid}不存在！");
}
else
{	
	$Flag = false;	
	$notice = unserialize($_pm['mem']->get('pany51_'.$_51orderid));
	if($notice&&$notice['order_price']>0){//银行支付
		$_pm['mem']->del('pany51_'.$_51orderid);		
		$Flag = true;
	}
	else 
	{		
		$msg = '支付失败，您没有正确支付。\n如果您支付了，可能由于网络延迟，支付还需要等待几分钟才能完成，请密切关注您的元宝数量。';
	}
	
	if($Flag === true){
		$price = $_SESSION['buyyb_info'][$_51orderid][0];
		$msg = '支付成功';
		$db->query("insert into yblog(title,nickname,yb,buytime,pname,nums,orderid)
					values('购买口袋精灵元宝".$_SESSION['buyyb_info'][$_51orderid][0]."个.','{$_SESSION['username']}','{$price}',unix_timestamp(),'元宝',".$_51orderid.",'{$_51orderid}')
				  ");// save bug yb log.			
		unset($_SESSION['buyyb_info'][$_51orderid]);		
		die('
		<script language="javascript">
		alert("'.$msg.'");
		window.opener.location = "Shopsm_Mod_yb.php";
		window.close();
		</script>
		');
	}else{
		die('
		<script language="javascript">
		alert("'.$msg.'");
		window.opener.location = "pay_Mod_yb.php";
		window.close();
		</script>
		');
	}
}
?>