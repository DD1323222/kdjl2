<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.07.13
*@Usage: 奥运答题显示模块
* 加入奥运时间限制。
× 最大答题次数限制。
*@Note: none
*/
require_once('../config/config.game.php');
secStart($_pm['mem']);
$_SESSION[$_SESSION['id']."aoyun"] = "checked";
$user	 = $_pm['user']->getUserById($_SESSION['id']);
//Word part.
//$taskword= taskcheck($user['task'],6);

$aoyunti = unserialize($_pm['mem']->get(MEM_AOYUN_KEY));
$num = count($aoyunti) - 1;

// 加入时间段限制开始
// time limit start
$tl = intval(date("H",time()));
$day = intval(date("Ymd"));
$timearr1 = unserialize($_pm['mem']->get(MEM_TIMENEW_KEY));
$timearr = $timearr1['dati'];
foreach($timearr as $k => $v)
{
	$dayarr = explode("-",$v['days']);
	if($tl >= $v['starttime'] && $tl < $v['endtime'])
	{
		$checktime = 1;
		break;
	}
}

if($day < $dayarr[0] || $day > $dayarr[1])
{
	exit;
}
// 加入时间段限制结束

// 检查用户是否参与过该活动。




$rs = $_pm['mysql']->getOneRecord("SELECT *
									 FROM aoyun_player 
									WHERE uid={$_SESSION['id']}");

if (!is_array($rs))
{
	$questionarrs = randq();
	$rs['tid'] = $questionarrs[1]['id'];
	$rs['qsums']=1;
	$_pm['mysql']->query("INSERT INTO aoyun_player(uid,stime,tid,qsums,oksum,times,result)
						  VALUES({$_SESSION['id']},unix_timestamp(),{$rs['tid']},1,0,0,0)
						");
	
} 
else if (($checktime == 1) && ($rs['qsums']==31))
{
	$questionarrs = randq();
	$rs['qsums']=1;
	$nowtime = time();
	$ctime = $nowtime - $rs['stime'];
	if (($checktime == 1) && ($ctime > 3600))
	{
		$_pm['mysql']->query("UPDATE aoyun_player
								 SET qsums=1,
								     tid={$rs['tid']},
									 stime=unix_timestamp(),
									 oksum=0,
									 result=0,
									 times=0
							   WHERE uid={$_SESSION['id']}
							");
	}
}else{
	$tiarr = unserialize($_pm['mem']->get('quest'.$_SESSION['id']));
	if(is_array($tiarr)){
		$questionarrs = $tiarr;
	}else{
		$questionarrs = randq();
	}
	
}
$_SESSION['datiid'.$_SESSION['id']] = "";
foreach($questionarrs as $k=>$v)
{
	foreach($v as $kk=>$vv)
	{//echo $v['id'].'<br />';
		$_SESSION['datiid'.$_SESSION['id']][$v['id']] = 1;
	}
}//print_r($_SESSION['datiid'.$_SESSION['id']]);
$questionarr = json_encode($questionarrs);

$rs['tid'] = $questionarrs[$rs['qsums']]['id'];
if(!empty($rs['tid']))
{
	$_pm['mysql']->query("UPDATE aoyun_player
								 SET
								     tid={$rs['tid']}
							   WHERE uid={$_SESSION['id']}
							");
}
// 获得所答题信息。
//$qst = $_pm['mysql']->getOneRecord("SELECT * FROM aoyun WHERE id={$rs['tid']}");
$qst = $aoyunti[$rs['tid']];



//@Load template.
$tn = $_game['template'] . 'tpl_aoyunti.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);
	
	$src = array('#word#',
				 '#order#',
				 '#title#',
		         '#akey#',
				 '#bkey#',
		         '#ckey#',
		         '#dkey#',
				 '#questionarr#'
				);
	$des = array(
				 $taskword,
		         $rs['qsums'],
		         $qst['title'],
		         $qst['a'],
		         $qst['b'],
		         $qst['c'],
		         $qst['d'],
				 $questionarr
				);
	$king = str_replace($src, $des, $tpl);
}
// gzip echo. if maybe.
ob_start('ob_gzip');
echo $king;
ob_end_flush();



function randq( )
{
	global $_pm,$aoyunti;
	$ti = "";
	$idarr = array();
	//$ret = $_pm['mysql']->getRecords("SELECT * FROM aoyun");
	//$ret = unserialize($_pm['mem']->get(MEM_AOYUN_KEY));
	
	$num1 = count($aoyunti) - 1;
	for($i = 1;$i <= 30;$i++)
	{
		$num = 1;
		$num = rand(1,$num1);
		$ct=0;
		$tmp=0;
		while
		(
			($aoyunti[$num]['title'] == "" || in_array($num,$idarr))
			&&
			$ct<10
		)
		{			
			$ct++;
			$num = rand(1,$num1);
			if($aoyunti[$num]['title'] != "")
			{
				$tmp = $num;
			}
		}
		if($ct>9)
		{
			$num = $tmp>0?$tmp:rand(1,$num1);
		}
		
		$idarr[] = $aoyunti[$num]['id'];
		$ti[$i]['id'] = $aoyunti[$num]['id'];
		$ti[$i]['title'] = $aoyunti[$num]['title'];
		$ti[$i]['a'] = $aoyunti[$num]['a'];
		$ti[$i]['b'] = $aoyunti[$num]['b'];
		$ti[$i]['c'] = $aoyunti[$num]['c'];
		$ti[$i]['d'] = $aoyunti[$num]['d'];
	}
	 $_pm['mem']->set(array('k' => 'quest'.$_SESSION['id'], 'v' => $ti));
	return $ti;
}
?>