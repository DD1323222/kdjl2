<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%
*/
require_once('../config/config.game.php');
secStart($_pm['mem']);

$petsAll  = $_pm['user']->getUserPetById($_SESSION['id']);
$user		= $_pm['user']->getUserById($_SESSION['id']);
$props		= unserialize($_pm['mem']->get('db_propsid'));
$userBag	= $_pm['user']->getUserBagById($_SESSION['id']);
$curBagNum = 0;
$merge_list="";
$id1="";
$time=0;
$chchengjnlist="";
$chchengzhu="";
$chcbb1=0;//加入对方的bb的id
//$mergeid=0;
$cwid=0;
//$img1=$img2='<img src="../images/cwb.jpg" width="64" height="103" />';
$showchc=$_REQUEST['show'];
if(empty($showchc)){
	$showchc=0;
}


if (is_array($userBag))
{
	foreach($userBag as $k => $v1)
	{
	
		if ($v1['sums'] < 1 || $v1['id']==0 || $v1['zbing'] == 1) continue;
		$curBagNum++;
	
	
		if($v1['varyname'] == 20 && $v1['effect']!='' && $v1['sums']>0){     //物品分类类型、背包中有转生用的物品
			$chuancname=explode(':',$v1['effect']);
			 if($chuancname[0]=='skills'){
			 	$chchengjnlist .= "<option value='{$v1['id']}'>{$v1['name']}</option>\n";
				
			 }else if($chuancname[0]=='chuanc'){
			 	$chchengzhu .= "<option value='{$v1['id']}'>{$v1['name']}</option>\n";
			 }
		}
	}
}

//牧场bb列表
$pall = 0;
$mainbb =''; //1:26;2:123 ;3:235;

