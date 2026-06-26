<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.09.25
*@Update Date: 
*@Usage: 宠物托管
*@Note: none
*/
/*ini_set('display_errors',true);
error_reporting(E_ALL);*/
session_start();
require_once('../config/config.game.php');

secStart($_pm['mem']);

$user	 = $_pm['user']->getUserById($_SESSION['id']);
$petsAll  = $_pm['user']->getUserPetById($_SESSION['id']);
$rs	= unserialize($_pm['mem']->get(MEM_PROPS_KEY));
$userBag	= $_pm['user']->getUserBagById($_SESSION['id']);
$action = $_REQUEST['action'];

if(lockItem($user['mbid']) === false)
{
	//die('已经在处理了！');
	sleep(3);
}




//增加一个冷却时间
$srctime = 1;
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
		unLockItem($user['mbid']);
		die("服务器繁忙，请稍候操作！");//没有达到间隔时间
	}
	else
	{
		$_SESSION['paitimes'.$_SESSION['id']] = time();
	}
}

//加载信息
if($action == 'getinfo'){
	$id = intval($_GET['id']);
	if($id <= 0){
		unLockItem($user['mbid']);
		die('1');
	}
	$mesarr = array(1=>'休息',2=>'武力修炼',3=>'冒险修炼');
	foreach($petsAll as $v){
		if($v['id'] == $id){
			$mes = $mesarr[$v['tgmes']];
			$tgtime = $v['tgtime'];
			$stime = $v['tgstime'];
		}
	}
	$time = time();
	$ctime = $time - $stime;
	if($ctime < 0){
		$flag = '等待中';
	}else if($ctime < $tgtime){
		$flag = '托管中';
	}else{
		$flag = '托管完成';
	}
	$str = '托管时间：'.($tgtime/3600).'小时&nbsp;托管方式:'.$mes.'&nbsp;托管状态：'.$flag;
	unLockItem($user['mbid']);
	die($str);
}
if($action == 'times'){
	$id = intval($_GET['id']);
	if($id <= 0){
		unLockItem($user['mbid']);
		die('1');
	}
	foreach($petsAll as $v){
		if($v['id'] == $id){
			$rs = $v;
		}
	}
	$time = time();
	$ctime = $time - $rs['tgstime'];
	if($ctime < 0){
		unLockItem($user['mbid']);
		die('2');
	}else if($ctime < $rs['tgtime']){
		//扣除水晶币=玩家节省时间（及玩家点击立即完成时所剩托管时间，单位为s）*200sj/3600s
		$sj = round(($rs['tgtime'] - $ctime) * 100 / 3600);
		unLockItem($user['mbid']);
		die('立即加速完成，需要消耗水晶：'.$sj.'，您确定加速吗？');
	}else{
		unLockItem($user['mbid']);
		die("3");
	}
}

if($action == 'timesdo'){
	$id = intval($_GET['id']);
	if($id <= 0){
		unLockItem($user['mbid']);
		die('数据有误！');
	}
	foreach($petsAll as $v){
		if($v['id'] == $id){
			$rs = $v;
		}
	}
	$time = time();
	$ctime = $time - $rs['tgstime'];
	if($ctime < 0){
		unLockItem($user['mbid']);
		die('等待的宠物不能加速！');
	}else if($ctime < $rs['tgtime']){
		//扣除水晶币=玩家节省时间（及玩家点击立即完成时所剩托管时间，单位为s）*200sj/3600s
		$sj = round(($rs['tgtime'] - $ctime) * 100 / 3600);
		$_pm['mysql'] -> query("UPDATE player_ext SET sj = sj - $sj WHERE uid = {$_SESSION['id']} and sj >= $sj");
		$result = mysql_affected_rows($_pm['mysql'] -> getConn());
		if($result != 1){
			unLockItem($mbid);
			die("1");
		}
		$time1 = $rs['tgtime'] - $ctime;
		$_pm['mysql'] -> query("UPDATE userbb SET tgstime = tgstime - $time1 WHERE id = $id and uid = {$_SESSION['id']}");
		unLockItem($mbid);
		die('加速完成，您是否取回您的宠物？');
	}else{
		unLockItem($user['mbid']);
		die('托管完成，不需要加速！');
	}
}

