<?php
/**
@Usage: Server message send center.
@Version: 1.0.1
@Copyright: www.webgame.com.cn 
*/
set_time_limit(0);
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
flush();
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=GBK" />
</head>
<body>

<script type="text/javascript">
// KHTML browser don't share javascripts between iframes
var is_khtml = navigator.appName.match("Konqueror") || navigator.appVersion.match("KHTML");
if (is_khtml)
{
	var prototypejs = document.createElement('script');
	prototypejs.setAttribute('type','text/javascript');
	prototypejs.setAttribute('src','../javascript/prototype.js');
	var head = document.getElementsByTagName('head');
	head[0].appendChild(prototypejs);
}
// load the comet object
var comet = window.parent.comet;
</script>

<?php
require_once('../config/config.game.php');
//$m = $_pm['mem'];	// Init memcache.

$crc = crc32($_REQUEST[PHPSESSID]);
$user = unserialize($_pm['mem']->get($crc));
$key = crc32($user);
$key = $key<1?1-$key-1:$key;

$time = time();
while(1) {
	if($time+60>time())
	{   $h = date("i",$time);
	if($h==50)//$h==10||$h==30 ||$h==50 || $h==40
	{
		$trs = $_pm['mysql']->getOneRecord("select id,msg,url
												  from gamead 
												 order by id desc 
												 limit 0,1");
		if (is_array($trs))
		{
			$newid = rand(1,$trs['id']);
			$lrs=$_pm['mysql']->getOneRecord("select id,msg,url
												    from gamead 
												   where id={$newid}");
			$cmdresult = $lrs['msg'].'#'.$lrs['url'];
			//$time=$time+70;
		}else $cmdresult=0;
	}
	else if ($h==48) $cmdresult=2;
	else $cmdresult=0;
	}
	else {$cmdresult=1;$time=time();}


	/*
	* 加入在线玩家统计。每小时统一次
	*/
	if(date("i", $time) == 59)
	{
		$ex = $_pm['mysql']->getOneRecord("select ctime from game_count order by id desc limit 0,1");
		if (!is_array($ex) || (is_array($ex) && date("i", $ex['ctime'])!=59) )
		{
			$rs = $_pm['mysql']->getRecords("select count(distinct(id))
									 from player 
									where lastvtime>unix_timestamp()-300
									group by id
								 ");

			$_pm['mysql']->query("insert into game_count(ctime,online)
						values('{$time}','".count($rs)."')
					  ");
			unset($rs);
		}
		unset($ex);
	}
	// 在线统计。


	$cm =  stripslashes(unserialize($_pm['mem']->get('chatMsgList'))); // get every player information from memcache.
	$cm =  ($cm==false?'':str_replace(chr(13),'',$cm));
	$cm =  formatMsg($cm);

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

	$retstr = $tword."#team#".$word.'#word#'.$cmdresult.'#msg#'.$cm;
	//$retstr = '0#msg#'.$cm;

	echo '<script type="text/javascript">';
	echo 'comet.socketRcvMsg("'. $retstr .'");';
	echo '</script>';

	flush(); // used to send the echoed data to the client
	sleep(1); // a little break to unload the server CPU
	unset($retstr, $cmdresult);
}
$_pm['mem']->memClose();

/**
Chat function
$msg: example: altc: 小静静linendm干你m小静静: 哎人妖撒```linend週桀倫: 我前面15级进化了一个成长4linend干你: 30进话4.9linend      寂寞..: - -！全是色狼
*/
function formatMsg($msg)
{
	global $user;
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
		if (substr($mg,0,1)=='m' && ($user=='GM' || $user == 'tanwei' || $user == '小力水手' || $user == '落水的旱鸭')) // recive user
		{
			$mg = substr($mg, 1);
			$mg = str_replace('m', ' => ',$mg);
			$mg = '<font color=#B64ABA>'.$mg.'</font>';
		}

		if (substr($mg,0,1) == 'm') continue;
		else $retmsg .= $mg.'linend';
	}
	for($i=1;$i<=36;$i++)
	{	$src[$i] = "(".$i.")";
	$src1[$i] = "[".$i."]";
	if($i<=26)
	{
		$des1[$i] = '<img src=images/ui/motion1/'.$i.'.gif>';
	}
	$des[$i] = '<img src=images/ui/motion/'.$i.'.gif>';
	}
	$okret=str_replace($src,$des,substr($retmsg,0,-6));
	$okret=str_replace($src1,$des1,$okret);
	$okret=str_replace("{<span>}","<span style=cursor:pointer onclick=\$('cmsg').value='/'+this.innerHTML;>",$okret);
	//$okret=str_replace(array("{","}"),array("<span style=color:green>【<a href='#' onclick=showchatTip(this.innerHTML,this) onmouseout=chatuntip() style=cursor:pointer;color:green;>","</a>】</span>"),$okret);
	unset($src,$des);
	return $okret;
}
?>
</body>
</html>