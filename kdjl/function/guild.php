<?php
header('Content-Type:text/html;charset=UTF-8');
require_once('../config/config.game.php');
require_once(dirname(__FILE__).'/../sec/activity_robot_fnc.php');
secStart($_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');
$guildBattleMaxLevelGap = 9;

function guildHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function guildJsSingle($value)
{
	return addcslashes((string)$value, "\\'\n\r");
}

function guildLegacyTextLength($value)
{
	$value = strval($value);
	if($value === '') return 0;
	if(!preg_match_all('/./us', $value, $matches)) return PHP_INT_MAX;
	$length = 0;
	foreach($matches[0] as $character) $length += strlen($character) == 1 ? 1 : 2;
	return $length;
}

function guildTransactionDie($message)
{
	global $_pm;
	if(isset($_pm['mysql'])) $_pm['mysql']->query('ROLLBACK');
	die($message);
}

function guildClearUserCache($targetUid, $clearBag)
{
	global $_pm;
	$targetUid = intval($targetUid);
	if($targetUid < 1) return;
	$_pm['mem']->del(strval($targetUid));
	if($clearBag) $_pm['mem']->del($targetUid.'bag');
}

$op = (isset($_GET['op']) && !is_array($_GET['op'])) ? $_GET['op'] : '';
//guild_update_mem();exit;
if($op == 'show'){
	$id = (isset($_GET['id']) && !is_array($_GET['id'])) ? intval($_GET['id']) : 0;

	if($id == 1){
		$order = 'honor';
	}else if($id == 2){
		$order = 'level';
	}else if($id == 3){
		$order = 'number_of_member';
	}else{
		die('');
	}
	$guild = $_pm['mysql'] -> getRecords("SELECT guild.id as gid,guild.name as gname,president_id,honor,level,number_of_member,player.nickname FROM guild,player WHERE player.id = guild.president_id ORDER BY ($order+0) DESC");
/*<table border="0" cellspacing="0" class="tit01">
                    <tr>
                      <td width="20%" height="24" align="center">家族名称</td>
                      <td width="20%" align="center" >族长</td>
                      <td width="20%" align="center" >家族荣誉度</td>
                      <td width="20%" align="center" >家族等级</td>
                      <td width="20%" align="center" >家族成员</td>
                    </tr>
                  </table>
				<div class="dt_list clearfix">
				  <table class="tit01" id="shoplist">
				    #guild#
			      </table>*/
	$guildstr = '<table border="0" cellspacing="0" class="tit01">
                    <tr>
                      <td width="20%" height="24" align="center">家族名称</td>
                      <td width="20%" align="center" >族长</td>
                      <td width="20%" align="center" >家族荣誉度</td>
                      <td width="20%" align="center" >家族等级</td>
                      <td width="20%" align="center" >家族成员</td>
                    </tr>
                  </table>
				<div class="dt_list clearfix">
				  <table class="tit01" id="shoplist">';
	if(!is_array($guild)){
		$guildstr .= '<tr>
				  <td height="24" colspan="5" align="center">暂时没有家族</td>
				</tr>';
	}else{
		foreach($guild as $v){
			$gid = isset($v['gid']) ? intval($v['gid']) : 0;
			$gname = guildHtml(isset($v['gname']) ? $v['gname'] : '');
			$nickname = guildHtml(isset($v['nickname']) ? $v['nickname'] : '');
			$honor = isset($v['honor']) ? intval($v['honor']) : 0;
			$level = isset($v['level']) ? intval($v['level']) : 0;
			$memberNumber = isset($v['number_of_member']) ? intval($v['number_of_member']) : 0;
			$guildstr .= '<tr>
				  <td width="20%" height="24" style="cursor:pointer" onclick="show_guild_info('.$gid.')" align="center" onmouseover="this.style.color=\'#ff0000\'" onmouseout="this.style.color=\'#600\'">'.$gname.'</td>
				  <td width="20%" height="24" align="center">'.$nickname.'</td>
				  <td width="20%" height="24" align="center">'.$honor.'</td>
				  <td width="20%" height="24" align="center">'.$level.'</td>
				  <td width="20%" height="24" align="center">'.$memberNumber.'</td>
				</tr>';
		}

	}
	$guildstr .= '</table>';
	echo $guildstr;
}else if($op == 'create'){
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

		$list = array('{','}','gm','日','客服','法轮功','胡锦涛','妈','搞','\?','<','>','管理','系统','公告','颁奖','元宝','出售','提示','kefu','代练','共产党','国民党','商务','5173','销售','淘宝','江泽民','毛泽东','温家宝','周恩来','习近平','奥巴马','热比娅','台湾','西藏','新疆','藏','本拉登','暴动','吸毒','赌博','走私','抽头','开房','一夜情','海洛因','大麻','鸦片','傻逼','shit','bitch');

		foreach($list as $v)
		{
			$reg = '/'.preg_quote($v, '/').'/i';
			if(preg_match($reg,$str)){
				return false;
			}
		}
		return true;
	}
	$guild_name = (isset($_GET['name']) && !is_array($_GET['name'])) ? $_GET['name'] : '';
	$guild_info = (isset($_GET['info']) && !is_array($_GET['info'])) ? $_GET['info'] : '';
	$check_time = $_pm['mem'] -> get('last_exit_guild_time_'.$uid);
	if ($check_time > 0) {
		$time = time();
		$ctime = ($time - $check_time) - 24 * 3600;
		if($ctime < 0){
			die('10');
		}
	}
	if (getWordCharInt($guild_name)===false || getWordCharInt($guild_info)===false)
	{
		die("输入的名称和信息不能含有特殊符号或者禁止使用的词！");
	}
	$guildNameLength = guildLegacyTextLength($guild_name);
	$guildInfoLength = guildLegacyTextLength($guild_info);
	if($guild_name == '' || $guild_info == '' || $guildNameLength < 4 || $guildNameLength > 16 || $guildInfoLength > 400){
		die('1');//格式不正确
	}
	//判断是否符合创建条件

	if(!$_pm['mysql'] -> query("INSERT INTO player_ext(uid,bbshow) VALUES($uid,5) ON DUPLICATE KEY UPDATE uid=uid")){
		die('7');
	}
	$user = $_pm['mysql'] -> getOneRecord("SELECT guild_request,vip FROM player_ext,player WHERE player.id = player_ext.uid AND uid = $uid");
	if(!is_array($user)){
		die('7');
	}

	$guildcheck = $_pm['mysql'] -> getOneRecord("SELECT member_id FROM guild_members WHERE member_id = $uid");
	if(is_array($guildcheck)){
		die('3');//您已经加入到其它家族，不能创建！
	}

	if($user['vip'] < 10){
		die('4');//您的积分不足10，不能创建！
	}

	//判断是否拥有当月vip卡
	/*$arr = array("1427","1474","1475","1476","1477","1478","1479","1480","1481","1482","1483","1484","1485");
	$arrayid=date('n');
	if($arrayid=='1'){
		$arraycode=array("1427",$arr[$arrayid],$arr[12]);
	}else{
		$arrayidjian=$arrayid-1;
		$arraycode=array("1427",$arr[$arrayidjian],$arr[$arrayid]);
	}
	$u_bags=getUserBagByIds($uid, $arraycode, $_pm['mysql']);

   $userIsVip = false;
	foreach($u_bags as $v)
	{
		if($v && isset($v['sums']) && $v['sums'] > 0)
		{
			$userIsVip = true;
			break;
		}
	}

	if($userIsVip !== true){
		die('5');//您没有当月vip卡，不能创建！
	}*/
	//判断通过，创建家族
	$uid = intval($uid);
	$guildNameSql = $_pm['mysql']->escape($guild_name);
	$guildInfoSql = $_pm['mysql']->escape($guild_info);
	$time = time();
	if(!$_pm['mysql'] -> query('START TRANSACTION')){
		die('7');
	}
	if(!$_pm['mysql'] -> query("INSERT INTO player_ext(uid,bbshow) VALUES($uid,5) ON DUPLICATE KEY UPDATE uid=uid")){
		$_pm['mysql'] -> query('ROLLBACK');
		die('7');
	}
	$lockedUser = $_pm['mysql']->getOneRecord("SELECT player.vip,player_ext.guild_request
		FROM player INNER JOIN player_ext ON player.id=player_ext.uid
		WHERE player.id=$uid FOR UPDATE");
	if(!is_array($lockedUser)){
		$_pm['mysql']->query('ROLLBACK');
		die('7');
	}
	if(intval($lockedUser['guild_request']) > 0){
		$_pm['mysql']->query('ROLLBACK');
		die('2');
	}
	$lockedMember = $_pm['mysql']->getOneRecord("SELECT member_id FROM guild_members WHERE member_id=$uid FOR UPDATE");
	if(is_array($lockedMember)){
		$_pm['mysql']->query('ROLLBACK');
		die('3');
	}
	if(intval($lockedUser['vip']) < 10){
		$_pm['mysql']->query('ROLLBACK');
		die('4');
	}
	$sameName = $_pm['mysql']->getOneRecord("SELECT id FROM guild WHERE name='$guildNameSql' LIMIT 1 FOR UPDATE");
	if(is_array($sameName)){
		$_pm['mysql']->query('ROLLBACK');
		die('9');
	}
	$bagCheck = $_pm['mysql'] -> getOneRecord("SELECT id FROM userbag WHERE uid = $uid AND pid = 2494 AND sums > 0 AND zbing = 0 AND (cantrade IS NULL OR cantrade <> 3) ORDER BY id LIMIT 1 FOR UPDATE");
	if(!is_array($bagCheck)){
		$_pm['mysql'] -> query('ROLLBACK');
		die('5');
	}
	$guildCreated = $_pm['mysql'] -> query("INSERT INTO guild(name,info,creator_id,president_id,create_time,victory_times,failed_times) VALUES('$guildNameSql','$guildInfoSql',$uid,$uid,$time,0,0)");
	$guildCreateError = $guildCreated ? 0 : mysql_errno($_pm['mysql'] -> getConn());
	$lastid = $_pm['mysql'] ->last_id();
	if($guildCreated && mysql_affected_rows($_pm['mysql'] -> getConn()) == 1){
		$vipUsed = $_pm['mysql'] -> query("UPDATE player SET vip = vip - 10 WHERE id = $uid AND vip >= 10");

		if(!$vipUsed || mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){
			$_pm['mysql'] -> query('ROLLBACK');
			die('8');//没有足够积分
		}
		if(!$_pm['mysql'] -> query("UPDATE player_ext SET guild_request = 0 WHERE uid = $uid")){
			$_pm['mysql'] -> query('ROLLBACK');
			die('7');
		}
		//写入内存供聊天


		if(!$_pm['mysql'] -> query("INSERT INTO guild_members(member_id,guild_id,join_time,priv) VALUES($uid,$lastid,$time,3)") || mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){
			$_pm['mysql'] -> query('ROLLBACK');
			die('7');
		}
		$bagId = intval($bagCheck['id']);
		if(!$_pm['mysql'] -> query("UPDATE userbag SET sums = sums - 1 WHERE id = $bagId AND uid = $uid AND zbing = 0 AND sums > 0 AND (cantrade IS NULL OR cantrade <> 3)") || mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){
			$_pm['mysql'] -> query('ROLLBACK');
			die('5');
		}
		if(!$_pm['mysql'] -> query("DELETE FROM userbag WHERE id = $bagId AND uid = $uid AND sums <= 0 AND bsum <= 0 AND psum <= 0 AND pyb = 0 AND zbing = 0 AND (cantrade IS NULL OR cantrade <> 3)")){
			$_pm['mysql'] -> query('ROLLBACK');
			die('7');
		}
		if(!$_pm['mysql'] -> query('COMMIT')){
			$_pm['mysql'] -> query('ROLLBACK');
			die('7');
		}
		guildClearUserCache($uid, true);
		guild_update_mem();
		die('6');//创建成功！
	}else{
		$_pm['mysql'] -> query('ROLLBACK');
		if($guildCreateError == 1062) die('9');
		die('7');//创建失败!
	}
}else if($op == 'show_guild_info'){
	$gid = (isset($_GET['gid']) && !is_array($_GET['gid'])) ? intval($_GET['gid']) : 0;
	if($gid == 0){//0表示查询自己所在的队伍的信息
		//die('1');//参数有误！
		$ar = $_pm['mysql'] -> getOneRecord("SELECT guild_id FROM guild_members WHERE member_id = $uid");
		if(!is_array($ar)){
			die('<br />&nbsp;&nbsp;<span style="font-size:12px">您还没有加入任何家族！</font>');
		}
		$gid = $ar['guild_id'];
	}
	$arr = $_pm['mysql'] -> getOneRecord("SELECT guild.name as name,info,player.nickname as cname,president_id,honor,level,create_time,guild.number_of_member FROM guild,player WHERE player.id = guild.president_id AND guild.id = $gid");
	if(!is_array($arr)){
		die('2');//没有这个家族
	}
	$arrDefaults = array('name'=>'', 'info'=>'', 'cname'=>'', 'president_id'=>0, 'honor'=>0, 'level'=>0, 'create_time'=>0, 'number_of_member'=>0);
	foreach($arrDefaults as $arrDefaultKey => $arrDefaultValue)
	{
		if(!isset($arr[$arrDefaultKey])) $arr[$arrDefaultKey] = $arrDefaultValue;
	}
	$arr['honor'] = intval($arr['honor']);
	$arr['level'] = intval($arr['level']);
	$arr['create_time'] = intval($arr['create_time']);
	$arr['number_of_member'] = intval($arr['number_of_member']);
	$current = $_pm['mysql'] -> getOneRecord("SELECT max_member_number FROM guild_settings WHERE level = {$arr['level']}");
	if(!is_array($current)) $current = array('max_member_number' => 0);
	$current['max_member_number'] = isset($current['max_member_number']) ? intval($current['max_member_number']) : 0;
	$guild_bag = $_pm['mysql'] -> getRecords("SELECT pid,COALESCE(SUM(sums),0) AS sums FROM guild_bag WHERE guild_id = $gid GROUP BY pid");
	$memprops = kdjlSafeMemValue($_pm['mem']->get('db_propsid'), array());
	if(!is_array($guild_bag) || count($guild_bag) < 1){
		$itemstr = '暂时没有宝藏';
	}else{
		$itemstr = '';
		foreach($guild_bag as $v){
			$propId = intval($v['pid']);
			$propName = (isset($memprops[$propId]) && isset($memprops[$propId]['name'])) ? $memprops[$propId]['name'] : $propId;
			$itemstr .= ','.$propName.'×'.intval($v['sums']);//echo $itemstr.'<br />';
		}
		$itemstr = substr($itemstr,1);
	}
//exit;

	$next_need_str = '';
	$check = $_pm['mysql'] -> getOneRecord("SELECT guild_id,priv FROM guild_members WHERE guild_id = $gid AND member_id = $uid");
	$guildAutomation = kdjlGuildAutomationInfo($_pm['mysql'], $gid);
	if (is_array($check)) {
		/*$str = '<tr>
              <td height="25" colspan="3" style="border-bottom-style:solid; border-bottom-width:1px; border-bottom-color:#000000">家族信息                </td>
              <td height="25" style="border-bottom-style:solid; border-bottom-width:1px; border-bottom-color:#000000;" onclick="giveProps()">家族捐献</td>
            </tr><tr>';*/

		  if($check['priv'] == '3'){
				$str = '<div class="bb01"><img src="../new_images/ui/icon15.jpg" width="76" height="25" /></div>
						<div class="family_wide_button family_header_button" style="left:550px;" onclick="giveProps()">家族捐献</div>
						<div class="family_wide_button family_header_button" style="left:626px;" onclick="dissolut()">解散家族</div>
						<div class="family_wide_button family_header_button" style="left:706px;" onclick="$(\'con_tab_2\').style.display=\'none\';$(\'con_tab_1\').style.display=\'block\'">返回</div>
			  </div><div class="box03">';
		  }else{
			$str = '<div class="bb01"><img src="../new_images/ui/icon15.jpg" width="76" height="25" /></div>
						<div class="family_wide_button family_header_button" style="left:550px;" onclick="giveProps()">家族捐献</div>
						<div class="family_wide_button family_header_button" style="left:626px;" onclick="exit()">退出家族</div>
						<div class="family_wide_button family_header_button" style="left:706px;" onclick="$(\'con_tab_2\').style.display=\'none\';$(\'con_tab_1\').style.display=\'block\'">返回</div>
			  </div><div class="box03">';
		  }

		//读出升级到下一级要的荣誉点及道具
		$next_level = $arr['level'];
		$next_level = intval($arr['level']);
		$next_level_need = $_pm['mysql'] -> getOneRecord("SELECT * FROM guild_settings WHERE level = $next_level");
		if(!is_array($next_level_need)) $next_level_need = array('need_props' => '', 'need_honor' => 0, 'need_member_number' => 0);
		$next_level_need_props = '';


		if (!empty($next_level_need['need_props'])) {
			$next_p_arr = explode(',',$next_level_need['need_props']);
			foreach ($next_p_arr as $v){
				//$next_level_need_props .= ','.$memprops[$v]['name'];
				$nv = explode('|',$v);
				$needPropId = isset($nv[0]) ? intval($nv[0]) : 0;
				$needPropSum = isset($nv[1]) ? intval($nv[1]) : 0;
				if($needPropId <= 0) continue;
				$have_props = $_pm['mysql'] -> getOneRecord("SELECT COALESCE(SUM(sums),0) AS sums FROM guild_bag WHERE pid = $needPropId AND guild_id = $gid");
				if(is_array($have_props)) $havesums = intval($have_props['sums']);
				else $havesums = 0;//print_r($nv);
				$needPropName = (isset($memprops[$needPropId]) && isset($memprops[$needPropId]['name'])) ? $memprops[$needPropId]['name'] : $needPropId;
				$next_level_need_props .= ','.$needPropName.':'.$needPropSum.'/'.$havesums;
			}
			$next_level_need_props = substr($next_level_need_props,1);
		}else $next_level_need_props = '';

		if(!is_array($next_level_need)) $next_level_need = array('need_honor' => 0, 'need_member_number' => 0);
		$nextNeedHonor = isset($next_level_need['need_honor']) ? intval($next_level_need['need_honor']) : 0;
		$nextNeedMembers = isset($next_level_need['need_member_number']) ? intval($next_level_need['need_member_number']) : 0;
		$next_need_str = '升级到 '.($next_level+1).' 级需要的物品'.$next_level_need_props.',需要荣誉:'.$nextNeedHonor.',需要成员数:'.$nextNeedMembers;

	}else{
		/*$str = '<tr>
              <td height="25" colspan="3" style="border-bottom-style:solid; border-bottom-width:1px; border-bottom-color:#000000">家族信息                </td>
              <td height="25" style="border-bottom-style:solid; border-bottom-width:1px; border-bottom-color:#000000"></td>
            </tr><tr>';*/
		$str = '
		<div class="box01">
			<div class="box02"><div class="bb01"><img src="../new_images/ui/icon15.jpg" width="76" height="25" /></div>
						<div class="family_wide_button family_header_button" style="left:626px;" onclick="apply();">申请加入</div>
						<div class="family_wide_button family_header_button" style="left:706px;" onclick="$(\'con_tab_2\').style.display=\'none\';$(\'con_tab_1\').style.display=\'block\'">返回</div>
		  </div><div class="box03">';
	}

	$guildNameHtml = guildHtml($arr['name']);
	$guildLeaderHtml = guildHtml($arr['cname']);
	$guildInfoHtml = guildHtml($arr['info']);
	$guildBagHtml = guildHtml($itemstr);
	$nextNeedTitle = guildHtml($next_need_str);
	$autoJoinEnabled = !empty($guildAutomation['auto_accept_join']);
	$autoJoinStatus = $autoJoinEnabled ? '自动同意' : '人工审核';
	if(is_array($check) && intval($check['priv']) === 3)
	{
		$autoJoinChecked = $autoJoinEnabled ? ' checked="checked"' : '';
		$autoJoinControl = '<label class="family_wide_button family_auto_join_button"><input type="checkbox"'.$autoJoinChecked.
			' onclick="setGuildAutoJoin('.$gid.',this)" /><span>自动同意玩家加入</span></label>';
	}
	else
	{
		$autoJoinControl = $autoJoinStatus;
	}
	$autoJoinSettingRow = '<tr><td class="family_info_label" align="left">加入设置：</td>'.
		'<td class="family_info_detail" colspan="5" align="left">'.$autoJoinControl.'</td></tr>';
	$str .= '<table border="0" cellspacing="0" class="tit01 family_info_table">
					<colgroup>
					  <col style="width:82px;" />
					  <col style="width:108px;" />
					  <col style="width:90px;" />
					  <col style="width:54px;" />
					  <col style="width:78px;" />
					  <col style="width:188px;" />
					</colgroup>
                    <tr>
                      <td class="family_info_label" align="left">家族名称：</td>
                      <td class="family_info_value" align="left">'.$guildNameHtml.'</td>
                      <td class="family_info_label" align="left">家族荣誉度：</td>
                      <td class="family_info_value" align="left">'.$arr['honor'].'</td>
                      <td class="family_info_label" align="left">族长：</td>
                      <td class="family_info_value" align="left">'.$guildLeaderHtml.'</td>
                    </tr>
					<tr>
                      <td class="family_info_label" align="left">家族宝藏：</td>
                      <td class="family_info_value" align="left">'.$guildBagHtml.'</td>
                      <td class="family_info_label" align="left" title="'.$nextNeedTitle.'">家族等级：</td>
                      <td class="family_info_value" align="left">'.$arr['level'].'</td>
                      <td class="family_info_label" align="left">创建时间：</td>
                      <td class="family_info_value" align="left">'.date('Y-m-d H:i',$arr['create_time']).'</td>
                    </tr>
					<tr>
                      <td class="family_info_label" align="left">家族成员：</td>
                      <td class="family_info_value" align="left">'.$arr['number_of_member'].'/'.$current['max_member_number'].'</td>
                      <td class="family_info_label" align="left">家族福利</td>
					  <td class="family_info_value" align="left" style="cursor:pointer" onclick="guild_welfare()"><input type="image" name="Submit" value="领取" src="../new_images/ui/1.gif" /></td>
					  <td class="family_info_value" colspan="2" align="left" style="cursor:pointer" onclick="next_level()"><input type="image" name="Submit" value="领取" src="../new_images/ui/2.gif" /></td>
                    </tr>
					<tr>
                      <td class="family_info_label" align="left">家族介绍：</td>
                      <td class="family_info_detail" colspan="5" align="left">'.$guildInfoHtml.'</td>
                    </tr>'.$autoJoinSettingRow.'
                  </table>
				  <table border="0" cellspacing="0" class="tit01">
				  <tr>
				    <td width="18%" height="24" align="center" bgcolor="#F4EDD7" style="padding-left:10px;">游戏名称</td>
				    <td width="16%" align="center" bgcolor="#F4EDD7" >等级</td>
				    <td width="14%" align="center" bgcolor="#F4EDD7" >成长</td>
				    <td width="23%" align="center" bgcolor="#F4EDD7" >职位</td>
				    <td colspan="2" align="center" bgcolor="#F4EDD7" >管理</td>
			      </tr>
				  <tr>
				    <td height="1" colspan="6" align="left" style="padding-left:10px;" bgcolor="#CC6600"></td>
			      </tr>';

	/*$str .= '<tr>
              <td height="23" align="center">家族名称：</td>
			  <td height="23" align="left">'.$arr['name'].'</td>
			  <td height="23" align="right">家族荣誉点：</td>
			  <td height="23" align="left">'.$arr['honor'].'</td>
			</tr>
            <tr>
              <td width="20%" height="20" align="center">族长：</td>
              <td width="37%" align="left">'.$arr['cname'].'</td>
              <td width="25%" align="right">家族宝藏：</td>
              <td width="25%" align="left">'.$itemstr.'</td>
            </tr>
            <tr>
              <td height="20" align="center">&nbsp;</td>
              <td align="left">&nbsp;</td>
              <td align="right">&nbsp;</td>
              <td align="left">&nbsp;</td>
            </tr>
            <tr>
              <td height="20" align="center">家族等级：</td>
              <td align="left" title="'.$next_need_str.'">'.$arr['level'].
              (is_array($check)?'<span style="cursor:pointer" onclick="guild_welfare()">家族福利</span>  <span style="cursor:pointer" onclick="next_level()">升级</span>':'') .'</td>
              <td align="right">&nbsp;</td>
              <td align="left">&nbsp;</td>
            </tr>
            <tr>
              <td height="20" align="center">创建时间：</td>
              <td align="left">'.date('Y-m-d H:i',$arr['create_time']).'</td>
              <td align="right">&nbsp;</td>
              <td align="left">&nbsp;</td>
            </tr>
            <tr>
              <td height="20" align="center">家族介绍：</td>
              <td align="left">'.$arr['info'].'</td>
              <td align="right">&nbsp;</td>
              <td align="left">&nbsp;</td>
            </tr>
            <tr>
              <td height="20" align="center">家族成员：'.$arr['number_of_member'].'/'.$current['max_member_number'].'</td>
              <td align="left">&nbsp;</td>
              <td align="right">&nbsp;</td>
              <td align="left">&nbsp;</td>
            </tr>
            <tr>
              <td height="20" colspan="4" align="center"><table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="24%" height="20" align="center">游戏名</td>
                  <td width="18%" height="20" align="center">等级</td>
                  <td width="16%" height="20" align="center">成长</td>
                   <td width="12%" height="20" align="center">职位</td>
                  <td width="30%" height="20" align="center">&nbsp;</td>
                </tr>';*/

$guild_member = $_pm['mysql'] -> getRecords("SELECT player.id as pid,player.nickname as pnickname,userbb.level,userbb.czl,priv FROM player,userbb,guild_members WHERE player.id = guild_members.member_id AND player.mbid = userbb.id AND userbb.uid = player.id AND guild_members.guild_id = $gid ORDER BY priv DESC");
$applyarr = $_pm['mysql'] -> getRecords("SELECT player.id as pid,player.nickname as pnickname,userbb.level,userbb.czl FROM player,userbb,player_ext WHERE player.id = player_ext.uid AND player.mbid = userbb.id AND userbb.uid = player.id AND player_ext.guild_request = $gid");

	if (!is_array($guild_member)) $guild_member = array();
	if (is_array($applyarr)) {
		$guild_member = array_merge($guild_member,$applyarr);
	}
	//print_r($applyarr);

	$v = '';

	foreach($guild_member as $v){
		$qx = '';
		if(!isset($v['priv'])) $v['priv'] = 0;
		$memberPriv = isset($v['priv']) ? intval($v['priv']) : 0;
		if ($memberPriv == 1) {
			$qx = '成员';
		}else if($v['priv'] == 2) $qx = '长老';
		else if($v['priv'] == 3) $qx = '族长';
		$memberNameHtml = guildHtml(isset($v['pnickname']) ? $v['pnickname'] : '');
		$memberNameJs = guildJsSingle(isset($v['pnickname']) ? $v['pnickname'] : '');
		$memberNameHtmlJs = guildJsSingle($memberNameHtml);
		$memberPid = isset($v['pid']) ? intval($v['pid']) : 0;
		$memberLevel = isset($v['level']) ? intval($v['level']) : 0;
		$memberCzl = isset($v['czl']) ? intval($v['czl']) : 0;
		$str .= '<tr>
				    <td height="24" align="center" style="padding-left:10px;cursor:pointer"onclick="$(\'permissions\').style.display=\'block\';$(\'qxname\').innerHTML=\''.$memberNameHtmlJs.'\';qxuid='.$memberPid.';setTimeout(\'guild_permissions_none()\',5000);" onmouseover="this.style.color=\'#ff0000\'" onmouseout="this.style.color=\'#600\'">'.$memberNameHtml.'&nbsp;</td>
				    <td align="center" >'.$memberLevel.'</td>
				    <td align="center" >'.$memberCzl.'</td>
				    <td align="center" >'.$qx.'</td>
				    <td width="14%" align="center" onmouseover="this.style.color=\'#ff0000\'" onmouseout="this.style.color=\'#600\'"><span onclick="friendlist(\''.$memberNameJs.'\')" style="cursor:pointer"><img src="../new_images/ui/add06.gif" border="0" /></span></td>
				    <td width="15%" align="center" onmouseover="this.style.color=\'#ff0000\'" onmouseout="this.style.color=\'#600\'"><span onclick="fire('.$memberPid.','.$gid.')" style="cursor:pointer"><img src="../new_images/ui/add07.gif" border="0" /></span></td>
			      </tr>';
		/*$str .= '<tr>
                  <td height="20" align="center" style="cursor:pointer" onclick="$(\'permissions\').style.display=\'block\';$(\'qxname\').innerHTML=\''.$v['pnickname'].'\';qxuid='.$v['pid'].'">'.$v['pnickname'].'</td>
                  <td height="20" align="center">'.$v['level'].'</td>
                  <td height="20" align="center">'.$v['czl'].'</td>
                  <td height="20" align="center">'.$qx.'</td>
                  <td height="20" align="center"><span onclick="friendlist(\''.$v['pnickname'].'\')" style="cursor:pointer">加为好友</span>&nbsp;&nbsp; <span onclick="fire('.$v['pid'].','.$gid.')" style="cursor:pointer">开除成员</span></td>
                </tr>';*/
	}

	$str .= '</table></div></div><div id="jzlevel" class="family_level_button" align="center" onclick="guild_level_info()"><img src=\'../new_images/ui/guild_next.gif\' />
						</div>';
	/*if (is_array($check)) {
		$str .= '<tr>
              <td height="20" colspan="3" align="left">&nbsp;&nbsp;<span onclick="exit()" style="cursor:pointer">退出家族</span>&nbsp;&nbsp;&nbsp;&nbsp;<span style="cursor:pointer" onclick="dissolut()">解散家族</span></td>
              <td height="20" align="center"><span onclick="$(\'guild_info\').style.display=\'none\';$(\'first\').style.display=\'block\'" style="cursor:pointer">返回</span></td>
            </tr>';
	}else{
		$str .= '<tr>
              <td height="20" colspan="3" align="left">&nbsp;&nbsp;<span onclick="apply()" style="cursor:pointer">申请加入</span></td>
              <td height="20" align="center"><span onclick="$(\'guild_info\').style.display=\'none\';$(\'first\').style.display=\'block\'" style="cursor:pointer">返回</span></td>
            </tr>';
	}*/
	echo $str;
}else if($op == 'set_auto_join'){
	$gid = (isset($_GET['gid']) && !is_array($_GET['gid'])) ? intval($_GET['gid']) : 0;
	$enabled = (isset($_GET['enabled']) && !is_array($_GET['enabled']) && intval($_GET['enabled']) === 1) ? 1 : 0;
	$selfUid = intval($uid);
	if($gid < 1 || $selfUid < 1) die('参数错误！');
	if(!kdjlMysqlTableHasColumn($_pm['mysql'], 'guild_automation', 'guild_id')) die('家族自动化数据尚未安装！');
	if(!$_pm['mysql']->query('START TRANSACTION')) die('保存失败！');
	$member = $_pm['mysql']->getOneRecord('SELECT priv FROM guild_members WHERE guild_id='.$gid.
		' AND member_id='.$selfUid.' FOR UPDATE');
	if(!is_array($member) || intval($member['priv']) !== 3) guildTransactionDie('只有族长可以修改此设置！');
	$sql = 'INSERT INTO guild_automation(guild_id,auto_accept_join) VALUES('.$gid.','.$enabled.') '.
		'ON DUPLICATE KEY UPDATE auto_accept_join=VALUES(auto_accept_join)';
	if(!$_pm['mysql']->query($sql) || !$_pm['mysql']->query('COMMIT')) guildTransactionDie('保存失败！');
	die('10');
}else if($op == 'fire'){
	$member_id = (isset($_GET['member_id']) && !is_array($_GET['member_id'])) ? intval($_GET['member_id']) : 0;
	$guild_id = (isset($_GET['guild_id']) && !is_array($_GET['guild_id'])) ? intval($_GET['guild_id']) : 0;
	$selfUid = intval($uid);

	if($member_id == 0 || $guild_id == 0){
		die('1');//操作有误！
	}
	if($member_id == $selfUid){
		die('2');//您不能操作自己！
	}
	if(!$_pm['mysql'] -> query('START TRANSACTION')) die('开除成员失败！');
	//检查是否有权限作此项操作
	$checkme = $_pm['mysql'] -> getOneRecord("SELECT priv FROM guild_members WHERE member_id = $selfUid AND guild_id = $guild_id FOR UPDATE");
	if(!is_array($checkme)){
		$_pm['mysql'] -> query('ROLLBACK');
		die('3');//您不在此家族，不能作此操作！
	}
	if($checkme['priv'] == 1){
		$_pm['mysql'] -> query('ROLLBACK');
		die('4');//您是普通成员，不能开除其它成员
	}

	$check = $_pm['mysql'] -> getOneRecord("SELECT priv FROM guild_members WHERE member_id = $member_id AND guild_id = $guild_id FOR UPDATE");

	if(is_array($check) && $checkme['priv'] <= $check['priv']){
		$_pm['mysql'] -> query('ROLLBACK');
		die('6');//您没有权限开除比您职位高的成员！
	}
	if(!is_array($check)){
		$apply = $_pm['mysql'] -> getOneRecord("SELECT guild_request FROM player_ext WHERE uid = $member_id AND guild_request = $guild_id FOR UPDATE");
		if (!is_array($apply)) {
			$_pm['mysql'] -> query('ROLLBACK');
			die('5');//对方不在此家族，您不能操作
		}
		$str = $_SESSION['nickname'].'拒绝了您的家族请求！';
		$strSql = $_pm['mysql']->escape($str);
		if (!$_pm['mysql'] -> query("UPDATE player_ext SET guild_request = 0 WHERE uid = $member_id AND guild_request = $guild_id") || mysql_affected_rows($_pm['mysql'] -> getConn()) != 1) {
			$_pm['mysql'] -> query('ROLLBACK');
			die('开除成员失败！');
		}
		if (!$_pm['mysql'] -> query("INSERT INTO information(uid,content) VALUES($member_id,'$strSql')")) {
			$_pm['mysql'] -> query('ROLLBACK');
			die('开除成员失败！');
		}
		if (!$_pm['mysql'] -> query('COMMIT')) {
			$_pm['mysql'] -> query('ROLLBACK');
			die('开除成员失败！');
		}
		guildClearUserCache($member_id, false);
		require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
		$s=new socketmsg();
		$rs=$s->sendMsg('an|'.$str,array($member_id));
		$rs=$s->sendMsg('SYSN|information-->',array($member_id));
		die('7');
	}
	$str = $_SESSION['nickname'].'把您被请出了家族！';
	$strSql = $_pm['mysql']->escape($str);
	if (!$_pm['mysql'] -> query("INSERT INTO information(uid,content) VALUES($member_id,'$strSql')")) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('开除成员失败！');
	}
	if (!$_pm['mysql'] -> query("DELETE FROM guild_members WHERE member_id = $member_id AND guild_id = $guild_id") || mysql_affected_rows($_pm['mysql'] -> getConn()) != 1) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('开除成员失败！');
	}
	if (!$_pm['mysql'] -> query("UPDATE guild SET number_of_member = IF(number_of_member > 0, number_of_member - 1, 0) WHERE id = $guild_id")) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('开除成员失败！');
	}
	if (!$_pm['mysql'] -> query('COMMIT')) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('开除成员失败！');
	}
	//$_pm['mysql'] -> query("UPDATE player_ext SET guild_request = 0 WHERE uid = $member_id AND guild_request = $guild_id");
	guild_update_mem();
	//退出后记录退出时间。写内内存
	$_pm['mem'] -> setns('last_exit_guild_time_'.$member_id,time().'');
	guildClearUserCache($member_id, false);
	require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
	$s=new socketmsg();
	$rs=$s->sendMsg('an|'.$str,array($member_id));
	$rs=$s->sendMsg('SYSN|information-->',array($member_id));
	die('7');
}else if($op == 'apply'){
	$gid = (isset($_GET['gid']) && !is_array($_GET['gid'])) ? intval($_GET['gid']) : 0;
	$uid = intval($uid);
	if($gid == 0){
		die('');
	}
	$check_time = $_pm['mem'] -> get('last_exit_guild_time_'.$uid);
	if ($check_time > 0) {
		$time = time();
		$ctime = ($time - $check_time) - 24 * 3600;
		if($ctime < 0){
			die('10');
		}
	}
	$ac = (isset($_GET['ac']) && !is_array($_GET['ac'])) ? $_GET['ac'] : '';
	if(!$_pm['mysql'] -> query('START TRANSACTION')) die('申请加入家族失败！');
	if(!$_pm['mysql'] -> query("INSERT INTO player_ext(uid,bbshow) VALUES($uid,5) ON DUPLICATE KEY UPDATE uid=uid")){
		$_pm['mysql'] -> query('ROLLBACK');
		die('申请加入家族失败！');
	}
	$user = $_pm['mysql'] -> getOneRecord("SELECT guild_request FROM player_ext WHERE uid = $uid FOR UPDATE");
	if(!is_array($user)){
		$_pm['mysql'] -> query('ROLLBACK');
		die('申请加入家族失败！');
	}
	if($ac != 'do'){
		if($user['guild_request'] > 0){
			$_pm['mysql'] -> query('ROLLBACK');
			die('1');//您已经申请加入别的家族，不能再申请了
		}
	}

	//echo "SELECT guild_id FROM guild_members WHERE member_id = {$_SESSION['id']}";exit;
	$guild_check = $_pm['mysql'] -> getOneRecord("SELECT guild_id FROM guild_members WHERE member_id = $uid FOR UPDATE");
	if(is_array($guild_check)){
		$_pm['mysql'] -> query('ROLLBACK');
		die('2');//您已经加入其它或者此家族，不能再申请！
	}

	//人数限制
	$settings = $_pm['mysql'] -> getOneRecord("SELECT max_member_number,number_of_member FROM guild_settings,guild WHERE guild.id = $gid AND guild.level=guild_settings.level FOR UPDATE");
	if(!is_array($settings) || $settings['number_of_member'] >= $settings['max_member_number']){
		$_pm['mysql'] -> query('ROLLBACK');
		die('4');//对方人数已满，不能再申请
	}
	$autoAcceptJoin = 0;
	if(kdjlMysqlTableHasColumn($_pm['mysql'], 'guild_automation', 'guild_id'))
	{
		$automation = $_pm['mysql']->getOneRecord('SELECT auto_accept_join FROM guild_automation WHERE guild_id='.$gid.' FOR UPDATE');
		if(is_array($automation) && !empty($automation['auto_accept_join'])) $autoAcceptJoin = 1;
	}
	if($autoAcceptJoin === 1)
	{
		$joinTime = time();
		$memberCount = intval($settings['number_of_member']);
		if(!$_pm['mysql']->query('INSERT INTO guild_members(member_id,guild_id,join_time,priv) VALUES('.
			$uid.','.$gid.','.$joinTime.',1)') || mysql_affected_rows($_pm['mysql']->getConn()) !== 1 ||
			!$_pm['mysql']->query('UPDATE guild SET number_of_member=number_of_member+1 WHERE id='.$gid.
				' AND number_of_member='.$memberCount) || mysql_affected_rows($_pm['mysql']->getConn()) !== 1 ||
			!$_pm['mysql']->query('UPDATE player_ext SET guild_request=0 WHERE uid='.$uid) ||
			!$_pm['mysql']->query('COMMIT'))
		{
			$_pm['mysql']->query('ROLLBACK');
			die('自动加入家族失败！');
		}
		guildClearUserCache($uid, false);
		guild_update_mem();
		die('11');//家族已自动同意申请
	}


	if (!$_pm['mysql'] -> query("UPDATE player_ext SET guild_request = $gid WHERE uid = $uid")) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('申请加入家族失败！');
	}
	//发请求给族长和长老（写入）
	$guild = $_pm['mysql'] -> getRecords("SELECT member_id FROM guild_members WHERE guild_id = $gid AND priv >= 2 FOR UPDATE");
	$str = $_SESSION['nickname'].' 请求加入您的家族，请速去处理吧！';
	$strSql = $_pm['mysql']->escape($str);
	$uidstr = '';

	if (is_array($guild)) {
		foreach($guild as $v){
			$notifyUid = intval($v['member_id']);
			if (!$_pm['mysql'] -> query("INSERT INTO information(uid,content) VALUES($notifyUid,'$strSql')")) {
				$_pm['mysql'] -> query('ROLLBACK');
				die('申请加入家族失败！');
			}
			$uidstr .= ','.$notifyUid;
		}
	}
	if (!$_pm['mysql'] -> query('COMMIT')) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('申请加入家族失败！');
	}
	if ($uidstr != '') {
		require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
		$s=new socketmsg();
		$rs=$s->sendMsg('an|'.$str,array(substr($uidstr,1)));
		$rs=$s->sendMsg('SYSN|information-->',array(substr($uidstr,1)));
	}
	guildClearUserCache($uid, false);
	//echo iconv('gbk','utf-8',$rs);
	die('3');//申请成功，请稍候再来查看！
}else if($op == 'exit'){
	$gid = (isset($_GET['gid']) && !is_array($_GET['gid'])) ? intval($_GET['gid']) : 0;
	$uid = intval($uid);
	if($gid == 0){
		die('');
	}
	if(!$_pm['mysql'] -> query('START TRANSACTION')) die('退出家族失败！');
	$guild_check = $_pm['mysql'] -> getOneRecord("SELECT priv FROM guild_members WHERE member_id = $uid AND guild_id = $gid FOR UPDATE");
	if(!is_array($guild_check)){
		$_pm['mysql'] -> query('ROLLBACK');
		die('2');//您尚未加入此家族，不用退出！
	}
	if ($guild_check['priv'] == 3){
		$_pm['mysql'] -> query('ROLLBACK');
		die('4');//您是会长，不能申请退出
	}
	if (!$_pm['mysql'] -> query("DELETE FROM guild_members WHERE guild_id = $gid AND member_id = $uid") || mysql_affected_rows($_pm['mysql'] -> getConn()) != 1) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('退出家族失败！');
	}
	if (!$_pm['mysql'] -> query("UPDATE guild SET number_of_member = IF(number_of_member > 0, number_of_member - 1, 0) WHERE id = $gid")) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('退出家族失败！');
	}

	//发请求给族长和长老（写入）
	$guild = $_pm['mysql'] -> getRecords("SELECT member_id FROM guild_members WHERE guild_id = $gid AND priv >= 2 FOR UPDATE");
	$str = $_SESSION['nickname'].' 退出了您的家族，请速去处理吧！';
	$strSql = $_pm['mysql']->escape($str);
	$uidstr = '';
	if (is_array($guild)) {
		foreach($guild as $v){
			$notifyUid = intval($v['member_id']);
			if (!$_pm['mysql'] -> query("INSERT INTO information(uid,content) VALUES($notifyUid,'$strSql')")) {
				$_pm['mysql'] -> query('ROLLBACK');
				die('退出家族失败！');
			}
			$uidstr .= ','.$notifyUid;
		}
	}
	if (!$_pm['mysql'] -> query('COMMIT')) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('退出家族失败！');
	}
	guild_update_mem();
	//退出后记录退出时间。写内内存
	$_pm['mem'] -> setns('last_exit_guild_time_'.$uid,time().'');
	guildClearUserCache($uid, false);

	if ($uidstr != '') {
		require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
		$s=new socketmsg();
		$rs=$s->sendMsg('an|'.$str,array(substr($uidstr,1)));
		$rs=$s->sendMsg('SYSN|information-->',array(substr($uidstr,1)));
	}
	die('3');//申请退出成功，请稍候再来查看！
}else if($op == 'dissolut'){
	$gid = (isset($_GET['gid']) && !is_array($_GET['gid'])) ? intval($_GET['gid']) : 0;
	$uid = intval($uid);
	if($gid == 0){
		die('');
	}
	if(!$_pm['mysql'] -> query('START TRANSACTION')) die('解散家族失败！');
	$guild_check = $_pm['mysql'] -> getOneRecord("SELECT priv FROM guild_members WHERE member_id = $uid AND guild_id = $gid FOR UPDATE");
	if(!is_array($guild_check) || $guild_check['priv'] != 3){
		$_pm['mysql'] -> query('ROLLBACK');
		die('2');//您没有权限操作！
	}
	$ac = (isset($_GET['ac']) && !is_array($_GET['ac'])) ? $_GET['ac'] : '';

	if($ac != 'do'){
		$_pm['mysql'] -> query('ROLLBACK');
		die('tips');//您确定解散此家族
	}
	$affectedUsers = array();
	$memberRows = $_pm['mysql']->getRecords("SELECT member_id FROM guild_members WHERE guild_id=$gid FOR UPDATE");
	if(is_array($memberRows)){
		foreach($memberRows as $memberRow) $affectedUsers[intval($memberRow['member_id'])] = true;
	}
	$applicantRows = $_pm['mysql']->getRecords("SELECT uid FROM player_ext WHERE guild_request=$gid FOR UPDATE");
	if(is_array($applicantRows)){
		foreach($applicantRows as $applicantRow) $affectedUsers[intval($applicantRow['uid'])] = true;
	}
	if (!$_pm['mysql'] -> query("DELETE FROM guild_members WHERE guild_id = $gid")) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('解散家族失败！');
	}
	if (!$_pm['mysql'] -> query("DELETE FROM guild_bag WHERE guild_id = $gid")) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('解散家族失败！');
	}
	if (kdjlMysqlTableHasColumn($_pm['mysql'], 'guild_automation', 'guild_id') &&
		!$_pm['mysql']->query("DELETE FROM guild_automation WHERE guild_id = $gid")) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('解散家族失败！');
	}
	if (!$_pm['mysql']-> query("DELETE FROM guild WHERE id = $gid") || mysql_affected_rows($_pm['mysql'] -> getConn()) != 1) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('解散家族失败！');
	}
	if (!$_pm['mysql'] -> query("UPDATE player_ext SET guild_request = 0 WHERE guild_request = $gid")) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('解散家族失败！');
	}
	if (!$_pm['mysql'] -> query("DELETE FROM guild_challenges WHERE challenger_id = $gid OR defenser_id = $gid")) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('解散家族失败！');
	}
	if (!$_pm['mysql'] -> query('COMMIT')) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('解散家族失败！');
	}
	$_pm['mem'] -> setns('last_exit_guild_time_'.$uid,time().'');
	foreach($affectedUsers as $affectedUid => $unused) guildClearUserCache($affectedUid, false);
	guild_update_mem();
	die('ok');
}else if($op == 'giveProps'){
	$gid = (isset($_GET['gid']) && !is_array($_GET['gid'])) ? intval($_GET['gid']) : 0;
	$pname = (isset($_GET['pname']) && !is_array($_GET['pname'])) ? $_GET['pname'] : '';
	$pnameSql = $_pm['mysql']->escape($pname);
	$psum = (isset($_GET['psum']) && !is_array($_GET['psum'])) ? intval($_GET['psum']) : 0;
	$uid = intval($uid);
	if($gid == 0 || $psum <= 0 || empty($pname)){
		die('');
	}
	if(!$_pm['mysql']->query('START TRANSACTION')) die('家族捐献失败！');
	$member = $_pm['mysql']->getOneRecord("SELECT contribution FROM guild_members
		WHERE member_id=$uid AND guild_id=$gid FOR UPDATE");
	if(!is_array($member)) guildTransactionDie('1');

	$guildConfig = $_pm['mysql']->getOneRecord("SELECT guild_settings.need_props
		FROM guild INNER JOIN guild_settings ON guild_settings.level=guild.level
		WHERE guild.id=$gid FOR UPDATE");
	if(!is_array($guildConfig) || !isset($guildConfig['need_props'])) guildTransactionDie('3');

	$requirements = array();
	foreach(explode(',', $guildConfig['need_props']) as $requirement)
	{
		$parts = explode('|', trim($requirement));
		if(count($parts) < 3) continue;
		$requiredPid = intval($parts[0]);
		$requiredSum = intval($parts[1]);
		$rewardContribution = intval($parts[2]);
		if($requiredPid > 0 && $requiredSum > 0 && $rewardContribution >= 0)
		{
			$requirements[$requiredPid] = array($requiredSum, $rewardContribution);
		}
	}
	if(count($requirements) < 1) guildTransactionDie('3');

	$nameRows = $_pm['mysql']->getRecords("SELECT id FROM props WHERE name='$pnameSql' ORDER BY id FOR UPDATE");
	if(!is_array($nameRows) || count($nameRows) < 1) guildTransactionDie('2');
	$pid = 0;
	foreach($nameRows as $nameRow)
	{
		$candidatePid = intval($nameRow['id']);
		if(isset($requirements[$candidatePid]))
		{
			$pid = $candidatePid;
			break;
		}
	}
	if($pid < 1) guildTransactionDie('3');
	$needSum = intval($requirements[$pid][0]);
	$giveContribution = intval($requirements[$pid][1]);

	$guildBagRows = $_pm['mysql']->getRecords("SELECT id,sums FROM guild_bag
		WHERE guild_id=$gid AND pid=$pid ORDER BY id FOR UPDATE");
	if(!is_array($guildBagRows)) $guildBagRows = array();
	$currentSum = 0;
	foreach($guildBagRows as $guildBagRow) $currentSum += max(0, intval($guildBagRow['sums']));
	if($currentSum >= $needSum) guildTransactionDie('4');
	$consumeSum = min($psum, $needSum - $currentSum);
	if($consumeSum < 1) guildTransactionDie('4');

	$bagRows = $_pm['mysql']->getRecords("SELECT id,sums FROM userbag
		WHERE uid=$uid AND pid=$pid AND zbing=0 AND sums>0
		AND (cantrade IS NULL OR cantrade<>3) ORDER BY sums DESC,id ASC FOR UPDATE");
	if(!is_array($bagRows)) $bagRows = array();
	$total = 0;
	foreach($bagRows as $bagRow) $total += max(0, intval($bagRow['sums']));
	if($total < $consumeSum) guildTransactionDie('5');

	$remaining = $consumeSum;
	foreach($bagRows as $bagRow)
	{
		if($remaining < 1) break;
		$bagId = intval($bagRow['id']);
		$take = min(max(0, intval($bagRow['sums'])), $remaining);
		if($bagId < 1 || $take < 1) continue;
		$used = $_pm['mysql']->query("UPDATE userbag SET sums=sums-$take
			WHERE id=$bagId AND uid=$uid AND pid=$pid AND sums>=$take AND zbing=0
			AND (cantrade IS NULL OR cantrade<>3)");
		if(!$used || mysql_affected_rows($_pm['mysql']->getConn()) != 1) guildTransactionDie('家族捐献失败！');
		if(!$_pm['mysql']->query("DELETE FROM userbag WHERE id=$bagId AND uid=$uid
			AND sums<=0 AND bsum<=0 AND psum<=0 AND pyb=0 AND zbing=0
			AND (cantrade IS NULL OR cantrade<>3)")) guildTransactionDie('家族捐献失败！');
		$remaining -= $take;
	}
	if($remaining > 0) guildTransactionDie('家族捐献失败！');

	$contributionGain = kdjlSafePositiveProduct($giveContribution, $consumeSum);
	if($contributionGain === false) guildTransactionDie('家族捐献失败！');
	$memberUpdated = $_pm['mysql']->query("UPDATE guild_members
		SET contribution=COALESCE(contribution,0)+$contributionGain
		WHERE member_id=$uid AND guild_id=$gid");
	if(!$memberUpdated || mysql_affected_rows($_pm['mysql']->getConn()) != 1) guildTransactionDie('家族捐献失败！');

	if(count($guildBagRows) > 0)
	{
		$guildBagId = intval($guildBagRows[0]['id']);
		$stored = $_pm['mysql']->query("UPDATE guild_bag SET sums=COALESCE(sums,0)+$consumeSum
			WHERE id=$guildBagId AND guild_id=$gid AND pid=$pid AND COALESCE(sums,0)<=255-$consumeSum");
		if(!$stored || mysql_affected_rows($_pm['mysql']->getConn()) != 1) guildTransactionDie('家族捐献失败！');
	}
	else
	{
		$stored = $_pm['mysql']->query("INSERT INTO guild_bag(pid,sums,guild_id) VALUES($pid,$consumeSum,$gid)");
		if(!$stored || mysql_affected_rows($_pm['mysql']->getConn()) != 1) guildTransactionDie('家族捐献失败！');
	}
	if(!$_pm['mysql']->query('COMMIT')) guildTransactionDie('家族捐献失败！');
	guildClearUserCache($uid, true);
	die('10');
}else if ($op == 'guild_welfare') {
	$gid = (isset($_GET['gid']) && !is_array($_GET['gid'])) ? intval($_GET['gid']) : 0;
	$uid = intval($uid);
	if ($gid == 0) {
		die('');
	}
	require_once('../sec/dblock_fun.php');
	$a = getLock($uid);
	if(!is_array($a)){
		die('服务器繁忙，请稍候再试！');
	}

	//判断时间 一天只能领取一次
	if(!$_pm['mysql'] -> query("INSERT INTO player_ext(uid,bbshow) VALUES($uid,5) ON DUPLICATE KEY UPDATE uid=uid")){
		$_pm['mysql']->query('ROLLBACK');
		realseLock();
		die('2');
	}
	$user = $_pm['mysql'] -> getOneRecord("SELECT get_welfare_time FROM player_ext WHERE uid = $uid FOR UPDATE");
	if(!is_array($user)){
		$_pm['mysql']->query('ROLLBACK');
		realseLock();
		die('2');
	}
	if ($user['get_welfare_time'] > 0) {
		$yes = date('Ymd',time()-24*3600);
		if ($user['get_welfare_time'] > $yes) {
			$_pm['mysql']->query('ROLLBACK');
			realseLock();
			die('3');//已经领过了，今天不能再领
		}
	}


	$check = $_pm['mysql'] -> getOneRecord("SELECT guild_id FROM guild_members WHERE guild_id = $gid AND member_id = $uid");
	if (!is_array($check)) {
		$_pm['mysql']->query('ROLLBACK');
		realseLock();
		die('1');//您不在此家族，不能领取这个家族的家族福利!
	}

	$guild = $_pm['mysql'] -> getOneRecord("SELECT welfare FROM guild_settings,guild WHERE guild_settings.level = guild.level AND guild.id = $gid");
	if (!is_array($guild) || $guild['welfare'] == '') {
		$_pm['mysql']->query('ROLLBACK');
		realseLock();
		die('2');//数据读取有误!
	}
	$propslist = explode(',', $guild['welfare']);
	$retstr = '';
	if (is_array($propslist))
	{
		foreach ($propslist as $k => $v)
		{
			$inarr = explode(':', $v);		//	0=> ID, 2=> rand number, 1=> sum props
			if(count($inarr) < 3){
				$_pm['mysql']->query('ROLLBACK');
				realseLock();
				die('家族福利配置错误！');
			}
			$pid = intval($inarr[0]);
			$rate = intval($inarr[1]);
			$num = intval($inarr[2]);
			if($pid <= 0 || $rate <= 0 || $num <= 0){
				$_pm['mysql']->query('ROLLBACK');
				realseLock();
				die('家族福利配置错误！');
			}
			$task = new task();
			if (rand(1, $rate) == 1){
				$giveResult = $task->saveGetPropsMore($pid,$num);//1424:100:1,747:10:2,95:1:1
				if($giveResult !== true){
					$_pm['mysql']->query('ROLLBACK');
					realseLock();
					die($giveResult === '200' ? '背包空间不足，请整理后再领取！' : '家族福利发放失败，请稍候再试！');
				}
				$prs = $_pm['mysql']->getOneRecord("SELECT name FROM props WHERE id=$pid");
				$pname = guildHtml(is_array($prs) ? $prs['name'] : $pid);
				if(empty($retstr))
				{
					$retstr = '获得道具 '.$pname.'&nbsp;'.$num.' 个';
				}
				else
				{
					$retstr .= ",".$pname.'&nbsp;'.$num.' 个';
				}
			}
		} // end foreach
		// del props current bag.
		$time = date('Ymd');
		if(!$_pm['mysql'] -> query("UPDATE player_ext SET get_welfare_time = '$time' WHERE uid = $uid AND (get_welfare_time IS NULL OR get_welfare_time <> '$time')") || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
			$_pm['mysql']->query('ROLLBACK');
			realseLock();
			die('家族福利状态保存失败，请稍候再试！');
		}
		if(!$_pm['mysql']->query('COMMIT')){
			$_pm['mysql']->query('ROLLBACK');
			realseLock();
			die('家族福利状态保存失败，请稍候再试！');
		}
		if(defined('MEM_USERBAG_KEY')) $_pm['mem']->del(MEM_USERBAG_KEY);
		realseLock();
		echo $retstr;
		exit;
	}
}else if ($op == 'next_level'){
	$gid = (isset($_GET['gid']) && !is_array($_GET['gid'])) ? intval($_GET['gid']) : 0;
	$uid = intval($uid);
	if ($gid == 0) {
		die('');
	}

	if(!$_pm['mysql'] -> query('START TRANSACTION')) die('4');
	$check = $_pm['mysql'] -> getOneRecord("SELECT priv FROM guild_members WHERE guild_id = $gid AND member_id = $uid FOR UPDATE");
	if (!is_array($check) || $check['priv'] != 3) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('1');//您没有权限升级家族
	}

	$guild = $_pm['mysql'] -> getOneRecord("SELECT guild.level,need_honor,need_props,need_member_number,number_of_member,honor FROM guild,guild_settings WHERE guild.level = guild_settings.level AND guild.id = $gid FOR UPDATE");//print_r($guild);exit;
	if (!is_array($guild)) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('4');
	}
	$currentGuildLevel = intval($guild['level']);
	$nextLevelConfig = $_pm['mysql']->getOneRecord('SELECT id FROM guild_settings WHERE level='.($currentGuildLevel+1).' ORDER BY id LIMIT 1 FOR UPDATE');
	if(!is_array($nextLevelConfig)) {
		$_pm['mysql']->query('ROLLBACK');
		die('6');//已经达到最高可配置等级
	}
	if ($guild['number_of_member'] < $guild['need_member_number']) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('2');//家族成员不够!
	}
	if ($guild['need_honor'] > $guild['honor']) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('3');//家族荣誉不够!
	}

	if (!empty($guild['need_props'])) {
		$arr = explode(',',$guild['need_props']);
		foreach ($arr as $v){
			$nv = explode('|',$v);
			$pid = isset($nv[0]) ? intval($nv[0]) : 0;
			$needSum = isset($nv[1]) ? intval($nv[1]) : 0;
			if ($pid <= 0 || $needSum < 0) {
				$_pm['mysql'] -> query('ROLLBACK');
				die('4');//物品不够!
			}
			if ($needSum == 0) {
				continue;
			}
			$pchecks = $_pm['mysql'] -> getRecords("SELECT id,sums FROM guild_bag WHERE pid = $pid AND guild_id = $gid AND sums > 0 ORDER BY sums DESC,id ASC FOR UPDATE");
			$total = 0;
			if (is_array($pchecks)) {
				foreach ($pchecks as $pcheck) $total += intval($pcheck['sums']);
			}
			if ($total < $needSum) {
				$_pm['mysql'] -> query('ROLLBACK');
				die('4');//物品不够!
			}
			$remaining = $needSum;
			foreach ($pchecks as $pcheck) {
				if ($remaining <= 0) break;
				$take = min(intval($pcheck['sums']), $remaining);
				$bagId = intval($pcheck['id']);
				if (!$_pm['mysql'] -> query("UPDATE guild_bag SET sums = sums - $take WHERE id = $bagId AND guild_id = $gid AND pid = $pid AND sums >= $take") || mysql_affected_rows($_pm['mysql'] -> getConn()) != 1) {
					$_pm['mysql'] -> query('ROLLBACK');
					die('4');//物品不够!
				}
				$remaining -= $take;
			}
		}
	}

	//升级
	$level = $currentGuildLevel;
	if (!$_pm['mysql'] -> query("UPDATE guild SET level = level+1 WHERE id = $gid AND level = $level") || mysql_affected_rows($_pm['mysql'] -> getConn()) != 1) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('4');
	}
	if (!$_pm['mysql'] -> query('COMMIT')) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('4');
	}
	die('5');
}else if ($op == 'permissions'){
	$gid = (isset($_GET['gid']) && !is_array($_GET['gid'])) ? intval($_GET['gid']) : 0;
	$num = (isset($_GET['num']) && !is_array($_GET['num'])) ? intval($_GET['num']) : 0;
	$targetUid = (isset($_GET['qxuid']) && !is_array($_GET['qxuid'])) ? intval($_GET['qxuid']) : 0;
	$selfUid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
	if ($gid == 0 || $num < 1 || $num > 3 || $targetUid == 0 || $selfUid == 0) {
		die('');
	}

	if(!$_pm['mysql'] -> query('START TRANSACTION')) die('1');
	$check = $_pm['mysql'] -> getOneRecord("SELECT priv FROM guild_members WHERE guild_id = $gid AND member_id = $selfUid FOR UPDATE");
	if (!is_array($check) || $check['priv'] < 2) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('1');//您没有权限操作
	}
	if ($targetUid == $selfUid) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('5');//不能操作自己
	}

	$flag = 0;
	$targetPriv = 0;
	$checkt = $_pm['mysql'] -> getOneRecord("SELECT priv FROM guild_members WHERE guild_id = $gid AND member_id = $targetUid FOR UPDATE");
	if (!is_array($checkt)) {
		$checkt = $_pm['mysql'] -> getOneRecord("SELECT guild_request FROM player_ext WHERE uid = $targetUid FOR UPDATE");
		//print_r($checkt);echo "SELECT guild_request FROM player_ext WHERE uid = $uid";exit;
		if (!is_array($checkt) || intval($checkt['guild_request']) != $gid) {
			$_pm['mysql'] -> query('ROLLBACK');
			die('2');//该用户不在您的家族
		}
		$flag =1;
	}else{
		$targetPriv = intval($checkt['priv']);
	}
	$guild = $_pm['mysql'] -> getOneRecord("SELECT guild.level,number_of_member,max_member_number FROM guild,guild_settings WHERE guild.level = guild_settings.level AND guild.id = $gid FOR UPDATE");
	if (!is_array($guild)) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('2');
	}
	$memberCount = intval($guild['number_of_member']);
	$maxMember = intval($guild['max_member_number']);
	if ($flag == 1 && $maxMember <= $memberCount) {
		if (!$_pm['mysql'] -> query("UPDATE player_ext SET guild_request = 0 WHERE uid = $targetUid AND guild_request = $gid") ||
			mysql_affected_rows($_pm['mysql'] -> getConn()) != 1 ||
			!$_pm['mysql'] -> query('COMMIT'))
		{
			$_pm['mysql'] -> query('ROLLBACK');
			die('1');
		}
		guildClearUserCache($targetUid, false);
		die('3');//人数已满，不能再加入
	}//echo __LINE__."<br>";
	if ($num >= 2 && $check['priv'] != 3) {//echo __LINE__."<br>";
		$_pm['mysql'] -> query('ROLLBACK');
		die('1');
	}
	if($flag != 1 && $targetPriv >= intval($check['priv'])){
		$_pm['mysql'] -> query('ROLLBACK');
		die('1');
	}
	//exit;
	if ($num == 2) {
		$elderRows = $_pm['mysql'] -> getRecords("SELECT member_id FROM guild_members WHERE priv =2 AND guild_id = $gid FOR UPDATE");
		$elderCount = is_array($elderRows) ? count($elderRows) : 0;
		if ($targetPriv != 2 && $elderCount >= 2) {
			$_pm['mysql'] -> query('ROLLBACK');
			die('4');//最多只有两个会长
		}
	}
	if ($num == 3) {
		if (!$_pm['mysql'] -> query("UPDATE guild_members SET priv = 1 WHERE member_id = $selfUid AND guild_id = $gid") || mysql_affected_rows($_pm['mysql'] -> getConn()) != 1) {
			$_pm['mysql'] -> query('ROLLBACK');
			die('1');
		}
		if (!$_pm['mysql'] -> query("UPDATE guild SET president_id = $targetUid WHERE id = $gid")) {
			$_pm['mysql'] -> query('ROLLBACK');
			die('1');
		}
	}
	$time = time();
	if ($flag == 1) {//echo "UPDATE guild SET number_of_member = number_of_member + 1 WHERE id = $gid";exit;
		if (!$_pm['mysql'] -> query("INSERT INTO guild_members(member_id,guild_id,join_time,priv) VALUES($targetUid,$gid,$time,$num)") || mysql_affected_rows($_pm['mysql'] -> getConn()) != 1) {
			$_pm['mysql'] -> query('ROLLBACK');
			die('2');
		}
		if (!$_pm['mysql'] -> query("UPDATE guild SET number_of_member = number_of_member + 1 WHERE id = $gid AND number_of_member = $memberCount") || mysql_affected_rows($_pm['mysql'] -> getConn()) != 1) {
			$_pm['mysql'] -> query('ROLLBACK');
			die('3');
		}
		if (!$_pm['mysql'] -> query("UPDATE player_ext SET guild_request = 0 WHERE uid = $targetUid AND guild_request = $gid") ||
			mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
			$_pm['mysql'] -> query('ROLLBACK');
			die('2');
		}
	}else {
		if (!$_pm['mysql'] -> query("UPDATE guild_members SET priv = $num WHERE member_id = $targetUid AND guild_id = $gid")) {
			$_pm['mysql'] -> query('ROLLBACK');
			die('1');
		}
	}
	if (!$_pm['mysql'] -> query('COMMIT')) {
		$_pm['mysql'] -> query('ROLLBACK');
		die('1');
	}
	if($flag == 1) guildClearUserCache($targetUid, false);
	guild_update_mem();
	die('10');
}else if($op == 'battle'){
	$gid = (isset($_GET['id']) && !is_array($_GET['id'])) ? intval($_GET['id']) : 0;
	if($gid == 0){
		die('数据错误');
	}

	//判断时间是否可下战书（战斗中不能下战书）
	$week = date("N", time());
	$hourM= date("Hi", time());
	$battletimearr = kdjlSafeMemValue($_pm['mem']->get(MEM_TIME_KEY), array());
	if(!is_array($battletimearr)) $battletimearr = array();
	foreach($battletimearr as $bv){
		if(!is_array($bv) || !isset($bv['titles']) || $bv['titles'] != "guild_battle")
		{
			continue;
		}
		if(isWeeklyDayTimeActive(isset($bv['days']) ? $bv['days'] : '', isset($bv['starttime']) ? $bv['starttime'] : '', isset($bv['endtime']) ? $bv['endtime'] : '', $week, $hourM, false)){//战场已经开始
			die('1');//已经开始不能再下战书
		}
	}

	if(!$_pm['mysql']->query('START TRANSACTION')) die('保存家族战书失败！');
	$check = $_pm['mysql'] -> getOneRecord("SELECT guild_id,priv FROM guild_members WHERE member_id = $uid FOR UPDATE");
	if(!is_array($check) || $check['priv'] < 2){
		guildTransactionDie('2');//您没有权限操作
	}
	$selfGuildId = intval($check['guild_id']);
	if($selfGuildId < 1 || $selfGuildId == $gid){
		guildTransactionDie('您不能给自己的家族下战书！');
	}

	$guildRows = $_pm['mysql']->getRecords("SELECT id,level FROM guild WHERE id IN ($selfGuildId,$gid) ORDER BY id FOR UPDATE");
	if(!is_array($guildRows) || count($guildRows) != 2) guildTransactionDie('数据错误2');
	$guildLevels = array();
	foreach($guildRows as $guildRow)
	{
		$guildLevels[intval($guildRow['id'])] = intval($guildRow['level']);
	}
	if(!isset($guildLevels[$selfGuildId], $guildLevels[$gid])) guildTransactionDie('数据错误2');

	$challengeRows = $_pm['mysql']->getRecords("SELECT id,challenger_id,defenser_id,flags
		FROM guild_challenges
		WHERE challenger_id IN ($selfGuildId,$gid) OR defenser_id IN ($selfGuildId,$gid)
		ORDER BY id FOR UPDATE");
	if(!is_array($challengeRows)) guildTransactionDie('保存家族战书失败！');
	$outgoingCount = 0;
	$incomingCount = 0;
	$duplicate = false;
	foreach($challengeRows as $challengeRow)
	{
		$challengerId = intval($challengeRow['challenger_id']);
		$defenserId = intval($challengeRow['defenser_id']);
		if(intval($challengeRow['flags']) == 1)
		{
			guildTransactionDie('您的家族或者对方家族已经接受战书，不能再下战书了！');
		}
		if($challengerId == $selfGuildId) $outgoingCount++;
		if($defenserId == $gid) $incomingCount++;
		if($challengerId == $selfGuildId && $defenserId == $gid) $duplicate = true;
	}
	if($outgoingCount >= 3) guildTransactionDie('3');//您的家族当前已经发出3份战书，不能再发了！
	if($incomingCount >= 3) guildTransactionDie('4');//该家族已经收到三份战书，不能再下了，试试别的吧
	if($duplicate) guildTransactionDie('6');//您的家族已经对此家族下了战书
	$levelDiff = $guildLevels[$selfGuildId] - $guildLevels[$gid];
	if(abs($levelDiff) > $guildBattleMaxLevelGap) guildTransactionDie('5');//家族等级最多相差9级

	$targetAutomation = kdjlGuildAutomationInfo($_pm['mysql'], $gid);
	$challengeFlag = !empty($targetAutomation['auto_accept_challenge']) ? 1 : 0;
	if($challengeFlag === 1 && !$_pm['mysql']->query('DELETE FROM guild_challenges WHERE flags=0 AND (challenger_id IN ('.
		$selfGuildId.','.$gid.') OR defenser_id IN ('.$selfGuildId.','.$gid.'))')) guildTransactionDie('保存家族战书失败！');
	$time = time();
	if(!$_pm['mysql'] -> query("INSERT INTO guild_challenges (challenger_id,defenser_id,create_time,flags) VALUES($selfGuildId,$gid,$time,$challengeFlag)") ||
		mysql_affected_rows($_pm['mysql']->getConn()) != 1 ||
		!$_pm['mysql']->query('COMMIT')) guildTransactionDie('保存家族战书失败！');
	die($challengeFlag === 1 ? '11' : '10');
}else if($op == 'accept'){
	$gid = (isset($_GET['id']) && !is_array($_GET['id'])) ? intval($_GET['id']) : 0;
	if($gid == 0){
		die('数据错误');
	}

	//判断时间是否可下战书（战斗中不能下战书）
	$week = date("N", time());
	$hourM= date("Hi", time());
	$battletimearr = kdjlSafeMemValue($_pm['mem']->get(MEM_TIME_KEY), array());
	if(!is_array($battletimearr)) $battletimearr = array();
	foreach($battletimearr as $bv){
		if(!is_array($bv) || !isset($bv['titles']) || $bv['titles'] != "guild_battle")
		{
			continue;
		}
		if(isWeeklyDayTimeActive(isset($bv['days']) ? $bv['days'] : '', isset($bv['starttime']) ? $bv['starttime'] : '', isset($bv['endtime']) ? $bv['endtime'] : '', $week, $hourM, false)){//战场已经开始
			die('1');//已经开始不能再接受
		}
	}

	if(!$_pm['mysql']->query('START TRANSACTION')) die('保存家族战书失败！');
	$check = $_pm['mysql'] -> getOneRecord("SELECT guild_id,priv FROM guild_members WHERE member_id = $uid FOR UPDATE");
	if(!is_array($check) || $check['priv'] < 2){
		guildTransactionDie('2');//您没有权限操作
	}
	$selfGuildId = intval($check['guild_id']);
	if($selfGuildId < 1 || $selfGuildId == $gid){
		guildTransactionDie('操作有误！');
	}

	$guildRows = $_pm['mysql']->getRecords("SELECT id,level FROM guild WHERE id IN ($selfGuildId,$gid) ORDER BY id FOR UPDATE");
	if(!is_array($guildRows) || count($guildRows) != 2) guildTransactionDie('数据错误2');
	$guildLevels = array();
	foreach($guildRows as $guildRow)
	{
		$guildLevels[intval($guildRow['id'])] = intval($guildRow['level']);
	}
	if(!isset($guildLevels[$selfGuildId], $guildLevels[$gid])) guildTransactionDie('数据错误2');

	$challengeRows = $_pm['mysql']->getRecords("SELECT id,challenger_id,defenser_id,flags
		FROM guild_challenges
		WHERE challenger_id IN ($selfGuildId,$gid) OR defenser_id IN ($selfGuildId,$gid)
		ORDER BY id FOR UPDATE");
	if(!is_array($challengeRows)) guildTransactionDie('保存家族战书失败！');
	$targetChallengeIds = array();
	foreach($challengeRows as $challengeRow)
	{
		$challengerId = intval($challengeRow['challenger_id']);
		$defenserId = intval($challengeRow['defenser_id']);
		$flags = intval($challengeRow['flags']);
		if($flags == 1) guildTransactionDie('3');//您的家族或者对方家族已经接受战书，不能再接受了！
		if($flags == 0 && $challengerId == $gid && $defenserId == $selfGuildId)
		{
			$targetChallengeIds[] = intval($challengeRow['id']);
		}
	}
	if(count($targetChallengeIds) != 1) guildTransactionDie('家族战书不存在！');
	$levelDiff = $guildLevels[$selfGuildId] - $guildLevels[$gid];
	if(abs($levelDiff) > $guildBattleMaxLevelGap) guildTransactionDie('5');//家族等级最多相差9级

	$challengeId = $targetChallengeIds[0];
	if(!$_pm['mysql'] -> query("UPDATE guild_challenges SET flags = 1 WHERE id = $challengeId AND challenger_id = $gid AND defenser_id = $selfGuildId AND flags = 0") ||
		mysql_affected_rows($_pm['mysql'] -> getConn()) != 1) guildTransactionDie('家族战书不存在！');
	if(!$_pm['mysql'] -> query("DELETE FROM guild_challenges WHERE flags = 0 AND (challenger_id IN ($selfGuildId,$gid) OR defenser_id IN ($selfGuildId,$gid))") ||
		!$_pm['mysql']->query('COMMIT')) guildTransactionDie('保存家族战书失败！');
	die('10');
}else if($op == 'select_guild'){
	$ar = $_pm['mysql'] -> getOneRecord("SELECT guild_id FROM guild_members WHERE member_id = $uid");
	if(!is_array($ar) || !isset($ar['guild_id'])) die('0');
	die(strval(intval($ar['guild_id'])));
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






function guild_update_mem(){
	global $_pm;
	$guild = $_pm['mysql'] -> getRecords("SELECT member_id,guild_id FROM guild_members");
	$arr = array();
	if (!is_array($guild)) {
		$_pm['mem'] -> setns('MEM_GUILD_LIST',$arr);
		memArr2Str($arr,'MEM_GUILD_LIST');
		return false;
	}
	foreach($guild as $v){
		$arr[$v['guild_id']][] = $v['member_id'];
	}
	$_pm['mem'] -> setns('MEM_GUILD_LIST',$arr);
	memArr2Str($arr,'MEM_GUILD_LIST');
}
$_pm['mem']->memClose();
?>
