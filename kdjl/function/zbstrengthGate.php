<?php
/**
 * Validate an equipment or auxiliary item before adding it to the furnace.
 */
require_once('../config/config.game.php');
@session_start();
secStart($_pm['mem']);

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
$pid = (isset($_REQUEST['pid']) && !is_array($_REQUEST['pid'])) ? intval($_REQUEST['pid']) : 0;
$bagId = (isset($_REQUEST['bid']) && !is_array($_REQUEST['bid'])) ? intval($_REQUEST['bid']) : 0;
if($uid < 1 || $pid < 1) die('0');

$propsById = kdjlSafeMemValue($_pm['mem']->get('db_propsid'), array());
if(!is_array($propsById) || !isset($propsById[$pid]) || !is_array($propsById[$pid])) die('0');
$props = $propsById[$pid];
$varyname = isset($props['varyname']) ? intval($props['varyname']) : 0;

if($varyname == 9)
{
	if(!isset($props['plusflag']) || intval($props['plusflag']) != 1) die('1');
	if($bagId < 1) die('0');
	$bag = $_pm['mysql']->getOneRecord(
		'SELECT id FROM userbag WHERE id='.$bagId.' AND uid='.$uid.' AND pid='.$pid.
		' AND sums>0 AND zbing=0 AND (cantrade IS NULL OR cantrade<>3)'
	);
	if(!is_array($bag)) die('0');
	echo '&pid='.$pid.'&pids='.(isset($_SESSION['pids'.$uid]) ? intval($_SESSION['pids'.$uid]) : 0).'&bid='.$bagId;
	exit;
}

if($varyname == 11)
{
	$bag = $_pm['mysql']->getOneRecord(
		'SELECT id FROM userbag WHERE uid='.$uid.' AND pid='.$pid.
		' AND sums>0 AND zbing=0 AND (cantrade IS NULL OR cantrade<>3) ORDER BY id LIMIT 1'
	);
	if(!is_array($bag)) die('0');
	echo '&pids='.$pid.'&pid='.(isset($_SESSION['pid'.$uid]) ? intval($_SESSION['pid'.$uid]) : 0).
		'&bid='.(isset($_SESSION['bid'.$uid]) ? intval($_SESSION['bid'.$uid]) : 0);
	exit;
}

die('0');
?>
