<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.19
*@Update Date: 2008.07.13
*@Usage: Save and Get pets of user
*/


require_once('../config/config.game.php');
if($_REQUEST['from'] != 1)
{
	secStart($_pm['mem']);
}
$id = intval($_REQUEST['id']); // table: userbb => id
$op = $_REQUEST['op'];


if ($_pm['user']->check(array('int' => $id)) === false) die('数据错误1！'); // 错误的数据。

$user	 = $_pm['user']->getUserById($_SESSION['id']);



$userbb  = $_pm['user']->getUserPetById($_SESSION['id']);
$mc		 = 0;
$bagmc	 = 0;
if($op =='z' && $id == $user['mbid']){
	die('已经是主战！');
}
if (!is_array($userbb) || !is_array($user)) die('数据错误2！');

$valid = false;
foreach ($userbb as $k => $v)
{
	if ($v['muchang'] == 1) $mc++;
	else if($v['muchang'] == 0) $bagmc++;
	if ($v['id'] == $id) $valid=true;
}
if ($valid === false) die('数据错误3！');

// Set main fight pets

if ($op =='z' && $id != $user['mbid'])
{

	//设主战宠物的时候要判断他当前的任务是不是不可以切换宠物的任务
	$sql = "SELECT taskid FROM tasklog WHERE uid = {$_SESSION['id']} and taskid = 9999";
	$arr = $_pm['mysql'] -> getOneRecord($sql);
	if(is_array($arr))
	{
		die("10");//不能切换主战。
	}
	foreach($userbb as $v){
		if($v['id'] == $id && $v['muchang'] != 0 ){
			die('在牧场的宝宝不能设为主战哦！');
		}
	}
	$_pm['mysql']->query("UPDATE player 
				   SET fightbb={$id},mbid={$id}
				 WHERE id={$_SESSION['id']}
			  ");
	//$_pm['user']->updateMemUser($_SESSION['id']);
	if($_REQUEST['from'] == 1)
	{
		die("OK");
	}
	die('更改主战宝宝成功!');
}
else if($op =='change' && $id != $user['mbid'])
{
	foreach($userbb as $v){
		if($v['id'] == $id && $v['muchang'] != 0 ){
			die('在牧场的宝宝不能设为主战哦！');
		}
	}
	$_pm['mysql']->query("UPDATE player 
				   SET mbid={$id},task = '',tasklog = ''
				 WHERE id={$_SESSION['id']}
			  ");
			  
	$_pm['mysql']->query("DELETE FROM tasklog WHERE uid = {$_SESSION['id']} and taskid = 9999
			  ");
	die("更改主战宝宝成功！");
}
// Save pets
if ($op=='s' && $mc<$user['maxmc'] && $bagmc>1 && $user['mbid'] != $id)
{
	
	$_pm['mysql']->query("UPDATE userbb
				   SET muchang=1
				 WHERE uid={$_SESSION['id']} and id={$id}
			  ");
	if(mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){
		die('已经在牧场！');
	}
	//$_pm['user']->updateMemUserbb($_SESSION['id']);
	die("操作成功!");
} 
else if ($op == 'g' && $bagmc<3)
{
	if(!empty($user['fieldpwd'])&&empty($_SESSION['loginField'.$_SESSION['id']])){
	  die('请先登录 !');
	}
	$res = $_pm['mysql']-> getOneRecord("SELECT * FROM userbb  WHERE uid={$_SESSION['id']} and id={$id} and (chchengsx is null or chchengsx = '')");
	if(!is_array($res))
	{
		die("传承中不能取出");
	}
	$_pm['mysql']->query("UPDATE userbb
				   SET muchang=0
				 WHERE uid={$_SESSION['id']} and id={$id}
			  ");
	if(mysql_affected_rows($_pm['mysql'] -> getConn()) != 1){
		die('此宠物已经携带！');
	}
	//$_pm['user']->updateMemUserbb($_SESSION['id']);
	die("操作成功!");
}
// del
else if ($op == 'd' && $user['mbid'] != $id)
{
	if($user['money']<10000){
		die('您没有足够多的金币哦！');
	}
	//print_r($user);
    $pwd = htmlspecialchars(mysql_escape_string($_REQUEST['pwd']));
    if(empty($pwd) && !empty($user['fieldpwd']))
    {
        die("请输入密码！");//请输入密码！
    }
    $pwd = abs(crc32(md5($pwd)));
    if($pwd != $user['fieldpwd']  && !empty($user['fieldpwd']))
    {
        die("1");//密码错误！
    }
    //exit;
	//增加日志。5.13
	$time = time();
	$bb = $_pm['mysql'] -> getOneRecord("SELECT * FROM userbb WHERE id = {$id}"); 
	if(!empty($bb['zb']))
	{
		$str = "装备：";
		//4:291791,9:268156,5:424046,6:300872,3:328876,1:379748,2:334791,7:308728
		$arr = explode(",",$bb['zb']);
		foreach($arr as $v)
		{
			$bagid = explode(":",$v);
			$newarr = $_pm['mysql'] -> getOneRecord("SELECT pid FROM userbag WHERE id = {$bagid[1]}");
			$str .= $newarr['pid'].',';
		}
	}
	$str .= "等级：".$bb['level'];
	$str .=",成长：".$bb['czl'];
	$str .=",名字：".$bb['name'];
	$_pm['mysql'] -> query("INSERT INTO gamelog (ptime,seller,buyer,pnote,vary) values({$time},{$_SESSION['id']},{$_SESSION['id']},'{$str}',16)");
	// del sk. 
	$_pm['mysql']->query("DELETE FROM skill
				 WHERE bid={$id}
			  ");
	// del zb.
	$_pm['mysql']->query("DELETE FROM userbag
				 WHERE uid={$_SESSION['id']} and zbpets={$id}
			  ");
	// del bb.
	$_pm['mysql']->query("DELETE FROM userbb
				 WHERE uid={$_SESSION['id']} and id={$id}
			  ");
	//$_pm['user']->updateMemUserbb($_SESSION['id']);
	//$_pm['user']->updateMemUsersk($_SESSION['id']);
	//$_pm['user']->updateMemUserbag($_SESSION['id']);

	die("操作成功!");
}
$_pm['mem']->memClose();
unset($db);

if($bagmc==3)
{
	if($op == 's' && $user['mbid'] == $id)
	{
		die('该宠物为主战宠物，无法寄养！');
	}
	if($op == 'g' || $op == 's')
	{
		die('此宠物已经携带！');
	}
	else
	{
		die('您最多同时只能携带3个宝宝！');
	}
	
}
else if($mc==$user['maxmc'])
{
	die('您的宠物寄养空间已满，不能寄养更多宝宝！');
}
else if($bagmc==1)
{
	die('您必须携带一个宝宝，以便参加战斗!');
}
die("操作失败!");
?>
