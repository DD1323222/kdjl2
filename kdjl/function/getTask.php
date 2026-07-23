<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.12.24
*@Usage: 任务接取、放弃和完成入口
* 任务条件示例：see:9、killmon:44|45:5、giveitem:X|Z:Y。
* 任务奖励示例：props:X:Y|A:B、itemrand:X:Y:Z|E:F:G、gonggao:文本。
*@Note: none
*/

require_once('../config/config.game.php');
secStart($_pm['mem']);

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');

$user		= $_pm['user']->getUserById($uid);
$bag		= $_pm['user']->getUserBagById($uid);
$petsAll	= $_pm['user']->getUserPetById($uid);
$bbs = kdjlSafeMemValue($_pm['mem']->get(MEM_BB_KEY), array());
$memtask = kdjlSafeMemValue($_pm['mem']->get(MEM_TASK_KEY), array());
if(!is_array($user)) $user = array();
if(!is_array($bag)) $bag = array();
if(!is_array($petsAll)) $petsAll = array();
if(!is_array($bbs)) $bbs = array();
if(!is_array($memtask)) $memtask = array();
if(!isset($user['mbid'])) $user['mbid'] = 0;
if(!isset($user['name'])) $user['name'] = '';
if(!isset($user['score'])) $user['score'] = 0;
if(!isset($user['vip'])) $user['vip'] = 0;

$n = (isset($_REQUEST['n']) && !is_array($_REQUEST['n'])) ? intval($_REQUEST['n']) : 0;
$s = (isset($_REQUEST['s']) && !is_array($_REQUEST['s'])) ? intval($_REQUEST['s']) : 0;
$tsk = new task();
$type = (isset($_REQUEST['type']) && !is_array($_REQUEST['type'])) ? $_REQUEST['type'] : '';
$requestTaskId = (isset($_REQUEST['taskid']) && !is_array($_REQUEST['taskid'])) ? intval($_REQUEST['taskid']) : 0;
$taskLocked = false;
$taskInTransaction = false;
function taskGateReleaseLockOnShutdown()
{
	global $taskLocked,$taskInTransaction,$_pm;
	if($taskInTransaction && isset($_pm['mysql']))
	{
		$_pm['mysql']->query('ROLLBACK');
		$taskInTransaction = false;
	}
	if($taskLocked && function_exists('realseLock'))
	{
		realseLock();
		$taskLocked = false;
	}
}
register_shutdown_function('taskGateReleaseLockOnShutdown');
function taskGateAbort($message)
{
	global $taskLocked,$taskInTransaction,$_pm;
	if($taskInTransaction && isset($_pm['mysql']))
	{
		$_pm['mysql']->query('ROLLBACK');
		$taskInTransaction = false;
	}
	if($taskLocked && function_exists('realseLock'))
	{
		realseLock();
		$taskLocked = false;
	}
	die($message);
}
function taskGateNormalizeTask($task)
{
	if(!is_array($task)) return false;
	$defaults = array(
		'id' => 0,
		'cid' => '',
		'fromnpc' => '',
		'hide' => 0,
		'xulie' => 0,
		'limitlv' => '',
		'okneed' => '',
		'oknpc' => 0,
		'frommsg' => ''
	);
	foreach($defaults as $key => $value)
	{
		if(!isset($task[$key])) $task[$key] = $value;
	}
	$task['id'] = intval($task['id']);
	$task['hide'] = intval($task['hide']);
	$task['xulie'] = intval($task['xulie']);
	$task['oknpc'] = intval($task['oknpc']);
	return $task;
}
if(in_array($type,array('get','off','complate'))){
	require_once('../sec/dblock_fun.php');
	$a = getLock($uid);
	if(!is_array($a)){
		taskGateAbort('服务器繁忙，请稍候操作！');
	}
	$taskLocked = true;
	$taskInTransaction = true;
	$user = $_pm['user']->getUserById($uid);
	$bag = $_pm['user']->getUserBagById($uid);
	$petsAll = $_pm['user']->getUserPetById($uid);
	if(!is_array($user)) $user = array();
	if(!is_array($bag)) $bag = array();
	if(!is_array($petsAll)) $petsAll = array();
	if(!isset($user['mbid'])) $user['mbid'] = 0;
	if(!isset($user['name'])) $user['name'] = '';
	if(!isset($user['score'])) $user['score'] = 0;
	if(!isset($user['vip'])) $user['vip'] = 0;
}

