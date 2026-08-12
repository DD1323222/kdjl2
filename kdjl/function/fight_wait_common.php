<?php
if (!function_exists('kdjlFightWaitCacheValue')) {
	function kdjlFightWaitCacheValue($raw, $default)
	{
		if ($raw === false || $raw === null || $raw === '') {
			return $default;
		}
		if (!is_string($raw)) {
			return $raw;
		}
		$parsed = @unserialize($raw);
		if ($parsed === false && $raw !== serialize(false)) {
			return $default;
		}
		return $parsed;
	}
}

if (!function_exists('kdjlReleaseTeamFightLock')) {
	function kdjlReleaseTeamFightLock()
	{
		global $_pm;
		if (empty($GLOBALS['kdjl_team_fight_lock_name'])) {
			return;
		}
		$lockName = $GLOBALS['kdjl_team_fight_lock_name'];
		unset($GLOBALS['kdjl_team_fight_lock_name']);
		if (!isset($_pm['mysql'])) {
			return;
		}
		$lockNameSql = $_pm['mysql']->escape($lockName);
		$_pm['mysql']->getOneRecord("SELECT RELEASE_LOCK('{$lockNameSql}') AS released");
	}
}

if (!function_exists('kdjlAcquireTeamFightLock')) {
	function kdjlAcquireTeamFightLock($teamId, $timeout)
	{
		global $_pm;
		$teamId = intval($teamId);
		$timeout = max(0, min(30, intval($timeout)));
		if ($teamId < 1 || !isset($_pm['mysql'])) {
			return false;
		}
		$lockName = 'kdjl_teamfight_'.$teamId;
		if (isset($GLOBALS['kdjl_team_fight_lock_name'])) {
			return $GLOBALS['kdjl_team_fight_lock_name'] === $lockName;
		}
		$lockNameSql = $_pm['mysql']->escape($lockName);
		$lock = $_pm['mysql']->getOneRecord(
			"SELECT GET_LOCK('{$lockNameSql}',{$timeout}) AS locked"
		);
		if (!is_array($lock) || !isset($lock['locked']) || intval($lock['locked']) !== 1) {
			return false;
		}
		$GLOBALS['kdjl_team_fight_lock_name'] = $lockName;
		if (empty($GLOBALS['kdjl_team_fight_shutdown_registered'])) {
			$GLOBALS['kdjl_team_fight_shutdown_registered'] = true;
			register_shutdown_function('kdjlReleaseTeamFightLock');
		}
		return true;
	}
}

if (!function_exists('kdjlEnsureTgt31CrystalPaid')) {
	function kdjlEnsureTgt31CrystalPaid($uid)
	{
		global $_pm;
		$uid = intval($uid);
		if ($uid < 1 || !isset($_pm['mysql'], $_pm['mem'])) {
			return false;
		}
		$cacheKey = 'tg31check_'.$uid;
		if (kdjlFightWaitCacheValue($_pm['mem']->get($cacheKey), 0) == 1) {
			return true;
		}

		require_once(dirname(__FILE__).'/../sec/dblock_fun.php');
		if (!getScopedLock('tgt31', $uid, 5)) {
			return false;
		}

		$paid = false;
		if (kdjlFightWaitCacheValue($_pm['mem']->get($cacheKey), 0) == 1) {
			$paid = true;
		}
		else if ($_pm['mysql']->query('START TRANSACTION')) {
			$charged = $_pm['mysql']->query(
				'UPDATE player_ext SET sj=sj-200 WHERE uid='.$uid.' AND sj>=200'
			) && mysql_affected_rows($_pm['mysql']->getConn()) == 1;
			if ($charged && $_pm['mem']->set(array('k'=>$cacheKey, 'v'=>1))) {
				if ($_pm['mysql']->query('COMMIT')) {
					$paid = true;
				}
				else {
					$_pm['mysql']->query('ROLLBACK');
				}
			}
			else {
				$_pm['mysql']->query('ROLLBACK');
			}
		}

		realseLock();
		return $paid;
	}
}

if (!function_exists('kdjlIsSoloTeamDungeonMap')) {
	function kdjlIsSoloTeamDungeonMap($mapId)
	{
		return in_array(intval($mapId), array(128, 129, 130), true);
	}
}

