<?php
//set_time_limit(30);
/**
@Usage: Get player information for map option.
@Write date: 2008.03.22
@Write by sugf
@Copyright www.webgame.com.cn
@##############################################
@Notice:
 This script only used test user data connection.
 so,we defined two user for test.
*/
$requestType = (isset($_REQUEST['type']) && !is_array($_REQUEST['type'])) ? $_REQUEST['type'] : '';
$requestMsg = (isset($_REQUEST['msg']) && !is_array($_REQUEST['msg'])) ? $_REQUEST['msg'] : '';
if(strlen($requestMsg) > 600) $requestMsg = substr($requestMsg, 0, 600);
if($requestType == '' && $requestMsg == ''){
	exit();
}


require_once('../config/config.game.php');
if ( !isset($_SESSION['id']) || intval($_SESSION['id']) < 1 ) exit("你没登陆.");
$uid = intval($_SESSION['id']);
require_once(dirname(dirname(__FILE__)).'/kernel/memory.v1.1.php');

$rs = $_pm['mysql']->getOneRecord("SELECT id,name,nickname,password,secid,money FROM player WHERE id={$uid}");
$userIsVip = false; /* whether the user send msg is a VIP user, if he has the 口袋精灵VIP卡, he is. added by Zheng.Ping */
$chatLockTime = is_array($rs) && isset($rs['password']) ? intval($rs['password']) : 0;
if($chatLockTime > 0 && $chatLockTime <= time())
{
	if(!chatGateSetPlayerLock($uid,0)) exit('保存禁言状态失败！');
	$_pm['mem']->del($uid);
	$chatLockTime = 0;
	if(is_array($rs)) $rs['password'] = 0;
}
if(!is_array($rs) || $chatLockTime > time() || (isset($rs['secid']) && $rs['secid']>0) || $requestMsg=='{'||$requestMsg=='}') exit("你没登陆!");
$rsDefaults = array('nickname' => '', 'name' => '', 'money' => 0);
foreach($rsDefaults as $rsDefaultKey => $rsDefaultValue)
{
	if(!isset($rs[$rsDefaultKey])) $rs[$rsDefaultKey] = $rsDefaultValue;
}

/*new player not say.*/
//if ($rs['regtime']+3600>time() || $rs['money']<1000) exit();

// 封号处理：增加封号命令：@@FH玩家昵称
/*$fff = false;
if(strpos($_SERVER['HTTP_USER_AGENT'],'Firefox/3')!==false||strpos($_SERVER['HTTP_USER_AGENT'],'Firefox/2')!==false){
	$fff = true;
}
$msg = htmlspecialchars(($_REQUEST['msg']),ENT_QUOTES,"GBK");
if(strlen($_REQUEST['msg'])>1&&strlen($msg)<1||$fff){
	$msg = htmlspecialchars(iconv('utf-8','GBK',$_REQUEST['msg']),ENT_QUOTES,"GBK");
}*/


//
function getip()
{
 $candidates = array();
 if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) $candidates[] = getenv('HTTP_CLIENT_IP');
 if(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) $candidates = array_merge($candidates, explode(',', getenv('HTTP_X_FORWARDED_FOR')));
 if(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) $candidates[] = getenv('REMOTE_ADDR');
 if(isset($_SERVER['REMOTE_ADDR']) && !is_array($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) $candidates[] = $_SERVER['REMOTE_ADDR'];
 foreach($candidates as $ip)
 {
  $ip = trim($ip);
  if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return $ip;
 }
 return 'unknown';
}

function chatGateSetPlayerLock($playerId,$lockTime)
{
	global $_pm;
	$playerId = intval($playerId);
	$lockTime = max(0,intval($lockTime));
	if($playerId < 1 || !$_pm['mysql']->query('START TRANSACTION')) return false;
	if(!$_pm['mysql']->query("UPDATE player SET password={$lockTime} WHERE id={$playerId}") ||
		!$_pm['mysql']->query("UPDATE chat_login_auth SET lock_time={$lockTime} WHERE uid={$playerId}") ||
		!$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		return false;
	}
	$old = kdjlSafeMemValue($_pm['mem']->get($playerId), array());
	if(is_array($old))
	{
		$old['password']=$lockTime;
		$_pm['mem']->set(array('k'=>$playerId,'v'=>$old));
	}
	$_pm['mem']->set(array('k'=>'chat_lock_'.$playerId,'v'=>$lockTime));
	return true;
}
//



