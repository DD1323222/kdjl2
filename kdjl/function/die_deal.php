<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.05.29
*@Usage:Fightting Display
*@Note: none
Mem style.
*/
require_once('../config/config.game.php');
switch($_GET['cmd'])
{
	case 'cancel' :
	{
		$_pm['mysql'] -> query("UPDATE player_ext SET F_saolei_points = '1'  WHERE uid = ".$_SESSION['id']);
		break;
	}
	case 'used':
	{
		$sl_fhtime = $_pm['mysql'] -> getOneRecord(" SELECT sums FROM userbag WHERE pid = 4038 AND uid =  {$_SESSION['id']}");
		if($sl_fhtime['sums'] > 0)
		{
			$option = unserialize($_pm['mem']->get('sl_die_option'));
			$option = $option[$_SESSION['id']];
			$_pm['mysql'] -> query("UPDATE player_ext SET F_saolei_points = '".$option."'  WHERE uid = ".$_SESSION['id']);
			$_pm['mysql'] -> query("UPDATE userbag SET sums = sums -1  WHERE pid = 4038 AND uid =  {$_SESSION['id']}");
			$_pm['mysql'] -> query("DELETE FROM  userbag WHERE pid = 4038 AND uid = {$_SESSION['id']} AND sums = 0 AND bsum = 0 AND psum = 0");
			$today_sl_ticket_use = unserialize($_pm['mem']->get('today_is_use_ticket'));
			if(is_array($today_sl_ticket_use))
			{
				$today_sl_ticket_use[] = $_SESSION['id'];
			}
			else
			{
				$today_sl_ticket_use = array($_SESSION['id']);
			}
			$_pm['mem']->set(array('k' => 'today_is_use_ticket', 'v' => $today_sl_ticket_use));
		}
		break;
	}
	case 'new' :
	{
		$_pm['mysql'] -> query("UPDATE player_ext SET F_saolei_points = '1'  WHERE uid = ".$_SESSION['id']);
		$today_sl_user = unserialize($_pm['mem']->get('today_sl_user'));
		if(!is_array($today_sl_user))
		{
			$today_sl_user = array($_SESSION['id']);
		}
		else
		{
			if( !in_array($_SESSION['id'],$today_sl_user) )
			{
				$today_sl_user[] = $_SESSION['id'];
			}
		}
		$_pm['mem']->set(array('k' => 'today_sl_user', 'v' => $today_sl_user));
		$today_sl_ticket_use = unserialize($_pm['mem']->get('today_is_use_ticket'));
		if(is_array($today_sl_ticket_use))
		{
			foreach($today_sl_ticket_use as $key => $info)
			{
				if($info == $_SESSION['id'])
				{
					unset($today_sl_ticket_use[$key]);
				}
			}
		}
		$_pm['mem']->set(array('k' => 'today_is_use_ticket', 'v' => $today_sl_ticket_use));
	}
	default :
	{
		die();
		break;
	}
}

?>
