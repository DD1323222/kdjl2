<?php
header('Content-Type:text/html;charset=gbk');
require_once('../config/config.game.php');
secStart($_pm['mem']);
$id = intval($_GET['id']);

require_once('../sec/dblock_fun.php');
$a = getLock($_SESSION['id']);
if(!is_array($a)){
	realseLock();
	die('服务器繁忙，请稍候再试！');
}
if($_GET['op'] == 'img'){
	if($id < 0){
		realseLock();
		die('id小于1');//id小于1
	}
	$membbid = unserialize($_pm['mem']->get('db_bbid')); 
	$bbarr = $_pm['mysql'] -> getRecords('SELECT super_zs.id,next_pet_id FROM super_zs WHERE cur_pet_id = '.$id);//echo 'SELECT next_pet_id,cardimg FROM super_zs,bb WHERE bb.id=super_zs.cur_pet_id AND cur_pet_id = '.$id;
	//print_r($bbarr);exit;
	if(!is_array($bbarr)){
		realseLock();
		die('未开放');
	}
	foreach($bbarr as $k => $v){
		if($k == 0){
			$res .= '<div class="sd_pet r00" id="p_p'.($k+1).'">
		<img src="'.IMAGE_SRC_URL.'/bb/'.$membbid[$v['next_pet_id']]['cardimg'].'" onclick="sszsstr('.$v["id"].',0,this);sszsbbid='.$v["id"].';copyWord(\''.$v["name"].'\')" style="opacity:1; filter : progid:DXImageTransform.Microsoft.Alpha(style=0,opacity=100,finishOpacity=100);cursor:pointer;" />
		</div>';
		}else{
			$res .= '<div class="sd_pet r00" id="p_p'.($k+1).'">
		<img src="'.IMAGE_SRC_URL.'/bb/'.$membbid[$v['next_pet_id']]['cardimg'].'" onclick="sszsstr('.$v["id"].',0,this);sszsbbid='.$v["id"].';copyWord(\''.$v["name"].'\')" style="opacity:0.5; filter : progid:DXImageTransform.Microsoft.Alpha(style=0,opacity=50,finishOpacity=50);cursor:pointer;" />
		</div>';
		}
	}
	echo $res;
}else if($_GET['op'] == 'str'){
	$sid = intval($_GET['sid']);
	if($sid >０){
		$sql = 'SELECT next_pet_id,name,need_level,need_czl,need_props,next_pet_id FROM super_zs,bb WHERE bb.id = cur_pet_id AND cur_pet_id = '.$sid.' limit 1';
	}else{
		$sql = 'SELECT next_pet_id,name,need_level,need_czl,need_props,next_pet_id FROM super_zs,bb WHERE bb.id = cur_pet_id AND super_zs.id = '.$id;
	}
	$arr = $_pm['mysql'] -> getOneRecord($sql);
	if(!is_array($arr)){
		realseLock();
		die('未开放');
	}
	$memprops = unserialize($_pm['mem']->get('db_propsid')); 
	$parr1 = explode(',',$arr['need_props']);
	foreach($parr1 as $v){
		$parr = explode('|',$v);
		$pstr .= $memprops[$parr[0]]['name'].'×'.$parr[1].'，';
	}
	//转生阶段x100000
	$m = $_pm['mysql'] -> getOneRecord('SELECT zs_progress FROM super_jh WHERE pet_id ='.$arr['next_pet_id']);
	$needmoney = $m['zs_progress'] * 100000;
	$membbid = unserialize($_pm['mem']->get('db_bbid'));
	$pstr = substr($pstr,0,-2);
	$str = '转生需求等级：'.$arr['need_level'].'<br />
			转生需求成长：'.$arr['need_czl'].'<br />
			转生所需金币：'.$needmoney.'<br />
			转生所需材料：'.$pstr.'<br />
			转生后宠物：'.$membbid[$arr['next_pet_id']]['name'].'	 
		  ';
	echo $str;
}else if($_GET['op'] == 'zs'){
	$oldbid = intval($_GET['old']);
	$newbid = intval($_GET['newid']);
	$wp1 = intval($_GET['wp1']);
	$wp2 = intval($_GET['wp2']);
	$srctime = 30;
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
			realseLock();
			die("请稍候操作！");//没有达到间隔时间
		}
		else
		{
			$_SESSION['time'.$_SESSION['id']] = time();
		}
	}

	if($oldbid < 1 && $newbid < 1){
		realseLock();
		die('您没有选择要转生的宠物，或者您选择的不是神圣宠物！');
	}
	$bb = $_pm['mysql'] -> getOneRecord('SELECT * FROM userbb WHERE id = '.$oldbid);
	if(!is_array($bb)){
		realseLock();
		die('没有相应的宠物！');
	}
	
	if($bb['wx'] != 7){
		realseLock();
		die('该宠物非神圣宠物，不能使用此功能！');
	}
	
	if($newbid > 0){
		$sql = 'SELECT cur_pet_id,need_level,need_czl,need_props,base_success_rate,next_pet_id FROM super_zs WHERE id = '.$newbid;
	}else {
		$membbname = unserialize($_pm['mem']->get('db_bbname'));
		$sql = 'SELECT cur_pet_id,need_level,need_czl,need_props,base_success_rate,next_pet_id FROM super_zs WHERE cur_pet_id = '.$membbname[$bb['name']]['id'].' limit 1';
	}
	$need = $_pm['mysql'] -> getOneRecord($sql);
	
	//判断条件是否满足
	if($bb['level'] < $need['need_level'] || $bb['czl'] < $need['need_czl']){
		realseLock();
		die('等级不足'.$need['need_level'].'或成长不足'.$need['need_czl']);
	}
	
	//需要物品判断
	$parr1 = explode(',',$need['need_props']);
	if(is_array($parr1)){
		foreach($parr1 as $v){
			$parr = explode('|',$v);
			$b = $_pm['mysql'] -> getOneRecord('SELECT id,sums FROM userbag WHERE uid = '.$_SESSION['id'].' AND pid = '.$parr[0].' AND sums >= '.$parr[1]);//echo 'SELECT id,sums FROM userbag WHERE uid = '.$_SESSION['id'].' AND pid = '.$parr[0].' AND sums >= '.$parr[1];
			if(!is_array($b)){
				realseLock();
				die('相应的必须品不够！');
			}
		}
	}
	
	if($wp1 == $wp2 && $wp1 > 0){
		$wpcheck = $_pm['mysql'] -> getOneRecord('SELECT sums FROM userbag WHERE id = '.$wp1.' AND sums >= 2');
		if(!is_array($wpcheck)){
			realseLock();
			die('道具不足！');
		}
	}
	$p1=$p2=array();
	if($wp1 > 0){
		$p1 = $_pm['mysql'] -> getOneRecord( 'SELECT pid,effect FROM userbag,props WHERE userbag.id='.$wp1.' AND props.id = userbag.pid');
	}
	if($wp2 > 0){
		$p2 = $_pm['mysql'] -> getOneRecord( 'SELECT pid,effect FROM userbag,props WHERE userbag.id='.$wp2.' AND props.id = userbag.pid');
	}
	$limit = $_pm['mysql'] -> getOneRecord('SELECT max_czl,zs_line,zs_progress FROM super_jh WHERE pet_id = '.$need['next_pet_id']);
	if(strpos($p1['effect'],'sszs') === false && strpos($p2['effect'],'sszs') === false && strpos($p1['effect'],'cszsczlbh') === false&& strpos($p2['effect'],'cszsczlbh') === false && count($p1)>0&& count($p2)>0){//说明是加属性道具，不能同时加两个
		realseLock();
		die('同时不能加两个增加属性的道具！');
	}
	//金币判断
	$need_money = $limit['zs_progress'] * 100000;
	
	$user = $_pm['user']->getUserById($_SESSION['id']);
	if($user['money'] < $need_money){
		realseLock();
		die('金币不足'.$need_money);
	}
	if($wp1 > 0){
		$_pm['mysql']->query('UPDATE userbag SET sums = sums - 1 WHERE uid = '.$_SESSION['id'].' AND id = '.$wp1.' AND sums > 0');
		$result = mysql_affected_rows($_pm['mysql'] -> getConn());
		if($result != 1){
			realseLock();
			die("您没有相应的物品！");
		}
	}
	
	if($wp2 > 0){
		$_pm['mysql']->query('UPDATE userbag SET sums = sums - 1 WHERE uid = '.$_SESSION['id'].' AND id = '.$wp2.' AND sums > 0');
		$result = mysql_affected_rows($_pm['mysql'] -> getConn());
		if($result != 1){
			realseLock();
			die("您没有相应的物品！");
		}
	}
	
	$newbb = $_pm['mysql'] -> getOneRecord('SELECT * FROM bb WHERE id = '.$need['next_pet_id']);
	//计算成功率
	$sus = getSuc($bb['level'],$p1,$p2);
	
	if(is_array($parr1)){
		foreach($parr1 as $v){
			$parr = explode('|',$v);
			$sql = 'UPDATE userbag SET sums = sums - '.$parr[1].' WHERE uid = '.$_SESSION['id'].' AND pid = '.$parr[0].' AND sums >='. $parr[1];
			$b = $_pm['mysql'] -> getOneRecord($sql);
			$result = mysql_affected_rows($_pm['mysql'] -> getConn());
			if($result != 1){
				realseLock();
				die("相应的必须品不够！");
			}
		}
	}
	$_pm['mysql'] -> query('UPDATE player SET money = money - '.$need_money.' WHERE id = '.$_SESSION['id']);
	$num = rand(1,10000);//echo $num.'<br />';
	//$num = 0;
	if($num <= $sus){//成功
		$log .= $user['nickname'].' 成功:';
		$nbb = makebb($bb,$newbb,$limit['zs_progress'],$p1,$p2);
		clearBB($bb);
		$task = new task();
		$word = '获得神圣宠物 '.$nbb['name'];
		$task->saveGword($word);
		echo '5';
	}else{
		$log .= $user['nickname'].' 失败:';
		echo '6';
	}
	//日志部分
	if(!is_array($p1)){
		$p1['pid'] = 0;
	}
	if(!is_array($p2)){
		$p2['pid'] = 0;
	}
	$log .= '加入物品:'.$p1['pid'].'+'.$p2['pid'].',原宠物：'.print_r($bb,1);
	if(is_array($nbb)){
		$log .= '转生路线：'.$limit['zs_line'].',新宠物：'.print_r($nbb,1);
	}
	$log .= date('Y-m-d H:i:s');
	$_pm['mysql']->query("INSERT INTO gamelog(ptime,seller,buyer,pnote,vary)
		                      VALUES(unix_timestamp(),'{$_SESSION['id']}','{$_SESSION['id']}','{$log}',104)
							");
	realseLock();
}
function getSuc($level,$wp1,$wp2){
	/*基数=取两位小数【宠物等级/30*（1+道具效果）】*100
数值=1到10000中取随机数，当数值<=基数的时候成功*/
	global $_pm;
	$num = 0;
	if(count($wp1) > 0){
		$num += str_replace('sszs:','',$wp1['effect']);
	}
	if(count($wp2) > 0){
		$num += str_replace('sszs:','',$wp2['effect']);
	}
	//echo $level.'<br />'.$num.'=======>';
	$res = round(($level/30*(1+$num)),2) * 100;
	return $res;
}

