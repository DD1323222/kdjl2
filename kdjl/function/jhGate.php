<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.19
*@Update Date: 2008.05.27
*@Usage: jinhua  user bb.
*@Memo: Add two format of jinhua for bb.
*/
session_start();

require_once('../config/config.game.php');
secStart($_pm['mem']);


$srctime = 2;
#################增加一个间隔时间################
$time = $_SESSION['paitimes'.$_SESSION['id']];

if(empty($time))
{	
	$_SESSION['paitimes'.$_SESSION['id']] = time();
}
else
{
	$nowtime = time();
	$ctime = $nowtime - $time;
	if($ctime < $srctime)
	{
		unLockItem($id);
		die("100");//没有达到间隔时间
	}
	else
	{
		$_SESSION['paitimes'.$_SESSION['id']] = time();
	}
}

$id	   = intval($_REQUEST['id']); // table: userbb => id
$style = intval($_REQUEST['n']); // style of jinhua.
$pids = intval($_REQUEST['pids']);//使用的必须材料

$cishu=$_pm['mysql']->getOneRecord("select chouqu_chongwu from player_ext where uid={$_SESSION['id']}");
if(strpos($cishu['chouqu_chongwu'],','.$id.',')!==false)
{
	die("该宠物抽取过成长,不能进行进化!");
}

if(lockItem($id) === false)
{
	unLockItem($id);
	die('已经在处理了！');
}

if ($id<1 || ($style!=1 && $style!=2))
{
	unLockItem($id);
	die('0');
}

$user		= $_pm['user']->getUserById($_SESSION['id']);
$userbb		= $_pm['user']->getUserPetById($_SESSION['id']);
$userbag	= $_pm['user']->getUserBagById($_SESSION['id']);
$bb			= unserialize($_pm['mem']->get(MEM_BB_KEY));

$bhid=intval($_GET['bhid']);
$bhEffect=false;
$bhPNewNum=0;
foreach($userbag as $v)
{
	if($v['id']==$bhid&&$v['sums']>0)
	{
		$bhEffect=intval(str_replace('keepczl:','',$v['effect']));
		$bhPNewNum=$v['sums']-1;
		if($bhEffect<150)
		{
			$bhEffect=false;
		}
	}
}

//JinHua(1,2309,748)
if ($user['money'] < 1000)
{
	unLockItem($id);
	die("5");
}


