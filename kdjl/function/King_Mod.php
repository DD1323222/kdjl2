<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2009.01.6
*@Usage: King
*@Note: none
*/
require_once('../config/config.game.php');
require_once(dirname(__FILE__).'/aoyun_common.php');

secStart($_pm['mem']);

function kingModHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) exit;
$user	 = $_pm['user']->getUserById($uid);
if(!is_array($user)) $user = array();
$taskId = isset($user['task']) ? intval($user['task']) : 0;
$prestige = isset($user['prestige']) ? $user['prestige'] : 0;
$jprestige = isset($user['jprestige']) ? $user['jprestige'] : 0;
//Word part.
$taskword= taskcheck($taskId,6);
$props = kdjlSafeMemValue($_pm['mem']->get(MEM_PROPS_KEY), array());
if(!is_array($props)) $props = array();
$_gpc = kdjlSafeMemValue($_pm['mem']->get(MEM_GPC_KEY), array());
$taskitem = (isset($task) && isset($task[$taskId])) ? $task[$taskId] : array();
/*$taskitem	= $_pm['mem']->dataGet(array('k'	=>	MEM_TASK_KEY,
										 'v'	=>	"if(\$rs['id']== '{$user['task']}') \$ret=\$rs;"
									));*/




$m = $_pm['mem'];
$taskArr = array();
$rwlidarr = array();



//知识问答
$timearr1 = kdjlSafeMemValue($_pm['mem']->get(MEM_TIMENEW_KEY), array());
$timearr = (is_array($timearr1) && isset($timearr1['dati']) && is_array($timearr1['dati'])) ? $timearr1['dati'] : array();
$aoyunActive = kdjlAoyunActiveWindow($timearr, time()) !== false;

$taskword= taskcheck($taskId,6);

