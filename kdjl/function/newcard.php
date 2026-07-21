<?php
require_once('../config/config.game.php');
//secStart($_pm['mem']);

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1 || !isset($_SESSION['username']) || $_SESSION['username'] == '')
{
	die('登录状态已失效，请重新登录！');
}
$user	 = $_pm['user']->getUserById($uid);
$bag	= $_pm['user']->getUserBagById($uid);
if(!is_array($user)) $user = array();
if(!is_array($bag)) $bag = array();
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
	$rtn=explode("\r\n\r\n",$rtn,2);
	return isset($rtn[1]) ? $rtn[1] : $rtn[0];
}

$apiDomain='card.webgame.com.cn';
$apiFile='/api.php';
$key='&)67&*(&*()sdadfJ';
//http://接口地址?server=域名&card=卡号&pass=密码&account=通行证账号&role=角色Id&time=请求时间&sign=md5签名
$domain=isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
if(!preg_match('/^[A-Za-z0-9.-]{1,255}(:[0-9]{1,5})?$/', $domain)) $domain = '';
$domainUrl = rawurlencode($domain);
//$account=$_REQUEST['username'];
$account=isset($_SESSION['username']) ? $_SESSION['username'] : '';
$accountSql = $_pm['mysql']->escape($account);
$role=$uid;
$time=time();
$code = (isset($_GET['id']) && !is_array($_GET['id'])) ? $_GET['id'] : '0';
$cardid = (isset($_REQUEST['cardid']) && !is_array($_REQUEST['cardid'])) ? trim($_REQUEST['cardid']) : '';
$pwd = (isset($_REQUEST['pwd']) && !is_array($_REQUEST['pwd'])) ? trim($_REQUEST['pwd']) : '';
$cardidUrl = rawurlencode($cardid);
$pwdUrl = rawurlencode($pwd);
$_SESSION['ghflag'] = 0;
if($cardid == '' || $pwd == ''){
	die('卡片包为空！');
}
$regcheck = $_pm['mysql'] -> getOneRecord("SELECT id FROM player WHERE name = '{$accountSql}' AND password != '00000000000000000000000000000000'");
if(!empty($regcheck['id'])){
	//不能使用公会卡
	$cflag = md5($cardid.$pwd.$key);
	$checkurl = '/apit.php?server='.$domainUrl.'&card='.$cardidUrl.'&pass='.$pwdUrl.'&sign='.$cflag;
	$res = socketData($apiDomain, $checkurl);//echo 'http://'.$apiDomain.$checkurl;exit;
	$y=explode("|",$res,2);
	$y0 = isset($y[0]) ? $y[0] : '';
	$y1 = isset($y[1]) ? $y[1] : '卡片服务器返回错误！';
	if($y0!=='10'){
		die($y1);
	}else{
		if($y1 == 3){
			die('您使用的卡类型不正确！');
		}
	}
}else{
	//只能使用公会卡
	$cflag = md5($cardid.$pwd.$key);
	$checkurl = '/apit.php?server='.$domainUrl.'&card='.$cardidUrl.'&pass='.$pwdUrl.'&sign='.$cflag;
	//echo $checkurl;exit;
	$res = socketData($apiDomain, $checkurl);
	$y=explode("|",$res,2);
	$y0 = isset($y[0]) ? $y[0] : '';
	$y1 = isset($y[1]) ? $y[1] : '卡片服务器返回错误！';
	if($y0!=='10'){
		die($y1);
	}else{
		if($y1 != 3){
			die('此卡需要注册后使用！');
		}
	}
	$_SESSION['ghflag'] = 1;
}
if($cardid != ''){
	if($role <= 0){
		$role = 123;
	}
	$sign=md5($domain.$cardid.$pwd.$account.$role.$time.$key);
	$url=$apiFile.'?server='.$domainUrl.'&card='.$cardidUrl.'&pass='.$pwdUrl.'&account='.rawurlencode($account).'&role='.$role.'&time='.$time.'&sign='.$sign;
	if($code != '0'){
		$url .= '&code='.rawurlencode($code);
	}
	//echo $apiDomain.$url;exit;
	//echo '<br>返回值：=><br>'.socketData($apiDomain,$url).'<br>';
	//$res = socketData($apiDomain,$url);echo __FILE__.":".__LINE__."<br>";echo $res;exit;
	//echo 'http://'.$apiDomain.$url;exit;
	$res = socketData($apiDomain, $url);
	$x=explode("|",$res,2);
	$x0 = isset($x[0]) ? $x[0] : '';
	$x1 = isset($x[1]) ? $x[1] : '卡片服务器返回错误！';
	if($x0!=='10') die($x1);
	$str = '';
	$checkflag = '';
	$numarr = array('');
	if($_SESSION['ghflag'] != 1){
		$numarr = explode("\r\n",$x1);
		$arr = explode(',',$numarr[0]);

		$task = new task();
		$remainingRewards = array();
		$awardedAny = false;
		if(is_array($arr)){
			foreach($arr as $v){
				$inarr = explode(':',$v);
				if(count($inarr) != 2){
					$checkflag .= '奖励配置错误；';
					continue;
				}
				$pid = intval($inarr[0]);
				$num = intval($inarr[1]);
				if($pid < 1 || $num < 1){
					$checkflag .= '奖励配置错误；';
					continue;
				}
				$parr = $_pm['mysql'] -> getOneRecord("SELECT name FROM props WHERE id = {$pid}");
				if(!is_array($parr)){
					$checkflag .= '道具 '.$pid.' 不存在；';
					continue;
				}
				$givecheck = false;
				if($_pm['mysql']->query('START TRANSACTION')){
					$givecheck = $task->saveGetPropsMore($pid,$num);
					if($givecheck === true && $_pm['mysql']->query('COMMIT')){
						$awardedAny = true;
					$str .= '获得物品：'.$parr['name'].'&nbsp;'.$inarr[1].' 件，';
						continue;
					}
					$_pm['mysql']->query('ROLLBACK');
				}
				$remainingRewards[] = $pid.':'.$num;
				$checkflag .= ($givecheck === '200') ? '背包空间不足；' : '奖励发放失败；';
			}
		}
		if($awardedAny) $_pm['mem']->del(MEM_USERBAG_KEY);
		$_SESSION['ghpstr'] = empty($remainingRewards) ? '' : implode(',',$remainingRewards);
		$pnote = 'card='.$cardid.'&pass=***&应得奖励：'.$numarr[0].'----实际：'.$str.'-----'.$checkflag;
		$pnote = $_pm['mysql']->escape($pnote);
		$uidSql = intval($uid);
		$_pm['mysql'] -> query("insert into gamelog (ptime,seller,buyer,pnote,vary) values (".time().",{$uidSql},{$uidSql},'{$pnote}',91)");
		if(!empty($str)){
			$tailComma = '，';
			if(substr($str, -strlen($tailComma)) === $tailComma){
				$str = substr($str, 0, strlen($str) - strlen($tailComma));
			}
		}
		if(!empty($checkflag)){
			$str .= ','.$checkflag;
		}
	}else{
		$_SESSION['ghpstr'] = $x1;
	}
	if(empty($str)){
		$str = '不好意思，可能是您太倒霉了，没有得到任何奖励！';
	}//echo $_SERVER['HTTP_REFERER']
	echo $str;
}
?>
