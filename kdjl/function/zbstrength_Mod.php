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

$user		= $_pm['user']->getUserById($_SESSION['id']);
$props		= unserialize($_pm['mem']->get(MEM_PROPS_KEY));
$userBag	= $_pm['user']->getUserBagById($_SESSION['id']);
//第一次进入该页面，清空部分SESSION值
$csign = $_REQUEST['csign'];
if($csign == "first")
{
	$_SESSION['pid'.$_SESSION['id']] = $_REQUEST['pid'];//强化道具
	$_SESSION['bid'.$_SESSION['id']] = $_REQUEST['bid'];
	$_SESSION['pids'.$_SESSION['id']] = $_REQUEST['pids'];//精练道具
}
if(!empty($_REQUEST['pid']) && !empty($_REQUEST['bid']))
{
	$_SESSION['pid'.$_SESSION['id']] = $_REQUEST['pid'];//强化道具
	$_SESSION['bid'.$_SESSION['id']] = $_REQUEST['bid'];
}
if(!empty($_REQUEST['pids']))
{
	$_SESSION['pids'.$_SESSION['id']] = $_REQUEST['pids'];//精练道具
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
/*<tr>
    <td><div class="eqbox"></div></td>
    <td class="txt05"> 请加入需要强化装备
  需要材料：<br />
  花费金币：<br /></td>
  </tr>*/
if(!empty($_SESSION['pid'.$_SESSION['id']])){
	//强化炉
	$id = $_SESSION['bid'.$_SESSION['id']];
	$strshop .= '<tr>
    <td><div class="eqbox" onmouseover="showTip('.$id.',0,1,2);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" ondblclick="takeoff('.$_SESSION['pid'.$_SESSION['id']].')"><input name="pid" type="hidden" id="pid" value='.$_SESSION['pid'.$_SESSION['id']].' /><img src="'.IMAGE_SRC_URL.'/props/'.$pimg.'"></div></td>
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
		if ($rs['sums'] < 1 || $rs['id']==0 || $rs['zbing'] == 1) continue;
		
		##################只显示装备和精练辅助的道具#########################
		$strCurBagNum++;
		if($rs['varyname'] != 9 && $rs['varyname'] != 11 && $rs['varyname'] != 10) continue;
		if (strlen($rs['requires']) > 2) 
		{
			$t = split(',', 
					   str_replace(array('lv','wx'), array('等级','五行'), $rs['requires'])
					  );
			$wx = str_replace($_props['wxs'], $_props['wxd'], $t[1]);
		}
		else $t[0]= $wx= '无';
		
		if ($rs['varyname'] == 9) $zbeffect = zbAttrib($rs['effect']) . '<br/>';
		
		$strbag .= '<tr>
              		<td width="18%%" id="t'.$rs['id'].'" style="cursor:pointer;" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" onclick="sel(this);bid='.$rs['id'].';price='.$rs['sell'].';pid='.$rs['pid'].';">'.$rs['name'].'</td>
              		<td width="18%" >' . $rs['sell'] . '</td>
              		<td width="18%%" id="s'.$rs['id'].'" >' . $rs['sums'] .'</td>
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
				 '#strshoplist#',
				 '#strmybag#',
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