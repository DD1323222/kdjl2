<?php
require_once(dirname(__FILE__).'/../config/config.game.php');

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
$nickname = (isset($_GET['nickname']) && !is_array($_GET['nickname'])) ? trim($_GET['nickname']) : '';
if($uid < 1 || $nickname === '' || strlen($nickname) > 60)
{
	die('您查询的用户不存在！');
}

$nicknameSql = $_pm['mysql']->escape($nickname);
$target = $_pm['mysql']->getOneRecord(
	"SELECT id FROM player WHERE nickname='{$nicknameSql}' " .
	"AND password != '00000000000000000000000000000000' LIMIT 1"
);
if(!is_array($target) || intval($target['id']) < 1)
{
	die('您查询的用户不存在！');
}

$targetUid = intval($target['id']);
$qyRow = $_pm['mysql']->getOneRecord(
	"SELECT COALESCE(SUM(sml), 0) AS qy FROM ml " .
	"WHERE (uid={$uid} AND tid={$targetUid}) OR (uid={$targetUid} AND tid={$uid})"
);
$mlRow = $_pm['mysql']->getOneRecord("SELECT ml FROM player_ext WHERE uid={$targetUid} LIMIT 1");
$qy = is_array($qyRow) ? intval($qyRow['qy']) : 0;
$ml = is_array($mlRow) ? intval($mlRow['ml']) : 0;

die('恭喜，您输入的用户存在!qy:'.$qy.'ml:'.$ml);
?>
