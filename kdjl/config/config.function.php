<?php
//公用函数
//取得指定地图的信息

function getBaseMapInfoById($map_id){
	global $_pm;
	$map_id = intval($map_id);
	if($map_id < 1) return false;
	$mapInfo = $_pm['mem']-> get("base_map_info_".$map_id);
	if($mapInfo){
		return $mapInfo;
	}
	$sql = "SELECT * FROM map WHERE id='{$map_id}'";
	$rs = $_pm['mysql'] -> getOneRecord($sql);
	$arr['k'] = "base_map_info_".$map_id;
	$arr['v'] = $rs;
	$_pm['mem'] -> setArr($arr);
	return $rs;
}

//取得指定怪物
function getBaseGpcInfoById($gpcid){
	global $_pm;
	$gpcid = intval($gpcid);
	if($gpcid < 1) return false;
	$gpcInfo = $_pm['mem']-> get("base_gpc_info_".$gpcid);
	if($gpcInfo){
		return $gpcInfo;
	}
	$sql = "SELECT * FROM gpc WHERE id='{$gpcid}'";
	$rs = $_pm['mysql'] -> getOneRecord($sql);
	$arr['k'] = "base_gpc_info_".$gpcid;
	$arr['v'] = $rs;
	$_pm['mem'] -> setArr($arr);
	return $rs;
}

//取得sys技能
function getBaseSkillSysInfoById($id){
	global $_pm;
	$id = intval($id);
	if($id < 1) return false;
	$skInfo = $_pm['mem']-> get("base_skillsys_info_".$id);
	if($skInfo){
		return $skInfo;
	}
	$sql = "SELECT * FROM skillsys WHERE id='{$id}'";
	$rs = $_pm['mysql'] -> getOneRecord($sql);
	$arr['k'] = "base_skillsys_info_".$id;
	$arr['v'] = $rs;
	$_pm['mem'] -> setArr($arr);
	return $rs;
}

//取得sys技能
function getBaseSkillSysInfoByPId($pid){
	global $_pm;
	$pid = intval($pid);
	if($pid < 1) return false;
	$skInfo = $_pm['mem']-> get("base_skillsys_info_pid_".$pid);
	if($skInfo){
		return $skInfo;
	}
	$sql = "SELECT * FROM skillsys WHERE pid='{$pid}'";
	$rs = $_pm['mysql'] -> getOneRecord($sql);
	$arr['k'] = "base_skillsys_info_pid_".$pid;
	$arr['v'] = $rs;
	$_pm['mem'] -> setArr($arr);
	return $rs;
}

//取得宠物基本信息
function getBaseBBInfoById($id){
	global $_pm;
	$id = intval($id);
	if($id < 1) return false;
	$bbInfo = $_pm['mem']-> get("base_bb_info_".$id);
	if($bbInfo){
		return $bbInfo;
	}
	$sql = "SELECT * FROM bb WHERE id='{$id}'";
	$rs = $_pm['mysql'] -> getOneRecord($sql);
	$arr['k'] = "base_bb_info_".$id;
	$arr['v'] = $rs;
	$_pm['mem'] -> setArr($arr);
	return $rs;
}

//通关宠物名字取得宠物信息
function getBaseBBNameInfoById($name){
	global $_pm;
	$nameKey = md5((string)$name);
	$name = $_pm['mysql']->escape($name);
	$bbInfo = $_pm['mem']-> get("base_bbname_info_".$nameKey);
	if($bbInfo){
		return $bbInfo;
	}
	$sql = "SELECT * FROM bb WHERE name='{$name}'";
	$rs = $_pm['mysql'] -> getOneRecord($sql);
	$arr['k'] = "base_bbname_info_".$nameKey;
	$arr['v'] = $rs;
	$_pm['mem'] -> setArr($arr);
	return $rs;
}

