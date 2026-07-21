<?php
header('Content-Type:text/html;charset=utf-8');
require_once('../config/config.game.php');
secStart($_pm['mem']);

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid <= 0){
	die('2');
}
$id = (isset($_GET['id']) && !is_array($_GET['id'])) ? intval($_GET['id']) : 0;
if($id < 1){
	die('2');
}

if(!$_pm['mysql'] -> query('START TRANSACTION')){
	die('2');
}

$pcheck = $_pm['mysql'] -> getOneRecord("SELECT cantrade FROM userbag WHERE id = $id AND uid = {$uid} FOR UPDATE");
if(!is_array($pcheck)){
	$_pm['mysql'] -> query('ROLLBACK');
	die('2');
}
if($pcheck['cantrade'] == 3){
	$_pm['mysql'] -> query('ROLLBACK');
	die('4');
}

if(!$_pm['mysql'] -> query("UPDATE userbag
                           SET sums = sums - 1
                         WHERE uid = {$uid}
                           AND pid = 2355
                           AND sums >= 1
                           AND zbing = 0
                           AND (cantrade IS NULL OR cantrade <> 3)
                         ORDER BY id LIMIT 1") ||
	mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){
	$_pm['mysql'] -> query('ROLLBACK');
	die('1');
}
if(!$_pm['mysql'] -> query("DELETE FROM userbag WHERE uid = {$uid} AND pid = 2355 AND sums <= 0 AND bsum <= 0 AND psum <= 0 AND pyb = 0 AND zbing = 0 AND (cantrade IS NULL OR cantrade <> 3)")){
	$_pm['mysql'] -> query('ROLLBACK');
	die('2');
}

if(!$_pm['mysql'] -> query("UPDATE userbag SET cantrade = 3 WHERE id = $id AND uid = {$uid} AND (cantrade IS NULL OR cantrade <> 3)") ||
	mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){
	$_pm['mysql'] -> query('ROLLBACK');
	die('2');
}
if(!$_pm['mysql'] -> query('COMMIT')){
	$_pm['mysql'] -> query('ROLLBACK');
	die('2');
}
$_pm['mem']->del(MEM_USERBAG_KEY);
die('3');
?>
