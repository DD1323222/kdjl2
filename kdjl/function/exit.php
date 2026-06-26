<?php
die();
require_once('../config/config.game.php');
if ( !isset($_SESSION['id']) || intval($_SESSION['id']) < 0 || $_SESSION['id'] == '') exit();

$crc = crc32($_REQUEST[PHPSESSID]);
$truecrc = unserialize($_pm['mem']->get($_SESSION['id'] . 'chat'));
if ($crc != $truecrc)
{
	$err = 1;
}
else
{	// 2: autofit,1:wg,>2: useroption;
	// 外挂检测部分。
	/* 2009-07-23 注释掉此段,lastvtime更新lastvtime已经放到onlineStat.php
	if(WG_CHECK==1)
	{
		$cid = isset($_REQUEST['ssid'])?addslashes($_REQUEST['ssid']):1;
		if ($user['autofitflag'] == 1) // Open auto fight.
		{
			$cid = 2;
		}

		$fix = " ,wg={$cid}";
	}
	else $fix = '';
	if($fix!=''){
		//Save lastvtime and wg record!
		$_pm['mysql']->query("UPDATE player SET lastvtime=".time()."{$fix}
						   WHERE id={$_SESSION['id']}
						");
	}
	*/
}
//####################
?>