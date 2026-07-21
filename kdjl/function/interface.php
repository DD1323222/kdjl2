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
@session_start();
require_once('../config/config.game.php');
require_once(dirname(__FILE__).'/saolei_common.php');
if(!kdjlCurrentUserIsAdmin())
{
	die();
}
echo "<pre>";
//$_pm['mem']->del('today_sl_user');
//$_pm['mem']->del('today_is_use_ticket');
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
$a = slTodayUserHas($_pm['mem'], $uid);
$b = slTodayTicketHas($_pm['mem'], $uid);
print_r($a);
print_r($b);
echo "<br>";
$c = slDieOptionFind($_pm['mem'], $uid);
print_r($c);
$d = slPrizeGetUserPool($_pm['mem'], $uid);
echo "<br>prize<br>";
print_r($d);
echo "</pre>";

?>
