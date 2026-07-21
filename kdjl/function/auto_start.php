<?php
/**
*@Author: %xueyuan%

*@Write Date: 2011.05.27
*@Update Date: 2011.05.27
*@Usage:Fightting saolei Mod
*@Note: none
*/
$czlxz = 65;
require_once('../config/config.game.php');
require_once(dirname(__FILE__).'/saolei_common.php');
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');
$sl_fhtime = $_pm['mysql'] -> getOneRecord(" SELECT SUM(sums) AS sums
                                               FROM userbag
                                              WHERE pid = 4038
                                                AND uid = {$uid}
                                                AND sums > 0
                                                AND zbing = 0
                                                AND (cantrade IS NULL OR cantrade<>3)");
$sl_fhtime = empty($sl_fhtime['sums'])?0:intval($sl_fhtime['sums']);
$res = $_pm['mysql'] -> getOneRecord("SELECT F_saolei_points FROM player_ext WHERE uid = ".$uid);
if(!is_array($res)) $res = array('F_saolei_points'=>1);
if(!isset($res['F_saolei_points']) || intval($res['F_saolei_points']) < 1) $res['F_saolei_points'] = 1;
$sl_pic = '<table id="leiqu" width="283" height="283"><tr>';

$tj01 = slTodayUserHas($_pm['mem'], $uid);
$tj02 = slTodayTicketHas($_pm['mem'], $uid);
$czl = $_pm['mysql'] -> getOneRecord("SELECT userbb.czl FROM userbb,player WHERE player.id = '".$uid."' AND player.mbid = userbb.id AND userbb.uid = player.id");
if(!is_array($czl)) $czl = array('czl'=>0);
if(intval($czl['czl']) < $czlxz)
{
	$tj01 = true;
}
if($tj01 && !$tj02 && $res['F_saolei_points'] == 1)
{
	for($i=1;$i<10;$i++)
	{
		if(($i-1)%3 == 0 && ($i-1) != 0)
		{
			$sl_pic .= '</tr><tr>';
		}
		$sl_pic .= '<td><div id="lq_'.$i.'" onclick="canntplay()" style="filter:alpha(opacity=100);opacity:1" class="btn tdclose"></div></td>';
	}
}
else
{
	for($i=1;$i<10;$i++)
	{
		if(($i-1)%3 == 0 && ($i-1) != 0)
		{
			$sl_pic .= '</tr><tr>';
		}
		$sl_pic .= '<td><div id="lq_'.$i.'" onclick="flash(this.id,1)" style="filter:alpha(opacity=100);opacity:1" class="btn tdclose"></div></td>';
	}
}
$sl_pic .= '</tr></table>';
echo $res['F_saolei_points'].'<Boundaries>'.$sl_pic.'<Boundaries>'.$sl_fhtime;
?>
