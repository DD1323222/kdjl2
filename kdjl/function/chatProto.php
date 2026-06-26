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
//exit();

header('Content-Type:text/html;charset=utf-8');
require_once('../config/config.game.php');
if ( !isset($_SESSION['id']) || intval($_SESSION['id']) < 0 ) exit("你没登陆.");
require_once(dirname(dirname(__FILE__)).'/kernel/memory.v1.1.php');
$rs = $_pm['user']->getUserById($_SESSION['id']);
$userIsVip = false; /* whether the user send msg is a VIP user, if he has the 口袋精灵VIP卡, he is. added by Zheng.Ping */
if($rs===FALSE ||  !empty($rs['password'])  || $rs['secid']>0 || $_REQUEST['msg']=='{'||$_REQUEST['msg']=='}') exit("你没登陆!");

/*new player not say.*/
//if ($rs['regtime']+3600>time() || $rs['money']<1000) exit();

// 封号处理：增加封号命令：@@FH玩家昵称
$fff = false;
if(strpos($_SERVER['HTTP_USER_AGENT'],'Firefox/3')!==false||strpos($_SERVER['HTTP_USER_AGENT'],'Firefox/2')!==false){
	$fff = true;
}
$msg = htmlspecialchars(($_REQUEST['msg']),ENT_QUOTES,"utf-8");
if(strlen($_REQUEST['msg'])>1&&strlen($msg)<1||$fff){
	$msg = htmlspecialchars(iconv('utf-8','utf-8',$_REQUEST['msg']),ENT_QUOTES,"utf-8");
}

$fletter = substr($msg,0,1);
$len = strlen(trim($msg) - 1);
$lletter = substr($msg,$len,1);

$arr = array(
'我日',
'fuck',
'admin',
'system',
'sb',
'TMD',
'淫',
'乳',
'陰',
'姦',
'奸',
'裸',
'骚',
'挂',
'屎',
'系统',
'管理',
'官方',
'鸡巴',
'阴茎',
'阳具',
'肛门',
'阴道',
'肉棍',
'肉棒',
'肉洞',
'阴囊',
'你妈逼',
'高潮',
'一党',
'多党',
'大法',
'大法',
'洪志',
'法轮',
'打倒',
'民运',
'六四',
'台独',
'李鹏',
'泽民',
'镕基',
'瑞环',
'锦涛',
'台独',
'台湾独立',
'藏独',
'西藏独立',
'疆独',
'新疆独立',
'小平',
'嫖',
'妈个',
'暴乱',
'家宝',
'邦国',
'庆红',
'黄菊',
'罗干',
'专制',
'赤匪',
'赤化',
'达赖',
'东北独立',
'动乱',
'独裁',
'反共',
'分裂',
'封杀',
'自联',
'共党',
'共匪',
'共狗',
'共军',
'登辉',
'蒙独',
'蒙古独立',
'民运',
'人权',
'真善忍',
'妈B',
'奶头',
'奶子',
'娘比',
'捏你鸡巴',
'捏你奶子',
'屁蛋',
'屁精',
'屁眼',
'嫖娼',
'嫖客',
'强暴',
'强奸',
'日你',
'日死',
'乳房',
'乳头',
'乳罩',
'骚B',
'骚包',
'骚货',
'骚鸡',
'骚卵',
'傻B',
'傻比',
'射精',
'手淫',
'他NND',
'他妈',
'他吗',
'他奶',
'他娘',
'温B',
'温比',
'瘟B',
'瘟比',
'下贱',
'下流',
'小B样',
'小比样',
'舔过B',
'畜生',
'一中一台',
'阴毛',
'淫荡',
'淫货',
'淫贱',
'杂种',
'早泄',
'共产党',
'造反',
'恩来',
'茱莉亚',
'血腥',
'反政',
'特码',
'退党',
'精子',
'对日强硬',
'售枪',
'妓女',
'黑社会',
'贱B',
'售假',
'反华',
'反日',
'狗卵',
'狗杂种',
'龟头',
'干你',
'大日',
'错B',
'戳B',
'戳比',
'戳那',
'操你',
'操神',
'操死',
'操王',
'册老',
'册那',
'巴子',
'比卵',
'比水',
'比样',
'鞭神',
'鞭王',
'彪精',
'彪王',
'操蛋',
'吗的',
'日妈',
'我日',
'你日',
'他日'
);
$msg =iconv('utf-8','utf-8',$msg);

