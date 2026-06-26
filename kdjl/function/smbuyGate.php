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
//---------------------------
//通过判断域名来确定是否需要向平台购买元宝
$Domain=explode('.',$_SERVER['HTTP_HOST']);
$DomainName1=$Domain[1];
$DomainName2=$Domain[2];
//---------------------------
$user	= $u->getUserById($_SESSION['id']);
$bags    = $u->getUserBagById($_SESSION['id']);
if($user===FALSE) {$err=1;}

$bid = intval($_REQUEST['bid']); // table: props => id
$n	 = intval($_REQUEST['n']); 

require_once('../sec/dblock_fun.php');
$a = getLock($_SESSION['id']);

//$lastbuy=unserialize($_pm['mem']->get('last_buy_sm_'.$_SESSION['id']));
//if($lastbuy+3>time())
//{
//	realseLock();
//	die('服务器繁忙，购买时间间隔太短！');
//}
if(!is_array($a)){
	realseLock();
	die('服务器繁忙，请稍候再试！');
}

if($n <= 0)
{
	realseLock();
	die('2');
}

if( !is_int($bid) || $bid<1 || $n<1) $err = 2;

/*$wp= $m->dataGet(array('k' => MEM_PROPS_KEY, 
					   'v' => "if(\$rs['id'] == '{$bid}' && \$rs['yb']>0) \$ret=\$rs;"
				 ));*/
$wp = $_pm['mysql'] -> getOneRecord("SELECT * FROM props WHERE id = $bid and (yb > 0 or zhekouyb > 0)");
if($wp['stime']<=0)
	die('don"t be evil.');
