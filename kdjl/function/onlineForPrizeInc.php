<?php
/*
time_config
    onlineforexp -> 0-10>1:2|2:1,3:2|4:1,5:2|6:1,7:2|8:1,9:2|12:1
10-20>1:2|2:1,3:2|4:1,5:2|6:1,7:2|8:1,9:2|12:1
20-30>1:2|2:1,3:2|4:1,5:2|6:1,7:2|8:1,9:2|12:1
30-230>1:2|2:1,3:2|4:1,5:2|6:1,7:2|8:1,9:2|12:1
*/
require_once('../config/config.game.php');
secStart($_pm['mem']);
require_once('../sec/dblock_fun.php');
function msg($m)
{
	realseLock();
	die($m);
}

$a = getLock($_SESSION['id']);
if(!is_array($a)){
	msg('请不要过快点击,谢谢！');
}

$setting = $_pm['mem']->get('db_welcome');
if(!is_array($setting)) $setting=unserialize($setting);
if(!is_array($setting))
{
	msg('后台配置数据读取失败(1)！');
}
$callback=false;
foreach($setting as $row)
{
	if($row['code']=='onlineforexp'){
		$setting['onlineforexp']=$row['contents'];
		break;
	}
}

if(!$setting['onlineforexp'])
{
	msg('活动没有开启！');
}

require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
$s=new socketmsg();
$s->sendMsg('updateUserOnline',$_SESSION['id']);

$arr = $_pm['mysql']->getOneRecord('select exp_got_step,last_logintime,onlinetime_today,last_online_day,last_onlinetime,onlinetime from player_ext where uid='.$_SESSION['id']);

if(!$arr)
{
	msg('获取你的数据失败！');
}

$tdStr=date('Ymd');
if($arr['last_online_day']!=$tdStr)
{
	if(date('Ymd',$arr['last_logintime'])!=$tdStr&&$arr['last_logintime']>10000000)
	{//挂机从头天挂到今天的
		$sql='update player_ext set exp_got_step=0,last_online_day="'.date('Ymd').'",onlinetime_today="'.(date("H")*3600+date("i")*60+date("s")).'",last_onlinetime=onlinetime where uid='.$_SESSION['id'];
		$_pm['mysql'] -> query($sql);
	}else{//肯定是用外挂得
		$sql='update player_ext set exp_got_step=0,last_online_day="'.date('Ymd').'",onlinetime_today=0,last_onlinetime=onlinetime where uid='.$_SESSION['id'];
		$_pm['mysql'] -> query($sql);
		msg('还不到领奖时间呢！');
	}
}else{
	$sql='update player_ext set onlinetime_today=onlinetime_today+onlinetime-last_onlinetime,last_onlinetime=onlinetime where uid='.$_SESSION['id'];
	$_pm['mysql'] -> query($sql);
}
realseLock();
$a = getLock($_SESSION['id']);
$arr = $_pm['mysql']->getOneRecord('select exp_got_step,onlinetime_today from player_ext where uid='.$_SESSION['id']);	

$onlinem=ceil($arr['onlinetime_today']/60);
//$onlinem=ceil($arr['onlinetime_today']);//测试的时候,一秒钟当一分钟

?>