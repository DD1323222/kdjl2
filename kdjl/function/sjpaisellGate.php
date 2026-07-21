<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

@Usage: PAI sell server Gate
@Write date: 2008.05.14
@Update date: 2008.07.16
@Note:
	主要调整拍卖为数据库方式。
*/
session_start();
require_once('../config/config.game.php');
if (!defined('MAX_PAI_VALIDTIME'))
	define('MAX_PAI_VALIDTIME', 10800); // 用户有效的拍卖时间

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
		die("12");//没有达到间隔时间
	}
	else
	{
		$_SESSION[$timeKey] = time();
	}
}

$err = 0;
$sjPaiSellTransactionActive = false;
$sjPaiSellLockedItemId = 0;

function sjPaiSellShutdown()
{
	global $_pm, $sjPaiSellTransactionActive, $sjPaiSellLockedItemId;
	$error = error_get_last();
	if(!is_array($error) || !in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) return;
	if($sjPaiSellTransactionActive) $_pm['mysql']->query('ROLLBACK');
	$sjPaiSellTransactionActive = false;
	if($sjPaiSellLockedItemId > 0)
	{
		unLockItem($sjPaiSellLockedItemId);
		$sjPaiSellLockedItemId = 0;
	}
}
register_shutdown_function('sjPaiSellShutdown');
$user		 = $_pm['user']->getUserById($uid);
if(!is_array($user)) die('1');
$userbag	 = $_pm['user']->getUserBagById($uid);
if(!is_array($userbag)) $userbag = array();
$bid 	= (isset($_REQUEST['bid']) && !is_array($_REQUEST['bid'])) ? intval($_REQUEST['bid']) : 0; // table userbag -> id
$n	 	= (isset($_REQUEST['n']) && !is_array($_REQUEST['n'])) ? intval($_REQUEST['n']) : 0;	// num
if($n <= 0)
{
	die('2');
}
if($bid <= 0)
{
	die('2');
}
if(lockItem($bid) === false)
{
	die('已经在处理了！');
}
$sjPaiSellLockedItemId = $bid;
$price	= (isset($_REQUEST['p']) && !is_array($_REQUEST['p'])) ? intval($_REQUEST['p']) : 0;	// price

if($price < 10){
	unLockItem($bid);
	die('20');
}

if($price <= 0)
{
	unLockItem($bid);
	die('2');
}
if(kdjlSafePositiveProduct($price, $n) === false)
{
	unLockItem($bid);
	die('2');
}

$bp = (isset($_REQUEST['bp']) && !is_array($_REQUEST['bp'])) ? $_REQUEST['bp'] : '';
$buycode = crc32($bp);
$buycode = $buycode<0?(1-$buycode-1):$buycode;

$_arr = new arrays();
$wp = $_arr->dataGet(array('k' => MEM_USERBAG_KEY,
							   'v' => "if(\$rs['uid'] == '{$uid}' && \$rs['id'] == '{$bid}' && \$rs['zbing']==0) \$ret=\$rs;"
						      ),
						 $userbag
						);
if (!is_array($wp))
{
	unLockItem($bid);
	die('10');
}


//玩家如果有东西在拍卖所就不让他再拍卖这样东西了
if(!empty($wp['psum']) || !empty($wp['psell']) || !empty($wp['psj']) || !empty($wp['pyb']))
{
	unLockItem($bid);
	die('11');
}

if ( $_pm['user']->check(array('int' => array($bid, $n, $price))) === false ||
	 $n > 100 ||
	 $price < 1 ||
	 $price > 9999999) $err = 2;
