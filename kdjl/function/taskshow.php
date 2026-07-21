<?php
ini_set('display_errors', false);
set_time_limit(60);
require_once('../config/config.game.php');
$m = $_pm['mem'];
$u = $_pm['user'];
//secStart($m);

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');

function taskshowHtml($value)
{
	return htmlspecialchars(strval($value), ENT_QUOTES);
}

function taskshowJsSingle($value)
{
	return str_replace(array('\\', "'", "\r", "\n", '<', '>'), array('\\\\', "\\'", '', '', '\\x3C', '\\x3E'), strval($value));
}

function taskshowImagePath($value)
{
	$value = trim(strval($value));
	if($value === '' || !preg_match('/^[A-Za-z0-9_\/.-]+$/D', $value) ||
		preg_match('#(?:^|/)\.\.(?:/|$)#', $value)) return '';
	$value = preg_replace('#^(?:\./)+#', '', $value);
	return '/'.ltrim($value, '/');
}

function taskshowNormalizeTask($task)
{
	if(!is_array($task)) return false;
	$defaults = array(
		'id' => 0,
		'title' => '',
		'fromnpc' => '',
		'oknpc' => 0,
		'cid' => '',
		'xulie' => 0,
		'hide' => 0,
		'limitlv' => '',
		'paihang' => 0
	);
	foreach($defaults as $key => $value)
	{
		if(!isset($task[$key])) $task[$key] = $value;
	}
	$task['id'] = intval($task['id']);
	$task['oknpc'] = intval($task['oknpc']);
	$task['xulie'] = intval($task['xulie']);
	$task['hide'] = intval($task['hide']);
	return $task;
}

function taskshowAddTitleTask(&$titleSmallNext, $task)
{
	$task = taskshowNormalizeTask($task);
	if($task === false || $task['id'] < 1) return;
	$titleSmall = explode('|', $task['fromnpc']);
	$key = isset($titleSmall[1]) ? intval($titleSmall[1]) : 0;
	if($key < 1) $key = $task['id'];
	$titleSmallNext[$key] = $task;
}

$user		= $u->getUserById($uid);
$userBag	= $u->getUserBagById($uid);
$petsAll	= $_pm['user']->getUserPetById($uid);
if(!is_array($user)) $user = array('mbid' => 0);
if(!is_array($userBag)) $userBag = array();
if(!is_array($petsAll)) $petsAll = array();
if(!isset($user['mbid'])) $user['mbid'] = 0;
if(!isset($user['paihang'])) $user['paihang'] = 0;
$bbs = kdjlSafeMemValue($_pm['mem']->get(MEM_BB_KEY), array());
$memtask = kdjlSafeMemValue($_pm['mem']->get(MEM_TASK_KEY), array());
if(!is_array($bbs)) $bbs = array();
if(!is_array($memtask)) $memtask = array();

