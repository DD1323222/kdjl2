<?php
/**
@Usage: 正查玩家信息。
*/

require_once('../config/config.game.php');
secStart($_pm['mem']);

$u = isset($_REQUEST['u']) ? $_REQUEST['u'] : '';
$u = iconv('gbk','iso-8859-2',$u);
$u = $_pm['mysql']->escape(isset($_REQUEST['u']) ? $_REQUEST['u'] : '');
if ($u=='') die('玩家不存在！');

$rs = $_pm['mysql']->getOneRecord("SELECT b.username as username,
										  b.name as name,
										  b.level as level,
										  b.czl as czl,
										  b.srchp as srchp,
										  b.srcmp as srcmp,
										  b.imgstand as effectimg
									 FROM userbb as b,player
									WHERE uid=player.id and player.nickname='{$u}' and player.mbid=b.id
								 ");
$rsU = $_pm['mysql']->getOneRecord("SELECT nickname username,id,mbid from player
									WHERE nickname='{$u}'
								 ");
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
if (is_array($rs))
{
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
}else if($rsU){
	echo '
			<table border=0>
			<tr><td></td>
			<td valign=middle style="font-size:12px;line-height:1.7;">
			玩家：'.$rsU['username'].'<br/>
			该玩家没有设置主战宠物！
			</td></tr>
		  </table>';
}else{
		echo ''.$u .'不存在！';
}
?>
