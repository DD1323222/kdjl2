<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.05.22
*@Usage: 查询任务进度及显示任务相关的所有信息。
*@Note: none
*/
require_once('../config/config.game.php');

secStart($_pm['mem']);

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');
$user		= $_pm['user']->getUserById($uid);
$task = $_pm['mem']->get(MEM_TASK_KEY);
if(!is_array($task)) $task = kdjlSafeMemValue($task, array());
if(!is_array($user)) $user = array();
if(!is_array($task)) $task = array();
$taskid = isset($user['task']) ? intval($user['task']) : 0;
$taskitem = isset($task[$taskid]) ? $task[$taskid] : false;
/*$taskitem	= $_pm['mem']->dataGet(array('k'	=>	MEM_TASK_KEY,
										 'v'	=>	"if(\$rs['id']== '{$user['task']}') \$ret=\$rs;"
									));*/

$props = $_pm['mem']->get('db_propsid');
if(!is_array($props)) $props = kdjlSafeMemValue($props, array());
if(!is_array($props)) $props = array();

$_gpc = $_pm['mem']->get(MEM_GPC_KEY);
if(!is_array($_gpc)) $_gpc = kdjlSafeMemValue($_gpc, array());
if(!is_array($_gpc)) $_gpc = array();
$gpcNames = array();
foreach($_gpc as $gpcRow)
{
	if(!is_array($gpcRow) || !isset($gpcRow['id']) || !isset($gpcRow['name'])) continue;
	$gpcId = intval($gpcRow['id']);
	if($gpcId > 0 && !isset($gpcNames[$gpcId])) $gpcNames[$gpcId] = $gpcRow['name'];
}
$str = '';
$strs = '';
$log = '';

function taskItemNameFromMap($rows, $id)
{
	return (isset($rows[$id]) && is_array($rows[$id]) && isset($rows[$id]['name']) && $rows[$id]['name'] !== '') ? $rows[$id]['name'] : $id;
}

function taskItemMonsterNames($ids, $names)
{
	if(!is_array($ids)) $ids = array($ids);
	$ret = array();
	foreach($ids as $id)
	{
		$id = intval($id);
		if($id < 1) continue;
		$name = isset($names[$id]) && $names[$id] !== '' ? $names[$id] : strval($id);
		if(!in_array($name, $ret, true)) $ret[] = $name;
	}
	return implode('、', $ret);
}

