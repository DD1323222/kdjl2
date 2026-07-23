<?php
/**
*@Usage: 战场入口
*@Author: GeFei Su.
*@Write Date:2008-08-27
*@Copyright:www.webgame.com.cn
Note:
    2: 重新开始.
	1: 战场结束.
	0: 战场初始值
*/
session_start();
set_time_limit(3600);
require_once('../config/config.game.php');
require_once(dirname(__FILE__).'/../sec/activity_robot_fnc.php');
require_once(dirname(__FILE__).'/../sec/battle_lifecycle_fnc.php');


secStart($_pm['mem']);
kdjlGuildBattleTick($_pm['mysql'], $_pm['mem'], time());
kdjlRunActivityAutomation($_pm['mysql'], $_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');
if(!$_pm['mysql']->query('UPDATE player SET inmap=0 WHERE id='.$uid)) die('');
if(defined('MEM_USER_KEY')) $_pm['mem']->del(MEM_USER_KEY);
$battletimearr1 = $_pm['mem']->get('db_welcome1');
if(!is_array($battletimearr1)) $battletimearr1 = kdjlSafeMemValue($battletimearr1, array());
$activeimg = (is_array($battletimearr1) && isset($battletimearr1['guild_battle'])) ? $battletimearr1['guild_battle'] : '';
$guild_str = '';
$str = '';
$zrlist = '';
$cet = '';
$ginfo = $_pm['mysql'] -> getOneRecord("SELECT id,level,name,priv FROM guild,guild_members WHERE guild_members.member_id = {$uid} AND guild_members.guild_id = guild.id");
if(is_array($ginfo)){
	$ginfo['id'] = isset($ginfo['id']) ? intval($ginfo['id']) : 0;
	$ginfo['level'] = isset($ginfo['level']) ? intval($ginfo['level']) : 0;
	$ginfo['priv'] = isset($ginfo['priv']) ? intval($ginfo['priv']) : 0;
	$guild_members = $_pm['mysql'] -> getRecords("SELECT honor,nickname FROM guild_members,player WHERE guild_members.member_id = player.id AND guild_members.guild_id = {$ginfo['id']} ORDER BY honor DESC");
	if(!is_array($guild_members)) $guild_members = array();
	foreach($guild_members as $k => $v){
		if(!is_array($v)) continue;
		$v['nickname'] = isset($v['nickname']) ? $v['nickname'] : '';
		$v['nickname'] = htmlspecialchars((string)$v['nickname'], ENT_QUOTES, 'UTF-8');
		if(empty($v['honor'])){
			$v['honor'] = 0;
		}
		$v['honor'] = intval($v['honor']);
		$guild_str .= "<tr><td width='30px'>".(++$k)."</td><td align='left'>{$v['nickname']}</td><td>{$v['honor']}</td></tr>";
	}
	//战书读取
	$arr = $_pm['mysql'] -> getRecords("SELECT challenger_id,name,flags FROM guild_challenges,guild WHERE defenser_id = {$ginfo['id']} AND challenger_id = guild.id");
	if(is_array($arr)){
		foreach($arr as $v){
			if(!is_array($v)) continue;
			$v['challenger_id'] = isset($v['challenger_id']) ? intval($v['challenger_id']) : 0;
			$v['name'] = isset($v['name']) ? $v['name'] : '';
			$v['name'] = htmlspecialchars((string)$v['name'], ENT_QUOTES, 'UTF-8');
			$v['flags'] = isset($v['flags']) ? intval($v['flags']) : 0;
			if($v['flags'] == 0){

				$str .= "<tr><td>{$v['name']}</td><td style='cursor:pointer' onclick='accept(".$v['challenger_id'].")'>接受</td></tr>";
			}else $str .= "<tr><td>{$v['name']}</td><td>已接受</td></tr>";
		}
	}
	$arr1 = $_pm['mysql'] -> getRecords("SELECT challenger_id,name,flags FROM guild_challenges,guild WHERE challenger_id = {$ginfo['id']} AND defenser_id = guild.id");//echo "SELECT challenger_id,name,flags FROM guild_challenges,guild WHERE challenger_id = {$ginfo['id']}";//print_r($arr1);
	if(is_array($arr1)){
		foreach($arr1 as $v1){
			if(!is_array($v1)) continue;
			$v1['name'] = isset($v1['name']) ? $v1['name'] : '';
			$v1['name'] = htmlspecialchars((string)$v1['name'], ENT_QUOTES, 'UTF-8');
			$v1['flags'] = isset($v1['flags']) ? intval($v1['flags']) : 0;
			if($v1['flags'] == 1){
				$str .= "<tr><td>{$v1['name']}</td><td>已接受</td></tr>";
			}else{
				$str .= "<tr><td>{$v1['name']}</td><td>未接受</td></tr>";
			}
		}
	}
	if(empty($str)){
		$str .= "<tr><td height='25' align='center' colspan=2>没有战书</td></tr>";
	}


}else{
	$guild_str = '没有加入家族！';// 没有加入家族
}


$topzr = $_pm['mysql'] -> getRecords("SELECT id,level,name,president_id,honor FROM guild ORDER BY honor DESC");//print_r($topzr);exit;
if(!is_array($topzr)){
	$zrlist = '暂时没有家族';
}else{
	if(!is_array($ginfo)){
		foreach ($topzr as $k => $v){
			if(!is_array($v)) continue;
			$v['name'] = isset($v['name']) ? $v['name'] : '';
			$v['honor'] = isset($v['honor']) ? intval($v['honor']) : 0;
			$v['name'] = htmlspecialchars((string)$v['name'], ENT_QUOTES, 'UTF-8');
			$zrlist .= "<tr><td width='30px'>".(++$k)."</td><td  align='left'>{$v['name']}</td><td>{$v['honor']}</td><td></td></tr>";
		}
	}else{
		foreach ($topzr as $k => $v){
			if(!is_array($v)) continue;
			$v['id'] = isset($v['id']) ? intval($v['id']) : 0;
			$v['level'] = isset($v['level']) ? intval($v['level']) : 0;
			$v['name'] = isset($v['name']) ? $v['name'] : '';
			$v['honor'] = isset($v['honor']) ? intval($v['honor']) : 0;
			$v['name'] = htmlspecialchars((string)$v['name'], ENT_QUOTES, 'UTF-8');
			$clevel = $ginfo['level'] - $v['level'];
			if($clevel <= 5 && $clevel >= -5 && $v['id'] != $ginfo['id']){
				if($ginfo['priv'] >= 2){
					$zrlist .= "<tr><td width='30px'>".(++$k)."</td><td  align='left'>{$v['name']}</td><td>{$v['honor']}</td><td style='cursor:pointer' onclick='down_the_gauntlet(".$v['id'].")'><img src='../new_images/ui/icon16.jpg' width='49' height='17' /></td></tr>";
				}else{
					$zrlist .= "<tr><td width='30px'>".(++$k)."</td><td  align='left'>{$v['name']}</td><td>{$v['honor']}</td><td><img src='../new_images/ui/icon16.jpg' width='49' height='17' /></td></tr>";
				}
			}else{
				$zrlist .= "<tr><td width='30px'>".(++$k)."</td><td  align='left'>{$v['name']}</td><td>{$v['honor']}</td><td></td></tr>";
			}
		}
	}
}

//###########################
// @Load template.
//###########################
$tn = $_game['template'] . 'tpl_guild_battle.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);

	$src = array('#zrlist#',
				'#aylist#',
				'#challenge#',
				'#activity_dis_2#'
				);
	$des = array($zrlist,
				$guild_str,
				$str,
				$activeimg
				);
	$cet = str_replace($src, $des, $tpl);
}
// gzip echo. if maybe.
ob_start('ob_gzip');
echo $cet;
ob_end_flush();
?>
