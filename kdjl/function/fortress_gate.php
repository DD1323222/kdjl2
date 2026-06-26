<?php
/**
*/
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');

function msg($m,$js='')
{
	realseLock();
	die('parent.Alert("'.$m.'");'.$js);
}
$a = getLock($_SESSION['id']);
if(!is_array($a)){
	msg('服务器繁忙，请稍候再试！');
}
secStart($_pm['mem']);

$petsarr	= $_pm['user']->getUserPetById($_SESSION['id']);

$_SESSION['exptype'.$_SESSION['id']] = "";
if($_SESSION['way'.$_SESSION['id']] == "" || $_SESSION['way'.$_SESSION['id']] == "money")
{
	$num = $user['sysautosum'];
}
else if($_SESSION['way'.$_SESSION['id']] == "yb")
{
	$num = $user['maxautofitsum'];
}
$_pm['mysql']->query("UPDATE player
					     SET autofitflag=0
					   WHERE id={$_SESSION['id']}
					");

$kk=0;
$selid=0;
$sk=1;
$mbczl=0;
$bid=abs($_GET['bid']);
$mbid=0;
if (is_array($petsarr))
{
	foreach ($petsarr as $k =>$rs) // Will filter in muchang pets for current user.
	{
		if($rs['muchang'] != 0){
			continue;
		}
		if($rs['id'] == $bid)
		{
			$sel  = 100;
			$selid=$rs['id'];
			$sk   =$kk;
			$mbczl=$rs['czl'];
			$mbid=$bid;
		}
		else
		{
			$sel = 50;
		}
		if($rs['level']==0) $rs['level']=1;
		$pets[$kk++] = "<img src='".IMAGE_SRC_URL."/bb/{$rs['cardimg']}' onClick=\"Setbbs(".$kk.",".$rs['id'].");\" alt=\"{$rs['name']}\" style='cursor:pointer;filter:alpha(opacity={$sel});' id='i{$kk}'> ";
		if ($kk==3) break;
	}
}

if(!$mbid)
{
	msg('请选择一个您的宠物！');
}
$setting = $_pm['mem']->get('db_welcome1');
if(!is_array($setting)) $setting=unserialize($setting);
if(!is_array($setting))
{
	msg('后台配置数据读取失败(1)！'.print_r($setting,1));
}
if(!isset($setting['fortress']))
{
	msg('缺少活动开启设定(fortress)！');
}

if(!isset($setting['fortress_time']))
{
	msg('缺少活动开启设定(fortress_time)！');
}
$table_name="`fortress_users_".date("Ymd")."`";
$user_in=$_pm['mysql']->getOneRecord('select user_id from '.$table_name.' where user_id='.$_SESSION['id']);

$time_settings=explode("|",$setting['fortress_time']);
$w=date('w');
$hm=date('His');
if($w==0)
{
	$w=7;
}
$time_flag=false;
foreach($time_settings as $s)
{
	$tmp=explode(',',$s);
	//1,210000,210459,212959,213459
	if($w==$tmp[0])
	{
		if(
		($hm>=$tmp[1]&&$hm<=$tmp[4]&&$user_in)
		||
		($hm>=$tmp[1]&&$hm<=$tmp[2])		
		)
		{		
			$time_flag=true;
		}
		if($hm>$tmp[4])
		{
			msg('现在只能查看排行！<br/><font color=#ff0000>系统没有扣除您的道具和金币！</font>','window.location="/function/fortress_stolen_Mod.php";');
		}
		break;
	}
}

if(!$time_flag){
	msg('现在不是要塞开启时间！');
}


$setABC = explode(" ",$setting['fortress']);
$sqls_remove_item=array();

foreach($setABC as $k=>$s)
{
	$tmp=explode(',',$s);
	$tmp0=explode('-',$tmp[0]);//进入需要的成长
	$tmp1=explode('|',$tmp[1]);//进入需要的东西	

	if($mbczl>=$tmp0[0]&&$mbczl<=$tmp0[1])
	{
		$user=$_pm['mysql']->getOneRecord('select money from player where id='.$_SESSION['id']);
		if($user['money']<$tmp[2])
		{
			msg("你的游戏币不足，无法进入要塞。");
		}

		$_pm['mysql']->query('update player set money='.($user['money']-$tmp[2]).',mbid='.$mbid.' where id='.$_SESSION['id']);

		foreach($tmp1 as $t)
		{
			$it_need_setting=explode(':',$t);
			$tmp1_str.=$props[$tt[0]]['name'].' '.$tt[1].'个,';

			$row=$_pm['mysql']->getOneRecord('select id,sums from userbag where uid='.$_SESSION['id'].' and sums>='.$it_need_setting[1].' and pid='.$it_need_setting[0]);
			if(!$row)
			{
				$_pm['mysql']->query('rollback');
				msg("需要的物品不足不能进入!");
			}
			$_pm['mysql']->query('select * from userbag where id='.$row['id'].' for update');
			$sqls_remove_item[]='update userbag set sums=sums - '.$it_need_setting[1].' where id='.$row['id'].' and sums >='.$it_need_setting[1];
		}

		foreach($sqls_remove_item as $sql)
		{
			$_pm['mysql']->query($sql);
			if(mysql_affected_rows($_pm['mysql'] -> getConn()) != 1)
			{
				$_pm['mysql']->query('rollback');
				msg('系统繁忙，请稍候操作！');
			}
		}
		$sql_create_today="CREATE TABLE ".$table_name." (
  `user_id` int(10),
  `bb_id` int(10) NULL DEFAULT NULL,
  `cur_gpc_id` int(10) NULL DEFAULT NULL,
  `at_section_num` tinyint(2) NULL DEFAULT NULL COMMENT '成长阶段数',
  `nickname` varchar(32) NULL DEFAULT NULL,
  `v_times` smallint(6) NULL DEFAULT 0  COMMENT '胜利次数',
  `f_times` smallint(6) NULL DEFAULT 0 COMMENT '失败次数',
  `fv_result` smallint(6) NULL DEFAULT 0 COMMENT '当前胜利失败计算基数',
  `score` smallint(6) NULL DEFAULT 0 COMMENT '积分',
  `score_final` smallint(6) NULL DEFAULT 0 COMMENT '偷取之后的积分',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB COMMENT='女神要塞';";
		$_pm['mysql']->query($sql_create_today);

		$sql_join='insert into '.$table_name.' set user_id='.$_SESSION['id'].',nickname="'.$_SESSION['nickname'].'",bb_id='.$mbid.',at_section_num='.($k+1).' on duplicate key update nickname="'.$_SESSION['nickname'].'"';
		
		$_pm['mysql']->query($sql_join);
		if(mysql_error()){
			msg($sql_join.'<br />'.mysql_error());
		}
		$_SESSION['fortress_pass']=1;
		msg('进入成功,中途退出,再次进入将会再次扣除道具和金币,<br/>再次进入不会更换宠物，不会改变进度，不改变积分！','window.location="/function/fortressCard_Mod.php";');
	}
}

msg('没有适合您宠物的要塞，您宠物的成长（'.$mbczl.'）不在设定的范围内。');
?>