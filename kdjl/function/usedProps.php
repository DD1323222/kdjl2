<?php
require_once('../config/config.game.php');
secStart($_pm['mem']);
$csForbiden = array(3160, 3121, 3120, 3119, 3118, 2766, 2763, 2714, 2713, 2712, 2711, 2710, 2709, 2708, 2707, 2706, 2705, 2704, 2703, 2702, 2701, 2700, 2699, 2698, 2697, 2696, 2695, 2694, 2693, 2692, 2691, 2690, 2689, 2688, 2687, 2686, 2685, 2684, 2683, 2682, 2628, 2624, 2623, 2622, 2621, 2620, 2614, 2613, 2612, 2611, 2610, 2609, 2572, 2571, 2570, 2569, 2568, 2567, 2566, 2565, 2564, 2563, 2562, 2560, 2481, 2456, 2413, 2408, 2407, 2406, 2389, 2388, 2387, 2386, 2385, 2313, 2235, 2213, 2207, 2206, 2205, 2204, 2179, 2162, 2147, 2146, 2145, 2144, 2143, 2142, 1972, 1963, 1962, 1961, 1719, 1697, 1696, 1653, 1647, 1574, 1573, 1572, 1571, 1438, 1437, 1424, 1423, 1414, 1326, 1324, 1217, 1163, 1142, 1141, 1137, 1136, 1105, 1104, 914, 913, 912);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if ($uid < 1) die('找不到该物品！');
$user = $_pm['user']->getUserById($uid);
$id = (isset($_REQUEST['id']) && !is_array($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0; // userbag id

// Expired items must be removed before the request takes its usable bag snapshot.
del_bag_expire();

if (isset($_GET['js']) && !is_array($_GET['js']) && isset($_GET['pid']) && !is_array($_GET['pid'])) {
	$__pid = intval($_GET['pid']);
	$sidrow = $_pm['mysql']->getOneRecord('select id from userbag where uid=' . $uid . ' and pid=' . $__pid . ' and sums>0 and zbing=0 and (cantrade IS NULL OR cantrade<>3)');
	if (!$sidrow) {
		 die('缺少魔法石！');
	}
	$id = $sidrow['id'];
}

$bags = $bag = $_pm['user']->getUserBagById($uid);
if ($id < 1 || !is_array($bags)) die('找不到该物品！');
if (lockItem($id) === false) {
	die("已经在处理了");
}

function usedPropsClearEmptyBag($bagId, $uid)
{
	global $_pm;
	$bagId = intval($bagId);
	$uid = intval($uid);
	if($bagId < 1 || $uid < 1) return false;
	return $_pm['mysql']->query("DELETE FROM userbag WHERE id={$bagId} and uid={$uid} and sums<=0 and bsum<=0 and psum<=0 and pyb=0 and zbing=0 and (cantrade IS NULL OR cantrade<>3)");
}

function usedPropsFailWithDbLock($bagId, $message)
{
	global $_pm;
	if(isset($_pm['mysql'])) $_pm['mysql']->query('ROLLBACK');
	if(function_exists('realseLock')) realseLock();
	unLockItem(intval($bagId));
	die($message);
}

function usedPropsFail($bagId, $message)
{
	global $_pm;
	if(isset($_pm['mysql'])) $_pm['mysql']->query('ROLLBACK');
	unLockItem(intval($bagId));
	die($message);
}

function usedPropsQueryOneWithDbLock($bagId, $sql, $message)
{
	global $_pm;
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
		usedPropsFailWithDbLock($bagId, $message);
	}
}

function usedPropsValidRandomRewardList($propsList)
{
	if(!is_array($propsList)) return false;
	$validCount = 0;
	foreach($propsList as $config)
	{
		$config = trim($config);
		if($config === '') continue;
		$parts = explode(':', $config);
		if(count($parts) < 3 || count($parts) > 4) return false;
		foreach($parts as $part)
		{
			if(!ctype_digit(trim($part))) return false;
		}
		if(intval($parts[0]) < 1 || intval($parts[1]) < 1 || intval($parts[2]) < 1) return false;
		$validCount++;
	}
	return $validCount > 0;
}

function usedPropsConsumeAndUpdatePlayer($bagId, $uid, $playerSql, $failMessage)
{
	global $_pm;
	$bagId = intval($bagId);
	$uid = intval($uid);
	if(!$_pm['mysql']->query('START TRANSACTION')) {
		unLockItem($bagId);
		die($failMessage);
	}
	if(!$_pm['mysql']->query("UPDATE userbag SET sums=sums-1 WHERE id={$bagId} and uid={$uid} and sums>0 and zbing=0 and (cantrade IS NULL OR cantrade<>3)") ||
		mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
		$_pm['mysql']->query('ROLLBACK');
		unLockItem($bagId);
		die('找不到该物品！');
	}
	if(!usedPropsClearEmptyBag($bagId, $uid)) {
		$_pm['mysql']->query('ROLLBACK');
		unLockItem($bagId);
		die($failMessage);
	}
	if(!$_pm['mysql']->query($playerSql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
		$_pm['mysql']->query('ROLLBACK');
		unLockItem($bagId);
		die($failMessage);
	}
	if(!$_pm['mysql']->query('COMMIT')) {
		$_pm['mysql']->query('ROLLBACK');
		unLockItem($bagId);
		die($failMessage);
	}
	if(defined('MEM_USER_KEY')) $_pm['mem']->del(MEM_USER_KEY);
	if(defined('MEM_USERBAG_KEY')) $_pm['mem']->del(MEM_USERBAG_KEY);
}


// 整理包裹
//if ($_REQUEST['op'] == 'reset') {
//    echo '整理完成！';
//    unLockItem($id);
//    exit();
//}


$rs = false;
foreach ($bags as $k => $v) {
	if ($v['id'] == $id && $v['uid'] == $uid && $v['sums'] > 0 && $v['zbing'] == 0 && intval($v['cantrade']) != 3) {
		$rs = $v;
		break;
	}
}
// main bb for user.
$bb = $_pm['mysql']->getOneRecord("SELECT * FROM userbb
						  WHERE id={$user['mbid']} and uid={$uid}
						  LIMIT 0,1
						");

if (!is_array($rs)) {
	unLockItem($id);
	die('找不到该物品！');
}

// if is zb,used it!
// if is zb,used it!
if ($rs['varyname'] == 9)
{
	if (is_array($bb)) {
		if ($rs['requires'] != '') {
			$arr = explode(',', $rs['requires']);
			if (is_array($arr)) {
				foreach ($arr as $v) {
					if (!empty($v)) {
						$newarr = explode(":", $v);
						if ($newarr[0] == 'lv') {
							$tlv = $newarr[1];
						} else if ($newarr[0] == 'wx' && !empty($newarr[1])) {
							$twx = $newarr[1];
						}
					}
				}
			}
			if (!empty($twx) && $twx != $bb['wx']) {
				unLockItem($id);
				die('宝宝五行不匹配!');
			} else if (!empty($tlv) && $tlv > $bb['level']) {
				unLockItem($id);
				die('宝宝等级不够!');
			}
			/*$lv  = explode(':', $arr[0]);
            if ($lv[0] == "lv") $tlv = $lv[1];
            else if($lv[0] == "wx") $twx = $lv[1];

            if($arr[1] != '')
            {
                $wx = explode(':', $arr[1]);
                if ($wx[0] == "lv") $tlv = $wx[1];
                else if($wx[0] == "wx") $twx = $wx[1];
            }

            if ($twx!= $bb['wx'] || $tlv>$bb['level']) die('宝宝等级不够或五行不匹配!');*/
		}

		// Ensure props attrib is ok
		if (!isset($rs['postion']) || $rs['postion'] == '') {
			/*$prs = $_pm['mem']->dataGet(array('k'	=>	MEM_PROPS_KEY,
                                     'v'	=>	"if(\$rs['id'] == '{$rs['pid']}') \$ret=\$rs;"
                    ));*/
			$mempropsid = kdjlSafeMemValue($_pm['mem']->get('db_propsid'), array());
			$grs = $mempropsid[$rs['pid']];
			$rs['postion'] = is_array($grs) ? $grs['postion'] : '';
			unset($grs);
		}

		if (strlen($bb['zb']) < 2) {
			$bb['zb'] = $rs['postion'] . ':' . $rs['id'];
		} else {
			if (strstr($bb['zb'], ",")) {
				$zb = explode(',', $bb['zb']); // format: p:id,p:id
				$new = '';
				$rpl = 0;
				foreach ($zb as $k => $v) {
					$arr = explode(':', $v);
					if ($arr[0] == $rs['postion'])
					{
						$new .= ',' . $arr[0] . ':' . $id;
						$oldid = $arr[1];
						$rpl = 1;
					} else $new .= ',' . $v;
				}
				$bb['zb'] = substr($new, 1);
				if (!$rpl) $bb['zb'] .= ',' . $rs['postion'] . ':' . $rs['id'];
			} else {
				$arr = explode(':', $bb['zb']);
				if ($arr[0] == $rs['postion']) // 替换对应装备。
				{
					$oldid = $arr[1];
					$bb['zb'] = $rs['postion'] . ':' . $rs['id'];
				} else $bb['zb'] = $bb['zb'] . ',' . $rs['postion'] . ':' . $rs['id'];
			}
		}

		/**Find current postion zb clear zb tag.*/
		$clearlist = '';
		foreach ($bags as $k => $v) {
			if ($v['postion'] == $rs['postion'] and $v['zbing'] != 0 and $v['zbpets'] != 0 and $v['zbpets'] == $bb['id']) {
				$clearlist .= $clearlist ? ',' . $v['id'] : $v['id'];
			}
		}

		if (!$_pm['mysql']->query('START TRANSACTION')) {
			usedPropsFail($id, '装备失败！');
		}
		$bbZbSql = $_pm['mysql']->escape($bb['zb']);
		if (!$_pm['mysql']->query("UPDATE userbb
					   SET zb='{$bbZbSql}'
					 WHERE id={$user['mbid']} AND uid={$uid}
				  ") || mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
			usedPropsFail($id, '装备失败！');
		}
		if (!empty($clearlist) && $clearlist != '') {
			$clearIds = array();
			foreach (explode(',', $clearlist) as $clearId) {
				$clearId = intval($clearId);
				if ($clearId > 0) $clearIds[] = $clearId;
			}
			$clearlist = implode(',', $clearIds);
			if ($clearlist == '') {
				usedPropsFail($id, '装备失败！');
			}
			if (!$_pm['mysql']->query("UPDATE userbag
						   SET zbing=0,zbpets=0
						 WHERE uid={$uid} and zbpets={$bb['id']} and id in ({$clearlist})
					 ") || mysql_affected_rows($_pm['mysql']->getConn()) != count($clearIds)) {
				usedPropsFail($id, '装备失败！');
			}
		}
		//echo $user['mbid'];exit;

		if (!$_pm['mysql']->query("UPDATE userbag
					   SET zbing=1,zbpets={$user['mbid']}
					 WHERE id={$id} and uid={$uid} and sums>0 and zbing=0 and (cantrade IS NULL OR cantrade<>3)
				  ") || mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
			usedPropsFail($id, '装备失败！');
		}
		if (!$_pm['mysql']->query('COMMIT')) {
			usedPropsFail($id, '装备失败！');
		}
		formatMsgEffect($user['mbid']);
		//设定装备变化标志
		$_pm['mem']->set(array("k" => "User_bb_equip_changed_" . $user['mbid'] . '_' . $_SESSION['id'], "v" => 1));
		//$_SESSION['dbg_equip_attr2'] .= "Right here 2!<br>";
		unLockItem($id);
		die('装备成功！');
	} else {
		unLockItem($id);
		die('请先设置主战宠物！');
	}
}
else if ($rs['varyname'] == 28)    //抽奖卡类
{
	require_once('../sec/dblock_fun.php');
	$key = 'user_chou_' . $uid;
	if (!isset($_SESSION[$key])) {
		$_SESSION[$key] = time();
	} else {
		// sleep(3);
		unset($_SESSION[$key]);
		die('服务器繁忙，请稍候再试！');
	}
	$r = $_pm['mysql']->getOneRecord("SELECT id,sums FROM userbag
										 WHERE id={$id} AND uid={$uid} AND pid=3965 AND sums>0 AND zbing=0
										   AND (cantrade IS NULL OR cantrade<>3)");
	if (!is_array($r) || intval($r['sums']) < 1) {
		//sleep(3);
		unset($_SESSION[$key]);
		die('服务器繁忙，请稍候再试！');
	}
	$url = trim((string)getenv('KDJL_SCRATCH_CARD_URL'));
	$urlInfo = $url === '' ? false : parse_url($url);
	$urlPort = is_array($urlInfo) && isset($urlInfo['port']) ? intval($urlInfo['port']) : 0;
	if($url === '' || !preg_match('#^https?://(?:127\.0\.0\.1|localhost|\[::1\])(?::\d{1,5})?(?:/|$)#i', $url) ||
		(isset($urlInfo['port']) && ($urlPort < 1 || $urlPort > 65535))) {
		unLockItem($id);
		unset($_SESSION[$key]);
		die('幸运刮刮卡奖池未配置，本次未消耗道具。');
	}
	$a = getScopedLock('scratch', $uid, 5);
	if (!$a) {
		unLockItem($id);
		unset($_SESSION[$key]);
		die('服务器繁忙，请稍候再试！');
	}
	require_once('../api/curl.php');
	$httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
	if(!preg_match('/^[A-Za-z0-9.-]{1,255}(:[0-9]{1,5})?$/', $httpHost)) $httpHost = 'localhost';
	$sessionUsername = isset($_SESSION['username']) ? $_SESSION['username'] : '';
	$sessionNickname = isset($_SESSION['nickname']) ? $_SESSION['nickname'] : '';
	$area = explode('.', $httpHost);
	$data['area'] = $area[0];
	$data['username'] = $sessionUsername;
	$data['nickname'] = $sessionNickname;
	$luck_return = curl_post($url, $data);
	if ($luck_return == "no inter") {
		unLockItem($id);
		realseLock();
		unset($_SESSION[$key]);
		die("此平台未加入");
	} elseif ($luck_return == 'today_end') {
		unLockItem($id);
		realseLock();
		unset($_SESSION[$key]);
		die("今日抽奖已经结束");
	} elseif ($luck_return == 'end') {
		unLockItem($id);
		realseLock();
		unset($_SESSION[$key]);
		die("今日抽库已经抽空");
	}
	$return_info = explode('|', $luck_return);
	if (count($return_info) < 4 || $return_info[0] != 'ok') {
		unLockItem($id);
		realseLock();
		unset($_SESSION[$key]);
		die("抽奖错误");
	}
	$level = '未知奖项';
	switch ($return_info[1]) {
		case 1 :
		{
			$level = '特等奖';
			break;
		}
		case 2 :
		{
			$level = '一等奖';
			break;
		}
		case 3 :
		{
			$level = '二等奖';
			break;
		}
		case 4 :
		{
			$level = '三等奖';
			break;
		}
		case 5 :
		{
			$level = '参与奖';
			break;
		}
	}
	if(!$_pm['mysql']->query('START TRANSACTION')) {
		unLockItem($id);
		realseLock();
		unset($_SESSION[$key]);
		die('服务器繁忙，请稍候再试！');
	}
	$sql = "UPDATE userbag SET sums = sums - 1 WHERE uid = {$uid} and id = {$id} and sums>0 and zbing=0 and (cantrade IS NULL OR cantrade<>3)";
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
		$_pm['mysql']->query('ROLLBACK');
		unLockItem($id);
		realseLock();
		unset($_SESSION[$key]);
		die('找不到该物品！');
	}
	if(!usedPropsClearEmptyBag($id, $uid)) {
		$_pm['mysql']->query('ROLLBACK');
		unLockItem($id);
		realseLock();
		unset($_SESSION[$key]);
		die('抽奖数据保存失败！');
	}
	$lucky_draw = new task;
	$rewardSaved = $lucky_draw->saveGetProps($return_info[3]);
	if ($rewardSaved !== true) {
		$_pm['mysql']->query('ROLLBACK');
		unLockItem($id);
		realseLock();
		unset($_SESSION[$key]);
		die('抽奖奖励发放失败！');
	}
	if(!$_pm['mysql']->query('COMMIT')) {
		$_pm['mysql']->query('ROLLBACK');
		unLockItem($id);
		realseLock();
		unset($_SESSION[$key]);
		die('抽奖数据保存失败！');
	}
	if(defined('MEM_USERBAG_KEY')) $_pm['mem']->del(MEM_USERBAG_KEY);
	unLockItem($id);
	realseLock();
	unset($_SESSION[$key]);
	die("抽奖成功,获得" . $level . ",得到物品:" . $return_info[2]);
}
else if ($rs['varyname'] == 13)
{
	if ($rs['pid'] == 1203) {
		if ($user['tgmax'] >= 2) {
			unLockItem($id);
			die('通天塔扩展卡已经使用过了！');
		} else if ($user['tgmax'] == 1) {
			$uid = intval($_SESSION['id']);
			usedPropsConsumeAndUpdatePlayer($id, $uid, "UPDATE player SET tgmax = 2 WHERE id = {$uid} and tgmax = 1", '通天塔扩展卡一使用失败！');
			unLockItem($id);
			die('通天塔扩展卡一使用成功！');
		}
	}
	if ($rs['pid'] == 1204) {
		if ($user['tgmax'] >= 3) {
			unLockItem($id);
			die('通天塔扩展卡已经使用过了！');
		} else if ($user['tgmax'] == 1) {
			unLockItem($id);
			die('请先使用通天塔扩展卡一！');
		} else if ($user['tgmax'] == 2) {
			$uid = intval($_SESSION['id']);
			usedPropsConsumeAndUpdatePlayer($id, $uid, "UPDATE player SET tgmax = 3 WHERE id = {$uid} and tgmax = 2", '通天塔扩展卡二使用失败！');
			unLockItem($id);
			die('通天塔扩展卡二使用成功！');
		}
	}
	$eff = explode(":", $rs['effect']);
	if ($eff[0] == 'zhanshi') {
		if (!isset($eff[1]) || intval($eff[1]) < 1) {
			unLockItem($id);
			die('当前不能使用宠物展示卡！');
		}
		$showNum = intval($eff[1]);
		$uid = intval($_SESSION['id']);
		if(!$_pm['mysql']->query('START TRANSACTION')) {
			unLockItem($id);
			die('当前不能使用宠物展示卡！');
		}
		if(!$_pm['mysql']->query("UPDATE userbag SET sums = sums - 1 WHERE id = {$id} and uid = {$uid} and sums>0 and zbing=0 and (cantrade IS NULL OR cantrade<>3)") ||
			mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
			$_pm['mysql']->query('ROLLBACK');
			unLockItem($id);
			die('找不到该物品！');
		}
		if(!usedPropsClearEmptyBag($id, $uid)) {
			$_pm['mysql']->query('ROLLBACK');
			unLockItem($id);
			die('当前不能使用宠物展示卡！');
		}
		$initialShow = 5 + $showNum;
		if(!$_pm['mysql']->query("INSERT INTO player_ext (uid,bbshow) VALUES ({$uid},{$initialShow}) ON DUPLICATE KEY UPDATE bbshow=COALESCE(bbshow,0)+{$showNum}")) {
			$_pm['mysql']->query('ROLLBACK');
			unLockItem($id);
			die('当前不能使用宠物展示卡！');
		}
		if(!$_pm['mysql']->query('COMMIT')) {
			$_pm['mysql']->query('ROLLBACK');
			unLockItem($id);
			die('当前不能使用宠物展示卡！');
		}
		if(defined('MEM_USERBAG_KEY')) $_pm['mem']->del(MEM_USERBAG_KEY);
		unLockItem($id);
		die("恭喜您使用宠物展示卷成功增加" . $showNum . "次展示机会！");
	} else if ($eff[0] == 'addsj') {
		if(!isset($eff[1])) {
			unLockItem($id);
			die('物品配置错误！');
		}
		$numarr = explode(',', $eff[1]);
		$numMin = isset($numarr[0]) ? intval($numarr[0]) : -1;
		$numMax = isset($numarr[1]) ? intval($numarr[1]) : -1;
		if(count($numarr) < 2 || $numMin < 0 || $numMax < 0 || $numMin > $numMax) {
			unLockItem($id);
			die('物品配置错误！');
		}
		$uid = intval($_SESSION['id']);
		$num = rand($numMin, $numMax);
		if(!$_pm['mysql']->query('START TRANSACTION')) {
			unLockItem($id);
			die('物品使用失败！');
		}
		$_pm['mysql']->query("UPDATE userbag
								  SET sums=sums-1
							 WHERE id={$id} and uid={$uid} and sums>0 and zbing=0
							   and (cantrade IS NULL OR cantrade<>3)
							 ");
		$result = mysql_affected_rows($_pm['mysql']->getConn());
		if ($result != 1) {
			$_pm['mysql']->query('ROLLBACK');
			unLockItem($id);
			die('找不到该物品！');
		}
		if(!usedPropsClearEmptyBag($id, $uid)) {
			$_pm['mysql']->query('ROLLBACK');
			unLockItem($id);
			die('物品使用失败！');
		}
		if(!$_pm['mysql']->query("INSERT INTO player_ext (uid,sj,bbshow) VALUES ({$uid},{$num},5) ON DUPLICATE KEY UPDATE sj=COALESCE(sj,0)+VALUES(sj)")) {
			$_pm['mysql']->query('ROLLBACK');
			unLockItem($id);
			die('物品使用失败！');
		}
		if(!$_pm['mysql']->query('COMMIT')) {
			$_pm['mysql']->query('ROLLBACK');
			unLockItem($id);
			die('物品使用失败！');
		}
		if(defined('MEM_USERBAG_KEY')) $_pm['mem']->del(MEM_USERBAG_KEY);
		unLockItem($id);
		die('获得水晶：' . $num . '个！');
	} else if ($eff[0] == 'addyb') {
		if(!isset($eff[1])) {
			unLockItem($id);
			die('物品配置错误！');
		}
		$numarr = explode(',', $eff[1]);
		$numMin = isset($numarr[0]) ? intval($numarr[0]) : -1;
		$numMax = isset($numarr[1]) ? intval($numarr[1]) : -1;
		if(count($numarr) < 2 || $numMin < 0 || $numMax < 0 || $numMin > $numMax) {
			unLockItem($id);
			die('物品配置错误！');
		}
		$uid = intval($_SESSION['id']);
		$num = rand($numMin, $numMax);
		usedPropsConsumeAndUpdatePlayer($id, $uid, "UPDATE player SET yb = COALESCE(yb,0)+{$num} WHERE id = {$uid}", '物品使用失败！');
		unLockItem($id);
		die('获得元宝：' . $num);
	} else if ($eff[0] == 'addbag') {
		if ($user['maxbag'] < 150) {
			unLockItem($id);
			die('包裹容量必须先达到150格！');
		}
		if ($user['maxbag'] >= 200) {
			unLockItem($id);
			die('包裹容量已经达到上限！');
		}
		$addBag = isset($eff[1]) ? intval($eff[1]) : 0;
		if($addBag < 1) {
			unLockItem($id);
			die('物品配置错误！');
		}
		$uid = intval($_SESSION['id']);
		usedPropsConsumeAndUpdatePlayer($id, $uid, "UPDATE player SET maxbag = LEAST(maxbag + {$addBag}, 200) WHERE id = {$uid} and maxbag >= 150 and maxbag < 200", '扩充包裹失败！');
		unLockItem($id);
		die('包裹容量增加：' . $addBag);
	} else if ($eff[0] == 'addck') {
		if ($user['maxbase'] < 150) {
			unLockItem($id);
			die('仓库容量必须先达到150格！');
		}
		if ($user['maxbase'] >= 200) {
			unLockItem($id);
			die('仓库容量已经达到上限！');
		}
		$addBase = isset($eff[1]) ? intval($eff[1]) : 0;
		if($addBase < 1) {
			unLockItem($id);
			die('物品配置错误！');
		}
		$uid = intval($_SESSION['id']);
		usedPropsConsumeAndUpdatePlayer($id, $uid, "UPDATE player SET maxbase = LEAST(maxbase + {$addBase}, 200) WHERE id = {$uid} and maxbase >= 150 and maxbase < 200", '扩充仓库失败！');
		unLockItem($id);
		die('仓库容量增加：' . $addBase);
	} else if ($eff[0] == 'addbag1') {
		if ($user['maxbag'] < 200) {
			unLockItem($id);
			die('包裹容量必须先达到200格！');
		}
		if ($user['maxbag'] >= 300) {
			unLockItem($id);
			die('包裹容量已经达到上限！');
		}
		$addBag = isset($eff[1]) ? intval($eff[1]) : 0;
		if($addBag < 1) {
			unLockItem($id);
			die('物品配置错误！');
		}
		$uid = intval($_SESSION['id']);
		usedPropsConsumeAndUpdatePlayer($id, $uid, "UPDATE player SET maxbag = LEAST(maxbag + {$addBag}, 300) WHERE id = {$uid} and maxbag >= 200 and maxbag < 300", '扩充包裹失败！');
		unLockItem($id);
		die('包裹容量增加：' . $addBag);
	} else if ($eff[0] == 'addbag2') {
		if ($user['maxbag'] < 300) {
			unLockItem($id);
			die('包裹容量必须先达到300格！');
		}
		if ($user['maxbag'] >= 500) {
			unLockItem($id);
			die('包裹容量已经达到上限！');
		}
		$addBag = isset($eff[1]) ? intval($eff[1]) : 0;
		if($addBag < 1) {
			unLockItem($id);
			die('物品配置错误！');
		}
		$uid = intval($_SESSION['id']);
		usedPropsConsumeAndUpdatePlayer($id, $uid, "UPDATE player SET maxbag = LEAST(maxbag + {$addBag}, 500) WHERE id = {$uid} and maxbag >= 300 and maxbag < 500", '扩充包裹失败！');
		unLockItem($id);
		die('包裹容量增加：' . $addBag);
	} else if ($eff[0] == 'addck1') {
		if ($user['maxbase'] < 200) {
			unLockItem($id);
			die('仓库容量必须先达到200格！');
		}
		if ($user['maxbase'] >= 300) {
			unLockItem($id);
			die('仓库容量已经达到上限！');
		}
		$addBase = isset($eff[1]) ? intval($eff[1]) : 0;
		if($addBase < 1) {
			unLockItem($id);
			die('物品配置错误！');
		}
		$uid = intval($_SESSION['id']);
		usedPropsConsumeAndUpdatePlayer($id, $uid, "UPDATE player SET maxbase = LEAST(maxbase + {$addBase}, 300) WHERE id = {$uid} and maxbase >= 200 and maxbase < 300", '扩充仓库失败！');
		unLockItem($id);
		die('仓库容量增加：' . $addBase);
	}
	if (is_array($eff)) {
		if ($eff[0] == "tuoguan") {
			$uid = intval($_SESSION['id']);
			$tgTime = isset($eff[1]) ? intval($eff[1]) : 0;
			if($tgTime < 1) {
				unLockItem($id);
				die('物品配置错误！');
			}
			usedPropsConsumeAndUpdatePlayer($id, $uid, "UPDATE player SET tgtime = COALESCE(tgtime,0) + {$tgTime} WHERE id = {$uid}", '物品使用失败！');
			unLockItem($id);
			die("使用{$tgTime}小时托管卷成功!");
		}
	}
	$keys = explode(':', $rs['effect']);
	if (isset($keys[0]) && $keys[0] == 'openmap') {
		$mapId = isset($keys[1]) ? intval($keys[1]) : 0;
		if ($mapId < 1) {
			unLockItem($id);
			die('地图开启配置错误！');
		}
		$currentOpenMap = isset($user['openmap']) ? $user['openmap'] : '';
		$item = array_map('trim', explode(',', $currentOpenMap));
		if (in_array((string)$mapId, $item)) {
			unLockItem($id);
			die($rs['name'] . '对应的地图已经打开了!');
		}

		$valid = false;
		foreach ($bags as $k => $v) {
			if ($v['id'] == $id) {
				$valid = true;
				$rs = $v;
				break;
			}
		}
		if (is_array($rs)) {
			$playerSql = "UPDATE player SET openmap=CONCAT_WS(',', NULLIF(TRIM(BOTH ',' FROM COALESCE(openmap,'')), ''), {$mapId})".
				" WHERE id={$uid} AND FIND_IN_SET({$mapId}, REPLACE(COALESCE(openmap,''), ' ', ''))=0";
			usedPropsConsumeAndUpdatePlayer($id, $uid, $playerSql, '开启地图失败！');

			unLockItem($id);
			die("{$rs['name']} 对应地图打开成功!");
		} else {
			unLockItem($id);
			die("地图打开失败，请确认包裹中有打开该地图对应的钥匙!");
		}
	} else if (($rs['pid'] >= 200 && $rs['pid'] <= 202) || $rs['pid'] == 1344) {
		$capacityField = '';
		$capacityLimit = 0;
		$capacityStep = 0;
		$capacityMinimum = 0;
		if ($rs['pid'] == 200) {
			$capacityField = 'maxbase';
			$capacityLimit = 96;
			$capacityStep = 6;
		} else if ($rs['pid'] == 201) {
			$capacityField = 'maxbag';
			$capacityLimit = 96;
			$capacityStep = 6;
		} else if ($rs['pid'] == 202) {
			$capacityField = 'maxmc';
			$capacityLimit = 40;
			$capacityStep = 6;
		} else {
			$capacityField = 'maxmc';
			$capacityLimit = 80;
			$capacityStep = 1;
			$capacityMinimum = 40;
		}
		$currentCapacity = isset($user[$capacityField]) ? intval($user[$capacityField]) : 0;
		if ($currentCapacity < $capacityMinimum) {
			unLockItem($id);
			die("您的牧场格子还没扩展到40格，请先买其它道具扩展到40格才能再用此道具扩展!");
		}
		if ($currentCapacity >= $capacityLimit) {
			unLockItem($id);
			die("已经扩展到极限，如需再扩展请买其它道具!");
		}
		$playerSql = "UPDATE player SET {$capacityField}=LEAST({$capacityField}+{$capacityStep}, {$capacityLimit})".
			" WHERE id={$uid} AND {$capacityField}>={$capacityMinimum} AND {$capacityField}<{$capacityLimit}";
		usedPropsConsumeAndUpdatePlayer($id, $uid, $playerSql, '升级卡使用失败！');
		unLockItem($id);
		die("使用道具 {$rs['name']} 成功!");
	} else if ($rs['pid'] == 1342) {
		if ($user['maxbag'] >= 150) {
			unLockItem($id);
			die("已经扩展到极限，不能再继续扩展了!");
		}
		if ($user['maxbag'] < 96) {
			unLockItem($id);
			die("背包还没扩展到96格，请先用背包升级卷轴扩展到96格!");
		}
		$playerSql = "UPDATE player SET maxbag=LEAST(maxbag+6, 150)".
			" WHERE id={$uid} AND maxbag>=96 AND maxbag<150";
		usedPropsConsumeAndUpdatePlayer($id, $uid, $playerSql, '包裹升级卡使用失败！');
		unLockItem($id);
		die("使用道具 {$rs['name']} 成功!");
	} else if ($rs['pid'] == 1343) {
		if ($user['maxbase'] >= 150) {
			unLockItem($id);
			die("已经扩展到极限，不能再继续扩展了!");
		}
		if ($user['maxbase'] < 96) {
			unLockItem($id);
			die("仓库还没扩展到96格，请先用仓库升级卷轴扩展到96格!");
		}
		$playerSql = "UPDATE player SET maxbase=LEAST(maxbase+6, 150)".
			" WHERE id={$uid} AND maxbase>=96 AND maxbase<150";
		usedPropsConsumeAndUpdatePlayer($id, $uid, $playerSql, '仓库升级卡使用失败！');
		unLockItem($id);
		die("使用道具 {$rs['name']} 成功!");
	} else if (($rs['pid'] >= 742 && $rs['pid'] <= 746) || $rs['pid'] == 1247 || $rs['pid'] == 1225 || $rs['pid'] == 2055) // 经验卷及自动战斗卷. format:
	{
		if ($keys[0] == 'exp')
		{
			$dbl = 0;
			switch ($keys[1]) {
				case 1.5:
					$dbl = 2;
					break;
				case 2:
					$dbl = 3;
					break;
				case 2.5:
					$dbl = 4;
					break;
				case 3:
					$dbl = 5;
					break;
			}
			if ($dbl < 1) {
				unLockItem($id);
				die('经验卡配置错误！');
			}

			if (is_array($rs)) {
				// accumulate remaining double-exp time
				if ($user['dblexpflag'] > 1 && $dbl == $user['dblexpflag']) {
					$other = $user['dblstime'] + $user['maxdblexptime'] - time();
					if ($other <= 0) $other = 0;
					$user['maxdblexptime'] = 3600 + $other;
				} else $user['maxdblexptime'] = 3600;

				$user['dblexpflag'] = $dbl;
				$user['dblstime'] = time();

				$playerSql = "UPDATE player
							   SET maxdblexptime={$user['maxdblexptime']},
								   dblexpflag={$user['dblexpflag']},
								   dblstime={$user['dblstime']}
							 WHERE id={$uid}";
				usedPropsConsumeAndUpdatePlayer($id, $uid, $playerSql, '经验卡使用失败！');
				unLockItem($id);
				die('经验卡使用成功！');
			} else {
				unLockItem($id);
				die('找不到该物品！');
			}
		}
	} // end 双倍卷。
	####################作用自动战斗卷，分为金币版和元宝版9.24谭炜###################

	if ($keys[0] == 'autofree') // 使用金币版自动战斗卷
	{
		if (is_array($rs)) {
			$autoCount = isset($keys[1]) ? intval($keys[1]) : 0;
			if ($autoCount < 1) {
				unLockItem($id);
				die('自动战斗卡配置错误！');
			}
			$user['sysautosum'] += $autoCount;
			$playerSql = "UPDATE player
								 SET sysautosum={$user['sysautosum']}
							 WHERE id={$uid}";
			usedPropsConsumeAndUpdatePlayer($id, $uid, $playerSql, '金币自动战斗卡使用失败！');
			unLockItem($id);
			die('金币自动战斗卡使用成功！');
		}
	} else if ($keys[0] == "auto" || $keys[0] == "autoteam") {
		$autoCount = isset($keys[1]) ? intval($keys[1]) : 0;
		if ($autoCount < 1) {
			unLockItem($id);
			die('自动战斗卡配置错误！');
		}
		if ($keys[0] == "auto") {
			$user['maxautofitsum'] += $autoCount;
			$playerSql = "UPDATE player
									  SET maxautofitsum={$user['maxautofitsum']}
								 WHERE id={$uid}";
			usedPropsConsumeAndUpdatePlayer($id, $uid, $playerSql, '元宝自动战斗卡使用失败！');
			$msg = "使用 {$keys[1]} 次元宝版自动战斗卷成功!";
		} else {
			$playerSql = "UPDATE player_ext
									  SET team_auto_times = COALESCE(team_auto_times,0)+" . $autoCount . " WHERE uid=" . $uid;
			usedPropsConsumeAndUpdatePlayer($id, $uid, $playerSql, '组队自动战斗卡使用失败！');
			$msg = "使用组队自动战斗卷成功,增加 {$keys[1]} 次!";
		}
		unLockItem($id);
		die($msg);
	}
	####################在这里结束###################
	if (isset($keys[0]) && ($keys[0] == 'skillup' || $keys[0] == 'bskillup')) {
		unLockItem($id);
		die('请在宠物资料的技能界面选择技能后使用升级卷轴。');
	}
	if (isset($keys[0]) && $keys[0] == 'vip') {
		unLockItem($id);
		die('VIP卡为背包持有生效，无需直接使用。');
	}
	if (isset($keys[0]) && $keys[0] == 'shuaxin') {
		unLockItem($id);
		die('属性重置功能尚未恢复，本次未消耗道具。');
	}
	unLockItem($id);
	die('该道具无需直接使用或功能尚未开放，本次未消耗道具。');
}
else if ($rs['varyname'] == 4) {
	if ($rs['effect'] != 'ticket') {
		unLockItem($id);
		die('彩票配置错误，本次未消耗道具。');
	}
	if (trim((string)getenv('KDJL_LUCKY_NUMBER_DRAW_ENABLED')) !== '1') {
		unLockItem($id);
		die('本地幸运数字开奖未配置，本次未消耗道具。');
	}
	$activityConfig = $_pm['mysql']->getOneRecord("SELECT value2 FROM welcome WHERE code='ticket' LIMIT 1");
	$timearr = is_array($activityConfig) && isset($activityConfig['value2']) ? explode(':', $activityConfig['value2']) : array();
	if(count($timearr) < 2 || intval($timearr[0]) !== 1) {
		unLockItem($id);
		die('幸运数字活动未开启，本次未消耗道具。');
	}
	$drawHour = intval($timearr[1]);
	if($drawHour < 0 || $drawHour > 23) {
		unLockItem($id);
		die('幸运数字活动时间配置错误，本次未消耗道具。');
	}
	if (intval(date('G')) >= $drawHour) {
		unLockItem($id);
		die('今天已开奖，明天再买吧！');
	}

	$welcome = memContent2Arr('db_welcome', 'code');
	$ticketPrefix = is_array($welcome) && isset($welcome['ticket_num']['contents']) ? trim((string)$welcome['ticket_num']['contents']) : '';
	if($ticketPrefix === '') {
		$prefixRow = $_pm['mysql']->getOneRecord("SELECT contents FROM welcome WHERE code='ticket_num' LIMIT 1");
		$ticketPrefix = is_array($prefixRow) && isset($prefixRow['contents']) ? trim((string)$prefixRow['contents']) : '';
	}
	if(!preg_match('/^\d{1,3}$/D', $ticketPrefix)) {
		unLockItem($id);
		die('幸运数字号码配置错误，本次未消耗道具。');
	}

	$ticketTable = 'ticket_'.date('Ymd');
	$sql = 'CREATE TABLE IF NOT EXISTS '.$ticketTable.' (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `uid` int(11) unsigned DEFAULT "0",
		  `ticket_num` varchar(8) DEFAULT "0" COMMENT "号码",
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `tn` (`ticket_num`),
		  KEY `uid` (`uid`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
	if(!$_pm['mysql']->query($sql)) {
		unLockItem($id);
		die('彩票号码表创建失败，本次未消耗道具。');
	}
	$tableStatus = $_pm['mysql']->getOneRecord("SHOW TABLE STATUS LIKE '{$ticketTable}'");
	if(!is_array($tableStatus) || !isset($tableStatus['Engine'])) {
		unLockItem($id);
		die('彩票号码表状态异常，本次未消耗道具。');
	}
	if(strcasecmp($tableStatus['Engine'], 'InnoDB') !== 0 && !$_pm['mysql']->query("ALTER TABLE {$ticketTable} ENGINE=InnoDB")) {
		unLockItem($id);
		die('彩票号码表升级失败，本次未消耗道具。');
	}

	if(!$_pm['mysql']->query('START TRANSACTION')) {
		unLockItem($id);
		die('服务器繁忙，本次未消耗道具。');
	}
	if(!$_pm['mysql']->query("UPDATE userbag
							  SET sums=sums-1
							WHERE id={$id} AND uid={$uid} AND sums>0 AND zbing=0
							  AND (cantrade IS NULL OR cantrade<>3)") ||
		mysql_affected_rows($_pm['mysql']->getConn()) != 1 ||
		!usedPropsClearEmptyBag($id, $uid)) {
		$_pm['mysql']->query('ROLLBACK');
		unLockItem($id);
		die('找不到该物品！');
	}
	$res = rand_num($ticketPrefix, $ticketTable);
	if ($res === false) {
		$_pm['mysql']->query('ROLLBACK');
		unLockItem($id);
		die('号码生成失败，幸运魔石未消耗，请稍候再试！');
	}
	if(!$_pm['mysql']->query('COMMIT')) {
		$_pm['mysql']->query('ROLLBACK');
		unLockItem($id);
		die('号码保存失败，幸运魔石未消耗，请稍候再试！');
	}
	if(defined('MEM_USERBAG_KEY')) $_pm['mem']->del(MEM_USERBAG_KEY);
	echo '彩票号码：' . $res;
	unLockItem($id);
}
else if ($rs['varyname'] == 12)
{
	/**
	 * Format: randitem:1308:1:80:2|1055:1:70:2|1141:1:80:2|744:1:30:2|211:1:40:1|213:1:40:1|871:1:40:1|870:1:20:1|1207:1:20:1|9:1:5:1|912:1:1:1
	 * @Memo: 1表示获得该道具的时候,会发系统公告(2表示不会发公告)
	 * “[玩家名字]打一枚徽章,或许是踩到了狗屎了,居然获得了E(对应数量)个D(对应的道具名称)”
	 */
	if (!empty($rs['requires'])) {
		$requires = explode(":", $rs['requires']);
		if ($requires[0] == 'lv') {
			if ($bb['level'] < $requires[1]) {
				unLockItem($id);
				die('等级不足！');
			}
		}
	}
	$propsPatter = $rs['effect'];
	$arr = explode(",", $propsPatter);
	$task = new task();
	if (!$_pm['mysql']->query('START TRANSACTION')) {
		unLockItem($id);
		die('服务器繁忙，请稍候再试！');
	}
	$rewardHandled = false;
	foreach ($arr as $v) {
		$newarr = explode(":", $v);
		if ($newarr[0] == "needkey") {
			$keyPid = isset($newarr[1]) ? intval($newarr[1]) : 0;
			$keyRow = $_pm['mysql']->getOneRecord("SELECT id FROM userbag
												 WHERE uid={$_SESSION['id']} AND pid={$keyPid} AND sums>0 AND zbing=0
												   AND (cantrade IS NULL OR cantrade<>3)
												 ORDER BY id LIMIT 1 FOR UPDATE");
			if (!is_array($keyRow)) {
				$_pm['mysql']->query('ROLLBACK');
				unLockItem($id);
				die("您没有开启宝箱的钥匙!");
			}
			$keyUsed = $_pm['mysql']->query("UPDATE userbag SET sums=sums-1
												 WHERE id=".intval($keyRow['id'])." AND uid={$_SESSION['id']}
												   AND pid={$keyPid} AND sums>0 AND zbing=0
												   AND (cantrade IS NULL OR cantrade<>3)");
			if (!$keyUsed || mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
				$_pm['mysql']->query('ROLLBACK');
				unLockItem($id);
				die("您没有开启宝箱的钥匙!");
			}
			if(!usedPropsClearEmptyBag(intval($keyRow['id']), $uid)) {
				$_pm['mysql']->query('ROLLBACK');
				unLockItem($id);
				die('钥匙使用失败！');
			}
		} else if ($newarr[0] == 'giveitems') {

			unset($result);
			$patter = str_replace('giveitems:', '', $rs['effect']);
			$propslist = explode(',', $patter);

			$retstr = '';
			if (is_array($propslist)) {
				$boxUsed = $_pm['mysql']->query("UPDATE userbag
								  SET sums=sums-1
							 WHERE id={$id} and uid={$_SESSION['id']} and sums>0 and zbing=0
							   and (cantrade IS NULL OR cantrade<>3)
							 ");
				$result = mysql_affected_rows($_pm['mysql']->getConn());
				if (!$boxUsed || $result != 1) {
					$_pm['mysql']->query('ROLLBACK');
					unLockItem($id);
					die('找不到该物品！');
				}
				if(!usedPropsClearEmptyBag($id, $uid)) {
					$_pm['mysql']->query('ROLLBACK');
					unLockItem($id);
					die('礼包奖励发放失败！');
				}
				foreach ($propslist as $k => $v) {
					$inarr = explode(':', $v);        //	0=> ID, 2=> rand number, 1=> sum props


					if (is_array($inarr)) {
						if (count($inarr) < 2 || intval($inarr[0]) < 1 || intval($inarr[1]) < 1) {
							continue;
						}
						//foreach($inarr as $inarrs)
						//{
						$prs = getBasePropsInfoById($inarr[0]);
						if(!is_array($prs)) continue;
						$giveResult = $task->saveGetPropsMore($inarr[0], $inarr[1], $rs['pid'],0,$prs);
						if($giveResult !== true){
							$_pm['mysql']->query('ROLLBACK');
							unLockItem($id);
						die($giveResult === '200' ? '包裹已满！' : '礼包奖励发放失败！');
						}

						if (empty($retstr)) {
							$retstr = '获得物品：' . $prs['name'] . '&nbsp;' . $inarr[1] . ' 个';
						} else {
							$retstr .= ',' . $prs['name'] . '&nbsp;' . $inarr[1] . ' 个';
						}
						//}
					}
				} // end foreach
				if ($retstr == '') {
					$_pm['mysql']->query('ROLLBACK');
					unLockItem($id);
					die('礼包配置错误！');
				}
				if (!$_pm['mysql']->query('COMMIT')) {
					$_pm['mysql']->query('ROLLBACK');
					unLockItem($id);
					die('服务器繁忙，请稍候再试！');
				}
				$rewardHandled = true;
				echo $retstr;
			}
		} elseif ($newarr[0] == "randitem") {
			$patter = str_replace('randitem:', '', $v);
			$propslist = explode('|', $patter);
			$retstr = '';
			if(!usedPropsValidRandomRewardList($propslist)) {
				$_pm['mysql']->query('ROLLBACK');
				unLockItem($id);
				die('随机奖励配置错误！');
			}
			if (is_array($propslist)) {
				$boxUsed = $_pm['mysql']->query("UPDATE userbag
								  SET sums=sums-1
							 WHERE id={$id} and uid={$_SESSION['id']} and sums>0 and zbing=0
							   and (cantrade IS NULL OR cantrade<>3)");
				if (!$boxUsed || mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
					$_pm['mysql']->query('ROLLBACK');
					unLockItem($id);
					die('找不到该物品！');
				}
				if(!usedPropsClearEmptyBag($id, $uid)) {
					$_pm['mysql']->query('ROLLBACK');
					unLockItem($id);
					die('随机奖励发放失败！');
				}
				foreach ($propslist as $k => $v) {
					$inarr = explode(':', $v);        //	0=> ID, 2=> rand number, 1=> sum props
					if (count($inarr) < 3 || intval($inarr[0]) < 1 || intval($inarr[1]) < 1 || intval($inarr[2]) < 1) {
						continue;
					}
					if (!isset($inarr[3])) $inarr[3] = 1;
					if (rand(1, intval($inarr[2])) == 1)    //  rand hits
					{
						$prs = getBasePropsInfoById($inarr[0]);
						if(!is_array($prs)) continue;
						$giveResult = $task->saveGetPropsMore($inarr[0], $inarr[1], $rs['pid'],0,$prs);
						if($giveResult !== true){
							$_pm['mysql']->query('ROLLBACK');
							unLockItem($id);
						die($giveResult === '200' ? '包裹已满！' : '随机奖励发放失败！');
						}
						$retstr = '获得物品：' . $prs['name'] . ' ' . $inarr[1] . ' 个';
						if ($inarr[3] == 2) {
						$word = '使用'.$rs['name'].'获得'.$inarr[1].'个'.$prs['name'];
							$task->saveGword($word);
						}

						echo $retstr;
						break;
					}
					} // end foreach
				if (!$_pm['mysql']->query('COMMIT')) {
					$_pm['mysql']->query('ROLLBACK');
					unLockItem($id);
					die('服务器繁忙，请稍候再试！');
				}
				$rewardHandled = true;
				if ($retstr == '') {
					echo '没有获得奖励';
				}
			}
		}
	}
	if (!$rewardHandled) {
		$_pm['mysql']->query('ROLLBACK');
	}
}
else if ($rs['varyname'] == 22)
{

	if (!isset($_GET['js'])) {
		unLockItem($id);
		die('请在占卜屋中使用该物品！');
	}
	/**
	 * Format: randitem:1308:1:80:2|1055:1:70:2|1141:1:80:2|744:1:30:2|211:1:40:1|213:1:40:1|871:1:40:1|870:1:20:1|1207:1:20:1|9:1:5:1|912:1:1:1
	 * @Memo: 1表示获得该道具的时候,会发系统公告(2表示不会发公告)
	 * “[玩家名字]打一枚徽章,或许是踩到了狗屎了,居然获得了E(对应数量)个D(对应的道具名称)”
	 */
	if (!empty($rs['requires'])) {
		$requires = explode(":", $rs['requires']);
		if ($requires[0] == 'lv') {
			if ($bb['level'] < $requires[1]) {
				unLockItem($id);
				die("您没有达到相应的等级，不能进行占卜！");
			}
		}
	}
	$propsPatter = $rs['effect'];
	$arr = explode(",", $propsPatter);
	if (!$_pm['mysql']->query('START TRANSACTION')) {
		unLockItem($id);
		die('服务器繁忙，请稍候再试！');
	}
	$rewardHandled = false;
	foreach ($arr as $v) {
		$newarr = explode(":", $v);
		if ($newarr[0] == "needkey") {
			$keyPid = isset($newarr[1]) ? intval($newarr[1]) : 0;
			$keyRow = $_pm['mysql']->getOneRecord("SELECT id FROM userbag
												 WHERE uid={$_SESSION['id']} AND pid={$keyPid} AND sums>0 AND zbing=0
												   AND (cantrade IS NULL OR cantrade<>3)
												 ORDER BY id LIMIT 1 FOR UPDATE");
			if (!is_array($keyRow)) {
				$_pm['mysql']->query('ROLLBACK');
				unLockItem($id);
				die("您没有占卜的钥匙!");
			}
			$keyUsed = $_pm['mysql']->query("UPDATE userbag SET sums=sums-1
												 WHERE id=".intval($keyRow['id'])." AND uid={$_SESSION['id']}
												   AND pid={$keyPid} AND sums>0 AND zbing=0
												   AND (cantrade IS NULL OR cantrade<>3)");
			if (!$keyUsed || mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
				$_pm['mysql']->query('ROLLBACK');
				unLockItem($id);
				die("您没有占卜的钥匙!");
			}
			if(!usedPropsClearEmptyBag(intval($keyRow['id']), $uid)) {
				$_pm['mysql']->query('ROLLBACK');
				unLockItem($id);
				die('钥匙使用失败！');
			}
		} else if ($newarr[0] == 'giveitems') {

			unset($result);
			$patter = str_replace('giveitems:', '', $rs['effect']);
			$propslist = explode(',', $patter);

			$retstr = '';
			if (is_array($propslist)) {
				$stoneUsed = $_pm['mysql']->query("UPDATE userbag
								  SET sums=sums-1
							 WHERE id={$id} and uid={$_SESSION['id']} and sums>0 and zbing=0
							   and (cantrade IS NULL OR cantrade<>3)
							 ");
				$result = mysql_affected_rows($_pm['mysql']->getConn());
				if (!$stoneUsed || $result != 1) {
					$_pm['mysql']->query('ROLLBACK');
					unLockItem($id);
					die('缺少占卜石！');
				}
				if(!usedPropsClearEmptyBag($id, $uid)) {
					$_pm['mysql']->query('ROLLBACK');
					unLockItem($id);
					die('占卜奖励发放失败！');
				}
				foreach ($propslist as $k => $v) {
					$inarr = explode(':', $v);        //	0=> ID, 2=> rand number, 1=> sum props


					if (is_array($inarr)) {
						if (count($inarr) < 2 || intval($inarr[0]) < 1 || intval($inarr[1]) < 1) {
							continue;
						}
						//foreach($inarr as $inarrs)
						//{
						$task = new task();
						$prs = getBasePropsInfoById($inarr[0]);
						if(!is_array($prs)) continue;
						$giveResult = $task->saveGetPropsMore($inarr[0], $inarr[1], $rs['pid'],0,$prs);
						if($giveResult !== true){
							$_pm['mysql']->query('ROLLBACK');
							unLockItem($id);
						die($giveResult === '200' ? '包裹已满！' : '占卜奖励发放失败！');
						}
						if (empty($retstr)) {
							$retstr = '获得物品：' . $prs['name'] . '&nbsp;' . $inarr[1] . ' 个';
						} else {
							$retstr .= ',' . $prs['name'] . '&nbsp;' . $inarr[1] . ' 个';
						}
						//}
					}
				} // end foreach
				if ($retstr == '') {
					$_pm['mysql']->query('ROLLBACK');
					unLockItem($id);
					die('礼包配置错误！');
				}
				if (!$_pm['mysql']->query('COMMIT')) {
					$_pm['mysql']->query('ROLLBACK');
					unLockItem($id);
					die('服务器繁忙，请稍候再试！');
				}
				$rewardHandled = true;
				echo $retstr;
			}
		} elseif ($newarr[0] == "randitem") {
			$patter = str_replace('randitem:', '', $v);
			$propslist = explode('|', $patter);
			$retstr = '';
			$task = new task();
			if(!usedPropsValidRandomRewardList($propslist)) {
				$_pm['mysql']->query('ROLLBACK');
				unLockItem($id);
				die('随机奖励配置错误！');
			}
			if (is_array($propslist)) {
				$stoneUsed = $_pm['mysql']->query("UPDATE userbag
								  SET sums=sums-1
							 WHERE id={$id} and uid={$_SESSION['id']} and sums>0 and zbing=0
							   and (cantrade IS NULL OR cantrade<>3)");
				if (!$stoneUsed || mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
					$_pm['mysql']->query('ROLLBACK');
					unLockItem($id);
					die('找不到该物品！');
				}
				if(!usedPropsClearEmptyBag($id, $uid)) {
					$_pm['mysql']->query('ROLLBACK');
					unLockItem($id);
					die('占卜奖励发放失败！');
				}
				foreach ($propslist as $k => $v) {
					$inarr = explode(':', $v);        //	0=> ID, 2=> rand number, 1=> sum props
					if (count($inarr) < 3 || intval($inarr[0]) < 1 || intval($inarr[1]) < 1 || intval($inarr[2]) < 1) {
						continue;
					}
					if (!isset($inarr[3])) $inarr[3] = 1;
					if (rand(1, intval($inarr[2])) == 1)    //  rand hits
					{
						$prs = getBasePropsInfoById($inarr[0]);
						if(!is_array($prs)) continue;
						$giveResult = $task->saveGetPropsMore($inarr[0], $inarr[1], $rs['pid'],0,$prs);
						if($giveResult !== true){
							$_pm['mysql']->query('ROLLBACK');
							unLockItem($id);
						die($giveResult === '200' ? '包裹已满！' : '占卜奖励发放失败！');
						}
						$retstr = '获得物品：' . $prs['name'] . ' ' . $inarr[1] . ' 个';

						if ($inarr[3] == 2) {
						$word = '使用'.$rs['name'].'获得'.$inarr[1].'个'.$prs['name'];
							//$word = "由于他（她）虔诚的占卜感动了自然女神，女神将心爱的{$prs['name']}*{$inarr[1]}个赐予了他（她）。";
							$task->saveGword($word);
						}

						echo $retstr;
						break;
					}
					} // end foreach
				if (!$_pm['mysql']->query('COMMIT')) {
					$_pm['mysql']->query('ROLLBACK');
					unLockItem($id);
					die('服务器繁忙，请稍候再试！');
				}
				$rewardHandled = true;
				if ($retstr == '') {
					echo '没有获得奖励';
				}
			}
		}
	}
	if (!$rewardHandled) {
		$_pm['mysql']->query('ROLLBACK');
	}
}
else if ($rs['varyname'] == 2)
{
	require_once('../sec/dblock_fun.php');
	$a = getLock($_SESSION['id']);
	if (!is_array($a)) {
		usedPropsFailWithDbLock($id, '服务器繁忙，请稍候再试！');
	}
	if (!$_pm['mysql']->query("UPDATE userbag
								  SET sums=sums-1
							 WHERE id={$id} and uid={$_SESSION['id']} and sums>0 and zbing=0
							   and (cantrade IS NULL OR cantrade<>3)
							 ")) {
		usedPropsFailWithDbLock($id, '找不到该物品！');
	}
	$result = mysql_affected_rows($_pm['mysql']->getConn());
	if ($result != 1) {
		usedPropsFailWithDbLock($id, '找不到该物品！');
	}
	if(!usedPropsClearEmptyBag($id, $uid)) {
		usedPropsFailWithDbLock($id, '物品使用失败！');
	}
	unset($result);
	$tid = $id == 0 ? $user['mbid'] : $id;
	$bb = $_pm['mysql']->getOneRecord("SELECT wx,name,czl,id
										 FROM userbb
										WHERE id={$user['mbid']} and uid={$uid}");
	if (!is_array($bb)) {
		usedPropsFailWithDbLock($id, '请先设置主战宠物！');
	}
	if ($bb['wx'] == 7 && $rs['requires'] != '__SS__') {
		usedPropsFailWithDbLock($id, '神圣宠物不能使用该物品！');
	}

	if ($bb['wx'] != 7 && $rs['requires'] == '__SS__') {
		usedPropsFailWithDbLock($id, "非神圣宠物无法使用此类物品！");
	}
	$arr = explode(':', $rs['effect']);
	$tips = '';
	$supportedEffects = array('addexp','addczl','addac','addmc','addhits','addmiss','addhp','addspeed','addmp','weiwang','add_cq_czl','add_zc_jifen');
	if (!isset($arr[0]) || !isset($arr[1]) || !in_array($arr[0], $supportedEffects)) {
		usedPropsFailWithDbLock($id, '物品效果配置错误！');
	}
	if ($arr[0] == 'addexp') // 增加经验
	{
		if (!isset($arr[1]) || !preg_match('/^\(([0-9]+),([0-9]+)\)$/', $arr[1], $expRange) || intval($expRange[1]) > intval($expRange[2])) {
			usedPropsFailWithDbLock($id, '经验增加效果配置错误！');
		}
		$exp = rand(intval($expRange[1]), intval($expRange[2]));
		$t = new task();
		$rtn = $t->saveExps($exp);
		if ($rtn === false) {
			usedPropsFailWithDbLock($id, '该宠物当前不能升级！');
		}
		$tips .= '获得经验' . $exp;
	} else if ($arr[0] == "addczl") // 添加成长
	{
		$growthAdd = floatval($arr[1]);
		if ($growthAdd <= 0) {
			usedPropsFailWithDbLock($id, '成长道具配置错误！');
		}
		$cishu = $_pm['mysql']->getOneRecord("select chouqu_chongwu from player_ext where uid={$_SESSION['id']}");
		$chouquChongwu = is_array($cishu) && isset($cishu['chouqu_chongwu']) ? $cishu['chouqu_chongwu'] : '';
		if (strpos($chouquChongwu, ',' . $bb['id'] . ',') !== false) {
			usedPropsFailWithDbLock($id, '该宠物的成长已经抽取过了！');
		}

		if ($bb['wx'] == 7) {
			$bb_settings = $_pm['mysql']->getOneRecord("SELECT max_czl FROM bb,super_jh	WHERE bb.id=super_jh.pet_id and bb.name='" . $bb['name'] . "' limit 1");
			if (!$bb_settings) {
				usedPropsFailWithDbLock($id, '神圣宠物配置不存在！');
			}
			$maxCzl = isset($bb_settings['max_czl']) ? floatval($bb_settings['max_czl']) : 0;
			if ($maxCzl <= 0) {
				usedPropsFailWithDbLock($id, '神圣宠物成长上限配置错误！');
			}

			if ($maxCzl < $bb['czl'] + $growthAdd) {
				$growthAdd = $maxCzl - $bb['czl'];
			}
			if ($growthAdd <= 0) {
				usedPropsFailWithDbLock($id, '宠物成长已经达到上限！');
			}
		}

		if ($user['mbid'] != '' && $user['mbid'] > 0) {
			usedPropsQueryOneWithDbLock($id, "UPDATE userbb
				                         SET czl=czl+{$growthAdd}
									   WHERE id={$user['mbid']} AND uid={$uid}
									", '宠物数据更新失败！');
			$tips .= '主战宠物成长 +' . $growthAdd;
		}
	} else if ($arr[0] == "addac")
	{
		if ($user['mbid'] != '' && $user['mbid'] > 0) {
			$effectValue = floatval($arr[1]);
			if ($effectValue <= 0) {
				usedPropsFailWithDbLock($id, '物品效果配置错误！');
			}
			usedPropsQueryOneWithDbLock($id, "UPDATE userbb
				                         SET ac=ac+{$effectValue}
									   WHERE id={$user['mbid']} AND uid={$uid}
									", '宠物数据更新失败！');
			$tips .= '主战宠物攻击 +' . $effectValue;
		}
	} else if ($arr[0] == "addmc") // 增加防御
	{
		if ($user['mbid'] != '' && $user['mbid'] > 0) {
			$effectValue = floatval($arr[1]);
			if ($effectValue <= 0) {
				usedPropsFailWithDbLock($id, '物品效果配置错误！');
			}
			usedPropsQueryOneWithDbLock($id, "UPDATE userbb
				                         SET mc=mc+{$effectValue}
									   WHERE id={$user['mbid']} AND uid={$uid}
									", '宠物数据更新失败！');
			$tips .= '主战宠物防御 +' . $effectValue;
		}
	} else if ($arr[0] == "addhits") // 增加命中
	{
		if ($user['mbid'] != '' && $user['mbid'] > 0) {
			$effectValue = floatval($arr[1]);
			if ($effectValue <= 0) {
				usedPropsFailWithDbLock($id, '物品效果配置错误！');
			}
			usedPropsQueryOneWithDbLock($id, "UPDATE userbb
				                         SET hits=hits+{$effectValue}
									   WHERE id={$user['mbid']} AND uid={$uid}
									", '宠物数据更新失败！');
			$tips .= '主战宠物命中 +' . $effectValue;
		}
	} else if ($arr[0] == "addmiss") // 增加闪避
	{
		if ($user['mbid'] != '' && $user['mbid'] > 0) {
			$effectValue = floatval($arr[1]);
			if ($effectValue <= 0) {
				usedPropsFailWithDbLock($id, '物品效果配置错误！');
			}
			usedPropsQueryOneWithDbLock($id, "UPDATE userbb
				                         SET miss=miss+{$effectValue}
									   WHERE id={$user['mbid']} AND uid={$uid}
									", '宠物数据更新失败！');
			$tips .= '主战宠物闪避 +' . $effectValue;
		}
	} else if ($arr[0] == "addhp")
	{
		if ($user['mbid'] != '' && $user['mbid'] > 0) {
			$effectValue = floatval($arr[1]);
			if ($effectValue <= 0) {
				usedPropsFailWithDbLock($id, '物品效果配置错误！');
			}
			usedPropsQueryOneWithDbLock($id, "UPDATE userbb
				                         SET srchp=srchp+{$effectValue}
									   WHERE id={$user['mbid']} AND uid={$uid}
									", '宠物数据更新失败！');
			$tips .= '主战宠物生命 +' . $effectValue;
		}
	} else if ($arr[0] == "addspeed")
	{
		if ($user['mbid'] != '' && $user['mbid'] > 0) {
			$effectValue = floatval($arr[1]);
			if ($effectValue <= 0) {
				usedPropsFailWithDbLock($id, '物品效果配置错误！');
			}
			usedPropsQueryOneWithDbLock($id, "UPDATE userbb
				                         SET speed=speed+{$effectValue}
									   WHERE id={$user['mbid']} AND uid={$uid}
									", '宠物数据更新失败！');
			$tips .= '主战宠物速度 +' . $effectValue;
		}
	} else if ($arr[0] == "addmp") // 增加魔力
	{
		if ($user['mbid'] != '' && $user['mbid'] > 0) {
			$effectValue = floatval($arr[1]);
			if ($effectValue <= 0) {
				usedPropsFailWithDbLock($id, '物品效果配置错误！');
			}
			usedPropsQueryOneWithDbLock($id, "UPDATE userbb
				                         SET srcmp=srcmp+{$effectValue}
									   WHERE id={$user['mbid']} AND uid={$uid}
									", '宠物数据更新失败！');
			$tips .= '主战宠物魔法 +' . $effectValue;
		}
	} else if ($arr[0] == "weiwang") // 增加威望
	{
		$effectValue = intval($arr[1]);
		if ($effectValue <= 0) {
			usedPropsFailWithDbLock($id, '物品效果配置错误！');
		}
		usedPropsQueryOneWithDbLock($id, "UPDATE player
				                         SET prestige=COALESCE(prestige,0)+{$effectValue}
									   WHERE id={$_SESSION['id']}
									", '玩家数据更新失败！');
		$tips .= '增加威望' . $arr[1] . '点！';
	} else if ($arr[0] == "add_cq_czl") // 增加抽取的成类
	{
		$effectValue = abs(intval($arr[1]));
		if ($effectValue <= 0) {
			usedPropsFailWithDbLock($id, '物品效果配置错误！');
		}
		$sql = 'update player_ext set czl_ss=COALESCE(czl_ss,0)+' . $effectValue . ' where uid=' . $_SESSION['id'];
		usedPropsQueryOneWithDbLock($id, $sql, '玩家数据更新失败！');
		$tips .= '成长 +' . $arr[1] . ' 点';
	} else if ($arr[0] == "add_zc_jifen") // 增加新战场（女神要塞）获胜积分倍数2010-11-3
	{
		$row = $_pm['mysql']->getOneRecord('select buff_status from player_ext where uid=' . $_SESSION['id']);
		if (!is_array($row)) {
			usedPropsFailWithDbLock($id, '玩家数据更新失败！');
		}
		$buffStatus = is_array($row) && isset($row['buff_status']) ? $row['buff_status'] : '';
		$effectValue = floatval($arr[1]);
		if ($effectValue <= 0) {
			usedPropsFailWithDbLock($id, '物品效果配置错误！');
		}
		$buff = preg_replace("/add_zc_jifen:[^;]+;?/", '', $buffStatus) . 'add_zc_jifen:' . date("Ymd") . ',' . $effectValue . ';';
		$buffSql = $_pm['mysql']->escape($buff);

		$sql = 'update player_ext set buff_status="' . $buffSql . '" where uid=' . $_SESSION['id'];
		if (!$_pm['mysql']->query($sql)) {
			usedPropsFailWithDbLock($id, '玩家数据更新失败！');
		}
		$tips .= '操作成功！';
	}
	if (!$_pm['mysql']->query('COMMIT')) {
		usedPropsFailWithDbLock($id, '物品使用失败！');
	}
	echo $tips;
	realseLock();
	unLockItem($id);
}
else if ($rs['varyname'] == 24) {    // card item
	if (!$_pm['mysql']->query('START TRANSACTION')) {
		unLockItem($id);
		die('卡片使用失败！');
	}
	if(!$_pm['mysql']->query("INSERT INTO player_ext(uid,bbshow) VALUES({$uid},5) ON DUPLICATE KEY UPDATE uid=uid")) {
		usedPropsFail($id, '卡片使用失败！');
	}
	$result_card = $_pm['mysql']->getOneRecord("SELECT F_User_Card_Info,F_Has_Title FROM player_ext WHERE uid={$uid} FOR UPDATE");
	if (!is_array($result_card)) {
		usedPropsFail($id, '卡片使用失败！');
	}
	$cardName = isset($rs['name']) ? strval($rs['name']) : '';
	if ($cardName === '') {
		usedPropsFail($id, '卡片配置错误！');
	}
	$cardInfo = isset($result_card['F_User_Card_Info']) ? $result_card['F_User_Card_Info'] : '';
	$cardParts = $cardInfo === '' ? array() : explode(',', $cardInfo);
	$cardCounts = array();
	$cardOrder = array();
	foreach ($cardParts as $cardPart) {
		if ($cardPart === '') continue;
		$cardArr = explode(':', $cardPart, 2);
		if (count($cardArr) < 2 || $cardArr[0] === '') continue;
		$storedName = $cardArr[0];
		$storedCount = max(0, intval($cardArr[1]));
		if (!isset($cardCounts[$storedName])) {
			$cardCounts[$storedName] = 0;
			$cardOrder[] = $storedName;
		}
		if ($cardCounts[$storedName] > 2147483647 - $storedCount) {
			usedPropsFail($id, '卡片数量超出上限！');
		}
		$cardCounts[$storedName] += $storedCount;
	}
	$cardFound = isset($cardCounts[$cardName]);
	if (!$cardFound) {
		$cardCounts[$cardName] = 0;
		$cardOrder[] = $cardName;
	}
	if ($cardCounts[$cardName] >= 2147483647) {
		usedPropsFail($id, '卡片数量超出上限！');
	}
	$cardCounts[$cardName]++;
	$newParts = array();
	foreach ($cardOrder as $storedName) {
		$newParts[] = $storedName . ':' . $cardCounts[$storedName];
	}

	$ownedTitleIds = array();
	$ownedTitleOrder = array();
	$ownedTitleValue = isset($result_card['F_Has_Title']) ? $result_card['F_Has_Title'] : '';
	foreach (explode(',', $ownedTitleValue) as $ownedTitleId) {
		$ownedTitleId = intval($ownedTitleId);
		if ($ownedTitleId < 1 || isset($ownedTitleIds[$ownedTitleId])) continue;
		$ownedTitleIds[$ownedTitleId] = true;
		$ownedTitleOrder[] = $ownedTitleId;
	}
	$titleRows = $_pm['mysql']->getRecords("SELECT id,F_title_must_card,F_title_Chinese FROM T_Card_to_Title ORDER BY id");
	if (!is_array($titleRows)) {
		usedPropsFail($id, '称号配置读取失败！');
	}
	$newTitleNames = array();
	foreach ($titleRows as $titleRow) {
		$titleId = isset($titleRow['id']) ? intval($titleRow['id']) : 0;
		if ($titleId < 1 || isset($ownedTitleIds[$titleId])) continue;
		$requiredCards = isset($titleRow['F_title_must_card']) ? explode(',', $titleRow['F_title_must_card']) : array();
		$hasRequirement = false;
		$hasAllCards = true;
		foreach ($requiredCards as $requiredCard) {
			$requiredCard = trim($requiredCard);
			if ($requiredCard === '') continue;
			$hasRequirement = true;
			if (!isset($cardCounts[$requiredCard]) || $cardCounts[$requiredCard] < 1) {
				$hasAllCards = false;
				break;
			}
		}
		if (!$hasRequirement || !$hasAllCards) continue;
		$ownedTitleIds[$titleId] = true;
		$ownedTitleOrder[] = $titleId;
		$newTitleNames[] = isset($titleRow['F_title_Chinese']) ? $titleRow['F_title_Chinese'] : '';
	}

	$newCardInfoValue = implode(',', $newParts);
	$newTitleInfoValue = implode(',', $ownedTitleOrder);
	$newCardInfo = $_pm['mysql']->escape($newCardInfoValue);
	$newTitleInfo = $_pm['mysql']->escape($newTitleInfoValue);
	$cardUpdated = $_pm['mysql']->query("UPDATE player_ext SET F_User_Card_Info='{$newCardInfo}',F_Has_Title='{$newTitleInfo}' WHERE uid={$uid}");
	if (!$cardUpdated || mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
		usedPropsFail($id, '卡片使用失败！');
	}
	$storedCardInfo = $_pm['mysql']->getOneRecord("SELECT F_User_Card_Info,F_Has_Title FROM player_ext WHERE uid={$uid}");
	if(!is_array($storedCardInfo) ||
		!isset($storedCardInfo['F_User_Card_Info']) || strval($storedCardInfo['F_User_Card_Info']) !== $newCardInfoValue ||
		!isset($storedCardInfo['F_Has_Title']) || strval($storedCardInfo['F_Has_Title']) !== $newTitleInfoValue) {
		usedPropsFail($id, '卡片保存字段长度不足，请先执行数据库迁移！');
	}
	$itemUsed = $_pm['mysql']->query("UPDATE userbag
						  SET sums=sums-1
						   WHERE id={$rs['id']} and uid={$uid} and sums>0 and zbing=0
						     and (cantrade IS NULL OR cantrade<>3)
						");
	if (!$itemUsed || mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
		usedPropsFail($id, '卡片使用失败！');
	}
	if(!usedPropsClearEmptyBag($id, $uid)) {
		usedPropsFail($id, '卡片使用失败！');
	}
	if (!$_pm['mysql']->query('COMMIT')) {
		usedPropsFail($id, '卡片使用失败！');
	}
	if (defined('MEM_USERBAG_KEY')) $_pm['mem']->del(MEM_USERBAG_KEY);
	echo $cardFound ? '卡片使用成功！' : '新卡片使用成功！';
	if (!empty($newTitleNames)) {
		$task = new task();
		foreach ($newTitleNames as $newTitleName) {
			$task->saveGword('获得新称号：' . $newTitleName);
		}
	}
}
else if ($rs['varyname'] == 16) {    // recipe combine item

	//判断用户包裹是否已满
	$bagNum = 0;

	if (is_array($bags)) {
		foreach ($bags as $x => $y) {
			if ($y['sums'] > 0 and $y['zbing'] == 0) {
				$bagNum++;
			}
		}
	}

	if ($bagNum >= $user['maxbag']) {
		unLockItem($id);
		die('包裹已满，请先清理包裹！');
	}

	$arr = explode(':', $rs['effect'], 2);
	if ($arr[0] == 'hecheng') // 图纸合成 格式：hecheng:(956:10|957:10|958:10|1025:1):1012:1|1013:1
	{
		if (!isset($arr[1])) {
			unLockItem($id);
			die('合成配方配置错误！');
		}
		$rarr = explode('):', $arr[1], 2);
		if (count($rarr) != 2) {
			unLockItem($id);
			die('合成配方配置错误！');
		}

		$require = str_replace('(', '', $rarr[0]);
		$needConfig = array();
		foreach (explode('|', $require) as $v) {
			$t = explode(':', $v);
			if (count($t) != 2 || intval($t[0]) < 1 || intval($t[1]) < 1) {
				unLockItem($id);
				die('合成材料配置错误！');
			}
			$needPid = intval($t[0]);
			$needConfig[$needPid] = isset($needConfig[$needPid])
				? $needConfig[$needPid] + intval($t[1])
				: intval($t[1]);
		}

		$idlist = '';
		foreach (explode('|', $rarr[1]) as $v) {
			$gets = explode(':', $v);
			if (count($gets) != 2 || intval($gets[0]) < 1 || intval($gets[1]) < 1) {
				unLockItem($id);
				die('合成产物配置错误！');
			}
			$getPid = intval($gets[0]);
			$getInfo = $_pm['mysql']->getOneRecord("SELECT id FROM props WHERE id={$getPid}");
			if (!is_array($getInfo)) {
				unLockItem($id);
				die('图纸产物不存在！');
			}
			for ($i = 0; $i < intval($gets[1]); $i++) {
				$idlist .= $idlist == '' ? $getPid : ',' . $getPid;
			}
		}

		require_once('../sec/dblock_fun.php');
		$a = getLock($_SESSION['id']);
		if (!is_array($a)) {
			usedPropsFailWithDbLock($id, '服务器繁忙，请稍候再试！');
		}
		$sysl = $_pm['mysql']->getOneRecord("SELECT id,sums
											 FROM userbag
											WHERE id={$id} AND uid={$_SESSION['id']} AND sums>0 AND zbing=0
											  AND (cantrade IS NULL OR cantrade<>3)
											  FOR UPDATE");
		if (!is_array($sysl)) {
			usedPropsFailWithDbLock($id, '合成材料不足！');
		}

		$deductions = array();
		foreach ($needConfig as $needPid => $needCount) {
			$materialRows = $_pm['mysql']->getRecords("SELECT id,sums
															 FROM userbag
															WHERE pid={$needPid} AND uid={$_SESSION['id']} AND sums>0 AND zbing=0
															  AND (cantrade IS NULL OR cantrade<>3)
														 ORDER BY id
															FOR UPDATE");
			$remaining = $needCount;
			if (is_array($materialRows)) {
				foreach ($materialRows as $materialRow) {
					$take = min($remaining, intval($materialRow['sums']));
					if ($take > 0) {
						$deductions[] = array('id' => intval($materialRow['id']), 'count' => $take);
						$remaining -= $take;
					}
					if ($remaining == 0) break;
				}
			}
			if ($remaining > 0) {
				usedPropsFailWithDbLock($id, '合成材料不足！');
			}
		}

		foreach ($deductions as $deduction) {
			$materialUsed = $_pm['mysql']->query("UPDATE userbag
															 SET sums=sums-{$deduction['count']}
														   WHERE id={$deduction['id']} AND uid={$_SESSION['id']} AND sums>={$deduction['count']} AND zbing=0
														     AND (cantrade IS NULL OR cantrade<>3)");
			if (!$materialUsed || mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
				usedPropsFailWithDbLock($id, '服务器繁忙，请稍候再试！');
			}
			if(!usedPropsClearEmptyBag($deduction['id'], $uid)) {
				usedPropsFailWithDbLock($id, '服务器繁忙，请稍候再试！');
			}
		}

		$recipeUsed = $_pm['mysql']->query("UPDATE userbag
													 SET sums=sums-1
												   WHERE id={$id} AND uid={$_SESSION['id']} AND sums>0 AND zbing=0
												     AND (cantrade IS NULL OR cantrade<>3)");
		if (!$recipeUsed || mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
			usedPropsFailWithDbLock($id, '合成材料不足！');
		}
		if(!usedPropsClearEmptyBag($id, $uid)) {
			usedPropsFailWithDbLock($id, '合成产物发放失败！');
		}

		$tsk = new task();
		$rewardSaved = $tsk->saveGetProps($idlist);
		if ($rewardSaved !== true) {
			usedPropsFailWithDbLock($id, '合成产物发放失败！');
		}
		if (mysql_errno($_pm['mysql']->getConn()) != 0 || !$_pm['mysql']->query('COMMIT')) {
			usedPropsFailWithDbLock($id, '服务器繁忙，请稍候再试！');
		}
		realseLock();
		unLockItem($id);
		die('合成成功！');
	} else if ($arr[0] == 'chongzhu') // 重铸合成 格式：chongzhu:(956|957|958|1025):1012:10|1013:50
	{
		if (!isset($arr[1])) {
			unLockItem($id);
			die('重铸配置错误！');
		}
		$forgeConfig = explode('):', $arr[1], 2);
		if (count($forgeConfig) != 2) {
			unLockItem($id);
			die('重铸配置错误！');
		}

		$candidatePids = array();
		$candidateText = str_replace('(', '', $forgeConfig[0]);
		foreach (explode(',', $candidateText) as $candidatePid) {
			$candidatePid = trim($candidatePid);
			if (!preg_match('/^[0-9]+$/', $candidatePid) || intval($candidatePid) < 1) {
				unLockItem($id);
				die('重铸材料配置错误！');
			}
			$candidatePids[intval($candidatePid)] = intval($candidatePid);
		}

		$rewardThresholds = array();
		foreach (explode('|', $forgeConfig[1]) as $rewardConfig) {
			$rewardParts = explode('-', $rewardConfig);
			if (count($rewardParts) != 2 || intval($rewardParts[0]) < 1 || !is_numeric($rewardParts[1])) {
				unLockItem($id);
				die('重铸产物配置错误！');
			}
			$rewardPid = intval($rewardParts[0]);
			$threshold = intval($rewardParts[1]);
			if ($threshold < 0 || $threshold > 100) {
				unLockItem($id);
				die('重铸概率配置错误！');
			}
			$rewardInfo = $_pm['mysql']->getOneRecord("SELECT id FROM props WHERE id={$rewardPid}");
			if (!is_array($rewardInfo)) {
				unLockItem($id);
				die('重铸奖励不存在！');
			}
			$rewardThresholds[$rewardPid] = $threshold;
		}
		if (empty($candidatePids) || empty($rewardThresholds)) {
			unLockItem($id);
			die('重铸配置错误！');
		}

		$lucky_num = rand(1, 100);
		asort($rewardThresholds, SORT_NUMERIC);
		$get_pid = 0;
		foreach ($rewardThresholds as $rewardPid => $threshold) {
			if ($lucky_num >= $threshold) {
				$get_pid = intval($rewardPid);
			}
		}

		require_once('../sec/dblock_fun.php');
		$a = getLock($_SESSION['id']);
		if (!is_array($a)) {
			usedPropsFailWithDbLock($id, '服务器繁忙，请稍候再试！');
		}

		$recipe = $_pm['mysql']->getOneRecord("SELECT id
															FROM userbag
														 WHERE id={$id} AND uid={$_SESSION['id']} AND sums>0 AND zbing=0
														   AND (cantrade IS NULL OR cantrade<>3)
														   FOR UPDATE");
		if (!is_array($recipe)) {
			usedPropsFailWithDbLock($id, '找不到该物品！');
		}

		$where = 'pid IN(' . implode(',', $candidatePids) . ')';
		$sql = "SELECT id,pid
				  FROM userbag
				 WHERE uid={$_SESSION['id']} AND sums=1 AND zbing=0 AND (cantrade IS NULL OR cantrade<>3) AND {$where}
				 ORDER BY id
				 FOR UPDATE";
		$res = $_pm['mysql']->getRecords($sql);
		if (!is_array($res) || count($res) < 1) {
			usedPropsFailWithDbLock($id, '没有可重铸的物品！');
		}
		shuffle($res);
		$targetId = intval($res[0]['id']);
		$targetPid = intval($res[0]['pid']);

		$recipeUsed = $_pm['mysql']->query("UPDATE userbag
													 SET sums=sums-1
												   WHERE id={$id} AND uid={$_SESSION['id']} AND sums>0 AND zbing=0
												     AND (cantrade IS NULL OR cantrade<>3)");
		if (!$recipeUsed || mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
			usedPropsFailWithDbLock($id, '找不到该物品！');
		}
		if(!usedPropsClearEmptyBag($id, $uid)) {
			usedPropsFailWithDbLock($id, '找不到该物品！');
		}

		$targetUsed = $_pm['mysql']->query("DELETE FROM userbag
													WHERE id={$targetId} AND uid={$_SESSION['id']} AND sums=1 AND zbing=0
													  AND (cantrade IS NULL OR cantrade<>3)");
		if (!$targetUsed || mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
			usedPropsFailWithDbLock($id, '扣除重铸物品失败！');
		}

		$get_name = false;
		$card_task = new task();
		if ($get_pid > 0) {
			$get_name = $_pm['mysql']->getOneRecord("SELECT name,propscolor FROM props WHERE id={$get_pid}");
			if (!is_array($get_name)) {
				usedPropsFailWithDbLock($id, '找不到重铸产物！');
			}
			$user = $_pm['user']->getUserById($uid);
			$bag = $_pm['user']->getUserBagById($uid);
			$rewardSaved = $card_task->saveGetProps($get_pid);
			if ($rewardSaved !== true || mysql_errno($_pm['mysql']->getConn()) != 0) {
				usedPropsFailWithDbLock($id, '重铸产物发放失败！');
			}
		}

		if (!$_pm['mysql']->query('COMMIT')) {
			usedPropsFailWithDbLock($id, '服务器繁忙，请稍候再试！');
		}

		echo '物品使用成功';
		if (is_array($get_name)) {
			if (intval($get_name['propscolor']) == 6) {
				$card_task->saveGword('使用'.$rs['name'].'重铸获得'.$get_name['name']);
			}
			echo '<br>重铸得到' . $get_name['name'];
			$str = '重铸成功,消失userbag表id:' . $targetId . ',props表id:' . $targetPid . ',获得物品:' . $get_name['name'];
		} else {
			$str = '重铸失败,消失userbag表id:' . $targetId . ',props表id:' . $targetPid;
		}
		$logText = $_pm['mysql']->escape($str);
		$_pm['mysql']->query("INSERT INTO gamelog (seller,buyer,ptime,pnote,vary)
											VALUES ({$_SESSION['id']},{$_SESSION['id']}," . time() . ",'{$logText}',177)");
		realseLock();
		unLockItem($id);
	} else if ($arr[0] == 'random_combine') {
		if (!isset($arr[1])) {
			unLockItem($id);
			die('随机合成配置错误！');
		}
		$settings_of_gain = explode(';', $arr[1]);
		if (count($settings_of_gain) != 2) {
			unLockItem($id);
			die('随机合成配置错误！');
		}

		$needConfig = array();
		foreach (explode('|', $settings_of_gain[0]) as $idx => $it_need) {
			$it_need_setting = explode(',', $it_need);
			if (count($it_need_setting) != 2 || !preg_match('/^[0-9]+$/', $it_need_setting[0])
				|| !preg_match('/^[0-9]+$/', $it_need_setting[1]) || intval($it_need_setting[0]) < 1
				|| intval($it_need_setting[1]) < 1) {
				unLockItem($id);
				die('所需物品配置错误，位置：' . $idx);
			}
			$needPid = intval($it_need_setting[0]);
			$needConfig[$needPid] = isset($needConfig[$needPid])
				? $needConfig[$needPid] + intval($it_need_setting[1])
				: intval($it_need_setting[1]);
		}

		$gainConfigs = array();
		$gainPids = array();
		foreach (explode('|', $settings_of_gain[1]) as $idx => $it_gain) {
			$it_gain_setting = explode(',', $it_gain);
			if (count($it_gain_setting) != 4
				|| !preg_match('/^[0-9]+$/', $it_gain_setting[0])
				|| !preg_match('/^[0-9]+$/', $it_gain_setting[1])
				|| !preg_match('/^[0-9]+$/', $it_gain_setting[2])
				|| !preg_match('/^[0-9]+$/', $it_gain_setting[3])) {
				unLockItem($id);
				die('产物配置错误，位置：' . $idx);
			}
			$gainPid = intval($it_gain_setting[0]);
			$gainChance = intval($it_gain_setting[1]);
			$gainCount = intval($it_gain_setting[2]);
			$gainNotice = intval($it_gain_setting[3]);
			if ($gainPid < 1 || $gainChance > 100 || $gainCount < 1) {
				unLockItem($id);
				die('产物配置错误，位置：' . $idx);
			}
			$gainConfigs[] = array(
				'pid' => $gainPid,
				'chance' => $gainChance,
				'count' => $gainCount,
				'notice' => $gainNotice
			);
			$gainPids[$gainPid] = $gainPid;
		}

		$gainNames = array();
		if (!empty($gainPids)) {
			$gainRows = $_pm['mysql']->getRecords('SELECT id,name FROM props WHERE id IN(' . implode(',', $gainPids) . ')');
			if (is_array($gainRows)) {
				foreach ($gainRows as $gainRow) {
					$gainNames[intval($gainRow['id'])] = $gainRow['name'];
				}
			}
		}
		if (count($gainNames) != count($gainPids)) {
			unLockItem($id);
			die('找不到随机合成产物！');
		}

		require_once('../sec/dblock_fun.php');
		$a = getLock($_SESSION['id']);
		if (!is_array($a)) {
			usedPropsFailWithDbLock($id, '服务器繁忙，请稍候再试！');
		}

		$sysl = $_pm['mysql']->getOneRecord("SELECT id,sums
											 FROM userbag
											WHERE id={$id} AND uid={$_SESSION['id']} AND sums>0 AND zbing=0
											  AND (cantrade IS NULL OR cantrade<>3)
											  FOR UPDATE");
		if (!is_array($sysl)) {
			usedPropsFailWithDbLock($id, '合成材料不足！');
		}

		$deductions = array();
		foreach ($needConfig as $needPid => $needCount) {
			$materialRows = $_pm['mysql']->getRecords("SELECT id,sums
															 FROM userbag
															WHERE pid={$needPid} AND uid={$_SESSION['id']} AND sums>0 AND zbing=0
															  AND (cantrade IS NULL OR cantrade<>3)
														 ORDER BY id
															FOR UPDATE");
			$remaining = $needCount;
			if (is_array($materialRows)) {
				foreach ($materialRows as $materialRow) {
					$take = min($remaining, intval($materialRow['sums']));
					if ($take > 0) {
						$deductions[] = array('id' => intval($materialRow['id']), 'count' => $take);
						$remaining -= $take;
					}
					if ($remaining == 0) break;
				}
			}
			if ($remaining > 0) {
				usedPropsFailWithDbLock($id, '所需物品数量不足！');
			}
		}

		foreach ($deductions as $deduction) {
			$materialUsed = $_pm['mysql']->query("UPDATE userbag
															 SET sums=sums-{$deduction['count']}
														   WHERE id={$deduction['id']} AND uid={$_SESSION['id']} AND sums>={$deduction['count']} AND zbing=0
														     AND (cantrade IS NULL OR cantrade<>3)");
			if (!$materialUsed || mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
				usedPropsFailWithDbLock($id, '服务器繁忙，请稍候再试！');
			}
			if(!usedPropsClearEmptyBag($deduction['id'], $uid)) {
				usedPropsFailWithDbLock($id, '服务器繁忙，请稍候再试！');
			}
		}

		$recipeUsed = $_pm['mysql']->query("UPDATE userbag
													 SET sums=sums-1
												   WHERE id={$id} AND uid={$_SESSION['id']} AND sums>0 AND zbing=0
												     AND (cantrade IS NULL OR cantrade<>3)");
		if (!$recipeUsed || mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
			usedPropsFailWithDbLock($id, '合成材料不足！');
		}
		if(!usedPropsClearEmptyBag($id, $uid)) {
			usedPropsFailWithDbLock($id, '随机合成产物发放失败！');
		}

		$selectedGain = false;
		foreach ($gainConfigs as $gainConfig) {
			if (rand(1, 100) <= $gainConfig['chance']) {
				$selectedGain = $gainConfig;
				break;
			}
		}

		$tsk = new task();
		if (is_array($selectedGain)) {
			$giveResult = $tsk->saveGetPropsMore($selectedGain['pid'], $selectedGain['count']);
			if ($giveResult !== true) {
				usedPropsFailWithDbLock($id, $giveResult === '200' ? '包裹已满！' : '随机合成产物发放失败！');
			}
		}

		if (!$_pm['mysql']->query('COMMIT')) {
			usedPropsFailWithDbLock($id, '服务器繁忙，请稍候再试！');
			$_pm['mysql']->query('ROLLBACK');
			unLockItem($id);
			die('服务器繁忙，请稍候再试！');
		}

		if (is_array($selectedGain)) {
			$msg = '合成成功：' . $gainNames[$selectedGain['pid']] . ' x' . $selectedGain['count'] . '<br/>';
			if ($selectedGain['notice'] > 0) {
				$tsk->saveGword('合成成功：' . $gainNames[$selectedGain['pid']] . ' x' . $selectedGain['count']);
			}
		} else {
			$msg = '合成失败，没有获得物品。';
		}
		realseLock();
		unLockItem($id);
		die($msg);
	}
}
else if ($rs['varyname'] == 15) {    // pet card item
	$petConfig = explode(':', $rs['effect']);
	if (count($petConfig) != 2 || $petConfig[0] != 'openpet' || intval($petConfig[1]) < 1) {
		unLockItem($id);
		die('宠物卡配置错误！');
	}
	$newpetsid = intval($petConfig[1]);

	$bb = $_pm['mem']->dataGet(array('k' => MEM_BB_KEY,
		'v' => "if(\$rs['id'] == '{$newpetsid}') \$ret=\$rs;"
	));
	if (!is_array($bb) || intval($bb['id']) < 1 || trim($bb['name']) == '' || trim($bb['name']) == '0'
		|| intval($bb['wx']) < 1) {
		unLockItem($id);
		die('宠物数据缺失或不完整！');
	}

	$czl = getCzl($bb['czl']);
	if ($czl === false || $czl <= 0) {
		unLockItem($id);
		die('宠物成长配置错误！');
	}

	$initialSkills = array();
	$skillList = isset($bb['skillist']) ? trim($bb['skillist']) : '';
	if ($skillList != '' && $skillList != '0') {
		foreach (explode(',', $skillList) as $skillConfig) {
			if ($skillConfig === '0' || $skillConfig === '') continue;
			$skillParts = explode(':', $skillConfig);
			if (count($skillParts) != 2 || !preg_match('/^[0-9]+$/', $skillParts[0])
				|| !preg_match('/^[0-9]+$/', $skillParts[1]) || intval($skillParts[0]) < 1
				|| intval($skillParts[1]) < 1) {
				unLockItem($id);
				die('宠物初始技能配置错误！');
			}

			$skillId = intval($skillParts[0]);
			$skillLevel = intval($skillParts[1]);
			$jn = $_pm['mem']->dataGet(array('k' => MEM_SKILLSYS_KEY,
				'v' => "if(\$rs['id'] == '{$skillId}') \$ret=\$rs;"
			));
			if (!is_array($jn) || intval($jn['id']) != $skillId) {
				unLockItem($id);
				die('找不到宠物初始技能！');
			}

			$skillIndex = $skillLevel - 1;
			$ackValues = explode(',', isset($jn['ackvalue']) ? $jn['ackvalue'] : '');
			$plusValues = explode(',', isset($jn['plus']) ? $jn['plus'] : '');
			$uhpValues = explode(',', isset($jn['uhp']) ? $jn['uhp'] : '');
			$umpValues = explode(',', isset($jn['ump']) ? $jn['ump'] : '');
			$imgValues = explode(',', isset($jn['imgeft']) ? $jn['imgeft'] : '');
			if (!isset($ackValues[$skillIndex]) || !isset($uhpValues[$skillIndex]) || !isset($umpValues[$skillIndex])) {
				unLockItem($id);
				die('宠物初始技能等级配置错误！');
			}

			$initialSkills[] = array(
				'id' => $skillId,
				'name' => isset($jn['name']) ? $jn['name'] : '',
				'level' => $skillLevel,
				'vary' => isset($jn['vary']) ? $jn['vary'] : '',
				'wx' => isset($jn['wx']) ? intval($jn['wx']) : 0,
				'value' => $ackValues[$skillIndex],
				'plus' => isset($plusValues[$skillIndex]) ? $plusValues[$skillIndex] : '',
				'img' => isset($imgValues[$skillIndex]) ? $imgValues[$skillIndex] : '',
				'uhp' => intval($uhpValues[$skillIndex]),
				'ump' => intval($umpValues[$skillIndex])
			);
		}
	}

	require_once('../sec/dblock_fun.php');
	$a = getLock($_SESSION['id']);
	if (!is_array($a)) {
		usedPropsFailWithDbLock($id, '服务器繁忙，请稍候再试！');
	}

	$carriedPets = $_pm['mysql']->getRecords("SELECT id
															 FROM userbb
															WHERE uid={$_SESSION['id']} AND muchang=0
															  FOR UPDATE");
	if ($carriedPets === false && mysql_errno($_pm['mysql']->getConn()) != 0) {
		usedPropsFailWithDbLock($id, '服务器繁忙，请稍候再试！');
	}
	$carriedCount = is_array($carriedPets) ? count($carriedPets) : 0;
	if ($carriedCount >= 3) {
		usedPropsFailWithDbLock($id, '您最多只能携带3只宠物！');
	}

	$eggRow = $_pm['mysql']->getOneRecord("SELECT id
														FROM userbag
													 WHERE id={$id} AND uid={$_SESSION['id']} AND sums>0 AND zbing=0
													   AND (cantrade IS NULL OR cantrade<>3)
													   FOR UPDATE");
	if (!is_array($eggRow)) {
		usedPropsFailWithDbLock($id, '找不到该物品！');
	}
	$eggUsed = $_pm['mysql']->query("UPDATE userbag
													 SET sums=sums-1
												   WHERE id={$id} AND uid={$_SESSION['id']} AND sums>0 AND zbing=0
												     AND (cantrade IS NULL OR cantrade<>3)");
	if (!$eggUsed || mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
		usedPropsFailWithDbLock($id, '找不到该物品！');
	}
	if(!usedPropsClearEmptyBag($id, $uid)) {
		usedPropsFailWithDbLock($id, '宠物生成失败，道具未扣除！');
	}

	$uinfo = $user;
	$petName = $_pm['mysql']->escape($bb['name']);
	$petUsername = $_pm['mysql']->escape(isset($uinfo['nickname']) ? $uinfo['nickname'] : (isset($_SESSION['username']) ? $_SESSION['username'] : ''));
	$skillListSql = $_pm['mysql']->escape($skillList);
	$remakeLevel = $_pm['mysql']->escape(isset($bb['remakelevel']) ? $bb['remakelevel'] : '');
	$remakeId = $_pm['mysql']->escape(isset($bb['remakeid']) ? $bb['remakeid'] : '');
	$remakePid = $_pm['mysql']->escape(isset($bb['remakepid']) ? $bb['remakepid'] : '');
	$petAc = $_pm['mysql']->escape(isset($bb['ac']) ? $bb['ac'] : '0');
	$petMc = $_pm['mysql']->escape(isset($bb['mc']) ? $bb['mc'] : '0');
	$petHp = $_pm['mysql']->escape(isset($bb['hp']) ? $bb['hp'] : '0');
	$petMp = $_pm['mysql']->escape(isset($bb['mp']) ? $bb['mp'] : '0');
	$petNowExp = $_pm['mysql']->escape(isset($bb['nowexp']) ? $bb['nowexp'] : '0');
	$petImgStand = $_pm['mysql']->escape(isset($bb['imgstand']) ? $bb['imgstand'] : '');
	$petImgAck = $_pm['mysql']->escape(isset($bb['imgack']) ? $bb['imgack'] : '');
	$petImgDie = $_pm['mysql']->escape(isset($bb['imgdie']) ? $bb['imgdie'] : '');
	$petHits = $_pm['mysql']->escape(isset($bb['hits']) ? $bb['hits'] : '0');
	$petMiss = $_pm['mysql']->escape(isset($bb['miss']) ? $bb['miss'] : '0');
	$petSpeed = $_pm['mysql']->escape(isset($bb['speed']) ? $bb['speed'] : '0');
	$petKx = $_pm['mysql']->escape(isset($bb['kx']) ? $bb['kx'] : '0');

	$petInserted = $_pm['mysql']->query("INSERT INTO userbb(
									   name,
									   uid,
									   username,
									   level,
									   wx,
									   ac,
									   mc,
									   srchp,
									   hp,
									   srcmp,
									   mp,
									   skillist,
									   stime,
									   nowexp,
									   lexp,
									   imgstand,
									   imgack,
									   imgdie,
									   hits,
									   miss,
									   speed,
									   kx,
									   remakelevel,
									   remakeid,
									   remakepid,
									   muchang,
									   czl,
									   headimg,
									   cardimg,
									   effectimg,
									   old_bid
									  )
					VALUES(
					   '{$petName}',
					   '{$_SESSION['id']}',
					   '{$petUsername}',
					   '1',
					   '" . intval($bb['wx']) . "',
					   '{$petAc}',
					   '{$petMc}',
					   '{$petHp}',
					   '{$petHp}',
					   '{$petMp}',
					   '{$petMp}',
					   '{$skillListSql}',
					   unix_timestamp(),
					   '{$petNowExp}',
					   '100',
					   '{$petImgStand}',
					   '{$petImgAck}',
					   '{$petImgDie}',
					   '{$petHits}',
					   '{$petMiss}',
					   '{$petSpeed}',
					   '{$petKx}',
					   '{$remakeLevel}',
					   '{$remakeId}',
					   '{$remakePid}',
					   '0',
					   '" . floatval($czl) . "',
					   't{$bb['id']}.gif',
						   'k{$bb['id']}.gif',
						   'q{$bb['id']}.gif',
						   '" . intval($bb['id']) . "'
						   )
				  ");
	if (!$petInserted || mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
		usedPropsFailWithDbLock($id, '宠物生成失败，道具未扣除！');
	}
	$bbid = $_pm['mysql']->last_id();
	if ($bbid < 1) {
		usedPropsFailWithDbLock($id, '宠物生成失败，道具未扣除！');
	}

	foreach ($initialSkills as $initialSkill) {
		$skillName = $_pm['mysql']->escape($initialSkill['name']);
		$skillVary = $_pm['mysql']->escape($initialSkill['vary']);
		$skillValue = $_pm['mysql']->escape($initialSkill['value']);
		$skillPlus = $_pm['mysql']->escape($initialSkill['plus']);
		$skillImg = $_pm['mysql']->escape($initialSkill['img']);
		$skillInserted = $_pm['mysql']->query("INSERT INTO skill(bid,name,level,vary,wx,value,plus,img,uhp,ump,sid)
						VALUES(
							   '{$bbid}',
							   '{$skillName}',
							   '{$initialSkill['level']}',
							   '{$skillVary}',
							   '{$initialSkill['wx']}',
							   '{$skillValue}',
							   '{$skillPlus}',
							   '{$skillImg}',
							   '{$initialSkill['uhp']}',
							   '{$initialSkill['ump']}',
							   '{$initialSkill['id']}'
							  )
					  ");
		if (!$skillInserted || mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
			usedPropsFailWithDbLock($id, '宠物技能生成失败，道具未扣除！');
		}
	}

	if (!$_pm['mysql']->query('COMMIT')) {
		usedPropsFailWithDbLock($id, '服务器繁忙，请稍候再试！');
	}
	realseLock();
	echo '物品使用成功！';
}
else if ($rs['varyname'] == 14) // 军功令，换取军功
{
	require_once('../sec/dblock_fun.php');
	$a = getLock($_SESSION['id']);
	if (!is_array($a)) {
		usedPropsFailWithDbLock($id, '服务器繁忙，请稍候再试！');
	}
	$arr = explode(':', $rs['effect']);
	if ($arr[0] == "jg" && isset($arr[1]) && intval($arr[1]) > 0) {
		$jgValue = intval($arr[1]);
		$sql = "SELECT jgvalue FROM battlefield_user WHERE uid = {$_SESSION['id']} FOR UPDATE";
		$row = $_pm['mysql']->getOneRecord($sql);
		if (!is_array($row)) {
			usedPropsFailWithDbLock($id, '当前不在战场活动中！');
		}
		$itemUsed = $_pm['mysql']->query("UPDATE userbag
									 SET sums=sums-1
								   WHERE id={$id} AND uid={$_SESSION['id']} AND sums>0 AND zbing=0
								     AND (cantrade IS NULL OR cantrade<>3)");
		if (!$itemUsed || mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
			usedPropsFailWithDbLock($id, '找不到该物品！');
		}
		if(!usedPropsClearEmptyBag($id, $uid)) {
			usedPropsFailWithDbLock($id, '服务器繁忙，请稍候再试！');
		}
		$meritUpdated = $_pm['mysql']->query("UPDATE battlefield_user
		                         SET jgvalue=COALESCE(jgvalue,0)+{$jgValue}
							   WHERE uid={$_SESSION['id']}
							");
		if (!$meritUpdated || mysql_affected_rows($_pm['mysql']->getConn()) != 1 || !$_pm['mysql']->query('COMMIT')) {
			usedPropsFailWithDbLock($id, '服务器繁忙，请稍候再试！');
		}
			realseLock();
			echo '物品使用成功，功勋 +' . $jgValue;
	} else {
		usedPropsFailWithDbLock($id, '物品使用失败！');
	}

}
else if ($rs['varyname'] == 55) {    // talent wash item
	$arr = explode(':', $rs['effect']);
	if ($arr[0] == "xidian" && isset($arr[1]) && intval($arr[1]) > 0) {
		$washCount = intval($arr[1]);
		if (!$_pm['mysql']->query('START TRANSACTION')) {
			usedPropsFail($id, '服务器繁忙，请稍候再试！');
		}
		$sql = "SELECT id FROM war_player WHERE id = {$_SESSION['id']} FOR UPDATE";
		$row = $_pm['mysql']->getOneRecord($sql);
		$itemUsed = $_pm['mysql']->query("UPDATE userbag
									 SET sums=sums-1
								   WHERE id={$id} AND uid={$_SESSION['id']} AND sums>0 AND zbing=0
								     AND (cantrade IS NULL OR cantrade<>3)");
		if (!$itemUsed || mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
			usedPropsFail($id, '找不到该物品！');
		}
		if(!usedPropsClearEmptyBag($id, $uid)) {
			usedPropsFail($id, '服务器繁忙，请稍候再试！');
		}
		if (!is_array($row)) {
			$washUpdated = $_pm['mysql']->query("INSERT INTO war_player (`id`, wash_talent_count) VALUES
														({$_SESSION['id']}, {$washCount})");
		} else {
			$washUpdated = $_pm['mysql']->query("UPDATE war_player
								 SET wash_talent_count=COALESCE(wash_talent_count,0)+{$washCount}
							   WHERE id={$_SESSION['id']}");
		}
		if (!$washUpdated || mysql_affected_rows($_pm['mysql']->getConn()) != 1 || !$_pm['mysql']->query('COMMIT')) {
			usedPropsFail($id, '服务器繁忙，请稍候再试！');
		}
			echo '物品使用成功，洗点次数 +' . $washCount;
	} else {
		echo '物品使用失败！';
	}
}
else if ($rs['varyname'] == 57) {    // carry pet limit item
	$arr = explode(':', $rs['effect']);

	if (
		isset($arr[0]) && in_array($arr[0], array('xiedaibb21', 'xiedaibb31', 'xiedaibb20', 'xiedaibb30'))

	) {
		$xdnum = 1;
		$xdtime = 0;
		$now = time();
		switch ($arr[0]) {
			case 'xiedaibb21':
				$xdnum = 2;
				$xdtime = $now + 3600 * 24 * 30;
				break;
			case 'xiedaibb31':
				$xdnum = 3;
				$xdtime = $now + 3600 * 24 * 30;
				break;
			case 'xiedaibb20':
				$xdnum = 2;
				$xdtime = 0;
				break;
			case 'xiedaibb30':
				$xdnum = 3;
				$xdtime = 0;
				break;
		}

		$xdtimestr = $xdtime == 0 ? '永久' : date('Y/m/d H:i', $xdtime);
		if (!$_pm['mysql']->query('START TRANSACTION')) {
			usedPropsFail($id, '服务器繁忙，请稍候再试！');
		}

		$sql = "SELECT max_take_pet_num_save,max_take_pet_num,take_pet_limit_time
				  FROM war_player
				 WHERE id={$_SESSION['id']}
				 FOR UPDATE";
		$row = $_pm['mysql']->getOneRecord($sql);
		if ($row === false && mysql_errno($_pm['mysql']->getConn()) != 0) {
			usedPropsFail($id, '服务器繁忙，请稍候再试！');
		}

		if (is_array($row) && $row['take_pet_limit_time'] < $now && $row['take_pet_limit_time'] > 0) {
			$row['take_pet_limit_time'] = 0;
			$row['max_take_pet_num'] = $row['max_take_pet_num_save'];
		}

		if (is_array($row) && $row['max_take_pet_num'] > 2 && $row['take_pet_limit_time'] == 0) {
			usedPropsFail($id, '可携带宠物数量已经达到上限！');
		}

		if (is_array($row) && $row['take_pet_limit_time'] > $now && !isset($_GET['cofxiedaibb'])) {
			usedPropsFail($id, '当前可携带宠物数量：' . $row['max_take_pet_num'] . '，到期时间：' . date("Y/m/d H:i", $row['take_pet_limit_time']) . '<br/><font color="#f00">继续使用将覆盖当前状态。</font><br/>确定请点击<a href="javascript:bid=\'' . $id . '&cofxiedaibb=1\';Used();setTimeout(\'bid=' . $id . '\',500);this.style.display=\'none\';void(0);"><strong>继续</strong></a>。');
		}

		$itemUsed = $_pm['mysql']->query("UPDATE userbag
										 SET sums=sums-1
									   WHERE id={$id} AND uid={$_SESSION['id']} AND sums>0 AND zbing=0
									     AND (cantrade IS NULL OR cantrade<>3)");
		if (!$itemUsed || mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
			usedPropsFail($id, '找不到该物品！');
		}
		if(!usedPropsClearEmptyBag($id, $uid)) {
			usedPropsFail($id, '服务器繁忙，请稍候再试！');
		}

		if (!is_array($row)) {
			$row_czl = $_pm['mysql']->getOneRecord("SELECT czl,wx
											  FROM userbb
											 WHERE uid={$_SESSION['id']}
										  ORDER BY czl DESC
											 LIMIT 1");
			if ($row_czl === false && mysql_errno($_pm['mysql']->getConn()) != 0) {
				$limitUpdated = false;
			} else {
				$growUp = is_array($row_czl) ? intval($row_czl['czl']) : 0;
				$wuxing = is_array($row_czl) ? intval($row_czl['wx']) : 1;
				$savedNum = $xdtime == 0 ? $xdnum : 1;
				$username = $_pm['mysql']->escape(isset($_SESSION['username']) ? $_SESSION['username'] : '');
				$limitUpdated = $_pm['mysql']->query("INSERT INTO war_player
															 (`id`,name,max_take_pet_num,max_take_pet_num_save,take_pet_limit_time,grow_up,wuxing)
													 VALUES ({$_SESSION['id']},'{$username}',{$xdnum},{$savedNum},{$xdtime},{$growUp},{$wuxing})");
			}
		} else {
			if ($xdtime == 0) {
				$savedNum = $xdnum;
			} else {
				$savedNum = $row['take_pet_limit_time'] > $now
					? intval($row['max_take_pet_num_save'])
					: intval($row['max_take_pet_num']);
			}
			$limitUpdated = $_pm['mysql']->query("UPDATE war_player
															 SET max_take_pet_num_save={$savedNum},
																 max_take_pet_num={$xdnum},
																 take_pet_limit_time={$xdtime}
														   WHERE id={$_SESSION['id']}");
		}

		if ($limitUpdated && $_pm['mysql']->query('COMMIT')) {
			echo '物品使用成功，可携带宠物数量变更为：' . $xdnum . '，有效时间至：' . $xdtimestr;
		} else {
			usedPropsFail($id, '物品使用失败，道具数量未减少！');
		}

	} else {
		echo '物品使用失败！';
	}
}
else if ($rs['varyname'] == 58) {    // talent exp item
	if (!is_array($bb)) {
		unLockItem($id);
		die('请先设置主战宠物！');
	}

	$arr = explode(':', $rs['effect']);
	if (
		in_array($arr[0], array('tianfuexp')) && count($arr) == 2

	) {
		$exp = explode(',', $arr[1]);
		if (count($exp) == 2) {
			$expMin = intval($exp[0]);
			$expMax = intval($exp[1]);
			if ($expMin < 1 || $expMin > $expMax) {
				unLockItem($id);
				die('天赋经验道具配置错误：' . $arr[1]);
			}
			$expGet = (int)rand($expMin, $expMax);
		} else {
			$expGet = intval($exp[0]);
			if($expGet < 1)
			{
				unLockItem($id);
				die('天赋经验道具配置错误：' . $arr[1]);
			}
		}

		if (!$_pm['mysql']->query("START TRANSACTION")) {
			usedPropsFail($id, '查询数据失败！');
		}
		$sql = 'select
					id,current_experience
				from
					war_fighter_talent
				where
					fighter_id=' . intval($bb['id']) . ' for update';

		$ts = $_pm['mysql']->getRecords($sql);


		if (empty($ts) || !is_array($ts)) {
			usedPropsFail($id, '没有找到天赋数据！');
		}

		if ($err = mysql_error($_pm['mysql']->getConn())) {
			usedPropsFail($id, '查询数据失败！');
		}

		$expGetAver = ceil($expGet / count($ts));

		$itemUsed = $_pm['mysql']->query("UPDATE userbag
										 SET sums=sums-1
									   WHERE id={$id} AND uid={$_SESSION['id']} AND sums>0 AND zbing=0
									     AND (cantrade IS NULL OR cantrade<>3)");
		if (!$itemUsed || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
		{
			usedPropsFail($id, '找不到该物品！');
		}
		if(!usedPropsClearEmptyBag($id, $uid)) {
			usedPropsFail($id, '查询数据失败！');
		}

		$updateOk = true;
		foreach ($ts as $row) {
			$newTalentExp = kdjlSafeNonNegativeSum(isset($row['current_experience']) ? $row['current_experience'] : 0, $expGetAver);
			if($newTalentExp === false)
			{
				$updateOk = false;
				break;
			}
			$sql = 'update war_fighter_talent set current_experience=' . $newTalentExp . ' where id=' . intval($row['id']);
			if (!$_pm['mysql']->query($sql))
			{
				$updateOk = false;
				break;
			}
		}

		if ($updateOk && $_pm['mysql']->query("COMMIT")) {
			echo '物品使用成功，天赋经验 +' . $expGet;
		} else {
			usedPropsFail($id, '物品使用失败，道具数量未减少！');
		}
	} else {
		echo '物品使用失败，物品数据错误！';
	}
}

function rand_num($config, $ticketTable='')
{
	global $_pm;
	$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
	if ($uid < 1 || !preg_match('/^ticket_\d{8}$/D', $ticketTable)) return false;
	for ($attempt = 0; $attempt < 100; $attempt++) {
		$rand = rand(10000, 99999);
		$num = $config . $rand;
		$inserted = $_pm['mysql']->query('INSERT INTO '.$ticketTable.' SET uid='.$uid.',ticket_num="'.$num.'"');
		if ($inserted && mysql_affected_rows($_pm['mysql']->getConn()) == 1) {
			return $num;
		}
		if (mysql_errno($_pm['mysql']->getConn()) != 1062) {
			return false;
		}
	}
	return false;
}

unLockItem($id);
$_pm['mem']->memClose();
unset($m, $u, $db, $user, $bags, $rs);
?>
