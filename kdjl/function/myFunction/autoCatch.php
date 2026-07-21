<?php
/**
 *
*/
require_once('../../config/config.game.php');

function autoCatchSkillValue($values, $index, $required)
{
    $parts = explode(',', strval($values));
    $index = intval($index);
    if($index < 0) $index = 0;
    if(isset($parts[$index])) return $parts[$index];
    return $required ? false : '';
}

function autoCatchCreateSkills($bbid, $bbInfo)
{
    global $_pm;
    $bbid = intval($bbid);
    if($bbid < 1) return false;
    $skillList = isset($bbInfo['skillist']) ? trim(strval($bbInfo['skillist'])) : '';
    if($skillList == '' || $skillList == '0') return true;

    $entries = explode(',', $skillList);
    foreach($entries as $entry){
        $entry = trim($entry);
        if($entry == '' || $entry == '0') continue;
        $parts = explode(':', $entry);
        if(count($parts) != 2 || !ctype_digit(strval($parts[0])) || !ctype_digit(strval($parts[1]))){
            return false;
        }
        $sid = intval($parts[0]);
        $skillLevel = intval($parts[1]);
        if($sid < 1 || $skillLevel < 1) return false;
        $skill = $_pm['mysql']->getOneRecord("SELECT * FROM skillsys WHERE id='{$sid}'");
        if(!is_array($skill) || intval($skill['id']) != $sid) return false;

        $skillIndex = $skillLevel - 1;
        $skillValue = autoCatchSkillValue(isset($skill['ackvalue']) ? $skill['ackvalue'] : '', $skillIndex, true);
        $skillUhp = autoCatchSkillValue(isset($skill['uhp']) ? $skill['uhp'] : '', $skillIndex, true);
        $skillUmp = autoCatchSkillValue(isset($skill['ump']) ? $skill['ump'] : '', $skillIndex, true);
        if($skillValue === false || $skillUhp === false || $skillUmp === false) return false;

        $skillName = $_pm['mysql']->escape(isset($skill['name']) ? $skill['name'] : '');
        $skillVary = $_pm['mysql']->escape(isset($skill['vary']) ? $skill['vary'] : '');
        $skillValue = $_pm['mysql']->escape($skillValue);
        $skillPlus = $_pm['mysql']->escape(autoCatchSkillValue(isset($skill['plus']) ? $skill['plus'] : '', $skillIndex, false));
        $skillImg = $_pm['mysql']->escape(autoCatchSkillValue(isset($skill['imgeft']) ? $skill['imgeft'] : '', $skillIndex, false));
        $insertOk = $_pm['mysql']->query("INSERT INTO skill(bid,name,level,vary,wx,value,plus,img,uhp,ump,sid)
            VALUES('{$bbid}','{$skillName}','{$skillLevel}','{$skillVary}','".intval($skill['wx'])."','{$skillValue}','{$skillPlus}','{$skillImg}','".intval($skillUhp)."','".intval($skillUmp)."','{$sid}')");
        if(!$insertOk || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
            return false;
        }
    }
    return true;
}

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1){
    echo '0';
    return;
}

$resAllowList = $_pm['mysql']->getOneRecord("select * from welcome where code='fuzhulist'");
$AllowListArr = is_array($resAllowList) ? explode(',',$resAllowList['contents']) : array();

$isAllowed = false;

for($i=0;$i<count($AllowListArr);$i++){
    if(intval($AllowListArr[$i]) == $uid){
        $isAllowed = true;
    }
}

if(!$isAllowed){
    echo '6'; //不被允许使用自动抓捏辅助
    return;
}

$bid = (isset($_REQUEST['bid']) && !is_array($_REQUEST['bid'])) ? intval($_REQUEST['bid']) : 0;
$sid = (isset($_REQUEST['sid']) && !is_array($_REQUEST['sid'])) ? intval($_REQUEST['sid']) : 0;

