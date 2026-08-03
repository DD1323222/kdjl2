<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.05.29
*@Usage:Fightting Display
*@Note: none
Mem style.
*/
session_start();
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1)
{
	die('');
}
$_SESSION['id'] = $uid;
define('MEM_BOSS_KEY', $uid . 'boss');
define('MEM_FIGHT_KEY', $uid . 'fight'); // 保存战斗信息。
//if ($_SESSION['nickname'] !='GM') exit();

require_once('../config/config.game.php');
require_once('../config/config.fuben.php');
require_once(dirname(__FILE__).'/fight_wait_common.php');
require_once(dirname(__FILE__).'/boss_refresh_common.php');

function fbfightModJsSingle($value)
{
	return str_replace(array('\\', "'", "\r", "\n", '<', '>'), array('\\\\', "\\'", '', '', '\\x3C', '\\x3E'), strval($value));
}

function fbfightModImage($value)
{
	$value = basename(strval($value));
	return preg_match('/^[A-Za-z0-9_.-]+$/', $value) ? $value : '';
}

secStart($_pm['mem']);
//加速外挂
$time = time();
$_SESSION['multi_monsters'.$uid] = 2;

$user	= $_pm['user']->getUserById($uid);
if(!is_array($user))
{
	die('');
}
foreach(array('mapinfo'=>'','openmap'=>'','inmap'=>0,'mbid'=>0,'autofitflag'=>0,'maxautofitsum'=>0,'sysautosum'=>0) as $fightUserKey=>$fightUserDefault)
{
	if(!isset($user[$fightUserKey])) $user[$fightUserKey] = $fightUserDefault;
}
$requestedMapid = (isset($_GET['mapid']) && !is_array($_GET['mapid'])) ? abs(intval($_GET['mapid'])) : 0;
$currentMapid = $requestedMapid > 0 ? $requestedMapid : (isset($user['inmap']) ? abs(intval($user['inmap'])) : 0);
$validFubenMap = false;
foreach($fbinfo as $fbRow)
{
	if(is_array($fbRow) && isset($fbRow['id']) && intval($fbRow['id']) == $currentMapid)
	{
		$validFubenMap = true;
		break;
	}
}
if(!$validFubenMap || $currentMapid < 1)
{
	echo '<center>您的帐号非法操作！</center>';
	exit();
}

$sqlMap = "SELECT multi_monsters FROM map WHERE id = ".$currentMapid;
$map = $_pm['mysql'] -> getOneRecord($sqlMap);
if(!$map)
{
	echo '<center>您的帐号非法操作！</center>';
	exit();
}
if(!isset($map['multi_monsters'])) $map['multi_monsters'] = 0;
if($requestedMapid > 0)
{
	if(!$_pm['mysql']->query("UPDATE player SET inmap={$currentMapid} WHERE id={$uid}"))
	{
		die('');
	}
	$_pm['mem']->del(MEM_USER_KEY);
	$user['inmap'] = $currentMapid;
}

$chaoshenchongDituFlag=false;
$requestBid = (isset($_REQUEST['p']) && !is_array($_REQUEST['p'])) ? intval($_REQUEST['p']) : 0;
$sessionFight = isset($_SESSION['fight'.$uid]) && is_array($_SESSION['fight'.$uid]) ? $_SESSION['fight'.$uid] : array();
$sessionBid = isset($sessionFight['bid']) ? intval($sessionFight['bid']) : 0;
$bid = $requestBid > 0 ? $requestBid : $sessionBid;

$sql = "SELECT level,wx
		FROM userbb
		WHERE uid = ".$uid." and id = {$bid} and muchang = 0 and COALESCE(tgflag,0) = 0";
$petsleval = $_pm['mysql'] -> getOneRecord($sql);

if($map['multi_monsters']==4)
{
	if(!is_array($petsleval) || $petsleval['wx']!=7)
	{
		die("<script language='javascript'>parent.Alert('只有神圣宠物,才可以在这里战斗！');window.location='/function/fb_Mod.php?mapid=".$currentMapid."'</script>");
	}
	$chaoshenchongDituFlag=true;
}
else
{
	$userInmap = isset($user['inmap']) ? intval($user['inmap']) : 0;
	if ($userInmap != $currentMapid)
	{
		unset($_SESSION['id']);
		$_pm['mem']->memClose();
		echo '<center>您的帐号非法操作！</center>';
		exit();
	}
}
$userbb = $_pm['user']->getUserPetById($uid);
$bag    = $_pm['user']->getUserBagById($uid);
$fight	=	isset($_SESSION['fight'.$uid]) && is_array($_SESSION['fight'.$uid]) ? $_SESSION['fight'.$uid] : array();
$reentryClockKey = 'fight_reentry_clock'.$uid;
$_SESSION['fttime'.$uid] = 5;

