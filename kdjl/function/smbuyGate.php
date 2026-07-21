<?php
/*
*说明：整合各种消费流程，有的需要向平台请求用元宝购买，有的直接在游戏中的玩家账户上扣除元宝进行购买。
*By Huizheng Yu
*2009-04-17
*/
//die('维护中……');
//exit();
require_once('../config/config.game.php');

$m	= $_pm['mem'];
$db = &$_pm['mysql'];
$u	= $_pm['user'];
secStart($m);
$err = 0;
define('HTTP_CONTENT_STARTED',"\r\n\r\n");
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid <= 0)
{
	die('1');
}
del_bag_expire();
$sessionUsername = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$sessionLicenseId = isset($_SESSION['licenseid']) ? $_SESSION['licenseid'] : '';
$sessionUserId = isset($_SESSION['userid']) ? $_SESSION['userid'] : '';
$sessionUsernameSql = $db->escape($sessionUsername);
//---------------------------
//通过判断域名来确定是否需要向平台购买元宝
$httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$httpHost = preg_replace('/[^A-Za-z0-9\\.\\-:]/', '', $httpHost);
$Domain=explode('.',$httpHost);
$DomainName1=isset($Domain[1]) ? $Domain[1] : '';
$DomainName2=isset($Domain[2]) ? $Domain[2] : '';
//---------------------------
$bid = (isset($_REQUEST['bid']) && !is_array($_REQUEST['bid'])) ? intval($_REQUEST['bid']) : 0; // table: props => id
$n	 = (isset($_REQUEST['n']) && !is_array($_REQUEST['n'])) ? intval($_REQUEST['n']) : 0;
$buyChannel = (isset($_REQUEST['channel']) && !is_array($_REQUEST['channel']) && $_REQUEST['channel'] === 'limit') ? 'limit' : 'yb';

if($bid < 1 || $n < 1 || $n > 100) die('2');

require_once('../sec/dblock_fun.php');
$a = getLock($uid);

//if($lastbuy+3>time())
//{
//	realseLock();
//	die('服务器繁忙，购买时间间隔太短！');
//}
if(!is_array($a)){
	realseLock();
	die('服务器繁忙，请稍候再试！');
}

$user = $u->getUserById($uid);
$bags = $u->getUserBagById($uid);
if(!is_array($user))
{
	realseLock();
	die('1');
}
if(!is_array($bags)) $bags = array();
if(!isset($user['maxbag'])) $user['maxbag'] = 0;
if(!isset($user['yb'])) $user['yb'] = 0;
if(!isset($user['useyb'])) $user['useyb'] = 0;
if(!isset($user['active_useyb'])) $user['active_useyb'] = 0;
if(!isset($user['vipyb'])) $user['vipyb'] = 0;

/*$wp= $m->dataGet(array('k' => MEM_PROPS_KEY,
					   'v' => "if(\$rs['id'] == '{$bid}' && \$rs['yb']>0) \$ret=\$rs;"
				 ));*/
