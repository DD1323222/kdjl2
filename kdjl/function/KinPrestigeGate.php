<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: 谭炜

*@Write Date: 2008.11.24
*@Update Date: 
*@Usage: 威望系统。
*@Memo: 威望系统。

*/
require_once('../config/config.game.php');
secStart($_pm['mem']);
$user = $_pm['user']->getUserById($_SESSION['id']);
$num = intval($_REQUEST['num']);
if(lockItem($_SESSION['id']) === false)
{
	die('已经在处理了！');
}

$err = 0;
if($num <= 0)
{
	unLockItem($_SESSION['id']);
	die("1");//请先填写您要缴纳的威望！
}
if($user['prestige'] < $num)
{
	unLockItem($_SESSION['id']);
	die("2");//您当前的威望不够
}
$sql = "UPDATE player SET prestige = prestige - $num,jprestige = jprestige + $num WHERE id = {$_SESSION['id']} and prestige >= $num";
$_pm['mysql'] -> query($sql);
$err = 10;
echo $err;
$_pm['mem']->memClose();
unLockItem($_SESSION['id']);
?>