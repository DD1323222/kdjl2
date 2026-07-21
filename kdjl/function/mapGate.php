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
$mapTransactionActive = false;

function mapGateShutdown()
{
	global $_pm, $mapTransactionActive;
	$error = error_get_last();
	if(!is_array($error) || !in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true))
	{
		return;
	}
	if($mapTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$mapTransactionActive = false;
	}
}
register_shutdown_function('mapGateShutdown');

$from = (isset($_REQUEST['from']) && !is_array($_REQUEST['from'])) ? intval($_REQUEST['from']) : 0;
if($from != 1)
{
	secStart($_pm['mem']);
}
$m = $_pm['mem'];
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid <= 0) die("3");
$firstIn = isset($_SESSION['first_in']) ? intval($_SESSION['first_in']) : 0;
if( $firstIn == 2 || $firstIn == 3 )
{
	$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$uid."'");
}
$user		= $_pm['user']->getUserById($uid);
del_bag_expire();
$userBag    = $_pm['user']->getUserBagById($uid);
if(!is_array($user)) die("3");
if(!is_array($userBag)) $userBag = array();
$map = kdjlSafeMemValue($m->get(MEM_MAP_KEY), array());
if(!is_array($map)) $map = array();
$type = (isset($_REQUEST['type']) && !is_array($_REQUEST['type'])) ? intval($_REQUEST['type']) : 0;
$requestN = (isset($_REQUEST['n']) && !is_array($_REQUEST['n'])) ? intval($_REQUEST['n']) : 0;
$strs = '';
$str = '';
if($type == "1")//普通地图
{
	$id = $requestN;
	$usermap = explode(",",isset($user['mapinfo']) ? $user['mapinfo'] : '');
	foreach($usermap as $v)
	{
		$mapinfo = explode(":",$v);
		$time = time();
		if(count($mapinfo) < 2) continue;
		if($mapinfo[0] == $id && $mapinfo[1] > $time)
		{
			die("10");//地图已经打开
		}
	}
	foreach($map as $v)
	{
		if(!is_array($v) || !isset($v['id'])) continue;
		if($v['id'] == $id)
		{
			if($from == 1)
			{
				$_pm['mysql'] -> query(" UPDATE player SET bot_map_id = {$id} WHERE id = '".$uid."'");
			}
			die("12");//不需要道具的
		}
	}
		echo $strs;
}
else if($type == 2)//确定
{
	$err = 11;
	$id = $requestN;
	$maparr = false;
	foreach($map as $v)
	{
		if(!is_array($v) || !isset($v['id'])) continue;
		if($v['id'] == $id)
		{
			$maparr = $v;
			break;
		}
	}
	if(!is_array($maparr) || !isset($maparr['needs'])) die("3");
	$arr = explode(":",$maparr['needs']);
	if(count($arr) < 2 || $arr[1] == '')
	{
		die("3");
	}
	$needs = explode("|",$arr[1]);
	if(empty($needs[0]))
	{
		die("3");
	}
	$needs[0] = intval($needs[0]);
	if($needs[0] <= 0)
	{
		die("3");
	}
	if(empty($needs[1]))
	{
		$needs[1] = 1 * 12 * 30 * 3600;
	}
	else
	{
		$needs[1] = intval($needs[1]);
		if($needs[1] <= 0) $needs[1] = 1 * 12 * 30 * 3600;
	}
	$time1 = time()+$needs[1];
	if(!$_pm['mysql']->query('START TRANSACTION')){
		die("3");
	}
	$mapTransactionActive = true;
	$mapUser = $_pm['mysql']->getOneRecord("SELECT prestige,mapinfo FROM player WHERE id = {$uid} FOR UPDATE");
	if(!is_array($mapUser)){
		$_pm['mysql']->query('ROLLBACK');
		die("3");
	}
	$userarr = explode(",",isset($mapUser['mapinfo']) ? $mapUser['mapinfo'] : '');
	$now = time();
	foreach($userarr as $v)
	{
		$narr = explode(":",$v);
		if(count($narr) < 2) continue;
		$entryMapid = intval($narr[0]);
		$entryExpiry = intval($narr[1]);
		if($entryMapid == $id && $entryExpiry > $now)
		{
			$_pm['mysql']->query('ROLLBACK');
			die("10");
		}
		if($entryMapid < 1 || $entryMapid == $id || $entryExpiry <= $now)
		{
			continue;
		}
		$str .= $entryMapid.":".$entryExpiry.",";
	}
	$str .= $id.":".$time1;
	if($arr[0] == 'needww')//需要威望
	{
		$sql = "UPDATE player SET prestige = prestige - {$needs[0]},mapinfo= '{$str}' where id = {$uid} and prestige >= {$needs[0]}";
		if(!$_pm['mysql'] -> query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
		{
			$_pm['mysql']->query('ROLLBACK');
			die("3");
		}
	}
	else if($arr[0] == 'needtime' || $arr[0] == 'needitem')
	{
		$sql = "UPDATE userbag SET sums = sums-1 WHERE pid = {$needs[0]} and uid = {$uid} and sums > 0 and zbing=0 and (cantrade IS NULL OR cantrade<>3) ORDER BY id LIMIT 1";

		$effectRow = $_pm['mysql']->query($sql) ? mysql_affected_rows($_pm['mysql']->getConn()) : 0;

		if($effectRow != 1)
		{
			$_pm['mysql']->query('ROLLBACK');
			die("3");
		}
		if(!$_pm['mysql']->query("DELETE FROM userbag WHERE uid = {$uid} AND pid = {$needs[0]} AND sums <= 0 AND bsum <= 0 AND psum <= 0 AND pyb = 0 AND zbing = 0 AND (cantrade IS NULL OR cantrade<>3)"))
		{
			$_pm['mysql']->query('ROLLBACK');
			die("3");
		}

		$sql = "UPDATE player SET mapinfo= '{$str}' where id = {$uid}";
		if(!$_pm['mysql'] -> query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
		{
			$_pm['mysql']->query('ROLLBACK');
			die("3");
		}
	}
	else
	{
		$_pm['mysql']->query('ROLLBACK');
		die("3");
	}
	if(!$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		die("3");
	}
	$mapTransactionActive = false;
	$_pm['mem']->del(MEM_USER_KEY);
	$_pm['mem']->del(MEM_USERBAG_KEY);
	echo $err;
}
else if($type == 3)
{
	$id = $requestN;
	$arr = false;
	$npid = 0;
	$nsj = 0;
	$name = '';
	foreach($map as $v)
	{
		if(!is_array($v) || !isset($v['id'])) continue;
		if($v['id'] == $id)
		{
			$arr = $v;
			break;
		}
	}
	if(!is_array($arr) || !isset($arr['needs'])) die("1");
	$xy = explode(',',$arr['needs']);
	$fubenRow = $_pm['mysql']->getOneRecord("SELECT lttime FROM fuben WHERE uid = {$uid} and inmap = {$id}");
	if(!is_array($fubenRow)){
		die('3');
	}
	foreach($xy as $v){
		$need = explode(':',$v);
		if(count($need) < 2) continue;
		if($need[0] == 'needitem'){
			$npid = intval($need[1]);
		}else if($need[0] == 'sj'){
			$nsj = intval($need[1]);
		}
	}
	if($npid <= 0 && $nsj <= 0)
	{
		die("1");
	}

	$sjarr = $_pm['mysql'] -> getOneRecord("SELECT sj FROM player_ext WHERE uid = {$uid}");
	if(!is_array($sjarr)) $sjarr = array('sj' => 0);
	if(!is_array($userBag)){
		if($sjarr['sj'] < $nsj){
			die("1");
		}
	}
	if(is_array($userBag))
	{
		foreach($userBag as $v)
		{
			if(!is_array($v)) continue;
			if(!isset($v['pid'])) $v['pid'] = 0;
			if(!isset($v['sums'])) $v['sums'] = 0;
			if(!isset($v['zbing'])) $v['zbing'] = 0;
			if(!isset($v['cantrade'])) $v['cantrade'] = 0;
			if($v['pid'] == $npid && $v['sums'] >= 1 && intval($v['zbing']) == 0 && intval($v['cantrade']) != 3)
			{
				$props = kdjlSafeMemValue($m->get('db_propsid'), array());
				if(!is_array($props)) $props = array();
				$name = isset($props[$npid]['name']) ? $props[$npid]['name'] : '';
			}
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
	$id = $requestN;
	$arr = false;
	foreach($map as $v)
	{
		if(!is_array($v) || !isset($v['id'])) continue;
		if($v['id'] == $id)
		{
			$arr = $v;
			break;
		}
	}
	if(!is_array($arr) || !isset($arr['needs'])) die('3');
	$xy = explode(',',$arr['needs']);
	if(!$_pm['mysql']->query('START TRANSACTION')){
		die('3');
	}
	$mapTransactionActive = true;
	if(!$_pm['mysql']->query("INSERT INTO player_ext(uid,bbshow) VALUES({$uid},5) ON DUPLICATE KEY UPDATE uid=uid")){
		$_pm['mysql']->query('ROLLBACK');
		die('3');
	}
	$fubenRow = $_pm['mysql']->getOneRecord("SELECT gwid,lttime,srctime FROM fuben WHERE uid = {$uid} and inmap = {$id} FOR UPDATE");
	if(!is_array($fubenRow)){
		$_pm['mysql']->query('ROLLBACK');
		die('3');
	}
	$currentGwid = isset($fubenRow['gwid']) ? intval($fubenRow['gwid']) : 0;
	$currentLttime = isset($fubenRow['lttime']) ? intval($fubenRow['lttime']) : 0;
	$currentSrctime = isset($fubenRow['srctime']) ? intval($fubenRow['srctime']) : 0;
	$cooldownActive = ($currentGwid == 0 && $currentLttime > 0 && $currentSrctime > 0 && time() - $currentLttime < $currentSrctime);
	if(!$cooldownActive)
	{
		if(!$_pm['mysql']->query('COMMIT'))
		{
			$_pm['mysql']->query('ROLLBACK');
			die('3');
		}
		$mapTransactionActive = false;
		$_pm['mem']->memClose();
		die((string)$err);
	}
	foreach($xy as $v){
		$need = explode(':',$v);
		if(count($need) < 2) continue;
		$needNum = intval($need[1]);
		if($needNum <= 0) continue;
		if($need[0] == 'needitem'){
			$sql = "UPDATE userbag SET sums = sums-1 WHERE uid = {$uid} and pid = {$needNum} and sums > 0 and zbing=0 and (cantrade IS NULL OR cantrade<>3) ORDER BY id LIMIT 1";
			$effectRow = $_pm['mysql']->query($sql) ? mysql_affected_rows($_pm['mysql']->getConn()) : 0;

			if($effectRow == 1)
			{
				if(!$_pm['mysql']->query("DELETE FROM userbag WHERE uid = {$uid} AND pid = {$needNum} AND sums <= 0 AND bsum <= 0 AND psum <= 0 AND pyb = 0 AND zbing = 0 AND (cantrade IS NULL OR cantrade<>3)"))
				{
					$_pm['mysql']->query('ROLLBACK');
					die('3');
				}
				$check = 101;
				break;
			}
		}else if($need[0] == 'sj'){
			$sql = "UPDATE player_ext SET sj = sj - {$needNum} WHERE uid = {$uid} and sj >= {$needNum}";
			$effectRow = $_pm['mysql']->query($sql) ? mysql_affected_rows($_pm['mysql']->getConn()) : 0;

			if($effectRow == 1)
			{
				$check = 101;
				break;
			}
		}
	}
	if($check == 101){
		$sql = "UPDATE fuben SET lttime = 0 WHERE uid = {$uid} and inmap = {$id} and COALESCE(gwid,0) = 0 and lttime = {$currentLttime}";
		if(!$_pm['mysql'] -> query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
			$_pm['mysql']->query('ROLLBACK');
			die('3');
		}
		if(!$_pm['mysql']->query('COMMIT')){
			$_pm['mysql']->query('ROLLBACK');
			die('3');
		}
		$mapTransactionActive = false;
		$_pm['mem']->del(MEM_USER_KEY);
		$_pm['mem']->del(MEM_USERBAG_KEY);
		echo $err;
	}else{
		$_pm['mysql']->query('ROLLBACK');
		die('3');
	}

	/*$need = explode(":",$arr['needs']);
	$sql = "UPDATE userbag SET sums = sums-1 WHERE uid = {$_SESSION['id']} and pid = {$need[1]} and sums > 0 ORDER BY id LIMIT 1";

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
	$mapid = $requestN;
	$need = '';
	$czl = 0;
	$sums = 0;
	$mapFound = false;
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
		if(!is_array($v) || !isset($v['id'])) continue;
		if($v['id'] == $mapid)
		{
			$mapFound = true;
			$need = isset($v['czlprops']) ? $v['czlprops'] : '';
			break;
		}
	}
	if(!$mapFound) die("1");
	if(!empty($need))
	{
		$arr = explode("|",$need);
		if(!empty($arr[0]))//只有成长限制
		{
			$petsAll = $_pm['user']->getUserPetById($uid);
			if(!is_array($petsAll)) $petsAll = array();
			foreach($petsAll as $p)
			{
				if(isset($user['mbid']) && $p['id'] == $user['mbid'])
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
					if(!is_array($b)) continue;
					if(!isset($b['pid'])) $b['pid'] = 0;
					if(!isset($b['sums'])) $b['sums'] = 0;
					if(!isset($b['zbing'])) $b['zbing'] = 0;
					if(!isset($b['cantrade'])) $b['cantrade'] = 0;
					if($b['pid'] == $arr[1] && $b['sums'] > 0 && intval($b['zbing']) == 0 && intval($b['cantrade']) != 3)
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
			if(!isset($arr[1]) || empty($arr[1]) || !is_array($userBag))
			{
				die("3");
			}
			foreach($userBag as $b)
			{
				if(!is_array($b)) continue;
				if(!isset($b['pid'])) $b['pid'] = 0;
				if(!isset($b['sums'])) $b['sums'] = 0;
				if(!isset($b['zbing'])) $b['zbing'] = 0;
				if(!isset($b['cantrade'])) $b['cantrade'] = 0;
				if($b['pid'] == $arr[1] && $b['sums'] > 0 && intval($b['zbing']) == 0 && intval($b['cantrade']) != 3)
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
	echo isset($user['mbid']) ? $user['mbid'] : 0;
}
$_pm['mem']->memClose();
?>
