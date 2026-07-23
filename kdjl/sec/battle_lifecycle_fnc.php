<?php

if(!function_exists('kdjlBattleLifecycleWindowSort'))
{
	function kdjlBattleLifecycleWindowSort($a, $b)
	{
		if($a['start'] == $b['start']) return 0;
		return $a['start'] > $b['start'] ? -1 : 1;
	}
}

if(!function_exists('kdjlBattleLifecycleWindows'))
{
	function kdjlBattleLifecycleWindows($mem, $title, $now)
	{
		$windows = array();
		if(!is_object($mem) || !defined('MEM_TIME_KEY')) return $windows;
		$rows = kdjlSafeMemValue($mem->get(MEM_TIME_KEY), array());
		if(!is_array($rows)) return $windows;

		$today = strtotime(date('Y-m-d 00:00:00', $now));
		foreach($rows as $row)
		{
			if(!is_array($row) || !isset($row['titles']) || $row['titles'] !== $title) continue;
			$startMinute = clockTimeToMinutes(isset($row['starttime']) ? $row['starttime'] : '');
			$endMinute = clockTimeToMinutes(isset($row['endtime']) ? $row['endtime'] : '');
			$days = weeklyDayList(isset($row['days']) ? $row['days'] : '');
			if($startMinute === false || $endMinute === false || empty($days)) continue;

			for($offset = 0; $offset < 15; $offset++)
			{
				$dayStart = strtotime('-'.$offset.' day', $today);
				if(!in_array(intval(date('N', $dayStart)), $days, true)) continue;
				$start = $dayStart + $startMinute * 60;
				$end = $dayStart + $endMinute * 60;
				if($endMinute < $startMinute) $end += 86400;
				$key = $start.'_'.$end;
				$windows[$key] = array(
					'start' => $start,
					'end' => $end,
					'token' => intval(date('Ymd', $start))
				);
			}
		}
		$windows = array_values($windows);
		usort($windows, 'kdjlBattleLifecycleWindowSort');
		return $windows;
	}
}

if(!function_exists('kdjlBattleLifecycleCurrentWindow'))
{
	function kdjlBattleLifecycleCurrentWindow($windows, $now)
	{
		if(!is_array($windows)) return false;
		foreach($windows as $window)
		{
			if($now >= $window['start'] && $now < $window['end']) return $window;
		}
		return false;
	}
}

if(!function_exists('kdjlBattleLifecycleLatestEndedWindow'))
{
	function kdjlBattleLifecycleLatestEndedWindow($windows, $before)
	{
		if(!is_array($windows)) return false;
		foreach($windows as $window)
		{
			if($window['end'] <= $before) return $window;
		}
		return false;
	}
}

if(!function_exists('kdjlBattleLifecycleAcquireLock'))
{
	function kdjlBattleLifecycleAcquireLock($db, $lockName, $wait)
	{
		$lockSql = $db->escape($lockName);
		$row = $db->getOneRecord("SELECT GET_LOCK('".$lockSql."',".intval($wait).") AS locked");
		return is_array($row) && intval($row['locked']) === 1;
	}
}

if(!function_exists('kdjlBattleLifecycleReleaseLock'))
{
	function kdjlBattleLifecycleReleaseLock($db, $lockName)
	{
		$lockSql = $db->escape($lockName);
		$db->getOneRecord("SELECT RELEASE_LOCK('".$lockSql."') AS released");
	}
}

