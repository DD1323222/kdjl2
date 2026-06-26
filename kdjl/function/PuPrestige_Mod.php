<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.05.22
*@Usage: Public top (GongGao Bang.)
*@Note: none
*/

define(MEM_PRETOP_KEY, "pupublictop");
require_once('../config/config.game.php');

secStart($_pm['mem']);

$user = $_pm['user']->getUserById($_SESSION['id']);
$putop = unserialize($_pm['mem']->get(MEM_PRETOP_KEY));
if (!is_array($putop) || $putop['time']+3600<time()) 
{
	$putoprs = $_pm['mysql']->getRecords("SELECT name,jprestige,nickname 
								FROM player
							   WHERE (secid is null or secid = 0) and jprestige != 0
							   ORDER BY jprestige DESC
							   LIMIT 0,15
							");
	if (!is_array($putoprs)) $prepub = '排行榜为空!';
	else 
	{
		$putoprs['time'] = time();
		$_pm['mem']->set(array('k' =>MEM_PRETOP_KEY, 'v' => $putoprs ));
		$putop = $putoprs;
		unset($putoprs);
	}
}

$pos = 1;
if(is_array($putop))
{
	foreach ($putop as $k => $rs)
	{
		if(is_array($rs))
		{
			$prepub .= '<tr>
			  	<td width="15%">'. ($pos++) .'</td>
			 	 <td width="30%" >'. $rs['nickname'] .'</td>
			 	 <td width="30%" >'. $rs['jprestige'] .'</td>
				</tr>';
		}
	}
}


$taskword= taskcheck(intval($user['task'])==0?1:$user['task'],8);
//
$_pm['mem']->memClose();
unset($db);

$tn = $_game['template'] . 'tpl_puPrestige.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);
	
	$src = array(
				 '#word#',
				 '#prepubliclist#'
				);
	$des = array(
				 $taskword,
				 $prepub
				);
	$public = str_replace($src, $des, $tpl);
}

// gzip echo. if maybe.
ob_start('ob_gzip');
echo $public;
ob_end_flush();
?>;
?>