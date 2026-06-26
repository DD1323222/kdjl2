<?php
require_once('../config/config.game.php');
secStart($_pm['mem']);
$csForbiden = array(3160, 3121, 3120, 3119, 3118, 2766, 2763, 2714, 2713, 2712, 2711, 2710, 2709, 2708, 2707, 2706, 2705, 2704, 2703, 2702, 2701, 2700, 2699, 2698, 2697, 2696, 2695, 2694, 2693, 2692, 2691, 2690, 2689, 2688, 2687, 2686, 2685, 2684, 2683, 2682, 2628, 2624, 2623, 2622, 2621, 2620, 2614, 2613, 2612, 2611, 2610, 2609, 2572, 2571, 2570, 2569, 2568, 2567, 2566, 2565, 2564, 2563, 2562, 2560, 2481, 2456, 2413, 2408, 2407, 2406, 2389, 2388, 2387, 2386, 2385, 2313, 2235, 2213, 2207, 2206, 2205, 2204, 2179, 2162, 2147, 2146, 2145, 2144, 2143, 2142, 1972, 1963, 1962, 1961, 1719, 1697, 1696, 1653, 1647, 1574, 1573, 1572, 1571, 1438, 1437, 1424, 1423, 1414, 1326, 1324, 1217, 1163, 1142, 1141, 1137, 1136, 1105, 1104, 914, 913, 912);
$user = $_pm['user']->getUserById($_SESSION['id']);
$bags = $bag = $_pm['user']->getUserBagById($_SESSION['id']);
$id = intval($_REQUEST['id']); // userbag id

if (isset($_GET['js']) && isset($_GET['pid'])) {
	$__pid = intval($_GET['pid']);
	$sidrow = $_pm['mysql']->getOneRecord('select id from userbag where uid=' . $_SESSION['id'] . ' and pid=' . $__pid . ' and sums>0');
	if (!$sidrow) {
		die('无相关魔法石，无法满足释放魔法需要的魔力T_T下次再来吧。');
	}
	$id = $sidrow['id'];
}

if ($id < 1 || !is_array($bags)) die('物品不存在!');
del_bag_expire();
if (lockItem($id) === false) {
	unLockItem($id);
	die("已经在处理了");
}


// 整理包裹
//if ($_REQUEST['op'] == 'reset') {
//    echo '整理完成！';
//    unLockItem($id);
//    exit();
//}


