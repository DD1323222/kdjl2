<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.06.06
*@Usage:Common function.
*@Note: none
*/

/**
*@Usage: 获取内存中各表的自增ID。Mem map table : memorder
*@Param: $m obj,
*@Table: $key of memory
*@Return: false or (int)id
*/
function mem_get_autoid($m, $key, $table)
{
	if (!is_object($m))	return false;
	global $db;
	if (!is_object($db)) $db = new mysql();
	$retid = false;
	$times=0;
	$tableSql = $db->escape($table);
########事务开始#######

    //$db->query("SET autocommit=0");
	//$db->query("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
	if(!$db->query("START TRANSACTION")) return false;
	if(!$db->query("UPDATE memorder SET lastid=lastid+1 WHERE tname='{$tableSql}'") || mysql_affected_rows($db->getConn()) != 1)
	{
		$db->query("ROLLBACK");
		return false;
	}
	$rs = $db->getOneRecord("SELECT lastid FROM memorder WHERE tname='{$tableSql}'");
	if(!is_array($rs) || !isset($rs['lastid']))
	{
		$db->query("ROLLBACK");
		return false;
	}

	if (!$db->query("COMMIT")){
		  $db->query("ROLLBACK");
	}
	else
	{
		$retid = $rs['lastid'];
		unset($rs);
	}
########事务开始#######
	return $retid;
}

/**
@Usage: 检测用户SESSION是否过期或是否多开。
@Param: $m
@Return: false;
*/
function secStart($m)
{
	$sessionId = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;

	$ttmt_Exclusion_File = array(
				'Aoyun_Mod.php'=>0,
				'Aoyunti_Mod.php'=>0,
				'AoyuntiGate.php'=>0,
				'Base_Mod.php'=>0,
				'BattleComein_Mod.php'=>0,
				'BattleExp_Mod.php'=>0,
				'BattleFight_Mod.php'=>0,
				'battleFightGate.php'=>0,
				'BattleInfo_Mod.php'=>0,
				'BattleProps_Mod.php'=>0,
				'Challenge_Mod.php'=>0,
				'ChallengeGate.php'=>0,
				'City_Mod.php'=>0,
				'ext_Battle.php'=>0,
				'ext_Challenge.php'=>0,
				'ext_Muchang.php'=>0,
				'fb_Mod.php'=>0,
				'fbfight_Mod.php'=>0,
				'fbfightGate.php'=>0,
				'friendGate.php'=>0,
				'getmap.php'=>0,
				'GrowthRanking_Mod.php'=>0,
				'King_Mod.php'=>0,
				'KinPrestige_Mod.php'=>0,
				'KinPrestigeGate.php'=>0,
				'guild.php'=>0,
				'Family_Mod.php'=>0,
				'FamilyGate.php'=>0,
				'Muchang_Mod.php'=>0,
				'offprops.php'=>0,
				'offpropsAll.php'=>0,
				'onpropsAll.php'=>0,
				'openMap.php'=>0,
				'Pai_Mod.php'=>0,
				'paibuyGate.php'=>0,
				'paiProps_Mod.php'=>0,
				'paiPropsGate.php'=>0,
				'paisellGate.php'=>0,
				'Pets_Mod.php'=>0,
				'Pets_Mod_View.php'=>0,
				'Prestige_Mod.php'=>0,
				'PrestigeGate.php'=>0,
				'Public_Mod.php'=>0,
				'PuPrestige_Mod.php'=>0,
				'Sd_ComposeMod.php'=>0,
				'Sd_Mod.php'=>0,
				'Shopsm_Mod.php'=>0,
				'smbuyGate.php'=>0,
				'Tuoguan_Mod.php'=>0,
				'tuoGuanGate.php'=>0,
				'User_Mod.php'=>0,
				'Zb_Mod.php'=>0,
				'ZbGate.php'=>0,
				'zbPrestige_Mod.php'=>0,
				'zbPrestigeGate.php'=>0,
				'zbstrength_Mod.php'=>0,
				'zbstrengthGate.php'=>0,
				'zs_Mod.php'=>0,
				'zsGate.php'=>0
				);

	$currentScript = (isset($_SERVER['PHP_SELF']) && !is_array($_SERVER['PHP_SELF'])) ? basename($_SERVER['PHP_SELF']) : '';
	if($sessionId > 0 && isset($ttmt_Exclusion_File[$currentScript]))
	{
		global $_pm;
		$ts=$_pm['mysql']->getOneRecord('select state from team_members where uid='.$sessionId.' and state=1 limit 1');

		if(is_array($ts) && isset($ts['state']) && intval($ts['state'])==1){
			header('Content-Type:text/html;charset=utf-8');
			if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])){
				echo '您已经组队了，不能进行这个操作!';
			}else{
				echo '<body topmargin="100px">
				<table width="300" border="0" cellpadding="0" cellpadding="0" style="background-image:url(../new_images/ui/team_notice.gif);background-repeat:no-repeat" align="center">
				  <tr>
					<th scope="col" style="color:#FFFFFF" align="right"></th>
				  </tr>
				  <tr>
					<td style="font-size:12px; padding:5px; color:#EED193" height="120">
					<font color="#fffff">您已经组队了，不能进行这个操作!</font><br/><br/>
					请 <a href="/function/Team_Mod.php?tact=return" style="color:#EED193;font-weight:bold">返回队伍</a>，<br /><br />
					或者 <a href="/function/Team_Mod.php?tact=swap" style="color:#EED193;font-weight:bold">暂时离开队伍</a>，<br /><br />
					或者 <a href="/function/Team_Mod.php?tact=quit" style="color:#EED193;font-weight:bold">退出队伍</a>！
					</td>
				  </tr>
				</table>
				</body>
				';
			}
			die();
		}
	}

	$OnlineUsers = $m->get('waitors');
	if(!is_array($OnlineUsers)) $OnlineUsers = kdjlSafeMemValue($OnlineUsers, array());
	if($sessionId > 0 && isset($OnlineUsers[$sessionId]))
	{
		unset($OnlineUsers[$sessionId]);
		$m->set(array('k'=>'waitors','v'=>$OnlineUsers));
	}

	//================
	if ($sessionId < 1)
		die('<a href="/login/login.php?rd='.rand().'" target=_top>网络中断，请重新登录!</a>');
	$crc = (isset($_COOKIE['PHPSESSID']) && !is_array($_COOKIE['PHPSESSID'])) ? $_COOKIE['PHPSESSID'] : '';
	$truecrc = kdjlSafeMemValue($m->get($sessionId . 'chat'), '');
	$validSessionId = $crc !== '' && preg_match('/^[A-Za-z0-9,-]{1,128}$/D', $crc) === 1;
	$sessionCrc = $validSessionId ? crc32($crc) : '';
	// Current login code stores crc32(session id); accept the legacy raw value
	// as well so sessions created before an update are not disconnected.
	if (!$validSessionId ||
		((string)$truecrc !== (string)$sessionCrc && (string)$truecrc !== $crc)) {
		unset($_SESSION);
		die('<a href="/login/login.php" target=_top>网络中断，请重新登录!!</a>');
	}
	$m=null;
	unset($m);
}

/**
*@Usage: 根据成长率范围，返回宝宝的初始成长率。
*@Param: $czl: 成长率范围。
*@Return: false or (int)id
*/
function getCzl($czl)
{
	$ok = str_replace(".", "", $czl);
	$arr = explode(",", $ok);
	if (count($arr) != 2) return false;
	$min = intval($arr[0]);
	$max = intval($arr[1]);
	if ($min > $max)
	{
		$tmp = $min;
		$min = $max;
		$max = $tmp;
	}
	$num = rand($min, $max);
	unset($ok, $arr);
	return $num/10;
}

/**
* @Usage: 得到五行.
*/
function getWx($n)
{
	$str = '';
	switch($n){
		case 1: $str = '金';break;
		case 2: $str = '木';break;
		case 3: $str = '水';break;
		case 4: $str = '火';break;
		case 5: $str = '土';break;
		case 6: $str = '神';break;
		case 7: $str = '神圣';break;
	}
	return $str;
}

function ob_gzip($content)
{
	$acceptEncoding = isset($_SERVER["HTTP_ACCEPT_ENCODING"]) ? $_SERVER["HTTP_ACCEPT_ENCODING"] : '';
	if( !headers_sent() &&
	extension_loaded("zlib") &&
	strstr($acceptEncoding,"gzip"))
	{
		$content = gzencode($content." \n",9);

		header("Content-Encoding: gzip");
		header("Vary: Accept-Encoding");
		header("Content-Length: ".strlen($content));
	}
	return $content;
}

// Return task status.
// @Parm: task id, current npc code.
// $defaultWord = $_task['npc'][8].':<br/>
// @Add: 颜色替换：
// [中文]绿色代码：#008200 \中文\蓝色代码：#848EF7 {中文}红色代码：#FF0000 |中文|紫色代码：#B528E7 /中文/橙色代码：#FF7200