function getBaseBBInfoForUserPet($pet){
	global $_pm;
	if(!is_array($pet)) return false;
	$oldBid = isset($pet['old_bid']) ? intval($pet['old_bid']) : 0;
	if($oldBid > 0){
		$basePet = getBaseBBInfoById($oldBid);
		if(is_array($basePet)) return $basePet;
	}
	foreach(array('cardimg','headimg','effectimg','imgstand') as $imageField){
		if(!isset($pet[$imageField]) || !is_string($pet[$imageField])) continue;
		$image = str_replace('\\', '/', $pet[$imageField]);
		if(!preg_match('/(?:^|\/)[ztkq](\d+)\.gif$/i', $image, $match)) continue;
		$basePet = getBaseBBInfoById(intval($match[1]));
		if(is_array($basePet)) return $basePet;
	}
	$name = isset($pet['name']) ? trim($pet['name']) : '';
	if($name === '') return false;
	$where = "name='".$_pm['mysql']->escape($name)."'";
	$routeFields = array('remakelevel','remakeid','remakepid');
	$routeComplete = true;
	foreach($routeFields as $field){
		if(!isset($pet[$field])){
			$routeComplete = false;
			break;
		}
	}
	if($routeComplete){
		foreach($routeFields as $field){
			$where .= " AND ".$field."='".$_pm['mysql']->escape($pet[$field])."'";
		}
		$basePet = $_pm['mysql']->getOneRecord('SELECT * FROM bb WHERE '.$where.' ORDER BY id LIMIT 1');
		if(is_array($basePet)) return $basePet;
	}
	return getBaseBBNameInfoById($name);
}

//通过id取得装备信息
function getBasePropsInfoById($id){
	global $_pm;
	$id = intval($id);
	if($id < 1) return false;
//	$pInfo = $_pm['mem']-> get("base_props_info_".$id);
//	if($pInfo){
//		return $pInfo;
//	}
	$sql = "SELECT * FROM props WHERE id='{$id}'";
	$rs = $_pm['mysql'] -> getOneRecord($sql);
	$arr['k'] = "base_props_info_".$id;
	$arr['v'] = $rs;
	$_pm['mem'] -> setArr($arr);
	return $rs;
}

//取得welcome表
function getBaseWelcomeInfoByCode($code){
	global $_pm;
	$codeKey = md5((string)$code);
	$code = $_pm['mysql']->escape($code);
	$pInfo = $_pm['mem']-> get("base_welcome_info_".$codeKey);
	if($pInfo){
		return $pInfo;
	}
	$sql = "SELECT * FROM welcome WHERE code='{$code}'";
	$rs = $_pm['mysql'] -> getOneRecord($sql);
	$arr['k'] = "base_welcome_info_".$codeKey;
	$arr['v'] = $rs;
	$_pm['mem'] -> setArr($arr);
	return $rs;
}

if (!function_exists('kdjlMemArrayValue')) {
	function kdjlMemArrayValue($mem, $key)
	{
		if (!is_object($mem)) return array();
		$raw = $mem->get($key);
		if (is_array($raw)) return $raw;
		if (!is_string($raw) || $raw === '') return array();
		$value = @unserialize($raw);
		return is_array($value) ? $value : array();
	}
}

if (!function_exists('kdjlRowsIndexedByField')) {
	function kdjlRowsIndexedByField($rows, $field)
	{
		$indexed = array();
		if (!is_array($rows)) return $indexed;
		foreach ($rows as $row)
		{
			if (!is_array($row) || !array_key_exists($field, $row) || is_array($row[$field])) continue;
			$key = (string)$row[$field];
			if ($key === '') continue;
			$indexed[$key] = $row;
		}
		return $indexed;
	}
}

