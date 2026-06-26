<?php
require_once('config/config.game.php');
$m = new memory();	// Init memcache.
$db = new mysql();
$ip=$_SERVER['REMOTE_ADDR'];
//exec("netstat -an |grep \":80\" |grep $ip| grep \"ESTABLISHED\"|wc -l" , $outputTop , $return_var);
//exec("netstat -an |grep \":80\"|grep \"ESTABLISHED\"|awk -F\":\" '{print $8}'" , $outputTop , $return_var);
exec("netstat -an |grep \":80\"|grep \"ESTABLISHED\"|awk -F\":\" '{print $2}'|awk -F\" \" '{print $2}'" , $outputTop , $return_var);
/*print_r($outputTop);
echo "<br />".$return_var;
exit;*/
$arr = array();
$newarr = array();
if(is_array($outputTop))
{
	foreach($outputTop as $v)
	{
		if(in_array($v,$arr))
		{
			$newarr[$v]++; 
		}
		else
		{
			$arr[] = $v;
		}
	}
}
arsort($newarr);
unset($arr,$v);
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
		$sql = "SELECT uname FROM logins WHERE uIP = '$k' and times > $time";
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
    <td height="25" align="center" bgcolor="#FFFFFF"><?=$k?></td>
    <td height="25" align="center" bgcolor="#FFFFFF"><?=$v?></td>
    <td height="25" align="center" bgcolor="#FFFFFF"><?php
		foreach($uarr as $vvv)
		{
			echo $vvv."<br />";
		}
	?></td>
  </tr>
  <?php
 	 }
  }
  ?>
</table>