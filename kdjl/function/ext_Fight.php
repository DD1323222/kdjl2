<?php
/**
@Usage: Auto Fight set
*/
require_once('../config/config.game.php');
//secStart($_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('0');
$op = (isset($_REQUEST['op']) && !is_array($_REQUEST['op'])) ? intval($_REQUEST['op']) : 0;
$expTypeKey = 'exptype'.$uid;
$wayKey = 'way'.$uid;
$fightTimeKey = 'fttime'.$uid;
//if($_SESSION['multi_monsters'.$_SESSION['id']] != 2){
	//die('exit');
//}
if(!in_array($op, array(1,2,3,4), true)) die('0');

$countField = ($op == 1 || $op == 3) ? 'maxautofitsum' : 'sysautosum';
if(!$_pm['mysql']->query('START TRANSACTION')) die('0');
$player = $_pm['mysql']->getOneRecord("SELECT {$countField} FROM player WHERE id={$uid} FOR UPDATE");
if(!is_array($player))
{
	$_pm['mysql']->query('ROLLBACK');
	die('0');
}

$remaining = isset($player[$countField]) ? max(0, intval($player[$countField])) : 0;
$enable = ($op == 1 || $op == 2) && $remaining > 0;
$autoFlag = $enable ? 1 : 0;
if(!$_pm['mysql']->query("UPDATE player SET autofitflag={$autoFlag} WHERE id={$uid}") ||
	!$_pm['mysql']->query('COMMIT'))
{
	$_pm['mysql']->query('ROLLBACK');
	die('0');
}

if(defined('MEM_USER_KEY')) $_pm['mem']->del(MEM_USER_KEY);
if($enable)
{
	$_SESSION[$expTypeKey] = 1;
	$_SESSION[$wayKey] = $op == 1 ? 'yb' : 'money';
	$_SESSION[$fightTimeKey] = $op == 1 ? 3 : 4;
	if(isset($_SESSION['fight'.$uid]) && is_array($_SESSION['fight'.$uid])) {
		$_SESSION['fight'.$uid]['fight_mode'] = $op == 1 ? 'yb' : 'money';
	}
}
else
{
	$_SESSION[$expTypeKey] = 0;
	$_SESSION[$wayKey] = '';
	$_SESSION[$fightTimeKey] = 5;
	if(isset($_SESSION['fight'.$uid]) && is_array($_SESSION['fight'.$uid])) {
		$_SESSION['fight'.$uid]['fight_mode'] = 'manual';
	}
}
echo $remaining;

/*
require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
if(isset($_SESSION['team_id'])){
	$s=new socketmsg();
	$team=new team($_SESSION['team_id'],$s);

	$teamInfo=$team->getTeamInfo();

	$team->clearTeamState();
}
*/
$_pm['mem']->memClose();
//####################
?>
