<?php
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');
secStart($_pm['mem']);
$fj_prop = $_GET['props'];
if( preg_match("/[^0-9]+/",$fj_prop) || empty($fj_prop) )
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
$sql = " SELECT  * FROM userbag WHERE uid = '".$_SESSION['id']."' AND id = '".$fj_prop."'";
$user_info = $_pm['mysql'] -> getRecords($sql);
if( count($user_info) != 1 )
{
	realseLock();
	die("illegal");
}
global $mem_props;
if(is_array($mem_props))
{
	$props = $mem_props;
}
else
{	
	$props	= unserialize($_pm['mem']->get(MEM_PROPS_KEY));
}
$db_welcome = unserialize($_pm['mem']->get('db_welcome'));
if( !is_array($props) || !is_array($db_welcome) )
{
	realseLock();
	die("memcacheerror");
}
foreach( $db_welcome as $info )
{
	if( $info['code'] == "biodegradable_equipment" )
	{
		$allow_postion = explode(',',$info['contents']);
		break;
	}
}
//每日分解次数限制
$day_zbfj = unserialize($_pm['mem'] -> get('zbfj_info'));
if( is_array($day_zbfj) )
{
	$has_record = 0;
	foreach($day_zbfj as $key => $val)
	{
		if( $key == 'FJ_NUM:'.$_SESSION['id'] )
		{
			$has_record = 1;
			if( $val > 0 )
			{
				$day_zbfj[$key] = $val-1;
			}
			else
			{
				die("nofjnum");
			}
		}
	}
	if( $has_record == 0 )
	{
		$key = 'FJ_NUM:'.$_SESSION['id'];
		$val = 4;
		$day_zbfj[$key] = $val;
	}
	$_pm['mem']->set(array('k'=>'zbfj_info','v'=>$day_zbfj));
	
}
else
{
	$key = 'FJ_NUM:'.$_SESSION['id'];
	$day_zbfj[$key] = 4;
	$_pm['mem']->set(array('k'=>'zbfj_info','v'=>$day_zbfj));
}

foreach( $props as $info )
{
	if( $info['id'] == $user_info[0]['pid'] )
	{
		if( !in_array($info['postion'],$allow_postion) )
		{
			die("illegal");
		}
		foreach( $db_welcome as $info_wel )
		{
			if( $info_wel['code'] == "fj_".$info['propscolor']."_success_rate" )
			{
				$rate_info = explode(',',$info_wel['contents']);
				break;
			}
		}
		foreach( $rate_info as $content )
		{
			$arr_mid = explode(':',$content);
			$item_ob[$arr_mid[0]] = array($arr_mid[1],$arr_mid[2]); 
		}
		$luck_num = rand(1,100);
		foreach ( $item_ob as $key => $val )
		{
			$interval = explode('-',$val[1]);
			if( $luck_num >= $interval[0] && $luck_num <= $interval[1] )
			{
				$get_item_type =  $key;
				break;
			}
		}
		$_pm['mysql'] -> query(" DELETE FROM userbag WHERE id = '".$fj_prop."' AND uid = '".$_SESSION['id']."'");
		$time = time();
		if( !isset($get_item_type) )	//fail
		{
			$return_str = 'fail';
			$massage = "装备分解:失去物品id:".$fj_prop.",物品名称:".$info['name'].",分解失败";
			$_pm['mysql'] -> query(" INSERT INTO gamelog (ptime,buyer,seller,pnote,vary) VALUES($time,'".$_SESSION['id']."','".$_SESSION['id']."','".$massage."','22')");
		}
		else
		{
			$num = explode('-',$item_ob[$get_item_type][0]);
			$num_result = rand($num[0],$num[1]);
			//database deal
			$massage = "装备分解:失去物品id:".$fj_prop.",物品名称:".$info['name'].",得到物品:".$get_item_type.",得到数量:".$num_result;
			$_pm['mysql'] -> query(" INSERT INTO gamelog (ptime,buyer,seller,pnote,vary) VALUES($time,'".$_SESSION['id']."','".$_SESSION['id']."','".$massage."','22')");
			$user = $_pm['user']->getUserById($_SESSION['id']);
			$bag  = $_pm['user']->getUserBagById($_SESSION['id']);
			$get_gem = new task;
			$get_gem->saveGetPropsMore($get_item_type,$num_result);
			
		}
		break;
	}
}
$get_gam_info = $_pm['mysql'] -> getOneRecord(" SELECT * FROM userbag WHERE sums > 0 AND sums < 100 AND uid = '".$_SESSION['id']."' AND pid = '".$get_item_type."' LIMIT 1 ");
foreach( $props as $info_p )
{
	if( $info_p['id'] == $get_item_type )
	{
		$get_item_name = $info_p['name'];
		$return_str = $get_item_name.','.$num_result.','.$info_p['img'].','.$get_gam_info['id'].','.$get_gam_info['pid'].','.$info_p['varyname'];
		break;
	}
}

echo $return_str;
realseLock();
?>