if (!function_exists('kdjlEnsureSoloTeamDungeonTeam')) {
	function kdjlEnsureSoloTeamDungeonTeam($uid, $mapId, $team, $socket)
	{
		$uid = intval($uid);
		$mapId = intval($mapId);
		$teamId = isset($_SESSION['team_id']) ? intval($_SESSION['team_id']) : 0;
		if ($teamId > 0) {
			return array('team_id'=>$teamId, 'team'=>$team, 'created'=>false);
		}
		if ($uid < 1 || !kdjlIsSoloTeamDungeonMap($mapId) || !is_object($team)) {
			return false;
		}

		$created = $team->createTeam();
		$teamId = isset($_SESSION['team_id']) ? intval($_SESSION['team_id']) : 0;
		if ($created !== true || $teamId < 1) {
			return false;
		}

		$team = new team($teamId, $socket);
		if ($team->checkMyTeam() === false ||
			!$team->setTeamState(array('team_select_map'=>$mapId))) {
			$team->disbandTeam(false);
			return false;
		}
		$_SESSION['team_inmap'] = $mapId;
		return array('team_id'=>$teamId, 'team'=>$team, 'created'=>true);
	}
}

if (!function_exists('kdjlStartTeamDungeonEntry')) {
	function kdjlStartTeamDungeonEntry($uid, $teamId, $team, $mapId, $crystalCost, $chargeCrystal)
	{
		global $_pm;
		$uid = intval($uid);
		$teamId = intval($teamId);
		$mapId = intval($mapId);
		$crystalCost = intval($crystalCost);
		$chargeCrystal = $chargeCrystal ? true : false;
		if ($uid < 1 || $teamId < 1 || $mapId < 1 || !is_object($team) ||
			!isset($_pm['mysql'], $_pm['mem'])) {
			return -1;
		}

		require_once(dirname(__FILE__).'/../sec/dblock_fun.php');
		if (!getScopedLock('teamfb', $teamId, 5)) {
			return -1;
		}

		$result = -1;
		$transactionActive = false;
		$teamStateStored = false;
		do {
			$teamState = $team->getTeamState();
			if (is_array($teamState) && !empty($teamState['fubensjoj'])) {
				$result = 1;
				break;
			}
			if ($chargeCrystal && $crystalCost < 1) {
				break;
			}
			if (!$_pm['mysql']->query('START TRANSACTION')) {
				break;
			}
			$transactionActive = true;

			if ($chargeCrystal) {
				$charged = $_pm['mysql']->query(
					'UPDATE player_ext SET sj=sj-'.$crystalCost.
					' WHERE uid='.$uid.' AND sj>='.$crystalCost
				) && mysql_affected_rows($_pm['mysql']->getConn()) == 1;
				if (!$charged) {
					$result = 0;
					break;
				}
			}

			$teamInfo = $team->getTeamInfo();
			if (!is_array($teamInfo) || !isset($teamInfo['members']) || !is_array($teamInfo['members'])) {
				break;
			}
			$today = date('Ymd');
			$activeMembers = 0;
			foreach ($teamInfo['members'] as $member) {
				if (!is_array($member) || !isset($member['state']) || intval($member['state']) != 1) {
					continue;
				}
				$memberUid = isset($member['uid']) ? intval($member['uid']) : 0;
				if ($memberUid < 1) {
					continue;
				}
				$entry = $_pm['mysql']->getOneRecord(
					'SELECT Id,lttime FROM fuben WHERE uid='.$memberUid.' AND inmap='.$mapId.
					' AND LEFT(lttime,8)="'.$today.'" ORDER BY Id LIMIT 1 FOR UPDATE'
				);
				if (is_array($entry) && isset($entry['Id'])) {
					$currentCount = max(0, intval(substr(strval($entry['lttime']), 8)));
					if ($currentCount >= 10) {
						$result = 2;
						break 2;
					}
					$newEntryTime = intval($today.strval($currentCount + 1));
					$stored = $_pm['mysql']->query(
						'UPDATE fuben SET lttime='.$newEntryTime.' WHERE Id='.intval($entry['Id']).
						' AND uid='.$memberUid.' AND inmap='.$mapId.' AND lttime='.intval($entry['lttime'])
					) && mysql_affected_rows($_pm['mysql']->getConn()) == 1;
				}
				else {
					$newEntryTime = intval($today.'1');
					$stored = $_pm['mysql']->query(
						'INSERT INTO fuben(uid,lttime,inmap) VALUES('.$memberUid.','.$newEntryTime.','.$mapId.')'
					) && mysql_affected_rows($_pm['mysql']->getConn()) == 1;
				}
				if (!$stored) {
					break 2;
				}
				$activeMembers++;
			}
			if ($activeMembers < 1) {
				break;
			}
			if (!$team->setTeamState(array('fubensjoj'=>1))) {
				break;
			}
			$teamStateStored = true;
			if (!$_pm['mysql']->query('COMMIT')) {
				break;
			}
			$transactionActive = false;
			$result = 1;
		} while (false);

		if ($transactionActive) {
			$_pm['mysql']->query('ROLLBACK');
		}
		if ($teamStateStored && $result !== 1) {
			$team->setTeamState(array('fubensjoj'=>0));
		}
		realseLock();
		if ($result === 1 && $chargeCrystal && defined('MEM_USER_KEY')) {
			$_pm['mem']->del(MEM_USER_KEY);
		}
		return $result;
	}
}

