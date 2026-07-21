<?php
ini_set("display_errors", false);
require_once('../config/config.game.php');
require_once(dirname(__FILE__).'/fortress_common.php');
require_once('../sec/dblock_fun.php');
secStart($_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid <= 0)
{
	die('登录状态已失效，请重新登录！');
}
$fortressCountStored = false;
$fortressPreviousNum = 0;
$fortressCardsStored = false;
$fortressPreviousCards = array();
$fortressAnnouncements = array();
$fortressUserLocked = false;
$fortressTransactionActive = false;
$fortressOperationFinished = false;
function fortressCardReleaseLock()
{
	global $fortressUserLocked;
	if($fortressUserLocked && function_exists('realseLock'))
	{
		realseLock();
		$fortressUserLocked = false;
	}
}
function fortressCardRestoreCaches()
{
	global $_pm,$uid,$fortressCountStored,$fortressPreviousNum,$fortressCardsStored,$fortressPreviousCards;
	if($fortressCountStored)
	{
		fortressDailyCacheSet($_pm['mem'], 'count', $uid, max(0, intval($fortressPreviousNum)));
		$fortressCountStored = false;
	}
	if($fortressCardsStored)
	{
		fortressDailyCacheSet($_pm['mem'], 'cards', $uid, is_array($fortressPreviousCards) ? $fortressPreviousCards : array());
		$fortressCardsStored = false;
	}
}
function fortressCardShutdown()
{
	global $_pm,$fortressTransactionActive,$fortressOperationFinished;
	if($fortressTransactionActive)
	{
		$_pm['mysql']->query('ROLLBACK');
		$fortressTransactionActive = false;
	}
	if(!$fortressOperationFinished) fortressCardRestoreCaches();
	fortressCardReleaseLock();
}
function msg($m,$js='')
{
	global $fortressOperationFinished;
	$fortressOperationFinished = true;
	fortressCardReleaseLock();
	die($m);
}
function fortressCardRollback($m)
{
	global $_pm,$fortressTransactionActive,$fortressOperationFinished;
	$_pm['mysql']->query('ROLLBACK');
	$fortressTransactionActive = false;
	fortressCardRestoreCaches();
	$fortressOperationFinished = true;
	fortressCardReleaseLock();
	die($m);
}
$a = getLock($uid);
if(!is_array($a)){
	msg('服务器繁忙，请稍候再试！');
}
$fortressUserLocked = true;
register_shutdown_function('fortressCardShutdown');
require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
$s=new socketmsg();
$requestOp = (isset($_GET['op']) && !is_array($_GET['op'])) ? $_GET['op'] : '';
if($requestOp == 'fortress'){//要塞翻牌
	$setting = $_pm['mem']->get('db_welcome1');
	if(!is_array($setting)) $setting = kdjlSafeMemValue($setting, false);
	if(!is_array($setting))
	{
		msg('后台配置数据读取失败(1)！');
	}
	if(!isset($setting['fortress']))
	{
		msg('缺少活动开启设定(fortress)！');
	}

	if(!isset($setting['fortress_time']))
	{
		msg('缺少活动开启设定(fortress_time)！');
	}

	$time_settings=explode("|",$setting['fortress_time']);
	$w=intval(date('w'));
	$hm=intval(date('His'));
	if($w==0)
	{
		$w=7;
	}
	$time_flag=false;
	foreach($time_settings as $s1)
	{
		$tmp=explode(',',$s1);
		if(count($tmp) < 4) continue;
		//1,2100,2105,2130,2135
		$day = intval($tmp[0]);
		$start = intval($tmp[2]);
		$end = intval($tmp[3]);
		if($w == $day)
		{
			if($hm >= $start && $hm <= $end)
			{
				$time_flag=true;
			}
			break;
		}
	}

	if(!$time_flag){
		msg('1');
	}


	/*if($_SESSION['fortress_pass'] != 2){
		msg('非法进入'.$_SESSION['fortress_pass']);
	}*/
	$srctime = 3;
	#################增加一个间隔时间################
	$time = isset($_SESSION['time'.$uid]) ? intval($_SESSION['time'.$uid]) : 0;
	if(empty($time)){
		$_SESSION['time'.$uid] = time();
	}else{
		$nowtime = time();
		$ctime = $nowtime - $time;
		if($ctime < $srctime){
			msg('服务器繁忙，请稍候操作！');
		}
		else{
			$_SESSION['time'.$uid] = time();
		}
	}

	$id = (isset($_GET['id']) && !is_array($_GET['id'])) ? intval($_GET['id']) : 0;
	if($id < 1 || $id > 30){
		msg('服务器繁忙，请稍候操作！');
	}
	//得到当前信息
	$sql = 'SELECT bb_id,at_section_num,cur_gpc_id FROM fortress_users_'.date("Ymd").' WHERE user_id = '.$uid.' FOR UPDATE';
	$fortress_arr = $_pm['mysql'] -> getOneRecord($sql);
	if(!is_array($fortress_arr)){
		//msg('你没有参加要赛活动！！'.var_dump($fortress_arr).'sql:'.$sql);
		$fortressOperationFinished = true;
		fortressCardReleaseLock();
		die('<!--quit-->');
	}
	$pendingCardId = isset($_SESSION['fortress_card_id']) ? intval($_SESSION['fortress_card_id']) : 0;
	$pendingCardDate = isset($_SESSION['fortress_card_date']) ? strval($_SESSION['fortress_card_date']) : '';
	if($pendingCardId > 0 && $pendingCardDate !== date('Ymd'))
	{
		$_SESSION['fortress_card_id'] = 0;
		unset($_SESSION['fortress_card_date']);
		$pendingCardId = 0;
	}
	if(intval($fortress_arr['cur_gpc_id']) > 0 || $pendingCardId > 0)
	{
		msg('请先完成或结算当前要塞战斗！');
	}

	$openedCards = fortressDailyCacheGet($_pm['mem'], 'cards', $uid, array());
	if(!is_array($openedCards)) $openedCards = array();
	foreach($openedCards as $openedCard)
	{
		if(is_array($openedCard) && isset($openedCard['id']) && intval($openedCard['id']) === $id)
		{
			msg('这张牌已经翻过了！');
		}
	}

	$fortress_num = max(intval(fortressDailyCacheGet($_pm['mem'], 'count', $uid, 0)), count($openedCards));
	if($fortress_num >= 30){
		msg('您已经翻了30张了，今天不能再翻！');
	}
	$fortressPreviousNum = max(0, $fortress_num);
	$fortress_num++;

	$sectionNum = intval($fortress_arr['at_section_num']);
	$fortress_users=$_pm['mysql']->getRecords('select fortress.user_id,fortress.bb_id from fortress_users_'.date("Ymd").' fortress inner join userbb on userbb.id=fortress.bb_id and userbb.uid=fortress.user_id where fortress.user_id!='.$uid.' and fortress.at_section_num='.$sectionNum.' and userbb.muchang=0 and userbb.tgflag=0');
	if(!is_array($fortress_users)) $fortress_users = array();
	$ct=count($fortress_users);
	if($ct<2){
		$fortressOperationFinished = true;
		fortressCardReleaseLock();
		die('<!--quitmen-->');
	}

	//80%几率遇怪
	//计数
	if(!fortressDailyCacheSet($_pm['mem'], 'count', $uid, $fortress_num))
	{
		msg('要塞翻牌次数保存失败，请稍候再试！');
	}
	$fortressCountStored = true;
	$num=rand(1,10);
	if($num <= 8){//遇怪
		$_SESSION['fortress_card_id'] = $id;//跳到战斗界面
		$_SESSION['fortress_card_date'] = date('Ymd');
		$rs=$s->sendMsg('SYSN|fortress_boss->2秒后开始战斗',$uid);
		$fortressOperationFinished = true;
		msg('即将开始战斗');
	}
	$sql = 'SELECT id,effect FROM fortress_card WHERE section_num = '.$fortress_arr['at_section_num'];
	//echo $sql;
	$tarot = $_pm['mysql'] -> getRecords($sql);

	if(!is_array($tarot) || empty($tarot)){
		fortressCardRollback('要塞奖励配置缺失，请联系管理员！');
	}
	$max = count($tarot) - 1;
	$rand = rand(0,$max);
	$newTarot = $tarot[$rand];

	$effect = explode('|',$newTarot['effect']);

	$retParts = array();
	$fortressTransactionActive = true;
	foreach($effect as $v){
		$arr = explode(':',$v);
		if(count($arr) < 2)
		{
			fortressCardRollback('要塞奖励配置错误，请联系管理员！');
		}
		switch ($arr[0]){
			case 'money_add'://单人获得金币奖励
				$moneyAmount = intval($arr[1]);
				if($moneyAmount === 0) fortressCardRollback('要塞奖励配置错误，请联系管理员！');
				moneyAdd($uid,$moneyAmount);
				if($moneyAmount < 0){
					$retParts[]='扣除金币：'.abs($moneyAmount);
				}else{
					$retParts[]='获得金币：'.$moneyAmount;
				}
				break;
			case 'exp_add'://单人获得经验奖励
				$expAmount = intval($arr[1]);
				if($expAmount <= 0) fortressCardRollback('要塞奖励配置错误，请联系管理员！');
				$t = new task();
				if($t->saveExps($expAmount) === false)
				{
					fortressCardRollback('要塞奖励发放失败，请稍候再试！');
				}
				$retParts[]='获得经验：'.$expAmount;
				break;
			case 'giveitems'://单人获得道具奖励
				$itemstr = str_replace('giveitems:', '', $v);
				$retParts[] = getItem($itemstr);
				break;
			default:
				fortressCardRollback('要塞奖励配置错误，请联系管理员！');
		}
	}
	$retParts = array_values(array_filter($retParts, 'strlen'));
	if(empty($retParts))
	{
		fortressCardRollback('要塞奖励配置错误，请联系管理员！');
	}
	$ret = implode('<br/>', $retParts);

	// Save the revealed card before commit so a cache failure can still roll back the reward.
	$fortressPreviousCards = fortressDailyCacheGet($_pm['mem'], 'cards', $uid, array());
	if(!is_array($fortressPreviousCards)) $fortressPreviousCards = array();
	$ar = $fortressPreviousCards;
	$ar[]=array('id' => $id,'img' => $ret);
	if(!fortressDailyCacheSet($_pm['mem'], 'cards', $uid, $ar))
	{
		fortressCardRollback('要塞翻牌记录保存失败，请稍候再试！');
	}
	$fortressCardsStored = true;
	if(!$_pm['mysql']->query('COMMIT'))
	{
		fortressCardRollback('要塞奖励发放失败，请稍候再试！');
	}
	$fortressTransactionActive = false;
	$fortressOperationFinished = true;
	if(defined('MEM_USER_KEY')) $_pm['mem']->del(MEM_USER_KEY);
	if(defined('MEM_USERBB_KEY')) $_pm['mem']->del(MEM_USERBB_KEY);
	if(defined('MEM_USERBAG_KEY')) $_pm['mem']->del(MEM_USERBAG_KEY);

	foreach($fortressAnnouncements as $announcement)
	{
		$s->sendMsg('an|'.$announcement,'__ALL__');
	}
	echo $ret;
	//echo $rs.'aaa';
	//echo '['.__LINE__."]<br>";
}
fortressCardReleaseLock();



