<?php
/**
*/
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid <= 0)
{
	die('登录状态已失效，请重新登录！');
}

function msg($m,$js='')
{
	global $_pm;
	if(isset($_pm['mysql'])) $_pm['mysql']->query('ROLLBACK');
	realseLock();
	$m = addcslashes((string)$m, "\\\"\n\r");
	die('parent.Alert("'.$m.'");'.$js);
}
function fortressFail($m,$js='')
{
	global $_pm;
	$_pm['mysql']->query('ROLLBACK');
	msg($m,$js);
}
function fortressGateHtml($value)
{
	return htmlspecialchars(strval($value), ENT_QUOTES, 'UTF-8');
}
function fortressGateImage($value)
{
	$value = basename(strval($value));
	return preg_match('/^[A-Za-z0-9_.-]+$/', $value) ? $value : '';
}
secStart($_pm['mem']);

$petsarr	= $_pm['user']->getUserPetById($uid);
$user		= $_pm['user']->getUserById($uid);
if(!is_array($user)) msg('玩家数据不存在，请重新登录！');
if(!is_array($petsarr)) $petsarr = array();
if(!isset($user['sysautosum'])) $user['sysautosum'] = 0;
if(!isset($user['maxautofitsum'])) $user['maxautofitsum'] = 0;

