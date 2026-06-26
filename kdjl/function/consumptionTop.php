<?php
require_once('../config/config.game.php');
secStart($_pm['mem']);
$sql='select sum(yb) fee,nickname from yblog where buytime>'.strtotime(date("Y-m-d ").'00:00:00').' and buytime<'.strtotime(date("Y-m-d ").'20:59:59').' group by nickname order by sum(yb) desc limit 3';
$rows = $_pm->getRecords($sql);
function givePrize($name,$pstr,&$tsk)
{
	global $_pm;
	$user=$_pm['mysql']->getOneRecord('select id from player where name="'.$name.'" limit 1');
	if(!$user)
	{
		echo mysql_error();
		return;
	}
	$prize=explode('|',$pstr);
	foreach($prize as $p)
	{
		$t=explode(':',$p);
		if(!$tsk->saveGetPropsMore($t[0],$t[1],0,$user['id']))
		{
			$log='insert into gamelog set buyer="'.date('Ymd').'",vary=239,seller='.$user['id'].',ptime='.time().',pnote="发放奖励失败,用户:'.$name.',奖品id:'.$t[0].',数量:'.$t[1].'"';
		}else{
			$log='insert into gamelog set buyer="'.date('Ymd').'",vary=239,seller='.$user['id'].',ptime='.time().',pnote="发放奖励成功,用户:'.$name.',奖品id:'.$t[0].',数量:'.$t[1].'"';
		}
		$_pm['mysql']->query($log);
	}
}
if($rows)
{
	$memtimeconfig = unserialize($_pm['mem']->get('db_timeconfignew'));
	$config=$memtimeconfig['consumptionTop'][0];
	if($config['starttime']==0)
	{
		die("活动没有开启");
	}
	if($_GET['act']=='show')
	{
		/*foreach($rows as $row)
		{
			
		}*/
		echo '<pre>
		line='.__LINE__.'
		';
		var_dump($rows);
		echo '</pre>
		';
	}
	else if($_GET['act']=='calc')
	{
		if($config['starttime']>date('H') || $config['endtime']<date('H'))
		{
			die('只有 '.$config['starttime'].' 至 '.$config['endtime'].' 可以领奖！');
		}
		$a = getLock(1);
		$ck=$_pm['mysql']->getOneRecord('select id from gamelog where vary=239 and seller = '.$_SESSION['id'].' AND buyer="'.date('Ymd').'" limit 1');//检查发奖
		if(!$ck)
		{
			$prizes=explode(',',$config['days']);
			$task=new task();
			$top = 100;
			foreach($rows as $rk => $rv){
				if($rv['nickname'] == $_SESSION['name']){//判断是否是消费前三名
					$top = $rk;
					$newrow = $rv;
					break;
				}
			}
			if($top == 100){
				die('很遗憾，您没有进入前三名，详情请查看【公告牌】！');
			}
			foreach($prizes as $k=>$v)
			{
				if($k >= $top){
					$res = explode(';',$v);
					if($res[1] < $newrow['fee']){
						//die('消费不足 '.$res[1].' 不能领奖！');
						givePrize($newrow['nickname'],$res[0],&$task);
						$flag = 1;
						break;
					}
				}
				givePrize($newrow['nickname'],$res[0],&$task);
			}
		}else {
			die('已经领奖了！');
		}
		realseLock();
	}
}else{
	if($_GET['act']=='show')
	{
		echo '还没有消费,赶紧购买物品赢取大奖!';
	}
}
?>