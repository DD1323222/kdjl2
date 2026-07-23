<?php

if(!function_exists('kdjlGuildAutomationInfo'))
{
	function kdjlGuildAutomationInfo($db, $guildId)
	{
		$defaults = array(
			'guild_id' => intval($guildId),
			'auto_accept_join' => 0,
			'auto_accept_challenge' => 0,
			'system_key' => ''
		);
		$guildId = intval($guildId);
		if($guildId < 1 || !function_exists('kdjlMysqlTableHasColumn') ||
			!kdjlMysqlTableHasColumn($db, 'guild_automation', 'guild_id')) return $defaults;

		$row = $db->getOneRecord('SELECT guild_id,auto_accept_join,auto_accept_challenge,system_key '.
			'FROM guild_automation WHERE guild_id='.$guildId.' LIMIT 1');
		if(!is_array($row)) return $defaults;
		$defaults['auto_accept_join'] = !empty($row['auto_accept_join']) ? 1 : 0;
		$defaults['auto_accept_challenge'] = !empty($row['auto_accept_challenge']) ? 1 : 0;
		$defaults['system_key'] = isset($row['system_key']) ? strval($row['system_key']) : '';
		return $defaults;
	}
}

if(!function_exists('kdjlActivityWindowIsActive'))
{
	function kdjlActivityWindowIsActive($mem, $title)
	{
		if(!is_object($mem) || !defined('MEM_TIME_KEY')) return false;
		$rows = kdjlSafeMemValue($mem->get(MEM_TIME_KEY), array());
		if(!is_array($rows)) return false;
		$week = date('N');
		$hourMinute = date('H:i');
		foreach($rows as $row)
		{
			if(!is_array($row) || !isset($row['titles']) || $row['titles'] !== $title) continue;
			if(isWeeklyDayTimeActive(
				isset($row['days']) ? $row['days'] : '',
				isset($row['starttime']) ? $row['starttime'] : '',
				isset($row['endtime']) ? $row['endtime'] : '',
				$week,
				$hourMinute,
				false
			)) return true;
		}
		return false;
	}
}

if(!function_exists('kdjlActivityReleaseNamedLock'))
{
	function kdjlActivityReleaseNamedLock($db, $lockName)
	{
		$lockSql = $db->escape($lockName);
		$db->getOneRecord("SELECT RELEASE_LOCK('".$lockSql."') AS released");
	}
}

