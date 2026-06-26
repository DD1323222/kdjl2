<?php
/**
*@Usage: 战场入口
*@Author: GeFei Su.
*@Write Date:2008-08-27
*@Copyright:www.webgame.com.cn
*/
require_once('../config/config.game.php');
secStart($_pm['mem']);
$today = date("Y-m-d", time());

// 战场开放时间开关。
$week =	date("N", time());
$hourM=	date("H:i", time());

$battletimearr = unserialize($_pm['mem']->get(MEM_TIME_KEY));

foreach($battletimearr as $bv)
{
	if($bv['titles'] != "battle")
	{
		continue;
	}
	if(($week == $bv['days'] && $hourM >= $bv['endtime']) || battle_end() === true) // 战场时间结束，更新战场关闭标记。开始更新排名及相关数据，用于玩家领取奖励。
	{
		// 根据当前剩余的HP进行判断谁成功或失败。
		$checkstr = 1;
		$zyrs = $_pm['mysql']->getRecords("SELECT hp,id,posname
											 FROM battlefield
											WHERE countf=0
											ORDER BY hp DESC
										 ");
		if (is_array($zyrs)) // 第一次统计
		{
			$_pm['mysql']->query("UPDATE battlefield
									 SET success=1
								   WHERE id='{$zyrs[0]['id']}'
							   ");
			$exists = $_pm['mysql']->getOneRecord("SELECT id,countf,success,posname
													 FROM battlefield
													WHERE countf=0 and success=1
												  ");
			if (is_array($exists) && $exists['countf']==0) // 关闭更新标记，并开始更新相关数据
			{
				$_pm['mysql']->query("UPDATE battlefield
										 SET countf=1,startf=0,ends=1
									");
				
				
				//避免重复发奖
				/*$check = unserialize($_pm['mem'] -> get('battle_prize_check'));
				$timenow = time() - 300;
				if(!empty($check) && $check <= $timenow) return;*/
				$_pm['mem'] -> set(array('k'=>'battle_prize_check','v'=>time()));
				 
				 
				if($str != $hi) return;
				
				// 战场胜利公告
				$pub = new task();
				if ($exists['posname'] == $zyrs[0]['posname'])
					$fail =  $zyrs[1]['posname'];
				else $fail=  $zyrs[0]['posname'];
				$word = '[系统公告] 本次战场结束，'.$fail.'被打得溃不成军，'.$exists['posname'].'取得了胜利！';
				for($i=0;$i<5;$i++)
					$pub-> saveGword($word, 1);
	
				// 获取胜利方所有玩家的相关信息并进行本次战场排名更新。
				$all = $_pm['mysql']->getRecords("SELECT id 
													FROM battlefield_user
												   WHERE lastvtime>unix_timestamp({$today}) and curjgvalue>0 and pos={$exists['id']}
												   ORDER BY curjgvalue DESC
												   LIMIT 0,10
												");
			   if (is_array($all))
			   {
				   foreach ($all as $k => $rs)
				   {
					   $boxnum = 0;
					   $jgvl   = 0;
					   switch(($k+1))
					   {
						  case 1: $boxnum=10; $jgvl = 2000; break;
						  case 2: 
						  case 3: $boxnum=6; $jgvl = 1500;break;
						  case 4:
						  case 5:
						  case 6: $boxnum=4; $jgvl = 1000;break;
						  case 7:
						  case 8:
						  case 9:
						  case 10: $boxnum=2; $jgvl = 500;break;
						  default: $boxnum=$jgvl=0;
					   }
					  // 更新玩家的排名.
					  $_pm['mysql']->query("UPDATE battlefield_user 
											   SET tops=".($k+1).", boxnum={$boxnum}, curjgvalue=curjgvalue+{$jgvl}
											 WHERE id={$rs['id']}
										   ");
				   }
			   }
			   // 失败方排名统计开始
			   // 获取失败方所有玩家的相关信息并进行本次战场排名更新。
				$all = $_pm['mysql']->getRecords("SELECT id 
													FROM battlefield_user
												   WHERE lastvtime>unix_timestamp({$today}) and curjgvalue>0 and pos!={$exists['id']}
												   ORDER BY curjgvalue DESC
												   LIMIT 0,10
												");
			   if (is_array($all))
			   {
				   foreach ($all as $k => $rs)
				   {
					   $boxnum = 0;
					   $jgvl   = 0;
					   switch(($k+1))
					   {
						  case 1: $boxnum=5; $jgvl = 1000; break;
						  case 2: 
						  case 3: $boxnum=3; $jgvl = 500;break;
						  case 4:
						  case 5:
						  case 6: $boxnum=2; $jgvl = 300;break;
						  case 7:
						  case 8:
						  case 9:
						  case 10: $boxnum=1; $jgvl = 100;break;
						  default: $boxnum=$jgvl=0;
					   }
					   // 更新玩家的排名.
					   $_pm['mysql']->query("UPDATE battlefield_user 
												SET tops=".($k+1).", boxnum={$boxnum}, curjgvalue=curjgvalue+{$jgvl}
											  WHERE id={$rs['id']}
										   ");
				   }
			   }
			   $time = time();
			   $_pm['mysql'] -> query("INSERT INTO gamelog (ptime,buyer,seller,pnote,vary) VALUES($time,'1','1','jgprize','200')");
			   // 失败方排名统计结束
			}
		} // end out of if
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
									   WHERE uid={$_SESSION['id']}");

//###########################
// @Load template.
//###########################
$tn = $_game['template'] . 'tpl_battle_box.html';
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
