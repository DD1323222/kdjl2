<?php
require_once('../config/config.game.php');
require_once('../config/config.fuben.php');
secStart($_pm['mem']);
//$num 刷新次数

$action = (isset($_GET['action']) && !is_array($_GET['action'])) ? $_GET['action'] : '';
$type = (isset($_GET['type']) && !is_array($_GET['type'])) ? $_GET['type'] : '';
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid <= 0) die("100");
$user = array();

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

function normalizeChallengeGids($gpc)
{
	$list = explode(',', $gpc);
	$ret = array();
	foreach($list as $gid)
	{
		$gid = intval(trim($gid));
		if($gid > 0) $ret[] = $gid;
	}
	return $ret;
}

if ($action == 'schallenge') {
    $memgpc = $_pm['mem']->get('db_gpcid');
    if(!is_array($memgpc)) $memgpc = kdjlSafeMemValue($memgpc, array());
    $gpccolor = array(5 => '白', 6 => '黄', 7 => '蓝', 8 => '紫', 9 => '红');
    if(!$_pm['mysql']->query('START TRANSACTION')) die("100");
    $carr = $_pm['mysql']->getOneRecord("SELECT snums FROM challenge WHERE uid = {$uid} FOR UPDATE");
    //最大打30分钟
    $time = time();
    if (empty($carr)) {
        $_pm['mysql']->query('ROLLBACK');
        die("100");
    } else {
        $snums = isset($carr['snums']) ? intval($carr['snums']) : 0;
        if ((2 - $snums) > 0) {
            if(!$_pm['mysql']->query("DELETE FROM challenge_log WHERE uid = {$uid}")){
                $_pm['mysql']->query('ROLLBACK');
                die("100");
            }
            $garr = getGpc(5);
            if(!is_array($garr)){
                $_pm['mysql']->query('ROLLBACK');
                die("100");
            }
            $vary = isset($garr['boss']) ? intval($garr['boss']) : 0;
            $glist = normalizeChallengeGids(isset($garr['gpc']) ? $garr['gpc'] : '');
            if(empty($glist)){
                $_pm['mysql']->query('ROLLBACK');
                die("100");
            }
            $totalnums=count($glist);
            foreach ($glist as $v) {
                if(!$_pm['mysql']->query("INSERT INTO challenge_log (uid,gid) VALUES({$uid},$v)")){
                    $_pm['mysql']->query('ROLLBACK');
                    die("100");
                }
            }
            if ($_pm['mysql']->query("UPDATE challenge SET lastvtime = $time,gid = {$glist[0]},vary = $vary,snums = COALESCE(snums,0)+1,flag = 0,totalnums=$totalnums WHERE uid = {$uid}") && mysql_affected_rows($_pm['mysql']->getConn()) == 1 && $_pm['mysql']->query('COMMIT')) {
                die('1');
            }
            $_pm['mysql']->query('ROLLBACK');
            die("100");
        } else {
            $_pm['mysql']->query('ROLLBACK');
            die('102');
        }
    }
}
elseif ($action == 'sjschallenge') {
    $memgpc = $_pm['mem']->get('db_gpcid');
    if(!is_array($memgpc)) $memgpc = kdjlSafeMemValue($memgpc, array());
    $gpccolor = array(5 => '白', 6 => '黄', 7 => '蓝', 8 => '紫', 9 => '红');
    $carr = $_pm['mysql']->getOneRecord("SELECT nums,totalnums FROM challenge WHERE uid = {$uid}");
    $time = time();
    if (empty($carr)) {
        die("100");
    } else {
        if(!$_pm['mysql']->query("INSERT INTO player_ext(uid,bbshow) VALUES({$uid},5) ON DUPLICATE KEY UPDATE uid=uid")){
            die("200");
        }
        $arr = $_pm['mysql']->getOneRecord("SELECT sj FROM player_ext WHERE uid = {$uid}");
        if (is_array($arr)) {
            $user['sj'] = isset($arr['sj']) ? intval($arr['sj']) : 0;
        } else {
            $user['sj'] = 0;
            die("200");
        }
        $nowcoin = $user['sj'];
        if (10 > $nowcoin) {
            die("200");
        } else {
            $user['sj'] = $nowcoin - 10;
            $tzlog= $_pm['mysql']->getOneRecord("SELECT count(*) FROM challenge_log WHERE uid = {$uid}");
            $logCount = is_array($tzlog) && isset($tzlog['count(*)']) ? intval($tzlog['count(*)']) : 0;
            $totalBefore = isset($carr['totalnums']) ? intval($carr['totalnums']) : 0;
            $numsBefore = isset($carr['nums']) ? intval($carr['nums']) : 0;
            if($logCount != $totalBefore){
                $nums  =  $numsBefore+1;
            }else{
                $nums  = $numsBefore;
            }
            $garr = getGpc(5);
            if(!is_array($garr)){
                die("100");
            }
            $vary = isset($garr['boss']) ? intval($garr['boss']) : 0;
            $glist = normalizeChallengeGids(isset($garr['gpc']) ? $garr['gpc'] : '');
            if(empty($glist)){
                die("100");
            }
            if(!$_pm['mysql']->query('START TRANSACTION')){
                die("100");
            }
            $lockedExt = $_pm['mysql']->getOneRecord("SELECT sj FROM player_ext WHERE uid = {$uid} FOR UPDATE");
            if(!is_array($lockedExt) || intval($lockedExt['sj']) < 10){
                $_pm['mysql']->query('ROLLBACK');
                die("200");
            }
            $lockedChallenge = $_pm['mysql']->getOneRecord("SELECT nums,totalnums FROM challenge WHERE uid = {$uid} FOR UPDATE");
            if(!is_array($lockedChallenge)){
                $_pm['mysql']->query('ROLLBACK');
                die("100");
            }
            $tzlog = $_pm['mysql']->getOneRecord("SELECT count(*) AS cnt FROM challenge_log WHERE uid = {$uid}");
            $logCount = is_array($tzlog) && isset($tzlog['cnt']) ? intval($tzlog['cnt']) : 0;
            $totalBefore = isset($lockedChallenge['totalnums']) ? intval($lockedChallenge['totalnums']) : 0;
            $numsBefore = isset($lockedChallenge['nums']) ? intval($lockedChallenge['nums']) : 0;
            if($logCount != $totalBefore){
                $nums = $numsBefore + 1;
            }else{
                $nums = $numsBefore;
            }
            if(!$_pm['mysql']->query("update player_ext set sj=sj-10 where uid={$uid} and sj>=10") || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
                $_pm['mysql']->query('ROLLBACK');
                die("200");
            }
            if(!$_pm['mysql']->query("DELETE FROM challenge_log WHERE uid = {$uid}")){
                $_pm['mysql']->query('ROLLBACK');
                die("100");
            }
            foreach ($glist as $v) {
                if(!$_pm['mysql']->query("INSERT INTO challenge_log (uid,gid) VALUES({$uid},$v)")){
                    $_pm['mysql']->query('ROLLBACK');
                    die("100");
                }
            }
            $totalnums=count($glist);
			if ($_pm['mysql']->query("UPDATE challenge SET lastvtime = $time,gid = {$glist[0]},vary = $vary,nums=$nums,flag = 0,totalnums=$totalnums WHERE uid = {$uid}") && $_pm['mysql']->query('COMMIT')) die('1');
            $_pm['mysql']->query('ROLLBACK');
            die("100");
        }
    }
} elseif ($action == 'gomap' && $type == 'do') {
    if(!$_pm['mysql']->query("INSERT INTO player_ext(uid,bbshow) VALUES({$uid},5) ON DUPLICATE KEY UPDATE uid=uid")){
        die("b");
    }
    $arr = $_pm['mysql']->getOneRecord("SELECT sj FROM player_ext WHERE uid = {$uid}");
    if (is_array($arr)) {
        $user['sj'] = isset($arr['sj']) ? intval($arr['sj']) : 0;
    } else {
        $user['sj'] = 0;
        die("b");
    }
    $nowcoin = $user['sj'];
    if (50 > $nowcoin) {
        die("b");
    } else {
        $time=time();
        if(!$_pm['mysql']->query('START TRANSACTION')){
            die("100");
        }
        $lockedExt = $_pm['mysql']->getOneRecord("SELECT sj FROM player_ext WHERE uid = {$uid} FOR UPDATE");
        if(!is_array($lockedExt) || intval($lockedExt['sj']) < 50){
            $_pm['mysql']->query('ROLLBACK');
            die("b");
        }
        $lockedChallenge = $_pm['mysql']->getOneRecord("SELECT uid FROM challenge WHERE uid = {$uid} FOR UPDATE");
        if(!is_array($lockedChallenge)){
            $_pm['mysql']->query('ROLLBACK');
            die("100");
        }
        if(!$_pm['mysql']->query("update player_ext set sj=sj-50 where uid={$uid} and sj>=50") || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
            $_pm['mysql']->query('ROLLBACK');
            die("b");
        }
        if ($_pm['mysql']->query("UPDATE challenge SET lastvtime = $time,nums=COALESCE(nums,0)+1,flag = 1 WHERE uid = {$uid}") && mysql_affected_rows($_pm['mysql']->getConn()) == 1 && $_pm['mysql']->query('COMMIT')) die("2");
        $_pm['mysql']->query('ROLLBACK');
    }
    die("100");
} elseif ($action == 'gomap') {
    $carr = $_pm['mysql']->getOneRecord("SELECT nums FROM challenge WHERE uid = {$uid}");
    if (!is_array($carr)) die("a");
    if ((3 - (isset($carr['nums']) ? $carr['nums'] : 0)) > 0) {
        $time = time();
        if ($_pm['mysql']->query("UPDATE challenge SET lastvtime = $time,flag = 1 WHERE uid = {$uid}")) die('2');
    } else {
        die("a");
    }
} else {
    $mapid = (isset($_REQUEST['mapid']) && !is_array($_REQUEST['mapid'])) ? intval($_REQUEST['mapid']) : 0;
    $requestBid = (isset($_REQUEST['bid']) && !is_array($_REQUEST['bid'])) ? intval($_REQUEST['bid']) : 0;
    $sessionBid = isset($_SESSION["fight"]["bid"]) ? intval($_SESSION["fight"]["bid"]) : 0;
    $bid = $requestBid > 0 ? $requestBid : $sessionBid;
    $enterMap = false;
    $enterbid = false;
    $noncelevel = 0;
    $petsarr = $_pm['user']->getUserPetById($uid);
    $map = $_pm['mem']->get(MEM_MAP_KEY);
    if(!is_array($map)) $map = kdjlSafeMemValue($map, array());
    if (!is_array($map)) $map = array();
    if (!empty($mapid)) {
        $validFuben = false;
        foreach($fbinfo as $fbRow){
            if(is_array($fbRow) && isset($fbRow['id']) && intval($fbRow['id']) == $mapid){
                $validFuben = true;
                break;
            }
        }
        if(!$validFuben) die("0");
        foreach ($map as $v) {
            if (!is_array($v) || !isset($v['id'])) continue;
            if (intval($v['id']) == $mapid) {
                $enterMap = true;
                $noncelevel = isset($v['level']) ? intval($v['level']) : 0;
                break;
            }
        }
        if (!$enterMap) die("0");
    } else {
        die("2");
    }
    if (!empty($bid)) {
        if (is_array($petsarr)) {
            foreach ($petsarr as $k => $rs) // Will filter in muchang pets for current user.
            {
                if (!is_array($rs)) continue;
                if (isset($rs['muchang']) && $rs['muchang'] != 0) continue;
                if (isset($rs['tgflag']) && intval($rs['tgflag']) != 0) continue;
                if (isset($rs['id']) && intval($rs['id']) == $bid) {
                    $enterbid = true;
                    $petLevel = isset($rs['level']) ? intval($rs['level']) : 0;
                    if ($petLevel < $noncelevel) {
                        die("3");
                    }
                    break;
                }
            }
            if (!$enterbid) die("1");
        }
    } else {
        die("2");
    }
    $sql = "SELECT * FROM fuben WHERE uid = {$uid} and inmap = {$mapid}";
    $fbexist = $_pm['mysql']->getOneRecord($sql);
    if (is_array($fbexist)) {
        if (empty($fbexist['gwid'])) {
            $nowtime = time();
            $lttime = isset($fbexist['lttime']) ? intval($fbexist['lttime']) : 0;
            $srctime = isset($fbexist['srctime']) ? intval($fbexist['srctime']) : 0;
            $time = $nowtime - $lttime;//实际间隔时间
            if ($time < $srctime) {
                die("4");
            }
        }
    }
}
die("10");
?>
