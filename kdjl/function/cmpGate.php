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
session_start();
require_once('../config/config.game.php');
secStart($_pm['mem']);
require_once('../sec/dblock_fun.php');
$a = getLock($_SESSION['id']);
if(!is_array($a)){
	realseLock();
	die('11');
}
$ap	    = intval($_REQUEST['ap']);  // table userbb->id
$bp 	= intval($_REQUEST['bp']);  // table userbb->id
$p1 	= intval($_REQUEST['p1']);  // table userbag->id
$p2 	= intval($_REQUEST['p2']);  // table userbag->id
$srctime = 5;
if ($p1<0) $p1 = 0;
if ($p2<0) $p2 = 0;
if($ap < 0 || $bp < 0){
	realseLock();
	die();
}
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
	if($ctime < $srctime && $_GET['type'] != 'do' && $_GET['type1'] != 'check')
	{
		die("11");//没有达到间隔时间
	}
	else
	{
		$_SESSION['time'.$_SESSION['id']] = time();
	}
}




#################是否选了护宠仙石结束############
if($_GET['type1'] != 'check') //判断一次就够了
{
	$sql_props = 'SELECT pid FROM userbag WHERE (id='.$p1.' or id='.$p2.') and uid='.$_SESSION['id'];
	$props = $_pm['mysql'] -> getRecords($sql_props);
	if(is_array($props))
	{
		$check_props = 0;
		foreach ($props as $key_props => $key_value)//Array ( [pid] => 771 )
		{
			$a = 'SELECT effect FROM props WHERE varyname=8 and id='.$key_value['pid'];
			$cmpProps = $_pm['mysql'] -> getOneRecord($a);
			if(is_array($cmpProps))//Array ( [effect] => hecheng:A:10%,B:4%|addczl:8%|1 ) 
			{
				$key_values = substr($cmpProps['effect'],-1,1);
				if($key_values == '1')
				{
					$check_props = $check_props+1;
				}
			}

		}
		if($check_props == 0)
		{
			die('200');
		}
	}
	else
	{
		die('200');
	}
}

##################增加在这里结束#################
if($_GET['type'] != 'do'){
	$zbcheck = $_pm['mysql'] -> getRecords("SELECT id FROM userbag WHERE zbpets = $ap or zbpets = $bp");//echo "SELECT id FROM userbag WHERE zbpets = $ap or zbpets = $bp";
	if(count($zbcheck) >= 1){//echo __LINE__."<br>";
		realseLock();
		die('1000');
	}//echo __LINE__."<br>";
}


/*if(lockItem($ap) === false)
{
	die('已经在处理了！');
}*/
if ($ap<0 || $bp<0) {
	realseLock();
	die('0');
}




$user		= $_pm['user']->getUserById($_SESSION['id']);//一个用户的所有信息player
$userbb		= $_pm['user']->getUserPetById($_SESSION['id']);//一个用户所有的宠物信息userbb
if(!empty($p1))
{
	$pp1 = $_pm['user']->getUserItemById($_SESSION['id'],$p1);//道具一资料userbag
	if($pp1['sums'] < 1){
		realseLock();
		die('20');
	}
}
if(!empty($p2))
{
	$pp2 = $_pm['user']->getUserItemById($_SESSION['id'],$p2);//道具二资料userbag
	if($pp2['sums'] < 1){
		realseLock();
		die('20');
	}
}

$log = '';

