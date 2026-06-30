<?php
/**
*@Version: %version%
*@Copyright: %copyright%
*@Author: %author%

*@Write Date: 2008.05.19
*@Update Date: 2008.05.27
*@Usage: jinhua user bb.
*@Memo: Add two format of jinhua for bb.
*/
session_start();

require_once('../config/config.game.php');
secStart($_pm['mem']);

function jhFail($code, $id, $rollback)
{
	global $_pm;
	if ($rollback)
	{
		$_pm['mysql']->query('ROLLBACK');
	}
	unLockItem($id);
	die($code);
}

$uid = intval($_SESSION['id']);
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$style = isset($_REQUEST['n']) ? intval($_REQUEST['n']) : 0;
$bhid = isset($_GET['bhid']) ? intval($_GET['bhid']) : 0;

if ($id < 1 || ($style != 1 && $style != 2))
{
	die('0');
}

$srctime = 2;
$timeKey = 'paitimes'.$uid;
$time = isset($_SESSION[$timeKey]) ? intval($_SESSION[$timeKey]) : 0;
if ($time > 0 && time() - $time < $srctime)
{
	die('100');
}
$_SESSION[$timeKey] = time();

if (lockItem($id) === false)
{
	die('已经在处理了！');
}

$bb = unserialize($_pm['mem']->get(MEM_BB_KEY));
if (!is_array($bb))
{
	jhFail('000', $id, false);
}

$db = $_pm['mysql'];
if (!$db->query('START TRANSACTION'))
{
	jhFail('000', $id, false);
}

$user = $db->getOneRecord("SELECT id,money FROM player WHERE id={$uid} FOR UPDATE");
$cbb = $db->getOneRecord("SELECT * FROM userbb WHERE uid={$uid} AND id={$id} FOR UPDATE");
$cishu = $db->getOneRecord("SELECT chouqu_chongwu FROM player_ext WHERE uid={$uid} FOR UPDATE");

if (!is_array($user) || !is_array($cbb))
{
	jhFail('000', $id, true);
}
if (is_array($cishu) && strpos($cishu['chouqu_chongwu'], ','.$id.',') !== false)
{
	jhFail('该宠物抽取过成长,不能进行进化!', $id, true);
}
if (intval($user['money']) < 1000)
{
	jhFail('5', $id, true);
}

$currentBb = false;
$nameMatchedBb = false;
foreach ($bb as $v)
{
	if ($cbb['name'] == $v['name'])
	{
		if ($nameMatchedBb === false)
		{
			$nameMatchedBb = $v;
		}
		if ($cbb['remakelevel'] == $v['remakelevel'] &&
			$cbb['remakeid'] == $v['remakeid'] &&
			$cbb['remakepid'] == $v['remakepid'])
		{
			$currentBb = $v;
			break;
		}
	}
}
if ($currentBb === false)
{
	$currentBb = $nameMatchedBb;
}
if (!is_array($currentBb))
{
	jhFail('000', $id, true);
}
if ($currentBb['remakeid'] == '0,0' && $currentBb['remakepid'] == '0,0')
{
	jhFail('4', $id, true);
}

$branch = $style - 1;
$remakeIds = explode(',', $currentBb['remakeid']);
$remakePids = explode(',', $currentBb['remakepid']);
$remakeLevels = explode(',', $currentBb['remakelevel']);
if (!isset($remakeIds[$branch]) || !isset($remakePids[$branch]) ||
	!isset($remakeLevels[$branch]) || trim($remakeLevels[$branch]) === '')
{
	jhFail('00', $id, true);
}

$pid = intval($remakeIds[$branch]);
$levels = intval($remakeLevels[$branch]);
$propsids = array();
foreach (explode('|', $remakePids[$branch]) as $propsid)
{
	$propsid = intval($propsid);
	if ($propsid > 0)
	{
		$propsids[$propsid] = $propsid;
	}
}
if ($pid < 1 || empty($propsids))
{
	jhFail('4', $id, true);
}

$sbb = false;
foreach ($bb as $v)
{
	if (intval($v['id']) == $pid)
	{
		$sbb = $v;
		break;
	}
}
if (!is_array($sbb))
{
	jhFail('00', $id, true);
}

$material = $db->getOneRecord(
	"SELECT id,pid,sums FROM userbag
	  WHERE uid={$uid} AND pid IN (".implode(',', $propsids).") AND sums>0
	  ORDER BY id LIMIT 1 FOR UPDATE"
);
if (!is_array($material))
{
	jhFail('2', $id, true);
}

if (intval($cbb['level']) < $levels)
{
	jhFail('3', $id, true);
}
if (intval($cbb['wx']) > 6)
{
	jhFail('五行属于：金、木、水、火、土、神的才可以进行此操作！', $id, true);
}
if (intval($cbb['remaketimes']) >= 10)
{
	jhFail('6', $id, true);
}

