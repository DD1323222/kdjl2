<?php
require_once('config/config.game.php');
define("WE","db_welcome1");
$cmd = kdjlSafeMemValue($_pm['mem'] -> get(WE), array());
$iframearr = array('width'=>'','height'=>'','background'=>'','line_height'=>'','contents'=>'');
if(!empty($cmd['helpphp']))
{
	$arr = explode(",",$cmd['helpphp']);
	foreach($arr as $v)
	{
		$newarr = explode(";",$v,2);
		if(count($newarr) == 2 && array_key_exists($newarr[0],$iframearr)) $iframearr[$newarr[0]] = $newarr[1];
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>无标题文档</title>
</head>

<body>
<div style="font-size:12px;width:<?php echo $iframearr['width']; ?>px; height:<?php echo $iframearr['height']; ?>px; left:0px; top:0px; background:<?php echo $iframearr['background']; ?>; line-height:<?php echo $iframearr['line_height']; ?>">
<?php echo $iframearr['contents']; ?>
</div>
</body>
</html>
