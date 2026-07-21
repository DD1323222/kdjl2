<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%
*/
require_once('../config/config.game.php');
secStart($_pm['mem']);

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');

function mergeModHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function mergeModJsSingle($value)
{
	$value = str_replace("\\", "\\\\", (string)$value);
	$value = str_replace("'", "\\'", $value);
	$value = str_replace(array("\r", "\n"), array("\\r", "\\n"), $value);
	return $value;
}

function mergeModJsDouble($value)
{
	$value = str_replace("\\", "\\\\", (string)$value);
	$value = str_replace('"', '\\"', $value);
	$value = str_replace(array("\r", "\n"), array("\\r", "\\n"), $value);
	return $value;
}

function mergeModColor($value)
{
	return preg_match('/^#[0-9a-fA-F]{6}$/', (string)$value) ? $value : '#000000';
}

function mergeModImage($value)
{
	$value = basename((string)$value);
	return preg_match('/^[A-Za-z0-9_.-]+$/', $value) ? $value : '';
}

function mergeModExpireRequest($requestUid, $partnerUid)
{
	global $_pm;
	$requestUid = intval($requestUid);
	$partnerUid = intval($partnerUid);
	if($requestUid < 1 || $partnerUid < 1) return false;

	$cutoff = time() - 86400;
	$sql = 'UPDATE player_ext SET sj=COALESCE(sj,0)+2000,request=0 '.
		'WHERE uid='.$requestUid.' AND request=1 AND nomergetime<='.$cutoff;
	if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1)
	{
		return false;
	}

	$partner = $_pm['user']->getUserById($partnerUid);
	$partnerName = is_array($partner) && isset($partner['nickname']) ? $partner['nickname'] : '';
	$notice = '玩家【'.$partnerName.'】24小时内未响应你的离婚请求，系统已取消请求并退回2000水晶。';
	$noticeSql = $_pm['mysql']->escape($notice);
	$noticeTime = date('Y-m-d H:i:s');
	if($_pm['mysql']->query("INSERT INTO information(uid,times,content) VALUES({$requestUid},'{$noticeTime}','{$noticeSql}')"))
	{
		require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
		$socket = new socketmsg();
		$socket->sendMsg(kdjlSafeIconv('gbk','utf-8','SYSN|information-->'), array($requestUid));
	}
	return true;
}

$petsAll  = $_pm['user']->getUserPetById($uid);
$user		= $_pm['user']->getUserById($uid);
$props		= kdjlSafeMemValue($_pm['mem']->get('db_propsid'), array());
$userBag	= $_pm['user']->getUserBagById($uid);
if(!is_array($petsAll)) $petsAll = array();
if(!is_array($user)) $user = array();
if(!is_array($props)) $props = array();
if(!is_array($userBag)) $userBag = array();
$userDefaults = array('money'=>0, 'sj'=>0, 'maxbag'=>0, 'maxmc'=>0);
foreach($userDefaults as $userDefaultKey => $userDefaultValue)
{
	if(!isset($user[$userDefaultKey])) $user[$userDefaultKey] = $userDefaultValue;
}
$curBagNum = 0;
$merge_list="";
$id1="";
$time=0;
$chchengjnlist="";
$chchengzhu="";
$petslist = '';
$mybag = '';
$img1 = '';
$img2 = '';
$shop = '';
$chcbb1=0;//加入对方的bb的id
//$mergeid=0;
$cwid=0;
//$img1=$img2='<img src="../images/cwb.jpg" width="64" height="103" />';
$showchc = (isset($_REQUEST['show']) && !is_array($_REQUEST['show'])) ? intval($_REQUEST['show']) : 0;


