<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.22
*@Usage: Expore privew. --> Team
*@Note:
  @sugefei update: 2008-09-08 10:27 调整组队界面的在线玩家显示，优化SQL。
*/
require_once('../config/config.game.php');

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');
$from = (isset($_REQUEST['from']) && !is_array($_REQUEST['from'])) ? intval($_REQUEST['from']) : 0;
$hasRequestedMap = isset($_REQUEST['n']) && !is_array($_REQUEST['n']);
$nRequest = $hasRequestedMap ? intval($_REQUEST['n']) : 0;
$_REQUEST['n'] = $nRequest;
if(isset($_SESSION['team_inmap'])) $_SESSION['team_inmap'] = intval($_SESSION['team_inmap']);
define('MEM_FIGHTUSER_KEY', $uid . 'fuser');
if($from !=1)
{
	secStart($_pm['mem']);
}
$user		= $_pm['user']->getUserById($uid);
$petsarr	= $_pm['user']->getUserPetById($uid);
if(!is_array($user)) die('');
if(!is_array($petsarr)) $petsarr = array();
$userDefaults = array('openmap' => '', 'sysautosum' => 0, 'maxautofitsum' => 0, 'mbid' => 0, 'headimg' => '', 'nickname' => '');
foreach($userDefaults as $defaultKey => $defaultValue)
{
	if(!isset($user[$defaultKey])) $user[$defaultKey] = $defaultValue;
}
$openmap = explode(",",$user['openmap']);
$pets = array('', '', '');
$online = '';
$gpclist = '';
$mapinfo = '';
$gpccolor = array();
$c = '';
$snum = 0;
$snum1 = 0;
$tgt = 0;
$ret = '';
$num = 0;
$team = '';
$team1 = '';
$vary = 0;

if(!function_exists('teamModHtml'))
{
	function teamModHtml($value)
	{
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}
}

if(!function_exists('teamModJsSingle'))
{
	function teamModJsSingle($value)
	{
		$value = str_replace("\\", "\\\\", (string)$value);
		$value = str_replace("'", "\\'", $value);
		$value = str_replace(array("\r", "\n", "<", ">"), array("\\r", "\\n", "\\x3C", "\\x3E"), $value);
		return $value;
	}
}

$userNicknameHtml = teamModHtml($user['nickname']);
$userNicknameJs = teamModJsSingle($user['nickname']);
$user['nickname'] = $userNicknameHtml;

if(
	$hasRequestedMap
	&& !in_array($nRequest,$openmap)
	&& $nRequest != 125 && $nRequest != 15
	&& $nRequest != 19  && $nRequest != 126
	&& $nRequest != 20 && $nRequest != 128
	&& $nRequest != 18 && $nRequest != 17
	&& $nRequest != 101 && $nRequest != 102
	&& $nRequest != 104 && $nRequest != 105
	&& $nRequest != 107 && $nRequest != 108
	&& $nRequest != 110 && $nRequest != 111
	&& $nRequest != 113 && $nRequest != 114
	&& $nRequest != 116 && $nRequest != 117
	&& $nRequest != 119 && $nRequest != 120
	&& $nRequest != 122 && $nRequest != 123
	&& $nRequest != 129 && $nRequest != 130
	&& $nRequest != 132 && $nRequest != 133
	&& $nRequest != 135 && $nRequest != 136
	&& $nRequest != 138 && $nRequest != 139
	&& $nRequest != 141 && $nRequest != 142
	&& $nRequest != 143 && $nRequest != 144
	&& $nRequest != 145 && $nRequest != 146
	&& $nRequest != 147 && $nRequest != 148
	&& $nRequest != 149 && $nRequest != 150
)
{
	if($_pm['mysql']->query('update player set inmap=0 where id='.$uid))
	{
		$_pm['mem']->del(MEM_USER_KEY);
	}
	die("地图开放时间到期，或者地图未开启(".$nRequest.")！");
}

