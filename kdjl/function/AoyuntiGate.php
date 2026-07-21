<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.07.13
*@Usage: Aoyun
*@Note:
   1. 答题时间限制。16：00——17：00、20：00——21：00，
   2. 答题次数限制。2
   3. 是否已经完成答题。 qsums:已经答题总数。 oksum: 正确答题总数。  times：已经答题次数。 result: 是否领取奖励
   4. 单次答题最大限制为30道题。
*/
@session_start();
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');
require_once(dirname(__FILE__).'/aoyun_common.php');

define('MAX_QUESTION', 30);

secStart($_pm['mem']);

// time check.
$now = time();
$timearr1 = kdjlSafeMemValue($_pm['mem']->get(MEM_TIMENEW_KEY), array());
$timearr = (is_array($timearr1) && isset($timearr1['dati']) && is_array($timearr1['dati'])) ? $timearr1['dati'] : array();
if(!kdjlAoyunDateIsOpen($timearr, $now))
{
	die("101");
}
$checktime = kdjlAoyunActiveWindow($timearr, $now) !== false ? 1 : 0;
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1)
{
	die("100");
}

$sessionAoyunKey = $uid."aoyun";
if(!isset($_SESSION[$sessionAoyunKey]) || $_SESSION[$sessionAoyunKey] != "checked")
{
	die("100");
}
$aoyunQuestionGateLocked = false;
if(!function_exists('aoyunQuestionGateUnlock'))
{
	function aoyunQuestionGateUnlock()
	{
		global $aoyunQuestionGateLocked;
		if(!$aoyunQuestionGateLocked) return;
		if(function_exists('realseLock')) realseLock();
		$aoyunQuestionGateLocked = false;
	}
}
register_shutdown_function('aoyunQuestionGateUnlock');
if(!getScopedLock('aoyun', $uid, 5)) die('11');
$aoyunQuestionGateLocked = true;

$timeKey = 'time'.$uid;
$datiIdKey = 'datiid'.$uid;
$aoyunti = kdjlSafeMemValue($_pm['mem']->get(MEM_AOYUN_KEY), array());
$srctime = 3;


// 检查用户是否参与过该活动。

/*
$rs = $_pm['mysql']->getOneRecord("SELECT *
									 FROM aoyun_player
									WHERE uid={$_SESSION['id']}");
*/


if($checktime != 1)die('100');

#################增加一个间隔时间################
/*$time = $_SESSION['time'.$_SESSION['id']];
if(empty($time))
{
	$_SESSION['time'.$_SESSION['id']] = time();
}
else
{
	$nowtime = time();
	$ctime = $nowtime - $time;
	if($ctime < $srctime)
	{
		die("11");//没有达到间隔时间
	}
	else
	{
		$_SESSION['time'.$_SESSION['id']] = time();
	}
}*/
##################增加在这里结束#################

//

//$user	 = $_pm['user']->getUserById($_SESSION['id']);