$msg = htmlspecialchars($requestMsg,ENT_QUOTES,'UTF-8');
//$msg = htmlspecialchars(iconv('utf-8','GBK',$_GET['msg']));

//echo $msg;exit;
$fletter = substr($msg,0,1);
$trimmedMsg = trim($msg);
$len = strlen($trimmedMsg) - 1;
$lletter = $len >= 0 ? substr($trimmedMsg,$len,1) : '';

$arr = array(
);
//message
for($i=0;$i<count($arr);$i++){
	$msg = str_replace($arr[$i],"*",$msg);
}
if($fletter == "{" && $lletter != "}")
{
	$msg = $msg."}";
}
else if($fletter != "{" && $lletter == "}")
{
	$msg = "{".$msg;
}

$welcome = memContent2Arr("db_welcome",'code');
$gm_in_mem = is_array($welcome) && isset($welcome['admin']['contents']) ? $welcome['admin']['contents'] : '';
if(!isset($_gm['name']) || !is_array($_gm['name'])) $_gm['name'] = array();
if(!empty($gm_in_mem))
{
	$_gm['name'] = array_merge($_gm['name'], preg_split("/(?:,|;|\\xEF\\xBC\\x8C|\\xEF\\xBC\\x9B)+/", $gm_in_mem, -1, PREG_SPLIT_NO_EMPTY));
}
$_gm['name'] = array_filter(array_map('trim', $_gm['name']), 'strlen');
$isChatGm = (is_array($rs) && isset($rs['name']) && in_array($rs['name'], $_gm['name'], true));

$cmdstr = substr($msg,0,2);


if (($cmdstr == 'JY' || $cmdstr == 'FH'|| $cmdstr == 'JJ' || $cmdstr == 'YZ' || $cmdstr == 'ZY' || $cmdstr == 'WF') && $isChatGm)
{
	$nickname = substr($requestMsg, 2);
	$safeNickname = $_pm['mysql']->escape($nickname);
	$players = $_pm['mysql']->getOneRecord("SELECT id,password FROM player where nickname='{$safeNickname}' limit 0,1");
	if (is_array($players))
	{
		$playerId = intval($players['id']);
		if ($cmdstr == 'FH')
		{
			$_pm['mysql']->query("UPDATE player set secid=1 WHERE id={$playerId}");
			$_pm['mem']->set(array('k'=>$playerId . 'chat', 'v'=>0)); // 踢下线
			$_pm['mem']->del($playerId);
			exit("FH");
		}
		else if($cmdstr == 'JY') // 12小时禁言
		{
			$time = time() + 12 * 3600;
			if(!chatGateSetPlayerLock($playerId,$time)) exit('保存禁言状态失败！');
			$msg = '@'. $nickname . ' 因为违反江湖道义，被众英雄送入思过涯思过12小时！';
		}
		else if($cmdstr == "JJ") // 12小时解禁
		{
			$nowtime = time();
			$playerPassword = isset($players['password']) ? intval($players['password']) : 0;
			$ctime = ($playerPassword - $nowtime) / 3600;
			if($ctime <  12)
			{
				if(!chatGateSetPlayerLock($playerId,0)) exit('保存禁言状态失败！');
				$msg = '@'. $nickname . ' 在思过涯面壁思过结束，被允许重出江湖！';
			}
			//exit();
		}
		else if($cmdstr == 'YZ') // 永久禁言
		{
			$time = time() + 10 * 365 * 12 * 3600;
			if(!chatGateSetPlayerLock($playerId,$time)) exit('保存禁言状态失败！');
			$msg = '@ 天降巨雷，把玩家&nbsp;'.$nickname.'&nbsp;嘴巴劈成了两半，&nbsp;'.$nickname.'&nbsp;永久失去了说话的权利！';
		}
		else if($cmdstr == 'WF') // 永久禁言不发公告
		{
			$time = time() + 10 * 365 * 12 * 3600;
			if(!chatGateSetPlayerLock($playerId,$time)) exit('保存禁言状态失败！');
			//$msg = '@ 天降巨雷，把玩家&nbsp;'.$nickname.'&nbsp;嘴巴劈成了两半，&nbsp;'.$nickname.'&nbsp;永久失去了说话的权利！';
			$msg = "";
			$rs['nickname'] = "";
		}
		else if($cmdstr == "ZY") //解禁
		{
			if(!chatGateSetPlayerLock($playerId,0)) exit('保存禁言状态失败！');
			$msg = '@ 天降神光，照射到&nbsp;'. $nickname . '&nbsp;的身上，他嘴上的伤口奇迹般的复原了，从此，他过上了幸福的生活.';
			//exit();
		}
	}
	//exit();
}

