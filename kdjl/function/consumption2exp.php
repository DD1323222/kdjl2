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
	global $_pm;
	if(isset($_pm['mysql'])) $_pm['mysql']->query('ROLLBACK');
	realseLock();
	die($m);
}

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('登录状态无效！');
$_pm['mysql']->addColumnIfMissing('player_ext', 'consumption2exp_day', 'char(8) null default ""');
$a = getLock($uid);
if(!is_array($a)){
	msg('请不要过快点击,谢谢！');
}
$setting = $_pm['mem']->get('db_timeconfignew');
if(!is_array($setting)) $setting=kdjlSafeMemValue($setting, array());
if(!is_array($setting))
{
	msg('后台配置数据读取失败(1)！');
}
if(!isset($setting['consumption2exp_flag']))
{
	msg('缺少活动开启设定(consumption2exp_flag)！');
}

$flagDays = isset($setting['consumption2exp_flag'][0]['days']) ? $setting['consumption2exp_flag'][0]['days'] : '';
$daysopen=explode('|',$flagDays);
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

$timeSetting = isset($setting['consumption2exp_time'][0]['days']) ? $setting['consumption2exp_time'][0]['days'] : '';
$times=explode('-',$timeSetting);
if(count($times) < 2)
{
	msg('活动时间配置错误！');
}
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

if(!$_pm['mysql']->query("INSERT INTO player_ext(uid,bbshow) VALUES({$uid},5) ON DUPLICATE KEY UPDATE uid=uid"))
{
	msg('初始化玩家活动数据失败！');
}

$got = $_pm['mysql']->getOneRecord('select consumption2exp_day from player_ext where uid='.$uid.' FOR UPDATE');
if($got === false && mysql_errno($_pm['mysql']->getConn()) != 0)
{
	msg('获取领取状态失败，请稍候再试！');
}

if(!$got)
{
	msg('获取你的设定失败！');
}

$gotDay = isset($got['consumption2exp_day']) ? $got['consumption2exp_day'] : '';
if($gotDay>=date('Ymd'))
{
	msg('你已经领取过了！');
}

$consumption_today = kdjlSafeMemValue($_pm['mem']->get('consumption2exp_consumption_'.date('Ymd')), array());
$consumption_rate = isset($setting['consumption2exp_rate'][0]['days']) ? intval($setting['consumption2exp_rate'][0]['days']) : 0;
if($consumption_rate < 1) $consumption_rate = 1;

if(!$consumption_today){
	$props = kdjlSafeMemValue($_pm['mem']->get('db_propsid'), array());
	if(!is_array($props))
	{
		msg('后台配置数据读取失败(2)！');
	}

	$propsSetting = isset($setting['consumption2exp_props'][0]['days']) ? $setting['consumption2exp_props'][0]['days'] : '';
	$aimprops=explode(',',$propsSetting);
	$arrSearchProps = array();

	foreach($aimprops as $v)
	{
		$v=intval($v);
		if(!isset($props[$v]))
		{
			msg('数据中无物品: '.$v.'！');
		}else{
			$arrSearchProps[]=isset($props[$v]['name']) ? $props[$v]['name'] : '';
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
				$pname = isset($c['pname']) ? $c['pname'] : '';
				$nums = isset($c['nums']) ? intval($c['nums']) : 0;
				$pos1=$pname==$name;
				if($pos1!==false)
				{
					$consumption_today += $nums;
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

$user = $_pm['user']->getUserById($uid);
if(!is_array($user)) msg("读取玩家数据失败！");
$mbid = isset($user['mbid']) ? intval($user['mbid']) : 0;
$fightbb = isset($user['fightbb']) ? intval($user['fightbb']) : 0;
$user['mbid'] = $mbid;
$user['fightbb'] = $fightbb;
$user['mbib'] = $mbid;
$_bb  = $_pm['user']->getUserPetByIdS($uid,$user['mbid']);//战斗宠物。

if (!is_array($_bb))
{
	$loop=true;
	$ct=0;
	while($loop)
	{
		$ct++;
		$_bb		 = $_pm['user']->getUserPetByIdS($uid,$user['fightbb']);
		if (is_array($_bb)) break;
		if($ct>10) msg("取得你的宠物失败,请设置主战宠物再试(".$user['mbib']."-".$user['fightbb'].")!");
	}
}

if(!isset($_bb['level']) || !$_bb['level'])
{
	msg("取得你的宠物失败,请设置主战宠物再试(2)!");
}
if(intval($_bb['level']) >= 130)
{
	msg('主战宠物已经达到最高等级！');
}

$expBefore = $_pm['mysql']->getOneRecord('SELECT level,nowexp FROM userbb WHERE id='.intval($_bb['id']).' AND uid='.$uid.' FOR UPDATE');
if(!is_array($expBefore))
{
	msg('读取宠物经验状态失败！');
}

$rs = array_merge($_bb,array());
$db_bb=&$rs;

$exp=$consumption_today*$_bb['level']*$consumption_rate;
if($exp <= 0)
{
	msg('当前没有可领取的奖励经验！');
}
$sj = saveGetOther($rs, $exp,$uid);
$expAfter = $_pm['mysql']->getOneRecord('SELECT level,nowexp FROM userbb WHERE id='.intval($_bb['id']).' AND uid='.$uid.' FOR UPDATE');
if(!is_array($expAfter) ||
	(intval($expAfter['level']) == intval($expBefore['level']) && intval($expAfter['nowexp']) == intval($expBefore['nowexp'])))
{
	msg('保存宠物经验失败！');
}

$claimDay = date('Ymd');
$claimed = $_pm['mysql']->query('update player_ext set consumption2exp_day="'.$claimDay.'" where uid='.$uid.' and ifnull(consumption2exp_day,"")<"'.$claimDay.'"');
if(!$claimed || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
{
	$_pm['mysql']->query('ROLLBACK');
	realseLock();
	die('保存领取状态失败！');
}
if(!$_pm['mysql']->query('COMMIT'))
{
	$_pm['mysql']->query('ROLLBACK');
	realseLock();
	die('保存领取状态失败！');
}

realseLock();
die("获得经验".($exp)."!");

?>