$priceField = $buyChannel === 'limit' ? 'zhekouyb' : 'yb';
$wp = $_pm['mysql'] -> getOneRecord("SELECT * FROM props WHERE id = $bid and {$priceField} > 0 and {$priceField} < 99999 and stime > 0 and LEFT(CAST(stime AS CHAR),1) IN (1,2,3,4) FOR UPDATE");
if(!is_array($wp))
{
	realseLock();
	die('101');
}
if(!isset($wp['id'])) $wp['id'] = 0;
if(!isset($wp['name'])) $wp['name'] = '';
if(!isset($wp['vary'])) $wp['vary'] = 0;
if(!isset($wp['sell'])) $wp['sell'] = 0;
if(!isset($wp['yb'])) $wp['yb'] = 0;
if(!isset($wp['zhekouyb'])) $wp['zhekouyb'] = 0;
if(!isset($wp['timelimit'])) $wp['timelimit'] = '';
if(intval($wp['vary']) != 1 && intval($wp['vary']) != 2)
{
	realseLock();
	die('3');
}
if(intval($wp['sell']) < 0)
{
	realseLock();
	die('3');
}
$isLimitedBuy = false;
$limitedSoldKey = '';
if($buyChannel === 'limit'){
	$zk = 0;

	$time = date('Y-m-d H:i:s');
	$sql = 'SELECT value2,contents FROM welcome WHERE code = "timelimitbuy"';
	$tm = $_pm["mysql"] -> getOneRecord($sql);
	if(!is_array($tm)){
		realseLock();
		die('活动未开启1！');
	}
	$tarr = explode('|',$tm['value2']);
	if(count($tarr) < 2 || $tarr[0] == '' || $tarr[1] == ''){
		realseLock();
		die('抢购商品限购配置错误！');
	}
	if($time < $tarr[0] || $time > $tarr[1]){
		realseLock();
		die('活动未开启2！');
	}
	$limitedSoldKey = 'zhekou_'.$wp['id'].'_num';
	$zk = intval(kdjlSafeMemValue($_pm['mem']->get($limitedSoldKey), 0));

	//if($zk > 0){
		$pa = empty($tm['contents']) ? array() : explode(',',$tm['contents']);
		foreach($pa as $pv){
			$parr = explode(':',$pv);
			if(count($parr) < 2 || $parr[0] == '') continue;
			if($parr[0] == $wp['id']){
				$isLimitedBuy = true;
				$s = intval($parr[1]) - $zk;//echo $s.'<br />'.$n.'<br />'.$zk.'<br />';print_r($parr[1]);
				if($s < $n){
					realseLock();
					die('您购买的物品剩余数量不够！');
				}
				else if($s >= $n){
					break;
				}
				if(intval($parr[1]) <= $zk){
					realseLock();
					die('已售完！');
				}

			}
		}
		if(!$isLimitedBuy){
			realseLock();
			die('该商品已从抢购商城下架！');
		}
	//}

}
//增加自动上下架的功能
if(!empty($wp['timelimit'])){
	$limitarr = explode('|',$wp['timelimit']);
	$nowtime = date('YmdHi');
	if(!empty($limitarr[0]) && $nowtime < $limitarr[0]){
		realseLock();
		die('101');
	}
	if(!empty($limitarr[1]) && $nowtime > $limitarr[1]){
		realseLock();
		die('101');
	}
}
//增加自动上下架的功能在这里结束
// Get current bag props num.
$bagnum = 0;
$hasStackItem = false;
if (is_array($bags))
{
	foreach ($bags as $k => $v)
	{
		if(!is_array($v)) continue;
		if(!isset($v['sums'])) $v['sums'] = 0;
		if(!isset($v['zbing'])) $v['zbing'] = 0;
		if(!isset($v['pid'])) $v['pid'] = 0;
		if(!isset($v['vary'])) $v['vary'] = 0;
		if(!isset($v['cantrade'])) $v['cantrade'] = 0;
		if ($v['sums']>0 && $v['zbing']==0) $bagnum++;
		if ($v['sums']>0 && $v['zbing']==0 && intval($v['vary']) == 1 && $v['pid']==$bid && intval($v['cantrade']) == 0) $hasStackItem = true;
	}
}