if (!function_exists('kdjlFightWaitEquipTime')) {
	function kdjlFightWaitEquipTime($bid)
	{
		global $_pm;
		$bid = intval($bid);
		if ($bid <= 0) {
			return 0;
		}
		if (function_exists('formatMsgEffect')) {
			formatMsgEffect($bid);
		}
		$str = kdjlFightWaitCacheValue($_pm['mem']->get('format_user_zhuangbei_'.$bid), '');
		if (!is_string($str) || $str === '') {
			return 0;
		}
		$time = 0;
		$arr = explode(',', $str);
		foreach ($arr as $v) {
			$kv = explode(':', $v);
			if (isset($kv[0]) && $kv[0] == 'time' && isset($kv[1])) {
				$time += intval($kv[1]);
			}
		}
		return $time > 0 ? $time : 0;
	}
}

if (!function_exists('kdjlFightWaitTitleTime')) {
	function kdjlFightWaitTitleTime()
	{
		global $_pm;
		$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		if ($uid <= 0) {
			return 0;
		}
		$title = $_pm['mysql']->getOneRecord("SELECT T_Card_to_Title.F_time FROM player_ext,T_Card_to_Title WHERE player_ext.now_Achievement_title = T_Card_to_Title.F_title_name AND player_ext.uid = '".$uid."'");
		if (!is_array($title) || !isset($title['F_time'])) {
			return 0;
		}
		$time = intval($title['F_time']);
		return $time > 0 ? $time : 0;
	}
}

if (!function_exists('kdjlFightMode')) {
	function kdjlFightMode($user, $normalMapOnly, $forcedMode)
	{
		if ($forcedMode == 'manual' || $forcedMode == 'money' || $forcedMode == 'yb') {
			return $forcedMode;
		}

		$sid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		if (
			$sid <= 0
			|| !isset($_SESSION['exptype'.$sid])
			|| intval($_SESSION['exptype'.$sid]) != 1
			|| !isset($user['autofitflag'])
			|| intval($user['autofitflag']) != 1
		) {
			return 'manual';
		}

		if ($normalMapOnly) {
			$multiKey = 'multi_monsters'.$sid;
			if (!isset($_SESSION[$multiKey]) || intval($_SESSION[$multiKey]) != 2) {
				return 'manual';
			}
		}

		$wayKey = 'way'.$sid;
		$way = isset($_SESSION[$wayKey]) ? $_SESSION[$wayKey] : '';
		if ($way == 'yb' && isset($user['maxautofitsum']) && intval($user['maxautofitsum']) > 0) {
			return 'yb';
		}
		if (($way == '' || $way == 'money') && isset($user['sysautosum']) && intval($user['sysautosum']) > 0) {
			return 'money';
		}
		return 'manual';
	}
}

