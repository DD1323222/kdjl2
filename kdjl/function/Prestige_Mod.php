<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.07.14
*@Usage: Shop main ui
*@Note: none
*/
require_once('../config/config.game.php');
$m = $_pm['mem'];
$u = $_pm['user'];
secStart($m);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');

function prestigeModHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function prestigeModJsSingle($value)
{
	$value = str_replace("\\", "\\\\", (string)$value);
	$value = str_replace("'", "\\'", $value);
	$value = str_replace(array("\r", "\n"), array("\\r", "\\n"), $value);
	return $value;
}

$user		= $u->getUserById($uid);
$props		= $m->get(MEM_PROPS_KEY);
if(!is_array($props)) $props = kdjlSafeMemValue($props, array());
$userBag	= $u->getUserBagById($uid);
if(!is_array($user)) die('');
$userDefaults = array('money' => 0, 'yb' => 0, 'maxbag' => 0, 'prestige' => 0, 'task' => 0);
foreach($userDefaults as $defaultKey => $defaultValue)
{
	if(!isset($user[$defaultKey])) $user[$defaultKey] = $defaultValue;
}
$type = (isset($_REQUEST['type']) && !is_array($_REQUEST['type'])) ? preg_replace('/[^0-9,|]/', '', $_REQUEST['type']) : '';
$bagtype = (isset($_REQUEST['bagtype']) && !is_array($_REQUEST['bagtype'])) ? preg_replace('/[^0-9,|]/', '', $_REQUEST['bagtype']) : '';
$preshop = '';
$bag = '';
$bagoption = '';
$options = '';
if (!is_array($props)) $preshop='还没有任何商品!';
else
{
	foreach ($props as $k => $rs)
	{
		if(!is_array($rs)) continue;
		$rsDefaults = array('id' => 0, 'name' => '', 'vary' => 0, 'varyname' => 0, 'prestige' => 0, 'requires' => '', 'buy' => 0);
		foreach($rsDefaults as $defaultKey => $defaultValue)
		{
			if(!isset($rs[$defaultKey])) $rs[$defaultKey] = $defaultValue;
		}
		if ($rs['prestige']==0 ||
			$rs['id'] ==0 ||
			$rs['varyname']==9) continue;

		if ($rs['requires']!=0)
		{
			$t = explode(',',
					   str_replace(array('lv','wx'), array('等级','五行'), $rs['requires'])
				      );
			$wx = isset($t[1]) ? str_replace($_props['wxs'], $_props['wxd'], $t[1]) : '';
		}
		else $t[0]= $wx= '无';
		$varyTitle = isset($_props['vary'][$rs['vary']]) ? $_props['vary'][$rs['vary']] : '';
		$nameHtml = prestigeModHtml($rs['name']);
		$nameJs = prestigeModHtml(prestigeModJsSingle($rs['name']));
		$preshop .= '<tr>
		<td width="15%" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
		<td width="45%" id="t'.$rs['id'].'" style="cursor:pointer;" onmouseover="window.parent.showTipEquip('.$rs['id'].',1,window.event);;this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" onclick="copyWord(\''.$nameJs.'\');sel(this,true);bid='.($rs['id']?$rs['id']:0).';price='.$rs['buy'].';prestige='.$rs['prestige'].';">'.$nameHtml.'</td>
		<td width="35%" >' . $rs['prestige'] . '</td>
		<td width="20%" >' . prestigeModHtml($varyTitle) .'</td>
	 </tr>';
	}
}

