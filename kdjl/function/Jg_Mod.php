<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.05.22
*@Usage: Shop main ui
*@Note: none
*/
require_once('../config/config.game.php');

secStart($_pm['mem']);

$user		= $_pm['user']->getUserById($_SESSION['id']);


//Word part.

$taskword10= taskcheck($user['task'],10);

$taskword11= taskcheck($user['task'],11);

$taskword12= taskcheck($user['task'],12);

$taskword13= taskcheck($user['task'],13);


$_pm['mem']->memClose();

//@Load template.
$tn = $_game['template'] . 'tpl_jg.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);
	
	$src = array('#one#', // 12
				 '#two#', //11
				 '#three#', //10
				 '#four#' // 13
				);
	$des = array($taskword11,
				 $taskword12,
				 $taskword10,
		         $taskword13
				);
	$shop = str_replace($src, $des, $tpl);
}
// gzip echo. if maybe.
ob_start('ob_gzip');
echo $shop;
ob_end_flush();

?>