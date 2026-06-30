<?php
header('Content-Type:text/html;charset=gbk');
require_once('../config/config.game.php');
secStart($_pm['mem']);

$op = isset($_GET['op']) ? $_GET['op'] : '';
//guild_update_mem();exit;
if($op == 'show'){
	$id = intval($_GET['id']);

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
			$guildstr .= '<tr>
				  <td width="20%" height="24" style="cursor:pointer" onclick="show_guild_info('.$v['gid'].')" align="center" onmouseover="this.style.color=\'#ff0000\'" onmouseout="this.style.color=\'#600\'">'.$v['gname'].'</td>
				  <td width="20%" height="24" align="center">'.$v['nickname'].'</td>
				  <td width="20%" height="24" align="center">'.$v['honor'].'</td>
				  <td width="20%" height="24" align="center">'.$v['level'].'</td>
				  <td width="20%" height="24" align="center">'.$v['number_of_member'].'</td>
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
			$reg = '/'.$v.'/i';
			if(preg_match($reg,$str)){
				return false;
			}
		}
		return true;
	}
	$guild_name = $_GET['name'];
	$guild_info = $_GET['info'];
	$check_time = $_pm['mem'] -> get('last_exit_guild_time_'.$_SESSION['id']);
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
	if($guild_name == '' || $guild_info == '' || strlen($guild_name) < 4  || strlen($guild_name) > 16 || strlen($guild_info) > 400){
		die('1');//格式不正确
	}
	//判断是否符合创建条件

	$user = $_pm['mysql'] -> getOneRecord("SELECT guild_request,vip FROM player_ext,player WHERE player.id = player_ext.uid AND uid = {$_SESSION['id']}");
	
	$guildcheck = $_pm['mysql'] -> getOneRecord("SELECT member_id FROM guild_members WHERE member_id = {$_SESSION['id']}");
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
	$u_bags=getUserBagByIds($_SESSION['id'], $arraycode, $_pm['mysql']); 
	
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
	$bagCheck = $_pm['mysql'] -> getOneRecord("SELECT id FROM userbag WHERE uid = {$_SESSION['id']} AND pid = 2494 AND sums > 0");
	if(!is_array($bagCheck)) die('5');
	//判断通过，创建家族
	$time = time();
	$_pm['mysql'] -> query("INSERT INTO guild(name,info,creator_id,president_id,create_time) VALUES('$guild_name','$guild_info',{$_SESSION['id']},{$_SESSION['id']},$time)");
	$lastid = $_pm['mysql'] ->last_id();
	if(mysql_affected_rows($_pm['mysql'] -> getConn()) == 1){
		$_pm['mysql'] -> query("UPDATE player SET vip = vip - 10 WHERE id = {$_SESSION['id']} AND vip >= 10");
		
		if(mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){
			$_pm['mysql'] -> query("DELETE FROM guild WHERE creator_id = {$_SESSION['id']}");
			die('8');//没有足够积分
		}
		$_pm['mysql'] -> query("UPDATE player_ext SET guild_request = 0 WHERE uid = {$_SESSION['id']}");
		//写入内存供聊天
		
		
		$_pm['mysql'] -> query("INSERT INTO guild_members(member_id,guild_id,join_time,priv) VALUES({$_SESSION['id']},$lastid,$time,3)");
		$_pm['mysql'] -> query("UPDATE userbag SET sums = sums - 1 WHERE id = {$bagCheck['id']} AND sums > 0");
		guild_update_mem();
		die('6');//创建成功！
	}else die('7');//创建失败!
}else if($op == 'show_guild_info'){
	$gid = intval($_GET['gid']);
	if($gid == 0){//0表示查询自己所在的队伍的信息
		//die('1');//参数有误！
		$ar = $_pm['mysql'] -> getOneRecord("SELECT guild_id FROM guild_members WHERE member_id = {$_SESSION['id']}");
		if(!is_array($ar)){
			die('<br />&nbsp;&nbsp;<span style="font-size:12px">您还没有加入任何家族！</font>');
		}
		$gid = $ar['guild_id'];
	}
	$arr = $_pm['mysql'] -> getOneRecord("SELECT guild.name as name,info,player.nickname as cname,president_id,honor,level,create_time,guild.number_of_member FROM guild,player WHERE player.id = guild.president_id AND guild.id = $gid");
	$current = $_pm['mysql'] -> getOneRecord("SELECT max_member_number FROM guild_settings WHERE level = {$arr['level']}");
	if(!is_array($arr)){
		die('2');//没有这个家族
	}
	$guild_bag = $_pm['mysql'] -> getRecords("SELECT pid,sums FROM guild_bag WHERE guild_id = $gid");
	$memprops = unserialize($_pm['mem']->get('db_propsid'));
	if(!is_array($guild_bag)){
		$itemstr = '暂时没有宝藏';
	}else{
		foreach($guild_bag as $v){
			$itemstr .= ','.$memprops[$v['pid']]['name'].'×'.$v['sums'];//echo $itemstr.'<br />';
		}
		$itemstr = substr($itemstr,1);
	}
//exit;
	
	$check = $_pm['mysql'] -> getOneRecord("SELECT guild_id,priv FROM guild_members WHERE guild_id = $gid AND member_id = {$_SESSION['id']}");
	if (is_array($check)) {
		/*$str = '<tr>
              <td height="25" colspan="3" style="border-bottom-style:solid; border-bottom-width:1px; border-bottom-color:#000000">家族信息                </td>
              <td height="25" style="border-bottom-style:solid; border-bottom-width:1px; border-bottom-color:#000000;" onclick="giveProps()">家族捐献</td>
            </tr><tr>';*/
		
		  if($check['priv'] == '3'){
				$str = '<div class="bb01"><img src="../new_images/ui/icon15.jpg" width="76" height="25" /></div>
						<div id="jhjs" align="center" style="position:absolute; width:72px; left:550px; cursor:pointer; padding-top:5px; background-image:url(..//../new_images/ui/qzlihun.jpg); color:#600; height: 14px; top: 33px;" onclick="giveProps()"> 家族捐献</div>
						<div id="jhjs" align="center" style="position:absolute; width:72px; left:626px; cursor:pointer; padding-top:5px; background-image:url(..//../new_images/ui/qzlihun.jpg); color:#600; height: 14px; top: 33px;" onclick="dissolut()"> 解散家族</div>
						<div id="jhjs" align="center" style="position:absolute; width:72px; left:706px; cursor:pointer; padding-top:5px; background-image:url(..//../new_images/ui/qzlihun.jpg); color:#600; height: 14px; top: 33px;" onclick="$(\'con_tab_2\').style.display=\'none\';$(\'con_tab_1\').style.display=\'block\'"> 返回</div>
			  </div><div class="box03">';
		  }else{
		  	$str = '<div class="bb01"><img src="../new_images/ui/icon15.jpg" width="76" height="25" /></div>
						<div id="jhjs" align="center" style="position:absolute; width:72px; left:550px; cursor:pointer; padding-top:5px; background-image:url(..//../new_images/ui/qzlihun.jpg); color:#600; height: 14px; top: 33px;" onclick="giveProps()"> 家族捐献</div>
						<div id="jhjs" align="center" style="position:absolute; width:72px; left:626px; cursor:pointer; padding-top:5px; background-image:url(..//../new_images/ui/qzlihun.jpg); color:#600; height: 14px; top: 33px;" onclick="exit()">退出家族</div>
						<div id="jhjs" align="center" style="position:absolute; width:72px; left:706px; cursor:pointer; padding-top:5px; background-image:url(..//../new_images/ui/qzlihun.jpg); color:#600; height: 14px; top: 33px;" onclick="$(\'con_tab_2\').style.display=\'none\';$(\'con_tab_1\').style.display=\'block\'"> 返回</div>
			  </div><div class="box03">';
		  }
			
		//读出升级到下一级要的荣誉点及道具
		$next_level = $arr['level'];
		$next_level_need = $_pm['mysql'] -> getOneRecord("SELECT * FROM guild_settings WHERE level = $next_level");
		
		
		if (!empty($next_level_need['need_props'])) {
			$next_p_arr = explode(',',$next_level_need['need_props']);
			foreach ($next_p_arr as $v){
				//$next_level_need_props .= ','.$memprops[$v]['name'];
				$nv = explode('|',$v);
				$have_props = $_pm['mysql'] -> getOneRecord("SELECT sums FROM guild_bag WHERE pid = $nv[0] AND guild_id = $gid");
				if(is_array($have_props)) $havesums = $have_props['sums'];
				else $havesums = 0;//print_r($nv);
				$next_level_need_props .= ','.$memprops[$nv[0]]['name'].':'.$nv[1].'/'.$havesums;
			}
			$next_level_need_props = substr($next_level_need_props,1);
		}else $next_level_need_props = '';
		
		$next_need_str = '升级到 '.($next_level+1).' 级需要的物品'.$next_level_need_props.',需要荣誉:'.$next_level_need['need_honor'].',需要成员数:'.$next_level_need['need_member_number'];
		  
	}else{
		/*$str = '<tr>
              <td height="25" colspan="3" style="border-bottom-style:solid; border-bottom-width:1px; border-bottom-color:#000000">家族信息                </td>
              <td height="25" style="border-bottom-style:solid; border-bottom-width:1px; border-bottom-color:#000000"></td>
            </tr><tr>';*/
		$str = '		
		<div class="box01">
  			<div class="box02"><div class="bb01"><img src="../new_images/ui/icon15.jpg" width="76" height="25" /></div>
                    <div id="jhjs" align="center" style="position:absolute; width:72px; left:626px; cursor:pointer; padding-top:5px; background-image:url(..//../new_images/ui/qzlihun.jpg); color:#600; height: 14px; top: 33px;" onclick="apply();"> <font color=red><b>申请加入</b></font></div>
                    <div id="jhjs" align="center" style="position:absolute; width:72px; left:706px; cursor:pointer; padding-top:5px; background-image:url(..//../new_images/ui/qzlihun.jpg); color:#600; height: 14px; top: 33px;" onclick="$(\'con_tab_2\').style.display=\'none\';$(\'con_tab_1\').style.display=\'block\'"> <font color=red><b>返回</b></font></div>
		  </div><div class="box03">';
	}
	
	$str .= '<table border="0" cellspacing="0" class="tit01">
                    <tr>
                      <td width="12%" height="24" align="left" style="padding-left:10px;">家族名称：</td>
                      <td width="18%" align="left" >'.$arr['name'].'</td>
                      <td width="12%" align="left" >家族荣誉度：</td>
                      <td width="23%" align="left" >'.$arr['honor'].'</td>
                      <td width="11%" align="left" >族长：</td>
                      <td width="24%" align="left" >'.$arr['cname'].'</td>
                    </tr>
					<tr>
                      <td height="24" align="left" style="padding-left:10px;">家族宝藏：</td>
                      <td align="left" >'.$itemstr.'</td>
                      <td align="left" title="'.$next_need_str.'">家族等级：</td>
                      <td align="left" >'.$arr['level'].'</td>
                      <td align="left" >创建时间：</td>
                      <td align="left" >'.date('Y-m-d H:i',$arr['create_time']).'</td>
                    </tr>
					<tr>
                      <td height="24" align="left" style="padding-left:10px;">家族成员：</td>
                      <td align="left" >'.$arr['number_of_member'].'/'.$current['max_member_number'].'</td>
                      <td align="left" >家族福利</td>
					  <td align="left" style="cursor:pointer" onclick="guild_welfare()"><input type="image" name="Submit" value="领取" src="../new_images/ui/1.gif" /></td>
					  <td align="left"style="cursor:pointer" onclick="next_level()"><input type="image" name="Submit" value="领取" src="../new_images/ui/2.gif" /></td>
                      <td align="left" ></td>
                    </tr>
                    <tr>
                      <td height="24" align="left" style="padding-left:10px;">家族介绍：</td>
                      <td colspan="5" align="left" >'.$arr['info'].'</td>
                    </tr>
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
				
	$guild_member = $_pm['mysql'] -> getRecords("SELECT player.id as pid,player.nickname as pnickname,userbb.level,userbb.czl,priv FROM player,userbb,guild_members WHERE player.id = guild_members.member_id AND player.mbid = userbb.id AND guild_members.guild_id = $gid ORDER BY priv DESC");
	$applyarr = $_pm['mysql'] -> getRecords("SELECT player.id as pid,player.nickname as pnickname,userbb.level,userbb.czl FROM player,userbb,player_ext WHERE player.id = player_ext.uid AND player.mbid = userbb.id AND player_ext.guild_request = $gid");
	
	if (is_array($applyarr)) {
		$guild_member = array_merge($guild_member,$applyarr);
	}
	//print_r($applyarr);
	
	$v = '';

	foreach($guild_member as $v){
		$qx = '';
		if ($v['priv'] == 1) {
			$qx = '成员';
		}else if($v['priv'] == 2) $qx = '长老';
		else if($v['priv'] == 3) $qx = '族长';
		$str .= '<tr>
				    <td height="24" align="center" style="padding-left:10px;cursor:pointer"onclick="$(\'permissions\').style.display=\'block\';$(\'qxname\').innerHTML=\''.$v['pnickname'].'\';qxuid='.$v['pid'].';setTimeout(\'guild_permissions_none()\',5000);" onmouseover="this.style.color=\'#ff0000\'" onmouseout="this.style.color=\'#600\'">'.$v['pnickname'].'&nbsp;</td>
				    <td align="center" >'.$v['level'].'</td>
				    <td align="center" >'.$v['czl'].'</td>
				    <td align="center" >'.$qx.'</td>
				    <td width="14%" align="center" onmouseover="this.style.color=\'#ff0000\'" onmouseout="this.style.color=\'#600\'"><span onclick="friendlist(\''.$v['pnickname'].'\')" style="cursor:pointer"><img src="../new_images/ui/add06.gif" border="0" /></span></td>
				    <td width="15%" align="center" onmouseover="this.style.color=\'#ff0000\'" onmouseout="this.style.color=\'#600\'"><span onclick="fire('.$v['pid'].','.$gid.')" style="cursor:pointer"><img src="../new_images/ui/add07.gif" border="0" /></span></td>
			      </tr>';
		/*$str .= '<tr>
                  <td height="20" align="center" style="cursor:pointer" onclick="$(\'permissions\').style.display=\'block\';$(\'qxname\').innerHTML=\''.$v['pnickname'].'\';qxuid='.$v['pid'].'">'.$v['pnickname'].'</td>
                  <td height="20" align="center">'.$v['level'].'</td>
                  <td height="20" align="center">'.$v['czl'].'</td>
                  <td height="20" align="center">'.$qx.'</td>
                  <td height="20" align="center"><span onclick="friendlist(\''.$v['pnickname'].'\')" style="cursor:pointer">加为好友</span>&nbsp;&nbsp; <span onclick="fire('.$v['pid'].','.$gid.')" style="cursor:pointer">开除成员</span></td>
                </tr>';*/
	}
	
	$str .= '</table></div></div><div id="jzlevel" align="center" style="position:absolute;  left:241px; cursor:pointer; padding-top:5px; height: 14px; top: 33px;" onclick="guild_level_info()"> <img src=\'../new_images/ui/guild_next.gif\' />
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
}else if($op == 'fire'){
	$member_id = intval($_GET['member_id']);
	$guild_id = intval($_GET['guild_id']);
	
	if($member_id == 0 || $guild_id == 0){
		die('1');//操作有误！
	}
	if($member_id == $_SESSION['id']){
		die('2');//您不能操作自己！
	}
	//检查是否有权限作此项操作
	$checkme = $_pm['mysql'] -> getOneRecord("SELECT priv FROM guild_members WHERE member_id = {$_SESSION['id']} AND guild_id = $guild_id");
	if(!is_array($checkme)){
		die('3');//您不在此家族，不能作此操作！
	}
	if($checkme['priv'] == 1){
		die('4');//您是普通成员，不能开除其它成员
	}
	
	$check = $_pm['mysql'] -> getOneRecord("SELECT priv FROM guild_members WHERE member_id = $member_id AND guild_id = $guild_id");
	
	if(is_array($check) && $checkme['priv'] <= $check['priv']){
		die('6');//您没有权限开除比您职位高的成员！
	}
	if(!is_array($check)){
		$apply = $_pm['mysql'] -> getOneRecord("SELECT guild_request FROM player_ext WHERE uid = $member_id AND guild_request = $guild_id");
		if (!is_array($apply)) {
			die('5');//对方不在此家族，您不能操作
		}
		//退出后记录退出时间。写内内存
		$_pm['mem'] -> setns('last_exit_guild_time_'.$member_id,time().'');
		$_pm['mysql'] -> query("UPDATE player_ext SET guild_request = 0 WHERE uid = $member_id AND guild_request = $guild_id");
		$str = $_SESSION['nickname'].'拒绝了您的家族请求！';
	
		require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
	
		$_pm['mysql'] -> query("INSERT INTO information(uid,content) VALUES($member_id,'$str')");
		
		$s=new socketmsg();
		$rs=$s->sendMsg('an|'.iconv('gbk','utf-8',$str),array(substr($member_id,1)));
		$rs=$s->sendMsg(iconv('gbk','utf-8','SYSN|information-->'),array(substr($member_id,1)));
		die('7');
	}
	$str = $_SESSION['nickname'].'把您被请出了家族！';
	
	require_once(dirname(__FILE__).'/../socketChat/config.chat.php');

	$_pm['mysql'] -> query("INSERT INTO information(uid,content) VALUES($member_id,'$str')");
	
	$s=new socketmsg();
	$rs=$s->sendMsg('an|'.iconv('gbk','utf-8',$str),array(substr($member_id,1)));
	$rs=$s->sendMsg(iconv('gbk','utf-8','SYSN|information-->'),array(substr($member_id,1)));
	
	
	$_pm['mysql'] -> query("DELETE FROM guild_members WHERE member_id = $member_id AND guild_id = $guild_id");
	$_pm['mysql'] -> query("UPDATE guild SET number_of_member = number_of_member - 1 WHERE id = $guild_id");
	//$_pm['mysql'] -> query("UPDATE player_ext SET guild_request = 0 WHERE uid = $member_id AND guild_request = $guild_id");
	guild_update_mem();
	//退出后记录退出时间。写内内存
	$_pm['mem'] -> setns('last_exit_guild_time_'.$member_id,time().'');
	die('7');
}else if($op == 'apply'){
	$gid = intval($_GET['gid']);
	if($gid == 0){
		die('');
	}
	$check_time = $_pm['mem'] -> get('last_exit_guild_time_'.$_SESSION['id']);
	if ($check_time > 0) {
		$time = time();
		$ctime = ($time - $check_time) - 24 * 3600;
		if($ctime < 0){
			die('10');
		}
	}
	$ac = $_GET['ac'];
	if($ac != 'do'){
		$user = $_pm['mysql'] -> getOneRecord("SELECT guild_request FROM player_ext WHERE uid = {$_SESSION['id']}");
		if($user['guild_request'] > 0){
			die('1');//您已经申请加入别的家族，不能再申请了
		}
	}
	
	//echo "SELECT guild_id FROM guild_members WHERE member_id = {$_SESSION['id']}";exit;
	$guild_check = $_pm['mysql'] -> getOneRecord("SELECT guild_id FROM guild_members WHERE member_id = {$_SESSION['id']}");
	if(is_array($guild_check)){
		die('2');//您已经加入其它或者此家族，不能再申请！
	}
	
	//人数限制
	$settings = $_pm['mysql'] -> getOneRecord("SELECT max_member_number,number_of_member FROM guild_settings,guild WHERE guild.id = $gid AND guild.level=guild_settings.level");
	if(!is_array($settings) || $settings['number_of_member'] >= $settings['max_member_number']){
		die('4');//对方人数已满，不能再申请
	}
	
	
	$_pm['mysql'] -> query("UPDATE player_ext SET guild_request = $gid WHERE uid = {$_SESSION['id']}");
	//发请求给族长和长老（写入）
	$guild = $_pm['mysql'] -> getRecords("SELECT member_id FROM guild_members WHERE guild_id = $gid AND priv >= 2");
	$str = $_SESSION['nickname'].' 请求加入您的家族，请速去处理吧！';
	
	require_once(dirname(__FILE__).'/../socketChat/config.chat.php');

	foreach($guild as $v){
		$_pm['mysql'] -> query("INSERT INTO information(uid,content) VALUES({$v['member_id']},'$str')");
		$uidstr .= ','.$v['member_id'];
	}
	require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
	
	$s=new socketmsg();
	$rs=$s->sendMsg('an|'.iconv('gbk','utf-8',$str),array(substr($uidstr,1)));
	$rs=$s->sendMsg(iconv('gbk','utf-8','SYSN|information-->'),array(substr($uidstr,1)));
	//echo iconv('gbk','utf-8',$rs);
	die('3');//申请成功，请稍候再来查看！
}else if($op == 'exit'){
	$gid = intval($_GET['gid']);
	if($gid == 0){
		die('');
	}
	$guild_check = $_pm['mysql'] -> getOneRecord("SELECT priv FROM guild_members WHERE member_id = {$_SESSION['id']} AND guild_id = $gid");
	if(!is_array($guild_check)){
		die('2');//您尚未加入此家族，不用退出！
	}
	if ($guild_check['priv'] == 3){
		die('4');//您是会长，不能申请退出
	}
	$_pm['mysql'] -> query("DELETE FROM guild_members WHERE guild_id = $gid AND member_id = {$_SESSION['id']}");
	$_pm['mysql'] -> query("UPDATE guild SET number_of_member = number_of_member - 1 WHERE id = $gid");
	guild_update_mem();
	//退出后记录退出时间。写内内存
	$_pm['mem'] -> setns('last_exit_guild_time_'.$_SESSION['id'],time().'');

	
	//发请求给族长和长老（写入）
	$guild = $_pm['mysql'] -> getRecords("SELECT member_id FROM guild_members WHERE guild_id = $gid AND priv >= 2");
	$str = $_SESSION['nickname'].' 退出了您的家族，请速去处理吧！';
	foreach($guild as $v){
		$_pm['mysql'] -> query("INSERT INTO information(uid,content) VALUES({$v['member_id']},'$str')");
		$uidstr .= ','.$v['member_id'];
	}
	require_once(dirname(__FILE__).'/../socketChat/config.chat.php');

	$s=new socketmsg();
	$rs=$s->sendMsg('an|'.iconv('gbk','utf-8',$str),array(substr($uidstr,1)));
	$rs=$s->sendMsg(iconv('gbk','utf-8','SYSN|information-->'),array(substr($uidstr,1)));
	die('3');//申请退出成功，请稍候再来查看！
}else if($op == 'dissolut'){
	$gid = intval($_GET['gid']);
	if($gid == 0){
		die('');
	}
	$guild_check = $_pm['mysql'] -> getOneRecord("SELECT priv FROM guild_members WHERE member_id = {$_SESSION['id']} AND guild_id = $gid");
	if(!is_array($guild_check) || $guild_check['priv'] != 3){
		die('2');//您没有权限操作！
	}
	$ac = $_GET['ac'];
	
	if($ac != 'do'){
		die('tips');//您确定解散此家族
	}
	$_pm['mem'] -> setns('last_exit_guild_time_'.$_SESSION['id'],time().'');
	$_pm['mysql'] -> query("DELETE FROM guild_members WHERE guild_id = $gid");
	$_pm['mysql']-> query("DELETE FROM guild WHERE id = $gid");
	$_pm['mysql'] -> query("UPDATE player_ext SET guild_request = 0 WHERE guild_request = $gid");
	$_pm['mysql'] -> query("DELETE FROM guild_challenges WHERE challenger_id = $gid OR defenser_id = $gid");
	guild_update_mem();
	die('ok');
}else if($op == 'giveProps'){
	$gid = intval($_GET['gid']);
	$pname = isset($_GET['pname']) ? $_GET['pname'] : '';
	$pnameSql = $_pm['mysql']->escape($pname);
	$psum = intval($_GET['psum']);
	$uid = intval($_SESSION['id']);
	if($gid == 0 || $psum <= 0 || empty($pname)){
		die('');
	}
	
	$ugc = $_pm['mysql'] -> getOneRecord("SELECT contribution FROM guild_members WHERE member_id = $uid AND guild_id = $gid");
	if (!is_array($ugc)) {
		die('1');//不在此家族，不能捐献！
	}
	
	$pcheck = $_pm['mysql'] -> getOneRecord("SELECT id FROM props WHERE name = '$pnameSql'");
	if (!is_array($pcheck)) {
		die('2');//没有这个物品，请检查您的输入是否有误！
	}
	
	$guild_check = $_pm['mysql'] -> getOneRecord("SELECT guild_settings.need_props FROM guild_settings,guild WHERE guild.id = $gid AND guild_settings.level = guild.level");//echo "SELECT guild_settings.need_props FROM guild_settings,guild WHERE guild.id = $gid AND guild_settings.level = guild.level";exit;2496|5|10
	if (is_array($guild_check)) {
		$arr = explode(',',$guild_check['need_props']);
		$flag = 0;
		if (is_array($arr)) {
			foreach($arr as $v){
				$narr = explode('|',$v);//print_r($pcheck['id']);exit;
				if ($narr[0] == $pcheck['id']) {
					$need_sum = $narr[1];//echo __LINE__."<br>";
					$give_honor = $narr[2];
					$flag = 1;
					break;
				}
			}
		}//exit;
		if ($flag != 1) {
			die('3');//升到下一级不需要您捐献这个物品！
		}
		
		$guild_bag = $_pm['mysql'] -> getOneRecord("SELECT sums FROM guild_bag WHERE guild_id = $gid AND pid = {$pcheck['id']}");
		if (is_array($guild_bag)) {
			$sums = $guild_bag['sums'];
		}else $sums = 0;
		
		if ($sums >= $need_sum) {
			die('4');//您要捐赠的物品已经足够了！
		}
		
		$csums = $need_sum - $sums;
		if ($csums > $psum) {
			$csums = $psum;
		}
		$_pm['mysql'] -> query("UPDATE userbag SET sums = sums - $csums WHERE uid = $uid AND pid = {$pcheck['id']} AND sums >= $csums");
		if (mysql_affected_rows($_pm['mysql'] -> getConn()) != 1) {
			die('5');//您没有足够的数量
		}else{
			//加荣誉
			$honor = $give_honor * $csums;
			$_pm['mysql'] -> query("UPDATE guild_members SET contribution = contribution + $honor WHERE member_id = $uid AND guild_id = $gid");
			//加入到家族包裹
			if (is_array($guild_bag)) {
				$_pm['mysql'] -> query("UPDATE guild_bag SET sums = sums + $csums WHERE pid = {$pcheck['id']} AND guild_id = $gid");
			}else{
				$_pm['mysql'] -> query("INSERT INTO guild_bag(pid,sums,guild_id) VALUES({$pcheck['id']},$csums,$gid)");
			}
			die('10');
		}
	}else {
		die('3');
	}
}else if ($op == 'guild_welfare') {
	$gid = intval($_GET['gid']);
	if ($gid == 0) {
		die('');
	}
	
	//判断时间 一天只能领取一次
	$user = $_pm['mysql'] -> getOneRecord("SELECT get_welfare_time FROM player_ext WHERE uid = {$_SESSION['id']}");
	if ($user['get_welfare_time'] > 0) {
		$yes = date('Ymd',time()-24*3600);
		if ($user['get_welfare_time'] > $yes) {
			die('3');//已经领过了，今天不能再领
		}
	}
	
	
	$check = $_pm['mysql'] -> getOneRecord("SELECT guild_id FROM guild_members WHERE guild_id = $gid AND member_id = {$_SESSION['id']}");
	if (!is_array($check)) {
		die('1');//您不在此家族，不能领取这个家族的家族福利!
	}
	
	$guild = $_pm['mysql'] -> getOneRecord("SELECT welfare FROM guild_settings,guild WHERE guild_settings.level = guild.level AND guild.id = $gid");
	if (!is_array($guild) || $guild['welfare'] == '') {
		die('2');//数据读取有误!
	}
	$propslist = explode(',', $guild['welfare']);	
	$retstr = '';
	if (is_array($propslist))
	{
		$user = $_pm['user']->getUserById($_SESSION['id']);
		foreach ($propslist as $k => $v)
		{
			$inarr = explode(':', $v);		//	0=> ID, 2=> rand number, 1=> sum props
			
			
			if(is_array($inarr))
			{
				//foreach($inarr as $inarrs)
				//{
				$task = new task();
				if (rand(1, intval($inarr[1])) == 1){
					$task->saveGetPropsMore($inarr[0],$inarr[2]);//1424:100:1,747:10:2,95:1:1
					$prs = $_pm['mysql']->getOneRecord("SELECT name FROM props WHERE id={$inarr[0]}");
					if(empty($retstr))
					{
						$retstr = '获得道具 '.$prs['name'].'&nbsp;'.$inarr[2].' 个';
					}
					else
					{
						$retstr .= ",".$prs['name'].'&nbsp;'.$inarr[2].' 个';
					}
				}
			}
		} // end foreach
		// del props current bag.
		$time = date('Ymd');
		$_pm['mysql'] -> query("UPDATE player_ext SET get_welfare_time = '$time' WHERE uid = {$_SESSION['id']}");
		echo $retstr;
		exit;
	}
}else if ($op == 'next_level'){
	$gid = intval($_GET['gid']);
	if ($gid == 0) {
		die('');
	}
	$check = $_pm['mysql'] -> getOneRecord("SELECT priv FROM guild_members WHERE guild_id = $gid AND member_id = {$_SESSION['id']}");
	if (!is_array($check) || $check['priv'] != 3) {
		die('1');//您没有权限升级家族
	}
	
	$guild = $_pm['mysql'] -> getOneRecord("SELECT need_honor,need_props,need_member_number,number_of_member,honor FROM guild,guild_settings WHERE guild.level = guild_settings.level AND guild.id = $gid");//print_r($guild);exit;
	if ($guild['number_of_member'] < $guild['need_member_number']) {
		die('2');//家族成员不够!
	}
	if ($guild['need_honor'] > $guild['honor']) {
		die('3');//家族荣誉不够!
	}

	if (!empty($guild['need_props'])) {
		$arr = explode(',',$guild['need_props']);
		foreach ($arr as $v){
			$nv = explode('|',$v);
			$pcheck = $_pm['mysql'] -> getOneRecord("SELECT sums FROM guild_bag WHERE pid = {$nv['0']} AND guild_id = $gid");
			if (!is_array($pcheck) || $pcheck['sums'] < $nv[1]) {
				die('4');//物品不够!
			}
		}
	}
	
	//升级
	$_pm['mysql'] -> query("DELETE FROM guild_bag WHERE guild_id = $gid");
	$_pm['mysql'] -> query("UPDATE guild SET level = level+1 WHERE id = $gid");
	die('5');
}else if ($op == 'permissions'){
	$gid = intval($_GET['gid']);
	$num = intval($_GET['num']);
	$uid = intval($_GET['qxuid']);
	if ($gid == 0 || $num == 0 || $uid == 0) {
		die('');
	}
	$check = $_pm['mysql'] -> getOneRecord("SELECT priv FROM guild_members WHERE guild_id = $gid AND member_id = {$_SESSION['id']}");
	if (!is_array($check) || $check['priv'] < 2) {
		die('1');//您没有权限操作
	}
	if ($uid == $_SESSION['id']) {
		die('5');//不能操作自己
	}
	
	$checkt = $_pm['mysql'] -> getOneRecord("SELECT priv FROM guild_members WHERE guild_id = $gid AND member_id = $uid");
	if (!is_array($checkt)) {
		$checkt = $_pm['mysql'] -> getOneRecord("SELECT guild_request FROM player_ext WHERE uid = $uid");
		//print_r($checkt);echo "SELECT guild_request FROM player_ext WHERE uid = $uid";exit;
		if ($checkt['guild_request'] != $gid) {
			die('2');//该用户不在您的家族
		}
		$flag =1;
	}
	$guild = $_pm['mysql'] -> getOneRecord("SELECT level,number_of_member FROM guild WHERE id = $gid");
	$settings = $_pm['mysql'] -> getOneRecord("SELECT max_member_number FROM guild_settings WHERE level = {$guild['level']}");
	if ($settings['max_member_number'] <= $guild['number_of_member'] && $flag == 1) {
		$_pm['mysql'] -> query("UPDATE player_ext SET guild_request = 0 WHERE uid = $uid");
		die('3');//人数已满，不能再加入
	}//echo __LINE__."<br>";
	if ($num >= 2 && $check['priv'] != 3) {//echo __LINE__."<br>";
		die('1');
	}
	if($checkt['priv'] >= $check['priv']){
		die('1');
	}
	//exit;
	if ($num == 2) {
		$c = $_pm['mysql'] -> getOneRecord("SELECT count(member_id) as sums FROM guild_members WHERE priv =2 AND guild_id = $gid");
		if ($c['sums'] >= 2) {
			die('4');//最多只有两个会长
		}
	}
	if ($num == 3) {
		$_pm['mysql'] -> query("UPDATE guild_members SET priv = 1 WHERE member_id = {$_SESSION['id']} AND guild_id = $gid");
		$_pm['mysql'] -> query("UPDATE guild SET president_id = $uid WHERE id = $gid");
	}
	$time = time();
	if ($flag == 1) {//echo "UPDATE guild SET number_of_member = number_of_member + 1 WHERE id = $gid";exit;
		$_pm['mysql'] -> query("INSERT INTO guild_members(member_id,guild_id,join_time,priv) VALUES($uid,$gid,$time,$num)");
		$_pm['mysql'] -> query("UPDATE guild SET number_of_member = number_of_member + 1 WHERE id = $gid");
		$_pm['mysql'] -> query("UPDATE player_ext SET guild_request = 0 WHERE uid = $uid");
	}else {
		$_pm['mysql'] -> query("UPDATE guild_members SET priv = $num WHERE member_id = $uid AND guild_id = $gid");
	}
	guild_update_mem();
	die('10');
}else if($op == 'battle'){
	$gid = intval($_GET['id']);
	if($gid == 0){
		die('数据错误');
	}
	
	//判断时间是否可下战书（战斗中不能下战书）
	$today = date("Y-m-d",time());
														 
	// 战场开放时间开关。
	$week = date("N", time());
	$hourM= date("Hi", time());
	
	$battletimearr = unserialize($_pm['mem']->get(MEM_TIME_KEY));
	
	foreach($battletimearr as $bv){
		if($bv['titles'] != "guild_battle")
		{
			continue;
		}
		if($week == $bv['days'] && ($hourM >= $bv['starttime'] && $hourM < $bv['endtime'])){//战场已经开始
			die('1');//已经开始不能再下战书
		}
	}
	
	//判断是否有权限下战书
	$check = $_pm['mysql'] -> getOneRecord("SELECT guild_id,priv FROM guild_members WHERE member_id = {$_SESSION['id']}");
	if(!is_array($check) || $check['priv'] < 2){
		die('2');//您没有权限操作
	}
	if($check['guild_id'] == $gid){
		die('您不能给自己的家族下战书！');
	}
	$arc = $_pm['mysql'] -> getOneRecord("SELECT id FROM guild_challenges WHERE (challenger_id = $gid or defenser_id = $gid or challenger_id = {$check['guild_id']} or defenser_id = {$check['guild_id']}) AND flags = 1");
	if(is_array($arc)){
		die('您的家族或者对方家族已经接受战书，不能再下战书了！');
	}
	//控制数量：判断是否已经下了三个或者以上的战书
	$sumcheck = $_pm['mysql'] -> getOneRecord("SELECT count(id) as sums FROM guild_challenges WHERE challenger_id = {$check['guild_id']}");
	if($sumcheck['sums'] >= 3){
		die('3');//您的家族当前已经发出3份战书，不能再发了！
	}
	
	//控制被下方是否已经有三个
	$sumcheck1 = $_pm['mysql'] -> getOneRecord("SELECT count(id) as sums FROM guild_challenges WHERE defenser_id = $gid");
	if($sumcheck1['sums'] >= 3){
		die('4');//该家族已经收到三份战书，不能再下了，试试别的吧
	}
	//判断你是否已经对该家族下了战书
	$sumcheck2 = $_pm['mysql'] -> getOneRecord("SELECT id FROM guild_challenges WHERE challenger_id = {$check['guild_id']} AND defenser_id = $gid");
	if($sumcheck2['id'] > 0){
		die('6');//您的家族已经对此家族下了战书
	}
	
	//检查等级是否在五级以内
	$yougild = $_pm['mysql'] -> getOneRecord("SELECT level FROM guild WHERE id = $gid");
	if(!is_array($yougild)){
		die('数据错误2');
	}
	$myguild = $_pm['mysql'] -> getOneRecord("SELECT a.level-b.level lvldiff FROM guild a,guild b WHERE a.id= {$check['guild_id']} and b.id = $gid");
	if($myguild['lvldiff'] < -5 ||  $myguild['lvldiff'] >= 5){
		die('5');//您只能对等级相差为5的家族下战书
	}
	$time = time();
	$_pm['mysql'] -> query("INSERT INTO guild_challenges (challenger_id,defenser_id,create_time) VALUES({$check['guild_id']},$gid,$time)");
	die('10');
}else if($op == 'accept'){
	$gid = intval($_GET['id']);
	if($gid == 0){
		die('数据错误');
	}
	
	//判断时间是否可下战书（战斗中不能下战书）
	$today = date("Y-m-d",time());
														 
	// 战场开放时间开关。
	$week = date("N", time());
	$hourM= date("Hi", time());
	
	$battletimearr = unserialize($_pm['mem']->get(MEM_TIME_KEY));
	
	foreach($battletimearr as $bv){
		if($bv['titles'] != "guild_battle")
		{
			continue;
		}
		if($week == $bv['days'] && ($hourM >= $bv['starttime'] && $hourM < $bv['endtime'])){//战场已经开始
			die('1');//已经开始不能再接受
		}
	}
	
	//判断是否有权限接受战书
	$check = $_pm['mysql'] -> getOneRecord("SELECT guild_id,priv FROM guild_members WHERE member_id = {$_SESSION['id']}");
	if(!is_array($check) || $check['priv'] < 2){
		die('2');//您没有权限操作
	}
	if($check['guild_id'] == $gid){
		die('操作有误！');
	}
	$arc = $_pm['mysql'] -> getOneRecord("SELECT id FROM guild_challenges WHERE (challenger_id = $gid or defenser_id = $gid or challenger_id = {$check['guild_id']} or defenser_id = {$check['guild_id']}) AND flags = 1");
	if(is_array($arc)){
		die('3');//您的家族或者对方家族已经接受战书，不能再接受了！
	}
	
	
	//检查等级是否在五级以内
	$yougild = $_pm['mysql'] -> getOneRecord("SELECT level FROM guild WHERE id = $gid");
	if(!is_array($yougild)){
		die('数据错误2');
	}
	$myguild = $_pm['mysql'] -> getOneRecord("SELECT a.level-b.level lvldiff FROM guild a,guild b WHERE a.id= {$check['guild_id']} and b.id = $gid");
	if($myguild['lvldiff'] < -5 ||  $myguild['lvldiff'] >= 5){
		die('5');//您接受的战书请求等级相差最多为5
	}
	$time = time();
	$_pm['mysql'] -> query("UPDATE guild_challenges SET flags = 1 WHERE challenger_id = $gid AND defenser_id = {$check['guild_id']}");
	$_pm['mysql'] -> query("DELETE FROM guild_challenges WHERE (challenger_id = $gid OR defenser_id = $gid) AND flags = 0");
	$_pm['mysql'] -> query("DELETE FROM guild_challenges WHERE (challenger_id = {$check['guild_id']} OR defenser_id = {$check['guild_id']}) AND flags = 0");
	die('10');
}else if($op == 'select_guild'){
	$ar = $_pm['mysql'] -> getOneRecord("SELECT guild_id FROM guild_members WHERE member_id = {$_SESSION['id']}");
	die($ar['guild_id']);
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
