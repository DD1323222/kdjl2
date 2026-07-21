<?php
/*
 *
 */
require_once('../config/config.game.php');
require_once(dirname(__FILE__).'/fortress_common.php');
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid <= 0)
{
	die('登录状态已失效，请重新登录！');
}
define('MEM_FIGHTUSER_KEY', $uid . 'fuser');
secStart($_pm['mem']);
$bag		= $_pm['user']->getUserBagById($uid);
if(!is_array($bag)) $bag = array();

function msg($m)
{
	die($m);
}

function fortressStolenHtml($value)
{
	return htmlspecialchars(strval($value), ENT_QUOTES, 'UTF-8');
}

function fortressStolenJsDouble($value)
{
	return str_replace(
		array("\\", "\r", "\n", "\"", "</"),
		array("\\\\", "\\r", "\\n", "\\\"", "<\/"),
		strval($value)
	);
}

function fortressStolenInt($row, $key)
{
	return (is_array($row) && isset($row[$key])) ? intval($row[$key]) : 0;
}

$setting = $_pm['mem']->get('db_welcome1');
if(!is_array($setting)) $setting = kdjlSafeMemValue($setting, false);
if(!is_array($setting))
{
	msg('后台配置数据读取失败(1)！');
}

if(!isset($setting['fortress_time']))
{
	msg('缺少活动开启设定(fortress_time)！');
}

$stolenStr="";
$stolen_arr=array();
$stolen_arr1=array();
$stolen_arr2=array();
foreach($bag as $v)
{
	if(!is_array($v) || !isset($v['effect']) || !is_string($v['effect']) ||
		intval(isset($v['sums']) ? $v['sums'] : 0) <= 0 ||
		intval(isset($v['zbing']) ? $v['zbing'] : 0) != 0 ||
		intval(isset($v['cantrade']) ? $v['cantrade'] : 0) == 3) continue;
	if(strpos($v["effect"],'stolen_yaosai_jifen:')!==false)
	{
		if(intval(isset($v['expire']) ? $v['expire'] : 0)>0){
			$stolen_arr1[]=$v;
		}else{
			$stolen_arr2[]=$v;
		}
	}
}

$stolen_arr=array_merge($stolen_arr1,$stolen_arr2);
function tosecond($str)
{
	return substr($str,0,2)*3600+substr($str,2,2)*60+substr($str,-2);
}

$time_settings=explode("|",$setting['fortress_time']);
$w=intval(date('w'));
$hmText=date('His');
$hm=intval($hmText);
if($w==0)
{
	$w=7;
}
$time_flag=false;
$timejs='var fortressStolenActive=false;var times=[0,0,0];';
foreach($time_settings as $s)
{
	$tmp=explode(',',$s);
	if(count($tmp) < 5) continue;
	//1,210000,210459,212959,213459
	$day = intval($tmp[0]);
	$stolenStart = intval($tmp[3]);
	$stolenEnd = intval($tmp[4]);
	if($w == $day)
	{
		if($hm >= $stolenStart && $hm <= $stolenEnd)
		{
			$timejs='var fortressStolenActive=true;var times=['.tosecond($tmp[3]).','.tosecond($tmp[4]).','.tosecond($hmText).'];';
			$time_flag=true;
		}
		break;
	}
}


$table_name   = "`fortress_users_".date("Ymd")."`";
$user_fortress= $_pm['mysql']->getOneRecord('select v_times,f_times,fv_result,at_section_num,bb_id from '.$table_name.' where user_id='.$uid);

if(!$user_fortress)
{
	msg('<script language="javascript">parent.Alert("您没有参加要塞活动！");history.back();</script>');
}


$key='fortress_score_'.date("Ymd").'_'.$user_fortress['at_section_num'];
$fortress_over=kdjlSafeMemValue($_pm['mem']->get($key), 0);

if($time_flag && !$fortress_over)
{
	if(!$_pm['mysql']->query('update '.$table_name.' set score_final=score where score_final=0 and at_section_num='.intval($user_fortress['at_section_num'])))
	{
		msg('要塞积分初始化失败，请稍候再试！');
	}
	$_pm['mem']->setexpire(array('k'=>$key,'v'=>1), fortressDailyCacheTtl());
}



