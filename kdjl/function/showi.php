<?php

session_start();
require_once('../config/config.game.php');

if(!kdjlCurrentUserIsAdmin())
{
	die();
}

if(isset($_GET['k']) && !is_array($_GET['k']))
{
	$key = trim((string)$_GET['k']);
	if ($key === '' || strlen($key) > 128 || preg_match('/[\x00-\x20\x7f]/', $key))
	{
		die('bad key');
	}
	echo '<pre>'.htmlspecialchars(print_r(kdjlSafeMemValue($_pm['mem']->get($key), null), true), ENT_QUOTES, 'UTF-8').'</pre>';
}

if(isset($_GET['s']) && !is_array($_GET['s'])){
echo '<pre>session debug output disabled</pre>';
}
?>