$bhEffect = false;
$protector = false;
if ($bhid > 0)
{
	$protector = $db->getOneRecord(
		"SELECT b.id,b.sums,p.effect
		   FROM userbag AS b INNER JOIN props AS p ON p.id=b.pid
		  WHERE b.id={$bhid} AND b.uid={$uid} AND b.sums>0
		  FOR UPDATE"
	);
	if (is_array($protector))
	{
		$bhEffect = intval(str_replace('keepczl:', '', $protector['effect']));
		if ($bhEffect < 150)
		{
			$bhEffect = false;
		}
	}
}

$actualPropsId = intval($material['pid']);
$useProtector = false;
if ($actualPropsId != 1221 && $actualPropsId != 1222)
{
	if ($style == 1)
	{
		if ($cbb['czl'] < 50)
		{
			$czl = round($cbb['czl'] + rand(1, 5) / 10 + round(($cbb['level'] - $levels) / 200, 1), 1);
		}
		else if ($cbb['czl'] < 80)
		{
			$czl = $cbb['czl'] + rand(1, 3) / 10;
		}
		else
		{
			$czl = round($cbb['czl'] + 0.1, 1);
		}
	}
	else
	{
		if ($cbb['czl'] < 50)
		{
			$czl = round($cbb['czl'] + rand(5, 10) / 10 + round(($cbb['level'] - $levels) / 200, 1), 1);
		}
		else if ($cbb['czl'] < 70)
		{
			$czl = $cbb['czl'] + rand(4, 7) / 10;
		}
		else if ($cbb['czl'] < 80)
		{
			$czl = $cbb['czl'] + rand(3, 5) / 10;
		}
		else if ($cbb['czl'] < 90)
		{
			$czl = $cbb['czl'] + rand(2, 3) / 10;
		}
		else
		{
			$czl = $cbb['czl'] + rand(1, 3) / 10;
		}
	}

	if ($czl >= 150.0)
	{
		if ($bhEffect !== false)
		{
			$useProtector = true;
			if ($czl > $bhEffect)
			{
				$czl = $bhEffect;
			}
		}
		else
		{
			$czl = 150.0;
		}
	}
}
else if ($actualPropsId == 1221)
{
	$czl = $cbb['czl'] + rand(1, 3) / 10;
}
else
{
	$czl = $cbb['czl'] + rand(3, 6) / 10;
}

$times = intval($cbb['remaketimes']) + 1;
$petUpdated = $db->query(
	"UPDATE userbb SET
		imgstand=".$db->quote($sbb['imgstand']).",
		imgack=".$db->quote($sbb['imgack']).",
		imgdie=".$db->quote($sbb['imgdie']).",
		name=".$db->quote($sbb['name']).",
		czl=".$db->quote($czl).",
		remakelevel=".$db->quote($sbb['remakelevel']).",
		remakeid=".$db->quote($sbb['remakeid']).",
		remakepid=".$db->quote($sbb['remakepid']).",
		cardimg=".$db->quote($sbb['cardimg']).",
		effectimg=".$db->quote($sbb['effectimg']).",
		remaketimes={$times}
	 WHERE uid={$uid} AND id={$id}"
);
if (!$petUpdated || mysql_affected_rows($db->getConn()) != 1)
{
	jhFail('000', $id, true);
}

$materialUpdated = $db->query(
	"UPDATE userbag SET sums=sums-1
	  WHERE id=".intval($material['id'])." AND uid={$uid}
	    AND pid={$actualPropsId} AND sums>0"
);
if (!$materialUpdated || mysql_affected_rows($db->getConn()) != 1)
{
	jhFail('2', $id, true);
}

if ($useProtector)
{
	$protectorUpdated = $db->query(
		"UPDATE userbag SET sums=sums-1
		  WHERE id=".intval($protector['id'])." AND uid={$uid} AND sums>0"
	);
	if (!$protectorUpdated || mysql_affected_rows($db->getConn()) != 1)
	{
		jhFail('000', $id, true);
	}
}

$moneyUpdated = $db->query(
	"UPDATE player SET money=money-1000 WHERE id={$uid} AND money>=1000"
);
if (!$moneyUpdated || mysql_affected_rows($db->getConn()) != 1)
{
	jhFail('5', $id, true);
}

if (!$db->query('COMMIT'))
{
	jhFail('000', $id, true);
}

$logNote = '进化,使用的道具为:'.$actualPropsId.',被进化宝宝id:'.$id.
	',被进化宝宝名:'.$cbb['name'].',得到:'.$sbb['name'];
$db->query(
	"INSERT INTO gamelog(ptime,seller,buyer,pnote,vary)
	 VALUES(unix_timestamp(),{$uid},{$uid},".$db->quote($logNote).",99)"
);

unLockItem($id);
die('1');
?>
