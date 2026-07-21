<?php
if(!function_exists('kdjlOnlinePrizeUnlockedSteps'))
{
	function kdjlOnlinePrizeUnlockedSteps($seconds)
	{
		$seconds = max(0,intval($seconds));
		if($seconds >= 18000) return 5;
		if($seconds >= 7200) return 4;
		if($seconds >= 3600) return 3;
		if($seconds >= 1800) return 2;
		if($seconds >= 600) return 1;
		return 0;
	}
}

if(!function_exists('kdjlOnlinePrizeRequiredSeconds'))
{
	function kdjlOnlinePrizeRequiredSeconds($step)
	{
		$required = array(600,1800,3600,7200,18000);
		$step = intval($step);
		return isset($required[$step]) ? $required[$step] : 0;
	}
}

if(!function_exists('kdjlOnlinePrizeSecondsUntilReset'))
{
	function kdjlOnlinePrizeSecondsUntilReset($timestamp = null)
	{
		$timestamp = $timestamp === null ? time() : intval($timestamp);
		if($timestamp < 1) $timestamp = time();
		$tomorrow = mktime(
			0,
			0,
			0,
			intval(date('n', $timestamp)),
			intval(date('j', $timestamp)) + 1,
			intval(date('Y', $timestamp))
		);
		$seconds = $tomorrow - $timestamp;
		return $seconds > 0 ? $seconds : 1;
	}
}
?>
