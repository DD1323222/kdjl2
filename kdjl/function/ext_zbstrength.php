<?php
/**
 * Equipment strengthening settlement.
 */
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');

secStart($_pm['mem']);

$zbStrengthUserLocked = false;
$zbStrengthItemLocked = false;
$zbStrengthItemId = 0;
$zbStrengthTransactionActive = false;
$zbStrengthPendingLogId = 0;
$zbStrengthCommitted = false;

function zbStrengthRelease()
{
	global $_pm, $zbStrengthUserLocked, $zbStrengthItemLocked, $zbStrengthItemId,
		$zbStrengthTransactionActive, $zbStrengthPendingLogId, $zbStrengthCommitted;
	if ($zbStrengthTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$zbStrengthTransactionActive = false;
	}
	if (!$zbStrengthCommitted && intval($zbStrengthPendingLogId) > 0)
	{
		$_pm['mysql']->query('DELETE FROM gamelog WHERE id='.intval($zbStrengthPendingLogId).' AND vary=5');
		$zbStrengthPendingLogId = 0;
	}
	if ($zbStrengthItemLocked)
	{
		unLockItem($zbStrengthItemId);
		$zbStrengthItemLocked = false;
	}
	if ($zbStrengthUserLocked && function_exists('realseLock'))
	{
		realseLock();
		$zbStrengthUserLocked = false;
	}
}

function zbStrengthFail($code)
{
	zbStrengthRelease();
	die(strval($code));
}

register_shutdown_function('zbStrengthRelease');

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
$pid = (isset($_REQUEST['pid']) && !is_array($_REQUEST['pid'])) ? intval($_REQUEST['pid']) : 0;
$pids = (isset($_REQUEST['pids']) && !is_array($_REQUEST['pids'])) ? intval($_REQUEST['pids']) : 0;
$bagId = (isset($_REQUEST['bid']) && !is_array($_REQUEST['bid'])) ? intval($_REQUEST['bid']) : 0;
if ($uid < 1 || $pid < 1 || $pids < 0 || $bagId < 1) die('0');

$propsById = kdjlSafeMemValue($_pm['mem']->get('db_propsid'), array());
if (!is_array($propsById) || !isset($propsById[$pid]) || !is_array($propsById[$pid])) die('0');
$equipment = $propsById[$pid];
if (!isset($equipment['varyname']) || intval($equipment['varyname']) != 9 ||
	!isset($equipment['plusflag']) || intval($equipment['plusflag']) != 1)
{
	die('0');
}
$requiredPid = isset($equipment['pluspid']) ? intval($equipment['pluspid']) : 0;
$plusValues = isset($equipment['plusget']) ? explode(',', strval($equipment['plusget'])) : array();
$maxStrengthLevel = min(is_array($harden) ? count($harden) : 0, count($plusValues));
if ($requiredPid < 1 || $maxStrengthLevel < 1) die('0');

$auxiliary = false;
$auxiliaryEffect = '';
if ($pids > 0)
{
	if (!isset($propsById[$pids]) || !is_array($propsById[$pids]) ||
		!isset($propsById[$pids]['varyname']) || intval($propsById[$pids]['varyname']) != 11)
	{
		die('1');
	}
	$auxiliary = $propsById[$pids];
	$auxiliaryEffect = isset($auxiliary['effect']) ? trim(strval($auxiliary['effect'])) : '';
	if (!preg_match('/^(suc:[0-9]+|100suc:[0-9]+,[0-9]+|baodi:-?[0-9]+|baodeng:[0-9]+)$/', $auxiliaryEffect))
	{
		die('1');
	}
}

$timerKey = 'tgtimes'.$uid;
$now = time();
$lastTime = isset($_SESSION[$timerKey]) ? intval($_SESSION[$timerKey]) : 0;
if ($lastTime > 0 && $now - $lastTime < 5) die('11');
$_SESSION[$timerKey] = $now;

$zbStrengthItemId = $pid;
if (lockItem($zbStrengthItemId) === false) die('已经在处理了！');
$zbStrengthItemLocked = true;
if (!is_array(getLock($uid))) zbStrengthFail('11');
$zbStrengthUserLocked = true;
$zbStrengthTransactionActive = true;

