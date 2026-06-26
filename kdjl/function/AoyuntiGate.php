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

define(MAX_QUESTION, 30);

secStart($_pm['mem']);

// time check.
$tl = intval(date("H",time()));
$day = intval(date("Ymd"));

$timearr1 = unserialize($_pm['mem']->get(MEM_TIMENEW_KEY));
$timearr = $timearr1['dati'];
foreach($timearr as $k => $v)
{
	$dayarr = explode("-",$v['days']);
	if($tl >= $v['starttime'] && $tl < $v['endtime'])
	{
		$checktime = 1;
		break;
	}
}
if($day < $dayarr[0] || $day > $dayarr[1])
{
	die("101");
}
if($_SESSION[$_SESSION['id']."aoyun"] != "checked")
{
	$_SESSION['id'] == "";
}

$_SESSION[$_SESSION['id']."aoyun"] = "";
$aoyunti = unserialize($_pm['mem']->get(MEM_AOYUN_KEY));
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

$op = $_REQUEST['op'];
$key= $_REQUEST['k'];
if($op == 'getnum')
{
	$id = intval($_REQUEST['q']);
	$arr = $_pm['mysql'] -> getOneRecord("SELECT qsums FROM aoyun_player WHERE uid = {$_SESSION['id']}");
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
	$tid = intval($_REQUEST['p']);
	$_pm['mysql'] -> query("UPDATE aoyun_player SET tid = $tid WHERE uid = {$_SESSION['id']}");
}
else if ($op == 'cancel')
{
	$time = $_SESSION['time'.$_SESSION['id']];
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
	}
	$rs = $_pm['mysql']->getOneRecord("SELECT *
									FROM aoyun_player 
								   WHERE uid={$_SESSION['id']}");
	if (!is_array($rs)) die('10');
	
	if ($rs['qsums'] > MAX_QUESTION)
	{
		die('100'); // 当前次数完成。
	}
	
	//$question = randq();
	
	unset($_SESSION['datiid'.$_SESSION['id']][$rs['tid']]);
	if ($rs['qsums']== MAX_QUESTION)
	{
		$times = 1;$result=1;
	}else {$times=0;$result=0;}
	
	if (is_array($rs))
	{
		$_pm['mysql']->query("UPDATE aoyun_player 
								 SET stime=unix_timestamp(),
									 qsums=qsums+1,
								 	 times=times+{$times},
									 result={$result}
							   WHERE uid={$_SESSION['id']}
							");
	}
}
else if ($op == "re") // 玩家答题。
{
	$time = $_SESSION['time'.$_SESSION['id']];
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
	}
	
	$_SESSION[$_SESSION['id']."dati"] = time();
	$q=intval($_REQUEST['q']);
	$rs = $_pm['mysql']->getOneRecord("SELECT *
									FROM aoyun_player 
								   WHERE uid={$_SESSION['id']}");
    if (!is_array($rs))
	{
		die('10'); // error
	}
    else
	{	
		$today = mktime(0,0,0, date("m",time()),date("d",time())-1,date("Y",time()) );
		if ( ($rs['qsums'] > MAX_QUESTION) && $rs['stime'] > $today )
		{
			die('100'); // 当前次数完成。
		}
		else if ($rs['stime']<$today)
		{
			$_pm['mysql']->query("UPDATE aoyun_player
								     SET qsums=1
								   WHERE uid={$_SESSION['id']}
								");
		}
		if ($rs['qsums'] == MAX_QUESTION)
		{
			$times = 1;
			$result=1;
		}else {$times=0;$result=0;}
		
		//$qrs = $_pm['mysql']->getOneRecord("SELECT k FROM aoyun WHERE id={$rs['tid']}");
		$qrs = $aoyunti[$rs['tid']];
		if(!is_array($_SESSION['datiid'.$_SESSION['id']])){
			$_SESSION['datiid'.$_SESSION['id']] = array();
		}
		//$ti = randq();
		//echo $rs['tid'];print_r($_SESSION['datiid'.$_SESSION['id']]);exit;
		if(!array_key_exists($rs['tid'],$_SESSION['datiid'.$_SESSION['id']]))
		{
			die("您不能回答这道题!");
		}
		unset($_SESSION['datiid'.$_SESSION['id']][$rs['tid']]);
		
		if (strtoupper($qrs['k']) == strtoupper($key))
		{
			$_pm['mysql']->query("UPDATE aoyun_player
									SET oksum=oksum+1,
										stime=unix_timestamp(),
										qsums=qsums+1,
										times=times+{$times},
										result={$result}
								  WHERE uid={$_SESSION['id']}
							   ");
			die('2'); // 回答正确 。
		}
		else // 回答错误。
		{
			$_pm['mysql']->query("UPDATE aoyun_player
									SET stime=unix_timestamp(),
										qsums=qsums+1,
										times=times+{$times},
										result={$result}
								  WHERE uid={$_SESSION['id']}
							   ");
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
	$ti = "";
	//$ret = $_pm['mysql']->getRecords("SELECT * FROM aoyun");
	//$ret = unserialize($_pm['mem']->get(MEM_AOYUN_KEY));
	
	$num1 = count($aoyunti) - 1;
	for($i = 0;$i <= $num1;$i++)
	{
		$num = 1;
		$num = rand(1,$num1);
		if($aoyunti[$num]['title'] != "")
		{
			$ti[] = $aoyunti[$num];
		}
	}
	print_r($ti);
	exit;
	return $ti;
}
?>