<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.08
*@Update Date: 2008.08.15
*@Usage:Fightting Function.
*@Note: NO Add magic props.
  本模块主要功能：
	 战场战斗处理网关脚本。
  主要：
  ###############################################################
     成功：设我方宠物与对方宠物的成长值之差=x
	       战场等级：提供军功基数与女神生命
		   军功值=取整{战场胜利军功基数*[1－(X－20）/100)]}
	       同时减少对方女神X点生命
     玩家失败：减自己阵营女生生命 1 点。

	 >> 加入战场活动时间限制。
	 >> 解决用户非法关闭浏览器问题。
  ###############################################################
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
	define(BATTLE_TIME_START, "20:00");
if (!defined('BATTLE_TIME_END'))
	define(BATTLE_TIME_END, "22:00");
if (!defined('BATTLE_TIME_WEEK'))
	define(BATTLE_TIME_WEEK, 5);*/

secStart($_pm['mem']);
//加速外挂
$time = time();
$sql = "SELECT time FROM fight_log WHERE uid = {$uid} and vary = 1";
$timearr = $_pm['mysql'] -> getOneRecord($sql);
if(is_array($timearr)){
	$_pm['mysql'] -> query("UPDATE fight_log SET time = ".time()." WHERE uid = {$uid} and vary = 1");
}else{
	$_pm['mysql'] -> query("INSERT INTO fight_log (uid,time,vary) VALUES({$uid},".time().",1)");
}
//在这里结束

$id			= (isset($_REQUEST['id']) && !is_array($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0;		// 	技能ID
if($id>1)
{
	$_SESSION['id'] = '';
	$drops='非法使用技能，断线惩罚！！！';
	$word='';
	echo '0,0,0#0,0#' . $drops . '#' . $word;
//stopUser(10);
	exit;
}
$id			= 1;
$gid		= (isset($_REQUEST['g']) && !is_array($_REQUEST['g'])) ? intval($_REQUEST['g']) : 0;	 	//  被挑战玩家的宠物ID
$db_bb		= array();	//	数据库中宝宝的原始属性。
$word = '';

function battleSettleDuel($uid,$won,$attackerGrowth,$defenderGrowth)
{
	global $_pm;
	$uid = intval($uid);
	$pre = $_pm['mysql']->getOneRecord('SELECT pos FROM battlefield_user WHERE uid='.$uid.' AND lastvtime>=UNIX_TIMESTAMP(CURDATE()) ORDER BY id LIMIT 1');
	if(!is_array($pre) || !in_array(intval($pre['pos']),array(1,2))) return false;
	$ownCamp = intval($pre['pos']);
	$campId = $won ? ($ownCamp == 1 ? 2 : 1) : $ownCamp;
	if(!$_pm['mysql']->query('START TRANSACTION')) return false;
	$camp = $_pm['mysql']->getOneRecord('SELECT id,hp FROM battlefield WHERE id='.$campId.' AND startf=1 AND ends=0 FOR UPDATE');
	if(!is_array($camp) || intval($camp['hp']) <= 0)
	{
		$_pm['mysql']->query('ROLLBACK');
		return false;
	}
	$cUser = $_pm['mysql']->getOneRecord('SELECT id,pos,addjgvalue,ackvalue,failackvalue,doublejg FROM battlefield_user WHERE uid='.$uid.' AND lastvtime>=UNIX_TIMESTAMP(CURDATE()) ORDER BY id LIMIT 1 FOR UPDATE');
	if(!is_array($cUser) || intval($cUser['pos']) != $ownCamp)
	{
		$_pm['mysql']->query('ROLLBACK');
		return false;
	}

	$jgvalue = 0;
	if($won)
	{
		$jgvalue = intval(intval($cUser['addjgvalue'])*(1-(intval($attackerGrowth)-intval($defenderGrowth)-20)/1000));
		$jgvalue *= intval($cUser['doublejg']) == 1 ? 3 : 2;
		if($jgvalue < 0) $jgvalue = 5;
		if(!$_pm['mysql']->query('UPDATE battlefield_user SET curjgvalue=COALESCE(curjgvalue,0)+'.$jgvalue.' WHERE id='.intval($cUser['id'])))
		{
			$_pm['mysql']->query('ROLLBACK');
			return false;
		}
		$damage = max(0,intval($cUser['ackvalue']));
	}
	else
	{
		$damage = max(0,intval($cUser['failackvalue']));
	}
	if($damage > 0 && (!$_pm['mysql']->query('UPDATE battlefield SET hp=GREATEST(0,hp-'.$damage.') WHERE id='.$campId.' AND startf=1 AND ends=0 AND hp>0') || mysql_affected_rows($_pm['mysql']->getConn()) != 1))
	{
		$_pm['mysql']->query('ROLLBACK');
		return false;
	}
	if(!$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		return false;
	}
	return array('jgvalue'=>$jgvalue);
}


$wgcheck = (isset($_GET['checkwg']) && !is_array($_GET['checkwg'])) ? $_GET['checkwg'] : '';
if($wgcheck != 'checked'){
	$_SESSION['id'] = '';
	die('<!--checkwg-->');
}

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
		break;
	}
}
if(empty($checkstr))
{
	die('<center><span style="font-size:12px;">战场还未开启！</span></center>');
}

