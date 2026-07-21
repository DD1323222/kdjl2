<?php
require_once('../config/config.game.php');
secStart($_pm['mem']);
require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('登录状态已失效，请重新登录！');
$teamId = isset($_SESSION['team_id']) ? intval($_SESSION['team_id']) : 0;
if($teamId < 1) die('你没有组队！');
$s=new socketmsg();
$team=new team($teamId,$s);
$teamInfo = $team->getTeamInfo();
$activeMember = false;
if(is_array($teamInfo) && isset($teamInfo['members']) && is_array($teamInfo['members']))
{
	foreach($teamInfo['members'] as $memberRow)
	{
		if(!is_array($memberRow)) continue;
		if(isset($memberRow['uid'], $memberRow['state']) && intval($memberRow['uid']) === $uid && intval($memberRow['state']) == 1)
		{
			$activeMember = true;
			break;
		}
	}
}
if(!$activeMember) die('你已不在当前队伍中！');
if(isset($teamInfo['team']) && is_array($teamInfo['team']) && isset($teamInfo['team']['inmap']))
{
	$_SESSION['team_inmap'] = intval($teamInfo['team']['inmap']);
}
function tarotModJsDouble($value)
{
	return str_replace(array('\\', '"', "\r", "\n", '<', '>'), array('\\\\', '\\"', '', '', '\\x3C', '\\x3E'), strval($value));
}
function tarotModImage($value)
{
	$value = basename(strval($value));
	if(!preg_match('/^[A-Za-z0-9_.-]+$/D', $value) ||
		!file_exists(dirname(__FILE__).'/../images/tarot/'.$value)) return 'card.gif';
	return $value;
}
$point = $team -> get_team_funben_card_step();
$jsstr = '';
$pinfo = '';
//$point = 1;
//echo $point;
unset($_SESSION['teamfb']);
if($point == 1 || $point == 2){
	$tn = $_game['template'] . 'tpl_tarot.html';
}else if($point == 3){
	$jsstr='var openstr=[];
';
	$arValue = $_pm['mem']->get('tarot_info_'.$teamId);
	$ar = kdjlSafeMemValue($arValue, array());//print_r($ar);
	if(is_array($ar)){
		$i=0;
		foreach($ar as $v){
			if(!is_array($v)) continue;
			$cardId = isset($v['id']) ? intval($v['id']) : 0;
			$cardImg = tarotModJsDouble(tarotModImage(isset($v['img']) ? $v['img'] : ''));
			$jsstr.='openstr['.($i).']=["'.$cardId.'","'.$cardImg.'"];
';
			$i++;
		}
	}//echo $jsstr;
	$_SESSION['gs'] = 3;
	$tn = $_game['template'] . 'tpl_tarot1.html';
}else{
	$msg='错误的请求！';
	if($point=='0a')
	{
		$msg='你没有组队！';
	}else if($point=='0b')
	{
		$msg='现在不能翻牌！';
	}else if($point=='0c')
	{
		$msg='只允许队长操作';
	}
	die('<script language="javascript">
parent.recvMsg("SM|<font color=\'#442266\'>'.$msg.'</font>");
window.location="/function/Team_Mod.php?n='.(isset($_SESSION['team_inmap']) ? intval($_SESSION['team_inmap']) : 0).'";
</script>');
}
if (file_exists($tn)){
	$tpl = @file_get_contents($tn);

	$src = array(
				 '#js#'
				 );
	$des = array(
				  $jsstr
				);
	$pinfo = str_replace($src, $des, $tpl);
}

// gzip echo. if maybe.
ob_start();
echo $pinfo;
ob_end_flush();
$_pm['mem']->memClose();
?>
