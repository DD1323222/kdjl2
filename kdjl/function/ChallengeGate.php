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
  完成玩家挑战功能。
*/
require_once('../config/config.game.php');
require_once(dirname(__FILE__).'/fortress_common.php');
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1)
{
	die("非法进入");
}
$_SESSION['id'] = $uid;
define('MEM_BOSS_KEY',	$uid . 'boss');
define('MEM_FIGHT_KEY',	$uid . 'fight');

secStart($_pm['mem']);
$mmonsterContinueFlag = 'NOT';
$str = '';
$word = '';
$mem_system_crit = 0;

function challengeGateHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function challengeRecordFightResult($winnerUid, $loserUid)
{
	global $_pm;
	$winnerUid = intval($winnerUid);
	$loserUid = intval($loserUid);
	if($winnerUid < 1 || $loserUid < 1 || $winnerUid == $loserUid) return false;
	if(!$_pm['mysql']->query('START TRANSACTION')) return false;
	$rows = $_pm['mysql']->getRecords('SELECT id,fighttop FROM player WHERE id IN('.$winnerUid.','.$loserUid.') ORDER BY id FOR UPDATE');
	if(!is_array($rows) || count($rows) != 2)
	{
		$_pm['mysql']->query('ROLLBACK');
		return false;
	}
	foreach($rows as $row)
	{
		$playerId = isset($row['id']) ? intval($row['id']) : 0;
		$parts = isset($row['fighttop']) ? explode(':', $row['fighttop']) : array();
		$wins = isset($parts[0]) ? max(0, intval($parts[0])) : 0;
		$losses = isset($parts[1]) ? max(0, intval($parts[1])) : 0;
		if($playerId == $winnerUid) $wins++;
		else if($playerId == $loserUid) $losses++;
		else
		{
			$_pm['mysql']->query('ROLLBACK');
			return false;
		}
		if(!$_pm['mysql']->query("UPDATE player SET fighttop='{$wins}:{$losses}' WHERE id={$playerId}"))
		{
			$_pm['mysql']->query('ROLLBACK');
			return false;
		}
	}
	if(!$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		return false;
	}
	return true;
}
function challengeFortressSettle($tableName, $uid, $expectedGpcId, $sqlExtra, $scoreDelta, $cardId, $cardImage)
{
	global $_pm;
	$uid = intval($uid);
	$expectedGpcId = intval($expectedGpcId);
	$scoreDelta = intval(round($scoreDelta));
	if($uid < 1 || $expectedGpcId < 1) return false;

	$previousCards = fortressDailyCacheGet($_pm['mem'], 'cards', $uid, array());
	if(!is_array($previousCards)) $previousCards = array();
	$newCards = $previousCards;
	$newCards[] = array('id' => intval($cardId), 'img' => $cardImage);

	if(!$_pm['mysql']->query('START TRANSACTION')) return false;
	$sql = 'update '.$tableName.' set cur_gpc_id=0'.$sqlExtra.',score=COALESCE(score,0)+'.$scoreDelta.
		' where user_id='.$uid.' and cur_gpc_id='.$expectedGpcId;
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
	{
		$_pm['mysql']->query('ROLLBACK');
		return false;
	}
	if(!$_pm['mysql']->query('UPDATE userbb,player SET hp=srchp,mp=srcmp,addmp=0,addhp=0 WHERE mbid=userbb.id AND userbb.uid=player.id AND player.id='.$uid))
	{
		$_pm['mysql']->query('ROLLBACK');
		return false;
	}
	if(!fortressDailyCacheSet($_pm['mem'], 'cards', $uid, $newCards))
	{
		$_pm['mysql']->query('ROLLBACK');
		return false;
	}
	if(!$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		fortressDailyCacheSet($_pm['mem'], 'cards', $uid, $previousCards);
		return false;
	}
	return true;
}
$id			= (isset($_REQUEST['id']) && !is_array($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0;		// 	技能ID
$fortress_flag=false;
if( isset($_SESSION['first_in']) && ($_SESSION['first_in'] == 2 || $_SESSION['first_in'] == 3) )
{
	$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$uid."'");
}
$_SESSION['first_in'] = 2;
$isGuildFight = isset($_GET['guildFight'])
	&& isset($_SESSION['guild_fight_id'], $_SESSION['guild_fight_bid'], $_SESSION['guild_fight_time'])
	&& intval($_SESSION['guild_fight_id']) > 0
	&& intval($_SESSION['guild_fight_bid']) > 0
	&& intval($_SESSION['guild_fight_time']) + 300 > time();
if($isGuildFight)
{
	require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
	$s=new socketmsg();
	$guild=new guild($s);

	$gid		= intval($_SESSION['guild_fight_bid']);
}else if(isset($_SESSION['fortress_gpc_time'])&&$_SESSION['fortress_gpc_time']+18>time()){
	$fortress_flag=true;
	$_SESSION['fortress_gpc_time']=time();
	$gid		= (isset($_REQUEST['g']) && !is_array($_REQUEST['g'])) ? intval($_REQUEST['g']) : 0;
	$table_name="`fortress_users_".date("Ymd")."`";
	$user_fortress=$_pm['mysql']->getOneRecord('select cur_gpc_id,bb_id,at_section_num from '.$table_name.' where user_id='.$_SESSION['id']);
	if(!$user_fortress)
	{
		$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");
		$_SESSION['first_in'] = 3;
		die('你没有进入要塞!');
	}

	if(!$user_fortress['cur_gpc_id'])
	{
		$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");
		$_SESSION['first_in'] = 3;
		header('location:/function/');
		die('你没有进入要塞!');
	}

	$setting = $_pm['mem']->get('db_welcome1');
	if(!is_array($setting)) $setting=kdjlSafeMemValue($setting, array());
	if(!is_array($setting))
	{
		$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");
		$_SESSION['first_in'] = 3;
		die('要塞配置读取失败！');
	}

	if(!isset($setting['fortress_time']))
	{
		$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");
		$_SESSION['first_in'] = 3;
		die('要塞开放时间配置缺失！');
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
		//1,210000,210459,212959,213459
		if(count($tmp) < 4)
		{
			continue;
		}
		if($w==$tmp[0])
		{
			if($hm>=$tmp[2]&&$hm<=$tmp[3])
			{
				$time_flag=true;
			}
			break;
		}
	}

	if(!$time_flag){
		$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");
		$_SESSION['first_in'] = 3;
		die('1,1,1,普通攻击,#0,1,普通攻击#获得经验：0<br/>获得金币：0+0 个
					  <br/>捕获宠物：0<br/><font color=#ff0000>现在不是战斗时间</font><br/>特殊奖励：无<br/>##1,0,0#NOT');
	}

	$user['fightbb']= $user_fortress['bb_id'];
	$user['mbid']   = $user_fortress['bb_id'];
	$gid			= $user_fortress['cur_gpc_id'];
	$_pm['mysql']-> query('update player set mbid='.($user_fortress['bb_id']).',fightbb='.$user_fortress['bb_id'].' where id='.$_SESSION['id']);
}else{
	$gid		= (isset($_REQUEST['g']) && !is_array($_REQUEST['g'])) ? intval($_REQUEST['g']) : 0;	 	//  被挑战玩家的宠物ID
}
$db_bb		= array();	//	数据库中宝宝的原始属性。
//$user		= $_pm['user']->getUserById($_SESSION['id']);
$user	 = kdjlSafeMemValue($_pm['mem']->get(MEM_USER_KEY), array());
if(!is_array($user) || !isset($user['fightbb'])) $user = $_pm['user']->getUserById($uid);
if(!is_array($user)) $user = array();
if($fortress_flag && isset($user_fortress['bb_id']))
{
	$user['fightbb']= $user_fortress['bb_id'];
	$user['mbid']   = $user_fortress['bb_id'];
}
$fight		= isset($_SESSION['fight'.$_SESSION['id']]) && is_array($_SESSION['fight'.$_SESSION['id']]) ? $_SESSION['fight'.$_SESSION['id']] : array('gid' => 0);
if ( empty($fight['gid']) )
{
	$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");
	$_SESSION['first_in'] = 3;
	exit();
}

if(!$fortress_flag && !$isGuildFight)
{
	$expectedTargetBid = isset($_SESSION['challenge_target_bid']) ? intval($_SESSION['challenge_target_bid']) : 0;
	$expectedTargetUid = isset($_SESSION['challenge_target_uid']) ? intval($_SESSION['challenge_target_uid']) : 0;
	if($gid < 1 || $gid != intval($fight['gid']) || $gid != $expectedTargetBid || $expectedTargetUid < 1)
	{
		$_pm['mysql']->query("UPDATE player_ext SET F_Medicine_Buff='' WHERE uid={$uid}");
		$_SESSION['first_in'] = 3;
		exit('10');
	}
}

$_bag = kdjlSafeMemValue($_pm['mem']->get(MEM_USERBAG_KEY), array());
//$sk	 = unserialize($_pm['mem']->get(MEM_USERSK_KEY));

$_sksys	 = kdjlSafeMemValue($_pm['mem']->get(MEM_SKILLSYS_KEY), array());
$_gpc	 = kdjlSafeMemValue($_pm['mem']->get(MEM_GPC_KEY), array());


/* Fix read database fail!*/
if(intval($_SESSION['id'])<1||intval($user['fightbb'])<1)
{
	$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");
	$_SESSION['first_in'] = 3;
	exit('10');
}
$_bb = $_pm['user']->getUserPetByIdS($_SESSION['id'],$user['fightbb']);
if (!is_array($_bb))
{
	$loop=true;
	$ct=0;
	while($loop)
	{
		$ct++;
		$_bb		 = $_pm['user']->getUserPetByIdS($_SESSION['id'],$user['fightbb']);
		if (is_array($_bb)) break;
		if($ct>10)
		{
			$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");
			$_SESSION['first_in'] = 3;
			exit('10');
		}
		sleep(1);
	}
}
if(intval(isset($_bb['uid']) ? $_bb['uid'] : 0) != $uid ||
	intval(isset($_bb['muchang']) ? $_bb['muchang'] : 0) != 0 ||
	intval(isset($_bb['tgflag']) ? $_bb['tgflag'] : 0) != 0) exit('10');
$_sk		 = $_pm['user']->getUserPetSkillByIdS($_SESSION['id'],$_bb['id'],$id);
if(intval($_SESSION['id'])<1||intval($user['fightbb'])<1) exit('10');
if (!is_array($_sk))
{
	$_sk = $_pm['user']->getUserPetSkillByIdS($_SESSION['id'],$_bb['id'],1);
	if(!is_array($_sk)) exit('读取技能数据失败！');
}
$availableMp = max(0, floatval(isset($_bb['mp']) ? $_bb['mp'] : 0) + floatval(isset($_bb['addmp']) ? $_bb['addmp'] : 0));
$requestedMpCost = max(0, floatval(isset($_sk['ump']) ? $_sk['ump'] : 0));
if($id != 1 && $requestedMpCost > $availableMp)
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
								's_ump'   => $_sk['ump'],
								's_imgeft'=> isset($_sk['img']) ? $_sk['img'] : ''
							   )
					 );
}
else $rs = '';

