<?php
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');
secStart($_pm['mem']);

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('0');
$user		= $_pm['user']->getUserById($uid);
if(!is_array($user)) die('0');
$op = (isset($_REQUEST['op']) && !is_array($_REQUEST['op'])) ? $_REQUEST['op'] : '';
if ($op == 'getauto')
{
	if (!is_array(getLock($uid)))
	{
		realseLock();
		die('0');
	}
	$todayStart = mktime(0, 0, 0, date('m',time()), date('d',time()), date('Y',time()));
	$player = $_pm['mysql']->getOneRecord("SELECT sysautosum,sysautotime FROM player WHERE id={$uid} FOR UPDATE");
	if (!is_array($player))
	{
		$_pm['mysql']->query('ROLLBACK');
		realseLock();
		die('0');
	}
	if (intval($player['sysautotime'])==0 || intval($player['sysautotime'])<$todayStart)
	{
		$user['sysautosum'] = kdjlSafeNonNegativeSum(intval($player['sysautosum']), 800);
		if($user['sysautosum'] === false)
		{
			$_pm['mysql']->query('ROLLBACK');
			realseLock();
			die('0');
		}
		$user['sysautotime']=	time();
		if(!$_pm['mysql']->query("UPDATE player
							     SET sysautosum={$user['sysautosum']},
								 sysautotime={$user['sysautotime']}
							   WHERE id={$uid}
					") || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
		{
			$_pm['mysql']->query('ROLLBACK');
			realseLock();
			die('0');
		}
		if(!$_pm['mysql']->query('COMMIT'))
		{
			$_pm['mysql']->query('ROLLBACK');
			realseLock();
			die('0');
		}
		realseLock();
		if(defined('MEM_USER_KEY')) $_pm['mem']->del(MEM_USER_KEY);
		echo "恭喜您，领取自动战斗寻怪奖励成功!";
		//$u->updateMemUser($_SESSION['id']);
	}
	else
	{
		$_pm['mysql']->query('ROLLBACK');
		realseLock();
		echo "您今天已经领取过了，每天只能一次噢！<br/>除非您到了一个级别，我会考虑特别奖励！";
	}
}
$_pm['mem']->memClose();
//####################
?>