if(!is_array($taskitem))
{
	echo "还没有接受任何任务！";
}
else
{
	$needarr = neednpc(isset($taskitem['okneed']) ? $taskitem['okneed'] : '');
	$fromnpc = explode("|", isset($taskitem['fromnpc']) ? $taskitem['fromnpc'] : '0');
	$fromNpcName = isset($_task['oknpc'][$fromnpc[0]]) ? $_task['oknpc'][$fromnpc[0]] : '';
	$oknpc = isset($taskitem['oknpc']) ? $taskitem['oknpc'] : 0;
	$okNpcName = isset($_task['oknpc'][$oknpc]) ? $_task['oknpc'][$oknpc] : '';
	$str .= '任务接受NPC：<u>'. $fromNpcName.'</u><br/>';
	$str .= '任务完成NPC：<u>'. $okNpcName.'</u><br/>';
	if(is_array($needarr))
	{
		foreach($needarr as $k => $v)
		{
			switch($k)
			{
				case "item":
					foreach($v as $item)
					{
						foreach($item as $ik => $iv)
						{
							$propId = isset($iv[0]) ? intval($iv[0]) : 0;
							$par = array('name' => taskItemNameFromMap($props, $propId));
							$strs .= "收集".$par['name']."&nbsp;".$ik."个<br />";
						}
					}
					break;
				case "money":
					$strs .= "需要金币：".$v[0]."个<br />";
					break;
				case "jifen":
					$strs .= "需要积分：".$v[0]."个<br />";
					break;
				case "ww":
					$strs .= "需要威望：".$v[0]."点<br />";
					break;
				case "lv":
					$lvarr = explode("|", isset($v[0]) ? $v[0] : '0|0');
					if(!isset($lvarr[0])) $lvarr[0] = 0;
					$maxLv = isset($lvarr[1]) ? intval($lvarr[1]) : 0;
					if($maxLv == 0)
					{
						$strs .= "需要等级：".$lvarr[0]."级以上<br />";
					}
					else
					{
						$strs .= "需要等级：".$lvarr[0]."-".$lvarr[1]."级<br />";
					}
					break;
				case "killmon":
					foreach($v as $kss => $kill)
					{
						$str1 = taskItemMonsterNames($kill, $gpcNames);
						$gpcnum = explode(",",$kss);
						$strs .= "杀死怪物:".$str1."&nbsp;".(isset($gpcnum[0]) ? $gpcnum[0] : 0)."个<br />";
					}
					break;
			}
		}
		$str .= "任务目标：<br />".$strs."<br /><hr><br />";
	}
	if(!empty($user['tasklog']))
	{
		$arr = neednpc($user['tasklog']);
		if(!is_array($arr)) $arr = array();
		foreach($arr as $k => $v)
		{
			switch($k)
			{
				case "item":
					foreach($v as $item)
					{
						foreach($item as $ik => $iv)
						{
							$propId = isset($iv[0]) ? intval($iv[0]) : 0;
							$pa = array('name' => taskItemNameFromMap($props, $propId));
							$log .= "收集".$pa['name']."&nbsp;".$ik."个<br />";
							/*foreach($props as $p)
							{
								if($iv[0] == $p['id'])
								{
									$log .= "收集".$p['name']."&nbsp;".$ik."个<br />";
								}
							}*/
						}
					}
					break;
				case "money":
					$log .= "需要金币：".$v[0]."个<br />";
					break;
				case "ww":
					$log .= "需要威望：".$v[0]."点<br />";
					break;
				case "lv":
					$lvarr = explode("|", isset($v[0]) ? $v[0] : '0|0');
					if(!isset($lvarr[0])) $lvarr[0] = 0;
					$maxLv = isset($lvarr[1]) ? intval($lvarr[1]) : 0;
					if($maxLv == 0)
					{
						$log .= "需要等级：".$lvarr[0]."级以上<br />";
					}
					else
					{
						$log .= "需要等级：".$lvarr[0]."-".$lvarr[1]."级<br />";
					}
					break;
				case "killmon":
					foreach($v as $kss => $kill)
					{
						$str1 = taskItemMonsterNames($kill, $gpcNames);
						$gpcnum = explode(",",$kss);
						$log .= "杀死怪物:".$str1."&nbsp;".(isset($gpcnum[0]) ? $gpcnum[0] : 0)."个<br />";
					}
					break;
			}
		}
		$str .= "当前杀怪进度：<br />".$log;
	}
	else{
		$str .= "当前杀怪进度为0";
	}
}
if (!empty($str)) {
	echo $str;
}
/*$taskresult = '任务：'.$taskitem['title'].'<br/><hr style="height:1px;border:1px solid green">';

if (is_array($taskitem) && $taskitem['okneed'] != '')
{
	$taskresult .= '任务接受NPC：<u>'. $_task['npc'][$taskitem['fromnpc']].'</u><br/>';

	$arr = explode(',', $taskitem['okneed']);
	foreach($arr as $k => $v)
	{
		$tarr = explode(':', $v);
		if ($tarr[0] == "see")
		{
			$taskresult .= "需要拜访：<u>" . $_task['npc'][$tarr[1]].'</u>';
		}
		else if($tarr[0] == "killmon")
		{
			$t1 = explode('|', $tarr[1]);
			$grs = $_pm['mem']->dataGet(array('k'	=>	MEM_GPC_KEY,
												'v'	=>	"if(\$rs['id']== '{$t1[0]}') \$ret=\$rs;"
										));
			$taskresult .= " <br/>需要打败： <u>".$grs['name']." ".$tarr[2]."</u> 个";
			unset($t1);
		}
	    else if($tarr[0] == "giveitem")	// 1=>id, 2=>num
		{
			$idlist = str_replace('|',',',$tarr[1]);
			$all = $_pm['mysql']->getRecords("SELECT name
												FROM props
											   WHERE id in({$idlist})
											");
			if(!is_array($all)) $all = array();
			$wplist = '';
			foreach($all as $key => $value)
			{
				$wplist = $wplist?', '.$value['name']:$value['name'];
			}

			$taskresult .= " <br/><u>需要收集物品： {$wplist} 中任何 ".$tarr[2]."</u> 个";
		}
	}
	// end npcl
	$taskresult .= '<br/>任务完成NPC：<u>'.$_task['npc'][$taskitem['oknpc']].'</u>';
	//had part.
	if ($user['tasklog'] == '') $taskresult .= '<br/><hr style="height:1px;border:1px solid green">完成情况：未完成。';
	else
	{
		$hdtask = explode(',',$user['tasklog']);
		$taskresult .= '<br/><hr style="height:1px;border:1px solid green">完成情况：';
		foreach ($hdtask as $k => $v)
		{
			if ($v == '') continue;
			else
			{
				$arr = explode(':', $v);
				if ($arr[0] == "see")
					$taskresult .= "拜访 <u>" . $_task['npc'][$arr[1]] . '</u> 完成';
				else if ($arr[0] == "killmon")
				{
					$t1 = explode('|', $arr[1]);
					$grs = $_pm['mem']->dataGet(array('k'	=>	MEM_GPC_KEY,
												'v'	=>	"if(\$rs['id']== '{$t1[0]}') \$ret=\$rs;"
										));
					$taskresult .= "<br/>已打败 <u>" . $grs['name'] . " " . $arr[2] . '</u> 个';
				}
				else if ($arr[0] == "giveitem")
				{
					$taskresult .= "<br/>已收集 <u>" . $arr[2] . '</u> 个';
				}
			}
		}

	}
	unset($grs);

	echo $taskresult;
}
else echo "还没有任务信息!";*/

$_pm['mem']->memClose();
?>
