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
if($_REQUEST['type'] == "list")
{
	$list = 1;
}
else
{
	$list = 2;
}

if(isset($_GET['tiaozhan']))
{
	$_pm['mysql'] ->query('update player_ext set tiaozhan=abs(tiaozhan -1) where uid='.$_SESSION['id']);
}

$user		= $_pm['user']->getUserById($_SESSION['id']);
$petsAll	= $_pm['user']->getUserPetById($_SESSION['id']);
define("MEM_BLACKLIST_KEY","db_blacklist");
$sjarr = $_pm['mysql'] -> getOneRecord("SELECT sj,merge,team_auto_times,tiaozhan FROM player_ext WHERE uid = {$_SESSION['id']}");
$blacklist = unserialize($_pm['mem'] -> get(MEM_BLACKLIST_KEY));
$teamauto=intval($sjarr['team_auto_times']);
$tiaozhan='<a href="?tiaozhan" title="点击修改">'.($sjarr['tiaozhan']==1?'允许':'不允许').'</a>';
if(is_array($sjarr) && $sjarr['merge']>0){
	$user1		= $_pm['user']->getUserById($sjarr['merge']);
	$mergename="婚配:".$user1['nickname'];
}else{
	$mergename="婚姻:未婚";
}
/**
获得好友列表。
*/
if (strlen($user['friendlist']) > 3 )
{
	//$friendlist = $user['friendlist'];
	$arr = explode(',', $user['friendlist']);
	foreach($arr as $k => $v)
	{
		$friendlist .= "<span style='cursor:pointer;display:block;' onclick=\"chat('{$v}');\"><u>".$v . '</u></span>';
		//$friendlist .= $v.",";
	}
	
}
else $friendlist='您还未添加任何好友！';
if(!empty($blacklist[$_SESSION['id']]))
{
	$blacklists = $blacklist[$_SESSION['id']];
	$arr = explode(',', $blacklists);
	foreach($arr as $k => $v)
	{
		$lists .= "<span style='cursor:pointer;display:block;' onclick=\"blacks('{$v}');\"><u>".$v . '</u></span>';
		//$list .= $v.",";
	}
}
if(empty($blacklist[$_SESSION['id']]) || $blacklist[$_SESSION['id']] == ",,")
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
	$des = array(
				 $sjarr['sj'],
				 $user['nickname'],
				 '3'.$user['headimg'],
				 '',
				 $user['sex'],
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
				 $mergename,$teamauto,$tiaozhan
				);
	$pinfo = str_replace($src, $des, $tpl);
}

// gzip echo. if maybe.
ob_start('ob_gzip');
echo $pinfo;
ob_end_flush();
?>