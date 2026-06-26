<?php
/**
@Usage: 获取任务信息详细。
*/

require_once('../config/config.game.php');
error_reporting(7);
secStart($_pm['mem']);
$m = $_pm['mem'];
$n = intval($_REQUEST['n']);
if ($n<1) die('');

$user = $_pm['user']->getUserById($_SESSION['id']);

$tid = intval($_REQUEST['t']);
if ($tid>0) $user['task'] = $tid;
$op = intval($_REQUEST['op']);
$str = taskdiv($tid,$n,$op,$ifshow);



/* added by Zheng.Ping */
// handling for paihang
$battletimearr = unserialize($_pm['mem']->get(MEM_TIME_KEY));
foreach($battletimearr as $v)
{
	if($v['starttime'] == "paihang")
	{
		define('SORT_PAIHANG_TIME', $v['titles']);
	}
}
if (!defined(SORT_PAIHANG_TIME)) {
	define("SORT_PAIHANG_TIME","2022-06-01 10:00:00");
}
define('MEM_SORT_PAIHANG', 'sort_paihang');
$sorted_paihang = unserialize($_pm['mem']->get(MEM_SORT_PAIHANG));
if (!$sorted_paihang) {
    $now = mktime();
    $sort_time = strtotime(SORT_PAIHANG_TIME);
    if ($now >= $sort_time) {
        // trigger the sorting action
        if (sort_player_paihang()) {
            $_pm['mem']->set(array('k' => MEM_SORT_PAIHANG, 'v' => true));
        }
    }
} 

function sort_player_paihang()
{
    $ret = true;

    if (is_sorted_paihang()) {
        // if sorting had occurred before, return at here
        //echo 'sorting had occurred!<br />';
        return $ret;
    }

    $sort_nums = 10;
    $query_nums = 100;
    $toprs = $GLOBALS['_pm']['mysql']->getRecords("SELECT b.uid
								FROM userbb as b,player as u
							   WHERE u.id = b.uid and (u.secid is null or u.secid=0)
							   ORDER BY level DESC,nowexp DESC
							   LIMIT 0, $query_nums
							");
    if (is_array($toprs)) {
        $pid_list = array();
        foreach ($toprs as $v) {
            $pid_list[] = $v['uid'];
        }
        $pid_list = array_unique($pid_list);

        $i = 0;
        $first_paihang = array();
        $second_paihang = array();
        $third_paihang = array();
        $fourth_paihang = array();
        foreach ($pid_list as $pid) {
            $i++;
            if ($i == 1) {
                $first_paihang[] = $pid;
            } elseif (in_array($i, array(2, 3, 4))) {
                $second_paihang[] = $pid;
            } elseif (in_array($i, array(5, 6, 7))) {
                $third_paihang[] = $pid;
            } elseif (in_array($i, array(8, 9, 10))) {
                $fourth_paihang[] = $pid;
            }
        }

        $paihang_level = array(1 => $first_paihang, 
            2 => $second_paihang, 
            3 => $third_paihang, 
            4 => $fourth_paihang);
        foreach ($paihang_level as $k => $v) {
            if (!empty($v)) {
                $sql = sprintf("UPDATE player SET paihang=%d WHERE id IN (%s)", $k, implode(',', $v));
                if (false === $GLOBALS['_pm']['mysql']->query($sql)) {
                    $ret = false;
                }
            }
        }
	}

    return $ret;
}

/**
 * check if the paihang had been sorted before
 *
 * @return boolean
 * @author Zheng.Ping
 */
function is_sorted_paihang()
{
    $ret = false;

    $check = $GLOBALS['_pm']['mysql']->getOneRecord("SELECT count(*) AS sum FROM player WHERE paihang > 0");
    if (!empty($check) && isset($check['sum']) && $check['sum'] > 0) {
        $ret = true;
    };

    return $ret;
}

/**
 * check whether the task need top level sorting (paihang)
 *
 * @param string $task_completion
 * @return boolean
 * @author Zheng.Ping
 */
function is_task_need_paihang($task_completion)
{
    $ret = false;
    $condition = 'paihang';

    if (is_string($task_completion) 
        && strpos($task_completion, $condition) !== false) {
        $ret = true;
    }

    return $ret;
}

/**
 * get the paihang needed level
 *
 * @param string $taks_completion
 * @return integer
 * @author Zheng.Ping
 */
function get_paihang_level($task_completion)
{
    $ret = 0;
    $condition = 'paihang';

    if (strpos($task_completion, $condition) !== false) {
        $items = explode(',', $task_completion);
        if (!empty($items)) {
            foreach ($items as $item) {
                $kv = explode(':', $item);
                if (isset($kv[0]) && isset($kv[1]) && $kv[0] == $condition) {
                    $ret = intval($kv[1]);
                    break;
                }
            }
        }
    }

    return $ret;
}

/**
 * get the completion information about the task 
 *
 * @param array $taks_list
 * @param integer $task_id
 * @return string
 * @author Zheng.Ping
 */
function get_task_completion_info($task_list, $task_id)
{
    $info = '';

    if (is_array($task_list)) {
        // try to get completion information from $task_list
        foreach($task_list as $v) {
            if ($v['id'] == $task_id) {
                if (isset($v['okneed'])) {
                    $info = $v['okneed'];
                }
                break;
            }
        }
    }

    if (!$info && $task_id > 0) {
        $sql = sprintf("SELECT okneed FROM task WHERE id=%d", $task_id);
        $res = $GLOBALS['_pm']['mysql']->getOneRecord($sql);
        if (is_array($res)) {
            $info = $res['okneed'];
        }
    }

    return $info;
}
?>
