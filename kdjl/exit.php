<?php
require_once('config/config.game.php');
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid > 0) {
	$_SESSION = array();
	if(ini_get('session.use_cookies'))
	{
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time()-42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
	}
    session_destroy();
    echo '<br/><br/><br/><center><div style="font-size:14px;border:solid #ccc 1px;width:200px;height:100px;padding:5px;">正常退出成功! <br/>
<a href=../passport/login.php>重新登陆</a>
</div></center>';
}else{
    echo("<script type='text/javascript'>window.location='index.php';</script>");
}
?>
