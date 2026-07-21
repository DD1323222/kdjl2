<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.05.22
*@Usage: Shop main ui
*@Note: none
*/
session_start();
require_once('../config/config.game.php');
$m = $_pm['mem'];
$u = $_pm['user'];
secStart($m);

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');
del_bag_expire();

function shopsmYbHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function shopsmYbJsSingle($value)
{
	$value = str_replace("\\", "\\\\", (string)$value);
	$value = str_replace("'", "\\'", $value);
	$value = str_replace(array("\r", "\n"), array("\\r", "\\n"), $value);
	return $value;
}

$user		= $u->getUserById($uid);
$userBag	= $u->getUserBagById($uid);
if(!is_array($user)) die('');
$userDefaults = array('money'=>0, 'yb'=>0, 'maxbag'=>0, 'task'=>0);
foreach($userDefaults as $userDefaultKey => $userDefaultValue)
{
	if(!isset($user[$userDefaultKey])) $user[$userDefaultKey] = $userDefaultValue;
}
$props = kdjlSafeMemValue($m->get(MEM_PROPS_KEY), array());
$bagtype = (isset($_REQUEST['bagtype']) && !is_array($_REQUEST['bagtype'])) ? preg_replace('/[^0-9,|]/', '', $_REQUEST['bagtype']) : '';
$basetype = (isset($_REQUEST['basetype']) && !is_array($_REQUEST['basetype'])) ? preg_replace('/[^0-9,|]/', '', $_REQUEST['basetype']) : '';
$bagoption = '';
$baseoption = '';
$shop = '';

#########################仓库的物品 9.18 谭炜###########################3
$strings = ",1,2,3,4,5,6,7,8,9,10|11,12,13,14,15,16";
$strinfo = "全部道具,辅助道具,增益道具,捕捉道具,收集道具,技能书,卡片道具,进化道具,合体道具,装备道具,精练道具,宝箱道具,特殊道具,功能道具,宠物卵,合成道具";
$arr = explode(",",$strings);
$arrinfo = explode(",",$strinfo);
//背包
foreach($arr as $ks => $v)
{
	if($bagtype == $v)
	{
		$bagoption .= "<option selected=selected value='./Shopsm_Mod.php?bagtype=".$v."&basetype=".$basetype." '>".$arrinfo[$ks]."</option>";
	}
	else
	{
		$bagoption .= "<option value='./Shopsm_Mod.php?bagtype=".$v."&basetype=".$basetype." '>".$arrinfo[$ks]."</option>";
	}
}
//神密商店
$basestr = ",15,9,1|2|3|4|5|6|7|8,10|11|12|13|14|16";
$baseinfo = "全部道具,宠物卵,宠物装备,宠物用品,奇珍异宝";
$basearr = explode(",",$basestr);
$basearrinfo = explode(",",$baseinfo);
foreach($basearr as $bk => $bv)
{
	if($basetype == $bv)
	{
		$baseoption .= "<option selected=selected value='./Shopsm_Mod.php?basetype=".$bv."&bagtype=".$bagtype." '>".$basearrinfo[$bk]."</option>";
	}
	else
	{
		$baseoption .= "<option value='./Shopsm_Mod.php?basetype=".$bv."&bagtype=".$bagtype." '>".$basearrinfo[$bk]."</option>";
	}
}
##########################在这里结束###############################

