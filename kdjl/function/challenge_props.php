<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2009.12.14
*@Update Date: 
*@Usage:Get User challenge props.
*@Note: 
*/
require_once('../config/config.game.php');
secStart($_pm['mem']);

$op = $_GET['op'];
if($op == 'propslist'){
	$arr = $_pm['mysql'] -> getRecords("SELECT userbag.id,name,sums FROM userbag,props WHERE uid = {$_SESSION['id']} AND pid = props.id AND props.varyname = 18");
	if(empty($arr)){
		die('没有此类道具！');
	}
	foreach($arr as $v){
		$str .= $v['name'].':'.$v['id'].',';
	}
	echo $str;
}else if($op == 'usedprops'){
	if($_SESSION['multi_monsters'.$_SESSION['id']] == 0){
		die('3');
	}
	$id = intval($_GET['id']);
	if($id < 1){
		die('1');
	}
	$user	= $_pm['user']->getUserById($_SESSION['id']);
	if(empty($user['mbid'])){
		die('2');
	}
	$bb = $_pm['mysql'] -> getOneRecord("SELECT hp,srchp,addhp FROM userbb WHERE id = {$user['mbid']} AND uid = {$_SESSION['id']}");
	if(empty($bb)){
		die('1');
	}
	
	$props = $_pm['mysql'] -> getOneRecord("SELECT effect FROM props,userbag WHERE props.id = userbag.pid AND userbag.id = $id AND sums > 0 AND props.varyname = 18 AND uid = {$_SESSION['id']}");
	if(empty($props)){
		die('4');
	}
	
	if($props['effect'] != 'addhp:full'){
		die('5');
	}
	$_pm['mysql'] -> query("UPDATE userbag SET sums = sums - 1 WHERE id = {$id} AND uid = {$_SESSION['id']} AND sums >= 1");
	$result = mysql_affected_rows($_pm['mysql'] -> getConn());
	if($result != 1){
		die("4");
	}
	$arr = getzbAttrib($user['mbid']);
	if(empty($arr['hp'])){
		$arr['hp'] = 0;
	}
	$sql = "UPDATE userbb SET hp = {$bb['srchp']},addhp = {$arr['hp']} WHERE id = {$user['mbid']}";
	//echo $sql;
	$_pm['mysql'] -> query($sql);
	die('100');
}
?>