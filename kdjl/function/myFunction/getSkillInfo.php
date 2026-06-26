<?php
/**
 * 显示数据库搜索宠物技能倍率
*/
require_once('../../config/config.game.php');

$bid = intval($_REQUEST['bid']);
$sid = intval($_REQUEST['sid']);

$sql = "select * from skill where bid='".$bid."' and sid='".$sid."'";
$res = $_pm['mysql']->getRecords($sql);


$skillRate = 100;

if(is_array($res)){
    $skillInfoStr = $res[0]["plus"];
    if(substr($skillInfoStr,0,2) == "hp"){
        $rate = substr($skillInfoStr,3);
        $rate = intval(str_replace('%','',$rate));
        $skillRate = $skillRate + $rate;
        $skillRate = $skillRate."%";
    }else{
        $skillRate = "error1";
    }
}else {
    $skillRate = "error2";
}
echo '技能倍率：'.$skillRate.'';
?>