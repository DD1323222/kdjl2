<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: 谭炜

*@Write Date: 2008.09.12
*@Update Date: 2008.09.12
*@Usage: 装备强化
*@Note: NO Add magic props.
  本模块主要功能：
  	 判断装备强化
*/
require_once('../config/config.game.php');
@session_start();
secStart($_pm['mem']);
//道具id
$id = intval($_REQUEST['pid']);
//得到要强化的装备或道具的相关信息
$err = 0;
$rs	= unserialize($_pm['mem']->get('db_propsid'));
$bid = intval($_GET['bid']);
if(!is_array($rs))
{
	die("0");
}
$props = $rs[$id];
if($props['varyname'] != 9 && $props['varyname'] != 11)
{
	die("0");//得到数据错误
}
if($props['varyname'] == 9)
{
	//判断该装备是否可强化
	if($props['plusflag'] != 1)
	{
		die("1");//该装备不可强化
	}
	else
	{
		$ar = $_pm['mysql'] -> getOneRecord("SELECT id FROM userbag WHERE pid = $id AND uid = {$_SESSION['id']} AND zbing != 1 AND id = $bid");
		if(!is_array($ar)){
			die(0);
		}
		$str = '&pid='.$id.'&pids='.$_SESSION['pids'.$_SESSION['id']].'&bid='.$bid;
		echo $str;
		exit;
	}
}else{
	$str = '&pids='.$id.'&pid='.$_SESSION['pid'.$_SESSION['id']].'&bid='.$_SESSION['bid'.$_SESSION['id']];
	echo $str;
	exit;
}
echo $err;
?>