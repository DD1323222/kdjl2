<?php
ini_set('display_errors',true);
error_reporting(E_ALL);
session_start();
$key = 'sl_gonggao';
if(time()-$_GET['time'] > 30)
{
	die();
}
if(!md5($_GET['text'].$_GET['time'].$key) == $_GET['sign'])
{
	die();
}
require_once('../kernel/socketmsg.v1.php');
require_once('../socketChat/config.chat.php');
$s=new socketmsg();
$word = 'an|'.$_GET['text'];
$word = iconv('gbk','utf-8',$word);
$s->sendMsg($word);
?>