$op = (isset($_REQUEST['op']) && !is_array($_REQUEST['op'])) ? $_REQUEST['op'] : '';
$key= (isset($_REQUEST['k']) && !is_array($_REQUEST['k'])) ? $_REQUEST['k'] : '';
if($op == 'getnum')
{
	$id = (isset($_REQUEST['q']) && !is_array($_REQUEST['q'])) ? intval($_REQUEST['q']) : 0;
	$arr = $_pm['mysql'] -> getOneRecord("SELECT qsums FROM aoyun_player WHERE uid = {$uid} ORDER BY id LIMIT 1");
	if(is_array($arr))
	{
		$num = $arr['qsums'];
	}
	else
	{
		$num = 1;
	}
	die($num);
}
else if($op == 'change')
{
	$tid = (isset($_REQUEST['p']) && !is_array($_REQUEST['p'])) ? intval($_REQUEST['p']) : 0;
	$state = $_pm['mysql']->getOneRecord("SELECT id,qsums FROM aoyun_player WHERE uid={$uid} ORDER BY id LIMIT 1");
	$currentOrder = is_array($state) && isset($state['qsums']) ? intval($state['qsums']) : 0;
	if(
		$tid < 1 ||
		$currentOrder < 1 || $currentOrder > MAX_QUESTION ||
		!isset($aoyunti[$tid]) ||
		!is_array($aoyunti[$tid]) ||
		!isset($_SESSION[$datiIdKey]) ||
		!is_array($_SESSION[$datiIdKey]) ||
		!array_key_exists($tid, $_SESSION[$datiIdKey]) ||
		intval($_SESSION[$datiIdKey][$tid]) !== $currentOrder
	){
		die('10');
	}
	$rowId = intval($state['id']);
	if($rowId < 1 || !$_pm['mysql'] -> query("UPDATE aoyun_player SET tid={$tid} WHERE id={$rowId} AND uid={$uid} AND qsums={$currentOrder}")) die('10');
}
else if ($op == 'cancel')
{
	$time = isset($_SESSION[$timeKey]) ? $_SESSION[$timeKey] : 0;
	if(empty($time))
	{
		$_SESSION[$timeKey] = time();
	}
	else
	{
		$nowtime = time();
		$ctime = $nowtime - $time;
		if($ctime < $srctime)
		{
			die("11");//没有达到间隔时间
		}
		else
		{
			$_SESSION[$timeKey] = time();
		}
	}
	$rs = $_pm['mysql']->getOneRecord("SELECT *
									FROM aoyun_player
								   WHERE uid={$uid}
								ORDER BY id LIMIT 1");
	if (!is_array($rs)) die('10');
	$qsums = isset($rs['qsums']) ? intval($rs['qsums']) : 0;
	$tid = isset($rs['tid']) ? intval($rs['tid']) : 0;

	if ($qsums > MAX_QUESTION)
	{
		die('100'); // 当前次数完成。
	}

	//$question = randq();

	if(!isset($_SESSION[$datiIdKey]) || !is_array($_SESSION[$datiIdKey]) ||
	   !isset($_SESSION[$datiIdKey][$tid]) || intval($_SESSION[$datiIdKey][$tid]) !== $qsums) die('10');
	if ($qsums == MAX_QUESTION)
	{
		$times = 1;$result=1;
	}else {$times=0;$result=0;}

	if (is_array($rs))
	{
		$rowId = isset($rs['id']) ? intval($rs['id']) : 0;
		$updated = $rowId > 0 && $_pm['mysql']->query("UPDATE aoyun_player
								 SET stime=unix_timestamp(),
									 qsums=qsums+1,
									 times=times+{$times},
									 result={$result}
							   WHERE id={$rowId} AND uid={$uid} AND qsums={$qsums} AND tid={$tid}
							     AND stime<=unix_timestamp()-{$srctime}");
		if(!$updated) die('10');
		if(mysql_affected_rows($_pm['mysql']->getConn()) != 1) die('11');
		unset($_SESSION[$datiIdKey][$tid]);
	}
}
else if ($op == "re") // 玩家答题。
{
	$time = isset($_SESSION[$timeKey]) ? $_SESSION[$timeKey] : 0;
	if(empty($time))
	{
		$_SESSION[$timeKey] = time();
	}
	else
	{
		$nowtime = time();
		$ctime = $nowtime - $time;
		if($ctime < $srctime)
		{
			die("11");//没有达到间隔时间
		}
		else
		{
			$_SESSION[$timeKey] = time();
		}
	}

	$_SESSION[$uid."dati"] = time();
	$q= (isset($_REQUEST['q']) && !is_array($_REQUEST['q'])) ? intval($_REQUEST['q']) : 0;
	$rs = $_pm['mysql']->getOneRecord("SELECT *
									FROM aoyun_player
								   WHERE uid={$uid}
								ORDER BY id LIMIT 1");
    if (!is_array($rs))
	{
		die('10'); // error
	}
    else
	{
		$today = kdjlAoyunTodayStart($now);
		$qsums = isset($rs['qsums']) ? intval($rs['qsums']) : 0;
		$stime = isset($rs['stime']) ? intval($rs['stime']) : 0;
		$tid = isset($rs['tid']) ? intval($rs['tid']) : 0;
		if ( ($qsums > MAX_QUESTION) && $stime > $today )
		{
			die('100'); // 当前次数完成。
		}
		else if ($stime < $today) die('100');
		if ($qsums == MAX_QUESTION)
		{
			$times = 1;
			$result=1;
		}else {$times=0;$result=0;}

		//$qrs = $_pm['mysql']->getOneRecord("SELECT k FROM aoyun WHERE id={$rs['tid']}");
		if($tid < 1 || !isset($aoyunti[$tid]) || !is_array($aoyunti[$tid])) die('10');
		$qrs = $aoyunti[$tid];
		if(!isset($_SESSION[$datiIdKey]) || !is_array($_SESSION[$datiIdKey])){
			$_SESSION[$datiIdKey] = array();
		}
		//$ti = randq();
		//echo $rs['tid'];print_r($_SESSION['datiid'.$_SESSION['id']]);exit;
		if(!array_key_exists($tid,$_SESSION[$datiIdKey]) || intval($_SESSION[$datiIdKey][$tid]) !== $qsums)
		{
			die("您不能回答这道题!");
		}
		$rowId = isset($rs['id']) ? intval($rs['id']) : 0;
		if($rowId < 1) die('10');
		if (isset($qrs['k']) && strtoupper($qrs['k']) == strtoupper($key))
		{
			$updated = $_pm['mysql']->query("UPDATE aoyun_player
									SET oksum=oksum+1,
										stime=unix_timestamp(),
										qsums=qsums+1,
										times=times+{$times},
										result={$result}
								  WHERE id={$rowId} AND uid={$uid} AND qsums={$qsums} AND tid={$tid}
								    AND stime<=unix_timestamp()-{$srctime}");
			if(!$updated) die('10');
			if(mysql_affected_rows($_pm['mysql']->getConn()) != 1) die('11');
			unset($_SESSION[$datiIdKey][$tid]);
			die('2'); // 回答正确 。
		}
		else // 回答错误。
		{
			$updated = $_pm['mysql']->query("UPDATE aoyun_player
									SET stime=unix_timestamp(),
										qsums=qsums+1,
										times=times+{$times},
										result={$result}
								  WHERE id={$rowId} AND uid={$uid} AND qsums={$qsums} AND tid={$tid}
								    AND stime<=unix_timestamp()-{$srctime}");
			if(!$updated) die('10');
			if(mysql_affected_rows($_pm['mysql']->getConn()) != 1) die('11');
			unset($_SESSION[$datiIdKey][$tid]);
			die('3');
		}
	}
}
die('1'); // go next.

/**
@Usage: rand get one question.
@Return: array.
*/
function randq( )
{
	global $_pm,$aoyunti;
	$ti = array();
	//$ret = $_pm['mysql']->getRecords("SELECT * FROM aoyun");
	//$ret = unserialize($_pm['mem']->get(MEM_AOYUN_KEY));
	if(!is_array($aoyunti)) return array();
	$pool = array();
	foreach($aoyunti as $row)
	{
		if(is_array($row) && isset($row['title']) && $row['title'] != '')
		{
			$pool[] = $row;
		}
	}
	while(count($pool) > 0)
	{
		$num = rand(0, count($pool) - 1);
		$ti[] = $pool[$num];
		array_splice($pool, $num, 1);
	}
	return $ti;
}
?>
