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

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');
$user		= $_pm['user']->getUserById($uid);
if(!is_array($user)) $user = array('task' => 0);
$taskid = isset($user['task']) ? intval($user['task']) : 0;


//Word part.

$taskword10= taskcheck($taskid,10);

$taskword11= taskcheck($taskid,11);

$taskword12= taskcheck($taskid,12);

$taskword13= taskcheck($taskid,13);


$_pm['mem']->memClose();

//@Load template.
$tn = $_game['template'] . 'tpl_jg.html';
$shop = '';
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
