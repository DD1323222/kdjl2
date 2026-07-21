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
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid <= 0)
{
	die('登录状态无效！');
}

function zbModHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function zbModJsSingle($value)
{
	$value = str_replace("\\", "\\\\", (string)$value);
	$value = str_replace("'", "\\'", $value);
	$value = str_replace(array("\r", "\n"), array("\\r", "\\n"), $value);
	return $value;
}

function zbModImage($value)
{
	return kdjlPropsImageName($value);
}

$pidKey = 'pid'.$uid;
$bidKey = 'bid'.$uid;
$pidsKey = 'pids'.$uid;
$user		= $_pm['user']->getUserById($uid);
$props		= kdjlSafeMemValue($_pm['mem']->get(MEM_PROPS_KEY), array());
$userBag	= $_pm['user']->getUserBagById($uid);
if(!is_array($user)) die('玩家数据错误！');
if(!is_array($props)) $props = array();
if(!is_array($userBag)) $userBag = array();
$propsById = array();
foreach($props as $propsRow)
{
	if(is_array($propsRow) && isset($propsRow['id'])) $propsById[intval($propsRow['id'])] = $propsRow;
}
$userDefaults = array('money'=>0, 'prestige'=>0, 'maxbag'=>0, 'task'=>0);
foreach($userDefaults as $userDefaultKey => $userDefaultValue)
{
	if(!isset($user[$userDefaultKey])) $user[$userDefaultKey] = $userDefaultValue;
}
$harden = (isset($harden) && is_array($harden)) ? $harden : array();
$bagtype = (isset($_REQUEST['bagtype']) && !is_array($_REQUEST['bagtype'])) ? preg_replace('/[^0-9,|]/', '', $_REQUEST['bagtype']) : '';
$basetype = (isset($_REQUEST['basetype']) && !is_array($_REQUEST['basetype'])) ? preg_replace('/[^0-9,|]/', '', $_REQUEST['basetype']) : '';
$srcs = (isset($_GET['srcs']) && !is_array($_GET['srcs']) && $_GET['srcs'] === 'strength') ? 'strength' : '';
$bagoption = '';
$baseoption = '';
$bag = '';
$strshop = '';
$strbag = '';
$equipment_bag = '';
$gam_equipment = '';
$pimg = '';
$himg = '';
$pneeds = '';
$pmoney = 0;
$heffect = '';
$zbjf_postion_arr = array();
$bsxq_postion_arr = array();

#########################背包的物品 9.19 谭炜###########################3
$strings = ",1,2,3,4,5,6,7,8,9,10|11,12,13,14,15,16";
$strinfo ="全部道具,辅助道具,增益道具,捕捉道具,收集道具,技能书,卡片道具,进化道具,合体道具,装备道具,精练道具,宝箱道具,特殊道具,功能道具,宠物卵,合成道具";
$arr = explode(",",$strings);
$arrinfo = explode(",",$strinfo);
foreach($arr as $ks => $v)
{
	if($bagtype == $v)
	{
		$bagoption .= "<option selected=selected value='./Zb_Mod.php?bagtype=".$v."&basetype=".$basetype." '>".$arrinfo[$ks]."</option>";
	}
	else
	{
		$bagoption .= "<option value='./Zb_Mod.php?bagtype=".$v."&basetype=".$basetype." '>".$arrinfo[$ks]."</option>";
	}
}




