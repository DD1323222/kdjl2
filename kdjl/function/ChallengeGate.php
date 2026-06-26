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
define(MEM_BOSS_KEY,	$_SESSION['id'] . 'boss');
define(MEM_FIGHT_KEY,	$_SESSION['id'] . 'fight');

secStart($_pm['mem']);
$id			= intval($_REQUEST['id']);		// 	技能ID
$fortress_flag=false;
if( $_SESSION['first_in'] == 2 || $_SESSION['first_in'] == 3 )
{
	$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");	
}
$_SESSION['first_in'] = 2;	//处理中
if(isset($_GET['guildFight'])&&isset($_SESSION['guild_fight_bid'])&&$_SESSION['guild_fight_time']+300>time())
{
	require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
	$s=new socketmsg();
	$guild=new guild(&$s);
	
	$gid		= $_SESSION['guild_fight_bid'];
}else if(isset($_SESSION['fortress_gpc_time'])&&$_SESSION['fortress_gpc_time']+18>time()){
	$fortress_flag=true;
	$_SESSION['fortress_gpc_time']=time();
	$gid		= intval($_REQUEST['g']);
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
	if(!is_array($setting)) $setting=unserialize($setting);
	if(!is_array($setting))
	{
		$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");
		$_SESSION['first_in'] = 3;
		die('后台配置数据读取失败(1)！'.print_r($setting,1));
	}
	
	if(!isset($setting['fortress_time']))
	{
		$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");
		$_SESSION['first_in'] = 3;
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
		//1,210000,210459,212959,213459
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
	$gid		= intval($_REQUEST['g']);	 	//  被挑战玩家的宠物ID
}
$db_bb		= array();	//	数据库中宝宝的原始属性。

//$user		= $_pm['user']->getUserById($_SESSION['id']);
$user	 = unserialize($_pm['mem']->get(MEM_USER_KEY));
$fight		= $_SESSION['fight'.$_SESSION['id']];
if ( $fight['gid']==0 )
{
	$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");
	$_SESSION['first_in'] = 3;
	exit();
}

$_bag = unserialize($_pm['mem']->get(MEM_USERBAG_KEY));
//$sk	 = unserialize($_pm['mem']->get(MEM_USERSK_KEY));

$_sksys	 = unserialize($_pm['mem']->get(MEM_SKILLSYS_KEY));
$_gpc	 = unserialize($_pm['mem']->get(MEM_GPC_KEY));


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
$_sk		 = $_pm['user']->getUserPetSkillByIdS($_SESSION['id'],$_bb['id'],$id);
if(intval($_SESSION['id'])<1||intval($user['fightbb'])<1) exit('$_bb[\'id\']='.$_bb['id'].',$_SESSION[\'id\']='.$_SESSION['id']);
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

if(!$fortress_flag){
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
}else{
	if(is_array($_SESSION['fortress_gw'])){
		$gs = $_SESSION['fortress_gw'];
	}else{
		$gs = $_pm['mysql']->getOneRecord("SELECT b.*,
											 s.name as s_name, 
											 s.wx as s_wx,
											 s.value as s_value,
											 s.plus as s_plus,
											 s.uhp as s_uhp,
											 s.ump as s_ump,
											 s.id as s_id
										FROM userbb as b,skill as s
									   WHERE b.id=s.bid and b.id=".intval($_SESSION['fortress_gw'])."
									   LIMIT 0,1
									");
		$gs['hp'] = $gs['srchp'];
	}
}

$grs=$gs;
$memskillsysid = unserialize($_pm['mem']->get('db_skillsysid'));
$y = $memskillsysid[$skid];

if (is_array($gs) && is_array($y))
{
	// Componse array .
	$gs = array_merge($gs, array('s_name'  => $y['name'],
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
	
	$bb = number_format($aobj->skillack,'','',''). ',' . $rs['s_name'];
	$aobj1 = new Ack($gs, $rs);
	$aobj1 -> getSkillAck();
	
	$gwac= $aobj1->skillack;// . ',' . $gs['s_name'];

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
	$gw = number_format($gwac1,'','','') . ',' . $gs['s_name'];
	$gw1 = number_format($gwac,'','','') . ',' . $gs['s_name'];
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
	if (!is_array($ftgw))	// 插入用户的战斗记录及参战的怪物数据。
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
	if ($aobj->skillack < $ftgw['hp']) $nhp = $rs['hp']-$aobj1->skillack;
	else $nhp = $rs['hp'];
	$nmp = $rs['mp']-$rs['s_ump']; 
	if ($nhp<0) $nhp=0;
	if ($nmp<0) $nmp=0;

	$_pm['mysql']->query("UPDATE userbb
				   SET hp={$nhp}, 
				       mp={$nmp}
				 WHERE id={$rs['id']} and uid={$_SESSION['id']}
			  ");
	$drops='';
	if(isset($_GET['guildFight'])&&isset($_SESSION['guild_fight_bid'])&&$_SESSION['guild_fight_time']+300>time())
	{		
		if ($newhp == 0)
		{
			$rs=$guild->writeGuildFightScore($_SESSION['id'],$_SESSION['guild_fight_id']);
			$drops='战斗胜利！<br/>'.$rs;
			$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '' WHERE uid = '".$_SESSION['id']."'");	
			$_SESSION['first_in'] = 3;
		}else if ($nhp == 0){
			//$rs=$guild->writeGuildFightScore($_SESSION['guild_fight_id'],$_SESSION['id']);
			$drops='战斗失败！<br/>'; 
		}
		
		$hasAutoRecover=$_pm['mysql']->getOneRecord('select userbag.id from userbag,props where userbag.uid='.$_SESSION['id'].' and userbag.pid=props.id and props.varyname=21 limit 1');

		if($hasAutoRecover)
		{
			$_pm['mysql']->query("UPDATE userbb,player
									 SET hp=srchp,mp = srcmp,addmp = 0,addhp = 0
								   WHERE mbid=userbb.id and player.id=".$_SESSION['id']);
		}
	}else if($fortress_flag){
		$cur_cards=unserialize($_pm['mem']->get('fortress_card_info_'.date('md').'_'.$_SESSION['id']));
		
		if ($newhp == 0)
		{
			$_SESSION['guild_fight_time'] = 0;
			$table_name="`fortress_users_".date("Ymd")."`";
			$user_fortress=$_pm['mysql']->getOneRecord('select cur_gpc_id,bb_id,at_section_num,fv_result from '.$table_name.' where user_id='.$_SESSION['id']);
			if($user_fortress['fv_result']>=0)
			{
				$sql_extra=',v_times=v_times+1,fv_result=fv_result+1';
				$get_score=(2*abs($user_fortress['fv_result']+1)-1)*10;
			}
			else
			{
				$sql_extra=',v_times=v_times+1,fv_result=1';
				$get_score=10;
			}

			$row=$_pm['mysql']->getOneRecord('select buff_status from player_ext where uid='.$_SESSION['id']);
			if(
				($pos1=strpos($row['buff_status'],'add_zc_jifen:'))!==false
			){
				$pos2=strpos($row['buff_status'],';',$pos1);
				$pos1=strlen('add_zc_jifen:')+$pos1;
				$buff=substr($row['buff_status'],$pos1,$pos2-$pos1);
				$buffs=explode(',',$buff);
				if($buffs[0]==date('Ymd'))
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

			$cur_cards[]=array('id'=>$_SESSION['fortress_card_id'],'img' =>'<img src="../images/ys/win.png" width="62">');
			$_pm['mem']->set(array('k'=>'fortress_card_info_'.date('md').'_'.$_SESSION['id'],'v'=>$cur_cards));
			$_SESSION['fortress_card_id']=0;
			$_SESSION['fortress_pass']=1;
			$_pm['mysql']->query('update '.$table_name.' set cur_gpc_id=0'.$sql_extra.',score=score+'.$get_score.' where user_id='.$_SESSION['id']);
			$_pm['mysql']->query("UPDATE userbb,player
									 SET hp=srchp,mp = srcmp,addmp = 0,addhp = 0
								   WHERE mbid=userbb.id and player.id=".$_SESSION['id']);
		}else if ($nhp == 0){
			$_SESSION['guild_fight_time'] = 0;
			$table_name="`fortress_users_".date("Ymd")."`";
			$user_fortress=$_pm['mysql']->getOneRecord('select cur_gpc_id,bb_id,at_section_num,fv_result from '.$table_name.' where user_id='.$_SESSION['id']);
			if($user_fortress['fv_result']<=0)
			{
				$sql_extra=',f_times=f_times+1,fv_result=fv_result-1';
				$get_score=(2*abs($user_fortress['fv_result']-1)-1)*(-5);
			}
			else
			{
				$sql_extra=',f_times=f_times+1,fv_result=-1';
				$get_score=-5;
			}
			$cur_cards[]=array('id'=>$_SESSION['fortress_card_id'],'img' =>'<img src="../images/ys/miss.png" width="62">');
			$_pm['mem']->set(array('k'=>'fortress_card_info_'.date('md').'_'.$_SESSION['id'],'v'=>$cur_cards));
			$_SESSION['fortress_card_id']=0;
			$_SESSION['fortress_pass']=1;
			$_pm['mysql']->query('update '.$table_name.' set cur_gpc_id=0'.$sql_extra.',score=score+'.$get_score.' where user_id='.$_SESSION['id']);
			$drops='战斗失败！<br/>';
			$_pm['mysql']->query("UPDATE userbb,player
									 SET hp=srchp,mp = srcmp,addmp = 0,addhp = 0
								   WHERE mbid=userbb.id and player.id=".$_SESSION['id']);
		} 
	}else{
		if ($nhp == 0){
			$drops='很遗憾，挑战失败！'; // bb die.
		}
		else if ($newhp == 0) // gaiwu die
		{
			################################################################################		
			// 更新用户数据。记录用户战绩
			if (empty($user['fighttop'])) $ftop='1:0';
			else
			{
				$tmparr = explode(':',$user['fighttop']);
				$tmparr[0]=$tmparr[0]+1;
				$ftop=$tmparr[0].':'.$tmparr[1];
			}
	
			$_pm['mysql']->query("UPDATE player
						   SET fighttop='{$ftop}'
						 WHERE id={$_SESSION['id']}
					  ");
			
			// 更新对方的败绩。
			$cdes = $_pm['mysql']->getOneRecord("SELECT fighttop
												   FROM player
												  WHERE id={$grs['uid']}
												");
			if (empty($cdes['fighttop'])) $ftop='0:1';
			else
			{
				$tmparr = explode(':',$cdes['fighttop']);
				$tmparr[1]=$tmparr[1]+1;
				$ftop=$tmparr[0].':'.$tmparr[1];
			}
			$_pm['mysql']->query("UPDATE player
									 SET fighttop='{$ftop}'
								   WHERE id={$grs['uid']}
								");
	
			unset($prs, $rarr, $cdes);
			$drops = "<br/>恭喜您，挑战成功！<br/>您的战绩增加了 <font size=30% color=yellow>1</font> 点！";
			$word = " ,挑战 <font style=font-size:130%>{$gs['username']}</font> 的宝宝 <font style=font-size:130%>{$gs['name']}</font> 成功！获得了 <font style=font-size:130%>1</font> 点战绩！";
			$task = new task();
			//$task->saveGword($word);
		}
	}		

	if ($newhp == 0) {
		/*$r =$_SESSION['fight' . $_SESSION['id']];
		$r['hp']		= $newhp;
		$r['mp']		= $newmp;
		$r['fatting']	= 0;
		$r['fuzu']		= 0;
		$r['gid']		= 0;
		$_SESSION['fight'.$_SESSION['id']]= $r;*/
		unset($_SESSION['fight'.$_SESSION['id']]);
	}
	// Free resource.
	$_pm['mem']->memClose();
	
	
	if(!empty($att['hp1']) && empty($att['mp1']))
	{
		$str .= $nhp . ',' . $nmp. ',' . $bb.',<br />吸血'.$att['hp1'].'#'. $newhp . ',' . $gw1.'#' . $drops . '#' . $word;	
	}
	
	else if(!empty($att['hp1']) && !empty($att['mp1']) && $att['mp1'] > 0)
	{
		$str .= $nhp . ',' . $nmp. ',' . $bb.',<br />吸血'.$att['hp1'].'&nbsp;==<br />吸魔'.$att['mp1'].'&nbsp;#'. $newhp . ',' . $gw1.'#' . $drops . '#' . $word;	
	}
	else if(!empty($att['hp1']) && !empty($att['mp1']) && $att['mp1'] < 0)
	{
		$str .= $nhp . ',' . $nmp. ',' . $bb.',<br />吸血'.$att['hp1'].'&nbsp;==<br /失魔'.$att['mp1'].'&nbsp;#'. $newhp . ',' . $gw1.'#' . $drops . '#' . $word;
	}
	else if(empty($att['hp1']) && !empty($att['mp1']) && $att['mp1'] < 0)
	{
		$str .= $nhp . ',' . $nmp. ',' . $bb.'<br /> 失魔'.$att['mp1'].'&nbsp;#'. $newhp . ',' . $gw1.'#' . $drops . '#' . $word;	
	}
	else if(empty($att['hp1']) && !empty($att['mp1']) && $att['mp1'] > 0)
	{
		$str .= $nhp . ',' . $nmp. ',' . $bb.',<br />吸魔'.$att['mp1'].'&nbsp;#'. $newhp . ',' . $gw1.'#' . $drops . '#' . $word;	
	}
	else
	{
		 //     10022   ,    100    ,    1,普通攻击#0,396,普通攻击#战斗胜利！<br/>##1,0,0#
  	     //      [  10022   ],[    100    ],[1,普通攻击]#[   0         ],[    456,普通攻击]#[战斗胜利！<br/>]##1,0,0#
		$str .= ''.$nhp . ',' . $nmp. ',' . $bb.' #'. $newhp . ',' . $gw1.'#' . $drops . '#' . $word;
	}
	if(!empty($att['hpdx']))
	{
		$str .= '<dx>抵销：'.$att['hpdx'];
	}
	
	if( ($rs['s_uhp']<0||$rs['s_ump']<0) && ($rs['mp']< $rs['s_ump']) ){
		$str.='#'.$rs['s_vary'].',0,0#'.$mmonsterContinueFlag;
	}else{
		 $str.='#'.$rs['s_vary'].','.$rs['s_uhp'].','.$rs['s_ump'].'#'.$mmonsterContinueFlag;
	}
	if(!empty($att['ack']))
	{
		$str .= '#<ack>伤害加深：'.$att['ack'];
	}
	$str .= "*".$Crit;	//是否暴击
	$ack_type = 0;
	$str .= "*".$ack_type;	//五行攻击
	echo $str;
}
else
{	$drops='宝宝 ' . $grs['name'].' 逃跑了！！！';
	echo '0,0,0#0,0#' . $drops . '#' . $word;	
}
$_SESSION['first_in'] = 4;	//处理完
// =========================
?>
