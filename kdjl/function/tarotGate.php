<?php
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');
require_once(dirname(__FILE__).'/fight_wait_common.php');
secStart($_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('登录状态已失效，请重新登录！');
$teamId = isset($_SESSION['team_id']) ? intval($_SESSION['team_id']) : 0;
if($teamId < 1) die('队伍数据错误！');
$nickname = isset($_SESSION['nickname']) ? $_SESSION['nickname'] : '';
$tarotInTransaction = false;
$tarotAnnouncements = array();
$tarotSocketMessages = array();
$tarotSerializedMemSnapshots = array();
$tarotRawMemSnapshots = array();
$tarotSessionSnapshot = array();
$tarotRequestRegistered = false;
function tarotSnapshotSerializedMem($key)
{
	global $_pm,$tarotSerializedMemSnapshots;
	if($key === '' || isset($tarotSerializedMemSnapshots[$key])) return;
	$raw = $_pm['mem']->get($key);
	$tarotSerializedMemSnapshots[$key] = array(
		'exists' => $raw !== false && $raw !== null,
		'value' => kdjlSafeMemValue($raw, null)
	);
}
function tarotSnapshotRawMem($key,$setter='setns')
{
	global $_pm,$tarotRawMemSnapshots;
	if($key === '' || isset($tarotRawMemSnapshots[$key])) return;
	$raw = $_pm['mem']->get($key);
	$tarotRawMemSnapshots[$key] = array(
		'exists' => $raw !== false && $raw !== null,
		'value' => $raw,
		'setter' => $setter === 'setnsnc' ? 'setnsnc' : 'setns'
	);
}
function tarotSnapshotSession($keys)
{
	global $tarotSessionSnapshot;
	foreach($keys as $key)
	{
		if(isset($tarotSessionSnapshot[$key])) continue;
		$tarotSessionSnapshot[$key] = array(
			'exists' => array_key_exists($key, $_SESSION),
			'value' => array_key_exists($key, $_SESSION) ? $_SESSION[$key] : null
		);
	}
}
function tarotReleaseRequestGuard()
{
	global $requestId,$tarotRequestRegistered;
	if(!$tarotRequestRegistered || intval($requestId) < 1 || !isset($_SESSION['teamfb']) || !is_array($_SESSION['teamfb'])) return;
	foreach($_SESSION['teamfb'] as $key=>$value)
	{
		if(intval($value) === intval($requestId)) unset($_SESSION['teamfb'][$key]);
	}
	$_SESSION['teamfb'] = array_values($_SESSION['teamfb']);
	$tarotRequestRegistered = false;
}
function tarotRestoreSideEffects()
{
	global $_pm,$tarotSerializedMemSnapshots,$tarotRawMemSnapshots,$tarotSessionSnapshot,$tarotSocketMessages;
	foreach($tarotSerializedMemSnapshots as $key=>$snapshot)
	{
		if($snapshot['exists']) $_pm['mem']->set(array('k'=>$key,'v'=>$snapshot['value']));
		else $_pm['mem']->del($key);
	}
	foreach($tarotRawMemSnapshots as $key=>$snapshot)
	{
		if(!$snapshot['exists'])
		{
			$_pm['mem']->del($key);
			continue;
		}
		if($snapshot['setter'] === 'setnsnc') $_pm['mem']->setnsnc($key,$snapshot['value']);
		else $_pm['mem']->setns($key,$snapshot['value']);
	}
	foreach($tarotSessionSnapshot as $key=>$snapshot)
	{
		if($snapshot['exists']) $_SESSION[$key] = $snapshot['value'];
		else unset($_SESSION[$key]);
	}
	$tarotSocketMessages = array();
	tarotReleaseRequestGuard();
}
function tarotStoreCardCache($key, $newValue)
{
	global $_pm;
	if($key === '' || !is_array($newValue)) return false;
	tarotSnapshotSerializedMem($key);
	return $_pm['mem']->set(array('k'=>$key,'v'=>$newValue));
}
function tarotSendMessage($message,$users)
{
	global $s,$tarotInTransaction,$tarotSocketMessages;
	if($tarotInTransaction)
	{
		$tarotSocketMessages[] = array('message'=>$message,'users'=>$users);
		return true;
	}
	return is_object($s) ? $s->sendMsg($message,$users) : false;
}
function tarotFlushMessages()
{
	global $s,$tarotSocketMessages;
	if(!is_object($s)) return false;
	foreach($tarotSocketMessages as $queuedMessage)
	{
		$s->sendMsg($queuedMessage['message'],$queuedMessage['users']);
	}
	$tarotSocketMessages = array();
	return true;
}
function tarotInvalidateMemberCaches($uids)
{
	global $_pm;
	if(!is_array($uids)) return;
	$seen = array();
	foreach($uids as $memberUid)
	{
		$memberUid = intval($memberUid);
		if($memberUid < 1 || isset($seen[$memberUid])) continue;
		$seen[$memberUid] = true;
		$_pm['mem']->del(strval($memberUid));
		$_pm['mem']->del($memberUid.'bb');
		$_pm['mem']->del($memberUid.'bag');
	}
}
function tarotBeginTransaction()
{
	global $tarotInTransaction;
	if($tarotInTransaction) return true;
	$tarotInTransaction = true;
	return true;
}
function tarotRollbackTransaction()
{
	global $_pm,$tarotInTransaction;
	if($tarotInTransaction)
	{
		$_pm['mysql']->query('ROLLBACK');
		tarotRestoreSideEffects();
		$tarotInTransaction = false;
	}
}
function tarotFail($message)
{
	tarotRollbackTransaction();
	if(function_exists('realseLock')) realseLock();
	die($message);
}
function tarotCommitTransaction()
{
	global $_pm,$tarotInTransaction;
	if(!$tarotInTransaction) return true;
	if(!$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		tarotRestoreSideEffects();
		$tarotInTransaction = false;
		return false;
	}
	$tarotInTransaction = false;
	return true;
}
function tarotGateImage($value)
{
	$value = basename(strval($value));
	if(!preg_match('/^[A-Za-z0-9_.-]+$/D', $value) ||
		!file_exists(dirname(__FILE__).'/../images/tarot/'.$value)) return 'card.gif';
	return $value;
}
function tarotRollbackOnShutdown()
{
	tarotRollbackTransaction();
}
register_shutdown_function('tarotRollbackOnShutdown');
function tarotMemberRow($mem)
{
	if(!is_array($mem)) return false;
	if(!isset($mem['state'])) $mem['state'] = 0;
	if(!isset($mem['uid'])) $mem['uid'] = 0;
	$mem['state'] = intval($mem['state']);
	$mem['uid'] = intval($mem['uid']);
	if($mem['uid'] < 1) return false;
	return $mem;
}
function tarotTeamMembers($teamInfo)
{
	if(!is_array($teamInfo) || !isset($teamInfo['members']) || !is_array($teamInfo['members'])) return array();
	return $teamInfo['members'];
}
function tarotKickTeamMember($team,$teamId,$memberUid)
{
	global $_pm;
	$teamId=intval($teamId);
	$memberUid=intval($memberUid);
	if(!is_object($team) || $teamId<1 || $memberUid<1) return false;
	$teamRow=$_pm['mysql']->getOneRecord('select id,inmap,creator from team where id='.$teamId.' for update');
	if(!is_array($teamRow) || !isset($teamRow['creator']) || intval($teamRow['creator'])==$memberUid) return false;
	if(!$_pm['mysql']->query('delete from team_members where team_id='.$teamId.' and uid='.$memberUid.' and state>-1') ||
		mysql_affected_rows($_pm['mysql']->getConn())!=1) return false;
	if(!$team->syncChatTeamId($memberUid,0)) return false;
	if(!$team->refreshTeamInfo()) return false;
	if(isset($teamRow['inmap']) && !$team->refreshTeamList(intval($teamRow['inmap']))) return false;
	$activeMembers=array();
	foreach(tarotTeamMembers($team->getTeamInfo($teamId)) as $member)
	{
		$member=tarotMemberRow($member);
		if($member!==false && $member['state']>-1) $activeMembers[]=$member['uid'];
	}
	tarotSendMessage('SYSN|updateYouTeam',$activeMembers);
	tarotSendMessage('SYSN|uareKicked',$memberUid);
	tarotSendMessage('SYSLTEAM|no',array($memberUid));
	return true;
}
function tarotDisbandTeam($team,$teamId)
{
	global $_pm;
	$teamId=intval($teamId);
	if(!is_object($team) || $teamId<1) return false;
	$teamRow=$_pm['mysql']->getOneRecord('select id,inmap from team where id='.$teamId.' for update');
	if(!is_array($teamRow) || !isset($teamRow['inmap'])) return false;
	$memberIds=array();
	foreach(tarotTeamMembers($team->getTeamInfo($teamId)) as $member)
	{
		$member=tarotMemberRow($member);
		if($member!==false && $member['state']>-1) $memberIds[]=$member['uid'];
	}
	if(!$_pm['mysql']->query('delete from team_members where team_id='.$teamId)) return false;
	if(!$_pm['mysql']->query('delete from team where id='.$teamId) || mysql_affected_rows($_pm['mysql']->getConn())!=1) return false;
	if(!$team->syncChatTeamId($memberIds,0)) return false;
	$_pm['mem']->del('pm_team_'.$teamId);
	$_pm['mem']->del('pm_team_fight_'.$teamId);
	if(!$team->refreshTeamList(intval($teamRow['inmap'])) || !$team->updateTeamListMem()) return false;
	if(isset($_SESSION['team_id']) && intval($_SESSION['team_id'])==$teamId)
	{
		unset($_SESSION['team_id']);
		unset($_SESSION['team_inmap']);
		unset($_SESSION['team_state']);
	}
	if(!empty($memberIds))
	{
		tarotSendMessage('SYSLTEAM|'.$teamId,$memberIds);
		tarotSendMessage('SYSN|disbandTeam',$memberIds);
	}
	return true;
}
$a = getLock($uid);
if(!is_array($a)){
	realseLock();
	die('服务器繁忙，请稍候再试！');
}
if(!getScopedLock('team', $teamId, 5))
{
	realseLock();
	die('队伍操作繁忙，请稍候再试！');
}

require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
$s=new socketmsg();
$team=new team($teamId,$s);


$teamInfo=$team->getTeamInfo();
if(!is_array($teamInfo) || !isset($teamInfo['members']) || !is_array($teamInfo['members']))
{
	realseLock();
	die('队伍数据错误！');
}
$callerActive = false;
foreach(tarotTeamMembers($teamInfo) as $callerMember)
{
	$callerMember = tarotMemberRow($callerMember);
	if($callerMember !== false && $callerMember['uid'] === $uid && $callerMember['state'] == 1)
	{
		$callerActive = true;
		break;
	}
}
if(!$callerActive)
{
	realseLock();
	die('您当前不在队伍中！');
}
$requestOp = (isset($_GET['op']) && !is_array($_GET['op'])) ? $_GET['op'] : '';
$requestId = (isset($_GET['id']) && !is_array($_GET['id'])) ? intval($_GET['id']) : 0;
$uidarr1 = array();
$leader = '';
$member = '';
$jsstr = '';
$ar = array();
$ar1 = array();
$ar2 = array();
$sessionTeamInMap = isset($_SESSION['team_inmap']) ? intval($_SESSION['team_inmap']) : 0;
$teamInfoInMap = (isset($teamInfo['team']) && is_array($teamInfo['team']) && isset($teamInfo['team']['inmap'])) ? intval($teamInfo['team']['inmap']) : $sessionTeamInMap;
$teamInMap = $teamInfoInMap;
if($teamInMap < 1)
{
	realseLock();
	die('队伍地图状态错误！');
}
tarotSnapshotRawMem('pm_team_fight_'.$teamId);
tarotSnapshotRawMem('pm_team_'.$teamId);
tarotSnapshotRawMem('MEM_TEAM_LIST');
tarotSnapshotRawMem('MEM_TEAM_LISTstr','setnsnc');
foreach(array_unique(array($teamInMap,$teamInfoInMap)) as $snapshotInMap)
{
	if(intval($snapshotInMap) < 0) continue;
	tarotSnapshotRawMem('pm_list_team_'.intval($snapshotInMap));
	tarotSnapshotRawMem('pm_list_team_'.intval($snapshotInMap).'_time');
}
tarotSnapshotSession(array('team_id','team_inmap','team_state','gs','gs_status'));
$_SESSION['team_inmap'] = $teamInMap;

if($requestId > 0)
{
	if(!isset($_SESSION['teamfb']) || !is_array($_SESSION['teamfb']))
	{
		$_SESSION['teamfb'] = array();
	}
	if(in_array($requestId, $_SESSION['teamfb'], true))
	{
		realseLock();
		die('2');
	}
	$_SESSION['teamfb'][] = $requestId;
	$tarotRequestRegistered = true;
}
$ct=0;
foreach(tarotTeamMembers($teamInfo) as $mem)
{
	$mem = tarotMemberRow($mem);
	if($mem === false) continue;
	if($mem['state']==1)
	{
		$ct++;
		$uidarr1[] = $mem['uid'];
	}
}

$minimumMembers = kdjlIsSoloTeamDungeonMap($teamInMap) ? 1 : 2;
if($ct<$minimumMembers){
	if(!$team->setTeamState(array(
							'team_fuben_card_step_num'=>-1,
							'team_fuben_step'=>array(0,0),
							'fubensjoj' => 0
							)))
	{
		tarotReleaseRequestGuard();
		realseLock();
		die('保存队伍状态失败！');
	}

	$rs=tarotSendMessage(kdjlSafeIconv('utf-8','utf-8','SYSN|goTarot'),$uidarr1);//echo '['.__LINE__."]<br>";
	tarotReleaseRequestGuard();
	realseLock();
	die('服务器繁忙，请稍候再试！');
}


if($requestOp == 'show'){//显示血量

	$teamInfo=$team->getTeamInfo();

	foreach(tarotTeamMembers($teamInfo) as $mem){
		$mem = tarotMemberRow($mem);
		if($mem === false) continue;
		if($mem['state'] == 1){
		$marr = $_pm['mysql'] -> getOneRecord('SELECT player.nickname as nickname,hp,mp,srchp,srcmp,addhp,addmp,level,player.headimg FROM player,userbb WHERE player.id = '.$mem['uid'].' AND player.mbid = userbb.id AND userbb.uid = player.id');
			if(!is_array($marr)) continue;
			$maxHp = $marr['srchp'] + $marr['addhp'];
			$maxMp = $marr['srcmp'] + $marr['addmp'];
			if($maxHp < 1) $maxHp = 1;
			if($maxMp < 1) $maxMp = 1;
			$hpRate = intval(100 * ($marr['hp'] + $marr['addhp']) / $maxHp);
			$mpRate = intval(100 * ($marr['mp'] + $marr['addmp']) / $maxMp);
			if($hpRate < 0) $hpRate = 0;
			if($mpRate < 0) $mpRate = 0;
			if($hpRate > 100) $hpRate = 100;
			if($mpRate > 100) $mpRate = 100;
			$isleader=$team->isTeamLeader($mem['uid'],$teamId);
			$tarotName = htmlspecialchars(isset($marr['nickname']) ? (string)$marr['nickname'] : '', ENT_QUOTES, 'UTF-8');
			$tarotLevel = isset($marr['level']) ? intval($marr['level']) : 0;
			$tarotHeadimg = isset($marr['headimg']) ? intval($marr['headimg']) : 0;
			if($isleader){//队长../images/bb/t13.gif
				$leader = '<div class="leader">	<!--队长-->
							<div class="name">'.$tarotName.'</div>
							<div class="level">'.$tarotLevel.'</div>
							<div class="avatar"><img src="../images/tarot/face'.$tarotHeadimg.'.gif" /></div>
							<div class="red"><p style="width:'.$hpRate.'%"></p></div>	<!--血量，请修改p的width百分比值-->
							<div class="blue"><p style="width:'.$mpRate.'%"></p></div>
						</div>';
			}else{//成员
				$member .= '<div class="team">	<!--队员-->
							<div class="name">'.$tarotName.'</div>
							<div class="level">'.$tarotLevel.'</div>
							<div class="avatar"><img src="../images/tarot/face'.$tarotHeadimg.'.gif" /></div>
							<div class="red"><p style="width:'.$hpRate.'%"></p></div>
							<div class="blue"><p style="width:'.$mpRate.'%"></p></div>
						</div>';
			}
			unset($marr);
		}
	}
	echo $leader.$member;
}
else if($requestOp == 'o'){
	//显示其它玩家的牌和随机得没有翻的牌
	$flag = '1';
	$arValue = $_pm['mem']->get('tarot_info1_'.$teamId);
	$ar = kdjlSafeMemValue($arValue, array());
	$i = 0;
	if(is_array($ar)){
		foreach($ar as $v){
			if(!is_array($v)) continue;
			$cardType = isset($v['type']) ? intval($v['type']) : 0;
			$cardUid = isset($v['uid']) ? intval($v['uid']) : 0;
			if($cardType == 1){
				$ar1[] = $v;
			}else{
				$ar2[] = $v;
			}
			if($cardUid == $uid){
				continue;
			}

			$cardMsg = isset($v['msg']) ? $v['msg'] : '';
			$jsstr.='**|'.$cardType.'~,~'.$cardMsg;
			$i++;
		}
	}
	if(count($ar2) < 1){
		$type2 = 5;
	}else{
		$type2 = 5 - count($ar2);
	}
	$type1 = 5 - count($ar1);
	if($type1 > 0){
		$sql = 'SELECT id,effect,sj,boss,img,name FROM tarot WHERE sj = 0 AND flag = 0 AND mapid = '.$teamInMap;
		$row = $_pm['mysql'] -> getRecords($sql);
		if(!is_array($row) || empty($row)) $type1 = 0;
		if(!is_array($row)) $row = array();
		$len = count($row) - 1;
		for($j = 0;$j < $type1;$j++){
			$res = $row[rand(0,$len)];
			$msg = '<span class="text">'.showt($res['effect']).'</span>';
			$jsstr.='**|1~,~'.$msg;
			$ar[]=array('type' => 1,'msg' => $msg,'uid' => 0);
		}
	}
	if($type2 > 0){
		$sql = 'SELECT id,effect,sj,boss,img,name FROM tarot WHERE sj != 0 AND flag = 0 AND mapid = '.$teamInMap;
		$row = $_pm['mysql'] -> getRecords($sql);
		if(!is_array($row) || empty($row)) $type2 = 0;
		if(!is_array($row)) $row = array();
		$len = count($row) - 1;
		for($j = 0;$j < $type2;$j++){
			$res = $row[rand(0,$len)];
			$msg = '<span class="text2">'.showt($res['effect']).'</span>';
			$jsstr.='**|2~,~'.$msg;
			$ar[]=array('type' => 2,'msg' => $msg,'uid' => 0);
		}
	}
	$_pm['mem']->set(array('k'=>'tarot_info1_'.$teamId,'v'=>$ar));
	echo $jsstr;
}else{

	$srctime = 3;
	#################增加一个间隔时间################
	$timeKey = 'time'.$uid;
	$time = isset($_SESSION[$timeKey]) ? intval($_SESSION[$timeKey]) : 0;
	if(empty($time)){
		$_SESSION[$timeKey] = time();
	}else{
		$nowtime = time();
		$ctime = $nowtime - $time;
		if($ctime < $srctime){
			// Keep the request lock while allowing the original rapid card flow.
		}
		else{
			$_SESSION[$timeKey] = time();
		}
	}

	$id = $requestId;
	if($id < 1 || $id > 10){
		realseLock();
		die('1');
	}
	$point1 = 0;
	$point2 = 0;
	if($id <= 5){
		$point1 = $team -> get_team_funben_card_step();
	}else{
		$point2 = $team -> get_team_funben_card_step($uid,'_sj');
	}

	//判断是否是第三关;
	if($point1 == 3 || $point2 == 3){
	$point = 3;
	}else{
		$point = $id <= 5?$point1:$point2;
	}
	if($point == 3)	//翻牌记录
	{
		$openedCards = kdjlSafeMemValue($_pm['mem']->get('tarot_info_'.$teamId), array());
		if(is_array($openedCards))
		{
			foreach($openedCards as $openedCard)
			{
				if(is_array($openedCard) && isset($openedCard['id']) && intval($openedCard['id']) === $id)
				{
					realseLock();
					die('2');
				}
			}
		}
	}
	$teamState = $team ->getTeamState();
	if(!is_array($teamState)) $teamState = array();
	if(!isset($teamState['team_fuben_card_step_num'])) $teamState['team_fuben_card_step_num'] = 0;

	//$point = 1;
	if($point == 0){
		realseLock();
		die('2');//已经翻过
	}
	if($teamState['team_fuben_card_step_num'] == 3){
		$isleader=$team->isTeamLeader($uid,$teamId);
		if(!$isleader){
			realseLock();
			die('3');
		}
	}
	$tarotPoint3Times = null;
	$tarotPoint3Finish = false;
	if($id <= 5 && $point != 3){
		$sql = 'SELECT id,effect,sj,boss,img,name FROM tarot WHERE sj = 0 AND flag = 0 AND mapid = '.$teamInMap;

	}else if($id >= 5 && $point != 3){
		$sql = 'SELECT id,effect,sj,boss,img,name FROM tarot WHERE sj != 0 AND flag = 0 AND mapid = '.$teamInMap;
	}else if($point == 3){
		$tarot_times_value = $_pm['mem']->get('tarot_times_'.$teamId);
		$tarot_times = intval(kdjlSafeMemValue($tarot_times_value, 0));
		$tarotPoint3Times = $tarot_times;
		if($tarot_times > 7){
			if(!$team -> set_team_funben_card_prize_got())
			{
				tarotReleaseRequestGuard();
				realseLock();
				die('保存队伍状态失败！');
			}
			$_pm['mem']->del('tarot_info_'.$teamId);
			$_pm['mem'] -> del('tarot_times_'.$teamId);
			tarotReleaseRequestGuard();
			realseLock();
			die('4');
		}else if($tarot_times == 7){
			$tarotPoint3Finish = true;
			$sql = 'SELECT id,effect,sj,boss,img,name FROM tarot WHERE flag = 1 AND boss != "0" AND boss != "" AND mapid = '.$teamInMap;
		}else{
			$sql = 'SELECT id,effect,sj,boss,img,name FROM tarot WHERE flag = 1 AND mapid = '.$teamInMap;
			//$sql = 'SELECT id,effect,sj,boss,img,name FROM tarot WHERE flag = 1 and id = 420';
		}
	}//echo $sql.'<br />';
	$tarot = $_pm['mysql'] -> getRecords($sql);

	if(!is_array($tarot) || empty($tarot)){
		tarotReleaseRequestGuard();
		realseLock();
		die('5');
	}
	$max = count($tarot) - 1;
	$rand = rand(0,$max);
	$newTarot = $tarot[$rand];
	$newTarotSj = isset($newTarot['sj']) ? intval($newTarot['sj']) : 0;
	$newTarotBoss = isset($newTarot['boss']) ? intval($newTarot['boss']) : 0;

	if(!tarotBeginTransaction()){
		tarotReleaseRequestGuard();
		realseLock();
		die('服务器繁忙，请稍候再试！');
	}
	if($point == 3)
	{
		tarotSnapshotSerializedMem('tarot_times_'.$teamId);
		tarotSnapshotSerializedMem('tarot_info_'.$teamId);
		if(!$_pm['mem']->set(array('k'=>'tarot_times_'.$teamId,'v'=>intval($tarotPoint3Times)+1))) tarotFail('保存塔罗牌状态失败！');
		if($tarotPoint3Finish)
		{
			$_pm['mem']->del('tarot_info_'.$teamId);
			$_pm['mem']->del('tarot_times_'.$teamId);
			if(!$team -> set_team_funben_card_prize_got()) tarotFail('保存队伍状态失败！');
		}
	}
	if(!$_pm['mysql']->query("INSERT INTO player_ext(uid,bbshow) VALUES({$uid},5) ON DUPLICATE KEY UPDATE uid=uid")){
		tarotFail('6');
	}
	if($newTarotSj > 0){
		if(!$_pm['mysql'] -> query('UPDATE player_ext SET sj = sj-'.$newTarotSj.' WHERE uid = '.$uid.' AND sj >= '.$newTarotSj) ||
			mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){
			tarotFail('6');
		}
	}

	if($newTarotBoss != 0 || ($point < 3 && $id <= 5)){
		if(!$team -> set_team_funben_card_prize_got()) tarotFail('保存队伍状态失败！');
		tarotSnapshotSerializedMem('tarot_info_'.$teamId);
		tarotSnapshotSerializedMem('tarot_times_'.$teamId);
		$_pm['mem']->del('tarot_info_'.$teamId);
		$_pm['mem'] -> del('tarot_times_'.$teamId);
		if($newTarotBoss != 0){
			//写怪物数据
			if(!$team -> setTeamMonsters($newTarotBoss)) tarotFail('保存队伍怪物状态失败！');
		}
	}

	if($point < 3 && $id > 5){
		if(!$team -> set_team_funben_card_prize_got($uid,'_sj')) tarotFail('保存队伍状态失败！');
	}


	//效果 填写格式：all_hp_add:10%|all_money_add:100|all_giveitems:747:1:3:1,738:1:10:1,739:1:10:1|all_fight:1|fight_one:0|fight_all:0|all_money_less:100|all_hp_less:10%|all_exp_add:100|money_add:100|exp_add:100|giveitems:1225:15,1308:10,734:1,1142:3|fight_one:0
	$newTarotEffect = isset($newTarot['effect']) ? $newTarot['effect'] : '';
	$newTarotId = isset($newTarot['id']) ? intval($newTarot['id']) : 0;
	$effect = explode('|',$newTarotEffect);
	if($newTarotBoss != 0){
		$uidarr = array();
		if(isset($_SESSION['gs']) && ($_SESSION['gs'] == 3 || !empty($_SESSION['gs'])))
		{
			$_SESSION['gs'] = "";
			unset($_SESSION['gs']);
		}
		//是boss
		$teamInfo=$team->getTeamInfo();
		foreach(tarotTeamMembers($teamInfo) as $mem){
			$mem = tarotMemberRow($mem);
			if($mem === false) continue;
			if($mem['state'] == 1){
				$uidarr[] = $mem['uid'];
			}
		}
		if(!$team->setTeamState(
							array(
								'team_fuben_boss'=>1								)
							)) tarotFail('保存队伍状态失败！');
		echo 'boss===>';

		$rs=tarotSendMessage(kdjlSafeIconv('utf-8','utf-8','SYSN|getTeamFightMod'),$uidarr);
		$rs=tarotSendMessage(kdjlSafeIconv('utf-8','utf-8','SYSN|alertTarot->遇到boss了！！！'),$uidarr);
	}else{
		foreach($effect as $v){
			$uidarr = array();
			$memarr = array();
			$nuid = array();
			$ret = '';
			$arr = explode(':',$v);
			if(!isset($arr[0]) || $arr[0] === '') continue;
			if(!isset($arr[1])) $arr[1] = 0;
			switch ($arr[0]){
				case 'all_hp_less'://全体HP减少
					$teamInfo=$team->getTeamInfo();
					if(strpos($arr[1],'%') === false) { // not percent
						$damage = intval($arr[1]);
						if($damage < 1) break;
						foreach(tarotTeamMembers($teamInfo) as $mem){
							$mem = tarotMemberRow($mem);
							if($mem === false) continue;
							if($mem['state'] == 1){
								$mbid = getMbid($mem['uid']);
								$hp = getMaxHp($mbid, $mem['uid']);
								if(!is_array($hp)) continue;
								if($hp['totalhp'] <= $damage){
									if(!$_pm['mysql'] -> query('UPDATE userbb SET hp = 0,addhp = 0 WHERE id = '.$mbid.' AND uid = '.$mem['uid'])) tarotFail('保存宠物血量失败！');
								}else{
									$takeAddHp = min($damage, max(0, intval($hp['addhp'])));
									$takeHp = $damage - $takeAddHp;
									if(!$_pm['mysql'] -> query('UPDATE userbb SET hp = GREATEST(0,hp - '.$takeHp.'),addhp = GREATEST(0,addhp - '.$takeAddHp.') WHERE id = '.$mbid.' AND uid = '.$mem['uid'])) tarotFail('保存宠物血量失败！');
								}
								$uidarr[] = $mem['uid'];
							}
						}
					}else{//是百分比
						$num = floatval(str_replace('%','',$arr[1])) * 0.01;
						if($num <= 0) break;
						foreach(tarotTeamMembers($teamInfo) as $mem){
							$mem = tarotMemberRow($mem);
							if($mem === false) continue;
							if($mem['state'] == 1){
								$mbid = getMbid($mem['uid']);
								$hp = getMaxHp($mbid, $mem['uid']);
								if(!is_array($hp)) continue;
								$takeHp = intval(round($hp['srchp'] * $num));
								$takeAddHp = intval(round($hp['addhp'] * $num));
								if(!$_pm['mysql'] -> query('UPDATE userbb SET hp = GREATEST(0,hp - '.$takeHp.'),addhp = GREATEST(0,addhp - '.$takeAddHp.') WHERE id = '.$mbid.' AND uid = '.$mem['uid'])) tarotFail('保存宠物血量失败！');
								$uidarr[] = $mem['uid'];
							}
						}
					}


					$rs=tarotSendMessage(kdjlSafeIconv('utf-8','utf-8','SYSN|changhp'),$uidarr);
					$rs=tarotSendMessage(kdjlSafeIconv('utf-8','utf-8','SYSN|alertTarot->全体减少'.$arr[1].'的血量！'),$uidarr);
					echo 'hp===>';
					break;
				case 'all_money'://全体获得同等金币奖励或惩罚，惩罚填负
					$teamInfo=$team->getTeamInfo();
					$memarr = array();
					$nuid = array();
					foreach(tarotTeamMembers($teamInfo) as $mem){
						$mem = tarotMemberRow($mem);
						if($mem === false) continue;
						if($mem['state'] == 1){
							if(!moneyAdd($mem['uid'],$arr[1])){
								tarotFail('保存金币奖励失败！');
							}
							$uidarr[] = $mem['uid'];
						}
					}
					if($arr[1] < 0){
						$rs=tarotSendMessage(kdjlSafeIconv('utf-8','utf-8','SYSN|alertTarot->全体扣除'.abs($arr[1]).'金币！'),$uidarr);
					}else{
						$rs=tarotSendMessage(kdjlSafeIconv('utf-8','utf-8','SYSN|alertTarot->全体获得'.$arr[1].'金币！'),$uidarr);
					}
					echo 'money===>';
					break;
				case 'all_giveitems'://全体获得相同道具奖励
					$teamInfo=$team->getTeamInfo();
					$uidarr=array();
					foreach(tarotTeamMembers($teamInfo) as $mem){
						$mem = tarotMemberRow($mem);
						if($mem === false) continue;
						if($mem['state'] == 1){
							$uidarr[] = $mem['uid'];

						}
					}

					echo 'items===>';
					$itemstr = str_replace('all_giveitems:', '', $v);
					$it = getItem($uidarr,$itemstr);
					if($it == 'bag full' || $it == 'give item failed'){
						tarotFail($it == 'bag full' ? '背包空间不足！' : '发放道具失败！');
					}
					if($it == '真遗憾，您没有获得任何道具！'){
						$nit = '真遗憾，您没有获得任何道具！';
					}else $nit = '全体'.$it;
					$rs=tarotSendMessage(kdjlSafeIconv('utf-8','utf-8','SYSN|alertTarot->'.$nit),$uidarr);
					break;
				case 'all_fight'://触发战斗  读取数据
					if(isset($_SESSION['gs']) && ($_SESSION['gs'] == 3 || !empty($_SESSION['gs'])))
					{
						$_SESSION['gs'] = "";
						unset($_SESSION['gs']);
					}
					if(!$team -> setTeamMonsters($arr[1])) tarotFail('保存队伍怪物状态失败！');
					echo 'fight===>';
					$teamInfo=$team->getTeamInfo();
					foreach(tarotTeamMembers($teamInfo) as $mem){
						$mem = tarotMemberRow($mem);
						if($mem === false) continue;
						if($mem['state'] == 1){
							$uidarr[] = $mem['uid'];
						}
					}
					if(!$team->setTeamState(
							array(
								'team_fuben_boss'=>0								)
							)) tarotFail('保存队伍状态失败！');
					$rs=tarotSendMessage(kdjlSafeIconv('utf-8','utf-8','SYSN|alertTarot->触发战斗！！！！！！！'),$uidarr);
					$rs=tarotSendMessage(kdjlSafeIconv('utf-8','utf-8','SYSN|getTeamFightMod'),$uidarr);
					break;
				case 'hit_one'://随机一人被踢出副本

					$teamInfo=$team->getTeamInfo();
					foreach(tarotTeamMembers($teamInfo) as $mem){
						$mem = tarotMemberRow($mem);
						if($mem === false) continue;
						if($mem['state'] == 1){
							$isleader=$team->isTeamLeader($mem['uid'],$teamId);
							if($isleader){//队长不能踢出
								continue;
							}
							$memarr[] = $mem['uid'];
						}
					}
					if(empty($memarr)) break;
					$len = count($memarr) - 1;
					$i = rand(0,$len);

					foreach($uidarr1 as $v){
						if($v == $memarr[$i]){
							continue;
						}
						$nuid[] = $v;
					}

					$hit = $_pm['mysql'] -> getOneRecord('SELECT id,nickname FROM player WHERE id = '.$memarr[$i]);
					$hitName = is_array($hit) ? $hit['nickname'] : $memarr[$i];
					if(count($nuid) > 1 || (count($nuid) == 1 && kdjlIsSoloTeamDungeonMap($teamInMap))){
						$rs=tarotSendMessage(kdjlSafeIconv('utf-8','utf-8','SYSN|alertTarot->您的队友'.$hitName.'被踢出战斗！'),$nuid);
					}else{//只有队长一个人的时候
						$rs=tarotSendMessage(kdjlSafeIconv('utf-8','utf-8','SYSN|outTarot->由于副本人数不足，强制离开副本，挑战失败。'),$nuid);
						if(!$team->setTeamState(array(
							'team_fuben_card_step_num'=>-1,
							'team_fuben_step'=>array(0,0),
							'fubensjoj' => 0
							))) tarotFail('保存队伍状态失败！');
					}
					$rs=tarotSendMessage(kdjlSafeIconv('utf-8','utf-8','SYSN|outTarot->运气太差，遇上恶魔，你将被强制踢出副本，请下次再来吧，挑战副本失败。'),$memarr[$i]);
					echo 'hit_one->'.$hitName.'===>';
					if(!tarotKickTeamMember($team,$teamId,$memarr[$i])) tarotFail('保存队员状态失败！');
					break;
				case 'hit_all': // 全体被踢出副本
					$rs=tarotSendMessage(kdjlSafeIconv('utf-8','utf-8','SYSN|goTarot->运气太差，遇上恶魔，你们将被强制踢出副本，请明日再来吧，挑战副本失败！'),$uidarr1);
					if(!tarotDisbandTeam($team,$teamId)) tarotFail('解散队伍失败！');
					break;
				case 'all_exp_add'://全体获得经验奖励
					$teamInfo=$team->getTeamInfo();
					$t = new task();
					foreach(tarotTeamMembers($teamInfo) as $mem){
						$mem = tarotMemberRow($mem);
						if($mem === false) continue;
						if($mem['state'] == 1){
							if($t->saveExps(intval($arr[1]),0,intval($mem['uid'])) === false){
								tarotFail('发放经验奖励失败！');
							}
							$uidarr[] = $mem['uid'];
						}
					}
					$rs=tarotSendMessage(kdjlSafeIconv('utf-8','utf-8','SYSN|alertTarot->全体获得'.$arr[1].'点经验！'),$uidarr);
					echo 'exp_all===>';
					break;
				case 'all_hp_add'://全体HP增加
					$teamInfo=$team->getTeamInfo();
					foreach(tarotTeamMembers($teamInfo) as $mem){
						$mem = tarotMemberRow($mem);
						if($mem === false) continue;
						if($mem['state'] == 1){
							$mbid = getMbid($mem['uid']);
							$zbarr = getzbAttrib($mbid);
							$hp = getMaxHp($mbid, $mem['uid']);
							if(!is_array($hp)) continue;
							if(!is_array($zbarr) ||empty($zbarr['hp'])){
								$zbarr['hp'] = 0;
							}
							$maxAddHp = intval($zbarr['hp']);
							$missHp = max(0, intval($hp['srchp']) - intval($hp['hp']));
							$missAddHp = max(0, $maxAddHp - intval($hp['addhp']));
							$cchp = $missHp + $missAddHp;
							if(strpos($arr[1],'%') === false) { // not percent
								$heal = intval($arr[1]);
								if($heal < 1) continue;
								if($heal >= $cchp){
									if(!$_pm['mysql'] -> query('UPDATE userbb SET hp = srchp,addhp = '.$zbarr['hp'].' WHERE id = '.$mbid.' AND uid = '.$mem['uid'])) tarotFail('保存宠物血量失败！');
									/*echo 'UPDATE userbb SET hp = srchp,addhp = '.$zbarr['hp'].' WHERE id = '.$mbid;
									echo '<br />'.__line__.'<br />';*/
								}else{
									if($missHp >= $heal){
										if(!$_pm['mysql'] -> query('UPDATE userbb SET hp = hp + '.$heal.' WHERE id = '.$mbid.' AND uid = '.$mem['uid'])) tarotFail('保存宠物血量失败！');
										/*echo 'UPDATE userbb SET hp = srchp,addhp = '.$zbarr['hp'].' WHERE id = '.$mbid;
										echo '<br />'.__line__.'<br />';*/
									}else{
										$a = $heal - $missHp;
										$newAddHp = min($maxAddHp, intval($hp['addhp']) + $a);
										if(!$_pm['mysql'] -> query('UPDATE userbb SET hp = srchp,addhp = '.$newAddHp.' WHERE id = '.$mbid.' AND uid = '.$mem['uid'])) tarotFail('保存宠物血量失败！');
										/*echo 'UPDATE userbb SET hp = srchp,addhp = '.$zbarr['hp'].' WHERE id = '.$mbid;
										echo '<br />'.__line__.'<br />';*/
									}
								}
							}else{
								$num = floatval(str_replace('%','',$arr[1])) * 0.01;
								if($num <= 0) continue;
								$hhp = round($hp['srchp'] * $num) + $hp['hp'];
								$haddhp = round($zbarr['hp'] * $num) + $hp['addhp'];
								if($hhp > $hp['srchp']){
									$hhp = $hp['srchp'];
								}
								if($haddhp > $zbarr['hp']){
									$haddhp = $zbarr['hp'];
								}
								if(!$_pm['mysql'] -> query('UPDATE userbb SET addhp = '.$haddhp.',hp='.$hhp.' WHERE id = '.$mbid.' AND uid = '.$mem['uid'])) tarotFail('保存宠物血量失败！');
							}
							$uidarr[] = $mem['uid'];
						}
					}

					$rs=tarotSendMessage(kdjlSafeIconv('utf-8','utf-8','SYSN|changhp'),$uidarr);
					$rs=tarotSendMessage(kdjlSafeIconv('utf-8','utf-8','SYSN|alertTarot->全体增加'.$arr[1].'点血量！'),$uidarr);
					echo 'hp_all===>';
					break;
				case 'money_add'://单人获得金币奖励
					if(!moneyAdd($uid,$arr[1])){
						tarotFail('保存金币奖励失败！');
					}
					if($arr[1] < 0){
						$ret='扣除金币：'.$arr[1];
					}else{
						$ret='获得金币：'.$arr[1];
					}
					break;
				case 'exp_add'://单人获得经验奖励
					$t = new task();
					if($t->saveExps(intval($arr[1])) === false){
						tarotFail('发放经验奖励失败！');
					}
					$ret='获得经验：'.$arr[1];
					break;
				case 'giveitems'://单人获得道具奖励
					$itemstr = str_replace('giveitems:', '', $v);
					$ret = getItem(array($uid),$itemstr);
					if($ret == 'bag full' || $ret == 'give item failed'){
						tarotFail($ret == 'bag full' ? '背包空间不足！' : '发放道具失败！');
					}
					break;
				case 'hit_me': // 当前玩家被踢出副本
					$isleader=$team->isTeamLeader($uid,$teamId);
					if($isleader){
						if(!tarotDisbandTeam($team,$teamId)) tarotFail('解散队伍失败！');
						$rs=tarotSendMessage(kdjlSafeIconv('utf-8','utf-8','SYSN|goTarot->运气太差，遇上恶魔，你们将被强制踢出副本，请下次再来吧，挑战副本失败！'),$uidarr1);
					}else{
						if(!tarotKickTeamMember($team,$teamId,$uid)) tarotFail('保存队员状态失败！');
						$ret = $nickname.'被踢出战斗！';
						$rs=tarotSendMessage(kdjlSafeIconv('utf-8','utf-8','SYSN|goTarot->运气太差，遇上恶魔，你将被强制踢出副本，请下次再来吧，挑战副本失败。'),$uid);
					}
					$teamInfo=$team->getTeamInfo();
					foreach(tarotTeamMembers($teamInfo) as $mem){
						$mem = tarotMemberRow($mem);
						if($mem === false) continue;
						if($mem['state'] == 1){
							$uidarr[] = $mem['uid'];
						}
					}
					if(count($uidarr) == 1 && !kdjlIsSoloTeamDungeonMap($teamInMap)){
							$rs=tarotSendMessage(kdjlSafeIconv('utf-8','utf-8','SYSN|goTarot->由于副本人数不足，将强制离开副本，挑战失败。'),$uidarr);
						if(!$team->setTeamState(array(
							'team_fuben_card_step_num'=>-1,
							'team_fuben_step'=>array(0,0),
							'fubensjoj' => 0
							))) tarotFail('保存队伍状态失败！');
					}
					break;
				default:
					 echo '牌：'.$newTarotId.'填写有误，'.$newTarotEffect.'数据有误！';
					break;
			}
		}
	}
	if($point < 3){
		$arValue = $_pm['mem']->get('tarot_info1_'.$teamId);
		$ar = kdjlSafeMemValue($arValue, array());
		if(!is_array($ar)) $ar = array();
		$tarotSelfName = htmlspecialchars((string)$nickname, ENT_QUOTES, 'UTF-8');
		if($id <= 5){
			$e = '<span class="text">'.$tarotSelfName.'<br />'.$ret.'</span>';
			$type = 1;
		}
		else{
			$e = '<span class="text2">'.$tarotSelfName.'<br />'.$ret.'</span>';
			$type = 2;
		}
		echo $e;
		$ar[]=array('type' => $type,'msg' => $e,'uid' => $uid);
		if(!tarotStoreCardCache('tarot_info1_'.$teamId, $ar)) tarotFail('保存塔罗牌状态失败！');
	}else{
		//第三关要把所翻的牌存起来
		$arValue = $_pm['mem']->get('tarot_info_'.$teamId);
		$ar = kdjlSafeMemValue($arValue, array());
		if(!is_array($ar)) $ar = array();
		$tarotImg = tarotGateImage(isset($newTarot['img']) ? $newTarot['img'] : '');
		$ar[]=array('id' => $id,'img' => $tarotImg);
		if(!tarotStoreCardCache('tarot_info_'.$teamId, $ar)) tarotFail('保存塔罗牌状态失败！');
		$rs=tarotSendMessage(kdjlSafeIconv('utf-8','utf-8','SYSN|tarot->'.$id.'->'.$tarotImg),$uidarr1);
		//echo $rs.'aaa';
		//echo '['.__LINE__."]<br>";
		echo $tarotImg;
	}
}
$tarotHadTransaction = $tarotInTransaction;
if(!tarotCommitTransaction()){
	realseLock();
	die('保存塔罗牌奖励失败！');
}
if($tarotHadTransaction) tarotInvalidateMemberCaches($uidarr1);
tarotFlushMessages();
foreach($tarotAnnouncements as $announcement)
{
	$s->sendMsg('an|'.$announcement,'__ALL__');
}
realseLock();
function getMbid($uid){//得到主宠id
	global $_pm;
	$arr = $_pm['mysql'] -> getOneRecord('SELECT mbid FROM player WHERE id = '.$uid);
	return is_array($arr) ? intval($arr['mbid']) : 0;
}

function getMaxHp($id, $ownerUid=0){//得到剩余hp
	global $_pm;
	$id = intval($id);
	$ownerUid = intval($ownerUid);
	if($id < 1) return false;
	$sql = 'SELECT srchp,hp,addhp,(hp+addhp) as totalhp FROM userbb WHERE id = '.$id;
	if($ownerUid > 0) $sql .= ' AND uid = '.$ownerUid;
	$arr = $_pm['mysql'] -> getOneRecord($sql);
	return is_array($arr) ? $arr : false;
}

function moneyAdd($uid,$num){
	global $_pm;
	$uid = intval($uid);
	$num = intval($num);
	if($uid < 1 || $num === 0) return false;
	if($num < 0) return $_pm['mysql'] -> query('UPDATE player SET money = GREATEST(0,COALESCE(money,0) +'.$num.') WHERE id = '.$uid);
	return $_pm['mysql'] -> query('UPDATE player SET money = LEAST(COALESCE(money,0) +'.$num.',1000000000) WHERE id = '.$uid);
}

function getItem($uidarr,$str){
	global $_pm,$point,$tarotAnnouncements;
	//echo $str;
	$flag = 0;
	$retstr = '';
	$propslist = explode(',', $str);
	if (is_array($propslist)){
		$task = new task();
		foreach ($propslist as $k => $v){
			$inarr = explode(':', $v);
			if(is_array($inarr) && count($inarr) >= 3){
				$pid = intval($inarr[0]);
				$sums = intval($inarr[1]);
				$chance = intval($inarr[2]);
				if($pid < 1 || $sums < 1 || $chance < 1) continue;
				if (rand(1, $chance) == 1){	//  rand hits
					$prs = $_pm['mysql']->getOneRecord("SELECT name FROM props WHERE id={$pid}");
					$propsName = is_array($prs) ? htmlspecialchars(strval($prs['name']), ENT_QUOTES, 'UTF-8') : $pid;
					if($uidarr == 0){
						$flag = 1;
						if(empty($retstr)){
							$retstr = '获得 '.$propsName.'&nbsp;'.$sums.' 个';
						}else{
							$retstr .= ','.$propsName.'&nbsp;'.$sums.' 个';
						}
					}else if(is_array($uidarr) && !empty($uidarr)){
						foreach($uidarr as $v){
							$giveResult = $task->saveGetPropsMore($pid,$sums,0,$v);
							if($giveResult !== true){
								return $giveResult === '200' ? 'bag full' : 'give item failed';
							}
							$flag = 1;
							if(empty($retstr)){
								$retstr = '获得 '.$propsName.'&nbsp;'.$sums.' 个';
							}else{
								$retstr .= ','.$propsName.'&nbsp;'.$sums.' 个';
							}
							if(isset($inarr[3]) && $inarr[3] == '2'){ // 发布公告
								$p = $_pm['mysql'] -> getOneRecord('SELECT nickname FROM player WHERE id = '.$v);
								$nickname = is_array($p) ? htmlspecialchars(strval($p['nickname']), ENT_QUOTES, 'UTF-8') : $v;
								$tarotAnnouncements[]='恭喜玩家 '.$nickname.'获得遗忘宫殿第'.$point.'关的奖励：'.$propsName.'&nbsp;'.$sums.' 个';
							}
						}
					}
				}
			}
		}
		if($flag == 0 || $retstr == ''){
			return '真遗憾，您没有获得任何道具！';
		}
		return $retstr;
	}
}

function showt($str){
	$effect = explode('|',$str);
	$ret = '塔罗牌效果配置错误！';
	foreach($effect as $v){
		$arr = explode(':',$v);
		if(!isset($arr[0]) || $arr[0] === '') continue;
		if(!isset($arr[1])) $arr[1] = 0;
		switch ($arr[0]){
			case 'money_add'://单人获得金币奖励
				if($arr[1] < 0){
				$ret='扣除金币：'.$arr[1];
				}else{
				$ret='获得金币：'.$arr[1];
				}
				break;
			case 'exp_add'://单人获得经验奖励
				$ret='获得经验：'.$arr[1];
				break;
			case 'giveitems'://单人获得道具奖励
				$itemstr = str_replace('giveitems:', '', $v);
				$ret = getItem(0,$itemstr);
				break;
			case 'hit_me':
				$ret = '随机一人踢出副本！';
				break;
			default:
				$ret = '塔罗牌效果配置错误！';
				break;
		}
	}
	return $ret;
}
$_pm['mem']->memClose();
?>
