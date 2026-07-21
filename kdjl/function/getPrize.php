<?php
require_once('../config/config.game.php');
secStart($_pm['mem']);
require_once('../sec/dblock_fun.php');
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('登录状态无效！');
$inPrizeTransaction = false;
$a = getLock($uid);
if(!is_array($a)){
	msg('请不要过快点击,谢谢！');
}
$inPrizeTransaction = true;
if(!$_pm['mysql']->query("INSERT INTO player_ext(uid,bbshow) VALUES({$uid},5) ON DUPLICATE KEY UPDATE uid=uid"))
{
	msg('初始化玩家领奖数据失败！');
}
$welcome = memContent2Arr("db_welcome",'code');
if(!is_array($welcome)) $welcome = array();
$uarr = array();
$now = date('Ymd');
$mempropsid = $_pm['mem']->get('db_propsid');
if(!is_array($mempropsid)) $mempropsid = kdjlSafeMemValue($mempropsid, array());
if(!is_array($mempropsid)) $mempropsid = array();
$u = $_pm['mysql'] -> getOneRecord('SELECT prize_every_day FROM player_ext WHERE uid = '.$uid.' FOR UPDATE');
$uarr = is_array($u) && isset($u['prize_every_day']) ? explode('|',$u['prize_every_day']) : array();
while(count($uarr) < 3) $uarr[] = 0;
$prize_str = isset($welcome['holiday_prize']['contents']) ? $welcome['holiday_prize']['contents'] : '';
$arr = explode('|',$prize_str);
while(count($arr) < 3) $arr[] = 0;
$type = (isset($_GET['type']) && !is_array($_GET['type'])) ? intval($_GET['type']) : 0;
$s = '';
$weekprizeflag = 0;
$holidayprizeflag = 0;
$flag = 0;
if($type == 1){
	if($arr[0] == 0){//日常奖励
		msg('尚未开启');
	}else{
		if($uarr[0] < $now){
			$row = explode(',',$arr[0]);
			$task = new task();
			foreach($row as $rv){
				$res = explode(':',$rv);
				$pid = isset($res[0]) ? intval($res[0]) : 0;
				$num = isset($res[1]) ? intval($res[1]) : 0;
				if($pid < 1 || $num < 1 || !isset($mempropsid[$pid]) || !is_array($mempropsid[$pid])) msg('奖励配置错误！');
				$giveResult = $task->saveGetPropsMore($pid,$num);
				if($giveResult !== true){
					$_pm['mysql']->query('ROLLBACK');
					msg($giveResult === '200' ? '背包空间不足，请整理后再领取！' : '每日奖励发放失败，请稍候再试！');
				}
				$pname = isset($mempropsid[$pid]['name']) ? $mempropsid[$pid]['name'] : $pid;
				$s.=','.$pname.'x'.$num;
			}
			$s = substr($s,1);
			if($s === ''){
				msg('奖励配置错误！');
			}

			$newstr = $now.'|'.$uarr[1].'|'.$uarr[2];
			if(!$_pm['mysql']->query("UPDATE player_ext SET prize_every_day = '$newstr' WHERE uid = ".$uid) || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
				$_pm['mysql']->query('ROLLBACK');
				msg('每日奖励状态保存失败，请稍候再试！');
			}
			if(!$_pm['mysql']->query('COMMIT')){
				msg('每日奖励状态保存失败，请稍候再试！');
			}
			$inPrizeTransaction = false;
			$_pm['mem']->del(MEM_USERBAG_KEY);
			msg('每日奖励领取成功，获得'.$s);
		}else{
			msg('已经领取');
		}
	}
}else if($type == 2){
	if($arr[1] == 0){//周末奖励
		msg('尚未开启');
	}else{
		$week = date('w');
		if($week != 0 && $week != 6){
			msg('不是周末');
		}else{
			if($week == 0){//星期天
				$yes = date("Ymd", strtotime("1 days ago"));//需要判断昨天也没有领取
				if($uarr[1] < $yes){
					$weekprizeflag = 1;//尚未领取
				}else{
					msg('已经领取');
				}
			}else{
				if($uarr[1] < $now){
					$weekprizeflag = 1;//尚未领取
				}else{
					msg('已经领取');
				}
			}
		}
	}
	if($weekprizeflag == 1){
		$row = explode(',',$arr[1]);
		$task = new task();
		foreach($row as $rv){
			$res = explode(':',$rv);
			$pid = isset($res[0]) ? intval($res[0]) : 0;
			$num = isset($res[1]) ? intval($res[1]) : 0;
			if($pid < 1 || $num < 1 || !isset($mempropsid[$pid]) || !is_array($mempropsid[$pid])) msg('奖励配置错误！');
			$giveResult = $task->saveGetPropsMore($pid,$num);
			if($giveResult !== true){
				$_pm['mysql']->query('ROLLBACK');
				msg($giveResult === '200' ? '背包空间不足，请整理后再领取！' : '周末奖励发放失败，请稍候再试！');
			}
			$pname = isset($mempropsid[$pid]['name']) ? $mempropsid[$pid]['name'] : $pid;
			$s.=','.$pname.'x'.$num;
		}
		$s = substr($s,1);
		if($s === ''){
			msg('奖励配置错误！');
		}

		$newstr = $uarr[0].'|'.$now.'|'.$uarr[2];
		if(!$_pm['mysql']->query("UPDATE player_ext SET prize_every_day = '$newstr' WHERE uid = ".$uid) || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
			$_pm['mysql']->query('ROLLBACK');
			msg('周末奖励状态保存失败，请稍候再试！');
		}
		if(!$_pm['mysql']->query('COMMIT')){
			msg('周末奖励状态保存失败，请稍候再试！');
		}
		$inPrizeTransaction = false;
		$_pm['mem']->del(MEM_USERBAG_KEY);
		msg('周末奖励领取成功，获得'.$s);
	}
}else if($type == 3){
	$harr = explode(';',$arr[2]);//20100917:1*20,2*30;20101001:5*20,6*30
	if(is_array($harr)){
		foreach($harr as $hv){
			$row = explode(':',$hv);
			if(isset($row[0]) && $now == $row[0]){//是节假日
				$flag = 1;
				if($uarr[2] == $row[0]){
					msg('已经领取');
				}else{
					$holidayprizeflag = 1;//尚未领取
				}
				break;
			}
		}
	}else{
		msg('没有设置节假日！');
	}
	if($flag != 1){
		msg('不是节假日，不能领奖！');
	}
	if($holidayprizeflag == 1){//发奖
		//得到设置的奖励物品
		$rs = explode(',', isset($row[1]) ? $row[1] : '');
		$task=new task();
		foreach($rs as $rv){
			$res = explode('*',$rv);
			$pid = isset($res[0]) ? intval($res[0]) : 0;
			$num = isset($res[1]) ? intval($res[1]) : 0;
			if($pid < 1 || $num < 1 || !isset($mempropsid[$pid]) || !is_array($mempropsid[$pid])) msg('奖励配置错误！');
			$giveResult = $task->saveGetPropsMore($pid,$num);
			if($giveResult !== true){
				$_pm['mysql']->query('ROLLBACK');
				msg($giveResult === '200' ? '背包空间不足，请整理后再领取！' : '节日奖励发放失败，请稍候再试！');
			}
			$pname = isset($mempropsid[$pid]['name']) ? $mempropsid[$pid]['name'] : $pid;
			$s.=','.$pname.'x'.$num;
		}
		$s=substr($s,1);
		if($s === ''){
			msg('奖励配置错误！');
		}
		$newstr = $uarr[0].'|'.$uarr[1].'|'.$now;
		if(!$_pm['mysql']->query("UPDATE player_ext SET prize_every_day = '$newstr' WHERE uid = ".$uid) || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
			$_pm['mysql']->query('ROLLBACK');
			msg('节日奖励状态保存失败，请稍候再试！');
		}
		if(!$_pm['mysql']->query('COMMIT')){
			msg('节日奖励状态保存失败，请稍候再试！');
		}
		$inPrizeTransaction = false;
		$_pm['mem']->del(MEM_USERBAG_KEY);
		msg('节假日奖励领取成功，获得'.$s);
	}
}else{
	msg('领奖请求错误！');
}


function msg($m)
{
	global $_pm,$inPrizeTransaction;
	if(!empty($inPrizeTransaction) && isset($_pm['mysql']))
	{
		$_pm['mysql']->query('ROLLBACK');
		$inPrizeTransaction = false;
	}
	realseLock();
	die($m);
}
?>
