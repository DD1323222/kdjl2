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

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid <= 0)
{
	die('10');
}

$err = 0;
del_bag_expire();
// Check bid.
$bid = (isset($_REQUEST['bid']) && !is_array($_REQUEST['bid'])) ? intval($_REQUEST['bid']) : 0; // table: userbag -> id
$n	 = (isset($_REQUEST['n']) && !is_array($_REQUEST['n'])) ? intval($_REQUEST['n']) : 0;
if($bid <= 0 || $n <= 0)
{
	die('2');
}
if(lockItem($bid) === false)
{
	die('已经在处理了！');
}
if($n <= 0)
{
	unLockItem($bid);
	die('2');
}

if ($_pm['user']->check(array('int' => array($bid, $n))) === FALSE) {
	unLockItem($bid);
	die('2');
}

$db = &$_pm['mysql'];
if(!$db->query('START TRANSACTION'))
{
	unLockItem($bid);
	die('3');
}
$wp = $db->getOneRecord("SELECT id,sell,vary,sums,zbing,cantrade FROM userbag WHERE uid={$uid} and id={$bid} FOR UPDATE");

if (!is_array($wp))
{
	$db->query('ROLLBACK');
	unLockItem($bid);
	die('3');
}
else if(intval($wp['sell']) < 0 || (intval($wp['vary']) != 1 && intval($wp['vary']) != 2))
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
else if(isset($wp['cantrade']) && intval($wp['cantrade']) == 3)
{
	$db->query('ROLLBACK');
	unLockItem($bid);
	die('10');
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
		$money = intval($wp['sell']);
		$itemSold = $db->query("DELETE FROM userbag
					 WHERE uid={$uid} and id={$bid} and sums>=1 and zbing=0 and (cantrade IS NULL OR cantrade<>3)
				  ");
	}
	else
	{
		$money = kdjlSafePositiveProduct($wp['sell'], $n);
		if($money === false)
		{
			$db->query('ROLLBACK');
			unLockItem($bid);
			die('3');
		}
		$itemSold = $db->query("UPDATE userbag
					   SET sums=sums-{$n}
					 WHERE uid={$uid} and id={$bid} and sums>={$n} and zbing=0 and (cantrade IS NULL OR cantrade<>3)
				  ");
	}
	if(!$itemSold || mysql_affected_rows($db->getConn()) != 1)
	{
		$db->query('ROLLBACK');
		unLockItem($bid);
		die('3');
	}
	if ($wp['vary'] != 2 && !$db->query("DELETE FROM userbag WHERE uid={$uid} and id={$bid} and sums<=0 and bsum<=0 and psum<=0 and pyb=0 and zbing=0 and (cantrade IS NULL OR cantrade<>3)"))
	{
		$db->query('ROLLBACK');
		unLockItem($bid);
		die('3');
	}
	if($money > 0)
	{
		if(!$db->query("UPDATE player SET money=LEAST(COALESCE(money,0)+{$money},1000000000) WHERE id={$uid}") ||
			mysql_affected_rows($db->getConn()) != 1)
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
$_pm['mem']->del(MEM_USER_KEY);
$_pm['mem']->del(MEM_USERBAG_KEY);
unLockItem($bid);
$_pm['mem']->memClose();

echo $err;
?>
