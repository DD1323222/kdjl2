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
	private $re_str;
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
		$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		if($uid < 1) return '登录状态已失效，请重新登录！';
		if(!is_array($user)) $user = array();
		if(!isset($user['task'])) $user['task'] = 0;
		if(!isset($user['tasklog'])) $user['tasklog'] = '';
		/*
		if ($user['task'] == 0) // 启动新手任务.
		{
			$user['task'] = 1;	// update player task.
		}*/

		if (isset($_task['oknpc'][$s]))
		{
			$user['tasklog'] = ',see:'.$s;
		}

		$this->m_db->query("UPDATE player
							   SET task='{$user['task']}',
								   tasklog='{$user['tasklog']}'
							 WHERE id={$uid}
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
		$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		if($uid < 1) die('登录状态已失效，请重新登录！');
		if(!is_array($user)) $user = array();
		if(!is_array($taskinfo)) return '任务配置不存在！';
		$taskDefaults = array(
			'id' => 0,
			'title' => '',
			'fromnpc' => '',
			'limitlv' => '',
			'okneed' => '',
			'result' => '',
			'cid' => '',
			'xulie' => 0
		);
		foreach($taskDefaults as $key => $value)
		{
			if(!isset($taskinfo[$key])) $taskinfo[$key] = $value;
		}
		$taskinfo['id'] = intval($taskinfo['id']);
		$taskinfo['xulie'] = intval($taskinfo['xulie']);
		$userDefaults = array('money' => 0, 'score' => 0, 'prestige' => 0, 'active_score' => 0, 'mbid' => 0, 'maxbag' => 30, 'paihang' => 0, 'tasklog' => '');
		foreach($userDefaults as $key => $value)
		{
			if(!isset($user[$key])) $user[$key] = $value;
		}
		if(!taskScheduleIsActive($taskinfo))
		{
			die("对不起，该任务当前不在开放时间内，只能放弃！");
		}
		$needvip = 0;
		$ml = 0;
		$log = '';
		$taskOriginalMoney = intval($user['money']);
		$taskOriginalScore = intval($user['score']);
		$taskOriginalPrestige = intval($user['prestige']);
		$taskOriginalActiveScore = intval($user['active_score']);
		$bb = kdjlSafeMemValue($_pm['mem']->get(MEM_BB_KEY), array());
		$taskarr = kdjlSafeMemValue($_pm['mem']->get(MEM_TASK_KEY), array());
		if(!is_array($taskarr)) $taskarr = array();

		$fromnpc = explode("|",$taskinfo['fromnpc']);
		if(!isset($fromnpc[0]) || $fromnpc[0] === '') $fromnpc[0] = 0;
		$limit = explode(",",$taskinfo['limitlv']);



		$requestNpc = (isset($_REQUEST['n']) && !is_array($_REQUEST['n'])) ? intval($_REQUEST['n']) : 0;
		if(preg_match('/see\:(\d+)/', $taskinfo['okneed'], $seeMatch))
		{
			$requestNpc = intval($seeMatch[1]);
		}
		$user['tasklog'] .= ',see:'.$requestNpc;		//记录任务完成度
		$need = explode(',', $taskinfo['okneed']);// Check task whether complete.
		$needs = array();
		if (is_array($need))
		{
			$i = 0;
			foreach($need as $x => $y)
			{
				if (strpos($user['tasklog'], $y) === false)
				{
					$arr = explode(':', $y);
					if(!isset($arr[0]) || $arr[0] === '') continue;
					if($arr[0] === 'giveitm') $arr[0] = 'giveitem';
					if(!isset($arr[1])) $arr[1] = 0;
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
						if($arr[1] === 0 || $arr[1] === '')
						{
							$str = '任务条件配置错误！';
							return $str;
						}

						if(empty($user['mbid']))
						{
							$str = '请先设置主战宠物！';
							return $str;
							break;
						}
						$arrm = explode("|",$arr[1]);
						$bname = '';
						$blevel = 0;
						$bwx = 0;
						$petsAll = $_pm['user']->getUserPetById($uid);
						if(!is_array($petsAll)) $petsAll = array();
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
							$bbs = kdjlSafeMemValue($_pm['mem']->get(MEM_BB_KEY), array());
							if(!is_array($bbs)) $bbs = array();
							$bnamearr = array();
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
			if($n === '') continue;

			$arr = explode(":",$n);
			if(!isset($arr[0]) || $arr[0] === '') continue;
			if(!isset($arr[1])) $arr[1] = 0;
			if($arr[0] != 'givejifen' && $arr[0] != 'giveww' && $arr[0] != 'lv' && $arr[0] != 'givemoney' && $arr[0] != 'monself' && $arr[0] != 'no' && $arr[0] != 'givevip' && $arr[0] != 'givedianjuan' && $arr[0] != 'paihang' && $arr[0] != 'giveml')
			{
				$needs[] = $n;
			}
			else
			{
				if($arr[0] == "givejifen")
				{
					$user['score'] -= intval($arr[1]);
				}
				else if($arr[0] == 'giveww')
				{
					$user['prestige'] -= intval($arr[1]);
				}
				else if($arr[0] == "givemoney")
				{
					$user['money'] -= intval($arr[1]);
				}
				else if($arr[0] == "givevip")
				{
					$user['vip'] -= intval($arr[1]);
					$needvip = intval($arr[1]);
				}else if($arr[0] == "giveml")
				{
					$ml = intval($arr[1]);
				}
				else if($arr[0] == 'givedianjuan')
				{
					$user['active_score'] -= intval($arr[1]);
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
						if(count($limitarr) < 3) die('任务次数限制配置错误！');
						$today = strtotime(date('Ymd',time())) - (max(1,intval($limitarr[2])) - 1) * 24 * 3600;
						$sql = "SELECT count(*) sl FROM tasklog WHERE uid = {$uid} and taskid = {$taskinfo['id']} and time > {$today}";
						$arr = $_pm['mysql'] -> getOneRecord($sql);
						if(is_array($arr))
						{
							if(!isset($arr['sl'])) $arr['sl'] = 0;
							/*$time = 24 * 3600 * $limitarr[2];
							$ntime = time();*/
							if($arr['sl'] >= $limitarr[1] )
							{
								die("该任务{$limitarr[2]}天只能完成{$limitarr[1]}次！");
							}
							else
							{
								$time = time();
								if(!$_pm['mysql'] -> query("INSERT INTO tasklog (taskid,uid,xulie,time,fromnpc) VALUES ({$taskinfo['id']},{$uid},{$taskinfo['xulie']},{$time},{$fromnpc[0]})") ||
									mysql_affected_rows($_pm['mysql']->getConn()) != 1)
								{
									$_pm['mysql']->query('ROLLBACK');
									die('任务完成记录保存失败！');
								}
							}
						}
						else
						{
							$time = time();
							if(!$_pm['mysql'] -> query("INSERT INTO tasklog (taskid,uid,xulie,time,fromnpc) VALUES ({$taskinfo['id']},{$uid},{$taskinfo['xulie']},{$time},{$fromnpc[0]})") ||
								mysql_affected_rows($_pm['mysql']->getConn()) != 1)
							{
								$_pm['mysql']->query('ROLLBACK');
								die('任务完成记录保存失败！');
							}
						}
					}
					elseif($limitarr[0] == "xfsj"){
						if(!isset($limitarr[1])) die('任务限制配置错误！');
						$this->xfsj=$limitarr[1];
					}
					else if($limitarr[0] == "paihang")
					{
						if(!isset($limitarr[1])) die('任务限制配置错误！');
						if($user['paihang'] != $limitarr[1])
						{
							die('您不能完成此任务！');
						}
					}else if($limitarr[0] == "timelimit"){
						$t = $_pm['mysql'] -> getOneRecord('SELECT time FROM task_accept WHERE uid = '.$uid.' AND taskid = '.$taskinfo['id']);
						if(!is_array($t) || !isset($t['time'])) die('任务已超过完成时限！');
						$nowtime = time();
						$c = ($nowtime - $t['time']) - $limitarr[1] * 3600;
						if($c > 0){
							die('任务已超过完成时限！');
						}
					}
				}
			}
			$this->clearTaskProps($need);	// array.
		}


		// Update task.
		$gets = explode(',',$taskinfo['result']);
		$taskgets = '';

		$user['task'] = $taskinfo['cid'];
		$user['tasklog']= 0;
		//防外挂
		$wgarr = explode(":",$taskinfo['cid']);
		if($wgarr[0] == "rwl")
		{
			if(!isset($wgarr[1])) die('任务序列配置错误！');
			$sql = "select taskid from tasklog WHERE uid = {$uid} and taskid = {$taskinfo['id']}";
			$arr = $_pm['mysql'] -> getOneRecord($sql);
			if(is_array($arr) && strpos($taskinfo['limitlv'],"cishu") === false)
			{
				$rwlcheck = explode("|",$wgarr[1]);
				if(!isset($rwlcheck[1]) || $rwlcheck[0] != $rwlcheck[1])
				{
					//$_pm['mysql'] -> query("UPDATE player SET secid = 3 WHERE id = {$_SESSION['id']}");
					$_SESSION['id'] = "";
					die('非法任务操作！');
				}
			}
			else
			{
				$sql = "select taskid from tasklog WHERE uid = {$uid} and fromnpc = {$fromnpc[0]} and xulie = {$taskinfo['xulie']}";
				$arrs = $_pm['mysql'] -> getOneRecord($sql);


				$time = time();
				if(is_array($arrs)){
					$prevTaskId = intval($arrs['taskid']);
					if(!isset($taskarr[$prevTaskId]) || !is_array($taskarr[$prevTaskId])){
						die('任务序列配置错误！');
					}
					$t1 = $taskarr[$prevTaskId];
					$t1Cid = isset($t1['cid']) ? $t1['cid'] : '';
					$t1_cid = explode(':',$t1Cid,2);
					if(count($t1_cid) < 2 || $t1_cid[0] != 'rwl'){
						die('任务序列配置错误！');
					}
					$tid_arr = explode('|',$t1_cid[1]);
					$nextTaskId = isset($tid_arr[1]) ? intval($tid_arr[1]) : 0;
					if(intval($taskinfo['id']) != $nextTaskId){
						die('您不能完成这个任务！');
					}
					if(!$_pm['mysql'] -> query("UPDATE tasklog SET taskid = {$taskinfo['id']} WHERE uid = {$uid} and fromnpc = {$fromnpc[0]} and xulie = {$taskinfo['xulie']}"))
					{
						$_pm['mysql']->query('ROLLBACK');
						die('任务序列保存失败！');
					}
				}
				else
				{
					if(!$_pm['mysql'] -> query("INSERT INTO tasklog (taskid,uid,xulie,time,fromnpc) VALUES({$taskinfo['id']},{$uid},{$taskinfo['xulie']},{$time},{$fromnpc[0]})") ||
						mysql_affected_rows($_pm['mysql']->getConn()) != 1)
					{
						$_pm['mysql']->query('ROLLBACK');
						die('任务序列保存失败！');
					}
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
			$arr = $_pm['mysql'] -> getOneRecord("SELECT taskid FROM tasklog WHERE uid = {$uid} and taskid = {$taskinfo['id']}");
			if(is_array($arr))
			{
				//$_pm['mysql'] -> query("UPDATE player SET secid = 3 WHERE id = {$_SESSION['id']}");
				$_SESSION['id'] = "";
				die('非法操作');
			}
			else
			{
				$time = time();
				if(!$_pm['mysql'] -> query("INSERT INTO tasklog (taskid,uid,xulie,time,fromnpc) VALUES ({$taskinfo['id']},{$uid},{$taskinfo['xulie']},{$time},{$fromnpc[0]})") ||
					mysql_affected_rows($_pm['mysql']->getConn()) != 1)
				{
					$_pm['mysql']->query('ROLLBACK');
					die('任务完成记录保存失败！');
				}
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
		$taskAcceptDeleted = $_pm['mysql'] -> query("DELETE FROM task_accept WHERE uid = {$uid} AND taskid = {$taskinfo['id']}");
		$result = $taskAcceptDeleted ? mysql_affected_rows($_pm['mysql'] -> getConn()) : 0;
		if($result != 1){
			$_pm['mysql']->query('ROLLBACK');
			if(isset($id)) unLockItem($id);
			die("操作有误！");
		}

		if(isset($ml) && $ml > 0){
			if(!$_pm['mysql'] -> query("UPDATE player_ext SET ml = ml-$ml WHERE uid = {$uid} AND ml >= $ml") ||
				mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){
				$_pm['mysql']->query('ROLLBACK');
				die('魅力值不足，无法领取奖励！');
			}
		}
		if($needvip > 0){
			$vipUsed = $_pm['mysql'] -> query("UPDATE player SET vip = vip-$needvip WHERE id = {$uid} AND vip >= $needvip");
			//echo "UPDATE player SET vip = vip-$needvip WHERE id = {$_SESSION['id']} AND vip >= $needvip";
			if(!$vipUsed || mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){
				$_pm['mysql']->query('ROLLBACK');
				die('当月VIP反馈积分不足，无法领取奖励！');
			}
		}

		if (is_array($gets))
		{
			if (isset($gets[0]))
			{
				foreach($gets as $k => $v) // money,exp.
				{
					$tt = explode(':', $v);
					if(!isset($tt[0]) || $tt[0] === '') continue;
					$rewardType = strtolower(trim($tt[0]));
					switch ($rewardType)
					{
						case "fksj":
							if(!isset($tt[1])) break;
							if($this->getSJ($tt[1])){
								$taskgets.= $this->re_str."<br/>";
							    $this->saveGword("消费了大量元宝，自然女神奖励其慷慨赠送他大量水晶币<br/>");
								unset($this->re_str);
								unset($this->xfsj);
							}
							break;

						case "exp":
							if(!isset($tt[1])) break;
							$rewardText = $this->saveExp($tt);
							if($rewardText === false)
							{
								$this->m_db->query('ROLLBACK');
								die('任务经验奖励发放失败！');
							}
							$taskgets .= $rewardText."<br/>";
							break;
						case "props":
							if(!isset($tt[1]) || !isset($tt[2]) || intval($tt[1]) < 1 || intval($tt[2]) < 1) break;
							$rewardText = $this->saveProps($tt);
							if($rewardText === false){
								$this->m_db->query('ROLLBACK');
								die('任务奖励发放失败，请预留足够背包空间后重试！');
							}
							$taskgets .= $rewardText."<br/>";
							$log.=print_r($tt,1).'==>'.$taskgets;
							break;
						case "bprops":
							if(!isset($tt[1]) || !isset($tt[2]) || intval($tt[1]) < 1 || intval($tt[2]) < 1) break;
							$rewardText = $this->saveProps($tt,true);
							if($rewardText === false){
								$this->m_db->query('ROLLBACK');
								die('任务奖励发放失败，请预留足够背包空间后重试！');
							}
							$taskgets .= $rewardText."<br/>";
							$log.=print_r($tt,1).'==>'.$taskgets;
						break;
						//case "money":    $user['money']+=$tt[1];$taskgets .= ' 金币'.$tt[1]; break;
						case "money":
							$moneystr = $this->saveMoney($tt);
							$moneyarr = explode(':',$moneystr);
							$user['money'] += isset($moneyarr[1]) ? intval($moneyarr[1]) : 0;
							$taskgets .= $moneystr."<br/>";
							break;
						    //$taskgets .= $this->saveMoney($tt); break;
						case "itemrand":
							$rewardText = $this->saveRand($v);
							if($rewardText === false)
							{
								$this->m_db->query('ROLLBACK');
								die('任务随机道具奖励发放失败！');
							}
							$taskgets .= $rewardText."<br/>";
							$log.=print_r($tt,1).'==>'.$taskgets;
							break;
						case "gonggao":
							if(isset($tt[1])) $this->saveGword($tt[1])."<br/>";
							break;
						case "paihang":  $user['paihang'] = 0;break;
						case "lvprops" :
							$rewardText = $this->levelProps($tt);
							if($rewardText === false){
								$this->m_db->query('ROLLBACK');
								die('任务奖励发放失败，请预留足够背包空间后重试！');
							}
							if($rewardText !== '') $taskgets .= $rewardText."<br/>";
							$log.=print_r($tt,1).'==>'.$taskgets;
							break;
						//case "givejifen":  $user['score']+=$tt[1];$taskgets .= ' 积分'.$tt[1]; break;
						// In here add more patter...
					}
				} // end foreach
			}
		}
		$time = time();
		$log .= '==>任务id：'.$taskinfo['id'];
		$logSql = $this->m_db->escape($log);
		$uidSql = $uid;
		$taskIdSql = intval($taskinfo['id']);
		if(!$_pm['mysql'] -> query("INSERT INTO gamelog(ptime,seller,buyer,pnote,vary) VALUES ({$time},{$uidSql},{$taskIdSql},'{$logSql}',161)")){
			$_pm['mysql']->query('ROLLBACK');
			die('任务日志保存失败！');
		}
		//vip 记录 vary 为6的是vip记录，seller是玩家ID，buyer是任务号,ptime 是时间，pnote是任务标题;
		if($taskinfo['id'] >= 179 && $taskinfo['id'] <= 190)
		{
			$vipTitleSql = $this->m_db->escape($taskinfo['title']);
			if(!$_pm['mysql'] -> query("INSERT INTO gamelog(ptime,seller,buyer,pnote,vary) VALUES ({$time},{$uidSql},{$taskIdSql},'{$vipTitleSql}',6)")){
				$_pm['mysql']->query('ROLLBACK');
				die('任务日志保存失败！');
			}
		}
		//vip记录到此结束
		$moneyDelta = intval($user['money']) - $taskOriginalMoney;
		$scoreDelta = intval($user['score']) - $taskOriginalScore;
		$prestigeDelta = intval($user['prestige']) - $taskOriginalPrestige;
		$activeScoreDelta = intval($user['active_score']) - $taskOriginalActiveScore;
		$playerSet = array();
		$playerWhere = "id={$uid}";
		if($moneyDelta > 0)
		{
			$playerSet[] = "money=LEAST(COALESCE(money,0)+{$moneyDelta},1000000000)";
		}
		else if($moneyDelta < 0)
		{
			$needMoney = abs($moneyDelta);
			$playerSet[] = "money=COALESCE(money,0)-{$needMoney}";
			$playerWhere .= " AND COALESCE(money,0)>={$needMoney}";
		}
		if($scoreDelta != 0)
		{
			$scoreOp = $scoreDelta > 0 ? '+' : '-';
			$scoreAmount = abs($scoreDelta);
			$playerSet[] = "score=COALESCE(score,0){$scoreOp}{$scoreAmount}";
			if($scoreDelta < 0) $playerWhere .= " AND COALESCE(score,0)>={$scoreAmount}";
		}
		if($prestigeDelta != 0)
		{
			$prestigeOp = $prestigeDelta > 0 ? '+' : '-';
			$prestigeAmount = abs($prestigeDelta);
			$playerSet[] = "prestige=COALESCE(prestige,0){$prestigeOp}{$prestigeAmount}";
			if($prestigeDelta < 0) $playerWhere .= " AND COALESCE(prestige,0)>={$prestigeAmount}";
		}
		if($activeScoreDelta != 0)
		{
			$activeScoreOp = $activeScoreDelta > 0 ? '+' : '-';
			$activeScoreAmount = abs($activeScoreDelta);
			$playerSet[] = "active_score=COALESCE(active_score,0){$activeScoreOp}{$activeScoreAmount}";
			if($activeScoreDelta < 0) $playerWhere .= " AND COALESCE(active_score,0)>={$activeScoreAmount}";
		}
		$playerSet[] = "paihang = ".intval($user['paihang']);
		$playerSet[] = "task=''";
		$playerSet[] = "tasklog=''";
		$playerSql = "UPDATE player SET ".implode(',', $playerSet)." WHERE ".$playerWhere;
		$playerUpdated = $_pm['mysql']->query($playerSql);
		$playerAffected = $playerUpdated ? mysql_affected_rows($_pm['mysql']->getConn()) : -1;
		if($playerUpdated && $playerAffected === 0){
			// MySQL reports zero affected rows when every assigned value is unchanged.
			$playerMatched = $_pm['mysql']->getOneRecord("SELECT id FROM player WHERE ".$playerWhere." LIMIT 1");
			if(!$playerMatched) $playerUpdated = false;
		}
		if(!$playerUpdated || $playerAffected > 1){
			$_pm['mysql']->query('ROLLBACK');
			die('任务结算失败，请稍候再试！');
		}
		//return $taskinfo['title'] . ' 任务完成！您获得了 ' . $taskgets;
		return $taskinfo['title'] . ' 任务完成！您获得了相应任务奖励！';
	}




	function levelProps($arr){
		global $_pm;
		$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		if($uid < 1) return false;
		if(!isset($arr[1]) || !isset($arr[2]) || !isset($arr[3])) return false;
		$pid = intval($arr[1]);
		$num = intval($arr[2]);
		if($pid < 1 || $num < 1) return false;
		$props = kdjlSafeMemValue($_pm['mem']->get('db_propsid'), array());
		if(!is_array($props)) $props = array();
		$u = $_pm['mysql'] -> getOneRecord('SELECT level FROM userbb,player WHERE player.id = '.$uid.' AND player.mbid = userbb.id AND userbb.uid = player.id');
		$ar = explode('|',$arr[3]);
		if(!is_array($u) || !isset($u['level'])) return false;
		$minLevel = isset($ar[0]) ? intval($ar[0]) : 0;
		$maxLevel = isset($ar[1]) ? intval($ar[1]) : 0;
		if($u['level'] < $minLevel || ($u['level'] > $maxLevel && $maxLevel != 0)){
			return '';
		}
		$giveResult = $this->saveGetPropsMore($pid,$num,0,$uid);
		if($giveResult !== true) return false;
		$pname = isset($props[$pid]['name']) ? $props[$pid]['name'] : $pid;
			return '获得道具 '.$pname.'&nbsp;'.$num.' 个<br />';
	}


	/**
	@Param: patter of arr=>giveitem,ID,num
	@Return: true of false
	@Param ex: giveitem:843:1,giveitem:844:1,giveitem:845:1,giveitem:846:1
	*/
	function existsProps($arr)
	{
		$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		if($uid < 1 || !isset($arr[1]) || !isset($arr[2])) return false;
		$pidMap = array();
		foreach(explode('|',$arr[1]) as $pid)
		{
			$pid = intval($pid);
			if($pid > 0) $pidMap[$pid] = $pid;
		}
		$needNum = intval($arr[2]);
		if($needNum < 1 || count($pidMap) < 1) return false;
		$pidList = implode(',',array_values($pidMap));
		$rs = $this->m_db->getOneRecord("SELECT sum(sums) as cnt
										   FROM userbag
										  WHERE pid in({$pidList}) and uid={$uid} and zbing=0 and sums>0
										    AND (cantrade IS NULL OR cantrade<>3)
									   ");

		if (is_array($rs) && isset($rs['cnt']) && $rs['cnt']>=$needNum)
		{
			return true;
		}
		else return false;
	}

	function getSJ($str){
		$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		if($uid < 1) return false;
		$get_sum=0;
		if(!empty($str)){
			$rs = $this->m_db->getOneRecord("SELECT name
										   FROM player
										  WHERE id={$uid}
									   ");
			if(!is_array($rs) || !isset($rs['name'])) return 0;
			$safeYbName = $this->m_db->escape($rs['name']);
			$check = $this->m_db->getOneRecord("select time from tasklog where uid = {$uid} and taskid = 88888 order by id desc limit 1");
			if(is_array($check) && isset($check['time'])){
				$getYb = $this->m_db->getRecords("select * from yblog where nickname='{$safeYbName}' AND id > ".intval($check['time'])." order by id desc");
			}else{
				$getYb = $this->m_db->getRecords("select * from yblog where nickname='{$safeYbName}' order by id desc");
			}

			//加入日志
			if(!is_array($getYb) || empty($getYb)) return 0;
			if(!isset($getYb[0]['id'])) return 0;
			$this->m_db->query("INSERT INTO tasklog (uid,taskid,time) VALUES({$uid},88888,".intval($getYb[0]['id']).")");
			$t=explode("|",$this->xfsj);
			if(is_array($getYb) && is_array($t) && isset($t[0]) && isset($t[1])){
				foreach($getYb as $k=>$v){
					if(!is_array($v) || !isset($v['buytime']) || !isset($v['yb'])) continue;
					if(date('Ymd',$v['buytime'])>=$t[0] && date('Ymd',$v['buytime'])<=$t[1]){
						$get_sum+=intval($v['yb']);
					}
				}
				if($get_sum>0){
					$f=substr($str,0,-1);
					$get_sum=intval($f*$get_sum/100);
					$rewardOk = $this->m_db->query("UPDATE player_ext
							                       SET sj=COALESCE(sj,0)+$get_sum
												 WHERE uid={$uid}
											  ");
					if(!$rewardOk || mysql_affected_rows($this->m_db->getConn()) != 1){
						return false;
					}
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
		if(!isset($arr[1])) return false;
		//$arr[1] = str_replace('|',',',$arr[1]);
		/*$rs = $this->m_db->getOneRecord("SELECT score
										   FROM player
										  WHERE id={$_SESSION['id']}
									   ");*/
		global $user;
		if (is_array($user) && !(empty($user['score'])) && $user['score']>=intval($arr[1]))
		{
			return true;
		}
		else return false;
	}

	//点卷
	function existsDianjuan($arr)
	{
		$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		if($uid < 1 || !isset($arr[1])) return false;
		//$arr[1] = str_replace('|',',',$arr[1]);
		$rs = $this->m_db->getOneRecord("SELECT active_score
										   FROM player
										  WHERE id={$uid}
									   ");
		if (is_array($rs) && isset($rs['active_score']) && $rs['active_score']>=intval($arr[1]))
		{
			return true;
		}
		else return false;
	}

	//VIP
	function existsVip($arr)
	{
		$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		if($uid < 1 || !isset($arr[1])) return false;
		//$arr[1] = str_replace('|',',',$arr[1]);
		$rs = $this->m_db->getOneRecord("SELECT vip
										   FROM player
										  WHERE id={$uid}
									   ");
		if (is_array($rs) && isset($rs['vip']) && $rs['vip']>=intval($arr[1]))
		{
			return true;
		}
		else return false;
	}
	//魅力判断
	function existsMl($arr)
	{
		$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		if($uid < 1 || !isset($arr[1])) return false;
		//$arr[1] = str_replace('|',',',$arr[1]);
		$rs = $this->m_db->getOneRecord("SELECT ml
										   FROM player_ext
										  WHERE uid={$uid}
									   ");
		if (is_array($rs) && isset($rs['ml']) && $rs['ml']>=intval($arr[1]))
		{
			return true;
		}
		else return false;
	}

	//威望判断
	function existsWw($arr)
	{
		if(!isset($arr[1])) return false;
		global $user;
		//$arr[1] = str_replace('|',',',$arr[1]);
		/*$rs = $this->m_db->getOneRecord("SELECT score
										   FROM player
										  WHERE id={$_SESSION['id']}
									   ");*/
		if (is_array($user) && !(empty($user['prestige'])) && $user['prestige']>=intval($arr[1]))
		{
			return true;
		}
		else return false;
	}

	//金币判断

	//威望判断
	function existsMoney($arr)
	{
		if(!isset($arr[1])) return false;
		global $user;
		//$arr[1] = str_replace('|',',',$arr[1]);
		/*$rs = $this->m_db->getOneRecord("SELECT score
										   FROM player
										  WHERE id={$_SESSION['id']}
									   ");*/
		if (is_array($user) && !(empty($user['money'])) && $user['money']>=intval($arr[1]))
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
		$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		if($uid < 1 || !is_array($need)) return false;
		//echo $need;exit;
		$delcount = 0;
		foreach($need as $x => $y)
		{
			$arr = explode(':', $y);
			if(isset($arr[0]) && $arr[0] === 'giveitm') $arr[0] = 'giveitem';

			if ($arr[0] == "giveitem")
			{
				$required = isset($arr[2]) ? intval($arr[2]) : 0;
				if(!isset($arr[1])) die('任务物品需求配置错误！');
				$pidMap = array();
				foreach(explode('|',$arr[1]) as $pid){
					$pid = intval($pid);
					if($pid > 0) $pidMap[$pid] = $pid;
				}
				if($required < 1 || count($pidMap) < 1){
					die('任务物品需求配置错误！');
				}
				$pidList = implode(',', array_values($pidMap));
				$ret = $this->m_db->getOneRecord("SELECT id,sums
											     FROM userbag
												WHERE pid in({$pidList}) and uid={$uid} AND zbing=0 AND sums>0
												  AND (cantrade IS NULL OR cantrade<>3)
												ORDER by sums desc
											 ");
				if(!is_array($ret) || intval($ret['sums']) < $required){
					die('任务物品数量不足！');
				}
				$id = intval($ret['id']);
				if(!$this->m_db->query("UPDATE userbag
							                       SET sums=sums - {$required}
												 WHERE id={$id} and uid={$uid} AND zbing=0 and sums >= {$required}
												   AND (cantrade IS NULL OR cantrade<>3)
											  ") || mysql_affected_rows($this->m_db->getConn()) != 1){
					die('任务物品扣除失败！');
				}
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
		$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		if($uid < 1 || !is_array($need)) return false;
		foreach($need as $x => $y)
		{
			$arr = explode(':', $y);
			if(isset($arr[0]) && $arr[0] === 'giveitm') $arr[0] = 'giveitem';
			if ($arr[0] != "giveitem") continue;

			$required = isset($arr[2]) ? intval($arr[2]) : 0;
			if(!isset($arr[1])){
				$this->m_db->query('ROLLBACK');
				die('任务物品需求配置错误！');
			}
			$pidMap = array();
			foreach(explode('|',$arr[1]) as $pid){
				$pid = intval($pid);
				if($pid > 0) $pidMap[$pid] = $pid;
			}
			if($required < 1 || count($pidMap) < 1){
				$this->m_db->query('ROLLBACK');
				die('任务物品需求配置错误！');
			}

			$pidList = implode(',',array_values($pidMap));
			$ret = $this->m_db->getRecords("SELECT id,sums
									     FROM userbag
											WHERE pid IN ($pidList) AND uid={$uid} AND zbing=0 AND sums>0
											  AND (cantrade IS NULL OR cantrade<>3)
											ORDER BY sums DESC,id ASC
											FOR UPDATE");
			$total = 0;
			if(is_array($ret)){
				foreach($ret as $v)
				{
					if(is_array($v) && isset($v['sums'])) $total += intval($v['sums']);
				}
			}
			if($total < $required){
				$this->m_db->query('ROLLBACK');
				die('任务物品数量不足！');
			}

			$remaining = $required;
			foreach($ret as $v){
				if(!is_array($v) || !isset($v['sums']) || !isset($v['id'])) continue;
				if($remaining < 1) break;
				$take = min(intval($v['sums']),$remaining);
				$id = intval($v['id']);
				$sql = "UPDATE userbag SET sums=sums-$take WHERE id=$id AND uid={$uid} AND zbing=0 AND sums>=$take AND (cantrade IS NULL OR cantrade<>3)";
				if(!$this->m_db->query($sql) || mysql_affected_rows($this->m_db->getConn()) != 1){
					$this->m_db->query('ROLLBACK');
					die('任务物品扣除失败！');
				}
				if(!$this->m_db->query("DELETE FROM userbag WHERE id=$id AND uid={$uid} AND sums<=0 AND bsum<=0 AND psum<=0 AND pyb=0 AND zbing=0 AND (cantrade IS NULL OR cantrade<>3)")){
					$this->m_db->query('ROLLBACK');
					die('任务物品扣除失败！');
				}
				$remaining -= $take;
			}
		}
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
		$nickname = isset($_SESSION['nickname']) ? $_SESSION['nickname'] : '';
		$msg_key = 'chatMsgList';
		$nowMsgList = kdjlSafeMemValue($this->m_m->get($msg_key), '');
		if(!is_string($nowMsgList)) $nowMsgList = '';
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
			$newstr = '<font color=red>[系统公告]恭喜玩家 '.$nickname.' '.$word.'</font>';
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
			$newstr = '恭喜玩家 '.$nickname.' '.$word;
		}
		//$_olddata = @unserialize($_pm['mem']->get('ttmt_data_notice'));

		$swfData = kdjlSafeIconv('utf-8','utf-8',$newstr);

		require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
		require_once(dirname(__FILE__).'/socketmsg.v1.php');
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
		$props = kdjlSafeMemValue($_pm['mem']->get('db_propsid'), array());
		if(!is_array($props)) $props = array();
		$patter = str_replace('itemrand:', '', $propsPatter);
		$arr = explode(',', $patter);			// arr[0] => rand props
		if(!isset($arr[0]) || $arr[0] === '') return '';
		$propslist = explode('|', $arr[0]);
		$retstr = '';
		if (is_array($propslist))
		{
			foreach ($propslist as $k => $v)
			{
				$inarr = explode(':', $v);		//	0=> ID, 1=> rand number, 3=> sum props
				if(count($inarr) < 3) continue;
				$pid = intval($inarr[0]);
				$chance = intval($inarr[1]);
				$num = intval($inarr[2]);
				if($pid < 1 || $chance < 1 || $num < 1 || !isset($props[$pid])) continue;
				if (rand(1, $chance) == 1)	//  rand hits
				{
					$rewardText = $this->saveProps(array(1=>$pid, 2=>$num));
					if ($rewardText !== false)
					{
						$retstr .= '获得'.$props[$pid]['name'].'&nbsp;'.$num.'个';
						break;
					}
					return false;
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
		$db_props = kdjlSafeMemValue($_pm['mem']->get('db_propsid'), array());
		if(!is_array($db_props)) $db_props = array();
		$idnum = isset($props[2]) ? intval($props[2]) : 0;
		if($idnum < 1 || !isset($props[1])) return false;
		$pid = explode("|",$props[1]);
		$idlist = '';
		$n = '';
		if(is_array($pid))
		{
			foreach($pid as $p)
			{
				$p = intval($p);
				if($p > 0 && isset($db_props[$p]))
				{
					$n = $db_props[$p]['name'].',';
					for($i = 0; $i < $idnum; $i++)
					{
						$idlist .= ','.$p;
					}
				}
			}
		}
		if($idlist === '') return false;
		if ($this->saveGetProps(substr($idlist,1),array(),$flagTrade) === true)
			 return '任务奖励道具 '.$n.'&nbsp;'.$props[2].'个';
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
		$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		if($uid < 1 || !isset($exp[1])) return false;
		if(!is_array($user)) $user = array();
		if(!isset($user['mbid'])) $user['mbid'] = 0;
		if(!isset($user['jprestige'])) $user['jprestige'] = 0;
		$tid = $id==0?intval($user['mbid']):intval($id);
		if($tid < 1) return false;
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
											WHERE id={$tid} and uid={$uid}
										 ");
		if(!is_array($bb)) return false;
		$petMaxLevel = 130;
		if(intval($bb['wx']) == 7)
		{
			$init = getBaseBBInfoForUserPet($bb);
			if(is_array($init) && isset($init['id']))
			{
				$maxlvlRow = $_pm['mysql']->getOneRecord('select max_level from super_jh where pet_id='.intval($init['id']));
				if(is_array($maxlvlRow) && intval($maxlvlRow['max_level']) > 0)
				{
					$petMaxLevel = min(130, intval($maxlvlRow['max_level']));
				}
			}
		}
		if(intval($bb['level']) >= $petMaxLevel) return '';
		if($this->saveGetOther($bb, $expnum) === false) return false;
		if(!empty($expnum))
		{
			return '经验' . $expnum;
		}
		return '';

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
		if($uid == 0) $uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		$uid = intval($uid);
		if($uid < 1) return false;
		global $_pm;
		$user = $_pm['user']->getUserById($uid);
		if(!is_array($user) || !isset($user['mbid'])) return false;
		$tid = $id==0?intval($user['mbid']):intval($id);
		if($tid < 1) return false;
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
		if(!is_array($money) || !isset($money[1])) return 'money:0';
		if(!is_array($user)) $user = array();
		if(!isset($user['jprestige'])) $user['jprestige'] = 0;
		$moneynum = 0;
		if(!empty($money[2]))
		{
			$wwarr = explode("|",$money[2]);
			if(empty($wwarr[0]))
			{
				if($user['jprestige'] <= $wwarr[1])
				{
					$moneynum = $money[1];
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
			return 'money:'.max(0,intval($moneynum));
		}
		return 'money:0';
	}

	/**
	@Usage: 存储获得经验。
	@Param: $bb->array, $exp->int.
	@Return: true or false.
	@Memo:
	*/
	function saveGetOther($bb, $exp)
	{
		$exp = intval($exp);
		if($exp < 0)
		{
			die('任务信息无效！');
		}
		if($exp == 0) return true;
		global $_pm;
		if (!is_array($bb)) return false;
		$bbDefaults = array(
			'id' => 0,
			'uid' => 0,
			'name' => '',
			'level' => 0,
			'nowexp' => 0,
			'lexp' => 0,
			'wx' => 0,
			'kx' => '0,0,0,0,0',
			'czl' => 0,
			'srchp' => 0,
			'srcmp' => 0,
			'ac' => 0,
			'mc' => 0,
			'speed' => 0,
			'hits' => 0,
			'miss' => 0
		);
		foreach($bbDefaults as $key => $value)
		{
			if(!isset($bb[$key])) $bb[$key] = $value;
		}
		$bb['id'] = intval($bb['id']);
		if($bb['id'] < 1) return false;
		if (intval($bb['level']) >= 130) return false;

		$willexp = $bb['nowexp']+$exp;
		if ($willexp >= $bb['lexp'])
		{
			$now = $willexp-$bb['lexp'];
			//############### Update start ###############
			$czz = $_pm['mem']->dataGet(array('k' => MEM_WX_KEY,
									 'v' => "if(\$rs['wx'] == '{$bb['wx']}') \$ret=\$rs;"
							   ));
			$init = getBaseBBInfoForUserPet($bb);

			if (is_array($czz) && is_array($init) && isset($init['id']))
			{
				foreach(array('j','m','s','h','t','hp','mp','ac','mc','speed','hits','miss') as $czzKey)
				{
					if(!isset($czz[$czzKey])) $czz[$czzKey] = 0;
				}
				$kx = explode(',', $bb['kx']);
				while (count($kx) < 5) $kx[] = 0;
				$petMaxLevel = 130;
				if($bb['wx']==7){
					$maxlvlRow=$_pm['mysql']->getOneRecord('select max_level from super_jh where pet_id='.intval($init['id']));

					if($maxlvlRow && intval($maxlvlRow['max_level']) > 0)
					{
						$petMaxLevel = min(130, intval($maxlvlRow['max_level']));
						if($bb['level'] >= $petMaxLevel) return false;
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
						die('经验配置错误！');
					}
					$updateOk = $_pm['mysql']->query("UPDATE userbb
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
					if(!$updateOk || mysql_affected_rows($_pm['mysql']->getConn()) != 1) return false;
					if ($now > 0 && $lv < $petMaxLevel)
					{
						$bb = $_pm['mysql']->getOneRecord("SELECT *
													 FROM userbb
													WHERE id={$bb['id']} and uid={$bb['uid']}
												 ");
						return $this->saveGetOther($bb, $now);
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
				die('经验值无效！');
			}
			$updateOk = $_pm['mysql']->query("UPDATE userbb
						   SET nowexp=nowexp+{$exp}
						 WHERE id={$bb['id']} and uid={$bb['uid']}
					  ");
			if(!$updateOk || mysql_affected_rows($_pm['mysql']->getConn()) != 1) return false;
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
		$type = is_scalar($type) ? intval($type) : 0;
		$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		if($uid < 1) return false;
		if(!is_array($user)) $user = $_pm['user']->getUserById($uid);
		if(!is_array($user) || !isset($user['maxbag'])) return false;
		$maxbag = intval($user['maxbag']);
		if($maxbag < 1) return false;
		$stackTradeCondition = $flagTrade ? 'cantrade=1' : '(cantrade IS NULL OR cantrade=0)';
		$bagCount = $_pm['mysql']->getOneRecord("SELECT count(*) cnt FROM userbag WHERE uid=$uid AND sums>0 AND zbing=0");
		$l = (is_array($bagCount) && isset($bagCount['cnt'])) ? intval($bagCount['cnt']) : 0;

		/*$l=0;
		if (is_array($bag))
		{
			foreach ($bag as $x => $y)
			{
				if ($y['sums']>0 && $y['zbing']==0) $l++;
			}
		}
		if ($l >= $user['maxbag']) return false;*/

		$arr = explode(',', $idlist);
		$validItems = array();
		$newStackable = array();
		$needSlots = 0;
		foreach ($arr as $k => $v)
		{
			$v = intval($v);
			if($v < 1) return false;
			$propsInfo = getBasePropsInfoById($v);
			if(!is_array($propsInfo)) return false;
			if(!isset($propsInfo['sell'])) $propsInfo['sell'] = 0;
			if(!isset($propsInfo['vary'])) $propsInfo['vary'] = 0;
			$propsSell = intval($propsInfo['sell']);
			$propsVary = intval($propsInfo['vary']);
			if($propsVary == 1)
			{
				$rs = $_pm['mysql']->getOneRecord("SELECT id FROM userbag WHERE uid=$uid and pid={$v} and vary=1 and zbing=0 and sums>0 and {$stackTradeCondition} ORDER BY id ASC LIMIT 1 FOR UPDATE");
				if(!is_array($rs) && !isset($newStackable[$v]))
				{
					$needSlots++;
					$newStackable[$v] = 1;
				}
			}
			else
			{
				$needSlots++;
			}
			$validItems[] = array('pid'=>$v,'sell'=>$propsSell,'vary'=>$propsVary);
		}
		if($l + $needSlots > $maxbag) return false;



		foreach ($validItems as $k => $item)
		{
			$v = $item['pid'];
			$propsSell = $item['sell'];
			$propsVary = $item['vary'];
			$checkarr = array(1,1384,1206,920,921,922,1059,1060,1061,873,874,875,876,911,915,916,917,1048,1049,1050,1541,1648);
			if(!empty($type) && !in_array($type,$checkarr))
			{
				$tis = time();
				$sql = "INSERT INTO libao (pname,flag,cet,nums) values ({$v},{$type},{$tis},1)";
				$_pm['mysql'] -> query($sql);
			}
			$rs = false;
			if($propsVary == 1)
			{
				$rs = $_pm['mysql']->getOneRecord("SELECT id FROM userbag WHERE uid=$uid and pid={$v} and vary=1 and zbing=0 and sums>0 and {$stackTradeCondition} ORDER BY id ASC LIMIT 1 FOR UPDATE");
			}

			if (is_array($rs))
			{
				if(!isset($rs['id'])) return false;
				$tt = time();
				if(!$_pm['mysql']->query("UPDATE userbag
							   SET sums=sums+1,
								   stime={$tt}
								 WHERE id={$rs['id']} AND uid=$uid and pid={$v} and vary=1 and zbing=0 and sums <= 2147483646 and {$stackTradeCondition}
						  ")) return false;
				if(mysql_affected_rows($_pm['mysql']->getConn()) != 1) return false;
			}
			else{
				if ($l >= $maxbag) return false;

				if(!$_pm['mysql']->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime"
				.($flagTrade?",cantrade":"").
				")
							VALUES(
								   '{$uid}',
								   '{$v}',
								   '{$propsSell}',
								   '{$propsVary}',
								   '1',
								   unix_timestamp()"
							.($flagTrade?",1":"")."
								  );
						  ")) return false;
				if(mysql_affected_rows($_pm['mysql']->getConn()) != 1) return false;
				$l++;
			}
			unset($rs);
		}
		return true;
	}


	function saveGetPropsInsertRows($values)
	{
		global $_pm;
		if(!is_array($values) || count($values) < 1) return false;
		$firstId = 0;
		$batch = array();
		foreach($values as $value)
		{
			$batch[] = $value;
			if(count($batch) >= 100)
			{
				$insertId = $this->saveGetPropsInsertBatch($batch);
				if($insertId === false) return false;
				if($firstId < 1) $firstId = intval($insertId);
				$batch = array();
			}
		}
		if(count($batch) > 0)
		{
			$insertId = $this->saveGetPropsInsertBatch($batch);
			if($insertId === false) return false;
			if($firstId < 1) $firstId = intval($insertId);
		}
		return $firstId > 0 ? $firstId : true;
	}

	function saveGetPropsInsertBatch($values)
	{
		global $_pm;
		if(!is_array($values) || count($values) < 1) return false;
		$sql = "INSERT INTO userbag(uid,pid,sell,vary,sums,stime) VALUES ".implode(',',$values);
		if(!$_pm['mysql']->query($sql)) return false;
		if(mysql_affected_rows($_pm['mysql']->getConn()) != count($values)) return false;
		return intval($_pm['mysql']->last_id());
	}

	// save multiple copies of one props
	/**
	* @Return:  true of false
	*/
    function saveGetPropsMore($idlist, $num, $type = 0, $uid = 0,$propsrs=null)
    {
		global $_pm;
		$uid = intval($uid);
		if($uid < 1) $uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		$pid = intval($idlist);
		$num = intval($num);
		if($uid < 1 || $pid < 1 || $num < 1) return false;

		$userInfo = $_pm['user']->getUserById($uid);
		if(!is_array($userInfo) || !isset($userInfo['maxbag']) || intval($userInfo['maxbag']) < 1) return false;

		$propsInfo = $propsrs;
		if(!is_array($propsInfo) || !isset($propsInfo['id']) || intval($propsInfo['id']) != $pid){
			$propsInfo = getBasePropsInfoById($pid);
		}
		if(!is_array($propsInfo)) return false;
		if(!isset($propsInfo['vary'])) $propsInfo['vary'] = 0;
		if(!isset($propsInfo['sell'])) $propsInfo['sell'] = 0;

		$vary = intval($propsInfo['vary']);
		$sell = intval($propsInfo['sell']);
		$now = time();
		if($vary == 1){
			$bagRow = $_pm['mysql']->getOneRecord("SELECT id FROM userbag WHERE uid=$uid AND pid=$pid AND vary=1 AND zbing=0 AND sums>0 AND (cantrade IS NULL OR cantrade=0) ORDER BY id LIMIT 1 FOR UPDATE");
			if(is_array($bagRow)){
				if(!isset($bagRow['id'])) return false;
				$bagId = intval($bagRow['id']);
				$sql = "UPDATE userbag SET sums=sums+$num,stime=$now WHERE id=$bagId AND uid=$uid AND pid=$pid AND vary=1 AND zbing=0 AND sums>0 AND sums <= 2147483647-$num AND (cantrade IS NULL OR cantrade=0)";
				if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) return false;
				return true;
			}
			$neededSlots = 1;
		}else{
			$neededSlots = $num;
		}

		$bagCount = $_pm['mysql']->getOneRecord("SELECT count(*) cnt FROM userbag WHERE uid=$uid AND sums>0 AND zbing=0");
		if(!is_array($bagCount) || !isset($bagCount['cnt'])) return false;
		if(intval($bagCount['cnt']) + $neededSlots > intval($userInfo['maxbag'])) return "200";

		$values = array();
		if($vary == 1){
			$values[] = "($uid,$pid,$sell,$vary,$num,$now)";
		}else{
			for($i=0;$i<$num;$i++){
				$values[] = "($uid,$pid,$sell,$vary,1,$now)";
			}
		}
		if($this->saveGetPropsInsertRows($values) === false) return false;
		return true;
    }

	function saveGetPropsMore_return($idlist,$num,$type = 0,$uid=0)
	{
		global $_pm;
		$uid = intval($uid);
		if($uid < 1) $uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		$pid = intval($idlist);
		$num = intval($num);
		$type = intval($type);
		if($uid < 1 || $pid < 1 || $num < 1) return false;

		$userInfo = $_pm['user']->getUserById($uid);
		$propsInfo = getBasePropsInfoById($pid);
		if(!is_array($userInfo) || !isset($userInfo['maxbag']) || !is_array($propsInfo) || intval($userInfo['maxbag']) < 1) return false;
		if(!isset($propsInfo['vary'])) $propsInfo['vary'] = 0;
		if(!isset($propsInfo['sell'])) $propsInfo['sell'] = 0;

		$vary = intval($propsInfo['vary']);
		$sell = intval($propsInfo['sell']);
		$now = time();
		if($vary == 1){
			$bagRow = $_pm['mysql']->getOneRecord("SELECT id FROM userbag WHERE uid=$uid AND pid=$pid AND vary=1 AND zbing=0 AND sums>0 AND (cantrade IS NULL OR cantrade=0) ORDER BY id LIMIT 1 FOR UPDATE");
			if(is_array($bagRow)){
				if(!isset($bagRow['id'])) return false;
				$bagId = intval($bagRow['id']);
				$sql = "UPDATE userbag SET sums=sums+$num,stime=$now WHERE id=$bagId AND uid=$uid AND pid=$pid AND vary=1 AND zbing=0 AND sums>0 AND sums <= 2147483647-$num AND (cantrade IS NULL OR cantrade=0)";
				if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) return false;
				return $bagId;
			}
			$neededSlots = 1;
		}else{
			$neededSlots = $num;
		}

		$bagCount = $_pm['mysql']->getOneRecord("SELECT count(*) cnt FROM userbag WHERE uid=$uid AND sums>0 AND zbing=0");
		if(!is_array($bagCount) || !isset($bagCount['cnt'])) return false;
		if(intval($bagCount['cnt']) + $neededSlots > intval($userInfo['maxbag'])) return "200";

		$values = array();
		if($vary == 1){
			$values[] = "($uid,$pid,$sell,$vary,$num,$now)";
		}else{
			for($i=0;$i<$num;$i++) $values[] = "($uid,$pid,$sell,$vary,1,$now)";
		}
		$bagId = intval($this->saveGetPropsInsertRows($values));
		if($bagId < 1) return false;

		$checkarr = array(1,1384,1206,920,921,922,1059,1060,1061,873,874,875,876,911,915,916,917,1048,1049,1050,1541,1648);
		if($type > 0 && !in_array($type,$checkarr)){
			$sql = "INSERT INTO libao (pname,flag,cet,nums) values ($pid,$type,$now,$num)";
			if(!$_pm['mysql']->query($sql)) return false;
		}
		return $bagId;
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
		$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		if($uid < 1) return false;
		if(!is_array($user)) $user = array();
		if(!is_array($taskinfo)) return false;
		$taskDefaults = array('id' => 0, 'fromnpc' => '', 'limitlv' => '', 'okneed' => '');
		foreach($taskDefaults as $key => $value)
		{
			if(!isset($taskinfo[$key])) $taskinfo[$key] = $value;
		}
		$taskinfo['id'] = intval($taskinfo['id']);
		$userDefaults = array('mbid' => 0, 'maxbag' => 30, 'paihang' => 0, 'tasklog' => '', 'score' => 0, 'prestige' => 0, 'money' => 0, 'vip' => 0, 'active_score' => 0);
		foreach($userDefaults as $key => $value)
		{
			if(!isset($user[$key])) $user[$key] = $value;
		}
		if(!taskScheduleIsActive($taskinfo)) return false;
		$petsAll	= $_pm['user']->getUserPetById($uid);
		if(!is_array($petsAll)) $petsAll = array();
		$fromnpc = explode("|",$taskinfo['fromnpc']);
		if(!isset($fromnpc[0]) || $fromnpc[0] === '') $fromnpc[0] = 0;


		$limit = explode(",",$taskinfo['limitlv']);
		$requestNpc = (isset($_REQUEST['n']) && !is_array($_REQUEST['n'])) ? intval($_REQUEST['n']) : 0;
		if(preg_match("/see\:(\d+)/",$taskinfo['okneed'],$out))
		{
			$requestNpc = intval($out[1]);
		}
		$sql = "select * from task_accept where taskid=".$taskinfo['id']." and uid={$uid}";
		//echo $sql;
		$arr_task = $_pm['mysql'] -> getOneRecord($sql);
		$user['tasklog'] = ',see:'.$requestNpc;		//记录任务完成度
		$acceptState = (is_array($arr_task) && isset($arr_task['state'])) ? $arr_task['state'] : '';
		$user['tasklog'] .= ','.$acceptState;

		//echo $user['tasklog']."<br /><br />";
		$need = explode(',', $taskinfo['okneed']);// Check task whether complete.
		if (is_array($need))
		{
			$i = 0;
			foreach($need as $x => $y)
			{
				if (!empty($y)&&strpos($user['tasklog'], $y) === false) //,see:5
				{//echo __LINE__."<BR />";echo $i."<br />";
					$arr = explode(':', $y);
					if(!isset($arr[0]) || $arr[0] === '') continue;
					if($arr[0] === 'giveitm') $arr[0] = 'giveitem';
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
						if(!isset($arr[1]))
						{
							$checks = 16;
							break;
						}
						$arrm = explode("|",$arr[1]);
						$bname = '';
						$blevel = 0;
						$bwx = 0;
						$petsAll = $_pm['user']->getUserPetById($uid);
						if(!is_array($petsAll)) $petsAll = array();
						foreach($petsAll as $pet)
						{
							if(!is_array($pet)) continue;
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
							$bbs = kdjlSafeMemValue($_pm['mem']->get(MEM_BB_KEY), array());
							if(!is_array($bbs)) $bbs = array();
							$bnamearr = array();
							foreach($arrm as $v)
							{
								foreach($bbs as $bv)
								{
									if(!is_array($bv)) continue;
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

		$needs = array();
		foreach($need as $n)
		{
			if($n === '') continue;
			$arr = explode(":",$n);
			if(!isset($arr[0]) || $arr[0] === '') continue;
			if(!isset($arr[1])) $arr[1] = 0;
			if($arr[0] != 'givejifen' && $arr[0] != 'giveww' && $arr[0] != 'lv' && $arr[0] != 'givemoney' && $arr[0] != 'monself' && $arr[0] != 'no' && $arr[0] != 'givevip' && $arr[0] != 'givedianjuan' && $arr[0] != 'paihang' && $arr[0] != 'giveml')
			{
				$needs[] = $n;
			}
			else
			{
				if($arr[0] == "givejifen")
				{
					$user['score'] -= intval($arr[1]);
				}
				else if($arr[0] == 'giveww')
				{
					$user['prestige'] -= intval($arr[1]);
				}
				else if($arr[0] == "givemoney")
				{
					$user['money'] -= intval($arr[1]);
				}
				else if($arr[0] == "givevip")
				{
					$user['vip'] -= intval($arr[1]);
				}else if($arr[0] == "giveml")
				{
					$ml = intval($arr[1]);
				}
				else if($arr[0] == 'givedianjuan')
				{
					$user['active_score'] -= intval($arr[1]);
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
					if(!isset($limitarr[0]) || $limitarr[0] === '') continue;
					if($limitarr[0] == "cishu")
					{
						if(count($limitarr) < 3)
						{
							$checks = 16;
							break;
						}
						$today = strtotime(date('Ymd',time())) - (max(1,intval($limitarr[2])) - 1) * 24 * 3600;
						$sql = "SELECT count(*) dif FROM tasklog WHERE uid = {$uid} and taskid = {$taskinfo['id']} and time > {$today}";
						$arr = $_pm['mysql'] -> getOneRecord($sql);
						if(is_array($arr))
						{
							if(!isset($arr['dif'])) $arr['dif'] = 0;
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
							}
						}
						else
						{
							$checks = 16;
							break;
						}
					}else if($limitarr[0] == "timelimit"){
						if(!isset($limitarr[1]))
						{
							$checks = 16;
							break;
						}
						$t = $_pm['mysql'] -> getOneRecord('SELECT time FROM task_accept WHERE uid = '.$uid.' AND taskid = '.$taskinfo['id']);
						if(!is_array($t) || !isset($t['time']))
						{
							$checks = 16;
							break;
						}
						$nowtime = time();
						$c = ($nowtime - $t['time']) - $limitarr[1] * 3600;
						if($c > 0){
							$checks = 16;
							break;
						}
					}
					elseif($limitarr[0] == "xfsj"){
						if(!isset($limitarr[1]))
						{
							$checks = 16;
							break;
						}
						$this->xfsj=$limitarr[1];
					}
					else if($limitarr[0] == "paihang")
					{
						if(!isset($limitarr[1]))
						{
							$checks = 16;
							break;
						}
						if($user['paihang'] != $limitarr[1])
						{
							//die("您不能完成此任务！");
							$checks = 16;
							break;
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
