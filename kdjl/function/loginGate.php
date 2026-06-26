<?php
/**
 * @Version: %version%
 * @Copyright: %copyright%
 * @Author: %author%
 * @Write Date: 2008.07.13
 * @Usage:Login interface.
 * @Note: none
 */
session_start();
$a = '';
$team = new team($_SESSION['team_id'], $a);
$team->checkMyTeam();

//----------------------------------------------------------
//loginGate.php和serverGate.php,fcminput.php;里面都有下面这段,保持一致
$fcmflag = false;
$partnerDomain = strtolower(substr($_SERVER['HTTP_HOST'], strpos($_SERVER['HTTP_HOST'], '.') + 1));
if (
	(
		$partnerDomain == 'webgame.com.cn'
		&& strpos($_SERVER['HTTP_HOST'], 'pmbd' === false)
		&& !preg_match("/pm51\d/", $_SERVER['HTTP_HOST'])
	)
	||
	$partnerDomain == 'qq496.cn' ||
	$partnerDomain == 'my4399.com'
) {
	$fcmflag = true;
}
switch ($partnerDomain) {
	case 'webgame.com.cn':
		$fcmSysPath = '';
		break;
	case 'qq496.cn':
	case 'my4399.com':
		$fcmSysPath = '4399/';
		break;
	default:
		$fcmSysPath = '';
		break;
}
//----------------------------------------------------------

if ($fcmflag) {//防沉迷
	$key = '*)(OJI(*77786*(**(8';

	//$urlFCMGame = 'http://61.160.192.12/' . $fcmSysPath . 'query.php?username=' . $_SESSION['username'] . '&host=' . $_SERVER['HTTP_HOST'] . '&sn=' . md5($_SERVER['HTTP_HOST'] . $_SESSION['username'] . date("Ymd") . $key);
	$urlFCMGame = 'http://47.121.183.254/' . $fcmSysPath . 'query.php?username=' . $_SESSION['username'] . '&host=' . $_SERVER['HTTP_HOST'] . '&sn=' . md5($_SERVER['HTTP_HOST'] . $_SESSION['username'] . date("Ymd") . $key);
	$rs = curlSN($urlFCMGame);
	if (strpos($rs, 'ok') === false) {

		unset($_SESSION['id']);
		unset($_SESSION['username']);
		die('<script type="text/javascript">alert("您已经在某个区在线超过3小时!\n您还暂时不能登陆,请再休息一会儿!");</script>');
	}
}
$LERROR = 0;
require_once('../config/config.game.php');

$user = $_SESSION['username'];

