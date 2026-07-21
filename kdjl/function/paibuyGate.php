<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

@Usage: PAI Buy ServerGate;
@Write date: 2008.05.14
@Update date: 2008.07.13
@Note:
*/
session_start();
require_once('../config/config.game.php');

$arrobj = new arrays();
secStart($_pm['mem']);

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid <= 0)
{
	die('1');
}
del_bag_expire();

//增加一个冷却时间
$srctime = 5;
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
		die("5");//没有达到间隔时间
	}
	else
	{
		$_SESSION[$timeKey] = time();
	}
}

$err = 0;
$pendingPaiLog = false;
$paiBuyTransactionActive = false;
$paiBuyLockedItemId = 0;

function paiBuyShutdown()
{
	global $_pm, $paiBuyTransactionActive, $paiBuyLockedItemId;
	$error = error_get_last();
	if(!is_array($error) || !in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) return;
	if($paiBuyTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$paiBuyTransactionActive = false;
	}
	if($paiBuyLockedItemId > 0)
	{
		unLockItem($paiBuyLockedItemId);
		$paiBuyLockedItemId = 0;
	}
}
register_shutdown_function('paiBuyShutdown');

$user		 = $_pm['user']->getUserById($uid);
if(!is_array($user)) die('1');
$userDefaults = array('money'=>0, 'maxbag'=>0, 'nickname'=>'');
foreach($userDefaults as $userDefaultKey => $userDefaultValue)
{
	if(!isset($user[$userDefaultKey])) $user[$userDefaultKey] = $userDefaultValue;
}
$userbag	 = $_pm['user']->getUserBagById($uid);


$bid = (isset($_REQUEST['bid']) && !is_array($_REQUEST['bid'])) ? intval($_REQUEST['bid']) : 0; // TABLE: userbag => id
if($bid <= 0)
{
	die('2');
}
if(lockItem($bid) === false)
{
	die('已经在处理了！');
}
$paiBuyLockedItemId = $bid;
$n	 = (isset($_REQUEST['n']) && !is_array($_REQUEST['n'])) ? intval($_REQUEST['n']) : 0;	 // Buy number
if($n <= 0)
{
	unLockItem($bid);
	die('2');
}
if ($_pm['user']->check(array('int' => array($bid, $n)))===false || $n>100)	{$_pm['mem']->memClose();
	unLockItem($bid);
	die("2");}

$paiProps = FALSE;
$now = time();
$type = 0;
//####################################################################

//## bag num
$bagNum=0;
if(!empty($userbag)&&count($userbag)>0){
	foreach($userbag as $x=>$y)
	{
		if(!is_array($y)) continue;
		if(!isset($y['sums'])) $y['sums'] = 0;
		if(!isset($y['zbing'])) $y['zbing'] = 0;
		if($y['sums']>0 and $y['zbing']==0) $bagNum++;
	}
}

$buycode = crc32($user['nickname']);
$buycode = $buycode<0?(1-$buycode-1):$buycode;

/*$paiProps = $_pm['mysql']->getOneRecord("SELECT *
								 FROM userbag
								WHERE psell>0 and psum>0 and petime> {$now} and id={$bid}
							 ");*/

