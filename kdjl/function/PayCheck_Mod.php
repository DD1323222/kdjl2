<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.05.22
*@Usage: Userinfo
*@Note: none
*/
require_once('../config/config.game.php');

//if ($_SESSION['nickname']!='GM') die('关闭调试！');
$user = $_pm['user']->getUserById($_SESSION['id']);
$backObj = array();
$backObj['yb'] = $user['yb'];
$backObj['id'] = $user['id'];
require_once('../config/config.alipay.php');

$backObj['ali_url'] = "http://".$_SERVER['HTTP_HOST']."/alipay/buyYb.php";
echo "OK".json_encode($backObj);
?>;
?>