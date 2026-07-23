<?php

function adminDropCatalog($db, $fbinfo)
{
	$dungeonIds = array();
	$dungeons = array();
	if (!is_array($fbinfo)) $fbinfo = array();
	foreach ($fbinfo as $fb)
	{
		if (!is_array($fb)) continue;
		if (!isset($fb['id'])) continue;
		if (!isset($fb['name'])) $fb['name'] = '';
		if (!isset($fb['lv'])) $fb['lv'] = '';
		if (!isset($fb['gwid'])) $fb['gwid'] = '';
		$id = intval($fb['id']);
		$dungeonIds[$id] = true;
		$dungeons['dungeon-' . $id] = array(
			'key' => 'dungeon-' . $id,
			'kind' => 'dungeon',
			'id' => $id,
			'name' => $fb['name'],
			'level' => $fb['lv'],
			'monster_ids' => adminDropIdList($fb['gwid'])
		);
	}
	$maps = array();
	$rows = $db->getRecords('SELECT id,name,gpclist,level,multi_monsters FROM map ORDER BY id');
	if (is_array($rows))
	{
		foreach ($rows as $row)
		{
			$id = intval($row['id']);
			if (isset($dungeonIds[$id])) continue;
			$maps['map-' . $id] = array(
				'key' => 'map-' . $id,
				'kind' => 'map',
				'id' => $id,
				'name' => $row['name'],
				'level' => $row['level'],
				'multi_monsters' => intval($row['multi_monsters'])
			);
		}
	}
	return array('maps' => $maps, 'dungeons' => $dungeons, 'all' => $maps + $dungeons);
}

function adminDropIdList($value)
{
	$result = array();
	if (is_array($value)) return $result;
	foreach (explode(',', (string)$value) as $id)
	{
		$id = intval(trim($id));
		if ($id > 0) $result[$id] = $id;
	}
	return array_values($result);
}

function adminDropSelectedMonsterIds($arrayValue, $csvValue)
{
	$result = array();
	foreach (adminSelectedIds(is_array($arrayValue) ? $arrayValue : array()) as $id) $result[$id] = $id;
	foreach (adminDropIdList($csvValue) as $id) $result[$id] = $id;
	return array_values($result);
}

function adminDropSelectedScopes($value, $catalog)
{
	$result = array();
	if (!is_array($value)) return $result;
	foreach ($value as $key)
	{
		if (is_array($key)) continue;
		$key = trim((string)$key);
		if (isset($catalog[$key])) $result[$key] = $key;
	}
	return array_values($result);
}

function adminDropLevelRange($value)
{
	if (is_array($value)) return false;
	$value = trim((string)$value);
	if (preg_match('/^(\d+)\s*,\s*(\d+)$/', $value, $parts))
	{
		$first = intval($parts[1]);
		$second = intval($parts[2]);
		return array(min($first, $second), max($first, $second));
	}
	if (preg_match('/^\d+$/', $value)) return array(intval($value), intval($value));
	return false;
}

function adminDropMonsterSort($left, $right)
{
	$levelDiff = intval($left['level']) - intval($right['level']);
	return $levelDiff !== 0 ? $levelDiff : intval($left['id']) - intval($right['id']);
}

