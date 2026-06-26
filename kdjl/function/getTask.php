<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.12.24
*@Usage: 任务奖励系统
×调整为：
  已经有的：拜访：NPC编号(see:9,)
			杀怪需求：杀怪范围：个数。(killmon:44|45:5);

* 增加各种奖励格式：
   giveitem:X|Z:Y 
		收集ID为X、Z的道具共Y个,任务完成时扣除收集的道具
   giveitem:X:Y, giveitem:Z:Y
		意义：收集ID为X、Z的道具各Y个,任务完成时扣除收集的道具
   
   killmon:X|Y|Z:M, killmon:E|F|G:M
		意义：其中X、Y、Z、E、F、G均为怪物的ID数，M为需要杀死的怪物个数
   
   从几个物品里随机选择一个给予的规则：
	itemrand:X:Y:Z|E:F:G|A:B:C

	props:X:Y|A:B
	表示：同时获得ID为X的道具Y个、ID为A的道具B个。
    
	gonggao:玩家名字+公告内容(中文输入)

*@Note: none
*/

require_once('../config/config.game.php');
secStart($_pm['mem']);

$user		= $_pm['user']->getUserById($_SESSION['id']);
$bag		= $_pm['user']->getUserBagById($_SESSION['id']);
$petsAll	= $_pm['user']->getUserPetById($_SESSION['id']);
$bbs = unserialize($_pm['mem']->get(MEM_BB_KEY));
$memtask = unserialize($_pm['mem']->get(MEM_TASK_KEY));

$n = intval($_REQUEST['n']);
$s = intval($_REQUEST['s']);
$tsk = new task();
$type = $_REQUEST['type'];

