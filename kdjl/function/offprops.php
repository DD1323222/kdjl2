<?php
session_start();
//避免出现乱码
require_once "../config/config.game.php";
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die("1");
$userBag = $_pm['user'] -> getUserBagById($uid);
$user = $_pm['user'] -> getUserById($uid);
secStart($_pm['mem']);
if(!is_array($user)) die("1");
if(!isset($user['maxbag'])) $user['maxbag'] = 0;
$action = (isset($_REQUEST['action']) && !is_array($_REQUEST['action'])) ? $_REQUEST['action'] : '';
if($action == "to")
{
	$err = 0;
	$id = (isset($_REQUEST['id']) && !is_array($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0;
	$oldPid = isset($_SESSION['pid'.$uid]) ? $_SESSION['pid'.$uid] : '';
	$oldPids = isset($_SESSION['pids'.$uid]) ? $_SESSION['pids'.$uid] : '';
	if($oldPid !== '' && $id == $oldPid)
	{
		$_SESSION['pid'.$uid] = "";
		$_SESSION['bid'.$uid] = "";
		$err = 1;
	}
	else if($oldPids !== '' && $id == $oldPids)
	{
		$_SESSION['pids'.$uid] = "";
		$err = 1;
	}
	echo $err;
	//$_SESSION['dbg_equip_attr2'] .= "Why here 1?<br>";
}
else
{
	$err = 0;
    $str = "";
    $newStr = "";
	$id = (isset($_REQUEST['id']) && !is_array($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0;
	if($id < 1)
	{
		die("1");
	}
	$bid = (isset($_REQUEST['bid']) && !is_array($_REQUEST['bid'])) ? intval($_REQUEST['bid']) : 0;
	if($bid < 1)
	{
		die("3");
	}

	if(!$_pm['mysql']->query('START TRANSACTION'))
	{
		die("4");
	}
	$lockedUser = $_pm['mysql']->getOneRecord("SELECT maxbag FROM player WHERE id={$uid} FOR UPDATE");
	$lockedBag = $_pm['mysql']->getRecords("SELECT id,sums,zbing FROM userbag WHERE uid={$uid} FOR UPDATE");
	if(!is_array($lockedUser) || !is_array($lockedBag))
	{
		$_pm['mysql']->query('ROLLBACK');
		die("4");
	}
	$bagNum = 0;
	foreach($lockedBag as $bagRow)
	{
		if(is_array($bagRow) && intval($bagRow['sums']) > 0 && intval($bagRow['zbing']) == 0) $bagNum++;
	}
	if($bagNum >= intval($lockedUser['maxbag']))
	{
		$_pm['mysql']->query('ROLLBACK');
		die('5');
	}
	$sql = "SELECT id
			FROM userbag
			WHERE zbing = 1 and zbpets = {$bid} and uid = {$uid} and pid = {$id} FOR UPDATE";
	$row = $_pm['mysql'] -> getOneRecord($sql);
	//判断包裹ID是否有效（为空），当用户非法操作的时候可能出现此情况
	if(!is_array($row) || $row['id'] == "")
	{
		$_pm['mysql']->query('ROLLBACK');
		die("4");
	}
	if(isset($_REQUEST['batch']) && intval($_REQUEST['batch']) == 1)
	{
		$lastKey = 'last_takeoff_equips_'.$uid.'_'.$bid;
		$tokenKey = $lastKey.'_token';
		$batchKey = (isset($_REQUEST['batchkey']) && !is_array($_REQUEST['batchkey'])) ? $_REQUEST['batchkey'] : '';
		if($batchKey !== '')
		{
			if(!isset($_SESSION[$tokenKey]) || $_SESSION[$tokenKey] !== $batchKey)
			{
				$_SESSION[$tokenKey] = $batchKey;
				$_SESSION[$lastKey] = array();
			}
		}
		if(!isset($_SESSION[$lastKey]) || !is_array($_SESSION[$lastKey])) $_SESSION[$lastKey] = array();
		$_SESSION[$lastKey][] = intval($row['id']);
	}
	$sql = "SELECT zb
			FROM userbb
			WHERE id = {$bid} and uid = {$uid} FOR UPDATE";
	$rs = $_pm['mysql'] -> getOneRecord($sql);
	if(!is_array($rs))
	{
		$_pm['mysql']->query('ROLLBACK');
		die("4");
	}
	if(is_array($rs))
	{
		$zb = explode(",",$rs['zb']);
		if(is_array($zb))
		{
			foreach($zb as $k => $v)
			{
				$zbs = explode(":",$zb[$k]);
				if(is_array($zbs) && count($zbs) >= 2)
				{
					if($zbs[1] == $row['id'])
					{
						continue;
					}
					else
					{
						$str .= $zbs[0].":".$zbs[1].",";
						//$str后多了一个","
					}
				}
			}
		}
	}
	//去$str 后的多的那个","
	if(!empty($str))
	{
		$newStr = substr($str,0,-1);
	}
	$sql = "UPDATE userbag SET zbing = 0,zbpets = 0 WHERE id = {$row['id']} and uid = {$uid}";
	if(!$_pm['mysql'] -> query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
	{
		$_pm['mysql']->query('ROLLBACK');
		die("4");
	}
	$newStrSql = $_pm['mysql']->escape($newStr);
	$sql = "UPDATE userbb
            SET zb = '{$newStrSql}'
            WHERE id = {$bid} and uid = {$uid}";
	if($_pm['mysql'] -> query($sql) === false || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
	{
		$_pm['mysql']->query('ROLLBACK');
		die("4");
	}
	if(!$_pm['mysql']->query("UPDATE userbb SET addmp=0,addhp=0 WHERE id={$bid} AND uid={$uid}") || !$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		die("4");
	}
	$err = 2;
	$_pm['mem']->del(MEM_USERBB_KEY);
	$_pm['mem']->del(MEM_USERBAG_KEY);
	//设定装备变化标志
	formatMsgEffect($bid);
	$_pm['mem']->set(array("k"=>"User_bb_equip_changed_".$bid.'_'.$uid,"v"=>1));


	$_pm['mem']->del("User_bb_equip_info_a_".$bid.'_'.$uid);
	$_pm['mem']->del("User_bb_equip_info_b_".$bid.'_'.$uid);

	//$_SESSION['dbg_equip_attr2'] .= "<strong>Right here 1!</strong><br>";
	echo $err;
}
$_pm['mem']->memClose();
?>
