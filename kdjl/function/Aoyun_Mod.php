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

secStart($_pm['mem']);

$user	 = $_pm['user']->getUserById($_SESSION['id']);

//Word part.
$timearr1 = unserialize($_pm['mem']->get(MEM_TIMENEW_KEY));
$timearr = $timearr1['dati'];
foreach($timearr as $k => $v)
{
	$dayarr = explode("-",$v['days']);
}

$taskword= taskcheck($user['task'],6);

$rs = $_pm['mysql']->getOneRecord("SELECT times, result,oksum
									 FROM aoyun_player
									WHERE uid={$_SESSION['id']}
								 ");
if (is_array($rs) && $rs['times']>0 && $rs['result']==1)	//设置领奖激活。
{
	// in here add time limit.
	$active="style='cursor:pointer;'";
}
else $active='';

$welcome = memContent2Arr("db_welcome",'code');

$a = $welcome['dati']['contents'];
if(empty($a))
{
	$rs = $_pm['mysql']->getOneRecord("SELECT contents from welcome where code='dati'");
	$a = $rs['contents'];
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
				 $rs['oksum'],
				 $a				 
				);
	$king = str_replace($src, $des, $tpl);
}
// gzip echo. if maybe.
ob_start('ob_gzip');
echo $king;
ob_end_flush();
?>