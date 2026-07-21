<?php
/**
 * 取得礼包类物品
*/

require_once('../config/config.game.php');
secStart($_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');

del_bag_expire();
$sql='SELECT DISTINCT p.name,p.id,p.stime
        FROM userbag AS b
        INNER JOIN props AS p ON p.id=b.pid
       WHERE b.uid='.$uid.'
         AND b.sums>0
         AND b.zbing=0
         AND (b.cantrade IS NULL OR b.cantrade<>3)
         AND p.varyname=22
       ORDER BY p.stime,p.id';
$rows=$_pm['mysql']->getRecords($sql);
if(!is_array($rows)) $rows = array();
$format = (isset($_GET['format']) && !is_array($_GET['format'])) ? $_GET['format'] : '';
$con='';
foreach($rows as $row)
{
	if(!is_array($row) || !isset($row['id']) || !isset($row['name'])) continue;
	$id = intval($row['id']);
	if($id < 1) continue;
	if($format === 'options')
	{
		echo '<option value="'.$id.'">'.htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8').'</option>';
		continue;
	}
	echo $con.$id.'|'.$row['name'];
	$con='#|#';
}
?>
