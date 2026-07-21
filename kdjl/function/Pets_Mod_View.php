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
require_once('../config/config.game.php');

secStart($_pm['mem']);
$dbn  = $GLOBALS['_pm']['mysql'];
function petsViewHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function petsViewJsSingle($value)
{
	$value = str_replace("\\", "\\\\", (string)$value);
	$value = str_replace("'", "\\'", $value);
	$value = str_replace(array("\r", "\n"), array("\\r", "\\n"), $value);
	return $value;
}

function petsViewImage($value)
{
	return kdjlPropsImageName($value);
}

function petsViewBbImage($value, $fallback)
{
	return kdjlBbImageName($value, $fallback);
}

$uid = (isset($_REQUEST['uid']) && !is_array($_REQUEST['uid'])) ? trim($_REQUEST['uid']) : '';
$uidSql = $dbn->escape($uid);
$uidParam = rawurlencode($uid);
$sql = "select id from player where nickname='".$uidSql."' limit 1";
$res  = $dbn->getOneRecord($sql);
$userid = is_array($res) ? intval($res['id']) : 0;
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
$userDefaults = array('mbid'=>0, 'nickname'=>'', 'headimg'=>0, 'vary'=>'', 'sex'=>'', 'money'=>0, 'yb'=>0);
foreach($userDefaults as $userKey => $userValue)
{
	if(!isset($user[$userKey])) $user[$userKey] = $userValue;
}
$user['mbid'] = intval($user['mbid']);
$user['headimg'] = intval($user['headimg']);
$user['money'] = intval($user['money']);
$user['yb'] = intval($user['yb']);
$pets = array();
$petszb = array();
$jnbook = '';
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
		if(!isset($rs['cardimg'])) $rs['cardimg'] = '';
		if ($rs['muchang'] == 1) continue;
		if ($pid == $rs['id'])
		{
			$selid	= $rs['id'];
			$pd		= $rs;
			$user['mbid']= $rs['id'];
		}
		if(empty($pid))
		{
			if ($kk == 0 )
			{
				$sel = 100;
				$selidinit= $rs['id'];
				$pdinit= $rs;
			}
			else $sel = 50;
		}
		else
		{
			if($rs['id'] == $pid)
			{
				$sel = 100;
				$selidinit= $rs['id'];
				$pdinit= $rs;
			}
			else $sel = 50;
		}
		$pets[$kk++] = "<img src='".IMAGE_SRC_URL."/bb/".petsViewBbImage($rs['cardimg'], 'k1.gif')."' onclick='window.location.href=\"Pets_Mod_View.php?uid=".$uidParam."&pid=".$rs['id']."\";' style='cursor:pointer;filter:alpha(opacity=".$sel.")' id='i".$kk."'>";
		if ($kk==3) break;
	}
}


if(!is_array($pd))
{
	if(!is_array($pdinit) || empty($pdinit))
	{
		$_pm['mem']->memClose();
		die('');
	}
	$selid	= $selidinit;
	$pd		= $pdinit;
}
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
			if(!isset($rs['postion'])) $rs['postion'] = 0;
			if(!isset($rs['img'])) $rs['img'] = '';
			if(!isset($rs['name'])) $rs['name'] = '';
			if ($rs['requires'] != '')
			{
				$t = explode(',',
					       str_replace(array('lv','wx'), array('等级','五行'), $rs['requires'])
					      );
				$wx = isset($t[1]) ? str_replace($_props['wxs'], $_props['wxd'], $t[1]) : '';
			}
			else $t[0]= $wx= '无';

			$zbeffect = zbAttrib($rs['effect']);
			$petszb[$rs['postion']] = '<img src="'.IMAGE_SRC_URL.'/props/'.petsViewImage($rs['img']).'" border=0  onmouseover="showTip('.$rs['id'].','.$pd['id'].',1,2)"  onmouseout="window.parent.UnTip()"  onclick="copyWord(\''.petsViewHtml(petsViewJsSingle($rs['name'])).'\');" style="cursor:pointer" />';
		}
	}
}

for ($i=1; $i<=10; $i++)
{
	if (empty($petszb[$i])) $petszb[$i] = $_props['postion'][$i];
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

		if ($rs['level']==10 || $pd['level']<$rs['level']) $uplevel='';
		else $uplevel='<input type="button" value="升级" style="background-image:url('.IMAGE_SRC_URL.'/ui/shop/gm13.gif);border:0px;width:39px;height:15px;color:#2F291D;" onclick="sjJn(\''.$rs['sid'].'\');"/>';


		$skillName = isset($rs['name']) ? $rs['name'] : '';
		$jnlist .= '<span onclick="copyWord(\''.petsViewHtml(petsViewJsSingle($skillName)).'\');"> <b>' .petsViewHtml($skillName). '</b> </span>'.intval($rs['level']).' 级<br/>';
	}
}

