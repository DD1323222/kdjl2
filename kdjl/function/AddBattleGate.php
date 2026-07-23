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
require_once(dirname(__FILE__).'/../sec/activity_robot_fnc.php');
require_once(dirname(__FILE__).'/../sec/battle_lifecycle_fnc.php');
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('0');
$_SESSION['id'] = $uid;

/*if (!defined('BATTLE_TIME_START'))
	define('BATTLE_TIME_START', "20:00");
if (!defined('BATTLE_TIME_END'))
	define('BATTLE_TIME_END', "22:00");
if (!defined('BATTLE_TIME_WEEK'))
	define('BATTLE_TIME_WEEK', 5);*/

secStart($_pm['mem']);
if(!kdjlSacredBattleTick($_pm['mysql'], $_pm['mem'], time()))
	die('战场初始化失败，请稍候重试！');
kdjlRunActivityAutomation($_pm['mysql'], $_pm['mem']);
$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$uid."'");
$n = (isset($_REQUEST['n']) && !is_array($_REQUEST['n'])) ? intval($_REQUEST['n']) : 0;
if ($n!=1 && $n!=2) die('0');

$battleJoinLockName = 'battle_join';
$battleJoinLockSql = $_pm['mysql']->escape($battleJoinLockName);
$battleJoinLock = $_pm['mysql']->getOneRecord("SELECT GET_LOCK('{$battleJoinLockSql}',5) AS locked");
if(!is_array($battleJoinLock) || intval($battleJoinLock['locked']) != 1)
{
	die('服务器繁忙，请稍候再试！');
}
function kdjlReleaseBattleJoinLock()
{
	global $_pm,$battleJoinLockName;
	if($battleJoinLockName !== '')
	{
		$battleJoinLockSql = $_pm['mysql']->escape($battleJoinLockName);
		$_pm['mysql']->getOneRecord("SELECT RELEASE_LOCK('{$battleJoinLockSql}') AS released");
		$battleJoinLockName = '';
	}
}
register_shutdown_function('kdjlReleaseBattleJoinLock');

