<?php
/*
time_config
    callback -> 0-10>1:2,2:1#0-10>1:2,2:1#0-10>1:2,2:1#0-10>1:2,2:1
*/
require_once('../config/config.game.php');
secStart($_pm['mem']);
require_once('../sec/dblock_fun.php');
$callbackPendingLogId = 0;
$callbackCommitted = false;
function callbackPrizeCleanupPendingLog()
{
	global $_pm,$callbackPendingLogId,$callbackCommitted;
	if(!$callbackCommitted && intval($callbackPendingLogId) > 0 && isset($_pm['mysql']))
	{
		$_pm['mysql']->query('DELETE FROM gamelog WHERE id='.intval($callbackPendingLogId).' AND vary=241');
	}
	$callbackPendingLogId = 0;
}
function callbackPrizeShutdown()
{
	global $_pm,$callbackCommitted;
	if(!$callbackCommitted && isset($_pm['mysql'])) $_pm['mysql']->query('ROLLBACK');
	callbackPrizeCleanupPendingLog();
	if(function_exists('realseLock')) realseLock();
}
function msg($m)
{
	global $_pm;
	if(isset($_pm['mysql'])) $_pm['mysql']->query('ROLLBACK');
	callbackPrizeCleanupPendingLog();
	realseLock();
	die($m);
}

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
//$echo = '<'.microtime_float()."-";
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('登录状态无效！');
$a = getLock($uid);
//$echo .= ''.microtime_float().">\r\n";
//echo $echo;


if(!is_array($a)){
	msg('请不要过快点击,谢谢！');
}
register_shutdown_function('callbackPrizeShutdown');

$setting = $_pm['mem']->get('db_welcome');
if(!is_array($setting)) $setting=kdjlSafeMemValue($setting, array());
if(!is_array($setting))
{
	msg('后台配置数据读取失败(1)！');
}
$callback=false;
foreach($setting as $row)
{
	if(!is_array($row) || !isset($row['code'])) continue;
	if($row['code']=='callback'){
		$callback=isset($row['contents']) ? $row['contents'] : '';
		break;
	}
}

if(!$callback)
{
	msg('活动没有开启！');
}

$lastvtime = isset($_SESSION['lastvtime']) ? intval($_SESSION['lastvtime']) : time();
$day=time()-$lastvtime;
$callgetedKey = 'callgeted_'.$uid;
$getM=$_pm['mem']->get($callgetedKey);
$callbackLog = $_pm['mysql']->getOneRecord('SELECT id FROM gamelog WHERE seller="'.$uid.'" AND buyer="'.$uid.'" AND vary=241 LIMIT 1');

if($day<30*24*3600||$getM||is_array($callbackLog))
{
	msg('很遗憾，您已经领取奖励或者不够资格！');
}
$user= $_pm['user']->getUserById($uid);
if(!is_array($user) || empty($user['mbid']))
{
	msg('获取你的数据失败！');
}
$_bb = $_pm['user']->getUserPetByIdS($uid,$user['mbid']);//战斗宠物。
if(!$_bb)
{
	msg('请到点击左侧<宠物资料>,再点击一个宠物设置为主战宠物！');
}
$bbCzl = isset($_bb['czl']) ? intval($_bb['czl']) : -1;

//0-10>1:2,2:1#10-30>3:2,4:1#30-90>5:2,6:1#90-10000>7:2,8:1
$settings=explode('#',$callback);

$getPrize=array();
foreach($settings as $se)
{
	$t1=explode('>',$se);
	if(count($t1) < 2) continue;
	$t2=explode('-',$t1[0]);
	if(count($t2) < 2) continue;
	if(
		$bbCzl>=intval($t2[0])
		&&
		$bbCzl<intval($t2[1])
	)
	{
		$t3=explode(',',$t1[1]);
		foreach($t3 as $t4)
		{
			$t5=explode(':',trim($t4));
			if(count($t5)!=2) msg('奖励配置错误！');
			$pid = intval($t5[0]);
			$num = intval($t5[1]);
			if($pid < 1 || $num < 1) msg('奖励配置错误！');
			if(!isset($getPrize[$pid])) $getPrize[$pid] = 0;
			$getPrize[$pid] += $num;
		}
		break;
	}
}

if(empty($getPrize))
{
	msg('后台奖品设置不对, 没有给成长为:'.$bbCzl.'的设定奖品！');
}

$props = $_pm['mem']->get('db_propsid');
if(!is_array($props)) $props=kdjlSafeMemValue($props, array());
if(!is_array($props))
{
	msg('后台物品数据读取失败！');
}

$task=new task();
$prizeWord='';
foreach($getPrize as $k=>$v)
{
	$pid = intval($k);
	$num = intval($v);
	if($pid < 1 || $num < 1 || !isset($props[$pid]) || !is_array($props[$pid])) msg('奖励配置错误！');
	$rtn=$task->saveGetPropsMore($pid,$num);
	if($rtn !== true)
	{
		$_pm['mysql']->query("rollback");
		msg($rtn === '200' ? '您的背包空间不足，请整理后再来领取！' : '奖励发放失败，请稍候再试！');
	}
	$pname = isset($props[$pid]['name']) ? $props[$pid]['name'] : $pid;
	$prizeWord.=$pname.' '.$num.'个，';
}
$logNote = $_pm['mysql']->escape('callback prize:'.$prizeWord);
if(!$_pm['mysql']->query('INSERT INTO gamelog(ptime,seller,buyer,pnote,vary) VALUES('.time().',"'.$uid.'","'.$uid.'","'.$logNote.'",241)') || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
{
	msg('回归奖励状态保存失败，请稍候再试！');
}
$callbackPendingLogId = intval($_pm['mysql']->last_id());
if($callbackPendingLogId < 1) msg('回归奖励状态保存失败，请稍候再试！');
if(!$_pm['mysql']->query('COMMIT'))
{
	msg('回归奖励状态保存失败，请稍候再试！');
}
$callbackCommitted = true;
$_pm['mem']->del(MEM_USERBAG_KEY);
$_pm['mem']->set(array('k'=>$callgetedKey,'v'=>1));

$nickname = isset($_SESSION['nickname']) ? $_SESSION['nickname'] : '';
$noticePrizeWord = $prizeWord;
$tailComma = '，';
if($noticePrizeWord !== '' && substr($noticePrizeWord, -strlen($tailComma)) === $tailComma)
{
	$noticePrizeWord = substr($noticePrizeWord, 0, strlen($noticePrizeWord) - strlen($tailComma));
}
$swfData='欢迎曾经的朋友'.$nickname.'，回到【口袋精灵】的家庭，获得了回归奖励：'.$noticePrizeWord.'！';
require_once('../socketChat/config.chat.php');
require_once('../kernel/socketmsg.v1.php');
$s=new socketmsg();
$s->sendMsg('an|'.$swfData);
$_SESSION['lastvtime']=time();
session_write_close();
msg("<!--OK-->欢迎您的回归，恭喜您获得了老玩家回归活动的奖励物品：".$noticePrizeWord."，祝您游戏愉快！");

?>
