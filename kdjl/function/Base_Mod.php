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
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');
define('MEM_BAG_KEY', $uid . 'bag');

require_once('../config/config.game.php');
secStart($_pm['mem']);

function baseModHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function baseModJsSingle($value)
{
	$value = str_replace("\\", "\\\\", (string)$value);
	$value = str_replace("'", "\\'", $value);
	$value = str_replace(array("\r", "\n"), array("\\r", "\\n"), $value);
	return $value;
}

$uobj	 = $_pm['user'];
$user	 = $uobj->getUserById($uid);
$userBag = $uobj->getUserBagById($uid);
if(!is_array($user)) die('');
$userDefaults = array('money' => 0, 'yb' => 0, 'maxbag' => 0, 'maxbase' => 0, 'task' => 0, 'ckpwd' => '');
foreach($userDefaults as $defaultKey => $defaultValue)
{
	if(!isset($user[$defaultKey])) $user[$defaultKey] = $defaultValue;
}

$b=$bg=0;
$base = false;
$basetype = (isset($_REQUEST['basetype']) && !is_array($_REQUEST['basetype'])) ? preg_replace('/[^0-9,|]/', '', $_REQUEST['basetype']) : '';
$bagtype = (isset($_REQUEST['bagtype']) && !is_array($_REQUEST['bagtype'])) ? preg_replace('/[^0-9,|]/', '', $_REQUEST['bagtype']) : '';
$baseoption = '';
$bagoption = '';
$bag = '';
$shop = '';
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
if (!is_array($userBag)) $base='<tr><td colspan="4">您还没有任何存放物品噢!!</td></tr>';
else
{
	foreach ($userBag as $k => $rs)
	{
		if(!is_array($rs)) continue;
		$rsDefaults = array('id' => 0, 'pid' => 0, 'name' => '', 'sums' => 0, 'bsum' => 0, 'varyname' => '', 'requires' => '', 'sell' => 0);
		foreach($rsDefaults as $defaultKey => $defaultValue)
		{
			if(!isset($rs[$defaultKey])) $rs[$defaultKey] = $defaultValue;
		}
		$rs['id'] = intval($rs['id']);
		$rs['pid'] = intval($rs['pid']);
		$rs['sums'] = intval($rs['sums']);
		$rs['bsum'] = intval($rs['bsum']);
		$rs['varyname'] = intval($rs['varyname']);
		$rs['sell'] = intval($rs['sell']);
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
			$t = explode(',', str_replace(array('lv','wx'),array('等级','五行'),$rs['requires']));
			$wx = isset($t[1]) ? str_replace($_props['wxs'],$_props['wxd'],$t[1]) : '';
		}
		else $t[0]=$wx='无';

		$nameHtml = baseModHtml($rs['name']);
		$nameJs = baseModHtml(baseModJsSingle($rs['name']));
		$base.= '<tr>
		<td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
		<td width="110px" id="t'.$rs['id'].'"  style="cursor:pointer; text-align:left" onmouseover="showTip('.$rs['pid'].');this.style.border=\'solid 1px #DFD496\';"  onmouseout="window.parent.UnTip();this.style.border=0;" onclick="ready_fetch();copyWord(\''.$nameJs.'\');sel(this);bid='.$rs['id'].';price='.$rs['sell'].'">'.$nameHtml.'</td>
		<td width="60px" style=" text-align:left" >' . $rs['sell'] . '</td>
		<td width="" style=" text-align:left"  id="s'.$rs['id'].'" >' . $rs['bsum'] .'</td>
	 </tr>';

		unset($rs);
	}
}

if ($base === false) $base = '<tr><td colspan="4">您还没有任何存放物品噢!</td></tr>';

$curBagNum=0;
$bagShowNum=0;

if (!is_array($userBag)) $bag='还没有任何物品!';
else
{
	foreach ($userBag as $k => $rs)
	{
		if(!is_array($rs)) continue;
		$rsDefaults = array('id' => 0, 'pid' => 0, 'name' => '', 'sums' => 0, 'zbing' => 0, 'varyname' => '', 'requires' => '', 'sell' => 0);
		foreach($rsDefaults as $defaultKey => $defaultValue)
		{
			if(!isset($rs[$defaultKey])) $rs[$defaultKey] = $defaultValue;
		}
		$rs['id'] = intval($rs['id']);
		$rs['pid'] = intval($rs['pid']);
		$rs['sums'] = intval($rs['sums']);
		$rs['zbing'] = intval($rs['zbing']);
		$rs['varyname'] = intval($rs['varyname']);
		$rs['sell'] = intval($rs['sell']);

		if ($rs['sums'] < 1 || $rs['id']==0 || $rs['zbing'] == 1) continue;
		$curBagNum++;
		if (strlen($rs['requires'])>2)
		{
			$t = explode(',', str_replace(array('lv','wx'), array('等级','五行'), $rs['requires']));
			$wx = isset($t[1]) ? str_replace($_props['wxs'],$_props['wxd'],$t[1]) : '';
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
		$nameHtml = baseModHtml($rs['name']);
		$nameJs = baseModHtml(baseModJsSingle($rs['name']));
		$bag .= '<tr>
		<td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
		<td width="110px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" onclick="ready_put();copyWord(\''.$nameJs.'\');sel(this);bid='.$rs['id'].';price='.$rs['sell'].';">'.$nameHtml.'</td>
		<td width="60px" style="text-align:left">' . $rs['sell'] . '</td>
		<td style="text-align:left" id="s'.$rs['id'].'" >' . $rs['sums'] .'</td>
	 </tr>';
		$bagShowNum++;

	}
}
if($bagShowNum < 1) $bag = '';
if(empty($base))
{
	$base = "您的仓库中没有相应的物品！";
}
if(empty($bag))
{
	$bag = "<span style='font-size:12px'>您的背包中没有相应的物品！</span>";
}
$login = '密码：<input name="login" type="password" id="login"  /><br /><br />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" name="Submit" value="确定" onclick="login()" hidefocus />&nbsp;&nbsp;&nbsp;&nbsp;
<input type="button" name="Submit2" value="修改密码" onclick="update()" hidefocus />';
if(!empty($user['ckpwd']) && empty($_SESSION['login'.$uid]))
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

unset($uobj, $user, $userBag, $_pm['mem']);

// gzip echo. if maybe.
ob_start('ob_gzip');
echo $shop;

ob_end_flush();
?>
