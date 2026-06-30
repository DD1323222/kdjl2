<?php
/**
 * Upgrade one learned pet skill by consuming one matching upgrade scroll.
 */
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');
require_once(dirname(__FILE__).'/skill_common.php');
secStart($_pm['mem']);

$uid = intval($_SESSION['id']);
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$bid = isset($_REQUEST['pid']) ? intval($_REQUEST['pid']) : 0;
if ($uid < 1 || $id < 1 || $bid < 1) die('0');

if (!is_array(getLock($uid))) skillFlowFail('0');
$db = $_pm['mysql'];
$pet = $db->getOneRecord(
	'SELECT * FROM userbb WHERE uid='.$uid.' AND id='.$bid.' FOR UPDATE'
);
if (!is_array($pet) ||
	(intval($pet['muchang']) != 0 && intval($pet['muchang']) != 1) || intval($pet['tgflag']) != 0)
{
	skillFlowFail('0');
}

$skillConfig = $db->getOneRecord('SELECT * FROM skillsys WHERE id='.$id);
if (!is_array($skillConfig)) skillFlowFail('0');

$skillRows = $db->getRecords(
	'SELECT * FROM skill WHERE bid='.$bid.' AND sid='.$id.' ORDER BY id LIMIT 2 FOR UPDATE'
);
if (!is_array($skillRows) || count($skillRows) != 1) skillFlowFail('0');
$skill = $skillRows[0];
$currentLevel = intval($skill['level']);
if ($currentLevel >= 10) skillFlowFail('4');
if ($currentLevel < 1) skillFlowFail('0');

$requiredLevel = intval(skillFlowValue($skillConfig['requires'], $currentLevel));
if (intval($pet['level']) < $requiredLevel) skillFlowFail('3');

$bookPid = intval($skillConfig['vary']) == 4 ? 1666 : 733;
$book = $db->getOneRecord(
	'SELECT id FROM userbag WHERE uid='.$uid.' AND pid='.$bookPid.
	' AND sums>0 AND zbing=0 AND bsum=0 AND psum=0 AND pyb=0 ORDER BY id LIMIT 1 FOR UPDATE'
);
if (!is_array($book)) skillFlowFail('2');

$storedEffect = skillFlowValue($skillConfig['imgeft'], $currentLevel);
if (!skillFlowApplyPermanent($uid, $pet, $storedEffect, $storedEffect))
{
	skillFlowFail('0');
}

$sql = 'UPDATE skill SET level='.($currentLevel + 1).
	',value='.$db->quote(skillFlowValue($skillConfig['ackvalue'], $currentLevel)).
	',plus='.$db->quote(skillFlowValue($skillConfig['plus'], $currentLevel)).
	',uhp='.intval(skillFlowValue($skillConfig['uhp'], $currentLevel)).
	',ump='.intval(skillFlowValue($skillConfig['ump'], $currentLevel)).
	',img='.$db->quote($storedEffect).
	' WHERE id='.intval($skill['id']).' AND bid='.$bid.' AND sid='.$id.' AND level='.$currentLevel;
if (!$db->query($sql) || mysql_affected_rows($db->getConn()) != 1)
{
	skillFlowFail('0');
}

$sql = 'UPDATE userbag SET sums=sums-1 WHERE uid='.$uid.' AND id='.intval($book['id']).' AND sums>=1';
if (!$db->query($sql) || mysql_affected_rows($db->getConn()) != 1)
{
	skillFlowFail('2');
}

skillFlowCommit();
die('1');
?>
