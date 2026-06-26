<?php
require_once('../config/config.game.php');
//secStart($_pm['mem']);

$user	 = $_pm['user']->getUserById($_SESSION['id']);
$bag	= $_pm['user']->getUserBagById($_SESSION['id']);
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
	$rtn=explode("\r\n\r\n",$rtn,2);
	return $rtn[1];
}

$apiDomain='card.webgame.com.cn';
$apiFile='/api.php';
$key='&)67&*(&*()sdadfJ';
//http://接口地址?server=域名&card=卡号&pass=密码&account=通行证账号&role=角色Id&time=请求时间&sign=md5签名
$domain=$_SERVER['HTTP_HOST'];
//$account=$_REQUEST['username'];
$account=$_SESSION['username'];
$role=$_SESSION['id'];
$time=time();
$code = $_GET['id'];
$regcheck = $_pm['mysql'] -> getOneRecord("SELECT id FROM player WHERE name = '{$_SESSION['username']}' AND password != '00000000000000000000000000000000'");
if(!empty($regcheck['id'])){
	//不能使用公会卡
	$cflag = md5($_GET['cardid'].$_GET['pwd'].$key);
	$checkurl = '/apit.php?server='.$domain.'&card='.$_REQUEST['cardid'].'&pass='.$_REQUEST['pwd'].'&sign='.$cflag;
	$res = curl('http://'.$apiDomain.$checkurl);//echo 'http://'.$apiDomain.$checkurl;exit;
	$y=explode("|",$res,2);
	if($y[0]!=='10'){
		die($y[1]);
	}else{
		if($y[1] == 3){
			die('您使用的卡类型不正确！');
		}
	}
}else{
	//只能使用公会卡
	$cflag = md5($_GET['cardid'].$_GET['pwd'].$key);
	$checkurl = '/apit.php?server='.$domain.'&card='.$_REQUEST['cardid'].'&pass='.$_REQUEST['pwd'].'&sign='.$cflag;
	//echo $checkurl;exit;
	$res = curl('http://'.$apiDomain.$checkurl);
	$y=explode("|",$res,2);
	if($y[0]!=='10'){
		die($y[1]);
	}else{
		if($y[1] != 3){
			die('此卡需要注册后使用！');
		}
	}
	$_SESSION['ghflag'] = 1;
}
if(isset($_REQUEST['cardid'])){
	if($role <= 0){
		$role = 123;
	}
	$sign=md5($domain.$_REQUEST['cardid'].$_REQUEST['pwd'].$account.$role.$time.$key);
	$url=$apiFile.'?server='.$domain.'&card='.$_REQUEST['cardid'].'&pass='.$_REQUEST['pwd'].'&account='.$account.'&role='.$role.'&time='.$time.'&sign='.$sign;
	if($code != '0'){
		$url .= '&code='.$code;
	}
	//echo $apiDomain.$url;exit;
	//echo '<br>返回值：=><br>'.socketData($apiDomain,$url).'<br>';
	//$res = socketData($apiDomain,$url);echo __FILE__.":".__LINE__."<br>";echo $res;exit;
	//echo 'http://'.$apiDomain.$url;exit;
	$res = curl('http://'.$apiDomain.$url);
	$x=explode("|",$res,2);
	if($x[0]!=='10')
	die($x[1]);//失败
	$str = '';
	if($_SESSION['ghflag'] != 1){
		$numarr = explode("\r\n",$x[1]);
		$arr = explode(',',$numarr[0]);
		
		$task = new task();
		if(is_array($arr)){
			foreach($arr as $v){
				$inarr = explode(':',$v);
				if(count($inarr) != 2){
					continue;
				}
				$givecheck = $task->saveGetPropsMore($inarr[0],$inarr[1]);
				if($givecheck === true){
					$parr = $_pm['mysql'] -> getOneRecord("SELECT name FROM props WHERE id = {$inarr[0]}");
					$str .= '获得物品：'.$parr['name'].'&nbsp;'.$inarr[1].' 件，';
				}else{
					$checkflag = '存在背包满了，发不下奖励的情况！';
				}
			}
		}
		$pnote = 'card='.$_REQUEST['cardid'].'&pass='.$_REQUEST['pwd'].'&应得奖励：'.$numarr[0].'----实际：'.$str.'-----'.$checkflag;
		$_pm['mysql'] -> query("insert into gamelog (ptime,seller,buyer,pnote,vary) values (".time().",{$_SESSION['id']},{$_SESSION['id']},'$pnote',91)");
		if(!empty($str)){
			 $str=substr($str,0,-2);
		}
		if(isset($checkflag) && !empty($checkflag)){
			$str .= ','.$checkflag;
		}
	}else{
		$_SESSION['ghpstr'] = $x[1];
	}
	if(empty($str)){
		$str = '不好意思，可能是您太倒霉了，没有得到任何奖励！';
	}//echo $_SERVER['HTTP_REFERER']
	echo $str;
}
?>