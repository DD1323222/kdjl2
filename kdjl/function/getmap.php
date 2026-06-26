<?php
require_once('../config/config.game.php');
secStart($_pm['mem']);
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

if ($_GET['action'] == 'schallenge') {
    $memgpc = unserialize($_pm['mem']->get('db_gpcid'));
    $gpccolor = array(5 => '白', 6 => '黄', 7 => '蓝', 8 => '紫', 9 => '红');
    $carr = $_pm['mysql']->getOneRecord("SELECT snums FROM challenge WHERE uid = {$_SESSION['id']}");
    //最大打30分钟
    $time = time();
    if (empty($carr)) {
        die("100");
    } else {
        if ((2 - $carr['snums']) > 0) {
            $_pm['mysql']->query("DELETE FROM challenge_log WHERE uid = {$_SESSION['id']}");
            $garr = getGpc(5);
            $vary = $garr['boss'];
            $glist = explode(',', $garr['gpc']);
            $totalnums=count($glist);
            foreach ($glist as $v) {
                $_pm['mysql']->query("INSERT INTO challenge_log (uid,gid) VALUES({$_SESSION['id']},$v)");
            }
            if ($_pm['mysql']->query("UPDATE challenge SET lastvtime = $time,gid = {$glist[0]},vary = $vary,snums = snums+1,flag = 0,totalnums=$totalnums WHERE uid = {$_SESSION['id']}")) {
                die('1');
            }
        } else {
            die('102');
        }
    }
}
elseif ($_GET['action'] == 'sjschallenge') {
    $memgpc = unserialize($_pm['mem']->get('db_gpcid'));
    $gpccolor = array(5 => '白', 6 => '黄', 7 => '蓝', 8 => '紫', 9 => '红');
    $carr = $_pm['mysql']->getOneRecord("SELECT nums,totalnums FROM challenge WHERE uid = {$_SESSION['id']}");
    $time = time();
    if (empty($carr)) {
        die("100");
    } else {
        $arr = $_pm['mysql']->getOneRecord("SELECT sj FROM player_ext WHERE uid = {$_SESSION['id']}");
        if (is_array($arr)) {
            $user['sj'] = $arr['sj'];
        } else {
            $user['sj'] = 0;
            die("200");
        }
        $nowcoin = $user['sj'];
        if (10 > $nowcoin) {
            die("200");
        } else {
            $user['sj'] = $nowcoin - 10;
            $tzlog= $_pm['mysql']->getOneRecord("SELECT count(*) FROM challenge_log WHERE uid = {$_SESSION['id']}");
            if($tzlog['count(*)'] != $carr['totalnums']){
                $nums  =  $carr['nums']+1;
            }else{
                $nums  = $carr['nums'];
            }
            $_pm['mysql']->query("DELETE FROM challenge_log WHERE uid = {$_SESSION['id']}");
            $_pm['mysql']->query("update player_ext set sj={$user['sj']} where uid={$_SESSION['id']}");
            $garr = getGpc(5);
            $vary = $garr['boss'];
            $glist = explode(',', $garr['gpc']);
            foreach ($glist as $v) {
                $_pm['mysql']->query("INSERT INTO challenge_log (uid,gid) VALUES({$_SESSION['id']},$v)");
            }
            $totalnums=count($glist);
            if ($_pm['mysql']->query("UPDATE challenge SET lastvtime = $time,gid = {$glist[0]},vary = $vary,nums=$nums,flag = 0,totalnums=$totalnums WHERE uid = {$_SESSION['id']}")) die('1');
        }
    }
} elseif ($_GET['action'] == 'gomap' && $_GET['type'] == 'do') {
    $arr = $_pm['mysql']->getOneRecord("SELECT sj FROM player_ext WHERE uid = {$_SESSION['id']}");
    if (is_array($arr)) {
        $user['sj'] = $arr['sj'];
    } else {
        $user['sj'] = 0;
        die("b");
    }
    $nowcoin = $user['sj'];
    if (50 > $nowcoin) {
        die("b");
    } else {
        $user['sj'] = $nowcoin - 50;
        $_pm['mysql']->query("update player_ext set sj={$user['sj']} where uid={$_SESSION['id']}");
        $time=time();
        if ($_pm['mysql']->query("UPDATE challenge SET lastvtime = $time,nums=nums+1,flag = 1 WHERE uid = {$_SESSION['id']}")) die("2");
    }
    die("100");
} elseif ($_GET['action'] == 'gomap') {
    $carr = $_pm['mysql']->getOneRecord("SELECT nums FROM challenge WHERE uid = {$_SESSION['id']}");
    if ((3 - $carr['nums']) > 0) {
        $time = time();
        if ($_pm['mysql']->query("UPDATE challenge SET lastvtime = $time,flag = 1 WHERE uid = {$_SESSION['id']}")) die('2');
    } else {
        die("a");
    }
} else {
    $mapid = intval($_REQUEST['mapid']);
    $bid = intval($_REQUEST['bid']) > 0 ? intval($_REQUEST['bid']) : intval($_SESSION["fight"]["bid"]);
    $petsarr = $_pm['user']->getUserPetById($_SESSION['id']);
    $map = unserialize($_pm['mem']->get(MEM_MAP_KEY));
    if (!empty($mapid)) {
        foreach ($map as $v) {
            if ($v['id'] == $mapid) {
                $enterMap = true;
                $noncelevel = $v['level'];
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
                if ($rs['muchang'] != 0) continue;
                if ($rs['id'] == $bid) {
                    $enterbid = true;
                    if ($rs['level'] < $noncelevel) {
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
    $sql = "SELECT * FROM fuben WHERE uid = {$_SESSION['id']} and inmap = {$mapid}";
    $fbexist = $_pm['mysql']->getOneRecord($sql);
    if (is_array($fbexist)) {
        if (empty($fbexist['gwid'])) {
            $nowtime = time();
            $time = $nowtime - $fbexist['lttime'];//实际间隔时间
            if ($time < $fbexist['srctime']) {
                die("4");
            }
        }
    }
}
die("10");
?>
