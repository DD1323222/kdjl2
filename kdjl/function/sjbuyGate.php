<?php
//die('维护中……');
//exit();
require_once('../config/config.game.php');

$m	= $_pm['mem'];
$db = &$_pm['mysql'];
$u	= $_pm['user'];
secStart($m);
$err = 0;
//---------------------------
$user	= $u->getUserById($_SESSION['id']);
$bags    = $u->getUserBagById($_SESSION['id']);
$arr = $_pm['mysql'] -> getOneRecord("SELECT sj FROM player_ext WHERE uid = {$_SESSION['id']}");
if(is_array($arr)){
	$user['sj'] = $arr['sj'];
}else{
	$user['sj'] = 0;
}
if($user===FALSE) {die('1');}

$bid = intval($_REQUEST['bid']); // table: props => id
$n	 = intval($_REQUEST['n']); 

if(lockItem($bid) === false)
{
	die('已经在处理了！');
}

if($n <= 0)
{
	unLockItem($bid);
	die('2');
}

if( !is_int($bid) || $bid<1 || $n<1) die(2);

/*$wp= $m->dataGet(array('k' => MEM_PROPS_KEY, 
					   'v' => "if(\$rs['id'] == '{$bid}' && \$rs['yb']>0) \$ret=\$rs;"
				 ));*/
$wp = $_pm['mysql'] -> getOneRecord("SELECT * FROM props WHERE id = $bid and sj > 0");
//增加自动上下架的功能
if(!empty($wp['timelimit'])){
	$limitarr = explode('|',$wp['timelimit']);
	$nowtime = date('YmdHi');
	if(!empty($limitarr[0]) && $nowtime < $limitarr[0]){
		unLockItem($bid);
		die('101');
	}
	if(!empty($limitarr[1]) && $nowtime > $limitarr[1]){
		unLockItem($bid);
		die('101');
	}
}
//增加自动上下架的功能在这里结束
// Get current bag props num.
$bagnum = 0;
if (is_array($bags))
{
	foreach ($bags as $k => $v)
	{
		if ($v['sums']>0 && $v['zbing']==0) $bagnum++;
	}
}

if (!is_array($wp)){
	unLockItem($bid);
	die('3');
}
else if( ($wp['vary']==2 && ($n+$bagnum)>$user['maxbag']) || (($bagnum+1)>$user['maxbag']) ){
	unLockItem($bid);
	die('4');
}
else
{
	$price = $wp['sj']*$n;
	if(empty($price))
	{
		unLockItem($bid);
		die("3");
	}
	$nowCoin = $user['sj'];

	if ($price > $nowCoin)
	{
		unLockItem($bid);
		die('10');
	}
	else
	{   
		//----------------------------
		$now = time();
		$number = $n;
		
		$db -> query("INSERT INTO gamelog (ptime,seller,buyer,pnote,vary) VALUES (".time().",{$_SESSION['id']},{$_SESSION['id']},'购买道具{$wp['name']} {$n} 个',101)");
		
		$user['sj'] = $nowCoin-$price;
		
		#########################################################

		if ($wp['vary']==2) //不能叠加
		{ 
			for ($i=0; $i<$n; $i++)
			{
			    $db->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
							VALUES(
								   {$user['id']},
								   {$bid},
								   {$wp['sell']},
									2,
								   1,
								   unix_timestamp()
								  );
						  ");
			}
		}
		else
		{		
			$arrobj = new arrays();
			$ret = false;
			if(is_array($bags))
			foreach($bags as $k=>$v)
			{
				if($v['uid']==$_SESSION['id'] && $v['pid']==$bid) $ret=$v;
			}
			
			if (is_array($ret))
			{

				$db->query("UPDATE userbag
							   SET sums=sums+{$n},stime=".time()."
							 WHERE id={$ret['id']}
						  ");
						  
			}
			else //create new data
			{
				$db->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
							VALUES(
								   {$user['id']},
								   {$bid},
								   {$wp['sell']},
									1,
									{$n},
								   unix_timestamp());
						  ");
						
			}
		}
		/*$db->query("update player set yb={$user['yb']},useyb={$useyb},score=score + {$score},active_useyb={$active_useyb},active_score=active_score+{$active_score} where id={$_SESSION['id']}");*/
		$db->query("update player_ext set sj={$user['sj']} where uid={$_SESSION['id']}");
	}	// end inner else
}
unset($user,$wp);
$m->memClose();
echo $err;
unLockItem($bid);
?>