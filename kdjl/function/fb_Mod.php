<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.08.26
*@Usage: Expore privew. --> 副本功能
*@Note: 
*/
require_once('../config/config.game.php');
require_once('../config/config.fuben.php');
define(MEM_FIGHTUSER_KEY, $_SESSION['id'] . 'fuser');
$_SESSION['exptype'.$_SESSION['id']] = "";
$_SESSION[$_SESSION['id'].'mapid'] = $_GET['mapid'];
secStart($_pm['mem']);
$user		= $_pm['user']->getUserById($_SESSION['id']);
$petsarr	= $_pm['user']->getUserPetById($_SESSION['id']);
$id = intval($_REQUEST['mapid']);//副本ID
if($id == "" || $id <= 0)
{
	header("location:/function/Expore_Mod.php");
	die("");
}
//查询副本地图的相关信息
$sql = "SELECT descs FROM map WHERE id = {$id}";
$map = $_pm['mysql'] -> getOneRecord($sql);
$sql = "SELECT gwid,inmap,lttime,srctime FROM fuben WHERE uid = {$_SESSION['id']}";
$fuben = $_pm['mysql'] -> getOneRecord($sql);

if(is_array($map))
{
	if($id == 11)
	{
		$img = "".IMAGE_SRC_URL."/fuben/fbdt02.jpg";
	}
	else if($id == 12)
	{
		$img = "".IMAGE_SRC_URL."/fuben/fbdt10.jpg";
	}
	else if($id == 13)
	{
		$img = "".IMAGE_SRC_URL."/fuben/fbdt11.jpg";
	}
	else if($id == 14)
	{
		$img = "".IMAGE_SRC_URL."/fuben/fbdt14.jpg";
	}
	else if($id == 50)
	{
		$img = "".IMAGE_SRC_URL."/fuben/fbdt50.jpg";
	}else{
		$img = "".IMAGE_SRC_URL."/fuben/fbdt".$id.".jpg";
	}
	
	$info = info($id,$fuben['gwid']);
	$introduce = $map['descs'];
}

$kk=0;
$selid=0; // default select pets!
if (is_array($petsarr))
{
	foreach ($petsarr as $k =>$rs) // Will filter in muchang pets for current user.
	{
		if ($rs['muchang'] != 0) continue;
		if ($kk == 0) {$sel = 100;$selid=$rs['id'];}
		else $sel = 50;
		if($rs['level']==0) $rs['level']=1;
		$pets[$kk++] = "<img src='".IMAGE_SRC_URL."/bb/{$rs['cardimg']}' onClick=\"startFatting({$rs['id']},this);\"  alt=\"{$rs['name']}\" style='cursor:pointer;filter:alpha(opacity={$sel});' id='i{$kk}'> ";
		if ($kk==3) break;
	}
}

$_pm['mem']->memClose();

//###########################
// @Load template.
//###########################
$tn = $_game['template'] . 'tpl_fb.html';
if (file_exists($tn))
{
	$tpl = file_get_contents($tn);
	if($tn) 
	{
		$src = array("#img#",
					 "#info#",
					"#one#",
					 "#two#",
					 "#three#",
					 "#bid#",
					 "#head1#",
					 "#head1info#",
					 "#_self#",
					 "#introduce#",
					 "#mapid#"
					);
		$des = array($img,
					 $info,
					 $pets[0],
					 $pets[1],
					 $pets[2],
					 $selid,
					 $user['headimg'].'.gif',
					 '昵称：'.$user['nickname'],
					 $user['nickname'],
					 $introduce,
					 $id
				);
	}

	$ret = str_replace($src, $des, $tpl);
}
// gzip echo. if maybe.
ob_start('ob_gzip');
echo $ret;
ob_end_flush();
//得到副本的相关信息
//$id 是传过来的副本的ID
//$gwid 是玩家当前的所打怪物的ID
function info($id,$gwid)
{
	$arr = array();
	global $_pm;
	global $fbinfo;
	foreach($fbinfo as $k => $v)
	{
		if($id == $v['id'])
		{
			$arr = $v;
		}
	};
	$numarr = explode(",",$arr['gwid']);
	$gwnum = count($numarr);
	$m = $gwid + 1;
	if(!in_array($m,$numarr))
	{
		$m = $numarr[0];
	}
	$sql = "SELECT * FROM fuben WHERE uid = {$_SESSION['id']} and inmap = $id";
	$fuben = $_pm['mysql'] -> getOneRecord($sql);
	$i = 0;
	foreach($numarr as $k => $v)
	{
		if($v == $fuben['gwid'])
		{
			$i++;
			$nb = $k + 1;
			$abc = $v;
		}
	}
	if(empty($abc))
	{
		$abc = $numarr[0];
	}
	if(!is_array($fuben))
	{
		$nb = 1;
	}
	if(empty($nb))
	{
		$nb = 1;
	}
	$j = $i + 1;
	$sql = "SELECT name FROM gpc WHERE id = $abc";
	$name = $_pm['mysql'] -> getOneRecord($sql);
	//倒计时
	$nowtime = time();
	if(is_array($fuben))
	{
		if(!empty($fuben['lttime']))
		{
			$ctime = $nowtime - $fuben['lttime'];
			$djtime = $fuben['srctime'] - $ctime;
			if($djtime < 0)
			{
				$djtime = "已开启";
			}
			else
			{
				$djtime = $djtime."秒";
			}
		}
		else
		{
			$djtime = "已开启";
		}
	}
	else
	{
		$djtime = "已开启";
	}
	$str = '<tr>
                    <td height="20">副本等级：'.$arr['lv'].'级</td>
                  </tr>
                  <tr>
                    <td height="20">副本开启倒计时：'.$djtime.'</td>
                  </tr>
                  <tr>
                    <td height="20">怪物总数：'.$gwnum.'</td>
                  </tr>
                  <tr>
                    <td height="20">当前进度：'.$nb.'</td>
                  </tr>
				<tr>
				  <td height="20">即将面对的宠物：'.$name['name'].'</td>
			 	 </tr>';
	return $str;
}
?>
