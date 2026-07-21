<?php
require_once(dirname(__FILE__) . '/_bootstrap.php');
require_once(dirname(__FILE__) . '/_layout.php');

function adminSearchPlayers($db, $search)
{
	$search = trim((string)$search);
	if ($search === '') return array();
	$escaped = $db->escape($search);
	$where = "nickname LIKE '%{$escaped}%' OR name LIKE '%{$escaped}%'";
	if (preg_match('/^[0-9]+$/', $search)) $where = "id=" . intval($search) . " OR {$where}";
	$rows = $db->getRecords("SELECT p.id,p.name,p.nickname,p.money,p.yb,p.vip,p.prestige,p.maxbag,IFNULL(e.sj,0) AS sj FROM player p LEFT JOIN player_ext e ON e.uid=p.id WHERE {$where} ORDER BY p.id LIMIT 80");
	return is_array($rows) ? $rows : array();
}

function adminGrantSearchProps($db, $search)
{
	$search = trim((string)$search);
	if ($search === '') return array();
	$escaped = $db->escape($search);
	$where = "name LIKE '%{$escaped}%'";
	if (preg_match('/^[0-9]+$/', $search)) $where = "id=" . intval($search) . " OR {$where}";
	$rows = $db->getRecords("SELECT id,name,sell,vary,varyname FROM props WHERE {$where} ORDER BY id LIMIT 100");
	return is_array($rows) ? $rows : array();
}

function adminGrantPlayer($db, $uid)
{
	$uid = intval($uid);
	if ($uid < 1) return false;
	$row = $db->getOneRecord("SELECT p.id,p.name,p.nickname,p.money,p.yb,p.vip,p.prestige,p.maxbag,IFNULL(e.sj,0) AS sj FROM player p LEFT JOIN player_ext e ON e.uid=p.id WHERE p.id={$uid} LIMIT 1");
	return is_array($row) ? $row : false;
}

function adminGrantProp($db, $pid)
{
	$pid = intval($pid);
	if ($pid < 1) return false;
	$row = $db->getOneRecord("SELECT id,name,sell,vary,varyname FROM props WHERE id={$pid} LIMIT 1");
	return is_array($row) ? $row : false;
}

function adminGrantPlayerExtExists($db, $uid)
{
	$row = $db->getOneRecord("SELECT uid FROM player_ext WHERE uid=" . intval($uid) . " LIMIT 1");
	return is_array($row);
}

function adminGrantAddItem($db, $uid, $prop, $num)
{
	$uid = intval($uid);
	$pid = intval($prop['id']);
	$num = intval($num);
	if ($uid < 1 || $pid < 1 || $num < 1) return false;
	$sell = intval($prop['sell']);
	$vary = intval($prop['vary']);
	$now = time();
	if ($vary == 1)
	{
		$maxExisting = 2147483647 - $num;
		$bagRow = $db->getOneRecord("SELECT id FROM userbag WHERE uid={$uid} AND pid={$pid} AND vary=1 AND zbing=0 AND sums>0 AND sums<={$maxExisting} AND (cantrade IS NULL OR cantrade=0) ORDER BY id LIMIT 1 FOR UPDATE");
		if (is_array($bagRow))
		{
			if ($db->query("UPDATE userbag SET sums=sums+{$num},stime={$now} WHERE id=" . intval($bagRow['id']) . " AND uid={$uid} AND pid={$pid} AND vary=1 AND zbing=0 AND sums>0 AND sums<={$maxExisting} AND (cantrade IS NULL OR cantrade=0)") === false) return false;
			return mysql_affected_rows($db->getConn()) == 1;
		}
		return $db->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime) VALUES({$uid},{$pid},{$sell},1,{$num},{$now})") !== false;
	}

	$values = array();
	for ($i = 0; $i < $num; $i++)
	{
		$values[] = "({$uid},{$pid},{$sell},{$vary},1,{$now})";
		if (count($values) >= 100)
		{
			if ($db->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime) VALUES " . implode(',', $values)) === false) return false;
			$values = array();
		}
	}
	if (count($values) > 0)
	{
		if ($db->query("INSERT INTO userbag(uid,pid,sell,vary,sums,stime) VALUES " . implode(',', $values)) === false) return false;
	}
	return true;
}

function adminGrantRequiredSlots($bagRows, $prop, $num)
{
	$num = intval($num);
	if ($num < 1) return 0;
	if (intval($prop['vary']) !== 1) return $num;
	$pid = intval($prop['id']);
	$maxExisting = 2147483647 - $num;
	foreach ($bagRows as $row)
	{
		$cantrade = isset($row['cantrade']) ? $row['cantrade'] : 0;
		if (intval($row['pid']) === $pid && intval($row['vary']) === 1 && intval($row['sums']) > 0 &&
			intval($row['sums']) <= $maxExisting && ($cantrade === null || intval($cantrade) === 0)) return 0;
	}
	return 1;
}

