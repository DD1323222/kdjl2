<?php
if (!function_exists('slPrizeCacheKey')) {
	function slPrizeCacheKey($uid)
	{
		return 'sl_prize_info_'.date('Ymd').'_'.intval($uid);
	}
}

if (!function_exists('slDailyCacheTtl')) {
	function slDailyCacheTtl()
	{
		$ttl = strtotime('tomorrow') - time() + 3600;
		return $ttl < 3600 ? 3600 : $ttl;
	}
}

if (!function_exists('slPrizePoolIsComplete')) {
	function slPrizePoolIsComplete($pool)
	{
		if(!is_array($pool)) return false;
		for($stage = 1; $stage <= 9; $stage++)
		{
			if(!isset($pool[$stage]) || !is_array($pool[$stage]) || !isset($pool[$stage]['id']) || intval($pool[$stage]['id']) < 1) return false;
		}
		return true;
	}
}

if (!function_exists('slPrizeStoreUserPool')) {
	function slPrizeStoreUserPool($mem, $uid, $pool)
	{
		if(!slPrizePoolIsComplete($pool)) return false;
		return $mem->setexpire(array('k' => slPrizeCacheKey($uid), 'v' => $pool), slDailyCacheTtl());
	}
}

if (!function_exists('slLegacyStateIsCurrent')) {
	function slLegacyStateIsCurrent($mem)
	{
		return kdjlSafeMemValue($mem->get('SL_CLEAR_TIME'), '') === date('Ymd');
	}
}

if (!function_exists('slDailyStateKey')) {
	function slDailyStateKey($type, $uid)
	{
		$allowed = array('played', 'ticket', 'die');
		if(!in_array($type, $allowed, true)) return '';
		return 'sl_'.$type.'_'.date('Ymd').'_'.intval($uid);
	}
}

if (!function_exists('slDailyStateSet')) {
	function slDailyStateSet($mem, $type, $uid, $value)
	{
		$key = slDailyStateKey($type, $uid);
		if($key === '') return false;
		if($value === false || $value === null || $value === 0) $value = 0;
		return $mem->setexpire(array('k' => $key, 'v' => $value), slDailyCacheTtl());
	}
}

if (!function_exists('slTodayUserHas')) {
	function slTodayUserHas($mem, $uid)
	{
		$uid = intval($uid);
		if($uid < 1) return false;
		$current = kdjlSafeMemValue($mem->get(slDailyStateKey('played', $uid)), null);
		if($current !== null) return intval($current) === 1;
		if(!slLegacyStateIsCurrent($mem)) return false;
		$legacy = kdjlSafeMemValue($mem->get('today_sl_user'), array());
		if(!is_array($legacy)) return false;
		foreach($legacy as $legacyUid)
		{
			if(intval($legacyUid) === $uid)
			{
				slDailyStateSet($mem, 'played', $uid, 1);
				return true;
			}
		}
		return false;
	}
}

if (!function_exists('slTodayUserSet')) {
	function slTodayUserSet($mem, $uid, $enabled)
	{
		return slDailyStateSet($mem, 'played', intval($uid), $enabled ? 1 : false);
	}
}

if (!function_exists('slTodayTicketHas')) {
	function slTodayTicketHas($mem, $uid)
	{
		$uid = intval($uid);
		if($uid < 1) return false;
		$current = kdjlSafeMemValue($mem->get(slDailyStateKey('ticket', $uid)), null);
		if($current !== null) return intval($current) === 1;
		if(!slLegacyStateIsCurrent($mem)) return false;
		$legacy = kdjlSafeMemValue($mem->get('today_is_use_ticket'), array());
		if(!is_array($legacy)) return false;
		foreach($legacy as $legacyUid)
		{
			if(intval($legacyUid) === $uid)
			{
				slDailyStateSet($mem, 'ticket', $uid, 1);
				return true;
			}
		}
		return false;
	}
}

