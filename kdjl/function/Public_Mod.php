<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.05.22
*@Usage: Public top (GongGao Bang.)
*@Note: none
*/

define('MEM_TOP_KEY', "publictop");
define('MEM_PRETOP_KEY', "sspetpublictop");
define("MEM_CZLTOP_KEY", "growthrankingtop");
define('SORT_NUM', 50);
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');
secStart($_pm['mem']);
function publicModHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
$publicPrizePendingLogIds = array();
$publicPrizeCommitted = true;
$publicPrizeTransactionActive = false;
function publicPrizeTrackLastLog()
{
	global $_pm,$publicPrizePendingLogIds;
	$id = intval($_pm['mysql']->last_id());
	if($id < 1) return false;
	$publicPrizePendingLogIds[$id] = $id;
	return true;
}
function publicPrizeCleanupPendingLogs()
{
	global $_pm,$publicPrizePendingLogIds,$publicPrizeCommitted;
	if(!$publicPrizeCommitted && !empty($publicPrizePendingLogIds) && isset($_pm['mysql']))
	{
		$_pm['mysql']->query('DELETE FROM gamelog WHERE id IN ('.implode(',',array_values($publicPrizePendingLogIds)).')');
	}
	$publicPrizePendingLogIds = array();
}
function publicPrizeShutdown()
{
	global $_pm,$publicPrizeTransactionActive;
	if($publicPrizeTransactionActive && isset($_pm['mysql'])) $_pm['mysql']->query('ROLLBACK');
	$publicPrizeTransactionActive = false;
	publicPrizeCleanupPendingLogs();
	if(function_exists('realseLock')) realseLock();
}
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');
$user = $_pm['user']->getUserById($uid);
$pub = '';
$sspub = '';
$ybtop = '';
$ybprize = '';
$top = kdjlSafeMemValue($_pm['mem']->get(MEM_TOP_KEY), array());
//等级排行
//if (!is_array($top) || $top['time']+3600<time())
if (!is_array($top) || !isset($top['time']) || intval($top['time'])+3600<time())
{
	$toprs = $_pm['mysql']->getRecords("SELECT b.id,b.name,level,nickname
								FROM userbb as b,player as u
							   WHERE u.id = b.uid and (u.secid is null or u.secid=0)
							   ORDER BY level DESC,nowexp DESC
							   LIMIT 0,30
							");
	if (!is_array($toprs)) $pub = '排行榜为空!';
	else
	{
		$toprs['time'] = time();
		$_pm['mem']->set(array('k' =>MEM_TOP_KEY, 'v' => $toprs ));
		$top = $toprs;
		unset($toprs);
	}
}
$pos = 1;
if(is_array($top)) foreach ($top as $k => $rs)
{
	//if ($k == 'time') continue;
	if(is_array($rs))
	{
		$petName = publicModHtml(isset($rs['name']) ? $rs['name'] : '');
		$nickname = publicModHtml(isset($rs['nickname']) ? $rs['nickname'] : '');
		$petId = isset($rs['id']) ? intval($rs['id']) : 0;
		$level = isset($rs['level']) ? intval($rs['level']) : 0;
		$pub .= '<tr id="pai'.$petId.'"><td width="40px">'. ($pos++) .'</td><td width="80px" onmousedown="seethepet(this)" style="text-align:left">'. $petName .'</td><td width="70px" style="text-align:left">'. $level .'</td><td style="text-align:left" >'. $nickname .'</td></tr>';
	}
}
//威望排行

$putop = kdjlSafeMemValue($_pm['mem']->get(MEM_PRETOP_KEY), array());
if (!is_array($putop) || !isset($putop['time']) || intval($putop['time'])+3600<time())
{
	$putoprs = $_pm['mysql']->getRecords("SELECT id,name,username,czl FROM userbb WHERE wx = 7 ORDER by czl+0 desc limit 15");
	if (!is_array($putoprs)) $sspub = '排行榜为空!';
	else
	{
		$putoprs['time'] = time();
		$_pm['mem']->set(array('k' =>MEM_PRETOP_KEY, 'v' => $putoprs ));
		$putop = $putoprs;
		unset($putoprs);
	}
}

$pos = 1;
$k = 0;
$rs = "";
if(is_array($putop))
{
	foreach ($putop as $k => $rs)
	{
		if(is_array($rs))
		{
			$ssPetId = isset($rs['id']) ? intval($rs['id']) : 0;
			$ssName = publicModHtml(isset($rs['name']) ? $rs['name'] : '');
			$ssCzl = publicModHtml(isset($rs['czl']) ? $rs['czl'] : '');
			$ssUser = publicModHtml(isset($rs['username']) ? $rs['username'] : '');
			$sspub .= '<tr id="ssp'.$ssPetId.'">
				<td width="50px">'. ($pos++) .'</td>
				<td width="80px" onmousedown="seethepet(this)" style="text-align:left">'.$ssName.'</td>
				 <td width="80px" style="text-align:left">'.$ssCzl.'</td>
				 <td style="text-align:left" >'.$ssUser.'</td>
				</tr>';
		}
	}
}
//消费排行
//strtotime(date("Y-m-d").' 00:00:00')
$sql='select sum(yb) fee,nickname from yblog
where buytime >= '.strtotime('2014-02-03 00:00:00').'
and buytime < '.strtotime('2014-02-09 23:00:00').'
group by nickname order by sum(yb) desc limit 50';
$rows = $_pm['mysql']->getRecords($sql);
if(!is_array($rows)) $rows = array();
$memtimeconfig = kdjlSafeMemValue($_pm['mem']->get('db_timeconfignew'), array());
if(!is_array($memtimeconfig)) $memtimeconfig = array();
$config=isset($memtimeconfig['consumptionTop'][0]) ? $memtimeconfig['consumptionTop'][0] : array('starttime'=>0,'days'=>'');
$configColor=isset($memtimeconfig['consumptionColor'][0]) ? $memtimeconfig['consumptionColor'][0] : array('days'=>'0,0,0');
if(!isset($config['starttime'])) $config['starttime'] = 0;
if(!isset($config['days'])) $config['days'] = '';
if(!isset($configColor['days'])) $configColor['days'] = '0,0,0';
if($config['starttime']==0){
	$ybtop=$ybprize='活动没有开启！';
}else{
	if(empty($rows)){
		$ybtop =  '暂时还没有人消费！';
	}else{
		$colorarr = explode(',',$configColor['days']);
		while(count($colorarr) < 3) $colorarr[] = 0;
		foreach($rows as $k => $v){
			$safeTopName = $_pm['mysql']->escape($v['nickname']);
			$ruser = $_pm['mysql'] -> getOneRecord('SELECT id,nickname FROM player WHERE name = "'.$safeTopName.'"');
			$showTopName = is_array($ruser) && isset($ruser['nickname']) ? $ruser['nickname'] : $v['nickname'];
			$showTopNameHtml = publicModHtml($showTopName);
			if($v['fee'] >= $colorarr[0]){
				$ybtop .= '<tr>
					<td width="40px"><font color=red>'. ($k+1) .'</font></td>
					 <td style="text-align:left"><font color=red>'. $showTopNameHtml .'</font></td>
					</tr>';
			}else if($v['fee'] >= $colorarr[1]){
				$ybtop .= '<tr>
					<td width="40px"><font color=blue>'. ($k+1) .'</font></td>
					 <td style="text-align:left"><font color=blue>'. $showTopNameHtml .'</font></td>
					</tr>';
			}else if($v['fee'] >= $colorarr[2]){
				$ybtop .= '<tr>
					<td width="40px"><font color=green>'. ($k+1) .'</font></td>
					 <td style="text-align:left"><font color=green>'. $showTopNameHtml .'</font></td>
					</tr>';
			}else{
				$ybtop .= '<tr>
					<td width="40px">'. ($k+1) .'</td>
					 <td style="text-align:left">'. $showTopNameHtml .'</td>
					</tr>';
			}
		}
	}

	if($config['starttime']>date('H')) //|| $config['endtime']<date('H'))
	{//查昨天的排名
		//$ybprize='还没有开奖，请继续等待吧！';
		$yes = date("Ymd", strtotime("1 days ago"));
				$sql = 'SELECT pnote FROM gamelog WHERE vary = 240 AND buyer = '.$yes.' AND seller>0 ORDER BY id';
		$arr = $_pm['mysql'] -> getRecords($sql);
		if($arr){
			foreach($arr as $k => $av){
				$pnote = publicModHtml(isset($av['pnote']) ? $av['pnote'] : '');
				$ybprize .= '<tr>
				<td width="40px">'. ($k+1) .'</td>
				 <td style="text-align:left">'. $pnote .'</td>
				</tr>';
			}
		}
	}else{
$ck = $_pm['mysql']->getOneRecord('select id from gamelog where vary=240 AND buyer="'.date('Ymd').'" limit 1');

// 		$ck=$_pm['mysql']->getOneRecord('select id from gamelog where vary=240 AND buyer="'.date('Ymd').'" limit 1');

		if(!$ck){
			//发公告
			require_once('../sec/dblock_fun.php');
			$a = getLock(-240);
			if(!is_array($a)){
				realseLock();
			}else{

			$now = date('Ymd');
			$check = $_pm['mem'] -> get('fee_prize_check');
			if(!is_string($check)) $check = '';
			$lockedPrizeCheck = $_pm['mysql']->getOneRecord('select id from gamelog where vary=240 AND buyer="'.$now.'" limit 1');
			if(is_array($lockedPrizeCheck))
			{
				$check = $now;
				$_pm['mem']->set(array('k'=>'fee_prize_check','v'=>$now));
			}
			if($check != $now){
				$publicPrizePendingLogIds = array();
				$publicPrizeCommitted = false;
				$publicPrizeTransactionActive = true;
				register_shutdown_function('publicPrizeShutdown');
				$task = new task();//恭喜xxx（玩家名）荣登今日消费排行榜榜首，请获得今日消费排行的玩家前往公告牌及时领取奖励。
				$flag = 0;
				$allPrizeOk = true;
				$pendingPrizeAnnouncements = array();
				$feePrizeRecipientIds = array();
				$prizes=explode(',',$config['days']);
				if(count($prizes) < 4) $allPrizeOk = false;
				$rankRows = $allPrizeOk ? $rows : array();
				foreach($rankRows as $rk => $rv){
					if($rk > 2){
						break;
					}
					$safePrizeName = $_pm['mysql']->escape($rv['nickname']);
					$ruser = $_pm['mysql'] -> getOneRecord('SELECT id,nickname FROM player WHERE name = "'.$safePrizeName.'"');
					if(!is_array($ruser) || !isset($ruser['id'])) continue;
					foreach($prizes as $k=>$v)
					{
						if($k >= $rk){
							$res = explode(';',$v);
							if(count($res) != 2 || trim($res[0]) == '' || !is_numeric(trim($res[1]))){
								$allPrizeOk = false;
								break 2;
							}
							if(intval($res[1]) <= intval($rv['fee'])){
								if($flag == 0){
									$word = "恭喜 {$ruser['nickname']} ,荣登本周消费排行榜榜首，获得相应珍贵奖励。";
									$str = '<font color=red>'.htmlspecialchars((string)$ruser['nickname'], ENT_QUOTES, 'UTF-8').'</font>';
								}else if($flag == 1){
									$word = '';
									$str = '<font color=blue>'.htmlspecialchars((string)$ruser['nickname'], ENT_QUOTES, 'UTF-8').'</font>';
								}else if($flag == 2){
									$word = '';
									$str = '<font color=green>'.htmlspecialchars((string)$ruser['nickname'], ENT_QUOTES, 'UTF-8').'</font>';
								}
								//5.4后不支挂
								if(!givePrize($rv['nickname'],$res[0],$task)){
									$allPrizeOk = false;
									break 2;
								}
								if($flag == 0 && $word != ''){
									$pendingPrizeAnnouncements[] = $word;
								}
								$safeRankNote = $_pm['mysql']->escape($str);
								$sql = 'insert into gamelog set buyer="'.date('Ymd').'",vary=240,seller='.$ruser['id'].',ptime='.time().',pnote="'.$safeRankNote.'"';
								if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
									$allPrizeOk = false;
									break 2;
								}
								if(!publicPrizeTrackLastLog()){
									$allPrizeOk = false;
									break 2;
								}
								$flag++;
								break;
							}
						}
					}
				}
				if($allPrizeOk && !empty($rows) && isset($prizes[3]) && $prizes[3] != '')
				{
					$num = rand(0,(count($rows)-1));
					$xprize = $rows[$num];//幸运奖
					if(!is_array($xprize)) $xprize = array('nickname' => '');
					if(!isset($xprize['nickname'])) $xprize['nickname'] = '';
					$safeLuckyName = $_pm['mysql']->escape($xprize['nickname']);
					$ruser = $_pm['mysql'] -> getOneRecord('SELECT id,nickname FROM player WHERE name = "'.$safeLuckyName.'"');
					if(is_array($ruser) && isset($ruser['id']))
					{
						$word = "恭喜 {$ruser['nickname']} ,荣登本周消费排行幸运奖，获得相应奖励。";
					//5.3
						if(givePrize($xprize['nickname'],$prizes[3],$task)){
							$safeLuckyNote = $_pm['mysql']->escape($ruser['nickname']);
							$sql = 'insert into gamelog set buyer="'.date('Ymd').'",vary=240,seller='.$ruser['id'].',ptime='.time().',pnote="'.$safeLuckyNote.'"';
							if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
								$allPrizeOk = false;
							}else if(!publicPrizeTrackLastLog()){
								$allPrizeOk = false;
							}else{
								$pendingPrizeAnnouncements[] = $word;
							}
						}else{
							$allPrizeOk = false;
						}
					}
					else
					{
						$allPrizeOk = false;
					}
				}
				if($allPrizeOk)
				{
					$completeNote = $_pm['mysql']->escape('consumption prize complete');
					$completeSql = 'insert into gamelog set buyer="'.date('Ymd').'",vary=240,seller=-240,ptime='.time().',pnote="'.$completeNote.'"';
					if(!$_pm['mysql']->query($completeSql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
					{
						$_pm['mysql']->query('ROLLBACK');
						$allPrizeOk = false;
					}
					else if(!publicPrizeTrackLastLog())
					{
						$allPrizeOk = false;
					}
				}
				if($allPrizeOk)
				{
					if(!$_pm['mysql']->query('COMMIT'))
					{
						$_pm['mysql']->query('ROLLBACK');
						$allPrizeOk = false;
						$publicPrizeTransactionActive = false;
						publicPrizeCleanupPendingLogs();
					}
					else
					{
						$publicPrizeCommitted = true;
						$publicPrizeTransactionActive = false;
						$_pm['mem'] -> set(array('k'=>'fee_prize_check','v'=>$now));
						foreach($feePrizeRecipientIds as $recipientId)
						{
							$_pm['mem']->del(intval($recipientId).'bag');
						}
						if(!empty($pendingPrizeAnnouncements))
						{
							require_once('../socketChat/config.chat.php');
							require_once('../kernel/socketmsg.v1.php');
							$s=new socketmsg();
							foreach($pendingPrizeAnnouncements as $pendingWord)
							{
								$s->sendMsg('an|'.$pendingWord);
							}
						}
					}
				}
				else
				{
					$_pm['mysql']->query('ROLLBACK');
					$publicPrizeTransactionActive = false;
					publicPrizeCleanupPendingLogs();
				}
			}
			realseLock();
			}
		}
		$today = date("Ymd");
		$sql = 'SELECT pnote FROM gamelog WHERE vary = 240 AND buyer = '.$today.' AND seller>0 ORDER BY id';
		$arr = $_pm['mysql'] -> getRecords($sql);//print_r($arr);exit;
		if($arr){
			foreach($arr as $k => $av){
				$pnote = publicModHtml(isset($av['pnote']) ? $av['pnote'] : '');
				$ybprize .= '<tr>
				<td width="40px">'. ($k+1) .'</td>
				 <td style="text-align:left">'. $pnote .'</td>
				</tr>';
			}
		}
	}
}

function givePrize($name,$pstr,$tsk)
{
	global $_pm,$feePrizeRecipientIds;
	$safeName = $_pm['mysql']->escape($name);
	$user=$_pm['mysql']->getOneRecord('select id from player where name="'.$safeName.'" limit 1');
	if(!is_array($user) || !isset($user['id']))
	{
		return false;
	}
	$parsedPrize = array();
	foreach(explode('|',$pstr) as $p)
	{
		$t=explode(':',trim($p));
		if(count($t) != 2) return false;
		$pid = intval($t[0]);
		$num = intval($t[1]);
		if($pid < 1 || $num < 1) return false;
		$parsedPrize[] = array($pid,$num);
	}
	if(empty($parsedPrize)) return false;

	$issued = false;
	foreach($parsedPrize as $prizeInfo)
	{
		$pid = $prizeInfo[0];
		$num = $prizeInfo[1];
		$logName = $_pm['mysql']->escape($name);
		$giveResult = $tsk->saveGetPropsMore($pid,$num,0,intval($user['id']));
		if($giveResult !== true)
		{
			return false;
		}else{
			$log='insert into gamelog set buyer="'.date('Ymd').'",vary=239,seller='.$user['id'].',ptime='.time().',pnote="发放奖励成功,用户:'.$logName.',奖品id:'.$pid.',数量:'.$num.'"';
			$issued = true;
		}
		if(!$_pm['mysql']->query($log) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) return false;
		if(!publicPrizeTrackLastLog()) return false;
	}
	if($issued) $feePrizeRecipientIds[intval($user['id'])] = intval($user['id']);
	return $issued;
}

//成长排行
function sort_by_czl($records)
{
    $orderd = array();
    if (is_array($records) && !empty($records)) {
        $tmp = array();

        foreach($records as $r) {
            $tmp[] = $r['jprestige'];
        }
        arsort($tmp);
        foreach($tmp as $k => $v) {
            $orderd[] = $records[$k];
        }
        $orderd = array_slice($orderd, 0, SORT_NUM);
    }

    return $orderd;
}

$user = $_pm['user']->getUserById($uid);
$czltop = kdjlSafeMemValue($_pm['mem']->get(MEM_CZLTOP_KEY), array());
$czlpub = '';
if (!is_array($czltop) || !isset($czltop['time']) || $czltop['time'] + 3600 < time())
{
    $czltoprs = $_pm['mysql']->getRecords("SELECT id,name, czl as jprestige, username as nickname FROM `userbb`
        WHERE czl IS NOT NULL and wx != 7
        ORDER BY czl+0 DESC
        LIMIT 0,150");
    $czltoprs = sort_by_czl($czltoprs);
	if (!is_array($czltoprs)) $czlpub = '排行榜为空!';
	else
	{
		$czltoprs['time'] = time();
		$_pm['mem']->set(array('k' =>MEM_CZLTOP_KEY, 'v' => $czltoprs ));
		$czltop = $czltoprs;
		unset($czltoprs);
	}
}
$czlpos = 1;
$k = 0;
$rs = "";
if(is_array($czltop))
{
	foreach ($czltop as $k => $rs)
	{
		if(is_array($rs))
		{
			$petId = isset($rs['id']) ? intval($rs['id']) : 0;
			$petName = publicModHtml(isset($rs['name']) ? $rs['name'] : '');
			$petCzl = publicModHtml(isset($rs['jprestige']) ? $rs['jprestige'] : '');
			$ownerName = publicModHtml(isset($rs['nickname']) ? $rs['nickname'] : '');
			$czlpub .= '<tr id="czl'.$petId.'">
				<td width="40px">'. ($czlpos++) .'</td>
				 <td width="80px" onmousedown="seethepet(this)" style="text-align:left">'. $petName .'</td>
				 <td width="70px" style="text-align:left">'. $petCzl .'</td>
                 <td width="" style="text-align:left">'. $ownerName .'</td>
				</tr>';
		}
	}
}

//活动介绍
$welcome = memContent2Arr("db_welcome",'code');
$message = isset($welcome['public']['contents']) ? $welcome['public']['contents'] : '';
//task check.
$taskid = is_array($user) && isset($user['task']) ? intval($user['task']) : 0;
$taskword= taskcheck($taskid,8);
//
$_pm['mem']->memClose();
unset($db);

//@Load template.
$type = (isset($_GET['type']) && !is_array($_GET['type']) && $_GET['type'] === 'active') ? 'active' : '';
$public = '';
if(!isset($pub)) $pub = '';
if(!isset($sspub)) $sspub = '';
if(!isset($ybtop)) $ybtop = '';
if(!isset($ybprize)) $ybprize = '';
if(!isset($cmd)) $cmd = '';
if(!isset($gangyin)) $gangyin = '';
$kfFightBaseUrl = kdjlConfiguredServiceBaseUrl('KDJL_KF_FIGHT_BASE_URL');
$kfUserDeal = $kfFightBaseUrl === ''
	? '<div id="userdeal" style="text-align:center">跨服战中心未配置</div>'
	: '<div id="userdeal"><img style="float:left" src="../new_images/ui/bm.jpg" onclick="user(1)" alt="报名" /><img style="float:right" src="../new_images/ui/lj.jpg" onclick="user(2)" alt="领奖" /></div>';
if(!isset($group) || !is_array($group)) $group = array();
for($groupIndex = 1; $groupIndex <= 3; $groupIndex++)
{
	if(!isset($group[$groupIndex])) $group[$groupIndex] = '';
}
$tn = $_game['template'] . 'tpl_public.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);

	$src = array(
				 '#word#',
				 '#publiclist#',
				 '#sspubliclist#',
				 '#czlpubliclist#',
				 '#message#',
				 '#type#',
				 '#ybtop#',
				 '#ybprize#',
				 '#group1#',
				 '#group2#',
				 '#group3#',
				 '#cmd#',
				 '#gangyin#',
				 '#kfuserdeal#'
				);
	$des = array(
				 $taskword,
				 $pub,
				 $sspub,
				 $czlpub,
				 $message,
				 $type,
				 $ybtop,
				 $ybprize,
				 $group[1],
				 $group[2],
				 $group[3],
				 $cmd,
				 $gangyin,
				 $kfUserDeal
				);
	$public = str_replace($src, $des, $tpl);
}

// gzip echo. if maybe.
ob_start('ob_gzip');
echo $public;
ob_end_flush();
?>