// Get sk book in here.
if (!is_array($bag)) $jnbook= '<option value="0">包裹中没有技能书</option>';
else
{
	foreach ($bag as $k => $rs)
	{
		if(!is_array($rs) || !isset($rs['pid'])) continue;
		foreach ($skillsys as $x => $y)
		{
			if(!is_array($y) || !isset($y['pid'],$y['wx'],$y['id'],$y['name'])) continue;
			if ($rs['pid'] == $y['pid'] && $y['wx'] == $pd['wx'])
			{
				$jnbook .= '<option value="'.$y['id'].'">'.petsViewHtml($y['name']).'</option>';
			}
		}
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
$tn = $_game['template'] . 'tpl_bb_view.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);
	$empty = '<img src="'.IMAGE_SRC_URL.'/ui/muchang/cwzl26.gif" />';

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
				 '#pinfo#',
				 '#pinfos#',
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
				 '#mbid#',
				 '#one1#',
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
				 '#lexp#'
				);
	$des = array(
				 petsViewHtml($user['nickname']).'<br/>宝贝：<font color=green>'.petsViewHtml($pd['name']).'</font>',
				  petsViewHtml($pd['name']),//add by DuHao
				 '2'.$user['headimg'],
				 petsViewHtml($user['vary']),
				 petsViewHtml($user['sex']),
				 count($petsAll),
				 0,
				 $user['money'],
				 isset($user['yb']) && $user['yb'] ? $user['yb'] : 0,
				 isset($pets[0]) ? $pets[0] : $empty,
				 isset($pets[1]) ? $pets[1] : $empty,
				 isset($pets[2]) ? $pets[2] : $empty,
				 '等级：'.$pd['level'].'<br/>'.
				 '当前经验：'.$pd['nowexp'].'<br />'.
				 '升级经验：'.$pd['lexp'].'<br />'.
				 '五行：'.getWx($pd['wx']).'<br/>'.
				 '生命: '.($pd['hp']+$att['hp']).'/'.($pd['srchp']+$att['hp']).'<br/>'.
				 '魔法: '.($pd['mp']+$att['mp']).'/'.($pd['srcmp']+$att['mp']).'<br/>'.
				 '攻击：'.($pd['ac']+$att['ac']).'<br/>'.
				 '防御：'.($pd['mc']+$att['mc']).'<br/>'.
				 '命中：'.($pd['hits']+$att['hits']).'<br/>'.
				 '闪避：'.($pd['miss']+$att['miss']).'<br/>'.
				 '速度：'.($pd['speed']+$att['speed']).'<br/>'.
				 '成长：'.$pd['czl'],
				 '等级：'.$pd['level'].'<br/>'.
				 '五行：'.getWx($pd['wx']).'<br/>'.
				 '生命: '.($pd['hp']+$att['hp']).'/'.($pd['srchp']+$att['hp']).'<br/>'.
				 '魔法: '.($pd['mp']+$att['mp']).'/'.($pd['srcmp']+$att['mp']).'<br/>'.
				 '攻击：'.($pd['ac']+$att['ac']).'<br/>'.
				 '防御：'.($pd['mc']+$att['mc']).'<br/>'.
				 '命中：'.($pd['hits']+$att['hits']).'<br/>'.
				 '闪避：'.($pd['miss']+$att['miss']).'<br/>'.
				 '速度：'.($pd['speed']+$att['speed']).'<br/>'.
				 '成长：'.$pd['czl'],
				 petsViewBbImage($pd['imgstand'], 'z1.gif'),
				 $petszb[1],
				 $petszb[2],
				 $petszb[3],
				 $petszb[4],
				 $petszb[5],
				 $petszb[6],
				 $petszb[7],
				 $petszb[8],
				 $petszb[9],
				 $petszb[10],
				 $jnlist,
				 petsViewHtml($kx[0]),
				 petsViewHtml($kx[1]),
				 petsViewHtml($kx[2]),
				 petsViewHtml($kx[3]),
				 petsViewHtml($kx[4]),
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
				 $selid,
				 '',
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
				 $pd['lexp']
				);
	$bbatib = str_replace($src, $des, $tpl);
}

// gzip echo. if maybe.
ob_start('ob_gzip');
echo $bbatib;
ob_end_flush();
?>
