<?php
ini_set('display_errors',true);
error_reporting(E_ALL);
session_start();
$key = 'sl_gonggao';
$time = isset($_GET['time']) ? intval($_GET['time']) : 0;
$text = isset($_GET['text']) ? $_GET['text'] : '';
$sign = isset($_GET['sign']) ? $_GET['sign'] : '';
if(time()-$time > 30)
{
	die();
}
if(md5($text.$time.$key) != $sign)
{
	die();
}
require_once('../kernel/socketmsg.v1.php');
require_once('../socketChat/config.chat.php');
$s=new socketmsg();
$word = 'an|'.$text;
$word = iconv('gbk','utf-8',$word);
$s->sendMsg($word);
?>