//得到玩家当前所选的宠物的状态
if($action == "change")
{
	$err = "";
	$id = intval($_REQUEST['id']);
	if($petsid < 0)
	{
		unLockItem($user['mbid']);
		die("10");//信息出错
	}
	foreach($petsAll as $pets)
	{
		if($pets['id'] == $id)
		{
			if($pets['tgflag'] == "0")
			{
				$err = 0;//未托管
			}
			else if($pets['tgflag'] == "1")
			{
				$times = time();
				$time = $times - $pets['tgstime'];
				if($time < $pets['tgtime'])
				{
					$err = 1;//托管中
				}
				else
				{
					$err = 2;//托管完成
				}
			}
			else if($pets['tgflag'] == "2")
			{
				$time = time();
				if($time < $pets['tgstime'])
				{
					$err = 3;//等待中
				}
				else
				{
					$time = $time - $pets['tgstime'];
					if($time < $pets['tgtime'])
					{
						$err = 1;//托管中
					}
					else
					{
						$err = 2;//托管完成
					}
				}
			}	
		}
	}
	echo $err;
}

//托管宠物
if($action == "tuoguan")
{
	//时间限制(只有在22:00 到 10：00 可以托管)
	$err = "";
	$times = date("H:i:s");
	$timearr = explode(":",$times);
	if($timearr[0] >= 10 && $timearr[0] < 22)
	{
		unLockItem($user['mbid']);
		die("0");//只有22：00--10：00 才可以托管！
	}
	$pets = intval($_REQUEST['pets']);
	$time = intval($_REQUEST['time']);
	$mes = intval($_REQUEST['mes']);
	$time1 = $timearr[0] + $time;
	if($time1 >= 24)
	{
		$time1 = $time1 - 24;
	}
	if($time1 >= 10 && $time1 < 22)
	{
		unLockItem($user['mbid']);
		die("7");//超出托管结束时间。请重新选择时间! 
	}
	if($pets <=0 )
	{
		unLockItem($user['mbid']);
		die("1");//请选择要托管宠物
	}
	$i = 0;
	foreach($petsAll as $p)
	{
		if($p['tgflag'] > 0)
		{
			$i++;
		}
	}
	if($i >= 3)
	{
		unLockItem($user['mbid']);
		die("5");//托管个数已达上限
	}
	if($i >= 1 && $i < 3 && $i == $user['tgmax'])
	{
		unLockItem($user['mbid']);
		die("6");//托管个数您目前的上限，您可以能过购买托管所扩充卷扩充您的托管所！
	}
	
	foreach($petsAll as $pet)
	{	
		if($pet['id'] == $pets)
		{
			if($pet['level'] < 10){
				unLockItem($user['mbid']);
				die('199');
			}
			if(!empty($pet['tgflag']))
			{
				$now = time();
				$time5 = $now - $pet['tgstime'];
				if($pet['tgstime'] > $now)
				{
					unLockItem($user['mbid']);
					die("8");//等待中
				}
				else
				{
					if($time5 < $pet['tgtime'])
					{
						unLockItem($user['mbid']);
						die("3");//玩家当前所选宠物已经在托管！
					}
					else
					{
						unLockItem($user['mbid']);
						die("4");//当前宠物托管已完成，请先取回再托管!
					}
				}
			}
		}
	}
	if($pets >0 && $time > 0 && !empty($mes))
	{
		//得到要消耗的托管时间
		if($mes == "1")
		{
			$times = $time;
		}
		else if($mes == "2")
		{
			$times = 2* $time;
		}
		else if($mes == "3")
		{
			$times = 3*$time;
		}
		$tgtime = $time * 3600;
		//判断用户是否有足够的托管时间
		if($user['tgtime'] < $times)
		{
			unLockItem($user['mbid']);
			die("2");//托管失败，您的托管时间不足！您可以购买“托管卷”来增加时间。
		}
		//减去玩家的托管时间
		$sql = "UPDATE player
				SET tgtime = tgtime - {$times}
				WHERE id = {$_SESSION['id']} AND tgtime >= $times";
		$_pm['mysql'] -> query($sql);
		$result = mysql_affected_rows($_pm['mysql'] -> getConn());
		if($result != 1){
			unLockItem($user['mbid']);
			die("托管时间不足");
		}
		//更新玩家该宠物的状态
		$time1 = time();
		$sql = "UPDATE userbb 
				SET tgflag = 1,tgstime = {$time1},tgmes = {$mes},tgtime = {$tgtime}
				WHERE id = {$pets}";
		$_pm['mysql'] -> query($sql);
		$err = 10;
	}
	echo $err;
}

