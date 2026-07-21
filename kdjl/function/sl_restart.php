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
require_once('../sec/dblock_fun.php');
require_once('../config/config.game.php');
require_once(dirname(__FILE__).'/saolei_common.php');
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1)
{
	die('登录状态无效！');
}
$a = getLock($uid);
if(!is_array($a)){
	realseLock();
	die('服务器繁忙，请稍候再试！');
}
$slRestartTransactionActive = false;
$slRestartPoolChanged = false;
$prize_info_best_old = false;
$prizeCacheKey = '';
function slRestartRestorePool()
{
	global $_pm,$uid,$slRestartPoolChanged,$prize_info_best_old,$prizeCacheKey;
	if(!$slRestartPoolChanged) return;
	if(is_array($prize_info_best_old)) slPrizeStoreUserPool($_pm['mem'], $uid, $prize_info_best_old);
	else if($prizeCacheKey !== '') $_pm['mem']->del($prizeCacheKey);
	$slRestartPoolChanged = false;
}
function slRestartShutdown()
{
	global $_pm,$slRestartTransactionActive;
	if($slRestartTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		slRestartRestorePool();
		$slRestartTransactionActive = false;
	}
	if(function_exists('realseLock')) realseLock();
}
register_shutdown_function('slRestartShutdown');
$slRestartTransactionActive = true;
function slRestartHtml($value)
{
	return htmlspecialchars(strval($value), ENT_QUOTES, 'UTF-8');
}
function slRestartImage($value)
{
	$value = basename(strval($value));
	return preg_match('/^[A-Za-z0-9_.-]+$/', $value) ? $value : '';
}
$sql = "SELECT *
          FROM userbag
         WHERE pid = 4019
           AND sums > 0
           AND uid = ".$uid."
           AND zbing = 0
           AND (cantrade IS NULL OR cantrade<>3)
      ORDER BY id LIMIT 1 FOR UPDATE";
$res = $_pm['mysql'] -> getOneRecord($sql);
if(!is_array($res))
{
	$_pm['mysql']->query('ROLLBACK');
	realseLock();
	die("1");
}
$cardUsed = $_pm['mysql'] -> query("UPDATE userbag
                           SET sums=sums-1
                         WHERE id=".intval($res['id'])."
                           AND uid=".$uid."
                           AND pid=4019
                           AND sums>0
                           AND zbing=0
                           AND (cantrade IS NULL OR cantrade<>3)");
if(!$cardUsed || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
{
	$_pm['mysql']->query('ROLLBACK');
	realseLock();
	die("1");
}
if(!$_pm['mysql'] -> query("DELETE FROM userbag WHERE id=".intval($res['id'])." AND uid=".$uid." AND pid=4019 AND sums=0 AND bsum=0 AND psum=0 AND pyb=0 AND zbing=0 AND (cantrade IS NULL OR cantrade<>3)"))
{
	$_pm['mysql']->query('ROLLBACK');
	realseLock();
	die("1");
}
$config = slPrizeLoadBestConfig($_pm['mysql'], $_pm['mem']);
$props = slPrizeLoadProps($_pm['mysql'], $_pm['mem']);
$prize_info_best = slPrizeBuildUserPool($config, $props);
if(!slPrizePoolIsComplete($prize_info_best))
{
	$_pm['mysql']->query('ROLLBACK');
	realseLock();
	die('扫雷奖励配置错误！');
}
$prizeCacheKey = slPrizeCacheKey($uid);
$prize_info_best_old = kdjlSafeMemValue($_pm['mem']->get($prizeCacheKey), false);
$slRestartPoolChanged = true;
if(!slPrizeStoreUserPool($_pm['mem'], $uid, $prize_info_best))
{
	$_pm['mysql']->query('ROLLBACK');
	realseLock();
	die('保存扫雷奖励状态失败！');
}
if(!$_pm['mysql']->query('COMMIT'))
{
	$_pm['mysql']->query('ROLLBACK');
	if(is_array($prize_info_best_old))
	{
		slPrizeStoreUserPool($_pm['mem'], $uid, $prize_info_best_old);
	}
	else
	{
		$_pm['mem']->del($prizeCacheKey);
	}
	realseLock();
	die('保存扫雷数据失败！');
}
$slRestartTransactionActive = false;
$slRestartPoolChanged = false;
$_pm['mem']->del(MEM_USERBAG_KEY);
//每关奖品展示逻辑
$i = 1;
$prize_echo = '<table id="everybox" width="140" ><tr>';
$prize_look_pic = '';
foreach($prize_info_best as $info)
{
	$prizeNameHtml = slRestartHtml(isset($info['name']) ? $info['name'] : '');
	$prizeImgUrl = slPrizeImageUrl($info);
	$prize_look_pic .= '<td width="33%"><font>第'.$i.'关</font><img width="40px" height="40px" title="'.$prizeNameHtml.'" src="'.$prizeImgUrl.'" /></td>';
	if($i%3 == 0 && $i < 9)
	{
		$prize_echo .= $prize_look_pic."</tr><tr>";
		$prize_look_pic = '';
	}
	else
	{
		$prize_echo .= $prize_look_pic;
		$prize_look_pic = '';
	}
	$i++;
}
$prize_echo .= '</tr>
				<tr class="noborder">
					<td class="noborder" colspan="3"><img class="btn" onclick="sl_restart('."'sx'".')" src="../images/img/sl09.gif" /></td>
				</tr></table>';
echo $prize_echo;
realseLock();
?>
