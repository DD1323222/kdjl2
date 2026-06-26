<?php
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');
secStart($_pm['mem']);
$a = getLock($_SESSION['id']);
if(!is_array($a)){
	realseLock();
	die('服务器繁忙，请稍候再试！');
}

require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
$s=new socketmsg();
$team=new team($_SESSION['team_id'],&$s);


$teamInfo=$team->getTeamInfo();
if($_GET['id'])
{
	if(!isset($_SESSION['teamfb']))
	{
		$_SESSION['teamfb'] = array();
		$_SESSION['teamfb'][] = $_GET['id'];
	}
	else
	{
		foreach($_SESSION['teamfb'] as $info)
		{
			if($info == $_GET['id'])
			{
			
				unset($_SESSION['teamfb']);
				die("error");
			}
			else
			{
				$_SESSION['teamfb'][] = $_GET['id'];
			}
		}
	}
}
$ct=0;
foreach($teamInfo['members'] as $mem)
{
	if($mem['state']==1)
	{
		$ct++;
		$uidarr1[] = $mem['uid'];
	}
}

if($ct<2){
	realseLock();
	$team->setTeamState(array(
							'team_fuben_card_step_num'=>-1,
							'team_fuben_step'=>array(0,0),
							'fubensjoj' => 0
							));
	
	$rs=$s->sendMsg(iconv('utf-8','utf-8','SYSN|goTarot'),$uidarr1);//echo '['.__LINE__."]<br>";
	die('服务器繁忙，请稍候再试111！');
}

