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
$uid = intval($_SESSION['id']);
if($uid < 1) die('1');

del_bag_expire();
$db = &$_pm['mysql'];
$db->query('START TRANSACTION');
$items = $db->getRecords("SELECT b.id,b.sell
                          FROM userbag b
                          INNER JOIN props p ON p.id=b.pid
                         WHERE b.uid={$uid}
                           and b.vary=2
                           and b.sums>0
                           and b.zbing=0
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
	$ids[] = intval($item['id']);
	$money += max(0, intval($item['sell']));
}
$idList = implode(',', $ids);
$db->query("DELETE FROM userbag
             WHERE uid={$uid}
               and id IN ({$idList})
               and vary=2
               and sums>0
               and zbing=0");

if(mysql_affected_rows($db->getConn()) != count($ids))
{
	$db->query('ROLLBACK');
	$_pm['mem']->memClose();
	die('3');
}

if($money > 0)
{
	$db->query("UPDATE player SET money=money+{$money} WHERE id={$uid}");
	if(mysql_affected_rows($db->getConn()) != 1)
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

$_pm['mem']->memClose();
echo $err;
?>
