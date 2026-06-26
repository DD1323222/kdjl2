<?php 
/**
@Usage: 游戏的礼包功能。
2.	判断玩家身上是否带有”中国加油”道具,道具ID:817，如果有，则给予玩家6个双倍经验卷轴（ID：745）
*/
exit();

require_once('../config/config.game.php');
secStart($_pm['mem']);

$user		= $_pm['user']->getUserById($_SESSION['id']);
$bag		= $_pm['user']->getUserBagById($_SESSION['id']);

// time limit start
$tl = mktime(10,0,0,date("m",time()),date("d",time()),date("y",time()));

$lb = $_pm['mysql']->getOneRecord("SELECT id,cet
									 FROM libao
									WHERE pname='{$user['id']}'
								 ");
if (is_array($lb) &&  (($lb['cet'] > $tl) || time()<$tl  ) )
{
	die('您已经领取过了，请明天10点后再来！');
}

$rs = $_pm['mysql']->getOneRecord("SELECT id 
									 FROM userbag
									WHERE uid={$_SESSION['id']} and pid=817 and sums>0
								");

if (is_array($rs)) // 用户携带有该道具,3个双倍。
{
	$tsk = new task();
	$tsk->saveGetProps('745,745,745,745,745');
	// 删除一个道具并更新已经领取奖励的标记。
	$_pm['mysql']->query("UPDATE userbag
						     SET sums=abs(sums-1)
						   WHERE uid={$_SESSION['id']} and id={$rs['id']} and sums > 0
						");
	if (is_array($lb)) // 已经存在该用户。更新时间。
	{
		$_pm['mysql']->query("UPDATE libao
							     SET cet='".time()."'
							   WHERE id='{$lb['id']}'
							");
	}
	else $_pm['mysql']->query("INSERT INTO libao(pname,flag,cet)
							   VALUES('{$user['id']}',1,'".time()."')
							  ");
	die('恭喜您，领取奖励成功！');   
}
else die('想欺骗我？您的包裹中没有中国加油的道具吧！');
?>