<?php
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');
secStart($_pm['mem']);

$act = (isset($_GET['act']) && !is_array($_GET['act'])) ? $_GET['act'] : '';
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
$playerName = isset($_SESSION['username']) ? $_SESSION['username'] : (isset($_SESSION['name']) ? $_SESSION['name'] : '');
$startTime = strtotime(date("Y-m-d ").'00:00:00');
$endTime = strtotime(date("Y-m-d ").'20:59:59');
$sql = 'select sum(yb) fee,nickname from yblog where buytime>'.$startTime.' and buytime<'.$endTime.' group by nickname order by sum(yb) desc limit 3';
$rows = $_pm['mysql']->getRecords($sql);
if(!is_array($rows)) $rows = array();
$consumptionTopPendingLogIds = array();
$consumptionTopCommitted = false;
$consumptionTopTransactionActive = false;

function consumptionTopCleanupPendingLogs()
{
    global $_pm,$consumptionTopPendingLogIds,$consumptionTopCommitted;
    if(!$consumptionTopCommitted && !empty($consumptionTopPendingLogIds) && isset($_pm['mysql']))
    {
        $ids = array();
        foreach($consumptionTopPendingLogIds as $id)
        {
            $id = intval($id);
            if($id > 0) $ids[$id] = $id;
        }
        if(!empty($ids)) $_pm['mysql']->query('DELETE FROM gamelog WHERE vary=239 AND id IN ('.implode(',',$ids).')');
    }
    $consumptionTopPendingLogIds = array();
}

function consumptionTopFail($message)
{
    global $_pm,$consumptionTopTransactionActive;
    if($consumptionTopTransactionActive && isset($_pm['mysql'])) $_pm['mysql']->query('ROLLBACK');
    $consumptionTopTransactionActive = false;
    consumptionTopCleanupPendingLogs();
    if(function_exists('realseLock')) realseLock();
    die($message);
}

function consumptionTopShutdown()
{
    global $_pm,$consumptionTopTransactionActive;
    if($consumptionTopTransactionActive && isset($_pm['mysql'])) $_pm['mysql']->query('ROLLBACK');
    $consumptionTopTransactionActive = false;
    consumptionTopCleanupPendingLogs();
    if(function_exists('realseLock')) realseLock();
}

function givePrize($name, $pstr, &$tsk)
{
    global $_pm,$uid,$consumptionTopPendingLogIds;
    $safeName = $_pm['mysql']->escape($name);
    $user = $_pm['mysql']->getOneRecord('select id from player where name="'.$safeName.'" limit 1');
    if(!is_array($user) || !isset($user['id']) || intval($user['id']) != intval($uid)) return false;

    $parsedPrize = array();
    foreach(explode('|', $pstr) as $p)
    {
        $t = explode(':', trim($p));
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
        $giveResult = $tsk->saveGetPropsMore($pid, $num, 0, $user['id']);
        if($giveResult !== true)
        {
            return false;
        }
        else
        {
            $note = '发放奖励成功，用户：'.$name.'，道具ID：'.$pid.'，数量：'.$num;
            $issued = true;
        }
        $noteSql = $_pm['mysql']->escape($note);
        $log = 'insert into gamelog set buyer="'.date('Ymd').'",vary=239,seller='.intval($user['id']).',ptime='.time().',pnote="'.$noteSql.'"';
        if(!$_pm['mysql']->query($log) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) return false;
        $logId = intval($_pm['mysql']->last_id());
        if($logId < 1) return false;
        $consumptionTopPendingLogIds[$logId] = $logId;
    }
    return $issued;
}

if(count($rows) > 0)
{
    $memtimeconfig = kdjlSafeMemValue($_pm['mem']->get('db_timeconfignew'), array());
    if(!is_array($memtimeconfig)) $memtimeconfig = array();
    $config = isset($memtimeconfig['consumptionTop'][0]) ? $memtimeconfig['consumptionTop'][0] : array();
    $config['starttime'] = isset($config['starttime']) ? intval($config['starttime']) : 0;
    $config['endtime'] = isset($config['endtime']) ? intval($config['endtime']) : 23;
    $config['days'] = isset($config['days']) ? $config['days'] : '';

    if($config['starttime'] == 0) die('活动没有开启');

    if($act == 'show')
    {
        echo '<table border="1" cellpadding="4" cellspacing="0"><tr><th>排名</th><th>玩家</th><th>元宝消费</th></tr>';
        foreach($rows as $rk => $row)
        {
            $nickname = isset($row['nickname']) ? $row['nickname'] : '';
            $fee = isset($row['fee']) ? intval($row['fee']) : 0;
            echo '<tr><td>'.(intval($rk)+1).'</td><td>'.htmlspecialchars($nickname, ENT_QUOTES, 'UTF-8').'</td><td>'.$fee.'</td></tr>';
        }
        echo '</table>';
    }
    else if($act == 'calc')
    {
        if($uid < 1) die('登录状态无效！');
        if($config['starttime'] > date('H') || $config['endtime'] < date('H'))
        {
            die('只有 '.$config['starttime'].' 至 '.$config['endtime'].' 可以领取奖励');
        }
        $lock = getLock($uid);
        if(!is_array($lock)){
            realseLock();
            die('操作过快，请稍候再试！');
        }
        if(!getScopedLock('consumption_top',intval(date('Ymd')),5)){
            realseLock();
            die('操作过快，请稍候再试！');
        }
		$consumptionTopTransactionActive = true;
		register_shutdown_function('consumptionTopShutdown');
        $ck = $_pm['mysql']->getOneRecord('select id from gamelog where vary=239 and seller = '.$uid.' AND buyer="'.date('Ymd').'" limit 1');
        if($ck === false && mysql_errno($_pm['mysql']->getConn()) != 0)
        {
			consumptionTopFail('发放奖励失败！');
        }
        if(!$ck)
        {
            $prizes = explode(',', $config['days']);
            $task = new task();
            $top = 100;
            $newrow = array();
            $flag = 0;
            foreach($rows as $rk => $rv)
            {
                if(isset($rv['nickname']) && $rv['nickname'] == $playerName)
                {
                    $top = $rk;
                    $newrow = $rv;
                    break;
                }
            }
            if($top == 100)
            {
				consumptionTopFail('您当前不在消费排行前三名！');
            }

            foreach($prizes as $k => $v)
            {
                if($k >= $top)
                {
                    $res = explode(';', $v);
					if(count($res) != 2 || trim($res[0]) == '' || !is_numeric(trim($res[1]))) consumptionTopFail('奖励配置错误！');
                    if(intval($res[1]) <= intval($newrow['fee']))
                    {
                        if(!givePrize($newrow['nickname'], $res[0], $task))
                        {
							consumptionTopFail('发放奖励失败！');
                        }
                        $flag = 1;
                        break;
                    }
                }
            }
            if(empty($flag))
            {
				consumptionTopFail('消费额不足，无法领取奖励！');
            }
            if(!$_pm['mysql']->query('COMMIT')){
				consumptionTopFail('发放奖励失败！');
            }
			$consumptionTopCommitted = true;
			$consumptionTopTransactionActive = false;
            $_pm['mem']->del(MEM_USERBAG_KEY);
            realseLock();
            die('OK');
        }
        else
        {
			consumptionTopFail('奖励已经领取过了！');
        }
    }
}
else if($act == 'show')
{
    echo '暂无消费排行记录';
}
?>
