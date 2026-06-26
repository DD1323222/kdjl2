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
$user		= $_pm['user']->getUserById($_SESSION['id']);
$bags		= $_pm['user']->getUserBagById($_SESSION['id']);
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

$wp = false;
foreach ($bags as $k => $v)
{
	if ($v['uid'] == $_SESSION['id'] && $v['id'] == $bid) 
	{
		$wp = $v; 
		break;
	}
}

if (!is_array($wp))
{
	unLockItem($bid);
	die('3');
}
else if(!empty($wp['zbing']))
{
	unLockItem($bid);
	die("10");//装备在身上的不能卖出。
}
else
{
	if ($n > $wp['sums']) {
		unLockItem($bid);
		die('10');
	}

	if ($wp['vary'] == 2)	//	Can't repeat!
	{
		$money = $wp['sell'];
		$_pm['mysql']->query("DELETE FROM userbag
					 WHERE uid={$_SESSION['id']} and id={$bid}
				  ");
	}
	else
	{	
		$money = $wp['sell']*$n;
		$_pm['mysql']->query("UPDATE userbag
					   SET sums=sums-{$n}
					 WHERE uid={$_SESSION['id']} and id={$bid} and sums>={$n}
				  ");
	}
	$user['money'] += $money;

	$_pm['mysql']->query("UPDATE player 
				   SET money={$user['money']}
				 WHERE id={$_SESSION['id']} and {$user['money']} > 0
			  ");
}
//$_pm['user']->updateMemUser($_SESSION['id']);
//$_pm['user']->updateMemUserbag($_SESSION['id']);
$_pm['mem']->memClose();

echo $err;
unLockItem($bid);
?>