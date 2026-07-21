<?php
/*
 * 此文件自20081217只用来清理memcache，已经修改为直接修改其它服务器的memcache
 */

require_once('../config/config.game.php');

$httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
if(!preg_match('/^[A-Za-z0-9.-]{1,255}(:[0-9]{1,5})?$/', $httpHost)) $httpHost = '';
$requestCode = (isset($_GET['code']) && !is_array($_GET['code'])) ? $_GET['code'] : '';
$isAnounceAdmin = kdjlCurrentUserIsAdmin();
$code=md5("THiKJ*o)PP:)(J0jlk;l*S&SpoS".$httpHost.date("Ymd"));

if($requestCode!=$code)
{
	die();
}

function anounce_mem_key($key)
{
	$key = strval($key);
	if($key === '' || strlen($key) > 128 || preg_match('/[\x00-\x20\x7f]/', $key))
	{
		return false;
	}
	return $key;
}

if(isset($_GET['clearall'])&&$isAnounceAdmin)
{
	$_pm['mem']->del('chatMsgList');
	echo 'cleared chat message<hr>';
}
if(isset($_GET['clearkey']) && !is_array($_GET['clearkey']) && $isAnounceAdmin)
{
	$memKey = anounce_mem_key($_GET['clearkey']);
	if($memKey === false) die('bad key');
	$_pm['mem']->del($memKey);
	echo 'cleared key<hr>';
}
if(isset($_GET['kickuser']) && !is_array($_GET['kickuser']) && $isAnounceAdmin)
{
	$kickUserId = intval($_GET['kickuser']);
	$key = $kickUserId."chat";
	$_pm['mem']->del($key);
	echo 'kicked user<hr>';
}
if(isset($_GET['setkey']) && !is_array($_GET['setkey']) && $isAnounceAdmin)
{
	$memKey = anounce_mem_key($_GET['setkey']);
	if($memKey === false) die('bad key');
	$setValue = (isset($_GET['v']) && !is_array($_GET['v'])) ? $_GET['v'] : '';
	$_pm['mem']->set(array('k'=>$memKey,'v'=>$setValue));
	echo 'set '.htmlspecialchars($memKey, ENT_QUOTES, 'UTF-8').' to '.htmlspecialchars($setValue, ENT_QUOTES, 'UTF-8').'.<hr>';
}
if(isset($_GET['showkey']) && !is_array($_GET['showkey']) && $isAnounceAdmin)
{
	$memKey = anounce_mem_key($_GET['showkey']);
	if($memKey === false) die('bad key');
	$timeMem=kdjlSafeMemValue($_pm['mem']->get($memKey), null);
	if(!isset($_GET['json']))
	{
		echo '<b>'.__FILE__.'-->'.__LINE__.'</b><br/><pre>'.htmlspecialchars($memKey, ENT_QUOTES, 'UTF-8').'=';
		echo htmlspecialchars(print_r($timeMem, true), ENT_QUOTES, 'UTF-8');
		echo '</pre>';
	}
	else
	{
		echo json_encode($timeMem);
	}
}
?>