function adminDropResolveMonsters($db, $selectedScopes, $catalog)
{
	if (count($selectedScopes) === 0) return array();
	$rows = $db->getRecords('SELECT id,name,level,boss,kx,droplist,activedroplist FROM gpc ORDER BY level,id');
	if (!is_array($rows)) return array();
	$challengeRows = $db->getRecords('SELECT gpc,boss,map_id FROM c_gpc ORDER BY id');
	if (!is_array($challengeRows)) $challengeRows = array();
	$byId = array();
	foreach ($rows as $row) $byId[intval($row['id'])] = $row;
	$result = array();
	foreach ($selectedScopes as $scopeKey)
	{
		if (!isset($catalog[$scopeKey])) continue;
		$scope = $catalog[$scopeKey];
		$scopeLabel = ($scope['kind'] === 'dungeon' ? '副本：' : '地图：') . $scope['name'] . '（id=' . $scope['id'] . '）';
		$matchedIds = array();
		if ($scope['kind'] === 'dungeon')
		{
			$matchedIds = $scope['monster_ids'];
		}
		else if (intval($scope['multi_monsters']) > 0 && intval($scope['multi_monsters']) < 4)
		{
			$multiType = intval($scope['multi_monsters']);
			foreach ($challengeRows as $challengeRow)
			{
				$include = ($multiType === 1 && intval($challengeRow['boss']) >= 1 && intval($challengeRow['boss']) <= 5) ||
					($multiType === 2 && intval($challengeRow['boss']) >= 1 && intval($challengeRow['boss']) <= 55) ||
					($multiType === 3 && intval($challengeRow['map_id']) === intval($scope['id']));
				if (!$include) continue;
				foreach (adminDropIdList($challengeRow['gpc']) as $gpcId) $matchedIds[$gpcId] = $gpcId;
			}
			$matchedIds = array_values($matchedIds);
		}
		else
		{
			$range = adminDropLevelRange($scope['level']);
			if ($range !== false)
			{
				foreach ($rows as $row)
				{
					$level = intval($row['level']);
					if ($level >= $range[0] && $level <= $range[1] && intval($row['boss']) !== 4)
						$matchedIds[] = intval($row['id']);
				}
			}
		}
		foreach ($matchedIds as $gpcId)
		{
			$gpcId = intval($gpcId);
			if (!isset($byId[$gpcId])) continue;
			if (!isset($result[$gpcId]))
			{
				$result[$gpcId] = $byId[$gpcId];
				$result[$gpcId]['_sources'] = array();
			}
			$result[$gpcId]['_sources'][$scopeKey] = $scopeLabel;
		}
	}
	$result = array_values($result);
	usort($result, 'adminDropMonsterSort');
	return $result;
}

function adminDropSourceIndex($db, $catalog)
{
	$rows = adminDropResolveMonsters($db, array_keys($catalog), $catalog);
	$result = array();
	foreach ($rows as $row) $result[intval($row['id'])] = $row['_sources'];
	return $result;
}

function adminDropSearchProps($db, $search)
{
	$search = trim((string)$search);
	if ($search === '') return array();
	$escaped = $db->escape($search);
	$rows = $db->getRecords("SELECT id,name,varyname,propslock FROM props WHERE CAST(id AS CHAR) LIKE '%{$escaped}%' OR name LIKE '%{$escaped}%' ORDER BY id LIMIT 100");
	return is_array($rows) ? $rows : array();
}

function adminDropDisplayGroups($droplist)
{
	$groups = array();
	$invalidIndex = 0;
	foreach (explode(',', (string)$droplist) as $token)
	{
		$token = trim($token);
		if ($token === '' || $token === '0') continue;
		if (preg_match('/^(\d+):(\d+)$/', $token, $parts) && intval($parts[1]) > 0 && intval($parts[2]) > 0)
		{
			$key = intval($parts[1]) . ':' . intval($parts[2]);
			if (!isset($groups[$key]))
			{
				$groups[$key] = array('valid' => true, 'id' => intval($parts[1]), 'denominator' => intval($parts[2]), 'count' => 0, 'raw' => $token);
			}
			$groups[$key]['count']++;
		}
		else
		{
			$groups['invalid-' . $invalidIndex++] = array('valid' => false, 'id' => 0, 'denominator' => 0, 'count' => 1, 'raw' => $token);
		}
	}
	return array_values($groups);
}

function adminDropGroupsForProp($droplist, $propId)
{
	$result = array();
	foreach (adminDropDisplayGroups($droplist) as $group)
	{
		if ($group['valid'] && intval($group['id']) === intval($propId)) $result[] = $group;
	}
	return $result;
}

function adminDropPercent($denominator)
{
	$denominator = intval($denominator);
	if ($denominator < 1) return '0';
	$precision = $denominator <= 100 ? 2 : 6;
	$value = number_format(100 / $denominator, $precision, '.', '');
	return rtrim(rtrim($value, '0'), '.');
}

function adminDropRewrite($current, $propId, $denominator, $remove)
{
	$current = (string)$current;
	$next = array();
	$found = false;
	if ($current !== '')
	{
		foreach (explode(',', $current) as $token)
		{
			$check = explode(':', trim($token));
			$checkId = isset($check[0]) ? trim($check[0]) : '';
			if ($checkId !== '' && ctype_digit($checkId) && intval($checkId) === intval($propId))
			{
				$found = true;
				continue;
			}
			$next[] = $token;
		}
	}
	if ($remove && !$found) return $current;
	if (!$remove && $found) return $current;
	if (!$remove)
	{
		$clean = array();
		foreach ($next as $token) if (trim($token) !== '' && trim($token) !== '0') $clean[] = $token;
		$next = $clean;
		$next[] = intval($propId) . ':' . intval($denominator);
	}
	return implode(',', $next);
}

