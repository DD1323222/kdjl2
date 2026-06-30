<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.09.25
*@Usage: 宠物托管
*/
session_start();
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');
secStart($_pm['mem']);

function tgFail($message, $rollback=false)
{
	global $_pm;
	if ($rollback)
	{
		$_pm['mysql']->query('ROLLBACK');
	}
	die($message);
}

function tgCommit()
{
	global $_pm;
	if (!$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		die('操作提交失败，请重试！');
	}
}

function tgPetStatus($pet, $now)
{
	if (!is_array($pet) || intval($pet['tgflag']) == 0) return 0;
	if ($now < intval($pet['tgstime'])) return 3;
	if ($now - intval($pet['tgstime']) < intval($pet['tgtime'])) return 1;
	return 2;
}

function tgGetPet($uid, $id, $forUpdate=false)
{
	global $_pm;
	$sql = 'SELECT * FROM userbb WHERE uid='.intval($uid).' AND id='.intval($id);
	if ($forUpdate) $sql .= ' FOR UPDATE';
	return $_pm['mysql']->getOneRecord($sql);
}

function tgStart($uid, $auto)
{
	global $_pm;
	$db = $_pm['mysql'];
	$petId = isset($_REQUEST['pets']) ? intval($_REQUEST['pets']) : 0;
	$hours = isset($_REQUEST['time']) ? intval($_REQUEST['time']) : 0;
	$mode = isset($_REQUEST['mes']) ? intval($_REQUEST['mes']) : 0;
	$allowedHours = array(1, 2, 4, 8, 10);

	if ($petId < 1 || !in_array($hours, $allowedHours, true) || $mode < 1 || $mode > 3)
	{
		tgFail('1');
	}

	$now = time();
	$currentHour = intval(date('G', $now));
	if (!$auto && $currentHour >= 10 && $currentHour < 22)
	{
		tgFail('0');
	}

	$startTime = $now;
	if ($auto && $currentHour >= 10 && $currentHour < 22)
	{
		$startTime = strtotime(date('Y-m-d', $now).' 22:00:00');
	}
	$duration = $hours * 3600;
	$endHour = intval(date('G', $startTime + $duration));
	if ($endHour >= 10 && $endHour < 22)
	{
		tgFail('7');
	}

	getLock($uid);
	$user = $db->getOneRecord('SELECT tgtime,tgmax FROM player WHERE id='.$uid.' FOR UPDATE');
	$pet = tgGetPet($uid, $petId, true);
	if (!is_array($user) || !is_array($pet))
	{
		tgFail('1', true);
	}
	if (intval($pet['level']) < 10)
	{
		tgFail('199', true);
	}
	if (intval($pet['muchang']) != 1 || !empty($pet['chchengsx']))
	{
		tgFail('该宠物当前状态不能托管！', true);
	}
	if (intval($pet['tgflag']) != 0)
	{
		$status = tgPetStatus($pet, $now);
		if ($status == 3) tgFail('8', true);
		if ($status == 1) tgFail('3', true);
		tgFail('4', true);
	}

	$activePets = $db->getRecords('SELECT id FROM userbb WHERE uid='.$uid.' AND tgflag>0 FOR UPDATE');
	$activeCount = is_array($activePets) ? count($activePets) : 0;
	if ($activeCount >= 3)
	{
		tgFail('5', true);
	}
	$playerLimit = intval($user['tgmax']);
	if ($playerLimit < 0) $playerLimit = 0;
	if ($activeCount >= $playerLimit)
	{
		tgFail('6', true);
	}

	$cost = $hours * $mode;
	$sql = 'UPDATE player SET tgtime=tgtime-'.$cost.' WHERE id='.$uid.' AND tgtime>='.$cost;
	if (!$db->query($sql) || mysql_affected_rows($db->getConn()) != 1)
	{
		tgFail('2', true);
	}

	$flag = $auto ? 2 : 1;
	$sql = 'UPDATE userbb SET tgflag='.$flag.',tgstime='.$startTime.',tgmes='.$mode.',tgtime='.$duration.
		' WHERE uid='.$uid.' AND id='.$petId.' AND muchang=1 AND tgflag=0'.
		' AND (chchengsx IS NULL OR chchengsx="")';
	if (!$db->query($sql) || mysql_affected_rows($db->getConn()) != 1)
	{
		tgFail('宠物状态已经变化，请刷新后重试！', true);
	}

	tgCommit();
	die('10');
}