if(!function_exists('kdjlRefreshSacredBattleRobots'))
{
	function kdjlRefreshSacredBattleRobots($db)
	{
		if(!function_exists('kdjlMysqlTableHasColumn') ||
			!kdjlMysqlTableHasColumn($db, 'activity_robot', 'uid')) return false;

		$lockName = 'kdjl_activity_robot_battle_'.date('Ymd');
		$lockSql = $db->escape($lockName);
		$lock = $db->getOneRecord("SELECT GET_LOCK('".$lockSql."',2) AS locked");
		if(!is_array($lock) || intval($lock['locked']) !== 1) return false;

		$battle = $db->getOneRecord('SELECT level_get FROM battlefield WHERE id=1 LIMIT 1');
		if(!is_array($battle) || empty($battle['level_get']))
		{
			kdjlActivityReleaseNamedLock($db, $lockName);
			return false;
		}

		$brackets = array();
		foreach(explode(',', $battle['level_get']) as $entry)
		{
			$parts = explode(':', trim($entry), 2);
			if(count($parts) !== 2 || !preg_match('/^[0-9]+-[0-9]+$/D', $parts[0])) continue;
			$effects = explode('|', $parts[1]);
			if(count($effects) !== 2) continue;
			$success = explode(':', $effects[0]);
			$failure = explode(':', $effects[1]);
			if(count($success) !== 2 || count($failure) !== 2) continue;
			$brackets[$parts[0]] = array(
				'addjgvalue' => intval($success[0]),
				'ackvalue' => intval($success[1]),
				'failjgvalue' => intval($failure[0]),
				'failackvalue' => intval($failure[1])
			);
		}

		$robots = $db->getRecords('SELECT ar.uid,ar.camp_pos,ar.level_code,p.mbid '.
			'FROM activity_robot ar JOIN player p ON p.id=ar.uid '.
			'JOIN userbb b ON b.id=p.mbid AND b.uid=p.id '.
			'WHERE ar.enabled=1 AND ar.camp_pos IN (1,2) AND b.muchang=0 AND b.tgflag=0 '.
			'ORDER BY ar.camp_pos,ar.level_min,ar.uid');
		if(!is_array($robots))
		{
			kdjlActivityReleaseNamedLock($db, $lockName);
			return false;
		}

		if(!$db->query('START TRANSACTION'))
		{
			kdjlActivityReleaseNamedLock($db, $lockName);
			return false;
		}
		$todayStart = strtotime(date('Y-m-d'));
		$now = time();
		$ok = true;
		foreach($robots as $robot)
		{
			$uid = intval($robot['uid']);
			$campPos = intval($robot['camp_pos']);
			$bid = intval($robot['mbid']);
			$levelCode = isset($robot['level_code']) ? strval($robot['level_code']) : '';
			if($uid < 1 || $bid < 1 || !isset($brackets[$levelCode]))
			{
				$ok = false;
				break;
			}
			$levelCodeSql = $db->escape($levelCode);
			$effect = $brackets[$levelCode];
			$existing = $db->getOneRecord('SELECT id,lastvtime FROM battlefield_user WHERE uid='.$uid.' ORDER BY id LIMIT 1 FOR UPDATE');
			if(is_array($existing))
			{
				$id = intval($existing['id']);
				$newDay = intval($existing['lastvtime']) < $todayStart;
				$sql = 'UPDATE battlefield_user SET pos='.$campPos.',bid='.$bid.
					",levels='".$levelCodeSql."',addjgvalue=".$effect['addjgvalue'].
					',ackvalue='.$effect['ackvalue'].',failjgvalue='.$effect['failjgvalue'].
					',failackvalue='.$effect['failackvalue'];
				if($newDay)
				{
					$sql .= ',lastvtime='.$now.',doublejg=0,tops=0,'.
						'jgvalue=COALESCE(jgvalue,0)+COALESCE(curjgvalue,0),curjgvalue=0,'.
						'boxnum=0,nscf=0,subhp=0,addhp=0';
				}
				$sql .= ' WHERE id='.$id.' AND uid='.$uid;
				if(!$db->query($sql) || mysql_affected_rows($db->getConn()) > 1)
				{
					$ok = false;
					break;
				}
			}
			else
			{
				$sql = 'INSERT INTO battlefield_user(uid,pos,bid,jgvalue,levels,addjgvalue,ackvalue,'.
					'failjgvalue,failackvalue,lastvtime,doublejg,tops,curjgvalue,boxnum,nscf,subhp,addhp) VALUES('.
					$uid.','.$campPos.','.$bid.",0,'".$levelCodeSql."',".$effect['addjgvalue'].','.
					$effect['ackvalue'].','.$effect['failjgvalue'].','.$effect['failackvalue'].','.$now.',0,0,0,0,0,0,0)';
				if(!$db->query($sql) || mysql_affected_rows($db->getConn()) !== 1)
				{
					$ok = false;
					break;
				}
			}
		}

		if(!$ok || !$db->query('COMMIT'))
		{
			$db->query('ROLLBACK');
			kdjlActivityReleaseNamedLock($db, $lockName);
			return false;
		}
		kdjlActivityReleaseNamedLock($db, $lockName);
		return true;
	}
}

