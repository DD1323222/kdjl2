<?php
/**
 * put the props in bag to depot
 *
 * @date:2009-03-24
 * @author:Zheng.Ping
 **/


require_once('../config/config.game.php');

secStart($_pm['mem']);

/**
 * 仓库存在的道具，从背包全部放入仓库
 */
$sql = "SELECT * FROM userbag WHERE uid = '".$_SESSION['id']."'";
$res = $_pm['mysql']->getRecords($sql);

$isTrue = false;
$inNum = 0;

while(list($resK,$resV) = each($res)){
    $usSums = 0;
    $usBsum = 0;
    while(list($kUB,$vUB) = each($resV)){
        if($kUB == 'id'){
            $usId = $vUB;
        }
        if($kUB == 'sums'){
            $usSums = $vUB;
        }
        if($kUB == 'bsum'){
            $usBsum = $vUB;
        }
        if($usBsum>0 && $usSums>0){
            $num = $usSums + $usBsum;
            $isTrue = $_pm['mysql']->query("UPDATE userbag
            			   SET sums=0,bsum = '".$num."'
            			 WHERE id='".$usId."' and bsum>0 and vary!=2 and uid='".$_SESSION['id']."'
            		  ");
            $inNum = $inNum + 1;
        }
    }
}

if($inNum<=0){
    exit("5");
}
if($isTrue){
    exit("0");
}else {
    exit("1");
}

$_pm['mem']->memClose();

?>
