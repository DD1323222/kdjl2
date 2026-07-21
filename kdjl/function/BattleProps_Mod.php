<?php
/**
*@Usage: 奖励经验兑换UI显示脚本(兑换物品)。
*@Author: GeFei Su.
*@Write Date:2008-08-27
*@Copyright:www.webgame.com.cn
*/
require_once('../config/config.game.php');
secStart($_pm['mem']);

function battlePropsHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');
$cUser = $_pm['mysql']->getOneRecord("SELECT jgvalue
										FROM battlefield_user
									   WHERE uid={$uid}
									ORDER BY id LIMIT 1");
$userJg = is_array($cUser) && isset($cUser['jgvalue']) ? intval($cUser['jgvalue']) : 0;

$wp = $_pm['mysql']->getRecords("SELECT p.id as id,name,need,b.pid as pid
                                   FROM props as p,battlefield_props as b
								  WHERE p.id=b.pid
								");
$plist = '';
if (is_array($wp))
{
	foreach ($wp as $k => $rs)
	{
		if(!is_array($rs)) continue;
		$itemId = isset($rs['id']) ? intval($rs['id']) : 0;
		$pid = isset($rs['pid']) ? intval($rs['pid']) : 0;
		$name = battlePropsHtml(isset($rs['name']) ? $rs['name'] : '');
		$need = isset($rs['need']) ? intval($rs['need']) : 0;
		if($itemId < 1 || $pid < 1) continue;
		$plist .= '<tr><td width=250 id="t'.$itemId.'"  style="cursor:pointer;" onmouseover="showTip('.$pid.');this.style.border=\'solid 0px #DFD496\';"  onmouseout="window.parent.UnTip();this.style.border=0;" onclick="sel(this);pid='.$pid.';">'.$name.'</td><td>'.$need.'</td></tr>';
	}
}

//###########################
// @Load template.
//###########################
$tn = $_game['template'] . 'tpl_battle_props.html';
$cet = '';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);

	$src = array('#userjg#',
				 '#usertop#',
	             '#desclist#',
				 '#plist#'
				);
	$des = array($userJg,
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
