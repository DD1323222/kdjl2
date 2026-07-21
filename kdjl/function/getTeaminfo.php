<?php

/**

@Usage: 获取当前玩家的队伍成员信息。

*/



require_once('../config/config.game.php');

secStart($_pm['mem']);



$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');
$user	 = $_pm['user']->getUserById($uid);
if(!is_array($user)) die('');
$trow = $_pm['mysql']->getOneRecord("SELECT team_id,state FROM team_members WHERE uid={$uid} AND state>-1 ORDER BY state DESC,team_id LIMIT 1");
$tid = (is_array($trow) && isset($trow['team_id'])) ? intval($trow['team_id']) : 0;
if($tid < 1) die('');
$_SESSION['team_id']=$tid;

$teamRow = $_pm['mysql']->getOneRecord("SELECT creator FROM team WHERE id={$tid}");
if(!is_array($teamRow) || !isset($teamRow['creator'])) die('');
$leaderId = intval($teamRow['creator']);

// Get user information.

$rs = $_pm['mysql']->getRecords("SELECT player.nickname,player.headimg,player.id,team_members.state

								   FROM player,team_members

								  WHERE player.id=team_members.uid AND team_members.team_id={$tid} AND team_members.state>-1

								  ORDER BY team_members.apply_time

								");

if (is_array($rs))

{

	foreach ($rs as $k => $v)

	{
		if(!is_array($v)) continue;
		$headimg = isset($v['headimg']) ? intval($v['headimg']) : 0;
		if(!isset($v['nickname'])) $v['nickname'] = '';
		$nickname = htmlspecialchars($v['nickname'], ENT_QUOTES, 'UTF-8');
		$role = (isset($v['id']) && intval($v['id']) == $leaderId) ? '队长' : '队友';

		echo '<table width="100%" border="0" cellspacing="0" cellpadding="0">

          <tr>

            <td width="8">&nbsp;</td>

            <td width="21"><img src="'.IMAGE_SRC_URL.'/ui/team/tt01.gif" width="21" height="37"></td>

            <td width="11">&nbsp;</td>

            <td width="60"><img src="'.IMAGE_SRC_URL.'/head/'.$headimg.'.gif" width="60" height="49"></td>

            <td width="14">&nbsp;</td>

            <td>'.$role.'：'.$nickname.'</td>

          </tr>

        </table>

          <table width="100%" height="19" border="0" cellpadding="0" cellspacing="0">

            <tr>

              <td style="border-top:1px solid #d9c98b;">&nbsp;</td>

            </tr>

          </table>';

	}

}



?>