function giveprops($level)
{
	global $tuoguan;
	$config = false;
	foreach ($tuoguan as $range => $value)
	{
		$levels = explode('-', $range);
		if (count($levels) == 2 && $level >= intval($levels[0]) && $level <= intval($levels[1]))
		{
			$config = $value;
			break;
		}
	}
	if ($config === false) return false;

	$reward = false;
	foreach (explode(',', $config) as $itemConfig)
	{
		$info = explode(':', $itemConfig);
		if (count($info) != 3) continue;
		$chance = intval($info[1]);
		if ($chance < 1) continue;
		if (rand(1, $chance) == 1)
		{
			$reward = array('id'=>intval($info[0]), 'sum'=>intval($info[2]));
		}
	}
	return $reward;
}

$uid = intval($_SESSION['id']);
$action = isset($_REQUEST['action']) ? strval($_REQUEST['action']) : '';
$allowedActions = array('getinfo', 'times', 'timesdo', 'change', 'tuoguan', 'offpets', 'offpet', 'show', 'auto');
if ($uid < 1 || !in_array($action, $allowedActions, true))
{
	die('数据有误！');
}

$mutatingActions = array('timesdo', 'tuoguan', 'offpet', 'auto');
if (in_array($action, $mutatingActions, true))
{
	$timeKey = 'tuoguan_action_'.$uid;
	$lastTime = isset($_SESSION[$timeKey]) ? intval($_SESSION[$timeKey]) : 0;
	if ($lastTime > 0 && time() - $lastTime < 1)
	{
		die('服务器繁忙，请稍候操作！');
	}
	$_SESSION[$timeKey] = time();
}

$db = $_pm['mysql'];
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

if ($action == 'getinfo')
{
	$pet = $id > 0 ? tgGetPet($uid, $id, false) : false;
	if (!is_array($pet) || intval($pet['tgflag']) == 0) tgFail('1');
	$modes = array(1=>'休息', 2=>'武力修炼', 3=>'冒险修炼');
	$mode = isset($modes[intval($pet['tgmes'])]) ? $modes[intval($pet['tgmes'])] : '未知';
	$statusNames = array(0=>'未托管', 1=>'托管中', 2=>'托管完成', 3=>'等待中');
	$status = tgPetStatus($pet, time());
	$str = '托管时间：'.(intval($pet['tgtime']) / 3600).'小时&nbsp;托管方式:'.$mode.
		'&nbsp;托管状态：'.$statusNames[$status];
	die($str);
}

if ($action == 'times')
{
	$pet = $id > 0 ? tgGetPet($uid, $id, false) : false;
	if (!is_array($pet) || intval($pet['tgflag']) == 0) tgFail('1');
	$status = tgPetStatus($pet, time());
	if ($status == 3) tgFail('2');
	if ($status == 2) tgFail('3');
	$elapsed = time() - intval($pet['tgstime']);
	$remaining = intval($pet['tgtime']) - $elapsed;
	$crystal = intval(round($remaining * 100 / 3600));
	die('立即加速完成，需要消耗水晶：'.$crystal.'，您确定加速吗？');
}

