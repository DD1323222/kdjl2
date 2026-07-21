<?php
set_time_limit(300);
ignore_user_abort(true);//用户关闭浏览器不退出
require_once('./config/config.game.php');
global $m;
$m = new memory();    // Init memcache.
$db =new mysql();

/**
 * 通用处理函数
 *
 * @param string $db_name 表名, bool/int $is_need_iterative 是否需要foreach，如果是1，则先set，后返回foreach的值, bool/string $assign 是否直接赋值, bool $return_db_values 是否直接返回数据库查询的值
 */
function common_process($db_name, $is_need_iterative = false, $assign = false, $return_db_values = false)
{
    global $m, $db;

    if (!$db_name)
        return false;
    $res = array();
    $arr = array();
    $baseCacheTypes = array('task', 'gpc', 'map', 'skillsys', 'bb', 'welcome', 'props');
    $refreshesBaseRows = !is_array($assign) && in_array($db_name, $baseCacheTypes, true);
    $oldRows = $refreshesBaseRows ? kdjlMemArrayValue($m, 'db_' . $db_name) : array();
    if (is_array($assign)) {
        $res = $assign;
    } else {
        if ($db_name == 'props') {
            $res = $db->getRecords("select * from " . $db_name ." ORDER BY stime");
        } else {
            $res = $db->getRecords("select * from " . $db_name);
        }
        if (!is_array($res))
            return false;
    }
    if ($return_db_values)
        return $res;

    if ($refreshesBaseRows)
        kdjlInvalidateChangedBaseConfigRows($m, $db_name, $oldRows, $res);

    if ($is_need_iterative) {
        foreach ($res as $v)
            $arr[$v['id']] = $v;
        if (is_bool($is_need_iterative))
            $res = $arr;
    }

    $m->del('db_' . $db_name);
    if ($m->set(array('k' => 'db_' . $db_name, 'v' => $res)) === false)
        return false;

    if (!is_bool($is_need_iterative))
        return $arr;
    return $res;
}

/**
 * 内存载入
 *
 * @param string $key 要载入的表名
 */
function loadmem($key)
{
    global $m, $db;
    switch ($key) {
        case 'task':
            return common_process('task', true) !== false;
        case 'skillsys':

            //case 'skillsysid':
            $arr = common_process('skillsys', 1);
            if (!is_array($arr)) return false;
            return common_process('skillsysid', false, $arr) !== false;
        case 'bb':
            //case 'bbname':
            //case 'bbid':
            $ret2 = common_process('bb');
            if (!is_array($ret2)) return false;
            $arr = array();
            $arrnew = array();
            foreach ($ret2 as $k => $v) {
                $arr[$v['name']] = $v;
                $arrnew[$v['id']] = $v;
            }
            return common_process('bbname', false, $arr) !== false &&
                common_process('bbid', false, $arrnew) !== false;
        case 'gpc':
            //case 'gpcid':
            $ret2 = common_process('gpc', 1);
            if (!is_array($ret2)) return false;
            return common_process('gpcid', false, $ret2) !== false;
        case 'merge':
            return common_process('merge') !== false;
        case 'zs':
            return common_process('zs') !== false;
        case 'map':
            //case 'mapid':
            $ret2 = common_process('map', 1);
            if (!is_array($ret2)) return false;
            return common_process('mapid', false, $ret2) !== false;
        case 'wx':

            return common_process('wx') !== false;
        case 'welcome':
            //case 'welcome1':
            $ret2 = common_process('welcome');
            if (!is_array($ret2)) return false;
            $arrnew = array();
            foreach ($ret2 as $k => $v)
                $arrnew[$v['code']] = $v['contents'];
            return common_process('welcome1', false, $arrnew) !== false;
        case 'timeconfig':
            //case 'timeconfignew':
            $ret2 = common_process('timeconfig');
            if (!is_array($ret2)) return false;
            $arrnew = array();
            foreach ($ret2 as $v)
                $arrnew[$v['titles']][] = $v;
            return common_process('timeconfignew', false, $arrnew) !== false;
        case 'exptolv':
            return common_process('exptolv') !== false;
        case 'aoyun':
            $ret2 = common_process('aoyun', false, false, true);
            if (!is_array($ret2)) return false;
            $arr = array();
            foreach ($ret2 as $k => $v)
                if (is_array($v))
                    $arr[$v['id']] = $v;
            return $m->set(array('k' => 'db_aoyun', 'v' => $arr)) !== false;
        case 'blacklist':
            $ret2 = common_process('blacklist', false, false, true);
            if (!is_array($ret2)) return false;
            $newarr = array();
            foreach ($ret2 as $k => $v)
                $newarr[$v['uid']] = ',' . $v['list'] . ",";
            return $m->set(array('k' => 'db_blacklist', 'v' => $newarr)) !== false;
        case 'gonggao':
            return common_process('gonggao') !== false;
        case 'props':
            //case 'propsid':
            //case 'propsname':
            //case 'equip':
            $ret2 = common_process('props');
            if (!is_array($ret2)) return false;
            $arr = array();
            $arrnew = array();
            foreach ($ret2 as $pv) {
                $arr[$pv['id']] = $pv;
                $arrnew[$pv['name']] = $pv;
            }
            return common_process('propsid', false, $arr) !== false &&
                common_process('propsname', false, $arrnew) !== false;
        case 't_ly_url_config':
        {
            // Linux keeps this legacy table name case-sensitive. Using the
            // real name also preserves the cache key read by index.php.
            return common_process('T_ly_URL_config') !== false;
        }
    }
    return false;
}