function taskWord($tid,$n)
{
	global $_pm,$_task,$user;
	$user = $_pm['user']->getUserById($_SESSION['id']);
	if(!is_array($user)) $user = array();
	$ret = isset($_task['dlg'][$n]) ? $_task['dlg'][$n] : '';
	$colortag = array('[',']','{','}','(',')');
	$colorlist = array('<font color=#0000FF>','</font>','<font color=#848EF7>','</font>','<font color=#FF0000>','</font>');
	$taskid = array();
	$tls = array();

	if (intval($tid) == 10000 || intval($tid)<0)
	{

		return $ret; // No any task.
	}

	$taskinfo = $_pm['mem']->dataGet(array('k'	=>	MEM_TASK_KEY,
										  'v'	=> "if(\$rs['id']=={$tid}) \$ret=\$rs;"
									));
	if (is_array($taskinfo))
	{
		// compose task ui.title=>detail
		if ($taskinfo['fromnpc'] == $n )
		{
			$taskinfo['title'] = str_replace($colortag, $colorlist, $taskinfo['title']);
			$title = str_replace($colortag, $colorlist, $taskinfo['title']);
			/*if($taskinfo['id'] == 179 || $taskinfo['id'] == 182 || $taskinfo['id'] == 185 || $taskinfo['id'] == 186)
			{
				$ret = "";
			}else{*/
				$ret = '<span onclick="javascript:taskCatch('.$n.')" style="cursor:pointer;"><u>'.$taskinfo['title'].'</u></span>';
			//}
		}
		else if ($taskinfo['oknpc'] == $n && strlen($user['tasklog'])>2)
		{
		   $ret = "<font color=blue>{$taskinfo['title']}</font><br />".$taskinfo['okmsg']."<font color=green>[拜访完成]</font>
		    <br/><span style='cursor:pointer;color:green;' onclick=\"task('n={$n}');\">[<u>领取奖励</u>]</span>";
		}
	}

	// 加入满足等级的循环任务。

	$mainBid = isset($user['mbid']) ? intval($user['mbid']) : 0;
	$taskUid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
	$bbinfo = $_pm['mysql']->getOneRecord("SELECT level
											 FROM userbb
											 WHERE id={$mainBid} AND uid={$taskUid}");
	$bbLevel = is_array($bbinfo) && isset($bbinfo['level']) ? intval($bbinfo['level']) : 0;
	//等级限制完善  等级字段由原来的数字Ｘ改为Ｘ，Ｙ
	/*$lvlimit = $_pm['mysql'] -> getRecords("SELECT limitlv FROM task");
	foreach($limit as $kl => $vl)
	{
		$kl1 = $kl1 + 1;
		if($lvlimit[$kl]['name'] == $lvlimit[$kl1]['name'])
		{
			continue;
		}
		else
		{
			$newlimit[] = $vl;//将所有的等级限制组成一个一维数组
		}
	}
	foreach($newlimit as $ks => $vs)
	{
		$arlv = explode(",",$vs);
		$tl = $_pm['mysql'] -> getRecords("SELECT * FROM task
										WHERE id > 8
										and fromnpc = {$n}
										and id in
										(SELECT id
										FROM task
										WHERE {$bbinfo['level']} between $arlv[0] and $arlv[1])");
	}*/

	$tl = $_pm['mysql']->getRecords("SELECT * FROM task
									  WHERE id>8 and limitlv<={$bbLevel} and fromnpc={$n}
									");
	$arr = 	$_pm['mysql']->getRecords("SELECT taskid FROM tasklog,task
									  WHERE task.id = tasklog.taskid and uid = {$taskUid}
									");
	if(is_array($tl) && is_array($arr))
	{
		foreach($arr as $v)
		{
			$taskid[] = $v['taskid'];
		}
	}
	if(is_array($taskid))
	{
		foreach($tl as $vtl)
		{
			if(in_array($vtl['id'],$taskid))
			{
				continue;
			}
			else
			{
				$tls[] = $vtl;
			}
		}
	}
	else
	{
		$tls = $tl;
	}
	if (is_array($tls))
	{
		foreach($tls as $k => $v)
		{
			$title = str_replace($colortag, $colorlist, $v['title']);
			$ret .= '<br/><span onclick="javascript:OpenLogin(1,'.$v['id'].','.$n.','.($user['task']?1:0).')" style="cursor:pointer;"><u>'.$title.'</u></span>';
		}
	}
	return $ret;
}

// function part.
// curent only support one attrib.
function zbAttrib($effect)
{
	global $_props;
	if(strlen($effect)<2) return '无';

	$arr = explode(',', $effect);
	foreach ($arr as $k => $v)
	{
		if($v== '' ) continue;
		$ar = explode(':', $v);
		switch($ar[0])
		{
			case "ac": $retstr .= '攻击: '.$ar[1].' ';break;
			case "mc": $retstr .= '防御: '.$ar[1].' ';break;
			case "hp": $retstr .= '生命: '.$ar[1].' ';break;
			case "mp": $retstr .= '魔法: '.$ar[1].' ';break;
			case "speed": $retstr .= '速度: '.$ar[1].' ';break;
			case "hits": $retstr .= '命中: '.$ar[1].' ';break;
			case "miss": $retstr .= '闪避: '.$ar[1].' ';break;
		}
	}
	return $retstr;
}

/*
* @Usage: Get zb all attrib.
* @Param:
* $cpets 效果，字符串
* $ack 是宠物对怪物的攻击力
* $ack1 是怪物对宠物的攻击力
* @Return:
*/
if (!function_exists('kdjlSafeMemValue')) {
	function kdjlSafeMemValue($raw, $default)
	{
		if ($raw === false || $raw === null || $raw === '') {
			return $default;
		}
		if (!is_string($raw)) {
			return $raw;
		}
		$parsed = @unserialize($raw);
		if ($parsed === false && $raw !== serialize(false)) {
			return $default;
		}
		return $parsed;
	}
}

if (!function_exists('kdjlSafeIconv')) {
	function kdjlSafeIconv($from, $to, $value)
	{
		$value = (string)$value;
		$converted = @iconv($from, $to, $value);
		return ($converted === false) ? $value : $converted;
	}
}

if (!function_exists('kdjlReflectedDamage')) {
	function kdjlReflectedDamage($incomingDamage, $reflectRate)
	{
		$incomingDamage = max(0, floatval($incomingDamage));
		$reflectRate = max(0, floatval(str_replace('%', '', strval($reflectRate))));
		if($incomingDamage <= 0 || $reflectRate <= 0) return 0;
		return max(0, intval(round($incomingDamage * $reflectRate * 0.01)));
	}
}

function getzbAttrib($bid,$ack=false,$ack1='',$ownerUid=0){
	global $_pm;
	$bid = intval($bid);
	$ownerUid = intval($ownerUid);
	if($ownerUid < 1) $ownerUid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
	formatMsgEffect($bid);
	$str = kdjlSafeMemValue($_pm['mem'] -> get('format_user_zhuangbei_'.$bid), '');
	if (empty($str)) {
		formatMsgEffect($bid);
		$str = kdjlSafeMemValue($_pm['mem'] -> get('format_user_zhuangbei_'.$bid), '');
	}
	$zbattrib=array();
	$zbattribDefaults = array('ac','mc','hp','mp','speed','hits','miss','hpdx','hp1','mp1','ack','shft','fhp','money','time','crit');
	foreach($zbattribDefaults as $zbattribKey){
		$zbattrib[$zbattribKey] = 0;
	}
	$equipChangedFlag = kdjlSafeMemValue($_pm['mem']->get("User_bb_equip_changed_".$bid.'_'.$ownerUid), 0);
	//$_SESSION['dbg_equip_attr3'] .= date("Y-m-d H:i:s")."= 1 ====".$cpets."<br>";
	if($ack===false&&$ack1===""){
		if(!$equipChangedFlag&&$bid!=0){
			$cachedZbAttrib = kdjlSafeMemValue($_pm['mem']->get("User_bb_equip_info_a_".$bid.'_'.$ownerUid), array());
			if(is_array($cachedZbAttrib) && $cachedZbAttrib && isset($cachedZbAttrib[0]) && is_array($cachedZbAttrib[0]) && isset($cachedZbAttrib[0]['shft'])){
				return $cachedZbAttrib[0];
			}
		}
	}else{
		if(!$equipChangedFlag&&$bid!=0){
			$cachedZbAttrib = kdjlSafeMemValue($_pm['mem']->get("User_bb_equip_info_b_".$bid.'_'.$ownerUid), array());//包含了吸血、抵消等
			if(is_array($cachedZbAttrib) && isset($cachedZbAttrib[0]) && is_array($cachedZbAttrib[0]) && isset($cachedZbAttrib[0]['shft']) && isset($cachedZbAttrib[1]) && $ack.'_'.$ack1==$cachedZbAttrib[1]){
				if($cachedZbAttrib){
					//$_SESSION['dbg_equip_attr'] .= date("Y-m-d H:i:s").$_SERVER['PHP_SELF']."= return b.<br/>";
					return $cachedZbAttrib[0];
				}
			}
		}
	}

	$pets = $_pm['mysql'] -> getOneRecord("SELECT srchp,srcmp,ac,mc,hits,speed,miss
								             FROM userbb
								            WHERE id = $bid");
	if(!is_array($pets)) $pets = array();
	$petDefaults = array('srchp'=>0,'srcmp'=>0,'ac'=>0,'mc'=>0,'hits'=>0,'speed'=>0,'miss'=>0);
	foreach($petDefaults as $petKey=>$petValue){
		if(!isset($pets[$petKey])) $pets[$petKey] = $petValue;
	}
	$arr = empty($str) ? array() : explode(',',$str);
	foreach ($arr as $v){
		if (empty($v)) {
			continue;
		}
		$newarr = explode(':',$v);
		if(count($newarr) < 2 || $newarr[0] === '') continue;
		$newarr[1] = intval($newarr[1]);
		switch ($newarr[0]){
			case 'ac':
				$zbattrib['ac'] += $newarr[1];
				break;
			case 'mc':
				$zbattrib['mc'] += $newarr[1];
				break;
			case 'hp':
				$zbattrib['hp'] += $newarr[1];
				break;
			case 'mp':
				$zbattrib['mp'] += $newarr[1];
				break;
			case 'speed':
				$zbattrib['speed'] += $newarr[1];
				break;
			case 'hits':
				$zbattrib['hits'] += $newarr[1];
				break;
			case 'miss':
				$zbattrib['miss'] += $newarr[1];
				break;
			case 'hprate':
				$zbattrib['hp'] += round($newarr[1] * $pets['srchp'] * 0.01);
				break;
			case 'mprate':
				$zbattrib['mp'] += round($newarr[1] * $pets['srcmp'] * 0.01);
				break;
			case 'acrate':
				$zbattrib['ac'] += round($newarr[1] * $pets['ac'] * 0.01);
				break;
			case 'mcrate':
				$zbattrib['mc'] += round($newarr[1] * $pets['mc'] * 0.01);
				break;
			case 'hitsrate':
				$zbattrib['hits'] += round($newarr[1] * $pets['hits'] * 0.01);
				break;
			case 'missrate':
				$zbattrib['miss'] += round($newarr[1] * $pets['miss'] * 0.01);
				break;
			case 'speedrate':
				$zbattrib['speed'] += round($newarr[1] * $pets['speed'] * 0.01);
				break;
			case 'dxsh':
				$zbattrib['hpdx'] += round($newarr[1] * $ack * 0.01);
				break;
			case 'hitshp':
				$zbattrib['hp1'] += round($newarr[1] * $ack1 * 0.01);
				break;
			case 'hitsmp':
				$zbattrib['mp1'] += round($newarr[1] * $ack1 * 0.01);
				break;
			case 'shjs':
				$zbattrib['ack'] += round($newarr[1] * $ack1 * 0.01);
				break;
			case 'shft':
				$zbattrib['shft'] += max(0, $newarr[1]);
				break;
			case 'sdmp':
				$zbattrib['hpdx'] += round($newarr[1] * $ack * 0.01);//echo $zbattrib['hp2']."sdmp";
				$zbattrib['mp1'] = $zbattrib['mp1'] - round($newarr[1] * $ack * 0.01);
				break;
			case 'szmp':
				$zbattrib['mp1'] += round($newarr[1] * $ack * 0.01);
				break;
			case 'addmoney':
				$zbattrib['money'] += $newarr[1];
				break;
			case 'time':
				$zbattrib['time'] += $newarr[1];
				break;
			case 'crit':
			{
				$zbattrib['crit'] += $newarr[1];
			}
		}
	}
	$uid = $ownerUid;
	$buff_title = array();
	if($uid > 0)
	{
		$sql = " SELECT F_add_hp,F_add_mp,F_add_ac,F_add_mc,F_add_hits,F_add_miss,F_add_speed,F_add_hprate,F_add_mprate,F_add_acrate,F_add_mcrate,F_add_hitsrate,F_add_missrate,F_add_speedrate,F_dxsh,F_hitshp,F_hitsmp,F_shjs,F_sdmp,F_szmp,F_addmoney,F_time FROM player_ext,T_Card_to_Title WHERE player_ext.now_Achievement_title = T_Card_to_Title.F_title_name AND player_ext.uid = '".$uid."'";
		$buff_title = $_pm['mysql']->getOneRecord($sql);
	}
	if(!is_array($buff_title)) $buff_title = array();
	$buffTitleDefaults = array('F_add_hp'=>0,'F_add_mp'=>0,'F_add_ac'=>0,'F_add_mc'=>0,'F_add_hits'=>0,'F_add_miss'=>0,'F_add_speed'=>0,'F_add_hprate'=>0,'F_add_mprate'=>0,'F_add_acrate'=>0,'F_add_mcrate'=>0,'F_add_hitsrate'=>0,'F_add_missrate'=>0,'F_add_speedrate'=>0,'F_dxsh'=>0,'F_hitshp'=>0,'F_hitsmp'=>0,'F_shjs'=>0,'F_sdmp'=>0,'F_szmp'=>0,'F_addmoney'=>0,'F_time'=>0);
	foreach($buffTitleDefaults as $buffTitleKey=>$buffTitleValue){
		if(!isset($buff_title[$buffTitleKey])) $buff_title[$buffTitleKey] = $buffTitleValue;
	}
	$zbattrib['hp'] += round($buff_title['F_add_hprate'] * $pets['srchp'] * 0.01);
	$zbattrib['mp'] += round($buff_title['F_add_mprate'] * $pets['srcmp'] * 0.01);
	$zbattrib['ac'] += round($buff_title['F_add_acrate'] * $pets['ac'] * 0.01);
	$zbattrib['mc'] += round($buff_title['F_add_mcrate'] * $pets['mc'] * 0.01);
	$zbattrib['hits'] += round($buff_title['F_add_hitsrate'] * $pets['hits'] * 0.01);
	$zbattrib['miss'] += round($buff_title['F_add_missrate'] * $pets['miss'] * 0.01);
	$zbattrib['speed'] += round($buff_title['F_add_speedrate'] * $pets['speed'] * 0.01);

	$zbattrib['hpdx'] += round($buff_title['F_dxsh'] * $ack * 0.01);
	$zbattrib['hp1'] += round($buff_title['F_hitshp'] * $ack1 * 0.01);
	$zbattrib['mp1'] += round($buff_title['F_hitsmp'] * $ack1 * 0.01);
	$zbattrib['ack'] += round($buff_title['F_shjs'] * $ack1 * 0.01);
	$zbattrib['hpdx'] += round($buff_title['F_sdmp'] * $ack * 0.01);
	$zbattrib['mp1'] = $zbattrib['mp1'] - round($buff_title['F_sdmp'] * $ack * 0.01);
	$zbattrib['mp1'] += round($buff_title['F_szmp'] * $ack * 0.01);
	$zbattrib['money'] += $buff_title['F_addmoney'];
	$zbattrib['time'] += $buff_title['F_time'];

	$zbattrib['hp'] += $buff_title['F_add_hp'];
	$zbattrib['mp'] += $buff_title['F_add_mp'];
	$zbattrib['ac'] += $buff_title['F_add_ac'];
	$zbattrib['mc'] += $buff_title['F_add_mc'];
	$zbattrib['hits'] += $buff_title['F_add_hits'];
	$zbattrib['miss'] += $buff_title['F_add_miss'];
	$zbattrib['speed'] += $buff_title['F_add_speed'];
	$key_arr = array('hp','mp','ac','mc','hits','miss','speed');
	/*foreach( $zbattrib as $key => $val )
	{
		if( in_array($key,$key_arr) && $val < 0 )
		{
			$zbattrib[$key] = 0;
		}
	}*/
	//确认不能为负哈
	savezbAtrribToMem($bid,$ack,$ack1,$zbattrib,$ownerUid);
	return $zbattrib;
}

function savezbAtrribToMem($cpets,$ack,$ack1,$zbattrib,$ownerUid=0){
	global $m;
	global $_pm;
	$ownerUid = intval($ownerUid);
	if($ownerUid < 1) $ownerUid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;

	if($cpets!=0){
		//$_SESSION['dbg_equip_attr'] .= date("Y-m-d H:i:s")."==========================================";
		if($ack===false&&$ack1===""){
			//$_SESSION['dbg_equip_attr'] .= $_SERVER['PHP_SELF']."=:"."User_bb_equip_info_a_".$cpets.'_'.$_SESSION['id']." a changed.<br/>";
			$_pm['mem']->set(array("k"=>"User_bb_equip_info_a_".$cpets.'_'.$ownerUid,"v"=>array($zbattrib,$ack.'_'.$ack1)));
		}else{
			$_pm['mem']->del("User_bb_equip_changed_".$cpets.'_'.$ownerUid);

			$_pm['mem']->set(array("k"=>"User_bb_equip_info_b_".$cpets.'_'.$ownerUid,"v"=>array($zbattrib,$ack.'_'.$ack1)));
		}
	}
}


/**
*@Usage: 获取毫秒级别的时间。
*@Return: float;
*/
function utime($inms){
    $utime = preg_match("/^(.*?) (.*?)$/", microtime(), $match);
    $utime = $match[2] + $match[1];
    if($inms){
        $utime *=  1000000;
    }
    return $utime;
}

/**
 * 判断任务是否处于 timeconfig 配置的开放时间内。
 * flags=0 为普通任务；flags=N 匹配 titles=task 且 days=N 的任一时间段。
 */
function taskScheduleIsActive($taskinfo, $timearr = null, $nowtime = null)
{
	if(!is_array($taskinfo)) return false;
	if(empty($taskinfo['flags'])) return true;

	if($nowtime === null) $nowtime = date('YmdHis');
	if(!preg_match('/^[0-9]{14}$/', (string)$nowtime)) return false;

	if($timearr === null)
	{
		global $_pm;
		if(!isset($_pm['mem']) || !is_object($_pm['mem'])) return false;
		$timearr = kdjlSafeMemValue($_pm['mem']->get(MEM_TIME_KEY), array());
	}
	if(!is_array($timearr)) return false;

	foreach($timearr as $timeinfo)
	{
		if(!is_array($timeinfo) || !isset($timeinfo['titles'], $timeinfo['days'], $timeinfo['starttime'], $timeinfo['endtime'])) continue;
		if($timeinfo['titles'] != 'task' || (string)$timeinfo['days'] !== (string)$taskinfo['flags']) continue;

		$starttime = (string)$timeinfo['starttime'];
		$endtime = (string)$timeinfo['endtime'];
		if(!preg_match('/^[0-9]{14}$/', $starttime) || !preg_match('/^[0-9]{14}$/', $endtime)) continue;
		if($starttime <= $endtime && $nowtime >= $starttime && $nowtime <= $endtime) return true;
	}

	return false;
}

/*
	接受任务
	$num 接爱NPC序号
	$taskid 玩家在完成的任务
*/
function taskcheck($taskid,$num)
{//如果他接了任务，那么第一条显示的就是他接的任务的标题。

	if(empty($num))
	{
        $ret = "数据错误！";
		return $ret;
		exit;
	}
	global $_pm,$_task,$user;
	$taskinfo = array();

	$m = $_pm['mem'];
	$nowtime = date("YmdHis");
	$timearr = kdjlSafeMemValue($_pm['mem']->get(MEM_TIME_KEY), array());
	$memtask = kdjlSafeMemValue($_pm['mem']->get(MEM_TASK_KEY), array());
	if(!empty($taskid))
	{
		/*$taskinfo = $_pm['mem']->dataGet(array('k'	=>	MEM_TASK_KEY,
										  'v'	=> "if(\$rs['id']=={$taskid}) \$ret=\$rs;"
									));*/
		$taskinfo = isset($memtask[$taskid]) ? $memtask[$taskid] : array();

	}
	/*define('TASKSTARTTIME',			'2009-04-01 00:00:00');
define('TASKENDTIME',			'2009-04-20 00:00:00');*/


	if (is_array($taskinfo))
	{
		// compose task ui.title=>detail
		if(taskScheduleIsActive($taskinfo, $timearr, $nowtime))
		{
			if ($taskinfo['oknpc'] == $num)
			{
				$colortag = array('[',
								  ']',
								  '{',
								  '}',
								  '(',
								  ')'
								  );
				$colorlist = array('<font color=#0000FF>',
								   '</font>',
								   '<font color=#848EF7>',
								   '</font>',
								   '<font color=#FF0000>',
								   '</font>'
								   );
				$taskinfo['title'] = str_replace($colortag, $colorlist, $taskinfo['title']);
				$title = str_replace($colortag, $colorlist, $taskinfo['title']);
				/*if($taskinfo['id'] == 179 || $taskinfo['id'] == 182 || $taskinfo['id'] == 185 || $taskinfo['id'] == 186)
				{
					$ret = "";
				}else{*/
					$ret[0][] = $taskinfo['id'];
					$ret[0][] = '<li class="r0'.$taskinfo['color'].'"><a href="#"><span onclick="javascript:OpenLogin(2,'.$taskinfo['id'].','.$num.','.($taskid?1:0).')">'.$taskinfo['title'].'</span></a></li>';
					$ret[0][] = $taskinfo['cid'];
				//}
			}
		}
	}
	//$task = unserialize($m->get(MEM_TASK_KEY));
	$taskArr = array();
	$rwlidarr = array();
	foreach($memtask as $v)
	{
		if(!is_array($v))
		{
			continue;
		}
		if(!taskScheduleIsActive($v, $timearr, $nowtime))
		{
			continue;
		}
		$taskCid = isset($v['cid']) ? strval($v['cid']) : '';
		$taskCidParts = explode(':', $taskCid, 2);
		$isSequenceTask = count($taskCidParts) === 2 && $taskCidParts[0] === 'rwl';
		$taskHide = isset($v['hide']) ? intval($v['hide']) : 0;
		if($taskHide != 1 && !$isSequenceTask)
		{
			continue;
		}
		$fnpcarr = explode("|",$v['fromnpc']);
		if($fnpcarr[0] == $num)
		{
			if(empty($v['cid']))
			{
				$sql = "SELECT taskid FROM tasklog WHERE uid = {$_SESSION['id']} and taskid = {$v['id']}";
				$checkarr = $_pm['mysql'] -> getOneRecord($sql);
				if(is_array($checkarr))
				{
					continue;
				}
				else
				{
					$npcNum = explode("|",$v['fromnpc']);
					$colortag = array('[',
													  ']',
													  '{',
													  '}',
													  '(',
													  ')'
													  );
					$colorlist = array('<font color=#0000FF>',
												   '</font>',
												   '<font color=#848EF7>',
												   '</font>',
												   '<font color=#FF0000>',
												   '</font>'
												   );
					$v['title'] = str_replace($colortag, $colorlist, $v['title']);
					$ret[$npcNum[1]][] = $v['id'];
					$ret[$npcNum[1]][] = '<li class="r0'.$v['color'].'"><a href="#"><span onclick="javascript:OpenLogin(1,'.$v['id'].','.$num.','.($taskid?1:0).')">'.$v['title'].'</span></a></li>';
					$ret[$npcNum[1]][] = $v['cid'];
				}
			}
			else
			{
				$cidarr = explode(":",$v['cid']);
				if($cidarr[0] == "rwl")
				{
					$arr = explode("|",$cidarr[1]);
					if(isset($rwlidarr[$v['xulie']]) && is_array($rwlidarr[$v['xulie']]))
					{
						if(isset($arr[0]) && !in_array($arr[0],$rwlidarr[$v['xulie']]) && !empty($arr[0]))
						{
							$rwlidarr[$v['xulie']][] = $arr[0];
						}
						if(isset($arr[1]) && !in_array($arr[1],$rwlidarr[$v['xulie']]) && !empty($arr[1]))
						{
							$rwlidarr[$v['xulie']][] = $arr[1];
						}
					}
					else
					{
						if(isset($arr[0]) && !empty($arr[0]))
						{
							$rwlidarr[$v['xulie']][] = $arr[0];
						}
						if(isset($arr[1]) && !empty($arr[1]))
						{
							$rwlidarr[$v['xulie']][] = $arr[1];
						}
					}
				}
				else if ($cidarr[0] == "paihang")
				{
					if($cidarr[1] != $user['paihang'])
					{
						continue;
					}
					else
					{
						$npcNum = explode("|",$v['fromnpc']);
						$colortag = array('[',
														  ']',
														  '{',
														  '}',
														  '(',
														  ')'
														  );
						$colorlist = array('<font color=#0000FF>',
														   '</font>',
														   '<font color=#848EF7>',
														   '</font>',
														   '<font color=#FF0000>',
														   '</font>'
														   );
						$v['title'] = str_replace($colortag, $colorlist, $v['title']);
						$ret[$npcNum[1]][] = $v['id'];
						$ret[$npcNum[1]][] = '<li class="r0'.$v['color'].'"><a href="#"><span onclick="javascript:OpenLogin(1,'.$v['id'].','.$num.','.($taskid?1:0).')">'.$v['title'].'</span><a href="#"></li>';
						$ret[$npcNum[1]][] = $v['cid'];
					}
				}
				else
				{
					if($v['hide'] == 1 && $v['id'] != $taskid)
					{
						$npcNum = explode("|",$v['fromnpc']);
						$colortag = array('[',
														  ']',
														  '{',
														  '}',
														  '(',
														  ')'
														  );
						$colorlist = array('<font color=#0000FF>',
														   '</font>',
														   '<font color=#848EF7>',
														   '</font>',
														   '<font color=#FF0000>',
														   '</font>'
														   );
						$v['title'] = str_replace($colortag, $colorlist, $v['title']);
						$ret[$npcNum[1]][] = $v['id'];
						$ret[$npcNum[1]][] = '<li class="r0'.$v['color'].'"><a href="#"><span onclick="javascript:OpenLogin(1,'.$v['id'].','.$num.','.($taskid?1:0).')">'.$v['title'].'</span></a></li>';
						$ret[$npcNum[1]][] = $v['cid'];
					}
				}
			}
		}
	}
	//$arrcount = count($rwlidarr) - 1;echo $arrcount;exit;
	//print_r($rwlidarr);exit;
	if(is_array($rwlidarr)){
		foreach($rwlidarr as $k => $v){
			$result = '';
			$result = $_pm['mysql'] -> getOneRecord("SELECT * FROM tasklog WHERE uid = {$_SESSION['id']} and xulie = {$k} and fromnpc = {$num}");
			if(!is_array($result)){//没有做过这个序列的任务
				$tarr = $memtask[$v[0]];
				if($tarr['hide'] == 2){
					continue;
				}else{
					$npcNum = explode("|",$tarr['fromnpc']);
					$colortag = array('[',
													  ']',
													  '{',
													  '}',
													  '(',
													  ')'
													  );
					$colorlist = array('<font color=#0000FF>',
													   '</font>',
													   '<font color=#848EF7>',
													   '</font>',
													   '<font color=#FF0000>',
													   '</font>'
													   );
					$tarr['title'] = str_replace($colortag, $colorlist, $tarr['title']);
					$ret[$npcNum[1]][] = $tarr['id'];
					$ret[$npcNum[1]][] = '<li class="r0'.$tarr['color'].'"><a href="#"><span onclick="javascript:OpenLogin(1,'.$tarr['id'].','.$num.','.($taskid?1:0).')">'.$tarr['title'].'</span></a></li>';
					$ret[$npcNum[1]][] = $tarr['cid'];
				}
			}
			else{
				//做过这个序列的任务
				foreach($v as $vv){
					if($vv == $result['taskid']){
						if(!isset($memtask[$result['taskid']]) || !is_array($memtask[$result['taskid']])){
							continue;
						}
						$doneTask = $memtask[$result['taskid']];
						$doneCid = explode(':',$doneTask['cid'],2);
						if(count($doneCid) < 2 || $doneCid[0] != 'rwl'){
							continue;
						}
						$nextArr = explode('|',$doneCid[1]);
						$nextTaskId = isset($nextArr[1]) ? intval($nextArr[1]) : 0;
						if($nextTaskId < 1 || !isset($memtask[$nextTaskId]) || !is_array($memtask[$nextTaskId])){
							continue;
						}
						$tarr = $memtask[$nextTaskId];
						if(!taskScheduleIsActive($tarr, $timearr, $nowtime)){
							continue;
						}

						$npcNum = explode("|",$tarr['fromnpc']);
						$colortag = array('[',
														  ']',
														  '{',
														  '}',
														  '(',
														  ')'
														  );
						$colorlist = array('<font color=#0000FF>',
														   '</font>',
														   '<font color=#848EF7>',
														   '</font>',
														   '<font color=#FF0000>',
														   '</font>'
														   );
						$tarr['title'] = str_replace($colortag, $colorlist, $tarr['title']);
						$ret[$npcNum[1]][] = $tarr['id'];
						$ret[$npcNum[1]][] = '<li class="r0'.$tarr['color'].'"><a href="#"><span onclick="javascript:OpenLogin(1,'.$tarr['id'].','.$num.','.($taskid?1:0).')">'.$tarr['title'].'</span></a></li>';
						$ret[$npcNum[1]][] = $tarr['cid'];
					}
				}
			}
		}
	}
	/*for($i = 1;$i < 100;$i++)
	{
		if(!is_array($rwlidarr[$i])) break;
		if(!in_array($taskid,$rwlidarr[$i]))
		{
			$sql = "SELECT * FROM tasklog WHERE uid = {$_SESSION['id']} and xulie = {$i} and fromnpc = {$num}";
			$result = $_pm['mysql'] -> getOneRecord($sql);
			if(is_array($result))
			{
				$taskinfo = $memtask[$result['taskid']];
				$a = explode("|",$taskinfo['cid']);
				if(empty($a[1]) || !is_numeric($a[1]))
				{
					continue;
				}
				$taskinfo = $memtask[$a[1]];

				if(is_array($taskinfo))
				{
					$checknum = 10;
					if(!empty($taskinfo['flags']))
					{
						foreach($taskcheckarr as $fv)
						{
							if($fv['days'] == $taskinfo['flags'] && $nowtime >= $fv['starttime'] && $nowtime <= $fv['endtime'])
							{
								$checknum = 11;
							}
						}
					}
					if($checknum != 10)
					{
						continue;
					}
					$npcNum = explode("|",$taskinfo['fromnpc']);
					if($npcNum[0] == $num)
					{
						$colortag = array('[',
															']',
														  '{',
														  '}',
														  '(',
														  ')'
															  );
						$colorlist = array('<font color=#0000FF>',
															   '</font>',
															   '<font color=#848EF7>',
															   '</font>',
															   '<font color=#FF0000>',
															   '</font>'
															   );
						$taskinfo['title'] = str_replace($colortag, $colorlist, $taskinfo['title']);
						$ret[$npcNum[1]][] = $taskinfo['id'];
						$ret[$npcNum[1]][] = '<li class="r0'.$taskinfo['color'].'"><a href="#"><span onclick="javascript:OpenLogin(1,'.$taskinfo['id'].','.$num.','.($taskid?1:0).')">'.$taskinfo['title'].'</span></a></li>';
						$ret[$npcNum[1]][] = $taskinfo['cid'];
					}
				}
			}
			else
			{
				foreach($memtask as $t)
				{
					if($t['xulie'] == $i && $t['hide'] == 1)
					{
						$npcNum = explode("|",$t['fromnpc']);
						if($npcNum[0] == $num)
						{
							$checknum = 10;
							if(!empty($t['flags']))
							{
								foreach($taskcheckarr as $fv)
								{
									if($fv['days'] == $t['flags'] && $nowtime >= $fv['starttime'] && $nowtime <= $fv['endtime'])
									{
										$checknum = 11;
									}
								}
							}
							if($checknum != 10)
							{
								continue;
							}
							$colortag = array('[',
															']',
														  '{',
														  '}',
														  '(',
														  ')'
														  );
							$colorlist = array('<font color=#0000FF>',
															   '</font>',
															   '<font color=#848EF7>',
															   '</font>',
															   '<font color=#FF0000>',
															   '</font>'
															   );
							$t['title'] = str_replace($colortag, $colorlist, $t['title']);
							$ret[$npcNum[1]][] = $t['id'];
							$ret[$npcNum[1]][] = '<li class="r0'.$t['color'].'"><a href="#"><span onclick="javascript:OpenLogin(1,'.$t['id'].','.$num.','.($taskid?1:0).')">'.$t['title'].'</span></a></li>';
							$ret[$npcNum[1]][] = $t['cid'];
						}
					}
				}
			}
		}
	}
	*/
	$array = BubbleSort($ret);
	$str = show($array);
	#########################加入卡片提示########################
	if($num == 3){
		$str .= card();
		$msgs = zhaohui();
		$str .= newcard();
		if($msgs)
		{

				$msgs_str = substr($msgs,1);
				$array = explode(',', $msgs_str);//Array ( [0] => 35 [1] => 34 )
				foreach($array as $key => $value)
				{

					$a='<span onclick="javascript:showPrizeListDiv(\''.$value.'\');" style="cursor:pointer;"><b>领取回归奖品</b></span><br />';
					$str .= $a;
				}


		}
	}
	if(empty($str))
	{
		$str = $_task['dlg'][$num];
	}
	return $str;
}



//任务显示
function show($arr)
{
	$string = '';
	if(is_array($arr))
	{
		foreach($arr as $k => $v)
		{
			$string .= $v[1];
		}
		return $string;
	}
}

//按键值的大小重组数组
//$str 要处理的数组
function BubbleSort($str)
{
	$newarr = array();
	$arrs = array();
	if(!is_array($str))
	{
		return $arrs;
	}
	foreach($str as $k => $v)
	{
		if(in_array($k,$newarr))
		{
			continue;
		}
		$newarr[] = $k;
	}
    for ($i=0;$i<count($newarr);$i++)
    {
		for($j=count($newarr)-2;$j>=$i;$j--)
        {
             if($newarr[$j+1]<$newarr[$j])
             {
	$tmp = $newarr[$j+1];
                $newarr[$j+1]=$newarr[$j];
                $newarr[$j]=$tmp;
             }
        }
    }
	foreach($newarr as $v)
	{
		$arrs[$v] = $str[$v];
	}
        return $arrs;
}

//取得
function getBaseTaskInfoById($id){
	global $_pm;
	$id = intval($id);
	if($id < 1) return false;
	$gpcInfo = kdjlSafeMemValue($_pm['mem']-> get("base_task_info_".$id), false);
	if(is_array($gpcInfo)){
		return $gpcInfo;
	}
	$sql = "SELECT * FROM task WHERE id='{$id}'";
	$rs = $_pm['mysql'] -> getOneRecord($sql);
	if(!is_array($rs)) return false;
	$arr = array();
	$arr['k'] = "base_task_info_".$id;
	$arr['v'] = $rs;
	$_pm['mem'] -> setArr($arr);
	return $rs;
}




//显示任务的具体内容
//$taskid 要显示内容的任务ID
function taskdiv($taskid,$npc,$op,$ifshow)
{
	global $_pm,$_task;
	$m = $_pm['mem'];
	$str = '';
	$str_jin = '';
	$resultstr = '';
	$props = array();
	$_gpcid = array();
	$div1 = '';
	//$task = unserialize($m->get(MEM_TASK_KEY));
	$user = $_pm['user']->getUserById($_SESSION['id']);
	//$_gpc	 = unserialize($m->get(MEM_GPC_KEY));
	//$_gpcid  = unserialize($m->get('db_gpcid'));
	//$props = unserialize($m->get("db_propsid"));
	//if(!is_array($task))
	//{
		//$div = $_task['dlg'][$npc];
	//}
	//$divv = $task[$taskid];
	$divv = getBaseTaskInfoById($taskid);
	if(!is_array($divv))
	{
		return '';
	}
	//$div = '<h2 style="text-align:center;">'.$divv['title'].'</h2>';
	$div = '<h2 style="text-align:left;">任务描述：</h2>';

	//foreach($task as $divv)
	//{
		//if($divv['id'] == $taskid)
		//{
			$colortag = array('[',
			                  ']',
							  '{',
							  '}',
							  '(',
							  ')'
							  );
			$colorlist = array('<font color=#0000FF>',
							   '</font>',
							   '<font color=#848EF7>',
				               '</font>',
				               '<font color=#0000FF>',
							   '</font>'
							   );
			$frommsg = str_replace($colortag, $colorlist, $divv['frommsg']);
			$okmsg = str_replace($colortag, $colorlist, $divv['okmsg']);
			//$div .= $divv['title']."<br />";
			if($op == 1)
			{
				$duihua = $frommsg."<br />";
			}
			else if($op == 2)
			{
				$duihua = $okmsg."<br />";
			}
			//$oknpc = str_replace(array(10,11,12,13,1,2,3,4,5,6,7,8,9),$_task['npc'],$divv['oknpc']);
			$oknpc = $_task['oknpc'][$divv['oknpc']];
			$div .= "接受地点:任务使者<br />";
			$div .= "接受对话:<br />".$duihua."<br />";
			$des = neednpc($divv['okneed']);//see:3,killmon:3|4|5:2,killmon:20|21|22:2,killmon:37|38|39:2,killmon:54|55|56:2,killmon:71|72:2
			//Array ( [killmon] => Array ( [2] => Array ( [0] => 71 [1] => 72 ) ) )
			//任务目标
			if(is_array($des))
			{
				foreach($des as $k => $v)
				{
					switch($k)
					{
                        case "item":
                            foreach($v as $item)
                            {
                                foreach($item as $ik => $iv)
                                {
                                    foreach($iv as $ivv)
                                    {
                                        $props[$ivv] = getBasePropsInfoById($ivv);
										$p['name'] = is_array($props[$ivv]) && isset($props[$ivv]['name']) ? $props[$ivv]['name'] : $ivv;
										$str .= "收集".$p['name']."&nbsp;".$ik."个<br />";
                                    }
                                }
                            }
                            break;
						case "money":
							$str .= "需要金币：".$v[0]."个<br />";
							break;
						case "ww":
							$str .= "需要威望：".$v[0]."点<br />";
							break;
						case "jifen":
							$str .= "需要积分：".$v[0]."点<br />";
							break;
						case "dianjuan":
							$str .= "需要交纳点卷：".$v[0]."点<br />";
							break;
						case "lv":
							$lvarr = explode("|",$v[0]);
							$lvarr[1] = isset($lvarr[1]) ? $lvarr[1] : 0;
							if($lvarr[1] == 0)
							{
								$str .= "需要等级：".$lvarr[0]."级以上<br />";
							}
							else
							{
								$str .= "需要等级：".$lvarr[0]."-".$lvarr[1]."级<br />";
							}
							break;
						case "killmon":
							foreach($v as $kss => $kill)
							{
								$str1 = "";
								$targetNames = array();
								foreach($kill as $vss)
								{
									$_gpcid[$vss] = getBaseGpcInfoById($vss);
									$g = $_gpcid[$vss];
									if(!in_array($g['name'], $targetNames, true))
									{
										$targetNames[] = $g['name'];
										if(!empty($str1))
										{
											$str1 .= "、".$g['name'];
										}
										else
										{
											$str1 = $g['name'];
										}
									}
								}
								$gcpnum = explode(",",$kss);
								$str .= "杀死怪物：".$str1."&nbsp;".$gcpnum[0]."个<br />";
							}
							break;
					}
				}
			//	$div .= "任务目标：<br />".$str;
				$div .= "<h2 style='text-align:left;'>任务目标：</h2><br />".$str;
			}

			#########
			//任务进度
            if($op == 2 && is_array($des))
            {
                $str_jin = "";
				$taskStateCounts = array();
				$acceptedTask = $_pm['mysql']->getOneRecord("SELECT state FROM task_accept WHERE uid={$_SESSION['id']} AND taskid=".intval($taskid)." ORDER BY id DESC LIMIT 1");
				$taskState = is_array($acceptedTask) && isset($acceptedTask['state']) ? strval($acceptedTask['state']) : '';
				if($taskState !== '')
				{
					foreach(explode(',', $taskState) as $statePart)
					{
						$stateInfo = explode(':', $statePart);
						if(count($stateInfo) >= 3 && $stateInfo[0] === 'killmon')
						{
							$stateIds = array();
							foreach(explode('|', $stateInfo[1]) as $stateGid)
							{
								$stateGid = intval($stateGid);
								if($stateGid > 0) $stateIds[] = $stateGid;
							}
							sort($stateIds, SORT_NUMERIC);
							if(!empty($stateIds)) $taskStateCounts[implode('|', $stateIds)] = max(0, intval($stateInfo[2]));
						}
					}
				}
                foreach($des as $k => $v)
                {
                    if($k == "item")
                    {
                        foreach($v as $item)
                        {
                            foreach($item as $needCount => $pidList)
                            {
                                foreach($pidList as $pid)
                                {
                                    $rs = $_pm['mysql']->getOneRecord("SELECT sum(sums) as cnt FROM userbag WHERE pid={$pid} and uid={$_SESSION['id']} and sums>0 and zbing=0 and (cantrade IS NULL OR cantrade<>3)");
                                    $got = is_array($rs) && intval($rs['cnt']) > 0 ? intval($rs['cnt']) : 0;
                                    if(!isset($props[$pid])) $props[$pid] = getBasePropsInfoById($pid);
                                    $pname = is_array($props[$pid]) ? $props[$pid]['name'] : $pid;
									$jindu = ($got >= $needCount) ? "已完成" : "未完成";
									$str_jin .= "收集".$pname."（".$got."/".$needCount."）".$jindu."<br />";
                                }
                            }
                        }
                    }
                    else if($k == "money")
                    {
						$jindu = ($user['money'] >= $v[0]) ? "已完成" : "未完成";
						$str_jin .= "需要金币：".$v[0]."个，当前金币：".$user['money']."个，".$jindu."<br />";
                    }
                    else if($k == "ww")
                    {
						$jindu = ($user['prestige'] >= $v[0]) ? "已完成" : "未完成";
						$str_jin .= "需要威望：".$v[0]."点，当前威望：".$user['prestige']."点，".$jindu."<br />";
                    }
                    else if($k == "jifen")
                    {
						$jindu = ($user['score'] >= $v[0]) ? "已完成" : "未完成";
						$str_jin .= "需要积分：".$v[0]."点，当前积分：".$user['score']."点，".$jindu."<br />";
                    }
                    else if($k == "dianjuan")
                    {
						$jindu = ($user['active_score'] >= $v[0]) ? "已完成" : "未完成";
						$str_jin .= "需要交纳点卷：".$v[0]."点，当前点卷：".$user['active_score']."点，".$jindu."<br />";
                    }
					else if($k == "lv")
					{
						$lvarr = explode('|', $v[0]);
						$lvarr[1] = isset($lvarr[1]) ? intval($lvarr[1]) : 0;
						$blevel = 0;
						if(!empty($user['mbid']))
						{
							$petsAll = $_pm['user']->getUserPetById($_SESSION['id']);
							if(is_array($petsAll))
							{
								foreach($petsAll as $pet)
								{
									if(isset($pet['id']) && intval($pet['id']) == intval($user['mbid']))
									{
										$blevel = isset($pet['level']) ? intval($pet['level']) : 0;
										break;
									}
								}
							}
						}
						if($lvarr[1] == 0)
						{
							$str_jin .= "需要等级：".intval($lvarr[0])."级以上，当前主宠等级：".$blevel."级<br />";
						}
						else
						{
							$str_jin .= "需要等级：".intval($lvarr[0])."-".$lvarr[1]."级，当前主宠等级：".$blevel."级<br />";
						}
					}
                    else if($k == "killmon")
                    {
                        foreach($v as $needCount => $kill)
                        {
                            $names = array();
							$killIds = array();
                            foreach($kill as $gid)
                            {
								$gid = intval($gid);
								if($gid > 0) $killIds[] = $gid;
                                if(!isset($_gpcid[$gid])) $_gpcid[$gid] = getBaseGpcInfoById($gid);
                                if(is_array($_gpcid[$gid]) && !in_array($_gpcid[$gid]['name'], $names, true)) $names[] = $_gpcid[$gid]['name'];
                            }
							$needParts = explode(',', $needCount);
							sort($killIds, SORT_NUMERIC);
							$killStateKey = implode('|', $killIds);
							$doneCount = isset($taskStateCounts[$killStateKey]) ? intval($taskStateCounts[$killStateKey]) : 0;
							$str_jin .= "杀死怪物：".implode("、", $names)."，需要".intval($needParts[0])."个(".$doneCount."/".intval($needParts[0]).")<br />";
                        }
                    }
                }
				$div .= "<h2 style='text-align:left;'>完成进度：</h2><br />".$str_jin;
            }
			$resultarr = result($divv['result']);
			if(is_array($resultarr))
			{
				foreach($resultarr as $k => $v)
				{
					switch($k)
					{
                        case "item":
							$resultstr .= "随机获得道具<br />";
                            break;
                        case "lvprops":
                            foreach($v as $ks => $vs)
                            {
                                foreach($vs as $kkk => $pr)
                                {
                                    $props[$kkk] = getBasePropsInfoById($kkk);
                                    $pname = is_array($props[$kkk]) ? $props[$kkk]['name'] : $kkk;
									$resultstr .= "等级在".$ks."时获得".$pname."×".$pr."<br />";
                                }
                            }
                            break;
                        case "props":
                            foreach($v as $ks => $vs)
                            {
                                foreach($vs as $pr)
                                {
                                    $props[$pr] = getBasePropsInfoById($pr);
                                    $pname = is_array($props[$pr]) ? $props[$pr]['name'] : $pr;
                                    $propsnum = explode(',',$ks);
									$resultstr .= "获得物品：".$pname."×".$propsnum[0]."<br />";
                                }
                            }
                            break;
                        case "bprops":
                            foreach($v as $ks => $vs)
                            {
                                foreach($vs as $pr)
                                {
                                    $props[$pr] = getBasePropsInfoById($pr);
                                    $pname = is_array($props[$pr]) ? $props[$pr]['name'] : $pr;
                                    $propsnum = explode(',',$ks);
									$resultstr .= "获得可交易物品：".$pname."×".$propsnum[0]."<br />";
                                }
                            }
                            break;
						case "exp":
							foreach($v as $ks => $vs)
							{
								if(is_numeric($ks))
								{
									$resultstr .= "获得经验：".$vs."<br />";
								}
								else
								{
									$arrs = explode("-",$ks);
									if(empty($arrs[1]))
									{
										$resultstr .= "当交纳的威望大于".$arrs[0]."时获得经验：".$vs."<br />";
									}
									else
									{
										$resultstr .= "当交纳的威望在".$ks."间获得经验：".$vs."<br />";
									}
								}
							}
							break;
						case "mon":
							foreach($v as $ks => $vs)
							{
								if(is_numeric($ks))
								{
									$resultstr .= "获得金币：".$vs."<br />";
								}
								else
								{
									$arrs = explode("-",$ks);
									if(empty($arrs[1]))
									{
										$resultstr .= "当交纳的威望大于".$arrs[0]."时获得金币：".$vs."<br />";
									}
									else
									{
										$resultstr .= "当交纳的威望在".$ks."间获得金币：".$vs."<br />";
									}
								}
							}
							break;
					}
				}
			}
			$div .= "<h2 style='text-align:left;'>任务奖励：</h2><br />".$resultstr;
			$ifshow = (isset($_GET['ifshow']) && !is_array($_GET['ifshow'])) ? intval($_GET['ifshow']) : 0;
			//$div.='$op='.$op.',$ifshow='.$ifshow.'<br/>';
			if($op == 2)//完成
			{
				if($ifshow==1)//接受 完成 放弃gettask  complatetask  offtask
				{
					//屏蔽接受按钮
					$div1 = "<div class='btn' align='center'>
			   <input type='button' class='b' disabled='disabled'  id='taskbtn1' style='cursor:pointer;color:green;' onclick=\"javascript:gettask('taskid={$divv['id']}');window.parent.oPopup.hide();\" value='接受'>
			  <input type='button' class='c' id='taskbtn2' onclick=\"javascript:complatetask('n={$npc}&taskid={$divv['id']}');window.parent.oPopup.hide();\" value='完成'>
			  ";
				}
				else
				{
					//只有一个放弃按钮
					$div1 = "<div class='btn' align='center'>
			   <input type='button' class='b' disabled='disabled'  id='taskbtn1' value='接受'>
			  <input type='button' class='b' disabled='disabled'  id='taskbtn2' value='完成'>
			  ";
				}
				$div1 .= "<input type='button' class='c' id='taskbtn3'  style='cursor:pointer;color:green;' onclick=\"javascript:offtask('taskid={$divv['id']}');window.parent.oPopup.hide();\" value='放弃'>
		  </div>";
			}
			else if($op == 1)//接受
			{
				if($ifshow==3)
				{
					//显示放弃按钮 已接
					$div1 = "<div class='btn' align='center'>
			   <input type='button' class='b' disabled='disabled'  id='taskbtn1' value='接受'>
			  <input type='button' class='b' disabled='disabled'  id='taskbtn2' value='完成'>
			   <input type='button' class='c' id='taskbtn3'  style='cursor:pointer;color:green;' onclick=\"javascript:offtask('taskid={$divv['id']}');window.parent.oPopup.hide();\" value='放弃'>
		  </div>";
				}
				else if($ifshow==4)
				{
					//显示接受按钮 可接
					$div1 = "<div class='btn' align='center'>
			   <input type='button' class='c' style='cursor:pointer;color:green;' onclick=\"javascript:gettask('taskid={$divv['id']}');window.parent.oPopup.hide();\" value='接受'>
			  <input type='button' class='b' disabled='disabled'  id='taskbtn2' value='完成'>
			  <input type='button' class='b' disabled='disabled'  id='taskbtn3' value='放弃'>
		  </div>";
				}
				else
				{
					//显示关闭按钮 不可接
					$div1 = "<div class='btn' align='center'>
			   <input type='button' class='b' disabled='disabled'  id='taskbtn1' value='接受'>
			  <input type='button' class='b' disabled='disabled'  id='taskbtn2' value='完成'>
			  <input type='button' class='b' disabled='disabled'  id='taskbtn3' value='放弃'>
		  </div>";

				}

			}
	echo '
	<h2 style="text-align:center;">'.$divv['title'].'</h2>
	  <div class="task_info" style="overflow-x:hidden; overflow-y:auto; scrollbar-arrow-color:#ffffff;scrollbar-face-color:#e1d395; scrollbar-darkshadow-color:#e1d395; scrollbar-base-color:#f3edc9; scrollbar-highlight-color:#f3edc9; scrollbar-shadow-color:#f3edc9; scrollbar-track-color:#f3edc9; scrollbar-3dlight-color:#e1d395;height:280px;padding-left:10px;padding-right:18px;">

			  '.$div.'
			</div>'.$div1.'<div class="tip02" id="do_task" style="text-align:center;color:red;"></div>';
}


//完成NPC条件
//$str 字符串
function neednpc($str)
{
	$ret = array();
	$i = 0;
	$killmonarr = array();
	if(!is_string($str) || $str === '') return $ret;
	$arr = explode(",",$str);
	foreach($arr as $v)
	{
		$newarr = explode(":",$v);//see:3,killmon:3|4|5:2,killmon:20|21|22:2,killmon:37|38|39:2,killmon:54|55|56:2,killmon:71|72:2
		if(!isset($newarr[0]) || $newarr[0] === '') continue;
		if($newarr[0] === 'giveitm') $newarr[0] = 'giveitem';
		switch ($newarr[0])
		{
			case "giveitem":
				if(count($newarr) < 3) break;
				$itemarr = explode("|",$newarr[1]);
				$ret['item'][][$newarr[2]] = $itemarr;
				break;
			case "givemoney":
				if(!isset($newarr[1])) break;
				$ret['money'][] =  $newarr[1];
				break;
			case "giveww":
				if(!isset($newarr[1])) break;
				$ret['ww'][] =  $newarr[1];
				break;
			case "givejifen":
				if(!isset($newarr[1])) break;
				$ret['jifen'][] =  $newarr[1];
				break;
			case "givedianjuan":
				if(!isset($newarr[1])) break;
				$ret['dianjuan'][] =  $newarr[1];
				break;
			case "killmon":
				if(count($newarr) < 3) break;
				$killmonarr[$newarr[2].",".$i] = explode("|",$newarr[1]);//Array ( [killmon] => Array ( [2,0] => Array ( [0] => 3 [1] => 4 [2] => 5 ) [2,1] => Array ( [0] => 20 [1] => 21 [2] => 22 ) [2,2] => Array ( [0] => 37 [1] => 38 [2] => 39 ) [2,3] => Array ( [0] => 54 [1] => 55 [2] => 56 ) [2,4] => Array ( [0] => 71 [1] => 72 ) ) )
				foreach($killmonarr as $k => $v)
				{
					if(empty($v))
					{
						continue;
					}
					$ret['killmon'][$k] = $v;
				}
				$i++;
				break;
			case "monself":
				if(!isset($newarr[1])) break;
				$ret['monself'][] =  $newarr[1];
				break;
			case "lv":
				if(!isset($newarr[1])) break;
				$ret['lv'][] =  $newarr[1];
				break;
			case "givevip":
				if(!isset($newarr[1])) break;
				$ret['givevip'][] =  $newarr[1];
				break;
		}
	}
	return $ret;
}

//完成任务获得奖励
function result($str)
{
	$ret = array();
	$j = 0;
	$mm = 0;
	$propsarr = array();
	$bpropsarr = array();
	if(!is_string($str) || $str === '') return $ret;
	$arr = explode(",",$str);
	foreach($arr as $v)
	{
		$newarr = explode(":",$v);
		if(!isset($newarr[0]) || $newarr[0] === '') continue;
		$resultType = strtolower(trim($newarr[0]));
		switch ($resultType)
		{
			case "lvprops":
				if(count($newarr) < 4) break;
				$lvarr = explode('|',$newarr[3]);
				if(count($lvarr) < 2) break;
				$ret['lvprops'][$lvarr[0].'-'.$lvarr[1]][$newarr[1]] = $newarr[2];
				break;
			case "exp":
				if(!isset($newarr[1])) break;
				$exparr = explode("|",$v);
				if(count($exparr) == 1)
				{
					$ret['exp'][] = $newarr['1'];
				}
				else if(count($exparr) == 2)
				{
					$ar = explode(":",$exparr[0]);
					$key = $ar[2]."-".$exparr[1];
					$ret['exp'][$key] = $ar[1];
				}
				break;
			case "money":
				if(!isset($newarr[1])) break;
				$monarr = explode("|",$v);
				if(count($monarr) == 1)
				{
					$ret['mon'][] = $newarr['1'];
				}
				else if(count($monarr) == 2)
				{
					$monar = explode(":",$monarr[0]);
					$key = $monar[2]."-".$monarr[1];
					$ret['mon'][$key] = $monar[1];
				}
				break;
			case "props":
				if(count($newarr) < 3) break;
				$propsarr[$newarr[2].",".$j] = explode("|",$newarr[1]);//exp:61,props:1387:1,props:1395:1,props:1396:1,props:1397:1
				foreach($propsarr as $k => $v)
				{
					if(empty($v))
					{
						continue;
					}
					$ret['props'][$k] = $v;
				}
				$j++;
				break;
			case "bprops":
				//$newarrArray ( [0] => bprops [1] => 1415 [2] => 1 )
				if(count($newarr) < 3) break;
				$bpropsarr[$newarr[2].",".$mm] = explode("|",$newarr[1]);//exp:61,props:1387:1,bprops:1395:1,props:1396:1,props:1397:1
				foreach($bpropsarr as $k => $v)
				{
					if(empty($v))
					{
						continue;
					}
					$ret['bprops'][$k] = $v;
				}
				$mm++;
				break;
			case "itemrand":
				$itemarr = explode("|",$v);
				foreach($itemarr as $itemar)
				{
					$item = explode(":",$itemar);
					if(count($item) == 4)
					{
						$ret['item'][$item[1]]['jl'] = $item[2];
						$ret['item'][$item[1]]['num'] = $item[3];
					}
					if(count($item) == 3)
					{
						$ret['item'][$item[0]]['jl'] = $item[1];
						$ret['item'][$item[0]]['num'] = $item[2];
					}
				}
				break;

		}
	}
	return $ret;
}

function getTemplate($file)
{
	if(!isset($GLOBALS['_pm']['mem']))
	{
		$GLOBALS['_pm']['mem'] = new memory();
	}

	if(!file_exists($file))
	{
		return false;
	}

	$filetime = filemtime($file);
	$key = preg_replace("/[^\w]/","_",$file);

	$data = kdjlSafeMemValue($GLOBALS['_pm']['mem']->get($key), array());
	if(!$data || !isset($data[0]) || $data[0]<$filetime)
	{
		$data[0] = $filetime;
		$data[1] = file_get_contents($file);
		$GLOBALS['_pm']['mem']->set(array('k'=>$key,'v'=>$data));
	}
	return $data[1];
}





//战斗用到的函数和类
//==============================================================
function getProps($str){
	if ($str == '') return false;
	if (strstr($str, ',') === false){ // Only one props.
		$arr = explode(":", $str);
		$pid = isset($arr[0]) ? intval($arr[0]) : 0;
		$rate = isset($arr[1]) ? intval($arr[1]) : 0;
		if ($pid < 1 || $rate < 1) return false;
		seek();
		if (rand(1, $rate) <= 1)
		{
			return $pid;
		}else return false;
	}
	else	// for more than one props.
	{
		$ret = '';
		$arr = explode(",", $str);
		seek();
		foreach ($arr as $k => $v)
		{
			if ($v == '' or strstr($v, ":")===false ) continue;
			$temp = explode(":", $v);
			$pid = isset($temp[0]) ? intval($temp[0]) : 0;
			$rate = isset($temp[1]) ? intval($temp[1]) : 0;
			if ($pid < 1 || $rate < 1) continue;
			if (rand(1, $rate) <=1) $ret .= $pid.',';
		}
		//
		$ret = substr($ret, 0, -1);
		return $ret?$ret:false;
	}
}

function seek(){
		list($usec, $sec) = explode(' ', microtime());
		$t = (float) $sec + ((float) $usec * 100000);
		srand($t);
}
/**
* @Usage: 存储用户得到的道具到用户包裹.
* @Param: String, format: 1,2,3
* @Logic:
  如果用户包裹有此物品，如果可以折叠，直接累加，否则插入新纪录。
  >>增加物品说明字段
*/
function saveGetPropsa($idlist,$_userid=0)
{
	if($_userid==0)
	{
		$_userid=$_SESSION["id"];
	}
	if ($idlist == '' or $idlist == 0) return false;
	global $_pm;//,$user;

	$user		= $_pm['user']->getUserById($_userid);
	if (!is_array($user) || !isset($user['maxbag'])) return false;

	$arrobj = new arrays();

	/*$l=0;
	if (is_array($_bag))
	{
		foreach ($_bag as $x => $y)
		{
			if ($y['sums']>0 && $y['zbing']==0) $l++;
		}
	}*/

	$_userid = intval($_userid);
	$maxbag = intval($user['maxbag']);
	$l= $_pm['mysql'] -> getOneRecord("SELECT count(id) as sums FROM userbag WHERE sums > 0 and zbing = 0 and uid = ".$_userid);
	$bagUsed = is_array($l) ? intval($l['sums']) : 0;
	if ($bagUsed >= $maxbag) return false;

	$arr = explode(',', $idlist);
	$validArr = array();
	$newStackable = array();
	$needSlots = 0;
	foreach ($arr as $k => $v)
	{
		if($v=='') continue;
		$v = intval($v);
		if($v < 1) continue;
		$rs = $_pm['mysql']->getOneRecord("SELECT id,vary,sell,sums FROM userbag WHERE uid=".$_userid." and pid={$v} and vary=1 and zbing=0 and (cantrade IS NULL OR cantrade<>3) ORDER BY IF(sums>0,1,0) DESC, id ASC LIMIT 1");
		if (is_array($rs))
		{
			if (intval($rs['vary']) != 1)
			{
				$needSlots++;
			}
			else if (intval($rs['sums']) <= 0 && !isset($newStackable[$v]))
			{
				$needSlots++;
				$newStackable[$v] = 1;
			}
		}
		else
		{
			$mempropsid[$v] = getBasePropsInfoById($v);
			$rs = $mempropsid[$v];
			if (!is_array($rs)) return false;
			if (intval($rs['vary']) == 1)
			{
				if (!isset($newStackable[$v]))
				{
					$needSlots++;
					$newStackable[$v] = 1;
				}
			}
			else
			{
				$needSlots++;
			}
		}
		$validArr[] = $v;
	}
	if ($bagUsed + $needSlots > $maxbag) return false;
	$arr = $validArr;
	foreach ($arr as $k => $v)
	{
		if($v=='') continue;
		/*$rs = $arrobj->dataGet(array('k' => MEM_USERBAG_KEY,
									 'v' => "if(\$rs['uid']=='{$_SESSION['id']}' && \$rs['pid']=='{$v}') \$ret=\$rs;"
									 ),
								   $_bag
							  ); */

		$rs = $_pm['mysql']->getOneRecord("SELECT id,vary,sell,sums FROM userbag WHERE uid=".$_userid." and pid={$v} and vary=1 and zbing=0 and (cantrade IS NULL OR cantrade<>3) ORDER BY IF(sums>0,1,0) DESC, id ASC LIMIT 1");
		if (is_array($rs))
		{
			if ($rs['vary'] == 1)
			{
				$wasEmptyStack = intval($rs['sums']) <= 0;
				if ($_pm['mysql']->query("UPDATE userbag
							   SET sums=sums+1
							 WHERE id={$rs['id']} and uid={$_userid} and pid={$v} and vary=1 and zbing=0 and (cantrade IS NULL OR cantrade<>3)
						  ") === false || mysql_affected_rows($_pm['mysql']->getConn()) != 1) return false;
				if ($wasEmptyStack) $bagUsed++;
			}
			else
			{
				if ($bagUsed >= $maxbag) return false;
				//$newid = mem_get_autoid($m, MEM_ORDER_KEY, 'userbag');
				if ($_pm['mysql']->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
							VALUES(
								   ".$_userid.",
								   {$v},
								   {$rs['sell']},
								   2,
								   1,
								   unix_timestamp()
								  );
						  ") === false) return false;
				 $bagUsed++;
			}
		}
		else{
			/*$rs = $_pm['mem']->dataGet(array('k' => MEM_PROPS_KEY,
								    'v' => "if(\$rs['id'] == '{$v}') \$ret=\$rs;"
								  ));*/
			//$mempropsid = unserialize($_pm['mem'] -> get('db_propsid'));
			$mempropsid[$v] = getBasePropsInfoById($v);
			$rs = $mempropsid[$v];
			if (is_array($rs))
			{
				if ($bagUsed >= $maxbag) return false;
				//$newid = mem_get_autoid($m, MEM_ORDER_KEY, 'userbag');
				if ($_pm['mysql']->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
							VALUES(
								   ".$_userid.",
								   {$v},
								   {$rs['sell']},
								   {$rs['vary']},
								   1,
								   unix_timestamp()
								  );
						  ") === false) return false;
				 $bagUsed++;
			}else{
				return false;
			}
		}
		unset($rs);
		// 检测是否超出包裹，
		if ($bagUsed > $maxbag) return false;
	}
	return true;
}
/**
@Save Other data.
@Param: $bb pets info.
        $exp: get exp data.
@ 加入升级的属性。
@Return: false or 升级后的提示信息。true.
@Memo:
	BB升级需要进行的操作：
	1）升级公式：X=取整（属性成长值*成长率）

*/
function saveGetOther($bb, $exp, $_userid=0)
{
	global $_pm;//, $db_bb;
	if (!is_array($bb) || !isset($bb['id'])) return false;
	if($_userid==0)
	{
		$_userid=isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
	}
	else
	{
		$_userid=intval($_userid);
	}
	$petId = intval($bb['id']);
	if($_userid < 1 || $petId < 1) return false;
	//if(!isset($db_bb)||!is_array($db_bb))
	//$db_bb=&$bb;
	$db_bb= $_pm['mysql']->getOneRecord("SELECT *
												  FROM userbb
												  WHERE id=".$petId." AND uid=".$_userid."
												  LIMIT 0,1
												");
	$bb=&$db_bb;
	if (!is_array($bb)) return false;
	if ($bb['level']>=130) return false;
	$willexp = $bb['nowexp']+$exp;
	if ($willexp >= $bb['lexp'])
	{
		//$now = 0;
		$now = $willexp-$bb['lexp'];

		//############### Update start ###############
		$czz = $_pm['mem']->dataGet(array('k' => MEM_WX_KEY,
								 'v' => "if(\$rs['wx'] == '{$bb['wx']}') \$ret=\$rs;"
						   ));
		$init = getBaseBBInfoForUserPet($bb);
		// fix .
		if (!is_array($init))
		{
			return false;
		}

		// fix .

		if (is_array($czz))
		{
			$kx = explode(",", $bb['kx']);
			while(count($kx) < 5) $kx[] = 0;
			if($bb['wx']==7){
				$maxlvlRow=$_pm['mysql']->getOneRecord('select max_level from super_jh where pet_id='.$init['id']);

				if($maxlvlRow&&$bb['level']>=$maxlvlRow['max_level'])
				{
					return false;
				}
			}
			//Get all attrib.
			$lv = ++$bb['level'];
			if ($lv > 130)
			{
				return false;
			}
			$jk = intval($czz['j']*$bb['czl'])+$kx[0];
			$mk = intval($czz['m']*$bb['czl'])+$kx[1];
			$sk = intval($czz['s']*$bb['czl'])+$kx[2];
			$hk = intval($czz['h']*$bb['czl'])+$kx[3];
			$tk = intval($czz['t']*$bb['czl'])+$kx[4];
			//
			$hp = intval($czz['hp']*$bb['czl'])+$db_bb['srchp'];
			$mp = intval($czz['mp']*$bb['czl'])+$db_bb['srcmp'];
			$ac = intval($czz['ac']*$bb['czl'])+$db_bb['ac'];
			$mc = intval($czz['mc']*$bb['czl'])+$db_bb['mc'];
			$sp = intval($czz['speed']*$bb['czl'])+$db_bb['speed'];
			$hits=intval($czz['hits']*$bb['czl'])+$db_bb['hits'];
			$miss=intval($czz['miss']*$bb['czl'])+$db_bb['miss'];
			// 属性修复特别代码结束。

			$srchp = $hp;
			$srcmp = $mp;

			// Get Next Level exp require.
			$lrs = $_pm['mem']->dataGet(array('k' => MEM_EXP_KEY,
										'v' => "if(\$rs['level'] == '{$lv}') \$ret=\$rs;"
								  ));
			if (!is_array($lrs) || empty($lrs['nxtlvexp']))
			{
				return false;
			}

			//update user bb.
			$levelSaved = $_pm['mysql']->query("UPDATE userbb
						   SET level=	{$lv},
							   ac	=	{$ac},
							   mc	=	{$mc},
							   srchp=	{$srchp},
							   hp	=	{$hp},
							   srcmp=	{$srcmp},
							   mp	=	{$mp},
							   nowexp=	{$now},
							   lexp	=	{$lrs['nxtlvexp']},
							   hits	=	{$hits},
							   miss	=	{$miss},
							   speed=	{$sp},
							   kx	=	'{$jk},{$mk},{$sk},{$hk},{$tk}'
						 WHERE id={$bb['id']} and uid={$bb['uid']}
					   ");
			return $levelSaved && mysql_affected_rows($_pm['mysql']->getConn()) == 1;
		}
		//############### Update end.#################
		else return false;
	}
	else
	{
		// Save exp
		$_pm['mysql']->query("UPDATE userbb
					   SET nowexp=nowexp+{$exp}
					 WHERE id={$bb['id']} and uid=".$_userid."
				  ");
		return false;
	}
}

/**
@Gaiwu Say word.
@Param: gaiwu data for a row of database.
@Param: $nowhp, current hp.
*/
function sayWord($rs,$nowhp)
{
	global $_gwword;
	$word = '';
	if ($rs['name'] == '天马')
	{
		$maxHp = isset($rs['hp']) ? max(1, intval($rs['hp'])) : 1;
		$now = ((1-round($nowhp/$maxHp,2))*100);

		if ($now>80 && $now<=100) $word = $_gwword[3];
		else if($now>50 && $now<70) $word =$_gwword[2];
		else if($now>10 && $now<30) $word = $_gwword[1];
		else if($now<2) $word = $_gwword[0];
	}
	return $word;
}

/**
*@Usage: Catch task.
*@Param: Record task gid.
*@Return: void.
*@Message format: killmon:18|19|20:5
		  giveitem:885:1,killmon:3|4|5:3,killmon:20|21|22:3,killmon:37|38|39:3,killmon:54|55|56:3,killmon:71|72:3
* Note: current only one vary gpc.
*/
function catchTask(&$user, $gid)
{
	global $_pm;
	$uid = is_array($user) && isset($user['id']) ? intval($user['id']) : 0;
	$gid = intval($gid);
	if($uid < 1 || $gid < 1) return;
	$usertaskarr = $_pm['mysql']->getRecords("SELECT id,taskid,state FROM task_accept WHERE uid={$uid} ORDER BY id");
	if(!is_array($usertaskarr)) return;
	foreach($usertaskarr as $acceptedTask)
	{
		if(!is_array($acceptedTask) || !isset($acceptedTask['id'],$acceptedTask['taskid'])) continue;
		$state = isset($acceptedTask['state']) ? strval($acceptedTask['state']) : '0';
		if($state === '1') continue;
		$taskinfo = getBaseTaskInfoById($acceptedTask['taskid']);
		if(!is_array($taskinfo) || !isset($taskinfo['okneed']) || strpos($taskinfo['okneed'],'killmon:') === false) continue;
		if(!taskScheduleIsActive($taskinfo)) continue;
		$currentCounts = array();
		foreach(explode(',',$state) as $statePart)
		{
			$stateInfo = explode(':',$statePart);
			if(count($stateInfo) >= 3 && $stateInfo[0] === 'killmon')
			{
				$currentCounts[$stateInfo[1]] = max(0,intval($stateInfo[2]));
			}
		}
		foreach(explode(',',$taskinfo['okneed']) as $needPart)
		{
			$needInfo = explode(':',$needPart);
			if(count($needInfo) < 3 || $needInfo[0] !== 'killmon') continue;
			$monsterIds = array_map('intval',explode('|',$needInfo[1]));
			$required = intval($needInfo[2]);
			$current = isset($currentCounts[$needInfo[1]]) ? intval($currentCounts[$needInfo[1]]) : 0;
			if($required < 1 || $current >= $required || !in_array($gid,$monsterIds,true)) continue;
			findsave('killmon:'.$needInfo[1].':'.$required,intval($acceptedTask['id']),$uid);
			break;
		}
	}
}

// 获得已经杀怪的数目.
function findme($str, $needle)
{
	if($str == '') return 0;
	$arr = explode(',',$str);

	foreach ($arr as $k => $rs)
	{
		if ($rs === '' || $rs === '0') continue;
		$find = explode(':',$rs);
		if ($find[0] == $needle)
		{
			return $find[2];
		}
	}
	return 0;
}

/**
* 保存数据到用户任务状态中。
* 仅仅只更新当前所打怪物。
*/
function findsave($str,$tid,$uid=0)
{
	global $_pm;
	$tid = intval($tid);
	$uid = intval($uid);
	if($uid < 1) $uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
	if($tid < 1 || $uid < 1 || $str === '') return false;
	$sql = "SELECT state FROM task_accept WHERE id={$tid} AND uid={$uid}";
	$tarr = $_pm['mysql'] -> getOneRecord($sql);
	if(!is_array($tarr)) return false;
	$need = isset($tarr['state']) ? strval($tarr['state']) : '0';
	$patter=explode(':',$str);
	if(count($patter) < 3 || $patter[0] !== 'killmon' || intval($patter[2]) < 1) return false;
	$arr = explode(',',$need);

	$tasklog = '';
	$ok = 0;
	foreach ($arr as $k => $rs)
	{
		if ($rs === '' || $rs === '0') continue;
		$find = explode(':',$rs);

		if (count($find) >= 3 && $find[0] == 'killmon' && $find[1]==$patter[1])
		{
			if ($find[2]<$patter[2])
			{
				$tasklog .= ','.$find[0].':'.$find[1].':'.($find[2]+1);
			}
			else
			{
				$tasklog .= ','.$rs;
			}
			$ok=1;
		}
		else $tasklog .= ','.$rs;
	}

	if ($ok == 0)
	{
		$tmp = explode(':', $str);

		$tasklog .= ','.$tmp[0].':'.$tmp[1].':1';
	}
	$tasklogSql = $_pm['mysql']->escape($tasklog);
	return $_pm['mysql']->query("UPDATE task_accept SET state='{$tasklogSql}' WHERE id={$tid} AND uid={$uid}");
}

function stopUser($type=1,$flag = false)
{
	global $_pm;
	if($flag){
		/*$_pm['mysql']->query("UPDATE player
				   SET secid={$type}
				 where id={$_SESSION['id']}");*/
	}
	unset($_SESSION['id']);
	$_pm['mem']->memClose();
	exit();
}
function stopUser2($type=1,$flag = true)
{
	global $_pm;
	$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
	if($flag && $uid > 0){
		$type = intval($type);
		if(kdjlMysqlTableHasColumn($_pm['mysql'], 'player', 'wg'))
		{
			$_pm['mysql']->query("UPDATE player SET wg='{$type}' WHERE id={$uid}");
		}
		else
		{
			$note = $_pm['mysql']->escape('stopUser2:'.$type);
			$_pm['mysql']->query("INSERT INTO gamelog(ptime,seller,buyer,pnote,vary) VALUES(".time().",'{$uid}','{$uid}','{$note}',99)");
		}
	}
	unset($_SESSION['id']);
	$_pm['mem']->memClose();
	exit();
}
/*
@Param: array=> $user
@Return false or array.
*/
function weeklyDayList($days)
{
	$result = array();
	$parts = preg_split('/[\s,，|]+/', trim((string)$days));
	if (!is_array($parts)) return $result;
	foreach ($parts as $part)
	{
		if ($part === '' || !preg_match('/^\d+$/', $part)) continue;
		$day = intval($part);
		if ($day >= 1 && $day <= 7) $result[$day] = $day;
	}
	ksort($result);
	return array_values($result);
}

function weeklyDaysContain($days, $currentDay = null)
{
	if ($currentDay === null) $currentDay = date('N');
	return in_array(intval($currentDay), weeklyDayList($days), true);
}

function weeklyDaysText($days)
{
	$labels = array(1 => '一', 2 => '二', 3 => '三', 4 => '四', 5 => '五', 6 => '六', 7 => '日');
	$result = array();
	foreach (weeklyDayList($days) as $day) $result[] = $labels[$day];
	return implode('、', $result);
}

function clockTimeToMinutes($time)
{
	$time = trim((string)$time);
	if (preg_match('/^(\d{1,2}):(\d{2})$/', $time, $parts))
	{
		$hour = intval($parts[1]);
		$minute = intval($parts[2]);
	}
	else if (preg_match('/^\d{1,4}$/', $time))
	{
		$time = str_pad($time, 4, '0', STR_PAD_LEFT);
		$hour = intval(substr($time, 0, 2));
		$minute = intval(substr($time, 2, 2));
	}
	else return false;
	if ($hour > 23 || $minute > 59) return false;
	return $hour * 60 + $minute;
}

function isWeeklyDayTimeActive($days, $starttime, $endtime, $currentDay = null, $currentTime = null, $includeEnd = true)
{
	if ($currentDay === null) $currentDay = date('N');
	if ($currentTime === null) $currentTime = date('Hi');
	$currentDay = intval($currentDay);
	$current = clockTimeToMinutes($currentTime);
	$start = clockTimeToMinutes($starttime);
	$end = clockTimeToMinutes($endtime);
	if ($currentDay < 1 || $currentDay > 7 || $current === false || $start === false || $end === false) return false;

	if ($start <= $end)
	{
		if (!weeklyDaysContain($days, $currentDay)) return false;
		return $current >= $start && ($includeEnd ? $current <= $end : $current < $end);
	}

	if (weeklyDaysContain($days, $currentDay) && $current >= $start) return true;
	$previousDay = $currentDay === 1 ? 7 : $currentDay - 1;
	if (!weeklyDaysContain($days, $previousDay)) return false;
	return $includeEnd ? $current <= $end : $current < $end;
}

function isWeeklyDayTimeFinished($days, $endtime, $currentDay = null, $currentTime = null, $includeEnd = false)
{
	if ($currentDay === null) $currentDay = date('N');
	if ($currentTime === null) $currentTime = date('Hi');
	$current = clockTimeToMinutes($currentTime);
	$end = clockTimeToMinutes($endtime);
	if ($current === false || $end === false || !weeklyDaysContain($days, $currentDay)) return false;
	return $includeEnd ? $current >= $end : $current > $end;
}

function weeklyMostRecentDay($days, $currentDay = null)
{
	if ($currentDay === null) $currentDay = date('N');
	$currentDay = intval($currentDay);
	$list = weeklyDayList($days);
	if ($currentDay < 1 || $currentDay > 7 || count($list) === 0) return false;
	for ($offset = 0; $offset < 7; $offset++)
	{
		$day = $currentDay - $offset;
		if ($day < 1) $day += 7;
		if (in_array($day, $list, true)) return $day;
	}
	return false;
}

function weeklyTimeToMinutes($day, $time)
{
	$day = intval($day);
	$minutes = clockTimeToMinutes($time);
	if ($day < 1 || $day > 7 || $minutes === false) return false;
	return ($day - 1) * 1440 + $minutes;
}

function isWeeklyTimeRangeActive($starttime, $endtime, $currentDay = null, $currentTime = null)
{
	$startParts = explode('|', trim((string)$starttime), 2);
	if (count($startParts) !== 2) return false;
	$endParts = explode('|', trim((string)$endtime), 2);
	$endHasDay = count($endParts) === 2;
	$endDay = $endHasDay ? $endParts[0] : $startParts[0];
	$endClock = $endHasDay ? $endParts[1] : $endParts[0];
	$start = weeklyTimeToMinutes($startParts[0], $startParts[1]);
	$end = weeklyTimeToMinutes($endDay, $endClock);
	if ($start === false || $end === false) return false;
	if ($end < $start) $end += $endHasDay ? 10080 : 1440;
	if ($currentDay === null) $currentDay = date('N');
	if ($currentTime === null) $currentTime = date('Hi');
	$current = weeklyTimeToMinutes($currentDay, $currentTime);
	if ($current === false) return false;
	if ($current < $start && $end >= 10080) $current += 10080;
	return $current >= $start && $current <= $end;
}

function usedProps(&$user)
{
	global $_pm;
	$doubleexp = kdjlSafeMemValue($_pm['mem']->get(MEM_TIME_KEY), array());
	$newdoubleexparr = array();
	$newdoubleexparr1 = array();
	if(is_array($doubleexp)) foreach($doubleexp as $v)
	{
		if(!is_array($v) || !isset($v['titles'])) continue;
		if($v['titles'] == "exp" && isset($v['starttime']) && isset($v['endtime']) && isset($v['days']))
		{
			$newdoubleexparr[$v['starttime'].'-'.$v['endtime']] = $v['days'];
		}
		else if($v['titles'] == "exp1" && isset($v['starttime']) && isset($v['endtime']) && isset($v['days']))
		{
			$newdoubleexparr1[] = $v;
		}
	}
	$nowtime = date("YmdHis");	// double exp 12         3600
	if ($user['dblexpflag']>1 && ($user['dblstime']+$user['maxdblexptime']) > time())
	{
		switch($user['dblexpflag'])
		{
			case 2: $dbl = 1.5;break;
			case 3: $dbl = 2;break;
			case 4: $dbl = 2.5;break;
			case 5: $dbl = 3;break;
			default:$dbl = 1;break;
			/*case 2: $dbl = 1;break;
			case 3: $dbl = 1;break;
			case 4: $dbl = 1;break;
			case 5: $dbl = 1;break;
			default:$dbl = 1;break;*/
		}
		$ret['double'] = $dbl;
	}
	else if($user['dblexpflag']>0 && ($user['dblstime']+$user['maxdblexptime']) <= time())
	{
		$user['dblexpflag']=0;
		$user['maxdblexptime']=0;
		$ret['double']=1;
	}
	else $ret['double']=1;
	// auto fitting.

	############################# 谭炜 9.24###############################
	//金钱版
	if(empty($_SESSION['way'.$_SESSION['id']]) || $_SESSION['way'.$_SESSION['id']] == "money")
	{
		if ($user['autofitflag']>0 && $user['sysautosum']>0) // 自动战斗!
		{
			$user['sysautosum']-=1;
			$ret['doubleexp'] =1.2;
			$ret['auto'] = 1;
		}
		else if($user['autofitflag']>1 && $user['sysautosum']<=0)
		{
			$user['sysautosum']=0;
			$ret['auto'] = 2; //money auto complete.
			$ret['doubleexp'] =1;
		}
	}
	//元宝版
	else if($_SESSION['way'.$_SESSION['id']] == "yb")
	{
		if ($user['autofitflag']>0 && $user['maxautofitsum']>0) // 自动战斗!
		{
			$user['maxautofitsum']-=1;
			$ret['doubleexp']=1.5;
			$ret['auto'] = 1;
		}
		else if($user['autofitflag']>1 && $user['maxautofitsum']<=0)
		{
			$user['maxautofitsum']=0;
			$ret['auto'] =2; // auto complete.
			$ret['doubleexp']=1;
		}
	}
	if(is_array($newdoubleexparr))
	{
		$k = "";
		$v = "";
		foreach($newdoubleexparr as $k => $v)
		{
			if(!empty($k))
			{
				$arr = "";
				$arr = explode("-",$k);
				if(isset($arr[1]) && $nowtime >= $arr[0] && $nowtime <= $arr[1])
				{
					$ret['double'] = $v;
				}
			}
		}
	}
	if(is_array($newdoubleexparr1))
	{
		foreach($newdoubleexparr1 as $v)
		{
			if(isWeeklyTimeRangeActive($v['starttime'], $v['endtime']))
			{
				$ret['double'] = $v['days'];
			}
		}
	}
	return $ret;
}

// =========================

class Ack{
	private $ap	=	array();//怪物

	private $bp =	array();//宠物

	private $hitsp	=	0;

	private $commack=	0;

	private $maxack	=	0;

	public $skillack	=	0;

	public $fixedDamage = false;

	public $hit = 0;

	public $Crit;

	// save array
	public function __construct($a, $b)
	{
		global $Crit;
		$this->Crit = $Crit;
		$this->ap	=	$a;
		$this->bp	=	$b;
		$this->getHitsp();
		$this->His_and_miss();
	}

	/**
	@Get hits%
	@patter: （攻击方命中值－防御方闪避值）*100%
	*/
	public function getHitsP()
	{
		$this->hitsp	=	($this->ap['hits']-$this->bp['miss'])/100;
		if ($this->hitsp<0.1) $this->hitsp= 0.1;
		else if ($this->hitsp>1.5) $this->hitsp= 1.5;
	}
	public function His_and_miss()
	{
		if( $this->ap['hits'] > 3*$this->bp['miss'] )
		{
			$num = 9;
		}
		if( $this->ap['hits'] <= 3*$this->bp['miss'] && $this->ap['hits'] > 2*$this->bp['miss'] )
		{
			$num = 8;
		}
		if( $this->ap['hits'] <= 2*$this->bp['miss'] && $this->ap['hits'] > $this->bp['miss'] )
		{
			$num = 6;
		}
		if( $this->ap['hits'] <= $this->bp['miss'] && 2*$this->ap['hits'] > $this->bp['miss'] )
		{
			$num = 4;
		}
		if( 2*$this->ap['hits'] <= $this->bp['miss'] && 3*$this->ap['hits'] > $this->bp['miss'] )
		{
			$num = 3;
		}
		if( 3*$this->ap['hits'] <= $this->bp['miss'] )//怪物躲闪是宠物命中的2-3倍)
		{
			$num = 3;
		}
		$hit_or_miss = rand(1,10);
		if( $num  >= $hit_or_miss )	//中了
		{
			$this->hit = 1;
		}
		else
		{
			$this->hit = 0;
			global $Crit;
			$Crit = 0;
		}
	}
	public function getSkillAck()
	{
		if( $this->hit == '1' )
		{
			$this->hit = 0;	//初始化
			$this->fixedDamage = false;
			$ackvalue = '';
			$plus = '';
			$plusFromSkillList = false;
			if (!empty($this->ap['skill']))
			{
				$plusFromSkillList = true;
				$patter = $this->ap['s_id'] . ":";
				$arr = explode($patter, $this->ap['skill'], 2);
				if(count($arr) < 2 || !preg_match('/^(\d+)/', $arr[1], $levelMatch)) return;
				$cid = intval($levelMatch[1])-1; // 对应数组key。
				if($cid<0) $cid=0;

				$ar = explode(",", $this->ap['s_value']);
				$ar1= explode(",", $this->ap['s_plus']);
				if(!isset($ar[$cid]) || !isset($ar1[$cid])) return;
				$ackvalue = $ar[$cid];
				$plus = $ar1[$cid];
			}
			else
			{
				$ackvalue = $this->ap['s_value'];
				$plus	  = $this->ap['s_plus'];
			}

			$plusname = explode(":", $plus, 2);
			if($plusname[0] == 'super')
			{
				$percent = isset($plusname[1]) ? floatval(str_replace("%", "", $plusname[1])) : 0;
				if(isset($this->bp['srchp']))
				{
					$targetMaxHp = $this->bp['srchp'];
					if(isset($this->bp['addhp'])) $targetMaxHp += $this->bp['addhp'];
				}
				else
				{
					$targetMaxHp = isset($this->bp['hp']) ? $this->bp['hp'] : 0;
				}

				$one = intval($percent * $targetMaxHp / 100);
				$this->fixedDamage = true;
				$this->Crit = 0;
			}
			else
			{
				if($plus == '')
				{
					$plus = 1;
				}
				else if($plusFromSkillList)
				{
					$plus = $plus / 100 + 1;
				}
				else
				{
					$plusValue = str_replace("hp:", "", $plus);
					$plusValue = str_replace("%", "", $plusValue);
					$plus = $plusValue / 100 + 1;
				}

				$ackvalue=explode(',',$ackvalue);
				$ackvalue=$ackvalue[0];
				$ackvalue=preg_replace("/[^\d]/",'',$ackvalue);
				$base = ($this->ap['ac']+$ackvalue)*$plus-$this->bp['mc'];
				$base = $base<=0?1:$base;

				$one = round($base*$this->hitsp)-0; // kx=0 current.
				$this->seedInit();
				$one = $one + 1;

				if ($one<0) $one=0;
			}
			if(!$this->fixedDamage && $this->bp['id'] != 262)
			{
				$foalt_ac_number = rand(-10,5);
				if($this->Crit == '1')
				{
					$one *= 2;
				}
				else
				{
					$one = intval($one*(100+$foalt_ac_number)/100);
				}
			}
			if($one < 1)
			{
				$one = 1;
			}
			$this->skillack	=	$one;
		}
	}

	public function seedInit()
	{
		list($usec, $sec) = explode(' ', microtime());
		$t = (float) $sec + ((float) $usec * 100000);
		srand($t);
	}
}


class Ack1{
	private $ap	=	array();

	private $bp =	array();

	private $hitsp	=	0;

	private $commack=	0;

	private $maxack	=	0;

	public $skillack	=	0;

	public $hit = 0;

	// save array
	public function __construct($a, $b)
	{
		$this->ap	=	$a;
		$this->bp	=	$b;
		$this->getHitsp();
		$this->His_and_miss();
	}

	/**
	@Get hits%
	@patter: （攻击方命中值－防御方闪避值）*100%
	*/
	public function getHitsP()
	{
		$this->hitsp	=	($this->ap['hits']-$this->bp['miss'])/100;
		if ($this->hitsp<0.1) $this->hitsp= 0.1;
		else if ($this->hitsp>1.5) $this->hitsp= 1.5;
	}
	public function His_and_miss()
	{
		if( $this->bp['hits'] > 3*$this->ap['miss'] )
		{
			$num = 9;
		}
		if( $this->bp['hits'] <= 3*$this->ap['miss'] && $this->bp['hits'] > 2*$this->ap['miss'] )
		{
			$num = 8;
		}
		if( $this->bp['hits'] <= 2*$this->ap['miss'] && $this->bp['hits'] > $this->ap['miss'] )
		{
			$num = 6;
		}
		if( $this->bp['hits'] <= $this->ap['miss'] && 2*$this->bp['hits'] > $this->ap['miss'] )
		{
			$num = 4;
		}
		if( 2*$this->bp['hits'] <= $this->ap['miss'] && 3*$this->bp['hits'] > $this->ap['miss'] )
		{
			$num = 3;
		}
		if( 3*$this->bp['hits'] <= $this->ap['miss'] )//怪物躲闪是宠物命中的2-3倍)
		{
			$num = 3;
		}
		$hit_or_miss = rand(1,10);
		//echo "->".$hit_or_miss."|".$num."<-";
		if( $num  >= $hit_or_miss )	//怪物中了
		{
			$this->hit = 1;
		}
		else
		{
			$this->hit = 0;
		}
	}
	/**
	@Get common actvalue
	@Patter: 普通伤害=（攻击方攻击值－防御方防御值）*命中率+rand（－1*攻击方等级，+1*攻击方等级）
	*/
	public function getCommonAct()
	{
		$this->seedInit();
		//$rand = rand(0, $this->ap['level']);
		$this->commack	=	($this->ap['ac']-$this->bp['mc'])*$this->hitsp + 1;
		if ($this->commack <= 0) $this->commack = 0;
	}

	/**
	@Get max ack.
	@Patter: （攻击方攻击值－防御方防御值）*命中率*2+rand（－2*攻击方等级，+2*攻击方等级）
	*/
	public function getMaxAct()
	{
		$this->maxack=($this->ap['ac']-$this->bp['mc'])*$this->hitsp;
		$this->seedInit();
		//$rand = rand(0, 2*$this->ap['level']);
		$this->maxack = $this->maxack + 1;
		if($this->maxack<1) $this->maxack=0;
	}

	/**
	@Get skill ack
	@Patter: 取整{[（攻击方攻击值+技能伤害值）*（100%+技能特性伤害加成）－防御方防御值]*命中率}-防御方属性抵抗值+rand（－1*攻击方等级，+1*攻击方等级）
	增加无双技能：伤害=技能伤害百分比 * 最大血量;
	*/
	public function getSkillAck()
	{
		if( $this->hit == '1' )
		{
			$patter = $this->ap['s_id'] . ":";
			$arr = explode($patter, $this->ap['skill'], 2);
			if(count($arr) < 2 || !preg_match('/^(\d+)/', $arr[1], $levelMatch)) return;
			$cid = intval($levelMatch[1])-1; // 对应数组key。
			if($cid<0) $cid=0;

			$ar = explode(",", $this->ap['s_value']);
			$ar1= explode(",", $this->ap['s_plus']);
			if(!isset($ar[$cid]) || !isset($ar1[$cid])) return;
			$ackvalue = $ar[$cid];
			$ackvalue=preg_replace("/[^\d]/",'',$ackvalue);
			$plus	=	$ar1[$cid]/100+1;
			/*$ackvalue = $this->ap['s_value'];
			$plus	  = $this->ap['s_plus'];*/
			//$plusname = explode(":",$plus);
			if($this -> ap['s_id'] == 50)
			{
				$plus = $plus - 1;
				if(isset($this -> bp['srchp']))
				{
					$one = intval($plus * $this -> bp['srchp']);
					if(isset($this -> bp['addhp']))
					{
						$one += intval($plus * $this -> bp['addhp']);
					}
				}
				else
				{
					$one = intval($plus * $this -> bp['hp']);
				}
			}
			if($this -> ap['s_id'] != 50)
			{
				$base = ($this->ap['ac']+$ackvalue)*$plus-$this->bp['mc'];
				$base = $base<=0?1:$base;

				$one = round($base*$this->hitsp)-0; // kx=0 current.
				$this->seedInit();
				$one = $one + 1;
				if ($one<0) $one=0;
			}
			$foalt_ac_number = rand(-5,10);
			$one = intval($one*(100+$foalt_ac_number)/100);
			$this->skillack	=	$one;
		}
	}

	public function seedInit()
	{
		list($usec, $sec) = explode(' ', microtime());
		$t = (float) $sec + ((float) $usec * 100000);
		srand($t);
	}
}

/**
* @Usage: 刷怪BOSS检查。
*/


function updateBoss($gid)
{
	global $_pm,$gs;
	$gid = intval($gid);
	$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
	$fightKey = 'fight'.$uid;
	$fight = isset($_SESSION[$fightKey]) && is_array($_SESSION[$fightKey]) ? $_SESSION[$fightKey] : array();
	if ($gid < 1 || $uid < 1) return false;
	if (!isset($fight['boss']) || intval($fight['boss']) != 3) return true;
	$newtime = time()+rand(60,900);
	$now = time();
	$sql = "UPDATE boss_refresh
				 SET dtime={$newtime},glock=0,rtime={$now}
			   WHERE gid={$gid} AND fightuid={$uid} AND glock=1";
	if (!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
		return false;
	}
	$log = "UPDATE boss_refresh
				 SET dtime={$newtime},glock=0,rtime={$now}
			   WHERE gid={$gid} AND fightuid={$uid} AND glock=1
						";
	$task = new task();
	$bossName = is_array($gs) && isset($gs['name']) ? $gs['name'] : $gid;
	$task->saveGword("消灭了BOSS[".$bossName."]，获得了大量宝物！");
	$log = $_pm['mysql']->escape($log);
	$_pm['mysql']->query("INSERT INTO gamelog(ptime,seller,buyer,pnote,vary) VALUES(unix_timestamp(),{$uid},{$uid},'{$log}',3)");
	return true;
}

function memContent2Arr($memKey,$arrKey)
{
	if(isset($GLOBALS['sysmem_'.$memKey]))
	{
		return $GLOBALS['sysmem_'.$memKey];
	}
	global $_pm;
	$data = kdjlSafeMemValue($_pm['mem']->get($memKey), array());
	if(!is_array($data)) $data = array();
	$arr=array();
	foreach($data as $d)
	{
		if(!is_array($d) || !isset($d[$arrKey])) continue;
		$arr[$d[$arrKey]] = $d;
	}
	$GLOBALS['sysmem_'.$memKey] = $arr;
	return $arr;
}
function iconvall(&$des){
	if(!is_array($des)) return $des;
	foreach($des as $k=>$v)
	{
		$des[$k]=kdjlSafeIconv('gbk','utf-8',$v);
	}
	return $des;
}




//$id背包id
function lockItem($id){
	if(!isset($_SESSION['lockedItems']) || !is_string($_SESSION['lockedItems']))
	{
		$_SESSION['lockedItems'] = '';
	}
	if(strpos($_SESSION['lockedItems'],','.$id.',')!==false)
	{
		return false;
	}else{
		$_SESSION['lockedItems'] .= ','.$id.',';
		return true;
	}
}

function unLockItem($id){
	if(!isset($_SESSION['lockedItems']) || !is_string($_SESSION['lockedItems'])) return;
	$_SESSION['lockedItems']=str_replace(array(','.$id.',',',,'),array('',','),$_SESSION['lockedItems']);
}


//装备效果叠加存入内存
/*
$id 装备id(props)
*/
function formatMsgEffect($bid){
	global $_pm;
	$bid = intval($bid);
	$_pm['mem'] -> del('format_user_zhuangbei_'.$bid);
	$arr = array(
		'ac' => 0,
		'mc' => 0,
		'hp' => 0,
		'mp' => 0,
		'speed' => 0,
		'hits' => 0,
		'miss' => 0,
		'hprate' => 0,
		'mprate' => 0,
		'acrate' => 0,
		'mcrate' => 0,
		'hitsrate' => 0,
		'missrate' => 0,
		'speedrate' => 0,
		'sdmp' => 0,
		'szmp' => 0,
		'dxsh' => 0,
		'hitshp' => 0,
		'hitsmp' => 0,
		'shjs' => 0,
		'shft' => 0,
		'addmoney' => 0,
		'time' => 0,
		'crit' => 0
	);
	//找出此宠一共穿有哪几件装备
	$sql = 'SELECT id,pid,plus_tms_eft,F_item_hole_info FROM userbag WHERE sums > 0 AND zbpets = '.$bid;
	$parr = $_pm['mysql'] -> getRecords($sql);
	if (!is_array($parr) || empty($parr)){
		$str = 'ac:0';
		$_pm['mem'] -> set(array('k'=>'format_user_zhuangbei_'.$bid,'v'=>$str));
		return;
	}
	//$props = unserialize($_pm['mem']->get('db_propsid'));
	foreach ($parr as $pv){
		//$rs = $props[$pv['pid']];
		$pid = isset($pv['pid']) ? intval($pv['pid']) : 0;
		$rs = getBasePropsInfoById($pid);
		if(!is_array($rs)) continue;
		$addplus = explode(',',isset($pv['plus_tms_eft']) ? $pv['plus_tms_eft'] : '');
		$rs['addplus'] = isset($addplus[1]) ? intval($addplus[1]) : 0;
		//基础效果


		if (!empty($rs['effect'])) {
			$effectarr = explode(':',$rs['effect']);
			if (is_array($effectarr) && count($effectarr) >= 2 && $effectarr[0] !== '') {
				if(!isset($arr[$effectarr[0]])) $arr[$effectarr[0]] = 0;
				$arr[$effectarr[0]] += intval($effectarr[1]);
				if (!empty($rs['addplus'])){
					$arr[$effectarr[0]] += $rs['addplus'];
				}
			}
			unset($effectarr);
		}
		//附加效果
		if (!empty($rs['pluseffect'])) {
			$plusar = explode(',',$rs['pluseffect']);
			foreach ($plusar as $v){
				$plusarr = explode(':',$v);
				if(count($plusarr) < 2 || $plusarr[0] === '') continue;
				switch ($plusarr[0]){
					case 'ac':
						$arr['ac'] += $plusarr[1];
						break;
					case 'mc':
						$arr['mc'] += $plusarr[1];
						break;
					case 'hp':
						$arr['hp'] += $plusarr[1];
						break;
					case 'mp':
						$arr['mp'] += $plusarr[1];
						break;
					case 'speed':
						$arr['speed'] += $plusarr[1];
						break;
					case 'hits':
						$arr['hits'] += $plusarr[1];
						break;
					case 'miss':
						$arr['miss'] += $plusarr[1];
						break;
					case 'hprate':
						$num = intval($plusarr[1]);
						$arr['hprate'] += $num;
						unset($num);
						break;
					case 'mprate':
						$num = intval($plusarr[1]);
						$arr['mprate'] += $num;
						unset($num);
						break;
					case 'acrate':
						$num = intval($plusarr[1]);
						$arr['acrate'] += $num;
						unset($num);
						break;
					case 'mcrate':
						$num = intval($plusarr[1]);
						$arr['mcrate'] += $num;
						unset($num);
						break;
					case 'hitsrate':
						$num = intval($plusarr[1]);
						$arr['hitsrate'] += $num;
						unset($num);
						break;
					case 'missrate':
						$num = intval($plusarr[1]);
						$arr['missrate'] += $num;
						unset($num);
						break;
					case 'speedrate':
						$num = intval($plusarr[1]);
						$arr['speedrate'] += $num;
						unset($num);
						break;
					case 'sdmp'://将受到伤害的X%以MP抵消
						$num = intval($plusarr[1]);
						$arr['sdmp'] += $num;
						unset($num);
						break;
					case 'szmp'://将受到伤害的X%转化为MP
						$num = intval($plusarr[1]);
						$arr['szmp'] += $num;
						unset($num);
						break;
					case 'dxsh'://伤害抵销
						$num = intval($plusarr[1]);
						$arr['dxsh'] += $num;
						unset($num);
						break;
					case 'hitshp'://命中吸取伤害的X%转化为自身HP
						$num = intval($plusarr[1]);
						$arr['hitshp'] += $num;
						unset($num);
						break;
					case 'hitsmp':
						$num = intval($plusarr[1]);
						$arr['hitsmp'] += $num;
						unset($num);
						break;
					case 'shjs'://对敌人造成的伤害增加X%
						$num = intval($plusarr[1]);
						$arr['shjs'] += $num;
						unset($num);
						break;
					case 'shft'://反弹受到伤害的X%
						$num = intval($plusarr[1]);
						$arr['shft'] += max(0, $num);
						unset($num);
						break;
					case 'addmoney'://战斗胜利获得金钱增加X点
						$arr['addmoney'] += $plusarr[1];
						break;
				}
			}
		}
		//镶嵌(宝石属性)
		$sql = " SELECT F_item_hole_info FROM userbag WHERE  id = '".$pv['id']."'";
		$item_hole_info = $_pm['mysql'] -> getOneRecord($sql);
		if( isset($item_hole_info) && !empty($item_hole_info) )
		{
			$mid_arr = explode(',',$item_hole_info['F_item_hole_info']);
			foreach( $mid_arr as $info )
			{
				$hole_info = explode(':',$info);
				if(count($hole_info) < 2 || $hole_info[0] === '') continue;
				switch ($hole_info[0])
				{
					case 'ac' :
					{
						$num = intval(substr($hole_info[1],0,-1));
						$arr['acrate'] += $num;
						unset($num);
						break;
					}
					case 'mc' :
					{
						$num = intval(substr($hole_info[1],0,-1));
						$arr['mcrate'] += $num;
						unset($num);
						break;
					}
					case 'hits' :
					{
						$num = intval(substr($hole_info[1],0,-1));
						$arr['hitsrate'] += $num;
						unset($num);
						break;
					}
					case 'miss' :
					{
						$num = intval(substr($hole_info[1],0,-1));
						$arr['missrate'] += $num;
						unset($num);
						break;
					}
					case 'hp' :
					{
						$num = intval(substr($hole_info[1],0,-1));
						$arr['hprate'] += $num;
						unset($num);
						break;
					}
					case 'mp' :
					{
						$num = intval(substr($hole_info[1],0,-1));
						$arr['mprate'] += $num;
						unset($num);
						break;
					}
					case 'speed' :
					{
						$num = intval(substr($hole_info[1],0,-1));
						$arr['speedrate'] += $num;
						unset($num);
						break;
					}
					case 'sdmp' :
					{
						$num = intval(substr($hole_info[1],0,-1));
						$arr['sdmp'] += $num;
						unset($num);
						break;
					}
					case 'szmp' :
					{
						$num = intval(substr($hole_info[1],0,-1));
						$arr['szmp'] += $num;
						unset($num);
						break;
					}
					case 'dxsh' :
					{
						$num = intval(substr($hole_info[1],0,-1));
						$arr['dxsh'] += $num;
						unset($num);
						break;
					}
					case 'shjs':
					{
						$num = intval(substr($hole_info[1],0,-1));
						$arr['shjs'] += $num;
						unset($num);
						break;
					}
					case 'shft':
					{
						$num = intval(substr($hole_info[1],0,-1));
						$arr['shft'] += max(0, $num);
						unset($num);
						break;
					}
					case 'crit' :
					{
						$num = intval(substr($hole_info[1],0,-1));
						$arr['crit'] += $num;
						unset($num);
						break;
					}
					case 'hitshp' :
					{
						$num = intval(substr($hole_info[1],0,-1));
						$arr['hitshp'] += $num;
						unset($num);
						break;
					}
					case 'hitsmp' :
					{
						$num = intval(substr($hole_info[1],0,-1));
						$arr['hitsmp'] += $num;
						unset($num);
						break;
					}
				}
			}
		}
		unset($plusar,$plusarr,$v);
		//套装效果

		if (!empty($rs['serieseffect']) && !empty($bid)) {
			$series = explode(':',$rs['series']);
			if(count($series) < 2) continue;
			$seriesid = explode('|',$series[1]);//此套装的所有id
			$seriesidSafe = array();
			foreach($seriesid as $seriesPid)
			{
				$seriesPid = intval($seriesPid);
				if($seriesPid > 0) $seriesidSafe[] = $seriesPid;
			}
			if(empty($seriesidSafe)) continue;
			$check = 0;
			if (isset($_SESSION[$bid.'series']) && is_array($_SESSION[$bid.'series'])) {
				foreach ($_SESSION[$bid.'series'] as $bv){
					if (in_array(intval($bv),$seriesidSafe)){
						$check = 1;//已经计算
						break;
					}
				}
			}
			if ($check != 1) {
				$ids = implode(',',$seriesidSafe);
				$sql = "SELECT count(id) as countid FROM userbag WHERE pid in ($ids) and uid = {$_SESSION['id']} and zbpets = $bid";
				$idarr = $_pm['mysql'] -> getOneRecord($sql);
				if (!is_array($idarr)) {
					return false;
				}
				$readynum = isset($idarr['countid']) ? intval($idarr['countid']) : 0;
				$seriesar = explode(',',$rs['serieseffect']);
				foreach ($seriesar as $k => $v){
					$j = $k + 1;
					if ($j <= $readynum) {
							$seriesarr = explode(':',$v);
							if(count($seriesarr) < 2 || $seriesarr[0] === '') continue;
							switch ($seriesarr[0]){
								case 'ac':
									$arr['ac'] += $seriesarr[1];
									break;
								case 'mc':
									$arr['mc'] += $seriesarr[1];
									break;
								case 'hp':
									$arr['hp'] += $seriesarr[1];
									break;
								case 'mp':
									$arr['mp'] += $seriesarr[1];
									break;
								case 'speed':
									$arr['speed'] += $seriesarr[1];
									break;
								case 'hits':
									$arr['hits'] += $seriesarr[1];
									break;
								case 'miss':
									$arr['miss'] += $seriesarr[1];
									break;
								case 'hprate':
									$num = intval($seriesarr[1]);
									$arr['hprate'] += $num;
									unset($num);
									break;
								case 'mprate':
									$num = intval($seriesarr[1]);
									$arr['mprate'] += $num;
									unset($num);
									break;
								case 'acrate':
									$num = intval($seriesarr[1]);
									$arr['acrate'] += $num;
									unset($num);
									break;
								case 'mcrate':
									$num = intval($seriesarr[1]);
									$arr['mcrate'] += $num;
									unset($num);
									break;
								case 'hitsrate':
									$num = intval($seriesarr[1]);
									$arr['hitsrate'] += $num;
									unset($num);
									break;
								case 'speedrate':
									$num = intval($seriesarr[1]);
									$arr['speedrate'] += $num;
									unset($num);
									break;
								case 'missrate':
									$num = intval($seriesarr[1]);
									$arr['missrate'] += $num;
									unset($num);
									break;
								case 'dxsh':
									$num = intval($seriesarr[1]);
									$arr['dxsh'] += $num;
									unset($num);
									break;
								case 'hitshp':
									$num = intval($seriesarr[1]);
									$arr['hitshp'] += $num;
									unset($num);
									break;
								case 'hitsmp':
									$num = intval($seriesarr[1]);
									$arr['hitsmp'] += $num;
									unset($num);
									break;
								case 'sdmp':
									$num = intval($seriesarr[1]);
									$arr['sdmp'] += $num;
									unset($num);
									break;
								case 'szmp':
									$num = intval($seriesarr[1]);
									$arr['szmp'] += $num;
									unset($num);
									break;
								case 'time':
									$num = intval($seriesarr[1]);
									$arr['time'] += $num;
									unset($num);
									break;
								case 'shjs':
									$num = intval($seriesarr[1]);
									$arr['shjs'] += $num;
									unset($num);
									break;
								case 'shft':
									$num = intval($seriesarr[1]);
									$arr['shft'] += max(0, $num);
									unset($num);
									break;
								case 'addmoney':
									$arr['addmoney'] += $seriesarr[1];
									break;
								case 'crit':
									$arr['crit'] += intval($seriesarr[1]);
									break;
							}
					}
				}
				unset($k,$v);
			}
			$_SESSION[$bid.'series'][] = $rs['id'];
		}
		unset($rs);
	}
	if( $arr['dxsh'] >= 70 )
	{
		$arr['dxsh'] = 70;
	}

	if(is_array($arr)){
		$memstr = "";
		foreach($arr as $k => $v){
			if(empty($v)) continue;
			if(empty($memstr)) $memstr = $k.':'.$v;
			else $memstr .= ','.$k.':'.$v;
		}
		$_pm['mem'] -> set(array('k'=>'format_user_zhuangbei_'.$bid,'v'=>$memstr));
	}
	unset($_SESSION[$bid.'series']);
}


function curl($url,$port=80){
	if(!function_exists('curl_init')) return false;
	$request = '';
	$cookie_jar = '';
	$post = 1;
	$returntransfer = 1;
	$header = 0;
	$nobody = 0;
	$followlocation = 1;

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
	return $result;
}

//卡片
function card(){
	global $_pm;
	$time = date('YmdHi');
	$sql = "SELECT id,npcmsg,flag FROM card WHERE starttime <= $time and endtime >= $time";
	$arr = $_pm['mysql'] -> getRecords($sql);
	if(!is_array($arr)){
		return false;
	}


	$str = false;
	foreach($arr as $v){
		if(!empty($v)){
			if($v['flag'] == 1){
				$sql = "SELECT uid FROM card_info WHERE cardtype = {$v['id']} and uid = {$_SESSION['id']}";
				$newarr = $_pm['mysql'] -> getOneRecord($sql);
				if(is_array($newarr)){
					continue;
				}
			}
			//<li class="r0'.$t['color'].'"><a href="#"><span onclick="javascript:OpenLogin(1,'.$t['id'].','.$num.','.($taskid?1:0).')">'.$t['title'].'</span></a></li>
			$str .= '<li class="r04"><a href="#"><span onclick="javascript:card('.$v['id'].');">'.$v['npcmsg'].'</span></a></li>';
			//$str .= '<font color="red"><span onclick="javascript:card('.$v['id'].');" style="cursor:pointer;">'.$v['npcmsg'].'</span></font><br />';
		}
	}
	return $str;
}


function newcard(){
	global $_pm;
	$time = date('YmdHi');
	$sql = "SELECT id,days FROM timeconfig WHERE starttime <= $time and endtime >= $time AND titles='newcard'";
	$arr = $_pm['mysql'] -> getRecords($sql);
	if(!is_array($arr)){
		return false;
	}


	$str = false;
	foreach($arr as $v){
		if(empty($v)){
			continue;
			}
			$cname = explode('|',$v['days']);
			if(!empty($cname[1])){
				$cid = $cname[1];
			}else{
				$cid = 0;
			}
			$str .= '<li class="r04"><a href="#"><span onclick="javascript:card(\''.$cid.'\');">'.$cname[0].'</span></a></li>';
	}
	return $str;
}
/*
参数：timeconfig =>id
return:string
*/
function getprizelist($msgs)
{
	global $_pm,$user;
	$user = $_pm['user']->getUserById($_SESSION['id']);
	$memtimeconfig = kdjlSafeMemValue($_pm['mem']->get('db_timeconfignew'), array());
	$arr = (is_array($memtimeconfig) && isset($memtimeconfig['recallPlayer']) && is_array($memtimeconfig['recallPlayer'])) ? $memtimeconfig['recallPlayer'] : array();

		$nums = count($arr);
		$one = array();
		$prize = '';
		for($i=0;$i<$nums;$i++)
		{
			if(isset($arr[$i]['Id'], $arr[$i]['days']) && $arr[$i]['Id']==$msgs)
			{
				$one = explode(',', $arr[$i]['days']);//array(0->111:2,1->112:4)
				break;
			}
		}
		if(empty($one)) return '';
		$idlist = '';
		foreach($one as $a => $b)
		{
			$b_value = explode(':', $b);
			if(count($b_value) < 2) continue;
			//$memtimeconfig = unserialize($_pm['mem']->get('db_propsid'));
			//$arr_name = $memtimeconfig[$b_value[0]];
			$arr_name = getBasePropsInfoById($b_value[0]);
			if(is_array($arr_name) && isset($arr_name['name'])) $prize .=  $arr_name['name'].":".$b_value[1]."件<br />";
		}

					$string = "<b>您将获得以下物品奖励（请预留相应背包空位）：</b><br/><br/>".$prize."<br /><a href='javascript:getprize(".$msgs.");'><span style='text-decoration:none;'>确定领取</span></a>&nbsp;&nbsp;&nbsp;<a href='javascript:close_window();'><span style='text-decoration:none;'>关闭</span></a>";
				return $string;
}
//召回玩家设置
/*
return:array(id) =>(timeconfig->id)
*/
function zhaohui()//return ,35,34  (timeconfig->id)
{
		global $_pm,$user;
		$taskUid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		if($taskUid < 1) return '';
		$user = $_pm['user']->getUserById($taskUid);
		if(!is_array($user)) return '';
		$memtimeconfig = kdjlSafeMemValue($_pm['mem']->get('db_timeconfignew'), array());
		$arr = (is_array($memtimeconfig) && isset($memtimeconfig['recallPlayer']) && is_array($memtimeconfig['recallPlayer'])) ? $memtimeconfig['recallPlayer'] : array();
		/*
		Array ( [0] => Array ( [Id] => 35 [titles] => recallPlayer [days] => 1:2,2:4 [starttime] => 1 [endtime] => 94,5,10 ) [1] => Array ( [Id] => 34 [titles] => recallPlayer [days] => 111:2,12:4 [starttime] => 1 [endtime] => 5,2,0 ) )
		*/
		$ct = count($arr);//有几个就循环几次
		$msg = '';
		$lastVisit = isset($_SESSION['lastvtime']) ? intval($_SESSION['lastvtime']) : time();
		for($i=0;$i<$ct;$i++)// 0 1
		{
			if(!isset($arr[$i]['Id'], $arr[$i]['starttime'], $arr[$i]['endtime']) || $arr[$i]['starttime']==0)
			{
				continue;
			}
			else
			{
				$panduan = explode(',', $arr[$i]['endtime']);
				if(count($panduan) < 3) continue;
				$lasttime = max(0, intval($panduan[1])) * 24 * 60 * 60;
				$time = time()-$lasttime;
				$mainBid = isset($user['mbid']) ? intval($user['mbid']) : 0;
				$sql_level = "select level from userbb where id={$mainBid} AND uid={$taskUid}";
				$arr_level = $_pm['mysql'] -> getOneRecord($sql_level);//等级
				$petLevel = is_array($arr_level) && isset($arr_level['level']) ? intval($arr_level['level']) : 0;
				if($petLevel<$panduan[0])
				{
					continue;
				}
				if($lastVisit>$time)
				{

					continue;
				}
				if(intval($panduan[2])!=0)
				{
					$safeYbName = $_pm['mysql']->escape($user['name']);
					$sql_yb = "select sum(yb) tl from yblog where nickname='{$safeYbName}'";
					$arr_yb = $_pm['mysql'] -> getOneRecord($sql_yb);//元宝
					$ybTotal = is_array($arr_yb) && isset($arr_yb['tl']) ? intval($arr_yb['tl']) : 0;
					if($ybTotal<$panduan[2])
					{
						continue;
					}
				}
				$configId = intval($arr[$i]['Id']);
				if($configId < 1) continue;
				$marker = 999900 + $configId;
				$legacyMarker = intval('9999'.$configId);
				$markerList = $marker == $legacyMarker ? strval($marker) : $marker.','.$legacyMarker;
				$claimed = $_pm['mysql']->getOneRecord("SELECT Id FROM tasklog WHERE uid={$taskUid} AND taskid IN ({$markerList}) LIMIT 1");
				if(is_array($claimed)) continue;


			}//else 结束
			$msg .= ','.intval($arr[$i]['Id']);
		}//for循环结束
		return $msg;

}

function del_bag_expire(){
	global $_pm;
	$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
	if($uid < 1) return;
	$arr = array();
	$bagChanged = false;
	$sql = "SELECT userbag.id as ubid,userbag.pid as pid,userbag.sums as sums,userbag.stime as stime,userbag.zbpets as zbpets FROM userbag,props WHERE props.expire > 0 AND uid = {$uid} AND userbag.sums > 0 AND userbag.pid = props.id AND  userbag.stime<=(".time()." - props.expire)";
	$arr = $_pm['mysql'] -> getRecords($sql);
	if(is_array($arr) && count($arr) > 0){
		foreach($arr as $vv){
			if(!is_array($vv)) continue;
			$ubid = isset($vv['ubid']) ? intval($vv['ubid']) : 0;
			$pid = isset($vv['pid']) ? intval($vv['pid']) : 0;
			$sums = isset($vv['sums']) ? intval($vv['sums']) : 0;
			$stime = isset($vv['stime']) ? intval($vv['stime']) : 0;
			$zbpets = isset($vv['zbpets']) ? intval($vv['zbpets']) : 0;
			if($ubid < 1 || $sums < 1 || $stime < 1) continue;
			$str = 'pid:'.$pid.'sums:'.$sums.'stime:'.date('Y-m-d H:i',$stime);
			$logUid = $uid;
			$strSql = $_pm['mysql']->escape($str);
			$sql = "UPDATE userbag SET sums = 0 WHERE id = {$ubid} AND uid = {$uid} AND sums = {$sums} AND stime = {$stime}";
			if(!$_pm['mysql'] -> query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) continue;
			$bagChanged = true;
			$_pm['mysql'] -> query("INSERT INTO gamelog (seller,buyer,ptime,pnote,vary) VALUES({$logUid},{$logUid},".time().",'{$strSql}',222)");
			if($zbpets > 0){
				$_pm['mem'] -> del('format_user_zhuangbei_'.$zbpets);
				$_pm['mem']->set(array("k"=>"User_bb_equip_changed_".$zbpets.'_'.$uid,"v"=>1));
			}
		}
	}
	if($bagChanged) $_pm['mem']->del($uid.'bag');
}

function tgtgw(){
	global $_pm;
	$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
	if($uid < 1) return false;
	$carr = $_pm['mysql'] -> getOneRecord("SELECT gid FROM tgt WHERE uid = {$uid}");
	if(is_array($carr)){
		return false;
	}
	$tgcheck = $_pm['mysql'] -> getOneRecord("SELECT tgt,tgttime FROM player_ext WHERE uid = {$uid}");
	if(!is_array($tgcheck)){
		return false;
	}
	$tgnum = $tgcheck['tgt'] + 1;

	if($tgnum > 55){  //TT总层数控制
		if(!$_pm['mysql'] -> query("UPDATE player_ext SET tgt=0,tgttime = ".time()." WHERE uid = {$uid}")) return false;
		return 'a';
	}
	$tggwarr = $_pm['mysql'] -> getRecords("SELECT id,gpc,drops FROM c_gpc WHERE boss = $tgnum");
	if(!is_array($tggwarr) || empty($tggwarr)) return false;
	$tgtgwmax = count($tggwarr) - 1;
	$tgtgwnum = rand(0,$tgtgwmax);
	$tggw = $tggwarr[$tgtgwnum];
	$arr = explode(',',isset($tggw['gpc']) ? $tggw['gpc'] : '');
	$groupId = isset($tggw['id']) ? intval($tggw['id']) : 0;
	if($groupId < 1) return false;
	$inserted = 0;
	foreach($arr as $v){
		$gid = intval($v);
		if($gid < 1) continue;
		if(!$_pm['mysql'] -> query("INSERT INTO tgt (uid,gid,boss) VALUES ({$uid},{$gid},{$groupId})")){
			$_pm['mysql']->query("DELETE FROM tgt WHERE uid={$uid}");
			return false;
		}
		$inserted++;
	}
	if($inserted < 1) $_pm['mysql']->query("DELETE FROM tgt WHERE uid={$uid}");
	return $inserted > 0;
}

function getTasks($task,&$user,&$petsAll,&$bbs)
{
	//echo '$taskid='.$taskid.'$user='.count($user).',$petsAll='.count($petsAll).',$bbs='.count($bbs).',$memtask='.count($memtask)."\n";
	global $_pm;
	$taskid = $task['id'];
	if(empty($taskid))
	{
		//die("数据错误！".__LINE__);
		return false;
	}
	//$a = count($memtask);


	$taskinfo = $task;

	//print_r($taskinfo);
	if(!is_array($taskinfo)) //die("数据错误！".__LINE__);
		return false;
	if(!taskScheduleIsActive($taskinfo)) return false;

	//只能完成一次,等级限制的任务
	if(!empty($taskinfo['limitlv']))
	{
		$limitarr = explode(",",$taskinfo['limitlv']);//lv:1|0,jifen:1,cishu:1:1
		if(is_array($limitarr))
		{
			foreach($limitarr as $v)
			{
				$limitarrs = explode(":",$v);
				$limitType = isset($limitarrs[0]) ? strtolower(trim($limitarrs[0])) : '';
				if($limitType === 'level') $limitType = 'lv';
				switch($limitType)
				{
					case "lv":
						foreach($petsAll as $bb)
						{
							if($bb['id'] == $user['mbid'])
							{
								$blv = $bb['level'];
							}
						}
						if(empty($blv))
						{
							//die("请先到牧场设置主战！");
							return false;
						}
						$lvarr = explode("|",$limitarrs[1]);
						if($lvarr[1] == "0")
						{
							if($blv < $lvarr[0])
							{
								//die("您的等级不够接受此任务！");
								return false;
							}
						}
						else
						{
							if($blv < $lvarr[0] || $blv > $lvarr[1])
							{
								//die("您的等级不在可接此任务范围之内！");
								return false;
							}
						}
						break;


					case "xfyb"://字段格式xfyb：xxxxxxxx|xxxxxxxx；xxxx|yyyy代表某年某月某日到某年某月某日需要消费须在xxxx到yyyy间的玩家才能接受任务
						$safeYbName = $_pm['mysql']->escape($user['name']);
						$sql="select yb,buytime from yblog where nickname='{$safeYbName}'";
						$t=$_pm['mysql'] -> getRecords($sql);
						if(!is_array($t))
						{
							//die("你未进行元宝消费，无法领取任务!");
							return false;
						}else{

							$xfyb=explode(";",$limitarrs[1]);
							if(count($xfyb) < 2) return false;
                                $xfyb1=explode("|",$xfyb[0]);
                                $xfyb2=explode("|",$xfyb[1]);
                                if(count($xfyb1) < 2 || count($xfyb2) < 2) return false;
                                $sum_yb=0;
							if(is_array($xfyb2) && is_array($xfyb1)){
								foreach($t as $k=>$v){
									if(date('Ymd',$v['buytime'])>=$xfyb1[0] && date('Ymd',$v['buytime'])<=$xfyb1[1]){
										$sum_yb+=$v['yb'];
									}
								}

								$taskidxfyb=$_pm['mysql']->getRecords('select id,limitlv from task where  left(limitlv,4)="xfyb"');
								if(!is_array($taskidxfyb)) $taskidxfyb = array();
								$taskidxfybs=array();
								$taskidxfybinfos=array();
								foreach($taskidxfyb as $row)
								{
									$taskidxfybs[$row['id']]=$row['id'];
									$taskidxfybinfos[$row['id']]=$row['limitlv'];
								}

								$mytasklogs=array();
								if(!empty($taskidxfybs))
								{
									$mytasklogssql='select taskid from tasklog where uid='.$_SESSION['id'].' and taskid in ('.implode(',',array_values($taskidxfybs)).');';
									$mytasklogs=$_pm['mysql']->getRecords($mytasklogssql);
									if(!is_array($mytasklogs)) $mytasklogs = array();
								}
								$myusedtaskyblog=0;
								if(!empty($mytasklogs)){
									foreach($mytasklogs as $tlog)
									{
										if(!isset($taskidxfybinfos[$tlog['taskid']])) continue;
										$strtlog=explode(';',$taskidxfybinfos[$tlog['taskid']]);
										if(count($strtlog) < 2) continue;
										$yblogstr=explode('|',$strtlog[1]);
										$strtlog=explode(':',$strtlog[0]);
										if(count($strtlog) < 2) continue;
										$strtlog=explode('|',$strtlog[1]);
										if(count($strtlog) < 2 || count($yblogstr) < 1) continue;
                                        if(intval($strtlog[1])>$xfyb1[0])
                                        {
											$myusedtaskyblog+=intval($yblogstr[0]);
										}
									}
								}
								$sum_yb-=$myusedtaskyblog;

								if($xfyb2[1]==0){
									if($sum_yb<=$xfyb2[0]){
										//die("您的元宝消费在{$xfyb2[0]}以上才可领取此任务！");
										return false;
									}
								}elseif($xfyb2[0]>0 && $xfyb2[1]>=0){
									if($sum_yb<=$xfyb2[0] || $sum_yb>=$xfyb2[1] ){
										//die("您的元宝消费量不在领取此任务的范围内！");
										return false;
									}
								}else{
									//die("领取任务出错！");
									return false;
								}
							}else{
								//die("领取任务出错！");
								return false;
							}
						}
						break;

					case "xfsj":// task表中limitlv字段中添加xfsj条件格式xfsj：xxxxxxxx|xxxxxxxx代表某年某月某日到某年某月某日需要消费才能接受任务
						$jc=0;
						$xfsj=explode("|",$limitarrs[1]);
						if(count($xfsj) < 2) return false;
						$safeYbName = $_pm['mysql']->escape($user['name']);
						$sql="select id,yb,buytime from yblog where nickname='{$safeYbName}'";
						$t=$_pm['mysql'] -> getRecords($sql);
						if(!is_array($t) || empty($t)) return false;
						$check = $_pm['mysql'] -> getOneRecord("select time from tasklog where uid = {$_SESSION['id']} and taskid = 88888 order by id desc limit 1");
						$count = count($t) - 1;
						if(is_array($check) && isset($t[$count]['id']) && $t[$count]['id'] <= $check['time']){
							//die('这段时间您没有新的消费记录，不能接受此任务！');
							return false;
						}

						if(!is_array($t))
						{
							//die("你未进行消费，无法领取任务!");
							return false;
						}else{
							foreach($t as $k=>$v){
							 //如果检测到有消费则跳出，否则直到检测完所有数据

								if(date('Ymd',$v['buytime'])>=$xfsj[0] && date('Ymd',$v['buytime'])<=$xfsj[1]){
									$jc=1;
									break;
								}
							}
						}
						if($jc==0){
							//die("你未进行消费，无法领取任务!{$tt} |{$xfsj[0]} | {$xfsj[1]}");
							return false;
						}
						break;

					case "cishu":
						// cishu:X:Y daily window task limit
						if(count($limitarrs) < 3) return false;
						$time = strtotime(date('Ymd',time())) - (max(1,intval($limitarrs[2])) - 1) * 24 * 3600;
						$sql = "SELECT count(*) sl FROM tasklog WHERE uid = {$_SESSION['id']} and taskid = {$taskid} and time > {$time}";
						$arr = $_pm['mysql'] -> getOneRecord($sql);
						if(is_array($arr))
						{
							if($arr['sl'] >= $limitarrs[1])
							{
								//die("在{$limitarrs[2]}天内您只能完成$limitarrs[1]次任务！");
								return false;
							}
						}
						break;

					case "czl"://判断主战宠物的成长值是否够格接受此任务
						$lvarr = explode("|",$limitarrs[1]);
						if(!isset($lvarr[1])) $lvarr[1] = 0;
						$mainBid = isset($user['mbid']) ? intval($user['mbid']) : 0;
						$petsmain = array('czl' => 0);
						foreach($petsAll as $pv){
							if(is_array($pv) && isset($pv['id']) && intval($pv['id']) == $mainBid){
								$petsmain = $pv;
								break;
							}
						}
						if(!isset($petsmain['czl'])) $petsmain['czl'] = 0;

						if($lvarr[1]==0){
							if($lvarr[0]>$petsmain['czl']){
								//die("该宠物成长值为".$petsmain['czl']."，无法领取该任务!");
								return false;
							}
						}
						if($lvarr[1]>0){
							if(!($lvarr[0]<=$petsmain['czl'] && $lvarr[1]>=$petsmain['czl'])){
								//die("该宠物成长值不在此任务范围内，无法领取任务！");
								return false;
							}
						}
						break;
					case "comself"://comself:X 如果玩家身主战宠物ID为X，则可以接受任务。
						$abcarr = explode("|",$limitarrs[1]);
						$bbarr = "";
						foreach($petsAll as $pv)
						{
							if($pv['id'] == $user['mbid'])
							{
								$bname = $pv['name'];
							}
						}
						$bnamearr = array();
						foreach($abcarr as $av)
						{
							foreach($bbs as $bbav)
							{
								if($bbav['id'] == $av)
								{
									$bnamearr[] = $bbav['name'];
									break;
								}
							}
						}
						if(!in_array($bname,$bnamearr))
						{
							//die("您的当前主宠不能接受此任务！");
							return false;
						}
						break;
					case "jifen"://jifen:X 只有在积分达到X时才可以接受任务
						if($user['score'] < $limitarrs[1])
						{
							//die("您的当前积分不够接此任务！");
							return false;
						}
						break;
					case "vip"://jifen:X 只有在积分达到X时才可以接受任务
						if($user['vip'] < $limitarrs[1])
						{
							//die("您的vip积分不够接此任务！");
							return false;
						}
						break;
					case 'merge':
						$merge = $_pm['mysql'] -> getOneRecord("SELECT merge FROM player_ext WHERE uid = {$_SESSION['id']}");
						if(!is_array($merge) || !isset($merge['merge']) || intval($merge['merge']) < 1){
							//die('您目前未婚，不能接受此任务！');
							return false;
						}
				}
			}
		}
	}
	$arr = "";
	if(empty($taskinfo['cid']))
	{
		$sql = "SELECT taskid FROM tasklog WHERE uid = {$_SESSION['id']} and taskid = {$taskid}";
		$arr = $_pm['mysql'] -> getOneRecord($sql);
	}
	if(is_array($arr))
	{
		//die("该任务只能接受一次~！");
		return false;
	}
	//只能完成一次的任务在这里结束

	$usertaskarr = $_pm['mysql'] -> getRecords("SELECT taskid FROM task_accept WHERE uid = {$_SESSION['id']}");
	if(is_array($usertaskarr)){
		foreach($usertaskarr as $v){
			$accept[] = $v['taskid'];
		}
		if(in_array($taskid,$accept)){
			//die("您已经接受此任务！");
			return false;
		}
		if(count($usertaskarr) >= 15){
			//die("您已经接受了15个任务，超过了最大限制！");
			return false;
		}
	}


	//echo "恭喜您，成功接受此任务！";
	return true;
}


//客户端现实提示信息$msg,执行脚本$script，如果$back=true将自动跳回前一个页面
function alert($msg,$script='',$back=false)
{
	$httpReferer = (isset($_SERVER['HTTP_REFERER']) && !is_array($_SERVER['HTTP_REFERER'])) ? str_replace(array("\r","\n"), '', $_SERVER['HTTP_REFERER']) : '';
	$requestUri = (isset($_SERVER['REQUEST_URI']) && !is_array($_SERVER['REQUEST_URI'])) ? str_replace(array("\r","\n"), '', $_SERVER['REQUEST_URI']) : '';
	if($back&&!empty($httpReferer)&&strpos($httpReferer, $requestUri) === false)
	{
		header('location:'.$httpReferer);
		echo $msg;
	}
	$msgJs = addcslashes($msg, "\\\"\r\n");
	echo '<script language="javascript">parent.Alert("'.$msgJs.'");'.$script.'</script>';
}


//把memcache中的某个一自增数字做键的数字二维数组转换成字符串保存起来
function memArr2Str($data,$key,$spFiled="`_`",$spLine="$+$",$suffix='str')
{
	global $_pm;
	//$data=$_pm['mem']->get($key);
	//if(!is_array($data)&&strlen($data)>3) $data=unserialize($data);
	$str='';
	$con='';

	if(count($data)>0){
		foreach($data as $v)
		{
			if(count($v)>0)
			{
				$str.=$con.implode($spFiled,$v);
				$con=$spLine;
			}
		}
	}
	$key.=$suffix;
	$_pm['mem']->setnsnc($key,$str);

}

function kdjlCurrentUserIsAdmin($accountName = null)
{
	if($accountName === null)
	{
		$accountName = isset($_SESSION['username']) ? $_SESSION['username'] : '';
	}
	$accountName = trim(strval($accountName));
	if($accountName === '')
	{
		return false;
	}

	$welcome = memContent2Arr('db_welcome', 'code');
	if(!is_array($welcome) || !isset($welcome['admin']['contents']) || $welcome['admin']['contents'] === '')
	{
		return false;
	}
	$adminNames = preg_split("/(?:,|;|\xEF\xBC\x8C|\xEF\xBC\x9B)+/", $welcome['admin']['contents'], -1, PREG_SPLIT_NO_EMPTY);
	if(!is_array($adminNames))
	{
		return false;
	}
	foreach($adminNames as $adminName)
	{
		if(trim($adminName) === $accountName)
		{
			return true;
		}
	}
	return false;
}
?>
