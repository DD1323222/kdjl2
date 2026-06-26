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
session_start();
define(MEM_BOSS_KEY, $_SESSION['id'] . 'boss');
define(MEM_FIGHT_KEY, $_SESSION['id'] . 'fight'); // 保存战斗信息。
//if ($_SESSION['nickname'] !='GM') exit();

require_once('../config/config.game.php');
require_once('../config/config.fuben.php');

secStart($_pm['mem']);
//加速外挂
$time = time();
$_SESSION['multi_monsters'.$_SESSION['id']] = 2;

$user	= $_pm['user']->getUserById($_SESSION['id']);
if(isset($_GET['mapid']))
{
	$sqlMap = "SELECT multi_monsters FROM map WHERE id = ".abs($_GET['mapid']);
}else{
	$sqlMap = "SELECT multi_monsters FROM map WHERE id = ".abs($user['inmap']);
	//$_GET['mapid'] = $user['inmap'];
}

$map = $_pm['mysql'] -> getOneRecord($sqlMap);
if(!$map)
{
	echo '<center>您的帐号非法操作！</center>';
	exit();
}

$chaoshenchongDituFlag=false;//神圣宠物地图标志
$bid = intval($_REQUEST['p'])>0?intval($_REQUEST['p']):intval($_SESSION["fight"]["bid"]);

$sql = "SELECT level,wx
		FROM userbb 
		WHERE uid = {$_SESSION['id']} and id = {$bid}";
$petsleval = $_pm['mysql'] -> getOneRecord($sql);
	
if($map['multi_monsters']==4)
{
	if($petsleval['wx']!=7)
	{
		die("<script language='javascript'>parent.Alert('只有神圣宠物,才可以在这里战斗！');window.location='/function/fb_Mod.php?mapid=".abs($_GET['mapid'])."'</script>");
	}
	$chaoshenchongDituFlag=true;	

	if(isset($_GET['mapid'])&&$petsleval['wx']==7){
		$sql = "UPDATE player 
				SET inmap='".abs($_GET['mapid'])."'
				WHERE id = {$_SESSION['id']}";
		$_pm['mysql'] -> query($sql);
		$user['inmap']=abs($_GET['mapid']);
	}
	
}else{
	//在这里结束
	if(
	isset($_GET['mapid'])&&
	
	(
	intval($_GET['mapid']) <= 14 && intval($_GET['mapid']) >= 11) || intval($_GET['mapid']) == 50 || intval($_GET['mapid']) == 124 || intval($_GET['mapid']) == 127 || intval($_GET['mapid']) == 143 || intval($_GET['mapid']) == 144)
	{
		$sql = "UPDATE player 
				SET inmap='".$_GET['mapid']."'
				WHERE id = {$_SESSION['id']}";
		$_pm['mysql'] -> query($sql);
		$user['inmap']=abs($_GET['mapid']);
	}
	
	if (($user['inmap']>14 && $user['inmap'] != 50 && $user['inmap'] != 124 &&  $user['inmap'] != 127  && intval($_GET['mapid']) != 143 && intval($_GET['mapid']) != 144) || $user['inmap'] < 11) // 地图限制
	{
		/*$_pm['mysql']->query("UPDATE player 
								 SET secid=2
							   WHERE id={$_SESSION['id']}");*/
		unset($_SESSION['id']);
		$_pm['mem']->memClose();
		echo '<center>您的帐号非法操作，已被冻结！</center>';
		exit();
	}
}


$userbb = $_pm['user']->getUserPetById($_SESSION['id']);
$bag    = $_pm['user']->getUserBagById($_SESSION['id']);
$fight	=	$_SESSION['fight'.$_SESSION['id']];
$_SESSION['fttime'.$_SESSION['id']] = 10;