//把memcache中的某个一自增数字做键的数字二维数组转换成字符串保存起来
function memArr2Str1($data, $key, $spFiled = "`_`", $spLine = "$+$", $suffix = 'str')
{
    global $_pm;
    //$data=$_pm['mem']->get($key);
    //if(!is_array($data)&&strlen($data)>3) $data=unserialize($data);
    $str = '';
    $con = '';

    if (count($data) > 0) {
        foreach ($data as $v) {
            if (count($v) > 0) {
                $str .= $con . implode($spFiled, $v);
                $con = $spLine;
            }
        }
    }
    $key .= $suffix;
    $_pm['mem']->setnsnc($key, $str);

}

function guild_update_mem()
{
    global $_pm;
    $guild = $_pm['mysql']->getRecords("SELECT member_id,guild_id FROM guild_members");
    $arr = array();
    if (!is_array($guild)) {
        $_pm['mem']->setns('MEM_GUILD_LIST', $arr);
        memArr2Str($arr, 'MEM_GUILD_LIST');
        return false;
    }
    foreach ($guild as $v) {
        $arr[$v['guild_id']][] = $v['member_id'];
    }
    $_pm['mem']->setns('MEM_GUILD_LIST', $arr);
    memArr2Str1($arr, 'MEM_GUILD_LIST');
}

