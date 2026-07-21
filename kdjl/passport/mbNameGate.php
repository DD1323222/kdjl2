<?php
require_once(dirname(__FILE__).'/../config/config.game.php');
require_once(dirname(__FILE__).'/security_common.php');
require_once(dirname(__FILE__).'/../socketChat/badWord.php');
header('Content-Type: text/plain; charset=UTF-8');

$post = array();
foreach($_POST as $key => $value)
{
	$post[$key] = is_array($value) ? '' : (string)$value;
}
$passportInput = isset($post['passport']) ? trim($post['passport']) : '';
$answerCheckInput = isset($post['anS']) ? $post['anS'] : '';
$newName = isset($post['newName']) ? trim($post['newName']) : '';

if($passportInput === '') die('无此用户');
$user = kdjlMbNameUserCheck($passportInput);
$userId = intval($user['id']);
$today = intval(date('Ymd'));
$protection = $_pm['mysql']->getOneRecord(
	'SELECT id,answer,startTime,count FROM PasswordProtection WHERE player_id='.$userId.' ORDER BY id LIMIT 1'
);
if(!is_array($protection)) die('该用户未设置密保');
if(intval($protection['startTime']) !== $today)
{
	$_pm['mysql']->query('UPDATE PasswordProtection SET startTime='.$today.',count=0 WHERE player_id='.$userId);
	$protection['startTime'] = $today;
	$protection['count'] = 0;
}
else if(intval($protection['count']) >= 3)
{
	die('密保答案每天只能尝试3次！');
}
if(kdjlProtectionAttemptCount($userId) >= 3) die('密保答案每天只能尝试3次！');
if($answerCheckInput === '' || $newName === '') die('异常！');
if(!kdjlValidNickname($newName)) die('输入的名称格式或长度不正确！');
$blockedWord = kdjlFindBlockedWord($newName);
if($blockedWord !== false) die('输入的角色名中('.$blockedWord.')为禁止使用的词！');

$newNameSql = $_pm['mysql']->escape($newName);
$duplicate = $_pm['mysql']->getOneRecord("SELECT id FROM player WHERE nickname='{$newNameSql}' AND id<>{$userId} LIMIT 1");
if(is_array($duplicate)) die('你输入的名称已存在，请换个名称！');
if(!$_pm['mysql']->query('START TRANSACTION')) die('修改失败，请稍后再试！');

$lockedProtection = $_pm['mysql']->getOneRecord(
	'SELECT id,answer FROM PasswordProtection WHERE player_id='.$userId.' ORDER BY id LIMIT 1 FOR UPDATE'
);
if(!is_array($lockedProtection))
{
	$_pm['mysql']->query('ROLLBACK');
	die('该用户未设置密保');
}
if(!kdjlProtectionAnswerMatches($lockedProtection['answer'], $answerCheckInput))
{
	$_pm['mysql']->query('ROLLBACK');
	kdjlProtectionRegisterFailure($userId);
	kdjlMbNameRegisterDbFailure($userId, $today);
	die('密保答案不正确');
}

$currentNameRow = $_pm['mysql']->getOneRecord("SELECT nickname FROM player WHERE id={$userId} FOR UPDATE");
if(!is_array($currentNameRow) || !isset($currentNameRow['nickname']))
{
	$_pm['mysql']->query('ROLLBACK');
	die('修改失败，请稍后再试！');
}
$oldName = (string)$currentNameRow['nickname'];
$oldNameSql = $_pm['mysql']->escape($oldName);
$friendOwnerRows = $_pm['mysql']->getRecords("SELECT id FROM player WHERE FIND_IN_SET('{$oldNameSql}',friendlist)>0 FOR UPDATE");
if(!is_array($friendOwnerRows)) $friendOwnerRows = array();
$teamRows = $_pm['mysql']->getRecords(
	"SELECT DISTINCT team.id,team.inmap FROM team LEFT JOIN team_members ON team_members.team_id=team.id ".
	"WHERE team.creator={$userId} OR team_members.uid={$userId}"
);
if(!is_array($teamRows)) $teamRows = array();

