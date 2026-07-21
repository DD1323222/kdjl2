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

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('登录状态无效！');
$user		= $_pm['user']->getUserById($uid);
$petsAll	= $_pm['user']->getUserPetById($uid);
if(!is_array($user)) die('玩家数据错误！');
if(!is_array($petsAll)) $petsAll = array();
$backObj = array();
$backObj['user'] = array();
$backObj['user']['nick'] = isset($user['name']) ? $user['name'] : '';
$backObj['user']['vip'] = isset($user['vip']) ? $user['vip'] : 0;
$backObj['user']['gold'] = isset($user['money']) ? $user['money'] : 0;
$backObj['user']['yb'] = isset($user['yb']) ? $user['yb'] : 0;
$backObj['user']['head'] = isset($user['headimg']) ? $user['headimg'] : '';
$backObj['user']['fightbb'] = isset($user['mbid']) ? $user['mbid'] : 0;
$backObj['user']['fightName'] = '';
$backObj['pet'] = array();
foreach($petsAll as $info)
{
	if(!is_array($info)) continue;
	if(!isset($info['muchang']) || $info['muchang'] == 0)
	{
		$petId = isset($info['id']) ? intval($info['id']) : 0;
		$petName = isset($info['name']) ? $info['name'] : '';
		$templateId = isset($info['old_bid']) ? intval($info['old_bid']) : 0;
		$petArr = array("id"=>$petId,"name"=>$petName,"level"=>(isset($info['level']) ? $info['level'] : 0),"pet_id"=>$templateId);
		if($templateId < 1)
		{
			$sql = "SELECT id FROM bb WHERE name = ".$_pm['mysql']->quote($petName)." ORDER BY id LIMIT 1";
			$res = $_pm['mysql']->getOneRecord($sql);
			if(is_array($res) && isset($res['id'])) $petArr['pet_id'] = intval($res['id']);
		}
		$backObj['pet'][] = $petArr;
		if($petId == $backObj['user']['fightbb'])
		{
			$backObj['user']['fightName'] = $petName;
		}
	}
}
echo "OK".json_encode($backObj);
?>
