<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.12.03
*@Usage: Expore privew. --> 进入地图限制
*@Note: 
*/
require_once('../config/config.game.php');
if($_REQUEST['from'] != 1)
{
	secStart($_pm['mem']);
}
$m = $_pm['mem'];
if( $_SESSION['first_in'] == 2 || $_SESSION['first_in'] == 3 )
{
	$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");	
}
$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");	
$user		= $_pm['user']->getUserById($_SESSION['id']);
$userBag    = $_pm['user']->getUserBagById($_SESSION['id']);
$map = unserialize($m->get(MEM_MAP_KEY));
del_bag_expire();
$type = intval($_REQUEST['type']);
if($type == "1")//普通地图
{
	$id = intval($_REQUEST['n']);
	$usermap = explode(",",$user['mapinfo']);
	foreach($usermap as $v)
	{
		$mapinfo = explode(":",$v);
		$time = time();
		if($mapinfo[0] == $id && $mapinfo[1] > $time)
		{
			die("10");//地图已经打开
		}
	}
	foreach($map as $v)
	{
		if($v['id'] == $id)
		{
			if($_REQUEST['from'] == 1)
			{
				$_pm['mysql'] -> query(" UPDATE player SET bot_map_id = {$id} WHERE id = '".$_SESSION['id']."'");	
			}
			die("12");//不需要道具的
		}
	}
		echo $strs;
}
else if($type == 2)//确定
{
	$err = 11;
	$id = intval($_REQUEST['n']);
	foreach($map as $v)
	{
		if($v['id'] == $id)
		{
			$maparr = $v;
			break;
		}
	}
	$arr = explode(":",$maparr['needs']);
	$needs = explode("|",$arr[1]);
	if(empty($needs[1]))
	{
		$needs[1] = 1 * 12 * 30 * 3600;
	}
	$time1 = time()+$needs[1];
	$userarr = explode(",",$user['mapinfo']);
	foreach($userarr as $v)
	{
		$narr = explode(":",$v);
		if($narr[0] == $id || $narr[1] <= time())
		{
			continue;
		}
		if(!empty($v))
		{
			$str .= $v.",";
		}
	}
	$str .= $id.":".$time1;
	if($arr[0] == 'needww')//需要威望
	{
		$sql = "UPDATE player SET prestige = prestige - {$needs[0]},mapinfo= '{$str}' where id = {$_SESSION['id']} and prestige >= {$needs[0]}";
		$_pm['mysql'] -> query($sql);
		if($_pm['mysql'] -> getEffectRows() != 1)
		{
			die("3");
		}
	}
	else if($arr[0] == 'needtime' || $arr[0] == 'needitem')
	{
		$sql = "UPDATE userbag SET sums = abs(sums-1) WHERE pid = {$needs[0]} and uid = {$_SESSION['id']} and sums > 0";
		
		$_pm['mysql']->query($sql);
		$effectRow = mysql_affected_rows($_pm['mysql']->getConn());
		
		if($effectRow != 1)
		{
			die("3");
		}
		
		$sql = "UPDATE player SET mapinfo= '{$str}' where id = {$_SESSION['id']}";
		$_pm['mysql'] -> query($sql);
	}
	echo $err;
}
else if($type == 3)
{
	$id = intval($_REQUEST['n']);
	foreach($map as $v)
	{
		if($v['id'] == $id)
		{
			$arr = $v;
			break;
		}
	}
	$xy = explode(',',$arr['needs']);
	foreach($xy as $v){
		$need = explode(':',$v);
		if($need[0] == 'needitem'){
			$npid = $need[1];
		}else if($need[0] == 'sj'){
			$nsj = $need[1];
		}
	}
		
	$sjarr = $_pm['mysql'] -> getOneRecord("SELECT sj FROM player_ext WHERE uid = {$_SESSION['id']}");
	if(!is_array($userBag)){
		if($sjarr['sj'] < $nsj){
			die("1");
		}
	}
	foreach($userBag as $v)
	{
		if($v['pid'] == $npid && $v['sums'] >= 1)
		{
			$props = unserialize($m->get('db_propsid'));
			$name = $props[$npid]['name'];
		}
	}
	if(!empty($name))
	{
		$str = "强制进入副本地图将花费您".$name."道具1件，您确定进入吗？";
		echo $str;
	}
	else
	{
		if($sjarr['sj'] >= $nsj){
			$str = "强制进入副本地图将花费您".$nsj."水晶，您确定进入吗？";
			die($str);
		}
		die("1");
	}
}
else if($type == 4)
{
	$check = 100;
	$err = 11;
	$id = intval($_REQUEST['n']);
	foreach($map as $v)
	{
		if($v['id'] == $id)
		{
			$arr = $v;
			break;
		}
	}
	$xy = explode(',',$arr['needs']);
	foreach($xy as $v){
		$need = explode(':',$v);
		if($need[0] == 'needitem'){
			$sql = "UPDATE userbag SET sums = abs(sums-1) WHERE uid = {$_SESSION['id']} and pid = {$need[1]} and sums > 0";
			$_pm['mysql']->query($sql);
			$effectRow = mysql_affected_rows($_pm['mysql']->getConn());
			
			if($effectRow == 1)
			{
				$check = 101;
				break;
			}
		}else if($need[0] == 'sj'){
			$sql = "UPDATE player_ext SET sj = sj - {$need[1]} WHERE uid = {$_SESSION['id']} and sj >= {$need[1]}";
			$_pm['mysql']->query($sql);
			$effectRow = mysql_affected_rows($_pm['mysql']->getConn());
			
			if($effectRow == 1)
			{
				$check = 101;
				break;
			}
		}
	}
	if($check == 101){
		$sql = "UPDATE fuben SET lttime = 0 WHERE uid = {$_SESSION['id']} and inmap = {$id}";
		$_pm['mysql'] -> query($sql);
		echo $err;
	}else{
		die('3');
	}
	
	/*$need = explode(":",$arr['needs']);
	$sql = "UPDATE userbag SET sums = abs(sums-1) WHERE uid = {$_SESSION['id']} and pid = {$need[1]} and sums > 0";

	$_pm['mysql']->query($sql);
	$effectRow = mysql_affected_rows($_pm['mysql']->getConn());
	
	if($effectRow != 1)
	{
		die("3");
	}

	$sql = "UPDATE fuben SET lttime = 0 WHERE uid = {$_SESSION['id']} and inmap = {$id}";
	$_pm['mysql'] -> query($sql);
	echo $err;*/
}
else if($type == 5)//成长或者物品判断
{
	$err = 100;
	$mapid = $_REQUEST['n'];
	if(!is_numeric($mapid))
	{
		die("1");//数据有误
	}
	if(empty($mapid))
	{
		die("1");//数据有误
	}
	foreach($map as $v)
	{
		if($v['id'] == $mapid)
		{
			$need = $v['czlprops'];
			break;
		}
	}
	if(!empty($need))
	{
		$arr = explode("|",$need);
		if(!empty($arr[0]))//只有成长限制
		{
			$petsAll = $_pm['user']->getUserPetById($_SESSION['id']);
			foreach($petsAll as $p)
			{
				if($p['id'] == $user['mbid'])
				{
					$czl = $p['czl'];
					break;
				}
			}
			if(empty($czl))
			{
				die("1");
			}
			if($czl >= $arr[0])
			{
				die("100");//进入地图;
			}
			else if(($czl < $arr[0]) && empty($arr[1]))
			{
				die("2");//成长不够进入此地图;
			}
			else if(!empty($arr[1]))
			{
				if(!is_array($userBag))
				{
					die("3");
				}
				foreach($userBag as $b)
				{
					if($b['pid'] == $arr[1] && $b['sums'] > 0)
					{
						$sums = $b['sums'];
						break;
					}
				}
				if(!empty($sums))
				{
					die("100");//进入地图;
				}
				else
				{
					die("3");//成长不够，且没有相应的道进入该地图。
				}
			}
		}
		else
		{
			foreach($userBag as $b)
			{
				if($b['pid'] == $arr[1] && $b['sums'] > 0)
				{
					$sums = $b['sums'];
					break;
				}
			}
			if(empty($sums))
			{
				die("3");//进入地图;
			}
		}
	}
	echo $err;
}
else if($type == 6)
{
	echo $user['mbid'];
}
$_pm['mem']->memClose();
?>