require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
$s=new socketmsg();
// 5.4后不支持挂
$teamId = isset($_SESSION['team_id']) ? intval($_SESSION['team_id']) : 0;
$tcls=new team($teamId,$s);
$myState=$tcls->checkMyTeam();
$teamId = isset($_SESSION['team_id']) ? intval($_SESSION['team_id']) : 0;
$teamState=$tcls->getTeamState();
$teamWasFighting=!empty($teamState['fighting']);
/*
$dataNow['team_fuben_card_step_num']=$oldData['team_fuben_card_step_num'];
*/
if($teamId>0 && $myState!==false && !$teamWasFighting)
{
	$tcls->setTeamState(array(
								'team_fuben_card_step_num'=>-1
								));
	$tcls->clearTeamState();
}

$teamState=$tcls->getTeamState();
if(!$teamWasFighting && isset($teamState['team_fuben_boss']) && $teamState['team_fuben_boss'])
{
	$tcls->clearTeamFubenData();
	header('location:/function/Fight_Mod.php');
	die();
}

//$_pm['mem']->del('tarot_info_'.$_SESSION['team_id']);

if($teamId > 0){
	$isleader=$tcls->isTeamLeader($uid,$teamId);
	if($teamWasFighting)
	{
		$teamFightHtml = isset($teamState['fight_html']) ? $teamState['fight_html'] : '';
		if(strpos($teamFightHtml,'<body')!==false && strlen($teamFightHtml)>=100)
		{
			header("Cache-Control: no-cache, must-revalidate");
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			echo $teamFightHtml;
			die();
		}
		header('location:/function/Fight_Mod.php');
		die();
	}
	if(isset($_GET['tact']) && !is_array($_GET['tact']))
	{
		$teamAction = $_GET['tact'];
		if($teamAction=='quit')
		{
			if($isleader)
			{
				//$_SESSION['GoToCity']=0;
				echo '<script language="javascript">
				parent.Alert("不允许队长退出!");
				window.location="/function/Team_Mod.php"</script>';
				die();
			}
			$tcls->leaveTeam();
			header('location:/function/City_Mod.php');
			die();
		}else if($teamAction=='swap'){
			if($isleader)
			{
				//$_SESSION['GoToCity']=0;
				echo '<script language="javascript">
				parent.Alert("不允许队长暂离!");
				window.location="/function/Team_Mod.php"</script>';
				die();
			}
			$tcls->swapTeamState();
			$httpReferer = (isset($_SERVER['HTTP_REFERER']) && !is_array($_SERVER['HTTP_REFERER'])) ? str_replace(array("\r","\n"), '', $_SERVER['HTTP_REFERER']) : '';
			$redirectPath = '/function/City_Mod.php';
			if(!empty($httpReferer))
			{
				$refererInfo = parse_url($httpReferer);
				$currentHost = (isset($_SERVER['HTTP_HOST']) && !is_array($_SERVER['HTTP_HOST'])) ? strtolower($_SERVER['HTTP_HOST']) : '';
				if(strpos($currentHost, ':') !== false) $currentHost = substr($currentHost, 0, strpos($currentHost, ':'));
				$refererHost = (is_array($refererInfo) && isset($refererInfo['host'])) ? strtolower($refererInfo['host']) : '';
				if(is_array($refererInfo) && ($refererHost == '' || $refererHost == $currentHost) && isset($refererInfo['path']) && substr($refererInfo['path'], 0, 1) == '/')
				{
					$redirectPath = $refererInfo['path'].(isset($refererInfo['query']) ? '?'.$refererInfo['query'] : '');
				}
			}
			$redirectPath = str_replace(array("\r", "\n"), '', $redirectPath);
			header('location:'.$redirectPath);

			die();
		}else{
			$teamState=$tcls->getTeamState();
			$teamFightHtml = isset($teamState['fight_html']) ? $teamState['fight_html'] : '';
			if(strpos($teamFightHtml,'<body')!==false&&strlen($teamFightHtml)>=100)
			{
				header("Cache-Control: no-cache, must-revalidate");
				header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
				echo $teamFightHtml;
				die();
			}
		}
	}
	if($isleader)
	{
		$tcls->clearTeamState();
		$tcls->reliveAll(0);
		$tcls->returnVi();
		$nRequest = isset($_SESSION['team_inmap']) ? intval($_SESSION['team_inmap']) : 0;
		$_REQUEST['n']=$nRequest;
	}else if($myState<1){
		//暂离状态什么也不做
	}else{
		$teamState=$tcls->getTeamState();
		$teamFightHtml = isset($teamState['fight_html']) ? $teamState['fight_html'] : '';
		if(strpos($teamFightHtml,'<body')!==false&&strlen($teamFightHtml)>=100)
		{
			header("Cache-Control: no-cache, must-revalidate");
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			echo $teamFightHtml;
			die();
		}
		$nRequest = isset($_SESSION['team_inmap']) ? intval($_SESSION['team_inmap']) : 0;
		$_REQUEST['n']=$nRequest;
	}

	/*
	if(){
		die('
		<script language="javascript">
		window.location="/function/Team_Mod.php?n='.$_SESSION['team_inmap'].'";
		parent.Alert("退出队伍才能更换地图!");
		</script>
		');
	}*/
}


