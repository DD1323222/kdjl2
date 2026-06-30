<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.08
*@Update Date: 2008.05.29
*@Usage:Fightting Function.
*@Note: NO Add magic props.
  本模块主要功能：
  1)计算攻击力，包括BB和怪物。
  2)同时记录用户战斗的怪物数据，包括HP,MP,
  3)掉落物品最后根据机率，
*/
session_start();
define(MEM_BOSS_KEY,	$_SESSION['id'] . 'boss');
define(MEM_FIGHT_KEY,	$_SESSION['id'] . 'fight');
require_once('../config/config.game.php');

if( !isset($_SESSION[$_SESSION['id'].'mapid']) )
{
	die("非法进入");
}
$sql = " SELECT inmap FROM player WHERE id = '".$_SESSION['id']."'";
$relogin_fb_bug = $_pm['mysql'] -> getOneRecord($sql);

if( $relogin_fb_bug['inmap'] != $_SESSION[$_SESSION['id'].'mapid'] )
{
	die("Unauthorized access to copy");
}
$_SESSION['inmap'] = $relogin_fb_bug['inmap'];
if( $_SESSION['first_in'] == 2 || $_SESSION['first_in'] == 3 )
{
	$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");	
}
$_SESSION['first_in'] = 2;	//处理中

$wgcheck = $_GET['checkwg'];
if($wgcheck != 'checked'){
	$_SESSION['id'] = '';
	$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");
	$_SESSION['first_in'] = 3;
	die('<!--checkwg-->');
}

require_once('../config/config.fuben.php');
secStart($_pm['mem']);

//加速外挂
$time = time();
$sql = "SELECT time FROM fight_log WHERE uid = {$_SESSION['id']} and vary = 1";
$timearr = $_pm['mysql'] -> getOneRecord($sql);
if(is_array($timearr)){
	$ctime = $time - $timearr['time'];
	if($ctime < 2){
		$_SESSION['id'] = '';
		$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");
		$_SESSION['first_in'] = 3;
		die('<!--fight_log-->');
	}else{
		$_pm['mysql'] -> query("UPDATE fight_log SET time = ".time()." WHERE uid = {$_SESSION['id']} and vary = 1");
	}
}else{
	$_pm['mysql'] -> query("INSERT INTO fight_log (uid,time,vary) VALUES({$_SESSION['id']},".time().",1)");
}
//在这里结束

$id			= intval($_REQUEST['id']);		// 	技能ID
$need_cold_skill_id_arr = array('319'=>'299','320'=>'299','321'=>'179','322'=>'179','323'=>'119');
/*冷却技能，必须与fight.js,fbfight.js,fbfightGate.php,FightGate.php中技能传递保持一直，此为基于sission的服务器端验证，为排除延迟，这里减少1秒*/
if ( isset($need_cold_skill_id_arr[$id]) )
{
	$cold_time = $need_cold_skill_id_arr[$id];
	$key = $id."_".$_SESSION['id'];
	if( $_SESSION[$key] )
	{
		if( time()- $_SESSION[$key] > $cold_time  )
		{
			unset($_SESSION[$key]);
			$_SESSION[$key] = time();
		}
		else
		{
			echo "SKILLCOLD";
			$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");
			$_SESSION['first_in'] = 3;
			die();
		}
	}
	else
	{
		$_SESSION[$key] = time();
	}
}
$gid		= intval($_REQUEST['g']);	 	//  怪物ID
$db_bb		= array();	//	数据库中宝宝的原始属性。
$doubleexp = unserialize($_pm['mem']->get(MEM_TIME_KEY));
foreach($doubleexp as $v)
{
	if($v['titles'] == "exp")
	{
		$newdoubleexparr = $v;
	}
}
$nowtime = date("YmdHis");
$user		= $_pm['user']->getUserById($_SESSION['id']);
//$user	 = unserialize($_pm['mem']->get(MEM_USER_KEY));


