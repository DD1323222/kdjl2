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
require_once(dirname(__FILE__).'/../sec/battle_lifecycle_fnc.php');
secStart($_pm['mem']);

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('服务器繁忙，请稍候再试！');
$timerKey = 'battle_action_time_'.$uid;
$srctime = 5;
#################增加一个间隔时间################
$time = isset($_SESSION[$timerKey]) ? $_SESSION[$timerKey] : 0;
if(empty($time))
{
	$_SESSION[$timerKey] = time();
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
		$_SESSION[$timerKey] = time();
	}
}
//die('维护中！');
$memtimeconfig = kdjlSafeMemValue($_pm['mem']->get('db_timeconfignew'), array());
$arr = (is_array($memtimeconfig) && isset($memtimeconfig['usejg']) && is_array($memtimeconfig['usejg'])) ? $memtimeconfig['usejg'] : array();
$useJG = true;
foreach($arr as $v){
	if(is_array($v) && isset($v['days']) && $v['days'] == '1'){
		$useJG=false;
	}
}
define('USEJG',$useJG);

$num  = (isset($_REQUEST['t']) && !is_array($_REQUEST['t'])) ? intval($_REQUEST['t']) : 0;
$num  = $num<1?0:$num;
if($num === 4) kdjlSacredBattleTick($_pm['mysql'], $_pm['mem'], time());

$user	= $_pm['user']->getUserById($uid);
if(!is_array($user)) die('玩家数据错误！');

if($num >= 4 && $num <= 6 && USEJG)
{
	initJGLog();
}

if(lockItem(1) === false)
{
	die('服务器繁忙，请稍候操作！');
}

