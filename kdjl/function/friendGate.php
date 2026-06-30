<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.05.22
*@Usage: 添加好友。
*@Note: none
*/

header('Content-Type:text/html;charset=GBK');

require_once('../config/config.game.php');
secStart($_pm['mem']);

$user	 = $_pm['user']->getUserById($_SESSION['id']);
$_REQUEST['name'] = trim($_REQUEST['name']);
$tu	= $_pm['mysql']->escape($_REQUEST['name']);
define("MEM_BLACKLIST_KEY","db_blacklist");
$blacklist = unserialize($_pm['mem'] -> get(MEM_BLACKLIST_KEY));
if ($tu=='' or empty($tu)) die('请正确输入玩家角色名！');
	
if ($_REQUEST['op'] == 'add')	//	添加好友。
{
	$fret = $_pm['mysql']->getOneRecord("SELECT nickname FROM player WHERE nickname='{$tu}'");
	if (is_array($fret))
	{
		$fname = $fret['nickname'];
		
		$friendlist = $user['friendlist'];
		
		$black = $_pm['mysql'] -> getOneRecord("SELECT nickname,list FROM blacklist WHERE uid = {$_SESSION['id']}");
		if($black['list'] != ''){
			$farr = explode(',',$black['list']);
			if(array_search($fname, $farr) !== FALSE){
				die('该玩家在您的黑名单中，不能加入好友！');
			}
		}
		
		$self = $_pm['mysql'] -> getOneRecord("SELECT nickname FROM player WHERE id = {$_SESSION['id']}");
		if($self['nickname'] == $fname){
			die('您不能添加您自己！');
		}
		
		if (strlen($friendlist)<3)
		{
			$friendlist = $fname;
		}
		else
		{
			$arr = explode(',', $friendlist);
			if (count($friendlist)>=20)
				die('您目前只能添加20个好友！');
			
			if (array_search($fname, $arr) === FALSE)
			{
				$friendlist .= ',' . $fname;
			}
			else die('该用户已经是好友了！');
		}
		
		$_pm['mysql']->query("UPDATE player 
					   SET friendlist='{$friendlist}'
					 WHERE id={$_SESSION['id']}
				  ");
		liststr($friendlist);
	}
	else die('无效的玩家角色名!');
}
else if($_REQUEST['op'] == 'del') // 删除好友。
{	
		$friendlist = $_REQUEST['name'];
		if (strlen($friendlist)<3)
		{
			die('该用户不是您的好友！');
		}
		else
		{
			$arr = explode(',', $user['friendlist']);
			$s = array_search($_REQUEST['name'], $arr);
			if ($s === FALSE)
			{
				die('该用户不是您的好友！');
			}
			else
			{
				$friendlist = '';
				foreach($arr as $k => $v)
				{
					if ($v == $_REQUEST['name']) continue;
					$friendlist .= ','.$v;
				}
				$friendlist = substr($friendlist,1);
			}
		}
		
		$_pm['mysql']->query("UPDATE player 
					   SET friendlist='{$friendlist}'
					 WHERE id={$_SESSION['id']}
				  ");
		liststr($friendlist);
}
else if($_REQUEST['op'] == 'addblacklist')//加入黑名单
{
	$err = 10;
	
	$fret = $_pm['mysql']->getOneRecord("SELECT nickname FROM player WHERE nickname='{$tu}'");//找到要加入黑名单的用户
	$fname = $fret['nickname'];
	if(!is_array($fret))
	{
		die("请正确输入您要加黑名单的角色名！");
	}
	$friend = $_pm['mysql'] -> getOneRecord("SELECT nickname,friendlist FROM player WHERE id = {$_SESSION['id']}");
	if($friend['friendlist'] != ''){
		$farr = explode(',',$friend['friendlist']);
		if(array_search($fname, $farr) !== FALSE){
			die('该玩家是您的好友，您不能加入黑名单！');
		}
	}
	if($friend['nickname'] == $fname){
		die('您不能添加您自己！');
	}
	
	$barr = $_pm['mysql'] -> getOneRecord("SELECT list FROM blacklist WHERE uid = {$_SESSION['id']}");
	
	if(!empty($barr['list']))
	{
		//$list = substr($blacklist[$_SESSION['id']],1,-1);
		$arr = explode(",",$barr['list']);
		$num = count($arr);
		if($num >= 30)
		{
			die("您当前只能加30个人入黑名单!");
		}
		if (array_search($fname, $arr) === FALSE)
		{
			$blacklist = $barr['list'].','.$fname;
			$_pm['mysql'] -> query("UPDATE blacklist SET list = '{$blacklist}' WHERE uid = {$_SESSION['id']}");
			$_pm['mem']->del('db_blacklist');//重新加载内存数据
			$ret2 = $_pm['mysql']->getRecords("select uid,list from blacklist");
			foreach($ret2 as $k => $v)
			{
				$newarr[$v['uid']] = $v['list'];
			}
			$_pm['mem']->set(array('k'=>'db_blacklist','v'=>$newarr));
			liststr1($blacklist);
		}
		else die('该用户已经被您加入黑名单了！');
	}
	else
	{
		$_pm['mysql'] -> query("INSERT INTO blacklist (uid,list) VALUES ({$_SESSION['id']},'{$fname}');");
		$_pm['mem']->del('db_blacklist');//重新加载内存数据
		$ret2 = $_pm['mysql']->getRecords("select uid,list from blacklist");
		foreach($ret2 as $k => $v)
		{
			$newarr[$v['uid']] = $v['list'];
		}
		$_pm['mem']->set(array('k'=>'db_blacklist','v'=>$newarr));
	}
	liststr1($fname);
	echo $err;
}
else if($_REQUEST['op'] == 'deleteblacklist')//从黑名单取消
{
	$err = 10;
	$fret = $_pm['mysql']->getOneRecord("SELECT nickname FROM player WHERE nickname='{$tu}'");//找到要取消黑名单的用户
	if(!is_array($fret))
	{
		die("请正确输入您要从黑名单取消的角色名！");
	}
	$fname = $fret['nickname'];
	$blacklist = $_pm['mysql']->getOneRecord("SELECT list FROM blacklist WHERE uid='{$_SESSION['id']}'");
	//$arr = explode(',',$blacklist[$_SESSION['id']]);
	if(!is_array($blacklist))
	{
		die("该用户不在您的黑名单中！");
	}
	
	$arr = explode(',',$blacklist['list']);
	$s = array_search($fname,$arr);
	if ($s === FALSE)
	{
		die('该用户不在您的黑名单中！');
	}
	else
	{
		$blacklist = 0;
		foreach($arr as $k => $v)
		{
			if ($v == $fname) continue;
			if(empty($blacklist)){
				$blacklist = $v;
			}else{
				$blacklist .= ','.$v;
			}
		}
		$blacklists = $blacklist;
	}
	if(empty($blacklists)){
		$_pm['mysql'] -> query("delete from blacklist where uid = {$_SESSION['id']}");
	}else{
		$_pm['mysql'] -> query("UPDATE blacklist SET list = '{$blacklists}' WHERE uid = {$_SESSION['id']}");
	}
	$_pm['mem']->del('db_blacklist');//重新加载内存数据
	$ret2 = $_pm['mysql']->getRecords("select uid,list from blacklist");
	foreach($ret2 as $k => $v)
	{
		$newarr[$v['uid']] = $v['list'];
	}
	$_pm['mem']->set(array('k'=>'db_blacklist','v'=>$newarr));
	if(empty($blacklists)){
		echo $err;
	}else
	{
		liststr1($blacklists);
	}
}


function liststr($friendlist)
{
	if (empty($friendlist)) return false;
	$arr = explode(',',$friendlist);
	if(!is_array($arr)) $arr[0]=$friendlist;
	$f = '';
	foreach($arr as $k => $v)
	{
		$f .= "<span style='cursor:pointer;display:block;' onclick=\"chat('{$v}');\"><u>".$v . '</u></span>';
	}
	header('Content-Type:text/html;charset=GBK');
	die('#'.$f);
}
function liststr1($friendlist)
{
	if (empty($friendlist)) return false;
	$arr = explode(',',$friendlist);
	if(!is_array($arr)) $arr[0]=$friendlist;
	$f = '';
	foreach($arr as $k => $v)
	{
		if(empty($v)){
			continue;
		}
		$f .= "<span style='cursor:pointer;display:block;' onclick=\"blacks('{$v}');\"><u>".$v . '</u></span>';
	}
	header('Content-Type:text/html;charset=GBK');
	die('#'.$f);
}
?>