function makebb($oldbb,$newbb,$zsjd,$wp1,$wp2){
	global $_pm;
	$eff = '';
	$czlnum = 10;
	if(count($wp1) > 0){
		if(strpos($wp1['effect'],'sszs') === false&&strpos($wp1['effect'],'cszsczlbh') === false){
			$eff = $wp1['effect'];
		} 
		if(strpos($wp1['effect'],'cszsczlbh') !== false){
			$czlnum += str_replace('cszsczlbh:','',$wp1['effect']);
		}
	}
	if(count($wp2) > 0){
		if($eff != ''){
			$czlnum += str_replace('cszsczlbh:','',$wp2['effect']);
		}else{
			if(strpos($wp2['effect'],'sszs') === false&&strpos($wp2['effect'],'cszsczlbh') === false){
				$eff = $wp2['effect'];
			}else{
				$czlnum += str_replace('cszsczlbh:','',$wp2['effect']);
			}
		}
	}
	$pac = $pmc = $phit = $pmiss = $pspeed = $php = $pmp = 0;
	if($eff != ''){
		$propseff = explode(':',$eff);
		switch($propseff[0]){
			case "addac": $pac = $propseff[1];break;
			case "addmc": $pmc = $propseff[1];break;
			case "addhit": $phit = $propseff[1];break;
			case "addmiss": $pmiss = $propseff[1];break;
			case "addspeed": $pspeed = $propseff[1];break;
			case "addhp": $php = $propseff[1];break;
			case "addmp": $pmp = $propseff[1];break;
		}
	}
	
	//转生属性计算公式：(初始属性*转生阶段+当前属性*等级/6000+当前属性*成长/9000)*（百分百+道具百分比)
	$hp = round(($newbb['hp']*$zsjd+$oldbb['hp'] * $oldbb['level']/6000 + $oldbb['hp'] * $oldbb['czl']/9000) * ($php+1));
	$mp = round(($newbb['mp']*$zsjd+$oldbb['mp'] * $oldbb['level']/6000 + $oldbb['mp'] * $oldbb['czl']/9000) * ($pmp+1));
	$mc = round(($newbb['mc']*$zsjd+$oldbb['mc'] * $oldbb['level']/6000 + $oldbb['mc'] * $oldbb['czl']/9000) * ($pmc+1));
	$ac = round(($newbb['ac']*$zsjd+$oldbb['ac'] * $oldbb['level']/6000 + $oldbb['ac'] * $oldbb['czl']/9000) * ($pac+1));
	$hits = round(($newbb['hits']*$zsjd+$oldbb['hits'] * $oldbb['level']/6000 + $oldbb['hits'] * $oldbb['czl']/9000) * ($phit+1));
	$miss = round(($newbb['miss']*$zsjd+$oldbb['miss'] * $oldbb['level']/6000 + $oldbb['miss'] * $oldbb['czl']/9000) * ($pmiss+1));
	$speed = round(($newbb['speed']*$zsjd+$oldbb['speed'] * $oldbb['level']/6000 + $oldbb['speed'] * $oldbb['czl']/9000) * ($pspeed+1));
	$czl = round($oldbb['czl']*$czlnum*0.01,1);
	//echo '$ac = round(('.$newbb['ac'].'*'.$zsjd.'+'.$oldbb['ac'] .'*'. $oldbb['level'].'/6000 + '.$oldbb['ac'].' * '.$oldbb['czl'].'/9000) * ('.$pac.'+1))';exit;
	/*echo "INSERT INTO userbb(
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
					   '{$newbb['name']}',
					   '{$_SESSION['id']}',
					   '{$_SESSION['nickname']}',
					   '1',
					   '{$newbb['wx']}',
					   '{$ac}',
					   '{$mc}',
					   '{$hp}',
					   '{$hp}',
					   '{$mp}',
					   '{$mp}',
					   '{$newbb['skillist']}',
					   unix_timestamp(),
					   '{$newbb['nowexp']}',
					   '100',
					   '{$newbb['imgstand']}',
					   '{$newbb['imgack']}',
					   '{$newbb['imgdie']}',
					   '{$hits}',
					   '{$miss}',
					   '{$speed}',
					   '{$newbb['kx']}',
					   '{$newbb['remakelevel']}',
					   '{$newbb['remakeid']}',
					   '{$newbb['remakepid']}',
					   '0',
					   '{$czl}',
					   't{$newbb['id']}.gif',
					   'k{$newbb['id']}.gif',
					   'q{$newbb['id']}.gif'
					   )
			  ";exit;*/
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
					   '{$newbb['name']}',
					   '{$_SESSION['id']}',
					   '{$_SESSION['nickname']}',
					   '1',
					   '{$newbb['wx']}',
					   '{$ac}',
					   '{$mc}',
					   '{$hp}',
					   '{$hp}',
					   '{$mp}',
					   '{$mp}',
					   '{$newbb['skillist']}',
					   unix_timestamp(),
					   '{$newbb['nowexp']}',
					   '100',
					   '{$newbb['imgstand']}',
					   '{$newbb['imgack']}',
					   '{$newbb['imgdie']}',
					   '{$hits}',
					   '{$miss}',
					   '{$speed}',
					   '{$newbb['kx']}',
					   '{$newbb['remakelevel']}',
					   '{$newbb['remakeid']}',
					   '{$newbb['remakepid']}',
					   '0',
					   '{$czl}',
					   't{$newbb['id']}.gif',
					   'k{$newbb['id']}.gif',
					   'q{$newbb['id']}.gif'
					   )
			  ");
	$jnall = split(",", $newbb['skillist']);
	foreach($jnall as $a => $b)
	{
		$arr = split(":", $b);
		
		$memskillsysid = unserialize($_pm['mem']->get('db_skillsysid'));
		$jn = $memskillsysid[$arr[0]];
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
		$newbb1 = $_pm['mysql']->getOneRecord("SELECT * 
									  FROM userbb
									 WHERE uid={$_SESSION['id']}
									 ORDER BY stime DESC
									 LIMIT 0,1			                                         
								  ");
		$bbid = $newbb1['id'];

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
		$sql = "UPDATE player
				SET mbid = {$bbid}
				WHERE id = {$_SESSION['id']}";
		$_pm['mysql'] -> query($sql);
	}
	return $newbb1;
}

function clearBB($bb)
{//return;
	global $_pm;
	$id = $bb['id'];
	
	
	
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
$_pm['mem']->memClose();
realseLock();
?>