<?php
if(PHP_SAPI !== 'cli')
{
	header('HTTP/1.1 404 Not Found');
	exit;
}

require_once(dirname(__FILE__).'/config/config.game.php');
if(!defined('CLIENT_MULTI_RESULTS')) define('CLIENT_MULTI_RESULTS', 131072);

$lock = $_pm['mysql']->getOneRecord("SELECT GET_LOCK('kdjl_clean_dead_user',0) AS locked");
if(!is_array($lock) || intval($lock['locked']) !== 1)
{
	fwrite(STDERR, "cleanDeadUser is already running.\n");
	exit(1);
}

$dbName = isset($_mysql['db']) ? $_mysql['db'] : '';
$dbNameSql = $_pm['mysql']->escape($dbName);
$required = array('clear_dead_user', 'do_clear_user');
$rows = $_pm['mysql']->getRecords("SELECT ROUTINE_NAME
									 FROM INFORMATION_SCHEMA.ROUTINES
									WHERE ROUTINE_SCHEMA='{$dbNameSql}'
									  AND ROUTINE_TYPE='PROCEDURE'
									  AND ROUTINE_NAME IN ('clear_dead_user','do_clear_user')");
$found = array();
if(is_array($rows))
{
	foreach($rows as $row)
	{
		if(is_array($row) && isset($row['ROUTINE_NAME'])) $found[$row['ROUTINE_NAME']] = true;
	}
}
$missing = array();
foreach($required as $procedure)
{
	if(!isset($found[$procedure])) $missing[] = $procedure;
}
if(!empty($missing))
{
	$_pm['mysql']->getOneRecord("SELECT RELEASE_LOCK('kdjl_clean_dead_user') AS released");
	fwrite(STDERR, 'Skipped: missing stored procedures: '.implode(', ', $missing).".\n");
	exit(2);
}

function kdjlRunDeadUserProcedure($sql)
{
	global $_mysql;
	$conn = @mysql_connect($_mysql['host'], $_mysql['user'], $_mysql['pass'], true, CLIENT_MULTI_RESULTS);
	if(!$conn || !@mysql_select_db($_mysql['db'], $conn))
	{
		if($conn) mysql_close($conn);
		return false;
	}
	if(!@mysql_query('SET max_sp_recursion_depth=4', $conn))
	{
		mysql_close($conn);
		return false;
	}
	$result = @mysql_query($sql, $conn);
	if(is_resource($result)) mysql_free_result($result);
	$ok = $result !== false;
	mysql_close($conn);
	return $ok;
}

$ok = kdjlRunDeadUserProcedure('CALL clear_dead_user(3)') && kdjlRunDeadUserProcedure('CALL do_clear_user()');
$_pm['mysql']->getOneRecord("SELECT RELEASE_LOCK('kdjl_clean_dead_user') AS released");
if(!$ok)
{
	fwrite(STDERR, "Dead-user cleanup failed; inspect the stored procedures and MySQL log.\n");
	exit(3);
}
echo "Dead-user cleanup completed.\n";
?>
