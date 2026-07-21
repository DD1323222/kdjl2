<?php
/**
*/
require_once('../config/config.game.php');

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid <= 0)
{
	die('登录状态已失效，请重新登录！');
}

define('MEM_FIGHTUSER_KEY', $uid . 'fuser');
secStart($_pm['mem']);

function fortressModHtml($value)
{
	return htmlspecialchars(strval($value), ENT_QUOTES, 'UTF-8');
}

function fortressModImage($value)
{
	$value = basename(strval($value));
	return preg_match('/^[A-Za-z0-9_.-]+$/', $value) ? $value : '';
}

$petsarr	= $_pm['user']->getUserPetById($uid);
$user		= $_pm['user']->getUserById($uid);
if(!is_array($user)) die('1');
if(!is_array($petsarr)) $petsarr = array();
$userDefaults = array('sysautosum' => 0, 'maxautofitsum' => 0, 'mbid' => 0);
foreach($userDefaults as $defaultKey => $defaultValue)
{
	if(!isset($user[$defaultKey])) $user[$defaultKey] = $defaultValue;
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
else
{
	$num = 0;
}
if(!$_pm['mysql']->query("UPDATE player
						     SET autofitflag=0
						   WHERE id={$uid}
						")) die('1');

$kk=0;
$selid=0; // default select pets!
$sk=1;
$mbczl=0;
$pets = array('', '', '');
if (is_array($petsarr))
{
	foreach ($petsarr as $k =>$rs) // Will filter in muchang pets for current user.
	{
		if(!is_array($rs)) continue;
		$rsDefaults = array('id' => 0, 'name' => '', 'muchang' => 0, 'tgflag' => 0, 'level' => 1, 'czl' => 0, 'cardimg' => '');
		foreach($rsDefaults as $defaultKey => $defaultValue)
		{
			if(!isset($rs[$defaultKey])) $rs[$defaultKey] = $defaultValue;
		}
		if(intval($rs['muchang']) != 0 || intval($rs['tgflag']) != 0){
			continue;
		}
		if($rs['id'] == $user['mbid'])
		{
			$sel  = 100;
			$selid=$rs['id'];
			$sk   =$kk+1;
			$mbczl=$rs['czl'];
		}
		else
		{
			$sel = 50;
		}
		if($rs['level']==0) $rs['level']=1;
		$petCardImg = fortressModImage(isset($rs['cardimg']) ? $rs['cardimg'] : '');
		$petNameHtml = fortressModHtml(isset($rs['name']) ? $rs['name'] : '');
		$petId = intval(isset($rs['id']) ? $rs['id'] : 0);
		$petCzl = intval(isset($rs['czl']) ? $rs['czl'] : 0);
		$pets[$kk++] = "<img src='".IMAGE_SRC_URL."/bb/{$petCardImg}' onClick=\"Setbbs(".$kk.",".$petId.",".$petCzl.");\" alt=\"{$petNameHtml}\" style='cursor:pointer;filter:alpha(opacity={$sel});' id='i{$kk}'> ";
		if ($kk==3) break;
	}
}

function msg($m)
{
	die($m);
}

if(!$_pm['mysql']->query("UPDATE player
						 SET inmap='0'
					   WHERE id = {$uid}
					")) die('1');
if(defined('MEM_USER_KEY')) $_pm['mem']->del(MEM_USER_KEY);

//$setting = $_pm['mem']->get('db_welcome1');
//if(!is_array($setting)) $setting=unserialize($setting);
$setting = array();
$setting['fortress'] = getBaseWelcomeInfoByCode('fortress');
if(!is_array($setting['fortress']) || !isset($setting['fortress']['contents']))
{
	msg('后台配置数据读取失败(1)！');
}
if(!isset($setting['fortress']))
{
	msg('缺少活动开启设定(fortress)！');
}
/*
$props = $_pm['mem']->get('db_propsid');
if(!is_array($props)) $props=unserialize($props);
if(!is_array($props))
{
	msg('后台配置数据读取失败(2)！');
}
*/
$set=preg_split('/\r\n|\n|\r/', trim($setting['fortress']['contents']));
$props = array();
$str='';
$i_need='';
$js='var czl_pstr=[];';
foreach($set as $k1=>$s)
{
	$s = trim($s);
	if($s == '')
	{
		continue;
	}
	$tmp=explode(',',$s);
	if(count($tmp) < 5)
	{
		continue;
	}
	$tmp0=explode('-',$tmp[0]);//进入需要的成长
	$tmp1=explode('|',$tmp[1]);//进入需要的东西
	if(count($tmp0) < 2)
	{
		continue;
	}
	$tmp1_str='';
	foreach($tmp1 as $t)
	{
		$tt=explode(':',$t);
		if(count($tt) < 2 || intval($tt[0]) <= 0 || intval($tt[1]) <= 0)
		{
			continue;
		}
		$props[$tt[0]] = getBasePropsInfoById($tt[0]);
		$propsName = (is_array($props[$tt[0]]) && isset($props[$tt[0]]['name'])) ? $props[$tt[0]]['name'] : $tt[0];
		$tmp1_str.=fortressModHtml($propsName).' '.intval($tt[1]).'个,';
	}

	if($mbczl>=$tmp0[0]&&$mbczl<=$tmp0[1])
	{
		$i_need=substr($tmp1_str,0,-1);
	}
	$tmp2=explode('|',$tmp[3]);//第一名的奖励
	$tmp2_str='';
	foreach($tmp2 as $t)
	{
		$tt=explode(':',$t);
		if(count($tt) < 2 || intval($tt[0]) <= 0 || intval($tt[1]) <= 0)
		{
			continue;
		}
		$props[$tt[0]] = getBasePropsInfoById($tt[0]);
		$propsName = (is_array($props[$tt[0]]) && isset($props[$tt[0]]['name'])) ? $props[$tt[0]]['name'] : $tt[0];
		$tmp2_str.=fortressModHtml($propsName).' '.intval($tt[1]).'个,';
	}
	$jsText = html_entity_decode(substr($tmp1_str,0,-1), ENT_QUOTES, 'UTF-8');
	$js.='czl_pstr['.intval($k1).']=['.intval($tmp0[0]).','.intval($tmp0[1]).','.json_encode($jsText).'];
';
	$tmp3=explode('|',$tmp[4]);//怪物
	$str.='<tr><td align="center" class="text03">'.fortressModHtml($tmp[0]).'</td><td align="center" class="text03">'.fortressModHtml($tmp[2]).'</td></tr>';
}
$tn = $_game['template'] . 'tpl_fortress.html';
$ret = '';
if (file_exists($tn))
{
	$tpl = file_get_contents($tn);

	$src = array(
				 "#one#",
				 "#two#",
				 "#three#",
				 '#sbb#',
				 '#sk#',
				 '#str#',
				 '#i_need#',
				 '#js#'
				);
	$des = array(
				 $pets[0],
				 $pets[1],
				 $pets[2],
				 $selid,
				 $sk,
				 $str,
				 $i_need,
				 $js
			);
	$ret = str_replace($src, $des, $tpl);
}

$_pm['mem']->memClose();
// gzip echo. if maybe.
ob_start('ob_gzip');
echo $ret;
ob_end_flush();
//$('gw').contentWindow.location='/function/fortress_Mod.php';
?>
