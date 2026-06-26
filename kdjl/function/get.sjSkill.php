<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.19
*@Update Date: 2008.05.27
*@Usage: sj skill of user bb.
*@Memo:
	1) Get jn
	2) had study?
	3) had sj props and require level is ok?
	4) sj
	5) clear sj props.
	6) complete.
*/
require_once('../config/config.game.php');
secStart($_pm['mem']);

$id = intval($_REQUEST['id']); // table: skillsys => id
$bid =intval($_REQUEST['pid']); // pets id

if ($_pm['user']->check(array('int' => $id, 'int' => $bid)) === false) die('0');

//$user	= $_pm['user']->mgetUserById();
$sp 	= $_pm['user']->getUserPetSkillById($_SESSION['id']);
$bag	= $_pm['user']->getUserBagById($_SESSION['id']);
$bb		= $_pm['user']->getUserPetById($_SESSION['id']);

// Get props id => pid

$memskillsysid = unserialize($_pm['mem']->get('db_skillsysid'));
$wp = $memskillsysid[$id];
/*$wp= $_pm['mem']->dataGet(array('k' => MEM_SKILLSYS_KEY, 
					   'v' => "if(\$rs['id'] == '{$id}') \$ret=\$rs;"
				 ));*/
if (is_array($wp))
{
	$pid = $wp['pid'];
	// Check pets whether had study the skill.
	$had = false;
	foreach ($sp as $k => $v)
	{
		if ($v['uid'] == $_SESSION['id'] && $v['bid'] == $bid && $v['sid'] == $id)
		{
			$had = $v;
			break;
		}
	}
	if ($had === false) die('0');	

	//############################################################
	// have a bag.
	// Check userbag whether have jnbook.
	$book = false;
	foreach ($bag as $k => $v)
	{
		if ($v['uid'] == $_SESSION['id'] && $v['pid'] == '733' && $v['sums']>0 && $wp['vary'] != 4)//非被动技能
		{
			$book = $v;
			break;
		}
		else if($v['uid'] == $_SESSION['id'] && $v['pid'] == '1666' && $v['sums']>0 && $wp['vary'] == 4)
		{
			$book = $v;
			break;
		}
	}
    if (!is_array($book)) die('2');	

	// Check level of jnbook.
	$level = false;
	foreach ($bb as $k => $v)
	{
		if ($v['uid'] == $_SESSION['id'] && $v['id'] == $bid)
		{
			$level = $v;
			break;
		}
	}

	$larr = split(',', $wp['requires']);
	$ack  = split(',', $wp['ackvalue']);
	$plus = split(',', $wp['plus']);
	$uhp  = split(',', $wp['uhp']);
	$ump  = split(',', $wp['ump']);
	$img  = split(',', $wp['imgeft']);

	// 升级：获得BB当前等级，技能等级。判断是否可以升级到下一级
	################最多升级到10    10.09 ############################
	if ($had['level']>=10) 
	{
		die('4');
	}

	$cl = $had['level'];		// current level
	$nl = $cl+1;				// next level.
	$rl = $larr[$cl];			// require bb level
	if ($level['level'] < $rl) die('3');
	
	
	$had['level']	=	$nl;			// 提升等级。
	$had['value']	=	$ack[$cl];		// 提升攻击。
	$had['plus']	=	$plus[$cl];		// 提升附加效果。
	$had['uhp']		=	$uhp[$cl];		//	消耗hp
	$had['ump']		=	$ump[$cl];		// 消耗mp
	$had['img']		=	$img[$cl];
	
	//效果持续时间（同装备）的技能7.29
	$imgarr = explode(":",$had['img']);
	if(!empty($imgarr[0]))
	{
		switch($imgarr[0])
		{
			case 'addmc':
				$num = str_replace('%','',$imgarr[1]);
				$addmc = round($level['mc'] * $num/100) + $level['mc'];
				$sql = 'UPDATE userbb SET mc = '.$addmc.' WHERE id = '.$bid.'';
				$_pm['mysql'] -> query($sql);
				$had['img'] = 0;
				break;
			case 'addhits':
                                $num = str_replace('%','',$imgarr[1]);
                                $addhits = round($level['hits'] * $num/100) + $level['hits'];
                                $sql = 'UPDATE userbb SET hits = '.$addhits.' WHERE id = '.$bid.'';
                                $_pm['mysql'] -> query($sql);
                                $had['img'] = 0;
                                break;
			case 'addac':
				$num = str_replace('%','',$imgarr[1]);
				$addac = round($level['ac'] * $num/100) + $level['ac'];
				$sql = 'UPDATE userbb SET ac = '.$addac.' WHERE id = '.$bid.'';
				$_pm['mysql'] -> query($sql);
				$had['img'] = 0;
				break;
			case 'addhp':
				$num = str_replace('%','',$imgarr[1]);
				$addsrchp = round($level['srchp'] * $num/100) + $level['srchp'];
				$sql = 'UPDATE userbb SET srchp = '.$addsrchp.' WHERE id = '.$bid.'';
				$_pm['mysql'] -> query($sql);
				$had['img'] = 0;
				break;
			case 'addmp':
				$num = str_replace('%','',$imgarr[1]);
				$addsrcmp = round($level['srcmp'] * $num/100) + $level['srcmp'];
				$sql = 'UPDATE userbb SET srcmp = '.$addsrcmp.' WHERE id = '.$bid.'';
				$_pm['mysql'] -> query($sql);
				$had['img'] = 0;
				break;
			default:
				$num = 0;
				$addsrcmp = 0;
				break;
		}
	}
	
	
	$_pm['mysql']->query("UPDATE skill
			       SET level='{$had['level']}',
					   value='{$had['value']}',
					   plus='{$had['plus']}',
					   uhp='{$had['uhp']}',
					   ump='{$had['ump']}',
					   img='{$had['img']}'
				 WHERE bid='{$bid}' and sid='{$id}'
			  ");
	// clear jnbook.
	$_pm['mysql']->query("UPDATE userbag
				   SET sums=abs(sums-1)
				 WHERE uid='{$_SESSION['id']}' and id='{$book['id']}' and sums>0
			  ");
	
	//$_pm['user']->updateMemUsersk($_SESSION['id']);
	//$_pm['user']->updateMemUserbag($_SESSION['id']);
	die('1');
}else die('0');
$_pm['mem']->memClose();
unset($db);
?>
