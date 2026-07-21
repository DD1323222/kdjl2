<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

@Usage: PAI Display function(PROPS);
@Write date: 2008.05.13
@Update date: 2008.05.23
@Note:
	1) Loop Init userbag while use have pai props. User as loop object
	2) Get in memory's userbag
*/
error_reporting(0);
ini_set('display_errors', 0);
require_once('../config/config.game.php');
secStart($_pm['mem']);
//exit;
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid <= 0)
{
	die('登录状态已失效，请重新登录！');
}
del_bag_expire();

function paiModHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function paiModJsSingle($value)
{
	$value = str_replace("\\", "\\\\", (string)$value);
	$value = str_replace("'", "\\'", $value);
	$value = str_replace(array("\r", "\n"), array("\\r", "\\n"), $value);
	return $value;
}

function paiModIntFields($row, $fields)
{
	foreach($fields as $field)
	{
		$row[$field] = isset($row[$field]) ? intval($row[$field]) : 0;
	}
	return $row;
}

$user	 = $_pm['user']->getUserById($uid);
if(!is_array($user)) die('玩家数据不存在，请重新登录！');
$userDefaults = array('money'=>0, 'yb'=>0, 'maxbag'=>0, 'paimoney'=>0, 'task'=>0, 'nickname'=>'');
foreach($userDefaults as $userDefaultKey => $userDefaultValue)
{
	if(!isset($user[$userDefaultKey])) $user[$userDefaultKey] = $userDefaultValue;
}
$userBag	 = $_pm['user']->getUserBagById($uid);
$now = time();
$bagtype = (isset($_REQUEST['bagtype']) && !is_array($_REQUEST['bagtype'])) ? $_REQUEST['bagtype'] : '';
$basetype = (isset($_REQUEST['basetype']) && !is_array($_REQUEST['basetype'])) ? $_REQUEST['basetype'] : '';
$atype = (isset($_GET['atype']) && !is_array($_GET['atype'])) ? intval($_GET['atype']) : 0;
if($atype < 1 || $atype > 4) $atype = 0;
if($bagtype !== '' && !preg_match('/^[0-9,|]+$/', $bagtype))
{
	$bagtype = '';
}
if($basetype !== '' && !preg_match('/^[0-9,|]+$/', $basetype))
{
	$basetype = '';
}
$baseVaryIds = array();
if($basetype !== '')
{
	foreach(preg_split('/[|,]+/', $basetype) as $baseVaryId)
	{
		$baseVaryId = intval($baseVaryId);
		if($baseVaryId >= 1 && $baseVaryId <= 16) $baseVaryIds[$baseVaryId] = $baseVaryId;
	}
}
$baseFilterSql = count($baseVaryIds) > 0 ? ' and p.varyname IN ('.implode(',', $baseVaryIds).')' : '';
$mypairet = "";
$bagoption = "";
$baseoption = "";
$bag = '';
$pairet = '';
$sjpairet = '';
$ybpairet = '';
$shop = '';
$sjarr = $_pm['mysql'] -> getOneRecord("SELECT sj,paisj,paiyb FROM player_ext WHERE uid = {$uid}");
if(!is_array($sjarr)) $sjarr = array('sj' => 0, 'paisj' => 0, 'paiyb' => 0);
if(!isset($sjarr['sj'])) $sjarr['sj'] = 0;
if(!isset($sjarr['paisj'])) $sjarr['paisj'] = 0;
if(!isset($sjarr['paiyb'])) $sjarr['paiyb'] = 0;

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
		$bagoption .= "<option selected=selected value='./Pai_Mod.php?bagtype=".$v."&basetype=".$basetype."&atype=".$atype."'>".$arrinfo[$ks]."</option>";
	}
	else
	{
		$bagoption .= "<option value='./Pai_Mod.php?bagtype=".$v."&basetype=".$basetype."&atype=".$atype."'>".$arrinfo[$ks]."</option>";
	}
}
//交易所
foreach($arr as $ks => $v)
{
	if($basetype == $v)
	{
		$baseoption .= "<option selected=selected value='./Pai_Mod.php?basetype=".$v."&bagtype=".$bagtype."&atype=".$atype."'>".$arrinfo[$ks]."</option>";
	}
	else
	{
		$baseoption .= "<option value='./Pai_Mod.php?basetype=".$v."&bagtype=".$bagtype."&atype=".$atype."'>".$arrinfo[$ks]."</option>";
	}
}

