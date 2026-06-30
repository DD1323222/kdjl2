<?php
/**
 * @uses encrypt the field
 * @author Zheng.Ping
 * @date 2009-02-26
 */
require_once('../config/config.game.php');
secStart($_pm['mem']);

function fieldPasswordValue($key)
{
	$value = isset($_REQUEST[$key]) ? $_REQUEST[$key] : '';
	return htmlspecialchars(mysql_escape_string($value));
}

$uid = intval($_SESSION['id']);
$action = isset($_REQUEST['action']) ? strval($_REQUEST['action']) : '';
$allowedActions = array('reg', 'do', 'login', 'reset');
if ($uid < 1 || !in_array($action, $allowedActions, true))
{
	die('0');
}

$user = $_pm['mysql']->getOneRecord('SELECT fieldpwd FROM player WHERE id='.$uid);
if (!is_array($user))
{
	die('0');
}

if ($action == 'reg')
{
	$pwd = fieldPasswordValue('pwd');
	$repwd = fieldPasswordValue('repwd');
	if (!empty($user['fieldpwd']) && empty($_SESSION['loginField'.$uid]))
	{
		die('3');
	}
	if ($pwd === '') die('0');
	if (strlen($pwd) <= 3 || strlen($pwd) > 10) die('4');
	if ($repwd === '') die('1');
	if ($pwd !== $repwd) die('2');
	die('10');
}

if ($action == 'do')
{
	if (!empty($user['fieldpwd']) && empty($_SESSION['loginField'.$uid]))
	{
		die('请先登录');
	}
	$pwd = fieldPasswordValue('pwd');
	if ($pwd === '') die('0');
	if (strlen($pwd) <= 3 || strlen($pwd) > 10) die('4');
	$pwdHash = abs(crc32(md5($pwd)));
	if (empty($pwdHash)) die('0');
	if (!$_pm['mysql']->query('UPDATE player SET fieldpwd='.$pwdHash.' WHERE id='.$uid))
	{
		die('0');
	}
	$_SESSION['loginField'.$uid] = '1';
	die('10');
}

if ($action == 'login')
{
	if (empty($user['fieldpwd'])) die('2');
	$pwd = fieldPasswordValue('pwd');
	if ($pwd === '') die('0');
	$pwdHash = abs(crc32(md5($pwd)));
	if ($pwdHash != $user['fieldpwd']) die('1');
	$_SESSION['loginField'.$uid] = '1';
	die('10');
}

if (empty($user['fieldpwd'])) die('1');
$oldPwd = fieldPasswordValue('pwd');
$newPwd = fieldPasswordValue('repwd');
if ($oldPwd === '' || $newPwd === '') die('0');
if (strlen($newPwd) <= 3 || strlen($newPwd) > 10) die('0');
if (abs(crc32(md5($oldPwd))) != $user['fieldpwd']) die('1');

$newHash = abs(crc32(md5($newPwd)));
if (empty($newHash) || !$_pm['mysql']->query('UPDATE player SET fieldpwd='.$newHash.' WHERE id='.$uid))
{
	die('0');
}
$_SESSION['loginField'.$uid] = '1';
die('10');
?>
