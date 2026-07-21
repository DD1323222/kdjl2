<?php
/**
* user name. Check.
*/
require_once("../config/config.game.php");
//secStart($_pm['mem']);
$hasNickname = isset($_GET['n']);
$hasUsername = isset($_GET['u']);
if(!$hasNickname && !$hasUsername)
{
	return;
}
$db = new mysql();

if($hasUsername)
{
	$u = !is_array($_GET['u']) ? trim($_GET['u']) : '';
	if(!kdjlValidAccountName($u))
	{
		die('error');
	}
	$_user = $db->escape($u);
	$rs = $db->getOneRecord("SELECT id FROM player WHERE name = '{$_user}'");
	if (is_array($rs))
	{
		die("<span style='color:#f00;font-size:12px'>已经存在</span>");
	}
	echo 'OK';
	return;
}

$n = ($hasNickname && !is_array($_GET['n'])) ? trim($_GET['n']) : '';
if(!kdjlValidNickname($n))
{
	die('error');
}
$_user = $db->escape($n);
$rs = $db->getOneRecord("SELECT id FROM player WHERE nickname = '{$_user}'");
if (is_array($rs))
{
	die("<span style='color:#f00;font-size:12px'>已经存在</span>");
}

echo 'OK';
?>
