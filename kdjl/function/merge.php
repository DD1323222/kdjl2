<?php
require_once('../config/config.game.php');
secStart($_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
$type = (isset($_REQUEST['type']) && !is_array($_REQUEST['type'])) ? intval($_REQUEST['type']) : 0;
$mergeid = (isset($_REQUEST['mergeid']) && !is_array($_REQUEST['mergeid'])) ? intval($_REQUEST['mergeid']) : 0;
$mergeTransactionActive = false;
$mergeLockedItemId = 0;
$mergeTouchedUserIds = array();
if($uid < 1) die('非法操作！');

function mergeShutdown()
{
	global $_pm, $mergeTransactionActive, $mergeLockedItemId;
	$error = error_get_last();
	if(!is_array($error) || !in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) return;
	if($mergeTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$mergeTransactionActive = false;
	}
	if($mergeLockedItemId > 0)
	{
		unLockItem($mergeLockedItemId);
		$mergeLockedItemId = 0;
	}
}
register_shutdown_function('mergeShutdown');

function mergeBegin($userIds)
{
	global $_pm, $mergeTransactionActive, $mergeTouchedUserIds;
	$ids = array();
	foreach($userIds as $userId)
	{
		$userId = intval($userId);
		if($userId > 0) $ids[$userId] = $userId;
	}
	if(empty($ids)) return false;
	sort($ids, SORT_NUMERIC);
	$values = array();
	foreach($ids as $userId) $values[] = '('.$userId.',0)';
	if(!$_pm['mysql']->query('INSERT IGNORE INTO `lock` (uid,lockvalue) VALUES '.implode(',', $values))) return false;
	if(!$_pm['mysql']->query('START TRANSACTION')) return false;
	$mergeTransactionActive = true;
	$rows = $_pm['mysql']->getRecords('SELECT uid FROM `lock` WHERE uid IN ('.implode(',', $ids).') ORDER BY uid FOR UPDATE');
	if(!is_array($rows) || count($rows) != count($ids))
	{
		$_pm['mysql']->query('ROLLBACK');
		$mergeTransactionActive = false;
		return false;
	}
	$mergeTouchedUserIds = $ids;
	return true;
}

function mergeCommit($errorCode)
{
	global $_pm, $mergeTransactionActive, $mergeTouchedUserIds;
	if(!$_pm['mysql']->query('COMMIT')) mergeRollback($errorCode);
	$mergeTransactionActive = false;
	foreach($mergeTouchedUserIds as $userId)
	{
		$userId = intval($userId);
		if($userId < 1) continue;
		$_pm['mem']->del($userId);
		$_pm['mem']->del($userId.'bag');
	}
	$mergeTouchedUserIds = array();
}

function mergeNotify($targetUid, $content)
{
	global $_pm;
	$targetUid = intval($targetUid);
	if($targetUid < 1) return false;
	return $_pm['mysql']->query(
		'INSERT INTO information(uid,times,content) VALUES('.$targetUid.','.
		$_pm['mysql']->quote(date('Y-m-d H:i:s')).','.$_pm['mysql']->quote($content).')'
	);
}

function mergeRowsByUid($rows)
{
	$result = array();
	if(!is_array($rows)) return $result;
	foreach($rows as $row)
	{
		if(is_array($row) && isset($row['uid'])) $result[intval($row['uid'])] = $row;
	}
	return $result;
}

function mergeRollback($code)
{
	global $_pm, $mergeTransactionActive, $mergeTouchedUserIds;
	if($mergeTransactionActive) $_pm['mysql']->query('ROLLBACK');
	$mergeTransactionActive = false;
	$mergeTouchedUserIds = array();
	die($code);
}

function mergeRollbackItem($bid, $code)
{
	global $_pm, $mergeTransactionActive, $mergeTouchedUserIds, $mergeLockedItemId;
	if($mergeTransactionActive) $_pm['mysql']->query('ROLLBACK');
	$mergeTransactionActive = false;
	$mergeTouchedUserIds = array();
	if($mergeLockedItemId > 0)
	{
		unLockItem($mergeLockedItemId);
		$mergeLockedItemId = 0;
	}
	die($code);
}

function mergeParseSend($send)
{
	$parts = explode(',', strval($send));
	if(count($parts) < 2) return false;
	$n = intval($parts[0]);
	$pid = intval($parts[1]);
	if($n <= 0 || $pid <= 0) return false;
	return array('pid' => $pid, 'n' => $n);
}

function mergeGiveSendItem($uid, $pid, $n)
{
	global $_pm;
	$uid = intval($uid);
	$pid = intval($pid);
	$n = intval($n);
	if($uid <= 0 || $pid <= 0 || $n <= 0) return false;
	$mempropsid = kdjlSafeMemValue($_pm['mem']->get('db_propsid'), array());
	if(!is_array($mempropsid) || !isset($mempropsid[$pid]) || !is_array($mempropsid[$pid])) return false;
	$wp = $mempropsid[$pid];
	$task = new task();
	return $task->saveGetPropsMore($pid, $n, 0, $uid, $wp) === true;
}

if($type==1){ //离婚请求
	$preview = $_pm['mysql']->getOneRecord("SELECT merge FROM player_ext WHERE uid={$uid}");
	$partnerUid = is_array($preview) ? intval($preview['merge']) : 0;
	if($partnerUid < 1 || !mergeBegin(array($uid, $partnerUid))) die('11');
	$rows = $_pm['mysql']->getRecords(
		"SELECT uid,merge,request,sj FROM player_ext WHERE uid IN ({$uid},{$partnerUid}) ORDER BY uid FOR UPDATE"
	);
	$marriageRows = mergeRowsByUid($rows);
	if(!isset($marriageRows[$uid], $marriageRows[$partnerUid]) ||
		intval($marriageRows[$uid]['merge']) != $partnerUid ||
		intval($marriageRows[$partnerUid]['merge']) != $uid) mergeRollback('11');
	if(intval($marriageRows[$partnerUid]['request']) == 1) mergeRollback('14');
	if(intval($marriageRows[$uid]['request']) == 1) mergeRollback('3');
	$nomergetime = time();
	$sql = "UPDATE player_ext SET sj=sj-2000,request=1,nomergetime={$nomergetime} " .
		"WHERE uid={$uid} AND merge={$partnerUid} AND sj>=2000 AND request=0";
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) mergeRollback('2');
	mergeCommit('2');

	$userRow = $_pm['mysql']->getOneRecord("SELECT nickname FROM player WHERE id={$uid}");
	$nickname = is_array($userRow) && isset($userRow['nickname']) ? $userRow['nickname'] : '';
	mergeNotify($partnerUid, '玩家【'.$nickname.'】向你提出离婚!');
	require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
	$s = new socketmsg();
	$s->sendMsg(kdjlSafeIconv('gbk','utf-8','SYSN|information-->'), array($partnerUid));
	die('1');
}elseif($type==2){ //取消离婚
	$preview = $_pm['mysql']->getOneRecord("SELECT merge FROM player_ext WHERE uid={$uid}");
	if(!is_array($preview)) die('5');
	$partnerUid = intval($preview['merge']);
	$lockUsers = $partnerUid > 0 ? array($uid, $partnerUid) : array($uid);
	if(!mergeBegin($lockUsers)) die('5');
	$rows = $_pm['mysql']->getRecords(
		"SELECT uid,merge,request FROM player_ext WHERE uid IN (".implode(',', $lockUsers).") ORDER BY uid FOR UPDATE"
	);
	$marriageRows = mergeRowsByUid($rows);
	if(!isset($marriageRows[$uid]) || intval($marriageRows[$uid]['request']) != 1) mergeRollback('5');
	if($partnerUid > 0 && (!isset($marriageRows[$partnerUid]) ||
		intval($marriageRows[$uid]['merge']) != $partnerUid ||
		intval($marriageRows[$partnerUid]['merge']) != $uid)) mergeRollback('15');
	$sql = "UPDATE player_ext SET sj=COALESCE(sj,0)+2000,request=0 WHERE uid={$uid} AND request=1";
	if($partnerUid > 0) $sql .= " AND merge={$partnerUid}";
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) mergeRollback('5');
	mergeCommit('5');

	if($partnerUid > 0)
	{
		$userRow = $_pm['mysql']->getOneRecord("SELECT nickname FROM player WHERE id={$uid}");
		$nickname = is_array($userRow) && isset($userRow['nickname']) ? $userRow['nickname'] : '';
		mergeNotify($partnerUid, '玩家【'.$nickname.'】取消了向你提出离婚请求！');
		require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
		$s = new socketmsg();
		$s->sendMsg(kdjlSafeIconv('gbk','utf-8','SYSN|information-->'), array($partnerUid));
	}
	die('4');
}elseif($type==3){ //接受婚姻
	$user		= $_pm['user']->getUserById($uid);
	if(!is_array($user)) die('1');
	if($mergeid < 1 || $mergeid == $uid){
		die('1');
	}
	$user2 = $_pm['user']->getUserById($mergeid);
	if(!is_array($user2)) die('6');
	if(!mergeBegin(array($uid, $mergeid))) die('6');
	$sql = "select request,request_merge,merge from player_ext WHERE uid = {$uid} FOR UPDATE";
	$arrmerge=$_pm['mysql']->getOneRecord($sql);
	if(is_array($arrmerge)){
		if($arrmerge['request']==1){
				mergeRollback('2');//还没有正式离婚，正等待对方同意离婚
			}
		if($arrmerge['request']==2){
				mergeRollback('4');//你向其他的玩家发送了结婚请求，必须取消才可接受
			}
		if($arrmerge['merge']>0){
			mergeRollback('3');//结婚
		}
		if($arrmerge['request_merge']>0){
			mergeRollback('4');//你向其他的玩家发送了结婚请求，必须取消才可接受
		}

	}
		$sql = "select send from player_ext WHERE uid = {$mergeid} and request_merge={$uid} and merge=0 and request=0 FOR UPDATE";
		$send=$_pm['mysql']->getOneRecord($sql);
		if(is_array($send)){
			$send1=explode(',',isset($send['send']) ? $send['send'] : '');
			if(count($send1) < 2) mergeRollback('1');
			$bid=intval($send1[1]);
			$n=intval($send1[0]);
			if($bid <= 0 || $n <= 0) mergeRollback('1');
			//echo "bid:".$bid."|n:".$n;
			$err = 0;
			$mempropsid = kdjlSafeMemValue($_pm['mem']->get('db_propsid'), array());
			if(!is_array($mempropsid)) $mempropsid = array();
			if(!isset($mempropsid[$bid])) mergeRollback('1');
			$wp1 = $mempropsid[$bid];
			$bid=intval($wp1['endtime']);
			if($bid <= 0) mergeRollback('1');
			//echo "|bid2:".$bid;

			//die('sss');
			if(!isset($mempropsid[$bid])) mergeRollback('1');
			$wp=$mempropsid[$bid];
				//var_dump($wp1);
			//var_dump($wp);
			//die('ss');
				$task = new task();
				$giveResult = $task->saveGetPropsMore($bid, $n, 0, $user['id'], $wp);
				if($giveResult !== true) mergeRollback('1');
		}else{
			mergeRollback("6");
		}
	if(!is_array($arrmerge)){
		$stateOk = $_pm['mysql']->query("insert into player_ext(uid,request_merge,merge,request,send) values({$uid},0,{$mergeid},0,'0')");
	}else{
		$sql = "UPDATE player_ext SET request=0,merge={$mergeid},request_merge=0,send='0' WHERE uid = {$uid} and request=0 and merge=0 and request_merge=0";
		$stateOk = $_pm['mysql']->query($sql);
		}
		if(!$stateOk || mysql_affected_rows($_pm['mysql']->getConn()) != 1) mergeRollback('6');
		if(!$_pm['mysql']->query("UPDATE player_ext SET request=0,merge={$uid},request_merge=0,send='0' WHERE uid = {$mergeid} and request_merge={$uid} and request=0 and merge=0") ||
			mysql_affected_rows($_pm['mysql']->getConn()) != 1) mergeRollback('6');
		mergeCommit('6');

		//
		//公告我接受了某玩家的婚姻
			/*$user2		= $_pm['user']->getUserById($mergeid);
			$msg_key = 'chatMsgList';
			$nowMsgList = kdjlSafeMemValue($_pm['mem']->get($msg_key), '');
			if(!is_string($nowMsgList)) $nowMsgList = '';
			$arr = explode('linend', $nowMsgList);
			if( count($arr)>20 ) // cear old
			{
				$arrt = array_shift($arr);
			}
			$newstr = '<font color=red>【系统公告】恭喜玩家  '.$user['nickname'].'  与玩家  '.$user2['nickname'].'  结成夫妻!</font>';
			foreach($arr as $k=>$v)
			{
				$retstr .= $v.'linend';
			}
			$retstr = $retstr.$newstr;
			$_pm['mem']->set( array('k'=>$msg_key, 'v'=>$retstr) );*/




		mergeNotify($mergeid, '玩家【'.$user['nickname'].'】接收了你对他提出的婚姻请求');
		require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
		$s=new socketmsg();
		$rs=$s->sendMsg(kdjlSafeIconv('gbk','utf-8','SYSN|information-->'),array($mergeid));
		$str = '恭喜【'.$user['nickname'].'】和【'.$user2['nickname'].'】结为夫妻！';
		$rs=$s->sendMsg(kdjlSafeIconv('gbk','utf-8','an|'.$str));
		die("5");//chenggong
}elseif($type==4){ //赠送
	$err = 0;
	$user		= $_pm['user']->getUserById($uid);
	if(!is_array($user)) die('1');
	del_bag_expire();
	$bags		= $_pm['user']->getUserBagById($uid);
	if(!is_array($bags)) $bags = array();
	$bid = (isset($_REQUEST['pid']) && !is_array($_REQUEST['pid'])) ? intval($_REQUEST['pid']) : 0; // table: userbag -> id
	$n	 = (isset($_REQUEST['n']) && !is_array($_REQUEST['n'])) ? intval($_REQUEST['n']) : 0;
	if($n>10){
		die('110');
	}
	$arr=$_pm['mysql']->getOneRecord("select * from player_ext WHERE uid = {$uid}");
	if($uid==$mergeid){
		die('17');
	}
	if(is_array($arr) && $arr['merge']>0){
		die('4');//未离婚
	}
	if(is_array($arr) && $arr['request_merge']>0){
		die('5');//向别人已经发出一个求婚信息
	}
	if($mergeid==0 || empty($mergeid)){
		die('6');
	}
	$targetUser = $_pm['user']->getUserById($mergeid, 'id');
	if(!is_array($targetUser)){
		die('6');
	}
	$arr1=$_pm['mysql']->getOneRecord("select request_merge from player_ext WHERE uid = {$mergeid}");
	if(is_array($arr1) && $arr1['request_merge']==$uid){
		die('18');//向别人已经发出一个求婚信息
	}
	if($n <= 0)
	{
		unLockItem($bid);
		die('2');
	}

	if ($_pm['user']->check(array('int' => array($bid, $n))) === FALSE) {
		unLockItem($bid);
		die('2');
	}
	if(lockItem($bid) === false)
	{
		die('已经处理过该请求！');
	}
	$mergeLockedItemId = $bid;

	$wp = false;
	foreach ($bags as $k => $v)
	{
		if ($v['uid'] == $_SESSION['id'] && $v['id'] == $bid)
		{
			$wp = $v;
			break;
		}
	}
	if (!is_array($wp))
	{
		unLockItem($bid);
		die('3');
	}
	else if(!empty($wp['zbing']))
	{
		unLockItem($bid);
		die("10");//装备在身上的不能赠送。
	}
	else if(isset($wp['cantrade']) && intval($wp['cantrade']) == 3)
	{
		unLockItem($bid);
		die("10");
	}
	else
	{
		if ($wp['vary'] == 2 && $n != 1) {
			unLockItem($bid);
			die('10');
		}
		if ($n > $wp['sums']) {
			unLockItem($bid);
			die('10');
		}
		if(!mergeBegin(array($uid, $mergeid)))
		{
			unLockItem($bid);
			$mergeLockedItemId = 0;
			die('10');
		}
		$arr = $_pm['mysql']->getOneRecord(
			"SELECT merge,request_merge FROM player_ext WHERE uid={$uid} FOR UPDATE"
		);
		$targetExt = $_pm['mysql']->getOneRecord(
			"SELECT merge,request_merge FROM player_ext WHERE uid={$mergeid} FOR UPDATE"
		);
		if(is_array($arr) && intval($arr['merge']) > 0) mergeRollbackItem($bid, '4');
		if(is_array($arr) && intval($arr['request_merge']) > 0) mergeRollbackItem($bid, '5');
		if(is_array($targetExt) && intval($targetExt['merge']) > 0) mergeRollbackItem($bid, '6');
		if(is_array($targetExt) && intval($targetExt['request_merge']) == $uid) mergeRollbackItem($bid, '18');
		if ($wp['vary'] == 2)	//	Can't repeat!
		{
			if(!$_pm['mysql']->query("DELETE FROM userbag
						 WHERE uid={$_SESSION['id']} and id={$bid} and zbing=0 and (cantrade IS NULL OR cantrade<>3)
					  ") || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
			{
				mergeRollbackItem($bid, '10');
			}
		}
		else
		{
			$itemSent = $_pm['mysql']->query("UPDATE userbag
						   SET sums=sums-{$n}
						 WHERE uid={$_SESSION['id']} and id={$bid} and sums>={$n} and zbing=0 and (cantrade IS NULL OR cantrade<>3)
					  ");
			if(!$itemSent || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
			{
				mergeRollbackItem($bid, '10');
			}
			if(!$_pm['mysql']->query("DELETE FROM userbag WHERE uid={$_SESSION['id']} and id={$bid} and sums<=0 and bsum<=0 and psum<=0 and pyb=0 and zbing=0 and (cantrade IS NULL OR cantrade<>3)"))
			{
				mergeRollbackItem($bid, '10');
			}
		}

		$send=$n.','.$wp['pid'];
		if(is_array($arr)){
			$sql = "UPDATE player_ext SET request=0,merge=0,request_merge={$mergeid},send='{$send}' WHERE uid = {$uid} and merge=0 and request_merge=0";
			$requestOk = $_pm['mysql']->query($sql);
		}else{
			$requestOk = $_pm['mysql']->query("insert into player_ext(uid,request_merge,merge,request,send) values({$uid},{$mergeid},0,0,'{$send}')");
		}
		if(!$requestOk || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
		{
			mergeRollbackItem($bid, '10');
		}
		mergeCommit('10');
		unLockItem($bid);
		$mergeLockedItemId = 0;
	}




	//
	//公告我向某玩家求婚
	mergeNotify($mergeid, '玩家【'.$user['nickname'].'】向你求婚！');
	require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
	$s=new socketmsg();
	$rs=$s->sendMsg(kdjlSafeIconv('gbk','utf-8','SYSN|information-->'),array($mergeid));
	echo $err;
	if($mergeLockedItemId > 0)
	{
		unLockItem($mergeLockedItemId);
		$mergeLockedItemId = 0;
	}
}elseif($type==5){//同意离婚请求
	if($mergeid < 1 || $mergeid == $uid || !mergeBegin(array($uid, $mergeid))) die('2');
	$rows = $_pm['mysql']->getRecords(
		"SELECT uid,merge,request FROM player_ext WHERE uid IN ({$uid},{$mergeid}) ORDER BY uid FOR UPDATE"
	);
	$marriageRows = mergeRowsByUid($rows);
	if(!isset($marriageRows[$uid], $marriageRows[$mergeid]) ||
		intval($marriageRows[$uid]['merge']) != $mergeid ||
		intval($marriageRows[$mergeid]['merge']) != $uid ||
		intval($marriageRows[$mergeid]['request']) != 1) mergeRollback('2');
	$sql = "UPDATE player_ext SET request=0,merge=0,request_merge=0,send='0' WHERE " .
		"(uid={$uid} AND merge={$mergeid}) OR (uid={$mergeid} AND merge={$uid} AND request=1)";
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 2) mergeRollback('2');
	mergeCommit('2');

	$userRow = $_pm['mysql']->getOneRecord("SELECT nickname FROM player WHERE id={$uid}");
	$nickname = is_array($userRow) && isset($userRow['nickname']) ? $userRow['nickname'] : '';
	mergeNotify($mergeid, '玩家【'.$nickname.'】同意了你提出的离婚请求！');
	require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
	$s = new socketmsg();
	$s->sendMsg(kdjlSafeIconv('gbk','utf-8','SYSN|information-->'), array($mergeid));
	die('1');

}elseif($type==6){ //取消提出的婚姻
	$user		= $_pm['user']->getUserById($_SESSION['id']);
	if(!is_array($user)) die('6');
	if(!mergeBegin(array($uid))) die('6');
	$sql = "select send from player_ext WHERE uid = {$uid} and request_merge>0 FOR UPDATE";
	$send=$_pm['mysql']->getOneRecord($sql);
	if(!is_array($send)) mergeRollback("6");
	$sendItem = mergeParseSend($send['send']);
	if(!is_array($sendItem)) mergeRollback("6");
	if(!mergeGiveSendItem($uid, $sendItem['pid'], $sendItem['n'])) mergeRollback("6");
	$sql = "UPDATE player_ext SET request=0,merge=0,request_merge=0,send='0' WHERE uid = {$uid} and request_merge>0";
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) mergeRollback("6");
	mergeCommit('6');
	//
	//公告我取消了对某玩家的求婚
	//
	die("1");//chenggong
}elseif($type==7){//拒绝对方的婚姻请求
	if($mergeid < 1 || $mergeid == $uid || !mergeBegin(array($uid, $mergeid))) die('2');
	$arr = $_pm['mysql']->getOneRecord(
		"SELECT request_merge FROM player_ext WHERE uid={$mergeid} AND request=0 FOR UPDATE"
	);
	if(!is_array($arr) || intval($arr['request_merge']) != $uid) mergeRollback('2');
	$sql = "UPDATE player_ext SET request=2 WHERE uid={$mergeid} AND request=0 AND request_merge={$uid}";
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) mergeRollback('2');
	mergeCommit('2');
	$userRow = $_pm['mysql']->getOneRecord("SELECT nickname FROM player WHERE id={$uid}");
	$nickname = is_array($userRow) && isset($userRow['nickname']) ? $userRow['nickname'] : '';
	mergeNotify($mergeid, '玩家【'.$nickname.'】拒绝了你的求婚！');
	require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
	$s = new socketmsg();
	$s->sendMsg(kdjlSafeIconv('gbk','utf-8','SYSN|information-->'), array($mergeid));
	die('1');
}elseif($type==8){//被玩家拒绝，响应这个拒绝，取回物品，取消婚姻请求
	$user		= $_pm['user']->getUserById($_SESSION['id']);
	if(!is_array($user)) die('2');
	if(!mergeBegin(array($uid))) die('2');
	$sql="select send from player_ext where uid ={$uid} and request=2 FOR UPDATE";
	$send=$_pm['mysql']->getOneRecord($sql);
	if(!is_array($send)) mergeRollback('2');//对方已经取消了对你的拒绝
	$sendItem = mergeParseSend($send['send']);
	if(!is_array($sendItem)) mergeRollback('2');
	if(!mergeGiveSendItem($uid, $sendItem['pid'], $sendItem['n'])) mergeRollback('2');
	$sql = "UPDATE player_ext SET request=0,merge=0,request_merge=0,send='0' WHERE uid = {$uid} and request=2";
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) mergeRollback('2');
	mergeCommit('2');
	//
	//公告我取消了对玩家的婚姻请求
	//
	die('1');
}elseif($type==9){
	if($mergeid < 1 || $mergeid == $uid || !mergeBegin(array($uid, $mergeid))) die('2');
	$arr = $_pm['mysql']->getOneRecord(
		"SELECT request_merge FROM player_ext WHERE uid={$mergeid} AND request=2 FOR UPDATE"
	);
	if(!is_array($arr) || intval($arr['request_merge']) != $uid) mergeRollback('2');
	$sql = "UPDATE player_ext SET request=0 WHERE uid={$mergeid} AND request=2 AND request_merge={$uid}";
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) mergeRollback('2');
	mergeCommit('2');
	die('1');
}elseif($type==10){
	$sql = "select merge,request from player_ext WHERE uid = {$uid}";
	$mer=$_pm['mysql']->getOneRecord($sql);
	if(!is_array($mer) || intval($mer['merge']) < 1) die('3');//对方已与你无婚姻关系
	$partnerUid = intval($mer['merge']);
	if(!mergeBegin(array($uid, $partnerUid))) die('2');
	$firstUid = min($uid, $partnerUid);
	$secondUid = max($uid, $partnerUid);
	$rows = $_pm['mysql']->getRecords(
		"SELECT uid,merge,request,sj FROM player_ext WHERE uid IN ({$firstUid},{$secondUid}) ORDER BY uid FOR UPDATE"
	);
	if(!is_array($rows) || count($rows) != 2) mergeRollback('3');
	$marriageRows = mergeRowsByUid($rows);
	if(!isset($marriageRows[$uid], $marriageRows[$partnerUid])) mergeRollback('3');
	$mine = $marriageRows[$uid];
	$partner = $marriageRows[$partnerUid];
	if(intval($mine['merge']) != $partnerUid || intval($partner['merge']) != $uid) mergeRollback('3');
	if(intval($mine['request']) == 1) mergeRollback('4');
	if(intval($partner['request']) == 1) mergeRollback('14');

	$sql = "UPDATE player_ext SET sj=sj-5000,request=0,merge=0,request_merge=0,send='0' ".
		"WHERE uid={$uid} AND merge={$partnerUid} AND request=0 AND sj>=5000";
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) mergeRollback('2');
	$sql = "UPDATE player_ext SET request=0,merge=0,request_merge=0,send='0' ".
		"WHERE uid={$partnerUid} AND merge={$uid} AND request=0";
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) mergeRollback('2');
	mergeCommit('2');

	$user = $_pm['user']->getUserById($uid);
	$nickname = is_array($user) && isset($user['nickname']) ? $user['nickname'] : '';
	mergeNotify($partnerUid, '玩家【'.$nickname.'】强制与你离婚！');
	require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
	$s=new socketmsg();
	$s->sendMsg(kdjlSafeIconv('gbk','utf-8','SYSN|information-->'),array($partnerUid));
	die('1');//离婚成功
}elseif($type==11){//玩家拒绝对方提出的离婚请求
	$sql = "select merge,request from player_ext WHERE uid = {$uid}";
	$mer=$_pm['mysql']->getOneRecord($sql);
	if(!is_array($mer) || intval($mer['merge']) < 1) die('3');//对方已与你无婚姻关系
	$partnerUid = intval($mer['merge']);
	if(!mergeBegin(array($uid, $partnerUid))) die('4');
	$firstUid = min($uid, $partnerUid);
	$secondUid = max($uid, $partnerUid);
	$rows = $_pm['mysql']->getRecords(
		"SELECT uid,merge,request FROM player_ext WHERE uid IN ({$firstUid},{$secondUid}) ORDER BY uid FOR UPDATE"
	);
	if(!is_array($rows) || count($rows) != 2) mergeRollback('3');
	$marriageRows = mergeRowsByUid($rows);
	if(!isset($marriageRows[$uid], $marriageRows[$partnerUid])) mergeRollback('3');
	if(intval($marriageRows[$uid]['merge']) != $partnerUid ||
		intval($marriageRows[$partnerUid]['merge']) != $uid) mergeRollback('3');
	if(intval($marriageRows[$partnerUid]['request']) != 1) mergeRollback('2');

	$sql = "UPDATE player_ext SET sj=COALESCE(sj,0)+2000,request=0 ".
		"WHERE uid={$partnerUid} AND merge={$uid} AND request=1";
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) mergeRollback('4');
	mergeCommit('4');

	$user = $_pm['user']->getUserById($uid);
	$nickname = is_array($user) && isset($user['nickname']) ? $user['nickname'] : '';
	mergeNotify($partnerUid, '玩家【'.$nickname.'】拒绝你的离婚请求，系统已退回2000水晶，婚姻恢复正常。');
	die('1');//你拒绝了对方的离婚请求
}

$_pm['mem']->memClose();



?>
