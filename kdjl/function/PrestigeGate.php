<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

@Usage: Shop buy function
@Write date: 2008.05.02
@Update date: 2008.05.23
@Memo: Don't buy protect props.
     Fix: Max limit for buy props. (2008.06.22)
@##############################################
*/
require_once('../config/config.game.php');

secStart($_pm['mem']);
$err = 0;

$user		= $_pm['user']->getUserById($_SESSION['id']);
$bags		= $_pm['user']->getUserBagById($_SESSION['id']);

$bid = intval($_REQUEST['bid']); // table: props => id
$n	 = intval($_REQUEST['n']); 

if(lockItem($bid) === false)
{
	die('已经在处理了！');
}

if($n <= 0)
{
	unLockItem($bid);
	die('2');
}



if ($_pm['user']->check(array('int' => $bid, 'int' => $n)) === false || $n>10) 
{
	unLockItem($bid);
	die('2');
}
$mempropsid = unserialize($_pm['mem']->get('db_propsid'));
$wp = is_array($mempropsid) && isset($mempropsid[$bid]) ? $mempropsid[$bid] : false;
if(!is_array($wp) || $wp['sell'] < 0 || $wp['prestige'] <= 0)
{
	unLockItem($bid);
	die('3');
}
/*$wp= $_pm['mem']->dataGet(array('k' => MEM_PROPS_KEY, 
					   'v' => "if(\$rs['id'] == '{$bid}' && \$rs['sell']>0) \$ret=\$rs;"
				 ));*/
if (!is_array($wp))
{
	unLockItem($bid);
	die("3");
}

if ($wp['prestige']==0 || 
	$wp['id']==0 || 
	$wp['varyname']==9){
	unLockItem($bid);
	die("2");
}

// Get current bag props num.
$bagnum = 0;
if (is_array($bags))
{
	foreach ($bags as $k => $v)
	{
		if ($v['sums']>0 && $v['zbing']==0) $bagnum++;
	}
	unset($bags);
}

if( ($wp['vary']==2 && ($n+$bagnum)>$user['maxbag']) || 
	(($bagnum+1) > $user['maxbag']) ) $err= 4;
else
{
	$price = $wp['prestige']*$n;
	if ($price > $user['prestige'])
	{
		$err= 10; // prestige too less.
	}
	else
	{   
		$_pm['mysql']->query('START TRANSACTION');
		$_pm['mysql']->query("UPDATE player
						   SET prestige=prestige-{$price}
						 WHERE id={$_SESSION['id']} and prestige >= {$price}");
		if (mysql_affected_rows($_pm['mysql']->getConn()) != 1)
		{
			$_pm['mysql']->query('ROLLBACK');
			$err = 10;
		}
		else if ($wp['vary']==2) //不能叠加
		{
			$purchaseOk = true;
			for ($i=0; $i<$n; $i++) // Add to memory.
			{
			    //$newid = mem_get_autoid($m, MEM_ORDER_KEY,'userbag');
				if ($_pm['mysql']->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
							VALUES(
								   {$user['id']},
								   {$bid},
								   {$wp['sell']},
								   {$wp['vary']},
								   1,
								   unix_timestamp()
								  );
						  ") === false) $purchaseOk = false;
			}
			if ($purchaseOk) $_pm['mysql']->query('COMMIT');
			else
			{
				$_pm['mysql']->query('ROLLBACK');
				$err = 3;
			}
		}
		else
		{
			$ret = $_pm['mysql']->getOneRecord("SELECT id 
										FROM userbag
									   WHERE uid={$_SESSION['id']} and pid={$bid}
									   LIMIT 0,1
									");
			if (is_array($ret))
			{
				$purchaseOk = $_pm['mysql']->query("UPDATE userbag 
							   SET sums=sums+{$n} 
							 WHERE uid={$_SESSION['id']} and id={$ret['id']} and sums+{$n}>0
						  ") !== false;
			}
			else //create new data
			{
				//$newid = mem_get_autoid($m, MEM_ORDER_KEY,'userbag');
				$purchaseOk = $_pm['mysql']->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
							VALUES(
								   {$user['id']},
								   {$bid},
								   {$wp['sell']},
								   {$wp['vary']},
								   {$n},
								   ".time()."
								  );
						  ") !== false;					
			}
			if ($purchaseOk) $_pm['mysql']->query('COMMIT');
			else
			{
				$_pm['mysql']->query('ROLLBACK');
				$err = 3;
			}
		}
	}	// end inner else
}
//$_pm['user']->updateMemUser($_SESSION['id']);
//$_pm['user']->updateMemUserbag($_SESSION['id']);
$_pm['mem']->memClose();
echo $err;
unLockItem($bid);
?>
