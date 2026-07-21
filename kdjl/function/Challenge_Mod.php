<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.08.12
*@Update Date: 2008.08.12
*@Usage:Challenge Gui Mod
*@Note: none
@Param:
p:  pets id
cp: 被挑战玩家的主战pets id
*/
require_once('../config/config.game.php');
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');
$_SESSION['id'] = $uid;
if(isset($_SESSION['team_id']) && intval($_SESSION['team_id']) > 0)
{
	die('退出出队伍才可以进入！');
}
secStart($_pm['mem']);

function challengeModJsSingle($value)
{
	$value = str_replace("\\", "\\\\", (string)$value);
	$value = str_replace("'", "\\'", $value);
	$value = str_replace(array("\r", "\n", "<", ">"), array("\\r", "\\n", "\\x3C", "\\x3E"), $value);
	return $value;
}

function challengeModImage($value)
{
	$value = basename((string)$value);
	return preg_match('/^[A-Za-z0-9_.-]+$/', $value) ? $value : '';
}

$user	= $_pm['user']->getUserById($uid);
if(!is_array($user)) die('挑战玩家数据错误！');
$user['mbid'] = isset($user['mbid']) ? intval($user['mbid']) : 0;
$user['inmap'] = isset($user['inmap']) ? intval($user['inmap']) : 0;

