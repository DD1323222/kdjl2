<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %1.2版%

*@Write Date: 2008.05.19
*@Update Date: 2008.05.22
*@Usage: 仓库处理网关
*@Memo: op = s : save
	    op = g : get
		修复：可以超过格子上限BUG。
*/
require_once('../config/config.game.php');

function baseGateAbort($bid, $message, $rollback)
{
	global $_pm;
	if($rollback)
	{
		$_pm['mysql']->query('ROLLBACK');
	}
	if($bid > 0)
	{
		unLockItem($bid);
	}
	if(isset($_pm['mem'])) $_pm['mem']->memClose();
	die($message);
}

secStart($_pm['mem']);

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid <= 0)
{
	die("10");
}

$srctime = 2;
#################增加一个间隔时间################
$timeKey = 'paitimes'.$uid;
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
		die("1000");//没有达到间隔时间
	}
	else
	{
		$_SESSION[$timeKey] = time();
	}
}



$user	 = $_pm['user']->getUserById($uid);
if(!is_array($user)) die('10');
if(!isset($user['maxbag'])) $user['maxbag'] = 0;
if(!isset($user['maxbase'])) $user['maxbase'] = 0;
//$userBag = $_pm['user']->getUserBagById($_SESSION['id']);

$bid = (isset($_REQUEST['bid']) && !is_array($_REQUEST['bid'])) ? intval($_REQUEST['bid']) : 0;	// 包裹ID


if(empty($bid))
{
	die("10");
}

if(lockItem($bid) === false)
{
	die('已经在处理了！');
}

$parr = $_pm['user']->getUserItemById($uid,$bid);
$n = (isset($_REQUEST['n']) && !is_array($_REQUEST['n'])) ? intval($_REQUEST['n']) : 0; // 物品数量
$op = (isset($_REQUEST['op']) && !is_array($_REQUEST['op'])) ? $_REQUEST['op'] : '';
if(!is_array($parr) || $n <= 0)
{
	unLockItem($bid);
	die("10");
}
if(!isset($parr['vary'])) $parr['vary'] = 0;
if(!isset($parr['sums'])) $parr['sums'] = 0;
if(!isset($parr['bsum'])) $parr['bsum'] = 0;
if(intval($parr['vary']) != 1 && intval($parr['vary']) != 2)
{
	unLockItem($bid);
	die("10");
}
if(intval($parr['vary']) == 2 && $n != 1)
{
	unLockItem($bid);
	die("10");
}
if($op != 's' && $op != 'g')
{
	unLockItem($bid);
	die("10");
}
if(!$_pm['mysql']->query('START TRANSACTION'))
{
	baseGateAbort($bid, "10", false);
}
$lockRow = $_pm['mysql']->getOneRecord("SELECT vary,sums,bsum,cantrade FROM userbag WHERE id={$bid} and uid={$uid} and zbing=0 FOR UPDATE");
if(!is_array($lockRow))
{
	baseGateAbort($bid, "10", true);
}
$parr['vary'] = isset($lockRow['vary']) ? intval($lockRow['vary']) : 0;
$parr['sums'] = isset($lockRow['sums']) ? intval($lockRow['sums']) : 0;
$parr['bsum'] = isset($lockRow['bsum']) ? intval($lockRow['bsum']) : 0;
if(isset($lockRow['cantrade']) && intval($lockRow['cantrade']) == 3)
{
	baseGateAbort($bid, "10", true);
}
$bagRowsLocked = $_pm['mysql']->getRecords("SELECT id FROM userbag WHERE uid={$uid} FOR UPDATE");
if(!is_array($bagRowsLocked))
{
	baseGateAbort($bid, "10", true);
}
$bagsums = $_pm['mysql'] -> getOneRecord("SELECT count(id) as sum FROM userbag WHERE zbing = 0 and sums > 0 and uid = {$uid}");
$cksums = $_pm['mysql'] -> getOneRecord("SELECT count(id) as sum FROM userbag WHERE zbing = 0 and bsum > 0 and uid = {$uid}");
$bagSum = is_array($bagsums) ? intval($bagsums['sum']) : 0;
$baseSum = is_array($cksums) ? intval($cksums['sum']) : 0;
if ($n <= $parr['sums'] && $op == 's')
{
	$baseNeedSlot = ($parr['vary']==2) ? $n : (intval($parr['bsum']) > 0 ? 0 : 1);
	if(($baseSum+$baseNeedSlot) > $user['maxbase'])
	{
		baseGateAbort($bid, '4', true);
	}
	$newBaseSum = kdjlSafeNonNegativeSum($parr['bsum'], $n);
	if($newBaseSum === false)
	{
		baseGateAbort($bid, '10', true);
	}

	$storeOk = $_pm['mysql']->query("UPDATE userbag
							 SET sums=sums-{$n},bsum={$newBaseSum}
						   WHERE id={$bid} and uid={$uid} and sums >= $n and zbing = 0 and (cantrade IS NULL OR cantrade<>3)
						");
	$result = $storeOk ? mysql_affected_rows($_pm['mysql'] -> getConn()) : 0;
	if($result != 1){
		baseGateAbort($bid, "10", true);
	}
/**************提交事务*************/
	if (!$_pm['mysql']->query('COMMIT'))
	{
		baseGateAbort($bid, '10', true);
	}

}
else if($n <= $parr['bsum'] && $op == 'g')
{
	$bagNeedSlot = ($parr['vary']==2) ? $n : (intval($parr['sums']) > 0 ? 0 : 1);
	if(($bagSum+$bagNeedSlot) > $user['maxbag']){
		baseGateAbort($bid, '5', true);
	}
	$newBagSum = kdjlSafeNonNegativeSum($parr['sums'], $n);
	if($newBagSum === false)
	{
		baseGateAbort($bid, '10', true);
	}

	$takeOk = $_pm['mysql']->query("UPDATE userbag
							 SET sums={$newBagSum},bsum=bsum-{$n}
						   WHERE id={$bid} and uid={$uid} and bsum >= $n and zbing = 0 and (cantrade IS NULL OR cantrade<>3)
						");
/**************提交事务*************/
	$result = $takeOk ? mysql_affected_rows($_pm['mysql'] -> getConn()) : 0;
	if($result != 1){
		baseGateAbort($bid, "10", true);
	}
	if (!$_pm['mysql']->query('COMMIT'))
	{
		baseGateAbort($bid, '10', true);
	}

}
else{
	baseGateAbort($bid, '10', true);
}

$_pm['mem']->del(MEM_USERBAG_KEY);
unLockItem($bid);
$_pm['mem']->memClose();
die('0');
?>
