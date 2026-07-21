<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.05.29
*@Usage:Fightting Display
*@Note: none
Mem style.
*/
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');
require_once(dirname(__FILE__).'/saolei_common.php');
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1)
{
	die();
}
$lock = getLock($uid);
if(!is_array($lock))
{
	realseLock();
	die();
}
$dieDealLocked = true;
$dieDealTransactionActive = false;
$dieDealStateChanged = false;
$dieDealOldPlayed = false;
$dieDealOldTicket = false;
$dieDealOldDie = 0;
$dieDealUserChanged = false;
$dieDealBagChanged = false;
function dieDealRestoreState()
{
	global $_pm,$uid,$dieDealStateChanged,$dieDealOldPlayed,$dieDealOldTicket,$dieDealOldDie;
	if(!$dieDealStateChanged) return;
	slTodayUserSet($_pm['mem'], $uid, $dieDealOldPlayed);
	slTodayTicketSet($_pm['mem'], $uid, $dieDealOldTicket);
	if($dieDealOldDie > 0) slDieOptionSet($_pm['mem'], $uid, $dieDealOldDie);
	else slDieOptionClear($_pm['mem'], $uid);
	$dieDealStateChanged = false;
}
function dieDealShutdown()
{
	global $_pm,$dieDealLocked,$dieDealTransactionActive;
	if($dieDealTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		dieDealRestoreState();
		$dieDealTransactionActive = false;
	}
	if($dieDealLocked && function_exists('realseLock'))
	{
		realseLock();
		$dieDealLocked = false;
	}
}
register_shutdown_function('dieDealShutdown');
$cmd = (isset($_GET['cmd']) && !is_array($_GET['cmd'])) ? $_GET['cmd'] : '';
switch($cmd)
{
	case 'cancel':
	case 'new':
	{
		$oldPlayed = $dieDealOldPlayed = slTodayUserHas($_pm['mem'], $uid);
		$oldTicket = $dieDealOldTicket = slTodayTicketHas($_pm['mem'], $uid);
		$oldDie = $dieDealOldDie = slDieOptionFind($_pm['mem'], $uid);
		$dieDealTransactionActive = true;
		if(!$_pm['mysql']->query("INSERT INTO player_ext(uid,bbshow,F_saolei_points) VALUES (".$uid.",5,1) ON DUPLICATE KEY UPDATE F_saolei_points=VALUES(F_saolei_points)"))
		{
			$_pm['mysql']->query('ROLLBACK');
			break;
		}
		$dieDealStateChanged = true;
		if(!slTodayUserSet($_pm['mem'], $uid, true) ||
		   !slTodayTicketSet($_pm['mem'], $uid, false) ||
		   !slDieOptionClear($_pm['mem'], $uid))
		{
			slTodayUserSet($_pm['mem'], $uid, $oldPlayed);
			slTodayTicketSet($_pm['mem'], $uid, $oldTicket);
			if($oldDie > 0) slDieOptionSet($_pm['mem'], $uid, $oldDie);
			else slDieOptionClear($_pm['mem'], $uid);
			$_pm['mysql']->query('ROLLBACK');
			break;
		}
		if(!$_pm['mysql']->query('COMMIT'))
		{
			$_pm['mysql']->query('ROLLBACK');
			slTodayUserSet($_pm['mem'], $uid, $oldPlayed);
			slTodayTicketSet($_pm['mem'], $uid, $oldTicket);
			if($oldDie > 0) slDieOptionSet($_pm['mem'], $uid, $oldDie);
			else slDieOptionClear($_pm['mem'], $uid);
		}
		else
		{
			$dieDealTransactionActive = false;
			$dieDealStateChanged = false;
			$dieDealUserChanged = true;
		}
		break;
	}
	case 'used':
	{
		$option = slDieOptionFind($_pm['mem'], $uid);
		if($option < 1 || !slTodayUserHas($_pm['mem'], $uid) || slTodayTicketHas($_pm['mem'], $uid)) break;
		$dieDealTransactionActive = true;
		$sl_fhbag = $_pm['mysql'] -> getOneRecord("SELECT id,sums
		                                             FROM userbag
		                                            WHERE pid=4038
		                                              AND uid=".$uid."
		                                              AND sums>0
		                                              AND zbing=0
		                                              AND (cantrade IS NULL OR cantrade<>3)
		                                         ORDER BY id LIMIT 1 FOR UPDATE");
		if(!is_array($sl_fhbag) || intval($sl_fhbag['sums']) < 1)
		{
			$_pm['mysql'] -> query('ROLLBACK');
			break;
		}
		$bagid = intval($sl_fhbag['id']);
		$itemUsed = $_pm['mysql'] -> query("UPDATE userbag
		                           SET sums=sums-1
		                         WHERE id=".$bagid."
		                           AND pid=4038
		                           AND uid=".$uid."
		                           AND sums>0
		                           AND zbing=0
		                           AND (cantrade IS NULL OR cantrade<>3)");
		if(!$itemUsed || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
		{
			$_pm['mysql'] -> query('ROLLBACK');
			break;
		}
		if(!$_pm['mysql'] -> query("INSERT INTO player_ext(uid,bbshow,F_saolei_points) VALUES (".$uid.",5,".$option.") ON DUPLICATE KEY UPDATE F_saolei_points=VALUES(F_saolei_points)"))
		{
			$_pm['mysql'] -> query('ROLLBACK');
			break;
		}
		if(!$_pm['mysql'] -> query("DELETE FROM userbag WHERE id=".$bagid." AND pid=4038 AND uid=".$uid." AND sums=0 AND bsum=0 AND psum=0 AND pyb=0 AND zbing=0 AND (cantrade IS NULL OR cantrade<>3)"))
		{
			$_pm['mysql'] -> query('ROLLBACK');
			break;
		}
		$dieDealOldPlayed = slTodayUserHas($_pm['mem'], $uid);
		$dieDealOldTicket = slTodayTicketHas($_pm['mem'], $uid);
		$dieDealOldDie = $option;
		$dieDealStateChanged = true;
		if(!slTodayTicketSet($_pm['mem'], $uid, true) || !slDieOptionClear($_pm['mem'], $uid))
		{
			slTodayTicketSet($_pm['mem'], $uid, false);
			slDieOptionSet($_pm['mem'], $uid, $option);
			$_pm['mysql']->query('ROLLBACK');
			break;
		}
		if(!$_pm['mysql'] -> query('COMMIT'))
		{
			$_pm['mysql'] -> query('ROLLBACK');
			slTodayTicketSet($_pm['mem'], $uid, false);
			slDieOptionSet($_pm['mem'], $uid, $option);
			break;
		}
		$dieDealTransactionActive = false;
		$dieDealStateChanged = false;
		$dieDealUserChanged = true;
		$dieDealBagChanged = true;
		break;
	}
	default :
	{
		break;
	}
}
if($dieDealTransactionActive)
{
	$_pm['mysql']->query('ROLLBACK');
	dieDealRestoreState();
	$dieDealTransactionActive = false;
}
if($dieDealUserChanged) $_pm['mem']->del(MEM_USER_KEY);
if($dieDealBagChanged) $_pm['mem']->del(MEM_USERBAG_KEY);
dieDealShutdown();
die();
?>
