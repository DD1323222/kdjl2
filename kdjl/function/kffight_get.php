<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %xueyuan%

*@Write Date: 2011.08.31
*@Update Date: /
*@Usage: 跨服战场领奖页面
*请求后台公开接口
*/
ini_set('display_errors',true);
error_reporting(E_ALL);
require_once('../config/config.game.php');
require_once('../login/curl.php');
$mem_welcome = unserialize($_pm['mem']->get('db_welcome'));
if(!is_array($mem_welcome))
{
	die("内存错误");
}
$user = $_pm['user']->getUserById($_SESSION['id']);
$bag = $_pm['user']->getUserBagById($_SESSION['id']);
$bagNum=0;
if(is_array($bag))
{
	foreach($bag as $x => $y)
	{
		if($y['sums']>0 and $y['zbing'] == 0) 
		{
			$bagNum++;		
		}
	}
}
$snum = $user['maxbag'] - $bagNum;
if($snum < 3)
{
	die('请留至少三个空格子！');
}
$interface = "http://pmmg1.webgame.com.cn/interface/kffight_get.php";
$respone = curl_get($interface."?username=".urlencode($_SESSION['nickname'])."&host=".$_SERVER['HTTP_HOST']);
switch($respone)
{
	case 'no_stat' :
	{
		die("请本次决赛之后领取奖励");
	}
	case 'noopen' :
	{
		die("本次战场尚未开启");
	}
	case 'nobm' :
	{
		die("您上次没有参赛");
	}
	case 'has' :
	{
		die("您已经领取过对应奖励了,感谢您的参加");
	}
	case '5':
	{
		foreach($mem_welcome as $info)
		{
			if( $info['code'] == 'kf_join_prize')
			{		
				$kf_task = new task;
				$kf_task->saveGetProps($info['contents']);
				$prize_name = "参与奖";
				break;
			}
		}
	}
}
foreach($mem_welcome as $info)
{
	if($info['code'] == 'kf_fight_prize_config')
	{
		$ts_arr = explode('|',$info['contents']);
		foreach($ts_arr as $key => $val)
		{
			$prize_arr[$key+1] = explode(',',$val);
		}
	}
}
$respone_info = explode('|',$respone);
switch($respone_info[0])
{
	case 1 :
	{
		$prize_name = '第一阶段-';break;
	}
	case 2 :
	{
		$prize_name = '第二阶段-';break;
	}
	case 3 :
	{
		$prize_name = '第三阶段-';break;
	}
}
switch($respone_info[1])
{
	case 1 :
	{
		$prize_name .= '[冠军奖]';break;
	}
	case 2 :
	{
		$prize_name .= '[亚军奖]';break;
	}
	case 3 :
	{
		$prize_name .= '[季军奖]';break;
	}
	case 4 :
	{
		$prize_name .= '[精英奖]';break;
	}
}
$kf_task = new task;
$kf_task->saveGetProps($prize_arr[$respone_info[0]][$respone_info[1]-1]);
die("领奖成功,你获得".$prize_name."奖品已经发放进您的背包");
?>