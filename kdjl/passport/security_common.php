<?php
function kdjlProtectionHashAnswer($answer)
{
	$salt = substr(hash('sha256', uniqid('', true).mt_rand().getmypid()), 0, 32);
	return 'sha256:'.$salt.':'.hash('sha256', $salt.'|'.(string)$answer);
}

function kdjlProtectionStringEquals($left, $right)
{
	$left = (string)$left;
	$right = (string)$right;
	$length = strlen($left);
	if($length !== strlen($right)) return false;
	$diff = 0;
	for($i=0; $i<$length; $i++) $diff |= ord($left[$i]) ^ ord($right[$i]);
	return $diff === 0;
}

function kdjlProtectionAnswerMatches($stored, $answer)
{
	$stored = (string)$stored;
	if(preg_match('/^sha256:([0-9a-f]{32}):([0-9a-f]{64})$/D', $stored, $matches) === 1)
	{
		return kdjlProtectionStringEquals($matches[2], hash('sha256', $matches[1].'|'.(string)$answer));
	}
	if(preg_match('/^sha256:([0-9a-f]{64})$/D', $stored, $matches) === 1)
		return kdjlProtectionStringEquals($matches[1], hash('sha256', (string)$answer));
	if(strpos($stored, 'sha256:') === 0) return false;
	return kdjlProtectionStringEquals($stored, (string)$answer);
}

function kdjlProtectionAnswerNeedsUpgrade($stored)
{
	return preg_match('/^sha256:[0-9a-f]{32}:[0-9a-f]{64}$/D', (string)$stored) !== 1;
}

function kdjlProtectionTextValid($value, $maxBytes=200)
{
	if(!is_string($value) || $value === '' || strlen($value) > intval($maxBytes)) return false;
	if(preg_match('//u', $value) !== 1) return false;
	return preg_match('/[\x00-\x1F\x7F]/', $value) !== 1;
}

function kdjlProtectionAttemptKeys($userId)
{
	$userId = intval($userId);
	$remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '';
	$prefix = 'kdjl_mb_try_'.date('Ymd').'_'.$userId.'_';
	return array($prefix.'all', $prefix.md5($remoteAddr));
}

function kdjlProtectionAttemptCount($userId)
{
	global $_pm;
	$keys = kdjlProtectionAttemptKeys($userId);
	$count = 0;
	foreach($keys as $key)
	{
		$sessionKey = 'mb_try_'.$key;
		if(isset($_SESSION[$sessionKey])) $count = max($count, max(0, intval($_SESSION[$sessionKey])));
	}
	$handle = isset($_pm['mem']) && is_object($_pm['mem']) && method_exists($_pm['mem'], 'getHandle') ? $_pm['mem']->getHandle() : false;
	if(!is_object($handle)) return $count;
	foreach($keys as $key)
	{
		$current = @$handle->get($key);
		if($current === false)
		{
			@$handle->add($key, '0', 0, 86400);
			$current = 0;
		}
		$count = max($count, max(0, intval($current)));
	}
	return $count;
}

function kdjlProtectionRegisterFailure($userId)
{
	global $_pm;
	$keys = kdjlProtectionAttemptKeys($userId);
	foreach($keys as $key)
	{
		$sessionKey = 'mb_try_'.$key;
		$_SESSION[$sessionKey] = isset($_SESSION[$sessionKey]) ? intval($_SESSION[$sessionKey])+1 : 1;
	}
	$handle = isset($_pm['mem']) && is_object($_pm['mem']) && method_exists($_pm['mem'], 'getHandle') ? $_pm['mem']->getHandle() : false;
	if(is_object($handle))
	{
		foreach($keys as $key)
		{
			$current = @$handle->get($key);
			if($current === false) @$handle->add($key, '1', 0, 86400);
			else if(@$handle->increment($key, 1) === false) @$handle->set($key, strval(intval($current)+1), 0, 86400);
		}
	}
	return kdjlProtectionAttemptCount($userId);
}

function kdjlProtectionClearFailures($userId)
{
	global $_pm;
	$keys = kdjlProtectionAttemptKeys($userId);
	foreach($keys as $key) unset($_SESSION['mb_try_'.$key]);
	$handle = isset($_pm['mem']) && is_object($_pm['mem']) && method_exists($_pm['mem'], 'getHandle') ? $_pm['mem']->getHandle() : false;
	if(is_object($handle)) foreach($keys as $key) @$handle->delete($key);
}

function kdjlReplaceChatLoginAuth($db, $data)
{
	if(!is_object($db) || !is_array($data)) return false;
	$uid = isset($data['uid']) ? intval($data['uid']) : 0;
	$username = isset($data['username']) && !is_array($data['username']) ? (string)$data['username'] : '';
	$nickname = isset($data['nickname']) && !is_array($data['nickname']) ? (string)$data['nickname'] : '';
	$sid = isset($data['sid']) && !is_array($data['sid']) ? (string)$data['sid'] : '';
	if($uid < 1 || $username === '' || $nickname === '' || $sid === '') return false;

	$usernameSql = $db->escape($username);
	$nicknameSql = $db->escape($nickname);
	$sidSql = $db->escape($sid);
	$ipSql = $db->escape(isset($data['u_ip']) && !is_array($data['u_ip']) ? (string)$data['u_ip'] : '');
	$macSql = $db->escape(isset($data['mac_addr']) && !is_array($data['mac_addr']) ? (string)$data['mac_addr'] : '');
	$guildId = isset($data['guild_id']) ? max(0, intval($data['guild_id'])) : 0;
	$teamId = isset($data['team_id']) ? max(0, intval($data['team_id'])) : 0;
	$lockTime = isset($data['lock_time']) ? max(0, intval($data['lock_time'])) : 0;
	$admin = isset($data['admin']) ? intval($data['admin']) : 0;
	$vip = isset($data['vip']) ? max(0, intval($data['vip'])) : 0;
	$isOnline = !empty($data['is_online']) ? 1 : 0;

	if(!$db->query('START TRANSACTION')) return false;
	$lockedPlayer = $db->getOneRecord('SELECT id FROM player WHERE id='.$uid.' FOR UPDATE');
	if(!is_array($lockedPlayer) ||
		!$db->query("DELETE FROM chat_login_auth WHERE uid={$uid} OR username='{$usernameSql}' OR sid='{$sidSql}'"))
	{
		$db->query('ROLLBACK');
		return false;
	}

	$sql = "INSERT INTO chat_login_auth(uid,username,nickname,sid,guild_id,team_id,lock_time,admin,vip,u_ip,is_online,mac_addr) VALUES(".
		"{$uid},'{$usernameSql}','{$nicknameSql}','{$sidSql}',{$guildId},{$teamId},{$lockTime},{$admin},{$vip},'{$ipSql}',{$isOnline},'{$macSql}')";
	if(!$db->query($sql) || mysql_affected_rows($db->getConn()) !== 1 || !$db->query('COMMIT'))
	{
		$db->query('ROLLBACK');
		return false;
	}
	return true;
}
?>
