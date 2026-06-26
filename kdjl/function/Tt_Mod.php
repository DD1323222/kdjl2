<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.22
*@Usage: Expore privew. --> Team
*@Note: 
  @sugefei update: 2008-09-08 10:27 调整组队界面的在线玩家显示，优化SQL。
*/
require_once('../config/config.game.php');
define(MEM_FIGHTUSER_KEY, $_SESSION['id'] . 'fuser');
secStart($_pm['mem']);

$user		= $_pm['user']->getUserById($_SESSION['id']);
$petsarr	= $_pm['user']->getUserPetById($_SESSION['id']);

$_SESSION['exptype'.$_SESSION['id']] = "";
if($_SESSION['way'.$_SESSION['id']] == "" || $_SESSION['way'.$_SESSION['id']] == "money")
{
	$num = $user['sysautosum'];
}
else if($_SESSION['way'.$_SESSION['id']] == "yb")
{
	$num = $user['maxautofitsum'];
}
$_pm['mysql']->query("UPDATE player
					     SET autofitflag=0
					   WHERE id={$_SESSION['id']}
					");
$n = intval($_REQUEST['n']);
$table = "";
if($n == 16 || $n >= 100)
{
	$table = '<table width="100%" height="28" border="0" cellpadding="0" cellspacing="0" style="margin-bottom:10px">
			<tr>
            <td height="25" colspan="4"  align="left">&nbsp;&nbsp;&nbsp;&nbsp;当前选择的难度：<span id="sign">普通</span></td>
          </tr>
        </table><table width="100%" border="0" cellspacing="0" cellpadding="0"  style="margin-bottom:10px">
          <tr>
            <td width="94"  align="right">
			<img src="'.IMAGE_SRC_URL.'/ui/team/ann07.gif" width="64" height="28" style="padding-right:5px;cursor:pointer;" onclick="pk1(1);mapid='.$n.'"></td>
            <td><img src="'.IMAGE_SRC_URL.'/ui/team/ann08.gif" width="64" height="28" style="cursor:pointer;" onclick="pk1(2);mapid='.$n.'"></td>
            <td><img src="'.IMAGE_SRC_URL.'/ui/team/ann09.gif" width="64" height="28" style="cursor:pointer;" onclick="pk1(3);mapid='.$n.'"></td>
            <td width="12">&nbsp;</td>
          </tr>
        </table>';
}
$memmapid = unserialize($_pm['mem']->get('db_mapid'));
if ($n>0)
{
	$map = $memmapid[$n];
	/*$map = $_pm['mem']->dataGet(array('k' => MEM_MAP_KEY, 
					  	     'v' => "if(\$rs['id'] == '{$n}') \$ret=\$rs;"
					 ));*/
	if (!is_array($map))
	{
		$mapinfo = '载入地图出错！';
	}
}
else {die('地图数据错误！');}



$kk=0;
$selid=0; // default select pets!
$lmt = explode(',', $map['level']);
if (is_array($petsarr))
{
	foreach ($petsarr as $k =>$rs) // Will filter in muchang pets for current user.
	{
		/*if ($rs['muchang'] == 1) continue;
		if ($kk == 0) {$sel = 100;$selid=$rs['id'];}
		else $sel = 50;
		if($rs['level']==0) $rs['level']=1;*/
		if($rs['muchang'] == 1) continue;
		if($rs['id'] == $user['mbid'])
		{
			$sel = 100;
			$selid=$rs['id'];
		}
		else
		{
			$sel = 50;
		}
		if($rs['level']==0) $rs['level']=1;
		$pets[$kk++] = "<img src='".IMAGE_SRC_URL."/bb/{$rs['cardimg']}' onClick=\"Setbbs({$rs['id']},{$rs['level']},{$lmt['0']},this);\" alt=\"{$rs['name']}\" style='cursor:pointer;filter:alpha(opacity={$sel});' id='i{$kk}'> ";
		if ($kk==3) break;
	}
}

$useridlist = $_pm['mysql']->getRecords("SELECT player.nickname,player_ext.tgt FROM player,player_ext WHERE player.id=player_ext.uid AND tgt!=0
										  ORDER by player_ext.tgt DESC
										  LIMIT 0,5");
if (is_array($useridlist))
{
	$online .= '<tr>
              <td width="20" height="23">&nbsp;</td>
              <td width="40">名次</td>
              <td width="90">玩家姓名</td>
              <td>通关数</td>
            </tr>';
	foreach ($useridlist as $k => $tuser)
	{
		$i = 0;
		if (is_array($tuser))
		{
			$i = $k+1;
			$online .= '<tr>
              <td width="20" height="23">&nbsp;</td>
              <td width="40">'.$i.'</td>
              <td width="90">'.$tuser['nickname'].'</td>
              <td>'.$tuser['tgt'].'</td>
            </tr>';
		}
	}
}else{
	$online = '<tr>
              <td width="20" height="23">&nbsp;</td>
              <td width="40"></td>
              <td width="90">排行为空</td>
              <td></td>
            </tr>';
}
//$online="<tr><td width=200>暂时关闭显示玩家列表</td><td></td></tr>\n";

// Save map position to user.
$user['inmap'] = $map['id'];
$_pm['mysql']->query("UPDATE player 
						 SET inmap='{$map['id']}'
					   WHERE id = {$_SESSION['id']}
					");
//$_pm['user']->updateMemUser($_SESSION['id']);
//###########################
// @Load template.
//###########################


/*雷迅 15:01:52
$tpl = unserialize($_pm['mem']->get($mmKey));
	if(empty($tpl)||isset($_GET['clearTpCache'])){
雷迅 15:02:09
$tpl = file_get_contents($tn);
雷迅 15:02:18
$mmKey = str_replace(array(".","/"."\\",":"),"_",$tn);
	$tpl = unserialize($_pm['mem']->get($mmKey));
	if(empty($tpl)||isset($_GET['clearTpCache'])){
		$tpl = file_get_contents($tn);
		$_pm['mem']->set(array('k'=>$mmKey, 'v'=>$tpl));
	}*/
$gw=array();
$monsters=split(",",$map['gpclist']);
if($monsters){
	foreach ($monsters as $v)
	{
		$gw[]='<span onclick="copyWord(\'怪物-'.$v.'\');">'.$v.'</span>';
	}
}
$maggw=implode(",",$gw);	
//成长限制
if(empty($map['czlprops']))
{
	$czl = "无限制";
}
else
{
	$arr = explode("|",$map['czlprops']);
	if(empty($arr[0]))
	{
		$czl = "无限制";
	}
	else
	{
		$czl = $arr[0];
	}
}


$sql = "SELECT tgt,tgttime,uid FROM player_ext WHERE uid = {$_SESSION['id']}";
$uarr = $_pm['mysql'] -> getOneRecord($sql);
if(!is_array($uarr)){
	$_pm['mysql'] -> query("INSERT INTO player_ext (uid,bbshow) VALUES ({$_SESSION['id']},5)");
	$tgt = 0;
}else{
	if($uarr['tgttime'] > 0){
		$time = time();
		$ctime = $time - $uarr['tgttime'];
		$day = 24*3600;
		if($ctime > $day){
			$_pm['mysql'] -> query("UPDATE player_ext SET tgt=0,tgttime=0 WHERE uid = {$_SESSION['id']}");
			$tgt = $uarr['tgt'];
		}
	}else{
		$tgt = $uarr['tgt'];
	}
	
}

$tn = $_game['template'] . 'tpl_tt.html';
if (file_exists($tn))
{	
	$tpl = file_get_contents($tn);

	if($n) 
	{
		$src = array(
					 "#one#",
					 "#two#",
					 "#three#",
					 "#otherlist#",
					 "#bid#",
					 "#head1#",
					 "#head1info#",
					 "#_self#",
					 "#num#",
					 "#table#",
					 "#mapid#",
					 "#tgt#"
					);
		$des = array(
					 $pets[0],
					 $pets[1],
					 $pets[2],
					 $online,
					 $selid,
					 $user['headimg'].'.gif',
					 '昵称：'.$user['nickname'],
					 $user['nickname'],
					 $num,
					 $table,
					 $n,
					 $tgt
				);
	}

	$ret = str_replace($src, $des, $tpl);
}
$_pm['mem']->memClose();
// gzip echo. if maybe.
ob_start('ob_gzip');
echo $ret;
ob_end_flush();




//$num 刷新次数

function getGpc($num){
	global $_pm;
	if($num <= 3){
		$vary = rand(1,2);
	}else if($num == 4){
		$vary = rand(2,3);
	}else{
		$vary = rand(1,5);
	}
	$arr = $_pm['mysql'] -> getRecords("SELECT gpc,boss FROM c_gpc WHERE boss = $vary");
	if(empty($arr)){
		return false;
	}
	$count = count($arr) - 1;
	$gid = rand(0,$count);
	return $arr[$gid];
}
?>
