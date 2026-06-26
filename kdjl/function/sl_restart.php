<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.05.29
*@Usage:Fightting Display
*@Note: none
Mem style.
*/
require_once('../sec/dblock_fun.php');
require_once('../config/config.game.php');
$a = getLock($_SESSION['id']);
if(!is_array($a)){
	realseLock();
	die('服务器繁忙，请稍候再试！');
}
$sql = "SELECT * FROM userbag WHERE pid = 4019 AND sums > 0 AND uid = ".$_SESSION['id'];
$res = $_pm['mysql'] -> getOneRecord($sql);
if(!is_array($res))
{
	realseLock();
	die("1");
}
$_pm['mysql'] -> query(" UPDATE userbag SET sums=sums-1 WHERE uid = '".$_SESSION['id']."' AND pid = 4019 ");
$_pm['mysql'] -> query(" DELETE FROM userbag WHERE uid = '".$_SESSION['id']."' AND pid = 4019  AND sums=0 AND bsum =0 AND psum=0");
$props = unserialize($_pm['mem']->get('db_props'));

$configWelcome = unserialize($_pm['mem']->get('db_welcome'));
$prize_info_best = unserialize($_pm['mem']->get('sl_prize_info'));
$prize_info_best = $prize_info_best[$_SESSION['id']];

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
//$_pm['mem']->set(array('k' => 'sl_prize_info'.$_SESSION['id'], 'v' => $prize_info_best));
$prize_info_best_all = unserialize($_pm['mem']->get('sl_prize_info'));
$prize_info_best_all[$_SESSION['id']] = $prize_info_best;
$_pm['mem']->set(array('k' => 'sl_prize_info','v' => $prize_info_best_all));
//每关奖品展示逻辑
$i = 1;
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
				</tr></table>';
echo $prize_echo;
realseLock();
?>