$fight		= $_SESSION['fight'.$_SESSION['id']];
//if ( $fight['gid']==0 ){exit;}
$memKey= "last_update_user_fight_time_".$_SESSION['id'];
$timeMem = unserialize($_pm['mem']->get($memKey));
$minWait = 4.5;
if($timeMem){//防止加速（外挂取得了当前战斗的参数就可以连续请求来快速战斗）
	if($timeMem+$minWait>time()){
		sleep(intval($timeMem+$minWait-time()));
	}
}
$_pm['mem']->set(array("k"=>$memKey,"v"=>time()));

/** 非法数据监测。*/

//使用自动会城加血，无限副本外挂
if(intval($_SESSION['GoToCity'])>0){
	stopUser2(50);//,true
	setcookie("PHPSESSID",'YOUAREBAD');
	unset($_SESSION['username']);
	unset($_SESSION['licenseid']);
	$drops='怪物 逃跑了！！！';
	$word='';
	$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");
	$_SESSION['first_in'] = 3;
	exit("0,0,0,普通攻击,#0,0,普通攻击#获得经验：0<br/>获得金币：0+0 个
					  <br/>捕获宠物：0<br/>获得物品：无！<br/>特殊奖励：无<br/>##1,0,0#NOT");
	//exit;
}

if ($fight['gid'] != $gid || $fight['gid']==0){
	$drops='怪物 逃跑了！！！';
	$word='';
	$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");
	$_SESSION['first_in'] = 3;
	exit("0,0,0,普通攻击,#0,0,普通攻击#获得经验：0<br/>获得金币：0+0 个
					  <br/>捕获宠物：0<br/>获得物品：无！<br/>特殊奖励：无<br/>##1,0,0#NOT");
}

if (
	(
		$user['inmap'] >  14 && 
		$user['inmap'] != 50 && 
		$user['inmap'] != 124 && 
		$user['inmap'] != 127&& 
		$user['inmap'] != 143&& 
		$user['inmap'] != 144
	) 
		|| $user['inmap'] < 11
	){
		echo '<!--stopUser(2-'.$user['inmap'].')-->';
		stopUser(2);		// 地图检查		
	}

/*if(empty($_SESSION['gwcdie'.$_SESSION['id']]) && $_SESSION['gwcdie'.$_SESSION['id']] != $gid)
{
	$_SESSION['id'] = 0;
}*/

/*###非法数据监测完成###*/	

// auto fit check
if($user['autofitflag']==1 && $user['maxautofitsum']<=0 && $user['sysautosum']<=0)
{
	$user['maxautofitsum']=0;
	$user['sysautosum']=0;
	echo 'autoend';	
	$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");
	$_SESSION['first_in'] = 3;
	exit();
}

// GET INFO FROM ARRAY.
//$_bb		 = $_pm['user']->getUserPetById($_SESSION['id']);
//$_bag		 = $_pm['user']->getUserBagById($_SESSION['id']);
//$_sk		 = $_pm['user']->getUserPetSkillById($_SESSION['id']);
//$bb	 = unserialize($_pm['mem']->get(MEM_USERBB_KEY));
//$_bag = unserialize($_pm['mem']->get(MEM_USERBAG_KEY));
//$sk	 = unserialize($_pm['mem']->get(MEM_USERSK_KEY));

//$_sksys	 = unserialize($_pm['mem']->get(MEM_SKILLSYS_KEY));
//$_gpc	 = unserialize($_pm['mem']->get(MEM_GPC_KEY));
//$memgpcid	 = unserialize($_pm['mem']->get('db_gpcid'));

//$memskillsysid	 = unserialize($_pm['mem']->get('db_skillsysid'));


/* Fix read database fail!*/
if(intval($_SESSION['id'])<1||intval($user['fightbb'])<1) 
{
	$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");
	$_SESSION['first_in'] = 3;
	exit('$user[\'fightbb\']='.$user['fightbb'].',$_SESSION[\'id\']='.$_SESSION['id']);
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
			exit("Get BB Failed!");
		}
		sleep(1);
	}
}

if($id == 112){
	$_SESSION['id'] == '';
	$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");
	$_SESSION['first_in'] = 3;
	die('非法操作，服务器强制断线！');
}