if(!function_exists('kdjlSacredBattleRoundBounds'))
{
	function kdjlSacredBattleRoundBounds($db, $state, $roundStart, $roundEnd)
	{
		$start = intval($roundStart);
		$end = intval($roundEnd);
		if($start < 1 && is_array($state) && !empty($state['start_time'])) $start = intval($state['start_time']);
		if($start < 1 && is_array($state) && preg_match('/^[0-9]{8}$/D', strval($state['bfdate'])))
		{
			$date = strval($state['bfdate']);
			$start = strtotime(substr($date, 0, 4).'-'.substr($date, 4, 2).'-'.substr($date, 6, 2).' 00:00:00');
		}
		if($start < 1)
		{
			$last = $db->getOneRecord('SELECT MAX(lastvtime) AS lastvtime FROM battlefield_user');
			if(is_array($last) && intval($last['lastvtime']) > 0)
				$start = strtotime(date('Y-m-d 00:00:00', intval($last['lastvtime'])));
		}
		if($start < 1) $start = strtotime(date('Y-m-d 00:00:00'));
		if($end < 1 && is_array($state) && !empty($state['end_time'])) $end = intval($state['end_time']);
		if($end <= $start) $end = $start + 86400;
		return array($start, $end);
	}
}

if(!function_exists('kdjlSacredBattleSettleLocked'))
{
	function kdjlSacredBattleSettleLocked($db, $roundStart, $roundEnd)
	{
		if(!$db->query('START TRANSACTION')) return false;
		$camps = $db->getRecords('SELECT id,hp,posname,bfdate,start_time,end_time,startf,countf '.
			'FROM battlefield ORDER BY hp DESC,id ASC FOR UPDATE');
		if(!is_array($camps) && mysql_errno($db->getConn()) !== 0)
		{
			$db->query('ROLLBACK');
			return false;
		}
		if(!is_array($camps)) $camps = array();

		$active = false;
		foreach($camps as $camp)
		{
			if(intval($camp['startf']) === 1 && intval($camp['countf']) === 0)
			{
				$active = true;
				break;
			}
		}
		if(!$active || empty($camps))
		{
			if(!$db->query('COMMIT'))
			{
				$db->query('ROLLBACK');
				return false;
			}
			return array('settled'=>0);
		}

		list($roundStart, $roundEnd) = kdjlSacredBattleRoundBounds(
			$db,
			$camps[0],
			$roundStart,
			$roundEnd
		);
		$winner = $camps[0];
		$winnerId = intval($winner['id']);
		$actualEnd = time();
		if($winnerId < 1 || !$db->query(
			'UPDATE battlefield SET success=IF(id='.$winnerId.',1,0),countf=1,startf=0,ends=1,'.
			'end_time=IF(COALESCE(end_time,0)=0,'.$actualEnd.',end_time)'
		))
		{
			$db->query('ROLLBACK');
			return false;
		}

		$rankGroups = array(
			array(
				'where'=>'pos='.$winnerId,
				'rewards'=>array(
					array(10,2000),array(6,1500),array(6,1500),array(4,1000),array(4,1000),
					array(4,1000),array(2,500),array(2,500),array(2,500),array(2,500)
				)
			),
			array(
				'where'=>'pos<>'.$winnerId,
				'rewards'=>array(
					array(5,1000),array(3,500),array(3,500),array(2,300),array(2,300),
					array(2,300),array(1,100),array(1,100),array(1,100),array(1,100)
				)
			)
		);
		foreach($rankGroups as $group)
		{
			$rows = $db->getRecords(
				'SELECT id FROM battlefield_user WHERE lastvtime>='.$roundStart.
				' AND lastvtime<'.$roundEnd.' AND curjgvalue>0 AND '.$group['where'].
				' ORDER BY curjgvalue DESC,id ASC LIMIT 10 FOR UPDATE'
			);
			if(!is_array($rows) && mysql_errno($db->getConn()) !== 0)
			{
				$db->query('ROLLBACK');
				return false;
			}
			if(!is_array($rows)) $rows = array();
			foreach($rows as $rank=>$row)
			{
				if(!isset($group['rewards'][$rank])) break;
				$rowId = intval($row['id']);
				$boxnum = intval($group['rewards'][$rank][0]);
				$jgvalue = intval($group['rewards'][$rank][1]);
				if($rowId < 1 || !$db->query(
					'UPDATE battlefield_user SET tops='.($rank+1).',boxnum='.$boxnum.
					',curjgvalue=COALESCE(curjgvalue,0)+'.$jgvalue.' WHERE id='.$rowId
				))
				{
					$db->query('ROLLBACK');
					return false;
				}
			}
		}
		if(!$db->query('COMMIT'))
		{
			$db->query('ROLLBACK');
			return false;
		}
		$loserName = isset($camps[1]['posname']) ? $camps[1]['posname'] : '';
		return array(
			'settled'=>1,
			'winner'=>isset($winner['posname']) ? $winner['posname'] : '',
			'loser'=>$loserName,
			'round_start'=>$roundStart,
			'round_end'=>$roundEnd
		);
	}
}

