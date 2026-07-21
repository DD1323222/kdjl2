<?php
session_start();
require_once('../config/config.game.php');
require_once('../sec/sec_common_fnc.php');
require_once('../sec/dblock_fun.php');
secStart($_pm['mem']);

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');
$nums = (isset($_REQUEST['nums']) && !is_array($_REQUEST['nums'])) ? intval($_REQUEST['nums']) : 0;

$recallTransactionActive = false;
$recallLockHeld = false;
function recallRewardRelease()
{
	global $_pm,$recallTransactionActive,$recallLockHeld;
	if($recallTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$recallTransactionActive = false;
	}
	if($recallLockHeld)
	{
		realseLock();
		$recallLockHeld = false;
	}
}

function recallRewardAbort($message)
{
	recallRewardRelease();
	die($message);
}

register_shutdown_function('recallRewardRelease');
if(!is_array(getLock($uid))) die('服务器繁忙，请稍候再试！');
$recallLockHeld = true;
$recallTransactionActive = true;

$eligible = zhaohui();
$eligibleIds = array();
foreach(explode(',', strval($eligible)) as $eligibleId)
{
	$eligibleId = intval($eligibleId);
	if($eligibleId > 0) $eligibleIds[$eligibleId] = true;
}
if($nums < 1 || !isset($eligibleIds[$nums]))
{
	recallRewardAbort('对不起，你已经领取过此奖品或者你还没有达到相应的等级!');
}

$memtimeconfig = kdjlSafeMemValue($_pm['mem']->get('db_timeconfignew'), array());
$configs = (is_array($memtimeconfig) && isset($memtimeconfig['recallPlayer']) && is_array($memtimeconfig['recallPlayer']))
	? $memtimeconfig['recallPlayer'] : array();
$selected = false;
foreach($configs as $config)
{
	if(is_array($config) && isset($config['Id']) && intval($config['Id']) == $nums)
	{
		$selected = $config;
		break;
	}
}
if(!is_array($selected) || !isset($selected['days'])) recallRewardAbort('奖励配置错误！');

$rewardIds = array();
foreach(explode(',', $selected['days']) as $reward)
{
	$reward = trim($reward);
	if($reward === '') continue;
	$parts = explode(':', $reward);
	if(count($parts) != 2) recallRewardAbort('奖励配置错误！');
	$pid = isset($parts[0]) ? intval($parts[0]) : 0;
	$count = isset($parts[1]) ? intval($parts[1]) : 0;
	if($pid < 1 || $count < 1) recallRewardAbort('奖励配置错误！');
	for($i=0;$i<$count;$i++) $rewardIds[] = $pid;
}
if(empty($rewardIds)) recallRewardAbort('奖励配置错误！');

$marker = 999900 + $nums;
$legacyMarker = intval('9999'.$nums);
$markerList = $marker == $legacyMarker ? strval($marker) : $marker.','.$legacyMarker;
$claimed = $_pm['mysql']->getOneRecord("SELECT Id FROM tasklog WHERE uid={$uid} AND taskid IN ({$markerList}) LIMIT 1 FOR UPDATE");
if(is_array($claimed)) recallRewardAbort('对不起，你已经领取过此奖品！');

if(saveGetPropsa(implode(',', $rewardIds), $uid) !== true)
{
	recallRewardAbort('奖励发放失败，请整理背包后再试！');
}
$now = time();
if(!$_pm['mysql']->query("INSERT INTO tasklog(uid,taskid,time) VALUES({$uid},{$marker},{$now})") ||
	mysql_affected_rows($_pm['mysql']->getConn()) != 1)
{
	recallRewardAbort('领取记录保存失败，请稍候再试！');
}
if(!$_pm['mysql']->query('COMMIT')) recallRewardAbort('奖励发放失败，请稍候再试！');
$recallTransactionActive = false;
$_pm['mem']->del(MEM_USERBAG_KEY);
recallRewardRelease();
echo '领取奖品成功！';
?>
