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

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid <= 0) die('您没有打开该地图的钥匙!');
del_bag_expire();
$user	 = $_pm['user']->getUserById($uid);
$userBag = $_pm['user']->getUserBagById($uid);
if (!is_array($user)) die('您没有打开该地图的钥匙!');
if (!is_array($userBag)) die('您没有打开该地图的钥匙!');

$n = (isset($_REQUEST['open']) && !is_array($_REQUEST['open'])) ? intval($_REQUEST['open']) : 0;
if ($_pm['user']->check(array('int' => $n)) === true )
{
	$mapExists = $_pm['mysql']->getOneRecord("SELECT id FROM map WHERE id={$n}");
	if(!is_array($mapExists)) die('地图打开失败，请确认地图数据存在!');
	$item = explode(',', isset($user['openmap']) ? $user['openmap'] : '');
	if (in_array($n, $item)) die('该地图已经打开了!');

	$patter = 'openmap:' . $n;
	$valid = false;
	foreach ($userBag as $k => $v)
	{
		if(!is_array($v)) continue;
		$vDefaults = array('id'=>0, 'effect'=>'', 'sums'=>0, 'zbing'=>0, 'cantrade'=>0);
		foreach($vDefaults as $vDefaultKey => $vDefaultValue)
		{
			if(!isset($v[$vDefaultKey])) $v[$vDefaultKey] = $vDefaultValue;
		}
		if ($v['effect'] == $patter && intval($v['zbing']) == 0 && intval($v['sums']) > 0 && intval($v['cantrade']) != 3)
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
		if(!$_pm['mysql']->query('START TRANSACTION')){
			die('地图打开失败，请稍候重试！');
		}
		$mapRow = $_pm['mysql']->getOneRecord("SELECT openmap FROM player WHERE id={$uid} FOR UPDATE");
		if(!is_array($mapRow)){
			$_pm['mysql']->query('ROLLBACK');
			die('地图打开失败，请稍候重试！');
		}
		$currentOpenmap = isset($mapRow['openmap']) ? $mapRow['openmap'] : '';
		$item = explode(',', $currentOpenmap);
		if (in_array($n, $item)){
			$_pm['mysql']->query('ROLLBACK');
			die('该地图已经打开了!');
		}
		// del a props for current map.
		$itemUsed = $_pm['mysql']->query("UPDATE userbag SET sums = sums-1
							   WHERE id={$pid} and uid={$uid} and sums > 0 and zbing=0
							   and (cantrade IS NULL OR cantrade<>3)
							 ");
		if(!$itemUsed || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
		{
            $_pm['mysql']->query('ROLLBACK');
			die('您没有打开该地图的钥匙!');
		}
		if(!$_pm['mysql']->query("DELETE FROM userbag WHERE id={$pid} and uid={$uid} and sums<=0 and bsum<=0 and psum<=0 and pyb=0 and zbing=0 and (cantrade IS NULL OR cantrade<>3)"))
		{
            $_pm['mysql']->query('ROLLBACK');
			die('地图打开失败，请稍候重试！');
		}
		$user['openmap'] = ($currentOpenmap === '' ? $n : $currentOpenmap.','.$n);
		$openmapSql = $_pm['mysql']->escape($user['openmap']);

        if(!$_pm['mysql']->query("UPDATE player
                                 SET openmap='{$openmapSql}'
                                WHERE id={$uid}") || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
            $_pm['mysql']->query('ROLLBACK');
            die('地图打开失败，请稍候重试！');
        }
        if(!$_pm['mysql']->query('COMMIT')){
            $_pm['mysql']->query('ROLLBACK');
            die('地图打开失败，请稍候重试！');
        }
		$_pm['mem']->del(MEM_USER_KEY);
		$_pm['mem']->del(MEM_USERBAG_KEY);

		echo "地图打开成功!";

		//$_pm['user']->updateMemUser($_SESSION['id']);
		//$_pm['user']->updateMemUserbag($_SESSION['id']);
	}
	else echo "地图打开失败，请确认包裹中有打开该地图的钥匙!";
}
?>
