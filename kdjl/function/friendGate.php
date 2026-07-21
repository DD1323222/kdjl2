<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.05.22
*@Usage: 添加好友。
*@Note: none
*/

header('Content-Type:text/html;charset=utf-8');

require_once('../config/config.game.php');
secStart($_pm['mem']);

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid <= 0)
{
	die('登录状态已失效，请重新登录！');
}
$user	 = $_pm['user']->getUserById($uid);
if(!is_array($user)) die('玩家数据不存在，请重新登录！');
$name = (isset($_REQUEST['name']) && !is_array($_REQUEST['name'])) ? trim($_REQUEST['name']) : '';
$op = (isset($_REQUEST['op']) && !is_array($_REQUEST['op'])) ? $_REQUEST['op'] : '';
$tu	= $_pm['mysql']->escape($name);
if ($tu=='' or empty($tu)) die('请正确输入玩家角色名！');

if ($op == 'add')
{
	$target = $_pm['mysql']->getOneRecord("SELECT id FROM player WHERE nickname='{$tu}'");
	if(!is_array($target) || intval($target['id']) < 1) die('无效的玩家角色名!');
	$targetId = intval($target['id']);
	if(!$_pm['mysql']->query('START TRANSACTION')) die('保存好友数据失败！');
	$ids = array($uid,$targetId);
	sort($ids,SORT_NUMERIC);
	$lockedPlayers = $_pm['mysql']->getRecords('SELECT id,nickname,friendlist FROM player WHERE id IN ('.implode(',',array_unique($ids)).') ORDER BY id FOR UPDATE');
	$self = false;
	$fret = false;
	if(is_array($lockedPlayers)) foreach($lockedPlayers as $lockedPlayer)
	{
		if(intval($lockedPlayer['id']) === $uid) $self = $lockedPlayer;
		if(intval($lockedPlayer['id']) === $targetId) $fret = $lockedPlayer;
	}
	if(!is_array($self) || !is_array($fret) || $fret['nickname'] !== $name) friendGateRollback('无效的玩家角色名!');
	if($targetId === $uid) friendGateRollback('您不能添加您自己！');
	$fname = $fret['nickname'];
	$blackRows = $_pm['mysql']->getRecords("SELECT Id,list FROM blacklist WHERE uid={$uid} ORDER BY Id FOR UPDATE");
	$firstBlacklistId = 0;
	$blackNames = friendGateBlacklistNames($blackRows,$firstBlacklistId);
	if(in_array($fname,$blackNames,true)) friendGateRollback('该玩家在您的黑名单中，不能加入好友！');
	$friends = friendGateNames(isset($self['friendlist']) ? $self['friendlist'] : '');
	if(in_array($fname,$friends,true)) friendGateRollback('该用户已经是好友了！');
	if(count($friends) >= 20) friendGateRollback('您目前只能添加20个好友！');
	$friends[] = $fname;
	$friendlist = implode(',',$friends);
	$friendlistSql = $_pm['mysql']->escape($friendlist);
	if(!$_pm['mysql']->query("UPDATE player SET friendlist='{$friendlistSql}' WHERE id={$uid}") ||
		mysql_affected_rows($_pm['mysql']->getConn()) != 1 || !$_pm['mysql']->query('COMMIT')) friendGateRollback('保存好友数据失败！');
	$_pm['mem']->del($uid);
	liststr($friendlist);
}
else if($op == 'del')
{
	if(!$_pm['mysql']->query('START TRANSACTION')) die('保存好友数据失败！');
	$self = $_pm['mysql']->getOneRecord("SELECT friendlist FROM player WHERE id={$uid} FOR UPDATE");
	if(!is_array($self)) friendGateRollback('玩家数据不存在，请重新登录！');
	$friends = friendGateNames(isset($self['friendlist']) ? $self['friendlist'] : '');
	$friendIndex = array_search($name,$friends,true);
	if($friendIndex === false) friendGateRollback('该用户不是您的好友！');
	unset($friends[$friendIndex]);
	$friendlist = implode(',',array_values($friends));
	$friendlistSql = $_pm['mysql']->escape($friendlist);
	if(!$_pm['mysql']->query("UPDATE player SET friendlist='{$friendlistSql}' WHERE id={$uid}") ||
		mysql_affected_rows($_pm['mysql']->getConn()) != 1 || !$_pm['mysql']->query('COMMIT')) friendGateRollback('保存好友数据失败！');
	$_pm['mem']->del($uid);
	liststr($friendlist);
}
else if($op == 'addblacklist')
{
	$target = $_pm['mysql']->getOneRecord("SELECT id FROM player WHERE nickname='{$tu}'");
	if(!is_array($target) || intval($target['id']) < 1) die('请正确输入您要加黑名单的角色名！');
	$targetId = intval($target['id']);
	if(!$_pm['mysql']->query('START TRANSACTION')) die('保存好友数据失败！');
	$ids = array($uid,$targetId);
	sort($ids,SORT_NUMERIC);
	$lockedPlayers = $_pm['mysql']->getRecords('SELECT id,nickname,friendlist FROM player WHERE id IN ('.implode(',',array_unique($ids)).') ORDER BY id FOR UPDATE');
	$self = false;
	$fret = false;
	if(is_array($lockedPlayers)) foreach($lockedPlayers as $lockedPlayer)
	{
		if(intval($lockedPlayer['id']) === $uid) $self = $lockedPlayer;
		if(intval($lockedPlayer['id']) === $targetId) $fret = $lockedPlayer;
	}
	if(!is_array($self) || !is_array($fret) || $fret['nickname'] !== $name) friendGateRollback('请正确输入您要加黑名单的角色名！');
	if($targetId === $uid) friendGateRollback('您不能添加您自己！');
	$fname = $fret['nickname'];
	if(in_array($fname,friendGateNames(isset($self['friendlist']) ? $self['friendlist'] : ''),true)) friendGateRollback('该玩家是您的好友，您不能加入黑名单！');
	$blackRows = $_pm['mysql']->getRecords("SELECT Id,list FROM blacklist WHERE uid={$uid} ORDER BY Id FOR UPDATE");
	$firstBlacklistId = 0;
	$blackNames = friendGateBlacklistNames($blackRows,$firstBlacklistId);
	if(in_array($fname,$blackNames,true)) friendGateRollback('该用户已经被您加入黑名单了！');
	if(count($blackNames) >= 30) friendGateRollback('您当前只能加30个人入黑名单!');
	$blackNames[] = $fname;
	if(!friendGateSaveBlacklist($uid,$firstBlacklistId,$blackNames) || !$_pm['mysql']->query('COMMIT')) friendGateRollback('保存好友数据失败！');
	friendGateRefreshBlacklistCache();
	liststr1(implode(',',$blackNames));
}
else if($op == 'deleteblacklist')//从黑名单取消
{
	$err = 10;
	if(!$_pm['mysql']->query('START TRANSACTION')) die('保存好友数据失败！');
	$blackRows = $_pm['mysql']->getRecords("SELECT Id,list FROM blacklist WHERE uid={$uid} ORDER BY Id FOR UPDATE");
	$firstBlacklistId = 0;
	$blackNames = friendGateBlacklistNames($blackRows,$firstBlacklistId);
	$blackIndex = array_search($name,$blackNames,true);
	if($blackIndex === false) friendGateRollback('该用户不在您的黑名单中！');
	unset($blackNames[$blackIndex]);
	$blackNames = array_values($blackNames);
	if(!friendGateSaveBlacklist($uid,$firstBlacklistId,$blackNames) || !$_pm['mysql']->query('COMMIT')) friendGateRollback('保存好友数据失败！');
	friendGateRefreshBlacklistCache();
	if(empty($blackNames)){
		echo $err;
	}else
	{
		liststr1(implode(',',$blackNames));
	}
}

