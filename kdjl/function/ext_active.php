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
header('Content-Type:text/html;charset=gbk');
secStart($_pm['mem']);
$action = isset($_GET['action']) ? $_GET['action'] : '';

if($action === 'go'){
	$check = 1;
	$arr = $_pm['mysql'] -> getOneRecord("SELECT active_lastvtime FROM player_ext WHERE uid = {$_SESSION['id']}");
	if(!is_array($arr) || empty($arr)){
		//$_pm['mysql'] -> query("REPLACE INTO player_ext (uid,active_lastvtime,bbshow) values({$_SESSION['id']},".time().",5)");
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
				header('location:http://pmhd.webgame.com.cn/pmdatamanager/index.php?gamearea='.$_GET['gamearea'].'&name='.$_GET['name']);
				exit;
			}
		}
	}
	
	
	$srctime = 20;
	#################增加一个间隔时间################
	$time1 = $_SESSION['tgtimes'.$_SESSION['id']];
	if(empty($time1))
	{	
		$_SESSION['tgtimes'.$_SESSION['id']] = time();
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
			$_SESSION['tgtimes'.$_SESSION['id']] = time();
		}
	}
	if($check > 1){
		$user = $_pm['mysql'] -> getOneRecord("SELECT name,nickname FROM player WHERE id = {$_SESSION['id']}");
		$bbarr = $_pm['mysql'] -> getRecords("SELECT name,level,srchp,srcmp,ac,mc,hits,miss,czl,wx,effectimg FROM userbb WHERE uid = {$_SESSION['id']}");
		if(empty($bbarr)){
			//die('3');//数据有误
			$str = "数据有误，没有发送成功！!";
			$str .= '<br /><br />'.'<a href="http://pmhd.webgame.com.cn/pmdatamanager/index.php">点此进入查看活动详情</a>';
			die($str);
		}
		$www=explode('.',$_SERVER['HTTP_HOST']);
		$gamearea = $www[0];
		$str = 'gamearea='.$gamearea.'&name='.urlencode(urlencode(iconv('gbk','utf-8',$user['name']))).'&nickname='.urlencode(urlencode(iconv('gbk','utf-8',$user['nickname'])));
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
			$str .= '&bbname'.$i.'='.urlencode(urlencode(iconv('gbk','utf-8',$v['name']))).'&hp'.$i.'='.$v['srchp'].'&mp'.$i.'='.$v['srcmp'].'&ac'.$i.'='.$v['ac'].'&mc'.$i.'='.$v['mc'].'&hits'.$i.'='.$v['hits'].'&shanbi'.$i.'='.$v['miss'].'&grow'.$i.'='.$v['czl'].'&level'.$i.'='.$v['level'].'&wx'.$i.'='.$v['wx'].'&img'.$i.'='.$v['effectimg'];
			$i++;
		}
		if($czlcheck == 1){
			//die('3');//成都不能少于10
			$str = "您的所有宠物的成长都小于10，不能参加活动，赶快练习您的宠物吧！!";
			$str .= '<br /><br />'.'<a href="http://pmhd.webgame.com.cn/pmdatamanager/index.php">点此进入查看活动详情</a>';
			die($str);
		}
		$key = '7sl+kb9adDAc7gLuv31MeEFPBMJZdRZyAx9eEmXSTui4423hgGfXF1pyM';
		$md5 = md5($str.$key);
		$str .= '&md5='.$md5;
		//$url = 'http://pmhd.webgame.com.cn/loadbbdata.do?'.$str;
		/*$content = curls($url);
		//$content = file_get_contents($url);
		echo $content;*/
		
		
		$data = $str;//echo $data;exit;
		$fp = fsockopen("pmhd.webgame.com.cn", 80, $errno, $errstr, 30);
		if(!$fp) die('error:'.$errstr);
		$out = "POST /loadbbdata.do HTTP/1.1\r\n";
		$out .= "Host: pmhd.webgame.com.cn\r\n";
		$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$out .= "Content-Length: ".strlen($data)."\r\n";
		$out .="Connection: Close \r\n\r\n";
		$out .= $data."\r\n";
		fwrite($fp, $out);
		while (!feof($fp)) {
			$out1 .= fgets($fp, 128);
		}
		$out1=explode("\r\n\r\n",$out1,2);
		$out1=$out1[1];
		fclose($fp);
		if(strpos($out1,'mark=0') !== false){
			if($check == 2){
				$_pm['mysql'] -> query("INSERT INTO player_ext (uid,active_lastvtime,bbshow) values({$_SESSION['id']},".time().",5)");
			}else{
				$_pm['mysql'] -> query("UPDATE player_ext SET active_lastvtime = ".time()." WHERE uid = {$_SESSION['id']}");
				//echo "UPDATE player_ext SET active_lastvtime = ".time()." WHERE uid = {$_SESSION['id']}"."<br />";
			}
		}
		//echo $out1;
		header('location:'.$out1);
		exit;
	}
}
?>
