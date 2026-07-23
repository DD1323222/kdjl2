<?php
@session_start();
require_once('../config/config.game.php');
ob_start();
if(!isset($_SESSION['manager']) || $_SESSION['manager'] != 1)
{
	//die();
}
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.22
*@Usage:New user register.
*@Note: none
*/

require_once("loginCheck.php");
//require_once("../function/cwords.php");

function grantNewPlayerRewards($db, $uid)
{
	$rewardCounts = array(1308 => 15, 1241 => 2, 912 => 10, 1039 => 1, 1992 => 5, 2493 => 1, 2047 => 1);
	$rows = $db->getRecords('SELECT id,sell,vary FROM props WHERE id IN (' . implode(',', array_keys($rewardCounts)) . ')');
	if (!is_array($rows) || count($rows) !== count($rewardCounts)) return false;
	$values = array();
	$found = array();
	$now = time();
	foreach ($rows as $row)
	{
		$pid = intval($row['id']);
		if (!isset($rewardCounts[$pid])) continue;
		$found[$pid] = true;
		$count = intval($rewardCounts[$pid]);
		$sell = intval($row['sell']);
		$vary = intval($row['vary']);
		if ($vary === 1)
		{
			$values[] = '(' . intval($uid) . ",{$pid},{$sell},{$vary},{$count},{$now})";
		}
		else
		{
			for ($i = 0; $i < $count; $i++) $values[] = '(' . intval($uid) . ",{$pid},{$sell},{$vary},1,{$now})";
		}
	}
	if (count($found) !== count($rewardCounts) || empty($values)) return false;
	return $db->query('INSERT INTO userbag(uid,pid,sell,vary,sums,stime) VALUES ' . implode(',', $values)) ? true : false;
}

function registerFail($message)
{
	global $_pm;
	if(isset($_pm['mysql'])) $_pm['mysql']->query('ROLLBACK');
	die($message);
}

$login = '0';
$_gm = array('name' => array());
$battletimearr = kdjlSafeMemValue($_pm['mem']->get(MEM_TIME_KEY), array());
if (is_array($battletimearr))
{
	foreach($battletimearr as $v)
	{
		if($v['titles'] == "login")
		{
			$login = $v['days'];
			break;
		}
	}
}
if($login != "0")
{
	$welcome = memContent2Arr("db_welcome",'code');
	$gm_in_mem = $welcome['admin']['contents'];
	if(!empty($gm_in_mem))
	{
	$_gm['name'] = array_merge($_gm['name'], preg_split("/(?:,|;|\\xEF\\xBC\\x8C|\\xEF\\xBC\\x9B)+/", $gm_in_mem, -1, PREG_SPLIT_NO_EMPTY));
	}
	/*if(!in_array($_SESSION['username'],$_gm['name']) )
	{
		die('维护中，暂停注册！');
	}*/
}
//die('维护中，暂停注册！');

