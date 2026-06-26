<?php
/**
*@Author: %xueyuan%

*@Write Date: 2011.05.27
*@Update Date: 2011.05.27
*@Usage:Fightting saolei Mod
*@Note: none
*/
require_once('../config/config.game.php');
$_SESSION['insl'] = $_SESSION['id'];
$czlxz = 65;	//成长率限制
$sql = "SELECT F_saolei_points FROM player_ext where uid = ".$_SESSION['id'];
$points = $_pm['mysql'] -> getOneRecord($sql);
$leinum = $points['F_saolei_points'] -1;
$configWelcome = unserialize($_pm['mem']->get('db_welcome'));
//$prize_info_best = unserialize($_pm['mem']->get('sl_prize_info'.$_SESSION['id']));
$prize_info_best = unserialize($_pm['mem']->get('sl_prize_info'));
$prize_info_best = $prize_info_best[$_SESSION['id']];
	//扫雷复活卡id 为 4038
$sl_fhtime = $_pm['mysql'] -> getOneRecord(" SELECT sums FROM userbag WHERE pid = 4038 AND uid =  {$_SESSION['id']}");
$sl_fhtime = empty($sl_fhtime)?0:$sl_fhtime['sums'];

$gonggao = "<div id='sm' class='sm'><b>点击 <font color=red>?</font> 试试您的运气吧!</b></div>";
if(!is_array($prize_info_best))
{
	$props = unserialize($_pm['mem']->get('db_props'));
	if(is_array($configWelcome))
	{
		foreach($configWelcome as $info)
		{
			if(substr($info['code'],0,14) == 'sl_prize_best_')
			{
				$prize_info[] = $info['contents'];
			}
		}
	}
	else
	{
		$sql = "SELECT contents FROM welcome WHERE code like '%sl_prize_best_%'";
		$prize_info = $_pm['mysql'] -> getRecords($sql);
		$res = $db->getRecords("select * from welcome");	//自动加载机制
		$_pm['mem']->set(array('k' => 'db_welcome', 'v' => $res));
	}
	foreach($prize_info as $key=>$info)
	{
		$arr_key = $key+1;
		$every_points = explode(',',$info);
		$every_prize_id[$arr_key] = $every_points[array_rand($every_points)];
	}
	foreach($props as $info)
	{
		foreach($every_prize_id as $key => $val)
		{
			if($info['id'] == $val)
			{
				$prize_info_best[$key] = $info;
			}
		}
	}
	ksort($prize_info_best);
	//存入内存逻辑
	$prize_info_best_all = unserialize($_pm['mem']->get('sl_prize_info'));
	$prize_info_best_all[$_SESSION['id']] = $prize_info_best;
	$_pm['mem']->set(array('k' => 'sl_prize_info','v' => $prize_info_best_all));
}
//用户当前关数逻辑
$i = 1;
//每关奖品展示逻辑
$prize_echo .= '<table id="everybox" width="140" ><tr>';
foreach($prize_info_best as $info)
{
	$prize_look_pic .= '<td width="33%"><font>第'.$i.'关</font><img width="40px" height="40px" title="'.$info['name'].'" src='.IMAGE_SRC_URL."/props/".$info['img']."  /></td>";
	if( $i%3 == 0 && $i<=9)
	{
		$prize_echo .= $prize_look_pic."</tr><tr>";
		$prize_look_pic = '';
	}
	else
	{
		$prize_echo .= $prize_look_pic;
		$prize_look_pic = '';
	}
	$i++;
}
$prize_echo .= '</tr>
				<tr class="noborder">
					<td class="noborder" colspan="3"><img class="btn" onclick="sl_restart('."'sx'".')" src="../images/img/sl09.gif" /></td>
				</tr>
			</table>';
//每关奖品展示逻辑
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
		break;
	}
}
$czl = $_pm['mysql'] -> getOneRecord("SELECT userbb.czl FROM userbb,player WHERE player.id = '".$_SESSION['id']."' AND player.mbid = userbb.id");
if(intval($czl['czl']) < $czlxz)
{	
	if(!in_array($_SESSION['id'],$today_sl))
	{
		$today_sl[] = $_SESSION['id'];
		$_pm['mem']->set(array('k' => 'today_sl_user', 'v' => $today_sl));
	}
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
if($tj01 && !$tj02 && $points['F_saolei_points'] == 1)
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
//加载模块
$fn='tpl_sl.html';
$tn = $_game['template'] . $fn;
if (file_exists($tn))
{
	$tpl = file_get_contents($tn);
	$src = array
	(
		'#gonggao#','#sl_pic#','#prize#','#points#','#fhtime#','#leinum#'
	);
	$des = array($gonggao,$sl_pic,$prize_echo,$points['F_saolei_points'],$sl_fhtime,$leinum);

	$echo = str_replace($src, $des, $tpl);
}
echo $echo;
?>