//#########################
if (is_array($fight))
{
	   // Check time 
	   $will = (10-time()+$fight['ftime']);
	   if ($fight['ftime']+10>=time()) {
	   	$end='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 

Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-

transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=gbk" />
</head>
<!--[if IE 6]><script type="text/javascript">try{ document.execCommand

("BackgroundImageCache", false, true); } catch(e) {}
</script>
<![endif]-->
<body style="background-color: #FFFCEB;margin-top:0px;">
<center>
  <div style="margin-top:140px;"><img 

src="../images/ui/fight/loading.gif"/><div id="timev"  

style="position:absolute; text-align:center; color:#F98F2C; font-

weight:bold;font-size:2em;left: 390px; top: 160px; height: 

40px;"></div>
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
		window.parent.document.getElementById("gw").src="./function/fbfight_Mod.php?p='.$_REQUEST['p'].'&s=t";	
	}	   
	pt=1;
 }
loadtime('.($will<2?2:$will).');
</script>';
			ob_start('ob_gzip');
			echo $end;
			ob_end_flush();
			exit();
		}
}
//########################


//$mapid = intval($_REQUEST['mapid']);
$err = 0;
//if($mapid == "" || $mapid <= 0)
//{
//	$err = "0";//没有对应的副本，返回到地图;
//}
if($bid == "" || $bid <= 0)
{
	$err = "1";//没有选择主战宠物
}


//判断玩家所选宠物是否达到所对应的副本的级数要求
//得到玩家当前宠物的级数
if($err == 0){
	
	
	//得到当前副本所需要的宠物的级数
	$sql = "SELECT map.level FROM map,player WHERE map.id =player.inmap and player.id=".$_SESSION['id'];
	$mapleval = $_pm['mysql'] -> getOneRecord($sql);
	
	if(!is_array($petsleval) || !is_array($mapleval))
	{
		$err = "2";//信息出错
	}
	
	if($petsleval['level'] < $mapleval['level'])
	{
		$err = "3";//您当前所选宠物没有达到相应的级数
	}
	
}
if($err == 0){
	//判断副本刷新时间
	//得到该副本所需要的刷新的时间
	$sql = "SELECT fuben.gwid,fuben.srctime,fuben.lttime FROM fuben,player WHERE fuben.inmap = player.inmap and fuben.inmap = ".$user['inmap']."  and fuben.uid = {$_SESSION['id']}";
	$fuben = $_pm['mysql'] -> getOneRecord($sql);

	if(is_array($fuben))
	{//当玩家玩到该副本的最后一个宠物时将不会记录他的怪物
		if(empty($fuben['gwid']))
		{
			$srctime = $fuben['srctime'];
			$nowtime = time();
			$time = $nowtime - $fuben['lttime'];//实际间隔时间
			if($time < $srctime)
			{
				$err = "4";//副本地图正在刷新!
			}
			else
			{
				/*$sql = "UPDATE fuben
						SET lttime = $nowtime,gwid = '' 
						WHERE uid = {$_SESSION['id']} and inmap = {$mapid}";
				$_pm['mysql'] -> query($sql);*/
				$err = 10;
			}
		}
		else
		{
			$err = 10;
		}
	}
	else
	{
		$err = 10;
	}
	
	if($chaoshenchongDituFlag&&$petsleval['wx']<7)
	{
		$err = "44";//只有神圣宠物物才能进入
	}
}
if($err == 44){
	die("<script language='javascript'>parent.Alert('只有神圣宠物,才可以在这里战斗！');window.location='/function/fb_Mod.php?mapid=".abs($_GET['mapid'])."'</script>");
}
if($err == 4){
	die("<script language='javascript'>parent.Alert('副本刷新中!')！');window.location='/function/fb_Mod.php?mapid=".abs($_GET['mapid'])."'</script>");
}
if($err != 10){
	echo '<!--stopUser2(51='.$err.');-->';
	stopUser2(51);
	die("您现在无法进入该地图(".$err.")！");
}




// Get bb info.
$bid = intval($_REQUEST['p']);
$arrobj = new arrays();

$bb = $arrobj->dataGet(array('k' => MEM_BB_KEY, 
					  		 'v' => "if

(\$rs['id'] == '{$bid}' && \$rs['uid'] == '{$_SESSION['id']}') 

\$ret=\$rs;"
					        ),
							$userbb
					  );
