<?php
if (!function_exists('kdjlReserveWorldBoss')) {
	function kdjlReserveWorldBoss($gs, $uid, &$log)
	{
		global $_pm;
		$log = '';
		$uid = intval($uid);
		if (
			$uid < 1 || !is_array($gs) || !isset($gs['boss'], $gs['id']) ||
			intval($gs['boss']) != 3
		) {
			return false;
		}

		$gid = intval($gs['id']);
		if ($gid < 1) return false;

		$lockName = 'kdjl_boss_refresh_'.$gid;
		$lockSql = $_pm['mysql']->escape($lockName);
		$lock = $_pm['mysql']->getOneRecord("SELECT GET_LOCK('{$lockSql}',2) AS locked");
		if (!is_array($lock) || !isset($lock['locked']) || intval($lock['locked']) != 1) {
			return false;
		}

		$reserved = false;
		$now = time();
		$exists = $_pm['mysql']->getOneRecord(
			"SELECT id,rtime,gid,fightuid,glock,dtime
			   FROM boss_refresh
			  WHERE gid={$gid}
			  ORDER BY id ASC
			  LIMIT 0,1"
		);

		if (is_array($exists) && isset($exists['id'])) {
			$id = intval($exists['id']);
			$rtime = isset($exists['rtime']) ? intval($exists['rtime']) : 0;
			$dtime = isset($exists['dtime']) ? intval($exists['dtime']) : 0;
			$glock = isset($exists['glock']) ? intval($exists['glock']) : 0;
			$sql = '';

			if ($dtime + 3600 < $now && $glock == 0) {
				$sql = "UPDATE boss_refresh
				           SET rtime={$now},fightuid={$uid},glock=1
				         WHERE id={$id} AND gid={$gid} AND glock=0
				           AND (COALESCE(dtime,0)+3600)<{$now}";
			}
			else if ($glock == 1 && $rtime + 600 < $now) {
				$sql = "UPDATE boss_refresh
				           SET rtime={$now},fightuid={$uid},glock=1
				         WHERE id={$id} AND gid={$gid} AND glock=1
				           AND (rtime+600)<{$now}";
			}

			if ($sql !== '' && $_pm['mysql']->query($sql)) {
				$check = $_pm['mysql']->getOneRecord(
					"SELECT id FROM boss_refresh
					  WHERE id={$id} AND gid={$gid} AND fightuid={$uid} AND glock=1
					  LIMIT 0,1"
				);
				if (is_array($check)) {
					$reserved = true;
					$log = $sql;
				}
			}
		}
		else {
			$sql = "INSERT INTO boss_refresh(gid,rtime,fightuid,glock)
			        VALUES({$gid},{$now},{$uid},1)";
			if ($_pm['mysql']->query($sql)) {
				$check = $_pm['mysql']->getOneRecord(
					"SELECT id FROM boss_refresh
					  WHERE gid={$gid} AND fightuid={$uid} AND glock=1
					  ORDER BY id ASC
					  LIMIT 0,1"
				);
				$reserved = is_array($check);
				if ($reserved) $log = $sql;
			}
		}

		$_pm['mysql']->getOneRecord("SELECT RELEASE_LOCK('{$lockSql}') AS released");
		return $reserved;
	}
}
?>
