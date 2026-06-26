<?php
require_once('../config/config.game.php');
secStart($_pm['mem']);
require_once('../sec/dblock_fun.php');
$a = getLock($_SESSION['id']);
if(!is_array($a)){
	msg('请不要过快点击,谢谢！');
}
$welcome = memContent2Arr("db_welcome",'code');
$uarr = array();
$now = date('Ymd');
$user = $_pm['user']->getUserById($_SESSION['id']);
$mempropsid = unserialize($_pm['mem']->get('db_propsid'));
$u = $_pm['mysql'] -> getOneRecord('SELECT prize_every_day FROM player_ext WHERE uid = '.$_SESSION['id']);
$uarr = explode('|',$u['prize_every_day']);
$prize_str = $welcome['holiday_prize']['contents'];
$arr = explode('|',$prize_str);
if($_GET['type'] == 1){
	if($arr[0] == 0){//日常奖励
		msg('尚未开启');
	}else{
		if($uarr[0] < $now){
			$row = explode(',',$arr[0]);
			$task = new task();
			foreach($row as $rv){
				$res = explode(':',$rv);
				$task->saveGetPropsMore($res[0],$res[1]);
				$s.=','.$mempropsid[$res[0]]['name'].'x'.$res[1];
			}
			$s = substr($s,1);
			
			$newstr = $now.'|'.$uarr[1].'|'.$uarr[2];
			$_pm['mysql']->query("UPDATE player_ext SET prize_every_day = '$newstr' WHERE uid = ".$_SESSION['id']);
			msg('每日奖励领取成功，获得'.$s);
		}else{
			msg('已经领取');
		}
	}
}else if($_GET['type'] == 2){
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
			$task->saveGetPropsMore($res[0],$res[1]);
			$s.=','.$mempropsid[$res[0]]['name'].'x'.$res[1];
		}
		$s = substr($s,1);
		
		$newstr = $uarr[0].'|'.$now.'|'.$uarr[2];
		$_pm['mysql']->query("UPDATE player_ext SET prize_every_day = '$newstr' WHERE uid = ".$_SESSION['id']);
		msg('周末奖励领取成功，获得'.$s);
	}
}else if($_GET['type'] == 3){
	$harr = explode(';',$arr[2]);//20100917:1*20,2*30;20101001:5*20,6*30
	if(is_array($harr)){
		foreach($harr as $hv){
			$row = explode(':',$hv);
			if($now == $row[0]){//是节假日
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
		$rs = explode(',',$row[1]);
		$task=new task();
		foreach($rs as $rv){
			$res = explode('*',$rv);
			$task->saveGetPropsMore($res[0],$res[1]);
			$s.=','.$mempropsid[$res[0]]['name'].'x'.$res[1];
		}
		$holidayprizestr = substr($holidayprizestr,6);
		$newstr = $uarr[0].'|'.$uarr[1].'|'.$now;
		$_pm['mysql']->query("UPDATE player_ext SET prize_every_day = '$newstr' WHERE uid = ".$_SESSION['id']);
		$s=substr($s,1);
		msg('节假日奖励领取成功，获得'.$s);
	}
}


function msg($m)
{
	realseLock();
	die($m);
}
?>