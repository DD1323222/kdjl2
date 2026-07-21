<?php
/**
@Usage: Server message send center.
@Version: 1.0.1
@Copyright: www.webgame.com.cn
*/
set_time_limit(0);
require_once('../config/config.game.php');
$socketchatflag=false;
$refreshtime=2500;
if(file_exists('../socketChat/config.chat.php'))
{
	$refreshtime=120000;
	$socketchatflag=true;
}
$computeOnline = false;
$welcome = memContent2Arr("db_welcome",'code');
$serverGateIsGm = kdjlCurrentUserIsAdmin();

if(isset($welcome['openonlinetimestat']['contents'])&&$welcome['openonlinetimestat']['contents']==1)
{
	$computeOnline = true;
}

$serverGateUid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($computeOnline && $serverGateUid > 0){
	$_pm['mysql']->query('update player_ext set logintime='.time().' where uid='.$serverGateUid);
}
function get_real_ip(){
	$ip=false;

	if(!empty($_SERVER["HTTP_CLIENT_IP"]) && !is_array($_SERVER["HTTP_CLIENT_IP"]) && filter_var($_SERVER["HTTP_CLIENT_IP"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)){
		$ip = $_SERVER["HTTP_CLIENT_IP"];
	}

	if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && !is_array($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ips = explode(",", $_SERVER['HTTP_X_FORWARDED_FOR']);
		if ($ip) {
			array_unshift($ips, $ip); $ip = FALSE;
		}
		for ($i = 0; $i < count($ips); $i++) {
			$ipCandidate = trim($ips[$i]);
			if (filter_var($ipCandidate, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && !preg_match("/^(10|172\.(1[6-9]|2[0-9]|3[0-1])|192\.168)\./i", $ipCandidate)) {
				$ip = $ipCandidate;
				break;
			}
		}
	}
	$remoteAddr = (isset($_SERVER['REMOTE_ADDR']) && !is_array($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';
	return ($ip ? $ip : (filter_var($remoteAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? $remoteAddr : ''));
}
secStart($_pm['mem']);
if(isset($_GET['setrefreshpage']))
{

	$_SESSION['refreshpage'] = time();
	die('var rs="OK";');
}


$_pm['mysql']->close();

$isMultiServer = true;//isset($_SERVER["HTTP_X_REAL_IP"])&&isset($_SERVER["HTTP_X_FORWARDED_FOR"])||($_SERVER["SERVER_SOFTWARE"]=='nginx');

header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header('Content-Type:text/html;charset=utf-8');
session_write_close();
flush();
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
<?php
 $fcmmsgecho = isset($fcmmsgecho) ? $fcmmsgecho : '';
 echo $fcmmsgecho;
//if($dbg)die();
 ?>
<script type="text/javascript">
  // KHTML browser don't share javascripts between iframes
  var is_khtml = navigator.appName.match("Konqueror") || navigator.appVersion.match("KHTML");
  if (is_khtml)
  {
    var prototypejs = document.createElement('script');
    prototypejs.setAttribute('type','text/javascript');
    prototypejs.setAttribute('src','/javascript/prototype.js');
    var head = document.getElementsByTagName('head');
    head[0].appendChild(prototypejs);
  }
  //window.parent.parent.goToIndex();
  // load the comet object
  var comet = window.parent.comet;
</script>
<?php
//$m = $_pm['mem'];	// Init memcache.
define("MEM_BLACKLIST_KEY","db_blacklist");
$requestSessionId = (isset($_REQUEST['PHPSESSID']) && !is_array($_REQUEST['PHPSESSID'])) ? $_REQUEST['PHPSESSID'] : session_id();
if(!preg_match('/^[A-Za-z0-9,-]{1,128}$/', $requestSessionId)) $requestSessionId = session_id();
$crc = crc32($requestSessionId);
$chatUserValue = $_pm['mem']->get($crc);
if($chatUserValue === false) $chatUserValue = $_pm['mem']->get($requestSessionId);
$user = kdjlSafeMemValue($chatUserValue, '');
if(!is_string($user)) $user = '';
$key = crc32($user);
$key = $key<1?1-$key-1:$key;

$sleepInterval = 2;

$time = time();
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1)
{
	echo '<script type="text/javascript">try{window.parent.parent.goToIndex();}catch(e){}</script>';
	$_pm['mem']->memClose();
	exit;
}
$msg = '';

$row = $_pm['mysql']->getOneRecord('select logintime,onlinetime from player_ext where uid='.$uid);
if(empty($row))
{
	$_pm['mysql']->query('insert into player_ext(uid,bbshow,onlinetime,logintime) values('.$uid.',5,0,'.time().')');
	$row = array();
	$row['logintime'] = time();
	$row['onlinetime'] = 0;
}

$logintime = $row['logintime'];
$onlinetime = $row['onlinetime'];


$rand = $uid%60;
$lastdomd = intval($_pm['mem'] -> get('last_do_md_'.$uid));
while(1) {
	$h = date('H');
	$m = date('i');
	$dh = date('mdH');
	if($computeOnline&&$lastdomd!=$dh&&$rand == $m)
	{
		$lastdotime = intval($_pm['mem'] -> get('last_do_'.$uid));
		//echo '<br>time()%10='.(time()%10)."<br/>";
		$lastvisttime = intval($_pm['mem'] -> get('last_visit_'.$uid));
		if($lastdotime > 0 && $lastvisttime > 0){
			$newvalue=intval($onlinetime)+max(0,$lastvisttime-$lastdotime);
			$sql = 'update player_ext set onlinetime='.$newvalue.' where uid='.$uid;

			$_pm['mysql']->close();
			$_pm['mysql']	= new mysql();

			$_pm['mysql']->query($sql);
			$_pm['mem'] -> set(array('k' => 'last_do_'.$uid,'v' => $time));
			$_pm['mem'] -> set(array('k' => 'last_visit_'.$uid,'v' => $time));
			$_pm['mem'] -> set(array('k' => 'last_do_md_'.$uid,'v' => $dh));
		}

		flush();
	}

	$cmdresult=1;$time=time();

	//GM公告
	//要发公告的时间：
	$somecontent = "";
	if(date('s')%10<$sleepInterval)
	{
		$msg_key = 'chatMsgListLoundSpeaker';//小喇叭
		$loudspeak	= kdjlSafeMemValue($_pm['mem']->get($msg_key), array());
		if(is_array($loudspeak)){
			foreach($loudspeak as $k=>$v){
				$somecontent = str_replace(array("\r","\n",'"'),array('','','\"'),$v).'';
			}
		}
	}

	if(date('s')%59==$sleepInterval){
		$dt = date("YmdHi");
		$gonggao = kdjlSafeMemValue($_pm['mem'] -> get(MEM_GONGGAO_KEY), array());
		if(!is_array($gonggao)) $gonggao = array();
		$gonggaomsg = array();
		$retstr = '';
		//$curMsg=stripslashes(unserialize($_pm['mem']->get('chatMsgList')));
		foreach($gonggao as $gg)
		{
			if(!is_array($gg)) continue;
			$announceInterval = isset($gg['times']) ? intval($gg['times']) : 0;
			$announceStart = isset($gg['starttime']) ? $gg['starttime'] : '';
			$announceEnd = isset($gg['endtime']) ? $gg['endtime'] : '';
			$announceMessage = isset($gg['msg']) ? $gg['msg'] : '';
			$announceId = isset($gg['Id']) ? $gg['Id'] : 0;
			if($announceInterval < 1) continue;
			if($announceStart <= $dt && $announceEnd >= $dt)
			{
				if(round(time()/60)%$announceInterval == 0)
				{
					if($announceMessage=="resetchat")
					{
						echo '<script type="text/javascript">';
						echo "setTimeout('window.location.reload();',500);";
						echo '</script>';
						die();
					}
					if($announceMessage=="refreshpage")
					{
						$msg_key = 'chatMsgList';
						$nowMsgList = kdjlSafeMemValue($_pm['mem']->get($msg_key), '');
						if(!is_string($nowMsgList)) $nowMsgList = '';
						$arr = explode('linend', $nowMsgList);
						if( count($arr)>20 ) // cear old
						{
							$arrt = array_shift($arr);
						}

						$newstr = '<font color=red>[系统公告]游戏将在第一次公告的 60 秒之后更新数据，届时会自动刷新整个页面，为大家带来的不便表示抱歉。</font>';

						foreach($arr as $k=>$v)
						{
							$retstr .= $v.'linend';
						}
						if(strpos($nowMsgList,$newstr )===false){
							$serverHttpHost = (isset($_SERVER['HTTP_HOST']) && !is_array($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : '';
							if(!preg_match('/^[A-Za-z0-9.-]{1,255}(:[0-9]{1,5})?$/', $serverHttpHost)) $serverHttpHost = '';
							$serverPort = (isset($_SERVER['SERVER_PORT']) && !is_array($_SERVER['SERVER_PORT'])) ? $_SERVER['SERVER_PORT'] : '80';
							if(!preg_match('/^[0-9]{1,5}$/', $serverPort)) $serverPort = '80';
							$requestUri = (isset($_SERVER['REQUEST_URI']) && !is_array($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '/function/serverGate.php';
							if($requestUri === '' || substr($requestUri, 0, 1) !== '/' || preg_match('/["\'\r\n]/', $requestUri)) $requestUri = '/function/serverGate.php';
							$requestJoin = strpos($requestUri, '?') === false ? '?' : '&';
							$serverHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && strtolower($_SERVER['HTTPS']) !== 'off';
							$serverScheme = ($serverHttps || $serverPort === '443') ? 'https' : 'http';
							$defaultPort = ($serverScheme === 'https') ? '443' : '80';
							$refreshAuthority = $serverHttpHost;
							if(strpos($refreshAuthority, ':') === false && $serverPort !== $defaultPort) $refreshAuthority .= ':'.$serverPort;
							$refreshSrc = $serverScheme.'://'.$refreshAuthority.$requestUri.$requestJoin.'setrefreshpage=1&r='.time();
							$refreshSrc = htmlspecialchars($refreshSrc, ENT_QUOTES, 'UTF-8');
							echo '
							<script language="javascript" src="'.$refreshSrc.'">
							</script>
							';
							$retstr = $retstr.$newstr;
							$_pm['mem']->set( array('k'=>$msg_key, 'v'=>$retstr));
							//$_SESSION['refreshfortest'] = time();
						}
						continue;
					}
				}
				$i = date("i");
				$YmdH = date('YmdH');
				$YmdHi = $YmdH.$i;
				$curSign = '<ANOUNCE id="'.$announceId.'" atime="'.$YmdHi.'" />';
				if(intval($i)==0)
				{
					$lastSign = '<ANOUNCE id="'.$announceId.'" atime="'.$YmdH.'59" />';
				}
				else
				{
					$lastSign = '<ANOUNCE id="'.$announceId.'" atime="'.$YmdH.($i-1).'" />';
				}

				$curMsg = kdjlSafeMemValue($_pm['mem']->get('chatMsgList'), '');
				$curMsg = is_string($curMsg) ? stripslashes($curMsg) : '';
				if($announceInterval==1)
				{
					if(strpos($curMsg,$curSign)!==false)
					{
						continue;
					}
				}
				else
				{
					if(strpos($curMsg,$lastSign)!==false || strpos($curMsg,$curSign)!==false)
					{
						continue;
					}
				}

				$pos0 = strpos($curMsg,$lastSign);
				if($pos0!==false)
				{
					$pos0 += strlen($lastSign);
					$pos2 = strpos($curMsg,'-->',$pos0);
					if($pos2 !== false)
					{
						$lastTime = preg_replace("/[^\d]/","",substr($curMsg,$pos0,$pos2-$pos0));
						if($lastTime !== '' && time()-intval($lastTime)<60) continue;
					}
				}

				if(round(time()/60)%$announceInterval == 0)
				{
					$gonggaomsg[round(time()/60)] = 'linend'.$curSign.'<!--'.time().'-->'.'<font color="#9900FF">[公告]'.$announceMessage.date("H:i:s").'</font>';
				}
			}
			else continue;
		}

		if(!empty($gonggaomsg)){
			$curMsg = kdjlSafeMemValue($_pm['mem']->get('chatMsgList'), '');
			$curMsg = is_string($curMsg) ? stripslashes($curMsg) : '';
			$curMsg = str_replace($gonggaomsg, '', $curMsg);
			$curMsg .= implode("",$gonggaomsg);

			$_pm['mem']->set(array('k'=>'chatMsgList','v'=>$curMsg));
		}
	}

	$cm = kdjlSafeMemValue($_pm['mem']->get('chatMsgList'), '');
	$cm = is_string($cm) ? stripslashes($cm) : '';

	// get every player information from memcache.

	/*
	$arr = explode("linend",$cm);
	$cm = "";
	$len = count($arr);
	for($i = 0;$i<$len;$i++)
	{
		if($i == 0)
		{
			$cm = $arr[0];
		}
		else
		{
			if($arr[$i] != $arr[$i-1])
			{
				$cm .= 'linend'.$arr[$i];
			}
		}
	}
	$_pm['mem']->set(array('k'=>'chatMsgList','v'=>$cm));
	*/

	$cm =  ($cm==false?'':str_replace(chr(13),'',$cm));
	$cm =  formatMsg($cm);
	/*$_users = $_pm['mysql'] -> getOneRecord("SELECT friendlist FROM player WHERE id = {$_SESSION['id']}");
	if(!empty($_users['friendlist'])){
		$narr = explode(',',$_users['friendlist']);
		if(count($narr) > 0){
			foreach($narr as $nv){
				if(!empty($nv)){
					$sql = "SELECT id FROM player WHERE nickname = '$nv'";
					$friendarr = $_pm['mysql'] -> getOneRecord($sql);
					$ftime = time() - 120;
					$fftime = time() - 240;
					$fvarr = kdjlSafeMemValue($_pm['mem'] -> get('friend_visit_'.$friendarr['id']), 0);//echo $nv.'!!!'.$fvarr.'<br />';
					if($fvarr > $ftime){
						$cm .= 'linend<!--friendintips#nickname'.$nv.'-->';//好友进入游戏
					}
					$flarr = kdjlSafeMemValue($_pm['mem'] -> get('last_visit_'.$friendarr['id']), 0);
					if($flarr < $ftime && $flarr > $fftime){
						$cm .= 'linend<!--friendlefttips#nickname'.$nv.'-->';//好友离开游戏
					}
				}
			}
		}
	}*/

//echo $cm;exit;
	$word	= kdjlSafeMemValue($_pm['mem']->get(MEM_SYSWORD_KEY), '');
	if(!is_string($word) && !is_numeric($word)) $word = '';
	if (strlen($word)>5)
	{
		$_pm['mem']->set(array('k'=>MEM_SYSWORD_KEY, 'v'=>0));
	}else $word=1;

	// team
	$team = kdjlSafeMemValue($_pm['mem']->get($key), array());
	if (is_array($team) && isset($team[0],$team[1]) && $team[1]==$user)
	{
		$tword=$team[0];
		$_pm['mem']->set(array('k'=>$key, 'v'=>0));
	}else $tword=0;

	$tword = trimxLound($tword);
	$word = trimxLound($word);
	$msg = trimxLound($msg);
	$somecontent = trimxLound($somecontent);

	$retstr = $tword."#team#".$word.'#word#'.$cmdresult.'#msg#'.$cm."#loudspeak#".$somecontent;
	//$retstr = '0#msg#'.$cm;

	echo '<script type="text/javascript">';
	if(!$socketchatflag){
		echo 'try{
			comet.socketRcvMsg("'. str_replace(array("\r","\n",'"',"\\\\\""),array('','','\"','\"'),$retstr) .'");
			}catch(e){}
			';
	}
	if ($isMultiServer) echo "setTimeout('window.location.reload();',$refreshtime);";
	echo '</script>';
	if (!$isMultiServer) sleep($sleepInterval); // a little break to unload the server CPU
	if ($isMultiServer) break;
	flush(); // used to send the echoed data to the client
	unset($retstr, $cmdresult);//exit;
}
$_pm['mem']->memClose();

function trimxLound($str){
	return str_replace(array('#team#','#word#','#msg#','#loudspeak#'),"-*-",$str);
}

/**
Chat function
$msg: example: altc: 小静静linendm干你m小静静: 哎人妖撒```linend週桀倫: 我前面15级进化了一个成长4linend干你: 30进话4.9linend      寂寞..: - -！全是色狼
*/
function formatMsg($msg)
{
	global $user;
	global $serverGateIsGm;
	global $_pm;
	$blacklist = kdjlSafeMemValue($_pm['mem'] -> get('db_blacklist'), array());
	//echo '====='.MEM_BLACKLIST_KEY.'-'.print_r($blacklist,1).'id='.$_SESSION['id']."\n\n";
	$blacklist = isset($blacklist[$_SESSION['id']]) ? ','.$blacklist[$_SESSION['id']].',' : ',,';
	if ($msg == '') return $msg;
	$arr = explode('linend', $msg);
	$patterdes = 'm'.$user.':';
	$pattersrc = 'm'.$user.'m';
	$retmsg = '';
	foreach ($arr as $k => $mg)
	{
		if (substr($mg,0,1)=='m' && strpos($mg, $patterdes)!==false) // recive user
		{
			// split the result.
			$try = explode($patterdes, $mg,2);
			$fromuser = substr($try[0],1);
			$mg = '<font color=#B64ABA><u>{<span>}'.$fromuser.' </span></u> => '.$try[1].'</font>';
		}
		else if(substr($mg,0,1)=='m' && strpos($mg, $pattersrc)!==false) // send user
		{
			// split the result.
			$try = explode(':', $mg,2);
			$fromuser  = str_replace($pattersrc, "", $try[0]);

			$mg = '<font color=#B64ABA>/'.$fromuser.' '.$try[1].'</font>';
		}

		 // for gm.
		if (substr($mg,0,1)=='m' && $serverGateIsGm) // recive user
		{
			$mg = substr($mg, 1);
			$mg = str_replace('m', ' => ',$mg);
			$mg = '<font color=#B64ABA>'.$mg.'</font>';
		}

		if (substr($mg,0,1) == 'm') continue;
		else
		{
			$markStart = strpos($mg,'<u>{<span>}');
			if($markStart !== false)
			{
				$pos1 = $markStart + strlen('<u>{<span>}');//echo $mg.'<br />';
				$pos2 = strpos($mg,' </span></u>',$pos1);
				if($pos2 !== false)
				{
					$username=",".substr($mg,$pos1,$pos2-$pos1).",";//echo $pos1.'<br />'.$pos2.'<br />'.$username;exit;
					if(!empty($blacklist) && strpos($blacklist,$username) !== false){
						//echo " 1 \n\n\n\n".$username."\n";
						continue;
					}
				}
			}
			//echo " 2 \n\n\n\n".$username."\n";
			$retmsg .= $mg.'linend';
		}
	}
	for($i=1;$i<=36;$i++)
	{	$src[$i] = "(".$i.")";
		$src1[$i] = "[".$i."]";
		if($i<=26)
		{
			$des1[$i] = '<img src=../images/ui/motion1/'.$i.'.gif>';
		}
		$des[$i] = '<img src=../images/ui/motion/'.$i.'.gif>';
	}
	$okret=str_replace($src,$des,substr($retmsg,0,-6));
	$okret=str_replace($src1,$des1,$okret);
	$okret=str_replace("{<span>}","<span style=cursor:pointer onclick=\$('cmsg').value='/'+this.innerHTML;>",$okret);
	//$okret=str_replace(array("{","}"),array("<u><span onclick=showchatTip(this.innerHTML,this) onmouseout=chatuntip() style=cursor:pointer;color:green;>","</span></u>"),$okret);

	return $okret;
}

function socketData($host,$port, $url, $flag=false){
	$port = intval($port) > 0 ? intval($port) : 80;
	$fp = @fsockopen($host, $port, $errno, $errstr, 3);
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
	return $rtn;
}

function wr($somecontent,$flag=0){
	//echo $somecontent."\r\n";
	//return ;
	//if($flag>0) return;
	//echo $somecontent;
	$filename = dirname(__FILE__).'/log.txt';
	//$somecontent = date("Y-m-d H:i:s")."\r\n";

    $handle = fopen($filename, 'a+');
	if(!$handle) return;

	flock($handle, LOCK_EX);
    if (fwrite($handle, $somecontent."\r\n") === FALSE) {
        //exit;
    }

	flock($handle, LOCK_UN);
    fclose($handle);
}

if ($isMultiServer) echo "
<script language='javascript'>
setTimeout('window.location.reload();',$refreshtime);
</script>
";
?>
</body>
</html>
