<?php
require_once('../config/config.game.php');
secStart($_pm['mem']);
define("ZS", "db_welcome1");
require_once('../sec/dblock_fun.php');
$a = getLock($_SESSION['id']);
if (!is_array($a)) {
    realseLock();
    die('11');
}
$ap = intval($_REQUEST['ap']);  // table userbb->id
$bp = intval($_REQUEST['bp']);  // table userbb->id
$p1 = intval($_REQUEST['p1']);  // table userbag->id
$zs = intval($_REQUEST['zs']);  // table userbag->id
$srctime = 10;
$chouqu_chk_ext = $_pm['mysql']->getRecords("select 1 from userbb where wx=6 and name not like '涅磐兽%' and czl=1 and uid={$_SESSION['id']} and (id=$ap or id=$bp)");
if (is_array($chouqu_chk_ext) && count($chouqu_chk_ext) > 0) {
    realseLock();
    die("某个宠物已经抽取过成长,不能进行涅槃!");
}

$cishu = $_pm['mysql']->getOneRecord("select chouqu_chongwu from player_ext where uid={$_SESSION['id']}");
if (strpos($cishu['chouqu_chongwu'], ',' . $ap . ',') !== false || strpos($cishu['chouqu_chongwu'], ',' . $bp . ',') !== false) {
    realseLock();
    die("某个宠物抽取过成长,不能进行涅槃!");
}

if ($ap < 0 || $bp < 0) {
    realseLock();
    die();
}
#################增加一个间隔时间################
$time = $_SESSION['time' . $_SESSION['id']];
if (empty($time)) {
    $_SESSION['time' . $_SESSION['id']] = time();
} else {
    $nowtime = time();
    $ctime = $nowtime - $time;
    if ($ctime < $srctime && $_GET['type'] != 'do' && $_GET['type1'] != 'check') {
        realseLock();
        die("11");//没有达到间隔时间
    } else {
        $_SESSION['time' . $_SESSION['id']] = time();
    }
}


if ($p1 < 0) $p1 = 0;
$p2 = intval($_GET['p2']);
if ($p2 < 0) {
    $p2 = 0;
}


#################是否选了护宠仙石结束############
if ($_GET['type1'] != 'check') //判断一次就够了
{
    $sql_props = 'SELECT pid FROM userbag WHERE (id=' . $p1 . ' or id=' . $p2 . ') and uid=' . $_SESSION['id'];
    $props = $_pm['mysql']->getRecords($sql_props);
    if (is_array($props)) {
        $check_props = 0;
        foreach ($props as $key_props => $key_value)//Array ( [pid] => 771 )
        {
            $a = 'SELECT effect FROM props WHERE varyname=8 and id=' . $key_value['pid'];
            $cmpProps = $_pm['mysql']->getOneRecord($a);
            if (is_array($cmpProps))//Array ( [effect] => npbb:1,npcg:3000%,npcz:15% )
            {
                $key_values = strpos($cmpProps['effect'], 'npbb');
                if ($key_values !== false) {

                    $check_props = $check_props + 1;
                }
            }

        }
        if ($check_props == 0) {
            realseLock();
            die('200');
        }
    } else {
        realseLock();
        die('200');
    }
}

$zbcheck = $_pm['mysql']->getRecords("SELECT id FROM userbag WHERE uid={$_SESSION['id']} and (zbpets = $ap or zbpets = $bp or zbpets = $zs)");
if (is_array($zbcheck) && count($zbcheck) >= 1) {
    realseLock();
    die('1000');
}


##################增加在这里结束#################
if ($ap < 0 || $bp < 0 || $zs < 0) {
    realseLock();
    die('0');
}

$pp1 = array();
$pp2 = array();

if(lockItem($ap) === false)
{
	realseLock();
	die('已经在处理了！');
}

