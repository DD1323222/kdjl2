<?php
function logs($note,$vary=103)
{
	global $_pm;
	$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
	$noteSql = $_pm['mysql']->escape($note);
	$sql='insert into gamelog set seller='.$uid.',vary='.intval($vary).',pnote="'.$noteSql.'",ptime='.time();
	if(!$_pm['mysql']->query($sql))
	{
		return false;
	}
	return intval(mysql_insert_id($_pm['mysql']->getConn()));
}
function superJhFail($message)
{
	global $_pm, $superJhTransactionActive, $superJhLockHeld, $superJhPendingLogId;
	if($superJhTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$superJhTransactionActive = false;
	}
	if($superJhPendingLogId > 0)
	{
		$_pm['mysql']->query('DELETE FROM gamelog WHERE id='.intval($superJhPendingLogId));
		$superJhPendingLogId = 0;
	}
	if($superJhLockHeld && function_exists('realseLock'))
	{
		realseLock();
		$superJhLockHeld = false;
	}
	if(isset($_pm['mem'])) $_pm['mem']->memClose();
	die($message);
}
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');
secStart($_pm['mem']);

$superJhTransactionActive = false;
$superJhLockHeld = false;
$superJhPendingLogId = 0;

function superJhShutdown()
{
	global $_pm, $superJhTransactionActive, $superJhLockHeld, $superJhPendingLogId;
	$error = error_get_last();
	if(!is_array($error) || !in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true))
	{
		return;
	}
	if($superJhTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$superJhTransactionActive = false;
	}
	if($superJhPendingLogId > 0)
	{
		$_pm['mysql']->query('DELETE FROM gamelog WHERE id='.intval($superJhPendingLogId));
		$superJhPendingLogId = 0;
	}
	if($superJhLockHeld && function_exists('realseLock'))
	{
		realseLock();
		$superJhLockHeld = false;
	}
}
register_shutdown_function('superJhShutdown');

$uid=isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
$petId = (isset($_GET['pid']) && !is_array($_GET['pid'])) ? abs(intval($_GET['pid'])) : 0;
if($uid < 1 || $petId < 1) die('非法操作！');
$a = getLock($uid);
if(!is_array($a))
{
	realseLock();
	die('服务器繁忙，请稍候再试！');
}
$superJhLockHeld = true;
$superJhTransactionActive = true;
$bb = $_pm['mysql']->getOneRecord('select * from userbb where uid='.$uid.' and id='.$petId.' for update');
if(!$bb)
{
	superJhFail('这个宠物不是你的！');
}
if(intval($bb['muchang']) != 0 || intval($bb['tgflag']) != 0)
{
	superJhFail('该宠物当前状态不能进化！');
}
$bb['name'] = isset($bb['name']) ? $bb['name'] : '';
$bb['wx'] = isset($bb['wx']) ? intval($bb['wx']) : 0;
$bb['level'] = isset($bb['level']) ? intval($bb['level']) : 0;
$bb['czl'] = isset($bb['czl']) ? floatval($bb['czl']) : 0;
$bb['remaketimes'] = isset($bb['remaketimes']) ? intval($bb['remaketimes']) : 0;
$bb['remakelevel'] = isset($bb['remakelevel']) ? $bb['remakelevel'] : '';
$bb['remakeid'] = isset($bb['remakeid']) ? $bb['remakeid'] : '';
$bb['remakepid'] = isset($bb['remakepid']) ? $bb['remakepid'] : '';
$bb['old_bid'] = isset($bb['old_bid']) ? $bb['old_bid'] : 0;

if($bb['remaketimes']>=10)
{
	superJhFail('您的宠物已经达到该阶段进化上限，无法再进化了！');
}

if($bb['wx']!=7)
{
	superJhFail('请确认您的宠物是否为神圣宠物！');
}

$membbname = kdjlSafeMemValue($_pm['mem']->get('db_bbname'), array());
$membbid = kdjlSafeMemValue($_pm['mem']->get('db_bbid'), array());
if(!is_array($membbname)) $membbname = array();
if(!is_array($membbid)) $membbid = array();
$bbO = resolveBasePetForSuperJh($bb, $membbname, $membbid);

