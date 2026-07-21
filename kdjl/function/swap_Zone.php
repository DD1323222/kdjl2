<?php
//ini_set('display_errors','on');
//error_reporting(E_ALL);
ob_start();

$prizeLimitLevel = 40;
$prizeForSwap = array(
	'1444'=>10,
	'1443'=>10
);
$pwdSec = 'sd212228(*';
$pwd = md5($pwdSec);
$block = "~`'`~";
$vbcrlf = "\r\n";
define('ERROR_AUTH','ERR_AUTH');
define('ERROR_SECID_STATUS','ERROR_USER_ID');
define('HTTP_CONTENT_STARTED','<!--~HTTP_CONTENTS_STARTED~-->');

if(isset($_GET['qid']) && !is_array($_GET['qid']) && isset($_GET['p']) && isset($_GET['sid']) && !is_array($_GET['p']) && !is_array($_GET['sid']) && intval($_GET['qid'])>0 && $_GET['p']==$pwd && preg_match('/^[a-f0-9]{32}$/i', $_GET['sid']))
{
	//为安全检查做准备，保证要转区的用户已经登陆
	session_id($_GET['sid']);
}
ob_end_clean();
if(!headers_sent())

require_once('../config/config.game.php');

$swapDebug = kdjlCurrentUserIsAdmin();
$currentHttpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
if(!preg_match('/^[A-Za-z0-9.-]{1,255}(:[0-9]{1,5})?$/', $currentHttpHost)) $currentHttpHost = '';
$currentHttpHostLower = strtolower($currentHttpHost);

//INSERT INTO `timeconfig` (`Id`, `titles`, `days`, `starttime`, `endtime`) VALUES (NULL, 'swapzone', 0, '20090501', '20090601');
$prizeEndAt = '';
$swapRenamePendingLogId = 0;
$swapRenameCommitted = false;
function swapRenameShutdown()
{
	global $conn,$swapRenamePendingLogId,$swapRenameCommitted;
	if(!$swapRenameCommitted && $swapRenamePendingLogId > 0 && is_resource($conn))
	{
		mysql_query("DELETE FROM gamelog WHERE id=".intval($swapRenamePendingLogId)." AND vary=13 AND pnote='ChangeName'", $conn);
		$swapRenamePendingLogId = 0;
	}
}
register_shutdown_function('swapRenameShutdown');

connect();
$swapZoneSetting = query('select starttime,endtime from timeconfig where titles= "swapzone"');
$swapZoneSetting = $swapZoneSetting[0];
$today = date("Ymd");
$todayhour = date("YmdH");
$swapZoneStarted = false;//本区开启转区
$swapZoneEndTime = 0;
$fromZone = array(//转区的区
);

$aimZone = array(//目标区
);
if(is_array($swapZoneSetting))
{
	$swapZoneFrom = query('select titles,endtime from timeconfig where starttime= "SWAP_FROM"');
	$fromZone = getSwapZoneSetting($swapZoneFrom[0]['titles']);

	$swapZoneTo = query('select titles,endtime from timeconfig where starttime= "SWAP_TO"');
	$aimZone = getSwapZoneSetting($swapZoneTo[0]['titles']);

	$swapZonePrize = query('select titles,endtime from timeconfig where starttime= "swapzonePrize"');
	$prizeForSwap = getSwapZoneSetting($swapZonePrize[0]['titles']);
	/*if($_SESSION['username']!="tanwei2008"){
		$swapZoneSetting['starttime'] = '20100603';
	}*/
	if($swapZoneSetting['starttime']<=$today || ( strlen($swapZoneSetting['starttime'])==10&&$swapZoneSetting['starttime']<=$todayhour ) )
	{
		$swapZoneStarted = true;//本区开启转区
	}
	$swapZoneEndTime = $swapZoneSetting['endtime'];
}
else
{
	query('alter table timeconfig change titles titles varchar(255) Null default ""');
	query("INSERT INTO `timeconfig` (`Id`, `titles`, `days`, `starttime`, `endtime`) VALUES (NULL, 'swapzone', 0, '21090501', '20190502')");
	query("INSERT INTO `timeconfig` (`Id`, `titles`, `days`, `starttime`, `endtime`) VALUES (NULL, '1308,20;1225,20;1142,5', 0, 'swapzonePrize', 'swapzonePrize')");
	query("
		INSERT INTO `timeconfig`
		(`Id`, `titles`, `days`, `starttime`, `endtime`)
		VALUES
		(NULL, 'pm1.webgame.com.cn,一区;pmtest.webgame.com.cn,测试一区', 0, 'SWAP_FROM', 'SWAP_FROM')");
	query("
		INSERT INTO `timeconfig`
		(`Id`, `titles`, `days`, `starttime`, `endtime`)
		VALUES
		(NULL, 'pmtest2.webgame.com.cn,测试二区;pm2.webgame.com.cn,二区;pm3.webgame.com.cn,三区;pm4.webgame.com.cn,四区;pm5.webgame.com.cn,五区', 0, 'SWAP_TO', 'SWAP_TO')");
	echo '<!-- swap zone timeconfig not found, it was inited! -->';
}

if($swapDebug && isset($_GET['dbg'])){
	echo '<!-- swap zone debug output disabled -->';
}

function swapZoneHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES);
}

function swapZoneHostOk($host)
{
	return preg_match('/^[A-Za-z0-9.-]{1,255}(:[0-9]{1,5})?$/', (string)$host);
}

/********************************************************************************************************************/
/*********************************           以下是函数程序部分              ****************************************/
/********************************************************************************************************************/
function changeNamePriv(){
	global $changeNamePriv,$conn;
	$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
	$username = mysql_real_escape_string($username, $conn);
	$rs = query('select yb from yblog where nickname="'.$username.'" limit 1');
	$changeNamePriv = false;
	if(!empty($rs))
	{
		$changeNamePriv = true;
	}
}


function getSwapZoneSetting($str){
	$strs = explode(";",$str);
	$tmp = array();
	foreach($strs as $str)
	{
		$t = explode(',',$str,2);
		if(count($t)==2)
		{
			$tmp[$t[0]]=$t[1];
		}
	}
	return $tmp;
}





