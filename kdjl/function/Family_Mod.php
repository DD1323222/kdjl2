<?php
require_once('../config/config.game.php');
$m = $_pm['user'];
secStart($_pm['mem']);

$u = $_pm['mem'];
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');

function familyModHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function familyModJsSingle($value)
{
	$value = str_replace("\\", "\\\\", (string)$value);
	$value = str_replace("'", "\\'", $value);
	$value = str_replace(array("\r", "\n"), array("\\r", "\\n"), $value);
	return $value;
}

$user		= $m->getUserById($uid);
$props		= kdjlSafeMemValue($u->get(MEM_PROPS_KEY), array());
$userBag	= $m->getUserBagById($uid);
if(!is_array($user)) $user = array();
if(!is_array($userBag)) $userBag = array();
if(!is_array($props)) $props = array();
$userDefaults = array('maxbag'=>0, 'task'=>0);
foreach($userDefaults as $userDefaultKey => $userDefaultValue)
{
	if(!isset($user[$userDefaultKey])) $user[$userDefaultKey] = $userDefaultValue;
}
$type = (isset($_REQUEST['type']) && !is_array($_REQUEST['type'])) ? preg_replace('/[^0-9,|]/', '', $_REQUEST['type']) : '';
$bagtype = (isset($_REQUEST['bagtype']) && !is_array($_REQUEST['bagtype'])) ? preg_replace('/[^0-9,|]/', '', $_REQUEST['bagtype']) : '';
$guildstr = '';
$shop = '';
$bag = '';
$bagoption = '';
//家族

$guild = $_pm['mysql'] -> getRecords('SELECT guild.id as gid,guild.name as gname,president_id,honor,level,number_of_member,player.nickname FROM guild,player WHERE player.id = guild.president_id ORDER BY honor DESC');

if(!is_array($guild)){
	$guildstr = '<tr>
              <td height="23" colspan="5" align="center">暂时没有家族</td>
            </tr>';
}else{
	foreach($guild as $v){
		if(!is_array($v)) continue;
		$vDefaults = array('gid'=>0, 'gname'=>'', 'nickname'=>'', 'honor'=>0, 'level'=>0, 'number_of_member'=>0);
		foreach($vDefaults as $vDefaultKey => $vDefaultValue)
		{
			if(!isset($v[$vDefaultKey])) $v[$vDefaultKey] = $vDefaultValue;
		}
		$guildId = intval($v['gid']);
		$guildHonor = intval($v['honor']);
		$guildLevel = intval($v['level']);
		$guildMembers = intval($v['number_of_member']);
		$guildstr .= '
					<tr>
              <td width="20%" height="24" style="cursor:pointer" onclick="guild_id='.$guildId.';show_guild_info('.$guildId.')" align="center" onmouseover="this.style.color=\'#ff0000\'" onmouseout="this.style.color=\'#600\'">'.familyModHtml($v['gname']).'</td>
              <td width="20%" height="24" align="center">'.familyModHtml($v['nickname']).'</td>
              <td width="20%" height="24" align="center">'.$guildHonor.'</td>
              <td width="20%" height="24" align="center">'.$guildLevel.'</td>
              <td width="20%" height="24" align="center">'.$guildMembers.'</td>
            </tr>';
	}

}

//家族商店
$member = "SELECT guild_id,contribution,honor FROM guild_members where member_id={$uid}";
$member_eve = $_pm['mysql']->getOneRecord($member);
$level_query = array('shop_level' => 0);
$user['shop_level'] = 0;
if(is_array($member_eve))
{
	$member_eve['guild_id'] = isset($member_eve['guild_id']) ? intval($member_eve['guild_id']) : 0;
	$member_eve['honor'] = isset($member_eve['honor']) ? intval($member_eve['honor']) : 0;
	$member_eve['contribution'] = isset($member_eve['contribution']) ? intval($member_eve['contribution']) : 0;
	$guild_level = "SELECT shop_level FROM guild where id={$member_eve['guild_id']}";
	$level_query = $_pm['mysql']->getOneRecord($guild_level);
	if(is_array($level_query)) $user['shop_level'] = intval($level_query['shop_level']);
}
else
{
	$member_eve = array('guild_id' => 0, 'contribution' => 0, 'honor' => 0);
}
if(!is_array($level_query)) $level_query = array('shop_level' => 0);
$level_query['shop_level'] = isset($level_query['shop_level']) ? intval($level_query['shop_level']) : 0;

if ($member_eve['guild_id'] < 1) $shop='您没有加入任何家族！';
else if (!is_array($props)) $shop='还没有任何商品!';
else
{
	$sql = "SELECT * FROM props WHERE (contribution > 0 OR honor > 0)
		AND buy=0 AND vary IN(1,2) AND guild_level<=".intval($user['shop_level']);
	$props = $_pm['mysql']->getRecords($sql);
}

