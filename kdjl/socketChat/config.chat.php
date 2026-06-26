<?php 
require(dirname(__FILE__).'/../config/config.mysql.php');
$server_ip = '47.121.183.254';//聊天socket服务器的地址
$socket_port =1988; #socket_port#;
$smile_icon_num = 36;
$socket_file_store_path = '/socketChat/server';
define('PWD',"123456");
$pwd = md5(date("Ymd") . PWD);
$usec=false;
?>
