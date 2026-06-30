<?php
//set_time_limit(30);
/**
@Usage: Get player information for map option.
@Write date: 2008.03.22
@Write by sugf 
@Copyright www.webgame.com.cn
@##############################################
@Notice:
 This script only used test user data connection.
 so,we defined two user for test.
*/
if(empty($_REQUEST['type'])){
	exit();
}


require_once('../config/config.game.php');
if ( !isset($_SESSION['id']) || intval($_SESSION['id']) < 0 ) exit("你没登陆.");
require_once(dirname(dirname(__FILE__)).'/kernel/memory.v1.1.php');

$rs = $_pm['user']->getUserById($_SESSION['id']);
$userIsVip = false; /* whether the user send msg is a VIP user, if he has the 口袋精灵VIP卡, he is. added by Zheng.Ping */
if($rs===FALSE ||  !empty($rs['password'])  || $rs['secid']>0 || $_REQUEST['msg']=='{'||$_REQUEST['msg']=='}') exit("你没登陆!");

/*new player not say.*/
//if ($rs['regtime']+3600>time() || $rs['money']<1000) exit();

// 封号处理：增加封号命令：@@FH玩家昵称
/*$fff = false;
if(strpos($_SERVER['HTTP_USER_AGENT'],'Firefox/3')!==false||strpos($_SERVER['HTTP_USER_AGENT'],'Firefox/2')!==false){
	$fff = true;
}
$msg = htmlspecialchars(($_REQUEST['msg']),ENT_QUOTES,"GBK");
if(strlen($_REQUEST['msg'])>1&&strlen($msg)<1||$fff){
	$msg = htmlspecialchars(iconv('utf-8','GBK',$_REQUEST['msg']),ENT_QUOTES,"GBK");
}*/


//
function getip()
{
 if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown'))
 {
  $ip = getenv('HTTP_CLIENT_IP');
 }
 elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown'))
 {
  $ip = getenv('HTTP_X_FORWARDED_FOR');
 }
 elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown'))
 {
  $ip = getenv('REMOTE_ADDR');
 }
 elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown'))
 {
  $ip = $_SERVER['REMOTE_ADDR'];
 }
 return preg_match("/[\d\.]{7,15}/", $ip, $matches) ? $matches[0] : 'unknown';
} 
//



$fff = false;
if(strpos($_SERVER['HTTP_USER_AGENT'],'Firefox/3')!==false||strpos($_SERVER['HTTP_USER_AGENT'],'Firefox/2')!==false){
	$fff = true;
}
//修改GBK2312------utf-8
$msg = htmlspecialchars(($_REQUEST['msg']),ENT_QUOTES,"utf-8");
if(strlen($_REQUEST['msg'])>1&&strlen($msg)<1||$fff){
	$msg = htmlspecialchars(iconv('gbk','utf-8',$_REQUEST['msg']),ENT_QUOTES,"utf-8");

}
//$msg = htmlspecialchars(iconv('utf-8','GBK',$_GET['msg']));

//echo $msg;exit;
$fletter = substr($msg,0,1);
$len = strlen(trim($msg) - 1);
$lletter = substr($msg,$len,1);

$arr = array(
);
//message
$msg =iconv('GBK','utf-8',$msg);
for($i=0;$i<count($arr);$i++){
	$msg = str_replace(iconv('GBK','utf-8',$arr[$i]),"*",$msg);
}
$msg =iconv('utf-8','GBK',$msg);
if($fletter == "{" && $lletter != "}")
{
	$msg = $msg."}";
}
else if($fletter != "{" && $lletter == "}")
{
	$msg = "{".$msg;
}

$welcome = memContent2Arr("db_welcome",'code');
$gm_in_mem = $welcome['admin']['contents'];
if(!empty($gm_in_mem))
{
	$_gm['name'] = array_merge($_gm['name'],preg_split("/[,；;，]/",$gm_in_mem));
}

$cmdstr = substr($msg,0,2);


