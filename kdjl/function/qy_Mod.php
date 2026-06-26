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

$mlarr = $_pm['mysql'] -> getRecords('SELECT nickname,ml FROM player,player_ext WHERE player.id = player_ext.uid AND ml > 0 ORDER BY ml DESC limit 5');
if(empty($mlarr)){
	$mlph = '<tr>
                <td height="25" colspan="3" align="center" valign="middle" class="zi09">魅力排行当前为空</td>
                </tr>';
}else{
	$i = 3;
	foreach($mlarr as $v){
		$mlph .= '<tr>
                <td height="25" align="center" valign="middle"><img src="../images/qy0'.$i.'.gif" width="15" height="15"></td>
                <td align="center" valign="middle" class="zi09">'.$v['nickname'].'</td>
                <td align="center" valign="middle" class="zi09">'.$v['ml'].'</td>
              </tr>';
		$i++;
	}
}
$v = '';
$mlprops = $_pm['mysql'] -> getRecords("SELECT userbag.id as bid,userbag.sums,props.effect,props.name FROM userbag,props WHERE userbag.pid = props.id AND userbag.uid = {$_SESSION['id']} AND sums > 0 AND props.varyname = 17");
if(empty($mlprops)){
	$mybag = '<tr>
            <td height="23" colspan="2" align="center" class="zi09">没有此类道具</td>
            </tr>';
}else{
	foreach($mlprops as $v){
	$mearr = explode(':',$v['effect']);
	$mybag .= '<tr>
            <td width="70%" height="23" align="left" class="zi09"><span style="cursor:pointer" onclick=\'giveprops("'.$v['name'].'",'.$v['sums'].');\'>'.$v['name'].' 魅力：+'.$mearr[1].'</span></td>
            <td height="23" align="left" class="zi09">'.$v['sums'].'</td>
          </tr>';

	}
}

$nowml = $_pm['mysql'] -> getOneRecord("SELECT ml FROM player_ext WHERE uid = {$_SESSION['id']}");
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
				 $nowml['ml']
				);
	$shop = str_replace($src, $des, $tpl);
}

// gzip echo. if maybe.
ob_start('ob_gzip');
echo $shop;
ob_end_flush();
?>