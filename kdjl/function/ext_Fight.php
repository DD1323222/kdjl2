<?php 
/**
@Usage: Auto Fight set
*/
require_once('../config/config.game.php');
//secStart($_pm['mem']);
$user		= $_pm['user']->getUserById($_SESSION['id']);
$op = $_REQUEST['op'];
//if($_SESSION['multi_monsters'.$_SESSION['id']] != 2){
	//die('exit');
//}
if($op == 1)//元宝版
{
	if ($user['maxautofitsum']>0) 
	{
		$user['autofitflag']=1;
		$_SESSION['exptype'.$_SESSION['id']] = 1;
		$_SESSION['way'.$_SESSION['id']] = "yb";
	}
	else $user['maxautofitsum']=0;
	echo $user['maxautofitsum'];
	$_pm['mysql']->query("UPDATE player
					     SET maxautofitsum={$user['maxautofitsum']},
							 autofitflag={$user['autofitflag']}
					   WHERE id={$_SESSION['id']}
					");
	$_SESSION['fttime'.$_SESSION['id']]=3;
}
else if($op == 2)//金币版
{
	if ($user['sysautosum']>0) 
	{
		$user['autofitflag']=1;
		$_SESSION['exptype'.$_SESSION['id']] = 1;
		$_SESSION['way'.$_SESSION['id']] = "money";
		$_SESSION['fttime'.$_SESSION['id']]=4;
	}
	else $user['maxautofitsum']=0;
	echo $user['sysautosum'];
	$_pm['mysql']->query("UPDATE player
					     SET sysautosum={$user['sysautosum']},
							 autofitflag={$user['autofitflag']}
					   WHERE id={$_SESSION['id']}
					");	
}
else if($op == 3)//关闭元宝版
{
	$user['autofitflag']=0;
	$_SESSION['way'.$_SESSION['id']] = "";
	echo $user['maxautofitsum'];
	$_pm['mysql']->query("UPDATE player
					     SET maxautofitsum={$user['maxautofitsum']},
							 autofitflag={$user['autofitflag']}
					   WHERE id={$_SESSION['id']}
					");
}
//关闭金币版
else if($op == 4)//关闭金币版
{
	$user['autofitflag']=0;
	$_SESSION['way'.$_SESSION['id']] = "";
	echo $user['sysautosum'];
	$_pm['mysql']->query("UPDATE player
					     SET sysautosum={$user['sysautosum']},
							 autofitflag={$user['autofitflag']}
					   WHERE id={$_SESSION['id']}
					");
}

/*
require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
if(isset($_SESSION['team_id'])){
	$s=new socketmsg();
	$team=new team($_SESSION['team_id'],&$s);

	$teamInfo=$team->getTeamInfo();
	
	$team->clearTeamState();
}
*/
$_pm['mem']->memClose();
//####################
?>