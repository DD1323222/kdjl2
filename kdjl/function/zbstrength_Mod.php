<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: 谭炜

*@Write Date: 18%08.09.12
*@Update Date: 18%08.09.12
*@Usage: Shop main ui for zbstrength
*@Note: none
*/
require_once('../config/config.game.php');
secStart($_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');
$pidKey = 'pid'.$uid;
$bidKey = 'bid'.$uid;
$pidsKey = 'pids'.$uid;
$strshop = '';
$strbag = '';
$shop = '';
$pimg = '';
$himg = '';
$pneeds = '';
$pmoney = 0;
$heffect = '';
$harden = (isset($harden) && is_array($harden)) ? $harden : array();

function zbstrengthHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function zbstrengthImage($value)
{
	return kdjlPropsImageName($value);
}

$user		= $_pm['user']->getUserById($uid);
$props		= $_pm['mem']->get(MEM_PROPS_KEY);
if(!is_array($props)) $props = kdjlSafeMemValue($props, array());
if(!is_array($props)) $props = array();
$propsById = array();
foreach($props as $propsRow)
{
	if(is_array($propsRow) && isset($propsRow['id'])) $propsById[intval($propsRow['id'])] = $propsRow;
}
$userBag	= $_pm['user']->getUserBagById($uid);
if(!is_array($userBag)) $userBag = array();
if(!is_array($user)) die('');
$userDefaults = array('money' => 0, 'yb' => 0, 'maxbag' => 0, 'task' => 0);
foreach($userDefaults as $defaultKey => $defaultValue)
{
	if(!isset($user[$defaultKey])) $user[$defaultKey] = $defaultValue;
}
//第一次进入该页面，清空部分SESSION值
$csign = (isset($_REQUEST['csign']) && !is_array($_REQUEST['csign'])) ? $_REQUEST['csign'] : '';
if($csign == "first")
{
	$_SESSION[$pidKey] = (isset($_REQUEST['pid']) && !is_array($_REQUEST['pid'])) ? intval($_REQUEST['pid']) : 0;//强化道具
	$_SESSION[$bidKey] = (isset($_REQUEST['bid']) && !is_array($_REQUEST['bid'])) ? intval($_REQUEST['bid']) : 0;
	$_SESSION[$pidsKey] = (isset($_REQUEST['pids']) && !is_array($_REQUEST['pids'])) ? intval($_REQUEST['pids']) : 0;//精练道具
}
if(isset($_REQUEST['pid'], $_REQUEST['bid']) && !is_array($_REQUEST['pid']) && !is_array($_REQUEST['bid']) && $_REQUEST['pid'] !== '' && $_REQUEST['bid'] !== '')
{
	$requestPid = intval($_REQUEST['pid']);
	$_SESSION[$pidKey] = $requestPid;//强化道具
	$requestBid = intval($_REQUEST['bid']);
	$_SESSION[$bidKey] = $requestBid;
}
if(isset($_REQUEST['pids']) && !is_array($_REQUEST['pids']) && $_REQUEST['pids'] !== '')
{
	$requestPids = intval($_REQUEST['pids']);
	$_SESSION[$pidsKey] = $requestPids;//精练道具
}

$selectedPid = isset($_SESSION[$pidKey]) ? intval($_SESSION[$pidKey]) : 0;
$selectedBid = isset($_SESSION[$bidKey]) ? intval($_SESSION[$bidKey]) : 0;
$selectedPids = isset($_SESSION[$pidsKey]) ? intval($_SESSION[$pidsKey]) : 0;
$selectedTargetValid = false;
if($selectedPid > 0 && $selectedBid > 0 && isset($propsById[$selectedPid]) &&
	isset($propsById[$selectedPid]['varyname'], $propsById[$selectedPid]['plusflag']) &&
	intval($propsById[$selectedPid]['varyname']) == 9 && intval($propsById[$selectedPid]['plusflag']) == 1)
{
	foreach($userBag as $selectedBagRow)
	{
		if(is_array($selectedBagRow) && intval($selectedBagRow['id']) == $selectedBid &&
			intval($selectedBagRow['pid']) == $selectedPid && intval($selectedBagRow['sums']) > 0 &&
			intval($selectedBagRow['zbing']) == 0 && (!isset($selectedBagRow['cantrade']) || intval($selectedBagRow['cantrade']) != 3))
		{
			$selectedTargetValid = true;
			break;
		}
	}
}
if(!$selectedTargetValid)
{
	$selectedPid = $selectedBid = 0;
	$_SESSION[$pidKey] = $_SESSION[$bidKey] = '';
}
$selectedAuxiliaryValid = false;
if($selectedPids > 0 && isset($propsById[$selectedPids]) && isset($propsById[$selectedPids]['varyname']) &&
	intval($propsById[$selectedPids]['varyname']) == 11)
{
	foreach($userBag as $selectedBagRow)
	{
		if(is_array($selectedBagRow) && intval($selectedBagRow['pid']) == $selectedPids &&
			intval($selectedBagRow['sums']) > 0 && intval($selectedBagRow['zbing']) == 0 &&
			(!isset($selectedBagRow['cantrade']) || intval($selectedBagRow['cantrade']) != 3))
		{
			$selectedAuxiliaryValid = true;
			break;
		}
	}
}
if(!$selectedAuxiliaryValid)
{
	$selectedPids = 0;
	$_SESSION[$pidsKey] = '';
}

foreach($props as $prop)
{
	if(!is_array($prop)) continue;
	$propDefaults = array('id' => 0, 'name' => '', 'varyname' => 0, 'plusflag' => 0, 'img' => '', 'pluspid' => 0, 'usages' => '');
	foreach($propDefaults as $defaultKey => $defaultValue)
	{
		if(!isset($prop[$defaultKey])) $prop[$defaultKey] = $defaultValue;
	}
	//要强化的道具
	if(!empty($_SESSION[$pidsKey]) || !empty($_SESSION[$pidKey]))
	{
		if($prop['id'] == $_SESSION[$pidKey])
		{
			if($prop['varyname'] == 9 && $prop['plusflag'] == 1)
			{
				$pimg = zbstrengthImage($prop['img']);
				$nid = $prop['pluspid'];
				foreach($props as $nprop)
				{
					if(!is_array($nprop)) continue;
					if(!isset($nprop['id'])) $nprop['id'] = 0;
					if(!isset($nprop['name'])) $nprop['name'] = '';
					if($nprop['id'] == $nid)
					{
						$pneeds = zbstrengthHtml($nprop['name']);//强化所需要的物品
					}
				}
				//得到用户当前强化的次数
				foreach($userBag as $ub)
				{
					if(!is_array($ub)) continue;
					if(!isset($ub['id'])) $ub['id'] = 0;
					if(!isset($ub['plus_tmes_eft'])) $ub['plus_tmes_eft'] = '';
					if($ub['id'] == $_SESSION[$bidKey])
					{
						$plus_tms_eft = $ub['plus_tmes_eft'];
						//在这之前强化过
						if(!empty($plus_tms_eft))
						{
							$plusarr = explode(",",$plus_tms_eft);
							foreach($harden as $kh => $har)
							{
								$num = $kh + 1;
								if($kh == $plusarr[0] && isset($harden[$num]))
								{
									$eff = explode(",",$harden[$num]);
									$pmoney = isset($eff[1]) ? intval($eff[1]) : 0;
								}
							}
						}
						else
						{
							$eff = isset($harden[0]) ? explode(",",$harden[0]) : array();
							$pmoney = isset($eff[1]) ? intval($eff[1]) : 0;
						}
					}
				} //end foreach
			}
		}
	}
	//精练道具
	if(!empty($_SESSION[$pidsKey]))
	{
		if($prop['id'] == $_SESSION[$pidsKey])
		{
			if($prop['varyname'] == 11)
			{
				$himg = zbstrengthImage($prop['img']);
				if(!empty($prop['usages']))
				{
					$arr = explode("：",$prop['usages']);
					$heffect = isset($arr[1]) ? zbstrengthHtml($arr[1]) : '';
				}
			}
		}
	}
}
/*<tr>
    <td><div class="eqbox"></div></td>
    <td class="txt05"> 请加入需要强化装备
  需要材料：<br />
  花费金币：<br /></td>
  </tr>*/
if(!empty($_SESSION[$pidKey])){
	//强化炉
	$id = isset($_SESSION[$bidKey]) ? intval($_SESSION[$bidKey]) : 0;
	$selectedPid = intval($_SESSION[$pidKey]);
	$strshop .= '<tr>
    <td><div class="eqbox" onmouseover="showTip('.$id.',0,1,2);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" ondblclick="takeoff('.$selectedPid.')"><input name="pid" type="hidden" id="pid" value='.$selectedPid.' /><input name="bid" type="hidden" id="bid" value='.$id.' /><img src="'.IMAGE_SRC_URL.'/props/'.$pimg.'"></div></td>
    <td class="txt05"> 请加入需要强化装备
  需要材料：'.$pneeds.'<br />
  花费金币：'.$pmoney.'<br /></td>
  </tr>';
}else{
	$strshop .= '<tr>
    <td><div class="eqbox"></div></td>
    <td class="txt05"> 请加入需要强化装备
  需要材料：<br />
  花费金币：<br /></td>
  </tr>';
}
/*<tr>
    <td><div class="eqbox"></div></td>
    <td class="txt05">加入强化辅助道具（非必须）
  辅助效果：<br /></td>
  </tr>*/
if(!empty($_SESSION[$pidsKey])){
	$selectedPids = intval($_SESSION[$pidsKey]);
	$strshop .= '<tr>
    <td><div class="eqbox" onmouseover="showTip('.$selectedPids.');this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" ondblclick="takeoff('.$selectedPids.')"><input name="pids" type="hidden" id="pids" value='.$selectedPids.' /><img src="'.IMAGE_SRC_URL.'/props/'.$himg.'"></div></td>
    <td class="txt05">加入强化辅助道具（非必须）
  辅助效果：'.$heffect.'<br /></td>
  </tr>';
}else{
	$strshop .= '<tr>
    <td><div class="eqbox"></div></td>
    <td class="txt05">加入强化辅助道具（非必须）
  辅助效果：<br /></td>
  </tr>';
}




/*if(!empty($_SESSION['pid'.$_SESSION['id']]))
{
	//强化炉
	$id = $_SESSION['bid'.$_SESSION['id']];
	$shop .= '<tr height="54">';
	$shop .= '<td width="18%" align="center" style="cursor:pointer;" background="'.IMAGE_SRC_URL.'/ui/shop/qh02.gif" onmouseover="showTip('.$id.',0,1,2);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" ondblclick="takeoff('.$_SESSION['pid'.$_SESSION['id']].')"><input name="pid" type="hidden" id="pid" value='.$_SESSION['pid'.$_SESSION['id']].' /><input name="bid" type="hidden" id="bid" value='.$_SESSION['bid'.$_SESSION['id']].' /><img src="'.IMAGE_SRC_URL.'/props/'.$pimg.'"></td>';//图
	$shop .= '<td align="left">&nbsp;&nbsp;请加入需要强化装备<br />
			&nbsp;&nbsp;需要材料：'.$pneeds.'<br />&nbsp;&nbsp;花费金币：'.$pmoney.'</td>';
	$shop .= '</tr>';
	//加入空格
	$shop .= '<tr>';
	$shop .= '<td width="18%" align="center">&nbsp;</td>';
	$shop .= '<td align="left">&nbsp;</td>';
	$shop .= '</tr>';
}
else
{
	//强化炉
	$shop .= '<tr height="54">';
	$shop .= '<td width="18%" align="center" background="'.IMAGE_SRC_URL.'/ui/shop/qh02.gif"></td>';//图
	$shop .= '<td align="left">&nbsp;&nbsp;请加入需要强化装备<br />
			&nbsp;&nbsp;需要材料：<br />&nbsp;&nbsp;花费金币：</td>';
	$shop .= '</tr>';
	//加入空格
	$shop .= '<tr>';
	$shop .= '<td width="18%" align="center">&nbsp;</td>';
	$shop .= '<td align="left">&nbsp;</td>';
	$shop .= '</tr>';
}
if(!empty($_SESSION['pids'.$_SESSION['id']]))
{
	//辅助材料
	$shop .= '<tr  height="54">';
	$shop .= '<td width="18%" align="center" style="cursor:pointer;" background="'.IMAGE_SRC_URL.'/ui/shop/qh02.gif" onmouseover="showTip('.$_SESSION['pids'.$_SESSION['id']].');this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" ondblclick="takeoff('.$_SESSION['pids'.$_SESSION['id']].')"><input name="pids" type="hidden" id="pids" value='.$_SESSION['pids'.$_SESSION['id']].' /><img src="'.IMAGE_SRC_URL.'/props/'.$himg.'"></td>';//图片
	$shop .= '<td align="left">&nbsp;&nbsp;加入强化辅助道具（非必须）<br />
			&nbsp;&nbsp;辅助效果：'.$heffect.'<br /></td>';
	$shop .= '</tr>';
	$shop .= '<tr>';
	$shop .= '<td width="18%" align="center">&nbsp;</td>';
	$shop .= '<td align="left">&nbsp;</td>';
	$shop .= '</tr>';
}
else
{
	//辅助材料
	$shop .= '<tr height="54">';
	$shop .= '<td width="18%" align="center" background="'.IMAGE_SRC_URL.'/ui/shop/qh02.gif"></td>';//图片
	$shop .= '<td align="left">&nbsp;&nbsp;加入强化辅助道具（非必须）<br />
			&nbsp;&nbsp;辅助效果：<br /></td>';
	$shop .= '</tr>';
	$shop .= '<tr>';
	$shop .= '<td width="18%" align="center">&nbsp;</td>';
	$shop .= '<td align="left">&nbsp;</td>';
	$shop .= '</tr>';
}
$shop .= '<tr>
	 <td colspan="2" align="center">
		 <input type="button" hidefocus onclick="sell();"
style="cursor:pointer;width:102px;height:47px;background-image:url('.IMAGE_SRC_URL.'/ui/compose/hc06.gif);font-weight:bold;" id="snb"
				value="加入物品" >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" hidefocus onclick="harden();"
style="cursor:pointer;width:102px;height:47px;background-image:url('.IMAGE_SRC_URL.'/ui/compose/hc06.gif);font-weight:bold;" id="snb"
				value="开始强化" >
		 </td>
		 </tr>';*/
$strCurBagNum=0;
$k = $rs = '';
if (!is_array($userBag)) $strbag='您的包裹是空的!';
else
{
	foreach ($userBag as $k => $rs)
	{
		if(!is_array($rs)) continue;
		$rsDefaults = array('id' => 0, 'pid' => 0, 'name' => '', 'sums' => 0, 'zbing' => 0, 'varyname' => 0, 'requires' => '', 'effect' => '', 'sell' => 0);
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

		##################只显示装备和精练辅助的道具#########################
		$strCurBagNum++;
		if($rs['varyname'] != 9 && $rs['varyname'] != 11 && $rs['varyname'] != 10) continue;
		if (strlen($rs['requires']) > 2)
		{
			$t = explode(',',
					   str_replace(array('lv','wx'), array('等级','五行'), $rs['requires'])
					  );
			$wx = isset($t[1]) ? str_replace($_props['wxs'], $_props['wxd'], $t[1]) : '';
		}
		else $t[0]= $wx= '无';

		if ($rs['varyname'] == 9) $zbeffect = zbAttrib($rs['effect']) . '<br/>';

		$strbag .= '<tr>
		<td width="18%" id="t'.$rs['id'].'" style="cursor:pointer;" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" onclick="sel(this);bid='.$rs['id'].';price='.$rs['sell'].';pid='.$rs['pid'].';">'.zbstrengthHtml($rs['name']).'</td>
		<td width="18%" >' . $rs['sell'] . '</td>
		<td width="18%" id="s'.$rs['id'].'" >' . $rs['sums'] .'</td>
	 </tr>';
	}

}

$taskword= taskcheck($user['task'], 9);
$_pm['mem']->memClose();
unset($u, $m);

//@Load template.
$tn = $_game['template'] . 'tpl_zbstrength.html';
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
				);
	$des = array($user['money'],
				 $user['yb'],
				 $strCurBagNum.'/'.$user['maxbag'],
				 //right part
				 $strshop,
				 $strbag,
				 $taskword
				);
	$shop = str_replace($src, $des, $tpl);
}

// gzip echo. if maybe.
ob_start('ob_gzip');
echo $shop;
ob_end_flush();

?>
