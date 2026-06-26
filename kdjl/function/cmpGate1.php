<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.07.30
*@Update Date: 
*@Usage: 宠物合成系统。
*@Memo: 宠物合成系统。
:属性=[宠物资料数据库属性+取整（主怪物属性5%）+取整（副怪物属性1%）]*(100%+道具附加属性%)
:实际成长率=取1位小数{[取一位小数（主宠物成长*90%）+取一位小数（副宠物成长*10%）]* (100%+道具附加属性%)}
*/
require_once('../config/config.game.php');
secStart($_pm['mem']);

$ap	    = intval($_REQUEST['ap']);  // table userbb->id
$bp 	= intval($_REQUEST['bp']);  // table userbb->id
$p1 	= intval($_REQUEST['p1']);  // table userbag->id
$p2 	= intval($_REQUEST['p2']);  // table userbag->id
$srctime = 15;
#################增加一个间隔时间################
$time = $_SESSION['time'.$_SESSION['id']];
if(empty($time))
{	
	$_SESSION['time'.$_SESSION['id']] = time();
}
else
{
	$nowtime = time();
	$ctime = $nowtime - $time;
	if($ctime < $srctime)
	{
		die("11");//没有达到间隔时间
	}
	else
	{
		$_SESSION['time'.$_SESSION['id']] = time();
	}
}
##################增加在这里结束#################
if ($ap<0 || $bp<0) die('0');
if ($p1<0) $p1 = 0;
if ($p2<0) $p2 = 0;



$user		= $_pm['user']->getUserById($_SESSION['id']);
$userbb		= $_pm['user']->getUserPetById($_SESSION['id']);
if(!empty($p1))
{
	$pp1 = $_pm['user']->getUserItemById($_SESSION['id'],$p1);
}
if(!empty($p2))
{
	$pp2 = $_pm['user']->getUserItemById($_SESSION['id'],$p2);
}
$log = '';