//判断宠物状态
if($action == "offpets")
{
	$id = intval($_REQUEST['id']);
	if($id <= 0)
	{
		unLockItem($user['mbid']);
		die("0");//请选择您要取回的宠物！
	}
	foreach($petsAll as $pets)
	{
		if($pets['id'] == $id)
		{
			if($pets['tgflag'] == 0)
			{
				unLockItem($user['mbid']);
				die("1");//您还没有进行任何托管操作，不用取回宠物。
			}
			else if($pets['tgflag'] == 2)
			{
				$time = time();
				if($pets['tgstime'] > $time )
				{
					unLockItem($user['mbid']);
					die("4");//还在等待中，确定取回吗？
				}
				else
				{
					$ctime = $time - $pets['tgstime'];
					if($ctime < $pets['tgtime'])
					{
						unLockItem($user['mbid']);
						die("3");//提前取回宠物，您之前消耗托管时间将失效，确认取回吗？
					}
					else
					{
						unLockItem($user['mbid']);
						die("2");//托管已完成，您可以取回您的宠物了！
					}
				}
			}
			else if(!empty($pets['tgflag']))
			{
				$time = time();
				$ctime = $time - $pets['tgstime'];
				if($ctime < $pets['tgtime'])
				{
					unLockItem($user['mbid']);
					die("3");//提前取回宠物，您之前消耗托管时间将失效，确认取回吗？
				}
				else
				{
					unLockItem($user['mbid']);
					die("2");//托管已完成，您可以取回您的宠物了！
				}
			}
		}
	}
}
//取回宠物
if($action == "offpet")
{
	$err = "";
	$id = intval($_REQUEST['id']);
	foreach($petsAll as $p)
	{
		if($p['muchang'] == 1)
		{
			$numarr[] = $p['id'];
		}
	}
	if(count($numarr) >= $user['maxmc'] ) 
	{
		unLockItem($user['mbid']);
		die("13");//牧场格子已经占满！
	}
	if($id > 0)
	{
		//改变状态并增加该玩家的当前宠物的相关信息
		foreach($petsAll as $pets)
		{
			if($pets['id'] == $id)
			{
				$mes = $pets['tgmes'];
				$stime = $pets['tgstime'];
				$time = $pets['tgtime'];
				$level = $pets['level'];
				$czl = $pets['czl'];
				$srchp = $pets['srchp'];
				$srcmp = $pets['srcmp'];
				$ac = $pets['ac'];
				$mc = $pets['mc'];
				break;
			}
		}
		$nowtime = time();

		$ctime = $nowtime - $stime;
		//获得的经验数=宠物等级*（宠物成长率/40）*5000
		if($time <= 0)
		{
			unLockItem($user['mbid']);
			die("0");
		}
		if($ctime < $time)//取回托管时间未用完
		{
			$time = $ctime;
		}
		if($time < 0){
			$time = 0;
		}
		$num = intval($time / 60 / 5);//计算的次数
		if($mes == 1)//休息
		{
			$exp += $level * ($czl / 40) * 2500 * $num;
		}
		else if($mes == 2)//武力修炼
		{
			$exp += $level * ($czl / 40) * 2500 * $num * 2;
		}
		else if($mes == 3)//冒险修炼
		{
			$exp += $level * ($czl / 40) * 2500 * $num * 2.5;
			for($i = 1;$i <= $num;$i++)
			{
				$props[] = giveprops($level);
			}
		}
		else
		{
			unLockItem($user['mbid']);
			die("0");//相关信息出错
		}
		//判断用户包裹是否已满
		$bagNum=0;
		$arr = array();
		if(is_array($props))
		{
			foreach($props as  $v)
			{
				if(array_key_exists($v['id'],$arr))
				{
					$arr[$v['id']] += $v['sum'];
				}
				else
				{
					$arr[$v['id']] = $v['sum'];
				}
			}
		}
		if(is_array($userBag))
		{
			foreach($userBag as $x => $y)
			{
				if($y['sums']>0 and $y['zbing'] == 0) 
				{
					$bagNum++;		
				}
			}
		}
		$bagNum += count($arr);
		if($bagNum > $user['maxbag'])
		{
			unLockItem($user['mbid']);
			die('12');//包裹空间不够，请先清理包裹！
		}
		if(is_array($arr))
		{
			foreach($arr as $k => $p)
			{
				foreach($userBag as $ub)
				{
					$ids[] = $ub['pid'];
				}
				if(in_array($k,$ids))
				{
					$sql = "UPDATE userbag SET sums = sums+{$p} WHERE uid = {$_SESSION['id']} and pid = {$k}";
				}
				else
				{
					$sql = "INSERT INTO userbag (pid,sums,uid) VALUES ({$k},{$p},{$_SESSION['id']})";
				}
				$_pm['mysql'] -> query($sql);
			}
		}
		//宠物升级
		$t = new task();
		$a = $t->saveExps($exp,$id);
		//改变宠物的状态
		$sql = "UPDATE userbb
				SET tgflag = 0,tgstime = 0,tgtime = 0,tgmes = 0
				WHERE id = {$id}";
		$_pm['mysql'] -> query($sql);
		//加日志 09 06 24
		$time1 = $time / 3600;
		$rearr = $_pm['mysql'] -> getOneRecord("SELECT level,czl,srchp,srcmp,ac,mc,tgflag,tgstime,tgtime,tgmes FROM userbb WHERE id = {$id}");
		$str = 'id:'.$id.'得经验:'.$exp.'level:'.$level.'->'.$rearr['level'].'托管方式:'.$mes.'stime:'.date("YmdHi",$stime).'->'.$rearr['tgstime'].'托管时间:'.$time1.'->'.$rearr['tgtime'].'成长:'.$czl.'->'.$rearr['czl'].'生命:'.$srchp.'->'.$rearr['srchp'].'魔法:'.$srcmp.'->'.$rearr['srcmp'].'攻击:'.$ac.'->'.$rearr['ac'].'防御:'.$mc.'->'.$rearr['mc'];
		$_pm['mysql'] -> query("INSERT INTO gamelog (ptime,seller,buyer,pnote,vary) VALUES (".time().",{$_SESSION['id']},{$_SESSION['id']},'$str',30)");
		
		
		$err = 10;//取回宠物成功
	}
	else
	{
		$err = 11;//取回宠物失败
	}
	echo $err;
}


