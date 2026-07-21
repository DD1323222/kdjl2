<?php
/*=============================================================================
#     FileName: gold_usage_sta.php
#         Desc:
#       Author: ifree
#        Email: zhangyongxiang@webgame.com.cn
#     HomePage: webgame
#      Version: 0.0.1
#   LastChange: 2011-12-07 17:04:59
#      History:
=============================================================================*/

require_once("../config/config.game.php");

//ini_set('display_errors',true);
define('SECRET_KEY','99754106633f94d350db34d548d6091a');
/**
 * 获取本日消费统计
 *
 * @return json object
 * @author ifree
 **/
function GoldUsageStat($from,$to)
{
	global $_pm;
	$startTime=null;
	$endTime=null;
	$error=null;
	if($from&&$to){
		$from = intval($from);
		$to = intval($to);
		$startTime = min($from, $to);
		$endTime = max($from, $to);
	}else{
		$now = time();
		$startTime=mktime(0,0,0,date("m",$now),date("d",$now)-1,date("Y",$now));
		$endTime=mktime(23,59,59,date("m",$now),date("d",$now),date("Y",$now));
	}
	$sql=<<<EOF
select p.id,p.name,sum(b.yb) yb,sum(b.nums) nums,p.yb price from props p inner join yblog b
on b.pname=p.name and p.yb>0 and b.buytime between %d and %d group by p.id,p.name,p.yb
EOF;
	$result=$_pm['mysql']->getRecords(sprintf($sql,$startTime,$endTime));

	if(is_array($result)){
		$records=count($result);
		$nums=0;
		$equips=0;
		foreach ($result as $key=>$value) {

			$result[$key]['name']=urlencode(kdjlSafeIconv('gbk','utf-8',$value['name']));
			$nums+=intval($value['yb']);
			$equips+=intval($value['nums']);
		}

		return genOutput(
			array(
				'records'=>$records,
				'moneyPayed'=>$nums,
				'equipBought'=>$equips,
				'details'=>($result)
			)
		);
	}else{
		return genOutput(
			array(
				'records'=>'0',
				'moneyPayed'=>'0',
				'equipBought'=>'0',
				'detail'=>'empty set'
			)
		);
	}

}

function validate($date,$time,$flag){
	$fixedFlag = md5($date.$time.SECRET_KEY);
	$legacyFlag = md5($date+$time+SECRET_KEY);
	if($flag == $fixedFlag || $flag == $legacyFlag)
		return true;
	else return false;
}

function genOutput($arr)
{
	return json_encode($arr);
}

//process request

if(isset($_GET['date']) && isset($_GET['time']) && isset($_GET['flag']) && !is_array($_GET['date']) && !is_array($_GET['time']) && !is_array($_GET['flag'])){
	if(validate($_GET['date'],$_GET['time'],$_GET['flag'])){
		$dbg = (isset($_GET['dbg']) && !is_array($_GET['dbg'])) ? $_GET['dbg'] : '';
		if($dbg=='d'){
			$from = (isset($_GET['from']) && !is_array($_GET['from'])) ? $_GET['from'] : null;
			$to = (isset($_GET['to']) && !is_array($_GET['to'])) ? $_GET['to'] : null;
			die(GoldUsageStat($from,$to));
		}else
			die(GoldUsageStat(null,null));
	}else{
		die(genOutput(array(
			'Error'=>-2,
			'ExtMsg'=>'validation error'
		)));
	}

}else{
	die(
		genOutput(array(
			'Error'=>-1,
			'ExtMsg'=>'invalid arguments'
		))
	);
}

?>