$curBagNum = 0;
#########################背包的物品 9.18 谭炜###########################3
$strings = ",1,2,3,4,5,6,7,8,9,10|11,12,13,14,15,16";
$strinfo = "全部道具,辅助道具,增益道具,捕捉道具,收集道具,技能书,卡片道具,进化道具,合体道具,装备道具,精练道具,宝箱道具,特殊道具,功能道具,宠物卵,合成道具";
$arr = explode(",",$strings);
$arrinfo = explode(",",$strinfo);
foreach($arr as $ks => $v)
{
	if($bagtype == $v)
	{
		$bagoption .= "<option selected=selected value='./Prestige_Mod.php?bagtype=".$v."&type=".$type." '>".$arrinfo[$ks]."</option>";
	}
	else
	{
		$bagoption .= "<option value='./Prestige_Mod.php?bagtype=".$v."&type=".$type." '>".$arrinfo[$ks]."</option>";
	}
}
//道具商店的
$strings = ",1,13,5,7|8,2|3|4|6|10|11|12|14|15|16";
$strinfo = "全部道具,恢复药剂,功能道具,技能书,进化合体材料,杂货";
$arr = explode(",",$strings);
$arrinfo = explode(",",$strinfo);
foreach($arr as $ks => $v)
{
	if($type == $v)
	{
		$options .= "<option selected=selected value='./Prestige_Mod.php?type=".$v."&bagtype=".$type." '>".$arrinfo[$ks]."</option>";
	}
	else
	{
		$options .= "<option value='./Prestige_Mod.php?type=".$v."&bagtype=".$type." '>".$arrinfo[$ks]."</option>";
	}
}

##########################在这里结束###############################
if (!is_array($userBag)) $bag='还没有任何物品!';
else
{
	foreach ($userBag as $k => $rs)
	{
		if(!is_array($rs)) continue;
		$rsDefaults = array('id' => 0, 'name' => '', 'sums' => 0, 'zbing' => 0, 'vary' => 0, 'varyname' => 0, 'requires' => '', 'sell' => 0, 'prestige' => 0);
		foreach($rsDefaults as $defaultKey => $defaultValue)
		{
			if(!isset($rs[$defaultKey])) $rs[$defaultKey] = $defaultValue;
		}
		if ($rs['sums'] < 1 ||
			$rs['id']==0 ||
			$rs['zbing'] == 1) continue;
	########################背包的物品 9.18 谭炜###########################3
		if(!empty($bagtype))
		{
			$varyname = explode("|",$bagtype);
			if(!in_array($rs['varyname'],$varyname))
			{
				continue;
			}
		}
########################在这里结束###########################3
		if (strlen($rs['requires'])>2)
		{
			$t = explode(',',
					   str_replace(array('lv','wx'), array('等级','五行'), $rs['requires'])
					  );
			$wx = isset($t[1]) ? str_replace($_props['wxs'], $_props['wxd'], $t[1]) : '';
		}
		else $t[0]= $wx= '无';
		$nameHtml = prestigeModHtml($rs['name']);
		$nameJs = prestigeModHtml(prestigeModJsSingle($rs['name']));
		$bag .= '<tr>
		<td width="15%" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
		<td width="40%" id="t'.$rs['id'].'" style="cursor:pointer;" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" onclick="copyWord(\''.$nameJs.'\');sel(this,false);bid='.$rs['id'].';price='.$rs['sell'].';prestige='.$rs['prestige'].';types='.intval($rs['vary']).';">'.$nameHtml.'</td>
		<td width="25%" >' . $rs['sell'] . '</td>
		<td width="35%" id="s'.$rs['id'].'" >' . $rs['sums'] .'</td>
	 </tr>';
		$curBagNum++;
	}
}


//Word part.
$taskword= taskcheck($user['task'], 5);

$m->memClose();



//@Load template.
if(empty($bag))
{
	$bag = "您没有相应的道具！";
}
if(empty($preshop))
{
	$preshop = "没有相应的商品！";
}
$tn = $_game['template'] . 'tpl_prestige.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);

	$src = array('#money#',
				 '#yb#',
				 '#baglimit#',
				 //right attrib.
				 '#preshoplist#',
				 '#mybag#',
				 '#word#',
				 '#options#',
				 '#bagoption#',
				 '#prestige#'
				);
	$des = array($user['money'],
				 $user['yb'],
				 $curBagNum.'/'.$user['maxbag'],
				 //right part
				 $preshop,
				 $bag,
				 $taskword,
				 $options,
				 $bagoption,
				 $user['prestige']
				);
	$preshop = str_replace($src, $des, $tpl);
}

// gzip echo. if maybe.
ob_start('ob_gzip');
echo $preshop;
ob_end_flush();
?>
