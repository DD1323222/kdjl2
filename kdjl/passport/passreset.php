<?php

die();
if($_POST)
{
	require_once("../config/config.game.php");
	header('Content-Type:text/html;charset=utf-8');
	@session_start();
	$db = new mysql();
	$passport = mysql_real_escape_string($_POST['passport']);
	$res = $db->getOneRecord("SELECT * FROM player WHERE name = '{$passport}' AND secret = '".md5($_POST['password'])."'");
	if($res)
	{
		if($_POST['newpassword1'] == $_POST['newpassword2'] && strlen($_POST['newpassword1']) >=6 && strlen($_POST['newpassword1']) <= 20)
		{
			$db->query("UPDATE player SET secret='".md5($_POST['newpassword1'])."' WHERE name='{$_POST['passport']}'");
			echo "<script>alert('修改成功')</script>";
		//	updateDiszucUserPwd($_POST['passport'],$_POST['newpassword1']);
		}

	}
	else
	{
		echo "<script>alert('用户原账号密码不正确')</script>";
	}
}

function updateDiszucUserPwd($passport,$password){
	$passport = mysql_real_escape_string($passport);

	$link = mysql_connect("localhost", "poke_war", "poke@war!@#$") or die("Could not connect : " . mysql_error()); 
    mysql_select_db("poke_war") or die("Could not select database");
	$sql = "select uid,username from cdb_uc_members where username='{$passport}'";
	$result = mysql_query($sql);
	if(mysql_affected_rows()>0){
		
		$salt = substr(uniqid(rand()), -6);
		$password = md5(md5($password).$salt);
		$sql = "update cdb_uc_members set password='{$password}',salt='{$salt}' where username='{$passport}'";
		mysql_query($sql);
		$sql = "update cdb_members set password='{$password}' where username='{$passport}'";
		mysql_query($sql);
		//echo $sql;
	}
	//echo $sql;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<title>密码修改</title>
<body>
<form action="" method="post" onsubmit="return chick()">
用户名：
<br />
<input type="text" name="passport" id="name" />
<br />
密码：
<br />
<input type="password" name="password" id="pass" />
<br />
新密码：
<br />
<input type="password" name="newpassword1" id="pass1" />
<br />
再输入一次新密码：
<br />
<input type="password" name="newpassword2" id="pass2" />
<br />
<input type="submit" value="修改"  />
</form>
<script>
function chick()
{
	var pass1 = document.getElementById('pass1').value;
	var pass2 = document.getElementById('pass2').value;
	var oldpass = document.getElementById('pass').value;
	var name = document.getElementById('name').value;
	if(pass1 != pass2)
	{
		return false;
	}
	if(pass1.length < 6 || pass1.length > 20)
	{
		return false;
	}
	return true;
}
</script>
</body>

</html>

