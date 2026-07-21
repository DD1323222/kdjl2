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
session_start();
require_once('../config/config.game.php');

secStart($_pm['mem']);

function sdComposeHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function sdComposeImage($value)
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

$compets = array('', '');
$comapetslist = '';
$combblistid = '';
$plist = '';
$shop = '';

if (is_array($petsAll))
{
	$kk=0;
	foreach ($petsAll as $k => $rs)
	{
		if(!is_array($rs)) continue;
		$rs['id'] = isset($rs['id']) ? intval($rs['id']) : 0;
		$rs['muchang'] = isset($rs['muchang']) ? intval($rs['muchang']) : 0;
		$rs['tgflag'] = isset($rs['tgflag']) ? intval($rs['tgflag']) : 0;
		$rs['level'] = isset($rs['level']) ? intval($rs['level']) : 0;
		$rs['name'] = isset($rs['name']) ? $rs['name'] : '';
		$rs['cardimg'] = isset($rs['cardimg']) ? sdComposeImage($rs['cardimg']) : '';
		if ($rs['id'] < 1 || $rs['muchang'] != 0 || $rs['tgflag'] != 0 || $rs['level']<40) continue;
		$compets[$kk++] = "<img src='".IMAGE_SRC_URL."/bb/{$rs['cardimg']}' onclick='Display({$rs['id']});' style='cursor:pointer;display:none;' id='cp{$kk}'>";
		$comapetslist .= "<option value='{$rs['id']}'>".sdComposeHtml($rs['name'])."-{$rs['level']}</option>\n";
		$combblistid .= $combblistid?",'{$rs['id']}-{$rs['cardimg']}'":"'{$rs['id']}-{$rs['cardimg']}'";
		if ($kk == 3) break;
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
		$v['id'] = isset($v['id']) ? intval($v['id']) : 0;
		$v['name'] = isset($v['name']) ? $v['name'] : '';
		$v['sums'] = isset($v['sums']) ? intval($v['sums']) : 0;
		$v['varyname'] = isset($v['varyname']) ? intval($v['varyname']) : 0;
		$v['effect'] = isset($v['effect']) ? $v['effect'] : '';
		$v['usages'] = isset($v['usages']) ? $v['usages'] : '';
		if ($v['varyname']!=8 || $v['effect']=='') continue;
		$money = 0;
		// Get money;
		// effect format: luck:B:10%:5000, shbb:5000
		$one = explode(',', $v['effect']);
		foreach ($one as $a => $b)
		{
			$arr = explode(':', $b);
			$money += intval($arr[count($arr)-1]);
		}
		$name = explode(":",$v['usages']);
		if($v['sums'] > 0 && $name[0] != '涅盘')
		{
			$plist .= "<option value='{$v['id']}'>".sdComposeHtml($v['name'])."-{$money}-{$v['sums']}个</option>\n";
		}
	}
}

//Word part.
$taskword= taskcheck($user['task'], 2);
$_pm['mem']->memClose();

//get xingyunxin
$a=$_pm['mysql']->getOneRecord("select hecheng_nums from player_ext where uid='{$uid}'");
if($err=mysql_error($_pm['mysql']->getConn()))
{
	if(strpos($err,'hecheng_nums')!==false)
	{
		$_pm['mysql']->addColumnIfMissing('player_ext', 'hecheng_nums', 'int(11) null default 0');
		$a=$_pm['mysql']->getOneRecord("select hecheng_nums from player_ext where uid='{$uid}'");
	}
}
$xingyunxin=(is_array($a) && isset($a['hecheng_nums'])) ? intval($a['hecheng_nums']) : 0;
if($xingyunxin < 0) $xingyunxin = 0;
if($xingyunxin > 10) $xingyunxin = 10;

//@Load template.
$tn = $_game['template'] . 'tpl_compose.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);

	$src = array("#word#",
				 "#comone#",
				 "#comtwo#",
				 "#comapetslist#",
				 "#bpetslist#",
				 "#wupinone#",
				 "#wupintwo#",
				 "#bballid#",
				 "#xingyunxin#"
				);
	$des = array($taskword,
				 $compets[0],
				 $compets[1],
				 $comapetslist,
				 $comapetslist,
				 $plist,
				 $plist,
				 $combblistid,
				 $xingyunxin
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
