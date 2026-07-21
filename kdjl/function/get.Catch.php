<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.19
*@Update Date: 2008.10.28
*@Usage: study skill of user bb.
*@Memo:
  0: 数据错误
  捕捉功能方法修改。
*/

require_once('../config/config.game.php');
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('0');
$_SESSION['id'] = $uid;
define('MEM_FIGHT_KEY', $uid . 'fight');

$arrobj = new arrays();
secStart($_pm['mem']);

$bid = isset($_REQUEST['pid']) && !is_array($_REQUEST['pid']) ? intval($_REQUEST['pid']) : 0; // bag props id.table:userbag.使用道具ID（精灵球ID）

if($bid < 1) die('0');

require_once('../sec/dblock_fun.php');
$catchUserLocked = false;
$catchTransactionActive = false;
function catchShutdown()
{
	global $_pm,$catchUserLocked,$catchTransactionActive;
	if($catchTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$catchTransactionActive = false;
	}
	if($catchUserLocked && function_exists('realseLock'))
	{
		realseLock();
		$catchUserLocked = false;
	}
}
$a = getLock($uid);
if(!is_array($a)){
	realseLock();
	die('服务器繁忙，请稍候再试！');
}
$catchUserLocked = true;
$catchTransactionActive = true;
register_shutdown_function('catchShutdown');
function catchUseBall($bid)
{
	global $_pm,$uid;
	$bid = intval($bid);
	$sql = "UPDATE userbag SET sums=sums-1 WHERE id=$bid AND uid=$uid AND sums>0 AND zbing=0 AND (cantrade IS NULL OR cantrade<>3)";
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
		catchAbort('20');
	}
	if(!$_pm['mysql']->query("DELETE FROM userbag WHERE id=$bid AND uid=$uid AND sums<=0 AND bsum<=0 AND psum<=0 AND pyb=0 AND zbing=0 AND (cantrade IS NULL OR cantrade<>3)")){
		catchAbort('20');
	}
}

function catchAbort($message)
{
	global $_pm,$catchTransactionActive;
	$_pm['mysql']->query('ROLLBACK');
	$catchTransactionActive = false;
	catchShutdown();
	die($message);
}

function catchCommit($gid)
{
	global $_pm,$catchTransactionActive;
	if(!$_pm['mysql']->query('COMMIT')){
		$_pm['mysql']->query('ROLLBACK');
		$catchTransactionActive = false;
		catchShutdown();
		die('服务器繁忙，请稍候再试！');
	}
	$catchTransactionActive = false;
	if(defined('MEM_USERBAG_KEY')) $_pm['mem']->del(MEM_USERBAG_KEY);
	if(defined('MEM_USERBB_KEY')) $_pm['mem']->del(MEM_USERBB_KEY);
	if(defined('MEM_USERSK_KEY')) $_pm['mem']->del(MEM_USERSK_KEY);
	$_SESSION['catch_gw_info'] = intval($gid);
	catchShutdown();
}

function catchFinish($gid,$message,$announcement='')
{
	catchCommit($gid);
	if($announcement !== '')
	{
		$task = new task();
		$task->saveGword($announcement);
	}
	die($message);
}

function catchChanceHit($chance)
{
	$chance = max(0,min(1,floatval($chance)));
	if($chance <= 0) return false;
	if($chance >= 1) return true;
	return rand(1,10000) <= intval(round($chance*10000));
}

function catchPositiveIdList($value)
{
	$result = array();
	foreach(explode('|', strval($value)) as $id)
	{
		$id = trim($id);
		if($id !== '' && ctype_digit($id) && intval($id) > 0) $result[] = intval($id);
	}
	return array_values(array_unique($result));
}

function catchMemArray($key)
{
	global $_pm;
	$value = $_pm['mem']->get($key);
	if(is_array($value)) return $value;
	if(is_string($value))
	{
		$value = @unserialize($value);
		if(is_array($value)) return $value;
	}
	return array();
}

