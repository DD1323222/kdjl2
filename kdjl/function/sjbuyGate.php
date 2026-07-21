<?php
//die('维护中……');
//exit();
require_once('../config/config.game.php');

$m	= $_pm['mem'];
$db = &$_pm['mysql'];
$u	= $_pm['user'];
secStart($m);
$err = 0;
//---------------------------
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
$sjbuyTransactionActive = false;
$sjbuyLockedBid = 0;
$sjbuyPendingLogId = 0;

function sjbuyShutdown()
{
	global $_pm, $sjbuyTransactionActive, $sjbuyLockedBid, $sjbuyPendingLogId;
	$error = error_get_last();
	if(!is_array($error) || !in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) return;
	if($sjbuyTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$sjbuyTransactionActive = false;
	}
	if($sjbuyPendingLogId > 0)
	{
		$_pm['mysql']->query('DELETE FROM gamelog WHERE id='.intval($sjbuyPendingLogId));
		$sjbuyPendingLogId = 0;
	}
	if($sjbuyLockedBid > 0)
	{
		unLockItem($sjbuyLockedBid);
		$sjbuyLockedBid = 0;
	}
}
register_shutdown_function('sjbuyShutdown');

if($uid <= 0)
{
	die('1');
}
del_bag_expire();
$user	= $u->getUserById($uid);
if(!is_array($user)) {die('1');}
$bags    = $u->getUserBagById($uid);
if(!is_array($bags)) $bags = array();
if(!isset($user['maxbag'])) $user['maxbag'] = 0;
if(!$_pm['mysql']->query("INSERT INTO player_ext(uid,bbshow) VALUES({$uid},5) ON DUPLICATE KEY UPDATE uid=uid"))
{
	die('1');
}
$arr = $_pm['mysql'] -> getOneRecord("SELECT sj FROM player_ext WHERE uid = {$uid}");
if(is_array($arr)){
	$user['sj'] = $arr['sj'];
}else{
	$user['sj'] = 0;
}

$bid = (isset($_REQUEST['bid']) && !is_array($_REQUEST['bid'])) ? intval($_REQUEST['bid']) : 0; // table: props => id
$n	 = (isset($_REQUEST['n']) && !is_array($_REQUEST['n'])) ? intval($_REQUEST['n']) : 0;

if($bid < 1 || $n < 1 || $n > 100)
{
	die('2');
}

if(lockItem($bid) === false)
{
	die('已经在处理了！');
}
$sjbuyLockedBid = $bid;

if($n <= 0)
{
	unLockItem($bid);
	die('2');
}

if($bid<1 || $n>100)
{
	unLockItem($bid);
	die('2');
}

/*$wp= $m->dataGet(array('k' => MEM_PROPS_KEY,
					   'v' => "if(\$rs['id'] == '{$bid}' && \$rs['yb']>0) \$ret=\$rs;"
				 ));*/
$wp = $_pm['mysql'] -> getOneRecord("SELECT * FROM props WHERE id = $bid and sj > 0 and sj < 99999 and stime > 0 and LEFT(CAST(stime AS CHAR),1) IN (1,2,3,4)");
if (!is_array($wp)){
	unLockItem($bid);
	die('101');
}
if(!isset($wp['id'])) $wp['id'] = 0;
if(!isset($wp['name'])) $wp['name'] = '';
if(!isset($wp['vary'])) $wp['vary'] = 0;
if(!isset($wp['sell'])) $wp['sell'] = 0;
if(!isset($wp['sj'])) $wp['sj'] = 0;
if(!isset($wp['timelimit'])) $wp['timelimit'] = '';
//增加自动上下架的功能
if(!empty($wp['timelimit'])){
	$limitarr = explode('|',$wp['timelimit']);
	$nowtime = date('YmdHi');
	if(!empty($limitarr[0]) && $nowtime < $limitarr[0]){
		unLockItem($bid);
		die('101');
	}
	if(!empty($limitarr[1]) && $nowtime > $limitarr[1]){
		unLockItem($bid);
		die('101');
	}
}
//增加自动上下架的功能在这里结束
// Get current bag props num.
$bagnum = 0;
$hasStackItem = false;
if (is_array($bags))
{
	foreach ($bags as $k => $v)
	{
		if(!is_array($v)) continue;
		if(!isset($v['sums'])) $v['sums'] = 0;
		if(!isset($v['zbing'])) $v['zbing'] = 0;
		if(!isset($v['pid'])) $v['pid'] = 0;
		if(!isset($v['vary'])) $v['vary'] = 0;
		if(!isset($v['cantrade'])) $v['cantrade'] = 0;
		if ($v['sums']>0 && $v['zbing']==0) $bagnum++;
		if ($v['sums']>0 && $v['zbing']==0 && intval($v['vary']) == 1 && $v['pid']==$bid && intval($v['cantrade']) == 0) $hasStackItem = true;
	}
}

