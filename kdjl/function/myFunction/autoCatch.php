<?php
/**
 * 
*/
require_once('../../config/config.game.php');

$resAllowList = $_pm['mysql']->getOneRecord("select * from welcome where code='fuzhulist'");
$AllowListArr = explode(',',$resAllowList['contents']);

$isAllowed = false;

for($i=0;$i<count($AllowListArr);$i++){
    if($AllowListArr[$i] == $_SESSION['id']){
        $isAllowed = true;
    }
}

if(!$isAllowed){
    echo '6'; //不被允许使用自动抓捏辅助
    return;
}

$bid = intval($_REQUEST['bid']);
$sid = intval($_REQUEST['sid']);

$sqlBBNie = "select * from bb where id='103'";
$resBBNie = $_pm['mysql']->getOneRecord($sqlBBNie);

$isSuccess = 0;//报错

$resPlayer = $_pm['mysql']->getOneRecord("select * from player where id='".$_SESSION['id']."'");
$resMaxMC = $resPlayer['maxmc'];
$resBBNum = count($_pm['mysql']->getRecords("select * from userbb where uid='".$_SESSION['id']."' and muchang='1'"));
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
$resBall1 = $_pm['mysql']->getOneRecord("select * from userbag where uid='".$_SESSION['id']."' and pid='1252'");
$resBallNum1 = $resBall1['sums'];
$resBall2 = $_pm['mysql']->getOneRecord("select * from userbag where uid='".$_SESSION['id']."' and pid='1994'");
$resBallNum2 = $resBall2['sums'];
if(intval($resBallNum1)<=0 && intval($resBallNum2)<=0){
    echo '4';//没有可用的精灵球！
    return;
}

$rd = rand(8,13);
if(rand(0,$rd) != 5){
    echo "7"; // 未遇到涅槃兽
    return;
}

if(intval($resBallNum1) > 0){
    $_pm['mysql'] -> query(" UPDATE userbag SET sums = abs(sums-1) WHERE id = '".$resBall1['id']."'");
}else if(intval($resBallNum2) > 0){
    $_pm['mysql'] -> query(" UPDATE userbag SET sums = abs(sums-1) WHERE id = '".$resBall2['id']."'");
}else{
    echo '4';//没有可用的精灵球！
    return;
}

$rdSuccess = rand(10,80);

if(rand(0,90)==$rdSuccess){
    $_pm['mysql']->query("INSERT INTO userbb(name,uid,username,level,wx,ac,mc,srchp,hp,srcmp,mp,skillist,stime,nowexp,
    								lexp,imgstand,imgack,imgdie,hits,miss,speed,kx,remakelevel,remakeid,remakepid,czl,headimg,cardimg,effectimg,muchang)
    				    VALUES('{$resBBNie['name']}','{$_SESSION['id']}','{$_SESSION['nickname']}','1','{$resBBNie['wx']}',
    					       '{$resBBNie['ac']}','{$resBBNie['mc']}','{$resBBNie['hp']}','{$resBBNie['hp']}','{$resBBNie['mp']}','{$resBBNie['mp']}','{$resBBNie['skillist']}',unix_timestamp(),
    						  '{$resBBNie['nowexp']}','55','{$resBBNie['imgstand']}','{$resBBNie['imgack']}','{$resBBNie['imgdie']}',
    						   '{$resBBNie['hits']}','{$resBBNie['miss']}','{$resBBNie['speed']}','{$resBBNie['kx']}','{$resBBNie['remakelevel']}',
    						   '{$resBBNie['remakeid']}','{$resBBNie['remakepid']}','1','t{$resBBNie['id']}.gif','k{$resBBNie['id']}.gif','q{$resBBNie['id']}.gif','1')
    				  ");
    $swfData =  '恭喜玩家 ' . $_SESSION['nickname'] . ' 通过自动抓捏辅助抓到了'.$resBBNie['name'].'!';
    require_once(dirname(__FILE__) . '/../../socketChat/config.chat.php');
    $s = new socketmsg();
    $s->sendMsg('an|' . $swfData);
    $isSuccess = '1';//捕捉成功！
}else{
    $isSuccess = '2';//捕捉失败！
}

echo $isSuccess;
return;

?>