function socketData($host,$url1,$flag=false){
	$port = 80;
	$url = 'http://'.$host.$url1;
	$post = 1;
	$returntransfer = 1;
	$header = 0;
	$nobody = 0;
	$followlocation = 1;
	$request = '';
	$cookie_jar = tempnam(sys_get_temp_dir(), 'kdjl_cookie_');

	$ch = curl_init();
	$options = array(CURLOPT_URL => $url,
		CURLOPT_HEADER => $header,
		CURLOPT_NOBODY => $nobody,
		CURLOPT_PORT => $port,
		CURLOPT_POST => $post,
		CURLOPT_POSTFIELDS => $request,
		CURLOPT_RETURNTRANSFER => $returntransfer,
		CURLOPT_FOLLOWLOCATION => $followlocation,
		CURLOPT_COOKIEJAR => $cookie_jar,
		CURLOPT_COOKIEFILE => $cookie_jar,
		CURLOPT_REFERER => $url,
		CURLOPT_CONNECTTIMEOUT => 2,
		CURLOPT_TIMEOUT => 3
	);
	curl_setopt_array($ch, $options);
	$result = curl_exec($ch);
	curl_close($ch);
	if($cookie_jar && file_exists($cookie_jar)) @unlink($cookie_jar);
	return $result;
}
function connect(){
	global $conn,$_mysql;
	$conn = mysql_connect($_mysql['host'],$_mysql['user'],$_mysql['pass']);
	if(!$conn) die('转区数据库连接失败！');
	if(!mysql_select_db($_mysql['db'],$conn)) die('转区数据库连接失败！');
	$row = mysql_fetch_row(mysql_query('show tables',$conn));
	$row = mysql_fetch_row(mysql_query('show create table '.$row[0],$conn));

	if(strpos(strtolower($row[1]),"charset=gbk")!==false){
		mysql_query("SET NAMES GBK;",$conn);
		mysql_query("SET CHARACTER_SET_CLIENT=GBK;",$conn);
		mysql_query("SET CHARACTER_SET_RESULTS=GBK;",$conn);
	}else{
		mysql_query("SET NAMES latin1;",$conn);
	}
}
function query($sql,$flag=false){
	global $conn;
	if($flag)	echo 'query|'.__FILE__.'->'.__LINE__."<hr>\n";
	$result = mysql_query($sql,$conn);
	if(!$result) die('转区数据读取失败！');
	$rtn = array();
	if(is_resource($result)){
		while ($row = mysql_fetch_assoc($result)) {
			$rtn[]=$row;
		}
	}
	if($flag)	echo __FILE__.'->'.__LINE__."<hr>\n";
	@mysql_free_result($result);
	if($flag)	echo __FILE__.'->'.__LINE__."<hr>\n";
	return $rtn;
}
function getPrikey($name)
{
	$cols = query('show columns from  `'.$name.'`');
	$primaryKey = array();
	foreach($cols as $col)
	{
		if($col['Key']=='PRI')
		{
			$primaryKey[]=$col['Field'];
		}
	}
	return $primaryKey;
}
function formatData($data,$name)
{
	$return = $GLOBALS['block'].$name.$GLOBALS['vbcrlf'];
	$primaryKey = getPrikey($name);

	foreach($data as $v)
	{
		$con = "";
		foreach($v as $k=>$vv)
		{
			if(
				(!in_array($k,$primaryKey) || $name=='userbb')
				||
				(in_array($k,$primaryKey) && $name=='player_ext' && $k=='uid')
			)//userbb必须要保留主键才能正确对应技能
			{
				$return .= $con.'`'.$k.'`="'.str_replace('"','\"',$vv).'"';
				$con = ",";
			}
		}
		$return .= $GLOBALS['vbcrlf'];
	}
	return $return;
}

