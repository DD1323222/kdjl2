<?php
session_start();
if (empty($_SESSION['id'])) {
    header("Location: ../passport/login.php");
    die();
}

require_once('../config/config.game.php');
@header('Content-Type:text/html;charset=utf-8');
@header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
@header("Pragma: no-cache");

if (empty($_SESSION['username'])) {
    die("<script type='text/javascript'>window.location='../passport/login.php';</script>");
}
$db=new mysql();
$usernameSql = $db->escape($_SESSION['username']);
$sessionId = intval($_SESSION['id']);
$rs = $db->getOneRecord("SELECT name,lastvtime FROM player WHERE id={$sessionId} AND name='{$usernameSql}' AND name<>'' LIMIT 0,1");
if (is_array($rs))
{
    require_once("../function/loginGate.php");
//        //转区
//        $displaySwapZone = true;
//        if (file_exists(dirname(__FILE__) . "/../function/swap_Zone.php")) {
//            require_once("../function/swap_Zone.php");
//        }

    $uIP = get_real_ip();
    $time = time();
    $_SESSION['vip']=0;
    $sql = "select merge,now_Achievement_title from player_ext where uid = {$sessionId}";
    $arr_merge =$db->getOneRecord($sql);
    if (is_array($arr_merge) && intval($arr_merge['merge']) > 0) {
        $_SESSION['vip'] = 2;
        $sql = "select nickname from player where id = {$arr_merge['merge']}";
        $arr_info=$_pm['mysql']->getOneRecord($sql);
        $_SESSION['mergename'] = is_array($arr_info) ? $arr_info['nickname'] : '';
    }
    $arr = array("1427", "1474", "1475", "1476", "1477", "1478", "1479", "1480", "1481", "1482", "1483", "1484", "1485");
    $arrayid = date('n');
    if ($arrayid == '1') {
        $arraycode = array("1427", $arr[$arrayid], $arr[12]);
    } else {
        $arrayidjian = $arrayid - 1;
        $arraycode = array("1427", $arr[$arrayidjian], $arr[$arrayid]);
    }

    $u_bags = getUserBagByIds($_SESSION['id'], $arraycode, $_pm['mysql']); /* 口袋精灵VIP卡:1427 */

    // $u_bags=getUserBagById($_SESSION['id'], 1427, $_pm['mysql']); /* 口袋精灵VIP卡:1427 */
    if (is_array($u_bags)) {
        foreach ($u_bags as $v) {
            if ($v && isset($v['sums']) && $v['sums'] > 0) {
                if($_SESSION['vip']==2){
                    $_SESSION['vip']++;
                }else{
                    $_SESSION['vip'] =1;
                }
                break;
            }
        }
    }


    $uIPSql = $db->escape($uIP);
    $sql = "INSERT INTO logins (uname,uIP,times) VALUES ('{$usernameSql}','{$uIPSql}',{$time})";
    $_pm['mysql']->query($sql);
    ###########################################################################################
    $uid = $sessionId;
    $row = $_pm['mysql']->getOneRecord('select logintime,onlinetime from player_ext where uid=' . $uid);
    if (!is_array($row)) {
        $_pm['mysql']->query('insert into player_ext(uid,bbshow,onlinetime,logintime) values(' . $uid . ',5,0,' . $time . ')');
    } else {
        $lastdotime = intval($_pm['mem']->get('last_do_' . $uid));
        $lastvisttime = intval($_pm['mem']->get('last_visit_' . $uid));
        $stime = max(0, $lastvisttime - $lastdotime) + intval($row['onlinetime']);
        if ($lastdotime > 0 && $lastvisttime > 0) {
            $_pm['mysql']->query("UPDATE player_ext SET onlinetime = $stime WHERE uid = $uid");
        }
    }

    $_pm['mem']->set(array('k' => 'last_visit_' . $uid, 'v' => "$time"));
    $_pm['mem']->set(array('k' => 'last_do_' . $uid, 'v' => "$time"));
    $_pm['mem']->set(array('k' => 'friend_visit_' . $uid, 'v' => "$time"));
    echo "<script type='text/javascript'>window.location.href='/index.php';</script>";
} else {
    echo "<script type='text/javascript'>window.location.href='reg1.php';</script>";
}


function get_real_ip()
{
    $ip = false;

    if (!empty($_SERVER["HTTP_CLIENT_IP"]) && filter_var($_SERVER["HTTP_CLIENT_IP"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $ip = $_SERVER["HTTP_CLIENT_IP"];
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(",", $_SERVER['HTTP_X_FORWARDED_FOR']);
        if ($ip) {
            array_unshift($ips, $ip);
            $ip = FALSE;
        }
        for ($i = 0; $i < count($ips); $i++) {
            $ipCandidate = trim($ips[$i]);
            if (filter_var($ipCandidate, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && !preg_match("/^(10|172\.(1[6-9]|2[0-9]|3[0-1])|192\.168)\./i", $ipCandidate)) {
                $ip = $ipCandidate;
                break;
            }
        }
    }
    $remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    return ($ip ? $ip : (filter_var($remoteAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? $remoteAddr : ''));
}


?>
