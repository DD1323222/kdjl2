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
	$rows = $db->getRecords("SELECT id,name,varyname FROM props WHERE CAST(id AS CHAR) LIKE '%{$escaped}%' OR name LIKE '%{$escaped}%' ORDER BY id LIMIT 100");
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
