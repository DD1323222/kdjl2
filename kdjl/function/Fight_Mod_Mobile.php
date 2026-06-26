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
require_once('../config/config.game.php');

//因为不同难度得组队副本，是不同得地图，这个三个地图看做一个图，无法计算知道哪三个是一起得，只能写死
//注意fight_mod.php和team_mod.php里面都有这个数组
$__teamFubenMap=array(
	'128'=>128,
	'129'=>128,
	'130'=>128
);
if( $_SESSION['gs'] == 3 )
{
	$is_dz = $_pm['mysql'] -> getOneRecord(" SELECT * FROM team WHERE inmap = '128' AND creator = '".$_SESSION['id']."'");
	if( isset($is_dz['id']) )
	{
		$_pm['mysql'] -> query(" DELETE FROM team WHERE creator = '".$_SESSION['id']."'");
		$_pm['mysql'] -> query(" DELETE FROM team_members WHERE team_id = '".$is_dz['id']."'");
		header('location:/function/Team_Mod.php?n=128');
	}
}
require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
$s=new socketmsg();
$team=new team($_SESSION['team_id'],&$s);
$myState=$team->checkMyTeam();
$flagteam=false;
$isleader=false;
$teamInfo=array();

//$memmapid = unserialize($_pm['mem']->get('db_mapid'));


$sql = "select inmap from player where id=".$_SESSION['id'];
$mapcheck = $_pm['mysql'] -> getOneRecord($sql);
$theTeamFubenMap=false;
$teamFbstr='var intfbFlag=false;';
//将取多条数据改为取单条数据
$baseMapInfo =  getBaseMapInfoById($mapcheck['inmap']);
$memmapid[$mapcheck['inmap']] = $baseMapInfo;

$chaoshenchongDituFlag=false;
if($memmapid[$mapcheck['inmap']]['multi_monsters']==4)
{
	$chaoshenchongDituFlag=true;
}

