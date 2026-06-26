<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.05.22
*@Usage: 仓库显示脚本
*@Note: none
*/
session_start();
define(MEM_BAG_KEY, $_SESSION['id'] . 'bag');

require_once('../config/config.game.php');
secStart($_pm['mem']);

$uobj	 = $_pm['user'];
$user	 = $uobj->getUserById($_SESSION['id']);
$userBag = $uobj->getUserBagById($_SESSION['id']);

$b=$bg=0;
$base = false;
$basetype = $_REQUEST['basetype'];
$bagtype = $_REQUEST['bagtype'];
#########################仓库的物品 9.18 谭炜###########################3
$strings = ",1,2,3,4,5,6,7,8,9,10|11,12,13,14,15,16";
$strinfo = "全部道具,辅助道具,增益道具,捕捉道具,收集道具,技能书,卡片道具,进化道具,合体道具,装备道具,精练道具,宝箱道具,特殊道具,功能道具,宠物卵,合成道具";
$arr = explode(",",$strings);
$arrinfo = explode(",",$strinfo);
//仓库
foreach($arr as $ks => $v)
{
	if($basetype == $v)
	{
		$baseoption .= "<option selected=selected value='./Base_Mod.php?basetype=".$v."&bagtype=".$bagtype." '>".$arrinfo[$ks]."</option>";
	}
	else
	{
		$baseoption .= "<option value='./Base_Mod.php?basetype=".$v."&bagtype=".$bagtype." '>".$arrinfo[$ks]."</option>";
	}
}

//背包
foreach($arr as $ks => $v)
{
	if($bagtype == $v)
	{
		$bagoption .= "<option selected=selected value='./Base_Mod.php?bagtype=".$v."&basetype=".$basetype." '>".$arrinfo[$ks]."</option>";
	}
	else
	{
		$bagoption .= "<option value='./Base_Mod.php?bagtype=".$v."&basetype=".$basetype." '>".$arrinfo[$ks]."</option>";
	}
}
##########################在这里结束###############################
if ($userBag === false) $base='<tr><td colspan=3>您还没有任何存放物品噢!!</td></tr>';
else
{   
	foreach ($userBag as $k => $rs)
	{	
		if ($rs['sums']>0) $bg++;
		if ($rs['bsum']<1) continue;
		$b++;
		#########################仓库的物品 9.18 谭炜###########################
		if(!empty($basetype))
		{
			$varyname = explode("|",$basetype); 
			if(!in_array($rs['varyname'],$varyname))
			{
				continue;
			}
		}
		##########################在这里结束###############################
		
		if (strlen($rs['requires'])>2) 
		{
			$t = split(',', str_replace(array('lv','wx'),array('等级','五行'),$rs['requires']));
			$wx = str_replace($_props['wxs'],$_props['wxd'],$t[1]);
		}
		else $t[0]=$wx='无';
		
		$base.= '<tr>
		<td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
              		<td width="110px" id="t'.$rs['id'].'"  style="cursor:pointer; text-align:left" onmouseover="showTip('.$rs['pid'].');this.style.border=\'solid 1px #DFD496\';"  onmouseout="window.parent.UnTip();this.style.border=0;" onclick="ready_fetch();copyWord(\''.$rs[name].'\');sel(this);bid='.$rs['id'].';price='.$rs['sell'].'">'.$rs['name'].'</td>
              		<td width="60px" style=" text-align:left" >' . $rs['sell'] . '</td>
              		<td width="" style=" text-align:left"  id="s'.$rs['id'].'" >' . $rs['bsum'] .'</td>
            	 </tr>';
		
		unset($rs);
	}
}

if ($base === false) $base = '<tr><td colspan=3>您还没有任何存放物品噢!</td></tr>';

$curBagNum=0;

if (!is_array($userBag)) $bag='还没有任何物品!';
else
{
	foreach ($userBag as $k => $rs)
	{
		
		if ($rs['sums'] < 1 || $rs['id']==0 || $rs['zbing'] == 1) continue;
		$curBagNum++;
		if (strlen($rs['requires'])>2) 
		{
			$t = split(',', str_replace(array('lv','wx'), array('等级','五行'), $rs['requires']));
			$wx = str_replace($_props['wxs'],$_props['wxd'],$t[1]);
		}
		else $t[0]=$wx='无';
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
		$bag .= '<tr>
		<td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
              		<td width="110px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" onclick="ready_put();copyWord(\''.$rs[name].'\');sel(this);bid='.$rs['id'].';price='.$rs['sell'].';">'.$rs['name'].'</td>
              		<td width="60px" style="text-align:left">' . $rs['sell'] . '</td>
              		<td style="text-align:left" id="s'.$rs['id'].'" >' . $rs['sums'] .'</td>
            	 </tr>';
		
	}
}
if(empty($base))
{
	$base = "您的仓库中没有相应的物品！";
}
if(empty($bag))
{
	$bag = "<span style='font-size:12px'>您的背包中没有相应的物品！</font>";
}
$login = '密码：<input name="login" type="password" id="login"  /><br /><br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" name="Submit" value="确定" onclick="login()" hidefocus />&nbsp;&nbsp;&nbsp;&nbsp;
<input type="button" name="Submit2" value="修改密码" onclick="update()" hidefocus />';
if(!empty($user['ckpwd']) && empty($_SESSION['login'.$_SESSION['id']]))
{
	$base = $login;
}
//task part.
$taskword= taskcheck($user['task'],1);
$_pm['mem']->memClose();

//@Load template.
$tn = $_game['template'] . 'tpl_base.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);
	
	$src = array('#money#',
				 '#yb#',
				 '#baglimit#',
				 '#baselimit#',
				 //right attrib.
				 '#base#',
				 '#mybag#',
				 '#word#',
				 '#baseoption#',
				 '#bagoption#'
				);
	$des = array($user['money'],
				 $user['yb'],
				 $curBagNum.'/'.$user['maxbag'],
				 $b.'/'.$user['maxbase'],
				 //right part
				 $base,
				 $bag,
				 $taskword,
				 $baseoption,
				 $bagoption
				);
	$shop = str_replace($src, $des, $tpl);
}

unset($uobj, $user, $userbag,$_pm['mem']);

// gzip echo. if maybe.
ob_start('ob_gzip');
echo $shop;

ob_end_flush();
?>