//铁匠铺
$basestr = ",4,2,1,3,5|6|7|8|9|10";
$baseinfo = "全部道具,武器,衣服,头盔,鞋子,其他";
if(!isset($basestr)) $basestr = ",4,2,1,3,5|6|7|8|9|10";
$basearr = explode(",",$basestr);
$basearrinfo = explode(",",$baseinfo);
foreach($basearr as $bk => $bv)
{
	if($basetype == $bv)
	{
		$baseoption .= "<option selected=selected value='./Zb_Mod.php?basetype=".$bv."&bagtype=".$bagtype." '>".$basearrinfo[$bk]."</option>";
	}
	else
	{
		$baseoption .= "<option value='./Zb_Mod.php?basetype=".$bv."&bagtype=".$bagtype." '>".$basearrinfo[$bk]."</option>";
	}
}
##########################在这里结束###############################
$shop=false;
if (!is_array($props)) $shop='还没有任何装备物品!';
else
{
	foreach ($props as $k => $rs)
	{
		if(!is_array($rs)) continue;
		$rsDefaults = array('id'=>0, 'name'=>'', 'varyname'=>0, 'postion'=>0, 'buy'=>0, 'yb'=>0, 'prestige'=>0, 'requires'=>'', 'effect'=>'', 'pluseffect'=>'');
		foreach($rsDefaults as $rsDefaultKey => $rsDefaultValue)
		{
			if(!isset($rs[$rsDefaultKey])) $rs[$rsDefaultKey] = $rsDefaultValue;
		}
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
		if (intval($rs['buy'])<=0 || $rs['id']==0 || intval($rs['yb'])>0 || intval($rs['prestige'])>0) continue;
		if ($rs['requires']!='')
		{
			$t = explode(',',
						str_replace(array('lv','wx'), array('等级','五行'),$rs['requires'])
					  );
			$wx = isset($t[1]) ? str_replace($_props['wxs'], $_props['wxd'], $t[1]) : '';
		}
		else $t[0]= $wx= '无';

		// 装备属性显示。
		$zbeffect = zbAttrib($rs['effect']);
		$plus     = ($pzb=zbAttrib($rs['pluseffect']))=='无'?'':'<font color=green>'.$pzb.'</font><br/>';

// 		这一行代码主要用于构建一个表格中的某一行，这一行包含了商品的图片、名称、价格以及其他一些信息
		$nameHtml = zbModHtml($rs['name']);
		$nameJs = zbModHtml(zbModJsSingle($rs['name']));
		$shop .= '<tr>
		<td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
		<td width="90px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left" onmouseover="window.parent.showTipEquip('.$rs['id'].',1,event);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" onclick="copyWord(\''.$nameJs.'\');sel(this);setShopBuyNumDefault();bid='.$rs['id'].';price='.$rs['buy'].';">'.$nameHtml.'</td>
		<td width="40px" style="text-align:left" >' . $rs['buy'] . '</td>
		<td style="text-align:left" >' . zbModHtml($t[0].','.$wx) .'</td>
	 </tr>';
	}
}

//威望装备
$preshop=false;
if (!is_array($props)) $preshop='还没有任何装备物品!';
else
{
	foreach ($props as $k => $rs)
	{
		if(!is_array($rs)) continue;
		$rsDefaults = array('id'=>0, 'name'=>'', 'varyname'=>0, 'postion'=>0, 'buy'=>0, 'yb'=>0, 'prestige'=>0, 'requires'=>'', 'effect'=>'', 'pluseffect'=>'');
		foreach($rsDefaults as $rsDefaultKey => $rsDefaultValue)
		{
			if(!isset($rs[$rsDefaultKey])) $rs[$rsDefaultKey] = $rsDefaultValue;
		}
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
		if (intval($rs['prestige'])<=0 || $rs['id']==0 || intval($rs['yb'])>0 || intval($rs['buy'])>0) continue;
		if ($rs['requires']!='')
		{
			$t = explode(',',
						str_replace(array('lv','wx'), array('等级','五行'),$rs['requires'])
					  );
			$wx = isset($t[1]) ? str_replace($_props['wxs'], $_props['wxd'], $t[1]) : '';
		}
		else $t[0]= $wx= '无';

		// 装备属性显示。
		$zbeffect = zbAttrib($rs['effect']);
		$plus     = ($pzb=zbAttrib($rs['pluseffect']))=='无'?'':'<font color=green>'.$pzb.'</font><br/>';
//测试
		$nameHtml = zbModHtml($rs['name']);
    $preshop .= '<tr>
    <td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
    <td  width="110px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left" onmouseover="window.parent.showTipEquip('.$rs['id'].',1,event);this.style.border=\'solid 1px #DFD496\';"  onmouseout="window.parent.UnTip();this.style.border=0;" onclick="sel(this);setShopBuyNumDefault();bid='.$rs['id'].';price='.$rs['buy'].';prestige='.$rs['prestige'].';">'.$nameHtml.'</td>
    <td width="60px" style="text-align:left">' . $rs['prestige'] . '</td>
    <td style="text-align:left" >' . zbModHtml($t[0].','.$wx) .'</td>
    </tr>';

	}
}

