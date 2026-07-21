<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.05.22
*@Usage: MuChang
*@Note: none
*/
require_once('../config/config.game.php');
secStart($_pm['mem']);

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');

function muchangModHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function muchangModJsSingle($value)
{
	$value = str_replace("\\", "\\\\", (string)$value);
	$value = str_replace("'", "\\'", $value);
	$value = str_replace(array("\r", "\n"), array("\\r", "\\n"), $value);
	return $value;
}

function muchangModImage($value)
{
	$value = basename((string)$value);
	return preg_match('/^[A-Za-z0-9_.-]+$/', $value) ? $value : '';
}

$user	 = $_pm['user']->getUserById($uid);
$petsAll  = $_pm['user']->getUserPetById($uid);
if(!is_array($user)) die('');
if(!is_array($petsAll)) $petsAll = array();
$userDefaults = array('mbid' => 0, 'fieldpwd' => '', 'tgtime' => 0, 'tgmax' => 0, 'maxmc' => 0, 'task' => 0, 'money' => 0, 'yb' => 0);
foreach($userDefaults as $defaultKey => $defaultValue)
{
	if(!isset($user[$defaultKey])) $user[$defaultKey] = $defaultValue;
}
$td_num = 0;
$pall = 0;
$mainbb =''; //1:26;2:123 ;3:235;
$petslist = '';
$petsoption = '';
$mesoption = '';
$mc = '';
$pets = array('', '', '');
if (!is_array($petsAll)) $petslist='获取宝宝数据失败!';
else
{
	$kk = 0;
	foreach ($petsAll as $k => $rs)
	{
		if(!is_array($rs)) continue;
		$rsDefaults = array('id' => 0, 'name' => '', 'muchang' => 0, 'tgflag' => 0, 'wx' => 0, 'level' => 0, 'cardimg' => '', 'imgdie' => '');
		foreach($rsDefaults as $defaultKey => $defaultValue)
		{
			if(!isset($rs[$defaultKey])) $rs[$defaultKey] = $defaultValue;
		}
		if ($rs['name'] == '') continue;
		if ($rs['muchang']==1 && $rs['tgflag'] == 0)
		{//title="<img src='.IMAGE_SRC_URL.'/bb/'.$rs['imgdie'].' style=\'margin:0px;float:left;\'>'.$rs['name'].'<br/>'.getWx($rs['wx']).'系<br/>'.'"
			$pall++;
			$nameHtml = muchangModHtml($rs['name']);
			$nameJs = muchangModHtml(muchangModJsSingle($rs['name']));
			$wxHtml = muchangModHtml(getWx($rs['wx']));
			$imgDie = muchangModImage($rs['imgdie']);
			$petslist .= '<tr>
			<td width="130px" style="cursor:pointer;text-align:left;" onmouseover="mcbbshow('.$rs['id'].');vtips(this);" onmouseout="ctips(this)" onclick="sel(this);copyWord(\''.$nameJs.'\');Display('.$rs['id'].');"><img src="'.IMAGE_SRC_URL.'/ui/muchang/mc05.gif" />'.$nameHtml.'</td>
			<td width="70px" style="text-align:left;">'.$wxHtml.'</td>
			<td style="text-align:left;" >LV '.$rs['level'].'</td>
		  </tr>';
		}
		else if($rs['muchang'] == 0 && $rs['tgflag'] == 0)
		{
			//----------------------------------
			if ($rs['id'] == $user['mbid'])
			{
				$pets[$kk++] = "<img src='".IMAGE_SRC_URL."/bb/".muchangModImage($rs['cardimg'])."' onclick='Display({$rs['id']});zhixiang(this,".$td_num.")' style='cursor:pointer;'>";
			}
			else
			{
				$pets[$kk++] = "<img class='ch02' src='".IMAGE_SRC_URL."/bb/".muchangModImage($rs['cardimg'])."' onclick='Display({$rs['id']});zhixiang(this,".$td_num.")' style='cursor:pointer;'>";
			}
			$td_num++;
			if ($rs['id'] == $user['mbid'])
			{
				if ($kk == 1) $mainbb='<td class="ch01">&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>';
				else if($kk==2) $mainbb='<td>&nbsp;</td><td class="ch01">&nbsp;</td><td>&nbsp;</td>';
				else if($kk==3) $mainbb='<td>&nbsp;</td><td>&nbsp;</td><td class="ch01">&nbsp;</td>';
			}
			//----------------------------------
		}
	}
	if ($petslist == '') $petslist = '牧场里面还没有宝贝！';
}
$pnum = $pall;
$pall = '';
/* added by Zheng.Ping */
$login = '密码：<input name="login" type="password" id="login"  /><br /><br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" name="Submit" value="确定" onclick="login()" hidefocus />&nbsp;&nbsp;&nbsp;&nbsp;
<input type="button" name="Submit2" value="修改密码" onclick="update()" hidefocus />';
$encryptAction = 'pwd()';
$encryptSubmit = 'jiami();';
$oldPwdInput   = '';
$discardStep   = 'delbb()';
$discardAction = "Ajax.Request('../function/mcGate.php?op=d&id='+bid, opt)";
$getDiscardPwd = '';
$discardCheck  = "if(bid==0){window.parent.Alert('您需要先选择一个宝宝噢!');return;}
    else if(!confirm('为了口袋精灵世界的健康发展，丢弃需要花费您10000金币处理费!\\n注意，您一旦丢弃宝宝，就再也找不回来了，并且宝宝穿戴的装备\\n也会一起消失。当然您在丢弃前也可以先取下来，您确定要丢弃该宝宝吗?')) return;";

