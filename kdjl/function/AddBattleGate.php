<?php 
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Usage: 加入阵营接口。
   需要： 1. 玩家是否已经加入阵营。
   		  2. 验证双方人数是否达到最大数。
          3. 验证双方人数的差距，是否允许加入玩家选择的阵营。

*@Write Date: 2008.08.27
*@Usage: Aoyun
*@Note: 
*/
require_once('../config/config.game.php');

/*if (!defined('BATTLE_TIME_START'))
	define(BATTLE_TIME_START, "20:00");
if (!defined('BATTLE_TIME_END'))
	define(BATTLE_TIME_END, "22:00");
if (!defined('BATTLE_TIME_WEEK'))
	define(BATTLE_TIME_WEEK, 5);*/

secStart($_pm['mem']);
$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");	
$n = intval($_REQUEST['n']);
if ($n!=1 && $n!=2) die('0');

$today = date("Y-m-d",time());
$user  = $_pm['user']->getUserById($_SESSION['id']);
$battleinfo = $_pm['mysql']->getOneRecord("SELECT maxuser,bf_ml_num,bf_level_limit,level_get,ends
                                             FROM battlefield
											WHERE id={$n}
										 ");
										

// 战场开放时间开关。
$week = date("N", time());
$hourM= date("H:i", time());

$battletimearr = unserialize($_pm['mem']->get(MEM_TIME_KEY));

foreach($battletimearr as $v)
{
	if($v['titles'] == "battle")
	{
		if(empty($days))
		{
			$days = $v['days'];
		}
		else
		{
			$days .= ",".$v['days'];
		}
	}
}

foreach($battletimearr as $bv)
{
	if($bv['titles'] != "battle")
	{
		continue;
	}
	if($week == $bv['days'] && $hourM >= $bv['starttime'] && $hourM <= $bv['endtime'])
	{
		$checkstr = 1;
		break;
	}
	else if ($week != $bv['days'] || ($hourM < $bv['starttime'] || $hourM > $bv['endtime']) )
	{
		$str = '战场未开启！战场开放时间：每周'
			 . str_replace(array(1,2,3,4,5,6,7),array('一','二','三','四','五','六','日'),$days)
			 .' '.$bv['starttime']. '点-'. $bv['endtime'] .'点开放！';
	}
	else if ($battleinfo['ends'] == 1)
	{
		$str = '战场已经结束！战场开放时间：每周'
			 . str_replace(array(1,2,3,4,5,6,7),array('一','二','三','四','五','六','日'),$days)
			 .' '.$bv['starttime']. '点-'. $bv['endtime'] .'点开放！';
	}
}

if(empty($checkstr))
{
	die($str);
}
// ####### end ##############################

// 玩家等级验证。
$main_bb = $_pm['mysql']->getOneRecord("SELECT czl
										  FROM userbb
										 WHERE id={$user['mbid']}
										");
if ($main_bb['czl'] < $battleinfo['bf_level_limit']) die("您的主战宠物成长不够，进入阵营主战宠物需要 {$battleinfo['bf_level_limit']} 成长!");

// 获得所选阵营的当前人数。
$zrsum = $_pm['mysql']->getOneRecord("SELECT count(id) as cnt
										FROM battlefield_user 
									   WHERE lastvtime>unix_timestamp('{$today}') and pos={$n}
									");
// 获得所选阵营的当前人数。
$dessum = $_pm['mysql']->getOneRecord("SELECT count(id) as cnt
										FROM battlefield_user 
									   WHERE lastvtime>unix_timestamp('{$today}') and pos!={$n}
									");

$currentNum = is_array($zrsum)?$zrsum['cnt']:0;
$desNum = is_array($dessum)?$dessum['cnt']:0;

if (is_array($battleinfo) && $battleinfo['maxuser'] == $currentNum) die('本阵营人数已满！');
else  
{ 
    // 验证双方相差的人数。
    if ($currentNum-$desNum >= $battleinfo['bf_ml_num']) die('我方当前人数超过对方至少 '.$bf_ml_num.' 人，已足够剿灭对方，请您稍后再来！');
	
	// 允许玩家加入阵营,玩家以前是否加入阵营
	$exists =$_pm['mysql']->getOneRecord("SELECT uid,lastvtime,bid,pos
									        FROM battlefield_user
										   WHERE uid={$_SESSION['id']}
										");

	//ex format: 30-45:10:1|0:1,46-60:20:1|0:1,61-70:30:2|0:1,71-80:40:2|0:1,81-90:50:3|0:1,91-100:60:3|0:1
	$par = explode(',', $battleinfo['level_get']);
	foreach ($par as $k => $v)
	{
		$inparrt = explode(':', $v, 2);
		$inparr  = explode('-', $inparrt[0]);

		//if ($main_bb['level'] >= $inparr[0] && $main_bb['level']<= $inparr[1]) // 找到对应等级。
		if ($main_bb['czl'] >= 10 && $main_bb['czl'] >= $inparr[0] && $main_bb['czl']<= $inparr[1])
		{
			// levels, addjgvalue, ackvalue, failjgvalue, failackvalue, lastvtime
			$att = explode('|', $inparrt[1]); // 获得各项战场属性值
			$onepart = explode(':', $att[0]); // 成功部分影响值
			$twopart = explode(':', $att[1]); // 失败部分影响值
			if (is_array($exists)) // 有玩家的战场记录，更新时间，主战宠物，能进入的级别及攻击值等。
			{
				// 玩家是否已经加入战场
				if (date("Y-m-d",$exists['lastvtime']) >= $today)
				{
				   $_SESSION['jgbug'] .= __LINE__." B <br>\n";
				    if ($exists['pos']!=$n) die('2'); // 不能加入其它阵营。
					else 
					{
						$_pm['mysql']->query("UPDATE battlefield_user
											 SET addjgvalue={$onepart[0]},
												 ackvalue={$onepart[1]},
												 failjgvalue={$twopart[0]},
												 failackvalue={$twopart[1]},
												 bid={$user['mbid']},
												 levels='{$inparrt[0]}'
										   WHERE uid={$_SESSION['id']}
										 ");

						die('3');  // 已经加入阵营，不用再加入。
					}
				}
				else if ($main_bb['czl'] >= $inparr[0] && $main_bb['czl']<= $inparr[1])
				{
					// 更新加入阵营时间。
					$_pm['mysql']->query("UPDATE battlefield_user
											 SET lastvtime=unix_timestamp(),
												 addjgvalue={$onepart[0]},
												 ackvalue={$onepart[1]},
												 failjgvalue={$twopart[0]},
												 failackvalue={$twopart[1]},
												 doublejg=0,
												 pos={$n},
												 tops=0,
												 jgvalue=curjgvalue+jgvalue,
												 curjgvalue=0,
												 boxnum=0,
												 bid={$user['mbid']},
												 nscf=0,
												 subhp=0,
												 addhp=0,
												 levels='{$inparrt[0]}'
										   WHERE uid={$_SESSION['id']}
										 ");
					$_SESSION['jgbug'] .= __LINE__." A <br>\n";
					die('1');  // 加入成功！
					break;
				}
			}
			else
			{
				$_SESSION['jgbug'] .= __LINE__." C <br>\n";
				$_pm['mysql']->query("INSERT INTO battlefield_user(uid,pos,bid,jgvalue,levels,addjgvalue,ackvalue,failjgvalue,failackvalue,lastvtime)
									  VALUES({$_SESSION['id']},{$n},{$user['mbid']},0,'{$inparrt[0]}',{$onepart[0]},
											 {$onepart[1]},{$twopart[0]},{$twopart[1]},unix_timestamp()
									        )
									 ");
				die('1');  // 加入成功！
				break;
			}
		}else continue;
	} // end foreach.
}
?>