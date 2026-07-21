<?php
/**
* user name. Check.
*/
require_once("../config/config.game.php");
require_once(dirname(__FILE__).'/security_common.php');

@session_start();
$db = new mysql();



$username = $db->escape((isset($_POST['username']) && !is_array($_POST['username'])) ? $_POST['username'] : '');
$password = (isset($_POST['password']) && !is_array($_POST['password'])) ? $_POST['password'] : '';
if($username === '' || $password === '')
{
	echo "<script>window.location='login.php'</script>";
	exit;
}
$rs = $db->getOneRecord("SELECT id,name,nickname,password,secret FROM player WHERE secret = '".md5($password)."' AND name= '".$username."'");

if (is_array($rs))
{
	$playerId = intval($rs['id']);
	session_regenerate_id(true);
	$user = $rs;

	$_SESSION['username'] = $rs['name'];
	$_SESSION['nickname'] = $rs['nickname'];
	$_SESSION['name'] = 	$rs['name'];
	$_SESSION['id'] = $playerId;
	$_SESSION['LoginApiState'] = 1;
	$_SESSION['game_server_flag'] = GAME_SERVER_FLAG;
	$lockTime = isset($rs['password']) ? intval($rs['password']) : 0;
	if($lockTime > 0 && $lockTime <= time())
	{
		$db->query("UPDATE player SET password=0 WHERE id='{$playerId}' AND password='{$lockTime}'");
		$lockTime = 0;
	}
	$_SESSION['lock_time'] = $lockTime;
	$_SESSION['password'] = $lockTime;
	$_SESSION['vip'] = 0;
	$_pm['mem']->set(array('k'=>'chat_lock_'.$playerId,'v'=>$lockTime));

	//获取家族的id号供聊天使用
	$sql = "select member_id,guild_id from guild_members where member_id='{$playerId}'";
	$guild = $db->getOneRecord($sql);
	if(is_array($guild)){
		$_SESSION['guild_id'] = $guild['guild_id'];
	}else{
		$_SESSION['guild_id'] = 0;
	}
	$team = $db->getOneRecord("SELECT team_id FROM team_members WHERE uid='{$playerId}' AND state>-1 ORDER BY state DESC,team_id LIMIT 1");
	$teamId = is_array($team) && isset($team['team_id']) ? intval($team['team_id']) : 0;
	if($teamId > 0){
		$_SESSION['team_id'] = $teamId;
	}else{
		unset($_SESSION['team_id'], $_SESSION['team_inmap'], $_SESSION['team_state']);
	}
	if(!kdjlReplaceChatLoginAuth($db, array(
		'uid'=>$playerId,
		'username'=>$_SESSION['username'],
		'nickname'=>$user['nickname'],
		'sid'=>session_id(),
		'guild_id'=>$_SESSION['guild_id'],
		'team_id'=>$teamId,
		'lock_time'=>$lockTime,
		'vip'=>0,
		'is_online'=>0
	))) die('聊天登录初始化失败，请稍候再试！');
	//获取家族的id号供聊天使用
	echo "<script>window.location='../login/login.php'</script>";
}
else
{
	echo "<script>window.location='login.php'</script>";
}
?>
