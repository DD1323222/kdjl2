<?php
header("Content-type: text/html; charset=gbk"); 
session_start();
/*if( empty($_SESSION['id']) )
{
	die("error");
}*/
require_once('../config/config.game.php');
$key = "xueyuanisarabbit";
$secret = md5($key.$_SESSION['lastvtime']);
/*if( $secret != $_GET['secret'] )
{
	die("error");
}*/
switch( $_GET['usecz'] )
{
	case "golden" :
	{
		$prize_type = "golden_eggs";
		$doing = "砸金蛋";
		$sub_props_name = '金蛋券';
		break;
	}
	case "silver" :
	{
		$prize_type = "silver_eggs";
		$doing = "砸银蛋";
		$sub_props_name = '银蛋券';
		break;	
	}
	case "copper" :
	{
		$prize_type = "copper_eggs";
		$doing = "砸铜蛋";
		$sub_props_name = '铜蛋券';
		break;
	}
	default :
	{
		die("error");
		break;
	}
	
}
require_once('../sec/dblock_fun.php');
$a = getLock($_SESSION['id']);
	if(!is_array($a)){
			realseLock();
			unLockItem($id);
			die('服务器繁忙，请稍候再试！');
	}
$sql = " SELECT userbag.sums,userbag.pid FROM userbag,props WHERE userbag.uid = {$_SESSION[id] } AND props.name = '".$sub_props_name."' AND userbag.pid = props.id AND userbag.sums > 0 ";
$res_sub_thing_info = $_pm['mysql'] -> getOneRecord($sql);
if( !isset($res_sub_thing_info) ||  empty($res_sub_thing_info['sums']) )
{
	$_pm['mysql']->query('rollback');
	die("noegg");
}
$sql = " SELECT code,contents FROM welcome WHERE  code = '".$prize_type."'";
$prize_info = $_pm['mysql'] -> getOneRecord($sql);
$everything = explode(',',$prize_info['contents']);
foreach( $everything as $info )
{
	$one_info = explode(':',$info);
	$thing_info_arr[$one_info[0]] = $one_info;
}



//$thing_info_arr物品对象
$luck_num = rand(0,10051);

foreach ($thing_info_arr as $key => $info )
{
	$range = explode('-',$info[4]);
	if( $luck_num >= $range[0] && $luck_num <= $range[1] )
	{//中此奖品
		$getprize = $key;
	}
	if( $info[3] == 1 )//显示4个没有砸得物品
	{
		$good_things[] = $key.":".$info[1];
	}
}
if( !isset($getprize) || empty($getprize) )
{
	$_pm['mysql']->query('rollback');
	die("error");
}

$task = new task();
$ret = $task->saveGetPropsMore($getprize,$thing_info_arr[$getprize][1]);
if( intval($ret) == 200 )
{
	$_pm['mysql']->query('rollback');
	die("bagfull");
}
if( $thing_info_arr[$getprize][2] == 1 )
{
	$sql = " SELECT name FROM props WHERE id = {$getprize} ";
	$get_prize_name = $_pm['mysql'] -> getOneRecord($sql);
	$word = "参加了幸运{$doing}活动，并幸运的获得了{$get_prize_name['name']}  {$thing_info_arr[$getprize][1]}个";
	$task->saveGword($word);
}
$_pm['mysql']->query("UPDATE userbag SET sums=sums-1 WHERE pid={$res_sub_thing_info['pid']} and uid={$_SESSION[id]} and sums>0 ");
realseLock();

$good_things_result_arr_key = array_rand($good_things,4);
for( $i = 0; $i < count($good_things_result_arr_key); $i++ )
{
	$good_things_result_arr_val[] = $good_things[$good_things_result_arr_key[$i]];
}
//print_r($good_things_result_arr_val);
echo $res_sub_thing_info['sums']-1;
echo "|";
$j = 0;
for($i = 1; $i < 6; $i++ )
{
	if($j < 0 )
	{
		$j = 0;
	}	
	if( $i == $_GET['choose_egg'] )
	{
		$sql = " SELECT name FROM props WHERE id = {$getprize} ";
		$thing_name = $_pm['mysql'] -> getOneRecord($sql);
		$echo .=  $i.":".$thing_name['name'].":".$thing_info_arr[$getprize][1]."|";
		$j--;
	}
	else
	{
		$mid_arr = explode(':',$good_things_result_arr_val[$j]);
		$sql = " SELECT name FROM props WHERE id = {$mid_arr[0]} ";
		$thing_name = $_pm['mysql'] -> getOneRecord($sql);
		$echo .=  $i.":".$thing_name['name'].":".$mid_arr[1]."|";
	}
	$j++;
}
sleep(1);
echo substr($echo,0,-1);
die();
?>
