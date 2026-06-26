<?php 
require_once('onlineForPrizeInc.php');
if($onlinem>300){
	$ms=5;
}else if($onlinem>120){
	$ms=4;
}else if($onlinem>60){
	$ms=3;
}else if($onlinem>30){
	$ms=2;
}else if($onlinem>10){
	$ms=1;
}else{
	$ms=-1;
}
echo 'OK';
if($arr['exp_got_step']<$ms)
{
	echo 0;
}else{
	$timeStep=array(
			600,1800,3600,7200,18000
			);
	echo $timeStep[$arr['exp_got_step']]-$arr['onlinetime_today'];
	//echo ($timeStep[$arr['exp_got_step']]-$arr['onlinetime_today']);//测试的时候，一秒钟当一分钟
}
?>