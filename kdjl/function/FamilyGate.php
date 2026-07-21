<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

@Usage: Shop buy function
@Write date: 2008.05.02
@Update date: 2008.05.23
@Memo: Don't buy protect props.
     Fix: Max limit for buy props. (2008.06.22)
@##############################################
*/
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');

secStart($_pm['mem']);

function familyBuyFinish($code, $rollback, $clearBag)
{
	global $_pm;
	if($rollback) $_pm['mysql']->query('ROLLBACK');
	if($clearBag && defined('MEM_USERBAG_KEY')) $_pm['mem']->del(MEM_USERBAG_KEY);
	realseLock();
	$_pm['mem']->memClose();
	die(strval($code));
}

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
$bid = (isset($_REQUEST['bid']) && !is_array($_REQUEST['bid'])) ? intval($_REQUEST['bid']) : 0;
$n = (isset($_REQUEST['n']) && !is_array($_REQUEST['n'])) ? intval($_REQUEST['n']) : 0;
if($uid < 1 || $bid < 1 || $n < 1 || $n > 10)
{
	die('2');
}
if($_pm['user']->check(array('int' => array($bid, $n))) === false)
{
	die('2');
}
if(getLock($uid) === false)
{
	die('2');
}

$user = $_pm['mysql']->getOneRecord("SELECT maxbag FROM player WHERE id={$uid} FOR UPDATE");
$member = $_pm['mysql']->getOneRecord("SELECT guild_id,contribution,honor FROM guild_members WHERE member_id={$uid} FOR UPDATE");
if(!is_array($user) || !is_array($member))
{
	familyBuyFinish('3', true, false);
}

$guildId = isset($member['guild_id']) ? intval($member['guild_id']) : 0;
$guild = $guildId > 0
	? $_pm['mysql']->getOneRecord("SELECT shop_level FROM guild WHERE id={$guildId} FOR UPDATE")
	: false;
$wp = $_pm['mysql']->getOneRecord("SELECT id,vary,sell,buy,honor,contribution,guild_level FROM props WHERE id={$bid} FOR UPDATE");
if(!is_array($guild) || !is_array($wp))
{
	familyBuyFinish('3', true, false);
}

$vary = isset($wp['vary']) ? intval($wp['vary']) : 0;
$sell = isset($wp['sell']) ? intval($wp['sell']) : -1;
$buy = isset($wp['buy']) ? intval($wp['buy']) : 0;
$honor = isset($wp['honor']) ? intval($wp['honor']) : 0;
$contribution = isset($wp['contribution']) ? intval($wp['contribution']) : 0;
$requiredLevel = isset($wp['guild_level']) ? intval($wp['guild_level']) : 0;
$shopLevel = isset($guild['shop_level']) ? intval($guild['shop_level']) : 0;
if(($vary != 1 && $vary != 2) || $sell < 0 || $buy != 0 ||
	($honor <= 0 && $contribution <= 0) || $honor < 0 || $contribution < 0 ||
	$requiredLevel > $shopLevel)
{
	familyBuyFinish('3', true, false);
}

$priceHonor = $honor > 0 ? kdjlSafePositiveProduct($honor, $n) : 0;
$priceContribution = $contribution > 0 ? kdjlSafePositiveProduct($contribution, $n) : 0;
if($priceHonor === false || $priceContribution === false)
{
	familyBuyFinish('3', true, false);
}
$memberHonor = isset($member['honor']) ? intval($member['honor']) : 0;
$memberContribution = isset($member['contribution']) ? intval($member['contribution']) : 0;
if($priceContribution > $memberContribution)
{
	familyBuyFinish('10', true, false);
}
if($priceHonor > $memberHonor)
{
	familyBuyFinish('11', true, false);
}

$bagRows = $_pm['mysql']->getRecords("SELECT id,pid,vary,sums,cantrade FROM userbag
	WHERE uid={$uid} AND sums>0 AND zbing=0 AND (cantrade IS NULL OR cantrade<>3)
	ORDER BY id FOR UPDATE");
if($bagRows === false) familyBuyFinish('3', true, false);
if(!is_array($bagRows)) $bagRows = array();

$stackId = 0;
foreach($bagRows as $bagRow)
{
	if(!is_array($bagRow)) continue;
	if($vary == 1 && intval($bagRow['pid']) == $bid && intval($bagRow['vary']) == 1 &&
		(!isset($bagRow['cantrade']) || intval($bagRow['cantrade']) == 0) && $stackId < 1)
	{
		$stackId = intval($bagRow['id']);
	}
}
$neededSlots = ($vary == 2) ? $n : ($stackId > 0 ? 0 : 1);
$maxbag = isset($user['maxbag']) ? intval($user['maxbag']) : 0;
if($maxbag < 1 || count($bagRows) + $neededSlots > $maxbag)
{
	familyBuyFinish('4', true, false);
}

$paid = $_pm['mysql']->query("UPDATE guild_members
	SET honor=COALESCE(honor,0)-{$priceHonor},contribution=COALESCE(contribution,0)-{$priceContribution}
	WHERE member_id={$uid} AND guild_id={$guildId}
	AND COALESCE(honor,0)>={$priceHonor} AND COALESCE(contribution,0)>={$priceContribution}");
if(!$paid || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
{
	familyBuyFinish('10', true, false);
}

$now = time();
$purchaseOk = true;
if($vary == 2)
{
	for($i=0; $i<$n; $i++)
	{
		$sql = "INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
			VALUES({$uid},{$bid},{$sell},{$vary},1,{$now})";
		if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
		{
			$purchaseOk = false;
			break;
		}
	}
}
else if($stackId > 0)
{
	$purchaseOk = $_pm['mysql']->query("UPDATE userbag SET sums=sums+{$n},stime={$now}
		WHERE id={$stackId} AND uid={$uid} AND pid={$bid} AND vary=1 AND sums>0
		AND sums<=2147483647-{$n} AND zbing=0 AND (cantrade IS NULL OR cantrade=0)");
	if($purchaseOk && mysql_affected_rows($_pm['mysql']->getConn()) != 1) $purchaseOk = false;
}
else
{
	$purchaseOk = $_pm['mysql']->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
		VALUES({$uid},{$bid},{$sell},1,{$n},{$now})");
	if($purchaseOk && mysql_affected_rows($_pm['mysql']->getConn()) != 1) $purchaseOk = false;
}

if(!$purchaseOk || !$_pm['mysql']->query('COMMIT'))
{
	familyBuyFinish('3', true, false);
}
familyBuyFinish('0', false, true);
?>
