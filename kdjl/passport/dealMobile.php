<?php
/**
* user name. Check.
*/
require_once("../config/config.game.php");
require_once(dirname(__FILE__).'/security_common.php');
@session_start();
$_user = (isset($_GET['n']) && !is_array($_GET['n'])) ? $_GET['n'] : '';
$db = new mysql();
$pssport = $db->escape((isset($_POST['username']) && !is_array($_POST['username'])) ? $_POST['username'] : '');
$password = (isset($_POST['password']) && !is_array($_POST['password'])) ? $_POST['password'] : '';
$from = (isset($_REQUEST['from']) && !is_array($_REQUEST['from'])) ? intval($_REQUEST['from']) : 0;
if($pssport === '' || $password === '')
{
	echo "<script>window.location='login.php'</script>";
	exit;
}
$hasFromType = kdjlMysqlTableHasColumn($db, 'player', 'from_type');
$hasHeartTime = kdjlMysqlTableHasColumn($db, 'player', 'heart_time');
$hasBotTime = kdjlMysqlTableHasColumn($db, 'player', 'bot_time');
$optionalColumns = ($hasFromType ? ',from_type' : '').($hasHeartTime ? ',heart_time' : '');
$rs = $db->getOneRecord("SELECT id,name,nickname,password,secret,secid{$optionalColumns} FROM player WHERE secret = '".md5($password)."' AND name= '".$pssport."'");
if (is_array($rs))
{
	$playerId = intval($rs['id']);
	session_regenerate_id(true);
	if($from == 1)
	{
		if($rs['secid']=='1'){
			die('服务器繁忙，请稍候再试！');
		}
		if($hasFromType) $db->query("UPDATE player SET from_type = 1 WHERE id =  '{$playerId}'");
		if($hasFromType && $hasHeartTime && $hasBotTime && intval($rs['from_type']) == 1)
		{
			$botTime = time()-intval($rs['heart_time']);
			$botTime = $botTime>60?$botTime:0;
			$db->query("UPDATE player SET bot_time = {$botTime} WHERE id =  '{$playerId}'");
		}
	}
	else
	{
		if($hasFromType) $db->query("UPDATE player SET from_type = 0 WHERE id =  '{$playerId}'");
	}
	$user = $rs;
	$_SESSION['username'] = 	$rs['name'];
	$_SESSION['nickname'] = $rs['nickname'];
	$_SESSION['name'] = 	$rs['name'];
	$_SESSION['mac']= (isset($_POST['mac_addr']) && !is_array($_POST['mac_addr'])) ? substr($_POST['mac_addr'],0,32) : '';
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
	if($from == 1)
	{
		if(!kdjlReplaceChatLoginAuth($db, array(
			'uid'=>$playerId,
			'username'=>$_SESSION['username'],
			'nickname'=>$user['nickname'],
			'sid'=>session_id(),
			'guild_id'=>$_SESSION['guild_id'],
			'team_id'=>$teamId,
			'lock_time'=>$lockTime,
			'vip'=>0,
			'is_online'=>1,
			'mac_addr'=>$_SESSION['mac']
		))) die('聊天登录失败，请稍候再试！');
  //MAC 禁止判断
		$macCheckFile = dirname(__FILE__).'/../ipAdmin/ipm.php';
		if(is_file($macCheckFile)) require_once($macCheckFile);
		echo 'SID'.session_id();
  //MAC 禁止判断
//               require("../ipAdmin/ipm.php");
	}
	else
	{
		echo "<script>window.location='../login/login.php'</script>";
	}
}
else
{
	echo "<script>window.location='login.php'</script>";
}
?>
