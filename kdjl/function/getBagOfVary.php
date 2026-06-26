<?php
/**
 * 取得礼包类物品
*/

require_once('../config/config.game.php');
secStart($_pm['mem']);
$sql='select name,id from props where varyname="22" order by stime';
$rows=$_pm['mysql']->getRecords($sql);
echo mysql_error();
$con='';
foreach($rows as $row)
{
	echo $con.$row['id'].'|'.$row['name'].'</option>';
	$con='#|#';
}
?>