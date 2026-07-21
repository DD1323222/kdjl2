<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.01
*@Update Date: 2008.07.14
*@Usage: Shop main ui
*@Note: none
*/
session_start();
require_once('../config/config.game.php');

secStart($_pm['mem']);

function qyModHtml($value)
{
	return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function qyModJsDouble($value)
{
	$value = str_replace("\\", "\\\\", (string)$value);
	$value = str_replace('"', '\\"', $value);
	$value = str_replace(array("\r", "\n"), array("\\r", "\\n"), $value);
	return $value;
}

$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');
$mlph = '';
$mybag = '';
$shop = '';
$mlarr = $_pm['mysql'] -> getRecords('SELECT nickname,ml FROM player,player_ext WHERE player.id = player_ext.uid AND ml > 0 ORDER BY ml DESC limit 5');
if(empty($mlarr)){
	$mlph = '<tr>
                <td height="25" colspan="3" align="center" valign="middle" class="zi09">魅力排行当前为空</td>
                </tr>';
}else{
	$i = 3;
	foreach($mlarr as $v){
		if(!is_array($v)) continue;
		$nickname = isset($v['nickname']) ? qyModHtml($v['nickname']) : '';
		$ml = isset($v['ml']) ? intval($v['ml']) : 0;
		$mlph .= '<tr>
                <td height="25" align="center" valign="middle"><img src="../images/qy0'.$i.'.gif" width="15" height="15"></td>
                <td align="center" valign="middle" class="zi09">'.$nickname.'</td>
                <td align="center" valign="middle" class="zi09">'.$ml.'</td>
              </tr>';
		$i++;
	}
}
$v = '';
$mlprops = $_pm['mysql'] -> getRecords("SELECT userbag.id as bid,userbag.sums,props.effect,props.name
                                          FROM userbag,props
                                         WHERE userbag.pid = props.id
                                           AND userbag.uid = {$uid}
                                           AND userbag.sums > 0
                                           AND userbag.zbing = 0
                                           AND (userbag.cantrade IS NULL OR userbag.cantrade <> 3)
                                           AND props.varyname = 17");
if(empty($mlprops)){
	$mybag = '<tr>
            <td height="23" colspan="2" align="center" class="zi09">没有此类道具</td>
            </tr>';
}else{
	foreach($mlprops as $v){
	if(!is_array($v)) continue;
	if(!isset($v['effect'])) $v['effect'] = '';
	if(!isset($v['name'])) $v['name'] = '';
	if(!isset($v['sums'])) $v['sums'] = 0;
	$mearr = explode(':',$v['effect']);
	if(!isset($mearr[1])) $mearr[1] = 0;
	$propNameHtml = qyModHtml($v['name']);
	$propNameJs = qyModHtml(qyModJsDouble($v['name']));
	$propSums = intval($v['sums']);
	$mlValue = intval($mearr[1]);
	$mybag .= '<tr>
            <td width="70%" height="23" align="left" class="zi09"><span style="cursor:pointer" onclick=\'giveprops("'.$propNameJs.'",'.$propSums.');\'>'.$propNameHtml.' 魅力：+'.$mlValue.'</span></td>
            <td height="23" align="left" class="zi09">'.$propSums.'</td>
          </tr>';

	}
}

$nowml = $_pm['mysql'] -> getOneRecord("SELECT ml FROM player_ext WHERE uid = {$uid}");
$tn = $_game['template'] . 'tpl_qy.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);

	$src = array('#mlph#',
				 '#mybag#',
				 '#nowml#'
				);
	$des = array($mlph,
				 $mybag,
				 (is_array($nowml) && isset($nowml['ml']) ? intval($nowml['ml']) : 0)
				);
	$shop = str_replace($src, $des, $tpl);
}

// gzip echo. if maybe.
ob_start('ob_gzip');
echo $shop;
ob_end_flush();
?>