if (!is_array($wp)){
	unLockItem($bid);
	die('3');
}
if(intval($wp['vary']) != 1 && intval($wp['vary']) != 2)
{
	unLockItem($bid);
	die('3');
}
if(intval($wp['sell']) < 0)
{
	unLockItem($bid);
	die('3');
}
$needBagSlot = ($wp['vary']==2) ? $n : ($hasStackItem ? 0 : 1);
if(($bagnum+$needBagSlot)>$user['maxbag']){
	unLockItem($bid);
	die('4');
}
else
{
	$price = kdjlSafePositiveProduct($wp['sj'], $n);
	if($price === false)
	{
		unLockItem($bid);
		die("3");
	}
	$nowCoin = $user['sj'];

	if ($price > $nowCoin)
	{
		unLockItem($bid);
		die('10');
	}
	else
	{
		if(!$db->query('START TRANSACTION')) { unLockItem($bid); $sjbuyLockedBid = 0; $m->memClose(); die('3'); }
		$sjbuyTransactionActive = true;
		$lockedWp = $db->getOneRecord("SELECT * FROM props WHERE id={$bid} and sj>0 and sj<99999 and stime>0 and LEFT(CAST(stime AS CHAR),1) IN (1,2,3,4) FOR UPDATE");
		if(!is_array($lockedWp))
		{
			$db->query('ROLLBACK');
			$sjbuyTransactionActive = false;
			unLockItem($bid);
			$sjbuyLockedBid = 0;
			die('101');
		}
		if((intval($lockedWp['vary']) != 1 && intval($lockedWp['vary']) != 2) || intval($lockedWp['sell']) < 0)
		{
			$db->query('ROLLBACK');
			$sjbuyTransactionActive = false;
			unLockItem($bid);
			$sjbuyLockedBid = 0;
			die('3');
		}
		$lockedLimit = isset($lockedWp['timelimit']) ? $lockedWp['timelimit'] : '';
		if($lockedLimit !== '')
		{
			$lockedLimitParts = explode('|', $lockedLimit);
			$lockedNow = date('YmdHi');
			if((!empty($lockedLimitParts[0]) && $lockedNow < $lockedLimitParts[0]) || (!empty($lockedLimitParts[1]) && $lockedNow > $lockedLimitParts[1]))
			{
				$db->query('ROLLBACK');
				$sjbuyTransactionActive = false;
				unLockItem($bid);
				$sjbuyLockedBid = 0;
				die('101');
			}
		}
		$wp = $lockedWp;
		$price = kdjlSafePositiveProduct($wp['sj'], $n);
		if($price === false)
		{
			$db->query('ROLLBACK');
			$sjbuyTransactionActive = false;
			unLockItem($bid);
			$sjbuyLockedBid = 0;
			die('3');
		}
		$lockedPlayer = $db->getOneRecord("SELECT maxbag FROM player WHERE id={$uid} FOR UPDATE");
		$lockedBags = $db->getRecords("SELECT pid,vary,sums,zbing,cantrade FROM userbag WHERE uid={$uid} FOR UPDATE");
		if(!is_array($lockedPlayer) || !is_array($lockedBags))
		{
			$db->query('ROLLBACK');
			$sjbuyTransactionActive = false;
			unLockItem($bid);
			$sjbuyLockedBid = 0;
			die('3');
		}
		$lockedBagNum = 0;
		$lockedHasStack = false;
		foreach($lockedBags as $lockedBag)
		{
			if(intval($lockedBag['sums']) > 0 && intval($lockedBag['zbing']) == 0) $lockedBagNum++;
			if(intval($lockedBag['sums']) > 0 && intval($lockedBag['zbing']) == 0 &&
				intval($lockedBag['vary']) == 1 && intval($lockedBag['pid']) == $bid &&
				(!isset($lockedBag['cantrade']) || intval($lockedBag['cantrade']) == 0)) $lockedHasStack = true;
		}
		$lockedNeedSlot = intval($wp['vary']) == 2 ? $n : ($lockedHasStack ? 0 : 1);
		if($lockedBagNum + $lockedNeedSlot > intval($lockedPlayer['maxbag']))
		{
			$db->query('ROLLBACK');
			$sjbuyTransactionActive = false;
			unLockItem($bid);
			$sjbuyLockedBid = 0;
			die('4');
		}
		if(!$db->query("UPDATE player_ext SET sj=sj-{$price} WHERE uid={$uid} and sj >= {$price}") || mysql_affected_rows($db->getConn()) != 1)
		{
			$db->query('ROLLBACK');
			$sjbuyTransactionActive = false;
			unLockItem($bid);
			$sjbuyLockedBid = 0;
			die('10');
		}
		$purchaseOk = true;
		//----------------------------
		$now = time();
		$number = $n;
		$logPropName = $db->escape($wp['name']);

		#########################################################

		if ($wp['vary']==2) //不能叠加
		{
			for ($i=0; $i<$n; $i++)
			{
			    if($db->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
							VALUES(
								   {$uid},
								   {$bid},
								   {$wp['sell']},
									2,
								   1,
								   unix_timestamp()
								  );
						  ") === false) $purchaseOk = false;
				if($purchaseOk && mysql_affected_rows($db->getConn()) != 1) $purchaseOk = false;
			}
		}
		else
		{
			$ret = $db->getOneRecord("SELECT id FROM userbag
									 WHERE uid={$uid} and pid={$bid} and vary=1 and zbing=0 and (cantrade IS NULL OR cantrade=0)
									 LIMIT 0,1 FOR UPDATE
								  ");

			if (is_array($ret))
			{

				if($db->query("UPDATE userbag
							   SET sums=COALESCE(sums,0)+{$n},stime=".time()."
							 WHERE uid={$uid} and id={$ret['id']} and vary=1 and COALESCE(sums,0) <= 2147483647-{$n} and zbing=0 and (cantrade IS NULL OR cantrade=0)
						  ") === false) $purchaseOk = false;
				if($purchaseOk && mysql_affected_rows($db->getConn()) != 1) $purchaseOk = false;

			}
			else //create new data
			{
				if($db->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
							VALUES(
								   {$uid},
								   {$bid},
								   {$wp['sell']},
									1,
									{$n},
								   unix_timestamp());
						  ") === false) $purchaseOk = false;
				if($purchaseOk && mysql_affected_rows($db->getConn()) != 1) $purchaseOk = false;

			}
		}
		if($purchaseOk)
		{
			$logOk = $db->query("INSERT INTO gamelog (ptime,seller,buyer,pnote,vary) VALUES (".time().",{$uid},{$uid},'购买道具{$logPropName} {$n} 个',101)");
			if($logOk) $sjbuyPendingLogId = intval(mysql_insert_id($db->getConn()));
			if(!$logOk || mysql_affected_rows($db->getConn()) != 1) $purchaseOk = false;
		}
		if(!$purchaseOk || !$db->query('COMMIT'))
		{
			$db->query('ROLLBACK');
			$sjbuyTransactionActive = false;
			if($sjbuyPendingLogId > 0)
			{
				$db->query('DELETE FROM gamelog WHERE id='.intval($sjbuyPendingLogId));
				$sjbuyPendingLogId = 0;
			}
			$err = 3;
		}
		else
		{
			$sjbuyTransactionActive = false;
			$sjbuyPendingLogId = 0;
			$m->del(MEM_USER_KEY);
			$m->del(MEM_USERBAG_KEY);
		}
	}	// end inner else
}
unset($user,$wp);
$m->memClose();
echo $err;
unLockItem($bid);
$sjbuyLockedBid = 0;
?>
