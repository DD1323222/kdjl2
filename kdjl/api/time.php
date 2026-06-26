<?php
require_once('../config/config.game.php');
$time = time();
echo $time;
$time_info = date('d-H-i-s',$time);
$time_info_arr = explode('-',$time_info);

$gonggao_time = array
					(
						'113000'=>3,	//结束
						'113001'=>3,	//结束
						'120000'=>2,	//开始
						'120001'=>2,	//开始
						'11400'=>4,	//更新奖库
						'114001'=>4		//更新奖库
					);

if( ( $time_info_arr[2] == '40' || $time_info_arr[2] == '30' || $time_info_arr[2] == '00' ) && ( $time_info_arr[3] == '00' || $time_info_arr[3] == '01') )
{
	$time_ticket_gg = unserialize($_pm['mem'] -> get('ticket_gg'));
	if(empty($time_ticket_gg))
	{
		$_pm['mem'] -> set(array('k'=>'ticket_gg','v'=>$time_info));
		$time_ticket_gg = $time_info;
	}
	else
	{
		if($time_ticket_gg != $time_info )
		{
			$_pm['mem'] -> set(array('k'=>'ticket_gg','v'=>$time_info));
			require_once('../api/curl.php');
			$key = $time_info_arr[1].$time_info_arr[2].$time_info_arr[3];
			$data['type'] = $gonggao_time[$key]?$gonggao_time[$key]:1;
			$data['time'] = $time_info;
			$url = 'http://127.0.0.1/interface/ticket_gg.php';
			$return = curl_post($url,$data);
		}
	}
}
?>