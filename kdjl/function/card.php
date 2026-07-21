<?php
require_once('../config/config.game.php');
//secStart($_pm['mem']);
header('Content-Type:text/html;charset=utf-8');

$cardid = (isset($_GET['cardid']) && !is_array($_GET['cardid'])) ? trim($_GET['cardid']) : '';
$uid = (isset($_GET['userid']) && !is_array($_GET['userid'])) ? intval($_GET['userid']) : 0;
$requestFlag = (isset($_GET['flag']) && !is_array($_GET['flag'])) ? $_GET['flag'] : '';
$flag = md5($cardid.$uid.'afecy564thgkui');
if($uid < 1 || $flag != $requestFlag){
	die('操作有误！');
}
$_SESSION['id'] = $uid;
$user	 = $_pm['user']->getUserById($_SESSION['id']);//用户信息
if(!is_array($user)){
	die('玩家数据错误！');
}
if($cardid == ""){
	die('请填写完整！');
}

function kdjlCardCodeCount($raw)
{
	if($raw == 'checked') return 'checked';
	if($raw === false || $raw === null || $raw === '') return 0;
	if(is_numeric($raw)) return intval($raw);
	if(!is_string($raw)) return 0;
	$parsed = @unserialize($raw);
	return is_numeric($parsed) ? intval($parsed) : 0;
}
$memKey = $cardid.$uid;
$memRaw = $_pm['mem']->get($memKey);
$memcardnum = kdjlCardCodeCount($memRaw);
if($memcardnum == 'checked'){
	die('此卡已经领取！');
}else if($memcardnum >= 10){
	die('今天您已经错了10次，请明天再来吧！');
}

//是否存存在此卡号的卡片

$cardidSql = $_pm['mysql']->escape($cardid);
$infoarr = $_pm['mysql'] -> getOneRecord("SELECT id FROM card_info WHERE cardid = '{$cardidSql}' AND checked is null");
if(!is_array($infoarr)){
	die('此类卡片不存在或已经领过奖励！');
}



//是否为只能领一次奖的卡片
$checkarr = false;
if(is_array($checkarr)){
	die('对不起，您已经领过此类卡片！');
}
$time = time();
$cardLocked = $_pm['mysql'] -> query("UPDATE card_info SET uid = {$uid},checked = 1,times = $time WHERE cardid = '{$cardidSql}' AND checked is null");
if(!$cardLocked){
	$num = intval($memcardnum) + 1;
	$handle = $_pm['mem'] -> getHandle();
	$handle->set($memKey, serialize($num), MEMCACHE_COMPRESSED, 50);
	die('卡号或密码错误！');
}
if(mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){//密码输入错误
	$num = intval($memcardnum) + 1;
	$handle = $_pm['mem'] -> getHandle();
	//$handle -> set($cardid.$_SESSION['id'], $num, MEMCACHE_COMPRESSED, 43200);
	$handle->set($memKey, serialize($num), MEMCACHE_COMPRESSED, 50);
	die('卡号或密码错误！');
}else{//输入正确，发放奖励
	/*$parr = explode(',',$arr['prize']);
	if(is_array($parr)){
		$retstr = '';
		$task = new task();
		foreach($parr as $v){
			$inarr = explode(":",$v);
			$task->saveGetPropsMore($inarr[0],$inarr[1]);
			$prs = $_pm['mysql']->getOneRecord("SELECT name FROM props WHERE id={$inarr[0]}");
			if(empty($retstr)){
				$retstr = '获得道具 '.$prs['name'].'&nbsp;'.$inarr[1].' 个';
			}else{
				$retstr .= ",".$prs['name'].'&nbsp;'.$inarr[1].' 个';
			}
		}
	}*/
	$retstr = 'ok';
	$_pm['mem'] -> set(array('k'=>$memKey,'v'=>'checked'));
	echo $retstr;
	exit;
}
?>