if ($action == 'timesdo')
{
	if ($id < 1) tgFail('数据有误！');
	getLock($uid);
	$pet = tgGetPet($uid, $id, true);
	$playerExt = $db->getOneRecord('SELECT sj FROM player_ext WHERE uid='.$uid.' FOR UPDATE');
	if (!is_array($pet) || !is_array($playerExt) || intval($pet['tgflag']) == 0 || intval($pet['muchang']) != 1)
	{
		tgFail('数据有误！', true);
	}
	$status = tgPetStatus($pet, time());
	if ($status == 3) tgFail('等待的宠物不能加速！', true);
	if ($status == 2) tgFail('托管完成，不需要加速！', true);

	$elapsed = time() - intval($pet['tgstime']);
	$remaining = intval($pet['tgtime']) - $elapsed;
	$crystal = intval(round($remaining * 100 / 3600));
	if ($crystal > 0)
	{
		$sql = 'UPDATE player_ext SET sj=sj-'.$crystal.' WHERE uid='.$uid.' AND sj>='.$crystal;
		if (!$db->query($sql) || mysql_affected_rows($db->getConn()) != 1)
		{
			tgFail('1', true);
		}
	}
	$newStart = time() - intval($pet['tgtime']);
	$sql = 'UPDATE userbb SET tgstime='.$newStart.' WHERE uid='.$uid.' AND id='.$id.' AND tgflag>0';
	if (!$db->query($sql) || mysql_affected_rows($db->getConn()) != 1)
	{
		tgFail('数据有误！', true);
	}
	tgCommit();
	die('加速完成，您是否取回您的宠物？');
}

if ($action == 'change')
{
	$pet = $id > 0 ? tgGetPet($uid, $id, false) : false;
	if (!is_array($pet)) tgFail('10');
	die(strval(tgPetStatus($pet, time())));
}

if ($action == 'tuoguan')
{
	tgStart($uid, false);
}

if ($action == 'auto')
{
	tgStart($uid, true);
}

if ($action == 'offpets')
{
	$pet = $id > 0 ? tgGetPet($uid, $id, false) : false;
	if (!is_array($pet)) tgFail('0');
	$status = tgPetStatus($pet, time());
	if ($status == 0) tgFail('1');
	if ($status == 3) tgFail('4');
	if ($status == 1) tgFail('3');
	tgFail('2');
}