if(empty($preshop)) $preshop='还没有任何装备物品!';

if($shop==false) $shop='还没有任何装备物品!';

$curBagNum=0;
// Get userbag
if (!is_array($userBag)) $bag='您的包裹是空的!';
else
{
	foreach ($userBag as $k => $rs)
	{
		if(!is_array($rs)) continue;
		$rsDefaults = array('id'=>0, 'pid'=>0, 'name'=>'', 'sums'=>0, 'zbing'=>0, 'varyname'=>0, 'requires'=>'', 'sell'=>0, 'postion'=>0, 'effect'=>'');
		foreach($rsDefaults as $rsDefaultKey => $rsDefaultValue)
		{
			if(!isset($rs[$rsDefaultKey])) $rs[$rsDefaultKey] = $rsDefaultValue;
		}

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
			$t = explode(',',
					   str_replace(array('lv','wx'), array('等级','五行'), $rs['requires'])
					  );
			$wx = isset($t[1]) ? str_replace($_props['wxs'], $_props['wxd'], $t[1]) : '';
		}
		else $t[0]= $wx= '无';

		if ($rs['varyname'] == 9) $zbeffect = zbAttrib($rs['effect']) . '<br/>';

		$bag .= '<tr>
		<td width="35px" ><img style="width:25px;height:25px;text-align:left" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
		<td ondblclick="dbclickSell()" width="110px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" onclick="copyWord(\''.zbModHtml(zbModJsSingle($rs['name'])).'\');sel(this);bid='.$rs['id'].';price='.$rs['sell'].';">'.zbModHtml($rs['name']).'</td>
		<td width="60px" style="text-align:left">' . $rs['sell'] . '</td>
		<td style="text-align:left" id="s'.$rs['id'].'" >' . $rs['sums'] .'</td>
	 </tr>';
	}

}