if(!empty($user['fieldpwd'])) {
    //$encryptAction = 'pwd3()';
    $encryptSubmit = 'resetPwd();';
    $oldPwdInput   = '原密码：<input type="password" name="old_pwd" id="old_pwd" /><br /><br />新';
    $discardStep   = 'discardBb();';
    $discardAction = "Ajax.Request('../function/mcGate.php?op=d&pwd='+encodeURIComponent(pwd)+'&id='+bid, opt)";
    $getDiscardPwd = "var pwd = $('pwd2').value;";
    $discardCheck  = '';
}

if(!empty($user['fieldpwd']) && empty($_SESSION['loginField' . $uid]))
{
    $petslist = $login;
    $encryptAction = "alert('该牧场已经加密!');return false;";
}




//得到可以设置的宠物
$i = 0;
$utime = $user['tgtime'];
foreach($petsAll as $pall)
{
	if(!is_array($pall)) continue;
	$pallDefaults = array('id' => 0, 'name' => '', 'level' => 0, 'muchang' => 0, 'tgflag' => 0, 'chchengsx' => '');
	foreach($pallDefaults as $defaultKey => $defaultValue)
	{
		if(!isset($pall[$defaultKey])) $pall[$defaultKey] = $defaultValue;
	}
	if($pall['level'] < 10 || $pall['muchang'] != 1 || !empty($pall['chchengsx']))
	{
		continue;
	}
	if($pall['tgflag'] > 0){
		$i++;
		$mesoption .= '<option value='.intval($pall["id"]).'>'.muchangModHtml($pall['name']).'</option>';
	}else{
		$petsoption .= '<option value='.intval($pall["id"]).'>'.muchangModHtml($pall['name']).'</option>';
	}
}
//可以托管的最大数目
$tgnum = max(0, intval($user['tgmax']) - $i);
/* added by Zheng.Ping */

//Word part.
$taskword= taskcheck($user['task'],4);