if(isset($_GET['guild_fight']))
{
	require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
	$s=new socketmsg();
	$guild=new guild($s);
	if(!$myGuild=$guild->getMyGuildInfo())
	{
		$guild->clearGuildFightSession();
		alert('您没有加入一个家族！','window.location="/function/Expore_Mod.php"');
		die();
	}

	if(!$guild->checkGuildFightTime())
	{
		$guild->clearGuildFightSession();
		alert('现在不是家族战斗时间！','window.location="/function/guild_battle_mod.php"');
		die();
	}

	$_pm['mysql']->query('delete from guild_challenges where flags=0');
	$changeGuid=$guild->getChanllengeGuildInfo($myGuild['id']);
	if(!is_array($changeGuid))
	{
		$guild->clearGuildFightSession();
		alert($changeGuid,'',true);
		die();
	}

	$flagChanllenger=false;
	$enemyGuidId=0;
	if($changeGuid['challenger_id']==$myGuild['id'])
	{
		$flagChanllenger=true;
		$enemyGuidId=$changeGuid['defenser_id'];
	}else{
		$enemyGuidId=$changeGuid['challenger_id'];
	}

	$changeGuidMembers=$guild->getChanllengeGuildMembers($enemyGuidId);
	if(!is_array($changeGuidMembers) || empty($changeGuidMembers))
	{
		alert('没有找到可挑战的敌对家族成员！','',true);
		die();
	}
	shuffle($changeGuidMembers);
	$enemyGuildMember=$changeGuidMembers[0];

	$_SESSION['guild_fight_id']=$enemyGuildMember['member_id'];
	$_SESSION['guild_fight_time']=time();
	$enemyGuildMemberInfo=$_pm['mysql']->getOneRecord("SELECT mbid
					    FROM player
					   WHERE id='".$enemyGuildMember['member_id']."'
					   LIMIT 0,1
					");
	if(!is_array($enemyGuildMemberInfo) || empty($enemyGuildMemberInfo['mbid']))
	{
		alert('敌对家族成员的主战宠物数据错误！','',true);
		die();
	}
	$_REQUEST['cp']=$enemyGuildMemberInfo['mbid'];
	$_SESSION['guild_fight_bid']=$enemyGuildMemberInfo['mbid'];
	$challengeCp = (isset($_REQUEST['cp']) && !is_array($_REQUEST['cp'])) ? intval($_REQUEST['cp']) : 0;
	if($challengeCp==0)
	{
		$requestUri = (isset($_SERVER['REQUEST_URI']) && !is_array($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '/function/Challenge_Mod.php';
		if($requestUri === '' || substr($requestUri, 0, 1) !== '/' || preg_match('/["\'\r\n]/', $requestUri)) $requestUri = '/function/Challenge_Mod.php';
		header("location:".$requestUri);
		echo '加载中...';
		die();
	}
	$_REQUEST['p']=$user['mbid'];
	if($_pm['mysql']->query('update player set inmap=0 where id='.$uid))
	{
		$_pm['mem']->del(MEM_USER_KEY);
	}
}

$fortress_flag=false;

$fortressCardDate = isset($_SESSION['fortress_card_date']) ? strval($_SESSION['fortress_card_date']) : '';
if(isset($_SESSION['fortress_card_id']) && intval($_SESSION['fortress_card_id']) > 0 && $fortressCardDate !== date('Ymd'))
{
	$_SESSION['fortress_card_id'] = 0;
	unset($_SESSION['fortress_card_date']);
}
if(isset($_SESSION['fortress_card_id'])&&$_SESSION['fortress_card_id']>0)
{
	$setting = $_pm['mem']->get('db_welcome1');
	if(!is_array($setting)) $setting=kdjlSafeMemValue($setting, array());
	if(!is_array($setting))
	{
		die('后台配置数据读取失败(1)！');
	}

	if(!isset($setting['fortress_time']))
	{
		die('缺少活动开启设定(fortress_time)！');
	}

	$time_settings=explode("|",$setting['fortress_time']);
	$w=date('w');
	$hm=date('His');
	if($w==0)
	{
		$w=7;
	}
	$time_flag=false;
	foreach($time_settings as $s)
	{
		$tmp=explode(',',$s);
		//1,2100,2105,2130,2135
		if(count($tmp) < 4)
		{
			continue;
		}
		if($w==$tmp[0])
		{
			if($hm>=$tmp[1]&&$hm<=$tmp[3])
			{
				$time_flag=true;
			}
			break;
		}
	}
	if(!$time_flag){
		die('现在不是要塞战斗时间！');
	}
	$table_name="`fortress_users_".date("Ymd")."`";
	$user_fortress=$_pm['mysql']->getOneRecord('select cur_gpc_id,bb_id,at_section_num,fv_result from '.$table_name.' where user_id='.$uid);
	if(!$user_fortress)
	{
		header('location:/function/fortress_Mod.php');
	die('没有找到您的要塞参战数据！');
	}
	$user_fortress['cur_gpc_id'] = isset($user_fortress['cur_gpc_id']) ? intval($user_fortress['cur_gpc_id']) : 0;
	$user_fortress['bb_id'] = isset($user_fortress['bb_id']) ? intval($user_fortress['bb_id']) : 0;
	$user_fortress['at_section_num'] = isset($user_fortress['at_section_num']) ? intval($user_fortress['at_section_num']) : 0;
	$user_fortress['fv_result'] = isset($user_fortress['fv_result']) ? intval($user_fortress['fv_result']) : 0;
	$fortressPet = $_pm['mysql']->getOneRecord('select id from userbb where id='.$user_fortress['bb_id'].' and uid='.$uid.' and muchang=0 and tgflag=0');
	if(!is_array($fortressPet))
	{
		die('您报名时使用的宠物当前不可出战，请先恢复该宠物状态！');
	}

	$sql_extra='';
	$get_score=0;
	if($user_fortress['cur_gpc_id']!=0)//上个怪物没有打死
	{
		if($user_fortress['fv_result']<=0)
		{
			$sql_extra=',f_times=COALESCE(f_times,0)+1,fv_result=COALESCE(fv_result,0)-1';
			$get_score=(2*abs($user_fortress['fv_result']-1)-1)*(-5);
		}
		else
		{
			$sql_extra=',f_times=COALESCE(f_times,0)+1,fv_result=-1';
			$get_score=-5;
		}
	}

	if(!$user_fortress)
	{
		header('location:/function/fortress_Mod.php');
		die('您没有加入要塞！');
	}

	$user['mbid']=$user_fortress['bb_id'];
	if(!$_pm['mysql']->query('update player set mbid='.$user_fortress['bb_id'].' where id='.$uid))
	{
		die('要塞出战宠物保存失败，请稍候重试！');
	}
	if(defined('MEM_USER_KEY')) $_pm['mem']->del(MEM_USER_KEY);
	//$_SESSION['fortress_card_id']=0;
	$_SESSION['fortress_pass']=3;

	$fortress_flag=true;
	$monsters_id=0;
	//if(rand(1,100)<=30)

	$fortress_users=$_pm['mysql']->getRecords('select fortress.user_id,fortress.bb_id from '.$table_name.' fortress inner join userbb on userbb.id=fortress.bb_id and userbb.uid=fortress.user_id where fortress.user_id!='.$uid.' and fortress.at_section_num='.$user_fortress['at_section_num'].' and userbb.muchang=0 and userbb.tgflag=0');
	if(!is_array($fortress_users)) $fortress_users = array();
	$validFortressUsers = array();
	foreach($fortress_users as $fortressUser)
	{
		if(isset($fortressUser['bb_id']) && intval($fortressUser['bb_id']) > 0)
		{
			$validFortressUsers[] = array('user_id' => intval($fortressUser['user_id']), 'bb_id' => intval($fortressUser['bb_id']));
		}
	}
	$fortress_users = $validFortressUsers;
	$ct=count($fortress_users);
	if($ct<2){
		die('<script language="javascript">parent.Alert("进入要塞的玩家太少！");window.location="/function/Expore_Mod.php"</script>');
	}

	$useFortressMonster = (rand(1,100)<=60);
	if($useFortressMonster)
	{
		if(!isset($setting['fortress']))
		{
			die('缺少活动开启设定(fortress)！');
		}
		$set=preg_split('/\s+/', trim($setting['fortress']), -1, PREG_SPLIT_NO_EMPTY);
		$sectionIdx = intval($user_fortress['at_section_num']) - 1;
		if(!isset($set[$sectionIdx]))
		{
			$useFortressMonster = false;
		}
		else
		{
			$set=explode(',',$set[$sectionIdx]);
			$monsters=isset($set[4]) ? explode('|',$set[4]) : array();
			$monsters=array_filter($monsters, 'strlen');
			$monsters=array_values($monsters);
			$monsterCt=count($monsters);
			if($monsterCt < 1) $useFortressMonster = false;
		}
	}
	if($useFortressMonster)
	{
		$key=rand(1,$monsterCt);
		$monsters_id=$monsters[$key-1];
		$gw	= $_pm['mysql']->getOneRecord("SELECT *
									FROM gpc
								   WHERE id={$monsters_id}
								");
		if(!is_array($gw))
		{
			die('要塞怪物配置错误！');
		}

		$_SESSION['fortress_gw']=$gw;
		$gw['srchp']=$gw['hp'];
		$gw['srcmp']=$gw['mp'];
	}
	else
	{
		$key=rand(1,$ct);
		$monsters_id=$fortress_users[$key-1]['bb_id'];
		$opponentUid=$fortress_users[$key-1]['user_id'];
		$gw	= $_pm['mysql']->getOneRecord("SELECT *
									FROM userbb
								   WHERE id={$monsters_id} AND uid={$opponentUid} AND muchang=0 AND tgflag=0
								");
		if(!is_array($gw))
		{
			die('要塞对手数据错误！');
		}

		$_SESSION['fortress_gw']=$monsters_id;
	}
	$gw['name']='要塞怪物';
	$_SESSION['fight'.$uid]['ftime']=10;
	$expectedGpcId = intval($user_fortress['cur_gpc_id']);
	$setFortressFightSql = 'update '.$table_name.' set cur_gpc_id='.$monsters_id.$sql_extra.
		',score=COALESCE(score,0)+'.intval(round($get_score)).' where user_id='.$uid.' and COALESCE(cur_gpc_id,0)='.$expectedGpcId;
	if(!$_pm['mysql']->query($setFortressFightSql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
	{
		unset($_SESSION['fortress_gw']);
		die('要塞战斗状态保存失败，请稍候重试！');
	}
}

define('MEM_BOSS_KEY', $uid . 'boss');
define('MEM_FIGHT_KEY', $uid . 'fight');

error_reporting(E_ALL&~E_NOTICE);
$userbb = $_pm['user']->getUserPetById($uid);
$bag    = $_pm['user']->getUserBagById($uid);
$fight	=	isset($_SESSION['fight'.$uid]) ? $_SESSION['fight'.$uid] : false;
if(!is_array($userbb)) $userbb = array();
if(!is_array($bag)) $bag = array();

if(isset($flagChanllenger))
{
	$_game['map'] = array(1,2,3,4,5,6,7,8,9,10,14,15,16,17,18,19,20,100,101,102,103,104,105,106,107,108,109,110,111,112);
	if(isset($fight['ftime'])) $fight['ftime']-=290;
	$user['inmap']=$_game['map'][rand(0,count($_game['map'])-1)];
}
if($fortress_flag)$user['inmap']=1;
if (!in_array($user['inmap'],$_game['map']))
{
	/*$user['secid']=1;
	$_pm['mysql']->query("UPDATE player
							 SET secid=1
						   WHERE id={$_SESSION['id']}
					    ");*/

	unset($_SESSION['id']);
	$_pm['mem']->memClose();
	echo '<center>您的帐号非法操作，服务器强制断线！</center>';
	exit();
}
$arr = $_pm['mysql'] -> getOneRecord('SELECT img FROM map WHERE id = '.$user['inmap']);
if(!is_array($arr)) $arr = array('img' => '');
$bgtype = isset($arr['img']) ? $arr['img'] : '';
if($bgtype == 'swf'){
	//$flash = '<embed src="../images/map/t'.$user['inmap'].'/'.$user['inmap'].'.swf" width="778" height="311" wmode="transparent">';
	$flash = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,19,0" width="778" height="311">
			  <param name="movie" value="../images/map/t'.$user['inmap'].'/'.$user['inmap'].'.swf">
			  <param name="quality" value="high">
			  <param name="wmode" value="transparent">
			  <embed src="../images/map/t'.$user['inmap'].'/'.$user['inmap'].'.swf" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" width="778" height="311" wmode="transparent"></embed>
           </object>';
}else{
	$flash = '';
}
//#########################
if (is_array($fight) && isset($fight['ftime']) && intval($fight['ftime']) > 0)
{
	   // Check time
	   $fightTime = intval($fight['ftime']);
	   $will = max(1, 300 - (time() - $fightTime));
	   if ($fightTime+300>=time()) {
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
  <div style="margin-top:140px;"><img src="'.IMAGE_SRC_URL.'/ui/fight/loading.gif"/><div id="timev" style="position:absolute; text-align:center; color:#F98F2C; font-weight:bold;font-size:2em;left:360px; top:140px; width:70px; height:70px; line-height:70px; padding:0;"></div>
</div>
</center>
</body>
</html>
<script language="javascript">
function loadtime(m){

	m = parseInt(m, 10);
	if(isNaN(m) || m < 1)
	{
		location.reload();
		return;
	}
	document.getElementById("timev").innerHTML = m;
	readH=window.setTimeout("loadtime("+(m-1)+");", 1000);
}
loadtime('.$will.');
</script>';
			ob_start('');
			echo $end;
			ob_end_flush();
			exit();
		}
}
//########################

// Get bb info.
$bid = (isset($_REQUEST['p']) && !is_array($_REQUEST['p'])) ? intval($_REQUEST['p']) : 0;
$cpid = (isset($_REQUEST['cp']) && !is_array($_REQUEST['cp'])) ? intval($_REQUEST['cp']) : 0;

$arrobj = new arrays();
if($bid==0){
	if (!empty($fight)&&isset($_SESSION['fight'.$uid]['bid'])&&$_SESSION['fight'.$uid]['bid']>0)
	{
		$bid = $_SESSION['fight'.$uid]['bid'];
	}
	else $bid = $user['mbid'];
}

if($fortress_flag)
{
	$bid 						  = $user['mbid'];
	$_SESSION['fortress_gpc_time']= time();
	$cpid						  = $monsters_id;
}

$bb = $arrobj->dataGet(array('k' => MEM_BB_KEY,
							 'v' => "if(\$rs['id'] == '{$bid}' && \$rs['uid'] == '{$uid}') \$ret=\$rs;"
							),
						$userbb
					 );

if (!is_array($bb))
{
	die('不能获得宠物数据！');
}
else
{
	if(intval(isset($bb['uid']) ? $bb['uid'] : 0) != $uid ||
		intval(isset($bb['muchang']) ? $bb['muchang'] : 0) != 0 ||
		intval(isset($bb['tgflag']) ? $bb['tgflag'] : 0) != 0)
	{
		die('当前宠物状态不能参加挑战！');
	}
	$bb['wx'] = isset($bb['wx']) ? $bb['wx'] : 0;
	$bb['name'] = isset($bb['name']) ? $bb['name'] : '';
	$bb['level'] = isset($bb['level']) ? intval($bb['level']) : 1;
	$bb['ac'] = isset($bb['ac']) ? intval($bb['ac']) : 0;
	$bb['mc'] = isset($bb['mc']) ? intval($bb['mc']) : 0;
	$bb['hp'] = isset($bb['hp']) ? intval($bb['hp']) : 0;
	$bb['mp'] = isset($bb['mp']) ? intval($bb['mp']) : 0;
	$bb['skillist'] = isset($bb['skillist']) ? $bb['skillist'] : '';
	$bb['imgstand'] = isset($bb['imgstand']) ? $bb['imgstand'] : '';
	$bb['imgack'] = isset($bb['imgack']) ? $bb['imgack'] : '';
	$bb['imgdie'] = isset($bb['imgdie']) ? $bb['imgdie'] : '';
	$bb['srchp'] = isset($bb['srchp']) ? intval($bb['srchp']) : $bb['hp'];
	$bb['srcmp'] = isset($bb['srcmp']) ? intval($bb['srcmp']) : $bb['mp'];
	$bb['addhp'] = isset($bb['addhp']) ? max(0, intval($bb['addhp'])) : 0;
	$bb['addmp'] = isset($bb['addmp']) ? max(0, intval($bb['addmp'])) : 0;
	$bb['nowexp'] = isset($bb['nowexp']) ? intval($bb['nowexp']) : 0;
	$bb['lexp'] = isset($bb['lexp']) ? intval($bb['lexp']) : 0;
	$challengeEquip = getzbAttrib($bid);
	$challengeEquipHp = max(0, intval(round(isset($challengeEquip['hp']) ? $challengeEquip['hp'] : 0)));
	$challengeEquipMp = max(0, intval(round(isset($challengeEquip['mp']) ? $challengeEquip['mp'] : 0)));
	$bb['hp'] += min($challengeEquipHp, $bb['addhp']);
	$bb['mp'] += min($challengeEquipMp, $bb['addmp']);
	$bb['srchp'] += $challengeEquipHp;
	$bb['srcmp'] += $challengeEquipMp;
	// By field order.
	$bb['wx'] = getWx($bb['wx']);
	$bbNameJs = challengeModJsSingle($bb['name']);
	$bbSkillJs = challengeModJsSingle($bb['skillist']);
	$bbImgStand = challengeModImage($bb['imgstand']);
	$bbImgAck = challengeModImage($bb['imgack']);
	$bbImgDie = challengeModImage($bb['imgdie']);
	$bbHeadImg = challengeModImage(isset($bb['headimg']) ? $bb['headimg'] : '');
	$bbinfo = "['{$bbNameJs}',{$bb['level']},'{$bb['wx']}',{$bb['ac']},{$bb['mc']},{$bb['hp']},{$bb['mp']},'{$bbSkillJs}','{$bbImgStand}','{$bbImgAck}','{$bbImgDie}',{$bid},'{$bb['srchp']}','{$bb['srcmp']}','{$bb['nowexp']}','{$bb['lexp']}']";
}

// Get detail jn info.
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
$bjn   =	$_pm['user']->getUserPetSkillById($uid);

if (!is_array($bjn))
{
	Header("Location:Challenge_Mod.php?p={$bid}&cp={$cpid}");exit();
}

$jlistarr = ($jlist === '') ? array() : explode(',', $jlist);
foreach($bjn as $k => $rs)
{
	$rs['sid'] = isset($rs['sid']) ? intval($rs['sid']) : 0;
	$rs['bid'] = isset($rs['bid']) ? intval($rs['bid']) : 0;
	$rs['value'] = isset($rs['value']) ? $rs['value'] : 0;
	$rs['name'] = isset($rs['name']) ? $rs['name'] : '';
	$rs['level'] = isset($rs['level']) ? intval($rs['level']) : 1;
	$rs['vary'] = isset($rs['vary']) ? $rs['vary'] : '';
	$rs['wx'] = isset($rs['wx']) ? intval($rs['wx']) : 0;
	$rs['plus'] = isset($rs['plus']) ? $rs['plus'] : '';
	$rs['img'] = isset($rs['img']) ? $rs['img'] : '';
	$rs['uhp'] = isset($rs['uhp']) ? intval($rs['uhp']) : 0;
	$rs['ump'] = isset($rs['ump']) ? intval($rs['ump']) : 0;
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
		$skillNameJs = challengeModJsSingle($rs['name']);
		$skillValueJs = challengeModJsSingle($rs['value']);
		$skillPlusJs = challengeModJsSingle($rs['plus']);
		$skillImg = challengeModImage($rs['img']);
		$jnlist .="['{$skillNameJs}',{$rs['level']},'{$rs['vary']}',{$rs['wx']},'{$skillValueJs}','{$skillPlusJs}','{$skillImg}',{$rs['uhp']},{$rs['ump']},{$rs['sid']}],";
	}
}
$jnlist = rtrim($jnlist, ','); // []#[];

if(!$fortress_flag){
	// Normal challenges must use the target's current, challenge-enabled main pet.
	if(!isset($flagChanllenger))
	{
		$gw = $_pm['mysql']->getOneRecord("SELECT b.*
									  FROM userbb b
									  JOIN player p ON p.id=b.uid AND p.mbid=b.id
								 LEFT JOIN player_ext e ON e.uid=p.id
									 WHERE b.id={$cpid} AND b.uid<>{$uid} AND b.level>=20
									   AND b.muchang=0 AND b.tgflag=0 AND COALESCE(e.tiaozhan,0)<>0
									 LIMIT 1");
	}
	else
	{
		$guildTargetUid = isset($_SESSION['guild_fight_id']) ? intval($_SESSION['guild_fight_id']) : 0;
		$gw = $_pm['mysql']->getOneRecord("SELECT * FROM userbb WHERE id={$cpid} AND uid={$guildTargetUid} AND muchang=0 AND tgflag=0 LIMIT 1");
	}
}
if (!is_array($gw))
{
	die('被挑战宠物当前不可用！');
}
	$gw['username'] = isset($gw['username']) ? $gw['username'] : '';

	$gw['wx'] = isset($gw['wx']) ? $gw['wx'] : 0;
	$gw['name'] = isset($gw['name']) ? $gw['name'] : '';
	$gw['level'] = isset($gw['level']) ? intval($gw['level']) : 1;
	$gw['ac'] = isset($gw['ac']) ? intval($gw['ac']) : 0;
	$gw['mc'] = isset($gw['mc']) ? intval($gw['mc']) : 0;
	$gw['hp'] = isset($gw['hp']) ? intval($gw['hp']) : 0;
	$gw['mp'] = isset($gw['mp']) ? intval($gw['mp']) : 0;
	$gw['skillist'] = isset($gw['skillist']) ? $gw['skillist'] : (isset($gw['skill']) ? $gw['skill'] : '');
	$gw['imgstand'] = isset($gw['imgstand']) ? $gw['imgstand'] : '';
	$gw['imgack'] = isset($gw['imgack']) ? $gw['imgack'] : '';
	$gw['imgdie'] = isset($gw['imgdie']) ? $gw['imgdie'] : '';
	$gw['id'] = isset($gw['id']) ? intval($gw['id']) : 0;
	$gw['srchp'] = isset($gw['srchp']) ? intval($gw['srchp']) : $gw['hp'];
	$gw['srcmp'] = isset($gw['srcmp']) ? intval($gw['srcmp']) : $gw['mp'];
	$gw['wx'] = getWx($gw['wx']);

	$gw['hp']=$gw['srchp'];

	$gwNameJs = challengeModJsSingle($gw['name']);
	$gwSkillJs = challengeModJsSingle($gw['skillist']);
	$gwImgStand = challengeModImage($gw['imgstand']);
	$gwImgAck = challengeModImage($gw['imgack']);
	$gwImgDie = challengeModImage($gw['imgdie']);
	$gwinfo="['{$gwNameJs}',{$gw['level']},'{$gw['wx']}',{$gw['ac']},{$gw['mc']},{$gw['hp']},{$gw['mp']},'{$gwSkillJs}','{$gwImgStand}','{$gwImgAck}','{$gwImgDie}',{$gw['id']}]";
	if(!$fortress_flag && !isset($flagChanllenger))
	{
		$_SESSION['challenge_target_bid'] = intval($gw['id']);
		$_SESSION['challenge_target_uid'] = intval($gw['uid']);
	}
	else
	{
		unset($_SESSION['challenge_target_bid']);
		unset($_SESSION['challenge_target_uid']);
	}

	$test = isset($_SESSION['fight'.$uid]) ? $_SESSION['fight'.$uid] : false;
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
	}
	else
	{
	   // Check time
	   $fightTime = isset($fight['ftime']) ? intval($fight['ftime']) : 0;
	   $will = max(1, 300 - (time() - $fightTime));
	   if ($fightTime > 0 && $fightTime+300 >= time()) {
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
  <div style="margin-top:140px;"><img src="'.IMAGE_SRC_URL.'/ui/fight/loading.gif"/><div id="timev" style="position:absolute; text-align:center; color:#F98F2C; font-weight:bold;font-size:2em;left:360px; top:140px; width:70px; height:70px; line-height:70px; padding:0;"></div>
</div>
</center>
</body>
</html>
<script language="javascript">
function loadtime(m){

	m = parseInt(m, 10);
	if(isNaN(m) || m < 1)
	{
		location.reload();
		return;
	}
	document.getElementById("timev").innerHTML = m;
	readH=window.setTimeout("loadtime("+(m-1)+");", 1000);
}
loadtime('.$will.');
</script>';
			ob_start('');
			echo $end;
			ob_end_flush();
			exit();
		}

		$r['bid']		=$bid;
		$r['gid']		=$gw['id'];
		$r['hp']		=$gw['srchp'];
		$r['mp']		=$gw['srcmp'];
		$r['fatting']=1;
		$r['ftime']	=time();
		$r['fuzu']	=0;
		$r['boss']	=0;
		//$fight=$r;
		$_SESSION["fight".$uid]=$r;
	}


$bbfzp = "";
$catcharr = "";

// Get bag props.
if (is_array($bag))
{
	foreach ($bag as $k => $v)
	{
		$v['varyname'] = isset($v['varyname']) ? $v['varyname'] : '';
		$v['sums'] = isset($v['sums']) ? intval($v['sums']) : 0;
		$v['name'] = isset($v['name']) ? $v['name'] : '';
		$v['id'] = isset($v['id']) ? intval($v['id']) : 0;
		if ($v['varyname'] == 1 && $v['sums']>0)
		{
			$itemNameJs = challengeModJsSingle($v['name']);
			if (empty($bbfzp)) $bbfzp = "['".$itemNameJs."',".$v['sums'].','.$v['id']."]";
			else $bbfzp .= ",['".$itemNameJs."',".$v['sums'].','.$v['id']."]";
		}
		else if ($v['varyname'] == 3 && $v['sums']>0)
		{
			$itemNameJs = challengeModJsSingle($v['name']);
			if (empty($catcharr)) $catcharr = "['".$itemNameJs."',".$v['sums'].','.$v['id']."]";
			else $catcharr .= ",['".$itemNameJs."',".$v['sums'].','.$v['id']."]";
		}
	}

}else $bbfzp='0';
//
$user['fightbb'] = $bid;
if(!$_pm['mysql']->query("UPDATE player
			   SET fightbb={$bid}
			 WHERE id={$uid}
		  ")) die('保存挑战状态失败！');
//update fight status to memory.
$_pm['mem']->set(array('k' =>MEM_USER_KEY, 'v' => $user));
$_pm['mem']->set(array('k' =>MEM_USERBB_KEY, 'v' => $userbb));
$_pm['mem']->set(array('k' =>MEM_USERBAG_KEY, 'v' => $bag));
$_pm['mem']->memClose();

//###########################
// @Load template.
//###########################

$fn='tpl_challenge.html';
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
     x:ev.clientX + document.body.scrollLeft - document.body.clientLeft,
     y:ev.clientY + document.body.scrollTop     - document.body.clientTop
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
					 '#guildFight#',
					 '#flash#'
					);
		$des = array(
					 $bbinfo,
					 $gwinfo,
					 $jnlist,
					 rand(1,3),
					 $bid,
					 isset($_SESSION['nickname']) ? htmlspecialchars($_SESSION['nickname'], ENT_QUOTES, 'UTF-8') : '',
						 $bbHeadImg,
					 $bbfzp,
					 $catcharr,
					 $user['inmap'],
					 $mouse,
			         challengeModJsSingle($gw['username']),
					 $fortress_flag?'false;var fortressFight=true;':(
					 isset($flagChanllenger)?'true':'false')
					 ,
					 $flash
				);

	$fat = str_replace($src, $des, $tpl);
}



// gzip echo. if maybe.
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
flush();
ob_start('');
echo $fat;
ob_end_flush();

function err($str)
{
	die('<center>
			<div style="margin-top:100px;padding:5px;font-size:12px; line-height:1.7;width:99%;height:100px;overflow:hidden;">'. $str .'<br/>
				<<<a href="javascript:history.go(-1);">返回村庄</a>
			</div>
		</center>');

}
?>