if (!function_exists('kdjlFightStateMode')) {
	function kdjlFightStateMode($fight, $user, $normalMapOnly, $forcedMode)
	{
		if ($forcedMode == 'manual' || $forcedMode == 'money' || $forcedMode == 'yb') {
			return $forcedMode;
		}
		if (is_array($fight) && isset($fight['fight_mode'])) {
			$mode = $fight['fight_mode'];
			if ($mode == 'manual' || $mode == 'money' || $mode == 'yb') {
				return $mode;
			}
		}
		return kdjlFightMode($user, $normalMapOnly, '');
	}
}

if (!function_exists('kdjlFightAttackWaitLimit')) {
	function kdjlFightAttackWaitLimit($user, $normalMapOnly, $fight, $forcedMode)
	{
		$mode = kdjlFightStateMode($fight, $user, $normalMapOnly, $forcedMode);
		if ($mode == 'yb') {
			return 3;
		}
		if ($mode == 'money') {
			return 4;
		}
		return 5;
	}
}

if (!function_exists('kdjlFightRequestEarlySeconds')) {
	function kdjlFightRequestEarlySeconds($value, $fight, $user, $normalMapOnly, $forcedMode, $fallbackStartedAt)
	{
		$limit = kdjlFightAttackWaitLimit($user, $normalMapOnly, $fight, $forcedMode);
		if ($limit <= 0) {
			return 0;
		}

		if (!is_array($value) && $value !== null && $value !== '' && is_numeric($value)) {
			$early = floatval($value);
		}
		else {
			$startedAt = 0;
			if (is_array($fight) && isset($fight['attack_wait_started_at'])) {
				$startedAt = floatval($fight['attack_wait_started_at']);
			}
			else if (is_array($fight) && isset($fight['ftime'])) {
				$startedAt = floatval($fight['ftime']);
			}
			if ($startedAt <= 0) {
				$startedAt = floatval($fallbackStartedAt);
			}
			$early = $startedAt > 0 ? $startedAt + $limit - microtime(true) : 0;
		}

		if ($early <= 0) {
			return 0;
		}
		return min(floatval($limit), $early);
	}
}

if (!function_exists('kdjlFightMarkAttackState')) {
	function kdjlFightMarkAttackState($fight, $earlySeconds)
	{
		if (!is_array($fight)) {
			$fight = array();
		}
		$attackRounds = isset($fight['attack_rounds']) ? intval($fight['attack_rounds']) : 0;
		$fight['attack_rounds'] = max(0, $attackRounds) + 1;
		$fight['ftime'] = time();
		$fight['attack_wait_started_at'] = microtime(true);
		$fight['attack_early_seconds'] = max(0, floatval($earlySeconds));
		return $fight;
	}
}

if (!function_exists('kdjlFightPostWaitLimit')) {
	function kdjlFightPostWaitLimit($mode, $bid)
	{
		if ($mode == 'yb') {
			$wait = 4;
		}
		else if ($mode == 'money') {
			$wait = 5;
		}
		else {
			$wait = 6;
		}
		$bid = intval($bid);
		if ($bid > 0) {
			$wait -= kdjlFightWaitEquipTime($bid);
		}
		$wait -= kdjlFightWaitTitleTime();
		return $wait > 0 ? $wait : 0;
	}
}

if (!function_exists('kdjlFightAutoPostWaitLimit')) {
	function kdjlFightAutoPostWaitLimit($mode, $bid)
	{
		return kdjlFightPostWaitLimit($mode, $bid);
	}
}

if (!function_exists('kdjlFightManualDurationDiscount')) {
	function kdjlFightManualDurationDiscount($fight, $referenceAt)
	{
		if (!is_array($fight) || !isset($fight['battle_started_at'])) {
			return 0;
		}
		$startedAt = floatval($fight['battle_started_at']);
		$referenceAt = floatval($referenceAt);
		if ($startedAt <= 0 || $referenceAt <= $startedAt) {
			return 0;
		}
		$elapsed = $referenceAt - $startedAt;
		return $elapsed > 12 ? $elapsed - 12 : 0;
	}
}

if (!function_exists('kdjlFightCalculatedPostWait')) {
	function kdjlFightCalculatedPostWait($fight, $mode, $bid, $referenceAt)
	{
		$wait = is_array($fight) && isset($fight['post_wait_base_seconds'])
			? max(0, floatval($fight['post_wait_base_seconds']))
			: floatval(kdjlFightPostWaitLimit($mode, $bid));
		if ($mode == 'manual') {
			$wait -= kdjlFightManualDurationDiscount($fight, $referenceAt);
		}
		return $wait > 0 ? intval(ceil($wait)) : 0;
	}
}

