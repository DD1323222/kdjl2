<?php
/**
 * 显示进化表信息
*/
require_once('../config/config.game.php');

$site = $_GET["site"];
$pageItemNum = 10;
$pageNum = intval(intval($site)/$pageItemNum)+1;

if($site>0){
    $sql = "select * from merge LIMIT ".$site.",10";
}else {
    $sql = "select * from merge LIMIT 0,10";
}
//$res = $_pm['mysql']->getRecords($sql);

echo "<h1 style='text-align:center;'>合成表</h1>";
/**echo "<table border=1 style='margin:auto;text-align:center;'>";
echo "<tr>
        <td style='width: 160px;font-weight:bold;'>合成表id</td>
        <td style='width: 160px;font-weight:bold;'>主宠</td>
        <td style='width: 160px;font-weight:bold;'>副宠</td>
        <td style='width: 160px;font-weight:bold;'>获得宠物（大概率）</td>
        <td style='width: 160px;font-weight:bold;'>获得宠物（小概率）</td>
        <td style='width: 160px;font-weight:bold;'>合成条件</td>
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
echo "</table>";
$upSite = $site-10;
$downSite = $site+10;
/**echo "</br><div style='text-align:center;margin:auto;'>
        <a href='./selectMerge.php?site=".$upSite."'>上一页</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <span>第".$pageNum."页<span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <a href='./selectMerge.php?site=".$downSite."'>下一页</a>
    </div></br>";*/
echo "<div style='text-align:center;margin:auto;'>
        <input id='aid' type='text' style='margin-left:20px;width:230px;' placeholder='请输入要查询的主宠物ID'/>
        <input id='bid' type='text' style='margin-left:20px;width:230px;' placeholder='请输入要查询的副宠物ID'/>
        <input type='button' style='margin-left:20px;' value='查询' onclick='selectABid(".$site.")'/>
    </div></br>";
echo "<div style='text-align:center;margin:auto;'>
        <input id='maid' type='text' style='margin-left:20px;width:230px;' placeholder='请输入要查询的获得宠物ID（大概率）ID'/>
        <input id='mbid' type='text' style='margin-left:20px;width:230px;' placeholder='请输入要查询的获得宠物ID（小概率）ID'/>
        <input type='button' style='margin-left:20px;' value='查询' onclick='selectMABid(".$site.")'/>
    </div>
    </br>
    <div style='text-align:center;margin:auto;'>
        <a href='./adminIndex.php?'>返回</a>
    </div>";

?>

<script>
    async function selectABid(site){
        var aid = '';
        var bid = '';
        var maid = '';
        var mbid = '';
        aid = document.getElementById('aid').value;
        bid = document.getElementById('bid').value;
        window.location.href = "./selectMergeQuery.php?aid="+aid+"&bid="+bid+"&maid="+maid+"&mbid="+mbid+"&site="+site;
    }
    
    async function selectMABid(site){
        var aid = '';
        var bid = '';
        var maid = '';
        var mbid = '';
        maid = document.getElementById('maid').value;
        mbid = document.getElementById('mbid').value;
        window.location.href = "./selectMergeQuery.php?aid="+aid+"&bid="+bid+"&maid="+maid+"&mbid="+mbid+"&site="+site;
    }
</script>