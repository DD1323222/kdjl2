<?php
require_once('../config/config.game.php');
$m = $_pm['user'];
secStart($_pm['mem']);

$u = $_pm['mem'];


$user		= $m->getUserById($_SESSION['id']);
$props		= unserialize($u->get(MEM_PROPS_KEY));
$userBag	= $m->getUserBagById($_SESSION['id']);
$type = $_REQUEST['type'];
$bagtype = $_REQUEST['bagtype'];
//家族

$guild = $_pm['mysql'] -> getRecords('SELECT guild.id as gid,guild.name as gname,president_id,honor,level,number_of_member,player.nickname FROM guild,player WHERE player.id = guild.president_id ORDER BY honor DESC');

if(!is_array($guild)){
	$guildstr = '<tr>
              <td height="23" colspan="5" align="center">暂时没有家族</td>
            </tr>';
}else{
	foreach($guild as $v){
		$guildstr .= ' 
					<tr>
              <td width="20%" height="24" style="cursor:pointer" onclick="guild_id='.$v['gid'].';show_guild_info('.$v['gid'].')" align="center" onmouseover="this.style.color=\'#ff0000\'" onmouseout="this.style.color=\'#600\'">'.$v['gname'].'</td>
              <td width="20%" height="24" align="center">'.$v['nickname'].'</td>
              <td width="20%" height="24" align="center">'.$v['honor'].'</td>
              <td width="20%" height="24" align="center">'.$v['level'].'</td>
              <td width="20%" height="24" align="center">'.$v['number_of_member'].'</td>
            </tr>';
	}
	
}

//家族商店
$member = "SELECT guild_id,contribution,honor FROM guild_members where member_id={$_SESSION['id']}";
$member_eve = $_pm['mysql']->getOneRecord($member);
$guild_level = "SELECT shop_level FROM guild where id={$member_eve['guild_id']}";
$level_query = $_pm['mysql']->getOneRecord($guild_level);
$user['shop_level'] = $level_query['shop_level'];

if (!is_array($props)) $shop='还没有任何商品!';
else
{
	$sql = "SELECT * FROM props WHERE (contribution > 0 or honor > 0) and guild_level<={$user['shop_level']}";
	$props = $_pm['mysql']->getRecords($sql);
}

if (!is_array($props)) $shop='没有相应的家族商品!';
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
		if ($rs['id'] ==0 || intval($rs['buy'])>0) continue;//buy大于0表示道具商店的

		$shop .= '<tr>
		<td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
                        <td width="110px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left" onmouseover="window.parent.showTipEquip('.$rs['id'].',1,window.event);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" onclick="copyWord(\''.$rs[name].'\');sel(this);bid='.($rs['id']?$rs['id']:0).';price1='.$rs['honor'].';price2='.$rs['contribution'].';">'.$rs['name'].'</td>
                        <td width="60px" style="text-align:left">' . $rs['honor'] . '</td>
                        <td style="text-align:left">' . $rs['contribution'] .'</td>
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
		$bagoption .= "<option selected=selected value='./Family_Mod.php?bagtype=".$v."&type=".$type." '>".$arrinfo[$ks]."</option>";
	}
	else
	{
		$bagoption .= "<option value='./Family_Mod.php?bagtype=".$v."&type=".$type." '>".$arrinfo[$ks]."</option>";
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


//@Load template.
if(empty($bag))
{
	$bag = "您没有相应的道具！";
}


$sql = "SELECT guild.id,guild_settings.level,need_honor,need_props,need_member_number FROM guild_settings,guild_members,guild WHERE guild_settings.level = guild.level AND guild_members.member_id = {$_SESSION['id']} AND guild_members.guild_id = guild.id";
$arr = $_pm['mysql'] -> getOneRecord($sql);
if(!is_array($arr)){
	$guild_level_info='您没有加入任何家族！';
}else{
	$props	= unserialize($_pm['mem']->get('db_propsid'));
	$guild_level_info = '升级到<font color=red> '.($arr['level']+1).' </font>级<br />需要荣誉：'.$arr['need_honor'].'<br />需要成员数：'.$arr['need_member_number'].'<br />需要物品：<br />';
	$new_arr = explode(',',$arr['need_props']);
	foreach($new_arr as $v){
		$a = explode('|',$v);
		$have_props = $_pm['mysql'] -> getOneRecord("SELECT sums FROM guild_bag WHERE pid = $a[0] AND guild_id = {$arr['id']}");
		if(!is_array($have_props)){
			$have_props['sums'] = 0;
		}
		$guild_level_info .= $props[$a[0]]['name'].'&nbsp;'.$have_props['sums'].'/'.$a[1].'个<br />';
	}
}
//echo $guildstr;exit;
$taskword= taskcheck($user['task'],6);
$tn = $_game['template'] . 'tpl_family.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);
	
	$src = array('#guild#',
				'#word#',
				'#honor#',
			    '#contribution#',
				 '#baglimit#',
				 '#shoplist#',
				 '#mybag#',
				 '#bagoption#',
				 '#guild_level_info#',
				 '#guild_shop_level#'
				);
	$des = array($guildstr,
				$taskword,
				$member_eve['honor'],
				 $member_eve['contribution'],
				 $curBagNum.'/'.$user['maxbag'],
				 $shop,
				 $bag,
				 $bagoption,
				 $guild_level_info,
				 $level_query['shop_level']
				);
	$shop = str_replace($src, $des, $tpl);
}

// gzip echo. if maybe.
ob_start('ob_gzip');
echo $shop;
ob_end_flush();
?>