if($type == "get") // 接取任务
{
	$taskid = $requestTaskId;
	if(empty($taskid))
	{
		taskGateAbort('数据错误！');
	}
	/*$taskinfo = $_pm['mem']->dataGet(array('k'	=>	MEM_TASK_KEY,
										  'v'	=> "if(\$rs['id']=={$taskid}) \$ret=\$rs;"
										  ));*/
	$taskinfo = isset($memtask[$taskid]) ? taskGateNormalizeTask($memtask[$taskid]) : false;
	if($taskinfo === false) taskGateAbort('数据错误！');
	if(!taskScheduleIsActive($taskinfo)) taskGateAbort('当前不在任务开放时间内！');
	if(empty($taskinfo['fromnpc'])){
		taskGateAbort('任务接取数据错误！');
	}
	$taskCidParts = explode(':', strval($taskinfo['cid']), 2);
	$isSequenceTask = count($taskCidParts) === 2 && $taskCidParts[0] === 'rwl';
	if($taskinfo['hide'] != 1 && !$isSequenceTask){
		taskGateAbort('该任务当前不可接受！');
	}
	if($isSequenceTask){
		if($taskinfo['hide'] != 1){
			$a = explode('|',$taskinfo['fromnpc']);
			$rwlarr = $_pm['mysql'] -> getOneRecord("SELECT taskid FROM tasklog WHERE uid = {$uid} AND xulie = {$taskinfo['xulie']} and fromnpc = {$a[0]}");
			if(!is_array($rwlarr)){
				taskGateAbort('不能接受此序列任务！');
			}
			$prevTaskId = intval($rwlarr['taskid']);
			if(!isset($memtask[$prevTaskId]) || !is_array($memtask[$prevTaskId])){
				taskGateAbort("数据错误！");
			}
			$lar = $memtask[$prevTaskId];
			$larCid = isset($lar['cid']) ? $lar['cid'] : '';
			$larCidArr = explode(':',$larCid,2);
			if(count($larCidArr) < 2 || $larCidArr[0] != 'rwl'){
				taskGateAbort("数据错误！");
			}
			$rwl = explode('|',$larCidArr[1]);
			if(!isset($rwl[1]) || intval($rwl[1]) != $taskid){
				taskGateAbort('不能接受此序列任务！');
			}
		}else{
			$rwlarr = $_pm['mysql'] -> getOneRecord("SELECT taskid FROM tasklog WHERE uid = {$uid} AND taskid = {$taskid}");
			if(is_array($rwlarr)){
				taskGateAbort('不能接受此任务！');
			}
		}
	}
	if(!empty($taskinfo['limitlv']))
	{
		$limitarr = explode(",",$taskinfo['limitlv']);
		if(is_array($limitarr))
		{
			foreach($limitarr as $v)
			{
				$limitarrs = explode(":",$v);
				if(count($limitarrs) < 2) continue;
				$limitType = strtolower(trim($limitarrs[0]));
				if($limitType === 'level') $limitType = 'lv';
				switch($limitType)
				{
					case "lv":
						$blv = 0;
						foreach($petsAll as $bb)
						{
							if(!is_array($bb)) continue;
							if($bb['id'] == $user['mbid'])
							{
								$blv = $bb['level'];
							}
						}
						if(empty($blv))
						{
							taskGateAbort('请先到牧场设置主战宠物！');
						}
						$lvarr = explode("|",$limitarrs[1]);
						if(!isset($lvarr[1])) $lvarr[1] = 0;
						if($lvarr[1] == "0")
						{
							if($blv < $lvarr[0])
							{
								taskGateAbort('您的等级不够接受此任务！');
							}
						}
						else
						{
							if($blv < $lvarr[0] || $blv > $lvarr[1])
							{
								taskGateAbort('您的等级不在可接此任务范围之内！');
							}
						}
						break;
					case "wx":
						$_mbwx='';
						foreach($petsAll as $bb)
						{
							if(!is_array($bb)) continue;
							if($bb['id'] == $user['mbid'])
							{
								$_mbwx = $bb['wx'];
							}
						}
						if(empty($_mbwx))
						{
							taskGateAbort('请先到牧场设置主战宠物！');
						}
						$wxs=explode('|',$limitarrs[1]);
						if(!in_array($_mbwx,$wxs))
						{
							taskGateAbort('主战宠物五行不符合任务要求！');
						}
						break;
					case "xfyb":
						$safeYbName = $_pm['mysql']->escape($user['name']);
						$sql="select * from yblog where nickname='{$safeYbName}'";
						$t=$_pm['mysql'] -> getRecords($sql);
						if(!is_array($t))
						{
							taskGateAbort('您未进行元宝消费，无法领取任务！');
						}else{

							$xfyb=explode(";",$limitarrs[1]);
							if(count($xfyb) < 2) taskGateAbort('领取任务出错！');
							$xfyb1=explode("|",$xfyb[0]);
							$xfyb2=explode("|",$xfyb[1]);
							if(count($xfyb1) < 2 || count($xfyb2) < 2) taskGateAbort('领取任务出错！');
							$sum_yb=0;
							if(is_array($xfyb2) && is_array($xfyb1)){
								foreach($t as $k=>$v){
									if(date('Ymd',$v['buytime'])>=$xfyb1[0] && date('Ymd',$v['buytime'])<=$xfyb1[1]){
										$sum_yb+=$v['yb'];

									}
								}

								$taskidxfyb=$_pm['mysql']->getRecords('select id,limitlv from task where  left(limitlv,4)="xfyb"');
								if(!is_array($taskidxfyb)) $taskidxfyb = array();
								$taskidxfybs=array();
								$taskidxfybinfos=array();
								foreach($taskidxfyb as $row)
								{
									$taskidxfybs[$row['id']]=$row['id'];
									$taskidxfybinfos[$row['id']]=$row['limitlv'];
								}

								$mytasklogs=array();
								if(!empty($taskidxfybs))
								{
									$mytasklogssql='select taskid from tasklog where uid='.$uid.' and taskid in ('.implode(',',array_values($taskidxfybs)).');';
									$mytasklogs=$_pm['mysql']->getRecords($mytasklogssql);
									if(!is_array($mytasklogs)) $mytasklogs = array();
								}
								$myusedtaskyblog=0;
								if(!empty($mytasklogs)){
									foreach($mytasklogs as $tlog)
									{
										if(!isset($taskidxfybinfos[$tlog['taskid']])) continue;
										$strtlog=explode(';',$taskidxfybinfos[$tlog['taskid']]);
										if(count($strtlog) < 2) continue;
										$yblogstr=explode('|',$strtlog[1]);
										$strtlog=explode(':',$strtlog[0]);
										if(count($strtlog) < 2) continue;
										$strtlog=explode('|',$strtlog[1]);
										if(count($strtlog) < 2 || count($yblogstr) < 1) continue;
										if(intval($strtlog[1])>$xfyb1[0])
										{
											$myusedtaskyblog+=intval($yblogstr[0]);
										}
									}
								}
								$sum_yb-=$myusedtaskyblog;

								if($xfyb2[1]==0){
									if($sum_yb<=$xfyb2[0]){
										taskGateAbort('您的元宝消费未达到领取此任务的要求！');
									}
								}elseif($xfyb2[0]>0 && $xfyb2[1]>=0){
									if($sum_yb<=$xfyb2[0] || $sum_yb>=$xfyb2[1] ){
									/*echo $sum_yb.'<br />';
									print_r($xfyb2);exit;*/
										taskGateAbort('您的元宝消费量不在领取此任务的范围内！');
									}
								}else{
									taskGateAbort('领取任务出错！');
								}
							}else{
								taskGateAbort('领取任务出错！');
							}
						}
						break;

					case "xfsj":
						$jc=0;
						$xfsj=explode("|",$limitarrs[1]);
						if(count($xfsj) < 2) taskGateAbort('领取任务出错！');
						$safeYbName = $_pm['mysql']->escape($user['name']);
						$sql="select * from yblog where nickname='{$safeYbName}'";
						$t=$_pm['mysql'] -> getRecords($sql);
						$check = $_pm['mysql'] -> getOneRecord("select time from tasklog where uid = {$uid} and taskid = 88888 order by id desc limit 1");
						if(!is_array($t) || empty($t))
						{
							taskGateAbort('您未进行消费，无法领取任务！');
						}
						$count = count($t) - 1;
						if(is_array($check) && isset($t[$count]['id']) && $t[$count]['id'] <= $check['time']){
							taskGateAbort('任务条件不满足！');
						}
						else
						{
							foreach($t as $k=>$v){
							 // 检测到符合日期范围的消费记录即可通过。
								if(date('Ymd',$v['buytime'])>=$xfsj[0] && date('Ymd',$v['buytime'])<=$xfsj[1]){
									$jc=1;
									break;
								}
							}
						}
						if($jc==0){
							taskGateAbort('您的消费时间不符合任务要求！');
						}
						break;

					case "cishu":
						if(count($limitarrs) < 3) taskGateAbort('领取任务出错！');
						$time = strtotime(date('Ymd',time())) - (max(1,intval($limitarrs[2])) - 1) * 24 * 3600;
						$sql = "SELECT count(*) sl FROM tasklog WHERE uid = {$uid} and taskid = {$taskid} and time > {$time}";
						$arr = $_pm['mysql'] -> getOneRecord($sql);
						if(is_array($arr))
						{
							if(!isset($arr['sl'])) $arr['sl'] = 0;
							if($arr['sl'] >= $limitarrs[1])
							{
								taskGateAbort('您已达到该任务的完成次数限制！');
							}
						}
						break;

					case "cz": // 接取任务所需的主战宠物成长范围
						$lvarr = explode("|",$limitarrs[1]);
						if(!isset($lvarr[1])) $lvarr[1] = 0;
						$mainBid = isset($user['mbid']) ? intval($user['mbid']) : 0;
						$sql = "SELECT czl FROM userbb WHERE id={$mainBid} AND uid={$uid}";
						$petsmain=$_pm['mysql'] -> getOneRecord($sql);
						if(!is_array($petsmain) || !isset($petsmain['czl'])) $petsmain = array('czl' => 0);

						if($lvarr[1]==0){
							if($lvarr[0]>$petsmain['czl']){
								taskGateAbort('该宠物成长值不足，无法领取任务！');
							}
						}
						if($lvarr[1]>0){
							if(!($lvarr[0]<=$petsmain['czl'] && $lvarr[1]>=$petsmain['czl'])){
								taskGateAbort('该宠物成长值不在此任务范围内，无法领取任务！');
							}
						}
						break;
					case "comself":
						$abcarr = explode("|",$limitarrs[1]);
						$bbarr = "";
						$bname = '';
						foreach($petsAll as $pv)
						{
							if(!is_array($pv)) continue;
							if($pv['id'] == $user['mbid'])
							{
								$bname = $pv['name'];
							}
						}
						$bnamearr = array();
						foreach($abcarr as $av)
						{
							foreach($bbs as $bbav)
							{
								if(!is_array($bbav)) continue;
								if($bbav['id'] == $av)
								{
									$bnamearr[] = $bbav['name'];
								}
							}
						}
						if(!in_array($bname,$bnamearr))
						{
							taskGateAbort('您的当前主宠不能接受此任务！');
						}
						break;
					case "jifen": // jifen:X，接取任务所需积分
						if($user['score'] < $limitarrs[1])
						{
							taskGateAbort('您的当前积分不够接此任务！');
						}
						break;
					case "vip": // vip:X，接取任务所需 VIP 积分
						if($user['vip'] < $limitarrs[1])
						{
							taskGateAbort('您的VIP积分不够接此任务！');
						}
						break;
					case 'merge':
						$merge = $_pm['mysql'] -> getOneRecord("SELECT merge FROM player_ext WHERE uid = {$uid}");
						if(!is_array($merge) || !isset($merge['merge'])) $merge = array('merge' => 0);
						if($merge['merge'] < 1){
							taskGateAbort('任务条件不满足！');
						}
				}
			}
		}
	}
	$arr = "";
	if(empty($taskinfo['cid']))
	{
		$sql = "SELECT taskid FROM tasklog WHERE uid = {$uid} and taskid = {$taskid}";
		$arr = $_pm['mysql'] -> getOneRecord($sql);
	}
	if(is_array($arr))
	{
		taskGateAbort('该任务只能接受一次！');
	}
	// 一次性任务检查结束。
	// 以下保留旧的单任务字段检查，仅作兼容参考。
	/*if(!empty($user['task']))
	{
		if($user['task'] == $taskid)
		{
		die('该任务只能接受一次！');
		}

	}*/
	$accept = array();
	$usertaskarr = $_pm['mysql'] -> getRecords("SELECT taskid FROM task_accept WHERE uid = {$uid}");
	if(!is_array($usertaskarr)){
		taskGateAbort('任务接取数据错误！');
	}
	foreach($usertaskarr as $v){
		if(!is_array($v) || !isset($v['taskid'])) continue;
		$accept[] = $v['taskid'];
	}
	if(in_array($taskid,$accept)){
		taskGateAbort('您已经接受此任务！');
	}
	if(count($usertaskarr) >= 15){
		taskGateAbort('您已经接受了15个任务，超过了最大限制！');
	}

	$arr1=explode(',',$taskinfo['okneed']);
	for($i=0;$i<count($arr1);$i++){
		$arr2[$i]=explode(':',$arr1[$i]);
		if(count($arr2[$i]) < 2) continue;
		if($arr2[$i][0]=='zx'){
			$sql = "SELECT onlinetime FROM player_ext WHERE uid = {$uid}";
			$arr0 = $_pm['mysql'] -> getOneRecord($sql);
			if(!is_array($arr0) || !isset($arr0['onlinetime'])) $arr0 = array('onlinetime' => 0);
				if($arr0['onlinetime']<($arr2[$i][1]*3600)){
					taskGateAbort('您的在线时间还不够，无法接受此任务！');
				}
		}
	}
	//$sql = "UPDATE player SET task = {$taskid},tasklog='' WHERE id = {$uid}";
	$sql = "INSERT INTO task_accept (uid,taskid,time) VALUES ({$uid},$taskid,".time().")";
	if(!$_pm['mysql'] -> query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
		$_pm['mysql']->query('ROLLBACK');
		taskGateAbort('任务条件不满足！');
	}
	if(!$_pm['mysql']->query('COMMIT')){
		$_pm['mysql']->query('ROLLBACK');
		taskGateAbort('接受任务失败！');
	}
	$taskInTransaction = false;
	realseLock();
	$taskLocked = false;
	echo "恭喜您，成功接受此任务！";
}

