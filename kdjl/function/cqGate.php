<?php
require_once('../config/config.game.php');
require_once('../sec/dblock_fun.php');
secStart($_pm['mem']);

function cqFail($message)
{
	global $_pm;
	$_pm['mysql']->query('ROLLBACK');
	die($message);
}

function cqLog($note, $vary=103)
{
	global $_pm;
	$sql = 'INSERT INTO gamelog SET seller='.intval($_SESSION['id']).
		',vary='.intval($vary).',pnote='.$_pm['mysql']->quote($note).',ptime='.time();
	return $_pm['mysql']->query($sql);
}

$uid = intval($_SESSION['id']);
$petId = isset($_GET['pid']) ? abs(intval($_GET['pid'])) : 0;
$p1 = isset($_GET['pid1']) ? abs(intval($_GET['pid1'])) : 0;
$p2 = isset($_GET['pid2']) ? abs(intval($_GET['pid2'])) : 0;

if ($uid < 1 || $petId < 1)
{
	die('数据错误！');
}

getLock($uid);
$db = $_pm['mysql'];

$player = $db->getOneRecord('SELECT money FROM player WHERE id='.$uid.' FOR UPDATE');
$bb = $db->getOneRecord(
	'SELECT name,wx,level,czl,remaketimes FROM userbb WHERE uid='.$uid.' AND id='.$petId.' FOR UPDATE'
);
$playerExt = $db->getOneRecord(
	'SELECT czl_ss,chouqu_chongwu FROM player_ext WHERE uid='.$uid.' FOR UPDATE'
);

if (!is_array($player) || !is_array($playerExt))
{
	cqFail('玩家数据不存在！');
}
if (!is_array($bb))
{
	cqFail('这个宠物不存在！');
}
if (strpos($playerExt['chouqu_chongwu'], ','.$petId.',') !== false)
{
	cqFail('这个宠物抽取过成长,不能再抽取!');
}
if (intval($bb['wx']) > 6)
{
	cqFail('该宠物不能抽取!');
}
if (floatval($bb['czl']) < 30)
{
	cqFail('成长小于30的不能抽取！');
}

$selectedIds = array();
if ($p1 > 0) $selectedIds[$p1] = $p1;
if ($p2 > 0) $selectedIds[$p2] = $p2;

$bagById = array();
if (!empty($selectedIds))
{
	$rows = $db->getRecords(
		'SELECT b.id,b.pid,b.sums,p.effect FROM userbag AS b ' .
		'INNER JOIN props AS p ON p.id=b.pid ' .
		'WHERE b.uid='.$uid.' AND b.id IN ('.implode(',', $selectedIds).') FOR UPDATE'
	);
	if (is_array($rows))
	{
		foreach ($rows as $row)
		{
			$bagById[intval($row['id'])] = $row;
		}
	}
}

$selected = array($p1, $p2);
$useCounts = array();
$stoneCount = 0;
$swapRateInc = 0;
$swapRateIncFixed = 0;
$wpLog = '';

foreach ($selected as $bagId)
{
	if ($bagId < 1) continue;
	if (!isset($bagById[$bagId]))
	{
		cqFail('选择的抽取道具不存在或数量不足！');
	}

	$row = $bagById[$bagId];
	$propsId = intval($row['pid']);
	if (intval($bb['wx']) == 6 && $propsId == 3383)
	{
		cqFail('非五系宠物不能使用五系宠物抽取石！');
	}
	if (intval($bb['wx']) < 6 && $propsId != 3383)
	{
		cqFail('五系宠物不能使用增加比例道具！');
	}
	if ($propsId == 3383)
	{
		$stoneCount++;
	}
	if (strpos($row['effect'], 'inczhl:') === false)
	{
		cqFail('选择的道具不能用于成长抽取！');
	}

	if (!isset($useCounts[$bagId])) $useCounts[$bagId] = 0;
	$useCounts[$bagId]++;
	if ($useCounts[$bagId] > intval($row['sums']))
	{
		cqFail('选择的抽取道具数量不足！');
	}

	if (strpos($row['effect'], 'inczhl:') !== false)
	{
		$effect = str_replace('inczhl:', '', $row['effect']);
		if (strpos($effect, 'a') === false)
		{
			$swapRateInc += abs(floatval($effect));
		}
		else
		{
			$swapRateIncFixed += abs(floatval(str_replace('a', '', $effect)));
		}
		$wpLog .= ' ['.$propsId.'] ';
	}
}

