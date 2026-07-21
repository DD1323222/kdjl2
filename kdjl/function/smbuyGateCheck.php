<?php
/*
*说明：整合各种消费流程，有的需要向平台请求用元宝购买，有的直接在游戏中的玩家账户上扣除元宝进行购买。
*By Huizheng Yu
*2009-04-17
*/

require_once('../config/config.game.php');
$orderId=substr((isset($_GET['orderId']) && !is_array($_GET['orderId'])) ? $_GET['orderId'] : '',0,25);//游戏发送消费记录时的订单号
$userAccount=substr((isset($_GET['userAccount']) && !is_array($_GET['userAccount'])) ? $_GET['userAccount'] : '',0,60);//用户通行证号
$feeMoney=intval((isset($_GET['feeMoney']) && !is_array($_GET['feeMoney'])) ? $_GET['feeMoney'] : 0);//用户消费金额
$logDate=(isset($_GET['logDate']) && !is_array($_GET['logDate'])) ? $_GET['logDate'] : '';//用户消费时间,格式yyyyMMddHHmmss
$sign=(isset($_GET['sign']) && !is_array($_GET['sign'])) ? $_GET['sign'] : '';//MD5签名
require_once("../login/lib/nusoap.php");
$key="7sl+kb9adDAc7gLuv31MeEFPBMJZdRZyAx9eEmXSTui4423hgGfXF1pyM";
$sn= md5($orderId.$userAccount.$feeMoney.$logDate.$key);

if($sn!==$sign)
{
	die('102');
}

$orderIdSql = $_pm['mysql']->escape($orderId);
$userAccountSql = $_pm['mysql']->escape($userAccount);
$db = &$_pm['mysql'];
if(!$db->query('START TRANSACTION')) die('105');
$row=$db->getOneRecord('select id,pid,pnum,uid,uname,fee,flag from shop_order where order_id="'.$orderIdSql.'" order by id desc limit 1 FOR UPDATE');
if($row === false && mysql_errno($db->getConn()) != 0)
{
	$db->query('ROLLBACK');
	die('105');
}

if(!$row)
{
	$db->query('ROLLBACK');
	die('103');
}

if($row['flag']==1)
{
	$db->query('ROLLBACK');
	die('10');
}

$orderRowId = intval($row['id']);
$userid = intval($row['uid']);
$bid = intval($row['pid']);
$n = intval($row['pnum']);
$fee = intval($row['fee']);

if($fee!=$feeMoney||$userAccount!=$row['uname'])
{
	$db->query('ROLLBACK');
	die('104');
}

if($orderRowId < 1 || $userid < 1 || $bid < 1 || $n < 1 || $n > 100 || $fee < 1)
{
	$db->query('ROLLBACK');
	die('104');
}


$m	= &$_pm['mem'];
$u	= &$_pm['user'];
$user = $db->getOneRecord("SELECT id,yb,useyb,score,active_useyb,active_score,vip,vipyb FROM player WHERE id={$userid} FOR UPDATE");
if(!is_array($user))
{
	$db->query('ROLLBACK');
	die('104');
}
if(!isset($user['yb'])) $user['yb'] = 0;
if(!isset($user['useyb'])) $user['useyb'] = 0;
if(!isset($user['score'])) $user['score'] = 0;
if(!isset($user['active_useyb'])) $user['active_useyb'] = 0;
if(!isset($user['active_score'])) $user['active_score'] = 0;
if(!isset($user['vip'])) $user['vip'] = 0;
if(!isset($user['vipyb'])) $user['vipyb'] = 0;
//$bags    = $u->getUserBagById($_SESSION['id']);
$bags    = $u->getUserBagById($userid);

$wp = $db->getOneRecord("SELECT * FROM props WHERE id = {$bid}");
if(!is_array($wp))
{
	$db->query('ROLLBACK');
	die('104');
}
if(!isset($wp['sell'])) $wp['sell'] = 0;
if(intval($wp['vary']) != 1 && intval($wp['vary']) != 2)
{
	$db->query('ROLLBACK');
	die('104');
}

$now = time();
//$number = $n;

