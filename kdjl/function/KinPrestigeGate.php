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
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid <= 0)
{
	die('1');
}
$user = $_pm['user']->getUserById($uid);
$num = (isset($_REQUEST['num']) && !is_array($_REQUEST['num'])) ? intval($_REQUEST['num']) : 0;
if(!is_array($user)) die('1');
if(!isset($user['prestige'])) $user['prestige'] = 0;
if(lockItem($uid) === false)
{
	die('已经在处理了！');
}

$err = 0;
if($num <= 0)
{
	unLockItem($uid);
	die("1");//请先填写您要缴纳的威望！
}
if($user['prestige'] < $num)
{
	unLockItem($uid);
	die("2");//您当前的威望不够
}
$sql = "UPDATE player SET prestige = prestige - $num,jprestige = COALESCE(jprestige,0) + $num WHERE id = {$uid} and prestige >= $num";
if(!$_pm['mysql'] -> query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
{
	unLockItem($uid);
	die("2");
}
if(defined('MEM_USER_KEY')) $_pm['mem']->del(MEM_USER_KEY);
$_pm['mem']->del('pupublictop');
$err = 10;
echo $err;
unLockItem($uid);
$_pm['mem']->memClose();
?>
