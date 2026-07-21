<?php


/**************************
平台禁言封号

locktime   时间（分钟）

roleName   角色名

ServerUrl  url  urlencode编码

type       禁言==1    封号==2





return

1001 限制访问
1002 加密串
1003 没有这个区
1004 没有这个用户
1005 数据错
1006 失败
1000 成功
****************************/

/*IP限制*/


/**
locktime   时间（分钟）

roleName   角色名

ServerUrl  url   urlencode编码

type       禁言==1  封号==2
**/

//http://xjtest1.webgame.com.cn/api/Gamemaster.php?locktime=
$locktime = (isset($_GET['locktime']) && !is_array($_GET['locktime'])) ? $_GET['locktime'] : '';
$roleNameRaw = (isset($_GET['roleName']) && !is_array($_GET['roleName'])) ? $_GET['roleName'] : '';
$roleName = iconv('utf-8','gbk',$roleNameRaw);
$ServerUrl = (isset($_GET['ServerUrl']) && !is_array($_GET['ServerUrl'])) ? $_GET['ServerUrl'] : '';
$type = (isset($_GET['type']) && !is_array($_GET['type'])) ? $_GET['type'] : '';
$key = (isset($_GET['key']) && !is_array($_GET['key'])) ? $_GET['key'] : '';
$userid = (isset($_GET['userid']) && !is_array($_GET['userid'])) ? intval($_GET['userid']) : 0;






if(!is_numeric($locktime)||empty($roleName)||empty($ServerUrl)||!is_numeric($type)||empty($locktime)||empty($type)||!is_numeric($userid)){
	die('1005');
}

//die($locktime.$roleName.$ServerUrl.$type.'315sab');
$sgin = md5($locktime.$roleNameRaw.$ServerUrl.$type.'315sab');

if($key!=$sgin){
	die("1002");
}


//$roleName1 = urlencode($roleName);
//$sql="select p_id from player where p_name='{$roleName}'";

//$player=$db->query_first($sql);

//if(empty($player['p_id'])){
//	die("1004");
//}
require_once('../config/config.game.php');
$players = $_pm['mysql'] -> getOneRecord("SELECT * FROM player WHERE id = $userid");
if(!is_array($players)){
	die('1004');
}
$nickname = $roleName;

require_once('../kernel/socketmsg.v1.php');
require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
$s=new socketmsg();


if($type==1){
	/*$msg_key = 'chatMsgList';
	$nowMsgList = unserialize($_pm['mem']->get($msg_key));
	$arr = explode('linend', $nowMsgList);
	if( count($arr)>20 ) // cear old
	{
		$arrt = array_shift($arr);
	}*/
	if($players['password'] > 0){
		die('1007');
	}
	if($locktime=='-1'){//永久禁言
		$time = time() + 10 * 365 * 12 * 3600;
		$_pm['mysql']->query("update player set password='{$time}' where id={$players['id']}");
		$result = mysql_affected_rows($_pm['mysql'] -> getConn());
		if($result != 1){
			exit('1006');
		}else{
			echo '1000';
		}
		$old = kdjlSafeMemValue($_pm['mem']->get($players['id']), array());
		$old['password']=1;
		$_pm['mem']->set(array('k'=> $players['id'], 'v'=> $old));
		$s->sendMsg($players['id'].'|YZ');
		//$newstr = '<font color=red>[系统公告]天降巨雷，把玩家&nbsp;'.$nickname.'&nbsp;嘴巴劈成了两半，&nbsp;'.$nickname.'&nbsp;永久失去了说话的权利！';
	}else if($locktime>0){
		$time = time() + $locktime * 60;
		$hour = $locktime/60;
		$_pm['mysql']->query("update player set password='{$time}' where id={$players['id']}");
		$result = mysql_affected_rows($_pm['mysql'] -> getConn());
		if($result != 1){
			exit('1006');
		}else{
			echo '1000';
		}
		$old = kdjlSafeMemValue($_pm['mem']->get($players['id']), array());
		$old['password']=1;
		$_pm['mem']->set(array('k'=> $players['id'], 'v'=> $old));
		$s->sendMsg($players['id'].'|JY');
		//$newstr = '<font color=red>[系统公告]'. $nickname . ' 因为违反江湖道义，被众英雄送入思过涯思过'.$hour.'小时！';
	}
	/*foreach($arr as $k=>$v)
	{
		$retstr .= $v.'linend';
	}

	$retstr = $retstr.$newstr;
	$_pm['mem']->set( array('k'=>$msg_key, 'v'=>$retstr) ); // default ten min.

	//----------------------------------------------------------------------------------------------------------------------
	$_olddata = @unserialize($_pm['mem']->get('ttmt_data_notice'));

	$swfData = iconv('gbk','utf-8',$newstr);
	$_olddata['an'] = isset($_olddata['an'])?$_olddata['an']."\n".$swfData:$swfData;
	$_pm['mem']->set(array('k'=>'ttmt_data_notice','v'=>$_olddata));*/
}else if($type == 2){//封号
	if($players['password'] == 1){
		die('1007');
	}
	$_pm['mysql']->query("UPDATE player set secid=1 WHERE id={$players['id']}");
	$result = mysql_affected_rows($_pm['mysql'] -> getConn());
	if($result != 1){
		exit('1006');
	}else{
		echo '1000';
	}
	$_pm['mem']->set(array('k'=>$players['id'] . 'chat', 'v'=>0)); // 踢下线
	$_pm['mem']->del($players['id']);
	$s->sendMsg($players['id'].'|FH');
}