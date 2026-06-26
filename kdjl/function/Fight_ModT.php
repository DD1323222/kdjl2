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
define(MEM_BOSS_KEY, $_SESSION['id'] . 'boss');
define(MEM_FIGHT_KEY, $_SESSION['id'] . 'fight'); // 保存战斗信息。
//if ($_SESSION['nickname'] !='GM') exit();

secStart($_pm['mem']);

$user	= $_pm['user']->getUserById($_SESSION['id']);
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
if($mapflag != 1 && !in_array($user['inmap'],$openmap) && $user['inmap'] < 15)
{
	die("地图开放时间到期，或者地图未开启！");
}

if ($user['inmap'] > 10 && $user['inmap'] < 15) // 地图限制
{
	/*
	$_pm['mysql']->query("UPDATE player 
							 SET secid=2
						   WHERE id={$_SESSION['id']}");
					*/
	unset($_SESSION['id']);
	$_pm['mem']->memClose();
	echo '<center>您的帐号非法操作，服务器强制断线！</center>';
	exit();
}

//#########################
if (is_array($fight))
{
	   // Check time 
	   $will = (10-time()+$fight['ftime']);
	   if ($fight['ftime']+10>=time()) {
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
  <div style="margin-top:140px;"><img src="../images/ui/fight/loading.gif"/><div id="timev"  style="position:absolute; text-align:center; color:#F98F2C; font-weight:bold;font-size:2em;left: 390px; top: 160px; height: 40px;"></div>
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
		window.setTimeout("pause()",100);
		return;
	}
	else{
		document.getElementById("timev").innerHTML = m--;
		readH=window.setTimeout("loadtime("+m+");", 1000);
	}
}
function pause()
{   if (pt==1) return;
	window.parent.document.getElementById("gw").src="./function/Fight_Mod.php?p='.$_REQUEST['p'].'&s=t";		   
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
$bid = intval($_REQUEST['p']);
$arrobj = new arrays();

$bb = $arrobj->dataGet(array('k' => MEM_BB_KEY, 
					  		 'v' => "if(\$rs['id'] == '{$bid}' && \$rs['uid'] == '{$_SESSION['id']}') \$ret=\$rs;"
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
					  			 'v' => "if(\$rs['id'] == '{$bid}' && \$rs['uid'] == '{$_SESSION['id']}') \$ret=\$rs;"
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
	// ============================== 装备效果开始 ==========================================
	//宠物的血量和魔法的最大值的计算（加上装备的效果）；
	$arr = getzbAttrib($bag,$bid);
		echo '<b>'.__FILE__.'-->'.__LINE__.'</b><br/><pre>=';
	var_dump($arr	);
	echo '</pre>';


/*
	$x = $_pm['mysql']->getRecords("SELECT zbing,zbpets,pid FROM userbag where uid=47 and pid=932");
	$_SESSION['dbg_equip_attr_2'] .= " <h1><font color=\"#B48D03\">".$_SERVER['PHP_SELF']."</font></h1>".date("Y-m-d H:i:s")." arr =>".print_r($arr ,1)." <br/> x=>".print_r($x,1);
*/
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
	Header("Location:Fight_Mod.php?p={$bid}");exit();
}

$jlistarr = split(',', $jlist);
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
//$levels = $_pm['mem']->dataGet(array('k' => MEM_MAP_KEY, 
 //	  						'v' => "if(\$rs['id'] == '{$user['inmap']}') \$ret=\$rs;"
//					));
$bb_index_name = unserialize($_pm['mem']->get('db_map_index_name'));	
$levels = $bb_index_name[$user['inmap']];

/**###################################
*Level limit lock
###################################*/
if (!is_array($levels) || $levels['level']<1 )
{
	$levels['level']="1,15";
}
$lvl = explode(',', $levels['level']);

$idse = rand($lvl[0], $lvl[1]); // <<<<<<<

$gw = $_pm['mem']->dataGetAll(array('k' => MEM_GPC_KEY, 
						   'v' => "if(\$rs['level'] == '{$idse}') \$ret=\$rs;"
					));

if (count($gw)<1)
{
	Header("Location:Fight_Mod.php?p={$bid}");exit();
}
else
{	
	if (count($gw)==1) $gw = $gw[0];
	else
	{
		$min	= $gw[0];
		$n		= rand(1, count($gw));
		$gw		= $gw[$n-1];
	}
		/*加入遇BOSS的时间限制。*/	 
	if ($gw['boss'] == 3 || $gw['boss']==4)
	{
		if (bossCheck($gw) === false || $gw['boss']==4)
		{
			Header("Location:Fight_Mod.php?p={$bid}");exit;
		}
	}

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
  <div style="margin-top:140px;"><img src="../images/ui/fight/loading.gif"/><div id="timev"  style="position:absolute; text-align:center; color:#F98F2C; font-weight:bold;font-size:2em;left: 390px; top: 160px; height: 40px;"></div>
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
		window.setTimeout("pause()",100);
		return;
	}
	else{
		document.getElementById("timev").innerHTML = m--;
		readH=window.setTimeout("loadtime("+m+");", 1000);
	}
}
function pause()
{   if (pt==1) return;
	window.parent.document.getElementById("gw").src="./function/Fight_Mod.php?p='.$_REQUEST['p'].'&s=t";		   
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
$_pm['mem']->memClose();

//###########################
// @Load template.
//###########################

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
	
	$_pm['mysql']->query("SET autocommit=0");
	$_pm['mysql']->query("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
	$_pm['mysql']->query("START TRANSACTION");
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
?>
