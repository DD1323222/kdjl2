<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.12.03
*@Usage: Expore privew. --> 一图多等级
*@Note: 
*/
require_once('../config/config.game.php');
//print_r($_SESSION);
secStart($_pm['mem']);
$m = $_pm['mem'];
$user		= $_pm['user']->getUserById($S->id);
$userBag    = $_pm['user']->getUserBagById($S->id);
$map = unserialize($m->get(MEM_MAP_KEY));
$type = intval($_REQUEST['type']);
$err = 10;
$mapid = abs(intval($_REQUEST['mapid']));
foreach($map as $v)
{
	if($v['id'] == $mapid)
	{
		$name = $v['name'];
		break;
	}
}
foreach($map as $vv)
{
	if($vv['name'] == $name)
	{
		$id[] = $vv['id'];
	}
}
if(is_array($id) && count($id) > 1)
{
	sort($id);
	$mapid = $id[0];
}

if($type == 2)
{
	$err = $mapid + 1;
}
else if($type == 1)
{
	$err = $mapid;//普通
}
else if($type == 3)
{
	$err = $mapid + 2;//冒险
}
$mapinfo=$_pm['mysql']->getOneRecord('select multi_monsters from map where id='.$mapid);
if(!$mapinfo)
{
	die("01");
}else{
	if($mapinfo['multi_monsters']==3&&$_SESSION['team_id'])
	{
		require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
		$s=new socketmsg();
		$team=new team($_SESSION['team_id'],&$s);
		$teamState=$team->getTeamState();
		if(
			!isset($teamState['team_fuben_step'])
			||
			($teamState['team_fuben_step'][0]==0&&$teamState['team_fuben_step'][1]==0)
		){		
			$isleader=$team->isTeamLeader($_SESSION['id'],$_SESSION['team_id']);
			if($isleader)
			{
				$state['team_select_map']=$err;
				$team->setTeamState($state);
			}
		}else{
			die("01");
		}
	}
}
$_pm['mysql'] -> query("UPDATE player SET inmap = $err WHERE id = {$_SESSION['id']}");
echo $err;
$_pm['mem']->memClose();
?>