$js='';
if(isset($_GET['stolen']))
{

	if(!$time_flag){
		echo '<script language="javascript">parent.Alert("现在不是要塞夺取时间！");history.back();</script>';
	}else if(empty($stolen_arr)){
		echo '<script language="javascript">parent.Alert("您没有夺取道具！");history.back();</script>';
	}else{
		require_once('../sec/dblock_fun.php');
		require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
		$s=new socketmsg();
		$a = getLock($uid);
		if(!is_array($a)){
			die('<script language="javascript">parent.Alert("服务器繁忙，请稍候再试！");history.back();</script>');
		}
		$pendingAnnouncement = '';

		$sql = 'select score,score_final,user_id,nickname from '.$table_name.' where score_final>CEIL(score/2) and user_id<>'.$uid.' and at_section_num='.intval($user_fortress['at_section_num']).' order by score_final desc';
		$fortress_score=$_pm['mysql']->getRecords($sql);
		if(empty($fortress_score)){
			$_pm['mysql']->query('ROLLBACK');
			realseLock();
			die('<script language="javascript">parent.Alert("没有玩家可以夺取！");history.back();</script>');
		}
		$stolenDone = false;
		foreach($stolen_arr as $k=>$arr)
		{
			if($arr['sums']>0)
			{
				$r1=rand(1,100);
				$effParts=explode(':',$arr["effect"],2);
				if(count($effParts) < 2) continue;
				$eff=explode(';',$effParts[1]);

				$point=0;
				$percent=0;
				$percentMatched=false;
				foreach($eff as $e)
				{
					$tmp=explode(',',$e);
					if(count($tmp) < 2) continue;
					$tmp1=explode('-',$tmp[0]);
					$tmp2=explode('-',$tmp[1]);
					if(count($tmp1) < 2 || count($tmp2) < 2) continue;
					$rangeStart = intval($tmp1[0]);
					$rangeEnd = intval($tmp1[1]);
					$percentStart = intval($tmp2[0]);
					$percentEnd = intval($tmp2[1]);
					if($percentStart > $percentEnd) continue;
					if($rangeStart <= $r1 && $r1 <= $rangeEnd)
					{
						$percent=rand($percentStart,$percentEnd);
						$percentMatched=true;
						break;
					}
				}
				if(!$percentMatched || $percent <= 0) continue;
				$aim_name='';
				if(is_array($fortress_score)&&count($fortress_score))
				{
					$r2=rand(1,count($fortress_score));
					$aim_row=$fortress_score[$r2-1];

					$oldScore=max(0, intval($aim_row['score_final']));
					$minimumScore=max(0, intval(ceil(intval($aim_row['score'])/2)));
					$requestedPoint=max(1, intval(ceil(($percent/100)*$oldScore)));
					$new_score=max($minimumScore, $oldScore-$requestedPoint);
					$point=$oldScore-$new_score;
					if($point <= 0) continue;
					$sql='update '.$table_name.' set score_final='.$new_score.' where user_id='.intval($aim_row['user_id']).' and score_final='.$oldScore;
					if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
						$_pm['mysql']->query('ROLLBACK');
						realseLock();
						die('<script language="javascript">parent.Alert("夺取目标积分已变化，请重试！");history.back();</script>');
					}
					$aim_name=$aim_row['nickname'];
					$sql='update '.$table_name.' set score_final=score_final+'.$point.' where user_id='.$uid;
					if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
						$_pm['mysql']->query('ROLLBACK');
						realseLock();
						die('<script language="javascript">parent.Alert("夺取失败，请稍候再试！");history.back();</script>');
					}

					$nicknamea=fortressStolenHtml(isset($_SESSION['nickname']) ? $_SESSION['nickname'] : '');
					$nicknameb=fortressStolenHtml($aim_name);
					$pendingAnnouncement='<strong>'.$nicknamea.'</strong>夺取了<strong>'.$nicknameb.'</strong> '.$point.' 点要塞积分。';
				}

				$bagId = intval($arr['id']);
				$bagSums = intval($arr['sums']);
				$sql='update userbag set sums=sums-1 where uid='.$uid.' and id='.$bagId.' and sums='.$bagSums.
					' and sums>0 and zbing=0 and (cantrade IS NULL OR cantrade<>3)';
				if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
					$_pm['mysql']->query('ROLLBACK');
					realseLock();
					die('<script language="javascript">parent.Alert("夺取道具数量已变化，请刷新后再试！");history.back();</script>');
				}
				$js='parent.Alert("成功夺取了'.fortressStolenJsDouble($aim_name).'的'.intval($point).'点积分！")';
				$stolen_arr[$k]['sums']=$bagSums-1;
				$stolenDone = true;
				break;
			}
		}
		if(!$stolenDone){
			$_pm['mysql']->query('ROLLBACK');
			realseLock();
			die('<script language="javascript">parent.Alert("您没有可用的夺取道具！");history.back();</script>');
		}
		if(!$_pm['mysql']->query('COMMIT')){
			$_pm['mysql']->query('ROLLBACK');
			realseLock();
			die('<script language="javascript">parent.Alert("服务器繁忙，请稍候再试！");history.back();</script>');
		}
		$_pm['mem']->del(MEM_USERBAG_KEY);
		if($pendingAnnouncement !== '') $s->sendMsg('an|'.$pendingAnnouncement,'__ALL__');
		realseLock();
	}
}

