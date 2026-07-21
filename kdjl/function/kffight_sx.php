<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %xueyuan%

*@Write Date: 2011.08.31
*@Update Date: /
*@Usage: 跨服战场报名页面
*请求后台公开接口
*/
require_once('../config/config.game.php');
require_once('../login/curl.php');
require_once('../sec/dblock_fun.php');
$kfFightBaseUrl = kdjlConfiguredServiceBaseUrl('KDJL_KF_FIGHT_BASE_URL');
if($kfFightBaseUrl === '') die('跨服战中心未配置');
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1)
{
	die('登录状态无效！');
}
$sessionNickname = isset($_SESSION['nickname']) ? $_SESSION['nickname'] : '';
$httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
if(!preg_match('/^[A-Za-z0-9.-]{1,255}(:[0-9]{1,5})?$/', $httpHost)) $httpHost = 'localhost';
$interface_top = $kfFightBaseUrl.'/kffight_status.php';
$res_status_self = curl_get($interface_top."?status=4&nickname=".urlencode($sessionNickname)."&host=".urlencode($httpHost));	//self info
if($res_status_self === false || $res_status_self === '')
{
	die('战场接口暂时不可用，请稍后再试');
}
$res_status_self = trim($res_status_self);
if(strlen($res_status_self) > 64) die('战场接口返回异常，请稍后再试');
//宠物分值
$a = getLock($uid);
if(!is_array($a))
{
	realseLock();
	die('服务器繁忙，请稍候再试！');
}
if($res_status_self == 'nostart')
{
	realseLock();
	die("战场未开启");
}
$props_res = $_pm['mysql'] -> getOneRecord("SELECT *
                                             FROM userbag
                                            WHERE pid = 4198
                                              AND uid = '".$uid."'
                                              AND sums > 0
                                              AND zbing = 0
                                              AND (cantrade IS NULL OR cantrade<>3)
                                         ORDER BY id LIMIT 1 FOR UPDATE");
if(!is_array($props_res))
{
	$_pm['mysql']->query('ROLLBACK');
	realseLock();
	die("物品数量不足,请前往神秘商店购买");
}
$itemUsed = $_pm['mysql']->query("UPDATE userbag
                         SET sums=sums-1
                       WHERE id=".intval($props_res['id'])."
                         and uid={$uid}
                         and sums>0
                         and zbing=0
                         and (cantrade IS NULL OR cantrade<>3)");
if(!$itemUsed || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
{
	$_pm['mysql']->query('ROLLBACK');
	realseLock();
	die("物品数量不足，请前往神秘商店购买");
}
if(!$_pm['mysql']->query("DELETE FROM userbag WHERE id=".intval($props_res['id'])." and uid={$uid} and sums<=0 AND psum <=0 AND bsum <=0 AND pyb=0 AND pid = 4198 AND zbing=0 AND (cantrade IS NULL OR cantrade<>3)"))
{
	$_pm['mysql']->query('ROLLBACK');
	realseLock();
	die('服务器繁忙，请稍候再试！');
}
$res_status_self = curl_get($interface_top."?status=5&nickname=".urlencode($sessionNickname)."&host=".urlencode($httpHost));	//self info
if($res_status_self === false || $res_status_self === '')
{
	$_pm['mysql']->query('ROLLBACK');
	realseLock();
	die('战场接口暂时不可用，请稍后再试');
}
$res_status_self = trim($res_status_self);
if(!in_array($res_status_self, array('1','2','3','4','5'), true))
{
	$_pm['mysql']->query('ROLLBACK');
	realseLock();
	die('战场接口返回异常，幸运油未扣除');
}
if(!$_pm['mysql']->query('COMMIT'))
{
	$_pm['mysql']->query('ROLLBACK');
	realseLock();
	die('服务器繁忙，请稍候再试！');
}
$_pm['mem']->del(MEM_USERBAG_KEY);
echo $res_status_self;
realseLock();
die();
?>