$aoyunRs = $_pm['mysql']->getOneRecord("SELECT times, result,oksum
									 FROM aoyun_player
									WHERE uid={$uid}
								 ORDER BY id LIMIT 1
								 ");
$oksum = (is_array($aoyunRs) && isset($aoyunRs['oksum'])) ? $aoyunRs['oksum'] : 0;
if ($aoyunActive && is_array($aoyunRs) && isset($aoyunRs['times']) && isset($aoyunRs['result']) && $aoyunRs['times']>0 && $aoyunRs['result']==1)	// 领奖已激活
{
	// in here add time limit.
	$active="style='cursor:pointer;'";
}
else $active='';

$welcome = memContent2Arr("db_welcome",'code');

$a = (is_array($welcome) && isset($welcome['dati']['contents'])) ? $welcome['dati']['contents'] : '';
if(empty($a))
{
	$welcomeRs = $_pm['mysql']->getOneRecord("SELECT contents from welcome where code='dati'");
	$a = is_array($welcomeRs) && isset($welcomeRs['contents']) ? $welcomeRs['contents'] : '';
}

if(empty($a))
{
    $a = "活动内容，见官方网站通知。";
}

//日常奖励   872:1,871:2|872,1;871,2|20100917:1*20,2*30;20101001:5*20,6*30
$uarr = array();
$now = date('Ymd');
$mempropsid = kdjlSafeMemValue($_pm['mem']->get('db_propsid'), array());
if(!is_array($mempropsid)) $mempropsid = array();
$u = $_pm['mysql'] -> getOneRecord('SELECT prize_every_day FROM player_ext WHERE uid = '.$uid);
$uarr = (is_array($u) && isset($u['prize_every_day'])) ? explode('|',$u['prize_every_day']) : array();
while(count($uarr) < 3) $uarr[] = 0;
$prize_str = (is_array($welcome) && isset($welcome['holiday_prize']['contents'])) ? $welcome['holiday_prize']['contents'] : '';
$arr = explode('|',$prize_str);
$arr = array_pad($arr, 3, '0');
$dayprizestr = '';
$weekprizestr = '';
$holidayprizestr = '';
if($arr[0] == 0){ // 日常奖励
	$dayprizeflag = 2;// 尚未开启
}else{
	if($uarr[0] < $now){
		$dayprizeflag = 1;//尚未领取
	}else{
		$dayprizeflag = 0;//已经领取
	}
	// 奖励物品
	$row = explode(',',$arr[0]);
	foreach($row as $rv){
		$res = explode(':',$rv);
		$pid = isset($res[0]) ? intval($res[0]) : 0;
		$num = isset($res[1]) ? intval($res[1]) : 0;
		if($pid < 1 || $num < 1 || !isset($mempropsid[$pid])) continue;
		$dayprizestr .= '<br /><img src="../images/ui/bag/'.intval($mempropsid[$pid]['varyname']).'.gif" border="0" width="20" height="20"/><span class="text02">'.kingModHtml($mempropsid[$pid]['name']).'x'.$num.'</span>';
	}
	if($dayprizestr !== '') $dayprizestr = substr($dayprizestr,6);
}

if($arr[1] == 0){ //周末奖励
	$weekprizeflag = 2;// 尚未开启
}else{
	$week = date('w');
	if($week != 0 && $week != 6){
		$weekprizeflag = 3;//不是周末
	}else{
		if($week == 0){// 星期日
			$yes = date("Ymd", strtotime("1 days ago"));//需要判断昨天也没有领取
			if($uarr[1] < $yes){
				$weekprizeflag = 1;//尚未领取
			}else{
				$weekprizeflag = 0;//已经领取
			}
		}else{
			if($uarr[1] < $now){
				$weekprizeflag = 1;//尚未领取
			}else{
				$weekprizeflag = 0;//已经领取
			}
		}
	}
	// 奖励物品
	$row = explode(',',$arr[1]);
	foreach($row as $rv){
		$res = explode(':',$rv);
		$pid = isset($res[0]) ? intval($res[0]) : 0;
		$num = isset($res[1]) ? intval($res[1]) : 0;
		if($pid < 1 || $num < 1 || !isset($mempropsid[$pid])) continue;
		$weekprizestr .= '<br /><img src="../images/ui/bag/'.intval($mempropsid[$pid]['varyname']).'.gif" border="0" width="20" height="20"/><span class="text02">'.kingModHtml($mempropsid[$pid]['name']).'x'.$num.'</span>';
	}
	if($weekprizestr !== '') $weekprizestr = substr($weekprizestr,6);
}
// 节假日奖励
$harr = explode(';',$arr[2]);//20100917:1*20,2*30;20101001:5*20,6*30
$holidayprizeflag = 2;
if(is_array($harr)){
	foreach($harr as $hv){
		$row = explode(':',$hv);
		if(isset($row[0]) && $now == $row[0]){
			if($uarr[2] == $row[0]){
				$holidayprizeflag = 0;//已经领取
			}else{
				$holidayprizeflag = 1;//尚未领取
			}
			// 奖励物品
			$rs = explode(',', isset($row[1]) ? $row[1] : '');
			foreach($rs as $rv){
				$res = explode('*',$rv);
				$pid = isset($res[0]) ? intval($res[0]) : 0;
				$num = isset($res[1]) ? intval($res[1]) : 0;
				if($pid < 1 || $num < 1 || !isset($mempropsid[$pid])) continue;
				$holidayprizestr .= '<br /><img src="../images/ui/bag/'.intval($mempropsid[$pid]['varyname']).'.gif" border="0" width="20" height="20"/><span class="text02">'.kingModHtml($mempropsid[$pid]['name']).'x'.$num.'</span>';
			}
			if($holidayprizestr !== '') $holidayprizestr = substr($holidayprizestr,6);
			break;
		}
	}

}else{
	$holidayprizeflag = 2;
}

$goldName = '金蛋券';
$silverName = '银蛋券';
$copperName = '铜蛋券';
$sql = " SELECT SUM(userbag.sums) AS sums,props.name
           FROM userbag,props
          WHERE userbag.uid = {$uid}
            AND props.name IN ('".$goldName."','".$silverName."','".$copperName."')
            AND userbag.pid = props.id
            AND userbag.sums > 0
            AND userbag.zbing = 0
            AND (userbag.cantrade IS NULL OR userbag.cantrade<>3)
       GROUP BY props.name";
$res_choose = $_pm['mysql'] -> getRecords($sql);
if( is_array($res_choose) )
{
	foreach( $res_choose as  $info )
	{
		if( $info['name'] == $goldName )
		{
			$golden_num = $info['sums'];
		}
		elseif( $info['name'] == $silverName )
		{
			$silver_num = $info['sums'];
		}
		elseif( $info['name'] == $copperName )
		{
			$copper_num = $info['sums'];
		}
	}
}
if( !isset($golden_num) )
{
	$golden_num = 0;
}
if( !isset($silver_num) )
{
	$silver_num = 0;
}
if( !isset($copper_num) )
{
	$copper_num = 0;
}
//@Load template.
$tn = $_game['template'] . 'tpl_king.html';
$king = '';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);

	$src = array(
				 '#word#',
				 '#active#',
				 '#oksum#',
				 '#anounce_msg#',
				 '#prestige#',
				 '#jprestige#',
				 '#dayprizestr#',
				 '#weekprizestr#',
				 '#holidayprizestr#',
				 '#dayprizeflag#',
				 '#weekprizeflag#',
				 '#holidayprizeflag#',
				 '#golden_num#',
				 '#silver_num#',
				 '#copper_num#'
				);
	$des = array(
				 $taskword,
				 $active,
				 $oksum,
				 $a	,
				 $prestige,
				 $jprestige,
				 $dayprizestr,
				 $weekprizestr,
				 $holidayprizestr,
				 $dayprizeflag,
				 $weekprizeflag,
				 $holidayprizeflag,
				 $golden_num,
				 $silver_num,
				 $copper_num
				);
	$king = str_replace($src, $des, $tpl);
}
// gzip echo. if maybe.
ob_start('ob_gzip');
echo $king;
ob_end_flush();
?>
