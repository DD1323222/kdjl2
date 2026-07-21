<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%
*@Write Date: 2008.05.01
*@Update Date: 2008.05.22
*@Usage: Shop main ui
*@Note: none
*/
require_once('../config/config.game.php');
secStart($_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');

function sdModHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function sdModJsDouble($value)
{
	$value = str_replace("\\", "\\\\", (string)$value);
	$value = str_replace('"', '\\"', $value);
	$value = str_replace(array("\r", "\n"), array("\\r", "\\n"), $value);
	$value = str_replace("</", "<\\/", $value);
	return $value;
}

function sdModImage($value)
{
	$value = basename((string)$value);
	return preg_match('/^[A-Za-z0-9_.-]+$/', $value) ? $value : '';
}

$user		= $_pm['user']->getUserById($uid);
$petsAll	= $_pm['user']->getUserPetById($uid);
$bag		= $_pm['user']->getUserBagById($uid);
if(!is_array($user)) die('');
if(!is_array($petsAll)) $petsAll = array();
if(!is_array($bag)) $bag = array();
$userDefaults = array('money' => 0, 'yb' => 0, 'maxbag' => 0, 'task' => 0);
foreach($userDefaults as $defaultKey => $defaultValue)
{
	if(!isset($user[$defaultKey])) $user[$defaultKey] = $defaultValue;
}
$membbname = $_pm['mem']->get('db_bbname');
if(!is_array($membbname)) $membbname = kdjlSafeMemValue($membbname, array());
if(!is_array($membbname)) $membbname = array();
$membbid = $_pm['mem']->get('db_bbid');
if(!is_array($membbid)) $membbid = kdjlSafeMemValue($membbid, array());
if(!is_array($membbid)) $membbid = array();

$incZhl='';
$zjsxdj="'";
$pets = array('', '', '');
$compets = array('', '', '');
$zspets = array('', '', '');
$petsSS = array('', '', '');
$petsZS = array('', '', '');
$comapetslist = '';
$combblistid = '';
$zsoption = '';
$zsapetslist = '';
$zsbblistid = '';
$plist = '';
$zsplist = '';
$zswptwo = '';
$sszswp = '';
$shop = '';
$pd = array();
$selid = 0;
$chga = array('level' => 0, 'money' => 0, 'clevel' => 0, 'pid' => '', 'gbname' => '');
$chgb = array('level' => 0, 'money' => 0, 'clevel' => 0, 'pid' => '', 'gbname' => '', 'pids2' => 0);
$chap = array('pids1' => 0);
$bbsszs = '';
$js = '';
foreach($bag as $v)
{
	if(!is_array($v)) continue;
	if(!isset($v['id'])) $v['id'] = 0;
	if(!isset($v['name'])) $v['name'] = '';
	if(!isset($v['effect'])) $v['effect'] = '';
	if(strpos($v["effect"],'inczhl:')!==false)
	{
		$incZhl    .='<option value="'.$v['id'].'">'.sdModHtml($v['name']).'</option>';
	}

	if(strpos($v["effect"],'zjsxdj_')!==false)
	{
		$zjsxdj    .='<option value="'.$v['id'].'">'.sdModHtml($v['name']).'</option>';
	}
}
$zjsxdj.="'";

$petsAlls	= $_pm['mem']->get(MEM_BB_KEY);
if(!is_array($petsAlls)) $petsAlls = kdjlSafeMemValue($petsAlls, array());
if(!is_array($petsAlls)) $petsAlls = array();
if (isset($_REQUEST['pid']) && !is_array($_REQUEST['pid']) && intval($_REQUEST['pid'])>0)
$pid = intval($_REQUEST['pid']);
else $pid=0;
$comkk = 0;
$zskk = 0;
$style = (isset($_GET['style']) && !is_array($_GET['style'])) ? $_GET['style'] : '';
$bbjs='var bbjs={};
';

// pet maps are loaded above.
$mempropsid = $_pm['mem']->get('db_propsid');
if(!is_array($mempropsid)) $mempropsid = kdjlSafeMemValue($mempropsid, array());
if(!is_array($mempropsid)) $mempropsid = array();

if (is_array($petsAll))
{
	$kk=0;
	$flag = 0;
	foreach ($petsAll as $k => $rs)
	{
		if(!is_array($rs)) continue;
		$rsDefaults = array('id' => 0, 'name' => '', 'muchang' => 0, 'tgflag' => 0, 'level' => 0, 'wx' => 0, 'czl' => 0, 'cardimg' => '', 'remaketimes' => 0, 'remakelevel' => 0, 'remakeid' => 0, 'remakepid' => '');
		foreach($rsDefaults as $defaultKey => $defaultValue)
		{
			if(!isset($rs[$defaultKey])) $rs[$defaultKey] = $defaultValue;
		}
		if ($rs['muchang'] != 0 || intval($rs['tgflag']) != 0) continue;

		// 合成宠物
		if($rs['level'] >= 40 && $comkk < 3){
			$cardImg = sdModImage($rs['cardimg']);
			$compets[$comkk++] = "<img src='".IMAGE_SRC_URL."/bb/".$cardImg."' onclick='Display({$rs['id']});' style='cursor:pointer;display:none;' id='cp{$comkk}'>";
			$comapetslist .= "<option value='{$rs['id']}'>".sdModHtml($rs['name'])."-".intval($rs['level'])."</option>\n";
			$combblistid .= $combblistid?",'{$rs['id']}-{$cardImg}'":"'{$rs['id']}-{$cardImg}'";
		}
		// 涅槃宠物
		if($rs['level'] >= 60 && ($rs['name'] == "涅磐兽（亥）" || $rs['name'] == "涅磐兽（午）" || $rs['name'] == "涅磐兽（卯）") && $rs['muchang'] == 0)
		{
			$zsoption .= "<option value='{$rs['id']}'>".sdModHtml($rs['name'])."-".intval($rs['level'])."</option>\n";
		}
		if ($rs['level']>=60 && $zskk < 3 && $rs['wx'] == 6 && ($rs['name'] != "涅磐兽（亥）" && $rs['name'] != "涅磐兽（午）" && $rs['name'] != "涅磐兽（卯）")){
			$cardImg = sdModImage($rs['cardimg']);
			$zspets[$zskk++] = "<img src='".IMAGE_SRC_URL."/bb/".$cardImg."' onclick='Display({$rs['id']});' style='cursor:pointer;display:none;' id='zscp{$zskk}'>";
			$zsapetslist .= "<option value='{$rs['id']}'>".sdModHtml($rs['name'])."-".intval($rs['level'])."</option>\n";
			$zsbblistid .= $zsbblistid?",'{$rs['id']}-{$cardImg}'":"'{$rs['id']}-{$cardImg}'";
		}
		//涅磐从这里结束

		if($pid == 0)
		{
			if ($kk == 0)
			{
				$sel	= 100;
				$selid	= $rs['id'];
				$pd=$rs;
			}
			else $sel = 50;
		}
		else
		{
			if($rs['id'] == $pid)
			{
				$sel	= 100;
				$selid	= $rs['id'];
				$pd=$rs;
			}
			else
			{
				$sel = 50;
			}
		}
		if ($pid == $rs['id'])
		{
			$pd		= $rs;
			$selid	= $rs['id'];
		}
		$sellv = $sel / 100;
		$basePetConfig = resolveBasePetForSd($rs, $membbname, $membbid);
		$sszsBaseId = is_array($basePetConfig) ? intval($basePetConfig['id']) : 0;
		$cardImg = sdModImage($rs['cardimg']);
		$nameJs = sdModHtml(sdModJsDouble($rs['name']));
		$pets[$kk++] = "<img src='".IMAGE_SRC_URL."/bb/".$cardImg."' onclick='Display(this,{$rs['id']});copyWord(\"{$nameJs}\")' style='opacity: ".$sellv."; filter : progid:DXImageTransform.Microsoft.Alpha(style=0,opacity={$sel},finishOpacity=100);cursor:pointer;' id='i{$kk}'>";// Added a new function of "copyWord" by DuHao
		$petsSS[]="<img src='".IMAGE_SRC_URL."/bb/".$cardImg."' onclick='Display1(this,{$rs['id']},0,0,0);showJHInfo({$rs['id']});copyWord(\"{$nameJs}\")' style='opacity: ".$sellv."; filter : progid:DXImageTransform.Microsoft.Alpha(style=0,opacity={$sel},finishOpacity=100);cursor:pointer;' id='s{$kk}'>";
		if($flag == 0){
			$bbsszs = '<table width="210" border="0" cellspacing="0" cellpadding="0">
			  <tr>
				<td colspan="2">宠物当前等级：<span id="bblevel">'.($rs['wx']==7?$rs['level']:'').'</span></td>
				</tr>
			  <tr>
				<td colspan="2">宠物当前成长：<span id="bbczl">'.($rs['wx']==7?$rs['czl']:'').'</span></td>
				</tr>
			  <tr>
				<td colspan="2">&nbsp;</td>
				</tr>
			  <tr>
				<td colspan="2">&nbsp;</td>
			  </tr>
			  <tr>
				<td width="125"><img src="../images/sd_cion08.jpg" style="cursor:pointer" onclick="displayInfo(3)" width="79" height="24" /></td>
				<td width="85"><img src="../images/sd_cion09.jpg" width="79" height="24" style="cursor:pointer" onclick="sszs()" /></td>
			  </tr>
			</table>';
			$js = 'setBBId='.$rs['id'].';';
			if($rs['wx'] == 7 && $sszsBaseId > 0){
				$js .= "sszsshow(".$sszsBaseId.");sszsstr(0,".$sszsBaseId.",this);";
			}else{
				$js .= "sszsshow(0);sszsstr(0,0,this);";
			}

			$flag = 1;
		}
		if($rs['wx'] == 7){
			$petsZS[]="<img src='".IMAGE_SRC_URL."/bb/".$cardImg."' onclick='Display1(this,{$rs['id']},".intval($rs['level']).",".intval($rs['czl']).",1);sszsshow(".$sszsBaseId.");sszsstr(0,".$sszsBaseId.",this);copyWord(\"{$nameJs}\")' style='opacity: ".$sellv."; filter : progid:DXImageTransform.Microsoft.Alpha(style=0,opacity={$sel},finishOpacity=100);cursor:pointer;' id='z{$kk}'>";
		}else{
			$petsZS[]="<img src='".IMAGE_SRC_URL."/bb/".$cardImg."' onclick='Display1(this,{$rs['id']},0,0,2);sszsshow(0);sszsstr(0,0,this);copyWord(\"{$nameJs}\")' style='opacity: ".$sellv."; filter : progid:DXImageTransform.Microsoft.Alpha(style=0,opacity={$sel},finishOpacity=100);cursor:pointer;' id='z{$kk}'>";
		}

		//$petsZS[]="<img src='".IMAGE_SRC_URL."/bb/{$rs['cardimg']}' onclick='Display1(this,{$rs['id']});copyWord(\"{$rs['name']}\")' style='opacity: ".$sellv."; filter : progid:DXImageTransform.Microsoft.Alpha(style=0,opacity={$sel},finishOpacity=100);cursor:pointer;' id='z{$kk}'>";
		$bbjs.='bbjs["'.$rs['id'].'"]="'.sdModJsDouble(getSSJh($rs)).'";
';
		if ($kk == 3) break;

	}
}

$petDefaults = array('id' => 0, 'name' => '', 'level' => 0, 'wx' => 0, 'czl' => 0, 'cardimg' => '', 'remaketimes' => 0, 'remakelevel' => 0, 'remakeid' => 0, 'remakepid' => '');
foreach($petDefaults as $defaultKey => $defaultValue)
{
	if(!isset($pd[$defaultKey])) $pd[$defaultKey] = $defaultValue;
}
if($selid < 1 && isset($pd['id'])) $selid = intval($pd['id']);

function resolveBasePetForSd($pet, $byName, $byId)
{
	if(!is_array($pet)) return false;
	$petDefaults = array('name' => '', 'old_bid' => 0, 'remakelevel' => '', 'remakeid' => '', 'remakepid' => '');
	foreach($petDefaults as $defaultKey => $defaultValue)
	{
		if(!isset($pet[$defaultKey])) $pet[$defaultKey] = $defaultValue;
	}
	if(isset($pet['old_bid'])){
		$oldBid = intval($pet['old_bid']);
		if($oldBid > 0 && is_array($byId) && isset($byId[$oldBid]) && is_array($byId[$oldBid])){
			return $byId[$oldBid];
		}
	}
	if(is_array($byId)){
		foreach($byId as $basePet){
			if(!is_array($basePet) || !isset($basePet['name'])){
				continue;
			}
			if($basePet['name'] != $pet['name']){
				continue;
			}
			if(!isset($basePet['remakelevel'])) $basePet['remakelevel'] = '';
			if(!isset($basePet['remakeid'])) $basePet['remakeid'] = '';
			if(!isset($basePet['remakepid'])) $basePet['remakepid'] = '';
			if((string)$basePet['remakelevel'] == (string)$pet['remakelevel'] &&
			   (string)$basePet['remakeid'] == (string)$pet['remakeid'] &&
			   (string)$basePet['remakepid'] == (string)$pet['remakepid']){
				return $basePet;
			}
		}
	}
	if(is_array($byName) && isset($byName[$pet['name']]) && is_array($byName[$pet['name']])){
		return $byName[$pet['name']];
	}
	return false;
}

function getSSJh(&$bb)
{
	global $_pm,$membbname,$membbid,$mempropsid;
	if(!is_array($bb)) return 'N/A|0|N/A|N/A|0|0|0|0';
	$bbDefaults = array('id' => 0, 'name' => '', 'level' => 0, 'czl' => 0, 'wx' => 0, 'remaketimes' => 0, 'remakelevel' => '', 'remakeid' => '', 'remakepid' => '');
	foreach($bbDefaults as $defaultKey => $defaultValue)
	{
		if(!isset($bb[$defaultKey])) $bb[$defaultKey] = $defaultValue;
	}
	$bbO = resolveBasePetForSd($bb, $membbname, $membbid);

	if(!$bbO)
	{
		die('内存中找不到要进化的宠物('.$bb['name'].')的原始数据！');
	}

	$bbJhSetting = $_pm['mysql']->getOneRecord('select zs_progress,need_levels,need_props,max_czl from super_jh where pet_id='.$bbO['id']);
	if(!$bbJhSetting)
	{
		return 'N/A|'.$bb['level'].'|N/A|N/A|'.$bb['remaketimes'].'|'.$bb['czl'].'|'.$bb['wx'];
		//die('数据库中没有该宠物('.$bb['name'].')神圣进化的设定！');
	}

	$settingDefaults = array('zs_progress' => 0, 'need_levels' => '0', 'need_props' => '0:0', 'max_czl' => 0);
	foreach($settingDefaults as $defaultKey => $defaultValue)
	{
		if(!isset($bbJhSetting[$defaultKey])) $bbJhSetting[$defaultKey] = $defaultValue;
	}
	$nlvls = explode(',',$bbJhSetting['need_levels']);
	if(count($nlvls)-1<$bb['remaketimes'])
	{
		$limitlvl=$nlvls[0];
	}else{
		$limitlvl=$nlvls[$bb['remaketimes']];
	}

	$nprops = explode(',',$bbJhSetting['need_props']);
	if(count($nprops)-1<$bb['remaketimes'])
	{
		$npropsIds=explode('|',$nprops[0]);
	}else{
		$npropsIds=explode('|',$nprops[$bb['remaketimes']]);
	}

	$propsStr='';
	foreach($npropsIds as $str)
	{
		$items=explode(':',$str);
		if(count($items)==2){
			if(isset($mempropsid[$items[0]]))
			{
				$propsName = isset($mempropsid[$items[0]]['name']) ? $mempropsid[$items[0]]['name'] : '';
				$propsStr.=sdModHtml($propsName).' '.intval($items[1]).'个,';
			}
			else
			{
				if($items[0]==0){
					$propsStr.='<font color=ff00000>不存在的物品</font> '.intval($items[1]).'个,';
				}else{
					$propsStr.='<font color=ff00000>设定错误</font>,';
				}
			}
		}
	}
	if($propsStr) $propsStr = substr($propsStr,0,-1);
	$gold=($bbJhSetting['zs_progress']+$bb['remaketimes'])*10000;
	if($bb['remaketimes']>9)
	{
		$limitlvl='N/A';
		$gold    ='N/A';
	}
	return $limitlvl.'|'.$bb['level'].'|'.$propsStr.'|'.$gold.'|'.$bb['remaketimes'].'|'.$bb['czl'].'|'.$bb['wx'].'|'.$bbJhSetting['max_czl'];
}

$taskword= taskcheck($user['task'], 2);
$rs= $pd;
// Fix parameter.
if (is_array($petsAlls) && !empty($petsAlls)) { //only if statement is added by Zheng.Ping
	$matchedPetConfig = false;
	$nameMatchedPetConfig = false;
    foreach($petsAlls as $x=>$y)
    {
		if(!is_array($y)) continue;
		$baseDefaults = array('name' => '', 'remakelevel' => '', 'remakeid' => '', 'remakepid' => '');
		foreach($baseDefaults as $defaultKey => $defaultValue)
		{
			if(!isset($y[$defaultKey])) $y[$defaultKey] = $defaultValue;
		}
        if ($y['name'] == $rs['name'])
        {
			if ($nameMatchedPetConfig === false)
			{
				$nameMatchedPetConfig = $y;
			}
			if ($rs['remakelevel'] == $y['remakelevel'] &&
				$rs['remakeid'] == $y['remakeid'] &&
				$rs['remakepid'] == $y['remakepid'])
			{
				$matchedPetConfig = $y;
				break;
			}
        }
    }
	if ($matchedPetConfig === false)
	{
		$matchedPetConfig = $nameMatchedPetConfig;
	}
	if (is_array($matchedPetConfig))
	{
		$rs['remakelevel'] = $matchedPetConfig['remakelevel'];
		$rs['remakeid'] = $matchedPetConfig['remakeid'];
		$rs['remakepid'] = $matchedPetConfig['remakepid'];
	}
}
// 获得进化资料。默认为第一个宝宝。
// Get plus level info. $pd.
if ($rs['remakelevel'] == '0,0' || $rs['remakelevel']==0)
{
$chga = array('level' => 0, 'money' => 0, 'clevel' => $rs['level'], 'pid' => '', 'gbname' => '');
$chgb = array('level' => 0, 'money' => 0, 'clevel' => $rs['level'], 'pid' => '', 'gbname' => '', 'pids2' => 0);
}
else
{

	$props = $_pm['mem']->get('db_propsid');
	if(!is_array($props)) $props = kdjlSafeMemValue($props, array());
	if(!is_array($props)) $props = array();
	$bbs = $_pm['mem']->get('db_bbid'); // added by Zheng.Ping
	if(!is_array($bbs)) $bbs = kdjlSafeMemValue($bbs, array());
	if(!is_array($bbs)) $bbs = array();

	$arrlevel = explode(',', $rs['remakelevel']);
	$arrpid   = explode(',', $rs['remakepid']);
	$petgoals = explode(',', $rs['remakeid']); //added by Zheng.Ping
	if(!isset($arrlevel[0])) $arrlevel[0] = 0;
	if(!isset($arrlevel[1])) $arrlevel[1] = $arrlevel[0];
	if(!isset($arrpid[0])) $arrpid[0] = 0;
	if(!isset($arrpid[1])) $arrpid[1] = $arrpid[0];
	$no_pet_goal = '不可进化';
	$chga['level'] = $arrlevel[0];
	$chga['money'] = 1000;
	$chga['clevel']= $rs['level'];
	$chga['pid']   = getPropsName($arrpid[0],$props);
	$chga['gbname'] = (false !== $petgoals && isset($petgoals[0])) ? getBbName($petgoals[0], $bbs) : $no_pet_goal; // added by Zheng.Ping
	$chap['pids1'] = getPropsId($arrpid[0]);
	$chgb['level'] = $arrlevel[1];
	$chgb['money'] = 1000;
	$chgb['clevel']= $rs['level'];
	$chgb['pid']   = $arrpid[0]==$arrpid[1]?$chga['pid']:getPropsName($arrpid[1],$props);
	$chgb['gbname'] = (false !== $petgoals && isset($petgoals[1])) ? getBbName($petgoals[1], $bbs) : $no_pet_goal; // added by Zheng.Ping
	//$chgb['pids2']   = $arrpid[0]==$arrpid[1]?$chga['pid']:getPropsId($arrpid[1]);
	$chgb['pids2'] = getPropsId($arrpid[1]);
}
//@Load template.


//合成物品
if (is_array($bag))
{
	$i = 0;
	foreach($bag as $k => $v)
	{
		if(!is_array($v)) continue;
		$vDefaults = array('id' => 0, 'name' => '', 'sums' => 0, 'varyname' => 0, 'effect' => '', 'usages' => '');
		foreach($vDefaults as $defaultKey => $defaultValue)
		{
			if(!isset($v[$defaultKey])) $v[$defaultKey] = $defaultValue;
		}
		if($v['varyname'] == 19){
			$zswptwo .= "<option value='{$v['id']}'>".sdModHtml($v['name'])."-".intval($v['sums'])."个</option>\n";
			continue;
		}
		if($v['varyname'] == 23){
			$sszswp .= "<option value='{$v['id']}'>".sdModHtml($v['name'])."-".intval($v['sums'])."个</option>\n";
			continue;
		}
		if ($v['varyname']!=8 || $v['effect']=='') continue;
		$money = 0;
		// Get money;
		// effect format: luck:B:10%:5000, shbb:5000
		$one = explode(',', $v['effect']);
		foreach ($one as $a => $b)
		{
			$arr = explode(':', $b);
			$money+=$arr[count($arr)-1];
		}
		//转生
		$name = explode(":",$v['usages']);
		if(!empty($v['sums']) && $name[0] != '涅盘')
		{
			$plist .= "<option value='{$v['id']}'>".sdModHtml($v['name'])."-".intval($money)."-".intval($v['sums'])."个</option>\n";
		}
		$effarr = explode(":",$v['usages']);
		if($effarr[0] == '涅盘' && !empty($v['sums'])){
			$zsplist .= "<option value='{$v['id']}'>".sdModHtml($v['name'])."-".intval($v['sums'])."个</option>\n";
		}
	}
}

$a=$_pm['mysql']->getOneRecord("select hecheng_nums,czl_ss from player_ext where uid='{$uid}'");

if($err=mysql_error($_pm['mysql']->getConn()))
{
	if(strpos($err,'hecheng_nums')!==false)
	{
		$_pm['mysql']->addColumnIfMissing('player_ext', 'hecheng_nums', 'int(11) null default 0');
	}
	if(strpos($err,'czl_ss')!==false)
	{
		$_pm['mysql']->addColumnIfMissing('player_ext', 'czl_ss', 'int(11) null default 0');
	}
	if(strpos($err,'hecheng_nums')!==false || strpos($err,'czl_ss')!==false)
	{
		$a=$_pm['mysql']->getOneRecord("select hecheng_nums,czl_ss from player_ext where uid='{$uid}'");
	}
}

$a = is_array($a) ? $a : array('hecheng_nums' => 0, 'czl_ss' => 0);
if(!isset($a['hecheng_nums'])) $a['hecheng_nums'] = 0;
if(!isset($a['czl_ss'])) $a['czl_ss'] = 0;
$xingyunxin=(is_array($a) && isset($a['hecheng_nums'])) ? intval($a['hecheng_nums']) : 0;
if($xingyunxin < 0) $xingyunxin = 0;
if($xingyunxin > 10) $xingyunxin = 10;


$tn = $_game['template'] . 'tpl_sd.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);
	$src =
	array(
		'#money#',
		'#yb#',
		'#baglimit#',
		//right attrib.
		'#shoplist#',
		'#mybag#',
		'#word#',
		'#one#',
		'#two#',
		'#three#',
		'#alevel#',
		'#aclevel#',
		'#amoney#',
		'#aprops#',
	    '#agbname#', /* added by Zheng.Ping */
		'#blevel#',
		'#bclevel#',
		'#bmoney#',
		'#bprops#',
	    '#bgbname#', /* added by Zheng.Ping */
		'#id#',
		'#pids1#',
		'#pids2#',
		"#comone#",
		 "#comtwo#",
		 "#comapetslist#",
		 "#bpetslist#",
		 "#wupinone#",
		 "#wupintwo#",
		 "#bballid#",
		 "#xingyunxin#",
		  "#zsone#",
		 "#zstwo#",
		 "#zsapetslist#",
		 "#zsbpetslist#",
		 "#zswupinone#",
		 "#zsbballid#",
		 "#zsoptions#",
		 "#style#",
		 "#zswupintwo#",
		 '#bbjs#',
		 '#petsSS1#','#petsSS2#','#petsSS3#',
		 '#petsZS1#','#petsZS2#','#petsZS3#',
		 '#incZhl#','#yyczl#',
		 '#bbsszsinfo#','#js#','#sszswp#','#zjsxdj#'
	);

	$des = array($user['money'],
	$user['yb'],
	count($bag).'/'.$user['maxbag'],
	//right part
	$shop,
	$bag,
	$taskword,
	$pets[0],
	$pets[1],
	$pets[2],
	$chga['level'],
	$chga['clevel'],
	$chga['money'],
	$chga['pid'],
	$chga['gbname'], /* added by Zheng.Ping */
	$chgb['level'],
	$chgb['clevel'],
	$chgb['money'],
	$chgb['pid'],
	$chgb['gbname'], /* added by Zheng.Ping */
	$selid,
	$chap['pids1'],
	$chgb['pids2'],
	$compets[0],
	$compets[1],
	$comapetslist,
	$comapetslist,
	$plist,
	$plist,
	$combblistid,
	$xingyunxin,
	$zspets[0],
	$zspets[1],
	$zsapetslist,
	$zsapetslist,
	$zsplist,
	$zsbblistid,
	$zsoption,
	$style,
	$zswptwo,
	$bbjs,$petsSS[0],$petsSS[1],$petsSS[2]
	,$petsZS[0],$petsZS[1],$petsZS[2],
	$incZhl,$a['czl_ss'],$bbsszs,$js,$sszswp,$zjsxdj
	);
	$shop = str_replace($src, $des, $tpl);
}
// gzip echo. if maybe.
ob_start('ob_gzip');
echo $shop;
ob_end_flush();
// Get props name for pid.
// @return: false or String.
function getPropsName($pid,$props)
{
	$pids = explode('|',$pid);
	$rtn = array();
	$str = '';
	if(is_array($pids))
	{
		foreach($pids as $v)
		{
			if(empty($v))
			{
				continue;
			}
			if(!is_array($props) || !isset($props[$v]) || !is_array($props[$v]))
			{
				continue;
			}
			$p = $props[$v];
			if(!isset($p['name'])) $p['name'] = '';
			if(empty($str))
			{
				$nameHtml = sdModHtml($p['name']);
				$nameJs = sdModHtml(sdModJsDouble($p['name']));
				$str = "<span onclick='copyWord(\"{$nameJs}\")'>".$nameHtml."</span>";
			}
			/*else
			{
				$str .= " 或 <span onclick='copyWord(\"{$p['name']}\")'>".$p['name']."</span>";
			}*/
		}
	}

	/*foreach($props as $p)
	{
		if(in_array($p['id'],$pids))
		{
			$rtn[] = "<span onclick='copyWord(\"{$p['name']}\")'>".$p['name']."</span>"; //Add "<span>" by DuHao
		}
		if(count($rtn)==count($pids)) break;
	}

	$arr = array();
	foreach($rtn as $v)
	{
		if(in_array($v,$arr))
		{
			continue;
		}
		else
		{
			$arr[] = $v;
		}
	}
	if (is_array($arr)) return implode(" 或 ",$arr);*/
	if(!empty($str)) return $str;
	else return false;
}
function getPropsId($pid)
{
	global $_pm;
	/*$rs = $_pm['mem']->dataGet(array('k' => MEM_PROPS_KEY,
	'v' => "if(\$rs['id'] == {$pid}) \$ret=\$rs;"
	));*/
	$mempropsid = $_pm['mem']->get('db_propsid');
	if(!is_array($mempropsid)) $mempropsid = kdjlSafeMemValue($mempropsid, array());
	if(!is_array($mempropsid)) $mempropsid = array();
	$arr = explode('|',$pid);
	$rs = false;
	if(is_array($arr)){
		foreach($arr as $v){
			if(isset($mempropsid[$v]) && is_array($mempropsid[$v])) $rs = $mempropsid[$v];
		}
	}
	if (is_array($rs) && isset($rs['id'])) return $rs['id'];
	else return false;
}

