<?php
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');

$zbfjPendingLogId = 0;
$zbfjCommitted = false;
$zbfjTransactionActive = false;
$zbfjLockHeld = false;
function zbfjCleanupPendingLog()
{
	global $_pm,$zbfjPendingLogId,$zbfjCommitted;
	if(!$zbfjCommitted && intval($zbfjPendingLogId) > 0 && isset($_pm['mysql']))
	{
		$_pm['mysql']->query('DELETE FROM gamelog WHERE id='.intval($zbfjPendingLogId).' AND vary=22');
	}
	$zbfjPendingLogId = 0;
}

function zbfjShutdown()
{
	global $_pm,$zbfjCommitted,$zbfjTransactionActive,$zbfjLockHeld;
	if(!$zbfjCommitted && $zbfjTransactionActive && isset($_pm['mysql'])) $_pm['mysql']->query('ROLLBACK');
	$zbfjTransactionActive = false;
	zbfjCleanupPendingLog();
	if($zbfjLockHeld && function_exists('realseLock')) realseLock();
	$zbfjLockHeld = false;
}

function zbfjFail($message)
{
	global $_pm,$zbfjTransactionActive,$zbfjLockHeld;
	if($zbfjTransactionActive) $_pm['mysql']->query('ROLLBACK');
	$zbfjTransactionActive = false;
	zbfjCleanupPendingLog();
	if($zbfjLockHeld && function_exists('realseLock')) realseLock();
	$zbfjLockHeld = false;
	die($message);
}

function zbfjImage($value)
{
	return kdjlPropsImageName($value);
}

secStart($_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid <= 0)
{
	die("illegal");
}
$fj_prop = (isset($_GET['props']) && !is_array($_GET['props'])) ? $_GET['props'] : '';
if( preg_match("/[^0-9]+/",$fj_prop) || empty($fj_prop) )
{
	die("illegal");
}
$fj_prop = intval($fj_prop);
$a = getLock($uid);
if(!is_array($a))
{
	die('busy');
}
$zbfjLockHeld = true;
$zbfjTransactionActive = true;
register_shutdown_function('zbfjShutdown');
$lockedPlayer = $_pm['mysql']->getOneRecord('SELECT maxbag FROM player WHERE id='.$uid.' FOR UPDATE');
$lockedBags = $_pm['mysql']->getRecords('SELECT id FROM userbag WHERE uid='.$uid.' FOR UPDATE');
if(!is_array($lockedPlayer) || !is_array($lockedBags)) zbfjFail('busy');
$sql = " SELECT * FROM userbag WHERE uid = '".$uid."' AND id = '".$fj_prop."' AND sums>0 AND zbing=0 AND (cantrade IS NULL OR cantrade<>3) FOR UPDATE";
$user_info = $_pm['mysql'] -> getRecords($sql);
if(!is_array($user_info)) $user_info = array();
if( count($user_info) != 1 )
{
	zbfjFail("illegal");
}
if(isset($user_info[0]['cantrade']) && intval($user_info[0]['cantrade']) == 3)
{
	zbfjFail("illegal");
}
global $mem_props;
if(is_array($mem_props))
{
	$props = $mem_props;
}
else
{
	$props	= kdjlSafeMemValue($_pm['mem']->get(MEM_PROPS_KEY), array());
}
$db_welcome = kdjlSafeMemValue($_pm['mem']->get('db_welcome'), array());
if( !is_array($props) || !is_array($db_welcome) )
{
	zbfjFail("memcacheerror");
}
$allow_postion = array();
foreach( $db_welcome as $info )
{
	if( $info['code'] == "biodegradable_equipment" )
	{
		$allow_postion = explode(',',$info['contents']);
		break;
	}
}
if(empty($allow_postion))
{
	zbfjFail("memcacheerror");
}
$return_str = 'illegal';
$get_item_type = 0;
$num_result = 0;
$rewardInfo = false;
// 每日次数以分解日志为准，避免 memcache 重启后恢复成 5 次。
$dayStart = strtotime(date('Y-m-d').' 00:00:00');
$dayEnd = $dayStart + 86400;
$usedToday = $_pm['mysql']->getOneRecord("SELECT COUNT(*) AS cnt FROM gamelog WHERE seller='".$uid."' AND buyer='".$uid."' AND vary=22 AND ptime>=".$dayStart." AND ptime<".$dayEnd);
if(!is_array($usedToday) || !isset($usedToday['cnt']))
{
	zbfjFail("busy");
}
$usedToday = intval($usedToday['cnt']);
if($usedToday >= 5)
{
	zbfjFail("nofjnum");
}

