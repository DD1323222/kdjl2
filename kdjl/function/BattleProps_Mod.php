<?php
/**
*@Usage: 奖励经验兑换UI显示脚本(兑换物品)。
*@Author: GeFei Su.
*@Write Date:2008-08-27
*@Copyright:www.webgame.com.cn
*/
require_once('../config/config.game.php');
secStart($_pm['mem']);

$cUser = $_pm['mysql']->getOneRecord("SELECT jgvalue 
										FROM battlefield_user 
									   WHERE uid={$_SESSION['id']}");

$wp = $_pm['mysql']->getRecords("SELECT name,need,b.pid as pid
                                   FROM props as p,battlefield_props as b
								  WHERE p.id=b.pid
								");
if (is_array($wp))
{
	foreach ($wp as $k => $rs)
	{
		$plist .= '<tr><td width=250 id="t'.$rs['id'].'"  style="cursor:pointer;" onmouseover="showTip('.$rs['pid'].');this.style.border=\'solid 0px #DFD496\';"  onmouseout="window.parent.UnTip();this.style.border=0;" onclick="sel(this);pid='.$rs['pid'].';">'.$rs['name'].'</td><td>'.$rs['need'].'</td></tr>';
	}
}
else $plist = '';									   

//###########################
// @Load template.
//###########################
$tn = $_game['template'] . 'tpl_battle_props.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);
	
	$src = array('#userjg#',
				 '#usertop#',
	             '#desclist#',
				 '#plist#'				 
				);
	$des = array($cUser['jgvalue'],
	             '',
				 '',
				 $plist       
				);
	$cet = str_replace($src, $des, $tpl);
}
// gzip echo. if maybe.
ob_start('ob_gzip');
echo $cet;
ob_end_flush();
?>
