<?php

require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');

$zbxlTransactionActive = false;
$zbxlLockHeld = false;

function zbxlShutdown()
{
	global $_pm, $zbxlTransactionActive, $zbxlLockHeld;
	$error = error_get_last();
	if (!is_array($error) || !in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true))
	{
		return;
	}
	if ($zbxlTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$zbxlTransactionActive = false;
	}
	if ($zbxlLockHeld && function_exists('realseLock'))
	{
		realseLock();
		$zbxlLockHeld = false;
	}
}
register_shutdown_function('zbxlShutdown');

function zbxlFail($message)
{
	global $_pm, $zbxlTransactionActive, $zbxlLockHeld;
	if($zbxlTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$zbxlTransactionActive = false;
	}
	if($zbxlLockHeld && function_exists('realseLock'))
	{
		realseLock();
		$zbxlLockHeld = false;
	}
	die($message);
}

function zbxlConsumeBag($bagId, $uid, $num)
{
	global $_pm;
	$bagId = intval($bagId);
	$uid = intval($uid);
	$num = intval($num);
	if($bagId <= 0 || $uid <= 0 || $num <= 0)
	{
		return false;
	}
	$sql = " UPDATE userbag SET sums = sums-".$num." WHERE id = '".$bagId."' AND uid = '".$uid."' AND sums >= ".$num." AND zbing=0 AND (cantrade IS NULL OR cantrade<>3)";
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
	{
		return false;
	}
	if(!$_pm['mysql'] -> query(" DELETE FROM userbag WHERE id = '".$bagId."' AND uid = '".$uid."' AND sums < 1 AND psum < 1 AND bsum < 1 AND pyb=0 AND zbing=0 AND (cantrade IS NULL OR cantrade<>3) "))
	{
		return false;
	}
	return true;
}

function zbxlClearHoleInfo($bagId, $uid)
{
	global $_pm;
	$bagId = intval($bagId);
	$uid = intval($uid);
	$sql = " UPDATE userbag SET F_item_hole_info = '' WHERE id = '".$bagId."' AND uid = '".$uid."' AND F_item_hole_info <> '' AND (cantrade IS NULL OR cantrade<>3)";
	return $_pm['mysql']->query($sql) && mysql_affected_rows($_pm['mysql']->getConn()) == 1;
}

function zbxlInvalidateEquipmentCache($uid, $petId)
{
	global $_pm;
	$uid = intval($uid);
	$petId = intval($petId);
	if($uid < 1 || $petId < 1) return;
	$_pm['mem']->del('format_user_zhuangbei_'.$petId);
	$_pm['mem']->set(array('k'=>'User_bb_equip_changed_'.$petId.'_'.$uid,'v'=>1));
	$_pm['mem']->del('User_bb_equip_info_a_'.$petId.'_'.$uid);
	$_pm['mem']->del('User_bb_equip_info_b_'.$petId.'_'.$uid);
}

secStart($_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
$get_prop1 = (isset($_GET['props1']) && !is_array($_GET['props1'])) ? $_GET['props1'] : '';
$get_prop2 = (isset($_GET['props2']) && !is_array($_GET['props2'])) ? $_GET['props2'] : '';
if( preg_match("/[^0-9]+/",$get_prop1) || empty($get_prop1) || preg_match("/[^0-9]+/",$get_prop2) || empty($get_prop2) )
{
	die("illegal");
}
$get_prop1 = intval($get_prop1);
$get_prop2 = intval($get_prop2);
if( $uid <= 0 )
{
	die("illegal");
}
$a = getLock($uid);
if(!is_array($a))
{
	realseLock();
	die('busy');
}
$zbxlLockHeld = true;
$zbxlTransactionActive = true;
$sql = " SELECT  * FROM userbag WHERE uid = '".$uid."' AND (id = '".$get_prop1."' OR id = '".$get_prop2."') AND (cantrade IS NULL OR cantrade<>3) FOR UPDATE";
$user_info = $_pm['mysql'] -> getRecords($sql);
if(!is_array($user_info)) $user_info = array();
if( ($get_prop1 != $get_prop2 && count($user_info) < 2) || ($get_prop1 == $get_prop2 && count($user_info) < 1) )
{
	zbxlFail("illegal");
}
$props	= kdjlSafeMemValue($_pm['mem']->get(MEM_PROPS_KEY), array());
$props1_info = $_pm['mysql'] -> getOneRecord(" SELECT * FROM props,userbag WHERE userbag.uid = '".$uid."' AND userbag.id = '".$get_prop1."' AND userbag.pid = props.id AND userbag.sums > 0 AND userbag.zbing=0 AND (userbag.cantrade IS NULL OR userbag.cantrade<>3)");
$props2_info = $_pm['mysql'] -> getOneRecord(" SELECT * FROM props,userbag WHERE userbag.uid = '".$uid."' AND userbag.id = '".$get_prop2."' AND userbag.pid = props.id AND userbag.sums > 0 AND userbag.zbing=0 AND (userbag.cantrade IS NULL OR userbag.cantrade<>3)");
if( empty($props1_info) || empty($props2_info) )
{
	zbxlFail("illegal");
}
if( $props1_info['varyname'] == 26 )
{
	if( empty($props2_info['F_item_hole_info']) )
	{
		zbxlFail("noneed");
	}
	if($props2_info['varyname'] != 9)
	{
		zbxlFail("error");
	}
	if($props1_info['effect'] != "clear")
	{
		zbxlFail("error");
	}
	if(!zbxlConsumeBag($get_prop1, $uid, 1))
	{
		zbxlFail("noenough");
	}
	if(!zbxlClearHoleInfo($get_prop2, $uid))
	{
		zbxlFail("busy");
	}
	if(!$_pm['mysql']->query('COMMIT'))
	{
		zbxlFail("busy");
	}
	$zbxlTransactionActive = false;
	$_pm['mem']->del(MEM_USERBAG_KEY);
	zbxlInvalidateEquipmentCache($uid, intval($props2_info['zbpets']));
	realseLock();
	$zbxlLockHeld = false;
	die("end");
}
else
{
	if( empty($props1_info['F_item_hole_info']) )
	{
		zbxlFail("noneed");
	}
	if($props1_info['varyname'] != 9)
	{
		zbxlFail("error");
	}
	if($props2_info['effect'] != "clear")
	{
		zbxlFail("error");
	}
	if(!zbxlConsumeBag($get_prop2, $uid, 1))
	{
		zbxlFail("noenough");
	}
	if(!zbxlClearHoleInfo($get_prop1, $uid))
	{
		zbxlFail("busy");
	}
	if(!$_pm['mysql']->query('COMMIT'))
	{
		zbxlFail("busy");
	}
	$zbxlTransactionActive = false;
	$_pm['mem']->del(MEM_USERBAG_KEY);
	zbxlInvalidateEquipmentCache($uid, intval($props1_info['zbpets']));
	realseLock();
	$zbxlLockHeld = false;
	die("end");
}
?>