if ($action == 'offpet')
{
	if ($id < 1) tgFail('11');
	getLock($uid);
	$user = $db->getOneRecord('SELECT maxbag FROM player WHERE id='.$uid.' FOR UPDATE');
	$pet = tgGetPet($uid, $id, true);
	if (!is_array($user) || !is_array($pet) || intval($pet['tgflag']) == 0 || intval($pet['muchang']) != 1)
	{
		tgFail('11', true);
	}

	$mode = intval($pet['tgmes']);
	$duration = intval($pet['tgtime']);
	if ($duration <= 0 || $mode < 1 || $mode > 3)
	{
		tgFail('0', true);
	}
	$elapsed = time() - intval($pet['tgstime']);
	if ($elapsed < 0) $elapsed = 0;
	if ($elapsed > $duration) $elapsed = $duration;
	$ticks = intval($elapsed / 300);

	$multiplier = 1;
	if ($mode == 2) $multiplier = 2;
	else if ($mode == 3) $multiplier = 2.5;
	$exp = intval(round(intval($pet['level']) * (floatval($pet['czl']) / 40) * 2500 * $ticks * $multiplier));

	$rewards = array();
	if ($mode == 3 && $ticks > 0)
	{
		for ($i=0; $i<$ticks; $i++)
		{
			$reward = giveprops(intval($pet['level']));
			if (!is_array($reward) || $reward['id'] < 1 || $reward['sum'] < 1) continue;
			if (!isset($rewards[$reward['id']])) $rewards[$reward['id']] = 0;
			$rewards[$reward['id']] += $reward['sum'];
		}
	}

	$bagRows = $db->getRecords(
		'SELECT id,pid,sums,sell,bsum,psum,pyb,zbing,zbpets FROM userbag WHERE uid='.$uid.' FOR UPDATE'
	);
	if (!is_array($bagRows)) $bagRows = array();
	$usedSlots = 0;
	$stackByPid = array();
	foreach ($bagRows as $bagRow)
	{
		if (intval($bagRow['sums']) > 0 && intval($bagRow['zbing']) == 0) $usedSlots++;
		if (intval($bagRow['sums']) > 0 && intval($bagRow['zbing']) == 0 &&
			intval($bagRow['bsum']) == 0 &&
			intval($bagRow['psum']) == 0 && intval($bagRow['pyb']) == 0 &&
			intval($bagRow['zbpets']) == 0 && !isset($stackByPid[intval($bagRow['pid'])]))
		{
			$stackByPid[intval($bagRow['pid'])] = intval($bagRow['id']);
		}
	}

	$newSlots = 0;
	foreach ($rewards as $propsId => $sum)
	{
		if (!isset($stackByPid[$propsId])) $newSlots++;
	}
	if ($usedSlots + $newSlots > intval($user['maxbag']))
	{
		tgFail('12', true);
	}

	foreach ($rewards as $propsId => $sum)
	{
		if (isset($stackByPid[$propsId]))
		{
			$sql = 'UPDATE userbag SET sums=sums+'.intval($sum).' WHERE uid='.$uid.
				' AND id='.$stackByPid[$propsId];
		}
		else
		{
			$sql = 'INSERT INTO userbag (pid,sums,uid) VALUES ('.intval($propsId).','.intval($sum).','.$uid.')';
		}
		if (!$db->query($sql) || mysql_affected_rows($db->getConn()) != 1)
		{
			tgFail('奖励物品发放失败，请重试！', true);
		}
	}

	if ($exp > 0)
	{
		$task = new task();
		$task->saveExps($exp, $id, $uid);
	}

	$sql = 'UPDATE userbb SET tgflag=0,tgstime=0,tgtime=0,tgmes=0'.
		' WHERE uid='.$uid.' AND id='.$id.' AND tgflag>0';
	if (!$db->query($sql) || mysql_affected_rows($db->getConn()) != 1)
	{
		tgFail('宠物托管状态更新失败，请重试！', true);
	}
	$afterPet = tgGetPet($uid, $id, false);
	tgCommit();

	$log = 'id:'.$id.'得经验:'.$exp.'level:'.$pet['level'].'->'.(is_array($afterPet) ? $afterPet['level'] : $pet['level']).
		'托管方式:'.$mode.'托管时间:'.($elapsed / 3600).'成长:'.$pet['czl'];
	$db->query('INSERT INTO gamelog (ptime,seller,buyer,pnote,vary) VALUES ('.time().','.$uid.','.$uid.','.
		$db->quote($log).',30)');
	die('10');
}

if ($action == 'show')
{
	$pet = $id > 0 ? tgGetPet($uid, $id, false) : false;
	if (!is_array($pet)) tgFail('请选择一个宠物!');
	if (intval($pet['tgflag']) == 0) tgFail('该宠物还没有托管或者已经取回！');
	if (time() < intval($pet['tgstime'])) tgFail('还没有到托管时间，您不能查看！');

	$elapsed = time() - intval($pet['tgstime']);
	if ($elapsed > intval($pet['tgtime'])) $elapsed = intval($pet['tgtime']);
	if ($elapsed < 0) $elapsed = 0;
	$ticks = intval($elapsed / 300);
	$multiplier = intval($pet['tgmes']) == 1 ? 1 : (intval($pet['tgmes']) == 2 ? 2 : 2.5);
	$exp = intval(round(intval($pet['level']) * (floatval($pet['czl']) / 40) * 2500 * $ticks * $multiplier));
	$str = '托管宠物：'.$pet['name']."\n";
	$str .= '托管前宠物等级：'.$pet['level']."\n";
	$str .= '托管时间：'.(intval($pet['tgtime']) / 3600)."小时\n";
	$str .= '当前已托管时间：'.round($elapsed / 60)."分钟\n";
	$str .= '托管获得经验：'.$exp."\n";
	$str .= '随机物品将在取回时结算';
	die($str);
}
?>
