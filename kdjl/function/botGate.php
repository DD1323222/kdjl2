<?php
/*
require_once('../config/config.game.php');
$user		= $_pm['user']->getUserById($_SESSION['id']);
$time = $user['bot_time'];
$fight_times = $time/20;	//场次
$_bb = $_pm['user']->getUserPetByIdS($_SESSION['id'],$user['fightbb']);//战斗宠物。
$id = 1;
$_sk		 = $_pm['user']->getUserPetSkillByIdS($_SESSION['id'],$_bb['id'],$id);
if($user['bot_map_id'] != 0)
{
	$mapData = $_pm['mysql']->getOneRecord("SELECT name,level FROM map WHERE id = '{$user['bot_map_id']}'");
	$levelArr = explode(",",$mapData['level']);
	$level1 = $levelArr[0];
	$level2 = $levelArr[1];
	$gpcData = $_pm['mysql']->getRecords("SELECT * FROM gpc WHERE level >= ".$level1." AND level < ".$level2." AND boss = 1");
	$gs = array();
	$rand_num = rand(0,count($gpcData)-1);
	$gs = $gpcData[$rand_num];
	if (is_array($_bb) && is_array($_sk) )
	{	
		// Componse array .
		$rs = array_merge($_bb,
		array(
				's_name'  => $_sk['name'],
				's_level' => $_sk['level'],
				's_vary'  => $_sk['vary'],
				's_wx'	  => $_sk['wx'],
				's_value' => $_sk['value'],
				's_plus'  => $_sk['plus'],
				's_uhp'   => $_sk['uhp'],
				's_ump'   => $_sk['ump'],
				's_imgeft'   => $_sk['img']
			 )
		);
	}
	//########################
	// 附加装备属性到战斗中。
	#############################
	$att = getzbAttrib($rs['id']);	
	$rs['ac']	+= $att['ac'];
	$rs['mc']	+= $att['mc'];
	$rs['hits'] += $att['hits'];
	$rs['speed']+= $att['speed'];
	$rs['miss']	+= $att['miss'];

	$aobj = new Ack($rs, $gs);
	$aobj -> getSkillAck();
	$myHurt = $aobj->skillack;		//我方伤害
	$aobj1 = new Ack1($gs, $rs);
	$aobj1 -> getSkillAck();
	$otherHurt = $aobj1->skillack;	//敌方伤害
	//怪物挨血
	$ghurt = $myHurt;
	//怪物血量
	$gall =  $gs['hp'];
	//我方挨血
	$myhurt =  $otherHurt;
	//我方血量
	$myall = $rs['srchp'];
	while($gall > 0 && $myall > 0)
	{
		$gall -= $ghurt;
		if($gall <= 0)
		{
			$fight_times = intval($fight_times*$myall/$rs['srchp']);
			break;
		}
		$myall -= $myhurt;
		if($myall <= 0)
		{
			$fight_times = 0;
			$myall = 0;
			break;
		}
	}
}


$time_str = '';


if($time > 24*60*60)
{
	$time_str .= intval($time/(24*60*60))."天 ";
	$time = $time%(24*60*60);
}
if($time > 60*60)
{
	$time_str .= intval($time/(60*60))."小时 ";
	$time = $time%(60*60);
}
if($time > 60)
{
	$time_str .= intval($time/(60))."分 ";
	$time = $time%(60);
}
$time_str .= $time%(60)."秒 ";


$map_str = $mapData?$mapData['name']:"无";

$get_exp = 0;
$get_money = 0;
$gpcArr = array();
$dropArr = array();
$prpid = array();
$pidArr = array();
$itemDic = array();
$sendPropArr = array();

for($i=0;$i<$fight_times;$i++)
{
	$rand_num = rand(0,count($gpcData)-1);
	$gpcArr[$gpcData[$rand_num]['name']]++;
	$get_exp += $gpcData[$rand_num]['exps'];
	$get_money += $gpcData[$rand_num]['money'];
	$prpid[] = getProps($gpcData[$rand_num]['droplist']);
}
foreach($prpid as $info)
{
	if($info != '')
	{
		$midArr = explode(",",$info);
		for($i=0;$i<count($midArr);$i++)
		{
			if(!in_array($midArr[$i],$pidArr))
			{
				$pidArr[] = $midArr[$i];
			}
			$dropArr[$midArr[$i]]++;
			$sendPropArr[] = $midArr[$i];
		}
	}
}
if(count($pidArr) > 0 )
{
	$sql = "SELECT id,name FROM props WHERE id IN (".implode(",",$pidArr).")";
	$itemData = $_pm['mysql']->getRecords($sql);
	foreach($itemData as $info)
	{
		$itemDic[$info['id']] = $info['name'];
	}
}
$gpc_str = '<br />';
foreach($gpcArr as $key => $info)
{
	$name_length = strlen($key);
	$base_str = "";
	for($base=22;$base>$name_length;$base--)
	{
		$base_str .= " ";
	}
	$gpc_str .= "     <font color='#0099FF'>{$key}</font><font color='#990033'>{$base_str}× {$info}</font><br />";
}


$str = "<strong>※离线战斗结算</strong><br /><br />";
$str .= "<font>挂机时间：</font><font color='#990033'>{$time_str}</font><br />";
$str .= "<font>挂机地图：</font><font color='#990033'>{$map_str}</font><br />";
$str .= "<font>击杀怪物：</font><font color='#990033'>{$gpc_str}</font><br />";
$str .= "<font>获得经验：</font><font color='#990033'>{$get_exp}点</font><br />";
$str .= "<font>获得金币：</font><font color='#990033'>{$get_money}个</font><br />";
$str .= "<font>获得物品：</font><br /><br />";
if(count($dropArr) > 0)
{
	foreach($dropArr as $key => $val)
	{
		$name = $itemDic[$key];
		$name_length = strlen($name);
		$base_str = "";
		for($base=22;$base>$name_length;$base--)
		{
			$base_str .= " ";
		}
		$str .= "     <font color='#0099FF'>{$name}</font><font color='#990033'>{$base_str}× {$val}</font><br />";
	}
	$str .= "<br /><br />";
}
else
{
	$str .= "    无";
}
$user['money'] += $get_money;
if ($user['money'] >= 1000000000)
{
	$user['money']=1000000000;
}
saveGetOther($rs, $get_exp);
saveGetPropsa(implode(",",$sendPropArr));
$sql = "UPDATE player SET money={$user['money']},bot_time=0,heart_time=".time()." WHERE id={$_SESSION['id']}";
$_pm['mysql']->query($sql);
*/
require_once('../config/config.game.php');
$str='活动内容：<br>
1、《口袋精灵2》战斗辅助手机版隆重上线了!<br>
2、选择地图即可在线挂机，即将开放背包、合宠等系统！<br>
3、《口袋精灵2》官方QQ群：133169475，官方唯一客服QQ：1066087792<br>
4、口袋新宠【厨女菇】火爆上线！详见群文件百科。<br>
5、活动任务-【新手任务】每天奖励，源源不断！<br>
<span style="color:red;">警告：请牢记并保管好自己的帐号、密码，如果随便给人使用、交易被骗等行为造成不能登录、盗号等情况，一概自己负责！但是扰乱游戏秩序或存在骗子行为的玩家，一经查实立即封杀所有号！请三思！</span>';
echo "<br><br><br><br>";
?>
