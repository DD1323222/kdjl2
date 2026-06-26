<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.19
*@Update Date: 2008.10.28
*@Usage: study skill of user bb.
*@Memo:
  0: 数据错误
  捕捉功能方法修改。
*/

require_once('../config/config.game.php');
define(MEM_FIGHT_KEY, $_SESSION['id'] . 'fight');

$arrobj = new arrays();
secStart($_pm['mem']);

$bid =intval($_REQUEST['pid']); // bag props id.table:userbag.使用道具ID（精灵球ID）

if( !is_int($bid) || $bid<1 ) die('0');

$user	 = $_pm['user']->getUserById($_SESSION['id']);//用户信息
$sp	     = $_pm['user']->getUserItemById($_SESSION['id'],$bid);//用户包裹信息
$allbb   = $_pm['user']->getUserPetById($_SESSION['id']);//用户宠物信息
$memgpcid = unserialize($_pm['mem']->get('db_gpcid'));
$mempropsid = unserialize($_pm['mem']->get('db_propsid'));

$all = 0;
if (is_array($allbb))
{
	foreach($allbb as $x => $y)
	{
		if($y['muchang']==1) continue;
		$all++;
	}
	if ($all>=3) die("6");//您携带的宝宝个数已达最大！
}

$test = $_SESSION['fight'.$_SESSION['id']];


if(isset($_SESSION['catch_gw_info'])&&$_SESSION['catch_gw_info']==$_SESSION['fight'.$_SESSION['id']]['gid'])
{
	stopUser2(52);//,true
	die('0');
}

$gs = $memgpcid[$test['gid']];
/*$gs = $_pm['mem']->dataGet(array('k'	=>	MEM_GPC_KEY,
			 		    'v'	=>  "if(\$rs['id'] == '{$test['gid']}') \$ret=\$rs;"
				 ));*/
				 //当前所打的怪物数据
$bb = $test;
if (!is_array($bb) || !is_array($gs)) die('-1');
else
{
	$bbrs = $arrobj->dataGet(array('k'	=>	MEM_BB_KEY,
			 		    	  'v'	=>  "if(\$rs['uid'] == '{$_SESSION['id']}' && \$rs['id']=='{$bb['bid']}') \$ret=\$rs;"
					 			),//当前所打怪的宠物数据
							$allbb
						   );
	if (!is_array($bbrs)) $bbrs['level']=0;
}

