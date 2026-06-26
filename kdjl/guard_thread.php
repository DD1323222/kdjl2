<?php
ini_set('display_errors',true);
error_reporting(E_ALL);
$socket_port = getSocketPortgt($_SERVER['HTTP_HOST']);
set_time_limit(180);
ignore_user_abort(true);

define('MAX_SLEEP_TIME', 10);
define('WAIT_TIME', 120);
define('WAIT_TIME_LONG', 360);
$ip = "127.0.0.1";

$socket = socket_create (AF_INET, SOCK_DGRAM, SOL_UDP);
$bind = @socket_bind ($socket, $ip, $socket_port); 
if(!$bind)
{
	die("//exit ".$socket_port);
}
//
require_once(dirname(__FILE__).'/config/config.game.php');
require_once(dirname(__FILE__).'/kernel/socketmsg.v1.php');
require_once('sec/dblock_fun.php');
require_once('socketChat/config.chat.php');	

//定时清除分解次数限制
$day_zbfj = unserialize($_pm['mem'] -> get('SYS_ZBFJ_NEW'));
$_pm['mysql']->query("UPDATE player SET heart_time = ".time()." WHERE id = '{$_SESSION['id']}'");
//print_r($day_zbfj);
$today = date('Ymd',time());
if( empty($day_zbfj) )
{
	$_pm['mem']->set(array('k'=>'SYS_ZBFJ_NEW','v'=>$today));
}
else
{
	if( $day_zbfj != $today )
	{
		//清空
		$day_zbfj = unserialize($_pm['mem'] -> get('zbfj_info'));
		foreach( $day_zbfj as $key => $val )
		{
			$new_info[$key] = 5; 
		}
		$_pm['mem']->set(array('k'=>'zbfj_info','v'=>$new_info));
		$_pm['mem']->set(array('k'=>'SYS_ZBFJ_NEW','v'=>$today));
	}
}

doWork1(time());
doWork2(time());
//doWork3(time());
doWork4(time());
doWork5(time());
require_once(dirname(__FILE__).'/socketChat/config.chat.php');	
$s=new socketmsg();
checkGuildFightEnd();
function calcGuildFight($day='')
{
	global $_pm,$s;
	if($day=='') $day=date('Ymd');
	
	$table=$_pm['mysql']->getOneRecord('show create TABLE guild_challenges'.$day);
	if($table)//已经计算了！
	{
		return;
	}
	$_pm['mysql']->close();
	$_pm['mysql']	= new mysql();
	$tables=$_pm['mysql']->getRecords('show tables like "guild_challenges%"');

	$tl=count($tables);
	if($tl>5)
	{
		$min=999999999;
		   //20100818
		foreach($tables as $v)
		{
			foreach($v as $n)
			{
				if($tl>5&&strlen($n)>16)
				{
					$_pm['mysql']->query('drop table '.$n);
					$tl--;
				}
			}		
		}
		
	}
	
	$tables1=$_pm['mysql']->getRecords('show tables like "ticket_%"');

	$tl1=count($tables1);
	if($tl1>5)
	{
		$min=999999999;
		   //20100818
		foreach($tables1 as $v1)
		{
			foreach($v1 as $n1)
			{
				if($tl1>5&&strlen($n1)>7)
				{
					$_pm['mysql']->query('drop table '.$n1);
					$tl1--;
				}
			}		
		}
		
	}

	$challenges=$_pm['mysql']->getRecords('select challenger_id,defenser_id,challenger_score,defenser_score from guild_challenges where flags=1');
	
	foreach($challenges as $challenge)
	{
		$c=$_pm['mysql']->getOneRecord('select name from guild where id='.$challenge['challenger_id']);
		$d=$_pm['mysql']->getOneRecord('select name from guild where id='.$challenge['defenser_id']);
		if($challenge['challenger_score']!=$challenge['defenser_score'])
		{
			if($challenge['challenger_score']>$challenge['defenser_score'])
			{
				$sql1='update guild set victory_times=victory_times+1 where id='.$challenge['challenger_id'];
				$sql2='update guild set failed_times =failed_times+1  where id='.$challenge['defenser_id'];
				$msg=iconv('utf-8','utf-8','<strong>《'.$c['name'].'》</strong>家族在与<strong>《'.$d['name'].'》</strong>家族的战斗中获得胜利！');
			}	
			else if($challenge['challenger_score']<$challenge['defenser_score'])
			{
				$sql1='update guild set failed_times =failed_times +1 where id='.$challenge['challenger_id'];
				$sql2='update guild set victory_times=victory_times+1 where id='.$challenge['defenser_id'];
				$msg=iconv('utf-8','utf-8','<strong>《'.$c['name'].'》</strong>家族在与<strong>《'.$d['name'].'》</strong>家族的战斗中失败！');
			}
			$_pm['mysql']->query($sql1);
			$_pm['mysql']->query($sql2);
		}else{
			$msg=iconv('utf-8','utf-8','<strong>《'.$c['name'].'》</strong>家族与<strong>《'.$d['name'].'》</strong>家族战成平局');
		}
		$s->sendMsg('SYS|'.$msg,'__ALL__');
	}
	
	$table=$_pm['mysql']->getOneRecord('show create TABLE guild_challenges');
	$_pm['mysql']->query('rename table guild_challenges to guild_challenges'.$day);	
	$_pm['mysql']->query($table['Create Table']);
}


