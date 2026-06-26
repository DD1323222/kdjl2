<?php
ini_set("display_errors",true);
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');
secStart($_pm['mem']);
function msg($m,$js='')
{
	realseLock();
	die($m);
}
$a = getLock($_SESSION['id']);
if(!is_array($a)){
	msg('服务器繁忙，请稍候再试！');
}
require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
$s=new socketmsg();
if($_GET['op'] == 'fortress'){//要塞翻牌
	$setting = $_pm['mem']->get('db_welcome1');
	if(!is_array($setting)) $setting=unserialize($setting);
	if(!is_array($setting))
	{
		msg('后台配置数据读取失败(1)！'.print_r($setting,1));
	}
	if(!isset($setting['fortress']))
	{
		msg('缺少活动开启设定(fortress)！');
	}
	
	if(!isset($setting['fortress_time']))
	{
		msg('缺少活动开启设定(fortress_time)！');
	}
	
	$time_settings=explode("|",$setting['fortress_time']);
	$w=date('w');
	$hm=date('His');
	if($w==0)
	{
		$w=7;
	}
	$time_flag=false;
	foreach($time_settings as $s1)
	{
		$tmp=explode(',',$s1);
		//1,2100,2105,2130,2135
		if($w==$tmp[0])
		{
			if($hm>=$tmp[2]&&$hm<=$tmp[3])
			{
				$time_flag=true;
			}
			break;
		}
	}
	
	if(!$time_flag){
		msg('1');
	}
	
	
	/*if($_SESSION['fortress_pass'] != 2){
		msg('非法进入'.$_SESSION['fortress_pass']);
	}*/
	$srctime = 3;
	#################增加一个间隔时间################
	$time = $_SESSION['time'.$_SESSION['id']];
	if(empty($time)){	
		$_SESSION['time'.$_SESSION['id']] = time();
	}else{
		$nowtime = time();
		$ctime = $nowtime - $time;
		if($ctime < $srctime){
			msg('服务器繁忙，请稍候操作！');
		}
		else{
			$_SESSION['time'.$_SESSION['id']] = time();
		}
	}
	
	$id = intval($_GET['id']);
	if($id < 1 || $id > 30){
		msg('服务器繁忙，请稍候操作！');
	}
	$fortress_num = unserialize($_pm['mem']->get('fortress_num'.date('md').'_'.$_SESSION['id']));
	if($fortress_num >= 30){
		msg('您已经翻了30张了，今天不能再翻！');
	}
	if(!$fortress_num)
	{
		$fortress_num=0;
	}
	$fortress_num++;
	
	//得到当前信息
	$sql = 'SELECT bb_id,at_section_num FROM fortress_users_'.date("Ymd").' WHERE user_id = '.$_SESSION['id'];
	$fortress_arr = $_pm['mysql'] -> getOneRecord($sql);
	if(!is_array($fortress_arr)){
		//msg('你没有参加要赛活动！！'.var_dump($fortress_arr).'sql:'.$sql);
		realseLock();
		die('<!--quit-->');
	}

	$fortress_users=$_pm['mysql']->getRecords('select bb_id from fortress_users_'.date("Ymd").' where user_id!='.$_SESSION['id'].' and at_section_num='.$fortress_arr['at_section_num']);
	$ct=count($fortress_users);
	if($ct<2){
		realseLock();
		$_pm['mysql']->query('delete from fortress_users_'.date("Ymd").' where at_section_num='.$fortress_arr['at_section_num']);
		die('<!--quitmen-->');
	}

	//80%几率遇怪
	//计数
	$_pm['mem']->set(array('k'=>'fortress_num'.date('md').'_'.$_SESSION['id'],'v'=>$fortress_num));
	$num=rand(1,10);
	if($num <= 8){//遇怪
		$_SESSION['fortress_card_id'] = $id;//跳到战斗界面
		$rs=$s->sendMsg(iconv('utf-8','utf-8','SYSN|fortress_boss->2秒后开始战斗'),$_SESSION['id']);
		msg('即将开始战斗');
	}
	$sql = 'SELECT id,effect FROM fortress_card WHERE section_num = '.$fortress_arr['at_section_num'];
	//echo $sql;
	$tarot = $_pm['mysql'] -> getRecords($sql);

	if(!is_array($tarot)){
		msg($sql.'数据库中没有数据！');
	}
	$max = count($tarot) - 1;
	$rand = rand(0,$max);
	$newTarot = $tarot[$rand];
	
	$effect = explode('|',$newTarot['effect']);
	
	foreach($effect as $v){
		$arr = explode(':',$v);
		switch ($arr[0]){
			case 'money_add'://单人获得金币奖励
				moneyAdd($_SESSION['id'],$arr[1]);
				if($arr[1] < 0){
					$ret='扣除金币：'.$arr[1];
				}else{
					$ret='获得金币：'.$arr[1];
				}
				break;
			case 'exp_add'://单人获得经验奖励
				$t = new task();
				$t->saveExps($arr[1]);
				$ret='获得经验：'.$arr[1];
				break;
			case 'giveitems'://单人获得道具奖励
				$itemstr = str_replace('giveitems:', '', $v);
				$ret = getItem($itemstr);
				break;
			default:
				echo '牌：'.$newTarot['id'].'填写有误，'.$newTarot['effect'].'数据有误！';
				break;
		}
	}
	
	//要把所翻的牌存起来
	$ar = unserialize($_pm['mem']->get('fortress_card_info_'.date('md').'_'.$_SESSION['id']));
	$ar[]=array('id' => $id,'img' => $ret);
	$_pm['mem']->set(array('k'=>'fortress_card_info_'.date('md').'_'.$_SESSION['id'],'v'=>$ar));
	echo $ret;
	//echo $rs.'aaa';
	//echo '['.__LINE__."]<br>";
}
realseLock();



