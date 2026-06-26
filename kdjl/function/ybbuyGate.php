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

//增加一个冷却时间
$srctime = 5;
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
		unLockItem($bid);
		die("5");//没有达到间隔时间
	}
	else
	{
		$_SESSION['paitimes'.$_SESSION['id']] = time();
	}
}

$err = 0;

$user		 = $_pm['user']->getUserById($_SESSION['id']);
$userbag	 = $_pm['user']->getUserBagById($_SESSION['id']);
//$sjarr = $_pm['mysql'] -> getOneRecord("SELECT yb FROM player WHERE id = {$_SESSION['id']}");
//if(is_array($sjarr)){
//	$user['yb'] = $sjarr['yb'];
//}else $user['yb'] = 0;

$bid = intval($_REQUEST['bid']); // TABLE: userbag => id

if(lockItem($bid) === false)
{
	die('已经在处理了！');
}

$n	 = intval($_REQUEST['n']);	 // Buy number
if($n <= 0)
{
	unLockItem($bid);
	die('2');
}
if ($_pm['user']->check(array('int' => $bid, 'int'=> $n))===false || $n>100)	{$_pm['mem']->memClose();
	unLockItem($bid);
	die("2");}

$paiProps = FALSE;
$now = time();
$type = 0;
//####################################################################

//## bag num
$bagNum=0;
foreach($userbag as $x=>$y)
{
	if($y['sums']>0 and $y['zbing']==0) $bagNum++;
}

$buycode = crc32($user['nickname']);
$buycode = $buycode<0?(1-$buycode-1):$buycode;

/*$paiProps = $_pm['mysql']->getOneRecord("SELECT * 
								 FROM userbag
								WHERE psell>0 and psum>0 and petime> {$now} and id={$bid}
							 ");*/
							 
