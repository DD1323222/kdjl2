<?php
/**
*@Author: %xueyuan%

*@Write Date: 2011.05.27
*@Update Date: 2011.05.27
*@Usage:Fightting saolei Mod
*@Note: none
*/
ini_set('display_errors',false);
error_reporting(0);
require_once('../config/config.game.php');
require_once(dirname(__FILE__).'/saolei_common.php');
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1)
{
	die('登录状态无效！');
}
if(!isset($_SESSION['insl']) || intval($_SESSION['insl']) != $uid)
{
	die('扫雷状态无效，请重新进入扫雷！');
}
$chooseid = (isset($_GET['id']) && !is_array($_GET['id'])) ? intval($_GET['id']) : 0;
if($chooseid < 1 || $chooseid > 9)
{
	die('扫雷操作无效！');
}
$props = slPrizeLoadProps($_pm['mysql'], $_pm['mem']);
$slPropsById = array();
foreach($props as $propsRow)
{
	if(is_array($propsRow) && isset($propsRow['id'])) $slPropsById[intval($propsRow['id'])] = $propsRow;
}
//扫雷验证
$deal = !slTodayUserHas($_pm['mem'], $uid) || slTodayTicketHas($_pm['mem'], $uid);
if(!$deal)
{
	$_pm['mysql'] -> query("INSERT INTO gamelog (seller,buyer,ptime,pnote,vary) VALUES($uid,$uid,".time().",'扫雷恶意玩家',253)");
	die('扫雷操作无效！');

}
$deal = new task;
$get_fh = 0;
require_once('../sec/dblock_fun.php');
$a = getLock($uid);
if(!is_array($a)){
	realseLock();
	die('服务器繁忙，请稍候再试！');
}
$slTransactionActive = true;
$slStateChanged = false;
$slOldPlayed = false;
$slOldTicket = false;
$slOldDie = 0;
$slFinalizeRun = false;
$slFinalDieStage = 0;
$slAnnouncementMode = '';
$slAnnouncementWord = '';
if(!function_exists('slStartShutdown'))
{
	function slStartShutdown()
	{
		global $_pm,$uid,$slTransactionActive,$slStateChanged,$slOldPlayed,$slOldTicket,$slOldDie;
		if($slTransactionActive && isset($_pm['mysql'])) $_pm['mysql']->query('ROLLBACK');
		if($slTransactionActive && $slStateChanged)
		{
			slTodayUserSet($_pm['mem'], $uid, $slOldPlayed);
			slTodayTicketSet($_pm['mem'], $uid, $slOldTicket);
			if($slOldDie > 0) slDieOptionSet($_pm['mem'], $uid, $slOldDie);
			else slDieOptionClear($_pm['mem'], $uid);
		}
		$slTransactionActive = false;
		if(function_exists('realseLock')) realseLock();
	}
}
register_shutdown_function('slStartShutdown');
if(!function_exists('slFail'))
{
	function slFail($msg)
	{
		global $_pm;
		if(isset($_pm['mysql'])) $_pm['mysql']->query('ROLLBACK');
		if(function_exists('realseLock')) realseLock();
		die($msg);
	}
}
function slGiveProps($deal,$pid,$num)
{
	global $_pm;
	$giveResult = $deal->saveGetPropsMore($pid,$num);
	if($giveResult !== true){
		$_pm['mysql']->query('ROLLBACK');
		slFail($giveResult === '200' ? '背包空间不足，请整理后再试！' : '扫雷奖励发放失败，请稍候再试！');
	}
}
function slStartHtml($value)
{
	return htmlspecialchars(strval($value), ENT_QUOTES, 'UTF-8');
}
function slStartImage($value)
{
	$value = basename(strval($value));
	return preg_match('/^[A-Za-z0-9_.-]+$/', $value) ? $value : '';
}
if(slTodayUserHas($_pm['mem'], $uid) && !slTodayTicketHas($_pm['mem'], $uid)) slFail('今日扫雷次数已用完！');
if(!$_pm['mysql'] -> query("INSERT INTO player_ext(uid,bbshow,F_saolei_points) VALUES (".$uid.",5,1) ON DUPLICATE KEY UPDATE uid=uid"))
{
	slFail('初始化扫雷数据失败！');
}
$res = $_pm['mysql'] -> getOneRecord("SELECT F_saolei_points FROM player_ext WHERE uid = ".$uid." FOR UPDATE");
if(!is_array($res)) $res = array('F_saolei_points'=>1);
if(!isset($res['F_saolei_points']) || intval($res['F_saolei_points']) < 1) $res['F_saolei_points'] = 1;
if($res['F_saolei_points']  == 3 || $res['F_saolei_points'] == 6 || $res['F_saolei_points'] == 9)
{
	if(rand(1,10) > 9)
	{
		slGiveProps($deal,4038,1);	//赠送复活卡
		$get_fh = 1;
	}
}
$sl_fhtime = $_pm['mysql'] -> getOneRecord(" SELECT SUM(sums) AS sums
                                               FROM userbag
                                              WHERE pid = 4038
                                                AND uid =  $uid
                                                AND sums > 0
                                                AND zbing = 0
                                                AND (cantrade IS NULL OR cantrade<>3)");
$sl_fhtime = empty($sl_fhtime['sums'])?0:intval($sl_fhtime['sums']);
$stage = intval($res['F_saolei_points']);
if($stage == 1 && !slTodayUserHas($_pm['mem'], $uid) && !slTodayTicketHas($_pm['mem'], $uid))
{
	$growthRow = $_pm['mysql']->getOneRecord("SELECT userbb.czl
										 FROM player,userbb
										WHERE player.id={$uid}
										  AND player.mbid=userbb.id
										  AND userbb.uid=player.id
									 LIMIT 1");
	if(!is_array($growthRow) || intval($growthRow['czl']) < 65)
	{
		$slOldPlayed = slTodayUserHas($_pm['mem'], $uid);
		$slOldTicket = slTodayTicketHas($_pm['mem'], $uid);
		$slOldDie = slDieOptionFind($_pm['mem'], $uid);
		$slStateChanged = true;
		if(!slTodayUserSet($_pm['mem'], $uid, true)) slFail('保存扫雷状态失败！');
		if(!$_pm['mysql']->query('COMMIT'))
		{
			slTodayUserSet($_pm['mem'], $uid, $slOldPlayed);
			slFail('保存扫雷数据失败！');
		}
		$slTransactionActive = false;
		$slStateChanged = false;
		realseLock();
		die('主战宠物成长率不足，无法参与扫雷！');
	}
}
list($this_probability, $this_other) = slPrizeLoadStageRules($_pm['mysql'], $_pm['mem'], $stage);
$mid_arr = array();
$this_other_thing = array();
$this_thing_end = array();
$return_thing_info = array();
if($this_probability === '' || $this_other === '') slFail('扫雷配置错误！');
$probabilityCovered = array_fill(1, 100, false);
$arr = explode(',',$this_probability);
foreach($arr as $info)
{
	$parts = explode(':', $info, 2);
	$range = count($parts) == 2 ? explode('-', $parts[1], 2) : array();
	$typeName = isset($parts[0]) ? trim($parts[0]) : '';
	$rangeStart = isset($range[0]) ? intval($range[0]) : 0;
	$rangeEnd = isset($range[1]) ? intval($range[1]) : 0;
	if(($typeName !== 'good' && $typeName !== 'die') || $rangeStart < 1 || $rangeEnd > 100 || $rangeStart > $rangeEnd)
	{
		slFail('扫雷配置错误！');
	}
	$mid_arr[] = array($typeName, $rangeStart.'-'.$rangeEnd);
	for($covered = $rangeStart; $covered <= $rangeEnd; $covered++)
	{
		if($probabilityCovered[$covered]) slFail('扫雷配置错误！');
		$probabilityCovered[$covered] = true;
	}
}
if(empty($mid_arr)) slFail('扫雷配置错误！');

$this_other = $this_other === '0' ? array() : explode(',',$this_other);
$valid_other = array();
$otherCovered = array_fill(1, 100, false);
foreach($this_other as $otherInfo)
{
	$otherParts = explode(':',$otherInfo,2);
	$otherRange = count($otherParts) == 2 ? explode('-', $otherParts[1], 2) : array();
	$otherPid = isset($otherParts[0]) ? intval($otherParts[0]) : 0;
	$otherStart = isset($otherRange[0]) ? intval($otherRange[0]) : 0;
	$otherEnd = isset($otherRange[1]) ? intval($otherRange[1]) : 0;
	if($otherPid > 0 && isset($slPropsById[$otherPid]) && $otherStart >= 1 && $otherEnd <= 100 && $otherStart <= $otherEnd)
	{
		$valid_other[] = $otherPid.':'.$otherStart.'-'.$otherEnd;
		for($covered = $otherStart; $covered <= $otherEnd; $covered++)
		{
			if($otherCovered[$covered]) slFail('扫雷配置错误！');
			$otherCovered[$covered] = true;
		}
	}
}
$normalResultPossible = in_array(false, $probabilityCovered, true);
if($normalResultPossible && (empty($valid_other) || in_array(false, $otherCovered, true))) slFail('扫雷配置错误！');
$this_other = $valid_other;
//5.3
$luck = rand(1,100);	//幸运数,判断中大奖品,一般奖,或死亡
$prize_info_best = slPrizeGetUserPool($_pm['mem'], $uid);
if(!is_array($prize_info_best)) $prize_info_best = array();
if(!isset($prize_info_best[$res['F_saolei_points']]['id'])) slFail('扫雷奖励配置错误！');
if(count($mid_arr) == 1)
{
	$num = explode('-',$mid_arr[0][1]);
	$i = 1;
	foreach($this_other as $info)
	{
		$mid = explode(':',$info);
		$this_other_thing[$i] = $mid[0];
		$i++;
	}
	shuffle($this_other_thing);
	while(count($this_other_thing) > 9)
	{
		array_pop($this_other_thing);
	}
	foreach($this_other_thing as $key => $val)
	{
		$key_end = $key+1;
		$this_thing_end[$key_end] = $val;
	}
	if($luck >= $num[0] && $luck <= $num[1])	//中好东西了
	{
		$this_thing_end[$chooseid] = $prize_info_best[$res['F_saolei_points']]['id'];
		slGiveProps($deal,$this_thing_end[$chooseid],1);	//发奖品
	}
	else										//中普通东西
	{
		do
		{
			$goodthingarea = rand(1,9);
		}
		while($goodthingarea == $chooseid);
		$this_thing_end[$goodthingarea] = $prize_info_best[$res['F_saolei_points']]['id'];
		$luck = rand(1,100);
		foreach($this_other as  $info)
		{
			$oarr = explode(':',$info);
			$othingnum = explode('-',$oarr[1]);
			if($luck >= $othingnum[0] && $luck <= $othingnum[1])
			{
				$this_thing_end[$chooseid] = $oarr[0];
				slGiveProps($deal,$this_thing_end[$chooseid],1);	//发奖品
				break;
			}
		}
	}
	if(!$_pm['mysql'] -> query("UPDATE player_ext SET F_saolei_points = COALESCE(F_saolei_points,0) +1  WHERE uid = ".$uid)) slFail('保存扫雷数据失败！');
}
else
{
	$type = 0;
	$bob_num = $res['F_saolei_points']-1;
	$other_num =  9-$bob_num-1;
	$good_num = 1;
	foreach($mid_arr as $info)
	{
		$m = explode('-',$info[1]);
		if($luck >= $m[0] && $luck <= $m[1] && $info[0] == 'good')
		{
			$this_thing_end_chooseid = $prize_info_best[$res['F_saolei_points']]['id'];
			$good_num--;
			$type = 1;
			$best_props_name = $_pm['mysql'] -> getOneRecord('SELECT name FROM props WHERE id = '.$prize_info_best[$res['F_saolei_points']]['id']);
			if(!is_array($best_props_name)) $best_props_name = array('name'=>'');
			if($res['F_saolei_points'] == 9)
			{
				if(!$_pm['mysql'] -> query("UPDATE player_ext SET F_saolei_points = 1  WHERE uid = ".$uid)) slFail('保存扫雷数据失败！');
				$slFinalizeRun = true;
				$slFinalDieStage = 0;
				$word = ",通过扫雷最终关,得到本关最极品奖励:".$best_props_name['name'];
				$_pm['mysql'] -> query("INSERT INTO gamelog (seller,buyer,ptime,pnote,vary) VALUES($uid,$uid,".time().",'扫雷通过第9关玩家',254)");
				//$_pm['mem']->set(array('k' => 'sl_die_option'.$uid, 'v' => $res['F_saolei_points']));
			}
			else
			{
				if(!$_pm['mysql'] -> query("UPDATE player_ext SET F_saolei_points = COALESCE(F_saolei_points,0) +1  WHERE uid = ".$uid)) slFail('保存扫雷数据失败！');
				$word = ",通过扫雷第".$res['F_saolei_points']."关,得到本关最极品奖励:".$best_props_name['name'];
				$bestLogNote = $_pm['mysql']->escape($res['F_saolei_points']."关最极品:".$best_props_name['name']);
				$_pm['mysql'] -> query("INSERT INTO gamelog (seller,buyer,ptime,pnote,vary) VALUES($uid,$uid,".time().",'{$bestLogNote}',254)");
			}
			slGiveProps($deal,$prize_info_best[$res['F_saolei_points']]['id'],1);	//发奖品
			$slAnnouncementMode = 'game';
			$slAnnouncementWord = $word;
			break;
		}
		elseif($luck >= $m[0] && $luck <= $m[1] && $info[0] == 'die')
		{
			$this_thing_end_chooseid = 'bob';
			$bob_num--;
			$type = 1;
			if(!$_pm['mysql'] -> query("UPDATE player_ext SET F_saolei_points = 1  WHERE uid = ".$uid)) slFail('保存扫雷数据失败！');
			$slFinalizeRun = true;
			$slFinalDieStage = $stage;
			break;
		}
	}
	if($type != 1)	//中普通东西
	{
		$luck = rand(1,100);
		$other_num--;
		foreach($this_other as  $info)
		{
			$oarr = explode(':',$info);
			$othingnum = explode('-',$oarr[1]);
			if($luck >= $othingnum[0] && $luck <= $othingnum[1])
			{
				$this_thing_end_chooseid = $oarr[0];
				slGiveProps($deal,$this_thing_end_chooseid,1);	//发奖品
				$normalLogNote = $_pm['mysql']->escape($res['F_saolei_points']."关普通:".$this_thing_end_chooseid);
				$_pm['mysql'] -> query("INSERT INTO gamelog (seller,buyer,ptime,pnote,vary) VALUES($uid,$uid,".time().",'{$normalLogNote}',254)");
				break;
			}
		}
		if(!$_pm['mysql'] -> query("UPDATE player_ext SET F_saolei_points = COALESCE(F_saolei_points,0) +1  WHERE uid = ".$uid)) slFail('保存扫雷数据失败！');
	}
	if($other_num != 0)
	{
		foreach($this_other as $info)
		{
			$mid = explode(':',$info);
			$this_other_thing[] = $mid[0];
		}
		shuffle($this_other_thing);
		while(count($this_other_thing) > $other_num)
		{
			array_pop($this_other_thing);
		}
	}
	if($other_num == 0)
	{
		$this_other_thing = array();
	}
	if($bob_num != 0)
	{
		for($i=0;$i<$bob_num;$i++)
		{
			$this_other_thing[] = 'bob';
		}
	}
	if($good_num != 0)
	{
		$this_other_thing[] = $prize_info_best[$res['F_saolei_points']]['id'];
	}
	shuffle($this_other_thing);
	$type = 0;
	foreach($this_other_thing as $key => $val)
	{
		if($type == 0)
		{
			if($key+1 == $chooseid )
			{
				$this_thing_end[$key+1] = $this_thing_end_chooseid;
				$this_thing_end[$key+2] = $val;
				$type = 1;
			}
			else
			{
				$this_thing_end[$key+1] = $val;
			}
		}
		else
		{
			$this_thing_end[$key+2] = $val;
		}
	}
	if( $chooseid == 9)
	{
		$this_thing_end[9] = $this_thing_end_chooseid;
	}
	ksort($this_thing_end);
}
foreach($props as $key => $val)
{
	if(in_array($val['id'],$this_thing_end))
	{
		$return_thing_info[$val['id']] = $val;
	}
}
$echo = '<table id="leiqu" width="283" height="283"><tr>';
$echo2 = '';
$echo3 = '';
foreach($this_thing_end as $key => $info)
{
	$cellInfo = isset($return_thing_info[$info]) && is_array($return_thing_info[$info]) ? $return_thing_info[$info] : array('name'=>'','img'=>'');
	$cellNameHtml = slStartHtml(isset($cellInfo['name']) ? $cellInfo['name'] : '');
	$isMine = ((string)$info === 'bob');
	$cellImgUrl = $isMine ? IMAGE_SRC_URL.'/props/bob.gif' : slPrizeImageUrl($cellInfo);
	if(($key-1)%3 == 0 && ($key-1) != 0)
	{
		$echo .= '</tr><tr>';
	}
	if($isMine)
	{
		$echo .= '<td><div id="lq_'.$key.'" class="open_lei"><img title="'.$cellNameHtml.'" src="'.$cellImgUrl.'" /></div></td>';
	}
	else
	{
		$echo .= '<td><div id="lq_'.$key.'" class="open"><img width="40px" height="40px" title="'.$cellNameHtml.'" src="'.$cellImgUrl.'" /></div></td>';
	}
	if($key == $chooseid)
	{
		if($isMine)
		{
			$echo2 = '<img title="'.$cellNameHtml.'" src="'.$cellImgUrl.'" />';
			$echo3 = "在".$res['F_saolei_points']."关中,踩中地雷不幸身亡";
		}
		else
		{
			$echo2 = '<img width="40px" height="40px" title="'.$cellNameHtml.'" src="'.$cellImgUrl.'" />';
			$echo3 = '获得第'.$res['F_saolei_points'].'关物品:'.$cellNameHtml;
		}
	}
}
$echo .= '</tr></table>';
$echo .= "<Boundaries>";
$echo .=$echo2."<Boundaries>".$echo3."<Boundaries>".$sl_fhtime."<Boundaries>".$get_fh;
if($slFinalizeRun)
{
	$slOldPlayed = slTodayUserHas($_pm['mem'], $uid);
	$slOldTicket = slTodayTicketHas($_pm['mem'], $uid);
	$slOldDie = slDieOptionFind($_pm['mem'], $uid);
	$slStateChanged = true;
	$dieStateOk = $slFinalDieStage > 0
		? slDieOptionSet($_pm['mem'], $uid, $slFinalDieStage)
		: slDieOptionClear($_pm['mem'], $uid);
	if(!slTodayUserSet($_pm['mem'], $uid, true) ||
	   !slTodayTicketSet($_pm['mem'], $uid, false) ||
	   !$dieStateOk)
	{
		slTodayUserSet($_pm['mem'], $uid, $slOldPlayed);
		slTodayTicketSet($_pm['mem'], $uid, $slOldTicket);
		if($slOldDie > 0) slDieOptionSet($_pm['mem'], $uid, $slOldDie);
		else slDieOptionClear($_pm['mem'], $uid);
		$slStateChanged = false;
		slFail('保存扫雷状态失败！');
	}
}
if(!$_pm['mysql']->query('COMMIT'))
{
	if($slStateChanged)
	{
		slTodayUserSet($_pm['mem'], $uid, $slOldPlayed);
		slTodayTicketSet($_pm['mem'], $uid, $slOldTicket);
		if($slOldDie > 0) slDieOptionSet($_pm['mem'], $uid, $slOldDie);
		else slDieOptionClear($_pm['mem'], $uid);
	}
	slFail('保存扫雷数据失败！');
}
$slTransactionActive = false;
$_pm['mem']->del(MEM_USERBAG_KEY);
realseLock();
if($slAnnouncementMode === 'game' && $slAnnouncementWord !== '')
{
	$deal->saveGword($slAnnouncementWord);
}
echo $echo;

?>
