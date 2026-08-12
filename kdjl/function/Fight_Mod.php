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
require_once(dirname(__FILE__).'/fight_wait_common.php');
require_once(dirname(__FILE__).'/boss_refresh_common.php');
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1)
{
	die('');
}
$_SESSION['id'] = $uid;
$requestFrom = (isset($_REQUEST['from']) && !is_array($_REQUEST['from'])) ? intval($_REQUEST['from']) : 0;
$requestUri = (isset($_SERVER['REQUEST_URI']) && !is_array($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '/function/Fight_Mod.php';
if($requestUri === '' || substr($requestUri, 0, 1) !== '/' || preg_match('/["\'\r\n]/', $requestUri)) $requestUri = '/function/Fight_Mod.php';

function fightModJsSingle($value)
{
	return str_replace(array('\\', "'", "\r", "\n", '<', '>'), array('\\\\', "\\'", '', '', '\\x3C', '\\x3E'), strval($value));
}

function fightModImage($value)
{
	$value = basename(strval($value));
	return preg_match('/^[A-Za-z0-9_.-]+$/', $value) ? $value : '';
}

//因为不同难度得组队副本，是不同得地图，这个三个地图看做一个图，无法计算知道哪三个是一起得，只能写死
//注意fight_mod.php和team_mod.php里面都有这个数组
$__teamFubenMap=array(
	'128'=>128,
	'129'=>128,
	'130'=>128
);
require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
$s=new socketmsg();
$teamId = isset($_SESSION['team_id']) ? intval($_SESSION['team_id']) : 0;
$team=new team($teamId,$s);
$myState=$team->checkMyTeam();
$flagteam=false;
$isleader=false;
$teamInfo=array();

if(isset($_SESSION['gs']) && intval($_SESSION['gs']) == 3 && $teamId > 0 && $team->isTeamLeader($uid, $teamId))
{
	$tarotTeam = $_pm['mysql']->getOneRecord('SELECT id FROM team WHERE id='.$teamId.' AND inmap IN (128,129,130) AND creator='.$uid);
	if(is_array($tarotTeam) && isset($tarotTeam['id']))
	{
		$disbandResult = $team->disbandTeam(false);
		if($disbandResult !== true) die($disbandResult);
		unset($_SESSION['gs'], $_SESSION['gs_status']);
		header('location:/function/Team_Mod.php?n=128');
		exit;
	}
}

//$memmapid = unserialize($_pm['mem']->get('db_mapid'));


$sql = "select inmap from player where id=".$uid;
$mapcheck = $_pm['mysql'] -> getOneRecord($sql);
if(!is_array($mapcheck) || !isset($mapcheck['inmap']))
{
	die('');
}
$inmap = intval($mapcheck['inmap']);
$mapcheck['inmap'] = $inmap;
$theTeamFubenMap=false;
$teamFbstr='var intfbFlag=false;';
//将取多条数据改为取单条数据
$baseMapInfo =  getBaseMapInfoById($inmap);
if(!is_array($baseMapInfo))
{
	die('地图配置错误！');
}
if(!isset($baseMapInfo['multi_monsters'])) $baseMapInfo['multi_monsters'] = 0;
if(!isset($baseMapInfo['level']) || $baseMapInfo['level'] === '') $baseMapInfo['level'] = '0,0';
if(!isset($baseMapInfo['needs'])) $baseMapInfo['needs'] = '';
if(!isset($baseMapInfo['czlprops'])) $baseMapInfo['czlprops'] = 0;
$memmapid[$inmap] = $baseMapInfo;
$teamFubenInmap = isset($__teamFubenMap[$inmap]) ? intval($__teamFubenMap[$inmap]) : $inmap;
$allowSoloTeamDungeon = kdjlIsSoloTeamDungeonMap($inmap);
if($baseMapInfo['multi_monsters']==3 && $allowSoloTeamDungeon && $teamId<1)
{
	$soloTeam = kdjlEnsureSoloTeamDungeonTeam($uid, $inmap, $team, $s);
	if(!is_array($soloTeam) || empty($soloTeam['team_id']) || !isset($soloTeam['team']))
	{
		die('<script language="javascript">
parent.Alert("单人副本队伍创建失败，请稍后重试！");
window.location="/function/Team_Mod.php?n=128";
</script>');
	}
	$teamId = intval($soloTeam['team_id']);
	$team = $soloTeam['team'];
	$myState = 1;
}
if(!isset($_SESSION['team_inmap'])) $_SESSION['team_inmap'] = $inmap;
$_SESSION['team_inmap'] = intval($_SESSION['team_inmap']);

$chaoshenchongDituFlag=false;
if($memmapid[$inmap]['multi_monsters']==4)
{
	$chaoshenchongDituFlag=true;
}

if($memmapid[$inmap]['multi_monsters']==3&&$teamId<1)
{
	die('<script language="javascript">
parent.recvMsg("SM|<font color=\'#442266\'>此地图，必须组队才能战斗!</font>");
window.history.back();
</script>');
}
else if($memmapid[$inmap]['multi_monsters']==3&&$teamId>0)
{
	//
	$_pm['mem']->del('tarot_info1_'.$teamId);
	$teamInfo=$team->getTeamInfo();
	if(!isset($teamInfo['members']) || !is_array($teamInfo['members'])) $teamInfo['members'] = array();
	$ct=0;
	$leaderCzl=0;
	$limitSetting=explode(',',$memmapid[$inmap]['level']);
	$minLevel=isset($limitSetting[0]) ? intval($limitSetting[0]) : 0;
	$czlDiff=isset($limitSetting[1]) ? intval($limitSetting[1]) : 0;

	$needRow=$_pm['mysql']->getOneRecord('select uid from fuben where uid='.$uid.' and inmap ='.$teamFubenInmap.' and left(lttime,8)="'.date('Ymd').'"');
	$sj=0;
	if(preg_match('/(?:^|,)sj\:(\d+)/',$memmapid[$inmap]['needs'],$sjMatch))
	{
		$sj=intval($sjMatch[1]);
	}
	$memberNeedSjFlag=false;
	$teamState=$team->getTeamState();
	foreach($teamInfo['members'] as $__k=>$mem)
	{
		if(isset($mem['state']) && intval($mem['state'])==1)
		{
			$memberUid = isset($mem['uid']) ? intval($mem['uid']) : 0;
			if($memberUid < 1) continue;
			$csql='select b.level,b.czl,fb.uid,fb.id fbid,p.nickname,fb.lttime from userbb b,player p left join fuben fb on fb.uid=p.id and fb.inmap='.$teamFubenInmap.' and left(fb.lttime,8)="'.date('Ymd').'" where p.mbid=b.id and p.id='.$memberUid;
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
			$userbbNicknameJs = addcslashes((string)$userbb['nickname'], "\\\"\n\r");

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
parent.Alert("<font color=\'#442266\'>队员('.$userbbNicknameJs.')主战宠物成长率和队长相比差距大于'.$czlDiff.'!</font>");
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
					$exclude=array($uid);
					foreach($teamInfo['members'] as $row)
					{
						if($row['state']<1){
							$exclude[]=isset($row['uid']) ? intval($row['uid']) : 0;
						}
					}
					//$s=$team->getTeamState();
					$sr=$team->snotice(
					kdjlSafeIconv('gbk','utf-8','_AL_队伍中'.$userbb['nickname'].' 今日已经用完免费次数，如若继续进行战斗，需要扣除队长'.$sj.'水晶!'),$teamInfo,$exclude
					);

					die('<script language="javascript">
parent.Alert("<font color=\'#ffffff\'>队伍中'.$userbbNicknameJs.' 今日已经用完免费次数，如若继续进行战斗，需要扣除队长'.$sj.'水晶!</font><br/><span style=\'cursor:pointer\' onclick=\'$(\\"gw\\").contentWindow.location=\\"/function/Fight_Mod.php?oksj\\"\'><font color=\'#ff0000\'><strong>点击这里继续,将会扣除队长水晶!</strong></font></span>");
window.location="/function/Team_Mod.php?n='.$_SESSION['team_inmap'].'";
</script>');
				}
			}

			if(substr($userbb['lttime'],8)>=10&&(!isset($teamState['fubensjoj'])||!$teamState['fubensjoj']))
			{
				die('<script language="javascript">
parent.Alert("<font color=\'#ffffff\'>队员 '.$userbbNicknameJs.' 进入副本次数已经达到最大限度了!</font>");
window.location="/function/Team_Mod.php?n='.$_SESSION['team_inmap'].'";
</script>');
			}

			$ct++;
		}
	}

	if($ct<1)
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
			window.location="'.$requestUri.(strpos($requestUri,'?')===false?'?oksj':'&oksj').'";
		}else{
			window.location="/function/Team_Mod.php";
		}
		</script>');
	}else if(($needRow||$memberNeedSjFlag)&&isset($_GET['oksj'])&&(!isset($teamState['fubensjoj'])||!$teamState['fubensjoj'])){
		$teamEntryResult = kdjlStartTeamDungeonEntry($uid, $teamId, $team, $teamFubenInmap, $sj, true);
		if($teamEntryResult === 0){
			die('
		<script language="javascript">
			alert("对不起，您的水晶不够支付！");
			window.location="/function/Team_Mod.php";
		</script>
');
		}
		if($teamEntryResult === 2) die('有队员进入副本次数已经达到最大限度了！');
		if($teamEntryResult !== 1) die('副本次数保存失败，请稍后再试。');
	}else if($needRow&&(!isset($teamState['fubensjoj'])||!$teamState['fubensjoj'])){
		header('location:/function/Team_Mod.php');
		exit;
	}else if(!$needRow){
		//免费机会只有一次
		$teamEntryResult = kdjlStartTeamDungeonEntry($uid, $teamId, $team, $teamFubenInmap, 0, false);
		if($teamEntryResult === 2) die('有队员进入副本次数已经达到最大限度了！');
		if($teamEntryResult !== 1) die('副本次数保存失败，请稍后再试。');
	}
	$teamState=$team->getTeamState();
	if(!isset($teamState['team_fuben_step']) || !is_array($teamState['team_fuben_step']) || !isset($teamState['team_fuben_step'][0]) || !isset($teamState['team_fuben_step'][1]))
	{
		$teamState['team_fuben_step']=array(0,0);
		$teamState['team_fuben_flag']=1;
		$team->setTeamState(array('team_fuben_step'=>$teamState['team_fuben_step'],'team_fuben_flag'=>1));
	}

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
		$_SESSION['gs_status'] = "lock";
		header('location:/function/tarot_Mod.php');
		die('最后一关队长翻牌！');
	}

	if(isset($teamState['team_select_map'])&&$teamState['team_select_map']>0)
	{
		$teamSelectMap = intval($teamState['team_select_map']);
		if(!isset($memmapid[$teamSelectMap]))
		{
			$selectMapInfo = getBaseMapInfoById($teamSelectMap);
			if(is_array($selectMapInfo))
			{
				if(!isset($selectMapInfo['multi_monsters'])) $selectMapInfo['multi_monsters'] = 0;
				if(!isset($selectMapInfo['level']) || $selectMapInfo['level'] === '') $selectMapInfo['level'] = '0,0';
				if(!isset($selectMapInfo['needs'])) $selectMapInfo['needs'] = '';
				if(!isset($selectMapInfo['czlprops'])) $selectMapInfo['czlprops'] = 0;
				$memmapid[$teamSelectMap] = $selectMapInfo;
			}
		}
		if(
			isset($memmapid[$teamSelectMap])&&$memmapid[$teamSelectMap]['multi_monsters']==3
			&&
			$teamSelectMap+3>$inmap&&$teamSelectMap-3<$inmap
		)
		{
			$_pm['mysql']->query('update player set inmap='.$teamSelectMap.' where id='.$uid);
			$_SESSION['team_inmap']=$teamSelectMap;
			$mapcheck['inmap']=$teamSelectMap;
			$inmap=$teamSelectMap;
			$teamFubenInmap = isset($__teamFubenMap[$inmap]) ? intval($__teamFubenMap[$inmap]) : $inmap;
		}
	}
	$state=array();
	if(!isset($teamState['team_fuben_step'])||!is_array($teamState['team_fuben_step'])){
		$state['team_fuben_step']=array(0,0);
		$state['team_fuben_flag']=1;
		$team->setTeamState($state);
	}else{
		$state['team_fuben_flag']=1;
		$team->setTeamState($state);
	}
	$theTeamFubenMap=$memmapid[$inmap];
	$teamFbstr='var intfbFlag=true;';


}else if($teamId>0){
	$state['team_fuben_flag']=0;
	$team->setTeamState($state);
}

//$waittimestr='';
if($teamId>0)
{
	$isleader=$team->isTeamLeader($uid,$teamId);
	if($isleader&&isset($_GET['team_auto']))
	{
		$data=array();
		$teamAuto = (isset($_GET['team_auto']) && !is_array($_GET['team_auto'])) ? intval($_GET['team_auto']) : 0;
		$data['autofighting'] = $teamAuto > 0 ? 1 : 0;

		$oldData=$team->getTeamState();
		$dataNow=array();

		if(isset($oldData['team_fuben_flag']))
		{
			$dataNow['team_fuben_flag']=$oldData['team_fuben_flag'];
		}

		if(isset($oldData['team_fuben_step']))
		{
			foreach(array('team_select_map','autofighting','team_fuben_boss','team_fuben_step','fubensjoj') as $oldKey)
			{
				if(isset($oldData[$oldKey])) $dataNow[$oldKey]=$oldData[$oldKey];
			}
			if(isset($oldData['team_fuben_card_step_num']) && $oldData['team_fuben_card_step_num']==3)
			{
				if(isset($oldData['monsters_bak'])) $dataNow['monsters']=$oldData['monsters_bak'];
				if(isset($oldData['cur_monster'])) $dataNow['cur_monster']=$oldData['cur_monster'];
				$dataNow['team_fuben_card_step_num']=$oldData['team_fuben_card_step_num'];
				$dataNow['team_fuben_step']=$oldData['team_fuben_step'];
				if(isset($oldData['autofight'])) $dataNow['autofight']=$oldData['autofight'];
				if(isset($oldData['fight_html'])) $dataNow['fight_html']=$oldData['fight_html'];
			}
		}

		$_pm['mem']->setns('pm_team_fight_'.$teamId,$dataNow);

		if(isset($_GET['setteamauto'])&&(!isset($oldData['team_fuben_card_step_num']) || $oldData['team_fuben_card_step_num']!=3)){
			$team->clearTeamState();
		}
		$team->setTeamState($data);
		$_SESSION['fight'.$_SESSION['id']]['ftime']=0;
	}

	$teamState=$team->getTeamState();

	if(isset($teamState['team_fuben_step']))
	{
		$teamInmap = isset($_SESSION['team_inmap']) ? intval($_SESSION['team_inmap']) : $inmap;
		if(!isset($memmapid[$teamInmap]))
		{
			$teamMapInfo = getBaseMapInfoById($teamInmap);
			if(is_array($teamMapInfo))
			{
				if(!isset($teamMapInfo['multi_monsters'])) $teamMapInfo['multi_monsters'] = 0;
				if(!isset($teamMapInfo['level']) || $teamMapInfo['level'] === '') $teamMapInfo['level'] = '0,0';
				if(!isset($teamMapInfo['needs'])) $teamMapInfo['needs'] = '';
				if(!isset($teamMapInfo['czlprops'])) $teamMapInfo['czlprops'] = 0;
				$memmapid[$teamInmap] = $teamMapInfo;
			}
		}
		$theTeamFubenMap=isset($memmapid[$teamInmap]) ? $memmapid[$teamInmap] : false;
		if($theTeamFubenMap===false || !isset($theTeamFubenMap['multi_monsters']) || $theTeamFubenMap['multi_monsters']!=3)//不是组队副本地图
		{
			$theTeamFubenMap=false;
			$team->clearTeamFubenData();
		}
	}

	$teamInfo=$team->getTeamInfo();
	if(!isset($teamInfo['members']) || !is_array($teamInfo['members'])) $teamInfo['members'] = array();
	$isMyTurn=false;
	$ct=0;
	foreach($teamInfo['members'] as $mem)
	{
		$memberState = isset($mem['state']) ? intval($mem['state']) : 0;
		if($memberState==1)
		{
			$ct++;
		}
		else if($theTeamFubenMap!==false&&$memberState<1&&isset($mem['uid']))//组队副本踢掉所有没有归队的人
		{
			if($team->kickMember(intval($mem['uid']),true)===-1) exit('<script>window.location="/function/City_Mod.php";</script>');
		}
	}

	if($ct<1)
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
			header("refresh:2;url=".$requestUri);
			exit('稍等……');
		}else{
			foreach($teamInfo['members'] as $amem)
			{
				if(isset($amem['living'],$amem['state']) && $amem['living']==1&&$amem['state']==1)
				{
					if(isset($amem['uid']) && intval($amem['uid'])==$uid)
					{
						$isMyTurn=true;
					}
					break;
				}
			}

			if(!$isMyTurn){
				if(strpos($teamState['fight_html'],'<body')===false&&strlen($teamState['fight_html'])<100)
				{
					if($team->checkLost()===-1) exit('<script>window.location="/function/City_Mod.php";</script>');
					header("refresh:2;url=".$requestUri);
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
		if($team->checkLost()===-1) exit('<script>window.location="/function/City_Mod.php";</script>');
		$flagteam=true;
	}
}

if($requestFrom != 1)
{
	secStart($_pm['mem']);
}
header("Cache-Control: no-cache, must-revalidate");
header("Pragma:no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

define('MEM_BOSS_KEY', $_SESSION['id'] . 'boss');
define('MEM_FIGHT_KEY', $_SESSION['id'] . 'fight'); // 保存战斗信息。
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


$user	= $_pm['user']->getUserById($uid);
if(!is_array($user))
{
	die('');
}
foreach(array('mapinfo'=>'','openmap'=>'','inmap'=>0,'mbid'=>0,'autofitflag'=>0,'maxautofitsum'=>0,'sysautosum'=>0) as $fightUserKey=>$fightUserDefault)
{
	if(!isset($user[$fightUserKey])) $user[$fightUserKey] = $fightUserDefault;
}

//$memgpc = unserialize($_pm['mem'] -> get('db_gpcid'));
$userbb = $_pm['user']->getUserPetById($uid);
$bag    = $_pm['user']->getUserBagById($uid);
$fight	=	isset($_SESSION['fight'.$uid]) ? $_SESSION['fight'.$uid] : false;
$_SESSION['fttime'.$uid] = 5;
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

$openmap = explode(",", isset($user['openmap']) ? $user['openmap'] : '');
$requestMapId = (isset($_REQUEST['n']) && !is_array($_REQUEST['n'])) ? intval($_REQUEST['n']) : 0;
if(
	$requestMapId > 0
	&& !in_array($requestMapId,$openmap)
	&& $requestMapId != 125 && $requestMapId != 15
	&& $requestMapId != 19  && $requestMapId != 126
	&& $requestMapId != 20 && $requestMapId != 128
	&& $requestMapId != 18 && $requestMapId != 17
	&& $requestMapId != 101 && $requestMapId != 102
	&& $requestMapId != 104 && $requestMapId != 105
	&& $requestMapId != 107 && $requestMapId != 108
	&& $requestMapId != 110 && $requestMapId != 111
	&& $requestMapId != 113 && $requestMapId != 114
	&& $requestMapId != 116 && $requestMapId != 117
	&& $requestMapId != 119 && $requestMapId != 120
	&& $requestMapId != 122 && $requestMapId != 123
	&& $requestMapId != 129 && $requestMapId != 130
	&& $requestMapId != 132 && $requestMapId != 133
	&& $requestMapId != 135 && $requestMapId != 136
	&& $requestMapId != 138 && $requestMapId != 139
	&& $requestMapId != 141 && $requestMapId != 142
	&& $requestMapId != 143 && $requestMapId != 144
	&& $requestMapId != 145 && $requestMapId != 146
	&& $requestMapId != 147 && $requestMapId != 148
	&& $requestMapId != 149 && $requestMapId != 150
)
{
	$_pm['mysql']->query('update player set inmap=0 where id='.$uid);
	die("地图开放时间到期，或者地图未开启(".$requestMapId.")！");
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
$user['inmap'] = intval($user['inmap']);
if(!isset($memmapid[$user['inmap']]))
{
	$userMapInfo = getBaseMapInfoById($user['inmap']);
	if(!is_array($userMapInfo))
	{
		die('地图配置错误！');
	}
	if(!isset($userMapInfo['multi_monsters'])) $userMapInfo['multi_monsters'] = 0;
	if(!isset($userMapInfo['level']) || $userMapInfo['level'] === '') $userMapInfo['level'] = '0,0';
	if(!isset($userMapInfo['needs'])) $userMapInfo['needs'] = '';
	if(!isset($userMapInfo['czlprops'])) $userMapInfo['czlprops'] = 0;
	$memmapid[$user['inmap']] = $userMapInfo;
}
if(strpos($memmapid[$user['inmap']]['needs'],'needprops:') !== false){
	$pcheck = explode('needprops:',$memmapid[$user['inmap']]['needs']);
	$needPid = isset($pcheck[1]) ? intval($pcheck[1]) : 0;
	$pa = $needPid > 0 ? $_pm['mysql'] -> getOneRecord("SELECT SUM(sums) AS sums FROM userbag WHERE uid = ".$uid." AND pid = {$needPid} AND sums > 0 AND zbing=0 AND (cantrade IS NULL OR cantrade<>3)") : false;
	$needSums = is_array($pa) && isset($pa['sums']) ? intval($pa['sums']) : 0;
	if($needSums < 1){
		die("<script>window.parent.Alert('您背包中没有必须道具！');window.parent.document.getElementById('gw').src='function/Team_Mod.php?n=".$user['inmap']."';</script>");
	}
}
//加入进入地图需要物品才能进入的功能在此结束
$maparr = $memmapid[$user['inmap']];
if(!isset($maparr['multi_monsters'])) $maparr['multi_monsters'] = 0;
if($maparr['multi_monsters'] == 1){
	$_SESSION['multi_monsters'.$uid] = 1;//挑战地图
}else if($maparr['multi_monsters'] == 2){//通关塔
	$_SESSION['multi_monsters'.$uid] = 3;
}else{
	$_SESSION['multi_monsters'.$uid] = 2;//普通地图
}
$arr = $_pm['mysql'] -> getOneRecord('SELECT img FROM map WHERE id = '.$user['inmap']);
$bgtype = is_array($arr) && isset($arr['img']) ? $arr['img'] : '';
$flash = '';
$waitBid = (isset($_REQUEST['p']) && !is_array($_REQUEST['p'])) ? intval($_REQUEST['p']) : 0;
if ($requestFrom == 1) {
	$waitBid = intval($user['mbid']);
}
if ($waitBid <= 0 && is_array($fight) && isset($fight['bid'])) {
	$waitBid = intval($fight['bid']);
}
if ($waitBid <= 0) {
	$waitBid = intval($user['mbid']);
}

//#########################
if (!empty($fight) && is_array($fight))
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
	   $will = kdjlFightEntryWaitRemaining($fight, $user, true, $waitBid, '');
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
		window.parent.document.getElementById("gw").src="./function/Fight_Mod.php?p='.$waitBid.'&s=t";
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
if($requestFrom == 1)
{
	$bid = $user['mbid'];
}
else
{
	$bid = $waitBid;
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
	$arrHp = max(0, intval(round(isset($arr['hp']) ? $arr['hp'] : 0)));
	$arrMp = max(0, intval(round(isset($arr['mp']) ? $arr['mp'] : 0)));
	$currentAddHp = max(0, min($arrHp, intval(isset($bb['addhp']) ? $bb['addhp'] : 0)));
	$currentAddMp = max(0, min($arrMp, intval(isset($bb['addmp']) ? $bb['addmp'] : 0)));
	$bb['srchp'] += $arrHp;
	$bb['srcmp'] += $arrMp;
	$bb['hp'] += $currentAddHp;
	$bb['mp'] += $currentAddMp;
	/*$sql = "SELECT addmp,addhp FROM userbb WHERE uid = {$_SESSION['id']} and id = {$bid}";
	$add = $_pm['mysql'] -> getOneRecord($sql);
	$bb['srchp'] += $add['addhp'];
	$bb['srcmp'] += $add['addmp'];*/
   // ================================ 装备效果结束 ========================================

	//if ($bb['hp'] <= 0) err($_bbword[rand(0,count($_bbword)-1)]);



	$autoTypeKey = 'exptype'.$uid;
	$autoWayKey = 'way'.$uid;
	$autoMultiKey = 'multi_monsters'.$uid;
	$autoType = isset($_SESSION[$autoTypeKey]) ? intval($_SESSION[$autoTypeKey]) : 0;
	$autoWay = isset($_SESSION[$autoWayKey]) ? $_SESSION[$autoWayKey] : '';
	$autoMultiMode = isset($_SESSION[$autoMultiKey]) ? intval($_SESSION[$autoMultiKey]) : 2;
	$attackWaitLimit = kdjlFightAttackWaitLimit($user, true, $fight, '');
	if($autoType == 1 && $autoMultiMode == 2 &&
		((($autoWay == '' || $autoWay == 'money') && $user['autofitflag']==1 && $user['sysautosum']>0) ||
		 ($autoWay == 'yb' && $user['autofitflag']==1 && $user['maxautofitsum']>0)))
	{
		$_SESSION['fttime'.$uid] = $attackWaitLimit;
	}
	if(kdjlFightNeedsPetRestore($fight))
	{
		if(!kdjlFightRestorePet($uid, $bid, $arrHp, $arrMp)) die('保存战斗宠物状态失败！');
		$bb['hp'] = $bb['srchp'];
		$bb['mp'] = $bb['srcmp'];
	}

	// By field order.
	$bb['wx'] = getWx($bb['wx']);
	$bbNameJs = fightModJsSingle($bb['name']);
	$bbWxJs = fightModJsSingle($bb['wx']);
	$bbSkillJs = fightModJsSingle($bb['skillist']);
	$bbImgStand = fightModImage($bb['imgstand']);
	$bbImgAck = fightModImage($bb['imgack']);
	$bbImgDie = fightModImage($bb['imgdie']);
	$bbinfo = "['{$bbNameJs}',{$bb['level']},'{$bbWxJs}',{$bb['ac']},{$bb['mc']},{$bb['hp']},{$bb['mp']},'{$bbSkillJs}','{$bbImgStand}','{$bbImgAck}','{$bbImgDie}',{$bid},'{$bb['srchp']}','{$bb['srcmp']}','{$bb['nowexp']}','{$bb['lexp']}']";
}

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
	for($i = 1; $i < 5; $i++){
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
	echo '战斗技能数据加载失败，正在重试。';
	exit;
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
		$skillNameJs = fightModJsSingle($rs['name']);
		$skillValueJs = fightModJsSingle($rs['value']);
		$skillPlusJs = fightModJsSingle($rs['plus']);
		$skillImg = fightModImage($rs['img']);
		$jnlist .="['{$skillNameJs}',{$rs['level']},'{$rs['vary']}',{$rs['wx']},'{$skillValueJs}','{$skillPlusJs}','{$skillImg}',{$rs['uhp']},{$rs['ump']},{$rs['sid']}],";
	}
}
$jnlist = rtrim($jnlist, ','); // []#[];
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
$lvlMin = isset($lvl[0]) ? intval($lvl[0]) : 1;
$lvlMax = isset($lvl[1]) ? intval($lvl[1]) : $lvlMin;
if($lvlMin < 1) $lvlMin = 1;
if($lvlMax < $lvlMin) $lvlMax = $lvlMin;

$idse = rand($lvlMin, $lvlMax); // <<<<<<<


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
	$nextChallengeGid = (isset($gw1[1]) && is_array($gw1[1]) && isset($gw1[1]['gid'])) ? intval($gw1[1]['gid']) : 0;
	//$gw = $memgpc[$gw1[0]['gid']];
	$gw = getBaseGpcInfoById($gw1[0]['gid']);//改为单条取记录
	$_SESSION['multi_monsters_id'.$_SESSION['id']] = $gw1[0]['id'];
	$_SESSION['multi_monsters_next'.$_SESSION['id']] = $nextChallengeGid;
	$sql = "SELECT vary,lastvtime,flag FROM challenge WHERE uid = {$_SESSION['id']}";
	$challengarr = $_pm['mysql'] -> getOneRecord($sql);
	if(empty($challengarr)){
		die('数据有误！');
	}
	if($challengarr['flag'] != '1'){
		die('非法进入！');
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
		exit;
		//die('非法操作1！');
	}
	if($tgcheck['tgttime'] > 0){
		$yes = date('Ymd',$tgcheck['tgttime']);
		$yes1 = date('Ymd',time()-24*3600);
		if($yes1 < $yes){
			header("Location:Team_Mod.php?n=126");
			exit;
			//die('非法操作2！');
		}
	}
	$sql = "SELECT id,gid,boss FROM tgt WHERE uid = {$_SESSION['id']} ORDER BY id DESC LIMIT 2";
	$gw1 = $_pm['mysql'] -> getRecords($sql);
	if(empty($gw1)){
		die('大神你已经通关了今天的所有关卡，请明天再来挑战吧！！！');
	}
	$nextTgtGid = (isset($gw1[1]) && is_array($gw1[1]) && isset($gw1[1]['gid'])) ? intval($gw1[1]['gid']) : 0;
	//$gw = $memgpc[$gw1[0]['gid']];
	$gw = getBaseGpcInfoById($gw1[0]['gid']);//改为单条取记录
	if($tgcheck['tgt'] == 30){//收取200水晶
		$tg31check = kdjlFightWaitCacheValue($_pm['mem']->get('tg31check_'.$_SESSION['id']), 0);
		if($tg31check != 1 && (!isset($_GET['confirm31']) || is_array($_GET['confirm31']) || $_GET['confirm31'] != 'yes')){
			die('<script language="javascript">if(confirm("继续31层，将收取200水晶，是否继续？")){
				window.setTimeout("window.parent.$(\"gw\").src=\"function/Fight_Mod.php?p='.$waitBid.'&confirm31=yes\"",1000);
			}else{
				window.location="/function/Team_Mod.php?n='.$mapcheck['inmap'].'";
			}</script>');
		}else{
			if($tg31check != 1 && !kdjlEnsureTgt31CrystalPaid($uid)){
				die("<script language='javascript'>parent.Alert('水晶不够，扣取失败！！');".'window.location="/function/Team_Mod.php?n='.$mapcheck['inmap'].'";'."</script>");
			}
		}
	}else{
		$_pm['mem']->set(array("k"=>'tg31check_'.$_SESSION['id'],"v"=>0));
	}
	$_SESSION['multi_monsters_id_tgt_'.$_SESSION['id']] = $gw1[0]['id'];
	$_SESSION['multi_monsters_tgid_tgt_'.$_SESSION['id']] = $gw1[0]['boss'];
	$_SESSION['multi_monsters_next_tgt_'.$_SESSION['id']] = $nextTgtGid;
	//$tg = $_pm['mysql'] -> getOneRecord("SELECT tgt FROM player_ext WHERE uid = {$_SESSION['id']}");
	$_SESSION['multi_monsters_boss_tgt_'.$_SESSION['id']] = $tgcheck['tgt'] + 1;
}else{
	//普通地图
	//判断是否是玛亚大陆保卫战
	$datew = date("N");
	$datehour = date("H:i");
	$maya = false;
	$mayaTimeconfig = kdjlFightWaitCacheValue($_pm['mem']->get(MEM_TIME_KEY), array());
	if(is_array($mayaTimeconfig)) foreach($mayaTimeconfig as $mayaConfig)
	{
		if($mayaConfig['titles'] == 'maya' && isWeeklyDayTimeActive($mayaConfig['days'], $mayaConfig['starttime'], $mayaConfig['endtime'], $datew, $datehour))
		{
			$maya = $mayaConfig;
			break;
		}
	}


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
					$gwRandMax = max(1, intval($tmsNum*1.5));
					$gwNum=1+rand(1,$gwRandMax);
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
							$gw=array_values($gw);
							$gwct=count($gw);
						}else{
							if(!empty($_gw)){
								$gws[]=$_gw;
								$gwNum--;
							}
						}
					}
				}
				if(empty($gws))
				{
					header('location:/function/Team_Mod.php?546&n='.$_SESSION['team_inmap']);
					exit() ;
				}

				if(!kdjlFightRestoreActiveTeamPets($teamInfo))
				{
					die('队伍战斗宠物恢复失败！');
				}
				if(!$team->fightStart($gws))
				{
					header('location:/function/Team_Mod.php?n='.intval($_SESSION['team_inmap']));
					exit('队伍状态已经变化，请重新开始战斗！');
				}
				$teamState=$team->getTeamState();

				$started=true;
				$gw=$gws[0];
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
					if($team->checkLost()===-1) exit('<script>window.location="/function/City_Mod.php";</script>');
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
							foreach(array('team_select_map','autofighting','team_fuben_step','team_fuben_boss','fubensjoj') as $oldKey)
							{
								if(isset($oldData[$oldKey])) $dataNow[$oldKey]=$oldData[$oldKey];
							}
						}

						$_pm['mem']->setns('pm_team_fight_'.$teamId,$dataNow);
						$team->clearTeamState();
						header('location:/function/Fight_Mod.php?'.($teamState['autofighting']?'team_auto=1':'').'&type=1');
						echo '有队员超时,重新开始战斗!'.($teamState['autofighting']?'team_auto=1':'');
						exit;
					}

					//header("refresh:2;url=".$_SERVER['REQUEST_URI']);
					echo '
					<script language="javascript">
					setTimeout("window.location=\''.$requestUri.'\'",2000);
					</script>
					';

					if($_SESSION['waitTeamTime']>12)
					{
						echo '<br/><span style="font-size:12px" onclick="window.parent.$(\'gw\').src=\'./function/Fight_Mod.php?&type=1\';">有队员相应超时,点击这里,重新开始战斗111111111111111111111111111111!</font>';
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
				if(isset($teamState['cur_monster']['hp']) && intval($teamState['cur_monster']['hp'])>0)//继续打上一个其它队员没有打死的怪物
				{
					$_SESSION['fight'.$_SESSION['id']]=NULL;
					if(!empty($teamState['monsters'])){
						foreach($teamState['monsters'] as $k=>$v)//目的是取第一个键
							break;
						$teamState['monsters'][$k]['hp']=$teamState['cur_monster']['hp'];
						$teamState['monsters'][$k]['mp']=isset($teamState['cur_monster']['mp']) ? $teamState['cur_monster']['mp'] : 0;
						$gw=$teamState['monsters'][$k];
					}else{
						$teamState['monsters_last']['hp']=$teamState['cur_monster']['hp'];
						$teamState['monsters_last']['mp']=isset($teamState['cur_monster']['mp']) ? $teamState['cur_monster']['mp'] : 0;
						$gw=$teamState['monsters_last'];
					}
					$team->setTeamState($teamState, true);
				}else if(isset($teamState['cur_monster'])&&!empty($teamState['cur_monster'])){

					$teamState['monsters_last']['hp']=$teamState['cur_monster']['hp'];
					$teamState['monsters_last']['mp']=isset($teamState['cur_monster']['mp']) ? $teamState['cur_monster']['mp'] : 0;
					$team->setTeamState($teamState, true);
					$currentMonsterGid = isset($teamState['cur_monster']['gid']) ? intval($teamState['cur_monster']['gid']) : 0;
					$gw = $currentMonsterGid > 0 ? getBaseGpcInfoById($currentMonsterGid) : array();
					if(!is_array($gw)) $gw = $teamState['cur_monster'];
					$gw['hp']=$teamState['cur_monster']['hp'];
					$gw['mp']=isset($teamState['cur_monster']['mp']) ? $teamState['cur_monster']['mp'] : 0;
				}else{
				//继续打下一个怪物,这种情况，不会出现？
					//$gw=array_shift($teamState['monsters']);
					//echo 'fg $flagteam='.$flagteam.','.print_r($fight,1).','.__LINE__.'-'.$_SESSION['mbid'];
					$__gw=false;
					if(isset($teamState['monsters']) && is_array($teamState['monsters']) && count($teamState['monsters'])>0)
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
						$_SESSION['fight'.$_SESSION['id']] = kdjlFightStartState($_SESSION['fight'.$_SESSION['id']], $user, true, '');
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
			if(!is_array($gw)) $gw = array();
		}else{
			$sql = "SELECT * FROM gpc WHERE level = $idse AND boss != 4 AND kx != '1'";
			$gw = $_pm['mysql'] -> getRecords($sql);
			if(!is_array($gw)) $gw = array();
		}
		if($_SESSION['multi_monsters'.$_SESSION['id']] == 2){
			if (count($gw)<1)
			{
				$gw = array();
			}
			else if ((count($gw)==1)) $gw = $gw[0];
			else
			{
				$min	= $gw[0];
				$n		= rand(1, count($gw));
				$gw		= $gw[$n-1];
			}

			/*加入遇BOSS的时间限制。*/

			while(is_array($gw) && isset($gw['boss']) && $gw['boss'] == 3 && bossCheck($gw) === false){
				$idse = rand($lvlMin, $lvlMax);
				$gw = $_pm['mysql'] -> getRecords("SELECT * FROM gpc WHERE level = $idse AND boss != 4 AND boss != 3 LIMIT 1");
				$gw = (!empty($gw) && is_array($gw[0])) ? $gw[0] : array();
			}
		}
	}
}
/*if($_SESSION['id'] == '281991'){
	echo $sql.'<br />';
	print_r($gw);
	echo '<br />';
}*/
$gwBoss = (is_array($gw) && isset($gw['boss'])) ? intval($gw['boss']) : 0;
if (
		(!is_array($gw) || count($gw)<1 || $gwBoss == 4)
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
				foreach(array('team_select_map','team_fuben_step','autofighting','team_fuben_boss','fubensjoj') as $oldKey)
				{
					if(isset($oldData[$oldKey])) $dataNow[$oldKey]=$oldData[$oldKey];
				}
			}

			$_pm['mem']->setns('pm_team_fight_'.$teamId,$dataNow);

			$team->clearTeamState();
			$mems=array();
			if(!empty($teamInfo['members'])){
				foreach($teamInfo['members'] as $row)
				{
					if(isset($row['state'],$row['uid']) && $row['state']<1) $mems[]=intval($row['uid']);
				}
			}
			$team->snotice('getTeamFightMod',$teamInfo['members'],$mems);
		}

	}
	header("refresh:2;url=Fight_Mod.php?p={$bid}");
	echo '当前地图没有可用怪物，正在重新匹配。';
	exit;
}
else
{


	$gw['wx'] = getWx($gw['wx']);
$_SESSION['gwcdie'.$_SESSION['id']] = $gw['id'];
	$gwNameJs = fightModJsSingle($gw['name']);
	$gwWxJs = fightModJsSingle($gw['wx']);
	$gwSkillJs = fightModJsSingle($gw['skill']);
	$gwImgStand = fightModImage($gw['imgstand']);
	$gwImgAck = fightModImage($gw['imgack']);
	$gwImgDie = fightModImage($gw['imgdie']);
	$gwinfo="['{$gwNameJs}',{$gw['level']},'{$gwWxJs}',{$gw['ac']},{$gw['mc']},{$gw['hp']},{$gw['mp']},'{$gwSkillJs}','{$gwImgStand}','{$gwImgAck}','{$gwImgDie}',{$gw['id']}]";

	$test = isset($_SESSION['fight'.$_SESSION['id']]) && is_array($_SESSION['fight'.$_SESSION['id']]) ? $_SESSION['fight'.$_SESSION['id']] : array();
	//Update fightting stats.
	if (empty($test))
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
		$_SESSION['fight'.$_SESSION['id']] = kdjlFightStartState($_SESSION['fight'.$_SESSION['id']], $user, true, '');

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
	   $will = kdjlFightEntryWaitRemaining($fight, $user, true, $bid, '');
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
		window.parent.document.getElementById("gw").src="./function/Fight_Mod.php?p='.$waitBid.'&s=t";
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
		$_SESSION["fight".$_SESSION['id']]=kdjlFightStartState($r, $user, true, '');

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
		if ($v['varyname'] == 1 && $v['sums']>0)
		{
			if (empty($bbfzp)) $bbfzp = "['".$v['name']."',".$v['sums'].','.$v['id']."]";
			else $bbfzp .= ",['".$v['name']."',".$v['sums'].','.$v['id']."]";
		}
		else if ($v['varyname'] == 3 && $v['sums']>0 &&
			kdjlCatchPropTargetsGpc(isset($v['effect']) ? $v['effect'] : '', $currentCatchGpcId))
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
	$currentFight = isset($_SESSION['fight'.$_SESSION['id']]) && is_array($_SESSION['fight'.$_SESSION['id']]) ? $_SESSION['fight'.$_SESSION['id']] : array();
	$_SESSION['fttime'.$_SESSION['id']] = kdjlFightAttackWaitLimit($user, true, $currentFight, '');
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
if($requestFrom == 1)
{
	$backObj['bbInfo'] = array(
		"id"=>$bb['id'],
		"pet_name"=>kdjlSafeIconv("gbk","utf-8",$bb['name']),
		"hp"=>$bb['srchp'],
		"mp"=>$bb['srcmp'],
		"nowexp"=>$bb['nowexp'],
		"lexp"=>$bb['lexp'],
		"lv"=>$bb['level'],
		"pet_id"=>str_replace(array("z",".gif"),array("",""),$bb['imgstand']),
		"name"=>kdjlSafeIconv("gbk","utf-8",$user['nickname'])
	);
	$backObj['otherInfo'] = array(
		"id"=>$gw['id'],
		"name"=>kdjlSafeIconv("gbk","utf-8",$gw['name']),
		"level"=>$gw['level'],
		"hp"=>$gw['hp'],
		"pet_id"=>str_replace(array("z",".gif"),array("",""),$gw['imgstand']),
		"wx"=>kdjlSafeIconv("gbk","utf-8",$gw['wx']),
		'imgstand'=>$gw['imgstand']
	);
	if($gw['boss'] == 1)
	{
		$gwNameSql = $_pm['mysql']->escape($gw['name']);
		$sql = "SELECT imgstand FROM bb WHERE name = '{$gwNameSql}'";
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

if($teamId>0){
	echo str_replace('<body>','<body><iframe src="/function/team.php?a3&checkOnly=1&rd=" style="position:absolute;z-index:0;top:1000px;" width="30" height="30"  class="wgframe"></iframe><script language="javascript">var a0;var teamautofight='.intval(isset($teamState['autofight']) ? $teamState['autofight'] : 0).';window.parent.autoack='.intval(isset($teamState['autofighting']) ? $teamState['autofighting'] : 0).';var teamfightlock=false;var teamLeader='.intval(isset($teamInfo['team']['creator']) ? $teamInfo['team']['creator'] : 0).';'.$teamFbstr.'</script>',$fat);
}else{
	echo str_replace('<body>','<script language="javascript">var a1;var teamfightlock="NONE";var teamLeader=0;'.$teamFbstr.'</script><body>',$fat);
}
if($flagteam){
	//组队保存数据和发送通知
	$team->setTeamState(array('cur_monster'=>$_SESSION["fight".$_SESSION['id']]));
	$team->setTeamState(array('fight_html'=>str_replace('<body>','<body><iframe src="/function/team.php?a4&checkOnly=1&rd=" style="position:absolute;z-index:0;top:1000px;" width="30" height="30"  class="wgframe"></iframe><script language="javascript">var a2;var curTeamTurnId='.$uid.';window.parent.autoack='.intval(isset($teamState['autofighting']) ? $teamState['autofighting'] : 0).';var teamautofight='.intval(isset($teamState['autofight']) ? $teamState['autofight'] : 0).';if(curTeamTurnId==parent.myUid){var teamfightlock=false;}else{var teamfightlock=true;};var teamLeader='.intval(isset($teamInfo['team']['creator']) ? $teamInfo['team']['creator'] : 0).';</script>',$fat)));
	$exclude=array($uid);
	foreach($teamInfo['members'] as $row)
	{
		if(isset($row['state'],$row['uid']) && $row['state']<1){
			$exclude[]=intval($row['uid']);
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
	$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
	if($uid < 1) return false;
	$log='';
	if (!kdjlReserveWorldBoss($gs, $uid, $log)) return false;
	$gid = intval($gs['id']);
    $log = $_pm['mysql']->escape($log);
	$logUid = $uid;
	$task = new task();
	$bossName = isset($gs['name']) ? $gs['name'] : $gid;
	$task->saveGword("遇上了沉睡中的[".$bossName."]，勇士请赶快去消灭它吧！");
	$_pm['mysql']->query("INSERT INTO gamelog(ptime,seller,buyer,pnote,vary) VALUES(unix_timestamp(),{$logUid},{$logUid},'{$log}',3)");
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