$sql="select chchengbb,nomergetime,request,uid,merge from player_ext where uid = {$_SESSION['id']}";
$chchengarr=$_pm['mysql']->getOneRecord($sql);
if($chchengarr['request']==1){
	if(time()-$chchengarr['nomergetime']>=86400){
			$sql = "UPDATE player_ext SET sj = sj + 2000,request=0 WHERE uid = {$_SESSION['id']} ";
			$_pm['mysql']->query($sql);
		
			//
			//玩家24小时没响应你的离婚请求，已经自动取消了
			$userx		= $_pm['user']->getUserById($chchengarr['uid']);
			$tt=date('Y-m-d H:m:s',time());
			$_pm['mysql']->query("insert into information(uid,times,content) values({$chchengarr['merge']},'{$tt}','玩家【{$userx['nickname']}】玩家24小时没响应你的离婚请求，已经自动取消了你的请求如果你要坚持离婚可选择强制离婚，你的2000水晶已收回，婚姻回复正常！')");
			require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
			$s=new socketmsg();
			$rs=$s->sendMsg(iconv('gbk','utf-8','SYSN|information-->'),array($chchengarr['merge']));
	}
}else{
	$sql="select nomergetime,request from player_ext where uid = {$chchengarr['merge']}";
	$sjmerge=$_pm['mysql']->getOneRecord($sql);
	if($sjmerge['request']==1){
		if(time()-$sjmerge['nomergetime']>=86400){
			$sql = "UPDATE player_ext SET sj = sj + 2000,request=0 WHERE uid = {$chchengarr['merge']} ";
			$_pm['mysql']->query($sql);
			
			$userx		= $_pm['user']->getUserById($_SESSION['id']);
			$tt=date('Y-m-d H:m:s',time());
			$_pm['mysql']->query("insert into information(uid,times,content) values({$chchengarr['merge']},'{$tt}','玩家【{$userx['nickname']}】玩家24小时没响应你的离婚请求，已经自动取消了你的请求如果你要坚持离婚可选择强制离婚，你的2000水晶已收回，婚姻回复正常！')");
			
			require_once(dirname(__FILE__).'/../socketChat/config.chat.php');
			$s=new socketmsg();
			$rs=$s->sendMsg(iconv('gbk','utf-8','SYSN|information-->'),array($chchengarr['merge']));
			
		}
	}
}
if (!is_array($petsAll)) $petslist='获取宝宝数据失败!';
else 
{

	
	if($chchengarr['chchengbb']>0){
		$sql="select cardimg from userbb where id={$chchengarr['chchengbb']}";
		$chcbb11 = $_pm['mysql'] ->getOneRecord($sql);
		$img2= "<img src='".IMAGE_SRC_URL."/bb/{$chcbb11['cardimg']}'  style='cursor:pointer;'>";
	}
	
	$kk = 0;
	foreach ($petsAll as $k => $rs)
	{
		if ($rs['name'] == '') continue;
		if ($rs['muchang']==1 || $rs['muchang']==3 || $rs['muchang']==4 || $rs['muchang']==5 || $rs['muchang']==6 || $rs['muchang']==7 ){
			$pall++;
		}
		if ($rs['muchang']==1 && $rs['tgflag'] == 0 && $rs['wx']==6 && $rs['level']>=90 && $rs['czl']>=60)
		{
			$petslist .= '<tr>
						<td width="35" align="center">&nbsp;</td>
              			<td width="100px" id="t'.$rs['id'].'" style="cursor:pointer;text-align:left;" onmouseover="pos=0;mcbbshow('.$rs['id'].');this.style.border=\'solid 1px #DFD496\';"  onmouseout="mcbbdisplay();this.style.border=0;" onclick="sel(this);copyWord(\''.$rs['name'].'\');bid='.$rs['id'].';"><font color="'.$rs['chchengcolor'].'">'.$rs['name'].'</font></td>
              			<td style="text-align:left;" width="80px"> '.$rs['level'].'</td>
						<td style="text-align:left;" width="70px" >'.$rs['czl'].'</td>
            		  </tr>';
		}elseif(($rs['muchang']==3 || $rs['muchang']==4 || $rs['muchang']==5 || $rs['muchang']==6 || $rs['muchang']==7) && $rs['tgflag'] == 0 ){ //&& $rs['wx']==6
			
			$cwid=$rs['id'];
			$img1= "<img src='".IMAGE_SRC_URL."/bb/{$rs['cardimg']}' style='cursor:pointer;'>";
				if(($rs['muchang']==4 || $rs['muchang']==5 || $rs['muchang']==6 || $rs['muchang']==7) && $rs['chchengbb']>0){
					$chcbb1=$rs['chchengbb'];
					$sql="select cardimg from userbb where id={$rs['chchengbb']}";
					$chcbb = $_pm['mysql'] ->getOneRecord($sql);
					$img2= "<img src='".IMAGE_SRC_URL."/bb/{$chcbb['cardimg']}'  style='cursor:pointer;'>";
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
	if ($petslist == '' && ($rs['muchang']==6 || $rs['muchang']==5 || $rs['muchang']==4 || $rs['muchang']==3 || $rs['muchang']==7)) $petslist = '牧场里面还没有宝贝！';
}
//牧场结束

//玩家传承宠物





//结婚道具
if($curBagNum > 0){
	foreach($userBag as $v){
		if($v['merge'] == 1 && $v['sums'] > 0){
			$mybag .="<tr>
              <td width='50' align='center'><img src='../images/ui/bag/".$v['varyname'].".gif' width='23' height='23' /></td>
              <td width='100' align='left' id='t".$v['id']."' onmouseover='showTip(".$v['id'].",0,1,2);this.style.border=\"solid 1px #DFD496\";' onmouseout='window.parent.UnTip();this.style.border=0;' onclick='sel(this);copyWord(\"".$v['name']."\");pid=".$v['id'].";vary=".$v['vary'].";this.style.backgroundColor=\"#CCCC00\";' style='cursor:pointer;'>
			  ".$v['name']."
			  
			  </td>
              <td width='80' align='center'>".$v['sell']."</td>
              <td width='70' align='center'>".$v['sums']."</td>
            </tr>";
		}
	}
}
//道具结束


//载入时列表（已婚）
$sql="select merge,request,sj,request_merge,send from player_ext where uid={$_SESSION['id']}"; 
$merge=$_pm['mysql']->getOneRecord($sql);
if(is_array($merge) && $merge['merge']>0 && $merge['request_merge']==0 ){ //已建立婚姻
	$sql="select * from player_ext where uid={$merge['merge']} and merge={$_SESSION['id']}"; 
	$merge_lihun=$_pm['mysql']->getOneRecord($sql);//查找对方
	if(is_array($merge_lihun)){
		$sql="select nickname from player where id={$merge['merge']}"; //对方昵称
		$merge_name=$_pm['mysql']->getOneRecord($sql);
		if($merge_lihun['request']==1 && $merge['request']==0){ //对方请求离婚
			  $merge_list.="<tr onclick=\"sel(this);merge_id={$merge['merge']};xy=1;xy_qx();\" style='cursor:pointer;' onmouseover='this.style.border=\"solid 1px #DFD496\";'  onmouseout='this.style.border=0;' title='对方要求离婚，你可以接受也可拒绝对方的要求！'><td width='10'></td>
							  <td width='100' align='center' >".$merge_name['nickname']."</td>
								<td width='80' align='center'>有</td>
								 <td width='100' align='center'>对方请求离婚</td>
									</tr>";
		}elseif($merge_lihun['request']==0 && $merge['request']==1){ //你提出离婚请求
		
		
				$merge_list.="<tr onclick=\";sel(this);xy=2;xy_qx();\" style='cursor:pointer;' onmouseover='this.style.border=\"solid 1px #DFD496\";'  onmouseout='this.style.border=0;' title='如果对方拒绝或24小时内对方没有响应，你的婚姻将回复正常，扣除的水晶将返回，如果你坚持离婚也可选择强制离婚！'><td width='10'></td>
							  <td width='100' align='center' >".$merge_name['nickname']."</td>
								<td width='80' align='center'>有</td>
								 <td width='100' align='center'>你请求离婚</td>
									</tr>";
		}elseif($merge_lihun['request']==0 && $merge['request']==0){//婚姻正常
				$merge_list.="<tr onclick=\"sel(this);xy=7;xy_qx();\" style='cursor:pointer;' onmouseover='this.style.border=\"solid 1px #DFD496\";'  onmouseout='this.style.border=0;'><td width='10'></td>
							  <td width='100' align='center' >".$merge_name['nickname']."</td>
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
	
	
	
		if(is_array($merge_name1)){
			if($mergezc['merge']>0){
				$yes_no= $_pm['user']->getUserById($mergezc['merge']);
				$hunpei=$yes_no['nickname'];
			}else{
				$hunpei="无";
			}
			if($merge['request']==2 ){
				
				$merge_list.="<tr onclick=\"sel(this);merge_id=0;xy=6;xy_qx();\" style='cursor:pointer;' onmouseover='this.style.border=\"solid 1px #DFD496\";'  onmouseout='this.style.border=0;'><td width='10'></td>
									  <td width='100' align='center' >".$merge_name1['nickname']."</td>
										<td width='80' align='center'>".$hunpei."</td>
										 <td width='100' align='center'>对方已拒绝</td>
											</tr>";
			}elseif($merge['request']==0){
				if(!empty($merge['send']) && $mergezc['merge']>0){
				
					$merge_list.="<tr onclick=\"sel(this);xy=13;xy_qx();\" style='cursor:pointer;' onmouseover='this.style.border=\"solid 1px #DFD496\";'  onmouseout='this.style.border=0;' title='对方与选择其他玩家结成夫妻，请取回你的物品，选择其他的对象求婚！'><td width='10'></td>
									  <td width='100' align='center' >".$merge_name1['nickname']."</td>
										<td width='80' align='center'>".$hunpei."</td>
										 <td width='100' align='center'>对方与他人结婚</td>
											</tr>";
				}else{
					$merge_list.="<tr onclick=\"sel(this);xy=3;xy_qx();\" style='cursor:pointer;' onmouseover='this.style.border=\"solid 1px #DFD496\";'  onmouseout='this.style.border=0;' ><td width='10'></td>
									  <td width='100' align='center' >".$merge_name1['nickname']."</td>
										<td width='80' align='center'>无</td>
										 <td width='100' align='center'>你已向该玩家求婚</td>
											</tr>";
				}
				
			}
			
		}
	
}else{ //别人对我的求婚
	$sql="select uid,request from player_ext where request_merge={$_SESSION['id']} and merge=0 ";//request=2 已经拒绝 request=1离婚请求
	$request_merge=$_pm['mysql']->getRecords($sql);
	if(is_array($request_merge)){
		
		foreach($request_merge as $k=>$v){
				$sql="select nickname from player where id={$v['uid']}";
				$merge_name=$_pm['mysql']->getOneRecord($sql);
				if(is_array($merge_name)){
					if($v['request']==2 || $v['request']==3){ //为2时拒绝并通知，为3时不通知//merge_id={$v['uid']};
							$merge_list.="<tr onclick=\"sel(this);xy=5;xy_qx();\" style='cursor:pointer;' onmouseover='this.style.border=\"solid 1px #DFD496\";'  onmouseout='this.style.border=0;'>
							 <td width='10'></td>
							  <td width='100' align='center' >".$merge_name['nickname']."</td>
							  <td width='80' align='center'>无</td>
							  <td width='100' align='center' >你已拒绝</td>
							</tr>";

					}else{
						$merge_list.="<tr onclick=\"sel(this);merge_id={$v['uid']};xy=4;xy_qx();\" style='cursor:pointer;' onmouseover='this.style.border=\"solid 1px #DFD496\";'  onmouseout='this.style.border=0;'>
							 <td width='10'></td>
							  <td width='100' align='center' >".$merge_name['nickname']."</td>
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