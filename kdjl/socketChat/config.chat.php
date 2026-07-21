<?php 
require(dirname(__FILE__).'/../config/config.mysql.php');
$server_ip = '119.91.114.58';//聊天socket服务器的地址
$socket_port =1988; #socket_port#;
$smile_icon_num = 36;
$socket_file_store_path = '/socketChat/server';
$socketSecret = getenv('KDJL_SOCKET_SECRET');
if(!is_string($socketSecret) || strlen($socketSecret) < 16) $socketSecret = 'kdjl2-internal-7f2c9a54b13e6d80';
define('PWD',$socketSecret);
$pwd = md5(date("Ymd") . PWD);
$usec=false;
?>
