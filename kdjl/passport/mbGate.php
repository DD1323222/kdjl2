<?php
require_once("../config/config.game.php");
if($_SERVER['REMOTE_ADDR'] != '171.216.222.197')
{
	//die();
}
$word = '';
if($_POST)
{
	foreach($_POST as $key =>$info)
	{
		$_POST[$key] = urldecode($info);
		$word .= "{$key}={$_POST[$key]}&";
		inject_check($_POST[$key]);
	}
}
log_result($word);

//超过3次错误就不能再次操作，每日限制三次
$userInfo = userCheck($_POST['passport']);
$pb = $_pm['mysql']->getOneRecord("SELECT * FROM PasswordProtection WHERE player_id = '{$userInfo['id']}'");
$days = date('z');
if($pb){
	if($days == $pb['startTime']){//同一天
		if($pb['count'] >= 3){
			die("密保答案每天只能偿试3次！");
		}
		
	}else{//不同天，初始化一下
		$_pm['mysql']->query("UPDATE PasswordProtection SET startTime = '".$days."',count=0 WHERE player_id = '{$userInfo['id']}'");
	}
}




if($_POST['passport'] && !isset($_POST['an']) && !isset($_POST['qu']) && !isset($_POST['anS']) )
{
	$passport = $_POST['passport'];
	$user = userCheck($passport);
	$mb = $_pm['mysql']->getOneRecord("SELECT * FROM PasswordProtection WHERE player_id = '{$user['id']}'");
	if(!$mb)
	{
		die("OK1");
	}
	else
	{
		die("OK2|".$mb['question']);
	}
	
}
else if($_POST['passport'] && isset($_POST['an']) && isset($_POST['qu']) && isset($_POST['pass']) )
{
	$passport = $_POST['passport'];
	$an = $_POST['an'];
	$qu = $_POST['qu'];
	$pass = $_POST['pass'];
	$user = userCheck($passport);
	if(md5($pass) != $user['secret'])
	{
		$_pm['mysql']->query("UPDATE PasswordProtection SET count=count+1 WHERE player_id = '{$user['id']}'");
		die("密码错误");
	}
	$mb = $_pm['mysql']->getOneRecord("SELECT * FROM PasswordProtection WHERE player_id = '{$user['id']}'");
	if(!$mb)
	{
		$_pm['mysql']->query(" INSERT INTO PasswordProtection SET player_id = '{$user['id']}',question = '{$qu}',answer='{$an}'");
		die("OK设置成功");
	}
	else
	{
		die("该用户已经设置");
	}
}
else if($_POST['passport'] && $_POST['anS'] && !isset($_POST['newPass']))
{
	$passport = $_POST['passport'];
	$anS = $_POST['anS'];
	$user = userCheck($passport);
	
	$mb = $_pm['mysql']->getOneRecord("SELECT * FROM PasswordProtection WHERE player_id = '{$user['id']}'");
	if(!$mb)
	{
		die("该用户未设置密保");
	}
	if($mb['answer'] == $anS)
	{
		die("OK");
	}
	else
	{
		$_pm['mysql']->query("UPDATE PasswordProtection SET count=count+1 WHERE player_id = '{$user['id']}'");
		die("密保答案不正确");
	}
	
}
else if($_POST['passport'] && $_POST['anS'] && isset($_POST['newPass']))
{
	$passport = $_POST['passport'];
	$anS = $_POST['anS'];
	$newPass = $_POST['newPass'];
	$user = userCheck($passport);
	
	
	$mb = $_pm['mysql']->getOneRecord("SELECT * FROM PasswordProtection WHERE player_id = '{$user['id']}'");
	if(!$mb)
	{
		die("该用户未设置密保");
	}
	if($mb['answer'] == $anS)
	{
		$_pm['mysql']->query("UPDATE player SET secret = '".md5($newPass)."' WHERE id = '{$user['id']}'");
		die("OK");
	}
	else
	{
		$_pm['mysql']->query("UPDATE PasswordProtection SET count=count+1 WHERE player_id = '{$user['id']}'");
		die("密保答案不正确");
	}
}


/**日志消息,把支付宝返回的参数记录下来
 * 请注意服务器是否开通fopen配置
 */
function  log_result($word) {
	return true;
    $fp = fopen(date("Ymd").".txt","a");
    flock($fp, LOCK_EX) ;
	$time = date("Y-m-d H:i:s",time());
    fwrite($fp,"time:{$time}argv:".$word."\n");
    flock($fp, LOCK_UN);
    fclose($fp);
}

function userCheck($passport)
{
	global $_pm;
	$user  =  $_pm['mysql']->getOneRecord("SELECT id,secret FROM player WHERE name = '{$passport}'");
	if(!$user)
	{
		die("无此用户");
	}
	return $user;
}
function inject_check($Sql_Str) {//自动过滤Sql的注入语句。
    $check=preg_match('/select|insert|\ |update|UPDATE|delete|DELETE|\'|\\*|\*|\.\.\/|\.\/|union|into|load_file|outfile/i',$Sql_Str);
    if ($check) {
        die("有非法字符");
    }else{
        return $Sql_Str;
    }
}
?>