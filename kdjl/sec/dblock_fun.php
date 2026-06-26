<?php
require_once(dirname(__FILE__).'/../config/config.game.php');
function getLock($uid){
	global $_pm;
	$_pm['mysql']->query("INSERT INTO `lock` VALUES($uid,0)");
	$_pm['mysql'] -> query("BEGIN");
	$rs = $_pm['mysql'] -> getOneRecord("SELECT uid FROM `lock` WHERE uid=$uid FOR UPDATE");
	return $rs;
}
function realseLock()
{
	global $_pm;
	$_pm['mysql'] -> query("COMMIT");
}
?>