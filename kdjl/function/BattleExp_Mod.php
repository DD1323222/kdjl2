<?php
/**
*@Usage: 奖励经验兑换UI显示脚本。
*@Author: GeFei Su.
*@Write Date:2008-08-27
*@Copyright:www.webgame.com.cn
*/
require_once('../config/config.game.php');
secStart($_pm['mem']);

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');
$cUser = $_pm['mysql']->getOneRecord("SELECT jgvalue
										FROM battlefield_user
									   WHERE uid={$uid}
									ORDER BY id LIMIT 1");
$userJg = is_array($cUser) && isset($cUser['jgvalue']) ? intval($cUser['jgvalue']) : 0;

//###########################
// @Load template.
//###########################
$tn = $_game['template'] . 'tpl_battle_exp.html';
$cet = '';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);

	$src = array('#userjg#',
				 '#usertop#',
	             '#desclist#'
				);
	$des = array($userJg,
	             '',
				 ''
				);
	$cet = str_replace($src, $des, $tpl);
}
// gzip echo. if maybe.
ob_start('ob_gzip');
echo $cet;
ob_end_flush();
?>