if(isset($mapcheck['inmap'])&&isset($memmapid[$mapcheck['inmap']])&&$memmapid[$mapcheck['inmap']]['multi_monsters']==3&&!isset($_SESSION['team_id']))
{
	die('<script language="javascript">
parent.recvMsg("SM|<font color=\'#442266\'>此地图，必须组队才能战斗!</font>");
window.history.back();
</script>');
}
else if(isset($mapcheck['inmap'])&&isset($memmapid[$mapcheck['inmap']])&&$memmapid[$mapcheck['inmap']]['multi_monsters']==3&&isset($_SESSION['team_id']))
{
	//
	$_pm['mem']->del('tarot_info1_'.$_SESSION['team_id']);	
	$teamInfo=$team->getTeamInfo();
	$ct=0;
	$leaderCzl=0;
	$limitSetting=explode(',',$memmapid[$mapcheck['inmap']]['level']);
	$minLevel=intval($limitSetting[0]);
	$czlDiff=intval($limitSetting[1]);
	
	$needRow=$_pm['mysql']->getOneRecord('select uid from fuben where uid='.$_SESSION['id'].' and inmap ='.intval($__teamFubenMap[$mapcheck['inmap']]).' and left(lttime,8)="'.date('Ymd').'"');
	$sj=preg_replace('/([^,]+,)*sj\:(\d+)[^\d]*/','$2',$memmapid[$mapcheck['inmap']]['needs']);
	$memberNeedSjFlag=false;
	$teamState=$team->getTeamState();
	foreach($teamInfo['members'] as $__k=>$mem)
	{
		if($mem['state']==1)
		{
			$csql='select b.level,b.czl,fb.uid,fb.id fbid,p.nickname,fb.lttime from userbb b,player p left join fuben fb on fb.uid=p.id and fb.inmap='.intval($__teamFubenMap[$mapcheck['inmap']]).' and left(fb.lttime,8)="'.date('Ymd').'" where p.mbid=b.id and p.id='.$mem['uid'];
			$userbb = $_pm['mysql']->getOneRecord($csql);
			if(
				!$userbb||
				$userbb['level']<$minLevel&&
				$minLevel>0
			)
			{
				die('<script language="javascript">
parent.Alert("<font color=\'#442266\'>有队员没有设置主战宠物，或者有队员主战宠物等级低于：'.$minLevel.'!</font>");
window.location="/function/Team_Mod.php?n='.$_SESSION['team_inmap'].'";
</script>');
			}else{
				$teamInfo['members'][$__k]['fbid']=$userbb['fbid'];
			}
			
			if($chaoshenchongDituFlag&&$userbb['wx']!=7)
			{
				die('<script language="javascript">
parent.Alert("<font color=\'#442266\'>有队员的主战宠物不是神圣宠物!</font>");
window.location="/function/Team_Mod.php?n='.$_SESSION['team_inmap'].'";
</script>');
			}

			if($leaderCzl==0)
			{
				$leaderCzl=$userbb['czl'];
			}
			else if(
				($leaderCzl-$czlDiff>$userbb['czl']||$leaderCzl+$czlDiff<$userbb['czl'])
				&&
				$czlDiff>0
			)
			{
				die('<script language="javascript">
parent.Alert("<font color=\'#442266\'>队员('.$userbb['nickname'].')主战宠物成长率和队长相比差距大于'.$czlDiff.'!</font>");
window.location="/function/Team_Mod.php?n='.$_SESSION['team_inmap'].'";
</script>');
			}
			//echo (!$needRow).'&&'.($userbb['uid']>0).'&&'.(substr($userbb['lttime'],9)>0).'&&'.(substr($userbb['lttime'],9)<10).'<br/>';
			if(!$needRow&&$userbb['uid']>0&&substr($userbb['lttime'],9)<10)
			{				
				if(isset($_GET['oksj']))
				{
					$memberNeedSjFlag=true;
					//
				}else{
					$exclude=array($_SESSION['id']);
					foreach($teamInfo['members'] as $row)
					{
						if($row['state']<1){
							$exclude[]=$row['uid'];
						}
					}
					//$s=$team->getTeamState();
					$sr=$team->snotice(
					iconv('gbk','utf-8','_AL_队伍中'.$userbb['nickname'].' 今日已经用完免费次数，如若继续进行战斗，需要扣除队长'.$sj.'水晶!'),$teamInfo,$exclude
					);
					
					die('<script language="javascript">
parent.Alert("<font color=\'#ffffff\'>队伍中'.$userbb['nickname'].' 今日已经用完免费次数，如若继续进行战斗，需要扣除队长'.$sj.'水晶!</font><br/><span style=\'cursor:pointer\' onclick=\'$(\\"gw\\").contentWindow.location=\\"/function/Fight_Mod.php?oksj\\"\'><font color=\'#ff0000\'><strong>点击这里继续,将会扣除队长水晶!</strong></font></span>");
window.location="/function/Team_Mod.php?n='.$_SESSION['team_inmap'].'";
</script>');
				}
			}

			if(substr($userbb['lttime'],8)>=10&&(!isset($teamState['fubensjoj'])||!$teamState['fubensjoj']))
			{
				die('<script language="javascript">
parent.Alert("<font color=\'#ffffff\'>队员 '.$userbb['nickname'].' 进入副本次数已经达到最大限度了!</font>");
window.location="/function/Team_Mod.php?n='.$_SESSION['team_inmap'].'";
</script>');
			}

			$ct++;
		}
	}
	
	if($ct<2)
	{
		die('<script language="javascript">
parent.recvMsg("SM|<font color=\'#442266\'>至少要有一名其它队员归队,您才能开始战斗!</font>");
window.location="/function/Team_Mod.php?n='.$_SESSION['team_inmap'].'";
</script>');
	}	
	
	if($needRow&&!isset($_GET['oksj'])&&(!isset($teamState['fubensjoj'])||!$teamState['fubensjoj']))
	{
		die('<script language="javascript">
		if(confirm("您需要支付 '.$sj.' 水晶才能继续！\n继续么？"))
		{
			window.location="'.$_SERVER['REQUEST_URI'].(strpos($_SERVER['REQUEST_URI'],'?')===false?'?oksj':'&oksj').'";
		}else{
			window.location="/function/Team_Mod.php";
		}
		</script>');
	}else if(($needRow||$memberNeedSjFlag)&&isset($_GET['oksj'])&&(!isset($teamState['fubensjoj'])||!$teamState['fubensjoj'])){
		$_pm['mysql'] -> query("UPDATE player_ext SET sj = sj - ".$sj." WHERE uid = {$_SESSION['id']} AND sj >= ".$sj."");
		if(mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){
			die('
		<script language="javascript">
			alert("对不起，您的水晶不够支付！");
			window.location="/function/Team_Mod.php";		
		</script>
');
		}
		$team->setTeamState(array('fubensjoj'=>1));
		
		//$teamInfo=$team->getTeamInfo();
		foreach($teamInfo['members'] as $mem)
		{
			if($mem['state']==1)
			{
				if(isset($mem['fbid'])){
					$sql='update fuben set lttime=concat("'.date('Ymd').'",if(SUBSTRING(lttime,9)+1,SUBSTRING(lttime,9)+1,1)) where uid='.$mem['uid'].' and id='.$mem['fbid'].' and inmap='.$__teamFubenMap[$mapcheck['inmap']];
				}else{
					$sql='insert into fuben set uid='.$mem['uid'].',lttime=concat("'.date('Ymd').'",if(SUBSTRING(lttime,9)+1,SUBSTRING(lttime,9)+1,1)),inmap='.$__teamFubenMap[$mapcheck['inmap']];
				}
				$_pm['mysql'] ->query($sql);
				if($error=mysql_error())
				{
					die($error."<br/>".$sql);
				}
			}
		}
	}else if($needRow&&(!isset($teamState['fubensjoj'])||!$teamState['fubensjoj'])){	
		header('location:/function/Team_Mod.php');
		exit;
	}else if(!$needRow){
		//免费机会只有一次
		$team->setTeamState(array('fubensjoj'=>1));
		//$teamInfo=$team->getTeamInfo();
		foreach($teamInfo['members'] as $mem)
		{
			if($mem['state']==1)
			{
				if(isset($mem['fbid'])){
					$sql='update fuben set lttime=concat("'.date('Ymd').'",if(SUBSTRING(lttime,9)+1,SUBSTRING(lttime,9)+1,1)) where uid='.$mem['uid'].' and id='.$mem['fbid'].' and inmap='.$__teamFubenMap[$mapcheck['inmap']];
				}else{
					$sql='insert into fuben set uid='.$mem['uid'].',lttime=concat("'.date('Ymd').'",if(SUBSTRING(lttime,9)+1,SUBSTRING(lttime,9)+1,1)),inmap='.$__teamFubenMap[$mapcheck['inmap']];
				}
				$_pm['mysql'] ->query($sql);
				if($error=mysql_error())
				{
					die($error."<br/>".$sql);
				}
			}
		}
	}
	$teamState=$team->getTeamState();
	
	if(
		$teamState['team_fuben_step'][0]+1==3
		&&
		empty($teamState['monsters'])
		&&
		empty($teamState['cur_monster'])
		&&
		empty($teamState['monsters_tf_3'])
		&&
		!isset($_GET['team_auto'])//没有已经在翻
	)
	{
		$team->setTeamState(array(
							'team_fuben_card_step_num'=>3,
							'team_fuben_step'=>array(2,0),
							'team_fuben_flag'=>1,
							'team_fuben_get_card_users'=>array(),
							'team_fuben_get_card_sj_users'=>array()
							));	
		$_SESSION['gs'] = 3;
		$_SESSION['gs_status'] == "lock";
		header('location:/function/tarot_Mod.php');
		die('最后一关队长翻牌！');
	}

	if(isset($teamState['team_select_map'])&&$teamState['team_select_map']>0)
	{
		if(
			isset($memmapid[$teamState['team_select_map']])&&$memmapid[$teamState['team_select_map']]['multi_monsters']==3
			&&
			$teamState['team_select_map']+3>$mapcheck['inmap']&&$teamState['team_select_map']-3<$mapcheck['inmap']
		)
		{
			$_pm['mysql']->query('update player set inmap='.$teamState['team_select_map'].' where id='.$_SESSION['id']);
			$_SESSION['team_inmap']=$teamState['team_select_map'];
			$mapcheck['inmap']=$teamState['team_select_map'];
		}
	}
	if(!isset($teamState['team_fuben_step'])||!is_array($teamState['team_fuben_step'])){
		$state['team_fuben_step']=array(0,0);
		$state['team_fuben_flag']=1;
		$team->setTeamState($state);
	}else{
		$state['team_fuben_flag']=1;
		$team->setTeamState($state);
	}
	$theTeamFubenMap=$memmapid[$mapcheck['inmap']];	
	$teamFbstr='var intfbFlag=true;';
	
	
}else if(isset($_SESSION['team_id'])&&$_SESSION['team_id']>0){
	$state['team_fuben_flag']=0;
	$team->setTeamState($state);
}

//$waittimestr='';
if(isset($_SESSION['team_id'])&&$_SESSION['team_id']>0)
{
	$isleader=$team->isTeamLeader($_SESSION['id'],$_SESSION['team_id']);
	if($isleader&&isset($_GET['team_auto']))
	{
		$data=array();
		$data['autofighting']=intval($_GET['team_auto']);
		
		$oldData=$team->getTeamState();
		$dataNow=array();
		
		if(isset($oldData['team_fuben_flag']))
		{
			$dataNow['team_fuben_flag']=$oldData['team_fuben_flag'];			
		}

		if(isset($oldData['team_fuben_step']))
		{
			$dataNow['team_select_map']=$oldData['team_select_map'];
			$dataNow['autofighting']=$oldData['autofighting'];	
			$dataNow['team_fuben_boss']=$oldData['team_fuben_boss'];
			$dataNow['team_fuben_step']=$oldData['team_fuben_step'];
			$dataNow['fubensjoj']=$oldData['fubensjoj'];
			if($oldData['team_fuben_card_step_num']==3)
			{
				$dataNow['monsters']=$oldData['monsters_bak'];
				$dataNow['cur_monster']=$oldData['cur_monster'];
				$dataNow['team_fuben_card_step_num']=$oldData['team_fuben_card_step_num'];
				$dataNow['team_fuben_step']=$oldData['team_fuben_step'];
				$dataNow['autofight']=$oldData['autofight'];
				$dataNow['fight_html']=$oldData['fight_html'];
			}
		}

		$_pm['mem']->setns('pm_team_fight_'.$_SESSION['team_id'],$dataNow);
		
		if(isset($_GET['setteamauto'])&&$oldData['team_fuben_card_step_num']!=3){
			$team->clearTeamState();
		}
		$team->setTeamState($data);		
		$_SESSION['fight'.$_SESSION['id']]['ftime']=0;
	}
	
	$teamState=$team->getTeamState();
	
	if(isset($teamState['team_fuben_step']))
	{
		$theTeamFubenMap=$memmapid[$_SESSION['team_inmap']];
		if($theTeamFubenMap['multi_monsters']!=3)//不是组队副本地图
		{
			$theTeamFubenMap=false;
			$team->clearTeamFubenData();
		}
	}

	$teamInfo=$team->getTeamInfo();
	$isMyTurn=false;
	$ct=0;
	foreach($teamInfo['members'] as $mem)
	{
		if($mem['state']==1)
		{
			$ct++;
		}
		else if($theTeamFubenMap!==false&&$mem['state']<1)//组队副本踢掉所有没有归队的人
		{
			$team->kickMember($mem['uid'],true);
		}
	}
	
	if($ct<2)
	{
		die('<script language="javascript">
parent.recvMsg("SM|<font color=\'#442266\'>至少要有一名其它队员归队,您才能开始组队战斗!</font>");
window.location="/function/Team_Mod.php?n='.$_SESSION['team_inmap'].'";
</script>');
	}
	if(!$isleader)
	{
		if($teamState['fighting']==0){
			//队员非法进入这里
			header("refresh:2;url=".$_SERVER['REQUEST_URI']);
			exit('稍等……');
		}else{
			foreach($teamInfo['members'] as $amem)
			{
				if($amem['living']==1&&$amem['state']==1)
				{
					if($amem['uid']==$_SESSION['id'])
					{
						$isMyTurn=true;
					}					
					break;
				}
			}
				
			if(!$isMyTurn){
				if(strpos($teamState['fight_html'],'<body')===false&&strlen($teamState['fight_html'])<100)
				{	
					$team->checkLost();
					header("refresh:2;url=".$_SERVER['REQUEST_URI']);
					echo '<span style="font-size:12px">等待其他队员操作,请稍等……!</font>';
					exit();
				}
				unset($_SESSION['fight'.$_SESSION['id']]);				
				die($teamState['fight_html']);
			}else{
				$flagteam=true;
			}
		}
	}else{
		$team->checkLost();
		$flagteam=true;
	}
}

if($_REQUEST['from'] != 1)
{
	secStart($_pm['mem']);
}
header("Cache-Control: no-cache, must-revalidate");
header("Pragma:no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
define(MEM_BOSS_KEY, $_SESSION['id'] . 'boss');
define(MEM_FIGHT_KEY, $_SESSION['id'] . 'fight'); // 保存战斗信息。
//加速外挂
$time = time();
/*$sql = "SELECT time FROM fight_log WHERE uid = {$_SESSION['id']} and vary = 2";
$timearr = $_pm['mysql'] -> getOneRecord($sql);
if(is_array($timearr)){
	$ctime = $time - $timearr['time'];
	if($ctime < 2){
		if(!$flagteam){
			$_SESSION['id'] = '';
			die('操作过快！');
		}
	}else{
		$_pm['mysql'] -> query("UPDATE fight_log SET time = ".time()." WHERE uid = {$_SESSION['id']} and vary = 2");
	}
}else{
	$_pm['mysql'] -> query("INSERT INTO fight_log (uid,time,vary) VALUES({$_SESSION['id']},".time().",2)");
}*/


//在这里结束


$user	= $_pm['user']->getUserById($_SESSION['id']);

//$memgpc = unserialize($_pm['mem'] -> get('db_gpcid'));
$userbb = $_pm['user']->getUserPetById($_SESSION['id']);
$bag    = $_pm['user']->getUserBagById($_SESSION['id']);
$fight	=	$_SESSION['fight'.$_SESSION['id']];
$_SESSION['fttime'.$_SESSION['id']] = 10;
$expflag = 0;

$time = time();
$usermap = explode(",",$user['mapinfo']);
foreach($usermap as $v)
{
	$mapinfo = explode(":",$v);
	$time = time();
	if($mapinfo[0] == $user['inmap'] && $mapinfo[1] > $time)
	{
		$mapflag = 1;//地图已经打开
		break;
	}
}

$openmap = explode(",",$user['openmap']);
if(
	!in_array($_REQUEST['n'],$openmap) 
	&& $_REQUEST['n'] != 125 && $_REQUEST['n'] != 15
	&& $_REQUEST['n'] != 19  && $_REQUEST['n'] != 126	
	&& $_REQUEST['n'] != 20 && $_REQUEST['n'] != 128
	&& $_REQUEST['n'] != 18 && $_REQUEST['n'] != 17
	&& $_REQUEST['n'] != 101 && $_REQUEST['n'] != 102
	&& $_REQUEST['n'] != 104 && $_REQUEST['n'] != 105
	&& $_REQUEST['n'] != 107 && $_REQUEST['n'] != 108
	&& $_REQUEST['n'] != 110 && $_REQUEST['n'] != 111
	&& $_REQUEST['n'] != 113 && $_REQUEST['n'] != 114
	&& $_REQUEST['n'] != 116 && $_REQUEST['n'] != 117
	&& $_REQUEST['n'] != 119 && $_REQUEST['n'] != 120
	&& $_REQUEST['n'] != 122 && $_REQUEST['n'] != 123
	&& $_REQUEST['n'] != 129 && $_REQUEST['n'] != 130
	&& $_REQUEST['n'] != 132 && $_REQUEST['n'] != 133
	&& $_REQUEST['n'] != 135 && $_REQUEST['n'] != 136
	&& $_REQUEST['n'] != 138 && $_REQUEST['n'] != 139
	&& $_REQUEST['n'] != 141 && $_REQUEST['n'] != 142
	&& $_REQUEST['n'] != 143 && $_REQUEST['n'] != 144
	&& $_REQUEST['n'] != 145 && $_REQUEST['n'] != 146
	&& $_REQUEST['n'] != 147 && $_REQUEST['n'] != 148
	&& $_REQUEST['n'] != 149 && $_REQUEST['n'] != 150
	&& isset($_REQUEST['n'])
)
{
	$_pm['mysql']->query('update player set inmap=0 where id='.$_SESSION['id']);
	die("地图开放时间到期，或者地图未开启(".$_REQUEST['n'].")！");
}



if (!in_array($user['inmap'],$_game['map'])) // 地图限制
{
	/*
	$_pm['mysql']->query("UPDATE player 
							 SET secid=2
						   WHERE id={$_SESSION['id']}");
					*/
	unset($_SESSION['id']);
	$_pm['mem']->memClose();
	echo '<center>您的帐号非法操作，服务器强制断线(3)！</center>';
	exit();
}

//加入进入地图需要物品才能进入的功能
if(strpos($memmapid[$user['inmap']]['needs'],'needprops:') !== false){
	$pcheck = explode('needprops:',$memmapid[$user['inmap']]['needs']);
	$pa = $_pm['mysql'] -> getOneRecord("SELECT sums FROM userbag WHERE uid = {$_SESSION['id']} AND pid = {$pcheck[1]} AND sums > 0");
	if($pa['sums'] < 1){
		die("<script>window.parent.Alert('您背包中没有必须道具！');window.parent.document.getElementById('gw').src='function/Team_Mod.php?n=".$user['inmap']."';</script>");
	}
}
//加入进入地图需要物品才能进入的功能在此结束
$maparr = $memmapid[$user['inmap']];
if($maparr['multi_monsters'] == 1){
	$_SESSION['multi_monsters'.$_SESSION['id']] = 1;//挑战地图
}else if($maparr['multi_monsters'] == 2){//通关塔
	$_SESSION['multi_monsters'.$_SESSION['id']] = 3;
}else{
	$_SESSION['multi_monsters'.$_SESSION['id']] = 2;//普通地图
}
$arr = $_pm['mysql'] -> getOneRecord('SELECT img FROM map WHERE id = '.$user['inmap']);
$bgtype = $arr['img'];
if($bgtype == 'swf'){
	//$flash = '<embed src="'.IMAGE_SRC_URL.'/map/t'.$user['inmap'].'/'.$user['inmap'].'.swf" width="778" height="311" wmode="transparent">';
	$flash = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,19,0" width="778" height="311">
			  <param name="movie" value="'.IMAGE_SRC_URL.'/map/t'.
			  $user['inmap']
			  .'/'.
			  $user['inmap']
			  .'.swf">
			  <param name="quality" value="high">
			  <param name="wmode" value="transparent">
			  <embed src="'.IMAGE_SRC_URL.'/map/t'.$user['inmap'].'/'.$user['inmap'].'.swf" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" width="778" height="311" wmode="transparent"></embed>
           </object>';
}else{
	$flash = '';
}

//#########################
if (is_array($fight))
{
	   // Check time 
	   $will = (10-time()+$fight['ftime']);
	   if (
	   		$fight['ftime']+10>=time() 
	   ){


	   	$end='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=gbk" />
</head>
<!--[if IE 6]><script type="text/javascript">try{ document.execCommand("BackgroundImageCache", false, true); } catch(e) {}
</script>
<![endif]-->
<body style="background-color: #FFFCEB;margin-top:0px;">
<center>
  <div style="margin-top:140px;"><img src="'.IMAGE_SRC_URL.'/ui/fight/loading.gif"/><div id="timev"  style="position:absolute; text-align:center; color:#F98F2C; font-weight:bold;font-size:2em;left: 390px; top: 160px; height: 40px;"></div>
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
		window.setTimeout("pause("+m+")",1000);
		return;
	}
	else{
		document.getElementById("timev").innerHTML = m--;
		readH=window.setTimeout("loadtime("+m+");", 1000);
	}
}
function pause(m)
{   if (pt==1) return;
	if(m == 0){
		window.parent.document.getElementById("gw").src="./function/Fight_Mod.php?p='.$_REQUEST['p'].'&s=t";
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


// Get bb info.
if($_REQUEST['from'] == 1)
{
	$bid = $user['mbid'];
}
else
{
	$bid = intval($_REQUEST['p']);
}
$arrobj = new arrays();

$bb = $arrobj->dataGet(array('k' => MEM_BB_KEY, 
					  		 'v' => "if(\$rs['id'] == '{$bid}' && \$rs['uid'] == '{$_SESSION['id']}') \$ret=\$rs;"
					        ),
							$userbb
					  );	 

if (!is_array($bb))
{
	if (!empty($fight)&&isset($_SESSION['fight'.$_SESSION['id']]['bid'])&&$_SESSION['fight'.$_SESSION['id']]['bid']>0)
	{
		$bid = $_SESSION['fight'.$_SESSION['id']]['bid'];
	}
	else $bid = $user['mbid'];

	$bb = $arrobj->dataGet(array('k' => MEM_BB_KEY, 
					  			 'v' => "if(\$rs['id'] == '{$bid}' && \$rs['uid'] == '{$_SESSION['id']}') \$ret=\$rs;"
								),
							$userbb
					     );
}
$_SESSION['mbid'] = $bid;

if($chaoshenchongDituFlag&&$bb['wx']!=7)
{
	die("<script language='javascript'>parent.Alert('只有神圣宠物,才可以在这里战斗！');".'window.location="/function/Team_Mod.php?n='.$mapcheck['inmap'].'";'."</script>");
}




if (!is_array($bb))
{
	if(isset($_SESSION['team_inmap'])){
		header('location:/function/Team_Mod.php?233&n='.$_SESSION['team_inmap']);		
		exit('('.$_SESSION['team_inmap'].')') ;
	}else{
		die('不能获得宠物数据！');
	}
}
else
{
	if(
		$bb['czl']<$memmapid[$mapcheck['inmap']]['czlprops']
		&&
		$memmapid[$mapcheck['inmap']]['czlprops']>90000
	)
	{
		die('成长不够!');
	}
	// ============================== 装备效果开始 ==========================================
	//宠物的血量和魔法的最大值的计算（加上装备的效果）；
	$arr = getzbAttrib($bid);
	$bb['srchp'] += $arr['hp'];
	$bb['srcmp'] += $arr['mp'];
	$bb['hp'] += $arr['hp'];
	$bb['mp'] += $arr['mp'];
	/*$sql = "SELECT addmp,addhp FROM userbb WHERE uid = {$_SESSION['id']} and id = {$bid}";
	$add = $_pm['mysql'] -> getOneRecord($sql);
	$bb['srchp'] += $add['addhp'];
	$bb['srcmp'] += $add['addmp'];*/
   // ================================ 装备效果结束 ========================================

	//if ($bb['hp'] <= 0) err($_bbword[rand(0,count($_bbword)-1)]);

	
	
		//金币版
	if($_SESSION['exptype'.$_SESSION['id']] == 1 && $_SESSION['multi_monsters'.$_SESSION['id']] == 2)
	{
		if((empty($_SESSION['way'.$_SESSION['id']]) || $_SESSION['way'.$_SESSION['id']] == "money") && $user['autofitflag']==1 && $user['sysautosum']>0)
		{
			$recoverHPFlag=true;
			if($flagteam)
			{
				if(!$teamState['autofighting'])
				{
					$recoverHPFlag=false;
				}
			}
			if($recoverHPFlag){
				$_SESSION['fttime'.$_SESSION['id']] = 4;
				if(!empty($arr['hp']) && !empty($arr['mp']))
				{
					$_pm['mysql'] -> query("UPDATE userbb
							   SET hp=srchp,mp=srcmp/2,addhp={$arr['hp']},addmp={$arr['mp']}/2
							 WHERE id={$bid} and uid={$_SESSION['id']}");
				}
				else if(!empty($arr['hp']) && empty($arr['mp']))
				{
					$_pm['mysql'] -> query("UPDATE userbb
							   SET hp=srchp,mp=srcmp/2,addhp={$arr['hp']}
							 WHERE id={$bid} and uid={$_SESSION['id']}");
				}
				else if(empty($arr['hp']) && !empty($arr['mp']))
				{
					$_pm['mysql'] -> query("UPDATE userbb
							   SET hp=srchp,mp=srcmp/2,addmp={$arr['mp']}/2
							 WHERE id={$bid} and uid={$_SESSION['id']}");
				}
				else
				{
					$_pm['mysql'] -> query("UPDATE userbb
							 SET hp=srchp,mp=srcmp/2
							 WHERE id={$bid} and uid={$_SESSION['id']}");
				}
				$bb['hp'] = $bb['srchp']; //保证显示是正确的，因为$bb['hp']这个为0，但是已经被修改为最大值了 
			}
		}
		//元宝版
		else if($_SESSION['way'.$_SESSION['id']] == "yb" && $user['autofitflag']==1 && $user['maxautofitsum']>0 && $_SESSION['multi_monsters'.$_SESSION['id']] == 2)
		{
			$_SESSION['fttime'.$_SESSION['id']] = 3;
			if(!empty($arr['hp']) && !empty($arr['mp']))
			{
				$_pm['mysql'] -> query("UPDATE userbb
						   SET hp=srchp,mp=srcmp,addhp={$arr['hp']},addmp={$arr['mp']}
						 WHERE id={$bid} and uid={$_SESSION['id']}");				
			}
			else if(!empty($arr['hp']) && empty($arr['mp']))
			{
				$_pm['mysql'] -> query("UPDATE userbb
						   SET hp=srchp,mp=srcmp,addhp={$arr['hp']}
						 WHERE id={$bid} and uid={$_SESSION['id']}");
			}
			else if(empty($arr['hp']) && !empty($arr['mp']))
			{
				$_pm['mysql'] -> query("UPDATE userbb
						   SET hp=srchp,mp=srcmp,addmp={$arr['mp']}
						 WHERE id={$bid} and uid={$_SESSION['id']}");
			}
			else
			{
				$_pm['mysql'] -> query("UPDATE userbb
					  	 SET hp=srchp,mp=srcmp
						 WHERE id={$bid} and uid={$_SESSION['id']}");
			}
			$bb['hp'] = $bb['srchp']; //保证显示是正确的，因为$bb['hp']这个为0，但是已经被修改为最大值了 
		}
	}
	else 
	{
		if(!empty($arr['hp']) && !empty($arr['mp']))
		{
			$_pm['mysql']->query("UPDATE userbb
						  SET addhp={$arr['hp']},addmp={$arr['mp']}
						WHERE id={$bid} and uid={$_SESSION['id']}
					 ");
		}
		else if(!empty($arr['hp']) && empty($arr['mp']))
		{
			$_pm['mysql']->query("UPDATE userbb
					   SET addhp={$arr['hp']}
					 WHERE id={$bid} and uid={$_SESSION['id']}
				  ");
		}
		else if(empty($arr['hp']) && !empty($arr['mp']))
		{
			$_pm['mysql']->query("UPDATE userbb
					   SET addmp={$arr['mp']}
					 WHERE id={$bid} and uid={$_SESSION['id']}
					 ");
		}
	}

	// By field order.
	$bb['wx'] = getWx($bb['wx']);
	$bbinfo = "['{$bb['name']}',{$bb['level']},'{$bb['wx']}',{$bb['ac']},{$bb['mc']},{$bb['hp']},{$bb['mp']},'{$bb['skillist']}','{$bb['imgstand']}','{$bb['imgack']}','{$bb['imgdie']}',{$bid},'{$bb['srchp']}','{$bb['srcmp']}','{$bb['nowexp']}','{$bb['lexp']}']";
}

// Get detail jn info.
$tjn = split(",", $bb['skillist']);
foreach($tjn as $mkey => $n)
{
	$tt = split(":", $n);
	$jlist .= $tt[0] . ",";
}
$jlist =	substr($jlist, 0, -1);
$bjn   =	$_pm['user']->getUserPetSkillById($_SESSION['id']);
if (!is_array($bjn))
{
	for($i = 1;$i++;$i<5){
		$bjn   =	$_pm['user']->getUserPetSkillById($_SESSION['id']);
		if(!is_array($bjn)){
			$bjn   =	$_pm['user']->getUserPetSkillById($_SESSION['id']);
		}else{
			break;
		}
	}
	//Header("Location:Fight_Mod.php?p={$bid}");exit();
}
if (!is_array($bjn)){
	header("refresh:2;url=Fight_Mod.php?p={$bid}");
	echo '585';
	exit;
}

$jlistarr = split(',', $jlist);
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
				$ak = split(":", $rs['value']);
				$rs['value']=$ak[count($ak)-1];
			}
		}
		else $rs['value']=0;
		
		 $rs['value'] = str_replace("%","0",$rs['value']);
		$jnlist .="['{$rs[name]}',{$rs[level]},'{$rs[vary]}',{$rs[wx]},'{$rs[value]}','{$rs[plus]}','{$rs['img']}',{$rs[uhp]},{$rs[ump]},{$rs['sid']}],";
	}
}
$jnlist = substr($jnlist, 0, -1); // []#[];
// from current map choose level limit.

$levels = $memmapid[$user['inmap']];
/*$levels = $_pm['mem']->dataGet(array('k' => MEM_MAP_KEY, 
 	  						'v' => "if(\$rs['id'] == '{$user['inmap']}') \$ret=\$rs;"
					));*/

/**###################################
*Level limit lock
###################################*/
if (!is_array($levels) || $levels['level']<1 )
{
	$levels['level']="1,15";
}
$lvl = explode(',', $levels['level']);

$idse = rand($lvl[0], $lvl[1]); // <<<<<<<


/*$gw = $_pm['mem']->dataGetAll(array('k' => MEM_GPC_KEY, 
						   'v' => "if(\$rs['level'] == '{$idse}') \$ret=\$rs;"
					));
*/


if($_SESSION['multi_monsters'.$_SESSION['id']] == 1){//挑战
	$sql = "SELECT id,gid FROM challenge_log WHERE uid = {$_SESSION['id']} ORDER BY id DESC LIMIT 2";
	$gw1 = $_pm['mysql'] -> getRecords($sql);
	if(empty($gw1)){
		die('数据错误(1)！');
	}
	//$gw = $memgpc[$gw1[0]['gid']];
	$gw = getBaseGpcInfoById($gw1[0]['gid']);//改为单条取记录
	$_SESSION['multi_monsters_id'.$_SESSION['id']] = $gw1[0]['id'];
	$_SESSION['multi_monsters_next'.$_SESSION['id']] = $gw1[1]['gid'];
	$sql = "SELECT vary,lastvtime,flag FROM challenge WHERE uid = {$_SESSION['id']}";
	$challengarr = $_pm['mysql'] -> getOneRecord($sql);
	if($challengarr['flag'] != '1'){
		die('非法进入！');
	}
	if(empty($challengarr)){
	die('数据有误！');
	}
	$yes = date('Ymd',$challengarr['lastvtime']);
	$yes1 = date('Ymd',time()-24*3600);
	if($yes1 >= $yes){//刷新
		die('数据错误(2)！');
	}
	$_SESSION['multi_monsters_boss'.$_SESSION['id']] = $challengarr['vary'];
	//print_r($gw);exit;
}else if($_SESSION['multi_monsters'.$_SESSION['id']] == 3){
	//取怪
	$tgcheck = $_pm['mysql'] -> getOneRecord("SELECT tgt,tgttime FROM player_ext WHERE uid = {$_SESSION['id']}");
	if(!is_array($tgcheck)){
		header("Location:Team_Mod.php?n=126");
		//die('非法操作1！');
	}
	if($tgcheck['tgttime'] > 0){
		$yes = date('Ymd',$tgcheck['tgttime']);
		$yes1 = date('Ymd',time()-24*3600);
		if($yes1 < $yes){
			header("Location:Team_Mod.php?n=126");
			//die('非法操作2！');
		}
	}
	$sql = "SELECT id,gid,boss FROM tgt WHERE uid = {$_SESSION['id']} ORDER BY id DESC LIMIT 2";
	$gw1 = $_pm['mysql'] -> getRecords($sql);
	if(empty($gw1)){
		die('数据错误(3)！');
	}
	//$gw = $memgpc[$gw1[0]['gid']];
	$gw = getBaseGpcInfoById($gw1[0]['gid']);//改为单条取记录
	if($tgcheck['tgt'] == 30){//收取200水晶
		$tg31check = unserialize($_pm['mem']->get('tg31check_'.$_SESSION['id']));
		if($tg31check != 1 && (!isset($_GET['confirm31']) || $_GET['confirm31'] != 'yes')){
			die('<script language="javascript">if(confirm("继续31层，将收取200水晶，是否继续？")){
				window.setTimeout("window.parent.$(\"gw\").src=\"function/Fight_Mod.php?p='.$_GET['p'].'&confirm31=yes\"",1000);
			}else{
				window.location="/function/Team_Mod.php?n='.$mapcheck['inmap'].'";
			}</script>');
		}else{
			if($tg31check != 1){
				$_pm['mysql'] -> query('UPDATE player_ext SET sj = sj-200 WHERE uid = '.$_SESSION['id'].' AND sj >= 200');
				if(mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){
					die("<script language='javascript'>parent.Alert('水晶不够，扣取失败！！');".'window.location="/function/Team_Mod.php?n='.$mapcheck['inmap'].'";'."</script>");
				}else{
					$_pm['mem']->set(array("k"=>'tg31check_'.$_SESSION['id'],"v"=>1));
				}
			}
		}
	}else{
		$_pm['mem']->set(array("k"=>'tg31check_'.$_SESSION['id'],"v"=>0));
	}
	$_SESSION['multi_monsters_id_tgt_'.$_SESSION['id']] = $gw1[0]['id'];
	$_SESSION['multi_monsters_tgid_tgt_'.$_SESSION['id']] = $gw1[0]['boss'];
	$_SESSION['multi_monsters_next_tgt_'.$_SESSION['id']] = $gw1[1]['gid'];
	//$tg = $_pm['mysql'] -> getOneRecord("SELECT tgt FROM player_ext WHERE uid = {$_SESSION['id']}");
	$_SESSION['multi_monsters_boss_tgt_'.$_SESSION['id']] = $tgcheck['tgt'] + 1;
}else{
	//普通地图
	//判断是否是玛亚大陆保卫战
	$datew = date("w");
	$datehour = date("H:i");
	$maya = $_pm['mysql'] -> getOneRecord("SELECT id FROM timeconfig WHERE days = '$datew' AND starttime <= '$datehour' AND endtime >= '$datehour' AND titles='maya'");
	
	
	if($flagteam)//组队
	{
		if(is_array($maya)){
			$sql = "SELECT * FROM gpc WHERE level >=".$lvl[0]." and level <=".$lvl[1]." AND kx = 1";
			$gw = $_pm['mysql'] -> getRecords($sql);
		}else{
			$sql = "SELECT * FROM gpc WHERE level >=".$lvl[0]." and level <=".$lvl[1]." AND boss != 4 AND kx = 0";
			$gw = $_pm['mysql'] -> getRecords($sql);
		}
		if(empty($gw))
		{
			$sql= "SELECT * FROM gpc WHERE level >=".$lvl[0]." and level <=".$lvl[1]." AND boss != 4 AND boss != 3";
			$gw = $_pm['mysql'] -> getRecords($sql);
		}
		if(empty($gw)&&$theTeamFubenMap==false)
		{
			header('location:/function/Team_Mod.php?494&n='.$_SESSION['team_inmap'].'&s='.$sql);
			exit("数据库内没有怪物,请通知GM!") ;
			die("数据库内没有怪物,请通知GM!");
		}
		if($isleader){
			$getGw=false;
			if(!$teamState)
			{
				$getGw=true;
			}
			if(
				$teamState['team_fuben_card_step_num']==3&&!empty($teamState['monsters_tf_3'])
				&&empty($teamState['monsters'])
				&&!isset($_GET['team_auto'])
			)
			{
				$team->setTeamState(array(
									'monsters'=>array(),
									'monsters_bak'=>$teamState['monsters']
									));	
				//echo ' monsters set to empty'."\r\n";
				$teamState=$team->getTeamState();
			}
			if(
				empty($teamState['monsters'])
				&&
				empty($teamState['cur_monster'])
			)
			{
				
				$getGw=true;
			}
			if(!$getGw){
				$getGw=true;
				foreach($teamInfo['members'] as $v)
				{
					if(isset($v['living'])&&$v['living'])
					{
						$getGw=false;
						break;
					}
				}
			}

			if($getGw)
			{
				if($theTeamFubenMap!==false)
				{
					/*	
					alter table c_gpc add map_id smallint(5) null default 0,
		 			 				  add step_id smallint(5) null default 0,
									  add group_id smallint(5) null default 0;
					*/
					
					if(
						(
						($teamState['team_fuben_step'][0]+1==3&&empty($teamState['monsters_tf_3']))
						||
						$teamState['team_fuben_step'][0]+1>3
						)
						&&
						!(
							isset($_GET['team_auto'])&&$teamState['team_fuben_card_step_num']!=3
						)
					)
					{
						$teamState_team_fuben_step=0;
						$teamState_team_fuben_step1=0;
						$state['team_fuben_step']=array(0,0);
						$state['team_fuben_flag']=1;
						$team->setTeamState($state);
						$teamState=$team->getTeamState();
					}
					if($teamState['team_fuben_step'][1]<0) $teamState['team_fuben_step']=0;
					$teamState_team_fuben_step_arr=$teamState['team_fuben_step'];
					
					
					$teamState_team_fuben_step=$teamState_team_fuben_step_arr[0]+1;
					$teamState_team_fuben_step1=$teamState_team_fuben_step_arr[1]+1;
					
					//if()
					if(
						($teamState_team_fuben_step<3||empty($teamState['monsters_tf_3']))
						&&
						empty($teamState['monsters'])
						&&
						empty($teamState['cur_monster'])
						&&
						empty($teamState['multi_monsters_next'])						
					)
					{
						$sql='select gpc from c_gpc where map_id='.$theTeamFubenMap['id'].' and step_id='.$teamState_team_fuben_step.' and group_id='.$teamState_team_fuben_step1;

						$gw=$_pm['mysql']->getOneRecord($sql);
						if(!$gw)
						{
							die('没有数据，地图：'.$theTeamFubenMap['id'].'，第'.$teamState_team_fuben_step.'关，第'.$teamState_team_fuben_step1.'组地图设定！');
						}

						$gwstrs=explode(',',$gw['gpc']);
						$gws=array();
	
						foreach($gwstrs as $gid)
						{
							$tempGpcInfo = getBaseGpcInfoById($gid);//改为单条取记录
							if(isset($tempGpcInfo)) $gws[]=$tempGpcInfo;
						}
					}else{
						$gws = $teamState['monsters_tf_3'];

					}
					if(empty($gws)){
						die('数据为空！');
					}
				}
				else
				{
					$tmsNum=0;
					foreach($teamInfo['members'] as $v)
					{
						if($v['state']>0)
						{
							$tmsNum++;
						}
					}
					$gwNum=1+rand(1,intval($tmsNum*1.5));
					$gws=array();
					$gwct=count($gw);
					$connector = "";
					$monsterStr = "";
					$monsterJsStr = "
	mmonsters=[];
	";
					while($gwNum>0)
					{
						if(count($gw)==0)
						{
							break;
						}
						$rd=rand(0,$gwct-1);
						$_gw=$gw[$rd];
						if($_gw['boss'] == 3 ){		//&& bossCheck($_gw) === false		 不让遇到boss，遇到boss容易出问题		
							unset($gw[$rd]);
							$gwct=count($gw);
						}else{
							if(!empty($_gw)){
								$gws[]=$_gw;						
								$gwNum--;
							}
						}
					}
				}

				$team->fightStart($gws);
				$teamState=$team->getTeamState();
				
				$started=true;
				$gw=$gws[0];
				if(empty($gw))
				{
					header('location:/function/Team_Mod.php?546&n='.$_SESSION['team_inmap']);
					exit() ;
				}
			}else{//有宠物死了，别人的宠物接倒打
				//
				if(strpos($teamState['fight_html'],'<body')===false&&strlen($teamState['fight_html'])<100)
				{
					
					if(!isset($_SESSION['waitTeamTime']))
					{
						$_SESSION['waitTeamTime']=0;
					}else{
						$_SESSION['waitTeamTime']+=2;
					}
					$team->checkLost();
					if($_SESSION['waitTeamTime']>20)
					{						
						$_SESSION['waitTeamTime']=0;
						$oldData=$team->getTeamState();
						$dataNow=array();
						
						if(isset($oldData['team_fuben_flag']))
						{
							$dataNow['team_fuben_flag']=$oldData['team_fuben_flag'];			
						}
				
						if(isset($oldData['team_fuben_step']))
						{
							$dataNow['team_select_map']=$oldData['team_select_map'];
							$dataNow['autofighting']=$oldData['autofighting'];	
							$dataNow['team_fuben_step']=$oldData['team_fuben_step'];	
							$dataNow['team_fuben_boss']=$oldData['team_fuben_boss'];
							$dataNow['fubensjoj']=$oldData['fubensjoj'];
						}
				
						$_pm['mem']->setns('pm_team_fight_'.$_SESSION['team_id'],$dataNow);
						$team->clearTeamState();
						header('location:/function/Fight_Mod.php?'.($teamState['autofighting']?'team_auto=1':'').'&type=1');
						echo '有队员超时,重新开始战斗!'.($teamState['autofighting']?'team_auto=1':'');
						exit;
					}
					
					//header("refresh:2;url=".$_SERVER['REQUEST_URI']);
					echo '
					<script language="javascript">
					setTimeout("window.location=\''.$_SERVER['REQUEST_URI'].'\'",2000);
					</script>
					';
					
					if($_SESSION['waitTeamTime']>12)
					{
						echo '<br/><span style="font-size:12px" onclick="window.parent.$(\'gw\').src=\'./function/Fight_Mod.php?&type=1\';">有队员相应超时,点击这里,重新开始战斗!</font>';
					}
					exit();
					//$team->clearTeamState();
				}else{
					$_SESSION['waitTeamTime']=0;
					echo $teamState['fight_html'];
					die();
				}
			}
		}else{//轮到当前队员上阵
			if(
				(isset($teamState['monsters'])&&!empty($teamState['monsters']))
				||
				(isset($teamState['cur_monster'])&&!empty($teamState['cur_monster']))
				||
				!empty($teamState['monsters_last'])				
				)
			{
				if($teamState['monsters']['hp']>0)//继续打上一个其它队员没有打死的怪物
				{
					$_SESSION['fight'.$_SESSION['id']]=NULL;
					if(!empty($teamState['monsters'])){
						foreach($teamState['monsters'] as $k=>$v)//目的是取第一个键
							break;
						$teamState['monsters'][$k]['hp']=$teamState['cur_monster']['hp'];
						$teamState['monsters'][$k]['mp']=$teamState['cur_monster']['mp'];
						$gw=$teamState['monsters'][$k];
					}else{
						$teamState['monsters_last']['hp']=$teamState['cur_monster']['hp'];
						$teamState['monsters_last']['mp']=$teamState['cur_monster']['mp'];
						$gw=$teamState['monsters_last'];
					}
					$team->setTeamState($teamState);
				}else if(isset($teamState['cur_monster'])&&!empty($teamState['cur_monster'])){
					
					$teamState['monsters_last']['hp']=$teamState['cur_monster']['hp'];
					$teamState['monsters_last']['mp']=$teamState['cur_monster']['mp'];
					$team->setTeamState($teamState);					
					//$gw = $memgpc[$teamState['cur_monster']['gid']];
					$gw = getBaseGpcInfoById($gw1[0]['gid']);//改为单条取记录
					$gw['hp']=$teamState['cur_monster']['hp'];
				}else{
				//继续打下一个怪物,这种情况，不会出现？					
					//$gw=array_shift($teamState['monsters']);
					//echo 'fg $flagteam='.$flagteam.','.print_r($fight,1).','.__LINE__.'-'.$_SESSION['mbid'];
					$__gw=false;
					if(count($teamState['monsters'])>0)
					{
						foreach($teamState['monsters'] as $k=>$v)
						{
							if(!empty($v))
							{
								$__gw=$v;
								break;
							}else{
								unset($teamState['monsters'][$k]);
							}
						}
					}
				
					if($__gw)
					{
						$_SESSION['fight'.$_SESSION['id']]	= array(
									'uid'=>$_SESSION['id'],
									'bid'=>$_SESSION['mbid'],
									'gid'=>$__gw['id'],
									'hp' =>$__gw['hp'],
									'mp' =>$__gw['mp'],
									'fuzu'=>0,
									'fatting'=>1,
									'boss'=>$__gw['boss'],
									'ftime'=>time()-11
									);
						$_SESSION['gwcdie'.$_SESSION['id']]=$__gw['id'];
					}
					else exit('数据错误,请通知队长重新进入!');
					$team->setTeamState(array('monsters'=>$teamState['monsters']));
				}
			}else{
				//怪物数据不存在时非队员进入这里，跳转回去，等通知
				header('location:/function/Team_Mod.php?n='.$_SESSION['team_inmap']);
				echo '重新加载数据！';
				exit();
			}
		}
	}else{
		if(is_array($maya)){
			$sql = "SELECT * FROM gpc WHERE level = $idse AND kx = 1";
			$gw = $_pm['mysql'] -> getRecords($sql);
		}else{
			$sql = "SELECT * FROM gpc WHERE level = $idse AND boss != 4 AND kx != '1'";
			$gw = $_pm['mysql'] -> getRecords($sql);
		}
		if($_SESSION['multi_monsters'.$_SESSION['id']] == 2){
			if ((count($gw)==1)) $gw = $gw[0];
			else 
			{
				$min	= $gw[0];
				$n		= rand(1, count($gw));
				$gw		= $gw[$n-1];
			}		
			
			/*加入遇BOSS的时间限制。*/	
			
			while(($gw['boss'] == 3 && bossCheck($gw) === false) || !is_array($gw)){
				$idse = rand($lvl[0], $lvl[1]);
				$gw = $_pm['mysql'] -> getRecords("SELECT * FROM gpc WHERE level = $idse AND boss != 4 AND boss != 3 LIMIT 1");
				$gw = $gw[0];
			}
		}
	}
}
/*if($_SESSION['id'] == '281991'){
	echo $sql.'<br />';
	print_r($gw);
	echo '<br />';
}*/
if (
		(count($gw)<1 || $gw['boss'] == 4)
	&&
	(
		!$theTeamFubenMap
	)
)
{
	if($flagteam){
		if(!isset($_SESSION['waitTeamTime']))
		{
			$_SESSION['waitTeamTime']=0;
		}else{
			$_SESSION['waitTeamTime']+=2;
		}
		
		if($_SESSION['waitTeamTime']>20)
		{
			$_SESSION['waitTeamTime']=0;
			$oldData=$team->getTeamState();
			$dataNow=array();
			
			if(isset($oldData['team_fuben_flag']))
			{
				$dataNow['team_fuben_flag']=$oldData['team_fuben_flag'];			
			}
	
			if(isset($oldData['team_fuben_step']))
			{
				$dataNow['team_select_map']=$oldData['team_select_map'];
				$dataNow['team_fuben_step']=$oldData['team_fuben_step'];	
				$dataNow['autofighting']=$oldData['autofighting'];	
				$dataNow['team_fuben_boss']=$oldData['team_fuben_boss'];
				$dataNow['fubensjoj']=$oldData['fubensjoj'];
			}
	
			$_pm['mem']->setns('pm_team_fight_'.$_SESSION['team_id'],$dataNow);

			$team->clearTeamState();
			$mems=array();
			if(!empty($teaminfo['members'])){
				foreach($teaminfo['members'] as $row)
				{
					if($row['state']<1) $mems[]=$row['uid'];				
				}
			}
			$team->snotice('getTeamFightMod',$teaminfo['members'],$mems);
		}

	}
	header("refresh:2;url=Fight_Mod.php?p={$bid}");
	echo '1064';
	exit;
}
else
{
	
		
	$gw['wx'] = getWx($gw['wx']);
$_SESSION['gwcdie'.$_SESSION['id']] = $gw['id'];
	$gwinfo="['{$gw['name']}',{$gw['level']},'{$gw['wx']}',{$gw['ac']},{$gw['mc']},{$gw['hp']},{$gw['mp']},'{$gw['skill']}','{$gw['imgstand']}','{$gw['imgack']}','{$gw['imgdie']}',{$gw['id']}]";
	
	$test = $_SESSION['fight'.$_SESSION['id']];
	//Update fightting stats.
	if (!is_array($test))
	{		
		$_SESSION["fight".$_SESSION['id']]	= array('uid'=>$_SESSION['id'],
						'bid'=>$bid,
						'gid'=>$gw['id'],
						'hp' =>$gw['hp'],
						'mp' =>$gw['mp'],
						'fuzu'=>0,
						'fatting'=>1,
						'boss'=>$gw['boss'],
						'ftime'=>time());
						
	}
	else
	{
	   // Check time 
	   $will = (10-time()+$fight['ftime']);
	   if ($fight['ftime']+10 >= time()) {
	   	$end='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=gbk" />
</head>
<!--[if IE 6]><script type="text/javascript">try{ document.execCommand("BackgroundImageCache", false, true); } catch(e) {}
</script>
<![endif]-->
<body style="background-color: #FFFCEB;margin-top:0px;">
<center>
  <div style="margin-top:140px;"><img src="'.IMAGE_SRC_URL.'/ui/fight/loading.gif"/><div id="timev"  style="position:absolute; text-align:center; color:#F98F2C; font-weight:bold;font-size:2em;left: 390px; top: 160px; height: 40px;"></div>
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
		window.setTimeout("pause("+m+")",1000);
		return;
	}
	else{
		document.getElementById("timev").innerHTML = m--;
		readH=window.setTimeout("loadtime("+m+");", 1000);
	}
}
function pause(m)
{   if (pt==1) return;
	if(m == 0){
		window.parent.document.getElementById("gw").src="./function/Fight_Mod.php?p='.$_REQUEST['p'].'&s=t";	
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
		$_SESSION["fight".$_SESSION['id']]=$r;
	
	}
}
//$_SESSION["fight".$_SESSION['id']]=$fight;
$bbfzp = "";
$catcharr = "";

// Get bag props.
if (is_array($bag))
{  
	foreach ($bag as $k => $v)
	{
		if ($v['varyname'] == 1 && $v['sums']>0)
		{
			if (empty($bbfzp)) $bbfzp = "['".$v['name']."',".$v['sums'].','.$v['id']."]";
			else $bbfzp .= ",['".$v['name']."',".$v['sums'].','.$v['id']."]";
		}
		else if ($v['varyname'] == 3 && $v['sums']>0)
		{
			if (empty($catcharr)) $catcharr = "['".$v['name']."',".$v['sums'].','.$v['id']."]";
			else $catcharr .= ",['".$v['name']."',".$v['sums'].','.$v['id']."]";
		}
	}
	
}else $bbfzp='0';
//
$user['fightbb'] = $bid;
$_pm['mysql']->query("UPDATE player 
			   SET fightbb={$bid}
			 WHERE id={$_SESSION['id']}
		  ");
//update fight status to memory.
//$_pm['mem']->set(array('k' =>MEM_USER_KEY, 'v' => $user));
//$_pm['mem']->set(array('k' =>MEM_USERBB_KEY, 'v' => $userbb));
//$_pm['mem']->set(array('k' =>MEM_USERBAG_KEY, 'v' => $bag));

//###########################
// @Load template.
//###########################
if($flagteam){
	$teamState=$team->getTeamState();
	$mmonsterStr = $teamState['userliststr'].$teamState['monsterliststr'];
}

$fn='tpl_fight.html';
$tn = $_game['template'] . $fn;
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
	$_SESSION['fttime'.$_SESSION['id']] -= $arr['time']; 
	
		
	if($_SESSION['fttime'.$_SESSION['id']] < 0)
	{
		$_SESSION['fttime'.$_SESSION['id']] = 0;
	}
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
					 "#fttime#",
					 "#mmonster#",
					 "#bgtype#",
					 "#flash#"
					);
		$des = array(
					 $bbinfo,
					 $gwinfo,
					 $jnlist,
					 rand(1,3),
					 $bid,
					 $_SESSION['nickname'],
					 $bb['headimg'],
					 $bbfzp,
					 $catcharr,
					 $user['inmap'],
					 $mouse,
					 $_SESSION['fttime'.$_SESSION['id']],
					 $mmonsterStr,
					 $bgtype,
					 $flash
				);

	$fat = str_replace($src, $des, $tpl);
}

$backObj = array(); 
if($_REQUEST['from'] == 1)
{
	$backObj['bbInfo'] = array(
		"id"=>$bb['id'],
		"pet_name"=>iconv("gbk","utf-8",$bb['name']),
		"hp"=>$bb['srchp'],
		"mp"=>$bb['srcmp'],
		"nowexp"=>$bb['nowexp'],
		"lexp"=>$bb['lexp'],
		"lv"=>$bb['level'],
		"pet_id"=>str_replace(array("z",".gif"),array("",""),$bb['imgstand']),
		"name"=>iconv("gbk","utf-8",$user['nickname'])
	);
	$backObj['otherInfo'] = array(
		"id"=>$gw['id'],
		"name"=>iconv("gbk","utf-8",$gw['name']),
		"level"=>$gw['level'],
		"hp"=>$gw['hp'],
		"pet_id"=>str_replace(array("z",".gif"),array("",""),$gw['imgstand']),
		"wx"=>iconv("gbk","utf-8",$gw['wx']),
		'imgstand'=>$gw['imgstand']
	);
	if($gw['boss'] == 1)
	{
		$sql = "SELECT imgstand FROM bb WHERE name = '{$gw['name']}'";
		$res = $_pm['mysql']->getOneRecord($sql);
		if($res)
		{
			$backObj['otherInfo']['pet_id'] = str_replace(array("z",".gif"),array("",""),$res['imgstand']);
		}
	}
	echo "OK".json_encode($backObj);
	die();
}

// gzip echo. if maybe.
flush();
ob_start('ob_gzip');

if(isset($_SESSION['team_id'])){
	echo str_replace('<body>','<body><iframe src="/function/team.php?a3&checkOnly=1&rd=" style="position:absolute;z-index:0;top:1000px;" width="30" height="30"  class="wgframe"></iframe><script language="javascript">var a0;var teamautofight='.intval($teamState['autofight']).';window.parent.autoack='.intval($teamState['autofighting']).';var teamfightlock=false;var teamLeader='.intval($teamInfo['team']['creator']).';'.$teamFbstr.'</script>',$fat);
}else{
	echo str_replace('<body>','<script language="javascript">var a1;var teamfightlock="NONE";var teamLeader=0;'.$teamFbstr.'</script><body>',$fat);
}
if($flagteam){
	//组队保存数据和发送通知
	$team->setTeamState(array('cur_monster'=>$_SESSION["fight".$_SESSION['id']]));
	$team->setTeamState(array('fight_html'=>str_replace('<body>','<body><iframe src="/function/team.php?a4&checkOnly=1&rd=" style="position:absolute;z-index:0;top:1000px;" width="30" height="30"  class="wgframe"></iframe><script language="javascript">var a2;var curTeamTurnId='.$_SESSION['id'].';window.parent.autoack='.intval($teamState['autofighting']).';var teamautofight='.intval($teamState['autofight']).';if(curTeamTurnId==parent.myUid){var teamfightlock=false;}else{var teamfightlock=true;};var teamLeader='.intval($teamInfo['team']['creator']).';</script>',$fat)));	
	$exclude=array($_SESSION['id']);
	foreach($teamInfo['members'] as $row)
	{
		if($row['state']<1){
			$exclude[]=$row['uid'];
		}
	}
	//$s=$team->getTeamState();
	$team->snotice('getTeamFightMod',$teamInfo,$exclude);

	//die();
	sleep(1);//等待一秒，让所有人尽量同步播放
}
ob_end_flush();

$_pm['mem']->memClose();

function err($str)
{
	die('<center>
			<div style="margin-top:100px;padding:5px;font-size:12px; line-height:1.7;width:99%;height:100px;overflow:hidden;">'. $str .'<br/>
				<<<a href="javascript:history.go(-1);">返回村庄</a>
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
	$log='';
	if (!is_array($gs) || $gs['boss']!=3) return false;

	$exists = $_pm['mysql']->getOneRecord("SELECT id,rtime,gid,glock,dtime
										     FROM boss_refresh
											WHERE gid={$gs['id']}
											LIMIT 0,1
										 ");
	
	//$_pm['mysql']->query("SET autocommit=0");
	//$_pm['mysql']->query("START TRANSACTION");
	if (is_array($exists))
	{
		if (($exists['dtime']+1*3600)>=time() || 
			 ($exists['glock']==1 && ($exists['rtime']+120)>time())
		   ) return false;
		else if( ($exists['dtime']+1*3600)<time() && $exists['glock']==0)
		{
			$_pm['mysql']->query("UPDATE boss_refresh
								     SET rtime=".time().",fightuid={$_SESSION['id']},glock=1
								   WHERE gid={$gs['id']} and (dtime+3600)<".time()."
								");
			$log.="UPDATE boss_refresh
								     SET rtime=".time().",fightuid={$_SESSION['id']},glock=1
								   WHERE gid={$gs['id']} and (dtime+3600)<".time()."
								";
		}
	    else if($exists['glock']==1 && ($exists['rtime']+600)<time())
	    {
			$_pm['mysql']->query("UPDATE boss_refresh
									SET rtime=".time().",fightuid={$_SESSION['id']},glock=1
								  WHERE gid={$gs['id']} and glock=1 and (rtime+600)<".time()."
								");
			$log.="UPDATE boss_refresh
									SET rtime=".time().",fightuid={$_SESSION['id']},glock=1
								  WHERE gid={$gs['id']} and glock=1 and (rtime+600)<".time()."
								";
	    }
		else return false;
		$trs = $_pm['mysql']->getOneRecord("SELECT id
											 FROM boss_refresh
											WHERE gid={$gs['id']} and fightuid={$_SESSION['id']}
											LIMIT 0,1
										 ");
		if (!is_array($trs)) return false;
	}
	else // CREATE boss refresh record log.
	{
		$_pm['mysql']->query("INSERT INTO boss_refresh(gid,rtime,fightuid,glock)
							  VALUES({$gs['id']},".time().",{$_SESSION['id']},1)
							");
	}
	if (!$_pm['mysql']->query("COMMIT")){
			$_pm['mysql']->query("ROLLBACK");
			return false;
		}
    $log = addslashes($log);
	$task = new task();
	$task->saveGword("遇上了沉睡中的[".$gs['name']."]，勇士请赶快去消灭它吧！");
	$_pm['mysql']->query("INSERT INTO gamelog(ptime,seller,buyer,pnote,vary) VALUES(unix_timestamp(),{$_SESSION['id']},{$_SESSION['id']},'{$log}',3)");
	return true;
}

/*function getgpc($level1,$level2){
	global $_pm;
	$memgpc = unserialize($_pm['mem'] -> get('db_gpcid'));
	if(!is_array($memgpc)){
		$memgpc = unserialize($_pm['mem'] -> get('db_gpcid'));
	}
	if(!is_array($memgpc)){
		return false;
	}
	foreach($memgpc as $k => $v){
		if($v['boss'] == 4 || $v['level'] < $level1 || $v['level'] > $level2 ){
			continue;
		}
		$gpc[$k] = $v;
	}
	if(!is_array($gpc)){
		return false;
	}
}*/
?>