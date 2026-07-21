<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.05.22
*@Usage: Public top (GongGao Bang.)
*@Note: none
*/
require_once('../config/config.game.php');
define('MEM_TOP_KEY', "publictop");


secStart($_pm['mem']);

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) exit;
$user = $_pm['user']->getUserById($uid);
if(!is_array($user)) $user = array('task' => 0);
//task check.
$taskId = isset($user['task']) ? intval($user['task']) : 0;
$taskword= taskcheck($taskId==0?1:$taskId,8);

/*define('MEM_TOP_ACTIVE', "db_public");
$welcome = unserialize($_pm['mem']->get(MEM_TOP_ACTIVE));*/

$welcome = memContent2Arr("db_welcome",'code');
$message = (is_array($welcome) && isset($welcome['public']['contents'])) ? $welcome['public']['contents'] : '';

//
$_pm['mem']->memClose();
unset($db);

//@Load template.
$tn = $_game['template'] . 'tpl_active.html';
$public = '';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);

	$src = array(
				 '#word#',
				 '#message#'
				);
	$des = array(
				 $taskword,
				 $message
				);
	$public = str_replace($src, $des, $tpl);
}

// gzip echo. if maybe.
ob_start('ob_gzip');
echo $public;
ob_end_flush();
?>
