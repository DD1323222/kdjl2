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

function petsModHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function petsModJsSingle($value)
{
	$value = str_replace("\\", "\\\\", (string)$value);
	$value = str_replace("'", "\\'", $value);
	$value = str_replace(array("\r", "\n"), array("\\r", "\\n"), $value);
	return $value;
}

function petsModImage($value)
{
	return kdjlPropsImageName($value);
}

function petsModBbImage($value, $fallback)
{
	return kdjlBbImageName($value, $fallback);
}

$uid = (isset($_GET['uid']) && !is_array($_GET['uid'])) ? trim($_GET['uid']) : '';
if(!empty($uid)){
	$uidSql = $_pm['mysql']->escape($uid);
	$sql = "select id from player where nickname='".$uidSql."' limit 1";
	$res  = $_pm['mysql']->getOneRecord($sql);
	$userid = is_array($res) ? intval($res['id']) : 0;
}else{
	$userid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
}
if($userid <= 0)
{
	die("");
}
$user		= $_pm['user']->getUserById($userid);
$petsAll	= $_pm['user']->getUserPetById($userid);
$bag		= $_pm['user']->getUserBagById($userid);
$sk			= $_pm['user']->getUserPetSkillById($userid);
$skillsys	= kdjlSafeMemValue($_pm['mem']->get(MEM_SKILLSYS_KEY), array());
if(!is_array($user)) die('');
if(!is_array($petsAll)) $petsAll = array();
if(!is_array($skillsys)) $skillsys = array();
$userDefaults = array('mbid'=>0, 'nickname'=>'', 'headimg'=>0, 'vary'=>'', 'sex'=>'', 'money'=>0, 'yb'=>0, 'vip'=>0);
foreach($userDefaults as $userKey => $userValue)
{
	if(!isset($user[$userKey])) $user[$userKey] = $userValue;
}
$user['mbid'] = intval($user['mbid']);
$user['headimg'] = intval($user['headimg']);
$user['money'] = intval($user['money']);
$user['yb'] = intval($user['yb']);
$user['vip'] = intval($user['vip']);
$pets = array();
$pets_look = array();
$str = array();
$petszb_look = array();
$jnbook = '';
$mbid = isset($user['mbid']) ? intval($user['mbid']) : 0;
$selid = 0;
$selidinit = 0;
$pdinit = array();


if (isset($_REQUEST['pid']) && !is_array($_REQUEST['pid']) && intval($_REQUEST['pid'])>0)
	 $pid = intval($_REQUEST['pid']);
