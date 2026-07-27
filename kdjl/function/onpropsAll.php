<?php
session_start();
require_once "../config/config.game.php";

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die("1");

secStart($_pm['mem']);

$bid = (isset($_REQUEST['bid']) && !is_array($_REQUEST['bid'])) ? intval($_REQUEST['bid']) : 0;
if($bid < 1) die("3");

$petLastKey = 'last_takeoff_equips_'.$uid.'_'.$bid;
$globalLastKey = 'last_takeoff_equips_'.$uid;
$lastRows = isset($_SESSION[$globalLastKey]) ? $_SESSION[$globalLastKey] : array();
if(!is_array($lastRows) || count($lastRows) < 1)
{
	$lastRows = kdjlSafeMemValue($_pm['mem']->get($globalLastKey), array());
}
if(!is_array($lastRows) || count($lastRows) < 1)
{
	$lastRows = isset($_SESSION[$petLastKey]) ? $_SESSION[$petLastKey] : array();
}
if(!is_array($lastRows) || count($lastRows) < 1)
{
	$lastRows = kdjlSafeMemValue($_pm['mem']->get($petLastKey), array());
}
if(!is_array($lastRows) || count($lastRows) < 1)
{
	die("6");
}

$ids = array();
foreach($lastRows as $bagId)
{
	$bagId = intval($bagId);
	if($bagId > 0) $ids[$bagId] = $bagId;
}
if(count($ids) < 1) die("6");

$idList = implode(',', $ids);
if(!$_pm['mysql']->query('START TRANSACTION')) die("4");

$bb = $_pm['mysql']->getOneRecord("SELECT id,uid,level,wx,zb FROM userbb WHERE id={$bid} and uid={$uid} FOR UPDATE");
if(!is_array($bb))
{
	$_pm['mysql']->query('ROLLBACK');
	die("3");
}

$rows = $_pm['mysql']->getRecords("SELECT id,pid,sums,zbing,zbpets
									 FROM userbag
									WHERE uid={$uid} and id in ({$idList})
									  FOR UPDATE");
if(!is_array($rows))
{
	$_pm['mysql']->query('ROLLBACK');
	die("6");
}

$byId = array();
foreach($rows as $row)
{
	if(is_array($row)) $byId[intval($row['id'])] = $row;
}

$slotMap = array();
if(isset($bb['zb']) && strlen($bb['zb']) > 0)
{
	$oldZb = explode(',', $bb['zb']);
	foreach($oldZb as $oldOne)
	{
		$oldOne = trim($oldOne);
		if($oldOne == '') continue;
		$parts = explode(':', $oldOne);
		if(count($parts) < 2) continue;
		$slot = intval($parts[0]);
		$oldBagId = intval($parts[1]);
		if($slot > 0 && $oldBagId > 0) $slotMap[$slot] = $oldBagId;
	}
}

$mempropsid = kdjlSafeMemValue($_pm['mem']->get('db_propsid'), array());
$pids = array();
foreach($byId as $bagId => $row)
{
	if(is_array($row) && intval($row['pid']) > 0) $pids[intval($row['pid'])] = intval($row['pid']);
}
if(count($pids) > 0)
{
	$pidList = implode(',', $pids);
	$propRows = $_pm['mysql']->getRecords("SELECT id,varyname,postion,requires FROM props WHERE id in ({$pidList})");
	if(is_array($propRows))
	{
		foreach($propRows as $propRow)
		{
			if(is_array($propRow) && isset($propRow['id'])) $mempropsid[intval($propRow['id'])] = $propRow;
		}
	}
}
$equipIds = array();
foreach($ids as $bagId)
{
	if(!isset($byId[$bagId]) || !is_array($byId[$bagId])) continue;
	$row = $byId[$bagId];
	$pid = intval($row['pid']);
	$prop = (isset($mempropsid[$pid]) && is_array($mempropsid[$pid])) ? $mempropsid[$pid] : array();
	if(intval($row['zbing']) != 0 || !isset($prop['varyname']) || intval($prop['varyname']) != 9) continue;

	$postion = isset($prop['postion']) ? intval($prop['postion']) : 0;
	if($postion < 1 || isset($slotMap[$postion])) continue;

	$requires = isset($prop['requires']) ? $prop['requires'] : '';
	$needLv = 0;
	$needWx = '';
	if($requires != '')
	{
		$reqArr = explode(',', $requires);
		foreach($reqArr as $reqOne)
		{
			$reqOne = trim($reqOne);
			if($reqOne == '') continue;
			$reqParts = explode(':', $reqOne);
			if(count($reqParts) < 2) continue;
			if($reqParts[0] == 'lv') $needLv = intval($reqParts[1]);
			else if($reqParts[0] == 'wx') $needWx = $reqParts[1];
		}
	}
	if($needLv > 0 && intval($bb['level']) < $needLv) continue;
	if($needWx !== '' && $needWx != $bb['wx']) continue;

	$slotMap[$postion] = $bagId;
	$equipIds[] = $bagId;
}

if(count($equipIds) < 1)
{
	$_pm['mysql']->query('ROLLBACK');
	die("6");
}

ksort($slotMap);
$zbParts = array();
foreach($slotMap as $slot => $bagId)
{
	$zbParts[] = intval($slot).':'.intval($bagId);
}
$newZbSql = $_pm['mysql']->escape(implode(',', $zbParts));
$equipList = implode(',', $equipIds);

if(!$_pm['mysql']->query("UPDATE userbb SET zb='{$newZbSql}',addmp=0,addhp=0 WHERE id={$bid} and uid={$uid}") ||
	mysql_affected_rows($_pm['mysql']->getConn()) != 1)
{
	$_pm['mysql']->query('ROLLBACK');
	die("4");
}

if(!$_pm['mysql']->query("UPDATE userbag SET zbing=1,zbpets={$bid} WHERE uid={$uid} and id in ({$equipList}) and zbing=0") ||
	mysql_affected_rows($_pm['mysql']->getConn()) != count($equipIds))
{
	$_pm['mysql']->query('ROLLBACK');
	die("4");
}

if(!$_pm['mysql']->query('COMMIT'))
{
	$_pm['mysql']->query('ROLLBACK');
	die("4");
}

unset($_SESSION[$petLastKey]);
unset($_SESSION[$petLastKey.'_token']);
unset($_SESSION[$globalLastKey]);
$_pm['mem']->del($petLastKey);
$_pm['mem']->del($globalLastKey);
$_pm['mem']->del(MEM_USERBB_KEY);
$_pm['mem']->del(MEM_USERBAG_KEY);
formatMsgEffect($bid);
$_pm['mem']->set(array("k"=>"User_bb_equip_changed_".$bid.'_'.$uid,"v"=>1));
$_pm['mem']->del("User_bb_equip_info_a_".$bid.'_'.$uid);
$_pm['mem']->del("User_bb_equip_info_b_".$bid.'_'.$uid);
echo "2";
$_pm['mem']->memClose();
?>
