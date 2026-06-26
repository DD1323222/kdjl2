<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.05.22
*@Usage: Userinfo
*@Note: none
*/
require_once('../config/config.game.php');
//if ($_SESSION['nickname']!='GM') die('关闭调试！');

$user		= $_pm['user']->getUserById($_SESSION['id']);
$petsAll	= $_pm['user']->getUserPetById($_SESSION['id']);
$backObj = array();
$backObj['user'] = array();
$backObj['user']['nick'] = iconv("gbk","utf-8",$user['name']);
$backObj['user']['vip'] = $user['vip'];
$backObj['user']['gold'] = $user['money'];
$backObj['user']['yb'] = $user['yb'];
$backObj['user']['head'] = $user['headimg'];
$backObj['user']['fightbb'] = $user['mbid'];
$backObj['user']['fightName'] = '';
$backObj['pet'] = array();
foreach($petsAll as $info)
{
	if($info['muchang'] == 0)
	{
		$petArr = array("id"=>$info['id'],"name"=>iconv("gbk","utf-8",$info['name']),"level"=>$info['level'],"pet_id"=>0);
		$sql = "SELECT id FROM bb WHERE name = '{$info['name']}'";
		$res = $_pm['mysql']->getOneRecord($sql);
		$petArr['pet_id'] = $res['id'];
		$backObj['pet'][] = $petArr;
		if($info['id'] == $backObj['user']['fightbb'])
		{
			$backObj['user']['fightName'] = iconv("gbk","utf-8",$info['name']);
		}
	}
}
echo "OK".json_encode($backObj);
?>;
?>