//#########################
if (!empty($fight))
{
	   // Check time
	   if (!isset($fight['ftime']) || intval($fight['ftime']) <= 0) {
			$fight['ftime'] = time();
			$_SESSION['fight'.$_SESSION['id']] = $fight;
	   }
	   if (isset($fight['fatting']) && intval($fight['fatting']) == 0) {
			$fight = kdjlFightBeginPostWait($fight);
			$_SESSION['fight'.$_SESSION['id']] = $fight;
	   }
	   $will = kdjlFightEntryWaitRemaining($fight, $user, false, $bid, '');
	   if ($will > 0) {
		$end='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0

Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-

transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<!--[if IE 6]><script type="text/javascript">try{ document.execCommand

("BackgroundImageCache", false, true); } catch(e) {}
</script>
<![endif]-->
<body style="background-color: #FFFCEB;margin-top:0px;">
<center>
  <div style="margin-top:140px;"><img src="../images/ui/fight/loading.gif"/><div id="timev" style="position:absolute; text-align:center; color:#F98F2C; font-weight:bold;font-size:2em;left:360px; top:140px; width:70px; height:70px; line-height:70px; padding:0;"></div>
</div>
</center>
</body>
</html>
<script language="javascript">
var readH;
var pt=0;
function loadtime(m){
	m = parseInt(m, 10);
	if(isNaN(m) || m < 1) m = 1;
	document.getElementById("timev").innerHTML = m;
	if(m <= 1 && pt==0)
	{
		window.clearTimeout(readH);
		window.setTimeout("pause(0)",1000);
		return;
	}
	readH=window.setTimeout("loadtime("+(m-1)+");", 1000);
}
function pause(m)
{   if (pt==1) return;
	if(m == 0){
		window.parent.document.getElementById("gw").src="./function/fbfight_Mod.php?p='.$bid.'&s=t";
	}
	pt=1;
 }
loadtime('.$will.');
</script>';
			ob_start('ob_gzip');
			echo $end;
			ob_end_flush();
			exit();
		}
}
//########################


//$mapid = intval($_REQUEST['mapid']);
$err = 0;
//if($mapid == "" || $mapid <= 0)
//{
//	$err = "0";//没有对应的副本，返回到地图;
//}
if($bid == "" || $bid <= 0)
{
	$err = "1";//没有选择主战宠物
}


//判断玩家所选宠物是否达到所对应的副本的级数要求
//得到玩家当前宠物的级数
if($err == 0){


	//得到当前副本所需要的宠物的级数
	$sql = "SELECT map.level FROM map,player WHERE map.id =player.inmap and player.id=".$uid;
	$mapleval = $_pm['mysql'] -> getOneRecord($sql);

	if(!is_array($petsleval) || !is_array($mapleval))
	{
		$err = "2";//信息出错
	}

	$petLevel = isset($petsleval['level']) ? intval($petsleval['level']) : 0;
	$mapNeedLevel = isset($mapleval['level']) ? intval($mapleval['level']) : 0;
	if($petLevel < $mapNeedLevel)
	{
		$err = "3";//您当前所选宠物没有达到相应的级数
	}

}
if($err == 0){
	//判断副本刷新时间
	//得到该副本所需要的刷新的时间
	$sql = "SELECT fuben.gwid,fuben.srctime,fuben.lttime FROM fuben,player WHERE fuben.inmap = player.inmap and fuben.inmap = ".$user['inmap']."  and fuben.uid = ".$uid;
	$fuben = $_pm['mysql'] -> getOneRecord($sql);

	if(is_array($fuben))
	{//当玩家玩到该副本的最后一个宠物时将不会记录他的怪物
		if(empty($fuben['gwid']))
		{
			$srctime = $fuben['srctime'];
			$nowtime = time();
			$time = $nowtime - $fuben['lttime'];//实际间隔时间
			if($time < $srctime)
			{
				$err = "4";//副本地图正在刷新!
			}
			else
			{
				/*$sql = "UPDATE fuben
						SET lttime = $nowtime,gwid = ''
						WHERE uid = {$_SESSION['id']} and inmap = {$mapid}";
				$_pm['mysql'] -> query($sql);*/
				$err = 10;
			}
		}
		else
		{
			$err = 10;
		}
	}
	else
	{
		$err = 10;
	}

	$petWx = isset($petsleval['wx']) ? intval($petsleval['wx']) : 0;
	if($chaoshenchongDituFlag&&$petWx<7)
	{
		$err = "44";//只有神圣宠物物才能进入
	}
}
if($err == 44){
	die("<script language='javascript'>parent.Alert('只有神圣宠物,才可以在这里战斗！');window.location='/function/fb_Mod.php?mapid=".$currentMapid."'</script>");
}
if($err == 4){
	die("<script language='javascript'>parent.Alert('副本刷新中!');window.location='/function/fb_Mod.php?mapid=".$currentMapid."'</script>");
}
if($err != 10){
	echo '<!--stopUser2(51='.$err.');-->';
	stopUser2(51);
	die("您现在无法进入该地图(".$err.")！");
}