$_pm['mysql']->query('START TRANSACTION');
$paiProps = $_pm['mysql']->getOneRecord("SELECT * 
								 FROM userbag
								WHERE id={$bid}  FOR UPDATE 
							 ");
$pid = $paiProps['pid'];
if (!is_array($paiProps) || $paiProps['psum']<$n ) {
	unLockItem($bid);
	$_pm['mysql']->query('ROLLBACK');
	die('3');
}

if($paiProps['pyb']<1||$paiProps['psum']<1)
{
	unLockItem($bid);
	$_pm['mysql']->query('ROLLBACK');
	die('拍卖设定错误！');
}

if($paiProps['uid']==$_SESSION['id'])
{
	unLockItem($bid);
	$_pm['mysql']->query('ROLLBACK');
	die('不允许购买自己的的东西!');
}

if (!is_array($paiProps) || $paiProps['psum']<$n ) {
	unLockItem($bid);$_pm['mysql']->query('ROLLBACK');
	die('3');
	}

if ($paiProps['buycode'] > 1 && $paiProps['buycode']!=$buycode){
	unLockItem($bid);$_pm['mysql']->query('ROLLBACK');
	die('7');
}

$priceSum = intval($paiProps['pyb']*$n);

if ($priceSum > $user['yb'])
{
	
	$err='10'; // Money too less.
	
}
else if(($bagNum+1)>$user['maxbag']) $err=5;
else
{	
	$status=false;
    if ($paiProps['vary']==2) // Not Repeat!
	{
		$logsql='logsql:';

/****打开MYSQL事务，禁止自动提交****/		
		
	    //$_pm['mysql']->query('START TRANSACTION');
		$_pm['mysql']->query("UPDATE userbag
							     SET uid={$_SESSION['id']}, 
									 psj=0, 
									 psum=0, 
									 pstime=0,
									 petime=0,
									 sums=1,
									 pyb=0 
							   WHERE pyb>0 and psum>0 and id={$bid}");
/**************提交事务*************/
       // $_pm['mysql']->query('COMMIT');

		// log of sql.
		$logsql .= "UPDATE userbag
					 SET uid={$_SESSION['id']}, 
						 psj=0, 
						 psum=0, 
						 pstime=0,
						 petime=0,
						 sums=1,
						 pyb=0 
				   WHERE pyb>0 and psum>0 and id={$bid}";

		$rs = $_pm['mysql']->getOneRecord("SELECT uid 
											 FROM userbag 
											WHERE id={$bid} and uid={$_SESSION['id']}");
		if (!is_array($rs))
		{
			unLockItem($bid);$_pm['mysql']->query('ROLLBACK');
			die('3');
		}
		
		$check = updateUser($paiProps['uid'],$_SESSION['id'],$priceSum);//给玩家减去和加上相应的金币
		if($check === false){
			unLockItem($bid);$_pm['mysql']->query('ROLLBACK');
			die('10');
		}
		savelog($paiProps['uid'], $_SESSION['id'], '交易物品：'.$bid.' 1个.'.$logsql, 108);

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
				$_pm['mysql']->query("UPDATE userbag
								 SET psum=psum-{$n},buycode={$buycode}
							   WHERE pyb>0 and psum>{$n} and id={$bid}
							");
			}else{
				$_pm['mysql']->query("UPDATE userbag
								 SET psum=0,buycode={$buycode},psell = 0,pyb = 0
							   WHERE pyb>0 and psum={$n} and id={$bid}
							");	
			}	
/**************提交事务*************/
           // $_pm['mysql']->query('COMMIT');

			$logsql .= "UPDATE userbag
						 SET psum=psum-{$n},buycode={$buycode}
					   WHERE pyb>0 and psum>={$n} and id={$bid}
					";
	
			$rs = $_pm['mysql']->getOneRecord("SELECT uid 
												 FROM userbag 
												WHERE id={$bid} and buycode={$buycode}");
		}
		else
		{
/****打开MYSQL事务，禁止自动提交****/		
	        //$_pm['mysql']->query('START TRANSACTION');
			if($paiProps['psum'] > $n){
				$_pm['mysql']->query("UPDATE userbag
								 SET psum=psum-{$n}
							   WHERE pyb>0 and psum>{$n} and id={$bid}
							");
			}else{
				$_pm['mysql']->query("UPDATE userbag
								 SET psum=0,psell = 0,pyb = 0
							   WHERE pyb>0 and psum={$n} and id={$bid}
							");
			}
/**************提交事务*************/
           //$_pm['mysql']->query('COMMIT');

			$logsql .= "UPDATE userbag
						 SET psum=psum-{$n}
					   WHERE pyb>0 and psum>={$n} and id={$bid}
					";
	
			$rs = $_pm['mysql']->getOneRecord("SELECT uid 
												 FROM userbag 
												WHERE id={$bid}");
		}
		
		if (!is_array($rs))
		{
			unLockItem($bid);$_pm['mysql']->query('ROLLBACK');
			die('3');
		}
		else
		{
			$hvd = $_pm['mysql']->getOneRecord("SELECT id 
												  FROM userbag
												 WHERE pid={$paiProps['pid']} 
													   and zbing=0 
													   and uid={$_SESSION['id']} 
											 ");
			if (is_array($hvd))
			{
/****打开MYSQL事务，禁止自动提交****/		
	            //$_pm['mysql']->query('START TRANSACTION');				
				$_pm['mysql']->query("UPDATE userbag 
									     SET sums=sums+{$n}
									   WHERE uid={$_SESSION['id']} and id={$hvd['id']} and sums+{$n}>0
									");
/**************提交事务*************/
                //$_pm['mysql']->query('COMMIT');

				$logsql .= ";UPDATE userbag 
							    SET sums=sums+{$n} 
							  WHERE uid={$_SESSION['id']} and id={$hvd['id']}
						   ";
			}
			else // Create new record.
			{
/****打开MYSQL事务，禁止自动提交****/
					
	            //$_pm['mysql']->query('START TRANSACTION');	
				$_pm['mysql']->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
								VALUES(
								   {$_SESSION['id']},
								   {$paiProps['pid']},
								   {$paiProps['sell']},
								   {$paiProps['vary']},
								   {$n},
								   ".time()."
								  )
						  ");
/**************提交事务*************/
                //$_pm['mysql']->query('COMMIT');

				$logsql .= ";INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
								VALUES(
								   {$_SESSION['id']},
								   {$paiProps['pid']},
								   {$paiProps['sell']},
								   {$paiProps['vary']},
								   {$n},
								   ".time()."
								  )
							";
			}
			$check = updateUser($paiProps['uid'],$_SESSION['id'],$priceSum);
			if($check === false){
				unLockItem($bid);$_pm['mysql']->query('ROLLBACK');
				die('10');
			}
			savelog($paiProps['uid'], $_SESSION['id'], '交易物品：'.$bid.'物品ID：'.$pid.', '.$n.'个;'.$logsql, 108);
        }	
	}
}
if($e=mysql_error()){
	$err=$e;
	$_pm['mysql']->query('ROLLBACK');
}else{
	$_pm['mysql']->query('commit');
}
unLockItem($bid);
$_pm['mem']->memClose();
echo $err;

function updateUser($selluid,$buyuid,$priceSum)
{
	global $_pm,$logsql;
	$newPriceSum = round($priceSum * 0.95);
/****打开MYSQL事务，禁止自动提交****/		
	$_pm['mysql']->query('START TRANSACTION');	
	$_pm['mysql']->query("UPDATE player 
							 SET yb=yb-{$priceSum}
						   WHERE id={$buyuid} and yb >= $priceSum
							");
	$result = mysql_affected_rows($_pm['mysql'] -> getConn());
	if($result != 1){
		return false;
	}
		// Update sell's user money.
	$_pm['mysql']->query("UPDATE player_ext
							 SET paiyb=paiyb+{$newPriceSum}
						   WHERE uid={$selluid}
						");
/****提交事务，失败则执行回滚操作****/
                if(!$_pm['mysql']->query('COMMIT')){
				    $_pm['mysql']->query('ROLLBACK');
				}

	$logsql .=";UPDATE player 
				 SET yb=yb-{$priceSum}
			   WHERE uid={$buyuid}
				";
    $logsql .=";UPDATE player_ext
				 SET paiyb=paiyb+{$newPriceSum}
			   WHERE id={$selluid}
			  ";
}
// 保存交易日志
function savelog($sell, $buy, $note, $vary)
{
	global $_pm;
	$_pm['mysql']->query("INSERT INTO gamelog(ptime,seller,buyer,pnote,vary)
						  VALUES(".time().",'{$sell}','{$buy}','{$note}','{$vary}')
						");
}
?>