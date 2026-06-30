<?php
require_once(dirname(__FILE__) . '/../config/config.game.php');

$adminDb = $_pm['mysql'];
$adminMem = $_pm['mem'];

function adminH($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
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

function adminRefreshPropsCache($db, $mem)
{
	$rows = $db->getRecords('SELECT * FROM props ORDER BY stime');
	if (!is_array($rows)) return false;
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

function adminRefreshWelcomeCache($db, $mem)
{
	$rows = $db->getRecords('SELECT * FROM welcome ORDER BY Id');
	return is_array($rows) ? $mem->set(array('k' => 'db_welcome', 'v' => $rows)) : false;
}

function adminNormalizeDate($value)
{
	$value = trim((string)$value);
	if ($value === '') return '';
	if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/', $value, $parts)) return false;
	if (!checkdate(intval($parts[2]), intval($parts[3]), intval($parts[1])) || intval($parts[4]) > 23 || intval($parts[5]) > 59) return false;
	return $parts[1] . $parts[2] . $parts[3] . $parts[4] . $parts[5];
}

function adminCompactDateInput($value)
{
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
	$timestamp = strtotime(trim((string)$value));
	return $timestamp === false ? '' : date('Y-m-d\\TH:i', $timestamp);
}

function adminScheduleState($timelimit)
{
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
	$valueSql = $db->escape($value2);
	$contentsSql = $db->escape(adminBuildLimitedItems($items));
	if (intval($config['Id']) > 0)
	{
		return $db->query("UPDATE welcome SET value2='{$valueSql}',contents='{$contentsSql}' WHERE Id=" . intval($config['Id']));
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
	$rows = $db->getRecords('SELECT id,name,remakelevel,remakeid,remakepid FROM bb ORDER BY id');
	$map = array();
	if (is_array($rows)) foreach ($rows as $row) $map[intval($row['id'])] = $row;
	return $map;
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
		$id = intval($id);
		if ($id > 0) $ids[$id] = $id;
	}
	return array_values($ids);
}
