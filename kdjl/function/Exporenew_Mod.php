<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.07.13
*@Usage: Map
*@Note: none
*/
require_once('../config/config.game.php');
unset($_SESSION['catch_gw_info']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');
if(!isset($_SESSION['fight'.$uid]) || !is_array($_SESSION['fight'.$uid])) $_SESSION['fight'.$uid] = array();
$_SESSION['fight'.$uid]['gid'] = 0;
//用于抓进城补血，继续打怪外挂！如果是使用目前的外挂（2009-01-30）则不会访问这个地方，$_SESSION['GoToCity']为一个时间，进入战斗时就可以判断这个玩家使用外挂！
$_SESSION['GoToCity'] = NULL;
unset($_SESSION['GoToCity']);
secStart($_pm['mem']);

$user	 = $_pm['user']->getUserById($uid);
if(!is_array($user)) die('');

//副本地图
$sql = "SELECT id FROM map WHERE gpclist = '0'";
$fuben = $_pm['mysql'] -> getRecords($sql);
$fbid = array();
$src = array();
$des = array();
$mapsrc = array();
$mapdes = array();
$mapret = '';
//@Load template.
$requestN = (isset($_GET['n']) && !is_array($_GET['n'])) ? intval($_GET['n']) : 0;
if($requestN == 2){
	$tn = $_game['template'] . 'tpl_mapnew1.html';
}else{
	$tn = $_game['template'] . 'tpl_mapnew.html';
}
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);

	if($tpl !== false && is_array($fuben))
	{
		foreach($fuben as $ks => $vs)
		{
			if(!is_array($vs) || !isset($vs['id'])) continue;
			$fbid[] = $vs['id'];
		}
	}
	foreach($fbid as $k1 => $v1)
	{
		$v1 = intval($v1);
		if($v1 < 1) continue;
		$src[$v1] = "#{$v1}#";
		$des[$v1] = $v1;
	}

	$map = isset($user['openmap']) ? $user['openmap'] : '';

	// Fix maybe error.
	if ($map == '') $map = 1;

	$rawMaparr = explode(',', $map);
	$maparr = array();
	foreach($rawMaparr as $v)
	{
		$v = intval(trim($v));
		if($v < 1 || in_array((string)$v, $maparr, true)) continue;
		$maparr[] = (string)$v;
		$src[$v] = "#{$v}#";
		$mapsrc[$v] = "#map{$v}#";
		$des[$v] = $v;
		$mapdes[$v] = $v;
	}
	for($x=100;$x<=200;$x++)
	{
		if(isset($src[$x])) continue;
		else{
			$src[$x] = "#{$x}#";
			$des[$x] = 0;
			$mapsrc[$x] = "#map{$x}#";
			$mapdes[$x] = "03";
		}
	}
	if($tpl !== false)
	{
		$mapret = str_replace($src, $des, $tpl);
		$mapret = str_replace($mapsrc,$mapdes,$mapret);
	}
}

// gzip echo. if maybe.
ob_start('ob_gzip');
echo $mapret;
ob_end_flush();
?>