if (!is_array($wp)) $err=3;
else {
	$needBagSlot = ($wp['vary']==2) ? $n : ($hasStackItem ? 0 : 1);
	if(($bagnum+$needBagSlot)>$user['maxbag']) $err=4;
}
if($err)
{
	realseLock();
	$m->memClose();
	echo $err;
	exit;
}
else
{

	$price = kdjlSafePositiveProduct(($buyChannel === 'limit' ? $wp['zhekouyb'] : $wp['yb']), $n);
	if($price === false)
	{
		realseLock();
		die("3");
	}

	$nowCoin = $user['yb'];
	$externalPayment = false;


	//--------------------------
	//如果为webgame的请求
	//--------------------------
	if($DomainName1=='webgame'&&!preg_match('/pm51\d/is',$httpHost)&&!preg_match('/kdjl\d/is',$httpHost)&&!preg_match('/^pmbd\d/is',$httpHost))
	{
		######平台加密，解密接口函数包
		require_once("../login/lib/passport.php");

		######平台接口通用接口函数包
		require_once("../login/lib/nusoap.php");
		// 获取玩家剩余元宝。
		$coinXml = queryCoin($sessionUsername,$sessionLicenseId);
		//echo $coinXml;exit();
		$xmlarr = explode('Response10/Response', str_replace(array("<",">"),"",$coinXml));
		$nowCoin = 0;
		if (count($xmlarr)>1)
		{
			$endpart = explode('coin_valid![CDATA[',$xmlarr[1]);
			if(count($endpart)>1)
			{
				$coinarr = explode('/coin_valid', $endpart[1]);
				$nowCoin=intval($coinarr[0]);
			}
		}
		else{
			$nowCoin = 0;
		}
	}
	//--------------------------


	if ($price > $nowCoin)
	{
		$err='10'; // Money too less.
	}
	else
	{
		//--------------------------
		//如果为webgame的请求
		//--------------------------

		if($DomainName1=='webgame'&&!preg_match('/pm51\d/is',$httpHost)&&!preg_match('/kdjl\d/is',$httpHost)&&!preg_match('/^pmbd\d/is',$httpHost))
		{
			######平台加密，解密接口函数包
			require_once("../login/lib/passport.php");

			######平台接口通用接口函数包
			require_once("../login/lib/nusoap.php");
			// 付费##################################################
			// $pay is xml document.
			$host = str_replace("PM51","",strtoupper(substr($httpHost,0,strpos($httpHost,'.'))));
			$ordid=$host.substr("000000000000".$uid,-8).substr(time(),-9);
			$pay=payment($sessionUsername,
						$sessionLicenseId,
						$ordid,
						$price,
						$sessionUsername."购买口袋精灵二[1区]道具 ".$wp['name']." {$n}个。");
			/*
			CREATE TABLE `shop_order` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `uid` int(11) DEFAULT NULL,
			  `uname` varchar(60) DEFAULT NULL,
			  `pid` int(6) DEFAULT NULL,
			  `pnum` smallint(4) DEFAULT NULL,
			  `fee` int(6) DEFAULT NULL,
			  `order_id` varchar(25) DEFAULT NULL,
			  `create_time` int(10) DEFAULT NULL,
			  `flag` tinyint(1) DEFAULT '0',
			  PRIMARY KEY (`id`),
			  KEY `order` (`order_id`)
			) ENGINE=MyISAM
			*/
			if (strpos($pay,'<Response>10</Response>')===false)
			{
				$sql='INSERT INTO `shop_order` SET `uid`="'.$uid.'", `uname`="'.$sessionUsernameSql.'", `pid`="'.$wp['id'].'", `pnum`="'.$n.'", `create_time`='.time().', `flag`=0,fee="'.$price.'",order_id="'.$ordid.'";';
				$db->query($sql);
				//header('Content-Type:text/html;charset=GBK');
				 realseLock();
				 die("元宝支付失败!");
			}else{
				$sql='INSERT INTO `shop_order` SET `uid`="'.$uid.'", `uname`="'.$sessionUsernameSql.'", `pid`="'.$wp['id'].'", `pnum`="'.$n.'", `create_time`='.time().', `flag`=1,fee="'.$price.'",order_id="'.$ordid.'";';
				$db->query($sql);
				$externalPayment = true;
			}
		}
		//-----------------------------

		//----------------------------
		//如果为4399的请求
		//----------------------------
		if(($DomainName2=='cn'||$DomainName2=='com')&&($DomainName1=='youjia'||$DomainName1=='qq496'||$DomainName1=='my4399'))
		{
			$host = str_replace("KD","",strtoupper(substr($httpHost,0,strpos($httpHost,'.'))));
			$ordid=$host.substr("000000000000".$uid,-9).substr(time(),-9);

			$now = time();
			$api_code = '4399_Pm_Gold_WCQmhS7FDvnv533b';
			$rflag = md5($sessionUserId.'|'.urlencode($sessionUsername).'|'.'S1'.'|'.$price.'|'.$now.'|'.$api_code);
			$utype = urlencode(base64_encode(kdjlSafeIconv("GBK","UTF-8",$wp['name'])));
			$number = $n;
			$desc = urlencode(base64_encode(kdjlSafeIconv("GBK","UTF-8",$sessionUsername."购买口袋精灵二[1区]道具 ".$wp['name']." {$n}个。")));
			$par = 'UserId='.$sessionUserId.'&UserName='.$sessionUsername.'&ServerId=S1&UseGold='.$price.'&UseTime='.$now.'&flag='.$rflag.'&UseType='.$utype.'&Number='.$n.'&Desc='.$desc.'&orderid='.$ordid;

			//echo $par;
			//$rets = @file_get_contents("http://web.4399.com/api/kdjl/use_gold.php?".$par);

			//$rets = socketData("web.4399.com","/api/kdjl/use_gold.php?".$par);
			/*if (substr($rets,0,5) != 'true|' )
			{
				//ob_start();
				//header('Content-Type:text/html;charset=GBK');
				die('元宝支付失败！！');
				//ob_end_flush();
			}*/

			$requestNum = 0;
			$tmp = '';
			for($i = 0; $i <= 4; $i++)
			{
				$rets = socketData("web.4399.com","/api/kdjl/use_gold_order.php?".$par);
				if($rets === false) $rets = '';
				//$tmp .= '|'.$requestNum.': '.print_r($rets,1)."<br/>\r\n";
				//echo '$par='.$par.',$rets='.$rets;
				//$rets = http_get_result("http://web.4399.com/api/kdjl/use_gold_order.php?".$par);
				if($requestNum == 4 && strpos($rets,'true|') ===false)
				{
					/*$db->query("insert into weblog(title,nickname,yb,buytime,pname,nums)
				    values('{$tmp}','{$_SESSION['username']}','{$price}',unix_timestamp(),'{$wp['name']}',{$n})
				  ");*/

					if(strpos($rets,'false|') !==false)
					{realseLock();
						die('扣费失败，请检查余额或者稍后重试！！');
					}else
					{realseLock();
						die('网络故障，请稍后购买！！');
					}
				}
				/* 判断订单号重复，并且已经扣钱，就加道具，否则继续循环,返回值：order_exist|true|1257309440|*/
				$array = explode("|",$rets);
				$time_exit = isset($array[2]) ? intval($array[2])+8 : 0;
				if(strpos($rets,'order_exist|') !==false && strpos($rets,'true|') !==false && time()<$time_exit)
				{
					echo "购买成功";
					$err='';
					$externalPayment = true;
					break;
				}
				if(strpos($rets,'false|') !==false)
				{realseLock();
					die('扣费失败，请检查余额或者稍后重试！！');
				}
				/* 成功接收返回信息，加道具*/
				if(strpos($rets,'true|') !==false)
				{

					echo "购买成功";
					$err='';
					$externalPayment = true;
					break;
				}
				else
				{
					/*$db->query("insert into weblog(title,nickname,yb,buytime,pname,nums)
						values('{$tmp}','{$_SESSION['username']}','{$price}',unix_timestamp(),'{$wp['name']}',{$n})
					  ");*/
					$requestNum++;
					sleep(2);
				}

			}

		}
		//----------------------------
		$now = time();
		$number = $n;
		$purchaseOk = true;
		if($isLimitedBuy)
		{
			$lockedLimit = $db->getOneRecord('SELECT value2,contents FROM welcome WHERE code = "timelimitbuy" FOR UPDATE');
			if(!is_array($lockedLimit))
			{
				$db->query('ROLLBACK');
				$m->memClose();
				realseLock();
				die('抢购商品限购配置错误！');
			}
			$limitTimes = explode('|', $lockedLimit['value2']);
			$limitNow = date('Y-m-d H:i:s');
			if(count($limitTimes) < 2 || $limitTimes[0] == '' || $limitTimes[1] == '' || $limitNow < $limitTimes[0] || $limitNow > $limitTimes[1])
			{
				$db->query('ROLLBACK');
				$m->memClose();
				realseLock();
				die('101');
			}
			$limitMatched = false;
			$limitRows = empty($lockedLimit['contents']) ? array() : explode(',', $lockedLimit['contents']);
			$zk = intval(kdjlSafeMemValue($_pm['mem']->get($limitedSoldKey), 0));
			foreach($limitRows as $limitRow)
			{
				$limitParts = explode(':', $limitRow);
				if(count($limitParts) < 2 || intval($limitParts[0]) != intval($wp['id'])) continue;
				$limitMatched = true;
				if((intval($limitParts[1]) - $zk) < $n)
				{
					$db->query('ROLLBACK');
					$m->memClose();
					realseLock();
					die('该商品已经售罄！');
				}
				break;
			}
			if(!$limitMatched)
			{
				$db->query('ROLLBACK');
				$m->memClose();
				realseLock();
				 die('该商品已经售罄！');
			}
		}
		$lockedUser = $db->getOneRecord("SELECT yb,useyb,active_useyb,vipyb,maxbag FROM player WHERE id={$uid} FOR UPDATE");
		$lockedBags = $db->getRecords("SELECT pid,vary,sums,zbing,cantrade FROM userbag WHERE uid={$uid} FOR UPDATE");
		if(!is_array($lockedUser) || !is_array($lockedBags))
		{
			$db->query('ROLLBACK');
			$m->memClose();
			realseLock();
			die('3');
		}
		$lockedBagNum = 0;
		$lockedHasStack = false;
		foreach($lockedBags as $lockedBag)
		{
			if(!is_array($lockedBag)) continue;
			if(intval($lockedBag['sums']) > 0 && intval($lockedBag['zbing']) == 0) $lockedBagNum++;
			if(intval($lockedBag['sums']) > 0 && intval($lockedBag['zbing']) == 0 && intval($lockedBag['vary']) == 1 &&
				intval($lockedBag['pid']) == $bid && (!isset($lockedBag['cantrade']) || intval($lockedBag['cantrade']) == 0)) $lockedHasStack = true;
		}
		$lockedNeedSlot = intval($wp['vary']) == 2 ? $n : ($lockedHasStack ? 0 : 1);
		if($lockedBagNum + $lockedNeedSlot > intval($lockedUser['maxbag']))
		{
			$db->query('ROLLBACK');
			$m->memClose();
			realseLock();
			die('4');
		}
		$user['useyb'] = intval($lockedUser['useyb']);
		$user['active_useyb'] = intval($lockedUser['active_useyb']);
		$user['vipyb'] = intval($lockedUser['vipyb']);
		if(!$externalPayment)
		{
			$nowCoin = intval($lockedUser['yb']);
			if($price > $nowCoin)
			{
				$db->query('ROLLBACK');
				$m->memClose();
				realseLock();
				die('10');
			}
		}
		$logUsername = $sessionUsernameSql;
		$logPropName = $db->escape($wp['name']);
		if($db->query("insert into yblog(title,nickname,yb,buytime,pname,nums)
				    values(".$wp['id'].",'{$logUsername}','{$price}',unix_timestamp(),'{$logPropName}',{$n})
				  ") === false) $purchaseOk = false; // save buy log.
		if($purchaseOk && mysql_affected_rows($db->getConn()) != 1) $purchaseOk = false;

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

		######################################在这里增加vip 谭炜 1.20###########################################
		//开放积分（玩家累计消耗100元宝增1分）
		//在player表里新增积分（score）字段，保存用户积分，增加useyb字段，保存用户没有换取积分的元宝
		$vipybs = $user['vipyb'] + $price;//总的消费的元宝数
		$vip = intval($vipybs / 100);
		$vipyb = intval($vipybs % 100);
		#######################################活动积分在这里结束#######################################3

		$user['yb'] = $nowCoin-$price;

		#########################################################

		if ($wp['vary']==2) //不能叠加
		{
			for ($i=0; $i<$n; $i++)
			{
			    if($db->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
							VALUES(
								   {$uid},
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
			$ret = $db->getOneRecord("SELECT id FROM userbag WHERE uid={$uid} and pid={$bid} and vary=1 and zbing=0 and (cantrade IS NULL OR cantrade=0) LIMIT 1 FOR UPDATE");

			if (is_array($ret))
			{

				if($db->query("UPDATE userbag
							   SET sums=COALESCE(sums,0)+{$n},stime=".time()."
							 WHERE uid={$uid} and id={$ret['id']} and vary=1 and COALESCE(sums,0) <= 2147483647-{$n} and zbing=0 and (cantrade IS NULL OR cantrade=0)
						  ") === false) $purchaseOk = false;
				if($purchaseOk && mysql_affected_rows($db->getConn()) != 1) $purchaseOk = false;

			}
			else //create new data
			{
				if($db->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
							VALUES(
								   {$uid},
								   {$bid},
								   {$wp['sell']},
									1,
									{$n},
								   unix_timestamp());
						  ") === false) $purchaseOk = false;
				if($purchaseOk && mysql_affected_rows($db->getConn()) != 1) $purchaseOk = false;

			}
		}
		/*$db->query("update player set yb={$user['yb']},useyb={$useyb},score=score + {$score},active_useyb={$active_useyb},active_score=active_score+{$active_score} where id={$_SESSION['id']}");*/
		$d = date('YmdHi');
		$sql = 'SELECT days FROM timeconfig WHERE titles = "vip_multi" AND starttime <'.$d.' AND endtime >'.$d;
		$multi = $db -> getOneRecord($sql);
		if(is_array($multi)){
			$vip = $vip * $multi['days'];
		}
		if($externalPayment)
		{
			$balanceUpdate = "yb={$user['yb']}";
			$balanceWhere = '';
		}
		else
		{
			$balanceUpdate = "yb=yb-{$price}";
			$balanceWhere = " and yb >= {$price}";
		}
		if($db->query("update player set {$balanceUpdate},useyb={$useyb},score=COALESCE(score,0)+{$score},vip=COALESCE(vip,0)+{$vip},vipyb={$vipyb},active_useyb={$active_useyb},active_score=COALESCE(active_score,0)+{$active_score} where id={$uid}{$balanceWhere}") === false)
		{
			$purchaseOk = false;
		}
		else if(mysql_affected_rows($db->getConn()) != 1)
		{
			$db->query('ROLLBACK');
			$m->memClose();
			realseLock();
			die('10');
		}
		if(!$purchaseOk)
		{
			$db->query('ROLLBACK');
			$m->memClose();
			realseLock();
			die('3');
		}
		if($isLimitedBuy)
		{
			$oldZk = $zk;
			$zk += $n;
			if(!$_pm['mem'] -> set(array('k' =>$limitedSoldKey, 'v' => $zk)))
			{
				$db->query('ROLLBACK');
				$m->memClose();
				realseLock();
				die('3');
			}
		}
		if(!$db->query('COMMIT'))
		{
			if($isLimitedBuy) $_pm['mem'] -> set(array('k' =>$limitedSoldKey, 'v' => $oldZk));
			$db->query('ROLLBACK');
			$m->memClose();
			realseLock();
			die('3');
		}
		$m->del(MEM_USER_KEY);
		$m->del(MEM_USERBAG_KEY);

	}	// end inner else
}
unset($user,$wp);
//$_pm['mem']->set(
//					array(
//						'k'=>'last_buy_sm_'.$_SESSION['id'],
//						'v'=>time()
//						)
//					);
$m->memClose();
echo $err;
realseLock();

function socketData($host,$url,$flag=false){
	$fp = @fsockopen($host, 80, $errno, $errstr, 3);
	if (!$fp) {
		return false;
	} else {
		stream_set_timeout($fp, 3);
		$path = ($url != '' && $url[0] == '/') ? $url : '/'.$url;
		$out = "GET ".$path." HTTP/1.1\r\n";
		$out .= "Host: ".$host."\r\n";
		$out .= "Connection: Close\r\n\r\n";

		fwrite($fp, $out);
		$rtn = "";
		while (!feof($fp)) {
			$line = fgets($fp, 128);
			if($line === false) break;
			$rtn.= $line;
		}
		fclose($fp);
	}
	if($flag)
	{
		echo "\n\n\n".$rtn."\n\n\n";
	}
	$rtn = explode(HTTP_CONTENT_STARTED,$rtn,2);
	$rtn = isset($rtn[1]) ? $rtn[1] : $rtn[0];
	return $rtn;
}
?>
