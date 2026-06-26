<?php
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');
secStart($_pm['mem']);
function logs($note,$vary=103)
{
	global $_pm;
	$sql='insert into gamelog set seller='.$_SESSION['id'].',vary='.intval($vary).',pnote="'.$note.'",ptime='.time();
	$_pm['mysql']->query($sql);
}
$petId=abs($_GET['pid']);
getLock($_SESSION['id']);

$cishu=$_pm['mysql']->getOneRecord("select chouqu_chongwu from player_ext where uid={$_SESSION['id']}");
if(strpos($cishu['chouqu_chongwu'],','.$petId.',')!==false)
{
	realseLock();
	die("这个宠物抽取过成长,不能再抽取!");
}

$bb = $_pm['mysql']->getOneRecord('select name,wx,level,czl,remaketimes from userbb where uid='.$_SESSION['id'].' and id='.$petId);
if(!$bb)
{
	realseLock();
	die('这个宠物不存在！');
}
if($bb['wx']>6)
{
	realseLock();
	die('该宠物不能抽取!');
}


/*
if($bb['wx']!=6)
{
	if($bb['czl']<40){
		realseLock();
		die('五系宠物成长小于40不能抽取!');
	}
}
*/

if($bb['czl']<30)
{
	realseLock();
	die('成长小于30的不能抽取！');
}

$p1=abs($_GET['pid1']);
$p2=abs($_GET['pid2']);

$sql = 'select p.effect,p.id pids,b.id,b.sums from userbag b,props p where (b.id='.$p1.' or b.id='.$p2.') and p.id=b.pid';
$rows = $_pm['mysql']->getRecords($sql);
if($bb['wx']!=6&&$rows[0]['pids']!=3383&&$rows[1]['pids']!=3383)
{
	realseLock();
	die('缺少五系宠物抽取的必须道具！');
}

$swapRateInc=0;
$swapRateIncFixed=0;
$wpLog='';
if(count($rows)>0)
{
	foreach($rows as $k=>$v)
	{
		if($p1==$p2&&$v["pids"]==3383)
		{
			$_pm['mysql']->query("rollback");
			die('请不要使用两个五系宠物抽取石！');
		}
		if($bb['wx']>5&&$v["pids"]==3383)
		{
			$_pm['mysql']->query("rollback");
			die('非五系宠物不能使用五系宠物抽取石！');
		}
		if($bb['wx']<6&&$v["pids"]!=3383)
		{
			$_pm['mysql']->query("rollback");
			die('五系宠物不能使用增加比例道具！');
		}
		if(strpos($v["effect"],'inczhl:')!==false&&$v["sums"]>0)
		{
			$str = str_replace('inczhl:','',$v["effect"]);
			if(strpos($str,'a')===false){
				$swapRateInc+=abs($str);
			}else{
				$swapRateIncFixed+=abs($str);
			}
			$wpLog.=' ['.$v["pids"].'] ';
			$sqlDel = 'update userbag set sums='.($v["sums"]-1).' where id='.$v["id"].' and sums>0';
			$_pm['mysql']->query($sqlDel);
			$v["sums"]-=1;
			if($p1==$p2)//选择同一个物品
			{
				if(strpos($v["effect"],'inczhl:')!==false&&$v["sums"]>0)
				{
					$str = str_replace('inczhl:','',$v["effect"]);
					if(strpos($str,'a')===false){
						$swapRateInc+=abs($str);
					}else{
						$swapRateIncFixed+=abs($str);
					}
					
					$wpLog.=' ['.$v["pids"].'] ';
					$sqlDel = 'update userbag set sums='.($v["sums"]-1).' where id='.$v["id"].' and sums>0';
					$_pm['mysql']->query($sqlDel);
				}
			}
		}
	}
}

if($bb['czl']<65)
{
	$swapRate=rand(10,20);
}
else if($bb['czl']<85)
{
	$swapRate=rand(30,50);
}
else if($bb['czl']<100)
{
	$swapRate=rand(50,65);
}
else if($bb['czl']<110)
{
	$swapRate=65;
}
else if($bb['czl']<115)
{
	$swapRate=70;
}
else if($bb['czl']<120)
{
	$swapRate=75;
}
else
{
	$swapRate=80;
}

if($bb['wx']!=6)
{
	$swapRate=rand(5,15);
}

$swapRate+=$swapRateInc;

if($bb['czl']*($swapRate/100)>600)
{
	$czl=600/$swapRate*100;
}else{
	$czl=$bb['czl'];
}
if($swapRate>100)
{
	$swapRate=100;
}

if($bb['czl']<600)
{
	$money=$bb['czl']*10000;
}else{
	$money=6000000;
}

$czl=ceil($czl*($swapRate/100));//进行转换

$czl+=$swapRateIncFixed;
if($czl>600) $czl=600;
$rowP=$_pm['mysql']->getOneRecord('select money from player where id='.$_SESSION['id'].' for update');
if($rowP['money']<$money)
{
	$_pm['mysql']->query("rollback");
	die("您的金币不足(需要:".$money.")");
}

$sql = 'update player set money='.number_format($rowP['money']-$money,0,'.','').' where id='.$_SESSION['id'];
$_pm['mysql']->query($sql);

$couqu_res = $_pm['mysql'] -> getOneRecord("SELECT chouqu_chongwu FROM player_ext where uid= ".$_SESSION['id']);
if(empty($couqu_res['chouqu_chongwu']) )
{
	$sql = 'update player_ext set czl_ss=czl_ss+'.abs($czl).',chouqu_chongwu="'.",".$petId.",".'" where uid='.$_SESSION['id'];
	$_pm['mysql']->query($sql);
}
else
{
	$sql = 'update player_ext set czl_ss=czl_ss+'.abs($czl).',chouqu_chongwu=concat(chouqu_chongwu,",","'.$petId.'",",") where uid='.$_SESSION['id'];
	$_pm['mysql']->query($sql);
}

if($err=mysql_error())
{
	if(strpos($err,'czl_ss')!==false||strpos($err,'chouqu_chongwu')!==false)
	{
		$_pm['mysql']->query('alter table player_ext add czl_ss int(11) null default 0;              ');
		$_pm['mysql']->query('alter table player_ext add chouqu_chongwu varchar(255) null default "";');
		$_pm['mysql']->query($sql);
	}
}

if($bb['wx']<6){
	$_pm['mysql']->query('update userbb set name=concat(name,"-",uid),uid=0 where id='.$petId);
}else{
	$_pm['mysql']->query('update userbb set czl=1 where id='.$petId);
}
logs('被抽取的宠物id='.$petId.',抽取了:'.abs($czl).',使用物品'.($wpLog==''?'无':$wpLog));

if($err=mysql_error())
{
	$_pm['mysql']->query("rollback");
	die($err);
}

realseLock();
die('OK'.$czl);
?>