foreach( $props as $info )
{
	if( $info['id'] == $user_info[0]['pid'] )
	{
		if(!isset($info['postion'])) $info['postion'] = '';
		if(!isset($info['propscolor'])) $info['propscolor'] = '';
		if(!isset($info['name'])) $info['name'] = '';
		if( !in_array($info['postion'],$allow_postion) )
		{
			zbfjFail("illegal");
		}
		$rate_info = array();
		foreach( $db_welcome as $info_wel )
		{
			if( $info_wel['code'] == "fj_".$info['propscolor']."_success_rate" )
			{
				$rate_info = explode(',',$info_wel['contents']);
				break;
			}
		}
		if(empty($rate_info))
		{
			zbfjFail("memcacheerror");
		}
		$item_ob = array();
		foreach( $rate_info as $content )
		{
			$arr_mid = explode(':',trim($content));
			if(count($arr_mid) != 3) zbfjFail("memcacheerror");
			$item_id = intval($arr_mid[0]);
			$numRange = explode('-',$arr_mid[1]);
			$luckRange = explode('-',$arr_mid[2]);
			if($item_id < 1 || isset($item_ob[$item_id]) || count($numRange) != 2 || count($luckRange) != 2) zbfjFail("memcacheerror");
			$numMin = min(intval($numRange[0]),intval($numRange[1]));
			$numMax = max(intval($numRange[0]),intval($numRange[1]));
			$luckMin = intval($luckRange[0]);
			$luckMax = intval($luckRange[1]);
			if($numMin < 1 || $luckMin < 1 || $luckMax > 100 || $luckMin > $luckMax) zbfjFail("memcacheerror");
			$item_ob[$item_id] = array($numMin.'-'.$numMax,$luckMin.'-'.$luckMax);
		}
		if(empty($item_ob))
		{
			zbfjFail("memcacheerror");
		}
		$luck_num = rand(1,100);
		foreach ( $item_ob as $key => $val )
		{
			$interval = explode('-',$val[1]);
			if(count($interval) < 2) continue;
			if( $luck_num >= $interval[0] && $luck_num <= $interval[1] )
			{
				$get_item_type =  $key;
				break;
			}
		}
		if($get_item_type > 0)
		{
			$num_check = explode('-',$item_ob[$get_item_type][0]);
			if(count($num_check) < 2)
			{
				zbfjFail("memcacheerror");
			}
			foreach($props as $rewardPropsRow)
			{
				if(is_array($rewardPropsRow) && isset($rewardPropsRow['id']) && intval($rewardPropsRow['id']) == $get_item_type)
				{
					$rewardInfo = $rewardPropsRow;
					break;
				}
			}
			if(!is_array($rewardInfo) || !isset($rewardInfo['name'], $rewardInfo['img'], $rewardInfo['varyname']))
			{
				zbfjFail('memcacheerror');
			}
		}
		$equipmentDeleted = $_pm['mysql'] -> query("DELETE FROM userbag WHERE id='".$fj_prop."' AND uid='".$uid."' AND sums=1 AND zbing=0 AND (cantrade IS NULL OR cantrade<>3)");
		if(!$equipmentDeleted || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
			zbfjFail('illegal');
		}
		$time = time();
		if( $get_item_type < 1 )	//fail
		{
			$return_str = 'fail';
			$massage = "装备分解:失去物品id:".$fj_prop.",物品名称:".$info['name'].",分解失败";
			$massageSql = $_pm['mysql']->escape($massage);
			$logSaved = $_pm['mysql'] -> query(" INSERT INTO gamelog (ptime,buyer,seller,pnote,vary) VALUES($time,'".$uid."','".$uid."','".$massageSql."','22')");
			if(!$logSaved || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
			{
				zbfjFail('busy');
			}
			$zbfjPendingLogId = intval($_pm['mysql']->last_id());
			if($zbfjPendingLogId < 1) zbfjFail('busy');
		}
		else
		{
			$num = explode('-',$item_ob[$get_item_type][0]);
			$num_min = intval($num[0]);
			$num_max = intval($num[1]);
			if($num_max < $num_min)
			{
				$num_mid = $num_min;
				$num_min = $num_max;
				$num_max = $num_mid;
			}
			if($num_min < 1 || $num_max < 1)
			{
				zbfjFail("memcacheerror");
			}
			$num_result = rand($num_min,$num_max);
			//database deal
			$massage = "装备分解:失去物品id:".$fj_prop.",物品名称:".$info['name'].",得到物品:".$get_item_type.",得到数量:".$num_result;
			$get_gem = new task;
			$_pm['mem']->del(MEM_USER_KEY);
			$giveResult = $get_gem->saveGetPropsMore_return($get_item_type,$num_result,0,$uid);
			if($giveResult === '200'){
				zbfjFail('bagfull');
			}
			if(!is_numeric($giveResult) || intval($giveResult) < 1){
				zbfjFail('busy');
			}
			$getBagId = intval($giveResult);
			$massageSql = $_pm['mysql']->escape($massage);
			$logSaved = $_pm['mysql'] -> query(" INSERT INTO gamelog (ptime,buyer,seller,pnote,vary) VALUES($time,'".$uid."','".$uid."','".$massageSql."','22')");
			if(!$logSaved || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
			{
				zbfjFail('busy');
			}
			$zbfjPendingLogId = intval($_pm['mysql']->last_id());
			if($zbfjPendingLogId < 1) zbfjFail('busy');

		}
		break;
	}
}
if($return_str == 'illegal' && $get_item_type < 1)
{
	zbfjFail("illegal");
}
if(!$_pm['mysql']->query('COMMIT'))
{
	zbfjFail('busy');
}
$zbfjTransactionActive = false;
$zbfjCommitted = true;
$_pm['mem']->del(MEM_USERBAG_KEY);
if($get_item_type > 0)
{
	$return_str = $rewardInfo['name'].','.intval($num_result).','.zbfjImage($rewardInfo['img']).','.
		intval($getBagId).','.intval($get_item_type).','.intval($rewardInfo['varyname']);
}

echo $return_str;
if($zbfjLockHeld && function_exists('realseLock')) realseLock();
$zbfjLockHeld = false;
?>
