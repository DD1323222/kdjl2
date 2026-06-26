<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.05.22
*@Usage: Shop main ui for zb
*@Note: none
*/
require_once('../config/config.game.php');
secStart($_pm['mem']);

$user		= $_pm['user']->getUserById($_SESSION['id']);
$props		= unserialize($_pm['mem']->get(MEM_PROPS_KEY));
$userBag	= $_pm['user']->getUserBagById($_SESSION['id']);
$bagtype = $_REQUEST['bagtype'];
$basetype = $_REQUEST['basetype'];

#########################背包的物品 9.19 谭炜###########################3
$strings = ",1,2,3,4,5,6,7,8,9,10|11,12,13,14,15,16";
$strinfo = "全部道具,辅助道具,增益道具,捕捉道具,收集道具,技能书,卡片道具,进化道具,合体道具,装备道具,精练道具,宝箱道具,特殊道具,功能道具,宠物卵,合成道具";
$arr = explode(",",$strings);
$arrinfo = explode(",",$strinfo);
foreach($arr as $ks => $v)
{
	if($bagtype == $v)
	{
		$bagoption .= "<option selected=selected value='./zbPrestige_Mod.php?bagtype=".$v."&basetype=".$basetype." '>".$arrinfo[$ks]."</option>";
	}
	else
	{
		$bagoption .= "<option value='./zbPrestige_Mod.php?bagtype=".$v."&basetype=".$basetype." '>".$arrinfo[$ks]."</option>";
	}
}

//铁匠铺
$basestr = ",4,2,1,3,5|6|7|8|9|10";
$baseinfo = "全部道具,武器,衣服,头盔,鞋子,其他";
$basearr = explode(",",$basestr);
$basearrinfo = explode(",",$baseinfo);
foreach($basearr as $bk => $bv)
{
	if($basetype == $bv)
	{
		$baseoption .= "<option selected=selected value='./zbPrestige_Mod.php?basetype=".$bv."&bagtype=".$bagtype." '>".$basearrinfo[$bk]."</option>";
	}
	else
	{
		$baseoption .= "<option value='./zbPrestige_Mod.php?basetype=".$bv."&bagtype=".$bagtype." '>".$basearrinfo[$bk]."</option>";
	}
}
##########################在这里结束###############################
$preshop=false;
if (!is_array($props)) $preshop='还没有任何装备物品!';
else
{
	foreach ($props as $k => $rs)
	{
		#########################商店的物品 9.18 谭炜###########################
		if(!empty($basetype))
		{
			$postion = explode("|",$basetype); 
			if(!in_array($rs['postion'],$postion))
			{
				continue;
			}
		}
		##########################在这里结束###############################
		if ($rs['varyname'] != 9) continue;
		if ($rs['prestige']==0 || $rs['id']==0) continue;
		if ($rs['requires']!='') 
		{
			$t = split(',', 
						str_replace(array('lv','wx'), array('等级','五行'),$rs['requires'])
					  );
			$wx = str_replace($_props['wxs'], $_props['wxd'], $t[1]);
		}
		else $t[0]= $wx= '无';
		
		// 装备属性显示。
		$zbeffect = zbAttrib($rs['effect']);
		$plus     = ($pzb=zbAttrib($rs['pluseffect']))=='无'?'':'<font color=green>'.$pzb.'</font><br/>';
		
		
		$preshop .= '<tr>
              		<td width="40%" id="t'.$rs['id'].'" style="cursor:pointer;" onmouseover="window.parent.showTipEquip('.$rs['id'].',1,window.event);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" onclick="sel(this);bid='.$rs['id'].';price='.$rs['buy'].';prestige='.$rs['prestige'].';">'.$rs['name'].'</td>
              		<td width="25%" >' . $rs['prestige'] . '</td>
              		<td width="35%" >' . $t[0].','.$wx .'</td>
            	 </tr>';
	}
}

if($preshop==false) $preshop='还没有任何装备物品!';

$curBagNum=0;
// Get userbag
if (!is_array($userBag)) $bag='您的包裹是空的!';
else
{
	foreach ($userBag as $k => $rs)
	{
		
		if ($rs['sums'] < 1 || $rs['id']==0 || $rs['zbing'] == 1) continue;
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
		if (strlen($rs['requires']) > 2) 
		{
			$t = split(',', 
					   str_replace(array('lv','wx'), array('等级','五行'), $rs['requires'])
					  );
			$wx = str_replace($_props['wxs'], $_props['wxd'], $t[1]);
		}
		else $t[0]= $wx= '无';
		
		if ($rs['varyname'] == 9) $zbeffect = zbAttrib($rs['effect']) . '<br/>';
		
		$bag .= '<tr>
              		<td width="40%" id="t'.$rs['id'].'" style="cursor:pointer;" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" onclick="sel(this);bid='.$rs['id'].';price='.$rs['sell'].';">'.$rs['name'].'</td>
              		<td width="25%" >' . $rs['sell'] . '</td>
              		<td width="35%" id="s'.$rs['id'].'" >' . $rs['sums'] .'</td>
            	 </tr>';
	}
	
}

$taskword= taskcheck($user['task'], 9);
$_pm['mem']->memClose();
unset($u, $m);

//@Load template.
if(empty($bag))
{
	$bag = "您的背包中没有相应的物品！";
}
if(empty($preshop))
{
	$preshop = "没有相应的装备物品！";
}
$tn = $_game['template'] . 'tpl_zbPrestige.html';
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
				 '#bagoption#',
				 '#baseoption#',
				 '#prestige#'
				);
	$des = array($user['money'],
				 $user['yb'],
				 $curBagNum.'/'.$user['maxbag'],
				 //right part
				 $preshop,
				 $bag,
				 $taskword,
				 $bagoption,
				 $baseoption,
				 $user['prestige']
				);
	$preshop = str_replace($src, $des, $tpl);
}

// gzip echo. if maybe.
ob_start('ob_gzip');
echo $preshop;
ob_end_flush();

?>