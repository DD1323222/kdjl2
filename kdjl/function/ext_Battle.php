<?php 
/**
@Usage:战场使用道具影响阵营脚本。
@Write: 2008-09-02
@Note:
  诅咒宝石，减少对方女神100点生命，增加自身军功50点
  天地树果实，恢复我方女神生命1000点，增加自身军功500点
  女神圣水，本场战斗内获得双倍军功，战斗结束后失效
  ------------------------------------------------
  4: 领取宝箱
  5：领取经验
  6：换取道具
*/
session_start();
require_once('../config/config.game.php');
secStart($_pm['mem']);

$srctime = 5;
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
		die("没有达到间隔时间!");//没有达到间隔时间
	}
	else
	{
		$_SESSION['paitimes'.$_SESSION['id']] = time();
	}
}
//die('维护中！');
$memtimeconfig = unserialize($_pm['mem']->get('db_timeconfignew'));
$arr = $memtimeconfig['usejg'];
$useJG = true;
foreach($arr as $v){
	if(is_array($v) && $v['days'] == '1'){
		$useJG=false;
	}
}
define('USEJG',$useJG);

if(lockItem(1) === false)
{
	die('服务器繁忙，请稍候操作！');
}

$num  = intval($_REQUEST['t']);
$num  = $num<1?0:$num;