// 时间间隔:
if (isset($_SESSION['msgtime']) && $_SESSION['msgtime'] && $_SESSION['msgtime']>time()-5) exit('TOOFAST');
if(!isset($_SESSION['chatHis']))
{
	$_SESSION['chatHis']=array();
}
if(!is_array($_SESSION['chatHis'])) $_SESSION['chatHis'] = array();
if(!isset($_SESSION['chatHisCount'])) $_SESSION['chatHisCount']=0;
if(compareMsg($msg))
{
	die('REPEATCONTENT');
}
else
{
	$_SESSION['chatHis'][$_SESSION['chatHisCount']%3]=$msg;
	$_SESSION['chatHisCount']++;
}

if (strlen($requestMsg)>100 && substr($msg, 0,2) != '//' && !$isChatGm) exit("DATATOOLONG");
if (strlen($requestMsg)>100 && $isChatGm) exit("DATATOOLONG:".strlen($requestMsg));
$truename= $rs['nickname'];

$msg = str_ireplace('linend','',$msg);
$sc = 0;

//Format msg.
//展示宠物
if($requestType == 'showbb')
{
	$showPetIdText = trim($requestMsg);
	if($showPetIdText === '' || preg_match('/[^0-9]/', $showPetIdText))
	{
		die("100");
	}
	$showPetId = intval($showPetIdText);
	$srctime = 10;
	#################增加一个间隔时间################
	$paiKey = 'paitimes'.$uid;
	$time = isset($_SESSION[$paiKey]) ? $_SESSION[$paiKey] : 0;
	if(empty($time))
	{
		$_SESSION[$paiKey] = time();
	}
	else
	{
		$nowtime = time();
		$ctime = $nowtime - $time;
		if($ctime < $srctime)
		{
			die("1000");//没有达到间隔时间
		}
		else
		{
			$_SESSION[$paiKey] = time();
		}
	}
	$bid = (isset($_REQUEST['bid']) && !is_array($_REQUEST['bid'])) ? intval($_REQUEST['bid']) : 0;
	$user = $_pm['user']->getUserById($uid);
	if(!is_array($user) || intval($user['mbid']) != $showPetId)
	{
		die("100");
	}
	if(!$_pm['mysql'] -> query("INSERT INTO player_ext(uid,bbshow) VALUES({$uid},5) ON DUPLICATE KEY UPDATE uid=uid"))
	{
		die("101");
	}
	$sql = "SELECT bbshow FROM player_ext WHERE uid = {$uid}";
	$arr = $_pm['mysql'] -> getOneRecord($sql);
	if(!is_array($arr) || $arr['bbshow'] < 1)
	{
		die("101");
	}
	$bb = $_pm['mysql'] -> getOneRecord("SELECT name FROM userbb WHERE id = {$showPetId} AND uid = {$uid}");
	if(!is_array($bb)) die("100");
	$str = $showPetId;
	//$_olddata = @unserialize($_pm['mem']->get('ttmt_data_notice'));
	//$swfData = iconv('GBK','utf-8',"\$".$truename."`说：")."<a onclick=\"showBb('".$msg."')\"><b><font color=\"#A3ABAD\">".iconv('GBK','utf-8','【'.$bb['name'].'】')."</font></b></a>";

	/*
	$_olddata['bs'] = isset($_olddata['bs'])?$_olddata['bs']."<br/>[系统公告]：".$swfData:$swfData;
	$_pm['mem']->set(array('k'=>'ttmt_data_notice','v'=>$_olddata));
	*/
	$showPetName = htmlspecialchars(isset($bb['name']) ? $bb['name'] : '', ENT_QUOTES, 'UTF-8');
	$msg = "<span style='color:#A3ABAD;cursor:pointer;color:#A3ABAD;'><a onclick=showBb('".$showPetId."')><b>【".$showPetName."】</b></a></span>";
	if(!$_pm['mysql'] -> query("UPDATE player_ext SET bbshow = bbshow - 1 WHERE uid = {$uid} AND bbshow > 0") ||
		mysql_affected_rows($_pm['mysql']->getConn()) != 1)
	{
		die("101");
	}
	require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
	$s=new socketmsg();
	//展示宠物
	$s->sendMsg('CT|$'.$truename.'`说: '.$msg);
	die();
}
die();
if (substr($msg, 0,2) == '!!') $msg = '<font color=blue>'.substr($msg,2).'</font>';
else if (substr($msg, 0,1) == '!') $msg = '<font color=#FF00FF>'.substr($msg,1).'</font>';
/*
else if (substr($msg, 0,1) == '$' && ($rs['money']>1000))
{
	$rs['money']-=1000;
	//$msg ='<marquee scrollamount=1 behavior=alternate scrolldelay=1 width=300 direction=up height=25><font color=#FF00FF>'.substr($msg,1).'</font></marquee>';
	$msg ='<font color=#FF00FF>'.substr($msg,1).'</font>';
} */ //commented by Zheng.Ping
else if (substr($msg, 0,1) == '$' /* && ($rs['money']>1000) */) /* added by Zheng.Ping */
{
	$arr = array("1427","1474","1475","1476","1477","1478","1479","1480","1481","1482","1483","1484","1485");
	$arrayid=date('n');
	if($arrayid=='1')
	{
		$arraycode=array("1427",$arr[$arrayid],$arr[12]);
	}else
	{
		$arrayidjian=$arrayid-1;
		$arraycode=array("1427",$arr[$arrayidjian],$arr[$arrayid]);
	}
	$u_bags=getUserBagByIds($uid, $arraycode, $_pm['mysql']); /* 口袋精灵VIP卡:1427 */
	if(!is_array($u_bags)) $u_bags = array();

   // $u_bags=getUserBagById($_SESSION['id'], 1427, $_pm['mysql']); /* 口袋精灵VIP卡:1427 */
	foreach($u_bags as $v)
	{
		if($v && isset($v['sums']) && $v['sums'] > 0)
		{
			$userIsVip = true;
			$msg =' <font color="#FF0000">'.substr($msg, 1).'</font>';
		}
	}
	/*if ($u_bags && isset($u_bags['sums']) && $u_bags['sums'] > 0) {
		$userIsVip = true;
		$msg = '<font color="#FF0000">' . substr($msg, 1) .'</font></marquee>';
	}*/

	unset($u_bags);
} /* added by Zheng.Ping */
else if (substr($msg, 0,1) == '#' && ($rs['money']>10))
{
	$rs['money']-=10;
	$msg='<font color=green>'.substr($msg,1).'</font>';
}
//filter:shadow(color=blue);height:1
else if ($isChatGm && substr($msg, 0,1) == '@')
{
	// sub command
	if(strtolower(trim($msg)) == "@@clear")
	{
		$_pm['mem']->del('chatMsgList');
		exit("@@Clear");
	}

	$msg = '<font color=red>[公告] '.substr($msg,1).'</font>';
	//$rs['nickname']='GM';
	$truename='GM';
}
else if(substr($msg, 0,2) == '//' && strlen($msg)>3)
{
	die("nabaweihu");
}


