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

define(MEM_TOP_KEY, "publictop");
define(MEM_PRETOP_KEY, "sspetpublictop");
define("MEM_CZLTOP_KEY", "growthrankingtop");
define('SORT_NUM', 50);
require_once('../config/config.game.php');
require_once('../login/curl.php');
require_once('../sec/dblock_fun.php');
secStart($_pm['mem']);
//$interface_top = "http://pmmg1.webgame.com.cn/interface/kffight_status.php";
$user = $_pm['user']->getUserById($_SESSION['id']);
//$top = unserialize($_pm['mem']->get(MEM_TOP_KEY));
//跨服争霸
//$res_status = curl_get($interface_top."?status=1");	//排名

//等级排行
//if (!is_array($top) || $top['time']+3600<time()) 
if (!is_array($top) || $top['time']+3600<time()) 
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
foreach ($top as $k => $rs)
{
	//if ($k == 'time') continue;
	if(is_array($rs))
	{
		$pub .= '<tr id="pai'.$rs['id'].'"><td width="40px">'. ($pos++) .'</td><td width="80px" onmousedown="seethepet(this)" style="text-align:left">'. $rs['name'] .'</td><td width="70px" style="text-align:left">'. $rs['level'] .'</td><td style="text-align:left" >'. $rs['nickname'] .'</td></tr>';
	}
}
//威望排行

