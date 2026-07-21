<?php
session_start();
// Cancel display online player count
/*if(in_array($_SESSION['username'],$_gm['name']) ) {
}else{
exit();
}*/
require_once('../config/config.game.php');
secStart($_pm['mem']);
$min = 0;
$httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$hostDotPos = strpos($httpHost, '.');

/*
$rs = $_pm['mysql']->getOneRecord("
							select
								count(id) olu
							from
								player
							where lastvtime>unix_timestamp()-{$min}
						 ");
*/
$domainPrefix = $hostDotPos === false ? $httpHost : substr($httpHost,0,$hostDotPos);
//echo $domainPrefix.'_online_user';
if(substr($domainPrefix,0,5) == 'kdjls')
{
	$domainPrefix = 'pm'.substr($domainPrefix,5);
}
$domainPrefix = 'pokeelf';
$rs = kdjlSafeMemValue($_pm['mem']->get($domainPrefix.'_online_user'), 0);
echo $rs+$min;

$setting = kdjlSafeMemValue($_pm['mem']->get('db_timeconfignew'), array());

if(!is_array($setting))
{
	echo '<!--后台配置数据读取失败(1)！'.'-->';die();
}

if(!isset($setting['consumption2exp_time']))
{
	echo '<!--没有设定活动开启的时间(consumption2exp_time)！'.'-->';die();
}

if(!isset($setting['consumption2exp_time'][0]) || !is_array($setting['consumption2exp_time'][0]) || !isset($setting['consumption2exp_time'][0]['days']))
{
	die('<!--consumption2exp_time empty-->');
}
$times=explode('-',$setting['consumption2exp_time'][0]['days']);
if(count($times) < 2)
{
	die('<!--consumption2exp_time format error-->');
}
$now_m=date("Hi");
if($now_m<$times[0])
{
	echo '<!--活动开启的时间还没有到,也请不要频繁操作,谢谢！-->';die();
}

if($now_m>$times[1])
{
	echo '<!--抱歉,活动时间已经过了！-->';die();
}
if(!isset($setting['consumption2exp_flag'][0]) || !is_array($setting['consumption2exp_flag'][0]) || !isset($setting['consumption2exp_flag'][0]['days']))
{
	die('<!--consumption2exp_flag empty-->');
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

if($now_m<$times[0]||$now_m>$times[1]||!$flag)
{
	echo '<!--'.$now_m.'<'.$times[0].'||'.$now_m.'>'.$times[1].'||'.$flag.'-->';
}else{
	echo '<!--consumption2exp-->';
}
?>
