<?php
require_once('../config/config.game.php');
secStart($_pm['mem']);
$uid = intval($_SESSION['id']);
$type = isset($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;
$bid = isset($_REQUEST['bid']) ? intval($_REQUEST['bid']) : 0;

function ccFail($message, $rollback=false)
{
	global $_pm;
	if ($rollback) $_pm['mysql']->query('ROLLBACK');
	die($message);
}

function ccBegin($userIds)
{
	global $_pm;
	$ids = array();
	foreach ($userIds as $userId)
	{
		$userId = intval($userId);
		if ($userId > 0) $ids[$userId] = $userId;
	}
	if (empty($ids)) return false;
	sort($ids, SORT_NUMERIC);
	$values = array();
	foreach ($ids as $userId) $values[] = '('.$userId.',0)';
	$_pm['mysql']->query('INSERT IGNORE INTO `lock` (uid,lockvalue) VALUES '.implode(',', $values));
	if (!$_pm['mysql']->query('START TRANSACTION')) return false;
	$rows = $_pm['mysql']->getRecords(
		'SELECT uid FROM `lock` WHERE uid IN ('.implode(',', $ids).') ORDER BY uid FOR UPDATE'
	);
	return is_array($rows) && count($rows) == count($ids);
}

function ccCommit()
{
	global $_pm;
	if (!$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		die('操作提交失败，请重试！');
	}
}

function ccNotify($targetUid, $content)
{
	global $_pm;
	$targetUid = intval($targetUid);
	if ($targetUid < 1) return false;
	return $_pm['mysql']->query(
		'INSERT INTO information (uid,times,content) VALUES ('.$targetUid.','.
		$_pm['mysql']->quote(date('Y-m-d H:i:s')).','.$_pm['mysql']->quote($content).')'
	);
}

function ccNickname($uid)
{
	global $_pm;
	$row = $_pm['mysql']->getOneRecord('SELECT nickname FROM player WHERE id='.intval($uid));
	return is_array($row) ? $row['nickname'] : '';
}

function ccGrowthHistory($history, $growth)
{
	$parts = explode(',', strval($history));
	$count = isset($parts[0]) ? intval($parts[0]) : 0;
	$baseGrowth = isset($parts[1]) ? floatval($parts[1]) : 0;
	if ($count >= 2)
	{
		return array(1, $growth);
	}
	if ($count < 1)
	{
		return array(1, $growth);
	}
	return array($count + 1, $baseGrowth > 0 ? $baseGrowth : $growth);
}

function ccMaterial($uid, $bagId, $effectName)
{
	global $_pm;
	$bagId = intval($bagId);
	if ($bagId < 1) return false;
	$row = $_pm['mysql']->getOneRecord(
		'SELECT b.id,b.pid,b.sums,b.vary,p.effect FROM userbag AS b '.
		'INNER JOIN props AS p ON p.id=b.pid WHERE b.uid='.intval($uid).' AND b.id='.$bagId.
		' AND b.sums>0 AND p.varyname=20 FOR UPDATE'
	);
	if (!is_array($row)) return false;
	$effect = explode(':', $row['effect']);
	if (!isset($effect[0]) || $effect[0] !== $effectName) return false;
	return $row;
}

function ccConsumeMaterial($uid, $item)
{
	global $_pm;
	if (!is_array($item)) return false;
	if (intval($item['vary']) == 2)
	{
		$sql = 'DELETE FROM userbag WHERE uid='.intval($uid).' AND id='.intval($item['id']).' AND sums>0';
	}
	else
	{
		$sql = 'UPDATE userbag SET sums=sums-1 WHERE uid='.intval($uid).' AND id='.intval($item['id']).' AND sums>=1';
	}
	return $_pm['mysql']->query($sql) && mysql_affected_rows($_pm['mysql']->getConn()) == 1;
}

function ccPetListHtml($rows)
{
	if (!is_array($rows)) return '';
	$html = '';
	foreach ($rows as $pet)
	{
		$id = intval($pet['id']);
		$name = strval($pet['name']);
		$nameHtml = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
		$selectAction = 'sel(this);copyWord('.json_encode($name).');bid='.$id.';';
		$addAction = 'sel(this);jinchuanc('.$id.');';
		$color = isset($pet['chchengcolor']) ? $pet['chchengcolor'] : '';
		if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#000000';
		$html .= '<table style="font-size:12px"><tr>'.
			'<td width="100px" style="cursor:pointer;text-align:center;" '.
			'onmouseover="pos=1;mcbbshow('.$id.');this.style.border=\'solid 1px #DFD496\';" '.
			'onmouseout="mcbbdisplay();this.style.border=0;" onclick="'.
			htmlspecialchars($selectAction, ENT_QUOTES, 'UTF-8').'">'.
			'<font color="'.$color.'">'.$nameHtml.'</font></td>'.
			'<td style="text-align:center;" width="60px">'.intval($pet['level']).'</td>'.
			'<td style="text-align:center;" width="60px">'.htmlspecialchars($pet['czl'], ENT_QUOTES, 'UTF-8').'</td>'.
			'<td width="80px" style="cursor:pointer;text-align:center;" '.
			'onmouseover="this.style.border=\'solid 1px #DFD496\';" onmouseout="this.style.border=0;" '.
			'onclick="'.htmlspecialchars($addAction, ENT_QUOTES, 'UTF-8').'">'.
			'<img src="../new_images/ui/add05.gif" border="0" /></td></tr></table>';
	}
	return $html;
}

function ccInheritedStat($base, $ownValue, $ownLevel, $otherValue, $otherLevel, $multiplier)
{
	$value = (floatval($base) +
		(floor(floatval($ownValue) * intval($ownLevel) / 400) +
		 floor(floatval($otherValue) * intval($otherLevel) / 800))) * floatval($multiplier);
	if ($value < 0) $value = 0;
	return sprintf('%.0f', floor($value));
}

function ccNumberDifference($left, $right)
{
	return sprintf('%.0f', floatval($left) - floatval($right));
}

function ccSkillMap($skillList)
{
	$skills = array();
	foreach (explode(',', strval($skillList)) as $entry)
	{
		$parts = explode(':', trim($entry));
		$sid = isset($parts[0]) ? intval($parts[0]) : 0;
		$level = isset($parts[1]) ? intval($parts[1]) : 1;
		if ($sid < 1) continue;
		if ($level < 1) $level = 1;
		if ($level > 10) $level = 10;
		$skills[$sid] = $level;
	}
	return $skills;
}

function ccSkillValueAtLevel($values, $level)
{
	$parts = explode(',', strval($values));
	$index = intval($level) - 1;
	if ($index < 0) $index = 0;
	if (isset($parts[$index])) return $parts[$index];
	return isset($parts[0]) ? $parts[0] : '';
}

function ccCreateSkillDetail($petId, $sid, $level)
{
	global $_pm;
	$config = $_pm['mysql']->getOneRecord('SELECT * FROM skillsys WHERE id='.intval($sid));
	if (!is_array($config)) return false;
	$sql = 'INSERT INTO skill (bid,sid,name,level,vary,wx,value,plus,img,uhp,ump) VALUES ('.
		intval($petId).','.intval($sid).','.$_pm['mysql']->quote($config['name']).','.intval($level).','.
		$_pm['mysql']->quote($config['vary']).','.intval($config['wx']).','.
		$_pm['mysql']->quote(ccSkillValueAtLevel($config['ackvalue'], $level)).','.
		$_pm['mysql']->quote(ccSkillValueAtLevel($config['plus'], $level)).','.
		$_pm['mysql']->quote(ccSkillValueAtLevel($config['imgeft'], $level)).','.
		intval(ccSkillValueAtLevel($config['uhp'], $level)).','.
		intval(ccSkillValueAtLevel($config['ump'], $level)).')';
	return $_pm['mysql']->query($sql) && mysql_affected_rows($_pm['mysql']->getConn()) == 1;
}

function ccSyncSkillDetails($petId, $skillList)
{
	global $_pm;
	$skills = ccSkillMap($skillList);
	if (empty($skills)) return false;
	$rows = $_pm['mysql']->getRecords(
		'SELECT id,sid FROM skill WHERE bid='.intval($petId).' ORDER BY id FOR UPDATE'
	);
	if (!is_array($rows)) $rows = array();
	$kept = array();
	$deleteIds = array();
	foreach ($rows as $row)
	{
		$sid = intval($row['sid']);
		if (!isset($skills[$sid]) || isset($kept[$sid]))
		{
			$deleteIds[] = intval($row['id']);
		}
		else
		{
			$kept[$sid] = intval($row['id']);
		}
	}
	if (!empty($deleteIds) && !$_pm['mysql']->query(
		'DELETE FROM skill WHERE bid='.intval($petId).' AND id IN ('.implode(',', $deleteIds).')'
	)) return false;
	foreach ($skills as $sid => $level)
	{
		if (!isset($kept[$sid]) && !ccCreateSkillDetail($petId, $sid, $level)) return false;
	}
	return true;
}


if($type==1){  //加入
	del_bag_expire();
	if($bid < 1) ccFail('1');
	if($bid==103 || $bid==104 || $bid==105) ccFail('77');

	$selection = $_pm['mysql']->getOneRecord('SELECT chchengbb FROM player_ext WHERE uid='.$uid);
	$selectedPetId = is_array($selection) ? intval($selection['chchengbb']) : 0;
	$selectedPreview = $selectedPetId > 0 ? $_pm['mysql']->getOneRecord(
		'SELECT uid FROM userbb WHERE id='.$selectedPetId
	) : false;
	$selectedUid = is_array($selectedPreview) ? intval($selectedPreview['uid']) : 0;
	if (!ccBegin(array($uid, $selectedUid))) ccFail('3', true);

	$playerExt = $_pm['mysql']->getOneRecord(
		'SELECT chchengbb,chouqu_chongwu FROM player_ext WHERE uid='.$uid.' FOR UPDATE'
	);
	$petsAll = $_pm['mysql']->getRecords('SELECT * FROM userbb WHERE uid='.$uid.' FOR UPDATE');
	if (!is_array($playerExt) || !is_array($petsAll)) ccFail('3', true);

	$pet = false;
	foreach($petsAll as $ownedPet){
		$state = intval($ownedPet['muchang']);
		if($state >= 3 && $state <= 7){
			$codes = array(3=>'4', 4=>'5', 5=>'6', 6=>'7', 7=>'8');
			ccFail($codes[$state], true);
		}
		if(intval($ownedPet['id']) == $bid) $pet = $ownedPet;
	}
	if(!is_array($pet) || intval($pet['muchang']) != 1 || intval($pet['tgflag']) != 0) ccFail('3', true);
	if(strpos($playerExt['chouqu_chongwu'], ','.$bid.',') !== false){
		ccFail('宠物抽取过成长,不能进行传承!', true);
	}
	if(intval($pet['wx']) != 6) ccFail('10', true);
	if(intval($pet['level']) < 90) ccFail('11', true);
	if(floatval($pet['czl']) < 60) ccFail('14', true);

	$equippedSpecial = $_pm['mysql']->getOneRecord(
		'SELECT b.id FROM userbag AS b INNER JOIN props AS p ON p.id=b.pid '.
		'WHERE b.uid='.$uid.' AND b.zbpets='.$bid.' AND b.sums>0 AND p.varyname=9 LIMIT 1 FOR UPDATE'
	);
	if(is_array($equippedSpecial)) ccFail('250', true);

	$history = explode(',', strval($pet['chchengcz']));
	$historyCount = isset($history[0]) ? intval($history[0]) : 0;
	$historyGrowth = isset($history[1]) ? floatval($history[1]) : 0;
	if($historyCount >= 2 && floatval($pet['czl']) - $historyGrowth < 10) ccFail('12', true);
	if(intval($pet['chchengtime']) + 86400 > time()) ccFail('13', true);

	$selectedPetId = intval($playerExt['chchengbb']);
	if($selectedPetId < 1){
		$sql = 'UPDATE userbb SET muchang=3,chchengbb=0 WHERE uid='.$uid.' AND id='.$bid.
			' AND muchang=1 AND tgflag=0';
		if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) ccFail('3', true);
		ccCommit();
		die('2');
	}

	$otherPet = $_pm['mysql']->getOneRecord('SELECT * FROM userbb WHERE id='.$selectedPetId.' FOR UPDATE');
	$otherValid = is_array($otherPet) && intval($otherPet['uid']) > 0 && intval($otherPet['uid']) != $uid &&
		intval($otherPet['uid']) == $selectedUid && intval($otherPet['muchang']) == 3 &&
		intval($otherPet['chchengbb']) == 0 && intval($otherPet['tgflag']) == 0;
	if(!$otherValid){
		$sql = 'UPDATE userbb SET muchang=3,chchengbb=0 WHERE uid='.$uid.' AND id='.$bid.' AND muchang=1';
		if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) ccFail('3', true);
		$_pm['mysql']->query('UPDATE player_ext SET chchengbb=0 WHERE uid='.$uid);
		$staleCode = is_array($otherPet) && intval($otherPet['chchengbb']) > 0 ? '79' : '78';
		ccCommit();
		die($staleCode);
	}

	$sql = 'UPDATE userbb SET muchang=4,chchengbb='.$selectedPetId.' WHERE uid='.$uid.
		' AND id='.$bid.' AND muchang=1 AND tgflag=0';
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) ccFail('3', true);
	$sql = 'UPDATE userbb SET muchang=4,chchengbb='.$bid.' WHERE uid='.intval($otherPet['uid']).
		' AND id='.$selectedPetId.' AND muchang=3 AND COALESCE(chchengbb,0)=0 AND tgflag=0';
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) ccFail('3', true);
	$otherUid = intval($otherPet['uid']);
	$nickname = ccNickname($uid);
	ccCommit();
	ccNotify($otherUid, '玩家【'.$nickname.'】加入了你的传承宠物');
	die('15');
}elseif($type==2){//取消
	if($bid < 1) ccFail('1');
	$preview = $_pm['mysql']->getOneRecord('SELECT uid,chchengbb FROM userbb WHERE uid='.$uid.' AND id='.$bid);
	if(!is_array($preview)) ccFail('1');
	$partnerId = intval($preview['chchengbb']);
	$partnerPreview = $partnerId > 0 ? $_pm['mysql']->getOneRecord('SELECT uid FROM userbb WHERE id='.$partnerId) : false;
	$partnerUid = is_array($partnerPreview) ? intval($partnerPreview['uid']) : 0;
	if(!ccBegin(array($uid, $partnerUid))) ccFail('1', true);

	$pet = $_pm['mysql']->getOneRecord('SELECT * FROM userbb WHERE uid='.$uid.' AND id='.$bid.' FOR UPDATE');
	$partner = $partnerId > 0 ? $_pm['mysql']->getOneRecord('SELECT * FROM userbb WHERE id='.$partnerId.' FOR UPDATE') : false;
	if(!is_array($pet)) ccFail('1', true);
	$state = intval($pet['muchang']);
	if($state == 6) ccFail('3', true);
	if(($state == 3 || $state == 5) && !empty($pet['chchengwp'])) ccFail('4', true);
	if($state == 5) ccFail('4', true);

	if($state == 3 || $state == 7){
		$sql = 'UPDATE userbb SET muchang=1,chchengbb=0,chchengwp="" WHERE uid='.$uid.' AND id='.$bid.
			' AND muchang IN (3,7)';
		if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) ccFail('4', true);
		$_pm['mysql']->query('UPDATE player_ext SET chchengbb=0 WHERE uid='.$uid);
		ccCommit();
		die('2');
	}

	if($state != 4 || !empty($pet['chchengwp'])) ccFail('4', true);
	$reciprocal = is_array($partner) && intval($partner['uid']) == $partnerUid && $partnerUid > 0 &&
		intval($partner['chchengbb']) == $bid &&
		(intval($partner['muchang']) == 4 || intval($partner['muchang']) == 5);
	if($reciprocal){
		$sql = 'UPDATE userbb SET chchengbb=0,muchang=3 WHERE uid='.$partnerUid.' AND id='.$partnerId.
			' AND muchang IN (4,5) AND chchengbb='.$bid;
		if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) ccFail('4', true);
	}
	$sql = 'UPDATE userbb SET muchang=1,chchengbb=0,chchengwp="" WHERE uid='.$uid.' AND id='.$bid.' AND muchang=4';
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) ccFail('4', true);
	$_pm['mysql']->query('UPDATE player_ext SET chchengbb=0 WHERE uid IN ('.$uid.($partnerUid > 0 ? ','.$partnerUid : '').')');
	$nickname = ccNickname($uid);
	ccCommit();
	if($reciprocal) ccNotify($partnerUid, '玩家【'.$nickname.'】已经取回了宠物,拒绝与你的宠物传承！');
	die('2');
}elseif($type==3){ //其他玩家的可传承宠物列表
	$sql='SELECT id,name,level,czl,chchengcolor FROM userbb WHERE wx=6 AND muchang=3 AND tgflag=0 AND uid<>'.$uid;
	$arr = $_pm['mysql']->getRecords($sql);
	die(ccPetListHtml($arr));
}elseif($type==5){ //添加其他玩家的宠物到传承中
	$cwid = isset($_REQUEST['cwid']) ? intval($_REQUEST['cwid']) : 0;
	if($bid < 1) ccFail('1');
	$targetPreview = $_pm['mysql']->getOneRecord('SELECT uid FROM userbb WHERE id='.$bid);
	$targetUid = is_array($targetPreview) ? intval($targetPreview['uid']) : 0;
	if($targetUid < 1 || $targetUid == $uid) ccFail('11');
	if(!ccBegin(array($uid, $targetUid))) ccFail('1', true);

	$target = $_pm['mysql']->getOneRecord('SELECT * FROM userbb WHERE uid='.$targetUid.' AND id='.$bid.' FOR UPDATE');
	if(!is_array($target) || intval($target['muchang']) != 3 || intval($target['tgflag']) != 0){
		ccFail('11', true);
	}
	if($cwid < 1){
		$sql = 'UPDATE player_ext SET chchengbb='.$bid.' WHERE uid='.$uid;
		if(!$_pm['mysql']->query($sql)) ccFail('1', true);
		ccCommit();
		die('2');
	}

	$pet = $_pm['mysql']->getOneRecord('SELECT * FROM userbb WHERE uid='.$uid.' AND id='.$cwid.' FOR UPDATE');
	$playerExt = $_pm['mysql']->getOneRecord(
		'SELECT chouqu_chongwu FROM player_ext WHERE uid='.$uid.' FOR UPDATE'
	);
	if(!is_array($pet) || !is_array($playerExt)) ccFail('1', true);
	if(strpos($playerExt['chouqu_chongwu'], ','.$cwid.',') !== false){
		ccFail('宠物抽取过成长,不能进行传承!', true);
	}
	$state = intval($pet['muchang']);
	if($state == 6) ccFail('3', true);
	if($state == 7) ccFail('4', true);
	if($state == 5) ccFail('你已经响应了其他的宠物传承', true);
	if($state == 4) ccFail('已有对象传承了', true);
	if($state != 3 || intval($pet['tgflag']) != 0) ccFail('1', true);

	$sql = 'UPDATE userbb SET muchang=4,chchengbb='.$cwid.' WHERE uid='.$targetUid.' AND id='.$bid.
		' AND muchang=3 AND COALESCE(chchengbb,0)=0 AND tgflag=0';
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) ccFail('11', true);
	$sql = 'UPDATE userbb SET muchang=4,chchengbb='.$bid.' WHERE uid='.$uid.' AND id='.$cwid.
		' AND muchang=3 AND COALESCE(chchengbb,0)=0 AND tgflag=0';
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) ccFail('1', true);
	$_pm['mysql']->query('UPDATE player_ext SET chchengbb=0 WHERE uid='.$uid);
	ccCommit();
	die('2');

}elseif($type==6){
$merge_list="";
$sel = isset($_REQUEST['value']) ? strval($_REQUEST['value']) : '';
$ts = isset($_REQUEST['ts']) ? strval($_REQUEST['ts']) : '';
if($ts=="ts"){
	$sel .= chr(14);
}
$sql='SELECT id FROM player WHERE nickname='.$_pm['mysql']->quote($sel).' LIMIT 1';
	$id=$_pm['mysql']->getOneRecord($sql);
	$sel = htmlspecialchars($sel, ENT_QUOTES, 'UTF-8');
	if(is_array($id)){ //用户是否存在
		$sql="select request_merge,merge,request from player_ext where uid={$id['id']}";
		$arr=$_pm['mysql']->getOneRecord($sql);//查找的人是否有婚姻	
		if(is_array($arr)){
			//$sql="select request_merge,merge from player_ext where uid={$_SESSION['id']}";
			//$arr2=$_pm['mysql']->getOneRecord($sql);
				if(is_array($arr) && $arr['request_merge']==0 && $arr['merge']==0){
					$merge_list="<tr id='t".$id['id']."' style='cursor:pointer;text-align:center;' onmouseover='this.style.border=\"solid 1px #DFD496\";'  onmouseout='this.style.border=0;' onclick='sel(this);mergeid=".$id['id'].";xy_qx();'>
							  <td width='100' align='center' >".$sel."</td>
							  <td width='70' align='left'>无</td>
							  <td width='100' align='left'>可赠送定情礼物</td>
							  </tr>";
				}elseif(is_array($arr) && $arr['request_merge']==$_SESSION['id'] && $arr['merge']==0){
					if($arr['request']==2 || $arr['request']==3){
						$merge_list="<tr id='t".$id['id']."' style='cursor:pointer;text-align:center;' onmouseover='this.style.border=\"solid 1px #DFD496\";'  onmouseout='this.style.border=0;' onclick='sel(this);xy=5;xy_qx();'>
							  <td width='100' align='center' >".$sel."</td>
							  <td width='100' align='center'>无</td>
							  <td width='100' align='center'>你已拒绝</td>
							</tr>";
					}else{
						$merge_list="<tr id='t".$id['id']."' style='cursor:pointer;text-align:center;' onmouseover='this.style.border=\"solid 1px #DFD496\";'  onmouseout='this.style.border=0;' onclick='sel(this);merge_id=".$id['id'].";xy=4;xy_qx();'>
							  <td width='100' align='center' >".$sel."</td>
							  <td width='70' align='left'>无</td>
							  <td width='100' align='left'>向你求婚</td>
							</tr>";
					}
					
				}elseif(is_array($arr) && $arr['request_merge']==0 && $arr['merge']>0){
					$usernickname		= $_pm['user']->getUserById($arr['merge']);
					$merge_list="<tr id='t".$id['id']."' style='cursor:pointer;text-align:center;' onmouseover='this.style.border=\"solid 1px #DFD496\";'  onmouseout='this.style.border=0;' onclick='sel(this);xy_qx();'>
							  <td width='100' align='center' >".$sel."</td>
							  <td width='70' align='left'>".$usernickname['nickname']."</td>
							  <td width='100' align='left'>已婚</td>
							</tr>";
				}elseif(is_array($arr) && $arr['request_merge']!=$_SESSION['id'] && $arr['request_merge']>0 && $arr['merge']==0){
					$usernickname		= $_pm['user']->getUserById($arr['merge']);
					$merge_list="<tr id='t".$id['id']."' style='cursor:pointer;' onmouseover='this.style.border=\"solid 1px #DFD496\";'  onmouseout='this.style.border=0;' onclick='sel(this);xy_qx();'>
							  <td width='100' align='center' >".$sel."</td>
							  <td width='70' align='left'>".$usernickname['nickname']."</td>
							  <td width='100' align='left'>已向别人求婚</td>
							</tr>";
				}
		}else{
		$merge_list="<tr id='t".$id['id']."' style='cursor:pointer;text-align:center;' onmouseover='this.style.border=\"solid 1px #DFD496\";'  onmouseout='this.style.border=0;' onclick='sel(this);mergeid=".$id['id'].";xy_qx();'>
						  <td width='100' align='center' >".$sel."</td>
						  <td width='70' align='left'>无</td>
						  <td width='100' align='left'>可赠送定情礼物</td>
						</tr>";
		}
	}
echo (is_array($id) ? intval($id['id']) : 0)."|".$merge_list;
}elseif($type==7){	
	$cwid = isset($_REQUEST['cwid']) ? intval($_REQUEST['cwid']) : 0;
	if($cwid < 1) ccFail('1');
	$preview = $_pm['mysql']->getOneRecord(
		'SELECT chchengbb FROM userbb WHERE uid='.$uid.' AND id='.$cwid
	);
	if(!is_array($preview) || intval($preview['chchengbb']) < 1) ccFail('22');
	$partnerId = intval($preview['chchengbb']);
	$partnerPreview = $_pm['mysql']->getOneRecord('SELECT uid FROM userbb WHERE id='.$partnerId);
	$partnerUid = is_array($partnerPreview) ? intval($partnerPreview['uid']) : 0;
	if($partnerUid < 1 || $partnerUid == $uid) ccFail('22');
	if(!ccBegin(array($uid, $partnerUid))) ccFail('1', true);

	$pet = $_pm['mysql']->getOneRecord('SELECT * FROM userbb WHERE uid='.$uid.' AND id='.$cwid.' FOR UPDATE');
	$partner = $_pm['mysql']->getOneRecord(
		'SELECT * FROM userbb WHERE uid='.$partnerUid.' AND id='.$partnerId.' FOR UPDATE'
	);
	$playerExt = $_pm['mysql']->getOneRecord(
		'SELECT chouqu_chongwu FROM player_ext WHERE uid='.$uid.' FOR UPDATE'
	);
	if(!is_array($pet) || !is_array($partner) || !is_array($playerExt)) ccFail('22', true);
	if(strpos($playerExt['chouqu_chongwu'], ','.$cwid.',') !== false){
		ccFail('宠物抽取过成长,不能进行传承!', true);
	}
	if(intval($pet['chchengbb']) != $partnerId || intval($partner['chchengbb']) != $cwid){
		ccFail('22', true);
	}
	if(intval($pet['tgflag']) != 0 || intval($partner['tgflag']) != 0){
		ccFail('22', true);
	}
	if(intval($pet['muchang']) == 7) ccFail('9', true);
	if(intval($pet['muchang']) == 6) ccFail('3', true);
	if(intval($pet['muchang']) == 5) ccFail('2', true);
	if(intval($pet['muchang']) != 4) ccFail('22', true);
	if(intval($partner['muchang']) != 4 && intval($partner['muchang']) != 5) ccFail('22', true);
	if(intval($pet['wx']) != 6 || intval($partner['wx']) != 6 ||
		intval($pet['level']) < 90 || intval($partner['level']) < 90 ||
		floatval($pet['czl']) < 60 || floatval($partner['czl']) < 60){
		ccFail('数据有误！', true);
	}

	if(empty($pet['chchengwp'])){
		$orbBagId = isset($_REQUEST['zhu']) ? intval($_REQUEST['zhu']) : 0;
		$skillBagId = isset($_REQUEST['jn']) ? intval($_REQUEST['jn']) : 0;
		$orb = ccMaterial($uid, $orbBagId, 'chuanc');
		if(!is_array($orb)) ccFail('50', true);
		$skillItem = false;
		if($skillBagId > 0){
			$skillItem = ccMaterial($uid, $skillBagId, 'skills');
			if(!is_array($skillItem)) ccFail('50', true);
		}
		if(!ccConsumeMaterial($uid, $orb)) ccFail('您没有相应的物品！', true);
		if(is_array($skillItem) && !ccConsumeMaterial($uid, $skillItem)){
			ccFail('您没有相应的物品！', true);
		}
		$storedSkillId = is_array($skillItem) ? intval($skillItem['pid']) : '';
		$sql = 'UPDATE userbb SET chchengwp="'.intval($orb['pid']).','.$storedSkillId.'" WHERE uid='.$uid.
			' AND id='.$cwid.' AND muchang=4 AND (chchengwp IS NULL OR chchengwp="")';
		if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
			ccFail('您没有相应的物品！', true);
		}
		$pet['chchengwp'] = intval($orb['pid']).','.$storedSkillId;
	}

	$nickname = ccNickname($uid);
	if(intval($partner['muchang']) == 4){
		$sql = 'UPDATE userbb SET muchang=5 WHERE uid='.$uid.' AND id='.$cwid.
			' AND muchang=4 AND chchengbb='.$partnerId;
		if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
			ccFail('22', true);
		}
		ccCommit();
		ccNotify($partnerUid, '玩家【'.$nickname.'】正在等待你确认传承！');
		die('4');
	}

	if(empty($partner['chchengwp'])) ccFail('22', true);
	$ownHistory = ccGrowthHistory($pet['chchengcz'], $pet['czl']);
	$partnerHistory = ccGrowthHistory($partner['chchengcz'], $partner['czl']);
	$now = time();
	$ownSnapshot = $pet['ac'].','.$pet['mc'].','.$pet['srchp'].','.$pet['hits'].','.
		$pet['miss'].','.$pet['speed'].','.$pet['srcmp'].','.$pet['level'];
	$partnerSnapshot = $partner['ac'].','.$partner['mc'].','.$partner['srchp'].','.$partner['hits'].','.
		$partner['miss'].','.$partner['speed'].','.$partner['srcmp'].','.$partner['level'];

	$sql = 'UPDATE userbb SET muchang=6,chchengtime='.$now.',chchengcz='.
		$_pm['mysql']->quote($ownHistory[0].','.$ownHistory[1]).',chchengsx='.
		$_pm['mysql']->quote($partnerSnapshot).' WHERE uid='.$uid.' AND id='.$cwid.
		' AND muchang=4 AND chchengbb='.$partnerId;
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) ccFail('22', true);
	$sql = 'UPDATE userbb SET muchang=6,chchengtime='.$now.',chchengcz='.
		$_pm['mysql']->quote($partnerHistory[0].','.$partnerHistory[1]).',chchengsx='.
		$_pm['mysql']->quote($ownSnapshot).' WHERE uid='.$partnerUid.' AND id='.$partnerId.
		' AND muchang=5 AND chchengbb='.$cwid;
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1) ccFail('22', true);
	ccCommit();
	ccNotify($partnerUid, '玩家【'.$nickname.'】接受了你的传承请求，你宠物现在已经开始传承了！');
	die('5');

}elseif($type==8){
	$cwid = isset($_REQUEST['cwid']) ? intval($_REQUEST['cwid']) : 0;
	$finishMode = isset($_REQUEST['t']) ? intval($_REQUEST['t']) : 0;
	if($cwid < 1) ccFail('1');
	if($finishMode != 1 && $finishMode != 2) ccFail('4');
	if(!ccBegin(array($uid))) ccFail('4', true);

	$pet = $_pm['mysql']->getOneRecord('SELECT * FROM userbb WHERE uid='.$uid.' AND id='.$cwid.' FOR UPDATE');
	if(!is_array($pet)) ccFail('1', true);
	if(intval($pet['muchang']) == 7) ccFail('3', true);
	if(intval($pet['muchang']) != 6) ccFail('4', true);
	if(intval($pet['level']) < 90 || floatval($pet['czl']) < 60) ccFail('数据有误！', true);

	$templates = $_pm['mysql']->getRecords(
		'SELECT id,ac,mc,hp,mp,hits,miss,speed FROM bb WHERE wx=6 AND name='.
		$_pm['mysql']->quote($pet['name']).' LIMIT 2'
	);
	if(!is_array($templates) || count($templates) != 1) ccFail('宠物模板数据错误！', true);
	$template = $templates[0];

	$materialIds = explode(',', strval($pet['chchengwp']));
	$orbPid = isset($materialIds[0]) ? intval($materialIds[0]) : 0;
	$skillPid = isset($materialIds[1]) ? intval($materialIds[1]) : 0;
	$orb = $orbPid > 0 ? $_pm['mysql']->getOneRecord('SELECT effect FROM props WHERE id='.$orbPid) : false;
	if(!is_array($orb)) ccFail('没有传承珠', true);
	$orbEffect = explode(':', $orb['effect']);
	if(!isset($orbEffect[0], $orbEffect[1]) || $orbEffect[0] !== 'chuanc' ||
		!is_numeric($orbEffect[1]) || floatval($orbEffect[1]) <= 0){
		ccFail('没有传承珠', true);
	}
	$multiplier = floatval($orbEffect[1]);

	$skillDivisor = 0;
	if($skillPid > 0){
		$skillItem = $_pm['mysql']->getOneRecord('SELECT effect FROM props WHERE id='.$skillPid);
		$skillEffect = is_array($skillItem) ? explode(':', $skillItem['effect']) : array();
		if(!isset($skillEffect[0], $skillEffect[1]) || $skillEffect[0] !== 'skills' ||
			!is_numeric($skillEffect[1]) || floatval($skillEffect[1]) <= 0){
			ccFail('技能保留道具配置错误！', true);
		}
		$skillDivisor = floatval($skillEffect[1]);
	}

	$otherStats = explode(',', strval($pet['chchengsx']));
	if(count($otherStats) != 8) ccFail('传承属性快照错误！', true);
	foreach($otherStats as $statValue){
		if(!is_numeric($statValue)) ccFail('传承属性快照错误！', true);
	}

	$remainingSeconds = intval($pet['chchengtime']) + 86400 - time();
	if($remainingSeconds < 0) $remainingSeconds = 0;
	$crystalCost = intval(floor($remainingSeconds / 60));
	if($finishMode == 1 && $crystalCost > 0){
		ccFail('立即完成将消耗您'.$crystalCost.'水晶！', true);
	}
	if($finishMode == 2 && $crystalCost > 0){
		$sql = 'UPDATE player_ext SET sj=sj-'.$crystalCost.' WHERE uid='.$uid.' AND sj>='.$crystalCost;
		if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
			ccFail('10', true);
		}
	}

	$newPet = array();
	$newPet['ac'] = ccInheritedStat($template['ac'], $pet['ac'], $pet['level'], $otherStats[0], $otherStats[7], $multiplier);
	$newPet['mc'] = ccInheritedStat($template['mc'], $pet['mc'], $pet['level'], $otherStats[1], $otherStats[7], $multiplier);
	$newPet['srchp'] = ccInheritedStat($template['hp'], $pet['srchp'], $pet['level'], $otherStats[2], $otherStats[7], $multiplier);
	$newPet['hits'] = ccInheritedStat($template['hits'], $pet['hits'], $pet['level'], $otherStats[3], $otherStats[7], $multiplier);
	$newPet['miss'] = ccInheritedStat($template['miss'], $pet['miss'], $pet['level'], $otherStats[4], $otherStats[7], $multiplier);
	$newPet['speed'] = ccInheritedStat($template['speed'], $pet['speed'], $pet['level'], $otherStats[5], $otherStats[7], $multiplier);
	$newPet['srcmp'] = ccInheritedStat($template['mp'], $pet['srcmp'], $pet['level'], $otherStats[6], $otherStats[7], $multiplier);

	$addedStats = array(
		ccNumberDifference($newPet['ac'], $pet['ac']),
		ccNumberDifference($newPet['mc'], $pet['mc']),
		ccNumberDifference($newPet['srchp'], $pet['srchp']),
		ccNumberDifference($newPet['hits'], $pet['hits']),
		ccNumberDifference($newPet['miss'], $pet['miss']),
		ccNumberDifference($newPet['speed'], $pet['speed']),
		ccNumberDifference($newPet['srcmp'], $pet['srcmp'])
	);
	$oldAddedStats = explode(',', strval($pet['addsx']));
	$inheritCount = isset($oldAddedStats[7]) ? intval($oldAddedStats[7]) + 1 : 1;
	$addedStats[] = $inheritCount;

	$skillChance = $skillDivisor > 0 ? round(10 + 100 / $skillDivisor) : 10;
	if($skillChance > 100) $skillChance = 100;
	$skillList = rand(1, 100) <= $skillChance ? $pet['skillist'] : '1:1';
	$skillMap = ccSkillMap($skillList);
	$normalizedSkills = array();
	foreach($skillMap as $sid => $skillLevel) $normalizedSkills[] = $sid.':'.$skillLevel;
	$skillList = implode(',', $normalizedSkills);
	if($skillList === '') ccFail('宠物技能数据更新失败！', true);
	if(!ccSyncSkillDetails($cwid, $skillList)) ccFail('宠物技能数据更新失败！', true);
	$sql = 'UPDATE userbb SET level=1,ac='.$newPet['ac'].',mc='.$newPet['mc'].',nowexp=0,lexp=170,'.
		'skillist='.$_pm['mysql']->quote($skillList).',srchp='.$newPet['srchp'].',hp='.$newPet['srchp'].','.
		'hits='.$newPet['hits'].',srcmp='.$newPet['srcmp'].',mp='.$newPet['srcmp'].',miss='.$newPet['miss'].','.
		'speed='.$newPet['speed'].',muchang=7,addsx='.$_pm['mysql']->quote(implode(',', $addedStats)).','.
		'chchengwp="",chchengcolor="#FF66CC",chchengsx="" WHERE uid='.$uid.' AND id='.$cwid.' AND muchang=6';
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
		ccFail('4', true);
	}
	ccCommit();

	$log = '传承完成 pet='.$cwid.',partner='.$pet['chchengbb'].',materials='.$pet['chchengwp'].
		',old_level='.$pet['level'].',new_stats='.implode(',', $newPet).',skill_chance='.$skillChance;
	$_pm['mysql']->query('INSERT INTO gamelog (ptime,seller,buyer,pnote,vary) VALUES ('.time().','.$uid.','.$uid.','.
		$_pm['mysql']->quote($log).',235)');
	die('2');

}elseif($type==9){
	$ser = isset($_REQUEST['txt']) ? trim(strval($_REQUEST['txt'])) : '';
	if($ser===''){
		die('3');
	}
	$sql='SELECT id,name,level,czl,chchengcolor FROM userbb WHERE wx=6 AND muchang=3 AND tgflag=0'.
		' AND uid<>'.$uid.' AND name='.$_pm['mysql']->quote($ser);
	$arr = $_pm['mysql']->getRecords($sql);
	if(!is_array($arr)) die('2');
	die(ccPetListHtml($arr));
}elseif($type==10){
	if($bid < 1) ccFail('1');
	$preview = $_pm['mysql']->getOneRecord(
		'SELECT chchengbb FROM userbb WHERE uid='.$uid.' AND id='.$bid
	);
	if(!is_array($preview)) ccFail('1');
	$partnerId = intval($preview['chchengbb']);
	$partnerPreview = $partnerId > 0 ? $_pm['mysql']->getOneRecord('SELECT uid FROM userbb WHERE id='.$partnerId) : false;
	$partnerUid = is_array($partnerPreview) ? intval($partnerPreview['uid']) : 0;
	if(!ccBegin(array($uid, $partnerUid))) ccFail('1', true);

	$user = $_pm['mysql']->getOneRecord('SELECT maxbag FROM player WHERE id='.$uid.' FOR UPDATE');
	$pet = $_pm['mysql']->getOneRecord('SELECT * FROM userbb WHERE uid='.$uid.' AND id='.$bid.' FOR UPDATE');
	$partner = $partnerId > 0 ? $_pm['mysql']->getOneRecord('SELECT * FROM userbb WHERE id='.$partnerId.' FOR UPDATE') : false;
	if(!is_array($user) || !is_array($pet)) ccFail('1', true);
	$state = intval($pet['muchang']);
	if($state != 3 && $state != 4 && $state != 5) ccFail('1', true);

	$storedIds = explode(',', strval($pet['chchengwp']));
	$materialIds = array();
	if(isset($storedIds[0]) && intval($storedIds[0]) > 0) $materialIds[] = intval($storedIds[0]);
	if(isset($storedIds[1]) && intval($storedIds[1]) > 0) $materialIds[] = intval($storedIds[1]);
	$materials = array();
	foreach($materialIds as $index => $propsId){
		$material = $_pm['mysql']->getOneRecord(
			'SELECT id,sell,vary,effect FROM props WHERE id='.$propsId
		);
		if(!is_array($material)) ccFail('材料配置错误！', true);
		$effect = explode(':', $material['effect']);
		$expectedEffect = $index == 0 ? 'chuanc' : 'skills';
		if(!isset($effect[0]) || $effect[0] !== $expectedEffect) ccFail('材料配置错误！', true);
		$materials[] = $material;
	}

	$bagRows = $_pm['mysql']->getRecords(
		'SELECT id,pid,sums,sell,bsum,psum,pyb,zbing,zbpets FROM userbag WHERE uid='.$uid.' FOR UPDATE'
	);
	if(!is_array($bagRows)) $bagRows = array();
	$usedSlots = 0;
	$stackByPid = array();
	foreach($bagRows as $bagRow){
		if(intval($bagRow['sums']) > 0 && intval($bagRow['zbing']) == 0) $usedSlots++;
		if(intval($bagRow['sums']) > 0 && intval($bagRow['zbing']) == 0 &&
			intval($bagRow['bsum']) == 0 &&
			intval($bagRow['psum']) == 0 && intval($bagRow['pyb']) == 0 &&
			intval($bagRow['zbpets']) == 0 && !isset($stackByPid[intval($bagRow['pid'])])){
			$stackByPid[intval($bagRow['pid'])] = intval($bagRow['id']);
		}
	}
	$newSlots = 0;
	foreach($materials as $material){
		$propsId = intval($material['id']);
		if(intval($material['vary']) == 2 || !isset($stackByPid[$propsId])) $newSlots++;
	}
	if($usedSlots + $newSlots > intval($user['maxbag'])){
		ccFail('100', true);
	}

	foreach($materials as $material){
		$propsId = intval($material['id']);
		if(intval($material['vary']) != 2 && isset($stackByPid[$propsId])){
			$sql = 'UPDATE userbag SET sums=sums+1 WHERE uid='.$uid.' AND id='.$stackByPid[$propsId];
		}else{
			$sql = 'INSERT INTO userbag (uid,pid,sell,vary,sums,stime) VALUES ('.$uid.','.$propsId.','.
				intval($material['sell']).','.intval($material['vary']).',1,'.time().')';
		}
		if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
			ccFail('材料退还失败，请重试！', true);
		}
	}

	$reciprocal = is_array($partner) && $partnerUid > 0 && intval($partner['uid']) == $partnerUid &&
		intval($partner['chchengbb']) == $bid &&
		(intval($partner['muchang']) == 4 || intval($partner['muchang']) == 5);
	if($reciprocal){
		$sql = 'UPDATE userbb SET chchengbb=0,muchang=3 WHERE uid='.$partnerUid.' AND id='.$partnerId.
			' AND muchang IN (4,5) AND chchengbb='.$bid;
		if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
			ccFail('材料退还失败，请重试！', true);
		}
	}
	$sql = 'UPDATE userbb SET muchang=1,chchengbb=0,chchengwp="" WHERE uid='.$uid.' AND id='.$bid.
		' AND muchang IN (3,4,5)';
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
		ccFail('材料退还失败，请重试！', true);
	}
	$_pm['mysql']->query('UPDATE player_ext SET chchengbb=0 WHERE uid IN ('.$uid.($partnerUid > 0 ? ','.$partnerUid : '').')');
	$nickname = ccNickname($uid);
	ccCommit();
	if($reciprocal) ccNotify($partnerUid, '玩家【'.$nickname.'】已经取回了宠物,拒绝与你的宠物传承！');
	die('4');

}	
$_pm['mem']->memClose();
?>
