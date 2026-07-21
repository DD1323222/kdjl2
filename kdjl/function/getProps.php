<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.20
*@Update Date: 2008.05.30
*@Usage:Get User props.
*@Note:
*/
require_once('../config/config.game.php');
secStart($_pm['mem']);

$err = 0;
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid <= 0) die('玩家数据错误！');
$id = (isset($_REQUEST['id']) && !is_array($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0;

if ($id<1) die('道具编号错误！');
del_bag_expire();
$user		= $_pm['user']->getUserById($uid);
$BAG		= $_pm['user']->getUserBagById($uid);
if(!is_array($BAG)) $BAG = array();
$wp			= false;
foreach ($BAG as $k => $v)
{
	if (!is_array($v)) continue;
	$rowUid = isset($v['uid']) ? intval($v['uid']) : 0;
	$rowId = isset($v['id']) ? intval($v['id']) : 0;
	$rowZbing = isset($v['zbing']) ? intval($v['zbing']) : 0;
	$rowCantrade = isset($v['cantrade']) ? intval($v['cantrade']) : 0;
	if ($rowUid == $uid && $rowId == $id && $rowZbing == 0 && $rowCantrade != 3)
	{
		$wp = $v;
		break;
	}
}
/*if($_SESSION['username']=="leinchu"){
	$lein_dbg = true;
}
*/
if (!is_array($wp) || !isset($wp['sums']) || $wp['sums']<1) die('道具数据错误！');
else
{
	if(!$_pm['mysql']->query('START TRANSACTION'))
	{
		die('道具数据错误！');
	}
	$itemUsed = $_pm['mysql']->query("UPDATE userbag
							 SET sums=sums-1
						   WHERE uid={$uid} and id={$id} and sums > 0 and zbing=0
						     and (cantrade IS NULL OR cantrade<>3)
						");
	if(!$itemUsed || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
	{
		$_pm['mysql']->query('ROLLBACK');
		die('道具数据错误！');
	}
	if(!$_pm['mysql']->query("DELETE FROM userbag WHERE uid={$uid} and id={$id} and sums<=0 and bsum<=0 and psum<=0 and pyb=0 and zbing=0 and (cantrade IS NULL OR cantrade<>3)"))
	{
		$_pm['mysql']->query('ROLLBACK');
		die('道具数据错误！');
	}
	$err = getValue($uid, $id, isset($wp['effect']) ? $wp['effect'] : '');
	if($err === 'hasusemedbuff')
	{
		$_pm['mysql']->query('ROLLBACK');
		die('hasusemedbuff');
	}
	if($err === false)
	{
		$_pm['mysql']->query('ROLLBACK');
		die('道具数据错误！');
	}
	if(!$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		die('道具数据错误！');
	}
	$_pm['mem']->del(MEM_USER_KEY);
	$_pm['mem']->del(MEM_USERBB_KEY);
	$_pm['mem']->del(MEM_USERBAG_KEY);
	//$_pm['user']->updateMemUserbag($_SESSION['id']);
}
$_pm['mem']->memClose();
echo $err;

// Get effect value
// 目前开放加MP,HP。
function getValue($uid,$n,$effect)
{
	global $_pm,$BAG;
	$hp = $mp = 0;
	$needFirstIn = false;
	$buff['addac'] = $buff['addmc'] = 0;
	$effect = is_string($effect) ? $effect : '';
	$arr = explode(',', $effect);
	foreach ($arr as $k => $v)
	{
		$tarr = explode(':', $v);
		$key = isset($tarr[0]) ? $tarr[0] : '';
		switch ($key)
		{
			case "hp": $hp = isset($tarr[1]) ? intval($tarr[1]) : 0;break;
			case "mp": $mp = isset($tarr[1]) ? intval($tarr[1]) : 0;break;
			case "addac":
			{
				$buff['addac'] = isset($tarr[1]) ? intval($tarr[1]) : 0;
				break;
			}
			case "addmc":
			{
				$buff['addmc'] = isset($tarr[1]) ? intval($tarr[1]) : 0;
				break;
			}

			default:;
		}
		unset($tarr);
	}
	if( $buff['addac'] > 0 || $buff['addmc'] > 0 )
	{
		$med_buff_info = $_pm['mysql']->getOneRecord(" SELECT F_Medicine_Buff FROM player_ext WHERE uid = {$uid} FOR UPDATE");
		if(!is_array($med_buff_info)) $med_buff_info = array('F_Medicine_Buff' => '');
		$effectSql = $_pm['mysql']->escape($effect);

		if(  $med_buff_info['F_Medicine_Buff'] == '' )	//从未使用过
		{
			if(!$_pm['mysql'] -> query("INSERT INTO player_ext(uid,bbshow,F_Medicine_Buff)
									     VALUES ({$uid},5,'{$effectSql}')
				ON DUPLICATE KEY UPDATE F_Medicine_Buff=VALUES(F_Medicine_Buff)") ||
				mysql_affected_rows($_pm['mysql']->getConn()) < 1) return false;
			$needFirstIn = true;
		}
		else
		{	//addac:10000,addmc:10000
			foreach( $buff as $key => $val )
			{
				if( $buff[$key] != 0 )
				{
					if( strstr($med_buff_info['F_Medicine_Buff'],$key) )
					{
						return 'hasusemedbuff';	//有类似属性了
					}
				}
			}
			//遍历完没有类似属性
			$buff_set = $med_buff_info['F_Medicine_Buff'].','.$effect;
			$buffSetSql = $_pm['mysql']->escape($buff_set);
			if(!$_pm['mysql'] -> query(" UPDATE player_ext SET F_Medicine_Buff = '{$buffSetSql}' WHERE uid = {$uid}") || mysql_affected_rows($_pm['mysql']->getConn()) != 1) return false;
			$needFirstIn = true;
			unset($med_buff_info);
		}

	}
	$fit= isset($_SESSION['fight'.$uid]) && is_array($_SESSION['fight'.$uid]) ? $_SESSION['fight'.$uid] : array();

	if (!is_array($fit)) return false;
	$fitBid = isset($fit['bid']) ? intval($fit['bid']) : 0;
	if($fitBid < 1) return false;
	$pet = $_pm['mysql']->getOneRecord("SELECT id,uid,hp,mp,srchp,srcmp,addhp,addmp FROM userbb WHERE id={$fitBid} AND uid={$uid} FOR UPDATE");
	if(!is_array($pet)) return false;
	$curHp = intval($pet['hp']);
	$curMp = intval($pet['mp']);
	$srcHp = max(0, intval($pet['srchp']));
	$srcMp = max(0, intval($pet['srcmp']));
	$curAddHp = max(0, intval($pet['addhp']));
	$curAddMp = max(0, intval($pet['addmp']));
	$hp = max(0, intval($hp));
	$mp = max(0, intval($mp));
	$newhp = min($srcHp, $curHp + $hp);
	$newmp = min($srcMp, $curMp + $mp);
	$addHPSql="";
	$addMPSql="";
	$equipment = false;
	$hpOverflow = max(0, $curHp + $hp - $srcHp);
	if($hpOverflow > 0)
	{
		$equipment = getzbAttrib($fitBid);
		if(!is_array($equipment)) $equipment = array();
		$maxAddHp = isset($equipment['hp']) ? max(0, intval($equipment['hp'])) : 0;
		if($maxAddHp > 0) $addHPSql = ',addhp='.min($maxAddHp, $curAddHp + $hpOverflow);
	}
	$mpOverflow = max(0, $curMp + $mp - $srcMp);
	if($mpOverflow > 0)
	{
		if(!is_array($equipment)) $equipment = getzbAttrib($fitBid);
		if(!is_array($equipment)) $equipment = array();
		$maxAddMp = isset($equipment['mp']) ? max(0, intval($equipment['mp'])) : 0;
		if($maxAddMp > 0) $addMPSql = ',addmp='.min($maxAddMp, $curAddMp + $mpOverflow);
	}

	$sql = "UPDATE userbb
				   SET hp={$newhp},
					   mp={$newmp}".$addMPSql.$addHPSql."
				 WHERE uid={$uid} and id={$fitBid}
			  ";
			  /*if($_SESSION['id'] == '261619'){
				echo $sql;
			  }*/
	// Update bb info.
	if(!$_pm['mysql']->query($sql)) return false;
	if($needFirstIn) $_SESSION['first_in'] = 1;
	$fit['fuzu']=1;
	$_SESSION['fight'.$uid]=$fit;
	return $hp.','.$mp.','.$buff['addac'].','.$buff['addmc'];
}
?>