if ($member_eve['guild_id'] < 1) $props = array();
else if (!is_array($props)) $shop='没有相应的家族商品!';
else
{
	foreach ($props as $k => $rs)
	{
		if(!is_array($rs)) continue;
		$rs['id'] = isset($rs['id']) ? intval($rs['id']) : 0;
		$rs['buy'] = isset($rs['buy']) ? intval($rs['buy']) : 0;
		$rs['varyname'] = isset($rs['varyname']) ? intval($rs['varyname']) : 0;
		$rs['name'] = isset($rs['name']) ? $rs['name'] : '';
		$rs['honor'] = isset($rs['honor']) ? intval($rs['honor']) : 0;
		$rs['contribution'] = isset($rs['contribution']) ? intval($rs['contribution']) : 0;

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

		$nameHtml = familyModHtml($rs['name']);
		$nameJs = familyModHtml(familyModJsSingle($rs['name']));
		$shop .= '<tr>
		<td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
                        <td width="110px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left" onmouseover="window.parent.showTipEquip('.$rs['id'].',1,window.event);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" onclick="copyWord(\''.$nameJs.'\');sel(this,true);bid='.($rs['id']?$rs['id']:0).';price1='.$rs['honor'].';price2='.$rs['contribution'].';">'.$nameHtml.'</td>
                        <td width="60px" style="text-align:left">' . $rs['honor'] . '</td>
                        <td style="text-align:left">' . $rs['contribution'] .'</td>
                     </tr>';
	}

}


$curBagNum = 0;
$bagShowNum = 0;
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
		if(!is_array($rs)) continue;
		$rs['sums'] = isset($rs['sums']) ? intval($rs['sums']) : 0;
		$rs['id'] = isset($rs['id']) ? intval($rs['id']) : 0;
		$rs['zbing'] = isset($rs['zbing']) ? intval($rs['zbing']) : 0;
		$rs['varyname'] = isset($rs['varyname']) ? intval($rs['varyname']) : 0;
		$rs['requires'] = isset($rs['requires']) ? $rs['requires'] : '';
		$rs['name'] = isset($rs['name']) ? $rs['name'] : '';
		$rs['sell'] = isset($rs['sell']) ? intval($rs['sell']) : 0;
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
			$t = explode(',',
					   str_replace(array('lv','wx'), array('等级','五行'), $rs['requires'])
					  );
			$wx = isset($t[1]) ? str_replace($_props['wxs'], $_props['wxd'], $t[1]) : '';
		}
		else $t[0]= $wx= '无';
		$nameHtml = familyModHtml($rs['name']);
		$nameJs = familyModHtml(familyModJsSingle($rs['name']));
		$bag .= '<tr>
		<td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
		<td width="110px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';"   onmouseout="window.parent.UnTip();this.style.border=0;" onclick="sel(this,false);copyWord(\''.$nameJs.'\');bid='.$rs['id'].';price='.$rs['sell'].';">'.$nameHtml.'</td>
		<td width="60px" style="text-align:left">' . $rs['sell'] . '</td>
		<td style="text-align:left" id="s'.$rs['id'].'" >' . $rs['sums'] .'</td>
			</tr>';
		$curBagNum++;
		$bagShowNum++;
	}
}

if($bagShowNum < 1) $bag = '';


//@Load template.
if(empty($bag))
{
	$bag = "您没有相应的道具！";
}
if(empty($shop))
{
	$shop = "没有相应的家族商品!";
}
if(empty($guildstr))
{
	$guildstr = '<tr><td height="23" colspan="5" align="center">暂时没有家族</td></tr>';
}

$sql = "SELECT guild.id,guild_settings.level,need_honor,need_props,need_member_number FROM guild_settings,guild_members,guild WHERE guild_settings.level = guild.level AND guild_members.member_id = {$uid} AND guild_members.guild_id = guild.id";
$arr = $_pm['mysql'] -> getOneRecord($sql);
if(!is_array($arr)){
	$guild_level_info='您没有加入任何家族！';
}else{
	$arr['id'] = isset($arr['id']) ? intval($arr['id']) : 0;
	$arr['level'] = isset($arr['level']) ? intval($arr['level']) : 0;
	$arr['need_honor'] = isset($arr['need_honor']) ? $arr['need_honor'] : 0;
	$arr['need_props'] = isset($arr['need_props']) ? $arr['need_props'] : '';
	$arr['need_member_number'] = isset($arr['need_member_number']) ? $arr['need_member_number'] : 0;
	$props	= kdjlSafeMemValue($_pm['mem']->get('db_propsid'), array());
	if(!is_array($props)) $props = array();
	$guild_level_info = '升级到<font color=red> '.($arr['level']+1).' </font>级<br />需要荣誉：'.$arr['need_honor'].'<br />需要成员数：'.$arr['need_member_number'].'<br />需要物品：<br />';
	$new_arr = explode(',',$arr['need_props']);
	foreach($new_arr as $v){
		$a = explode('|',$v);
		if(count($a) < 2 || intval($a[0]) < 1) continue;
		$pid = intval($a[0]);
		$have_props = $_pm['mysql'] -> getOneRecord("SELECT COALESCE(SUM(sums),0) AS sums FROM guild_bag WHERE pid={$pid} AND guild_id={$arr['id']}");
		if(!is_array($have_props)){
			$have_props['sums'] = 0;
		}
		$guild_level_info .= familyModHtml(isset($props[$a[0]]['name']) ? $props[$a[0]]['name'] : $a[0]).'&nbsp;'.intval($have_props['sums']).'/'.intval($a[1]).'个<br />';
	}
}
//echo $guildstr;exit;
$taskid = isset($user['task']) ? $user['task'] : 0;
$taskword= taskcheck($taskid,6);
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