if(!$fortress_flag){
	$targetOwner = $isGuildFight
		? (isset($_SESSION['guild_fight_id']) ? intval($_SESSION['guild_fight_id']) : 0)
		: (isset($_SESSION['challenge_target_uid']) ? intval($_SESSION['challenge_target_uid']) : 0);
	$gs = $_pm['mysql']->getOneRecord("SELECT * FROM userbb WHERE id={$gid} AND uid={$targetOwner} LIMIT 1");
}else{
	if(isset($_SESSION['fortress_gw']) && is_array($_SESSION['fortress_gw'])){
		$gs = $_SESSION['fortress_gw'];
	}else{
		$fortressGwId = isset($_SESSION['fortress_gw']) ? intval($_SESSION['fortress_gw']) : 0;
		$gs = $_pm['mysql']->getOneRecord("SELECT * FROM userbb WHERE id={$fortressGwId} LIMIT 1");
		if(is_array($gs)) $gs['hp'] = $gs['srchp'];
	}
}

$grs=$gs;
$skillSource = is_array($grs) && isset($grs['skillist']) ? $grs['skillist'] : (is_array($grs) && isset($grs['skill']) ? $grs['skill'] : '');
$alljn = array();
foreach(explode(',', $skillSource) as $skillEntry)
{
	$skillParts = explode(':', $skillEntry);
	$skillId = isset($skillParts[0]) ? intval($skillParts[0]) : 0;
	if($skillId > 0 && !in_array($skillId, $alljn)) $alljn[] = $skillId;
}
$skid = count($alljn) > 0 ? $alljn[rand(0, count($alljn)-1)] : 1;
$yIsUserSkill = false;
$enemyOwnerId = is_array($grs) && isset($grs['uid']) ? intval($grs['uid']) : 0;
$y = $enemyOwnerId > 0 ? $_pm['user']->getUserPetSkillByIdS($enemyOwnerId,$gid,$skid) : getBaseSkillSysInfoById($skid);
if(is_array($y) && $enemyOwnerId > 0) $yIsUserSkill = true;
if(!is_array($y) && $skid != 1)
{
	$skid = 1;
	$y = $enemyOwnerId > 0 ? $_pm['user']->getUserPetSkillByIdS($enemyOwnerId,$gid,1) : getBaseSkillSysInfoById(1);
	if(is_array($y) && $enemyOwnerId > 0) $yIsUserSkill = true;
}