if (!function_exists('kdjlInvalidateChangedBaseConfigRows')) {
	function kdjlInvalidateChangedBaseConfigRows($mem, $type, $oldRows, $newRows, $forcedIds = array())
	{
		if (!is_object($mem)) return 0;
		$supported = array('task', 'gpc', 'map', 'skillsys', 'bb', 'welcome', 'props');
		if (!in_array($type, $supported, true)) return 0;

		$identityField = ($type === 'welcome') ? 'code' : 'id';
		$oldByKey = kdjlRowsIndexedByField($oldRows, $identityField);
		$newByKey = kdjlRowsIndexedByField($newRows, $identityField);
		$identities = array();
		foreach ($oldByKey as $identity => $row) $identities[$identity] = true;
		foreach ($newByKey as $identity => $row) $identities[$identity] = true;

		$changed = array();
		foreach ($identities as $identity => $unused)
		{
			$hasOld = array_key_exists($identity, $oldByKey);
			$hasNew = array_key_exists($identity, $newByKey);
			if (!$hasOld || !$hasNew || serialize($oldByKey[$identity]) !== serialize($newByKey[$identity]))
				$changed[$identity] = true;
		}
		if (is_array($forcedIds) && $type !== 'welcome')
		{
			foreach ($forcedIds as $id)
			{
				$id = intval($id);
				if ($id > 0) $changed[(string)$id] = true;
			}
		}

		$cacheKeys = array();
		foreach ($changed as $identity => $unused)
		{
			$oldRow = isset($oldByKey[$identity]) ? $oldByKey[$identity] : array();
			$newRow = isset($newByKey[$identity]) ? $newByKey[$identity] : array();
			$id = intval($identity);
			switch ($type)
			{
				case 'task':
					if ($id > 0) $cacheKeys['base_task_info_' . $id] = true;
					break;
				case 'gpc':
					if ($id > 0) $cacheKeys['base_gpc_info_' . $id] = true;
					break;
				case 'map':
					if ($id > 0) $cacheKeys['base_map_info_' . $id] = true;
					break;
				case 'skillsys':
					if ($id > 0) $cacheKeys['base_skillsys_info_' . $id] = true;
					foreach (array($oldRow, $newRow) as $row)
					{
						$pid = isset($row['pid']) ? intval($row['pid']) : 0;
						if ($pid > 0) $cacheKeys['base_skillsys_info_pid_' . $pid] = true;
					}
					break;
				case 'bb':
					if ($id > 0) $cacheKeys['base_bb_info_' . $id] = true;
					foreach (array($oldRow, $newRow) as $row)
					{
						if (!isset($row['name']) || is_array($row['name'])) continue;
						$cacheKeys['base_bbname_info_' . md5((string)$row['name'])] = true;
					}
					break;
				case 'welcome':
					$cacheKeys['base_welcome_info_' . md5((string)$identity)] = true;
					break;
				case 'props':
					if ($id > 0) $cacheKeys['base_props_info_' . $id] = true;
					break;
			}
		}

		foreach ($cacheKeys as $cacheKey => $unused) $mem->del($cacheKey);
		return count($cacheKeys);
	}
}

if (!function_exists('kdjlSafePositiveProduct')) {
	function kdjlSafePositiveProduct($a, $b, $max = 2147483647)
	{
		$a = intval($a);
		$b = intval($b);
		$max = intval($max);
		if($a < 1 || $b < 1 || $max < 1) return false;
		if($a > floor($max / $b)) return false;
		return $a * $b;
	}
}

if (!function_exists('kdjlSafeNonNegativeSum')) {
	function kdjlSafeNonNegativeSum($a, $b, $max = 2147483647)
	{
		$a = intval($a);
		$b = intval($b);
		$max = intval($max);
		if($a < 0 || $b < 0 || $max < 0) return false;
		if($a > $max - $b) return false;
		return $a + $b;
	}
}