$_pm['mem']->memClose();
$style = (isset($_GET['style']) && !is_array($_GET['style']) && $_GET['style'] === '2') ? '2' : '';
$sjarr = $_pm['mysql'] -> getOneRecord("SELECT sj FROM player_ext WHERE uid = {$uid}");
if(!is_array($sjarr)) $sjarr = array('sj' => 0);
if(!isset($sjarr['sj'])) $sjarr['sj'] = 0;
//@Load template.
$tn = $_game['template'] . 'tpl_muchang.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);

	$src = array('#money#',
				 '#yb#',
				 '#mclimit#',
				 //right attrib.
				 '#bblist#',
	             '#encryptAction#',
                 '#encryptSubmit#',
                 '#oldPwdInput#',
                 '#discardStep#',
                 '#discardAction#',
                 '#getDiscardPwd#',
                 '#discardCheck#',
				 '#word#',
				 '#one#',
				 '#two#',
				 '#three#',
				 '#mainbb#',
				 '#bb#',
				 '#time#',
				 '#num#',
				 '#mesoption#',
				 '#style#',
				 '#sj#'
				);
	$des = array($user['money'],
				 $user['yb'],
				 $pnum.'/'.$user['maxmc'],
				 //right part
				 $petslist,
				 $encryptAction,
                 $encryptSubmit,
                 $oldPwdInput,
                 $discardStep,
                 $discardAction,
                 $getDiscardPwd,
                 $discardCheck,
				 $taskword,
				 $pets[0],
				 $pets[1],
				 $pets[2],
				 $mainbb,
				 $petsoption,
				 $utime,
				 $tgnum,
				 $mesoption,
				 $style,
				 $sjarr['sj']
				);
	$mc = str_replace($src, $des, $tpl);
}

// gzip echo. if maybe.
ob_start('ob_gzip');
echo $mc;
ob_end_flush();



//托管状态
//$stime  开始托管时间
//$time 托管的时间
function flag($stime,$time)
{
	$now = time();
	if($now < $stime)
	{
		$str = "等待中";
	}
	else
	{
		$times = $now - $stime;
		if($times >= $time)
		{
			$str = "托管完成";
		}
		else
		{
			$str = "托管中";
		}
	}
	return $str;
}

//托管宠物
//$id 托管宠物的ID
//$name 托管宠物的名字
//$pets array 所有的宠物
function petoption($id,$name,$pets)
{
	$str ='<option value='.intval($id).'>'.muchangModHtml($name).'</option>';
	if(!is_array($pets)) $pets = array();
	foreach($pets as $pall)
	{
		if(!is_array($pall)) continue;
		$pallDefaults = array('id' => 0, 'name' => '', 'level' => 0, 'muchang' => 0, 'tgflag' => 0, 'chchengsx' => '');
		foreach($pallDefaults as $defaultKey => $defaultValue)
		{
			if(!isset($pall[$defaultKey])) $pall[$defaultKey] = $defaultValue;
		}
		if($pall['level'] < 10 || $pall['id'] == $id || $pall['muchang'] != 1 ||
			$pall['tgflag'] != 0 || !empty($pall['chchengsx']))
		{
			continue;
		}
		$str .= '<option value='.intval($pall["id"]).'>'.muchangModHtml($pall['name']).'</option>';
	}
	return $str;
}

//托管时间
//$arr 数组
//$time 托管时间
function pettime($time,$arr)
{
	$time1 = $time / 3600;
	$str = '<option value='.$time1.'>'.$time1.'小时</option>';
	if(!is_array($arr)) $arr = array();
	foreach($arr as $v)
	{
		if($v == $time1)
		{
			break;
		}
		$str .= '<option value='.$v.'>'.$v.'小时</option>';
	}
	return $str;
}

//托管内容
//$mes 托管内容
//$arr1 数组 时间
//$arr2 数组 内容
function tgmes($mes,$arr1,$arr2)
{
	$str = '';
	if(!is_array($arr1)) $arr1 = array();
	if(!is_array($arr2)) $arr2 = array();
	foreach($arr1 as $k => $v)
	{
		$title = isset($arr2[$k]) ? $arr2[$k] : '';
		if($v == $mes)
		{
			$str .= '<option value='.$v.' selected=selected>'.$title.'</option>';
		}
		else
		{
			$str .= '<option value='.$v.'>'.$title.'</option>';
		}
	}
	return $str;
}
?>
