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

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');
$user	 = $_pm['user']->getUserById($uid);
if(!is_array($user)) $user = array('task' => 0, 'prestige' => 0, 'jprestige' => 0);

//Word part.
$taskword= taskcheck(isset($user['task']) ? intval($user['task']) : 0,6);


//@Load template.
$tn = $_game['template'] . 'tpl_kinPrestige.html';
$king = '';
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
				 isset($user['prestige']) ? $user['prestige'] : 0,
				 isset($user['jprestige']) ? $user['jprestige'] : 0
				);
	$king = str_replace($src, $des, $tpl);
}
// gzip echo. if maybe.
ob_start('ob_gzip');
echo $king;
ob_end_flush();
?>