if(!$bbO)
{
	superJhFail('内存中找不到要进化的宠物的原始数据！');
}

$bbJhSetting = $_pm['mysql']->getOneRecord('select zs_progress,need_levels,need_props,max_czl from super_jh where pet_id='.$bbO['id']);
if(!$bbJhSetting)
{
	superJhFail('数据库中没有该宠物神圣进化的设定！');
}
$bbJhSetting['zs_progress'] = isset($bbJhSetting['zs_progress']) ? intval($bbJhSetting['zs_progress']) : 0;
$bbJhSetting['need_levels'] = isset($bbJhSetting['need_levels']) ? $bbJhSetting['need_levels'] : '';
$bbJhSetting['need_props'] = isset($bbJhSetting['need_props']) ? $bbJhSetting['need_props'] : '';
$bbJhSetting['max_czl'] = isset($bbJhSetting['max_czl']) ? floatval($bbJhSetting['max_czl']) : 0;
if($bbJhSetting['need_levels'] === '' || $bbJhSetting['need_props'] === '' || $bbJhSetting['max_czl'] <= 0)
{
	superJhFail('该宠物的神圣进化配置不完整！');
}

$p1 = (isset($_GET['zjsxdj']) && !is_array($_GET['zjsxdj'])) ? abs(intval($_GET['zjsxdj'])) : 0;
$v = false;
$zjsx=array();
if($p1 > 0)
{
	$sql = 'select p.effect,p.id pids,b.id,b.sums from userbag b,props p where b.id='.$p1.' and b.uid='.$uid.' and b.sums>0 and b.zbing=0 and (b.cantrade IS NULL OR b.cantrade<>3) and p.id=b.pid for update';
	$v = $_pm['mysql']->getOneRecord($sql);
	if(!$v)
	{
		superJhFail('附加属性道具不足！');
	}
	$v['effect'] = isset($v['effect']) ? $v['effect'] : '';
	$str=explode(':',$v['effect']);
	if(!isset($str[0]) || strpos($str[0], 'zjsxdj_') !== 0)
	{
		superJhFail('附加属性道具无效！');
	}
	$bonusKey = str_replace('zjsxdj_','',$str[0]);
	$bonusValue = preg_replace("/[^\d]/",'',$v['effect']);
	if(!in_array($bonusKey, array('hp','mp','ac','mc','speed','hits','miss'), true) || intval($bonusValue) <= 0)
	{
		superJhFail('附加属性道具无效！');
	}
	$zjsx[$bonusKey] = $bonusValue;
}

$nlvls = explode(',',$bbJhSetting['need_levels']);
if(count($nlvls)-1<$bb['remaketimes'])
{
	$limitlvl=$nlvls[0];
}else{
	$limitlvl=$nlvls[$bb['remaketimes']];
}
$limitlvl = trim($limitlvl);
if($limitlvl === '' || !ctype_digit($limitlvl) || intval($limitlvl) < 1)
{
	superJhFail('宠物进化等级配置错误！');
}
$limitlvl = intval($limitlvl);

if($bb['level']<$limitlvl)
{
	superJhFail('宠物等级('.$limitlvl.')不够，请先升级宠物！');
}

$nprops = explode(',',$bbJhSetting['need_props']);
if(count($nprops)-1<$bb['remaketimes'])
{
	$npropsIds=$nprops[0];
}else{
	$npropsIds=$nprops[$bb['remaketimes']];
}
$npropsIds = trim($npropsIds);
if($npropsIds === '')
{
	superJhFail('宠物进化材料配置错误！');
}

