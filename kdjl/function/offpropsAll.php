<?php
session_start();
require_once "../config/config.game.php";

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die("1");

secStart($_pm['mem']);

$bid = (isset($_REQUEST['bid']) && !is_array($_REQUEST['bid'])) ? intval($_REQUEST['bid']) : 0;
if($bid < 1) die("3");

if(!$_pm['mysql']->query('START TRANSACTION')) die("4");

$lockedUser = $_pm['mysql']->getOneRecord("SELECT maxbag FROM player WHERE id={$uid} FOR UPDATE");
$bb = $_pm['mysql']->getOneRecord("SELECT id,zb FROM userbb WHERE id={$bid} and uid={$uid} FOR UPDATE");
$bagRows = $_pm['mysql']->getRecords("SELECT id,sums,zbing
									  FROM userbag
									 WHERE uid={$uid}
									   FOR UPDATE");
if(!is_array($lockedUser) || !is_array($bb) || !is_array($bagRows))
{
	$_pm['mysql']->query('ROLLBACK');
	die("4");
}

$bagNum = 0;
foreach($bagRows as $bagRow)
{
	if(is_array($bagRow) && intval($bagRow['sums']) > 0 && intval($bagRow['zbing']) == 0) $bagNum++;
}

$bagIds = array();
if(isset($bb['zb']) && strlen($bb['zb']) > 0)
{
	$zbRows = explode(',', $bb['zb']);
	foreach($zbRows as $zbRow)
	{
		$zbParts = explode(':', $zbRow);
		if(count($zbParts) < 2) continue;
		$zbBagId = intval($zbParts[1]);
		if($zbBagId > 0) $bagIds[$zbBagId] = $zbBagId;
	}
}
if(count($bagIds) < 1 && isset($_REQUEST['bagids']) && !is_array($_REQUEST['bagids']))
{
	$rawBagIds = explode(',', $_REQUEST['bagids']);
	foreach($rawBagIds as $rawBagId)
	{
		$rawBagId = intval($rawBagId);
		if($rawBagId > 0) $bagIds[$rawBagId] = $rawBagId;
	}
}
if(count($bagIds) < 1)
{
	$_pm['mysql']->query('ROLLBACK');
	die("6");
}
$bagWhere = ' and id in ('.implode(',', $bagIds).')';
$equips = $_pm['mysql']->getRecords("SELECT id
									 FROM userbag
									WHERE uid={$uid}{$bagWhere}
									ORDER BY id
									  FOR UPDATE");
if(!is_array($equips) || count($equips) < 1)
{
	$_pm['mysql']->query('ROLLBACK');
	die("6");
}

if($bagNum + count($equips) > intval($lockedUser['maxbag']))
{
	$_pm['mysql']->query('ROLLBACK');
	die("5");
}

$equipIds = array();
foreach($equips as $row)
{
	if(is_array($row) && intval($row['id']) > 0) $equipIds[] = intval($row['id']);
}
if(count($equipIds) < 1)
{
	$_pm['mysql']->query('ROLLBACK');
	die("6");
}
$equipList = implode(',', $equipIds);

if(!$_pm['mysql']->query("UPDATE userbag SET zbing=0,zbpets=0 WHERE uid={$uid} and id in ({$equipList})") ||
	mysql_affected_rows($_pm['mysql']->getConn()) != count($equipIds))
{
	$_pm['mysql']->query('ROLLBACK');
	die("4");
}

if(!$_pm['mysql']->query("UPDATE userbb SET zb='',addmp=0,addhp=0 WHERE id={$bid} and uid={$uid}") ||
	mysql_affected_rows($_pm['mysql']->getConn()) != 1)
{
	$_pm['mysql']->query('ROLLBACK');
	die("4");
}

if(!$_pm['mysql']->query('COMMIT'))
{
	$_pm['mysql']->query('ROLLBACK');
	die("4");
}

$lastKey = 'last_takeoff_equips_'.$uid.'_'.$bid;
$globalLastKey = 'last_takeoff_equips_'.$uid;
$_SESSION[$lastKey] = $equipIds;
$_SESSION[$globalLastKey] = $equipIds;
$_pm['mem']->set(array('k'=>$lastKey, 'v'=>$equipIds));
$_pm['mem']->set(array('k'=>$globalLastKey, 'v'=>$equipIds));

$_pm['mem']->del(MEM_USERBB_KEY);
$_pm['mem']->del(MEM_USERBAG_KEY);
formatMsgEffect($bid);
$_pm['mem']->set(array("k"=>"User_bb_equip_changed_".$bid.'_'.$uid,"v"=>1));
$_pm['mem']->del("User_bb_equip_info_a_".$bid.'_'.$uid);
$_pm['mem']->del("User_bb_equip_info_b_".$bid.'_'.$uid);
echo "2";
$_pm['mem']->memClose();
?>
