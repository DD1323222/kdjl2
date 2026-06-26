<?php
/**
 * 显示数据库搜索道具信息
*/
require_once('../config/config.game.php');

$name = $_GET["name"];

$sql = "select * from bb where name like '%".$name."%'";
$res = $_pm['mysql']->getRecords($sql);

if(is_array($res)){
    echo "<h1 style='text-align:center;'>查询结果</h1>";
    echo "<table border=1 style='margin:auto;text-align:center;'>";
    echo "<tr>
            <td style='width: 160px;font-weight:bold;'>宠物ID</td>
            <td style='width: 160px;font-weight:bold;'>宠物名称</td>
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
        }
        echo "</tr>";
    }
}else {
    echo "<h1 style='text-align:center;'>未查询到该宠物信息，请核对后再次查询！！！</h1>";
}
echo "</table>
    <div style='text-align:center;margin:auto;'>
        <a href='./selectBB.php?site=".$site."'>返回</a>
    </div>";

?>