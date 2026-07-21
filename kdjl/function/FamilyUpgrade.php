<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

@Usage: FamilyUpgrade buy function
@Write date: 2010.04.02
@##############################################
*/
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');

secStart($_pm['mem']);

function familyUpgradeFinish($code, $rollback, $clearBag)
{
	global $_pm;
	if($rollback) $_pm['mysql']->query('ROLLBACK');
	if($clearBag && defined('MEM_USERBAG_KEY')) $_pm['mem']->del(MEM_USERBAG_KEY);
	realseLock();
	$_pm['mem']->memClose();
	die(strval($code));
}

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1)
{
	die('1');
}

$srctime = 15;
$timeKey = 'time'.$uid;
$lastTime = isset($_SESSION[$timeKey]) ? intval($_SESSION[$timeKey]) : 0;
$now = time();
if($lastTime > 0 && $now - $lastTime < $srctime)
{
	die('6');
}
$_SESSION[$timeKey] = $now;

if(getLock($uid) === false)
{
	die('4');
}

$member = $_pm['mysql']->getOneRecord("SELECT guild_id,priv FROM guild_members
	WHERE member_id={$uid} FOR UPDATE");
if(!is_array($member) || (intval($member['priv']) != 2 && intval($member['priv']) != 3))
{
	familyUpgradeFinish('1', true, false);
}

$guildId = intval($member['guild_id']);
$guild = $_pm['mysql']->getOneRecord("SELECT id,level,shop_level FROM guild
	WHERE id={$guildId} FOR UPDATE");
if(!is_array($guild))
{
	familyUpgradeFinish('4', true, false);
}
$guildLevel = intval($guild['level']);
$shopLevel = intval($guild['shop_level']);
if($shopLevel >= $guildLevel)
{
	familyUpgradeFinish('2', true, false);
}

$settings = $_pm['mysql']->getOneRecord("SELECT id,need_items_for_shop FROM guild_settings
	WHERE level={$guildLevel} ORDER BY id LIMIT 1 FOR UPDATE");
if(!is_array($settings) || !isset($settings['need_items_for_shop']) || trim($settings['need_items_for_shop']) === '')
{
	familyUpgradeFinish('3', true, false);
}

$needs = array();
$parts = explode(',', $settings['need_items_for_shop']);
foreach($parts as $part)
{
	$pair = explode(':', trim($part));
	if(count($pair) != 2)
	{
		familyUpgradeFinish('3', true, false);
	}
	$pid = intval($pair[0]);
	$num = intval($pair[1]);
	if($pid < 1 || $num < 1)
	{
		familyUpgradeFinish('3', true, false);
	}
	if(!isset($needs[$pid])) $needs[$pid] = 0;
	if($needs[$pid] > 2147483647 - $num)
	{
		familyUpgradeFinish('3', true, false);
	}
	$needs[$pid] += $num;
}
if(count($needs) < 1)
{
	familyUpgradeFinish('3', true, false);
}
ksort($needs, SORT_NUMERIC);

$rowsByPid = array();
foreach($needs as $pid => $required)
{
	$pid = intval($pid);
	$rows = $_pm['mysql']->getRecords("SELECT id,pid,sums FROM userbag
		WHERE uid={$uid} AND pid={$pid} AND sums>0 AND zbing=0
		AND (cantrade IS NULL OR cantrade<>3) ORDER BY id FOR UPDATE");
	if(!is_array($rows)) $rows = array();
	$available = 0;
	foreach($rows as $row)
	{
		if(!is_array($row)) continue;
		$available += max(0, intval($row['sums']));
	}
	if($available < $required)
	{
		familyUpgradeFinish('3', true, false);
	}
	$rowsByPid[$pid] = $rows;
}

$upgrade = $_pm['mysql']->query("UPDATE guild SET shop_level=shop_level+1
	WHERE id={$guildId} AND shop_level={$shopLevel} AND shop_level<level");
if(!$upgrade || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
{
	familyUpgradeFinish('4', true, false);
}

foreach($needs as $pid => $required)
{
	$remaining = intval($required);
	foreach($rowsByPid[$pid] as $row)
	{
		if($remaining < 1) break;
		$rowId = intval($row['id']);
		$take = min($remaining, max(0, intval($row['sums'])));
		if($rowId < 1 || $take < 1) continue;
		$used = $_pm['mysql']->query("UPDATE userbag SET sums=sums-{$take}
			WHERE id={$rowId} AND uid={$uid} AND pid=".intval($pid)."
			AND sums>={$take} AND zbing=0 AND (cantrade IS NULL OR cantrade<>3)");
		if(!$used || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
		{
			familyUpgradeFinish('4', true, false);
		}
		$deleted = $_pm['mysql']->query("DELETE FROM userbag WHERE id={$rowId} AND uid={$uid}
			AND sums<=0 AND bsum<=0 AND psum<=0 AND pyb=0 AND zbing=0
			AND (cantrade IS NULL OR cantrade<>3)");
		if(!$deleted)
		{
			familyUpgradeFinish('4', true, false);
		}
		$remaining -= $take;
	}
	if($remaining > 0)
	{
		familyUpgradeFinish('4', true, false);
	}
}

if(!$_pm['mysql']->query('COMMIT'))
{
	familyUpgradeFinish('4', true, false);
}
familyUpgradeFinish('5', false, true);
?>
