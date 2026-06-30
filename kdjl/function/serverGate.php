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

if(isset($welcome['openonlinetimestat']['contents'])&&$welcome['openonlinetimestat']['contents']==1)
{
	$computeOnline = true;
}

if($computeOnline){
	$_pm['mysql']->addColumnIfMissing('player_ext', 'onlinetime', 'int(8)');
	$_pm['mysql']->addColumnIfMissing('player_ext', 'logintime', 'int(10)');
	
	$_pm['mysql']->query('update player_ext set logintime='.time().' where uid='.$_SESSION['id']);
}
function get_real_ip(){
	$ip=false;

	if(!empty($_SERVER["HTTP_CLIENT_IP"])){
		$ip = $_SERVER["HTTP_CLIENT_IP"];
	}
	
	if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
		if ($ip) { 
			array_unshift($ips, $ip); $ip = FALSE; 
		}
		for ($i = 0; $i < count($ips); $i++) {
			if (!preg_match("/^(10|172\.16|192\.168)\./i", $ips[$i])) {
				$ip = $ips[$i];
				break;
			}
		}
	}
	return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
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
header('Content-Type:text/html;charset=gbk');
session_write_close();
flush();
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=gbk" />
</head>
<body>
<?php
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
$crc = crc32($_REQUEST[PHPSESSID]);
$user = unserialize($_pm['mem']->get($crc));
$key = crc32($user);
$key = $key<1?1-$key-1:$key;

$sleepInterval = 2;

$time = time();
$uid = $_SESSION['id'];

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


$rand = $_SESSION['id']%60;
$lastdomd = unserialize($_pm['mem'] -> get('last_do_md_'.$uid));
while(1) {
	$h = date('H');
	$m = date('i');
	$dh = date('mdH');
	if($computeOnline&&$lastdomd!=$dh&&$rand == $m)
	{
		$lastdotime = unserialize($_pm['mem'] -> get('last_do_'.$uid));
		//echo '<br>time()%10='.(time()%10)."<br/>";
		$lastvisttime = unserialize($_pm['mem'] -> get('last_visit_'.$uid));
		if($lastdotime > 0 && $lastvisttime > 0){
			$newvalue=$onlinetime+$lastvisttime-$lastdotime;
			$sql = 'update player_ext set onlinetime='.$newvalue.' where uid='.$uid;
			
			$_pm['mysql']->close();
			$_pm['mysql']	= new mysql();
			
			$_pm['mysql']->query($sql);
			$_pm['mem'] -> set(array('k' => 'last_do_'.$uid,'v' => $time));
			$_pm['mem'] -> set(array('k' => 'last_visit_'.$uid,'v' => $time));
			$_pm['mem'] -> set(array('k' => 'last_do_md_'.$uid,'v' => $dh));
			//echo mysql_error();
			echo "<br>|new value=".$newvalue."|<br/>".$sql;
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
		$loudspeak	= unserialize($_pm['mem']->get($msg_key));
		if(is_array($loudspeak)){
			foreach($loudspeak as $k=>$v){
				$somecontent = str_replace(array("\r","\n",'"'),array('','','\"'),$v).'';
			}
		}
	}
	
	if(date('s')%59==$sleepInterval){
		$dt = date("YmdHi");
		$gonggao = unserialize($_pm['mem'] -> get(MEM_GONGGAO_KEY));
		$gonggaomsg = array();
		//$curMsg=stripslashes(unserialize($_pm['mem']->get('chatMsgList')));
		foreach($gonggao as $gg)
		{
			if($gg['starttime'] <= $dt && $gg['endtime'] >= $dt)
			{
				if(round(time()/60)%$gg['times'] == 0)
				{
					if($gg['msg']=="resetchat")
					{
						echo '<script type="text/javascript">';				
						echo "setTimeout('window.location.reload();',500);";
						echo '</script>';
						die();
					}
					if($gg['msg']=="refreshpage")
					{
						$msg_key = 'chatMsgList';
						$nowMsgList = unserialize($_pm['mem']->get($msg_key));
						$arr = split('linend', $nowMsgList);
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
							echo '
							<script language="javascript" src="http://'.$_SERVER['HTTP_HOST'].($_SERVER['SERVER_PORT']=='80'?"":':'.$_SERVER['SERVER_PORT']). $_SERVER['REQUEST_URI']."?setrefreshpage=1&r=".time().'">
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
				$curSign = '<ANOUNCE id="'.$gg['Id'].'" atime="'.$YmdHi.'" />';
				if(intval($i)==0)
				{
					$lastSign = '<ANOUNCE id="'.$gg['Id'].'" atime="'.$YmdH.'59" />';
				}
				else
				{
					$lastSign = '<ANOUNCE id="'.$gg['Id'].'" atime="'.$YmdH.($i-1).'" />';
				}

				$curMsg=stripslashes(unserialize($_pm['mem']->get('chatMsgList')));
				if($gg['times']==1)
				{
					if(strpos($curMsg,$curSign)!==false)
					{
						continue;
					}
				}
				else
				{
					if(strpos($curMsg,$lastSign)!==false || strpos($curMsg,$curSign))
					{
						continue;
					}
				}
				
				$pos0 = strpos($curMsg,$lastSign);
				if($pos0!==false)
				{
					$pos0 += strlen($lastSign);
					$pos2 = strpos($curMsg,'-->',$pos0);
					$lastTime = preg_replace("/[^\d]/","",substr($curMsg,$pos0,$pos2-$pos0));
					if(time()-$lastTime<60) continue;
				}				
				
				if(round(time()/60)%$gg['times'] == 0)
				{
					$gonggaomsg[round(time()/60)] = 'linend'.$curSign.'<!--'.time().'-->'.'<font color="#9900FF">[公告]'.$gg['msg'].''.date("H:i:s").'</font>';
				}
			}
			else continue;
		}	
		
		if(!empty($gonggaomsg)){
			$curMsg = stripslashes(unserialize($_pm['mem']->get('chatMsgList')));
			$curMsg = str_replace($gonggaomsg, '', $curMsg);
			$curMsg .= implode("",$gonggaomsg);		

			$_pm['mem']->set(array('k'=>'chatMsgList','v'=>$curMsg));
		}
	}
	
	$cm =  stripslashes(unserialize($_pm['mem']->get('chatMsgList'))); 	
	
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
					$fvarr = unserialize($_pm['mem'] -> get('friend_visit_'.$friendarr['id']));//echo $nv.'!!!'.$fvarr.'<br />';
					if($fvarr > $ftime){
						$cm .= 'linend<!--friendintips#nickname'.$nv.'-->';//好友进入游戏
					}
					$flarr = unserialize($_pm['mem'] -> get('last_visit_'.$friendarr['id']));
					if($flarr < $ftime && $flarr > $fftime){
						$cm .= 'linend<!--friendlefttips#nickname'.$nv.'-->';//好友离开游戏
					}
				}
			}
		}
	}*/
	
