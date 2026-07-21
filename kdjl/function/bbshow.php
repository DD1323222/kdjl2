<?php
//避免出现乱码
require_once "../config/config.game.php";
function bbshowHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function bbshowImage($value)
{
	$value = basename((string)$value);
	return preg_match('/^[A-Za-z0-9_.-]+$/', $value) ? $value : '';
}

$requestId = (isset($_REQUEST['id']) && !is_array($_REQUEST['id'])) ? $_REQUEST['id'] : '';
$requestBbid = (isset($_REQUEST['bbid']) && !is_array($_REQUEST['bbid'])) ? $_REQUEST['bbid'] : '';

$bid = 0;
if($requestId !== '')
{
	$bid = intval($requestId);
}
if($requestBbid !== '')
{
	$bid = intval($requestBbid);
}
if($bid < 1)
{
	die("");
}
$arr = $_pm['mysql'] -> getOneRecord("SELECT level,effectimg,name,wx,srchp,srcmp,ac,mc,hits,miss,speed,czl FROM userbb WHERE id = $bid");//../images/ui/petbg.jpg
if(!is_array($arr))
{
	die("");
}
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
	'czl' => 0
);
foreach($defaults as $key => $value)
{
	if(!isset($arr[$key]) || $arr[$key] === '')
	{
		$arr[$key] = $value;
	}
}
$wxName = isset($_pets['wx'][$arr['wx']]) ? $_pets['wx'][$arr['wx']] : $arr['wx'];
$effectImg = bbshowImage($arr['effectimg']);
if($requestId !== '')
{
?>
<div style="z-index:10000; width:40px; height:20px; position:absolute; left:260px; font-size:12px; padding-top:5px; padding-right:5px"><span onclick="UnTipbb();" style="cursor:pointer"><font color="#FF0000">关闭</font></span></div>
<?php
}
?>
<div style=" clear:both;width:300px;height:230px; background-image:url(../images/ui/petbg.jpg) ; background-repeat:no-repeat;position:absolute; z-index:9999">
    <div style="width:177px;height:230px;float:left;"><img src="../images/bb/<?php echo $effectImg; ?>" width="177px" height="230px" /></div>
    <div style="width:123px;height:230px;float:left;position:relative">
         <div style="position:absolute; text-align:center;top:16px;left:9px;width:99px;height:24px; font-size:12px; color:#FFFFFF;font-family:微软雅黑,黑体,Arial,Verdana;color:#ffffff;"><?php echo bbshowHtml($arr['name']); ?></div>
         <div style="font-size:12px;line-height:20px;position:absolute;top:40px;padding:2px;left:5px;height:180px;width:110px;overflow:hidden;">
         五行：<?php echo bbshowHtml($wxName); ?><br />
		 生命：<?php echo intval($arr['srchp']); ?><br />
		 魔法：<?php echo intval($arr['srcmp']); ?><br />
		 攻击：<?php echo intval($arr['ac']); ?><br />
		 防御：<?php echo intval($arr['mc']); ?><br />
		 命中：<?php echo intval($arr['hits']); ?><br />
		 闪避：<?php echo intval($arr['miss']); ?><br />
		 成长：<?php echo bbshowHtml($arr['czl']); ?><br />
		 等级：<?php echo intval($arr['level']); ?>
         </div>
    </div>
</div>
