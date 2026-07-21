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
require_once(dirname(__FILE__).'/online_prize_common.php');
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid <= 0)
{
	die('登录状态无效！');
}
function msg($m)
{
	global $_pm;
	if(isset($_pm['mysql'])) $_pm['mysql']->query('ROLLBACK');
	realseLock();
	die($m);
}

$a = getLock($uid);
if(!is_array($a)){
	msg('请不要过快点击,谢谢！');
}

if(!$_pm['mysql']->query("INSERT INTO player_ext(uid,bbshow) VALUES({$uid},5) ON DUPLICATE KEY UPDATE uid=uid"))
{
	msg('初始化玩家在线奖励数据失败！');
}

$setting = $_pm['mem']->get('db_welcome');
if(!is_array($setting)) $setting=kdjlSafeMemValue($setting, array());
if(!is_array($setting))
{
	msg('后台配置数据读取失败(1)！');
}
$callback=false;
$setting['onlineforexp'] = '';
foreach($setting as $row)
{
	if(!is_array($row) || !isset($row['code'])) continue;
	if($row['code']=='onlineforexp'){
		$setting['onlineforexp']=isset($row['contents']) ? $row['contents'] : '';
		break;
	}
}

if(!$setting['onlineforexp'])
{
	msg('活动没有开启！');
}

require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
$s=new socketmsg();
$s->sendMsg('updateUserOnline',$uid);

$arr = $_pm['mysql']->getOneRecord('select exp_got_step,last_logintime,onlinetime_today,last_online_day,last_onlinetime,onlinetime from player_ext where uid='.$uid);

if(!$arr)
{
	msg('获取你的数据失败！');
}
$arr['last_logintime'] = isset($arr['last_logintime']) ? intval($arr['last_logintime']) : 0;
$arr['onlinetime_today'] = isset($arr['onlinetime_today']) ? intval($arr['onlinetime_today']) : 0;
$arr['last_online_day'] = isset($arr['last_online_day']) ? $arr['last_online_day'] : '';
$arr['last_onlinetime'] = isset($arr['last_onlinetime']) ? intval($arr['last_onlinetime']) : 0;
$arr['onlinetime'] = isset($arr['onlinetime']) ? intval($arr['onlinetime']) : 0;

$tdStr=date('Ymd');
if($arr['last_online_day']!=$tdStr)
{
	if(date('Ymd',$arr['last_logintime'])!=$tdStr&&$arr['last_logintime']>10000000)
	{//挂机从头天挂到今天的
		$sql='update player_ext set exp_got_step=0,last_online_day="'.date('Ymd').'",onlinetime_today="'.(date("H")*3600+date("i")*60+date("s")).'",last_onlinetime=onlinetime where uid='.$uid;
		if(!$_pm['mysql'] -> query($sql)) msg('保存在线奖励状态失败！');
	}else{//肯定是用外挂得
		$sql='update player_ext set exp_got_step=0,last_online_day="'.date('Ymd').'",onlinetime_today=0,last_onlinetime=onlinetime where uid='.$uid;
		if(!$_pm['mysql'] -> query($sql)) msg('保存在线奖励状态失败！');
		if(!$_pm['mysql']->query('COMMIT')) msg('保存在线奖励状态失败！');
		realseLock();
		die('还不到领奖时间呢！');
	}
}else{
	$sql='update player_ext set onlinetime_today=GREATEST(COALESCE(onlinetime_today,0)+COALESCE(onlinetime,0)-COALESCE(last_onlinetime,0),0),last_onlinetime=COALESCE(onlinetime,0) where uid='.$uid;
	if(!$_pm['mysql'] -> query($sql)) msg('保存在线奖励状态失败！');
}
if(!$_pm['mysql']->query('COMMIT'))
{
	msg('保存在线奖励状态失败！');
}
$arr = $_pm['mysql']->getOneRecord('select exp_got_step,onlinetime_today from player_ext where uid='.$uid);
if(!is_array($arr)){
	msg('获取你的数据失败！');
}
$arr['exp_got_step'] = isset($arr['exp_got_step']) ? intval($arr['exp_got_step']) : 0;
$arr['onlinetime_today'] = isset($arr['onlinetime_today']) ? intval($arr['onlinetime_today']) : 0;

$entryScript = isset($_SERVER['SCRIPT_FILENAME']) ? basename($_SERVER['SCRIPT_FILENAME']) : '';
if($entryScript === basename(__FILE__))
{
	realseLock();
	die('在线奖励入口错误！');
}

?>
