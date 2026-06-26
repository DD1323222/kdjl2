<?php
/**
 * 给玩家派送道具
*/
require_once('../config/config.game.php');

$error = 10;

$act = $_GET["act"];
$userID = $_GET["userID"];
$propID = $_GET["propID"];
$num = $_GET["num"];

switch ($act) {
    case 'selectUserName':
        $sqlUserName = "select nickname from player where id='".$userID."'";
        $resUserName = $_pm['mysql']->getRecords($sqlUserName);
        if(is_array($resUserName)){
            $error = 'username'.$resUserName[0]['nickname'];
            //$error = '"username"';
        }else{
            $error = 4;
        }
        break;
        
    case 'selectPropName':
        $sqlPropName = "select name from props where id='".$propID."'";
        $resPropName = $_pm['mysql']->getRecords($sqlPropName);
        if(is_array($resPropName)){
            $error = 'propname'.$resPropName[0]['name'];
        }else{
            $error = 5;
        }
        break;
        
    case 'addYB':
        $sqlPlayer = "select * from player where id='".$userID."'";
        $resPlayer = $_pm['mysql']->getRecords($sqlPlayer);
        $addNum = $resPlayer[0]['yb'] + intval($num);
        if(is_array($resPlayer)){
            $sqlYBtoU = "update player set yb='".$addNum."' where id='".$resPlayer[0]['id']."'";
            if($_pm['mysql']->query($sqlYBtoU)){
                $error = 0;
            }else{
                $error = 61;
            }
        }else{
            $error = 6;
        }
        break;
        
    case 'addSJ':
        $sqlPlayerExt = "select * from player_ext where uid='".$userID."'";
        $resPlayerExt = $_pm['mysql']->getRecords($sqlPlayerExt);
        $addNum = $resPlayerExt[0]['sj'] + intval($num);
        if(is_array($resPlayerExt)){
            $sqlSJtoU = "update player_ext set sj='".$addNum."' where uid='".$resPlayerExt[0]['uid']."'";
            if($_pm['mysql']->query($sqlSJtoU)){
                $error = 0;
            }else{
                $error = 71;
            }
        }else{
            $error = 7;
        }
        break;
        
    case 'addProp':
        $sqlUserBag = "select * from userbag where uid='".$userID."' and pid='".$propID."' and vary!='2'";
        $resUserBag = $_pm['mysql']->getRecords($sqlUserBag);
        
        if(is_array($resUserBag)){
            $addNum = $resUserBag[0]['sums'] + intval($num);
            $sqlUserBag = "update userbag set sums='".$addNum."' where id='".$resUserBag[0]['id']."'";
            if($_pm['mysql']->query($sqlUserBag)){
                $error = 0;
            }else{
                $error = 3;
            }
        }else{
            $sqlProp = "select * from props where id='".$propID."'";
            $resProp = $_pm['mysql']->getRecords($sqlProp);
            if(is_array($resProp)){
                if($resProp[0]['vary']==2){
                    $num = 1;
                }
                $sqlUserBag = "insert into userbag (`pid`, `uid`, `sell`, `vary`, `sums`, `stime`, `psell`, `petime`, `psum`, `psj`, `bsum`, `zbing`, `buycode`, `cantrade`, `pyb`) value ('".$resProp[0]['id']."','".$userID."','".$resProp[0]['sell']."','".$resProp[0]['vary']."','".$num."','".time()."','0','0','0','0','0','0','0','0','0')";
                //insert into userbag (`pid`, `uid`, `sell`, `vary`, `sums`, `stime`, `psell`, `petime`, `psum`, `psj`, `bsum`, `zbing`, `buycode`, `cantrade`, `pyb`) value ('751','94','1','1','3','0','0','0','0','0','0','0','0','0','0')
                if($_pm['mysql']->query($sqlUserBag)){
                    $error = 0;
                }else{
                    $error = 1;
                }
            }else{
                $error = 2;
            }
        }
        break;
    
    default:
        break;
}



echo $error;

?>