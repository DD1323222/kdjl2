<?php
require_once(dirname(__FILE__).'/../config/config.game.php');
require_once(dirname(__FILE__).'/security_common.php');
header('Content-Type: text/plain; charset=UTF-8');

$post = array();
foreach($_POST as $key => $value)
{
	$post[$key] = is_array($value) ? '' : (string)$value;
}
$passportInput = isset($post['passport']) ? trim($post['passport']) : '';
$answerInput = isset($post['an']) ? $post['an'] : '';
$questionInput = isset($post['qu']) ? trim($post['qu']) : '';
$passwordInput = isset($post['pass']) ? $post['pass'] : '';
$answerCheckInput = isset($post['anS']) ? $post['anS'] : '';
$newPasswordInput = isset($post['newPass']) ? $post['newPass'] : '';
$hasAnswerInput = array_key_exists('an', $post);
$hasQuestionInput = array_key_exists('qu', $post);
$hasPasswordInput = array_key_exists('pass', $post);
$hasAnswerCheckInput = array_key_exists('anS', $post);
$hasNewPasswordInput = array_key_exists('newPass', $post);

if($passportInput === '') die('无此用户');
$userInfo = kdjlMbUserCheck($passportInput);
$userId = intval($userInfo['id']);
$today = intval(date('Ymd'));
$protection = $_pm['mysql']->getOneRecord('SELECT id,question,answer,startTime,count FROM PasswordProtection WHERE player_id='.$userId.' ORDER BY id LIMIT 1');
if(is_array($protection))
{
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
}
if(kdjlProtectionAttemptCount($userId) >= 3) die('密保答案每天只能尝试3次！');

if(!$hasAnswerInput && !$hasQuestionInput && !$hasPasswordInput && !$hasAnswerCheckInput && !$hasNewPasswordInput)
{
	if(!is_array($protection)) die('OK1');
	die('OK2|'.(string)$protection['question']);
}

if($hasAnswerInput && $hasQuestionInput && $hasPasswordInput && !$hasAnswerCheckInput && !$hasNewPasswordInput)
{
	if(!kdjlProtectionTextValid($questionInput, 200) || !kdjlProtectionTextValid($answerInput, 200)) die('密保问题或答案格式不正确');
	if(strlen($passwordInput) < 1 || strlen($passwordInput) > 128) die('密码格式不正确');
	if(!$_pm['mysql']->query('START TRANSACTION')) die('服务器繁忙，请稍后再试');
	$lockedUser = $_pm['mysql']->getOneRecord('SELECT secret FROM player WHERE id='.$userId.' FOR UPDATE');
	if(!is_array($lockedUser) || !kdjlProtectionStringEquals((string)$lockedUser['secret'], md5($passwordInput)))
	{
		$_pm['mysql']->query('ROLLBACK');
		kdjlProtectionRegisterFailure($userId);
		kdjlMbRegisterDbFailure($userId, $today);
		die('密码错误');
	}
	$existing = $_pm['mysql']->getOneRecord('SELECT id FROM PasswordProtection WHERE player_id='.$userId.' ORDER BY id LIMIT 1 FOR UPDATE');
	if(is_array($existing))
	{
		$_pm['mysql']->query('ROLLBACK');
		die('该用户已经设置');
	}
	$questionSql = $_pm['mysql']->escape($questionInput);
	$answerSql = $_pm['mysql']->escape(kdjlProtectionHashAnswer($answerInput));
	if(!$_pm['mysql']->query("INSERT INTO PasswordProtection(player_id,question,answer,startTime,count) VALUES({$userId},'{$questionSql}','{$answerSql}',{$today},0)") ||
		mysql_affected_rows($_pm['mysql']->getConn()) !== 1 || !$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		die('密保设置失败，请稍后再试');
	}
	kdjlProtectionClearFailures($userId);
	die('OK设置成功');
}

if($hasAnswerCheckInput && !$hasNewPasswordInput && !$hasAnswerInput && !$hasQuestionInput && !$hasPasswordInput)
{
	if(!is_array($protection)) die('该用户未设置密保');
	if($answerCheckInput === '') die('密保答案格式不正确');
	if(!kdjlProtectionAnswerMatches($protection['answer'], $answerCheckInput))
	{
		kdjlProtectionRegisterFailure($userId);
		kdjlMbRegisterDbFailure($userId, $today);
		die('密保答案不正确');
	}
	$answerSql = $_pm['mysql']->escape(
		kdjlProtectionAnswerNeedsUpgrade($protection['answer']) ? kdjlProtectionHashAnswer($answerCheckInput) : (string)$protection['answer']
	);
	if(!$_pm['mysql']->query("UPDATE PasswordProtection SET answer='{$answerSql}',startTime={$today},count=0 WHERE id=".intval($protection['id'])))
		die('服务器繁忙，请稍后再试');
	kdjlProtectionClearFailures($userId);
	die('OK');
}

if($hasAnswerCheckInput && $hasNewPasswordInput && !$hasAnswerInput && !$hasQuestionInput && !$hasPasswordInput)
{
	if($answerCheckInput === '') die('密保答案格式不正确');
	if(strlen($newPasswordInput) < 6 || strlen($newPasswordInput) > 20) die('新密码长度应为6到20位');
	if(!$_pm['mysql']->query('START TRANSACTION')) die('服务器繁忙，请稍后再试');
	$lockedProtection = $_pm['mysql']->getOneRecord('SELECT id,answer FROM PasswordProtection WHERE player_id='.$userId.' ORDER BY id LIMIT 1 FOR UPDATE');
	if(!is_array($lockedProtection))
	{
		$_pm['mysql']->query('ROLLBACK');
		die('该用户未设置密保');
	}
	if(!kdjlProtectionAnswerMatches($lockedProtection['answer'], $answerCheckInput))
	{
		$_pm['mysql']->query('ROLLBACK');
		kdjlProtectionRegisterFailure($userId);
		kdjlMbRegisterDbFailure($userId, $today);
		die('密保答案不正确');
	}
	$answerSql = $_pm['mysql']->escape(kdjlProtectionHashAnswer($answerCheckInput));
	$newSecret = md5($newPasswordInput);
	if(!$_pm['mysql']->query("UPDATE PasswordProtection SET answer='{$answerSql}',startTime={$today},count=0 WHERE id=".intval($lockedProtection['id'])) ||
		!$_pm['mysql']->query("UPDATE player SET secret='{$newSecret}' WHERE id={$userId}") || !$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		die('密码修改失败，请稍后再试');
	}
	$_pm['mem']->del($userId);
	kdjlProtectionClearFailures($userId);
	die('OK');
}

die('异常！');

function kdjlMbUserCheck($passport)
{
	global $_pm;
	$passportSql = $_pm['mysql']->escape($passport);
	$user = $_pm['mysql']->getOneRecord("SELECT id,secret FROM player WHERE name='{$passportSql}' LIMIT 1");
	if(!is_array($user)) die('无此用户');
	return $user;
}

function kdjlMbRegisterDbFailure($userId, $today)
{
	global $_pm;
	$userId = intval($userId);
	$today = intval($today);
	$_pm['mysql']->query("UPDATE PasswordProtection SET count=IF(startTime={$today},LEAST(count+1,127),1),startTime={$today} WHERE player_id={$userId}");
}
?>
