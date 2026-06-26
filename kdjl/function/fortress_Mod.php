<?php
/**
*/
require_once('../config/config.game.php');

define(MEM_FIGHTUSER_KEY, $_SESSION['id'] . 'fuser');
secStart($_pm['mem']);

$petsarr	= $_pm['user']->getUserPetById($_SESSION['id']);
$user		= $_pm['user']->getUserById($_SESSION['id']);

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

$kk=0;
$selid=0; // default select pets!
$sk=1;
$mbczl=0;
if (is_array($petsarr))
{
	foreach ($petsarr as $k =>$rs) // Will filter in muchang pets for current user.
	{
		if($rs['muchang'] != 0){
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
		$pets[$kk++] = "<img src='".IMAGE_SRC_URL."/bb/{$rs['cardimg']}' onClick=\"Setbbs(".$kk.",".$rs['id'].",".$rs['czl'].");\" alt=\"{$rs['name']}\" style='cursor:pointer;filter:alpha(opacity={$sel});' id='i{$kk}'> ";
		if ($kk==3) break;
	}
}

function msg($m)
{
	die($m);
}

$_pm['mysql']->query("UPDATE player 
						 SET inmap='0'
					   WHERE id = {$_SESSION['id']}
					");

//$setting = $_pm['mem']->get('db_welcome1');
//if(!is_array($setting)) $setting=unserialize($setting);
$setting['fortress'] = getBaseWelcomeInfoByCode('fortress');
if(!is_array($setting))
{
	msg('后台配置数据读取失败(1)！'.print_r($setting,1));
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
$set=explode("\r\n",$setting['fortress']['contents']);
$str='';
$i_need='';
$js='var czl_pstr=[];';
foreach($set as $k1=>$s)
{
	$tmp=explode(',',$s);
	$tmp0=explode('-',$tmp[0]);//进入需要的成长
	$tmp1=explode('|',$tmp[1]);//进入需要的东西
	$tmp1_str='';
	foreach($tmp1 as $t)
	{
		$tt=explode(':',$t);
		$props[$tt[0]] = getBasePropsInfoById($tt[0]);
		$tmp1_str.=$props[$tt[0]]['name'].' '.$tt[1].'个,';
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
		$props[$tt[0]] = getBasePropsInfoById($tt[0]);
		$tmp2_str.=$props[$tt[0]]['name'].' '.$tt[1].'个,';
	}
	$js.='czl_pstr['.$k1.']=['.$tmp0[0].','.$tmp0[1].',"'.substr($tmp1_str,0,-1).'"];
';
	$tmp3=explode('|',$tmp[4]);//怪物
	$str.='<tr><td align="center" class="text03">'.$tmp[0].'</td><td align="center" class="text03">'.$tmp[2].'</td></tr>';
}
$tn = $_game['template'] . 'tpl_fortress.html';
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