foreach($stolen_arr as $v)
{
	if($stolenStr !== '') $stolenStr .= '、';
	$stolenStr.=fortressStolenHtml(isset($v['name']) ? $v['name'] : '').'('.intval(isset($v['sums']) ? $v['sums'] : 0).')';
}

$sql = 'select score,score_final,user_id,nickname from '.$table_name.' where at_section_num='.intval($user_fortress['at_section_num']).' order by score_final desc';

$fortress_score=$_pm['mysql']->getRecords($sql);
if(!is_array($fortress_score)) $fortress_score = array();

$ph='';
foreach($fortress_score as $k=>$row)
{
$scoreNickname = fortressStolenHtml(isset($row['nickname']) ? $row['nickname'] : '');
	$scoreFinal = isset($row['score_final']) ? intval($row['score_final']) : 0;
	$ph.='
            <tr>
              <td align="center" class="text01">'.($k+1).'</td>
              <td align="center" class="text01">'.$scoreNickname.'</td>
              <td align="center" class="text01">'.$scoreFinal.'</td>
            </tr>';
}
$fortressPetId = intval($user_fortress['bb_id']);
$marr = $_pm['mysql'] -> getOneRecord('SELECT player.nickname as nickname,hp,mp,srchp,srcmp,addhp,addmp,level,player.headimg FROM player,userbb WHERE player.id = '.$uid.' AND userbb.uid=player.id AND userbb.id='.$fortressPetId);
$marr = is_array($marr) ? $marr : array();
$equipment = $fortressPetId > 0 ? getzbAttrib($fortressPetId) : array();
$equipmentHp = is_array($equipment) && isset($equipment['hp']) ? max(0, intval(round($equipment['hp']))) : 0;
$equipmentMp = is_array($equipment) && isset($equipment['mp']) ? max(0, intval(round($equipment['mp']))) : 0;
$hpMax = max(1, fortressStolenInt($marr, 'srchp') + $equipmentHp);
$mpMax = max(1, fortressStolenInt($marr, 'srcmp') + $equipmentMp);
$hpRate = max(0, min(100, 100 * (fortressStolenInt($marr, 'hp') + fortressStolenInt($marr, 'addhp')) / $hpMax));
$mpRate = max(0, min(100, 100 * (fortressStolenInt($marr, 'mp') + fortressStolenInt($marr, 'addmp')) / $mpMax));
$nickname = fortressStolenHtml(isset($marr['nickname']) ? $marr['nickname'] : '');
$level = fortressStolenInt($marr, 'level');
$headimg = fortressStolenInt($marr, 'headimg');
$playerinfo = '<div class="team">
            <div class="name">'.$nickname.'</div>
            <div class="level">'.$level.'</div>
            <div class="avatar"><img src="../images/tarot/face'.$headimg.'.gif" /></div>
            <div class="red"><p style="width:'.$hpRate.'%"></p></div>
            <div class="blue"><p style="width:'.$mpRate.'%"></p></div>
        </div>';
$tn = $_game['template'].'tpl_fortress_stolen.html';
$ret = '';
if (file_exists($tn))
{
	$tpl = file_get_contents($tn);

	$src = array(
				 "#stolenStr#",
				 "#v_times#",
				 "#sv_times#",
				 '#f_times#',
				 '#sf_times#',
				 '#ph#',
				 '#timejs#',
				 '#info#'
				);
	$des = array(
				 $stolenStr,
				 $user_fortress['v_times'],
				 $user_fortress['fv_result']>0?$user_fortress['fv_result']:0,
				 $user_fortress['f_times'],
				 $user_fortress['fv_result']<0?abs($user_fortress['fv_result']):0,
				 $ph,
				 $timejs,
				 $playerinfo
			);
	$ret = str_replace($src, $des, $tpl);
}

$_pm['mem']->memClose();
// gzip echo. if maybe.
ob_start();
echo $ret;
ob_end_flush();
//$('gw').contentWindow.location='/function/fortress_Mod.php';
?>