else if(substr($msg, 0,1) == '/' && strpos($msg,' ')!==false)
{
	$posChk = explode(' ', $msg,2);
	if (is_array($posChk) && count($posChk)==2)
	{
		$fromuser = ",".$truename.",";
		$getuser = str_replace('/','',$posChk[0]);
		define("MEM_BLACKLIST_KEY","db_blacklist");
		$blacklist = kdjlSafeMemValue($_pm['mem'] -> get(MEM_BLACKLIST_KEY), array());
		$truename = 'm'.$truename.'m'.str_replace('/','',$posChk[0]); // m+from+'m'+to:
		$getuserSql = $_pm['mysql']->escape($getuser);
		$arr = $_pm['mysql'] -> getOneRecord("SELECT id FROM player WHERE nickname = '{$getuserSql}'");
		$msg = $posChk[1];
		if(is_array($arr) && !empty($arr['id']) && !empty($blacklist[$arr['id']]))
		{
			$toBlacklist = ','.$blacklist[$arr['id']].',';
			if(strpos($toBlacklist, $fromuser) !== false)
			{
				die("");
			}
		}
	}
	$sc = 1;
}else if(substr($msg, 0,1) == '|' && strpos($msg,' ')!==false){//送礼
	$msg_key = 'chatMsgList';
	$nowMsgList = kdjlSafeMemValue($_pm['mem']->get($msg_key), '');
	if(!is_string($nowMsgList)) $nowMsgList = '';
	$arr = explode('linend', $nowMsgList);
	if(count($arr)>20 ) // cear old
	{
		$arrt = array_shift($arr);
	}
	$nmsg = substr($msg,1);
	$amsg = explode(' ',$nmsg);
	if(!isset($amsg[0]) || !isset($amsg[1]) || $amsg[0] == '' || $amsg[1] == ''){
		die('nomsg');
	}
	$cmd = kdjlSafeMemValue($_pm['mem'] -> get('db_welcome1'), array());
	if(!is_array($cmd) || !isset($cmd['swfemotion']) || $cmd['swfemotion'] == ''){
		die('nomsg');
	}

	$cmdarr = explode("\r\n",$cmd['swfemotion']);
	$tmsg = array('');
	foreach($cmdarr as $cv){
		if(strpos($cv,$amsg[1]) !== false){
			$tmsg = explode('##',$cv);
		}
	}

	if($tmsg[0] == ''){
		die('nomsg');
	}
	$giftItemUsed = $_pm['mysql'] -> query("UPDATE userbag SET sums = sums - 1 WHERE uid = {$uid} AND pid = 2309 AND sums >= 1 AND zbing = 0 AND (cantrade IS NULL OR cantrade <> 3) ORDER BY id LIMIT 1");
	$result = $giftItemUsed ? mysql_affected_rows($_pm['mysql'] -> getConn()) : 0;
	if($result != 1){
		die("NOPROPS！");
	}
	$giftFromName = isset($_SESSION['nickname']) ? $_SESSION['nickname'] : $truename;
	$gword = $giftFromName.' 对 '.$amsg[0].'说:'.$tmsg[0];
	$newstr = '<!--'.time().'-'.$_SESSION['id'].'#givegift#'.$amsg[1].'--><font color=red>[系统公告] '.$gword.'!</font>';

	//$msg = '<!-->'.time().'-'.$_SESSION['id'].'#givegift#'.$amsg[1].'<-->';
	$arr = explode('linend', $nowMsgList);
	if(count($arr)>20 ) // cear old
	{
		$arrt = array_shift($arr);
	}
	$retstr = '';
	foreach($arr as $k=>$v)
	{
		$retstr .= $v.'linend';
	}

	$retstr = $retstr.$newstr;
	$_pm['mem']->set( array('k'=>$msg_key, 'v'=>$retstr) );
	die($msg);
}