$user = $_pm['user']->getUserById($_SESSION['id']);
$userbb = $_pm['user']->getUserPetById($_SESSION['id']);
$userbag = $_pm['user']->getUserBagById($_SESSION['id']);
$log = '';
$app = array();
$bpp = array();
$zsp = array();
if (is_array($userbb) && is_array($userbag)) {
    $membbid = unserialize($_pm['mem']->get('db_bbname'));
    foreach ($userbb as $key => $rs) {
        if ($rs['id'] == $ap && $rs['level'] >= 60) // From bb base find user current bb.
        {
            $app = $rs;
        } else if ($rs['id'] == $bp && $rs['level'] >= 60) {
            $bpp = $rs;
        } else if ($rs['id'] == $zs && $rs['level'] >= 60 && ($rs['name'] == "涅磐兽（亥）" || $rs['name'] == "涅磐兽（午）" || $rs['name'] == "涅磐兽（卯）" ) && $rs['muchang'] == 0) {
            $zsp = $rs;
        }
    }
	if (!is_array($app) || !is_array($bpp) || ($app['id'] == $bpp['id'])) {
		unLockItem($ap);
		realseLock();
		die('1');
	}
	if (!is_array($zsp) || $zsp['id'] == $app['id'] || $zsp['id'] == $bpp['id']) {
		unLockItem($ap);
		realseLock();
		die('7');
	}
    if ($app['wx'] > 6 || $bpp['wx'] > 6 || $zsp['wx'] > 6) {
		unLockItem($ap);
        realseLock();
        die('五行属于：金、木、水、火、土、神的才可以进行此操作！');
    }
    unset($rs);
	if (!is_array($membbid) || !isset($membbid[$app['name']]) || !isset($membbid[$bpp['name']])) {
		unLockItem($ap);
		realseLock();
		die('2');
	}
    if (!is_array($app) || !is_array($bpp) || ($app['id'] == $bpp['id'])) {
        unLockItem($ap);
        realseLock();
        die('1'); //没有对应的宠物。
    }

    if (!is_array($zsp)) {
        realseLock();
        unLockItem($ap);
        die("7");//请选择涅磐兽
    }
  

    // 检查是否满足公式。
    /*$ars = $_pm['mem']->dataGet(array('k' => MEM_BB_KEY,
                                         'v' => "if(\$rs['name'] == '{$app['name']}') \$ret=\$rs;"
                              ));
    $brs = $_pm['mem']->dataGet(array('k' => MEM_BB_KEY,
                                         'v' => "if(\$rs['name'] == '{$bpp['name']}') \$ret=\$rs;"
                              ));*/

    $ars = $membbid[$app['name']];
    $brs = $membbid[$bpp['name']];

    $cmprs = $_pm['mysql']->getOneRecord("SELECT * 
											FROM zs
										   WHERE aid = {$ars['id']} and bid={$brs['id']}
										   LIMIT 0,1
	                                    ");
    if (!is_array($cmprs)) {
        unLockItem($ap);
        realseLock();
        die('2');    //不能合成，
    }


    // 判断金币消耗：
    $money = 500000;

    if ($user['money'] < $money) {
        unLockItem($ap);
		realseLock();
        die('3');    //	金币不足。
    }
    foreach ($userbag as $k => $rs) {
		if ($rs['varyname'] == 19 && $rs['id'] == $p2) {
            $pp2 = $rs;
            continue;
        }
        if ($rs['varyname'] != 8 || $rs['effect'] == '' || empty($rs['effect']) || $rs['sums'] < 1) continue;
        if ($rs['id'] == $p1 && $rs['sums'] >= 1) {
            $pp1 = $rs;
        }
    }
	if (!isset($pp1['id']) || ($p2 > 0 && !isset($pp2['id']))) {
		unLockItem($ap);
		realseLock();
		die('20');
	}

    //$propseff = getEffect($pp1, $pp2);
    //得到使用物品的效果
	$arr = array();
    if (!empty($pp1)) {
        $one = explode(",", $pp1['effect']);
        foreach ($one as $b) {
            $arr[] = explode(":", $b);
        }
    }
    $zsflag = 0;
    $psuc = 0;
    $pczl = 0;
    if (is_array($arr)) {
        foreach ($arr as $a) {
            switch ($a[0]) {
                case "npbb":
                    $zsflag = $a[1];
					break;//涅槃失败时保护涅槃兽
                case "npcg":
                    $psuc = str_replace('%', '', $a[1]) / 100;
                    break;//增加成功机率
                case "npcz":
                    $pczl = str_replace('%', '', $a[1]) / 100;
                    break;//增加成长
            }
        }
    }


    // 得到成功率.
    //$sus = getSuccess($propseff);
    //得到成长率：[(主宠等级/15+副宠等级/15)*(100%+道具增加百分比)]*100%
    $sus = round(($app['level'] / 30 + $bpp['level'] / 30) * (1 + $psuc), 2);
    $pp2eff = 0;
	if (!empty($pp2)) {
        $pp2arr = explode(':', $pp2['effect']);
        if ($pp2arr[0] == 'addcz') {
            $pp2eff = str_replace('%', '', $pp2arr[1]) * 0.01;
        }
    }
    $czl = bbczl($app, $bpp, $pczl, $zsp, $pp2eff);
    if($czl ==0){
        realseLock();
        unLockItem($ap);
        die("10");
    }
    $susnum = rand(1, 10000);
    $a = $sus * 100;
    if ($susnum <= $a) // 合成成功。a,b宠物消失，得到新的宠物。$cmprs=> 得到相关宝宝信息。
    {
        // 改变属性地方为:
        $newbid = $cmprs['mid'];

        $brs = $_pm['mysql']->getOneRecord("SELECT * 
											  FROM  bb
											 WHERE id={$newbid}
											 LIMIT 0,1
										  ");

        if (!is_array($brs)) {
            realseLock();
            unLockItem($ap);
            die('10'); // 数据错误
        }
        // 改变各项数据:
		$newbbid = makebb($brs, $czl);
		if ($newbbid === false) {
			$_pm['mysql']->query('ROLLBACK');
			unLockItem($ap);
			die('10');
		}
        $cstatus = 2;
    } else // 如果没有相关道具进行绑定，副宠消失
    {
        $cstatus = 1;
    }

	if (!$_pm['mysql']->query("UPDATE player SET money=money-{$money} WHERE id={$_SESSION['id']} and money>={$money}") ||
		mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
		$_pm['mysql']->query('ROLLBACK');
		unLockItem($ap);
		die('3');
	}
    // 记录日志：
    $log .= "合成结果：" . ($cstatus == 1 ? "失败" : "成功") . "\n";
	$log .= "合成道具：1:" . (isset($pp1['name']) ? $pp1['name'] : '') . '，合成道具：2:' . (isset($pp2['name']) ? $pp2['name'] : '') . ' 涅磐:' . $zsp['id'] . "\n";

    //######### del props Start.##################
	if (!delProps()) {
		$_pm['mysql']->query('ROLLBACK');
		unLockItem($ap);
		die('20');
	}
    ############# del props end.#####################

	if ($cstatus == 1) //失败时，无 npbb:1 才消耗涅槃兽。
    {
        $log .= '合成道具详细：';
        if ($zsflag != 1) {
			if (!clearBB($zsp)) {
				$_pm['mysql']->query('ROLLBACK');
				unLockItem($ap);
				die('10');
			}
            $log .= 'name:' . $zsp['name'] . 'level:' . $zsp['level'] . 'czl:' . $zsp['czl'] . 'hp:' . $zsp['srchp'] . 'hits:' . $zsp['hits'] . 'ac:' . $zsp['ac'];
        }
        $log = addslashes($log);
		if (!$_pm['mysql']->query('COMMIT')) {
			$_pm['mysql']->query('ROLLBACK');
			unLockItem($ap);
			die('10');
		}
        // 合成失败记录点：
        $_pm['mysql']->query("INSERT INTO gamelog(ptime,seller,buyer,pnote,vary)
		                      VALUES(unix_timestamp(),'{$_SESSION['id']}','{$_SESSION['id']}','{$log}',10)
							");
        unLockItem($ap);
        die('6');
    } else if ($cstatus == 2) // 成功。
    {
		if (!clearBB($app) || !clearBB($bpp) || !clearBB($zsp)) {
			$_pm['mysql']->query('ROLLBACK');
			unLockItem($ap);
			die('10');
		}
		if (!$_pm['mysql']->query('COMMIT')) {
			$_pm['mysql']->query('ROLLBACK');
			unLockItem($ap);
			die('10');
		}
        $msg_key = 'chatMsgList';
        $nowMsgList = unserialize($_pm['mem']->get($msg_key));
        $arr = split('linend', $nowMsgList);
        if (count($arr) > 20) // cear old
        {
            $arrt = array_shift($arr);
        }
        $newstr = '<font color=red>[系统公告]恭喜玩家 ' . $user['nickname'] . ' 的宝宝经过圣洁的洗礼，成功的转世成为[' . $brs['name'] . ']!</font>';
		$newbbarr = $_pm['mysql']->getOneRecord("SELECT level,czl,ac,hits,srchp FROM userbb WHERE id={$newbbid} and uid={$_SESSION['id']} LIMIT 1");
       $str = '新宠物名字：' . $brs['name'] . 'level:' . $newbbarr['level'] . 'czl:' . $newbbarr['czl'] . 'ac:' . $newbbarr['ac'] . 'hits:' . $newbbarr['hits'] . ',使用物品：' . $pp1['name'] . ',涅磐兽：' . $zsp['name'] . 'level:' . $zsp['level'] . 'czl:' . $zsp['czl'] . 'ac:' . $zsp['ac'] . 'hits:' . $zsp['hits'] . ',宠物：' . $app['name'] . 'level:' . $app['level'] . 'czl:' . $app['czl'] . 'ac:' . $app['ac'] . 'hits:' . $app['hits'] . '-' . $bpp['name'] . 'level:' . $bpp['level'] . 'czl:' . $bpp['czl'] . 'ac:' . $bpp['ac'] . 'hits:' . $bpp['hits'];
        $_pm['mysql']->query("INSERT INTO gamelog(ptime,seller,buyer,pnote,vary)
		                      VALUES(unix_timestamp(),'{$_SESSION['id']}','{$_SESSION['id']}','{$str}',11)
							");
        $addczl=$czl-$app["czl"];
    
		$retstr = '';
		foreach ($arr as $k => $v) {
            $retstr .= $v . 'linend';
        }

        $retstr = $retstr . $newstr;
        $_pm['mem']->set(array('k' => $msg_key, 'v' => $retstr)); // default ten min.

        //----------------------------------------------------------------------------------------------------------------------
        //$_olddata = @unserialize($_pm['mem']->get('ttmt_data_notice'));

        $swfData =  '恭喜玩家 ' . $user['nickname'] . ' 的宝宝经过圣洁的洗礼，成功的转世成为[' . $brs['name'] . ']!';
        require_once(dirname(__FILE__) . '/../socketChat/config.chat.php');
        $s = new socketmsg();
        $s->sendMsg('an|' . $swfData);
        unLockItem($ap);
        die('5');
    }
} else {
    unLockItem($ap);
    realseLock();
    die('000');
}
$_pm['mem']->memClose();
// Logic code end.
realseLock();


/**
 * @Usage: 创建新的宠物。
 * @Param: array -> $bb. 新宠物
 * @Return: Void(0);
 */
function makebb($bb, $czl)
{
    global $app, $bpp, $user, $_pm, $zsp, $pp2;
    //$czl = bbczl($app,$bpp,$pczl,$zsp);
    $pac = $pmc = $phits = $php = 0;
    if (!empty($pp2)) {
        $arr = explode(':', $pp2['effect']);
        switch ($arr[0]) {
            case 'addac':
                $pac = str_replace('%', '', $arr[1]) * 0.01;
                break;
            case 'addmc':
                $pmc = str_replace('%', '', $arr[1]) * 0.01;
                break;
            case 'addhits':
                $phits = str_replace('%', '', $arr[1]) * 0.01;
                break;
            case 'addhp':
                $php = str_replace('%', '', $arr[1]) * 0.01;
                break;
        }
    }
    // ac,luck,mc,hit,miss,speed,hp,mp,shbb;
    $bb['ac'] = getPa($bb['ac'], $app['ac'], $bpp['ac'], $pac);  #### 暂时没有加入道具附加属性。
    $bb['mc'] = getPa($bb['mc'], $app['mc'], $bpp['mc'], $pmc);
    $bb['hits'] = getPa($bb['hits'], $app['hits'], $bpp['hits'], $phits);
    $bb['miss'] = getPa($bb['miss'], $app['miss'], $bpp['miss'], 0);
    $bb['speed'] = getPa($bb['speed'], $app['speed'], $bpp['speed'], 0);
    $bb['hp'] = getPa($bb['hp'], $app['hp'], $bpp['hp'], $php);
    $bb['mp'] = getPa($bb['mp'], $app['mp'], $bpp['mp'], 0);
    $uinfo = $user;
    $inserted = $_pm['mysql']->query("INSERT INTO userbb(
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
	if (!$inserted) {
		return false;
	}
	$bbid = intval($_pm['mysql']->last_id());
	if ($bbid <= 0) {
		return false;
	}
    $jnall = split(",", $bb['skillist']);
	$memskillsysid = unserialize($_pm['mem']->get('db_skillsysid'));
    foreach ($jnall as $a => $b) {
        $arr = split(":", $b);
		if (!isset($arr[0]) || !isset($arr[1]) || !isset($memskillsysid[$arr[0]])) {
			return false;
		}
        $jn = $memskillsysid[$arr[0]];
        // #################################################
    //   if ($jn['ackvalue'] == '') continue; // 增加辅助技能。
        //##################################################

        $ack = split(",", $jn['ackvalue']);
        $plus = split(",", $jn['plus']);
        $uhp = split(",", $jn['uhp']);
        $ump = split(",", $jn['ump']);
        $img = split(",", $jn['imgeft']);

        if (!$_pm['mysql']->query("INSERT INTO skill(bid,name,level,vary,wx,value,plus,img,uhp,ump,sid)
					VALUES(
						   '{$bbid}',
						   '{$jn['name']}',
						   '{$arr['1']}',
						   '{$jn['vary']}',
						   '{$jn['wx']}',
						   '{$ack[0]}',
						   '{$plus[0]}',
						   '{$img[0]}',
						   '{$uhp[0]}',
						   '{$ump[0]}',
						   '{$jn['id']}'
						  )
				  ")) {
			return false;
		}

        ####################天梯######################
        /*$wararr1 = $_pm['mysql'] -> getOneRecord("SELECT fighter_id FROM war_fighter WHERE fighter_id = {$app['id']}");
        if(is_array($wararr1)){
            $_pm['mysql'] -> query("UPDATE war_fighter SET fighter_id = {$bbid} WHERE fighter_id = {$app['id']}");
        }
        $wararr2 = $_pm['mysql'] -> getOneRecord("SELECT fighter_id FROM war_fighter_talent WHERE fighter_id = {$app['id']}");
        if(is_array($wararr2)){
            $_pm['mysql'] -> query("UPDATE war_fighter_talent SET fighter_id = {$bbid} WHERE fighter_id = {$app['id']}");
        }*/
        ####################天梯在这里结束######################

        ##################################合成成功，给用户设当前宠物为主战宠物#########################################
        ###################################在这里结束##################################################################
    }
	if (!$_pm['mysql']->query("UPDATE player SET mbid={$bbid} WHERE id={$_SESSION['id']}") ||
		mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
		return false;
	}
	return $bbid;
}

/**
 * @Usage: 删除一个宠物;
 * @Param: Array -> $bb.
 * @Return: Void(0);
 */
function clearBB($bb)
{
    global $_pm, $log;
    $id = $bb['id'];

    foreach ($bb as $k => $v) {
        $log .= $k . '=>' . $v . '-';
    }

    // del sk.
    if (!$_pm['mysql']->query("DELETE FROM skill
				 WHERE bid={$id}
			  ")) {
		return false;
	}

    // del zb.
    if (!$_pm['mysql']->query("DELETE FROM userbag
				 WHERE uid={$_SESSION['id']} and zbpets={$id}
			  ")) {
		return false;
	}
    // del bb.
    if (!$_pm['mysql']->query("DELETE FROM userbb
				 WHERE uid={$_SESSION['id']} and id={$id}
			  ")) {
		return false;
	}
	return mysql_affected_rows($_pm['mysql']->getConn()) == 1;
}

unLockItem($ap);
/**
 * @Param: 宠物a,b的属性。
 * @Return: 返回组后的成长率。
 * czl:实际成长率=对应宠物资料数据库成长率属性+取1位小数{[取一位小数（主宠物成长*主宠物等级/120）+取一位小数（副宠物成长*副宠物等级/240）+rand(副宠物成长/10,主宠物成长/10)]* (100%+道具附加属性%)}
 * rand(副宠物成长/10,主宠物成长/10)
 * 意思是:取副宠物的成长值/10到主宠物成长值/10的随机数
 * 如副宠物成长10,主宠物成长20
 * 则: rand(1,2)
 */
function bbczl($a, $b, $pp1, $zs, $pp2)
{
    global $brs;
    global $_pm;
    $zsarr = unserialize($_pm['mem']->get(ZS));
    // 资料库中宠物属性。
    if ($zs['name'] == '涅磐兽（卯）') {
        $lv = 0.3;
    } else if ($zs['name'] == '涅磐兽（午）') {
        $lv = 0.15;
    } else if ($zs['name'] == '涅磐兽（亥）') {
        $lv = 0.05;
    }
    //if($a['name'] == '小神龙琅玡' || $a['name'] == '★青龙★' || $a['name'] == '★破天虎★' || $a['name'] == '白虎' || $a['name'] == '★龙蛇玄武★' || $a['name'] == '圣兽赤牝鹿' || $a['name'] == '蝶·影娅瑟' || $a['name'] == '尤佳娜' || $a['name'] == 'GM-鸭子' || $a['name'] == '忍者小乌龟' || $a['name'] == '囧娃娃' || $a['name'] == '蜡笔妹妹' || $a['name'] == '四叶草宝宝')
    $zs1 = explode(",", $zsarr['zs1']);
    $zs2 = explode(",", $zsarr['zs2']);
    $zs3 = explode(",", $zsarr['zs3']);
    if (in_array($a['name'], $zs1)) {
        if ($a['czl'] >= 1.0 && $a['czl'] <= 10.9) {
            $num1 = 1;
            $num2 = 200;
        } else if ($a['czl'] > 10.9 && $a['czl'] <= 30.9) {
            $num1 = 1;
            $num2 = 250;
        } else if ($a['czl'] > 30.9 && $a['czl'] <= 49.9) {
            $num1 = 1;
            $num2 = 350;
        } else if ($a['czl'] > 49.9 && $a['czl'] <= 60.9) {
            $num1 = 1;
            $num2 = 480;
        } else if ($a['czl'] > 60.9 && $a['czl'] <= 70.9) {
            $num1 = 1;
            $num2 = 600;
        } else if ($a['czl'] > 70.9 && $a['czl'] <= 80.9) {
            $num1 = 1;
            $num2 = 800;
        } else if ($a['czl'] > 80.9 && $a['czl'] <= 90.9) {
            $num1 = 2;
            $num2 = 1200;
        } else if ($a['czl'] > 90.9) {
            $num1 = 2;
            $num2 = 2200;
        }
    } else if (in_array($a['name'], $zs2)) {//else if($a['name'] == '熊猫orz宝宝' || $a['name'] == '火羽凤凰' || $a['name'] == '雪羽凤凰' || $a['name'] == '蛇女美杜莎')
        if ($a['czl'] >= 1.0 && $a['czl'] <= 10.9) {
            $num1 = 1;
            $num2 = 190;
        } else if ($a['czl'] > 10.9 && $a['czl'] <= 30.9) {
            $num1 = 1;
            $num2 = 240;
        } else if ($a['czl'] > 30.9 && $a['czl'] <= 49.9) {
            $num1 = 1;
            $num2 = 340;
        } else if ($a['czl'] > 49.9 && $a['czl'] <= 60.9) {
            $num1 = 1;
            $num2 = 470;
        } else if ($a['czl'] > 60.9 && $a['czl'] <= 70.9) {
            $num1 = 1;
            $num2 = 590;
        } else if ($a['czl'] > 70.9 && $a['czl'] <= 80.9) {
            $num1 = 1;
            $num2 = 780;
        } else if ($a['czl'] > 80.9 && $a['czl'] <= 90.9) {
            $num1 = 2;
            $num2 = 1100;
        } else if ($a['czl'] > 90.9) {
            $num1 = 2;
            $num2 = 1800;
        }
    } else if (in_array($a['name'], $zs3)) {//else if($a['name'] == '★寒江雪★' || $a['name'] == '寒江雪宝宝' || $a['name'] == '自然女神·影' || $a['name'] == '暗夜女神·影')
        if ($a['czl'] >= 1.0 && $a['czl'] <= 10.9) {
            $num1 = 1;
            $num2 = 180;
        } else if ($a['czl'] > 10.9 && $a['czl'] <= 30.9) {
            $num1 = 1;
            $num2 = 230;
        } else if ($a['czl'] > 30.9 && $a['czl'] <= 49.9) {
            $num1 = 1;
            $num2 = 330;
        } else if ($a['czl'] > 49.9 && $a['czl'] <= 60.9) {
            $num1 = 1;
            $num2 = 450;
        } else if ($a['czl'] > 60.9 && $a['czl'] <= 70.9) {
            $num1 = 1;
            $num2 = 570;
        } else if ($a['czl'] > 70.9 && $a['czl'] <= 80.9) {
            $num1 = 1;
            $num2 = 760;
        } else if ($a['czl'] > 80.9 && $a['czl'] <= 90.9) {
            $num1 = 2;
            $num2 = 1000;
        } else if ($a['czl'] > 90.9) {
            $num1 = 2;
            $num2 = 1500;
        }
    }
    //主宠物成长+{[(主宠物等级/主宠物成长./2)+(副宠物等级*副宠物成长/1500)]*(100%+涅盘兽百分比+道具百分比)}
	$czl =$a['czl'] + round(((($a['level'] / $a['czl'] / $num1) + ($b['level'] * $b['czl'] / $num2)) * (1 + $lv + $pp1 + $pp2)),1);
	//echo $a['czl'].'+round(((('.$a['level'].'/'.$a['czl'].'/'.$num1.')+('.$b['level'].'*'.$b['czl'].'/'.$num2.'))*(1+'.$lv.'+'.$pp1.'+'.$pp2.')),1)'.'<br />';
	return $czl;
}


/*
*@Usage:计算合成后的宠物单一属性。
* a,b,p=> $props attrib.
*@Return: int.
*@Memo 属性=[宠物资料数据库属性+取整（主怪物属性*主宠物等级/400）+取整（副怪物属性*副宠物等级/800）]*(100%+道具附加属性%)
*/
function getPa($old, $a, $b, $p)
{    //echo $p.'<br />';
    global $app, $bpp;
    if ($p == '' || $p <= 0) $p = 1;
    else $p = 1 + $p;
    $res = intval(($old + (intval($a * $app['level'] / 400) + intval($b * $bpp['level'] / 800))) * $p);

    return $res;
}


/**
 * @Usage: 删除添加到合成中的材料。
 * @Param:  void(0)
 * @Return: void(0)
 */
function delProps()
{
	global $pp1, $pp2, $_pm;
    if (is_array($pp1)) {
        if (!$_pm['mysql']->query("UPDATE userbag
								 SET sums=sums-1
						       WHERE id={$pp1['id']} and uid={$_SESSION['id']} and sums > 0
							") || mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
			return false;
		}
    }
	if (!empty($pp2)) {
		if (!$_pm['mysql']->query("UPDATE userbag SET sums=sums-1 WHERE id={$pp2['id']} and uid={$_SESSION['id']} and sums>0") ||
			mysql_affected_rows($_pm['mysql']->getConn()) != 1) {
			return false;
		}
	}
	return true;
}

?>