if (($cmdstr == 'JY' || $cmdstr == 'FH'|| $cmdstr == 'JJ' || $cmdstr == 'YZ' || $cmdstr == 'ZY' || $cmdstr == 'WF') && (in_array($rs['name'],$_gm['name'])))
{
	$nickname = str_replace(array("JY",'FH','JJ','YZ','ZY','WF'), '',$_REQUEST['msg']);
	$players = $_pm['mysql']->getOneRecord("SELECT id,password FROM player  where nickname='{$nickname}' limit 0,1");
	if (is_array($players))
	{
		if ($cmdstr == 'FH')
		{
			$_pm['mysql']->query("UPDATE player set secid=1 WHERE id={$players['id']}");
			$_pm['mem']->set(array('k'=>$players['id'] . 'chat', 'v'=>0)); // 踢下线
			$_pm['mem']->del($players['id']);
			exit("FH");
		}
		else if($cmdstr == 'JY') // 12小时禁言
		{
			$time = time() + 12 * 3600;
			$_pm['mysql']->query("update player set password='{$time}' where id={$players['id']}");
			$old = unserialize($_pm['mem']->get($players['id']));
			$old['password']=1;
			$_pm['mem']->set(array('k'=> $players['id'], 'v'=> $old));
			$msg = '@'. $nickname . ' 因为违反江湖道义，被众英雄送入思过涯思过12小时！';
		}
		else if($cmdstr == "JJ") // 12小时解禁
		{
			$nowtime = time();
			$ctime = ($players['password'] - $nowtime) / 3600;
			if($ctime <  12)
			{
				$_pm['mysql']->query("update player set password='0' where id={$players['id']}");
				$old = unserialize($_pm['mem']->get($players['id']));
				$old['password']=0;
				$_pm['mem']->set(array('k'=> $players['id'], 'v'=> $old));
				$msg = '@'. $nickname . ' 在思过涯面壁思过结束，被允许重出江湖！';
			}
			//exit();
		}
		else if($cmdstr == 'YZ') // 永久禁言
		{
			$time = time() + 10 * 365 * 12 * 3600;
			$_pm['mysql']->query("update player set password='{$time}' where id={$players['id']}");
			$old = unserialize($_pm['mem']->get($players['id']));
			$old['password']=1;
			$_pm['mem']->set(array('k'=> $players['id'], 'v'=> $old));
			$msg = '@ 天降巨雷，把玩家&nbsp;'.$nickname.'&nbsp;嘴巴劈成了两半，&nbsp;'.$nickname.'&nbsp;永久失去了说话的权利！';
		}
		else if($cmdstr == 'WF') // 永久禁言不发公告
		{
			$time = time() + 10 * 365 * 12 * 3600;
			$_pm['mysql']->query("update player set password='{$time}' where id={$players['id']}");
			$old = unserialize($_pm['mem']->get($players['id']));
			$old['password']=1;
			$_pm['mem']->set(array('k'=> $players['id'], 'v'=> $old));
			//$msg = '@ 天降巨雷，把玩家&nbsp;'.$nickname.'&nbsp;嘴巴劈成了两半，&nbsp;'.$nickname.'&nbsp;永久失去了说话的权利！';
			$msg = "";
			$rs['nickname'] = "";
		}
		else if($cmdstr == "ZY") //解禁
		{
			$_pm['mysql']->query("update player set password='0' where id={$players['id']}");
			$old = unserialize($_pm['mem']->get($players['id']));
			$old['password']=0;
			$_pm['mem']->set(array('k'=> $players['id'], 'v'=> $old));
			$msg = '@ 天降神光，照射到&nbsp;'. $nickname . '&nbsp;的身上，他嘴上的伤口奇迹般的复原了，从此，他过上了幸福的生活.';
			//exit();
		}
	}
	//exit();
}

// 时间间隔:
if ($_SESSION['msgtime'] && $_SESSION['msgtime']>time()-5) exit('TOOFAST');
if(!isset($_SESSION['chatHis']))
{
	$_SESSION['chatHis']=array();
	$_SESSION['chatHisCount']=0;
}
if(compareMsg($msg))
{
	die('REPEATCONTENT');
}
else
{	
	$_SESSION['chatHis'][$_SESSION['chatHisCount']%3]=$msg;
	$_SESSION['chatHisCount']++;
}

