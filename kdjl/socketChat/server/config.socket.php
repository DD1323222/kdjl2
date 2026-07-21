<?php
if(!isset($argv) || count($argv)<6) die("Arguments error!");

$server_ip = getServerIp();

$socket_port = intval($argv[3]);
if($socket_port < 1 || $socket_port > 65535) die("Socket port error!");
$_mem['host'] = $argv[1];
$_mem['port'] = intval($argv[2]);
if($_mem['port'] < 1 || $_mem['port'] > 65535) die("Memcache port error!");
$_mysql['host'] = $argv[4];
$_mysql['db'] = $argv[5];

$_mysql['user']= 'kdjl';
$_mysql['pass']= 'kdjl';

define('debugLevel', isset($argv[7])?$argv[7]:0);
$socketSecret = getenv('KDJL_SOCKET_SECRET');
if(!is_string($socketSecret) || strlen($socketSecret) < 16) $socketSecret = 'kdjl2-internal-7f2c9a54b13e6d80';
define('PWD',$socketSecret);
$pwd = md5(date("Ymd").PWD);

function getServerIp(){
	$outputTop = array();
	$return_var = 1;
	exec('grep "IPADDR" /etc/sysconfig/network-scripts/ifcfg-eth0' , $outputTop , $return_var);
	if($return_var===0 && isset($outputTop[0])){
		$ip = explode("=",$outputTop[0],2);
		if(isset($ip[1]) && filter_var(trim($ip[1]),FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)) return trim($ip[1]);
	}
	return '';
}
?>
