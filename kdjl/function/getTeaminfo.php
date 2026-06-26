<?php

/**

@Usage: 获取当前玩家的队伍成员信息。

*/



require_once('../config/config.game.php');

secStart($_pm['mem']);



$user	 = $_pm['user']->getUserById($_SESSION['id']);

$team = new team();



if ($user['openteam']=='999999999') $tid = $user['id'];

else $tid = $user['openteam'];



// get team member information.

$member = $team->getMember($tid);



if ($member === FALSE) die('');



// Get user inforamtion.

$rs = $_pm['mysql']->getRecords("SELECT nickname,headimg,id,openteam 

								   FROM player

								  WHERE id in({$member})

								");

if (is_array($rs))

{

	foreach ($rs as $k => $v)

	{

		echo '<table width="100%" border="0" cellspacing="0" cellpadding="0">

          <tr>

            <td width="8">&nbsp;</td>

            <td width="21"><img src='.IMAGE_SRC_URL.'/ui/team/tt01.gif" width="21" height="37"></td>

            <td width="11">&nbsp;</td>

            <td width="60"><img src="'.IMAGE_SRC_URL.'/head/'.$v['headimg'].'.gif" width="60" height="49"></td>

            <td width="14">&nbsp;</td>

            <td>'.($v['openteam']=='999999999'?'队长':'队友').'：'.$v['nickname'].'</td>

          </tr>

        </table>

          <table width="100%" height="19" border="0" cellpadding="0" cellspacing="0">

            <tr>

              <td background="../images/ui/team/tt03.gif">&nbsp;</td>

            </tr>

          </table>';

	}

}



?>