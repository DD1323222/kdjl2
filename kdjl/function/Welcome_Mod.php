<?php
//Init part.


//###########################
// @Load template.
//###########################

//自动领取奖励
require_once('../config/config.game.php');
require_once(dirname(__FILE__).'/fight_wait_common.php');
secStart($_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
kdjlFightBeginNavigationWait($uid);
$teamId = isset($_SESSION['team_id']) ? intval($_SESSION['team_id']) : 0;
if($teamId > 0)
{
	$teamMapId = isset($_SESSION['team_inmap']) ? intval($_SESSION['team_inmap']) : 0;
	header('Location: Team_Mod.php?n='.$teamMapId);
	exit;
}
else
{
	unset($_SESSION['team_id'], $_SESSION['team_inmap'], $_SESSION['team_state']);
}

$welcome = memContent2Arr("db_welcome",'code');

$word = (is_array($welcome) && isset($welcome['welcome']['contents'])) ? $welcome['welcome']['contents'] : '';
$img = (is_array($welcome) && isset($welcome['welimg']['contents'])) ? $welcome['welimg']['contents'] : '';
$href = (is_array($welcome) && isset($welcome['href']['contents'])) ? $welcome['href']['contents'] : '';
$content = (is_array($welcome) && isset($welcome['welcontent']['contents'])) ? $welcome['welcontent']['contents'] : '';
$a = '';
$imgs = '';

$user		= $_pm['user']->getUserById($_SESSION['id']);

if ($user['sysautotime']==0 || $user['sysautotime']<mktime(0, 0, 0, date("m",time()), date("d",time()), date("Y",time())))
{
	 $autosum = 800;
	//$u->updateMemUser($_SESSION['id']);
}
else $autosum = 0;
//echo $autosum;exit;
$_pm['mem']->memClose();
$_game['template'] = '../template/';
$tn = $_game['template'] . 'tpl_welcome.html';
$cet = '';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);

	$src = array('#welcomeword#',
				 '#autosum#',
				 '#welcome#',
				 '#img#',
				 '#href#',
				 '#content#',
				 '#imgs#'
				);
	$des = array($word,
				$autosum,
				$a,
				$img,
				$href,
				$content,
				$imgs
				);
	$cet = str_replace($src, $des, $tpl);
}
// gzip echo. if maybe.
ob_start('ob_gzip');
echo $cet;
ob_end_flush();
?>
