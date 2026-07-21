<?php
/**
@Usage: 正查玩家信息。
*/

require_once('../config/config.game.php');
secStart($_pm['mem']);

$requestUserName = (isset($_REQUEST['u']) && !is_array($_REQUEST['u'])) ? $_REQUEST['u'] : '';
$u = $_pm['mysql']->escape($requestUserName);
$uHtml = htmlspecialchars($requestUserName, ENT_QUOTES, 'UTF-8');
if ($u=='') die('玩家不存在！');

$rs = false;
$rsU = $_pm['mysql']->getOneRecord("SELECT nickname username,id,mbid from player
									WHERE nickname='{$u}'
								 ");
if (is_array($rsU))
{
	$rs = 	 $_pm['mysql']->getOneRecord("SELECT username as username,
										  name as name,
										  level as level,
										  czl as czl,
										  srchp as srchp,
										  srcmp as srcmp,
										  imgstand as effectimg
									 FROM userbb as b
									WHERE uid=".intval($rsU['id'])." and ".intval($rsU['mbid'])."=id
								 ");
}
else
{
	$rs = false;
}
if (is_array($rs))
{
	$effectimg = preg_replace('/[^A-Za-z0-9_.-]/', '', isset($rs['effectimg']) ? $rs['effectimg'] : '');
	$username = htmlspecialchars(isset($rs['username']) ? $rs['username'] : '', ENT_QUOTES, 'UTF-8');
	$petname = htmlspecialchars(isset($rs['name']) ? $rs['name'] : '', ENT_QUOTES, 'UTF-8');
	$level = intval(isset($rs['level']) ? $rs['level'] : 0);
	$czl = htmlspecialchars(isset($rs['czl']) ? $rs['czl'] : '', ENT_QUOTES, 'UTF-8');
	$srchp = intval(isset($rs['srchp']) ? $rs['srchp'] : 0);
	$srcmp = intval(isset($rs['srcmp']) ? $rs['srcmp'] : 0);
	$rs['effectimg'] = $effectimg;
	$rs['username'] = $username;
	$rs['name'] = $petname;
	$rs['level'] = $level;
	$rs['czl'] = $czl;
	$rs['srchp'] = $srchp;
	$rs['srcmp'] = $srcmp;
	echo '
			<table border=0>
			<tr><td><img src="'.IMAGE_SRC_URL.'/bb/'.$rs['effectimg'].'"></td>
			<td valign=middle style="font-size:12px;line-height:1.7;">
			主人：'.$rs['username'].'<br/>
			宠物：'.$rs['name'].'<br/>
			等级：'.$rs['level'].'<br/>
			成长：'.$rs['czl'].'<br/>
			生命：'.$rs['srchp'].'<br/>
			魔法：'.$rs['srcmp'].'<br/>
			</td></tr>
		  </table>';
}else if(is_array($rsU)){
	if(isset($rsU['username'])) $rsU['username'] = htmlspecialchars($rsU['username'], ENT_QUOTES, 'UTF-8');
	echo '
			<table border=0>
			<tr><td></td>
			<td valign=middle style="font-size:12px;line-height:1.7;">
			玩家：'.$rsU['username'].'<br/>
			该玩家没有设置主战宠物！
			</td></tr>
		  </table>';
}else{
		echo ''.$uHtml .'不存在！';
}
?>
