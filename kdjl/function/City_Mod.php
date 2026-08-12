<?php
require_once('../config/config.game.php');
require_once(dirname(__FILE__).'/saolei_common.php');
require_once(dirname(__FILE__).'/fight_wait_common.php');
//$_SESSION['fight'.$_SESSION['id']] = NULL;
$from = (isset($_REQUEST['from']) && !is_array($_REQUEST['from'])) ? intval($_REQUEST['from']) : 0;
if($from != 1)
{
	secStart($_pm['mem']);
	$_SESSION['GoToCity'] = time();//用于抓进城补血，继续打怪外挂！
}


del_bag_expire();
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');
kdjlFightBeginNavigationWait($uid);
$user	 = $_pm['user']->getUserById($_SESSION['id']);//用户信息
if(!is_array($user)) die('');

/**战斗宝宝自动回满血和魔法（包含当前装备加成）*/
$healResult = kdjlFightRestorePlayerPet($uid, 0);
if($healResult === false) die('主战宠物恢复失败！');

//###########################
// @Load template.
//###########################
$need_tishi = !slTodayUserHas($_pm['mem'], $uid);
$tishi = '';
if($need_tishi)
{
	$tishi = '<div onclick="distorydiv(this)" style="z-index:200;width:140px;height:120px;position:absolute;left:500px;top:200px;cursor:pointer;opacity:0;filter:alpha(opacity=0);background:#000000"></div>';
}
$op = (isset($_GET['op']) && !is_array($_GET['op'])) ? intval($_GET['op']) : 0;
if($op == 2){

	$flash = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,19,0" width="806" height="328">
			  <param name="movie" value="../new_images/ui/map_city_c.swf">
			  <param name="quality" value="high">
			  <param name="wmode" value="transparent">
			  <param name="allowScriptAccess" value="always" />
			  <embed src="../new_images/ui/map_city_c.swf" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" width="806" allowScriptAccess="always"  height="328" wmode="transparent"></embed>
           </object>';
}else{
	$flash = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,19,0" width="806" height="328">
			  <param name="movie" value="../new_images/ui/map_city_c.swf">
			  <param name="quality" value="high">
			  <param name="wmode" value="transparent">
			  <param name="allowScriptAccess" value="always" />
			  <embed src="../new_images/ui/map_city_c.swf" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" width="806" allowScriptAccess="always"  height="328" wmode="transparent"></embed>
           </object>';
	/*$flash = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,19,0" width="788" height="311">
  <param name="movie" value="../new_images/ui/map_city_a.swf" />
  <param name="quality" value="high" />
  <param name="wmode" value="transparent"/>
  <param name="allowScriptAccess" value="always" />
  <embed src="../new_images/ui/map_city_a.swf" allowScriptAccess="always" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" width="788" height="311"></embed>
</object>';*/
}

$nickname = isset($_SESSION['nickname']) && !is_array($_SESSION['nickname']) ? htmlspecialchars((string)$_SESSION['nickname'], ENT_QUOTES, 'UTF-8') : '';
$word = '欢迎<font color=green>'.$nickname.'</font>来到口袋精灵世界！ <font color=green>新手可以到公告牌接受任务,记得到牧场先设置宝宝为主战宝宝,否则可能无法获取奖励噢！</font>';
$_game['template'] = '../template/';
$tn = $_game['template'] . 'tpl_city.html';
$img = '';
$cet = '';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);

	$src = array('#welcomeword#',
				 '#img#',
				 '#flash#',
				 '#tishi#'
				);
	$des = array($word,
				 $img,
				 $flash,
				 $tishi
				);
	$cet = str_replace($src, $des, $tpl);
}
// gzip echo. if maybe.
ob_start('ob_gzip');
echo $cet;
ob_end_flush();
?>