function moneyAdd($uid,$num){
	global $_pm;
	if($num < 0){
		$_pm['mysql'] -> query('UPDATE player SET money = money +'.$num.' WHERE id = '.$uid.' AND money >= '.$num);
		if(mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){
			$_pm['mysql'] -> query('UPDATE player SET money = 0 WHERE id = '.$uid);
		}
	}else{
		//echo 'UPDATE player SET money = money +'.$num.' WHERE id = '.$uid;
		$_pm['mysql'] -> query('UPDATE player SET money = money +'.$num.' WHERE id = '.$uid);
	}
}

function getItem($str){
	global $_pm,$s;//749:1:3:2
	$flag = 0;
	$propslist = explode(',', $str);
	if (is_array($propslist)){
		$task = new task();
		foreach ($propslist as $k => $v){
			$inarr = explode(':', $v);
			if(is_array($inarr)){
				if (rand(1, intval($inarr[2])) == 1){	//  rand hits
					$task->saveGetPropsMore($inarr[0],$inarr[1]);
					$flag = 1;
					$prs = $_pm['mysql']->getOneRecord("SELECT name FROM props WHERE id={$inarr[0]}");
					if(empty($retstr)){
						$retstr = '获得 '.$prs['name'].'&nbsp;'.$inarr[1].' 个';
					}else{
						$retstr .= ",".$prs['name'].'&nbsp;'.$inarr[1].' 个';
					}
					if($inarr[3] == '2'){//发公告
						$msg=iconv('utf-8','utf-8','恭喜玩家 '.$_SESSION['nickname'].'在女神要塞中幸运地得到了女神的眷顾，获得'.$prs['name'].'&nbsp; 奖励&nbsp; '.$inarr[1].' 个');
						$s->sendMsg('an|'.$msg,'__ALL__');
					}	
				}
			}
		}
		if($flag == 0){
			return '真遗憾，您没有获得任何道具！';
		}
		return $retstr;
	}
}
$_pm['mem']->memClose();
?>