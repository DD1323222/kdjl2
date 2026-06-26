<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.05.29
*@Usage:Fightting Display
*@Note: none
Mem style.
*/
require_once('../config/config.game.php');
$res = $_pm['mysql'] -> getOneRecord(" SELECT sums FROM userbag WHERE pid = 4045 AND uid = ".$_SESSION['id']." AND sums > 0 ");
if(!is_array($res))
{
	die("扫雷卡数量不足,请前往神秘商店购买!");
}
$_pm['mysql'] -> query(" UPDATE userbag SET sums = sums-1 WHERE  pid = 4045 AND uid = ".$_SESSION['id']." AND sums > 0");
$_pm['mysql'] -> query(" DELETE FROM userbag WHERE pid = 4045 AND uid = ".$_SESSION['id']." AND sums <= 0 AND bsum <=0 AND psum <=0");
$today_sl_ticket_use = unserialize($_pm['mem']->get('today_is_use_ticket'));
if(!is_array($today_sl_ticket_use))
{
	$today_sl_ticket_use = array($_SESSION['id']);
}
else
{
	$today_sl_ticket_use[] = $_SESSION['id'];
}
$_pm['mem']->set(array('k' => 'today_is_use_ticket', 'v' => $today_sl_ticket_use));
die("ok");

?>
