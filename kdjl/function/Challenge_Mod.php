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
if(isset($_SESSION['team_id']))
{
	die('退出出队伍才可以进入！');
}
secStart($_pm['mem']);

$user	= $_pm['user']->getUserById($_SESSION['id']);

if(isset($_GET['guild_fight']))
{
	require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
	$s=new socketmsg();
	$guild=new guild($s);
	if(!$myGuild=$guild->getMyGuildInfo())
	{
		$guild->clearGuildFightSession();
		alert('您没有加入一个家族！'.mysql_error(),'window.location="/function/Expore_Mod.php"');
		die();
	}
	
	if(!$guild->checkGuildFightTime())
	{
		$guild->clearGuildFightSession();
		alert('现在不是家族战斗时间！'.mysql_error(),'window.location="/function/guild_battle_mod.php"');
		die();
	}
	
	$_pm['mysql']->query('delete from guild_challenges where flags=0');
	$changeGuid=$guild->getChanllengeGuildInfo($myGuild['id']);	
	if(!is_array($changeGuid))
	{
		$guild->clearGuildFightSession();
		alert($changeGuid.mysql_error(),'',true);
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
	if(!is_array($changeGuidMembers))
	{
		alert($changeGuidMembers.mysql_error(),'',true);
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
	$_REQUEST['cp']=$enemyGuildMemberInfo['mbid'];
	$_SESSION['guild_fight_bid']=$enemyGuildMemberInfo['mbid'];
	if($_REQUEST['cp']==0)
	{
		header("location:".$_SERVER['REQUEST_URI']);
		echo 'Loading...';
		die();
	}
	$_REQUEST['p']=$user['mbid'];
	$_pm['mysql']->query('update player set inmap=0 where id='.$_SESSION['id']);
}

$fortress_flag=false;

if(isset($_SESSION['fortress_card_id'])&&$_SESSION['fortress_card_id']>0)
{
	$setting = $_pm['mem']->get('db_welcome1');
	if(!is_array($setting)) $setting=unserialize($setting);
	if(!is_array($setting))
	{
		die('后台配置数据读取失败(1)！'.print_r($setting,1));
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
	$user_fortress=$_pm['mysql']->getOneRecord('select cur_gpc_id,bb_id,at_section_num,fv_result from '.$table_name.' where user_id='.$_SESSION['id']);
	
	$sql_extra='';
	$get_score=0;
	if($user_fortress['cur_gpc_id']!=0)//上个怪物没有打死
	{
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
	}

	if(!$user_fortress)
	{
		header('location:/function/fortress_Mod.php');
		die('您没有加入要塞！');
	}

	$user['mbid']=$user_fortress['bb_id'];
	$_pm['mysql']->query('update player set mbid='.$user_fortress['bb_id'].' where id='.$_SESSION['id']);
	//$_SESSION['fortress_card_id']=0;
	$_SESSION['fortress_pass']=3;
	
	$fortress_flag=true;
	$monsters_id=0;
	//if(rand(1,100)<=30)
	
	$fortress_users=$_pm['mysql']->getRecords('select bb_id from '.$table_name.' where user_id!='.$_SESSION['id'].' and at_section_num='.$user_fortress['at_section_num']);
	$ct=count($fortress_users);
	if($ct<2){
		$_pm['mysql']->query('delete from '.$table_name.' where at_section_num='.$user_fortress['at_section_num']);
		die('<script language="javascript">parent.Alert("进入要塞的玩家太少！");window.location="/function/Expore_Mod.php"</script>');
	}
	
	if(rand(1,100)<=60)
	{
		if(!isset($setting['fortress']))
		{
			die('缺少活动开启设定(fortress)！');
		}
		$set=explode(" ",$setting['fortress']);
		$set=explode(',',$set[$user_fortress['at_section_num']-1]);
		$monsters=explode('|',$set[4]);
		$ct=count($monsters);
		$key=rand(1,$ct);
		$monsters_id=$monsters[$key-1];
		$gw	= $_pm['mysql']->getOneRecord("SELECT *
									FROM gpc
								   WHERE id={$monsters_id}
								");
		
		$_SESSION['fortress_gw']=$gw;
		$gw['srchp']=$gw['hp'];
		$gw['srcmp']=$gw['mp'];
	}else{
		$key=rand(1,$ct);
		$monsters_id=$fortress_users[$key-1]['bb_id'];
		$gw	= $_pm['mysql']->getOneRecord("SELECT *
									FROM userbb
								   WHERE id={$monsters_id}
								");
		
		$_SESSION['fortress_gw']=$monsters_id;
	}
	$gw['name']='要塞怪物';
	$_SESSION['fight'.$_SESSION['id']]['ftime']=10;
	$_pm['mysql']->query('update '.$table_name.' set cur_gpc_id='.$monsters_id.$sql_extra.',score=score+'.$get_score.' where user_id='.$_SESSION['id']);
}

define(MEM_BOSS_KEY, $_SESSION['id'] . 'boss');
define(MEM_FIGHT_KEY, $_SESSION['id'] . 'fight'); 

error_reporting(E_ALL&~E_NOTICE);
$userbb = $_pm['user']->getUserPetById($_SESSION['id']);
$bag    = $_pm['user']->getUserBagById($_SESSION['id']);
$fight	=	$_SESSION['fight'.$_SESSION['id']];

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
$bgtype = $arr['img'];
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
if (is_array($fight))
{
	   // Check time 
	   $will = (300-time()+$fight['ftime']);
	   if ($fight['ftime']+300>=time()) {
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
function loadtime(m){
	
	document.getElementById("timev").innerHTML = m--;
	if(m==-1) 
	{	
		location.reload();
		return;
	}
	else{
		readH=window.setTimeout("loadtime("+m+");", 1000);
	}
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
$bid = @mysql_real_escape_string(intval($_REQUEST['p']));
$cpid = @mysql_real_escape_string(intval($_REQUEST['cp']));

$arrobj = new arrays();
if($bid==0){
	if (!empty($fight)&&$_SESSION['fight'.$_SESSION['id']]['bid']>0)
	{
		$bid = $_SESSION['fight'.$_SESSION['id']]['bid'];
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
							 'v' => "if(\$rs['id'] == '{$bid}' && \$rs['uid'] == '{$_SESSION['id']}') \$ret=\$rs;"
							),
						$userbb
					 );

if (!is_array($bb))
{
	die('不能获得宠物数据！');
}
else
{
	// By field order.
	$bb['wx'] = getWx($bb['wx']);
	$bbinfo = "['{$bb['name']}',{$bb['level']},'{$bb['wx']}',{$bb['ac']},{$bb['mc']},{$bb['hp']},{$bb['mp']},'{$bb['skillist']}','{$bb['imgstand']}','{$bb['imgack']}','{$bb['imgdie']}',{$bid},'{$bb['srchp']}','{$bb['srcmp']}','{$bb['nowexp']}','{$bb['lexp']}']";
}

// Get detail jn info.
$tjn = explode(",", $bb['skillist']);
foreach($tjn as $mkey => $n)
{
	$tt = explode(":", $n);
	$jlist .= $tt[0] . ",";
}
$jlist =	substr($jlist, 0, -1);
$bjn   =	$_pm['user']->getUserPetSkillById($_SESSION['id']);

if (!is_array($bjn))
{
	Header("Location:Challenge_Mod.php?p={$bid}&cp={$cpid}");exit();
}

$jlistarr = explode(',', $jlist);
foreach($bjn as $k => $rs)
{
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
		$jnlist .="['{$rs[name]}',{$rs[level]},'{$rs[vary]}',{$rs[wx]},'{$rs[value]}','{$rs[plus]}','{$rs['img']}',{$rs[uhp]},{$rs[ump]},{$rs['sid']}],";
	}
}
$jnlist = substr($jnlist, 0, -1); // []#[];

if(!$fortress_flag){
	// 获取被挑战玩家的宠物信息。
	$gw	= $_pm['mysql']->getOneRecord("SELECT *
									FROM userbb
								   WHERE id={$cpid}
								");
}
if (!is_array($gw))
{
	die('……');
}

	$gw['wx'] = getWx($gw['wx']);
	
	$gw['hp']=$gw['srchp'];
	
	$gwinfo="['{$gw['name']}',{$gw['level']},'{$gw['wx']}',{$gw['ac']},{$gw['mc']},{$gw['hp']},{$gw['mp']},'{$gw['skill']}','{$gw['imgstand']}','{$gw['imgack']}','{$gw['imgdie']}',{$gw['id']}]";
	
	$test = $_SESSION['fight'.$_SESSION['id']];
	//Update fightting stats.
	if (!is_array($test))
	{		
		$_SESSION["fight".$_SESSION['id']]	= array('uid'=>$_SESSION['id'],
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
	   $will = (300-time()+$fight['ftime']);
	   if ($fight['ftime']+300 >= time()) {
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
function loadtime(m){
	
	document.getElementById("timev").innerHTML = m--;
	if(m==-1) 
	{	
		location.reload();
		return;
	}
	else{
		readH=window.setTimeout("loadtime("+m+");", 1000);
	}
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
		$_SESSION["fight".$_SESSION['id']]=$r;
	}


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
$_pm['mem']->set(array('k' =>MEM_USER_KEY, 'v' => $user));
$_pm['mem']->set(array('k' =>MEM_USERBB_KEY, 'v' => $userbb));
$_pm['mem']->set(array('k' =>MEM_USERBAG_KEY, 'v' => $bag));
$_pm['mem']->memClose();

//###########################
// @Load template.
//###########################

$fn='tpl_challenge.html';
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
					 $_SESSION['nickname'],
					 $bb['headimg'],
					 $bbfzp,
					 $catcharr,
					 $user['inmap'],
					 $mouse,
			         $gw['username'],
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
