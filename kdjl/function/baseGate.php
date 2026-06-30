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

secStart($_pm['mem']);

$srctime = 2;
#################增加一个间隔时间################
$time = $_SESSION['paitimes'.$_SESSION['id']];
if(empty($time))
{	
	$_SESSION['paitimes'.$_SESSION['id']] = time();
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
		$_SESSION['paitimes'.$_SESSION['id']] = time();
	}
}



$user	 = $_pm['user']->getUserById($_SESSION['id']);
//$userBag = $_pm['user']->getUserBagById($_SESSION['id']);

$bid = intval($_REQUEST['bid']);	// 包裹ID


if(empty($bid))
{
	die("10");
}

if(lockItem($bid) === false)
{
	die('已经在处理了！');
}

$parr = $_pm['user']->getUserItemById($_SESSION['id'],$bid);
$n = isset($_REQUEST['n']) ? intval($_REQUEST['n']) : 0; // 物品数量
$op = isset($_REQUEST['op']) ? $_REQUEST['op'] : '';
if(!is_array($parr) || $n <= 0)
{
	unLockItem($bid);
	die("10");
}
$bagsums = $_pm['mysql'] -> getOneRecord("SELECT count(id) as sum FROM userbag WHERE zbing = 0 and sums > 0 and uid = {$_SESSION['id']}");
$cksums = $_pm['mysql'] -> getOneRecord("SELECT count(id) as sum FROM userbag WHERE zbing = 0 and bsum > 0 and uid = {$_SESSION['id']}");
if ($n <= $parr['sums'] && $op == 's')
{
	if( ($parr['vary']==2 && ($n+$cksums['sum'])>$user['maxbase']) || 
	(($cksums['sum']+1) > $user['maxbase']) )
	{
		unLockItem($bid);
		die('4');
	}
	
/****打开MYSQL事务，禁止自动提交****/		
	$_pm['mysql']->query('START TRANSACTION');
	$_pm['mysql']->query("UPDATE userbag
							 SET sums=sums-{$n},bsum=bsum+{$n}
						   WHERE id={$bid} and uid={$_SESSION['id']} and sums >= $n and zbing = 0
						");
	$result = mysql_affected_rows($_pm['mysql'] -> getConn());
	if($result != 1){
		$_pm['mysql']->query('ROLLBACK');
		unLockItem($bid);
		die("10");
	}
/**************提交事务*************/
	if (!$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		unLockItem($bid);
		die('10');
	}

}
else if($n <= $parr['bsum'] && $op == 'g')
{
	if( ($parr['vary']==2 && ($n+$bagsums['sum'])>$user['maxbag']) || 
	(($bagsums['sum']+1) > $user['maxbag']) ){
		unLockItem($bid);
		die('5');
	}

/****打开MYSQL事务，禁止自动提交****/	
	$_pm['mysql']->query('START TRANSACTION');
	$_pm['mysql']->query("UPDATE userbag
							 SET sums=sums+{$n},bsum=bsum-{$n}
						   WHERE id={$bid} and uid={$_SESSION['id']} and bsum >= $n and zbing = 0
						");
/**************提交事务*************/
	$result = mysql_affected_rows($_pm['mysql'] -> getConn());
	if($result != 1){
		$_pm['mysql']->query('ROLLBACK');
		unLockItem($bid);
		die("10");
	}
	if (!$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		unLockItem($bid);
		die('10');
	}

}
else{
	unLockItem($bid);
	die('10');
}

unLockItem($bid);
die('0');
?>