$rs = $_pm['mysql']->getOneRecord("SELECT * 
						  FROM player 
						 where name='{$user}' 
						 limit 0,1");
if (intval($rs['secid']) > 0) {
	if ($rs['secid'] == 40) {
		die('<script type="text/javascript">alert("您的帐号已经转区！");</script>');
	} else {
		die('<script type="text/javascript">alert("您的帐号已被冻结,请到论坛反馈！");</script>');
	}
}

$trueid = $rs['id'];

if (!is_array($rs)) {
	$LERROR = 1;
}
else {
	check($rs['id']);
	// 双倍经验时间自动开启
	if ($rs['maxdblexptime'] > 0 && $rs['dblexpflag'] > 1) {
		$rs['maxdblexptime'] = $rs['maxdblexptime'] + $rs['dblstime'] - $rs['lastvtime'];
		$rs['dblstime'] = time();
		$rs['maxdblexptime'] = $rs['maxdblexptime'] < 1 ? 0 : $rs['maxdblexptime'];
		if ($rs['maxdblexptime'] == 0) $rs['dblexpflag'] = 0;
	}


	#########################根据平台不同得到用户元宝############################
	$www = explode('.', $_SERVER['HTTP_HOST']);
	$website = '';
	for ($i = 1; $i < count($www); $i++) {
		$website .= $www[$i] . '.';
	}
	if ($website == 'webgame.com.cn.') {
		if (!preg_match('/pm51\d/is', $_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'pmbd') === false) {
			/********************************************
			 * 获得用户的元宝数，并更新到内存中。
			 */
			######平台加密，解密接口函数包
			require_once("../login/lib/passport.php");

			######平台接口通用接口函数包
			require_once("../login/lib/nusoap.php");

			// 获取玩家剩余元宝。
			$coinXml = queryCoin($_SESSION['username'], $_SESSION['licenseid']);
			//echo $coinXml;exit();
			$xmlarr = explode('Response10/Response', str_replace(array("<", ">"), "", $coinXml));
			$nowCoin = 0;
			if (count($xmlarr) > 1) {
				$endpart = explode('coin_valid![CDATA[', $xmlarr[1]);
				if (count($endpart) > 1) {
					$coinarr = explode('/coin_valid', $endpart[1]);
					$nowCoin = intval($coinarr[0]);
				}
			} else {
				$nowCoin = 0;
			}
			$rs['yb'] = $nowCoin;
			/********************************/
		}
	} else if ($website == 'qq496.cn.' || $website == 'youjia.cn.' || $website == 'my4399.com.') {
		/********************************************
		 * 获得用户的元宝数，并更新到内存中。
		 */
		$nowCoin = getCoin();
		$rs['yb'] = $nowCoin;
		/********************************/
	} else {
		$rs['yb'] = $rs['yb'];
	}
	#########################根据平台不同得到用户元宝在这里结束############################


	$useritem = md5(MEM_USER_LIST);
	// Map data start.
	if ($_pm['mem']->get($rs['id']) === false) {
		if ($_pm['mem']->get($useritem) !== false) {
			$existsUser = unserialize($_pm['mem']->get($useritem));
			if (array_search($rs['id'], $existsUser) === false) {
				array_push($existsUser, $rs['id']);
				$_pm['mem']->set(array('k' => $useritem, 'v' => $existsUser));
			}
		} else {
			$_pm['mem']->add(array('k' => $useritem, 'v' => array($rs['id'])));
		}
		$_pm['mem']->add(array('k' => $rs['id'], 'v' => $rs));
	} else // Fix userlist id.
	{
		if ($_pm['mem']->get($useritem) !== false) {
			$existsUser = unserialize($_pm['mem']->get($useritem));

			if (array_search($rs['id'], $existsUser) === false) {
				array_push($existsUser, $rs['id']);
				$_pm['mem']->set(array('k' => $useritem, 'v' => $existsUser));
			}
		} else {
			$_pm['mem']->add(array('k' => $useritem, 'v' => array($rs['id'])));
		}
	}


	###########################################################
	// Add Map key of chat.
	$key = $rs['id'] . "chat";
	if ($_REQUEST[PHPSESSID] == '' || empty($_REQUEST[PHPSESSID])) {
		$_REQUEST[PHPSESSID] = session_id();
	}


	$crc = $_REQUEST[PHPSESSID];
	if ($_pm['mem']->get($key) === false) {
		$_pm['mem']->add(array('k' => $key, 'v' => $crc));
	} else {
		$oldcrc = unserialize($_pm['mem']->get($key));
		$_pm['mem']->set(array('k' => $key, 'v' => $crc));
		$_pm['mem']->del($oldcrc);
	}

	if ($_pm['mem']->get($crc) === false) {
		$_pm['mem']->add(array('k' => $crc, 'v' => $rs['nickname']));
	} else {
		$_pm['mem']->set(array('k' => $crc, 'v' => $rs['nickname']));
	}

	$_SESSION['id'] = $rs['id'];
	$_SESSION['nickname'] = $rs['nickname'];
	$_SESSION['lastvtime'] = $rs['lastvtime'];
	$_SESSION['password'] = $rs['password'];//告诉socket玩家是否被禁言！
	$_SESSION['vip'] = false;//告诉socket玩家是否是Vip！ ---> 下面查询是否有vip卡


	$arr = array("1427", "1474", "1475", "1476", "1477", "1478", "1479", "1480", "1481", "1482", "1483", "1484", "1485");
	$arrayid = date('n');
	if ($arrayid == '1') {
		$arraycode = array("1427", $arr[$arrayid], $arr[12]);
	} else {
		$arrayidjian = $arrayid - 1;
		$arraycode = array("1427", $arr[$arrayidjian], $arr[$arrayid]);
	}
	$u_bags = getUserBagByIds($_SESSION['id'], $arraycode, $_pm['mysql']); /* 口袋精灵VIP卡:1427 */

	// $u_bags=getUserBagById($_SESSION['id'], 1427, $_pm['mysql']); /* 口袋精灵VIP卡:1427 */
	foreach ($u_bags as $v) {
		if ($v && isset($v['sums']) && $v['sums'] > 0) {
			$_SESSION['vip'] = 2;
			break;
		}
	}
	$sql = "select merge,now_Achievement_title from player_ext where uid = {$_SESSION['id']}";
	$arr_merge = $_pm['mysql']->getOneRecord($sql);
	if ($arr_merge['merge'] > 0) {
		$_SESSION['vip'] = 3;
		//$truename = $truename . '<img src="../images/merge.gif" />';
	}
	if (isset($arr_merge['now_Achievement_title']) && !empty($arr_merge['now_Achievement_title'])) {
		$_SESSION['now_Achievement_title'] = $arr_merge['now_Achievement_title'];
		$sql = " SELECT F_title_Chinese FROM t_card_to_title WHERE F_title_name = '" . $arr_merge['now_Achievement_title'] . "'";
		$result_title_chinese = $_pm['mysql']->getOneRecord($sql);

		$_SESSION['now_Achievement_title_chinese'] = $result_title_chinese['F_title_Chinese'];
	} else {
		unset($_SESSION['now_Achievement_title']);
		unset($_SESSION['now_Achievement_title_chinese']);
	}
	del_bag_expire();
	$_pm['mysql']->query("UPDATE player
				  SET lastvtime=" . time() . ",
					  dblstime=" . $rs['dblstime'] . ",
					  maxdblexptime=" . $rs['maxdblexptime'] . ",
					  yb='{$rs['yb']}'
				WHERE id={$rs['id']}
			 ");
	$_pm['mysql']->query("UPDATE player_ext
				  SET last_logintime=" . time() . "
				WHERE uid={$rs['id']}
			 ");
//	$_pm['mem']->set(array('k'=>MEM_SYSWORD_KEY,
//				  'v'=>'欢迎'.$rs['sex'].' '.$rs['nickname'].' 回到口袋精灵世界！'));
	##########################################################
	/*
    // 清空该用户的数据。
    $_pm['mem']->del(MEM_USER_KEY);
    $_pm['mem']->del(MEM_USERBB_KEY);
    $_pm['mem']->del(MEM_USERSK_KEY);
    $_pm['mem']->del(MEM_USERBAG_KEY);

    // 缓存用户数据开始
    $_pm['user']->updateMemUser($rs['id']);
    $_pm['user']->updateMemUserbb($rs['id']);
    $_pm['user']->updateMemUsersk($rs['id']);
    $_pm['user']->updateMemUserbag($rs['id']);
    // 缓存用户数据结束.
    */
	//$_pm['mem']->memClose();
}

//解禁
//$id 表示用户ID
function check($id)
{
	global $_pm;
	$nowTime = time();
	$sql = "SELECT password 
			FROM player 
			WHERE id = {$id}";
	$row = $_pm['mysql']->getOneRecord($sql);
	if (!empty($row['password'])) {
		$jyTime = intval($row['password']);
		if ($jyTime <= intval($nowTime)) {
			$sql = "UPDATE player
					SET password = '0'
					WHERE id = {$id}";
			$_pm['mysql']->query($sql);
		}
	}
}

function getCoin()
{
	global $_pm;
	$arr = $_pm['mysql']->getOneRecord("SELECT yb FROM player WHERE id = {$_SESSION['id']}");
	$api_code = '4399_Pm_Gold_WCQmhS7FDvnv533b';
	$flag = md5($_SESSION['userid'] . '|' . urlencode($_SESSION['username']) . '|' . $api_code);
	$ret = http_get_result("http://web.4399.com/api/kdjl/query_gold.php?UserId=" . $_SESSION['userid'] . '&UserName=' . urlencode($_SESSION['username']) . '&flag=' . $flag . '&gold=' . $arr['yb']);
	if ($ret == 'fail') return 0;
	else return intval($ret);
}

function http_get_result($url, $time_out = "10")
{
	$urlarr = parse_url($url);
	$errno = "";
	$errstr = "";
	$transports = "";
	if ($urlarr["scheme"] == "https") {
		$transports = "ssl://";
		$urlarr["port"] = "443";
	} else {
		$transports = "tcp://";
		$urlarr["port"] = "80";
	}
	$fp = @fsockopen($transports . $urlarr['host'], $urlarr['port'], $errno, $errstr, $time_out);
	if (!$fp) {
		die("ERROR: $errno - $errstr<br />\n");
	} else {
		$out = "GET " . $urlarr["path"] . '?' . $urlarr["query"] . " HTTP/1.1\r\n";
		$out .= "Accept: */*\r\n";
		$out .= "Accept-Language: zh-cn\r\n";
		$out .= "UA-CPU: x86\r\n";
		$out .= "User-Agent: 4399_kdjl_interface\r\n";
		$out .= "Host: " . $urlarr["host"] . "\r\n";
		$out .= "Connection: Close\r\n";
		$out .= "\r\n";

		fwrite($fp, $out);

		while (!feof($fp)) {
			$info[] = @fgets($fp, 4096);
		}

		fclose($fp);

		//去除返回的HTTP文件头
		$pos = -1;
		for ($i = 0; $i < count($info) - 1; $i++) {
			$tmp = trim($info[$i], "\r\n ");
			if (empty($tmp)) {
				$pos = $i + 1;
				break;
			}
		}
		if ($pos > -1) {
			$len = hexdec($info[$pos]);
			//获得返回的结果
			$result = substr($info[$pos + 1], 0, $len);
			return $result;
		}

		return '';
	}
}


function getUserBagByIds($id, $pidarr, &$mysql)
{
	$id = intval($id);
	foreach ($pidarr as $v) {
		$rs[] = $mysql->getOneRecord("SELECT b.id as id,
									  b.uid as uid,
									  b.sums as sums,
									  b.pid as pid,
									  b.vary as vary,
									  b.psell as psell,
									  b.pstime as pstime,
									  b.petime as petime,
									  b.bsum as bsum,
									  b.psum as psum,
									  b.zbing as zbing,
									  b.zbpets as zbpets,
									  b.plus_tms_eft as plus_tmes_eft,
									  p.name as name,
									  p.varyname as varyname,
									  p.effect as effect,
									  p.requires as requires,
									  p.usages as usages,
									  p.sell as sell,
									  p.img as img,
									  p.pluseffect as pluseffect,
									  p.postion as postion,
									  p.plusflag as plusflag,
									  p.pluspid as pluspid,
									  p.plusget as plusget,
									  p.plusnum as plusnum,
									  p.series as series,
									  p.serieseffect as serieseffect,
									  p.propslock as propslock,
									  p.prestige as prestige
								 FROM userbag as b,props as p
								WHERE 
								b.pid={$v} and
								p.id = b.pid and b.uid={$id} and b.sums>0
								ORDER BY b.id DESC limit 1");
	}
	return $rs;
}

function curlSN($url, $port = 80)
{
	$post = 1;
	$returntransfer = 1;
	$header = 0;
	$nobody = 0;
	$followlocation = 1;

	$ch = curl_init();
	$options = array(CURLOPT_URL => $url,
		CURLOPT_HEADER => $header,
		CURLOPT_NOBODY => $nobody,
		CURLOPT_PORT => $port,
		CURLOPT_POST => $post,
		CURLOPT_POSTFIELDS => $request,
		CURLOPT_RETURNTRANSFER => $returntransfer,
		CURLOPT_FOLLOWLOCATION => $followlocation,
		CURLOPT_COOKIEJAR => $cookie_jar,
		CURLOPT_COOKIEFILE => $cookie_jar,
		CURLOPT_REFERER => $url
	);
	curl_setopt_array($ch, $options);
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}

?>
