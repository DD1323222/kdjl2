<?php
header('Content-Type:text/html;charset=utf-8');
require_once('../config/config.game.php');
secStart($_pm['mem']);
$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
if($uid < 1) die('');
$html = '';
$info = $_pm['mysql'] -> getRecords("SELECT id,times,content FROM information WHERE uid = {$uid} ORDER BY id DESC LIMIT 10");
if(is_array($info)){
	if(count($info) == 10 && isset($info[3]) && is_array($info[3]) && isset($info[3]['id'])){
		$deleteId = intval($info[3]['id']);
		$sql = "DELETE FROM information WHERE uid = {$uid} AND id < {$deleteId}";
		//echo $sql;
		$_pm['mysql'] -> query($sql);
	}
	foreach($info as $k => $v){
		if(!is_array($v)) continue;
		$i = $k + 1;
		$content = isset($v['content']) ? $v['content'] : '';
		$times = isset($v['times']) ? $v['times'] : '';
		$len = function_exists('mb_strlen') ? mb_strlen($content, 'UTF-8') : strlen($content);
		if($len>42){
			$c = function_exists('mb_substr') ? mb_substr($content,0,41,'UTF-8') : substr($content,0,41);
			$c .= '...';
		}else{
			$c = $content;
		}
		$title = htmlspecialchars($content.' '.$times, ENT_QUOTES, 'UTF-8');
		$text = htmlspecialchars($c, ENT_QUOTES, 'UTF-8');
		$html .= '<li><a title="'.$title.'"><p>'.$i.'. '.$text.'</p></a></li>';
	}
}
if($html == ''){
	$html = '<li><a title="目前您没有任何系统消息"><p>
			目前您没有任何系统消息
			</p></a></li>';
}
echo $html;
?>