else if($type == 'off')
{
	$taskid = $requestTaskId;
	if(empty($taskid))
	{
		taskGateAbort('数据错误！');
	}
	/*if($user['task'] != $taskid)
	{
		die('您没有接受此任务！');
	}*/
	$accept = array();
	$usertaskarr = $_pm['mysql'] -> getRecords("SELECT taskid FROM task_accept WHERE uid = {$uid}");
	if(!is_array($usertaskarr)){
		taskGateAbort('任务接取数据错误！');
	}
	foreach($usertaskarr as $v){
		if(!is_array($v) || !isset($v['taskid'])) continue;
		$accept[] = $v['taskid'];
	}
	if(!in_array($taskid,$accept)){
		taskGateAbort('您没有接受此任务！');
	}
	$taskinfo = isset($memtask[$taskid]) ? taskGateNormalizeTask($memtask[$taskid]) : false;
	//$sql = "UPDATE player SET task = '',tasklog = '' WHERE id = {$uid}";
	$sql = "DELETE FROM task_accept WHERE uid = {$uid} AND taskid = $taskid";
	if(!$_pm['mysql'] -> query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
		$_pm['mysql']->query('ROLLBACK');
		taskGateAbort('放弃任务失败！');
	}
	if(!$_pm['mysql']->query('COMMIT')){
		$_pm['mysql']->query('ROLLBACK');
		taskGateAbort('放弃任务失败！');
	}
	$taskInTransaction = false;
	realseLock();
	$taskLocked = false;
	echo '放弃成功！';
}
else if($type == "complate")
{
	$taskid = $requestTaskId;
	$flag = 0;
	$accept = array();
	$usertaskarr = $_pm['mysql'] -> getRecords("SELECT taskid,state FROM task_accept WHERE uid = {$uid}");
	if(!is_array($usertaskarr)){
		taskGateAbort('任务接取数据错误！');
	}
	foreach($usertaskarr as $v){
		if(!is_array($v) || !isset($v['taskid'])) continue;
		$accept[] = $v['taskid'];
		if($v['taskid'] == $taskid){
			$user['tasklog'] = $v['state'];
			$user['task'] = $v['taskid'];
			$flag = 1;
		}
	}
	if($flag != 1){
		taskGateAbort('您没有接受此任务！');
	}


	$taskinfo = isset($memtask[$taskid]) ? taskGateNormalizeTask($memtask[$taskid]) : false;
	if($taskinfo === false){
		taskGateAbort('任务配置不存在！');
	}
	if(!taskScheduleIsActive($taskinfo)){
		taskGateAbort('当前不在任务开放时间内，只能放弃已接任务！');
	}
	if(!empty($taskinfo['limitlv']))
	{
		$limitarr = explode(",",$taskinfo['limitlv']);

		if(is_array($limitarr))
		{
			foreach($limitarr as $v)
			{
				$limitarrs = explode(":",$v);
				if(count($limitarrs) < 2) continue;
				switch($limitarrs[0])
				{
					case "cishu":
						if(count($limitarrs) < 3) taskGateAbort('领取任务出错！');
						$time = strtotime(date('Ymd',time())) - (max(1,intval($limitarrs[2])) - 1) * 24 * 3600;
						$sql = "SELECT taskid FROM tasklog WHERE uid = {$uid} and taskid = {$taskid} and time > {$time}";
						$arr = $_pm['mysql'] -> getRecords($sql);
						if(is_array($arr))
						{
							if(count($arr) >= $limitarrs[1])
							{
								taskGateAbort('任务条件不满足！');
							}
						}
						break;
					case "jifen": // jifen:X，完成任务所需积分
						if($user['score'] < $limitarrs[1])
						{
							taskGateAbort('您的当前积分不够完成此任务！');
						}
						break;
					case "vip": // vip:X，完成任务所需 VIP 积分
						if($user['vip'] < $limitarrs[1])
						{
							taskGateAbort('您的VIP积分不够完成此任务！');
						}
						break;
				}
			}
		}
	}
	$arr = "";
	if(empty($taskinfo['cid']))
	{
		$sql = "SELECT taskid FROM tasklog WHERE uid = {$uid} and taskid = {$taskid}";
		$arr = $_pm['mysql'] -> getOneRecord($sql);
	}
	if(is_array($arr))
	{
		taskGateAbort('该任务只能接受一次！');
	}

	if (isset($_REQUEST['n']) && $n>0 && $n<10000) // 显示 NPC 任务对话
	{
		$ret = $_task['dlg'][$n];
		$tid = $user['task'];
		/*$taskinfo = $_pm['mem']->dataGet(array('k'	=>	MEM_TASK_KEY,
									  'v'	=> "if(\$rs['id']=={$tid} && {$tid}=={$user['task']}) \$ret=\$rs;"
							));*/
		$taskinfo = isset($memtask[$tid]) ? taskGateNormalizeTask($memtask[$tid]) : false;
	//echo $taskinfo['oknpc'].'<br />'.$n;exit;
		if ($taskinfo !== false) // 找到当前任务配置
		{
			if ($taskid != $user['task']) // start task.
			{
				$ret = $tsk->formatTask($taskinfo['frommsg']);
				echo $ret;
			}
			else if ($taskinfo['oknpc'] == $n)
			{
				$ret = $tsk->completeTask($user, $taskinfo);
				if(!$_pm['mysql']->query('COMMIT')){
					$_pm['mysql']->query('ROLLBACK');
					taskGateAbort('完成任务失败！');
				}
				$taskInTransaction = false;
				realseLock();
				$taskLocked = false;
				echo $ret;
			}
		}
	}
	else if (isset($_REQUEST['s']) && $s>0 && $s<10000)
	{
		if ($requestTaskId > 0)
			$user['task']=$requestTaskId;
		$tsk->startTask($user, $s);
	}
}
if($taskLocked){
	realseLock();
	$taskLocked = false;
}
$_pm['mem']->memClose();
?>