if($wp['zhekouyb'] > 0){
	$zk = unserialize($_pm['mem']->get('zhekou_'.$wp['id'].'_num'));
	
	$time = date('Y-m-d H:i:s');
	$sql = 'SELECT value2,contents FROM welcome WHERE code = "timelimitbuy"';
	$tm = $_pm["mysql"] -> getOneRecord($sql);
	$tarr = explode('|',$tm['value2']);
	if(!is_array($tm)){
		realseLock();
		die('活动未开启1！');
	}
	if($time < $tarr[0] || $time > $tarr[1]){
		realseLock();
		die('活动未开启2！');
	}
	
	//if($zk > 0){
		$pa = explode(',',$tm['contents']);
		foreach($pa as $pv){
			$parr = explode(':',$pv);
			if($parr[0] == $wp['id']){
				$s = $parr[1] - $zk;//echo $s.'<br />'.$n.'<br />'.$zk.'<br />';print_r($parr[1]);
				if($s < $n){
					realseLock();
					die('您购买的物品剩余数量不够！');
				}
				else if($s >= $n){
					break;
				}
				if($parr[1] <= $zk){
					realseLock();
					die('已售完！');
				}
				
			}
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
	if(empty($price)){
		$price = $wp['zhekouyb']*$n;
	};
	if(empty($price))
	{
		realseLock();
		die("3");
	}
	
	$nowCoin = $user['yb'];

	
	//--------------------------
	//如果为webgame的请求
	//--------------------------
	if($DomainName1=='webgame'&&!preg_match('/pm51\d/is',$_SERVER['HTTP_HOST'])&&!preg_match('/kdjl\d/is',$_SERVER['HTTP_HOST'])&&!preg_match('/^pmbd\d/is',$_SERVER['HTTP_HOST']))
	{
		######平台加密，解密接口函数包
		require_once("../login/lib/passport.php");
		
		######平台接口通用接口函数包
		require_once("../login/lib/nusoap.php");
		// 获取玩家剩余元宝。
		$coinXml = queryCoin($_SESSION['username'],$_SESSION['licenseid']);
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
		
		if($DomainName1=='webgame'&&!preg_match('/pm51\d/is',$_SERVER['HTTP_HOST'])&&!preg_match('/kdjl\d/is',$_SERVER['HTTP_HOST'])&&!preg_match('/^pmbd\d/is',$_SERVER['HTTP_HOST']))
		{
			######平台加密，解密接口函数包
			require_once("../login/lib/passport.php");
			
			######平台接口通用接口函数包
			require_once("../login/lib/nusoap.php");
			// 付费##################################################
			// $pay is xml document.
			$host = str_replace("PM51","",strtoupper(substr($_SERVER['HTTP_HOST'],0,strpos($_SERVER['HTTP_HOST'],'.'))));
			$ordid=$host.substr("000000000000".$_SESSION['id'],-8).substr(time(),-9);			
			$pay=payment($_SESSION['username'],
						$_SESSION['licenseid'],
						$ordid,
						$price,
						$_SESSION['username']."购买口袋精灵二[1区]道具 ".$wp['name']." {$n}个。");
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
				$sql='INSERT INTO `shop_order` SET `uid`="'.$_SESSION['id'].'", `uname`="'.$_SESSION['username'].'", `pid`="'.$wp['id'].'", `pnum`="'.$n.'", `create_time`='.time().', `flag`=0,fee="'.$price.'",order_id="'.$ordid.'";';
				$db->query($sql);
				//header('Content-Type:text/html;charset=GBK');
				 realseLock();
				 die("元宝支付失败!");
			}else{
				$sql='INSERT INTO `shop_order` SET `uid`="'.$_SESSION['id'].'", `uname`="'.$_SESSION['username'].'", `pid`="'.$wp['id'].'", `pnum`="'.$n.'", `create_time`='.time().', `flag`=1,fee="'.$price.'",order_id="'.$ordid.'";';
				$db->query($sql);
			}
		}
		//-----------------------------
		
		//----------------------------
		//如果为4399的请求
		//----------------------------
		if(($DomainName2=='cn'||$DomainName2=='com')&&($DomainName1=='youjia'||$DomainName1=='qq496'||$DomainName1=='my4399'))
		{
			$host = str_replace("KD","",strtoupper(substr($_SERVER['HTTP_HOST'],0,strpos($_SERVER['HTTP_HOST'],'.'))));
			$ordid=$host.substr("000000000000".$_SESSION['id'],-9).substr(time(),-9);
			
			$now = time();
			$api_code = '4399_Pm_Gold_WCQmhS7FDvnv533b';
			$rflag = md5($_SESSION['userid'].'|'.urlencode($_SESSION['username']).'|'.'S1'.'|'.$price.'|'.$now.'|'.$api_code);
			$utype = urlencode(base64_encode(iconv("GBK","UTF-8",$wp['name'])));
			$number = $n;
			$desc = urlencode(base64_encode(iconv("GBK","UTF-8",$_SESSION['username']."购买口袋精灵二[1区]道具 ".$wp['name']." {$n}个。")));
			$par = 'UserId='.$_SESSION['userid'].'&UserName='.$_SESSION['username'].'&ServerId=S1&UseGold='.$price.'&UseTime='.$now.'&flag='.$rflag.'&UseType='.$utype.'&Number='.$n.'&Desc='.$desc.'&orderid='.$ordid;
	
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
				$time_exit = $array[2]+8;
				if(strpos($rets,'order_exist|') !==false && strpos($rets,'true|') !==false && time()<$time_exit)
				{
					echo "购买成功";
					$err='';
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
		
		
		
		$db->query("insert into yblog(title,nickname,yb,buytime,pname,nums)
				    values(".$wp['id'].",'{$_SESSION['username']}','{$price}',unix_timestamp(),'{$wp['name']}',{$n})
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
		/*$db->query("update player set yb={$user['yb']},useyb={$useyb},score=score + {$score},active_useyb={$active_useyb},active_score=active_score+{$active_score} where id={$_SESSION['id']}");*/
		$d = date('YmdHi');
		$sql = 'SELECT days FROM timeconfig WHERE titles = "vip_multi" AND starttime <'.$d.' AND endtime >'.$d;
		$multi = $db -> getOneRecord($sql);
		if(is_array($multi)){
			$vip = $vip * $multi['days'];
		}
		$db->query("update player set yb={$user['yb']},useyb={$useyb},score=score + {$score},vip = vip + {$vip},vipyb = {$vipyb} where id={$_SESSION['id']}");
		//内存数据加个数
		if($wp['zhekouyb'] > 0){
			$zk+=$n;
			$_pm['mem'] -> set(array('k' =>'zhekou_'.$wp['id'].'_num', 'v' => $zk));
		}
		
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
	$fp = @fsockopen($host, 80, $errno, $errstr, 30);
	if (!$fp) {
		return false;
	} else {
		$out = "GET /".$url." HTTP/1.1\r\n";
		$out .= "Host: ".$host."\r\n";
		$out .= "Connection: Close\r\n\r\n";
	
		fwrite($fp, $out);
		$rtn = "";
		while (!feof($fp)) {
			$rtn.= fgets($fp, 128);
		}
		fclose($fp);
	}	
	if($flag)
	{
		echo "\n\n\n".$rtn."\n\n\n";
	}
	$rtn = split(HTTP_CONTENT_STARTED,$rtn,2);
	$rtn = $rtn[1];
	return $rtn;
}
?>
