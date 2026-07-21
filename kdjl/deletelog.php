<?php
if(PHP_SAPI !== 'cli')
{
	header('HTTP/1.1 404 Not Found');
	exit;
}

require_once(dirname(__FILE__).'/config/config.game.php');
$days = isset($argv[1]) ? intval($argv[1]) : 15;
if($days < 1 || $days > 365)
{
	fwrite(STDERR, "Usage: php deletelog.php [retention-days: 1..365]\n");
	exit(1);
}

$lock = $_pm['mysql']->getOneRecord("SELECT GET_LOCK('kdjl_delete_gamelog',0) AS locked");
if(!is_array($lock) || intval($lock['locked']) !== 1)
{
	fwrite(STDERR, "deletelog is already running.\n");
	exit(2);
}

$cutoff = time() - $days * 86400;
$deleted = 0;
$chunkSize = 5000;
$completed = false;
for($batch = 0; $batch < 10000; $batch++)
{
	// vary=241 is the persistent marker for the once-only returning-player reward.
	if(!$_pm['mysql']->query("DELETE FROM gamelog WHERE ptime < {$cutoff} AND (vary IS NULL OR vary <> 241) ORDER BY ptime LIMIT {$chunkSize}"))
	{
		$_pm['mysql']->getOneRecord("SELECT RELEASE_LOCK('kdjl_delete_gamelog') AS released");
		fwrite(STDERR, "Log cleanup failed.\n");
		exit(3);
	}
	$affected = mysql_affected_rows($_pm['mysql']->getConn());
	if($affected < 0) $affected = 0;
	$deleted += $affected;
	if($affected < $chunkSize)
	{
		$completed = true;
		break;
	}
}
$_pm['mysql']->getOneRecord("SELECT RELEASE_LOCK('kdjl_delete_gamelog') AS released");
if(!$completed)
{
	fwrite(STDERR, 'Stopped after deleting '.$deleted." rows; rerun the command to continue.\n");
	exit(4);
}
echo 'Deleted '.$deleted.' gamelog rows older than '.$days." days.\n";
?>
