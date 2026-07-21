<?php
session_start();
require_once('../config/config.game.php');
header('Content-Type:text/html;charset=UTF-8');

function fcmAlertAndClose($message)
{
	$json = json_encode((string)$message);
	if($json === false) $json = '"操作失败！"';
	die('<script language="javascript">alert('.$json.');window.close();</script>');
}

$sessionUsername = isset($_SESSION['username']) ? (string)$_SESSION['username'] : '';
if($sessionUsername === '') die('');

$cardNo = (isset($_REQUEST['card_no']) && !is_array($_REQUEST['card_no'])) ? trim((string)$_REQUEST['card_no']) : '';
if(!preg_match('/^(?:[0-9]{15}|[0-9]{17}[0-9Xx])$/D', $cardNo))
{
	fcmAlertAndClose('操作失败，你输入的身份证号码格式不正确！');
}

$httpHost = isset($_SERVER['HTTP_HOST']) ? strtolower((string)$_SERVER['HTTP_HOST']) : '';
if(!preg_match('/^[a-z0-9.-]{1,255}(:[0-9]{1,5})?$/D', $httpHost))
{
	fcmAlertAndClose('操作失败，当前服务器地址无效！');
}
$hostWithoutPort = preg_replace('/:[0-9]{1,5}$/D', '', $httpHost);
$partnerDomain = '';
foreach(array('webgame.com.cn', 'qq496.cn', 'my4399.com') as $knownDomain)
{
	$suffix = '.'.$knownDomain;
	if($hostWithoutPort === $knownDomain ||
		(strlen($hostWithoutPort) > strlen($suffix) && substr($hostWithoutPort, -strlen($suffix)) === $suffix))
	{
		$partnerDomain = $knownDomain;
		break;
	}
}
$fcmSysPath = in_array($partnerDomain, array('qq496.cn', 'my4399.com'), true) ? '4399/' : '';
if($hostWithoutPort === 'pmtest.webgame.com.cn') $fcmSysPath = '4399/';

$fcmBaseUrl = kdjlConfiguredServiceBaseUrl('KDJL_FCM_BASE_URL');
if($fcmBaseUrl === '')
{
	fcmAlertAndClose('防沉迷认证服务未配置。');
}

$key = '*)(OJI(*77786*(**(8';
$signature = md5($httpHost.$sessionUsername.date('Ymd').$key.$cardNo);
$urlFCMGame = $fcmBaseUrl.'/'.$fcmSysPath.'queryId.php?username='.rawurlencode($sessionUsername).
	'&card_no='.rawurlencode($cardNo).'&host='.rawurlencode($httpHost).'&sn='.$signature;

function curlSN($url)
{
	if(!function_exists('curl_init')) return false;
	$ch = curl_init();
	curl_setopt_array($ch, array(
		CURLOPT_URL => $url,
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => '',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => false,
		CURLOPT_CONNECTTIMEOUT => 2,
		CURLOPT_TIMEOUT => 3,
		CURLOPT_SSL_VERIFYPEER => true,
		CURLOPT_SSL_VERIFYHOST => 2
	));
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}

$rs = curlSN($urlFCMGame);
if($rs !== 'OK')
{
	fcmAlertAndClose('操作失败，你输入的信息不正确！');
}
fcmAlertAndClose('操作成功，请关闭浏览器重新登录，让你输入的信息生效！');
