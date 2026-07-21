<?php
ini_set('display_errors', false);
error_reporting(0);
session_start();
$key = getenv('KDJL_SWEEPER_ANNOUNCEMENT_SECRET');
if($key === false || strlen($key) < 32) die();
$time = (isset($_GET['time']) && !is_array($_GET['time'])) ? intval($_GET['time']) : 0;
$text = (isset($_GET['text']) && !is_array($_GET['text'])) ? $_GET['text'] : '';
$sign = (isset($_GET['sign']) && !is_array($_GET['sign'])) ? $_GET['sign'] : '';
if($time < 1 || abs(time()-$time) > 30 || $text === '' || strlen($text) > 2000)
{
	die();
}
if(!is_string($sign) || !preg_match('/^[a-f0-9]{32}$/D', $sign) || md5($text.$time.$key) !== $sign)
{
	die();
}
require_once('../kernel/socketmsg.v1.php');
require_once('../socketChat/config.chat.php');
$s=new socketmsg();
$word = 'an|'.$text;
if(function_exists('iconv'))
{
	$convertedWord = @iconv('gbk','utf-8//IGNORE',$word);
	if($convertedWord !== false) $word = $convertedWord;
}
$s->sendMsg($word);
?>
