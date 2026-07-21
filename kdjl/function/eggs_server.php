<?php
header("Content-type: text/html; charset=UTF-8");
session_start();
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if( $uid < 1 )
{
	die("error");
}
require_once('../config/config.game.php');
$key = "xueyuanisarabbit";
$lastvtime = isset($_SESSION['lastvtime']) ? $_SESSION['lastvtime'] : '';
$secret = md5($key.$lastvtime);
/*if( $secret != $_GET['secret'] )
{
	die("error");
}*/
$usecz = (isset($_GET['usecz']) && !is_array($_GET['usecz'])) ? $_GET['usecz'] : '';
switch( $usecz )
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
$choose_egg = (isset($_GET['choose_egg']) && !is_array($_GET['choose_egg'])) ? intval($_GET['choose_egg']) : 0;
if($choose_egg < 1 || $choose_egg > 5)
{
	die("error");
}
require_once('../sec/dblock_fun.php');
$a = getLock($uid);
	if(!is_array($a)){
			realseLock();
			die('服务器繁忙，请稍候再试！');
	}
$sql = "SELECT userbag.id,userbag.sums,userbag.pid
          FROM userbag,props
         WHERE userbag.uid=$uid
           AND props.name='".$sub_props_name."'
           AND userbag.pid=props.id
           AND userbag.sums>0
           AND userbag.zbing=0
           AND (userbag.cantrade IS NULL OR userbag.cantrade<>3)
      ORDER BY userbag.id LIMIT 1 FOR UPDATE";
$res_sub_thing_info = $_pm['mysql'] -> getOneRecord($sql);
if( !isset($res_sub_thing_info) ||  empty($res_sub_thing_info['sums']) )
{
	$_pm['mysql']->query('rollback');
	realseLock();
	die("noegg");
}
$sql = " SELECT code,contents FROM welcome WHERE  code = '".$prize_type."'";
$prize_info = $_pm['mysql'] -> getOneRecord($sql);
if(!is_array($prize_info) || empty($prize_info['contents']))
{
	$_pm['mysql']->query('rollback');
	realseLock();
	die("error");
}
$everything = explode(',',$prize_info['contents']);
$thing_info_arr = array();
$good_things = array();
$luckMin = null;
$luckMax = null;
$announceWord = '';
foreach( $everything as $info )
{
	$one_info = explode(':',trim($info));
	if(count($one_info) != 5 || $one_info[0] == '')
	{
		$_pm['mysql']->query('ROLLBACK');
		realseLock();
		die("error");
	}
	$pid = intval($one_info[0]);
	$num = intval($one_info[1]);
	$announceFlag = intval($one_info[2]);
	$displayFlag = intval($one_info[3]);
	$range = explode('-',$one_info[4]);
	if($pid < 1 || $num < 1 || ($announceFlag != 0 && $announceFlag != 1) || ($displayFlag != 0 && $displayFlag != 1) || count($range) != 2)
	{
		$_pm['mysql']->query('ROLLBACK');
		realseLock();
		die("error");
	}
	$rangeStart = intval($range[0]);
	$rangeEnd = intval($range[1]);
	if($rangeStart < 0 || $rangeStart > $rangeEnd)
	{
		$_pm['mysql']->query('ROLLBACK');
		realseLock();
		die("error");
	}
	if($luckMin === null || $rangeStart < $luckMin) $luckMin = $rangeStart;
	if($luckMax === null || $rangeEnd > $luckMax) $luckMax = $rangeEnd;
	$thing_info_arr[] = array($pid,$num,$announceFlag,$displayFlag,$rangeStart.'-'.$rangeEnd);
}
if(empty($thing_info_arr) || $luckMin === null || $luckMax === null)
{
	$_pm['mysql']->query('rollback');
	realseLock();
	die("error");
}



//$thing_info_arr物品对象
$luck_num = rand($luckMin,$luckMax);

foreach ($thing_info_arr as $key => $info )
{
	$range = explode('-',$info[4]);
	if(count($range) < 2) continue;
	$rangeStart = intval($range[0]);
	$rangeEnd = intval($range[1]);
	if($rangeStart > $rangeEnd) continue;
	if( !isset($getprize) && $luck_num >= $rangeStart && $luck_num <= $rangeEnd )
	{//中此奖品
		$getprize = $key;
	}
	if( $info[3] == 1 )//显示4个没有砸得物品
	{
		$good_things[] = $info[0].":".$info[1];
	}
}
if( !isset($getprize) )
{
	$_pm['mysql']->query('rollback');
	realseLock();
	die("error");
}