if(!$_pm['mysql']->query('START TRANSACTION')) { unLockItem($bid); $paiBuyLockedItemId = 0; $_pm['mem']->memClose(); die('3'); }
$paiBuyTransactionActive = true;
$paiProps = $_pm['mysql']->getOneRecord("SELECT *
								 FROM userbag
								WHERE id={$bid} and psell>0 and psum>0 and petime>{$now} FOR UPDATE
							 ");
//psell>0 and psum>0
if (!is_array($paiProps) || $paiProps['psum']<$n ) {
	unLockItem($bid);
	$_pm['mysql']->query('ROLLBACK');
	die('3');
}
$paiProps['uid'] = intval($paiProps['uid']);
$paiProps['pid'] = intval($paiProps['pid']);
$paiProps['sell'] = intval($paiProps['sell']);
$paiProps['vary'] = intval($paiProps['vary']);
$paiProps['psum'] = intval($paiProps['psum']);
$paiProps['psell'] = intval($paiProps['psell']);
$sourceCantrade = isset($paiProps['cantrade']) ? intval($paiProps['cantrade']) : 0;
if($sourceCantrade != 0 && $sourceCantrade != 1)
{
	unLockItem($bid);
	$_pm['mysql']->query('ROLLBACK');
	die('3');
}
$stackTradeWhere = $sourceCantrade == 1 ? 'cantrade=1' : '(cantrade IS NULL OR cantrade=0)';

$pid = $paiProps['pid'];
if($paiProps['psell']<1||$paiProps['psum']<1)
{
	unLockItem($bid);
	$_pm['mysql']->query('ROLLBACK');
	die('拍卖设定错误！');
}
if(intval($paiProps['vary']) == 2 && $n != 1)
{
	unLockItem($bid);
	$_pm['mysql']->query('ROLLBACK');
	die('2');
}

if($paiProps['uid']==$uid)
{
	unLockItem($bid);
	$_pm['mysql']->query('ROLLBACK');
	die('不允许购买自己的的东西!');
}

if ($paiProps['buycode'] > 1 && $paiProps['buycode']!=$buycode){
	unLockItem($bid);
	$_pm['mysql']->query('ROLLBACK');
	die('7');
}

$lockedBagState = paiLockBuyerBagState($uid);
if(!is_array($lockedBagState))
{
	unLockItem($bid);
	$_pm['mysql']->query('ROLLBACK');
	die('3');
}
$userbag = $lockedBagState['bags'];
$bagNum = $lockedBagState['count'];
$user['maxbag'] = $lockedBagState['maxbag'];

$needBagSlot = 1;
if($paiProps['vary'] == 1 && is_array($userbag))
{
	foreach($userbag as $bagItem)
	{
		if(!is_array($bagItem)) continue;
		if(!isset($bagItem['pid'])) $bagItem['pid'] = 0;
		if(!isset($bagItem['zbing'])) $bagItem['zbing'] = 0;
		if(!isset($bagItem['sums'])) $bagItem['sums'] = 0;
		$bagCantrade = isset($bagItem['cantrade']) ? intval($bagItem['cantrade']) : 0;
		$tradeMatches = $sourceCantrade == 1 ? $bagCantrade == 1 : $bagCantrade == 0;
		if(intval($bagItem['pid']) == intval($pid) && intval($bagItem['zbing']) == 0 && intval($bagItem['sums']) > 0 && $tradeMatches)
		{
			$needBagSlot = 0;
			break;
		}
	}
}

$priceSum = kdjlSafePositiveProduct($paiProps['psell'], $n);
if ($priceSum === false)
{
	unLockItem($bid);
	$_pm['mysql']->query('ROLLBACK');
	$_pm['mem']->memClose();
	die('2');
}
if ($priceSum > $user['money'])
{
	unLockItem($bid);
	$_pm['mysql']->query('ROLLBACK');
	$_pm['mem']->memClose();
	die('10'); // Money too less.
}
else if(($bagNum+$needBagSlot)>$user['maxbag'])
{
	unLockItem($bid);
	$_pm['mysql']->query('ROLLBACK');
	$_pm['mem']->memClose();
	die('4');
}
else
{
	$status=false;
    if ($paiProps['vary']==2) // Not Repeat!
	{
		$logsql='logsql:';

/****打开MYSQL事务，禁止自动提交****/

	    //$_pm['mysql']->query('START TRANSACTION');
		if(!$_pm['mysql']->query("UPDATE userbag
							     SET uid={$uid},
									 psell=0,
									 psj=0,
									 pyb=0,
									 psum=0,
									 pstime=0,
									 petime=0,
									 buycode=0,
									 sums=1
							   WHERE psell>0 and psum>0 and id={$bid} and uid={$paiProps['uid']} and vary=2") ||
			mysql_affected_rows($_pm['mysql']->getConn()) != 1)
		{
			unLockItem($bid);
			$_pm['mysql']->query('ROLLBACK');
			die('3');
		}
/**************提交事务*************/
        //$_pm['mysql']->query('COMMIT');

		// log of sql.
		$logsql .= "UPDATE userbag
					 SET uid={$uid},
						 psell=0,
						 psum=0,
						 pstime=0,
						 petime=0,
						 sums=1
				   WHERE psell>0 and psum>0 and id={$bid}";

		$rs = $_pm['mysql']->getOneRecord("SELECT uid
											 FROM userbag
											WHERE id={$bid} and uid={$uid}");
		if (!is_array($rs))
		{
			unLockItem($bid);
			$_pm['mysql']->query('ROLLBACK');
			die('3');
		}

		$check = updateUser($paiProps['uid'],$uid,$priceSum);
		if($check === false){
			unLockItem($bid);
			$_pm['mysql']->query('ROLLBACK');
			die('10');
		}
		if(!savelog($paiProps['uid'], $uid, '交易物品：'.$bid.' 1个.'.$logsql, 1))
		{
			unLockItem($bid);
			$_pm['mysql']->query('ROLLBACK');
			die('3');
		}

	}
	else if ($paiProps['vary']==1) // 可叠加!
	{
		if(empty($paiProps['buycode']))
		{
			$buycode = 0;
		}
		$logsql='sqllog:';

/****打开MYSQL事务，禁止自动提交****/
	    //$_pm['mysql']->query('START TRANSACTION');
		if(!empty($paiProps['buycode']))
		{
			if($paiProps['psum'] > $n){
				$updateOk = $_pm['mysql']->query("UPDATE userbag
								 SET psum=psum-{$n},buycode={$buycode}
							   WHERE psell>0 and psum>{$n} and id={$bid} and uid={$paiProps['uid']} and vary=1
							");
			}else{
				$updateOk = $_pm['mysql']->query("UPDATE userbag
								 SET psum=0,psj=0,psell=0,pyb=0,pstime=0,petime=0,buycode=0
							   WHERE psell>0 and psum={$n} and id={$bid} and uid={$paiProps['uid']} and vary=1
							");
			}
			if(!$updateOk || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
			{
				unLockItem($bid);
				$_pm['mysql']->query('ROLLBACK');
				die('3');
			}

/**************提交事务*************/
            //$_pm['mysql']->query('COMMIT');

			$logsql .= "UPDATE userbag
						 SET psum=psum-{$n},buycode={$buycode}
					   WHERE psell>0 and psum>={$n} and id={$bid}
					";

			$rs = $_pm['mysql']->getOneRecord("SELECT uid
												 FROM userbag
												WHERE id={$bid}");
		}
		else
		{
/****打开MYSQL事务，禁止自动提交****/
			//$_pm['mysql']->query('START TRANSACTION');
			if($paiProps['psum'] > $n){
				$updateOk = $_pm['mysql']->query("UPDATE userbag
								 SET psum=psum-{$n}
							   WHERE psell>0 and psum>{$n} and id={$bid} and uid={$paiProps['uid']} and vary=1
							");
			}else{
				$updateOk = $_pm['mysql']->query("UPDATE userbag
								 SET psum=0,psell=0,psj=0,pyb=0,pstime=0,petime=0,buycode=0
							   WHERE psell>0 and psum={$n} and id={$bid} and uid={$paiProps['uid']} and vary=1
							");
			}
			if(!$updateOk || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
			{
				unLockItem($bid);
				$_pm['mysql']->query('ROLLBACK');
				die('3');
			}
/**************提交事务*************/
            //$_pm['mysql']->query('COMMIT');

			$logsql .= "UPDATE userbag
						 SET psum=psum-{$n}
					   WHERE psell>0 and psum>={$n} and id={$bid}
					";

			$rs = $_pm['mysql']->getOneRecord("SELECT uid
												 FROM userbag
												WHERE id={$bid}");
		}

		if (!is_array($rs))
		{
			unLockItem($bid);
			$_pm['mysql']->query('ROLLBACK');
			die('3');
		}
		else
		{

			$hvd = $_pm['mysql']->getOneRecord("SELECT id,sums
												  FROM userbag
												 WHERE pid={$paiProps['pid']}
													   and vary=1
													   and zbing=0
													   and uid={$uid}
											   and {$stackTradeWhere}
											 LIMIT 1 FOR UPDATE
											 ");
			if (is_array($hvd))
			{
				$newSums = kdjlSafeNonNegativeSum(isset($hvd['sums']) ? $hvd['sums'] : 0, $n);
				if($newSums === false || $newSums <= 0)
				{
					unLockItem($bid);
					$_pm['mysql']->query('ROLLBACK');
					die('3');
				}
/****打开MYSQL事务，禁止自动提交****/
	            //$_pm['mysql']->query('START TRANSACTION');
				if(!$_pm['mysql']->query("UPDATE userbag
									     SET sums={$newSums},stime=".time()."
									   WHERE uid={$uid} and id={$hvd['id']} and zbing=0 and {$stackTradeWhere}
									") ||
					mysql_affected_rows($_pm['mysql']->getConn()) != 1)
				{
					unLockItem($bid);
					$_pm['mysql']->query('ROLLBACK');
					die('3');
				}
/**************提交事务*************/
                //$_pm['mysql']->query('COMMIT');

				$logsql .= ";UPDATE userbag
							    SET sums={$newSums},stime=".time()."
							  WHERE uid={$uid} and id={$hvd['id']} and zbing=0 and {$stackTradeWhere}
						   ";
			}
			else // Create new record.
			{
/****打开MYSQL事务，禁止自动提交****/
	            //$_pm['mysql']->query('START TRANSACTION');
				if(!$_pm['mysql']->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime,cantrade)
								VALUES(
								   {$uid},
								   {$paiProps['pid']},
								   {$paiProps['sell']},
								   {$paiProps['vary']},
								   {$n},
								   ".time().",
								   {$sourceCantrade}
								  )
						  ") ||
					mysql_affected_rows($_pm['mysql']->getConn()) != 1)
				{
					unLockItem($bid);
					$_pm['mysql']->query('ROLLBACK');
					die('3');
				}
/**************提交事务*************/
               // $_pm['mysql']->query('COMMIT');

				$logsql .= ";INSERT INTO userbag(uid,pid,sell,vary,sums,stime,cantrade)
								VALUES(
								   {$uid},
								   {$paiProps['pid']},
								   {$paiProps['sell']},
								   {$paiProps['vary']},
								   {$n},
								   ".time().",
								   {$sourceCantrade}
								  )
							";
			}
			$check = updateUser($paiProps['uid'],$uid,$priceSum);
			if($check === false){
				unLockItem($bid);
				$_pm['mysql']->query('ROLLBACK');
				die('10');
			}
			if(!savelog($paiProps['uid'], $uid, '交易物品：'.$bid.'物品ID：'.$pid.', '.$n.'个;'.$logsql, 1))
			{
				unLockItem($bid);
				$_pm['mysql']->query('ROLLBACK');
				die('3');
			}
        }
	}
	else
	{
		unLockItem($bid);
		$_pm['mysql']->query('ROLLBACK');
		die('3');
	}
}
if($e=mysql_error($_pm['mysql']->getConn())){
	$err='3';
	$_pm['mysql']->query('ROLLBACK');
	$paiBuyTransactionActive = false;
}else if(!$_pm['mysql']->query('COMMIT')){
	$err='3';
	$_pm['mysql']->query('ROLLBACK');
	$paiBuyTransactionActive = false;
}else{
	$paiBuyTransactionActive = false;
	$err=0;
	flushPaiLog();
	$_pm['mem']->del(MEM_USER_KEY);
	$_pm['mem']->del(MEM_USERBAG_KEY);
	$sellerUid = intval($paiProps['uid']);
	if($sellerUid > 0)
	{
		$_pm['mem']->del($sellerUid);
		$_pm['mem']->del($sellerUid.'bag');
	}
}
unLockItem($bid);
$paiBuyLockedItemId = 0;
$_pm['mem']->memClose();
echo $err;

function updateUser($selluid,$buyuid,$priceSum)
{
	global $_pm,$logsql;
	$selluid = intval($selluid);
	$buyuid = intval($buyuid);
	$priceSum = intval($priceSum);
	if($selluid <= 0 || $buyuid <= 0 || $priceSum <= 0) return false;

/****打开MYSQL事务，禁止自动提交****/
	$payOk = $_pm['mysql']->query("UPDATE player
							 SET money=money-{$priceSum}
						   WHERE id={$buyuid} and money >= $priceSum
							");
	$result = mysql_affected_rows($_pm['mysql'] -> getConn());
	if(!$payOk || $result != 1){
		return false;
	}
		// Update sell's user money.
	$settleOk = $_pm['mysql']->query("UPDATE player
							 SET paimoney=COALESCE(paimoney,0)+{$priceSum}
						   WHERE id={$selluid}
						");
	$result = mysql_affected_rows($_pm['mysql'] -> getConn());
	if(!$settleOk || $result != 1){
		return false;
	}

	$logsql .=";UPDATE player
				 SET money=money-{$priceSum}
			   WHERE id={$buyuid}
				";
    $logsql .=";UPDATE player
				 SET paimoney=COALESCE(paimoney,0)+{$priceSum}
			   WHERE id={$selluid}
			  ";
	return true;
}
function paiLockBuyerBagState($uid)
{
	global $_pm;
	$uid = intval($uid);
	$player = $_pm['mysql']->getOneRecord('SELECT maxbag FROM player WHERE id='.$uid.' FOR UPDATE');
	$bags = $_pm['mysql']->getRecords('SELECT pid,vary,sums,zbing,cantrade FROM userbag WHERE uid='.$uid.' FOR UPDATE');
	if(!is_array($player) || !is_array($bags)) return false;
	$count = 0;
	foreach($bags as $bag)
	{
		if(intval($bag['sums']) > 0 && intval($bag['zbing']) == 0) $count++;
	}
	return array('bags'=>$bags, 'count'=>$count, 'maxbag'=>intval($player['maxbag']));
}
// 保存交易日志
function savelog($sell, $buy, $note, $vary)
{
	global $pendingPaiLog;
	$sell = intval($sell);
	$buy = intval($buy);
	$vary = intval($vary);
	if($sell < 1 || $buy < 1 || $vary < 1) return false;
	$pendingPaiLog = array('sell'=>$sell, 'buy'=>$buy, 'note'=>strval($note), 'vary'=>$vary);
	return true;
}
function flushPaiLog()
{
	global $_pm, $pendingPaiLog;
	if(!is_array($pendingPaiLog)) return true;
	$note = $_pm['mysql']->escape($pendingPaiLog['note']);
	$ok = $_pm['mysql']->query("INSERT INTO gamelog(ptime,seller,buyer,pnote,vary) VALUES(".time().",".
		intval($pendingPaiLog['sell']).",".intval($pendingPaiLog['buy']).",'{$note}',".intval($pendingPaiLog['vary']).")");
	$pendingPaiLog = false;
	return $ok;
}
?>
