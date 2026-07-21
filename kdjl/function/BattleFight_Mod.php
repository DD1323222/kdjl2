<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.08.29
*@Update Date: 2008.08.29
*@Usage: 战场战斗脚本
*@Note: none
@Param:
	>> 加入战场活动时间限制。
*/
session_start();
require_once('../config/config.game.php');
require_once(dirname(__FILE__).'/fight_wait_common.php');
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1)
{
	die("非法进入");
}
$_SESSION['id'] = $uid;
/*if (!defined('BATTLE_TIME_START'))
	define('BATTLE_TIME_START', "20:00");
if (!defined('BATTLE_TIME_END'))
	define('BATTLE_TIME_END', "22:00");
if (!defined('BATTLE_TIME_WEEK'))
	define('BATTLE_TIME_WEEK', 5);*/
//error_reporting(E_ALL&~E_NOTICE);
secStart($_pm['mem']);
$mouse = '';

function battleFightJsSingle($value)
{
	$value = str_replace("\\", "\\\\", (string)$value);
	$value = str_replace("'", "\\'", $value);
	$value = str_replace(array("\r", "\n", "<", ">"), array("\\r", "\\n", "\\x3C", "\\x3E"), $value);
	return $value;
}

function battleFightImage($value)
{
	$value = basename((string)$value);
	return preg_match('/^[A-Za-z0-9_.-]+$/', $value) ? $value : '';
}

$user	= $_pm['user']->getUserById($uid);
if(!is_array($user) || intval($user['mbid']) < 1)
{
	die('战场玩家数据错误！');
}
//加速外挂
$time = time();
$sql = "SELECT time FROM fight_log WHERE uid = {$uid} and vary = 2";
$timearr = $_pm['mysql'] -> getOneRecord($sql);
if(is_array($timearr)){
	$_pm['mysql'] -> query("UPDATE fight_log SET time = ".time()." WHERE uid = {$uid} and vary = 2");
}else{
	$_pm['mysql'] -> query("INSERT INTO fight_log (uid,time,vary) VALUES({$uid},".time().",2)");
}
//在这里结束

// 战场开放时间开关。
$week=date("N", time());
$hourM=date("H:i", time());

$battletimearr = kdjlSafeMemValue($_pm['mem']->get(MEM_TIME_KEY), array());
if(!is_array($battletimearr)) $battletimearr = array();
$checkstr = 0;

foreach($battletimearr as $bv)
{
	if(!is_array($bv)) continue;
	if(!isset($bv['titles'])) $bv['titles'] = '';
	if(!isset($bv['days'])) $bv['days'] = '';
	if(!isset($bv['starttime'])) $bv['starttime'] = '';
	if(!isset($bv['endtime'])) $bv['endtime'] = '';
	if($bv['titles'] != "battle")
	{
		continue;
	}
	if(isWeeklyDayTimeActive($bv['days'], $bv['starttime'], $bv['endtime'], $week, $hourM))
	{
		$checkstr = 1;
	}
}
if(empty($checkstr))
{
	die('<center><span style="font-size:12px;">战场未开启！</span></center>');
}

/*if ($week != BATTLE_TIME_WEEK && ($hourM < BATTLE_TIME_START || $hourM > BATTLE_TIME_END) )
{
	die('<center><span style="font-size:12px;">战场未开启！</span></center>'); // record log in here.
}
else if($week == BATTLE_TIME_WEEK && $hourM < BATTLE_TIME_START )
{
	die('<center><span style="font-size:12px;">战场未开启！</span></center>'); // record log in here.
}
else if($week == BATTLE_TIME_WEEK && $hourM > BATTLE_TIME_END )
{
	die("<script>window.parent.Alert('战场已结束,欢迎您下次参与战场活动！');window.parent.document.getElementById('gw').src='function/BattleInfo_Mod.php';</script>");
}*/