if (!is_array($bb))
{
	if (!empty($fight))
	{
		$bid = $_SESSION['fight'.$_SESSION['id']]['bid'];
	}
	else $bid = $user['mbid'];
	$bb = $arrobj->dataGet(array('k' => MEM_BB_KEY, 
					  			 'v' => 

"if(\$rs['id'] == '{$bid}' && \$rs['uid'] == '{$_SESSION['id']}') 

\$ret=\$rs;"
								),
							$userbb
					     );
	if (!is_array($bb))
	{
		die('不能获得宠物数据！');
	}
}
else
{
	//
	
	
	$memeffect = unserialize($_pm['mem'] -> get('format_user_zhuangbei_'.$user['mbid']));//装备效果
	$arr = getzbAttrib($bid);
	$bb['srchp'] += $arr['hp'];
	$bb['srcmp'] += $arr['mp'];
	$bb['hp'] += $arr['hp'];
	$bb['mp'] += $arr['mp'];
	//宠物的血量和魔法的最大值的计算（加上装备的效果）；
	/*$sql = "SELECT addmp,addhp FROM userbb WHERE uid = {$_SESSION['id']} and id = {$bid}";
	$add = $_pm['mysql'] -> getOneRecord($sql);
	$bb['hp'] += $add['addhp'];
	$bb['mp'] += $add['addmp'];*/
	
	
	
	//if ($bb['hp'] <= 0) err($_bbword[rand(0,count($_bbword)-1)]);
	//金币版
		
		if($_SESSION['exptype'.$_SESSION['id']] == 1)
	{
		if((empty($_SESSION['way'.$_SESSION['id']]) || $_SESSION['way'.$_SESSION['id']] == "money") && $user['autofitflag']==1 && $user['sysautosum']>0)
		{
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
		}
		//元宝版
		else if($_SESSION['way'.$_SESSION['id']] == "yb" && $user['autofitflag']==1 && $user['maxautofitsum']>0)
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
	Header("Location:fbfight_Mod.php?a=1&p={$bid}");exit();
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
		$jnlist .="['{$rs[name]}',{$rs[level]},'{$rs[vary]}',

{$rs[wx]},'{$rs[value]}','{$rs[plus]}','{$rs['img']}',{$rs[uhp]},{$rs

[ump]},{$rs['sid']}],";
	}
}
$jnlist = substr($jnlist, 0, -1); // []#[];

// from current map choose level limit.
//根据玩家所在地图刷新怪物

$sql = "SELECT time FROM fight_log WHERE uid = {$_SESSION['id']} and vary = 2";
$timearr = $_pm['mysql'] -> getOneRecord($sql);
if(is_array($timearr)){
	$time = time();
	$ctime = $time - $timearr['time'];
	if($ctime < 1){
		die('操作太快！<!--'.$timearr['time'].'-'.$time.'-->');
		//$_SESSION['id'] = '';
	}else{
		$_pm['mysql'] -> query("UPDATE fight_log SET time = ".time()." WHERE uid = {$_SESSION['id']} and vary = 2");
	}
}else{
	$_pm['mysql'] -> query("INSERT INTO fight_log (uid,time,vary) VALUES({$_SESSION['id']},".time().",2)");
}

/*$levels = $_pm['mem']->dataGet(array('k' => MEM_MAP_KEY, //地图的相关信息
 	  						'v' => "if



(\$rs['id'] == '{$user['inmap']}') \$ret=\$rs;));"*/
//$memmapid = unserialize($_pm['mem']->get('db_mapid'));
//将取多条数据改为取单条数据
$baseMapInfo =  getBaseMapInfoById($user['inmap']);
$memmapid[$user['inmap']] = $baseMapInfo;
$levels = $memmapid[$user['inmap']];
				
if(empty($levels['gpclist']))//说明是副本地图
{
	foreach($fbinfo as $fb)
	{
		if($fb['id'] == $levels['id'])
		{
			$gwlist1 = $fb['gwid'];//得到在该副本地图中的所有怪物ID
			break;
		}
	}
}
else
{
	die("载入地图(".$user['inmap'].")出错!");
}
$gwlist = explode(",",$gwlist1);
/**###################################
*Level limit lock
###################################*/

