<?php
/**
 * Get/set auto-fight payment mode.
 */
require_once('../config/config.game.php');

secStart($_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('0');

$err = 0;
$way = (isset($_REQUEST['way']) && !is_array($_REQUEST['way'])) ? intval($_REQUEST['way']) : 0;
if(empty($way))
{
	$_SESSION['way'.$uid] = "money";
}
if($way == 1)
{
	$_SESSION['way'.$uid] = "money";
	$err = 1;
}
else if($way == 2)
{
	$_SESSION['way'.$uid] = "yb";
	$err = 2;
}
echo $err;
?>
