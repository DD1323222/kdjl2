<?php
/**
* user name. Check.
*/
require_once("../config/config.game.php");
//secStart($_pm['mem']);
header('Content-Type:text/html;charset=utf-8');
@session_start();
if(!isset($_REQUEST['username']) || !isset($_REQUEST['nickname']))
{
	die("0");
}
preg_match("/[^A-Za-z0-9]/", $_REQUEST['username'], $matches);
if(count($matches) > 0)
{
	die("0");
}
if($_REQUEST['username'])
{
	$_REQUEST['username'] =$_REQUEST['username'];
}
if($_REQUEST['nickname'])
{
	$_REQUEST['nickname'] = $_REQUEST['nickname'];
}
$db = new mysql();
$username = mysql_real_escape_string($_REQUEST['username']);
$nickname = mysql_real_escape_string($_REQUEST['nickname']);
if (strlen(trim($nickname))<4 || strlen(trim($nickname))>14){ $err="3";echo $err;exit();}
$rs = $db->getOneRecord("SELECT * FROM player WHERE name = '{$username}'");
if($rs)
{
	die("1");
}
$rs = $db->getOneRecord("SELECT * FROM player WHERE nickname = '{$nickname}'");
if($rs)
{
	die("2");
}
else
{
	die("OK");
}
?>
