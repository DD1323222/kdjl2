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
$interface_top = "http://pmmg1.webgame.com.cn/interface/kffight_status.php";
$res_status_self = curl_get($interface_top."?status=4&nickname=".urlencode($_SESSION['nickname'])."&host=".$_SERVER['HTTP_HOST']);	//自身信息
//宠物分值
require_once('../sec/dblock_fun.php');
$a = getLock($_SESSION['id']);
if(!is_array($a))
{
	realseLock();
	unLockItem($id);
	die('服务器繁忙，请稍候再试！');
}
if($res_status_self == 'nostart')
{
	die("战场未开启");
}
$props_res = $_pm['mysql'] -> getOneRecord("SELECT * FROM userbag WHERE pid = 4198 AND uid = '".$_SESSION['id']."' AND sums > 0 ");
if(!is_array($props_res))
{
	die("物品数量不足,请前往神秘商店购买");
}
$_pm['mysql']->query("UPDATE userbag SET sums=sums-1 WHERE id={$props_res['id']} and uid={$_SESSION['id']} and sums>0 ");
$_pm['mysql']->query("DELETE FROM userbag WHERE uid={$_SESSION['id']} and sums<=0 AND psum <=0 AND bsum <=0 AND pid = 4198");
$res_status_self = curl_get($interface_top."?status=5&nickname=".urlencode($_SESSION['nickname'])."&host=".$_SERVER['HTTP_HOST']);	//自身信息
echo $res_status_self;
realseLock();
die();
?>