function postAnounce($server,$isSmallSpeaker,$data){
	global $_SESSION,$server_ip_list,$isChatGm;
	if(strtolower($server)=='kd5.youjia.cn'||strtolower($server)=='kd7.youjia.cn'){
		$memAnother = new memoryC(array('host'=>$server_ip_list[$server],'port'=>11212));
	}else{
		$memAnother = new memoryC(array('host'=>$server_ip_list[$server],'port'=>11211));
	}
	if(!$memAnother->getHandle()){
		if($isChatGm){
				echo 'Mem '.$server.'=>'.$server_ip_list[$server].' connect fail!'."\r\n";
		}
		return false;
	}
	if($isChatGm){
		echo $server."\r\n";
	}
	$time = time();
	if(!$isSmallSpeaker){
		$msg_key = 'chatMsgListLoundSpeaker';
		$memAnother->del($msg_key);
		if ($memAnother->add( array('k'=>$msg_key, 'v'=>array(time()=>$data) ) ) != true)
		{
			$memAnother->set( array('k'=>$msg_key, 'v'=>array( time()=>$data ) ) );
		}
	}

	$nmsg = preg_split("/\#\`\#/",$data,-1,PREG_SPLIT_NO_EMPTY);
	$msg_key = 'chatMsgList';
	if ($memAnother->add( array('k'=>$msg_key, 'v'=>implode('linend',$nmsg)) ) != true)
	{
		$nowMsgList = kdjlSafeMemValue($memAnother->get($msg_key), '');
		if(!is_string($nowMsgList)) $nowMsgList = '';
		$arr = explode('linend', $nowMsgList);
		if( count($arr)>20 ) // clear old
		{
			$arrt = array_shift($arr);
		}
		$arr = array_merge($arr,$nmsg);
		$retstr =implode('linend',$arr).'linend';

		if(!$memAnother->set( array('k'=>$msg_key, 'v'=>$retstr) )){
			if($isChatGm){
				echo $server." set failed!!\r\n";
			}
		}
	}
	$memAnother->memClose();
	$memAnother = NULL;
	return true;
}