$requestBname = (isset($_REQUEST['bname']) && !is_array($_REQUEST['bname'])) ? $_REQUEST['bname'] : '';
$requestBc = (isset($_REQUEST['bc']) && !is_array($_REQUEST['bc'])) ? $_REQUEST['bc'] : '';
if($requestBname!='' && $requestBc!='')
{
	require_once("../config/config.game.php");

	$p['bname']=preg_replace("/[\s]/",'',trim($requestBname));
	if(!kdjlValidNickname($p['bname']))
	{
		die('角色名格式或长度不符！');
	}
	//echo $p['bname']."  5<br/>\r\n";
	require_once(dirname(__FILE__).'/../socketChat/badWord.php');
	$blockedWord = kdjlFindBlockedWord($p['bname']);
	if($blockedWord !== false)
	{
		die('输入的角色名中('.$blockedWord.')为禁止使用的词！');
	}
	//echo $p['bname']."  2<br/>\r\n";
	$msg =$p['bname'];
	//echo $p['bname']."  4<br/>\r\n";
	$_pm['mysql'] = new mysql();
	$tu=$p['bname'];
	$tuSql = $_pm['mysql']->escape($tu);
	//$tu	= mysql_real_escape_string(preg_replace("/[ 	 _\s　	]/",'',trim($p['bname'])));
	//$tu	= preg_replace("/[\s_]/",'',$p['bname']);

	$u	= (isset($_REQUEST['username']) && !is_array($_REQUEST['username'])) ? trim($_REQUEST['username']) : '';
	if(!kdjlValidAccountName($u))
	{
		die('账号格式错误！');
	}
	$pass = (isset($_REQUEST['pass']) && !is_array($_REQUEST['pass'])) ? $_REQUEST['pass'] : '';
	if($pass === '' || strlen($pass) > 64)
	{
		die('密码格式错误！');
	}
	$uSql = $_pm['mysql']->escape($u);
	//$bc	= $p['bc'];
	$bc	= intval($requestBc);
	$rs = $_pm['mysql']->getOneRecord("SELECT id
							   FROM player
							  WHERE nickname='{$tuSql}'");

	if (!is_array($rs))
	{
		$rs = $_pm['mysql']->getOneRecord("SELECT id
							   FROM player
							  WHERE name='{$uSql}'");
	}
	$requestSex = (isset($_REQUEST['sex']) && !is_array($_REQUEST['sex'])) ? intval($_REQUEST['sex']) : 0;
	if($requestSex !== 1 && $requestSex !== 2) die('请选择角色性别！');
	$p['sex'] = $requestSex==1?'帅哥':'美女';

	if (is_array($rs))
	{
		die('角色已经存在或者您已经有一个角色!');
	}
	else
	{
		$p['head'] = (isset($_REQUEST['head']) && !is_array($_REQUEST['head'])) ? intval($_REQUEST['head']) : 0;
		if($p['head'] < 1 || $p['head'] > 6) die('请选择有效头像！');
		if($bc < 1 || $bc > 5) $bc=1;
		//die('注册成功！调试');
		// insert user data.

		if(!$_pm['mysql']->query('START TRANSACTION'))
		{
			die('注册失败：系统繁忙。');
		}
		if(!$_pm['mysql']->query("INSERT INTO player(name,secret,nickname,sex,regtime,lastvtime,money,yb,headimg,task,maxbag,maxbase,maxmc)
				    VALUES('{$uSql}','".md5($pass)."','{$tuSql}','{$p['sex']}',".time().",".time().",0,0,'{$p['head']}','',500,300,80)
				  "))
		{
			if(mysql_errno($_pm['mysql']->getConn()) == 1062) registerFail('角色已经存在或者您已经有一个角色!');
			registerFail('注册失败：玩家数据创建失败。');
		}
		$newUid = intval($_pm['mysql']->last_id());
		if($newUid < 1)
		{
			registerFail('注册失败：玩家数据创建失败。');
		}
		// insert user bb init data.
		switch($bc)
		{
			case 1: $tbc = 1;break;
			case 2: $tbc = 13;break;
			case 3: $tbc = 23;break;
			case 4: $tbc = 32;break;
			case 5: $tbc = 42;break;
			//default:$tbc = 1;$bc=1;
			default:$tbc = 1;break;
		}
		$bb = $_pm['mysql']->getOneRecord("SELECT * FROM bb WHERE id={$tbc} LIMIT 0,1");

		if (is_array($bb))
		{
			$czl = getCzl($bb['czl']);
			$uinfo = array('id' => $newUid, 'nickname' => $tu);
			$uinfoNicknameSql = $_pm['mysql']->escape($uinfo['nickname']);
			$bbNameSql = $_pm['mysql']->escape($bb['name']);
			$bbSkillistSql = $_pm['mysql']->escape($bb['skillist']);
			$bbImgStandSql = $_pm['mysql']->escape($bb['imgstand']);
			$bbImgAckSql = $_pm['mysql']->escape($bb['imgack']);
			$bbImgDieSql = $_pm['mysql']->escape($bb['imgdie']);
			$bbKxSql = $_pm['mysql']->escape($bb['kx']);
			$bbRemakeLevelSql = $_pm['mysql']->escape($bb['remakelevel']);
			$bbRemakeIdSql = $_pm['mysql']->escape($bb['remakeid']);
			$bbRemakePidSql = $_pm['mysql']->escape($bb['remakepid']);


			if(!$_pm['mysql']->query("INSERT INTO userbb(name,uid,username,level,wx,ac,mc,srchp,hp,srcmp,mp,skillist,stime,nowexp,
									lexp,imgstand,imgack,imgdie,hits,miss,speed,kx,remakelevel,remakeid,remakepid,czl,headimg,cardimg,effectimg,old_bid)
					    VALUES('{$bbNameSql}','{$uinfo['id']}','{$uinfoNicknameSql}','1','".intval($bb['wx'])."',
						       '".intval($bb['ac'])."','".intval($bb['mc'])."','".intval($bb['hp'])."','".intval($bb['hp'])."','".intval($bb['mp'])."','".intval($bb['mp'])."','{$bbSkillistSql}',unix_timestamp(),
							  '".intval($bb['nowexp'])."','55','{$bbImgStandSql}','{$bbImgAckSql}','{$bbImgDieSql}',
							   '".intval($bb['hits'])."','".intval($bb['miss'])."','".intval($bb['speed'])."','{$bbKxSql}','{$bbRemakeLevelSql}',
							   '{$bbRemakeIdSql}','{$bbRemakePidSql}','{$czl}','t{$tbc}.gif','k{$tbc}.gif','q{$tbc}.gif','{$tbc}')
					  "))
			{
				registerFail('注册失败：初始宠物创建失败。');
			}
			$newPetId = intval($_pm['mysql']->last_id());
			if($newPetId < 1)
			{
				registerFail('注册失败：初始宠物创建失败。');
			}
			$ids = array('id' => $newPetId);

			$arr = explode(":", $bb['skillist']);
			if(count($arr) < 2 || intval($arr[0]) < 1 || intval($arr[1]) < 1)
			{
				registerFail('注册失败：初始技能配置错误。');
			}
			// Get jn info.
			$jn = $_pm['mysql']->getOneRecord("SELECT *
									   FROM skillsys
									  WHERE id = ".intval($arr[0]));
			if(!is_array($jn))
			{
				registerFail('注册失败：初始技能读取失败。');
			}
			$ack  = explode(",", $jn['ackvalue']);
			$plus = explode(",", $jn['plus']);
			$uhp  = explode(",", $jn['uhp']);
			$ump  = explode(",", $jn['ump']);
			if(!isset($ack[0]) || !isset($plus[0]) || !isset($uhp[0]) || !isset($ump[0]))
			{
				registerFail('注册失败：初始技能配置错误。');
			}
			$skillNameSql = $_pm['mysql']->escape($jn['name']);
			$skillVarySql = $_pm['mysql']->escape($jn['vary']);
			$skillWxSql = $_pm['mysql']->escape($jn['wx']);
			$skillAckSql = $_pm['mysql']->escape($ack[0]);
			$skillPlusSql = $_pm['mysql']->escape($plus[0]);
			$skillImgSql = $_pm['mysql']->escape($jn['img']);
			$skillUhpSql = $_pm['mysql']->escape($uhp[0]);
			$skillUmpSql = $_pm['mysql']->escape($ump[0]);
			// Insert userbb jn.

			if(!$_pm['mysql']->query("INSERT INTO skill(bid,name,level,vary,wx,value,plus,img,uhp,ump)
						VALUES('{$ids['id']}', '{$skillNameSql}','".intval($arr[1])."','{$skillVarySql}','{$skillWxSql}','{$skillAckSql}','{$skillPlusSql}','{$skillImgSql}','{$skillUhpSql}','{$skillUmpSql}')
					  "))
			{
				registerFail('注册失败：初始技能创建失败。');
			}
			if(!$_pm['mysql']->query("UPDATE player SET mbid = {$ids['id']} WHERE id={$uinfo['id']}"))
			{
				registerFail('注册失败：主战宠物设置失败。');
			}
			if (!$_pm['mysql']->query("INSERT INTO player_ext(uid,bbshow,new_guide_step) VALUES({$uinfo['id']},5,-1)"))
			{
				registerFail('注册失败：玩家扩展数据创建失败。');
			}
			if (!grantNewPlayerRewards($_pm['mysql'], $uinfo['id']))
			{
				registerFail('注册失败：新手奖励发放失败。');
			}
			if(!$_pm['mysql'] -> query("INSERT INTO `lock`(uid,lockvalue) values({$uinfo['id']},0)"))
			{
				registerFail('注册失败：玩家锁数据创建失败。');
			}
			if(!$_pm['mysql']->query('COMMIT'))
			{
				registerFail('注册失败：数据提交失败。');
			}
			session_regenerate_id(true);
			$_SESSION['username'] = 	$u;
			$_SESSION['name'] = $u;
			$_SESSION['nickname'] = $tu;
			$_SESSION['id'] = $newUid;
			$_SESSION['LoginApiState'] = 1;
			$_SESSION['game_server_flag'] = GAME_SERVER_FLAG;

			$_pm['mem']->set(array('k'=>MEM_SYSWORD_KEY,
					      'v'=>'欢迎新'.$p['sex'].$tu.'携带宝宝 '.$bb['name'].' 进入口袋精灵世界！'));
###########################网易用户通知  2011-3-11 薛原####################################
			$httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
			$dom = explode('.',$httpHost);
			die("OK1");
		}
		else
		{
			$_pm['mysql']->query('ROLLBACK');
			die('注册失败！！！\n请联系客服！');
		}
	}
}
else
{
	die("请选择头像和宝宝类型！");
}

?>
