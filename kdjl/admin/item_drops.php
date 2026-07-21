<?php
require_once(dirname(__FILE__) . '/_bootstrap.php');
require_once(dirname(__FILE__) . '/_layout.php');
require_once(dirname(__FILE__) . '/_drop_helpers.php');

$search = trim(adminRequest('q'));
$propId = intval(adminRequest('prop_id', 0));

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST')
{
	$action = adminPost('action');
	$gpcIds = adminDropSelectedMonsterIds(
		isset($_POST['selected_gpc_ids']) ? $_POST['selected_gpc_ids'] : array(),
		isset($_POST['selected_gpc_csv']) ? $_POST['selected_gpc_csv'] : ''
	);
	$backUrl = 'item_drops.php?q=' . rawurlencode($search) . '&prop_id=' . $propId;
	if ($action !== 'delete_item_drop')
	{
		adminSetFlash('error', '操作参数未提交，请刷新页面后重试。');
		adminRedirect($backUrl);
	}
	if ($propId < 1)
	{
		adminSetFlash('error', '请选择需要管理的道具。');
		adminRedirect($backUrl);
	}
	if (count($gpcIds) === 0)
	{
		adminSetFlash('error', '没有收到怪物选择，请重新勾选怪物。');
		adminRedirect($backUrl);
	}
	$prop = $adminDb->getOneRecord("SELECT id,name FROM props WHERE id={$propId} LIMIT 1");
	if (!is_array($prop))
	{
		adminSetFlash('error', '所选道具不存在。');
		adminRedirect('item_drops.php?q=' . rawurlencode($search));
	}
	$result = adminDropUpdate($adminDb, $gpcIds, $propId, 1, true, 'droplist');
	if (!$result[0])
	{
		adminSetFlash('error', '删除掉落失败：' . $result[2]);
		adminRedirect($backUrl);
	}
	$changedIds = $result[1];
	$cacheOk = count($changedIds) === 0 ? true : adminRefreshGpcCache($adminDb, $adminMem, $changedIds);
	$message = count($changedIds) === 0 ? '所选怪物均未配置该道具掉落。' : '已从 ' . count($changedIds) . ' 只怪物删除普通掉落：id=' . $propId . ' ' . $prop['name'] . '。';
	if (!$cacheOk) $message .= ' 数据库已保存，但怪物缓存刷新失败。';
	adminSetFlash($cacheOk ? 'success' : 'warning', $message);
	adminRedirect($backUrl);
}

$searchRows = $search !== '' ? adminDropSearchProps($adminDb, $search) : array();
$selectedProp = $propId > 0 ? $adminDb->getOneRecord("SELECT id,name,varyname FROM props WHERE id={$propId} LIMIT 1") : false;
$monsters = array();
if (is_array($selectedProp))
{
	$rows = $adminDb->getRecords('SELECT id,name,level,droplist FROM gpc ORDER BY level,id');
	if (is_array($rows))
	{
		foreach ($rows as $row)
		{
			$groups = adminDropGroupsForProp($row['droplist'], $propId);
			if (count($groups) === 0) continue;
			$row['_drop_groups'] = $groups;
			$monsters[] = $row;
		}
	}
}
$sources = array();
if (count($monsters) > 0)
{
	$catalogGroups = adminDropCatalog($adminDb, $fbinfo);
	$sources = adminDropSourceIndex($adminDb, $catalogGroups['all']);
}

adminPageStart('道具掉落管理', 'item_drops');
?>
	<section class="band">
		<div class="section-head"><div><h2>查询道具</h2><div class="subtle">按 id 或名称模糊搜索普通掉落道具</div></div></div>
		<form class="form-row" method="get" action="item_drops.php"><input class="input drop-search" type="search" name="q" value="<?php echo adminH($search); ?>" placeholder="道具 id 或名称" required="required" /><button class="btn primary" type="submit">搜索</button></form>
		<?php if ($search !== '') { ?>
			<?php if (count($searchRows) === 0) { ?><div class="empty drop-search-results">没有匹配的道具</div>
			<?php } else { ?><div class="table-wrap prop-picker drop-search-results"><table><thead><tr><th>道具</th><th>类型</th><th>操作</th></tr></thead><tbody>
			<?php foreach ($searchRows as $row) { ?><tr><td><?php adminPropLabel($row); ?></td><td><?php echo adminH(adminPropTypeName($row)); ?></td><td><a class="btn secondary" href="item_drops.php?q=<?php echo rawurlencode($search); ?>&amp;prop_id=<?php echo intval($row['id']); ?>">查看掉落怪物</a></td></tr><?php } ?>
			</tbody></table></div><?php } ?>
		<?php } ?>
	</section>

	<?php if ($propId > 0) { ?>
	<section class="band">
		<?php if (!is_array($selectedProp)) { ?><div class="empty error-text">所选道具不存在</div>
		<?php } else { ?>
		<div class="section-head"><div><h2><?php echo adminH($selectedProp['name']); ?> 的普通掉落</h2><div class="subtle">id=<?php echo intval($selectedProp['id']); ?> · <?php echo count($monsters); ?> 只怪物</div></div></div>
		<?php if (count($monsters) === 0) { ?><div class="empty">没有怪物掉落该道具</div>
		<?php } else { ?>
		<form method="post" action="item_drops.php" data-drop-form>
			<input type="hidden" name="q" value="<?php echo adminH($search); ?>" />
			<input type="hidden" name="prop_id" value="<?php echo intval($propId); ?>" />
			<input type="hidden" name="selected_gpc_csv" value="" data-selected-gpc-csv />
			<div class="batch-bar"><label class="batch-check"><input type="checkbox" data-select-all="drop-monsters" />全选怪物</label><span class="subtle">已选择 <strong data-selected-count="drop-monsters">0</strong> 只</span><button class="btn danger" type="submit" name="action" value="delete_item_drop" data-batch-submit="drop-monsters" data-confirm-action="确认从所选怪物删除此道具的全部普通掉落配置？">批量删除掉落</button></div>
			<div class="table-wrap"><table class="item-drop-table"><thead><tr><th class="select-cell">选择</th><th>怪物</th><th>出现区域</th><th>等级</th><th>掉落概率</th></tr></thead><tbody>
			<?php foreach ($monsters as $monster) { $gpcId = intval($monster['id']); ?>
				<tr><td class="select-cell"><input type="checkbox" name="selected_gpc_ids[]" value="<?php echo $gpcId; ?>" data-select-item="drop-monsters" /></td>
				<td><div class="query-pet"><strong><?php echo adminH($monster['name']); ?></strong><span>id=<?php echo $gpcId; ?></span></div></td>
				<td><div class="source-list"><?php if (!isset($sources[$gpcId]) || count($sources[$gpcId]) === 0) { ?><span class="subtle">未匹配到地图或副本</span><?php } else { foreach ($sources[$gpcId] as $source) { ?><span><?php echo adminH($source); ?></span><?php } } ?></div></td>
				<td><span class="badge muted"><?php echo intval($monster['level']); ?>级</span></td>
				<td><div class="drop-list"><?php foreach ($monster['_drop_groups'] as $group) { ?><span class="drop-entry"><span>1/<?php echo intval($group['denominator']); ?> · <?php echo adminH(adminDropPercent($group['denominator'])); ?>%</span><?php if ($group['count'] > 1) { ?><em>重复 <?php echo intval($group['count']); ?> 次</em><?php } ?></span><?php } ?></div></td></tr>
			<?php } ?>
			</tbody></table></div>
		</form>
		<?php } } ?>
	</section>
	<?php } ?>
<?php adminPageEnd(); ?>