function checkGuildFightEnd()
{
	global $_pm;
	$week = date("N", time());
	$hourM= date("Hi", time());
	
	$battletimearr = unserialize($_pm['mem']->get(MEM_TIME_KEY));
	foreach($battletimearr as $bv)
	{
		if($bv['titles'] != "guild_battle")
		{
			continue;
		}
		//if($bv['days']!=0&&$week==0) $week=7;
		if(isset($_GET['manual']))
		{
			if($week==0) $week=7;
			if($bv['days']==0) $bv['days']=7;			
			calcGuildFight(date('Ym').(intval(date('d'))-($week+7-$bv['days'])));
		}
		else if($week == $bv['days'] && $hourM > $bv['endtime'])//家族战结束了
		{			
			calcGuildFight();
		}
		else
		{
			//echo $week .'=='. $bv['days'] .'&&'. $hourM .'>'. $bv['endtime'].'<br>';
		}
	}
	return false;
}

$curminute = intval(date("i"));
if($curminute%2==0) 
{	
	$cur = $_pm['mysql']->getOneRecord('select left(from_unixtime(ctime),16) lastct from game_count order by id desc limit 1');
	if(!$cur||$cur['lastct']!=date("Y-m-d H:i")){
		//$domainPrefix = substr($_SERVER['HTTP_HOST'],0,strpos($_SERVER['HTTP_HOST'],"."));
		$domainPrefix = substr($_SERVER['HTTP_HOST'],0,strpos($_SERVER['HTTP_HOST'],"."));
		$domainPrefix = "pokeelf";
		$old = unserialize($_pm['mem']->get($domainPrefix.'_online_user_list'));
		if(!is_array($old)) $old=array();
		$time = time()-300;
		foreach($old as $k=>$t)
		{
			if($t<$time) unset($old[$k]);
		}
		$_pm['mem']->set(array('k'=>$domainPrefix.'_online_user_list','v'=>$old));
		$_pm['mem']->set(array('k'=>$domainPrefix.'_online_user','v'=>count($old)));
		$_pm['mysql']->close();
		$_pm['mysql']	= new mysql();
		$sql = "insert into game_count(ctime,online) values('".time()."','".(count($old))."')";
		//$_pm['mysql']->query('delete from game_count where left(from_unixtime(ctime),16)="'.date("Y-m-d H:i").'"');
		$_pm['mysql']->query($sql);
	}
	sleep(1);
}

