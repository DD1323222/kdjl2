<?php
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');
secStart($_pm['mem']);
$zhTransactionActive = false;
$zhLockHeld = false;

function zhShutdown()
{
	global $_pm, $zhTransactionActive, $zhLockHeld;
	$error = error_get_last();
	if(!is_array($error) || !in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) return;
	if($zhTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$zhTransactionActive = false;
	}
	if($zhLockHeld && function_exists('realseLock'))
	{
		realseLock();
		$zhLockHeld = false;
	}
}
register_shutdown_function('zhShutdown');

function logs($note,$vary=103)
{
	global $_pm;
	$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
	$noteSql = $_pm['mysql']->escape($note);
	$sql='insert into gamelog set seller='.$uid.',vary='.intval($vary).',pnote="'.$noteSql.'",ptime='.time();
	return $_pm['mysql']->query($sql) && mysql_affected_rows($_pm['mysql']->getConn()) == 1;
}
function zhFail($message)
{
	global $_pm, $zhTransactionActive, $zhLockHeld;
	if($zhTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$zhTransactionActive = false;
	}
	if($zhLockHeld && function_exists('realseLock'))
	{
		realseLock();
		$zhLockHeld = false;
	}
	if(isset($_pm['mem'])) $_pm['mem']->memClose();
	die($message);
}
$petId = (isset($_GET['pid']) && !is_array($_GET['pid'])) ? abs(intval($_GET['pid'])) : 0;
$value = (isset($_GET['v']) && !is_array($_GET['v'])) ? abs(intval($_GET['v'])) : 0;
$uid=isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1 || $petId < 1 || $value <= 0) die('非法操作！');
$_pm['mysql']->addColumnIfMissing('player_ext', 'czl_ss', 'int(11) null default 0');
$a = getLock($uid);
if(!is_array($a))
{
	realseLock();
	die('服务器繁忙，请稍候再试！');
}
$zhLockHeld = true;
$zhTransactionActive = true;
$bb = $_pm['mysql']->getOneRecord('select name,wx,level,czl,remaketimes,remakelevel,remakeid,remakepid,old_bid,muchang,tgflag from userbb where uid='.$uid.' and id='.$petId.' for update');
if(!$bb)
{
	zhFail('这个宠物不是你的！');
}
if($bb['wx']!=7)
{
	zhFail('这个宠物不能接受转化！');
}
if(intval($bb['muchang']) != 0 || intval($bb['tgflag']) != 0)
{
	zhFail('该宠物当前状态不能转化！');
}

$membbname = kdjlSafeMemValue($_pm['mem']->get('db_bbname'), array());
$membbid = kdjlSafeMemValue($_pm['mem']->get('db_bbid'), array());
$bbO = resolveBasePetForZh($bb, $membbname, $membbid);

if(!$bbO)
{
	zhFail('内存中找不到要进化的宠物的原始数据！');
}

$bbJhSetting = $_pm['mysql']->getOneRecord('select max_czl from super_jh where pet_id='.$bbO['id']);
if(!$bbJhSetting)
{
	zhFail('数据库中没有该宠物神圣进化的设定！');
}

$zhCzl=$_pm['mysql']->getOneRecord('select czl_ss from player_ext where uid='.$uid.' for update');
if($err=mysql_error($_pm['mysql']->getConn()))
{
	if(strpos($err,'czl_ss')!==false)
	{
		zhFail('玩家成长池数据异常！');
	}
	$zhCzl['czl_ss']=0;
}
if(!is_array($zhCzl))
{
	$zhCzl = array('czl_ss' => 0);
}


$extraMsg='';

if($value+$bb['czl']>$bbJhSetting['max_czl'])
{
	$value=$bbJhSetting['max_czl']-$bb['czl'];
	$extraMsg='(该宠物最大成长率是:'.$bbJhSetting['max_czl'].')';
}
if($value <= 0)
{
	zhFail('该宠物的成长已达到上限！');
}
$cost = ceil($value);
if($cost > intval($zhCzl['czl_ss']))
{
	zhFail('剩余成长不够！');
}

$sqlPlayer = 'update player_ext set czl_ss=czl_ss-'.$cost.' where uid='.$uid.' and czl_ss>='.$cost;
if(!$_pm['mysql']->query($sqlPlayer) || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
{
	zhFail('剩余成长不够！');
}

$sqlBb = 'update userbb set czl='.($bb['czl']+$value).' where id='.$petId.' and uid='.$uid;
if(!$_pm['mysql']->query($sqlBb) || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
{
	zhFail('成长转化保存失败！');
}
if(!$_pm['mysql']->query('COMMIT'))
{
	zhFail('成长转化保存失败！');
}
$zhTransactionActive = false;
$_pm['mem']->del(MEM_USER_KEY);
$_pm['mem']->del(MEM_USERBB_KEY);
logs("转化{$value}成长给{$petId}");


function resolveBasePetForZh($pet, $byName, $byId)
{
	if(isset($pet['old_bid'])){
		$oldBid = intval($pet['old_bid']);
		if($oldBid > 0 && is_array($byId) && isset($byId[$oldBid]) && is_array($byId[$oldBid])){
			return $byId[$oldBid];
		}
	}
	if(is_array($byId)){
		foreach($byId as $basePet){
			if(!is_array($basePet) || !isset($basePet['name'])){
				continue;
			}
			if($basePet['name'] != $pet['name']){
				continue;
			}
			if((string)$basePet['remakelevel'] == (string)$pet['remakelevel'] &&
			   (string)$basePet['remakeid'] == (string)$pet['remakeid'] &&
			   (string)$basePet['remakepid'] == (string)$pet['remakepid']){
				return $basePet;
			}
		}
	}
	if(is_array($byName) && isset($byName[$pet['name']]) && is_array($byName[$pet['name']])){
		return $byName[$pet['name']];
	}
	return false;
}
realseLock();
$zhLockHeld = false;
$_pm['mem']->memClose();
die('OK');
?>
