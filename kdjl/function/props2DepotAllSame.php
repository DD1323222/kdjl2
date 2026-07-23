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

function propsDepotAllSameTradeState($row)
{
	return isset($row['cantrade']) ? intval($row['cantrade']) : 0;
}

function propsDepotAllSameTradeWhere($column, $tradeState)
{
	if(intval($tradeState) == 0)
	{
		return '('.$column.' IS NULL OR '.$column.'=0)';
	}
	return $column.'='.intval($tradeState);
}

function propsDepotAllSameStackKey($row)
{
	$key = array(
		isset($row['pid']) ? intval($row['pid']) : 0,
		propsDepotAllSameTradeState($row),
		isset($row['sell']) ? intval($row['sell']) : 0,
		isset($row['plus_tms_eft']) ? strval($row['plus_tms_eft']) : '',
		isset($row['F_item_hole_info']) ? strval($row['F_item_hole_info']) : '',
		isset($row['buycode']) ? strval($row['buycode']) : '',
		isset($row['zbpets']) ? strval($row['zbpets']) : ''
	);
	if(isset($row['props_expire']) && intval($row['props_expire']) > 0)
	{
		$key[] = isset($row['stime']) ? intval($row['stime']) : 0;
	}
	return serialize($key);
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
$sql = "SELECT b.*,p.vary AS props_vary,p.expire AS props_expire
		  FROM userbag AS b
		  LEFT JOIN props AS p ON p.id=b.pid
		 WHERE b.uid='".$uid."'
		 FOR UPDATE";
$res = $_pm['mysql']->getRecords($sql);
if(!is_array($res)) $res = array();

$isTrue = false;
$allOk = true;
$inNum = 0;
$depotRows = array();

/*
 * Most normal items are stackable (props.vary=1). Some award paths create a
 * second userbag row even when the same item already exists in the depot, so
 * remember compatible depot rows before moving bag quantities.
 */
foreach($res as $resV)
{
	if(!is_array($resV)) continue;
	$usId = isset($resV['id']) ? intval($resV['id']) : 0;
	$usBsum = isset($resV['bsum']) ? intval($resV['bsum']) : 0;
	$usVary = isset($resV['vary']) ? intval($resV['vary']) : 0;
	$propsVary = isset($resV['props_vary']) ? intval($resV['props_vary']) : 0;
	$usZbing = isset($resV['zbing']) ? intval($resV['zbing']) : 0;
	$usCantrade = propsDepotAllSameTradeState($resV);
	if($usId < 1 || $usBsum < 1 || $usVary != 1 || $propsVary != 1 ||
		$usZbing != 0 || $usCantrade == 3)
	{
		continue;
	}
	$key = propsDepotAllSameStackKey($resV);
	if(!isset($depotRows[$key]))
	{
		$depotRows[$key] = array('id' => $usId, 'bsum' => $usBsum);
	}
}

foreach($res as $resV){
    if(!is_array($resV)) continue;
    $usId = isset($resV['id']) ? intval($resV['id']) : 0;
    $usPid = isset($resV['pid']) ? intval($resV['pid']) : 0;
    $usSums = isset($resV['sums']) ? intval($resV['sums']) : 0;
    $usBsum = isset($resV['bsum']) ? intval($resV['bsum']) : 0;
    $usVary = isset($resV['vary']) ? intval($resV['vary']) : 0;
    $propsVary = isset($resV['props_vary']) ? intval($resV['props_vary']) : 0;
    $usZbing = isset($resV['zbing']) ? intval($resV['zbing']) : 0;
    $usCantrade = propsDepotAllSameTradeState($resV);
    if($usId < 1 || $usPid < 1 || $usSums < 1 || $usVary == 2 ||
        $usZbing != 0 || $usCantrade == 3)
    {
        continue;
    }

    $tradeWhere = propsDepotAllSameTradeWhere('cantrade', $usCantrade);
    if($usId > 0 && $usBsum > 0 && $usSums > 0 && $usVary != 2 && $usZbing == 0 && $usCantrade != 3){
        $num = kdjlSafeNonNegativeSum($usSums, $usBsum);
        if($num === false)
        {
            $allOk = false;
            break;
        }
        $isTrue = $_pm['mysql']->query("UPDATE userbag
                       SET sums=0,bsum = '".$num."'
                     WHERE id='".$usId."' and pid='".$usPid."' and bsum='".$usBsum."' and sums='".$usSums."'
                       and vary!=2 and zbing=0 and uid='".$uid."' and ".$tradeWhere."
                  ");
        if($isTrue && mysql_affected_rows($_pm['mysql']->getConn()) == 1)
        {
            $inNum = $inNum + 1;
            if($propsVary == 1)
            {
                $key = propsDepotAllSameStackKey($resV);
                if(isset($depotRows[$key]) && intval($depotRows[$key]['id']) == $usId)
                {
                    $depotRows[$key]['bsum'] = $num;
                }
            }
        }
        else
        {
            $allOk = false;
            break;
        }
        continue;
    }

    /*
     * A separate bag row may still match an existing depot stack. Only merge
     * stackable definitions with the same trade and item-instance metadata.
     */
    if($usVary != 1 || $propsVary != 1) continue;
    $key = propsDepotAllSameStackKey($resV);
    if(!isset($depotRows[$key])) continue;

    $targetId = intval($depotRows[$key]['id']);
    $targetBsum = intval($depotRows[$key]['bsum']);
    if($targetId < 1 || $targetId == $usId || $targetBsum < 1) continue;
    $newBsum = kdjlSafeNonNegativeSum($targetBsum, $usSums);
    if($newBsum === false)
    {
        $allOk = false;
        break;
    }

    $targetOk = $_pm['mysql']->query("UPDATE userbag
                                       SET bsum='".$newBsum."'
                                     WHERE id='".$targetId."' and uid='".$uid."' and pid='".$usPid."'
                                       and bsum='".$targetBsum."' and vary=1 and zbing=0 and ".$tradeWhere);
    if(!$targetOk || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
    {
        $allOk = false;
        break;
    }

    $sourceOk = $_pm['mysql']->query("UPDATE userbag
                                       SET sums=0
                                     WHERE id='".$usId."' and uid='".$uid."' and pid='".$usPid."'
                                       and sums='".$usSums."' and COALESCE(bsum,0)=0
                                       and vary=1 and zbing=0 and ".$tradeWhere);
    if(!$sourceOk || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
    {
        $allOk = false;
        break;
    }

    $cleanOk = $_pm['mysql']->query("DELETE FROM userbag
                                     WHERE id='".$usId."' and uid='".$uid."'
                                       and sums=0 and bsum=0 and psum=0 and pyb=0 and zbing=0");
    if(!$cleanOk)
    {
        $allOk = false;
        break;
    }
    $depotRows[$key]['bsum'] = $newBsum;
    $isTrue = true;
    $inNum = $inNum + 1;
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
