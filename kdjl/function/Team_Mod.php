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

define(MEM_FIGHTUSER_KEY, $_SESSION['id'] . 'fuser');
if($_REQUEST['from'] !=1)
{
	secStart($_pm['mem']);
}
$user		= $_pm['user']->getUserById($_SESSION['id']);
$petsarr	= $_pm['user']->getUserPetById($_SESSION['id']);
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

require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
$s=new socketmsg();
// 5.4后不支持挂
$tcls=new team($_SESSION['team_id'],$s);
$myState=$tcls->checkMyTeam();
$teamState=$tcls->getTeamState();
/*
$dataNow['team_fuben_card_step_num']=$oldData['team_fuben_card_step_num'];
*/
$tcls->setTeamState(array(
							'team_fuben_card_step_num'=>-1
							));	
$tcls->clearTeamState();

$teamState=$tcls->getTeamState();
if($teamState['team_fuben_boss'])
{
	$tcls->clearTeamFubenData();
	header('location:/function/Fight_Mod.php');
	die();
}

//$_pm['mem']->del('tarot_info_'.$_SESSION['team_id']);

if(isset($_SESSION['team_id'])){
	$isleader=$tcls->isTeamLeader($_SESSION['id'],$_SESSION['team_id']);
	if(isset($_GET['tact']))
	{
		if($_GET['tact']=='quit')
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
		}else if($_GET['tact']=='swap'){
			if($isleader)
			{
				//$_SESSION['GoToCity']=0;
				echo '<script language="javascript">
				parent.Alert("不允许队长暂离!");
				window.location="/function/Team_Mod.php"</script>';
				die();
			}
			$tcls->swapTeamState();
			if(!empty($_SERVER['HTTP_REFERER']))
				header('location:'.$_SERVER['HTTP_REFERER']);
			else
				header('location:/function/City_Mod.php');
				
			die();
		}else{
			$teamState=$tcls->getTeamState();
			if(strpos($teamState['fight_html'],'<body')!==false&&strlen($teamState['fight_html'])>=100)
			{
				header("Cache-Control: no-cache, must-revalidate");
				header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
				echo $teamState['fight_html'];
				die();
			}
		}
	}
	if(isset($_GET['returnv']))
	{
		if($isleader){
			$tcls->returnVi();
		}
	}
	if($isleader)
	{
		$tcls->clearTeamState();
		$tcls->reliveAll(0);
		$tcls->returnVi();
		$_REQUEST['n']=$_SESSION['team_inmap'];
	}else if($myState<1){
		//暂离状态什么也不做
	}else{
		$teamState=$tcls->getTeamState();
		if(strpos($teamState['fight_html'],'<body')!==false&&strlen($teamState['fight_html'])>=100)
		{
			header("Cache-Control: no-cache, must-revalidate");
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			echo $teamState['fight_html'];
			die();
		}
		$_REQUEST['n']=$_SESSION['team_inmap'];
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


$_SESSION['exptype'.$_SESSION['id']] = "";
if($_SESSION['way'.$_SESSION['id']] == "" || $_SESSION['way'.$_SESSION['id']] == "money")
{
	$num = $user['sysautosum'];
}
else if($_SESSION['way'.$_SESSION['id']] == "yb")
{
	$num = $user['maxautofitsum'];
}
$_pm['mysql']->query("UPDATE player
					     SET autofitflag=0
					   WHERE id={$_SESSION['id']}
					");
$n = intval($_REQUEST['n']);
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

if(isset($_SESSION['team_id'])&&$_REQUEST['n']!=$_SESSION['team_inmap']) $_REQUEST['n']=$_SESSION['team_inmap'];

$ifr='';
if(isset($_SESSION['team_id'])&&$_REQUEST['n']==$_SESSION['team_inmap'])
{
	$ifr='<iframe src="/function/team.php?b1&showAllTeamsTime=0&rd=" style="position:absolute;z-index:0;top:1000px;" width="30" height="30"  class="wgframe"></iframe>';
	$isleader=$tcls->isTeamLeader($_SESSION['id'],$_SESSION['team_id']);

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

$memmapid = unserialize($_pm['mem']->get('db_mapid'));
if($n==0)
{
	$rsInmap=$_pm['mysql']->getOneRecord('select inmap from player where id='.$_SESSION['id']);
	if($rsInmap) $n=$rsInmap['inmap'];
	if($n==0)  $n=1;
}

if ($n>0)
{
	$map = $memmapid[$n];
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
$lmt = explode(',', $map['level']);
if (is_array($petsarr))
{
	foreach ($petsarr as $k =>$rs) // Will filter in muchang pets for current user.
	{
		/*if ($rs['muchang'] == 1) continue;
		if ($kk == 0) {$sel = 100;$selid=$rs['id'];}
		else $sel = 50;
		if($rs['level']==0) $rs['level']=1;*/
		//if($rs['muchang'] == 1 || $rs['muchang'] == 3 || $rs['muchang'] == 4 || $rs['muchang'] == 7 || $rs['muchang'] == 5 || $rs['muchang'] == 6 ) continue;
		if($rs['muchang'] != 0){
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
		$pets[$kk++] = "<img src='".IMAGE_SRC_URL."/bb/{$rs['cardimg']}' onClick=\"Setbbs({$rs['id']},{$rs['level']},{$lmt['0']},this);\" alt=\"{$rs['name']}\" style='cursor:pointer;filter:alpha(opacity={$sel});' id='i{$kk}'> ";
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
		if ($tuser['id'] == $_SESSION['id']) continue;
		if (is_array($tuser) && $tuser['inmap']==$n)
		{
			$online .= '
			<li>
				<div class="zxwj_list "><img src="../images/ui/team/ren.gif" width="13" height="15" />                    </div>
				<div class="zxwj_list2 " style="cursor:pointer" onclick="TeamChoose(\''.$tuser['nickname'].'\','.$tuser['id'].',event);">'.$tuser['nickname'].'
				</div>
			</li>';
		}
	}
}
//$online="<tr><td width=200>暂时关闭显示玩家列表</td><td></td></tr>\n";

// Save map position to user.
$user['inmap'] = $map['id'];
$_pm['mysql']->query("UPDATE player 
						 SET inmap='{$map['id']}'
					   WHERE id = {$_SESSION['id']}
					");
if($_REQUEST['from'] == 1)
{
	$_pm['mysql']->query("UPDATE player 
						 SET bot_map_id='{$map['id']}'
					   WHERE id = {$_SESSION['id']}
					");
}
//$_pm['user']->updateMemUser($_SESSION['id']);
//###########################
// @Load template.
//###########################

$gw=array();
$monsters=split(",",$map['gpclist']);
if($monsters){
	foreach ($monsters as $v)
	{
		$gw[]='<span onclick="copyWord(\'怪物-'.$v.'\');">'.$v.'</span>';
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
	$memgpc = unserialize($_pm['mem'] -> get('db_gpcid'));
	$gpccolor = array(5=>'白',6=>'黄',7=>'蓝',8=>'紫',9=>'红');
	$_pm['mysql'] -> query("CREATE TABLE if not exists `challenge_log` (`id` int(11) NOT NULL AUTO_INCREMENT,`uid` int(11) DEFAULT '0',`gid` int(11) DEFAULT '0',PRIMARY KEY (`id`)) ENGINE=MEMORY");
	$carr = $_pm['mysql'] -> getOneRecord("SELECT nums,lastvtime,vary,nums,snums FROM challenge WHERE uid = {$_SESSION['id']}");
	//最大打30分钟
	$time = time();
	if(empty($carr)){
		//增加
		$garr = getGpc(1);
		$vary = $garr['boss'];
		$snum = 3;
		$snum1 = 2;
		$glist = explode(',',$garr['gpc']);
		foreach($glist as $v){
			$gpclist .= '<tr>
             <td width="70%">'.$memgpc[$v]['name'].'</td>
             <td>'.$gpccolor[$memgpc[$v]['boss']].'</td>
           </tr>';
			$_pm['mysql'] -> query("INSERT INTO challenge_log (uid,gid) VALUES({$_SESSION['id']},$v)");
		}
		$_pm['mysql'] -> query("INSERT INTO challenge (uid,lastvtime,gid,vary,nums,snums) VALUES({$_SESSION['id']},$time,{$glist[0]},$vary,0,0)");
	}else{
		//是否刷新
		$yes = date('Ymd',$carr['lastvtime']);
		$yes1 = date('Ymd',$time-24*3600);
		if($yes1 >= $yes){//刷新
			//删除昨天的怪,重新取怪
			$_pm['mysql'] -> query("DELETE FROM challenge_log WHERE uid = {$_SESSION['id']}");
			$garr = getGpc(1);
			$vary = $garr['boss'];
			$snum = 3;
			$snum1 = 2;
			$glist = explode(',',$garr['gpc']);
			foreach($glist as $v){
				$gpclist .= '<tr>
						 <td width="70%">'.$memgpc[$v]['name'].'</td>
						 <td>'.$gpccolor[$memgpc[$v]['boss']].'</td>
					   </tr>';
				$_pm['mysql'] -> query("INSERT INTO challenge_log (uid,gid) VALUES({$_SESSION['id']},$v)");
			}
			$_pm['mysql'] -> query("UPDATE challenge SET lastvtime = $time,gid = {$glist[0]},vary = $vary,nums = 0,snums = 0,flag = 0 WHERE uid = {$_SESSION['id']}");
		}else{
			$glist = $_pm['mysql'] -> getRecords("SELECT gid FROM challenge_log WHERE uid = {$_SESSION['id']}");
			if(empty($glist)){
				//重新取怪
				$snum = (3 - $carr['nums']) > 0?3 - $carr['nums']:0;
				$snum1 = (2 - $carr['snums']) > 0?2 - $carr['snums']:0;
				$anum = $carr['nums'] + 1;
				$garr = getGpc($anum);
				$vary = $garr['boss'];
				$glist = explode(',',$garr['gpc']);
				foreach($glist as $v){
					$gpclist .= '<tr>
							 <td width="70%">'.$memgpc[$v]['name'].'</td>
							 <td>'.$gpccolor[$memgpc[$v]['boss']].'</td>
						   </tr>';
					$_pm['mysql'] -> query("INSERT INTO challenge_log (uid,gid) VALUES({$_SESSION['id']},$v)");
				}
				$_pm['mysql'] -> query("UPDATE challenge SET lastvtime = $time,gid = {$glist[0]},vary = $vary,nums = nums + 1 WHERE uid = {$_SESSION['id']}");
			}else{//得到怪物列表
				foreach($glist as $v){
					$gpclist .= '<tr>
             <td width="70%">'.$memgpc[$v['gid']]['name'].'</td>
             <td>'.$gpccolor[$memgpc[$v['gid']]['boss']].'</td>
           </tr>';
				}
				$vary = $carr['vary'];
				$snum = (3 - $carr['nums']) > 0?3 - $carr['nums']:0;
				$snum1 = (2 - $carr['snums']) > 0?2 - $carr['snums']:0;
			}
		}
	}
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
				$i = $k+1;
				$online .= '<tr>
				  <td width="20" height="23">&nbsp;</td>
				  <td width="40">'.$i.'</td>
				  <td width="90">'.$tuser['nickname'].'</td>
				  <td>'.$tuser['tgt'].'</td>
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
	$sql = "SELECT tgt,tgttime,uid FROM player_ext WHERE uid = {$_SESSION['id']}";
	$uarr = $_pm['mysql'] -> getOneRecord($sql);
	if(!is_array($uarr)){
		$_pm['mysql'] -> query("INSERT INTO player_ext (uid,bbshow) VALUES ({$_SESSION['id']},5)");
		$tgt = 1;
	}else{
		if($uarr['tgttime'] > 0){
			$time = time();
			$ctime = $time - $uarr['tgttime'];
			$day = 24*3600;
			if($ctime > $day){
				$_pm['mysql'] -> query("UPDATE player_ext SET tgt=0,tgttime=0 WHERE uid = {$_SESSION['id']}");
			}
		}
		$tgt = $uarr['tgt'] + 1;
		
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
					 $user['nickname'],
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
		return false;
	}
	$count = count($arr) - 1;
	$gid = rand(0,$count);
	return $arr[$gid];
}
?>