$_sk		 = $_pm['user']->getUserPetSkillByIdS($_SESSION['id'],$_bb['id'],$id);
/**补丁代码*当检查到玩家的技能不正确时.将主动技能设为原始技能  2009.06.24 kevin**/
if(!is_array($_sk))
$_sk = $_pm['user']->getUserPetSkillByIdS($_SESSION['id'],$_bb['id'],"1");
/*结束代码*/


if(intval($_SESSION['id'])<1||intval($user['fightbb'])<1)
{
	$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");
	$_SESSION['first_in'] = 3;
	exit('$_bb[\'id\']='.$_bb['id'].',$_SESSION[\'id\']='.$_SESSION['id']);
}
if (!is_array($_sk))
{   
	$loop=true;
	$ct=0;
	while($loop)
	{
		$ct++;
		$_sk		 = $_pm['user']->getUserPetSkillByIdS($_SESSION['id'],$_bb['id'],$id);
		if (is_array($_sk)) break;
		if($ct>10)
		{
			$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");
			$_SESSION['first_in'] = 3;	
			exit("Get SKILL Failed!");
		}
		sleep(1);
	}
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
								's_imgeft'   => $_sk['img']//增加效果。
							   )
					 );				
}
else $rs = '';

//get gwinfo.
/*$grs = $_pm['mem']->dataGet(array('k' => MEM_GPC_KEY, 
						 'v' => "if(\$rs['id'] == '{$gid}') \$ret=\$rs;"
					));*/

//$grs = $memgpcid[$gid];
$grs =  getBaseGpcInfoById($gid);
if (!is_array($grs)) $skid=1;
else
{
	$sk = str_replace(":", '.' ,$grs['skill']);
	if (strstr($sk, ",") === false) 	$sk .= "," . $sk;

	$evlmax = "\$max=max(".$sk.");";
	$evlmin  = "\$min=min(".$sk.");";
	eval($evlmax);
	eval($evlmin);
	$ar = split(",", $grs['skill']);
	foreach($ar as $k => $v)
	{
		$arr = split(":", $v);
		$alljn[$k] = $arr[0];
	}	
	while(true)
	{
		$skid = rand($min, $max);
		if ( in_array($skid, $alljn) )
		{
			break;
		}
	}
}