if (is_array($sp))
{
	
	$_SESSION['catch_gw_info'] = $test['gid'];
	
	$prs = $sp;//包裹信息。
	
	// 捕捉道具 和要被捕捉的怪物信息都正确，开始计算。
	if (is_array($prs) && is_array($gs))
	{
	
		if($prs['sums'] < 1)
		{
			die("20");
		}
		if($bid != $prs['id'])
		{
			die("20");
		}
		// Start count...
		// 实际捕捉率=[怪物捕捉值/（100－玩家宠物与怪物等级之差）]*（1－怪物当前HP值/怪物最大HP值）*100%+捕捉道具附加捕捉率
		
		//实际捕捉率＝（怪物捕捉值/100）*（1－怪物当前HP值/怪物最大HP值）*100%+捕捉道具附加捕捉率 
		
		// 结果格式：

		$pv = explode(':', $prs['effect']);
		
		if(strtolower($pv[0])=='getitems')//获取装备
		{
			$params = explode(",",$pv[1]);
			$theGPCs = explode("|",$params[0]);
			/*if(!in_array($_SESSION['fight'.$_SESSION['id']]['gid'],$theGPCs))
			{
				die("12");
			}*/
			
			
			
			
			$pzl = ($gs['catchv']/100)*(1-$bb['hp']/$gs['hp']);
			
			$randNum = $pzl*100;
			$a = $randNum==0?10000:intval(100/$randNum);
			$nvl = rand(1,$a);
			if($nvl == 1) 
			{
				$msg = "";
				$strarr = explode(",",$prs['effect']);
				$items = explode("|",$strarr[1]);
				foreach($items as $v)
				{
					$proparr = explode(":",$v);
					$randnum = rand(1,$proparr[1]);
					if($randnum == 1)
					{
						
						$prs = $mempropsid[$proparr[0]];
						/*$prs = $_pm['mem']->dataGet(array('k' => MEM_PROPS_KEY, 
													 'v' => "if(\$rs['id'] == '{$proparr[0]}') \$ret=\$rs;"
										  ));*/
										 
						
						
						$task = new task();
						$task->saveGetPropsMore($proparr[0],$proparr[2]);
						if($proparr[3] == "2")
						{
							
							$gpc = $memgpcid[$proparr[0]];
							/*$gpc = $_pm['mem']->dataGet(array('k' => MEM_GPC_KEY, 
													 'v' => "if(\$rs['id'] == '{$proparr[0]}') \$ret=\$rs;"
										  ));*/
							$task->saveGword("在 {$gpc['name']} 身上成功的发现了 {$prs['name']} {$proparr[2]} 个。");
						}
						$newstr = "恭喜您得到 {$prs['name']} {$proparr[2]} 个。";
						$_pm['mysql']->query("UPDATE userbag
						SET sums=abs(sums-1)
						WHERE id=$bid and sums > 0
						");
						die($newstr);
						break;
					}
				}
			}
			else{
			$_pm['mysql']->query("UPDATE userbag
					SET sums=abs(sums-1)
					WHERE id=$bid and sums > 0
					");
			}
		}
		else if(strtolower($pv[0])=='get')//获取装备
		{
			$theGPCs = explode("|",$pv[1]);			
			
			if(!in_array($_SESSION['fight'.$_SESSION['id']]['gid'],$theGPCs))
			{
				die("12");
			}
			
			$pvv = str_replace('%','',$pv[2])/100;
			
			$pzl = ($gs['catchv']/100)*(1-$bb['hp']/$gs['hp'])+$pvv;
			
			$randNum = $pzl*100;
			$a = intval(100/$randNum);
			$nvl = rand(1,$a);
			if($nvl == 1) // Catch ok.
			{
				//掉落物品获取。格式：道具ID：机率范围。
				$prpid = intval($pv[4]);
				$okidlist = $drop = "";
				if ($prpid === false || $prpid == 0 || $prpid == '') $drop = '无';
				else
				{
					$rarr = array($prpid);
					foreach ($rarr as $k => $v)
					{
						
						/*$prs = $_pm['mem']->dataGet(array('k' => MEM_PROPS_KEY, 
												 'v' => "if(\$rs['id'] == '{$v}') \$ret=\$rs;"
									  ));*/

						$prs = $mempropsid[$v];
						if( is_array($prs) )
						{
							$drop .= $prs['name'].',';
							$okidlist .= $v.',';
						} 
					}// end foreach.
					$drop = substr($drop, 0, -1);
					$okidlist = substr($okidlist, 0, -1);
					$_bag		 = $_pm['user']->getUserBagById($_SESSION['id']);
					saveGetProps($okidlist);
				}
				
				//发公告			
				if($pv['3'] == 2)
				{
					$task = new task();
					$task->saveGword("成功的获取了: ".$drop."，太爽了！");
				}
				
				$_pm['mysql']->query("UPDATE userbag
				SET sums=abs(sums-1)
				WHERE id=$bid and sums > 0
				");
				die('15');
			}else{
				$_pm['mysql']->query("UPDATE userbag
				SET sums=abs(sums-1)
				WHERE id=$bid and sums > 0
				");
				die('13');
			}
		}
		else if(strtolower($pv[0])=='catch')
		{
			if ($gs['catchid'] == 0) die('3'); // 此怪不能捕捉
			$pvv = str_replace('%','',$pv[2])/100;
			$gwidarr = explode("|",$pv[1]);
			if(!in_array($gs['id'],$gwidarr))
			{
				die("7");//不能捕捉此宝宝
			}
			
			
			
			$pzl = ($gs['catchv']/100)*(1-$bb['hp']/$gs['hp'])+$pvv;
			
			$randNum = $pzl*100;
			$nvl = rand(1, intval(100/$randNum));
			
			
			
			//$nvl = 1;
			
			if($nvl == 1) // Catch ok.
			{
				$newpetsid = $gs['catchid'];
				// Get new bb info.
						$membbid = unserialize($_pm['mem']->get('db_bbid'));
						$bb = $membbid[$newpetsid];
						/*$bb = $_pm['mem']->dataGet(array('k'	=>	MEM_BB_KEY,
						'v'	=>  "if(\$rs['id'] == '{$newpetsid}') \$ret=\$rs;"
						),
						$allbb
				 );*/
				if ($gs['wx'] != $bb['wx']) die('2');
				$czl = getCzl($bb['czl']);
				
				// insert into userbb.
				//$bbid= $newid = mem_get_autoid($m, MEM_ORDER_KEY, 'userbb');
				
				$uinfo = $user;
				$_pm['mysql']->query("INSERT INTO userbb(name,uid,username,level,wx,ac,mc,srchp,hp,srcmp,mp,skillist,stime,nowexp,
						lexp,imgstand,imgack,imgdie,hits,miss,speed,kx,remakelevel,remakeid,remakepid,czl,headimg,cardimg,effectimg)
				VALUES('{$bb['name']}','{$uinfo['id']}','{$uinfo['nickname']}','1','{$bb['wx']}',
				   '{$bb['ac']}','{$bb['mc']}','{$bb['hp']}','{$bb['hp']}','{$bb['mp']}','{$bb['mp']}','{$bb['skillist']}',unix_timestamp(),
				  '{$bb['nowexp']}','100','{$bb['imgstand']}','{$bb['imgack']}','{$bb['imgdie']}',
				   '{$bb['hits']}','{$bb['miss']}','{$bb['speed']}','{$bb['kx']}','{$bb['remakelevel']}',
				   '{$bb['remakeid']}','{$bb['remakepid']}','{$czl}','{$bb['headimg']}','{$bb['cardimg']}','{$bb['effectimg']}')
				");
				//发公告			
				if($pv['3'] == 2)
				{
					$task = new task();
					$task->saveGword("成功的捕捉到了 {$bb['name']} ，太有才了！");
				}
				/*获取刚插入宠物ID。*/
				$newbb = $_pm['mysql']->getOneRecord("SELECT id 
							  FROM userbb
							 WHERE uid={$_SESSION['id']}
							 ORDER BY stime DESC
							 LIMIT 0,1			                                         
						  ");
				$bbid = $newbb['id'];
				
				//修复只能有一种技能的bug技能，和吸血技能
				$arr = split(",", $bb['skillist']);
				foreach($arr as $av)
				{
					if(empty($av))
					{
						continue;
					}
					$newarr = explode(":",$av);
					if(empty($newarr[0]))
					{
						continue;
					}
					$memskillsysid = unserialize($_pm['mem']->get('db_skillsysid'));
					$jn = $memskillsysid[$newarr[0]];
					/*$jn = $_pm['mem']->dataGet(array('k'	=>	MEM_SKILLSYS_KEY,
						'v'	=>  "if(\$rs['id'] == '{$newarr[0]}') \$ret=\$rs;"
					));*/
					$ack  = split(",", $jn['ackvalue']);
					$plus = split(",", $jn['plus']);
					$uhp  = split(",", $jn['uhp']);
					$ump  = split(",", $jn['ump']);
					$img = split(",",$jn['imgeft']);
					$_pm['mysql']->query("INSERT INTO skill(bid,name,level,vary,wx,value,plus,img,uhp,ump,sid)
					VALUES({$bbid}, '{$jn['name']}','{$newarr['1']}','{$jn['vary']}','{$jn['wx']}','{$ack['0']}','{$plus['0']}','{$img['0']}',{$uhp['0']},{$ump['0']},{$jn['id']})
					");
				}
				// Get jn info.
				/*$jn = $_pm['mem']->dataGet(array('k'	=>	MEM_SKILLSYS_KEY,
						'v'	=>  "if(\$rs['id'] == '{$arr[0]}') \$ret=\$rs;"
				));
				$ack  = split(",", $jn['ackvalue']);
				$plus = split(",", $jn['plus']);
				$uhp  = split(",", $jn['uhp']);
				$ump  = split(",", $jn['ump']);
				获取刚插入宠物ID。
				$newbb = $_pm['mysql']->getOneRecord("SELECT id 
							  FROM userbb
							 WHERE uid={$_SESSION['id']}
							 ORDER BY stime DESC
							 LIMIT 0,1			                                         
						  ");
				$bbid = $newbb['id'];
				
				// Insert userbb jn.	
				//$newid = mem_get_autoid($m, MEM_ORDER_KEY,'skill');
				echo "INSERT INTO skill(bid,name,level,vary,wx,value,plus,img,uhp,ump,sid)
				VALUES({$bbid}, '{$jn['name']}','{$arr['1']}','{$jn['vary']}','{$jn['wx']}','{$ack['0']}','{$plus['0']}','{$jn['img']}',{$uhp['0']},{$ump['0']},{$jn['id']})
				";exit;
				$_pm['mysql']->query("INSERT INTO skill(bid,name,level,vary,wx,value,plus,img,uhp,ump,sid)
				VALUES({$bbid}, '{$jn['name']}','{$arr['1']}','{$jn['vary']}','{$jn['wx']}','{$ack['0']}','{$plus['0']}','{$jn['img']}',{$uhp['0']},{$ump['0']},{$jn['id']})
				");*/
				//减去精灵球
				$_pm['mysql']->query("UPDATE userbag
				SET sums=abs(sums-1)
				WHERE id={$prs['id']} and sums > 0
				");
				//$_pm['user']->updateMemUserbb($_SESSION['id']);
				//$_pm['user']->updateMemUsersk($_SESSION['id']);
				die('10');
			}
			else
			{ // Clear props.
				$_pm['mysql']->query("UPDATE userbag
				   SET sums=abs(sums-1)
				 WHERE id={$prs['id']} and sums > 0
				");
				//$_pm['user']->updateMemUserbag($_SESSION['id']);
			} // 捕捉机率太低。	 
		}
	}
}
$_pm['mem']->memClose();
echo "0";


/**
* @Usage: 存储用户得到的道具到用户包裹.
* @Param: String, format: 1,2,3
* @Logic: 
  如果用户包裹有此物品，如果可以折叠，直接累加，否则插入新纪录。
  >>增加物品说明字段
*/
function saveGetProps($idlist)
{
	if ($idlist == '' or $idlist == 0) return false;
	global $_pm,$_bag,$user;
	$arrobj = new arrays();

	$l=0;
	if (is_array($_bag))
	{
		foreach ($_bag as $x => $y)
		{
			if ($y['sums']>0 && $y['zbing']==0) $l++;
		}
	}
	if ($l >= $user['maxbag']) return false;	
	
	$arr = split(',', $idlist);
	foreach ($arr as $k => $v)
	{
		$rs = $arrobj->dataGet(array('k' => MEM_USERBAG_KEY, 
									 'v' => "if(\$rs['uid']=='{$_SESSION['id']}' && \$rs['pid']=='{$v}') \$ret=\$rs;"
									 ),
								   $_bag
							  ); 
		
		//$rs = $_pm['mysql']->getOneRecord("SELECT * FROM userbag WHERE uid={$_SESSION['id']} and pid={$v}");
		if (is_array($rs))
		{
			if ($rs['vary'] == 1) // 可折叠道具.
			{
				$_pm['mysql']->query("UPDATE userbag
							   SET sums=sums+1
							 WHERE id={$rs['id']}
						  ");
			}
			else
			{
				//$newid = mem_get_autoid($m, MEM_ORDER_KEY, 'userbag');
				$_pm['mysql']->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
							VALUES(
								   {$user['id']},
								   {$v},
								   {$rs['sell']},
								   2,
								   1,
								   unix_timestamp()
								  );
						  ");
				 $l++;
			}
		}
		else{
			$mempropsid = unserialize($_pm['mem']->get('db_propsid'));
			$rs = $mempropsid[$v];
			/*$rs = $_pm['mem']->dataGet(array('k' => MEM_PROPS_KEY, 
								    'v' => "if(\$rs['id'] == '{$v}') \$ret=\$rs;"
								  ));*/
			if (is_array($rs))
			{
				//$newid = mem_get_autoid($m, MEM_ORDER_KEY, 'userbag');
				$_pm['mysql']->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime)
							VALUES(
								   {$user['id']},
								   {$v},
								   {$rs['sell']},
								   {$rs['vary']},
								   1,
								   unix_timestamp()
								  );
						  ");
				 $l++;
			}	
		}		
		unset($rs);
		// 检测是否超出包裹，
		if ($l >= $user['maxbag']) return false;
	}	
}
?>
