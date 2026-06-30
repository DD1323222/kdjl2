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
set_time_limit(25);
if(isset($_GET['cancel'])&&$_GET['cancel']==1)die('
		<script language="javascript">
		window.opener.location = "pay_Mod.php";
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

$db = &$_pm['mysql'];

if($notice){//支付成功后51post数据过来
	$price = intval($notice['order_price'])/10;	
	
	$return_str = "0";
	
	//$_pm['mem']->set(array('k'=>'bankpaytets_DEBUG','v'=>unserialize($_pm['mem']->get('bankpaytets_DEBUG'))."<hr><font color=#ff0000>notice=".print_r($notice,1)."</font><br>"));	
	
	
	/*
	if($notice['order_id']=='4000024484238420031')
		{
			$_pm['mem']->set(array('k'=>'bankpaytets_DEBUG2','v'=>unserialize($_pm['mem']->get('bankpaytets_DEBUG2'))."<hr><font color=#ff0000>notice=".print_r($notice,1)."</font><br>"));	
		}
	*/
	
	if(isset($notice['order_id']))//51币支付 else 银行支付
	{
		
		/*
		if($notice['order_id']=='4000024484238420031')
		{
			$_pm['mem']->set(array('k'=>'bankpaytets_DEBUG2','v'=>unserialize($_pm['mem']->get('bankpaytets_DEBUG2'))."<hr>".__LINE__."<font color=#ff0000>notice=".print_r($notice,1)."</font><br>"));	
		}
		*/

		$notice['sn_app'] = $notice['order_id'];
		$notice['time_pay'] = $notice['time'];
		$notice['sn_platform'] = '51CoinPay';
		$params = array('order_code'=>1, 'order_id'=>$notice['order_id'], 'order_price'=>$notice['order_price'], 'order_num'=>$notice['order_num']);
		$OpenApp_51 = new OpenApp_51($appapikey, $appsecret);
		$OpenApp_51->api_client->set_encoding("GBK");
		$return_str = $OpenApp_51->api_client->create_post_string('51_pay', $params);
		if($notice['app_key']!=$appapikey){
			
			die("ERR_app_key");	
		}
	}
	$orderIdSql = $db->escape($notice['sn_app']);
	$db->query('START TRANSACTION');
	$orderInfo = $db->getOneRecord("SELECT Id,getyb,user_id,paytime FROM yb WHERE orderid = '{$orderIdSql}' ORDER BY Id DESC LIMIT 1 FOR UPDATE");
	if(!$orderInfo){
		$db->query('ROLLBACK');
		die("Order not found.");
	}
	if(intval($orderInfo['paytime']) > 0){
		$db->query('ROLLBACK');
		die($return_str);
	}
	if(intval($orderInfo['getyb']) < 1 || intval($orderInfo['user_id']) < 1){
		$db->query('ROLLBACK');
		die('ERR_order');
	}
	if(isset($notice['order_num']) && intval($notice['order_num']) != intval($orderInfo['getyb'])){
		$db->query('ROLLBACK');
		die('ERR_order_num');
	}
	$payTime = intval($notice['time_pay']);
	if($payTime < 1) $payTime = time();
	$platformSql = $db->escape($notice['sn_platform']);
	$db->query("update yb set paytime={$payTime},sn_platform='{$platformSql}' where Id=".intval($orderInfo['Id'])." and paytime=0");
	if(mysql_affected_rows($db->getConn()) != 1){
		$db->query('ROLLBACK');
		die($return_str);
	}
	$db->query("update player set yb=yb+".intval($orderInfo['getyb'])." where id=".intval($orderInfo['user_id']));
	if(mysql_affected_rows($db->getConn()) != 1 || !$db->query('COMMIT')){
		$db->query('ROLLBACK');
		die("Q_ERROR_".intval($orderInfo['user_id'])."_".$price);
	}
	$_pm['mem']->set(array('k'=>'pany51_'.$notice['sn_app'],'v'=>$notice));
	die($return_str);
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

require_once(dirname(__FILE__).'/51_check_pay.php');

if(!isset($_SESSION['buyyb_info']))
{
	die("您还没有够买元宝！");
}else if(!isset($_SESSION['buyyb_info'][$_51orderid])){
	die($msg = "定单：{$_51orderid}不存在！");
}
else
{	
	if(!is_array($webgameCanceledOrderId)) $webgameCanceledOrderId=array();
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

	if($Flag === true&&!in_array($_51orderid,$webgameCanceledOrderId)){
		$price = $_SESSION['buyyb_info'][$_51orderid][0];
		$msg = '支付成功';		
		/*
		$db->query("insert into yblog(title,nickname,yb,buytime,pname,nums,orderid)
							values('购买口袋精灵元宝".$_SESSION['buyyb_info'][$_51orderid][0]."个.','{$_SESSION['username']}','{$price}',unix_timestamp(),'元宝',".$_51orderid.",'{$_51orderid}')
						  ");	
		*/	
		unset($_SESSION['buyyb_info'][$_51orderid]);		
		die('
		<script language="javascript">
		alert("'.$msg.'");
		window.opener.location = "Shopsm_Mod.php";
		window.close();
		</script>
		');
	}
	else if(in_array($_51orderid,$webgameCanceledOrderId))
	{
		die('
		<script language="javascript">
		alert("服务器繁忙，支付失败，请5分钟后重试。");
		window.opener.location = "pay_Mod.php";
		window.close();
		</script>
		');
	}else{
		die('
		<script language="javascript">
		alert("'.$msg.'\n建议您1分钟后刷新此窗口！\n也可以在神秘商店中观察元宝余额。");
		//window.opener.location = "pay_Mod.php";
		//window.close();
		</script>
		');
	}
}
?>