$task = new task();
$prizePid = intval($thing_info_arr[$getprize][0]);
$prizeNum = intval($thing_info_arr[$getprize][1]);
$ret = $task->saveGetPropsMore($prizePid,$prizeNum);
if($ret !== true)
{
	$_pm['mysql']->query('rollback');
	realseLock();
	die($ret === '200' ? "bagfull" : 'error');
}
if( $thing_info_arr[$getprize][2] == 1 )
{
	$sql = " SELECT name FROM props WHERE id = {$prizePid} ";
	$get_prize_name = $_pm['mysql'] -> getOneRecord($sql);
	$get_prize_name = is_array($get_prize_name) ? $get_prize_name['name'] : $prizePid;
	$announceWord = "参加了幸运{$doing}活动，并幸运的获得了{$get_prize_name}  {$thing_info_arr[$getprize][1]}个";
}
$ticketId = intval($res_sub_thing_info['id']);
$ticketUsed = $_pm['mysql']->query("UPDATE userbag
                                       SET sums=sums-1
                                     WHERE id=$ticketId
                                       AND uid=$uid
                                       AND sums>0
                                       AND zbing=0
                                       AND (cantrade IS NULL OR cantrade<>3)");
if(!$ticketUsed || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
	$_pm['mysql']->query('ROLLBACK');
	realseLock();
	die('noegg');
}
$ticketPid = intval($res_sub_thing_info['pid']);
if(!$_pm['mysql']->query("DELETE FROM userbag WHERE id=$ticketId AND uid=$uid AND pid=$ticketPid AND sums<=0 AND bsum<=0 AND psum<=0 AND pyb=0 AND zbing=0 AND (cantrade IS NULL OR cantrade<>3)")){
	$_pm['mysql']->query('ROLLBACK');
	realseLock();
	die('error');
}
if(!$_pm['mysql']->query('COMMIT')){
	$_pm['mysql']->query('ROLLBACK');
	realseLock();
	die('error');
}
$_pm['mem']->del(MEM_USERBAG_KEY);
realseLock();
if($announceWord !== '') $task->saveGword($announceWord);

$good_things_result_arr_key = array();
$good_things_result_arr_val = array();
$goodCount = count($good_things);
if($goodCount > 0)
{
	$randCount = min(4, $goodCount);
	$good_things_result_arr_key = array_rand($good_things,$randCount);
	if($randCount == 1) $good_things_result_arr_key = array($good_things_result_arr_key);
}
for( $i = 0; $i < count($good_things_result_arr_key); $i++ )
{
	$good_things_result_arr_val[] = $good_things[$good_things_result_arr_key[$i]];
}
if(empty($good_things_result_arr_val))
{
	$good_things_result_arr_val[] = $prizePid.":".$prizeNum;
}
$baseGoodThingsCount = count($good_things_result_arr_val);
for($i = $baseGoodThingsCount; $i < 4; $i++)
{
	$good_things_result_arr_val[] = $good_things_result_arr_val[$i % $baseGoodThingsCount];
}
//print_r($good_things_result_arr_val);
echo $res_sub_thing_info['sums']-1;
echo "|";
$echo = '';
$j = 0;
for($i = 1; $i < 6; $i++ )
{
	if($j < 0 )
	{
		$j = 0;
	}
	if( $i == $choose_egg )
	{
		$sql = " SELECT name FROM props WHERE id = {$prizePid} ";
		$thing_name = $_pm['mysql'] -> getOneRecord($sql);
		$thing_name = is_array($thing_name) ? $thing_name['name'] : $prizePid;
		$echo .=  $i.":".$thing_name.":".$prizeNum."|";
		$j--;
	}
	else
	{
		$mid_arr = explode(':',$good_things_result_arr_val[$j]);
		if(count($mid_arr) < 2) $mid_arr = array($prizePid,$prizeNum);
		$mid_arr[0] = intval($mid_arr[0]);
		$mid_arr[1] = intval($mid_arr[1]);
		$sql = " SELECT name FROM props WHERE id = {$mid_arr[0]} ";
		$thing_name = $_pm['mysql'] -> getOneRecord($sql);
		$thing_name = is_array($thing_name) ? $thing_name['name'] : $mid_arr[0];
		$echo .=  $i.":".$thing_name.":".$mid_arr[1]."|";
	}
	$j++;
}
sleep(1);
echo substr($echo,0,-1);
die();
?>
