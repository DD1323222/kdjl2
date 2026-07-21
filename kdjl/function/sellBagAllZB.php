<?php
/**
* 一键卖背包装备
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.02
*@Update Date: 2008.05.22
*@Usage:User Bag sell
*/
require_once('../config/config.game.php');

secStart($_pm['mem']);

$err = 0;
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('1');

del_bag_expire();
$db = &$_pm['mysql'];
if(!$db->query('START TRANSACTION'))
{
	$_pm['mem']->memClose();
	die('3');
}
$items = $db->getRecords("SELECT b.id,b.sell,b.sums
                          FROM userbag b
                          INNER JOIN props p ON p.id=b.pid
                         WHERE b.uid={$uid}
                           and b.vary=2
                           and b.sums>0
                           and b.sell>=0
                           and b.zbing=0
                           and (b.cantrade IS NULL OR b.cantrade<>3)
                           and p.varyname=9
                          FOR UPDATE");

if(!is_array($items) || count($items) == 0)
{
	$db->query('ROLLBACK');
	$_pm['mem']->memClose();
	echo $err;
	exit;
}

$ids = array();
$money = 0;
foreach($items as $item)
{
	if(!is_array($item)) continue;
	$ids[] = intval($item['id']);
	$itemSell = max(0, intval($item['sell']));
	$itemSums = max(1, intval($item['sums']));
	$itemMoney = $itemSell > 0 ? kdjlSafePositiveProduct($itemSell, $itemSums) : 0;
	if($itemMoney === false || $money > 2147483647 - $itemMoney)
	{
		$db->query('ROLLBACK');
		$_pm['mem']->memClose();
		die('3');
	}
	$money += $itemMoney;
}
$ids = array_unique($ids);
if(count($ids) == 0)
{
	$db->query('ROLLBACK');
	$_pm['mem']->memClose();
	echo $err;
	exit;
}
$idList = implode(',', $ids);
$itemsSold = $db->query("DELETE FROM userbag
             WHERE uid={$uid}
               and id IN ({$idList})
               and vary=2
               and sums>0
               and sell>=0
               and zbing=0
               and (cantrade IS NULL OR cantrade<>3)");

if(!$itemsSold || mysql_affected_rows($db->getConn()) != count($ids))
{
	$db->query('ROLLBACK');
	$_pm['mem']->memClose();
	die('3');
}

if($money > 0)
{
	if(!$db->query("UPDATE player SET money=LEAST(COALESCE(money,0)+{$money},1000000000) WHERE id={$uid}") ||
		mysql_affected_rows($db->getConn()) != 1)
	{
		$db->query('ROLLBACK');
		$_pm['mem']->memClose();
		die('3');
	}
}

if(!$db->query('COMMIT'))
{
	$db->query('ROLLBACK');
	$_pm['mem']->memClose();
	die('3');
}

$_pm['mem']->del(MEM_USER_KEY);
$_pm['mem']->del(MEM_USERBAG_KEY);
$_pm['mem']->memClose();
echo $err;
?>
