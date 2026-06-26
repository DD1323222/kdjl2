<?php
session_start();
require_once('../config/config.game.php');
//$_pm['mem']->set(array('k'=>'BAN_CARD_USER_'.$_SESSION['username'],'v'=>'0'));
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
	if( !$verify )
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
		die("非法传参数4");			
	}
}
foreach( $_GET as $key => $val )
{
	if($key == 'select_map')
	{
		if( !get_para_verify($val) )
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
			die("非法传参数3");
		}
	}
}
$sql = " SELECT * FROM T_Card_Type ";
$result_card_select = $_pm['mysql']->getRecords($sql);
$sql = " SELECT * FROM T_Card_to_Title order by id ";
$result = $_pm['mysql']->getRecords($sql);
$title_num = 0;
$sql = " SELECT F_Has_Title FROM player_ext WHERE uid = '".$_SESSION['id']."'";
$result_user_has_title = $_pm['mysql']->getOneRecord($sql);
if( !empty($result_user_has_title['F_Has_Title']) )
{
	$user_has_title = explode(',',$result_user_has_title['F_Has_Title']);
}
?>
<?php
if($_GET['view'] != 'list' && $_GET['view'] != 'title_use'  && !$_GET['cmd'] && !$_GET['prize'] )
{
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=gbk" />
<title>无标题文档</title>
<style type="text/css">
body,div,dl,dt,dd,ul,ol,li,h1,h2,h3,h4,h5,h6,pre,form,fieldset,input,textarea,p,blockquote,th,td,em {padding:0; margin:0; outline:none} 
.con{display:none;}
.task{width:788px;height:319px;background:#f2ebc5; color:#B06A01; font-size:12px; background:url(../images/ui/card/bg.jpg);}
.task_right{width:763px; height:319px;float:left; padding-left:25px;}
.task_nav{width:640px;height:29px;}
.task_nav li{float:left;height:29px;line-height:29px;}
.task_nav li a{width:90px;display:block;}
.a01{background:url(../images/ui/card/mkz.jpg) no-repeat;height:29px;}
.task_nav li.on .a01{background:url(../images/ui/card/mkz.jpg) no-repeat 0 -29px;height:29px;}
.a02{background:url(../images/ui/card/mkz.jpg) no-repeat -90px 0;height:29px;}
.task_nav li.on .a02{background:url(../images/ui/card/mkz.jpg) no-repeat -90px -29px;height:29px;}
.clearfix:after {content: "."; display: block; height: 0; clear: both; visibility: hidden; }  
.clearfix{zoom:1;}  
ol,ul {list-style:none} 


.dt_task{width:763px;height:auto; overflow:auto;}
.dt_map{width:250px; height:24px; float:left; padding:5px 0 0 20px;}
.dt_box{width:720px; height:200px; float:left; border:#d1b269 1px solid; background:#fffceb; padding:10px;}
.dt_dd{width:720px; height:24px; float:left; padding:5px 0 0 20px; line-height:24px;}

.dt_box02{width:730px; height:auto; float:left; border:#d1b269 1px solid; background:#fffceb; padding:5px 0 0 10px; margin-top:10px;}
.dt_title{width:720px; height:28px; float:left; background:url(../images/ui/card/title_bg.jpg);}
.dt_content{width:730px; height:200px; float:left;overflow-x:hidden;overflow-y:scroll;background:#fff;scrollbar-face-color:#E1D395;scrollbar-highlight-color:#ffffff;scrollbar-3dlight-color:#E1D395;scrollbar-shadow-color:#ffffff;scrollbar-darkshadow-color:#F3EDC9;scrollbar-track-color:#F3EDC9;scrollbar-arrow-color:#ffffff; color:#BF7D1A; border-top-width: 1px;
	border-right-width: 0px;
	border-bottom-width: 0px;
	border-left-width: 0px;
	border-top-style: solid;
	border-right-style: none;
	border-bottom-style: none;
	border-left-style: none;
	border-top-color: #D9BD7A;}

</style>
<script type="text/javascript">
function setTab(name,cursel,n)
{
	for(i=1;i<=n;i++)
	{
	  var menu=document.getElementById(name+i);
	  var con=document.getElementById("con_"+name+"_"+i);
	  menu.className=i==cursel?"on":"";
	  con.style.display=i==cursel?"block":"none";
	}
}
function ChangeMap()
{
	var select_map = document.getElementById('select_map').value;
	document.getElementById('select_map').value=select_map;
	Mod_iframe.location="card_Mod_Request.php?view=list&select_map="+select_map;
}
function getPrize()
{
	Mod_iframe.location="card_Mod_Request.php?cmd=getprize";
}
function get_radio_value(field)
{ 
	var has = 0;
	if( field.value )
	{	
		window.location="card_Mod.php?usetitle="+field.value; 
	}
    if (field && field.length)
	{ 
        for (var i = 0; i < field.length; i++)
		{ 
            if (field[i].checked)
			{  
				has = 1;
                window.location="card_Mod.php?usetitle="+field[i].value; 
            } 
        } 
		if( has == 0 )
		{
			alert("请先选择一个获得的称号哦！");
			return false;
		}
		
    }
	else
	{ 
        return;     
    }
} 
function unuse()
{
	window.location="card_Mod.php?usetitle=unuse_title"; 
}
</script>
</head>

<body>
<div class="task">
	<div class="task_right">
    <ul class="task_nav">
    <li id="tab1" onclick="setTab('tab',1,2)" class="on"><a class="a01" href="javascript:void(0)"></a></li>
    <li id="tab2" onclick="setTab('tab',2,2)"><a class="a02" href="javascript:void(0)"></a></li>
    </ul>
    	<div class="dt_task" id="con_tab_1" >
       		<div class="dt_map">
       		  <table width="250" border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="63" height="24">选择地图</td>
                  <td width="187"><label>
					<select id="select_map" onchange="ChangeMap()">'+'<?php foreach( $result_card_select as $info ){?><option value="<?php echo $info['F_Class_Name']; ?>"><?php echo $info['F_Class_Name']; ?></option><?php }?>
					</select>

                  </label></td>
                </tr>
              </table>
       		</div>
			<div class="dt_box">
			  <iframe name="Mod_iframe" src="card_Mod_Request.php?view=list&select_map=新手基地" width="720px" height="200px" title="卡片系统"></iframe>
			</div>
			<div class="dt_dd">
			  <table width="600" border="0" cellspacing="0" cellpadding="0">
                <tr>

	
		<td width="81"><img src="../images/ui/card/cion01.jpg" width="69" height="17" onclick="getPrize() "/></td>
		<td width="519">满足一定的条件就能得到丰厚的奖励哦！</td>
                </tr>
              </table>
			</div>
        </div>
 
        <div class="dt_task con" id="con_tab_2" >
        	<div class="dt_box02">
				<div class="dt_title">
				  <table width="730" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td width="5%" height="24">选择框</td>
                      <td width="5%">图标</td>
                      <td width="15%">名称</td>
                      <td width="25%">获得方法</td>
					  <td width="40%">称号效果</td>
                    </tr>
                  </table>
				</div>
				<div class="dt_content">
				  <table width="auto" border="0" cellspacing="0" cellpadding="0">
<form name="myForm">
	<?php
	$arr_title_name = array('#F_add_hp#','#F_add_mp#','#F_add_ac#','#F_add_mc#','#F_add_hits#','#F_add_miss#','#F_add_speed#','#F_add_hprate#','#F_add_mprate#','#F_add_acrate#','#F_add_mcrate#','#F_add_hitsrate#','#F_add_missrate#','#F_add_speedrate#','#F_dxsh#','#F_hitshp#','#F_hitsmp#','#F_shjs#','#F_sdmp#','#F_szmp#','#F_addmoney#','#F_time#');
	$arr_others = array('F_dxsh','F_hitshp','F_hitsmp','F_shjs','F_sdmp','F_szmp','F_addmoney','F_time');
	$arr_title_chinaesename = array('HP','MP','攻击','防御','命中','闪避','速度','HP百分比','MP百分比','攻击百分比','防御百分比','命中百分比','闪避百分比','速度百分比','伤害抵消','转化为自身HP','转化为自身MP','对敌人造成的伤害增加','以MP抵消','转化为MP','战斗胜利获得金币增加','自动战斗间隔时间减少');
	foreach( $result as $info )
	{
		$deal = $info;
		$introduction = "";
		unset($deal['id']);
		unset($deal['F_title_name']);
		unset($deal['F_title_Chinese']);
		unset($deal['F_title_img']);
		unset($deal['F_title_must_card']);
		unset($deal['F_title_get_methods']);
		foreach( $deal as $key => $val )
		{
			if( $val > 0 )
			{
				if( strstr($key,'rate') )
				{
					$introduction .= "增加#".$key."#:".$val."% ";
				}
				elseif( strstr($key,'F_add_') )
				{
					$introduction .= "增加#".$key."#:".$val." ";
				}
				else
				{
					switch ($key)
					{
						case 'F_dxsh' :
						{
							$introduction .= '#'.$key."#:".$val."% ";
							break;
						}
						case  'F_hitsmp' :
						{
							$introduction .= '命中吸取伤害的'.$val.'%#'.$key."# ";
							break;
						}
						case  'F_hitshp' :
						{
							$introduction .= '命中吸取伤害的'.$val.'%#'.$key."# ";
							break;
						}
						case  'F_shjs' :
						{
							$introduction .= '#'.$key."#:".$val."% ";
							break;
						}
						case 'F_sdmp' :
						{
							$introduction .= '将受到伤害的'.$val."%#".$key."# ";
							break;
						}
						case 'F_szmp' :
						{
							$introduction .= '将受到伤害的'.$val."%#".$key."# ";
							break;
						}
						case 'F_addmoney' :
						{
							$introduction .=  '#'.$key."#:".$val." ";
							break;
						}
						case 'F_time' :
						{
							$introduction .=  '#'.$key."#:".$val."秒 ";
							break;
						}
					}
				}
			}
			elseif( $val < 0 )
			{
				$val = abs($val);
				if( strstr($key,'rate') )
				{
					$introduction .= "减少#".$key."#:".$val."% ";
				}
				elseif( strstr($key,'F_add_') )
				{
					$introduction .= "减少#".$key."#:".$val." ";
				}
				
			}
		}
		if( $introduction == '' )
		{
			$introduction = "称号效果稍后开放，敬请期待";
		}
		$introduction = str_replace($arr_title_name,$arr_title_chinaesename,$introduction);
		if( empty($result_user_has_title['F_Has_Title']) )
		{
			$echo_td_title = '<tr id ="TR_ID'.$info['F_title_name'].'">
		<td width="5%"></td>
		<td width="5%"><img src="../images/Achievement_title/'.$info['F_title_img'].'"</img></td>
		<td width="15%" style="color:#999999";>'.$info['F_title_Chinese'].'</td>
		<td width="25%" style="color:#999999";>'.$info['F_title_get_methods'].'</td>
		<td width="40%" style="color:#999999";>'.$introduction.'</td>
	</tr>';
			echo $echo_td_title;
		}
		else
		{
			$title_num = 1;
			$echo_td_title = '<tr id ="TR_ID'.$info['F_title_name'].'">
		<td width="5%"></td>
		<td width="5%"><img src="../images/Achievement_title/'.$info['F_title_img'].'"</img></td>
		<td width="15%" style="color:#999999";>'.$info['F_title_Chinese'].'</td>
		<td width="25%" style="color:#999999";>'.$info['F_title_get_methods'].'</td>
		<td width="40%" style="color:#999999";>'.$introduction.'</td>
	</tr>';
			if( in_array($info['id'],$user_has_title) )
			{
				$sql = " SELECT now_Achievement_title FROM player_ext WHERE uid = '".$_SESSION['id']."'";
				$result_now_title = $_pm['mysql']->getOneRecord($sql);
				$echo_td_title = '<tr id ="TR_ID'.$info['F_title_name'].'">
		<td width="5%"><input type="radio" name="title_use" value="'.$info['F_title_name'].'" ></td>
		<td width="5%"><img src="../images/Achievement_title/'.$info['F_title_img'].'"</img></td>
		<td width="15%">'.$info['F_title_Chinese'].'</td>
		<td width="25%">'.$info['F_title_get_methods'].'</td>
		<td width="40%">'.$introduction.'</td>
	</tr>';
				if ( $info['F_title_name'] == $result_now_title['now_Achievement_title'] )
				{
					$echo_td_title = '<tr id ="TR_ID'.$info['F_title_name'].'" style="background:green">
		<td width="5%"><input type="radio" name="title_use" value="'.$info['F_title_name'].'"   checked = "true" ></td>
		<td width="5%"><img src="../images/Achievement_title/'.$info['F_title_img'].'"</img></td>
		<td width="15%">'.$info['F_title_Chinese'].'</td>
		<td width="25%">'.$info['F_title_get_methods'].'</td>
		<td width="40%">'.$introduction.'</td>
	</tr>';
				}			
			}
			echo $echo_td_title;
		}
		?>
	<tr>
		<td colspan="5"><img src="../images/ui/card/line_bg.jpg" width="706" height="1" /></td>
	</tr>
	<?php
	}
	if ( $_GET['usetitle'] )
	{
		$sql = " SELECT id FROM T_Card_to_Title WHERE  F_title_name = '".$_GET['usetitle']."'"; 
		$result_get_para = $_pm['mysql']->getOneRecord($sql);
		if( !in_array($result_get_para['id'],$user_has_title) || !isset($user_has_title) )
		{
			if ($_GET['usetitle'] != 'unuse_title' )
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
				echo "恶意传参次数:".$bad_transport_time.",超过10次会被永久封号，请注意！！！<br>";
				die("非法传参数2");
			}
		}
		if ($_GET['usetitle'] == 'unuse_title' && isset($result_now_title['now_Achievement_title'])  )
		{
			if( $_SESSION['now_Achievement_title'] != "" )
			{
			?>
				<script language="javascript">
				function unset_title()
				{
					document.getElementById('TR_ID<?php echo $result_now_title['now_Achievement_title']; ?>').style.background = "#fffceb";
					document.getElementById('TR_ID<?php echo $result_now_title['now_Achievement_title']; ?>').firstChild.firstChild.checked = false;
					alert("恭喜您！取消称号成功！刷新后生效！");
				}
				</script>
			<?php
				
				$_SESSION['now_Achievement_title'] = "";
				$sql = " UPDATE player_ext SET now_Achievement_title = '' WHERE uid ='".$_SESSION['id']."'";
				$result_unuse_title = $_pm['mysql']->query($sql);
				if( $result_unuse_title )
				{
				?>
					<script language="javascript">
					unset_title();
				</script>
				<?php
					$petsAll	= $_pm['user']->getUserPetById($_SESSION['id']);
					foreach ( $petsAll as $info )
					{
						getzbAttrib($info['id']);
						$_pm['mem']->set(array("k"=>"User_bb_equip_changed_".$info['id'].'_'.$_SESSION['id'],"v"=>1));
					}
				}
				else
				{
				?>
					<script language="javascript">alert("取消称号失败！");</script>
				<?php
					die();
				}
			}
			else
			{
				if($_SESSION['now_Achievement_title'] == "")
				{
				?>
					<script language="javascript">
					alert("你都没称号还取消什么哦！");
					</script>
				<?php
				}
			}
		}
		if ($_GET['usetitle'] != 'unuse_title')
		{
			$use_title_now = $_GET['usetitle'];
			$sql = " UPDATE player_ext SET now_Achievement_title = '".$use_title_now."' WHERE uid ='".$_SESSION['id']."'";
			unset($_GET['usetitle']);
			$result_change_title = $_pm['mysql']->query($sql);
			if( $result_change_title && $result_now_title['now_Achievement_title'] && !empty($result_now_title['now_Achievement_title']) && $use_title_now )
			{
			?>
			<script language="javascript">
					function Change_title()
					{
						document.getElementById('TR_ID<?php echo $result_now_title['now_Achievement_title']; ?>').style.background = "#fffceb";
						document.getElementById('TR_ID<?php echo $use_title_now; ?>').style.background = "green";
						document.getElementById('TR_ID<?php echo $use_title_now; ?>').firstChild.firstChild.checked = true;
						alert("恭喜您！更换称号成功！刷新后生效！");
					}
					Change_title();
			</script>
			<?php
				$_SESSION['now_Achievement_title'] = $use_title_now;
				$sql = " SELECT F_title_Chinese FROM T_Card_to_Title WHERE F_title_name =  '".$use_title_now."'";
				$result_title_chinese = $_pm['mysql']->getOneRecord($sql);
				$_SESSION['now_Achievement_title_chinese'] = $result_title_chinese['F_title_Chinese'];
				$petsAll	= $_pm['user']->getUserPetById($_SESSION['id']);
				foreach ( $petsAll as $info )
				{
					getzbAttrib($info['id']);
					$_pm['mem']->set(array("k"=>"User_bb_equip_changed_".$info['id'].'_'.$_SESSION['id'],"v"=>1));
				}
			}
			else
			{
				if ( $result_change_title && empty($result_now_title['now_Achievement_title'])  && $use_title_now )
				{
				?>
					<script language="javascript">
					function first_use_title()
					{
						document.getElementById('TR_ID<?php echo $use_title_now; ?>').style.background = "green";
						document.getElementById('TR_ID<?php echo $use_title_now; ?>').firstChild.firstChild.checked = true;
						alert("恭喜您！使用称号成功！刷新后生效！");
					}
					first_use_title();
					</script>
				<?php
					$_SESSION['now_Achievement_title'] = $use_title_now;
					$sql = " SELECT F_title_Chinese FROM T_Card_to_Title WHERE F_title_name =  '".$use_title_now."'";
					$result_title_chinese = $_pm['mysql']->getOneRecord($sql);
					$_SESSION['now_Achievement_title_chinese'] = $result_title_chinese['F_title_Chinese'];
					
					$petsAll	= $_pm['user']->getUserPetById($_SESSION['id']);
					foreach ( $petsAll as $info )
					{
						getzbAttrib($info['id']);
						$_pm['mem']->set(array("k"=>"User_bb_equip_changed_".$info['id'].'_'.$_SESSION['id'],"v"=>1));
					}
				}
			}
		}
		unset($result_change_title);
	}

?>
</form>
</table>
				</div>
			</div>
<?php
}

if($title_num > 0)
{
?>
<img src="../images/ui/card/cion02.jpg" onclick="get_radio_value(myForm.title_use);"/>
<img src="../images/ui/card/cion03.jpg"  onclick="unuse();"/>
        </div>
	</div>
</div>
</body>
</html>
<?php
}
?>