if(!function_exists('kdjlSacredBattlePublishSettlement'))
{
	function kdjlSacredBattlePublishSettlement($db, $mem, $result)
	{
		if(!is_array($result) || empty($result['settled'])) return;
		if(is_object($mem)) $mem->set(array('k'=>'battle_prize_check','v'=>time()));
		$now = time();
		$db->query("INSERT INTO gamelog (ptime,buyer,seller,pnote,vary) VALUES(".$now.",'1','1','jgprize','200')");
		if(class_exists('task'))
		{
			$word = '[系统公告] 本次战场结束，'.$result['loser'].'被打得溃不成军，'.
				$result['winner'].'取得了胜利！';
			$pub = new task();
			for($i=0; $i<5; $i++) $pub->saveGword($word, 1);
		}
	}
}

if(!function_exists('kdjlSacredBattleSettle'))
{
	function kdjlSacredBattleSettle($db, $mem, $roundStart, $roundEnd)
	{
		$lockName = 'kdjl_sacred_battle_lifecycle';
		if(!kdjlBattleLifecycleAcquireLock($db, $lockName, 2)) return false;
		$result = kdjlSacredBattleSettleLocked($db, $roundStart, $roundEnd);
		kdjlBattleLifecycleReleaseLock($db, $lockName);
		kdjlSacredBattlePublishSettlement($db, $mem, $result);
		return $result;
	}
}