function divideData($data)
{
	$tmp = array();
	$data = explode('",`',$data);
	foreach($data as $d)
	{
		$t = explode('`="',$d);
		if(count($t) < 2) continue;
		$t[0] = str_replace("`","",$t[0]);
		$tmp[$t[0]] = $t[1];
	}
	return $tmp;
}
function replaceUid($data,$uid,$field='uid')
{
	foreach($data as $k=>$v)
	{
		$data[$k] = preg_replace("/`".$field."`(=\"\d+\")/i","`".$field."`=\"".$uid."\"",$v);
	}
	return $data;
}
function replaceFiled($data,$uid,$field='uid')
{
	foreach($data as $k=>$v)
	{
		$data[$k] = preg_replace("/`".$field."`(=\"[^\"]+\")/i","`".$field."`=\"".$uid."\"",$v);
	}
	return $data;
}
function getBbOldId($data,$field = 'id')
{
	if(preg_match("/`".$field."`=\"\d+\",/",$data,$out))
	{
		$data = array(substr($out[0],strlen($field)+4,-2),str_replace($out[0],"",$data));
	}

	return $data;
}
function cancelSwapZone($userid,$cancel_pwd,$host){
	//echo 'http://'.$host.'/function/swap_Zone.php?id='.$userid.'&p='.$cancel_pwd.'&cancelSwap=yes'."<br/>";
	//return socketData($host,'function/swap_Zone.php?id='.$userid.'&p='.$cancel_pwd.'&cancelSwap=yes');
	return curlSS('http://'.$host.'/function/swap_Zone.php?id='.$userid.'&p='.$cancel_pwd.'&cancelSwap=yes');
}
function getWordCharInt($str)
{
	$stro=$str;
	if(strpos($str,'　') !== false){
		return false;
	}
	$str=preg_replace("/\w/","",$str);
	if(
		preg_match("/[\`~\!@#$%\^&\*\(\)_+\|\=-\{\}\[\];'\:\"<>\?,\.\/]/",$str) || preg_match("/\s/", $str)
	)
	{
		return false;
	}

	$str = $stro;

	$list = array('{','}','gm','日','客服','法轮功','胡锦涛','妈','搞','\?','<','>','管理','系统','公告','颁奖','元宝','出售','提示','kefu','代练','共产党','国民党','商务','5173','销售','淘宝','江泽民','毛泽东');

	foreach($list as $v)
	{
		$reg = '/'.$v.'/i';
		if(preg_match($reg,$str)){
			return false;
		}
	}
	return true;
}
function saveData($data,$userid,$server)
{
	global $conn,$swapDebug;
	$nUid = NULL;

	$sql = array();
	$cancel_pwd = false;

	foreach($data as $d)
	{
		if(!$cancel_pwd)
		{
			$cancel_pwd=$d;
			continue;
		}
		$tmp = preg_split("/".$GLOBALS['vbcrlf']."/",$d,0,PREG_SPLIT_NO_EMPTY);
		$primaryKey[$tmp[0]] = getPrikey($tmp[0]);
		for($i=1;$i<count($tmp);$i++)
		{
			$sql[$tmp[0]][] = $tmp[$i];
		}
		if(!isset($sql[$tmp[0]]))
			$sql[$tmp[0]] = array();
	}

	//die();
	if(!isset($sql['player'])||!isset($sql['userbag'])||!isset($sql['userbb'])||!isset($sql['skill'])||!isset($sql['tasklog'])||!isset($sql['player_ext']))
	{
		die("接收到的数据库不完整（缺少必要数据）！");
	}

	//player
	$sqlPlayer = $sql['player'][0];
	query('START TRANSACTION');
	$player = divideData($sqlPlayer);
	$existsUser = false;

	$bbHostNewName = false;
	if(!empty($player['name'])){
		$playerNameSql = mysql_real_escape_string($player['name'], $conn);
		$playerNicknameSql = mysql_real_escape_string($player['nickname'], $conn);
		$existsUser = query('select id,name,yb,nickname,openmap,money,maxbag,maxbase,vip from player where name="'.$playerNameSql.'"');
		$nickLocal = query('select name,nickname from player where nickname like binary "'.$playerNicknameSql.'"');

		if(!empty($nickLocal)&&$nickLocal[0]['name']!=$player['name'])
		{
				if(!isset($_POST['bname']) || is_array($_POST['bname']))
			{
				$GLOBALS['srMsg'] = '角色名已经存在，请修改你的角色名！';
				$c = cancelSwapZone($userid,$cancel_pwd,$server);
				return false;
			}
			$bname = trim((string)$_POST['bname']);
			$bnameSql = mysql_real_escape_string($bname, $conn);
			if(!getWordCharInt($bname)){
				$GLOBALS['srMsg'] = "角色名有禁止的字符！";
				$c = cancelSwapZone($userid,$cancel_pwd,$server);
				return false;
			}
			if(strlen($bname)<3||strlen($bname)>13){
				$GLOBALS['srMsg'] = "角色名长度错误！";
				$c = cancelSwapZone($userid,$cancel_pwd,$server);
				return false;
			}
			$nickLocalNew = query('select name,nickname from player where nickname like binary "'.$bnameSql.'"');
			if(!empty($nickLocalNew))
			{
				$GLOBALS['srMsg'] = '角色名已经被其他人抢先使用，请修改你的角色名！';
				$c = cancelSwapZone($userid,$cancel_pwd,$server);
				return false;
			}
			$sqlPlayer = str_replace('`nickname`="'.$player['nickname'].'"','`nickname`="'.$bnameSql.'"',$sqlPlayer);
			$bbHostNewName = array('`username`="'.$player['nickname'].'"','`username`="'.$bnameSql.'"');
		}
	}

	if($existsUser)
	{
		$existsNameSql = mysql_real_escape_string($existsUser[0]['name'], $conn);
		$importNameSql = mysql_real_escape_string($player['name'], $conn);
		$existsLogSql = mysql_real_escape_string('用户转区过来修改本区用户：'.print_r($existsUser,1), $conn);
		$sqlLog = '
			insert
			into
			gamelog
			(ptime,	seller,	buyer,	pnote,	vary)
			values
			(unix_timestamp(),"'.$existsNameSql.'","'.$importNameSql.'","'.$existsLogSql.'",13)
			';

		query($sqlLog);

		$existsUser = $existsUser[0];
		$keyCurUser  = $existsUser['id'] . "chat";
		//query('update player set nickname="ohno" where id="'.$existsUser['id'].'"');
		query('update player set name=md5(name),nickname="SwapZoneRpd" where id="'.$existsUser['id'].'"');
		//mysql_query('delete from player where id="'.$existsUser['id'].'"',$conn);
		//query('delete from tasklog where uid="'.$existsUser['id'].'"');
		$bb = query('select id from userbb where uid="'.$existsUser['id'].'"');
		$_bb = array();
		foreach($bb as $b)
		{
			$_bb[]=$b['id'];
		}
		//query('delete from userbb where id in ("'.implode('","',$_bb).'")');
		//query('delete from userbag where uid="'.$existsUser['id'].'"');
		//query('delete from skill where bid in ("'.implode('","',$_bb).'")');
	}
	//$sqlPlayer=str_replace("name`='","name`='A",$sqlPlayer);
	query('insert into player set '.$sqlPlayer);
	//echo 'insert into player set '.$sqlPlayer." ".__FILE__.'->'.__LINE__."<hr>";

	$nUid = mysql_insert_id($conn);
	if($nUid<1){
		//query('delete from player where name="'.$player['name'].'"');
		//query('ROLLBACK');
		$c = cancelSwapZone($userid,$cancel_pwd,$server);
		die("创建转区角色失败(03)。");
	}
	//echo '$nUid='.$nUid."<br/>\n";
	//userbb
	$sqlBb = $sql['userbb'];
	$sqlBb = replaceUid($sqlBb,$nUid);
	$bbId = array();
	//$nBBid = 111;
	//player_ext
	$chouquValue = '';
	if(isset($sql['player_ext'][0]))
	{
		$chouqu = explode('chouqu_chongwu`="',$sql['player_ext'][0]);
		if(isset($chouqu[1]))
		{
			$chouqu = explode('"',$chouqu[1]);
			$chouquValue = isset($chouqu[0]) ? $chouqu[0] : '';
		}
	}
	$sqlplayer_ext = $sql['player_ext'];
	$sqlplayer_ext = replaceUid($sqlplayer_ext,$nUid);

	foreach($sqlplayer_ext as $s)
	{
		if(strlen($s)<3) continue;
		query('insert into player_ext set '.$s.'');
	}
	query(" UPDATE player_ext SET chouqu_chongwu = '' WHERE uid = {$nUid} ");//clear chourqu
	$prizeLimitLevelFlag = false;//奖品领取等级限制
	foreach($sqlBb as $s)
	{
		if(strlen($s)<5) continue;
		$d = getBbOldId($s);
		$oldBbId = isset($d[0]) ? intval($d[0]) : 0;
		$can_chouqu = ($oldBbId > 0 && strpos(','.$chouquValue.',', ','.$oldBbId.',') !== false);
		if($bbHostNewName)
		{
			$d[1] = str_replace($bbHostNewName[0],$bbHostNewName[1],$d[1]);
		}

		query('insert into userbb set '.$d[1].'');
		$nBBid = mysql_insert_id($conn);//10hour
		if($can_chouqu)
		{
			$bid = $nBBid;
			$ext_type = query(" SELECT * FROM player_ext WHERE uid = {$nUid} ");
			$update_chouqu_data = $ext_type[0]['chouqu_chongwu'];
			$update_chouqu_data .= ','.$bid.',';
			query(" UPDATE player_ext SET chouqu_chongwu = '".$update_chouqu_data."' WHERE uid = {$nUid} ");
			$can_chouqu =false;
		}
		$bbDataCur = divideData($d[1]);

		if(isset($bbDataCur['level'])&&intval($bbDataCur['level'])>=$GLOBALS['prizeLimitLevel'])
		{
			$prizeLimitLevelFlag = true;
		}
		if($swapDebug && isset($_REQUEST['dbg1'])){
			echo "<script>console.log($nBBid);</script>";
			//die($d[0]);
		}
		$bbId[$d[0]] = $nBBid;
	}
	//skill
	$sqlSkill = $sql['skill'];
	$flag=true;
	$bbIdReplaceSetting = array();
	foreach($sqlSkill as $s)
	{
		if(strlen($s)<5) continue;
		if(preg_match("/`bid`=\"(\d+)\"/",$s,$out))
		{
			if(isset($bbId[$out[1]]))
			{
				$s=str_replace($out[0],"`bid`=\"".$bbId[$out[1]]."\"",$s);
				$bbIdReplaceSetting[preg_replace("/[^\d]/","",$out[0])] = $bbId[$out[1]];
				query('insert into skill set '.$s.'');
			}
			else
			{
				//echo "A \n";
				$flag=false;
			}
		}else{
			//echo "B \n".strlen($s).'-'.$s."<br/>\n";
			$flag=false;
		}

		if(!$flag)
		{
			query('ROLLBACK');
			$c = cancelSwapZone($userid,$cancel_pwd,$server);
			die('保存转区技能失败(04)。');
		}
	}

	//userbag
	$sqlBag = $sql['userbag'];
	$sqlBag = replaceUid($sqlBag,$nUid);
	foreach($sqlBag as $s)
	{
		if($swapDebug && isset($_REQUEST['dbg1'])){
			echo "<script>console.log(11031)</script>";
			echo "<script>console.log('$s');</script>";
			echo $s;
		}
		if(strlen($s)<5) continue;
		//echo $s."<br\>";
		foreach($bbIdReplaceSetting as $k=>$v)
		{
			$f='`zbpets`="'.$k.'"';
			$r='`zbpets`="'.$v.'"';
			$s = str_replace($f,$r,$s);
		}
		//echo $s."<br\>";
		query('insert into userbag set '.$s.'');
		//echo 'insert into userbag set '.$s.''."<br/>\n";
		if($swapDebug && isset($_REQUEST['dbg1'])){
			echo "<script>console.log('$s');</script>";
			echo $s;
		}

	}

	//tasklog
	$sqlSkill = $sql['tasklog'];
	$sqlSkill = replaceUid($sqlSkill,$nUid);
	foreach($sqlSkill as $s)
	{
		if(strlen($s)<3) continue;
		query('insert into tasklog set '.$s.'');
	}



	//give prize
	if($GLOBALS['swapZoneEndTime']>=$GLOBALS['today']&&$prizeLimitLevelFlag)
	{
		foreach($GLOBALS['prizeForSwap'] as $pid=>$v){
			$sql = 'INSERT INTO `userbag`
				(`id`,`pid`,`uid`, `sell`, `vary`, `sums`, `stime`, `psell`, `pstime`, `petime`, `psum`, `bsum`, `zbing`, `zbpets`, `buycode`, `plus_tms_eft`) VALUES
				(NULL, '.$pid.','.$nUid.',0, 1, '.$v.', unix_timestamp(), 0, NULL, 0, 0, 0, 0, NULL, 0, NULL)';
			query($sql);
		}
	}
	query('COMMIT');
	if(mysql_error($conn))
	{
		query('ROLLBACK');
		$c = cancelSwapZone($userid,$cancel_pwd,$server);
		die('保存转区背包失败(05)。');
		return false;
	}else{
		if(isset($keyCurUser)) //把本区帐号踢下线
			$GLOBALS['_pm']['mem']->del($keyCurUser);
		return true;
	}
}
/********************************************************************************************************************/
/*********************************         以下是流程控制程序部分            ****************************************/
/********************************************************************************************************************/

