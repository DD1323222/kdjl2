<?php
if(!function_exists('fortressDailyCacheTtl'))
{
	function fortressDailyCacheTtl()
	{
		$ttl = strtotime('tomorrow') - time() + 3600;
		return $ttl < 3600 ? 3600 : $ttl;
	}
}

if(!function_exists('fortressDailyCacheKey'))
{
	function fortressDailyCacheKey($type, $uid)
	{
		$prefixes = array(
			'count' => 'fortress_num_',
			'cards' => 'fortress_card_info_'
		);
		if(!isset($prefixes[$type]) || intval($uid) < 1) return '';
		return $prefixes[$type].date('Ymd').'_'.intval($uid);
	}
}

if(!function_exists('fortressLegacyCacheKey'))
{
	function fortressLegacyCacheKey($type, $uid)
	{
		if(intval($uid) < 1) return '';
		if($type === 'count') return 'fortress_num'.date('md').'_'.intval($uid);
		if($type === 'cards') return 'fortress_card_info_'.date('md').'_'.intval($uid);
		return '';
	}
}

if(!function_exists('fortressDailyCacheSet'))
{
	function fortressDailyCacheSet($mem, $type, $uid, $value)
	{
		$key = fortressDailyCacheKey($type, $uid);
		if($key === '') return false;
		return $mem->setexpire(array('k' => $key, 'v' => $value), fortressDailyCacheTtl());
	}
}

if(!function_exists('fortressDailyCacheGet'))
{
	function fortressDailyCacheGet($mem, $type, $uid, $default)
	{
		$key = fortressDailyCacheKey($type, $uid);
		if($key === '') return $default;

		$raw = $mem->get($key);
		if($raw !== false && $raw !== null && $raw !== '')
		{
			return kdjlSafeMemValue($raw, $default);
		}

		// Preserve the current day's state during deployment, then retire the old MMDD key.
		$legacyKey = fortressLegacyCacheKey($type, $uid);
		if($legacyKey === '') return $default;
		$legacyRaw = $mem->get($legacyKey);
		if($legacyRaw === false || $legacyRaw === null || $legacyRaw === '') return $default;

		$value = kdjlSafeMemValue($legacyRaw, $default);
		if(fortressDailyCacheSet($mem, $type, $uid, $value)) $mem->del($legacyKey);
		return $value;
	}
}
?>
