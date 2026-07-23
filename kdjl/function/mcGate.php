<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.19
*@Update Date: 2008.07.13
*@Usage: Save and Get pets of user
*/

require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');

$from = (isset($_REQUEST['from']) && !is_array($_REQUEST['from'])) ? intval($_REQUEST['from']) : 0;
if ($from != 1)
{
	secStart($_pm['mem']);
}

$mcTransactionActive = false;
$mcLockHeld = false;

function mcShutdown()
{
	global $_pm, $mcTransactionActive, $mcLockHeld;
	$error = error_get_last();
	if(!is_array($error) || !in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true))
	{
		return;
	}
	if($mcTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$mcTransactionActive = false;
	}
	if($mcLockHeld && function_exists('realseLock'))
	{
		realseLock();
		$mcLockHeld = false;
	}
}
register_shutdown_function('mcShutdown');

function mcStop($message)
{
	global $_pm, $mcTransactionActive, $mcLockHeld;
	if($mcTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$mcTransactionActive = false;
	}
	if($mcLockHeld && function_exists('realseLock'))
	{
		realseLock();
		$mcLockHeld = false;
	}
	die($message);
}

function mcCommit()
{
	global $_pm, $mcTransactionActive, $mcLockHeld;
	if (!$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		$mcTransactionActive = false;
		if($mcLockHeld && function_exists('realseLock')) realseLock();
		$mcLockHeld = false;
		die('操作提交失败，请重试！');
	}
	$mcTransactionActive = false;
	$_pm['mem']->del(MEM_USER_KEY);
	$_pm['mem']->del(MEM_USERBB_KEY);
	$_pm['mem']->del(MEM_USERSK_KEY);
	$_pm['mem']->del(MEM_USERBAG_KEY);
	if($mcLockHeld && function_exists('realseLock')) realseLock();
	$mcLockHeld = false;
}
function mcPasswordValue($key)
{
	$value = (isset($_REQUEST[$key]) && !is_array($_REQUEST[$key])) ? $_REQUEST[$key] : '';
	if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc())
	{
		$value = stripslashes($value);
	}
	return (string)$value;
}

function mcPasswordHash($value)
{
	return abs(crc32(md5((string)$value)));
}

function mcLegacyPasswordHash($value)
{
	global $_pm;
	$link = (isset($_pm['mysql']) && is_object($_pm['mysql'])) ? $_pm['mysql']->getConn() : null;
	if (is_resource($link)) $value = mysql_real_escape_string($value, $link);
	else $value = addslashes($value);
	return mcPasswordHash(htmlspecialchars($value));
}