$db = $_pm['mysql'];
$player = $db->getOneRecord('SELECT money FROM player WHERE id='.$uid.' FOR UPDATE');
$target = $db->getOneRecord(
	'SELECT id,pid,plus_tms_eft AS plus_tmes_eft FROM userbag WHERE id='.$bagId.' AND uid='.$uid.
	' AND pid='.$pid.' AND sums>0 AND zbing=0 AND (cantrade IS NULL OR cantrade<>3) FOR UPDATE'
);
$requiredItem = $db->getOneRecord(
	'SELECT id FROM userbag WHERE uid='.$uid.' AND pid='.$requiredPid.
	' AND sums>0 AND zbing=0 AND (cantrade IS NULL OR cantrade<>3) ORDER BY id LIMIT 1 FOR UPDATE'
);
if (!is_array($player) || !is_array($target)) zbStrengthFail('0');
if (!is_array($requiredItem)) zbStrengthFail('4');

$auxiliaryItem = false;
if ($pids > 0)
{
	$auxiliaryItem = $db->getOneRecord(
		'SELECT id FROM userbag WHERE uid='.$uid.' AND pid='.$pids.
		' AND sums>0 AND zbing=0 AND (cantrade IS NULL OR cantrade<>3) ORDER BY id LIMIT 1 FOR UPDATE'
	);
	if (!is_array($auxiliaryItem)) zbStrengthFail('1');
}

$currentIndex = -1;
$currentValue = isset($target['plus_tmes_eft']) ? trim(strval($target['plus_tmes_eft'])) : '';
if ($currentValue !== '' && $currentValue !== '0')
{
	$currentParts = explode(',', $currentValue);
	if (count($currentParts) < 2 || !preg_match('/^[0-9]+$/', trim($currentParts[0]))) zbStrengthFail('0');
	$currentIndex = intval($currentParts[0]);
	if ($currentIndex < 0 || $currentIndex >= $maxStrengthLevel) zbStrengthFail('0');
}
$attemptIndex = $currentIndex + 1;
if ($attemptIndex >= $maxStrengthLevel) zbStrengthFail('15');
$attemptPlusValue = trim($plusValues[$attemptIndex]);
if (!preg_match('/^-?[0-9]+(?:\.[0-9]+)?%?(?:rn)?$/', $attemptPlusValue)) zbStrengthFail('0');
$attemptPlusValue = preg_replace('/rn$/', '', $attemptPlusValue);

$strengthConfig = explode(',', $harden[$attemptIndex]);
if (count($strengthConfig) < 2 || !is_numeric($strengthConfig[0]) || !is_numeric($strengthConfig[1])) zbStrengthFail('0');
$successThreshold = intval($strengthConfig[0]);
$moneyCost = intval($strengthConfig[1]);
if ($successThreshold < 0 || $successThreshold > 10 || $moneyCost < 0) zbStrengthFail('0');

$protectMode = '';
if ($pids > 0)
{
	$effectParts = explode(':', $auxiliaryEffect, 2);
	if ($effectParts[0] == 'suc')
	{
		$successThreshold += intval($effectParts[1]);
	}
	else if ($effectParts[0] == '100suc')
	{
		$effectValues = explode(',', $effectParts[1]);
		$guaranteedMax = isset($effectValues[1]) ? intval($effectValues[1]) : 0;
		if ($attemptIndex < $guaranteedMax) $successThreshold = 10;
	}
	else if ($effectParts[0] == 'baodi')
	{
		$protectMode = 'baodi';
	}
	else if ($effectParts[0] == 'baodeng')
	{
		$protectMode = 'baodeng';
	}
}

$success = rand(1, 10) <= $successThreshold;
$equipmentName = isset($equipment['name']) ? $equipment['name'] : '';
$log = '装备包裹ID：'.$bagId.',名字：'.$equipmentName.'-强化等级：'.($currentIndex < 0 ? 0 : $currentValue);

