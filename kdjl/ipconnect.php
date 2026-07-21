<?php
require_once(dirname(__FILE__).'/config/config.game.php');
if(!kdjlCurrentUserIsAdmin())
{
	header('HTTP/1.1 404 Not Found');
	exit;
}
$m = new memory();	// Init memcache.
$db = new mysql();
$ip=isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
$outputTop = array();
$return_var = 1;
//exec("netstat -an |grep \":80\" |grep $ip| grep \"ESTABLISHED\"|wc -l" , $outputTop , $return_var);
//exec("netstat -an |grep \":80\"|grep \"ESTABLISHED\"|awk -F\":\" '{print $8}'" , $outputTop , $return_var);
if(function_exists('exec'))
	exec("netstat -an |grep \":80\"|grep \"ESTABLISHED\"|awk -F\":\" '{print $2}'|awk -F\" \" '{print $2}'" , $outputTop , $return_var);
/*print_r($outputTop);
echo "<br />".$return_var;
exit;*/
$newarr = array();
if(is_array($outputTop))
{
	foreach($outputTop as $v)
	{
		$v = trim($v);
		if($v === '') continue;
		if(isset($newarr[$v]))
		{
			$newarr[$v]++;
		}
		else
		{
			$newarr[$v] = 1;
		}
	}
}
arsort($newarr);
unset($v);
?>
<table width="778" border="0" cellpadding="0" cellspacing="1" bgcolor="#CCCCCC">
  <tr>
    <td height="25" align="center" bgcolor="#FFFFFF">ip</td>
    <td height="25" align="center" bgcolor="#FFFFFF">连接数</td>
    <td height="25" align="center" bgcolor="#FFFFFF">帐号</td>
  </tr>
  <?php
  foreach($newarr as $k => $v)
  {
	if($v > 10)
	{
		$uarr = array();
		$time = time() - 300;
		$ipSql = $db->escape($k);
		$sql = "SELECT uname FROM logins WHERE uIP = '{$ipSql}' and times > $time";
		$arr = $db -> getRecords($sql);
		if(!is_array($arr))
		{
			continue;
		}
		foreach($arr as $vv)
		{
			if(!in_array($vv['uname'],$uarr))
			{
				$uarr[] = $vv['uname'];
			}
		}
  ?>
  <tr>
    <td height="25" align="center" bgcolor="#FFFFFF"><?php echo htmlspecialchars($k, ENT_QUOTES, 'UTF-8'); ?></td>
    <td height="25" align="center" bgcolor="#FFFFFF"><?php echo $v; ?></td>
    <td height="25" align="center" bgcolor="#FFFFFF"><?php
		foreach($uarr as $vvv)
		{
			echo htmlspecialchars($vvv, ENT_QUOTES, 'UTF-8')."<br />";
		}
	?></td>
  </tr>
  <?php
	 }
  }
  ?>
</table>
