<?php
/**
 * 显示数据库搜索宠物技能倍率
*/
require_once('../../config/config.game.php');

$bid = (isset($_REQUEST['bid']) && !is_array($_REQUEST['bid'])) ? intval($_REQUEST['bid']) : 0;
$sid = (isset($_REQUEST['sid']) && !is_array($_REQUEST['sid'])) ? intval($_REQUEST['sid']) : 0;
if($bid < 1 || $sid < 1){
    echo '技能倍率：参数错误';
    exit;
}

$sql = "select * from skill where bid='".$bid."' and sid='".$sid."'";
$res = $_pm['mysql']->getRecords($sql);


$skillRate = 100;

if(is_array($res) && isset($res[0]) && is_array($res[0])){
    $skillInfoStr = isset($res[0]["plus"]) ? strval($res[0]["plus"]) : '';
    if(substr($skillInfoStr,0,2) == "hp"){
        $rate = substr($skillInfoStr,3);
        $rate = intval(str_replace('%','',$rate));
        $skillRate = $skillRate + $rate;
        $skillRate = $skillRate."%";
    }else{
        $skillRate = '技能配置错误';
    }
}else {
    $skillRate = '未找到技能数据';
}
echo '技能倍率：'.$skillRate.'';
?>