//判断功能是否开放
$swapZonePhpSelf = (isset($_SERVER['PHP_SELF']) && !is_array($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : '';
if(!$swapZoneStarted)
{
	if(strpos($swapZonePhpSelf,basename(__FILE__))!==false){
		die("未开放功能！");
	}
}
//改名
if(isset($_GET['changename']) && !is_array($_GET['changename']) && isset($_POST['nName']) && !is_array($_POST['nName']) && $swapZoneStarted)
{
	$name = substr(str_replace(array(' ','	','"',"'",';',',',"\\"),"",$_POST['nName']),0,30);
	$sessionUsername = isset($_SESSION['username']) ? $_SESSION['username'] : '';
	$sessionUserId = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
	$msg = "操作失败";
	$flag = false;
	if($name==$sessionUsername)
	{
		$msg = "改的登陆名和原来的相同！";
	}else{
		//if(preg_match("/^[a-zA-Z]{1}([a-zA-Z0-9]|[_]){3,19}$/",$_POST['nName'])){
		if(strlen($name)>3&&strlen($name)<15)
		{
			connect();
			$nameSql = mysql_real_escape_string($name, $conn);
			$sessionUsernameSql = mysql_real_escape_string($sessionUsername, $conn);
			$rs = query('select * from player where name="'.$nameSql.'"');
			if(empty($rs)){
				$rs = query('select
					date_format(from_unixtime(ptime),"%Y/%m/%d %H:%i") ptime
					from gamelog
					where
					(buyer="'.$sessionUsernameSql.'" or seller="'.$sessionUsernameSql.'" )
					and pnote="ChangeName" and vary=13');
				if(empty($rs)){
					query('START TRANSACTION');
					query('update player set name="'.$nameSql.'" where name="'.$sessionUsernameSql.'" and id="'.$sessionUserId.'"');
					if(mysql_affected_rows($conn) != 1)
					{
						query('ROLLBACK');
						$msg = "角色状态已变化，请重新登陆后再试！";
					}
					else
					{
						$sqlLog = '
						insert
						into
						gamelog
						(ptime,	seller,	buyer,	pnote,	vary)
						values
						(unix_timestamp(),"'.$sessionUsernameSql.'","'.$nameSql.'","ChangeName",13)
						';

						query($sqlLog);
						$swapRenamePendingLogId = intval(mysql_insert_id($conn));
						if($swapRenamePendingLogId > 0 && !mysql_error($conn)){
							query('COMMIT');
							$swapRenameCommitted = true;
							$msg = "操作成功！\\n请关闭浏览器重新登陆！";
							$flag = true;
							unset($_SESSION['username']);
							unset($_SESSION['id']);
							unset($_SESSION['licenseid']);
							unset($_SESSION);
							$sessionCookieValue = (isset($_COOKIE['PHPSESSID']) && !is_array($_COOKIE['PHPSESSID'])) ? $_COOKIE['PHPSESSID'] : '';
							if(preg_match('/^[A-Za-z0-9,-]{1,128}$/', $sessionCookieValue)){
								$crc = crc32($sessionCookieValue);
								$_pm['mem']->del($crc);
							}
						}else{
							query('ROLLBACK');
						}
					}
				}else{
					$msg = "您已经于".(isset($rs[0]['ptime']) ? $rs[0]['ptime'] : '')."改过用户名！";
				}
			}else{
				$msg = "输入的登陆名本区已经存在角色！";
			}
		}else{
			$msg = "输入的登陆名不合法！";
		}
	}
	$msg = addcslashes((string)$msg, "\\\"\n\r");
	die('
		<script language="javascript">
alert("'.$msg.'");
'.($flag?'parent.changeNameTableDh();top.close();top.location="/login/login.php?rand='.time().'";':'').'
	</script>
	');
}

//在登陆页面显示转区的区列表
else if(isset($displaySwapZone)&&$displaySwapZone===true&&array_key_exists($currentHttpHostLower,$fromZone)&&$swapZoneStarted)
{
	changeNamePriv();
	$_box="";
	$echo='	<fieldset style="width:385px">	<legend>将本区帐号转区到：</legend>';
	if($changeNamePriv){
		$echo .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" id="changeNameTable" STYLE="display:none"><tr><td style="padding-left:6px;font-size:12px">	<form style="padding:0px;margin:0px" target="chgNameIfr" method="post" action="/function/swap_Zone.php?changename=1" id="chgNameForm">若想保留将转入区服中已有的角色，步骤如下：<br/>1.	新申请注册一个<font color=red>通行证账号</font>，确保其在任何区服都没有角色<br/>2.	在下方的空缺处填入新<font color=red>通行证账号</font>，将角色转移到新账号中<br/>3.	登陆新帐号，并在转服页面上选择将要转入的服务器<br/>将角色转移到：<input type="text" size=12 name="nName" id="nName"><input type="button" value=" 确定 " onclick="changName()">&nbsp;&nbsp;<input type="button" value=" 取消 " onclick="changeNameTableDh()"><br/>注：您只有一次修改登陆名的机会！务必确认您填写是正确的！<br />登陆名不能包含空白、单引号、双引号、分号、逗号、斜杠（'.'\\\\'.'）等特殊符号。</form><iframe style="display:none" src="about:blank" name="chgNameIfr"></iframe></td></tr></table>';
		$_box="
<style>
/* Z-index of #mask must lower than #boxes .window */
#mask {
  position:absolute;
  z-index:9000;
  background-color:#000;
  display:none;
}
#boxes .window {
  position:absolute;
  width:440px;
  height:200px;
  display:none;
  z-index:9999;
  padding:20px;
  background-color:#eee;
}
/* Customize your modal window here, you can add background image too */
#boxes #dialog {
  width:375px;
  height:203px;
}
</style>

<script type='text/javascript' src='http://ajax.aspnetcdn.com/ajax/jquery/jquery-1.5.1.min.js'></script>
			<script type='text/javascript'>
$(document).ready(function() {
    //select all the a tag with name equal to modal

    //if close button is clicked
    $('.window .close').click(function (e) {
        //Cancel the link behavior
        e.preventDefault();
        $('#mask, .window').hide();
    });



});
			</script>
<div id='boxes'>
    <div id='dialog' class='window'>
	<blockquote>
现在需要在空白栏中填入一个<font color='red'>新的通行证账号</font>，输入完成后点击确定。（操作完毕后会自动关闭浏览器）
<br/>然后，使用刚才输入的<font color='red'>新通行证账号</font>登陆本区，直接点击“转入指定大区”即可完成转区程序
	</blockqupte>
        <a href='#' class='close'>关闭</a>
    </div>
    <div id='mask'></div>
</div>

			";
	}else{
		$_box="
<style>
/* Z-index of #mask must lower than #boxes .window */
#mask {
  position:absolute;
  z-index:9000;
  background-color:#000;
  display:none;
}
#boxes .window {
  position:absolute;
  width:440px;
  height:200px;
  display:none;
  z-index:9999;
  padding:20px;
  background-color:#eee;
}
/* Customize your modal window here, you can add background image too */
#boxes #dialog {
  width:375px;
  height:203px;
}
</style>

