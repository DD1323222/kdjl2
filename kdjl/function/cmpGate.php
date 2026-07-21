<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.07.30
*@Update Date:
*@Usage: 宠物合成系统。
*@Memo: 宠物合成系统。
:属性=[宠物资料数据库属性+取整（主怪物属性5%）+取整（副怪物属性1%）]*(100%+道具附加属性%)
:实际成长率=取1位小数{[取一位小数（主宠物成长*90%）+取一位小数（副宠物成长*10%）]* (100%+道具附加属性%)}
*/
session_start();
require_once('../config/config.game.php');
secStart($_pm['mem']);
require_once('../sec/dblock_fun.php');
$_pm['mysql']->addColumnIfMissing('player_ext', 'hecheng_nums', 'int(11) null default 0');
$cmpUid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
$cmpTransactionActive = false;
$cmpLockHeld = false;
$cmpPendingLogId = 0;

function cmpShutdown()
{
	global $_pm, $cmpTransactionActive, $cmpLockHeld, $cmpPendingLogId;
	$error = error_get_last();
	if(!is_array($error) || !in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true))
	{
		return;
	}
	if($cmpTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$cmpTransactionActive = false;
	}
	if($cmpPendingLogId > 0)
	{
		$_pm['mysql']->query('DELETE FROM gamelog WHERE id='.intval($cmpPendingLogId));
		$cmpPendingLogId = 0;
	}
	if($cmpLockHeld && function_exists('realseLock'))
	{
		realseLock();
		$cmpLockHeld = false;
	}
}
register_shutdown_function('cmpShutdown');

if($cmpUid < 1) die('11');
$a = getLock($cmpUid);
if(!is_array($a)){
	realseLock();
	die('11');
}
$cmpLockHeld = true;
function cmpRollback($code)
{
	global $_pm, $cmpTransactionActive, $cmpLockHeld, $cmpPendingLogId;
	if($cmpTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$cmpTransactionActive = false;
	}
	if($cmpPendingLogId > 0)
	{
		$_pm['mysql']->query('DELETE FROM gamelog WHERE id='.intval($cmpPendingLogId));
		$cmpPendingLogId = 0;
	}
	if($cmpLockHeld)
	{
		realseLock();
		$cmpLockHeld = false;
	}
	die($code);
}
$ap	    = (isset($_REQUEST['ap']) && !is_array($_REQUEST['ap'])) ? intval($_REQUEST['ap']) : 0;  // table userbb->id
$bp 	= (isset($_REQUEST['bp']) && !is_array($_REQUEST['bp'])) ? intval($_REQUEST['bp']) : 0;  // table userbb->id
$p1 	= (isset($_REQUEST['p1']) && !is_array($_REQUEST['p1'])) ? intval($_REQUEST['p1']) : 0;  // table userbag->id
$p2 	= (isset($_REQUEST['p2']) && !is_array($_REQUEST['p2'])) ? intval($_REQUEST['p2']) : 0;  // table userbag->id
$type = (isset($_GET['type']) && !is_array($_GET['type'])) ? $_GET['type'] : '';
$type1 = (isset($_GET['type1']) && !is_array($_GET['type1'])) ? $_GET['type1'] : '';
$srctime = 5;
if ($p1<0) $p1 = 0;
if ($p2<0) $p2 = 0;
if($ap < 0 || $bp < 0){
	realseLock();
	die();
}
#################增加一个间隔时间################
$time = isset($_SESSION['time'.$_SESSION['id']]) ? $_SESSION['time'.$_SESSION['id']] : 0;
if(empty($time))
{
	$_SESSION['time'.$_SESSION['id']] = time();
}
else
{
	$nowtime = time();
	$ctime = $nowtime - $time;
	if($ctime < $srctime && $type != 'do' && $type1 != 'check')
	{
		realseLock();
		die("11");//没有达到间隔时间
	}
	else
	{
		$_SESSION['time'.$_SESSION['id']] = time();
	}
}




#################是否选了护宠仙石结束############
if($type1 != 'check') //判断一次就够了
{
	$sql_props = 'SELECT pid FROM userbag WHERE (id='.$p1.' or id='.$p2.') and uid='.$_SESSION['id'].' and (cantrade IS NULL OR cantrade<>3)';
	$props = $_pm['mysql'] -> getRecords($sql_props);
	if(is_array($props))
	{
		$check_props = 0;
		foreach ($props as $key_props => $key_value)//Array ( [pid] => 771 )
		{
			$a = 'SELECT effect FROM props WHERE varyname=8 and id='.$key_value['pid'];
			$cmpProps = $_pm['mysql'] -> getOneRecord($a);
			if(is_array($cmpProps))//Array ( [effect] => hecheng:A:10%,B:4%|addczl:8%|1 )
			{
				$key_values = substr($cmpProps['effect'],-1,1);
				if($key_values == '1')
				{
					$check_props = $check_props+1;
				}
			}

		}
		if($check_props == 0)
		{
			realseLock();
			die('200');
		}
	}
	else
	{
		realseLock();
		die('200');
	}
}

##################增加在这里结束#################
$zbcheck = $_pm['mysql'] -> getRecords("SELECT id FROM userbag WHERE uid={$_SESSION['id']} and sums>0 and (zbpets = $ap or zbpets = $bp)");
if(is_array($zbcheck) && count($zbcheck) >= 1){
	realseLock();
	die('1000');
}


