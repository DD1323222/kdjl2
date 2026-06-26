<?php
if(count($argv)<4) die("Arguments error!");

$server_ip = getServerIp();

$socket_port = $argv[3];
$_mem['host'] = $argv[1];
$_mem['port'] = $argv[2];
$_mysql['host'] = $argv[4];
$_mysql['db'] = $argv[5];

$_mysql['user']= 'kdjl';
$_mysql['pass']= 'kdjl';

define('debugLevel', isset($argv[7])?$argv[7]:0);
define('PWD',"123456");
$pwd = md5(date("Ymd").PWD);

function getServerIp(){
	exec('grep "IPADDR" /etc/sysconfig/network-scripts/ifcfg-eth0' , $outputTop , $return_var);
	$ip = explode("=",$outputTop[0]);
    if($return_var===0){
		return $ip[1];
	}
	return '';
}
?>