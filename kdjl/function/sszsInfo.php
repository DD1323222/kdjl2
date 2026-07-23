<?php
header('Content-Type:text/html;charset=utf-8');
require_once('../config/config.game.php');
secStart($_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
$id = (isset($_GET['id']) && !is_array($_GET['id'])) ? intval($_GET['id']) : 0;
$op = (isset($_GET['op']) && !is_array($_GET['op'])) ? $_GET['op'] : '';
$res = '';
if($uid < 1) die('非法操作！');
if(!in_array($op, array('img','str','zs'), true)) die('非法操作！');

require_once('../sec/dblock_fun.php');
$sszsTransactionActive = false;
$sszsLockHeld = false;
$sszsPendingLogId = 0;

function sszsShutdown()
{
	global $_pm, $sszsTransactionActive, $sszsLockHeld, $sszsPendingLogId;
	$error = error_get_last();
	if(!is_array($error) || !in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true))
	{
		return;
	}
	if($sszsTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$sszsTransactionActive = false;
	}
	if($sszsPendingLogId > 0)
	{
		$_pm['mysql']->query('DELETE FROM gamelog WHERE id='.intval($sszsPendingLogId));
		$sszsPendingLogId = 0;
	}
	if($sszsLockHeld && function_exists('realseLock'))
	{
		realseLock();
		$sszsLockHeld = false;
	}
}
register_shutdown_function('sszsShutdown');

$a = getLock($uid);
if(!is_array($a)){
	realseLock();
	die('服务器繁忙，请稍候再试！');
}
$sszsLockHeld = true;
function sszsFail($msg)
{
	global $_pm, $sszsTransactionActive, $sszsLockHeld, $sszsPendingLogId;
	if($sszsTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$sszsTransactionActive = false;
	}
	if($sszsPendingLogId > 0)
	{
		$_pm['mysql']->query('DELETE FROM gamelog WHERE id='.intval($sszsPendingLogId));
		$sszsPendingLogId = 0;
	}
	if($sszsLockHeld)
	{
		realseLock();
		$sszsLockHeld = false;
	}
	die($msg);
}
function sszsHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function sszsJsSingle($value)
{
	$value = str_replace("\\", "\\\\", (string)$value);
	$value = str_replace("'", "\\'", $value);
	$value = str_replace(array("\r", "\n"), array("\\r", "\\n"), $value);
	return $value;
}
function sszsImage($value)
{
	$value = basename((string)$value);
	return preg_match('/^[A-Za-z0-9_.-]+$/', $value) ? $value : '';
}
function sszsBonusType($prop)
{
	if(!is_array($prop) || !isset($prop['effect'])) return false;
	$effect = $prop['effect'];
	if(strpos($effect, 'sszs:') === 0) return 'success';
	if(strpos($effect, 'cszsczlbh:') === 0) return 'growth';
	$arr = explode(':', $effect);
	if(count($arr) >= 2 && in_array($arr[0], array('addac','addmc','addhit','addmiss','addspeed','addhp','addmp')) && is_numeric($arr[1]))
	{
		return 'attr';
	}
	return false;
}

function sszsParseRequiredProps($value)
{
	$result = array();
	foreach(explode(',', strval($value)) as $entry)
	{
		$parts = explode('|', trim($entry));
		if(count($parts) < 2 || intval($parts[0]) < 1 || intval($parts[1]) < 1) return false;
		$pid = intval($parts[0]);
		if(!isset($result[$pid])) $result[$pid] = 0;
		$result[$pid] += intval($parts[1]);
	}
	return $result;
}

function sszsHasRequiredProps($uid, $requiredProps)
{
	global $_pm;
	foreach($requiredProps as $pid => $need)
	{
		$row = $_pm['mysql']->getOneRecord(
			'SELECT COALESCE(SUM(sums),0) AS total FROM userbag WHERE uid='.intval($uid).
			' AND pid='.intval($pid).' AND sums>0 AND zbing=0 AND (cantrade IS NULL OR cantrade<>3)'
		);
		if(!is_array($row) || intval($row['total']) < intval($need)) return false;
	}
	return true;
}