if ( is_array($userbb))
{
	foreach ($userbb as $key => $rs)
	{
		if ($rs['id']==$ap && $rs['level']>=40) // From bb base find user current bb.
		{
			$app = $rs;//主宠信息（数组）userbb
		}
		if ($rs['id']== $bp && $rs['level']>=40)
		{
			$bpp = $rs;//副宠信息（数组）userbb
		}
	}
    unset($rs);
	
	$cishu=$_pm['mysql']->getOneRecord("select hecheng_nums,chouqu_chongwu from player_ext where uid={$_SESSION['id']}");
	if(strpos($cishu['chouqu_chongwu'],','.$app['id'].',')!==false||strpos($cishu['chouqu_chongwu'],','.$bpp['id'].',')!==false)
	{
		die("某个宠物抽取过成长,不能进行合成!");
	}

	if($p1 == $p2 && $p1 != 0)
	{
		if($pp1['sums'] < 2)
		{
			realseLock();
			die("100");
		}
	}

	if (!is_array($app) || !is_array($bpp) || ($app['id'] == $bpp['id'])) {
		realseLock();
		die('1'); //没有对应的宠物。
	}
	
	// 检查是否满足公式。
	//$ars = $_pm['mem']->dataGet(array('k' => MEM_BB_KEY, 
	//									 'v' => "if(\$rs['name'] == '{$app['name']}') \$ret=\$rs;"//bb
	//						  ));
	//$brs = $_pm['mem']->dataGet(array('k' => MEM_BB_KEY, 
	//									 'v' => "if(\$rs['name'] == '{$bpp['name']}') \$ret=\$rs;"//bb
	//						  ));
	$membbname = unserialize($_pm['mem']->get('db_bbname'));
	$ars = $membbname[$app['name']];
	//print_r($ars);exit;
	$brs = $membbname[$bpp['name']];

	$cmprs = $_pm['mysql']->getOneRecord("SELECT * 
											FROM merge
										   WHERE aid = {$ars['id']} and bid={$brs['id']}
										   LIMIT 0,1
	                                    ");
    if (!is_array($cmprs)) {
		realseLock();
		die('2');	//不能合成，
	}
	//检查是否有成长限制
	$max_czl = 0;
	if(!empty($cmprs['limits']))
	{
		$limitsarr = explode('|',$cmprs['limits']);
		if(!empty($limitsarr[0]) && $app['czl'] < $limitsarr[0])//主宠成长限制
		{
			realseLock();
			die('15');
		}
		if(!empty($limitsarr[1]) && $bpp['czl'] < $limitsarr[1])//副宠成长限制
		{
			realseLock();
			die('15');
		}
		if(!empty($limitsarr[1]) && count($limitsarr) == 3 )
		{
			$max_czl = $limitsarr[2];
		}
	}								
					  
	$money=0;
	$money=$user['money'];
	if($user['money'] < 50000)
	{
		realseLock();
		die('3');//	金币不足
	}
		
	$propseff = getEffect($pp1, $pp2);//Array ( [0] => hecheng:A:1%,B:0% [1] => addczl:0% [2] => 1 [3] => hecheng:A:15%,B:3% [4] => addczl:20% [5] => 2 ) 

	$sus = getSuccess($app,$bpp,$pp1,$pp2);//成功率公式返回一个数字2->B宠 1->A宠
	//echo 'sus:'.$sus.'<br />';
	$czl = bbczl($app,$bpp,$pp1,$pp2);///获得成长率->一个百分小数23.2
	if($czl > $max_czl && $max_czl != 0)
	{
		$czl = $max_czl;
	}
//$sus = 1;

	if ($sus) // 合成成功。a,b宠物消失，得到新的宠物。$cmprs=> 得到相关宝宝信息。
	{

			// 改变属性地方为:
		if ($sus == 1) $newbid = $cmprs['maid'];
		if ($sus == 2) $newbid = $cmprs['mbid'];
		//echo 'newbb:'.$newbid.'<br />';exit;
		$brs = $_pm['mysql']->getOneRecord("SELECT * 
											  FROM  bb
											 WHERE id={$newbid}
											 LIMIT 0,1
										  ");
										  
		if (!is_array($brs))
		{
			realseLock();
			die('10'); // 数据错误
		}
		// 改变各项数据:
		makebb($brs,$max_czl);
		$cstatus = 2;
	}
	else // 如果没有相关道具进行绑定，副宠消失
	{


		$cstatus = 1;
	}

	$user['money'] = $user['money']-50000;		// 减少用户金币.
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
//$cstatus=1;
	if ($cstatus == 1) //副宠消失。合成失败
	{
		//在此写入一张表
		if(!isset($cishu)) $cishu=$_pm['mysql']->getOneRecord("select hecheng_nums from player_ext where uid={$_SESSION['id']}");
		$nums2=$_pm['mysql']->query("update player_ext set hecheng_nums=".($cishu[hecheng_nums]+1)." where uid={$_SESSION['id']}");
	
		$del = 1;
		$log .= '合成道具详细：';
		if(is_array($propseff))
		{
			if(!empty($pp1))
			{
				$log .= $pp1['name'].'-';
			}
			if(!empty($pp2))
			{
				$log .= $pp2['name'].'-';
			}
			//$pp1['name']$pp1['effect']
			
			//$log .= $n['shbb']."-";
			if ($propseff[2] == 1 || $propseff[5] == 1)
			{
				$del = 0;
			}
		}
	
		if ($del == 1)//副宠消失的条件
		{
			clearBB($bpp);
			$log .= 'name:'.$bpp['name'].'level:'.$bpp['level'].'czl:'.$bpp['czl'].'ac:'.$bpp['ac'].'srchp:'.$bpp['srchp'].'hits:'.$bpp['hits'];
		}
		$log = addslashes($log);
		// 合成失败记录点：
		$_pm['mysql']->query("INSERT INTO gamelog(ptime,seller,buyer,pnote,vary)
		                      VALUES(unix_timestamp(),'{$_SESSION['id']}','{$_SESSION['id']}','{$log}',2)
							");
		realseLock();
		die('6');
	}
	else if($cstatus == 2) // 成功。
	{
		$nums3=$_pm['mysql']->query("update player_ext set hecheng_nums=0 where uid={$_SESSION['id']}");
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
		$newbbarr = $_pm['mysql'] -> getOneRecord("SELECT level,czl,ac,hits,srchp FROM userbb WHERE name = '{$brs[name]}' and uid = {$_SESSION['id']} order by id desc");
		
		$str = '新宠物名字：'.$brs['name'].'level:'.$newbbarr['level'].'czl:'.$newbbarr['czl'].'ac:'.$newbbarr['ac'].'hits:'.$newbbarr['hits'].',使用物品1：'.$pp1['name'].',使用物品2：'.$pp2['name'].',宠物：'.$app['name'].'level:'.$app['level'].'czl:'.$app['czl'].'ac:'.$app['ac'].'hits:'.$app['hits'].'-'.$bpp['name'].'level:'.$bpp['level'].'czl:'.$bpp['czl'].'ac:'.$bpp['ac'].'hits:'.$bpp['hits'];
		$_pm['mysql']->query("INSERT INTO gamelog(ptime,seller,buyer,pnote,vary)
		                      VALUES(unix_timestamp(),'{$_SESSION['id']}','{$_SESSION['id']}','{$str}',4)
							");
		
		foreach($arr as $k=>$v)
		{
			$retstr .= $v.'linend';
		}

		$retstr = $retstr.$newstr;
		$_pm['mem']->set( array('k'=>$msg_key, 'v'=>$retstr) ); // default ten min.
		
		// 合成成功连接socket之前做记录
		$_pm['mysql']->query("INSERT INTO gamelog(ptime,seller,buyer,pnote,vary)
		                      VALUES(unix_timestamp(),'{$_SESSION['id']}','{$_SESSION['id']}','合成成功连接socket之前做记录',173)
							");
		clearBB($app); // del pets master
		clearBB($bpp); // del pets other
		
		//----------------------------------------------------------------------------------------------------------------------
		//$_olddata = @unserialize($_pm['mem']->get('ttmt_data_notice'));		
		require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
		$s=new socketmsg();
		$s->sendMsg('an|'.$newstr);
		
		// 合成成功连接socket之后做记录
		$_pm['mysql']->query("INSERT INTO gamelog(ptime,seller,buyer,pnote,vary)
		                      VALUES(unix_timestamp(),'{$_SESSION['id']}','{$_SESSION['id']}','合成成功连接socket之后做记录',173)
							");
		//$_olddata['an'] = isset($_olddata['an'])?$_olddata['an']."<br/>[系统公告]：".$swfData:$swfData;
		//$_pm['mem']->set(array('k'=>'ttmt_data_notice','v'=>$_olddata));
		//----------------------------------------------------------------------------------------------------------------------
		
		
		realseLock();
		die('5');
	}
}
else {
	realseLock();
	die('000');
}
realseLock();
$_pm['mem']->memClose();
// Logic code end.



/**
* @Usage: 创建新的宠物。
* @Param: array -> $bb.
* @Return: Void(0);
*/
function makebb($bb,$max_czl)
{$czl=0;
//echo "\r\n";
	global $app,$bpp,$pp1,$pp2,$user,$_pm,$propseff;
	$czl = bbczl($app,$bpp,$pp1,$pp2);
	$ac=getPlus($propseff,'ac');
	$mc=getPlus($propseff,'mc');
	$hit=getPlus($propseff,'hit');
	$miss=getPlus($propseff,'miss');
	$speed=getPlus($propseff,'speed');
	$hp=getPlus($propseff,'hp');
	$mp=getPlus($propseff,'mp');
	
	// ac,luck,mc,hit,miss,speed,hp,mp,shbb,czl; 
	$bb['ac']	= getPa($bb['ac'], $app['ac'], $bpp['ac'] ,getPlus($propseff,'ac'));#### 暂时没有加入道具附加属性。
    $bb['mc']	= getPa($bb['mc'], $app['mc'], $bpp['mc'] ,getPlus($propseff,'mc'));
	$bb['hits']	= getPa($bb['hits'], $app['hits'], $bpp['hits'] ,getPlus($propseff,'hit'));
    $bb['miss']	= getPa($bb['miss'], $app['miss'], $bpp['miss'] ,getPlus($propseff,'miss'));
	$bb['speed']= getPa($bb['speed'], $app['speed'], $bpp['speed'] ,getPlus($propseff,'speed'));
	$bb['hp']	= getPa($bb['hp'], $app['hp'], $bpp['hp'] ,getPlus($propseff,'hp'));
	$bb['mp']	= getPa($bb['mp'], $app['mp'], $bpp['mp'] ,getPlus($propseff,'mp'));
	
	$uinfo = $user;
	if($bb['wx']==6 && $czl>60)
	{
		$czl=60;
	}
	else if($bb['wx']!=6 && $czl>150)
	{
		$czl=150;
	}
	if($max_czl != 0 && $czl > $max_czl)
	{
		$czl = $max_czl;
	}
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
	
	$jnall = split(",", $bb['skillist']);//1:1,60:1
	
	$membbname = unserialize($_pm['mem']->get('db_skillsysid'));
	
	foreach($jnall as $a => $b)
	{
		$arr = split(":", $b);
		// Get jn info.
		
		//$memjnid = $this->m_m->unserialize(get('db_skillsysid'));
		$jn = $membbname[$arr[0]];
		/*$jn = $_pm['mem']->dataGet(array('k'	=>	MEM_SKILLSYS_KEY,
								'v'	=>  "if(\$rs['id'] == '{$arr[0]}') \$ret=\$rs;"
						));*/
		// #################################################				
		if ($jn['ackvalue']=='')
		{
			$_pm['mysql']->query("INSERT INTO gamelog(ptime,seller,buyer,pnote,vary)
		                      VALUES(unix_timestamp(),'{$_SESSION['id']}','{$_SESSION['id']}','".$arr[0]."技能攻击为0',173)
							");
			continue; // 增加辅助技能。
		}
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
	//return;
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
	/*$_pm['mysql']->query("DELETE FROM userbag
				 WHERE uid={$_SESSION['id']} and zbpets={$id}
			  ");*/
	$arr = $_pm['mysql'] -> getRecords("SELECT id,pid FROM userbag WHERE uid = {$_SESSION['id']} and zbpets = {$id}");
	if(is_array($arr)){
		foreach($arr as $v){
			if(!empty($v)){
				$_pm['mysql']->query("DELETE FROM userbag
				 WHERE uid={$_SESSION['id']} and pid = {$v['pid']} and zbpets = {$id}
			  ");
			}
		}
	}
	// del bb.
	$_pm['mysql']->query("DELETE FROM userbb
				 WHERE uid={$_SESSION['id']} and id={$id}
			  ");
}

/**
* @Param: 宠物a,b的属性。
* @Return: 返回组合后的成长率。
  
成长段		对应公式							
51.0(不包括51.0)以下	主宠物成长+{[(主宠物等级/(主宠物成长+10))+(副宠物等级*副宠物成长/200)]*(100%+道具百分比)}								
51.0(包括51.0)—70.0	主宠物成长+{[(主宠物等级/主宠物成长)+(副宠物等级*副宠物成长/350)]*(100%+道具百分比)}								
70.0(包括70.0)—90.0	主宠物成长+{[(主宠物等级/主宠物成长)+(副宠物等级*副宠物成长/500)]*(100%+道具百分比)}	
90.0(包括90.0)—100.0	主宠物成长+{[(主宠物等级/主宠物成长)+(副宠物等级*副宠物成长/700)]*(100%+道具百分比)}								
100(包括100.0)以上	主宠物成长+{[(主宠物等级/主宠物成长)+(副宠物等级*副宠物成长/900)]*(100%+道具百分比)}								

*/
function bbczl($a, $b, $pp1, $pp2)
{
	global $brs; // 资料库中宠物属性。
	
	if (is_array($pp1))
	{
		$one = explode('|', $pp1['effect']);
		
		$arr_11 = explode(':', $one[1]);
		if($arr_11[0]=='addczl')
		{
		$arr_1 = str_replace('%','',$arr_11[1]);
		$arr = $arr_1/100; 
		}
		
		
		
	}
	unset($one,$arr_11,$arr_1);
	if (is_array($pp2))
	{
		$one = explode('|', $pp2['effect']);
		$arr_11 = explode(':', $one[1]);
		if($arr_11[0]=='addczl')
		{
		$arr_1 = str_replace('%','',$arr_11[1]);
		$arr += $arr_1/100; 
		}
		
	}
	unset($one,$arr_11,$arr_1);
	
	if($a['czl']<51.0)
	{
	$czl=round($a['czl']+($a['level']/($a['czl']+10)+$b['level']*$b['czl']/200)*(1+$arr),1);//23.2
	return $czl;
	}
	if($a['czl']<70.0 || $a['czl']>=51.0)
	{
	$czl=round($a['czl']+($a['level']/$a['czl']+$b['level']*$b['czl']/350)*(1+$arr),1);
	return $czl;
	}
	if($a['czl']<90.0 || $a['czl']>=70.0)
	{
	$czl=round($a['czl']+($a['level']/$a['czl']+$b['level']*$b['czl']/500)*(1+$arr),1);
	return $czl;
	}
	if($a['czl']<100.0 || $a['czl']>=90.0)
	{
	$czl=round($a['czl']+($a['level']/$a['czl']+$b['level']*$b['czl']/700)*(1+$arr),1);
	return $czl;
	}
	if($a['czl']>=100.0)
	{
	$czl=round($a['czl']+($a['level']/$a['czl']+$b['level']*$b['czl']/900)*(1+$arr),1);
	return $czl;
	}
	//return $czl;
}

/**
*@Usage: 获取合成中添加道具的所有效果=》为一个6个元素数组
*@Return: array.
*/
function getEffect($pp1, $pp2)
{

	if (is_array($pp1))
	{
		$one = explode('|', $pp1['effect']);
		foreach ($one as $a => $b)
		{
			$one1[] = $b;
		}
		unset($one);
	}
	if (is_array($pp2))
	{
		$one = explode('|', $pp2['effect']);
		foreach ($one as $a => $b)
		{
			$one1[] = $b;
		}
		unset($one);
	}
	// 组合效果。
	return $one1;

}


/**
* @Usage: 返回单一效果。
* @Param: string->$vary, array->$value.
* @Return: array.
*/
function getvary($vary, $value)//hecheng:A:15%|B:3%|addspeed:15%|2
{
	switch($vary)
	{   // ac,luck,mc,hit,miss,speed,hp,mp,shbb,czl;  hecheng:A:15%,B:3%|addspeed:15%|2
	//$value[1]   0.15 $ret['ac'] = 0.15   $ret['hp'] = 0.15  $ret=array();
		case "addac": $ret['ac'] = str_replace('%','',$value[1])/100;break;
		case "luck": $ret['luck'] = $value['1'].':'.(str_replace('%','',$value[2])/100);break;
		case "addmc": $ret['mc'] = str_replace('%','',$value[1])/100;break;
		case "addhit": $ret['hit'] = str_replace('%','',$value[1])/100;break;
		case "addmiss": $ret['miss'] = str_replace('%','',$value[1])/100;break;
		case "addspeed": $ret['speed'] = str_replace('%','',$value[1])/100;break;
		case "addhp": $ret['hp'] = str_replace('%','',$value[1])/100;break;
		case "addmp": $ret['mp'] = str_replace('%','',$value[1])/100;break;
		case "addczl": $ret['czl'] = str_replace('%','',$value[1])/100;break;
		case "B": $ret['B'] = str_replace('%','',$value[1])/100;break;
		case "shbb": $ret['shbb'] = true;break;
	}
	return $ret;
}

/*
公式：												
新合成成功公式为(取1位小数)：		[合成次数/(主宠成长*2)]+[(主宠等级+副宠等级)/15]*0.01+(道具百分比)+[(随机1~5)*0.01]											
合成判断成功后，先随机：B阶成功百分比（默认5%）+（B阶道具百分比） 成功后合成为稀有宠（B）			失败后合成为普通宠(A)		
*/
function getSuccess($app,$bpp,$pp1,$pp2)
{
	global $_pm;
	if (is_array($pp1))
	{
		$one = explode('|', $pp1['effect']);
		$arr = explode(',', $one[0]);
		$arr_2 = explode(':',$arr[0]);		
		$arr_21 = str_replace('%','',$arr_2[2]);
		$arr2 = $arr_21/100;
		
		$arr_3 = explode(':',$arr[1]);
		$arr_31 = str_replace('%','',$arr_3[1]);
		$arr4 = $arr_31/100;		
		unset($arr_2,$arr_21,$one);
	}
	
	if (is_array($pp2))
	{

		$one = explode('|', $pp2['effect']);
		$arr = explode(',', $one[0]);       
		$arr_2 = explode(':',$arr[0]);		
		$arr_21 = str_replace('%','',$arr_2[2]);
		$arr2 += $arr_21/100;
		
		$arr_3 = explode(':',$arr[1]);
		$arr_31 = str_replace('%','',$arr_3[1]);
		$arr4 += $arr_31/100;
	}
	
	$nums="select hecheng_nums from player_ext where uid={$_SESSION['id']}";
	$cishu = $_pm['mysql'] -> getOneRecord($nums);
	$chenggonglv=($cishu['hecheng_nums']/($app['czl']*2))+(($app['level']+$bpp['level'])/15)*0.01+$arr2+(rand(1,5)*0.01);
	//echo "<br />".$arr2."<br />";
	$success=round($chenggonglv,1);

	if($cishu['hecheng_nums']==10 || $app['czl']<=5)//当幸运星达到10颗时，合成率为100%
	{
		$success=1.0;
	}

	$a=rand(1,100)/100;
	if($a<=$success)//成功的处理
	{
		//echo 'B:'.$arr4.'<br />';exit;
		$chance=0.05+$arr4;
		$chance_rand=rand(1,100)/100;
		if($chance_rand<=$chance)
		{
			//合成B宠物
			unset($success);
			return 2;
			
		}
		else
		{
			//合成A宠物
			unset($success);
			return 1;
			
		}
		
	}
	else//失败的处理
	{
	unset($success);
		return false;
	}
}
/*
*@Usage:计算合成后的宠物单一属性。
* a,b,p=> $props attrib.
*@Return: int.
*@Memo 属性=取整{[宠物资料数据库属性+取整（主宠物属性*主宠物等级/400）+取整（副宠物属性*副宠物等级/800）]*(100%+道具附加属性%)}
*/
function getPa($old, $a, $b ,$p)
{	
	global $app,$bpp;
	if ($p == '' || $p<=0) $p=1;
	else $p = 1+$p;

	return intval(($old+(intval($a*$app['level']/400)+intval($b*$bpp['level']/800)))*$p);
}


/**
*@Usage: 获得合成加入道
具的各项属性值。
*@ Return: float.
*/
function getPlus($parr,$a)//Array([0] => hecheng:A:15%,B:3% [1] => addczl:20% [2] => 2 [3] => hecheng:A:15%,B:3% [4] => addczl:20% [5] => 2)  
{
	$czl1 = 0;
	$czl2 = 0;
	$czl = 0;
	
	if (!is_array($parr)) return 0;
	$arr = explode(':',$parr[1]);//$arr[0]=addczl $arr[1]=15%
	$arr2 = substr($arr[0], 3); //czl mp cp  
	if(count($parr)==6)
	{
	$arr1 = explode(':',$parr[4]);
	$arr3 = substr($arr1[0], 3); //czl mp cp 
	}
	switch ($arr2)
			{
				case "czl":
					if($a=='czl')
					{
						$czl1 = str_replace('%','',$arr[1])/100;//$arr[1]=0.15最终要返回这个数字
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					else
					{
						$czl1=0;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					break;
				case "ac":
					if($a=='ac')
					{
						$czl1 = str_replace('%','',$arr[1])/100;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					else
					{
						$czl1=0;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					break;
				case "mc":
					if($a=='mc')
					{
						$czl1 = str_replace('%','',$arr[1])/100;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					else
					{
						$czl1=0;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					break;
				case "hit":
					if($a=='hits')
					{
						$czl1 = str_replace('%','',$arr[1])/100;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					else
					{
						$czl1=0;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					break;
				case "miss":
					if($a=='miss')
					{
						$czl1 = str_replace('%','',$arr[1])/100;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					else
					{
						$czl1=0;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					break;
				case "speed":
					if($a=='speed')
					{
						$czl1 = str_replace('%','',$arr[1])/100;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					else
					{
						$czl1=0;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					break;
				case "hp":
					if($a=='hp')
					{
						$czl1 = str_replace('%','',$arr[1])/100;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					else
					{
						$czl1=0;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					break;
				case "mp":
					if($a=='mp')
					{
						$czl1 = str_replace('%','',$arr[1])/100;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					else
					{
						$czl1=0;
						if(count($parr)==3)
						{
						return $czl1;
						}
					}
					break;
			}
			switch ($arr3)
			{
				case "czl":
					if($a=='czl')
					{
							$czl2 = str_replace('%','',$arr1[1])/100;
							$czl = $czl1+$czl2;
							return $czl;
							
					}
					else
					{
						return $czl1;
					}
					break;
				case "ac":
					if($a=='ac')
					{
						 	$czl2 = str_replace('%','',$arr1[1])/100;
							return $czl = $czl1+$czl2;
					}
					else
					{
						return $czl1;
					}
					break;
				case "mc":
					if($a=='mc')
					{
							$czl2 = str_replace('%','',$arr1[1])/100;
							return $czl = $czl1+$czl2;
					}
					else
					{
						return $czl1;
					}
					break;
				case "hit":
					if($a=='hits')
					{
							$czl2 = str_replace('%','',$arr1[1])/100;
							return $czl = $czl1+$czl2;
					}
					else
					{
						return $czl1;
					}
					break;
				case "miss":
					if($a=='miss')
					{
							$czl2 = str_replace('%','',$arr1[1])/100;
							return $czl = $czl1+$czl2;
					}
					else
					{
						return $czl1;
					}
					break;
				case "speed":
					if($a=='speed')
					{
							$czl2 = str_replace('%','',$arr1[1])/100;
							return $czl = $czl1+$czl2;
					}
					else
					{
						return $czl1;
					}
					break;
				case "hp":
					if($a=='hp')
					{
							$czl2 = str_replace('%','',$arr1[1])/100;
							return $czl = $czl1+$czl2;
					}
					else
					{
						return $czl1;
					}
					break;
				case "mp":
					if($a=='mp')
					{
							$czl2 = str_replace('%','',$arr1[1])/100;
							return $czl = $czl1+$czl2;
					}
					else
					{
						return $czl1;
					}
					break;
			}


}

/**
*@Usage: 删除添加到合成中的材料。
*@Param:  void(0)
*@Return: void(0)
*/
function delProps()
{
//return;
	global $pp1, $pp2, $_pm;	// props first,props second, global object array.
	if (is_array($pp1))
	{
		$_pm['mysql']->query("UPDATE userbag
								 SET sums=abs(sums-1)
						       WHERE id={$pp1['id']} and uid={$_SESSION['id']} and sums > 0
							");
		//echo mysql_affected_rows($_pm['mysql'] -> getConn()).'<br />';
		if(mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){
			realseLock();
			die('20');
		}
	}
	if (is_array($pp2))
	{
		$_pm['mysql']->query("UPDATE userbag
								 SET sums=abs(sums-1)
						       WHERE id={$pp2['id']} and uid={$_SESSION['id']} and sums > 0
							");
		if(mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){
			realseLock();
			die('20');
		}
	}
}
?>