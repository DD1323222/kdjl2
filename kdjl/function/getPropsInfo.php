<?php

//避免出现乱码
require_once "../config/config.game.php";
if($_REQUEST['equip'] == 1)
{
	$id = intval($_REQUEST['id']);
	$equip = new equipment();
	$_pm['mem']->del('db_equip');
	$result[$id] = $equip -> div($id,0,0,1);
	$arr = $result[$id];
	echo $arr;
	unset($id,$arr);
	exit;
}


if (isset($_REQUEST['op']) && !empty($_REQUEST['op'])) // 获得物品ID。
{
	$_REQUEST['op']=addslashes($_REQUEST['op']);
	$mempropsname = unserialize($_pm['mem']->get('db_propsname'));
	$prs = $mempropsname[$_REQUEST['op']];

    if (is_array($prs)) echo $prs['id'];
    else echo '0';
	exit();
}

$a = new equipment();
$id = intval($_REQUEST['id']);
if($id < 1)
{
	die("");
}
$bid = intval($_REQUEST['bid']);
$sign = intval($_REQUEST['sign']);
$type = intval($_REQUEST['type']);
if($type == 0)
{
	$type = 1;
}
if($bid < 0)
{
	die("");
}
$props_html = $a -> div($id,$bid,$sign,$type); // added by Zheng.Ping
/* added by Zheng.Ping */
$stime = get_user_props_generate_time(intval($_SESSION['id']), $id, $type);
$expire = $a->expiration;
$expire_str = get_remianing_time_str($expire, $stime);
$html = $a->tooltip_html_one . '<font color=' . $a->ep_base . '>' . $expire_str . '</font><br />' . $a->tooltip_html_two;
echo $html;
/* added by Zheng.Ping */
function get_user_props_generate_time($uid, $pid, $type)
{
    $time = 0;
    $dbn  = $GLOBALS['_pm']['mysql'];

    if ($type == 1) {
        $sql = sprintf("SELECT stime FROM userbag WHERE uid=%d AND pid=%d", $uid, $pid);
    } elseif ($type == 2) {
        $sql = sprintf("SELECT stime FROM userbag WHERE id=%d", $pid);
    } else {
        return $time; // bag's type is not in our handling scope
    }

    $res  = $dbn->getOneRecord($sql);
    if (is_array($res) && $res['stime'] > 0) {
        $time = $res['stime'];
    }

    return $time;
}


function get_remianing_time_str($expire, $get_time)
{
    $ret = '过期';

    if ($expire == 0) {
        $ret = '永久';
    } elseif ($expire > 0) {
        $now = time();
        $end = $get_time + $expire;
        if ($end > $now) {
            $distance = $end - $now;
            $hour     = floor($distance / 3600);
            $minute   = round($distance % 3600 / 60);

            $ret = '到期时间:'.date('Y-m-d H:i',$end);
        } else {
            $ret = '过期';
        }
    }

    return $ret;
}
?>