//$bag	= $_pm['user']->getUserBagById($_SESSION['id']);
require_once('../sec/dblock_fun.php');
$a = getLock($uid);
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
	CREATE TABLE IF NOT EXISTS `jg_log` (
	  `id` int(8) NOT NULL AUTO_INCREMENT,
	  `uid` int(11) NOT NULL DEFAULT '0',
	  `usejg` int(9) DEFAULT '0',
	  `type` varchar(10) DEFAULT '',
	  `num` varchar(10) DEFAULT '',
	  `pid` varchar(50) DEFAULT '',
	  `times` int(10) DEFAULT '0',
	  PRIMARY KEY (`id`),
	  KEY `uid` (`uid`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
	 ";
	 $_pm['mysql']->query($sql);
}
function logJgUse($jg,$type,$num,$pid){
	global $_pm,$uid;
	$sql = '
	insert into jg_log
		(uid,usejg,type,num,pid,times)
	values(
		'.$uid.','.$jg.',"'.$type.'","'.$num.'","'.$pid.'",unix_timestamp()
	)
	';
	$_pm['mysql']->query($sql);
}
// 让玩家的道具生效。
function usePropsOfBattle($n)
{
	global $_pm,$uid;
	$ubid = 0;
	$successWord = '';
	$successMessage = '使用道具成功！';
	$cUser = $_pm['mysql']->getOneRecord("SELECT pos,bid,failackvalue,id,nscf,addhp,subhp
											FROM battlefield_user
										   WHERE uid={$uid} AND pos IN(1,2) AND lastvtime>=UNIX_TIMESTAMP(CURDATE())
										   ORDER BY id LIMIT 1
											");
	if(!is_array($cUser))
	{
		unLockItem(1);
		realseLock();
		die('fail');
	}
    if ($n == 1)
	{
		$arr = $_pm['user']->getUserBagItemById($uid,203);
		if(is_array($arr) && $arr['sums'] > 0 && (!isset($arr['cantrade']) || intval($arr['cantrade']) != 3)){
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
			if(!is_array($limit))
			{
				unLockItem(1);
				realseLock();
				die('战场数据错误，无法使用该道具!');
			}
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

			if(!$_pm['mysql']->query("UPDATE battlefield
									 SET hp=hp-100
								   WHERE id!={$cUser['pos']} and hp>=1000
								"))
			{
				$_pm['mysql']->query('ROLLBACK');
				unLockItem(1);
				realseLock();
				die('fail');
			}
			if(mysql_affected_rows($_pm['mysql']->getConn()) != 1)
			{
				$_pm['mysql']->query('ROLLBACK');
				unLockItem(1);
				realseLock();
				die('fail');
			}
			if(!$_pm['mysql']->query("UPDATE battlefield_user
									 SET curjgvalue=COALESCE(curjgvalue,0)+50,
									     subhp=".time()."
								   WHERE id={$cUser['id']}
								"))
			{
				$_pm['mysql']->query('ROLLBACK');
				unLockItem(1);
				realseLock();
				die('fail');
			}
			if(mysql_affected_rows($_pm['mysql']->getConn()) != 1)
			{
				$_pm['mysql']->query('ROLLBACK');
				unLockItem(1);
				realseLock();
				die('fail');
			}
			$brs = $_pm['mysql']->getOneRecord("SELECT posname
			                                      FROM battlefield
										 WHERE id!={$cUser['pos']}
										 LIMIT 0,1
									  ");
			if(!is_array($brs) || !isset($brs['posname']))
			{
				$_pm['mysql']->query('ROLLBACK');
				unLockItem(1);
				realseLock();
				die('战场数据错误，无法使用该道具!');
			}
			// Format: :"XXX(玩家名) 使用“诅咒宝石”诅咒对方女神，(对方阵营的名字)女神HP减少100点。
			$successWord = " ,使用 <诅咒宝石>,诅咒对方女神,{$brs['posname']}女神HP减少 100 点!";
			$successMessage = '使用道具成功，军功增加 50 点';
		}
	}
	else if ($n == 2)
	{
		$arr = $_pm['user']->getUserBagItemById($uid,204);
		if(is_array($arr) && $arr['sums'] && (!isset($arr['cantrade']) || intval($arr['cantrade']) != 3))
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
			if(!is_array($selfField))
			{
				unLockItem(1);
				realseLock();
				die('战场数据错误，无法使用该道具!');
			}
			$week=date("N", time());
			$hourM=date("H:i", time());
			$battletimearr = kdjlSafeMemValue($_pm['mem']->get(MEM_TIME_KEY), array());
			if(!is_array($battletimearr)) $battletimearr = array();
			$checkstr = 0;
			$selfHp = is_array($selfField) && isset($selfField['hp']) ? intval($selfField['hp']) : 0;

			foreach($battletimearr as $bv)
			{
				if(!is_array($bv)) continue;
				if(!isset($bv['titles'])) $bv['titles'] = '';
				if(!isset($bv['days'])) $bv['days'] = '';
				if(!isset($bv['starttime'])) $bv['starttime'] = '';
				if(!isset($bv['endtime'])) $bv['endtime'] = '';
				if($bv['titles'] != "battle")
				{
					continue;
				}
				if($selfHp != 0 && isWeeklyDayTimeActive($bv['days'], $bv['starttime'], $bv['endtime'], $week, $hourM))
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

			if(!$_pm['mysql']->query("UPDATE battlefield
									 SET hp=LEAST(srchp,hp+1000)
								   WHERE id={$cUser['pos']} AND hp>0 AND hp<srchp
								"))
			{
				$_pm['mysql']->query('ROLLBACK');
				unLockItem(1);
				realseLock();
				die('fail');
			}
			if(mysql_affected_rows($_pm['mysql']->getConn()) != 1)
			{
				$_pm['mysql']->query('ROLLBACK');
				unLockItem(1);
				realseLock();
				die('我方女神生命已满，不能使用该道具！');
			}
			if(!$_pm['mysql']->query("UPDATE battlefield_user
									 SET curjgvalue=COALESCE(curjgvalue,0)+500,
									     addhp=".time()."
								   WHERE id={$cUser['id']}
								"))
			{
				$_pm['mysql']->query('ROLLBACK');
				unLockItem(1);
				realseLock();
				die('fail');
			}
			if(mysql_affected_rows($_pm['mysql']->getConn()) != 1)
			{
				$_pm['mysql']->query('ROLLBACK');
				unLockItem(1);
				realseLock();
				die('fail');
			}
			$successWord = " ,使用<天地树的果实>,{$selfField['posname']}女神HP恢复 1000 点!";
			$successMessage = '使用道具成功，军功增加 500 点';
		}
	}
	else if ($n == 3)
	{
		$arr = $_pm['user']->getUserBagItemById($uid,205);
		if(is_array($arr) && $arr['sums'] && (!isset($arr['cantrade']) || intval($arr['cantrade']) != 3))
		{
			$ubid = $arr['pid'];
		}
		if ($ubid>0)
		{
			if ($cUser['nscf']==1) {
				unLockItem(1);
				realseLock();
				die('每场活动时，只能使用道具得到一次女神赐福！');
			}

			// 战场是否结束！
			if (battle_timeout_check()===true)
			{
				unLockItem(1);
				realseLock();
				die('本次战场已经结束，不能使用该道具！');
			}

			if(!$_pm['mysql']->query("UPDATE battlefield_user
									 SET doublejg=1,nscf=1
								   WHERE id={$cUser['id']}
								"))
			{
				$_pm['mysql']->query('ROLLBACK');
				unLockItem(1);
				realseLock();
				die('fail');
			}
			if(mysql_affected_rows($_pm['mysql']->getConn()) != 1)
			{
				$_pm['mysql']->query('ROLLBACK');
				unLockItem(1);
				realseLock();
				die('fail');
			}
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
		$itemUsed = $_pm['mysql']->query("UPDATE userbag
							     SET sums=sums-1
							   WHERE pid={$ubid} and uid={$uid} and sums > 0 and zbing=0
							     and (cantrade IS NULL OR cantrade<>3)
							   ORDER BY id LIMIT 1
		                     ");
		if(!$itemUsed || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
		{
			$_pm['mysql']->query('ROLLBACK');
			unLockItem(1);
			realseLock();
			die('fail');
		}
		if(!$_pm['mysql']->query("DELETE FROM userbag WHERE pid={$ubid} and uid={$uid} and sums<=0 and bsum<=0 and psum<=0 and pyb=0 and zbing=0 and (cantrade IS NULL OR cantrade<>3)"))
		{
			$_pm['mysql']->query('ROLLBACK');
			unLockItem(1);
			realseLock();
			die('fail');
		}
		if(!$_pm['mysql']->query('COMMIT'))
		{
			$_pm['mysql']->query('ROLLBACK');
			unLockItem(1);
			realseLock();
			die('fail');
		}
		$_pm['mem']->del(MEM_USERBAG_KEY);
		unLockItem(1);
		realseLock();
		if($successWord !== '') aword($successWord);
		die($successMessage);
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
		unLockItem(1);
		realseLock();
		die('军功使用暂时关闭，请改天再试！');
	}

	global $_pm,$uid;

	$boxid = 0;
    $boxType = (isset($_REQUEST['v']) && !is_array($_REQUEST['v'])) ? intval($_REQUEST['v']) : 0;
    switch($boxType)
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
	$uinfo = $_pm['mysql']->getOneRecord("SELECT id,boxnum
	                                        FROM battlefield_user
										   WHERE uid={$uid}
										ORDER BY id LIMIT 1 FOR UPDATE
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

	$giveResult = $tsk->saveGetProps($idlist);
	if($giveResult !== true)
	{
		$_pm['mysql']->query('ROLLBACK');
		unLockItem(1);
		realseLock();
		die($giveResult === '200' ? '背包空间不足！' : '发放战场宝箱奖励失败！');
	}
	// 更新用户领取标记。
	$boxSaved = $_pm['mysql']->query("UPDATE battlefield_user
	                         SET boxnum=0
						   WHERE id=".intval($uinfo['id'])." AND uid={$uid} AND boxnum>0
						 ");
	if(!$boxSaved || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
	{
		$_pm['mysql']->query('ROLLBACK');
		unLockItem(1);
		realseLock();
		die('保存战场宝箱状态失败！');
	}
	if(!$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		unLockItem(1);
		realseLock();
		die('保存战场宝箱状态失败！');
	}
	$_pm['mem']->del(MEM_USERBAG_KEY);
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
	global $_pm,$uid;
	if(!USEJG)
	{
		unLockItem(1);
		realseLock();
		die('军功使用暂时关闭，请改天再试！');
	}

//===
//die('兑换暂时关闭！');

	$jg = (isset($_REQUEST['j']) && !is_array($_REQUEST['j'])) ? intval($_REQUEST['j']) : 0;
	$jg = $jg<1?0:$jg;
	if($jg < 1)
	{
		unLockItem(1);
		realseLock();
		die('请输入正确的军功点数！');
	}
    // 获得当前用户的军功数。
	$cur = $_pm['mysql']->getOneRecord("SELECT id,jgvalue
	                                      FROM battlefield_user
										 WHERE uid={$uid} and jgvalue>0
									  ORDER BY id LIMIT 1
										 FOR UPDATE
									  ");
   if (is_array($cur) && $cur['jgvalue'] >= $jg)
   {
		$user	 = $_pm['user']->getUserById($uid);
		$bb      = $_pm['mysql']->getOneRecord("SELECT level
												 FROM userbb
												WHERE uid={$uid} and id={$user['mbid']}
											 ");
        if (!is_array($bb)){
			unLockItem(1);
			realseLock();
			 die('请先到牧场设置主战宠物！');
			}

		// 扣除军功。
		$_pm['mysql']->query("UPDATE battlefield_user
		                         SET jgvalue=jgvalue-{$jg}
							   WHERE id=".intval($cur['id'])." AND uid={$uid} and jgvalue >= $jg
							");
		$result = mysql_affected_rows($_pm['mysql'] -> getConn());
		if($result != 1){
			$_pm['mysql']->query('ROLLBACK');
			unLockItem(1);
			realseLock();
			die('军功不足！');
		}
		$levelExp = kdjlSafePositiveProduct($bb['level'],100);
		$exp = ($levelExp === false) ? false : kdjlSafePositiveProduct($jg,$levelExp);
		if($exp === false)
		{
			$_pm['mysql']->query('ROLLBACK');
			unLockItem(1);
			realseLock();
			die('兑换经验数值过大！');
		}
        // 存储经验：
		$t = new task();
		if($t->saveExps($exp) === false)
		{
			$_pm['mysql']->query('ROLLBACK');
			unLockItem(1);
			realseLock();
			die('fail');
		}

		if(!$_pm['mysql']->query('COMMIT'))
		{
			$_pm['mysql']->query('ROLLBACK');
			unLockItem(1);
			realseLock();
			die('fail');
		}
		$_pm['mem']->del(MEM_USERBB_KEY);
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
	global $_pm,$uid;
	if(!USEJG)
	{
		unLockItem(1);
		realseLock();
		die('军功使用暂时关闭，请改天再试！');
	}

//die('兑换暂时关闭！');

    $pid = (isset($_REQUEST['p']) && !is_array($_REQUEST['p'])) ? intval($_REQUEST['p']) : 0;
	$pid = $pid<1?0:$pid;
    $num = (isset($_REQUEST['s']) && !is_array($_REQUEST['s'])) ? intval($_REQUEST['s']) : 0;
	$num = $num<1?0:$num;
	if($pid < 1 || $num < 1 || $num > 99)
	{
		unLockItem(1);
		realseLock();
		die('兑换数量必须在1到99之间！');
	}

	if ($num>0 && $pid>0)
	{
		$existsP = $_pm['mysql']->getOneRecord("SELECT need
		                                          FROM battlefield_props
												 WHERE pid={$pid}
											   ");
		if (is_array($existsP))
		{
			$need = kdjlSafePositiveProduct($existsP['need'],$num);
			if($need === false)
			{
				unLockItem(1);
				realseLock();
				die('道具发放失败，请稍候再试！');
			}
			// 获取用户的军功值
			$cur = $_pm['mysql']->getOneRecord("SELECT id,jgvalue
												  FROM battlefield_user
												 WHERE uid={$uid} and jgvalue>0
											  ORDER BY id LIMIT 1
												 FOR UPDATE
											  ");
			if (is_array($cur) && $cur['jgvalue'] >= $need)
			{
				$tsk = new task();

				$res = $tsk->saveGetPropsMore($pid,$num);
				if($res !== true)
				{
					$_pm['mysql']->query('ROLLBACK');
					unLockItem(1);
					realseLock();
					die($res === '200' ? "您的背包已满，请您整理自己的背包。" : '道具发放失败，请稍候再试！');
				}
				// 减少用户军功
				$battlePaid = $_pm['mysql']->query("UPDATE battlefield_user
										 SET jgvalue=jgvalue-{$need}
									   WHERE id=".intval($cur['id'])." AND uid={$uid} AND jgvalue >= $need
									 ");
				$result = mysql_affected_rows($_pm['mysql'] -> getConn());
				if(!$battlePaid || $result != 1){
					$_pm['mysql']->query('ROLLBACK');
					unLockItem(1);
					realseLock();
					die('军功不足！');
				}
				if(!$_pm['mysql']->query('COMMIT'))
				{
					$_pm['mysql']->query('ROLLBACK');
					unLockItem(1);
					realseLock();
					die('道具发放失败，请稍候再试！');
				}
				$_pm['mem']->del(MEM_USERBAG_KEY);
				logJgUse($need,'BattleItem',$num,$pid);
				realseLock();
				unLockItem(1);
				die('恭喜您，换取道具成功!');
			}
			else {
				$_pm['mysql']->query('ROLLBACK');
				unLockItem(1);
				realseLock();
				die('您的军功点数不够！');
			}
		}
		else
		{
			unLockItem(1);
			realseLock();
			die('该道具当前不能使用军功兑换！');
		}
	}
	unLockItem(1);
	realseLock();
	die('兑换请求无效！');
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
										  WHERE ends<>0 OR startf=0 OR hp<=0
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