if(!$_pm['mysql']->query("UPDATE player SET nickname='{$newNameSql}' WHERE id={$userId}"))
{
	$errorNumber = mysql_errno($_pm['mysql']->getConn());
	$_pm['mysql']->query('ROLLBACK');
	die($errorNumber == 1062 ? '你输入的名称已存在，请换个名称！' : '修改失败，请稍后再试！');
}
$answerSql = $_pm['mysql']->escape(kdjlProtectionHashAnswer($answerCheckInput));
if(!$_pm['mysql']->query("UPDATE userbb SET username='{$newNameSql}' WHERE uid={$userId}") ||
	!$_pm['mysql']->query("UPDATE team_members SET nickname='{$newNameSql}' WHERE uid={$userId}") ||
	!$_pm['mysql']->query("UPDATE team SET name='{$newNameSql}' WHERE creator={$userId}") ||
	!$_pm['mysql']->query("UPDATE player SET friendlist=TRIM(BOTH ',' FROM REPLACE(CONCAT(',',COALESCE(friendlist,''),','),',{$oldNameSql},',',{$newNameSql},')) WHERE FIND_IN_SET('{$oldNameSql}',friendlist)>0") ||
	!$_pm['mysql']->query("UPDATE blacklist SET list=TRIM(BOTH ',' FROM REPLACE(CONCAT(',',COALESCE(list,''),','),',{$oldNameSql},',',{$newNameSql},')) WHERE FIND_IN_SET('{$oldNameSql}',list)>0") ||
	!$_pm['mysql']->query("UPDATE chat_login_auth SET nickname='{$newNameSql}' WHERE uid={$userId}") ||
	!$_pm['mysql']->query("UPDATE PasswordProtection SET answer='{$answerSql}',startTime={$today},count=0 WHERE id=".intval($lockedProtection['id'])) ||
	!$_pm['mysql']->query('COMMIT'))
{
	$_pm['mysql']->query('ROLLBACK');
	die('修改失败，请稍后再试！');
}

kdjlProtectionClearFailures($userId);
$_pm['mem']->del($userId);
$_pm['mem']->del($userId.'bb');
foreach($friendOwnerRows as $friendOwnerRow)
{
	if(is_array($friendOwnerRow) && isset($friendOwnerRow['id'])) $_pm['mem']->del(intval($friendOwnerRow['id']));
}
foreach($teamRows as $teamRow)
{
	if(!is_array($teamRow)) continue;
	if(isset($teamRow['id'])) $_pm['mem']->del('pm_team_'.intval($teamRow['id']));
	if(isset($teamRow['inmap']))
	{
		$teamMapId = intval($teamRow['inmap']);
		$_pm['mem']->del('pm_list_team_'.$teamMapId);
		$_pm['mem']->del('pm_list_team_'.$teamMapId.'_time');
	}
}
$blackRows = $_pm['mysql']->getRecords('SELECT uid,list FROM blacklist ORDER BY Id');
$blackCache = array();
if(is_array($blackRows)) foreach($blackRows as $blackRow)
{
	if(!is_array($blackRow) || !isset($blackRow['uid'])) continue;
	$blackUid = intval($blackRow['uid']);
	if($blackUid < 1) continue;
	$blackNames = isset($blackCache[$blackUid]) && $blackCache[$blackUid] !== '' ? explode(',', $blackCache[$blackUid]) : array();
	foreach(explode(',', isset($blackRow['list']) ? $blackRow['list'] : '') as $blackName)
	{
		$blackName = trim($blackName);
		if($blackName !== '' && !in_array($blackName, $blackNames, true)) $blackNames[] = $blackName;
	}
	$blackCache[$blackUid] = implode(',', $blackNames);
}
$_pm['mem']->del('db_blacklist');
$_pm['mem']->set(array('k'=>'db_blacklist','v'=>$blackCache));
if(defined('MEM_USER_LIST')) $_pm['mem']->del(MEM_USER_LIST);
if(isset($_SESSION['id']) && intval($_SESSION['id']) === $userId) $_SESSION['nickname'] = $newName;
die('OK');

function kdjlMbNameUserCheck($passport)
{
	global $_pm;
	$passportSql = $_pm['mysql']->escape($passport);
	$user = $_pm['mysql']->getOneRecord("SELECT id,nickname FROM player WHERE name='{$passportSql}' LIMIT 1");
	if(!is_array($user)) die('无此用户');
	return $user;
}

function kdjlMbNameRegisterDbFailure($userId, $today)
{
	global $_pm;
	$userId = intval($userId);
	$today = intval($today);
	$_pm['mysql']->query("UPDATE PasswordProtection SET count=IF(startTime={$today},LEAST(count+1,127),1),startTime={$today} WHERE player_id={$userId}");
}
?>
