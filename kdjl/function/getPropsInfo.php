<?php

//避免出现乱码
require_once "../config/config.game.php";
if(isset($_REQUEST['equip']) && !is_array($_REQUEST['equip']) && $_REQUEST['equip'] == 1)
{
	$id = (isset($_REQUEST['id']) && !is_array($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0;
	if($id < 1)
	{
		die("");
	}
	$equip = new equipment();
	$result[$id] = $equip -> div($id,0,0,1);
	$arr = $result[$id];
	echo $arr;
	unset($id,$arr);
	exit;
}


if (isset($_REQUEST['op']) && !empty($_REQUEST['op'])) // 获得物品ID。
{
	if(is_array($_REQUEST['op']))
	{
		echo '0';
		exit();
	}
	$op = trim($_REQUEST['op']);
	$mempropsname = $_pm['mem']->get('db_propsname');
	if(!is_array($mempropsname)) $mempropsname = kdjlSafeMemValue($mempropsname, array());
	$prs = is_array($mempropsname) && isset($mempropsname[$op]) ? $mempropsname[$op] : '';

    if (is_array($prs)) echo $prs['id'];
    else echo '0';
	exit();
}

$a = new equipment();
$id = (isset($_REQUEST['id']) && !is_array($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0;
if($id < 1)
{
	die("");
}
$bid = (isset($_REQUEST['bid']) && !is_array($_REQUEST['bid'])) ? intval($_REQUEST['bid']) : 0;
$sign = (isset($_REQUEST['sign']) && !is_array($_REQUEST['sign'])) ? intval($_REQUEST['sign']) : 0;
$type = (isset($_REQUEST['type']) && !is_array($_REQUEST['type'])) ? intval($_REQUEST['type']) : 0;
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
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
$stime = get_user_props_generate_time($uid, $id, $type);
$expire = isset($a->expiration) ? intval($a->expiration) : 0;
$expire_str = get_remianing_time_str($expire, $stime);
$tooltip_one = isset($a->tooltip_html_one) ? $a->tooltip_html_one : '';
$tooltip_two = isset($a->tooltip_html_two) ? $a->tooltip_html_two : '';
$ep_base = isset($a->ep_base) ? preg_replace('/[^A-Za-z0-9#]/', '', $a->ep_base) : '';
$html = $tooltip_one . '<font color=' . $ep_base . '>' . $expire_str . '</font><br />' . $tooltip_two;
echo $html;
/* added by Zheng.Ping */
function get_user_props_generate_time($uid, $pid, $type)
{
    $time = 0;
    $dbn  = $GLOBALS['_pm']['mysql'];

    if ($type == 1) {
        $sql = sprintf("SELECT stime FROM userbag WHERE uid=%d AND pid=%d ORDER BY stime DESC,id DESC LIMIT 1", $uid, $pid);
    } elseif ($type == 2) {
        $sql = sprintf("SELECT stime FROM userbag WHERE id=%d AND uid=%d LIMIT 1", $pid, $uid);
    } else {
        return $time; // bag's type is not in our handling scope
    }

    $res  = $dbn->getOneRecord($sql);
    if (is_array($res) && isset($res['stime']) && $res['stime'] > 0) {
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