if (!function_exists('slTodayTicketSet')) {
	function slTodayTicketSet($mem, $uid, $enabled)
	{
		return slDailyStateSet($mem, 'ticket', intval($uid), $enabled ? 1 : false);
	}
}

if (!function_exists('slDieOptionFind')) {
	function slDieOptionFind($mem, $uid)
	{
		$uid = intval($uid);
		$current = kdjlSafeMemValue($mem->get(slDailyStateKey('die', $uid)), null);
		if($current !== null)
		{
			$stage = intval($current);
			return ($stage >= 1 && $stage <= 9) ? $stage : 0;
		}
		if(slLegacyStateIsCurrent($mem))
		{
			$legacy = kdjlSafeMemValue($mem->get('sl_die_option'), array());
			$stage = is_array($legacy) && isset($legacy[$uid]) ? intval($legacy[$uid]) : 0;
			if($stage >= 1 && $stage <= 9)
			{
				slDailyStateSet($mem, 'die', $uid, $stage);
				return $stage;
			}
		}
		return 0;
	}
}

if (!function_exists('slDieOptionGet')) {
	function slDieOptionGet($mem, $uid)
	{
		$stage = slDieOptionFind($mem, $uid);
		return $stage > 0 ? $stage : 1;
	}
}

if (!function_exists('slDieOptionSet')) {
	function slDieOptionSet($mem, $uid, $stage)
	{
		$stage = intval($stage);
		if($stage < 1 || $stage > 9) return false;
		return slDailyStateSet($mem, 'die', intval($uid), $stage);
	}
}

if (!function_exists('slDieOptionClear')) {
	function slDieOptionClear($mem, $uid)
	{
		return slDailyStateSet($mem, 'die', intval($uid), false);
	}
}

if (!function_exists('slPrizeExtractConfig')) {
	function slPrizeExtractConfig($rows)
	{
		$config = array();
		if(!is_array($rows)) return $config;
		foreach($rows as $row)
		{
			if(!is_array($row) || !isset($row['code'], $row['contents'])) continue;
			if(!preg_match('/^sl_prize_best_([1-9])$/D', trim($row['code']), $match)) continue;
			$stage = intval($match[1]);
			if(trim($row['contents']) !== '') $config[$stage] = $row['contents'];
		}
		ksort($config);
		return $config;
	}
}

if (!function_exists('slPrizeLoadBestConfig')) {
	function slPrizeLoadBestConfig($mysql, $mem)
	{
		$welcome = kdjlSafeMemValue($mem->get('db_welcome'), array());
		$config = slPrizeExtractConfig($welcome);
		if(count($config) == 9) return $config;

		$rows = $mysql->getRecords("SELECT code,contents FROM welcome WHERE LEFT(code,14)='sl_prize_best_'");
		$dbConfig = slPrizeExtractConfig($rows);
		if(!empty($dbConfig))
		{
			$allWelcome = $mysql->getRecords('SELECT * FROM welcome');
			if(is_array($allWelcome)) $mem->set(array('k' => 'db_welcome', 'v' => $allWelcome));
			return $dbConfig;
		}
		return $config;
	}
}