// Gpc data
//$v = $memgpcid[$gid];
$v = getBaseGpcInfoById($gid);//改为单条取记录
//$y = $memskillsysid[$skid];
$y = getBaseSkillSysInfoById($skid);//改为单条取记录
if (is_array($v) && is_array($y))
{
	$gs = array_merge($v, array('s_name'  => $y['name'],
								's_wx'	  => $y['wx'],
								's_value' => $y['ackvalue'],
								's_plus'  => $y['plus'],
								's_uhp'   => $y['uhp'],
								's_ump'   => $y['ump'],
								's_id'	  => $y['id']
							   )
						);
}
else $gs = '';


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
	//战斗药品附加效果
	$medicine_buff = $_pm['mysql'] -> getOneRecord(" SELECT F_Medicine_Buff FROM player_ext WHERE uid = '".$_SESSION['id']."'");
	if( isset($medicine_buff['F_Medicine_Buff']) && !empty($medicine_buff['F_Medicine_Buff']) )	//如果吃了战斗中buff药
	{
		$med_buff_arr_all = explode(',',$medicine_buff['F_Medicine_Buff']);
		foreach( $med_buff_arr_all as $info )
		{
			$med_buff_arr = explode(':',$info);	//addac:10
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
	//$mem_welcome = unserialize($_pm['mem']->get('db_welcome'));	
	$mem_welcome['crit_rate'] = getBaseWelcomeInfoByCode('getBaseWelcomeInfoByCode');
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
		$Crit_rate = $Crit_rate_db['contents'];	//读数据库暴击率
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
	$Crit_number = rand(0,100);	//最大100暴击率哦
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
	
	$bbskillAddHP=0;
	$bbskillAddMP=0;
	//宠物对怪物攻击力
	if($rs['s_uhp']<0||$rs['s_ump']<0){	//使用魔法或者生命为负，则是给自己加	
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
	
	
	
	$aobj1 = new Ack1($gs, $rs);
	$aobj1 -> getSkillAck();
	//怪物对宠物攻击力
	$gwac = $aobj1 -> skillack;
	$gw = $gwac . ',' . $gs['s_name'];
	
	
	//计算吸血和吸魔

	$att = getzbAttrib($rs['id'],$gwac,$bback);
	//dxsh 转化为被动技能
	$jnnewarr = $_pm['mysql'] -> getOneRecord("SELECT img FROM skill WHERE sid = 112 AND bid = {$_SESSION['mbid']}");
	if(count($jnnewarr) >= 1){
		$add_s_imgeft_arr1 = explode(':',$jnnewarr['img']);
		$add_s_imgeft_arr = str_replace('%','',$add_s_imgeft_arr1[1]);
		$att['hpdx'] += round($add_s_imgeft_arr * $gwac *0.01);
	}
	
	//增加技能效果:hitshp:命中吸取伤害的百分比转化为生命:
	if(!empty($rs['s_imgeft']))
	{
		$jnar = explode(":",$rs['s_imgeft']);
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
	$gwac1 = $gwac - $att['hpdx'];
	$gw = $gwac1 . ',' . $gs['s_name'];
	$gw1 = $gwac . ',' . $gs['s_name'];
	//$aobj -> skillack += $att['hp1'];
	
	$sql = "SELECT * FROM userbb 
			WHERE id = {$rs['id']}";
	$row = $_pm['mysql'] -> getOneRecord($sql);
	
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
	if($row['addmp']+$rs['mp']+$bbskillAddmp>$row['srcmp']+$att['mp'])//完全加满
	{
		$row['addmp'] = $att['mp'];
		$rs['mp'] = $row['srcmp'];
	}else if($rs['mp']+$bbskillAddmp>$row['srcmp']){//加满mp，加不满addmp
		$row['addmp'] = min($row['addmp'] +$rs['mp']+$bbskillAddmp-$row['srcmp'],$att['mp']);
		$rs['mp'] = $row['srcmp'];
	}else{
		$rs['mp'] += $bbskillAddmp;		
	}
	
	
	$srchp1 = $row['srchp'] + $row['addhp'];
	$srcmp1 = $row['srcmp'] + $row['addmp'];
		
	$ftgw = $_SESSION['fight'.$_SESSION['id']];
	//======================== 装备效果结束 ===============================
	if(!$aobj->fixedDamage)
	{
		$aobj->skillack += $att['ack'];
	}

	if (!is_array($ftgw))	// 插入用户的战斗记录及参战的怪物数据。
	{   
		$newhp = $gs['hp']-$aobj->skillack;
		$newmp = $gs['mp'];
		
		//加血时，怪物不减血
		
/*
if($rs['s_uhp']<0||$rs['s_ump']<0){	
			$newhp = $gs['hp'];					}*/
		$_SESSION['fight'.$_SESSION['id']]=array('uid' => $_SESSION['id'],
												 'bid' => $rs['id'],
												 'gid' => $gid,
												 'hp'  => $newhp,
												 'mp'  => $newmp,
												 'fuzu'=> 0,
												 'ftime'=> time(),
												 'fatting'=> 1);
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
		$r['ftime']		=time();
		$r['fuzu']		=0;
		
		$_SESSION['fight'.$_SESSION['id']] = $r;
	}
	else if($ftgw['fuzu'] == 1) //解除用户攻击一回锁定。
	{
		$r = $_SESSION['fight'.$_SESSION['id']];
		$r['fuzu']= 0;
		
		$_SESSION['fight'.$_SESSION['id']] = $r;
		$aobj->skillack = 0;
		$newhp = $ftgw['hp'];
		$newmp = $ftgw['mp'];
	}
	// 更新用户BB信息。
	// 如果BB秒杀怪物，则不减自己的生命.
	######################宠物的剩余血量和魔法###########################
	if ($aobj->skillack < $ftgw['hp']) 
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
			//$nhp = $sumhp + $rs['hp'] - $gwac1;
			$nhp = $sumhp + $rs['hp'] - $gwac1 ;
			
			if($row['srchp'] < $nhp + $bbskillAddHP)
			{
				$nhp = $sumhp + $row['srchp'] - $gwac1;
			}else{
				$nhp += $bbskillAddHP;
			}	
			$addhp = 0;
		}
		//$nhp = $rs['hp']-$aobj1->skillack;
		
	}
	else $nhp = $rs['hp'];
	
	
	$summp = $att['mp1'] + $row['addmp'];
	if($summp > $rs['s_ump'])
	{
		$nmp = $rs['mp'];
		$addmp = $summp - $rs['s_ump'];
		//判断宠物的mp是否超过最大值
		$summp1 = $addmp + $nmp;
		if($summp1 > $srcmp1)
		{
			$addmp = $srcmp1 - $nmp;
		}
	}
	else
	{
		$nmp = $summp + $rs['mp'] - $rs['s_ump'];
		if($nmp+$bbskillAddMP>$rs['srcmp'])
		{
			$nmp = $rs['srcmp'];
		}else{
			$nmp += $bbskillAddMP;
		}
		$addmp = 0;
	}
	//$nmp = $rs['mp']-$rs['s_ump']; 
	
	if ($nhp<0) $nhp=0;
	if ($nmp<0) $nmp=0;

	$_pm['mysql']->query("UPDATE userbb
				   SET hp={$nhp}, 
				       mp={$nmp},
					   addmp={$addmp},
					   addhp={$addhp}
				 WHERE id={$rs['id']} and uid={$_SESSION['id']}
			  ");
	if ($nhp <= 0){
		$mmonsterContinueFlag ="DIE";
		$drops='宝宝 ' . $rs['name'].' 受到了严重伤害，已经不能战斗！！！'; // bb die.
		$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");
		$_SESSION['first_in'] = 3;
		unset($_SESSION['catch_gw_info']);//捕捉的怪物id
	}
	else if ($newhp <= 0) // gaiwu die
    {
		$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");
		$_SESSION['first_in'] = 3;
		unset($_SESSION['catch_gw_info']);//捕捉的怪物id
		if($gid == 292)
		{
			$task = new task();
			$task->saveGword("消灭了玲珑城BOSS 雪羽凤凰 获得了 凤凰珠及大量宝物！");
		}
		else if($gid == 455)
		{
			$task = new task();
			$task->saveGword("消灭了boss§受诅咒的寒江雪§，获得了大量宝物。");
		}else if($gid == 513)
		{
			$task = new task();
			$task->saveGword("通过了阿尔提副本获得了大量元宝道具。");
		}else if($gid == 790){
			$task = new task();
			$task->saveGword("通过了菲拉苛地域获得了大量宝物。");
		}
		//updateBoss($gid);
		foreach($fbinfo as $fb)
		{
			if($fb['id'] == $user['inmap'])
			{//得到当前地图的相关信息
				$gwlist1 = $fb['gwid'];
				$srctime = $fb['time'];
			}
		}
		
		$gwlist = explode(",",$gwlist1);
		//判断玩家是否是通过正常操作得到怪物的ID
		$sql = "SELECT * FROM fuben WHERE uid = {$_SESSION['id']} and inmap = {$user['inmap']}";
		$exitgw = $_pm['mysql'] -> getOneRecord($sql);
		if(!is_array($exitgw) && $gid != $gwlist[0])
		{
			$drops = "非法操作!";
		}
		else
		{
			$ctime = time() - $exitgw['lttime'];
			if($ctime < $exitgw['srctime'])
			{
				if($exitgw['gwid'] != $gid)
				{
					$drops = "非法操作!";
				}
			}
		}
		$max = count($gwlist) - 1;
		if($gid > $gwlist[$max])
		{
			$drops = "非法操作!";
		}
		foreach($gwlist as $kgw => $vgw)
		{
			if($vgw == $gid)
			{
				$num = $kgw + 1;
				break;
			}
			else
			{
				$num = 0;
			}
		}
		//$sql = "SELECT * FROM fuben WHERE uid = {$_SESSION['id']} and inmap = {$user['inmap']}";//更新数据库存，首先要判断是否存在，存在则更新，不存在则增加。
		//$exits = $_pm['mysql'] -> getOneRecord($sql);
		if($num >= count($gwlist))//判断是否是本副本的最后一个怪物             
		{
			$time = time();
			$gid = $gwlist[$num];
			$sql = "UPDATE fuben
					SET lttime = {$time},srctime = {$srctime},gwid = ''
					WHERE uid = {$_SESSION['id']} and inmap = {$user['inmap']}";
			$_pm['mysql'] -> query($sql);
		}
		else
		{
			$time = time();
			$gid = $gwlist[$num];
			if(is_array($exitgw))
			{
				$sql = "UPDATE fuben
						SET gwid = {$gid},lttime = $time
						WHERE uid = {$_SESSION['id']} and inmap = {$user['inmap']}";
			}
			else
			{
				$sql = "INSERT INTO fuben (uid,gwid,inmap,srctime,lttime) VALUES({$_SESSION['id']},$gid,{$user['inmap']},$srctime,$time)";
			}
			$_pm['mysql'] -> query($sql);
		}
		
		//echo '<!--'.print_r($_GET,1).',$gid='.$gid.',$user[\'inmap\']='.$user['inmap'].',$sql='.$sql.'--->';
		
		################################################################################
		//掉落物品获取。格式：道具ID：机率范围。
		$prpid = getProps($gs['droplist']);

		$okidlist = '';
		if ($prpid === false || $prpid == 0 || $prpid == '') $drop = '无！';
		else
		{
		    $rarr = split(',', $prpid);
			foreach ($rarr as $k => $v)
			{
				//$mempropsid = unserialize($_pm['mem']->get('db_propsid'));
				//$prs = $mempropsid[$v];
				$prs = getBasePropsInfoById($v);
				/*$prs = $_pm['mem']->dataGet(array('k' => MEM_PROPS_KEY, 
										 'v' => "if(\$rs['id'] == '{$v}') \$ret=\$rs;"
							  ));*/

				if( is_array($prs) )
				{
					$drop .= $prs['name'].',';
					$okidlist .= $v.',';
				} 
			}	// end foreach.
			$drop = substr($drop, 0, -1);
			$okidlist = substr($okidlist, 0, -1);
			saveGetPropsa($okidlist);
		}
		
		/** 特殊道具检测 */
		$uProps = usedProps($user);
//$gs['exps'] = $gs['exps']*100;
		/*if ($uProps !== false)
		{
			if($_SESSION['exptype'.$_SESSION['id']] != 1)
			{
				$gs['exps'] = intval($gs['exps']*$uProps['double']);
			}
			else
			{
				if(!empty($uProps['doubleexp']))
				{
					$gs['exps'] = intval($gs['exps']*$uProps['double']*$uProps['doubleexp']);
				}
				else
				{
					$gs['exps'] = intval($gs['exps']*$uProps['double']);
				}
			}
		}*/
		
		if ($uProps !== false)
		{
			$doubleexp = unserialize($_pm['mem']->get(MEM_TIME_KEY));
			foreach($doubleexp as $v)
			{
				if($v['titles'] == "exp")
				{
					$newdoubleexparr[$v['starttime'].'-'.$v['endtime']] = $v['days'];
				}else if($v['titles'] == "exp1"){
					$newdoubleexparr1[$v['starttime'].'-'.$v['endtime']] = $v['days'];
				}
			}
			$nowtime = date("YmdHis");
			if(is_array($newdoubleexparr))
			{
				$k = "";
				$v = "";
				foreach($newdoubleexparr as $k => $v)
				{
					if(!empty($k))
					{
						$arr = "";
						$arr = explode("-",$k);
						if($nowtime >= $arr[0] && $nowtime <= $arr[1])
						{
							$ddd = $v;
						}
					}
				}
			}
			if(is_array($newdoubleexparr1))
			{
				$week = date('w');
				$time = date('Hi');
				$k = "";
				$v = "";
				foreach($newdoubleexparr1 as $k => $v)
				{
					if(!empty($k))
					{
						$arr = "";
						$arr = explode("-",$k);
						$narr = explode('|',$arr[0]);
						if($narr[0] == $week && $time >= $narr[1] && $time <= $arr[1])
						{
							$ddd = $v;
						}
					}
				}
			}
			
			if($ddd > 0)
			{
				if($_SESSION['exptype'.$_SESSION['id']] != 1)
				{
					$gs['exps'] = intval($gs['exps']*$uProps['double']) * $ddd;
				}
				else
				{
					if(!empty($uProps['doubleexp']))
					{
						$gs['exps'] = intval($gs['exps']*$uProps['double']*$uProps['doubleexp']) * $ddd;
					}
					else
					{
						$gs['exps'] = intval($gs['exps']*$uProps['double']) * $ddd;
					}
				}
			}
			else
			{
				if($_SESSION['exptype'.$_SESSION['id']] != 1)
				{
					$gs['exps'] = intval($gs['exps']*$uProps['double']);
				}
				else
				{
					if(!empty($uProps['doubleexp']))
					{
						$gs['exps'] = intval($gs['exps']*$uProps['double']*$uProps['doubleexp']);
					}
					else
					{
						$gs['exps'] = intval($gs['exps']*$uProps['double']);
					}
				}
			}
		}
		/*特殊道具部分完成*/
		
		$sj = saveGetOther($rs, $gs['exps']); // Save exps and money.
		if ($sj === true) 
		{
			$sj="<font color=yellow size=4 style='font-family:华文新魏;font-weight:bold;'>{$rs['name']} 的等级提升!</font>";
			//$_pm['user']->updateMemUsersk($_SESSION['id']);
			$_pm['mem']->set(array('k'=>MEM_SYSWORD_KEY, 'v'=>'恭喜玩家 '.$_SESSION['nickname'].'的宝宝 '.$rs['name'].' 通过艰苦的修行，进入到更高等级！'));
		}
		else $sj = "";
		
		$user['money'] = $gs['money'] + $att['money'] + $user['money'];	
		if ($user['money'] >= 1000000000) $user['money']=1000000000;
		
		catchTask($user, $gid);
		
		// 更新用户数据。dblexpflag,maxdblexptime,sysautosum,maxautofitsum
		$_pm['mysql']->query("UPDATE player
					   SET money={$user['money']},
						   tasklog='{$user['tasklog']}',
						   dblexpflag={$user['dblexpflag']},
						   maxdblexptime={$user['maxdblexptime']},
						   sysautosum={$user['sysautosum']},
						   maxautofitsum={$user['maxautofitsum']}
					 WHERE id={$_SESSION['id']}
				  ");

		unset($prs, $rarr);
		$sql = "SELECT * FROM fuben WHERE uid = {$_SESSION['id']} and inmap = {$user['inmap']}";
		$exit = $_pm['mysql'] -> getOneRecord($sql);
		if(empty($att['money']))
		{
			$att['money'] = 0;
		}
		if(is_array($exit))
		{
			if(empty($exit['gwid']) || $_SESSION['test_end']==$exit['gwid'])
			{
				$sql = "UPDATE fuben
						SET gwid = 0
						WHERE uid = {$_SESSION['id']} and inmap = {$user['inmap']}";
				$_pm['mysql'] -> query($sql);

				$drops = "获得经验：" . ($gs['exps']) . "<br/>获得金币：" . ($gs['money']."+".$att['money']) . " 个
				  <br/>捕获宠物：0<br/>获得物品：{$drop}<br/>特殊奖励：无<br/>{$sj}<br />";
				$drops .= "#end";
			}
			else
			{
				$drops = "获得经验：" . ($gs['exps']) . "<br/>获得金币：" . ($gs['money']."+".$att['money']) . " 个
				  <br/>捕获宠物：0<br/>获得物品：{$drop}<br/>特殊奖励：无<br/>{$sj}";
				 $drops .= "#0".'-'.$exit['gwid'];
			}
			$_SESSION['test_end'] = $exit['gwid'] ? $exit['gwid'] : 0;
		}
		else
		{
			$drops = "获得经验：" . ($gs['exps']) . "<br/>获得金币：" . ($gs['money']."+".$att['money']) . " 个
				  <br/>捕获宠物：0<br/>获得物品：{$drop}<br/>特殊奖励：无<br/>{$sj}";
			$drops .= "#0".'-'.$user['inmap'];
		}

		//$_pm['user']->updateMemUser($_SESSION['id']);
		//$_pm['user']->updateMemUserbb($_SESSION['id']);
		//$_pm['user']->updateMemUserbag($_SESSION['id']);
	}
	else $drops='';

	if ($newhp == 0) {
		$r =$_SESSION['fight' . $_SESSION['id']];
		$r['hp']		= $newhp;
		$r['mp']		= $newmp;
		$r['fatting']	= 0;
		$r['fuzu']		= 0;
		$r['gid']		= 0;
		$_SESSION['fight'.$_SESSION['id']]= $r;
	}
	// Free resource.
	// set fight info to memory.
	//$_pm['mem']->set(array('k'=>MEM_FIGHT_KEY, 'v'=>$fight));
	//$_SESSION['fight'.$_SESSION['id']]=$fight;
	$_pm['mem']->memClose();
	
	// Add gaiwu word.
	$word = sayWord($grs, $newhp);

	
	//echo $nhp . ',' . $nmp. ',' . $bb.'#'. $newhp . ',' . $gw.'#' . $drops . '#' . $word;	
	//输出加入装备后对hp,mp以影响 
		$sql = "SELECT addmp,addhp FROM userbb WHERE uid = {$_SESSION['id']} and id = {$rs['id']}";
		$add = $_pm['mysql'] -> getOneRecord($sql);
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
		//技能类型,生命、魔法消耗
		//$bb .= ','.$rs['s_vary'].','.$rs['s_uhp'].','.$rs['s_ump'];		
		if(!empty($att['hp1']) && empty($att['mp1']))
		{
			$str = $nhp . ',' . $nmp. ',' . $bb.',<br />吸血'.$att['hp1'].'#'. $newhp . ',' . $gw1.'#' . $drops . '#' . $word;	
		}
		
		else if(!empty($att['hp1']) && !empty($att['mp1']) && $att['mp1'] > 0)
		{
			$str = $nhp . ',' . $nmp. ',' . $bb.',<br />吸血'.$att['hp1'].'&nbsp;==<br />吸魔'.$att['mp1'].'&nbsp;#'. $newhp . ',' . $gw1.'#' . $drops . '#' . $word;	
		}
		else if(!empty($att['hp1']) && !empty($att['mp1']) && $att['mp1'] < 0)
		{
			$str = $nhp . ',' . $nmp. ',' . $bb.',<br />吸血'.$att['hp1'].'&nbsp;==<br /失魔'.$att['mp1'].'&nbsp;#'. $newhp . ',' . $gw1.'#' . $drops . '#' . $word;	
		}
		else if(empty($att['hp1']) && !empty($att['mp1']) && $att['mp1'] < 0)
		{
			$str = $nhp . ',' . $nmp. ',' . $bb.'<br /> 失魔'.$att['mp1'].'&nbsp;#'. $newhp . ',' . $gw1.'#' . $drops . '#' . $word;	
		}
		else if(empty($att['hp1']) && !empty($att['mp1']) && $att['mp1'] > 0)
		{
			$str = $nhp . ',' . $nmp. ',' . $bb.',<br />吸魔'.$att['mp1'].'&nbsp;#'. $newhp . ',' . $gw1.'#' . $drops . '#' . $word;	
		}
		else
		{
			$str = $nhp . ',' . $nmp. ',' . $bb.'#'.$newhp . ',' . $gw1.'#' . $drops . '#' . $word;	
		}
		if (!empty($att['hpdx']))
		{
			$str .= "<dx>抵消：".$att['hpdx'];
		}
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
{	$drops='怪物 ' . $grs['name'].' 逃跑了！！！';
	echo '0,0,0#0,0#' . $drops . '#' . $word;	
}
$_SESSION['gwcdie'.$_SESSION['id']] = "";
//==============================================================
$_SESSION['first_in'] = 4;	//处理完
?>