/**
 * get the name of the evolved BB
 *
 * @param integer $pid
 * @param array   $bbs
 * @return string
 * @author Zheng.Ping
 */
function getBbName($pid, $bbs)
{
	$pids = explode('|',$pid);
   // $rtn  = array();
    $ret  = '不可进化';
	$str = '';
	if(!is_array($pids))
	{
		return $ret;
	}
	foreach($pids as $v)
	{
		if(!empty($v))
		{
			$p = (is_array($bbs) && isset($bbs[$v]) && is_array($bbs[$v])) ? $bbs[$v] : false;
			if(is_array($p))
			{
				if(!isset($p['name'])) $p['name'] = '';
				if(empty($str))
				{
					$nameHtml = sdModHtml($p['name']);
					$nameJs = sdModHtml(sdModJsDouble($p['name']));
					$str = "<span onclick='copyWord(\"{$nameJs}\")'>".$nameHtml."</span>";
				}
				else
				{
					$nameHtml = sdModHtml($p['name']);
					$nameJs = sdModHtml(sdModJsDouble($p['name']));
					$str .= "或<span onclick='copyWord(\"{$nameJs}\")'>".$nameHtml."</span>";
				}
			}
		}
	}

    /*if (is_array($bbs) && !empty($bbs)) {
        foreach($bbs as $p)
        {
            if(in_array($p['id'], $pids))
            {
                $rtn[] = "<span onclick='copyWord(\"{$p['name']}\")'>".$p['name']."</span>"; //Add "<span>" by DuHao
            }
            if(count($rtn)==count($pids)) break;
        }
    }*/

    if (!empty($str)) return $str;
    else return $ret;
}
$_pm['mem']->memClose();
?>
