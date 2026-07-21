<?php

/**

*@Usage: 队伍系统处理模块

*/


require_once('../config/config.game.php');

//define('TEAM_MSG_KEY',	'team_msg' . crc32(session_id()));
secStart($_pm['mem']);
require_once(dirname(__FILE__).'/../socketChat/config.chat.php');


$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('1');
$user		= $_pm['user']->getUserById($uid);
if(!is_array($user)) die('1');

$op = (isset($_REQUEST['op']) && !is_array($_REQUEST['op'])) ? intval($_REQUEST['op']) : 0;

switch($op)

{

	case 1: vMember();break;

	case 2: LMember();break;



}



// 离开队伍

function LMember()

{

	global $user;

	$s = new socketmsg();
	$teamId = isset($_SESSION['team_id']) ? intval($_SESSION['team_id']) : 0;
	$team = new team($teamId, $s);
	$team->checkMyTeam();
	$teamId = isset($_SESSION['team_id']) ? intval($_SESSION['team_id']) : 0;
	if($teamId > 0)
	{
		if($team->isTeamLeader($user['id'], $teamId)) $result=$team->disbandTeam();
		else $result=$team->leaveTeam();
		if($result!==true) die($result);
	}

	die('10');

}



// 邀请组队。

function vMember()

{

	global $_pm, $user;

	$s = new socketmsg();
	$teamId = isset($_SESSION['team_id']) ? intval($_SESSION['team_id']) : 0;
	$team = new team($teamId, $s);
	$team->checkMyTeam();



	$requestUserName = (isset($_REQUEST['u']) && !is_array($_REQUEST['u'])) ? $_REQUEST['u'] : '';
	if (strlen(trim($requestUserName)) < 3 && $requestUserName!='GM') return false;

	$userName = $_pm['mysql']->escape($requestUserName);
	$userNameHtml = htmlspecialchars($requestUserName, ENT_QUOTES, 'UTF-8');



	if(!isset($_SESSION['team_id']) || intval($_SESSION['team_id']) < 1)
	{
		$rsCreate = $team->createTeam();
		if($rsCreate !== true) die($rsCreate);
		$team = new team(intval($_SESSION['team_id']), $s);
	}



	$rs = $_pm['mysql']->getOneRecord("SELECT id,lastvtime

										 FROM player

										WHERE nickname='{$userName}'");

	if (!is_array($rs)) die('玩家不存在！');



	if ($rs['lastvtime']+300 < time()) die("玩家 {$userNameHtml} 当前不在线！");

	else

	{
		$ret = $team->inviteTeam(intval($rs['id']));
		if($ret !== true) die($ret);

		die('1');

	}

}

?>