#####################################################
// Chat message set 60s valid
// Every player key is: hash+cm:
//
#####################################################
$msg_key = 'chatMsgList';
//$msg = htmlspecialchars($msg);
//$msg = preg_replace("/[<>]/","|",$msg);

require_once('chatSendInc.php');
echo sendToSoap($msg);
if ($_pm['mem']->add( array('k'=>$msg_key, 'v'=>$truename.': '.$msg) ) != true)
{
	$nowMsgList = kdjlSafeMemValue($_pm['mem']->get($msg_key), '');
	if(!is_string($nowMsgList)) $nowMsgList = '';
	$arr = explode('linend', $nowMsgList);
	if( count($arr)>20 ) // cear old
	{
		//$arrt = array_shift($arr);
		$arr = array_slice($arr, -20, 20);
	}
	if($isChatGm && $sc==0) $newstr = $msg;
	else
	{
		if($sc !=1) {
			$truename = '<u>{<span>}'.$truename.' </span></u>';
			//结婚证明
			$sql="select merge from player_ext where uid = {$uid}";
			$arr_merge=$_pm['mysql']->getOneRecord($sql);
			if(is_array($arr_merge) && isset($arr_merge['merge']) && $arr_merge['merge']>0){
				$truename = $truename . '<img src="/images/merge.gif" alt="" />';
			}

		}
        if ($userIsVip) $truename = $truename . '<font color=\"#FF0000\">(VIP)</font>'; // added by Zheng.Ping

		//if($sc !=1) $truename = '<u>{<span>}'.$truename.' </span></u>';

		$newstr = $truename.': '.$msg;
	}

	//foreach($arr as $k=>$v)
	//{
	//	$retstr .= $v.'linend';
	//}
	$retstr = '';
	$retstr .= implode('linend',$arr).'linend';
	$retstr = $retstr.$newstr;

	$_pm['mem']->set( array('k'=>$msg_key, 'v'=>$retstr) ); // default ten min.
}
$_SESSION['msgtime']=time();
$_pm['mem']->set(array('k'=>$uid,'v'=>$rs));
//require_once(dirname(__FILE__).'/chatMessage.php');
$_pm['mem']->memClose();

echo '1';
//##################################################
// @Notice: In here ,add save to database interface.
//##################################################
function splitStr($str)
{
	$arr=array();
	while(strlen($str)>0)
	{
		if(function_exists('mb_substr')) $tmp=mb_substr($str,0,1,'GBK');
		else $tmp=substr($str,0,1);
		$str=str_replace(
				$tmp,
				'',
				$str
				);
		$arr[]=$tmp;
	}
	return $arr;
}
function compareMsg($msg)
{
	return false;
	$msg=splitStr($msg);
	$len=count($msg);
	$similiarRuler=0.6;
	$similarTotal=0;

	for($i=0;$i<count($_SESSION['chatHis']);$i++)
	{
		$count = 0;
		for($j=0;$j<$len;$j++)
		{
			if(strpos($_SESSION['chatHis'][$i],$msg[$j])!==false)
			{
				$count++;
			}
		}
		if($count/$len>=$similiarRuler)
		{
			$similarTotal++;
			//return chatHis.text[i];
		}
	}
	if($similarTotal>=3)
	{
		return $similarTotal;
	}
	return false;
}

 function getUserBagById($id,$pid,&$mysql)
{
	$id = intval($id);
	$pid = intval($pid);
	if($pid<1 || $id<1){
		return false;
	}
	$rs = $mysql->getOneRecord("SELECT b.id as id,
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
								b.pid={$pid} and
								p.id = b.pid and b.uid={$id} and b.sums>0
								ORDER BY b.id DESC limit 1");

	return $rs;
}

function getUserBagByIds($id,$pidarr,&$mysql)
{
	$id = intval($id);
	$rs = array();
	foreach($pidarr as $v)
	{
		$v = intval($v);
		if($v < 1) continue;
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
?>
