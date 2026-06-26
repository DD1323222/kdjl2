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
$props = unserialize($_pm['mem']->get('db_props'));
$sl_fhtime = $_pm['mysql'] -> getOneRecord(" SELECT sums FROM userbag WHERE pid = 4038 AND uid =  {$_SESSION['id']}");
$sl_fhtime = empty($sl_fhtime)?0:$sl_fhtime['sums'];
$res = $_pm['mysql'] -> getOneRecord("SELECT F_saolei_points FROM player_ext WHERE uid = ".$_SESSION['id']);
$configWelcome = unserialize($_pm['mem']->get('db_welcome'));
$sl_pic = '<table id="leiqu" width="283" height="283"><tr>';

$tj01 = false;	//条件1,是否扫过1次,默认没有且扫雷成长满足要求
$tj02 = false;	//条件2,是否使用过扫雷卡,默认没有
$today_sl = unserialize($_pm['mem']->get('today_sl_user'));
$today_sl_ticket_use = unserialize($_pm['mem']->get('today_is_use_ticket'));
foreach($today_sl as $info)
{
	if($info == $_SESSION['id'])	//满足已经扫过1次
	{
		$tj01 = true;
	}
}
$czl = $_pm['mysql'] -> getOneRecord("SELECT userbb.czl FROM userbb,player WHERE player.id = '".$_SESSION['id']."' AND player.mbid = userbb.id");
if(intval($czl['czl']) < $czlxz)
{
	$tj01 = true;
}
if(!is_array($today_sl_ticket_use))
{
	$tj02 = false;
}
else
{
	foreach($today_sl_ticket_use as $info)
	{
		if($info == $_SESSION['id'])	//满足使用过
		{
			$tj02 = true;
		}
	}
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