if (!function_exists('kdjlConfiguredServiceBaseUrl')) {
	function kdjlConfiguredServiceBaseUrl($envName)
	{
		if(!is_string($envName) || !preg_match('/^[A-Z0-9_]+$/D', $envName)) return '';
		$url = getenv($envName);
		if($url === false) return '';
		$url = rtrim(trim($url), '/');
		if($url === '') return '';
		$parts = parse_url($url);
		if(!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) return '';
		$scheme = strtolower($parts['scheme']);
		$host = strtolower($parts['host']);
		if($scheme !== 'https' && !($scheme === 'http' && in_array($host, array('127.0.0.1', 'localhost', '::1', '[::1]'), true))) return '';
		if(isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) return '';
		if(isset($parts['port']) && (intval($parts['port']) < 1 || intval($parts['port']) > 65535)) return '';
		return $url;
	}
}

if (!function_exists('kdjlMysqlTableHasColumn')) {
	function kdjlMysqlTableHasColumn($db, $table, $column)
	{
		static $tableColumns = array();
		$table = strtolower(trim((string)$table));
		$column = strtolower(trim((string)$column));
		if(!is_object($db) || !preg_match('/^[a-z_][a-z0-9_]*$/', $table) ||
			!preg_match('/^[a-z_][a-z0-9_]*$/', $column)) return false;
		if(!array_key_exists($table, $tableColumns))
		{
			$tableColumns[$table] = array();
			$rows = $db->getRecords('SHOW COLUMNS FROM `'.$table.'`');
			if(is_array($rows))
			{
				foreach($rows as $row)
				{
					if(is_array($row) && isset($row['Field']))
					{
						$tableColumns[$table][strtolower($row['Field'])] = true;
					}
				}
			}
		}
		return isset($tableColumns[$table][$column]);
	}
}

if (!function_exists('kdjlValidAccountName')) {
	function kdjlValidAccountName($value)
	{
		if(!is_string($value)) return false;
		$length = strlen($value);
		return $length >= 4 && $length <= 32 && !is_numeric($value) &&
			preg_match('/^[0-9A-Za-z_]+$/D', $value) === 1;
	}
}

if (!function_exists('kdjlValidNickname')) {
	function kdjlValidNickname($value, $minBytes = 4, $maxBytes = 21)
	{
		if(!is_string($value) || preg_match('//u', $value) !== 1) return false;
		$length = strlen($value);
		if($length < intval($minBytes) || $length > intval($maxBytes)) return false;
		// Preserve the old rule: ASCII letters, numbers and underscore are valid;
		// valid non-ASCII UTF-8 characters are also allowed, ASCII punctuation is not.
		return preg_match('/^[0-9A-Za-z_\x80-\xFF]+$/D', $value) === 1;
	}
}

if (!function_exists('kdjlAssetImageName')) {
	function kdjlAssetImageName($value, $directory, $fallback)
	{
		static $resolved = array();
		$directories = array('props'=>1, 'bb'=>1);
		$directory = (string)$directory;
		if(!isset($directories[$directory])) return '';
		$name = basename((string)$value);
		$fallback = basename((string)$fallback);
		if(!preg_match('/^[A-Za-z0-9_.-]+$/D', $fallback)) $fallback = '';
		$cacheKey = $directory."\0".$name."\0".$fallback;
		if(isset($resolved[$cacheKey])) return $resolved[$cacheKey];
		$baseDir = dirname(__FILE__).'/../images/'.$directory.'/';
		if($name === '' || $name === '0' ||
			!preg_match('/^[A-Za-z0-9_.-]+$/D', $name) ||
			!is_file($baseDir.$name))
		{
			$resolved[$cacheKey] = $fallback !== '' && is_file($baseDir.$fallback) ? $fallback : '';
			return $resolved[$cacheKey];
		}
		$resolved[$cacheKey] = $name;
		return $resolved[$cacheKey];
	}
}

if (!function_exists('kdjlPropsImageName')) {
	function kdjlPropsImageName($value, $fallback = 'zbsx.gif')
	{
		return kdjlAssetImageName($value, 'props', $fallback);
	}
}

if (!function_exists('kdjlBbImageName')) {
	function kdjlBbImageName($value, $fallback = 'k1.gif')
	{
		return kdjlAssetImageName($value, 'bb', $fallback);
	}
}
