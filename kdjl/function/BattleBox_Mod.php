<?php
/**
*@Usage: 战场入口
*@Author: GeFei Su.
*@Write Date:2008-08-27
*@Copyright:www.webgame.com.cn
*/
require_once('../config/config.game.php');
secStart($_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');
$today = date("Y-m-d", time());

function battleSettleRound($today)
{
	global $_pm;
	$todaySql = $_pm['mysql']->escape($today);
	if(!$_pm['mysql']->query('START TRANSACTION')) return false;
	$camps = $_pm['mysql']->getRecords('SELECT id,hp,posname FROM battlefield WHERE countf=0 ORDER BY hp DESC,id ASC FOR UPDATE');
	if(!is_array($camps) || empty($camps))
	{
		$_pm['mysql']->query('ROLLBACK');
		return false;
	}
	$winner = $camps[0];
	$winnerId = intval($winner['id']);
	if($winnerId < 1 || !$_pm['mysql']->query('UPDATE battlefield SET success=IF(id='.$winnerId.',1,0),countf=1,startf=0,ends=1 WHERE countf=0'))
	{
		$_pm['mysql']->query('ROLLBACK');
		return false;
	}

	$rankGroups = array(
		array('where'=>'pos='.$winnerId, 'rewards'=>array(array(10,2000),array(6,1500),array(6,1500),array(4,1000),array(4,1000),array(4,1000),array(2,500),array(2,500),array(2,500),array(2,500))),
		array('where'=>'pos<>'.$winnerId, 'rewards'=>array(array(5,1000),array(3,500),array(3,500),array(2,300),array(2,300),array(2,300),array(1,100),array(1,100),array(1,100),array(1,100)))
	);
	foreach($rankGroups as $group)
	{
		$rows = $_pm['mysql']->getRecords("SELECT id FROM battlefield_user WHERE lastvtime>=UNIX_TIMESTAMP('{$todaySql}') AND curjgvalue>0 AND ".$group['where'].' ORDER BY curjgvalue DESC,id ASC LIMIT 10 FOR UPDATE');
		if(!is_array($rows)) $rows = array();
		foreach($rows as $rank=>$row)
		{
			if(!isset($group['rewards'][$rank])) break;
			$rowId = isset($row['id']) ? intval($row['id']) : 0;
			if($rowId < 1) continue;
			$boxnum = intval($group['rewards'][$rank][0]);
			$jgvalue = intval($group['rewards'][$rank][1]);
			if(!$_pm['mysql']->query('UPDATE battlefield_user SET tops='.($rank+1).',boxnum='.$boxnum.',curjgvalue=COALESCE(curjgvalue,0)+'.$jgvalue.' WHERE id='.$rowId))
			{
				$_pm['mysql']->query('ROLLBACK');
				return false;
			}
		}
	}
	if(!$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		return false;
	}
	$loserName = isset($camps[1]['posname']) ? $camps[1]['posname'] : '';
	return array('winner'=>isset($winner['posname']) ? $winner['posname'] : '', 'loser'=>$loserName);
}

// 战场开放时间开关。
$week =	date("N", time());
$hourM=	date("H:i", time());

$battletimearr = kdjlSafeMemValue($_pm['mem']->get(MEM_TIME_KEY), array());
if(!is_array($battletimearr)) $battletimearr = array();

foreach($battletimearr as $bv)
{
	if(!is_array($bv)) continue;
	if(!isset($bv['titles'])) $bv['titles'] = '';
	if(!isset($bv['days'])) $bv['days'] = '';
	if(!isset($bv['endtime'])) $bv['endtime'] = '';
	if($bv['titles'] != "battle")
	{
		continue;
	}
	if(isWeeklyDayTimeFinished($bv['days'], $bv['endtime'], $week, $hourM, true) || battle_end() === true) // 战场时间结束，更新战场关闭标记。开始更新排名及相关数据，用于玩家领取奖励。
	{
		$result = battleSettleRound($today);
		if(is_array($result))
		{
			$_pm['mem']->set(array('k'=>'battle_prize_check','v'=>time()));
			$time = time();
			$_pm['mysql']->query("INSERT INTO gamelog (ptime,buyer,seller,pnote,vary) VALUES({$time},'1','1','jgprize','200')");
			$word = '[系统公告] 本次战场结束，'.$result['loser'].'被打得溃不成军，'.$result['winner'].'取得了胜利！';
			$pub = new task();
			for($i=0;$i<5;$i++) $pub->saveGword($word,1);
		}
		break;
	}
	/*else if ($week != $bv['days'] && ($hourM < $bv['starttime'] || $hourM > $bv['endtime']) )
	{
		die('<center><span style="font-size:12px;">战场未开启3！</span></center>'); // record log in here.
	}*/
}

// 战场结束条件。对方女神生命为0或者时间结束。
/**
* @Usage: 战场是否结束。
* @Param: none
* @Return: true of false
* Note:
     结束有2种情况，一种是对方HP=0，另外是战场时间结束。
*/
function battle_end()
{
	global $_pm;
	$ends = $_pm['mysql']->getOneRecord("SELECT id
										   FROM battlefield
										  WHERE hp=0
										  LIMIT 0,1
									   ");
	if (is_array($ends))
	{
		return true;
	}
	else return false;
}
$cUser = $_pm['mysql']->getOneRecord("SELECT jgvalue,curjgvalue
										FROM battlefield_user
									   WHERE uid={$uid}
									ORDER BY id LIMIT 1");
if(!is_array($cUser))
{
	$cUser = array('jgvalue' => 0, 'curjgvalue' => 0);
}

//###########################
// @Load template.
//###########################
$tn = $_game['template'] . 'tpl_battle_box.html';
$cet = '';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);

	$src = array('#userjg#',
				 '#usertop#',
	             '#desclist#',
				 '#usercurjg#'
				);
	$des = array($cUser['jgvalue'],
	             '',
				 '',
				 $cUser['curjgvalue']
				);
	$cet = str_replace($src, $des, $tpl);
}
// gzip echo. if maybe.
ob_start('ob_gzip');
echo $cet;
ob_end_flush();
?>