$sqlBBNie = "select * from bb where id='103'";
$resBBNie = $_pm['mysql']->getOneRecord($sqlBBNie);
if(!is_array($resBBNie)){
    echo '0';
    return;
}
$bbDefaults = array(
    'name' => '', 'skillist' => '', 'nowexp' => 0, 'imgstand' => '', 'imgack' => '', 'imgdie' => '',
    'wx' => 0, 'ac' => 0, 'mc' => 0, 'hp' => 0, 'mp' => 0, 'hits' => 0, 'miss' => 0, 'speed' => 0,
    'kx' => '', 'remakelevel' => '', 'remakeid' => '', 'remakepid' => '', 'id' => 0
);
foreach($bbDefaults as $bbDefaultKey => $bbDefaultValue){
    if(!isset($resBBNie[$bbDefaultKey])) $resBBNie[$bbDefaultKey] = $bbDefaultValue;
}
$nickname = isset($_SESSION['nickname']) ? $_SESSION['nickname'] : '';
$nicknameSql = $_pm['mysql']->escape($nickname);
$bbNameSql = $_pm['mysql']->escape($resBBNie['name']);
$skillistSql = $_pm['mysql']->escape($resBBNie['skillist']);
$nowExpSql = $_pm['mysql']->escape($resBBNie['nowexp']);
$imgstandSql = $_pm['mysql']->escape($resBBNie['imgstand']);
$imgackSql = $_pm['mysql']->escape($resBBNie['imgack']);
$imgdieSql = $_pm['mysql']->escape($resBBNie['imgdie']);
$kxSql = $_pm['mysql']->escape($resBBNie['kx']);
$remakeLevelSql = $_pm['mysql']->escape($resBBNie['remakelevel']);
$remakeIdSql = $_pm['mysql']->escape($resBBNie['remakeid']);
$remakePidSql = $_pm['mysql']->escape($resBBNie['remakepid']);

$isSuccess = 0;//报错

$resPlayer = $_pm['mysql']->getOneRecord("select * from player where id='".$uid."'");
if(!is_array($resPlayer)){
    echo '0';
    return;
}
$resMaxMC = $resPlayer['maxmc'];
$resBBRows = $_pm['mysql']->getRecords("select * from userbb where uid='".$uid."' and muchang='1'");
$resBBNum = is_array($resBBRows) ? count($resBBRows) : 0;
if(intval($resBBNum) >= intval($resMaxMC)){
    echo '3';//牧场已满！
    return;
}

$resOpenMap = $resPlayer['openmap'];
$openmapArr = explode(',',$resOpenMap);
if(!in_array('10',$openmapArr)){
    echo '5';//没开冰滩地图
    return;
}

//涅盘兽·精灵球id	1252，涅槃兽·精灵球(绑定)id-1994
$resBall1 = $_pm['mysql']->getOneRecord("select * from userbag where uid='".$uid."' and pid='1252' and sums > 0 and zbing = 0 and (cantrade IS NULL OR cantrade<>3) order by id limit 1");
$resBallNum1 = is_array($resBall1) ? $resBall1['sums'] : 0;
$resBall2 = $_pm['mysql']->getOneRecord("select * from userbag where uid='".$uid."' and pid='1994' and sums > 0 and zbing = 0 and (cantrade IS NULL OR cantrade<>3) order by id limit 1");
$resBallNum2 = is_array($resBall2) ? $resBall2['sums'] : 0;
if(intval($resBallNum1)<=0 && intval($resBallNum2)<=0){
    echo '4';
    return;
}

$rd = rand(8,13);
if(rand(0,$rd) != 5){
    echo "7"; // 未遇到涅槃兽
    return;
}

if(!$_pm['mysql']->query('START TRANSACTION')){
    echo '0';
    return;
}
$lockedPlayer = $_pm['mysql']->getOneRecord("select id,maxmc from player where id='".$uid."' FOR UPDATE");
if(!is_array($lockedPlayer)){
    $_pm['mysql']->query('ROLLBACK');
    echo '0';
    return;
}
$resBBRows = $_pm['mysql']->getRecords("select id from userbb where uid='".$uid."' and muchang='1' FOR UPDATE");
$resBBNum = is_array($resBBRows) ? count($resBBRows) : 0;
if(intval($resBBNum) >= intval($lockedPlayer['maxmc'])){
    $_pm['mysql']->query('ROLLBACK');
    echo '3';
    return;
}
$resBall1 = $_pm['mysql']->getOneRecord("select id,sums from userbag where uid='{$uid}' and pid='1252' and sums>0 and zbing=0 and (cantrade IS NULL OR cantrade<>3) order by id limit 1 FOR UPDATE");
$resBall2 = $_pm['mysql']->getOneRecord("select id,sums from userbag where uid='{$uid}' and pid='1994' and sums>0 and zbing=0 and (cantrade IS NULL OR cantrade<>3) order by id limit 1 FOR UPDATE");
$resBallNum1 = is_array($resBall1) ? intval($resBall1['sums']) : 0;
$resBallNum2 = is_array($resBall2) ? intval($resBall2['sums']) : 0;
if($resBallNum1 <= 0 && $resBallNum2 <= 0){
    $_pm['mysql']->query('ROLLBACK');
    echo '4';
    return;
}
$ballUsed = false;
$ballBagId = 0;
$ballPid = 0;
if(intval($resBallNum1) > 0){
    $ballBagId = intval($resBall1['id']);
    $ballPid = 1252;
    $ballQuery = $_pm['mysql'] -> query(" UPDATE userbag SET sums = sums-1 WHERE id = '".$resBall1['id']."' AND uid = '".$uid."' AND pid = '1252' AND sums > 0 AND zbing = 0 AND (cantrade IS NULL OR cantrade<>3)");
    $ballUsed = $ballQuery && mysql_affected_rows($_pm['mysql']->getConn()) == 1;
}else if(intval($resBallNum2) > 0){
    $ballBagId = intval($resBall2['id']);
    $ballPid = 1994;
    $ballQuery = $_pm['mysql'] -> query(" UPDATE userbag SET sums = sums-1 WHERE id = '".$resBall2['id']."' AND uid = '".$uid."' AND pid = '1994' AND sums > 0 AND zbing = 0 AND (cantrade IS NULL OR cantrade<>3)");
    $ballUsed = $ballQuery && mysql_affected_rows($_pm['mysql']->getConn()) == 1;
}else{
    $_pm['mysql']->query('ROLLBACK');
    echo '4';
    return;
}
if(!$ballUsed){
    $_pm['mysql']->query('ROLLBACK');
    echo '4';
    return;
}

