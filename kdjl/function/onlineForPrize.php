<?php 
require_once('onlineForPrizeInc.php');

if($onlinem>300){
	$ms=5;
}else if($onlinem>120){
	$ms=4;
}else if($onlinem>60){
	$ms=3;
}else if($onlinem>30){
	$ms=2;
}else if($onlinem>10){
	$ms=1;
}else{
	msg('还不到领奖时间呢！');
}

$user= $_pm['user']->getUserById($_SESSION['id']);
$_bb = $_pm['user']->getUserPetByIdS($_SESSION['id'],$user['mbid']);//战斗宠物。

if(!$_bb)
{
	msg('请到点击左侧<宠物资料>,再点击一个宠物设置为主战宠物！');
}


if($arr['exp_got_step']<$ms)
{
	$prize=explode("\r\n",$setting['onlineforexp']);
	$prizeset='';
	foreach($prize as $p)
	{
		$t1=explode('>',$p);
		$t2=explode('-',$t1[0]);
		if($_bb['level']>=$t2[0]&&$_bb['level']<$t2[1])
		{
			$prizeset=$t1[1];
			break;
		}
	}
	
	if(!$prizeset)
	{
		msg('后台没有给等级为'.$_bb['level'].'的宠物做设定！');
	}

	$prize=explode(",",$prizeset);
	$prizes=array();
	foreach($prize as $p)
	{
		$ps=explode('|',$p);
		$tmp=array();
		foreach($ps as $ap)
		{
			$t=explode(':',$ap);
			$tmp[$t[0]]=$t[1];
		}
		$prizes[]=$tmp;
	}
	if(count($prizes)!=5)
	{
		msg('后台奖品设置不对, 数量不正确（'.print_r($prizes,1).'）！');
	}
	$getPrize=$prizes[$arr['exp_got_step']];
	if(empty($getPrize))
	{
		msg('领取失败, 第'.$arr['exp_got_step'].'次领取！');
	}
	
	$user = $_pm['user']->getUserById($_SESSION['id']);
	$totalget = 0;
	foreach($getPrize as $k=>$v)
	{
		$totalget += $v;
	}
	if ($totalget >= $user['maxbag']){
		msg('您的背包空间不足，请整理后再来领取(1)！');
		die();
	}
	$resbag = $_pm['mysql']->getOneRecord("SELECT count(*) sl FROM userbag WHERE uid = '".$_SESSION['id']."' AND sums > 0 AND zbing = 0");

	if($resbag['sl']+3 >= $user['maxbag'])
	{
		msg('您的背包空间不足，请整理后再来领取(3)！');
		die();
	}
	$props = $_pm['mem']->get('db_propsid');
	if(!is_array($props)) $props=unserialize($props);
	if(!is_array($props))
	{
		msg('后台物品数据读取失败！');
	}

	$task=new task();
	$prizeWord='';
	foreach($getPrize as $k=>$v)
	{
		$rtn=$task->saveGetPropsMore($k,$v);
		
		if($rtn==='200')
		{
			$_pm['mysql']->query("rollback");		
			msg('您的背包空间不足，请整理后再来领取(2)！');
		}
		$prizeWord.=$props[$k]['name'].' '.$v.'个，';
	}
	$_pm['mysql']->query('update player_ext set exp_got_step='.($arr['exp_got_step']+1).' where uid='.$_SESSION['id']);
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