<?php
/**
* user name. Check.
*/
require_once("../config/config.game.php");

@session_start();
$db = new mysql();



$rs = $db->getOneRecord("SELECT id,name,nickname,password,secret FROM player WHERE secret = '".md5($_POST['password'])."' AND name= '".$_POST['username']."'");

if (is_array($rs))
{
	$user = $rs;

	$_SESSION['username'] = $rs['name'];	
	$_SESSION['nickname'] = $rs['nickname'];
	$_SESSION['name'] = 	$rs['name'];
	$_SESSION['id'] = $rs['id'];
	$_SESSION['LoginApiState'] = 1;
	$_SESSION['game_server_flag'] = GAME_SERVER_FLAG;
	if(empty($rs['password'])){
		$_SESSION['lock_time'] = 0;
	}else{
		$_SESSION['lock_time'] = $rs['password'];
	}

	//获取家族的id号供聊天使用
	$sql = "select member_id,guild_id from guild_members where member_id='{$rs['id']}'";
	$guild = $db->getOneRecord($sql);
	if($guild){
		$_SESSION['guild_id'] = $guild['guild_id'];
	}else{
		$_SESSION['guild_id'] = 0;
	}
	//获取家族的id号供聊天使用
	$sql = "select member_id,guild_id from guild_members where member_id='{$rs['id']}'";
	$guild = $db->getOneRecord($sql);
	if($guild){
		$_SESSION['guild_id'] = $guild['guild_id'];
	}else{
		$_SESSION['guild_id'] = 0;
	}
	echo "<script>window.location='../login/login.php'</script>";
}
else
{
	echo "<script>window.location='login.php'</script>";
}
?>