##########################在这里结束###############################


// 此语句需要优化，考虑缓存。
$paiProps	= $_pm['mysql']->getRecords("SELECT b.id as id,
									  b.uid as uid,
									  b.vary as vary,
									  b.psell as psell,
									  b.pstime as pstime,
									  b.petime as petime,
									  b.psum as psum,
									  p.name as name,
									  p.varyname as varyname,
									  p.effect as effect,
									  p.requires as requires,
									  p.sell as sell,
									  p.img as img,
									  p.pluseffect as pluseffect,
									  p.id as a
								 FROM userbag as b,props as p
								WHERE p.id = b.pid  and b.psell>0 and b.psum>0 and b.petime>'{$now}'{$baseFilterSql}
								ORDER BY b.pstime DESC
								LIMIT 0,60
							");
if (is_array($paiProps))
{
	$paiRows = 0;
	foreach ($paiProps as $k => $rs)
	{
		if(!is_array($rs)) continue;
		$rsDefaults = array('id'=>0, 'name'=>'', 'varyname'=>0, 'requires'=>'', 'psell'=>0, 'psum'=>0);
		foreach($rsDefaults as $rsDefaultKey => $rsDefaultValue)
		{
			if(!isset($rs[$rsDefaultKey])) $rs[$rsDefaultKey] = $rsDefaultValue;
		}
		$rs = paiModIntFields($rs, array('id', 'varyname', 'psell', 'psum'));
		if(count($baseVaryIds) > 0 && !isset($baseVaryIds[$rs['varyname']])) continue;
###在这里结束####

		if (strlen($rs['requires'])>2)
		{
			$t = explode(',', str_replace(array('lv','wx'),array('等级','五行'),$rs['requires']));
			$wx = isset($t[1]) ? str_replace($_props['wxs'],$_props['wxd'],$t[1]) : '';
		}
		else $t[0]=$wx='无';

		$nameHtml = paiModHtml($rs['name']);
		$nameJs = paiModHtml(paiModJsSingle($rs['name']));
		$pairet .= '<tr>
		<td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
		<td width="110px" id="t'.$rs['id'].'" style="cursor:hand; text-align:left" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';"  onmouseout="window.parent.UnTip();this.style.border=0" onclick="copyWord(\''.$nameJs.'\');sel(this);bid='.($rs['id']?$rs['id']:0).';price='.$rs['psell'].';">'.$nameHtml.'('.intval($rs['psum']).')</td>
		<td width="60px" style=" text-align:left">' . $rs['psell'] . '</td>

	 </tr>';
		$paiRows++;
	}
	if($paiRows < 1) $pairet = '<tr><td colspan=3>当前没有符合条件的金币拍卖物品。</td></tr>';
}
else $pairet = '<tr><td colspan=3>当前没有符合条件的金币拍卖物品。</td></tr>';
//水晶拍卖
$sjpaiProps	= $_pm['mysql']->getRecords("SELECT b.id as id,
									  b.uid as uid,
									  b.vary as vary,
									  b.psj as psj,
									  b.pstime as pstime,
									  b.petime as petime,
									  b.psum as psum,
									  p.name as name,
									  p.varyname as varyname,
									  p.effect as effect,
									  p.requires as requires,
									  p.sell as sell,
									  p.img as img,
									  p.pluseffect as pluseffect,
									  p.id as a
								 FROM userbag as b,props as p
								WHERE p.id = b.pid  and b.psj>0 and b.psum>0 and b.petime>'{$now}'{$baseFilterSql}
								ORDER BY b.pstime DESC
								LIMIT 0,60
							");
if (is_array($sjpaiProps))
{
	$sjPaiRows = 0;
	foreach ($sjpaiProps as $k => $rs)
	{
		if(!is_array($rs)) continue;
		$rsDefaults = array('id'=>0, 'name'=>'', 'varyname'=>0, 'requires'=>'', 'psj'=>0, 'psum'=>0);
		foreach($rsDefaults as $rsDefaultKey => $rsDefaultValue)
		{
			if(!isset($rs[$rsDefaultKey])) $rs[$rsDefaultKey] = $rsDefaultValue;
		}
		$rs = paiModIntFields($rs, array('id', 'varyname', 'psj', 'psum'));
		if(count($baseVaryIds) > 0 && !isset($baseVaryIds[$rs['varyname']])) continue;
		if (strlen($rs['requires'])>2)
		{
			$t = explode(',', str_replace(array('lv','wx'),array('等级','五行'),$rs['requires']));
			$wx = isset($t[1]) ? str_replace($_props['wxs'],$_props['wxd'],$t[1]) : '';
		}
		else $t[0]=$wx='无';

		$nameHtml = paiModHtml($rs['name']);
		$nameJs = paiModHtml(paiModJsSingle($rs['name']));
		$sjpairet .= '<tr>
		<td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
        <td width="110px" id="t'.$rs['id'].'" style="cursor:hand; text-align:left" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';"  onmouseout="window.parent.UnTip();this.style.border=0" onclick="copyWord(\''.$nameJs.'\');sel(this);bid='.($rs['id']?$rs['id']:0).';price='.$rs['psj'].';">'.$nameHtml.'('.intval($rs['psum']).')</td>
        <td width="60px" style=" text-align:left">' . $rs['psj'] . '</td>

	 </tr>';
		$sjPaiRows++;
	}
	if($sjPaiRows < 1) $sjpairet = '<tr><td colspan=3>当前没有符合条件的水晶拍卖物品。</td></tr>';
}
else $sjpairet = '<tr><td colspan=3>当前没有符合条件的水晶拍卖物品。</td></tr>';

// 元宝拍卖
$ybpaiProps = $_pm['mysql']->getRecords("SELECT b.id as id,
                                            b.uid as uid,
                                            b.vary as vary,
                                            b.pyb as pyb,
                                            b.pstime as pstime,
                                            b.petime as petime,
                                            b.psum as psum,
                                            p.name as name,
                                            p.varyname as varyname,
                                            p.effect as effect,
                                            p.requires as requires,
                                            p.sell as sell,
                                            p.img as img,
                                            p.pluseffect as pluseffect,
                                            p.id as a
                                         FROM userbag as b
                                          JOIN props as p ON p.id = b.pid
                                         WHERE b.pyb > 0 AND b.psum > 0 AND b.petime > '{$now}'{$baseFilterSql}
                                         ORDER BY b.pstime DESC
                                         LIMIT 0,60");
if (is_array($ybpaiProps))
{
	$ybPaiRows = 0;
	foreach ($ybpaiProps as $k => $rs)
	{
		if(!is_array($rs)) continue;
		$rsDefaults = array('id'=>0, 'name'=>'', 'varyname'=>0, 'requires'=>'', 'pyb'=>0, 'psum'=>0);
		foreach($rsDefaults as $rsDefaultKey => $rsDefaultValue)
		{
			if(!isset($rs[$rsDefaultKey])) $rs[$rsDefaultKey] = $rsDefaultValue;
		}
		$rs = paiModIntFields($rs, array('id', 'varyname', 'pyb', 'psum'));

		#########################仓库的物品 9.18 谭炜###########################
		if(count($baseVaryIds) > 0 && !isset($baseVaryIds[$rs['varyname']])) continue;
		##########################在这里结束###############################

		if (strlen($rs['requires'])>2)
		{
			$t = explode(',', str_replace(array('lv','wx'),array('等级','五行'),$rs['requires']));
			$wx = isset($t[1]) ? str_replace($_props['wxs'],$_props['wxd'],$t[1]) : '';
		}
		else $t[0]=$wx='无';
		$nameHtml = paiModHtml($rs['name']);
		$nameJs = paiModHtml(paiModJsSingle($rs['name']));
        $ybpairet .= '<tr>
            <td width="35px"><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
            <td width="110px" id="t'.$rs['id'].'" style="cursor:pointer; text-align:left" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';" onmouseout="window.parent.UnTip();this.style.border=0" onclick="copyWord(\''.$nameJs.'\');sel(this);bid='.($rs['id']?$rs['id']:0).';price='.$rs['pyb'].';">'.$nameHtml.'('.intval($rs['psum']).')</td>
            <td width="60px" style="text-align:left">'.$rs['pyb'].'</td>
        </tr>';
		$ybPaiRows++;
    }
	if($ybPaiRows < 1) $ybpairet = '<tr><td colspan=3>当前没有符合条件的元宝拍卖物品。</td></tr>';
} else {
    $ybpairet = '<tr><td colspan=3>当前没有符合条件的元宝拍卖物品。</td></tr>';
}
//============元宝结束

$bg = 0;
$bagRows = 0;
// Get userbag
if (!is_array($userBag)) $bag='您的包裹是空的!';
else
{
	foreach ($userBag as $k => $rs)
	{
		if(!is_array($rs)) continue;
		$rsDefaults = array('id'=>0, 'pid'=>0, 'name'=>'', 'sums'=>0, 'zbing'=>0, 'varyname'=>0, 'requires'=>'', 'sell'=>0);
		foreach($rsDefaults as $rsDefaultKey => $rsDefaultValue)
		{
			if(!isset($rs[$rsDefaultKey])) $rs[$rsDefaultKey] = $rsDefaultValue;
		}
		$rs = paiModIntFields($rs, array('id', 'pid', 'sums', 'zbing', 'varyname', 'sell'));
		if ($rs['sums'] < 1 || $rs['id']==0 || $rs['zbing'] == 1) continue;
		if (strlen($rs['requires'])>2)
		{
			$t = explode(',', str_replace(array('lv','wx'),array('等级','五行'),$rs['requires']));
			$wx = isset($t[1]) ? str_replace($_props['wxs'],$_props['wxd'],$t[1]) : '';
		}
		else $t[0]=$wx='无';
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
		$nameHtml = paiModHtml($rs['name']);
		$nameJs = paiModHtml(paiModJsSingle($rs['name']));
		$bag .= '<tr>
		<td width="35px" ><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
		<td width="110px" id="t'.$rs['id'].'" style="cursor:hand; text-align:left" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';" onmouseout="window.parent.UnTip();;this.style.border=0" onclick="copyWord(\''.$nameJs.'\');sel(this);bid='.$rs['id'].';price='.$rs['sell'].';">'.$nameHtml.'</td>
		<td width="60px" style=" text-align:left">' . $rs['sell'] . '</td>
		<td style=" text-align:left" id="s'.$rs['id'].'" >' . $rs['sums'] .'</td>
	 </tr>';
		$bagRows++;
	}
}
//======================
if (is_array($userBag)) {
    $mypairet = '';

    foreach ($userBag as $k => $rs) {
		if(!is_array($rs)) continue;
		$rsDefaults = array('id'=>0, 'pid'=>0, 'name'=>'', 'psum'=>0, 'psell'=>0, 'psj'=>0, 'pyb'=>0, 'petime'=>0, 'varyname'=>'');
		foreach($rsDefaults as $rsDefaultKey => $rsDefaultValue)
		{
			if(!isset($rs[$rsDefaultKey])) $rs[$rsDefaultKey] = $rsDefaultValue;
		}
		$rs = paiModIntFields($rs, array('id', 'pid', 'psum', 'psell', 'psj', 'pyb', 'petime', 'varyname'));
        // 检查物品是否有库存且是否有售价、交易价或第三个价
        if ($rs['psum'] > 0 && ($rs['psell'] > 0 || $rs['psj'] > 0 || $rs['pyb'] > 0)) {
            $str = ($rs['petime'] < time()) ? "已过期" : date("H:i:s", $rs['petime']);

            // 根据售价、交易价或第三个价的存在性来确定价格字符串
            if ($rs['psell'] > 0) {
                $pprice = $rs['psell'] . '金币';
            } elseif ($rs['psj'] > 0) {
                $pprice = $rs['psj'] . '水晶';
            } elseif ($rs['pyb'] > 0) {
                $pprice = $rs['pyb'] . '元宝'; // 假设第三个价是元宝
            } else {
                $pprice = '无价格'; // 如果没有任何价格信息，显示无价格
            }

            // 构建拍卖区显示拍卖物品
			$nameHtml = paiModHtml($rs['name']);
            $mypairet .= '<tr>
                <td width="35px"><img style="width:25px;height:25px;" src="../images/ui/bag/'.$rs['varyname'].'.gif" /></td>
                <td width="110px" id="t'.$rs['id'].'" style="cursor:pointer; text-align:left" onmouseover="showTip('.$rs['id'].',0,1,2);this.style.border=\'solid 1px #DFD496\';" onmouseout="window.parent.UnTip();this.style.border=\'\';" onclick="sel(this);pid = '.$rs['pid'].';bid='.($rs['id']?$rs['id']:0).';price='.$rs['psell'].';">'.$nameHtml.'('.intval($rs['psum']).')</td>
                <td width="60px" style="text-align:left">' . paiModHtml($pprice) . '</td>
                <td style="text-align:left">' . paiModHtml($str) . '</td>
            </tr>';
        }
    }

    if ($mypairet == '') {
        $mypairet .= '<tr><td colspan="4">暂时您还没有拍卖物品,拍卖后再来吧！！！</td></tr>';
    }
} else {
        $mypairet .= '<tr><td colspan="4">背包数据读取失败。</td></tr>';
}

// 输出或返回$mypairet
// echo $mypairet;


//Word part.
$taskword= taskcheck($user['task'],7);
$_pm['mem']->memClose();
unset($db);

if(empty($shop))
{
	$shop = "没有相应的物品！";
}
if(empty($bag))
{
	$bag = "您的背包中没有相应的物品！";
}

//@Load template.

$tn = $_game['template'] . 'tpl_pai.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);

	$src = array('#money#',
				 '#sj#',
				 '#yb#',
				 '#baglimit#',
				 '#shoplist#',
				 '#mybag#',
				 '#word#',
				 '#bagoption#',
				 '#baseoption#',
				 '#paimoney#',
				 '#myshoplist#',
				 '#sjwp#',
				 '#atype#',
				 '#paisj#',

				 '#ybwp#',
				 '#paiyb#',





				);
	$des = array($user['money'],
				 $sjarr['sj'],
				 $user['yb'],
				 $bg.'/'.$user['maxbag'],
				 $pairet,   //right part
				 $bag,
				 $taskword,
				 $bagoption,
				 $baseoption,
				 $user['paimoney'],
				 $mypairet,
				 $sjpairet,
				 $atype,
				 $sjarr['paisj'],

				 $ybpairet,
				 $sjarr['paiyb'],






				);
	$shop = str_replace($src,$des,$tpl);
}
// gzip echo. if maybe.
ob_start('ob_gzip');
echo $shop;
ob_end_flush();
?>
