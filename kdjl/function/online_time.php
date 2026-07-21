<?php
header('HTTP/1.1 404 Not Found');
exit;
?>
<form action="" name="online" method="post">
查询玩家通行证:
<input name="passport" type="text" size="16" />
<input type="submit" value="查询" />
</form>
<?php
$key_time = false;
$key_nosign = false;
$key_sign = false;
$passport = (isset($_POST['passport']) && !is_array($_POST['passport'])) ? $_POST['passport'] : '';
if($_POST)
{
	$key_time = array_search('clear',$_POST);
	$key_nosign = array_search('被限制',$_POST);
	$key_sign = array_search('不被限制',$_POST);
}
if($passport || !empty($key_time) || !empty($key_sign) || !empty($key_nosign) )
{
	$_mysql['host']	= "61.160.192.13";
	$_mysql['user']	= "webpm2";
	$_mysql['pass'] = "chivci&*qwe89op";
	$_mysql['db']	= "fangcengmi";
	$db = new mysql();
	$username = $passport;
	if( !empty($key_time) )
	{
		$username = $key_time;
		$db -> query( "UPDATE fcm SET online_time = 0 WHERE passport = '".$db->escape($key_time)."'" );
	}
	if( !empty($key_sign) )
	{
		$username = $key_sign;
		$db -> query( "UPDATE fcm SET needfcm = 0 WHERE passport = '".$db->escape($key_sign)."'" );
	}
	if( !empty($key_nosign) )
	{
		$username = $key_nosign;
		$db -> query( "UPDATE fcm SET needfcm = 1 WHERE passport = '".$db->escape($key_nosign)."'" );
	}
	$res = $db -> getOneRecord(" SELECT * FROM fcm WHERE passport = '".$db->escape($username)."'");
	if( !is_array($res) )
	{
		die("该通行证不存在或没有登陆过游戏");
	}
	else
	{
		$name = $res['passport'];
		$time = $res['online_time'];
		$time_look = out($time);
		$day = $res['the_day'];
		$update = date('Y-m-d H:i:s',$res['update_time']);
		$fcmsign = $res['needfcm'] == 0?"未填身份证":"已填身份证";
		$cfcm = $res['needfcm'] == 0?"被限制":"不被限制";
	}
}
function out($sec)
{
	$d = floor($sec/86400);
	$tmp = $sec%86400;
	$h = floor($tmp/3600);
	$tmp %= 3600;
	$m = floor($tmp/60);
	$s = $tmp%60;
	return   $d. "天 ".$h. "小时 ".$m. "分 ".$s. "秒";
}
if($passport || !empty($key_time) || !empty($key_sign) || !empty($key_nosign) )
{
?>
<div>
<table border="0" style="border:#FFFFFF" width="800px">
	<tr bgcolor="#0066CC" style="border:#000000; color:#FFFFFF" align="center">
		<td width="25%">通行证</td>
		<td width="10%">身份证信息</td>
		<td width="10%">在线时间(秒)</td>
		<td width="20%">在线时间(格式)</td>
		<td width="10%">在线有效日期</td>
		<td width="25%">最后更新时间</td>
		<td width="5%">清在线时间</td>
		<td width="5%">防沉迷标记</td>
	</tr>
	<tr align="center">
		<td><?php echo $name; ?></td>
		<td><?php echo $fcmsign; ?></td>
		<td><?php echo $time; ?></td>
		<td><?php echo $time_look; ?></td>
		<td><?php echo $day; ?></td>
		<td><?php echo $update; ?></td>
		<td><form action="" method="post"><input type="submit" name="<?php echo $name; ?>" value="clear" /></form></td>
		<td><form action="" method="post"><input type="submit" name="<?php echo $name; ?>" value="<?php echo $cfcm; ?>" /></form></td>
	</tr>
</table>
</div>
</body>
</html>
<?php
}
?>