//杀死掉的mysql线程
function doWork1($time)
{
	global $_pm,$port;
	$minute = date("YmdHi",$time);			
	$curminute = intval(date("i",$time));

	$_pm['mem']->set(array('k'=>memKeyStep1,'v'=>'game_count_ended_'.time()));
	//杀mysql死线程
	if($time%10<=5)
	{
		mysql_close($_pm['mysql']->getConn());
		$_pm['mysql']->close();
		$_pm['mysql']	= new mysql();
		$conn = $_pm['mysql']->getConn();
		//$result = mysql_query("SHOW PROCESSLIST",$conn);
		$result = $_pm['mysql']->getRecords("SHOW PROCESSLIST");
		//logsqlerr(__LINE__.' - '.$conn.' - '.$conn);
		if($result){
			$_pm['mem']->set(
							array('k'=>'guard_threadk'.$port,'v'=>$time.' - thread num: '.count($result))
						);
		}else{			
			$_pm['mem']->set(
							array('k'=>'guard_threadk'.$port,'v'=>$time.' - error: '.mysql_error())
						);
			logsqlerr(__LINE__.' - '.$conn.' - '.$conn);
		}
		
		foreach ($result as $proc)
		{
			//echo '$proc["Command"]='.$proc["Command"].', $proc["State"]='.$proc["State"].', $proc["Time"]='.$proc["Time"].'<br/>';
			//flush();
			if($proc["Command"] == "Locked")
			{
				@$_pm['mysql']->query("KILL query " . $proc["Id"], $conn);
				@$_pm['mysql']->query("KILL " . $proc["Id"], $conn);
				logsqlerr(__LINE__);
			}
			
			if($proc["Command"] == "Sleep" && $proc["Time"] > MAX_SLEEP_TIME) {
				@$_pm['mysql']->query("KILL " . $proc["Id"], $conn);
				logsqlerr(__LINE__);
				//echo 'kill 1<br>';
				//flush();
			}
			else if
			( 
				($proc["State"] == "Sending data"||$proc["State"] == "end") 
				&& 
				$proc["Time"] > WAIT_TIME
			)
			{
				@$_pm['mysql']->query("KILL query " . $proc["Id"], $conn);
				@$_pm['mysql']->query("KILL " . $proc["Id"], $conn);
				logsqlerr(__LINE__);
				//echo 'kill 1<br>';
				//flush();
			}else if($proc["Time"] > WAIT_TIME_LONG){
				@$_pm['mysql']->query("KILL query " . $proc["Id"], $conn);
				@$_pm['mysql']->query("KILL " . $proc["Id"], $conn);
				logsqlerr(__LINE__);
				//echo 'kill 1<br>';
				//flush();
			}
		}
		logsqlerr(__LINE__);
		//flush();
	}else{
		$_pm['mem']->set(
							array('k'=>'guard_threadk'.$port,'v'=>$time.'=$time%10: '.($time%10))
						);
	}
	$_pm['mem']->set(array('k'=>memKeyStep1,'v'=>'will_sleep_'.time()));
}

