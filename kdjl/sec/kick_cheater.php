<?php
function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
function signWaiGua(&$wgUser,$type,$Time)
{
	if($type=='mustvisit'){
		if(
				!in_array($_SESSION['id'],$wgUser['wgList'])
				||$wgUser['wgList'][$_SESSION['id']][$type][1]!=date("YmdH")
		)
		{			
			$wgUser['wgList'][$_SESSION['id']][$type] = array($_SESSION['username'],date("YmdH"));
		}
	}else{
		if($wgUser['wgList'][$_SESSION['id']][$type][1]!=date("YmdH"))
		{
			$wgUser['wgList'][$_SESSION['id']][$type] = array($_SESSION['username'],date("YmdH"),1);		
		}else{
			$wgUser['wgList'][$_SESSION['id']][$type][2]++;
		}		
	}
}

if(isset($_SESSION['id'])){
	//初始化
	$Time = time();
	$userIsWaigua = false;
	$wgUser = unserialize($_pm['mem']->get("wgUser"));
	if(!isset($_SESSION['login_time']))
	{
		$_SESSION['login_time'] = $Time;
	}	
	if(empty($wgUser)) $wgUser=array(
									'userList'=>array(),		//用户登陆时间和最后活动时间
									'visitList'=>array(),		//按先后访问的文件的访问记录：要卖东西，必须先到商店
									'configMVVED'=>array(),		//每个用户访问必须要访问的文件的情况
									'wgList'=>array()			//被认为是使用外挂的用户的信息
									);
	if($wgUser['userList'][$_SESSION['id']]['login_time']!=$_SESSION['login_time'])
	{
		$wgUser['visitList'][$_SESSION['id']]=array();
		$wgUser['configMVVED'][$_SESSION['id']]=array();
		$wgUser['wgList']=array();
		$wgUser['userList'][$_SESSION['id']]['login_time'] = $_SESSION['login_time'];
	}
	
	//$wgUser['userList'][$_SESSION['id']]['login_time'] = $Time;
	
	$wgUser['userList'][$_SESSION['id']]['last_active_time'] = $Time;

	//访问顺序
	$configVORD= array(
					'/function/getTaskinfo.php'=>'/function/getTask.php',
					'/function/Base_Mod.php'=>'/function/baseGate.php',
					'/function/getBag.php'=>'/function/props2Depot.php',
					'/function/Pai_Mod.php'=>'/function/paisellGate.php',
					'/function/Props_Mod.php'=>'/function/PropsGate.php'
					);
	$configVORD1= array(					
					'/function/Props_Mod.php'=>'/function/sellBag.php'
					);
	if(array_key_exists($_SERVER['PHP_SELF'],$configVORD))
	{
		$wgUser['visitList'][$_SESSION['id']][$_SERVER['PHP_SELF']]=$Time;
	}
	if(
		in_array($_SERVER['PHP_SELF'],$configVORD)||
		in_array($_SERVER['PHP_SELF'],$configVORD1))
	{
		$configVORDReverse = array_merge(array_flip($configVORD),array_flip($configVORD1));
		//$configVORDReverse = array_flip($configVORD);
		$visitTime = $wgUser['visitList'][$_SESSION['id']][$configVORDReverse[$_SERVER['PHP_SELF']]];
		if(empty($visitTime)||$visitTime+1800<$Time)
		{	
			signWaiGua(&$wgUser,'visitorder',$Time);
		}
	}
	
	//必须访问的文件外挂不会访问的文件,发现可能是外挂，在一个随机的时间做出处理
	$configMV = array(
						'/function/onlineStat.php',
						'/function/ext_Online.php',
						'/function/exit.php',
						'/function/Welcome_Mod.php'
					);
	
	if(
		in_array(
			$_SERVER['PHP_SELF'],
			$configMV	
		)		
	)
	{
		$wgUser['configMVVED'][$_SESSION['id']][$_SERVER['PHP_SELF']] = 1;
	}else{
	}
	
	if(
		$Time-$wgUser['userList'][$_SESSION['id']]['login_time']>40 &&
		(
			empty($wgUser['configMVVED'][$_SESSION['id']]) ||
			count($wgUser['configMVVED'][$_SESSION['id']])+1 < count($configMV)
		)
	)
	{
		if(
			$Time-$wgUser['userList'][$_SESSION['id']]['login_time'] > 40+6*rand(1,10)+1.2*rand(1,100)
		)
		{		
			signWaiGua(&$wgUser,
				'mustvisit',$Time
			);		
		}
	}
	
	$_pm['mem']->set(array('k'=>"wgUser",'v'=>$wgUser));
}
?>