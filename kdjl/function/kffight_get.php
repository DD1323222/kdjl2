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
ini_set('display_errors', false);
error_reporting(0);
require_once('../config/config.game.php');
require_once('../login/curl.php');
require_once('../sec/dblock_fun.php');
$kfFightBaseUrl = kdjlConfiguredServiceBaseUrl('KDJL_KF_FIGHT_BASE_URL');
if($kfFightBaseUrl === '') die('跨服战中心未配置');
$mem_welcome = kdjlSafeMemValue($_pm['mem']->get('db_welcome'), array());
if(!is_array($mem_welcome))
{
	die("内存错误");
}
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('登录状态无效！');
$user = $_pm['user']->getUserById($uid);
$bag = $_pm['user']->getUserBagById($uid);
if(!is_array($user)) die('玩家数据错误！');
$maxbag = isset($user['maxbag']) ? intval($user['maxbag']) : 0;
$bagNum=0;
if(is_array($bag))
{
	foreach($bag as $x => $y)
	{
		$sums = isset($y['sums']) ? intval($y['sums']) : 0;
		$zbing = isset($y['zbing']) ? intval($y['zbing']) : 0;
		if($sums>0 and $zbing == 0)
		{
			$bagNum++;
		}
	}
}
$snum = $maxbag - $bagNum;
if($snum < 3)
{
	die('请留至少三个空格子！');
}
function kdjlKfGivePrizeOrFail($task, $idlist, $uid)
{
	global $_pm;
	$uid = intval($uid);
	$lock = getLock($uid);
	if(!is_array($lock)) die('服务器繁忙，请稍后再试');
	$giveResult = $task->saveGetProps($idlist);
	if($giveResult !== true)
	{
		$_pm['mysql']->query('ROLLBACK');
		realseLock();
		die($giveResult === '200' ? '背包空间不足！' : '发放跨服战奖励失败！');
	}
	if(!$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		realseLock();
		die('保存跨服战领奖状态失败！');
	}
	realseLock();
	$_pm['mem']->del($uid.'bag');
}

$interface = $kfFightBaseUrl.'/kffight_get.php';
$nickname = isset($_SESSION['nickname']) ? $_SESSION['nickname'] : '';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
if(!preg_match('/^[A-Za-z0-9.-]{1,255}(:[0-9]{1,5})?$/', $host)) $host = 'localhost';
$respone = curl_get($interface."?username=".urlencode($nickname)."&host=".urlencode($host));
if($respone === false || $respone === '')
{
	die("战场接口暂时不可用，请稍后再试");
}
$respone = trim($respone);
if(strlen($respone) > 64) die("战场接口返回异常，请稍后再试");
$prize_arr = array();
$prize_name = '';
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
		$joinPrize = '';
		foreach($mem_welcome as $info)
		{
			if(is_array($info) && isset($info['code']) && $info['code'] == 'kf_join_prize')
			{
				$joinPrize = isset($info['contents']) ? $info['contents'] : '';
				break;
			}
		}
		if($joinPrize == '') die('跨服战参与奖配置错误！');
		$kf_task = new task;
		kdjlKfGivePrizeOrFail($kf_task, $joinPrize, $uid);
		die("领奖成功,你获得参与奖品已经发放进您的背包");
	}
}
foreach($mem_welcome as $info)
{
	if(is_array($info) && isset($info['code']) && $info['code'] == 'kf_fight_prize_config')
	{
		$ts_arr = explode('|',isset($info['contents']) ? $info['contents'] : '');
		foreach($ts_arr as $key => $val)
		{
			$prize_arr[$key+1] = explode(',',$val);
		}
	}
}
$respone_info = explode('|',$respone);
if(count($respone_info) < 2) die('跨服战奖励接口返回错误！');
$stage = intval($respone_info[0]);
$rank = intval($respone_info[1]);
switch($stage)
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
switch($rank)
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
if(!isset($prize_arr[$stage]) || !isset($prize_arr[$stage][$rank-1]) || $prize_arr[$stage][$rank-1] == '')
{
	die('跨服战奖励配置错误！');
}
kdjlKfGivePrizeOrFail($kf_task, $prize_arr[$stage][$rank-1], $uid);
die("领奖成功,你获得".$prize_name."奖品已经发放进您的背包");
?>
