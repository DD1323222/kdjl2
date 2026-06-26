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
		if ($rs['buy']==0 || $rs['id']==0 || intval($rs['yb'])>0 ) continue;
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
		
// 		这一行代码主要用于构建一个表格中的某一行，这一行包含了商品的图片、名称、价格以及其他一些信息
		$shop .= '<tr>
		<td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
              		<td width="90px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left" onmouseover="window.parent.showTipEquip('.$rs['id'].',1,event);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" onclick="copyWord(\''.$rs[name].'\');sel(this);bid='.$rs['id'].';price='.$rs['buy'].';">'.$rs['name'].'</td>
              		<td width="40px" style="text-align:left" >' . $rs['buy'] . '</td>
              		<td style="text-align:left" >' . $t[0].','.$wx .'</td>
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
//测试		
    $preshop .= '<tr>  
    <td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>  
    <td  width="110px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left" onmouseover="window.parent.showTipEquip('.$rs['id'].',1,event);this.style.border=\'solid 1px #DFD496\';"  onmouseout="window.parent.UnTip();this.style.border=0;" onclick="sel(this);bid='.$rs['id'].';price='.$rs['buy'].';prestige='.$rs['prestige'].';">'.$rs['name'].'</td>  
    <td width="60px" style="text-align:left">' . $rs['prestige'] . '</td>  
    <td style="text-align:left" >' . $t[0].','.$wx .'</td>  
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
		<td width="35px" ><img style="width:25px;height:25px;text-align:left" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
              		<td ondblclick="dbclickSell()" width="110px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" onclick="copyWord(\''.$rs[name].'\');sel(this);bid='.$rs['id'].';price='.$rs['sell'].';">'.$rs['name'].'</td>
              		<td width="60px" style="text-align:left">' . $rs['sell'] . '</td>
              		<td style="text-align:left" id="s'.$rs['id'].'" >' . $rs['sums'] .'</td>
            	 </tr>';
	}
	
}



//装备强化
//第一次进入该页面，清空部分SESSION值
$csign = $_REQUEST['csign'];
/*if($csign == "first")
{
	$_SESSION['pid'.$_SESSION['id']] = $_REQUEST['pid'];//强化道具
	$_SESSION['bid'.$_SESSION['id']] = $_REQUEST['bid'];
	$_SESSION['pids'.$_SESSION['id']] = $_REQUEST['pids'];//精练道具
}*/
if(!empty($_REQUEST['pid']) && !empty($_REQUEST['bid']))
{
	
	$_SESSION['pid'.$_SESSION['id']] = $_REQUEST['pid'];//强化道具
	$_SESSION['bid'.$_SESSION['id']] = $_REQUEST['bid'];	
	
}

if(!empty($_REQUEST['pids']))
{
	$_SESSION['pids'.$_SESSION['id']] = $_REQUEST['pids'];//精练道具
	echo '<!--pre>-------------------------------------------------
	';
	var_dump($_SESSION,$_REQUEST);
	echo '
	</pre-->';
}