if ( is_array($userbb))
{
	foreach ($userbb as $key => $rs)
	{
		if ($rs['id']==$ap && $rs['level']>=40) // From bb base find user current bb.
		{
			$app = $rs;
		}
		if ($rs['id']== $bp && $rs['level']>=40)
		{
			$bpp = $rs;
		}
	}
    unset($rs);
	
	
	//mysql5BUG   谭炜 2009.2.24
	if($p1 == $p2 && $p1 != 0)
	{
		if($pp1['sums'] < 2)
		{
			die("100");
		}
	}

	if (!is_array($app) || !is_array($bpp) || ($app['id'] == $bpp['id'])) die('1'); //没有对应的宠物。
	
	// 检查是否满足公式。
	$ars = $_pm['mem']->dataGet(array('k' => MEM_BB_KEY, 
										 'v' => "if(\$rs['name'] == '{$app['name']}') \$ret=\$rs;"
							  ));
	$brs = $_pm['mem']->dataGet(array('k' => MEM_BB_KEY, 
										 'v' => "if(\$rs['name'] == '{$bpp['name']}') \$ret=\$rs;"
							  ));
	
	$cmprs = $_pm['mysql']->getOneRecord("SELECT * 
											FROM merge
										   WHERE aid = {$ars['id']} and bid={$brs['id']}
										   LIMIT 0,1
	                                    ");
    if (!is_array($cmprs)) die('2');	//不能合成，								
					  
	
	// 判断金币消耗：
	$money=0;
	if (is_array($pp1))
	{
		$one = explode(',', $pp1['effect']);
		foreach ($one as $a => $b)
		{
			$arr = explode(':', $b);
			$money+=$arr[count($arr)-1];
		}
	}
	if (is_array($pp2))
	{
		$one = explode(',', $pp2['effect']);
		foreach ($one as $a => $b)
		{
			$arr = explode(':', $b);
			$money+=$arr[count($arr)-1];
		}
	}
	
	$money = ($app['level']+$bpp['level'])*1000+$money;
	if ($user['money'] < $money) die('3');	//	金币不足。
		
	$propseff = getEffect($pp1, $pp2);
	
	
	// 得到成功率.
	$sus = getSuccess($propseff,$app['czl'],$pp1['pid'],$pp2['pid']);

	$czl = bbczl($app,$bpp,$pp1,$pp2);

	if ($sus) // 合成成功。a,b宠物消失，得到新的宠物。$cmprs=> 得到相关宝宝信息。
	{
		// 改变属性地方为:
		if ($sus == 1) $newbid = $cmprs['maid'];
		if ($sus == 2) $newbid = $cmprs['mbid'];
		
		$brs = $_pm['mysql']->getOneRecord("SELECT * 
											  FROM  bb
											 WHERE id={$newbid}
											 LIMIT 0,1
										  ");
										  
		if (!is_array($brs))
		{
			die('10'); // 数据错误
		}
		// 改变各项数据:
		makebb($brs);
		$cstatus = 2;
	}
	else // 如果没有相关道具进行绑定，副宠消失
	{
		$cstatus = 1;
	}
	
	$user['money'] = $user['money']-$money;		// 减少用户金币.
	$_pm['mysql']->query("UPDATE player
						     SET money='{$user['money']}'
					 	   WHERE id={$_SESSION['id']}
				  		");
	// 记录日志：
	$log .= "合成结果：".($cstatus==1?"失败":"成功")."\n";
	$log .= "合成道具：1:".$pp1['pid'].' 2:'.$pp2['pid']."\n";

	//######### del props Start.##################
	delProps();
	############# del props end.#####################

	if ($cstatus == 1) //副宠消失。
	{
		$del = 1;
		$log .= '合成道具详细：';
		if(is_array($propseff))
		{
			foreach ($propseff as $m => $n)
			{
				$log .= $n['shbb']."-";
				if ($n['shbb'] === true)
				{
					$del = 0;
					break;
				}
			}
		}
		
		if ($del == 1)
		{
			clearBB($bpp);
		}
		$log = addslashes($log);
		// 合成失败记录点：
		$_pm['mysql']->query("INSERT INTO gamelog(ptime,seller,buyer,pnote,vary)
		                      VALUES(unix_timestamp(),'{$_SESSION['id']}','{$_SESSION['id']}','{$log}',2)
							");
		die('6');
	}
	else if($cstatus == 2) // 成功。
	{
		/*
		$_pm['mem']->set(array('k'=>MEM_SYSWORD_KEY, 
							   'v'=>'[系统公告]恭喜玩家 '.$user['nickname'].'成功的合成了一只['.$cmprs['name'].'],真是太幸运了!'));
		*/
		$msg_key = 'chatMsgList';
		$nowMsgList = unserialize($_pm['mem']->get($msg_key));
		$arr = split('linend', $nowMsgList);
		if( count($arr)>20 ) // cear old
		{
			$arrt = array_shift($arr);
		}
		$newstr = '<font color=red>[系统公告]恭喜玩家 '.$user['nickname'].' 成功的合成了一只['.$brs['name'].'],真是太幸运了!</font>';
		$str = '新宠物名字：'.$brs['name'].',使用物品1：'.$pp1['name'].',使用物品2：'.$pp2['name'].',宠物：'.$app['name'].'-'.$bpp['name'].'';
		$_pm['mysql']->query("INSERT INTO gamelog(ptime,seller,buyer,pnote,vary)
		                      VALUES(unix_timestamp(),'{$_SESSION['id']}','{$_SESSION['id']}','{$str}',4)
							");
		
		foreach($arr as $k=>$v)
		{
			$retstr .= $v.'linend';
		}

		$retstr = $retstr.$newstr;
		$_pm['mem']->set( array('k'=>$msg_key, 'v'=>$retstr) ); // default ten min.

		
		clearBB($app); // del pets master
		clearBB($bpp); // del pets other
		die('5');
	}
}
else die('000');
$_pm['mem']->memClose();
// Logic code end.



/**
* @Usage: 创建新的宠物。
* @Param: array -> $bb.
* @Return: Void(0);
*/
function makebb($bb)
{
	global $app,$bpp,$pp1,$pp2,$user,$_pm,$propseff;
	$czl = bbczl($app,$bpp,$pp1,$pp2);

	// ac,luck,mc,hit,miss,speed,hp,mp,shbb; 
	$bb['ac']	= getPa($bb['ac'], $app['ac'], $bpp['ac'] ,getPlus($propseff, 'ac'));  #### 暂时没有加入道具附加属性。
    $bb['mc']	= getPa($bb['mc'], $app['mc'], $bpp['mc'] ,getPlus($propseff, 'mc'));
	$bb['hits']	= getPa($bb['hits'], $app['hits'], $bpp['hits'] ,getPlus($propseff, 'hits'));
    $bb['miss']	= getPa($bb['miss'], $app['miss'], $bpp['miss'] ,getPlus($propseff, 'miss'));
	$bb['speed']= getPa($bb['speed'], $app['speed'], $bpp['speed'] ,getPlus($propseff, 'speed'));
	$bb['hp']	= getPa($bb['hp'], $app['hp'], $bpp['hp'] ,getPlus($propseff, 'hp'));
	$bb['mp']	= getPa($bb['mp'], $app['mp'], $bpp['mp'] ,getPlus($propseff, 'mp'));


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
				  
		$_pm['mysql'] -> query("UPDATE player SET mbid = {$bbid} WHERE id = {$_SESSION['id']}");
				  
   }
}

/**
* @Usage: 删除一个宠物;
* @Param: Array -> $bb.
* @Return: Void(0);
*/
function clearBB($bb)
{
	global $_pm,$log;
	$id = $bb['id'];
	
	foreach ($bb as $k => $v)
	{
		$log .= $k.'=>'.$v.'-';
	}
	
	// del sk. 
	$_pm['mysql']->query("DELETE FROM skill
				 WHERE bid={$id}
			  ");
			  
	// del zb.
	$_pm['mysql']->query("DELETE FROM userbag
				 WHERE uid={$_SESSION['id']} and zbpets={$id}
			  ");
	// del bb.
	$_pm['mysql']->query("DELETE FROM userbb
				 WHERE uid={$_SESSION['id']} and id={$id}
			  ");
}

/**
* @Param: 宠物a,b的属性。
* @Return: 返回组后的成长率。
  czl:实际成长率=对应宠物资料数据库成长率属性+取1位小数{[取一位小数（主宠物成长*主宠物等级/120）+取一位小数（副宠物成长*副宠物等级/240）+rand(副宠物成长/10,主宠物成长/10)]* (100%+道具附加属性%)}
  rand(副宠物成长/10,主宠物成长/10)
  意思是:取副宠物的成长值/10到主宠物成长值/10的随机数
  如副宠物成长10,主宠物成长20
  则: rand(1,2)
*/
function bbczl($a, $b, $pp1, $pp2)
{
	global $brs; // 资料库中宠物属性。
	$czlplus = '';
	// 第一个道具。
	if (is_array($pp1))
	{
		$one = explode(',', $pp1['effect']);
		foreach ($one as $x => $y)
		{
			$arr = explode(':', $y);
			if ($arr[0] == 'addczl')
			{
				$czlplus = str_replace('%','',$arr[1])/100;
			}
		}
	}
    // 第二个道具。
	if (is_array($pp2))
	{
		$one = explode(',', $pp2['effect']);
		foreach ($one as $x => $y)
		{
			$arr = explode(':', $y);
			if ($arr[0] == 'addczl')
			{
				$czlplus += str_replace('%','',$arr[1])/100;
			}
		}
	}
	
	$czl = getCzl($brs['czl'])+round( (round(( ($a['czl']*$a['level'])/120),1) + round(( ($b['czl']*$b['level'])/240),1) + 
		          rand(round($b['czl']/10,1)*10, round($a['czl']/10,1)*10)/10 )*(1+$czlplus),1);
	if($czl > 50)
	{
		$czl = 50.0;
	}
	return $czl;
}

/**
*@Usage: 获取合成中添加道具的所有效果
*@Param: array -> $pp1, array -> $pp2.
*@Return: array.
*/
function getEffect($pp1, $pp2)
{
	$i = 0;
	if (is_array($pp1))
	{
		$one = explode(',', $pp1['effect']);
		foreach ($one as $a => $b)
		{
			$arr = explode(':', $b);
			$ret[$i++] = getvary(trim($arr[0]), $arr);
		}
	}
	if (is_array($pp2))
	{
		$one = explode(',', $pp2['effect']);
		foreach ($one as $a => $b)
		{
			$arr = explode(':', $b);
			$ret[$i++] = getvary(trim($arr[0]), $arr);
		}
	}
	// 组合效果。
	return $ret;
}

/**
* @Usage: 返回单一效果。
* @Param: string->$vary, array->$value.
* @Return: array.
*/
function getvary($vary, $value)
{
	switch($vary)
	{   // ac,luck,mc,hit,miss,speed,hp,mp,shbb;
		case "addac": $ret['ac'] = str_replace('%','',$value[1])/100;break;
		case "luck": $ret['luck'] = $value['1'].':'.(str_replace('%','',$value[2])/100);break;
		case "addmc": $ret['mc'] = str_replace('%','',$value[1])/100;break;
		case "addhit": $ret['hit'] = str_replace('%','',$value[1])/100;break;
		case "addmiss": $ret['miss'] = str_replace('%','',$value[1])/100;break;
		case "addspeed": $ret['speed'] = str_replace('%','',$value[1])/100;break;
		case "addhp": $ret['hp'] = str_replace('%','',$value[1])/100;break;
		case "addmp": $ret['mp'] = str_replace('%','',$value[1])/100;break;
		case "shbb": $ret['shbb'] = true;break;
	}
	return $ret;
}

/*
主宠物+副宠物=随机(宠物A，宠物B,，C失败)
其中随机的几率:
宠物A：25%
宠物B：5%
失败：70%
@Param: 道具附加属性。
@Return: 1=>a 2=>b 0->fail
成长段在>=1与<5时 合成成功率为100%
成长段在>=5与<10时 合成成功率为固定值 50%
成长段在>=10与<15时 合成成功率为固定值20%
成长段在>=15时 合成成功率使用以前的几率。

*/
function getSuccess($props,$czl,$id1,$id2)
{
	if(!empty($id1) && $id1 == 872)
	{
		return 2;
		exit;
	}
	if(!empty($id2) && $id2 == 872)
	{
		return 2;
		exit;
	}
	if($czl >= 1 && $czl < 5)
	{
		$num = rand(1,2);
		return $num;
	}
	else if($czl >= 5 && $czl < 10)
	{
		$sus = rand(1,2);
		if($sus == 1)
		{
			$num = rand(1,2);
		}
		else
		{
			$num = 0;
		}
		return $num;
	}
	else if($czl >= 10 && $czl < 15)
	{
		$sus = rand(1,5);
		if($sus == 1)
		{
			$num = rand(1,2);
		}
		else
		{
			$num = 0;
		}
		return $num;
	}
	else
	{
		$lucka = 0;
		$luckb = 0;
		if (is_array($props))
		{
			foreach ($props as $k => $v)
			{
				if ($v['luck']!='')
				{
					$arr = explode(':', $v['luck']);
					if (strtolower($arr[0]) == 'b') $luckb += $arr[1];
					else if(strtolower($arr[0]) == 'a') $lucka += $arr[1];
				}
			}
		}
		
		$arand = round(($lucka+0.80),2)*100;
		$brand = round(($luckb+0.60),2)*100;
		$af = $bf = 0;
		$arand = $arand>100?100:$arand;
		$brand = $brand>100?100:$brand;
	
	
		$okarand = rand($arand,100);
		$okbrand = rand($brand,100);
		
		if ($okarand == $arand ) $af = 1;
		if ($okbrand == $brand ) $bf = 1;
	
		if ($af == 1) return 1;
		else if($bf == 1) return 2;
		else return 0;
	}
}
/*
*@Usage:计算合成后的宠物单一属性。
* a,b,p=> $props attrib.
*@Return: int.
*@Memo 属性=[宠物资料数据库属性+取整（主怪物属性*主宠物等级/400）+取整（副怪物属性*副宠物等级/800）]*(100%+道具附加属性%)
*/
function getPa($old, $a, $b ,$p)
{	
	global $app,$bpp;
	if ($p == '' || $p<=0) $p=1;
	else $p = 1+$p;

	return intval(($old+(intval($a*$app['level']/400)+intval($b*$bpp['level']/800)))*$p);
}

/**
*@Usage: 获得合成加入道具的各项属性值。
*@ Return: float.
*/
function getPlus($parr, $name)
{
	$ret = 0;
	if (!is_array($parr)) return 0;
	foreach ($parr as $k => $rs)
	{
		if ($rs[$name]!='' && $rs[$name]>0)
		{
			$ret +=$rs[$name];
		}
	}
	return $ret;
}

/**
*@Usage: 删除添加到合成中的材料。
*@Param:  void(0)
*@Return: void(0)
*/
function delProps()
{
	global $pp1, $pp2, $_pm;	// props first,props second, global object array.
	if (is_array($pp1))
	{
		$_pm['mysql']->query("UPDATE userbag
								 SET sums=abs(sums-1)
						       WHERE id={$pp1['id']} and uid={$_SESSION['id']} and sums > 0
							");
	}
	if (is_array($pp2))
	{
		$_pm['mysql']->query("UPDATE userbag
								 SET sums=abs(sums-1)
						       WHERE id={$pp2['id']} and uid={$_SESSION['id']} and sums > 0
							");
	}
}
?>