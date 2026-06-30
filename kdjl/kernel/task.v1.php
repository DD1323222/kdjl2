<?php
/**
@Usage: Task class
@Copyright:www.webgame.com.cn
@Version:1.2
@Write: 2008.08.06
@Memo:
*/
class task
{
	private $m_db;	//	Db Handle
	private $xfsj; //活动日期字段
	private $m_m;	//	Memory Handle
	private $re_str;//得到字符串
	function __construct(){
		global $_pm;
		if (!is_array($_pm) || 
			!is_object($_pm['mysql']) || 
			!is_object($_pm['mem'])
			)
		return false;

		$this-> m_db = &$_pm['mysql'];
		$this-> m_m	 = &$_pm['mem'];
	}
	
	/**
	@Usage: 接受任务
	@Param: (array) user info. $s => npc code.
	@Return:String
	*/
	function startTask($user, $s)
	{
		global $_task;
		/*
		if ($user['task'] == 0) // 启动新手任务.
		{
			$user['task'] = 1;	// update player task.
		}*/

		if (isset($_task['oknpc'][$s])) // 存储NPC到玩家的访问日志。
		{
			$user['tasklog'] = ',see:'.$s;
		}
		
		$this->m_db->query("UPDATE player
							   SET task='{$user['task']}',
								   tasklog='{$user['tasklog']}'
							 WHERE id={$_SESSION['id']}
						  ");
		return '恩，赶快去吧!';
	}

	/**
	@Usage: 完成任务处理
	@Param: $user=>array, $taskinfo:=>array.
	@Return: String
	*/
	function completeTask($user, $taskinfo)
	{
		global $_pm;
		$bb = unserialize($_pm['mem']->get(MEM_BB_KEY));
		
		/**检查用户的包裹空间是否足够*/
		$bag = $_pm['user']->getUserBagById($_SESSION['id']);
		
		$nowtime = date("YmdHis");
		$fromnpc = explode("|",$taskinfo['fromnpc']);
		$timearr = unserialize($_pm['mem']->get(MEM_TIME_KEY));
		$taskarr = unserialize($_pm['mem']->get(MEM_TASK_KEY));
		foreach($timearr as $tv)
		{
			if($tv['titles'] == "task")
			{
				$taskcheckarr[] = $tv;
			}
		}
		$checknum = 10;
		if(!empty($taskinfo['flags']))
		{
			foreach($timearr as $fv)
			{
				if($fv['days'] == $taskinfo['flags'] && $nowtime >= $fv['starttime'] && $nowtime <= $fv['endtime'])
				{
					$checknum = 11;
				}
			}
		}
		
		if($checknum != 10)
		{
			die("对不起，该任务已经结束！");
		}
		
		
		$l=0;
		$limit = explode(",",$taskinfo['limitlv']);
		if (is_array($bag))
		{
			foreach ($bag as $x => $y)
			{
				if ($y['sums']>0 && $y['zbing']==0) $l++;
			}
		}
		if ($l+2 >= $user['maxbag']) return "您的背包空间不足，请预留至少3个背包格子，任务不能继续！";	
		

		$user['tasklog'] .= ',see:'.$_REQUEST['n'];		//记录任务完成度

		$need = split(',', $taskinfo['okneed']);// Check task whether complete.
		if (is_array($need))
		{
			$i = 0;
			foreach($need as $x => $y)
			{
				if (strpos($user['tasklog'], $y) === false) 
				{
					$arr = explode(':', $y);
					if ($arr[0] == "giveitem")
					{
						if ($this->existsProps($arr) === true) $i++; // arr
					}elseif($arr[0] == "zx"){
						$i++;
					}
					else if ($arr[0] == "givejifen")
					{
						if ($this->existsJifen($arr) !== true)
						{
							$str = '积分不够！';
							return $str;
							break;
						}
					}
					else if ($arr[0] == "giveww")
					{
						if ($this->existsWw($arr) !== true)
						{
							$str = '威望不够！';
							return $str;
							break;
						}
					}
					else if ($arr[0] == "givevip")
					{
						if ($this->existsVip($arr) !== true)
						{
							$str = '当月VIP反馈积分不够！';
							return $str;
							break;
						}
					}else if ($arr[0] == "giveml")
					{
						if ($this->existsMl($arr) !== true)
						{
							$str = '您的魅力值不足，无法领取该奖励！';
							return $str;
							break;
						}
					}
					else if ($arr[0] == "givemoney")
					{
						if ($this->existsMoney($arr) !== true)
						{
							$str = '金币不够！';
							return $str;
							break;
						}
					}
					else if($arr[0] == "givedianjuan")
					{
						if($this->existsDianjuan($arr) !== true)
						{
							$str = '点卷不够！';
							return $str;
							break;
						}
					}
					else if ($arr[0] == "monself" || $arr[0] == "lv" || $arr[0] == "wx")
					{
						
						if(empty($user['mbid']))
						{
							$str = "请先设置主战宠物！";
							return $str;
							break;
						}
						$arrm = explode("|",$arr[1]);
						$petsAll = $_pm['user']->getUserPetById($_SESSION['id']);
						foreach($petsAll as $pet)
						{
							if($pet['id'] == $user['mbid'])
							{
								$bname = $pet['name'];
								$blevel = $pet['level'];
								$bwx = $pet['wx'];
								break;
							}
						}
						if($arr[0] == 'wx')
						{
							if(!in_array($bwx,explode('|',$arr[1])))
							{//五行不符合
								$str = "主战宠物五行与任务不符合任务要求！";
								return $str;
							}else{
								$i++;
							}
						}
						if($arr[0] == "monself")
						{
							$bbs = unserialize($_pm['mem']->get(MEM_BB_KEY));
							foreach($arrm as $v)
							{
								foreach($bbs as $bv)
								{
									if($v == $bv['id'])
									{
										$bnamearr[] = $bv['name'];
									}
								}
							}
							if(!in_array($bname,$bnamearr))
							{
								die("您不能用该主战宠物交此任务！");
							}
						}
						
						if($arr[0] == 'lv')
						{
							if(empty($arrm[1]))
							{
								if($blevel < $arrm[0])
								{
									die("您的等级不够完成此任务！");
								}
							}
							else
							{
								if($blevel < $arrm[0] || $blevel > $arrm[1])
								{
									die("您的等级不在完成此任务范围之类！");
								}
							}
						}
						
					}
				}
				else 
				{
					$i++;
				}		
			}			
		}
		$needvip = 0;
		foreach($need as $n)
		{

			$arr = explode(":",$n);
			if($arr[0] != 'givejifen' && $arr[0] != 'giveww' && $arr[0] != 'lv' && $arr[0] != 'givemoney' && $arr[0] != 'monself' && $arr[0] != 'no' && $arr[0] != 'givevip' && $arr[0] != 'givedianjuan' && $arr[0] != 'paihang' && $arr[0] != 'giveml')
			{
				$needs[] = $n;
			}
			else
			{
				if($arr[0] == "givejifen")
				{
					$user['score'] -= $arr[1];
				}
				else if($arr[0] == 'giveww')
				{
					$user['prestige'] -= $arr[1];
				}
				else if($arr[0] == "givemoney")
				{
					$user['money'] -= $arr[1];
				}
				else if($arr[0] == "givevip")
				{
					$user['vip'] -= $arr[1];
					$needvip = $arr[1];
				}else if($arr[0] == "giveml")
				{
					$ml = $arr[1];
				}
				else if($arr[0] == 'givedianjuan')
				{
					$user['active_score'] -= $arr[1];
				}
			}
		}//echo $i.'aaaaaaaa'.count($needs);print_r($needs);
		if ($i != count($needs)) 
		{
			$str = '让你做的事还没做完，是不能获得奖励的噢！'; 
			return $str;
		}
		else
		{
			if(is_array($limit))
			{
				foreach($limit as $v)
				{
					$limitarr = explode(":",$v);
					if($limitarr[0] == "cishu")
					{
						$today = strtotime(date('Ymd',time()));
						$sql = "SELECT count(*) sl FROM tasklog WHERE uid = {$_SESSION['id']} and taskid = {$taskid} and time > {$today}";
						$arr = $_pm['mysql'] -> getOneRecord($sql);
						if(is_array($arr))
						{
							/*$time = 24 * 3600 * $limitarr[2];
							$ntime = time();*/
							if($arr['sl'] >= $limitarr[1] )
							{
								die("该任务{$limitarr[2]}天只能完成{$limitarr[1]}次！");
							}
							else
							{
								$time = time();
								$_pm['mysql'] -> query("INSERT INTO tasklog (taskid,uid,xulie,time,fromnpc) VALUES ({$taskinfo['id']},{$_SESSION['id']},{$taskinfo['xulie']},{$time},{$fromnpc[0]})");
							}
						}
						else
						{
							$time = time();
							$_pm['mysql'] -> query("INSERT INTO tasklog (taskid,uid,xulie,time,fromnpc) VALUES ({$taskinfo['id']},{$_SESSION['id']},{$taskinfo['xulie']},{$time},{$fromnpc[0]})");
						}
					}
					elseif($limitarr[0] == "xfsj"){
						$this->xfsj=$limitarr[1];
					}
					else if($limitarr[0] == "paihang")
					{
						if($user['paihang'] != $limitarr[1])
						{
							die("您不能完成此任务！");
						}
					}else if($limitarr[0] == "timelimit"){
						$t = $_pm['mysql'] -> getOneRecord('SELECT time FROM task_accept WHERE uid = '.$_SESSION['id'].' AND taskid = '.$taskinfo['id']);
						$nowtime = time();
						$c = ($nowtime - $t['time']) - $limitarr[1] * 3600;
						if($c > 0){
							die('超过时间限时，该任务必须在'.$limitarr[1].'小时内完成！');
						}
					}
				}
			}
			$this->clearTaskProps($need);	// array.
		}
		

		// Update task.
		$gets = split(',',$taskinfo['result']);
		$taskgets = '';
		
		$user['task'] = $taskinfo['cid'];
		$user['tasklog']= 0;
		//防外挂
		$wgarr = explode(":",$taskinfo['cid']);
		if($wgarr[0] == "rwl")
		{
			$sql = "select taskid from tasklog WHERE uid = {$_SESSION['id']} and taskid = {$taskinfo['id']}";
			$arr = $_pm['mysql'] -> getOneRecord($sql);
			if(is_array($arr) && strpos($taskinfo['limitlv'],"cishu") === false)
			{
				$rwlcheck = explode("|",$wgarr[1]);
				if($rwlcheck[0] != $rwlcheck[1])
				{
					//$_pm['mysql'] -> query("UPDATE player SET secid = 3 WHERE id = {$_SESSION['id']}");
					$_SESSION['id'] = "";
					die('操作有误（2）');
				}
			}
			else
			{
				$sql = "select taskid from tasklog WHERE uid = {$_SESSION['id']} and fromnpc = {$fromnpc[0]} and xulie = {$taskinfo['xulie']}";
				$arrs = $_pm['mysql'] -> getOneRecord($sql);
				
				
				$time = time();
				if(is_array($arrs)){
					$t1 = $taskarr[$arrs['taskid']];
					$t1_cid = explode(':',$t1['cid']);
					$tid_arr = explode('|',$t1_cid[1]);
					if($t1_cid['0'] != 'rwl' || $taskinfo['id'] != $tid_arr[1]){
						die('您不能完成这个任务！');
					}
					$_pm['mysql'] -> query("UPDATE tasklog SET taskid = {$taskinfo['id']} WHERE uid = {$_SESSION['id']} and fromnpc = {$fromnpc[0]} and xulie = {$taskinfo['xulie']}");
				}
				else
				{
					$_pm['mysql'] -> query("INSERT INTO tasklog (taskid,uid,xulie,time,fromnpc) VALUES({$taskinfo['id']},{$_SESSION['id']},{$taskinfo['xulie']},{$time},{$fromnpc[0]})");
				}
				
				
				/*if(is_array($arrs))
				{
					##################################
					foreach($taskarr as $k => $v)
					{
						$fromnpc1 = explode("|",$v['fromnpc']);
						if($v['xulie'] == $taskinfo['xulie'] && $fromnpc1[0] == $fromnpc[0] && $v['id'] == $arrs['taskid'])
 						{
 							$a = explode(":",$v['cid']);
 							$b = explode("|",$a[1]);
 							$cidarrcheck[] = $b[0];
 							$cidarrcheck[] = $b[1];
							if($taskinfo['id'] != $b[1]){
							//echo $taskinfo['id'].'<br />'.$b[1].'<br />';
							//echo $v['cid'].'aaa'.$v['id'].'<br />';
							die('非法操作！');
							}
 						}
					}
					$num = count($cidarrcheck) - 1;
					if($cidarrcheck[$num] == 0)
					{
						$n = max($cidarrcheck);
						if($taskinfo['id'] <= $arrs['taskid'])
						{
							die("该任务只能做一次！");
						}
						if($arrs['taskid'] == $n)
						{
							die("该任务链只能做一次！");
						}
					}
					

					###################################
					$_pm['mysql'] -> query("UPDATE tasklog SET taskid = {$taskinfo['id']} WHERE uid = {$_SESSION['id']} and fromnpc = {$fromnpc[0]} and xulie = {$taskinfo['xulie']}");
				}
				else
				{
					$_pm['mysql'] -> query("INSERT INTO tasklog (taskid,uid,xulie,time,fromnpc) VALUES({$taskinfo['id']},{$_SESSION['id']},{$taskinfo['xulie']},{$time},{$fromnpc[0]})");
				}*/
			}
		}
		if(empty($taskinfo['cid']))//只能完成一次的任务
		{
			$arr = "";
			$arr = $_pm['mysql'] -> getOneRecord("SELECT taskid FROM tasklog WHERE uid = {$_SESSION['id']} and taskid = {$taskinfo['id']}");
			if(is_array($arr))
			{
				//$_pm['mysql'] -> query("UPDATE player SET secid = 3 WHERE id = {$_SESSION['id']}");
				$_SESSION['id'] = "";
				die('非法操作');
			}
			else
			{
				$_pm['mysql'] -> query("INSERT INTO tasklog (taskid,uid) VALUES ({$taskinfo['id']},{$_SESSION['id']})");
			}
		}
		
		/*$_pm['mysql']->query("UPDATE player
								 SET
									 task='',
									 tasklog=''
							   WHERE id={$_SESSION['id']} AND task = {$taskinfo['id']}
				  ");
		$result = mysql_affected_rows($_pm['mysql'] -> getConn());
		if($result != 1){
			unLockItem($id);
			die("操作有误！");
		}*/
		$_pm['mysql'] -> query("DELETE FROM task_accept WHERE uid = {$_SESSION['id']} AND taskid = {$taskinfo['id']}");
		$result = mysql_affected_rows($_pm['mysql'] -> getConn());
		if($result != 1){
			unLockItem($id);
			die("操作有误（1）！");
		}
		
		if(isset($ml) && $ml > 0){
			$_pm['mysql'] -> query("UPDATE player_ext SET ml = ml-$ml WHERE uid = {$_SESSION['id']} AND ml >= $ml");
			if(mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){
				die('您的魅力值不足，无法领取该奖励');
			}
		}
		if($needvip > 0){
			$_pm['mysql'] -> query("UPDATE player SET vip = vip-$needvip WHERE id = {$_SESSION['id']} AND vip >= $needvip");
			//echo "UPDATE player SET vip = vip-$needvip WHERE id = {$_SESSION['id']} AND vip >= $needvip";
			if(mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){
				die('您的vip积分不足，无法领取该奖励！');
			}
		}
		
		if (is_array($gets))
		{
			if (isset($gets[0])) 
			{
				foreach($gets as $k => $v) // money,exp.
				{
					$tt = split(':', $v);
					switch ($tt[0])
					{
						case "fksj":  
							if($this->getSJ($tt[1])){
							 	$taskgets.= $this->re_str."<br/>";
							    $this->saveGword("消费了大量元宝，自然女神奖励其慷慨赠送他大量水晶币<br/>"); 
								unset($this->re_str);
								unset($this->xfsj); 
							}
							break;
							
						case "exp":      $taskgets .= $this->saveExp($tt)."<br/>";break;
						case "props":    
							$taskgets .= $this->saveProps($tt)."<br/>"; 
							$log.=print_r($tt,1).'==>'.$taskgets.mysql_error();
							break;
						case "bprops":    
							$taskgets .= $this->saveProps($tt,true)."<br/>"; 
							$log.=print_r($tt,1).'==>'.$taskgets.mysql_error();
						break;
						//case "money":    $user['money']+=$tt[1];$taskgets .= ' 金币'.$tt[1]; break;
						case "money":    
							$moneystr = $this->saveMoney($tt);
							$moneyarr = explode("：",$moneystr);
							$user['money'] += $moneyarr[1];
							$taskgets .= $moneystr."<br/>";
							break;
						    //$taskgets .= $this->saveMoney($tt); break;
						case "itemrand": 
							$taskgets .= $this->saveRand($v)."<br/>";
							$log.=print_r($tt,1).'==>'.$taskgets.mysql_error();
							break;
						case "gonggao":  $this->saveGword($tt[1])."<br/>"; break;
						case "paihang":  $user['paihang'] = 0;break;
						case "lvprops" : 
							$taskgets .= $this->levelProps($tt)."<br/>";
							$log.=print_r($tt,1).'==>'.$taskgets.mysql_error();
							break;
						//case "givejifen":  $user['score']+=$tt[1];$taskgets .= ' 积分'.$tt[1]; break;
						// In here add more patter...
					}
				} // end foreach
			}
		}
		$time = time();
		$log .= '==>任务id：'.$taskinfo['id'];//任务日志
		$_pm['mysql'] -> query("INSERT INTO gamelog(ptime,seller,buyer,pnote,vary) VALUES ({$time},{$_SESSION['id']},{$taskinfo['id']},'$log',161)");
		//vip 记录 vary 为6的是vip记录，seller是玩家ID，buyer是任务号,ptime 是时间，pnote是任务标题;
		if($taskinfo['id'] >= 179 && $taskinfo['id'] <= 190)
		{
			
			$_pm['mysql'] -> query("INSERT INTO gamelog(ptime,seller,buyer,pnote,vary) VALUES ({$time},{$_SESSION['id']},{$taskinfo['id']},'{$taskinfo['title']}',6)");
		}
		//vip记录到此结束
		$_pm['mysql']->query("UPDATE player
								 SET money={$user['money']},
								 	score={$user['score']},
									prestige={$user['prestige']},
									active_score={$user['active_score']},
									paihang = {$user['paihang']},
									 task='',
									 tasklog=''
							   WHERE id={$_SESSION['id']}
				  ");
		//return $taskinfo['title'] . ' 任务完成！您获得了 ' . $taskgets;
		return $taskinfo['title'] . ' 任务完成！您获得了相应任务奖励！';
	}
	
	
	
	
	function levelProps($arr){
		global $_pm;
		$props = unserialize($_pm['mem']->get('db_propsid'));
		$u = $_pm['mysql'] -> getOneRecord('SELECT level FROM userbb,player WHERE player.id = '.$_SESSION['id'].' AND player.mbid = userbb.id');
		$ar = explode('|',$arr[3]);
		if($u['level'] < $ar[0] || ($u['level'] > $ar[1] && $ar[1] != 0)){
			return false;
		}
		$this->saveGetPropsMore($arr[1],$arr[2]);
			return '获得道具 '.$props[$arr[1]]['name'].'&nbsp;'.$arr[2].' 个<br />';
	}
	
	
	/**
	@Param: patter of arr=>giveitem,ID,num
	@Return: true of false
	@Param ex: giveitem:843:1,giveitem:844:1,giveitem:845:1,giveitem:846:1
	*/
	function existsProps($arr)
	{
		$arr[1] = str_replace('|',',',$arr[1]);
		$rs = $this->m_db->getOneRecord("SELECT sum(sums) as cnt 
										   FROM userbag 
										  WHERE pid in({$arr[1]}) and uid={$_SESSION['id']} and zbing!=1
									   ");

		if (is_array($rs) && $rs['cnt']>=$arr[2])
		{
			return true;
		}
		else return false;
	}
	
	//消费元宝，反馈水晶
	function getSJ($str){
		$get_sum=0;
		if(!empty($str)){
			$rs = $this->m_db->getOneRecord("SELECT name
										   FROM player 
										  WHERE id={$_SESSION['id']}
									   ");
			$check = $this->m_db->getOneRecord("select time from tasklog where uid = {$_SESSION['id']} and taskid = 88888 order by id desc limit 1");
			if(is_array($check)){
				$getYb = $this->m_db->getRecords("select * from yblog where nickname='{$rs['name']}' AND id > {$check['time']} order by id desc");
			}else{
				$getYb = $this->m_db->getRecords("select * from yblog where nickname='{$rs['name']}' order by id desc");
			}
			
			//加入日志
			$count = count($getYb) - 1;
			$this->m_db->query("INSERT INTO tasklog (uid,taskid,time) VALUES({$_SESSION['id']},88888,{$getYb[0]['id']})");
			$t=explode("|",$this->xfsj);
			if(is_array($getYb) && is_array($t)){
				foreach($getYb as $k=>$v){
					if(date(Ymd,$v['buytime'])>=$t[0] && date(Ymd,$v['buytime'])<=$t[1]){
						$get_sum+=$v['yb'];
					}
				}
				if($get_sum>0){
					$f=substr($str,0,-1);
					$get_sum=intval($f*$get_sum/100);
					$this->m_db->query("UPDATE player_ext 
							                       SET sj=sj+ $get_sum
												 WHERE uid={$_SESSION['id']}
											  ");
					$this->re_str=$get_sum."个水晶币"	;
					return true;
				}else{
					return false;
				}	
			}else{
				return false;
			}
		}else{
			return false;
		}
		
	}
	
	
	
	
	/**
	@Param: patter of arr=>giveitem,ID,num
	@Return: true of false
	@Param ex: giveitem:843:1,giveitem:844:1,giveitem:845:1,giveitem:846:1
	*/
	function existsJifen($arr)
	{
		//$arr[1] = str_replace('|',',',$arr[1]);
		/*$rs = $this->m_db->getOneRecord("SELECT score 
										   FROM player 
										  WHERE id={$_SESSION['id']}
									   ");*/
		global $user;
		if (!(empty($user['score'])) && $user['score']>=$arr[1])
		{
			return true;
		}
		else return false;
	}
	
	//点卷
	function existsDianjuan($arr)
	{
		//$arr[1] = str_replace('|',',',$arr[1]);
		$rs = $this->m_db->getOneRecord("SELECT active_score 
										   FROM player 
										  WHERE id={$_SESSION['id']}
									   ");
		if (is_array($rs) && $rs['active_score']>=$arr[1])
		{
			return true;
		}
		else return false;
	}
	
	//VIP
	function existsVip($arr)
	{
		//$arr[1] = str_replace('|',',',$arr[1]);
		$rs = $this->m_db->getOneRecord("SELECT vip 
										   FROM player 
										  WHERE id={$_SESSION['id']}
									   ");
		if (is_array($rs) && $rs['vip']>=$arr[1])
		{
			return true;
		}
		else return false;
	}
	//魅力判断
	function existsMl($arr)
	{
		//$arr[1] = str_replace('|',',',$arr[1]);
		$rs = $this->m_db->getOneRecord("SELECT ml 
										   FROM player_ext 
										  WHERE uid={$_SESSION['id']}
									   ");
		if (is_array($rs) && $rs['ml']>=$arr[1])
		{
			return true;
		}
		else return false;
	}
	
	//威望判断
	function existsWw($arr)
	{
		global $user;
		//$arr[1] = str_replace('|',',',$arr[1]);
		/*$rs = $this->m_db->getOneRecord("SELECT score 
										   FROM player 
										  WHERE id={$_SESSION['id']}
									   ");*/
		if (!(empty($user['prestige'])) && $user['prestige']>=$arr[1])
		{
			return true;
		}
		else return false;
	}
	
	//金币判断
	
	//威望判断
	function existsMoney($arr)
	{
		global $user;
		//$arr[1] = str_replace('|',',',$arr[1]);
		/*$rs = $this->m_db->getOneRecord("SELECT score 
										   FROM player 
										  WHERE id={$_SESSION['id']}
									   ");*/
		if (!(empty($user['money'])) && $user['money']>=$arr[1])
		{
			return true;
		}
		else return false;
	}

	/**
	* @Param: need array
	*/
	function clearTaskProps1($need)
	{
		//echo $need;exit;
		$delcount = 0;
		foreach($need as $x => $y)
		{
			$arr = explode(':', $y);

			if ($arr[0] == "giveitem")
			{
				$arr[1] = str_replace('|',',',$arr[1]);
				$ret = $this->m_db->getOneRecord("SELECT id,sums
		 									     FROM userbag 
												WHERE pid in({$arr[1]}) and uid={$_SESSION['id']}
												ORDER by sums desc
											 ");
				if($ret['sums'] < $arr[2]){
					die('对不起，您没有这么多物品！');
				}
				$this->m_db->query("UPDATE userbag 
							                       SET sums=sums - {$arr[2]}
												 WHERE id={$ret['id']} and sums >= {$arr[2]}
											  ");
				// Del props and count num
				/*if (is_array($ret))
				{
					foreach ($ret as $k => $v)
					{
						if ($v['sums']<1) continue;
						if ($delcount<$arr[2]) $del = $arr[2]-$delcount;
						else break;
						if ($v['sums']==$del)
						{
							// del record
							$this->m_db->query("UPDATE userbag 
							                       SET sums=0
												 WHERE id={$v['id']}
											   ");
							break;
						}
						else if ($v['sums']<$del)
						{
							// del record. $v['sums']
							$delcount+=$v['sums'];
							$this->m_db->query("UPDATE userbag 
							                       SET sums=0
												 WHERE id={$v['id']}
											   ");
						}
						else // 减去剩余数值。update.
						{
							$v['sums'] = $v['sums']-$del;
							// update record.
							$this->m_db->query("UPDATE userbag 
							                       SET sums={$v['sums']}
												 WHERE id={$v['id']}
											  ");
							break;	
						}
					}
				} */// end if
			}						
		}// end foreach.		
		return true;
	}
	
	
	function clearTaskProps($need)
	{
		$delcount = 0;
		$this->m_db->query('START TRANSACTION');
		foreach($need as $x => $y)
		{
			$arr = explode(':', $y);

			if ($arr[0] == "giveitem")
			{
				$ar = explode('|',$arr[1]);
				foreach($ar as $av){
					$ret = $this->m_db->getRecords("SELECT id,sums
		 									     FROM userbag 
												WHERE pid = $av and uid={$_SESSION['id']} and zbing = 0 and sums>0
												ORDER by sums desc
											 ");
					if(is_array($ret)){
						foreach($ret as $v){
							$sum += $v['sums'];
						}
					}else{
						$this->m_db->query('ROLLBACK');
						die('对不起，您没有这么多物品！');
					}
					$sums1 = 0;
					foreach($ret as $v){
						$newsum = $arr[2] - $sums1;
						if($v['sums'] < $newsum){
							$this->m_db->query("UPDATE userbag SET sums = 0 WHERE id = {$v['id']} AND zbing = 0");
							if(mysql_affected_rows($this->m_db->getConn()) != 1){
								$this->m_db->query('ROLLBACK');
								die('对不起，您没有这么多物品！');
							}
							$sums1 += $v['sums'];
						}
						else if($v['sums'] >= $newsum)
						{
							$this->m_db->query("UPDATE userbag SET sums = sums - $newsum WHERE id = {$v['id']} AND zbing = 0 and sums >= $newsum");
							if(mysql_affected_rows($this->m_db->getConn()) != 1){
								$this->m_db->query('ROLLBACK');
								die('对不起，您没有这么多物品！');
							}
							$sums1 += $v['sums'];
							break;
						}
					}
					/*if($sum < $arr[2]){
					//echo $ret['sums'].'<br />'.$arr[2].'id'.$ret['id'];exit;
						$this->m_db->query('ROLLBACK');
						die('对不起，您没有这么多物品！');
					}
					$this->m_db->query("UPDATE userbag 
													   SET sums=sums - {$arr[2]}
													 WHERE id={$ret['id']}
												  ");*/
				}
			}						
		}// end foreach.
		$this->m_db->query('COMMIT');		
		return true;
	}
	
	/**
	*@Usage: publish global word of game
	*@Param: $word of String
	*@Return:void
	*/
	function saveGword($word, $epl=0)
	{
		$retstr = '';
		if ($word == '') return false;
		$msg_key = 'chatMsgList';
		$nowMsgList = unserialize($this->m_m->get($msg_key));
		$arr = explode('linend', $nowMsgList);
		if( count($arr)>20 ) // cear old
		{
			$arrt = array_shift($arr);
		}
		if ($epl == 1)
		{
			$newstr = '<font color=red>' . $word . '</font>';
		}
		else
		{
			$newstr = '<font color=red>[系统公告]恭喜玩家 '.$_SESSION['nickname'].' '.$word.'</font>';	
			//$newstr = '<font color=red>[系统公告] '.$word.'</font>';	
		}
		foreach($arr as $k=>$v)
		{
			$retstr .= $v.'linend';
		}
		$retstr = $retstr.$newstr;
		$this->m_m->set( array('k'=>$msg_key, 'v'=>$retstr) ); 

		//----------------------------------------------------------------------------------------------------------------------
		global $_pm;		
		if ($epl == 1)
		{
			$newstr = $word;
		}
		else
		{
			$newstr = '恭喜玩家 '.$_SESSION['nickname'].' '.$word;		
		}
		//$_olddata = @unserialize($_pm['mem']->get('ttmt_data_notice'));
		
 		$swfData = iconv('utf-8','utf-8',$newstr);

		require_once('../socketChat/config.chat.php');	
		require_once('../kernel/socketmsg.v1.php');
		$GLOBALS['server_ip']=$server_ip;
		$GLOBALS['socket_port']=$socket_port;
		$GLOBALS['pwd']=$pwd;
		$s=new socketmsg();
		
		//echo $newstr;
		$s->sendMsg('an|'.$swfData);
		
		//$_olddata['an'] = isset($_olddata['an'])?$_olddata['an']."<br/>[系统公告]：".$swfData:$swfData;
		//$_pm['mem']->set(array('k'=>'ttmt_data_notice','v'=>$_olddata));
		//----------------------------------------------------------------------------------------------------------------------
	
	
	}


	/**
	*@Usage: Save rand props.
	*@Param: Patter String.
		     ex: itemrand:853:3:1|854:3:1|855:1:1,gonggao:获得了一件奥运黄金首饰
			 itemrand:849:8:1|850:8:1|852:24:1|851:1:1,gonggao:获得了一件奥运黄金装备
	*@Return: String.
	*/
	function saveRand($propsPatter)
	{
		//$propsPatter 的格式为：itemrand:X:Y:Z 或者 itemrand:X:Y:Z|A:B:C
		//$patter = str_replace('itemrand:','',$propsPatter);
		global $_pm;
		$props = unserialize($_pm['mem']->get('db_propsid'));
		$patter = str_replace('itemrand:', '', $propsPatter);
		$arr = explode(',', $patter);			// arr[0] => rand props
		$propslist = explode('|', $arr[0]);		
		$retstr = '';
		if (is_array($propslist))
		{
			foreach ($propslist as $k => $v)
			{
				$inarr = explode(':', $v);		//	0=> ID, 1=> rand number, 3=> sum props
				if (rand(1, intval($inarr[1])) == 1)	//  rand hits
				{
					if ($this->saveProps(array(1=>$inarr[0], 2=>$inarr[2])) !== false)
					{
						$retstr .= ' 获得 '.$props[$inarr[0]]['name'].'&nbsp;'.$inarr[2].' 个';
						break;	
					}
				}
			} // end foreach
		}
		return $retstr;
	}

	/**
	*@Usage: Save Props.
	*@Param: array of $props.1=>props id, 2=> num
	*@Return: String
	*/
	function saveProps($props,$flagTrade=false)
	{
		//$props 为props:X:Z或者props:X|Y:Z	
		global $_pm;
		$db_props = unserialize($_pm['mem']->get('db_propsid'));		
		$pid = explode("|",$props[1]);
		if(is_array($pid))
		{
			$idlist = '';
			foreach($pid as $p)
			{
				$n = $db_props[$p]['name'].',';
				if(!empty($p))
				{
					$idnum = intval($props[2]);
					while($idnum--)
					{
						$idlist .= ','.$p;
					}
				}
			}
		}
		if ($this->saveGetProps(substr($idlist,1),array(),$flagTrade) === true)
			 return ' 任务奖励道具 '.$n.'&nbsp;'.$props[2].' 个';
		else return false;
		/*$idlist = '';
		$idnum = intval($props[2]);
		while($idnum--)
		{
			$idlist .= ','.$props[1];
		}	
		if ($this->saveGetProps(substr($idlist,1)) === true)
			 return ' 任务奖励道具 '.$props[2].' 个';
		else return false;*/
	}

    /**
	@Usage: Save exp of user pets.
	@Param: $exp, $pets' id
	@Return: String.
	*/
	function saveExp($exp,$id=0)
	{//print_r($exp);Array ( [0] => exp [1] => 1 [2] => 0|100 ) Array ( [0] => exp [1] => 10 [2] => 101|1000 ) Array ( [0] => exp [1] => 50 [2] => 1001|0 ) 根据贵族威望获得经验任务1 任务完成！您获得了 经验10
		//$exp 的格式：exp:X 或者：exp:X:Y|Z
		
		global $user,$_pm;
		$tid = $id==0?$user['mbid']:$id;
		$expnum = 0;
		if(!empty($exp[2]))
		{
			$wwarr = explode("|",$exp[2]);
			if(empty($wwarr[0]))
			{
				if($user['jprestige'] <= $wwarr[1])
				{
					$expnum = $exp[1];
				}
			}
			else if(empty($wwarr[1]))
			{
				if($user['jprestige'] >= $wwarr[0])
				{
					$expnum = $exp[1];
				}
			}
			else
			{
				if($user['jprestige'] >= $wwarr[0] && $user['jprestige'] <= $wwarr[1])
				{
					$expnum = $exp[1];
				}
			}
		}
		else
		{
			$expnum = $exp[1];
		}
		$bb = $_pm['mysql']->getOneRecord("SELECT * 
											 FROM userbb
											WHERE id={$tid} and uid={$_SESSION['id']}
										 ");
		$this->saveGetOther($bb, $expnum);
		if(!empty($expnum))
		{
			return '经验' . $expnum;
		}
		
		/*global $user,$_pm;
		$tid = $id==0?$user['mbid']:$id;
		$bb = $_pm['mysql']->getOneRecord("SELECT * 
											 FROM userbb
											WHERE id={$tid} and uid={$_SESSION['id']}
										 ");
		$this->saveGetOther($bb, $exp);
		return '经验' . $exp;*/
	}
	
	
	    /**
	@Usage: 吃经验月饼
	@Param: $exp, $pets' id
	@Return: String.
	*/
	function saveExps($exp,$id=0,$uid=0)
	{
		//$exp 的格式：exp:X 或者：exp:X:Y|Z
		if($uid == 0) $uid = $_SESSION['id'];
		global $_pm;
		$user = $_pm['user']->getUserById($uid);
		$tid = $id==0?$user['mbid']:$id;
		$bb = $_pm['mysql']->getOneRecord("SELECT * 
											 FROM userbb
											WHERE id={$tid} and uid=$uid
										 ");
		$result = $this->saveGetOther($bb, $exp);
		if ($result === false)
		{
			return false;
		}
		return '经验' . $exp;
	}
	
	//获得金币
	function saveMoney($money)
	{
		//$exp 的格式：money:X 或者：money:X:Y|Z
		global $user;
		global $_pm;
		$moneynum = 0;
		if(!empty($money[2]))
		{
			$wwarr = explode("|",$money[2]);
			if(empty($wwarr[0]))
			{
				if($user['jprestige'] <= $wwarr[1])
				{
					$moneyarr = $money[1];
				}
			}
			else if(empty($wwarr[1]))
			{
				if($user['jprestige'] >= $wwarr[0])
				{
					$moneynum = $money[1];
				}
			}
			else
			{
				if($user['jprestige'] >= $wwarr[0] && $user['jprestige'] <= $wwarr[1])
				{
					$moneynum = $money[1];
				}
			}
		}
		else
		{
			$moneynum = $money[1];
		}
		if(!empty($moneynum))
		{
			return '金币：'.$moneynum;
		}
	}
	
	/**
	@Usage: 存储获得经验。
	@Param: $bb->array, $exp->int.
	@Return: true or false.
	@Memo:		
	*/
	function saveGetOther($bb, $exp)
	{
		if($exp < 0)
		{
			die("信息有误！");
		}
		global $_pm;
		if (!is_array($bb)) return false;
		if (intval($bb['level']) >= 130) return false;

		$willexp = $bb['nowexp']+$exp;
		if ($willexp >= $bb['lexp'])
		{
			$now = $willexp-$bb['lexp'];			
			//############### Update start ###############
			$czz = $_pm['mem']->dataGet(array('k' => MEM_WX_KEY, 
									 'v' => "if(\$rs['wx'] == '{$bb['wx']}') \$ret=\$rs;"
							   ));
			$init = $_pm['mem']->dataGet(array('k' => MEM_BB_KEY, 
									  'v' => "if(\$rs['name'] == '{$bb['name']}') \$ret=\$rs;"
								));
			
			if (is_array($czz))
			{
				$kx = explode(',', $bb['kx']);
				while (count($kx) < 5) $kx[] = 0;
				if($bb['wx']==7){
					$maxlvlRow=$_pm['mysql']->getOneRecord('select max_level from super_jh where pet_id='.$init['id']);
					
					if($maxlvlRow&&$bb['level']>=$maxlvlRow['max_level'])
					{
						return false;
					}
				}
				//Get all attrib.
				$lv = ++$bb['level'];
				if($lv <= 130)
				{			
					$jk = intval($czz['j']*$bb['czl'])+$kx[0];
					$mk = intval($czz['m']*$bb['czl'])+$kx[1];
					$sk = intval($czz['s']*$bb['czl'])+$kx[2];
					$hk = intval($czz['h']*$bb['czl'])+$kx[3];
					$tk = intval($czz['t']*$bb['czl'])+$kx[4];
					$hp = intval($czz['hp']*$bb['czl'])+$bb['srchp'];
					$mp = intval($czz['mp']*$bb['czl'])+$bb['srcmp'];
					$ac = intval($czz['ac']*$bb['czl'])+$bb['ac'];
					$mc = intval($czz['mc']*$bb['czl'])+$bb['mc'];
					$sp = intval($czz['speed']*$bb['czl'])+$bb['speed'];
					$hits=intval($czz['hits']*$bb['czl'])+$bb['hits'];
					$miss=intval($czz['miss']*$bb['czl'])+$bb['miss'];
					
					$srchp = $hp;
					$srcmp = $mp;
	
					// Get Next Level exp require.
					$lrs = $_pm['mem']->dataGet(array('k' => MEM_EXP_KEY, 
											 'v' => "if(\$rs['level'] == '{$lv}') \$ret=\$rs;"
										  ));
					
					//update user bb.
					if(empty($lrs['nxtlvexp']))
					{
						die("数据错误！");
					}
					$_pm['mysql']->query("UPDATE userbb
								   SET level=	'{$lv}',
									   ac	=	'{$ac}',
									   mc	=	'{$mc}',
									   srchp=	'{$srchp}',
									   hp	=	'{$hp}',
									   srcmp=	'{$srcmp}',
									   mp	=	'{$mp}',
									   nowexp=	'0',
									   lexp	=	'{$lrs['nxtlvexp']}',
									   hits	=	'{$hits}',
									   miss	=	'{$miss}',
									   speed=	'{$sp}',
									   kx	=	'{$jk},{$mk},{$sk},{$hk},{$tk}'
								 WHERE id={$bb['id']} and uid={$bb['uid']}
							   ");
					if ($now > 0) 
					{
						$bb = $_pm['mysql']->getOneRecord("SELECT * 
															 FROM userbb
															WHERE id={$bb['id']} and uid={$bb['uid']}
														 ");
						$this->saveGetOther($bb, $now);
					}
					else return true;
				}			
			}
			//############### Update end.#################
			else return false;
		}
		else
		{
			// Save exp
			if($exp < 0)
			{
				die("信息有误！");
			}
			$_pm['mysql']->query("UPDATE userbb
						   SET nowexp=nowexp+{$exp}
						 WHERE id={$bb['id']} and uid={$bb['uid']}
					  ");
			return true;
		}
	}

	/**
	* @Usage: 存储用户得到的道具到用户包裹.
	* @Param: String, format: 1,2,3
	* @Return:  true of false
	*/
	function saveGetProps($idlist,$type = 0, $flagTrade=false)
	{
		if ($idlist == '' or $idlist == 0) return false;
		global $_pm, $user, $bag;

		/*$l=0;
		if (is_array($bag))
		{
			foreach ($bag as $x => $y)
			{
				if ($y['sums']>0 && $y['zbing']==0) $l++;
			}
		}
		if ($l >= $user['maxbag']) return false;*/
		
		$arr = split(',', $idlist);


		
		foreach ($arr as $k => $v)
		{
			$checkarr = array(1,1384,1206,920,921,922,1059,1060,1061,873,874,875,876,911,915,916,917,1048,1049,1050,1541,1648);
			if(!empty($type) && !in_array($type,$checkarr))
			{
				$tis = time();
				$sql = "INSERT INTO libao (pname,flag,cet,nums) values ({$v},{$type},{$tis},1)";
				$_pm['mysql'] -> query($sql);
			}
			$rs = false;
			$rs = $_pm['mysql']->getOneRecord("SELECT * FROM userbag WHERE uid={$_SESSION['id']} and pid={$v}");
			
			if (is_array($rs))
			{
				if ($rs['vary'] == 1) // 可折叠道具.
				{
					$tt = time();
					$_pm['mysql']->query("UPDATE userbag
								   SET sums=sums+1,
									   stime={$tt}
								 WHERE id={$rs['id']}
							  ");
				}
				else
				{

					
					$l=0;
					if (is_array($bag))
					{
						foreach ($bag as $x => $y)
						{
							if ($y['sums']>0 && $y['zbing']==0) $l++;
						}
					}
					if ($l >= $user['maxbag']) return false;
					
					$_pm['mysql']->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime"
					.($flagTrade?",cantrade":"").
					")
								VALUES(
									   '{$_SESSION['id']}',
									   '{$v}',
									   '{$rs['sell']}',
									   '{$rs['vary']}',
									   '1',
									   unix_timestamp()"
								.($flagTrade?",1":"")."
									  );
							  ");
				   $l++;
			   }	   
			}
			else{
			
				$l=0;
				if (is_array($bag))
				{
					foreach ($bag as $x => $y)
					{
						if ($y['sums']>0 && $y['zbing']==0) $l++;
					}
				}
				if ($l >= $user['maxbag']) return false;
				
				$rs = $_pm['mem']->dataGet(array('k' => MEM_PROPS_KEY, 
										'v' => "if(\$rs['id'] == '{$v}') \$ret=\$rs;"
									  ));
				if (is_array($rs))
				{
					$_pm['mysql']->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime"
					.($flagTrade?",cantrade":"").
					")
								VALUES(
									   '{$_SESSION['id']}',
									   '{$v}',
									   '{$rs['sell']}',
									   '{$rs['vary']}',
									   '1',
									   unix_timestamp()"
								.($flagTrade?",1":"")."
									  );
							  ");
					$l++;
				}	
			}		
			unset($rs);
			if ($l >= $user['maxbag']) return false;
		}	
		return true;
	}


//得到多个一种道具
	/**
	* @Usage: 得到多个一种道具
	* @Param: String, format: 1,2,3
	* @Return:  true of false
	*/
    function saveGetPropsMore($idlist, $num, $type = 0, $uid = 0,$propsrs=null)
    {
        if ($uid == 0) $uid = $_SESSION['id'];
        if ($idlist == '' or $idlist == 0) return false;
        global $_pm, $user, $bag;
        $user =$user?$user: $_pm['user']->getUserById($uid);
        $bag =$bag?$bag: $_pm['user']->getUserBagById($uid);
        $l = 0;
        if (is_array($bag)) {
            foreach ($bag as $x => $y) {
                if ($y['sums'] > 0 && $y['zbing'] == 0) $l++;
            }
        }
        if ($l >= $user['maxbag']) {
            return "200";
            exit;
        }
        $rs = false;
        $rs = $_pm['mysql']->getOneRecord("SELECT * FROM userbag WHERE uid=$uid and pid={$idlist}");
        if (is_array($rs)) {
            if ($rs['vary'] == 1) // 可折叠道具.
            {
                $tt = time();
                $_pm['mysql']->query("UPDATE userbag
							   SET sums=sums+$num,
								   stime={$tt}
							 WHERE id={$rs['id']}
						  ");
            } else {
                $_pm['mysql']->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
							VALUES(
								   $uid,
								   '{$idlist}',
								   '{$rs['sell']}',
								   '{$rs['vary']}',
								   {$num},
								   unix_timestamp()
								  );
						  ");
                $l++;
            }
        } else {
            $rs =$propsrs?$propsrs:getBasePropsInfoById($idlist);
            if (is_array($rs)) {
                $_pm['mysql']->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
							VALUES(
								   $uid,
								   '{$idlist}',
								   '{$rs['sell']}',
								   '{$rs['vary']}',
								   {$num},
								   unix_timestamp()
								  )
						  ");
                $l++;
            }
        }
        unset($rs);
        if ($l >= $user['maxbag']) return false;
        return true;
    }

	function saveGetPropsMore_return($idlist,$num,$type = 0,$uid=0)
	{
		if($uid==0)$uid=$_SESSION['id'];
		if ($idlist == '' or $idlist == 0) return false;
		global $_pm, $user, $bag;
		$user = $_pm['user']->getUserById($uid);
		$bag = $_pm['user']->getUserBagById($uid);
		$l=0;
		if (is_array($bag)){
			foreach ($bag as $x => $y){
				if ($y['sums']>0 && $y['zbing']==0) $l++;
			}
		}
		if ($l >= $user['maxbag']){
			return "200";
			exit;
		}
		//foreach ($arr as $k => $v)
		//{
		$rs = false;
		$checkarr = array(1,1384,1206,920,921,922,1059,1060,1061,873,874,875,876,911,915,916,917,1048,1049,1050,1541,1648);
		if(!empty($type) && !in_array($type,$checkarr))
		{
			$tis = time();
			$sql = "INSERT INTO libao (pname,flag,cet,nums) values ({$idlist},{$type},{$tis},{$num})";
			$_pm['mysql'] -> query($sql);
		}
		$rs = $_pm['mysql']->getOneRecord("SELECT * FROM userbag WHERE uid=$uid and pid={$idlist}");
		if (is_array($rs))
		{
			if ($rs['vary'] == 1) // 可折叠道具.
			{
				$tt = time();
				$_pm['mysql']->query("UPDATE userbag
							   SET sums=sums+$num,
								   stime={$tt}
							 WHERE id={$rs['id']}
						  ");
				$ret_thing = $rs['id'];
			}
			else
			{
				$ret_thing=$_pm['mysql']->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
							VALUES(
								   $uid,
								   '{$idlist}',
								   '{$rs['sell']}',
								   '{$rs['vary']}',
								   {$num},
								   unix_timestamp()
								  );
						  ");
			   $l++;
		   }	   
		}
		else{
			$rs = $_pm['mem']->dataGet(array('k' => MEM_PROPS_KEY, 
									'v' => "if(\$rs['id'] == '{$idlist}') \$ret=\$rs;"
								  ));
			if (is_array($rs))
			{
				$_pm['mysql']->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
							VALUES(
								   $uid,
								   '{$idlist}',
								   '{$rs['sell']}',
								   '{$rs['vary']}',
								   {$num},
								   unix_timestamp()
								  )
						  ");
				$ret_thing = 0;
				$l++;
			}	
		}		
		unset($rs);
		if ($l >= $user['maxbag']) return false;
		//}	
		return $ret_thing;
	}

	/**
	@Usage: 格式化任务标题。
	@Param: String Format str color.
	@Return: String.
	*/
	function formatTask($msg)
	{
		$colortag	=	array('[', 
			                  ']',
							  '{',
							  '}',
							  '(',
							  ')'
							  );

		$colorlist = array('<font color=#008200>',
						   '</font>',
						   '<font color=#848EF7>',
						   '</font>',
						   '<font color=#FF0000>',
						   '</font>'							  
						   );

		$msg = str_replace($colortag, $colorlist, $msg);
		return $msg;
	}
	
	/**
	@Usage: 任务显示状态。
	@Param: String Format str color.
	@Return: String.
	*/
	
	function completeTaskShow($user, $taskinfo)
	{
		$checks = 1;
		global $_pm;
		$bb = unserialize($_pm['mem']->get(MEM_BB_KEY));
		
		/**检查用户的包裹空间是否足够*/
		$bag = $_pm['user']->getUserBagById($_SESSION['id']);
		$petsAll	= $_pm['user']->getUserPetById($_SESSION['id']);
		$nowtime = date("YmdHis");
		$fromnpc = explode("|",$taskinfo['fromnpc']);
		$timearr = unserialize($_pm['mem']->get(MEM_TIME_KEY));
		$taskarr = unserialize($_pm['mem']->get(MEM_TASK_KEY));
		foreach($timearr as $tv)
		{
			if($tv['titles'] == "task")
			{
				$taskcheckarr[] = $tv;
			}
		}
		$checknum = 10;
		if(!empty($taskinfo['flags']))
		{
			foreach($timearr as $fv)
			{
				if($fv['days'] == $taskinfo['flags'] && $nowtime >= $fv['starttime'] && $nowtime <= $fv['endtime'])
				{
					$checknum = 11;
				}
			}
		}
		
		if($checknum != 10)
		{
			//die("对不起，该任务已经结束！");
			$checks = 2;
		}
		
		
		$l=0;
		$limit = explode(",",$taskinfo['limitlv']);
		if (is_array($bag))
		{
			foreach ($bag as $x => $y)
			{
				if ($y['sums']>0 && $y['zbing']==0) $l++;
			}
		}
		/*if ($l+2 >= $user['maxbag']) //return "您的背包空间不足，请预留至少3个背包格子，任务不能继续！";	
		$checks = 3;*/
		if(preg_match("/see\:(\d+)/",$taskinfo['okneed'],$out))
		{
			$_REQUEST['n']=$out[1];
		}
//echo $_REQUEST['n']."||";
		$sql = "select * from task_accept where taskid=".$taskinfo['id']." and uid={$_SESSION['id']}";
		//echo $sql;
		$arr_task = $_pm['mysql'] -> getOneRecord($sql);
		$user['tasklog'] = ',see:'.$_REQUEST['n'];		//记录任务完成度
		$user['tasklog'] .= ','.$arr_task['state'];
		
		//echo $user['tasklog']."<br /><br />";
		$need = split(',', $taskinfo['okneed']);// Check task whether complete.
		if (is_array($need))
		{
			$i = 0;
			foreach($need as $x => $y)
			{
				if (!empty($y)&&strpos($user['tasklog'], $y) === false) //,see:5
				{//echo __LINE__."<BR />";echo $i."<br />";
					$arr = explode(':', $y);
					if ($arr[0] == "giveitem")//see:5,killmon:24|75|58|41|8|42|25|76|89:150,lv:20|0
					{
						if ($this->existsProps($arr) === true) $i++; // arr
					}elseif($arr[0] == "zx"){//echo __LINE__."<BR />";echo $i."<br />";
						$i++;
					}
					else if ($arr[0] == "givejifen")
					{
						if ($this->existsJifen($arr) !== true)
						{
							//$str = '积分不够！';
							//return $str;
							$checks = 4;
							break;
						}
					}
					else if ($arr[0] == "giveww")
					{
						if ($this->existsWw($arr) !== true)
						{
							//$str = '威望不够！';
							//return $str;
							$checks = 5;
							break;
						}
					}
					else if ($arr[0] == "givevip")
					{
						if ($this->existsVip($arr) !== true)
						{
							//$str = '当月VIP反馈积分不够！';
							//return $str;
							$checks = 6;
							break;
						}
					}else if ($arr[0] == "giveml")
					{
						if ($this->existsMl($arr) !== true)
						{
							//$str = '您的魅力值不足，无法领取该奖励！';
							//return $str;
							$checks = 7;
							break;
						}
					}
					else if ($arr[0] == "givemoney")
					{
						if ($this->existsMoney($arr) !== true)
						{
							//$str = '金币不够！';
							//return $str;
							$checks = 8;
							break;
						}
					}
					else if($arr[0] == "givedianjuan")
					{
						if($this->existsDianjuan($arr) !== true)
						{
							//$str = '点卷不够！';
							//return $str;
							$checks = 9;
							break;
						}
					}
					else if ($arr[0] == "monself" || $arr[0] == "lv" || $arr[0] == "wx")
					{
						
						if(empty($user['mbid']))
						{
							//$str = "请先设置主战宠物！";
							//return $str;
							$checks = 10;
							break;
						}
						$arrm = explode("|",$arr[1]);
						$petsAll = $_pm['user']->getUserPetById($_SESSION['id']);
						foreach($petsAll as $pet)
						{
							if($pet['id'] == $user['mbid'])
							{
								$bname = $pet['name'];
								$blevel = $pet['level'];
								$bwx = $pet['wx'];
								break;
							}
						}
						if($arr[0] == 'wx')
						{
							if(!in_array($bwx,explode('|',$arr[1])))
							{//五行不符合
								$checks = 20;
								break;
							}else{
								$i++;
							}
						}
						if($arr[0] == "monself")
						{
							$bbs = unserialize($_pm['mem']->get(MEM_BB_KEY));
							foreach($arrm as $v)
							{
								foreach($bbs as $bv)
								{
									if($v == $bv['id'])
									{
										$bnamearr[] = $bv['name'];
									}
								}
							}
							if(!in_array($bname,$bnamearr))
							{
								//die("您不能用该主战宠物交此任务！");
								$checks = 11;
								break;
							}
						}
						
						if($arr[0] == 'lv')
						{
							if(empty($arrm[1]))
							{
								if($blevel < $arrm[0])
								{
									//die("您的等级不够完成此任务！");
									$checks = 12;
									break;
								}
							}
							else
							{
								if($blevel < $arrm[0] || $blevel > $arrm[1])
								{
									//die("您的等级不在完成此任务范围之类！");
									$checks = 13;
									break;
								}
							}
						}
						
					}
				}
				else 
				{
				//echo __LINE__."<BR />";
					$i++;
					//echo $i."<br />";
				}		
			}			
		}
		
		foreach($need as $n)
		{

			$arr = explode(":",$n);
			if($arr[0] != 'givejifen' && $arr[0] != 'giveww' && $arr[0] != 'lv' && $arr[0] != 'givemoney' && $arr[0] != 'monself' && $arr[0] != 'no' && $arr[0] != 'givevip' && $arr[0] != 'givedianjuan' && $arr[0] != 'paihang' && $arr[0] != 'giveml')
			{
				$needs[] = $n;
			}
			else
			{
				if($arr[0] == "givejifen")
				{
					$user['score'] -= $arr[1];
				}
				else if($arr[0] == 'giveww')
				{
					$user['prestige'] -= $arr[1];
				}
				else if($arr[0] == "givemoney")
				{
					$user['money'] -= $arr[1];
				}
				else if($arr[0] == "givevip")
				{
					$user['vip'] -= $arr[1];
				}else if($arr[0] == "giveml")
				{
					$ml = $arr[1];
				}
				else if($arr[0] == 'givedianjuan')
				{
					$user['active_score'] -= $arr[1];
				}
			}
		}
		//var_dump($user, $taskinfo,$i,$needs);
		if ($i != count($needs)) 
		{/*echo $i."||||||||<br />";
		print_r($needs);echo "<br />";
		print_r(count($needs));*/
		//echo "<br />";
			//$str = '让你做的事还没做完，是不能获得奖励的噢！';
			//$a = print_r($needs);
			$checks = 14;
		}
		else
		{
			if(is_array($limit))
			{
				foreach($limit as $v)
				{
					$limitarr = explode(":",$v);
					if($limitarr[0] == "cishu")
					{
						$sql = 'SELECT count(*) dif FROM tasklog WHERE date_format(from_unixtime(time),"%Y%m%d") > '.date('Ymd',time())." AND uid = {$_SESSION['id']} and taskid = {$taskinfo['id']}";
						$arr = $_pm['mysql'] -> getOneRecord($sql);
						if(is_array($arr))
						{
							/*$time = 24 * 3600 * $limitarr[2];
							$ntime = time();*/
							if($arr['dif'] >= $limitarr[1])
							{
								//die("该任务{$limitarr[2]}天只能完成{$limitarr[1]}次！");
								$checks = 15;
								break;
							}
							else
							{
								$checks = 1;
								break;
							}
						}
						else
						{
							$checks = 1;
							break;
						}
					}else if($limitarr[0] == "timelimit"){
						$t = $_pm['mysql'] -> getOneRecord('SELECT time FROM task_accept WHERE uid = '.$_SESSION['id'].' AND taskid = '.$taskinfo['id']);
						$nowtime = time();
						$c = ($nowtime - $t['time']) - $limitarr[1] * 3600;
						if($c > 0){
							$checks = 16;
							break;
						}
					}
					elseif($limitarr[0] == "xfsj"){
						$this->xfsj=$limitarr[1];
					}
					else if($limitarr[0] == "paihang")
					{
						if($user['paihang'] != $limitarr[1])
						{
							//die("您不能完成此任务！");
							$checks = 16;
							break;
						}
					}
					else if($limitarr[0] == "lv")
					{//echo __LINE__."<br />";
						foreach($petsAll as $bb)
						{
							if($bb['id'] == $user['mbid'])
							{
								$blv = $bb['level'];
							}
						}
						if(empty($blv))
						{
							//die("请先到牧场设置主战！");
							$checks = 17;
							break;
						}
						$lvarr = explode("|",$limitarr[1]);
						//print_r($lvarr);
						if($lvarr[1] == "0")
						{
							if($blv < $lvarr[0])
							{
								//die("您的等级不够接受此任务！");
								//echo __LINE__."<br />";
								$checks = 18;
								break;
							}
						}
						else
						{
							if($blv < $lvarr[0] || $blv > $lvarr[1])
							{
								//die("您的等级不在可接此任务范围之内！");
								$checks = 19;
								break;
							}
						}
					}
				}
			}
			//$this->clearTaskProps($need);	// array.
			//$checks = 17;
		}
		if($checks==1)
		{
			return true;
		}
		else
		{
			return false;
		}	
	}

    /**
	@Usage: 任务进度查询
	*/
	function queryTask()
	{
		
	}

	function __destruct(){
		unset($this->m_db, $this->m_m);
	}
}
?>