//查看详情
if($action == "show")
{
	$id = intval($_REQUEST['id']);
	if($id <= 0)
	{
		unLockItem($user['mbid']);
		die("请选择一个宠物!");//请选择一个您要查看的宠物！
	}
	foreach($petsAll as $pet)
	{
		if($pet['id'] == $id)
		{
			if(empty($pet['tgflag']))
			{
				$str = "该宠物还没有托管或者已经取回！";
				echo $str;
				exit;
			}
			$time = time();
			if($time < $pet['tgstime'])
			{
				$str = "还没有到托管时间，您不能查看！";
				echo $str;
				exit;
			}
			$nowtime = time();
			$ctime = $nowtime - $pet['tgstime'];
			if($ctime > $pet['tgtime'])
			{
				$time = $pet['tgtime'];
			}
			else
			{
				$time = $ctime;
			}
			$num = $time / 60 / 5;
			if($pet['tgmes'] == 1)//休息
			{
				$exp += $pet['level'] * ($pet['czl'] / 40) * 2500 * $num;
			}
			else if($pet['tgmes'] == 2)//武力修炼
			{
				$exp += $pet['level'] * ($pet['czl'] / 40) * 2500 * $num * 2;
			}
			else if($pet['tgmes'] == 3)//冒险修炼
			{
				$exp += $pet['level'] * ($pet['czl'] / 40) * 2500 * $num * 2.5;
				for($i = 1;$i <= $num;$i++)
				{
					$props[] = giveprops($pet['level']);
				}
			}
			$str = "托管宠物：".$pet['name']."\n";
			$str .= "托管前宠物等级：".$pet['level']."\n";
			//$str .= "当前宠物等级";
			$str .= "托管时间：".($pet['tgtime'] / 3600)."小时\n";
			$str .= "当前已托管时间：".round($time / 60)."分钟\n";
			$str .= "托管获得经验：".round($exp)."\n";
			$str .= "随机获得物品";
			echo $str;
		}	
	}
}