if(!function_exists('kdjlEnsureSystemGuildChallenge'))
{
	function kdjlEnsureSystemGuildChallenge($db)
	{
		if(!function_exists('kdjlMysqlTableHasColumn') ||
			!kdjlMysqlTableHasColumn($db, 'guild_automation', 'system_key')) return false;
		$guilds = $db->getRecords("SELECT ga.guild_id,ga.system_key FROM guild_automation ga ".
			"JOIN guild g ON g.id=ga.guild_id WHERE ga.auto_accept_challenge=1 ".
			"AND ga.system_key IN ('light','dark') ORDER BY ga.system_key");
		if(!is_array($guilds) || count($guilds) !== 2) return false;
		$ids = array();
		foreach($guilds as $guild) $ids[$guild['system_key']] = intval($guild['guild_id']);
		if(empty($ids['light']) || empty($ids['dark']) || $ids['light'] === $ids['dark']) return false;

		$lightId = $ids['light'];
		$darkId = $ids['dark'];
		$lockName = 'kdjl_system_guild_challenge_'.date('Ymd');
		$lockSql = $db->escape($lockName);
		$lock = $db->getOneRecord("SELECT GET_LOCK('".$lockSql."',2) AS locked");
		if(!is_array($lock) || intval($lock['locked']) !== 1) return false;
		if(!$db->query('START TRANSACTION'))
		{
			kdjlActivityReleaseNamedLock($db, $lockName);
			return false;
		}

		$rows = $db->getRecords('SELECT id,challenger_id,defenser_id,flags FROM guild_challenges '.
			'WHERE challenger_id IN ('.$lightId.','.$darkId.') OR defenser_id IN ('.$lightId.','.$darkId.') '.
			'ORDER BY id FOR UPDATE');
		if(!is_array($rows) && mysql_errno($db->getConn()) !== 0)
		{
			$db->query('ROLLBACK');
			kdjlActivityReleaseNamedLock($db, $lockName);
			return false;
		}
		if(!is_array($rows)) $rows = array();
		$hasAccepted = false;
		foreach($rows as $row)
		{
			if(intval($row['flags']) === 1)
			{
				$hasAccepted = true;
				break;
			}
		}
		if(!$hasAccepted)
		{
			$ok = $db->query('DELETE FROM guild_challenges WHERE flags=0 AND (challenger_id IN ('.
				$lightId.','.$darkId.') OR defenser_id IN ('.$lightId.','.$darkId.'))');
			$now = time();
			$ok = $ok && $db->query('INSERT INTO guild_challenges(challenger_id,defenser_id,create_time,flags) '.
				'VALUES('.$lightId.','.$darkId.','.$now.',1)') && mysql_affected_rows($db->getConn()) === 1;
			if(!$ok)
			{
				$db->query('ROLLBACK');
				kdjlActivityReleaseNamedLock($db, $lockName);
				return false;
			}
		}

		if(!$db->query('COMMIT'))
		{
			$db->query('ROLLBACK');
			kdjlActivityReleaseNamedLock($db, $lockName);
			return false;
		}
		kdjlActivityReleaseNamedLock($db, $lockName);
		return true;
	}
}

if(!function_exists('kdjlRunActivityAutomation'))
{
	function kdjlRunActivityAutomation($db, $mem)
	{
		$date = date('Ymd');
		if(kdjlActivityWindowIsActive($mem, 'battle'))
		{
			$key = 'kdjl_activity_robot_battle_'.$date;
			if(kdjlSafeMemValue($mem->get($key), 0) != 1 && kdjlRefreshSacredBattleRobots($db))
			{
				$mem->set(array('k' => $key, 'v' => 1));
			}
		}
		if(kdjlActivityWindowIsActive($mem, 'guild_battle'))
		{
			$key = 'kdjl_activity_robot_guild_'.$date;
			if(kdjlSafeMemValue($mem->get($key), 0) != 1 && kdjlEnsureSystemGuildChallenge($db))
			{
				$mem->set(array('k' => $key, 'v' => 1));
			}
		}
	}
}

?>
