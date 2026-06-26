<?php
require_once('../config/config.game.php');
secStart($_pm['mem']);
require_once('../sec/dblock_fun.php');
$a = getLock($_SESSION['id']);
if(!is_array($a)){
	realseLock();
	die('服务器繁忙，请稍候再试！');
}
$op = $_GET['op'];
$user = $_pm['user']->getUserById($_SESSION['id']);

if($op == 'ajax_guide'){
	$arr = $_pm['mysql'] -> getOneRecord("SELECT new_guide_step FROM player_ext WHERE uid=".$_SESSION['id']);
	echo $arr['new_guide_step'];
}else if($op == 'add_guide_step'){
	$_pm['mysql'] -> query("UPDATE player_ext SET new_guide_step = new_guide_step+1 WHERE uid = {$_SESSION['id']} AND new_guide_step < 20 AND new_guide_step != -1");
	if(mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){
		realseLock();
		die('操作错误(1)');
	}
	$prize_arr=array(3=>'1308:10',4=>'1241:2',6=>'912:10',8=>'1039:1',14=>'1308:5,1992:5,2493:1',20=>'2047:1');
	$sql = "SELECT new_guide_step FROM player_ext WHERE uid = {$_SESSION['id']}";
	$user1 = $_pm['mysql'] -> getOneRecord($sql);
	if(!array_key_exists($user1['new_guide_step'],$prize_arr)){
		realseLock();
		die("操作错误(2)");
	}
	$prize = $prize_arr[$user1['new_guide_step']];
	$arr = explode(',',$prize);
	$task = new task();
	foreach($arr as $v){
		$inarr = explode(':',$v);
		$task->saveGetPropsMore($inarr[0],$inarr[1]);
	}
}else if($op == 'do_over'){
	$sql = "SELECT new_guide_step FROM player_ext WHERE uid = {$_SESSION['id']}";
	$user1 = $_pm['mysql'] -> getOneRecord($sql);
	if($user1['new_guide_step'] >= 20 || $user1['new_guide_step'] < 0){
		realseLock();
		die('操作有误！');
	}
	$task = new task();
	$task->saveGetPropsMore(2047,1);

	$_pm['mysql'] -> query("UPDATE player_ext SET new_guide_step = -1 WHERE uid = {$_SESSION['id']}");
	realseLock();
	die('跳过新手引导，获得新手90级礼包。');
}
realseLock();
?>