<script type='text/javascript' src='http://ajax.aspnetcdn.com/ajax/jquery/jquery-1.5.1.min.js'></script>
			<script type='text/javascript'>
$(document).ready(function() {
    //select all the a tag with name equal to modal
    (function() {
        var id = '#dialog';
        //Get the screen height and width
        var maskHeight = $(document).height();
        var maskWidth = $(window).width();
        //Set height and width to mask to fill up the whole screen
        $('#mask').css({'left':0,'top':0,'width':maskWidth,'height':maskHeight});
        //transition effect
        $('#mask').fadeIn(1000);
        $('#mask').fadeTo('slow',0.8);
        //Get the window height and width
        var winH = $(window).height();
        var winW = $(window).width();
        //Set the popup window to center
        $(id).css('top',  winH/2-$(id).height()/2);
        $(id).css('left', winW/2-$(id).width()/2);
        //transition effect
        $(id).fadeIn(2000);
    })();
    //if close button is clicked
    $('.window .close').click(function (e) {
        //Cancel the link behavior
        e.preventDefault();
        $('#mask, .window').hide();
    });
});
			</script>
<div id='boxes'>
    <div id='dialog' class='window'>
	<p>点击蓝色文字“转入指定大区”即可直接进入转区程序</p>
	<p>如需保留角色，点击方框内<font color='red'>保留角色</font>后即可进入保留角色程序<font color='red'>(</font>仅有消费记录的玩家可见<font color='red'>)</font></p>
        <a href='#' class='close'>关闭</a>
    </div>
    <div id='mask'></div>