$gold=($bbJhSetting['zs_progress']+$bb['remaketimes'])*10000;
//当前成长+【宠物等级/宠物成长+（宠物等级/100*宠物转生阶段）】*（1-进化次数/100）+0.1
//$newCzl=$bb['czl']+($bb['level']/$bb['czl']+$bb['level']/100*$bbJhSetting['zs_progress'])*(1-$bb['remaketimes']/100)+0.1;
$player = $_pm['mysql']->getOneRecord('select id,money from player where id='.$uid.' for update');
if(!$player)
{
	superJhFail('读取玩家数据失败！');
}
$player['money'] = isset($player['money']) ? intval($player['money']) : 0;
if($player['money']<$gold){
	superJhFail('金币不足！');
}
if(!$_pm['mysql']->query('update player set money=money-'.$gold.' where id='.$uid.' and money>='.$gold) ||
	mysql_affected_rows($_pm['mysql']->getConn()) != 1)
{
	superJhFail('金币不足！');
}
if($p1 > 0 && is_array($v))
{
	if(!$_pm['mysql']->query('update userbag set sums=sums-1 where id='.$v["id"].' and uid='.$uid.' and sums>0 and zbing=0 and (cantrade IS NULL OR cantrade<>3)') ||
		mysql_affected_rows($_pm['mysql']->getConn()) != 1)
	{
		superJhFail('物品不足！');
	}
	$_pm['mysql']->query('delete from userbag where id='.$v["id"].' and uid='.$uid.' and sums<1 and psum<1 and bsum<1 and pyb=0 and zbing=0 and (cantrade IS NULL OR cantrade<>3)');
}

$mempropsid = kdjlSafeMemValue($_pm['mem']->get('db_propsid'), array());
if(!is_array($mempropsid)) $mempropsid = array();
$updatePropsLog='消耗物品：';
$npropsIds = explode('|',$npropsIds);

$perCzl=0;

