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
$_SESSION['fight'.$_SESSION['id']]['gid'] = 0;
//用于抓进城补血，继续打怪外挂！如果是使用目前的外挂（2009-01-30）则不会访问这个地方，$_SESSION['GoToCity']为一个时间，进入战斗时就可以判断这个玩家使用外挂！
$_SESSION['GoToCity'] = NULL;
unset($_SESSION['GoToCity']);
if($_REQUEST['from'] == 1)
{
	
}
else
{
	secStart($_pm['mem']);
}
$user	 = $_pm['user']->getUserById($_SESSION['id']);

//副本地图
$sql = "SELECT * FROM map WHERE gpclist = '0'";
$fuben = $_pm['mysql'] -> getRecords($sql);
//@Load template.
$tn = $_game['template'] . 'tpl_map.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);
	
	if(is_array($fuben))
	{
		foreach($fuben as $ks => $vs)
		{
			$fbid[] = $vs['id'];
		}
	}
	foreach($fbid as $k1 => $v1)
	{
		$src[$v1] = "#{$v1}#";
		$des[$v1] = $v1;
	}
	
	$map = $user['openmap'];
	

	// Fix maybe error.
	if ($map == '') $map = 1;

	$maparr = split(',', $map);
	foreach($maparr as $k => $v)
	{
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
	$mapret = str_replace($src, $des, $tpl);
	$mapret = str_replace($mapsrc, $mapdes, $mapret);
}

// gzip echo. if maybe.
ob_start('ob_gzip');

if($_REQUEST['from'] == 1)
{
	$sql = "SELECT id,name,level FROM map WHERE gpclist != '0'";
	$baseMap = $_pm['mysql'] -> getRecords($sql);
	foreach($baseMap as $key => $info)
	{
		$baseMap[$key]['name'] = iconv("gbk","utf-8",$info['name']);
		$arr = explode(",",$info['level']);
		$baseMap[$key]['level'] = iconv("gbk","utf-8","怪物等级：").$arr[0].'-'.$arr[1];
	}
	$backData = array("myMap"=>$maparr,"mapData"=>$baseMap,"inmap"=>$user['inmap']);
	echo "OK".json_encode($backData);
}
else
{
	echo $mapret;
}
ob_end_flush();
?>