</div>

			";

	}
	$echo .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" id="swapZoneTable">';
	if($changeNamePriv){
		$echo .= '<tr><td><input type="button" value=" 保留角色 " onclick="changeNameTableDh()"></td></tr>';
		}
	$i=0;
	foreach($aimZone as $k=>$v)
	{
		if($k==$currentHttpHost) continue;
		if($i%2==0) $echo .= '<tr>';
		$echo .= '<td style="padding-left:6px"><a href="javascript:go(\\\'http://'.$k.'/function/swap_Zone.php?id='.$_SESSION['id'].'\\\')" target="_top">'.$v.'</a></td>';
		if($i%2==1) $echo .= '</tr>';
		$i++;
	}//<a href="/"><strong></strong></a>
	$echo .= '</table>	</fieldset><form id="swapForm" method="post"><input type="hidden" name="sid" value="'.session_id().'"></form><a href="/"><strong>进入游戏</strong></a><br/><div  style="width:385px" id="swapnoticetext">1. 若玩家将转入的区服中已有角色，则转服后该角色数据会被清除，只保留转服前   所在的角色数据。例：账号名为test的玩家在一区有角色A，二区有角色B，若选择从二区转到一区，则一区的角色A会被清除，转服后账号名为test的玩家在二区无角色，在一区有角色B。<br/>2. 转服奖励：口袋礼包*10，口袋宝盒*10（只限于在'.substr($swapZoneSetting['starttime'],4,2).'月'.substr($swapZoneSetting['starttime'],6,2).'日至'.substr($swapZoneSetting['endtime'],4,2).'月'.substr($swapZoneSetting['endtime'],6,2).'日转区玩家！）。转服时玩家的主战宠物大于或等于40级才会得到转服奖励，若小于40级则不会得到任何奖励<br/>3. 转服前请确保角色背包中有两个以上的空位，以确保奖励能正常发放。</div>';
	html($urlJump,$urlReg,false,"
<dd/>	$_box	<script language='javascript'>
var dom = document.getElementsByTagName('table')['3'].getElementsByTagName('td')[0];
dom.innerHTML = '".$echo."';
function changName()
{
	var nName = document.getElementById(\"nName\").value;
	if(!confirm('请确认您的通行证账号填写正确，否则将导致无法挽回的损失！\\n您确定要将角色转移到新帐号：'+nName+' 吗？\\n请保证'+nName+'是您自己的其他账号，并且在本区不存在角色！\\n您只有一次角色转移的机会！请务必确认您填写是正确的！'))
	{
		return;
}
document.getElementById(\"chgNameForm\").submit();
}
function changeNameTableDh()
{

	var obj = document.getElementById(\"changeNameTable\");
	var obj1 = document.getElementById(\"swapZoneTable\");
	var obj2= document.getElementById(\"swapnoticetext\");
	if(obj.style.display=='none')
	{
		(function() {
			var id = '#dialog';
			//Get the screen height and width
			var maskHeight = $(document).height();
			var maskWidth = $(window).width();
			//Set height and width to mask to fill up the whole screen
			$('#mask').css({'left':0,'top':0,'width':maskWidth,'height':maskHeight});
			//transition effect
			$('#mask').fadeIn(1000);
			$('#mask').fadeTo('slow',0.8);
			//Get the window height and width
			var winH = $(window).height();
			var winW = $(window).width();
			//Set the popup window to center
			$(id).css('top',  winH/2-$(id).height()/2);
			$(id).css('left', winW/2-$(id).width()/2);
			//transition effect
			$(id).fadeIn(2000);
		})();


		obj.style.display='block';
		obj1.style.display='none';
		obj2.style.display='none';
	}
	else
	{
		obj1.style.display='block';
		obj.style.display='none';
		obj2.style.display='block';
	}
}
function go(url){var f=document.getElementById(\"swapForm\");f.action=url;f.submit();}
</script>
	");
}
//用户请求的是本页面而不是本页面被包含
else if(strpos($swapZonePhpSelf,basename(__FILE__))!==false)
{
//接受玩家转区到本区
if(isset($_GET['id']) && !is_array($_GET['id']) && intval($_GET['id']) > 0 && isset($_POST['sid']) && !is_array($_POST['sid']) && preg_match('/^[a-f0-9]{32}$/i', $_POST['sid']))
{
	if(!isset($_GET['id']) || is_array($_GET['id']) || intval($_GET['id']) <= 0 || !isset($_POST['sid']) || is_array($_POST['sid']) || !preg_match('/^[a-f0-9]{32}$/i', $_POST['sid']))
	{
		die('请求参数错误！');
	}
	$userid=intval($_GET['id']);
	$referer = (isset($_SERVER['HTTP_REFERER']) && !is_array($_SERVER['HTTP_REFERER'])) ? str_replace(array("\r","\n"), '', $_SERVER['HTTP_REFERER']) : '';
	$rfr=parse_url($referer);
	if(!is_array($rfr) || !isset($rfr['host'])) die("参数错误！");
	$rfr['host'] = strtolower($rfr['host']);
	if(!swapZoneHostOk($rfr['host'])) die("参数错误！");
	$html = "";
	if(
		!array_key_exists($rfr['host'],$fromZone)
		&&
		(!isset($_SESSION['swapFrom']) || !swapZoneHostOk($_SESSION['swapFrom']) || !array_key_exists($_SESSION['swapFrom'],$fromZone))
	){
		die("转区功能未在您的区开放！");
	}
	if(!array_key_exists($currentHttpHostLower,$aimZone)) die("本区不接受转区！");
	if(!isset($_GET['swapMe'])||!isset($_SESSION['swapMe']))
	{
		$qData = curlSS('http://'.$rfr['host'].'/function/swap_Zone.php?p='.$pwd.'&qid='.$userid.'&sid='.$_POST['sid']);
		//$qData = socketData($rfr['host'],'function/swap_Zone.php?p='.$pwd.'&qid='.$userid.'&sid='.$_POST['sid']);	//查询老区数据


		if(ERROR_AUTH==$qData) die("未授权的访问！");
		//$qData=preg_split("/".$GLOBALS['block']."/",$qData,0,PREG_SPLIT_NO_EMPTY);
		if(!is_array($qData) || count($qData) < 2) die("转区服务器连接失败！");
		$data =preg_split("/".$vbcrlf."/",$qData[1],0,PREG_SPLIT_NO_EMPTY);
		if(!is_array($data) || count($data) < 2) die("转区数据错误！");
		$userInfo = divideData($data[1]);
		if(!isset($userInfo['name']) || !isset($userInfo['nickname'])) die("转区数据错误！");
		connect();
		$userInfoNameSql = mysql_real_escape_string($userInfo['name'], $conn);
		$userInfoNicknameSql = mysql_real_escape_string($userInfo['nickname'], $conn);
		$userLocal = query('select nickname from player where name like binary "'.$userInfoNameSql.'"');

		$nickLocal = query('select name,nickname from player where nickname like binary "'.$userInfoNicknameSql.'" and name<>"'.$userInfoNameSql.'"');

		$warning = "";
		if(!empty($userLocal))
		{
			$warning = "<font color=#ff0000>重要提示：如果继续您在本区角色名为：".swapZoneHtml($userLocal[0]['nickname'])."的角色,将被删除！</font>&nbsp;&nbsp;";
		}
		if($qData)
		{
			$_SESSION['swapMe'] = $userid;
			$_SESSION['swapFrom'] = $rfr['host'];

			$html .= "<div style='width:390px;color:#000000'>转区信息(您有一分钟来完成)：<br/>";
			$html .= "　　您确定要将您在<u>".swapZoneHtml($rfr['host'])."</u>，
					角色名为：<u>".swapZoneHtml($userInfo['nickname'])."</u>的角色转入本区么？<br/>
					".$warning."
					<form action='?id=".$userid."&swapMe=yes' method=\"post\" id=\"doswapform\">
					";
			if(!empty($nickLocal))
			{
				$html .= "您的角色名已经存在，请修改:".'<input type="text" id="bname" name="bname" onChange="C();" size=4><span id="cname"></span>'."";
			}
			$html .= "<input type=\"hidden\" name=\"sid\" value=\"".swapZoneHtml($_POST['sid'])."\">
					<input type='button' value='确定转入' onclick='subit=true;C();' id='sbbtn123'>&nbsp;&nbsp;<input type='button' onclick='history.back()' value='取消'>
					<font color='red'>点击<b>确定转入</b>即可完成转区程序</font>
					</form>
					</div>
			<script language=\"javascript\" src=\"/javascript/prototype.js\"></script>
<script languge='javascript'>
var subit=false;
function C()
{
	var obj=document.getElementById('bname');
	if(obj){
		var id='cname';
		var op='';
		if(id=='cuser') op='n='+obj.value;
		else op='n='+obj.value;
		var opt = {
			method: 'get',
				onSuccess: function(t) {
					$(id).innerHTML=t.responseText;
					if((t.responseText=='ok' || t.responseText=='OK')&&subit)
					{
						$('sbbtn123').disabled=true;
						$('doswapform').submit();
						subit=false;
		}else if(subit)
		{
			if(t.responseText.replace(/[\w<>=\"'\/]/g,'') != ''){
				alert(t.responseText.replace(/[\w<>=\"'\/]/g,''));
		}
		}
		},
			on404: function(t) {
		},
			onFailure: function(t) {
		},
			asynchronous:true
		}
		//$(id).innerHTML+='/login/loginCheck.php?'+op;
		var ajax=new Ajax.Request('/login/loginCheck.php?'+op, opt);
		}
		else{
			if(subit)
			{
				$('doswapform').submit();
		}
		}
		}
		</script>
					";
		}
		else
		{
			die("连接服务器失败(00)!");
		}
	}
	else if(isset($_SESSION['swapFrom']))
	{
		if(!swapZoneHostOk($_SESSION['swapFrom']) || !array_key_exists($_SESSION['swapFrom'],$fromZone))
		{
			die("转区功能未在您的区开放！");
		}
		//$qData = socketData($_SESSION['swapFrom'],'function/swap_Zone.php?swapMe=yes&p='.$pwd.'&qid='.$userid.'&sid='.$_POST['sid'].'&swapto='.$_SERVER['HTTP_HOST']);	//查询老区数据
		$qData = curlSS('http://'.$_SESSION['swapFrom'].'/function/swap_Zone.php?swapMe=yes&p='.$pwd.'&qid='.$userid.'&sid='.$_POST['sid'].'&swapto='.urlencode($currentHttpHost));
		//var_dump($qData);
		//$qData=preg_split("/".$GLOBALS['block']."/",$qData,0,PREG_SPLIT_NO_EMPTY);

		if(ERROR_AUTH==$qData) die("未授权的访问！");
		if(ERROR_SECID_STATUS==$qData) die("您已经转区或者您的帐号被冻结！");
		if(!is_array($qData) || count($qData)!=7){
			die("操作过期，必须在一分钟内完成！<br>请关闭当前浏览器并新开浏览器重试！");
		}
		if($qData)
		{
			connect();
			if($swapDebug && isset($_REQUEST['dbg'])){
				die("转区调试信息已关闭。");
			}
			if(saveData($qData,$userid,$_SESSION['swapFrom'])){
				unset($_SESSION);
				die(
				'
<script language="javascript">
alert("转入成功");
window.location="/";
</script>
				');
			}else{
				die(
				'
<script language="javascript">
alert("'.$GLOBALS['srMsg'].'\n请“重新发送”。");
window.history.back(-1);
</script>
				');
			}
		}
		else
		{
			die("连接服务器失败(01)!");
		}
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>口袋精灵转区</title>
<style type="text/css">
<!--
body {
	background-color: #2CA67F;
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px;
}
.STYLE1 {
	color: #00553a;
	font-size: 12px;
	line-height: 20px;
}

#mbox{background-color:#eee; padding:8px; border:2px outset #666;}
#mbm{font-family:sans-serif;font-weight:bold;float:right;padding-bottom:5px;}
.dialog {display:none}



-->
</style>
</head>
	<script type="text/javascript">
	</script>
<body>



<div id="hh">
<table width="890" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
	<td><img src="/login/image/zc24.jpg" width="890" height="140"></td>
  </tr>
</table>
<table width="890" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
	<td width="246"><img src="/login/image/zc25.jpg" width="246" height="228"></td>
  <td width="166"><img src="/login/image/zc28.jpg" width="166" height="228" border="0" usemap="#Map"></td>
	<td width="144"><img src="/login/image/zc26.jpg" width="152" height="228" border="0" usemap="#Map2"></td>
	<td width="145"><img src="/login/image/zc31.jpg" width="137" height="228"></td>
	<td><img src="/login/image/zc27.jpg" width="189" height="228"></td>
  </tr>
</table>
<table width="890" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
	<td width="170" valign="top"><img src="/login/image/zc21.jpg" width="170" height="252"></td>
	<td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
	  <tr>
		<td height="132" align="left" valign="top" background="/login/image/zc30.jpg" bgcolor="#B5E1D2" style=" line-height:1.7;font-size:12px;color:red;padding-left:60px; font-weight:bold">
			<?php echo $html; ?>
		</td>
	  </tr>

	  <tr>
		<td><img src="/login/image/zc29.jpg" width="531" height="120"></td>
	  </tr>
	</table></td>
	<td width="189" valign="top"><img src="/login/image/zc22.jpg" width="189" height="252"></td>
  </tr>
</table>
</div>
	<script language="javascript">
	document.write('<map name="Map" id="Map">');
	document.write('<area onFocus="this.blur()" shape="circle" coords="79,64,64" href="http://passport.webgame.com.cn/login.do?forward=http://'+window.location.host+'/login/dl.html&gameType=ff" target="_self" />');
	document.write('</map><map name="Map2" id="Map2"><area onFocus="this.blur()" shape="circle" coords="88,114,55" href="http://passport.webgame.com.cn/register.do" target="_self" /></map>');
	</script>
	<script language="javascript">
	document.write('<script language="javascript" src="/login/get.info.php'+window.location.search+'"><\/script>');
	</script>
</body>
</html>
<?php
	die();
}

//取消转区，如果接受转区的服务器处理失败，通过下面程序接受取消转区
if(isset($_GET['cancelSwap'])&&isset($_GET['id'])&&!is_array($_GET['id'])&&intval($_GET['id'])>0&&isset($_GET['p'])&&!is_array($_GET['p'])&&strlen($_GET['p'])==32)
{
	$cancelUserId = intval($_GET['id']);
	$mem = new mem($_pm['mem']);
	$pwd = $mem->{'cancel_pwd_'.$cancelUserId};

	if($pwd && $pwd  == $_GET['p'])
	{
		unset($mem->{'cancel_pwd_'.$cancelUserId});
		unset($mem->{'swapMe_'.$cancelUserId});
		connect();
		query('update player set secid=0 where id='.$cancelUserId);
		echo "还原数据成功！";
	}
	die("!");
}

//接受其它服务器查询本区数据和转区：第一次查询只给用户信息，
if(isset($_GET['qid']) && !is_array($_GET['qid']) && isset($_GET['p']) && isset($_GET['sid']) && !is_array($_GET['p']) && !is_array($_GET['sid']) && intval($_GET['qid'])>0 && $_GET['p']==$pwd && preg_match('/^[a-f0-9]{32}$/i', $_GET['sid']))
{
	echo HTTP_CONTENT_STARTED;
	$userid=intval($_GET['qid']);
	if(!isset($_SESSION['id'])||$_SESSION['id']!=$userid)
	{
		die(ERROR_AUTH);
	}
	connect();
	$userInfo = query('select * from player where id='.$userid);


	//同学网通行证长度不一致特殊处理开始
	$userInfo[0]['name'] = substr($userInfo[0]['name'],0,18);
	//同学网通行证长度不一致特殊处理结束

	if(empty($userInfo)||$userInfo[0]['secid']>0)
	{
		die(ERROR_SECID_STATUS);
	}
	$memHandle = $_pm['mem']->getHandle();

	$swapMe = $_pm['mem']->get('swapMe_'.$userid);


	if(!isset($_GET['swapMe'])||!$swapMe)
	{
		$memHandle->set('swapMe_'.$userid,1,0,60);
		echo formatData($userInfo,'player');
		die();
	}
	else
	{
	$cancel_pwd = md5(time().$currentHttpHost);
		$memHandle->set('cancel_pwd_'.$userid,$cancel_pwd,0,60);
		$_pm['mem']->set(array('k'=>'cancel_pwd1_'.$userid,'v'=>$cancel_pwd));
		echo $cancel_pwd.formatData($userInfo,'player');
	}

	//结婚处理
	$merge=query("select merge,send,request_merge from player_ext WHERE uid ={$userid}");
	if($merge[0]['merge']>0){
		query("UPDATE player_ext SET request=0,merge=0,request_merge=0,send='' WHERE uid = {$userid} or uid={$merge[0]['merge']}");
		$sum_send=query("SELECT id FROM userbag WHERE uid={$userid} and pid=2381 and vary=1 and zbing=0 and (cantrade IS NULL OR cantrade<>3) LIMIT 0,1");
		if(!empty($sum_send[0]['id'])){
			query("UPDATE userbag SET sums=COALESCE(sums,0)+1  WHERE uid={$userid} and id={$sum_send[0]['id']} and vary=1 and zbing=0 and (cantrade IS NULL OR cantrade<>3)");
		}else{
			query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime) VALUES({$userid},2381,1,1,1, ".time()." )");
		}
		//$merge1=query("select request from player_ext where uid = {$merge[0]['merge']}");
		$tt=date('Y-m-d H:i:s',time());
		$usernickname0=query("select nickname from player where id={$userid}");
		//if($merge1[0]['request']==1){
		query("insert into information(uid,times,content) values({$merge[0]['merge']},'{$tt}','玩家【{$usernickname0[0]['nickname']}】已转区，已与你强制离婚！')");
		//}else{
		//query("insert into information(uid,times,content) values({$merge[0]['merge']},'{$tt}','玩家【{$usernickname0[0]['nickname']}】与你强制离婚！')");
		//}
	}elseif($merge[0]['request_merge']>0){
		$send1=explode(',',$merge[0]['send']);
		$bid=intval($send1[1]);
		$n=intval($send1[0]);
		if($bid > 0 && $n > 0){
			$sum_send=query("SELECT id FROM userbag WHERE uid={$userid} and pid={$bid} and vary=1 and zbing=0 and (cantrade IS NULL OR cantrade<>3) LIMIT 0,1");
			if(!empty($sum_send[0]['id'])){
				query("UPDATE userbag SET sums=COALESCE(sums,0)+{$n}  WHERE uid={$userid} and id={$sum_send[0]['id']} and vary=1 and zbing=0 and (cantrade IS NULL OR cantrade<>3)");
			}else{
				query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime) VALUES({$userid},{$bid},1,1,{$n}, ".time()." )");
			}
		}
		query("UPDATE player_ext SET request=0,merge=0,request_merge=0,send='' WHERE uid = {$userid}");
	}





	$userBag = query('select * from userbag where uid='.$userid);
	echo formatData($userBag,'userbag');

	$userBb = query('select * from userbb where uid='.$userid);
	echo formatData($userBb,'userbb');

	$userBbSkill = query('select skill.* from skill,userbb where userbb.id=skill.bid and userbb.uid='.$userid);
	echo formatData($userBbSkill,'skill');

	$userTask = query('select * from tasklog where uid='.$userid);
	echo formatData($userTask,'tasklog');

	$player_ext = query('select * from player_ext where uid='.$userid);
	echo formatData($player_ext,'player_ext');

	query('update player set secid=40 where id='.$userid);

	$keyCurUser  = $userid . "chat";
	if(isset($keyCurUser)) //把本区(转区来自区)帐号踢下线
		$GLOBALS['_pm']['mem']->del($keyCurUser);
	$swapto = (isset($_GET['swapto']) && !is_array($_GET['swapto'])) ? $_GET['swapto'] : '';
	$swaptoSql = mysql_real_escape_string($swapto, $conn);
	$exportNameSql = mysql_real_escape_string($userInfo[0]['name'], $conn);
	$exportLogSql = mysql_real_escape_string($swapto.','.print_r($userInfo[0],1), $conn);
	$sqlLog = '
		insert
		into
		gamelog
		(ptime,	seller,	buyer,	pnote,	vary)
		values
		(unix_timestamp(),"'.$exportNameSql.'","SWAP_ZONE_LOG","'.$exportLogSql.'",13)
		';
	query($sqlLog);
	$sqlPai = 'update userbag
		set
		psell=0,
		pstime=0,
		petime=0,
		sums=COALESCE(sums,0)+COALESCE(psum,0),
		psum=0,
		buycode=""
		where uid='.$userid;

	query($sqlPai);
	die();
}


echo "默认响应。";

}

function curlSS($url,$port=80){
	$post = 1;
	$returntransfer = 1;
	$header = 0;
	$nobody = 0;
	$followlocation = 1;
	$request = '';
	$cookie_jar = tempnam(sys_get_temp_dir(), 'kdjl_cookie_');

	$ch = curl_init();
	$options = array(CURLOPT_URL => $url,
		CURLOPT_HEADER => $header,
		CURLOPT_NOBODY => $nobody,
		CURLOPT_PORT => $port,
		CURLOPT_POST => $post,
		CURLOPT_POSTFIELDS => $request,
		CURLOPT_RETURNTRANSFER => $returntransfer,
		CURLOPT_FOLLOWLOCATION => $followlocation,
		CURLOPT_COOKIEJAR => $cookie_jar,
		CURLOPT_COOKIEFILE => $cookie_jar,
		CURLOPT_REFERER => $url,
		CURLOPT_CONNECTTIMEOUT => 2,
		CURLOPT_TIMEOUT => 3
	);
	curl_setopt_array($ch, $options);
	$result = curl_exec($ch);
	curl_close($ch);
	if($cookie_jar && file_exists($cookie_jar)) @unlink($cookie_jar);
	if($result === false) $result = '';
	$result=preg_split("/".$GLOBALS['block']."/",$result,0,PREG_SPLIT_NO_EMPTY);
	return $result;
}
?>
