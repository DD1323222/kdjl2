<?php
/**
 * the pet's growth ranking
 *
 * @date: 2009-03-17
 * @author:Zheng.Ping
 */


define("MEM_CZLTOP_KEY", "growthrankingtop");
require_once('../config/config.game.php');

secStart($_pm['mem']);

define('SORT_NUM', 50);

/**
 * sort the growth ranking returned from db in reverse order
 *
 * @param array $records
 * @return array
 */
function sort_by_czl($records)
{
    $orderd = array();
    if (is_array($records) && !empty($records)) {
        $tmp = array();

        foreach($records as $r) {
            $tmp[] = $r['jprestige'];
        }
        arsort($tmp);
        foreach($tmp as $k => $v) {
            $orderd[] = $records[$k];
        }
        $orderd = array_slice($orderd, 0, SORT_NUM);
    }

    return $orderd;
}

$user = $_pm['user']->getUserById($_SESSION['id']);
$czltop = unserialize($_pm['mem']->get(MEM_CZLTOP_KEY));
if (!is_array($czltop) || $czltop['time'] + 3600 < time()) 
{
    $czltoprs = $_pm['mysql']->getRecords("SELECT name, czl as jprestige, username as nickname FROM `userbb` 
        WHERE czl IS NOT NULL
        ORDER BY czl+0 DESC 
        LIMIT 0,150");
    $czltoprs = sort_by_czl($czltoprs);
	if (!is_array($czltoprs)) $czlpub = '排行榜为空!';
	else 
	{
		$czltoprs['time'] = time();
		$_pm['mem']->set(array('k' =>MEM_CZLTOP_KEY, 'v' => $czltoprs ));
		$czltop = $czltoprs;
		unset($czltoprs);
	}
}

$czlpos = 1;
$k = 0;
$rs = "";
if(is_array($czltop))
{
	foreach ($czltop as $k => $rs)
	{
		if(is_array($rs))
		{
			$czlpub .= '<tr>
			  	<td width="15%">'. ($czlpos++) .'</td>
			 	 <td width="30%" >'. $rs['name'] .'</td>
			 	 <td width="25%" style="text-align:center;">'. $rs['jprestige'] .'</td>
                 <td width="" >'. $rs['nickname'] .'</td>
				</tr>';
		}
	}
}


$taskword= taskcheck(intval($user['task'])==0?1:$user['task'],8);
//
$_pm['mem']->memClose();
unset($db);

//@Load template.
$tn = $_game['template'] . 'tpl_growthranking.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);
	
	$src = array(
				 '#word#',
				 '#publiclist#'
				);
	$des = array(
				 $taskword,
				 $czlpub
				);
	$public = str_replace($src, $des, $tpl);
}

// gzip echo. if maybe.
ob_start('ob_gzip');
echo $public;
ob_end_flush();
?>;
?>