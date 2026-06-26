<?php
/**
 * 显示数据库搜索合成表信息
*/
require_once('../config/config.game.php');

$aid = $_GET["aid"];
$bid = $_GET["bid"];
$maid = $_GET["maid"];
$mbid = $_GET["mbid"];
$site = $_GET["site"];//记录查询合成表前的页数

if($aid != '' && $bid !=''){
    $sql = "select * from merge where aid='".$aid."' and bid='".$bid."'";
}else if($aid != ''){
    $sql = "select * from merge where aid='".$aid."'";
}else if($bid != ''){
    $sql = "select * from merge where bid='".$bid."'";
}else if($maid != '' && $mbid !=''){
    $sql = "select * from merge where maid='".$maid."' and mbid='".$mbid."'";
}else if($maid != ''){
    $sql = "select * from merge where maid='".$maid."'";
}else if($mbid !=''){
    $sql = "select * from merge where mbid='".$mbid."'";
}
$res = $_pm['mysql']->getRecords($sql);

if(is_array($res)){
    echo "<h1 style='text-align:center;'>查询结果</h1>";
    echo "<table border=1 style='margin:auto;text-align:center;'>";
    echo "<tr>
            <td style='width: 160px;font-weight:bold;'>合成表id</td>
            <td style='width: 160px;font-weight:bold;'>主宠</td>
            <td style='width: 160px;font-weight:bold;'>副宠</td>
            <td style='width: 160px;font-weight:bold;'>获得宠物（大概率）</td>
            <td style='width: 160px;font-weight:bold;'>获得宠物（小概率）</td>
            <td style='width: 160px;font-weight:bold;'>合成条件</td>
        </tr>";
    while(list($k,$v) = each($res))
    {
        echo "<tr>";
        while(list($k1,$v1) = each($v)){
            if($k1 == "id"){
                echo "<td style='width: 160px;'>".$v1."</td>";
            }
            if($k1 == "aid"){
                $sqlBBName = "select name from bb where id =  '".$v1."'";
                $resBBName = $_pm['mysql']->getRecords($sqlBBName);
                echo "<td style='width: 160px;'>".$resBBName[0]['name']."（ID：".$v1."）</td>";
            }
            if($k1 == "bid"){
                $sqlBBName = "select name from bb where id =  '".$v1."'";
                $resBBName = $_pm['mysql']->getRecords($sqlBBName);
                echo "<td style='width: 160px;'>".$resBBName[0]['name']."（ID：".$v1."）</td>";
            }
            if($k1 == "maid"){
                $sqlBBName = "select name from bb where id =  '".$v1."'";
                $resBBName = $_pm['mysql']->getRecords($sqlBBName);
                echo "<td style='width: 160px;'>".$resBBName[0]['name']."（ID：".$v1."）</td>";
            }
            if($k1 == "mbid"){
                $sqlBBName = "select name from bb where id =  '".$v1."'";
                $resBBName = $_pm['mysql']->getRecords($sqlBBName);
                echo "<td style='width: 160px;'>".$resBBName[0]['name']."（ID：".$v1."）</td>";
            }
            if($k1 == "limits"){
                echo "<td style='width: 160px;'>".$v1."</td>";
            }
        }
        echo "</tr>";
    }
}else {
    echo "<h1 style='text-align:center;'>未查询到相关信息，请输入正确的宠物ID！！！</h1>";
}
echo "</table>
    <div style='text-align:center;margin:auto;'>
        <a href='./selectMerge.php?site=".$site."'>返回</a>
    </div>";

?>