/*if(lockItem($ap) === false)
{
	die('已经在处理了！');
}*/
if ($ap<0 || $bp<0) {
	realseLock();
	die('0');
}




$user		= $_pm['user']->getUserById($_SESSION['id']);//一个用户的所有信息player
$userbb		= $_pm['mysql']->getRecords('SELECT * FROM userbb WHERE uid='.$cmpUid.' AND id IN ('.$ap.','.$bp.') FOR UPDATE');
$app = array();
$bpp = array();
$pp1 = array();
$pp2 = array();
if(!empty($p1))
{
	$pp1 = $_pm['user']->getUserItemById($_SESSION['id'],$p1);//道具一资料userbag
	if(!is_array($pp1) || $pp1['sums'] < 1 || (isset($pp1['cantrade']) && intval($pp1['cantrade']) == 3)){
		realseLock();
		die('20');
	}
}
if(!empty($p2))
{
	$pp2 = $_pm['user']->getUserItemById($_SESSION['id'],$p2);//道具二资料userbag
	if(!is_array($pp2) || $pp2['sums'] < 1 || (isset($pp2['cantrade']) && intval($pp2['cantrade']) == 3)){
		realseLock();
		die('20');
	}
}

$log = '';

if ( is_array($userbb))
{
	foreach ($userbb as $key => $rs)
	{
		$rs['muchang'] = isset($rs['muchang']) ? intval($rs['muchang']) : 0;
		$rs['tgflag'] = isset($rs['tgflag']) ? intval($rs['tgflag']) : 0;
		if ($rs['id']==$ap && $rs['level']>=40 && $rs['muchang']==0 && $rs['tgflag']==0) // From bb base find user current bb.
		{
			$app = $rs;//主宠信息（数组）userbb
		}
		if ($rs['id']== $bp && $rs['level']>=40 && $rs['muchang']==0 && $rs['tgflag']==0)
		{
			$bpp = $rs;//副宠信息（数组）userbb
		}
	}
    unset($rs);

	if($p1 == $p2 && $p1 != 0)
	{
		if($pp1['sums'] < 2)
		{
			realseLock();
			die("100");
		}
	}

	if (!is_array($app) || !is_array($bpp) || ($app['id'] == $bpp['id'])) {
		realseLock();
		die('1'); //没有对应的宠物。
	}

	$cishu=$_pm['mysql']->getOneRecord("select hecheng_nums,chouqu_chongwu from player_ext where uid={$_SESSION['id']}");
	if($err=mysql_error($_pm['mysql']->getConn()))
	{
		if(strpos($err,'hecheng_nums')!==false)
		{
			cmpRollback('玩家扩展数据结构错误！');
		}
	}
	if(is_array($cishu) && isset($cishu['chouqu_chongwu']) && (strpos($cishu['chouqu_chongwu'],','.$app['id'].',')!==false||strpos($cishu['chouqu_chongwu'],','.$bpp['id'].',')!==false))
	{
		realseLock();
		die("某个宠物抽取过成长,不能进行合成!");
	}

	// 检查是否满足公式。
	//$ars = $_pm['mem']->dataGet(array('k' => MEM_BB_KEY,
	//									 'v' => "if(\$rs['name'] == '{$app['name']}') \$ret=\$rs;"//bb
	//						  ));
	//$brs = $_pm['mem']->dataGet(array('k' => MEM_BB_KEY,
	//									 'v' => "if(\$rs['name'] == '{$bpp['name']}') \$ret=\$rs;"//bb
	//						  ));
	$membbname = kdjlSafeMemValue($_pm['mem']->get('db_bbname'), array());
	$membbid = kdjlSafeMemValue($_pm['mem']->get('db_bbid'), array());
	$ars = resolveBasePetForCombine($app, $membbname, $membbid);
	$brs = resolveBasePetForCombine($bpp, $membbname, $membbid);
	if (!is_array($ars) || !is_array($brs))
	{
		realseLock();
		die('2');
	}

	$mergeRows = $_pm['mysql']->getRecords("SELECT *
											FROM merge
										   WHERE aid = {$ars['id']} and bid={$brs['id']}
										ORDER BY id
	                                    ");
	$cmprs = false;
	$mergeFallback = false;
	if(is_array($mergeRows))
	{
		foreach($mergeRows as $mergeRow)
		{
			if(!is_array($mergeRow)) continue;
			$mergeLimits = isset($mergeRow['limits']) ? trim($mergeRow['limits']) : '';
			if($mergeLimits === '')
			{
				if($mergeFallback === false) $mergeFallback = $mergeRow;
				continue;
			}
			$mergeLimitParts = explode('|', $mergeLimits);
			$mainGrowthOk = empty($mergeLimitParts[0]) || $app['czl'] >= $mergeLimitParts[0];
			$subGrowthOk = !isset($mergeLimitParts[1]) || empty($mergeLimitParts[1]) || $bpp['czl'] >= $mergeLimitParts[1];
			if($mainGrowthOk && $subGrowthOk)
			{
				$cmprs = $mergeRow;
				break;
			}
		}
	}
	if(!is_array($cmprs) && is_array($mergeFallback)) $cmprs = $mergeFallback;
    if (!is_array($cmprs)) {
		realseLock();
		die(is_array($mergeRows) && !empty($mergeRows) ? '15' : '2');	//成长不足或不能合成
	}
	//检查是否有成长限制
	$max_czl = 0;
	if(!empty($cmprs['limits']))
	{
		$limitsarr = explode('|',$cmprs['limits']);
		if(!empty($limitsarr[0]) && $app['czl'] < $limitsarr[0])//主宠成长限制
		{
			realseLock();
			die('15');
		}
		if(!empty($limitsarr[1]) && $bpp['czl'] < $limitsarr[1])//副宠成长限制
		{
			realseLock();
			die('15');
		}
		if(!empty($limitsarr[1]) && count($limitsarr) == 3 )
		{
			$max_czl = $limitsarr[2];
		}
	}

	$money=$user['money'];
	if($user['money'] < 50000)
	{
		realseLock();
		die('3');//	金币不足
	}

	$propseff = getEffect($pp1, $pp2);//Array ( [0] => hecheng:A:1%,B:0% [1] => addczl:0% [2] => 1 [3] => hecheng:A:15%,B:3% [4] => addczl:20% [5] => 2 )

	$sus = getSuccess($app,$bpp,$pp1,$pp2);//成功率公式返回一个数字2->B宠 1->A宠
	//echo 'sus:'.$sus.'<br />';
	$czl = bbczl($app,$bpp,$pp1,$pp2);///获得成长率->一个百分小数23.2
	if($czl > $max_czl && $max_czl != 0)
	{
		$czl = $max_czl;
	}
//$sus = 1;
	$cmpTransactionActive = true;
	if ($sus) // 合成成功。a,b宠物消失，得到新的宠物。$cmprs=> 得到相关宝宝信息。
	{

			// 改变属性地方为:
		if ($sus == 1) $newbid = $cmprs['maid'];
		if ($sus == 2) $newbid = $cmprs['mbid'];
		//echo 'newbb:'.$newbid.'<br />';exit;
		$brs = $_pm['mysql']->getOneRecord("SELECT *
											  FROM  bb
											 WHERE id={$newbid}
											 LIMIT 0,1
										  ");

		if (!is_array($brs))
		{
			cmpRollback('10'); // 数据错误
		}
		// 改变各项数据:
		$newbbid = makebb($brs,$max_czl);
		if ($newbbid === false)
		{
			cmpRollback('10');
		}
		$cstatus = 2;
	}
	else // 如果没有相关道具进行绑定，副宠消失
	{


		$cstatus = 1;
	}

	if (!$_pm['mysql']->query("UPDATE player SET money=money-50000 WHERE id={$_SESSION['id']} and money>=50000") ||
		mysql_affected_rows($_pm['mysql']->getConn()) != 1)
	{
		cmpRollback('3');
	}
	// 记录日志：
	$log .= "合成结果：".($cstatus==1?"失败":"成功")."\n";
	$log .= "合成道具：1:".(isset($pp1['pid']) ? $pp1['pid'] : 0).' 2:'.(isset($pp2['pid']) ? $pp2['pid'] : 0)."\n";

	//######### del props Start.##################
	if (!delProps())
	{
		cmpRollback('20');
	}
	############# del props end.#####################
//$cstatus=1;
	if ($cstatus == 1) //副宠消失。合成失败
	{
		if (!$_pm['mysql']->query("INSERT INTO player_ext(uid,bbshow,hecheng_nums) VALUES({$cmpUid},5,1) ON DUPLICATE KEY UPDATE hecheng_nums=LEAST(COALESCE(hecheng_nums,0)+1,10)"))
		{
			cmpRollback('10');
		}

		$del = 1;
		$log .= '合成道具详细：';
		if(is_array($propseff))
		{
			if(!empty($pp1))
			{
				$log .= $pp1['name'].'-';
			}
			if(!empty($pp2))
			{
				$log .= $pp2['name'].'-';
			}
			//$pp1['name']$pp1['effect']

			//$log .= $n['shbb']."-";
			if ((isset($propseff[2]) && $propseff[2] == 1) || (isset($propseff[5]) && $propseff[5] == 1))
			{
				$del = 0;
			}
		}

		if ($del == 1)//副宠消失的条件
		{
			if (!clearBB($bpp))
			{
				cmpRollback('10');
			}
			$log .= 'name:'.$bpp['name'].'level:'.$bpp['level'].'czl:'.$bpp['czl'].'ac:'.$bpp['ac'].'srchp:'.$bpp['srchp'].'hits:'.$bpp['hits'];
		}
		$log = $_pm['mysql']->escape($log);
		if (!$_pm['mysql']->query("INSERT INTO gamelog(ptime,seller,buyer,pnote,vary)
		                      VALUES(unix_timestamp(),'{$_SESSION['id']}','{$_SESSION['id']}','{$log}',2)
							") || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
		{
			cmpRollback('10');
		}
		$cmpPendingLogId = intval(mysql_insert_id($_pm['mysql']->getConn()));
		if (!$_pm['mysql']->query('COMMIT'))
		{
			cmpRollback('10');
		}
		$cmpTransactionActive = false;
		$cmpPendingLogId = 0;
		$_pm['mem']->del(MEM_USER_KEY);
		$_pm['mem']->del(MEM_USERBB_KEY);
		$_pm['mem']->del(MEM_USERSK_KEY);
		$_pm['mem']->del(MEM_USERBAG_KEY);
		// 合成失败记录点：
		realseLock();
		$cmpLockHeld = false;
		die('6');
	}
	else if($cstatus == 2) // 成功。
	{
		if (!$_pm['mysql']->query("INSERT INTO player_ext(uid,bbshow,hecheng_nums) VALUES({$cmpUid},5,0) ON DUPLICATE KEY UPDATE hecheng_nums=0") ||
			!clearBB($app) || !clearBB($bpp))
		{
			cmpRollback('10');
		}
		/*
		$_pm['mem']->set(array('k'=>MEM_SYSWORD_KEY,
							   'v'=>'[系统公告]恭喜玩家 '.$user['nickname'].'成功的合成了一只['.$cmprs['name'].'],真是太幸运了!'));
		*/
		$msg_key = 'chatMsgList';
		$nowMsgList = kdjlSafeMemValue($_pm['mem']->get($msg_key), '');
		if(!is_string($nowMsgList)) $nowMsgList = '';
		$arr = explode('linend', $nowMsgList);
		if( count($arr)>20 ) // cear old
		{
			$arrt = array_shift($arr);
		}
		$newstr = '<font color=red>[系统公告]恭喜玩家 '.$user['nickname'].' 成功的合成了一只['.$brs['name'].'],真是太幸运了!</font>';
		$newbbarr = $_pm['mysql'] -> getOneRecord("SELECT level,czl,ac,hits,srchp FROM userbb WHERE id={$newbbid} and uid={$_SESSION['id']} LIMIT 1");
		if(!is_array($newbbarr)) $newbbarr = array();
		$newbbarr['level'] = isset($newbbarr['level']) ? $newbbarr['level'] : 0;
		$newbbarr['czl'] = isset($newbbarr['czl']) ? $newbbarr['czl'] : 0;
		$newbbarr['ac'] = isset($newbbarr['ac']) ? $newbbarr['ac'] : 0;
		$newbbarr['hits'] = isset($newbbarr['hits']) ? $newbbarr['hits'] : 0;

		$p1name = isset($pp1['name']) ? $pp1['name'] : '';
		$p2name = isset($pp2['name']) ? $pp2['name'] : '';
		$str = '新宠物名字：'.$brs['name'].'level:'.$newbbarr['level'].'czl:'.$newbbarr['czl'].'ac:'.$newbbarr['ac'].'hits:'.$newbbarr['hits'].',使用物品1：'.$p1name.',使用物品2：'.$p2name.',宠物：'.$app['name'].'level:'.$app['level'].'czl:'.$app['czl'].'ac:'.$app['ac'].'hits:'.$app['hits'].'-'.$bpp['name'].'level:'.$bpp['level'].'czl:'.$bpp['czl'].'ac:'.$bpp['ac'].'hits:'.$bpp['hits'];
		$strSql = $_pm['mysql']->escape($str);
		if (!$_pm['mysql']->query("INSERT INTO gamelog(ptime,seller,buyer,pnote,vary)
		                      VALUES(unix_timestamp(),'{$_SESSION['id']}','{$_SESSION['id']}','{$strSql}',4)
							") || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
		{
			cmpRollback('10');
		}
		$cmpPendingLogId = intval(mysql_insert_id($_pm['mysql']->getConn()));
		if (!$_pm['mysql']->query('COMMIT'))
		{
			cmpRollback('10');
		}
		$cmpTransactionActive = false;
		$cmpPendingLogId = 0;
		$_pm['mem']->del(MEM_USER_KEY);
		$_pm['mem']->del(MEM_USERBB_KEY);
		$_pm['mem']->del(MEM_USERSK_KEY);
		$_pm['mem']->del(MEM_USERBAG_KEY);

		$retstr = '';
		foreach($arr as $k=>$v)
		{
			$retstr .= $v.'linend';
		}

		$retstr = $retstr.$newstr;
		$_pm['mem']->set( array('k'=>$msg_key, 'v'=>$retstr) ); // default ten min.

		//----------------------------------------------------------------------------------------------------------------------
		//$_olddata = @unserialize($_pm['mem']->get('ttmt_data_notice'));
		require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
		$s=new socketmsg();
		$s->sendMsg('an|'.$newstr);

		//$_olddata['an'] = isset($_olddata['an'])?$_olddata['an']."<br/>[系统公告]：".$swfData:$swfData;
		//$_pm['mem']->set(array('k'=>'ttmt_data_notice','v'=>$_olddata));
		//----------------------------------------------------------------------------------------------------------------------


		realseLock();
		$cmpLockHeld = false;
		die('5');
	}
}
else {
	realseLock();
	die('000');
}
realseLock();
$_pm['mem']->memClose();
// Logic code end.



/**
* @Usage: 创建新的宠物。
* @Param: array -> $bb.
* @Return: Void(0);
*/
function makebb($bb,$max_czl)
{$czl=0;
//echo "\r\n";
	global $app,$bpp,$pp1,$pp2,$user,$_pm,$propseff;
	$czl = bbczl($app,$bpp,$pp1,$pp2);
	$ac=getPlus($propseff,'ac');
	$mc=getPlus($propseff,'mc');
	$hit=getPlus($propseff,'hit');
	$miss=getPlus($propseff,'miss');
	$speed=getPlus($propseff,'speed');
	$hp=getPlus($propseff,'hp');
	$mp=getPlus($propseff,'mp');

	// ac,luck,mc,hit,miss,speed,hp,mp,shbb,czl;
	$bb['ac']	= getPa($bb['ac'], $app['ac'], $bpp['ac'] ,getPlus($propseff,'ac'));#### 暂时没有加入道具附加属性。
    $bb['mc']	= getPa($bb['mc'], $app['mc'], $bpp['mc'] ,getPlus($propseff,'mc'));
	$bb['hits']	= getPa($bb['hits'], $app['hits'], $bpp['hits'] ,getPlus($propseff,'hit'));
    $bb['miss']	= getPa($bb['miss'], $app['miss'], $bpp['miss'] ,getPlus($propseff,'miss'));
	$bb['speed']= getPa($bb['speed'], $app['speed'], $bpp['speed'] ,getPlus($propseff,'speed'));
	$bb['hp']	= getPa($bb['hp'], $app['srchp'], $bpp['srchp'] ,getPlus($propseff,'hp'));
	$bb['mp']	= getPa($bb['mp'], $app['srcmp'], $bpp['srcmp'] ,getPlus($propseff,'mp'));

	$uinfo = $user;
	$usernameSql = $_pm['mysql']->quote(isset($uinfo['nickname']) ? $uinfo['nickname'] : '');
	if($bb['wx']==6 && $czl>60)
	{
		$czl=60;
	}
	else if($bb['wx']!=6 && $czl>150)
	{
		$czl=150;
	}
	if($max_czl != 0 && $czl > $max_czl)
	{
		$czl = $max_czl;
	}
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
								   effectimg,
								   old_bid
								  )
				VALUES(
					   '{$bb['name']}',
					   '{$uinfo['id']}',
					   {$usernameSql},
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
						   'q{$bb['id']}.gif',
						   '{$bb['id']}'
					   )
			  ");
	if (!$inserted)
	{
		return false;
	}
	$bbid = intval($_pm['mysql']->last_id());
	if ($bbid <= 0)
	{
		return false;
	}

	$jnall = explode(",", $bb['skillist']);//1:1,60:1

	$membbname = kdjlSafeMemValue($_pm['mem']->get('db_skillsysid'), array());

	foreach($jnall as $a => $b)
	{
		$arr = explode(":", $b);
		if (!isset($arr[0]) || !isset($arr[1]) || !isset($membbname[$arr[0]]))
		{
			return false;
		}
		// Get jn info.

		//$memjnid = $this->m_m->unserialize(get('db_skillsysid'));
		$jn = $membbname[$arr[0]];
		/*$jn = $_pm['mem']->dataGet(array('k'	=>	MEM_SKILLSYS_KEY,
								'v'	=>  "if(\$rs['id'] == '{$arr[0]}') \$ret=\$rs;"
						));*/
		$ack  = explode(",", $jn['ackvalue']);
		$plus = explode(",", $jn['plus']);
		$uhp  = explode(",", $jn['uhp']);
		$ump  = explode(",", $jn['ump']);
		$img  = explode(",", $jn['imgeft']);
		$skillLevel = intval($arr[1]);
		$skillIndex = $skillLevel - 1;
		if(!isset($ack[$skillIndex]) || !isset($uhp[$skillIndex]) || !isset($ump[$skillIndex]))
		{
			return false;
		}

		if (!$_pm['mysql']->query("INSERT INTO skill(bid,name,level,vary,wx,value,plus,img,uhp,ump,sid)
					VALUES(
						   '{$bbid}',
						   '{$jn['name']}',
						   '{$skillLevel}',
						   '{$jn['vary']}',
						   '{$jn['wx']}',
						   '{$ack[$skillIndex]}',
						   '".(isset($plus[$skillIndex]) ? $plus[$skillIndex] : '')."',
						   '".(isset($img[$skillIndex]) ? $img[$skillIndex] : '')."',
						   '".intval($uhp[$skillIndex])."',
						   '".intval($ump[$skillIndex])."',
						   '{$jn['id']}'
						  )
				  "))
		{
			return false;
		}

   }
	if (!$_pm['mysql'] -> query("UPDATE player SET mbid = {$bbid},fightbb = {$bbid} WHERE id = {$_SESSION['id']}") ||
		mysql_affected_rows($_pm['mysql']->getConn()) != 1)
	{
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
	//return;
	global $_pm,$log;
	$id = $bb['id'];

	foreach ($bb as $k => $v)
	{
		$log .= $k.'=>'.$v.'-';
	}

	// del sk.
	if (!$_pm['mysql']->query("DELETE FROM skill
				 WHERE bid={$id}
			  "))
	{
		return false;
	}

	// del zb.
	if (!$_pm['mysql']->query("DELETE FROM userbag
				 WHERE uid={$_SESSION['id']} and zbpets={$id}
			  "))
	{
		return false;
	}
	// del bb.
	if (!$_pm['mysql']->query("DELETE FROM userbb
				 WHERE uid={$_SESSION['id']} and id={$id}
			  "))
	{
		return false;
	}
	return mysql_affected_rows($_pm['mysql']->getConn()) == 1;
}

/**
* @Param: 宠物a,b的属性。
* @Return: 返回组合后的成长率。

成长段		对应公式
51.0(不包括51.0)以下	主宠物成长+{[(主宠物等级/(主宠物成长+10))+(副宠物等级*副宠物成长/200)]*(100%+道具百分比)}
51.0(包括51.0)—70.0	主宠物成长+{[(主宠物等级/主宠物成长)+(副宠物等级*副宠物成长/350)]*(100%+道具百分比)}
70.0(包括70.0)—90.0	主宠物成长+{[(主宠物等级/主宠物成长)+(副宠物等级*副宠物成长/500)]*(100%+道具百分比)}
90.0(包括90.0)—100.0	主宠物成长+{[(主宠物等级/主宠物成长)+(副宠物等级*副宠物成长/700)]*(100%+道具百分比)}
100(包括100.0)以上	主宠物成长+{[(主宠物等级/主宠物成长)+(副宠物等级*副宠物成长/900)]*(100%+道具百分比)}

*/
function bbczl($a, $b, $pp1, $pp2)
{
	global $brs; // 资料库中宠物属性。
	$arr = 0;
	$a['czl'] = isset($a['czl']) ? max(0.1, floatval($a['czl'])) : 0.1;
	$b['czl'] = isset($b['czl']) ? max(0, floatval($b['czl'])) : 0;
	$a['level'] = isset($a['level']) ? intval($a['level']) : 1;
	$b['level'] = isset($b['level']) ? intval($b['level']) : 1;

	if (is_array($pp1))
	{
		$one = explode('|', $pp1['effect']);
		if(isset($one[1]))
		{
			$arr_11 = explode(':', $one[1]);
			if(isset($arr_11[1]) && $arr_11[0]=='addczl')
			{
				$arr_1 = str_replace('%','',$arr_11[1]);
				$arr = $arr_1/100;
			}
		}
	}
	unset($one,$arr_11,$arr_1);
	if (is_array($pp2))
	{
		$one = explode('|', $pp2['effect']);
		if(isset($one[1]))
		{
			$arr_11 = explode(':', $one[1]);
			if(isset($arr_11[1]) && $arr_11[0]=='addczl')
			{
				$arr_1 = str_replace('%','',$arr_11[1]);
				$arr += $arr_1/100;
			}
		}

	}
	unset($one,$arr_11,$arr_1);

	if($a['czl']<51.0)
	{
	$czl=round($a['czl']+($a['level']/($a['czl']+10)+$b['level']*$b['czl']/200)*(1+$arr),1);//23.2
	return $czl;
	}
	if($a['czl']<70.0)
	{
	$czl=round($a['czl']+($a['level']/$a['czl']+$b['level']*$b['czl']/350)*(1+$arr),1);
	return $czl;
	}
	if($a['czl']<90.0)
	{
	$czl=round($a['czl']+($a['level']/$a['czl']+$b['level']*$b['czl']/500)*(1+$arr),1);
	return $czl;
	}
	if($a['czl']<100.0)
	{
	$czl=round($a['czl']+($a['level']/$a['czl']+$b['level']*$b['czl']/700)*(1+$arr),1);
	return $czl;
	}
	if($a['czl']>=100.0)
	{
	$czl=round($a['czl']+($a['level']/$a['czl']+$b['level']*$b['czl']/900)*(1+$arr),1);
	return $czl;
	}
	//return $czl;
}

/**
*@Usage: 获取合成中添加道具的所有效果=》为一个6个元素数组
*@Return: array.
*/
function getEffect($pp1, $pp2)
{
	$one1 = array();
	if (is_array($pp1))
	{
		$one = explode('|', $pp1['effect']);
		foreach ($one as $a => $b)
		{
			$one1[] = $b;
		}
		unset($one);
	}
	if (is_array($pp2))
	{
		$one = explode('|', $pp2['effect']);
		foreach ($one as $a => $b)
		{
			$one1[] = $b;
		}
		unset($one);
	}
	// 组合效果。
	return $one1;

}


/**
* @Usage: 返回单一效果。
* @Param: string->$vary, array->$value.
* @Return: array.
*/
function getvary($vary, $value)//hecheng:A:15%|B:3%|addspeed:15%|2
{
	$ret = array();
	switch($vary)
	{   // ac,luck,mc,hit,miss,speed,hp,mp,shbb,czl;  hecheng:A:15%,B:3%|addspeed:15%|2
	//$value[1]   0.15 $ret['ac'] = 0.15   $ret['hp'] = 0.15  $ret=array();
		case "addac": if(isset($value[1])) $ret['ac'] = str_replace('%','',$value[1])/100;break;
		case "luck": if(isset($value[1]) && isset($value[2])) $ret['luck'] = $value['1'].':'.(str_replace('%','',$value[2])/100);break;
		case "addmc": if(isset($value[1])) $ret['mc'] = str_replace('%','',$value[1])/100;break;
		case "addhit": if(isset($value[1])) $ret['hit'] = str_replace('%','',$value[1])/100;break;
		case "addmiss": if(isset($value[1])) $ret['miss'] = str_replace('%','',$value[1])/100;break;
		case "addspeed": if(isset($value[1])) $ret['speed'] = str_replace('%','',$value[1])/100;break;
		case "addhp": if(isset($value[1])) $ret['hp'] = str_replace('%','',$value[1])/100;break;
		case "addmp": if(isset($value[1])) $ret['mp'] = str_replace('%','',$value[1])/100;break;
		case "addczl": if(isset($value[1])) $ret['czl'] = str_replace('%','',$value[1])/100;break;
		case "B": if(isset($value[1])) $ret['B'] = str_replace('%','',$value[1])/100;break;
		case "shbb": $ret['shbb'] = true;break;
	}
	return $ret;
}

/*
公式：
新合成成功公式为(取1位小数)：		[合成次数/(主宠成长*2)]+[(主宠等级+副宠等级)/15]*0.01+(道具百分比)+[(随机1~5)*0.01]
合成判断成功后，先随机：B阶成功百分比（默认5%）+（B阶道具百分比） 成功后合成为稀有宠（B）			失败后合成为普通宠(A)
*/
function getSuccess($app,$bpp,$pp1,$pp2)
{
	global $_pm;
	$arr2 = 0;
	$arr4 = 0;

	foreach (array($pp1, $pp2) as $pp)
	{
		if (!is_array($pp) || !isset($pp['effect']) || $pp['effect'] == '')
		{
			continue;
		}

		$one = explode('|', $pp['effect']);
		$arr = explode(',', $one[0]);
		if (isset($arr[0]))
		{
			$arr_2 = explode(':', $arr[0]);
			if (isset($arr_2[2]))
			{
				$arr2 += floatval(str_replace('%', '', $arr_2[2])) / 100;
			}
		}
		if (isset($arr[1]))
		{
			$arr_3 = explode(':', $arr[1]);
			if (isset($arr_3[1]))
			{
				$arr4 += floatval(str_replace('%', '', $arr_3[1])) / 100;
			}
		}
	}

	$nums="select hecheng_nums from player_ext where uid={$_SESSION['id']}";
	$cishu = $_pm['mysql']->getOneRecord($nums);
	if($err=mysql_error($_pm['mysql']->getConn()))
	{
		if(strpos($err,'hecheng_nums')!==false)
		{
			$_pm['mysql']->addColumnIfMissing('player_ext', 'hecheng_nums', 'int(11) null default 0');
			$cishu = $_pm['mysql']->getOneRecord($nums);
		}
	}

	$stars = 0;
	if (is_array($cishu) && isset($cishu['hecheng_nums']))
	{
		$stars = intval($cishu['hecheng_nums']);
	}
	if ($stars < 0) $stars = 0;

	$appCzl = isset($app['czl']) ? floatval($app['czl']) : 0;
	if($stars >= 10 || $appCzl <= 5)// 10 lucky stars guarantee success
	{
		$success = 1.0;
	}
	else
	{
		$appLevel = isset($app['level']) ? intval($app['level']) : 0;
		$bppLevel = isset($bpp['level']) ? intval($bpp['level']) : 0;
		$chenggonglv = ($stars / ($appCzl * 2)) + (($appLevel + $bppLevel) / 15) * 0.01 + $arr2 + (rand(1,5) * 0.01);
		$success = $chenggonglv;
		if($success < 0) $success = 0;
		if($success > 1) $success = 1.0;
	}

	$a=rand(1,10000)/10000;
	if($a<=$success)// 合成成功
	{
		$chance=0.05+$arr4;
		if($chance < 0) $chance = 0;
		if($chance > 1) $chance = 1.0;
		$chance_rand=rand(1,10000)/10000;
		if($chance_rand<=$chance)
		{
			return 2;
		}
		else
		{
			return 1;
		}
	}
	else// fail
	{
		return false;
	}
}
/*
*@Usage:计算合成后的宠物单一属性。
* a,b,p=> $props attrib.
*@Return: int.
*@Memo 属性=取整{[宠物资料数据库属性+取整（主宠物属性*主宠物等级/400）+取整（副宠物属性*副宠物等级/800）]*(100%+道具附加属性%)}
*/
function getPa($old, $a, $b ,$p)
{
	global $app,$bpp;
	if ($p == '' || $p<=0) $p=1;
	else $p = 1+$p;

	return intval(($old+(intval($a*$app['level']/400)+intval($b*$bpp['level']/800)))*$p);
}


/**
*@Usage: 获得合成加入道
具的各项属性值。
*@ Return: float.
*/
function getPlus($parr,$a)//Array([0] => hecheng:A:15%,B:3% [1] => addczl:20% [2] => 2 [3] => hecheng:A:15%,B:3% [4] => addczl:20% [5] => 2)
{
	$czl1 = 0;
	$czl2 = 0;
	$czl = 0;

	if (!is_array($parr)) return 0;
	if (!isset($parr[1])) return 0;
	$arr = explode(':',$parr[1]);//$arr[0]=addczl $arr[1]=15%
	if (!isset($arr[1])) return 0;
	$arr2 = substr($arr[0], 3); //czl mp cp
	$arr1 = array('', 0);
	$arr3 = '';
	if(count($parr)==6)
	{
		if (isset($parr[4]))
		{
			$arr1 = explode(':',$parr[4]);
			if (isset($arr1[1])) $arr3 = substr($arr1[0], 3); //czl mp cp
		}
	}
	switch ($arr2)
			{
				case "czl":
					if($a=='czl')
					{
						$czl1 = str_replace('%','',$arr[1])/100;//$arr[1]=0.15最终要返回这个数字
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					else
					{
						$czl1=0;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					break;
				case "ac":
					if($a=='ac')
					{
						$czl1 = str_replace('%','',$arr[1])/100;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					else
					{
						$czl1=0;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					break;
				case "mc":
					if($a=='mc')
					{
						$czl1 = str_replace('%','',$arr[1])/100;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					else
					{
						$czl1=0;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					break;
				case "hit":
					if($a=='hits')
					{
						$czl1 = str_replace('%','',$arr[1])/100;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					else
					{
						$czl1=0;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					break;
				case "miss":
					if($a=='miss')
					{
						$czl1 = str_replace('%','',$arr[1])/100;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					else
					{
						$czl1=0;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					break;
				case "speed":
					if($a=='speed')
					{
						$czl1 = str_replace('%','',$arr[1])/100;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					else
					{
						$czl1=0;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					break;
				case "hp":
					if($a=='hp')
					{
						$czl1 = str_replace('%','',$arr[1])/100;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					else
					{
						$czl1=0;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					break;
				case "mp":
					if($a=='mp')
					{
						$czl1 = str_replace('%','',$arr[1])/100;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					else
					{
						$czl1=0;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					break;
			}
			switch ($arr3)
			{
				case "czl":
					if($a=='czl')
					{
							$czl2 = str_replace('%','',$arr1[1])/100;
							$czl = $czl1+$czl2;
							return $czl;

					}
					else
					{
						return $czl1;
					}
					break;
				case "ac":
					if($a=='ac')
					{
							$czl2 = str_replace('%','',$arr1[1])/100;
							return $czl = $czl1+$czl2;
					}
					else
					{
						return $czl1;
					}
					break;
				case "mc":
					if($a=='mc')
					{
							$czl2 = str_replace('%','',$arr1[1])/100;
							return $czl = $czl1+$czl2;
					}
					else
					{
						return $czl1;
					}
					break;
				case "hit":
					if($a=='hits')
					{
							$czl2 = str_replace('%','',$arr1[1])/100;
							return $czl = $czl1+$czl2;
					}
					else
					{
						return $czl1;
					}
					break;
				case "miss":
					if($a=='miss')
					{
							$czl2 = str_replace('%','',$arr1[1])/100;
							return $czl = $czl1+$czl2;
					}
					else
					{
						return $czl1;
					}
					break;
				case "speed":
					if($a=='speed')
					{
							$czl2 = str_replace('%','',$arr1[1])/100;
							return $czl = $czl1+$czl2;
					}
					else
					{
						return $czl1;
					}
					break;
				case "hp":
					if($a=='hp')
					{
							$czl2 = str_replace('%','',$arr1[1])/100;
							return $czl = $czl1+$czl2;
					}
					else
					{
						return $czl1;
					}
					break;
				case "mp":
					if($a=='mp')
					{
							$czl2 = str_replace('%','',$arr1[1])/100;
							return $czl = $czl1+$czl2;
					}
					else
					{
						return $czl1;
					}
					break;
			}


	return $czl1;
}

/**
*@Usage: 删除添加到合成中的材料。
*@Param:  void(0)
*@Return: void(0)
*/
function delProps()
{
//return;
	global $pp1, $pp2, $_pm;	// props first,props second, global object array.
	$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
	if($uid < 1) return false;
	foreach(array($pp1, $pp2) as $prop)
	{
		if(!is_array($prop) || empty($prop) || !isset($prop['id']) || intval($prop['id']) < 1)
		{
			continue;
		}
		$bagId = intval($prop['id']);
		if (!$_pm['mysql']->query("UPDATE userbag
								 SET sums=sums-1
						       WHERE id={$bagId} and uid={$uid} and sums > 0 and zbing=0
						         and (cantrade IS NULL OR cantrade<>3)
							"))
		{
			return false;
		}
		//echo mysql_affected_rows($_pm['mysql'] -> getConn()).'<br />';
		if(mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){
			return false;
		}
		if (!$_pm['mysql']->query("DELETE FROM userbag
						       WHERE id={$bagId} and uid={$uid}
						         and sums<=0 and bsum<=0 and psum<=0 and pyb=0 and zbing=0
						         and (cantrade IS NULL OR cantrade<>3)"))
		{
			return false;
		}
	}
	return true;
}
function resolveBasePetForCombine($pet, $byName, $byId)
{
	if (isset($pet['old_bid']))
	{
		$oldBid = intval($pet['old_bid']);
		if ($oldBid > 0 && is_array($byId) && isset($byId[$oldBid]) && is_array($byId[$oldBid]))
		{
			return $byId[$oldBid];
		}
	}
	if (is_array($byId))
	{
		foreach ($byId as $basePet)
		{
			if (!is_array($basePet) || !isset($basePet['name'])) continue;
			if ($basePet['name'] != $pet['name']) continue;
			if ((string)$basePet['remakelevel'] == (string)$pet['remakelevel'] &&
				(string)$basePet['remakeid'] == (string)$pet['remakeid'] &&
				(string)$basePet['remakepid'] == (string)$pet['remakepid'])
			{
				return $basePet;
			}
		}
	}
	if (is_array($byName) && isset($byName[$pet['name']]) && is_array($byName[$pet['name']]))
	{
		return $byName[$pet['name']];
	}
	return false;
}
?>
