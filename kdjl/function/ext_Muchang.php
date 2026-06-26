<?php
/**
 * @uses encrypt the field
 * 
 * @author Zheng.Ping
 * @date 2009-02-26
 */
require_once('../config/config.game.php');
secStart($_pm['mem']);

$user    = $_pm['user']->getUserById($_SESSION['id']);
$action = $_REQUEST['action'];
//给仓库加密
if($action == "reg")
{
    $pwd = htmlspecialchars(mysql_escape_string($_REQUEST['pwd']));
    $repwd = htmlspecialchars(mysql_escape_string($_REQUEST['repwd']));
    $err = "";
    if(!empty($user['fieldpwd']) && empty($_SESSION['loginField' . $_SESSION['id']]))
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
	if($_SESSION['loginField' . $_SESSION['id']] != 1)
	{
		$r = $_pm['mysql']->getOneRecord("SELECT fieldpwd FROM player WHERE id = {$_SESSION['id']}");
		if(isset($r['fieldpwd']) && !empty($r['fieldpwd']))
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
            SET fieldpwd = $pwd
            WHERE id = {$_SESSION['id']}";
    $_pm['mysql'] -> query($sql);
    $err = 10;
    echo $err . $sql;
}
//登陆
else if($action == "login")
{
    $err ="";
    if(empty($user['fieldpwd']))
    {
        die("2");//您还没有设置仓库密码！
    }
    $pwd = htmlspecialchars(mysql_escape_string($_REQUEST['pwd']));
    if(empty($pwd))
    {
        die("0");//请输入密码！
    }
    $pwd = abs(crc32(md5($pwd)));
    if($pwd != $user['fieldpwd'])
    {
        die("1");//密码错误！
    }
    else
    {
        $err = 10;
        $_SESSION['loginField' . $_SESSION['id']] = "1";//已经登陆
    }
    echo $err;
} else if($action == "reset") { // reset password, added by Zheng.Ping
    $err   = "";
    if(empty($user['fieldpwd']))
    {
        die("1"); //您还没有设置仓库密码！
    }
    $pwd = htmlspecialchars(mysql_escape_string($_REQUEST['pwd']));
    $repwd = htmlspecialchars(mysql_escape_string($_REQUEST['repwd']));
    if(empty($pwd))
    {
        die("0");//请输入密码！
    }
    if(empty($repwd))
    {
        die("0");//请输入密码！
    }
    $pwd = abs(crc32(md5($pwd)));
    if($pwd != $user['fieldpwd'])
    {
        die("1");//原密码错误！
    }
    else
    {
        $repwd = abs(crc32(md5($repwd)));
        $err = 10;
        $sql = "UPDATE player 
                SET fieldpwd = $repwd
                WHERE id = {$_SESSION['id']}";
        $_pm['mysql'] -> query($sql);
        $_SESSION['loginField' . $_SESSION['id']] = "1"; //已经登陆
    }
    echo $err;
}

$_pm['mem']->memClose();
?>
