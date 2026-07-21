<?php
/*
 * 此文件自20081217只用来清理memcache，已经修改为直接修改其它服务器的memcache
 */


@session_start();

require_once('../config/config.game.php');

if(!kdjlCurrentUserIsAdmin())
{
	die();
}

//if($_SESSION['username']=="leinchu"){
	$wgUser = kdjlSafeMemValue($_pm['mem']->get("wgUser"), array());
	$wgUserList = (is_array($wgUser) && isset($wgUser['wgList']) && is_array($wgUser['wgList'])) ? $wgUser['wgList'] : array();
if(!empty($wgUserList))
	foreach($wgUserList as $rs){
		if(isset($rs['visitorder']))
		{
			echo htmlspecialchars($rs['visitorder'][0], ENT_QUOTES, 'UTF-8').'->'.htmlspecialchars($rs['visitorder'][2], ENT_QUOTES, 'UTF-8')."<br/>";
		}
		if(isset($rs['mustvisit']))
		{
			echo '<u>'.htmlspecialchars($rs['mustvisit'][0], ENT_QUOTES, 'UTF-8')."</u><br/>";
		}
	}
//}

?>