//战场结束，统计排名，领取奖励
function doWork2($time)
{
	return;
	global $_pm;
	$timeconfig = unserialize($_pm['mem']->get('db_timeconfig'));
	if(!is_array($timeconfig)) return;
	foreach($timeconfig as $v){
		if($v['titles'] == 'battle'){
			$arr[$v['days']] = $v['endtime'];
		}
		else continue;
	}
	if(!isset($arr) || !is_array($arr)) return;
	$day = date('w');
	$str = $arr[$day];
	if(empty($str)) return;
	$hi = date('H:i'); 
	
	//避免重复发奖
	$check = unserialize($_pm['mem'] -> get('battle_prize_check'));
	$timenow = time() - 300;
	if(!empty($check) && $check <= $timenow) return;
	$_pm['mem'] -> set(array('k'=>'battle_prize_check','v'=>time()));
	 
	 
	if($str != $hi) return;
	//找到胜利方
	$sql = "SELECT id,min(hp)AS hp,posname FROM battlefield LIMIT 1";
	$winner = $_pm['mysql'] -> getOneRecord($sql);
	//// 战场胜利公告
	if($winner['id'] == 1) $fail = '暗夜女神阵营';
	else if($winner['id'] == 2 ) $fail = '自然女神阵营';
	$pub = new task();
	$word = '[系统公告] 本次战场结束，'.$fail.'被打得溃不成军，'.$winner['posname'].'取得了胜利！';
	for($i=0;$i<5;$i++){
		$pub-> saveGword($word, 1);
	}
	
	
	// 获取胜利方所有玩家的相关信息并进行本次战场发放奖励
	$today = time() - 3600;
	$winarr = $_pm['mysql']->getRecords("SELECT id 
													FROM battlefield_user
												   WHERE lastvtime>$today and curjgvalue>0 and pos={$winner['id']}
												   ORDER BY curjgvalue DESC
												   LIMIT 0,10
												");
	if(is_array($winarr)){
		$v = '';
		foreach($winarr as $k => $v){
			$boxnum = 0;
			$jgvl   = 0;
			switch(($k+1))
		   {
			  case 1: $boxnum=10; $jgvl = 2000; break;
			  case 2: 
			  case 3: $boxnum=6; $jgvl = 1500;break;
			  case 4:
			  case 5:
			  case 6: $boxnum=4; $jgvl = 1000;break;
			  case 7:
			  case 8:
			  case 9:
			  case 10: $boxnum=2; $jgvl = 500;break;
			  default: $boxnum=$jgvl=0;
		   }
		  // 更新玩家的排名.
		  $_pm['mysql']->query("UPDATE battlefield_user 
								   SET tops=".($k+1).", boxnum={$boxnum}, curjgvalue=curjgvalue+{$jgvl}
								 WHERE id={$v['id']}
							   ");
		}
	}
	// 获取失败方所有玩家的相关信息并进行本次战场排名更新。
	$all = $_pm['mysql']->getRecords("SELECT id 
										FROM battlefield_user
									   WHERE lastvtime>$today and curjgvalue>0 and pos!={$winner['id']}
									   ORDER BY curjgvalue DESC
									   LIMIT 0,10
									");
	if (is_array($all))
   {
	   foreach ($all as $k => $rs)
	   {
		   $boxnum = 0;
		   $jgvl   = 0;
		   switch(($k+1))
		   {
			  case 1: $boxnum=5; $jgvl = 1000; break;
			  case 2: 
			  case 3: $boxnum=3; $jgvl = 500;break;
			  case 4:
			  case 5:
			  case 6: $boxnum=2; $jgvl = 300;break;
			  case 7:
			  case 8:
			  case 9:
			  case 10: $boxnum=1; $jgvl = 100;break;
			  default: $boxnum=$jgvl=0;
		   }
		   // 更新玩家的排名.
		   $_pm['mysql']->query("UPDATE battlefield_user 
									SET tops=".($k+1).", boxnum={$boxnum}, curjgvalue=curjgvalue+{$jgvl}
								  WHERE id={$rs['id']}
							   ");
	   }
   }
   
   $time = time();
   $_pm['mysql'] -> query("INSERT INTO gamelog (ptime,buyer,seller,pnote,vary) VALUES($time,'1','1','jgprize','200')");
}

/*
function doWork3($time){
	global $_pm;
	$sql='select sum(yb) fee,nickname from yblog where buytime>'.strtotime(date("Y-m-d ").'00:00:00').' and buytime<'.strtotime(date("Y-m-d ").'23:59:59').' group by nickname order by sum(yb) desc limit 50';
	$rows = $_pm['mysql']->getRecords($sql);
	$memtimeconfig = unserialize($_pm['mem']->get('db_timeconfignew'));
	$config=$memtimeconfig['consumptionTop'][0];
	if($config['starttime']==0){
		return;
	}else{
		if($config['starttime']>date('H') || $config['endtime']<date('H'))
		{
			return;
		}else{
			$ck=$_pm['mysql']->getOneRecord('select id from gamelog where vary=240 AND buyer="'.date('Ymd').'" limit 1');//检查发奖
			if(!$ck){
				//发公告
				
				$a = getLock(1);
				
				$now = date('Ymd');
				$check = unserialize($_pm['mem'] -> get('fee_prize_check'));
				if($check != $now){
					$_pm['mem'] -> set(array('k'=>'fee_prize_check','v'=>$now));
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
										$word = "恭喜 {$ruser['nickname']} ,荣登今日消费排行榜榜首，获得相应珍贵奖励。";
										$swfData=iconv('utf-8','utf-8',$word);
										$s=new socketmsg();
										$s->sendMsg('an|'.$swfData);
										$str = '<font color=red>'.$ruser['nickname'].'</font>';
									}else if($flag == 1){
										$str = '<font color=blue>'.$ruser['nickname'].'</font>';
									}else if($flag == 2){
										$str = '<font color=green>'.$ruser['nickname'].'</font>';
									}
									givePrize($rv['nickname'],$res[0],&$task);
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
					$word = "恭喜 {$ruser['nickname']} ,荣登今日消费排行幸运奖，获得相应奖励。";
					$swfData=iconv('utf-8','utf-8',$word);
					$s->sendMsg('an|'.$swfData);
					givePrize($xprize['nickname'],$prizes[3],&$task);
				}
			}
		}
		realseLock();
	}
}
*/


function doWork4($time){
	global $_pm;
	$setting = $_pm['mem']->get('db_welcome1');
	if(!is_array($setting)) $setting=unserialize($setting);
	if(!is_array($setting))
	{
		return '后台配置数据读取失败(1)！'.print_r($setting,1);
	}

	$time_settings=explode("|",$setting['fortress_time']);
	$w=date('w');
	$hm=date('His');
	if($w==0)
	{
		$w=7;
	}
	$time_flag=false;
	foreach($time_settings as $s)
	{
		$tmp=explode(',',$s);
		//1,210000,210459,212959,213459
		if($w==$tmp[0])
		{
			if($hm>$tmp[4])
			{
				$time_flag=true;
			}
			break;
		}
	}

	if(!$time_flag){
		return '现在不是要塞结束时间！';
	}

	$a = getLock(1);
	$mk='yaosai_prize_set2_'.date('Ymd');
	$flag = $_pm['mem']->get($mk);
	
	
	if(!$flag)
	{
		$notice='';
		$table_name="`fortress_users_".date("Ymd")."`";
		$users_first=$_pm['mysql']->getRecords('select * from '.$table_name.' a, (select max(score_final) score_final from '.$table_name.'  where score_final != 0 group by at_section_num)z where a.score_final=z.score_final');
		$set=explode(" ",$setting['fortress']);
		$prize_set=array();
		foreach($set as $k=>$s)
		{
			$tmp=explode(',',$s);
			$prize_set[$k+1]=$tmp[3];
		}
		$props = unserialize($_pm['mem']->get("db_propsid"));
		if( is_array($users_first) )
		{
			foreach($users_first as $user)
			{
				if($user['score_final']<0) continue;
				$tmp=$prize_set[$user['at_section_num']];
				$prizes=explode('|',$tmp);
				$notice.=iconv('utf-8','utf-8','<br/>&nbsp;&nbsp;&nbsp;&nbsp;恭喜玩家：').iconv('utf-8','utf-8',$user['nickname']).iconv('utf-8','utf-8','获得女神要塞成长'.$user['at_section_num'].'阶段要塞第一名！获得:');
				foreach($prizes as $p)
				{
					$t=explode(':',$p);
					if(!saveGetPropsMore_S($t[0],$t[1],$user['user_id']))
					{
						$log='insert into gamelog set buyer="'.date('Ymd').'",vary=246,seller='.$user['user_id'].',ptime='.time().',pnote="发放奖励失败,成长范围阶段：'.$user['at_section_num'].',用户:'.$user['user_id'].',奖品id:'.$t[0].',数量:'.$t[1].'"';
					}else{
						$log='insert into gamelog set buyer="'.date('Ymd').'",vary=246,seller='.$user['user_id'].',ptime='.time().',pnote="发放奖励成功,成长范围阶段：'.$user['at_section_num'].',用户:'.$user['user_id'].',奖品id:'.$t[0].',数量:'.$t[1].'"';
					}
					$notice.=iconv('utf-8','utf-8',$props[$t[0]]['name'].' '.$t[1].'个 ');
					$_pm['mysql']->query($log);
				}
			}
		}
		else
		{
			$notice = iconv('utf-8','utf-8','<br/>&nbsp;&nbsp;&nbsp;&nbsp;传说中纷争不断的女神要塞今天好像并没有发生过激烈的战斗...');
		}
		$s=new socketmsg();
		$swfData=$notice;
		$s->sendMsg('an|'.$swfData);
		$_pm['mem']->set(array('k'=>$mk,'v'=>1));
	}
	realseLock();
}


function write_log($vary,$log,$seller){
	global $_pm;
	$_pm['mysql'] -> query('insert into gamelog set buyer='.$seller.',vary='.$vary.',seller='.$seller.',ptime='.time().',pnote="'.$log.'"');
}

function in_arr($arr,$newarr){
	$tarr = $newarr[rand(0,(count($newarr)-1))];
	if(in_array($tarr['ticket_num'],$arr)){
		in_arr($arr,$newarr);
	}else{
		return $tarr;
	}
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

function givePrize($name,$pstr,&$tsk)
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


function wr($i){
	$filename = dirname(__FILE__).'/t/test'.$i.'.txt';
	$somecontent = date("Y-m-d H:i:s")."\r\n";

    $handle = fopen($filename, 'a+');

    // 将$somecontent写入到我们打开的文件中。
    if (fwrite($handle, $somecontent) === FALSE) {
        exit;
    }

    fclose($handle);
}


function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}


function sstrgt($str)
{
	$ends = array('.com.cn','.cn','.com','.net','.net.cn');
	$strs = str_replace($ends,'',strtolower($str));
	$strs = explode('.',$strs);
	return $strs[0].$strs[count($strs)-1];
}

function getSocketPortgt($str)
{	
	global $ax,$az;
	$maxNum = 45100;
	$rtn = abs(crc32(sstrgt($str)));
	$x = intval($rtn/$maxNum);
	
	while($maxNum>10000&&($x<10000||$x>50000))
	{
		$maxNum -= 7000;
		$x 		 = intval($rtn/$maxNum);
	}	

	if($x/63>$maxNum)
	{
		$x=$x/63;
	}
	if($x/33>$maxNum)
	{
		$x=$x/33;
	}
	if($x/13>$maxNum)
	{
		$x=$x/13;
	}
	if($x/7>$maxNum)
	{
		$x=$x/7;
	}
	if($x/3>$maxNum)
	{
		$x=$x/3;
	}

	while($x>50000)
	{
		$x=$x/1.26;
	}
	$rtn      = floor($x);
	if($rtn<10000)
	{
		$rtn = substr('10000',0,5-strlen($rtn)).$rtn;
	}	
	
	return $rtn;
}

function logsqlerr($msg="")
{
	global $_pm,$portadd;
	if($err=mysql_error())
	{
		$old = date("Y-m-d H:i:s").'('.$portadd.'):'.$err.'<br>'.$msg.'<br/>'.unserialize($_pm['mem']->get('guard_thread_error'));
		if(strlen($old )>1024*4) $old = substr($old ,0,1024*3);
		$_pm['mem']->set(array('k'=>'guard_thread_error','v'=>$old));
	}
	else if($msg!="")
	{
		$old = date("Y-m-d H:i:s").'('.$portadd.'):'.$msg.'<br/>'.unserialize($_pm['mem']->get('guard_thread_error'.$portadd));
		if(strlen($old )>1024*4) $old = substr($old ,0,1024*3);
		$_pm['mem']->set(array('k'=>'guard_thread_error'.$portadd,'v'=>$old));
	}
	
}
function doWork5($time)
{
	global $_pm;
	$clear = unserialize($_pm['mem']->get('SL_CLEAR_TIME'));
	$in = date('Ymd',$time);
	if(empty($clear) || !isset($clear))
	{
		$_pm['mem']->set(array('k'=>'SL_CLEAR_TIME','v'=>$in));
	}
	else
	{
		if($clear != $in)
		{
			$_pm['mysql'] -> query("UPDATE player_ext SET F_saolei_points = '1'");
			$_pm['mem'] -> del("today_sl_user");
			$_pm['mem'] -> del("today_is_use_ticket");
			$_pm['mem'] -> del("sl_die_option");
			$_pm['mem'] -> del("sl_prize_info");
			$_pm['mem']->set(array('k'=>'SL_CLEAR_TIME','v'=>$in));
		}
	}
}
echo "//OK";
?>
