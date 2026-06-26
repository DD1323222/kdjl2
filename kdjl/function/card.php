<?php
require_once('../config/config.game.php');
//secStart($_pm['mem']);
header('Content-Type:text/html;charset=gbk');

$cardid = $_GET['cardid'];
$_SESSION['id'] = intval($_GET['userid']);
$flag = md5($cardid.$_SESSION['id'].'afecy564thgkui');
if($flag != $_GET['flag']){
	die('操作有误！');
}
$user	 = $_pm['user']->getUserById($_SESSION['id']);//用户信息
if($cardid == ""){
	die('请填写完整！');
}

$memcardnum = unserialize($_pm['mem']->get($cardid.$_SESSION['id']));
if($memcardnum == 'checked'){
	die('此卡已经领取！');
}else if($memcardnum >= 10){
	die('今天您已经错了10次，请明天再来吧！');
}

//是否存存在此卡号的卡片

$infoarr = $_pm['mysql'] -> getOneRecord("SELECT id FROM card_info WHERE cardid = '$cardid' AND checked is null");
if(!is_array($infoarr)){
	die('此类卡片不存在或已经领过奖励！');
}



//是否为只能领一次奖的卡片
$checkarr = $_pm['mysql'] -> getOneRecord("SELECT id FROM card_info WHERE uid = {$_SESSION['id']} and cardtype = $id");
if(is_array($checkarr)){
	die('对不起，您已经领过此类卡片！');
}
$time = time();
$_pm['mysql'] -> query("UPDATE card_info SET uid = {$_SESSION['id']},checked = 1,times = $time WHERE cardid = '{$cardid}' AND checked is null");
if(mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){//密码输入错误
	$num = $memcardnum + 1;
	$handle = $_pm['mem'] -> getHandle();
	//$handle -> set($cardid.$_SESSION['id'], $num, MEMCACHE_COMPRESSED, 43200);
	$handle->set($cardid.$_SESSION['id'], serialize($num), MEMCACHE_COMPRESSED, 50);
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
	$_pm['mem'] -> set(array('k'=>$cardid.$_SESSION['id'],'v'=>'checked'));
	echo $retstr;
	exit;
}
?>