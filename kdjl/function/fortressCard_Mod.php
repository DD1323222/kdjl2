<?php
require_once('../config/config.game.php');
require_once(dirname(__FILE__).'/fortress_common.php');
secStart($_pm['mem']);
require_once('../sec/dblock_fun.php');
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid <= 0)
{
	die('登录状态已失效，请重新登录！');
}
function msg($m)
{
	realseLock();
	die($m);
}
$a = getLock($uid);
if(!is_array($a)){
	msg('服务器繁忙，请稍候再试！');
}
//$_SESSION['fortress_pass'] = 1;
/*if($_SESSION['fortress_pass'] != 1){
	msg('非法进入'.$_SESSION['fortress_pass']);
}*/
//$_SESSION['fortress_pass_last_time']=$_SESSION['fortress_pass_time'];
function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
function fortressCardJsSingle($value)
{
	return str_replace(array('\\', "'", "\r", "\n"), array('\\\\', "\\'", '', ''), strval($value));
}
function fortressCardInt($row, $key)
{
	return (is_array($row) && isset($row[$key])) ? intval($row[$key]) : 0;
}
//unset($_SESSION['fortress_pass_time']);
if(!isset($_SESSION['fortress_pass_time']) || !is_array($_SESSION['fortress_pass_time']))
{
	$_SESSION['fortress_pass_time']=array();
}
$_SESSION['fortress_pass_time'][]=microtime_float();

//var_dump($_SESSION['fortress_pass_time']);

if(count($_SESSION['fortress_pass_time'])>3)
{
	array_shift($_SESSION['fortress_pass_time']);
}