if (!function_exists('slPrizeLoadStageRules')) {
	function slPrizeLoadStageRules($mysql, $mem, $stage)
	{
		$stage = intval($stage);
		if($stage < 1 || $stage > 9) return array('', '');
		$probabilityCode = 'sl_probability_'.$stage;
		$otherCode = 'sl_prize_other_'.$stage;
		$probability = '';
		$other = '';
		$welcome = kdjlSafeMemValue($mem->get('db_welcome'), array());
		if(is_array($welcome))
		{
			foreach($welcome as $row)
			{
				if(!is_array($row) || !isset($row['code'], $row['contents'])) continue;
				if($row['code'] === $probabilityCode) $probability = $row['contents'];
				if($row['code'] === $otherCode) $other = $row['contents'];
			}
		}
		if($probability !== '' && $other !== '') return array($probability, $other);

		$rows = $mysql->getRecords("SELECT code,contents FROM welcome WHERE code IN ('{$probabilityCode}','{$otherCode}')");
		if(is_array($rows))
		{
			foreach($rows as $row)
			{
				if(!is_array($row) || !isset($row['code'], $row['contents'])) continue;
				if($row['code'] === $probabilityCode) $probability = $row['contents'];
				if($row['code'] === $otherCode) $other = $row['contents'];
			}
		}
		$allWelcome = $mysql->getRecords('SELECT * FROM welcome');
		if(is_array($allWelcome)) $mem->set(array('k' => 'db_welcome', 'v' => $allWelcome));
		return array($probability, $other);
	}
}

if (!function_exists('slPrizeLoadProps')) {
	function slPrizeLoadProps($mysql, $mem)
	{
		$props = kdjlSafeMemValue($mem->get('db_props'), array());
		if(is_array($props) && !empty($props)) return $props;
		$props = $mysql->getRecords('SELECT * FROM props');
		if(!is_array($props)) return array();
		$mem->set(array('k' => 'db_props', 'v' => $props));
		return $props;
	}
}

if (!function_exists('slPrizeBuildUserPool')) {
	function slPrizeBuildUserPool($config, $props)
	{
		$propsById = array();
		if(is_array($props))
		{
			foreach($props as $row)
			{
				if(!is_array($row) || !isset($row['id'])) continue;
				$pid = intval($row['id']);
				if($pid > 0) $propsById[$pid] = $row;
			}
		}

		$pool = array();
		if(!is_array($config)) return $pool;
		foreach($config as $stage => $contents)
		{
			$candidates = array();
			foreach(explode(',', (string)$contents) as $candidate)
			{
				$pid = intval(trim($candidate));
				if($pid > 0 && isset($propsById[$pid])) $candidates[] = $pid;
			}
			if(empty($candidates)) continue;
			$selectedPid = $candidates[array_rand($candidates)];
			$pool[intval($stage)] = $propsById[$selectedPid];
		}
		ksort($pool);
		return $pool;
	}
}

if (!function_exists('slPrizeImageUrl')) {
	function slPrizeImageUrl($info)
	{
		$fallback = IMAGE_SRC_URL.'/props/bob.gif';
		if(!is_array($info)) return $fallback;
		$img = isset($info['img']) ? basename(strval($info['img'])) : '';
		if($img !== '' && $img !== '0' && preg_match('/^[A-Za-z0-9_.-]+$/D', $img) &&
			file_exists(dirname(__FILE__).'/../images/props/'.$img))
		{
			return IMAGE_SRC_URL.'/props/'.$img;
		}
		$varyname = isset($info['varyname']) ? intval($info['varyname']) : 0;
		if($varyname > 0 && file_exists(dirname(__FILE__).'/../images/ui/bag/'.$varyname.'.gif'))
		{
			return IMAGE_SRC_URL.'/ui/bag/'.$varyname.'.gif';
		}
		return $fallback;
	}
}

if (!function_exists('slPrizeGetUserPool')) {
	function slPrizeGetUserPool($mem, $uid)
	{
		$uid = intval($uid);
		if($uid < 1) return false;
		$pool = kdjlSafeMemValue($mem->get(slPrizeCacheKey($uid)), false);
		if(slPrizePoolIsComplete($pool)) return $pool;

		$legacy = kdjlSafeMemValue($mem->get('sl_prize_info'), array());
		if(slLegacyStateIsCurrent($mem) && is_array($legacy) && isset($legacy[$uid]) && slPrizePoolIsComplete($legacy[$uid]))
		{
			slPrizeStoreUserPool($mem, $uid, $legacy[$uid]);
			return $legacy[$uid];
		}
		return false;
	}
}
?>
