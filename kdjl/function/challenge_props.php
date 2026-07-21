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
require_once('../sec/dblock_fun.php');
secStart($_pm['mem']);

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('1');
del_bag_expire();
$op = (isset($_GET['op']) && !is_array($_GET['op'])) ? $_GET['op'] : '';
if($op == 'propslist'){
	$arr = $_pm['mysql'] -> getRecords("SELECT userbag.id,name,sums
	                                       FROM userbag,props
	                                      WHERE uid = {$uid}
	                                        AND pid = props.id
	                                        AND props.varyname = 18
	                                        AND userbag.sums > 0
	                                        AND userbag.zbing = 0
	                                        AND (userbag.cantrade IS NULL OR userbag.cantrade <> 3)");
	if(empty($arr)){
		die('没有此类道具！');
	}
	$str = '';
	foreach($arr as $v){
		$str .= $v['name'].':'.$v['id'].',';
	}
	echo $str;
}else if($op == 'usedprops'){
	if(empty($_SESSION['multi_monsters'.$uid])){
		die('3');
	}
	$id = (isset($_GET['id']) && !is_array($_GET['id'])) ? intval($_GET['id']) : 0;
	if($id < 1){
		die('1');
	}
	$a = getLock($uid);
	if(!is_array($a)){
		realseLock();
		die("1");
	}
	$user = $_pm['mysql']->getOneRecord("SELECT mbid FROM player WHERE id = {$uid} FOR UPDATE");
	if(empty($user['mbid'])){
		$_pm['mysql'] -> query('ROLLBACK');
		realseLock();
		die('2');
	}
	$bb = $_pm['mysql'] -> getOneRecord("SELECT hp,srchp,addhp FROM userbb WHERE id = {$user['mbid']} AND uid = {$uid} FOR UPDATE");
	if(empty($bb)){
		$_pm['mysql'] -> query('ROLLBACK');
		realseLock();
		die('1');
	}

	$props = $_pm['mysql'] -> getOneRecord("SELECT effect
	                                          FROM props,userbag
	                                         WHERE props.id = userbag.pid
	                                           AND userbag.id = $id
	                                           AND userbag.sums > 0
	                                           AND userbag.zbing = 0
	                                           AND (userbag.cantrade IS NULL OR userbag.cantrade <> 3)
	                                           AND props.varyname = 18
	                                           AND uid = {$uid}
	                                           FOR UPDATE");
	if(empty($props)){
		$_pm['mysql'] -> query('ROLLBACK');
		realseLock();
		die('4');
	}

	if($props['effect'] != 'addhp:full'){
		$_pm['mysql'] -> query('ROLLBACK');
		realseLock();
		die('5');
	}
	$itemUsed = $_pm['mysql'] -> query("UPDATE userbag SET sums = sums - 1 WHERE id = {$id} AND uid = {$uid} AND sums >= 1 AND zbing = 0 AND (cantrade IS NULL OR cantrade <> 3)");
	$result = $itemUsed ? mysql_affected_rows($_pm['mysql'] -> getConn()) : 0;
	if($result != 1){
		$_pm['mysql'] -> query('ROLLBACK');
		realseLock();
		die("4");
	}
	if(!$_pm['mysql'] -> query("DELETE FROM userbag WHERE id = {$id} AND uid = {$uid} AND sums <= 0 AND bsum <= 0 AND psum <= 0 AND pyb = 0 AND zbing = 0 AND (cantrade IS NULL OR cantrade <> 3)")){
		$_pm['mysql'] -> query('ROLLBACK');
		realseLock();
		die("1");
	}
	$arr = getzbAttrib($user['mbid']);
	if(empty($arr['hp'])){
		$arr['hp'] = 0;
	}
	$sql = "UPDATE userbb SET hp = {$bb['srchp']},addhp = {$arr['hp']} WHERE id = {$user['mbid']} AND uid = {$uid}";
	//echo $sql;
	if(!$_pm['mysql'] -> query($sql) || mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){
		$_pm['mysql'] -> query('ROLLBACK');
		realseLock();
		die("1");
	}
	if(!$_pm['mysql'] -> query('COMMIT')){
		$_pm['mysql'] -> query('ROLLBACK');
		realseLock();
		die("1");
	}
	$_pm['mem']->del(MEM_USERBB_KEY);
	$_pm['mem']->del(MEM_USERBAG_KEY);
	realseLock();
	die('100');
}
?>
