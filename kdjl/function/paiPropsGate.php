<?php

require_once('../config/config.game.php');
secStart($_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid <= 0)
{
	die('0');
}
if (!defined('MAX_PAI_VALIDTIME'))
define('MAX_PAI_VALIDTIME', 10800);
$err = 0;
$user = $_pm['user'] -> getUserById($uid);
if(!is_array($user)) die('0');
del_bag_expire();
$bid = (isset($_REQUEST['bid']) && !is_array($_REQUEST['bid'])) ? intval($_REQUEST['bid']) : 0;
$bagid = (isset($_REQUEST['bagid']) && !is_array($_REQUEST['bagid'])) ? intval($_REQUEST['bagid']) : 0;
$action = (isset($_REQUEST['action']) && !is_array($_REQUEST['action'])) ? $_REQUEST['action'] : '';
//增加一个冷却时间
$srctime = 5;
#################增加一个间隔时间################
$timeKey = 'checktimes'.$uid;
$time = isset($_SESSION[$timeKey]) ? $_SESSION[$timeKey] : 0;
if(empty($time))
{
	$_SESSION[$timeKey] = time();
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
		$_SESSION[$timeKey] = time();
	}
}

if($action == "")
{
	if($bid < 1)
	{
		die('0');
	}
	if(!$_pm['mysql']->query('START TRANSACTION')) die('2');
	$sql = "SELECT id,psum,sums,zbing FROM userbag WHERE uid = {$uid} and id = {$bid} FOR UPDATE";
	$row = $_pm['mysql'] -> getOneRecord($sql);
	if(!is_array($row) || intval($row['psum']) <= 0 || intval($row['zbing']) != 0)
	{
		$_pm['mysql']->query('ROLLBACK');
		die('2');
	}
	$lockedPlayer = $_pm['mysql']->getOneRecord("SELECT maxbag FROM player WHERE id={$uid} FOR UPDATE");
	$lockedBags = $_pm['mysql']->getRecords("SELECT id,sums,zbing FROM userbag WHERE uid={$uid} FOR UPDATE");
	if(!is_array($lockedPlayer) || !is_array($lockedBags))
	{
		$_pm['mysql']->query('ROLLBACK');
		die('2');
	}
	$bagNum = 0;
	foreach($lockedBags as $lockedBag)
	{
		if(!is_array($lockedBag)) continue;
		if(intval($lockedBag['sums']) > 0 && intval($lockedBag['zbing']) == 0) $bagNum++;
	}
	$needBagSlot = intval($row['sums']) > 0 ? 0 : 1;
	if($bagNum + $needBagSlot > intval($lockedPlayer['maxbag']))
	{
		$_pm['mysql']->query('ROLLBACK');
		die('1');
	}
	$newSums = kdjlSafeNonNegativeSum($row['sums'], $row['psum']);
	if($newSums === false || $newSums <= 0)
	{
		$_pm['mysql']->query('ROLLBACK');
		die('2');
	}
	$sql = "UPDATE userbag
			SET sums = {$newSums},psum = 0,pstime = 0,petime = 0,psell = 0,psj = 0,pyb = 0,buycode = 0
			WHERE uid = {$uid} and id = {$bid} and psum = ".intval($row['psum'])." and COALESCE(sums,0) = ".intval($row['sums']);
	if(!$_pm['mysql'] -> query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1 || !$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		die('2');
	}
	$err = 3;
	$_pm['mem']->del($uid.'bag');
	$_pm['mem']->memClose();
	echo $err;
}
else if($action == "money")
{
	if(!$_pm['mysql']->query('START TRANSACTION')) die('0');
	if(!$_pm['mysql']->query("INSERT INTO player_ext(uid,bbshow) VALUES({$uid},5) ON DUPLICATE KEY UPDATE uid=uid"))
	{
		$_pm['mysql']->query('ROLLBACK');
		die('0');
	}
	$player = $_pm['mysql']->getOneRecord("SELECT id,money,paimoney,yb FROM player WHERE id={$uid} FOR UPDATE");
	$playerExt = $_pm['mysql']->getOneRecord("SELECT uid,sj,paisj,paiyb FROM player_ext WHERE uid={$uid} FOR UPDATE");
	if(!is_array($player) || !is_array($playerExt))
	{
		$_pm['mysql']->query('ROLLBACK');
		die('0');
	}
	$paiMoney = max(0, intval($player['paimoney']));
	$paiSj = max(0, intval($playerExt['paisj']));
	$paiYb = max(0, intval($playerExt['paiyb']));
	if($paiMoney <= 0 && $paiSj <= 0 && $paiYb <= 0)
	{
		$_pm['mysql']->query('ROLLBACK');
		die('0');
	}
	$currentMoney = max(0, intval($player['money']));
	$currentSj = max(0, intval($playerExt['sj']));
	$currentYb = max(0, intval($player['yb']));
	$moneyRoom = max(0, 1000000000 - min(1000000000, $currentMoney));
	$sjRoom = max(0, 2147483647 - min(2147483647, $currentSj));
	$ybRoom = max(0, 2147483647 - min(2147483647, $currentYb));
	$claimMoney = min($paiMoney, $moneyRoom);
	$claimSj = min($paiSj, $sjRoom);
	$claimYb = min($paiYb, $ybRoom);
	$remainMoney = $paiMoney - $claimMoney;
	$remainSj = $paiSj - $claimSj;
	$remainYb = $paiYb - $claimYb;
	$claimedAny = ($claimMoney > 0 || $claimSj > 0 || $claimYb > 0);
	$partialClaim = ($remainMoney > 0 || $remainSj > 0 || $remainYb > 0);
	if(!$claimedAny)
	{
		$_pm['mysql']->query('ROLLBACK');
		die($partialClaim ? '6' : '0');
	}
	if($claimMoney > 0 || $claimYb > 0)
	{
		$newMoney = $currentMoney + $claimMoney;
		$newYb = $currentYb + $claimYb;
		if(!$_pm['mysql']->query("UPDATE player SET money={$newMoney},paimoney={$remainMoney},yb={$newYb} WHERE id={$uid}") ||
			mysql_affected_rows($_pm['mysql']->getConn()) != 1)
		{
			$_pm['mysql']->query('ROLLBACK');
			die('0');
		}
	}
	if($claimSj > 0 || $claimYb > 0)
	{
		$newSj = $currentSj + $claimSj;
		if(!$_pm['mysql']->query("UPDATE player_ext SET sj={$newSj},paisj={$remainSj},paiyb={$remainYb} WHERE uid={$uid}") ||
			mysql_affected_rows($_pm['mysql']->getConn()) != 1)
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

	$err = $partialClaim ? 6 : 1;
	$_pm['mem']->del($uid);
	$_pm['mem']->memClose();
	echo $err;
}

else if($action == "sale")
{
	if($bagid < 1 && $bid < 1)
	{
		die("1");
	}
	if(!$_pm['mysql']->query('START TRANSACTION')) die('1');
	$saleWhere = $bagid > 0 ? 'id = '.$bagid : 'pid = '.$bid;
	$sql = "SELECT id,psum,petime
			FROM userbag
			WHERE {$saleWhere} and uid = {$uid} and psum > 0
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
				$sql = "UPDATE userbag set pstime = {$time},petime = {$et} WHERE uid = {$uid} and id = {$bag['id']} and psum > 0 and petime < {$time}";
				if(!$_pm['mysql'] -> query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1 || !$_pm['mysql']->query('COMMIT'))
				{
					$_pm['mysql']->query('ROLLBACK');
					die('1');
				}
				$_pm['mem']->del($uid.'bag');
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
		die("1");
	}
	echo "5";
}
?>