$bid = (isset($_REQUEST['bid']) && !is_array($_REQUEST['bid'])) ? intval($_REQUEST['bid']) : 0;
$title_vary = (isset($_REQUEST['title_vary']) && !is_array($_REQUEST['title_vary'])) ? intval($_REQUEST['title_vary']) : 1;
$page = (isset($_REQUEST['page']) && !is_array($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
$taskid = 0;

$tsk = new task();
foreach($petsAll as $k=>$pv)
{
	if(!is_array($pv))
	{
		unset($petsAll[$k]);
		continue;
	}
	if(!isset($pv['id'])) $pv['id'] = 0;
	if(!isset($pv['level'])) $pv['level'] = 0;
	if(!isset($pv['czl'])) $pv['czl'] = 0;
	if($pv['id'] != $user['mbid'])
	{
		unset($petsAll[$k]);
		continue;
	}
	$petsAll[$k] = $pv;
}
if($title_vary == 1)//显示大类以及默认接受任务内容
{
	$title = array();
	$task_accept = $_pm['mysql']->getRecords("select * from task_accept where uid = {$uid} order by id asc");
	$title_arr = ' ';
	//左边任务大标题显示
	$taskVaryTypes = (isset($_task['varytype']) && is_array($_task['varytype'])) ? $_task['varytype'] : array();
	foreach($taskVaryTypes as $key => $value)
	{
		$key = intval($key);
		$valueHtml = taskshowHtml($value);
		if($key == 1)
		{
			$title_arr .= '<ul class="lev"><li id="task'.$key.'" class="on" onClick="setTab(\'task\','.$key.',12)"><a style="cursor:pointer"onclick="getTaskDetail(\''.$key.'\');bid='.$key.';void(0);" ><p>'.$valueHtml.'</p></a></li></ul>';
			$title_arr .= '<ul id="con_task_'.$key.'" class="con"></ul>';
		}
		else if($key == 2)
		{
			$title_arr .= '<ul class="lev"><li id="task'.$key.'" onClick="setTab(\'task\','.$key.',12)"><a style="cursor:pointer"onclick="getTaskDetail(\''.$key.'\');bid='.$key.';void(0);" ><p onclick="taskASwap(this)">'.$valueHtml.'</p></a></li></ul>';
			$title_arr .= '<ul id="con_task_'.$key.'" class="con hiden"></ul>';
		}
		else
		{
			$title_arr .= '<ul class="lev"><li id="task'.$key.'" onClick="setTab(\'task\','.$key.',12)"><a style="cursor:pointer"onclick="getTaskDetail(\''.$key.'\');bid='.$key.';void(0);" ><p onclick="taskASwap(this)">'.$valueHtml.'</p></a></li></ul>';
			$title_arr .= '<ul id="con_task_'.$key.'" class="con hiden"></ul>';
		}
	}
	$title_arr .= "@@@@";//以下为活动显示
	$active_content = "";
	$week = date('N',time());
	$activeRows = $_pm['mysql']->getRecords("select * from system_activity where week between 1 and 7 order by id");
	$active = array();
	if(is_array($activeRows))
	{
		foreach($activeRows as $activeRow)
		{
			$timeParts = explode('|', trim((string)$activeRow['time']), 2);
			if(count($timeParts) === 2 && clockTimeToMinutes($timeParts[0]) !== false && clockTimeToMinutes($timeParts[1]) !== false)
			{
				if(!isWeeklyDayTimeActive($activeRow['week'], $timeParts[0], $timeParts[1], $week)) continue;
			}
			else if(!weeklyDaysContain($activeRow['week'], $week)) continue;
			$active[] = $activeRow;
		}
	}

	$title_arr .= '<ul>';
	if(count($active) > 0)
	{
		$j = 1;
		$sum = count($active);
		$kong = max(0, 4-$sum);
		foreach($active as $ac_key => $ac_value)
		{
			if(is_array($ac_value))
			{
				if(!isset($ac_value['title'])) $ac_value['title'] = '';
				if(!isset($ac_value['time'])) $ac_value['time'] = '';
				if(!isset($ac_value['pic'])) $ac_value['pic'] = '';
				$activeTitleJs = taskshowJsSingle($ac_value['title']);
				$activeTimeJs = taskshowJsSingle($ac_value['time']);
				$activePicHtml = taskshowHtml(taskshowImagePath($ac_value['pic']));
				$title_arr .= '<li><a style="cursor:pointer"onclick="void(0)"><span onmouseover="javascript:showcontent_ac(\''.$activeTitleJs.'\',\''.$activeTimeJs.'\',\'active'.$j.'\',event);" onmouseout="javascript:closecontent();" id=active_'.$j.'><img src="'.$activePicHtml.'"></span></a></li>';
				$j++;
			}
		}
		for($i=0;$i<$kong;$i++)
		{
			$title_arr .= ' <li><a class="activity_empty" style="cursor:default" onclick="void(0)"><span>暂无</span><span>活动</span></a></li>';
		}

	}
	else
	{
		for($i=0;$i<4;$i++)
		{
			$title_arr .= ' <li><a class="activity_empty" style="cursor:default" onclick="void(0)"><span>暂无</span><span>活动</span></a></li>';
		}
	}
	$title_arr .= ' <li id="date"></li>';
	$title_arr .= '</ul>';

	echo $title_arr;
}
if($title_vary == 2)//显示每一个大类下面的任务
{
	//右边各级任务小标题显示

	$task_details = $_pm['mysql']->getRecords("select * from task where color = {$bid}");
	if(!is_array($task_details)) $task_details = array();
	$task_all = array();
	$title_small_next = array();
	$user_task_array = array(0);
	$title_details = '';

	foreach($task_details as $key => $key_v)//以ID为KEY 的任务数组
	{
		$key_v = taskshowNormalizeTask($key_v);
		if($key_v === false || $key_v['id'] < 1)
		{
			unset($task_details[$key]);
			continue;
		}
		$task_details[$key] = $key_v;
		$task_all[$key_v['id']] = $key_v;
	}
	//查询出已接的任务
	$user_task = $_pm['mysql']->getRecords("select * from task_accept where uid = {$uid}");

	if(is_array($user_task))
	{
		foreach($user_task as $user_task_key => $user_task_value)
		{
			if(is_array($user_task_value) && isset($user_task_value['taskid']))
			{
				$user_task_array[] = intval($user_task_value['taskid']);
			}
		}
	}
	//$title_details .=  '<ul class="list l2">';
	$nowtime = date("YmdHis");
	$timearr = kdjlSafeMemValue($_pm['mem']->get(MEM_TIME_KEY), array());
	$taskArr = array();
	$rwlidarr = array();
	if(count($task_details) > 0)
	{
		foreach($task_details as $v_key => $v)//color 的任务
		{
			if(!taskScheduleIsActive($v, $timearr, $nowtime))
			{
				continue;
			}
			//是否可见条件判断
			if(!empty($v['limitlv']))
			{
				$limitarr = explode(",",$v['limitlv']);
				if(is_array($limitarr))
				{
					$flag=false;
					foreach($limitarr as $vl)
					{
						$limitarrs = explode(":",$vl,2);
						if(count($limitarrs) < 2) continue;
						switch($limitarrs[0])
						{
							case "level"://等级限制
								$blv = 0;
								foreach($petsAll as $bb)
								{
									if($bb['id'] == $user['mbid'])
									{
										$blv = $bb['level'];
									}
								}
								if(empty($blv))
								{
									$flag=true;
								}
								$lvarr = explode("|",$limitarrs[1]);
								if(empty($lvarr[1]))
								{
									if($blv < $lvarr[0])
									{
										$flag=true;
									}
								}
								else
								{
									if($blv < $lvarr[0] || $blv > $lvarr[1])
									{
										$flag=true;
									}
								}
								break;

							case "czl"://成长限制
								$bbczl = 0;
								foreach($petsAll as $bb)
								{
									if($bb['id'] == $user['mbid'])
									{
										$bbczl = $bb['czl'];
									}
								}
								if(empty($bbczl))
								{
									$flag=true;
								}
								$lvarr = explode("|",$limitarrs[1]);
								if(empty($lvarr[1]))
								{
									if($bbczl < $lvarr[0])
									{
										$flag=true;
									}
								}
								else
								{
									if($bbczl < $lvarr[0] || $bbczl > $lvarr[1])
									{
										$flag=true;
									}
								}
								break;
						}
					}
					if($flag)
					{
						continue;
					}
				}
			}
			if(empty($v['cid']))
			{
				$sql = "SELECT taskid FROM tasklog WHERE uid = {$uid} and taskid = {$v['id']} AND taskid != 88888";
				$checkarr = $_pm['mysql'] -> getOneRecord($sql);
				if(is_array($checkarr))
				{
					continue;
				}
				else
				{
					taskshowAddTitleTask($title_small_next, $v);
				}
			}
			else
			{
				$cidarr = explode(":",$v['cid'],2);
				$cidtype = isset($cidarr[0]) ? $cidarr[0] : '';
				$cidvalue = isset($cidarr[1]) ? $cidarr[1] : '';
				if($cidtype == "rwl")
				{
					if(!empty($v['xulie']))
					{
						$arr = explode("|",$cidvalue);
						$fromTaskId = isset($arr[0]) ? intval($arr[0]) : 0;
						$toTaskId = isset($arr[1]) ? intval($arr[1]) : 0;
						if(!isset($rwlidarr[$v['xulie']]) || !is_array($rwlidarr[$v['xulie']]))
						{
							$rwlidarr[$v['xulie']] = array();
						}
						if($fromTaskId > 0 && !in_array($fromTaskId,$rwlidarr[$v['xulie']]))
						{
							$rwlidarr[$v['xulie']][] = $fromTaskId;
						}
						if($toTaskId > 0 && !in_array($toTaskId,$rwlidarr[$v['xulie']]))
						{
							$rwlidarr[$v['xulie']][] = $toTaskId;
						}
					}
				}
				else if ($cidtype == "paihang")
				{
					if($cidvalue != $user['paihang'])
					{
						continue;
					}
					else
					{
						taskshowAddTitleTask($title_small_next, $v);
					}
				}
				else
				{
					if($v['hide'] == 1 && $v['id'] != $taskid)
					{
						taskshowAddTitleTask($title_small_next, $v);
					}
				}
			}
		}
	}

	if(is_array($rwlidarr))
	{
		foreach($rwlidarr as $i=>$v)//任务链处理
		{
			if(!is_array($v)) continue;//18 22
			$mixed = array_intersect($user_task_array,$v);//做完一条删一条
			if(empty($mixed))//表示当前接的任务链都完成了，才显示，否则不显示  （1：当前接了任务链，完成了，2;当前没有接任务链）
			{
				$sql = "SELECT * FROM tasklog WHERE uid = {$uid} and xulie = ".intval($i);//检查以前有没有做过这个任务
				$result = $_pm['mysql'] -> getOneRecord($sql);
				if(is_array($result) && isset($result['taskid']))//当前在做这个任务链（显示下一条）或者曾经做过
				{
					if(!isset($task_all[$result['taskid']])) continue;
					$taskinfo = taskshowNormalizeTask($task_all[$result['taskid']]);
					if($taskinfo === false) continue;
					$cidarr = explode(":",$taskinfo['cid'],2);
					if(!isset($cidarr[1]) || $cidarr[0] != 'rwl') continue;
					$a = explode("|",$cidarr[1]);
				//	print_r($a)."<br />";

					if(empty($a[1]) || !is_numeric($a[1]))
					{
						continue;
					}
					$nextTaskId = intval($a[1]);
					$nextTask = isset($task_all[$nextTaskId]) ? taskshowNormalizeTask($task_all[$nextTaskId]) : false;
					if($nextTask === false || !taskScheduleIsActive($nextTask, $timearr, $nowtime))
					{
						continue;
					}
					//echo $taskinfo['fromnpc'];//2|1
					taskshowAddTitleTask($title_small_next, $nextTask);
					//print_r($title_small_next);
				}
				else//没做过此任务链，从第一条开始做。
				{
					foreach($task_details as $t)//$task_details 为此类别
					{
						if($t['xulie'] == $i && $t['hide'] == 1)
						{
							if(!taskScheduleIsActive($t, $timearr, $nowtime))
							{
								continue;
							}
							taskshowAddTitleTask($title_small_next, $t);
						}
					}
				}

			}
		}//for 循环结束
	}
	//print_r($title_small_next);
	$array = count($title_small_next) > 0 ? BubbleSort($title_small_next) : array();//按顺序排序


	if(is_array($array) && count($array) > 0)
	{
		//if(count($array)>50) die('--big array--');
		$title_details='';
		$ct=0;

		foreach($array as $keys => $values)//getTasks($taskid,&$user,&$petsAll,&$bbs,&$memtask)
		{
			$values = taskshowNormalizeTask($values);
			if($values === false) continue;
			//if(!in_array($values['id'],array(1132,1133,1136,1137)))continue;
			$ct++;
			//if($ct>13) die('--死循环--:'. memory_get_usage() );
			//if($ct>13) die(__LINE__.' --死循环--:'. memory_get_usage().print_r($values,1));
				if(empty($values)) continue;
				$taskTitleHtml = taskshowHtml(isset($values['title']) ? $values['title'] : '');
				$flagnum = '';
				$in=in_array($values['id'],$user_task_array);
				// 5.3
				$gt=getTasks($values,$user,$petsAll,$bbs);
				if($in&& $gt!==false)//此任务ID是否在已接任务ID 中
				{
					$check = "已接";//显示放弃按钮
				//	$flagnum = 2;
					$flagnum = 1;
					$npcnum = $values['oknpc'];
					$title_details .= '<li class="t"><a style="cursor:pointer"onclick="void(0)"><p onclick="taskASwap(this);javascript:OpenLogin('.$flagnum.','.$values['id'].','.$npcnum.',3)">'.$taskTitleHtml.'</p></a></li>';
				}else if(!$in && $gt!==false){
					$check = "可接";//显示接受按钮
					$flagnum = 1;
					$npcnum = $bid;
					$title_details .= '<li class="a"><a style="cursor:pointer"onclick="void(0)"><p onclick="taskASwap(this);javascript:OpenLogin('.$flagnum.','.$values['id'].','.$npcnum.',4)">'.$taskTitleHtml.'</p></a></li>';
				}else if(!$gt)
				{
					$flagnum = 1;
					$check = '不可接';//显示关闭按钮
					$npcnum = $bid;
					//$title_details .= '<li><p class="p1" onclick="javascript:OpenLogin('.$flagnum.','.$values['id'].','.$npcnum.',5)">'.$values['title'].'</p><span>'.$check.'</span></li>';
					$title_details .= '<li class="t"><a style="cursor:pointer"onclick="void(0)"><p onclick="taskASwap(this);javascript:OpenLogin('.$flagnum.','.$values['id'].','.$npcnum.',5)">'.$taskTitleHtml.'</p></a></li>';
				}
			//echo strlen($title_details)."<br/>\n";$title_details='';
			//if($ct>13) die(__LINE__.' --死循环--:'. memory_get_usage());
		}
		/*
die('len='.strlen($title_details));
		flush();
		ob_flush();
*/
	}
	else
	{
		$title_details .= '暂无任务';
	}
	$title_details .= '</ul>';
	echo $title_details;
}

if($title_vary == 3)//显示大类以及默认接受任务内容
{
	$task_accept = $_pm['mysql']->getRecords("select * from task_accept where uid = {$uid} order by id asc");
	$task_accept_arr = '';
	$task_accept_array = array();
	$state = array();
	if(is_array($task_accept))
	{
		foreach($task_accept as $task_accept_key => $task_accept_value)
		{
			if(!is_array($task_accept_value) || !isset($task_accept_value['taskid'])) continue;
			$taskidValue = intval($task_accept_value['taskid']);
			if($taskidValue < 1) continue;
			$task_accepttitle = $_pm['mysql']->getOneRecord("select * from task where id = {$taskidValue}");
			$task_accepttitle = taskshowNormalizeTask($task_accepttitle);
			if($task_accepttitle === false) continue;
			$task_accept_array[] =  $task_accepttitle;
			$state[$taskidValue] = isset($task_accept_value['state']) ? intval($task_accept_value['state']) : 0;
		}
		foreach($task_accept_array as $accept_key => $accept_value)//task
		{
					$accept_value = taskshowNormalizeTask($accept_value);
					if($accept_value === false) continue;
					$acceptTitleHtml = taskshowHtml(isset($accept_value['title']) ? $accept_value['title'] : '');
					$taskinfo = $accept_value;
					if($tsk->completeTaskShow($user, $taskinfo))
					{
						$accept = '可交';//屏蔽接受按钮
						//$ac = explode('|',$accept_value['fromnpc']);
						$task_accept_arr .= '<li class="u"><a style="cursor:pointer"onclick="void(0)"><p onclick="taskASwap(this);javascript:OpenLogin(2,'.$accept_value['id'].','.$accept_value['oknpc'].',1)">'.$acceptTitleHtml.'</p></a></li>';

					}
					else
					{
						$accept = '不可交';//只有一个放弃按钮
						//$ac = explode('|',$accept_value['fromnpc']);
						 $task_accept_arr .= '<li class="c"><a style="cursor:pointer"onclick="void(0)"><p onclick="taskASwap(this);javascript:OpenLogin(2,'.$accept_value['id'].','.$accept_value['oknpc'].',2)">'.$acceptTitleHtml.'</p></a></li>';
					}
		}
	}
	echo $task_accept_arr;
}










?>