$sql = 'SELECT bb_id,v_times,f_times,fv_result,cur_gpc_id FROM fortress_users_'.date("Ymd").' WHERE user_id = '.$uid.' FOR UPDATE';
$fortress_arr = $_pm['mysql'] -> getOneRecord($sql);
if(!is_array($fortress_arr))
{
	msg('你没有参加要塞活动！');
}
$lastFortressTime = isset($_SESSION['last_f_v_t']) ? intval($_SESSION['last_f_v_t']) : 0;
$c = time()-$lastFortressTime;
$pendingCardId = isset($_SESSION['fortress_card_id']) ? intval($_SESSION['fortress_card_id']) : 0;
$pendingCardDate = isset($_SESSION['fortress_card_date']) ? strval($_SESSION['fortress_card_date']) : '';
if($pendingCardId > 0 && $pendingCardDate !== date('Ymd'))
{
	$pendingCardId = 0;
	$_SESSION['fortress_card_id'] = 0;
	unset($_SESSION['fortress_card_date']);
}
if($fortress_arr['cur_gpc_id']!=0 || $pendingCardId > 0){
	$cur_cards = fortressDailyCacheGet($_pm['mem'], 'cards', $uid, array());
	if(!is_array($cur_cards)) $cur_cards = array();
	$previousCards = $cur_cards;
	$cardAlreadyRecorded = false;
	if($pendingCardId > 0)
	{
		foreach($cur_cards as $recordedCard)
		{
			if(is_array($recordedCard) && isset($recordedCard['id']) && intval($recordedCard['id']) === $pendingCardId)
			{
				$cardAlreadyRecorded = true;
				break;
			}
		}
	}
	if(!$cardAlreadyRecorded) $cur_cards[]=array('id'=>$pendingCardId,'img' =>'<img src=" ../images/ys/miss.png" width="62">');
	if($fortress_arr['cur_gpc_id']!=0)
	{
		$fvResult = intval($fortress_arr['fv_result']);
		if($fvResult <= 0)
		{
			$sqlExtra = ',f_times=COALESCE(f_times,0)+1,fv_result=COALESCE(fv_result,0)-1';
			$scoreDelta = (2*abs($fvResult-1)-1)*(-5);
		}
		else
		{
			$sqlExtra = ',f_times=COALESCE(f_times,0)+1,fv_result=-1';
			$scoreDelta = -5;
		}
		$settleSql = 'UPDATE fortress_users_'.date("Ymd").' SET cur_gpc_id=0'.$sqlExtra.
			',score=COALESCE(score,0)+'.$scoreDelta.' WHERE user_id='.$uid.' AND cur_gpc_id='.intval($fortress_arr['cur_gpc_id']);
		if(!$_pm['mysql']->query($settleSql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
		{
			$_pm['mysql']->query('ROLLBACK');
			msg('要塞战斗状态保存失败，请稍候再试！');
		}
		if(!$cardAlreadyRecorded && !fortressDailyCacheSet($_pm['mem'], 'cards', $uid, $cur_cards))
		{
			$_pm['mysql']->query('ROLLBACK');
			msg('要塞翻牌记录保存失败，请稍候再试！');
		}
		if(!$_pm['mysql']->query('COMMIT'))
		{
			$_pm['mysql']->query('ROLLBACK');
			fortressDailyCacheSet($_pm['mem'], 'cards', $uid, $previousCards);
			msg('要塞战斗结算失败，请稍候再试！');
		}
	}
	else if(!$cardAlreadyRecorded && !fortressDailyCacheSet($_pm['mem'], 'cards', $uid, $cur_cards))
	{
		msg('要塞翻牌记录保存失败，请稍候再试！');
	}
	 $_SESSION['fortress_card_id'] = 0;
	 unset($_SESSION['fortress_card_date']);
}

$fortressPass = isset($_SESSION['fortress_pass']) ? intval($_SESSION['fortress_pass']) : 0;
if($fortressPass != 1 && $c > 1 && $fortressPass != 3){
	msg('非法进入或操作过快，请重新进入活动。');
}
$_SESSION['fortress_pass'] = 2;
$_SESSION['last_f_v_t']=time();
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
foreach($time_settings as $s)
{
	$tmp=explode(',',$s);
	if(count($tmp) < 5) continue;
	//1,210000,210459,212959,213459
	$day = intval($tmp[0]);
	$start = intval($tmp[1]);
	$battleEnd = intval($tmp[3]);
	$stolenEnd = intval($tmp[4]);
	if($w == $day)
	{
		if($hm >= $start && $hm <= $battleEnd)
		{
			$time_flag=true;
		}
		if($hm >= $battleEnd && $hm <= $stolenEnd){
			realseLock();
			header("Location:/function/fortress_stolen_Mod.php");
			exit;
		}
		$tmp2 = timetos($tmp[2]);
		$tmp3 = timetos($tmp[3]);
		$c = $tmp2-time();
		$touqu = ($tmp3-time())>0?($tmp3-time()):0;
		break;
	}
}
if($c < 0){
	$ctime = 0;
}else{
	$ctime = $c;
}
if(!$time_flag){
	msg('现在不是要塞开启时间！');
}



$jsstr='var openstr=[];';

$ar = fortressDailyCacheGet($_pm['mem'], 'cards', $uid, array());//print_r($ar);
if(!is_array($ar)) $ar = array();

if(is_array($ar)){
	$i=0;
	foreach($ar as $v){
		if(!is_array($v)) continue;
		$cardId = isset($v['id']) ? intval($v['id']) : 0;
		$cardImg = fortressCardJsSingle(isset($v['img']) ? $v['img'] : '');
		$jsstr.='openstr['.($i).']=["'.$cardId.'",\''.$cardImg.'\'];';
		$i++;
	}
}//echo $jsstr;


$finfo = '<table width="230" border="0" cellspacing="0" cellpadding="0" style="margin:10px 0 0 65px;">
            <tr>
              <td height="22">击败次数：'.$fortress_arr['f_times'].' </td>
              <td>胜利次数：'.$fortress_arr['v_times'].'</td>
            </tr>
            <tr>
              <td height="22">连败次数：'.($fortress_arr['fv_result']>0?0:abs($fortress_arr['fv_result'])).'</td>
              <td>连胜次数：'.($fortress_arr['fv_result']>0?$fortress_arr['fv_result']:0).'</td>
            </tr>
          </table>';

$fortressPetId = intval($fortress_arr['bb_id']);
$marr = $_pm['mysql'] -> getOneRecord('SELECT player.nickname as nickname,hp,mp,srchp,srcmp,addhp,addmp,level,player.headimg FROM player,userbb WHERE player.id = '.$uid.' AND userbb.uid=player.id AND userbb.id='.$fortressPetId);
$marr = is_array($marr) ? $marr : array();
$equipment = $fortressPetId > 0 ? getzbAttrib($fortressPetId) : array();
$equipmentHp = is_array($equipment) && isset($equipment['hp']) ? max(0, intval(round($equipment['hp']))) : 0;
$equipmentMp = is_array($equipment) && isset($equipment['mp']) ? max(0, intval(round($equipment['mp']))) : 0;
$hpMax = max(1, fortressCardInt($marr, 'srchp') + $equipmentHp);
$mpMax = max(1, fortressCardInt($marr, 'srcmp') + $equipmentMp);
$hpRate = max(0, min(100, 100 * (fortressCardInt($marr, 'hp') + fortressCardInt($marr, 'addhp')) / $hpMax));
$mpRate = max(0, min(100, 100 * (fortressCardInt($marr, 'mp') + fortressCardInt($marr, 'addmp')) / $mpMax));
$nickname = htmlspecialchars(isset($marr['nickname']) ? (string)$marr['nickname'] : '', ENT_QUOTES, 'UTF-8');
$level = isset($marr['level']) ? intval($marr['level']) : 0;
$headimg = isset($marr['headimg']) ? intval($marr['headimg']) : 0;
$playerinfo = '<div class="team">
            <div class="name">'.$nickname.'</div>
            <div class="level">'.$level.'</div>
            <div class="avatar"><img src="../images/tarot/face'.$headimg.'.gif" /></div>
            <div class="red"><p style="width:'.$hpRate.'%"></p></div>
            <div class="blue"><p style="width:'.$mpRate.'%"></p></div>
        </div>';

$tn = $_game['template'] . 'tpl_fortressCard.html';
$pinfo = '';
if (file_exists($tn)){
	$tpl = @file_get_contents($tn);

	$src = array(
				 '#js#',
				 '#finfo#',
				 '#ctime#',
				 '#touqu#',
				 '#playerinfo#'
				 );
	$des = array(
				  $jsstr,
				  $finfo,
				  $ctime,
				  $touqu,
				  $playerinfo
				);
	$pinfo = str_replace($src, $des, $tpl);
}

// gzip echo. if maybe.
ob_start('ob_gzip');
echo $pinfo;
ob_end_flush();
realseLock();
$_pm['mem']->memClose();

function timetos($num){
	$h = substr($num,0,2);
	$i = substr($num,2,2);
	$s = substr($num,4,2);
	$date = date('Y-m-d').' '.$h.':'.$i.':'.$s;
	$ds = strtotime($date);
	return $ds;
}
?>