if (!function_exists('kdjlFightPreserveStartedAt')) {
	function kdjlFightPreserveStartedAt($fight, $previousFight)
	{
		if (!is_array($fight)) {
			$fight = array();
		}
		if (
			!is_array($previousFight)
			|| !isset($previousFight['fatting'])
			|| intval($previousFight['fatting']) != 1
			|| !isset($previousFight['gid'], $fight['gid'])
			|| intval($previousFight['gid']) <= 0
			|| intval($previousFight['gid']) != intval($fight['gid'])
			|| !isset($previousFight['battle_started_at'])
		) {
			return $fight;
		}
		$startedAt = floatval($previousFight['battle_started_at']);
		if ($startedAt > 0 && $startedAt <= microtime(true)) {
			$fight['battle_started_at'] = $startedAt;
		}
		return $fight;
	}
}

if (!function_exists('kdjlFightStartState')) {
	function kdjlFightStartState($fight, $user, $normalMapOnly, $forcedMode)
	{
		if (!is_array($fight)) {
			$fight = array();
		}
		$fight['fight_mode'] = kdjlFightMode($user, $normalMapOnly, $forcedMode);
		$stateStartedAt = microtime(true);
		$battleStartedAt = $stateStartedAt;
		if (isset($fight['battle_started_at'])) {
			$previousStartedAt = floatval($fight['battle_started_at']);
			if ($previousStartedAt > 0 && $previousStartedAt <= $stateStartedAt) {
				$battleStartedAt = $previousStartedAt;
			}
		}
		$fight['battle_started_at'] = $battleStartedAt;
		$fight['attack_wait_started_at'] = $stateStartedAt;
		$fight['attack_early_seconds'] = 0;
		$fight['attack_rounds'] = 0;
		unset($fight['battle_ended_at'], $fight['post_wait_base_seconds'], $fight['post_wait_seconds'], $fight['post_wait_started_at'], $fight['post_wait_early_seconds']);
		return $fight;
	}
}

if (!function_exists('kdjlFightNeedsPetRestore')) {
	function kdjlFightNeedsPetRestore($fight)
	{
		if (!is_array($fight) || empty($fight)) {
			return true;
		}
		return !isset($fight['fatting'], $fight['gid'], $fight['hp'])
			|| intval($fight['fatting']) != 1
			|| intval($fight['gid']) < 1
			|| floatval($fight['hp']) <= 0;
	}
}

if (!function_exists('kdjlFightRestorePet')) {
	function kdjlFightRestorePet($uid, $bid, $equipHp, $equipMp)
	{
		global $_pm;
		$uid = intval($uid);
		$bid = intval($bid);
		$equipHp = max(0, intval(round($equipHp)));
		$equipMp = max(0, intval(round($equipMp)));
		if ($uid < 1 || $bid < 1 || !isset($_pm['mysql'])) {
			return false;
		}
		$restored = $_pm['mysql']->query(
			'UPDATE userbb SET hp=srchp,mp=srcmp,addhp='.$equipHp.',addmp='.$equipMp.
			' WHERE id='.$bid.' AND uid='.$uid.' AND muchang=0 AND tgflag=0'
		);
		if (!$restored) {
			return false;
		}
		if (isset($_pm['mem'])) {
			$_pm['mem']->del($uid.'bb');
		}
		return true;
	}
}

