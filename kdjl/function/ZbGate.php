<?php
/**
 * Equipment shop purchase with a locked price, balance and bag snapshot.
 */
error_reporting(E_ALL&~E_NOTICE);
require_once('../config/config.game.php');

secStart($_pm['mem']);

$zbShopItemId = 0;
$zbShopItemLocked = false;
$zbShopTransactionActive = false;

function zbShopCleanup()
{
	global $_pm, $zbShopItemId, $zbShopItemLocked, $zbShopTransactionActive;
	if ($zbShopTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$zbShopTransactionActive = false;
	}
	if ($zbShopItemLocked)
	{
		unLockItem($zbShopItemId);
		$zbShopItemLocked = false;
	}
}

function zbShopFinish($code, $invalidate=false)
{
	global $_pm;
	if ($invalidate)
	{
		$_pm['mem']->del(MEM_USER_KEY);
		$_pm['mem']->del(MEM_USERBAG_KEY);
	}
	zbShopCleanup();
	$_pm['mem']->memClose();
	die(strval($code));
}

register_shutdown_function('zbShopCleanup');

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
$zbShopItemId = (isset($_REQUEST['bid']) && !is_array($_REQUEST['bid'])) ? intval($_REQUEST['bid']) : 0;
$quantity = (isset($_REQUEST['n']) && !is_array($_REQUEST['n'])) ? intval($_REQUEST['n']) : 0;
if ($uid < 1 || $zbShopItemId < 1 || $quantity != 1 ||
	$_pm['user']->check(array('int'=>array($zbShopItemId, $quantity))) === false)
{
	die('2');
}

if (lockItem($zbShopItemId) === false) die('已经在处理了！');
$zbShopItemLocked = true;

$db = $_pm['mysql'];
if (!$db->query('START TRANSACTION')) zbShopFinish(3);
$zbShopTransactionActive = true;

$item = $db->getOneRecord(
	'SELECT id,buy,sell,vary FROM props WHERE id='.$zbShopItemId.
	' AND buy>0 AND yb=0 AND varyname=9 AND prestige=0 FOR UPDATE'
);
$player = $db->getOneRecord('SELECT money,maxbag FROM player WHERE id='.$uid.' FOR UPDATE');
$bags = $db->getRecords(
	'SELECT id,pid,vary,sums,zbing,cantrade FROM userbag WHERE uid='.$uid.' FOR UPDATE'
);
if (!is_array($item) || !is_array($player) || !is_array($bags) ||
	!in_array(intval($item['vary']), array(1, 2), true) || intval($item['sell']) < 0)
{
	zbShopFinish(3);
}

$usedSlots = 0;
$stackId = 0;
foreach ($bags as $bag)
{
	if (!is_array($bag)) continue;
	if (intval($bag['sums']) > 0 && intval($bag['zbing']) == 0) $usedSlots++;
	if ($stackId == 0 && intval($item['vary']) == 1 && intval($bag['pid']) == $zbShopItemId &&
		intval($bag['vary']) == 1 && intval($bag['sums']) > 0 && intval($bag['zbing']) == 0 &&
		(!isset($bag['cantrade']) || intval($bag['cantrade']) == 0))
	{
		$stackId = intval($bag['id']);
	}
}
$neededSlots = intval($item['vary']) == 2 ? 1 : ($stackId > 0 ? 0 : 1);
if ($usedSlots + $neededSlots > intval($player['maxbag'])) zbShopFinish(4);

$price = kdjlSafePositiveProduct($item['buy'], $quantity);
if ($price === false) zbShopFinish(3);
if ($price > intval($player['money'])) zbShopFinish(10);
if (!$db->query('UPDATE player SET money=money-'.$price.' WHERE id='.$uid.' AND money>='.$price) ||
	mysql_affected_rows($db->getConn()) != 1)
{
	zbShopFinish(10);
}

if (intval($item['vary']) == 2)
{
	$saved = $db->query(
		'INSERT INTO userbag(uid,pid,sell,vary,sums,stime,cantrade) VALUES('.
		$uid.','.$zbShopItemId.','.intval($item['sell']).',2,1,'.time().',0)'
	);
}
else if ($stackId > 0)
{
	$saved = $db->query(
		'UPDATE userbag SET sums=COALESCE(sums,0)+1 WHERE uid='.$uid.' AND id='.$stackId.
		' AND pid='.$zbShopItemId.' AND vary=1 AND COALESCE(sums,0)<2147483647'.
		' AND zbing=0 AND (cantrade IS NULL OR cantrade=0)'
	);
}
else
{
	$saved = $db->query(
		'INSERT INTO userbag(uid,pid,sell,vary,sums,stime,cantrade) VALUES('.
		$uid.','.$zbShopItemId.','.intval($item['sell']).',1,1,'.time().',0)'
	);
}
if (!$saved || mysql_affected_rows($db->getConn()) != 1) zbShopFinish(3);

if (!$db->query('COMMIT')) zbShopFinish(3);
$zbShopTransactionActive = false;
zbShopFinish(0, true);
?>
