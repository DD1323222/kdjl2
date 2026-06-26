<?php 
/*
 * 此文件自20081217只用来清理memcache，已经修改为直接修改其它服务器的memcache
 */

require_once('../config/config.game.php');

$code=md5("THiKJ*o)PP:)(J0jlk;l*S&SpoS".$_SERVER['HTTP_HOST'].date("Ymd"));

if($_GET['code']!=$code)
{
	die();
}

if(isset($_GET['clearall'])&&$_SESSION['username']=="leinchu")
{	
	$_pm['mem']->del('chatMsgList');
	echo 'cleared chat message<hr>';
}
if(isset($_GET['clearkey'])&&$_SESSION['username']=="leinchu")
{	
	$_pm['mem']->del($_GET['clearkey']);
	echo 'cleared key<hr>';
}
if(isset($_GET['kickuser']))
{
	$key = intval($_GET['kickuser'])."chat";
	$_pm['mem']->del($key);
	echo 'kicked user<hr>';
}
if(isset($_GET['setkey'])&&$_SESSION['username']=="leinchu")
{	
	$_pm['mem']->set(array('k'=>$_GET['setkey'],'v'=>$_GET['v']));
	echo 'set '.$_GET['setkey'].' to '.$_GET['v'].'.<hr>';
}
if(isset($_GET['showkey']))
{	
	$timeMem=unserialize($_pm['mem']->get($_GET['showkey']));
	if(!isset($_GET['json']))
	{
		echo '<b>'.__FILE__.'-->'.__LINE__.'</b><br/><pre>'.$_GET['showkey'].'=';
		var_dump($timeMem);
		echo '</pre>';
	}
	else
	{
		echo json_encode($timeMem);
	}
}
?>