if (!function_exists('kdjlFightRestorePlayerPet')) {
	function kdjlFightRestorePlayerPet($uid, $bid)
	{
		global $_pm;
		$uid = intval($uid);
		$bid = intval($bid);
		if ($uid < 1 || !isset($_pm['mysql'])) {
			return false;
		}
		if ($bid > 0) {
			$pet = $_pm['mysql']->getOneRecord(
				'SELECT id,srchp,srcmp FROM userbb WHERE uid='.$uid.' AND id='.$bid.
				' AND muchang=0 AND tgflag=0 LIMIT 1'
			);
		}
		else {
			$pet = $_pm['mysql']->getOneRecord(
				'SELECT b.id,b.srchp,b.srcmp FROM player p INNER JOIN userbb b'.
				' ON b.uid=p.id AND b.id=IF(p.fightbb>0,p.fightbb,p.mbid)'.
				' WHERE p.id='.$uid.' AND b.muchang=0 AND b.tgflag=0 LIMIT 1'
			);
		}
		if (!is_array($pet) || !isset($pet['id'])) {
			return false;
		}
		$bid = intval($pet['id']);
		$equip = getzbAttrib($bid, false, '', $uid);
		$equipHp = max(0, intval(round(isset($equip['hp']) ? $equip['hp'] : 0)));
		$equipMp = max(0, intval(round(isset($equip['mp']) ? $equip['mp'] : 0)));
		if (!kdjlFightRestorePet($uid, $bid, $equipHp, $equipMp)) {
			return false;
		}
		return array(
			'uid'=>$uid,
			'bid'=>$bid,
			'base_hp'=>max(0, intval(isset($pet['srchp']) ? $pet['srchp'] : 0)),
			'base_mp'=>max(0, intval(isset($pet['srcmp']) ? $pet['srcmp'] : 0)),
			'equip_hp'=>$equipHp,
			'equip_mp'=>$equipMp
		);
	}
}

if (!function_exists('kdjlFightRestoreActiveTeamPets')) {
	function kdjlFightRestoreActiveTeamPets($teamInfo)
	{
		if (!is_array($teamInfo) || !isset($teamInfo['members']) || !is_array($teamInfo['members'])) {
			return false;
		}
		$restored = 0;
		foreach ($teamInfo['members'] as $member) {
			if (!is_array($member) || intval(isset($member['state']) ? $member['state'] : 0) != 1) {
				continue;
			}
			$memberUid = intval(isset($member['uid']) ? $member['uid'] : 0);
			if ($memberUid < 1 || kdjlFightRestorePlayerPet($memberUid, 0) === false) {
				return false;
			}
			$restored++;
		}
		return $restored > 0;
	}
}

if (!function_exists('kdjlFightFinishState')) {
	function kdjlFightFinishState($fight, $user, $normalMapOnly, $bid, $forcedMode)
	{
		if (!is_array($fight)) {
			$fight = array();
		}
		$endedAt = microtime(true);
		$mode = kdjlFightStateMode($fight, $user, $normalMapOnly, $forcedMode);
		$fight['fight_mode'] = $mode;
		$fight['battle_ended_at'] = $endedAt;
		unset($fight['post_wait_started_at']);

		$wait = kdjlFightPostWaitLimit($mode, $bid);
		$earlySeconds = isset($fight['attack_early_seconds']) ? max(0, floatval($fight['attack_early_seconds'])) : 0;
		$attackRounds = isset($fight['attack_rounds']) ? max(0, intval($fight['attack_rounds'])) : 1;
		$earlyWait = $attackRounds <= 1 && $earlySeconds > 0 ? intval(ceil($earlySeconds)) : 0;
		$fight['post_wait_base_seconds'] = max(0, intval($wait));
		$fight['post_wait_seconds'] = max(0, intval($wait)) + $earlyWait;
		$fight['post_wait_early_seconds'] = $earlyWait;
		unset($fight['attack_early_seconds']);
		return $fight;
	}
}

if (!function_exists('kdjlFightBeginPostWait')) {
	function kdjlFightBeginPostWait($fight)
	{
		if (
			is_array($fight)
			&& isset($fight['fatting'])
			&& intval($fight['fatting']) == 0
			&& (!isset($fight['post_wait_started_at']) || floatval($fight['post_wait_started_at']) <= 0)
		) {
			$waitStartedAt = microtime(true);
			$fight['post_wait_started_at'] = $waitStartedAt;
			$mode = isset($fight['fight_mode']) ? $fight['fight_mode'] : 'manual';
			if ($mode != 'money' && $mode != 'yb') {
				$mode = 'manual';
			}
			$bid = isset($fight['bid']) ? intval($fight['bid']) : 0;
			// Manual elapsed-time credit ends when the player first requests the next battle.
			$baseWait = kdjlFightCalculatedPostWait($fight, $mode, $bid, $waitStartedAt);
			$earlyWait = isset($fight['post_wait_early_seconds'])
				? max(0, intval($fight['post_wait_early_seconds']))
				: 0;
			$fight['post_wait_seconds'] = $baseWait + $earlyWait;
		}
		return $fight;
	}
}

