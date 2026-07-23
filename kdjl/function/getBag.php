<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.05.22
*@Usage: Shop main ui
*@Note: none

1为可交易
2为不可交易
3为不可交易不可丢弃
0为以道具表的交易属性为准
*/

require_once('../config/config.game.php');
secStart($_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid <= 0) die('您的包裹是空的！');
del_bag_expire();
$user		= $_pm['user']->getUserById($uid);
$userBag	= $_pm['user']->getUserBagById($uid);
if(!is_array($user)) die('您的包裹是空的！');
$_pm['mem']->memClose();
$bagtype = (isset($_REQUEST['bagtype']) && !is_array($_REQUEST['bagtype'])) ? preg_replace('/[^0-9,|]/', '', $_REQUEST['bagtype']) : '';
$style = (isset($_REQUEST['style']) && !is_array($_REQUEST['style'])) ? intval($_REQUEST['style']) : 0;
//$clean = $_REQUEST['clean'];
$clean = 1;
$array = array();
$bagoption = '';

function getBagHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function getBagJsSingle($value)
{
	$value = str_replace("\\", "\\\\", (string)$value);
	$value = str_replace("'", "\\'", $value);
	$value = str_replace(array("\r", "\n"), array("\\r", "\\n"), $value);
	return $value;
}

function normalizeBagRow($rs)
{
	global $_props;
	if(!is_array($rs)) return false;
	$defaults = array(
		'id' => 0,
		'sums' => 0,
		'zbing' => 0,
		'requires' => '',
		'varyname' => '',
		'effect' => '',
		'sell' => 0,
		'name' => ''
	);
	foreach($defaults as $key => $value)
	{
		if(!isset($rs[$key])) $rs[$key] = $value;
	}
	$rs['id'] = intval($rs['id']);
	$rs['sums'] = intval($rs['sums']);
	$rs['zbing'] = intval($rs['zbing']);
	$rs['varyname'] = intval($rs['varyname']);
	$rs['sell'] = intval($rs['sell']);
	if(!isset($_props['varyname'][$rs['varyname']])) $_props['varyname'][$rs['varyname']] = '';
	return $rs;
}

if($clean == 1)
{
	if(is_array($userBag))
	{
		foreach($userBag as $key => $value)
		{
			$value = normalizeBagRow($value);
			if($value === false) continue;
			$array[$value['varyname']][] = $value;
		}
	}
}

