<?php
/*
$deal 为处理类型 1为宝石合成 2为宝石镶嵌
*/
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');

$xqhcTransactionActive = false;
$xqhcLockHeld = false;

function xqhcShutdown()
{
	global $_pm, $xqhcTransactionActive, $xqhcLockHeld;
	$error = error_get_last();
	if (!is_array($error) || !in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true))
	{
		return;
	}
	if ($xqhcTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$xqhcTransactionActive = false;
	}
	if ($xqhcLockHeld && function_exists('realseLock'))
	{
		realseLock();
		$xqhcLockHeld = false;
	}
}
register_shutdown_function('xqhcShutdown');

function xqhcFail($message)
{
	global $_pm, $xqhcTransactionActive, $xqhcLockHeld;
	if($xqhcTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$xqhcTransactionActive = false;
	}
	if($xqhcLockHeld && function_exists('realseLock'))
	{
		realseLock();
		$xqhcLockHeld = false;
	}
	die($message);
}

function xqhcConsumeBag($bagId, $uid, $num)
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

function xqhcSetHoleInfo($bagId, $uid, $value)
{
	global $_pm;
	$bagId = intval($bagId);
	$uid = intval($uid);
	$value = mysql_real_escape_string($value, $_pm['mysql']->getConn());
	$sql = " UPDATE userbag SET F_item_hole_info = '".$value."' WHERE id = '".$bagId."' AND uid = '".$uid."' AND (F_item_hole_info = '' OR F_item_hole_info IS NULL)";
	return $_pm['mysql']->query($sql) && mysql_affected_rows($_pm['mysql']->getConn()) == 1;
}

function xqhcImage($value)
{
	return kdjlPropsImageName($value);
}

function xqhcInvalidateEquipmentCache($uid, $petId)
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

