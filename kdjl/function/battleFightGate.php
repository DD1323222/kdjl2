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
/*if (!defined('BATTLE_TIME_START'))
	define(BATTLE_TIME_START, "20:00");
if (!defined('BATTLE_TIME_END'))
	define(BATTLE_TIME_END, "22:00");
if (!defined('BATTLE_TIME_WEEK'))
	define(BATTLE_TIME_WEEK, 5);*/

secStart($_pm['mem']);
//加速外挂
$time = time();
$sql = "SELECT time FROM fight_log WHERE uid = {$_SESSION['id']} and vary = 1";
$timearr = $_pm['mysql'] -> getOneRecord($sql);
if(is_array($timearr)){
	$ctime = $time - $timearr['time'];
	if($ctime < 1.5){
		$_SESSION['id'] = '';
		die('操作过快！');
	}else{
		$_pm['mysql'] -> query("UPDATE fight_log SET time = ".time()." WHERE uid = {$_SESSION['id']} and vary = 1");
	}
}else{
	$_pm['mysql'] -> query("INSERT INTO fight_log (uid,time,vary) VALUES({$_SESSION['id']},".time().",1)");
}
//在这里结束

$id			= intval($_REQUEST['id']);		// 	技能ID
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
$gid		= intval($_REQUEST['g']);	 	//  被挑战玩家的宠物ID
$db_bb		= array();	//	数据库中宝宝的原始属性。


$wgcheck = $_GET['checkwg'];
if($wgcheck != 'checked'){
	$_SESSION['id'] = '';
}

// 战场开放时间开关。
$week=date("N", time());
$hourM=date("H:i", time());

$battletimearr = unserialize($_pm['mem']->get(MEM_TIME_KEY));