$purchaseOk = true;
$logTitle = $db->escape($orderId.'购买口袋精灵二[7区]道具'.$wp['name'].' '.$n.' 个.');
$logUser = $db->escape($row['uname']);
$logProp = $db->escape($wp['name']);
if($db->query("insert into yblog(title,nickname,yb,buytime,pname,nums)
			values('{$logTitle}','{$logUser}','{$fee}',unix_timestamp(),'{$logProp}',{$n})
		  ") === false) $purchaseOk = false; // save buy log.
if($purchaseOk && mysql_affected_rows($db->getConn()) != 1) $purchaseOk = false;

######################################在这里增加积分 谭炜 11.10###########################################
//开放积分（玩家累计消耗100元宝增1分）
//在player表里新增积分（score）字段，保存用户，增加useyb字段，保存用户没有换取积分的元宝
$useryb = $user['useyb'] + $fee;//总的消费的元宝数
$score = intval($useryb / 100);
$useyb = intval($useryb % 100);
#######################################积分在这里结束#######################################3

######################################在这里增加活动积分 谭炜 1.20###########################################
//开放积分（玩家累计消耗100元宝增1分）
//在player表里新增积分（score）字段，保存用户积分，增加useyb字段，保存用户没有换取积分的元宝
$active_useybs = $user['active_useyb'] + $fee;//总的消费的元宝数
$active_score = intval($active_useybs / 100);
$active_useyb = intval($active_useybs % 100);
#######################################活动积分在这里结束#######################################3

######################################在这里增加vip 谭炜 1.20###########################################
//开放积分（玩家累计消耗100元宝增1分）
//在player表里新增积分（score）字段，保存用户积分，增加useyb字段，保存用户没有换取积分的元宝
$vipybs = $user['vipyb'] + $fee;//总的消费的元宝数
$vip = intval($vipybs / 100);
$vipyb = intval($vipybs % 100);
#######################################活动积分在这里结束#######################################3

#########################################################

if ($wp['vary']==2) //不能叠加
{
	for ($i=0; $i<$n; $i++)
	{
		if($db->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
					VALUES(
						   {$user['id']},
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
	$ret = $db->getOneRecord("SELECT id FROM userbag WHERE uid={$userid} and pid={$bid} and vary=1 and zbing=0 and (cantrade IS NULL OR cantrade=0) LIMIT 1 FOR UPDATE");

	if (is_array($ret))
	{

		if($db->query("UPDATE userbag
					   SET sums=COALESCE(sums,0)+{$n},stime=".time()."
				 WHERE uid={$userid} and id={$ret['id']} and vary=1 and COALESCE(sums,0) <= 2147483647-{$n} and zbing=0 and (cantrade IS NULL OR cantrade=0)
				  ") === false) $purchaseOk = false;
		if($purchaseOk && mysql_affected_rows($db->getConn()) != 1) $purchaseOk = false;

	}
	else if($ret === false && mysql_errno($db->getConn()) != 0)
	{
		$purchaseOk = false;
	}
	else //create new data
	{
		if($db->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
					VALUES(
						   {$user['id']},
						   {$bid},
						   {$wp['sell']},
							1,
							{$n},
						   unix_timestamp());
				  ") === false) $purchaseOk = false;
		if($purchaseOk && mysql_affected_rows($db->getConn()) != 1) $purchaseOk = false;

	}
}

if($db->query("update player set yb=yb-{$fee},useyb={$useyb},score=COALESCE(score,0)+{$score},vip=COALESCE(vip,0)+{$vip},vipyb={$vipyb},active_useyb={$active_useyb},active_score=COALESCE(active_score,0)+{$active_score} where id={$userid} and yb >= {$fee}") === false)
{
	$purchaseOk = false;
}
else if(mysql_affected_rows($db->getConn()) != 1)
{
	$purchaseOk = false;
}

if($purchaseOk)
{
	$purchaseOk = $db->query("update shop_order set flag=1 where id={$orderRowId} and flag=0") &&
		mysql_affected_rows($db->getConn()) == 1;
}

if(!$purchaseOk || !$db->query('COMMIT'))
{
	$db->query('ROLLBACK');
	die('105');
}
$m->del($userid);
$m->del($userid.'bag');

unset($user,$wp);
$m->memClose();
echo '';
?>
