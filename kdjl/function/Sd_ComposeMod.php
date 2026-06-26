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

$user		= $_pm['user']->getUserById($_SESSION['id']);
$petsAll	= $_pm['user']->getUserPetById($_SESSION['id']);
$bag		= $_pm['user']->getUserBagById($_SESSION['id']);

if (is_array($petsAll))
{   
	$kk=0;
	foreach ($petsAll as $k => $rs)
	{
		if ($rs['muchang'] == 1 || $rs['level']<40) continue;
		$compets[$kk++] = "<img src=''.IMAGE_SRC_URL.'/bb/{$rs['cardimg']}' onclick='Display({$rs['id']});' style='cursor:pointer;display:none;' id='cp{$kk}'>";
		$comapetslist .= "<option value='{$rs['id']}'>{$rs['name']}-{$rs['level']}</option>\n";
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
		if ($v['varyname']!=8 || $v['effect']=='') continue;
		$money = 0;
		// Get money;
		// effect format: luck:B:10%:5000, shbb:5000
		$one = explode(',', $v['effect']);
		foreach ($one as $a => $b)
		{
			$arr = explode(':', $b);
			$money+=$arr[count($arr)-1];
		}
		$name = explode(":",$v['usages']);
		if(!empty($v['sums']) && $name[0] != '涅盘')
		{
			$plist .= "<option value='{$v['id']}'>{$v['name']}-{$money}-{$v['sums']}个</option>\n";
		}
	}
}

//Word part.
$taskword= taskcheck($user['task'], 2);
$_pm['mem']->memClose();

//get xingyunxin
$a=$_pm['mysql']->getOneRecord("select hecheng_nums from player_ext where uid='{$_SESSION['id']}'");
$xingyunxin=$a['hecheng_nums'];

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
	$mempropsid = unserialize($_pm['mem']->get('db_propsid'));
	$rs = $mempropsid[$pid];

	if (is_array($rs)) return $rs['name'];
	else return false;	
}
?>