function mcPasswordMatches($value, $storedHash)
{
	return mcPasswordHash($value) == $storedHash || mcLegacyPasswordHash($value) == $storedHash;
}

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
$id = (isset($_REQUEST['id']) && !is_array($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0;
$op = (isset($_REQUEST['op']) && !is_array($_REQUEST['op'])) ? strval($_REQUEST['op']) : '';
$validOps = array('z', 'change', 's', 'g', 'd');

if ($uid < 1 || $id < 1 || !in_array($op, $validOps, true))
{
	die('数据错误1！');
}

$a = getLock($uid);
if(!is_array($a))
{
	die('服务器繁忙，请稍候再试！');
}
$mcLockHeld = true;
$mcTransactionActive = true;
$db = $_pm['mysql'];
$user = $db->getOneRecord('SELECT * FROM player WHERE id='.$uid.' FOR UPDATE');
$userbb = $db->getRecords('SELECT * FROM userbb WHERE uid='.$uid.' ORDER BY level DESC FOR UPDATE');

if (!is_array($user) || !is_array($userbb))
{
	mcStop('数据错误2！');
}

$mc = 0;
$bagmc = 0;
$target = false;
foreach ($userbb as $pet)
{
	if (intval($pet['muchang']) == 1) $mc++;
	else if (intval($pet['muchang']) == 0) $bagmc++;
	if (intval($pet['id']) == $id) $target = $pet;
}

if (!is_array($target))
{
	mcStop('数据错误3！');
}

if ($op == 'z')
{
	if ($id == intval($user['mbid']))
	{
		mcStop('已经是主战！');
	}
	if (intval($target['muchang']) != 0 || intval($target['tgflag']) != 0)
	{
		mcStop('在牧场的宝宝不能设为主战哦！');
	}

	$task = $db->getOneRecord('SELECT taskid FROM tasklog WHERE uid='.$uid.' AND taskid=9999');
	if (is_array($task))
	{
		mcStop('10');
	}

	$sql = 'UPDATE player SET fightbb='.$id.',mbid='.$id.' WHERE id='.$uid;
	if (!$db->query($sql) || mysql_affected_rows($db->getConn()) != 1)
	{
		mcStop('操作失败!');
	}
	mcCommit();
	die($from == 1 ? 'OK' : '更改主战宝宝成功!');
}

if ($op == 'change')
{
	if ($id == intval($user['mbid']))
	{
		mcStop('已经是主战！');
	}
	if (intval($target['muchang']) != 0 || intval($target['tgflag']) != 0)
	{
		mcStop('在牧场的宝宝不能设为主战哦！');
	}

	$sql = 'UPDATE player SET mbid='.$id.',fightbb='.$id.',task="",tasklog="" WHERE id='.$uid;
	if (!$db->query($sql) || mysql_affected_rows($db->getConn()) != 1)
	{
		mcStop('操作失败!');
	}
	if (!$db->query('DELETE FROM tasklog WHERE uid='.$uid.' AND taskid=9999'))
	{
		mcStop('操作失败!');
	}
	mcCommit();
	die('更改主战宝宝成功！');
}

if ($op == 's')
{
	if ($id == intval($user['mbid']))
	{
		mcStop('该宠物为主战宠物，无法寄养！');
	}
	if (intval($target['muchang']) != 0 || intval($target['tgflag']) != 0)
	{
		mcStop('已经在牧场或处于其他处理中！');
	}
	if ($mc >= intval($user['maxmc']))
	{
		mcStop('您的宠物寄养空间已满，不能寄养更多宝宝！');
	}
	if ($bagmc <= 1)
	{
		mcStop('您必须携带一个宝宝，以便参加战斗!');
	}

	$sql = 'UPDATE userbb SET muchang=1 WHERE uid='.$uid.' AND id='.$id.
		' AND muchang=0 AND tgflag=0';
	if (!$db->query($sql) || mysql_affected_rows($db->getConn()) != 1)
	{
		mcStop('宠物状态已经变化，请刷新后重试！');
	}
	mcCommit();
	die('操作成功!');
}

if ($op == 'g')
{
	if (!empty($user['fieldpwd']) && empty($_SESSION['loginField'.$uid]))
	{
		mcStop('请先登录 !');
	}
	if (intval($target['muchang']) != 1)
	{
		mcStop('此宠物已经携带或处于其他处理中！');
	}
	if (intval($target['tgflag']) != 0)
	{
		mcStop('托管中的宠物不能取出！');
	}
	if ($bagmc >= 3)
	{
		mcStop('您最多同时只能携带3个宝宝！');
	}
	if (!empty($target['chchengsx']))
	{
		mcStop('传承中不能取出');
	}

	$sql = 'UPDATE userbb SET muchang=0 WHERE uid='.$uid.' AND id='.$id.
		' AND muchang=1 AND tgflag=0 AND (chchengsx IS NULL OR chchengsx="")';
	if (!$db->query($sql) || mysql_affected_rows($db->getConn()) != 1)
	{
		mcStop('宠物状态已经变化，请刷新后重试！');
	}
	mcCommit();
	die('操作成功!');
}

if ($id == intval($user['mbid']))
{
	mcStop('主战宠物不能丢弃！');
}
if (intval($target['muchang']) != 0 && intval($target['muchang']) != 1)
{
	mcStop('处理中的宠物不能丢弃！');
}
if (intval($target['tgflag']) != 0 || !empty($target['chchengsx']))
{
	mcStop('托管或传承中的宠物不能丢弃！');
}
if (intval($target['muchang']) == 0 && $bagmc <= 1)
{
	mcStop('您必须携带一个宝宝，以便参加战斗!');
}
if (intval($user['money']) < 10000)
{
	mcStop('您没有足够多的金币哦！');
}

$pwd = mcPasswordValue('pwd');
if ($pwd === '' && !empty($user['fieldpwd']))
{
	mcStop('请输入密码！');
}
if (!empty($user['fieldpwd']) && !mcPasswordMatches($pwd, $user['fieldpwd']))
{
	mcStop('1');
}
if (!empty($user['fieldpwd']) && mcPasswordHash($pwd) != $user['fieldpwd'])
{
	if (!$db->query('UPDATE player SET fieldpwd='.mcPasswordHash($pwd).' WHERE id='.$uid))
	{
		mcStop('密码升级失败，请重试！');
	}
}

$equipment = $db->getRecords(
	'SELECT pid FROM userbag WHERE uid='.$uid.' AND zbpets='.$id.' AND sums>0 FOR UPDATE'
);
$equipmentText = '';
if (is_array($equipment) && count($equipment) > 0)
{
	$equipmentIds = array();
	foreach ($equipment as $item)
	{
		$equipmentIds[] = intval($item['pid']);
	}
	$equipmentText = '装备：'.implode(',', $equipmentIds).',';
}
$logText = $equipmentText.'等级：'.$target['level'].',成长：'.$target['czl'].',名字：'.$target['name'];

if (!$db->query('UPDATE player SET money=money-10000 WHERE id='.$uid.' AND money>=10000') ||
	mysql_affected_rows($db->getConn()) != 1)
{
	mcStop('金币扣除失败，请重试！');
}
if (!$db->query('DELETE FROM skill WHERE bid='.$id))
{
	mcStop('宠物技能删除失败，请重试！');
}
if (!$db->query('DELETE FROM userbag WHERE uid='.$uid.' AND zbpets='.$id))
{
	mcStop('宠物装备删除失败，请重试！');
}
if (!$db->query('DELETE FROM userbb WHERE uid='.$uid.' AND id='.$id) ||
	mysql_affected_rows($db->getConn()) != 1)
{
	mcStop('宠物删除失败，请重试！');
}

mcCommit();
$_pm['mem']->del('format_user_zhuangbei_'.$id);
$_pm['mem']->del('User_bb_equip_changed_'.$id.'_'.$uid);
$db->query(
	'INSERT INTO gamelog (ptime,seller,buyer,pnote,vary) VALUES ('.time().','.$uid.','.$uid.','.
	$db->quote($logText).',16)'
);
die('操作成功!');
?>
