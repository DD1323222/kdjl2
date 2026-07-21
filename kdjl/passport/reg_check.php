<?php
/**
* user name. Check.
*/
require_once("../config/config.game.php");
//secStart($_pm['mem']);
header('Content-Type:text/html;charset=utf-8');
@session_start();
if(!isset($_REQUEST['username']) || !isset($_REQUEST['nickname']) || is_array($_REQUEST['username']) || is_array($_REQUEST['nickname']))
{
	die("0");
}
$usernameRaw = trim($_REQUEST['username']);
$nicknameRaw = trim($_REQUEST['nickname']);
if(!kdjlValidAccountName($usernameRaw))
{
	die("0");
}
if(!kdjlValidNickname($nicknameRaw)) die('3');
$db = new mysql();
$username = $db->escape($usernameRaw);
$nickname = $db->escape($nicknameRaw);
$rs = $db->getOneRecord("SELECT id FROM player WHERE name = '{$username}' LIMIT 1");
if(is_array($rs))
{
	die("1");
}
$rs = $db->getOneRecord("SELECT id FROM player WHERE nickname = '{$nickname}' LIMIT 1");
if(is_array($rs))
{
	die("2");
}
else
{
	die("OK");
}
?>