//echo $cm;exit;
	$word	= unserialize($_pm['mem']->get(MEM_SYSWORD_KEY));
	if (strlen($word)>5) 
	{
		$_pm['mem']->set(array('k'=>MEM_SYSWORD_KEY, 'v'=>0));
	}else $word=1;
	
	// team
	$team = unserialize($_pm['mem']->get($key));
	if (is_array($team) && $team[1]==$user)
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
	global $_pm;
	$blacklist = unserialize($_pm['mem'] -> get('db_blacklist'));
	//echo '====='.MEM_BLACKLIST_KEY.'-'.print_r($blacklist,1).'id='.$_SESSION['id']."\n\n";
	$blacklist = ','.$blacklist[$_SESSION['id']].',';
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
		if (substr($mg,0,1)=='m' && ($user=='GM' || $user == 'tanwei')) // recive user
		{
			$mg = substr($mg, 1);
			$mg = str_replace('m', ' => ',$mg);
			$mg = '<font color=#B64ABA>'.$mg.'</font>';
		}

		if (substr($mg,0,1) == 'm') continue;
		else 
		{
			$pos1=strpos($mg,'<u>{<span>}')+strlen('<u>{<span>}');//echo $mg.'<br />';
			$pos2=strpos($mg,' </span></u>',$pos1);
			$username=",".substr($mg,$pos1,$pos2-$pos1).",";//echo $pos1.'<br />'.$pos2.'<br />'.$username;exit;
			if(!empty($blacklist) && strpos($blacklist,$username) !== false){
				//echo " 1 \n\n\n\n".$username."\n";
			}
			else{
			//echo " 2 \n\n\n\n".$username."\n";
				$retmsg .= $mg.'linend';
			}
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

    // 将$somecontent写入到我们打开的文件中。
    if (fwrite($handle, $somecontent."\r\n") === FALSE) {
        //exit;
    }

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
