<?php
require_once(dirname(__FILE__) . '/../config/config.game.php');

$adminDb = $_pm['mysql'];
$adminMem = $_pm['mem'];

function adminH($value)
{
	if (is_array($value)) $value = '';
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function adminScalar($value, $default = '')
{
	return is_array($value) ? $default : $value;
}

function adminGet($key, $default = '')
{
	return isset($_GET[$key]) && !is_array($_GET[$key]) ? $_GET[$key] : $default;
}

function adminPost($key, $default = '')
{
	return isset($_POST[$key]) && !is_array($_POST[$key]) ? $_POST[$key] : $default;
}

function adminRequest($key, $default = '')
{
	return isset($_REQUEST[$key]) && !is_array($_REQUEST[$key]) ? $_REQUEST[$key] : $default;
}

function adminSetFlash($type, $message)
{
	$_SESSION['admin_flash'] = array('type' => $type, 'message' => $message);
}

function adminGetFlash()
{
	$flash = isset($_SESSION['admin_flash']) ? $_SESSION['admin_flash'] : false;
	unset($_SESSION['admin_flash']);
	return $flash;
}

function adminRedirect($url)
{
	header('Location: ' . $url);
	exit;
}

function adminStartTransaction($db)
{
	return $db->query('START TRANSACTION') !== false;
}

function adminNextFreeNumericId($rows, $field)
{
	$used = array();
	if (is_array($rows))
	{
		foreach ($rows as $row)
		{
			if (!isset($row[$field])) continue;
			$id = intval($row[$field]);
			if ($id > 0) $used[$id] = true;
		}
	}
	for ($id = 1; $id < 2147483647; $id++)
	{
		if (!isset($used[$id])) return $id;
	}
	return false;
}

function adminRefreshPropsCache($db, $mem)
{
	$oldRows = kdjlMemArrayValue($mem, 'db_props');
	$rows = $db->getRecords('SELECT * FROM props ORDER BY stime');
	if (!is_array($rows)) return false;
	kdjlInvalidateChangedBaseConfigRows($mem, 'props', $oldRows, $rows);
	$byId = array();
	$byName = array();
	foreach ($rows as $row)
	{
		$byId[$row['id']] = $row;
		$byName[$row['name']] = $row;
	}
	$ok = $mem->set(array('k' => 'db_props', 'v' => $rows));
	$ok = $mem->set(array('k' => 'db_propsid', 'v' => $byId)) && $ok;
	$ok = $mem->set(array('k' => 'db_propsname', 'v' => $byName)) && $ok;
	return $ok;
}

function adminRefreshGpcCache($db, $mem, $changedIds)
{
	$oldRows = kdjlMemArrayValue($mem, 'db_gpc');
	$rows = $db->getRecords('SELECT * FROM gpc ORDER BY id');
	if (!is_array($rows)) return false;
	kdjlInvalidateChangedBaseConfigRows($mem, 'gpc', $oldRows, $rows, $changedIds);
	$byId = array();
	foreach ($rows as $row) $byId[intval($row['id'])] = $row;
	$ok = $mem->set(array('k' => 'db_gpc', 'v' => $rows));
	$ok = $mem->set(array('k' => 'db_gpcid', 'v' => $byId)) && $ok;
	return $ok;
}

function adminRefreshWelcomeCache($db, $mem)
{
	$oldRows = kdjlMemArrayValue($mem, 'db_welcome');
	$rows = $db->getRecords('SELECT * FROM welcome ORDER BY Id');
	if (!is_array($rows)) return false;
	kdjlInvalidateChangedBaseConfigRows($mem, 'welcome', $oldRows, $rows);
	$byCode = array();
	foreach ($rows as $row)
	{
		if (!isset($row['code'])) continue;
		$byCode[$row['code']] = isset($row['contents']) ? $row['contents'] : '';
	}
	$ok = $mem->set(array('k' => 'db_welcome', 'v' => $rows));
	$ok = $mem->set(array('k' => 'db_welcome1', 'v' => $byCode)) && $ok;
	return $ok;
}

function adminRefreshTimeConfigCache($db, $mem)
{
	$rows = $db->getRecords('SELECT * FROM timeconfig ORDER BY Id');
	if (!is_array($rows)) return false;
	$byTitle = array();
	foreach ($rows as $row) $byTitle[$row['titles']][] = $row;
	$ok = $mem->set(array('k' => MEM_TIME_KEY, 'v' => $rows));
	$ok = $mem->set(array('k' => MEM_TIMENEW_KEY, 'v' => $byTitle)) && $ok;
	return $ok;
}

function adminRefreshTaskCache($db, $mem, $changedIds = array())
{
	$oldRows = kdjlMemArrayValue($mem, MEM_TASK_KEY);
	$rows = $db->getRecords('SELECT * FROM task ORDER BY id');
	if (!is_array($rows)) return false;
	kdjlInvalidateChangedBaseConfigRows($mem, 'task', $oldRows, $rows, $changedIds);
	$byId = array();
	foreach ($rows as $row) $byId[intval($row['id'])] = $row;
	$ok = $mem->set(array('k' => MEM_TASK_KEY, 'v' => $byId));
	return $ok;
}

function adminNormalizeClockInput($value)
{
	if (is_array($value)) return false;
	$minutes = clockTimeToMinutes($value);
	if ($minutes === false) return false;
	return sprintf('%02d:%02d', floor($minutes / 60), $minutes % 60);
}

function adminClockInput($value)
{
	$clock = adminNormalizeClockInput($value);
	return $clock === false ? '' : $clock;
}

function adminPostedDays($value)
{
	if (!is_array($value)) return array();
	$days = array();
	foreach ($value as $day)
	{
		if (!is_array($day)) $days[] = $day;
	}
	return weeklyDayList(implode('|', $days));
}

function adminNormalizeDate($value)
{
	if (is_array($value)) return false;
	$value = trim((string)$value);
	if ($value === '') return '';
	if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/', $value, $parts)) return false;
	if (!checkdate(intval($parts[2]), intval($parts[3]), intval($parts[1])) || intval($parts[4]) > 23 || intval($parts[5]) > 59) return false;
	return $parts[1] . $parts[2] . $parts[3] . $parts[4] . $parts[5];
}

function adminCompactDateInput($value)
{
	if (is_array($value)) return '';
	if (!preg_match('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})$/', trim((string)$value), $parts)) return '';
	return $parts[1] . '-' . $parts[2] . '-' . $parts[3] . 'T' . $parts[4] . ':' . $parts[5];
}

function adminSqlDateTime($compact)
{
	if (!preg_match('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})$/', $compact, $parts)) return '';
	return $parts[1] . '-' . $parts[2] . '-' . $parts[3] . ' ' . $parts[4] . ':' . $parts[5] . ':00';
}

function adminSqlDateInput($value)
{
	if (is_array($value)) return '';
	$timestamp = strtotime(trim((string)$value));
	return $timestamp === false ? '' : date('Y-m-d\\TH:i', $timestamp);
}

function adminScheduleState($timelimit)
{
	if (is_array($timelimit)) return 'active';
	$timelimit = trim((string)$timelimit);
	if ($timelimit === '' || $timelimit === '0') return 'active';
	$parts = explode('|', $timelimit);
	$start = isset($parts[0]) ? trim($parts[0]) : '';
	$end = isset($parts[1]) ? trim($parts[1]) : '';
	$now = date('YmdHi');
	if ($start !== '' && $now < $start) return 'scheduled';
	if ($end !== '' && $now > $end) return 'expired';
	return 'active';
}

function adminStoredSchedule($timelimit)
{
	if (is_array($timelimit)) return '';
	$timelimit = trim((string)$timelimit);
	return $timelimit === '0' ? '' : $timelimit;
}

function adminSalePriceActive($price)
{
	$price = intval($price);
	return $price > 0 && $price < 99999;
}

function adminOtherActiveChannels($row, $currentChannel, $limitedItems)
{
	$stime = (is_array($row) && isset($row['stime'])) ? $row['stime'] : 0;
	if (!is_array($row) || adminCategory($stime) === 0) return array();
	$labels = array('yb' => '神秘商店', 'sj' => '水晶商店', 'vip' => 'VIP商城');
	$result = array();
	foreach ($labels as $field => $label)
	{
		$price = isset($row[$field]) ? $row[$field] : 0;
		if ($field !== $currentChannel && adminSalePriceActive($price)) $result[] = $label;
	}
	$id = isset($row['id']) ? intval($row['id']) : 0;
	$limitPrice = isset($row['zhekouyb']) ? $row['zhekouyb'] : 0;
	if ($id > 0 && $currentChannel !== 'limit' && adminSalePriceActive($limitPrice) && isset($limitedItems[$id]))
	{
		$result[] = '抢购商城';
	}
	return $result;
}

function adminCategory($stime)
{
	$value = (string)intval($stime);
	if ($value === '0') return 0;
	$category = intval(substr($value, 0, 1));
	return $category >= 1 && $category <= 4 ? $category : 0;
}

function adminSortSuffix($stime)
{
	$value = (string)intval($stime);
	return strlen($value) > 1 ? substr($value, 1) : '';
}

function adminStoreCode($category, $sortSuffix, $db)
{
	if (is_array($sortSuffix)) $sortSuffix = '';
	$sortSuffix = trim((string)$sortSuffix);
	if ($sortSuffix === '')
	{
		$row = $db->getOneRecord("SELECT MAX(CAST(SUBSTRING(CAST(stime AS CHAR),2) AS UNSIGNED)) AS max_sort FROM props WHERE stime LIKE '" . intval($category) . "%' AND stime>0");
		$sortSuffix = (string)((is_array($row) ? intval($row['max_sort']) : 0) + 1);
	}
	if (!preg_match('/^[0-9]{1,6}$/', $sortSuffix)) return false;
	$code = intval((string)intval($category) . $sortSuffix);
	return adminCategory($code) === intval($category) ? $code : false;
}

function adminParseLimitedItems($contents)
{
	$items = array();
	if (is_array($contents)) $contents = '';
	foreach (explode(',', (string)$contents) as $entry)
	{
		$parts = explode(':', trim($entry));
		if (count($parts) !== 2) continue;
		$id = intval($parts[0]);
		$stock = intval($parts[1]);
		if ($id > 0 && $stock > 0) $items[$id] = $stock;
	}
	return $items;
}

function adminBuildLimitedItems($items)
{
	$result = array();
	foreach ($items as $id => $stock)
	{
		if (intval($id) > 0 && intval($stock) > 0) $result[] = intval($id) . ':' . intval($stock);
	}
	return implode(',', $result);
}

function adminGetLimitedConfig($db)
{
	$config = $db->getOneRecord("SELECT Id,value2,contents FROM welcome WHERE code='timelimitbuy' LIMIT 1");
	return is_array($config) ? $config : array('Id' => 0, 'value2' => '', 'contents' => '');
}

function adminSaveLimitedConfig($db, $config, $value2, $items)
{
	if (!is_array($config)) $config = array();
	$valueSql = $db->escape($value2);
	$contentsSql = $db->escape(adminBuildLimitedItems($items));
	$configId = isset($config['Id']) ? intval($config['Id']) : 0;
	if ($configId > 0)
	{
		return $db->query("UPDATE welcome SET value2='{$valueSql}',contents='{$contentsSql}' WHERE Id=" . $configId);
	}
	return $db->query("INSERT INTO welcome(code,value2,contents) VALUES('timelimitbuy','{$valueSql}','{$contentsSql}')");
}

function adminLimitedState($value2)
{
	$parts = explode('|', (string)$value2);
	$start = isset($parts[0]) ? strtotime(trim($parts[0])) : false;
	$end = isset($parts[1]) ? strtotime(trim($parts[1])) : false;
	if ($start === false || $end === false) return '未配置';
	if (time() < $start) return '未开始';
	if (time() > $end) return '已结束';
	return '进行中';
}

function adminSearchProps($db, $search)
{
	$search = trim((string)$search);
	if ($search === '') return array();
	$escaped = $db->escape($search);
	$where = "name LIKE '%{$escaped}%'";
	if (preg_match('/^[0-9]+$/', $search)) $where = '(id=' . intval($search) . " OR {$where})";
	$rows = $db->getRecords("SELECT id,name,yb,sj,vip,zhekouyb,stime,timelimit,vary,varyname FROM props WHERE {$where} ORDER BY id LIMIT 100");
	return is_array($rows) ? $rows : array();
}

function adminPropTypeName($row)
{
	global $_props;
	return isset($_props['varyname'][$row['varyname']]) ? $_props['varyname'][$row['varyname']] : '类型 ' . intval($row['varyname']);
}

function adminPetMatchSql($db, $idExpression, $nameExpression, $term)
{
	$term = trim((string)$term);
	if ($term === '') return '';
	$escaped = $db->escape($term);
	return " AND (CAST({$idExpression} AS CHAR) LIKE '%{$escaped}%' OR {$nameExpression} LIKE '%{$escaped}%')";
}

function adminFuzzyMatch($term, $id, $name)
{
	$term = trim((string)$term);
	if ($term === '') return true;
	return strpos((string)intval($id), $term) !== false || stripos((string)$name, $term) !== false;
}

function adminPetMap($db)
{
	$rows = $db->getRecords('SELECT id,name,wx,remakelevel,remakeid,remakepid FROM bb ORDER BY id');
	$map = array();
	if (is_array($rows)) foreach ($rows as $row) $map[intval($row['id'])] = $row;
	return $map;
}

function adminIsGodPetId($pets, $id)
{
	$id = intval($id);
	return $id > 0 && isset($pets[$id]) && intval($pets[$id]['wx']) == 6;
}

function adminPropsMap($db)
{
	$rows = $db->getRecords('SELECT id,name FROM props ORDER BY id');
	$map = array();
	if (is_array($rows)) foreach ($rows as $row) $map[intval($row['id'])] = $row;
	return $map;
}

function adminEvolutionRoutes($pets)
{
	$routes = array();
	foreach ($pets as $pet)
	{
		$targets = explode(',', (string)$pet['remakeid']);
		$materials = explode(',', (string)$pet['remakepid']);
		$levels = explode(',', (string)$pet['remakelevel']);
		$count = max(count($targets), count($materials), count($levels));
		for ($i = 0; $i < $count; $i++)
		{
			$targetId = isset($targets[$i]) ? intval($targets[$i]) : 0;
			$materialIds = array();
			if (isset($materials[$i]))
			{
				foreach (explode('|', $materials[$i]) as $materialId)
				{
					$materialId = intval($materialId);
					if ($materialId > 0) $materialIds[$materialId] = $materialId;
				}
			}
			if ($targetId < 1 || count($materialIds) === 0) continue;
			$routes[] = array(
				'source_id' => intval($pet['id']),
				'target_id' => $targetId,
				'level' => isset($levels[$i]) ? intval($levels[$i]) : 0,
				'material_ids' => array_values($materialIds),
				'branch' => $i + 1
			);
		}
	}
	return $routes;
}

function adminMaterialsMatch($term, $materialIds, $props)
{
	$term = trim((string)$term);
	if ($term === '') return true;
	foreach ($materialIds as $id)
	{
		$name = isset($props[$id]) ? $props[$id]['name'] : '';
		if (adminFuzzyMatch($term, $id, $name)) return true;
	}
	return false;
}

function adminSelectedIds($value)
{
	$ids = array();
	if (!is_array($value)) return $ids;
	foreach ($value as $id)
	{
		if (is_array($id)) continue;
		$id = intval($id);
		if ($id > 0) $ids[$id] = $id;
	}
	return array_values($ids);
}
