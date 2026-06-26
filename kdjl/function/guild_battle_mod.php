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


secStart($_pm['mem']);
$_pm['mysql']->query('update player set inmap=0 where id='.$_SESSION['id']);
$battletimearr1 = unserialize($_pm['mem']->get('db_welcome1'));
$activeimg = $battletimearr1['guild_battle'];
$ginfo = $_pm['mysql'] -> getOneRecord("SELECT id,level,name,priv FROM guild,guild_members WHERE guild_members.member_id = {$_SESSION['id']} AND guild_members.guild_id = guild.id");
if(is_array($ginfo)){
	$guild_members = $_pm['mysql'] -> getRecords("SELECT honor,nickname FROM guild_members,player WHERE guild_members.member_id = player.id AND guild_members.guild_id = {$ginfo['id']} ORDER BY honor DESC");
	foreach($guild_members as $k => $v){
		if(empty($v['honor'])){
			$v['honor'] = 0;
		}
		$guild_str .= "<tr><td width='30px'>".(++$k)."</td><td align='left'>{$v['nickname']}</td><td>{$v['honor']}</td></tr>";
	}
	//战书读取
	$arr = $_pm['mysql'] -> getRecords("SELECT challenger_id,name,flags FROM guild_challenges,guild WHERE defenser_id = {$ginfo['id']} AND challenger_id = guild.id");
	if(is_array($arr)){
		foreach($arr as $v){
			if($v['flags'] == 0){
				
				$str .= "<tr><td width='70%'>{$v['name']}</td><td style='cursor:pointer' onclick='accept(".$v['challenger_id'].")'>接受</td></tr>";
			}else $str .= "<tr><td width='70%'>{$v['name']}</td><td>已接受</td></tr>";
		}
	}
	$arr1 = $_pm['mysql'] -> getRecords("SELECT challenger_id,name,flags FROM guild_challenges,guild WHERE challenger_id = {$ginfo['id']} AND defenser_id = guild.id");//echo "SELECT challenger_id,name,flags FROM guild_challenges,guild WHERE challenger_id = {$ginfo['id']}";//print_r($arr1);
	if(is_array($arr1)){
		foreach($arr1 as $v1){
			if($v1['flags'] == 1){
				$str .= "<tr><td width='70%'>{$v1['name']}</td><td>已接受</td></tr>";
			}else{
				$str .= "<tr><td width='70%'>{$v1['name']}</td><td>未接受</td></tr>";
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
			$zrlist .= "<tr><td width='30px'>".(++$k)."</td><td  align='left'>{$v['name']}</td><td>{$v['honor']}</td><td></td></tr>";
		}
	}else{
		foreach ($topzr as $k => $v){
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