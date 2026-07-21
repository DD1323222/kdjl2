<?php
require_once('onlineForPrizeInc.php');

$ms = kdjlOnlinePrizeUnlockedSteps($arr['onlinetime_today']);
if($ms < 1){
	msg('还不到领奖时间呢！');
}

$user= $_pm['user']->getUserById($uid);
if(!is_array($user) || empty($user['mbid'])) msg('玩家或主战宠物数据错误！');
$_bb = $_pm['user']->getUserPetByIdS($uid,$user['mbid']);//战斗宠物。

if(!$_bb)
{
	msg('请到点击左侧<宠物资料>,再点击一个宠物设置为主战宠物！');
}
$bbLevel = isset($_bb['level']) ? intval($_bb['level']) : 0;


if($arr['exp_got_step']<$ms)
{
	if(!$_pm['mysql']->query('START TRANSACTION'))
	{
		msg('服务器繁忙，请稍候再试！');
	}
	$arr = $_pm['mysql']->getOneRecord('select exp_got_step,onlinetime_today from player_ext where uid='.$uid.' FOR UPDATE');
	if(!is_array($arr))
	{
		msg('读取在线奖励状态失败！');
	}
	$arr['exp_got_step'] = isset($arr['exp_got_step']) ? intval($arr['exp_got_step']) : 0;
	$arr['onlinetime_today'] = isset($arr['onlinetime_today']) ? intval($arr['onlinetime_today']) : 0;
	$ms = kdjlOnlinePrizeUnlockedSteps($arr['onlinetime_today']);
	if($arr['exp_got_step'] >= $ms)
	{
		msg('<!--OK-->已经领取过了！');
	}
	$requiredSeconds = kdjlOnlinePrizeRequiredSeconds($arr['exp_got_step']);
	if($requiredSeconds < 1 || $arr['onlinetime_today'] < $requiredSeconds)
	{
		msg('还不到领奖时间呢！');
	}
	$prize=preg_split('/\r\n|\r|\n/',$setting['onlineforexp']);
	$prizeset='';
	foreach($prize as $p)
	{
		$t1=explode('>',$p);
		if(count($t1) < 2) continue;
		$t2=explode('-',$t1[0]);
		if(count($t2) < 2) continue;
		if($bbLevel>=intval($t2[0])&&$bbLevel<intval($t2[1]))
		{
			$prizeset=$t1[1];
			break;
		}
	}

	if(!$prizeset)
	{
		msg('后台没有给等级为'.$bbLevel.'的宠物做设定！');
	}

	$prize=explode(",",$prizeset);
	$prizes=array();
	foreach($prize as $p)
	{
		$ps=explode('|',$p);
		$tmp=array();
		foreach($ps as $ap)
		{
			$t=explode(':',trim($ap));
			if(count($t) != 2) msg('奖励配置错误！');
			$pid = intval($t[0]);
			$num = intval($t[1]);
			if($pid < 1 || $num < 1) msg('奖励配置错误！');
			if(!isset($tmp[$pid])) $tmp[$pid] = 0;
			$tmp[$pid] += $num;
		}
		if(empty($tmp)) msg('奖励配置错误！');
		$prizes[]=$tmp;
	}
	if(count($prizes)!=5)
	{
		msg('奖励配置错误！');
	}
	$getPrize=isset($prizes[$arr['exp_got_step']]) ? $prizes[$arr['exp_got_step']] : array();
	if(empty($getPrize))
	{
		msg('领取失败, 第'.$arr['exp_got_step'].'次领取！');
	}

	$user = $_pm['user']->getUserById($uid);
	$props = $_pm['mem']->get('db_propsid');
	if(!is_array($props)) $props=kdjlSafeMemValue($props, array());
	if(!is_array($props))
	{
		msg('后台物品数据读取失败！');
	}

	$task=new task();
	$prizeWord='';
	foreach($getPrize as $k=>$v)
	{
		$pid = intval($k);
		$num = intval($v);
		if($pid < 1 || $num < 1 || !isset($props[$pid]) || !is_array($props[$pid])) msg('奖励配置错误！');
		$rtn=$task->saveGetPropsMore($pid,$num);

		if($rtn !== true)
		{
			$_pm['mysql']->query("rollback");
			msg($rtn === '200' ? '您的背包空间不足，请整理后再来领取！' : '奖励发放失败，请稍候再试！');
		}
		$pname = isset($props[$pid]['name']) ? $props[$pid]['name'] : $pid;
		$prizeWord.=$pname.' '.$num.'个，';
	}
	$currentStep = intval($arr['exp_got_step']);
	$nextStep = $currentStep + 1;
	if(!$_pm['mysql']->query('update player_ext set exp_got_step='.$nextStep.' where uid='.$uid.' and exp_got_step='.$currentStep) || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
		$_pm['mysql']->query('ROLLBACK');
		msg('在线奖励状态保存失败，请稍候再试！');
	}
	if(!$_pm['mysql']->query('COMMIT')){
		msg('在线奖励状态保存失败，请稍候再试！');
	}
	$_pm['mem']->del(MEM_USERBAG_KEY);
	if($arr['exp_got_step']==4)
	{
		msg("<!--OK-->恭喜，您得到了今天最后大奖".$prizeWord."，今日在线奖励已全部发放，祝您游戏愉快！");
	}else{
		msg("<!--OK-->恭喜，您获得在线奖励".$prizeWord."更大的礼包还在后面，继续努力吧…");
	}

}else{
	msg('<!--OK-->你已经领取完毕了！');
}
?>