$_SESSION['exptype'.$uid] = "";
$way = isset($_SESSION['way'.$uid]) ? $_SESSION['way'.$uid] : '';
if($way == "" || $way == "money")
{
	$num = $user['sysautosum'];
}
else if($way == "yb")
{
	$num = $user['maxautofitsum'];
}
if(!$_pm['mysql']->query("UPDATE player
						     SET autofitflag=0
						   WHERE id={$uid}
						")) msg('系统繁忙，请稍候操作！');
if(defined('MEM_USER_KEY')) $_pm['mem']->del(MEM_USER_KEY);

$kk=0;
$selid=0;
$sk=1;
$mbczl=0;
$bid = (isset($_GET['bid']) && !is_array($_GET['bid'])) ? abs(intval($_GET['bid'])) : 0;
$mbid=0;
if (is_array($petsarr))
{
	foreach ($petsarr as $k =>$rs) // Will filter in muchang pets for current user.
	{
		if(!is_array($rs)) continue;
		if(!isset($rs['muchang'])) $rs['muchang'] = 0;
		if(!isset($rs['tgflag'])) $rs['tgflag'] = 0;
		if(intval($rs['muchang']) != 0 || intval($rs['tgflag']) != 0){
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
		$petCardImg = fortressGateImage(isset($rs['cardimg']) ? $rs['cardimg'] : '');
		$petNameHtml = fortressGateHtml(isset($rs['name']) ? $rs['name'] : '');
		$petId = intval(isset($rs['id']) ? $rs['id'] : 0);
		$pets[$kk++] = "<img src='".IMAGE_SRC_URL."/bb/{$petCardImg}' onClick=\"Setbbs(".$kk.",".$petId.");\" alt=\"{$petNameHtml}\" style='cursor:pointer;filter:alpha(opacity={$sel});' id='i{$kk}'> ";
		if ($kk==3) break;
	}
}

$setting = $_pm['mem']->get('db_welcome1');
if(!is_array($setting)) $setting = kdjlSafeMemValue($setting, false);
if(!is_array($setting))
{
	msg('后台配置数据读取失败(1)！');
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
$sql_create_today="CREATE TABLE IF NOT EXISTS ".$table_name." (
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
if(!$_pm['mysql']->query($sql_create_today))
{
	msg('要塞数据表初始化失败，请稍候再试！');
}
$a = getLock($uid);
if(!is_array($a)){
	msg('服务器繁忙，请稍候再试！');
}
$user_in=$_pm['mysql']->getOneRecord('select user_id,bb_id,at_section_num from '.$table_name.' where user_id='.$uid.' for update');

if(is_array($user_in))
{
	$registeredPetId = intval($user_in['bb_id']);
	$registeredPet = $_pm['mysql']->getOneRecord('select id,czl from userbb where id='.$registeredPetId.' and uid='.$uid.' and muchang=0 and tgflag=0 for update');
	if(!is_array($registeredPet))
	{
		fortressFail('您报名时使用的宠物当前不可出战，请先恢复该宠物状态！');
	}
	$mbid = intval($registeredPet['id']);
	$mbczl = intval($registeredPet['czl']);
}
else
{
	if($mbid < 1)
	{
		fortressFail('请选择一个您的宠物！');
	}
	$selectedPet = $_pm['mysql']->getOneRecord('select id,czl from userbb where id='.$mbid.' and uid='.$uid.' and muchang=0 and tgflag=0 for update');
	if(!is_array($selectedPet))
	{
		fortressFail('请选择一个当前可出战的宠物！');
	}
	$mbid = intval($selectedPet['id']);
	$mbczl = intval($selectedPet['czl']);
}

$time_settings=explode("|",$setting['fortress_time']);
$w=intval(date('w'));
$hm=intval(date('His'));
if($w==0)
{
	$w=7;
}
$time_flag=false;
foreach($time_settings as $s)
{
	$tmp=explode(',',$s);
	if(count($tmp) < 5) continue;
	//1,210000,210459,212959,213459
	$day = intval($tmp[0]);
	$start = intval($tmp[1]);
	$joinEnd = intval($tmp[2]);
	$activityEnd = intval($tmp[4]);
	if($w == $day)
	{
		if(
		($hm >= $start && $hm <= $activityEnd && $user_in)
		||
		($hm >= $start && $hm <= $joinEnd)
		)
		{
			$time_flag=true;
		}
		if($hm > $activityEnd)
		{
			msg('现在只能查看排行！<br/><font color=#ff0000>系统没有扣除您的道具和金币！</font>','window.location="/function/fortress_stolen_Mod.php";');
		}
		break;
	}
}

if(!$time_flag){
	msg('现在不是要塞开启时间！');
}


$setABC = preg_split('/\s+/', trim($setting['fortress']));
$sqls_remove_item=array();

foreach($setABC as $k=>$s)
{
	if($s=='')
	{
		continue;
	}
	$tmp=explode(',',$s);
	if(count($tmp)<3)
	{
		msg('要塞活动配置错误！');
	}
	$tmp0=explode('-',$tmp[0]);//进入需要的成长
	$tmp1=explode('|',$tmp[1]);//进入需要的东西
	if(count($tmp0)<2)
	{
		msg('要塞活动配置错误！');
	}

	$sectionMatches = is_array($user_in)
		? (intval($user_in['at_section_num']) == $k+1)
		: ($mbczl>=intval($tmp0[0])&&$mbczl<=intval($tmp0[1]));
	if($sectionMatches)
	{
		$user=$_pm['mysql']->getOneRecord('select money from player where id='.$uid.' for update');
		if(!is_array($user) || intval($user['money'])<intval($tmp[2]))
		{
			fortressFail("你的游戏币不足，无法进入要塞。");
		}

		if(!$_pm['mysql']->query('update player set money=money-'.intval($tmp[2]).',mbid='.$mbid.' where id='.$uid.' and money>='.intval($tmp[2])) ||
			mysql_affected_rows($_pm['mysql'] -> getConn()) != 1)
		{
			fortressFail('系统繁忙，请稍候操作！');
		}

		$sqls_remove_item = array();
		foreach($tmp1 as $t)
		{
			if($t=='')
			{
				continue;
			}
			$it_need_setting=explode(':',$t);
			if(count($it_need_setting)<2)
			{
				fortressFail('要塞入场材料配置错误！');
			}
			$need_pid=intval($it_need_setting[0]);
			$need_num=intval($it_need_setting[1]);
			if($need_pid<=0 || $need_num<=0)
			{
				fortressFail('要塞入场材料配置错误！');
			}

			$rows=$_pm['mysql']->getRecords('select id,sums from userbag where uid='.$uid.' and sums>0 and pid='.$need_pid.' and zbing=0 and (cantrade IS NULL OR cantrade<>3) order by sums desc,id asc for update');
			$total=0;
			if(is_array($rows))
			{
				foreach($rows as $row)
				{
					$total += intval($row['sums']);
				}
			}
			if($total < $need_num)
			{
				fortressFail("需要的物品不足不能进入!");
			}
			$left=$need_num;
			foreach($rows as $row)
			{
				if($left <= 0) break;
				$take=min(intval($row['sums']), $left);
				$sqls_remove_item[]='update userbag set sums=sums - '.$take.' where id='.intval($row['id']).' and uid='.$uid.' and sums >='.$take.' and zbing=0 and (cantrade IS NULL OR cantrade<>3)';
				$left -= $take;
			}
		}

		foreach($sqls_remove_item as $sql)
		{
			if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql'] -> getConn()) != 1)
			{
				fortressFail('系统繁忙，请稍候操作！');
			}
		}
		if(!$_pm['mysql']->query('delete from userbag where uid='.$uid.' and sums<=0 and bsum<=0 and psum<=0 and pyb=0 and zbing=0 and (cantrade IS NULL OR cantrade<>3)'))
		{
			fortressFail('系统繁忙，请稍候操作！');
		}

		$nickname=$_pm['mysql']->escape(isset($_SESSION['nickname']) ? $_SESSION['nickname'] : '');
		if(is_array($user_in))
		{
			$sql_join='update '.$table_name.' set nickname="'.$nickname.'" where user_id='.$uid.' and bb_id='.$mbid.' and at_section_num='.intval($user_in['at_section_num']);
		}
		else
		{
			$sql_join='insert into '.$table_name.' set user_id='.$uid.',nickname="'.$nickname.'",bb_id='.$mbid.',at_section_num='.($k+1);
		}

		if(!$_pm['mysql']->query($sql_join)){
			fortressFail('要塞数据保存失败，请稍候再试！');
		}
		$commitOk = $_pm['mysql']->query('COMMIT');
		if(!$commitOk)
		{
			fortressFail('系统繁忙，请稍候操作！');
		}
		$_pm['mem']->del(MEM_USER_KEY);
		$_pm['mem']->del(MEM_USERBAG_KEY);
		$_SESSION['fortress_pass']=1;
		msg('进入成功,中途退出,再次进入将会再次扣除道具和金币,<br/>再次进入不会更换宠物，不会改变进度，不改变积分！','window.location="/function/fortressCard_Mod.php";');
	}
}

msg('没有适合您宠物的要塞，您宠物的成长（'.$mbczl.'）不在设定的范围内。');
?>
