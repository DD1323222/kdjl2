<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.05.22
*@Usage: 任务奖励
*@Note: none
*/


require_once('../config/config.game.php');
secStart($_pm['mem']);

$user	 = $_pm['user']->getUserById($_SESSION['id']);
$userBag = $_pm['user']->getUserBagById($_SESSION['id']);
if (!is_array($userBag)) die('您没有打开该地图的钥匙!');

$n = intval($_REQUEST['open']);
if ($_pm['user']->check(array('int' => $n)) === true )
{
	$item = split(',', $user['openmap']);
	if (in_array($n, $item)) die('该地图已经打开了!');
	
	$patter = 'openmap:' . $n;
	$valid = false;
	foreach ($userBag as $k => $v)
	{
		if ($v['effect'] == $patter)
		{
			$pid	= $v['id'];
			$psum = $v['sums'];
			if(empty($psum))
			{
				die("您的包裹中没有打开该地图的钥匙！");
			}
			$valid	= true;
			break;
		}
	}

	if ($valid === true)
	{
		// del a props for current map.
		$_pm['mysql']->query("UPDATE userbag SET sums = abs(sums-1)
							   WHERE id={$pid} and uid={$_SESSION['id']} and sums > 0
							 ");
		$user['openmap'] .= ','.$n;

		$_pm['mysql']->query("UPDATE player 
								 SET openmap='{$user['openmap']}' 
								WHERE id={$_SESSION['id']}");

		echo "地图打开成功!";

		//$_pm['user']->updateMemUser($_SESSION['id']);
		//$_pm['user']->updateMemUserbag($_SESSION['id']);
	}
	else echo "地图打开失败，请确认包裹中有打开该地图的钥匙!";				
}
?>