// $idse = rand($lvl[0], $lvl[1]); // 得到怪物
$sql = "SELECT * 
		FROM fuben 
		WHERE uid = {$_SESSION['id']} and inmap = {$levels['id']}";
$fbexist = $_pm['mysql'] -> getOneRecord($sql);
if(is_array($fbexist))
{	
	foreach($gwlist as $kgw => $vgw)
	{
		if($vgw == $fbexist['gwid'])
		{
			$numgw = $kgw;
			break;
		}
		else
		{
			$numgw = count($gwlist);
		}
	}
	
	$n = count($gwlist) - 1;
	$nowtime = time();
	$time = $nowtime - $fbexist['lttime'];//实际间隔时间
	$waittime = $fbexist['srctime'] - $time;//实际需要等待时间
	if($numgw > $n)
	{//判断副本是否处于刷新时间
		//$sql = "SELECT * FROM fuben WHERE uid = {$_SESSION['id']} and inmap = {$user['inmap']}";
		//$wait = $_pm['mysql'] -> getOneRecord($sql); 		
		if($time > $fbexist['srctime'])
		{
			$numgw = 0;
			$sql = "UPDATE fuben 
					SET lttime = '',gwid = ''
					WHERE uid = {$_SESSION['id']} and inmap = {$user['inmap']}";
			$_pm['mysql'] -> query($sql);
		}
		else
		{
			die("副本刷新中,您还需要等待{$waittime}秒!");
		}
	}
	else
	{
		if($time > $fbexist['srctime'])
		{
			$numgw = 0;
		}
	}
}
else
{
	$numgw = 0;
}

$idse = $gwlist[$numgw];//得到当前玩家要遇到的怪物
if($_SESSION['fight' . $_SESSION['id']]['gid']==$idse&&$_SESSION['fight' . $_SESSION['id']]['hp']>0)//当前打的怪物是数据库取出来的怪物,并且怪物没有死,说明玩家按了后退
{
	unset($_SESSION['fight' . $_SESSION['id']]);
	header("refresh:2;url=fbfight_Mod.php?a=2&p={$bid}");
	exit("Loading...");
}

$_SESSION['gwcdie'.$_SESSION['id']] = $idse; 
/*$gw = $_pm['mem']->dataGet(array('k' => MEM_GPC_KEY, 
						   'v' => "if(\$rs

['id'] == '{$idse}') \$ret=\$rs;"
					));*/
//$memgpcid = unserialize($_pm['mem']->get('db_gpcid'));
$idse = trim($idse);
//$gw = $memgpcid[$idse];
$gw = getBaseGpcInfoById($idse);//改为单条取记录

if (count($gw)<1)
{
	Header("Location:fbfight_Mod.php?a=3&p={$bid}");exit();
}
else
{	

	$gw['wx'] = getWx($gw['wx']);

	$gwinfo="['{$gw['name']}',{$gw['level']},'{$gw['wx']}',{$gw

['ac']},{$gw['mc']},{$gw['hp']},{$gw['mp']},'{$gw['skill']}','{$gw

