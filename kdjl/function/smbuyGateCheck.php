<?php
/*
*说明：整合各种消费流程，有的需要向平台请求用元宝购买，有的直接在游戏中的玩家账户上扣除元宝进行购买。
*By Huizheng Yu
*2009-04-17
*/

require_once('../config/config.game.php');
$orderId=substr($_GET['orderId'],0,25);//游戏发送消费记录时的订单号
$userAccount=substr($_GET['userAccount'],0,25);//用户通行证号
$feeMoney=intval($_GET['feeMoney']);//用户消费金额
$logDate=$_GET['logDate'];//用户消费时间,格式yyyyMMddHHmmss
$sign=$_GET['sign'];//MD5签名
require_once("../login/lib/nusoap.php");
$key="7sl+kb9adDAc7gLuv31MeEFPBMJZdRZyAx9eEmXSTui4423hgGfXF1pyM";
$sn= md5($orderId.$userAccount.$feeMoney.$logDate.$key);

if($sn!==$sign)
{
	die('102');
}

$row=$_pm['mysql']->getOneRecord('select pid,pnum,uid,uname,fee,flag from shop_order where order_id="'.$orderId.'" order by id desc limit 1');
if(mysql_error())
{
	die('105');
}

if(!$row)
{
	die('103');
}

if($row['flag']==1)
{
	die('10');
}

if(!$row['fee']!=$feeMoney||$userAccount!=$row['uname'])
{
	die('104');
}

$userid=$row['uid'];
$bid=$row['pid'];
$n=$row['pnum'];


$m	= &$_pm['mem'];
$db = &$_pm['mysql'];
$u	= &$_pm['user'];
$user	= $u->getUserById($userid);
//$bags    = $u->getUserBagById($_SESSION['id']);
$bags    = $u->getUserBagById($userid);

$wp = $_pm['mysql'] -> getOneRecord("SELECT * FROM props WHERE id = $bid and yb > 0");

$now = time();
//$number = $n;
	
$db->query("insert into yblog(title,nickname,yb,buytime,pname,nums)
			values('{$orderId}购买口袋精灵二[7区]道具{$wp['name']} {$n} 个.','".$row['uname']."','{$row['fee']}',unix_timestamp(),'{$wp['name']}',{$n})
		  "); // save buy log.

$db->query("update shop_order set flag=1 where order_id='".$orderId."' and uname='".$userAccount."'"); 
		  
######################################在这里增加积分 谭炜 11.10###########################################
//开放积分（玩家累计消耗100元宝增1分）
//在player表里新增积分（score）字段，保存用户，增加useyb字段，保存用户没有换取积分的元宝
$useryb = $user['useyb'] + $row['fee'];//总的消费的元宝数
$score = intval($useryb / 100);
$useyb = intval($useryb % 100);
#######################################积分在这里结束#######################################3

######################################在这里增加活动积分 谭炜 1.20###########################################
//开放积分（玩家累计消耗100元宝增1分）
//在player表里新增积分（score）字段，保存用户积分，增加useyb字段，保存用户没有换取积分的元宝
$active_useybs = $user['active_useyb'] + $row['fee'];//总的消费的元宝数
$active_score = intval($active_useybs / 100);
$active_useyb = intval($active_useybs % 100);
#######################################活动积分在这里结束#######################################3

######################################在这里增加vip 谭炜 1.20###########################################
//开放积分（玩家累计消耗100元宝增1分）
//在player表里新增积分（score）字段，保存用户积分，增加useyb字段，保存用户没有换取积分的元宝
$vipybs = $user['vipyb'] + $row['fee'];//总的消费的元宝数
$vip = intval($vipybs / 100);
$vipyb = intval($vipybs % 100);
#######################################活动积分在这里结束#######################################3

$user['yb'] -=$row['fee'];//这里不会去平台查余额

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
		if($v['uid']==$userid && $v['pid']==$bid) $ret=$v;
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

$db->query("update player set yb={$user['yb']},useyb={$useyb},score=score + {$score},vip = vip + {$vip},vipyb = {$vipyb},active_useyb={$active_useyb},active_score=active_score+{$active_score} where id={$userid}");

unset($user,$wp);
$m->memClose();
echo $err;
?>