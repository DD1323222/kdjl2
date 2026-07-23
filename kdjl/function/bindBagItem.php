<?php
require_once('../config/config.game.php');
secStart($_pm['mem']);

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
$bagId = (isset($_REQUEST['id']) && !is_array($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0;
if($uid < 1) die('ERR|登录状态无效！');
if($bagId < 1) die('ERR|请先选择需要绑定的物品！');

require_once('../sec/dblock_fun.php');
$bindBagLockHeld = false;
$bindBagTransactionActive = false;

function bindBagItemFinish($message, $success)
{
	global $_pm, $bindBagLockHeld, $bindBagTransactionActive;
	$success = $success ? true : false;
	if($bindBagTransactionActive)
	{
		if($success)
		{
			if(!$_pm['mysql']->query('COMMIT'))
			{
				$_pm['mysql']->query('ROLLBACK');
				$success = false;
				$message = '保存绑定结果失败，请稍候再试！';
			}
		}
		else
		{
			$_pm['mysql']->query('ROLLBACK');
		}
		$bindBagTransactionActive = false;
	}
	if($success) $_pm['mem']->del(MEM_USERBAG_KEY);
	if($bindBagLockHeld)
	{
		realseLock();
		$bindBagLockHeld = false;
	}
	$_pm['mem']->memClose();
	die(($success ? 'OK|' : 'ERR|').$message);
}

$lock = getLock($uid);
if(!is_array($lock)) bindBagItemFinish('服务器繁忙，请稍候再试！', false);
$bindBagLockHeld = true;
$bindBagTransactionActive = true;

$source = $_pm['mysql']->getOneRecord(
	"SELECT b.id,b.pid,b.sums,b.bsum,b.psum,b.pyb,b.vary,b.stime,b.cantrade,b.zbing,\n".
	"       p.name,p.sell AS props_sell,p.vary AS props_vary,p.varyname,p.propslock\n".
	"  FROM userbag AS b\n".
	"  JOIN props AS p ON p.id=b.pid\n".
	" WHERE b.id={$bagId} AND b.uid={$uid} AND b.sums>0 AND b.zbing=0\n".
	" FOR UPDATE"
);
if(!is_array($source)) bindBagItemFinish('找不到选中的背包物品！', false);

$cantrade = isset($source['cantrade']) ? intval($source['cantrade']) : 0;
$propslock = isset($source['propslock']) ? intval($source['propslock']) : 0;
if($cantrade == 3) bindBagItemFinish('锁定物品不能执行绑定！', false);
if($cantrade == 2 || ($cantrade != 1 && $propslock == 0))
{
	bindBagItemFinish('该物品已经是绑定物品！', false);
}

// A trade override on a bound definition only needs the override removed.
if($propslock == 0 && $cantrade == 1)
{
	$updated = $_pm['mysql']->query(
		"UPDATE userbag SET cantrade=0\n".
		" WHERE id={$bagId} AND uid={$uid} AND sums>0 AND zbing=0 AND cantrade=1"
	);
	if(!$updated || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
	{
		bindBagItemFinish('保存绑定结果失败，请稍候再试！', false);
	}
	bindBagItemFinish('物品已成功绑定！', true);
}

$sourceName = isset($source['name']) ? strval($source['name']) : '';
if($sourceName === '') bindBagItemFinish('原物品名称为空，无法查找绑定版本！', false);
// Full-width brackets mark an independent bound variant, not a conversion target.
$suffixes = array('(绑定)');
$quotedNames = array();
$orderNames = array();
foreach($suffixes as $suffix)
{
	$name = $sourceName.$suffix;
	$quoted = $_pm['mysql']->quote($name);
	$quotedNames[] = $quoted;
	$orderNames[] = $quoted;
}
$sourcePid = intval($source['pid']);
$sourceVary = intval($source['props_vary']);
$sourceVaryname = intval($source['varyname']);
$target = $_pm['mysql']->getOneRecord(
	"SELECT id,name,sell,vary,varyname,propslock\n".
	"  FROM props\n".
	" WHERE id<>{$sourcePid} AND propslock=0\n".
	"   AND vary={$sourceVary} AND varyname={$sourceVaryname}\n".
	"   AND name IN(".implode(',', $quotedNames).")\n".
	" ORDER BY FIELD(name,".implode(',', $orderNames)."),id\n".
	" LIMIT 1"
);
if(!is_array($target)) bindBagItemFinish('该物品没有对应的绑定版本！', false);

$targetPid = intval($target['id']);
$targetSell = intval($target['sell']);
$targetVary = intval($target['vary']);
$amount = intval($source['sums']);
if($amount < 1) bindBagItemFinish('选中的物品数量无效！', false);

if($targetVary == 1)
{
	$destination = $_pm['mysql']->getOneRecord(
		"SELECT id,sums,stime\n".
		"  FROM userbag\n".
		" WHERE uid={$uid} AND pid={$targetPid} AND vary=1 AND sums>0 AND zbing=0\n".
		"   AND (cantrade IS NULL OR cantrade IN(0,2))\n".
		" ORDER BY id LIMIT 1 FOR UPDATE"
	);
	if(is_array($destination))
	{
		$newSums = kdjlSafeNonNegativeSum($destination['sums'], $amount);
		if($newSums === false) bindBagItemFinish('绑定后物品数量超过系统上限！', false);
		$destinationId = intval($destination['id']);
		$oldSums = intval($destination['sums']);
		$updated = $_pm['mysql']->query(
			"UPDATE userbag SET sums={$newSums},cantrade=0\n".
			" WHERE id={$destinationId} AND uid={$uid} AND pid={$targetPid}\n".
			"   AND sums={$oldSums} AND vary=1 AND zbing=0\n".
			"   AND (cantrade IS NULL OR cantrade IN(0,2))"
		);
		if(!$updated || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
		{
			bindBagItemFinish('合并绑定物品失败，请稍候再试！', false);
		}
	}
	else
	{
		$sourceTime = intval($source['stime']);
		if($sourceTime < 1) $sourceTime = time();
		$inserted = $_pm['mysql']->query(
			"INSERT INTO userbag(uid,pid,sell,vary,sums,stime,cantrade)\n".
			"VALUES({$uid},{$targetPid},{$targetSell},1,{$amount},{$sourceTime},0)"
		);
		if(!$inserted || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
		{
			bindBagItemFinish('生成绑定物品失败，请稍候再试！', false);
		}
	}

	$removed = $_pm['mysql']->query(
		"UPDATE userbag SET sums=0\n".
		" WHERE id={$bagId} AND uid={$uid} AND pid={$sourcePid}\n".
		"   AND sums={$amount} AND zbing=0 AND (cantrade IS NULL OR cantrade IN(0,1))"
	);
	if(!$removed || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
	{
		bindBagItemFinish('扣除原物品失败，请稍候再试！', false);
	}
	if(!$_pm['mysql']->query(
		"DELETE FROM userbag\n".
		" WHERE id={$bagId} AND uid={$uid} AND sums=0 AND bsum=0 AND psum=0 AND pyb=0 AND zbing=0"
	))
	{
		bindBagItemFinish('清理原物品失败，请稍候再试！', false);
	}
}
else
{
	if($amount != 1 || intval($source['bsum']) > 0 || intval($source['psum']) > 0 || intval($source['pyb']) > 0)
	{
		bindBagItemFinish('该非叠加物品同时存在其他位置数据，暂时不能绑定！', false);
	}
	$updated = $_pm['mysql']->query(
		"UPDATE userbag\n".
		"   SET pid={$targetPid},sell={$targetSell},vary={$targetVary},cantrade=0\n".
		" WHERE id={$bagId} AND uid={$uid} AND pid={$sourcePid}\n".
		"   AND sums=1 AND bsum=0 AND psum=0 AND pyb=0 AND zbing=0\n".
		"   AND (cantrade IS NULL OR cantrade IN(0,1))"
	);
	if(!$updated || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
	{
		bindBagItemFinish('保存绑定结果失败，请稍候再试！', false);
	}
}

$targetName = isset($target['name']) ? strval($target['name']) : '绑定物品';
bindBagItemFinish('已转换为“'.$targetName.'”，数量：'.$amount.'。', true);
?>
