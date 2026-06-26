<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: 谭炜

*@Write Date: 2008.09.12
*@Update Date: 2008.09.12
*@Usage: 装备强化
*@Note: NO Add magic props.
  本模块主要功能：
  	 装备强化
*/
require_once('../config/config.game.php');

secStart($_pm['mem']);

$srctime = 5;
#################增加一个间隔时间################
$time = $_SESSION['tgtimes'.$_SESSION['id']];
if(empty($time))
{	
	$_SESSION['tgtimes'.$_SESSION['id']] = time();
}
else
{
	$nowtime = time();
	$ctime = $nowtime - $time;
	if($ctime < $srctime)
	{
		die("11");//没有达到间隔时间
	}
	else
	{
		$_SESSION['tgtimes'.$_SESSION['id']] = time();
	}
}
##################增加在这里结束#################
$user		= $_pm['user']->getUserById($_SESSION['id']);
//$props		= unserialize($_pm['mem']->get(MEM_PROPS_KEY));
$mempropsid = unserialize($_pm['mem']->get('db_propsid'));
$userBag	= $_pm['user']->getUserBagById($_SESSION['id']);

$pid = intval($_REQUEST['pid']);
$pids = intval($_REQUEST['pids']);
$id = intval($_REQUEST['bid']);
$err = "";
$plus_tms_eft = "";
$baodeng = '';
if(!is_numeric($pid) || empty($pid))
{
	die("0");//没有相应的要强化的装备
}

/*if(lockItem($pid) === false)
{
	die('已经在处理了！');
}*/