// ===========战场结束检查开始============
$ends = $_pm['mysql']->getOneRecord("SELECT hp,id
									  FROM battlefield
									 WHERE hp=0
									 LIMIT 0,1
								   ");
if (is_array($ends))
{
	die("<script>window.parent.Alert('战场已结束,欢迎您下次参与战场活动！');window.parent.document.getElementById('gw').src='function/BattleInfo_Mod.php';</script>");
}
// ===========战场结束检查结束==========

// 战场等级检测
// 获得玩家自己的阵营，得到对方阵营

/*$cUser = $_pm['mysql']->getOneRecord("SELECT pos,bid,levels
										FROM battlefield_user
									   WHERE uid={$_SESSION['id']}
									");


$battleinfo = $_pm['mysql']->getOneRecord("SELECT level_get
                                             FROM battlefield
											WHERE id={$cUser['pos']}
										 ");
//$_REQUEST['bcode']
//10-29:10:1|0:1,30-59:20:1|0:1,60-99:30:2|0:1,100-149:40:2|0:1,150-199:50:3|0:1,200-499:60:3|0:1
$c = explode($_REQUEST['bcode'].':',$battleinfo['level_get']);
$d = explode(',',$c[1]);
$e = explode('|',$d[0]);
$onepart = explode(':',$e[0]);
$twopart = explode(':',$e[1]);
$_pm['mysql']->query("UPDATE battlefield_user
											 SET lastvtime=unix_timestamp(),
												 addjgvalue={$onepart[0]},
												 ackvalue={$onepart[1]},
												 failjgvalue={$twopart[0]},
												 failackvalue={$twopart[1]},
												 bid={$user['mbid']},
												 levels='{$_REQUEST['bcode']}'
										   WHERE uid={$_SESSION['id']}
										 ");*/

$cUser = $_pm['mysql']->getOneRecord("SELECT pos,bid,levels
										FROM battlefield_user
									   WHERE uid={$uid} AND pos IN(1,2) AND lastvtime>=UNIX_TIMESTAMP(CURDATE())
									   ORDER BY id LIMIT 1
									");
if(!is_array($cUser) || intval($cUser['pos']) < 1 || intval($cUser['bid']) < 1)
{
	die('战场玩家数据错误！');
}

$cUser1 = $_pm['mysql']->getOneRecord("SELECT czl
										FROM userbb
									   WHERE id=".intval($cUser['bid'])." AND uid={$uid}
									");
$bcode = (isset($_REQUEST['bcode']) && !is_array($_REQUEST['bcode'])) ? trim($_REQUEST['bcode']) : '';
if(!preg_match('/^[0-9]+-[0-9]+$/', $bcode) || !is_array($cUser1))
{
	die('战场等级配置错误！');
}
$_REQUEST['bcode'] = $bcode;
$czlarr = explode('-', $bcode);//echo $cUser1['czl'].'<hr />';print_r($czlarr);exit;
if($bcode !== $cUser['levels'])
{
	die('<script language="javascript">window.parent.Alert("请选择加入阵营时登记的成长战场！");window.parent.$("gw").src="/function/BattleInfo_Mod.php";</script>');
}
$battleState = $_pm['mysql']->getOneRecord('SELECT id FROM battlefield WHERE id='.intval($cUser['pos']).' AND startf=1 AND ends=0 AND hp>0 LIMIT 1');
if(!is_array($battleState))
{
	die('<script language="javascript">window.parent.Alert("本场战斗已经结束！");window.parent.$("gw").src="/function/BattleComein_Mod.php";</script>');
}
if($cUser1['czl'] < intval($czlarr[0]) || $cUser1['czl'] > intval($czlarr[1])){
	//die("<center><span style='font-size:12px;'>您的成长不在".$_REQUEST['bcode']."间，不能进入相关战场! <span onclick=\"window.parent.$('gw').src='/function/BattleInfo_Mod.php';\" style='cursor:pointer;'><b><<返回阵营</b></span></span></center>");
	die('<script language="javascript">window.parent.Alert("您的成长不在'.$bcode.'间，不能进入相关战场!");window.parent.$("gw").src="/function/BattleInfo_Mod.php"</script>');
}


/*if ($_REQUEST['bcode']!=$cUser['czl'])
{
	die("<center><span style='font-size:12px;'>您的成长不在此区间，不能进入相关战场! <span onclick=\"window.parent.$('gw').src='/function/BattleInfo_Mod.php';\" style='cursor:pointer;'><b><<返回阵营</b></span></span></center>");
}*///



$userbb = $_pm['user']->getUserPetById($uid);
if(!is_array($userbb)) $userbb = array();
$fight	= isset($_SESSION['fight'.$uid]) && is_array($_SESSION['fight'.$uid]) ? $_SESSION['fight'.$uid] : array();


// 对于已被封号的玩家，直接踢下线
if ($user['secid']>0) // 地图限制
{
	unset($_SESSION['id']);
	$_pm['mem']->memClose();
	echo '<center>您的帐号非法操作，已被冻结！</center>';
	exit();
}
if($_pm['mysql']->query('update player set inmap=0 where id='.$uid)) $_pm['mem']->del(MEM_USER_KEY);
$battleAttackWait = kdjlFightAttackWaitLimit($user, false, $fight, 'manual');

//########################################################
if (!empty($fight) && isset($fight['ftime']) && intval($fight['ftime']) > 0)
{
	   // Check time
	   if (isset($fight['fatting']) && intval($fight['fatting']) == 0) {
			$fight = kdjlFightBeginPostWait($fight);
			$_SESSION['fight'.$uid] = $fight;
	   }
	   $will = kdjlFightEntryWaitRemaining($fight, $user, false, intval($cUser['bid']), 'manual');
	   if ($will > 0) {
		$end='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<!--[if IE 6]><script type="text/javascript">try{ document.execCommand("BackgroundImageCache", false, true); } catch(e) {}
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
	if(m<1  && pt==0)
	{
		window.clearTimeout(readH);
		window.setTimeout("pause()",100);
		return;
	}
	else{
		document.getElementById("timev").innerHTML = m--;
		readH=window.setTimeout("loadtime("+m+");", 1000);
	}
}
function pause()
{   if (pt==1) return;
	window.parent.document.getElementById("gw").src="./function/BattleFight_Mod.php?bcode='.$bcode.'&s=t";
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
$bid=$cUser['bid'];
$arrobj = new arrays();
$bb = $arrobj->dataGet(array('k' => MEM_BB_KEY,
							 'v' => "if(\$rs['id'] == '{$cUser['bid']}' && \$rs['uid'] == '{$_SESSION['id']}') \$ret=\$rs;"
					        ),
							$userbb
					  );

if (!is_array($bb))
{
	die('登记的战场宠物已不可用，请返回阵营重新进入！');
}
if(intval(isset($bb['uid']) ? $bb['uid'] : 0) != $uid ||
	intval(isset($bb['muchang']) ? $bb['muchang'] : 0) != 0 ||
	intval(isset($bb['tgflag']) ? $bb['tgflag'] : 0) != 0)
{
	die('登记的战场宠物当前不能战斗，请返回阵营重新进入！');
}
{
	// ============================== 装备效果开始 ==========================================
	//宠物的血量和魔法的最大值的计算（加上装备的效果）；
	$arr = getzbAttrib($bid);
	$arrHp = max(0, intval(round(isset($arr['hp']) ? $arr['hp'] : 0)));
	$arrMp = max(0, intval(round(isset($arr['mp']) ? $arr['mp'] : 0)));
	$bb['srchp'] += $arrHp;
	$bb['srcmp'] += $arrMp;
	$bb['hp'] += $arrHp;
	$bb['mp'] += $arrMp;
   // ================================ 装备效果结束 ========================================
	if(!$_pm['mysql']->query("UPDATE userbb
					 SET hp=srchp,mp=srcmp,addhp={$arrHp},addmp={$arrMp}
				   WHERE id={$bid} and uid={$uid}"))
	{
		die('保存战斗宠物状态失败！');
	}
	$_pm['mem']->del(MEM_USERBB_KEY);

	// By field order.
	$bb['wx'] = getWx($bb['wx']);
	if($bb['hp'] == 0)
	{
		$bb['hp'] = $bb['srchp'];
	}
	$bbNameJs = battleFightJsSingle($bb['name']);
	$bbSkillJs = battleFightJsSingle($bb['skillist']);
	$bbImgStand = battleFightImage($bb['imgstand']);
	$bbImgAck = battleFightImage($bb['imgack']);
	$bbImgDie = battleFightImage($bb['imgdie']);
	$bbHeadImg = battleFightImage(isset($bb['headimg']) ? $bb['headimg'] : '');
	$bbinfo = "['{$bbNameJs}',{$bb['level']},'{$bb['wx']}',{$bb['ac']},{$bb['mc']},{$bb['hp']},{$bb['mp']},'{$bbSkillJs}','{$bbImgStand}','{$bbImgAck}','{$bbImgDie}',{$bid},'{$bb['srchp']}','{$bb['srcmp']}','{$bb['nowexp']}','{$bb['lexp']}']";
}

// 获得技能详细信息

$jlist = '';
$jnlist = '';
$tjn = explode(",", $bb['skillist']);
foreach($tjn as $mkey => $n)
{
	$tt = explode(":", $n);
	if(!isset($tt[0]) || $tt[0] === '') continue;
	$jlist .= intval($tt[0]) . ",";
}
$jlist =	rtrim($jlist, ',');
$bjn   =	$_pm['user']->getUserPetSkillById($_SESSION['id']);

if (!is_array($bjn))
{
	Header("Location:BattleFight_Mod.php?bcode=".$bcode);exit();
}

$jlistarr = explode(',', $jlist);
foreach($bjn as $k => $rs)
{
	if (in_array($rs['sid'], $jlistarr) &&
		$rs['bid'] == $bid
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
		$skillNameJs = battleFightJsSingle($rs['name']);
		$skillValueJs = battleFightJsSingle($rs['value']);
		$skillPlusJs = battleFightJsSingle($rs['plus']);
		$skillImg = battleFightImage($rs['img']);
		$jnlist .="['{$skillNameJs}',{$rs['level']},'{$rs['vary']}',{$rs['wx']},'{$skillValueJs}','{$skillPlusJs}','{$skillImg}',{$rs['uhp']},{$rs['ump']},{$rs['sid']}],";
	}
}
$jnlist = rtrim($jnlist, ','); // []#[];

// 随机获得挑战方的ID。
$battleLevelSql = $_pm['mysql']->escape($cUser['levels']);
$allUser = $_pm['mysql']->getRecords("SELECT bu.uid,bu.bid
										FROM battlefield_user bu
										JOIN userbb b ON b.id=bu.bid AND b.uid=bu.uid
									   WHERE bu.levels='{$battleLevelSql}' AND bu.pos!={$cUser['pos']}
										 AND bu.lastvtime>=UNIX_TIMESTAMP(CURDATE()) AND b.muchang=0 AND b.tgflag=0");
if(!is_array($allUser) || empty($allUser)) {
	//die("<center><span style='font-size:12px;'>没发现任何敌军！ <span onclick=\"window.parent.$('gw').src='/function/BattleInfo_Mod.php';\" style='cursor:pointer;'><b><<返回阵营</b></span></span></center>");
	die('<script language="javascript">window.parent.Alert("没发现任何敌军！");window.parent.$("gw").src="/function/BattleInfo_Mod.php";</script>');
}

$rid = rand(1, count($allUser))-1;
if (array_key_exists($rid, $allUser))
	$buserarr = $allUser[$rid];
else {Header("Location:BattleFight_Mod.php?bcode={$bcode}");exit();}

// 获取被挑战玩家的宠物信息。
$gw	= $_pm['mysql']->getOneRecord("SELECT *
									FROM userbb
								   WHERE id={$buserarr['bid']} AND uid={$buserarr['uid']}
								   LIMIT 1
								");
if (!is_array($gw))
{
	die('……');
}

$gw['wx'] = getWx($gw['wx']);
$gw['username'] = isset($gw['username']) ? $gw['username'] : '';
//避免0血的情况
if(empty($gw['hp']))
{
	$gw['hp'] = $gw['srchp'];
}
$gwNameJs = battleFightJsSingle($gw['name']);
$gwSkillJs = battleFightJsSingle(isset($gw['skillist']) ? $gw['skillist'] : '');
$gwImgStand = battleFightImage($gw['imgstand']);
$gwImgAck = battleFightImage($gw['imgack']);
$gwImgDie = battleFightImage($gw['imgdie']);
$gwinfo="['{$gwNameJs}',{$gw['level']},'{$gw['wx']}',{$gw['ac']},{$gw['mc']},{$gw['srchp']},{$gw['mp']},'{$gwSkillJs}','{$gwImgStand}','{$gwImgAck}','{$gwImgDie}',{$gw['id']}]";

$test = isset($_SESSION['fight'.$uid]) && is_array($_SESSION['fight'.$uid]) ? $_SESSION['fight'.$uid] : false;
//Update fightting stats.
if (!is_array($test))
{
	$_SESSION["fight".$uid]	= array('uid'=>$uid,
					'bid'=>$bid,
					'gid'=>$gw['id'],
					'hp' =>$gw['srchp'],
					'mp' =>$gw['srcmp'],
					'fuzu'=>0,
					'fatting'=>1,
					'boss'=>0,
					'ftime'=>time());
	$_SESSION['fight'.$uid] = kdjlFightStartState($_SESSION['fight'.$uid], $user, false, 'manual');
}
else{
	 // Check time
	   $fight = $test;
	   if (isset($fight['fatting']) && intval($fight['fatting']) == 0) {
			$fight = kdjlFightBeginPostWait($fight);
			$_SESSION['fight'.$uid] = $fight;
	   }
	   $will = kdjlFightEntryWaitRemaining($fight, $user, false, $bid, 'manual');
	   if ($will > 0) {
		$end='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<!--[if IE 6]><script type="text/javascript">try{ document.execCommand("BackgroundImageCache", false, true); } catch(e) {}
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
	if(m<1  && pt==0)
	{
		window.clearTimeout(readH);
		window.setTimeout("pause()",100);
		return;
	}
	else{
		document.getElementById("timev").innerHTML = m--;
		readH=window.setTimeout("loadtime("+m+");", 1000);
	}
}
function pause()
{   if (pt==1) return;
	window.parent.document.getElementById("gw").src="./function/BattleFight_Mod.php?bcode='.$bcode.'&s=t";
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
	$r['uid']		=$uid;
	$r['gid']		=$gw['id'];
	$r['hp']		=$gw['srchp'];
	$r['mp']		=$gw['srcmp'];
	$r['fatting']=1;
	$r['ftime']	=time();
	$r['fuzu']	=0;
	$r['boss']	=0;
	//$fight=$r;
	$r = kdjlFightPreserveStartedAt($r, $fight);
	$_SESSION["fight".$_SESSION['id']]=kdjlFightStartState($r, $user, false, 'manual');
}

$bbfzp = "";
$catcharr = "";

$bbfzp='0';
$_pm['mem']->memClose();

//###########################
// @Load template.
//###########################

$fn='tpl_battle_fight.html';
$tn = $_game['template'] . $fn;
$fat = '';

if (file_exists($tn))
{
	$tpl = file_get_contents($tn);

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
						 "#fuser#",
						 "#fttime#"
					);
		$des = array(
					 $bbinfo,
					 $gwinfo,
					 $jnlist,
					 rand(1,3),
					 $cUser['levels'],
						 isset($_SESSION['nickname']) ? htmlspecialchars($_SESSION['nickname'],ENT_QUOTES,'UTF-8') : '',
						 $bbHeadImg,
					 $bbfzp,
					 $catcharr,
					 $user['inmap'],
					 $mouse,
			         battleFightJsSingle($gw['username']),
						 $battleAttackWait
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
?>
