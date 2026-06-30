<?php

/**

*@Usage: 队伍系统处理模块

*/


require_once('../config/config.game.php');

//define(TEAM_MSG_KEY,	'team_msg' . crc32(session_id()));

secStart($_pm['mem']);



$user		= $_pm['user']->getUserById($_SESSION['id']);

$op = intval($_REQUEST['op']);

switch($op)

{

	case 1: vMember();break;

	case 2: LMember();break;

	

}



// 离开队伍

function LMember()

{

	global $user;

	$team = new team();

	$team->outTeam($user['id']);

	die('10');

}



// 邀请组队。

function vMember()

{

	global $_pm, $user;

	$team = new team();



	if (strlen(trim($_REQUEST['u'])) < 3 && $_REQUEST['u']!='GM') return false;

	$userName = $_pm['mysql']->escape($_REQUEST['u']);

	

	if ($user['openteam']!=0 && $user['openteam']!=999999999) {die('您不是队长，不能邀请玩家！');return false;}



	$rs = $_pm['mysql']->getOneRecord("SELECT id,lastvtime,online,openteam

										 FROM player 

										WHERE nickname='{$userName}'");

	if (!is_array($rs)) die('玩家不存在！');



	if ($rs['openteam'] != 0) die("玩家 {$userName} 已经有队伍！");

	else if ($rs['lastvtime']+300 < time()) die("玩家 {$userName} 当前不在线！");

	else

	{

		// 写入邀请组队标记。

		$key = crc32($userName);

		$key = $key<1?1-$key-1:$key;

		$_pm['mem']->set(array('k' =>$key ,'v'=> array($_SESSION['nickname'],$userName)));



		$team->addTeamMember($user['id'], $rs['id']);	//	建立队伍信息。

		// 更新队长信息。

		$_pm['mysql']->query("UPDATE player

								 SET openteam=999999999 

							   WHERE id={$user['id']}

							 ");

		// 更新成员信息。

		$_pm['mysql']->query("UPDATE player

								 SET openteam={$user['id']} 

							   WHERE id={$rs['id']}");

		

		die('1');

	}

}

?>