if (!is_array($props)) $shop='还没有任何商品!';
else
{
	foreach ($props as $k => $rs)
	{
		if(!is_array($rs)) continue;
		$rsDefaults = array('id'=>0, 'name'=>'', 'yb'=>0, 'vary'=>0, 'varyname'=>'', 'requires'=>'', 'effect'=>'', 'pluseffect'=>'');
		foreach($rsDefaults as $rsDefaultKey => $rsDefaultValue)
		{
			if(!isset($rs[$rsDefaultKey])) $rs[$rsDefaultKey] = $rsDefaultValue;
		}

	#########################神密商店的物品 9.18 谭炜###########################
		if(!empty($basetype))
		{
			$varyname = explode("|",$basetype);
			if(!in_array($rs['varyname'],$varyname))
			{
				continue;
			}
		}
	##########################在这里结束###############################

		if (intval($rs['yb'])<1) continue;
		if (strlen($rs['requires'])>2)
		{
			$t = explode(',',
					   str_replace(array('lv','wx'),array('等级','五行'), $rs['requires'])
					  );
			$wx = isset($t[1]) ? str_replace($_props['wxs'], $_props['wxd'], $t[1]) : '';
		}
		else $t[0]= $wx= '无';

		$effect = zbAttrib($rs['effect']);
		$effect = $effect=='无'?'':$effect.'<BR/>';
		$plus   = zbAttrib($rs['pluseffect']);
		$plus   = $plus=='无'?'':'<font color=green>'.$plus.'</font><BR/>';
		$varyTitle = isset($_props['vary'][$rs['vary']]) ? $_props['vary'][$rs['vary']] : '';
		$nameHtml = shopsmYbHtml($rs['name']);
		$nameJs = shopsmYbHtml(shopsmYbJsSingle($rs['name']));

		$shop .= '<tr>
		<td width="40%" id="t'.$rs['id'].'" style="cursor:hand;" onmouseover="showTip('.$rs['id'].');this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" onclick="copyWord(\''.$nameJs.'\');sel(this);bid='.($rs['id']?$rs['id']:0).';price='.$rs['yb'].';">'.$nameHtml.'</td>
		<td width="30%" >' . $rs['yb']/10 . '</td>
		<td width="30%" >' . shopsmYbHtml($varyTitle) .'</td>
	 </tr>';
	}
}

$curBagNum=0;
// Get userbag
if (!is_array($userBag)) $bag='还没有任何物品!';
else
{
	foreach ($userBag as $k => $rs)
	{
		if(!is_array($rs)) continue;
		$rsDefaults = array('id'=>0, 'name'=>'', 'sums'=>0, 'zbing'=>0, 'varyname'=>'', 'requires'=>'', 'sell'=>0);
		foreach($rsDefaults as $rsDefaultKey => $rsDefaultValue)
		{
			if(!isset($rs[$rsDefaultKey])) $rs[$rsDefaultKey] = $rsDefaultValue;
		}
		if ($rs['sums'] < 1 || $rs['id']==0 ||  $rs['zbing'] == 1) continue;
		$curBagNum++;
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
			$t = explode(',',
					   str_replace(array('lv','wx'), array('等级','五行'), $rs['requires'])
					  );
			$wx = isset($t[1]) ? str_replace($_props['wxs'], $_props['wxd'], $t[1]) : '';
		}
		else $t[0]= $wx= '无';
		$nameHtml = shopsmYbHtml($rs['name']);
		$nameJs = shopsmYbHtml(shopsmYbJsSingle($rs['name']));
		$bag .= '<tr>
		<td width="40%" id="t'.$rs['id'].'" style="cursor:hand;" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';"  onmouseout="window.parent.UnTip();this.style.border=0;" onclick="copyWord(\''.$nameJs.'\');sel(this);bid='.$rs['id'].';price='.$rs['sell'].';">'.$nameHtml.'</td>
		<td width="25%" >' . $rs['sell'] . '</td>
		<td width="35%" id="s'.$rs['id'].'" >' . $rs['sums'] .'</td>
	 </tr>';
	}
}

//Word part.
$taskword= taskcheck($user['task'],3);
$m->memClose();

if(empty($shop))
{
	$shop = "没有相应的商品！";
}
if(empty($bag))
{
	$bag = "您的背包中没有相应的物品！";
}
//@Load template.
$tn = $_game['template'] . 'tpl_smshop.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);

	$src = array('#money#',
				 '#yb#',
				 '#baglimit#',
				 //right attrib.
				 '#shoplist#',
				 '#mybag#',
				 '#word#',
				 '#bagoption#',
				 '#baseoption#'
				);
	$des = array($user['money'],
				 $user['yb'],
				 $curBagNum.'/'.$user['maxbag'],
				 //right part
				 $shop,
				 $bag,
				 $taskword,
				 $bagoption,
				 $baseoption
				);
	$shop = str_replace($src, $des, $tpl);
}

// gzip echo. if maybe.
ob_start('ob_gzip');
echo $shop;
ob_end_flush();
?>
