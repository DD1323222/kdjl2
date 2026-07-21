<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.05.22
*@Usage: Userinfo
*@Note: none
*/
require_once('../config/config.game.php');
//if ($_SESSION['nickname']!='GM') die('关闭调试！');
secStart($_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');

function userModHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function userModJsSingle($value)
{
	$value = str_replace("\\", "\\\\", (string)$value);
	$value = str_replace("'", "\\'", $value);
	$value = str_replace(array("\r", "\n"), array("\\r", "\\n"), $value);
	return $value;
}

$pinfo = '';
$requestType = (isset($_REQUEST['type']) && !is_array($_REQUEST['type'])) ? $_REQUEST['type'] : '';
if($requestType == "list")
{
	$list = 1;
}
else
{
	$list = 2;
}

if(isset($_GET['tiaozhan']))
{
	$_pm['mysql'] ->query('INSERT INTO player_ext(uid,bbshow,tiaozhan) VALUES('.$uid.',5,1) ON DUPLICATE KEY UPDATE tiaozhan=ABS(COALESCE(tiaozhan,0)-1)');
}

$user		= $_pm['user']->getUserById($uid);
$petsAll	= $_pm['user']->getUserPetById($uid);
if(!is_array($user)) $user = array();
if(!is_array($petsAll)) $petsAll = array();
define("MEM_BLACKLIST_KEY","db_blacklist");
$sjarr = $_pm['mysql'] -> getOneRecord("SELECT sj,merge,team_auto_times,tiaozhan FROM player_ext WHERE uid = {$uid}");
if(!is_array($sjarr)) $sjarr = array('sj' => 0, 'merge' => 0, 'team_auto_times' => 0, 'tiaozhan' => 0);
$blacklist = $_pm['mem'] -> get(MEM_BLACKLIST_KEY);
if(!is_array($blacklist)) $blacklist = kdjlSafeMemValue($blacklist, array());
$blacklist = is_array($blacklist) ? $blacklist : array();
$friendlist = '';
$lists = '';
$userDefaults = array(
	'nickname' => '',
	'headimg' => '',
	'sex' => '',
	'fighttop' => '',
	'money' => 0,
	'yb' => 0,
	'sysautosum' => 0,
	'maxautofitsum' => 0,
	'dblstime' => 0,
	'maxdblexptime' => 0,
	'score' => 0,
	'prestige' => 0,
	'jprestige' => 0,
	'active_score' => 0,
	'vip' => 0,
	'viplast' => 0,
	'dblexpflag' => 0,
	'friendlist' => ''
);
foreach($userDefaults as $defaultKey => $defaultValue)
{
	if(!isset($user[$defaultKey])) $user[$defaultKey] = $defaultValue;
}
$teamauto=intval($sjarr['team_auto_times']);
$tiaozhan='<a href="?tiaozhan" title="点击修改">'.($sjarr['tiaozhan']==1?'允许':'不允许').'</a>';
if(is_array($sjarr) && $sjarr['merge']>0){
	$user1		= $_pm['user']->getUserById($sjarr['merge']);
	$mergename="婚配:".((is_array($user1) && isset($user1['nickname'])) ? $user1['nickname'] : '');
}else{
	$mergename="婚姻:未婚";
}
/**
获得好友列表。
*/
if ($user['friendlist'] !== '')
{
	//$friendlist = $user['friendlist'];
	$arr = explode(',', $user['friendlist']);
	foreach($arr as $k => $v)
	{
		if($v == '') continue;
		$vHtml = userModHtml($v);
		$vJs = userModHtml(userModJsSingle($v));
		$friendlist .= "<span style='cursor:pointer;display:block;' onclick=\"chat('{$vJs}');\"><u>".$vHtml . '</u></span>';
		//$friendlist .= $v.",";
	}

}
else $friendlist='您还未添加任何好友！';
if(!empty($blacklist[$uid]))
{
	$blacklists = $blacklist[$uid];
	$arr = explode(',', $blacklists);
	foreach($arr as $k => $v)
	{
		if($v == '') continue;
		$vHtml = userModHtml($v);
		$vJs = userModHtml(userModJsSingle($v));
		$lists .= "<span style='cursor:pointer;display:block;' onclick=\"blacks('{$vJs}');\"><u>".$vHtml . '</u></span>';
		//$list .= $v.",";
	}
}
if(empty($blacklist[$uid]) || $blacklist[$uid] == ",,")
{
	$lists = "您还未添加任何黑名单！";
}





$tn = $_game['template'] . 'tpl_user.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);
	switch($user['dblexpflag'])
	{
		case 2: $dbl = 1.5;break;
		case 3: $dbl = 2;break;
		case 4: $dbl = 2.5;break;
		case 5: $dbl = 3;break;
		default:$dbl = 1;break;
	}

	$src = array(
				 '#sj#',
				 '#nickname#',
				 '#userbigimg#',
				 '#vary#',
				 '#sex#',
				 '#pets#',
				 '#success#',
				 '#money#',
				 '#yb#',
				 '#auto#',
				 '#auto1#',
				 '#dbltime#',
				 '#dbl#',
				 "#friendlist#",
				 "#jifen#",
				 "#prestige#",
				 "#jprestige#",
				 "#activejifen#",
				 "#vip#",
				 "#viplast#",
				 "#blacklist#",
				 "#list#",
				 "#merge#",'#teamauto#','#tiaozhan#'
				);
	$userNicknameHtml = userModHtml($user['nickname']);
	$userHeadimg = intval($user['headimg']);
	$userSexHtml = userModHtml($user['sex']);
	$mergenameHtml = userModHtml($mergename);
	$des = array(
				 $sjarr['sj'],
				 $userNicknameHtml,
				 '3'.$userHeadimg,
				 '',
				 $userSexHtml,
				 count($petsAll),
				 '胜：'.($user['fighttop']?str_replace(':',', 败：',$user['fighttop']):("0, 败：0")),
				 $user['money'],
				 $user['yb']?$user['yb']:0,
				 $user['sysautosum'],
				 $user['maxautofitsum'],
				 $et=($tot=intval($user['dblstime']+$user['maxdblexptime']-time()))<0?0:$tot,
				 $dbl,
				 $friendlist,
				 $user['score'],
				 $user['prestige'],
				 $user['jprestige'],
				 $user['active_score'],
				 $user['vip'],
				 $user['viplast'],
				 $lists,
				 $list,
				 $mergenameHtml,$teamauto,$tiaozhan
				);
	$pinfo = str_replace($src, $des, $tpl);
}

// gzip echo. if maybe.
ob_start('ob_gzip');
echo $pinfo;
ob_end_flush();
?>