function moneyAdd($uid,$num){
	global $_pm;
	$num = intval($num);
	if($num < 0){
		$cost = abs($num);
		if(!$_pm['mysql'] -> query('UPDATE player SET money = GREATEST(0,COALESCE(money,0)-'.$cost.') WHERE id = '.intval($uid))){
			fortressCardRollback('金币结算失败，请稍候再试！');
		}
	}else{
		//echo 'UPDATE player SET money = money +'.$num.' WHERE id = '.$uid;
		if(!$_pm['mysql'] -> query('UPDATE player SET money = LEAST(COALESCE(money,0) +'.$num.',1000000000) WHERE id = '.intval($uid))){
			fortressCardRollback('金币结算失败，请稍候再试！');
		}
	}
}

function getItem($str){
	global $_pm,$fortressAnnouncements;//749:1:3:2
	$flag = 0;
	$retstr = '';
	$propslist = explode(',', $str);
	if (is_array($propslist)){
		$task = new task();
		foreach ($propslist as $k => $v){
			$inarr = explode(':', $v);
			if(is_array($inarr)){
				if(count($inarr) < 3)
				{
					fortressCardRollback('要塞奖励配置错误，请联系管理员！');
				}
				$pid = intval($inarr[0]);
				$num = intval($inarr[1]);
				$rate = intval($inarr[2]);
				if($pid <= 0 || $num <= 0 || $rate <= 0)
				{
					fortressCardRollback('要塞奖励配置错误，请联系管理员！');
				}
				if (rand(1, $rate) == 1){	//  rand hits
					$giveResult = $task->saveGetPropsMore($pid,$num);
					if($giveResult !== true){
						fortressCardRollback($giveResult === '200' ? '背包空间不足，请整理后再翻牌！' : '要塞奖励发放失败，请稍候再试！');
					}
					$flag = 1;
					$prs = $_pm['mysql']->getOneRecord("SELECT name FROM props WHERE id={$pid}");
					$propsName = is_array($prs) ? htmlspecialchars(strval($prs['name']), ENT_QUOTES, 'UTF-8') : $pid;
					if(empty($retstr)){
						$retstr = '获得 '.$propsName.'&nbsp;'.$num.' 个';
					}else{
						$retstr .= ",".$propsName.'&nbsp;'.$num.' 个';
					}
					if(isset($inarr[3]) && $inarr[3] == '2'){//发公告
						$nickname = htmlspecialchars(isset($_SESSION['nickname']) ? strval($_SESSION['nickname']) : '', ENT_QUOTES, 'UTF-8');
						$fortressAnnouncements[]='恭喜玩家 '.$nickname.'在女神要塞中幸运地得到了女神的眷顾，获得'.$propsName.'&nbsp; 奖励&nbsp; '.$num.' 个';
					}
				}
			}
		}
		if($flag == 0){
			return '真遗憾，您没有获得任何道具！';
		}
		return $retstr;
	}
}
$_pm['mem']->memClose();
?>