else $pid=0;
$kk = 0;
$pd = 0;
if(is_array($petsAll))
{
	foreach ($petsAll as $k =>$rs) // Will filter in muchang pets for current user.
	{
		if(!is_array($rs)) continue;
		if(!isset($rs['muchang'])) $rs['muchang'] = 0;
		if(!isset($rs['id'])) $rs['id'] = 0;
		if(!isset($rs['name'])) $rs['name'] = '';
		if(!isset($rs['level'])) $rs['level'] = 0;
		if(!isset($rs['cardimg'])) $rs['cardimg'] = '';
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
		if($selidinit == 0)
		{
			$selidinit= $rs['id'];
			$pdinit= $rs;
			if($mbid <= 0) $mbid = $rs['id'];
		}
		$sellv = $sel / 100;
		//opacity: 1; filter : progid:DXImageTransform.Microsoft.Alpha(style=0,opacity=100,finishOpacity=100);

		$kk++;
		$cardImg = petsModBbImage($rs['cardimg'], 'k1.gif');
		$nameHtml = petsModHtml($rs['name']);
		$pets[$ii] = "<img onclick='Setbb(".$rs['id'].",this,".$user['mbid'].");' src='".IMAGE_SRC_URL."/bb/".$cardImg."' style='cursor:hand;opacity: ".$sellv."; filter : progid:DXImageTransform.Microsoft.Alpha(style=0,opacity=".$sel.",finishOpacity=100);' id='i".$kk."'>";
		$pets_look[$ii] = "<img src='".IMAGE_SRC_URL."/bb/".$cardImg."' style='cursor:hand;opacity: ".$sellv."; filter : progid:DXImageTransform.Microsoft.Alpha(style=0,opacity=".$sel.",finishOpacity=100);' id='i".$kk."'>";
		$str[$ii] = "<em><a onclick='Setbb(".$rs['id'].",this,".$user['mbid'].");'>".$nameHtml."<br />LV ".intval($rs['level'])."</a></em>";
		if ($ii==3) break;
	}
}
if(!isset($_GET['uid'])){
// save mbid.
	if($mbid > 0)
	{
		$_pm['mysql']->query("UPDATE player
							 SET mbid={$mbid}
						   WHERE id={$userid}
						");
		// refresh cache
		$_pm['user']->updateMemUser($userid);
	}
}
if(!is_array($pd))
{
	if(!is_array($pdinit) || empty($pdinit))
	{
		$_pm['mem']->memClose();
		die('');
	}
	$pd		= $pdinit;
}
$selid	= $selidinit;
$petDefaults = array(
	'id'=>0, 'name'=>'', 'imgstand'=>'', 'kx'=>'', 'level'=>0, 'wx'=>0,
	'hp'=>0, 'srchp'=>0, 'mp'=>0, 'srcmp'=>0, 'ac'=>0, 'mc'=>0,
	'hits'=>0, 'miss'=>0, 'speed'=>0, 'czl'=>0, 'nowexp'=>0, 'lexp'=>0,
	'subyl'=>0, 'subsl'=>0, 'subdl'=>0, 'subxl'=>0, 'subhl'=>0,
	'subfl'=>0, 'subkl'=>0, 'remaketimes'=>0
);
foreach($petDefaults as $petKey => $petValue)
{
	if(!isset($pd[$petKey])) $pd[$petKey] = $petValue;
}
$numericPetFields = array('id','level','wx','hp','srchp','mp','srcmp','ac','mc','hits','miss','speed','czl','nowexp','lexp','subyl','subsl','subdl','subxl','subhl','subfl','subkl','remaketimes');
foreach($numericPetFields as $petKey) $pd[$petKey] = intval($pd[$petKey]);
$petszb = array();
$alltakeoff = '';
$petAllPid = '';
$petAllUserPid = '';
if (is_array($bag))
{
	foreach ($bag as $k => $rs)
	{
		if(!is_array($rs)) continue;
		if(!isset($rs['varyname'])) $rs['varyname'] = 0;
		if(!isset($rs['zbing'])) $rs['zbing'] = 0;
		if(!isset($rs['zbpets'])) $rs['zbpets'] = 0;
		if ($rs['varyname'] == 9 && $rs['zbing'] == 1 && $rs['zbpets'] == $pd['id'])
		{
			if(!isset($rs['id'])) $rs['id'] = 0;
			if(!isset($rs['pid'])) $rs['pid'] = 0;
			if(!isset($rs['postion'])) $rs['postion'] = 0;
			if(!isset($rs['name'])) $rs['name'] = '';
			if(!isset($rs['img'])) $rs['img'] = '';
			if ($rs['requires'] != '')
			{
				$t = explode(',',
					       str_replace(array('lv','wx'), array('等级','五行'), $rs['requires'])
					      );
				$wx = isset($t[1]) ? str_replace($_props['wxs'], $_props['wxd'], $t[1]) : '';
			}
			else $t[0]= $wx= '无';

			$zbeffect = zbAttrib($rs['effect']);
			$nameJs = petsModHtml(petsModJsSingle($rs['name']));
			$propImg = petsModImage($rs['img']);
			$petszb[$rs['postion']] = '<img  src="'.IMAGE_SRC_URL.'/props/'.$propImg.'" border=0  onmouseover="showTip('.$rs['id'].','.$pd['id'].',1,2,'.$rs['postion'].')"  onmouseout="window.parent.UnTip()" ondblclick="takeoff('.$rs['pid'].','.$pd['id'].','.$rs['id'].')" style="cursor:pointer" onclick="copyWord(\''.$nameJs.'\');"/>';
			$petszb_look[$rs['postion']] = '<img  src="'.IMAGE_SRC_URL.'/props/'.$propImg.'" border=0  onmouseover="showTip('.$rs['id'].','.$pd['id'].',1,2,'.$rs['postion'].')"  onmouseout="window.parent.UnTip()" style="cursor:pointer" onclick="copyWord(\''.$nameJs.'\');"/>';
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
if(empty($petszb_look[0])){
	$petszb_look[0] = '<img src="'.IMAGE_SRC_URL.'/props/zbsx.gif" />';
}
if(empty($petszb[11])){
	$petszb[11] = '<img src="'.IMAGE_SRC_URL.'/props/zbsx.gif" />';
}
if(empty($petszb_look[11])){
	$petszb_look[11] = '<img src="'.IMAGE_SRC_URL.'/props/zbsx.gif" />';
}
for ($i=1; $i<=10; $i++)
{
	if (empty($petszb[$i])) $petszb[$i] = $_props['postion'][$i];
	if (empty($petszb_look[$i])) $petszb_look[$i] = $_props['postion'][$i];
}

// Get jn in here.
if (!is_array($sk)) $jnlist= '宝宝还没有学习技能！';
else
{
	$jnlist='';
	foreach ($sk as $k => $rs)
	{
		if (!is_array($rs)) continue;
		$rsDefaults = array('bid'=>0, 'sid'=>0, 'name'=>'', 'level'=>0);
		foreach($rsDefaults as $rsKey => $rsValue) if(!isset($rs[$rsKey])) $rs[$rsKey] = $rsValue;
		$rs['bid'] = intval($rs['bid']);
		$rs['sid'] = intval($rs['sid']);
		$rs['level'] = intval($rs['level']);
		if ($rs['bid'] != $selid) continue;
		//print_r(rrs);
		if ($rs['level']==10 || $pd['level']<$rs['level'] || $uid) $uplevel='';
		else
		{
			$uplevel='<input type="button" value="升级" onclick="sjJn(\''.$rs['sid'].'\');" />';
		}


		$skillName = isset($rs['name']) ? $rs['name'] : '';
		$jnlist .= '<li><span onclick="copyWord(\''.petsModHtml(petsModJsSingle($skillName)).'\');" onmouseover="showSkillTip('.$rs['bid'].','.$rs['sid'].')"  onmouseout="window.parent.UnTip()"> '.$uplevel.petsModHtml($skillName).'&nbsp;&nbsp;'.intval($rs['level']).' 级 </span> </li>';
	}
}

// Get sk book in here.
if (!is_array($bag)) $jnbook= '<option value="0">包裹中没有技能书</option>';
else
{
	foreach ($bag as $k => $rs)
	{
		if(!is_array($rs) || !isset($rs['pid']) || !isset($rs['sums'])) continue;
		foreach ($skillsys as $x => $y)
		{
			if(!is_array($y) || !isset($y['pid'],$y['wx'],$y['id'],$y['name'])) continue;
			if ($rs['pid'] == $y['pid'] && ($y['wx'] == $pd['wx'] || $y['wx'] == 0) && $rs['sums']!=0)
			{
				$jnbook .= '<option value="'.$y['id'].'">'.petsModHtml($y['name']).'</option>';
			}
		}
	}
}

$bbshownums = 0;
if(empty($uid))
{
	$bbshow = $_pm['mysql'] -> getOneRecord("SELECT bbshow FROM player_ext WHERE uid = {$userid}");
	if(!is_array($bbshow))
	{
		$_pm['mysql'] -> query("INSERT INTO player_ext (uid,bbshow) VALUES({$userid},5) ON DUPLICATE KEY UPDATE bbshow=bbshow");
		$bbshownums = 5;
	}
	else
	{
		$bbshownums = $bbshow['bbshow'];
	}
}
if ($jnbook == '') $jnbook= '<option value="0">包裹中没有技能书</option>';

if ($pd['kx']=='') $kx= array();
else $kx = explode(",", $pd['kx']);
$kx = array_pad($kx, 5, '');
$att =getzbAttrib($selid,false,'',$userid);
if(!is_array($att)) $att = array();
foreach(array('hp','mp','ac','mc','hits','miss','speed') as $attKey)
{
	$att[$attKey] = isset($att[$attKey]) ? intval($att[$attKey]) : 0;
}
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
	$pet1 = isset($pets[1]) ? $pets[1] : $empty;
	$pet2 = isset($pets[2]) ? $pets[2] : $empty;
}

if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);
	//rempty = '<img src="'.IMAGE_SRC_URL.'/ui/muchang/cwzl26.gif" />';

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
				 petsModHtml($user['nickname']).'<br/>宝贝：<font color=green>'.petsModHtml($pd['name']).'</font>',
				  petsModHtml($pd['name']),//add by DuHao
				 '2'.$user['headimg'],
				 petsModHtml($user['vary']),
				 petsModHtml($user['sex']),
				 count($petsAll),
				 0,
				 $user['money'],
				 isset($user['yb']) && $user['yb'] ? $user['yb'] : 0,
				 empty($uid) ? (isset($pets[0]) ? $pets[0] : $empty) : (isset($pets_look[0]) ? $pets_look[0] : $empty),
				 $pet1,
				 $pet2,
				 petsModBbImage($pd['imgstand'], 'z1.gif'),
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
				 petsModHtml($kx[0]),
				 petsModHtml($kx[1]),
				 petsModHtml($kx[2]),
				 petsModHtml($kx[3]),
				 petsModHtml($kx[4]),
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
				 isset($str[0]) ? $str[0] : $empty1,
				 isset($str[1]) ? $str[1] : $empty1,
				 isset($str[2]) ? $str[2] : $empty1,
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
				 empty($uid)?$petszb[0]:$petszb_look[0],
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
