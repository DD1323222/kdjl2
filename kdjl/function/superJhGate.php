<?php 
function logs($note,$vary=103)
{
	global $_pm;
	$sql='insert into gamelog set seller='.$_SESSION['id'].',vary='.intval($vary).',pnote="'.$note.'",ptime='.time();
	$_pm['mysql']->query($sql);
}
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');
secStart($_pm['mem']);

$petId=abs($_GET['pid']);
getLock($_SESSION['id']);

$bb = $_pm['mysql']->getOneRecord('select name,wx,level,czl,remaketimes from userbb where uid='.$_SESSION['id'].' and id='.$petId);
if(!$bb)
{
	realseLock();
	die('这个宠物不是你的！');
}

if($bb['remaketimes']>=10)
{
	realseLock();
	die('您的宠物已经达到该阶段进化上限，无法再进化了！');
}

if($bb['wx']!=7)
{
	realseLock();
	die('请确认您的宠物是否为神圣宠物！');
}

$membbid = unserialize($_pm['mem']->get('db_bbname'));
$bbO = $membbid[$bb['name']];

if(!$bbO)
{
	realseLock();
	die('内存中找不到要进化的宠物的原始数据！');
}

$bbJhSetting = $_pm['mysql']->getOneRecord('select zs_progress,need_levels,need_props,max_czl from super_jh where pet_id='.$bbO['id']);
if(!$bbJhSetting)
{
	realseLock();
	die('数据库中没有该宠物神圣进化的设定！');
}

$p1=abs($_GET['zjsxdj']);
$sql = 'select p.effect,p.id pids,b.id,b.sums from userbag b,props p where b.id='.$p1.' and p.id=b.pid';
$v = $_pm['mysql']->getOneRecord($sql);
$zjsx=array();
if($v)
{
	$str=explode(':',$v['effect']);
	$zjsx[str_replace('zjsxdj_','',$str[0])]=preg_replace("/[^\d]/",'',$v['effect']);
	$sqlDel = 'update userbag set sums='.($v["sums"]-1).' where id='.$v["id"].' and sums>0';
	$_pm['mysql']->query($sqlDel);
}

$nlvls = explode(',',$bbJhSetting['need_levels']);
if(count($nlvls)-1<$bb['remaketimes'])
{
	$limitlvl=$nlvls[0];
}else{
	$limitlvl=$nlvls[$bb['remaketimes']];
}

if($bb['level']<$limitlvl)
{
	realseLock();
	die('宠物等级('.$limitlvl.')不够，请先升级宠物！');
}

$nprops = explode(',',$bbJhSetting['need_props']);
if(count($nprops)<$bb['remaketimes']) 
{
	$npropsIds=$nprops[0];
}else{
	$npropsIds=$nprops[$bb['remaketimes']];
}

$gold=($bbJhSetting['zs_progress']+$bb['remaketimes'])*10000;
//当前成长+【宠物等级/宠物成长+（宠物等级/100*宠物转生阶段）】*（1-进化次数/100）+0.1
//$newCzl=$bb['czl']+($bb['level']/$bb['czl']+$bb['level']/100*$bbJhSetting['zs_progress'])*(1-$bb['remaketimes']/100)+0.1;
$player = $_pm['mysql']->getOneRecord('select id,money from player where id='.$_SESSION['id'].' for update');
if(!$player)
{
	realseLock();
	die('读取玩家数据失败！');
}else if($player['money']<$gold){
	realseLock();
	die('金币不足！');
}
$_pm['mysql']->query('update player set money='.($player['money']-$gold).' where id='.$_SESSION['id']);

$mempropsid = unserialize($_pm['mem']->get('db_propsid'));
$updatePropsLog='消耗物品';
$npropsIds = explode('|',$npropsIds);

$perCzl=0;

foreach($npropsIds as $str)
{
	$items=explode(':',$str);
	$bag = $_pm['mysql']->getOneRecord('select id,pid,sums from userbag where pid='.abs($items[0]).' and sums>='.abs($items[1]).' and uid='.$_SESSION['id'].' limit 1 for update');
	if($mempropsid[$bag['pid']])
	{
		if(strpos($mempropsid[$bag['pid']]['effect'],'ssjh:')!==false)
		{
			$chance=explode('|',str_replace('ssjh:','',$mempropsid[$bag['pid']]['effect']));
			$perCzl=rand($chance[0]*100,$chance[1]*100);
		}
	}

	if(!$bag)
	{
		$_pm['mysql']->query("rollback");
		realseLock();
		die('物品不足！');
	}else{
		$sqlb='update userbag set sums='.($bag['sums']-abs($items[1])).' where id='.$bag['id'];
		$_pm['mysql']->query($sqlb);
		//echo $sqlb.mysql_error();
		$updatePropsLog.=$bag['pid'].'='.abs($items[1]).'个，';
	}
}

//$newCzl=$bb['czl']*(1+$perCzl/100);
$newCzl=$bb['czl']+$perCzl/100;
if($newCzl>$bbJhSetting['max_czl'])
{
	$newCzl=$bbJhSetting['max_czl'];
}
$newCzl = number_format($newCzl,1,'.','');

$updatePropsLog.='金币：'.$player['money'].'（现有），减少：'.$gold.'，成长：'.$bb['czl'].'->'.$newCzl.'。';

$db_bb=$_pm['user']->getUserPetByIdS($_SESSION['id'],$petId);
$wx_sx=$_pm['mysql']->getOneRecord('select * from wx where id='.$db_bb['wx']);

if(!$wx_sx)
{
	$_pm['mysql']->query("rollback");
	realseLock();
	die('查找宠物五行设定失败！');
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
	$$k*=1+$v/100;
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
$_pm['mysql']->query($sqlsx);

if($e=mysql_error())
{
	$updatePropsLog='操作失败，执行回滚，数据：'.$updatePropsLog;
	$_pm['mysql']->query("rollback");
	realseLock();
	logs($updatePropsLog);
	echo $sqlsx."<br>";
	die('发生错误'.$e.'！');
}else{
	logs($updatePropsLog.'<br>'.$logCurrent.'<br/>属性变化SQL:'.$sqlsx);
}
		   
realseLock();
die('OK');
?>