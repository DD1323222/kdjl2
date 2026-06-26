<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: tanwei

*@Write Date: 2009.03.23
*@Update Date: 2009.03.23
*@Usage: 月末将当月VIP反馈积分保存至另外一字段
*@Note: none
*/
require_once('config/config.game.php');
$m = new memory();
$db = new mysql();
$timearr1 = array('01','03','05','07','08','10',12);
$timearr2 = array('04','06','09',11);
$times = date("Y-m-d H:i:s");
$arr = explode("-",$times);
if(in_array($arr[1],$timearr1))
{
	$day = 31;
}
else if(in_array($arr[1],$timearr2))
{
	$day = 30;
}
else
{
	if($arr[0] % 4 == 0)
	{
		$day = 29;
	}
	else
	{
		$day = 28;
	}
}
$time1 = $arr[0].$arr[1].$day."235959";
$time2 = date("YmdHis");

$a = $arr[0].$arr[1]."01000000";
$time3 = strtotime($a);


//每天统计一次人民币道具每月的售出和使用情况;
$atime = date("Hi");

/*while(intval(date("Hi"))<2359){
	sleep(5);
}*/
//if($atime == "2359")
//{
	$tm1 = strtotime($time1);
	$tm2 = strtotime($time2);
	$sql = "SELECT * from yblog where buytime >= {$time3}";//在这段时间的元宝售出记录
	$ybarr = $_pm['mysql'] -> getRecords($sql);
	$namearr = array();
	if(is_array($ybarr))
	{
		foreach($ybarr as $v)
		{
			if(!in_array($v['pname'],$namearr))
			{
				$arrs[] = $v;
				$namearr[] = $v['pname'];
			}
			else
			{
				foreach($arrs as $k => $vs)
				{
					if($vs['pname'] == $v['pname'])
					{
						$arrs[$k]['nums'] += $v['nums'];
					}
				}
			}
		}
	}
	$props = unserialize($m->get(MEM_PROPS_KEY));
	foreach($props as $p)
	{
		foreach($arrs as $k => $av)
		{
			if($av['pname'] == $p['name'])
			{
				$arrs[$k]['pid'] = $p['id'];
				$sql = "SELECT sum(sums) as sums,sum(bsum) as bsum,sum(psum) as psum from userbag where pid = {$p['id']}";
				//$sql = "SELECT sum(sums) as sums,sum(bsum) as bsum,sum(psum) as psum from userbag where pid = {$p['id']} and types != 0";
				$result = $_pm['mysql'] -> getOneRecord($sql);
				$nums = $result['sums'] + $result['bsum'] + $result['psum'];
				$arrs[$k]['snums'] = $nums;
			}
			else
			{
				continue;
			}
		}
	}
	foreach($arrs as $ams)
	{
		$tt = time();
		$sql = "SELECT times FROM monlog where pid = {$ams['pid']} ORDER BY id desc limit 0,1";
		$ar = $_pm['mysql'] -> getOneRecord($sql);
		if(is_array($ar))
		{
			$abc = $tt - $ar['times'];
			if($abc < 60)
			{
				break;
			}
			else
			{
				$sql = "INSERT INTO monlog(pid,times,snums,mnums) values({$ams['pid']},{$tt},{$ams['snums']},{$ams['nums']})";
				$_pm['mysql'] -> query($sql);
			}
		}
		else
		{
			$sql = "INSERT INTO monlog(pid,times,snums,mnums) values({$ams['pid']},{$tt},{$ams['snums']},{$ams['nums']})";
			$_pm['mysql'] -> query($sql);
		}
	}
//}

if($time1 == $time2)
{
	//for vip
	$_pm['mysql'] -> query("update player set viplast = vip,vip = 0");
}
?>