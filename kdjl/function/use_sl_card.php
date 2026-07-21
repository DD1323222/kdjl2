<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.05.29
*@Usage:Fightting Display
*@Note: none
Mem style.
*/
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');
require_once(dirname(__FILE__).'/saolei_common.php');

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('服务器繁忙，请稍候再试！');
$a = getLock($uid);
if(!is_array($a))
{
	realseLock();
	die("服务器繁忙，请稍候再试！");
}
$slCardTransactionActive = false;
$slCardStateChanged = false;
$oldTicketState = false;
function slCardShutdown()
{
	global $_pm,$uid,$slCardTransactionActive,$slCardStateChanged,$oldTicketState;
	if($slCardTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		if($slCardStateChanged) slTodayTicketSet($_pm['mem'], $uid, $oldTicketState);
		$slCardTransactionActive = false;
		$slCardStateChanged = false;
	}
	if(function_exists('realseLock')) realseLock();
}
register_shutdown_function('slCardShutdown');
if(slTodayTicketHas($_pm['mem'], $uid))
{
	realseLock();
	die('ok');
}
$slCardTransactionActive = true;
$res = $_pm['mysql'] -> getOneRecord("SELECT id,sums
                                        FROM userbag
                                       WHERE pid=4045
                                         AND uid=".$uid."
                                         AND sums>0
                                         AND zbing=0
                                         AND (cantrade IS NULL OR cantrade<>3)
                                    ORDER BY id LIMIT 1 FOR UPDATE");
if(!is_array($res) || intval($res['sums']) < 1)
{
	$_pm['mysql']->query('ROLLBACK');
	realseLock();
	die("扫雷卡数量不足,请前往神秘商店购买!");
}
$bagid = intval($res['id']);
$_pm['mysql'] -> query("UPDATE userbag
                           SET sums=sums-1
                         WHERE id=".$bagid."
                           AND pid=4045
                           AND uid=".$uid."
                           AND sums>0
                           AND zbing=0
                           AND (cantrade IS NULL OR cantrade<>3)");
if(mysql_affected_rows($_pm['mysql']->getConn()) != 1)
{
	$_pm['mysql']->query('ROLLBACK');
	realseLock();
	die("扫雷卡数量不足,请前往神秘商店购买!");
}
if(!$_pm['mysql'] -> query("DELETE FROM userbag WHERE id=".$bagid." AND pid=4045 AND uid=".$uid." AND sums<=0 AND bsum<=0 AND psum<=0 AND pyb=0 AND zbing=0 AND (cantrade IS NULL OR cantrade<>3)"))
{
	$_pm['mysql']->query('ROLLBACK');
	realseLock();
	die("服务器繁忙，请稍候再试！");
}
$oldTicketState = slTodayTicketHas($_pm['mem'], $uid);
$slCardStateChanged = true;
if(!slTodayTicketSet($_pm['mem'], $uid, true))
{
	$_pm['mysql']->query('ROLLBACK');
	realseLock();
	die("服务器繁忙，请稍候再试！");
}
if(!$_pm['mysql']->query('COMMIT'))
{
	$_pm['mysql']->query('ROLLBACK');
	slTodayTicketSet($_pm['mem'], $uid, $oldTicketState);
	realseLock();
	die("服务器繁忙，请稍候再试！");
}
$slCardTransactionActive = false;
$slCardStateChanged = false;
$_pm['mem']->del(MEM_USERBAG_KEY);
realseLock();
die("ok");

?>