if (strlen($_REQUEST['msg'])>100 && substr($msg, 0,2) != '//' && (!in_array($rs['name'],$_gm['name']))) exit("DATATOOLONG");
if (strlen($_REQUEST['msg'])>100 && (in_array($rs['name'],$_gm['name']))) exit("DATATOOLONG:".strlen($_REQUEST['msg']));
$truename= $rs['nickname'];

$msg = str_ireplace('linend','',$msg);
$sc = 0;

//Format msg.
//展示宠物
if(!empty($_REQUEST['type']) && $_REQUEST['type'] == 'showbb')
{
	$srctime = 10;
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
			die("1000");//没有达到间隔时间
		}
		else
		{
			$_SESSION['paitimes'.$_SESSION['id']] = time();
		}
	}
	$bid = $_REQUEST['bid'];
	$user = $_pm['user']->getUserById($_SESSION['id']);
	if($user['mbid'] != $msg)
	{
		die("100");
	}
	$sql = "SELECT bbshow FROM player_ext WHERE uid = {$_SESSION['id']}";
	$arr = $_pm['mysql'] -> getOneRecord($sql);
	if($arr['bbshow'] < 1)
	{
		die("101");
	}
	$bb = $_pm['mysql'] -> getOneRecord("SELECT name FROM userbb WHERE id = $msg");
	$str = $msg;
	//$_olddata = @unserialize($_pm['mem']->get('ttmt_data_notice'));
	//$swfData = iconv('GBK','utf-8',"\$".$truename."`说：")."<a onclick=\"showBb('".$msg."')\"><b><font color=\"#A3ABAD\">".iconv('GBK','utf-8','【'.$bb['name'].'】')."</font></b></a>";
	
	/*
	$_olddata['bs'] = isset($_olddata['bs'])?$_olddata['bs']."<br/>[系统公告]：".$swfData:$swfData;
	$_pm['mem']->set(array('k'=>'ttmt_data_notice','v'=>$_olddata));	
	*/
	$msg = "<span style='color:#A3ABAD;cursor:pointer;color:#A3ABAD;'><a onclick=showBb('".$msg."')><b>【".$bb['name']."】</b></a></span>";
	require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
	$s=new socketmsg();
	//展示宠物
	$s->sendMsg(iconv('utf-8','utf-8','CT|$'.$truename.'`说: '.$msg));
	
	$_pm['mysql'] -> query("UPDATE player_ext SET bbshow = bbshow - 1 WHERE uid = {$_SESSION['id']}");
	die();
}
die();
if (substr($msg, 0,2) == '!!') $msg = '<font color=blue>'.substr($msg,2).'</font>';
else if (substr($msg, 0,1) == '!') $msg = '<font color=#FF00FF>'.substr($msg,1).'</font>';
/*
else if (substr($msg, 0,1) == '$' && ($rs['money']>1000)) 
{
	$rs['money']-=1000;
	//$msg ='<marquee scrollamount=1 behavior=alternate scrolldelay=1 width=300 direction=up height=25><font color=#FF00FF>'.substr($msg,1).'</font></marquee>';
	$msg ='<font color=#FF00FF>'.substr($msg,1).'</font>';
} */ //commented by Zheng.Ping
else if (substr($msg, 0,1) == '$' /* && ($rs['money']>1000) */) /* added by Zheng.Ping */
{
	$arr = array("1427","1474","1475","1476","1477","1478","1479","1480","1481","1482","1483","1484","1485");
	$arrayid=date('n');
	if($arrayid=='1')
	{
		$arraycode=array("1427",$arr[$arrayid],$arr[12]);
	}else 
	{
		$arrayidjian=$arrayid-1;
		$arraycode=array("1427",$arr[$arrayidjian],$arr[$arrayid]);
	}
	$u_bags=getUserBagByIds($_SESSION['id'], $arraycode, $_pm['mysql']); /* 口袋精灵VIP卡:1427 */	
	
   // $u_bags=getUserBagById($_SESSION['id'], 1427, $_pm['mysql']); /* 口袋精灵VIP卡:1427 */
	foreach($u_bags as $v)
	{
		if($v && isset($v['sums']) && $v['sums'] > 0)
		{
			$userIsVip = true;
			$msg =' <font color="#FF0000">'.substr($msg, 1).'</font>';
		}
	}
	/*if ($u_bags && isset($u_bags['sums']) && $u_bags['sums'] > 0) {
		$userIsVip = true;
		$msg = '<font color="#FF0000">' . substr($msg, 1) .'</font></marquee>';
	}*/

	unset($u_bags);
} /* added by Zheng.Ping */
else if (substr($msg, 0,1) == '#' && ($rs['money']>10)) 
{
	$rs['money']-=10;
	$msg='<font color=green>'.substr($msg,1).'</font>';
}
//filter:shadow(color=blue);height:1
else if ((in_array($rs['name'],$_gm['name'])) && substr($msg, 0,1) == '@') 
{
	// sub command
	if(strtolower(trim($msg)) == "@@clear")
	{
		$_pm['mem']->del('chatMsgList');
		exit("@@Clear");
	}
	
	$msg = '<font color=red>[公告] '.substr($msg,1).'</font>';
	//$rs['nickname']='GM';
	$truename='GM';
}
else if(substr($msg, 0,2) == '//' && strlen($msg)>3)
{
	die("nabaweihu");
}