else
{
	//检测物品是否可交易
	/*
	$propslock = $_pm['mysql'] -> getOneRecord("SELECT props.propslock FROM props,userbag WHERE props.id = userbag.pid and userbag.id = {$bid} and uid = {$_SESSION['id']}");
	*/

	// check psell num.
	$painum = 0;
	if(is_array($userbag)) foreach ($userbag as $x => $y)
	{
		if(!is_array($y)) continue;
		if(!isset($y['psj'])) $y['psj'] = 0;
		if(!isset($y['psum'])) $y['psum'] = 0;
		if ($y['psj']>0 && $y['psum']>0) $painum++;
		if ($painum > 3) {
			unLockItem($bid);
			die("4");
		}
	}

	$wp= $arrobj->dataGet(array('k' => MEM_USERBAG_KEY,
							    'v' => "if(\$rs['id'] == '{$bid}' && \$rs['uid'] == '{$uid}') \$ret=\$rs;"
							   ),
						  $userbag
						 );
	if(is_array($wp))
	{
		if(!isset($wp['sums'])) $wp['sums'] = 0;
		if(!isset($wp['vary'])) $wp['vary'] = 0;
		if(!isset($wp['pid'])) $wp['pid'] = 0;
	}

	if (!is_array($wp) || $n > $wp['sums']) $err=3;
	else
	{   // 是否可交易检查
		$wpVary = intval($wp['vary']);
		if($wpVary == 2 && $n != 1)
		{
			unLockItem($bid);
			die('2');
		}
		$propslock = $_pm['mem']->dataGet(array('k' => MEM_PROPS_KEY,
												'v' => "if(\$rs['id'] == '{$wp['pid']}') \$ret=\$rs;"
										 ));

		$wpInfo = $_pm['mysql']->getOneRecord("SELECT cantrade
											 FROM userbag
											 WHERE id='$bid' and uid=".$uid
											 );
		if(!is_array($wpInfo)){
			unLockItem($bid);
			die("5");
		}
		if(!isset($wpInfo['cantrade'])) $wpInfo['cantrade'] = 0;
		$wpCantrade = isset($wpInfo['cantrade']) ? intval($wpInfo['cantrade']) : 0;
		$cantradeWhere = ($wpCantrade == 0) ? '(cantrade=0 OR cantrade IS NULL)' : 'cantrade='.$wpCantrade;
		if(!is_array($propslock)) $propslock = array('propslock'=>0);
		if(!isset($propslock['propslock'])) $propslock['propslock'] = 0;
					//0为不可交易            //0 为可交易
		if($wpInfo['cantrade'] == 0){
			if($propslock['propslock']  == 0){
				unLockItem($bid);
				die("5");
			}
		}else if($wpInfo['cantrade'] != 1){
			unLockItem($bid);
			die("5");
		}
		/*if($propslock['propslock']  == 0 && $wpInfo['cantrade']!=1 ){
			unLockItem($bid);
			die("5");
		}*/




		$now = time();

		$num1 = (isset($_REQUEST['num1']) && !is_array($_REQUEST['num1'])) ? $_REQUEST['num1'] : '';
		$logNote = $_pm['mysql']->escape($num1);
		if(!$_pm['mysql']->query('START TRANSACTION')) { unLockItem($bid); $sjPaiSellLockedItemId = 0; $_pm['mem']->memClose(); die('3'); }
		$sjPaiSellTransactionActive = true;

		$et  = $now + MAX_PAI_VALIDTIME;

/****打开MYSQL事务，禁止自动提交****/
    if(!$_pm['mysql']->query("UPDATE userbag
					   SET psj={$price},
						   pstime={$now},
						   petime={$et},
						   sums=sums-{$n},
						   psum=psum+{$n},
						   buycode={$buycode}
					 WHERE id={$bid} and uid={$uid} and vary={$wpVary} and sums >= $n and zbing=0 and {$cantradeWhere} and psum=0 and psell=0 and psj=0 and pyb=0
				   "))
	{
		$_pm['mysql']->query('ROLLBACK');
		unLockItem($bid);
		die('3');
	}

/**************提交事务*************/
                $result = mysql_affected_rows($_pm['mysql'] -> getConn());
                if($result != 1){
                    $_pm['mysql']->query('ROLLBACK');
                    unLockItem($bid);
                    die('3');
                }
                if(!$_pm['mysql']->query('COMMIT')){
                    $_pm['mysql']->query('ROLLBACK');
					$sjPaiSellTransactionActive = false;
                    unLockItem($bid);
					$sjPaiSellLockedItemId = 0;
                    die('3');
                }
				$sjPaiSellTransactionActive = false;
				$_pm['mem']->del(MEM_USERBAG_KEY);
				$_pm['mysql']->query("insert into gamelog (ptime,seller,buyer,pnote,vary) values($now,{$uid},{$uid},'$logNote',155)");

	}
}
$_pm['mem']->memClose();
echo $err;
unLockItem($bid);
$sjPaiSellLockedItemId = 0;
?>