// Get bb info.
$bid = (isset($_REQUEST['p']) && !is_array($_REQUEST['p'])) ? intval($_REQUEST['p']) : 0;
$arrobj = new arrays();

$bb = $arrobj->dataGet(array('k' => MEM_BB_KEY,
							 'v' => "if

(\$rs['id'] == '{$bid}' && \$rs['uid'] == '{$_SESSION['id']}')

\$ret=\$rs;"
					        ),
							$userbb
					  );
if (!is_array($bb))
{
	if (!empty($fight))
	{
		$bid = isset($_SESSION['fight'.$_SESSION['id']]['bid']) ? $_SESSION['fight'.$_SESSION['id']]['bid'] : 0;
	}
	else $bid = $user['mbid'];
	$bb = $arrobj->dataGet(array('k' => MEM_BB_KEY,
								 'v' =>

"if(\$rs['id'] == '{$bid}' && \$rs['uid'] == '{$_SESSION['id']}')

\$ret=\$rs;"
								),
							$userbb
					     );
	if (!is_array($bb))
	{
		die('不能获得宠物数据！');
	}
}
if((isset($bb['muchang']) && intval($bb['muchang']) != 0) || (isset($bb['tgflag']) && intval($bb['tgflag']) != 0))
{
	die('宠物当前状态不能战斗！');
}
$arr = getzbAttrib($bid);
	$arrHp = max(0, intval(round(isset($arr['hp']) ? $arr['hp'] : 0)));
	$arrMp = max(0, intval(round(isset($arr['mp']) ? $arr['mp'] : 0)));
	$baseSrcMp = intval($bb['srcmp']);
	$bb['srchp'] += $arrHp;
	$bb['srcmp'] += $arrMp;
	$bb['hp'] += $arrHp;
	$bb['mp'] += $arrMp;
	//宠物的血量和魔法的最大值的计算（加上装备的效果）；
	/*$sql = "SELECT addmp,addhp FROM userbb WHERE uid = {$_SESSION['id']} and id = {$bid}";
	$add = $_pm['mysql'] -> getOneRecord($sql);
	$bb['hp'] += $add['addhp'];
	$bb['mp'] += $add['addmp'];*/



	//if ($bb['hp'] <= 0) err($_bbword[rand(0,count($_bbword)-1)]);
	//金币版

	$autoTypeKey = 'exptype'.$uid;
	$autoWayKey = 'way'.$uid;
	$autoType = isset($_SESSION[$autoTypeKey]) ? intval($_SESSION[$autoTypeKey]) : 0;
	$autoWay = isset($_SESSION[$autoWayKey]) ? $_SESSION[$autoWayKey] : '';
	$attackWaitLimit = kdjlFightAttackWaitLimit($user, false, $fight, '');
		if($autoType == 1)
	{
		if(($autoWay == '' || $autoWay == "money") && $user['autofitflag']==1 && $user['sysautosum']>0)
		{
			$_SESSION['fttime'.$uid] = $attackWaitLimit;
			$moneyTotalMp = intval($bb['srcmp'] / 2);
			$moneyBaseMp = min($baseSrcMp, $moneyTotalMp);
			$moneyAddMp = max(0, $moneyTotalMp - $moneyBaseMp);
			$_pm['mysql']->query("UPDATE userbb
						 SET hp=srchp,mp={$moneyBaseMp},addhp={$arrHp},addmp={$moneyAddMp}
					   WHERE id={$bid} and uid={$_SESSION['id']}");
			$bb['hp'] = $bb['srchp'];
			$bb['mp'] = $moneyTotalMp;
		}
		//元宝版
		else if($autoWay == "yb" && $user['autofitflag']==1 && $user['maxautofitsum']>0)
		{
			$_SESSION['fttime'.$uid] = $attackWaitLimit;
			$_pm['mysql']->query("UPDATE userbb
						 SET hp=srchp,mp=srcmp,addhp={$arrHp},addmp={$arrMp}
					   WHERE id={$bid} and uid={$_SESSION['id']}");
			$bb['hp'] = $bb['srchp'];
			$bb['mp'] = $bb['srcmp'];
		}
	}
	else
	{
		$_pm['mysql']->query("UPDATE userbb
					 SET addhp={$arrHp},addmp={$arrMp}
				   WHERE id={$bid} and uid={$_SESSION['id']}");
	}

	// By field order.
	$bb['wx'] = getWx($bb['wx']);
	$bbNameJs = fbfightModJsSingle($bb['name']);
	$bbWxJs = fbfightModJsSingle($bb['wx']);
	$bbSkillJs = fbfightModJsSingle($bb['skillist']);
	$bbImgStand = fbfightModImage($bb['imgstand']);
	$bbImgAck = fbfightModImage($bb['imgack']);
	$bbImgDie = fbfightModImage($bb['imgdie']);
	$bbinfo = "['{$bbNameJs}',{$bb['level']},'{$bbWxJs}',{$bb['ac']},{$bb['mc']},{$bb['hp']},{$bb['mp']},'{$bbSkillJs}','{$bbImgStand}','{$bbImgAck}','{$bbImgDie}',{$bid},'{$bb['srchp']}','{$bb['srcmp']}','{$bb['nowexp']}','{$bb['lexp']}']";
// Get detail jn info.

$jlist = '';
$jnlist = '';
$tjn = explode(",", $bb['skillist']);
foreach($tjn as $mkey => $n)
{
	$tt = explode(":", $n);
	$jlist .= $tt[0] . ",";
}
$jlist =	rtrim($jlist, ',');
$bjn   =	$_pm['user']->getUserPetSkillById($_SESSION['id']);

if (!is_array($bjn))
{
	Header("Location:fbfight_Mod.php?a=1&p={$bid}");exit();
}

$jlistarr = explode(',', $jlist);
foreach($bjn as $k => $rs)
{
	if($rs['sid'] == '112'){
		continue;
	}
	if (in_array($rs['sid'], $jlistarr) &&
		$rs['bid'] == $bid && $rs['vary'] != 4
	   )
	{
		if ($rs['value']!='')
		{
			if(strstr($rs['value'],":"))
			{
				$ak = explode(":", $rs['value']);
				$rs['value']=$ak[count($ak)-1];
			}
		}
		else $rs['value']=0;

		 $rs['value'] = str_replace("%","0",$rs['value']);
		$skillNameJs = fbfightModJsSingle($rs['name']);
		$skillValueJs = fbfightModJsSingle($rs['value']);
		$skillPlusJs = fbfightModJsSingle($rs['plus']);
		$skillImg = fbfightModImage($rs['img']);
		$jnlist .="['{$skillNameJs}',{$rs['level']},'{$rs['vary']}',{$rs['wx']},'{$skillValueJs}','{$skillPlusJs}','{$skillImg}',{$rs['uhp']},{$rs['ump']},{$rs['sid']}],";
	}
}
$jnlist = rtrim($jnlist, ','); // []#[];

// from current map choose level limit.
//根据玩家所在地图刷新怪物

$sql = "SELECT time FROM fight_log WHERE uid = ".$uid." and vary = 2";
$timearr = $_pm['mysql'] -> getOneRecord($sql);
if(is_array($timearr)){
	$time = time();
	$lastFightTime = isset($timearr['time']) ? intval($timearr['time']) : 0;
	$ctime = $time - $lastFightTime;
	if($ctime < 1){
		die('操作太快！<!--'.$lastFightTime.'-'.$time.'-->');
		//$_SESSION['id'] = '';
	}else{
		$_pm['mysql'] -> query("UPDATE fight_log SET time = ".time()." WHERE uid = ".$uid." and vary = 2");
	}
}else{
	$_pm['mysql'] -> query("INSERT INTO fight_log (uid,time,vary) VALUES(".$uid.",".time().",2)");
}

/*$levels = $_pm['mem']->dataGet(array('k' => MEM_MAP_KEY, //地图的相关信息
							'v' => "if



(\$rs['id'] == '{$user['inmap']}') \$ret=\$rs;));"*/
//$memmapid = unserialize($_pm['mem']->get('db_mapid'));
//将取多条数据改为取单条数据
$baseMapInfo =  getBaseMapInfoById($user['inmap']);
if(!is_array($baseMapInfo))
{
	die("地图配置错误(".$user['inmap'].")");
}
if(!isset($baseMapInfo['id'])) $baseMapInfo['id'] = intval($user['inmap']);
if(!isset($baseMapInfo['gpclist'])) $baseMapInfo['gpclist'] = '';
$memmapid[$user['inmap']] = $baseMapInfo;
$levels = $memmapid[$user['inmap']];
if(!is_array($levels) || count($levels)<1)
{
	die("地图配置错误(".$user['inmap'].")");
}

if(empty($levels['gpclist']))//说明是副本地图
{
	foreach($fbinfo as $fb)
	{
		if($fb['id'] == $levels['id'])
		{
			$gwlist1 = $fb['gwid'];//得到在该副本地图中的所有怪物ID
			break;
		}
	}
}
else
{
	die("载入地图(".$user['inmap'].")出错!");
}
if(!isset($gwlist1) || trim($gwlist1) == '')
{
	die("副本配置错误(".$user['inmap'].")");
}
$gwlist = array();
foreach(explode(",",$gwlist1) as $gwid)
{
	$gwid = intval(trim($gwid));
	if($gwid > 0) $gwlist[] = $gwid;
}
if(empty($gwlist))
{
	die("副本配置错误(".$user['inmap'].")");
}
/**###################################
*Level limit lock
###################################*/

// $idse = rand($lvl[0], $lvl[1]); // 得到怪物
$sql = "SELECT *
		FROM fuben
		WHERE uid = ".$uid." and inmap = {$levels['id']}";
$fbexist = $_pm['mysql'] -> getOneRecord($sql);
if(is_array($fbexist))
{
	foreach($gwlist as $kgw => $vgw)
	{
		if($vgw == $fbexist['gwid'])
		{
			$numgw = $kgw;
			break;
		}
		else
		{
			$numgw = count($gwlist);
		}
	}

	$n = count($gwlist) - 1;
	$nowtime = time();
	$time = $nowtime - $fbexist['lttime'];//实际间隔时间
	$waittime = $fbexist['srctime'] - $time;//实际需要等待时间
	if($numgw > $n)
	{//判断副本是否处于刷新时间
		//$sql = "SELECT * FROM fuben WHERE uid = {$_SESSION['id']} and inmap = {$user['inmap']}";
		//$wait = $_pm['mysql'] -> getOneRecord($sql);
		if($time >= $fbexist['srctime'])
		{
			$numgw = 0;
			$firstGid = intval($gwlist[0]);
			$oldGid = isset($fbexist['gwid']) ? intval($fbexist['gwid']) : 0;
			$oldLttime = isset($fbexist['lttime']) ? intval($fbexist['lttime']) : 0;
			$sql = "UPDATE fuben
					SET lttime = 0,gwid = {$firstGid}
					WHERE uid = {$uid} and inmap = {$user['inmap']}
					  and COALESCE(gwid,0) = {$oldGid}
					  and COALESCE(lttime,0) = {$oldLttime}";
			if(!$_pm['mysql']->query($sql))
			{
				die("副本进度刷新失败！");
			}
		}
		else
		{
			die("副本刷新中,您还需要等待{$waittime}秒!");
		}
	}
	else
	{
		if($time >= $fbexist['srctime'])
		{
			$numgw = 0;
			$firstGid = intval($gwlist[0]);
			$oldGid = isset($fbexist['gwid']) ? intval($fbexist['gwid']) : 0;
			$oldLttime = isset($fbexist['lttime']) ? intval($fbexist['lttime']) : 0;
			$sql = "UPDATE fuben
					SET lttime = 0,gwid = {$firstGid}
					WHERE uid = {$uid} and inmap = {$user['inmap']}
					  and COALESCE(gwid,0) = {$oldGid}
					  and COALESCE(lttime,0) = {$oldLttime}";
			if(!$_pm['mysql']->query($sql))
			{
				die("副本进度刷新失败！");
			}
		}
	}
}
else
{
	$numgw = 0;
}

$idse = isset($gwlist[$numgw]) ? trim($gwlist[$numgw]) : '';//得到当前玩家要遇到的怪物
if($idse == '' || intval($idse) < 1)
{
	Header("Location:fbfight_Mod.php?a=3&p={$bid}");exit();
}
$fightState = isset($_SESSION['fight' . $_SESSION['id']]) && is_array($_SESSION['fight' . $_SESSION['id']]) ? $_SESSION['fight' . $_SESSION['id']] : array();
if(isset($fightState['gid']) && isset($fightState['hp']) && $fightState['gid']==$idse && $fightState['hp']>0)//当前打的怪物是数据库取出来的怪物,并且怪物没有死,说明玩家按了后退
{
	if (isset($fightState['battle_started_at'])) {
		$startedAt = floatval($fightState['battle_started_at']);
		if ($startedAt > 0 && $startedAt <= microtime(true)) {
			$_SESSION[$reentryClockKey] = array(
				'gid'=>intval($idse),
				'map'=>intval($levels['id']),
				'started_at'=>$startedAt
			);
		}
	}
	unset($_SESSION['fight' . $_SESSION['id']]);
	header("refresh:2;url=fbfight_Mod.php?a=2&p={$bid}");
	exit('加载中...');
}

$_SESSION['gwcdie'.$_SESSION['id']] = $idse;
/*$gw = $_pm['mem']->dataGet(array('k' => MEM_GPC_KEY,
						   'v' => "if(\$rs

['id'] == '{$idse}') \$ret=\$rs;"
					));*/
//$memgpcid = unserialize($_pm['mem']->get('db_gpcid'));
$idse = trim($idse);
//$gw = $memgpcid[$idse];
$gw = getBaseGpcInfoById($idse);//改为单条取记录

if (!is_array($gw) || count($gw)<1)
{
	Header("Location:fbfight_Mod.php?a=3&p={$bid}");exit();
}
else
{

	$gw['wx'] = getWx($gw['wx']);

	$gwNameJs = fbfightModJsSingle($gw['name']);
	$gwWxJs = fbfightModJsSingle($gw['wx']);
	$gwSkillJs = fbfightModJsSingle($gw['skill']);
	$gwImgStand = fbfightModImage($gw['imgstand']);
	$gwImgAck = fbfightModImage($gw['imgack']);
	$gwImgDie = fbfightModImage($gw['imgdie']);
	$gwinfo="['{$gwNameJs}',{$gw['level']},'{$gwWxJs}',{$gw['ac']},{$gw['mc']},{$gw['hp']},{$gw['mp']},'{$gwSkillJs}','{$gwImgStand}','{$gwImgAck}','{$gwImgDie}',{$gw['id']}]";


	$test = isset($_SESSION['fight'.$_SESSION['id']]) && is_array($_SESSION['fight'.$_SESSION['id']]) ? $_SESSION['fight'.$_SESSION['id']] : array();
	//Update fightting stats.
	if (empty($test))
	{
		$newFight = array('uid'=>$_SESSION['id'],
						'bid'=>$bid,
						'gid'=>$gw['id'],
						'hp' =>$gw['hp'],
						'mp' =>$gw['mp'],
						'fuzu'=>0,
						'fatting'=>1,
						'boss'=>$gw['boss'],
						'ftime'=>time());
		if (isset($_SESSION[$reentryClockKey]) && is_array($_SESSION[$reentryClockKey])) {
			$clock = $_SESSION[$reentryClockKey];
			$startedAt = isset($clock['started_at']) ? floatval($clock['started_at']) : 0;
			if (
				isset($clock['gid'], $clock['map'])
				&& intval($clock['gid']) == intval($gw['id'])
				&& intval($clock['map']) == intval($levels['id'])
				&& $startedAt > 0
				&& $startedAt <= microtime(true)
			) {
				$newFight['battle_started_at'] = $startedAt;
			}
			unset($_SESSION[$reentryClockKey]);
		}
		$_SESSION['fight'.$_SESSION['id']] = kdjlFightStartState($newFight, $user, false, '');
	}
	else
	{
	   // Check time
	   $fight = $test;
	   if (!isset($fight['ftime']) || intval($fight['ftime']) <= 0) {
			$fight['ftime'] = time();
			$_SESSION['fight'.$_SESSION['id']] = $fight;
	   }
	   if (isset($fight['fatting']) && intval($fight['fatting']) == 0) {
			$fight = kdjlFightBeginPostWait($fight);
			$_SESSION['fight'.$_SESSION['id']] = $fight;
	   }
	   $will = kdjlFightEntryWaitRemaining($fight, $user, false, $bid, '');
	   if ($will > 0) {
		$end='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0

Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-

transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<!--[if IE 6]><script type="text/javascript">try{ document.execCommand

("BackgroundImageCache", false, true); } catch(e) {}
</script>
<![endif]-->
<body style="background-color: #FFFCEB;margin-top:0px;">
<center>
  <div style="margin-top:140px;"><img src="../images/ui/fight/loading.gif"/><div id="timev" style="position:absolute; text-align:center; color:#F98F2C; font-weight:bold;font-size:2em;left:360px; top:140px; width:70px; height:70px; line-height:70px; padding:0;"></div>
</div>
</center>
</body>
</html>
<script language="javascript">
var readH;
var pt=0;
function loadtime(m){
	m = parseInt(m, 10);
	if(isNaN(m) || m < 1) m = 1;
	document.getElementById("timev").innerHTML = m;
	if(m <= 1 && pt==0)
	{
		window.clearTimeout(readH);
		window.setTimeout("pause(0)",1000);
		return;
	}
	readH=window.setTimeout("loadtime("+(m-1)+");", 1000);
}
function pause(m)
{   if (pt==1) return;
	if(m == 0){
		window.parent.document.getElementById("gw").src="./function/fbfight_Mod.php?p='.$bid.'&s=t";
	}
	pt=1;
 }
loadtime('.$will.');
</script>';
			ob_start('ob_gzip');
			echo $end;
			ob_end_flush();
			exit();
		}

		$r['bid']		=$bid;
		$r['gid']		=$gw['id'];
		$r['hp']		=$gw['hp'];
		$r['mp']		=$gw['mp'];
		$r['fatting']=1;
		$r['ftime']	=time();
		$r['fuzu']	=0;
		$r['boss']	=$gw['boss'];
		//$fight=$r;
		$r = kdjlFightPreserveStartedAt($r, $fight);
		$_SESSION["fight".$_SESSION['id']]=kdjlFightStartState($r, $user, false, '');
	}
}
//$_SESSION["fight".$_SESSION['id']]=$fight;
$bbfzp = "";
$catcharr = "";
$currentCatchGpcId = isset($_SESSION['fight'.$uid]['gid']) ? intval($_SESSION['fight'.$uid]['gid']) : 0;
// Get bag props.
if (is_array($bag))
{
	foreach ($bag as $k => $v)
	{
		if(!is_array($v)) continue;
		if(!isset($v['varyname'])) $v['varyname'] = 0;
		if(!isset($v['sums'])) $v['sums'] = 0;
		if(!isset($v['id'])) $v['id'] = 0;
		if(!isset($v['name'])) $v['name'] = '';
		$v['name'] = fbfightModJsSingle($v['name']);
		if ($v['varyname'] == 1 && $v['sums']>0)
		{
			if (empty($bbfzp)) $bbfzp = "['".$v

['name']."',".$v['sums'].','.$v['id']."]";
			else $bbfzp .= ",['".$v['name']."',".$v

['sums'].','.$v['id']."]";
		}
		else if ($v['varyname'] == 3 && $v['sums']>0 &&
			kdjlCatchPropTargetsGpc(isset($v['effect']) ? $v['effect'] : '', $currentCatchGpcId))
		{
			if (empty($catcharr)) $catcharr = "['".$v

['name']."',".$v['sums'].','.$v['id']."]";
			else $catcharr .= ",['".$v['name']."',".$v

['sums'].','.$v['id']."]";
		}
	}

}else{
	$bbfzp='0';
	$catcharr='0';
}
//
$user['fightbb'] = $bid;
$_SESSION['mbid'] = $bid;
$_pm['mysql']->query("UPDATE player
			   SET fightbb={$bid}
			 WHERE id={$_SESSION['id']}
		  ");
//update fight status to memory.
//$_pm['mem']->set(array('k' =>MEM_USER_KEY, 'v' => $user));
//$_pm['mem']->set(array('k' =>MEM_USERBB_KEY, 'v' => $userbb));
//$_pm['mem']->set(array('k' =>MEM_USERBAG_KEY, 'v' => $bag));
$_pm['mem']->memClose();

//###########################
// @Load template.
//###########################

$fn='tpl_fbfight.html';
$tn = $_game['template'] . $fn;
$fat = '';
if (file_exists($tn))
{
	$tpl = file_get_contents($tn);

	//#test
	if (WG_CHECK == 1)
	{
		$mouse = '<script language="javascript">
function mouseCoords(ev)
{
 if(ev.pageX || ev.pageY){
   return {x:ev.pageX, y:ev.pageY};
 }
 return {
     x:ev.clientX + document.body.scrollLeft -

document.body.clientLeft,
     y:ev.clientY + document.body.scrollTop     -

document.body.clientTop
 };
}

function mouseMove(ev)
{
	ev= ev || window.event;
	var mousePos = mouseCoords(ev);
    //alert(mousePos.x);
    //alert(mousePos.y);
	var opt = {
		 method: \'get\',
		 onSuccess: function(t){
		 },
		 on404: function(t) {
		 },
		 onFailure: function(t) {
		 },
		 asynchronous:true
		}
	var ajax=new Ajax.Request(\'../function/exit1c.php?ssid=\'+mousePos.x+mousePos.y, opt);
}
document.onmousemove = mouseMove;
if(window.parent.autoack==true)
{
	/***/
		var opt = {
		 method: \'get\',
		 onSuccess: function(t){
		 },
		 on404: function(t) {
		 },
		 onFailure: function(t) {
		 },
		 asynchronous:true
		}
	var ajax=new Ajax.Request(\'../function/exit1.php?ssid=\'+window.parent.waittime, opt);
		/***/
}
</script>';
	}
	else $mouse = '';
	$currentFight = isset($_SESSION['fight'.$_SESSION['id']]) && is_array($_SESSION['fight'.$_SESSION['id']]) ? $_SESSION['fight'.$_SESSION['id']] : array();
	$_SESSION['fttime'.$_SESSION['id']] = kdjlFightAttackWaitLimit($user, false, $currentFight, '');
	$src = array(
					 "#bbinfo#",
					 "#gwinfo#",
					 "#bbjn#",
					 "#mapcj#",
					 "#petsid#",
					 "#nickname#",
					 "#head0#",
					 "#bbfzp#",
					 "#catcharr#",
					 "#inmap#",
					 "#test#",
					 "#fttime#"
					);
		$des = array(
					 $bbinfo,
					 $gwinfo,
					 $jnlist,
					 rand(1,3),
					 $bid,
					 isset($_SESSION['nickname']) ? $_SESSION['nickname'] : '',
					 fbfightModImage(isset($bb['headimg']) ? $bb['headimg'] : ''),
					 $bbfzp,
					 $catcharr,
					 $user['inmap'],
					 $mouse,
					 $_SESSION['fttime'.$_SESSION['id']]
				);

	$fat = str_replace($src, $des, $tpl);
}



// gzip echo. if maybe.
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
flush();
ob_start('ob_gzip');
echo $fat;
ob_end_flush();

function err($str)
{
	die('<center>
			<div style="margin-top:100px;padding:5px;font-

size:12px; line-height:1.7;width:99%;height:100px;overflow:hidden;">'.

$str .'<br/>
				<<<a href="javascript:history.go(-1);">

返回村庄</a>
			</div>
		</center>');

}

/**
* @Usage:验证BOSS怪物是否有效
* @Param: $gs => array.
* @Return: true false
* @Memo:
   boss_refresh
*/
function bossCheck($gs)
{
	global $_pm;
	$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
	$log = '';
	if (!kdjlReserveWorldBoss($gs, $uid, $log)) return false;

	$task = new task();
	$bossName = isset($gs['name']) ? $gs['name'] : intval($gs['id']);
	$task->saveGword("遇上了沉睡中的[".$bossName."]，勇士请赶快去消灭它吧！");
	return true;
}
/*

$str=print_r($gw,1).print_r($_SESSION["fight".$_SESSION['id']],1).print_r($_GET,1).'->$fbexist[\'gwid\']='.$fbexist['gwid'].',headers_list='.print_r(headers_list(),1);
$str=str_replace("\n",'\\n',$str);
echo '<script >alert("'.$str.'")</script>';
*/

?>