/*if ($week != BATTLE_TIME_WEEK || ($hourM < BATTLE_TIME_START || $hourM > BATTLE_TIME_END) )
{
	die('<center><span style="font-size:12px;">战场还未开启！</span></center>'); // record log in here.
}*/

$user		= $_pm['user']->getUserById($_SESSION['id']);
$fight		= isset($_SESSION['fight'.$_SESSION['id']]) && is_array($_SESSION['fight'.$_SESSION['id']]) ? $_SESSION['fight'.$_SESSION['id']] : array('gid' => 0);
$cUser = $_pm['mysql']->getOneRecord("SELECT bid,pos,levels,lastvtime
										FROM battlefield_user
									   WHERE uid={$uid} AND pos IN(1,2) AND lastvtime>=UNIX_TIMESTAMP(CURDATE())
									   ORDER BY id LIMIT 1");
if(!is_array($user) || !is_array($cUser) || intval($cUser['bid']) < 1 || empty($fight['gid'])) exit('10');

/** 非法数据监测。*/
if ($fight['gid'] != $gid) stopUser();
/*###非法数据监测完成###*/

$requestEarly = isset($_REQUEST['early']) ? $_REQUEST['early'] : null;
$attackEarlySeconds = kdjlFightRequestEarlySeconds($requestEarly, $fight, $user, false, 'manual', 0);


// GET INFO FROM ARRAY.
//$_bb	 = $_pm['user']->getUserPetById($_SESSION['id']);

//$_sk	 = $_pm['user']->getUserPetSkillById($_SESSION['id']);

//$_sksys	 = unserialize($_pm['mem']->get(MEM_SKILLSYS_KEY));
//$_gpc	 = unserialize($_pm['mem']->get(MEM_GPC_KEY));


/* Fix read database fail!*/
if(intval($_SESSION['id'])<1||intval($cUser['bid'])<1) exit('10');
$_bb = $_pm['user']->getUserPetByIdS($_SESSION['id'],$cUser['bid']);
if (!is_array($_bb))
{
	$loop=true;
	$ct=0;
	while($loop)
	{
		$ct++;
		$_bb		 = $_pm['user']->getUserPetByIdS($_SESSION['id'],$cUser['bid']);
		if (is_array($_bb)) break;
		if($ct>10) exit('10');
		sleep(1);
	}
}
if(intval(isset($_bb['uid']) ? $_bb['uid'] : 0) != $uid ||
	intval(isset($_bb['muchang']) ? $_bb['muchang'] : 0) != 0 ||
	intval(isset($_bb['tgflag']) ? $_bb['tgflag'] : 0) != 0) exit('10');
$_sk		 = $_pm['user']->getUserPetSkillByIdS($_SESSION['id'],$_bb['id'],$id);

if(intval($_SESSION['id'])<1||intval($cUser['bid'])<1) exit('10');
if (!is_array($_sk))
{
	$_sk = $_pm['user']->getUserPetSkillByIdS($_SESSION['id'],$_bb['id'],1);
	if(!is_array($_sk)) exit('读取技能数据失败！');
}
/*** fix end ***/
// Get bb info for fightting.
if (is_array($_bb) && is_array($_sk))
{
	// Componse array .
	$rs = array_merge($_bb, array('s_name'  => $_sk['name'],
								's_level' => $_sk['level'],
								's_vary'  => $_sk['vary'],
								's_wx'	  => $_sk['wx'],
								's_value' => $_sk['value'],
								's_plus'  => $_sk['plus'],
								's_uhp'   => $_sk['uhp'],
								's_ump'   => $_sk['ump']
							   )
					 );
}
else $rs = '';

// The target must still be an active member of the opposite camp and bracket.
$battleLevelSql = $_pm['mysql']->escape($cUser['levels']);
$grs = $_pm['mysql']->getOneRecord("SELECT b.*
									FROM userbb b
									JOIN battlefield_user bu ON bu.bid=b.id AND bu.uid=b.uid
								   WHERE b.id={$gid} AND bu.pos<>".intval($cUser['pos'])."
									 AND bu.levels='{$battleLevelSql}' AND bu.lastvtime>=UNIX_TIMESTAMP(CURDATE())
									 AND b.muchang=0 AND b.tgflag=0
								   LIMIT 1");

if (!is_array($grs)) $skid=1;
else
{
	$alljn = array();
	$ar = explode(",", $grs['skillist']);
	foreach($ar as $k => $v)
	{
		$arr = explode(":", $v);
		$skillId = isset($arr[0]) ? intval($arr[0]) : 0;
		if ($skillId > 0 && !in_array($skillId, $alljn)) $alljn[] = $skillId;
	}
	$skid = count($alljn) > 0 ? $alljn[rand(0, count($alljn)-1)] : 1;
}

$enemySkill = is_array($grs) ? $_pm['user']->getUserPetSkillByIdS(intval($grs['uid']),$gid,$skid) : false;
if(!is_array($enemySkill) && $skid != 1)
{
	$skid = 1;
	$enemySkill = $_pm['user']->getUserPetSkillByIdS(intval($grs['uid']),$gid,1);
}
if(is_array($grs) && is_array($enemySkill))
{
	$gs = array_merge($grs,array(
		's_name'=>$enemySkill['name'],'s_wx'=>$enemySkill['wx'],'s_value'=>$enemySkill['value'],
		's_plus'=>$enemySkill['plus'],'s_uhp'=>$enemySkill['uhp'],'s_ump'=>$enemySkill['ump'],
		's_id'=>isset($enemySkill['sid']) ? $enemySkill['sid'] : $skid,
		's_level'=>isset($enemySkill['level']) ? $enemySkill['level'] : 1,
		's_vary'=>isset($enemySkill['vary']) ? $enemySkill['vary'] : 1,
		's_imgeft'=>isset($enemySkill['img']) ? $enemySkill['img'] : ''
	));
}
else $gs = '';

// END.
if (!is_array($gs)) $gs='';

if(is_array($rs) && is_array($gs))
{
//=================== 装备效果开始 =========================

	$db_bb = $rs;
	//########################
	// 附加装备属性到战斗中。
	#############################
	$att = getzbAttrib($rs['id']);
	$rs['ac']	+= $att['ac'];
	$rs['mc']	+= $att['mc'];
	$rs['hits'] += $att['hits'];
	$rs['speed']+= $att['speed'];
	$rs['miss']	+= $att['miss'];
	$mem_welcome = kdjlSafeMemValue($_pm['mem']->get('db_welcome'), array());
	if(!is_array($mem_welcome)) $mem_welcome = array();
	$mem_system_crit = 0;
	foreach($mem_welcome as $info)
	{
		if( $info['code'] == 'crit_rate' )
		{
			$mem_system_crit = $info['contents'];
		}
	}
	if( empty($mem_system_crit) )
	{
		$sql = " SELECT contents FROM welcome WHERE code = 'crit_rate'";
		$Crit_rate_db = $_pm['mysql']->getOneRecord($sql);
		$Crit_rate = is_array($Crit_rate_db) ? $Crit_rate_db['contents'] : 0;	//读数据库暴击率
	}
	else
	{
		$Crit_rate = $mem_system_crit;
	}
	//读宝宝装备暴击
	if( isset($att['crit']) )
	{
		$Crit_rate += intval($att['crit']);
	}
	$Crit_number = rand(1,100);	//最大100暴击率哦
	if( $Crit_number <= $Crit_rate )	//暴了
	{
		$Crit = 1;
	}
	else
	{
		$Crit = 0;	//没有暴
	}
	//----------------------------------------

	//----------------------------------------
	$aobj = new Ack($rs, $gs);
	$aobj -> getSkillAck();
	if(!$aobj->fixedDamage) $aobj->skillack += $att['ack'];
	$bbskillAddHP=0;
	$bbskillAddMP=0;
	//宠物对怪物攻击力
	$bback = $aobj -> skillack;

	$bb = $aobj->skillack . ',' . $rs['s_name'];
	$aobj1 = new Ack($gs, $rs);
	$aobj1 -> getSkillAck();
	//怪物对宠物攻击力
	$gwac = $aobj1 -> skillack;
	$gw = $gwac . ',' . $gs['s_name'];

	//计算吸血和吸魔
	$att = getzbAttrib($rs['id'],$gwac,$bback);
	$passiveDefense = $_pm['mysql']->getOneRecord('SELECT img FROM skill WHERE sid=112 AND bid='.intval($rs['id']).' LIMIT 1');
	if(is_array($passiveDefense) && isset($passiveDefense['img']))
	{
		$passiveParts = explode(':', $passiveDefense['img'], 2);
		if(isset($passiveParts[1]))
		{
			$passiveRate = floatval(str_replace('%', '', $passiveParts[1]));
			if($passiveRate > 0) $att['hpdx'] += round($passiveRate * $gwac * 0.01);
		}
	}
	//$rs['hp'] += $att['hp1'] + $att['hp2'];
	//$rs['mp'] += $att['mp1'];
	$gwac1 = max(0, $gwac - $att['hpdx']);
	$att['hpdx'] = max(0, $gwac - $gwac1);
	$gw = $gwac1 . ',' . $gs['s_name'];
	//$aobj -> skillack += $att['hp1'];

	$sql = "SELECT * FROM userbb
			WHERE id = {$rs['id']} and uid = {$uid}";
	$row = $_pm['mysql'] -> getOneRecord($sql);
	if(!is_array($row)) die('战斗宠物数据错误！');

	//计算加血，因为流程是玩家先加血，怪物再打玩家，所以应该先加血，怪物再打，
	//而不是把 玩家剩余的血+加的血-怪物的攻击 来当作玩家的最后的血
	//假如玩家 总血量 10000，剩余9000,怪物攻击力11000，玩家加血10000，这个时候玩家应该被打死！
	if($row['addhp']+$rs['hp']+$bbskillAddHP>$row['srchp']+$att['hp'])//完全加满
	{
		$row['addhp'] = $att['hp'];
		$rs['hp'] = $row['srchp'];
	}else if($rs['hp']+$bbskillAddHP>$row['srchp']){//加满hp，加不满addhp
		$row['addhp'] = min($row['addhp'] +$rs['hp']+$bbskillAddHP-$row['srchp'],$att['hp']);
		$rs['hp'] = $row['srchp'];
	}else{
		$rs['hp'] += $bbskillAddHP;
	}
	//加魔也一样
	if($row['addmp']+$rs['mp']+$bbskillAddMP>$row['srcmp']+$att['mp'])//完全加满
	{
		$row['addmp'] = $att['mp'];
		$rs['mp'] = $row['srcmp'];
	}else if($rs['mp']+$bbskillAddMP>$row['srcmp']){//加满mp，加不满addmp
		$row['addmp'] = min($row['addmp'] +$rs['mp']+$bbskillAddMP-$row['srcmp'],$att['mp']);
		$rs['mp'] = $row['srcmp'];
	}else{
		$rs['mp'] += $bbskillAddMP;
	}


	$srchp1 = $row['srchp'] + max(0, intval($att['hp']));
	$srcmp1 = $row['srcmp'] + max(0, intval($att['mp']));

	$ftgw = $_SESSION['fight'.$_SESSION['id']];
	$enemyHpBeforeAttack = is_array($ftgw) && isset($ftgw['hp']) ? max(0, floatval($ftgw['hp'])) : max(0, floatval($gs['hp']));
	//print_r($ftgw);exit;
//======================== 装备效果结束 ===============================

	if (!is_array($ftgw))	// 插入用户的战斗记录及参战的怪物数据。
	{
		$newhp = $gs['hp']-$aobj->skillack - $att['fhp'];
		$newmp = $gs['mp'];

		//加血时，怪物不减血

/*
if($rs['s_uhp']<0||$rs['s_ump']<0){
			$newhp = $gs['hp'];
		}
*/

		$_SESSION['fight'.$_SESSION['id']]=array('uid' => $_SESSION['id'],
												 'bid' => $rs['id'],
												 'gid' => $gid,
												 'hp'  => $newhp,
												 'mp'  => $newmp,
											 'fuzu'=> 0,
											 'ftime'=> time(),
											 'fatting'=> 1);
		$_SESSION['fight'.$_SESSION['id']] = kdjlFightStartState($_SESSION['fight'.$_SESSION['id']], $user, false, 'manual');
		$_SESSION['fight'.$_SESSION['id']] = kdjlFightMarkAttackState($_SESSION['fight'.$_SESSION['id']], $attackEarlySeconds);
		$ftgw = $_SESSION['fight'.$_SESSION['id']];
	}
	else if ($ftgw['fuzu']==0)	// 更新攻击后的HP,MP，
	{
		if ($ftgw['bid'] == $rs['id'] && $ftgw['fatting']==1)
		{
			$newhp = $ftgw['hp']-$aobj->skillack;
			$newmp = $ftgw['mp']-$gs['s_ump']; // in here add mp part..<<<<<<<<<<<
		}
		else
		{
			$newhp = $gs['hp']-$aobj->skillack;
			$newmp = $gs['mp']-$gs['s_ump'];
		}
		//加血时，怪物不减血
		if($rs['s_uhp']<0||$rs['s_ump']<0){
			//$newhp = $gs['hp'];
		}

		if ($newhp<0) $newhp = 0;
		if ($newmp<0) $newmp=0;
		$r = $fight;
		$r['hp']			=$newhp;
		$r['mp']			=$newmp;
		$r['fatting']		=1;
		$r['fuzu']		=0;
		$r = kdjlFightMarkAttackState($r, $attackEarlySeconds);

		$_SESSION['fight'.$_SESSION['id']] = $r;
	}
	else if($ftgw['fuzu'] == 1) //解除用户攻击一回锁定。
	{
		$r = $_SESSION['fight'.$_SESSION['id']];
		$r['fuzu']= 0;
		$r = kdjlFightMarkAttackState($r, $attackEarlySeconds);

		$_SESSION['fight'.$_SESSION['id']] = $r;
		$aobj->skillack = 0;
		$newhp = $ftgw['hp'];
		$newmp = $ftgw['mp'];
	}
	$enemyCountered = ($aobj->skillack < $enemyHpBeforeAttack);
	$reflectedDamage = $enemyCountered ? kdjlReflectedDamage($gwac1, isset($att['shft']) ? $att['shft'] : 0) : 0;
	if($reflectedDamage > 0)
	{
		$newhp = max(0, intval(round(floatval($newhp) - $reflectedDamage)));
		$fightSessionKey = 'fight'.$_SESSION['id'];
		if(isset($_SESSION[$fightSessionKey]) && is_array($_SESSION[$fightSessionKey]))
		{
			$_SESSION[$fightSessionKey]['hp'] = $newhp;
		}
	}

	// 更新用户BB信息。
	// 如果BB秒杀怪物，则不减自己的生命.

	######################宠物的剩余血量和魔法###########################
	$addhp = max(0, intval($row['addhp']));
	$addmp = max(0, intval($row['addmp']));
	if ($enemyCountered)
	{
		//计算装备所加所有的hp
		$sumhp = $att['hp1'] + $att['hp2'] + $row['addhp'];
		if($sumhp > $gwac1)
		{
			$addhp = $sumhp - $gwac1;
			$nhp = $rs['hp'];
			//判断宠物的hp是否超过最大值
			$sumhp1 = $addhp + $nhp;
			if($sumhp1 > $srchp1)
			{
				$addhp = $srchp1 - $nhp;
			}
		}
		else
		{
			$nhp = $sumhp + $rs['hp'] - $gwac1;
			$addhp = 0;
		}
		//$nhp = $rs['hp']-$aobj1->skillack;

	}
	//计算装备所加的mp
	else
	{
		$killRecovery = max(0, floatval($att['hp1'])) + max(0, floatval($att['hp2']));
		$killTotalHp = min($srchp1, max(0, floatval($rs['hp']) + floatval($row['addhp']) + $killRecovery));
		$nhp = min(intval($row['srchp']), intval(round($killTotalHp)));
		$addhp = max(0, intval(round($killTotalHp)) - $nhp);
	}
	$skillMpCost = max(0,floatval($rs['s_ump']));
	$summp = $att['mp1'] + $row['addmp'];
	if($summp > $skillMpCost)
	{
		$nmp = $rs['mp'];
		$addmp = $summp - $skillMpCost;
		//判断宠物的mp是否超过最大值
		$summp1 = $addmp + $nmp;
		if($summp1 > $srcmp1)
		{
			$addmp = $srcmp1 - $nmp;
		}
	}
	else
	{
		$nmp = $summp + $rs['mp'] - $skillMpCost;
		$addmp = 0;
	}
	//$nmp = $rs['mp']-$rs['s_ump'];

	if ($nhp<0) $nhp=0;
	if ($nmp<0) $nmp=0;
	$addhp = max(0, intval(round($addhp)));
	$addmp = max(0, intval(round($addmp)));

	if(!$_pm['mysql']->query("UPDATE userbb
				   SET hp={$nhp},
				       mp={$nmp},
					     addmp={$addmp},
					   addhp={$addhp}
				 WHERE id={$rs['id']} and uid={$_SESSION['id']}
			  ")) die('保存战斗宠物状态失败！');

	if ($nhp == 0)
	{
		if(battleSettleDuel($uid,false,$rs['czl'],$gs['czl']) === false) die('战斗结算失败！');
		$drops='很遗憾，战斗失败！';
	}
	else if ($newhp == 0) // gaiwu die
    {
		$settled = battleSettleDuel($uid,true,$rs['czl'],$gs['czl']);
		if(!is_array($settled)) die('战斗结算失败！');
		$jgvalue = intval($settled['jgvalue']);
		$drops = "<br/><font size=+1>恭喜您，获得了本次战斗的胜利！</font><br/>您获得了 <font size=30% color=yellow>{$jgvalue}</font> 点军功！";
	}
	else $drops='';

	if ($newhp == 0 || $nhp == 0) {
		$r =$_SESSION['fight' . $_SESSION['id']];
		$r['hp']		= $newhp;
		$r['mp']		= $newmp;
		$r['fatting']	= 0;
		$r['fuzu']		= 0;
		$r['gid']		= 0;
		$r['ftime']	= time();
		$r = kdjlFightFinishState($r, $user, false, intval($cUser['bid']), 'manual');
		$_SESSION['fight'.$_SESSION['id']]= $r;
	}
	// Free resource.
	$_pm['mem']->memClose();
	$sql = "SELECT addmp,addhp FROM userbb WHERE uid = {$_SESSION['id']} and id = {$rs['id']}";
		$add = $_pm['mysql'] -> getOneRecord($sql);
		if(!is_array($add)) $add = array('addhp' => 0, 'addmp' => 0);
		$nhp += $add['addhp'];
		if($nhp > $srchp1)
		{
			$nhp = $srchp1;
		}
		$nmp += $add['addmp'];
		if($nmp > $srcmp1)
		{
			$nmp = $srcmp1;
		}
	if(!empty($att['hp1']) && empty($att['mp1']))
	{
		$echo_str =  $nhp . ',' . $nmp. ',' . $bb.',<br />吸血'.$att['hp1'].'#'. $newhp . ',' . $gw.'#' . $drops . '#' . $word;
	}

	else if(!empty($att['hp1']) && !empty($att['mp1']) && $att['mp1'] > 0)
	{
		$echo_str = $nhp . ',' . $nmp. ',' . $bb.',<br />吸血'.$att['hp1'].'&nbsp;==<br />吸魔'.$att['mp1'].'&nbsp;#'. $newhp . ',' . $gw.'#' . $drops . '#' . $word;
	}
	else if(!empty($att['hp1']) && !empty($att['mp1']) && $att['mp1'] < 0)
	{
		$echo_str = $nhp . ',' . $nmp. ',' . $bb.',<br />吸血'.$att['hp1'].'&nbsp;==<br />失魔'.$att['mp1'].'&nbsp;#'. $newhp . ',' . $gw.'#' . $drops . '#' . $word;
	}
	else if(empty($att['hp1']) && !empty($att['mp1']) && $att['mp1'] < 0)
	{
		$echo_str = $nhp . ',' . $nmp. ',' . $bb.'<br /> 失魔'.$att['mp1'].'&nbsp;#'. $newhp . ',' . $gw.'#' . $drops . '#' . $word;
	}
	else if(empty($att['hp1']) && !empty($att['mp1']) && $att['mp1'] > 0)
	{
		$echo_str = $nhp . ',' . $nmp. ',' . $bb.',<br />吸魔'.$att['mp1'].'&nbsp;#'. $newhp . ',' . $gw.'#' . $drops . '#' . $word;
	}
	else
	{
		$echo_str = $nhp . ',' . $nmp. ',' . $bb.'#'.$newhp . ',' . $gw.'#' . $drops . '#' . $word;
	}
	$defenseNotes = array();
	if(!empty($att['hpdx'])) $defenseNotes[] = '抵消：'.$att['hpdx'];
	if($reflectedDamage > 0) $defenseNotes[] = '反弹伤害：'.$reflectedDamage;
	if(!empty($defenseNotes)) $echo_str .= '<dx>'.implode('<br />', $defenseNotes);
	$echo_str .= "*".($aobj->fixedDamage ? 0 : $Crit);	//是否暴击
	$ack_type = 0;
	$echo_str .= "*".$ack_type;	//五行攻击
	echo $echo_str;
}
else
{	$drops='宝宝 ' . $grs['name'].' 逃跑了！！！';
	echo '0,0,0#0,0#' . $drops . '#' . $word;
}

// =========================




?>
