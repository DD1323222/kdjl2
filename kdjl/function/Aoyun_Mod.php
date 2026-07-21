<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.07.13
*@Usage: 奥运活动进入页。
*@Note: none
*/
require_once('../config/config.game.php');
require_once(dirname(__FILE__).'/aoyun_common.php');

secStart($_pm['mem']);

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) exit;
$user	 = $_pm['user']->getUserById($uid);
if(!is_array($user)) exit;
$king = '';

//Word part.
$timearr1 = kdjlSafeMemValue($_pm['mem']->get(MEM_TIMENEW_KEY), array());
$timearr = (is_array($timearr1) && isset($timearr1['dati']) && is_array($timearr1['dati'])) ? $timearr1['dati'] : array();
$aoyunActive = kdjlAoyunActiveWindow($timearr, time()) !== false;

$taskword= taskcheck(isset($user['task']) ? $user['task'] : '',6);

$rs = $_pm['mysql']->getOneRecord("SELECT times, result,oksum
									 FROM aoyun_player
									WHERE uid={$uid}
								 ORDER BY id LIMIT 1
								 ");
$oksum = is_array($rs) && isset($rs['oksum']) ? $rs['oksum'] : 0;
if ($aoyunActive && is_array($rs) && isset($rs['times']) && isset($rs['result']) && $rs['times']>0 && $rs['result']==1)	//设置领奖激活。
{
	// in here add time limit.
	$active="style='cursor:pointer;'";
}
else $active='';

$welcome = memContent2Arr("db_welcome",'code');

$a = (is_array($welcome) && isset($welcome['dati']['contents'])) ? $welcome['dati']['contents'] : '';
if(empty($a))
{
	$rs = $_pm['mysql']->getOneRecord("SELECT contents from welcome where code='dati'");
	$a = is_array($rs) && isset($rs['contents']) ? $rs['contents'] : '';
}

if(empty($a))
{
	$a	="活动内容，见官方网站通知。";
}

//@Load template.
$tn = $_game['template'] . 'tpl_aoyun.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);

	$src = array(
				 '#word#',
				 '#active#',
				 '#oksum#',
				 '#anounce_msg#'
				);
	$des = array(
				 $taskword,
		         $active,
				 $oksum,
				 $a
				);
	$king = str_replace($src, $des, $tpl);
}
// gzip echo. if maybe.
ob_start('ob_gzip');
echo $king;
ob_end_flush();
?>
