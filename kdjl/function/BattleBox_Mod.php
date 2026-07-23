<?php
/**
*@Usage: 战场入口
*@Author: GeFei Su.
*@Write Date:2008-08-27
*@Copyright:www.webgame.com.cn
*/
require_once('../config/config.game.php');
require_once(dirname(__FILE__).'/../sec/battle_lifecycle_fnc.php');
secStart($_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');
kdjlSacredBattleTick($_pm['mysql'], $_pm['mem'], time());
$cUser = $_pm['mysql']->getOneRecord("SELECT jgvalue,curjgvalue
										FROM battlefield_user
									   WHERE uid={$uid}
									ORDER BY id LIMIT 1");
if(!is_array($cUser))
{
	$cUser = array('jgvalue' => 0, 'curjgvalue' => 0);
}

//###########################
// @Load template.
//###########################
$tn = $_game['template'] . 'tpl_battle_box.html';
$cet = '';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);

	$src = array('#userjg#',
				 '#usertop#',
	             '#desclist#',
				 '#usercurjg#'
				);
	$des = array($cUser['jgvalue'],
	             '',
				 '',
				 $cUser['curjgvalue']
				);
	$cet = str_replace($src, $des, $tpl);
}
// gzip echo. if maybe.
ob_start('ob_gzip');
echo $cet;
ob_end_flush();
?>
