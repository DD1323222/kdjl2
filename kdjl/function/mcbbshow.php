<?php
//避免出现乱码
require_once "../config/config.game.php";
$bid = intval($_REQUEST['id']);
if($bid < 1)
{
	die("");
}
$arr = $_pm['mysql'] -> getOneRecord("SELECT level,effectimg,name,wx,srchp,srcmp,ac,mc,hits,miss,speed,czl,zb FROM userbb WHERE id = $bid");//../images/ui/petbg.jpg

$zbArr = explode(',',$arr['zb']);
$zbStr = '';

for($i=0;$i<count($zbArr);$i++){
    $zbUserbagIdArr = explode(':',$zbArr[$i]);
    $zbUserbagId = $zbUserbagIdArr[1];
    $zbUserbagArr = $_pm['mysql'] -> getOneRecord("SELECT pid FROM userbag WHERE id = $zbUserbagId");
    $zbPropsArr = $_pm['mysql'] -> getOneRecord("SELECT name FROM props WHERE id = '".$zbUserbagArr['pid']."'");
    $zbStr = $zbStr.$zbPropsArr['name'].'，';
}

?>
<div style="z-index:10000; width:40px; height:20px; position:absolute; left:269px; font-size:12px; text-align:center; padding-top:5px; padding-right:5px"><span onclick="mcbbdisplay();" style="cursor:pointer"><font color="#FF0000">关闭</font></span></div>
<div style=" clear:both;width:300px;height:230; background-image:url(../images/ui/petbg.jpg) ; background-repeat:no-repeat;position:absolute; z-index:9999">
<div style=" clear:both;width:300px;height:230; background-image:url(../images/ui/petbg.jpg) ; background-repeat:no-repeat;position:absolute; z-index:9999">
    <div style="width:177px;height:230px;float:left;"><img src="../images/bb/<?=$arr['effectimg']?>" width="177px" height="230px" /></div>
    <div style="width:123px;height:230px;float:left;position:relative">
         <div style="position:absolute; text-align:center;top:16px;left:9px;width:99px;height:24px; font-size:12px; color:#FFFFFF;font-family:"微软雅黑","黑体",Arial,Vendana;color:#ffffff;"><?=$arr['name']?></div>
         <div style="font-size:12px;line-height:20px;position:absolute;top:40px;padding:2px;left:5px;height:180px;width:110px;overflow:scroll;">
         五行：<?=$_pets['wx'][$arr['wx']]?><br />
		 生命：<?=$arr['srchp']?><br />
		 魔法：<?=$arr['srcmp']?><br />
		 攻击：<?=$arr['ac']?><br />
		 防御：<?=$arr['mc']?><br />
		 命中：<?=$arr['hits']?><br />
		 闪避：<?=$arr['miss']?><br />
		 成长：<?=$arr['czl']?><br />
		 等级：<?=$arr['level']?><br />
		 装备：<?=$zbStr?>
         </div>
    </div>
</div>
