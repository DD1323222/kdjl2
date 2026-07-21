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
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('ERROR');
$user = $_pm['user']->getUserById($uid);
if(!is_array($user)) die('ERROR');
$backObj = array();
$backObj['yb'] = isset($user['yb']) ? $user['yb'] : 0;
$backObj['id'] = isset($user['id']) ? $user['id'] : 0;
require_once('../config/config.alipay.php');

$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
if(!preg_match('/^[A-Za-z0-9.-]{1,255}(:[0-9]{1,5})?$/', $host)) $host = '';
$backObj['ali_url'] = "http://".$host."/alipay/buyYb.php";
echo "OK".json_encode($backObj);
?>