//装备强化
//第一次进入该页面，清空部分SESSION值
$csign = (isset($_REQUEST['csign']) && !is_array($_REQUEST['csign'])) ? $_REQUEST['csign'] : '';
/*if($csign == "first")
{
	$_SESSION['pid'.$_SESSION['id']] = $_REQUEST['pid'];//强化道具
	$_SESSION['bid'.$_SESSION['id']] = $_REQUEST['bid'];
	$_SESSION['pids'.$_SESSION['id']] = $_REQUEST['pids'];//精练道具
}*/
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
	if($selectedPids > 0 || $selectedPid > 0)
	{
		if($selectedPid > 0 && $prop['id'] == $selectedPid)
		{
			if($prop['varyname'] == 9 && $prop['plusflag'] == 1)
			{
				$pimg = zbModImage($prop['img']);
				$nid = $prop['pluspid'];
				foreach($props as $nprop)
				{
					if(!is_array($nprop)) continue;
					if(!isset($nprop['id'])) $nprop['id'] = 0;
					if(!isset($nprop['name'])) $nprop['name'] = '';
					if($nprop['id'] == $nid)
					{
						$pneeds = zbModHtml($nprop['name']);//强化所需要的物品
					}
				}
				//得到用户当前强化的次数
				foreach($userBag as $ub)
				{
					if(!is_array($ub)) continue;
					if(!isset($ub['id'])) $ub['id'] = 0;
					if(!isset($ub['plus_tmes_eft'])) $ub['plus_tmes_eft'] = '';
					if($ub['id'] == $selectedBid)
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
	if($selectedPids > 0)
	{
		if($prop['id'] == $selectedPids)
		{
			if($prop['varyname'] == 11)
			{
				$himg = zbModImage($prop['img']);
				if(!empty($prop['usages']))
				{
					$arr = explode("：",$prop['usages']);
					$heffect = isset($arr[1]) ? zbModHtml($arr[1]) : '';
				}
			}
		}
	}
}
if($selectedPid > 0 && $selectedBid > 0){
	$id = $selectedBid;
	//强化炉
	$strshop .= '<tr>
    <td><div class="eqbox" onmouseover="showTip('.$id.',0,1,2);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" ondblclick="takeoff('.$selectedPid.')"><input name="apid" type="hidden" id="apid" value='.$selectedPid.' /><input name="bid" type="hidden" id="bid" value='.$selectedBid.' /><img src="'.IMAGE_SRC_URL.'/props/'.$pimg.'"></div></td>
    <td class="txt05"> 请加入需要强化装备<br />
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
if($selectedPids > 0){
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

$strCurBagNum=0;
$db_welcome = kdjlSafeMemValue($_pm['mem']->get('db_welcome'), array());
if( !is_array($db_welcome) )
{
	die("memcacheerror");
}
foreach ($db_welcome as $info )
{
	if(!is_array($info) || !isset($info['code']) || !isset($info['contents'])) continue;
	if( $info['code'] == "biodegradable_equipment" )
	{
		$zbjf_postion_str = $info['contents'];
		$zbjf_postion_arr = explode(',',$zbjf_postion_str);
	}
	if( $info['code'] == "allow_to_use_gam" )
	{
		$bsxq_postion_str = $info['contents'];
		$bsxq_postion_arr = explode(',',$bsxq_postion_str);
	}
}
$k = $rs = '';
if (!is_array($userBag))
{
	$strbag='您的包裹是空的!';
	$equipment_bag = '您的包裹是空的!';
}
else
{
	foreach ($userBag as $k => $rs)
	{
		if(!is_array($rs)) continue;
		$rsDefaults = array('id'=>0, 'pid'=>0, 'name'=>'', 'sums'=>0, 'zbing'=>0, 'varyname'=>0, 'requires'=>'', 'sell'=>0, 'postion'=>0, 'effect'=>'');
		foreach($rsDefaults as $rsDefaultKey => $rsDefaultValue)
		{
			if(!isset($rs[$rsDefaultKey])) $rs[$rsDefaultKey] = $rsDefaultValue;
		}
		if ($rs['sums'] < 1 || $rs['id']==0 || $rs['zbing'] == 1) continue;

		##################只显示装备和精练辅助的道具#########################
		$strCurBagNum++;
		if($rs['varyname'] != 9 && $rs['varyname'] != 11 && $rs['varyname'] != 10 && $rs['varyname'] != 25 && $rs['varyname'] != 26 && $rs['varyname'] != 27) continue;
		if (strlen($rs['requires']) > 2)
		{
			$t = explode(',',
					   str_replace(array('lv','wx'), array('等级','五行'), $rs['requires'])
					  );
			$wx = isset($t[1]) ? str_replace($_props['wxs'], $_props['wxd'], $t[1]) : '';
		}
		else {$t[0]= $wx= '无';}
		if ( $rs['varyname'] == 9 && ( in_array($rs['postion'],$zbjf_postion_arr) || in_array($rs['postion'],$bsxq_postion_arr) ) )
		{
			$res_img = isset($propsById[intval($rs['pid'])]) ? $propsById[intval($rs['pid'])] : false;
			if(!is_array($res_img)) $res_img = array('img'=>'', 'plusnum'=>0, 'propscolor'=>0);
			$resImgJs = zbModHtml(zbModJsSingle(zbModImage($res_img['img'])));
			$nameHtml = zbModHtml($rs['name']);
			$zbeffect = zbAttrib($rs['effect']) . '<br/>';
			if( $res_img['plusnum'] > 0 && $res_img['propscolor'] > 0 )
			{
				if( in_array($rs['postion'],$zbjf_postion_arr) )
				{
			$equipment_bag .= '<tr id="tr'.$rs['id'].'" style="display:block">
		<td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
		<td width="110px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';" onMouseDown=div_apear(\''.$resImgJs.'\',\''.$rs['id'].'\');  onmouseout="window.parent.UnTip();this.style.border=0;" onclick="bid='.$rs['id'].';price='.$rs['sell'].';sel(this);pid='.$rs['pid'].';">'.$nameHtml.'</td>
		<td width="60px" style="text-align:left">' . $rs['sell'] . '</td>
		<td style="text-align:left" id="s'.$rs['id'].'" >' . $rs['sums'] .'</td>
	 </tr>';
				}
				if( in_array($rs['postion'],$bsxq_postion_arr) )
				{
			$gam_equipment .= '<tr id="tr_hecheng'.$rs['id'].'" style="display:block"><td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td><td width="110px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';" onmouseout="window.parent.UnTip();this.style.border=0;" onMouseDown="xqhc_apear(\''.$resImgJs.'\',\''.$rs['id'].'\',\''.$rs['varyname'].'\');" onclick="bid='.$rs['id'].';price='.$rs['sell'].';sel(this);pid='.$rs['pid'].';">'.$nameHtml.'</td><td style="text-align:left" id="s'.$rs['id'].'" >' . $rs['sums'] .'</td></tr>';
				}
			}
		}
		if( $rs['varyname'] == 25 || $rs['varyname'] == 26 || $rs['varyname'] == 27)
		{
			$gam_pic = isset($propsById[intval($rs['pid'])]) ? $propsById[intval($rs['pid'])] : false;
			if(!is_array($gam_pic)) $gam_pic = array('img'=>'');
			$gamPicJs = zbModHtml(zbModJsSingle(zbModImage($gam_pic['img'])));
			$gam_equipment .= '<tr id="tr_hecheng'.$rs['id'].'" style="display:block"><td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td><td width="110px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';" onmouseout="window.parent.UnTip();this.style.border=0;" onMouseDown="xqhc_apear(\''.$gamPicJs.'\',\''.$rs['id'].'\',\''.$rs['varyname'].'\');" onclick="bid='.$rs['id'].';price='.$rs['sell'].';sel(this);pid='.$rs['pid'].';">'.zbModHtml($rs['name']).'</td><td style="text-align:left" id="s'.$rs['id'].'" >' . $rs['sums'] .'</td></tr>';

		}
		if( $rs['varyname'] != 25 && $rs['varyname'] != 26 && $rs['varyname'] != 27)
		{
			$strbag .= '<tr>
		<td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
		<td width="110px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" onclick="bid='.$rs['id'].';pid='.$rs['pid'].';sel(this);">'.zbModHtml($rs['name']).'</td>
		<td width="60px" style="text-align:left">' . $rs['sell'] . '</td>
		<td style="text-align:left" id="s'.$rs['id'].'" >' . $rs['sums'] .'</td>
	 </tr>';
		}
	}

}
$zbfjDayStart = strtotime(date('Y-m-d').' 00:00:00');
$zbfjDayEnd = $zbfjDayStart + 86400;
$zbfjUsed = $_pm['mysql']->getOneRecord("SELECT COUNT(*) AS cnt FROM gamelog WHERE seller='".$uid."' AND buyer='".$uid."' AND vary=22 AND ptime>=".$zbfjDayStart." AND ptime<".$zbfjDayEnd);
$syfjnum = is_array($zbfjUsed) && isset($zbfjUsed['cnt']) ? max(0,5-intval($zbfjUsed['cnt'])) : 0;
$taskword = taskcheck($user['task'], 9);
$_pm['mem']->memClose();
unset($u, $m);

//@Load template.
if(empty($bag))
{
	$bag = "您的背包中没有相应的物品！";
}
if(empty($shop))
{
	$shop = "没有相应的装备物品！";
}
if(empty($equipment_bag))
{
	$equipment_bag = "没有可分解的装备！";
}
if(empty($gam_equipment))
{
	$gam_equipment = "没有可镶嵌合成的物品！";
}
$tn = $_game['template'] . 'tpl_zb.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);

	$src = array('#money#',
				 '#prestige#',
				 '#baglimit#',
				 //right attrib.
				 '#shoplist#',
				 '#mybag#',
				 '#word#',
				 '#bagoption#',
				 '#baseoption#',
				 '#preshoplist#',
				 '#strshoplist#',
				 '#strmybag#',
				 '#srcs#',
				 '#equipmentbag#',
				 '#gam_equipment#',
				 '#syfjnum#'

				);
	$des = array($user['money'],
				 $user['prestige'],
				 $curBagNum.'/'.$user['maxbag'],
				 //right part
				 $shop,
				 $bag,
				 $taskword,
				 $bagoption,
				 $baseoption,
				 $preshop,
				 $strshop,
				 $strbag,
				 $srcs,
				 $equipment_bag,
				 $gam_equipment,
				 $syfjnum
				);
	$shop = str_replace($src, $des, $tpl);
}

ob_start();
echo $shop;
ob_end_flush();

?>