$deal = 0;	//处理类型
$gonggao = 0;
$xqhcPendingWord = '';
ini_set('display_errors',false);
error_reporting(0);
secStart($_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
$get_prop1 = (isset($_GET['props1']) && !is_array($_GET['props1'])) ? $_GET['props1'] : '';
$get_prop2 = (isset($_GET['props2']) && !is_array($_GET['props2'])) ? $_GET['props2'] : '';
if( isset($_GET['bds']) && !is_array($_GET['bds']) && $_GET['bds'] != 0 )
{
	if( preg_match("/[^0-9]+/",$_GET['bds']) )
	{
		die("illegal");
	}
	$use_bds = (!is_array($_GET['bds'])) ? intval($_GET['bds']) : 0;
}
if( preg_match("/[^0-9]+/",$get_prop1) || empty($get_prop1) || preg_match("/[^0-9]+/",$get_prop2) || empty($get_prop2) )
{
	die("illegal");
}
if( isset($use_bds) && $use_bds <= 0 )
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
$xqhcLockHeld = true;
$xqhcTransactionActive = true;
$sql = " SELECT  * FROM userbag WHERE uid = '".$uid."' AND (id = '".$get_prop1."' OR id = '".$get_prop2."') AND (cantrade IS NULL OR cantrade<>3) FOR UPDATE";
$user_info = $_pm['mysql'] -> getRecords($sql);
if(!is_array($user_info)) $user_info = array();
if( $get_prop1 != $get_prop2 )
{
	if( count($user_info) < 2 )
	{
		xqhcFail("illegal");
	}
}
else
{
	if( count($user_info) < 1 )
	{
		xqhcFail("illegal");
	}
}
if( isset($use_bds) )
{
	$sql = " SELECT  * FROM userbag,props WHERE userbag.uid = '".$uid."' AND userbag.id = '".$use_bds."' AND userbag.sums > 0 AND userbag.zbing=0 AND (userbag.cantrade IS NULL OR userbag.cantrade<>3) AND userbag.pid = props.id AND props.varyname = 27 FOR UPDATE";
	$use_bds_is_true = $_pm['mysql'] -> getOneRecord($sql);
	if( empty($use_bds_is_true) )
	{
		xqhcFail("illegal");
	}
	$bds_info = explode(':',$use_bds_is_true['effect']);
	if(count($bds_info) < 2 || $bds_info[0] != 'bd')
	{
		xqhcFail("illegal");
	}
	$bds_use_level = explode('-',$bds_info[1]);
	if(count($bds_use_level) < 2)
	{
		xqhcFail("illegal");
	}
}
$props1_info = $_pm['mysql'] -> getOneRecord(" SELECT * FROM props,userbag WHERE userbag.uid = '".$uid."' AND userbag.id = '".$get_prop1."' AND userbag.pid = props.id AND userbag.sums > 0 AND userbag.zbing=0 AND (userbag.cantrade IS NULL OR userbag.cantrade<>3)");
$props2_info = $_pm['mysql'] -> getOneRecord(" SELECT * FROM props,userbag WHERE userbag.uid = '".$uid."' AND userbag.id = '".$get_prop2."' AND userbag.pid = props.id AND userbag.sums > 0 AND userbag.zbing=0 AND (userbag.cantrade IS NULL OR userbag.cantrade<>3)");
if( empty($props1_info) || empty($props2_info) )
{
	xqhcFail("illegal");
}
$xqhcEquippedPetId = 0;
if(intval($props1_info['varyname']) == 9) $xqhcEquippedPetId = intval($props1_info['zbpets']);
if(intval($props2_info['varyname']) == 9) $xqhcEquippedPetId = intval($props2_info['zbpets']);
$user = $_pm['user']->getUserById($uid);
$bag = $_pm['user']->getUserBagById($uid);
$pam_system = new task;
if( $props1_info['varyname'] == 25 && $props2_info['varyname'] == 25 )
{
	preg_match_all("/[0-9]+/",$props1_info['name'],$gam_level);
	if( !isset($gam_level[0][0]) )
	{
		if( $props1_info['requires'] != '' && substr($props1_info['effect'],0,4) != 'full' )
		{
			xqhcFail("illegal");
		}
	}
	if( isset($use_bds) )
	{
		if( empty($gam_level[0][0]) )
		{
			xqhcFail("bsdnouse");
		}
		if( $gam_level[0][0] > $bds_use_level[1] || $gam_level[0][0] < $bds_use_level[0] )
		{
			xqhcFail("bsdnouse");
		}
	}
	if( isset($gam_level[0][0]) && $gam_level[0][0] >= 3 )
	{
		$gonggao = 1;
	}
	else
	{
		$gonggao = 0;
	}
	$deal = 1;
	if($props1_info['pid'] != $props2_info['pid'] )
	{
		xqhcFail("nosame");
	}
	$mag_effect_info = explode(',',$props1_info['effect']);
	if( $mag_effect_info[0] == "full" )
	{
		xqhcFail("full");
	}
	if( $get_prop1 == $get_prop2 )
	{
		if( $props1_info['sums'] < 2 )
		{
			xqhcFail("noenough");
		}
		$type = 1;
	}
	else
	{
		if( $props1_info['sums'] < 1 || $props2_info['sums'] < 1 )
		{
			xqhcFail("noenough");
		}
		$type = 2;
	}
	$gam_info = explode(',',$props1_info['effect']);
	$gam_hc_info = explode(':',$gam_info[0]);
	if(count($gam_hc_info) < 3)
	{
		xqhcFail("dataerror");
	}
	$successRateText = str_replace('%','',$gam_hc_info[1]);
	$targetPid = intval($gam_hc_info[2]);
	if($successRateText === '' || !ctype_digit($successRateText) || intval($successRateText) > 100 || $targetPid < 1)
	{
		xqhcFail("dataerror");
	}
	$targetProps = $_pm['mysql']->getOneRecord("SELECT id,name,img,propscolor FROM props WHERE id=".$targetPid);
	if(!is_array($targetProps))
	{
		xqhcFail("dataerror");
	}
	$luck_num = rand(1,100);
	if( isset($use_bds) )
	{
		if(!xqhcConsumeBag($use_bds, $uid, 1))
		{
			xqhcFail("noenough");
		}
		$bds_sy = ($use_bds_is_true['sums'] > 1) ? $use_bds_is_true['sums'] - 1 : 0;
	}
	$success_rate = intval($successRateText);
	if( $luck_num <= $success_rate )	//合成成功
	{
		if( $type == 1 )
		{
			if(!xqhcConsumeBag($get_prop1, $uid, 2))
			{
				xqhcFail("noenough");
			}
		}
		if( $type == 2 )
		{
			if(!xqhcConsumeBag($get_prop1, $uid, 1) || !xqhcConsumeBag($get_prop2, $uid, 1))
			{
				xqhcFail("noenough");
			}
		}
		$bagid = $pam_system->saveGetPropsMore_return($targetPid,1);	//发奖
		if($bagid === '200'){
			xqhcFail('bagfull');
		}
		if($bagid === false){
			xqhcFail('busy');
		}
		$return_str = xqhcImage(isset($targetProps['img']) ? $targetProps['img'] : '').",ok,".intval($bagid).",".$targetPid.",".$targetProps['name'];
		if( $gonggao == '1' )
		{
			$colorMap = array(3 => 'red', 4 => 'green', 5 => '#EDC028');
			$propsColor = intval($targetProps['propscolor']);
			$color = isset($colorMap[$propsColor]) ? $colorMap[$propsColor] : 'green';
			$xqhcPendingWord = "成功合成<span style=color:".$color."><b>【<a onclick=showTip3(".$bagid.",0,1,2) onmouseout=UnTip3() style=cursor:pointer;color:".$color.";>".$targetProps['name']."</a>】</b></span>";
		}
	}
	else											//合成失败
	{
		if( isset($use_bds) )
		{
			if(!xqhcConsumeBag($get_prop2, $uid, 1))
			{
				xqhcFail("noenough");
			}
			$return_str = "fail"."|".$bds_sy;
		}
		else
		{
			if(!xqhcConsumeBag($get_prop2, $uid, 1) || !xqhcConsumeBag($get_prop1, $uid, 1))
			{
				xqhcFail("noenough");
			}
			$return_str = "fail";
		}
	}
	if(!$_pm['mysql']->query('COMMIT'))
	{
		xqhcFail('busy');
	}
	$xqhcTransactionActive = false;
	$_pm['mem']->del(MEM_USERBAG_KEY);
	xqhcInvalidateEquipmentCache($uid, $xqhcEquippedPetId);
	realseLock();
	$xqhcLockHeld = false;
	if($xqhcPendingWord !== '')
	{
		$pam_system->saveGword($xqhcPendingWord);
	}
	die($return_str);

}
if( ($props1_info['varyname'] == 25 && $props2_info['varyname'] == 9 ) || ($props1_info['varyname'] == 9 && $props2_info['varyname'] == 25 ) )
{
	$deal = 2;
	if( ($props1_info['varyname'] == 9 && isset($props1_info['F_item_hole_info']) && !empty($props1_info['F_item_hole_info'])) || ($props2_info['varyname'] == 9 && isset($props2_info['F_item_hole_info']) && !empty($props2_info['F_item_hole_info'])) )
	{
		xqhcFail("mosaicd");
	}

	$luck_num = rand(1,100);
	if( $props1_info['varyname'] == 25 )
	{
		if( !empty($props1_info['requires']) )
		{
			$requires_arr =  explode(',',$props1_info['requires']);
			foreach( $requires_arr as $requires_info )
			{
				$requires_val = explode(':',$requires_info);
				if(count($requires_val) < 2) continue;
				$mid_need = explode('|',$requires_val[1]);
				if( $requires_val[0] == "postion" && !in_array($props2_info['postion'],$mid_need) )
				{
					xqhcFail("badpostion");
				}
				if( $requires_val[0] == "color" && !in_array($props2_info['propscolor'],$mid_need) )
				{
					xqhcFail("badcolor");
				}
			}
		}
		$gam_info = explode(',',$props1_info['effect']);
		if( count($gam_info) < 2 )	//碎片不能镶嵌
		{
			xqhcFail("nodeal");
		}
		$percentage = explode(':',$gam_info[1]);
		if( count($percentage) < 2 || $percentage[0] != "xq" )
		{
			xqhcFail("dataerror");
		}
		$infomation = explode('|',$percentage[1]);
		$luck_number = rand(1,100);
		foreach( $infomation as $info )
		{
			$mid_arr = explode('_',$info);
			if(count($mid_arr) < 3) continue;
			$num_between = explode('-',$mid_arr[2]);
			if(count($num_between) < 2) continue;
			if( $luck_number >= $num_between[0] && $luck_number <= $num_between[1] )
			{
				$get_percentage_name = $mid_arr[0];
				$get_percentage_val = $mid_arr[1];
				break;
			}
		}
		if(!isset($get_percentage_name) || !isset($get_percentage_val))
		{
			xqhcFail("dataerror");
		}
		$update = $get_percentage_name.":".$get_percentage_val;
		if(!xqhcConsumeBag($get_prop1, $uid, 1))
		{
			xqhcFail("noenough");
		}
		if(!xqhcSetHoleInfo($get_prop2, $uid, $update))
		{
			xqhcFail("busy");
		}
	}
	if( $props2_info['varyname'] == 25 )
	{
		if( !empty($props2_info['requires']) )
		{
			$requires_arr =  explode(',',$props2_info['requires']);
			foreach( $requires_arr as $requires_info )
			{
				$requires_val = explode(':',$requires_info);
				if(count($requires_val) < 2) continue;
				$mid_need = explode('|',$requires_val[1]);
				if( $requires_val[0] == "postion" && !in_array($props1_info['postion'],$mid_need) )
				{
					xqhcFail("badpostion");
				}
				if( $requires_val[0] == "color" && !in_array($props1_info['propscolor'],$mid_need) )
				{
					xqhcFail("badcolor");
				}
			}
		}
		$gam_info = explode(',',$props2_info['effect']);
		if( count($gam_info) < 2 )	//碎片不能镶嵌
		{
			xqhcFail("nodeal");
		}
		$percentage = explode(':',$gam_info[1]);
		if( count($percentage) < 2 || $percentage[0] != "xq" )
		{
			xqhcFail("dataerror");
		}
		$infomation = explode('|',$percentage[1]);
		$luck_number = rand(1,100);
		foreach( $infomation as $info )
		{
			$mid_arr = explode('_',$info);
			if(count($mid_arr) < 3) continue;
			$num_between = explode('-',$mid_arr[2]);
			if(count($num_between) < 2) continue;
			if( $luck_number >= $num_between[0] && $luck_number <= $num_between[1] )
			{
				$get_percentage_name = $mid_arr[0];
				$get_percentage_val = $mid_arr[1];
				break;
			}
		}
		if(!isset($get_percentage_name) || !isset($get_percentage_val))
		{
			xqhcFail("dataerror");
		}
		$update = $get_percentage_name.":".$get_percentage_val;
		if(!xqhcConsumeBag($get_prop2, $uid, 1))
		{
			xqhcFail("noenough");
		}
		if(!xqhcSetHoleInfo($get_prop1, $uid, $update))
		{
			xqhcFail("busy");
		}
	}
	if(!$_pm['mysql']->query('COMMIT'))
	{
		xqhcFail('busy');
	}
	$xqhcTransactionActive = false;
	$_pm['mem']->del(MEM_USERBAG_KEY);
	xqhcInvalidateEquipmentCache($uid, $xqhcEquippedPetId);
	realseLock();
	$xqhcLockHeld = false;
	$xq_return = $get_percentage_name.":".$get_percentage_val;
	switch($get_percentage_name)
	{
		case "ac" :
		{
			$xq_return = "攻击增加:".$get_percentage_val;
			break;
		}
		case "crit" :
		{
			$xq_return = "会心一击发动几率增加:".$get_percentage_val;
			break;
		}
		case "shjs" :
		{
			$xq_return = "伤害加深:".$get_percentage_val;
			break;
		}
		case "dxsh" :
		{
			$xq_return = "伤害抵消:".$get_percentage_val;
			break;
		}
		case "hp" :
		{
			$xq_return = "HP上限增加:".$get_percentage_val;
			break;
		}
		case "mp" :
		{
			$xq_return = "MP上限增加:".$get_percentage_val;
			break;
		}
		case "mc" :
		{
			$xq_return = "防御增加:".$get_percentage_val;
			break;
		}
		case "hits" :
		{
			$xq_return = "命中增加:".$get_percentage_val;
			break;
		}
		case "miss" :
		{
			$xq_return = "闪避增加:".$get_percentage_val;
			break;
		}
		case "szmp" :
		{
			$xq_return = "伤害的".$get_percentage_val."转化为mp";
			break;
		}
		case "sdmp" :
		{
			$xq_return = "伤害的".$get_percentage_val."以mp抵消";
			break;
		}
		case "speed" :
		{
			$xq_return =  "攻击速度:".$get_percentage_val;
			break;
		}
		case "hitsmp" :
		{
			$xq_return =  "命中吸取伤害的".$get_percentage_val."转化为自身MP";
			break;
		}
		case "hitshp" :
		{
			$xq_return =  "命中吸取伤害的".$get_percentage_val."转化为自身HP";
			break;
		}
	}
	die("xq,".$xq_return);
}
xqhcFail("nodeal");
?>
