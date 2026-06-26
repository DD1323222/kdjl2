<?php
require_once('../config/config.game.php');
//$_SESSION['fight'.$_SESSION['id']] = NULL;
if($_REQUEST['from'] != 1)
{
	secStart($_pm['mem']);
	$_SESSION['GoToCity'] = time();//用于抓进城补血，继续打怪外挂！
}


del_bag_expire();
$user	 = $_pm['user']->getUserById($_SESSION['id']);//用户信息

/**战斗宝宝自动回满血*/
$_pm['mysql']->query("UPDATE userbb,player
						 SET hp=srchp,mp = srcmp,addmp = 0,addhp = 0
					   WHERE fightbb=userbb.id and player.id={$_SESSION['id']}
					");

//###########################
// @Load template.
//###########################
$need_tishi = true;
$tishi = '';
$today_sl = unserialize($_pm['mem']->get('today_sl_user'));
if(is_array($today_sl))
{
	foreach($today_sl as $info)
	{
		if($info == $_SESSION['id'])
		{
			$need_tishi = false;
		}
	}
}
if($need_tishi)
{
	$tishi = '<div onclick="distorydiv(this)" style="z-index:200;width:140px;height:120px;position:absolute;left:500px;top:200px;cursor:pointer;opacity:0;filter:alpha(opacity=0);background:#000000"></div>';
}
if($_GET['op'] == 2){

	$flash = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,19,0" width="806" height="328">
			  <param name="movie" value="../new_images/ui/map_city_b.swf">
			  <param name="quality" value="high">
			  <param name="wmode" value="transparent">
			  <param name="allowScriptAccess" value="always" />
			  <embed src="../new_images/ui/map_city_b.swf" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" width="806" allowScriptAccess="always"  height="328" wmode="transparent"></embed>
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

$word = '欢迎<font color=green>'.$_SESSION['nickname'].'</font>来到口袋精灵世界！ <font color=green>新手可以到公告牌接受任务,记得到牧场先设置宝宝为主战宝宝,否则可能无法获取奖励噢！</font>';
$_game['template'] = '../template/';
$tn = $_game['template'] . 'tpl_city.html';
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