else if(substr($msg, 0,1) == '/' && strpos($msg,' ')!==false)
{
	$posChk = explode(' ', $msg,2);
	if (is_array($posChk) && count($posChk)==2)
	{
		$fromuser = ",".$truename.",";
		$getuser = str_replace('/','',$posChk[0]);
		define("MEM_BLACKLIST_KEY","db_blacklist");
		$blacklist = unserialize($_pm['mem'] -> get(MEM_BLACKLIST_KEY));
		$truename = 'm'.$truename.'m'.str_replace('/','',$posChk[0]); // m+from+'m'+to:
		$getuserSql = $_pm['mysql']->escape($getuser);
		$arr = $_pm['mysql'] -> getOneRecord("SELECT id FROM player WHERE nickname = '{$getuserSql}'");
		$msg = $posChk[1];
		if(!empty($blacklist[$arr['id']]) && strpos($fromuser,$blacklist[$arr['id']]) !== false)
		{
			die("");
		}
	} 
	$sc = 1;
}else if(substr($msg, 0,1) == '|' && strpos($msg,' ')!==false){//送礼
	$msg_key = 'chatMsgList';
	$nowMsgList = unserialize($_pm['mem']->get($msg_key));
	$arr = split('linend', $nowMsgList);
	if(count($arr)>20 ) // cear old
	{
		$arrt = array_shift($arr);
	}
	$nmsg = substr($msg,1);
	$amsg = explode(' ',$nmsg);
	$cmd = unserialize($_pm['mem'] -> get('db_welcome1'));
	if($cmd['swfemotion'] == ''){
		die('nomsg');
	}
	
	$cmdarr = explode("\r\n",$cmd['swfemotion']);
	foreach($cmdarr as $cv){
		if(strpos($cv,$amsg[1]) !== false){
			$tmsg = explode('##',$cv);
		}
	}
	
	if($tmsg[0] == ''){
		die('nomsg');
	}
	$_pm['mysql'] -> query("UPDATE userbag SET sums = sums - 1 WHERE uid = {$_SESSION['id']} AND pid = 2309 AND sums >= 1");
	$result = mysql_affected_rows($_pm['mysql'] -> getConn());
	if($result != 1){
		die("NOPROPS！");
	}
	$gword = $_SESSION['nickname'].' 对 '.$amsg[0].'说:'.$tmsg[0];
	$newstr = '<!--'.time().'-'.$_SESSION['id'].'#givegift#'.$amsg[1].'--><font color=red>[系统公告] '.$gword.'!</font>';
	
	//$msg = '<!-->'.time().'-'.$_SESSION['id'].'#givegift#'.$amsg[1].'<-->';
	$arr = split('linend', $nowMsgList);
	if(count($arr)>20 ) // cear old
	{
		$arrt = array_shift($arr);
	}
	foreach($arr as $k=>$v)
	{
		$retstr .= $v.'linend';
	}

	$retstr = $retstr.$newstr;
	$_pm['mem']->set( array('k'=>$msg_key, 'v'=>$retstr) );
	die($msg);
}