$bagTypeValues = array('', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10|11', '12', '13', '14', '15', '16');
$bagTypeLabels = array('全部道具', '辅助道具', '增益道具', '捕捉道具', '收集道具', '技能书', '卡片道具', '进化道具', '合体道具', '装备道具', '精练道具', '宝箱道具', '特殊道具', '功能道具', '宠物卵', '合成道具');
if(!in_array($bagtype, $bagTypeValues, true)) $bagtype = '';
foreach($bagTypeValues as $ks => $value)
{
	$selected = $bagtype === $value ? ' selected="selected"' : '';
	$bagoption .= '<option value="'.getBagHtml($value).'"'.$selected.'>'.getBagHtml($bagTypeLabels[$ks]).'</option>';
}
/**
* Delete userbag for novalid.
*/
$_pm['mysql']->query("DELETE FROM userbag
				       WHERE sums=0 and bsum=0 and psum=0 and pyb=0 and zbing=0 and (cantrade IS NULL OR cantrade<>3) and uid={$uid}
					");

$bagUsedCellCT=0;
$bagRenderCount=0;
// Get userbag
$bag = $style == 1 ? '<div class="pack_cont"><ul class="list l1 clearfix">' : '';
if (!is_array($userBag)) $bag .= '还没有任何商品!';
else if($style == 3){
	if (!is_array($userBag)) $bag = '还没有任何物品!';
	else
	{
		foreach ($userBag as $k => $rs)
		{
			$rs = normalizeBagRow($rs);
			if($rs === false) continue;
			if ($rs['sums'] < 1 ||
				$rs['id']==0 ||
				$rs['zbing'] == 1) continue;

			if (strlen($rs['requires'])>2)
			{
				$t = explode(',',
						   str_replace(array('lv','wx'), array('等级','五行'), $rs['requires'])
						  );
				$wx = isset($t[1]) ? str_replace($_props['wxs'], $_props['wxd'], $t[1]) : '无';
			}
			else $t[0]= $wx= '无';
			$nameHtml = getBagHtml($rs['name']);
			$nameJs = getBagHtml(getBagJsSingle($rs['name']));
			$bag .= '
 <table class="tit01">
<tr>
			<td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
						<td width="110px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" onclick="sel(this);copyWord(\''.$nameJs.'\');bid='.$rs['id'].';price='.$rs['sell'].';">'.$nameHtml.'</td>
						<td width="60px" style="text-align:left">' . $rs['sell'] . '</td>
						<td style="text-align:left" id="s'.$rs['id'].'" >' . $rs['sums'] .'</td>
					 </tr>
					 </table>';
		}
	}
	if($bag === '') $bag = '您的包裹是空的！';
	exit($bag);
}else{
	if($clean == 1)
	{
		foreach ($array as $k_1 => $rs_1)
		{
			foreach($rs_1 as $k_2 => $rs)
			{
				$rs = normalizeBagRow($rs);
				if($rs === false) continue;
				if ($rs['sums'] < 1 || $rs['zbing']==1) continue;
				if ($rs['sums'] < 1 || $rs['id']==0) continue;
				$bagUsedCellCT++;
				#########################背包的物品 9.18 谭炜###########################
				if(!empty($bagtype))
				{
					$varyname = explode("|",$bagtype);
					if(!in_array($rs['varyname'],$varyname))
					{
						continue;
					}
				}
				##########################在这里结束###############################
				if (strlen($rs['requires'])>2)
				{
					$t = explode(',', str_replace(array('lv','wx'),array('等级','五行'),$rs['requires']));
					$wx = isset($t[1]) ? str_replace($_props['wxs'],$_props['wxd'],$t[1]) : '无';
				}
				else $t[0]=$wx='无';
				if ($rs['varyname'] == 9 && strlen($rs['effect'])>1)
				{
					$effect = zbAttrib($rs['effect']);
				}else $effect='';
				$nameHtml = getBagHtml($rs['name']);
				$nameJs = getBagHtml(getBagJsSingle($rs['name']));

				if ($style == 1)
				{
						 $bag .= '<li><a >
							<p class="p1" onDblClick="Used();"><img src="../images/ui/bag/'.$rs['varyname'].'.gif" /></p>
							<p class="p2" id="t'.$rs['id'].'" onmouseover="showTip2('.$rs['id'].',0,1,2);" onDblClick="myContextMenu('."'".$nameJs."'".','.$rs['id'].')" onmouseout="UnBagTip2();" onclick="sel(this);bid='.$rs['id'].';price='.$rs['sell'].';copyWorda(\''.$nameJs.'\');" style="text-align:left;">'.$nameHtml.'</p>
							<p class="p3">' . getBagHtml($_props['varyname'][$rs['varyname']]) . '</p>
							<p class="p4">' . $rs['sums'] .'</p>
						 </a></li>';
				}
				else
				{
					$bag .= '<tr>
						<td width="35px"><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
						<td width="110px" id="t'.$rs['id'].'" onDblClick="myContextMenu('."'".$nameJs."'".','.$rs['id'].')" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';" onmouseout="window.parent.UnTip();this.style.border=0;" onclick="sel(this);bid='.$rs['id'].';price='.$rs['sell'].';copyWorda(\''.$nameJs.'\')" style="cursor:pointer;text-align:left">'.$nameHtml.'</td>
						<td width="60px" style="text-align:left">'.$rs['sell'].'</td>
						<td style="text-align:left" id="s'.$rs['id'].'">'.$rs['sums'].'</td>
					 </tr>';
				}
				$bagRenderCount++;
			}
		}
	}
	else
	{
		foreach ($userBag as $k => $rs)
		{
			$rs = normalizeBagRow($rs);
			if($rs === false) continue;
			if ($rs['sums'] < 1 || $rs['zbing']==1) continue;
			if ($rs['sums'] < 1 || $rs['id']==0) continue;
			$bagUsedCellCT++;
			#########################背包的物品 9.18 谭炜###########################
			if(!empty($bagtype))
			{
				$varyname = explode("|",$bagtype);
				if(!in_array($rs['varyname'],$varyname))
				{
					continue;
				}
			}
			##########################在这里结束###############################
			if (strlen($rs['requires'])>2)
			{
				$t = explode(',', str_replace(array('lv','wx'),array('等级','五行'),$rs['requires']));
				$wx = isset($t[1]) ? str_replace($_props['wxs'],$_props['wxd'],$t[1]) : '无';
			}
			else $t[0]=$wx='无';
			if ($rs['varyname'] == 9 && strlen($rs['effect'])>1)
			{
				$effect = zbAttrib($rs['effect']);
			}else $effect='';
			$nameHtml = getBagHtml($rs['name']);
			$nameJs = getBagHtml(getBagJsSingle($rs['name']));

			if ($style == 1)
			{
					 $bag .= '<li><a >
						<p class="p1" onDblClick="Used();"><img src="../images/ui/bag/'.$rs['varyname'].'.gif" /></p>
						<p class="p2" id="t'.$rs['id'].'" onmouseover="showTip2('.$rs['id'].',0,1,2)" onDblClick="myContextMenu('."'".$nameJs."'".','.$rs['id'].')" onmouseout="UnBagTip2()" onclick="sel(this);bid='.$rs['id'].';price='.$rs['sell'].';copyWorda('."'".$nameJs."'".');" style="text-align:left;">'.$nameHtml.'</p>
						<p class="p3">' . getBagHtml($_props['varyname'][$rs['varyname']]) . '</p>
						<p class="p4">' . $rs['sums'] .'</p>
					 </a></li>';
			}
			else
			{
				$bag .= '<tr>
						<td width="35px"><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
						<td width="110px" id="t'.$rs['id'].'" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';" onmouseout="window.parent.UnTip();this.style.border=0;" onclick="sel(this);bid='.$rs['id'].';price='.$rs['sell'].';copyWorda(\''.$nameJs.'\')" style="cursor:pointer;text-align:left">'.$nameHtml.'</td>
						<td width="60px" style="text-align:left">'.$rs['sell'].'</td>
						<td style="text-align:left" id="s'.$rs['id'].'">'.$rs['sums'].'</td>
					 </tr>';
			}
			$bagRenderCount++;
		}
	}
}
if($bagRenderCount < 1)
{
	if($style == 1)
	{
		$emptyText = $bagtype === '' ? '您的包裹是空的！' : '该分类暂无物品！';
		$bag .= '<li class="bag_empty"><p>'.$emptyText.'</p></li>';
	}
	else $bag = '您的包裹是空的！';
}
$maxbag = isset($user['maxbag']) ? intval($user['maxbag']) : 0;
$bagInfo = $bagUsedCellCT.'/'.$maxbag;
if ($style == 1)
{
	$bag = '<div class="close_btn" onclick="ShowBox(\'Tools\',\'1\',\'3\')"></div>
	<div class="i_pack"><span>当前背包空间：'.$bagInfo.'</span><input type="button" id="incangkuAll" class="bag_quick_depot" value="秒放仓库" onclick="putBagPropsAllSameIn();"/></div>
	<div class="pack_filter"><label for="bag_type_select">分类查看：</label><select id="bag_type_select" onchange="changeBagType(this.value)">'.$bagoption.'</select></div>
	<div class="pack_title">
	<ul class="list l1"><li><p class="p1">图标</p><p class="p2">物品名称</p><p class="p3">类型</p><p class="p4">数量</p></li></ul>
        </div>'.$bag.' </ul>
        </div>
		        <div class="pac_btn">
	<input type="button" class="ico_btn" value="使用" id="inused" onclick="Used();"/>
          <input type="button" id="incangku" class="ico_btn" value="放入仓库" onclick="putBagProps2Depot();"/>
          <input type="button" class="ico_btn" value="丢弃" onclick="dropBagProps();"/>
          <input type="button" class="ico_btn" value="整理" onclick="Clean();" />
        </div>
		';
}
else
{
	$bag = '<table width="93%" border="0" cellspacing="0" cellpadding="2" background="#ffffff">'.$bag.'</table>';
}

//<iframe   src='javascript:false'   style='Z-INDEX:-1; FILTER:progid:DXImageTransform.Microsoft.Alpha(style=0,opacity=0);   LEFT:0px;   VISIBILITY:inherit;   WIDTH:90%;   POSITION:absolute;   TOP:0px;   HEIGHT:290px'>    </iframe>  >
ob_start('ob_gzip');
echo $bag;
ob_end_flush();
?>