foreach($props as $prop)
{
	//要强化的道具
	if(!empty($_SESSION['pids'.$_SESSION['id']]) || !empty($_SESSION['pid'.$_SESSION['id']]))
	{
		if($prop['id'] == $_SESSION['pid'.$_SESSION['id']])
		{
			if($prop['varyname'] == 9 && $prop['plusflag'] == 1)
			{
				$pimg = $prop['img'];
				$nid = $prop['pluspid'];
				foreach($props as $nprop)
				{
					if($nprop['id'] == $nid)
					{
						$pneeds = $nprop['name'];//强化所需要的物品
					}
				}
				//得到用户当前强化的次数
				foreach($userBag as $ub)
				{
					if($ub['id'] == $_SESSION['bid'.$_SESSION['id']])
					{
						$plus_tms_eft = $ub['plus_tmes_eft'];
						//在这之前强化过
						if(!empty($plus_tms_eft))
						{
							$plusarr = explode(",",$plus_tms_eft);
							foreach($harden as $kh => $har)
							{
								$num = $kh + 1;
								if($kh == $plusarr[0])
								{
									$eff = explode(",",$harden[$num]);
									$pmoney = $eff[1];
								}
							}
						}
						else
						{
							$eff = explode(",",$harden[0]);
							$pmoney = $eff[1];
						}
					}
				} //end foreach
			}
		}
	}
	//精练道具
	if(!empty($_SESSION['pids'.$_SESSION['id']]))
	{
		if($prop['id'] == $_SESSION['pids'.$_SESSION['id']])
		{
			if($prop['varyname'] == 11)
			{
				$himg = $prop['img'];
				if(!empty($prop['usages']))
				{
					$arr = explode("：",$prop['usages']);
					$heffect = $arr[1];
				}
			}
		}
	}
}
if(!empty($_SESSION['pid'.$_SESSION['id']])){
	//强化炉
	$id = $_SESSION['bid'.$_SESSION['id']];
	$strshop .= '<tr>
    <td><div class="eqbox" onmouseover="showTip('.$id.',0,1,2);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" ondblclick="takeoff('.$_SESSION['pid'.$_SESSION['id']].')"><input name="apid" type="hidden" id="apid" value='.$_SESSION['pid'.$_SESSION['id']].' /><input name="bid" type="hidden" id="bid" value='.$_SESSION['bid'.$_SESSION['id']].' /><img src="'.IMAGE_SRC_URL.'/props/'.$pimg.'"></div></td>
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
if(!empty($_SESSION['pids'.$_SESSION['id']])){
	$strshop .= '<tr>
    <td><div class="eqbox" onmouseover="showTip('.$_SESSION['pids'.$_SESSION['id']].');this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" ondblclick="takeoff('.$_SESSION['pids'.$_SESSION['id']].')"><input name="pids" type="hidden" id="pids" value='.$_SESSION['pids'.$_SESSION['id']].' /><img src="'.IMAGE_SRC_URL.'/props/'.$himg.'"></div></td>
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
$db_welcome = unserialize($_pm['mem']->get('db_welcome'));
if( !is_array($db_welcome) )
{
	die("memcacheerror");
}
foreach ($db_welcome as $info )
{
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
		if ($rs['sums'] < 1 || $rs['id']==0 || $rs['zbing'] == 1) continue;
		
		##################只显示装备和精练辅助的道具#########################
		$strCurBagNum++;
		if($rs['varyname'] != 9 && $rs['varyname'] != 11 && $rs['varyname'] != 10 && $rs['varyname'] != 25 && $rs['varyname'] != 26 && $rs['varyname'] != 27) continue;
		if (strlen($rs['requires']) > 2) 
		{
			$t = split(',', 
					   str_replace(array('lv','wx'), array('等级','五行'), $rs['requires'])
					  );
			$wx = str_replace($_props['wxs'], $_props['wxd'], $t[1]);
		}
		else {$t[0]= $wx= '无';}
		if ( $rs['varyname'] == 9 && ( in_array($rs['postion'],$zbjf_postion_arr) || in_array($rs['postion'],$bsxq_postion_arr) ) )
		{	
			$sql = " SELECT img,plusnum,propscolor FROM props WHERE id=".$rs['pid'];
			$res_img = $_pm['mysql']->getOneRecord($sql);
			$zbeffect = zbAttrib($rs['effect']) . '<br/>';
			if( $res_img['plusnum'] > 0 && $res_img['propscolor'] > 0 )
			{
				if( in_array($rs['postion'],$zbjf_postion_arr) )
				{
			$equipment_bag .= '<tr id="tr'.$rs['id'].'" style="display:block">
		<td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
              		<td width="110px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';" onMouseDown=div_apear(\''.$res_img['img'].'\',\''.$rs['id'].'\');  onmouseout="window.parent.UnTip();this.style.border=0;" onclick="bid='.$rs['id'].';price='.$rs['sell'].';sel(this);pid='.$rs['pid'].';">'.$rs['name'].'</td>
              		<td width="60px" style="text-align:left">' . $rs['sell'] . '</td>
              		<td style="text-align:left" id="s'.$rs['id'].'" >' . $rs['sums'] .'</td>
            	 </tr>';
				}
				if( in_array($rs['postion'],$bsxq_postion_arr) )
				{
			$gam_equipment .= '<tr id="tr_hecheng'.$rs['id'].'" style="display:block"><td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td><td width="110px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';";  onmouseout="window.parent.UnTip();this.style.border=0;" onMouseDown=xqhc_apear(\''.$res_img['img'].'\',\''.$rs['id'].'\',\''.$rs['varyname'].'\'); onclick="bid='.$rs['id'].';price='.$rs['sell'].';sel(this);pid='.$rs['pid'].';">'.$rs['name'].'</td><td style="text-align:left" id="s'.$rs['id'].'" >' . $rs['sums'] .'</td>';
				}
			}
		}
		if( $rs['varyname'] == 25 || $rs['varyname'] == 26 || $rs['varyname'] == 27)
		{
			$sql = " SELECT img FROM props WHERE id=".$rs['pid']." AND ( varyname = 25 OR varyname = 26 OR varyname = 27 ) ";
			$gam_pic = $_pm['mysql']->getOneRecord($sql);
			$gam_equipment .= '<tr id="tr_hecheng'.$rs['id'].'" style="display:block"><td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td><td width="110px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';";  onmouseout="window.parent.UnTip();this.style.border=0;" onMouseDown=xqhc_apear(\''.$gam_pic['img'].'\',\''.$rs['id'].'\',\''.$rs['varyname'].'\'); onclick="bid='.$rs['id'].';price='.$rs['sell'].';sel(this);pid='.$rs['pid'].';">'.$rs['name'].'</td><td style="text-align:left" id="s'.$rs['id'].'" >' . $rs['sums'] .'</td>';
			
		}
		if( $rs['varyname'] != 25 && $rs['varyname'] != 26 && $rs['varyname'] != 27)
		{
			$strbag .= '<tr>
		<td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
              		<td width="110px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" onclick="bid='.$rs['id'].';pid='.$rs['pid'].';sel(this);">'.$rs['name'].'</td>
              		<td width="60px" style="text-align:left">' . $rs['sell'] . '</td>
              		<td style="text-align:left" id="s'.$rs['id'].'" >' . $rs['sums'] .'</td>
            	 </tr>';
		}
	}
	
}
$zbfj_info = unserialize($_pm['mem'] -> get('zbfj_info'));
$syfjnum = 5;
if( is_array($zbfj_info) )
{
	foreach( $zbfj_info as $key => $val )
	{
		if( $key == 'FJ_NUM:'.$_SESSION['id'] )
		{
			$syfjnum = $val;
		}
	}
}
else
{
	$syfjnum = 5;
}
//$taskword= taskcheck($user['task'], 9);
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
				 $_GET['srcs'],
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