function postAnounce($server,$isSmallSpeaker,$data){	
	global $_SESSION,$server_ip_list;	
	if(strtolower($server)=='kd5.youjia.cn'||strtolower($server)=='kd7.youjia.cn'){
		$memAnother = new memoryC(array('host'=>$server_ip_list[$server],'port'=>11212));
	}else{
		$memAnother = new memoryC(array('host'=>$server_ip_list[$server],'port'=>11211));
	}
	if(!$memAnother->getHandle()){
		if($_SESSION['username']=="leinchu"){
				echo 'Mem '.$server.'=>'.$server_ip_list[$server].' connect fail!'."\r\n";
		}
		return false;
	}
	if($_SESSION['username']=="leinchu"){
		echo $server."\r\n";
	}
	$time = date("mdHis");
	$time = time();
	if(!$isSmallSpeaker){
		$msg_key = 'chatMsgListLoundSpeaker';
		$memAnother->del($msg_key);
		if ($memAnother->add( array('k'=>$msg_key, 'v'=>array(time()=>$data) ) ) != true)
		{
			$memAnother->set( array('k'=>$msg_key, 'v'=>array( time()=>$data ) ) );
		}		
	}
	
	$nmsg = preg_split("/\#\`\#/",$data,-1,PREG_SPLIT_NO_EMPTY);	
	$msg_key = 'chatMsgList';
	if ($memAnother->add( array('k'=>$msg_key, 'v'=>implode('linend',$nmsg)) ) != true)
	{
		$nowMsgList = unserialize($memAnother->get($msg_key));
		$arr = split('linend', $nowMsgList);
		if( count($arr)>20 ) // clear old
		{
			$arrt = array_shift($arr);
		}
		$arr = array_merge($arr,$nmsg);	
		$retstr =implode('linend',$arr).'linend';
	
		if(!$memAnother->set( array('k'=>$msg_key, 'v'=>$retstr) )){
			if($_SESSION['username']=="leinchu"){
				echo $server." set failed!!\r\n";
			}
		}
	}	
	$memAnother->memClose();
	$memAnother = NULL;
	return true;
}

#####################################################
// Chat message set 60s valid
// Every player key is: hash+cm:
// 
#####################################################
$msg_key = 'chatMsgList';
//$msg = htmlspecialchars($msg);
//$msg = preg_replace("/[<>]/","|",$msg);

require_once('chatSendInc.php');
echo sendToSoap($msg);
if ($_pm['mem']->add( array('k'=>$msg_key, 'v'=>$truename.': '.$msg) ) != true)
{
	$nowMsgList = unserialize($_pm['mem']->get($msg_key));
	$arr = split('linend', $nowMsgList);
	if( count($arr)>20 ) // cear old
	{
		//$arrt = array_shift($arr);
		$arr = array_slice($arr, -20, 20); 
	}
	if(($truename == 'GM' || $truename == 'wenfang') && $sc==0) $newstr = $msg;
	else 
	{
		if($sc !=1) {
			$truename = '<u>{<span>}'.$truename.' </span></u>';
			//结婚证明
			$sql="select merge from player_ext where uid = {$_SESSION['id']}";
			$arr_merge=$_pm['mysql']->getOneRecord($sql);
			if($arr_merge['merge']>0){
				$truename = $truename . '<img src="http://gm.yibaoma.shop/images/merge.gif" />';
			}
			
		}
        if ($userIsVip) $truename = $truename . '<font color=\"#FF0000\">(VIP)</font>'; // added by Zheng.Ping
		
		//if($sc !=1) $truename = '<u>{<span>}'.$truename.' </span></u>';
		
		$newstr = $truename.': '.$msg;
	}
	
	//foreach($arr as $k=>$v)
	//{
	//	$retstr .= $v.'linend';
	//}
	$retstr .= implode('linend',$arr).'linend';
	$retstr = $retstr.$newstr;

	$_pm['mem']->set( array('k'=>$msg_key, 'v'=>$retstr) ); // default ten min.
}
$_SESSION['msgtime']=time();
$_pm['mem']->set(array('k'=>$_SESSION['id'],'v'=>$rs));	
//require_once(dirname(__FILE__).'/chatMessage.php');
$_pm['mem']->memClose();

