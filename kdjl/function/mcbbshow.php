<?php
//避免出现乱码
require_once "../config/config.game.php";
function mcbbshowHtml($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function mcbbshowImage($value)
{
    $value = basename((string)$value);
    return preg_match('/^[A-Za-z0-9_.-]+$/', $value) ? $value : '';
}

$bid = (isset($_REQUEST['id']) && !is_array($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0;
if($bid < 1)
{
	die("");
}
$arr = $_pm['mysql'] -> getOneRecord("SELECT uid,level,effectimg,name,wx,srchp,srcmp,ac,mc,hits,miss,speed,czl,zb FROM userbb WHERE id = $bid");//../images/ui/petbg.jpg
if(!is_array($arr)) die("");
$defaults = array(
    'level' => 0,
    'effectimg' => '',
    'name' => '',
    'wx' => 0,
    'srchp' => 0,
    'srcmp' => 0,
    'ac' => 0,
    'mc' => 0,
    'hits' => 0,
    'miss' => 0,
    'speed' => 0,
    'czl' => 0,
    'uid' => 0,
    'zb' => ''
);
foreach($defaults as $key => $value)
{
    if(!isset($arr[$key]) || $arr[$key] === '')
    {
        $arr[$key] = $value;
    }
}
$wxName = isset($_pets['wx'][$arr['wx']]) ? $_pets['wx'][$arr['wx']] : $arr['wx'];
$effectImg = mcbbshowImage($arr['effectimg']);

$zbArr = explode(',',isset($arr['zb']) ? $arr['zb'] : '');
$zbStr = '';
$equipmentIds = array();

for($i=0;$i<count($zbArr);$i++){
    $zbUserbagIdArr = explode(':',$zbArr[$i]);
    if(count($zbUserbagIdArr) < 2 || intval($zbUserbagIdArr[1]) < 1) continue;
	$equipmentIds[] = intval($zbUserbagIdArr[1]);
}
if(count($equipmentIds) > 0)
{
	$equipmentIds = array_values(array_unique($equipmentIds));
	$equipmentRows = $_pm['mysql']->getRecords(
		'SELECT ub.id,p.name FROM userbag ub INNER JOIN props p ON p.id=ub.pid'.
		' WHERE ub.uid='.intval($arr['uid']).' AND ub.zbpets='.$bid.' AND ub.sums>0'.
		' AND ub.id IN ('.implode(',', $equipmentIds).')'
	);
	$equipmentNames = array();
	if(is_array($equipmentRows))
	{
		foreach($equipmentRows as $equipmentRow)
		{
			$equipmentNames[intval($equipmentRow['id'])] = $equipmentRow['name'];
		}
	}
	foreach($equipmentIds as $equipmentId)
	{
		if(isset($equipmentNames[$equipmentId])) $zbStr .= $equipmentNames[$equipmentId].'，';
	}
}

?>
<div style="z-index:10000; width:40px; height:20px; position:absolute; left:269px; font-size:12px; text-align:center; padding-top:5px; padding-right:5px"><span onclick="mcbbdisplay();" style="cursor:pointer"><font color="#FF0000">关闭</font></span></div>
<div style=" clear:both;width:300px;height:230px; background-image:url(../images/ui/petbg.jpg) ; background-repeat:no-repeat;position:absolute; z-index:9999">
    <div style="width:177px;height:230px;float:left;"><img src="../images/bb/<?php echo $effectImg; ?>" width="177px" height="230px" /></div>
    <div style="width:123px;height:230px;float:left;position:relative">
         <div style="position:absolute; text-align:center;top:16px;left:9px;width:99px;height:24px; font-size:12px; color:#FFFFFF;font-family:微软雅黑,黑体,Arial,Verdana;color:#ffffff;"><?php echo mcbbshowHtml($arr['name']); ?></div>
         <div style="font-size:12px;line-height:20px;position:absolute;top:40px;padding:2px;left:5px;height:180px;width:110px;overflow:scroll;">
         五行：<?php echo mcbbshowHtml($wxName); ?><br />
		 生命：<?php echo intval($arr['srchp']); ?><br />
		 魔法：<?php echo intval($arr['srcmp']); ?><br />
		 攻击：<?php echo intval($arr['ac']); ?><br />
		 防御：<?php echo intval($arr['mc']); ?><br />
		 命中：<?php echo intval($arr['hits']); ?><br />
		 闪避：<?php echo intval($arr['miss']); ?><br />
		 成长：<?php echo mcbbshowHtml($arr['czl']); ?><br />
		 等级：<?php echo intval($arr['level']); ?><br />
		 装备：<?php echo mcbbshowHtml($zbStr); ?>
         </div>
    </div>
</div>
