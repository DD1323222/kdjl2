<?php
header('Content-Type:text/html;charset=gbk');
/*require_once('kernel/socketmsg.v1.php');
require_once('socketChat/config.chat.php');
$s=new socketmsg();
echo $s->sendMsg(iconv('gbk','utf-8','SYSN|information-->'),array(765));*/
//echo $s->sendMsg(iconv('gbk','utf-8','SYS|SYS information-->'),array(765));
//echo $s->sendMsg(iconv('gbk','utf-8','an|an information-->'),array(765));
require_once('../config/config.game.php');
/*$user = $_pm['user']->getUserById($_SESSION['id']);
echo catchTask($user,$_GET['id']);*/
$task = new task();
$task->saveGword("在琥珀屋刷到难度 4 星的挑战。<span style='cursor:pointer' color='#f44ebf' onclick='ggshow(\"玩家当前主宠：睡美人雅雅<br />攻击：10460&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;防御：7792<br />命中：13360&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;生命：37920<br />成长：32.9<br />当前怪物信息：<br />赤锦&nbsp;紫&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;海星&nbsp;紫<br />冷面鱼人&nbsp;红&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\");'><a href='javascript:void(0)'>【详细信息】</a></span>");
?>