foreach($npropsIds as $str)
{
	$items=explode(':',$str);
	if(count($items) < 2) superJhFail('所需道具配置错误！');
	$needPid = abs(intval($items[0]));
	$needNum = abs(intval($items[1]));
	if($needPid < 1 || $needNum < 1) superJhFail('所需道具配置错误！');
	$bags = $_pm['mysql']->getRecords('select id,pid,sums from userbag where pid='.$needPid.' and sums>0 and uid='.$uid.' and zbing=0 and (cantrade IS NULL OR cantrade<>3) order by sums desc,id asc for update');
	$bagTotal = 0;
	if(is_array($bags))
	{
		foreach($bags as $bag)
		{
			$bagTotal += intval($bag['sums']);
		}
	}
	if($bagTotal < $needNum)
	{
		superJhFail('物品不足！');
	}
	if(is_array($mempropsid) && isset($mempropsid[$needPid]) && is_array($mempropsid[$needPid]))
	{
		$mempropsid[$needPid]['effect'] = isset($mempropsid[$needPid]['effect']) ? $mempropsid[$needPid]['effect'] : '';
		if(strpos($mempropsid[$needPid]['effect'],'ssjh:')!==false)
		{
			$chance=explode('|',str_replace('ssjh:','',$mempropsid[$needPid]['effect']));
			if(count($chance) >= 2)
			{
				$chanceMin = intval($chance[0]*100);
				$chanceMax = intval($chance[1]*100);
				if($chanceMax < $chanceMin)
				{
					$chanceMid = $chanceMin;
					$chanceMin = $chanceMax;
					$chanceMax = $chanceMid;
				}
				$perCzl=rand($chanceMin,$chanceMax);
			}
		}
	}

	$left = $needNum;
	foreach($bags as $bag)
	{
		if($left <= 0) break;
		$take = min(intval($bag['sums']), $left);
		$sqlb='update userbag set sums=sums-'.$take.' where id='.intval($bag['id']).' and uid='.$uid.' and sums>='.$take.' and zbing=0 and (cantrade IS NULL OR cantrade<>3)';
		if(!$_pm['mysql']->query($sqlb) || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
		{
			superJhFail('物品不足！');
		}
		$_pm['mysql']->query('delete from userbag where id='.intval($bag['id']).' and uid='.$uid.' and sums<1 and psum<1 and bsum<1 and pyb=0 and zbing=0 and (cantrade IS NULL OR cantrade<>3)');
		$left -= $take;
	}
	$updatePropsLog.=$needPid.'='.$needNum.'个，';
}

//$newCzl=$bb['czl']*(1+$perCzl/100);
$newCzl=$bb['czl']+$perCzl/100;
if($newCzl>$bbJhSetting['max_czl'])
{
	$newCzl=$bbJhSetting['max_czl'];
}
$newCzl = number_format($newCzl,1,'.','');

$updatePropsLog.='金币：'.$player['money'].'->'.($player['money']-$gold).'，消耗：'.$gold.'，成长：'.$bb['czl'].'->'.$newCzl.'；';

$db_bb=$bb;
$db_bb['id'] = isset($db_bb['id']) ? intval($db_bb['id']) : 0;
$db_bb['uid'] = isset($db_bb['uid']) ? intval($db_bb['uid']) : $uid;
$db_bb['wx'] = isset($db_bb['wx']) ? intval($db_bb['wx']) : 0;
$db_bb['czl'] = isset($db_bb['czl']) ? floatval($db_bb['czl']) : 0;
$db_bb['remaketimes'] = isset($db_bb['remaketimes']) ? intval($db_bb['remaketimes']) : 0;
$db_bb['srchp'] = isset($db_bb['srchp']) ? intval($db_bb['srchp']) : 0;
$db_bb['srcmp'] = isset($db_bb['srcmp']) ? intval($db_bb['srcmp']) : 0;
$db_bb['hp'] = isset($db_bb['hp']) ? intval($db_bb['hp']) : $db_bb['srchp'];
$db_bb['mp'] = isset($db_bb['mp']) ? intval($db_bb['mp']) : $db_bb['srcmp'];
$db_bb['ac'] = isset($db_bb['ac']) ? intval($db_bb['ac']) : 0;
$db_bb['mc'] = isset($db_bb['mc']) ? intval($db_bb['mc']) : 0;
$db_bb['speed'] = isset($db_bb['speed']) ? intval($db_bb['speed']) : 0;
$db_bb['hits'] = isset($db_bb['hits']) ? intval($db_bb['hits']) : 0;
$db_bb['miss'] = isset($db_bb['miss']) ? intval($db_bb['miss']) : 0;
$wx_sx=$_pm['mysql']->getOneRecord('select * from wx where id='.$db_bb['wx']);
if(!$wx_sx || !is_array($wx_sx))
{
	superJhFail('查找宠物五行设定失败！');
}
$wx_sx['hp'] = isset($wx_sx['hp']) ? floatval($wx_sx['hp']) : 0;
$wx_sx['mp'] = isset($wx_sx['mp']) ? floatval($wx_sx['mp']) : 0;
$wx_sx['ac'] = isset($wx_sx['ac']) ? floatval($wx_sx['ac']) : 0;
$wx_sx['mc'] = isset($wx_sx['mc']) ? floatval($wx_sx['mc']) : 0;
$wx_sx['speed'] = isset($wx_sx['speed']) ? floatval($wx_sx['speed']) : 0;
$wx_sx['hits'] = isset($wx_sx['hits']) ? floatval($wx_sx['hits']) : 0;
$wx_sx['miss'] = isset($wx_sx['miss']) ? floatval($wx_sx['miss']) : 0;
if($db_bb['czl'] <= 0 || $wx_sx['hp'] <= 0 || $wx_sx['mp'] <= 0 || $wx_sx['ac'] <= 0 || $wx_sx['mc'] <= 0 || $wx_sx['speed'] <= 0 || $wx_sx['hits'] <= 0 || $wx_sx['miss'] <= 0)
{
	superJhFail('宠物成长配置错误！');
}

//$arrSx=array('hp','mp','ac','mc','speed','hits','miss');

//[当前属性*（0.3+进化次数/30）+当前属性*进化次数*超神阶段/（成长*7）]*（百分百+道具百分比）
$hp = round($db_bb['srchp']*(0.3+($db_bb['remaketimes']+1)/30+($db_bb['remaketimes']+1)*$bbJhSetting['zs_progress']/($db_bb['czl']*$wx_sx['hp'])));
$mp = round($db_bb['srcmp']*(0.3+($db_bb['remaketimes']+1)/30+($db_bb['remaketimes']+1)*$bbJhSetting['zs_progress']/($db_bb['czl']*$wx_sx['mp'])));
$ac = round($db_bb['ac']*(0.3+($db_bb['remaketimes']+1)/30+($db_bb['remaketimes']+1)*$bbJhSetting['zs_progress']/($db_bb['czl']*$wx_sx['ac'])));
$mc = round($db_bb['mc']*(0.3+($db_bb['remaketimes']+1)/30+($db_bb['remaketimes']+1)*$bbJhSetting['zs_progress']/($db_bb['czl']*$wx_sx['mc'])));
$speed = round($db_bb['speed']*(0.3+($db_bb['remaketimes']+1)/30+($db_bb['remaketimes']+1)*$bbJhSetting['zs_progress']/($db_bb['czl']*$wx_sx['speed'])));
$hits = round($db_bb['hits']*(0.3+($db_bb['remaketimes']+1)/30+($db_bb['remaketimes']+1)*$bbJhSetting['zs_progress']/($db_bb['czl']*$wx_sx['hits'])));
$miss = round($db_bb['miss']*(0.3+($db_bb['remaketimes']+1)/30+($db_bb['remaketimes']+1)*$bbJhSetting['zs_progress']/($db_bb['czl']*$wx_sx['miss'])));
$logCurrent='进化前属性：hp='.$db_bb['hp'].',mp='.$db_bb['mp'].',ac='.$db_bb['ac'].',mc='.$db_bb['mc'].',speed='.$db_bb['speed'].',hits='.$db_bb['hits'].',miss='.$db_bb['miss'].';';
foreach($zjsx as $k=>$v)
{
	$rate = 1 + $v / 100;
	switch($k)
	{
		case 'hp': $hp *= $rate; break;
		case 'mp': $mp *= $rate; break;
		case 'ac': $ac *= $rate; break;
		case 'mc': $mc *= $rate; break;
		case 'speed': $speed *= $rate; break;
		case 'hits': $hits *= $rate; break;
		case 'miss': $miss *= $rate; break;
		default: superJhFail('附加属性道具无效！');
	}
}

//$_pm['mysql']->query('update userbb set  where id='.$petId);
$sqlsx="UPDATE userbb
			   SET
				   remaketimes=remaketimes+1,
				   level=1,czl=".$newCzl.",
				   lexp=100,
				   nowexp=0,
				   ac	=	{$ac},
				   mc	=	{$mc},
				   srchp=	{$hp},
				   hp	=	{$hp},
				   srcmp=	{$mp},
				   mp	=	{$mp},
				   hits	=	{$hits},
				   miss	=	{$miss},
				   speed=	{$speed}
			 WHERE id={$db_bb['id']} and uid={$db_bb['uid']}
		   ";
$petSaved = $_pm['mysql']->query($sqlsx);

if(!$petSaved)
{
	$updatePropsLog='保存失败，已回滚数据：'.$updatePropsLog;
	if($superJhTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$superJhTransactionActive = false;
	}
	logs($updatePropsLog);
	if($superJhLockHeld && function_exists('realseLock'))
	{
		realseLock();
		$superJhLockHeld = false;
	}
	$_pm['mem']->memClose();
	die('神圣进化保存失败！');
}else{
	if(mysql_affected_rows($_pm['mysql']->getConn()) != 1)
	{
		superJhFail('神圣进化保存失败！');
	}
	$superJhPendingLogId = logs($updatePropsLog.'<br>'.$logCurrent.'<br/>属性变化SQL:'.$sqlsx);
	if(!$superJhPendingLogId)
	{
		superJhFail('神圣进化日志保存失败！');
	}
}
if(!$_pm['mysql']->query('COMMIT'))
{
	superJhFail('神圣进化保存失败！');
}
$superJhTransactionActive = false;
$superJhPendingLogId = 0;
$_pm['mem']->del(MEM_USER_KEY);
$_pm['mem']->del(MEM_USERBB_KEY);
$_pm['mem']->del(MEM_USERBAG_KEY);


function resolveBasePetForSuperJh($pet, $byName, $byId)
{
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
realseLock();
$superJhLockHeld = false;
$_pm['mem']->memClose();
die('OK');
?>