if (is_array($bb) && is_array($userbb) && is_array($userbag))
{
	foreach ($bb as $k => $v)
	{
		foreach ($userbb as $uk => $uv)
		{
			if ($uv['name'] == $v['name'] && $uv['id']==$id) // From bb base find user current bb.
			{
				if ($v['remakeid'] == '0,0' && $v['remakepid']=='0,0'){
				echo 4;exit();
				}
				$tt		= split(',',$v['remakeid']);
				$pid	= $tt[$style-1];
				
				$tt		= split(',',$v['remakepid']);
				$propsid= $tt[$style-1];
				
				$tt		= split(',',$v['remakelevel']);
				$levels = $tt[$style-1];
				unset($tt);
				$cbb = $uv;
				break;
			}
		}
	}

 	foreach ($bb as $k => $v)
	{
		if ($v['id'] == $pid) {$sbb = $v; break;}
	}
	
	$propsids = explode('|',$propsid);
	
	// Check
	$true = 0;
	foreach ($userbag as $t => $ts)
	{
		if (in_array($ts['pid'] ,$propsids) && $ts['sums']>0)
		{
			$propsid = $ts['pid'];
			$true = 1;break;
		}
	}

	if ($true ==0)
	{
		unLockItem($id);
		die('2');
	}

	if ($cbb['level'] < $levels){
		unLockItem($id);
		die('3');
	}

	if($cbb['wx']>6)
	{
		unLockItem($id);
		die('五行属于：金、木、水、火、土、神的才可以进行此操作！');
	}

	if ($cbb['remaketimes'] == 10){
		unLockItem($id);
		die('6');
	}

	// Start update info.成长率获得公式：实际成长率=原有成长率+rand(0.1,0.5)+取1位小数[(宠物当前等级－进化等级)/200]
	//  [remakelevel] => 30    [remakeid] => 11    [remakepid] => 74
	if (is_array($sbb))
	{
		$imgstand = $sbb['imgstand'];
		$imgack   = $sbb['imgack'];
		$imgdie   = $sbb['imgdie'];
		$name	  = $sbb['name'];
		$ds		  = explode(',', $sbb['czl']);
		//$czl	  = round( (rand($ds[0]*10,$ds[1]*10)/10 + round((($cbb['level']-$cbb['remakelevel'])/200),1)),1);
		$_pm['mysql']->query("INSERT INTO gamelog(ptime,seller,buyer,pnote,vary) VALUES(unix_timestamp(),{$_SESSION['id']},{$_SESSION['id']},'进化,使用的道具为:".$pids.",被进化宝宝id:".$id.",被进化宝宝名:".$cbb['name'].",得到:".$name."',99)");
		if($pids != 1221 && $pids != 1222)
		{
			if ($style == 1)
			{
				if($cbb['czl'] < 50)
				{
					$czl	= round(  ($cbb['czl']+rand(1,5)/10+ round((($cbb['level']-$levels)/200),1)), 1);
				}
				else if($cbb['czl'] >= 50 && $cbb['czl'] < 80)
				{
					$czl	= $cbb['czl']+ rand(1,3)/10;
				}
				else{
					$czl	= round(($cbb['czl']+0.1),1);
				}
			}
			else if ($style == 2)
			{	//实际成长率=原有成长率+rand(0.5,1.0)+取1位小数[(宠物当前等级－进化等级)/200]
				if($cbb['czl'] < 50)
				{
					$czl	= round(  ($cbb['czl']+rand(5,10)/10+ round((($cbb['level']-$levels)/200),1)), 1);
				}
				else if($cbb['czl'] >= 50 && $cbb['czl'] < 70)
				{
					$czl	= $cbb['czl']+rand(4,7)/10;
				}
				else if($cbb['czl'] >= 70 && $cbb['czl'] < 80)
				{
					$czl	= $cbb['czl']+rand(3,5)/10;
				}
				else if($cbb['czl'] >= 80 && $cbb['czl'] < 90)
				{
					$czl	= $cbb['czl']+rand(2,3)/10;
				}
				else{
					$czl	= $cbb['czl']+rand(1,3)/10;
				}
			}
			//通过进化系统时，如果原来成长大于(等于)50.0，则进化后成长率不发生任何改变
			if($czl >= 150.0)
			{
				if($bhEffect)
				{
					if($czl>$bhEffect)
					{
						$czl=$bhEffect;						
					}
					$_pm['mysql']->query("UPDATE userbag SET sums=".$bhPNewNum." WHERE id=".$bhid);
				}else{
					$czl = 150.0;
				}
			}
		}
		else if($pids == 1221)
		{
			$czl = $cbb['czl']+(rand(1,3))/10;
		}
		else if($pids == 1222)
		{
			$czl = $cbb['czl']+(rand(3,6))/10;
		}
		
		$rml 	= $sbb['remakelevel'];
		$rmid   = $sbb['remakeid'];
		$rmpid  = $sbb['remakepid'];
		$times  = isset($cbb['remaketimes'])?(intval($cbb['remaketimes'])+1):1;
		
		// Update pets data.
		$_pm['mysql']->query("UPDATE userbb
					   SET imgstand='{$sbb['imgstand']}',
						   imgack='{$sbb['imgack']}',
						   imgdie='{$sbb['imgdie']}',
						   name='{$sbb['name']}',
						   czl='{$czl}',
						   remakelevel='{$rmid}',
						   remakepid='{$rmpid}',
						   cardimg='{$sbb['cardimg']}',
						   effectimg='{$sbb['effectimg']}',
						   remaketimes='{$times}'
					 WHERE uid={$_SESSION['id']} and id={$cbb['id']}
				  ");
				  
		// del props for remake made.
		$_pm['mysql']->query("UPDATE userbag
					   SET sums=abs(sums-1)
					 WHERE pid={$propsid} and sums>0 and uid={$_SESSION['id']} and id={$ts['id']} and sums > 0
				 ");
	
		// 减少用户金币.
		$user['money'] = $user['money']-1000;
		$_pm['mysql']->query("UPDATE player
					   SET money='{$user['money']}'
					 WHERE id={$_SESSION['id']}
				  ");
		unLockItem($id);
		die('1');
	}else{
		unLockItem($id);
		die('00');
	}
}
else {
	unLockItem($id);
	die('000');
}

//$_pm['user']->updateMemUser($_SESSION['id']);
//$_pm['user']->updateMemUserbag($_SESSION['id']);
//$_pm['user']->updateMemUserbb($_SESSION['id']);

$_pm['mem']->memClose();
unset($m, $u, $db, $userbag, $bb, $user);
unLockItem($id);
?>