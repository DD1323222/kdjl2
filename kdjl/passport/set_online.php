<?php
header('HTTP/1.1 404 Not Found');
exit;
/**
* online set.
*/
require_once("../config/config.game.php");
header('Content-Type:text/html;charset=utf-8');
$db = new mysql();
$db->query("update  chat_login_auth set  is_online=0");
PRintf  ("Updated records:  %d\n", mysql_affected_rows($db->getConn()));
?>
