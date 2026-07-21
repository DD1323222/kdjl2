<?php
/**
 * Prestige consumable shop purchase.
 */
require_once('../config/config.game.php');

secStart($_pm['mem']);

$prestigeBuyTransactionActive = false;

function prestigeBuyCleanup()
{
	global $_pm, $prestigeBuyTransactionActive;
	if ($prestigeBuyTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$prestigeBuyTransactionActive = false;
	}
}

function prestigeBuyFinish($code, $invalidate=false)
{
	global $_pm;
	prestigeBuyCleanup();
	if ($invalidate)
	{
		if (defined('MEM_USER_KEY')) $_pm['mem']->del(MEM_USER_KEY);
		if (defined('MEM_USERBAG_KEY')) $_pm['mem']->del(MEM_USERBAG_KEY);
	}
	$_pm['mem']->memClose();
	die(strval($code));
}

register_shutdown_function('prestigeBuyCleanup');

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
$bid = (isset($_REQUEST['bid']) && !is_array($_REQUEST['bid'])) ? intval($_REQUEST['bid']) : 0;
$quantity = (isset($_REQUEST['n']) && !is_array($_REQUEST['n'])) ? intval($_REQUEST['n']) : 0;
if ($uid < 1 || $bid < 1 || $quantity < 1 || $quantity > 10 ||
	$_pm['user']->check(array('int'=>array($bid, $quantity))) === false)
{
	prestigeBuyFinish(2);
}

$db = $_pm['mysql'];
if (!$db->query('START TRANSACTION')) prestigeBuyFinish(3);
$prestigeBuyTransactionActive = true;

$item = $db->getOneRecord(
	'SELECT id,vary,varyname,sell,prestige FROM props WHERE id='.$bid.' FOR UPDATE'
);
$player = $db->getOneRecord(
	'SELECT prestige,maxbag FROM player WHERE id='.$uid.' FOR UPDATE'
);
$bags = $db->getRecords(
	'SELECT id,pid,vary,sums,zbing,cantrade FROM userbag WHERE uid='.$uid.' ORDER BY id FOR UPDATE'
);
if (!is_array($item) || !is_array($player) || !is_array($bags) ||
	intval($item['sell']) < 0 || intval($item['prestige']) <= 0 ||
	intval($item['varyname']) == 9 ||
	!in_array(intval($item['vary']), array(1, 2), true))
{
	prestigeBuyFinish(3);
}

$usedSlots = 0;
$stackId = 0;
foreach ($bags as $bag)
{
	if (!is_array($bag)) continue;
	if (intval($bag['sums']) > 0 && intval($bag['zbing']) == 0) $usedSlots++;
	if ($stackId == 0 && intval($item['vary']) == 1 && intval($bag['pid']) == $bid &&
		intval($bag['vary']) == 1 && intval($bag['sums']) > 0 && intval($bag['zbing']) == 0 &&
		(!isset($bag['cantrade']) || intval($bag['cantrade']) == 0))
	{
		$stackId = intval($bag['id']);
	}
}
$neededSlots = intval($item['vary']) == 2 ? $quantity : ($stackId > 0 ? 0 : 1);
if (intval($player['maxbag']) < 1 || $usedSlots + $neededSlots > intval($player['maxbag']))
{
	prestigeBuyFinish(4);
}

$price = kdjlSafePositiveProduct($item['prestige'], $quantity);
if ($price === false) prestigeBuyFinish(3);
if ($price > intval($player['prestige'])) prestigeBuyFinish(10);
if (!$db->query('UPDATE player SET prestige=prestige-'.$price.
	' WHERE id='.$uid.' AND prestige>='.$price) ||
	mysql_affected_rows($db->getConn()) != 1)
{
	prestigeBuyFinish(10);
}

$saved = true;
if (intval($item['vary']) == 2)
{
	for ($i=0; $i<$quantity; $i++)
	{
		$saved = $db->query(
			'INSERT INTO userbag(uid,pid,sell,vary,sums,stime) VALUES('.
			$uid.','.$bid.','.intval($item['sell']).',2,1,'.time().')'
		);
		if (!$saved || mysql_affected_rows($db->getConn()) != 1) break;
	}
}
else if ($stackId > 0)
{
	$saved = $db->query(
		'UPDATE userbag SET sums=COALESCE(sums,0)+'.$quantity.
		' WHERE id='.$stackId.' AND uid='.$uid.' AND pid='.$bid.
		' AND vary=1 AND COALESCE(sums,0)<=2147483647-'.$quantity.
		' AND zbing=0 AND (cantrade IS NULL OR cantrade=0)'
	);
}
else
{
	$saved = $db->query(
		'INSERT INTO userbag(uid,pid,sell,vary,sums,stime) VALUES('.
		$uid.','.$bid.','.intval($item['sell']).',1,'.$quantity.','.time().')'
	);
}
if (!$saved || mysql_affected_rows($db->getConn()) != 1) prestigeBuyFinish(3);
if (!$db->query('COMMIT')) prestigeBuyFinish(3);
$prestigeBuyTransactionActive = false;
prestigeBuyFinish(0, true);
?>