$_SESSION['exptype'.$uid] = "";
$fightWay = isset($_SESSION['way'.$uid]) ? $_SESSION['way'.$uid] : '';
if($fightWay == "" || $fightWay == "money")
{
	$num = $user['sysautosum'];
}
else if($fightWay == "yb")
{
	$num = $user['maxautofitsum'];
}
$_pm['mysql']->query("UPDATE player
					     SET autofitflag=0
					   WHERE id={$uid}
					");
$n = $nRequest;
$table = "";
$ifrteamh=210;

$tcls->autoDisbandTeam($n);

if($n == 16 || $n >= 100)
{
	$table = '<table width="100%" height="28" border="0" cellpadding="0" cellspacing="0" style="margin-bottom:10px">
			<tr>
            <td height="25" colspan="4"  align="left">&nbsp;&nbsp;&nbsp;&nbsp;当前选择的难度：<span id="sign"></span></td>
          </tr>
        </table><table width="100%" border="0" cellspacing="0" cellpadding="0"  style="margin-bottom:10px">
          <tr>
            <td width="94"  align="right">
			<img src="'.IMAGE_SRC_URL.'/ui/team/ann07.gif" width="64" height="28" style="padding-right:5px;cursor:pointer;" onclick="nadu=1;pk1(1);mapid='.$n.'"></td>
            <td><img src="'.IMAGE_SRC_URL.'/ui/team/ann08.gif" width="64" height="28" style="cursor:pointer;" onclick="nadu=2;pk1(2);mapid='.$n.'"></td>
            <td><img src="'.IMAGE_SRC_URL.'/ui/team/ann09.gif" width="64" height="28" style="cursor:pointer;" onclick="nadu=3;pk1(3);mapid='.$n.'"></td>
            <td width="12">&nbsp;</td>
          </tr>
        </table>';
		$ifrteamh-=80;
}

if($teamId > 0 && (!isset($_SESSION['team_inmap']) || $nRequest != intval($_SESSION['team_inmap'])))
{
	$nRequest = isset($_SESSION['team_inmap']) ? intval($_SESSION['team_inmap']) : 0;
	$_REQUEST['n']=$nRequest;
	$n = $nRequest;
}

$ifr='';
if($teamId > 0 && isset($_SESSION['team_inmap']) && $nRequest == intval($_SESSION['team_inmap']))
{
	$ifr='<iframe src="/function/team.php?b1&showAllTeamsTime=0&rd=" style="position:absolute;z-index:0;top:1000px;" width="30" height="30"  class="wgframe"></iframe>';
	$isleader=$tcls->isTeamLeader($uid,$teamId);

	if($isleader){
		$team='<iframe id="teamlistifr" allowtransparency="true" name="teamlistifrww" class="wgframe" width="260" height="'.$ifrteamh.'" frameborder="0" src="/function/team.php?showAllTeamsTime=0"></iframe>';
		$team1='
		<div class="anniu">
			<div class="anniu1"><img src="../images/ui/team/zd.gif" width="78" height="29" style="cursor:pointer;"  onclick="pk();"/></div>
			<div class="anniu1"><img src="../images/ui/team/jsdw.png" width="78" height="29"  style="cursor:pointer;" onclick="if(confirm(\'确定要解散你的队伍？\')){disbandTeam()}" value="解散队伍" /></div>
		</div>';
	}else{
		$team='<iframe id="teamlistifr" allowtransparency="true" name="teamlistifrww" class="wgframe" width="260" height="'.$ifrteamh.'" frameborder="0" src="/function/team.php?showAllTeamsTime=0"></iframe>';
		$team1='<div class="anniu">
			<div class="anniu1" ><img src="../images/ui/team/zlgd.png" style="cursor:pointer;"  onclick="swapState();"/></div>
			<div class="anniu1"><img src="../images/ui/team/lk.gif" width="78" height="29" style="cursor:pointer;" onclick="if(confirm(\'确定要离开你的队伍？\')){leaveTeam();this.disabled=true;}" /></div>
		</div>';
	}

}else{
	$team='
	<iframe frameborder="0" allowtransparency="true" id="teamlistifr" name="teamlistifr" class="wgframe" width="260" height="'.$ifrteamh.'" src="/function/team.php?showAllTeamsTime=0"></iframe>';
	$team1='<div class="anniu">
			<div class="anniu1"><img src="../images/ui/team/zd.gif" width="78" height="29" style="cursor:pointer;"  onclick="pk();"/></div>
			<div class="anniu1" id="creatUTeam"><img src="../images/ui/team/cjdw.gif" width="78" height="29" style="cursor:pointer;" onclick="if(confirm(\'确定要建立你的队伍？\')){createTeam()}" /></div>
		</div>';
}

$memmapid = $_pm['mem']->get('db_mapid');
if(!is_array($memmapid)) $memmapid = kdjlSafeMemValue($memmapid, array());
if(!is_array($memmapid)) $memmapid = array();
if($n==0)
{
	$rsInmap=$_pm['mysql']->getOneRecord('select inmap from player where id='.$uid);
	if(is_array($rsInmap) && isset($rsInmap['inmap'])) $n=intval($rsInmap['inmap']);
	if($n==0)  $n=1;
}

if ($n>0)
{
	if(isset($memmapid[$n]) && is_array($memmapid[$n]))
	{
		$map = $memmapid[$n];
	}
	else
	{
		die('地图数据错误：'.$n);
	}
	/*$map = $_pm['mem']->dataGet(array('k' => MEM_MAP_KEY,
						     'v' => "if(\$rs['id'] == '{$n}') \$ret=\$rs;"
					 ));*/
	if (!is_array($map))
	{
		$mapinfo = '载入地图出错！';
	}
}
else {
	die('地图数据错误！');
}

$kk=0;
$selid=0; // default select pets!
$mapDefaults = array('id' => $n, 'name' => '', 'descs' => '', 'level' => '1,999', 'gpclist' => '', 'czlprops' => '', 'multi_monsters' => 0);
foreach($mapDefaults as $defaultKey => $defaultValue)
{
	if(!isset($map[$defaultKey])) $map[$defaultKey] = $defaultValue;
}
$lmt = explode(',', $map['level']);
if(!isset($lmt[0])) $lmt[0] = 1;
$lmt[0] = intval($lmt[0]);
if($lmt[0] < 1) $lmt[0] = 1;
if (is_array($petsarr))
{
	foreach ($petsarr as $k =>$rs) // Will filter in muchang pets for current user.
	{
		if(!is_array($rs)) continue;
		$rsDefaults = array('id' => 0, 'name' => '', 'muchang' => 0, 'tgflag' => 0, 'level' => 1, 'cardimg' => '');
		foreach($rsDefaults as $defaultKey => $defaultValue)
		{
			if(!isset($rs[$defaultKey])) $rs[$defaultKey] = $defaultValue;
		}
		$rs['id'] = intval($rs['id']);
		$rs['level'] = intval($rs['level']);
		$rs['muchang'] = intval($rs['muchang']);
		$rs['tgflag'] = intval($rs['tgflag']);
		/*if ($rs['muchang'] == 1) continue;
		if ($kk == 0) {$sel = 100;$selid=$rs['id'];}
		else $sel = 50;
		if($rs['level']==0) $rs['level']=1;*/
		//if($rs['muchang'] == 1 || $rs['muchang'] == 3 || $rs['muchang'] == 4 || $rs['muchang'] == 7 || $rs['muchang'] == 5 || $rs['muchang'] == 6 ) continue;
		if($rs['muchang'] != 0 || $rs['tgflag'] != 0){
			continue;
		}
		if($rs['id'] == $user['mbid'])
		{
			$sel = 100;
			$selid=$rs['id'];
		}
		else
		{
			$sel = 50;
		}
		if($rs['level']==0) $rs['level']=1;
		$cardImg = preg_replace('/[^A-Za-z0-9_.-]/', '', (string)$rs['cardimg']);
		$petNameHtml = teamModHtml($rs['name']);
		$sel = intval($sel);
		$pets[$kk++] = "<img src='".IMAGE_SRC_URL."/bb/".$cardImg."' onClick=\"Setbbs({$rs['id']},{$rs['level']},{$lmt['0']},this);\" alt=\"".$petNameHtml."\" style='cursor:pointer;filter:alpha(opacity={$sel});' id='i{$kk}'> ";
		if ($kk==3) break;
	}
}

$useridlist = $_pm['mysql']->getRecords("SELECT id,inmap,nickname
										   FROM player
										  WHERE inmap={$n} and lastvtime>".time()."-300 and (secid=0 or secid is null)
										  ORDER by lastvtime DESC
										  LIMIT 0,20");
if (is_array($useridlist))
{
	foreach ($useridlist as $k => $tuser)
	{
		if(!is_array($tuser)) continue;
		if(!isset($tuser['id'])) $tuser['id'] = 0;
		if(!isset($tuser['inmap'])) $tuser['inmap'] = 0;
		if(!isset($tuser['nickname'])) $tuser['nickname'] = '';
		if ($tuser['id'] == $uid) continue;
		if (is_array($tuser) && $tuser['inmap']==$n)
		{
			$nicknameHtml = teamModHtml($tuser['nickname']);
			$nicknameJs = teamModJsSingle($tuser['nickname']);
			$online .= '
			<li>
				<div class="zxwj_list "><img src="../images/ui/team/ren.gif" width="13" height="15" />                    </div>
				<div class="zxwj_list2 " style="cursor:pointer" onclick="TeamChoose(\''.$nicknameJs.'\','.$tuser['id'].',event);">'.$nicknameHtml.'
				</div>
			</li>';
		}
	}
}
//$online="<tr><td width=200>暂时关闭显示玩家列表</td><td></td></tr>\n";

// Save map position to user.
$map['id'] = intval($map['id']);
$user['inmap'] = $map['id'];
$_pm['mysql']->query("UPDATE player
						 SET inmap={$map['id']}
					   WHERE id = {$uid}
					");
if($from == 1)
{
	$_pm['mysql']->query("UPDATE player
						 SET bot_map_id={$map['id']}
					   WHERE id = {$uid}
					");
}
//$_pm['user']->updateMemUser($_SESSION['id']);
//###########################
// @Load template.
//###########################

$gw=array();
$monsters=explode(",",$map['gpclist']);
if($monsters){
	foreach ($monsters as $v)
	{
		$v = trim($v);
		if($v === '') continue;
		$monsterHtml = teamModHtml($v);
		$monsterJs = teamModHtml(teamModJsSingle('怪物-'.$v));
		$gw[]='<span onclick="copyWord(\''.$monsterJs.'\');">'.$monsterHtml.'</span>';
	}
}
$maggw=implode(",",$gw);
//成长限制
if(empty($map['czlprops']))
{
	$czl = "无限制";
}
else
{
	$arr = explode("|",$map['czlprops']);
	if(empty($arr[0]))
	{
		$czl = "无限制";
	}
	else
	{
		$czl = $arr[0];
	}
}

if($map['multi_monsters'] == 1){//挑战地图
	$memgpc = $_pm['mem'] -> get('db_gpcid');
	if(!is_array($memgpc)) $memgpc = kdjlSafeMemValue($memgpc, array());
	if(!is_array($memgpc)) $memgpc = array();
	$gpccolor = array(5=>'白',6=>'黄',7=>'蓝',8=>'紫',9=>'红');
	$_pm['mysql'] -> query("CREATE TABLE if not exists `challenge_log` (`id` int(11) NOT NULL AUTO_INCREMENT,`uid` int(11) DEFAULT '0',`gid` int(11) DEFAULT '0',PRIMARY KEY (`id`),KEY `uid` (`uid`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
	$challengeState = loadTeamChallengeState($uid, $memgpc, $gpccolor);
	$gpclist = $challengeState['gpclist'];
	$vary = $challengeState['vary'];
	$snum = $challengeState['snum'];
	$snum1 = $challengeState['snum1'];
	//难度
	switch($vary){
		case 1:
			$c = '★';
			break;
		case 2:
			$c = '★★';
			break;
		case 3:
			$c = '★★★';
			break;
		case 4:
			$c = '★★★★';
			break;
		case 5:
			$c = '★★★★★';
			break;
		default:
			$c = '';
	}
	$tn = $_game['template'] . 'tpl_cteam.html';
}else if($map['multi_monsters'] == 2){
	//通关塔：
	$useridlist = $_pm['mysql']->getRecords("SELECT player.nickname,player_ext.tgt FROM player,player_ext WHERE player.id=player_ext.uid AND tgt!=0
										  ORDER by player_ext.tgt DESC
										  LIMIT 5");
	$online = '<table width="200" border="0" cellspacing="0" cellpadding="0" style="font-size:12px">';
	if (is_array($useridlist))
	{
		$online .= '<tr>
				  <td width="20" height="23">&nbsp;</td>
				  <td width="40">名次</td>
				  <td width="90">玩家姓名</td>
				  <td>通关数</td>
				</tr>';
		foreach ($useridlist as $k => $tuser)
		{
			$i = 0;
			if (is_array($tuser))
			{
				if(!isset($tuser['nickname'])) $tuser['nickname'] = '';
				if(!isset($tuser['tgt'])) $tuser['tgt'] = 0;
				$nicknameHtml = teamModHtml($tuser['nickname']);
				$tgt = intval($tuser['tgt']);
				$i = $k+1;
				$online .= '<tr>
				  <td width="20" height="23">&nbsp;</td>
				  <td width="40">'.$i.'</td>
				  <td width="90">'.$nicknameHtml.'</td>
				  <td>'.$tgt.'</td>
				</tr>';
			}
		}
	}else{
		$online .= '<tr>
				  <td width="20" height="23">&nbsp;</td>
				  <td width="40"></td>
				  <td width="90">排行为空</td>
				  <td></td>
				</tr>';
	}
	$online .= '</table>';
	$sql = "SELECT tgt,tgttime,uid FROM player_ext WHERE uid = {$uid}";
	$uarr = $_pm['mysql'] -> getOneRecord($sql);
	if(!is_array($uarr)){
		$_pm['mysql'] -> query("INSERT INTO player_ext (uid,bbshow) VALUES ({$uid},5) ON DUPLICATE KEY UPDATE bbshow=bbshow");
		$tgt = 1;
	}else{
		if(!isset($uarr['tgttime'])) $uarr['tgttime'] = 0;
		if(!isset($uarr['tgt'])) $uarr['tgt'] = 0;
		$tgtProgress = intval($uarr['tgt']);
		if(intval($uarr['tgttime']) > 0 && date('Ymd', intval($uarr['tgttime'])) < date('Ymd')){
			// The locked ttGate entry resets persistent state before generating floor 1.
			$tgtProgress = 0;
		}
		$tgt = $tgtProgress + 1;

	}
	$online .= '<table width="85%" border="0" cellspacing="0" cellpadding="0" align="center">
            <tr>
              <td height="25" style="font-size:12px;">当前关卡：'.$tgt.'</td>
            </tr>
            <tr>
              <td style="height:25px; font-size:14px;color:#FF0000" align="right"><span style="font:bold; cursor:pointer" onclick="cfight()">继续冒险</span></td>
            </tr>
          </table>';
	$tn = $_game['template'] . 'tpl_tt.html';
}else{
	$tn = $_game['template'] . 'tpl_team.html';
}
if (file_exists($tn))
{
	$tpl = file_get_contents($tn);

	if($n)
	{
		$src = array("#mapname#",
					 "#mapinfo#",
					 "#level#",
					 "#gw#",
					 "#one#",
					 "#two#",
					 "#three#",
					 "#otherlist#",
					 "#bid#",
					 "#head1#",
					 "#head1info#",
					 "#_self#",
					 "#num#",
					 "#table#",
					 "#mapid#",
					 "#czl#",
					 "#gpclist#",
					 "#c#",
					 "#snum#",
					 "#snum1#",
					 "#team#",
					 "#team1#",
					 '#ifrteamh#',
					 '#ifr#'
					);
		$des = array($map['name'],
		             $map['descs'],
					 str_replace(","," 到 ",$map['level']),
					 $maggw,
					 $pets[0],
					 $pets[1],
					 $pets[2],
					 $online,
					 $selid,
					 $user['headimg'].'.gif',
					 '昵称：'.$user['nickname'],
					 $userNicknameJs,
					 $num,
					 $table,
					 $n,
					 $czl,
					 $gpclist,
					 $c,
					 $snum,
					 $snum1,
					 $team,
					 $team1,
					 $ifrteamh,
					 $ifr
				);
	}

	$ret = str_replace($src, $des, $tpl);
}
$_pm['mem']->memClose();
// gzip echo. if maybe.
ob_start('ob_gzip');
echo $ret;
ob_end_flush();




//$num 刷新次数

function releaseTeamChallengeLock($lockName)
{
	global $_pm;
	$lockNameSql = $_pm['mysql']->escape($lockName);
	$_pm['mysql']->getOneRecord("SELECT RELEASE_LOCK('{$lockNameSql}') AS released");
}

function loadTeamChallengeState($uid, $memgpc, $gpccolor)
{
	global $_pm;
	$uid = intval($uid);
	$errorState = array('gpclist'=>'挑战配置错误！','vary'=>0,'snum'=>0,'snum1'=>0);
	if($uid < 1) return $errorState;

	$lockName = 'kdjl_challenge_'.$uid;
	$lockNameSql = $_pm['mysql']->escape($lockName);
	$lockRow = $_pm['mysql']->getOneRecord("SELECT GET_LOCK('{$lockNameSql}',5) AS locked");
	if(!is_array($lockRow) || intval($lockRow['locked']) != 1)
	{
		$errorState['gpclist'] = '挑战数据繁忙，请稍候再试！';
		return $errorState;
	}
	if(!$_pm['mysql']->query('START TRANSACTION'))
	{
		releaseTeamChallengeLock($lockName);
		return $errorState;
	}

	$carr = $_pm['mysql']->getOneRecord(
		'SELECT id,nums,lastvtime,vary,snums FROM challenge WHERE uid='.$uid.' ORDER BY id LIMIT 1 FOR UPDATE'
	);
	$time = time();
	$result = array('gpclist'=>'','vary'=>0,'snum'=>0,'snum1'=>0);
	$writeOk = true;

	if(!is_array($carr))
	{
		$challengeGpc = buildTeamChallengeRows(getGpc(1), $memgpc, $gpccolor, $uid, true);
		if(!is_array($challengeGpc))
		{
			$writeOk = false;
		}
		else
		{
			$vary = intval($challengeGpc['vary']);
			$glist = $challengeGpc['glist'];
			$writeOk = $_pm['mysql']->query(
				"INSERT INTO challenge (uid,lastvtime,gid,vary,nums,snums) VALUES({$uid},{$time},{$glist[0]},{$vary},0,0)"
			);
			if($writeOk)
			{
				$result = array('gpclist'=>$challengeGpc['html'],'vary'=>$vary,'snum'=>3,'snum1'=>2);
			}
		}
	}
	else
	{
		$challengeId = intval($carr['id']);
		$nums = isset($carr['nums']) ? intval($carr['nums']) : 0;
		$snums = isset($carr['snums']) ? intval($carr['snums']) : 0;
		$lastvtime = isset($carr['lastvtime']) ? intval($carr['lastvtime']) : 0;
		$currentVary = isset($carr['vary']) ? intval($carr['vary']) : 1;
		$yes = date('Ymd', $lastvtime);
		$yes1 = date('Ymd', $time-24*3600);

		if($yes1 >= $yes)
		{
			$writeOk = $_pm['mysql']->query('DELETE FROM challenge_log WHERE uid='.$uid);
			$challengeGpc = $writeOk ? buildTeamChallengeRows(getGpc(1), $memgpc, $gpccolor, $uid, true) : false;
			if(!is_array($challengeGpc))
			{
				$writeOk = false;
			}
			else
			{
				$vary = intval($challengeGpc['vary']);
				$glist = $challengeGpc['glist'];
				$writeOk = $_pm['mysql']->query(
					"UPDATE challenge SET lastvtime={$time},gid={$glist[0]},vary={$vary},nums=0,snums=0,flag=0 WHERE id={$challengeId} AND uid={$uid}"
				);
				if($writeOk)
				{
					$result = array('gpclist'=>$challengeGpc['html'],'vary'=>$vary,'snum'=>3,'snum1'=>2);
				}
			}
		}
		else
		{
			$glistRows = $_pm['mysql']->getRecords('SELECT gid FROM challenge_log WHERE uid='.$uid.' ORDER BY id');
			if(empty($glistRows))
			{
				$challengeGpc = buildTeamChallengeRows(getGpc($nums + 1), $memgpc, $gpccolor, $uid, true);
				if(!is_array($challengeGpc))
				{
					$writeOk = false;
				}
				else
				{
					$vary = intval($challengeGpc['vary']);
					$glist = $challengeGpc['glist'];
					$writeOk = $_pm['mysql']->query(
						"UPDATE challenge SET lastvtime={$time},gid={$glist[0]},vary={$vary},nums=COALESCE(nums,0)+1 WHERE id={$challengeId} AND uid={$uid}"
					);
					if($writeOk)
					{
						$result = array(
							'gpclist'=>$challengeGpc['html'],
							'vary'=>$vary,
							'snum'=>max(0, 3-$nums),
							'snum1'=>max(0, 2-$snums)
						);
					}
				}
			}
			else
			{
				$html = '';
				foreach($glistRows as $v)
				{
					$gid = isset($v['gid']) ? intval($v['gid']) : 0;
					if($gid < 1 || !isset($memgpc[$gid]) || !is_array($memgpc[$gid])) continue;
					$boss = isset($memgpc[$gid]['boss']) ? intval($memgpc[$gid]['boss']) : 0;
					$color = isset($gpccolor[$boss]) ? $gpccolor[$boss] : '';
					$name = isset($memgpc[$gid]['name']) ? teamModHtml($memgpc[$gid]['name']) : '';
					$html .= '<tr><td width="70%">'.$name.'</td><td>'.$color.'</td></tr>';
				}
				$result = array(
					'gpclist'=>$html,
					'vary'=>$currentVary,
					'snum'=>max(0, 3-$nums),
					'snum1'=>max(0, 2-$snums)
				);
			}
		}
	}

	if(!$writeOk || !$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		releaseTeamChallengeLock($lockName);
		return $errorState;
	}
	releaseTeamChallengeLock($lockName);
	return $result;
}

function buildTeamChallengeRows($garr, $memgpc, $gpccolor, $uid, $insertLog)
{
	global $_pm;
	if(!is_array($garr) || !isset($garr['gpc'])) return false;
	$vary = isset($garr['boss']) ? intval($garr['boss']) : 0;
	$rawList = explode(',', $garr['gpc']);
	$glist = array();
	$html = '';
	foreach($rawList as $gid)
	{
		$gid = intval($gid);
		if($gid < 1 || !isset($memgpc[$gid]) || !is_array($memgpc[$gid])) continue;
		$name = isset($memgpc[$gid]['name']) ? teamModHtml($memgpc[$gid]['name']) : '';
		$boss = isset($memgpc[$gid]['boss']) ? intval($memgpc[$gid]['boss']) : 0;
		$color = isset($gpccolor[$boss]) ? $gpccolor[$boss] : '';
		$glist[] = $gid;
		$html .= '<tr>
             <td width="70%">'.$name.'</td>
             <td>'.$color.'</td>
           </tr>';
		if($insertLog && !$_pm['mysql'] -> query("INSERT INTO challenge_log (uid,gid) VALUES({$uid},$gid)")) return false;
	}
	if(empty($glist)) return false;
	return array('vary' => $vary, 'glist' => $glist, 'html' => $html);
}

function getGpc($num){
	global $_pm;
	if($num <= 3){
		$vary = rand(1,2);
	}else if($num == 4){
		$vary = rand(2,3);
	}else{
		$vary = rand(1,5);
	}
	$arr = $_pm['mysql'] -> getRecords("SELECT gpc,boss FROM c_gpc WHERE boss = $vary");
	if(empty($arr)){
		return array('gpc' => '0', 'boss' => $vary);
	}
	$count = count($arr) - 1;
	$gid = rand(0,$count);
	return $arr[$gid];
}
?>
