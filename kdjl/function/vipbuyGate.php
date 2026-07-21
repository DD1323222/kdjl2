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
$vipbuyTransactionActive = false;
$vipbuyLockedBid = 0;
$vipbuyPendingLogId = 0;

function vipbuyShutdown()
{
	global $_pm, $vipbuyTransactionActive, $vipbuyLockedBid, $vipbuyPendingLogId;
	$error = error_get_last();
	if(!is_array($error) || !in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) return;
	if($vipbuyTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$vipbuyTransactionActive = false;
	}
	if($vipbuyPendingLogId > 0)
	{
		$_pm['mysql']->query('DELETE FROM gamelog WHERE id='.intval($vipbuyPendingLogId));
		$vipbuyPendingLogId = 0;
	}
	if($vipbuyLockedBid > 0)
	{
		unLockItem($vipbuyLockedBid);
		$vipbuyLockedBid = 0;
	}
}
register_shutdown_function('vipbuyShutdown');

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
if(!isset($user['vip'])) $user['vip'] = 0;

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
$vipbuyLockedBid = $bid;

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
$wp = $_pm['mysql'] -> getOneRecord("SELECT * FROM props WHERE id = $bid and vip > 0 and vip < 99999 and stime > 0 and LEFT(CAST(stime AS CHAR),1) IN (1,2,3,4)");
//vip 为0表示下架
if(!is_array($wp) || empty($wp['vip']))
{
	unLockItem($bid);
	die('101');
}
if(!isset($wp['id'])) $wp['id'] = 0;
if(!isset($wp['name'])) $wp['name'] = '';
if(!isset($wp['vary'])) $wp['vary'] = 0;
if(!isset($wp['sell'])) $wp['sell'] = 0;
if(!isset($wp['vip'])) $wp['vip'] = 0;
if(!isset($wp['timelimit'])) $wp['timelimit'] = '';
if(!empty($wp['timelimit']))
{
	$limitarr = explode('|',$wp['timelimit']);
	$nowtime = date('YmdHi');
	if((!empty($limitarr[0]) && $nowtime < $limitarr[0]) || (!empty($limitarr[1]) && $nowtime > $limitarr[1]))
	{
		unLockItem($bid);
		die('101');
	}
}
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
	unLockItem($bid);//2不可叠加
	die('4');
}
else
{
	$price = kdjlSafePositiveProduct($wp['vip'], $n);
	if($price === false)
	{
		unLockItem($bid);
		die("3");
	}
	$nowCoin = $user['vip'];

	if ($price > $nowCoin)
	{
		unLockItem($bid);
		die('10');
	}
	else
	{
		if(!$db->query('START TRANSACTION')) { unLockItem($bid); $vipbuyLockedBid = 0; $m->memClose(); die('3'); }
		$vipbuyTransactionActive = true;
		$lockedWp = $db->getOneRecord("SELECT * FROM props WHERE id={$bid} and vip>0 and vip<99999 and stime>0 and LEFT(CAST(stime AS CHAR),1) IN (1,2,3,4) FOR UPDATE");
		if(!is_array($lockedWp))
		{
			$db->query('ROLLBACK');
			$vipbuyTransactionActive = false;
			unLockItem($bid);
			$vipbuyLockedBid = 0;
			die('101');
		}
		if((intval($lockedWp['vary']) != 1 && intval($lockedWp['vary']) != 2) || intval($lockedWp['sell']) < 0)
		{
			$db->query('ROLLBACK');
			$vipbuyTransactionActive = false;
			unLockItem($bid);
			$vipbuyLockedBid = 0;
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
				$vipbuyTransactionActive = false;
				unLockItem($bid);
				$vipbuyLockedBid = 0;
				die('101');
			}
		}
		$wp = $lockedWp;
		$price = kdjlSafePositiveProduct($wp['vip'], $n);
		if($price === false)
		{
			$db->query('ROLLBACK');
			$vipbuyTransactionActive = false;
			unLockItem($bid);
			$vipbuyLockedBid = 0;
			die('3');
		}
		$lockedPlayer = $db->getOneRecord("SELECT maxbag,vip FROM player WHERE id={$uid} FOR UPDATE");
		$lockedBags = $db->getRecords("SELECT pid,vary,sums,zbing,cantrade FROM userbag WHERE uid={$uid} FOR UPDATE");
		if(!is_array($lockedPlayer) || !is_array($lockedBags))
		{
			$db->query('ROLLBACK');
			$vipbuyTransactionActive = false;
			unLockItem($bid);
			$vipbuyLockedBid = 0;
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
			$vipbuyTransactionActive = false;
			unLockItem($bid);
			$vipbuyLockedBid = 0;
			die('4');
		}
		if(!$db->query("UPDATE player SET vip=vip-{$price} WHERE id={$uid} and vip >= {$price}") || mysql_affected_rows($db->getConn()) != 1)
		{
			$db->query('ROLLBACK');
			$vipbuyTransactionActive = false;
			unLockItem($bid);
			$vipbuyLockedBid = 0;
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
			$logOk = $db->query("INSERT INTO gamelog (ptime,seller,buyer,pnote,vary) VALUES (".time().",{$uid},{$uid},'购买道具{$logPropName} {$n} 个',127)");
			if($logOk) $vipbuyPendingLogId = intval(mysql_insert_id($db->getConn()));
			if(!$logOk || mysql_affected_rows($db->getConn()) != 1) $purchaseOk = false;
		}
		if(!$purchaseOk || !$db->query('COMMIT'))
		{
			$db->query('ROLLBACK');
			$vipbuyTransactionActive = false;
			if($vipbuyPendingLogId > 0)
			{
				$db->query('DELETE FROM gamelog WHERE id='.intval($vipbuyPendingLogId));
				$vipbuyPendingLogId = 0;
			}
			$err = 3;
		}
		else
		{
			$vipbuyTransactionActive = false;
			$vipbuyPendingLogId = 0;
			$m->del(MEM_USER_KEY);
			$m->del(MEM_USERBAG_KEY);
		}
	}	// end inner else
}
unset($user,$wp);
$m->memClose();
echo $err;
unLockItem($bid);
$vipbuyLockedBid = 0;
?>
