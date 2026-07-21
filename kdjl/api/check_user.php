<?php
header('Content-Type:text/html;charset=gbk');
//用于合作方查询该用户是否在游戏服务器注册。有角色的时候返回1，没有创建角色的时候返回2;
require_once("../config/config.game.php");

if(isset($_GET['ly_id']) && isset($_GET['login_account']) && !is_array($_GET['ly_id']) && !is_array($_GET['login_account']) && $_GET['ly_id'] !== '' && $_GET['login_account'] !== '')
{
	$ly_id = $_pm['mysql']->escape($_GET['ly_id']);
	$login_account = (isset($_GET['login_account']) && !is_array($_GET['login_account'])) ? $_GET['login_account'] : '';
	$lys_is_true = $_pm['mysql'] -> getOneRecord(" SELECT F_prefix FROM T_udcconfig WHERE F_lys_id = '".$ly_id."'");
	if( !$lys_is_true )
	{
		die('11');
	}
	$lys_username = $_pm['mysql']->escape($lys_is_true['F_prefix'].$login_account);
	$user_real = $_pm['mysql'] -> getOneRecord(" SELECT id FROM player WHERE name = '".$lys_username."' AND pertain = '".$ly_id."'");
	if( is_array($user_real) )
	{
		die('10');
	}
	else
	{
		die('11');
	}
}

$nickname = (isset($_GET['nickname']) && !is_array($_GET['nickname'])) ? $_pm['mysql']->escape($_GET['nickname']) : '';
if(!empty($nickname)){
	$arr = $_pm['mysql'] -> getOneRecord("SELECT id FROM player WHERE nickname = '{$nickname}' AND password != '00000000000000000000000000000000'");
	if(!empty($arr['id'])){
		$targetUid = intval($arr['id']);
		$str = '恭喜，您输入的用户存在!';
		$sessionUid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
		$qy = 0;
		if($sessionUid > 0){
			$qyRow = $_pm['mysql'] -> getOneRecord("SELECT sml FROM ml WHERE uid = {$sessionUid} AND tid = {$targetUid}");
			$qyRow1 = $_pm['mysql'] -> getOneRecord("SELECT sml FROM ml WHERE tid = {$sessionUid} AND uid = {$targetUid}");
			$qy = (is_array($qyRow) ? intval($qyRow['sml']) : 0) + (is_array($qyRow1) ? intval($qyRow1['sml']) : 0);
		}
		if($qy > 0){
			$str .= 'qy:'.$qy;
		}else{
			$str .= 'qy:0';
		}
		$ml = $_pm['mysql'] -> getOneRecord("SELECT ml FROM player_ext WHERE uid = {$targetUid}");
		$mlnum = (is_array($ml) && intval($ml['ml']) > 0) ? intval($ml['ml']) : 0;
		$str .= 'ml:'.$mlnum;
		die($str);
	}else{
		die('您查询的用户不存在！');
	}
}
$httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$www=explode('.',$httpHost);
$website='';
for($i=1;$i<count($www);$i++)
{
	$website.=$www[$i].'.';
}
switch ($website){
	case "kd.weelaa.com.":
		$result = "";
		$name = (isset($_GET['pp_uid']) && !is_array($_GET['pp_uid'])) ? $_pm['mysql']->escape($_GET['pp_uid']) : '';
		if(!empty($name))
		{
			$sql = "SELECT id as uid,nickname as name FROM player WHERE name = '{$name}'";
			$arr = $_pm['mysql'] -> getOneRecord($sql);
			if(is_array($arr))
			{
				//$result = $arr;
				foreach($arr as $k=>$v)
				{
					$des[$k]=iconv('gbk','utf-8',$v);
				}
				$result = json_encode($des);

			}
			else
			{
				$result = "";
			}
		}
		print_r($result);exit;
		break;
	case "g.pplive.com.":
		$name = (isset($_GET['name']) && !is_array($_GET['name'])) ? iconv('gbk','utf-8',urldecode($_GET['name'])) : '';
		$safeName = $_pm['mysql']->escape($name);
		$sql = "SELECT id FROM player WHERE name = '{$safeName}' and name != ''";
		$arr = $_pm['mysql'] -> getOneRecord($sql);
		if(is_array($arr)){
			die('1');
		}else{
			die('2');
		}
		break;
	case "jingling.kuwo.cn.":
		$name1 = (isset($_REQUEST['name']) && !is_array($_REQUEST['name'])) ? $_pm['mysql']->escape($_REQUEST['name']) : '';
		$sql = "SELECT id FROM player WHERE name = '{$name1}'";
		$arr = $_pm['mysql'] -> getOneRecord($sql);
		if(is_array($arr))
		{
			die("1");
		}
		else
		{
			die("2");
		}
		break;
	case "czinfo.net.":
		//$name = iconv('gbk','utf-8',urldecode($_GET['name']));
		$name = (isset($_GET['name']) && !is_array($_GET['name'])) ? $_GET['name'] : '';
		$safeName = $_pm['mysql']->escape($name);
		$sql = "SELECT id FROM player WHERE name = '{$safeName}' and name != ''";
		$arr = $_pm['mysql'] -> getOneRecord($sql);
		if(is_array($arr)){
			die('1');
		}else{
			die('2');
		}
		break;
}



$name1 = (isset($_REQUEST['name']) && !is_array($_REQUEST['name'])) ? $_REQUEST['name'] : '';

$name = iconv('utf-8','gbk',urldecode($name1));

if(!empty($name1) && empty($name))
{
	$safeName = $_pm['mysql']->escape($name1);
	$sql = "SELECT id FROM player WHERE name = '{$safeName}' and name != ''";
}
else{
	$safeName = $_pm['mysql']->escape($name);
	$sql = "SELECT id FROM player WHERE name = '{$safeName}' and name != ''";
}
$arr = $_pm['mysql'] -> getOneRecord($sql);
if(is_array($arr))
{
	die("1");
}
else
{
	die("2");
}

?>