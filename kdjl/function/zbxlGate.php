<?php

require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');

secStart($_pm['mem']);
$get_prop1 = $_GET['props1'];
$get_prop2 = $_GET['props2'];
if( preg_match("/[^0-9]+/",$get_prop1) || empty($get_prop1) || preg_match("/[^0-9]+/",$get_prop2) || empty($get_prop2) )
{
	die("illegal");
}
$a = getLock($_SESSION['id']);
if(!is_array($a))
{
	realseLock();
	unLockItem($id);
	die('busy');
}
$sql = " SELECT  * FROM userbag WHERE uid = '".$_SESSION['id']."' AND (id = '".$get_prop1."' OR id = '".$get_prop2."')";
$user_info = $_pm['mysql'] -> getRecords($sql);
if( count($user_info) < 1 )
{
	realseLock();
	die("illegal");
}
$props	= unserialize($_pm['mem']->get(MEM_PROPS_KEY));
$props1_info = $_pm['mysql'] -> getOneRecord(" SELECT * FROM props,userbag WHERE userbag.id = '".$get_prop1."' AND userbag.pid = props.id AND sums > 0");
$props2_info = $_pm['mysql'] -> getOneRecord(" SELECT * FROM props,userbag WHERE userbag.id = '".$get_prop2."' AND userbag.pid = props.id AND sums > 0");
if( empty($props1_info) || empty($props2_info) )
{
	die("illegal");
}
if( $props1_info['varyname'] == 26 )
{
	if( empty($props2_info['F_item_hole_info']) )
	{
		realseLock();
		die("noneed");
	}
	if($props2_info['varyname'] != 9)
	{
		realseLock();
		die("error");
	}
	if($props1_info['effect'] != "clear")
	{
		die("error");
	}
	$_pm['mysql'] -> query(" UPDATE userbag SET sums = abs(sums-1) WHERE id = '".$get_prop1."'");
	$_pm['mysql'] -> query(" UPDATE userbag SET F_item_hole_info = '' WHERE id = '".$get_prop2."'");
	if( $props1_info['sums'] == 1 )
	{
		$_pm['mysql'] -> query(" DELETE FROM userbag WHERE id = '".$get_prop1."' AND sums < 1 AND psum < 1 AND bsum < 1 ");
	}
	realseLock();
	die("end");
}
else
{
	if( empty($props1_info['F_item_hole_info']) )
	{
		realseLock();
		die("noneed");
	}
	if($props1_info['varyname'] != 9)
	{
		realseLock();
		die("error");
	}
	if($props2_info['effect'] != "clear")
	{
		die("error");
	}
	$_pm['mysql'] -> query(" UPDATE userbag SET sums = abs(sums-1) WHERE id = '".$get_prop2."'");
	$_pm['mysql'] -> query(" UPDATE userbag SET F_item_hole_info = '' WHERE id = '".$get_prop1."'");
	if( $props2_info['sums'] == 1 )
	{
		$_pm['mysql'] -> query(" DELETE FROM userbag WHERE id = '".$get_prop2."' AND sums < 1 AND psum < 1 AND bsum < 1 ");
	}
	realseLock();
	die("end");
}
?>