if (is_array($gs) && is_array($y))
{
	// Componse array .
	$gs = array_merge($gs, array('s_name'  => isset($y['name']) ? $y['name'] : '',
								's_level' => isset($y['level']) ? $y['level'] : 1,
								's_vary'  => isset($y['vary']) ? $y['vary'] : 1,
								's_wx'	  => isset($y['wx']) ? $y['wx'] : 0,
								's_value' => $yIsUserSkill ? (isset($y['value']) ? $y['value'] : 0) : (isset($y['ackvalue']) ? $y['ackvalue'] : 0),
								's_plus'  => isset($y['plus']) ? $y['plus'] : '',
								's_uhp'   => isset($y['uhp']) ? $y['uhp'] : 0,
								's_ump'   => isset($y['ump']) ? $y['ump'] : 0,
								's_id'	  => $yIsUserSkill ? (isset($y['sid']) ? $y['sid'] : $skid) : (isset($y['id']) ? $y['id'] : $skid),
								's_imgeft'=> isset($y['img']) ? $y['img'] : ''
							   )
					 );
}
else $gs = '';

// END.
if (!is_array($gs)) $gs='';

if(is_array($rs) && is_array($gs))
{
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
	//战斗药品附加效果
	$medicine_buff = $_pm['mysql'] -> getOneRecord(" SELECT F_Medicine_Buff FROM player_ext WHERE uid = '".$_SESSION['id']."'");
	if( isset($medicine_buff['F_Medicine_Buff']) && !empty($medicine_buff['F_Medicine_Buff']) )
	{
		$med_buff_arr_all = explode(',',$medicine_buff['F_Medicine_Buff']);
		foreach( $med_buff_arr_all as $info )
		{
			$med_buff_arr = explode(':',$info);	//addac:10
			if(count($med_buff_arr) < 2 || $med_buff_arr[1] === '') continue;
			switch($med_buff_arr[0])
			{
				case "addac" :
				{
					if( strstr($med_buff_arr[1],'%') )	//有百分号
					{
						$med_buff_arr[1] = substr($med_buff_arr[1],0,-1);	//去百分号
						$rs['ac'] = (1+$med_buff_arr[1]/100)*$rs['ac'];
					}
					else
					{
						$rs['ac'] += $med_buff_arr[1];
					}
					break;
				}
				case "addmc" :
				{
					if( strstr($med_buff_arr[1],'%') )	//有百分号
					{
						$med_buff_arr[1] = substr($med_buff_arr[1],0,-1);	//去百分号
						$rs['mc'] = (1+$med_buff_arr[1]/100)*$rs['mc'];
					}
					else
					{
						$rs['mc'] += $med_buff_arr[1];
					}
					break;
				}
			}
		}

	}
	$mem_welcome = kdjlSafeMemValue($_pm['mem']->get('db_welcome'), array());
	if(!is_array($mem_welcome)) $mem_welcome = array();
	foreach($mem_welcome as $info)
	{
		if(is_array($info) && isset($info['code']) && $info['code'] == 'crit_rate')
		{
			$mem_system_crit = isset($info['contents']) ? $info['contents'] : 0;
		}
	}
	if( empty($mem_system_crit) )
	{
		$sql = " SELECT contents FROM welcome WHERE code = 'crit_rate'";
		$Crit_rate_db = $_pm['mysql']->getOneRecord($sql);
		$Crit_rate = is_array($Crit_rate_db) ? $Crit_rate_db['contents'] : 0;
	}
	else
	{
		$Crit_rate = $mem_system_crit;
	}
	// 叠加装备暴击
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
		$Crit = 0;
	}
	//----------------------------------------
	//----------------------------------------
	$aobj = new Ack($rs, $gs);
	$aobj -> getSkillAck();

	$bbskillAddHP=0;
	$bbskillAddMP=0;
	// 技能生命或魔法效果为负时给宠物自身恢复
	if($rs['s_uhp']<0||$rs['s_ump']<0){
		$bback = 0;
		$bb = '0,' . $rs['s_name'];
		$aobj->skillack = 0;
		//技能增加hp or mp
		if($rs['s_uhp']<0) $bbskillAddHP-=$rs['s_uhp'];
		if($rs['s_ump']<0) $bbskillAddMP-=$rs['s_ump'];
	}else{
		$bback = $aobj -> skillack;
		$bb = $aobj->skillack . ',' . $rs['s_name'];
	}

	$bb = number_format($aobj->skillack,'','',''). ',' . $rs['s_name'];
	$aobj1 = new Ack($gs, $rs);
	$aobj1 -> getSkillAck();

	$gwac= $aobj1->skillack;// . ',' . $gs['s_name'];


	//计算吸血和吸魔
	$att = getzbAttrib($rs['id'],$gwac,$bback);
	//dxsh 转化为被动技能
	$jnnewarr = $_pm['mysql'] -> getOneRecord("SELECT img FROM skill WHERE sid = 112 AND bid = ".intval($rs['id']));
	if(is_array($jnnewarr) && isset($jnnewarr['img'])){
		$add_s_imgeft_arr1 = explode(':',$jnnewarr['img']);
		if(isset($add_s_imgeft_arr1[1]))
		{
			$add_s_imgeft_arr = str_replace('%','',$add_s_imgeft_arr1[1]);
			$att['hpdx'] += round($add_s_imgeft_arr * $gwac *0.01);
		}
	}
	//增加技能效果:hitshp:命中吸取伤害的百分比转化为生命:
	if(!empty($rs['s_imgeft']))
	{
		$jnar = explode(":",$rs['s_imgeft']);
		if(count($jnar) < 2) $jnar = array('', '0%');
		$sp = explode("%",$jnar[1]);
		$num = $sp[0] / 100;
		switch($jnar[0])
		{
			case "hitshp":
				$att['hp1'] += round($num * $bback);
				//echo "bback:".$bback."num:".$num."hp1:".$att['hp1'];
				break;
			case "dxsh":
				$att['hpdx'] += round($num * $gwac);
				break;
			case "shjs":
				$att['ack'] += round($num * $bback);
				break;
		}
	}

	//$rs['hp'] += $att['hp1'] + $att['hp2'];
	//$rs['mp'] += $att['mp1'];
	$gwac1 = max(0, $gwac - $att['hpdx']);
	$att['hpdx'] = max(0, $gwac - $gwac1);
	$gw = number_format($gwac1,'','','') . ',' . $gs['s_name'];
	$gw1 = number_format($gwac,'','','') . ',' . $gs['s_name'];
	if(!$aobj->fixedDamage && !empty($att['ack']))
	{
		$aobj->skillack += $att['ack'];
	}
	$bb = number_format($aobj->skillack,'','',''). ',' . $rs['s_name'];
	//$aobj -> skillack += $att['hp1'];

	$sql = "SELECT * FROM userbb
			WHERE id = {$rs['id']} and uid = {$uid}";
	$row = $_pm['mysql'] -> getOneRecord($sql);
	if(!is_array($row)) die('主战宠物数据异常！');

	$baseMaxHp = max(0, intval($row['srchp']));
	$baseMaxMp = max(0, intval($row['srcmp']));
	$maxHp = $baseMaxHp + max(0, intval(round(isset($att['hp']) ? $att['hp'] : 0)));
	$maxMp = $baseMaxMp + max(0, intval(round(isset($att['mp']) ? $att['mp'] : 0)));
	$currentTotalHp = intval(round(min($maxHp, max(0, floatval($row['hp']) + floatval($row['addhp']) + $bbskillAddHP))));
	$currentTotalMp = intval(round(min($maxMp, max(0, floatval($row['mp']) + floatval($row['addmp']) + $bbskillAddMP))));
	$rs['hp'] = min($baseMaxHp, $currentTotalHp);
	$rs['mp'] = min($baseMaxMp, $currentTotalMp);


	$ftgw = $_SESSION['fight'.$_SESSION['id']];
	$enemyBaseHp = isset($gs['srchp']) ? $gs['srchp'] : (isset($gs['hp']) ? $gs['hp'] : 0);
	$enemyHpBeforeAttack = is_array($ftgw) && isset($ftgw['hp']) ? max(0, floatval($ftgw['hp'])) : max(0, floatval($enemyBaseHp));
	if (!is_array($ftgw))
	{
		$newhp = $gs['srchp']-$aobj->skillack;
		$newmp = $gs['srcmp'];
		$_SESSION['fight'.$_SESSION['id']]=array('uid' => $_SESSION['id'],
												 'bid' => $rs['id'],
												 'gid' => $gid,
												 'hp'  => $newhp,
												 'mp'  => $newmp,
												 'fuzu'=> 0,
												 'ftime'=> time(),
												 'fatting'=> 1);
	}
	else if ($ftgw['fuzu']==0)
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

		if ($newhp<0) $newhp = 0;
		if ($newmp<0) $newmp=0;
		$r = $fight;
		$r['hp']			=$newhp;
		$r['mp']			=$newmp;
		$r['fatting']		=1;
		$r['ftime']		=time();
		$r['fuzu']		=0;

		$_SESSION['fight'.$_SESSION['id']] = $r;
	}
	else if($ftgw['fuzu'] == 1)
	{
		$r = $_SESSION['fight'.$_SESSION['id']];
		$r['fuzu']= 0;

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
	// 装备附加生命和魔法分别保存在 addhp/addmp，按总量结算后再拆回字段。
	$incomingDamage = $enemyCountered ? max(0, floatval($gwac1)) : 0;
	$hpRecovery = max(0, floatval(isset($att['hp1']) ? $att['hp1'] : 0)) + max(0, floatval(isset($att['hp2']) ? $att['hp2'] : 0));
	$newTotalHp = intval(round(min($maxHp, max(0, $currentTotalHp + $hpRecovery - $incomingDamage))));
	$nhp = min($baseMaxHp, $newTotalHp);
	$addhp = max(0, $newTotalHp - $nhp);
	$skillMpCost = max(0, floatval($rs['s_ump']));
	$mpRecovery = floatval(isset($att['mp1']) ? $att['mp1'] : 0);
	$newTotalMp = intval(round(min($maxMp, max(0, $currentTotalMp + $mpRecovery - $skillMpCost))));
	$nmp = min($baseMaxMp, $newTotalMp);
	$addmp = max(0, $newTotalMp - $nmp);
	$displayHp = $nhp + $addhp;
	$displayMp = $nmp + $addmp;

	if(!$_pm['mysql']->query("UPDATE userbb
				   SET hp={$nhp},
				       mp={$nmp},
				       addhp={$addhp},
				       addmp={$addmp}
				 WHERE id={$rs['id']} and uid={$_SESSION['id']}
			  ")) die('主战宠物状态保存失败！');
	$drops='';
	if($isGuildFight)
	{
		$guildFightOpponentId = isset($_SESSION['guild_fight_id']) ? intval($_SESSION['guild_fight_id']) : 0;
		if ($newhp == 0)
		{
			$guildFightResult=$guild->writeGuildFightScore($uid,$guildFightOpponentId);
			$drops='战斗胜利！<br/>'.$guildFightResult;
		}else if ($nhp == 0){
			$guildFightResult=$guild->writeGuildFightScore($guildFightOpponentId,$uid);
			$drops='战斗失败！<br/>'.$guildFightResult;
		}
		if($newhp == 0 || $nhp == 0)
		{
			$guild->clearGuildFightSession();
			$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$uid."'");
			$_SESSION['first_in'] = 3;
		}

		$hasAutoRecover=$_pm['mysql']->getOneRecord('select userbag.id from userbag,props where userbag.uid='.$_SESSION['id'].' and userbag.pid=props.id and props.varyname=21 limit 1');

		if($hasAutoRecover)
		{
			$_pm['mysql']->query("UPDATE userbb,player
									 SET hp=srchp,mp = srcmp,addmp = 0,addhp = 0
								   WHERE mbid=userbb.id and userbb.uid=player.id and player.id=".$_SESSION['id']);
		}
	}else if($fortress_flag){
		if ($newhp == 0)
		{
			$_SESSION['guild_fight_time'] = 0;
			$table_name="`fortress_users_".date("Ymd")."`";
			$user_fortress=$_pm['mysql']->getOneRecord('select cur_gpc_id,bb_id,at_section_num,fv_result from '.$table_name.' where user_id='.$_SESSION['id']);
			if(!is_array($user_fortress)) $user_fortress = array('fv_result'=>0);
			if($user_fortress['fv_result']>=0)
			{
				$sql_extra=',v_times=COALESCE(v_times,0)+1,fv_result=COALESCE(fv_result,0)+1';
				$get_score=(2*abs($user_fortress['fv_result']+1)-1)*10;
			}
			else
			{
				$sql_extra=',v_times=COALESCE(v_times,0)+1,fv_result=1';
				$get_score=10;
			}

			$row=$_pm['mysql']->getOneRecord('select buff_status from player_ext where uid='.$_SESSION['id']);
			if(
				is_array($row)
				&& isset($row['buff_status'])
				&& ($pos1=strpos($row['buff_status'],'add_zc_jifen:'))!==false
			){
				$pos2=strpos($row['buff_status'],';',$pos1);
				if($pos2 === false) $pos2 = strlen($row['buff_status']);
				$pos1=strlen('add_zc_jifen:')+$pos1;
				$buff=substr($row['buff_status'],$pos1,$pos2-$pos1);
				$buffs=explode(',',$buff);
				if(count($buffs) >= 2 && $buffs[0]==date('Ymd'))
				{
					if(substr($buffs[1],-1)=='%')
					{
						$get_score*=1+intval(str_replace('%','',$buffs[1]))/100;
					}else{
						$get_score+=intval($buffs[1]);
					}
				}
			}
			$drops='战斗胜利！<br/>';
			$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");
			$_SESSION['first_in'] = 3;

			if(!challengeFortressSettle(
				$table_name,
				$uid,
				isset($user_fortress['cur_gpc_id']) ? $user_fortress['cur_gpc_id'] : 0,
				$sql_extra,
				$get_score,
				isset($_SESSION['fortress_card_id']) ? $_SESSION['fortress_card_id'] : 0,
				'<img src="../images/ys/win.png" width="62">'
			)) die('要塞战斗结算失败，请稍候重试！');
			$_SESSION['fortress_card_id']=0;
			unset($_SESSION['fortress_card_date']);
			$_SESSION['fortress_pass']=1;
		}else if ($nhp == 0){
			$_SESSION['guild_fight_time'] = 0;
			$table_name="`fortress_users_".date("Ymd")."`";
			$user_fortress=$_pm['mysql']->getOneRecord('select cur_gpc_id,bb_id,at_section_num,fv_result from '.$table_name.' where user_id='.$_SESSION['id']);
			if(!is_array($user_fortress)) $user_fortress = array('fv_result'=>0);
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
			if(!challengeFortressSettle(
				$table_name,
				$uid,
				isset($user_fortress['cur_gpc_id']) ? $user_fortress['cur_gpc_id'] : 0,
				$sql_extra,
				$get_score,
				isset($_SESSION['fortress_card_id']) ? $_SESSION['fortress_card_id'] : 0,
				'<img src="../images/ys/miss.png" width="62">'
			)) die('要塞战斗结算失败，请稍候重试！');
			$_SESSION['fortress_card_id']=0;
			unset($_SESSION['fortress_card_date']);
			$_SESSION['fortress_pass']=1;
			$drops='战斗失败！<br/>';
		}
	}else{
		if ($nhp == 0){
			if(!challengeRecordFightResult(isset($grs['uid']) ? $grs['uid'] : 0, $uid)) die('挑战结果保存失败！');
			$drops='挑战失败<br/>'; // bb die.
		}
		else if ($newhp == 0) // gaiwu die
		{
			if(!challengeRecordFightResult($uid, isset($grs['uid']) ? $grs['uid'] : 0)) die('挑战结果保存失败！');
			$drops = "<br/>恭喜您，挑战成功！<br/>您的战绩增加了 <font size=30% color=yellow>1</font> 点！";
			$targetUserHtml = challengeGateHtml(isset($gs['username']) ? $gs['username'] : '');
			$targetPetHtml = challengeGateHtml(isset($gs['name']) ? $gs['name'] : '');
			$word = " ,挑战 <font style=font-size:130%>{$targetUserHtml}</font> 的宝宝 <font style=font-size:130%>{$targetPetHtml}</font> 成功！获得了 <font style=font-size:130%>1</font> 点战绩！";
			$task = new task();
			//$task->saveGword($word);
		}
	}

	if ($newhp == 0) {
		unset($_SESSION['fight'.$_SESSION['id']]);
	}
	else if($nhp == 0 && isset($_SESSION['fight'.$_SESSION['id']]) && is_array($_SESSION['fight'.$_SESSION['id']]))
	{
		$_SESSION['fight'.$_SESSION['id']]['hp'] = $newhp;
		$_SESSION['fight'.$_SESSION['id']]['mp'] = $newmp;
		$_SESSION['fight'.$_SESSION['id']]['fatting'] = 0;
		$_SESSION['fight'.$_SESSION['id']]['fuzu'] = 0;
		$_SESSION['fight'.$_SESSION['id']]['gid'] = 0;
		$_SESSION['fight'.$_SESSION['id']]['ftime'] = time();
	}
	// Free resource.
	$_pm['mem']->memClose();


	if(!empty($att['hp1']) && empty($att['mp1']))
	{
		$str .= $displayHp . ',' . $displayMp. ',' . $bb.',<br />吸血'.$att['hp1'].'#'. $newhp . ',' . $gw1.'#' . $drops . '#' . $word;
	}

	else if(!empty($att['hp1']) && !empty($att['mp1']) && $att['mp1'] > 0)
	{
		$str .= $displayHp . ',' . $displayMp. ',' . $bb.',<br />吸血'.$att['hp1'].'&nbsp;==<br />吸魔'.$att['mp1'].'&nbsp;#'. $newhp . ',' . $gw1.'#' . $drops . '#' . $word;
	}
	else if(!empty($att['hp1']) && !empty($att['mp1']) && $att['mp1'] < 0)
	{
		$str .= $displayHp . ',' . $displayMp. ',' . $bb.',<br />吸血'.$att['hp1'].'&nbsp;==<br />失魔'.$att['mp1'].'&nbsp;#'. $newhp . ',' . $gw1.'#' . $drops . '#' . $word;
	}
	else if(empty($att['hp1']) && !empty($att['mp1']) && $att['mp1'] < 0)
	{
		$str .= $displayHp . ',' . $displayMp. ',' . $bb.'<br /> 失魔'.$att['mp1'].'&nbsp;#'. $newhp . ',' . $gw1.'#' . $drops . '#' . $word;
	}
	else if(empty($att['hp1']) && !empty($att['mp1']) && $att['mp1'] > 0)
	{
		$str .= $displayHp . ',' . $displayMp. ',' . $bb.',<br />吸魔'.$att['mp1'].'&nbsp;#'. $newhp . ',' . $gw1.'#' . $drops . '#' . $word;
	}
	else
	{
		 //     10022   ,    100    ,    1,普通攻击#0,396,普通攻击#战斗胜利！<br/>##1,0,0#
	     //      [  10022   ],[    100    ],[1,普通攻击]#[   0         ],[    456,普通攻击]#[战斗胜利！<br/>]##1,0,0#
		$str .= ''.$displayHp . ',' . $displayMp. ',' . $bb.' #'. $newhp . ',' . $gw1.'#' . $drops . '#' . $word;
	}
	$defenseNotes = array();
	if(!empty($att['hpdx'])) $defenseNotes[] = '抵消：'.$att['hpdx'];
	if($reflectedDamage > 0) $defenseNotes[] = '反弹伤害：'.$reflectedDamage;
	if(!empty($defenseNotes)) $str .= '<dx>'.implode('<br />', $defenseNotes);

	if( ($rs['s_uhp']<0||$rs['s_ump']<0) && ($rs['mp']< $rs['s_ump']) ){
		$str.='#'.$rs['s_vary'].',0,0#'.$mmonsterContinueFlag;
	}else{
		 $str.='#'.$rs['s_vary'].','.$rs['s_uhp'].','.$rs['s_ump'].'#'.$mmonsterContinueFlag;
	}
	if(!$aobj->fixedDamage && !empty($att['ack']))
	{
		$str .= '#<ack>伤害加深：'.$att['ack'];
	}
	$str .= "*".($aobj->fixedDamage ? 0 : $Crit);	//是否暴击
	$ack_type = 0;
	$str .= "*".$ack_type;	//五行攻击
	echo $str;
}
else
{	$drops='宝宝 ' . challengeGateHtml(is_array($grs) && isset($grs['name']) ? $grs['name'] : '').' 逃跑了！！！';
	echo '0,0,0#0,0#' . $drops . '#' . $word;
}
$_SESSION['first_in'] = 4;	//处理完
// =========================
?>
