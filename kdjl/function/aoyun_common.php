<?php
if (!function_exists('kdjlAoyunTimeMinutes')) {
	function kdjlAoyunTimeMinutes($value)
	{
		$value = trim((string)$value);
		if($value === '') return false;
		$hour = 0;
		$minute = 0;
		if(preg_match('/^(\d{1,2}):(\d{2})$/D', $value, $match))
		{
			$hour = intval($match[1]);
			$minute = intval($match[2]);
		}
		else if(preg_match('/^\d{1,2}$/D', $value))
		{
			$hour = intval($value);
		}
		else if(preg_match('/^(\d{1,2})(\d{2})$/D', $value, $match))
		{
			$hour = intval($match[1]);
			$minute = intval($match[2]);
		}
		else
		{
			return false;
		}
		if($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) return false;
		return $hour * 60 + $minute;
	}
}

if (!function_exists('kdjlAoyunActiveWindow')) {
	function kdjlAoyunActiveWindow($rows, $timestamp)
	{
		if(!is_array($rows)) return false;
		$timestamp = intval($timestamp);
		if($timestamp < 1) $timestamp = time();
		$today = intval(date('Ymd', $timestamp));
		$nowMinutes = intval(date('G', $timestamp)) * 60 + intval(date('i', $timestamp));
		foreach($rows as $row)
		{
			if(!is_array($row) || !isset($row['days'], $row['starttime'], $row['endtime'])) continue;
			if(!preg_match('/^(\d{8})-(\d{8})$/D', trim((string)$row['days']), $dateMatch)) continue;
			$startDay = intval($dateMatch[1]);
			$endDay = intval($dateMatch[2]);
			$startMinutes = kdjlAoyunTimeMinutes($row['starttime']);
			$endMinutes = kdjlAoyunTimeMinutes($row['endtime']);
			if($startDay < 1 || $endDay < $startDay || $today < $startDay || $today > $endDay) continue;
			if($startMinutes === false || $endMinutes === false || $startMinutes >= $endMinutes) continue;
			if($nowMinutes >= $startMinutes && $nowMinutes < $endMinutes) return $row;
		}
		return false;
	}
}

if (!function_exists('kdjlAoyunDateIsOpen')) {
	function kdjlAoyunDateIsOpen($rows, $timestamp)
	{
		if(!is_array($rows)) return false;
		$timestamp = intval($timestamp);
		if($timestamp < 1) $timestamp = time();
		$today = intval(date('Ymd', $timestamp));
		foreach($rows as $row)
		{
			if(!is_array($row) || !isset($row['days'])) continue;
			if(!preg_match('/^(\d{8})-(\d{8})$/D', trim((string)$row['days']), $dateMatch)) continue;
			$startDay = intval($dateMatch[1]);
			$endDay = intval($dateMatch[2]);
			if($startDay > 0 && $endDay >= $startDay && $today >= $startDay && $today <= $endDay) return true;
		}
		return false;
	}
}

if (!function_exists('kdjlAoyunTodayStart')) {
	function kdjlAoyunTodayStart($timestamp)
	{
		$timestamp = intval($timestamp);
		if($timestamp < 1) $timestamp = time();
		return mktime(0, 0, 0, intval(date('m', $timestamp)), intval(date('d', $timestamp)), intval(date('Y', $timestamp)));
	}
}
?>