for($i=0;$i<count($arr);$i++){
	$msg = str_replace(iconv('utf-8','utf-8',$arr[$i]),"*",$msg);
}
$msg =iconv('utf-8','utf-8',$msg);
$omsg=$msg ;
$num=strlen($msg);
######## added by Du Hao in 20090421########
######## 匹配{}，在聊天框显示有颜色的装备#######
$s=0;
for($i=0;$i<$num;$i++)
{
	if($s>=0 && $s<2)
	{
		if($msg[$i]=='{')
		{
			$s++;
		}elseif($msg[$i]=='}')
		{
			$s--;
		}
	}
}
if($s!='0')
{
	$msg=str_replace("{","",$msg);
	$msg=str_replace("}","",$msg);
}
if($_GET['props_id'])
{
	$props_id = str_replace("{","",$_GET['props_id']);
	$props_id = str_replace("{","",$props_id);
	$props_id = htmlspecialchars(str_replace(array(' ','	',"\n","\r"),'',$props_id));
}
$dbn  = $GLOBALS['_pm']['mysql'];

$msg =htmlspecialchars(($_REQUEST['msg']),ENT_QUOTES,"utf-8");
$msg = iconv('utf-8','utf-8',$msg);
if(empty($msg))
{
	$msg = htmlspecialchars(str_replace(array(' ','	',"\n","\r"),'',$_REQUEST['msg']));
}
$msg=substr($msg,0,30);


$sql = sprintf("select userbag.id,propscolor from props,userbag where name='$msg' and userbag.sums > 0 and userbag.uid = {$_SESSION['id']} and userbag.pid = props.id AND userbag.id = '".$props_id."'");
$res  = $dbn->getOneRecord($sql);
if($res[id]>0)
{
	switch($res['propscolor'])
	{
		case 1:
			$msg="<span style=color:#A3ABAD><b>【<a href='#' onclick=showTip3(".$res[id].",0,1,2) onmouseout=UnTip3() style=cursor:pointer;color:#A3ABAD;>$msg</a>】</b></span>";
			$color="#A3ABAD";
			break;
		case 2:
			$msg="<span style=color:#127EE1><b>【<a href='#' onclick=showTip3(".$res[id].",0,1,2) onmouseout=UnTip3() style=cursor:pointer;color:#127EE1;>$msg</a>】</b></span>";
			$color="#127EE1";
			break;
		case 3:
			$msg="<span style=color:#AD01DF><b>【<a href='#' onclick=showTip3(".$res[id].",0,1,2) onmouseout=UnTip3() style=cursor:pointer;color:#AD01DF;>$msg</a>】</b></span>";
			$color="#AD01DF";
			break;
		case 4:
			$msg="<span style=color:#279704><b>【<a href='#' onclick=showTip3(".$res[id].",0,1,2) onmouseout=UnTip3() style=cursor:pointer;color:#279704;>$msg</a>】</b></span>";
			$color="#279704";
			break;
		case 5:
			$msg="<span style=color:#EDC028><b>【<a href='#' onclick=showTip3(".$res[id].",0,1,2) onmouseout=UnTip3() style=cursor:pointer;color:#EDC028;>$msg</a>】</b></span>";
			$color="#EDC028";
			break;
		case 6:
			$msg="<span style=color:#DA6601><b>【<a href='#' onclick=showTip3(".$res[id].",0,1,2) onmouseout=UnTip3() style=cursor:pointer;color:#DA6601;>$msg</a>】</b></span>";
			$color="#DA6601";
			break;
	}
}else 
{
	exit($msg.' 不存在');
}

