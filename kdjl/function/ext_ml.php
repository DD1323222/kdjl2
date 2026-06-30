<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2009.12.8
*@Update Date: 
*@Usage: 魅力
*@Note: none
*/
require_once('../config/config.game.php');
header('Content-Type:text/html;charset=gbk');
secStart($_pm['mem']);
if($_GET['action'] == 'ml'){
	$mlarr = $_pm['mysql'] -> getRecords('SELECT nickname,ml FROM player,player_ext WHERE player.id = player_ext.uid AND ml > 0 ORDER BY ml DESC limit 50');
	$html = '<table width="100%" border="0" cellpadding="0" cellspacing="1" bgcolor="#CCCCCC" >
      <tr>
        <td height="20" colspan="3" align="center" bgcolor="#FFFFFF">魅力排行</td>
        </tr>
      <tr>
        <td height="20" align="center" bgcolor="#FFFFFF">名次</td>
        <td height="20" align="center" bgcolor="#FFFFFF">角色</td>
        <td height="20" align="center" bgcolor="#FFFFFF">魅力</td>
      </tr>';
	  if(empty($mlarr)){
	  	$html .= '<tr>
        <td height="20" colspan="3" align="center" bgcolor="#FFFFFF">排行榜为空！</td>
        </tr>';
	  }else{
	  	$i = 1;
	  	foreach($mlarr as $v){
			$html .= '<tr>
        <td height="20" align="center" bgcolor="#FFFFFF">'.$i.'</td>
        <td height="20" align="center" bgcolor="#FFFFFF" style="cursor:pointer" onclick=\'giveTo("'.$v['nickname'].'")\'>'.$v['nickname'].'</td>
        <td height="20" align="center" bgcolor="#FFFFFF">'.$v['ml'].'</td>
      </tr>';
			$i++;
		}
	}
	$html .= '</table>';
	die($html);
}
$uname = isset($_REQUEST['uname']) ? trim($_REQUEST['uname']) : '';
$pname = isset($_REQUEST['pname']) ? trim($_REQUEST['pname']) : '';
$unameSql = $_pm['mysql']->escape($uname);
$pnameSql = $_pm['mysql']->escape($pname);
$sums = intval($_GET['sums']);
if($sums < 1 || empty($uname) || empty($pname)){
	die('a');//填写完整
}

$ucheck = $_pm['mysql'] -> getOneRecord("SELECT id FROM player WHERE nickname = '$unameSql' and password != '00000000000000000000000000000000'");
if(empty($ucheck)){
	die('b');//用户名填写不正确
}
if($ucheck['id'] == $_SESSION['id']){
	die('e');
}
$pcheck = $_pm['mysql'] -> getOneRecord("SELECT userbag.id as bid,name,effect FROM props,userbag WHERE userbag.pid = props.id AND name = '$pnameSql' AND varyname = 17 AND sums >= $sums AND uid = {$_SESSION['id']}");
if(empty($pcheck)){
	die('c');//数量和名称不对
}

$_pm['mysql']->query("UPDATE userbag SET sums = sums - $sums WHERE id = {$pcheck['bid']} AND uid = {$_SESSION['id']} AND sums >= $sums");

$result = mysql_affected_rows($_pm['mysql'] -> getConn());
if($result != 1){
	die('d');//数量不够
}

$arr = explode(':',$pcheck['effect']);
$num = $arr[1] * $sums;


$ar = $_pm['mysql'] -> getOneRecord("SELECT uid FROM ml WHERE uid = {$_SESSION['id']} AND tid = {$ucheck['id']}");
if(empty($ar)){
	$_pm['mysql'] -> query("insert into `ml` (uid,sml,tid) values ({$_SESSION['id']},$num,{$ucheck['id']})");
}else{
	$_pm['mysql'] -> query("update ml set sml = sml + $num WHERE uid = {$_SESSION['id']} AND tid = {$ucheck['id']}");
}

if($arr[0] == 'ml'){
	$f = $_pm['mysql'] -> getOneRecord("SELECT uid,ml FROM player_ext WHERE uid = {$ucheck['id']}");
	if(empty($f)){
		$_pm['mysql'] -> query("REPLACE INTO player_ext(uid,bbshow,ml) VALUES ({$ucheck['id']},5,{$num})");
	}else{
		$_pm['mysql'] -> query("UPDATE player_ext SET ml = ml + {$num} WHERE uid = {$ucheck['id']}");
	}
}
echo $num;
?>
