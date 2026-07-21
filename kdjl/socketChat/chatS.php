<?php
header("content-type:text/html;charset=utf-8");
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
require_once('../config/config.game.php');
require_once('config.chat.php');
require_once(dirname(__FILE__).'/../passport/security_common.php');

if(!isset($_SESSION['id'])||!isset($_SESSION['nickname']))
{
	//echo "<script language='javascript'>top.location='/';<\/script>";
	die('请先登录');
}

$uid = intval($_SESSION['id']);
$sessionUsername = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$playerRow = $_pm['mysql']->getOneRecord("SELECT id,name,nickname,password,secid FROM player WHERE id={$uid}");
if(!is_array($playerRow) || !isset($playerRow['name'],$playerRow['nickname']) || $playerRow['name'] !== $sessionUsername || intval($playerRow['secid']) > 0)
{
	die('请先登录');
}
$sessionUsername = $playerRow['name'];
$sessionNickname = $playerRow['nickname'];
$lock_time = isset($playerRow['password']) ? intval($playerRow['password']) : 0;
if($lock_time > 0 && $lock_time <= time())
{
	$_pm['mysql']->query("UPDATE player SET password=0 WHERE id={$uid} AND password={$lock_time}");
	$_pm['mem']->del($uid);
	$lock_time = 0;
}
$vip = isset($_SESSION['vip']) ? intval($_SESSION['vip']) : 0;
$guildRow = $_pm['mysql']->getOneRecord("SELECT guild_id FROM guild_members WHERE member_id={$uid}");
$sessionGuildId = is_array($guildRow) && isset($guildRow['guild_id']) ? intval($guildRow['guild_id']) : 0;
$teamRow = $_pm['mysql']->getOneRecord("SELECT team_id FROM team_members WHERE uid={$uid} AND state>-1 ORDER BY state DESC,team_id LIMIT 1");
$team_id = is_array($teamRow) && isset($teamRow['team_id']) ? intval($teamRow['team_id']) : 0;
$_SESSION['username'] = $sessionUsername;
$_SESSION['nickname'] = $sessionNickname;
$_SESSION['name'] = $sessionUsername;
$_SESSION['guild_id'] = $sessionGuildId;
if($team_id > 0)
{
	$_SESSION['team_id'] = $team_id;
}
else
{
	unset($_SESSION['team_id'], $_SESSION['team_inmap'], $_SESSION['team_state']);
}
$_SESSION['lock_time'] = $lock_time;
$_SESSION['password'] = $lock_time;
$_SESSION['vip'] = $vip;
$_pm['mem']->set(array('k'=>'chat_lock_'.$uid,'v'=>$lock_time));

if(!isset($_SESSION['nicknamegb']))
{
	$_SESSION['nicknamegb'] = $sessionNickname;
}
//检测是不是管理员登陆
$adminRow = $_pm['mysql']->getOneRecord("select contents from welcome where code='admin'");
if(is_array($adminRow) && !empty($adminRow['contents'])){
	$tempArr = array_map('trim',preg_split('/(?:,|;|\xEF\xBC\x8C|\xEF\xBC\x9B)+/',$adminRow['contents'],-1,PREG_SPLIT_NO_EMPTY));
	if(in_array($sessionUsername,$tempArr)){
		$admin = 1;
	}else{
		$admin = 0;
	}
}else{
	$admin = 0;
}

$mac_addr = isset($_SESSION['mac']) && !is_array($_SESSION['mac']) ? substr($_SESSION['mac'],0,32) : '';
$sid = session_id();
$uIP = get_real_ip();
if(!kdjlReplaceChatLoginAuth($_pm['mysql'], array(
	'uid'=>$uid,
	'username'=>$sessionUsername,
	'nickname'=>$sessionNickname,
	'sid'=>$sid,
	'guild_id'=>$sessionGuildId,
	'team_id'=>$team_id,
	'lock_time'=>$lock_time,
	'admin'=>$admin,
	'vip'=>$vip,
	'u_ip'=>$uIP,
	'is_online'=>1,
	'mac_addr'=>$mac_addr
))) die('聊天登录初始化失败');


function get_real_ip(){
	$ip=false;

	if(!empty($_SERVER["HTTP_CLIENT_IP"]) && filter_var($_SERVER["HTTP_CLIENT_IP"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)){
		$ip = $_SERVER["HTTP_CLIENT_IP"];
	}

	if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
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
	$remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
	return ($ip ? $ip : (filter_var($remoteAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? $remoteAddr : ''));
}
?>
