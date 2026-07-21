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
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : (isset($S->id) ? intval($S->id) : 0);
if($uid < 1) die("01");
$user		= $_pm['user']->getUserById($uid);
if(!is_array($user)) die("01");
$map = kdjlSafeMemValue($m->get(MEM_MAP_KEY), array());
if(!is_array($map)) $map = array();
$type = (isset($_REQUEST['type']) && !is_array($_REQUEST['type'])) ? intval($_REQUEST['type']) : 0;
$mapid = (isset($_REQUEST['mapid']) && !is_array($_REQUEST['mapid'])) ? abs(intval($_REQUEST['mapid'])) : 0;
if($mapid < 1 || ($type != 1 && $type != 2 && $type != 3)) die("01");
$err = $mapid;
$name = '';
$id = array();
foreach($map as $v)
{
	if(!is_array($v) || !isset($v['id'], $v['name'])) continue;
	if($v['id'] == $mapid)
	{
		$name = $v['name'];
		break;
	}
}
foreach($map as $vv)
{
	if(!is_array($vv) || !isset($vv['id'], $vv['name'])) continue;
	if($vv['name'] == $name)
	{
		$id[] = $vv['id'];
	}
}
if($name == '') die("01");
sort($id, SORT_NUMERIC);
if(count($id) !== 3 || !isset($id[$type - 1])) die("01");
$baseMapid = intval($id[0]);
$err = intval($id[$type - 1]);
if($baseMapid < 1 || $err < 1 || $err != $baseMapid + $type - 1) die("01");

$targetMap = false;
foreach($map as $mapRow)
{
	if(!is_array($mapRow) || !isset($mapRow['id'], $mapRow['name'])) continue;
	if(intval($mapRow['id']) == $err && $mapRow['name'] == $name)
	{
		$targetMap = $mapRow;
		break;
	}
}
if(!is_array($targetMap)) die("01");

$mapinfo=$_pm['mysql']->getOneRecord('select multi_monsters from map where id='.$err);
if(!$mapinfo || !is_array($mapinfo) || !isset($mapinfo['multi_monsters']))
{
	die("01");
}else{
	if($mapinfo['multi_monsters']==3 && !empty($_SESSION['team_id']))
	{
		require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
		$s=new socketmsg();
		$teamId = intval($_SESSION['team_id']);
		$team=new team($teamId,$s);
		$teamState=$team->getTeamState();
		$teamStep = (isset($teamState['team_fuben_step']) && is_array($teamState['team_fuben_step'])) ? $teamState['team_fuben_step'] : array(0,0);
		if(!isset($teamStep[0])) $teamStep[0] = 0;
		if(!isset($teamStep[1])) $teamStep[1] = 0;
		if(
			!isset($teamState['team_fuben_step'])
			||
			($teamStep[0]==0&&$teamStep[1]==0)
		){
			$isleader=$team->isTeamLeader($uid,$teamId);
			if($isleader)
			{
				$state = array();
				$state['team_select_map']=$err;
				$team->setTeamState($state);
			}
		}else{
			die("01");
		}
	}
}
if(!$_pm['mysql'] -> query("UPDATE player SET inmap = $err WHERE id = {$uid}")) die("01");
$_pm['mem']->del(MEM_USER_KEY);
echo $err;
$_pm['mem']->memClose();
?>
