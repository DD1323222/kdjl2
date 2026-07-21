<?php
session_start();
require_once('../config/config.game.php');
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1)
{
	die('登录状态无效！');
}
if(!$_pm['mysql']->query("INSERT INTO player_ext(uid,bbshow) VALUES({$uid},5) ON DUPLICATE KEY UPDATE uid=uid"))
{
	die('初始化玩家卡片数据失败！');
}
function kdjlCardBanCount($username)
{
	global $_pm;
	if($username === '') return 0;
	$raw = $_pm['mem']->get('BAN_CARD_USER_'.$username);
	if($raw === false || $raw === null || $raw === '') return 0;
	if(is_numeric($raw)) return intval($raw);
	if(!is_string($raw)) return 0;
	$parsed = @unserialize($raw);
	return is_numeric($parsed) ? intval($parsed) : 0;
}
function kdjlCardBanAdd($username)
{
	global $_pm;
	if($username === '') return 0;
	$num = kdjlCardBanCount($username) + 1;
	$_pm['mem']->set(array('k'=>'BAN_CARD_USER_'.$username,'v'=>$num));
	return $num;
}
function cardReqHtml($value)
{
	return htmlspecialchars(strval($value), ENT_QUOTES);
}
function cardReqImage($value)
{
	$value = basename(strval($value));
	return preg_match('/^[A-Za-z0-9_.-]+$/', $value) ? $value : '';
}
function cardReqJsSingle($value)
{
	return str_replace(array('\\', "'", "\r", "\n", '<', '>'), array('\\\\', "\\'", '', '', '\\x3C', '\\x3E'), strval($value));
}
$cardBanUsername = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$ban_user = kdjlCardBanCount($cardBanUsername);
if( $ban_user  >= 10  )
{
	die("卡片系统不对恶意用户开放,请联系管理员！！！");
}
require_once('get_para_verify.php');
foreach( $_GET as $key => $val )
{
	$verify = true;
	if(is_array($val))
	{
		$verify = false;
	}
	else switch ($key)
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
		echo "你的帐号<font color = 'blue'>".$cardBanUsername."</font>";
		$bad_transport_time = kdjlCardBanAdd($cardBanUsername);
		echo "恶意传参次数:".$bad_transport_time.",超过10次会自动被永久封号，请注意！！！<br>";
		die("非法传参数4");
	}
}
function cardParseCountList($value)
{
	$ret = array();
	if($value === '' || $value === null) return $ret;
	$parts = explode(',', $value);
	$pendingNames = array();
	$positiveCounts = array();
	for($i = 0; $i < count($parts); $i++)
	{
		$part = trim($parts[$i]);
		if($part === '') continue;
		$item = explode(':', $part, 2);
		$name = trim($item[0]);
		if($name === '') continue;
		if(count($item) < 2 || trim($item[1]) === '')
		{
			$pendingNames[] = $name;
			continue;
		}
		$count = max(0, intval($item[1]));
		$ret[$name] = $count;
		if($count > 0) $positiveCounts[$count] = true;
	}
	if(!empty($pendingNames))
	{
		// Three legacy forest-card prize rows omit the final count. Their other
		// requirements all share one tier (1, 10 or 100), so preserve that tier.
		if(count($positiveCounts) != 1) return array();
		$defaultCount = intval(key($positiveCounts));
		foreach($pendingNames as $name) $ret[$name] = $defaultCount;
	}
	return $ret;
}
function cardParseIdList($value)
{
	$ret = array();
	if($value === '' || $value === null) return $ret;
	$parts = explode(',', $value);
	for($i = 0; $i < count($parts); $i++)
	{
		$id = intval($parts[$i]);
		if($id > 0) $ret[$id] = $id;
	}
	return $ret;
}
function cardRequirementsMet($requirements, $ownedCards)
{
	if(!is_array($requirements) || empty($requirements) || !is_array($ownedCards)) return false;
	foreach($requirements as $cardName => $requiredCount)
	{
		if(!isset($ownedCards[$cardName]) || intval($ownedCards[$cardName]) < intval($requiredCount)) return false;
	}
	return true;
}
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
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
$requestSelectMap = (isset($_GET['select_map']) && !is_array($_GET['select_map'])) ? $_GET['select_map'] : '';
$requestCmd = (isset($_GET['cmd']) && !is_array($_GET['cmd'])) ? $_GET['cmd'] : '';
$requestPrize = (isset($_GET['prize']) && !is_array($_GET['prize'])) ? $_GET['prize'] : '';
if( $requestSelectMap )
{
?>
<body bgcolor="#fffceb">
	<div class="dt_box">
	<table width="auto" border="1" cellspacing="0" cellpadding="0" bordercolor="#fffceb">
	<?php
	$select_map = $requestSelectMap;
	$selectMapSql = $_pm['mysql']->escape($select_map);
	$sql = " SELECT F_Had_Card FROM T_Card_Type WHERE F_Class_Name = '".$selectMapSql."'";
	unset($result);
	$result = $_pm['mysql']->getOneRecord($sql);
	$class_had_card_arr = is_array($result) && isset($result['F_Had_Card']) && $result['F_Had_Card'] !== '' ? array_values(array_filter(explode(',',$result['F_Had_Card']),'strlen')) : array();
	$sql = " SELECT F_User_Card_Info FROM player_ext WHERE uid = '".$uid."'";
	$result_had_card = $_pm['mysql']->getOneRecord($sql);
	$cardInfo = is_array($result_had_card) && isset($result_had_card['F_User_Card_Info']) ? $result_had_card['F_User_Card_Info'] : '';
	$result_had_card_info = cardParseCountList($cardInfo);
	$cardPropsByName = array();
	$cardPropsRows = $_pm['mysql']->getRecords("SELECT name,img FROM props WHERE varyname=24 ORDER BY id");
	if(!is_array($cardPropsRows)) $cardPropsRows = array();
	foreach($cardPropsRows as $cardPropsRow)
	{
		$propsName = isset($cardPropsRow['name']) ? $cardPropsRow['name'] : '';
		if($propsName !== '' && !isset($cardPropsByName[$propsName])) $cardPropsByName[$propsName] = $cardPropsRow;
	}

	?>
	<tr>
	<?php
	$num = 0;
	for($i = 0; $i < count($class_had_card_arr); $i++ )
	{
		$num++;
		$cardName = isset($class_had_card_arr[$i]) ? $class_had_card_arr[$i] : '';
		$cardNameHtml = cardReqHtml($cardName);
		$echo_td = '<td align="center" width="110"><img src="../images/ui/card/paper.jpg" width="63" height="94" /><br>'.$cardNameHtml.'<br>0张</td>';
		if( array_key_exists($cardName,$result_had_card_info)	)
		{
				$imgHtml = '<img src="../images/ui/card/paper.jpg" width="63" height="94" />';
				$cardProps = isset($cardPropsByName[$cardName]) ? $cardPropsByName[$cardName] : array();
				if(!empty($cardProps['img']))
				{
					$cardImg = cardReqImage($cardProps['img']);
					if($cardImg !== '') $imgHtml = '<img src="../images/card_Mod/'.$cardImg.'" width="63" height="94" />';
				}
				$echo_td = '<td align="center" width="110">'.$imgHtml."<br>".$cardNameHtml."<br>".intval($result_had_card_info[$cardName]).'张</td>';
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
if ( $requestCmd == 'getprize' || $requestPrize )
{
?>
	<body bgcolor="#fffceb">
	<div class="task">
	<div class="task_right">
		<div class="task_box">
	<script language="javascript">
	function getprize_thing(para)
	{
		if(confirm("确认领取该卡片奖励吗？"))
		{
			window.location="card_Mod_Request.php?prize="+para;
		}
	}
	</script>
<?php
	if ( empty($requestPrize) )
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
	$text = '';
	if(is_array($arr)) $arr = implode(',', $arr);
	$requirements = cardParseCountList($arr);
	foreach($requirements as $cardName => $cardCount)
	{
		$text .= '需要'.cardReqHtml($cardName).'卡片'.intval($cardCount).'个<br>';
	}
	return $text;
}
function getechoprize($arr)
{
	global $_pm;
	static $propsNameCache = array();
	$text = '';
	if(!is_array($arr)) return $text;
	for($j = 0 ; $j < count($arr); $j++ )
	{
		if($arr[$j] === '') continue;
		$arr_info = explode(':',$arr[$j],2);
		$pid = isset($arr_info[0]) ? intval($arr_info[0]) : 0;
		$num = isset($arr_info[1]) ? intval($arr_info[1]) : 0;
		if($pid < 1 || $num < 1) continue;
		if(!isset($propsNameCache[$pid]))
		{
			$result_props_name = $_pm['mysql']->getOneRecord("SELECT name FROM props WHERE id={$pid}");
			$propsNameCache[$pid] = is_array($result_props_name) && isset($result_props_name['name']) ? $result_props_name['name'] : '';
		}
		if($propsNameCache[$pid] === '') continue;
		$text .= cardReqHtml($propsNameCache[$pid]).$num.'个<br>';
	}
	return $text;
}

	$sql = " SELECT * FROM T_Card_Prize ";
	$result = $_pm['mysql']->getRecords($sql);
	if(!is_array($result)) $result = array();
	$sql = "SELECT F_User_Card_Info FROM player_ext WHERE uid = '".$uid."'";
	$result_card_info = $_pm['mysql']->getOneRecord($sql);
	$result_has_get_prize = $_pm['mysql']->getOneRecord("SELECT F_has_get_prize FROM player_ext WHERE uid={$uid}");
	$hasGetPrize = is_array($result_has_get_prize) && isset($result_has_get_prize['F_has_get_prize']) ? $result_has_get_prize['F_has_get_prize'] : '';
	$has_get_info = cardParseIdList($hasGetPrize);
	$prize_is_or_no = array();
	if( !is_array($result_card_info) || empty($result_card_info['F_User_Card_Info']) )
	{
		foreach( $result as $info )
		{
			$arr = explode(',',$info['F_Satisfy_condition']);
			$text_need = getechoneed($arr);
			unset($text);
			$arr = explode(',',$info['F_Prize']);
			$text_prize = getechoprize($arr);
			unset($text);
			$prizeTitleHtml = cardReqHtml(isset($info['F_Prize_title']) ? $info['F_Prize_title'] : '');
			$echo = '<tr>'.'<td align="center" bgcolor="#FFFCEB">'.$prizeTitleHtml."</td>".'<td align="center" bgcolor="#FFFCEB">'.$text_need.'</td>'.'<td align="center" bgcolor="#FFFCEB">'.$text_prize.'</td>'.'<td align="center" bgcolor="#FFFCEB" bgcolor="#FFFCEB"><img src="../images/ui/card/noget.jpg" width="44" height="17" /></td>'.'</tr>';
			if( empty($requestPrize) )
			{
				echo $echo;
			}
		}
	}
	else
	{
		$cardInfo = is_array($result_card_info) && isset($result_card_info['F_User_Card_Info']) ? $result_card_info['F_User_Card_Info'] : '';
		$user_card_info_arr = cardParseCountList($cardInfo);
		foreach( $result as $info )
		{
			if(!isset($info['F_Satisfy_condition']) || $info['F_Satisfy_condition'] === '') continue;
			$info_need_arr = cardParseCountList($info['F_Satisfy_condition']);
			if(empty($info_need_arr)) continue;
			if(cardRequirementsMet($info_need_arr, $user_card_info_arr))
			{
				$deal = $info['id'];	//有完成任务,待验证是否领取过
				if( isset($has_get_info[intval($deal)]) )	//领过
				{
					$deal = "got";
				}
				else
				{
					$prize_is_or_no[intval($info['id'])] = intval($info['id']);
				}
			}

			$arr = explode(',',$info['F_Satisfy_condition']);
			$text_need = getechoneed($arr);
			unset($text);
			$arr = explode(',',$info['F_Prize']);
			$text_prize = getechoprize($arr);
			unset($text);
			$prizeTitleHtml = cardReqHtml(isset($info['F_Prize_title']) ? $info['F_Prize_title'] : '');
			$echo = '<tr>'.'<td align="center" bgcolor="#FFFCEB">'.$prizeTitleHtml."</td>".'<td align="center" bgcolor="#FFFCEB">'.$text_need.'</td>'.'<td align="center" bgcolor="#FFFCEB">'.$text_prize.'</td>';
			if( isset($deal) )
			{
				if( $deal != 'got' )
				{
					$echo .= '<td align="center" bgcolor="#FFFCEB" bgcolor="#FFFCEB"><img src="../images/ui/card/award.jpg" width="44" height="17"  onClick="getprize_thing('.intval($deal).')" /></td>'.'</tr>';
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
			if( $requestPrize === '' )
			{
				echo $echo;
			}
			unset($deal);
			unset($echo);
			unset($info_need_arr);
			unset($info_need_name);
			unset($info_need_num);
		}
		if($requestPrize !== '' && intval($requestPrize) > 0 )
		{
			require_once('../sec/dblock_fun.php');
			$a = getLock($uid);
			if(!is_array($a))
			{
					realseLock();
					die('服务器繁忙，请稍候再试！');
				}
				$prize_id = intval($requestPrize);
			if ( isset($prize_is_or_no[$prize_id]) )
			{
				$sql = " SELECT F_Prize,F_Satisfy_condition FROM T_Card_Prize WHERE id = '".$prize_id."'";
				$result_prize_info = $_pm['mysql']->getOneRecord($sql);
				if(!is_array($result_prize_info) || empty($result_prize_info['F_Prize']) || empty($result_prize_info['F_Satisfy_condition'])){
					$_pm['mysql']->query('ROLLBACK');
					realseLock();
					die('卡片奖励配置错误！');
				}
				$prize_info = $result_prize_info['F_Prize'];
				$sql = " SELECT F_has_get_prize,F_User_Card_Info FROM player_ext WHERE uid = '".$uid."' FOR UPDATE";
				$result_has_get_prize = $_pm['mysql']->getOneRecord($sql);
				$lockedRequirements = cardParseCountList($result_prize_info['F_Satisfy_condition']);
				$lockedCardInfo = is_array($result_has_get_prize) && isset($result_has_get_prize['F_User_Card_Info']) ? $result_has_get_prize['F_User_Card_Info'] : '';
				if(!cardRequirementsMet($lockedRequirements, cardParseCountList($lockedCardInfo)))
				{
					$_pm['mysql']->query('ROLLBACK');
					realseLock();
					die('尚未满足领取奖励的条件！');
				}
				$hasGot = is_array($result_has_get_prize) && isset($result_has_get_prize['F_has_get_prize']) ? $result_has_get_prize['F_has_get_prize'] : '';
				$result_has_get_prize_arr = cardParseIdList($hasGot);
				if(isset($result_has_get_prize_arr[$prize_id]))
				{
					$_pm['mysql']->query('ROLLBACK');
					realseLock();
					echo '已经领取过奖励';
					die();
				}
				$user		= $_pm['user']->getUserById($uid);
				$bag		= $_pm['user']->getUserBagById($uid);
				$card_task = new task;
				$arr_prize_thing = explode(',',$prize_info);
				$gavePrize = false;
				for( $i = 0; $i < count($arr_prize_thing); $i++ )
				{
					$info = explode(':',trim($arr_prize_thing[$i]));
					if(count($info) != 2){
						$_pm['mysql']->query('ROLLBACK');
						realseLock();
						die('卡片奖励配置错误！');
					}
					$idlist = intval($info[0]);
					$num = intval($info[1]);
					if($idlist < 1 || $num < 1){
						$_pm['mysql']->query('ROLLBACK');
						realseLock();
						die('卡片奖励配置错误！');
					}
					$result_of_get_prize = $card_task->saveGetPropsMore($idlist,$num);
					if($result_of_get_prize !== true){
						$_pm['mysql']->query('ROLLBACK');
						realseLock();
						die($result_of_get_prize === '200' ? '背包空间不足！' : '发放奖励失败！');
					}
					$gavePrize = true;
				}
				if(!$gavePrize){
					$_pm['mysql']->query('ROLLBACK');
					realseLock();
					die('卡片奖励配置错误！');
				}
				$result_has_get_prize_arr[$prize_id] = $prize_id;
				$set = implode(',', $result_has_get_prize_arr);
				$sql = " UPDATE player_ext SET F_has_get_prize = '".$set."' WHERE uid = '".$uid."'";
				if(!$_pm['mysql']->query($sql) || mysql_affected_rows($_pm['mysql']->getConn()) != 1){
					$_pm['mysql']->query('ROLLBACK');
					realseLock();
					die('保存领奖状态失败！');
				}
				if(!$_pm['mysql']->query('COMMIT')){
					$_pm['mysql']->query('ROLLBACK');
					realseLock();
					die('保存领奖状态失败！');
				}
				$_pm['mem']->del(MEM_USERBAG_KEY);
				realseLock();
				echo '领取奖励成功';
			}
			else
			{
				$_pm['mysql']->query('ROLLBACK');
				realseLock();
				echo '非法奖励请求！';
				die('非法传参数2');
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