function adminGrantClearUserCache($mem, $uid)
{
	$uid = intval($uid);
	if ($uid < 1) return;
	$mem->del($uid);
	$mem->del($uid . 'user');
	$mem->del($uid . 'bag');
}

$playerQuery = trim(adminGet('player_q'));
$propQuery = trim(adminGet('prop_q'));
$selectedPlayerId = intval(adminRequest('player_id', 0));
$selectedPropId = intval(adminRequest('prop_id', 0));

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST')
{
	$action = adminPost('action');
	if ($action === 'grant')
	{
		$uid = intval(adminPost('player_id', 0));
		$pid = intval(adminPost('prop_id', 0));
		$itemNum = intval(adminPost('item_num', 0));
		$money = intval(adminPost('money', 0));
		$yb = intval(adminPost('yb', 0));
		$sj = intval(adminPost('sj', 0));
		$vip = intval(adminPost('vip', 0));
		$prestige = intval(adminPost('prestige', 0));
		$player = adminGrantPlayer($adminDb, $uid);
		$prop = $pid > 0 ? adminGrantProp($adminDb, $pid) : false;
		$messages = array();
		if (!is_array($player))
		{
			adminSetFlash('error', '请选择有效玩家。');
			adminRedirect('grant.php');
		}
		if ($itemNum > 0 && !is_array($prop))
		{
			adminSetFlash('error', '发放物品时必须选择有效道具。');
			adminRedirect('grant.php?player_id=' . $uid);
		}
		if ($itemNum < 0 || $money < 0 || $yb < 0 || $sj < 0 || $vip < 0 || $prestige < 0)
		{
			adminSetFlash('error', '发放数量不能为负数。');
			adminRedirect('grant.php?player_id=' . $uid . '&prop_id=' . $pid);
		}
		if ($itemNum > 9999)
		{
			adminSetFlash('error', '单次物品发放数量不能超过 9999。');
			adminRedirect('grant.php?player_id=' . $uid . '&prop_id=' . $pid);
		}
		if ($itemNum < 1 && $money < 1 && $yb < 1 && $sj < 1 && $vip < 1 && $prestige < 1)
		{
			adminSetFlash('error', '请至少填写一种要发放的物品或资源。');
			adminRedirect('grant.php?player_id=' . $uid . '&prop_id=' . $pid);
		}

		$ok = adminStartTransaction($adminDb);
		$failure = '';
		$lockedProp = false;
		if ($ok && $itemNum > 0)
		{
			$lockedProp = $adminDb->getOneRecord("SELECT id,name,sell,vary,varyname FROM props WHERE id={$pid} FOR UPDATE");
			if (!is_array($lockedProp))
			{
				$ok = false;
				$failure = '道具记录锁定失败。';
			}
		}
		$lockedPlayer = false;
		if ($ok)
		{
			$lockedPlayer = $adminDb->getOneRecord("SELECT id,maxbag,money,yb,vip,prestige FROM player WHERE id={$uid} FOR UPDATE");
			if (!is_array($lockedPlayer))
			{
				$ok = false;
				$failure = '玩家记录锁定失败。';
			}
		}
		$bagRows = array();
		if ($ok && $itemNum > 0)
		{
			$bagRows = $adminDb->getRecords("SELECT id,pid,vary,sums,cantrade FROM userbag WHERE uid={$uid} AND zbing=0 AND sums>0 FOR UPDATE");
			if (!is_array($bagRows))
			{
				$ok = false;
				$failure = '背包记录锁定失败。';
			}
		}
		$lockedExt = false;
		if ($ok && $sj > 0)
		{
			$lockedExt = $adminDb->getOneRecord("SELECT uid,sj FROM player_ext WHERE uid={$uid} LIMIT 1 FOR UPDATE");
			$currentSj = is_array($lockedExt) ? intval($lockedExt['sj']) : 0;
			if ($currentSj > 2147483647 - $sj)
			{
				$ok = false;
				$failure = '水晶发放后将超过 2147483647，未执行发放。';
			}
		}
		if ($ok && $itemNum > 0)
		{
			$neededSlots = adminGrantRequiredSlots($bagRows, $lockedProp, $itemNum);
			if (count($bagRows) + $neededSlots > intval($lockedPlayer['maxbag']))
			{
				$ok = false;
				$failure = '背包空间不足：还需要 ' . $neededSlots . ' 格，当前为 ' . count($bagRows) . '/' . intval($lockedPlayer['maxbag']) . '。';
			}
			else if (!adminGrantAddItem($adminDb, $uid, $lockedProp, $itemNum))
			{
				$ok = false;
				$failure = '道具写入背包失败。';
			}
			else $messages[] = $lockedProp['name'] . ' x' . $itemNum;
		}
		$resourceLimits = array('money' => 1000000000, 'yb' => 2147483647, 'vip' => 2147483647, 'prestige' => 2147483647);
		$resourceAdds = array('money' => $money, 'yb' => $yb, 'vip' => $vip, 'prestige' => $prestige);
		if ($ok)
		{
			foreach ($resourceAdds as $resourceField => $resourceAdd)
			{
				if ($resourceAdd > 0 && intval($lockedPlayer[$resourceField]) > $resourceLimits[$resourceField] - $resourceAdd)
				{
					$ok = false;
					$failure = $resourceField . ' 发放后将超过游戏数值上限，未执行发放。';
					break;
				}
			}
		}
		$setParts = array();
		if ($money > 0)
		{
			$setParts[] = "money=LEAST(COALESCE(money,0)+{$money},1000000000)";
			$messages[] = '金币 +' . $money;
		}
		if ($yb > 0)
		{
			$setParts[] = "yb=COALESCE(yb,0)+{$yb}";
			$messages[] = '元宝 +' . $yb;
		}
		if ($vip > 0)
		{
			$setParts[] = "vip=COALESCE(vip,0)+{$vip}";
			$messages[] = 'VIP点 +' . $vip;
		}
		if ($prestige > 0)
		{
			$setParts[] = "prestige=COALESCE(prestige,0)+{$prestige}";
			$messages[] = '威望 +' . $prestige;
		}
		if ($ok && count($setParts) > 0)
		{
			if ($adminDb->query("UPDATE player SET " . implode(',', $setParts) . " WHERE id={$uid}") === false) $ok = false;
		}
		if ($ok && $sj > 0)
		{
			if (is_array($lockedExt))
			{
				if ($adminDb->query("UPDATE player_ext SET sj=COALESCE(sj,0)+{$sj} WHERE uid={$uid}") === false) $ok = false;
			}
			else if ($adminDb->query("INSERT INTO player_ext(uid,sj,bbshow) VALUES({$uid},{$sj},5)") === false)
			{
				$ok = false;
			}
			if ($ok) $messages[] = '水晶 +' . $sj;
		}
		if (!$ok || !$adminDb->query('COMMIT'))
		{
			$adminDb->query('ROLLBACK');
			$error = $failure !== '' ? $failure : $adminDb->getError();
			adminSetFlash('error', '发放失败：' . $error);
			adminRedirect('grant.php?player_id=' . $uid . '&prop_id=' . $pid);
		}
		adminGrantClearUserCache($adminMem, $uid);
		adminSetFlash('success', '已向 ' . $player['nickname'] . '(id=' . $uid . ') 发放：' . implode('，', $messages));
		adminRedirect('grant.php?player_id=' . $uid . '&prop_id=' . $pid);
	}
}

