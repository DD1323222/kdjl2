<?php
ini_set('display_errors',false);
error_reporting(E_ALL);
header('Content-Type: application/javascript; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
set_time_limit(180);
ignore_user_abort(true);

require_once(dirname(__FILE__).'/config/config.game.php');
require_once(dirname(__FILE__).'/kernel/socketmsg.v1.php');
require_once(dirname(__FILE__).'/sec/dblock_fun.php');
require_once(dirname(__FILE__).'/socketChat/config.chat.php');

$guardLockHandle = (isset($_pm['mem']) && is_object($_pm['mem']) && method_exists($_pm['mem'], 'getHandle')) ? $_pm['mem']->getHandle() : false;
$guardLockKey = 'kdjl_guard_thread_'.md5(isset($_mysql['db']) ? $_mysql['db'] : 'default');
$guardLockToken = uniqid('guard_', true);
$guardLockHeld = is_object($guardLockHandle) && @$guardLockHandle->add($guardLockKey, $guardLockToken, 0, 240);
if(!$guardLockHeld)
{
	echo "// guard busy";
	exit;
}
function kdjlGuardReleaseProcessLock()
{
	global $guardLockHandle,$guardLockKey,$guardLockToken,$guardLockHeld;
	if(!$guardLockHeld || !is_object($guardLockHandle)) return;
	$currentToken = @$guardLockHandle->get($guardLockKey);
	if($currentToken === $guardLockToken) @$guardLockHandle->delete($guardLockKey);
	$guardLockHeld = false;
}
register_shutdown_function('kdjlGuardReleaseProcessLock');

$guardUid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($guardUid > 0 && kdjlMysqlTableHasColumn($_pm['mysql'], 'player', 'heart_time'))
{
	$_pm['mysql']->query("UPDATE player SET heart_time = ".time()." WHERE id = '{$guardUid}'");
}

doWork1(time());
doWork2(time());
//doWork3(time());
doWork4(time());
doWork5(time());
$s=new socketmsg();
checkGuildFightEnd();
function calcGuildFight($day='')
{
	global $_pm,$s;
	if($day==='') $day=date('Ymd');
	if(!preg_match('/^[0-9]{8}$/D',$day)) return false;

	$archive='guild_challenges'.$day;
	$next='guild_challenges_next_'.$day;
	$lockName='kdjl_guild_fight_'.$day;
	$lockNameSql=mysql_real_escape_string($lockName,$_pm['mysql']->getConn());
	$lock=$_pm['mysql']->getOneRecord("SELECT GET_LOCK('{$lockNameSql}',0) AS locked");
	if(!is_array($lock) || intval($lock['locked'])!==1) return false;

	$archiveInfo=$_pm['mysql']->getOneRecord('SHOW CREATE TABLE `'.$archive.'`');
	if(is_array($archiveInfo))
	{
		$baseInfo=$_pm['mysql']->getOneRecord('SHOW CREATE TABLE `guild_challenges`');
		if(!is_array($baseInfo) && !$_pm['mysql']->query('CREATE TABLE `guild_challenges` LIKE `'.$archive.'`'))
		{
			kdjlGuardReleaseNamedLock($lockName);
			return false;
		}
		kdjlGuardReleaseNamedLock($lockName);
		kdjlGuardPruneDatedTables('guild_challenges',5);
		kdjlGuardPruneDatedTables('ticket_',5);
		return true;
	}

	$baseInfo=$_pm['mysql']->getOneRecord('SHOW CREATE TABLE `guild_challenges`');
	if(!is_array($baseInfo))
	{
		kdjlGuardReleaseNamedLock($lockName);
		return false;
	}

	$_pm['mysql']->query('DROP TABLE IF EXISTS `'.$next.'`');
	if(!$_pm['mysql']->query('CREATE TABLE `'.$next.'` LIKE `guild_challenges`'))
	{
		kdjlGuardReleaseNamedLock($lockName);
		return false;
	}

	if(!$_pm['mysql']->query('START TRANSACTION'))
	{
		$_pm['mysql']->query('DROP TABLE IF EXISTS `'.$next.'`');
		kdjlGuardReleaseNamedLock($lockName);
		return false;
	}
	$challenges=$_pm['mysql']->getRecords('SELECT id,challenger_id,defenser_id,challenger_score,defenser_score FROM guild_challenges WHERE flags=1 FOR UPDATE');
	if(!is_array($challenges) && mysql_errno($_pm['mysql']->getConn())!==0)
	{
		$_pm['mysql']->query('ROLLBACK');
		$_pm['mysql']->query('DROP TABLE IF EXISTS `'.$next.'`');
		kdjlGuardReleaseNamedLock($lockName);
		return false;
	}
	if(!is_array($challenges)) $challenges=array();

	$messages=array();
	$settleOk=true;
	foreach($challenges as $challenge)
	{
		$challengeId=intval($challenge['id']);
		$challengerId=intval($challenge['challenger_id']);
		$defenserId=intval($challenge['defenser_id']);
		$c=$_pm['mysql']->getOneRecord('SELECT name FROM guild WHERE id='.$challengerId.' FOR UPDATE');
		$d=$_pm['mysql']->getOneRecord('SELECT name FROM guild WHERE id='.$defenserId.' FOR UPDATE');
		$msg='';
		if(is_array($c) && isset($c['name']) && is_array($d) && isset($d['name']))
		{
			$challengerName=htmlspecialchars((string)$c['name'],ENT_QUOTES,'UTF-8');
			$defenserName=htmlspecialchars((string)$d['name'],ENT_QUOTES,'UTF-8');
			$challengerScore=intval($challenge['challenger_score']);
			$defenserScore=intval($challenge['defenser_score']);
			if($challengerScore>$defenserScore)
			{
				$settleOk=$_pm['mysql']->query('UPDATE guild SET victory_times=COALESCE(victory_times,0)+1 WHERE id='.$challengerId)
					&& $_pm['mysql']->query('UPDATE guild SET failed_times=COALESCE(failed_times,0)+1 WHERE id='.$defenserId);
				$msg='<strong>《'.$challengerName.'》</strong>家族在与<strong>《'.$defenserName.'》</strong>家族的战斗中获得胜利！';
			}
			else if($challengerScore<$defenserScore)
			{
				$settleOk=$_pm['mysql']->query('UPDATE guild SET failed_times=COALESCE(failed_times,0)+1 WHERE id='.$challengerId)
					&& $_pm['mysql']->query('UPDATE guild SET victory_times=COALESCE(victory_times,0)+1 WHERE id='.$defenserId);
				$msg='<strong>《'.$challengerName.'》</strong>家族在与<strong>《'.$defenserName.'》</strong>家族的战斗中失败！';
			}
			else
			{
				$msg='<strong>《'.$challengerName.'》</strong>家族与<strong>《'.$defenserName.'》</strong>家族战成平局';
			}
		}
		if(!$settleOk || !$_pm['mysql']->query('UPDATE guild_challenges SET flags=2 WHERE id='.$challengeId.' AND flags=1') || mysql_affected_rows($_pm['mysql']->getConn())!==1)
		{
			$settleOk=false;
			break;
		}
		if($msg!=='') $messages[]=$msg;
	}

	if(!$settleOk || !$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		$_pm['mysql']->query('DROP TABLE IF EXISTS `'.$next.'`');
		kdjlGuardReleaseNamedLock($lockName);
		return false;
	}
	if(!$_pm['mysql']->query('RENAME TABLE `guild_challenges` TO `'.$archive.'`, `'.$next.'` TO `guild_challenges`'))
	{
		$_pm['mysql']->query('DROP TABLE IF EXISTS `'.$next.'`');
		kdjlGuardReleaseNamedLock($lockName);
		return false;
	}
	foreach($messages as $msg) $s->sendMsg('SYS|'.$msg,'__ALL__');

	kdjlGuardReleaseNamedLock($lockName);
	kdjlGuardPruneDatedTables('guild_challenges',5);
	kdjlGuardPruneDatedTables('ticket_',5);
	return true;
}

function kdjlGuardReleaseNamedLock($lockName)
{
	global $_pm;
	$lockNameSql=mysql_real_escape_string($lockName,$_pm['mysql']->getConn());
	$_pm['mysql']->getOneRecord("SELECT RELEASE_LOCK('{$lockNameSql}') AS released");
}

function kdjlGuardPruneDatedTables($prefix,$keep)
{
	global $_pm;
	$keep=max(0,intval($keep));
	$prefixSql=mysql_real_escape_string($prefix.'%',$_pm['mysql']->getConn());
	$rows=$_pm['mysql']->getRecords("SHOW TABLES LIKE '{$prefixSql}'");
	if(!is_array($rows)) return;
	$tables=array();
	$pattern=$prefix==='ticket_' ? '/^ticket_[0-9]{8}$/D' : '/^guild_challenges[0-9]{8}$/D';
	foreach($rows as $row)
	{
		foreach($row as $tableName)
		{
			if(preg_match($pattern,$tableName)) $tables[]=$tableName;
		}
	}
	rsort($tables,SORT_STRING);
	for($i=$keep;$i<count($tables);$i++) $_pm['mysql']->query('DROP TABLE `'.$tables[$i].'`');
}


function checkGuildFightEnd()
{
	global $_pm;
	$week = date("N", time());
	$hourM= date("Hi", time());

	$battletimearr = kdjlSafeMemValue($_pm['mem']->get(MEM_TIME_KEY), array());
	if(!is_array($battletimearr)) $battletimearr = array();
	foreach($battletimearr as $bv)
	{
		if($bv['titles'] != "guild_battle")
		{
			continue;
		}
		if(isWeeklyDayTimeFinished($bv['days'], $bv['endtime'], $week, $hourM))//家族战结束了
		{
			calcGuildFight();
		}
		else
		{
			//echo $week .'=='. $bv['days'] .'&&'. $hourM .'>'. $bv['endtime'].'<br>';
		}
	}
	return false;
}

$curminute = intval(date("i"));
if($curminute%2==0)
{
	$cur = $_pm['mysql']->getOneRecord('select left(from_unixtime(ctime),16) lastct from game_count order by id desc limit 1');
	if(!$cur||$cur['lastct']!=date("Y-m-d H:i")){
		$domainPrefix = "pokeelf";
		$old = kdjlSafeMemValue($_pm['mem']->get($domainPrefix.'_online_user_list'), array());
		if(!is_array($old)) $old=array();
		$time = time()-300;
		foreach($old as $k=>$t)
		{
			if($t<$time) unset($old[$k]);
		}
		$_pm['mem']->set(array('k'=>$domainPrefix.'_online_user_list','v'=>$old));
		$_pm['mem']->set(array('k'=>$domainPrefix.'_online_user','v'=>count($old)));
		$sql = "insert into game_count(ctime,online) values('".time()."','".(count($old))."')";
		//$_pm['mysql']->query('delete from game_count where left(from_unixtime(ctime),16)="'.date("Y-m-d H:i").'"');
		$_pm['mysql']->query($sql);
	}
	sleep(1);
}

// 记录数据库连接状态。连接回收和慢查询终止由 MySQL 自身配置处理。
function doWork1($time)
{
	global $_pm;
	$result = $_pm['mysql']->getRecords('SHOW PROCESSLIST');
	if(is_array($result))
	{
		$_pm['mem']->set(array('k'=>'guard_thread_status','v'=>$time.' - MySQL 线程数：'.count($result)));
		return true;
	}
	$_pm['mem']->set(array('k'=>'guard_thread_status','v'=>$time.' - 无法读取 MySQL 进程列表'));
	logsqlerr('SHOW PROCESSLIST 查询失败');
	return false;
}

//战场结束，统计排名，领取奖励
function doWork2($time)
{
	return;
	global $_pm;
	$timeconfig = unserialize($_pm['mem']->get('db_timeconfig'));
	if(!is_array($timeconfig)) return;
	foreach($timeconfig as $v){
		if($v['titles'] == 'battle'){
			$arr[$v['days']] = $v['endtime'];
		}
		else continue;
	}
	if(!isset($arr) || !is_array($arr)) return;
	$day = date('w');
	$str = $arr[$day];
	if(empty($str)) return;
	$hi = date('H:i');

	//避免重复发奖
	$check = unserialize($_pm['mem'] -> get('battle_prize_check'));
	$timenow = time() - 300;
	if(!empty($check) && $check <= $timenow) return;
	$_pm['mem'] -> set(array('k'=>'battle_prize_check','v'=>time()));


	if($str != $hi) return;
    $sql = "SELECT id,min(hp)AS hp,posname FROM battlefield LIMIT 1";
	$winner = $_pm['mysql'] -> getOneRecord($sql);
	//// 战场胜利公告
	if($winner['id'] == 1) $fail = '暗夜女神阵营';
	else if($winner['id'] == 2 ) $fail = '自然女神阵营';
	$pub = new task();
    $word = '[系统公告] 本次战场结束，'.$fail.'被打得溃不成军，'.$winner['posname'].'取得了胜利！';
	for($i=0;$i<5;$i++){
		$pub-> saveGword($word, 1);
	}


    $today = time() - 3600;
	$winarr = $_pm['mysql']->getRecords("SELECT id
													FROM battlefield_user
												   WHERE lastvtime>$today and curjgvalue>0 and pos={$winner['id']}
												   ORDER BY curjgvalue DESC
												   LIMIT 0,10
												");
	if(is_array($winarr)){
		$v = '';
		foreach($winarr as $k => $v){
			$boxnum = 0;
			$jgvl   = 0;
			switch(($k+1))
		   {
			  case 1: $boxnum=10; $jgvl = 2000; break;
			  case 2:
			  case 3: $boxnum=6; $jgvl = 1500;break;
			  case 4:
			  case 5:
			  case 6: $boxnum=4; $jgvl = 1000;break;
			  case 7:
			  case 8:
			  case 9:
			  case 10: $boxnum=2; $jgvl = 500;break;
			  default: $boxnum=$jgvl=0;
		   }
		  // 更新玩家的排名.
		  $_pm['mysql']->query("UPDATE battlefield_user
								   SET tops=".($k+1).", boxnum={$boxnum}, curjgvalue=curjgvalue+{$jgvl}
								 WHERE id={$v['id']}
							   ");
		}
	}
    $all = $_pm['mysql']->getRecords("SELECT id
										FROM battlefield_user
									   WHERE lastvtime>$today and curjgvalue>0 and pos!={$winner['id']}
									   ORDER BY curjgvalue DESC
									   LIMIT 0,10
									");
	if (is_array($all))
   {
	   foreach ($all as $k => $rs)
	   {
		   $boxnum = 0;
		   $jgvl   = 0;
		   switch(($k+1))
		   {
			  case 1: $boxnum=5; $jgvl = 1000; break;
			  case 2:
			  case 3: $boxnum=3; $jgvl = 500;break;
			  case 4:
			  case 5:
			  case 6: $boxnum=2; $jgvl = 300;break;
			  case 7:
			  case 8:
			  case 9:
			  case 10: $boxnum=1; $jgvl = 100;break;
			  default: $boxnum=$jgvl=0;
		   }
		   // 更新玩家的排名.
		   $_pm['mysql']->query("UPDATE battlefield_user
									SET tops=".($k+1).", boxnum={$boxnum}, curjgvalue=curjgvalue+{$jgvl}
								  WHERE id={$rs['id']}
							   ");
	   }
   }

   $time = time();
   $_pm['mysql'] -> query("INSERT INTO gamelog (ptime,buyer,seller,pnote,vary) VALUES($time,'1','1','jgprize','200')");
}

/*
function doWork3($time){
	global $_pm;
	$sql='select sum(yb) fee,nickname from yblog where buytime>'.strtotime(date("Y-m-d ").'00:00:00').' and buytime<'.strtotime(date("Y-m-d ").'23:59:59').' group by nickname order by sum(yb) desc limit 50';
	$rows = $_pm['mysql']->getRecords($sql);
	$memtimeconfig = unserialize($_pm['mem']->get('db_timeconfignew'));
	$config=$memtimeconfig['consumptionTop'][0];
	if($config['starttime']==0){
		return;
	}else{
		if($config['starttime']>date('H') || $config['endtime']<date('H'))
		{
			return;
		}else{
			$ck=$_pm['mysql']->getOneRecord('select id from gamelog where vary=240 AND buyer="'.date('Ymd').'" limit 1');//检查发奖
			if(!$ck){
				//发公告

				$a = getLock(1);

				$now = date('Ymd');
				$check = unserialize($_pm['mem'] -> get('fee_prize_check'));
				if($check != $now){
					$_pm['mem'] -> set(array('k'=>'fee_prize_check','v'=>$now));
					$task = new task();//恭喜xxx（玩家名）荣登今日消费排行榜榜首，请获得今日消费排行的玩家前往公告牌及时领取奖励。
					foreach($rows as $rk => $rv){
						if($rk > 2){
							break;
						}
						$ruser = $_pm['mysql'] -> getOneRecord('SELECT id,nickname FROM player WHERE name = "'.$rv['nickname'].'"');
						$prizes=explode(',',$config['days']);
						foreach($prizes as $k=>$v)
						{
							if($k >= $rk){
								$res = explode(';',$v);
								if($res[1] < $rv['fee']){
									if($flag == 0){
										$word = "恭喜 {$ruser['nickname']} ,荣登今日消费排行榜榜首，获得相应珍贵奖励。";
										$swfData=kdjlSafeIconv('utf-8','utf-8',$word);
										$s=new socketmsg();
										$s->sendMsg('an|'.$swfData);
										$str = '<font color=red>'.$ruser['nickname'].'</font>';
									}else if($flag == 1){
										$str = '<font color=blue>'.$ruser['nickname'].'</font>';
									}else if($flag == 2){
										$str = '<font color=green>'.$ruser['nickname'].'</font>';
									}
									givePrize($rv['nickname'],$res[0],$task);
									$sql = 'insert into gamelog set buyer="'.date('Ymd').'",vary=240,seller='.$ruser['id'].',ptime='.time().',pnote="'.$str.'"';
									$_pm['mysql']->query($sql);
									$flag++;
									break;
								}
							}
						}
					}
					$num = rand(0,(count($rows)-1));
					$xprize = $rows[$num];//幸运奖
					$ruser = $_pm['mysql'] -> getOneRecord('SELECT id,nickname FROM player WHERE name = "'.$xprize['nickname'].'"');

					$sql = 'insert into gamelog set buyer="'.date('Ymd').'",vary=240,seller='.$ruser['id'].',ptime='.time().',pnote="'.$ruser['nickname'].'"';
					$_pm['mysql']->query($sql);
					$word = "恭喜 {$ruser['nickname']} ,荣登今日消费排行幸运奖，获得相应奖励。";
					$swfData=kdjlSafeIconv('utf-8','utf-8',$word);
					$s->sendMsg('an|'.$swfData);
					givePrize($xprize['nickname'],$prizes[3],$task);
				}
			}
		}
		realseLock();
	}
}
*/


$fortressPrizePendingLogIds = array();
$fortressPrizeCommitted = true;
$fortressPrizeTransactionActive = false;
function fortressPrizeTrackLastLog()
{
	global $_pm,$fortressPrizePendingLogIds;
	$id = intval($_pm['mysql']->last_id());
	if($id < 1) return false;
	$fortressPrizePendingLogIds[$id] = $id;
	return true;
}
function fortressPrizeCleanupPendingLogs()
{
	global $_pm,$fortressPrizePendingLogIds,$fortressPrizeCommitted;
	if(!$fortressPrizeCommitted && !empty($fortressPrizePendingLogIds) && isset($_pm['mysql']))
	{
		$_pm['mysql']->query('DELETE FROM gamelog WHERE id IN ('.implode(',',array_values($fortressPrizePendingLogIds)).')');
	}
	$fortressPrizePendingLogIds = array();
}
function fortressPrizeShutdown()
{
	global $_pm,$fortressPrizeTransactionActive;
	if($fortressPrizeTransactionActive && isset($_pm['mysql'])) $_pm['mysql']->query('ROLLBACK');
	$fortressPrizeTransactionActive = false;
	fortressPrizeCleanupPendingLogs();
	if(function_exists('realseLock')) realseLock();
}

function doWork4($time){
	global $_pm;
	global $fortressPrizePendingLogIds,$fortressPrizeCommitted,$fortressPrizeTransactionActive;
	$setting = $_pm['mem']->get('db_welcome1');
	if(!is_array($setting)) $setting=kdjlSafeMemValue($setting, array());
	if(!is_array($setting))
	{
        return '后台配置数据读取失败(1)：'.print_r($setting,1);
	}

	$time_settings=preg_split('/[\s|]+/',trim(isset($setting['fortress_time']) ? $setting['fortress_time'] : ''),-1,PREG_SPLIT_NO_EMPTY);
	$w=date('w');
	$hm=date('His');
	if($w==0)
	{
		$w=7;
	}
	$time_flag=false;
	foreach($time_settings as $s)
	{
		$tmp=explode(',',$s);
		if(count($tmp) < 5) continue;
		//1,210000,210459,212959,213459
		if($w==intval($tmp[0]))
		{
			if(intval($hm)>intval($tmp[4]))
			{
				$time_flag=true;
			}
			break;
		}
	}

	if(!$time_flag){
        return '现在不是要塞奖励结算时间！';
	}

	$a = getLock(-246);
	if(!is_array($a))
	{
		return '要塞奖励正在结算，请稍候再试！';
	}
	$today = date('Ymd');
	$mk='yaosai_prize_set2_'.$today;
	$flag = $_pm['mem']->get($mk);
	if($flag)
	{
		realseLock();
		return '今日要塞奖励已经结算完成！';
	}
	$fortressPrizePendingLogIds = array();
	$fortressPrizeCommitted = false;
	$fortressPrizeTransactionActive = true;
	register_shutdown_function('fortressPrizeShutdown');
	$existing = $_pm['mysql']->getOneRecord('select id from gamelog where vary=246 and buyer="'.$today.'" limit 1 FOR UPDATE');
	if(is_array($existing))
	{
		$_pm['mysql']->query('ROLLBACK');
		$fortressPrizeTransactionActive = false;
		$fortressPrizeCommitted = true;
		$_pm['mem']->set(array('k'=>$mk,'v'=>1));
		realseLock();
		return '今日要塞奖励已经结算完成！';
	}

	$notice='';
	$allPrizeOk = true;
	$recipientIds = array();
	$table_name="`fortress_users_".$today."`";
	$users_first=$_pm['mysql']->getRecords('select a.* from '.$table_name.' a inner join (select at_section_num,max(score_final) score_final from '.$table_name.' where score_final != 0 group by at_section_num) z on a.at_section_num=z.at_section_num and a.score_final=z.score_final');
	if(!is_array($users_first) && mysql_errno($_pm['mysql']->getConn()) != 0)
	{
		$allPrizeOk = false;
		$users_first = array();
	}
	if(!is_array($users_first)) $users_first = array();
	$set=preg_split('/\s+/',trim(isset($setting['fortress']) ? $setting['fortress'] : ''),-1,PREG_SPLIT_NO_EMPTY);
	$prize_set=array();
	foreach($set as $k=>$s)
	{
		$tmp=explode(',',$s);
		if(count($tmp) < 4 || trim($tmp[3]) === '')
		{
			$allPrizeOk = false;
			break;
		}
		$prize_set[$k+1]=trim($tmp[3]);
	}
	$props = kdjlSafeMemValue($_pm['mem']->get("db_propsid"), array());
	if(!is_array($props)) $props = array();
	if($allPrizeOk && !empty($users_first))
	{
		foreach($users_first as $user)
		{
			if(!is_array($user) || !isset($user['score_final']) || intval($user['score_final']) < 0) continue;
			$section = isset($user['at_section_num']) ? intval($user['at_section_num']) : 0;
			$userId = isset($user['user_id']) ? intval($user['user_id']) : 0;
			if($section < 1 || $userId < 1 || !isset($prize_set[$section]))
			{
				$allPrizeOk = false;
				break;
			}
			$prizes=explode('|',$prize_set[$section]);
			$userNotice='';
			foreach($prizes as $p)
			{
				$t=explode(':',trim($p));
				if(count($t) != 2)
				{
					$allPrizeOk = false;
					break 2;
				}
				$pid = intval($t[0]);
				$num = intval($t[1]);
				if($pid < 1 || $num < 1 || !isset($props[$pid]) || !saveGetPropsMore_S($pid,$num,$userId))
				{
					$allPrizeOk = false;
					break 2;
				}
                $log='insert into gamelog set buyer="'.$today.'",vary=246,seller='.$userId.',ptime='.time().',pnote="发放奖励成功,成长范围阶段：'.$section.',用户:'.$userId.',奖品id:'.$pid.',数量:'.$num.'"';
				if(!$_pm['mysql']->query($log) || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
				{
					$allPrizeOk = false;
					break 2;
				}
				if(!fortressPrizeTrackLastLog())
				{
					$allPrizeOk = false;
					break 2;
				}
				$propName = isset($props[$pid]['name']) ? $props[$pid]['name'] : $pid;
				$userNotice.=$propName.' '.$num.'个 ';
			}
			$nickname = isset($user['nickname']) ? htmlspecialchars((string)$user['nickname'],ENT_QUOTES,'UTF-8') : $userId;
            $notice.='<br/>&nbsp;&nbsp;&nbsp;&nbsp;恭喜玩家：'.$nickname.'获得女神要塞成长'.$section.'阶段要塞第一名！获得:'.$userNotice;
			$recipientIds[$userId] = $userId;
		}
	}
	else if($allPrizeOk)
	{
		$notice = '<br/>&nbsp;&nbsp;&nbsp;&nbsp;传说中纷争不断的女神要塞今天好像并没有发生过激烈的战斗...';
	}
	if($allPrizeOk)
	{
        $completeLog='insert into gamelog set buyer="'.$today.'",vary=246,seller=-246,ptime='.time().',pnote="女神要塞奖励发放完成"';
		if(!$_pm['mysql']->query($completeLog) || mysql_affected_rows($_pm['mysql']->getConn()) != 1 || !fortressPrizeTrackLastLog()) $allPrizeOk = false;
	}
	if(!$allPrizeOk)
	{
		$_pm['mysql']->query('ROLLBACK');
		$fortressPrizeTransactionActive = false;
		fortressPrizeCleanupPendingLogs();
		realseLock();
		return '要塞奖励发放失败！';
	}
	if(!$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		$fortressPrizeTransactionActive = false;
		fortressPrizeCleanupPendingLogs();
		realseLock();
		return '提交要塞奖励结算失败！';
	}
	$fortressPrizeCommitted = true;
	$fortressPrizeTransactionActive = false;
	foreach($recipientIds as $recipientId) $_pm['mem']->del(intval($recipientId).'bag');
	$s=new socketmsg();
	$s->sendMsg('an|'.$notice);
	$_pm['mem']->set(array('k'=>$mk,'v'=>1));
	realseLock();
	return '要塞奖励结算完成！';
}


function write_log($vary,$log,$seller){
	global $_pm;
	$_pm['mysql'] -> query('insert into gamelog set buyer='.$seller.',vary='.$vary.',seller='.$seller.',ptime='.time().',pnote="'.$log.'"');
}

function in_arr($arr,$newarr){
	if(!is_array($newarr) || empty($newarr)) return false;
	$tarr = $newarr[rand(0,(count($newarr)-1))];
	if(in_array($tarr['ticket_num'],$arr)){
		return in_arr($arr,$newarr);
	}else{
		return $tarr;
	}
}

function saveGetPropsMore_S($pid,$num,$uid)
{
	global $_pm;
	$pid = intval($pid);
	$num = intval($num);
	$uid = intval($uid);
	if ($pid < 1 || $num < 1 || $uid < 1) return false;
	$rs = false;
	$ok = true;
	$rs = $_pm['mysql']->getOneRecord("SELECT * FROM userbag WHERE uid={$uid} and pid={$pid} and zbing=0 and (cantrade IS NULL OR cantrade<>3) ORDER BY id LIMIT 1 FOR UPDATE");
	if (is_array($rs))
	{
		if ($rs['vary'] == 1) // 可折叠道具.
		{
			$tt = time();
			$sql = "UPDATE userbag
						   SET sums=COALESCE(sums,0)+$num,
							   stime={$tt}
						 WHERE id={$rs['id']} and uid={$uid} and pid={$pid} and vary=1 and COALESCE(sums,0) <= 2147483647-$num and zbing=0 and (cantrade IS NULL OR cantrade<>3)
					  ";
			if ($_pm['mysql']->query($sql) === false) $ok = false;
			else if(mysql_affected_rows($_pm['mysql']->getConn()) != 1) $ok = false;
		}
		else
		{
			$values = array();
			for($i=0; $i<$num; $i++)
			{
				$values[] = "('{$uid}','{$pid}','{$rs['sell']}','{$rs['vary']}',1,unix_timestamp())";
			}
			$sql = "INSERT INTO userbag(uid,pid,sell,vary,sums,stime) VALUES ".implode(',', $values);
			if ($_pm['mysql']->query($sql) === false) $ok = false;
			else if(mysql_affected_rows($_pm['mysql']->getConn()) != $num) $ok = false;
	   }
	}
	else{
		$rs = $_pm['mysql'] -> getOneRecord("SELECT * FROM props WHERE id = $pid");
		if (is_array($rs))
		{
			if(intval($rs['vary']) == 1)
			{
				$sql = "INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
							VALUES(
								   '{$uid}',
								   '{$pid}',
								   '{$rs['sell']}',
								   '{$rs['vary']}',
								   {$num},
								   unix_timestamp()
								  )
						  ";
				$expectRows = 1;
			}
			else
			{
				$values = array();
				for($i=0; $i<$num; $i++)
				{
					$values[] = "('{$uid}','{$pid}','{$rs['sell']}','{$rs['vary']}',1,unix_timestamp())";
				}
				$sql = "INSERT INTO userbag(uid,pid,sell,vary,sums,stime) VALUES ".implode(',', $values);
				$expectRows = $num;
			}
			if ($_pm['mysql']->query($sql) === false) $ok = false;
			else if(mysql_affected_rows($_pm['mysql']->getConn()) != $expectRows) $ok = false;
		}else{
			return false;
		}
	}
	unset($rs);
	return $ok;
}

function givePrize($name,$pstr,&$tsk)
{
	global $_pm;
	$safeName = $_pm['mysql']->escape($name);
	$user=$_pm['mysql']->getOneRecord('select id from player where name="'.$safeName.'" limit 1');
	if(!is_array($user) || !isset($user['id']))
	{
		return false;
	}
	$prize=explode('|',$pstr);
	$issued = false;
	foreach($prize as $p)
	{
		$t=explode(':',$p);
		if(count($t) < 2 || $t[0] == '') continue;
		$pid = intval($t[0]);
		$num = intval($t[1]);
		if($pid < 1 || $num < 1) continue;
		$logName = $_pm['mysql']->escape($name);
		if(!saveGetPropsMore_S($pid,$num,$user['id']))
		{
			$log='insert into gamelog set buyer="'.date('Ymd').'",vary=239,seller='.$user['id'].',ptime='.time().',pnote="发放奖励失败,用户:'.$logName.',奖品id:'.$pid.',数量:'.$num.'"';
			$_pm['mysql']->query($log);
			return false;
		}else{
			$log='insert into gamelog set buyer="'.date('Ymd').'",vary=239,seller='.$user['id'].',ptime='.time().',pnote="发放奖励成功,用户:'.$logName.',奖品id:'.$pid.',数量:'.$num.'"';
			$issued = true;
		}
		$_pm['mysql']->query($log);
	}
	return $issued;
}


function wr($i){
	$filename = dirname(__FILE__).'/t/test'.$i.'.txt';
	$somecontent = date("Y-m-d H:i:s")."\r\n";

    $handle = fopen($filename, 'a+');

    if (fwrite($handle, $somecontent) === FALSE) {
        exit;
    }

    fclose($handle);
}


function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}


function logsqlerr($msg="")
{
	global $_pm;
	$err = mysql_error($_pm['mysql']->getConn());
	if($err === '') return false;
	$old = date('Y-m-d H:i:s').': '.$err;
	if($msg !== '') $old .= '<br>'.$msg;
	$old .= '<br/>'.kdjlSafeMemValue($_pm['mem']->get('guard_thread_error'), '');
	if(strlen($old)>4096) $old = substr($old,0,3072);
	$_pm['mem']->set(array('k'=>'guard_thread_error','v'=>$old));
	return true;
}
function doWork5($time)
{
	global $_pm;
	$clear = kdjlSafeMemValue($_pm['mem']->get('SL_CLEAR_TIME'), '');
	$in = date('Ymd',$time);
	if(empty($clear) || !isset($clear))
	{
		$_pm['mem']->set(array('k'=>'SL_CLEAR_TIME','v'=>$in));
	}
	else
	{
		if($clear != $in)
		{
			$_pm['mysql'] -> query("UPDATE player_ext SET F_saolei_points = '1'");
			$_pm['mem'] -> del("today_sl_user");
			$_pm['mem'] -> del("today_is_use_ticket");
			$_pm['mem'] -> del("sl_die_option");
			$_pm['mem'] -> del("sl_prize_info");
			$_pm['mem']->set(array('k'=>'SL_CLEAR_TIME','v'=>$in));
		}
	}
}
echo "//OK";
?>
