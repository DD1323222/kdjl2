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
echo "<pre>";
//$_pm['mem']->del('today_sl_user');
//$_pm['mem']->del('today_is_use_ticket');
$a = unserialize($_pm['mem']->get('today_sl_user'));
$b = unserialize($_pm['mem']->get('today_is_use_ticket'));
print_r($a);
print_r($b);
echo "<br>";
$c = unserialize($_pm['mem']->get('sl_die_option'));
print_r($c);
$d = unserialize($_pm['mem']->get('sl_prize_info'));
echo "<br>prize<br>";
print_r($d);
echo "</pre>";

?>