function friendGateNames($value)
{
	$result = array();
	foreach(explode(',',(string)$value) as $name)
	{
		$name = trim($name);
		if($name !== '' && !in_array($name,$result,true)) $result[] = $name;
	}
	return $result;
}

function friendGateBlacklistNames($rows,&$firstId)
{
	$firstId = 0;
	$result = array();
	if(!is_array($rows)) return $result;
	foreach($rows as $row)
	{
		if(!is_array($row)) continue;
		$rowId = isset($row['Id']) ? intval($row['Id']) : (isset($row['id']) ? intval($row['id']) : 0);
		if($firstId < 1 && $rowId > 0) $firstId = $rowId;
		foreach(friendGateNames(isset($row['list']) ? $row['list'] : '') as $name)
		{
			if(!in_array($name,$result,true)) $result[] = $name;
		}
	}
	return $result;
}

function friendGateSaveBlacklist($uid,$firstId,$names)
{
	global $_pm;
	$uid = intval($uid);
	$firstId = intval($firstId);
	if($uid < 1 || !is_array($names)) return false;
	if(empty($names)) return $_pm['mysql']->query("DELETE FROM blacklist WHERE uid={$uid}");
	$listSql = $_pm['mysql']->escape(implode(',',$names));
	if($firstId > 0)
	{
		if(!$_pm['mysql']->query("UPDATE blacklist SET list='{$listSql}' WHERE Id={$firstId} AND uid={$uid}")) return false;
		return $_pm['mysql']->query("DELETE FROM blacklist WHERE uid={$uid} AND Id<>{$firstId}");
	}
	return $_pm['mysql']->query("INSERT INTO blacklist(uid,list) VALUES({$uid},'{$listSql}')");
}

