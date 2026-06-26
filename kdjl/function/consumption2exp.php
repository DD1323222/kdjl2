<?php
/*
time_config
    consumption2exp_time -> 0800-0900
	consumption2exp_props -> 123,456
	consumption2exp_rate -> 100
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
$setting = $_pm['mem']->get('db_timeconfignew');
if(!is_array($setting)) $setting=unserialize($setting);
if(!is_array($setting))
{
	msg('后台配置数据读取失败(1)！'.print_r($setting,1));
}
if(!isset($setting['consumption2exp_flag']))
{
	msg('缺少活动开启设定(consumption2exp_flag)！');
}

$daysopen=explode('|',$setting['consumption2exp_flag'][0]['days']);
$flag=false;
$today=date('Ymd');
foreach($daysopen as $d)
{
	if($today==$d)
	{
		$flag=true;
		break;
	}
}

if(!$flag)
{
	msg('今天不是活动开放的日期！');
}
if(!isset($setting['consumption2exp_time']))
{
	msg('没有设定活动开启的时间(consumption2exp_time)！');
}

if(!isset($setting['consumption2exp_props']))
{
	msg('没有设定活动相关的道具信息(consumption2exp_props)！');
}

if(!isset($setting['consumption2exp_rate']))
{
	msg('没有设定活动相关的倍率信息(consumption2exp_rate)！');
}

$times=explode('-',$setting['consumption2exp_time'][0]['days']);
$now_m=date("Hi");
$day  =date("Ymd");

if($now_m<$times[0])
{
	msg('活动开启的时间还没有到,也请不要频繁操作,谢谢！');
}

if($now_m>$times[1])
{
	msg('抱歉,活动时间已经过了！');
}

$got = $_pm['mysql']->getOneRecord('select consumption2exp_day from player_ext where uid='.$_SESSION['id']);
$err = mysql_error();

if(strpos($err,'consumption2exp_day')!==false)
{
	$_pm['mysql']->query('alter table player_ext add consumption2exp_day char(8) null default ""');
	$got = $_pm['mysql']->getOneRecord('select consumption2exp_day from player where uid='.$_SESSION['id']);
}

if(!$got)
{
	msg('获取你的设定失败！');
}

if($got['consumption2exp_day']>=date('Ymd'))
{
	msg('你已经领取过了！');
}

$consumption_today = unserialize($_pm['mem']->get('consumption2exp_consumption_'.date('Ymd')));
$consumption_rate   = $setting['consumption2exp_rate'][0]['days'];

if(!$consumption_today){
	$props = $_pm['mem']->get('db_propsid');
	if(!is_array($props)) $props=unserialize($props);
	if(!is_array($props))
	{
		msg('后台配置数据读取失败(2)！');
	}

	$aimprops=explode(',',$setting['consumption2exp_props'][0]['days']);
	$arrSearchProps = array();

	foreach($aimprops as $v)
	{
		$v=intval($v);
		if(!isset($props[$v]))
		{
			msg('数据中无物品: '.$v.'！');
		}else{
			$arrSearchProps[]=$props[$v]['name'];
		}
	}

	$start_time = strtotime(date('Y-m-d 00:00:00'));
	$end_time   = time();

	$sql='select title,pname,nums from yblog where buytime>'.$start_time.' and buytime<'.$end_time;
	$consumptions = $_pm['mysql']->getRecords($sql);

	$consumption_today = 0;
	if($consumptions&&count($consumptions)>0)
	{
		foreach($consumptions as $c)
		{
			foreach($arrSearchProps as $name)
			{
				$pos1=$c['pname']==$name;
				if($pos1!==false)
				{
					$consumption_today += $c['nums'];
					break;
				}
			}
		}
	}

	$_pm['mem']->set(
						array(
							'k'=>'consumption2exp_consumption_'.date('Ymd'),
							'v'=>$consumption_today
							)
					);
}

$user = $_pm['user']->getUserById($_SESSION['id']);
$_bb  = $_pm['user']->getUserPetByIdS($_SESSION['id'],$user['mbid']);//战斗宠物。

if (!is_array($_bb))
{   
	$loop=true;
	$ct=0;
	while($loop)
	{
		$ct++;
		$_bb		 = $_pm['user']->getUserPetByIdS($_SESSION['id'],$user['fightbb']);		
		if (is_array($_bb)) break;
		if($ct>10) msg("取得你的宠物失败,请设置主战宠物再试(".$user['mbib']."-".$user['fightbb'].")!");
	}
}

if(!$_bb['level'])
{
	msg("取得你的宠物失败,请设置主战宠物再试(2)!");
}

$rs = array_merge($_bb,array());
$db_bb=&$rs;

$exp=$consumption_today*$_bb['level']*$consumption_rate;
$sj = saveGetOther($rs, $exp,$_SESSION['id']);

$_pm['mysql']->query('update player_ext set consumption2exp_day="'.date('Ymd').'" where uid='.$_SESSION['id']);

msg("获得经验".($exp)."!");

?>