foreach ($bags as $k => $v) {
	if ($v['id'] == $id && $v['uid'] == $_SESSION['id'] && $v['sums'] > 0 && $v['zbing'] == 0) {
		$rs = $v;
		break;
	}
}
// main bb for user.
$bb = $_pm['mysql']->getOneRecord("SELECT * FROM userbb
						  WHERE id={$user['mbid']} and uid={$_SESSION['id']} 
						  LIMIT 0,1
						");

if (!is_array($rs)) {
	unLockItem($id);
	die("没有发现相关物品！");
}

// if is zb,used it!
// if is zb,used it!
if ($rs['varyname'] == 9)    //装备系统。
{
	if (is_array($bb)) {
		// Check 是否符合宝宝要求。
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
			$mempropsid = unserialize($_pm['mem']->get('db_propsid'));
			$grs = $mempropsid[$rs['pid']];
			$rs['postion'] = $prs['postion']; // Fix postion.
			unset($prs);
		}

		if (strlen($bb['zb']) < 2) {
			$bb['zb'] = $rs['postion'] . ':' . $rs['id'];
		} else {
			if (strstr($bb['zb'], ",")) {
				$zb = split(',', $bb['zb']); // format: p:id,p:id
				$new = '';
				$rpl = 0;
				foreach ($zb as $k => $v) {
					$arr = explode(':', $v);
					if ($arr[0] == $rs['postion']) // 替换对应装备。
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
					$bb['zb'] = $arr[0] . ':' . $rs['id'];
					$oldid = $arr[1];
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


		$_pm['mysql']->query("UPDATE userbb 
					   SET zb='{$bb['zb']}'
					 WHERE id={$user['mbid']}
				  ");
		if (!empty($clearlist) && $clearlist != '') {
			$_pm['mysql']->query("UPDATE userbag 
						   SET zbing=0,zbpets=0 
						 WHERE id in ({$clearlist})
					 ");
		}
		//echo $user['mbid'];exit;

		$_pm['mysql']->query("UPDATE userbag 
					   SET zbing=1,zbpets={$user['mbid']}
					 WHERE id={$id}
				  ");
		formatMsgEffect($user['mbid']);
		$memeffect = unserialize($_pm['mem']->get('format_user_zhuangbei_' . $user['mbid']));
		//echo $memeffect.'----->'.__LINE__.'<br />';
		//设定装备变化标志
		$_pm['mem']->set(array("k" => "User_bb_equip_changed_" . $user['mbid'] . '_' . $_SESSION['id'], "v" => 1));
		//$_SESSION['dbg_equip_attr2'] .= "Right here 2!<br>";
		unLockItem($id);
		die('恭喜您，装备成功！');
	} else {
		unLockItem($id);
		die('您还没有设置主战宝宝，不能进行装备！');
	}
}
else if ($rs['varyname'] == 28)    //抽奖卡类
{
	//session锁
	$key = 'user_chou_' . $_SESSION['id'];
	if (!isset($_SESSION[$key])) {
		$_SESSION[$key] = time();
	} else {
		// sleep(3);
		realseLock();
		unset($_SESSION[$key]);
		die('服务器繁忙，请稍候再试！');
	}
	$r = $_pm['mysql']->getOneRecord("SELECT sums FROM userbag WHERE uid = " . $_SESSION['id'] . " AND pid = 3965 ");
	if ($r['sums'] < 1) {
		//sleep(3);
		realseLock();
		unset($_SESSION[$key]);
		die('服务器繁忙，请稍候再试！');
	}
	require_once('../sec/dblock_fun.php');
	$a = getLock($_SESSION['id']);
	if (!is_array($a)) {
		realseLock();
		die('服务器繁忙，请稍候再试！');
	}//echo __LINE__."<br>";
	require_once('../api/curl.php');
	$url = "http://pmmg1.webgame.com.cn/interface/use_ticket.php";
	$area = explode('.', $_SERVER['HTTP_HOST']);
	$data['area'] = $area[0];
	$data['username'] = $_SESSION['username'];
	$data['nickname'] = $_SESSION['nickname'];
	$luck_return = curl_post($url, $data);
	if ($luck_return == "no inter") {
		die("此平台未加入");
	} elseif ($luck_return == 'today_end') {
		die("今日抽奖已经结束");
	} elseif ($luck_return == 'end') {
		die("今日抽库已经抽空");
	}
	$return_info = explode('|', $luck_return);
	if ($return_info[0] != 'ok') {
		die("抽奖错误");
	}
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
	$user = $_pm['user']->getUserById($_SESSION['id']);
	$bag = $_pm['user']->getUserBagById($_SESSION['id']);
	$lucky_draw = new task;
	$lucky_draw->saveGetProps($return_info[3]);
	$sql = "UPDATE userbag SET sums = sums - 1 WHERE uid = {$_SESSION['id']} and pid = {$rs['pid']} and sums>0";
	$_pm['mysql']->query($sql);
	$sql = "DELETE from userbag WHERE sums = 0 AND bsum = 0 AND psum = 0 AND uid = {$_SESSION['id']} and pid = {$rs['pid']}";
	$_pm['mysql']->query($sql);
	unLockItem($id);
	realseLock();
	unset($_SESSION[$key]);
	die("抽奖成功,获得" . $level . ",得到物品:" . $return_info[2]);
}
else if ($rs['varyname'] == 13) // 特殊类型。扩展包裹，仓库，牧场格子
{
	//托管空间扩充卷
	if ($rs['pid'] == 1203) {
		if ($user['tgmax'] >= 2) {
			unLockItem($id);
			die("您只能使用此卷扩充一次托管所！");
		} else if ($user['tgmax'] == 1) {
			$sql = "UPDATE player SET tgmax = 2 WHERE id = {$_SESSION['id']}";
			$_pm['mysql']->query($sql);
			$sql = "UPDATE userbag SET sums = sums - 1 WHERE uid = {$_SESSION['id']} and pid = 1203 and sums>0";
			$_pm['mysql']->query($sql);
			unLockItem($id);
			die("使用托管所扩充卷轴（一）成功!");
		}
	}
	if ($rs['pid'] == 1204) {
		if ($user['tgmax'] >= 3) {
			unLockItem($id);
			die("您只能使用此卷扩充一次托管所！");
		} else if ($user['tgmax'] == 1) {
			unLockItem($id);
			die("请先使用托管所扩充卷（一）扩充您的托管所!");
		} else if ($user['tgmax'] == 2) {
			$sql = "UPDATE player SET tgmax = 3 WHERE id = {$_SESSION['id']}";
			$_pm['mysql']->query($sql);
			$sql = "UPDATE userbag SET sums = sums - 1 WHERE uid = {$_SESSION['id']} and pid = 1204 and sums>0";
			$_pm['mysql']->query($sql);
			unLockItem($id);
			die("使用托管所扩充卷轴（二）成功!");
		}
	}
	$eff = explode(":", $rs['effect']);
	if ($eff[0] == 'zhanshi') {
		$arr = "";
		$arr = $_pm['mysql']->getOneRecord("SELECT bbshow FROM player_ext WHERE uid = {$_SESSION['id']}");
		if (!is_array($arr)) {
			unLockItem($id);
			die("您暂时不能使用宠物展示卷！");
		}
		$_pm['mysql']->query("UPDATE player_ext SET bbshow = bbshow + {$eff[1]} WHERE uid = {$_SESSION['id']}");
		$_pm['mysql']->query("UPDATE userbag SET sums = sums - 1 WHERE pid = {$rs['pid']} and uid = {$_SESSION['id']} and sums>0");
		unLockItem($id);
		die("恭喜您使用宠物展示卷成功增加" . $eff[1] . "次展示机会！");
	} else if ($eff[0] == 'addsj') {
		$_pm['mysql']->query("UPDATE userbag
								  SET sums=sums-1
							 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
							 ");
		$result = mysql_affected_rows($_pm['mysql']->getConn());
		if ($result != 1) {
			unLockItem($id);
			die("您没有相应的物品！");
		}
		$arr = "";
		$arr = $_pm['mysql']->getOneRecord("SELECT sj FROM player_ext WHERE uid = {$_SESSION['id']}");
		$numarr = explode(',', $eff[1]);
		$num = rand($numarr[0], $numarr[1]);
		if (!is_array($arr)) {
			$_pm['mysql']->getOneRecord("INSERT INTO player_ext (uid,sj,bbshow) VALUES ({$_SESSION['id']},$num,5)");
		} else {
			$_pm['mysql']->query("UPDATE player_ext SET sj = sj + $num WHERE uid = {$_SESSION['id']}");
		}
		unLockItem($id);
		die("恭喜您得到了" . $num . "个水晶！");
	} else if ($eff[0] == 'addyb') {
		$_pm['mysql']->query("UPDATE userbag
								  SET sums=sums-1
							 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
							 ");
		$result = mysql_affected_rows($_pm['mysql']->getConn());
		if ($result != 1) {
			unLockItem($id);
			die("您没有相应的物品！");
		}
		$numarr = explode(',', $eff[1]);
		$num = rand($numarr[0], $numarr[1]);
		$_pm['mysql']->query("UPDATE player SET yb = yb+" . $num . " WHERE id = {$_SESSION['id']}");
		unLockItem($id);
		die("恭喜您得到了" . $num . "元宝！");
	} else if ($eff[0] == 'addbag') {
		if ($user['maxbag'] < 150) {
			unLockItem($id);
			die("您的背包没有达到150，不能使用此道具扩展！");
		}
		if ($user['maxbag'] >= 200) {
			unLockItem($id);
			die("您的背包已经有200格了，不能再使用此道具扩展！");
		}
		$maxbag = $user['maxbag'] + $eff[1];
		if ($maxbag > 200) $maxbag = 200;
		$_pm['mysql']->query("UPDATE userbag
								  SET sums=sums-1
							 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
							 ");
		$result = mysql_affected_rows($_pm['mysql']->getConn());
		if ($result != 1) {
			unLockItem($id);
			die("您没有相应的物品！");
		}
		$_pm['mysql']->query("UPDATE player SET maxbag = $maxbag WHERE id = {$_SESSION['id']}");
		unLockItem($id);
		die("恭喜您背包格子扩充了{$eff[1]}格！");
	} else if ($eff[0] == 'addck') {
		if ($user['maxbase'] < 150) {
			unLockItem($id);
			die("您的仓库没有达到150，不能使用此道具扩展！");
		}
		if ($user['maxbase'] >= 200) {
			unLockItem($id);
			die("您的背包已经有200格了，不能再使用此道具扩展！");
		}
		$maxbase = $user['maxbase'] + $eff[1];
		if ($maxbase > 200) $maxbase = 200;
		$_pm['mysql']->query("UPDATE userbag
								  SET sums=sums-1
							 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
							 ");
		$result = mysql_affected_rows($_pm['mysql']->getConn());
		if ($result != 1) {
			unLockItem($id);
			die("您没有相应的物品！");
		}
		$_pm['mysql']->query("UPDATE player SET maxbase = $maxbase WHERE id = {$_SESSION['id']}");
		unLockItem($id);
		die("恭喜您仓库格子扩充了{$eff[1]}格！");
	} else if ($eff[0] == 'addbag1') {
		if ($user['maxbag'] < 200) {
			unLockItem($id);
			die("您的背包没有达到200，不能使用此道具扩展！");
		}
		if ($user['maxbag'] >= 300) {
			unLockItem($id);
			die("您的背包已经有300格了，不能再使用此道具扩展！");
		}
		$maxbag = $user['maxbag'] + $eff[1];
		if ($maxbag > 300) $maxbag = 300;
		$_pm['mysql']->query("UPDATE userbag
								  SET sums=sums-1
							 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
							 ");
		$result = mysql_affected_rows($_pm['mysql']->getConn());
		if ($result != 1) {
			unLockItem($id);
			die("您没有相应的物品！");
		}
		$_pm['mysql']->query("UPDATE player SET maxbag = $maxbag WHERE id = {$_SESSION['id']}");
		unLockItem($id);
		die("恭喜您背包格子扩充了{$eff[1]}格！");
	} else if ($eff[0] == 'addck1') {
		if ($user['maxbase'] < 200) {
			unLockItem($id);
			die("您的仓库没有达到200，不能使用此道具扩展！");
		}
		if ($user['maxbase'] >= 300) {
			unLockItem($id);
			die("您的背包已经有300格了，不能再使用此道具扩展！");
		}
		$maxbase = $user['maxbase'] + $eff[1];
		if ($maxbase > 300) $maxbase = 300;
		$_pm['mysql']->query("UPDATE userbag
								  SET sums=sums-1
							 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
							 ");
		$result = mysql_affected_rows($_pm['mysql']->getConn());
		if ($result != 1) {
			unLockItem($id);
			die("您没有相应的物品！");
		}
		$_pm['mysql']->query("UPDATE player SET maxbase = $maxbase WHERE id = {$_SESSION['id']}");
		unLockItem($id);
		die("恭喜您仓库格子扩充了{$eff[1]}格！");
	}
	if (is_array($eff)) {
		if ($eff[0] == "tuoguan") {
			$_pm['mysql']->query("UPDATE userbag
								  SET sums=sums-1
							 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
							 ");
			$result = mysql_affected_rows($_pm['mysql']->getConn());
			if ($result != 1) {
				unLockItem($id);
				die("您没有相应的物品！");
			}
			unset($result);
			$sql = "UPDATE player SET tgtime = tgtime + $eff[1] WHERE id = {$_SESSION['id']}";
			$_pm['mysql']->query($sql);
			unLockItem($id);
			die("使用{$eff[1]}小时托管卷成功!");
		}
	}
	$keys = explode(':', $rs['effect']);
	if ($rs['pid'] >= 85 && $rs['pid'] <= 93) {
		$keys = explode(':', $rs['effect']);
		$item = split(',', $user['openmap']);
		if (in_array($keys[1], $item)) {
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
			// del a props for current map.
			$_pm['mysql']->query("UPDATE userbag SET sums = sums -1 WHERE uid = {$_SESSION['id']} and id = {$id} and sums>0");
			$user['openmap'] .= ',' . $keys[1];

			$_pm['mysql']->query("UPDATE player 
						   SET openmap='{$user['openmap']}' 
						 WHERE id={$_SESSION['id']}");

			unLockItem($id);
			die("{$rs['name']} 对应地图打开成功!");
		} else {
			unLockItem($id);
			die("地图打开失败，请确认包裹中有打开该地图对应的钥匙!");
		}
	} else if (($rs['pid'] >= 200 && $rs['pid'] <= 202) || $rs['pid'] == 1344) {
		$full = 0;
		if ($rs['name'] == "仓库升级卷轴") {
			if ($user['maxbase'] >= 96) $full = 1;
			if ($user['maxbase'] + 6 > 96) $user['maxbase'] = 96;
			else $user['maxbase'] += 6;
		} else if ($rs['name'] == "背包升级卷轴") {
			if ($user['maxbag'] >= 96) $full = 1;
			if ($user['maxbag'] + 6 > 96) $user['maxbag'] = 96;
			else $user['maxbag'] += 6;
		} else if ($rs['name'] == "牧场升级卷轴") {
			if ($user['maxmc'] >= 40) $full = 1;
			if ($user['maxmc'] + 6 > 40) $user['maxmc'] = 40;
			else $user['maxmc'] += 6;
		} else if ($rs['name'] == "高级牧场升级卷轴") {
			if ($user['maxmc'] < 40) {
				unLockItem($id);
				die("您的牧场格子还没扩展到40格，请先买其它道具扩展到40格才能再用此道具扩展!");
			}
			if ($user['maxmc'] >= 80) $full = 1;
			if ($user['maxmc'] + 1 > 80) $user['maxmc'] = 80;
			else $user['maxmc'] += 1;
		}
		if ($full == 1) {
			unLockItem($id);
			die("已经扩展到极限，如需再扩展请买其它道具!");
		}

		// del props. and save result.
		$_pm['mysql']->query("UPDATE player 
					   SET maxbase={$user['maxbase']},
					       maxbag={$user['maxbag']},
						   maxmc={$user['maxmc']}
					 WHERE id={$_SESSION['id']}");

		$_pm['mysql']->query("UPDATE userbag
					   SET sums=sums-1
					 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
				  ");
		unLockItem($id);
		die("使用道具 {$rs['name']} 成功!");
	} else if ($rs['pid'] == 1342) {
		$full = 0;
		if ($user['maxbag'] >= 150) $full = 1;
		if ($user['maxbag'] < 96) $full = 2;
		if ($user['maxbag'] + 6 > 150) $user['maxbag'] = 150;
		else $user['maxbag'] += 6;
		if ($full == 1) {
			unLockItem($id);
			die("已经扩展到极限，不能再继续扩展了!");
		}
		if ($full == 2) {
			unLockItem($id);
			die("背包还没扩展到96格，请先用背包升级卷轴扩展到96格!");
		}
		$_pm['mysql']->query("UPDATE player 
					   SET maxbag={$user['maxbag']}
					 WHERE id={$_SESSION['id']}");

		$_pm['mysql']->query("UPDATE userbag
					   SET sums=sums-1
					 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
				  ");
		unLockItem($id);
		die("使用道具 {$rs['name']} 成功!");
	} else if ($rs['pid'] == 1343) {
		$full = 0;
		if ($user['maxbase'] >= 150) $full = 1;
		if ($user['maxbase'] < 96) $full = 2;
		if ($user['maxbase'] + 6 > 150) $user['maxbase'] = 150;
		else $user['maxbase'] += 6;
		if ($full == 1) {
			unLockItem($id);
			die("已经扩展到极限，不能再继续扩展了!");
		}
		if ($full == 2) {
			unLockItem($id);
			die("仓库还没扩展到96格，请先用仓库升级卷轴扩展到96格!");
		}
		$_pm['mysql']->query("UPDATE player 
					   SET maxbase={$user['maxbase']}
					 WHERE id={$_SESSION['id']}");

		$_pm['mysql']->query("UPDATE userbag
					   SET sums=sums-1
					 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
				  ");
		unLockItem($id);
		die("使用道具 {$rs['name']} 成功!");
	} else if (($rs['pid'] >= 742 && $rs['pid'] <= 746) || $rs['pid'] == 1247 || $rs['pid'] == 1225 || $rs['pid'] == 2055) // 经验卷及自动战斗卷. format:
	{
		if ($keys[0] == 'exp') // 使用经验卷
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

			if (is_array($rs)) {
				// del a props for current
				$_pm['mysql']->query("UPDATE userbag
								  SET sums=sums-1
							 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
							 ");
				$result = mysql_affected_rows($_pm['mysql']->getConn());
				if ($result != 1) {
					unLockItem($id);
					die("您没有相应的物品！");
				}
				unset($result);
				// 获取当前的剩余双倍时间并累计。
				if ($user['dblexpflag'] > 1 && $dbl == $user['dblexpflag']) {
					$other = $user['dblstime'] + $user['maxdblexptime'] - time();
					if ($other <= 0) $other = 0;
					$user['maxdblexptime'] = 3600 + $other;
				} else $user['maxdblexptime'] = 3600;

				$user['dblexpflag'] = $dbl;
				$user['dblstime'] = time();

				// Update user data to database.
				$_pm['mysql']->query("UPDATE player
							   SET maxdblexptime={$user['maxdblexptime']},
								   dblexpflag={$user['dblexpflag']},
								   dblstime={$user['dblstime']}
							 WHERE id={$_SESSION['id']}
						  ");
				unLockItem($id);
				die("使用{$keys[1]} 倍经验卷成功!");
			} else {
				unLockItem($id);
				die("没有在包裹中发现相应的物品!");
			}
		}
	} // end 双倍卷。
	####################作用自动战斗卷，分为金币版和元宝版9.24谭炜###################

	if ($keys[0] == 'autofree') // 使用金币版自动战斗卷
	{
		if (is_array($rs)) {
			// del a props for current
			$_pm['mysql']->query("UPDATE userbag
								  SET sums=sums-1
							 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
							 ");
			$result = mysql_affected_rows($_pm['mysql']->getConn());
			if ($result != 1) {
				unLockItem($id);
				die("您没有相应的物品！");
			}
			unset($result);
			$user['sysautosum'] += intval($keys[1]);
			$_pm['mysql']->query("UPDATE player
								 SET sysautosum={$user['sysautosum']}
							 WHERE id={$_SESSION['id']}
							  ");
			unLockItem($id);
			die("使用 {$keys[1]} 次金币版自动战斗卷成功!");
		}
	} else if ($keys[0] == "auto" || $keys[0] == "autoteam") {
		$_pm['mysql']->query("UPDATE userbag
								  SET sums=sums-1
							 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
							 ");
		$result = mysql_affected_rows($_pm['mysql']->getConn());
		if ($result != 1) {
			unLockItem($id);
			die("您没有相应的物品！");
		}
		unset($result);
		if ($keys[0] == "auto") {
			$user['maxautofitsum'] += intval($keys[1]);
			$_pm['mysql']->query("UPDATE player
									  SET maxautofitsum={$user['maxautofitsum']}
								 WHERE id={$_SESSION['id']}
								 ");
			$msg = "使用 {$keys[1]} 次元宝版自动战斗卷成功!";
		} else {
			$_pm['mysql']->query("UPDATE player_ext
									  SET team_auto_times = team_auto_times+" . intval($keys[1]) . " WHERE uid=" . $_SESSION['id']);
			$msg = "使用组队自动战斗卷成功,增加 {$keys[1]} 次!";
		}
		unLockItem($id);
		die($msg);
	}
	####################在这里结束###################
}
else if ($rs['varyname'] == 4) {
	$config = $_pm['mysql']->getOneRecord("SELECT value2,contents FROM welcome WHERE code = 'ticket'");
	if (!is_array($config)) {
		$str = '';
	} else {
		$timearr = explode(':', $config['value2']);
		if ($timearr['0'] != 1) {
			$str = '';
		} else {
			if (date('H') >= $timearr['1']) {
				unLockItem($id);
				die('今天已开奖，明天再买吧！');
			}
		}
	}
	if ($rs['effect'] != 'ticket') {
		unLockItem($id);
		die();
	}
	$_pm['mysql']->query("UPDATE userbag
					  SET sums=sums-1
				 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
				 ");
	$result = mysql_affected_rows($_pm['mysql']->getConn());
	if ($result != 1) {
		unLockItem($id);
		die("您没有相应的物品！");
	}
	$sql = 'CREATE TABLE IF not exists ticket_' . date('Ymd') . ' (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `uid` int(11) unsigned DEFAULT "0",
		  `ticket_num` varchar(8) DEFAULT "0" COMMENT "号码",
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `tn` (`ticket_num`) 
		) ENGINE=MyISAM';
	$_pm['mysql']->query($sql);
	$welcome = memContent2Arr("db_welcome", 'code');
	$config = $welcome['ticket_num']['contents'];
	if (empty($config)) {
		$_pm['mysql']->query("UPDATE userbag
					  SET sums=sums+1
				 WHERE id={$id} and uid={$_SESSION['id']}
				 ");
		unLockItem($id);
		die("没有内存数据！");
	}
	$res = rand_num($config);
	echo '使用成功，获得号码为 ' . $res . ' 详情请到公告牌查看';
	unLockItem($id);
}
else if ($rs['varyname'] == 12) // 宝箱类型。
{
	/**
	 * Format: randitem:1308:1:80:2|1055:1:70:2|1141:1:80:2|744:1:30:2|211:1:40:1|213:1:40:1|871:1:40:1|870:1:20:1|1207:1:20:1|9:1:5:1|912:1:1:1
	 * @Memo: 1表示获得该道具的时候,会发系统公告(2表示不会发公告)
	 * “[玩家名字]打一枚徽章,或许是踩到了狗屎了,居然获得了E(对应数量)个D(对应的道具名称)”
	 */
	//判断用户包裹是否已满
	$bagNum = 0;

	if (is_array($bags)) {
		foreach ($bags as $x => $y) {
			if ($y['sums'] > 0 and $y['zbing'] == 0) {
				$bagNum++;
			}
		}
	}
	$snum = $user['maxbag'] - $bagNum;
	if ($snum < 3) {
		unLockItem($id);
		die('请留至少三个空格子！');
	}
	if ($bagNum >= $user['maxbag']) {
		unLockItem($id);
		die('您的包裹已满，请先清理包裹！');
	}

	if (!empty($rs['requires'])) {
		$requires = explode(":", $rs['requires']);
		if ($requires[0] == 'lv') {
			if ($bb['level'] < $requires[1]) {
				unLockItem($id);
				die("您没有达到相应的等级，不能开启该宝箱！");
			}
		}
	}
	$propsPatter = $rs['effect'];
	$arr = explode(",", $propsPatter);
	$task = new task();
	foreach ($arr as $v) {
		$newarr = explode(":", $v);
		if ($newarr[0] == "needkey") {
			if (is_array($bags)) {
				foreach ($bags as $y) {
					if ($y['pid'] == $newarr[1] && $y['sums'] > 0) {
						$_pm['mysql']->query("UPDATE userbag
										     SET sums=sums-1
										   WHERE pid={$newarr[1]} and uid={$_SESSION['id']} and sums>0
										");
						$sign = 1;
					}
				}
				if ($sign != 1) {
					unLockItem($id);
					die("您没有开启宝箱的钥匙!");
				}
			} else {
				unLockItem($id);
				die("您没有开启宝箱的钥匙!");
			}
		} else if ($newarr[0] == 'giveitems') {

			unset($result);
			$patter = str_replace('giveitems:', '', $rs['effect']);
			$propslist = explode(',', $patter);

			$retstr = '';
			if (is_array($propslist)) {
				if ($snum < count($propslist)) {
					die('背包空间不足！');
				}
				$_pm['mysql']->query("UPDATE userbag
								  SET sums=sums-1
							 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
							 ");
				$result = mysql_affected_rows($_pm['mysql']->getConn());
				if ($result != 1) {
					unLockItem($id);
					die("您没有相应的物品！");
				}
				foreach ($propslist as $k => $v) {
					$inarr = explode(':', $v);        //	0=> ID, 2=> rand number, 1=> sum props


					if (is_array($inarr)) {
						//foreach($inarr as $inarrs)
						//{
						$prs =getBasePropsInfoById($inarr[0]);
						$task->saveGetPropsMore($inarr[0], $inarr[1], $rs['pid'],0,$prs);

						if (empty($retstr)) {
							$retstr = '获得道具 ' . $prs['name'] . '&nbsp;' . $inarr[1] . ' 个';
						} else {
							$retstr .= "," . $prs['name'] . '&nbsp;' . $inarr[1] . ' 个';
						}
						//}
					}
				} // end foreach
				// del props current bag.
				echo $retstr;
			}
		} elseif ($newarr[0] == "randitem") {
			$patter = str_replace('randitem:', '', $v);
			$propslist = explode('|', $patter);
			$retstr = '';
			if (is_array($propslist)) {
				foreach ($propslist as $k => $v) {
					$inarr = explode(':', $v);        //	0=> ID, 2=> rand number, 1=> sum props
					if (rand(1, intval($inarr[2])) == 1)    //  rand hits
					{// del props current bag.
						$_pm['mysql']->query("UPDATE userbag
								  SET sums=sums-1
							 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
							 ");
						$result = mysql_affected_rows($_pm['mysql']->getConn());
						if ($result != 1) {
							unLockItem($id);
							die("您没有相应的物品！");
						}
						unset($result);
						$prs=getBasePropsInfoById($inarr[0]);
						$task->saveGetPropsMore($inarr[0], $inarr[1], $rs['pid'],0,$prs);
						$retstr = '获得道具 ' . $prs['name'] . ' ' . $inarr[1] . ' 个';
						if ($inarr[3] == 2) {
							$word = " ,使用{$rs['name']},幸运地得到自然女神的祝福,获得了 {$inarr[1]} 个{$prs['name']}";
							$task->saveGword($word);
						}

						echo $retstr;
						break;
					}
				} // end foreach
			}
		}
	}
}
else if ($rs['varyname'] == 22) // 宝箱类型。
{

	if (!isset($_GET['js'])) {
		unLockItem($id);
		die('占卜石,请在占卜屋中占卜时使用!<br/><span style="cursor:pointer;color:#ff0000;font-size:14px;font-weight:bold" onclick="$(\'gw\').contentWindow.location=\'/function/zhanbuwu.php\'"><strong>点击这里前往“占卜屋”！</strong></span>');
	}
	/**
	 * Format: randitem:1308:1:80:2|1055:1:70:2|1141:1:80:2|744:1:30:2|211:1:40:1|213:1:40:1|871:1:40:1|870:1:20:1|1207:1:20:1|9:1:5:1|912:1:1:1
	 * @Memo: 1表示获得该道具的时候,会发系统公告(2表示不会发公告)
	 * “[玩家名字]打一枚徽章,或许是踩到了狗屎了,居然获得了E(对应数量)个D(对应的道具名称)”
	 */
	//判断用户包裹是否已满
	$bagNum = 0;

	if (is_array($bags)) {
		foreach ($bags as $x => $y) {
			if ($y['sums'] > 0 and $y['zbing'] == 0) {
				$bagNum++;
			}
		}
	}
	$snum = $user['maxbag'] - $bagNum;
	if ($snum < 3) {
		die('请留至少三个空格子！');
	}
	if ($bagNum >= $user['maxbag']) {
		unLockItem($id);
		die('您的包裹已满，请先清理包裹！');
	}

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

	foreach ($arr as $v) {
		$newarr = explode(":", $v);
		if ($newarr[0] == "needkey") {
			if (is_array($bags)) {
				foreach ($bags as $y) {
					if ($y['pid'] == $newarr[1] && $y['sums'] > 0) {
						$_pm['mysql']->query("UPDATE userbag
										     SET sums=sums-1
										   WHERE pid={$newarr[1]} and uid={$_SESSION['id']} and sums>0
										");
						$sign = 1;
					}
				}
				if ($sign != 1) {
					unLockItem($id);
					die("您没有占卜的钥匙!");
				}
			} else {
				unLockItem($id);
				die("您没有占卜的钥匙!");
			}
		} else if ($newarr[0] == 'giveitems') {

			unset($result);
			$patter = str_replace('giveitems:', '', $rs['effect']);
			$propslist = explode(',', $patter);

			$retstr = '';
			if (is_array($propslist)) {
				if ($snum < count($propslist)) {
					die('背包空间不足！');
				}
				$_pm['mysql']->query("UPDATE userbag
								  SET sums=sums-1
							 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
							 ");
				$result = mysql_affected_rows($_pm['mysql']->getConn());
				if ($result != 1) {
					unLockItem($id);
					die("无相关占卜石，无法满足占卜需要的魔力T_T下次再来吧。");
				}
				foreach ($propslist as $k => $v) {
					$inarr = explode(':', $v);        //	0=> ID, 2=> rand number, 1=> sum props


					if (is_array($inarr)) {
						//foreach($inarr as $inarrs)
						//{
						$task = new task();
						$task->saveGetPropsMore($inarr[0], $inarr[1], $rs['pid']);
						$prs = $_pm['mysql']->getOneRecord("SELECT name FROM props WHERE id={$inarr[0]}");
						if (empty($retstr)) {
							$retstr = '获得道具 ' . $prs['name'] . '&nbsp;' . $inarr[1] . ' 个';
						} else {
							$retstr .= "," . $prs['name'] . '&nbsp;' . $inarr[1] . ' 个';
						}
						//}
					}
				} // end foreach
				// del props current bag.
				echo $retstr;
			}
		} elseif ($newarr[0] == "randitem") {
			$patter = str_replace('randitem:', '', $v);
			$propslist = explode('|', $patter);
			$retstr = '';
			$task = new task();
			if (is_array($propslist)) {
				foreach ($propslist as $k => $v) {
					$inarr = explode(':', $v);        //	0=> ID, 2=> rand number, 1=> sum props
					if (rand(1, intval($inarr[2])) == 1)    //  rand hits
					{// del props current bag.
						$_pm['mysql']->query("UPDATE userbag
								  SET sums=sums-1
							 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
							 ");
						$result = mysql_affected_rows($_pm['mysql']->getConn());
						if ($result != 1) {
							unLockItem($id);
							die("您没有相应的物品！");
						}
						unset($result);
						$task->saveGetPropsMore($inarr[0], $inarr[1], $rs['pid']);
						$prs = $_pm['mysql']->getOneRecord("SELECT name FROM props WHERE id={$inarr[0]}");
						$retstr = '获得道具 ' . $prs['name'] . ' ' . $inarr[1] . ' 个';

						if ($inarr[3] == 2) {
							$word = " ,使用{$rs['name']},虔诚的占卜感动了自然女神,获得了 {$inarr[1]} 个{$prs['name']}";
							//$word = "由于他（她）虔诚的占卜感动了自然女神，女神将心爱的{$prs['name']}*{$inarr[1]}个赐予了他（她）。";
							$task->saveGword($word);
						}

						echo $retstr;
						break;
					}
				} // end foreach
			}
		}
	}
}
else if ($rs['varyname'] == 2) // 增益类
{
	require_once('../sec/dblock_fun.php');
	$a = getLock($_SESSION['id']);
	if (!is_array($a)) {
		realseLock();
		unLockItem($id);
		die('服务器繁忙，请稍候再试！');
	}
	$_pm['mysql']->query("UPDATE userbag
								  SET sums=sums-1
							 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
							 ");
	$result = mysql_affected_rows($_pm['mysql']->getConn());
	if ($result != 1) {
		unLockItem($id);
		realseLock();
		die("您没有相应的物品！");
	}
	unset($result);
	$tid = $id == 0 ? $user['mbid'] : $id;
	$bb = $_pm['mysql']->getOneRecord("SELECT wx,name,czl,id
										 FROM userbb
										WHERE id={$user['mbid']} and uid=" . $_SESSION['id']);
	if ($bb['wx'] == 7 && $rs['requires'] != '__SS__') {
		unLockItem($id);
		$_pm['mysql']->query("rollback");
		realseLock();
		die("神圣宠物无法使用此类物品！");
	}

	if ($bb['wx'] != 7 && $rs['requires'] == '__SS__') {
		unLockItem($id);
		$_pm['mysql']->query("rollback");
		realseLock();
		die("非神圣宠物无法使用此类物品！");
	}
	$arr = explode(':', $rs['effect']);
	if (!is_array($arr)) return false;
	if ($arr[0] == 'addexp') // 增加经验
	{
		$eval = "\$exp=rand{$arr[1]};";
		eval($eval);
		$t = new task();
		$rtn = $t->saveExps($exp);
		if ($rtn === false) {
			$_pm['mysql']->query('rollback');
			realseLock();
			die("宠物已经不能再升级了！");
		}
		$tips .= '获得经验' . $exp;
	} else if ($arr[0] == "addczl") // 添加成长
	{
		$cishu = $_pm['mysql']->getOneRecord("select chouqu_chongwu from player_ext where uid={$_SESSION['id']}");
		if (strpos($cishu['chouqu_chongwu'], ',' . $bb['id'] . ',') !== false) {
			unLockItem($id);
			$_pm['mysql']->query("rollback");
			die("这个宠物抽取过成长,不能再使用这个道具!");
		}

		if ($bb['wx'] == 7) {
			$bb_settings = $_pm['mysql']->getOneRecord("SELECT max_czl FROM bb,super_jh	WHERE bb.id=super_jh.pet_id and bb.name='" . $bb['name'] . "' limit 1");
			if (!$bb_settings) {
				unLockItem($id);
				$_pm['mysql']->query("rollback");
				realseLock();
				die("取得神圣宠物设定失败！");
			}

			if ($bb_settings['max_czl'] < $bb['czl'] + $arr[1]) {
				$arr[1] = $bb_settings['max_czl'] - $bb['czl'];
			}
		}

		if ($user['mbid'] != '' && $user['mbid'] > 0) {
			$_pm['mysql']->query("UPDATE userbb
				                         SET czl=czl+{$arr[1]}
									   WHERE id={$user['mbid']}
									");
			$tips .= '主宠物永久增加' . $arr[1] . '成长！';
		}
	} else if ($arr[0] == "addac") // 增加攻击力
	{
		if ($user['mbid'] != '' && $user['mbid'] > 0) {
			$_pm['mysql']->query("UPDATE userbb
				                         SET ac=ac+{$arr[1]}
									   WHERE id={$user['mbid']}
									");
			$tips .= '主宠物永久增加' . $arr[1] . '攻击！';
		}
	} else if ($arr[0] == "addmc") // 增加防御
	{
		if ($user['mbid'] != '' && $user['mbid'] > 0) {
			$_pm['mysql']->query("UPDATE userbb
				                         SET mc=mc+{$arr[1]}
									   WHERE id={$user['mbid']}
									");
			$tips .= '主宠物永久增加' . $arr[1] . '防御！';
		}
	} else if ($arr[0] == "addhits") // 增加命中
	{
		if ($user['mbid'] != '' && $user['mbid'] > 0) {
			$_pm['mysql']->query("UPDATE userbb
				                         SET hits=hits+{$arr[1]}
									   WHERE id={$user['mbid']}
									");
			$tips .= '主宠物永久增加' . $arr[1] . '命中！';
		}
	} else if ($arr[0] == "addmiss") // 增加闪避
	{
		if ($user['mbid'] != '' && $user['mbid'] > 0) {
			$_pm['mysql']->query("UPDATE userbb
				                         SET miss=miss+{$arr[1]}
									   WHERE id={$user['mbid']}
									");
			$tips .= '主宠物永久增加' . $arr[1] . '闪避！';
		}
	} else if ($arr[0] == "addhp") // 增加生命力
	{
		if ($user['mbid'] != '' && $user['mbid'] > 0) {
			$_pm['mysql']->query("UPDATE userbb
				                         SET srchp=srchp+{$arr[1]}
									   WHERE id={$user['mbid']}
									");
			$tips .= '主宠物永久增加' . $arr[1] . '生命！';
		}
	} else if ($arr[0] == "addspeed") // 增加生命力
	{
		if ($user['mbid'] != '' && $user['mbid'] > 0) {
			$_pm['mysql']->query("UPDATE userbb
				                         SET speed=speed+{$arr[1]}
									   WHERE id={$user['mbid']}
									");
			$tips .= '主宠物永久增加' . $arr[1] . '速度！';
		}
	} else if ($arr[0] == "addmp") // 增加魔力
	{
		if ($user['mbid'] != '' && $user['mbid'] > 0) {
			$_pm['mysql']->query("UPDATE userbb
				                         SET srcmp=srcmp+{$arr[1]}
									   WHERE id={$user['mbid']}
									");
			$tips .= '主宠物永久增加' . $arr[1] . '魔法！';
		}
	} else if ($arr[0] == "weiwang") // 增加威望
	{
		$_pm['mysql']->query("UPDATE player
				                         SET prestige=prestige+{$arr[1]}
									   WHERE id={$_SESSION['id']}
									");
		$tips .= '增加威望' . $arr[1] . '点！';
	} else if ($arr[0] == "add_cq_czl") // 增加抽取的成长点数2010-11-3
	{
		$sql = 'update player_ext set czl_ss=czl_ss+' . abs($arr[1]) . ' where uid=' . $_SESSION['id'];
		$_pm['mysql']->query($sql);
		$tips .= '获得成长' . $arr[1] . '点！';
	} else if ($arr[0] == "add_zc_jifen") // 增加新战场（女神要塞）获胜积分倍数2010-11-3
	{
		$row = $_pm['mysql']->getOneRecord('select buff_status from player_ext where uid=' . $_SESSION['id']);
		$buff = preg_replace("/add_zc_jifen:[^;]+;?/", '', $row['buff_status']) . 'add_zc_jifen:' . date("Ymd") . ',' . $arr[1] . ';';

		$sql = 'update player_ext set buff_status="' . $buff . '" where uid=' . $_SESSION['id'];
		$_pm['mysql']->query($sql);
		$tips .= '操作成功！';
	}
	echo $tips;
	realseLock();
	unLockItem($id);
}
else if ($rs['varyname'] == 24)    //卡片类
{
	$sql = " SELECT F_User_Card_Info FROM player_ext WHERE uid = '" . $rs['uid'] . "'";
	$result_card = $_pm['mysql']->getOneRecord($sql);
	unset($sql);
	if (empty($result_card['F_User_Card_Info'])) {
		$sql = " UPDATE player_ext SET  F_User_Card_Info = '" . $rs['name'] . ":1' WHERE uid = '" . $rs['uid'] . "'";
		$result_card = $_pm['mysql']->query($sql);
		if ($result_card) {
			unset($result_card);
			$result_card = $result_card = $_pm['mysql']->query("UPDATE userbag
							  SET sums=sums-1
							   WHERE id={$rs['id']} and uid={$_SESSION['id']} and sums>0
							");
			if ($result_card) {
				$sql = " SELECT * FROM T_Card_to_Title ";
				$result = $_pm['mysql']->getRecords($sql);

				echo "恭喜您第一次使用卡片成功";
			} else {
				die("第一次使用卡片异常");
			}
		} else {
			die("第一次使用卡片异常");
		}
	} else {
		$card_arr = explode(',', $result_card['F_User_Card_Info']);
		for ($i = 0; $i < count($card_arr); $i++) {
			$arr_card_info = explode(':', $card_arr[$i]);
			$arr_card_name[$i] = $arr_card_info[0];
			if ($arr_card_name[$i] == $rs['name']) {
				$num = 0;
				$num = $arr_card_info[1] + 1;
				$arr_card_num[$i] = $num;
			} else {
				$arr_card_num[$i] = intval($arr_card_info[1]);
			}
		}
		if (in_array($rs['name'], $arr_card_name)) {
			$the_card = 1;
		} else {
			$the_card = 0;
		}
		switch ($the_card) {
			case 0 :    //没有这个卡片
			{
				$sql = " UPDATE player_ext SET F_User_Card_Info ='" . $result_card['F_User_Card_Info'] . "," . $rs['name'] . ":1' WHERE uid = '" . $rs['uid'] . "'";
				$result = $_pm['mysql']->query($sql);
				if ($result) {
					unset($result);
					$result = $_pm['mysql']->query("UPDATE userbag
							  SET sums=sums-1
							   WHERE id={$rs['id']} and uid={$_SESSION['id']} and sums>0
							");
					if ($result) {
						unset($result);
						unset($arr_card_name);
						unset($arr_card_num);
						echo "恭喜您使用新卡片成功";


					} else {
						unset($arr_card_name);
						unset($arr_card_num);
						unset($result);
						die("使用新卡片异常");
					}

				} else {
					unset($arr_card_name);
					unset($arr_card_num);
					die("使用新卡片异常");
				}
				break;
			}
			case 1 :    //这个卡片使用过了
			{
				$set_arr = array_combine($arr_card_name, $arr_card_num);
				foreach ($set_arr as $key => $val) {
					$set .= $key . ":" . $val . ",";
				}
				$set = substr($set, 0, -1);
				$sql = " UPDATE player_ext SET F_User_Card_Info = '" . $set . "' WHERE uid = '" . $rs['uid'] . "'";
				unset($set);
				unset($arr_card_name);
				unset($arr_card_num);
				unset($set_arr);
				unset($result);
				$result = $_pm['mysql']->query($sql);
				if ($result) {
					echo "使用过的卡片使用成功";
					$result_card = $_pm['mysql']->query("UPDATE userbag
							  SET sums=sums-1
							   WHERE id={$rs['id']} and uid={$_SESSION['id']} and sums>0
							");
				} else {
					die("使用过的卡片使用失败");
				}
				break;
			}
		}
	}
	$sql = " SELECT * FROM T_Card_to_Title ";
	$result = $_pm['mysql']->getRecords($sql);

	$sql = " SELECT F_User_Card_Info FROM player_ext WHERE uid = '" . $_SESSION['id'] . "'";
	$result_user_card = $_pm['mysql']->getOneRecord($sql);

	$arr_types = explode(',', $result_user_card['F_User_Card_Info']);
	for ($i = 0; $i < count($arr_types); $i++) {
		$result_user_card_arr = explode(':', $arr_types[$i]);
		$result_user_card_name[$i] = $result_user_card_arr[0];
	}
	unset($result_user_card);
	$result_user_card_has = array();
	$result_user_card_has = $result_user_card_name;
	$is_in_arr = array();
	foreach ($result as $info) {
		$arr_the_title_need_card = explode(',', $info['F_title_must_card']);
		for ($i = 0; $i < count($arr_the_title_need_card); $i++) {
			if (in_array($arr_the_title_need_card[$i], $result_user_card_has) || $arr_the_title_need_card[$i] == $result_user_card_has[0]) {
				array_push($is_in_arr, 1);
			} else {
				array_push($is_in_arr, 0);
			}
		}
		if (!in_array(0, $is_in_arr)) {
			$sql = " SELECT F_Has_Title FROM player_ext WHERE uid = '" . $_SESSION['id'] . "'";
			$title_result = $_pm['mysql']->getOneRecord($sql);
			if (empty($title_result['F_Has_Title'])) {
				$sql = " UPDATE player_ext SET  F_Has_Title = '" . $info['id'] . "' WHERE uid = '" . $_SESSION['id'] . "'";
				$_pm['mysql']->query($sql);
				$task = new task();
				$word .= "获得了新的称号-----" . $info['F_title_Chinese'];
				$task->saveGword($word);
			} else {
				$title_has = explode(',', $title_result['F_Has_Title']);
				if (!in_array($info['id'], $title_has)) {
					$set = $title_result['F_Has_Title'] . "," . $info['id'];
					$sql = " UPDATE player_ext SET F_Has_Title =  '" . $set . "' WHERE uid = '" . $_SESSION['id'] . "'";
					$_pm['mysql']->query($sql);
					$task = new task();
					$word .= "获得了新的称号-----" . $info['F_title_Chinese'];
					$task->saveGword($word);
				}
			}
		}
		unset($is_in_arr);
		$is_in_arr = array();
	}
}
else if ($rs['varyname'] == 16) // 图纸合成类
{

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
		die('您的包裹已满，请先清理包裹！');
	}

	$arr = explode(':', $rs['effect'], 2);
	if ($arr[0] == 'hecheng') // 图纸合成 格式：hecheng:(956:10|957:10|958:10|1025:1):1012:1|1013:1
	{
		require_once('../sec/dblock_fun.php');
		$a = getLock($_SESSION['id']);
		if (!is_array($a)) {
			realseLock();
			die('服务器繁忙，请稍候再试！');
		}
		$sysl = $_pm['mysql']->getOneRecord(" SELECT sums FROM userbag WHERE sums > 0 AND uid = {$_SESSION['id']} AND  id={$rs['id']}");
		if (!isset($sysl['sums']) || empty($sysl['sums'])) {
			unLockItem($id);
			die('你的材料不足，无法制作!');
		}
		$rarr = explode('):', $arr[1]);
		$require = str_replace('(', '', $rarr[0]);
		$getProps = explode('|', $rarr[1]);

		// Check props is exists?
		$need = explode('|', $require);
		foreach ($need as $k => $v) {
			$t = explode(':', $v);
			$ex = $_pm['mysql']->getOneRecord("SELECT sum(sums) as cnt 
												  FROM userbag 
												 WHERE pid ={$t['0']} and uid={$_SESSION['id']}");
			if ($ex['cnt'] < $t['1']) {
				unLockItem($id);
				die('你的材料不足，无法制作！');
			}
		}

		// ok, then get props.
		$idlist = '';
		foreach ($getProps as $v) {
			$gets = explode(':', $v);
			for ($i = 0; $i < $gets['1']; $i++) {
				$idlist .= $idlist == '' ? $gets[0] : ',' . $gets[0];
			}
		}

		// clear props
		$delcount = 0;
		foreach ($need as $k => $v) {
			$t = explode(':', $v);
			$ret = $_pm['mysql']->getRecords("SELECT id,sums
											  FROM userbag 
											 WHERE pid ={$t['0']} and uid={$_SESSION['id']}
											 ORDER by sums
										  ");
			//Del props and count num
			if (is_array($ret)) {
				foreach ($ret as $k => $v) {
					if ($v['sums'] < 1) continue;
					if ($delcount < $t[1]) $del = $t[1] - $delcount;
					else break;
					if ($v['sums'] == $del) {
						// del record
						$_pm['mysql']->query("UPDATE userbag 
											   SET sums=0
											 WHERE id={$v['id']}
										   ");
						break;
					} else if ($v['sums'] < $del) {
						// del record. $v['sums']
						$delcount += $v['sums'];
						$_pm['mysql']->query("UPDATE userbag 
											   SET sums=0
											 WHERE id={$v['id']}
										   ");
					} else // 减去剩余数值。update.
					{
						$v['sums'] = $v['sums'] - $del;
						// update record.
						$_pm['mysql']->query("UPDATE userbag 
											   SET sums={$v['sums']}
											 WHERE id={$v['id']}
										  ");
						break;
					}
				}
			} // end if
		} // end foreach
		// clear end
		$_pm['mysql']->query("UPDATE userbag
							     SET sums=sums-1
							   WHERE id={$rs['id']} and uid={$_SESSION['id']} and sums>0
							");
		// save props;
		$tsk = new task();
		$tsk->saveGetProps($idlist);
		unLockItem($id);
		realseLock();
		die('恭喜您,制作成功!获得了一件物品!');
	} else if ($arr[0] == 'chongzhu') // 重铸合成 格式：chongzhu:(956|957|958|1025):1012:10|1013:50
	{
		require_once('../sec/dblock_fun.php');
		$a = getLock($_SESSION['id']);
		if (!is_array($a)) {
			realseLock();
			unLockItem($id);
			die('服务器繁忙，请稍候再试！');
		}
		$arr = explode('):', $arr[1]);
		$arr[0] = str_replace('(', '', $arr[0]);
		$where = 'pid in(' . $arr[0] . ')';
		$sql = " SELECT id,pid FROM userbag WHERE  uid = '" . $_SESSION['id'] . "' AND sums = 1 AND zbing = 0  AND " . $where;
		//echo $sql;
		$res = $_pm['mysql']->getRecords($sql);
		if (count($res) < 1 || empty($res) || !is_array($res)) {
			unLockItem($id);
			die("背包里没有待重铸的物品哦！");
		}
		shuffle($res);
		$sql = " SELECT name FROM props WHERE id = {$res[0][pid]}";
		$del_name = $_pm['mysql']->getOneRecord($sql);
		$get_things_arr = explode('|', $arr[1]);
		for ($i = 0; $i < count($get_things_arr); $i++) {
			$arr_probability = explode('-', $get_things_arr[$i]);
			$new_arr[$arr_probability[0]] = $arr_probability[1];
		}
		$lucky_num = rand(0, 100);    //为了便于计算概率，最好还是取100
		//echo $lucky_num."<br>";
		asort($new_arr);
		//print_r($new_arr);
		foreach ($new_arr as $key => $val) {
			if ($lucky_num >= $val) {
				$get_pid = $key;
			}
		}
		$sub_p = $_pm['mysql']->query("UPDATE userbag SET sums=sums-1 WHERE id={$rs['id']} and uid={$_SESSION['id']} and sums>0");
		if ($sub_p) {
			echo "物品使用成功";
			$_pm['mysql']->query("DELETE FROM  userbag WHERE id={$res[0][id]} and uid={$_SESSION['id']} ");
			if (isset($get_pid)) {

				$sql = " SELECT name,propscolor FROM props WHERE id = {$get_pid}";
				$get_name = $_pm['mysql']->getOneRecord($sql);
				$user = $_pm['user']->getUserById($_SESSION['id']);
				$bag = $_pm['user']->getUserBagById($_SESSION['id']);
				$card_task = new task;
				$card_task->saveGetProps($get_pid);
				if ($get_name['propscolor'] == 6) {
					$word .= "使用" . $rs['name'] . "重铸得到了" . $get_name['name'];
					$card_task->saveGword($word);
				}
				echo "<br>重铸得到" . $get_name['name'];
				$str = '重铸成功,消失userbag表id:' . $res[0]['id'] . ',props表id:' . $get_pid . '获得物品:' . $get_name['name'];
			} else {
				$card_task = new task;
				$sql = " SELECT nickname FROM player WHERE id = '" . $_SESSION['id'] . "'";
				$user_nickname = $_pm['mysql']->getOneRecord($sql);
				//$word ="木得语言了,顶好的".$del_name['name']."被玩家".$user_nickname['nickname']."重铸过后,就这么木有了";
				//$card_task->saveGword($word,1);
				$str = '重铸失败消失userbag表id:' . $res[0]['id'] . ',props表id:' . $get_pid;
			}
			//需要日志
			$_pm['mysql']->query("INSERT INTO gamelog (seller,buyer,ptime,pnote,vary) VALUES({$_SESSION['id']},{$_SESSION['id']}," . time() . ",'$str',177)");
			realseLock();
			unLockItem($id);
		} else {
			unLockItem($id);
			die("出问题啦,扣物失败");
		}
	} else if ($arr[0] == 'random_combine') {
		require_once('../sec/dblock_fun.php');
		$a = getLock($_SESSION['id']);
		if (!is_array($a)) {
			realseLock();
			unLockItem($id);
			die('服务器繁忙，请稍候再试！');
		}
		$sysl = $_pm['mysql']->getOneRecord(" SELECT sums FROM userbag WHERE sums > 0 AND uid = {$_SESSION['id']} AND  id={$rs['id']}");
		if (!isset($sysl['sums']) || empty($sysl['sums'])) {
			unLockItem($id);
			die('你的材料不足，无法制作!');
		}
		$settings_of_gain = explode(';', $arr[1]);
		$items_need = explode('|', $settings_of_gain[0]);
		$items_gain = explode('|', $settings_of_gain[1]);

		$sqls_remove_item = array();
		foreach ($items_need as $idx => $it_need) {
			$it_need_setting = explode(',', $it_need);
			if (count($it_need_setting) != 2 || $it_need_setting[1] < 1 || $it_need_setting[0] < 1) {
				unLockItem($id);
				die("需要物品设定第" . $idx . "条错误!");
			}
			$row = $_pm['mysql']->getOneRecord('select id,sums from userbag where uid=' . $_SESSION['id'] . ' and sums>=' . $it_need_setting[1] . ' and pid=' . $it_need_setting[0]);
			if (!$row) {
				unLockItem($id);
				die("需要的物品不够数量!");
			}
			$_pm['mysql']->query('select * from userbag where id=' . $row['id'] . ' for update');
			//$sqls_remove_item[]='update userbag set sums='.($row['sums']-$it_need_setting[1]>-1?$row['sums']-$it_need_setting[1]:0).' where id='.$row['id'];
			$sqls_remove_item[] = 'update userbag set sums=sums - ' . $it_need_setting[1] . ' where id=' . $row['id'] . ' and sums >=' . $it_need_setting[1];
		}

		$gainFlag = false;
		$msg = "";
		foreach ($items_gain as $idx => $it_gain) {
			$it_gain_setting = explode(',', $it_gain);
			$rand = rand(0, 100);
			if ($rand <= $it_gain_setting[1]) {
				$tsk = new task();
				if (!isset($mempropsid)) $mempropsid = unserialize($_pm['mem']->get('db_propsid'));
				if (!isset($mempropsid[$it_gain_setting[0]])) {
					unLockItem($id);
					die("获得物品设定第" . $idx . "条错误,物品" . $it_gain_setting[0] . "不存在!");
				}
				$tsk->saveGetPropsMore($it_gain_setting[0], $it_gain_setting[2]);
				$msg .= '成功合成:' . $mempropsid[$it_gain_setting[0]]['name'] . " " . $it_gain_setting[2] . '件!<br/>';
				if ($it_gain_setting[3] > 0) {
					$tsk->saveGword('成功合成:' . $mempropsid[$it_gain_setting[0]]['name'] . " " . $it_gain_setting[2] . '件!');
				}
				$gainFlag = true;
				break;
			}
		}
		if ($msg == "") {
			$msg = "很遗憾,合成失败,没有获得任何物品!";
		}
		//只要数量够,都要扣除物品
		foreach ($sqls_remove_item as $sql) {
			$_pm['mysql']->query($sql);
			if (mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
				unLockItem($id);
				die('系统繁忙，请稍候操作！');
			}
		}

		if ($error = mysql_error()) {
			unLockItem($id);
			die("出现错误:" . $error);
		}
		$_pm['mysql']->query("UPDATE userbag
							     SET sums=sums-1
							   WHERE id={$rs['id']} and uid={$_SESSION['id']} and sums>0
							");

		realseLock();
		unLockItem($id);
		die($msg);
	}
}
else if ($rs['varyname'] == 15) // 宠物卵
{
	require_once('../sec/dblock_fun.php');
	$a = getLock($_SESSION['id']);
	if (!is_array($a)) {
		realseLock();
		unLockItem($id);
		die('服务器繁忙，请稍候再试！');
	}
	$allbb = $_pm['user']->getUserPetById($_SESSION['id']);
	$all = 0;
	if (is_array($allbb)) {
		foreach ($allbb as $x => $y) {
			if ($y['muchang'] != 0) continue;
			$all++;
		}
		if ($all >= 3) {
			unLockItem($id);
			die("您只能携带3个宝宝,使用道具失败！<br/>[系统推荐]：您可以把身上携带的宝宝放入到牧场！");
		}
	}

	//$_pm['mysql']->query("START TRANSACTION;");
	$_pm['mysql']->query("UPDATE userbag
								  SET sums=sums-1
							 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
							 ");
	$result = mysql_affected_rows($_pm['mysql']->getConn());
	if ($result != 1) {
		unLockItem($id);
		//$_pm['mysql']->query("ROLLBACK;");
		die("您没有相应的物品！");
	}
	unset($result);
	$arr = explode(':', $rs['effect']);
	if ($arr[0] == "openpet") $newpetsid = $arr[1];

	// 根据宝宝ID，生成宝宝属性并插入数据给到玩家数据包。
	#########################################################################################
	// Get new bb info.
	$bb = $_pm['mem']->dataGet(array('k' => MEM_BB_KEY,
		'v' => "if(\$rs['id'] == '{$newpetsid}') \$ret=\$rs;"
	));

	$czl = getCzl($bb['czl']);

	// insert into userbb.
	//$bbid= $newid = mem_get_autoid($m, MEM_ORDER_KEY, 'userbb');

	$uinfo = $user;

	$_pm['mysql']->query("INSERT INTO userbb(
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
									   effectimg
									  )
					VALUES(
						   '{$bb['name']}',
						   '{$uinfo['id']}',
						   '{$uinfo['nickname']}',
						   '1',
						   '{$bb['wx']}',
						   '{$bb['ac']}',
						   '{$bb['mc']}',
						   '{$bb['hp']}',
						   '{$bb['hp']}',
						   '{$bb['mp']}',
						   '{$bb['mp']}',
						   '{$bb['skillist']}',
						   unix_timestamp(),
						   '{$bb['nowexp']}',
						   '100',
						   '{$bb['imgstand']}',
						   '{$bb['imgack']}',
						   '{$bb['imgdie']}',
						   '{$bb['hits']}',
						   '{$bb['miss']}',
						   '{$bb['speed']}',
						   '{$bb['kx']}',
						   '{$bb['remakelevel']}',
						   '{$bb['remakeid']}',
						   '{$bb['remakepid']}',
						   '0',
						   '{$czl}',
						   't{$bb['id']}.gif',
						   'k{$bb['id']}.gif',
						   'q{$bb['id']}.gif'
						   )
				  ");

	$jnall = split(",", $bb['skillist']);
	foreach ($jnall as $a => $b) {
		$arr = split(":", $b);
		// Get jn info.
		$jn = $_pm['mem']->dataGet(array('k' => MEM_SKILLSYS_KEY,
			'v' => "if(\$rs['id'] == '{$arr[0]}') \$ret=\$rs;"
		));
		// #################################################
		// if ($jn['ackvalue'] == '') continue; // 增加辅助技能。
		//##################################################

		$ack = split(",", $jn['ackvalue']);
		$plus = split(",", $jn['plus']);
		$uhp = split(",", $jn['uhp']);
		$ump = split(",", $jn['ump']);
		$img = split(",", $jn['imgeft']);

		// Insert userbb jn.
		//$newid = mem_get_autoid($m, MEM_ORDER_KEY,'skill');
		/*获取刚插入宠物ID。*/
		$newbb = $_pm['mysql']->getOneRecord("SELECT id 
										  FROM userbb
										 WHERE uid={$_SESSION['id']}
										 ORDER BY stime DESC
										 LIMIT 0,1			                                         
									  ");
		$bbid = $newbb['id'];
		$szid=$arr['1']-1;
		$_pm['mysql']->query("INSERT INTO skill(bid,name,level,vary,wx,value,plus,img,uhp,ump,sid)
						VALUES(
							   '{$bbid}',
							   '{$jn['name']}',
							   '{$arr['1']}',
							   '{$jn['vary']}',
							   '{$jn['wx']}',
							   '{$ack[$szid]}',
							   '{$plus[$szid]}',
							   '{$img[$szid]}',
							   '{$uhp[$szid]}',
							   '{$ump[$szid]}',
							   '{$jn['id']}'
							  )
					  ");
	}

	// sub props sum.
	realseLock();
	echo "使用道具成功!";
	// $_pm['mysql']->query("COMMIT;");
	//#######################################################################################
}
else if ($rs['varyname'] == 14) // 军功令，换取军功
{
	require_once('../sec/dblock_fun.php');
	$a = getLock($_SESSION['id']);
	if (!is_array($a)) {
		realseLock();
		unLockItem($id);
		die('服务器繁忙，请稍候再试！');
	}
	$arr = explode(':', $rs['effect']);
	if ($arr[0] == "jg") {
		$sql = "SELECT jgvalue FROM battlefield_user WHERE uid = {$_SESSION['id']}";
		$row = $_pm['mysql']->getOneRecord($sql);
		if (!is_array($row)) {
			unLockItem($id);
			die("您目前没有参加战场活动，不能使用此道具！");
		}
		$_pm['mysql']->query("UPDATE battlefield_user
		                         SET jgvalue=jgvalue+{$arr[1]}
							   WHERE uid={$_SESSION['id']}
							");
		// sub props sum.
		$_pm['mysql']->query("UPDATE userbag
						   SET sums=sums-1
						   WHERE id={$id} and uid={$_SESSION['id']} and sums>0
						  ");
		realseLock();
		echo "恭喜您，使用道具成功，您获得了 {$arr[1]} 点军功！";
	} else {
		echo '道具使用失败！';
	}

}
else if ($rs['varyname'] == 55) // 天赋类
{
	$arr = explode(':', $rs['effect']);
	if ($arr[0] == "xidian") {
		$sql = "SELECT id FROM war_player WHERE id = {$_SESSION['id']}";
		$row = $_pm['mysql']->getOneRecord($sql);
		if (!is_array($row))
			$_pm['mysql']->query("INSERT INTO war_player (`id`, wash_talent_count) VALUES 
                                    ({$_SESSION['id']}, {$arr[1]})
                            ");
		else
			$_pm['mysql']->query("UPDATE war_player
                                 SET wash_talent_count=wash_talent_count+{$arr[1]}
                               WHERE id={$_SESSION['id']}
                            ");

		// sub props sum.
		$_pm['mysql']->query("UPDATE userbag
                           SET sums=sums-1
                           WHERE id={$id} and uid={$_SESSION['id']} and sums>0
                          ");
		echo "恭喜您，使用道具成功，您获得了 {$arr[1]} 点洗点次数！";
	} else {
		echo '道具使用失败！';
	}
}
else if ($rs['varyname'] == 57) // 增加携带宠物数量类
{
	$arr = explode(':', $rs['effect']);

	if (
		in_array($arr[0], array('xiedaibb21', 'xiedaibb31', 'xiedaibb20', 'xiedaibb30'))

	) {
		$xdnum = 1;
		$xdtime = 0;
		switch ($arr[0]) {
			case 'xiedaibb21':
				$xdnum = 2;
				$xdtime = time() + 3600 * 24 * 30;
				break;
			case 'xiedaibb31':
				$xdnum = 3;
				$xdtime = time() + 3600 * 24 * 30;
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

		$xdtimestr = $xdtime == 0 ? "永久" : date("Y/m/d H:i", $xdtime);
		$sql = "SELECT max_take_pet_num_save,max_take_pet_num, take_pet_limit_time FROM war_player WHERE id = {$_SESSION['id']}";
		$row = $_pm['mysql']->getOneRecord($sql);

		if ($row['take_pet_limit_time'] < time() && $row['take_pet_limit_time'] > 0)//处理过期
		{
			$_pm['mysql']->query("
							   UPDATE war_player
                                 SET max_take_pet_num=max_take_pet_num_save,take_pet_limit_time=0
                               WHERE id={$_SESSION['id']}
                            ");
			$row['take_pet_limit_time'] = 0;
			$row['max_take_pet_num'] = $row['max_take_pet_num_save'];
		}

		if ($row['max_take_pet_num'] > 2 && $row['take_pet_limit_time'] == 0) {
			die("您可以携带的宠物数量已经到最大值。");
		}

		if ($row['take_pet_limit_time'] > time() && !isset($_GET['cofxiedaibb'])) {
			die('您目前可以携带' . $row['max_take_pet_num'] . '只宝宝，到期时间为：' . date("Y/m/d H:i", $row['take_pet_limit_time']) . '；<br/><font color="#f00">如果继续这个状态将被覆盖，</font><br/>确定请点<a href="javascript:bid=\'' . $id . '&cofxiedaibb=1\';Used();setTimeout(\'bid=' . $id . '\',500);this.style.display=\'none\';void(0);"><strong>继续</strong></a>。');
		}

		$_pm['mysql']->query("START TRANSACTION");

		if (!is_array($row)) {
			//echo "********************************************";
			$row_czl = $_pm['mysql']->getOneRecord("select czl,wx from userbb where uid=" . $_SESSION['id'] . " order by czl desc");
			$_pm['mysql']->query("INSERT INTO war_player (`id`,name, max_take_pet_num, take_pet_limit_time,grow_up,wuxing) VALUES 
                                    ({$_SESSION['id']}, '" . $_SESSION['username'] . "'," . $xdnum . "," . $xdtime . "," . $row_czl['czl'] . "," . $row_czl['wx'] . ")
                            ");
			$_pm['mysql']->query("UPDATE userbag
                               SET sums=sums-1
                               WHERE id={$id} and uid={$_SESSION['id']} and sums>0
                              ");
		} else {
			//echo "-------------------------------------------";
			$_pm['mysql']->query("UPDATE war_player
                                 SET max_take_pet_num_save='" . ($row['take_pet_limit_time'] > time() ? $row['max_take_pet_num_save'] : $row['max_take_pet_num']) . "',max_take_pet_num='" . $xdnum . "',take_pet_limit_time='" . $xdtime . "'
                               WHERE id={$_SESSION['id']}
                            ");
			$_pm['mysql']->query("UPDATE userbag
                               SET sums=sums-1
                               WHERE id={$id} and uid={$_SESSION['id']} and sums>0
                              ");
		}

		if (!mysql_error()) {
			$_pm['mysql']->query("COMMIT");
			echo "恭喜您，使用道具成功，您的宠物携带数量变更为:" . $xdnum . "，有效时间至:" . $xdtimestr . "！";
		} else {
			$_pm['mysql']->query("ROLLBACK");
			echo "使用道具失败,道具数量未减少！";
		}
		echo time();
		// sub props sum.

	} else {
		echo '道具使用失败！';
	}
}
else if ($rs['varyname'] == 58) // 增加携带宠物数量类
{
	if (!is_array($bb)) {
		unLockItem($id);
		die('您还没有设置主战宝宝，不能使用这个道具！');
	}

	$arr = explode(':', $rs['effect']);
	if (
		in_array($arr[0], array('tianfuexp')) && count($arr) == 2

	) {
		$exp = explode(',', $arr[1]);
		if (count($exp) == 2) {
			if ($exp[0] < 1 || $exp[0] > $exp[1]) {
				unLockItem($id);
				die("道具随机经验数据错误（{$arr[1]}）！");
			}
			$expGet = (int)rand($exp[0], $exp[1]);
		} else {
			$expGet = (int)$exp[0];
		}

		$sql = 'select
					id,current_experience
				from
					war_fighter_talent
				where
					fighter_id=' . $bb['id'] . '';

		$ts = $_pm['mysql']->getRecords($sql);


		if (empty($ts) || !is_array($ts)) {
			unLockItem($id);
			die("您没有进入过魔塔，没有魔塔数据！");
		}

		if ($err = mysql_error()) {
			unLockItem($id);
			die("查询错误：" . $err);
		}

		$expGetAver = ceil($expGet / count($ts));

		$_pm['mysql']->query("START TRANSACTION");
		foreach ($ts as $row) {
			$sql = 'update war_fighter_talent set current_experience=' . ($row['current_experience'] + $expGetAver) . ' where id=' . $row['id'];
			$_pm['mysql']->query($sql);
		}

		if (!$err = mysql_error()) {
			$_pm['mysql']->query("COMMIT");
			echo "使用道具成功，主战宠物已经有天赋平分经验：" . $expGet . "！";
		} else {
			$_pm['mysql']->query("ROLLBACK");
			echo "使用道具失败,道具数量未减少($err)！";
		}
	} else {
		echo '道具使用失败！道具数据错误！';
	}
}

function rand_num($config)
{
	global $_pm;
	$rand = rand(10000, 99999);
	$num = $config . $rand;
	$_pm['mysql']->query('INSERT INTO ticket_' . date('Ymd') . ' SET uid=' . $_SESSION['id'] . ',ticket_num="' . $num . '"');
	$result = mysql_affected_rows($_pm['mysql']->getConn());
	if ($result != 1) {
		return rand_num($config);
	} else {
		return $num;
	}
}

unLockItem($id);
$_pm['mem']->memClose();
unset($m, $u, $db, $user, $bags, $rs);
?>
