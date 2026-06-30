<?php
/**
*@Usage: study skill for user bb.
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
$skillConfig = $db->getOneRecord(
	'SELECT s.*,p.requires AS book_requires FROM skillsys AS s '.
	'INNER JOIN props AS p ON p.id=s.pid WHERE s.id='.$id
);
if (!is_array($pet) || !is_array($skillConfig) ||
	(intval($pet['muchang']) != 0 && intval($pet['muchang']) != 1) || intval($pet['tgflag']) != 0)
{
	skillFlowFail('0');
}

$skillRows = $db->getRecords(
	'SELECT id FROM skill WHERE bid='.$bid.' AND sid='.$id.' FOR UPDATE'
);
if ($skillRows === false) skillFlowFail('0');
$listed = false;
foreach (explode(',', strval($pet['skillist'])) as $entry)
{
	$parts = explode(':', $entry);
	if (isset($parts[0]) && intval($parts[0]) == $id)
	{
		$listed = true;
		break;
	}
}
if ((is_array($skillRows) && count($skillRows) > 0) || $listed) skillFlowFail('10');

$book = $db->getOneRecord(
	'SELECT id,pid,sums FROM userbag WHERE uid='.$uid.' AND pid='.intval($skillConfig['pid']).
	' AND sums>0 AND zbing=0 AND bsum=0 AND psum=0 AND pyb=0 ORDER BY id LIMIT 1 FOR UPDATE'
);
if (!is_array($book)) skillFlowFail('2');

$requiredLevel = 0;
$exclusivePetId = 0;
foreach (explode(',', strval($skillConfig['book_requires'])) as $requirement)
{
	$parts = explode(':', trim($requirement), 2);
	if (count($parts) != 2) continue;
	if ($parts[0] == 'lv') $requiredLevel = intval($parts[1]);
	else if ($parts[0] == 'only') $exclusivePetId = intval($parts[1]);
}
if (intval($pet['level']) < $requiredLevel) skillFlowFail('3');
if (intval($skillConfig['wx']) != 0 && intval($pet['wx']) != intval($skillConfig['wx']))
{
	skillFlowFail('4');
}
if ($exclusivePetId > 0)
{
	$templates = $db->getRecords(
		'SELECT id,remakelevel,remakeid,remakepid FROM bb WHERE name='.$db->quote($pet['name']).
		' AND wx='.intval($pet['wx']).' ORDER BY id'
	);
	if ($templates === false) skillFlowFail('0');
	if (!is_array($templates)) skillFlowFail('11');
	$matchedTemplateId = intval($templates[0]['id']);
	foreach ($templates as $template)
	{
		if (strval($template['remakelevel']) == strval($pet['remakelevel']) &&
			strval($template['remakeid']) == strval($pet['remakeid']) &&
			strval($template['remakepid']) == strval($pet['remakepid']))
		{
			$matchedTemplateId = intval($template['id']);
			break;
		}
	}
	if ($matchedTemplateId != $exclusivePetId)
	{
		skillFlowFail('11');
	}
}

$storedEffect = skillFlowValue($skillConfig['imgeft'], 0);
if (!skillFlowApplyPermanent($uid, $pet, $storedEffect, $storedEffect))
{
	skillFlowFail('0');
}
$sql = 'INSERT INTO skill (bid,sid,name,level,vary,wx,value,plus,img,uhp,ump) VALUES ('.
	$bid.','.$id.','.$db->quote($skillConfig['name']).',1,'.
	$db->quote($skillConfig['vary']).','.intval($skillConfig['wx']).','.
	$db->quote(skillFlowValue($skillConfig['ackvalue'], 0)).','.
	$db->quote(skillFlowValue($skillConfig['plus'], 0)).','.$db->quote($storedEffect).','.
	intval(skillFlowValue($skillConfig['uhp'], 0)).','.intval(skillFlowValue($skillConfig['ump'], 0)).')';
if (!$db->query($sql) || mysql_affected_rows($db->getConn()) != 1)
{
	skillFlowFail('0');
}

$newEntry = $id.':1';
if (trim(strval($pet['skillist'])) == '')
{
	$sql = 'UPDATE userbb SET skillist='.$db->quote($newEntry).' WHERE uid='.$uid.' AND id='.$bid;
}
else
{
	$sql = 'UPDATE userbb SET skillist=CONCAT(skillist,'.$db->quote(',').','.$db->quote($newEntry).')'.
		' WHERE uid='.$uid.' AND id='.$bid;
}
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