if($_GET['op'] == 'show'){//显示血量
	
	$teamInfo=$team->getTeamInfo();
	
	foreach($teamInfo['members'] as $mem){
		if($mem['state'] == 1){
			$marr = $_pm['mysql'] -> getOneRecord('SELECT player.nickname as nickname,hp,mp,srchp,srcmp,addhp,addmp,level,player.headimg FROM player,userbb WHERE player.id = '.$mem['uid'].' AND player.mbid = userbb.id');
			$isleader=$team->isTeamLeader($mem['uid'],$_SESSION['team_id']);
			if($isleader){//队长../images/bb/t13.gif
				$leader = '<div class="leader">	<!--队长-->
							<div class="name">'.$marr['nickname'].'</div>
							<div class="level">'.$marr['level'].'</div>
							<div class="avatar"><img src="../images/tarot/face'.$marr['headimg'].'.gif" /></div>
							<div class="red"><p style="width:'.(100*($marr['hp']+$marr['addhp'])/($marr['srchp']+$marr['addhp'])).'%"></p></div>	<!--血量，请修改p的width百分比值-->
							<div class="blue"><p style="width:'.(100*($marr['mp']+$marr['addmp'])/($marr['srcmp']+$marr['addmp'])).'%"></p></div>
						</div>';
			}else{//成员
				$member .= '<div class="team">	<!--队员-->
							<div class="name">'.$marr['nickname'].'</div>
							<div class="level">'.$marr['level'].'</div>
							<div class="avatar"><img src="../images/tarot/face'.$marr['headimg'].'.gif" /></div>
							<div class="red"><p style="width:'.(100*($marr['hp']+$marr['addhp'])/($marr['srchp']+$marr['addhp'])).'%"></p></div>
							<div class="blue"><p style="width:'.(100*($marr['mp']+$marr['addmp'])/($marr['srcmp']+$marr['addmp'])).'%"></p></div>
						</div>';
			}
			unset($marr);
		}
	}
	echo $leader.$member;
}
else if($_GET['op'] == 'o'){
	//显示其它玩家的牌和随机得没有翻的牌
	$flag = '1';
	$ar = unserialize($_pm['mem']->get('tarot_info1_'.$_SESSION['team_id']));
	if(is_array($ar)){
		foreach($ar as $v){
			if($v['type'] == 1){
				$ar1[] = $v;
			}else{
				$ar2[] = $v;
			}
			if($v['uid'] == $_SESSION['id']){
				continue;
			}
			
			$jsstr.='**|'.$v['type'].'~,~'.$v['msg'];
			$i++;
		}
	}
	if(count($ar2) < 1){
		$type2 = 5;
	}else{
		$type2 = 5 - count($ar2);
	}
	$type1 = 5 - count($ar1);
	if($type1 > 0){
		$sql = 'SELECT id,effect,sj,boss,img,name FROM tarot WHERE sj = 0 AND flag = 0 AND mapid = '.$_SESSION['team_inmap'];
		$row = $_pm['mysql'] -> getRecords($sql);
		$len = count($row) - 1;
		for($j = 0;$j < $type1;$j++){
			$res = $row[rand(0,$len)];
			$msg = '<span class="text">'.showt($res['effect']).'</span>';
			$jsstr.='**|1~,~'.$msg;
			$ar[]=array('type' => 1,'msg' => $msg,'uid' => 0);
		}
	}
	if($type2 > 0){
		$sql = 'SELECT id,effect,sj,boss,img,name FROM tarot WHERE sj != 0 AND flag = 0 AND mapid = '.$_SESSION['team_inmap'];
		$row = $_pm['mysql'] -> getRecords($sql);
		$len = count($row) - 1;
		for($j = 0;$j < $type2;$j++){
			$res = $row[rand(0,$len)];
			$msg = '<span class="text2">'.showt($res['effect']).'</span>';
			$jsstr.='**|2~,~'.$msg;
			$ar[]=array('type' => 2,'msg' => $msg,'uid' => 0);
		}
	}
	$_pm['mem']->set(array('k'=>'tarot_info1_'.$_SESSION['team_id'],'v'=>$ar));
	echo $jsstr;
}else{
	
	$srctime = 3;
	#################增加一个间隔时间################
	$time = $_SESSION['time'.$_SESSION['id']];
	if(empty($time)){	
		$_SESSION['time'.$_SESSION['id']] = time();
	}else{
		$nowtime = time();
		$ctime = $nowtime - $time;
		if($ctime < $srctime){
			realseLock();
		//	die("11");//没有达到间隔时间
		}
		else{
			$_SESSION['time'.$_SESSION['id']] = time();
		}
	}

	$id = intval($_GET['id']);
	if($id < 1 || $id > 10){
		realseLock();
		die('1');//无效的请求
	}
	if($id <= 5){
		$point1 = $team -> get_team_funben_card_step();
	}else{
		$point2 = $team -> get_team_funben_card_step($_SESSION['id'],'_sj');
	}

	//判断是否是第三关;
	if($point1 == 3 || $point2 == 3){
	$point = 3;
	}else{
		$point = $id <= 5?$point1:$point2;
	}
	if($point == 3)	//翻牌记录
	{
		if(is_array($_SESSION['point3_card_record']))
		{
			if(in_array($_GET['id'],$_SESSION['point3_card_record']))
			{
				$_pm['mem']->del('tarot_info_'.$_SESSION['team_id']);
				$_pm['mem'] -> del('tarot_times_'.$_SESSION['team_id']);
				$res_team = $_pm['mysql'] -> getOneRecord("SELECT * FROM team WHERE creator = '{$_SESSION['id']}'");
				
				$_pm['mysql'] ->query("DELETE FROM team_members WHERE team_id = '{$res_team['id']}'");
				$_pm['mysql'] ->query("DELETE FROM team WHERE creator = '{$_SESSION['id']}'");
				unset($_SESSION['teamfb']);
				die("error");
			}
		}
		$_SESSION['point3_card_record'][] = $_GET['id'];
	}
	$teamState = $team ->getTeamState();
	
	//$point = 1;
	if($point == 0){
		realseLock();
		die('2');//已经翻过
	}
	if($teamState['team_fuben_card_step_num'] == 3){
		$isleader=$team->isTeamLeader($_SESSION['id'],$_SESSION['team_id']);
		if(!$isleader){
			realseLock();
			die('3');//不是队长，不能翻牌
		}
	}
	if($id <= 5 && $point != 3){
		$sql = 'SELECT id,effect,sj,boss,img,name FROM tarot WHERE sj = 0 AND flag = 0 AND mapid = '.$_SESSION['team_inmap'];
		if($_SESSION['id'] == 1053107){
			$sql = 'SELECT id,effect,sj,boss,img,name FROM tarot WHERE id=390';
		}
		
	}else if($id >= 5 && $point != 3){
		$sql = 'SELECT id,effect,sj,boss,img,name FROM tarot WHERE sj != 0 AND flag = 0 AND mapid = '.$_SESSION['team_inmap'];
	}else if($point == 3){
		$tarot_times = unserialize($_pm['mem']->get('tarot_times_'.$_SESSION['team_id']));
		//echo $tarot_times.'<br />';
		$tarot_times1 = $tarot_times + 1;
		$_pm['mem']->set(array('k'=>'tarot_times_'.$_SESSION['team_id'],'v'=>$tarot_times1));
		if($tarot_times > 7){
			$_pm['mem']->del('tarot_info_'.$_SESSION['team_id']);
			$_pm['mem'] -> del('tarot_times_'.$_SESSION['team_id']);
			$team -> set_team_funben_card_prize_got();
			realseLock();
			die('4');//无效的请求
		}else if($tarot_times == 7){
			$_pm['mem']->del('tarot_info_'.$_SESSION['team_id']);
			$_pm['mem'] -> del('tarot_times_'.$_SESSION['team_id']);
			$team -> set_team_funben_card_prize_got();
			$sql = 'SELECT id,effect,sj,boss,img,name FROM tarot WHERE flag = 1 AND boss != "0" AND boss != "" AND mapid = '.$_SESSION['team_inmap'];
		}else{
			$sql = 'SELECT id,effect,sj,boss,img,name FROM tarot WHERE flag = 1 AND mapid = '.$_SESSION['team_inmap'];
			//$sql = 'SELECT id,effect,sj,boss,img,name FROM tarot WHERE flag = 1 and id = 420';
		}
	}//echo $sql.'<br />';
	$tarot = $_pm['mysql'] -> getRecords($sql);

	if(!is_array($tarot)){
		realseLock();
		die('5');//无效的请求
	}
	$max = count($tarot) - 1;
	$rand = rand(0,$max);
	$newTarot = $tarot[$rand];
	
	if($newTarot > 0 && $newTarot['sj'] > 0){
		$_pm['mysql'] -> query('UPDATE player_ext SET sj = sj-'.$newTarot['sj'].' WHERE uid = '.$_SESSION['id'].' AND sj >= '.$newTarot['sj']);
		if(mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){
			realseLock();
			die('6');//没有足够的水晶
		}
	}
	
	if($newTarot['boss'] != 0 || ($point < 3 && $id <= 5)){//当第三关为boss或者第一二关翻免费牌的时候，设为领取奖励的状态
		$team -> set_team_funben_card_prize_got();
		$_pm['mem']->del('tarot_info_'.$_SESSION['team_id']);
		$_pm['mem'] -> del('tarot_times_'.$_SESSION['team_id']);
		if($newTarot['boss'] != 0){
			//写怪物数据
			$team -> setTeamMonsters($newTarot['boss']);
		}
	}
	
	if($point < 3 && $id > 5){
		$team -> set_team_funben_card_prize_got($_SESSION['id'],'_sj');
	}
	
	
	//效果 填写格式：all_hp_add:10%|all_money_add:100|all_giveitems:747:1:3:1,738:1:10:1,739:1:10:1|all_fight:1|fight_one:0|fight_all:0|all_money_less:100|all_hp_less:10%|all_exp_add:100|money_add:100|exp_add:100|giveitems:1225:15,1308:10,734:1,1142:3|fight_one:0
	$effect = explode('|',$newTarot['effect']);
	$_SESSION['carddddd'].='|card id='.$newTarot['id'].'|';
	if($newTarot['boss'] != 0){
		if( $_SESSION['gs'] == 3 || !empty($_SESSION['gs']) )
		{
			$_SESSION['gs'] = "";
			unset($_SESSION['gs']);
		}
		//是boss
		$_pm['mem']->del('tarot_info_'.$_SESSION['team_id']);
		$team -> setTeamMonsters($newTarot['boss']);
		$teamInfo=$team->getTeamInfo();
		foreach($teamInfo['members'] as $mem){
			if($mem['state'] == 1){
				$uidarr[] = $mem['uid'];
			}
		}
		$team->setTeamState(
							array(
								'team_fuben_boss'=>1								)
							);
		echo 'boss===>';
		
		$rs=$s->sendMsg(iconv('utf-8','utf-8','SYSN|getTeamFightMod'),$uidarr);
		$rs=$s->sendMsg(iconv('utf-8','utf-8','SYSN|alertTarot->遇到boss了！！！'),$uidarr);
	}else{
		foreach($effect as $v){
			$arr = explode(':',$v);
			switch ($arr[0]){
				case 'all_hp_less'://全体HP减少
					$teamInfo=$team->getTeamInfo();
					if(strpos($arr[1],'%') === false){//不是百分比
						foreach($teamInfo['members'] as $mem){
							if($mem['state'] == 1){
								$mbid = getMbid($mem['uid']);
								$hp = getMaxHp($mbid);
								if($hp['totalhp'] <= $arr[1]){
									$_pm['mysql'] -> query('UPDATE userbb SET hp = 0,addhp = 0 WHERE id = '.$mbid);
								}else{
									$h = intval($arr[1] - $hp['hp']);
									if($h < 0){
										$h = $arr[1];
										$ah = 0;
									}else{
										$h = $hp['hp'];
										$ah = $h;
									}
									$_pm['mysql'] -> query('UPDATE userbb SET hp = hp - '.$h.',addhp = addhp - '.$ah.' WHERE id = '.$mbid.' AND hp >= '.$h.' AND addhp >= '.$addhp);
								}
								$uidarr[] = $mem['uid'];
							}
						}
					}else{//是百分比
						$num = str_replace('%','',$arr[1]) * 0.01;
						foreach($teamInfo['members'] as $mem){
							if($mem['state'] == 1){
								$mbid = getMbid($mem['uid']);
								$_pm['mysql'] -> query('UPDATE userbb SET hp = hp - srchp * '.$num.',addhp = addhp - addhp * '.$num.' WHERE id ='.$mbid);
								$uidarr[] = $mem['uid'];
							}
						}
					}
					
					
					$rs=$s->sendMsg(iconv('utf-8','utf-8','SYSN|changhp'),$uidarr);
					$rs=$s->sendMsg(iconv('utf-8','utf-8','SYSN|alertTarot->全体减少'.$arr[1].'的血量！'),$uidarr);
					echo 'hp===>';
					break;
				case 'all_money'://全体获得同等金币奖励或惩罚，惩罚填负
					$teamInfo=$team->getTeamInfo();
					foreach($teamInfo['members'] as $mem){
						if($mem['state'] == 1){
							moneyAdd($mem['uid'],$arr[1]);
							$uidarr[] = $mem['uid'];
						}
					}
					if($arr[1] < 0){
						$rs=$s->sendMsg(iconv('utf-8','utf-8','SYSN|alertTarot->全体扣除'.abs($arr[1]).'金币！'),$uidarr);
					}else{
						$rs=$s->sendMsg(iconv('utf-8','utf-8','SYSN|alertTarot->全体获得'.$arr[1].'金币！'),$uidarr);
					}
					echo 'money===>';
					break;
				case 'all_giveitems'://全体获得相同道具奖励
					$teamInfo=$team->getTeamInfo();
					$uidarr=array();
					foreach($teamInfo['members'] as $mem){
						if($mem['state'] == 1){
							$uidarr[] = $mem['uid'];
							
						}
					}
					
					echo 'items===>';
					$itemstr = str_replace('all_giveitems:', '', $v);
					$it = getItem($uidarr,$itemstr);
					if($it == '真遗憾，您没有获得任何道具！'){
						$nit = '真遗憾，您没有获得任何道具！';
					}else $nit = '全体'.$it;
					$rs=$s->sendMsg(iconv('utf-8','utf-8','SYSN|alertTarot->'.$nit),$uidarr);
					break;
				case 'all_fight'://触发战斗  读取数据
					if( $_SESSION['gs'] == 3 || !empty($_SESSION['gs']) )
					{
						$_SESSION['gs'] = "";
						unset($_SESSION['gs']);
					}
					$team -> setTeamMonsters($arr[1]);
					echo 'fight===>';
					$teamInfo=$team->getTeamInfo();
					foreach($teamInfo['members'] as $mem){
						if($mem['state'] == 1){
							$uidarr[] = $mem['uid'];
						}
					}
					$team->setTeamState(
							array(
								'team_fuben_boss'=>0								)
							);
					$rs=$s->sendMsg(iconv('utf-8','utf-8','SYSN|alertTarot->触发战斗！！！！！！！'),$uidarr);
					$rs=$s->sendMsg(iconv('utf-8','utf-8','SYSN|getTeamFightMod'),$uidarr);
					break;
				case 'hit_one'://随机一人被踢出副本
					
					$teamInfo=$team->getTeamInfo();
					foreach($teamInfo['members'] as $mem){
						if($mem['state'] == 1){
							$isleader=$team->isTeamLeader($mem['uid'],$_SESSION['team_id']);
							if($isleader){//队长不能踢出
								continue;
							}
							$memarr[] = $mem['uid'];
						}
					}
					$len = count($memarr) - 1;
					$i = rand(0,$len);
					
					foreach($uidarr1 as $v){
						if($v == $memarr[$i]){
							continue;
						}
						$nuid[] = $v;
					}
					
					$hit = $_pm['mysql'] -> getOneRecord('SELECT id,nickname FROM player WHERE id = '.$memarr[$i]);
					if(count($nuid) > 1){
						$rs=$s->sendMsg(iconv('utf-8','utf-8','SYSN|alertTarot->您的队友'.$hit['nickname'].'被踢出战斗！'),$nuid);
					}else{//只有队长一个人的时候
						$rs=$s->sendMsg(iconv('utf-8','utf-8','SYSN|outTarot->由于副本人数不足，强制离开副本，挑战失败。'),$nuid);
						$team->setTeamState(array(
							'team_fuben_card_step_num'=>-1,
							'team_fuben_step'=>array(0,0),
							'fubensjoj' => 0
							));
					}					
					$rs=$s->sendMsg(iconv('utf-8','utf-8','SYSN|outTarot->运气太差，遇上恶魔，你将被强制踢出副本，请下次再来吧，挑战副本失败。'),$memarr[$i]);
					echo 'hit_one->'.$hit['nickname'].'===>';
					$team -> kickMember($memarr[$i],true);					
					break;
				case 'hit_all'://全体被踢出副本
					$rs=$s->sendMsg(iconv('utf-8','utf-8','SYSN|goTarot->运气太差，遇上恶魔，你们将被强制t出副本，请明日再来吧，挑战副本失败!'),$uidarr1);
					echo 'hit_all===>';
					$team -> disbandTeam(false);
					break;
				case 'all_exp_add'://全体获得经验奖励
					$teamInfo=$team->getTeamInfo();
					$t = new task();
					foreach($teamInfo['members'] as $mem){
						if($mem['state'] == 1){
							$t->saveExps($arr[1],$mem['uid']);
							$uidarr[] = $mem['uid'];
						}
					}
					$rs=$s->sendMsg(iconv('utf-8','utf-8','SYSN|alertTarot->全体获得'.$arr[1].'点经验！'),$uidarr);
					echo 'exp_all===>';
					break;
				case 'all_hp_add'://全体HP增加
					$teamInfo=$team->getTeamInfo();
					foreach($teamInfo['members'] as $mem){
						if($mem['state'] == 1){
							$mbid = getMbid($mem['uid']);
							$zbarr = getzbAttrib($mbid);
							$hp = getMaxHp($mbid)>0?getMaxHp($mbid):0;
							$chp = ($hp['scrhp'] - $hp['hp'])>0?($hp['scrhp'] - $hp['hp']):0;
							$addchp = ($arr['hp'] - $hp['addhp'])>0?($arr['hp'] - $hp['addhp']):0;
							$cchp = ($chp+$addchp)>0?($chp+$addchp):0;
							if(!is_array($zbarr) ||empty($zbarr['hp'])){
								$zbarr['hp'] = 0;
							}
							if(strpos($arr[1],'%') === false){//不是百分比
								if($arr[1] > $cchp){//回满
									$_pm['mysql'] -> query('UPDATE userbb SET hp = srchp,addhp = '.$zbarr['hp'].' WHERE id = '.$mbid);
									/*echo 'UPDATE userbb SET hp = srchp,addhp = '.$zbarr['hp'].' WHERE id = '.$mbid;
									echo '<br />'.__line__.'<br />';*/
								}else{
									if($addchp > $arr[1]){
										$_pm['mysql'] -> query('UPDATE userbb SET addhp = addhp + '.$arr[1].' WHERE id = '.$mbid);
										/*echo 'UPDATE userbb SET hp = srchp,addhp = '.$zbarr['hp'].' WHERE id = '.$mbid;
										echo '<br />'.__line__.'<br />';*/
									}else{
										$a = intval($arr[1] - $addchp);
										$_pm['mysql'] -> query('UPDATE userbb SET addhp = '.$zbarr['hp'].',hp = hp+'.$a.' WHERE id = '.$mbid);
										/*echo 'UPDATE userbb SET hp = srchp,addhp = '.$zbarr['hp'].' WHERE id = '.$mbid;
										echo '<br />'.__line__.'<br />';*/
									}
								}
							}else{
								$num = str_replace('%','',$arr[1]) * 0.01;
								$hhp = round($hp['srchp'] * $num) + $hp['hp'];
								$haddhp = round($zbarr['hp'] * $num) + $hp['addhp'];
								if($hhp > $hp['srchp']){
									$hhp = $hp['srchp'];
								}
								if($haddhp > $zbarr['hp']){
									$haddhp = $zbarr['hp'];
								}
								$_pm['mysql'] -> query('UPDATE userbb SET addhp = '.$haddhp.',hp='.$hhp.' WHERE id = '.$mbid);
							}
							$uidarr[] = $mem['uid'];
						}
					}
					
					$rs=$s->sendMsg(iconv('utf-8','utf-8','SYSN|changhp'),$uidarr);
					$rs=$s->sendMsg(iconv('utf-8','utf-8','SYSN|alertTarot->全体增加'.$arr[1].'点血量！'),$uidarr);
					echo 'hp_all===>';
					break;
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
					$ret = getItem(array($_SESSION['id']),$itemstr);
					break;
				case 'hit_me'://单人被踢出副本
					$isleader=$team->isTeamLeader($_SESSION['id'],$_SESSION['team_id']);
					if($isleader){
						$team -> disbandTeam(false);
						$rs=$s->sendMsg(iconv('utf-8','utf-8','SYSN|goTarot->运气太差，遇上恶魔，你们将被强制踢出副本，请下次再来吧，挑战副本失败！'),$uidarr1);
					}else{
						$team -> kickMember($_SESSION['id'],true);
						$ret = $_SESSION['nickname'].'被踢出战斗！';
						$rs=$s->sendMsg(iconv('utf-8','utf-8','SYSN|goTarot->运气太差，遇上恶魔，你将被强制踢出副本，请下次再来吧，挑战副本失败。'),$_SESSION['id']);
					}
					$teamInfo=$team->getTeamInfo();
					foreach($teamInfo['members'] as $mem){
						if($mem['state'] == 1){
							$uidarr[] = $mem['uid'];
						}
					}
					if(count($uidarr) == 1){
						$rs=$s->sendMsg(iconv('utf-8','utf-8','SYSN|goTarot->由于副本人数不足，将强制离开副本，挑战失败。'),$uidarr);
						$team->setTeamState(array(
							'team_fuben_card_step_num'=>-1,
							'team_fuben_step'=>array(0,0),
							'fubensjoj' => 0
							));
					}
					break;
				default:
					echo '牌：'.$newTarot['id'].'填写有误，'.$newTarot['effect'].'数据有误！';
					break;
			}
		}
	}
	if($point < 3){
		$ar = unserialize($_pm['mem']->get('tarot_info1_'.$_SESSION['team_id']));
		if($id <= 5){
			$e = '<span class="text">'.$_SESSION['nickname'].'<br />'.$ret.'</span>';
			$type = 1;
		}
		else{
			$e = '<span class="text2">'.$_SESSION['nickname'].'<br />'.$ret.'</span>';
			$type = 2;
		}
		echo $e;
		$ar[]=array('type' => $type,'msg' => $e,'uid' => $_SESSION['id']);
		$_pm['mem']->set(array('k'=>'tarot_info1_'.$_SESSION['team_id'],'v'=>$ar));
	}else{
		//第三关要把所翻的牌存起来
		$ar = unserialize($_pm['mem']->get('tarot_info_'.$_SESSION['team_id']));
		$ar[]=array('id' => $id,'img' => $newTarot['img']);
		echo '<pre>
line='.__LINE__.'
';
var_dump($newTarot);
echo '</pre>
';
		$_pm['mem']->set(array('k'=>'tarot_info_'.$_SESSION['team_id'],'v'=>$ar));
		$rs=$s->sendMsg(iconv('utf-8','utf-8','SYSN|tarot->'.$id.'->'.$newTarot['img']),$uidarr1);
		//echo $rs.'aaa';
		//echo '['.__LINE__."]<br>";
		echo $newTarot['img'];
	}
}
realseLock();
function getMbid($uid){//得到主宠id
	global $_pm;
	$arr = $_pm['mysql'] -> getOneRecord('SELECT mbid FROM player WHERE id = '.$uid);
	return $arr['mbid'];
}

