<?php
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');
secStart($_pm['mem']);

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
$op = (isset($_GET['op']) && !is_array($_GET['op'])) ? $_GET['op'] : '';
$action = (isset($_GET['action']) && !is_array($_GET['action'])) ? $_GET['action'] : '';
if($uid < 1 || ($op !== 'cfight' && $op !== 'tgfight')) die('a');

$ttGateTransactionActive = false;
function ttGateShutdown()
{
	global $_pm,$ttGateTransactionActive;
	if($ttGateTransactionActive && isset($_pm['mysql']))
	{
		$_pm['mysql']->query('ROLLBACK');
		$ttGateTransactionActive = false;
	}
	if(function_exists('realseLock')) realseLock();
}
register_shutdown_function('ttGateShutdown');

function ttGateFinish($message, $success, $clearCaches, $clearTimeFlag)
{
	global $_pm,$uid,$ttGateTransactionActive;
	$message = (string)$message;
	$success = $success ? true : false;
	if($ttGateTransactionActive)
	{
		if($success)
		{
			if(!$_pm['mysql']->query('COMMIT'))
			{
				$_pm['mysql']->query('ROLLBACK');
				$message = 'q';
				$success = false;
			}
		}
		else
		{
			$_pm['mysql']->query('ROLLBACK');
		}
		$ttGateTransactionActive = false;
	}
	if($success && $clearCaches)
	{
		if(defined('MEM_USER_KEY')) $_pm['mem']->del(MEM_USER_KEY);
		if(defined('MEM_USERBB_KEY')) $_pm['mem']->del(MEM_USERBB_KEY);
	}
	if($success && $clearTimeFlag) $_pm['mem']->del('tgtimeflag'.$uid);
	realseLock();
	die($message);
}

function ttGatePreviousDay($timestamp)
{
	$timestamp = intval($timestamp);
	return $timestamp > 0 && intval(date('Ymd', $timestamp)) < intval(date('Ymd'));
}

function ttGateResetForToday($uid)
{
	global $_pm;
	$uid = intval($uid);
	if($uid < 1) return false;
	if(!$_pm['mysql']->query('DELETE FROM tgt WHERE uid='.$uid)) return false;
	if(!$_pm['mysql']->query('UPDATE player_ext SET tgt=0,tgttime=0,tglasttime=0 WHERE uid='.$uid)) return false;
	if(tgtgw() !== true) return false;
	return $_pm['mysql']->query('UPDATE player SET inmap=126 WHERE id='.$uid);
}

function ttGateCrystalCost($mode, $progress, $uid)
{
	global $_pm;
	if($mode === 'tgfight') return 1000;
	$cost = (max(0, intval($progress)) + 1) * 20;
	$flag = kdjlSafeMemValue($_pm['mem']->get('tgtimeflag'.intval($uid)), 0);
	if(intval($flag) > 0 && date('Ymd', intval($flag)) === date('Ymd')) return 1000;
	return max(20, $cost);
}

$lock = getLock($uid);
if(!is_array($lock))
{
	realseLock();
	die('q');
}
$ttGateTransactionActive = true;

if(!$_pm['mysql']->query("INSERT INTO player_ext(uid,bbshow) VALUES({$uid},5) ON DUPLICATE KEY UPDATE uid=uid"))
{
	ttGateFinish('a', false, false, false);
}
$user = $_pm['mysql']->getOneRecord("SELECT tgt,tgttime,sj,tglasttime FROM player_ext WHERE uid={$uid} FOR UPDATE");
if(!is_array($user)) ttGateFinish('a', false, false, false);
$progress = isset($user['tgt']) ? max(0, intval($user['tgt'])) : 0;
$finishTime = isset($user['tgttime']) ? intval($user['tgttime']) : 0;
$lastFightTime = isset($user['tglasttime']) ? intval($user['tglasttime']) : 0;

if(ttGatePreviousDay($lastFightTime) || ttGatePreviousDay($finishTime))
{
	if(!ttGateResetForToday($uid)) ttGateFinish('q', false, false, false);
	ttGateFinish('b', true, true, false);
}

if($finishTime === 0)
{
	$currentMonster = $_pm['mysql']->getOneRecord("SELECT gid FROM tgt WHERE uid={$uid} LIMIT 1");
	$started = is_array($currentMonster) ? true : tgtgw();
	if($started === 'a')
	{
		$cost = $op === 'tgfight' ? 1000 : ttGateCrystalCost($op, 0, $uid);
		ttGateFinish((string)$cost, true, true, false);
	}
	if($started !== true) ttGateFinish('q', false, false, false);
	if(!$_pm['mysql']->query("UPDATE player SET inmap=126 WHERE id={$uid}")) ttGateFinish('q', false, false, false);
	ttGateFinish('b', true, true, false);
}

$cost = ttGateCrystalCost($op, $progress, $uid);
if($action !== 'do') ttGateFinish((string)$cost, true, false, false);

if(!$_pm['mysql']->query("UPDATE player_ext SET sj=sj-{$cost} WHERE uid={$uid} AND sj>={$cost}") ||
	mysql_affected_rows($_pm['mysql']->getConn()) !== 1)
{
	ttGateFinish('c', false, false, false);
}
if(!$_pm['mysql']->query("DELETE FROM tgt WHERE uid={$uid}")) ttGateFinish('q', false, false, false);

if($op === 'tgfight')
{
	if(!$_pm['mysql']->query("UPDATE player_ext SET tgt=0,tgttime=0 WHERE uid={$uid}")) ttGateFinish('q', false, false, false);
}
if(tgtgw() !== true) ttGateFinish('q', false, false, false);
if(!$_pm['mysql']->query("UPDATE player SET inmap=126 WHERE id={$uid}")) ttGateFinish('q', false, false, false);
if(!$_pm['mysql']->query("UPDATE userbb SET hp=srchp,mp=srcmp WHERE uid={$uid}")) ttGateFinish('q', false, false, false);
if($op === 'cfight' && !$_pm['mysql']->query("UPDATE player_ext SET tgttime=0 WHERE uid={$uid}")) ttGateFinish('q', false, false, false);

ttGateFinish('d', true, true, $op === 'cfight');
?>