$user	 = $_pm['user']->getUserById($uid);//用户信息
$sp	     = $_pm['user']->getUserItemById($uid,$bid);//用户包裹信息
$allbb   = $_pm['user']->getUserPetById($uid);//用户宠物信息
$memgpcid = catchMemArray('db_gpcid');
$mempropsid = catchMemArray('db_propsid');
if(!is_array($user)) catchAbort('0');
if(!is_array($allbb)) $allbb = array();

$lockedBall = $_pm['mysql']->getOneRecord("SELECT id,pid,sums FROM userbag WHERE id=$bid AND uid=$uid AND sums>0 AND zbing=0 AND (cantrade IS NULL OR cantrade<>3) FOR UPDATE");
if(!is_array($sp) || !is_array($lockedBall) || intval($sp['pid']) != intval($lockedBall['pid'])){
	catchAbort('20');
}
$sp['sums'] = intval($lockedBall['sums']);

$fightKey = 'fight'.$uid;
$test = isset($_SESSION[$fightKey]) ? $_SESSION[$fightKey] : false;
if(!is_array($test) || !isset($test['gid']) || intval($test['gid']) < 1){
	catchAbort('-1');
}
$fightGid = intval($test['gid']);

if(isset($_SESSION['catch_gw_info']) && intval($_SESSION['catch_gw_info']) == $fightGid)
{
	$_pm['mysql']->query('ROLLBACK');
	$catchTransactionActive = false;
	catchShutdown();
	stopUser2(52);//,true
	die('0');
}

$gs = is_array($memgpcid) && isset($memgpcid[$fightGid]) ? $memgpcid[$fightGid] : false;
/*$gs = $_pm['mem']->dataGet(array('k'	=>	MEM_GPC_KEY,
				'v'	=>  "if(\$rs['id'] == '{$test['gid']}') \$ret=\$rs;"
			));*/
//当前所打的怪物数据
$bb = $test;
if (!is_array($bb) || !is_array($gs)) catchAbort('-1');
else
{
	$gpcDefaults = array(
		'id' => $fightGid,
		'name' => '',
		'hp' => 1,
		'catchv' => 0,
		'catchid' => 0,
		'wx' => 0
	);
	foreach($gpcDefaults as $key => $value)
	{
		if(!isset($gs[$key]) || $gs[$key] === '') $gs[$key] = $value;
	}
	if(!isset($bb['hp']) || $bb['hp'] === '') $bb['hp'] = 0;
	$bbrs = $arrobj->dataGet(array('k'	=>	MEM_BB_KEY,
				'v'	=>  "if(\$rs['uid'] == '{$uid}' && \$rs['id']=='{$bb['bid']}') \$ret=\$rs;"
			),//当前所打怪的宠物数据
							$allbb
						   );
	if (!is_array($bbrs)) $bbrs['level']=0;
}