$user	= $_pm['user']->getUserById($_SESSION['id']);
//$bag	= $_pm['user']->getUserBagById($_SESSION['id']);
require_once('../sec/dblock_fun.php');
$a = getLock($_SESSION['id']);
if(!is_array($a))
{
	realseLock();
	unLockItem(1);
	die('服务器繁忙，请稍候再试！');
}
switch ($num)
{
	case 1:	usePropsOfBattle(1);break;	//  诅咒宝石
	case 2: usePropsOfBattle(2);break;	//  天地树果实
    case 3: usePropsOfBattle(3);break;	//  女神圣水
	case 4: 
	{
		getBattleGoldBox(4);
		break;	//  换取宝箱
	}
	case 5: 
	{
		getBattleExp(5);
		break;		//  换取经验
	}
	case 6: 
	{
		getBattleProps(6);
		break;	//  换取道具
	}
	default:
		unLockItem(1);
		realseLock();
		die("道具使用失败！");
}
realseLock();
unLockItem(1);
function initJGLog(){
	global $_pm;
	$sql = "
	CREATE TABLE if not exists`jg_log` (
	  `id` int(8) NOT NULL AUTO_INCREMENT,
	  `uid` int(11) NOT NULL DEFAULT '0',
	  `usejg` int(9) DEFAULT '0',
	  `type` varchar(10) DEFAULT '',
	  `num` varchar(10) DEFAULT '',
	  `pid` varchar(50) DEFAULT '',
	  `times` int(10) DEFAULT '0',
	  PRIMARY KEY (`id`),
	  KEY `uid` (`uid`)
	)
	";
	 $_pm['mysql']->query($sql);
	 echo mysql_error();
}
function logJgUse($jg,$type,$num,$pid){
	global $_pm;
	$sql = '
	insert into jg_log
		(uid,usejg,type,num,pid,times)
	values(
		'.$_SESSION['id'].','.$jg.',"'.$type.'","'.$num.'","'.$pid.'",unix_timestamp()
	)
	';
	$_pm['mysql']->query($sql);
	echo mysql_error();
}
// 让玩家的道具生效。
function usePropsOfBattle($n)
{
	global $_pm;
	$ubid = 0;
	$cUser = $_pm['mysql']->getOneRecord("SELECT pos,bid,failackvalue,id,nscf,addhp,subhp
											FROM battlefield_user
										   WHERE uid={$_SESSION['id']}
											");
    if ($n == 1) 
	{
		$arr = $_pm['user']->getUserBagItemById($_SESSION['id'],203);
		if(is_array($arr) && $arr['sums'] > 0){
			$ubid = $arr['pid'];
		}
		if ($ubid>0)
		{
			// 冷却时间检查 60秒
			if ($cUser['subhp']+60>time()) {
				unLockItem(1);
				realseLock();
				die('道具使用时间冷却中，请过 '.($cUser['subhp']+60-time()).' 秒再试！');
			}

			// 检测对方女神的HP是否小于限制的数据。
			$limit = $_pm['mysql']->getOneRecord("SELECT hp
												    FROM battlefield 
												   WHERE id!={$cUser['pos']}
												");
			if ($limit['hp'] < 1000) {
				unLockItem(1);
				realseLock();
				die('对方女神生命低于 1000 点，无法使用该道具!');
			}

			// 战场是否结束！
			if (battle_timeout_check()===true)
			{
				unLockItem(1);
				realseLock();
				die('本次战场已经结束，不能使用该道具！');
			}

			$_pm['mysql']->query("UPDATE battlefield
									 SET hp=hp-100
								   WHERE id!={$cUser['pos']} and hp>=1000
								");
			$_pm['mysql']->query("UPDATE battlefield_user
									 SET curjgvalue=curjgvalue+50,
									     subhp=".time()."
								   WHERE id={$cUser['id']}
								");
			$brs = $_pm['mysql']->getOneRecord("SELECT posname 
			                                      FROM battlefield
												 WHERE id!={$cUser['pos']}
												 LIMIT 0,1
											  ");
			// Format: :"XXX(玩家名) 使用“诅咒宝石”诅咒对方女神，(对方阵营的名字)女神HP减少100点。
			$word = " ,使用 <诅咒宝石>,诅咒对方女神,{$brs['posname']}女神HP减少 100 点!";
			aword($word);
			echo '使用道具成功，军功增加 50 点';
		}
	}
	else if ($n == 2)
	{
		$arr = $_pm['user']->getUserBagItemById($_SESSION['id'],204);
		if(is_array($arr) && $arr['sums'] )
		{
			$ubid = $arr['pid'];
		}
		if ($ubid>0)
		{
			// 战场是否结束！
			if (battle_timeout_check()===true)
			{
				unLockItem(1);
				realseLock();
				die('本次战场已经结束，不能使用该道具！');
			}

			if ($cUser['addhp']+600>time()) 
			{
				unLockItem(1);
				realseLock();
				die('道具使用时间冷却中，请过 '.($cUser['addhp']+600-time()).' 秒再试！');
			}

			$selfField = $_pm['mysql']->getOneRecord("SELECT id,srchp,hp,posname
														FROM battlefield 
													   WHERE id={$cUser['pos']}");
			$week=date("N", time());
			$hourM=date("H:i", time());
			$battletimearr = unserialize($_pm['mem']->get(MEM_TIME_KEY));

			foreach($battletimearr as $bv)
			{
				if($bv['titles'] != "battle")
				{
					continue;
				}
				if($selfField['hp'] != 0 && $week == $bv['days'] && $hourM >= $bv['starttime'] && $hourM <= $bv['endtime'])
				{
					$checkstr = 1;
					break;
				}
			}
			if(empty($checkstr))
			{
				unLockItem(1);
				realseLock();
				die('战场已结束，不能使用该道具！');
			}
			
            if ($selfField['hp']+1000>$selfField['srchp']) 
				 $sumhp = $selfField['srchp'];
			else $sumhp = $selfField['hp']+1000;

			$_pm['mysql']->query("UPDATE battlefield
									 SET hp={$sumhp}
								   WHERE id={$cUser['pos']}
								");
			$_pm['mysql']->query("UPDATE battlefield_user
									 SET curjgvalue=curjgvalue+500,
									     addhp=".time()."
								   WHERE id={$cUser['id']}
								");
			$word = " ,使用<天地树的果实>,{$selfField['posname']}女神HP恢复 1000 点!";
			aword($word);
			echo '使用道具成功，军功增加 500 点';
		}
	}
	else if ($n == 3)
	{
		$arr = $_pm['user']->getUserBagItemById($_SESSION['id'],205);
		if(is_array($arr) && $arr['sums'] )
		{
			$ubid = $arr['pid'];
		}
		if ($ubid>0)
		{
			if ($cUser['nscf']==1) {
				unLockItem(1);
				die('每场活动时，只能使用道具得到一次女神赐福！');
			}
			
			// 战场是否结束！
			if (battle_timeout_check()===true)
			{
				unLockItem(1);
				die('本次战场已经结束，不能使用该道具！');
			}

			$_pm['mysql']->query("UPDATE battlefield_user
									 SET doublejg=1,nscf=1
								   WHERE id={$cUser['id']}
								");
		}
		else
		{
			unLockItem(1);
			realseLock();
			die("您没有相关的物品~！");
		}
	}

	if ($ubid>0) // $uid => table:userbag's id
	{
		$_pm['mysql']->query("UPDATE userbag
							     SET sums=abs(sums-1)
							   WHERE pid={$ubid} and uid={$_SESSION['id']} and sums > 0
		                     ");
		unLockItem(1);
		realseLock();
		die('使用道具成功！');
	}
	else {
		unLockItem(1);
		realseLock();
		die("道具使用失败！");
	}
}

/**
*@Usage: 领取宝箱
*@Param: $v =>  宝箱类型
*@Return: void(0);
*/
function getBattleGoldBox($n)
{
	if(!USEJG)
	{
		realseLock();	
		die('军功使用暂时关闭，请改天再试！');
	}

	global $_pm;

	$boxid = 0;
    switch($_REQUEST['v'])
	{
		case 1: $boxid=1059;break;// 自然宝箱
		case 2: $boxid=1060;break;// 暗夜宝箱
		case 3: $boxid=1061;break;// 神圣宝箱
		default: 
			unLockItem(1);
			realseLock();
			die('您没进入排名或已经领取奖励！');
	}

	// 获取用户的军功排名并进行对应操作。
	$uinfo = $_pm['mysql']->getOneRecord("SELECT boxnum  
	                                        FROM battlefield_user
										   WHERE uid={$_SESSION['id']}
										");
    if (!is_array($uinfo) || $uinfo['boxnum']<1) {
		unLockItem(1);
		realseLock();
		die('您没进入排名或已经领取奖励！');
	}
	$tsk = new task();
	$idlist='';
	for($i=0; $i<$uinfo['boxnum'];$i++)
	{
		$idlist .= $idlist==''?	$boxid:','.$boxid;
	}

	$tsk->saveGetProps($idlist);
	// 更新用户领取标记。
	$_pm['mysql']->query("UPDATE battlefield_user
	                         SET boxnum=0
						   WHERE uid={$_SESSION['id']}
						 ");
	initJGLog();
	logJgUse(0,'GoldBox','x1',$idlist);
	unLockItem(1);
	realseLock();
	die('恭喜您，获得 '.$uinfo['boxnum'].' 宝箱!');
}

/**
*@Usage: 换取经验
*@Param: $j => 换取的军功点
*@Return: void(0);
×@Note: 每点军功兑换的经验值=主战宠物等级*100
*/
function getBattleExp($n)
{
	global $_pm;
	if(!USEJG)
	{
		realseLock();
		die('军功使用暂时关闭，请改天再试！');
	}

//===
//die('兑换暂时关闭！');

	$jg = intval($_REQUEST['j']);
	$jg = $jg<1?0:$jg;
    // 获得当前用户的军功数。
	$cur = $_pm['mysql']->getOneRecord("SELECT jgvalue
	                                      FROM battlefield_user
										 WHERE uid={$_SESSION['id']} and jgvalue>0
									  ");
   if (is_array($cur) && $cur['jgvalue'] >= $jg)
   {
		$user	 = $_pm['user']->getUserById($_SESSION['id']);
		$bb      = $_pm['mysql']->getOneRecord("SELECT level 
												 FROM userbb 
												WHERE uid={$_SESSION['id']} and id={$user['mbid']}
											 ");
        if (!is_array($bb)){
			unLockItem(1);
			realseLock();
			 die('请先到牧场设置主战宠物！');
			}
		
		// 扣除军功。
		$_pm['mysql']->query("UPDATE battlefield_user 
		                         SET jgvalue=jgvalue-{$jg}
							   WHERE uid={$_SESSION['id']} and jgvalue >= $jg
							");
		$result = mysql_affected_rows($_pm['mysql'] -> getConn());
		if($result != 1){
			unLockItem(1);
			realseLock();
			die('军功不足！');
		}
		$exp   = $jg*$bb['level']*100;
        // 存储经验：
		$t = new task();
		$t->saveExps($exp);
		
		initJGLog();
		logJgUse($jg,'BattleExp',$exp,0);
		unLockItem(1);
		realseLock();
		die('恭喜您，主战宠物获得了 '.$exp.' 点经验');
   }else {
   		realseLock();
   		unLockItem(1);
		die('您的战场积分不足！');
	}
}

/**
*@Usage: 换取道具
*@Param: $p => 道具id, $s => 换取的道具数量。
*@Return: void(0);
*/
function getBattleProps($n)
{
	global $_pm;
	if(!USEJG)die('军功使用暂时关闭，请改天再试！');

//die('兑换暂时关闭！');

    $pid = intval($_REQUEST['p']);
	$pid = $pid<1?0:$pid;
    $num = intval($_REQUEST['s']);
	$num = $num<1?0:$num;

	if ($num>0 && $pid>0)
	{
		$existsP = $_pm['mysql']->getOneRecord("SELECT need
		                                          FROM battlefield_props
												 WHERE pid={$pid}
											   ");
		if (is_array($existsP))
		{
			$need = $num*$existsP['need'];
			// 获取用户的军功值
			$cur = $_pm['mysql']->getOneRecord("SELECT jgvalue
												  FROM battlefield_user
												 WHERE uid={$_SESSION['id']} and jgvalue>0
											  ");
			if ($cur['jgvalue'] >= $need)
			{
				$tsk = new task();
				
				$res = $tsk->saveGetPropsMore($pid,$num);
				if($res === "200")
				{
					realseLock();
					unLockItem(1);
					die("您的背包已满，请您整理自己的背包。");
				}
				// 减少用户军功
				$_pm['mysql']->query("UPDATE battlefield_user
										 SET jgvalue=jgvalue-{$need}
									   WHERE uid={$_SESSION['id']} AND jgvalue >= $need
									 ");
				$result = mysql_affected_rows($_pm['mysql'] -> getConn());
				if($result != 1){
					realseLock();
					unLockItem(1);
					die('军功不足！');
				}
				initJGLog();
				logJgUse($need,'BattleProps',$num,$pid);
				realseLock();
				unLockItem(1);
				die('恭喜您，换取道具成功!');
			}
			else {
				unLockItem(1);
				realseLock();
				die('您的军功点数不够！');
			}
		}
	}
}
// Say word to game chat.
function aword($msg)
{
	$aw = new task();
	$aw-> saveGword($msg);
}

/**
*@Usage: 战场是否结束。
*/
function battle_timeout_check()
{
	global $_pm;
	$ends = $_pm['mysql']->getOneRecord("SELECT id
										   FROM battlefield
										  WHERE ends=1
										  LIMIT 0,1
									   ");
	if (is_array($ends))
	{
		return true;
	}
	else return false;
}

$_pm['mem']->memClose();
//####################
?>
