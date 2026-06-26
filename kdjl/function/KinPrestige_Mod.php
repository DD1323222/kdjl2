<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.07.13
*@Usage: King
*@Note: none
*/
require_once('../config/config.game.php');

secStart($_pm['mem']);

$user	 = $_pm['user']->getUserById($_SESSION['id']);

//Word part.
$taskword= taskcheck($user['task'],6);


//@Load template.
$tn = $_game['template'] . 'tpl_kinPrestige.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);
	
	$src = array(
				 '#word#',
				 '#prestige#',
				 '#jprestige#'
				);
	$des = array(
				 $taskword,
				 $user['prestige'],
				 $user['jprestige']
				);
	$king = str_replace($src, $des, $tpl);
}
// gzip echo. if maybe.
ob_start('ob_gzip');
echo $king;
ob_end_flush();
?>