if (is_array($userBag))
{
	foreach($userBag as $k => $v1)
	{
		if(!is_array($v1)) continue;
		$v1Defaults = array('id'=>0, 'name'=>'', 'sums'=>0, 'zbing'=>0, 'varyname'=>0, 'effect'=>'');
		foreach($v1Defaults as $v1DefaultKey => $v1DefaultValue)
		{
			if(!isset($v1[$v1DefaultKey])) $v1[$v1DefaultKey] = $v1DefaultValue;
		}

		if ($v1['sums'] < 1 || $v1['id']==0 || $v1['zbing'] == 1) continue;
		$curBagNum++;


		if($v1['varyname'] == 20 && $v1['effect']!='' && $v1['sums']>0){     //物品分类类型、背包中有转生用的物品
			$chuancname=explode(':',$v1['effect']);
			 if($chuancname[0]=='skills'){
				$chchengjnlist .= "<option value='{$v1['id']}'>".mergeModHtml($v1['name'])."</option>\n";

			 }else if($chuancname[0]=='chuanc'){
				$chchengzhu .= "<option value='{$v1['id']}'>".mergeModHtml($v1['name'])."</option>\n";
			 }
		}
	}
}

//牧场bb列表
$pall = 0;
$mainbb =''; //1:26;2:123 ;3:235;

$sql="select chchengbb,nomergetime,request,uid,merge from player_ext where uid = {$uid}";
$chchengarr=$_pm['mysql']->getOneRecord($sql);
if(!is_array($chchengarr))
{
	$chchengarr = array('chchengbb'=>0, 'nomergetime'=>0, 'request'=>0, 'uid'=>0, 'merge'=>0);
}
$chchengarr['nomergetime'] = isset($chchengarr['nomergetime']) ? intval($chchengarr['nomergetime']) : 0;
$chchengarr['request'] = isset($chchengarr['request']) ? intval($chchengarr['request']) : 0;
$chchengarr['uid'] = isset($chchengarr['uid']) ? intval($chchengarr['uid']) : $uid;
$chchengarr['merge'] = isset($chchengarr['merge']) ? intval($chchengarr['merge']) : 0;
if($chchengarr['request']==1){
	if(time()-$chchengarr['nomergetime']>=86400){
			mergeModExpireRequest($uid, $chchengarr['merge']);
	}
}else if($chchengarr['merge'] > 0){
	$sql="select nomergetime,request from player_ext where uid = {$chchengarr['merge']}";
	$sjmerge=$_pm['mysql']->getOneRecord($sql);
	if(!is_array($sjmerge)) $sjmerge = array('nomergetime'=>0, 'request'=>0);
	$sjmerge['request'] = isset($sjmerge['request']) ? intval($sjmerge['request']) : 0;
	$sjmerge['nomergetime'] = isset($sjmerge['nomergetime']) ? intval($sjmerge['nomergetime']) : 0;
	if($sjmerge['request']==1 && time()-$sjmerge['nomergetime']>=86400){
		mergeModExpireRequest($chchengarr['merge'], $uid);
	}
}
if (!is_array($petsAll)) $petslist='获取宝宝数据失败!';
else
{


	if($chchengarr['chchengbb']>0){
		$sql="select cardimg from userbb where id={$chchengarr['chchengbb']}";
		$chcbb11 = $_pm['mysql'] ->getOneRecord($sql);
		if(is_array($chcbb11) && isset($chcbb11['cardimg']))
		{
			$img2= "<img src='".IMAGE_SRC_URL."/bb/".mergeModImage($chcbb11['cardimg'])."'  style='cursor:pointer;'>";
		}
	}

	$kk = 0;
	$lastMuchang = 0;
	foreach ($petsAll as $k => $rs)
	{
		$lastMuchang = isset($rs['muchang']) ? intval($rs['muchang']) : 0;
		if ($rs['name'] == '') continue;
		if ($rs['muchang']==1 || $rs['muchang']==3 || $rs['muchang']==4 || $rs['muchang']==5 || $rs['muchang']==6 || $rs['muchang']==7 ){
			$pall++;
		}
		if ($rs['muchang']==1 && $rs['tgflag'] == 0 && $rs['wx']==6 && $rs['level']>=90 && $rs['czl']>=60)
		{
			$nameHtml = mergeModHtml($rs['name']);
			$nameJs = mergeModHtml(mergeModJsSingle($rs['name']));
			$color = mergeModColor(isset($rs['chchengcolor']) ? $rs['chchengcolor'] : '');
			$petslist .= '<tr>
						<td width="35" align="center">&nbsp;</td>
			<td width="100px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left;" onmouseover="pos=0;mcbbshow('.$rs['id'].');this.style.border=\'solid 1px #DFD496\';"  onmouseout="mcbbdisplay();this.style.border=0;" onclick="sel(this);copyWord(\''.$nameJs.'\');bid='.$rs['id'].';"><font color="'.$color.'">'.$nameHtml.'</font></td>
			<td style="text-align:left;" width="80px"> '.$rs['level'].'</td>
						<td style="text-align:left;" width="70px" >'.$rs['czl'].'</td>
		  </tr>';
		}elseif(($rs['muchang']==3 || $rs['muchang']==4 || $rs['muchang']==5 || $rs['muchang']==6 || $rs['muchang']==7) && $rs['tgflag'] == 0 ){ //&& $rs['wx']==6

			$cwid=$rs['id'];
			$img1= "<img src='".IMAGE_SRC_URL."/bb/".mergeModImage($rs['cardimg'])."' style='cursor:pointer;'>";
				if(($rs['muchang']==4 || $rs['muchang']==5 || $rs['muchang']==6 || $rs['muchang']==7) && $rs['chchengbb']>0){
					$chcbb1=$rs['chchengbb'];
					$sql="select cardimg from userbb where id={$rs['chchengbb']}";
					$chcbb = $_pm['mysql'] ->getOneRecord($sql);
					if(is_array($chcbb) && isset($chcbb['cardimg']))
					{
						$img2= "<img src='".IMAGE_SRC_URL."/bb/".mergeModImage($chcbb['cardimg'])."'  style='cursor:pointer;'>";
					}
				}
				if($rs['muchang']==6){
					$time=86400-(time()-$rs['chchengtime']);
					if($time < 0){
						$time = -1;
					}
				}
				if($rs['muchang']==7){
					$time=-1;

				}
		}
	}
	if ($petslist == '' && ($lastMuchang==6 || $lastMuchang==5 || $lastMuchang==4 || $lastMuchang==3 || $lastMuchang==7)) $petslist = '牧场里面还没有宝贝！';
}
//牧场结束