if(!function_exists('kdjlSacredBattleStartWindow'))
{
	function kdjlSacredBattleStartWindow($db, $mem, $window)
	{
		if(!is_array($window) || empty($window['start']) || empty($window['end'])) return false;
		$lockName = 'kdjl_sacred_battle_lifecycle';
		if(!kdjlBattleLifecycleAcquireLock($db, $lockName, 2)) return false;

		$result = false;
		$state = $db->getOneRecord(
			'SELECT id,bfdate,start_time,end_time,startf,countf,ends FROM battlefield WHERE id=1 LIMIT 1'
		);
		if(!is_array($state))
		{
			kdjlBattleLifecycleReleaseLock($db, $lockName);
			return false;
		}
		$token = intval($window['token']);
		$storedToken = intval($state['bfdate']);
		$isActive = intval($state['startf']) === 1 && intval($state['countf']) === 0;
		if($isActive && $storedToken === 0)
		{
			$legacyState = $db->getOneRecord(
				'SELECT MAX(lastvtime) AS lastvtime FROM battlefield_user'
			);
			$legacyDamage = $db->getOneRecord(
				'SELECT SUM(IF(hp<srchp,1,0)) AS damaged FROM battlefield'
			);
			$lastVisit = is_array($legacyState) ? intval($legacyState['lastvtime']) : 0;
			$damaged = is_array($legacyDamage) ? intval($legacyDamage['damaged']) : 0;
			$belongsToCurrentWindow = $lastVisit >= intval($window['start']) &&
				$lastVisit < intval($window['end']);
			if(!$belongsToCurrentWindow && ($lastVisit > 0 || $damaged > 0)) $storedToken = -1;
		}

		if($isActive && ($storedToken === 0 || $storedToken === $token))
		{
			$ok = $db->query(
				'UPDATE battlefield SET bfdate='.$token.',start_time='.intval($window['start']).
				',end_time='.intval($window['end']).' WHERE startf=1 AND countf=0'
			);
			kdjlBattleLifecycleReleaseLock($db, $lockName);
			return $ok;
		}
		if($isActive && $storedToken !== $token)
		{
			$result = kdjlSacredBattleSettleLocked(
				$db,
				intval($state['start_time']),
				intval($state['end_time'])
			);
			if($result === false)
			{
				kdjlBattleLifecycleReleaseLock($db, $lockName);
				return false;
			}
			$state = $db->getOneRecord(
				'SELECT id,bfdate,start_time,end_time,startf,countf,ends FROM battlefield WHERE id=1 LIMIT 1'
			);
			if(!is_array($state))
			{
				kdjlBattleLifecycleReleaseLock($db, $lockName);
				return false;
			}
			$storedToken = intval($state['bfdate']);
		}
		if(!$isActive && intval($state['startf']) === 0 && intval($state['countf']) === 1 &&
			intval($state['ends']) === 1 && $storedToken === 0)
		{
			$lastRoundVisit = $db->getOneRecord('SELECT MAX(lastvtime) AS lastvtime FROM battlefield_user');
			$lastVisit = is_array($lastRoundVisit) ? intval($lastRoundVisit['lastvtime']) : 0;
			if($lastVisit >= intval($window['start']) && $lastVisit < intval($window['end']))
			{
				$db->query(
					'UPDATE battlefield SET bfdate='.$token.',start_time='.intval($window['start']).
					',end_time='.intval($window['end']).' WHERE countf=1 AND startf=0 AND ends=1'
				);
				$storedToken = $token;
			}
		}

		// 女神提前被击败后，本开放时段内不重新开场。
		if(intval($state['startf']) === 0 && intval($state['countf']) === 1 &&
			intval($state['ends']) === 1 && $storedToken === $token)
		{
			kdjlBattleLifecycleReleaseLock($db, $lockName);
			kdjlSacredBattlePublishSettlement($db, $mem, $result);
			return true;
		}

		if(!$db->query('START TRANSACTION'))
		{
			kdjlBattleLifecycleReleaseLock($db, $lockName);
			return false;
		}
		$locked = $db->getOneRecord('SELECT id,startf,countf,ends,bfdate FROM battlefield WHERE id=1 FOR UPDATE');
		if(!is_array($locked))
		{
			$db->query('ROLLBACK');
			kdjlBattleLifecycleReleaseLock($db, $lockName);
			return false;
		}
		if(intval($locked['startf']) === 1 && intval($locked['countf']) === 0)
		{
			$db->query('COMMIT');
			kdjlBattleLifecycleReleaseLock($db, $lockName);
			kdjlSacredBattlePublishSettlement($db, $mem, $result);
			return true;
		}

		$now = time();
		$archiveSql = 'INSERT INTO battlelog(uid,jgvalue,curjgvalue,jgtime,sumjg) '.
			'SELECT uid,COALESCE(jgvalue,0),COALESCE(curjgvalue,0),'.$now.
			',COALESCE(jgvalue,0)+COALESCE(curjgvalue,0) FROM battlefield_user '.
			'WHERE COALESCE(jgvalue,0)>0 OR COALESCE(curjgvalue,0)>0';
		$ok = $db->query('DELETE FROM battlelog') &&
			$db->query($archiveSql) &&
			$db->query(
				'UPDATE battlefield SET startf=1,countf=0,success=0,ends=0,hp=srchp,'.
				'bfdate='.$token.',start_time='.intval($window['start']).
				',end_time='.intval($window['end']).',tips_time=0'
			) &&
			$db->query(
				'UPDATE battlefield_user SET tops=0,boxnum=0,doublejg=0,'.
				'jgvalue=COALESCE(jgvalue,0)+COALESCE(curjgvalue,0),curjgvalue=0,'.
				'nscf=0,subhp=0,addhp=0'
			);
		if(!$ok || !$db->query('COMMIT'))
		{
			$db->query('ROLLBACK');
			kdjlBattleLifecycleReleaseLock($db, $lockName);
			return false;
		}
		kdjlBattleLifecycleReleaseLock($db, $lockName);
		kdjlSacredBattlePublishSettlement($db, $mem, $result);
		return true;
	}
}