######################################
//if($fletter == "{" && $lletter != "}")
//{
//	$msg = $msg."}";
//}
//else if($fletter != "{" && $lletter == "}")
//{
//	$msg = "{".$msg;
//}
//
$cmdstr = substr($msg,0,2);
if (($cmdstr == 'JY' || $cmdstr == 'FH'|| $cmdstr == 'JJ' || $cmdstr == 'YZ' || $cmdstr == 'ZY' || $cmdstr == 'WF') && ($rs['nickname']=='GM'||$rs['name']=='wenfang' || $rs['name']=='mayier318' || $rs['name']=='kefu04' || $rs['name']=='tanwei2008'|| $rs['name']=='leinchu' ))
{
	//$nickname = str_replace(array("JY",'FH','JJ','YZ','ZY','WF'), '',$_REQUEST['msg'],1);
	$nickname = substr($_REQUEST['msg'],2);
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
			$msg = '@'. $nickname . ' 因为违反江湖道义，被众英雄送入思过涯思过12小时来啦！';
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
if (strlen($_REQUEST['msg'])>100 && substr($msg, 0,2) != '//' && ($rs['nickname']!='GM' && $rs['name']!='mayier318' && $rs['name']!='wenfang' && $rs['name']!='kefu04')) exit("DATATOOLONG");
if (strlen($_REQUEST['msg'])>100 && ($rs['nickname']!='GM' && $rs['name']!='mayier318' && $rs['name']!='wenfang' && $rs['name']!='kefu04')) exit("DATATOOLONG:".strlen($_REQUEST['msg']));
$truename= $rs['nickname'];

//$_olddata = @unserialize($_pm['mem']->get('ttmt_data_notice'));
//$swfData = iconv('utf-8','utf-8',"\$".$truename."`说：")."<a href=\"event:showTip3_".str_replace('"',"'",$res[id])."\"><b><font color=\"$color\">".iconv('utf-8','utf-8','【'.$omsg.'】')."</font></b></a>";

require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
$s=new socketmsg();
$s->sendMsg(iconv('utf-8','utf-8','CT|'."\$".$truename."`说：".$msg));

//$_olddata['es'] = isset($_olddata['es'])?$_olddata['es']."<br/>[系统公告]：".$swfData:$swfData;
//$_pm['mem']->set(array('k'=>'ttmt_data_notice','v'=>$_olddata));

$msg = str_ireplace('linend','',$msg);
$sc = 0;

//Format msg.
if (substr($msg, 0,2) == '!!') $msg = '<font color=blue>'.substr($msg,2).'</font>';
else if (substr($msg, 0,1) == '!') $msg = '<font color=#FF00FF>'.substr($msg,1).'</font>';
/*else if (substr($msg, 0,1) == '$' && ($rs['money']>1000))
{
$rs['money']-=1000;
$msg ='<marquee scrollamount=1 behavior=alternate scrolldelay=1 width=300 direction=up height=25><font color=#FF00FF>'.substr($msg,1).'</font></marquee>';
}*/
else if (substr($msg, 0,1) == '$' /* && ($rs['money']>1000) */) /* added by Zheng.Ping */
{
	$u_bags=getUserBagById($_SESSION['id'], 1427, $_pm['mysql']); /* 口袋精灵VIP卡:1427 */

	if ($u_bags && isset($u_bags['sums']) && $u_bags['sums'] > 0) {
		$userIsVip = true;
		$msg = '<font color="#FF0000">' . substr($msg, 1) .'</font></marquee>';
	}

	unset($u_bags);
} /* added by Zheng.Ping */
else if (substr($msg, 0,1) == '#' && ($rs['money']>10))
{
	$rs['money']-=10;
	$msg='<font color=green>'.substr($msg,1).'</font>';
}
//filter:shadow(color=blue);height:1
else if ( ($rs['nickname'] == 'GM'||$rs['name']=='kefu04' || $rs['name']=='wenfang' || $rs['name']=='mayier318' || $rs['name']=='tanwei2008'|| $rs['name']=='leinchu') && substr($msg, 0,1) == '@')
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
/*else if(substr($msg, 0,2) == '//' && strlen($msg)>3)
{
$msg = substr($msg, 2);
if(function_exists('mb_substr')){
$msg = mb_substr($msg,0,35,'utf-8');
}else{
//$msg = substr($msg,0,35);
}
$server_list = array(//顺序不能改变
"pm1.webgame.com.cn",
"pm2.webgame.com.cn",
"pm3.webgame.com.cn",
"pm4.webgame.com.cn",
"pm5.webgame.com.cn",
"pm6.webgame.com.cn",
"pm7.webgame.com.cn",
"pm8.webgame.com.cn",
"pm9.webgame.com.cn",
"pm10.webgame.com.cn",
"pmtest.webgame.com.cn"
);
$smallspeaker = false;
$host = strtoupper(substr($_SERVER['HTTP_HOST'],0,strpos($_SERVER['HTTP_HOST'],'.')));
$hostname = array(
'PM1'=>"一区",
'PM2'=>"二区",
'PM3'=>"三区",
'PM4'=>"四区",
'PM5'=>"五区",
'PM6'=>"六区",
'PM7'=>"七区",
'PM8'=>"八区",
'PM9'=>"寒江雪区",
'PM10'=>"青龙",
'PMTEST'=>"测试区"
);
$host = $hostname[$host];
if(strpos($msg,' ')!==false||strpos($msg,'　')!==false){
if(strpos($msg,' ')===false||strpos($msg,'　')!==false){
$msg = str_replace('　',' ',$msg);
}
$serverstr = substr($msg,0,strpos($msg,' '));
$serverstr = preg_split("/(,|，)/",$serverstr,-1,PREG_SPLIT_NO_EMPTY);
if(count($serverstr)>0){
$smallspeaker = true;
$tmp_server_list=array();
foreach($serverstr as $sid){
if($sid<1||$sid>count($server_list)){
$smallspeaker = false;
break;
}
if(count($tmp_server_list)==2) break;//允许三个区，除自己区外，还允许两个
$tmp_server_list[$sid-1]=$server_list[$sid-1];
}
if($smallspeaker){
//自己区
if($host==$hostname['PMTEST']){
$tmp_server_list[8]=$server_list[8];
}else{
$hostEnglish = strtoupper(substr($_SERVER['HTTP_HOST'],0,strpos($_SERVER['HTTP_HOST'],'.')));
$sid = intval(str_replace('PM','',$hostEnglish));
$tmp_server_list[$sid-1]=$server_list[$sid-1];
}
$server_list = $tmp_server_list;
$msg=substr($msg,strpos($msg,' '));
}
}
}
//$_pm['mysql']->query("SET autocommit=0");
//$_pm['mysql']->query("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
$_pm['mysql']->query("START TRANSACTION");
if($smallspeaker){
$bags=getUserBagById(intval($_SESSION['id']),1295,&$_pm['mysql']);
}else{
$bags=getUserBagById(intval($_SESSION['id']),1319,&$_pm['mysql']);
}
//喇叭卷轴
if($bags&&$bags['sums']>0)
{
$sql="update userbag set sums=sums-1 where id=".intval($bags['id']).' and uid='.intval($_SESSION['id']).' limit 1';
$_pm['mysql']->query($sql);
if($bags['sums']==1){
$sql="detele from userbag where sums=0 and id=".intval($bags['id']).' and uid='.intval($_SESSION['id']).' limit 1';
$_pm['mysql']->query($sql);
}
if (!$_pm['mysql']->query("COMMIT")){
$_pm['mysql']->query("ROLLBACK");
}
if($smallspeaker){
$memKey = 'UserSpeakInAllServersSmall';
}else{
$memKey = 'UserSpeakInAllServers';
}
$dataMem=unserialize($_pm['mem']->get($memKey));//取出原来没有发送出去的
if(!is_array($dataMem)){
$dataMem=array();
}
$connector = '#`#';
if($smallspeaker){
$msg='[<font color="#B48D03">'.$host.'</font>] '.$truename.'(<font color="#B48D03">小喇叭</font>)：<font color="#33AA33"><b>'.str_replace('#`#','#.#',substr($msg,1)).'</b></font>';
}else{
if($rs['name']=='wenfang' || $rs['name']=='mayier318' || $rs['name']=='kefu04' || $rs['name']=='leinchu'){
$msg='<font color="#B48D03">公告</font>：<font color="#ff0000"><b>'.str_replace('#`#','#.#',$msg).'</b></font>';
}else{
$msg='[<font color="#B48D03">'.$host.'</font>] '.$truename.'(<font color="#B48D03">大喇叭</font>)：<font color="#ff0000"><b>'.str_replace('#`#','#.#',$msg).'</b></font>';
}
}
foreach($server_list as $k=>$v){
if(isset($dataMem[$k])){
$dataMem[$k].=$connector.$msg;
}else{
$dataMem[$k]=$msg;
}
}
//$recv_file = "/function/anounce.php";
$_pm['mem']->set(array("k"=>$memKey,"v"=>$dataMem));//放入内存
$data=unserialize($_pm['mem']->get($memKey));
$newData =array();
if(is_array($data)){
foreach($data as $k=>$v){//逐个发送
if(!($rslt=postAnounce($server_list[$k],$smallspeaker,$v))){//发送失败的保存
if(isset($newData[$k])){
$newData[$k].=$v;
}else{
$newData[$k]=$v;
}
if($_SESSION['username']=="leinchu"){
echo '<b>'.__FILE__.'-->'.__LINE__.'</b><br/><pre>$rslt=';
var_dump($rslt	);
echo '</pre>';
}
}
}
}
//if(!empty($newData)){//保存
$_pm['mem']->set(array("k"=>$memKey,"v"=>$newData));//放入内存
//}
require_once(dirname(__FILE__).'/chatMessage.php');
exit("BROADCASTDONE");
}else{
exit("NOBROADCAST");
}
}
*/
else if(substr($msg, 0,1) == '/' && strpos($msg,' ')!==false)
{
	$posChk = explode(' ', $msg,2);
	if (is_array($posChk) && count($posChk)==2)
	{
		$truename = 'm'.$truename.'m'.str_replace('/','',$posChk[0]); // m+from+'m'+to:
		$msg = $posChk[1];
	}
	$sc = 1;
}

function postAnounce($server,$isSmallSpeaker,$data){
	/**/
	global $_SESSION;
	if(strtolower($server)=="pmtest.webgame.com.cn"){
		$memAnother = new memoryC(array('host'=>$server,'port'=>11212));
	}else{
		$memAnother = new memoryC(array('host'=>$server,'port'=>11211));
	}
	if(!$memAnother->getHandle()){
		if($_SESSION['username']=="leinchu"){
			echo 'Mem connect fail!<hr>';
		}
		return false;
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
	//$memAnother->set( array('k'=>$msg_key, 'v'=>$nowMsgList) ); // default ten min.

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
		//$retstr = $retstr.$newstr;
		$memAnother->set( array('k'=>$msg_key, 'v'=>$retstr) ); // default ten min.
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
		if ($userIsVip) $truename = $truename . '<font color=\"#FF0000\">(VIP)</font>'; // added by Zheng.Ping
		if($sc !=1) $truename = '<u>{<span>}'.$truename.' </span></u>';

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
?>