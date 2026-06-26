<?php
/**
 * 显示数据库道具信息
*/
require_once('../config/config.game.php');

$site = $_GET["site"];

if($site>0){
    $sql = "select * from props LIMIT ".$site.",10";
}else {
    $sql = "select * from props LIMIT 0,10";
}
//$res = $_pm['mysql']->getRecords($sql);

echo "<h1 style='text-align:center;'>道具表</h1>";
//echo "<table border=1 style='margin:auto;text-align:center;'>";
/**echo "<tr>
        <td style='width: 160px;font-weight:bold;'>id</td>
        <td style='width: 160px;font-weight:bold;'>名称</td>
        <td style='width: 160px;font-weight:bold;'>内容</td>
        <td style='width: 160px;font-weight:bold;'>内容</td>
        <td style='width: 160px;font-weight:bold;'>卖价（金币）</td>
        <td style='width: 160px;font-weight:bold;'>买价（金币）</td>
        <td style='width: 160px;font-weight:bold;'>元宝价格</td>
        <td style='width: 160px;font-weight:bold;'>水晶价格</td>
    </tr>";*/
while(list($k,$v) = each($res))
{
    /*echo "<tr>
        <td style='width: 160px;'>".<?php $row['id'] ?>."</td>
        <td style='width: 240px;'>".<?php $row['name'] ?>."</td>
    </tr>";*/
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
echo "</table>";
$upSite = $site-10;
$downSite = $site+10;
/**echo "<div style='text-align:center;margin:auto;'>
        <a href='./selectProps.php?site=".$upSite."'>上一页</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <a href='./selectProps.php?site=".$downSite."'>下一页</a>
        <input id='selectName' type='text' style='margin-left:20px;' placeholder='请输入要查询的道具名称'/>
        <input type='button' style='margin-left:20px;' value='查询' onclick='selectProp(".$site.")'/>
    </div>";
    </br>
    <div style='text-align:center;margin:auto;'>
        <a href='./adminIndex.php?'>返回</a>
    </div>*/
echo "<div style='text-align:center;margin:auto;'>
        <input id='selectName' type='text' style='margin-left:20px;' placeholder='请输入要查询的道具名称'/>
        <input type='button' style='margin-left:20px;' value='查询' onclick='selectProp(".$site.")'/>
    </div>
    </br>
    <div style='text-align:center;margin:auto;'>
        <a href='./adminIndex.php?'>返回</a>
    </div>";

?>

<script>
    async function selectProp(site){
        var name = document.getElementById('selectName').value;
        if(name!=""){
            window.location.href = "./selectPropsQuery.php?name="+name+"&site="+site;
        }
    }
</script>