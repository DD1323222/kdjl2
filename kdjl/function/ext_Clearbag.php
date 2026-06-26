<?php
/**
*@清理玩家包裹中的所有收集品。
*/

set_time_limit(0);
require_once('../config/config.game.php');
//secStart($_pm['mem']);

$all = $_pm['mysql']->getRecords("SELECT sum(p.sell) as cnt,uid
						  FROM userbag as b,props as p
						 WHERE p.id = b.pid and (b.sums>0) and p.varyname=4
						 group by b.uid
						 order by cnt desc
					  ");

foreach ($all as $k => $rs)
{
	// 删除所有收集物品。
	if ($rs['cnt']<1) continue;
	$_pm['mysql']->query("DELETE   FROM userbag USING userbag,props 
						   WHERE userbag.pid=props.id and props.varyname=4 and userbag.uid={$rs['uid']}");
	
	// 更新用户的金币。
	$_pm['mysql']->query("UPDATE player SET money=money+{$rs['cnt']} WHERE id={$rs['uid']}");
}
echo 'done';
?>