if ($success)
{
	if (intval($player['money']) < $moneyCost) zbStrengthFail('3');
	if ($moneyCost > 0 && (!$db->query(
		'UPDATE player SET money=money-'.$moneyCost.' WHERE id='.$uid.' AND money>='.$moneyCost
	) || mysql_affected_rows($db->getConn()) != 1))
	{
		zbStrengthFail('3');
	}
	$newStrength = $attemptIndex.','.$attemptPlusValue;
	if (!$db->query('UPDATE userbag SET plus_tms_eft='.$db->quote($newStrength).
		' WHERE id='.$bagId.' AND uid='.$uid.' AND pid='.$pid.' AND sums>0 AND zbing=0') ||
		mysql_affected_rows($db->getConn()) != 1)
	{
		zbStrengthFail('0');
	}
	$resultCode = 10;
}
else
{
	if ($protectMode == 'baodi')
	{
		if ($currentIndex >= 1) $newStrength = ($currentIndex - 1).','.$plusValues[$currentIndex - 1];
		else $newStrength = '';
		if (!$db->query('UPDATE userbag SET plus_tms_eft='.$db->quote($newStrength).
			' WHERE id='.$bagId.' AND uid='.$uid.' AND pid='.$pid.' AND sums>0 AND zbing=0'))
		{
			zbStrengthFail('0');
		}
	}
	else if ($protectMode != 'baodeng')
	{
		if (!$db->query('DELETE FROM userbag WHERE id='.$bagId.' AND uid='.$uid.
			' AND pid='.$pid.' AND sums>0 AND zbing=0') || mysql_affected_rows($db->getConn()) != 1)
		{
			zbStrengthFail('0');
		}
	}
	if (!$db->query('INSERT INTO gamelog(ptime,seller,buyer,pnote,vary) VALUES('.
		time().','.$uid.','.$uid.','.$db->quote($log).',5)') || mysql_affected_rows($db->getConn()) != 1)
	{
		zbStrengthFail('0');
	}
	$zbStrengthPendingLogId = intval($db->last_id());
	if ($zbStrengthPendingLogId < 1) zbStrengthFail('0');
	$resultCode = 2;
}

if ($pids > 0)
{
	if (!$db->query('UPDATE userbag SET sums=sums-1 WHERE id='.intval($auxiliaryItem['id']).
		' AND uid='.$uid.' AND pid='.$pids.' AND sums>0 AND zbing=0') ||
		mysql_affected_rows($db->getConn()) != 1)
	{
		zbStrengthFail('您没有相应的物品！');
	}
	if (!$db->query('DELETE FROM userbag WHERE id='.intval($auxiliaryItem['id']).
		' AND uid='.$uid.' AND pid='.$pids.
		' AND sums<=0 AND bsum<=0 AND psum<=0 AND pyb=0 AND zbing=0'.
		' AND (cantrade IS NULL OR cantrade<>3)'))
	{
		zbStrengthFail('0');
	}
}
if (!$db->query('UPDATE userbag SET sums=sums-1 WHERE id='.intval($requiredItem['id']).
	' AND uid='.$uid.' AND pid='.$requiredPid.' AND sums>0 AND zbing=0') ||
	mysql_affected_rows($db->getConn()) != 1)
{
	zbStrengthFail('您没有相应的物品！');
}
if (!$db->query('DELETE FROM userbag WHERE id='.intval($requiredItem['id']).
	' AND uid='.$uid.' AND pid='.$requiredPid.
	' AND sums<=0 AND bsum<=0 AND psum<=0 AND pyb=0 AND zbing=0'.
	' AND (cantrade IS NULL OR cantrade<>3)'))
{
	zbStrengthFail('0');
}

if (!$db->query('COMMIT')) zbStrengthFail('0');
$zbStrengthTransactionActive = false;
$zbStrengthCommitted = true;
$_pm['mem']->del(MEM_USER_KEY);
$_pm['mem']->del(MEM_USERBAG_KEY);
$_SESSION['pid'.$uid] = '';
$_SESSION['pids'.$uid] = '';
$_SESSION['bid'.$uid] = '';
zbStrengthRelease();
echo $resultCode;
?>