if(!function_exists('kdjlSacredBattleTick'))
{
	function kdjlSacredBattleTick($db, $mem, $now)
	{
		$windows = kdjlBattleLifecycleWindows($mem, 'battle', $now);
		$current = kdjlBattleLifecycleCurrentWindow($windows, $now);
		$state = $db->getOneRecord(
			'SELECT bfdate,start_time,end_time,startf,countf FROM battlefield WHERE id=1 LIMIT 1'
		);
		if(!is_array($state)) return false;
		$stats = $db->getOneRecord(
			'SELECT MIN(hp) AS minhp,SUM(IF(hp<srchp,1,0)) AS damaged FROM battlefield'
		);
		if(!is_array($stats)) return false;
		$state['minhp'] = intval($stats['minhp']);
		$state['damaged'] = intval($stats['damaged']);

		if(is_array($current))
		{
			if(intval($state['startf']) === 1 && intval($state['countf']) === 0 &&
				intval($state['minhp']) <= 0)
				return kdjlSacredBattleSettle($db, $mem, $current['start'], $current['end']) !== false;
			return kdjlSacredBattleStartWindow($db, $mem, $current);
		}

		if(intval($state['startf']) !== 1 || intval($state['countf']) !== 0) return true;
		$latest = kdjlBattleLifecycleLatestEndedWindow($windows, $now);
		if(!is_array($latest)) return true;
		if(intval($state['bfdate']) === 0 && intval($state['damaged']) === 0)
		{
			$joined = $db->getOneRecord(
				'SELECT COUNT(*) AS cnt FROM battlefield_user WHERE lastvtime>='.$latest['start'].
				' AND lastvtime<'.$latest['end']
			);
			if(!is_array($joined) || intval($joined['cnt']) === 0) return true;
		}
		$start = intval($state['start_time']);
		$end = intval($state['end_time']);
		if($start < 1) $start = $latest['start'];
		if($end < 1) $end = $latest['end'];
		if($now < $end && intval($state['minhp']) > 0) return true;
		return kdjlSacredBattleSettle($db, $mem, $start, $end) !== false;
	}
}

if(!function_exists('kdjlBattleLifecyclePruneGuildArchives'))
{
	function kdjlBattleLifecyclePruneGuildArchives($db, $keep)
	{
		$rows = $db->getRecords("SHOW TABLES LIKE 'guild_challenges%'");
		if(!is_array($rows)) return;
		$tables = array();
		foreach($rows as $row)
		{
			foreach($row as $table)
			{
				if(preg_match('/^guild_challenges[0-9]{8}$/D', $table)) $tables[] = $table;
			}
		}
		rsort($tables, SORT_STRING);
		for($i=max(0, intval($keep)); $i<count($tables); $i++)
			$db->query('DROP TABLE `'.$tables[$i].'`');
	}
}

