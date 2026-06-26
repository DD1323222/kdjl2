<?php
require_once('../kernel/socketmsg.v1.php');
require_once('../socketChat/config.chat.php');
$s=new socketmsg();
if(isset($_POST['type']))
{
	$best_arr = explode(',',$_POST['best']);
	$thing = explode('-',$best_arr[1]);
	for($i = 0; $i<count($thing);$i++)
	{
		$mid_arr = explode('|',$thing[$i]);
		$thing_info_best .= '<a>'.$mid_arr[1]."</a>,";
	}
	$thing_info_best = substr($thing_info_best,0,-1);
	$one_arr = explode(',',$_POST['one']);
	$thing = explode('-',$one_arr[1]);
	for($i = 0; $i<count($thing);$i++)
	{
		$mid_arr = explode('|',$thing[$i]);
		$thing_info_one .= '<a>'.$mid_arr[1]."</a>,";
	}
	$thing_info_one = substr($thing_info_one,0,-1);	
	$two_arr = explode(',',$_POST['two']);
	$thing = explode('-',$two_arr[1]);
	for($i = 0; $i<count($thing);$i++)
	{
		$mid_arr = explode('|',$thing[$i]);
		$thing_info_two .= '<a>'.$mid_arr[1]."</a>,";
	}
	$thing_info_two = substr($thing_info_two,0,-1);
	$gg = 1;
	switch($_POST['type'])
	{
		case 1 :
		{
			$word = 'an|目前剩余特等奖<font color = "black">'.$best_arr[0].'</font>张,奖励物品:'.$thing_info_best.',一等奖<font color = "black">'.$one_arr[0].'</font>张,奖励物品:'.$thing_info_one.',剩余刮刮卡数量:<font color = "black">'.$_POST['null'].'/'.$_POST['all'].'</font>';
			break;
		}
		case 2:
		{
			$word = 'an|新一轮抽奖活动开启,目前剩余特等奖<font color = "black">'.$best_arr[0].'</font>张,奖励物品:'.$thing_info_best.',一等奖<font color = "black">'.$one_arr[0].'</font>张,奖励物品:'.$thing_info_one.',剩余刮刮卡数量:<font color = "black">'.$_POST['null'].'/'.$_POST['all'].'</font>';
			break;
		}
		case 3:
		{
			$word = 'an|本轮活动结束,目前剩余特等奖<font color = "black">'.$best_arr[0].'</font>张,奖励物品:'.$thing_info_best.',一等奖<font color = "black">'.$one_arr[0].'</font>张,奖励物品:'.$thing_info_one.',剩余刮刮卡数量:<font color = "black">'.$_POST['null'].'/'.$_POST['all'].'</font>'.'下一轮活动将在半小时后开启';
			break;
		}
	}
}
else
{
	$nickname = $_POST['nickname'];
	$area = $_POST['area'];
	$get_props = $_POST['props'];
	switch($_POST['Award'])
	{
		case 1 :
		{
			$level = '特等奖';
			$gg = 1;
			$word = 'an|恭喜官方平台['.$area.']区 玩家['.$nickname.'] 通过幸运刮刮卡，获得'.$level.',得到物品:'.$get_props;
			break;
		}
		case 2 :
		{
			$level = '一等奖';
			$gg = 1;
			$word = 'an|恭喜官方平台['.$area.']区 玩家['.$nickname.'] 通过幸运刮刮卡，获得'.$level.',得到物品:'.$get_props;
			break;
		}
		case 3 :
		{
			$level = '二等奖';
			$word = 'an|恭喜官方平台['.$area.']区 玩家['.$nickname.'] 通过幸运刮刮卡，获得'.$level.',得到物品:'.$get_props;
			$gg = 1;
			break;
		}
		case 4 :
		{
			$level = '三等奖';
			$gg = 0;
			break;
		}
		case 5 :
		{
			$level = '四等奖';
			$gg = 0;
			break;
		}
	}
}
if($gg == 1)
{
	$word = iconv('gbk','utf-8',$word);
	$r = $s->sendMsg($word);
}
?>