if (intval($bb['wx']) < 6 && $stoneCount < 1)
{
	cqFail('缺少五系宠物抽取的必须道具！');
}
if ($stoneCount > 1)
{
	cqFail('请不要使用两个五系宠物抽取石！');
}

foreach ($useCounts as $bagId => $count)
{
	$sql = 'UPDATE userbag SET sums=sums-'.intval($count).
		' WHERE id='.intval($bagId).' AND uid='.$uid.' AND sums>='.intval($count);
	if (!$db->query($sql) || mysql_affected_rows($db->getConn()) != 1)
	{
		cqFail('抽取道具扣除失败，请重试！');
	}
}

$bbCzl = floatval($bb['czl']);
if ($bbCzl < 65)
{
	$swapRate = rand(10, 20);
}
else if ($bbCzl < 85)
{
	$swapRate = rand(30, 50);
}
else if ($bbCzl < 100)
{
	$swapRate = rand(50, 65);
}
else if ($bbCzl < 110)
{
	$swapRate = 65;
}
else if ($bbCzl < 115)
{
	$swapRate = 70;
}
else if ($bbCzl < 120)
{
	$swapRate = 75;
}
else
{
	$swapRate = 80;
}

if (intval($bb['wx']) != 6)
{
	$swapRate = rand(5, 15);
}

$swapRate += $swapRateInc;
if ($swapRate > 100) $swapRate = 100;

$czl = ceil($bbCzl * ($swapRate / 100));
$czl += $swapRateIncFixed;
if ($czl > 600) $czl = 600;

$money = intval(round(($bbCzl < 600 ? $bbCzl : 600) * 10000));
if (intval($player['money']) < $money)
{
	cqFail('您的金币不足(需要:'.$money.')');
}

$sql = 'UPDATE player SET money=money-'.$money.' WHERE id='.$uid.' AND money>='.$money;
if (!$db->query($sql) || mysql_affected_rows($db->getConn()) != 1)
{
	cqFail('金币扣除失败，请重试！');
}

$sql = 'UPDATE player_ext SET czl_ss=COALESCE(czl_ss,0)+'.abs($czl).',chouqu_chongwu='.
	'CONCAT(TRIM(TRAILING "," FROM COALESCE(chouqu_chongwu,"")),",'.$petId.',") WHERE uid='.$uid;
if (!$db->query($sql) || mysql_affected_rows($db->getConn()) != 1)
{
	cqFail('成长抽取记录写入失败，请重试！');
}

if (intval($bb['wx']) < 6)
{
	$sql = 'UPDATE userbb SET name=CONCAT(name,"-",uid),uid=0 WHERE id='.$petId.' AND uid='.$uid;
}
else
{
	$sql = 'UPDATE userbb SET czl=1 WHERE id='.$petId.' AND uid='.$uid;
}
if (!$db->query($sql) || mysql_affected_rows($db->getConn()) != 1)
{
	cqFail('宠物成长状态更新失败，请重试！');
}

if (!$db->query('COMMIT'))
{
	$db->query('ROLLBACK');
	die('成长抽取提交失败，请重试！');
}

cqLog('被抽取的宠物id='.$petId.',抽取了:'.abs($czl).',使用物品'.($wpLog == '' ? '无' : $wpLog));
die('OK'.$czl);
?>