function friendGateRefreshBlacklistCache()
{
	global $_pm;
	$rows = $_pm['mysql']->getRecords('SELECT uid,list FROM blacklist ORDER BY Id');
	$cache = array();
	if(is_array($rows)) foreach($rows as $row)
	{
		$cacheUid = isset($row['uid']) ? intval($row['uid']) : 0;
		if($cacheUid < 1) continue;
		$current = isset($cache[$cacheUid]) ? friendGateNames($cache[$cacheUid]) : array();
		foreach(friendGateNames(isset($row['list']) ? $row['list'] : '') as $name)
		{
			if(!in_array($name,$current,true)) $current[] = $name;
		}
		$cache[$cacheUid] = implode(',',$current);
	}
	$_pm['mem']->del('db_blacklist');
	$_pm['mem']->set(array('k'=>'db_blacklist','v'=>$cache));
}

function friendGateRollback($message)
{
	global $_pm;
	$_pm['mysql']->query('ROLLBACK');
	die($message);
}


function liststr($friendlist)
{
	if ($friendlist === '')
	{
		header('Content-Type:text/html;charset=utf-8');
		die('#您还未添加任何好友！');
	}
	$arr = explode(',',$friendlist);
	if(!is_array($arr)) $arr[0]=$friendlist;
	$f = '';
	foreach($arr as $k => $v)
	{
		$vHtml = friendGateHtml($v);
		$vJs = friendGateHtml(friendGateJsSingle($v));
		$f .= "<span style='cursor:pointer;display:block;' onclick=\"chat('{$vJs}');\"><u>".$vHtml . '</u></span>';
	}
	header('Content-Type:text/html;charset=utf-8');
	die('#'.$f);
}
function liststr1($friendlist)
{
	if ($friendlist === '')
	{
		header('Content-Type:text/html;charset=utf-8');
		die('#');
	}
	$arr = explode(',',$friendlist);
	if(!is_array($arr)) $arr[0]=$friendlist;
	$f = '';
	foreach($arr as $k => $v)
	{
		if(empty($v)){
			continue;
		}
		$vHtml = friendGateHtml($v);
		$vJs = friendGateHtml(friendGateJsSingle($v));
		$f .= "<span style='cursor:pointer;display:block;' onclick=\"blacks('{$vJs}');\"><u>".$vHtml . '</u></span>';
	}
	header('Content-Type:text/html;charset=utf-8');
	die('#'.$f);
}

function friendGateHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function friendGateJsSingle($value)
{
	$value = str_replace("\\", "\\\\", (string)$value);
	$value = str_replace("'", "\\'", $value);
	$value = str_replace(array("\r", "\n"), array("\\r", "\\n"), $value);
	return $value;
}
?>