if (!function_exists('kdjlFightBeginNavigationWait')) {
	function kdjlFightBeginNavigationWait($uid)
	{
		$uid = intval($uid);
		$fightKey = 'fight'.$uid;
		if (
			$uid < 1
			|| !isset($_SESSION[$fightKey])
			|| !is_array($_SESSION[$fightKey])
			|| !isset($_SESSION[$fightKey]['fatting'])
			|| intval($_SESSION[$fightKey]['fatting']) != 0
		) {
			return false;
		}
		$_SESSION[$fightKey] = kdjlFightBeginPostWait($_SESSION[$fightKey]);
		return true;
	}
}

if (!function_exists('kdjlFightAttackWaitRemaining')) {
	function kdjlFightAttackWaitRemaining($fight, $user, $normalMapOnly, $forcedMode)
	{
		if (!is_array($fight)) {
			return 0;
		}
		$lastAttackAt = isset($fight['attack_wait_started_at'])
			? floatval($fight['attack_wait_started_at'])
			: (isset($fight['ftime']) ? floatval($fight['ftime']) : 0);
		if ($lastAttackAt <= 0) {
			return 0;
		}
		$limit = kdjlFightAttackWaitLimit($user, $normalMapOnly, $fight, $forcedMode);
		$remaining = $lastAttackAt + $limit - microtime(true);
		return $remaining > 0 ? intval(ceil($remaining)) : 0;
	}
}

if (!function_exists('kdjlFightPostWaitRemaining')) {
	function kdjlFightPostWaitRemaining($fight, $user, $normalMapOnly, $bid, $forcedMode)
	{
		if (!is_array($fight)) {
			return 0;
		}
		$mode = kdjlFightStateMode($fight, $user, $normalMapOnly, $forcedMode);
		$endedAt = isset($fight['battle_ended_at']) ? floatval($fight['battle_ended_at']) : 0;
		if ($endedAt <= 0 && isset($fight['ftime'])) {
			$endedAt = floatval($fight['ftime']);
		}
		if ($endedAt <= 0) {
			return 0;
		}

		if (isset($fight['post_wait_seconds'])) {
			$wait = max(0, intval($fight['post_wait_seconds']));
		}
		else {
			$wait = kdjlFightCalculatedPostWait($fight, $mode, $bid, $endedAt);
		}
		$attackRounds = isset($fight['attack_rounds']) ? max(0, intval($fight['attack_rounds'])) : 1;
		if ($attackRounds <= 1 && !isset($fight['post_wait_seconds']) && isset($fight['attack_early_seconds'])) {
			$earlySeconds = max(0, floatval($fight['attack_early_seconds']));
			$wait += $earlySeconds > 0 ? intval(ceil($earlySeconds)) : 0;
		}

		$waitStartedAt = isset($fight['post_wait_started_at']) ? floatval($fight['post_wait_started_at']) : $endedAt;
		if ($waitStartedAt <= 0) {
			$waitStartedAt = $endedAt;
		}
		$remaining = $waitStartedAt + $wait - microtime(true);
		return $remaining > 0 ? intval(ceil($remaining)) : 0;
	}
}

if (!function_exists('kdjlFightEntryWaitRemaining')) {
	function kdjlFightEntryWaitRemaining($fight, $user, $normalMapOnly, $bid, $forcedMode)
	{
		if (is_array($fight) && isset($fight['fatting']) && intval($fight['fatting']) == 0) {
			return kdjlFightPostWaitRemaining($fight, $user, $normalMapOnly, $bid, $forcedMode);
		}
		return kdjlFightAttackWaitRemaining($fight, $user, $normalMapOnly, $forcedMode);
	}
}

if (!function_exists('kdjlAutoFightWaitLimit')) {
	function kdjlAutoFightWaitLimit($user, $normalMapOnly, $bid)
	{
		return kdjlFightAttackWaitLimit($user, $normalMapOnly, array(), '');
	}
}
?>
