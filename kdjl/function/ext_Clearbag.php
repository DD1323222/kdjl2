<?php
set_time_limit(0);
require_once('../config/config.game.php');

if(!kdjlCurrentUserIsAdmin())
{
	die();
}

$all = $_pm['mysql']->getRecords("SELECT COALESCE(SUM(COALESCE(p.sell,0) * b.sums),0) AS cnt,b.uid
						  FROM userbag AS b,props AS p
						 WHERE p.id=b.pid AND b.sums>0 AND p.varyname=4
						   AND (b.cantrade IS NULL OR b.cantrade<>3)
						 GROUP BY b.uid
						 ORDER BY cnt DESC
					  ");
if(!is_array($all)) $all = array();

foreach($all as $rs)
{
	$uid = intval($rs['uid']);
	$money = intval($rs['cnt']);
	if($uid < 1 || $money < 1) continue;

	$_pm['mysql']->query("DELETE FROM userbag USING userbag,props
						   WHERE userbag.pid=props.id AND props.varyname=4 AND userbag.uid={$uid}
						     AND (userbag.cantrade IS NULL OR userbag.cantrade<>3)");

	$_pm['mysql']->query("UPDATE player SET money=LEAST(COALESCE(money,0)+{$money},1000000000) WHERE id={$uid}");
}

echo 'done';
?>
