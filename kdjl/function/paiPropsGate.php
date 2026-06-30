<?php

require_once('../config/config.game.php');
secStart($_pm['mem']);
if (!defined('MAX_PAI_VALIDTIME'))
define(MAX_PAI_VALIDTIME, 10800);
$err = 0;
$user = $_pm['user'] -> getUserById($_SESSION['id']);
$userBag = $_pm['user'] -> getUserBagById($_SESSION['id']);
$bid = intval($_REQUEST['bid']);
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$sql = "SELECT paisj,sj FROM player_ext WHERE uid = {$_SESSION['id']}";
$sjarr = $_pm['mysql'] -> getOneRecord($sql);
if(is_array($sjarr)){
	$user['sj'] = $sjarr['sj'];
	$user['paisj'] = $sjarr['paisj'];
}else $user['sj'] = $user['paisj'] = 0;
//增加一个冷却时间
$srctime = 5;
#################增加一个间隔时间################
$time = $_SESSION['checktimes'.$_SESSION['id']];
if(empty($time))
{	
	$_SESSION['checktimes'.$_SESSION['id']] = time();
}
else
{
	$nowtime = time();
	$ctime = $nowtime - $time;
	if($ctime < $srctime)
	{
		die("100");//没有达到间隔时间
	}
	else
	{
		$_SESSION['checktimes'.$_SESSION['id']] = time();
	}
}

if($action == "")
{
	if($bid < 1)
	{
		die('0');
	}
	$_pm['mysql']->query('START TRANSACTION');
	$sql = "SELECT id,psum FROM userbag WHERE uid = {$_SESSION['id']} and id = {$bid} FOR UPDATE";
	$row = $_pm['mysql'] -> getOneRecord($sql);
	if(!is_array($row) || $row['psum'] <= 0)
	{
		$_pm['mysql']->query('ROLLBACK');
		die('2');
	}
	$sql = "UPDATE userbag 
			SET sums = sums + psum,psum = 0,pstime = 0,petime = 0,psell = 0,psj = 0,pyb = 0,buycode = 0
			WHERE uid = {$_SESSION['id']} and id = {$bid} and psum > 0";
	$_pm['mysql'] -> query($sql);
	if(mysql_affected_rows($_pm['mysql']->getConn()) != 1 || !$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		die('2');
	}
	$err = 3;
	$_pm['mem']->memClose();
	echo $err;
}
else if($action == "money")
{
	$_pm['mysql']->query('START TRANSACTION');
	$player = $_pm['mysql']->getOneRecord("SELECT id,paimoney FROM player WHERE id={$_SESSION['id']} FOR UPDATE");
	$playerExt = $_pm['mysql']->getOneRecord("SELECT uid,paisj,paiyb FROM player_ext WHERE uid={$_SESSION['id']} FOR UPDATE");
	if(!is_array($player) || !is_array($playerExt))
	{
		$_pm['mysql']->query('ROLLBACK');
		die('0');
	}
	$paiMoney = intval($player['paimoney']);
	$paiSj = intval($playerExt['paisj']);
	$paiYb = intval($playerExt['paiyb']);
	if($paiMoney <= 0 && $paiSj <= 0 && $paiYb <= 0)
	{
		$_pm['mysql']->query('ROLLBACK');
		die('0');
	}
	if($paiMoney > 0 || $paiYb > 0)
	{
		$_pm['mysql']->query("UPDATE player SET money=money+{$paiMoney},paimoney=0,yb=yb+{$paiYb} WHERE id={$_SESSION['id']}");
		if(mysql_affected_rows($_pm['mysql']->getConn()) != 1)
		{
			$_pm['mysql']->query('ROLLBACK');
			die('0');
		}
	}
	if($paiSj > 0 || $paiYb > 0)
	{
		$_pm['mysql']->query("UPDATE player_ext SET sj=sj+{$paiSj},paisj=0,paiyb=0 WHERE uid={$_SESSION['id']}");
		if(mysql_affected_rows($_pm['mysql']->getConn()) != 1)
		{
			$_pm['mysql']->query('ROLLBACK');
			die('0');
		}
	}
	if(!$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		die('0');
	}

	$err = 1;
	$_pm['mem']->memClose();
	echo $err;
}

else if($action == "sale")
{
	$err = 5;
	$_pm['mysql']->query('START TRANSACTION');
	$sql = "SELECT id,psum,petime 
			FROM userbag
			WHERE pid = {$bid} and uid = {$_SESSION['id']} and psum > 0
			ORDER BY id ASC LIMIT 1 FOR UPDATE";
	$bag = $_pm['mysql'] -> getOneRecord($sql);
	if(is_array($bag))
	{
		if($bag['psum'] <= 0)
		{
			$_pm['mysql']->query('ROLLBACK');
			die("1");
		}
		else
		{
			if($bag['petime'] < time())
			{
				$time = time();
				$et  = $time + MAX_PAI_VALIDTIME;
				$sql = "UPDATE userbag set pstime = {$time},petime = {$et} WHERE uid = {$_SESSION['id']} and id = {$bag['id']} and psum > 0";
				$_pm['mysql'] -> query($sql);
				if(mysql_affected_rows($_pm['mysql']->getConn()) != 1 || !$_pm['mysql']->query('COMMIT'))
				{
					$_pm['mysql']->query('ROLLBACK');
					die('1');
				}
			}
			else
			{
				$_pm['mysql']->query('ROLLBACK');
				die("0");
			}
		}
	}
	else
	{
		$_pm['mysql']->query('ROLLBACK');
	}
	echo "5";
}
?>
