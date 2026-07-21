<?php
/**
 * put the props in bag to depot
 *
 * @date:2009-03-24
 * @author:Zheng.Ping
 **/


require_once('../config/config.game.php');

secStart($_pm['mem']);
del_bag_expire();
require_once('../sec/dblock_fun.php');

function propsDepotAllSameDone($code, $locked, $rollback)
{
    global $_pm;
	$committed = false;
    if($locked)
    {
        if($rollback)
        {
            $_pm['mysql']->query('ROLLBACK');
        }
        else if(!$_pm['mysql']->query('COMMIT'))
        {
            $_pm['mysql']->query('ROLLBACK');
            $code = "1";
        }
		else
		{
			$committed = true;
		}
		if($committed) $_pm['mem']->del(MEM_USERBAG_KEY);
        realseLock();
    }
    $_pm['mem']->memClose();
    exit($code);
}

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1)
{
    $_pm['mem']->memClose();
    exit("1");
}

$lock = getLock($uid);
if(!is_array($lock))
{
    realseLock();
    $_pm['mem']->memClose();
    exit("1");
}

/**
 * 仓库存在的道具，从背包全部放入仓库
 */
$sql = "SELECT * FROM userbag WHERE uid = '".$uid."' FOR UPDATE";
$res = $_pm['mysql']->getRecords($sql);
if(!is_array($res)) $res = array();

$isTrue = false;
$allOk = true;
$inNum = 0;

foreach($res as $resV){
    if(!is_array($resV)) continue;
    $usId = isset($resV['id']) ? intval($resV['id']) : 0;
    $usSums = isset($resV['sums']) ? intval($resV['sums']) : 0;
    $usBsum = isset($resV['bsum']) ? intval($resV['bsum']) : 0;
    $usVary = isset($resV['vary']) ? intval($resV['vary']) : 0;
    $usZbing = isset($resV['zbing']) ? intval($resV['zbing']) : 0;
    $usCantrade = isset($resV['cantrade']) ? intval($resV['cantrade']) : 0;
    if($usId > 0 && $usBsum > 0 && $usSums > 0 && $usVary != 2 && $usZbing == 0 && $usCantrade != 3){
        $num = kdjlSafeNonNegativeSum($usSums, $usBsum);
        if($num === false)
        {
            $allOk = false;
            break;
        }
        $isTrue = $_pm['mysql']->query("UPDATE userbag
                       SET sums=0,bsum = '".$num."'
                     WHERE id='".$usId."' and bsum>0 and sums>0 and vary!=2 and zbing=0 and uid='".$uid."' and (cantrade IS NULL OR cantrade<>3)
                  ");
        if($isTrue && mysql_affected_rows($_pm['mysql']->getConn()) == 1)
        {
            $inNum = $inNum + 1;
        }
        else
        {
            $allOk = false;
            break;
        }
    }
}

if(!$allOk){
    propsDepotAllSameDone("1", true, true);
}
if($inNum<=0){
    propsDepotAllSameDone("5", true, false);
}
if($isTrue){
    propsDepotAllSameDone("0", true, false);
}else {
    propsDepotAllSameDone("1", true, true);
}

?>