$today = date("Y-m-d",time());
$user  = $_pm['user']->getUserById($uid);
if(!is_array($user)) die('0');
if(!isset($_SESSION['jgbug'])) $_SESSION['jgbug'] = '';
$battleinfo = $_pm['mysql']->getOneRecord("SELECT maxuser,bf_ml_num,bf_level_limit,level_get,startf,ends
                                             FROM battlefield
											WHERE id={$n}
										 ");
if(!is_array($battleinfo)) die('0');
$battleinfo['startf'] = isset($battleinfo['startf']) ? intval($battleinfo['startf']) : 0;
$battleinfo['ends'] = isset($battleinfo['ends']) ? intval($battleinfo['ends']) : 0;
if($battleinfo['startf'] != 1 || $battleinfo['ends'] != 0) die('战场尚未初始化，请返回战场入口后重试！');
$battleinfo['maxuser'] = isset($battleinfo['maxuser']) ? intval($battleinfo['maxuser']) : 0;
$battleinfo['bf_ml_num'] = isset($battleinfo['bf_ml_num']) ? intval($battleinfo['bf_ml_num']) : 0;
$battleinfo['bf_level_limit'] = isset($battleinfo['bf_level_limit']) ? intval($battleinfo['bf_level_limit']) : 0;
$battleinfo['level_get'] = isset($battleinfo['level_get']) ? $battleinfo['level_get'] : '';


// 战场开放时间开关。
$week = date("N", time());
$hourM= date("H:i", time());

$battletimearr = kdjlSafeMemValue($_pm['mem']->get(MEM_TIME_KEY), array());
$battleDays = array();
$battleStart = '';
$battleEnd = '';
$checkstr = 0;
if(is_array($battletimearr)) foreach($battletimearr as $bv)
{
	if(!isset($bv['titles']) || $bv['titles'] != "battle")
	{
		continue;
	}
	foreach(weeklyDayList($bv['days']) as $battleDay) $battleDays[$battleDay] = $battleDay;
	if($battleStart === '')
	{
		$battleStart = $bv['starttime'];
		$battleEnd = $bv['endtime'];
	}
	if(isWeeklyDayTimeActive($bv['days'], $bv['starttime'], $bv['endtime'], $week, $hourM))
	{
		$checkstr = 1;
		break;
	}
}

if(empty($checkstr))
{
	$str = '战场未开启！';
	if(count($battleDays) > 0)
	{
		$str .= '战场开放时间：每周' . weeklyDaysText(implode('|', $battleDays))
			 . ' ' . $battleStart . '-' . $battleEnd . '开放！';
	}
	die($str);
}
// ####### end ##############################

// 玩家等级验证。
$mainBid = isset($user['mbid']) ? intval($user['mbid']) : 0;
$main_bb = $_pm['mysql']->getOneRecord("SELECT czl
										  FROM userbb
										 WHERE id={$mainBid} AND uid={$uid}
										");
$mainCzl = is_array($main_bb) ? intval($main_bb['czl']) : 0;
if ($mainCzl < $battleinfo['bf_level_limit']) die("您的主战宠物成长不够，进入阵营主战宠物需要 {$battleinfo['bf_level_limit']} 成长!");

$exists = $_pm['mysql']->getOneRecord("SELECT uid,lastvtime,bid,pos
											FROM battlefield_user
										   WHERE uid={$uid}
										   ORDER BY id LIMIT 1");
$joinedToday = is_array($exists) && intval($exists['lastvtime']) >= strtotime($today);
if($joinedToday && intval($exists['pos']) != $n) die('2');

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

$currentNum = is_array($zrsum) ? intval($zrsum['cnt']) : 0;
$desNum = is_array($dessum) ? intval($dessum['cnt']) : 0;
if($joinedToday && intval($exists['pos']) == $n) $currentNum = max(0, $currentNum - 1);
$maxUser = $battleinfo['maxuser'];

if (!$joinedToday && $maxUser > 0 && $currentNum >= $maxUser) die('本阵营人数已满！');
else
{
    // 验证双方相差的人数。
    if (!$joinedToday && $battleinfo['bf_ml_num'] > 0 && $currentNum-$desNum >= $battleinfo['bf_ml_num']) die('我方当前人数超过对方至少 '.$battleinfo['bf_ml_num'].' 人，已足够剿灭对方，请您稍后再来！');

	//ex format: 30-45:10:1|0:1,46-60:20:1|0:1,61-70:30:2|0:1,71-80:40:2|0:1,81-90:50:3|0:1,91-100:60:3|0:1
	$par = empty($battleinfo['level_get']) ? array() : explode(',', $battleinfo['level_get']);
	foreach ($par as $k => $v)
	{
		if($v == '') continue;
		$inparrt = explode(':', $v, 2);
		if(count($inparrt) < 2) continue;
		if(!preg_match('/^[0-9]+-[0-9]+$/', $inparrt[0])) continue;
		$inparr  = explode('-', $inparrt[0]);
		if(count($inparr) < 2) continue;
		$levelMin = intval($inparr[0]);
		$levelMax = intval($inparr[1]);

		//if ($main_bb['level'] >= $inparr[0] && $main_bb['level']<= $inparr[1]) // 找到对应等级。
		if ($mainCzl >= $levelMin && $mainCzl <= $levelMax)
		{
			// levels, addjgvalue, ackvalue, failjgvalue, failackvalue, lastvtime
			$att = explode('|', $inparrt[1]); // 获得各项战场属性值
			if(count($att) < 2) continue;
			$onepart = explode(':', $att[0]); // 成功部分影响值
			$twopart = explode(':', $att[1]); // 失败部分影响值
			if(count($onepart) < 2 || count($twopart) < 2) continue;
			$addjgvalue = intval($onepart[0]);
			$ackvalue = intval($onepart[1]);
			$failjgvalue = intval($twopart[0]);
			$failackvalue = intval($twopart[1]);
			if (is_array($exists)) // 有玩家的战场记录，更新时间，主战宠物，能进入的级别及攻击值等。
			{
				// 玩家是否已经加入战场
				if ($joinedToday)
				{
				   $_SESSION['jgbug'] .= __LINE__." B <br>\n";
				    if ($exists['pos']!=$n) die('2'); // 不能加入其它阵营。
					else
					{
						if(!$_pm['mysql']->query("UPDATE battlefield_user
											 SET addjgvalue={$addjgvalue},
												 ackvalue={$ackvalue},
												 failjgvalue={$failjgvalue},
												 failackvalue={$failackvalue},
												 bid={$mainBid},
												 levels='{$inparrt[0]}'
										   WHERE uid={$uid}
										 ")) die('0');

						die('3');  // 已经加入阵营，不用再加入。
					}
				}
				else if ($mainCzl >= $levelMin && $mainCzl <= $levelMax)
				{
					// 更新加入阵营时间。
					if(!$_pm['mysql']->query("UPDATE battlefield_user
											 SET lastvtime=unix_timestamp(),
												 addjgvalue={$addjgvalue},
												 ackvalue={$ackvalue},
												 failjgvalue={$failjgvalue},
												 failackvalue={$failackvalue},
												 doublejg=0,
												 pos={$n},
												 tops=0,
											 jgvalue=COALESCE(curjgvalue,0)+COALESCE(jgvalue,0),
												 curjgvalue=0,
												 boxnum=0,
												 bid={$mainBid},
												 nscf=0,
												 subhp=0,
												 addhp=0,
												 levels='{$inparrt[0]}'
										   WHERE uid={$uid}
										 ")) die('0');
					$_SESSION['jgbug'] .= __LINE__." A <br>\n";
					die('1');  // 加入成功！
					break;
				}
			}
			else
			{
				$_SESSION['jgbug'] .= __LINE__." C <br>\n";
				if(!$_pm['mysql']->query("INSERT INTO battlefield_user(uid,pos,bid,jgvalue,levels,addjgvalue,ackvalue,failjgvalue,failackvalue,lastvtime)
									  VALUES({$uid},{$n},{$mainBid},0,'{$inparrt[0]}',{$addjgvalue},
											 {$ackvalue},{$failjgvalue},{$failackvalue},unix_timestamp()
									        )
									 ")) die('0');
				die('1');  // 加入成功！
				break;
			}
		}else continue;
	} // end foreach.
}
die('0');
?>
