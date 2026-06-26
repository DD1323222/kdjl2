<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.09.06
*@Usage: Used props.
*@Note: none
 Fix: zb level and wx limit for zb bb
*/
session_start();
require_once('../config/config.game.php');

secStart($_pm['mem']);

$user		= $_pm['user']->getUserById($_SESSION['id']);
$bags=$bag	= $_pm['user']->getUserBagById($_SESSION['id']);
$id = intval($_REQUEST['id']); // userbag id
if ($id<1 || !is_array($bags)) die('物品不存在!');
if(lockItem($id) === false)
{
	die('已经在处理了！');
}




// 整理包裹
if($_REQUEST['op'] == 'reset')
{
	echo '整理完成！';
	unLockItem($id);
	exit();
}



foreach ($bags as $k => $v)
{
	if ($v['id'] == $id && $v['uid'] == $_SESSION['id'] && $v['sums']>0 && $v['zbing']==0)
	{
		$rs = $v; 
		break;
	}
}
$_pm['mysql'] -> query('START TRANSACTION;');
// main bb for user.
$bb = $_pm['mysql']->getOneRecord("SELECT * FROM userbb
						  WHERE id={$user['mbid']} and uid={$_SESSION['id']} 
						  LIMIT 0,1
						");
if (!is_array($rs)){
	unLockItem($id);
	die("没有发现相关物品！");
}

// if is zb,used it!
// if is zb,used it!
if ($rs['varyname'] == 9)	//装备系统。
{
	if (is_array($bb))
	{
		// Check 是否符合宝宝要求。
		if ($rs['requires']!='')
		{
			$arr = explode(',', $rs['requires']);
			if(is_array($arr))
			{
				foreach($arr as $v){
					if(!empty($v))
					{
						$newarr = explode(":",$v);
						if($newarr[0] == 'lv'){
							$tlv = $newarr[1];
						}else if($newarr[0] == 'wx' && !empty($newarr[1])){
							$twx = $newarr[1];
						}
					}
				}
			}
			if(!empty($twx) && $twx != $bb['wx'])
			{
				unLockItem($id);
	$_pm['mysql'] -> query('ROLLBACK;');					
				die('宝宝五行不匹配!');
			}
			else if(!empty($tlv) && $tlv > $bb['level'])
			{
				unLockItem($id);
	$_pm['mysql'] -> query('ROLLBACK;');					
				die('宝宝等级不够!');
			}
			/*$lv  = explode(':', $arr[0]);
			if ($lv[0] == "lv") $tlv = $lv[1];
			else if($lv[0] == "wx") $twx = $lv[1];
			
			if($arr[1] != '')
			{
				$wx = explode(':', $arr[1]);
				if ($wx[0] == "lv") $tlv = $wx[1];
				else if($wx[0] == "wx") $twx = $wx[1];
			}
			
			if ($twx!= $bb['wx'] || $tlv>$bb['level']) die('宝宝等级不够或五行不匹配!');*/
		}
		
		// Ensure props attrib is ok
		if (!isset($rs['postion']) || $rs['postion'] == '')
		{
			$prs = $_pm['mem']->dataGet(array('k'	=>	MEM_PROPS_KEY,
									 'v'	=>	"if(\$rs['id'] == '{$rs['pid']}') \$ret=\$rs;"
					));
			$rs['postion'] = $prs['postion']; // Fix postion.
			unset($prs);
		}
		
		if (strlen($bb['zb'])<2) 
		{
			$bb['zb'] = $rs['postion'] . ':' . $rs['id'];
		}
		else
		{
			if (strstr($bb['zb'], ","))
			{
				$zb  = split(',', $bb['zb']); // format: p:id,p:id
				$new = '';
				$rpl = 0;
				foreach($zb as $k => $v)
				{
					$arr = explode(':',$v);
					if ($arr[0] == $rs['postion']) // 替换对应装备。
					{
						$new   .= ','.$arr[0] . ':' . $id;
						$oldid	= $arr[1];
						$rpl	= 1;
					}else $new .= ',' . $v;
				}
				$bb['zb'] = substr($new,1);
				if(!$rpl) $bb['zb'] .= ',' . $rs['postion'] . ':' . $rs['id'];
			}
			else 
			{
				$arr = explode(':', $bb['zb']);
				if ($arr[0] == $rs['postion']) // 替换对应装备。
				{
					$bb['zb'] = $arr[0] . ':' . $rs['id'];
					$oldid = $arr[1];
				}else $bb['zb'] = $bb['zb'] . ',' . $rs['postion'] . ':' . $rs['id'];
			}
		}
		
		/**Find current postion zb clear zb tag.*/
		$clearlist = '';
		foreach ($bags as $k => $v)
		{
			if ($v['postion'] == $rs['postion'] and $v['zbing']!=0 and $v['zbpets']!=0 and $v['zbpets']==$bb['id'])
			{
				$clearlist .= $clearlist?','.$v['id']:$v['id'];
			}
		}

		

		$_pm['mysql']->query("UPDATE userbb 
					   SET zb='{$bb['zb']}'
					 WHERE id={$user['mbid']}
				  ");
		if (!empty($clearlist) && $clearlist!='')
		{
			$_pm['mysql']->query("UPDATE userbag 
						   SET zbing=0,zbpets=0 
						 WHERE id in ({$clearlist})
					 ");
		}
		$_pm['mysql']->query("UPDATE userbag 
					   SET zbing=1,zbpets={$user['mbid']}
					 WHERE id={$id}
				  ");

		//设定装备变化标志
		$_pm['mem']->set(array("k"=>"User_bb_equip_changed_".$user['mbid'].'_'.$_SESSION['id'],"v"=>1));
		//$_SESSION['dbg_equip_attr2'] .= "Right here 2!<br>";
		unLockItem($id);
	$_pm['mysql'] -> query('COMMIT;');
		die('恭喜您，装备成功！');
	}
	else{
		unLockItem($id);
	$_pm['mysql'] -> query('ROLLBACK;');					
		die('您还没有设置主战宝宝，不能进行装备！');
	}
}
else if($rs['varyname'] == 13) // 特殊类型。扩展包裹，仓库，牧场格子
{
	//托管空间扩充卷
	if($rs['pid'] == 1203)
	{
		if($user['tgmax'] >= 2)
		{
			unLockItem($id);
	$_pm['mysql'] -> query('ROLLBACK;');					
			die("您只能使用此卷扩充一次托管所！");
		}
		else if($user['tgmax'] == 1) 
		{
			$sql = "UPDATE player SET tgmax = 2 WHERE id = {$_SESSION['id']}";
			$_pm['mysql'] -> query($sql);
			$sql = "UPDATE userbag SET sums = sums - 1 WHERE uid = {$_SESSION['id']} and pid = 1203 and sums>0";
			$_pm['mysql'] -> query($sql);
			unLockItem($id);
	$_pm['mysql'] -> query('COMMIT;');
			die("使用托管所扩充卷轴（一）成功!");
		}
	}
	if($rs['pid'] == 1204)
	{
		if($user['tgmax'] >= 3)
		{
			unLockItem($id);
	$_pm['mysql'] -> query('ROLLBACK;');					
			die("您只能使用此卷扩充一次托管所！");
		}
		else if($user['tgmax'] == 1)
		{
			unLockItem($id);
	$_pm['mysql'] -> query('ROLLBACK;');					
			die("请先使用托管所扩充卷（一）扩充您的托管所!");
		}
		else if($user['tgmax'] == 2) 
		{
			$sql = "UPDATE player SET tgmax = 3 WHERE id = {$_SESSION['id']}";
			$_pm['mysql'] -> query($sql);
			$sql = "UPDATE userbag SET sums = sums - 1 WHERE uid = {$_SESSION['id']} and pid = 1204 and sums>0";
			$_pm['mysql'] -> query($sql);
			unLockItem($id);
	$_pm['mysql'] -> query('COMMIT;');
			die("使用托管所扩充卷轴（二）成功!");
		}
	}
	$eff = explode(":",$rs['effect']);
	if($eff[0] == 'zhanshi')
	{
		$arr = "";
		$arr = $_pm['mysql'] -> getOneRecord("SELECT bbshow FROM player_ext WHERE uid = {$_SESSION['id']}");
		if(!is_array($arr))
		{
				unLockItem($id);
	$_pm['mysql'] -> query('ROLLBACK;');					
			die("您暂时不能使用宠物展示卷！");
		}
		$_pm['mysql'] -> query("UPDATE player_ext SET bbshow = bbshow + {$eff[1]} WHERE uid = {$_SESSION['id']}");
		$_pm['mysql'] -> query("UPDATE userbag SET sums = sums - 1 WHERE pid = {$rs['pid']} and uid = {$_SESSION['id']} and sums>0");
			unLockItem($id);
	$_pm['mysql'] -> query('COMMIT;');
		die("恭喜您使用宠物展示卷成功增加".$eff[1]."次展示机会！");
	}
	if(is_array($eff))
	{
		if($eff[0] == "tuoguan")
		{
			$sql = "UPDATE player SET tgtime = tgtime + $eff[1] WHERE id = {$_SESSION['id']}";
			$_pm['mysql'] -> query($sql);
			$_pm['mysql'] -> query("UPDATE userbag SET sums = sums - 1 WHERE uid = {$_SESSION['id']} and pid = {$rs['pid']} and sums>0");
				unLockItem($id);
	$_pm['mysql'] -> query('COMMIT;');
			die("使用{$eff[1]}小时托管卷成功!");
		}
	}
	$keys = explode(':', $rs['effect']);
	if ($rs['pid'] >=85 && $rs['pid']<=93)
	{
		$keys = explode(':', $rs['effect']);
		$item = split(',',$user['openmap']);
		if (in_array($keys[1], $item)){
			unLockItem($id);
	$_pm['mysql'] -> query('ROLLBACK;');					
			die($rs['name'].'对应的地图已经打开了!');
		}
		
		$valid = false;
		foreach ($bags as $k => $v)
		{
			if ($v['id'] == $id)
			{
				$valid	= true;
				$rs = $v;
				break;
			}
		}
		if (is_array($rs))
		{
			// del a props for current map.
			$_pm['mysql']->query("UPDATE userbag SET sums = sums -1 WHERE uid = {$_SESSION['id']} and id = {$id} and sums>0");
			$user['openmap'] .= ','.$keys[1];
		
			$_pm['mysql']->query("UPDATE player 
						   SET openmap='{$user['openmap']}' 
						 WHERE id={$_SESSION['id']}");

			unLockItem($id);
	$_pm['mysql'] -> query('COMMIT;');
			die("{$rs['name']} 对应地图打开成功!");
		}
		else{
			unLockItem($id);
	$_pm['mysql'] -> query('ROLLBACK;');					
			die("地图打开失败，请确认包裹中有打开该地图对应的钥匙!");
		}
	}
	else if($rs['pid'] >=200 && $rs['pid']<=202)
	{
		$full = 0;
		if($rs['name'] == "仓库升级卷轴")
		{
			if($user['maxbase'] >= 96) $full=1;
			if($user['maxbase']+6>96) $user['maxbase']=96;
			else $user['maxbase']+=6;
		}
		else if($rs['name'] == "背包升级卷轴")
		{
			if($user['maxbag'] >= 96) $full=1;
			if($user['maxbag']+6>96) $user['maxbag']=96;
			else $user['maxbag']+=6;
		}
		else if($rs['name'] == "牧场升级卷轴")
		{
			if($user['maxmc'] >= 40) $full=1;
			if($user['maxmc']+6>40) $user['maxmc']=40;
			else $user['maxmc']+=6;
		}
		if ($full==1){
			unLockItem($id);
	$_pm['mysql'] -> query('ROLLBACK;');					
			die("已经扩展到极限，如需再扩展请买其它道具!");
		}

		// del props. and save result.
		$_pm['mysql']->query("UPDATE player 
					   SET maxbase={$user['maxbase']},
					       maxbag={$user['maxbag']},
						   maxmc={$user['maxmc']}
					 WHERE id={$_SESSION['id']}");
		
		$_pm['mysql']->query("UPDATE userbag
					   SET sums=sums-1
					 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
				  ");
		unLockItem($id);
	$_pm['mysql'] -> query('COMMIT;');
		die("使用道具 {$rs['name']} 成功!");
	}
	else if ($rs['pid'] == 1342){
		$full = 0;
		if($user['maxbag'] == 150) $full=1;
		if($user['maxbag'] < 96) $full=2;
		if($user['maxbag']+6>150) $user['maxbag']=150;
		else $user['maxbag']+=6;
		if ($full==1){
			unLockItem($id);
	$_pm['mysql'] -> query('ROLLBACK;');					
			die("已经扩展到极限，不能再继续扩展了!");
		}
		if ($full==2){
			unLockItem($id);
	$_pm['mysql'] -> query('ROLLBACK;');					
			die("背包还没扩展到96格，请先用背包升级卷轴扩展到96格!");
		}
		$_pm['mysql']->query("UPDATE player 
					   SET maxbag={$user['maxbag']}
					 WHERE id={$_SESSION['id']}");
		
		$_pm['mysql']->query("UPDATE userbag
					   SET sums=sums-1
					 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
				  ");
		unLockItem($id);
	$_pm['mysql'] -> query('COMMIT;');
		die("使用道具 {$rs['name']} 成功!");
	}
	else if ($rs['pid'] == 1343){
		$full = 0;
		if($user['maxbase'] == 150) $full=1;
		if($user['maxbase'] < 96) $full=2;
		if($user['maxbase']+6>150) $user['maxbase']=150;
		else $user['maxbase']+=6;
		if ($full==1){
			unLockItem($id);
	$_pm['mysql'] -> query('ROLLBACK;');					
		die("已经扩展到极限，不能再继续扩展了!");
		}
		if ($full==2){	
			unLockItem($id);
	$_pm['mysql'] -> query('ROLLBACK;');					
			die("仓库还没扩展到96格，请先用仓库升级卷轴扩展到96格!");
		}
		$_pm['mysql']->query("UPDATE player 
					   SET maxbase={$user['maxbase']}
					 WHERE id={$_SESSION['id']}");
		
		$_pm['mysql']->query("UPDATE userbag
					   SET sums=sums-1
					 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
				  ");
		unLockItem($id);
	$_pm['mysql'] -> query('COMMIT;');
		die("使用道具 {$rs['name']} 成功!");
	}
	else if(($rs['pid'] >=742 && $rs['pid']<=746) || $rs['pid'] == 1247 || $rs['pid'] == 1225) // 经验卷及自动战斗卷. format: 
	{
		if ($keys[0] == 'exp') // 使用经验卷
		{
			$dbl=0;
			switch($keys[1])
			{
				case 1.5: $dbl = 2;break;
				case 2:   $dbl = 3;break;
				case 2.5: $dbl = 4;break;
				case 3: $dbl = 5;break;
			}
		
			if(is_array($rs))
			{
				// del a props for current 
				$_pm['mysql']->query("UPDATE userbag
							   SET sums=sums-1
							 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
						  ");
				// 获取当前的剩余双倍时间并累计。
				if ($user['dblexpflag']>1 && $dbl==$user['dblexpflag']) 
				{
					$other=$user['dblstime']+$user['maxdblexptime']-time();
					if ($other<=0) $other=0;
					$user['maxdblexptime']=3600+$other;
				}
				else $user['maxdblexptime']=3600;
				
				$user['dblexpflag']=$dbl;
				$user['dblstime']=time();

				// Update user data to database.
				$_pm['mysql']->query("UPDATE player
							   SET maxdblexptime={$user['maxdblexptime']},
								   dblexpflag={$user['dblexpflag']},
								   dblstime={$user['dblstime']}
							 WHERE id={$_SESSION['id']}
						  ");
				unLockItem($id);
	$_pm['mysql'] -> query('COMMIT;');
				die("使用{$keys[1]} 倍经验卷成功!");
			}	
			else{
				unLockItem($id);
	$_pm['mysql'] -> query('ROLLBACK;');					
			die("没有在包裹中发现相应的物品!");	
			}
		}
	} // end 双倍卷。
	####################作用自动战斗卷，分为金钱版和元宝版9.24谭炜###################
		
		if($keys[0] == 'autofree') // 使用金钱版自动战斗卷
		{
			if(is_array($rs))
			{
				// del a props for current 
				$_pm['mysql']->query("UPDATE userbag
								  SET sums=sums-1
							 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
							 ");
				$user['sysautosum']+= intval($keys[1]);
				$_pm['mysql']->query("UPDATE player
								 SET sysautosum={$user['sysautosum']}
							 WHERE id={$_SESSION['id']}
							  ");
				unLockItem($id);
		$_pm['mysql'] -> query('COMMIT;');
			die("使用 {$keys[1]} 次金钱版自动战斗卷成功!");
			}
		}
		else if($keys[0] == "auto")
		{
			$_pm['mysql']->query("UPDATE userbag
								  SET sums=sums-1
							 WHERE id={$id} and uid={$_SESSION['id']} and sums>0
							 ");
			$user['maxautofitsum']+= intval($keys[1]);
			$_pm['mysql']->query("UPDATE player
								  SET maxautofitsum={$user['maxautofitsum']}
							 WHERE id={$_SESSION['id']}
							 ");
			unLockItem($id);
		$_pm['mysql'] -> query('COMMIT;');
		die("使用 {$keys[1]} 次元宝版自动战斗卷成功!");
		}
				####################在这里结束###################
}
else if($rs['varyname'] == 12) // 宝箱类型。
{
	/**
	* Format: randitem:1308:1:80:2|1055:1:70:2|1141:1:80:2|744:1:30:2|211:1:40:1|213:1:40:1|871:1:40:1|870:1:20:1|1207:1:20:1|9:1:5:1|912:1:1:1
	@Memo: 1表示获得该道具的时候,会发系统公告(2表示不会发公告)
			“[玩家名字]打一枚徽章,或许是踩到了狗屎了,居然获得了E(对应数量)个D(对应的道具名称)”
	*/
	//判断用户包裹是否已满
	$bagNum=0;
	
	if(is_array($bags))
	{
		foreach($bags as $x => $y)
		{
			if($y['sums']>0 and $y['zbing'] == 0) 
			{
				$bagNum++;		
			}
		}
	}

	if($bagNum >= $user['maxbag'])
	{
		unLockItem($id);
	$_pm['mysql'] -> query('ROLLBACK;');					
	die('您的包裹已满，请先清理包裹！');
	}
	
	if(!empty($rs['requires']))
	{
		$requires = explode(":",$rs['requires']);
		if($requires[0] == 'lv')
		{
			if($bb['level'] < $requires[1])
			{
				unLockItem($id);
	$_pm['mysql'] -> query('ROLLBACK;');					
				die("您没有达到相应的等级，不能开启该宝箱！");
			}
		}
	}
    $propsPatter = $rs['effect'];
	$arr = explode(",",$propsPatter);

	foreach($arr as $v)
	{
		$newarr = explode(":",$v);
		if($newarr[0] == "needkey")
		{
			if(is_array($bags))
			{
				foreach($bags as $y)
				{
					if($y['pid'] == $newarr[1] && $y['sums'] > 0)
					{
						$_pm['mysql']->query("UPDATE userbag
										     SET sums=sums-1
										   WHERE pid={$newarr[1]} and uid={$_SESSION['id']} and sums>0
										");
						$sign = 1;
					}
				}
				if($sign != 1)
				{
					unLockItem($id);
	$_pm['mysql'] -> query('ROLLBACK;');					
				die("您没有开启宝箱的钥匙!");
				}
			}
			else
			{
				unLockItem($id);
	$_pm['mysql'] -> query('ROLLBACK;');					
			die("您没有开启宝箱的钥匙!");
			}
		}
		else if($newarr[0] == 'giveitems')
		{
			$patter = str_replace('giveitems:', '', $rs['effect']);
			$propslist = explode(',', $patter);
			
			$retstr = '';
			if (is_array($propslist))
			{
				foreach ($propslist as $k => $v)
				{
					$inarr = explode(':', $v);		//	0=> ID, 2=> rand number, 1=> sum props
					
					
					if(is_array($inarr))
					{
						//foreach($inarr as $inarrs)
						//{
							$task = new task();
							$task->saveGetPropsMore($inarr[0],$inarr[1],$rs['pid']);
							$prs = $_pm['mysql']->getOneRecord("SELECT name FROM props WHERE id={$inarr[0]}");
							if(empty($retstr))
							{
								$retstr = '获得道具 '.$prs['name'].'&nbsp;'.$inarr[1].' 个';
							}
							else
							{
								$retstr .= ",".$prs['name'].'&nbsp;'.$inarr[1].' 个';
							}
						//}
					}
				} // end foreach
				// del props current bag.
				$_pm['mysql']->query("UPDATE userbag
										 SET sums=sums-1
									   WHERE id={$rs['id']} and uid={$_SESSION['id']} and sums>0
									");
				echo $retstr;
			}
		}
		elseif($newarr[0] == "randitem")
		{
			$patter = str_replace('randitem:', '', $v);
			$propslist = explode('|', $patter);
			$retstr = '';
			$task = new task();
			if (is_array($propslist))
			{
				foreach ($propslist as $k => $v)
				{
					$inarr = explode(':', $v);		//	0=> ID, 2=> rand number, 1=> sum props
					if (rand(1, intval($inarr[2])) == 1)	//  rand hits
					{
						$task = new task();
						$task->saveGetPropsMore($inarr[0],$inarr[1],$rs['pid']);
						$prs = $_pm['mysql']->getOneRecord("SELECT name FROM props WHERE id={$inarr[0]}");
						$retstr = '获得道具 '.$prs['name'].' '.$inarr[1].' 个';
		
						// del props current bag.
						$_pm['mysql']->query("UPDATE userbag
												 SET sums=sums-1
											   WHERE id={$rs['id']} and uid={$_SESSION['id']} and sums>0
											");
						
						if ($inarr[3] == 2)
						{
							$word = " ,使用{$rs['name']},幸运地得到自然女神的祝福,获得了 {$inarr[1]} 个{$prs['name']}";
							$task->saveGword($word);
						}
	
						echo $retstr;
						break;	
					}
				} // end foreach
			}
		}
	}
}

else if($rs['varyname'] == 2) // 增益类 
{
		$arr = explode(':', $rs['effect']);
		if (!is_array($arr)) return false;
		if ($arr[0] == 'addexp') // 增加经验
		{
			$eval = "\$exp=rand{$arr[1]};";
			eval($eval);
			$t = new task();
			$t->saveExps($exp);
			$tips .= '获得经验'.$exp;
		}
		else if($arr[0] == "addczl") // 添加成长
		{
			if ($user['mbid']!='' && $user['mbid']>0)
			{   
				$_pm['mysql']->query("UPDATE userbb
				                         SET czl=czl+{$arr[1]}
									   WHERE id={$user['mbid']}
									");
				$tips .= '主宠物永久增加'.$arr[1].'成长！';
			}
		}
		else if($arr[0] == "addac") // 增加攻击力
		{
			if ($user['mbid']!='' && $user['mbid']>0)
			{   
				$_pm['mysql']->query("UPDATE userbb
				                         SET ac=ac+{$arr[1]}
									   WHERE id={$user['mbid']}
									");
				$tips .= '主宠物永久增加'.$arr[1].'攻击！';
			}
		}
		else if($arr[0] == "addmc") // 增加防御
		{
			if ($user['mbid']!='' && $user['mbid']>0)
			{   
				$_pm['mysql']->query("UPDATE userbb
				                         SET mc=mc+{$arr[1]}
									   WHERE id={$user['mbid']}
									");
				$tips .= '主宠物永久增加'.$arr[1].'防御！';
			}
		}
		else if($arr[0] == "addhits") // 增加命中
		{
			if ($user['mbid']!='' && $user['mbid']>0)
			{   
				$_pm['mysql']->query("UPDATE userbb
				                         SET hits=hits+{$arr[1]}
									   WHERE id={$user['mbid']}
									");
				$tips .= '主宠物永久增加'.$arr[1].'命中！';
			}
		}
		else if($arr[0] == "addmiss") // 增加闪避
		{
			if ($user['mbid']!='' && $user['mbid']>0)
			{   
				$_pm['mysql']->query("UPDATE userbb
				                         SET miss=miss+{$arr[1]}
									   WHERE id={$user['mbid']}
									");
				$tips .= '主宠物永久增加'.$arr[1].'闪避！';
			}
		}
		else if($arr[0] == "addhp") // 增加生命力
		{
			if ($user['mbid']!='' && $user['mbid']>0)
			{   
				$_pm['mysql']->query("UPDATE userbb
				                         SET srchp=srchp+{$arr[1]}
									   WHERE id={$user['mbid']}
									");
				$tips .= '主宠物永久增加'.$arr[1].'生命！';
			}
		}
		else if($arr[0] == "addmp") // 增加魔力
		{
			if ($user['mbid']!='' && $user['mbid']>0)
			{   
				$_pm['mysql']->query("UPDATE userbb
				                         SET srcmp=srcmp+{$arr[1]}
									   WHERE id={$user['mbid']}
									");
				$tips .= '主宠物永久增加'.$arr[1].'魔法！';
			}
		}
		else if($arr[0] == "weiwang") // 增加威望
		{
			$_pm['mysql']->query("UPDATE player
				                         SET prestige=prestige+{$arr[1]}
									   WHERE id={$_SESSION['id']}
									");
			$tips .= '增加威望'.$arr[1].'点！';
		}
		$_pm['mysql']->query("UPDATE userbag
							     SET sums=sums-1
							   WHERE id={$rs['id']} and uid={$_SESSION['id']} and sums>0
							");
		echo $tips;
}

else if($rs['varyname'] == 16) // 图纸合成类
{
	
	//判断用户包裹是否已满
	$bagNum=0;
	
	if(is_array($bags))
	{
		foreach($bags as $x => $y)
		{
			if($y['sums']>0 and $y['zbing'] == 0) 
			{
				$bagNum++;		
			}
		}
	}

	if($bagNum >= $user['maxbag'])
	{
		unLockItem($id);
	$_pm['mysql'] -> query('ROLLBACK;');					
	die('您的包裹已满，请先清理包裹！');
	}

	$arr = explode(':', $rs['effect'],2);
	if ($arr[0] == 'hecheng') // 图纸合成 格式：hecheng:(956:10|957:10|958:10|1025:1):1012:1
	{
		$rarr = explode('):', $arr[1]);
		$require = str_replace('(', '',$rarr[0]);
		$gets = explode(':', $rarr[1]);

		// Check props is exists?
		$need = explode('|', $require);
		foreach ($need as $k => $v)
		{   $t = explode(':', $v);
			$ex  = $_pm['mysql']->getOneRecord("SELECT sum(sums) as cnt 
												  FROM userbag 
												 WHERE pid ={$t['0']} and uid={$_SESSION['id']}");
            if ($ex['cnt'] < $t['1']){
				unLockItem($id);
	$_pm['mysql'] -> query('ROLLBACK;');					
		die('你的材料不足，无法制作！');
			}
		}

		// ok, then get props.
		$idlist='';
		for($i=0; $i<$gets['1'];$i++)
		{
			$idlist .= $idlist==''?	$gets[0]:','.$gets[0];
		}
 
		// clear props
		$delcount = 0;
		foreach ($need as $k => $v)
		{
			$t = explode(':', $v);
			$ret =$_pm['mysql']->getRecords("SELECT id,sums
											  FROM userbag 
											 WHERE pid ={$t['0']} and uid={$_SESSION['id']}
											 ORDER by sums
										  ");
			//Del props and count num
			if (is_array($ret))
			{
				foreach ($ret as $k => $v)
				{
					if ($v['sums']<1) continue;
					if ($delcount < $t[1]) $del = $t[1]-$delcount;
					else break;
					if ($v['sums']==$del)
					{
						// del record
						$_pm['mysql']->query("UPDATE userbag 
											   SET sums=0
											 WHERE id={$v['id']}
										   ");
						break;
					}
					else if ($v['sums']<$del)
					{
						// del record. $v['sums']
						$delcount+=$v['sums'];
						$_pm['mysql']->query("UPDATE userbag 
											   SET sums=0
											 WHERE id={$v['id']}
										   ");
					}
					else // 减去剩余数值。update.
					{
						$v['sums'] = $v['sums']-$del;
						// update record.
						$_pm['mysql']->query("UPDATE userbag 
											   SET sums={$v['sums']}
											 WHERE id={$v['id']}
										  ");
						break;	
					}
				}
			} // end if
		} // end foreach
        // clear end
		$_pm['mysql']->query("UPDATE userbag
							     SET sums=sums-1
							   WHERE id={$rs['id']} and uid={$_SESSION['id']} and sums>0
							");
		// save props;
		$tsk = new task();
		$tsk->saveGetProps($idlist);
		unLockItem($id);
	$_pm['mysql'] -> query('COMMIT;');
		die('恭喜您,制作成功!获得了一件物品!');
	}
}
else if ($rs['varyname'] == 15) // 宠物卵
{
	$allbb = $_pm['user']->getUserPetById($_SESSION['id']);
	$all = 0;
	if (is_array($allbb))
	{
		foreach($allbb as $x => $y)
		{
			if($y['muchang']==1) continue;
			$all++;
		}
		if ($all>=3){
			unLockItem($id);
	$_pm['mysql'] -> query('ROLLBACK;');					
			die("您只能携带3个宝宝,使用道具失败！<br/>[系统推荐]：您可以把身上携带的宝宝放入到牧场！");
		}
	}

	$arr = explode(':', $rs['effect']);
	if($arr[0] == "openpet") $newpetsid = $arr[1];
	
	// 根据宝宝ID，生成宝宝属性并插入数据给到玩家数据包。
	#########################################################################################
		// Get new bb info.
		$bb = $_pm['mem']->dataGet(array('k'	=>	MEM_BB_KEY,
								'v'	=>  "if(\$rs['id'] == '{$newpetsid}') \$ret=\$rs;"
						));

		$czl = getCzl($bb['czl']);

		// insert into userbb.
		//$bbid= $newid = mem_get_autoid($m, MEM_ORDER_KEY, 'userbb');
		
		$uinfo = $user;
		
		$_pm['mysql']->query("INSERT INTO userbb(
									   name,
									   uid,
									   username,
									   level,
									   wx,
									   ac,
									   mc,
									   srchp,
									   hp,
									   srcmp,
									   mp,
									   skillist,
									   stime,
									   nowexp,
									   lexp,
									   imgstand,
									   imgack,
									   imgdie,
									   hits,
									   miss,
									   speed,
									   kx,
									   remakelevel,
									   remakeid,
									   remakepid,
									   muchang,
									   czl,
									   headimg,
									   cardimg,
									   effectimg
									  )
					VALUES(
						   '{$bb['name']}',
						   '{$uinfo['id']}',
						   '{$uinfo['nickname']}',
						   '1',
						   '{$bb['wx']}',
						   '{$bb['ac']}',
						   '{$bb['mc']}',
						   '{$bb['hp']}',
						   '{$bb['hp']}',
						   '{$bb['mp']}',
						   '{$bb['mp']}',
						   '{$bb['skillist']}',
						   unix_timestamp(),
						   '{$bb['nowexp']}',
						   '100',
						   '{$bb['imgstand']}',
						   '{$bb['imgack']}',
						   '{$bb['imgdie']}',
						   '{$bb['hits']}',
						   '{$bb['miss']}',
						   '{$bb['speed']}',
						   '{$bb['kx']}',
						   '{$bb['remakelevel']}',
						   '{$bb['remakeid']}',
						   '{$bb['remakepid']}',
						   '0',
						   '{$czl}',
						   't{$bb['id']}.gif',
						   'k{$bb['id']}.gif',
						   'q{$bb['id']}.gif'
						   )
				  ");
		
		$jnall = split(",", $bb['skillist']);
		foreach($jnall as $a => $b)
		{
			$arr = split(":", $b);
			// Get jn info.
			$jn = $_pm['mem']->dataGet(array('k'	=>	MEM_SKILLSYS_KEY,
									'v'	=>  "if(\$rs['id'] == '{$arr[0]}') \$ret=\$rs;"
							));
			// #################################################				
			if ($jn['ackvalue']=='') continue; // 增加辅助技能。
			//##################################################
			
			$ack  = split(",", $jn['ackvalue']);
			$plus = split(",", $jn['plus']);
			$uhp  = split(",", $jn['uhp']);
			$ump  = split(",", $jn['ump']);
			$img  = split(",", $jn['imgeft']);

			// Insert userbb jn.	
			//$newid = mem_get_autoid($m, MEM_ORDER_KEY,'skill');
			/*获取刚插入宠物ID。*/
			$newbb = $_pm['mysql']->getOneRecord("SELECT id 
										  FROM userbb
										 WHERE uid={$_SESSION['id']}
										 ORDER BY stime DESC
										 LIMIT 0,1			                                         
									  ");
			$bbid = $newbb['id'];

			$_pm['mysql']->query("INSERT INTO skill(bid,name,level,vary,wx,value,plus,img,uhp,ump,sid)
						VALUES(
							   '{$bbid}',
							   '{$jn['name']}',
							   '{$arr['1']}',
							   '{$jn['vary']}',
							   '{$jn['wx']}',
							   '{$ack[0]}',
							   '{$plus[0]}',
							   '{$img[0]}',
							   '{$uhp[0]}',
							   '{$ump[0]}',
							   '{$jn['id']}'
							  )
					  ");
	  }
	 
	  // sub props sum.
	  $_pm['mysql']->query("UPDATE userbag
					 SET sums=sums-1
				   WHERE id={$id} and uid={$_SESSION['id']} and sums>0
				");
	  echo "使用道具成功!";
	//#######################################################################################
}
else if ($rs['varyname'] == 14) // 军功令，换取军功
{
	$arr = explode(':', $rs['effect']);
	if($arr[0] == "jg")
	{
		$sql = "SELECT jgvalue FROM battlefield_user WHERE uid = {$_SESSION['id']}";
		$row = $_pm['mysql'] -> getOneRecord($sql);
		if(!is_array($row))
		{
			unLockItem($id);
	$_pm['mysql'] -> query('ROLLBACK;');					
			die("您目前没有参加战场活动，不能使用此道具！");
		}
		$_pm['mysql']->query("UPDATE battlefield_user
		                         SET jgvalue=jgvalue+{$arr[1]}
							   WHERE uid={$_SESSION['id']}
							");
		 // sub props sum.
	  $_pm['mysql']->query("UPDATE userbag
						   SET sums=sums-1
						   WHERE id={$id} and uid={$_SESSION['id']} and sums>0
						  ");
		echo "恭喜您，使用道具成功，您获得了 {$arr[1]} 点军功！";
	}
	else {echo '道具使用失败！';}
	
}
$_pm['mysql'] -> query('COMMIT;');

unLockItem($id);
$_pm['mem']->memClose();
unset($m, $u, $db, $user, $bags, $rs);
?>