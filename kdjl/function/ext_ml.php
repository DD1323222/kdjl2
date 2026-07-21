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
require_once('../sec/dblock_fun.php');
header('Content-Type:text/html;charset=UTF-8');
secStart($_pm['mem']);
function extMlHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function extMlJsDouble($value)
{
	$value = str_replace("\\", "\\\\", (string)$value);
	$value = str_replace('"', '\\"', $value);
	$value = str_replace(array("\r", "\n"), array("\\r", "\\n"), $value);
	return $value;
}

$extMlLockHeld = false;
$extMlTransactionActive = false;
function extMlFail($message)
{
	global $_pm,$extMlLockHeld,$extMlTransactionActive;
	if($extMlTransactionActive) $_pm['mysql']->query('ROLLBACK');
	$extMlTransactionActive = false;
	if($extMlLockHeld)
	{
		realseLock();
		$extMlLockHeld = false;
	}
	die($message);
}
function extMlShutdown()
{
	global $_pm,$extMlLockHeld,$extMlTransactionActive;
	if($extMlTransactionActive) $_pm['mysql']->query('ROLLBACK');
	$extMlTransactionActive = false;
	if($extMlLockHeld)
	{
		realseLock();
		$extMlLockHeld = false;
	}
}
register_shutdown_function('extMlShutdown');

$action = (isset($_GET['action']) && !is_array($_GET['action'])) ? $_GET['action'] : '';
if($action == 'ml'){
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
			if(!is_array($v)) continue;
			$nickname = isset($v['nickname']) ? $v['nickname'] : '';
			$nicknameHtml = extMlHtml($nickname);
			$nicknameJs = extMlHtml(extMlJsDouble($nickname));
			$ml = isset($v['ml']) ? intval($v['ml']) : 0;
			$html .= '<tr>
        <td height="20" align="center" bgcolor="#FFFFFF">'.$i.'</td>
        <td height="20" align="center" bgcolor="#FFFFFF" style="cursor:pointer" onclick=\'giveTo("'.$nicknameJs.'")\'>'.$nicknameHtml.'</td>
        <td height="20" align="center" bgcolor="#FFFFFF">'.$ml.'</td>
      </tr>';
			$i++;
		}
	}
	$html .= '</table>';
	die($html);
}
$uname = (isset($_REQUEST['uname']) && !is_array($_REQUEST['uname'])) ? trim($_REQUEST['uname']) : '';
$pname = (isset($_REQUEST['pname']) && !is_array($_REQUEST['pname'])) ? trim($_REQUEST['pname']) : '';
$unameSql = $_pm['mysql']->escape($uname);
$pnameSql = $_pm['mysql']->escape($pname);
$sums = (isset($_GET['sums']) && !is_array($_GET['sums'])) ? intval($_GET['sums']) : 0;
if($sums < 1 || empty($uname) || empty($pname)){
	die('a');//填写完整
}

$ucheck = $_pm['mysql'] -> getOneRecord("SELECT id FROM player WHERE nickname = '$unameSql' and password != '00000000000000000000000000000000'");
if(empty($ucheck)){
	die('b');//用户名填写不正确
}
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('d');
if($ucheck['id'] == $uid){
	die('e');
}
$targetUid = intval($ucheck['id']);
$lock = getLock($uid);
if(!is_array($lock)){
	realseLock();
	die('d');
}
$extMlLockHeld = true;
$extMlTransactionActive = true;
$pcheck = $_pm['mysql'] -> getOneRecord("SELECT userbag.id as bid,name,effect
                                           FROM props,userbag
                                          WHERE userbag.pid = props.id
                                            AND name = '$pnameSql'
                                            AND varyname = 17
                                            AND sums >= $sums
                                            AND zbing = 0
                                            AND (userbag.cantrade IS NULL OR userbag.cantrade <> 3)
                                            AND uid = {$uid}
                                       ORDER BY userbag.id
                                          LIMIT 1 FOR UPDATE");
if(empty($pcheck)){
	extMlFail('c');//数量和名称不对
}

$arr = explode(':',$pcheck['effect']);
if(count($arr) < 2 || $arr[0] != 'ml' || intval($arr[1]) < 1){
	extMlFail('c');
}
$num = intval($arr[1]) * $sums;
$bagId = intval($pcheck['bid']);
$itemUsed = $_pm['mysql']->query("UPDATE userbag
                         SET sums = sums - $sums
                       WHERE id = {$bagId}
                         AND uid = {$uid}
                         AND sums >= $sums
                         AND zbing = 0
                         AND (cantrade IS NULL OR cantrade <> 3)");

$result = $itemUsed ? mysql_affected_rows($_pm['mysql'] -> getConn()) : 0;
if($result != 1){
	extMlFail('d');//数量不够
}

if(!$_pm['mysql']->query("DELETE FROM userbag WHERE id = {$bagId} AND uid = {$uid} AND sums <= 0 AND bsum <= 0 AND psum <= 0 AND pyb = 0 AND zbing = 0 AND (cantrade IS NULL OR cantrade <> 3)")){
	extMlFail('d');
}

$ar = $_pm['mysql'] -> getOneRecord("SELECT uid FROM ml WHERE uid = {$uid} AND tid = {$targetUid} FOR UPDATE");
if(empty($ar)){
	$mlOk = $_pm['mysql'] -> query("insert into `ml` (uid,sml,tid) values ({$uid},{$num},{$targetUid})");
}else{
	$mlOk = $_pm['mysql'] -> query("update ml set sml = COALESCE(sml,0) + {$num} WHERE uid = {$uid} AND tid = {$targetUid}");
}
if(!$mlOk || mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){
	extMlFail('d');
}

if($arr[0] == 'ml'){
	$f = $_pm['mysql'] -> getOneRecord("SELECT uid,ml FROM player_ext WHERE uid = {$targetUid} FOR UPDATE");
	if(empty($f)){
		$extOk = $_pm['mysql'] -> query("INSERT INTO player_ext(uid,bbshow,ml) VALUES ({$targetUid},5,{$num}) ON DUPLICATE KEY UPDATE ml=COALESCE(ml,0)+VALUES(ml)");
	}else{
		$extOk = $_pm['mysql'] -> query("UPDATE player_ext SET ml = COALESCE(ml,0) + {$num} WHERE uid = {$targetUid}");
	}
	if(!$extOk || mysql_affected_rows($_pm['mysql'] -> getConn()) < 1){
		extMlFail('d');
	}
}
if(!$_pm['mysql']->query('COMMIT')){
	extMlFail('d');
}
$extMlTransactionActive = false;
$_pm['mem']->del(MEM_USERBAG_KEY);
$_pm['mem']->del($targetUid);
realseLock();
$extMlLockHeld = false;
echo $num;
?>
