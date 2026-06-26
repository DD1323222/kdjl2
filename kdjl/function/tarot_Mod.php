<?php
require_once('../config/config.game.php');
secStart($_pm['mem']);
require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
$s=new socketmsg();
$team=new team($_SESSION['team_id'],&$s);
$point = $team -> get_team_funben_card_step();
//$point = 1;
//echo $point;
unset($_SESSION['teamfb']);
if($point == 1 || $point == 2){
	$tn = $_game['template'] . 'tpl_tarot.html';
}else if($point == 3){
	$jsstr='var openstr=[];
';
	$ar = unserialize($_pm['mem']->get('tarot_info_'.$_SESSION['team_id']));//print_r($ar);
	if(is_array($ar)){		
		$i=0;
		foreach($ar as $v){
			$jsstr.='openstr['.($i).']=["'.$v['id'].'","'.$v['img'].'"];
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
window.location="/function/Team_Mod.php?n='.$_SESSION['team_inmap'].'";
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