<?php
/*
time_config
    callback -> 0-10>1:2,2:1#0-10>1:2,2:1#0-10>1:2,2:1#0-10>1:2,2:1
*/
require_once('../config/config.game.php');
secStart($_pm['mem']);
require_once('../sec/dblock_fun.php');
function msg($m)
{
	realseLock();
	die($m);
}

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
//$echo = '<'.microtime_float()."-";
$a = getLock($_SESSION['id']);
//$echo .= ''.microtime_float().">\r\n";
//echo $echo;


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
	if($row['code']=='callback'){
		$callback=$row['contents'];
		break;
	}
}

if(!$callback)
{
	msg('活动没有开启！');
}

$day=time()-$_SESSION['lastvtime'];
$getM=$_pm['mem']->get('callgeted_'.$_SESSION['id']);

if($day<30*24*3600||$getM)
{
	msg('很遗憾，您已经领取奖励或者不够资格！');
}

$user= $_pm['user']->getUserById($_SESSION['id']);
$_bb = $_pm['user']->getUserPetByIdS($_SESSION['id'],$user['mbid']);//战斗宠物。

if(!$_bb)
{
	msg('请到点击左侧<宠物资料>,再点击一个宠物设置为主战宠物！');
}

//0-10>1:2,2:1#10-30>3:2,4:1#30-90>5:2,6:1#90-10000>7:2,8:1
$settings=explode('#',$callback);

$getPrize=array();
foreach($settings as $se)
{
	$t1=explode('>',$se);
	$t2=explode('-',$t1[0]);
	if(
		$_bb['czl']>=$t2[0]
		&&
		$_bb['czl']<$t2[1]
	)
	{
		$t3=explode(',',$t1[1]);
		foreach($t3 as $t4)
		{
			$t5=explode(':',$t4);
			if(count($t5)==2)
			{
				$getPrize[$t5[0]]=$t5[1];
			}
		}
		break;
	}	
}

if(empty($getPrize))
{
	msg('后台奖品设置不对, 没有给成长为:'.$_bb['czl'].'的设定奖品！');
}

$totalget = 0;
foreach($getPrize as $k=>$v)
{
	$totalget += $v;
}

if ($totalget >= $user['maxbag']){
	msg('您的背包空间不足，请整理后再来领取(需要约:'.$totalget.')！');
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

$swfData=iconv('gbk','utf-8','欢迎曾经的朋友'.$_SESSION['nickname'].'，回到【口袋精灵】的家庭，获得了回归奖励：'.substr($prizeWord,0,-2).'！');
require_once('../socketChat/config.chat.php');	
require_once('../kernel/socketmsg.v1.php');
$s=new socketmsg();
$s->sendMsg('an|'.$swfData);
$_SESSION['lastvtime']=time();
session_write_close();
$_pm['mem']->set(array('k'=>'callgeted_'.$_SESSION['id'],'v'=>1));
msg("<!--OK-->欢迎您的回归，恭喜您获得了老玩家回归活动的奖励物品：".$prizeWord."祝您游戏愉快！");

?>