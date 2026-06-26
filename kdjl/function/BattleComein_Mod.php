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
	define(BATTLE_TIME_START, "20:00");
if (!defined('BATTLE_TIME_END'))
	define(BATTLE_TIME_END, "22:00");
if (!defined('BATTLE_TIME_WEEK'))
	define(BATTLE_TIME_WEEK, 5);*/

secStart($_pm['mem']);
$_pm['mysql']->query('update player set inmap=0 where id='.$_SESSION['id']);
$today = date("Y-m-d",time());
														 
// 战场开放时间开关。
$week = date("N", time());
$hourM= date("H:i", time());

$battletimearr = unserialize($_pm['mem']->get(MEM_TIME_KEY));
$battletimearr1 = unserialize($_pm['mem']->get('db_welcome1'));
$activeimg = $battletimearr1['battle'];

foreach($battletimearr as $bv)
{
	if($bv['titles'] != "battle")
	{
		continue;
	}
	if($week == $bv['days'] && ($hourM >= $bv['starttime'] && $hourM < $bv['endtime']))//战场已经开始
	{
		$exist = $_pm['mysql']->getOneRecord("SELECT startf,hp,ends
											 FROM battlefield 
										    WHERE id=1 and startf=0");
		if($exist['ends'] == 1)
		{
			$_pm['mysql'] -> query("UPDATE battlefield SET ends = 2");
		}
		$exists = $_pm['mysql']->getOneRecord("SELECT startf,hp,ends
												 FROM battlefield 
												WHERE id=1 and startf=0");
		
		if (is_array($exists) && $exists['startf'] == 0 && $exists['ends']==2) // 当前战场第一次开启，记录开启标记，并更新相关信息。
		{
					
			$_pm['mysql']->query("UPDATE battlefield
											SET startf=1,countf=0,success=0,ends=0,hp=srchp
									   ");
			// 初始所有用户的排名及当前军功值
			//##############################加入军功记载 11.06 谭炜#########################################3
			//之前记录没有开放战场的时候的用户的军功
			$sql = "SELECT jgvalue,curjgvalue,uid FROM battlefield_user WHERE jgvalue > 0 or curjgvalue > 0";
			$row = $_pm['mysql'] -> getRecords($sql);
			if(is_array($row))
			{
				$_pm['mysql'] -> query("DELETE FROM battlelog");
				$time = time();
				foreach($row as $v)
				{
					$_pm['mysql'] -> query("INSERT INTO battlelog (uid,jgvalue,curjgvalue,jgtime) VALUES ({$v['uid']},{$v['jgvalue']},{$v['curjgvalue']},{$time})");
				}
			}
			//###################################在这里结束####################################3
			$_pm['mysql']->query("UPDATE battlefield_user
											SET tops=0,jgvalue=jgvalue+curjgvalue,curjgvalue=0
									   ");
			//##############################加入军功记载 11.06 谭炜#########################################3
			$sql = "SELECT jgvalue,uid FROM battlefield_user WHERE jgvalue > 0 or curjgvalue > 0";
			$rs = $_pm['mysql'] -> getRecords($sql);
			$logarr = $_pm['mysql'] -> getRecords("SELECT uid FROM battlelog");
			if(is_array($logarr))
			{
				foreach($logarr as $r)
				{
					$idarr[] = $r['uid'];
				}
			}
			if(is_array($rs) && is_array($idarr))
			{
				foreach($rs as $ks => $vs)
				{
					if(in_array($vs['uid'],$idarr))
					{
						$_pm['mysql'] -> query("UPDATE battlelog SET sumjg = {$vs['jgvalue']} WHERE uid = {$vs['uid']}");
					}
				}
			}
			//###################################在这里结束####################################3
		}
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
		$zrlist .= "<tr><td>".(++$k)."</td><td>{$v['nickname']}</td><td>{$v['jgvalue']}</td></tr>";
	}
}
else $zrlist .= '';

if (is_array($topay))
{
	foreach ($topay as $k => $v)
	{
		$aylist .= "<tr><td>".(++$k)."</td><td>{$v['nickname']}</td><td>{$v['jgvalue']}</td></tr>";
	}
}
else $aylist .= '';

// Online left user for battle field.
$zrsum = $_pm['mysql']->getOneRecord("SELECT count(id) as cnt
										FROM battlefield_user 
									   WHERE lastvtime>unix_timestamp('{$today}') and pos=1
									");

$zrpsum=is_array($zrsum)?$zrsum['cnt']:0;
$aysum = $_pm['mysql']->getOneRecord("SELECT count(id) as cnt
										FROM battlefield_user 
									   WHERE lastvtime>unix_timestamp('{$today}') and pos=2
									");
$aypsum=is_array($aysum)?$aysum['cnt']:0;

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