<?php
header("content-type:text/html;charset=utf-8");
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
require_once('../config/config.game.php');
require_once('config.chat.php');
session_start();

if(!isset($_SESSION['id'])||!isset($_SESSION['nickname']))
{
	//echo "<script language='javascript'>top.location='/';<\/script>";
	die('请先登录');
}

if(!isset($_SESSION['nicknamegb']))
{
	$_SESSION['nicknamegb'] = $_SESSION['nickname'];
}
if($_SESSION['team_id']){
	$team_id= $_SESSION['team_id'];
}else{
	$team_id= 0;
}
if($_SESSION['lock_time']>0){ //禁言
	$lock_time= $_SESSION['lock_time'];
}else{
	$rs = $_pm['mysql']->getOneRecord("SELECT id,name,password,secret FROM player WHERE id = '".$_SESSION['id']);
	if($rs['password']){
		$lock_time = $rs['password'];
	}else{
		$lock_time = 0;
	}
}
//检测是不是管理员登陆
$rs = $_pm['mysql']->getOneRecord("select contents from welcome where code='admin'");
if($rs['contents']){
	$tempArr = explode(",",$rs['contents']);
	if(in_array($_SESSION['username'],$tempArr)){
		$admin = 1;
	}else{
		$admin = 0;
	}
}else{
	$admin = 0;
}
//vip处理
if($_SESSION['vip']==false){
	$vip = 0;
}else if($_SESSION['vip']>0){
	$vip = intval($_SESSION['vip']);
}else{
	$vip = 0;
}

$mac_addr = $_SESSION['mac'];
$sql = "delete from chat_login_auth where uid='{$_SESSION['id']}'";
$_pm['mysql']->query($sql);

$sid = session_id();
$uIP = get_real_ip();
$sql = "insert into chat_login_auth(uid,username,nickname,sid,guild_id,team_id,lock_time,admin,vip,u_ip,is_online,mac_addr)values('{$_SESSION['id']}','{$_SESSION['username']}','{$_SESSION['nickname']}','{$sid}','{$_SESSION['guild_id']}','{$team_id}','{$lock_time}','{$admin}','{$vip}','{$uIP}','1','{$mac_addr}')";

$_pm['mysql']->query($sql);


function get_real_ip(){
	$ip=false;

	if(!empty($_SERVER["HTTP_CLIENT_IP"])){
		$ip = $_SERVER["HTTP_CLIENT_IP"];
	}
	
	if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
		if ($ip) { 
			array_unshift($ips, $ip); $ip = FALSE; 
		}
		for ($i = 0; $i < count($ips); $i++) {
			if (!eregi ("^(10|172\.16|192\.168)\.", $ips[$i])) {
				$ip = $ips[$i];
				break;
			}
		}
	}
	return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
}
?>