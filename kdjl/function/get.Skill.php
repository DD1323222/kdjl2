<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.19
*@Update Date: 2008.05.27
*@Usage: study skill for user bb.
*@Memo:
	1) Get jn
	2) had study?
	3) had jnbook and require level is ok?
	4) study
	5) clear jnbook.
	6) complete.
*/
require_once('../config/config.game.php');
secStart($_pm['mem']);

$id  = intval($_REQUEST['id']);  // table: skillsys => id
$bid = intval($_REQUEST['pid']); // pets id

if ($_pm['user']->check(array('int' => $bid, 'int' => $id)) === false) die('0');

$user	= $_pm['user']->getUserById($_SESSION['id']);
$sp 	= $_pm['user']->getUserPetSkillById($_SESSION['id']);
$bag	= $_pm['user']->getUserBagById($_SESSION['id']);
$bb		= $_pm['user']->getUserPetById($_SESSION['id']);

// Get props id => pid
/*$skrs= $_pm['mem']->dataGet(array('k' => MEM_SKILLSYS_KEY, 
					     'v' => "if(\$rs['id'] == '{$id}') \$ret=\$rs;"
				 ));*/

$memskillsysid = unserialize($_pm['mem']->get('db_skillsysid'));
$skrs = $memskillsysid[$id];


if (is_array($skrs))
{
	$wp = $skrs;
	$pid = $wp['pid'];
	// Check pets whether had study the skill.

	$had = false;
	foreach ($sp as $k => $v)
	{
		if ($v['uid'] == $_SESSION['id'] && $v['bid'] == $bid && $v['sid'] == $id)
		{
			$had = true;
			break;
		}
	}
	if ($had === true) die('10');	
	
	// Check userbag whether have jnbook.
	unset($k, $v);
	$book = false;
	foreach ($bag as $k => $v)
	{
		if ($v['uid'] == $_SESSION['id'] && $v['pid'] == $skrs['pid'])
		{
			$book = $v;
			break;
		}
	}
    if (!is_array($book)) die('2');

	// Check level of bb and jnbook.
	$level = false;
	foreach ($bb as $k => $v)
	{
		if ($v['uid'] == $_SESSION['id'] && $v['id'] == $bid)
		{
			$level = $v;
			break;
		}
	}

	$larr = split(',', $book['requires']);
	$bl	  = explode(':', $larr[0]);

	if ($level['level'] < $bl[1]) die('3');
	
	// Check wx.
	if ($level['wx'] != $skrs['wx'] && $skrs['wx'] != 0) die('4');	
	//判断是否是该宠物的特有技能
	if( isset($larr[2]) && !empty($larr[2]) )	//字段存在
	{
		$only = explode(':', $larr[2]);
		$sql = " SELECT bb.id FROM bb,userbb WHERE userbb.id = {$bid} AND userbb.name = bb.name "; 
		$bb_id = $_pm['mysql']->getOneRecord($sql);
		if($only[1] != $bb_id['id'] )
		{
			die('11');
		}
	}
	
	//$newid = mem_get_autoid($m, 'db_memorder', 'skill');
	// Study the skill,update bb's skill and bb's skillist.
	// (bid,name,level,vary,wx,value,plus,img,uhp,ump)
	$ack  = split(",", $wp['ackvalue']);
	$plus = split(",", $wp['plus']);
	$imgeft = split(",",$wp['imgeft']);
	$uhp  = split(",", $wp['uhp']);
	$ump  = split(",", $wp['ump']);
	
	//效果持续时间（同装备）的技能7.29
	$imgarr = explode(":",$imgeft[0]);
	if(!empty($imgarr[0]))
	{
		switch($imgarr[0])
		{
			case 'addmc':
				$num = str_replace('%','',$imgarr[1]);
				$addmc = round($level['mc'] * $num/100) + $level['mc'];
				$sql = 'UPDATE userbb SET mc = '.$addmc.' WHERE id = '.$bid.'';
				$_pm['mysql'] -> query($sql);
				$imgeft = 0;
				break;
			case 'addac':
				$num = str_replace('%','',$imgarr[1]);
				$addac = round($level['ac'] * $num/100) + $level['ac'];
				$sql = 'UPDATE userbb SET ac = '.$addac.' WHERE id = '.$bid.'';
				$_pm['mysql'] -> query($sql);
				$imgeft = 0;
				break;
			case 'addhp':
				$num = str_replace('%','',$imgarr[1]);
				$addsrchp = round($level['srchp'] * $num/100) + $level['srchp'];
				$sql = 'UPDATE userbb SET srchp = '.$addsrchp.' WHERE id = '.$bid.'';
				$_pm['mysql'] -> query($sql);
				$imgeft = 0;
				break;
			case 'addmp':
				$num = str_replace('%','',$imgarr[1]);
				$addsrcmp = round($level['srcmp'] * $num/100) + $level['srcmp'];
				$sql = 'UPDATE userbb SET srcmp = '.$addsrcmp.' WHERE id = '.$bid.'';
				$_pm['mysql'] -> query($sql);
				$imgeft = 0;
				break;
			default:
				$num = 0;
				$addsrcmp = 0;
				break;
		}
	}
	
	
	$_pm['mysql']->query("INSERT INTO skill(bid,sid,name,level,vary,wx,value,plus,img,uhp,ump)
				VALUES(
					   '{$bid}',	
					   '{$id}',
					   '{$skrs['name']}',
					   '1',
					   '{$skrs['vary']}',
					   '{$skrs['wx']}',
					   '{$ack[0]}',
					   '{$plus[0]}',
					   '{$imgeft[0]}',
					   '{$uhp[0]}',
					   '{$ump[0]}'
				      ) 
			  ");
	$_pm['mysql']->query("UPDATE userbb
				   SET skillist=concat(skillist,',','{$id}:1')
				 WHERE uid={$_SESSION['id']} and id={$bid}
			  ");

	// clear jnbook.
	$_pm['mysql']->query("DELETE FROM userbag
				 WHERE uid={$_SESSION['id']} and pid={$skrs['pid']} and id={$book['id']}
			  ");

	//$_pm['user']->updateMemUserbb($_SESSION['id']);
	//$_pm['user']->updateMemUsersk($_SESSION['id']);
	//$_pm['user']->updateMemUserbag($_SESSION['id']);
	die('1');
}
$_pm['mem']->memClose();
?>