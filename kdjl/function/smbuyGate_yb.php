<?php
require_once(dirname(__FILE__).'/smbuyGate.php');
exit;

/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

@Usage: Shop buy function
@Write date: 2008.05.02
@Update date: 2008.05.23
@Memo: 元宝商店购买处理脚本。
     Fix: Max limit for buy props. (2008.06.22)
	 // queryCoin($account,$licenseId)
	 // payment($account,$licenseId,$order_id,$feeMoney,$subject)
	 // Need: $licenseId, passport name.
	 // 单位：元宝。
@##############################################
*/
//die('维护中……');
//exit();
session_start();
require_once('../config/config.game.php');

$m	= $_pm['mem'];
$db = &$_pm['mysql'];
$u	= $_pm['user'];
secStart($m);
$err = 0;

$user	= $u->getUserById($_SESSION['id']);
$bags    = $u->getUserBagById($_SESSION['id']);
if($user===FALSE) {$err=1;}

$bid = intval($_REQUEST['bid']); // table: props => id
$n	 = intval($_REQUEST['n']); 
if( !is_int($bid) || $bid<1 || $n<1) $err = 2;

$wp= $m->dataGet(array('k' => MEM_PROPS_KEY, 
					   'v' => "if(\$rs['id'] == '{$bid}' && \$rs['yb']>0) \$ret=\$rs;"
				 ));

// Get current bag props num.
$bagnum = 0;
if (is_array($bags))
{
	foreach ($bags as $k => $v)
	{
		if ($v['sums']>0 && $v['zbing']==0) $bagnum++;
	}
}

if (!is_array($wp)) $err=3;
else if( ($wp['vary']==2 && ($n+$bagnum)>$user['maxbag']) || (($bagnum+1)>$user['maxbag']) ) $err=4;
else
{
	$price = $wp['yb']*$n;
	if(empty($price))
	{
		die("3");
	}
	$nowCoin = $user['yb'];

	if ($price > $nowCoin)
	{
		$err='10'; // Money too less.
	}
	else
	{   
		$now = time();
		$number = $n;
			
		$db->query("insert into yblog(title,nickname,yb,buytime,pname,nums)
				    values('购买口袋精灵二[1区]道具{$wp['name']} {$n} 个.','{$_SESSION['username']}','{$price}',unix_timestamp(),'{$wp['name']}',{$n})
				  "); // save buy log.
		
		######################################在这里增加积分 谭炜 11.10###########################################
		//开放积分（玩家累计消耗100元宝增1分）
		//在player表里新增积分（score）字段，保存用户，增加useyb字段，保存用户没有换取积分的元宝
		$useryb = $user['useyb'] + $price;//总的消费的元宝数
		$score = intval($useryb / 100);
		$useyb = intval($useryb % 100);
		#######################################积分在这里结束#######################################3

		######################################在这里增加活动积分 谭炜 1.20###########################################
		//开放积分（玩家累计消耗100元宝增1分）
		//在player表里新增积分（score）字段，保存用户积分，增加useyb字段，保存用户没有换取积分的元宝
		$active_useybs = $user['active_useyb'] + $price;//总的消费的元宝数
		$active_score = intval($active_useybs / 100);
		$active_useyb = intval($active_useybs % 100);
		#######################################活动积分在这里结束#######################################3

		$user['yb'] = $nowCoin-$price;
		
		#########################################################

		if ($wp['vary']==2) //不能叠加
		{ 
			for ($i=0; $i<$n; $i++)
			{
			    $db->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
							VALUES(
								   {$user['id']},
								   {$bid},
								   {$wp['sell']},
									2,
								   1,
								   unix_timestamp()
								  );
						  ");
			}
		}
		else
		{		
			$arrobj = new arrays();
			$ret = false;
			if(is_array($bags))
			foreach($bags as $k=>$v)
			{
				if($v['uid']==$_SESSION['id'] && $v['pid']==$bid) $ret=$v;
			}
			
			if (is_array($ret))
			{

				$db->query("UPDATE userbag
							   SET sums=sums+{$n},stime=".time()."
							 WHERE id={$ret['id']}
						  ");
						  
			}
			else //create new data
			{
				$db->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
							VALUES(
								   {$user['id']},
								   {$bid},
								   {$wp['sell']},
									1,
									{$n},
								   unix_timestamp());
						  ");
						
			}
		}
		$db->query("update player set yb={$user['yb']},useyb={$useyb},score=score + {$score},active_useyb={$active_useyb},active_score=active_score+{$active_score} where id={$_SESSION['id']}");
	}	// end inner else
}
unset($user,$wp);
$m->memClose();
echo $err;
?>