$putop = unserialize($_pm['mem']->get(MEM_PRETOP_KEY));
if (!is_array($putop) || $putop['time']+3600<time()) 
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
			$sspub .= '<tr id="ssp'.$rs['id'].'">
			  	<td width="50px">'. ($pos++) .'</td>
				<td width="80px" onmousedown="seethepet(this)" style="text-align:left">'.$rs['name'] .'</td>
			 	 <td width="80px" style="text-align:left">'.$rs['czl'].'</td>
			 	 <td style="text-align:left" >'.$rs['username'].'</td>
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
$memtimeconfig = unserialize($_pm['mem']->get('db_timeconfignew'));
$config=$memtimeconfig['consumptionTop'][0];
$configColor=$memtimeconfig['consumptionColor'][0];
if($config['starttime']==0){
	$ybtop=$ybprize='活动没有开启！';
}else{
	if(!is_array($rows)){
		$ybtop =  '暂时还没有人消费！';
	}else{
		$colorarr = explode(',',$configColor['days']);
		foreach($rows as $k => $v){
			$ruser = $_pm['mysql'] -> getOneRecord('SELECT id,nickname FROM player WHERE name = "'.$v['nickname'].'"');
			if($v['fee'] >= $colorarr[0]){
				$ybtop .= '<tr>
					<td width="40px"><font color=red>'. ($k+1) .'</font></td>
					 <td style="text-align:left"><font color=red>'. $ruser['nickname'] .'</font></td>
					</tr>';
			}else if($v['fee'] >= $colorarr[1]){
				$ybtop .= '<tr>
					<td width="40px"><font color=blue>'. ($k+1) .'</font></td>
					 <td style="text-align:left"><font color=blue>'. $ruser['nickname'] .'</font></td>
					</tr>';
			}else if($v['fee'] >= $colorarr[2]){
				$ybtop .= '<tr>
					<td width="40px"><font color=green>'. ($k+1) .'</font></td>
					 <td style="text-align:left"><font color=green>'. $ruser['nickname'] .'</font></td>
					</tr>';
			}else{
				$ybtop .= '<tr>
					<td width="40px">'. ($k+1) .'</td>
					 <td style="text-align:left">'. $ruser['nickname'] .'</td>
					</tr>';
			}
		}
	}
	
	if($config['starttime']>date('H')) //|| $config['endtime']<date('H'))
	{//查昨天的排名
		//$ybprize='还没有开奖，请继续等待吧！';
		$yes = date("Ymd", strtotime("1 days ago"));
		$sql = 'SELECT pnote FROM gamelog WHERE vary = 240 AND buyer = '.$yes.' ORDER BY id';
		$arr = $_pm['mysql'] -> getRecords($sql);
		if($arr){
			foreach($arr as $k => $av){
				$ybprize .= '<tr>
				<td width="40px">'. ($k+1) .'</td>
				 <td style="text-align:left">'. $v['pnote'] .'</td>
				</tr>';
			}
		}
	}else{
	    echo 'Current date: ' . date('Ymd');  
$ck = $_pm['mysql']->getOneRecord('select id from gamelog where vary=240 AND buyer="'.date('Ymd').'" limit 1');
	    
// 		$ck=$_pm['mysql']->getOneRecord('select id from gamelog where vary=240 AND buyer="'.date('Ymd').'" limit 1');
		
		if(!$ck){
			//发公告
			require_once('../sec/dblock_fun.php');
			$a = getLock(1);
			
			$now = date('Ymd');
			$check = unserialize($_pm['mem'] -> get('fee_prize_check'));
			if($check != $now){
				$task = new task();//恭喜xxx（玩家名）荣登今日消费排行榜榜首，请获得今日消费排行的玩家前往公告牌及时领取奖励。
				foreach($rows as $rk => $rv){
					if($rk > 2){
						break;
					}
					$ruser = $_pm['mysql'] -> getOneRecord('SELECT id,nickname FROM player WHERE name = "'.$rv['nickname'].'"');
					$prizes=explode(',',$config['days']);
					foreach($prizes as $k=>$v)
					{
						if($k >= $rk){
							$res = explode(';',$v);
							if($res[1] < $rv['fee']){
								if($flag == 0){
									$word = "恭喜 {$ruser['nickname']} ,荣登本周消费排行榜榜首，获得相应珍贵奖励。";
									$swfData=iconv('gbk','utf-8',$word);
									require_once('../socketChat/config.chat.php');	
									require_once('../kernel/socketmsg.v1.php');
									$s=new socketmsg();
									$s->sendMsg('an|'.$swfData);
									$str = '<font color=red>'.$ruser['nickname'].'</font>';
								}else if($flag == 1){
									$str = '<font color=blue>'.$ruser['nickname'].'</font>';
								}else if($flag == 2){
									$str = '<font color=green>'.$ruser['nickname'].'</font>';
								}
								//5.4后不支挂
								givePrize($rv['nickname'],$res[0],$task);
								$sql = 'insert into gamelog set buyer="'.date('Ymd').'",vary=240,seller='.$ruser['id'].',ptime='.time().',pnote="'.$str.'"';
								$_pm['mysql']->query($sql);
								$flag++;
								break;
							}
						}
					}
				}
				$num = rand(0,(count($rows)-1));
				$xprize = $rows[$num];//幸运奖
				$ruser = $_pm['mysql'] -> getOneRecord('SELECT id,nickname FROM player WHERE name = "'.$xprize['nickname'].'"');
				
				$sql = 'insert into gamelog set buyer="'.date('Ymd').'",vary=240,seller='.$ruser['id'].',ptime='.time().',pnote="'.$ruser['nickname'].'"';
				$_pm['mysql']->query($sql);
				$word = "恭喜 {$ruser['nickname']} ,荣登本周消费排行幸运奖，获得相应奖励。";
				$swfData=iconv('gbk','utf-8',$word);
				require_once('../socketChat/config.chat.php');	
				require_once('../kernel/socketmsg.v1.php');
				$s=new socketmsg();
				$s->sendMsg('an|'.$swfData);
			//5.3
				givePrize($xprize['nickname'],$prizes[3],$task);
				$_pm['mem'] -> set(array('k'=>'fee_prize_check','v'=>$now));
			}
		}
		$today = date("Ymd");
		$sql = 'SELECT pnote FROM gamelog WHERE vary = 240 AND buyer = '.$today.' ORDER BY id';
		$arr = $_pm['mysql'] -> getRecords($sql);//print_r($arr);exit;
		if($arr){
			foreach($arr as $k => $av){
				$ybprize .= '<tr>
				<td width="40px">'. ($k+1) .'</td>
				 <td style="text-align:left">'. $av['pnote'] .'</td>
				</tr>';
			}
		}
	}
	realseLock();
}

