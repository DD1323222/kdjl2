<?php
require_once('config/config.game.php');
define("WE","db_welcome1");
$cmd = unserialize($_pm['mem'] -> get(WE));
if(!empty($cmd['helpphp']))
{
	$arr = explode(",",$cmd['helpphp']);
	foreach($arr as $v)
	{
		$newarr = explode(";",$v);
		$iframearr[$newarr[0]] = $newarr[1];
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=gbk" />
<title>无标题文档</title>
</head>

<body>
<div style="font-size:12px;width:<?=$iframearr['width']?>px; height:<?=$iframearr['height']?>px; left:0px; top:0px; background:<?=$iframearr['background']?>; line-height:<?=$iframearr['line_height']?>">
<?=$iframearr['contents']?>
</div>
</body>
</html>