$cliDone = (isset($argv) && isset($argv[1]) && $argv[1] == 'done');
$formRefreshAll = isset($_GET['refresh_all']) && !is_array($_GET['refresh_all']);
$dbRefreshAll = $formRefreshAll || (count($_GET) == 1 && isset($_GET['db']) && !is_array($_GET['db']) && $_GET['db'] == "");
if ($dbRefreshAll || $cliDone) {
    $refreshFailures = array();
    foreach (array('task', 'skillsys', 'bb', 'gpc', 'merge', 'zs', 'map', 'wx', 'welcome', 'timeconfig', 'exptolv', 'aoyun', 'blacklist', 'gonggao', 'props', 't_ly_url_config') as $v) {
        if (!loadmem($v)) $refreshFailures[] = $v;
    }
    guild_update_mem();

    echo empty($refreshFailures) ? 'done' : 'failed: '.implode(',', $refreshFailures);
} else {
    $refreshMap = array(
        'db_task' => 'task',
        'skillsys' => 'skillsys',
        'db_skillsys' => 'skillsys',
        'db_bb' => 'bb',
        'db_gpc' => 'gpc',
        'db_merge' => 'merge',
        'db_zs' => 'zs',
        'db_map' => 'map',
        'db_wx' => 'wx',
        'db_welcome' => 'welcome',
        'db_timeconfig' => 'timeconfig',
        'db_exptolv' => 'exptolv',
        'db_aoyun' => 'aoyun',
        'db_blacklist' => 'blacklist',
        'db_gonggao' => 'gonggao',
        'db_props' => 'props',
        'db_t_ly_url_config' => 't_ly_url_config'
    );
    foreach ($_GET as $k => $v) {
        if (is_array($v)) continue;
        $v = trim($v);
        if (!isset($refreshMap[$v])) continue;
        echo $v . (loadmem($refreshMap[$v]) ? "<br />" : " 刷新失败<br />");
    }
}
//foreach(array('task', 'skillsys', 'bb', 'gpc', 'merge', 'zs', 'map', 'wx', 'welcome', 'timeconfig', 'exptolv', 'aoyun', 'blacklist', 'gonggao', 'props', 'skillsysid', 'bbname', 'bbid', 'gpcid', 'mapid', 'welcome1', 'timeconfignew', 'propsid', 'propsname', 'equip') as $v) if(!$m->get('db_'.$v)) $a[]=$v;
//var_dump(unserialize( $m->get('db_skillsys')));
$sql = 'SELECT value2,contents FROM welcome WHERE code = "timelimitbuy"';
$tm = $_pm["mysql"]->getOneRecord($sql);
if (is_array($tm)) {
    $time = date('Y-m-d H:i:s');
    $tarr = explode('|', $tm['value2']);
    if (count($tarr) >= 2 && $time > $tarr[0] && $time < $tarr[1]) {
        $p = explode(',', $tm['contents']);//20100915120000
        $v = '';
        foreach ($p as $v) {
            $va = explode(':', $v);
            if (count($va) < 1 || intval($va[0]) < 1) continue;
            $s = 0;
            $pid = intval($va[0]);
            $sql = 'SELECT id FROM props WHERE zhekouyb > 0 AND id = ' . $pid;
            $res = $_pm['mysql']->getOneRecord($sql);
            if (!is_array($res) || !isset($res['id'])) continue;
            $sql = 'SELECT sum(nums) as nums FROM yblog WHERE title ="' . $pid . '" AND DATE_FORMAT(from_unixtime(buytime),"%Y-%m-%d %H:%i:%s") > "' . $tarr[0] . '" AND DATE_FORMAT(from_unixtime(buytime),"%Y-%m-%d %H:%i:%s") < "' . $tarr[1] . '"';
            //echo $sql;
            $ybarr = $_pm['mysql']->getOneRecord($sql);

            if (is_array($ybarr)) {
                $s = $ybarr['nums'];
            }
            $m->set(array('k' => 'zhekou_' . $res['id'] . '_num', 'v' => $s));
        }
    }
}
$m->memClose();
if (isset($_GET['auto'])) {
    echo
    '
		<script type="text/javascript">
			alert("服务器初始化成功!\n请关闭浏览器重新登陆!");
			setTimeout("window.top.goToIndex();",500);
		</script>
	';
    die();
}
if (stripos($_SERVER['PHP_SELF'], 'vm') !== false) {

    ?>

    <style type="text/css">
        <!--
        body, td, th {
            font-size: 12px;
        }

        body {
            margin-left: 0px;
            margin-top: 0px;
            margin-right: 0px;
            margin-bottom: 0px;
        }

        .STYLE1 {
            color: #FF0000
        }

        -->
    </style>
    <center>
        <form id="form1" name="form1" method="get" action="">
            <table width="778" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td width="72" height="25" align="right">更新内容：</td>
                    <td width="706" height="25"><p>
                            <input name="db_task" type="checkbox" id="db_task" value="db_task"/>
                            任务
                            <input type="checkbox" name="skillsys" value="skillsys"/>

                            技能
                            <input name="db_bb" type="checkbox" id="db_bb" value="db_bb"/>
                            宠物
                            <input name="db_gpc" type="checkbox" id="db_gpc" value="db_gpc"/>
                            怪物
                            <input name="db_merge" type="checkbox" id="db_merge" value="db_merge"/>
                            合成
                            <input name="db_zs" type="checkbox" id="db_zs" value="db_zs"/>
                            转生
                            <input name="db_map" type="checkbox" id="db_map" value="db_map"/>
                            地图
                            <input name="db_wx" type="checkbox" id="db_wx" value="db_wx"/>
                            五行
                            <input name="db_welcome" type="checkbox" id="db_welcome" value="db_welcome"/>
                            活动介绍
                            <input name="db_timeconfig" type="checkbox" id="db_timeconfig" value="db_timeconfig"/>
                            时间配置
                            <input name="db_exptolv" type="checkbox" id="db_exptolv" value="db_exptolv"/>
                            升级经验
                            <input name="db_aoyun" type="checkbox" id="db_aoyun" value="db_aoyun"/>
                            奥运
                            <input name="db_blacklist" type="checkbox" id="db_blacklist" value="db_blacklist"/>
                            黑名单
                            <input name="db_gonggao" type="checkbox" id="db_gonggao" value="db_gonggao"/>
                            公告
                            <input name="db_props" type="checkbox" id="db_props" value="db_props"/>
                            道具
                            <input name="db_t_ly_url_config" type="checkbox" id="db_t_ly_url_config"
                                   value="db_t_ly_url_config"/>
                            联运商URL配置<span class="STYLE1">(可勾选部分项目更新)</span>
                            <input type="submit" name="Submit" value="提交"/>
                            <input type="submit" name="refresh_all" value="自动更新所有"/>
                        </p>
                    </td>
                </tr>
            </table>
        </form>
    </center>
    <?php
} ?>
