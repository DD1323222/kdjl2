<?php
/**
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
del_bag_expire();
// Check bid.
$bid = intval($_REQUEST['bid']); // table: userbag -> id
$n	 = intval($_REQUEST['n']);
if(lockItem($bid) === false)
{
	die('已经在处理了！');
}
if($n <= 0)
{
	unLockItem($bid);
	die('2');
}

if ($_pm['user']->check(array('int' => $bid, 'int' => $n)) === FALSE) {
	unLockItem($bid);
	die('2');
}

$uid = intval($_SESSION['id']);
$db = &$_pm['mysql'];
$db->query('START TRANSACTION');
$wp = $db->getOneRecord("SELECT id,sell,vary,sums,zbing FROM userbag WHERE uid={$uid} and id={$bid} FOR UPDATE");

if (!is_array($wp))
{
	$db->query('ROLLBACK');
	unLockItem($bid);
	die('3');
}
else if(!empty($wp['zbing']))
{
	$db->query('ROLLBACK');
	unLockItem($bid);
	die("10");//装备在身上的不能卖出。
}
else
{
	if ($n > $wp['sums']) {
		$db->query('ROLLBACK');
		unLockItem($bid);
		die('10');
	}

	if ($wp['vary'] == 2)	//	Can't repeat!
	{
		if($n != 1)
		{
			$db->query('ROLLBACK');
			unLockItem($bid);
			die('2');
		}
		$money = $wp['sell'];
		$db->query("DELETE FROM userbag
					 WHERE uid={$uid} and id={$bid} and sums>=1 and zbing=0
				  ");
	}
	else
	{	
		$money = $wp['sell']*$n;
		$db->query("UPDATE userbag
					   SET sums=sums-{$n}
					 WHERE uid={$uid} and id={$bid} and sums>={$n} and zbing=0
				  ");
	}
	if(mysql_affected_rows($db->getConn()) != 1)
	{
		$db->query('ROLLBACK');
		unLockItem($bid);
		die('3');
	}
	if($money > 0)
	{
		$db->query("UPDATE player SET money=money+{$money} WHERE id={$uid}");
		if(mysql_affected_rows($db->getConn()) != 1)
		{
			$db->query('ROLLBACK');
			unLockItem($bid);
			die('3');
		}
	}
	if(!$db->query('COMMIT'))
	{
		$db->query('ROLLBACK');
		unLockItem($bid);
		die('3');
	}
}
//$_pm['user']->updateMemUser($_SESSION['id']);
//$_pm['user']->updateMemUserbag($_SESSION['id']);
$_pm['mem']->memClose();

echo $err;
unLockItem($bid);
?>