$selectedPlayer = adminGrantPlayer($adminDb, $selectedPlayerId);
$selectedProp = adminGrantProp($adminDb, $selectedPropId);
$playerRows = adminSearchPlayers($adminDb, $playerQuery);
$propRows = adminGrantSearchProps($adminDb, $propQuery);

adminPageStart('物品发放', 'grant');
?>
<section class="band">
	<div class="grant-grid">
		<div>
			<div class="section-head">
				<div><h2>选择玩家</h2><?php if ($playerQuery !== '') { ?><div class="subtle">找到 <?php echo count($playerRows); ?> 条结果</div><?php } ?></div>
			</div>
			<form class="form-row" method="get" action="grant.php">
				<?php if ($selectedPropId > 0) { ?><input type="hidden" name="prop_id" value="<?php echo intval($selectedPropId); ?>" /><?php } ?>
				<input class="input grant-search" type="search" name="player_q" value="<?php echo adminH($playerQuery); ?>" placeholder="玩家 ID、账号或昵称" />
				<button class="btn primary" type="submit">搜索玩家</button>
			</form>
			<?php if (is_array($selectedPlayer)) { ?>
				<div class="grant-selected"><?php echo adminH($selectedPlayer['nickname']); ?><span>id=<?php echo intval($selectedPlayer['id']); ?> / 账号 <?php echo adminH($selectedPlayer['name']); ?></span></div>
			<?php } ?>
			<?php if ($playerQuery !== '') { ?>
			<div class="results grant-results">
				<table class="grant-table"><thead><tr><th>玩家</th><th>金币</th><th>元宝</th><th>水晶</th><th>操作</th></tr></thead><tbody>
				<?php foreach ($playerRows as $row) { ?><tr>
					<td><div class="query-pet"><strong><?php echo adminH($row['nickname']); ?></strong><span>id=<?php echo intval($row['id']); ?> / <?php echo adminH($row['name']); ?></span></div></td>
					<td><?php echo intval($row['money']); ?></td>
					<td><?php echo intval($row['yb']); ?></td>
					<td><?php echo intval($row['sj']); ?></td>
					<td><a class="btn secondary" href="grant.php?player_id=<?php echo intval($row['id']); ?>&amp;prop_id=<?php echo intval($selectedPropId); ?>&amp;player_q=<?php echo rawurlencode($playerQuery); ?>&amp;prop_q=<?php echo rawurlencode($propQuery); ?>">选择</a></td>
				</tr><?php } ?>
				</tbody></table>
				<?php if (count($playerRows) === 0) { ?><div class="empty">没有找到玩家</div><?php } ?>
			</div>
			<?php } ?>
		</div>
		<div>
			<div class="section-head">
				<div><h2>选择道具</h2><?php if ($propQuery !== '') { ?><div class="subtle">找到 <?php echo count($propRows); ?> 条结果</div><?php } ?></div>
			</div>
			<form class="form-row" method="get" action="grant.php">
				<?php if ($selectedPlayerId > 0) { ?><input type="hidden" name="player_id" value="<?php echo intval($selectedPlayerId); ?>" /><?php } ?>
				<input class="input grant-search" type="search" name="prop_q" value="<?php echo adminH($propQuery); ?>" placeholder="道具 ID 或名称" />
				<button class="btn primary" type="submit">搜索道具</button>
			</form>
			<?php if (is_array($selectedProp)) { ?>
				<div class="grant-selected"><?php echo adminH($selectedProp['name']); ?><span>id=<?php echo intval($selectedProp['id']); ?> / <?php echo intval($selectedProp['vary']) == 1 ? '可叠加' : '不可叠加'; ?></span></div>
			<?php } ?>
			<?php if ($propQuery !== '') { ?>
			<div class="results grant-results">
				<table class="grant-table"><thead><tr><th>道具</th><th>类型</th><th>叠加</th><th>操作</th></tr></thead><tbody>
				<?php foreach ($propRows as $row) { ?><tr>
					<td><?php adminPropLabel($row); ?></td>
					<td><?php echo adminH(adminPropTypeName($row)); ?></td>
					<td><?php echo intval($row['vary']) == 1 ? '可叠加' : '不可叠加'; ?></td>
					<td><a class="btn secondary" href="grant.php?player_id=<?php echo intval($selectedPlayerId); ?>&amp;prop_id=<?php echo intval($row['id']); ?>&amp;player_q=<?php echo rawurlencode($playerQuery); ?>&amp;prop_q=<?php echo rawurlencode($propQuery); ?>">选择</a></td>
				</tr><?php } ?>
				</tbody></table>
				<?php if (count($propRows) === 0) { ?><div class="empty">没有找到道具</div><?php } ?>
			</div>
			<?php } ?>
		</div>
	</div>
