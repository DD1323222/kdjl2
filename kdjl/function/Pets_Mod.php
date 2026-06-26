<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.07.14
*@Usage: pets info
*@Note: none
*/
session_start();
require_once('../config/config.game.php');

secStart($_pm['mem']);

$uid = $_GET['uid'];
if(!empty($uid)){
	$sql = sprintf("select id from player where nickname='".$_REQUEST['uid']."' limit 1");
	$res  = $_pm['mysql']->getOneRecord($sql);
	$userid=$res[id];
}else{
	$userid= $_SESSION['id'];
}
$user		= $_pm['user']->getUserById($userid);
$petsAll	= $_pm['user']->getUserPetById($userid);
$bag		= $_pm['user']->getUserBagById($userid);
$sk			= $_pm['user']->getUserPetSkillById($userid);
$skillsys	= unserialize($_pm['mem']->get(MEM_SKILLSYS_KEY));


if (isset($_REQUEST['pid']) && intval($_REQUEST['pid'])>0)
	 $pid = intval($_REQUEST['pid']);
else $pid=0;
$kk = 0;
$pd = 0;
if(is_array($petsAll))
{
	foreach ($petsAll as $k =>$rs) // Will filter in muchang pets for current user.
	{
		$ii = $kk;
		if ($rs['muchang'] != 0) continue;
		if($pid == 0 && $rs['id'] == $user['mbid'])
		{
			$sel = 100;
			$selidinit= $rs['id'];
			$pd	= $rs;
			$mbid = $user['mbid'];
		}
		else
		{
			if($rs['id'] == $pid)
			{
				$sel = 100;
				$selidinit= $rs['id'];
				$pdinit= $rs;
				$mbid = $rs['id'];
			}
			else $sel = 50;
		}
		$sellv = $sel / 100;
		//opacity: 1; filter : progid:DXImageTransform.Microsoft.Alpha(style=0,opacity=100,finishOpacity=100);
		
		$kk++;
		$pets[$ii] = "<img onclick='Setbb({$rs['id']},this,{$user['mbid']});' src='".IMAGE_SRC_URL."/bb/{$rs['cardimg']}' style='cursor:hand;opacity: ".$sellv."; filter : progid:DXImageTransform.Microsoft.Alpha(style=0,opacity=".$sel.",finishOpacity=100);' id='i{$kk}'>";
		$pets_look[$ii] = "<img src='".IMAGE_SRC_URL."/bb/{$rs['cardimg']}' style='cursor:hand;opacity: ".$sellv."; filter : progid:DXImageTransform.Microsoft.Alpha(style=0,opacity=".$sel.",finishOpacity=100);' id='i{$kk}'>";
		$str[$ii] = "<em><a onclick='Setbb({$rs['id']},this,{$user['mbid']});'>".$rs['name']."<br />LV ".$rs['level']."</a></em>";
		if ($ii==3) break;
	}
}
if(!isset($_GET['uid'])){
// save mbid.
$_pm['mysql']->query("UPDATE player
						 SET mbid={$user['mbid']}
					   WHERE id={$_SESSION['id']}
					");
// refresh cache
$_pm['user']->updateMemUser($_SESSION['id']);
}
if(!is_array($pd))
{
	
	$pd		= $pdinit;
}
$selid	= $selidinit;
$petszb = array();
$alltakeoff;
$petAllPid;
$petAllUserPid;
if (is_array($bag))
{
	foreach ($bag as $k => $rs)
	{
		if ($rs['varyname'] == 9 && $rs['zbing'] == 1 && $rs['zbpets'] == $pd['id'])
		{
			if ($rs['requires'] != '') 
			{
				$t = split(',', 
					       str_replace(array('lv','wx'), array('等级','五行'), $rs['requires'])
					      );
				$wx = str_replace($_props['wxs'], $_props['wxd'], $t[1]);
			}
			else $t[0]= $wx= '无';
			
			$zbeffect = zbAttrib($rs['effect']);
			$petszb[$rs['postion']] = '<img  src="'.IMAGE_SRC_URL.'/props/'.$rs['img'].'" border=0  onmouseover="showTip('.$rs['id'].','.$pd['id'].',1,2,'.$rs['postion'].')"  onmouseout="window.parent.UnTip()" ondblclick="takeoff('.$rs['pid'].','.$pd['id'].','.$rs['id'].')" style="cursor:pointer" onclick="copyWord(\''.$rs[name].'\');"/>';
			$petszb_look[$rs['postion']] = '<img  src="'.IMAGE_SRC_URL.'/props/'.$rs['img'].'" border=0  onmouseover="showTip('.$rs['id'].','.$pd['id'].',1,2,'.$rs['postion'].')"  onmouseout="window.parent.UnTip()" style="cursor:pointer" onclick="copyWord(\''.$rs[name].'\');"/>';
			$petAllPid = $petAllPid.$rs['pid']."a";
			$petAllUserPid = $petAllUserPid.$rs['id']."b";
		}
	}
	$petAllPid = "'".$petAllPid."'";
	$petAllUserPid = "'".$petAllUserPid."'";
	$alltakeoff = '<h2><input style="position:absolute;left:90px;top:255px;" type="button" value="秒脱" onclick="alltakeoff('.$petAllPid.','.$pd['id'].','.$petAllUserPid.')" /></h2>';
}	

if(empty($petszb[0])){
	$petszb[0] = '<img src="'.IMAGE_SRC_URL.'/props/zbsx.gif" />';
}
if(empty($petszb[11])){
	$petszb[11] = '<img src="'.IMAGE_SRC_URL.'/props/zbsx.gif" />';
}
for ($i=1; $i<=10; $i++)
{
	if ($petszb[$i] == '') $petszb[$i] = $_props['postion'][$i];
	if ($petszb_look[$i] == '') $petszb_look[$i] = $_props['postion'][$i];
}

// Get jn in here.
if (!is_array($sk)) $jnlist= '宝宝还没有学习技能！';
else
{
	$jnlist='';
	foreach ($sk as $k => $rs)
	{
		if (!is_array($rs) || $rs['bid'] != $selid) continue;
		//print_r($rs);
		if ($rs['level']==10 || $pd['level']<$rs['level'] || $uid) $uplevel='';
		else
		{
			$uplevel='<input type="button" value="升级" onclick="sjJn(\''.$rs['sid'].'\');" />';
		}

		
		$jnlist .= '<li><span onclick="copyWord(\''.$rs[name].'\');" onmouseover="showSkillTip('.$rs['bid'].','.$rs['sid'].')"  onmouseout="window.parent.UnTip()"> '.$uplevel.$rs['name'].'&nbsp;&nbsp;'.$rs['level']. ' 级 </span> </li>';
	}
}

// Get sk book in here.
if (!is_array($bag)) $jnbook= '<option value="0">包裹中没有技能书</option>';
else
{
	foreach ($bag as $k => $rs)
	{
		foreach ($skillsys as $x => $y)
		{	
			if ($rs['pid'] == $y['pid'] && ($y['wx'] == $pd['wx'] || $y['wx'] == 0) && $rs['sums']!=0)
			{
				$jnbook .= '<option value="'.$y['id'].'">'.$y['name'].'</option>';
			}
		}
	}
}

$bbshow = $_pm['mysql'] -> getOneRecord("SELECT bbshow FROM player_ext WHERE uid = {$_SESSION['id']}");
if(!is_array($bbshow))
{
	$_pm['mysql'] -> query("INSERT INTO player_ext (uid,bbshow) VALUES({$_SESSION['id']},5)");
	$bbshownums = 5;
}
else
{
	$bbshownums = $bbshow['bbshow'];
}
if ($jnbook == '') $jnbook= '<option value="0">包裹中没有技能书</option>';

if ($pd['kx']=='') $kx= array();
else $kx = explode(",", $pd['kx']);
$att =getzbAttrib($selid);
$_pm['mem']->memClose();
//@Load template.
if(!empty($uid)){
	$tn = $_game['template'] . 'tpl_bb_view.html';
}else{
	$tn = $_game['template'] . 'tpl_bb.html';
}
$empty = '<img src = "../images/nopet.jpg">';
$empty1 = '<em> </em>';
if($uid)
{
	$empty = '';
	$pet1 =$empty;
	$pet2 =$empty;
	$str[1] = $empty1;
	$str[2] = $empty1;
}
else
{
	$pet1 = $pets[1]?$pets[1]:$empty;
	$pet2 = $pets[2]?$pets[2]:$empty;
}

if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);
	//$empty = '<img src="'.IMAGE_SRC_URL.'/ui/muchang/cwzl26.gif" />';
	
	$src = array(
				 '#nickname#',
				  '#bbname#',//add by DuHao
				 '#headimg#',
				 '#vary#',
				 '#sex#',
				 '#pets#',
				 '#success#',
				 '#money#',
				 '#yb#',
				 '#one#',
				 '#two#',
				 '#three#',
				 '#bigimg#',
				 '#1#',
				 '#2#',
				 '#3#',
				 '#4#',
				 '#5#',
				 '#6#',
				 '#7#',
				 '#8#',
				 '#9#',
				 '#10#',
				 '#jnlist#',
				 '#jk#',
				 '#mk#',
				 '#sk#',
				 '#hk#',
				 '#tk#',
				 '#subyl#',
				 '#subsl#',
				 '#subdl#',
				 '#subxl#',
				 '#subhl#',
				 '#subfl#',
				 '#subkl#',
				 '#remaketimes#',
				 '#pid#',
				 '#jnbook#',
				 '#times#',
				 '#mbid#',
				 '#one1#',
				 '#two1#',
				 '#three1#',
				 '#level#',
            	 '#wx#',
            	 '#hp#',
            	 '#mp#',
            	 '#ac#',
            	 '#mc#',
            	 '#hits#',
            	 '#miss#',
            	 '#speed#',
             	 '#czl#',
				 '#nowexp#',
				 '#lexp#',
				 '#zbsx#',
				 '#vip#',
				 '#11#',
				 '#alltakeoff#'
				);
	$des = array(
				 $user['nickname'].'<br/>宝贝：<font color=green>'.$pd['name'].'</font>',
				  $pd['name'],//add by DuHao
				 '2'.$user['headimg'],
				 $user['vary'],
				 $user['sex'],
				 count($petsAll),
				 0,
				 $user['money'],
				 $user['yb']?$user['yb']:0,
				 empty($uid)?$pets[0]:$pets_look[0],
				 $pet1,
				 $pet2,
				 $pd['imgstand'],
				 empty($uid)?$petszb[1]:$petszb_look[1],
				 empty($uid)?$petszb[2]:$petszb_look[2],
				 empty($uid)?$petszb[3]:$petszb_look[3],
				 empty($uid)?$petszb[4]:$petszb_look[4],
				 empty($uid)?$petszb[5]:$petszb_look[5],
				 empty($uid)?$petszb[6]:$petszb_look[6],
				 empty($uid)?$petszb[7]:$petszb_look[7],
				 empty($uid)?$petszb[8]:$petszb_look[8],
				 empty($uid)?$petszb[9]:$petszb_look[9],
				 empty($uid)?$petszb[10]:$petszb_look[10],
				 $jnlist,
				 $kx[0],
				 $kx[1],
				 $kx[2],
				 $kx[3],
				 $kx[4],
				 $pd['subyl'],
				 $pd['subsl'],
				 $pd['subdl'],
				 $pd['subxl'],
				 $pd['subhl'],
				 $pd['subfl'],
				 $pd['subkl'],
				 $pd['remaketimes'],
				 $pd['id'],
				 $jnbook,
				 $bbshownums,
				 $mbid,
				 $str[0],
				 $str[1]?$str[1]:$empty1,
				 $str[2]?$str[2]:$empty1,
				 $pd['level'],
				 getWx($pd['wx']),
				 ($pd['hp']+$att['hp']).'/'.($pd['srchp']+$att['hp']),
				 ($pd['mp']+$att['mp']).'/'.($pd['srcmp']+$att['mp']),
				 $pd['ac']+$att['ac'],
				 $pd['mc']+$att['mc'],
				 $pd['hits']+$att['hits'],
				 $pd['miss']+$att['miss'],
				 $pd['speed']+$att['speed'],
				 $pd['czl'],
				 $pd['nowexp'],
				 $pd['lexp'],
				 $petszb[0],
				 $user['vip'],
				 empty($uid)?$petszb[11]:$petszb_look[11],
				 $alltakeoff
				);
	$bbatib = str_replace($src, $des, $tpl);
}

// gzip echo. if maybe.
ob_start('ob_gzip');
echo $bbatib;
ob_end_flush();
?>
