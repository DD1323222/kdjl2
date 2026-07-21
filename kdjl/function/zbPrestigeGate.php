<?php
/**
 * Prestige equipment shop purchase with an atomic balance and bag update.
 */
error_reporting(E_ALL&~E_NOTICE);
require_once('../config/config.game.php');

secStart($_pm['mem']);

$prestigeShopItemId = 0;
$prestigeShopItemLocked = false;
$prestigeShopTransactionActive = false;

function prestigeShopCleanup()
{
	global $_pm, $prestigeShopItemId, $prestigeShopItemLocked, $prestigeShopTransactionActive;
	if ($prestigeShopTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$prestigeShopTransactionActive = false;
	}
	if ($prestigeShopItemLocked)
	{
		unLockItem($prestigeShopItemId);
		$prestigeShopItemLocked = false;
	}
}

function prestigeShopFinish($code, $invalidate=false)
{
	global $_pm;
	if ($invalidate)
	{
		$_pm['mem']->del(MEM_USER_KEY);
		$_pm['mem']->del(MEM_USERBAG_KEY);
	}
	prestigeShopCleanup();
	$_pm['mem']->memClose();
	die(strval($code));
}

register_shutdown_function('prestigeShopCleanup');

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
$prestigeShopItemId = (isset($_REQUEST['bid']) && !is_array($_REQUEST['bid'])) ? intval($_REQUEST['bid']) : 0;
$quantity = (isset($_REQUEST['n']) && !is_array($_REQUEST['n'])) ? intval($_REQUEST['n']) : 0;
if ($uid < 1 || $prestigeShopItemId < 1 || $quantity != 1 ||
	$_pm['user']->check(array('int'=>array($prestigeShopItemId, $quantity))) === false)
{
	die('2');
}

if (lockItem($prestigeShopItemId) === false) die('已经在处理了！');
$prestigeShopItemLocked = true;

$db = $_pm['mysql'];
if (!$db->query('START TRANSACTION')) prestigeShopFinish(3);
$prestigeShopTransactionActive = true;

$item = $db->getOneRecord(
	'SELECT id,prestige,sell,vary FROM props WHERE id='.$prestigeShopItemId.
	' AND prestige>0 AND sell>0 AND varyname=9 AND yb=0 AND buy=0 FOR UPDATE'
);
$player = $db->getOneRecord('SELECT prestige,maxbag FROM player WHERE id='.$uid.' FOR UPDATE');
$bags = $db->getRecords(
	'SELECT id,pid,vary,sums,zbing,cantrade FROM userbag WHERE uid='.$uid.' FOR UPDATE'
);
if (!is_array($item) || !is_array($player) || !is_array($bags) ||
	!in_array(intval($item['vary']), array(1, 2), true))
{
	prestigeShopFinish(3);
}

$usedSlots = 0;
$stackId = 0;
foreach ($bags as $bag)
{
	if (!is_array($bag)) continue;
	if (intval($bag['sums']) > 0 && intval($bag['zbing']) == 0) $usedSlots++;
	if ($stackId == 0 && intval($item['vary']) == 1 && intval($bag['pid']) == $prestigeShopItemId &&
		intval($bag['vary']) == 1 && intval($bag['sums']) > 0 && intval($bag['zbing']) == 0 &&
		(!isset($bag['cantrade']) || intval($bag['cantrade']) == 0))
	{
		$stackId = intval($bag['id']);
	}
}
$neededSlots = intval($item['vary']) == 2 ? 1 : ($stackId > 0 ? 0 : 1);
if ($usedSlots + $neededSlots > intval($player['maxbag'])) prestigeShopFinish(4);

$price = kdjlSafePositiveProduct($item['prestige'], $quantity);
if ($price === false) prestigeShopFinish(3);
if ($price > intval($player['prestige'])) prestigeShopFinish(10);
if (!$db->query('UPDATE player SET prestige=prestige-'.$price.' WHERE id='.$uid.' AND prestige>='.$price) ||
	mysql_affected_rows($db->getConn()) != 1)
{
	prestigeShopFinish(10);
}

if (intval($item['vary']) == 2)
{
	$saved = $db->query(
		'INSERT INTO userbag(uid,pid,sell,vary,sums,stime,cantrade) VALUES('.
		$uid.','.$prestigeShopItemId.','.intval($item['sell']).',2,1,'.time().',0)'
	);
}
else if ($stackId > 0)
{
	$saved = $db->query(
		'UPDATE userbag SET sums=COALESCE(sums,0)+1 WHERE uid='.$uid.' AND id='.$stackId.
		' AND pid='.$prestigeShopItemId.' AND vary=1 AND COALESCE(sums,0)<2147483647'.
		' AND zbing=0 AND (cantrade IS NULL OR cantrade=0)'
	);
}
else
{
	$saved = $db->query(
		'INSERT INTO userbag(uid,pid,sell,vary,sums,stime,cantrade) VALUES('.
		$uid.','.$prestigeShopItemId.','.intval($item['sell']).',1,1,'.time().',0)'
	);
}
if (!$saved || mysql_affected_rows($db->getConn()) != 1) prestigeShopFinish(3);

if (!$db->query('COMMIT')) prestigeShopFinish(3);
$prestigeShopTransactionActive = false;
prestigeShopFinish(0, true);
?>