if($type == "get")//接受任务
{
	$taskid = intval($_REQUEST['taskid']);
	if(empty($taskid))
	{
		die("数据错误！");
	}
	/*$taskinfo = $_pm['mem']->dataGet(array('k'	=>	MEM_TASK_KEY,
										  'v'	=> "if(\$rs['id']=={$taskid}) \$ret=\$rs;"
										  ));*/
	$taskinfo = $memtask[$taskid];
	if(!is_array($taskinfo)) die("数据错误！");
	if(empty($taskinfo['fromnpc'])){
		die('数据错误！');
	}
	//只能完成一次,等级限制的任务
	if(strpos($taskinfo['cid'],'rwl') !== false){
		if($taskinfo['hide'] != 1){
			$a = explode('|',$taskinfo['fromnpc']);
			$rwlarr = $_pm['mysql'] -> getOneRecord("SELECT taskid FROM tasklog WHERE uid = {$_SESSION['id']} AND xulie = {$taskinfo['xulie']} and fromnpc = {$a[0]}");
			if(!is_array($rwlarr)){
				die("数据错误1!");
			}
			$lar = $memtask[$rwlarr['taskid']];
			$rwl = explode('|',str_replace('rwl:','',$lar['cid']));
			if($rwl[1] != $taskid){
				die("数据错误2");
			}
		}else{
			$rwlarr = $_pm['mysql'] -> getOneRecord("SELECT taskid FROM tasklog WHERE uid = {$_SESSION['id']} AND taskid = {$taskid}");
			if(is_array($rwlarr)){
				die('不能接受此任务！');
			}
		}
	}
	if(!empty($taskinfo['limitlv']))
	{
		$limitarr = explode(",",$taskinfo['limitlv']);
		if(is_array($limitarr))
		{
			foreach($limitarr as $v)
			{
				$limitarrs = explode(":",$v);
				switch($limitarrs[0])
				{
					case "lv":
						foreach($petsAll as $bb)
						{
							if($bb['id'] == $user['mbid'])
							{
								$blv = $bb['level'];
							}
						}
						if(empty($blv))
						{
							die("请先到牧场设置主战！");
						}
						$lvarr = explode("|",$limitarrs[1]);
						if($lvarr[1] == "0")
						{
							if($blv < $lvarr[0])
							{
								die("您的等级不够接受此任务！");
							}
						}
						else
						{
							if($blv < $lvarr[0] || $blv > $lvarr[1])
							{
								die("您的等级不在可接此任务范围之内！");
							}
						}
						break;
					case "wx":
						$_mbwx='';		
						foreach($petsAll as $bb)
						{
							if($bb['id'] == $user['mbid'])
							{
								$_mbwx = $bb['wx'];
							}
						}
						if(empty($_mbwx))
						{
							die("请先到牧场设置主战！");
						}
						$wxs=explode('|',$limitarrs[1]);
						if(!in_array($_mbwx,$wxs))
						{						
							die("主战宠物五行与任务不符合任务要求！");
						}
						break;
					case "xfyb"://字段格式xfyb：xxxxxxxx|xxxxxxxx；xxxx|yyyy代表某年某月某日到某年某月某日需要消费须在xxxx到yyyy间的玩家才能接受任务
						$sql="select * from yblog where nickname='{$user['name']}'";
						$t=$_pm['mysql'] -> getRecords($sql);
						if(!is_array($t))
						{
							die("你未进行元宝消费，无法领取任务!");
						}else{
							
							$xfyb=explode(";",$limitarrs[1]);
							$xfyb1=explode("|",$xfyb[0]);//时间段
							$xfyb2=explode("|",$xfyb[1]);//元宝消费段
							$sum_yb=0;
							if(is_array($xfyb2) && is_array($xfyb1)){
								foreach($t as $k=>$v){
									if(date(Ymd,$v['buytime'])>=$xfyb1[0] && date(Ymd,$v['buytime'])<=$xfyb1[1]){
										$sum_yb+=$v['yb'];
										
									}
								}
								
								$taskidxfyb=$_pm['mysql']->getRecords('select id,limitlv from task where  left(limitlv,4)="xfyb"');
								$taskidxfybs=array();
								$taskidxfybinfos=array();
								foreach($taskidxfyb as $row)
								{
									$taskidxfybs[$row['id']]=$row['id'];
									$taskidxfybinfos[$row['id']]=$row['limitlv'];
								}
								
								$mytasklogssql='select taskid from tasklog where uid='.$_SESSION['id'].' and taskid in ('.implode(',',array_values($taskidxfybs)).');';
								$mytasklogs=$_pm['mysql']->getRecords($mytasklogssql);
								$myusedtaskyblog=0;
								if(!empty($mytasklogs)){
									foreach($mytasklogs as $tlog)
									{
										$strtlog=explode(';',$taskidxfybinfos[$tlog['taskid']]);
										$yblogstr=explode('|',$strtlog[1]);
										$strtlog=explode(':',$strtlog[0]);
										$strtlog=explode('|',$strtlog[1]);
										if(intval($strtlog[1])>$xfyb1[0])//已经完成任务的结束时间大于当前任务的开始时间
										{
											$myusedtaskyblog+=intval($yblogstr[0]);
										}
									}
								}								
								$sum_yb-=$myusedtaskyblog;
								
								if($xfyb2[1]==0){
									if($sum_yb<=$xfyb2[0]){
										die("您的元宝消费在{$xfyb2[0]}以上才可领取此任务！");
									}
								}elseif($xfyb2[0]>0 && $xfyb2[1]>=0){
									if($sum_yb<=$xfyb2[0] || $sum_yb>=$xfyb2[1] ){
									/*echo $sum_yb.'<br />';
									print_r($xfyb2);exit;*/
										die("您的元宝消费量不在领取此任务的范围内！");
									}
								}else{
									die("领取任务出错！");
								}
							}else{
								die("领取任务出错！");
							}
						}
						break;
					
					case "xfsj":// task表中limitlv字段中添加xfsj条件格式xfsj：xxxxxxxx|xxxxxxxx代表某年某月某日到某年某月某日需要消费才能接受任务
						$jc=0;
						$xfsj=explode("|",$limitarrs[1]);
						$sql="select * from yblog where nickname='{$user['name']}'";
						$t=$_pm['mysql'] -> getRecords($sql);
						$check = $_pm['mysql'] -> getOneRecord("select time from tasklog where uid = {$_SESSION['id']} and taskid = 88888 order by id desc limit 1");
						$count = count($t) - 1;
						if(is_array($check) && $t[$count]['id'] <= $check['time']){
							die('这段时间您没有新的消费记录，不能接受此任务！');
						}
						
						if(!is_array($t))
						{
							die("你未进行消费，无法领取任务!");
						}else{
							foreach($t as $k=>$v){
							 //如果检测到有消费则跳出，否则直到检测完所有数据
							
								if(date(Ymd,$v['buytime'])>=$xfsj[0] && date(Ymd,$v['buytime'])<=$xfsj[1]){
									$jc=1;
									break;
								}
							}
						}
						if($jc==0){
							die("你未进行消费，无法领取任务!{$tt} |{$xfsj[0]} | {$xfsj[1]}");
						}
						break;	
						
					case "cishu":
						//cishu:X:Y 次数限制判断：如果该任务在Y天已经完成了X次，则无法完成任务
						$time = time() - $limitarrs[2] * 24 * 3600;
						$sql = "SELECT count(*) sl FROM tasklog WHERE uid = {$_SESSION['id']} and taskid = {$taskid} and time > ".strtotime(date('Ymd',time()));
						$arr = $_pm['mysql'] -> getOneRecord($sql);
						if(is_array($arr))
						{
							if($arr['sl'] >= $limitarrs[1])
							{
								die("在{$limitarrs[2]}天内您只能完成$limitarrs[1]次任务！");
							}
						}
						break;
						
					case "cz"://判断主战宠物的成长值是否够格接受此任务
						$lvarr = explode("|",$limitarrs[1]);
						$sql = "SELECT czl FROM userbb WHERE id=".$user['mbid'];
						$petsmain=$_pm['mysql'] -> getOneRecord($sql);
					
						if($lvarr[1]==0){
							if($lvarr[0]>$petsmain['czl']){
								die("该宠物成长值为".$petsmain['czl']."，无法领取该任务!");
							}
						}
						if($lvarr[1]>0){
							if(!($lvarr[0]<=$petsmain['czl'] && $lvarr[1]>=$petsmain['czl'])){
								die("该宠物成长值不在此任务范围内，无法领取任务！");
							}
						}
						break;	
					case "comself"://comself:X 如果玩家身主战宠物ID为X，则可以接受任务。
						$abcarr = explode("|",$limitarrs[1]);
						$bbarr = "";
						foreach($petsAll as $pv)
						{
							if($pv['id'] == $user['mbid'])
							{
								$bname = $pv['name'];
								$comselfbid = $pv['id'];
							}
						}
						$bnamearr = array();
						foreach($abcarr as $av)
						{
							foreach($bbs as $bbav)
							{
								if($bbav['id'] == $av)
								{
									$bnamearr[] = $bbav['name'];
								}
							}
						}
						if(!in_array($bname,$bnamearr))
						{
							die("您的当前主宠不能接受此任务！");
						}
						break;
					case "jifen"://jifen:X 只有在积分达到X时才可以接受任务
						if($user['score'] < $limitarrs[1])
						{
							die("您的当前积分不够接此任务！");
						}
						break;
					case "vip"://jifen:X 只有在积分达到X时才可以接受任务
						if($user['vip'] < $limitarrs[1])
						{
							die("您的vip积分不够接此任务！");
						}
						break;
					case 'merge':
						$merge = $_pm['mysql'] -> getOneRecord("SELECT merge FROM player_ext WHERE uid = {$_SESSION['id']}");
						if($merge['merge'] < 1){
							die('您目前未婚，不能接受此任务！');
						}
				}
			}
		}
	}
	$arr = "";
	if(empty($taskinfo['cid']))
	{
		$sql = "SELECT taskid FROM tasklog WHERE uid = {$_SESSION['id']} and taskid = {$taskid}";
		$arr = $_pm['mysql'] -> getOneRecord($sql);
	}
	if(is_array($arr))
	{
		die("该任务只能接受一次~！");
	}
	//只能完成一次的任务在这里结束
	
	//修改用户数据;
	/*if(!empty($user['task']))
	{
		if($user['task'] == $taskid)
		{
			die("您已经接受此任务！");
		}
		
	}*/
	$usertaskarr = $_pm['mysql'] -> getRecords("SELECT taskid FROM task_accept WHERE uid = {$_SESSION['id']}");
	if(is_array($usertaskarr)){
		foreach($usertaskarr as $v){
			$accept[] = $v['taskid'];
		}
		if(in_array($taskid,$accept)){
			die("您已经接受此任务！");
		}
		if(count($usertaskarr) >= 15){
			die("您已经接受了15个任务，超过了最大限制！");
		}
	}
	
	$arr = $_pm['mysql'] -> getOneRecord($sql);
	$arr1=explode(',',$arr['okneed']);
	for($i=0;$i<count($arr1);$i++){
		$arr2[$i]=explode(':',$arr1[$i]);
		if($arr2[$i][0]=='zx'){
			$sql = "SELECT onlinetime FROM player_ext WHERE uid = {$_SESSION['id']}";
			$arr0 = $_pm['mysql'] -> getOneRecord($sql);
				if($arr0['onlinetime']<($arr2[$i][1]*3600)){
					die("此任务需要在线".$arr2[$i][1]."小时才可接受，您目前在线时间还不够，无法接受此任务！");
				}
		}
	}
	
	
	//$sql = "UPDATE player SET task = {$taskid},tasklog='' WHERE id = {$_SESSION['id']}";
	$sql = "INSERT INTO task_accept (uid,taskid,time) VALUES ({$_SESSION['id']},$taskid,".time().")";
	$_pm['mysql'] -> query($sql);
	echo "恭喜您，成功接受此任务！";
	//记录不能切换主宠的任务:
	if(strpos($taskinfo['okneed'],",no:1"))
	{
		//$_pm['mysql'] -> query("INSERT INTO tasklog (taskid,uid,xulie,time,fromnpc) VALUES (9999,{$_SESSION['id']},0,0,0)");
		$_pm['mysql'] -> query("UPDATE task_accept SET comself = $comselfbid WHERE uid = {$_SESSION['id']} AND taskid = $taskid");
	}
}