function sszsConsumeRequiredProps($uid, $requiredProps)
{
	global $_pm;
	$uid = intval($uid);
	foreach($requiredProps as $pid => $need)
	{
		$remaining = intval($need);
		$rows = $_pm['mysql']->getRecords(
			'SELECT id,sums FROM userbag WHERE uid='.$uid.' AND pid='.intval($pid).
			' AND sums>0 AND zbing=0 AND (cantrade IS NULL OR cantrade<>3) ORDER BY id FOR UPDATE'
		);
		if(!is_array($rows)) return false;
		foreach($rows as $row)
		{
			if($remaining < 1) break;
			$take = min($remaining, intval($row['sums']));
			if($take < 1) continue;
			$sql = 'UPDATE userbag SET sums=sums-'.$take.' WHERE uid='.$uid.' AND id='.intval($row['id']).
				' AND sums>='.$take.' AND zbing=0 AND (cantrade IS NULL OR cantrade<>3)';
			if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) return false;
			$remaining -= $take;
		}
		if($remaining > 0) return false;
	}
	return true;
}
if($op == 'img'){
	if($id < 0){
		realseLock();
		die('宠物编号错误！');
	}
	$membbid = kdjlSafeMemValue($_pm['mem']->get('db_bbid'), array());
	if(!is_array($membbid)) $membbid = array();
	$bbarr = $_pm['mysql'] -> getRecords('SELECT super_zs.id,next_pet_id,bb.name FROM super_zs,bb WHERE bb.id = super_zs.next_pet_id AND cur_pet_id = '.$id);//echo 'SELECT next_pet_id,cardimg FROM super_zs,bb WHERE bb.id=super_zs.cur_pet_id AND cur_pet_id = '.$id;
	//print_r($bbarr);exit;
	if(!is_array($bbarr)){
		realseLock();
		die('未开放');
	}
	foreach($bbarr as $k => $v){
		$v['id'] = isset($v['id']) ? intval($v['id']) : 0;
		$v['next_pet_id'] = isset($v['next_pet_id']) ? intval($v['next_pet_id']) : 0;
		$v['name'] = isset($v['name']) ? $v['name'] : '';
		$cardimg = (isset($membbid[$v['next_pet_id']]['cardimg']) && $membbid[$v['next_pet_id']]['cardimg'] !== '') ? $membbid[$v['next_pet_id']]['cardimg'] : 'k'.$v['next_pet_id'].'.gif';
		$cardimg = sszsImage($cardimg);
		$nameJs = sszsHtml(sszsJsSingle($v["name"]));
		if($k == 0){
			$res .= '<div class="sd_pet r00" id="p_p'.($k+1).'">
		<img src="'.IMAGE_SRC_URL.'/bb/'.$cardimg.'" onclick="sszsstr('.$v["id"].',0,this);sszsbbid='.$v["id"].';copyWord(\''.$nameJs.'\')" style="opacity:1; filter : progid:DXImageTransform.Microsoft.Alpha(style=0,opacity=100,finishOpacity=100);cursor:pointer;" />
		</div>';
		}else{
			$res .= '<div class="sd_pet r00" id="p_p'.($k+1).'">
		<img src="'.IMAGE_SRC_URL.'/bb/'.$cardimg.'" onclick="sszsstr('.$v["id"].',0,this);sszsbbid='.$v["id"].';copyWord(\''.$nameJs.'\')" style="opacity:0.5; filter : progid:DXImageTransform.Microsoft.Alpha(style=0,opacity=50,finishOpacity=50);cursor:pointer;" />
		</div>';
		}
	}
	echo $res;
}else if($op == 'str'){
	$sid = (isset($_GET['sid']) && !is_array($_GET['sid'])) ? intval($_GET['sid']) : 0;
	if($sid > 0){
		$sql = 'SELECT next_pet_id,name,need_level,need_czl,need_props,next_pet_id FROM super_zs,bb WHERE bb.id = cur_pet_id AND cur_pet_id = '.$sid.' limit 1';
	}else{
		$sql = 'SELECT next_pet_id,name,need_level,need_czl,need_props,next_pet_id FROM super_zs,bb WHERE bb.id = cur_pet_id AND super_zs.id = '.$id;
	}
	$arr = $_pm['mysql'] -> getOneRecord($sql);
	if(!is_array($arr)){
		realseLock();
		die('未开放');
	}
	$arr['need_props'] = isset($arr['need_props']) ? $arr['need_props'] : '';
	$arr['next_pet_id'] = isset($arr['next_pet_id']) ? intval($arr['next_pet_id']) : 0;
	$arr['need_level'] = isset($arr['need_level']) ? $arr['need_level'] : 0;
	$arr['need_czl'] = isset($arr['need_czl']) ? $arr['need_czl'] : 0;
	$memprops = kdjlSafeMemValue($_pm['mem']->get('db_propsid'), array());
	if(!is_array($memprops)) $memprops = array();
	$parr1 = explode(',',$arr['need_props']);
	$pstr = '';
	foreach($parr1 as $v){
		$parr = explode('|',$v);
		if(count($parr) < 2 || intval($parr[0]) < 1) continue;
		$pid = intval($parr[0]);
		$pname = (isset($memprops[$pid]['name']) && $memprops[$pid]['name'] !== '') ? $memprops[$pid]['name'] : $pid;
		$pstr .= $pname.'x'.intval($parr[1]).', ';
	}
	$m = $_pm['mysql'] -> getOneRecord('SELECT zs_progress FROM super_jh WHERE pet_id ='.$arr['next_pet_id']);
	if(!is_array($m)) $m = array('zs_progress' => 0);
	$m['zs_progress'] = isset($m['zs_progress']) ? intval($m['zs_progress']) : 0;
	$needmoney = $m['zs_progress'] * 100000;
	$membbid = kdjlSafeMemValue($_pm['mem']->get('db_bbid'), array());
	if(!is_array($membbid)) $membbid = array();
	$pstr = substr($pstr,0,-2);
	$nextPetName = (isset($membbid[$arr['next_pet_id']]['name']) && $membbid[$arr['next_pet_id']]['name'] !== '') ? $membbid[$arr['next_pet_id']]['name'] : $arr['next_pet_id'];
	$str = '转生需求等级：'.$arr['need_level'].'<br />'
			.'转生需求成长：'.$arr['need_czl'].'<br />'
			.'转生所需金币：'.$needmoney.'<br />'
			.'转生所需材料：'.$pstr.'<br />'
			.'转生后宠物：'.$nextPetName;
	echo $str;
}else if($op == 'zs'){
	$oldbid = (isset($_GET['old']) && !is_array($_GET['old'])) ? intval($_GET['old']) : 0;
	$newbid = (isset($_GET['newid']) && !is_array($_GET['newid'])) ? intval($_GET['newid']) : 0;
	$wp1 = (isset($_GET['wp1']) && !is_array($_GET['wp1'])) ? intval($_GET['wp1']) : 0;
	$wp2 = (isset($_GET['wp2']) && !is_array($_GET['wp2'])) ? intval($_GET['wp2']) : 0;
	$type = (isset($_GET['type']) && !is_array($_GET['type'])) ? $_GET['type'] : '';
	$type1 = (isset($_GET['type1']) && !is_array($_GET['type1'])) ? $_GET['type1'] : '';
	$log = '';
	$nbb = false;
	$out = '';
	$srctime = 30;
	#################增加一个间隔时间################
	$timeKey = 'time'.$uid;
	$time = isset($_SESSION[$timeKey]) ? $_SESSION[$timeKey] : 0;
	if(empty($time))
	{
		$_SESSION[$timeKey] = time();
	}
	else
	{
		$nowtime = time();
		$ctime = $nowtime - $time;
		if($ctime < $srctime && $type != 'do' && $type1 != 'check')
		{
			realseLock();
			die("请稍候操作！");//没有达到间隔时间
		}
		else
		{
			$_SESSION[$timeKey] = time();
		}
	}

	if($oldbid < 1 && $newbid < 1){
		realseLock();
		die('您没有选择要转生的宠物，或者您选择的不是神圣宠物！');
	}
	$bb = $_pm['mysql'] -> getOneRecord('SELECT * FROM userbb WHERE uid = '.$uid.' AND id = '.$oldbid);
	if(!is_array($bb)){
		realseLock();
		die('没有相应的宠物！');
	}

	$bb['id'] = isset($bb['id']) ? intval($bb['id']) : 0;
	$bb['name'] = isset($bb['name']) ? $bb['name'] : '';
	$bb['wx'] = isset($bb['wx']) ? intval($bb['wx']) : 0;
	$bb['level'] = isset($bb['level']) ? intval($bb['level']) : 0;
	$bb['czl'] = isset($bb['czl']) ? floatval($bb['czl']) : 0;
	$bb['remakelevel'] = isset($bb['remakelevel']) ? $bb['remakelevel'] : '';
	$bb['remakeid'] = isset($bb['remakeid']) ? $bb['remakeid'] : '';
	$bb['remakepid'] = isset($bb['remakepid']) ? $bb['remakepid'] : '';
	$bb['old_bid'] = isset($bb['old_bid']) ? $bb['old_bid'] : 0;
	$bb['muchang'] = isset($bb['muchang']) ? intval($bb['muchang']) : 0;
	$bb['tgflag'] = isset($bb['tgflag']) ? intval($bb['tgflag']) : 0;
	if($bb['wx'] != 7){
		realseLock();
		die('该宠物不是可神圣转生的宠物！');
	}
	if($bb['muchang'] != 0 || $bb['tgflag'] != 0){
		realseLock();
		die('该宠物当前状态不能转生！');
	}

	$membbname = kdjlSafeMemValue($_pm['mem']->get('db_bbname'), array());
	$membbid = kdjlSafeMemValue($_pm['mem']->get('db_bbid'), array());
	if(!is_array($membbname)) $membbname = array();
	if(!is_array($membbid)) $membbid = array();
	$basePet = resolveBasePetForSuperNirvana($bb, $membbname, $membbid);
	if(!is_array($basePet)){
		realseLock();
		die('找不到该宠物的原始数据！');
	}
	if($newbid > 0){
		$sql = 'SELECT cur_pet_id,need_level,need_czl,need_props,base_success_rate,next_pet_id FROM super_zs WHERE id = '.$newbid.' AND cur_pet_id = '.intval($basePet['id']);
	}else {
		$sql = 'SELECT cur_pet_id,need_level,need_czl,need_props,base_success_rate,next_pet_id FROM super_zs WHERE cur_pet_id = '.intval($basePet['id']).' limit 1';
	}
	$need = $_pm['mysql'] -> getOneRecord($sql);
	if(!is_array($need)){
		realseLock();
		die('未开放');
	}
	$need['need_level'] = isset($need['need_level']) ? intval($need['need_level']) : 0;
	$need['need_czl'] = isset($need['need_czl']) ? floatval($need['need_czl']) : 0;
	$need['need_props'] = isset($need['need_props']) ? $need['need_props'] : '';
	$need['next_pet_id'] = isset($need['next_pet_id']) ? intval($need['next_pet_id']) : 0;
	$need['base_success_rate'] = isset($need['base_success_rate']) ? $need['base_success_rate'] : 0;

	//判断条件是否满足
	if($bb['level'] < $need['need_level'] || $bb['czl'] < $need['need_czl']){
		realseLock();
		die('等级不足'.$need['need_level'].'或成长不足'.$need['need_czl']);
	}

	// need props check
	$requiredProps = sszsParseRequiredProps($need['need_props']);
	if($requiredProps === false){
		realseLock();
		die('转生所需材料配置错误！');
	}
	if(!sszsHasRequiredProps($uid, $requiredProps)){
		realseLock();
		die('相应的必需品不够！');
	}

	if($wp1 == $wp2 && $wp1 > 0){
		$wpcheck = $_pm['mysql'] -> getOneRecord('SELECT sums FROM userbag WHERE uid = '.$uid.' AND id = '.$wp1.' AND sums >= 2 AND zbing = 0 AND (cantrade IS NULL OR cantrade<>3)');
		if(!is_array($wpcheck)){
			realseLock();
			die('道具不足！');
		}
	}
	$p1=$p2=array();
	if($wp1 > 0){
		$p1 = $_pm['mysql'] -> getOneRecord( 'SELECT pid,effect FROM userbag,props WHERE userbag.uid='.$uid.' AND userbag.id='.$wp1.' AND userbag.sums > 0 AND userbag.zbing = 0 AND (userbag.cantrade IS NULL OR userbag.cantrade<>3) AND props.id = userbag.pid');
	}
	if($wp2 > 0){
		$p2 = $_pm['mysql'] -> getOneRecord( 'SELECT pid,effect FROM userbag,props WHERE userbag.uid='.$uid.' AND userbag.id='.$wp2.' AND userbag.sums > 0 AND userbag.zbing = 0 AND (userbag.cantrade IS NULL OR userbag.cantrade<>3) AND props.id = userbag.pid');
	}
	$limit = $_pm['mysql'] -> getOneRecord('SELECT max_czl,zs_line,zs_progress FROM super_jh WHERE pet_id = '.$need['next_pet_id']);
	if(!is_array($limit)){
		realseLock();
		die('数据库中没有该宠物神圣进化的设定！');
	}
	$limit['max_czl'] = isset($limit['max_czl']) ? floatval($limit['max_czl']) : 0;
	$limit['zs_line'] = isset($limit['zs_line']) ? $limit['zs_line'] : '';
	$limit['zs_progress'] = isset($limit['zs_progress']) ? intval($limit['zs_progress']) : 0;
	if(is_array($p1)){
		$p1['pid'] = isset($p1['pid']) ? intval($p1['pid']) : 0;
		$p1['effect'] = isset($p1['effect']) ? $p1['effect'] : '';
	}
	if(is_array($p2)){
		$p2['pid'] = isset($p2['pid']) ? intval($p2['pid']) : 0;
		$p2['effect'] = isset($p2['effect']) ? $p2['effect'] : '';
	}
	$p1Type = $wp1 > 0 ? sszsBonusType($p1) : false;
	$p2Type = $wp2 > 0 ? sszsBonusType($p2) : false;
	if($wp1 > 0 && $p1Type === false){
		realseLock();
		die('附加属性道具无效！');
	}
	if($wp2 > 0 && $p2Type === false){
		realseLock();
		die('附加属性道具无效！');
	}
	if($p1Type == 'attr' && $p2Type == 'attr'){
		realseLock();
		die('同时不能使用两个增加属性的道具！');
	}
	//金币判断
	$need_money = $limit['zs_progress'] * 100000;

	$user = $_pm['user']->getUserById($uid);
	if(!is_array($user)) $user = array();
	$user['money'] = isset($user['money']) ? intval($user['money']) : 0;
	if($user['money'] < $need_money){
		realseLock();
		die('金币不足'.$need_money);
	}
	$sszsTransactionActive = true;
	$bb = $_pm['mysql'] -> getOneRecord('SELECT * FROM userbb WHERE uid = '.$uid.' AND id = '.$oldbid.' FOR UPDATE');
	if(is_array($bb)){
		$bb['wx'] = isset($bb['wx']) ? intval($bb['wx']) : 0;
		$bb['level'] = isset($bb['level']) ? intval($bb['level']) : 0;
		$bb['czl'] = isset($bb['czl']) ? floatval($bb['czl']) : 0;
	}
	if(is_array($bb)){
		$bb['muchang'] = isset($bb['muchang']) ? intval($bb['muchang']) : 0;
		$bb['tgflag'] = isset($bb['tgflag']) ? intval($bb['tgflag']) : 0;
	}
	if(!is_array($bb) || $bb['wx'] != 7 || $bb['muchang'] != 0 || $bb['tgflag'] != 0 || $bb['level'] < $need['need_level'] || $bb['czl'] < $need['need_czl']){
		sszsFail('宠物数据已发生变化，请重新操作！');
	}
	$user = $_pm['mysql']->getOneRecord('SELECT money,nickname FROM player WHERE id = '.$uid.' FOR UPDATE');
	if(is_array($user)){
		$user['money'] = isset($user['money']) ? intval($user['money']) : 0;
		$user['nickname'] = isset($user['nickname']) ? $user['nickname'] : '';
	}
	if(!is_array($user) || $user['money'] < $need_money){
		sszsFail('金币不足'.$need_money);
	}
	if($wp1 > 0){
		$wp1Used = $_pm['mysql']->query('UPDATE userbag SET sums = sums - 1 WHERE uid = '.$uid.' AND id = '.$wp1.' AND sums > 0 AND zbing = 0 AND (cantrade IS NULL OR cantrade<>3)');
		$result = $wp1Used ? mysql_affected_rows($_pm['mysql'] -> getConn()) : 0;
		if($result != 1){
			sszsFail('道具不足！');
		}
	}

	if($wp2 > 0){
		$wp2Used = $_pm['mysql']->query('UPDATE userbag SET sums = sums - 1 WHERE uid = '.$uid.' AND id = '.$wp2.' AND sums > 0 AND zbing = 0 AND (cantrade IS NULL OR cantrade<>3)');
		$result = $wp2Used ? mysql_affected_rows($_pm['mysql'] -> getConn()) : 0;
		if($result != 1){
			sszsFail('道具不足！');
		}
	}

	$newbb = $_pm['mysql'] -> getOneRecord('SELECT * FROM bb WHERE id = '.$need['next_pet_id']);
	if(!is_array($newbb)){
		sszsFail('转生后的宠物配置不存在！');
	}
	// calculate success rate
	$sus = getSuc($bb['level'],$p1,$p2);

	if(!sszsConsumeRequiredProps($uid, $requiredProps)){
		sszsFail('相应的必需品不够！');
	}
	$moneyUsed = $_pm['mysql'] -> query('UPDATE player SET money = money - '.$need_money.' WHERE id = '.$uid.' AND money >= '.$need_money);
	if(!$moneyUsed || mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){
		sszsFail('金币不足！');
	}
	$num = rand(1,10000);//echo $num.'<br />';
	$pendingWord = '';
	//$num = 0;
	if($num <= $sus){//成功
		$log .= $user['nickname'].' 成功:';
		$nbb = makebb($bb,$newbb,$limit['zs_progress'],$p1,$p2);
		if(!is_array($nbb)){
			sszsFail('生成转生宠物失败！');
		}
		if(!clearBB($bb)){
			sszsFail('清理原宠物数据失败！');
		}
		$task = new task();
		$pendingWord = '获得神圣宠物 '.$nbb['name'];
		$out = '5';
	}else{
		$log .= $user['nickname'].' 失败:';
		$out = '6';
	}
	//日志部分
	if(!is_array($p1)){
		$p1['pid'] = 0;
	}
	if(!is_array($p2)){
		$p2['pid'] = 0;
	}
	$log .= '加入物品:'.$p1['pid'].'+'.$p2['pid'].',原宠物：'.print_r($bb,1);
	if(is_array($nbb)){
		$log .= '转生路线：'.$limit['zs_line'].',新宠物：'.print_r($nbb,1);
	}
	$log .= date('Y-m-d H:i:s');
	$logSql = $_pm['mysql']->escape($log);
	if(!$_pm['mysql']->query("INSERT INTO gamelog(ptime,seller,buyer,pnote,vary)
		                      VALUES(unix_timestamp(),'{$uid}','{$uid}','{$logSql}',104)
							"))
	{
		sszsFail('转生日志保存失败！');
	}
	$sszsPendingLogId = intval(mysql_insert_id($_pm['mysql']->getConn()));
	if(!$_pm['mysql']->query('COMMIT')){
		sszsFail('转生数据保存失败！');
	}
	$sszsTransactionActive = false;
	$sszsPendingLogId = 0;
	$_pm['mem']->del(MEM_USER_KEY);
	$_pm['mem']->del(MEM_USERBB_KEY);
	$_pm['mem']->del(MEM_USERSK_KEY);
	$_pm['mem']->del(MEM_USERBAG_KEY);
	realseLock();
	$sszsLockHeld = false;
	if($pendingWord !== '')
	{
		$task->saveGword($pendingWord);
	}
	echo $out;
}
function getSuc($level,$wp1,$wp2){
	// success base: round(level / 30 * (1 + item_bonus), 2) * 100.
	// rand 1..10000 succeeds when value <= base.
	global $_pm;
	$num = 0;
	if(is_array($wp1)){
		$wp1['effect'] = isset($wp1['effect']) ? $wp1['effect'] : '';
		if(strpos($wp1['effect'], 'sszs:') === 0) $num += floatval(str_replace('sszs:','',$wp1['effect']));
	}
	if(is_array($wp2)){
		$wp2['effect'] = isset($wp2['effect']) ? $wp2['effect'] : '';
		if(strpos($wp2['effect'], 'sszs:') === 0) $num += floatval(str_replace('sszs:','',$wp2['effect']));
	}
	//echo $level.'<br />'.$num.'=======>';
	$res = round(($level/30*(1+$num)),2) * 100;
	return $res;
}

function makebb($oldbb,$newbb,$zsjd,$wp1,$wp2){
	global $_pm, $uid;
	$username = isset($_SESSION['nickname']) ? $_SESSION['nickname'] : '';
	$oldFields = array('hp','mp','srchp','srcmp','mc','ac','hits','miss','speed','level','czl');
	foreach($oldFields as $field){
		$oldbb[$field] = isset($oldbb[$field]) ? floatval($oldbb[$field]) : 0;
	}
	if($oldbb['srchp'] <= 0) $oldbb['srchp'] = $oldbb['hp'];
	if($oldbb['srcmp'] <= 0) $oldbb['srcmp'] = $oldbb['mp'];
	$newFields = array('id','wx','hp','mp','mc','ac','hits','miss','speed','nowexp');
	foreach($newFields as $field){
		$newbb[$field] = isset($newbb[$field]) ? $newbb[$field] : 0;
	}
	$newbb['name'] = isset($newbb['name']) ? $newbb['name'] : '';
	$newbb['skillist'] = isset($newbb['skillist']) ? $newbb['skillist'] : '';
	$newbb['imgstand'] = isset($newbb['imgstand']) ? $newbb['imgstand'] : '';
	$newbb['imgack'] = isset($newbb['imgack']) ? $newbb['imgack'] : '';
	$newbb['imgdie'] = isset($newbb['imgdie']) ? $newbb['imgdie'] : '';
	$newbb['kx'] = isset($newbb['kx']) ? $newbb['kx'] : 0;
	$newbb['remakelevel'] = isset($newbb['remakelevel']) ? $newbb['remakelevel'] : '';
	$newbb['remakeid'] = isset($newbb['remakeid']) ? $newbb['remakeid'] : '';
	$newbb['remakepid'] = isset($newbb['remakepid']) ? $newbb['remakepid'] : '';
	$eff = '';
	$czlnum = 10;
	if(is_array($wp1)){
		$wp1Type = sszsBonusType($wp1);
		if($wp1Type == 'attr'){
			$eff = $wp1['effect'];
		}
		if($wp1Type == 'growth'){
			$czlnum += floatval(str_replace('cszsczlbh:','',$wp1['effect']));
		}
	}
	if(is_array($wp2)){
		$wp2Type = sszsBonusType($wp2);
		if($wp2Type == 'attr' && $eff == ''){
			$eff = $wp2['effect'];
		}
		if($wp2Type == 'growth'){
			$czlnum += floatval(str_replace('cszsczlbh:','',$wp2['effect']));
		}
	}
	$pac = $pmc = $phit = $pmiss = $pspeed = $php = $pmp = 0;
	if($eff != ''){
		$propseff = explode(':',$eff);
		switch($propseff[0]){
			case "addac": $pac = $propseff[1];break;
			case "addmc": $pmc = $propseff[1];break;
			case "addhit": $phit = $propseff[1];break;
			case "addmiss": $pmiss = $propseff[1];break;
			case "addspeed": $pspeed = $propseff[1];break;
			case "addhp": $php = $propseff[1];break;
			case "addmp": $pmp = $propseff[1];break;
		}
	}

	//转生属性计算公式：(初始属性*转生阶段+当前属性*等级/6000+当前属性*成长/9000)*（百分百+道具百分比)
	$hp = round(($newbb['hp']*$zsjd+$oldbb['srchp'] * $oldbb['level']/6000 + $oldbb['srchp'] * $oldbb['czl']/9000) * ($php+1));
	$mp = round(($newbb['mp']*$zsjd+$oldbb['srcmp'] * $oldbb['level']/6000 + $oldbb['srcmp'] * $oldbb['czl']/9000) * ($pmp+1));
	$mc = round(($newbb['mc']*$zsjd+$oldbb['mc'] * $oldbb['level']/6000 + $oldbb['mc'] * $oldbb['czl']/9000) * ($pmc+1));
	$ac = round(($newbb['ac']*$zsjd+$oldbb['ac'] * $oldbb['level']/6000 + $oldbb['ac'] * $oldbb['czl']/9000) * ($pac+1));
	$hits = round(($newbb['hits']*$zsjd+$oldbb['hits'] * $oldbb['level']/6000 + $oldbb['hits'] * $oldbb['czl']/9000) * ($phit+1));
	$miss = round(($newbb['miss']*$zsjd+$oldbb['miss'] * $oldbb['level']/6000 + $oldbb['miss'] * $oldbb['czl']/9000) * ($pmiss+1));
	$speed = round(($newbb['speed']*$zsjd+$oldbb['speed'] * $oldbb['level']/6000 + $oldbb['speed'] * $oldbb['czl']/9000) * ($pspeed+1));
	$czl = round($oldbb['czl']*$czlnum*0.01,1);
	//echo '$ac = round(('.$newbb['ac'].'*'.$zsjd.'+'.$oldbb['ac'] .'*'. $oldbb['level'].'/6000 + '.$oldbb['ac'].' * '.$oldbb['czl'].'/9000) * ('.$pac.'+1))';exit;
	$usernameSql = $_pm['mysql']->quote($username);
	/*echo "INSERT INTO userbb(
								   name,
								   uid,
								   username,
								   level,
								   wx,
								   ac,
								   mc,
								   srchp,
								   hp,
								   srcmp,
								   mp,
								   skillist,
								   stime,
								   nowexp,
								   lexp,
								   imgstand,
								   imgack,
								   imgdie,
								   hits,
								   miss,
								   speed,
								   kx,
								   remakelevel,
								   remakeid,
								   remakepid,
								   muchang,
								   czl,
								   headimg,
								   cardimg,
								   effectimg,
								   old_bid
								  )
				VALUES(
					   '{$newbb['name']}',
					   '{$uid}',
					   {$usernameSql},
					   '1',
					   '{$newbb['wx']}',
					   '{$ac}',
					   '{$mc}',
					   '{$hp}',
					   '{$hp}',
					   '{$mp}',
					   '{$mp}',
					   '{$newbb['skillist']}',
					   unix_timestamp(),
					   '{$newbb['nowexp']}',
					   '100',
					   '{$newbb['imgstand']}',
					   '{$newbb['imgack']}',
					   '{$newbb['imgdie']}',
					   '{$hits}',
					   '{$miss}',
					   '{$speed}',
					   '{$newbb['kx']}',
					   '{$newbb['remakelevel']}',
					   '{$newbb['remakeid']}',
					   '{$newbb['remakepid']}',
					   '0',
					   '{$czl}',
						   't{$newbb['id']}.gif',
						   'k{$newbb['id']}.gif',
						   'q{$newbb['id']}.gif',
						   '{$newbb['id']}'
					   )
			  ";exit;*/
	if(!$_pm['mysql']->query("INSERT INTO userbb(
								   name,
								   uid,
								   username,
								   level,
								   wx,
								   ac,
								   mc,
								   srchp,
								   hp,
								   srcmp,
								   mp,
								   skillist,
								   stime,
								   nowexp,
								   lexp,
								   imgstand,
								   imgack,
								   imgdie,
								   hits,
								   miss,
								   speed,
								   kx,
								   remakelevel,
								   remakeid,
								   remakepid,
								   muchang,
								   czl,
								   headimg,
								   cardimg,
								   effectimg,
								   old_bid
								  )
				VALUES(
					   '{$newbb['name']}',
					   '{$uid}',
					   {$usernameSql},
					   '1',
					   '{$newbb['wx']}',
					   '{$ac}',
					   '{$mc}',
					   '{$hp}',
					   '{$hp}',
					   '{$mp}',
					   '{$mp}',
					   '{$newbb['skillist']}',
					   unix_timestamp(),
					   '{$newbb['nowexp']}',
					   '100',
					   '{$newbb['imgstand']}',
					   '{$newbb['imgack']}',
					   '{$newbb['imgdie']}',
					   '{$hits}',
					   '{$miss}',
					   '{$speed}',
					   '{$newbb['kx']}',
					   '{$newbb['remakelevel']}',
					   '{$newbb['remakeid']}',
					   '{$newbb['remakepid']}',
					   '0',
					   '{$czl}',
						   't{$newbb['id']}.gif',
						   'k{$newbb['id']}.gif',
						   'q{$newbb['id']}.gif',
						   '{$newbb['id']}'
						   )
			  "))
	{
		return false;
	}
	$bbid = intval($_pm['mysql']->last_id());
	if($bbid <= 0)
	{
		return false;
	}
	$jnall = explode(",", $newbb['skillist']);
	$memskillsysid = kdjlSafeMemValue($_pm['mem']->get('db_skillsysid'), array());
	if(!is_array($memskillsysid)) $memskillsysid = array();
	foreach($jnall as $a => $b)
	{
		$arr = explode(":", $b);

		if(!isset($arr[0]) || !isset($arr[1]) || !isset($memskillsysid[$arr[0]]))
		{
			return false;
		}
		$jn = $memskillsysid[$arr[0]];
		$jn['ackvalue'] = isset($jn['ackvalue']) ? $jn['ackvalue'] : '';
		$jn['plus'] = isset($jn['plus']) ? $jn['plus'] : '';
		$jn['uhp'] = isset($jn['uhp']) ? $jn['uhp'] : '';
		$jn['ump'] = isset($jn['ump']) ? $jn['ump'] : '';
		$jn['imgeft'] = isset($jn['imgeft']) ? $jn['imgeft'] : '';
		$jn['name'] = isset($jn['name']) ? $jn['name'] : '';
		$jn['vary'] = isset($jn['vary']) ? $jn['vary'] : '';
		$jn['wx'] = isset($jn['wx']) ? $jn['wx'] : 0;
		$jn['id'] = isset($jn['id']) ? $jn['id'] : intval($arr[0]);

		$ack  = explode(",", $jn['ackvalue']);
		$plus = explode(",", $jn['plus']);
		$uhp  = explode(",", $jn['uhp']);
		$ump  = explode(",", $jn['ump']);
		$img  = explode(",", $jn['imgeft']);
		$skillLevel = intval($arr[1]);
		$skillIndex = $skillLevel - 1;
		if(!isset($ack[$skillIndex]) || !isset($uhp[$skillIndex]) || !isset($ump[$skillIndex]))
		{
			return false;
		}
		$ackValue = $ack[$skillIndex];
		$plusValue = isset($plus[$skillIndex]) ? $plus[$skillIndex] : '';
		$uhpValue = intval($uhp[$skillIndex]);
		$umpValue = intval($ump[$skillIndex]);
		$imgValue = isset($img[$skillIndex]) ? $img[$skillIndex] : '';

		if(!$_pm['mysql']->query("INSERT INTO skill(bid,name,level,vary,wx,value,plus,img,uhp,ump,sid)
					VALUES(
						   '{$bbid}',
						   '{$jn['name']}',
						   '{$skillLevel}',
						   '{$jn['vary']}',
						   '{$jn['wx']}',
						   '{$ackValue}',
						   '{$plusValue}',
						   '{$imgValue}',
						   '{$uhpValue}',
						   '{$umpValue}',
						   '{$jn['id']}'
						  )
				  "))
		{
			return false;
		}
	}
	$oldPetId = isset($oldbb['id']) ? intval($oldbb['id']) : 0;
	if($oldPetId < 1)
	{
		return false;
	}
	$sql = "UPDATE player
			SET mbid = {$bbid},
				fightbb = IF(fightbb = {$oldPetId}, {$bbid}, fightbb)
			WHERE id = {$uid}";
	if(!$_pm['mysql'] -> query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
	{
		return false;
	}
	$newbb1 = $_pm['mysql']->getOneRecord("SELECT *
								  FROM userbb
								 WHERE uid={$uid} AND id={$bbid}
								 LIMIT 1
							  ");
	return $newbb1;
}

function clearBB($bb)
{//return;
	global $_pm, $uid;
	$id = isset($bb['id']) ? intval($bb['id']) : 0;
	if($id <= 0) return false;



	// del sk.
	if(!$_pm['mysql']->query("DELETE FROM skill
				 WHERE bid={$id}
			  "))
	{
		return false;
	}

	// del zb.
	if(!$_pm['mysql']->query("DELETE FROM userbag
				 WHERE uid={$uid} and zbpets={$id}
			  "))
	{
		return false;
	}
	// del bb.
	if(!$_pm['mysql']->query("DELETE FROM userbb
				 WHERE uid={$uid} and id={$id}
			  "))
	{
		return false;
	}
	return mysql_affected_rows($_pm['mysql']->getConn()) == 1;

}

function resolveBasePetForSuperNirvana($pet, $byName, $byId)
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
$_pm['mem']->memClose();
realseLock();
?>
