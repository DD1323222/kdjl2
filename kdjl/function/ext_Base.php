<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.10.09
*@Update Date: 2008.10.09
*@Usage: 仓库加密
*@Note: none
*/
require_once('../config/config.game.php');
secStart($_pm['mem']);
$user	 = $_pm['user']->getUserById($_SESSION['id']);
$action = $_REQUEST['action'];
//给仓库加密
if($action == "reg")
{
	$pwd = htmlspecialchars(mysql_escape_string($_REQUEST['pwd']));
	$repwd = htmlspecialchars(mysql_escape_string($_REQUEST['repwd']));
	$err = "";
	if(!empty($user['ckpwd']) && empty($_SESSION['login'.$_SESSION['id']]))
	{
		die("3");//您的仓库已加密！如要修改，请先输入旧密码！
	}
	if(empty($pwd))
	{
		die("0");//请先输入密码！
	}
	if(strlen($pwd) <= 3 || strlen($pwd) > 10)
	{
		die("4");//密码长度不一致！
	}
	if(empty($repwd))
	{
		die("1");//请先输入重复密码！
	}
	if($pwd != $repwd)
	{
		die("2");//两次密码不一致！
	}
	$err = "10";
	echo $err;
}
//设置密码，更新到数据库
else if($action == "do")
{
	if($_SESSION['login'.$_SESSION['id']] != 1)
	{
		$r = $_pm['mysql']->getOneRecord("SELECT ckpwd FROM player WHERE id = {$_SESSION['id']}");
		if(isset($r['ckpwd']) && !empty($r['ckpwd']))
		{
			die("请先登录");
		}
	}
	$err = "";
	$pwd = htmlspecialchars(mysql_escape_string($_REQUEST['pwd']));
	$pwd = abs(crc32(md5($pwd)));
	if(empty($pwd))
	{
		die("0");//信息有误！
	}
	$sql = "UPDATE player 
			SET ckpwd = $pwd
			WHERE id = {$_SESSION['id']}";
	$_pm['mysql'] -> query($sql);
	$err = 10;
	echo $err;
}
//登陆
else if($action == "login")
{
	$err ="";
	if(empty($user['ckpwd']))
	{
		die("2");//您还没有设置仓库密码！
	}
	$pwd = htmlspecialchars(mysql_escape_string($_REQUEST['pwd']));
	if(empty($pwd))
	{
		die("0");//请输入密码！
	}
	$pwd = abs(crc32(md5($pwd)));
	if($pwd != $user['ckpwd'])
	{
		die("1");//密码错误！
	}
	else
	{
		$err = 10;
		$_SESSION['login'.$_SESSION['id']] = "1";//已经登陆
	}
	echo $err;
}

$_pm['mem']->memClose();
?>