//玩家传承宠物





//结婚道具
if($curBagNum > 0){
	foreach($userBag as $v){
		if(!is_array($v)) continue;
		$vDefaults = array('id'=>0, 'name'=>'', 'sums'=>0, 'sell'=>0, 'vary'=>0, 'varyname'=>'', 'merge'=>0);
		foreach($vDefaults as $vDefaultKey => $vDefaultValue)
		{
			if(!isset($v[$vDefaultKey])) $v[$vDefaultKey] = $vDefaultValue;
		}
		if($v['merge'] == 1 && $v['sums'] > 0){
			$nameHtml = mergeModHtml($v['name']);
			$nameJs = mergeModHtml(mergeModJsDouble($v['name']));
			$mybag .="<tr>
              <td width='50' align='center'><img src='../images/ui/bag/".intval($v['varyname']).".gif' width='23' height='23' /></td>
              <td width='100' align='left' id='t".$v['id']."' onmouseover='showTip(".$v['id'].",0,1,2);this.style.border=\"solid 1px #DFD496\";' onmouseout='window.parent.UnTip();this.style.border=0;' onclick='sel(this);copyWord(\"".$nameJs."\");pid=".$v['id'].";vary=".$v['vary'].";this.style.backgroundColor=\"#CCCC00\";' style='cursor:pointer;'>
			  ".$nameHtml."

			  </td>
              <td width='80' align='center'>".$v['sell']."</td>
              <td width='70' align='center'>".$v['sums']."</td>
            </tr>";
		}
	}
}
//道具结束