if(!$_pm['mysql']->query("DELETE FROM userbag WHERE id = '".$ballBagId."' AND uid = '".$uid."' AND pid = '".$ballPid."' AND sums <= 0 AND bsum <= 0 AND psum <= 0 AND pyb = 0 AND zbing = 0 AND (cantrade IS NULL OR cantrade <> 3)")){
    $_pm['mysql']->query('ROLLBACK');
    echo '0';
    return;
}

$rdSuccess = rand(10,80);

if(rand(0,90)==$rdSuccess){
    $insertOk = $_pm['mysql']->query("INSERT INTO userbb(name,uid,username,level,wx,ac,mc,srchp,hp,srcmp,mp,skillist,stime,nowexp,
								lexp,imgstand,imgack,imgdie,hits,miss,speed,kx,remakelevel,remakeid,remakepid,czl,headimg,cardimg,effectimg,muchang,old_bid)
                            VALUES('{$bbNameSql}','{$uid}','{$nicknameSql}','1','{$resBBNie['wx']}',
                               '{$resBBNie['ac']}','{$resBBNie['mc']}','{$resBBNie['hp']}','{$resBBNie['hp']}','{$resBBNie['mp']}','{$resBBNie['mp']}','{$skillistSql}',unix_timestamp(),
                              '{$nowExpSql}','55','{$imgstandSql}','{$imgackSql}','{$imgdieSql}',
						   '{$resBBNie['hits']}','{$resBBNie['miss']}','{$resBBNie['speed']}','{$kxSql}','{$remakeLevelSql}',
						   '{$remakeIdSql}','{$remakePidSql}','1','t{$resBBNie['id']}.gif','k{$resBBNie['id']}.gif','q{$resBBNie['id']}.gif','1','{$resBBNie['id']}')
				  ");
    if(!$insertOk || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
        $_pm['mysql']->query('ROLLBACK');
        $isSuccess = '0';
    }else if(!autoCatchCreateSkills($_pm['mysql']->last_id(), $resBBNie)){
        $_pm['mysql']->query('ROLLBACK');
        $isSuccess = '0';
    }else if(!$_pm['mysql']->query('COMMIT')){
        $_pm['mysql']->query('ROLLBACK');
        $isSuccess = '0';
    }else{
		$_pm['mem']->del(MEM_USERBAG_KEY);
		$_pm['mem']->del(MEM_USERBB_KEY);
		$_pm['mem']->del(MEM_USERSK_KEY);
		$chatConfig = dirname(__FILE__) . '/../../socketChat/config.chat.php';
		if(file_exists($chatConfig))
		{
			require_once($chatConfig);
			if(class_exists('socketmsg'))
			{
				$swfData = '恭喜玩家 ' . $nickname . ' 通过自动抓捏辅助抓到了'.$resBBNie['name'].'!';
				$s = new socketmsg();
				$s->sendMsg('an|' . $swfData);
			}
		}
		$isSuccess = '1';//捕捉成功！
    }
}else{
    if(!$_pm['mysql']->query('COMMIT')){
        $_pm['mysql']->query('ROLLBACK');
        $isSuccess = '0';
    }else{
		$_pm['mem']->del(MEM_USERBAG_KEY);
        $isSuccess = '2';//捕捉失败！
    }
}

echo $isSuccess;
return;

?>
