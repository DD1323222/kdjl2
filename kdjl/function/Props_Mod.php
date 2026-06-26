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
session_start();
require_once('../config/config.game.php');
$m = $_pm['mem'];
$u = $_pm['user'];
secStart($m);

$user		= $u->getUserById($_SESSION['id']);
$props		= unserialize($m->get(MEM_PROPS_KEY));
$userBag	= $u->getUserBagById($_SESSION['id']);
$type = $_REQUEST['type'];
$bagtype = $_REQUEST['bagtype'];
if (!is_array($props)) $shop='还没有任何商品!';
else
{
	foreach ($props as $k => $rs)
	{
		
		###################分类展示，9.18，谭炜######################
		if(!empty($type))
		{
			$varyname = explode("|",$type); 
			if(!in_array($rs['varyname'],$varyname))
			{
				continue;
			}
		}
		###################分类展示结束######################
		if ($rs['buy']==0 || 
			$rs['id'] ==0 || 
			$rs['varyname']==9 || 
			intval($rs['yb'])>0) continue;

		if ($rs['requires']!=0) 
		{
			$t = split(',', 
					   str_replace(array('lv','wx'), array('等级','五行'), $rs['requires'])
				      );
			$wx = str_replace($_props['wxs'], $_props['wxd'], $t[1]);
		}
		else $t[0]= $wx= '无';
		$shop .= '<tr>
		<td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
              		<td width="110px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left" onmouseover="window.parent.showTipEquip('.$rs['id'].',1,event);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" onclick="sel(this);copyWord(\''.$rs['name'].'\');bid='.($rs['id']?$rs['id']:0).';price='.$rs['buy'].';">'.$rs['name'].'</td>
              		<td width="60px" style="text-align:left">' . $rs['buy'] . '</td>
              		<td style="text-align:left">' . $_props['vary'][$rs['vary']] .'</td>
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
		$bagoption .= "<option selected=selected value='./Props_Mod.php?bagtype=".$v."&type=".$type." '>".$arrinfo[$ks]."</option>";
	}
	else
	{
		$bagoption .= "<option value='./Props_Mod.php?bagtype=".$v."&type=".$type." '>".$arrinfo[$ks]."</option>";
	}
}


##########################在这里结束###############################
if (!is_array($userBag)) $bag='还没有任何物品!';
else
{
	foreach ($userBag as $k => $rs)
	{
		if ($rs['sums'] < 1 || 
			$rs['id']==0 || 
			$rs['zbing'] == 1) continue;
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
			$t = split(',', 
					   str_replace(array('lv','wx'), array('等级','五行'), $rs['requires'])
					  );
			$wx = str_replace($_props['wxs'], $_props['wxd'], $t[1]);
		}
		else $t[0]= $wx= '无';
		$bag .= ' <table class="tit01" id="mybag1">
<tr>
		<td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
              		<td width="110px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" onclick="sel(this);copyWord(\''.$rs['name'].'\');bid='.$rs['id'].';price='.$rs['sell'].';">'.$rs['name'].'</td>
              		<td width="60px" style="text-align:left">' . $rs['sell'] . '</td>
              		<td style="text-align:left" id="s'.$rs['id'].'" >' . $rs['sums'] .'</td>
            	 </tr>';
		$curBagNum++;
	}
}

$bag .= '</table>';


//威望商店
if (!is_array($props)) $preshop='还没有任何商品!';
else
{
	foreach ($props as $k => $rs)
	{
		if ($rs['prestige']==0 || 
			$rs['id'] ==0 || 
			$rs['varyname']==9) continue;

		if ($rs['requires']!=0) 
		{
			$t = split(',', 
					   str_replace(array('lv','wx'), array('等级','五行'), $rs['requires'])
				      );
			$wx = str_replace($_props['wxs'], $_props['wxd'], $t[1]);
		}
		else $t[0]= $wx= '无';
		$preshop .= '<tr>
		<td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
              		<td width="110px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left" onmouseover="window.parent.showTipEquip('.$rs['id'].',1,window.event);;this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" onclick="copyWord(\''.$rs[name].'\');sel(this);bid='.($rs['id']?$rs['id']:0).';price='.$rs['buy'].';prestige='.$rs['prestige'].';">'.$rs['name'].'</td>
              		<td width="60px" style="text-align:left" >' . $rs['prestige'] . '</td>
              		<td style="text-align:left">' . $_props['vary'][$rs['vary']] .'</td>
            	 </tr>';
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
if(empty($shop))
{
	$shop = "没有相应的商品！";
}
$tn = $_game['template'] . 'tpl_props.html';
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
				 '#preshoplist#',
				 '#bagoption#',
				 '#prestige#'
				);
	$des = array($user['money'],
				 $user['yb'],
				 $curBagNum.'/'.$user['maxbag'],
				 //right part
				 $shop,
				 $bag,
				 $taskword,
				 $preshop,
				 $bagoption,
				 $user['prestige']
				);
	$shop = str_replace($src, $des, $tpl);
}

// gzip echo. if maybe.
ob_start('ob_gzip');
echo $shop;
ob_end_flush();
?>