echo '1';
//##################################################
// @Notice: In here ,add save to database interface.
//##################################################
function splitStr($str)
{
	$arr=array();
	while(strlen($str)>0)
	{
		$tmp=mb_substr($str,0,1,'GBK');
		$str=str_replace(
				$tmp,
				'',
				$str
				);
		$arr[]=$tmp;
	}
	return $arr;
}
function compareMsg($msg)
{
	return false;
	$msg=splitStr($msg);	
	$len=count($msg);
	$similiarRuler=0.6;
	$similarTotal=0;

	for($i=0;$i<count($_SESSION['chatHis']);$i++)
	{
		$count = 0;
		for($j=0;$j<$len;$j++)
		{			
			if(strpos($_SESSION['chatHis'][$i],$msg[$j])!==false)
			{
				$count++;
			}
		}
		if($count/$len>=$similiarRuler)
		{
			$similarTotal++;
			//return chatHis.text[i];
		}
	}
	if($similarTotal>=3)
	{
		return $similarTotal;
	}
	return false;
}

 function getUserBagById($id,$pid,&$mysql)
{	
	$id = intval($id);
	$pid = intval($pid);
	if($pid<1 || $id<1){
		return false;
	}
	$rs = $mysql->getOneRecord("SELECT b.id as id,
									  b.uid as uid,
									  b.sums as sums,
									  b.pid as pid,
									  b.vary as vary,
									  b.psell as psell,
									  b.pstime as pstime,
									  b.petime as petime,
									  b.bsum as bsum,
									  b.psum as psum,
									  b.zbing as zbing,
									  b.zbpets as zbpets,
									  b.plus_tms_eft as plus_tmes_eft,
									  p.name as name,
									  p.varyname as varyname,
									  p.effect as effect,
									  p.requires as requires,
									  p.usages as usages,
									  p.sell as sell,
									  p.img as img,
									  p.pluseffect as pluseffect,
									  p.postion as postion,
									  p.plusflag as plusflag,
									  p.pluspid as pluspid,
									  p.plusget as plusget,
									  p.plusnum as plusnum,
									  p.series as series,
									  p.serieseffect as serieseffect,
									  p.propslock as propslock,
									  p.prestige as prestige
								 FROM userbag as b,props as p
								WHERE 
								b.pid={$pid} and
								p.id = b.pid and b.uid={$id} and b.sums>0
								ORDER BY b.id DESC limit 1");
	
	return $rs;
}

function getUserBagByIds($id,$pidarr,&$mysql)
{	
	$id = intval($id);
	foreach($pidarr as $v)
	{
		$rs[] = $mysql->getOneRecord("SELECT b.id as id,
									  b.uid as uid,
									  b.sums as sums,
									  b.pid as pid,
									  b.vary as vary,
									  b.psell as psell,
									  b.pstime as pstime,
									  b.petime as petime,
									  b.bsum as bsum,
									  b.psum as psum,
									  b.zbing as zbing,
									  b.zbpets as zbpets,
									  b.plus_tms_eft as plus_tmes_eft,
									  p.name as name,
									  p.varyname as varyname,
									  p.effect as effect,
									  p.requires as requires,
									  p.usages as usages,
									  p.sell as sell,
									  p.img as img,
									  p.pluseffect as pluseffect,
									  p.postion as postion,
									  p.plusflag as plusflag,
									  p.pluspid as pluspid,
									  p.plusget as plusget,
									  p.plusnum as plusnum,
									  p.series as series,
									  p.serieseffect as serieseffect,
									  p.propslock as propslock,
									  p.prestige as prestige
								 FROM userbag as b,props as p
								WHERE 
								b.pid={$v} and
								p.id = b.pid and b.uid={$id} and b.sums>0
								ORDER BY b.id DESC limit 1");
	}
	return $rs;
}
?>
