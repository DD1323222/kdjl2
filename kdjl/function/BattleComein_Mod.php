<?php
/**
*@Usage: 战场入口
*@Author: GeFei Su.
*@Write Date:2008-08-27
*@Copyright:www.webgame.com.cn
Note:
    2: 重新开始.
	1: 战场结束.
	0: 战场初始值
*/
session_start();
set_time_limit(3600);
require_once('../config/config.game.php');

/*if (!defined('BATTLE_TIME_START'))
	define('BATTLE_TIME_START', "20:00");
if (!defined('BATTLE_TIME_END'))
	define('BATTLE_TIME_END', "22:00");
if (!defined('BATTLE_TIME_WEEK'))
	define('BATTLE_TIME_WEEK', 5);*/

secStart($_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');
if($_pm['mysql']->query('update player set inmap=0 where id='.$uid)) $_pm['mem']->del(MEM_USER_KEY);
$today = date("Y-m-d",time());

// 战场开放时间开关。
$week = date("N", time());
$hourM= date("H:i", time());

$battletimearr = $_pm['mem']->get(MEM_TIME_KEY);
if(!is_array($battletimearr)) $battletimearr = kdjlSafeMemValue($battletimearr, array());
if(!is_array($battletimearr)) $battletimearr = array();
$battletimearr1 = $_pm['mem']->get('db_welcome1');
if(!is_array($battletimearr1)) $battletimearr1 = kdjlSafeMemValue($battletimearr1, array());
$activeimg = (is_array($battletimearr1) && isset($battletimearr1['battle'])) ? $battletimearr1['battle'] : '';
$zrlist = '';
$aylist = '';
$cet = '';

function battleStartRoundIfNeeded()
{
	global $_pm;
	if(!$_pm['mysql']->query('START TRANSACTION')) return false;
	$state = $_pm['mysql']->getOneRecord('SELECT id,startf,ends FROM battlefield WHERE id=1 FOR UPDATE');
	if(!is_array($state))
	{
		$_pm['mysql']->query('ROLLBACK');
		return false;
	}
	if(intval($state['startf']) != 0)
	{
		if(!$_pm['mysql']->query('COMMIT'))
		{
			$_pm['mysql']->query('ROLLBACK');
			return false;
		}
		return true;
	}

	$ends = intval($state['ends']);
	if($ends == 1)
	{
		if(!$_pm['mysql']->query('UPDATE battlefield SET ends=2 WHERE ends=1'))
		{
			$_pm['mysql']->query('ROLLBACK');
			return false;
		}
		$ends = 2;
	}
	if($ends == 2)
	{
		$now = time();
		$sql = 'INSERT INTO battlelog(uid,jgvalue,curjgvalue,jgtime,sumjg) '.
			'SELECT uid,COALESCE(jgvalue,0),COALESCE(curjgvalue,0),'.$now.
			',COALESCE(jgvalue,0)+COALESCE(curjgvalue,0) FROM battlefield_user '.
			'WHERE COALESCE(jgvalue,0)>0 OR COALESCE(curjgvalue,0)>0';
		if(!$_pm['mysql']->query('DELETE FROM battlelog') ||
		   !$_pm['mysql']->query($sql) ||
		   !$_pm['mysql']->query('UPDATE battlefield SET startf=1,countf=0,success=0,ends=0,hp=srchp') ||
		   !$_pm['mysql']->query('UPDATE battlefield_user SET tops=0,jgvalue=COALESCE(jgvalue,0)+COALESCE(curjgvalue,0),curjgvalue=0'))
		{
			$_pm['mysql']->query('ROLLBACK');
			return false;
		}
	}
	if(!$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		return false;
	}
	return true;
}

foreach($battletimearr as $bv)
{
	if(!is_array($bv)) continue;
	if(!isset($bv['titles'])) $bv['titles'] = '';
	if(!isset($bv['days'])) $bv['days'] = '';
	if(!isset($bv['starttime'])) $bv['starttime'] = '';
	if(!isset($bv['endtime'])) $bv['endtime'] = '';
	if($bv['titles'] != "battle")
	{
		continue;
	}
	if(isWeeklyDayTimeActive($bv['days'], $bv['starttime'], $bv['endtime'], $week, $hourM, false))//战场已经开始
	{
		if(!battleStartRoundIfNeeded()) die('战场初始化失败，请稍候重试！');
		break;
	}
}



// 左边阵营军功排名
$topzr = $_pm['mysql']->getRecords("SELECT b.curjgvalue as jgvalue,p.nickname as nickname
								      FROM player as p,battlefield_user as b
									 WHERE p.id=b.uid and b.pos=1 and b.curjgvalue>0
									 ORDER BY b.curjgvalue desc
									 LIMIT 0,10
								  ");

// 右边阵营军功排名
$topay = $_pm['mysql']->getRecords("SELECT b.curjgvalue as jgvalue,p.nickname as nickname
								      FROM player as p,battlefield_user as b
									 WHERE p.id=b.uid and b.pos=2 and b.curjgvalue>0
									 ORDER BY b.curjgvalue desc
									 LIMIT 0,10
								  ");

if (is_array($topzr))
{
	foreach ($topzr as $k => $v)
	{
		$nickname = (is_array($v) && isset($v['nickname'])) ? $v['nickname'] : '';
		$nickname = htmlspecialchars((string)$nickname, ENT_QUOTES, 'UTF-8');
		$jgvalue = (is_array($v) && isset($v['jgvalue'])) ? intval($v['jgvalue']) : 0;
		$zrlist .= "<tr><td>".(++$k)."</td><td>{$nickname}</td><td>{$jgvalue}</td></tr>";
	}
}
else $zrlist .= '';

if (is_array($topay))
{
	foreach ($topay as $k => $v)
	{
		$nickname = (is_array($v) && isset($v['nickname'])) ? $v['nickname'] : '';
		$nickname = htmlspecialchars((string)$nickname, ENT_QUOTES, 'UTF-8');
		$jgvalue = (is_array($v) && isset($v['jgvalue'])) ? intval($v['jgvalue']) : 0;
		$aylist .= "<tr><td>".(++$k)."</td><td>{$nickname}</td><td>{$jgvalue}</td></tr>";
	}
}
else $aylist .= '';

// Online left user for battle field.
$zrsum = $_pm['mysql']->getOneRecord("SELECT count(id) as cnt
										FROM battlefield_user
									   WHERE lastvtime>unix_timestamp('{$today}') and pos=1
									");

$zrpsum=is_array($zrsum)?intval($zrsum['cnt']):0;
$aysum = $_pm['mysql']->getOneRecord("SELECT count(id) as cnt
										FROM battlefield_user
									   WHERE lastvtime>unix_timestamp('{$today}') and pos=2
									");
$aypsum=is_array($aysum)?intval($aysum['cnt']):0;

//###########################
// @Load template.
//###########################
$tn = $_game['template'] . 'tpl_battle_comein.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);

	$src = array('#zrpsum#',
				 '#aypsum#',
	             '#zrlist#',
		         '#aylist#',
				 '#activity_dis#'
				);
	$des = array($zrpsum,
		         $aypsum,
		         $zrlist,
		         $aylist,
				 $activeimg
				);
	$cet = str_replace($src, $des, $tpl);
}
// gzip echo. if maybe.
ob_start('ob_gzip');
echo $cet;
ob_end_flush();
?>