if(!is_numeric($pids))
{
	unLockItem($pid);
	die("1");//辅助道具出错
}
//得到玩家该装备强化需要的道具ID
if(!empty($pid))
{
	$p = $mempropsid[$pid];
	//foreach($props as $p)
	//{
		//if($p['id'] == $pid)
		//{
			$pname = $p['name'];
			$nid = $p['pluspid'];
		//}
	//}
}
$log .= '装备包裹ID：'.$id.',名字：'.$pname.'';
$log .= '-强化等级：';
foreach($userBag as $ubag)
{
	if($ubag['pid'] == $nid && $ubag['sums']>0)
	{$rs = $ubag;
		$nsums = $ubag['sums'];//强化所需要的物品在用户包裹中的个数
		$fid = $ubag['id'];
		break;
	}
}
if($nsums < 1)
{
	unLockItem($pid);
	die("4");
}
foreach($userBag as $ubag)
{
	if($ubag['id'] == $id)
	{
		if(empty($ubag['plus_tmes_eft']))
		{
			$nums = 0;//当前玩家要强化的次数
			$num2 = 0;//如果成功，则玩家强化的次数
			$log .= "0";
		}
		else
		{
			$plus_tms_eft = $ubag['plus_tmes_eft'];
			$log .= $plus_tms_eft;
			$effect = explode(",",$plus_tms_eft);
			foreach($harden as $kh => $vh)
			{
				$khs = $kh + 1;
				if($effect[0] == $kh)
				{
					$nums = $khs;
					$num2 = $khs;//强化的次数
					break;
				}
			}
		}
	}
}
//只能强化15次
if($nums >= 15)
{
	unLockItem($pid);
	die("15");
}
//判断玩家的强化次数,从而得到玩家该次强化的几率 $num
//得到几率
foreach($harden as $ks => $h)
{
	$a = $nums;
	if($ks == $a)
	{
		$arr = explode(",",$h);
		$num = $arr[0];
		break;
	}
	else
	{
		$num = 6;
	}
}
//当使用辅助道具后进化
if(!empty($pids))
{

	//3.20
	foreach($userBag as $ub)
	{
		if($ub['pid'] == $pids && $ub['sums'] >= 1)
		{
			$flag = $ub['sums'];
			break;
		}
	}
	if(empty($flag))
	{
		unLockItem($pid);
		die("1");
	}
	
	$log .= '-辅助道具：'.$pids;
	$prop = $mempropsid[$pids];
	//foreach($props as $prop)
	//{
		//if($prop['id'] == $pids)
		//{
			$peffarr = explode(":",$prop['effect']);
			$peffe = explode(",",$peffarr[1]);
			if($peffarr[0] == "suc")
			{
				if(count($peffe) == 1)
				{
					$num = $num + $peffarr[1];
				}
			}
			else if($peffarr[0] == "100suc")
			{
				if($nums < $peffe[1])
				{
					$num = 10;
				}
			}
			else if($peffarr[0] == "baodi")
			{
				$baodi = explode("-","-1");
			}else if($peffarr[0] == "baodeng")
			{
				$baodeng = 1;
			}
		//}
	//}
}
$sj = rand(1,10);
if($sj <= $num)
{
	//装备强化成功，更新到数据库，同时扣除玩家相应的金币
	/*foreach($props as $pv)
	{
		if($pv['id'] == $pid)
		{
			$plusget = $pv['plusget'];
			break;
		}
	}*/
	$plusget = $mempropsid[$pid]['plusget'];
	$numarr = explode(",",$harden[$nums]);
	$num = $numarr[0];
	$moneys = $numarr[1];//装备强化所需金币
	//首先判断玩家的金币是否足够
	$money = $user['money'];
	if($money < $moneys)
	{
		unLockItem($pid);
		die("3");//金币不够
	}
	//减去玩家相应的金币
	$money = $money - $moneys;
	$sql = "UPDATE player 
			SET money = {$money}
			WHERE id = {$_SESSION['id']}";
	$_pm['mysql'] -> query($sql);
	//强化后加上的属性
	$plusarr = explode(",",$plusget);
	$plus = $plusarr[$nums];
	$plusstr = $num2.",".$plus;
	$sql = "UPDATE userbag
			SET plus_tms_eft = '{$plusstr}' 
			WHERE id = {$id}";
	$_pm['mysql'] -> query($sql);
	$err = 10;
}
else
{
	if(is_array($baodi))
	{
		if(!empty($plus_tms_eft))
		{
			$arr = explode(",",$plus_tms_eft);
			/*foreach($props as $pv)
			{
				if($pv['id'] == $pid)
				{
					$plusget = $pv['plusget'];
					break;
				}
			}*/
			$plusget = $mempropsid[$pid]['plusget'];
			$plusarr = explode(",",$plusget);
			$a = $nums - 2;
			$b = $num2 - 2;
			if($a >= 0)
			{
				$plus = $plusarr[$a];
				$plusstr = $b.",".$plus;
				$sql = "UPDATE userbag
						SET plus_tms_eft = '{$plusstr}' 
						WHERE id = {$id}";
			}
			else
			{
				$sql = "UPDATE userbag
						SET plus_tms_eft = 0
						WHERE id = {$id}";
			}
		}
		else
		{
			$sql = "UPDATE userbag
						SET plus_tms_eft = ''
						WHERE id = {$id}";
		}
		$_pm['mysql'] -> query($sql);
	}
	else if($baodeng != 1)
	{
		$sql = "DELETE FROM userbag
				WHERE id={$id}";
		$_pm['mysql'] -> query($sql);
	}
	
	$sql = "INSERT INTO gamelog(ptime,seller,buyer,pnote,vary)
		    VALUES(unix_timestamp(),'{$_SESSION['id']}','{$_SESSION['id']}','{$log}',5)";
	$_pm['mysql'] -> query($sql);
	$err = 2;//失败
	
}
//不管是成功还是失败，如果用了辅助道具，就要减去
if(!empty($pids))
{
	foreach($userBag as $v)
	{
		if($v['pid'] == $pids)
		{
			$sum = $v['sums'];
			break;
		}
	}
	/*
	if($sum > 1)
	{
		$sql = "UPDATE userbag
						SET sums = sums - 1
						WHERE uid = {$_SESSION['id']} and pid = {$pids}";
	}
	else
	{
		$sql = "DELETE FROM userbag
				WHERE uid = {$_SESSION['id']} and pid = {$pids}";
	}*/
	$sql = "UPDATE userbag
			   SET sums = abs(sums-1) WHERE uid = {$_SESSION['id']} and pid = {$pids} and sums > 0";
	$_pm['mysql'] -> query($sql);
	$result = mysql_affected_rows($_pm['mysql'] -> getConn());
	if($result != 1){
		unLockItem($pid);
		die("您没有相应的物品！");
	}
}
//减去所需的道具
/*
if($nsums > 1)
{
	$sql = "UPDATE userbag
			SET sums = sums - 1
			WHERE uid = {$_SESSION['id']} and pid = {$nid};";
}
else
{
	$sql = "DELETE FROM userbag
			WHERE uid = {$_SESSION['id']} and pid = {$nid}";
}*/

$sql = "UPDATE userbag
		SET sums = abs(sums-1) WHERE uid = {$_SESSION['id']} and pid = {$nid} and id = {$fid} and sums > 0;";
$_pm['mysql'] -> query($sql);
$result = mysql_affected_rows($_pm['mysql'] -> getConn());
if($result != 1){
	unLockItem($pid);
	die("您没有相应的物品！");
}
//清空session;
$_SESSION['pid'.$_SESSION['id']] = "";
$_SESSION['pids'.$_SESSION['id']] = "";
$_SESSION['bid'.$_SESSION['id']] = "";
unLockItem($pid);
echo $err;
?>