foreach($battletimearr as $bv)
{
	if($bv['titles'] != "battle")
	{
		continue;
	}
	if($week == $bv['days'] && $hourM >= $bv['starttime'] && $hourM <= $bv['endtime'])
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
$fight		= $_SESSION['fight'.$_SESSION['id']];
$cUser = $_pm['mysql']->getOneRecord("SELECT bid 
										FROM battlefield_user 
									   WHERE uid={$_SESSION['id']}");
if ( $fight['gid']==0 ){exit();}

/** 非法数据监测。*/
if ($fight['gid'] != $gid) stopUser();
/*###非法数据监测完成###*/	


// GET INFO FROM ARRAY.
//$_bb	 = $_pm['user']->getUserPetById($_SESSION['id']);

//$_sk	 = $_pm['user']->getUserPetSkillById($_SESSION['id']);

//$_sksys	 = unserialize($_pm['mem']->get(MEM_SKILLSYS_KEY));
//$_gpc	 = unserialize($_pm['mem']->get(MEM_GPC_KEY));


/* Fix read database fail!*/
if(intval($_SESSION['id'])<1||intval($user['mbid'])<1) exit('$user[\'mbid\']='.$user['mbid'].',$_SESSION[\'id\']='.$_SESSION['id']);
$_bb = $_pm['user']->getUserPetByIdS($_SESSION['id'],$user['mbid']);
if (!is_array($_bb))
{   
	$loop=true;
	$ct=0;
	while($loop)
	{
		$ct++;
		$_bb		 = $_pm['user']->getUserPetByIdS($_SESSION['id'],$user['mbid']);		
		if (is_array($_bb)) break;
		if($ct>10) exit("Get BB Failed!");
		sleep(1);
	}
}
$_sk		 = $_pm['user']->getUserPetSkillByIdS($_SESSION['id'],$_bb['id'],$id);

if(intval($_SESSION['id'])<1||intval($user['mbid'])<1) exit('$_bb[\'id\']='.$_bb['id'].',$_SESSION[\'id\']='.$_SESSION['id']);
if (!is_array($_sk))
{   
	$loop=true;
	$ct=0;
	while($loop)
	{
		$ct++;
		$_sk		 = $_pm['user']->getUserPetSkillByIdS($_SESSION['id'],$_bb['id'],$id);
		if (is_array($_sk)) break;
		if($ct>10) exit("Get SKILL Failed!");
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
								's_ump'   => $_sk['ump']
							   )
					 );				
}
else $rs = '';

//get gwinfo.
$grs = $_pm['mysql']->getOneRecord("SELECT *
									FROM userbb
								   WHERE id={$gid}
								");

if (!is_array($grs)) $skid=1;
else
{
	$sk = str_replace(":", '.' ,$grs['skillist']);
	if (strstr($sk, ",") === false) 	$sk .= "," . $sk;

	$evlmax = "\$max=max(".$sk.");";
	$evlmin  = "\$min=min(".$sk.");";
	eval($evlmax);
	eval($evlmin);
	$ar = split(",", $grs['skillist']);
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

//获得玩家宝宝的所有信息。
$gs = $_pm['mysql']->getOneRecord("SELECT b.*,
											 s.name as s_name, 
											 s.wx as s_wx,
											 s.value as s_value,
											 s.plus as s_plus,
											 s.uhp as s_uhp,
											 s.ump as s_ump,
											 s.id as s_id
										FROM userbb as b,skill as s
									   WHERE b.id=s.bid and b.id={$gid}
									   LIMIT 0,1
									");

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
	$mem_welcome = unserialize($_pm['mem']->get('db_welcome'));	
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
	$bback = $aobj -> skillack;
	
	$bb = $aobj->skillack . ',' . $rs['s_name'];
	$aobj1 = new Ack($gs, $rs);
	$aobj1 -> getSkillAck();
	//怪物对宠物攻击力
	$gwac = $aobj1 -> skillack;
	$gw = $gwac . ',' . $gs['s_name'];
	
	//计算吸血和吸魔
	$att = getzbAttrib($rs['id'],$gwac,$bback);
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
			$nhp = $sumhp + $rs['hp'] - $gwac1;
			$nhp = $sumhp + $rs['hp'] - $gwac1;
			$addhp = 0;
		}
		//$nhp = $rs['hp']-$aobj1->skillack;
		
	}
	//计算装备所加的mp
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
	
	if ($nhp == 0) 
	{
		$drops='很遗憾，战斗失败！'; // bb die.
		// in here add fail option of records.
		$cUser = $_pm['mysql']->getOneRecord("SELECT pos,bid,failackvalue
												FROM battlefield_user
											   WHERE uid={$_SESSION['id']}
											");
		$sql = "SELECT hp FROM battlefield WHERE id = {$cUser['pos']}";
		$checkhp1 = $_pm['mysql'] -> getOneRecord($sql);
		if($checkhp1['hp'] >= $cUser['ackvalue'])
		{
			$_pm['mysql']->query("UPDATE battlefield
								 SET hp=hp-{$cUser['ackvalue']}
							   WHERE id={$cUser['pos']} AND hp >= {$cUser['ackvalue']}
							");
		}
		else
		{
			$_pm['mysql']->query("UPDATE battlefield
								 SET hp=0
							   WHERE id={$cUser['pos']}
							");
		}
	}
	else if ($newhp == 0) // gaiwu die
    {
		##################################################
		// 更新用户数据。记录用户军功并减少对方阵营生命
		$cUser = $_pm['mysql']->getOneRecord("SELECT pos,bid,addjgvalue,ackvalue,doublejg
												FROM battlefield_user
											   WHERE uid={$_SESSION['id']}
											");
		//军功值：取整{战场胜利军功基数*[1－(X－20）/100)]}
		$jgvalue = intval( $cUser['addjgvalue']*(1-($rs['czl']-$gs['czl']-20)/1000) );
		if ($cUser['doublejg']==1) $jgvalue = $jgvalue*3;
		else $jgvalue=$jgvalue*2;

		if($jgvalue < 0){
			$jgvalue = 5;
		}
		$_pm['mysql']->query("UPDATE battlefield_user
		                         SET curjgvalue=curjgvalue+{$jgvalue}
							   WHERE uid={$_SESSION['id']}");

		// 减少对方阵营HP。
		$sql = "SELECT hp FROM battlefield WHERE id != {$cUser['pos']}";
		$checkhp = $_pm['mysql'] -> getOneRecord($sql);
		if($checkhp['hp'] >= $cUser['ackvalue'])
		{
			$_pm['mysql']->query("UPDATE battlefield
								 SET hp=hp-{$cUser['ackvalue']}
							   WHERE id!={$cUser['pos']} AND hp >= {$cUser['ackvalue']}"
							   );
		}
		else
		{
			$_pm['mysql']->query("UPDATE battlefield
								 SET hp=0
							   WHERE id!={$cUser['pos']}"
							   );
		}

		$drops = "<br/><font size=+1>恭喜您，获得了本次战斗的胜利！</font><br/>您获得了 <font size=30% color=yellow>{$jgvalue}</font> 点军功！";
		/*
		$word = " , <font style=font-size:130%>{$gs['username']}</font> 的宝宝 <font style=font-size:130%>{$gs['name']}</font> 成功！获得了 <font style=font-size:130%>1</font> 点战绩！";
		
		$task = new task();
		$task->saveGword($word);
		*/
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
	$_pm['mem']->memClose();
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
		$echo_str = $nhp . ',' . $nmp. ',' . $bb.',<br />吸血'.$att['hp1'].'&nbsp;==<br /失魔'.$att['mp1'].'&nbsp;#'. $newhp . ',' . $gw.'#' . $drops . '#' . $word;	
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