function getMaxHp($id){//得到剩余hp
	global $_pm;
	$arr = $_pm['mysql'] -> getOneRecord('SELECT srchp,hp,addhp,(hp+addhp) as totalhp FROM userbb WHERE id = '.$id);
	return $arr;
}

function moneyAdd($uid,$num){
	global $_pm;
	if($num < 0){
		$_pm['mysql'] -> query('UPDATE player SET money = money +'.$num.' WHERE id = '.$uid.' AND money >= '.$num);
		if(mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){
			$_pm['mysql'] -> query('UPDATE player SET money = 0 WHERE id = '.$uid);
		}
	}else{
		$_pm['mysql'] -> query('UPDATE player SET money = money +'.$num.' WHERE id = '.$uid);
	}
}

function getItem($uidarr,$str){
	global $_pm,$s,$point;
	//echo $str;
	$flag = 0;
	$propslist = explode(',', $str);
	if (is_array($propslist)){
		$task = new task();
		foreach ($propslist as $k => $v){
			$inarr = explode(':', $v);
			if(is_array($inarr)){
				if (rand(1, intval($inarr[2])) == 1){	//  rand hits
					$flag = 1;
					if($uidarr == 0){
						$prs = $_pm['mysql']->getOneRecord("SELECT name FROM props WHERE id={$inarr[0]}");
						if(empty($retstr)){
							$retstr = '获得 '.$prs['name'].'&nbsp;'.$inarr[1].' 个';
						}else{
							$retstr .= ",".$prs['name'].'&nbsp;'.$inarr[1].' 个';
						}
					}else{
						foreach($uidarr as $v){
							$task->saveGetPropsMore($inarr[0],$inarr[1],0,$v);
							$prs = $_pm['mysql']->getOneRecord("SELECT name FROM props WHERE id={$inarr[0]}");
							if(empty($retstr)){
								$retstr = '获得 '.$prs['name'].'&nbsp;'.$inarr[1].' 个';
							}else{
								$retstr .= ",".$prs['name'].'&nbsp;'.$inarr[1].' 个';
							}
							if($inarr[3] == '2'){//发公告
								$p = $_pm['mysql'] -> getOneRecord('SELECT nickname FROM player WHERE id = '.$v);
								$msg=iconv('utf-8','utf-8','恭喜玩家 '.$p['nickname'].'获得遗忘宫殿第'.$point.'关的奖励：'.$prs['name'].'&nbsp;'.$inarr[1].' 个');
								$s->sendMsg('an|'.$msg,'__ALL__');
							}
						}
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

function showt($str){
	$effect = explode('|',$str);
	foreach($effect as $v){
		$arr = explode(':',$v);
		switch ($arr[0]){
			case 'money_add'://单人获得金币奖励
				if($arr[1] < 0){
					$ret='扣除金币：'.$arr[1];
				}else{
					$ret='获得金币：'.$arr[1];
				}
				break;
			case 'exp_add'://单人获得经验奖励
				$ret='获得经验：'.$arr[1];
				break;
			case 'giveitems'://单人获得道具奖励
				$itemstr = str_replace('giveitems:', '', $v);
				$ret = getItem(0,$itemstr);
				break;
			case 'hit_me'://单人被踢出副本
				$ret = '随机一人踢出副本！';
				break;
			default:
				$ret = '牌：'.$newTarot['id'].'填写有误，'.$newTarot['effect'].'数据有误！';
				break;
		}
	}
	return $ret;
}
$_pm['mem']->memClose();
?>