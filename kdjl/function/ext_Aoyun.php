<?php
/**
@Usage: 获取奥运礼包
经验奖励：exp=积分*玩家主战宠物等级*1000
819:28,820:28,821:28,822:56,823:28,824:28,825:28,826:56,827:28,828:28,829:28,830:56,831:28,832:28,833:28,834:56,835:28,836:28,837:28,838:56,839:28,840:28,841:28,842:56,843:28,844:28,845:28,846:56,818:1
*/
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');
require_once(dirname(__FILE__).'/aoyun_common.php');
secStart($_pm['mem']);

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
$aoyunTransactionActive = false;
function aoyunPrizeShutdown()
{
	global $_pm,$aoyunTransactionActive;
	if($aoyunTransactionActive && isset($_pm['mysql']))
	{
		$_pm['mysql']->query('ROLLBACK');
		$aoyunTransactionActive = false;
	}
	realseLock();
}
if($uid < 1) die("10");
$user		= $_pm['user']->getUserById($uid);
if(!is_array($user)) die("10");
//$bag		= $_pm['user']->getUserBagById($_SESSION['id']);
$action = (isset($_REQUEST['action']) && !is_array($_REQUEST['action'])) ? $_REQUEST['action'] : '';
$props		= kdjlSafeMemValue($_pm['mem']->get(MEM_PROPS_KEY), array());
if(!is_array($props)) $props = array();
$timearr1 = kdjlSafeMemValue($_pm['mem']->get(MEM_TIMENEW_KEY), array());
$timearr = (is_array($timearr1) && isset($timearr1['dati']) && is_array($timearr1['dati'])) ? $timearr1['dati'] : array();
$now = time();
$dateIsOpen = kdjlAoyunDateIsOpen($timearr, $now);
$checktime = kdjlAoyunActiveWindow($timearr, $now) !== false ? 1 : 0;

$prize = (is_array($timearr1) && isset($timearr1['datiprops']) && is_array($timearr1['datiprops'])) ? $timearr1['datiprops'] : array();
$prizearr = array();
foreach($prize as $v)
{
	if(!is_array($v) || !isset($v['starttime'], $v['endtime'], $v['days'])) continue;
	$prizeKey = trim((string)$v['starttime']);
	if($prizeKey !== '') $prizearr[$prizeKey] = $v;
}

if(!$dateIsOpen)
{
	die(100);
}

// time limit end.
if($checktime != 1)
{
	die('不在领奖时间内！');
}

if($action == "answer")
{
	die("1");
}
else{
	if(!is_array(getLock($uid)))
	{
		realseLock();
		die("10");
	}
	register_shutdown_function('aoyunPrizeShutdown');
	$aoyunTransactionActive = true;
	$lockedUser = $_pm['mysql']->getOneRecord("SELECT mbid FROM player WHERE id={$uid} FOR UPDATE");
	if(!is_array($lockedUser) || intval($lockedUser['mbid']) < 1)
	{
		$_pm['mysql']->query('ROLLBACK');
		die('您必须先到牧场设置主战宠物，否则不能获得奖励经验噢!');
	}
	$rs = $_pm['mysql']->getOneRecord("SELECT *
										 FROM aoyun_player
										WHERE uid={$uid}
									 ORDER BY id LIMIT 1
										FOR UPDATE
									 ");
	if(!is_array($rs) || $rs['oksum'] == 0 || $rs['qsums'] < 30)
	{
		$_pm['mysql']->query('ROLLBACK');
		die("10");
	}
	$bb = $_pm['mysql']->getOneRecord("SELECT level,id
											 FROM userbb
											WHERE uid={$uid} and id=".intval($lockedUser['mbid'])."
											FOR UPDATE");
	if (!is_array($bb)) $_pm['mysql']->query('ROLLBACK');
	if (!is_array($bb)) die('您必须先到牧场设置主战宠物，否则不能获得奖励经验噢!');
	if (is_array($rs))
	{
		if ($rs['times'] > 0 && $rs['result']==1 && $rs['qsums']>=30)
		{
			$str = '';
			$exp = 0;
			$oksum = intval($rs['oksum']);
			if($oksum <= 5) $prizeKey = '0-5';
			else if($oksum <= 13) $prizeKey = '6-13';
			else if($oksum <= 22) $prizeKey = '14-22';
			else if($oksum <= 29) $prizeKey = '23-29';
			else $prizeKey = '30';
			if(!isset($prizearr[$prizeKey]) || !isset($prizearr[$prizeKey]['endtime']) || !isset($prizearr[$prizeKey]['days']))
			{
				$_pm['mysql']->query('ROLLBACK');
				die("10");
			}
			$expFactor = intval($prizearr[$prizeKey]['endtime']);
			if($expFactor < 1 || intval($bb['level']) < 1)
			{
				$_pm['mysql']->query('ROLLBACK');
				die("10");
			}
			$exp = $oksum*$expFactor*intval($bb['level']);
			$propsById = array();
			foreach($props as $p)
			{
				if(is_array($p) && isset($p['id'])) $propsById[intval($p['id'])] = $p;
			}
			$arr = explode('|',$prizearr[$prizeKey]['days']);
			$task = new task();
			foreach($arr as $v)
			{
				$newarr = explode(':',trim($v));
				if(count($newarr) != 3)
				{
					$_pm['mysql']->query('ROLLBACK');
					die("10");
				}
				$pid = intval($newarr[0]);
				$chance = intval($newarr[1]);
				$num = intval($newarr[2]);
				if($pid < 1 || $chance < 1 || $num < 1 || !isset($propsById[$pid]))
				{
					$_pm['mysql']->query('ROLLBACK');
					die("10");
				}
				if(rand(1,$chance) != 1) continue;
				$propsOk = $task->saveGetPropsMore($pid,$num);
				if($propsOk !== true)
				{
					$_pm['mysql']->query('ROLLBACK');
					die($propsOk === "200" ? "200" : "10");
				}
				$pname = isset($propsById[$pid]['name']) ? $propsById[$pid]['name'] : $pid;
				$str .= $pname."&nbsp;".$num."个,";
			}

			if($task->saveExps($exp,$bb['id']) === false){
				$_pm['mysql']->query('ROLLBACK');
				die("10");
			}
			$str1 = $str === '' ? '' : substr($str,0,-1);

			//times limit.
			if ($checktime == 1) $tm = 1;
			else $tm=2;

			$rowId = isset($rs['id']) ? intval($rs['id']) : 0;
			if($rowId < 1 || !$_pm['mysql']->query("UPDATE aoyun_player
									 SET result=0,
										 times={$tm}
									WHERE id={$rowId} AND uid={$uid} AND result=1
								") || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
				$_pm['mysql']->query('ROLLBACK');
				die("10");
			}
			if(!$_pm['mysql']->query('COMMIT')){
				$_pm['mysql']->query('ROLLBACK');
				die("10");
			}
			$aoyunTransactionActive = false;
			$_pm['mem']->del(MEM_USERBB_KEY);
			$_pm['mem']->del(MEM_USERBAG_KEY);
			$task->saveGword("通过皇宫的<知识问答>获得了大量经验及&nbsp;".$str1);
			// Rand get props.

			$newstr = "恭喜您获得".$str.$exp."经验";
			die($newstr);
		}
		else
		{
			$_pm['mysql']->query('ROLLBACK');
			die('您已经领取过或时间段已经过期，请参看帮助说明！');
		}
	}
}

$_pm['mem']->memClose();
//####################

?>