//载入时列表（已婚）
$sql="select merge,request,sj,request_merge,send from player_ext where uid={$uid}";
$merge=$_pm['mysql']->getOneRecord($sql);
if(!is_array($merge))
{
	$merge = array('merge'=>0, 'request'=>0, 'sj'=>0, 'request_merge'=>0, 'send'=>'');
}
if(is_array($merge) && $merge['merge']>0 && $merge['request_merge']==0 ){ //已建立婚姻
	$sql="select * from player_ext where uid={$merge['merge']} and merge={$uid}";
	$merge_lihun=$_pm['mysql']->getOneRecord($sql);//查找对方
	if(is_array($merge_lihun)){
		$sql="select nickname from player where id={$merge['merge']}"; //对方昵称
		$merge_name=$_pm['mysql']->getOneRecord($sql);
		$mergeNickname = is_array($merge_name) && isset($merge_name['nickname']) ? mergeModHtml($merge_name['nickname']) : '';
		if($merge_lihun['request']==1 && $merge['request']==0){ //对方请求离婚
			  $merge_list.="<tr onclick=\"sel(this);merge_id={$merge['merge']};xy=1;xy_qx();\" style='cursor:pointer;' onmouseover='this.style.border=\"solid 1px #DFD496\";'  onmouseout='this.style.border=0;' title='对方要求离婚，你可以接受也可拒绝对方的要求！'><td width='10'></td>
							  <td width='100' align='center' >".$mergeNickname."</td>
								<td width='80' align='center'>有</td>
								 <td width='100' align='center'>对方请求离婚</td>
									</tr>";
		}elseif($merge_lihun['request']==0 && $merge['request']==1){ //你提出离婚请求


				$merge_list.="<tr onclick=\";sel(this);xy=2;xy_qx();\" style='cursor:pointer;' onmouseover='this.style.border=\"solid 1px #DFD496\";'  onmouseout='this.style.border=0;' title='如果对方拒绝或24小时内对方没有响应，你的婚姻将回复正常，扣除的水晶将返回，如果你坚持离婚也可选择强制离婚！'><td width='10'></td>
							  <td width='100' align='center' >".$mergeNickname."</td>
								<td width='80' align='center'>有</td>
								 <td width='100' align='center'>你请求离婚</td>
									</tr>";
		}elseif($merge_lihun['request']==0 && $merge['request']==0){//婚姻正常
				$merge_list.="<tr onclick=\"sel(this);xy=7;xy_qx();\" style='cursor:pointer;' onmouseover='this.style.border=\"solid 1px #DFD496\";'  onmouseout='this.style.border=0;'><td width='10'></td>
							  <td width='100' align='center' >".$mergeNickname."</td>
								<td width='70' align='left'>有</td>
								 <td width='100' align='left'>正常婚姻</td>
									</tr>";
		}

	}
}elseif(is_array($merge) && $merge['merge']==0 && $merge['request_merge']>0 ){ //我发出请求对方结婚
	$sql="select nickname from player where id={$merge['request_merge']}"; //对方昵称
	$merge_name1=$_pm['mysql']->getOneRecord($sql);

	$sql="select merge from player_ext where uid={$merge['request_merge']}";
	$mergezc=$_pm['mysql']->getOneRecord($sql);//查找对方
	if(!is_array($mergezc)) $mergezc = array('merge'=>0);



		if(is_array($merge_name1)){
			if($mergezc['merge']>0){
				$yes_no= $_pm['user']->getUserById($mergezc['merge']);
				$hunpei=is_array($yes_no) && isset($yes_no['nickname']) ? mergeModHtml($yes_no['nickname']) : '';
			}else{
				$hunpei="无";
			}
			$mergeName1Html = mergeModHtml($merge_name1['nickname']);
			if($merge['request']==2 ){

				$merge_list.="<tr onclick=\"sel(this);merge_id=0;xy=6;xy_qx();\" style='cursor:pointer;' onmouseover='this.style.border=\"solid 1px #DFD496\";'  onmouseout='this.style.border=0;'><td width='10'></td>
									  <td width='100' align='center' >".$mergeName1Html."</td>
										<td width='80' align='center'>".$hunpei."</td>
										 <td width='100' align='center'>对方已拒绝</td>
											</tr>";
			}elseif($merge['request']==0){
				if(!empty($merge['send']) && $mergezc['merge']>0){

					$merge_list.="<tr onclick=\"sel(this);xy=13;xy_qx();\" style='cursor:pointer;' onmouseover='this.style.border=\"solid 1px #DFD496\";'  onmouseout='this.style.border=0;' title='对方与选择其他玩家结成夫妻，请取回你的物品，选择其他的对象求婚！'><td width='10'></td>
									  <td width='100' align='center' >".$mergeName1Html."</td>
										<td width='80' align='center'>".$hunpei."</td>
										 <td width='100' align='center'>对方与他人结婚</td>
											</tr>";
				}else{
					$merge_list.="<tr onclick=\"sel(this);xy=3;xy_qx();\" style='cursor:pointer;' onmouseover='this.style.border=\"solid 1px #DFD496\";'  onmouseout='this.style.border=0;' ><td width='10'></td>
									  <td width='100' align='center' >".$mergeName1Html."</td>
										<td width='80' align='center'>无</td>
										 <td width='100' align='center'>你已向该玩家求婚</td>
											</tr>";
				}

			}

		}

}else{ //别人对我的求婚
	$sql="select uid,request from player_ext where request_merge={$uid} and merge=0 ";//request=2 已经拒绝 request=1离婚请求
	$request_merge=$_pm['mysql']->getRecords($sql);
	if(is_array($request_merge)){

		foreach($request_merge as $k=>$v){
				$sql="select nickname from player where id={$v['uid']}";
				$merge_name=$_pm['mysql']->getOneRecord($sql);
				if(is_array($merge_name)){
					$requestMergeName = mergeModHtml($merge_name['nickname']);
					if($v['request']==2 || $v['request']==3){ //为2时拒绝并通知，为3时不通知//merge_id={$v['uid']};
							$merge_list.="<tr onclick=\"sel(this);xy=5;xy_qx();\" style='cursor:pointer;' onmouseover='this.style.border=\"solid 1px #DFD496\";'  onmouseout='this.style.border=0;'>
							 <td width='10'></td>
							  <td width='100' align='center' >".$requestMergeName."</td>
							  <td width='80' align='center'>无</td>
							  <td width='100' align='center' >你已拒绝</td>
							</tr>";

					}else{
						$merge_list.="<tr onclick=\"sel(this);merge_id={$v['uid']};xy=4;xy_qx();\" style='cursor:pointer;' onmouseover='this.style.border=\"solid 1px #DFD496\";'  onmouseout='this.style.border=0;'>
							 <td width='10'></td>
							  <td width='100' align='center' >".$requestMergeName."</td>
							  <td width='80' align='center'>无</td>
							  <td width='100' align='center'>向你求婚</td>
							</tr>";
					}

				}
		}
	}
}

if(empty($user['sj'])){
	$user['sj'] = 0;
}

$tn = $_game['template'] . 'tpl_merge.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);

	$src = array('#money#',
				 '#sj#',
				 '#baglimit#',
				 '#mybag#',
				  '#merge_list#',
				  //'#mergeid#',
				  '#petslist#',
				  '#img1#',
				   '#img2#','#cwid#','#usemuchang#','#maxmuchang#','#showchc#','#chcbb#','#time#','#chcjnlist#','#chchengzhu#'
				);
	$des = array($user['money'],
				 $merge['sj'],
				 $curBagNum.'/'.$user['maxbag'],
				 $mybag,
				 $merge_list,
				// $mergeid,
				 $petslist,
				 $img1,
				  $img2,$cwid,$pall,$user['maxmc'],$showchc,$chcbb1,$time,$chchengjnlist,$chchengzhu
				);
	$shop = str_replace($src, $des, $tpl);
}


// gzip echo. if maybe.
ob_start('ob_gzip');
echo $shop;
ob_end_flush();

?>
