<?php 
require_once('../config/config.game.php');
secStart($_pm['mem']);

$user		= $_pm['user']->getUserById($_SESSION['id']);

$op = $_REQUEST['op'];
if ($op == 'getauto')
{
	if ($user['sysautotime']==0 || $user['sysautotime']<mktime(0, 0, 0, date("m",time()), date("d",time()), date("Y",time())))
	{
	 	$user['sysautosum']	+=	800;
		$user['sysautotime']=	time();
		$_pm['mysql']->query("UPDATE player
							     SET sysautosum={$user['sysautosum']},
									 sysautotime={$user['sysautotime']}
							   WHERE id={$_SESSION['id']}
						");
		echo "恭喜您，领取自动战斗寻怪奖励成功!";
		//$u->updateMemUser($_SESSION['id']);
	}
	else echo "您今天已经领取过了，每天只能一次噢！<br/>除非您到了一个级别，我会考虑特别奖励！";	
}
$_pm['mem']->memClose();
//####################
?>