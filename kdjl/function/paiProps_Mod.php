<?php
require_once('../config/config.game.php');
secStart($_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid <= 0)
{
	die('登录状态已失效，请重新登录！');
}
del_bag_expire();
$user = $_pm['user']->getUserById($uid);
$userBag = $_pm['user']->getUserBagById($uid);
if(!is_array($user)) die('玩家数据不存在，请重新登录！');
$userDefaults = array('money'=>0, 'yb'=>0, 'maxbag'=>0, 'paimoney'=>0, 'task'=>0);
foreach($userDefaults as $userDefaultKey => $userDefaultValue)
{
	if(!isset($user[$userDefaultKey])) $user[$userDefaultKey] = $userDefaultValue;
}
$bagtype = (isset($_REQUEST['bagtype']) && !is_array($_REQUEST['bagtype'])) ? $_REQUEST['bagtype'] : '';
if($bagtype !== '' && !preg_match('/^[0-9,|]+$/', $bagtype))
{
	$bagtype = '';
}
$mypairet = "";
$bagoption = '';
$bag = '';
$shop = '';
$pendingExt = $_pm['mysql']->getOneRecord("SELECT paisj,paiyb FROM player_ext WHERE uid={$uid}");
if(!is_array($pendingExt)) $pendingExt = array('paisj'=>0, 'paiyb'=>0);
$pendingSummary = intval($user['paimoney']).'金币 / '.intval($pendingExt['paisj']).'水晶 / '.intval($pendingExt['paiyb']).'元宝';

function paiPropsHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

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
		$bagoption .= "<option selected=selected value='./paiProps_Mod.php?bagtype=".$v."'>".$arrinfo[$ks]."</option>";
	}
	else
	{
		$bagoption .= "<option value='./paiProps_Mod.php?bagtype=".$v."'>".$arrinfo[$ks]."</option>";
	}
}
##########################在这里结束###############################

if(is_array($userBag))
{
	foreach($userBag as $k => $rs)
	{
		if(!is_array($rs)) continue;
		$rsDefaults = array('id'=>0, 'pid'=>0, 'name'=>'', 'psum'=>0, 'psell'=>0, 'psj'=>0, 'pyb'=>0, 'petime'=>0);
		foreach($rsDefaults as $rsDefaultKey => $rsDefaultValue)
		{
			if(!isset($rs[$rsDefaultKey])) $rs[$rsDefaultKey] = $rsDefaultValue;
		}
		foreach(array('id','pid','psum','psell','psj','pyb','petime') as $numberField)
		{
			$rs[$numberField] = intval($rs[$numberField]);
		}
		if($rs['psum'] > 0 && ($rs['psell'] > 0 || $rs['psj'] > 0 || $rs['pyb'] > 0))
		{
			if($rs['petime'] < time())
			{
				$str = "已过期";
			}
			else
			{
				$str = date("H:i:s",$rs['petime']);
			}
			if($rs['psell'] > 0)
			{
				$pprice = $rs['psell'].'金币';
				$price = $rs['psell'];
			}
			else if($rs['psj'] > 0)
			{
				$pprice = $rs['psj'].'水晶';
				$price = $rs['psj'];
			}
			else
			{
				$pprice = $rs['pyb'].'元宝';
				$price = $rs['pyb'];
			}
			$itemName = paiPropsHtml($rs['name']);
			$mypairet .= '<tr>
						<td width="40%" id="t'.$rs['id'].'" style="cursor:pointer;" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';"  onmouseout="window.parent.UnTip();this.style.border=0" onclick="sel(this);pid='.$rs['pid'].';bid='.$rs['id'].';price='.$price.';">'.$itemName.'('.$rs['psum'].')</td>
						<td width="30%" >' . $pprice . '</td>
						<td width="30%" >' . $str .'</td>
					 </tr>';
		}
	}
	if($mypairet == "")
	{
		$mypairet .= '<tr><td colspan=3>暂时您还没有拍卖物品,拍卖后再来吧！</td></tr>';
	}
}
if($mypairet === '')
{
	$mypairet = '<tr><td colspan="3">暂时您还没有拍卖物品,拍卖后再来吧！</td></tr>';
}




$bg = 0;
// Get userbag
if (!is_array($userBag)) $bag='您的包裹是空的!';
else
{
	foreach ($userBag as $k => $rs)
	{
		if(!is_array($rs)) continue;
		$rsDefaults = array('id'=>0, 'pid'=>0, 'name'=>'', 'sums'=>0, 'zbing'=>0, 'varyname'=>'', 'requires'=>'', 'sell'=>0);
		foreach($rsDefaults as $rsDefaultKey => $rsDefaultValue)
		{
			if(!isset($rs[$rsDefaultKey])) $rs[$rsDefaultKey] = $rsDefaultValue;
		}
		foreach(array('id','pid','sums','zbing','varyname','sell') as $numberField)
		{
			$rs[$numberField] = intval($rs[$numberField]);
		}
		if ($rs['sums'] < 1 || $rs['id']==0 || $rs['zbing'] == 1) continue;
		$bg++;
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
			$wx = isset($t[1]) ? str_replace($_props['wxs'],$_props['wxd'],$t[1]) : '';
		}
		else $t[0]=$wx='无';

		$bag .= '<tr>
		<td width="40%" id="t'.$rs['id'].'" style="cursor:pointer;" onmouseover="showTip('.$rs['pid'].');this.style.border=\'solid 1px #DFD496\';" onmouseout="window.parent.UnTip();this.style.border=0" onclick="sel(this);bid='.$rs['id'].';price='.$rs['sell'].';">'.paiPropsHtml($rs['name']).'</td>
		<td width="25%" >' . $rs['sell'] . '</td>
		<td width="35%" id="s'.$rs['id'].'" >' . $rs['sums'] .'</td>
	 </tr>';
	}
}

//Word part.
$taskid = is_array($user) && isset($user['task']) ? $user['task'] : 0;
$taskword= taskcheck($taskid,7);
$_pm['mem']->memClose();
unset($db);
if(empty($bag))
{
	$bag = "您的背包中没有相应的物品！";
}
//@Load template.
$tn = $_game['template'] . 'tpl_paiProps.html';
if (file_exists($tn))
{
	$tpl = file_get_contents($tn);

	$src = array('#money#',
				 '#yb#',
				 '#baglimit#',
				 //right attrib.
				 '#myshoplist#',
				 '#mybag#',
				 '#word#',
				 '#paimoney#',
				 '#bagoption#'
				);
	$des = array(intval($user['money']),
				 intval($user['yb']),
				 $bg.'/'.intval($user['maxbag']),
				 //right part
				 $mypairet,
				 $bag,
				 $taskword,
				 paiPropsHtml($pendingSummary),
				 $bagoption
				);
	$shop = str_replace($src, $des, $tpl);
}
// gzip echo. if maybe.
ob_start('ob_gzip');
echo $shop;
ob_end_flush();
?>
