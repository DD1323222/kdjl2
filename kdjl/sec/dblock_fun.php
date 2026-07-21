<?php
require_once(dirname(__FILE__).'/../config/config.game.php');
function getLock($uid){
	global $_pm;
	$uid = intval($uid);
	if($uid == 0) return false;
	$lockName = 'kdjl_user_'.$uid;
	$lockNameSql = $_pm['mysql']->escape($lockName);
	$namedLock = $_pm['mysql']->getOneRecord("SELECT GET_LOCK('{$lockNameSql}',5) AS locked");
	if(!is_array($namedLock) || intval($namedLock['locked']) != 1)
	{
		return false;
	}
	if(!isset($GLOBALS['kdjl_db_named_locks']) || !is_array($GLOBALS['kdjl_db_named_locks']))
	{
		$GLOBALS['kdjl_db_named_locks'] = array();
	}
	$GLOBALS['kdjl_db_named_locks'][] = $lockName;
	if(!$_pm['mysql']->query("INSERT IGNORE INTO `lock` VALUES($uid,0)"))
	{
		realseLock();
		return false;
	}
	if(!$_pm['mysql'] -> query("BEGIN"))
	{
		realseLock();
		return false;
	}
	$rs = $_pm['mysql'] -> getOneRecord("SELECT uid FROM `lock` WHERE uid=$uid FOR UPDATE");
	if(!is_array($rs))
	{
		realseLock();
		return false;
	}
	return $rs;
}
function getScopedLock($scope,$id,$timeout=5)
{
	global $_pm;
	$scope = strtolower(strval($scope));
	$id = intval($id);
	$timeout = max(0, min(30, intval($timeout)));
	if($id < 1 || !preg_match('/^[a-z][a-z0-9_]{0,20}$/D', $scope)) return false;
	$lockName = 'kdjl_'.$scope.'_'.$id;
	$lockNameSql = $_pm['mysql']->escape($lockName);
	$namedLock = $_pm['mysql']->getOneRecord("SELECT GET_LOCK('{$lockNameSql}',{$timeout}) AS locked");
	if(!is_array($namedLock) || intval($namedLock['locked']) != 1) return false;
	if(!isset($GLOBALS['kdjl_db_named_locks']) || !is_array($GLOBALS['kdjl_db_named_locks']))
	{
		$GLOBALS['kdjl_db_named_locks'] = array();
	}
	$GLOBALS['kdjl_db_named_locks'][] = $lockName;
	return true;
}
function realseLock()
{
	global $_pm;
	$_pm['mysql'] -> query("COMMIT");
	if(isset($GLOBALS['kdjl_db_named_locks']) && is_array($GLOBALS['kdjl_db_named_locks']))
	{
		while(count($GLOBALS['kdjl_db_named_locks']) > 0)
		{
			$lockName = array_pop($GLOBALS['kdjl_db_named_locks']);
			$lockNameSql = $_pm['mysql']->escape($lockName);
			$_pm['mysql']->getOneRecord("SELECT RELEASE_LOCK('{$lockNameSql}') AS released");
		}
	}
}
?>
