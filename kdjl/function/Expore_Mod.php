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
$from = (isset($_REQUEST['from']) && !is_array($_REQUEST['from'])) ? intval($_REQUEST['from']) : 0;
if($from == 1)
{

}
else
{
	secStart($_pm['mem']);
}
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
$maparr = array();
$mapret = '';
//@Load template.
$tn = $_game['template'] . 'tpl_map.html';
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
	for($x=1;$x<=20;$x++)
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
		$mapret = str_replace($mapsrc, $mapdes, $mapret);
	}
}

// gzip echo. if maybe.
ob_start('ob_gzip');

if($from == 1)
{
	$sql = "SELECT id,name,level FROM map WHERE gpclist != '0'";
	$baseMap = $_pm['mysql'] -> getRecords($sql);
	if(!is_array($baseMap)) $baseMap = array();
	foreach($baseMap as $key => $info)
	{
		if(!is_array($info)) continue;
		$name = isset($info['name']) ? $info['name'] : '';
		$level = isset($info['level']) ? $info['level'] : '0,0';
		$baseMap[$key]['name'] = $name;
		$arr = explode(",",$level);
		if(!isset($arr[0])) $arr[0] = 0;
		if(!isset($arr[1])) $arr[1] = 0;
		$baseMap[$key]['level'] = "怪物等级：".$arr[0].'-'.$arr[1];
	}
	$backData = array("myMap"=>$maparr,"mapData"=>$baseMap,"inmap"=>(isset($user['inmap']) ? $user['inmap'] : 0));
	echo "OK".json_encode($backData);
}
else
{
	echo $mapret;
}
ob_end_flush();
?>
