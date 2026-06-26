<?php
/**
*@Usage: 战场主信息页面
*@Author: GeFei Su.
*@Write Date:2008-08-28
*@Copyright:www.webgame.com.cn
*
 Note:
 1. 当前玩家军功. #userjgvalue#
 2. 双方阵营HP    #ayhp#, #zrhp#
 3. 双方阵营军功榜 #aylist#, #zrlist#
 4. UI 界面输出（战场等级选择及说明） #battlechoose#
 5、当战场结束时，所有战斗停止，并自动返回战场信息主页面。
*/
require_once('../config/config.game.php');

/*if (!defined('BATTLE_TIME_START'))
	define(BATTLE_TIME_START, "20:00");
if (!defined('BATTLE_TIME_END'))
	define(BATTLE_TIME_END, "22:00");*/

secStart($_pm['mem']);
$today = date("Y-m-d", time());




$user	 = $_pm['user']->getUserById($_SESSION['id']);

// 当前玩家战场个人信息。
$battleinfo = $_pm['mysql']->getOneRecord("SELECT pos,jgvalue,curjgvalue
										     FROM battlefield_user
											WHERE uid={$_SESSION['id']}
										");
if (!is_array($battleinfo)) die('数据出错！');


// 获得双方阵营HP
$nshp = $_pm['mysql']->getRecords("SELECT srchp,hp,id,level_get
							      FROM battlefield 
								 ORDER BY id
							  ");
if (!is_array($nshp)) die('不能获得数据!');
$zrhp = array();
$ayhp = array();
foreach ($nshp as $k => $v)
{
	if ($v['id'] == 1) $zrhp = $v;
	else $ayhp = $v;
}

// 军功排名
// 左边阵营军功排名
$topzr = $_pm['mysql']->getRecords("SELECT b.curjgvalue as jgvalue,p.nickname as nickname
								      FROM player as p,battlefield_user as b
									 WHERE p.id=b.uid and b.pos=1 and b.curjgvalue>0
									 ORDER BY b.curjgvalue desc
									 LIMIT 0,10
								  ");

// 右边阵营军功排名
$topay = $_pm['mysql']->getRecords("SELECT b.curjgvalue as jgvalue,p.nickname as nickname
								      FROM player as p,battlefield_user as b
									 WHERE p.id=b.uid and b.pos=2 and b.curjgvalue>0
									 ORDER BY b.curjgvalue desc
									 LIMIT 0,10
								  ");
if (is_array($topzr))
{
	foreach ($topzr as $k => $v)
	{
		$zrlist .= "<tr><td width=24%>".(++$k)."</td><td width=76>{$v['nickname']}</td></tr>";
	}
}
else $zrlist .= '';

if (is_array($topay))
{
	foreach ($topay as $k => $v)
	{
		$aylist .= "<tr><td width=24%>".(++$k)."</td><td width=76>{$v['nickname']}</td></tr>";
	}
}
else $aylist .= '';

/**计算当前的血量*
$a = 175; b=186
*/
$imgwa = 173;
$imgwb = 182;
$initwa =  intval(($imgwa/$zrhp['srchp'])*$zrhp['hp']);// init img width.
$initwb =  intval(($imgwb/$ayhp['srchp'])*$ayhp['hp']);// init img width.
$initwa = $initwa<1?1:$initwa;
$initwb = $initwb<1?1:$initwb;

// 获得战场等级。30-45:10:1|0:1,46-60:20:1|0:1,61-70:30:2|0:1,71-80:40:2|0:1,81-90:50:3|0:1,91-100:60:3|0:1
$patter = $zrhp['level_get'];
$par = explode(',', $patter);
$battlearr = array();
$i=0;
foreach ($par as $k => $v)
{
	$inparr = explode(':', $v, 2);
	$battlearr[$i++] = $inparr[0];
}	
$battlelist  ='';				
foreach ($battlearr as $k => $v)
{
	if ($k == 3) $battlelist .= '<br/>';
	$battlelist .= "<input type='radio' value='{$v}' onclick='BattleStart(this);' name='bh'><span style='font-weight:bold;color:#D3710C'>{$v}成长</span> ";
}

//###########################
// @Load template.
//###########################
$tn = $_game['template'] . 'tpl_battle_info.html';
if (file_exists($tn))
{
	$tpl = @file_get_contents($tn);
	
	$src = array('#zrlist#',
		         '#aylist#',
		         '#userjgvalue#',
		         '#zrhp#',
		         '#ayhp#',
			     '#initwa#',
		         '#initwb#',
				 '#battlelist#'
				);
	$des = array($zrlist,
		         $aylist,
		         $battleinfo['curjgvalue'],
		         $zrhp['hp'].'/'.$zrhp['srchp'],
				 $ayhp['hp'].'/'.$ayhp['srchp'],
		         $initwa,
		         $initwb,
				 $battlelist
				);
	$cet = str_replace($src, $des, $tpl);
}
// gzip echo. if maybe.
ob_start('ob_gzip');
echo $cet;
ob_end_flush();

/**
* @Usage: 战场是否结束。
* @Param: none
* @Return: true of false
* Note: 
     结束有2种情况，一种是对方HP=0，另外是战场时间结束。
*/
function battle_end()
{
	global $_pm;
	$ends = $_pm['mysql']->getOneRecord("SELECT id
										   FROM battlefield
										  WHERE hp=0
										  LIMIT 0,1
									   ");
	if (is_array($ends))
	{
		return true;
	}
	else return false;
}
?>