function adminDropColumnLimit($db, $dropField)
{
	$fallback = $dropField === 'activedroplist' ? 1024 : 2048;
	$row = $db->getOneRecord("SHOW COLUMNS FROM gpc LIKE '" . $db->escape($dropField) . "'");
	if (!is_array($row) || !isset($row['Type'])) return $fallback;
	$type = strtolower(trim($row['Type']));
	if (preg_match('/^(?:var)?char\((\d+)\)$/', $type, $parts)) return intval($parts[1]);
	if ($type === 'tinytext') return 255;
	if ($type === 'text') return 65535;
	if ($type === 'mediumtext') return 16777215;
	if ($type === 'longtext') return 2147483647;
	return $fallback;
}

function adminDropUpdate($db, $gpcIds, $propId, $denominator, $remove, $dropField)
{
	if ($dropField !== 'droplist' && $dropField !== 'activedroplist') return array(false, array(), '掉落类型无效。');
	$gpcIds = adminSelectedIds($gpcIds);
	if (count($gpcIds) === 0) return array(false, array(), '没有选择怪物。');
	$maxLength = adminDropColumnLimit($db, $dropField);
	$idList = implode(',', $gpcIds);
	if (!$db->query('LOCK TABLES gpc WRITE')) return array(false, array(), $db->getError());
	$rows = $db->getRecords("SELECT id,{$dropField} AS drop_config FROM gpc WHERE id IN ({$idList})");
	if (!is_array($rows) || count($rows) !== count($gpcIds))
	{
		$error = is_array($rows) ? '部分怪物不存在。' : $db->getError();
		$db->query('UNLOCK TABLES');
		return array(false, array(), $error);
	}
	$cases = array();
	$changedIds = array();
	foreach ($rows as $row)
	{
		$id = intval($row['id']);
		$next = adminDropRewrite($row['drop_config'], $propId, $denominator, $remove);
		if ($next === (string)$row['drop_config']) continue;
		if (strlen($next) > $maxLength)
		{
			$db->query('UNLOCK TABLES');
			return array(false, array(), '怪物 id=' . $id . ' 的掉落配置需要 ' . strlen($next) . ' 字节，但数据库字段上限为 ' . $maxLength . ' 字节。');
		}
		$cases[] = 'WHEN ' . $id . " THEN '" . $db->escape($next) . "'";
		$changedIds[$id] = $id;
	}
	$ok = true;
	$error = '';
	if (count($cases) > 0)
	{
		$changedList = implode(',', array_values($changedIds));
		$ok = $db->query("UPDATE gpc SET {$dropField}=CASE id " . implode(' ', $cases) . " ELSE {$dropField} END WHERE id IN ({$changedList})") ? true : false;
		if (!$ok) $error = $db->getError();
	}
	$db->query('UNLOCK TABLES');
	return array($ok, array_values($changedIds), $error);
}

function adminDropFormatProbability($value)
{
	$value = max(0, min(100, floatval($value)));
	$precision = $value < 0.01 ? 6 : ($value < 1 ? 4 : 2);
	$text = number_format($value, $precision, '.', '');
	return rtrim(rtrim($text, '0'), '.');
}

function adminDropParseCountList($text, $itemSeparator, $valueSeparator)
{
	$result = array();
	foreach (explode($itemSeparator, (string)$text) as $token)
	{
		$parts = explode($valueSeparator, trim($token));
		if (count($parts) !== 2) continue;
		$pid = intval(trim($parts[0]));
		$count = intval(trim($parts[1]));
		if ($pid < 1 || $count < 1) continue;
		$result[$pid] = isset($result[$pid]) ? $result[$pid] + $count : $count;
	}
	return $result;
}

function adminDropSourceProps($db, $ids)
{
	$clean = array();
	foreach ($ids as $id)
	{
		$id = intval($id);
		if ($id > 0) $clean[$id] = $id;
	}
	if (count($clean) === 0) return array();
	$rows = $db->getRecords('SELECT id,name,propslock FROM props WHERE id IN(' . implode(',', array_values($clean)) . ')');
	$result = array();
	if (is_array($rows)) foreach ($rows as $row) $result[intval($row['id'])] = $row;
	return $result;
}