function saveGetPropsMore_S($pid,$num,$uid)
{
	global $_pm;
	if ($pid == '' or $pid == 0) return false;
	global $db;
	$l=0;
	
	$rs = false;
	$rs = $_pm['mysql']->getOneRecord("SELECT * FROM userbag WHERE uid={$uid} and pid={$pid}");
	if (is_array($rs))
	{
		if ($rs['vary'] == 1) // 可折叠道具.
		{
			$tt = time();
			$sql = "UPDATE userbag
						   SET sums=sums+$num,
							   stime={$tt}
						 WHERE id={$rs['id']}
					  ";
			$_pm['mysql']->query($sql);
			$str .= $sql;
		}
		else
		{
			$sql = "INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
						VALUES(
							   '{$uid}',
							   '{$pid}',
							   '{$rs['sell']}',
							   '{$rs['vary']}',
							   {$num},
							   unix_timestamp()
							  );
					  ";
			$_pm['mysql']->query($sql);
			$str .= $sql;
	   }	   
	}
	else{
		$rs = $_pm['mysql'] -> getOneRecord("SELECT * FROM props WHERE id = $pid");
		if (is_array($rs))
		{
			$sql = "INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
						VALUES(
							   '{$uid}',
							   '{$pid}',
							   '{$rs['sell']}',
							   '{$rs['vary']}',
							   {$num},
							   unix_timestamp()
							  )
					  ";
			$_pm['mysql']->query($sql);
			$str .= $sql;
		}else{
			return false;
		}
	}		
	unset($rs);
	return true;
}
//5.3
function givePrize($name,$pstr,$tsk)
{
	global $_pm;
	$user=$_pm['mysql']->getOneRecord('select id from player where name="'.$name.'" limit 1');
	if(!$user)
	{
		echo mysql_error();
		return;
	}
	$prize=explode('|',$pstr);
	foreach($prize as $p)
	{
		$t=explode(':',$p);
		if(!saveGetPropsMore_S($t[0],$t[1],$user['id']))
		{
			$log='insert into gamelog set buyer="'.date('Ymd').'",vary=239,seller='.$user['id'].',ptime='.time().',pnote="发放奖励失败,用户:'.$name.',奖品id:'.$t[0].',数量:'.$t[1].'"';
		}else{
			$log='insert into gamelog set buyer="'.date('Ymd').'",vary=239,seller='.$user['id'].',ptime='.time().',pnote="发放奖励成功,用户:'.$name.',奖品id:'.$t[0].',数量:'.$t[1].'"';
		}
		$_pm['mysql']->query($log);
	}
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

$user = $_pm['user']->getUserById($_SESSION['id']);
$czltop = unserialize($_pm['mem']->get(MEM_CZLTOP_KEY));
if (!is_array($czltop) || $czltop['time'] + 3600 < time()) 
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
			$czlpub .= '<tr id="czl'.$rs['id'].'">
			  	<td width="40px">'. ($czlpos++) .'</td>
			 	 <td width="80px" onmousedown="seethepet(this)" style="text-align:left">'. $rs['name'] .'</td>
			 	 <td width="70px" style="text-align:left">'. $rs['jprestige'] .'</td>
                 <td width="" style="text-align:left">'. $rs['nickname'] .'</td>
				</tr>';
		}
	}
}

//活动介绍
$welcome = memContent2Arr("db_welcome",'code');
$message = $welcome['public']['contents'];
//task check.
$taskword= taskcheck(intval($user['task']),8);
//
$_pm['mem']->memClose();
unset($db);

//@Load template.
$type = $_GET['type'];
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
				 '#gangyin#'
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
				 $gangyin
				);
	$public = str_replace($src, $des, $tpl);
}

// gzip echo. if maybe.
ob_start('ob_gzip');
echo $public;
ob_end_flush();
?>
