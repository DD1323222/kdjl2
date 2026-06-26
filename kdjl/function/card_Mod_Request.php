<?php
session_start();
require_once('../config/config.game.php');
$ban_user=unserialize($_pm['mem']->get('BAN_CARD_USER_'.$_SESSION['username']));
if( $ban_user  >= 10  )
{
	die("卡片系统不对恶意用户开放,请联系管理员！！！");
}
require_once('get_para_verify.php');
foreach( $_GET as $key => $val )
{	
	$verify = true;
	switch ($key)
	{
		case 'select_map' :
		{
			$verify = get_para_verify_map($val);
			break;
		}
		case 'usetitle' :
		{
			$verify = get_para_verify_title($val);
			break;
		}
		case 'prize' :
		{
			$verify = get_para_verify_prize($val);
			break;
		}
		default :
		{
			break;
		}
	}
	/*if( !$verify )
	{
		echo "你的帐号<font color = 'blue'>".$_SESSION['username']."</font>";
		$ban_user=unserialize($_pm['mem']->get('BAN_CARD_USER_'.$_SESSION['username']));
		if( $ban_user )
		{
			$bad_num =$ban_user + 1;
		}
		else
		{
			$bad_num  = 1;
		}
		$_pm['mem']->set(array('k'=>'BAN_CARD_USER_'.$_SESSION['username'],'v'=>$bad_num));
		$bad_transport_time = unserialize($_pm['mem']->get('BAN_CARD_USER_'.$_SESSION['username']));
		echo "恶意传参次数:".$bad_transport_time.",超过10次会自动被永久封号，请注意！！！<br>";
		die("非法传参数1");			
	}*/
}
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=gbk" />
<title>无标题文档</title>
<style type="text/css">
body,div,dl,dt,dd,ul,ol,li,h1,h2,h3,h4,h5,h6,pre,form,fieldset,input,textarea,p,blockquote,th,td,em {padding:0; margin:0; outline:none} 
body{overflow-x:hidden;overflow-y:scroll;background:#fff;scrollbar-face-color:#E1D395;scrollbar-highlight-color:#ffffff;scrollbar-3dlight-color:#E1D395;scrollbar-shadow-color:#ffffff;scrollbar-darkshadow-color:#F3EDC9;scrollbar-track-color:#F3EDC9;scrollbar-arrow-color:#ffffff; color:#BF7D1A; border-top-width: 1px;
	border-right-width: 0px;
	border-bottom-width: 0px;
	border-left-width: 0px;
	border-top-style: solid;
	border-right-style: none;
	border-bottom-style: none;
	border-left-style: none;
	border-top-color: #D9BD7A;}
.dt_box{width:700px; height:auto; float:left; border:#d1b269 1px solid; background:#fffceb; padding:10px;font-size:12px;} 
.task{width:699px;height:auto;background:#f2ebc5; color:#B06A01; font-size:12px;}
.task_left{width:163px; height:auto; float:left;}
.task_right{width:625px; height:auto; float:left;}
.task_box{width:700px; float:left; border:#d1b269 1px solid; background:#fffceb; padding:10px; line-height:24px;}
table{font-size:12px;}

</style>
</head>
<?php
if( $_GET['select_map'] )
{
?>
<body bgcolor="#fffceb">
	<div class="dt_box">
	<table width="auto" border="1" cellspacing="0" cellpadding="0" bordercolor="#fffceb">
	<?php
	$select_map = $_GET['select_map'];
	$sql = " SELECT F_Had_Card FROM T_Card_Type WHERE F_Class_Name = '".$select_map."'";
	unset($result);
	$result = $_pm['mysql']->getOneRecord($sql);
	$class_had_card_arr = explode(',',$result['F_Had_Card']);
	$sql = " SELECT F_User_Card_Info FROM player_ext WHERE uid = '".$_SESSION['id']."'";
	$result_had_card = $_pm['mysql']->getOneRecord($sql);
	$result_had_card_info = explode(',',$result_had_card['F_User_Card_Info']);
	for($i = 0; $i < count($result_had_card_info); $i++ )
	{
		$arr_result_info = explode(':',$result_had_card_info[$i]);
		$result_had_card_name[$i] = $arr_result_info[0];
		$result_had_card_num[$i] = $arr_result_info[1];
	}
	$result_had_card_info = array_combine($result_had_card_name,$result_had_card_num);
	/*进行一次排序*/
	foreach($result_had_card_info as $key => $val )
	{
		$sql = " SELECT merge FROM props WHERE name =  '".$key."' AND varyname = 24" ;
		$result_level = $_pm['mysql']->getOneRecord($sql);
		$result_had_card_info_new[$result_level['merge'].$key] = $val;
	}
	krsort($result_had_card_info_new);
	unset($result_had_card_info);
	foreach($result_had_card_info_new as $key => $val)
	{
		$newkey = substr($key,2);
		$result_had_card_info[$newkey] = $val;
	}
	
	?>
	<tr>
	<?php
	$num = 0;
	for($i = 0; $i < count($class_had_card_arr); $i++ )
	{	
		$num++;
		$echo_td = '<td align="center" width="110"><img src="../images/ui/card/paper.jpg" width="63" height="94" /><br>'.$class_had_card_arr[$i].'<br>0张</td>';
		$sql = " SELECT img FROM props WHERE name ='".trim($class_had_card_arr[$i])."'";
		unset($result);
		$result = $_pm['mysql']->getOneRecord($sql);
		if( array_key_exists($class_had_card_arr[$i],$result_had_card_info)	)
		{	
				$echo_td = '<td align="center" width="110"><img src="../images/card_Mod/'.$result['img'].'" width="63" height="94" />'."<br>".$class_had_card_arr[$i]."<br>".$result_had_card_info[$class_had_card_arr[$i]].'张</td>';
		}
		if ($num > 6)	//自动换行
		{
			echo '</tr><tr>';
			$num = 1;
		}
		echo $echo_td;
	}
?>
</tr>
</table>
</div>
</body>
</html>
<?php 
}
if ( $_GET['cmd'] == 'getprize' || $_GET['prize'] )
{
?>
	<body bgcolor="#fffceb">
	<div class="task">
	<div class="task_right">
		<div class="task_box">
	<script language="javascript">
	function getprize_thing(para)
	{
		if(confirm("请保证背包空格个数大于得到奖励物品的个数，否则会出现物品丢失等不可恢复的错误!"))
		{
			window.location="card_Mod_Request.php?prize="+para;
		} 	
	}
	</script>
<?php
	if ( empty($_GET['prize']) )
	{
?>
	<table width="auto" border="0" cellpadding="0" cellspacing="1" bgcolor="#B98531">
       <tr>
          <td width="300" align="center" bgcolor="#FFFCEB">任务名称</td>
          <td width="500" align="center" bgcolor="#FFFCEB">任务需求</td>
          <td width="300" align="center" bgcolor="#FFFCEB">任务奖励</td>
          <td width="70" align="center" bgcolor="#FFFCEB">操作</td>
       </tr>
<?php
	}
?>
<?php
function getechoneed($arr)
{
	for($j = 0; $j < count($arr); $j++ )
	{
		$arr_name_and_num = explode(':',$arr[$j]);
		$text .= '需要'.$arr_name_and_num[0].'卡片'.$arr_name_and_num[1].'个<br>';
	}
	return $text;
}
function getechoprize($arr)
{
	global $_pm;
	for($j = 0 ; $j < count($arr); $j++ )
	{
		$arr_info = explode(':',$arr[$j]);
		$sql = " SELECT name FROM props WHERE id = '".$arr_info[0]."'";
		$result_props_name = $_pm['mysql']->getOneRecord($sql);
		$text .= $result_props_name['name'].$arr_info[1].'个<br>';
	}
	return $text;
}

	$sql = " SELECT * FROM T_Card_Prize ";
	$result = $_pm['mysql']->getRecords($sql);
	$sql = "SELECT F_User_Card_Info FROM player_ext WHERE uid = '".$_SESSION['id']."'";
	$result_card_info = $_pm['mysql']->getOneRecord($sql);
	$prize_is_or_no = array();
	if( empty($result_card_info['F_User_Card_Info']) )
	{
		foreach( $result as $info )
		{
			$arr = explode(',',$info['F_Satisfy_condition']);
			$text_need = getechoneed($arr);
			unset($text);
			$arr = explode(',',$info['F_Prize']);
			$text_prize = getechoprize($arr);
			unset($text);
			$echo = '<tr>'.'<td align="center" bgcolor="#FFFCEB">'.$info['F_Prize_title']."</td>".'<td align="center" bgcolor="#FFFCEB">'.$text_need.'</td>'.'<td align="center" bgcolor="#FFFCEB">'.$text_prize.'</td>'.'<td align="center" bgcolor="#FFFCEB" bgcolor="#FFFCEB"><img src="../images/ui/card/noget.jpg" width="44" height="17" /></td>'.'</tr>';
			if( empty($_GET['prize']) )
			{
				echo $echo;
			}
		}
	}
	else
	{
		$user_card_info = explode(',',$result_card_info['F_User_Card_Info']);
		for($i = 0; $i < count($user_card_info); $i++ )
		{
			$user_card_one_type = explode(':',$user_card_info[$i]);
			$user_card_name[$i] =  $user_card_one_type[0];
			$user_card_num[$i] =  $user_card_one_type[1];
		}
		$user_card_info_arr = array_combine($user_card_name,$user_card_num);
		foreach( $result as $info )
		{
			$db_info = explode(',',$info['F_Satisfy_condition']);
			for( $i = 0; $i < count($db_info); $i++ )
			{
				$info_need = explode(':',$db_info[$i]);
				$info_need_name[$i] = $info_need[0];
				$info_need_num[$i] = $info_need[1];
			}
			$info_need_arr = array_combine($info_need_name,$info_need_num);
			$prize = array();
			foreach( $info_need_arr as $key => $val )
			{
				foreach($user_card_info_arr as $card_name => $card_num )
				{
					if($key == $card_name && $val <= $card_num )
					{
						array_push($prize,1);
					}
				}
			}
			if( count($prize) == count($info_need_arr) )
			{
				$deal = $info['id'];	//有完成任务,待验证是否领取过
				$sql = " SELECT F_has_get_prize FROM player_ext WHERE uid = '".$_SESSION['id']."'";
				$result_has_get_prize = $_pm['mysql']->getOneRecord($sql);
				if( isset($result_has_get_prize['F_has_get_prize']) )	//领过
				{
					$has_get_info = explode(',',$result_has_get_prize['F_has_get_prize']);
					if( in_array($deal,$has_get_info) )	//领过
					{
						$deal = "got";
					}
					else
					{
						array_push($prize_is_or_no,$info['id']);
					}
				}
			}

			$arr = explode(',',$info['F_Satisfy_condition']);
			$text_need = getechoneed($arr);
			unset($text);
			$arr = explode(',',$info['F_Prize']);
			$text_prize = getechoprize($arr);
			unset($text);
			$echo = '<tr>'.'<td align="center" bgcolor="#FFFCEB">'.$info['F_Prize_title']."</td>".'<td align="center" bgcolor="#FFFCEB">'.$text_need.'</td>'.'<td align="center" bgcolor="#FFFCEB">'.$text_prize.'</td>';
			if( isset($deal) )
			{
				if( $deal != 'got' )
				{
					$echo .= '<td align="center" bgcolor="#FFFCEB" bgcolor="#FFFCEB"><img src="../images/ui/card/award.jpg" width="44" height="17"  onClick="getprize_thing('."'".$deal."'".')" /></td>'.'</tr>';
				}
				else
				{
					$echo .= '<td align="center" bgcolor="#FFFCEB" bgcolor="#FFFCEB"><img src="../images/ui/card/hasget.jpg" width="44" height="17" /></td>'.'</tr>';
				}
			}
			else
			{
					$echo .= '<td align="center" bgcolor="#FFFCEB" bgcolor="#FFFCEB"><img src="../images/ui/card/noget.jpg" width="44" height="17" /></td>'.'</tr>';
			}
			if( !isset($_GET['prize']) )
			{
				echo $echo;
			}
			unset($deal);
			unset($prize);
			unset($echo);
			unset($info_need_arr);
			unset($info_need_name);
			unset($info_need_num);
		}
		if( $_GET['prize'] )
		{	
			require_once('../sec/dblock_fun.php');
			$a = getLock($_SESSION['id']);
			if(!is_array($a))
			{
				realseLock();
				unLockItem($id);
				die('服务器繁忙，请稍候再试！');
			}
			if ( in_array($_GET['prize'],$prize_is_or_no) )
			{
				//有对应的奖励
				$sql = " SELECT F_Prize FROM T_Card_Prize WHERE id = '".$_GET['prize']."'";
				$result_prize_info = $_pm['mysql']->getOneRecord($sql);
				$prize_info = $result_prize_info['F_Prize'];
		

		
				$sql = " SELECT F_has_get_prize FROM player_ext WHERE uid = '".$_SESSION['id']."'";	//已经领取奖励
				$result_has_get_prize = $_pm['mysql']->getOneRecord($sql);

				if ( empty($result_has_get_prize['F_has_get_prize']) )	//没有领取过奖励
				{
					//发奖
					$user		= $_pm['user']->getUserById($_SESSION['id']);
					$bag		= $_pm['user']->getUserBagById($_SESSION['id']);			
					$card_task = new task;
					$arr_prize_thing = explode(',',$prize_info);
					for( $i = 0; $i < count($arr_prize_thing); $i++ )
					{
						$info = explode(':',$arr_prize_thing[$i]);
						$idlist = $info[0];
						$num = $info[1];
						$result_of_get_prize = $card_task->saveGetPropsMore($idlist,$num);
					}
					echo "领取奖励成功";
					$set = $_GET['prize'];
					$sql = " UPDATE player_ext SET F_has_get_prize = '".$set."' WHERE uid = '".$_SESSION['id']."'";
					$_pm['mysql']->query($sql);
					realseLock();
				}
				else
				{
					$result_has_get_prize_arr = explode(',',$result_has_get_prize['F_has_get_prize']);
					if(in_array($_GET['prize'],$result_has_get_prize_arr))
					{
						echo "已经领取过奖励";
						die();
					}
					else
					{
						//有对应的奖励
						$sql = " SELECT F_Prize FROM T_Card_Prize WHERE id = '".$_GET['prize']."'";
						$result_prize_info = $_pm['mysql']->getOneRecord($sql);
						$prize_info = $result_prize_info['F_Prize'];
						//发奖
						$user		= $_pm['user']->getUserById($_SESSION['id']);
						$bag		= $_pm['user']->getUserBagById($_SESSION['id']);			
						$card_task = new task;
						$arr_prize_thing = explode(',',$prize_info);
						for( $i = 0; $i < count($arr_prize_thing); $i++ )
						{
							$info = explode(':',$arr_prize_thing[$i]);
							$idlist = $info[0];
							$num = $info[1];
							$result_of_get_prize = $card_task->saveGetPropsMore($idlist,$num);
						}
						echo "领取奖励成功";
						$set = $result_has_get_prize['F_has_get_prize'] .= ",".$_GET['prize'];
						$sql = " UPDATE player_ext SET F_has_get_prize = '".$set."' WHERE uid = '".$_SESSION['id']."'";
						//echo $sql;
						$_pm['mysql']->query($sql);
						realseLock();
					}
				}
			}
			else
			{
				echo "你的帐号<font color = 'blue'>".$_SESSION['username']."</font>";
				$ban_user=unserialize($_pm['mem']->get('BAN_CARD_USER_'.$_SESSION['username']));
				if( $ban_user )
				{
					$bad_num =$ban_user + 1;
				}
				else
				{
					$bad_num  = 1;
				}
				$_pm['mem']->set(array('k'=>'BAN_CARD_USER_'.$_SESSION['username'],'v'=>$bad_num));
				$bad_transport_time = unserialize($_pm['mem']->get('BAN_CARD_USER_'.$_SESSION['username']));
				echo "恶意传参次数:".$bad_transport_time.",超过10次会自动被永久封号，请注意！！！<br>";
				die("非法传参数2");
			}
		}
	}
	?>
			</table>
		</div>
	</div>
</div>
</body>
</html>
<?php		
}
?>

	