function adminDropTaskRepeatLabel($cid, $limitlv, $xulie)
{
	if (intval($xulie) > 0) return '序列任务（单次）';
	if (preg_match('/(?:^|,)cishu:(\d+):(\d+)(?:,|$)/i', (string)$limitlv, $parts))
		return '每 ' . intval($parts[2]) . ' 天 ' . intval($parts[1]) . ' 次';
	if (strtolower(trim((string)$cid)) === 'self') return '普通可重复';
	return '一次性任务';
}

function adminDropTaskVisibility($hide, $xulie)
{
	$hide = intval($hide);
	if ($hide === 1) return array('label' => '显示', 'class' => 'success');
	if (intval($xulie) > 0) return array('label' => '序列后续', 'class' => 'warning');
	if ($hide === 2) return array('label' => '隐藏', 'class' => 'muted');
	return array('label' => 'hide=' . $hide, 'class' => 'muted');
}

function adminDropItemSources($db, $propId)
{
	$propId = intval($propId);
	$result = array('packages' => array(), 'recipes' => array(), 'tasks' => array(), 'props' => array());
	if ($propId < 1) return $result;

	$rows = $db->getRecords("SELECT id,name,varyname,requires,effect,propslock FROM props
		WHERE varyname IN(12,16,22) AND effect IS NOT NULL AND effect<>'' AND effect<>'0' ORDER BY id");
	if (!is_array($rows)) return $result;
	$lookupIds = array();

	foreach ($rows as $row)
	{
		$sourceId = intval($row['id']);
		$varyname = intval($row['varyname']);
		$effect = trim((string)$row['effect']);
		$requires = trim((string)$row['requires']);
		if ($requires === '0') $requires = '';

		if ($varyname === 12 || $varyname === 22)
		{
			$keyId = 0;
			if (preg_match('/(?:^|,)needkey:(\d+)(?:,|$)/', $effect, $keyMatch))
			{
				$keyId = intval($keyMatch[1]);
				if ($keyId > 0) $lookupIds[$keyId] = $keyId;
			}
			$sourceType = $varyname === 22 ? '魔法石' : '礼包';

			$givePosition = strpos($effect, 'giveitems:');
			if ($givePosition !== false)
			{
				$rewards = adminDropParseCountList(substr($effect, $givePosition + strlen('giveitems:')), ',', ':');
				if (isset($rewards[$propId]))
				{
					$result['packages'][] = array(
						'source_id' => $sourceId, 'source_name' => $row['name'], 'source_type' => $sourceType,
						'source_propslock' => intval($row['propslock']),
						'mode' => '固定开出', 'count' => intval($rewards[$propId]), 'probability' => 100,
						'configured' => '固定奖励', 'position' => 0, 'key_id' => $keyId, 'requires' => $requires
					);
				}
			}

			$randomPosition = strpos($effect, 'randitem:');
			if ($randomPosition !== false)
			{
				$survival = 1.0;
				$position = 0;
				$payload = substr($effect, $randomPosition + strlen('randitem:'));
				foreach (explode('|', $payload) as $token)
				{
					$parts = explode(':', trim($token));
					if (count($parts) < 3) continue;
					$rewardId = intval($parts[0]);
					$count = intval($parts[1]);
					$denominator = intval($parts[2]);
					if ($rewardId < 1 || $count < 1 || $denominator < 1) continue;
					$position++;
					$probability = $survival * (100 / $denominator);
					if ($rewardId === $propId)
					{
						$result['packages'][] = array(
							'source_id' => $sourceId, 'source_name' => $row['name'], 'source_type' => $sourceType,
							'source_propslock' => intval($row['propslock']),
							'mode' => '随机开出', 'count' => $count, 'probability' => $probability,
							'configured' => '1/' . $denominator, 'position' => $position,
							'key_id' => $keyId, 'requires' => $requires
						);
					}
					$survival *= ($denominator - 1) / $denominator;
				}
			}
			continue;
		}

		if ($varyname !== 16) continue;
		if (strpos($effect, 'hecheng:') === 0)
		{
			$parts = explode('):', substr($effect, strlen('hecheng:')), 2);
			if (count($parts) !== 2) continue;
			$materials = adminDropParseCountList(ltrim($parts[0], '('), '|', ':');
			$outputs = adminDropParseCountList($parts[1], '|', ':');
			if (!isset($outputs[$propId])) continue;
			foreach ($materials as $pid => $count) $lookupIds[$pid] = $pid;
			$result['recipes'][] = array(
				'source_id' => $sourceId, 'source_name' => $row['name'], 'mode' => '固定合成',
				'source_propslock' => intval($row['propslock']),
				'materials' => $materials, 'material_label' => '所需材料', 'count' => intval($outputs[$propId]),
				'probability' => 100, 'configured' => '固定产物', 'position' => 0, 'requires' => $requires
			);
			continue;
		}

		if (strpos($effect, 'random_combine:') === 0)
		{
			$settings = explode(';', substr($effect, strlen('random_combine:')), 2);
			if (count($settings) !== 2) continue;
			$materials = adminDropParseCountList($settings[0], '|', ',');
			foreach ($materials as $pid => $count) $lookupIds[$pid] = $pid;
			$survival = 1.0;
			$position = 0;
			foreach (explode('|', $settings[1]) as $token)
			{
				$parts = explode(',', trim($token));
				if (count($parts) !== 4) continue;
				$rewardId = intval($parts[0]);
				$chance = intval($parts[1]);
				$count = intval($parts[2]);
				if ($rewardId < 1 || $chance < 0 || $chance > 100 || $count < 1) continue;
				$position++;
				$probability = $survival * $chance;
				if ($rewardId === $propId)
				{
					$result['recipes'][] = array(
						'source_id' => $sourceId, 'source_name' => $row['name'], 'mode' => '随机合成',
						'source_propslock' => intval($row['propslock']),
						'materials' => $materials, 'material_label' => '所需材料', 'count' => $count,
						'probability' => $probability, 'configured' => $chance . '%',
						'position' => $position, 'requires' => $requires
					);
				}
				$survival *= (100 - $chance) / 100;
			}
			continue;
		}

		if (strpos($effect, 'chongzhu:') === 0)
		{
			$settings = explode('):', substr($effect, strlen('chongzhu:')), 2);
			if (count($settings) !== 2) continue;
			$candidates = array();
			foreach (explode(',', ltrim($settings[0], '(')) as $candidate)
			{
				$pid = intval(trim($candidate));
				if ($pid > 0) $candidates[$pid] = 1;
			}
			$thresholds = array();
			foreach (explode('|', $settings[1]) as $token)
			{
				$parts = explode('-', trim($token));
				if (count($parts) !== 2) continue;
				$pid = intval($parts[0]);
				$threshold = intval($parts[1]);
				if ($pid > 0 && $threshold >= 0 && $threshold <= 100) $thresholds[$pid] = $threshold;
			}
			if (!isset($thresholds[$propId])) continue;
			asort($thresholds, SORT_NUMERIC);
			$ordered = array();
			foreach ($thresholds as $pid => $threshold) $ordered[] = array('pid' => intval($pid), 'threshold' => intval($threshold));
			foreach ($ordered as $index => $reward)
			{
				if ($reward['pid'] !== $propId) continue;
				$nextThreshold = isset($ordered[$index + 1]) ? intval($ordered[$index + 1]['threshold']) : 101;
				$lower = max(1, intval($reward['threshold']));
				$upper = min(100, $nextThreshold - 1);
				$probability = max(0, $upper - $lower + 1);
				foreach ($candidates as $pid => $count) $lookupIds[$pid] = $pid;
				$result['recipes'][] = array(
					'source_id' => $sourceId, 'source_name' => $row['name'], 'mode' => '重铸',
					'source_propslock' => intval($row['propslock']),
					'materials' => $candidates, 'material_label' => '候选物品', 'count' => 1,
					'probability' => $probability, 'configured' => '阈值 ' . intval($reward['threshold']),
					'position' => $index + 1, 'requires' => $requires
				);
				break;
			}
		}
	}

	$taskSchedules = array();
	$timeRows = $db->getRecords("SELECT days,starttime,endtime FROM timeconfig WHERE titles='task' ORDER BY Id");
	if (is_array($timeRows))
	{
		foreach ($timeRows as $timeRow)
		{
			$flag = intval($timeRow['days']);
			if ($flag < 1) continue;
			if (!isset($taskSchedules[$flag])) $taskSchedules[$flag] = array();
			$taskSchedules[$flag][] = trim((string)$timeRow['starttime']) . ' - ' . trim((string)$timeRow['endtime']);
		}
	}

	$taskRows = $db->getRecords("SELECT id,title,result,cid,limitlv,hide,xulie,flags,color FROM task
		WHERE result IS NOT NULL AND result<>'' AND result<>'0' ORDER BY id");
	if (is_array($taskRows))
	{
		foreach ($taskRows as $taskRow)
		{
			$taskId = intval($taskRow['id']);
			$limitlv = trim((string)$taskRow['limitlv']);
			if ($limitlv === '0') $limitlv = '';
			$flags = intval($taskRow['flags']);
			$visibility = adminDropTaskVisibility($taskRow['hide'], $taskRow['xulie']);
			$base = array(
				'task_id' => $taskId,
				'task_title' => html_entity_decode((string)$taskRow['title'], ENT_QUOTES, 'UTF-8'),
				'hide' => intval($taskRow['hide']), 'xulie' => intval($taskRow['xulie']),
				'flags' => $flags, 'color' => intval($taskRow['color']), 'limitlv' => $limitlv,
				'visibility' => $visibility['label'], 'visibility_class' => $visibility['class'],
				'repeat' => adminDropTaskRepeatLabel($taskRow['cid'], $limitlv, $taskRow['xulie']),
				'schedule' => $flags > 0
					? (isset($taskSchedules[$flags]) ? implode('；', $taskSchedules[$flags]) : '未配置活动时间')
					: ''
			);

			foreach (explode(',', (string)$taskRow['result']) as $token)
			{
				$token = trim($token);
				if ($token === '' || $token === '0') continue;
				$parts = explode(':', $token);
				$type = isset($parts[0]) ? strtolower(trim($parts[0])) : '';

				if ($type === 'props' || $type === 'bprops')
				{
					if (count($parts) < 3) continue;
					$count = intval($parts[2]);
					if ($count < 1) continue;
					$matches = 0;
					foreach (explode('|', $parts[1]) as $rewardId) if (intval($rewardId) === $propId) $matches++;
					if ($matches < 1) continue;
					$source = $base;
					$source['mode'] = $type === 'bprops' ? '可交易奖励' : '固定奖励';
					$source['count'] = $count * $matches;
					$source['probability'] = 100;
					$source['configured'] = '固定发放';
					$source['position'] = 0;
					$source['condition'] = '';
					$result['tasks'][] = $source;
					continue;
				}

				if ($type === 'lvprops')
				{
					if (count($parts) < 4 || intval($parts[1]) !== $propId || intval($parts[2]) < 1) continue;
					$levels = explode('|', $parts[3]);
					$minLevel = isset($levels[0]) ? intval($levels[0]) : 0;
					$maxLevel = isset($levels[1]) ? intval($levels[1]) : 0;
					$source = $base;
					$source['mode'] = '等级条件奖励';
					$source['count'] = intval($parts[2]);
					$source['probability'] = 100;
					$source['configured'] = '符合等级时固定发放';
					$source['position'] = 0;
					$source['condition'] = '主战宠物等级 ' . $minLevel . ' - ' . ($maxLevel > 0 ? $maxLevel : '不限');
					$result['tasks'][] = $source;
					continue;
				}

				if ($type === 'itemrand')
				{
					$separator = strpos($token, ':');
					if ($separator === false) continue;
					$survival = 1.0;
					$position = 0;
					foreach (explode('|', substr($token, $separator + 1)) as $rewardToken)
					{
						$rewardParts = explode(':', trim($rewardToken));
						if (count($rewardParts) !== 3) continue;
						$rewardId = intval($rewardParts[0]);
						$denominator = intval($rewardParts[1]);
						$count = intval($rewardParts[2]);
						if ($rewardId < 1 || $denominator < 1 || $count < 1) continue;
						$position++;
						$probability = $survival * (100 / $denominator);
						if ($rewardId === $propId)
						{
							$source = $base;
							$source['mode'] = '随机奖励';
							$source['count'] = $count;
							$source['probability'] = $probability;
							$source['configured'] = '1/' . $denominator;
							$source['position'] = $position;
							$source['condition'] = '';
							$result['tasks'][] = $source;
						}
						$survival *= ($denominator - 1) / $denominator;
					}
				}
			}
		}
	}

	$result['props'] = adminDropSourceProps($db, array_values($lookupIds));
	return $result;
}
