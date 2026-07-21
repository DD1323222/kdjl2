<?php
require_once('onlineForPrizeInc.php');
$ms = kdjlOnlinePrizeUnlockedSteps($arr['onlinetime_today']);
echo 'OK';
if($arr['exp_got_step']<$ms)
{
	echo 0;
}else{
	$step = intval($arr['exp_got_step']);
	$requiredSeconds = kdjlOnlinePrizeRequiredSeconds($step);
	if($requiredSeconds < 1){
		// All five prizes are claimed. Keep the icon disabled until the daily reset.
		echo kdjlOnlinePrizeSecondsUntilReset();
	}else{
		echo max(0, $requiredSeconds-intval($arr['onlinetime_today']));
	}
}
realseLock();
?>