</section>
<section class="band">
	<div class="section-head"><div><h2>发放内容</h2><div class="subtle">物品和资源可以同一次发放；留空或填 0 表示不发放。</div></div></div>
	<form class="grant-form" method="post" data-confirm="确认执行本次发放？">
		<input type="hidden" name="action" value="grant" />
		<input type="hidden" name="player_id" value="<?php echo is_array($selectedPlayer) ? intval($selectedPlayer['id']) : 0; ?>" />
		<input type="hidden" name="prop_id" value="<?php echo is_array($selectedProp) ? intval($selectedProp['id']) : 0; ?>" />
		<div class="field"><label>目标玩家</label><div class="grant-readonly"><?php echo is_array($selectedPlayer) ? adminH($selectedPlayer['nickname']) . ' / id=' . intval($selectedPlayer['id']) : '未选择'; ?></div></div>
		<div class="field"><label>发放道具</label><div class="grant-readonly"><?php echo is_array($selectedProp) ? adminH($selectedProp['name']) . ' / id=' . intval($selectedProp['id']) : '未选择'; ?></div></div>
		<div class="field"><label>物品数量</label><input class="input" type="number" min="0" max="9999" name="item_num" value="0" /></div>
		<div class="field"><label>水晶</label><input class="input" type="number" min="0" name="sj" value="0" /></div>
		<div class="field"><label>元宝</label><input class="input" type="number" min="0" name="yb" value="0" /></div>
		<div class="field"><label>金币</label><input class="input" type="number" min="0" name="money" value="0" /></div>
		<div class="field"><label>VIP点</label><input class="input" type="number" min="0" name="vip" value="0" /></div>
		<div class="field"><label>威望</label><input class="input" type="number" min="0" name="prestige" value="0" /></div>
		<button class="btn primary" type="submit"<?php echo is_array($selectedPlayer) ? '' : ' disabled="disabled"'; ?>>确认发放</button>
	</form>
</section>
<?php adminPageEnd(); ?>