if(!function_exists('kdjlGuildBattleSettle'))
{
	function kdjlGuildBattleSettle($db, $cutoff, $eventToken)
	{
		$cutoff = intval($cutoff);
		$eventToken = strval($eventToken);
		if($cutoff < 1 || !preg_match('/^[0-9]{8}$/D', $eventToken)) return false;
		$lockName = 'kdjl_guild_battle_lifecycle';
		if(!kdjlBattleLifecycleAcquireLock($db, $lockName, 2)) return false;

		$archive = 'guild_challenges'.$eventToken;
		if(!$db->query('CREATE TABLE IF NOT EXISTS `'.$archive.'` LIKE `guild_challenges`') ||
			!$db->query('START TRANSACTION'))
		{
			kdjlBattleLifecycleReleaseLock($db, $lockName);
			return false;
		}
		$rows = $db->getRecords(
			'SELECT id,challenger_id,defenser_id,challenger_score,defenser_score,flags '.
			'FROM guild_challenges WHERE COALESCE(create_time,0)<='.$cutoff.' ORDER BY id FOR UPDATE'
		);
		if(!is_array($rows) && mysql_errno($db->getConn()) !== 0)
		{
			$db->query('ROLLBACK');
			kdjlBattleLifecycleReleaseLock($db, $lockName);
			return false;
		}
		if(!is_array($rows) || empty($rows))
		{
			if(!$db->query('COMMIT'))
			{
				$db->query('ROLLBACK');
				kdjlBattleLifecycleReleaseLock($db, $lockName);
				return false;
			}
			kdjlBattleLifecycleReleaseLock($db, $lockName);
			return array('settled'=>0, 'messages'=>array());
		}

		$ids = array();
		$messages = array();
		$ok = true;
		foreach($rows as $row)
		{
			$id = intval($row['id']);
			if($id < 1)
			{
				$ok = false;
				break;
			}
			$ids[] = $id;
			if(intval($row['flags']) !== 1) continue;
			$challengerId = intval($row['challenger_id']);
			$defenserId = intval($row['defenser_id']);
			$c = $db->getOneRecord('SELECT name FROM guild WHERE id='.$challengerId.' FOR UPDATE');
			$d = $db->getOneRecord('SELECT name FROM guild WHERE id='.$defenserId.' FOR UPDATE');
			if(!is_array($c) || !is_array($d)) continue;

			$challengerName = htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8');
			$defenserName = htmlspecialchars((string)$d['name'], ENT_QUOTES, 'UTF-8');
			$challengerScore = intval($row['challenger_score']);
			$defenserScore = intval($row['defenser_score']);
			if($challengerScore > $defenserScore)
			{
				$ok = $db->query('UPDATE guild SET victory_times=COALESCE(victory_times,0)+1 WHERE id='.$challengerId) &&
					$db->query('UPDATE guild SET failed_times=COALESCE(failed_times,0)+1 WHERE id='.$defenserId);
				$messages[] = '<strong>《'.$challengerName.'》</strong>家族在与<strong>《'.
					$defenserName.'》</strong>家族的战斗中获得胜利！';
			}
			else if($challengerScore < $defenserScore)
			{
				$ok = $db->query('UPDATE guild SET failed_times=COALESCE(failed_times,0)+1 WHERE id='.$challengerId) &&
					$db->query('UPDATE guild SET victory_times=COALESCE(victory_times,0)+1 WHERE id='.$defenserId);
				$messages[] = '<strong>《'.$challengerName.'》</strong>家族在与<strong>《'.
					$defenserName.'》</strong>家族的战斗中失败！';
			}
			else
			{
				$messages[] = '<strong>《'.$challengerName.'》</strong>家族与<strong>《'.
					$defenserName.'》</strong>家族战成平局';
			}
			if(!$ok) break;
		}

		$idList = implode(',', $ids);
		if($ok && $idList !== '')
		{
			$ok = $db->query('UPDATE guild_challenges SET flags=2 WHERE flags=1 AND id IN('.$idList.')') &&
				$db->query('INSERT INTO `'.$archive.'` SELECT * FROM guild_challenges WHERE id IN('.$idList.')') &&
				$db->query('DELETE FROM guild_challenges WHERE id IN('.$idList.')');
		}
		if(!$ok || !$db->query('COMMIT'))
		{
			$db->query('ROLLBACK');
			kdjlBattleLifecycleReleaseLock($db, $lockName);
			return false;
		}
		kdjlBattleLifecycleReleaseLock($db, $lockName);
		kdjlBattleLifecyclePruneGuildArchives($db, 5);
		return array('settled'=>count($ids), 'messages'=>$messages);
	}
}

if(!function_exists('kdjlGuildBattleTick'))
{
	function kdjlGuildBattleTick($db, $mem, $now)
	{
		$windows = kdjlBattleLifecycleWindows($mem, 'guild_battle', $now);
		$current = kdjlBattleLifecycleCurrentWindow($windows, $now);
		if(is_array($current))
			$window = kdjlBattleLifecycleLatestEndedWindow($windows, $current['start'] - 1);
		else
			$window = kdjlBattleLifecycleLatestEndedWindow($windows, $now);
		if(!is_array($window)) return array('settled'=>0, 'messages'=>array());
		return kdjlGuildBattleSettle($db, $window['end'], date('Ymd', $window['start']));
	}
}

?>
