<?php
header('Content-Type:text/html;charset=gbk');
//用于合作方查询该用户是否在游戏服务器注册。有角色的时候返回1，没有创建角色的时候返回2;
require_once("../config/config.game.php");

if($_GET['ly_id'] && $_GET['login_account'])
{
	$ly_id = htmlspecialchars($_GET['ly_id']);
	$login_account = htmlspecialchars($_GET['login_account']);
	$lys_is_true = $_pm['mysql'] -> getOneRecord(" SELECT F_prefix FROM T_udcconfig WHERE F_lys_id = '".$ly_id."'");
	if( !$lys_is_true )
	{
		die('11');
	}
	$lys_username = $lys_is_true['F_prefix'].$login_account;
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

$nickname = htmlspecialchars($_GET['nickname']);
if(!empty($nickname)){
	$arr = $_pm['mysql'] -> getOneRecord("SELECT id FROM player WHERE nickname = '{$nickname}' AND password != '00000000000000000000000000000000'");
	if(!empty($arr['id'])){
		$str = '恭喜，您输入的用户存在!';
		$qy = $_pm['mysql'] -> getOneRecord("SELECT sml FROM ml WHERE uid = {$_SESSION['id']} AND tid = {$arr['id']}");
		$qy1 = $_pm['mysql'] -> getOneRecord("SELECT sml FROM ml WHERE tid = {$_SESSION['id']} AND uid = {$arr['id']}");
		$qy = $qy['sml'] + $qy1['sml'];
		if($qy > 0){
			$str .= 'qy:'.$qy;
		}else{
			$str .= 'qy:0';
		}
		$ml = $_pm['mysql'] -> getOneRecord("SELECT ml FROM player_ext WHERE uid = {$arr['id']}");
		if($ml['ml'] > 0){
			$mlnum = $ml['ml'];
		}else{
			$mlnum = 0;
		}
		$str .= 'ml:'.$mlnum;
		die($str);
	}else{
		die('您查询的用户不存在！');
	}
}
$www=explode('.',$_SERVER['HTTP_HOST']);
$website='';
for($i=1;$i<count($www);$i++)
{
	$website.=$www[$i].'.';
}
switch ($website){
	case "kd.weelaa.com.":
		$name = $_GET['pp_uid'];
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
		$name = iconv('gbk','utf-8',urldecode($_GET['name']));
		$sql = "SELECT id FROM player WHERE name = '{$name}' and name != ''";
		if(isset($_GET['cmd'])){
			echo $sql;
		}
		$arr = $_pm['mysql'] -> getOneRecord($sql);
		if(is_array($arr)){
			die('1');
		}else{
			die('2');
		}
		break;
	case "jingling.kuwo.cn.":
		$name1 = $_REQUEST['name'];
		$sql = "SELECT id FROM player WHERE name = '{$name1}'";
		$arr = $_pm['mysql'] -> getOneRecord($sql);
		if($_REQUEST['cmd']==2)
		{
			echo $sql."<br />";
			print_r($arr);
		}
		
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
		$name = $_GET['name'];
		$sql = "SELECT id FROM player WHERE name = '{$name}' and name != ''";
		if(isset($_GET['cmd'])){
			echo $sql;
		}
		$arr = $_pm['mysql'] -> getOneRecord($sql);
		if(is_array($arr)){
			die('1');
		}else{
			die('2');
		}
		break;
}



$name1 = $_REQUEST['name'];

$name = iconv('utf-8','gbk',urldecode($name1));

if($_REQUEST['cmd']==2)
{
	echo $name."<br />";
}
if(!empty($name1) && empty($name))
{
	$sql = "SELECT id FROM player WHERE name = '{$name1}' and name != ''";
}
else{
	$sql = "SELECT id FROM player WHERE name = '{$name}' and name != ''";
}
$arr = $_pm['mysql'] -> getOneRecord($sql);
if($_REQUEST['cmd']==2)
{
	echo $sql."<br />";
	print_r($arr);
}

if(is_array($arr))
{
	die("1");
}
else
{
	die("2");
}

?>