else if($type == 'off')
{
	$taskid = intval($_REQUEST['taskid']);
	if(empty($taskid))
	{
		die("数据错误！");
	}
	/*if($user['task'] != $taskid)
	{
		die("您当前接的不是此任务！");
	}*/
	$usertaskarr = $_pm['mysql'] -> getRecords("SELECT taskid FROM task_accept WHERE uid = {$_SESSION['id']}");
	if(is_array($usertaskarr)){
		foreach($usertaskarr as $v){
			$accept[] = $v['taskid'];
		}
		if(!in_array($taskid,$accept)){
			die("您没有接受此任务！");
		}
	}
	$taskinfo = $memtask[$taskid];
	
	//$sql = "UPDATE player SET task = '',tasklog = '' WHERE id = {$_SESSION['id']}";
	$sql = "DELETE FROM task_accept WHERE uid = {$_SESSION['id']} AND taskid = $taskid";
	$_pm['mysql'] -> query($sql);
	die("放弃成功！");
}
else if($type == "complate")
{
	require_once('../sec/dblock_fun.php');
	$a = getLock($_SESSION['id']);
	if(!is_array($a)){
		realseLock();
		die('服务器繁忙，请稍候操作！');
	}
	$taskid = intval($_REQUEST['taskid']);
	$usertaskarr = $_pm['mysql'] -> getRecords("SELECT taskid,state FROM task_accept WHERE uid = {$_SESSION['id']}");
	if(is_array($usertaskarr)){
		foreach($usertaskarr as $v){
			$accept[] = $v['taskid'];
			if($v['taskid'] == $taskid){
				$user['tasklog'] = $v['state'];
				$user['task'] = $v['taskid'];
				$flag = 1;
			}
		}
		if($flag != 1){
			realseLock();
			die("您没有接受此任务！");
		}
	}
	
	
	$taskinfo = $memtask[$taskid];
	//条件判断								  
	if(!empty($taskinfo['limitlv']))
	{
		$limitarr = explode(",",$taskinfo['limitlv']);

		if(is_array($limitarr))
		{
			foreach($limitarr as $v)
			{
				$limitarrs = explode(":",$v);
				switch($limitarrs[0])
				{
					case "lv":
						foreach($petsAll as $bb)
						{
							if($bb['id'] == $user['mbid'])
							{
								$blv = $bb['level'];
							}
						}
						if(empty($blv))
						{
							realseLock();
							die("请先到牧场设置主战！");
						}
						$lvarr = explode("|",$limitarrs[1]);
						if(empty($lvarr[1]))
						{
							if($blv < $lvarr[0])
							{
								realseLock();
								die("您的等级不够完成此任务！");
							}
						}
						else
						{
							if($blv < $lvarr[0] || $blv > $lvarr[1])
							{
								realseLock();
								die("您的等级不在可接此任务范围之内！");
							}
						}
						break;
					case "wx":
						$_mbwx='';		
						foreach($petsAll as $bb)
						{
							if($bb['id'] == $user['mbid'])
							{
								$_mbwx = $bb['wx'];
							}
						}
						if(empty($_mbwx))
						{
							realseLock();
							die("请先到牧场设置主战！");
						}
						$wxs=explode('|',$limitarrs[1]);
						if(!in_array($_mbwx,$wxs))
						{
							realseLock();
							die("主战宠物五行与任务不符合任务要求！");
						}
						break;
					case "cishu":
						//cishu:X:Y 次数限制判断：如果该任务在Y天已经完成了X次，则无法完成任务
						$time = time() - $limitarrs[2] * 24 * 3600;
						$sql = "SELECT taskid FROM tasklog WHERE uid = {$_SESSION['id']} and taskid = {$taskid} and tasktime > {$time}";
						$arr = $_pm['mysql'] -> getRecords($sql);
						if(is_array($arr))
						{
							if(count($arr) >= $limitarrs[1])
							{
								realseLock();
								die("在{$limitarrs[2]}天内您只能完成$limitarrs[1]次任务！");
							}
						}
						break;
					case "comself"://comself:X 如果玩家身主战宠物ID为X，则可以接受任务。
						$abcarr = explode("|",$limitarrs[1]);
						$bbarr = "";
						foreach($petsAll as $pv)
						{
							if($pv['id'] == $user['mbid'])
							{
								$bname = $pv['name'];
							}
						}
						$bnamearr = array();
						foreach($abcarr as $av)
						{
							foreach($bbs as $bbav)
							{
								if($bbav['id'] == $av)
								{
									$bnamearr[] = $bbav['name'];
								}
							}
						}
						if(!in_array($bname,$bnamearr))
						{
							realseLock();
							die("您的当前主宠不能完成此任务！");
						}
						break;
					case "jifen"://jifen:X 只有在积分达到X时才可以接受任务
						if($user['score'] < $limitarrs[1])
						{
							realseLock();
							die("您的当前积分不够接此任务！");
						}
						break;
					case "vip"://jifen:X 只有在积分达到X时才可以接受任务
						if($user['vip'] < $limitarrs[1])
						{
							realseLock();
							die("您的vip积分不够接此任务！");
						}
						break;
				}
			}
		}
	}
	$arr = "";						
	if(empty($taskinfo['cid']))
	{
		$sql = "SELECT taskid FROM tasklog WHERE uid = {$_SESSION['id']} and taskid = {$taskid}";
		$arr = $_pm['mysql'] -> getOneRecord($sql);
	}
	if(is_array($arr))
	{
		realseLock();
		die("该任务只能接受一次~！");
	}
	
	if (isset($_REQUEST['n']) && $n>0 && $n<10000)	// 完成任务.
	{
		$ret = $_task['dlg'][$n];	// 当前任务NPC编号
		$tid = $user['task'];	// 任务当前ID。
	
		/*$taskinfo = $_pm['mem']->dataGet(array('k'	=>	MEM_TASK_KEY,
									  'v'	=> "if(\$rs['id']=={$tid} && {$tid}=={$user['task']}) \$ret=\$rs;"
							));*/
		$taskinfo = $memtask[$tid];
	//echo $taskinfo['oknpc'].'<br />'.$n;exit;
		if (is_array($taskinfo))	//获取玩家当前的任务及详细信息
		{
			if ($taskid != $user['task']) // start task.
			{
				$ret = $tsk->formatTask($taskinfo['frommsg']);
				echo $ret;
			}
			else if ($taskinfo['oknpc'] == $n)
			{
				$ret = $tsk->completeTask($user, $taskinfo);
				echo $ret;
			}
		}
	}
	else if (isset($_REQUEST['s']) && $s>0 && $s<10000)	// 触发任务。保存任务日志。
	{
		if (intval($_REQUEST['taskid'])>0) 
			$user['task']=intval($_REQUEST['taskid']);
		$tsk->startTask($user, $s);
	}
}
realseLock();
$_pm['mem']->memClose();
?>