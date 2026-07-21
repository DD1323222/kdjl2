<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2009.12.03
*@Update Date: 2009.12.03
*@Usage: for baby
*@Note: none
*/
error_reporting(7);
require_once('../config/config.game.php');
require_once('../login/curl.php');
header('Content-Type:text/html;charset=utf-8');
secStart($_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('登录状态无效！');
$legacyActivityBaseUrl = kdjlConfiguredServiceBaseUrl('KDJL_LEGACY_ACTIVITY_BASE_URL');
if($legacyActivityBaseUrl === '') die('旧活动中心未配置');
$legacyActivityPage = $legacyActivityBaseUrl.'/pmdatamanager/index.php';
$timerKey = 'tgtimes'.$uid;
$action = (isset($_GET['action']) && !is_array($_GET['action'])) ? $_GET['action'] : '';

if($action === 'go'){
	$check = 1;
	$arr = $_pm['mysql'] -> getOneRecord("SELECT active_lastvtime FROM player_ext WHERE uid = {$uid}");
	if(!is_array($arr) || empty($arr)){
		$check = 2;
	}else{
		if(empty($arr['active_lastvtime'])){
			//$_pm['mysql'] -> query("UPDATE player_ext SET active_lastvtime = ".time()." WHERE uid = {$_SESSION['id']}");
			$check = 3;
		}else{
			$time = time();
			$yes = date('Ymd',$arr['active_lastvtime']);
			$yes1 = date('Ymd',$time-3*24*3600);
			$ctime = $yes1 - $yes;
			if($ctime >= 0){
				//$_pm['mysql'] -> query("UPDATE player_ext SET active_lastvtime = ".time()." WHERE uid = {$_SESSION['id']}");
				$check = 3;
			}else{
				//die("1");//3天后再发送数据，直接进入活动页
				$gameareaParam = (isset($_GET['gamearea']) && !is_array($_GET['gamearea'])) ? $_GET['gamearea'] : '';
				$nameParam = (isset($_GET['name']) && !is_array($_GET['name'])) ? $_GET['name'] : '';
				header('Location: '.$legacyActivityPage.'?gamearea='.urlencode($gameareaParam).'&name='.urlencode($nameParam));
				exit;
			}
		}
	}


	$srctime = 20;
	#################增加一个间隔时间################
	$time1 = isset($_SESSION[$timerKey]) ? $_SESSION[$timerKey] : 0;
	if(empty($time1))
	{
		$_SESSION[$timerKey] = time();
	}
	else
	{
		$nowtime = time();
		$ctime1 = $nowtime - $time1;
		if($ctime1 < $srctime)
		{
			//die("100");//没有达到间隔时间
			die("系统繁忙!<script language='javascript'>setTimeout('window.close()',3000);</script>");
		}
		else
		{
			$_SESSION[$timerKey] = time();
		}
	}
	if($check > 1){
		$user = $_pm['mysql'] -> getOneRecord("SELECT name,nickname FROM player WHERE id = {$uid}");
		if(!is_array($user)) die('玩家数据错误！');
		$bbarr = $_pm['mysql'] -> getRecords("SELECT name,level,srchp,srcmp,ac,mc,hits,miss,czl,wx,effectimg FROM userbb WHERE uid = {$uid}");
		if(empty($bbarr)){
			//die('3');//数据有误
			$str = "数据有误，没有发送成功！!";
			$str .= '<br /><br />'.'<a href="'.htmlspecialchars($legacyActivityPage,ENT_QUOTES,'UTF-8').'">点此进入查看活动详情</a>';
			die($str);
		}
		$httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
		if(!preg_match('/^[A-Za-z0-9.-]{1,255}(:[0-9]{1,5})?$/', $httpHost)) $httpHost = 'localhost';
		$www=explode('.',$httpHost);
		$gamearea = $www[0];
		$str = 'gamearea='.urlencode($gamearea).'&name='.urlencode(urlencode($user['name'])).'&nickname='.urlencode(urlencode($user['nickname']));
		$i = 1;
		$czlcheck = 1;
		foreach($bbarr as $v){
			if(empty($v)){
				continue;
			}
			if($v['czl'] < 10){
				continue;
			}
			$czlcheck = 2;//?gamearea=一区1&name=leinchu&nickname=我来1&bbname=圣兽赤牝鹿1&hp=11910&mp=1114&ac=400&mc=900&hits=845&shanbi=429&grow=6.9&level=41&key=1211221&wx=火&key=
			$str .= '&bbname'.$i.'='.urlencode(urlencode($v['name'])).'&hp'.$i.'='.$v['srchp'].'&mp'.$i.'='.$v['srcmp'].'&ac'.$i.'='.$v['ac'].'&mc'.$i.'='.$v['mc'].'&hits'.$i.'='.$v['hits'].'&shanbi'.$i.'='.$v['miss'].'&grow'.$i.'='.$v['czl'].'&level'.$i.'='.$v['level'].'&wx'.$i.'='.$v['wx'].'&img'.$i.'='.$v['effectimg'];
			$i++;
		}
		if($czlcheck == 1){
			//die('3');//成都不能少于10
			$str = "您的所有宠物的成长都小于10，不能参加活动，赶快练习您的宠物吧！!";
			$str .= '<br /><br />'.'<a href="'.htmlspecialchars($legacyActivityPage,ENT_QUOTES,'UTF-8').'">点此进入查看活动详情</a>';
			die($str);
		}
		$key = '7sl+kb9adDAc7gLuv31MeEFPBMJZdRZyAx9eEmXSTui4423hgGfXF1pyM';
		$md5 = md5($str.$key);
		$str .= '&md5='.$md5;
		$data = $str;
		$out1 = curl_post($legacyActivityBaseUrl.'/loadbbdata.do',$data);
		if($out1 === false || trim($out1) === '') die('活动接口暂时不可用，请稍后再试。');
		$out1 = trim($out1);
		if(strpos($out1,'mark=0') !== false){
			$_pm['mysql'] -> query("INSERT INTO player_ext (uid,active_lastvtime,bbshow) VALUES({$uid},".time().",5) ON DUPLICATE KEY UPDATE active_lastvtime=VALUES(active_lastvtime)");
		}
		$out1 = str_replace(array("\r", "\n"), '', $out1);
		$outInfo = parse_url($out1);
		$baseInfo = parse_url($legacyActivityBaseUrl);
		$outPort = is_array($outInfo) && isset($outInfo['port']) ? intval($outInfo['port']) : ((is_array($outInfo) && isset($outInfo['scheme']) && strtolower($outInfo['scheme']) === 'https') ? 443 : 80);
		$basePort = isset($baseInfo['port']) ? intval($baseInfo['port']) : (strtolower($baseInfo['scheme']) === 'https' ? 443 : 80);
		if(!is_array($outInfo) || !isset($outInfo['scheme'],$outInfo['host']) || isset($outInfo['user']) || isset($outInfo['pass']) ||
			strtolower($outInfo['scheme']) !== strtolower($baseInfo['scheme']) ||
			strtolower($outInfo['host']) !== strtolower($baseInfo['host']) || $outPort !== $basePort)
		{
			$out1 = $legacyActivityPage.'?gamearea='.urlencode($gamearea).'&name='.urlencode($user['name']);
		}
		header('Location: '.$out1);
		exit;
	}
}
?>
