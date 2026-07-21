<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.07.13
*@Usage: 奥运答题显示模块
* 加入奥运时间限制。
× 最大答题次数限制。
*@Note: none
*/
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');
require_once(dirname(__FILE__).'/aoyun_common.php');
secStart($_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) exit;
$aoyunQuestionModLocked = false;
if(!function_exists('aoyunQuestionModUnlock'))
{
	function aoyunQuestionModUnlock()
	{
		global $aoyunQuestionModLocked;
		if(!$aoyunQuestionModLocked) return;
		if(function_exists('realseLock')) realseLock();
		$aoyunQuestionModLocked = false;
	}
}
register_shutdown_function('aoyunQuestionModUnlock');
$user	 = $_pm['user']->getUserById($uid);
//Word part.
//$taskword= taskcheck($user['task'],6);
$taskword = '';
$king = '';

$aoyunti = kdjlSafeMemValue($_pm['mem']->get(MEM_AOYUN_KEY), array());
if(!is_array($aoyunti) || empty($aoyunti)) exit;
$maxQuestion = 30;

// 加入时间段限制开始
// time limit start
$now = time();
$timearr1 = kdjlSafeMemValue($_pm['mem']->get(MEM_TIMENEW_KEY), array());
$timearr = (is_array($timearr1) && isset($timearr1['dati']) && is_array($timearr1['dati'])) ? $timearr1['dati'] : array();
if(kdjlAoyunActiveWindow($timearr, $now) === false)
{
	exit;
}
if(!getScopedLock('aoyun', $uid, 5)) exit;
$aoyunQuestionModLocked = true;
$_SESSION[$uid."aoyun"] = "checked";
// 加入时间段限制结束

// 检查用户是否参与过该活动。




$rs = $_pm['mysql']->getOneRecord("SELECT *
									 FROM aoyun_player
									WHERE uid={$uid}
								 ORDER BY id LIMIT 1");
$todayStart = kdjlAoyunTodayStart($now);
$questionarrs = false;

if (!is_array($rs))
{
	$questionarrs = randq($uid);
	if(count($questionarrs) < $maxQuestion || !isset($questionarrs[1]['id'])) exit;
	$tid = intval($questionarrs[1]['id']);
	if($tid < 1 || !$_pm['mysql']->query("INSERT INTO aoyun_player(uid,stime,tid,qsums,oksum,times,result)
						  VALUES({$uid},unix_timestamp(),{$tid},1,0,0,0)")) exit;
	$rs = array('id'=>intval(mysql_insert_id($_pm['mysql']->getConn())), 'tid'=>$tid, 'qsums'=>1, 'stime'=>$now, 'oksum'=>0, 'times'=>0, 'result'=>0);
}
else if(!isset($rs['stime']) || intval($rs['stime']) < $todayStart)
{
	$questionarrs = randq($uid);
	if(count($questionarrs) < $maxQuestion || !isset($questionarrs[1]['id'])) exit;
	$rs['tid'] = intval($questionarrs[1]['id']);
	$rs['qsums'] = 1;
	$rs['stime'] = $now;
	$rowId = isset($rs['id']) ? intval($rs['id']) : 0;
	if($rowId < 1 || $rs['tid'] < 1 || !$_pm['mysql']->query("UPDATE aoyun_player
								 SET qsums=1,
								     tid={$rs['tid']},
									 stime=unix_timestamp(),
									 oksum=0,
									 result=0,
									 times=0
							   WHERE id={$rowId} AND uid={$uid}")) exit;
}
else
{
	$rs['qsums'] = isset($rs['qsums']) ? intval($rs['qsums']) : 0;
	if($rs['qsums'] > $maxQuestion) exit;
	if($rs['qsums'] < 1)
	{
		$rowId = isset($rs['id']) ? intval($rs['id']) : 0;
		if($rowId < 1 || !$_pm['mysql']->query("UPDATE aoyun_player SET qsums=1 WHERE id={$rowId} AND uid={$uid}")) exit;
		$rs['qsums'] = 1;
	}
	$tiarr = kdjlSafeMemValue($_pm['mem']->get('quest'.$uid), array());
	if(is_array($tiarr) && count($tiarr) >= $maxQuestion){
		$questionarrs = $tiarr;
	}else{
		$questionarrs = randq($uid);
	}
}
$_SESSION['datiid'.$uid] = array();
if(!is_array($questionarrs) || count($questionarrs) < $maxQuestion) exit;
foreach($questionarrs as $k=>$v)
{
	if(intval($k) >= intval($rs['qsums']) && is_array($v) && isset($v['id']))
	{
		$_SESSION['datiid'.$uid][intval($v['id'])] = intval($k);
	}
}//print_r($_SESSION['datiid'.$_SESSION['id']]);
$questionarr = json_encode($questionarrs);
if($questionarr === false) exit;

$rs['qsums'] = isset($rs['qsums']) ? intval($rs['qsums']) : 0;
if(!isset($questionarrs[$rs['qsums']]['id'])) exit;
$rs['tid'] = $questionarrs[$rs['qsums']]['id'];
if(!empty($rs['tid']))
{
	$rowId = isset($rs['id']) ? intval($rs['id']) : 0;
	if($rowId < 1 || !$_pm['mysql']->query("UPDATE aoyun_player
								 SET
								     tid={$rs['tid']}
							   WHERE id={$rowId} AND uid={$uid}")) exit;
}
// 获得所答题信息。
//$qst = $_pm['mysql']->getOneRecord("SELECT * FROM aoyun WHERE id={$rs['tid']}");
if(!isset($aoyunti[$rs['tid']]) || !is_array($aoyunti[$rs['tid']])) exit;
$qst = $aoyunti[$rs['tid']];
$qst['title'] = isset($qst['title']) ? $qst['title'] : '';
$qst['a'] = isset($qst['a']) ? $qst['a'] : '';
$qst['b'] = isset($qst['b']) ? $qst['b'] : '';
$qst['c'] = isset($qst['c']) ? $qst['c'] : '';
$qst['d'] = isset($qst['d']) ? $qst['d'] : '';



//@Load template.
$tn = $_game['template'] . 'tpl_aoyunti.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);

	$src = array('#word#',
				 '#order#',
				 '#title#',
		         '#akey#',
				 '#bkey#',
		         '#ckey#',
		         '#dkey#',
				 '#questionarr#'
				);
	$des = array(
				 $taskword,
		         $rs['qsums'],
		         $qst['title'],
		         $qst['a'],
		         $qst['b'],
		         $qst['c'],
		         $qst['d'],
				 $questionarr
				);
	$king = str_replace($src, $des, $tpl);
}
// gzip echo. if maybe.
ob_start('ob_gzip');
echo $king;
ob_end_flush();



function randq($uid)
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
	for($i = 1;$i <= 30 && count($pool) > 0;$i++)
	{
		$num = rand(0, count($pool) - 1);
		$row = $pool[$num];
		array_splice($pool, $num, 1);
		$ti[$i]['id'] = isset($row['id']) ? $row['id'] : 0;
		$ti[$i]['title'] = isset($row['title']) ? $row['title'] : '';
		$ti[$i]['a'] = isset($row['a']) ? $row['a'] : '';
		$ti[$i]['b'] = isset($row['b']) ? $row['b'] : '';
		$ti[$i]['c'] = isset($row['c']) ? $row['c'] : '';
		$ti[$i]['d'] = isset($row['d']) ? $row['d'] : '';
	}
	 $_pm['mem']->set(array('k' => 'quest'.$uid, 'v' => $ti));
	return $ti;
}
?>
