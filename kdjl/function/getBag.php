<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.05.22
*@Usage: Shop main ui
*@Note: none

1为可交易
2为不可交易
3为不可交易不可丢弃
0为以道具表的交易属性为准
*/

require_once('../config/config.game.php');
secStart($_pm['mem']);
$user		= $_pm['user']->getUserById($_SESSION['id']);
$userBag	= $_pm['user']->getUserBagById($_SESSION['id']);
$_pm['mem']->memClose();
$bagtype = $_REQUEST['bagtype'];
//$clean = $_REQUEST['clean'];
$clean = 1;

if($clean == 1)
{
	if(is_array($userBag))
	{
		foreach($userBag as $key => $value)
		{
			$array[$value['varyname']][] = $value;
		}
	}
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
		$bagoption .= "<option selected=selected value='./getBag_Mod.php?bagtype=".$v."'>".$arrinfo[$ks]."</option>";
	}
	else
	{
		$bagoption .= "<option value='./paiProps_Mod.php?getBag=".$v."'>".$arrinfo[$ks]."</option>";
	}
}
##########################在这里结束###############################
/**
* Delete userbag for novalid.
*/
$_pm['mysql']->query("DELETE FROM userbag
				       WHERE sums=0 and bsum=0 and psum=0 and uid={$_SESSION['id']}
					");

$bagUsedCellCT=0;
// Get userbag
$bag='<div class="pack_cont"><ul class="list l1 clearfix">';
if (!is_array($userBag)) $bag .= '还没有任何商品!';
else if($_REQUEST['style']=='3'){
	if (!is_array($userBag)) $bag = '还没有任何物品!';
	else
	{
		foreach ($userBag as $k => $rs)
		{
			if ($rs['sums'] < 1 || 
				$rs['id']==0 || 
				$rs['zbing'] == 1) continue;
			
			if (strlen($rs['requires'])>2) 
			{
				$t = split(',', 
						   str_replace(array('lv','wx'), array('等级','五行'), $rs['requires'])
						  );
				$wx = str_replace($_props['wxs'], $_props['wxd'], $t[1]);
			}
			else $t[0]= $wx= '无';
			$bag .= '
			
 <table class="tit01" id="mybag1">
<tr>
			<td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
						<td width="110px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" onclick="sel(this);copyWord(\''.$rs['name'].'\');bid='.$rs['id'].';price='.$rs['sell'].';">'.$rs['name'].'</td>
						<td width="60px" style="text-align:left">' . $rs['sell'] . '</td>
						<td style="text-align:left" id="s'.$rs['id'].'" >' . $rs['sums'] .'</td>
					 </tr>
					 </table>';
		}
	}
	exit($bag);
}else{
	if($clean == 1)
	{
		foreach ($array as $k_1 => $rs_1)
		{
			foreach($rs_1 as $k_2 => $rs)
			{
				if ($rs['sums'] < 1 || $rs['zbing']==1) continue;
				if ($rs['sums'] < 1 || $rs['id']==0) continue;
				$bagUsedCellCT++;
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
					$t = split(',', str_replace(array('lv','wx'),array('等级','五行'),$rs['requires']));
					$wx = str_replace($_props['wxs'],$_props['wxd'],$t[1]);
				}
				else $t[0]=$wx='无';
				if ($rs['varyname'] == 9 && strlen($rs['effect'])>1)
				{
					$effect = zbAttrib($rs['effect']);
				}else $effect='';
		
				if ($_REQUEST['style']==1)
				{
						 $bag .= '<li><a >
							<p class="p1" onDblClick="Used();"><img src="../images/ui/bag/'.$rs['varyname'].'.gif" /></p>
							<p class="p2" id="t'.$rs['id'].'" onmouseover="showTip2('.$rs['id'].',0,1,2);" onDblClick="myContextMenu('."'".$rs['name']."'".','.$rs['id'].')" onmouseout="UnBagTip2();" onclick="sel(this);bid='.$rs['id'].';price='.$rs['sell'].';copyWorda(\''.$rs['name'].'\');" style="text-align:left;">'.$rs['name'].'</p>
							<p class="p3">' . $_props['varyname'][$rs['varyname']] . '</p>
							<p class="p4">' . $rs['sums'] .'</p>
						 </a></li>';
				}
				else
				{
					$bag .= '<li><a >
							<p class="p1"><img src="../images/ui/bag/'.$rs['varyname'].'.gif" /></p>
							<p class="p2" id="t'.$rs['id'].'" onDblClick="myContextMenu('."'".$rs['name']."'".','.$rs['id'].')"  onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" onclick="sel(this);bid='.$rs['id'].';price='.$rs['sell'].';copyWorda(\''.$rs['name'].'\')" style="text-align:left">'.$rs['name'].'</p>
							<p class="p3">' . $rs['sell'] . '</p>
							<p class="p4" id=s'.$rs['id'].'>' . $rs['sums'] .'</p>
						 </a><li>';
				}
			}
		}
	}
	else
	{
		foreach ($userBag as $k => $rs)
		{
			if ($rs['sums'] < 1 || $rs['zbing']==1) continue;
			if ($rs['sums'] < 1 || $rs['id']==0) continue;
			$bagUsedCellCT++;
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
				$t = split(',', str_replace(array('lv','wx'),array('等级','五行'),$rs['requires']));
				$wx = str_replace($_props['wxs'],$_props['wxd'],$t[1]);
			}
			else $t[0]=$wx='无';
			if ($rs['varyname'] == 9 && strlen($rs['effect'])>1)
			{
				$effect = zbAttrib($rs['effect']);
			}else $effect='';
	
			if ($_REQUEST['style']==1)
			{
					 $bag .= '<li><a >
						<p class="p1" onDblClick="Used();"><img src="../images/ui/bag/'.$rs['varyname'].'.gif" /></p>
						<p class="p2" id="t'.$rs['id'].'" onmouseover="showTip2('.$rs['id'].',0,1,2)" onDblClick="myContextMenu('."'".$rs['name']."'".','.$rs['id'].')" onmouseout="UnBagTip2()" onclick="sel(this);bid='.$rs['id'].';price='.$rs['sell'].';copyWorda('."'".$rs['name']."'".');" style="text-align:left;">'.$rs['name'].'</p>
						<p class="p3">' . $_props['varyname'][$rs['varyname']] . '</p>
						<p class="p4">' . $rs['sums'] .'</p>
					 </a></li>';
			}
			else
			{
				$bag .= '<li><a >
						<p class="p1"><img src="../images/ui/bag/'.$rs['varyname'].'.gif" /></p>
						<p class="p2" id="t'.$rs['id'].'"  onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" onclick="sel(this);bid='.$rs['id'].';price='.$rs['sell'].';copyWorda(\''.$rs['name'].'\')" style="text-align:left">'.$rs['name'].'</p>
						<p class="p3">' . $rs['sell'] . '</p>
						<p class="p4" id=s'.$rs['id'].'>' . $rs['sums'] .'</p>
					 </a><li>';
			}
		}
	}
}
if ($bag == '' || $bag == false) $bag = '您的包裹是空的！';
$bagInfo = $bagUsedCellCT.'/'.$user['maxbag'];
if ($_REQUEST['style']==1)
{
	$bag = '<div class="close_btn" onclick="ShowBox(\'Tools\',\'1\',\'3\')"></div><div class="i_pack">当前背包空间：'.$bagInfo.'</div>'.' <div class="pack_title">
        	<ul class="list l1"><li><p class="p1">图标</p><p class="p2">物品名称</p><p class="p3">类型</p><p class="p4">数量</p></li></ul>
        </div>'.$bag.' </ul>
        </div>
		        <div class="pac_btn">
        	<input type="button" class="ico_btn" value="使用" id="inused" onclick="Used();"/>
          <input type="button" id="incangku" class="ico_btn" value="放入仓库" onclick="putBagProps2Depot();"/>
          <input type="button" class="ico_btn" value="丢弃" onclick="dropBagProps();"/>
          <input type="button" class="ico_btn" value="整理" onclick="Clean();" />
        </div>
          <input type="button" id="incangku" style="float:left; margin:30px 0px 0px 35px; width:59px; height:17px; *height:17px; *padding-top:2px; color:#772200; border:none; background:url(../new_images/index/ico_btn.gif); cursor:pointer" class="allSameIn" value="秒放仓库" onclick="putBagPropsAllSameIn();"/>
		';
}
else
{
	$bag = '<table width="93%" border="0" cellspacing="0" cellpadding="2" background="#ffffff">'.$bag.'</table>';
}

//<iframe   src='javascript:false'   style='Z-INDEX:-1; FILTER:progid:DXImageTransform.Microsoft.Alpha(style=0,opacity=0);   LEFT:0px;   VISIBILITY:inherit;   WIDTH:90%;   POSITION:absolute;   TOP:0px;   HEIGHT:290px'>    </iframe>  > 
ob_start('ob_gzip');
echo $bag;
ob_end_flush()
?>

