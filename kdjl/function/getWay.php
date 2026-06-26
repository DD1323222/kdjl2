<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: 谭炜

*@Write Date: 2008.09.24
*@Update Date: 
*@Usage:得到用户自动战斗的方式
*@Note: NO Add magic props.
  本模块主要功能：
  1)得到用户自动战斗的方式
*/
require_once('../config/config.game.php');

secStart($_pm['mem']);
$way = intval($_REQUEST['way']);
if(empty($way))
{
	$_SESSION['way'.$_SESSION['id']] = "money";//默认为金币版
}
if($way == 1)
{
	$_SESSION['way'.$_SESSION['id']] = "money";//金币版
	$err = 1;
}
else if($way == 2)
{
	$_SESSION['way'.$_SESSION['id']] = "yb";//元宝版
	$err = 2;
}
echo $err;
?>