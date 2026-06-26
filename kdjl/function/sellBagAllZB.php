<?php
/**
* 一键卖背包装备
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.02
*@Update Date: 2008.05.22
*@Usage:User Bag sell
*/
require_once('../config/config.game.php');

secStart($_pm['mem']);

$err = 0;
$user		= $_pm['user']->getUserById($_SESSION['id']);
$bags		= $_pm['user']->getUserBagById($_SESSION['id']);

if($_SESSION['id']){
    $sql = "select `pid`,`id` from userbag where uid='".$_SESSION['id']."' and vary = '2' and sums>'0' and zbing = '0'";
}

$res = $_pm['mysql']->getRecords($sql);


del_bag_expire();


while(list($resK,$resV) = each($res)){
    $isZBTrue = false;//判断是不是装备，如果不是装备不能卖。
    while(list($kUserBagid,$vUserBagid) = each($resV)){
        if($kUserBagid == 'pid'){
            $propSql = "select varyname from props where id='".$vUserBagid."'";
            $resProp = $_pm['mysql']->getRecords($propSql);
            if($resProp[0]["varyname"] == 9){
                $isZBTrue = true;
            }
            
        }
        if($kUserBagid == 'id' && $isZBTrue){
            // Check bid.
            $bid = intval($vUserBagid); // table: userbag -> id
            $n	 = intval(1);
            if(lockItem($bid) === false)
            {
            	die('已经在处理了！');
            }
            if($n <= 0)
            {
            	unLockItem($bid);
            	die('2');
            }
            
            if ($_pm['user']->check(array('int' => $bid, 'int' => $n)) === FALSE) {
            	unLockItem($bid);
            	die('2');
            }
            
            $wp = false;
            foreach ($bags as $k => $v)
            {
            	if ($v['uid'] == $_SESSION['id'] && $v['id'] == $bid) 
            	{
            		$wp = $v; 
            		break;
            	}
            }
            
            if (!is_array($wp))
            {
            	unLockItem($bid);
            	die('3');
            }
            else if(!empty($wp['zbing']))
            {
            	unLockItem($bid);
            	die("10");//装备在身上的不能卖出。
            }
            else
            {
            	if ($n > $wp['sums']) {
            		unLockItem($bid);
            		die('10');
            	}
            
            	if ($wp['vary'] == 2)	//	Can't repeat!
            	{
            		$money = $wp['sell'];
            		$_pm['mysql']->query("DELETE FROM userbag
            					 WHERE uid={$_SESSION['id']} and id={$bid}
            				  ");
            	}
            	else
            	{	
            		$money = $wp['sell']*$n;
            		$_pm['mysql']->query("UPDATE userbag
            					   SET sums=sums-{$n}
            					 WHERE uid={$_SESSION['id']} and id={$bid} and sums>={$n}
            				  ");
            	}
            	$user['money'] += $money;
            
            	$_pm['mysql']->query("UPDATE player 
            				   SET money={$user['money']}
            				 WHERE id={$_SESSION['id']} and {$user['money']} > 0
            			  ");
            }
        }
        
    }
}
//$_pm['user']->updateMemUser($_SESSION['id']);
//$_pm['user']->updateMemUserbag($_SESSION['id']);
$_pm['mem']->memClose();

echo $err;
unLockItem($bid);
?>