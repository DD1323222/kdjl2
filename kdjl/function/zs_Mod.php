<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.05.22
*@Usage: 宠物合成系统
         大致流程：玩家携带的宠物中，任意选择两个，并选择需要添加爱的道具后，即可开始合成。
*@Note: none
*/
require_once('../config/config.game.php');

secStart($_pm['mem']);

function zsModHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function zsModImage($value)
{
	$value = basename((string)$value);
	return preg_match('/^[A-Za-z0-9_.-]+$/', $value) ? $value : '';
}

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');
$user		= $_pm['user']->getUserById($uid);
$petsAll	= $_pm['user']->getUserPetById($uid);
$bag		= $_pm['user']->getUserBagById($uid);
if(!is_array($user)) die('');
if(!is_array($petsAll)) $petsAll = array();
if(!is_array($bag)) $bag = array();
$zspets = array('', '');
$zsoption = '';
$zsapetslist = '';
$zsbblistid = '';
$zsplist = '';
$shop = '';
/*if($user['name'] != 'tanwei2008' && $user['name'] != 'boss')
{
die("维护中，相关功能周一开放");
}*/
//die("维护中，相关功能周一开放");
if (is_array($petsAll))
{
	$zskk=0;
	foreach ($petsAll as $k => $rs)
	{
		if(!is_array($rs)) continue;
		if(!isset($rs['id'])) $rs['id'] = 0;
		if(!isset($rs['level'])) $rs['level'] = 0;
		if(!isset($rs['name'])) $rs['name'] = '';
		if(!isset($rs['muchang'])) $rs['muchang'] = 0;
		if(!isset($rs['tgflag'])) $rs['tgflag'] = 0;
		if(!isset($rs['wx'])) $rs['wx'] = 0;
		if(!isset($rs['cardimg'])) $rs['cardimg'] = '';
		$petId = intval($rs['id']);
		$petLevel = intval($rs['level']);
		$petNameHtml = zsModHtml($rs['name']);
		$cardImg = zsModImage($rs['cardimg']);
		if($rs['level'] >= 60 && ($rs['name'] == "涅磐兽（亥）" || $rs['name'] == "涅磐兽（午）" || $rs['name'] == "涅磐兽（卯）") && $rs['muchang'] == 0 && intval($rs['tgflag']) == 0)
		{
			$zsoption .= "<option value='{$petId}'>{$petNameHtml}-{$petLevel}</option>\n";
		}
		if ($rs['muchang'] != 0 || intval($rs['tgflag']) != 0 || $rs['level']<60 || $rs['wx'] != 6 || $rs['name'] == "涅磐兽（亥）" || $rs['name'] == "涅磐兽（午）" || $rs['name'] == "涅磐兽（卯）") continue;
		$zspets[$zskk++] = "<img src='".IMAGE_SRC_URL."/bb/{$cardImg}' onclick='Display({$petId});' style='cursor:pointer;display:none;' id='cp{$zskk}'>";
		$zsapetslist .= "<option value='{$petId}'>{$petNameHtml}-{$petLevel}</option>\n";
		$zsbblistid .= $zsbblistid?",'{$petId}-{$cardImg}'":"'{$petId}-{$cardImg}'";
		if ($zskk == 3) break;
	}
}

/**
*@Get Bag.
*/
if (is_array($bag))
{
	$i = 0;
	foreach($bag as $k => $v)
	{
		if(!is_array($v)) continue;
		if(!isset($v['id'])) $v['id'] = 0;
		if(!isset($v['name'])) $v['name'] = '';
		if(!isset($v['sums'])) $v['sums'] = 0;
		if(!isset($v['usages'])) $v['usages'] = '';
		$effarr = explode(":",$v['usages']);
		if($effarr[0] != '涅盘')
		{
			continue;
		}
		// Get money;
		// effect format: luck:B:10%:5000, shbb:5000
		if(!empty($v['sums']))
		{
			$zsplist .= "<option value='".intval($v['id'])."'>".zsModHtml($v['name'])."-".intval($v['sums'])."个</option>\n";
		}
	}
}


//Word part.
$taskword= taskcheck($user['task'], 2);
$_pm['mem']->memClose();


//@Load template.
$tn = $_game['template'] . 'tpl_zs.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);

	$src = array("#word#",
				 "#zsone#",
				 "#zstwo#",
				 "#zsapetslist#",
				 "#zsbpetslist#",
				 "#zswupinone#",
				 "#zsbballid#",
				 "#zsoptions#"
				);
	$des = array($taskword,
				 $zspets[0],
				 $zspets[1],
				 $zsapetslist,
				 $zsapetslist,
				 $zsplist,
				 $zsbblistid,
				 $zsoption
				);
	$shop = str_replace($src, $des, $tpl);
}
// gzip echo. if maybe.
ob_start('ob_gzip');
echo $shop;
ob_end_flush();

// Get props name for pid.
// @return: false or String.
function getPropsName($pid)
{
	global $_pm;
	/*$rs = $_pm['mem']->dataGet(array('k' => MEM_PROPS_KEY,
							'v' => "if(\$rs['id'] == {$pid}) \$ret=\$rs;"
						));*/
	$mempropsid = kdjlSafeMemValue($_pm['mem']->get('db_propsid'), array());
	$rs = (is_array($mempropsid) && isset($mempropsid[$pid])) ? $mempropsid[$pid] : false;
	if (is_array($rs)) return $rs['name'];
	else return false;
}
?>
