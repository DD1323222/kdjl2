<?php
/**
@Usage:获得被挑战玩家的信息
*/
require_once('../config/config.game.php');
secStart($_pm['mem']);

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('0');
$user = $_pm['user']->getUserById($uid);
if(!is_array($user)) die('0');
$requestName = (isset($_REQUEST['u']) && !is_array($_REQUEST['u'])) ? trim($_REQUEST['u']) : '';
if($requestName === '') die('0');
$uname = $_pm['mysql']->escape($requestName);
$rs = $_pm['mysql']->getOneRecord("SELECT id,mbid
					    FROM player
					   WHERE nickname='{$uname}'
					   LIMIT 0,1
					");
if(!is_array($rs) || intval($rs['id']) == $uid || intval($rs['mbid']) < 1)
{
	$_pm['mem']->memClose();
	die('0');
}
$bb = $_pm['mysql']->getOneRecord("SELECT level
					    FROM userbb
						   WHERE id={$rs['mbid']} AND uid={$rs['id']} AND muchang=0 AND tgflag=0
					   LIMIT 0,1
					");
if(!is_array($bb))
{
	$_pm['mem']->memClose();
	die('0');
}


$_pm['mem']->memClose();

if (is_array($rs))
{
	if ($bb['level']<20) echo 1;
	else {
		$ext = $_pm['mysql']->getOneRecord("SELECT tiaozhan
					    FROM player_ext
					   WHERE uid=".$rs['id']);
		if(!is_array($ext)) $ext = array('tiaozhan' => 0);
		if($ext['tiaozhan']==0)	//0为不允许
		{
			echo '2';
		}
		else
		{
			echo $rs['mbid'];
		}
	}
}
else echo 0;
//####################
?>
