<?php
//Init part.


//###########################
// @Load template.
//###########################

//自动领取奖励
require_once('../config/config.game.php');
secStart($_pm['mem']);
if(isset($_SESSION['team_id']))
{
	header('location:Team_Mod.php?n='.$_SESSION['team_inmap']);
}

$welcome = memContent2Arr("db_welcome",'code');

$word = $welcome['welcome']['contents'];
$img = $welcome['welimg']['contents'];
$href = $welcome['href']['contents'];
$content = $welcome['welcontent']['contents'];

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