//自动托管
if($action == "auto")
{
	//时间限制(只有在22:00 到 10：00 可以托管)
	$err = "";
	$times = date("H:i:s");
	$timearr = explode(":",$times);
	if($timearr[0] >= 10 && $timearr[0] < 22)
	{
		$date = date("Y-m-d");
		$autotime = strtotime($date." 22:00:00");
		//得到开始托管的时间
	}
	else
	{
		$autotime = time();
	}
	$pets = intval($_REQUEST['pets']);
	$time = intval($_REQUEST['time']);
	$mes = intval($_REQUEST['mes']);
	$time1 = $autotime + $time;
	if($time1 >= 24)
	{
		$time1 = $time1 - 24;
	}
	if($time1 >= 10 && $time1 < 22)
	{
		unLockItem($user['mbid']);
		die("7");//超出托管结束时间。请重新选择时间!
	}
	if($pets <=0 )
	{
		unLockItem($user['mbid']);
		die("1");//请选择要托管宠物
	}/**/
	$i = 0;
	foreach($petsAll as $p)
	{
		if($p['tgflag'] > 0)
		{
			$i++;
		}
	}
	if($i >= 3)
	{
		unLockItem($user['mbid']);
		die("5");//托管个数已达上限
	}
	if($i >= 1 && $i < 3 && $i == $user['tgmax'])
	{
		unLockItem($user['mbid']);
		die("6");//托管个数您目前的上限，您可以能过购买托管所扩充卷扩充您的托管所！
	}
	
	foreach($petsAll as $pet)
	{	
		if($pet['id'] == $pets)
		{
			if($pet['level'] < 10){
				unLockItem($user['mbid']);
				die('199');
			}
			if(!empty($pet['tgflag']))
			{
				$time = time() - $pet['tgstime'];
				if($pet['tgstime'] > $now)
				{
					unLockItem($user['mbid']);
					die("8");//等待中
				}
				else
				{
					if($time < $pet['tgtime'])
					{
						unLockItem($user['mbid']);
						die("3");//玩家当前所选宠物已经在托管！
					}
					else
					{
						unLockItem($user['mbid']);
						die("4");//当前宠物托管已完成，请先取回再托管!
					}
				}
			}
		}
	}
	if($pets >0 && $time > 0 && !empty($mes))
	{
		//得到要消耗的托管时间
		if($mes == "1")
		{
			$times = $time;
		}
		else if($mes == "2")
		{
			$times = 2* $time;
		}
		else if($mes == "3")
		{
			$times = 3*$time;
		}
		$tgtime = $time * 3600;
		//判断用户是否有足够的托管时间
		if($user['tgtime'] < $times)
		{
			unLockItem($user['mbid']);
			die("2");//托管失败，您的托管时间不足！您可以购买“托管卷”来增加时间。
		}
		//减去玩家的托管时间
		$sql = "UPDATE player
				SET tgtime = tgtime - {$times}
				WHERE id = {$_SESSION['id']} AND tgtime >= $times";
		$_pm['mysql'] -> query($sql);
		
		$result = mysql_affected_rows($_pm['mysql'] -> getConn());
		if($result != 1){
			unLockItem($mbid);
			die("托管时间不足");
		}
		//更新玩家该宠物的状态
		$sql = "UPDATE userbb 
				SET tgflag = 2,tgstime = {$autotime},tgmes = {$mes},tgtime = {$tgtime}
				WHERE id = {$pets}";
		$_pm['mysql'] -> query($sql);
		$err = 10;
	}
	echo $err;
}
unLockItem($user['mbid']);
$_pm['mem']->memClose();
unLockItem($user['mbid']);

//根据宠物的等级随机给道具
//$level 宠物的等级

function giveprops($level)
{
	global $tuoguan;
	foreach ($tuoguan as $k => $v)
	{
		$lv = explode("-",$k);
		//根据宠物的等级等到该宠物的道具
		if($level <= $lv[1] && $level >= $lv[0])
		{
			$arr = explode(",",$v);
			break;
		}
	}
	foreach($arr as $arrs)
	{
		$info[] = explode(":",$arrs);
	}
	foreach($info as $infos)
	{
		if(rand(1,$infos[1]) == 1)
		{
			$props['id'] = $infos[0];
			$props['sum'] = $infos[2];
		}
	}
	return $props;
}
?>