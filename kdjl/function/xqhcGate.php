<?php
/*
$deal 为处理类型 1为宝石合成 2为宝石镶嵌
*/
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');

$deal = 0;	//处理类型
$gonggao = 0;
ini_set('display_errors',true);
error_reporting(E_ALL);
secStart($_pm['mem']);
$get_prop1 = $_GET['props1'];
$get_prop2 = $_GET['props2'];
if( $_GET['bds'] != 0 )
{
	$use_bds = $_GET['bds'];
}
if( preg_match("/[^0-9]+/",$get_prop1) || empty($get_prop1) || preg_match("/[^0-9]+/",$get_prop2) || empty($get_prop2) )
{
	die("illegal");
}
if( isset($use_bds) && preg_match("/[^0-9]+/",$use_bds) )
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
$sql = " SELECT  * FROM userbag WHERE uid = '".$_SESSION['id']."' AND (id = '".$get_prop1."' OR id = '".$get_prop2."') ";
$user_info = $_pm['mysql'] -> getRecords($sql);
if( $get_prop1 != $get_prop2 )
{
	if( count($user_info) < 2 )
	{
		realseLock();
		die("illegal");
	}
}
else
{
	if( count($user_info) < 1 )
	{
		realseLock();
		die("illegal");
	}
}
if( isset($use_bds) )
{
	$sql = " SELECT  * FROM userbag,props WHERE userbag.uid = '".$_SESSION['id']."' AND userbag.id = '".$use_bds."' AND userbag.sums > 0 AND userbag.pid = props.id AND props.varyname = 27 ";
	$use_bds_is_true = $_pm['mysql'] -> getOneRecord($sql);
	if( empty($use_bds_is_true) )
	{
		die("illegal");
	}
	$bds_info = explode(':',$use_bds_is_true['effect']);
	if($bds_info[0] != 'bd')
	{
		die("illegal");
	}
	$bds_use_level = explode('-',$bds_info[1]);
}
$props	= unserialize($_pm['mem']->get(MEM_PROPS_KEY));
$props1_info = $_pm['mysql'] -> getOneRecord(" SELECT * FROM props,userbag WHERE userbag.id = '".$get_prop1."' AND userbag.pid = props.id AND sums > 0");
$props2_info = $_pm['mysql'] -> getOneRecord(" SELECT * FROM props,userbag WHERE userbag.id = '".$get_prop2."' AND userbag.pid = props.id AND sums > 0");
if( empty($props1_info) || empty($props2_info) )
{
	die("illegal");
}
$user = $_pm['user']->getUserById($_SESSION['id']);
$bag = $_pm['user']->getUserBagById($_SESSION['id']);	
$pam_system = new task;
if( $props1_info['varyname'] == 25 && $props2_info['varyname'] == 25 )
{
	preg_match_all("/[0-9]+/",$props1_info['name'],$gam_level);
	if( !isset($gam_level[0][0]) )
	{
		if( $props1_info['requires'] != '' && substr($props1_info['effect'],0,4) != 'full' )
		{
			die("illegal");
		}
	}
	if( isset($use_bds) )
	{
		if( empty($gam_level[0][0]) )
		{
			die("bsdnouse");
		}
		if( $gam_level[0][0] > $bds_use_level[1] || $gam_level[0][0] < $bds_use_level[0] )
		{
			die("bsdnouse");
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
		realseLock();
		die("nosame");
	}
	$mag_effect_info = explode(',',$props1_info['effect']);
	if( $mag_effect_info[0] == "full" )
	{
		realseLock();
		die("full");
	}
	if( $get_prop1 == $get_prop2 )
	{
		if( $props1_info['sums'] < 2 )
		{
			realseLock();
			die("noenough");
		}
		$type = 1;
	}
	else
	{
		if( $props1_info['sums'] < 1 || $props2_info['sums'] < 1 )
		{
			realseLock();
			die("noenough");	
		}
		$type = 2;
	}
	$gam_info = explode(',',$props1_info['effect']);
	$gam_hc_info = explode(':',$gam_info[0]);
	$luck_num = rand(1,100);
	if( isset($use_bds) )
	{
		$_pm['mysql'] -> query(" UPDATE userbag SET sums = abs(sums-1) WHERE id = '".$use_bds."'");
		if($use_bds_is_true['sums'] > 1 )
		{
			$bds_sy = $use_bds_is_true['sums'] - 1;
		}
		else
		{
			$_pm['mysql'] -> query(" DELETE FROM userbag WHERE id = '".$use_bds."' AND sums < 1 AND psum < 1 AND bsum < 1 ");
			$bds_sy = 0;
		}
	}
	if( $luck_num <= substr($gam_hc_info[1],0,-1) )	//合成成功
	{
		if( $type == 1 )
		{
			$_pm['mysql'] -> query(" UPDATE userbag SET sums = abs(sums-2) WHERE id = '".$get_prop1."'");
			if( $props1_info['sums'] == 2 )
			{
				$_pm['mysql'] -> query(" DELETE FROM userbag WHERE id = '".$get_prop1."' AND sums < 1 AND psum < 1 AND bsum < 1 ");
			}
		}
		if( $type == 2 )
		{
			$_pm['mysql'] -> query(" UPDATE userbag SET sums = abs(sums-1) WHERE id = '".$get_prop1."'");
			$_pm['mysql'] -> query(" UPDATE userbag SET sums = abs(sums-1) WHERE id = '".$get_prop2."'");
			if( $props1_info['sums'] == 1 || $props2_info['sums'] == 1 )
			{
				$_pm['mysql'] -> query(" DELETE FROM userbag WHERE id = '".$get_prop1."' AND sums < 1 AND psum < 1 AND bsum < 1 ");
				$_pm['mysql'] -> query(" DELETE FROM userbag WHERE id = '".$get_prop2."' AND sums < 1 AND psum < 1 AND bsum < 1 ");
			}
		}
		$bagid = $pam_system->saveGetPropsMore_return($gam_hc_info[2],1);	//发奖
		if( empty($bagid) )
		{
			$getid = $_pm['mysql'] -> getOneRecord(" SELECT id FROM userbag WHERE uid = '".$_SESSION['id']."' AND pid = '".$gam_hc_info[2]."'");
			$bagid = $getid['id'];
		}
		foreach( $props as $info )
		{
			if( $info['id'] == $gam_hc_info[2] )
			{
				$return_str = $info['img'].",ok,".$bagid.",".$gam_hc_info[2].",".$info['name'];
				if( $gonggao == '1' )
				{
					switch($info['propscolor'])
					{
						case 3 :
						{
							$color = "red";
							break;
						}
						case 5 :
						{
							$color = "#EDC028";
							break;
						}
						case 4 :
						{
							$color = "green";
							break;
						}
					}
					$word = "成功合成<span style=color:".$color."><b>【<a onclick=showTip3(".$bagid.",0,1,2) onmouseout=UnTip3() style=cursor:pointer;color:".$color.";>".$info['name']."</a>】</b></span>";
					$pam_system ->saveGword($word);
				} 
			}
		}
	}
	else											//合成失败
	{
		if( isset($use_bds) )
		{
			$_pm['mysql'] -> query(" UPDATE userbag SET sums = abs(sums-1) WHERE id = '".$get_prop2."'");
			if( $props2_info['sums'] == 1 )
			{
				$_pm['mysql'] -> query(" DELETE FROM userbag WHERE id = '".$get_prop2."' AND sums < 1 AND psum < 1 AND bsum < 1 ");
			}
			$return_str = "fail"."|".$bds_sy;
		}
		else
		{
			$_pm['mysql'] -> query(" UPDATE userbag SET sums = abs(sums-1) WHERE id = '".$get_prop2."'");
			$_pm['mysql'] -> query(" UPDATE userbag SET sums = abs(sums-1) WHERE id = '".$get_prop1."'");
			if( $props1_info['sums'] == 1 || $props2_info['sums'] == 1 )
			{
				$_pm['mysql'] -> query(" DELETE FROM userbag WHERE id = '".$get_prop1."' AND sums < 1 AND psum < 1 AND bsum < 1 ");
				$_pm['mysql'] -> query(" DELETE FROM userbag WHERE id = '".$get_prop2."' AND sums < 1 AND psum < 1 AND bsum < 1 ");
			}
			$return_str = "fail";
		}
	}
	realseLock();
	die($return_str);
	
}
if( ($props1_info['varyname'] == 25 && $props2_info['varyname'] == 9 ) || ($props1_info['varyname'] == 9 && $props2_info['varyname'] == 25 ) )
{
	$deal = 2;
	if( ($props1_info['varyname'] == 9 && isset($props1_info['F_item_hole_info']) && !empty($props1_info['F_item_hole_info'])) || ($props2_info['varyname'] == 9 && isset($props2_info['F_item_hole_info']) && !empty($props2_info['F_item_hole_info'])) )
	{
		realseLock();
		die("mosaicd");
	}

	$luck_num = rand(1,100);
	if( $props1_info['varyname'] == 25 )
	{
		if( !empty($props2_info['requires']) )
		{
			$requires_arr =  explode(',',$props2_info['requires']);
			foreach( $requires_arr as $requires_info )
			{
				$requires_val = explode(':',$requires_info);
				$mid_need = explode('|',$requires_val[1]);
				if( $requires_val[0] == "postion" && !in_array($props1_info['postion'],$mid_need) )
				{
					realseLock();
					die("badpostion");
				}
				if( $requires_val[0] == "color" && !in_array($props1_info['propscolor'],$mid_need) )
				{
					realseLock();
					die("badcolor");
				}
			}
		}
		$gam_info = explode(',',$props1_info['effect']);
		if( count($gam_info) < 2 )	//碎片不能镶嵌
		{
			die("nodeal");
		}
		$percentage = explode(':',$gam_info[1]);
		if( $percentage[0] != "xq" )
		{
			realseLock();
			die("dataerror");
		}
		$infomation = explode('|',$percentage[1]);
		$luck_number = rand(1,100);
		foreach( $infomation as $info )
		{
			$mid_arr = explode('_',$info);
			$num_between = explode('-',$mid_arr[2]);
			if( $luck_number >= $num_between[0] && $luck_number <= $num_between[1] )
			{
				$get_percentage_name = $mid_arr[0];
				$get_percentage_val = $mid_arr[1];
				break;
			}
		}
		$update = $get_percentage_name.":".$get_percentage_val;
		$_pm['mysql'] -> query(" UPDATE userbag SET sums = abs(sums-1) WHERE id = '".$get_prop1."'");
		if( $props1_info['sums'] == 1 )
		{
			$_pm['mysql'] -> query(" DELETE FROM userbag WHERE id = '".$get_prop1."' AND sums < 1 AND psum < 1 AND bsum < 1 ");
		}
		$_pm['mysql'] -> query(" UPDATE userbag SET F_item_hole_info = '".$update."' WHERE id = '".$get_prop2."'");
	}
	if( $props2_info['varyname'] == 25 )
	{
		if( !empty($props2_info['requires']) )
		{
			$requires_arr =  explode(',',$props2_info['requires']);
			foreach( $requires_arr as $requires_info )
			{
				$requires_val = explode(':',$requires_info);
				$mid_need = explode('|',$requires_val[1]);
				if( $requires_val[0] == "postion" && !in_array($props1_info['postion'],$mid_need) )
				{
					realseLock();
					die("badpostion");
				}
				if( $requires_val[0] == "color" && !in_array($props1_info['propscolor'],$mid_need) )
				{
					realseLock();
					die("badcolor");
				}
			}
		}
		$gam_info = explode(',',$props2_info['effect']);
		if( count($gam_info) < 2 )	//碎片不能镶嵌
		{
			die("nodeal");
		}
		$percentage = explode(':',$gam_info[1]);
		if( $percentage[0] != "xq" )
		{
			realseLock();
			die("dataerror");
		}
		$infomation = explode('|',$percentage[1]);
		$luck_number = rand(1,100);
		foreach( $infomation as $info )
		{
			$mid_arr = explode('_',$info);
			$num_between = explode('-',$mid_arr[2]);
			if( $luck_number >= $num_between[0] && $luck_number <= $num_between[1] )
			{
				$get_percentage_name = $mid_arr[0];
				$get_percentage_val = $mid_arr[1];
				break;
			}
		}
		$update = $get_percentage_name.":".$get_percentage_val;
		$_pm['mysql'] -> query(" UPDATE userbag SET sums = abs(sums-1) WHERE id = '".$get_prop2."'");
		if( $props2_info['sums'] == 1 )
		{
			$_pm['mysql'] -> query(" DELETE FROM userbag WHERE id = '".$get_prop2."' AND sums < 1 AND psum < 1 AND bsum < 1 ");
		}
		$_pm['mysql'] -> query(" UPDATE userbag SET F_item_hole_info = '".$update."' WHERE id = '".$get_prop1."'");
	}
	realseLock();
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
?>