if (is_array($sp))
{

	$prs = $sp;//包裹信息。

	// 捕捉道具 和要被捕捉的怪物信息都正确，开始计算。
	if (is_array($prs) && is_array($gs))
	{

		if($prs['sums'] < 1)
		{
			catchAbort("20");
		}
		if($bid != $prs['id'])
		{
			catchAbort("20");
		}
		// Start count...
		// 实际捕捉率=[怪物捕捉值/（100－玩家宠物与怪物等级之差）]*（1－怪物当前HP值/怪物最大HP值）*100%+捕捉道具附加捕捉率

		//实际捕捉率＝（怪物捕捉值/100）*（1－怪物当前HP值/怪物最大HP值）*100%+捕捉道具附加捕捉率

		// 结果格式：

		$pv = explode(':', isset($prs['effect']) ? $prs['effect'] : '');

		if(strtolower($pv[0])=='getitems')//获取装备
		{
			if(count($pv) < 2 || $pv[1] == '') catchAbort('12');
			$params = explode(",",$pv[1],2);
			if(count($params) != 2 || trim($params[0]) === '' || trim($params[1]) === '') catchAbort('12');
			$theGPCs = catchPositiveIdList(isset($params[0]) ? $params[0] : '');
			if(!in_array($fightGid, $theGPCs, true)) catchAbort('12');




			$monsterMaxHp = max(1,floatval($gs['hp']));
			$monsterHp = max(0,min(floatval($bb['hp']),$monsterMaxHp));
			$pzl = max(0,min(1,($gs['catchv']/100)*(1-$monsterHp/$monsterMaxHp)));

			if(catchChanceHit($pzl))
			{
				$msg = "";
				$items = explode("|",$params[1]);
				foreach($items as $v)
				{
					$proparr = explode(":",$v);
					if(count($proparr) < 3 || !ctype_digit($proparr[0]) || !ctype_digit($proparr[1]) || !ctype_digit($proparr[2])
						|| intval($proparr[0]) < 1 || intval($proparr[1]) < 1 || intval($proparr[2]) < 1) catchAbort('捕捉奖励配置错误！');
					$randnum = rand(1,$proparr[1]);
					if($randnum == 1)
					{

						if(!is_array($mempropsid) || !isset($mempropsid[$proparr[0]]) || !is_array($mempropsid[$proparr[0]])) catchAbort('捕捉奖励配置错误！');
						$prs = $mempropsid[$proparr[0]];
						/*$prs = $_pm['mem']->dataGet(array('k' => MEM_PROPS_KEY,
													 'v' => "if(\$rs['id'] == '{$proparr[0]}') \$ret=\$rs;"
										  ));*/



						$task = new task();
						$giveResult = $task->saveGetPropsMore($proparr[0],$proparr[2]);
						if($giveResult !== true){
							catchAbort($giveResult === '200' ? '背包空间不足！' : '捕捉奖励发放失败！');
						}
						catchUseBall($bid);
						$announcement = (isset($proparr[3]) && $proparr[3] == "2")
							? "在 {$gs['name']} 身上成功的发现了 {$prs['name']} {$proparr[2]} 个。" : '';
						$newstr = "恭喜您得到 {$prs['name']} {$proparr[2]} 个。";
						catchFinish($test['gid'],$newstr,$announcement);
						break;
					}
				}
				catchUseBall($bid);
				catchFinish($test['gid'],'0');
			}
			else{
				catchUseBall($bid);
				catchFinish($test['gid'],'0');
			}
		}
		else if(strtolower($pv[0])=='get')//获取装备
		{
			if(count($pv) < 5 || $pv[1] == '' || $pv[2] == '') catchAbort('12');
			$theGPCs = catchPositiveIdList($pv[1]);

			if(!in_array($fightGid,$theGPCs,true))
			{
				catchAbort("12");
			}

			$pvv = str_replace('%','',$pv[2])/100;
			$monsterMaxHp = max(1,floatval($gs['hp']));
			$monsterHp = max(0,min(floatval($bb['hp']),$monsterMaxHp));
			$pzl = max(0,min(1,($gs['catchv']/100)*(1-$monsterHp/$monsterMaxHp)+$pvv));

			if(catchChanceHit($pzl)) // Catch ok.
			{
				//掉落物品获取。格式：道具ID：机率范围。
				$prpid = intval($pv[4]);
				$okidlist = $drop = "";
				if ($prpid === false || $prpid == 0 || $prpid == '') $drop = '无';
				else
				{
					$rarr = array($prpid);
					foreach ($rarr as $k => $v)
					{

						/*$prs = $_pm['mem']->dataGet(array('k' => MEM_PROPS_KEY,
												 'v' => "if(\$rs['id'] == '{$v}') \$ret=\$rs;"
						));*/

						$prs = is_array($mempropsid) && isset($mempropsid[$v]) ? $mempropsid[$v] : false;
						if( is_array($prs) )
						{
							$drop .= $prs['name'].',';
							$okidlist .= $v.',';
						}
					}// end foreach.
					$drop = substr($drop, 0, -1);
					$okidlist = substr($okidlist, 0, -1);
					$task = new task();
					$giveResult = $task->saveGetPropsMore($prpid,1);
					if($giveResult !== true){
						catchAbort($giveResult === '200' ? '背包空间不足！' : '捕捉奖励发放失败！');
					}
				}

				catchUseBall($bid);
				$announcement = (isset($pv[3]) && $pv[3] == 2) ? "成功的获取了: ".$drop."，太爽了！" : '';
				catchFinish($test['gid'],'15',$announcement);
			}else{
				catchUseBall($bid);
				catchFinish($test['gid'],'13');
			}
		}
		else if(strtolower($pv[0])=='catch')
		{
			if(count($pv) < 3 || $pv[1] == '' || $pv[2] == '') catchAbort('7');
			if ($gs['catchid'] == 0) catchAbort('3'); // 此怪不能捕捉
			$pvv = str_replace('%','',$pv[2])/100;
			$gwidarr = catchPositiveIdList($pv[1]);
			if(!in_array(intval($gs['id']),$gwidarr,true))
			{
				catchAbort("7");//不能捕捉此宝宝
			}
			$carriedPets = $_pm['mysql']->getRecords("SELECT id FROM userbb WHERE uid=$uid AND muchang=0 FOR UPDATE");
			if($carriedPets === false && mysql_errno($_pm['mysql']->getConn()) != 0){
				catchAbort('服务器繁忙，请稍候再试！');
			}
			$carriedCount = is_array($carriedPets) ? count($carriedPets) : 0;
			if($carriedCount >= 3){
				catchAbort('6');
			}



			$monsterMaxHp = max(1,floatval($gs['hp']));
			$monsterHp = max(0,min(floatval($bb['hp']),$monsterMaxHp));
			$pzl = max(0,min(1,($gs['catchv']/100)*(1-$monsterHp/$monsterMaxHp)+$pvv));

			if(catchChanceHit($pzl)) // Catch ok.
			{
				$newpetsid = intval($gs['catchid']);
				// Get new bb info.
				$membbid = catchMemArray('db_bbid');
				$bb = isset($membbid[$newpetsid]) ? $membbid[$newpetsid] : false;
				/*$bb = $_pm['mem']->dataGet(array('k'	=>	MEM_BB_KEY,
						'v'	=>  "if(\$rs['id'] == '{$newpetsid}') \$ret=\$rs;"
						),
						$allbb
				 );*/
				// catchid is the authoritative pet template. Some maps intentionally
				// reuse one catchable pet across monsters with different elements.
				if (!is_array($bb) || intval($bb['id']) != $newpetsid || trim($bb['name']) == '' || trim($bb['name']) == '0'){
					catchAbort('2');
				}
				$czl = getCzl($bb['czl']);
				if($czl === false || $czl <= 0){
					catchAbort('捕捉宠物成长配置错误！');
				}

				// insert into userbb.
				//$bbid= $newid = mem_get_autoid($m, MEM_ORDER_KEY, 'userbb');

				$uinfo = $user;
				$petName = $_pm['mysql']->escape($bb['name']);
				$petUsername = $_pm['mysql']->escape(isset($uinfo['nickname']) ? $uinfo['nickname'] : '');
				$petSkillList = $_pm['mysql']->escape(isset($bb['skillist']) ? $bb['skillist'] : '');
				$petRemakeLevel = $_pm['mysql']->escape(isset($bb['remakelevel']) ? $bb['remakelevel'] : '');
				$petRemakeId = $_pm['mysql']->escape(isset($bb['remakeid']) ? $bb['remakeid'] : '');
				$petRemakePid = $_pm['mysql']->escape(isset($bb['remakepid']) ? $bb['remakepid'] : '');
				$petNowExp = $_pm['mysql']->escape(isset($bb['nowexp']) ? $bb['nowexp'] : '');
				$petImgStand = $_pm['mysql']->escape(isset($bb['imgstand']) ? $bb['imgstand'] : '');
				$petImgAck = $_pm['mysql']->escape(isset($bb['imgack']) ? $bb['imgack'] : '');
				$petImgDie = $_pm['mysql']->escape(isset($bb['imgdie']) ? $bb['imgdie'] : '');
				$petKx = $_pm['mysql']->escape(isset($bb['kx']) ? $bb['kx'] : '');
				$petHeadImg = $_pm['mysql']->escape(isset($bb['headimg']) ? $bb['headimg'] : '');
				$petCardImg = $_pm['mysql']->escape(isset($bb['cardimg']) ? $bb['cardimg'] : '');
				$petEffectImg = $_pm['mysql']->escape(isset($bb['effectimg']) ? $bb['effectimg'] : '');
				$petInserted = $_pm['mysql']->query("INSERT INTO userbb(name,uid,username,level,wx,ac,mc,srchp,hp,srcmp,mp,skillist,stime,nowexp,
						lexp,imgstand,imgack,imgdie,hits,miss,speed,kx,remakelevel,remakeid,remakepid,czl,headimg,cardimg,effectimg,old_bid)
				VALUES('{$petName}','".intval($uinfo['id'])."','{$petUsername}','1','".intval($bb['wx'])."',
				   '".floatval($bb['ac'])."','".floatval($bb['mc'])."','".floatval($bb['hp'])."','".floatval($bb['hp'])."','".floatval($bb['mp'])."','".floatval($bb['mp'])."','{$petSkillList}',unix_timestamp(),
				  '{$petNowExp}','100','{$petImgStand}','{$petImgAck}','{$petImgDie}',
				   '".floatval($bb['hits'])."','".floatval($bb['miss'])."','".floatval($bb['speed'])."','{$petKx}','{$petRemakeLevel}',
				   '{$petRemakeId}','{$petRemakePid}','".floatval($czl)."','{$petHeadImg}','{$petCardImg}','{$petEffectImg}','".intval($bb['id'])."')
				");
				if(!$petInserted || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
					catchAbort('捕捉宠物保存失败，请稍候再试！');
				}
				$bbid = intval($_pm['mysql']->last_id());
				if($bbid < 1){
					catchAbort('捕捉宠物保存失败，请稍候再试！');
				}

				//修复只能有一种技能的bug技能，和吸血技能
				$arr = explode(",", $bb['skillist']);
				$memskillsysid = catchMemArray('db_skillsysid');
				foreach($arr as $av)
				{
					if($av === '' || $av === '0')
					{
						continue;
					}
					$newarr = explode(":",$av);
					if(count($newarr) != 2 || !ctype_digit($newarr[0]) || !ctype_digit($newarr[1])
						|| intval($newarr[0]) < 1 || intval($newarr[1]) < 1)
					{
						catchAbort('捕捉宠物技能配置错误！');
					}
					$jn = is_array($memskillsysid) && isset($memskillsysid[$newarr[0]]) ? $memskillsysid[$newarr[0]] : false;
					/*$jn = $_pm['mem']->dataGet(array('k'	=>	MEM_SKILLSYS_KEY,
						'v'	=>  "if(\$rs['id'] == '{$newarr[0]}') \$ret=\$rs;"
					));*/
					if(!is_array($jn)){
						catchAbort('捕捉宠物技能配置错误！');
					}
					$skillLevel = intval($newarr[1]);
					$skillIndex = $skillLevel-1;
					$ack  = explode(",", isset($jn['ackvalue']) ? $jn['ackvalue'] : '');
					$plus = explode(",", isset($jn['plus']) ? $jn['plus'] : '');
					$uhp  = explode(",", isset($jn['uhp']) ? $jn['uhp'] : '');
					$ump  = explode(",", isset($jn['ump']) ? $jn['ump'] : '');
					$img = explode(",",isset($jn['imgeft']) ? $jn['imgeft'] : '');
					if(!isset($ack[$skillIndex]) || !isset($uhp[$skillIndex]) || !isset($ump[$skillIndex])){
						catchAbort('捕捉宠物技能等级配置错误！');
					}
					$skillName = $_pm['mysql']->escape(isset($jn['name']) ? $jn['name'] : '');
					$skillVary = $_pm['mysql']->escape(isset($jn['vary']) ? $jn['vary'] : '');
					$skillValue = $_pm['mysql']->escape($ack[$skillIndex]);
					$skillPlus = $_pm['mysql']->escape(isset($plus[$skillIndex]) ? $plus[$skillIndex] : '');
					$skillImg = $_pm['mysql']->escape(isset($img[$skillIndex]) ? $img[$skillIndex] : '');
					$skillInserted = $_pm['mysql']->query("INSERT INTO skill(bid,name,level,vary,wx,value,plus,img,uhp,ump,sid)
					VALUES({$bbid}, '{$skillName}','{$skillLevel}','{$skillVary}','".intval($jn['wx'])."','{$skillValue}','{$skillPlus}','{$skillImg}',".intval($uhp[$skillIndex]).",".intval($ump[$skillIndex]).",".intval($jn['id']).")
					");
					if(!$skillInserted || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
						catchAbort('捕捉宠物技能保存失败，请稍候再试！');
					}
				}
				// Get jn info.
				/*$jn = $_pm['mem']->dataGet(array('k'	=>	MEM_SKILLSYS_KEY,
						'v'	=>  "if(\$rs['id'] == '{$arr[0]}') \$ret=\$rs;"
				));
				$ack  = explode(",", $jn['ackvalue']);
				$plus = explode(",", $jn['plus']);
				$uhp  = explode(",", $jn['uhp']);
				$ump  = explode(",", $jn['ump']);
				获取刚插入宠物ID。
				$newbb = $_pm['mysql']->getOneRecord("SELECT id
							  FROM userbb
							 WHERE uid={$_SESSION['id']}
							 ORDER BY stime DESC
							 LIMIT 0,1
						  ");
				$bbid = $newbb['id'];

				// Insert userbb jn.
				//$newid = mem_get_autoid($m, MEM_ORDER_KEY,'skill');
				echo "INSERT INTO skill(bid,name,level,vary,wx,value,plus,img,uhp,ump,sid)
				VALUES({$bbid}, '{$jn['name']}','{$arr['1']}','{$jn['vary']}','{$jn['wx']}','{$ack['0']}','{$plus['0']}','{$jn['img']}',{$uhp['0']},{$ump['0']},{$jn['id']})
				";exit;
				$_pm['mysql']->query("INSERT INTO skill(bid,name,level,vary,wx,value,plus,img,uhp,ump,sid)
				VALUES({$bbid}, '{$jn['name']}','{$arr['1']}','{$jn['vary']}','{$jn['wx']}','{$ack['0']}','{$plus['0']}','{$jn['img']}',{$uhp['0']},{$ump['0']},{$jn['id']})
				");*/
				//减去精灵球
				catchUseBall($bid);
				catchCommit($test['gid']);
				if(isset($pv['3']) && $pv['3'] == 2){
					$task = new task();
					$task->saveGword("成功的捕捉到了 {$bb['name']} ，太有才了！");
				}
				//$_pm['user']->updateMemUserbb($_SESSION['id']);
				//$_pm['user']->updateMemUsersk($_SESSION['id']);
				die('10');
			}
			else
			{ // Clear props.
				catchUseBall($bid);
				catchFinish($test['gid'],'0');
				//$_pm['user']->updateMemUserbag($_SESSION['id']);
			} // 捕捉机率太低。
		}
	}
}
$_pm['mysql']->query('ROLLBACK');
$catchTransactionActive = false;
catchShutdown();
$_pm['mem']->memClose();
echo "0";


?>