['imgstand']}','{$gw['imgack']}','{$gw['imgdie']}',{$gw['id']}]";

	
	$test = $_SESSION['fight'.$_SESSION['id']];
	//Update fightting stats.
	if (!is_array($test))
	{		
		$_SESSION["fight".$_SESSION['id']]	= array

('uid'=>$_SESSION['id'],
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
	   	$end='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 

Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-

transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=gbk" />
</head>
<!--[if IE 6]><script type="text/javascript">try{ document.execCommand

("BackgroundImageCache", false, true); } catch(e) {}
</script>
<![endif]-->
<body style="background-color: #FFFCEB;margin-top:0px;">
<center>
  <div style="margin-top:140px;"><img 

src="../images/ui/fight/loading.gif"/><div id="timev"  

style="position:absolute; text-align:center; color:#F98F2C; font-

weight:bold;font-size:2em;left: 390px; top: 160px; height: 

40px;"></div>
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
			if (empty($bbfzp)) $bbfzp = "['".$v

['name']."',".$v['sums'].','.$v['id']."]";
			else $bbfzp .= ",['".$v['name']."',".$v

['sums'].','.$v['id']."]";
		}
		else if ($v['varyname'] == 3 && $v['sums']>0)
		{
			if (empty($catcharr)) $catcharr = "['".$v

['name']."',".$v['sums'].','.$v['id']."]";
			else $catcharr .= ",['".$v['name']."',".$v

['sums'].','.$v['id']."]";
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
$_pm['mem']->memClose();

//###########################
// @Load template.
//###########################

$fn='tpl_fbfight.html';
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
     x:ev.clientX + document.body.scrollLeft - 

document.body.clientLeft,
     y:ev.clientY + document.body.scrollTop     - 

document.body.clientTop
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
	var ajax=new Ajax.Request(\'../function/exit1c.php?

ssid=\'+mousePos.x+mousePos.y, opt);
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
	var ajax=new Ajax.Request(\'../function/exit1.php?

ssid=\'+window.parent.waittime, opt);
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
					 "#fttime#"
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
					 $_SESSION['fttime'.$_SESSION['id']]
				);

	$fat = str_replace($src, $des, $tpl);
}



// gzip echo. if maybe.
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
flush();
ob_start('ob_gzip');
echo $fat;
ob_end_flush();

function err($str)
{
	die('<center>
			<div style="margin-top:100px;padding:5px;font-

size:12px; line-height:1.7;width:99%;height:100px;overflow:hidden;">'. 

$str .'<br/>
				<<<a href="javascript:history.go(-1);">

返回村庄</a>
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
	if (!is_array($gs)) return false;

	$exists = $_pm['mysql']->getOneRecord("SELECT 

id,rtime,gid,glock,dtime
									

	     FROM boss_refresh
									

		WHERE gid={$gs['id']}
									

		LIMIT 0,1
									

	 ");
	
	//$_pm['mysql']->query("SET autocommit=0");
	//$_pm['mysql']->query("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
	$_pm['mysql']->query("START TRANSACTION");
	if (is_array($exists))
	{
		if (($exists['dtime']+1*3600)>=time() || 
			 ($exists['glock']==1 && ($exists['rtime']

+120)>time())
		   ) return false;
		else if( ($exists['dtime']+1*3600)<time() && $exists

['glock']==0)
		{
			$_pm['mysql']->query("UPDATE boss_refresh
								     

SET rtime=".time().",fightuid={$_SESSION['id']},glock=1
								   

WHERE gid={$gs['id']} and (dtime+3600)<".time()."
								");
		}
	    else if($exists['glock']==1 && ($exists['rtime']+600)<time

())
	    {
			$_pm['mysql']->query("UPDATE boss_refresh
									

SET rtime=".time().",fightuid={$_SESSION['id']},glock=1
								  WHERE 

gid={$gs['id']} and glock=1 and (rtime+600)<".time()."
								");
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
		$_pm['mysql']->query("INSERT INTO boss_refresh

(gid,rtime,fightuid,glock)
							  VALUES({$gs

['id']},".time().",{$_SESSION['id']},1)
							");
	}
	if (!$_pm['mysql']->query("COMMIT")){
			$_pm['mysql']->query("ROLLBACK");
			return false;
		}

	$task = new task();
	$task->saveGword("遇上了沉睡中的[".$gs['name']."]，勇士请赶快去

消灭它吧！");
	return true;
}
/*

$str=print_r($gw,1).print_r($_SESSION["fight".$_SESSION['id']],1).print_r($_GET,1).'->$fbexist[\'gwid\']='.$fbexist['gwid'].',headers_list='.print_r(headers_list(),1);
$str=str_replace("\n",'\\n',$str);
echo '<script >alert("'.$str.'")</script>';
*/

?>