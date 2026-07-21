<?php
/**
*@Author: %xueyuan%

*@Write Date: 2011.05.27
*@Update Date: 2011.05.27
*@Usage:Fightting saolei Mod
*@Note: none
*/
require_once('../config/config.game.php');
require_once(dirname(__FILE__).'/saolei_common.php');
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1)
{
	die('登录状态无效！');
}
$_SESSION['insl'] = $uid;
$czlxz = 65;	//成长率限制
$sql = "SELECT F_saolei_points FROM player_ext where uid = ".$uid;
$points = $_pm['mysql'] -> getOneRecord($sql);
if(!is_array($points)) $points = array('F_saolei_points'=>1);
if(!isset($points['F_saolei_points']) || intval($points['F_saolei_points']) < 1) $points['F_saolei_points'] = 1;
$leinum = $points['F_saolei_points'] -1;
$prize_info_best = slPrizeGetUserPool($_pm['mem'], $uid);
function saoleiModHtml($value)
{
	return htmlspecialchars(strval($value), ENT_QUOTES, 'UTF-8');
}
function saoleiModImage($value)
{
	$value = basename(strval($value));
	return preg_match('/^[A-Za-z0-9_.-]+$/', $value) ? $value : '';
}
	//扫雷复活卡id 为 4038
$sl_fhtime = $_pm['mysql'] -> getOneRecord(" SELECT SUM(sums) AS sums
                                               FROM userbag
                                              WHERE pid = 4038
                                                AND uid = $uid
                                                AND sums > 0
                                                AND zbing = 0
                                                AND (cantrade IS NULL OR cantrade<>3)");
$sl_fhtime = empty($sl_fhtime['sums'])?0:intval($sl_fhtime['sums']);

$gonggao = "<div id='sm' class='sm'><b>点击 <font color=red>?</font> 试试您的运气吧!</b></div>";
if(!is_array($prize_info_best))
{
	$config = slPrizeLoadBestConfig($_pm['mysql'], $_pm['mem']);
	$props = slPrizeLoadProps($_pm['mysql'], $_pm['mem']);
	$prize_info_best = slPrizeBuildUserPool($config, $props);
	if(!slPrizePoolIsComplete($prize_info_best) || !slPrizeStoreUserPool($_pm['mem'], $uid, $prize_info_best))
	{
		die('扫雷奖励配置暂不可用，请稍后重试！');
	}
}
//用户当前关数逻辑
$i = 1;
//每关奖品展示逻辑
$prize_echo = '<table id="everybox" width="140" ><tr>';
$prize_look_pic = '';
foreach($prize_info_best as $info)
{
	$prizeNameHtml = saoleiModHtml(isset($info['name']) ? $info['name'] : '');
	$prizeImgUrl = slPrizeImageUrl($info);
	$prize_look_pic .= '<td width="33%"><font>第'.$i.'关</font><img width="40px" height="40px" title="'.$prizeNameHtml.'" src="'.$prizeImgUrl.'" /></td>';
	if($i%3 == 0 && $i < 9)
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

$tj01 = slTodayUserHas($_pm['mem'], $uid);
$tj02 = slTodayTicketHas($_pm['mem'], $uid);
$czl = $_pm['mysql'] -> getOneRecord("SELECT userbb.czl FROM userbb,player WHERE player.id = ".$uid." AND player.mbid = userbb.id AND userbb.uid = player.id");
if(!is_array($czl)) $czl = array('czl'=>0);
if(intval($czl['czl']) < $czlxz)
{
	if(!slTodayUserSet($_pm['mem'], $uid, true)) die('扫雷状态暂不可用，请稍后重试！');
	$tj01 = true;
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
$echo = '';
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
