<?php
/**
 * 显示数据库搜索道具信息
*/
require_once('../config/config.game.php');

$name = $_GET["name"];
$site = $_GET["site"];//记录查询道具前的页数

$sql = "select * from props where name like '%".$name."%'";
$res = $_pm['mysql']->getRecords($sql);

if(is_array($res)){
    echo "<h1 style='text-align:center;'>查询结果</h1>";
    echo "<table border=1 style='margin:auto;text-align:center;'>";
    echo "<tr>
            <td style='width: 160px;font-weight:bold;'>id</td>
            <td style='width: 160px;font-weight:bold;'>名称</td>
            <td style='width: 160px;font-weight:bold;'>内容</td>
            <td style='width: 160px;font-weight:bold;'>效果</td>
            <td style='width: 160px;font-weight:bold;'>卖价卖价（金币）</td>
            <td style='width: 160px;font-weight:bold;'>买价卖价（金币）</td>
            <td style='width: 160px;font-weight:bold;'>元宝价格</td>
            <td style='width: 160px;font-weight:bold;'>水晶价格</td>
        </tr>";
    while(list($k,$v) = each($res))
    {
        echo "<tr>";
        while(list($k1,$v1) = each($v)){
            if($k1 == "id"){
                echo "<td style='width: 160px;'>".$v1."</td>";
            }
            if($k1 == "name"){
                echo "<td style='width: 160px;'>".$v1."</td>";
            }
            if($k1 == "usages"){
                echo "<td style='width: 160px;'>".$v1."</td>";
            }
            if($k1 == "effect"){
                $strEffectName = substr($v1,0,9);
                $strEffectContent = substr($v1,9);
                if($strEffectName == 'randitem:'){
                    $strContentArray = explode('|',$strEffectContent);
                    $strEffect = '';
                    while(list($kContentItem,$vContentItem) = each($strContentArray)){
                        $itemArray = explode(':',$vContentItem);
                        $sqlItem = "select name from props where id = '".$itemArray[0]."'";
                        $resItem = $_pm['mysql']->getRecords($sqlItem);
                        if(is_array($resItem)){
                            while(list($kItem,$vItem) = each($resItem)){
                                if($kItem == 'name'){
                                    $strItemIsInform = '</br>';
                                    if($itemArray[3] == 1){
                                        $strItemIsInform = '</br>是否公告：不公告';
                                    }else if($itemArray[3] == 2){
                                        $strItemIsInform = '</br>是否公告：公告';
                                    }else{
                                        $strItemIsInform = '</br>是否公告：'.$itemArray[3];
                                    }
                                    $strEffect = $strEffect.'<span style="font-weight:bold">道具名称：'.$vItem['name'].'（ID：'.$itemArray[0].'）</span></br>数量：'.$itemArray[1].'</br>获取概率：1/'.$itemArray[2].$strItemIsInform.'</br></br>';
                                }
                            }
                        }
                    }
                    echo "<td style='width: 360px;'>".$strEffect."</td>";
                }
                else{
                    echo "<td style='width: 160px;'>".$v1."</td>";
                }
            }
            if($k1 == "sell"){
                echo "<td style='width: 160px;'>".$v1."</td>";
            }
            if($k1 == "buy"){
                echo "<td style='width: 160px;'>".$v1."</td>";
            }
            if($k1 == "sj"){
                echo "<td style='width: 160px;'>".$v1."</td>";
            }
            if($k1 == "yb"){
                echo "<td style='width: 160px;'>".$v1."</td>";
            }
        }
        echo "</tr>";
    }
}else {
    echo "<h1 style='text-align:center;'>未查询到该道具！！！</h1>";
}
echo "</table>
    <div style='text-align:center;margin:auto;'>
        <a href='./selectProps.php?site=".$site."'>返回</a>
    </div>";

?>