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
$selectedProp = $propId > 0 ? $adminDb->getOneRecord("SELECT id,name,varyname,propslock FROM props WHERE id={$propId} LIMIT 1") : false;
$monsters = array();
$itemSources = array('packages' => array(), 'recipes' => array(), 'tasks' => array(), 'props' => array());
if (is_array($selectedProp))
{
	$itemSources = adminDropItemSources($adminDb, $propId);
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
		<div class="section-head"><div><h2>查询道具</h2><div class="subtle">按 id 或名称模糊搜索，查询怪物掉落、礼包开出和合成产出途径</div></div></div>
		<form class="form-row" method="get" action="item_drops.php"><input class="input drop-search" type="search" name="q" value="<?php echo adminH($search); ?>" placeholder="道具 id 或名称" required="required" /><button class="btn primary" type="submit">搜索</button></form>
		<?php if ($search !== '') { ?>
			<?php if (count($searchRows) === 0) { ?><div class="empty drop-search-results">没有匹配的道具</div>
			<?php } else { ?><div class="table-wrap prop-picker drop-search-results"><table><thead><tr><th>道具</th><th>类型</th><th>操作</th></tr></thead><tbody>
			<?php foreach ($searchRows as $row) { ?><tr><td><?php adminPropLabel($row); ?></td><td><?php echo adminH(adminPropTypeName($row)); ?></td><td><a class="btn secondary" href="item_drops.php?q=<?php echo rawurlencode($search); ?>&amp;prop_id=<?php echo intval($row['id']); ?>">查看获取途径</a></td></tr><?php } ?>
			</tbody></table></div><?php } ?>
		<?php } ?>
	</section>

	<?php if ($propId > 0) { ?>
	<section class="band">
		<?php if (!is_array($selectedProp)) { ?><div class="empty error-text">所选道具不存在</div>
		<?php } else { ?>
		<div class="section-head"><div><h2><?php echo adminH($selectedProp['name']); ?> 的获取途径</h2><div class="prop-meta"><span>id=<?php echo intval($selectedProp['id']); ?></span><?php adminPropTradeBadge($selectedProp); ?><span>怪物 <?php echo count($monsters); ?> 项 · 礼包/魔法石 <?php echo count($itemSources['packages']); ?> 项 · 合成/重铸 <?php echo count($itemSources['recipes']); ?> 项 · 任务 <?php echo count($itemSources['tasks']); ?> 项</span></div></div><a class="btn secondary" href="items.php?edit=<?php echo intval($selectedProp['id']); ?>">编辑道具</a></div>
		<div class="acquisition-panels">
		<details class="acquisition-panel">
			<summary><span><strong>怪物普通掉落</strong><small>查看怪物、出现区域和掉落概率，可批量删除配置</small></span><b><?php echo count($monsters); ?> 项</b></summary>
			<div class="acquisition-content">
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
			<?php } ?>
			</div>
		</details>

		<details class="acquisition-panel">
			<summary><span><strong>礼包与魔法石开出</strong><small>随机奖励按运行代码的先后顺序计算实际概率</small></span><b><?php echo count($itemSources['packages']); ?> 项</b></summary>
			<div class="acquisition-content">
			<?php if (count($itemSources['packages']) === 0) { ?><div class="empty">没有礼包或魔法石可开出该道具</div>
			<?php } else { ?><div class="table-wrap"><table class="acquisition-table package-source-table"><thead><tr><th>来源道具</th><th>来源类型</th><th>获得数量</th><th>开出概率</th><th>开启条件</th><th>操作</th></tr></thead><tbody>
		<?php foreach ($itemSources['packages'] as $source) { ?>
			<tr>
				<td><div class="query-pet"><strong><?php echo adminH($source['source_name']); ?></strong><div class="prop-meta"><span>id=<?php echo intval($source['source_id']); ?></span><?php adminPropTradeBadge(array('propslock' => $source['source_propslock'])); ?></div></div></td>
				<td><span class="badge muted"><?php echo adminH($source['source_type'] . ' · ' . $source['mode']); ?></span></td>
				<td><strong>x<?php echo intval($source['count']); ?></strong></td>
				<td><div class="probability-detail"><strong><?php echo adminH(adminDropFormatProbability($source['probability'])); ?>%</strong><span><?php echo adminH($source['configured']); ?><?php if ($source['position'] > 0) echo ' · 顺序第 ' . intval($source['position']) . ' 项'; ?></span></div></td>
				<td><div class="source-condition"><?php if ($source['key_id'] > 0) { $keyProp = isset($itemSources['props'][$source['key_id']]) ? $itemSources['props'][$source['key_id']] : false; ?><span>钥匙：<?php echo adminH(is_array($keyProp) ? $keyProp['name'] : '道具不存在'); ?>（id=<?php echo intval($source['key_id']); ?> · <?php echo adminH(is_array($keyProp) ? adminPropTradeText($keyProp) : '交易状态未知'); ?>）</span><?php } ?><?php if ($source['requires'] !== '') { ?><span>要求：<?php echo adminH($source['requires']); ?></span><?php } ?><?php if ($source['key_id'] < 1 && $source['requires'] === '') { ?><span class="subtle">无额外条件</span><?php } ?></div></td>
				<td><a class="btn secondary" href="items.php?edit=<?php echo intval($source['source_id']); ?>">编辑来源</a></td>
			</tr>
		<?php } ?>
		</tbody></table></div><?php } ?>
			</div>
		</details>

		<details class="acquisition-panel">
			<summary><span><strong>道具合成产出</strong><small>包含固定合成、顺序判定的随机合成和重铸产物</small></span><b><?php echo count($itemSources['recipes']); ?> 项</b></summary>
			<div class="acquisition-content">
			<?php if (count($itemSources['recipes']) === 0) { ?><div class="empty">没有合成或重铸途径产出该道具</div>
			<?php } else { ?><div class="table-wrap"><table class="acquisition-table recipe-source-table"><thead><tr><th>配方道具</th><th>方式</th><th>材料</th><th>获得数量</th><th>产出概率</th><th>操作</th></tr></thead><tbody>
		<?php foreach ($itemSources['recipes'] as $source) { ?>
			<tr>
				<td><div class="query-pet"><strong><?php echo adminH($source['source_name']); ?></strong><div class="prop-meta"><span>id=<?php echo intval($source['source_id']); ?></span><?php adminPropTradeBadge(array('propslock' => $source['source_propslock'])); ?></div></div></td>
				<td><span class="badge <?php echo $source['mode'] === '固定合成' ? 'success' : 'warning'; ?>"><?php echo adminH($source['mode']); ?></span></td>
				<td><div class="material-group"><span class="subtle"><?php echo adminH($source['material_label']); ?></span><div class="material-list"><?php foreach ($source['materials'] as $materialId => $materialCount) { $materialProp = isset($itemSources['props'][$materialId]) ? $itemSources['props'][$materialId] : false; ?><span class="material-chip"><strong><?php echo adminH(is_array($materialProp) ? $materialProp['name'] : '道具不存在'); ?></strong><em>id=<?php echo intval($materialId); ?> · <?php echo adminH(is_array($materialProp) ? adminPropTradeText($materialProp) : '交易状态未知'); ?><?php if ($source['material_label'] === '所需材料') echo ' · x' . intval($materialCount); ?></em></span><?php } ?></div></div></td>
				<td><strong>x<?php echo intval($source['count']); ?></strong></td>
				<td><div class="probability-detail"><strong><?php echo adminH(adminDropFormatProbability($source['probability'])); ?>%</strong><span><?php echo adminH($source['configured']); ?><?php if ($source['position'] > 0) echo ' · 顺序第 ' . intval($source['position']) . ' 项'; ?></span></div></td>
				<td><a class="btn secondary" href="items.php?edit=<?php echo intval($source['source_id']); ?>">编辑配方</a></td>
			</tr>
		<?php } ?>
		</tbody></table></div><?php } ?>
			</div>
		</details>

		<details class="acquisition-panel">
			<summary><span><strong>任务奖励来源</strong><small>包含固定、可交易、等级条件和顺序判定的随机任务奖励</small></span><b><?php echo count($itemSources['tasks']); ?> 项</b></summary>
			<div class="acquisition-content">
			<?php if (count($itemSources['tasks']) === 0) { ?><div class="empty">没有任务奖励该道具</div>
			<?php } else { ?><div class="table-wrap"><table class="acquisition-table task-source-table"><thead><tr><th>任务</th><th>奖励类型</th><th>获得数量</th><th>奖励概率/条件</th><th>任务状态</th><th>操作</th></tr></thead><tbody>
		<?php foreach ($itemSources['tasks'] as $source) { ?>
			<tr>
				<td><div class="query-pet"><strong><?php echo adminH($source['task_title']); ?></strong><span>id=<?php echo intval($source['task_id']); ?> · color=<?php echo intval($source['color']); ?><?php if ($source['flags'] > 0) echo ' · flags=' . intval($source['flags']); ?></span></div></td>
				<td><span class="badge <?php echo $source['mode'] === '随机奖励' ? 'warning' : ($source['mode'] === '固定奖励' ? 'success' : 'muted'); ?>"><?php echo adminH($source['mode']); ?></span></td>
				<td><strong>x<?php echo intval($source['count']); ?></strong></td>
				<td><div class="probability-detail"><strong><?php echo adminH(adminDropFormatProbability($source['probability'])); ?>%</strong><span><?php echo adminH($source['configured']); ?><?php if ($source['position'] > 0) echo ' · 顺序第 ' . intval($source['position']) . ' 项'; ?></span><?php if ($source['condition'] !== '') { ?><span><?php echo adminH($source['condition']); ?></span><?php } ?></div></td>
				<td><div class="task-source-state"><div><span class="badge <?php echo adminH($source['visibility_class']); ?>"><?php echo adminH($source['visibility']); ?></span><?php if ($source['flags'] > 0) { ?><span class="badge warning">限时</span><?php } ?></div><span><?php echo adminH($source['repeat']); ?></span><?php if ($source['limitlv'] !== '') { ?><span>接取限制：<?php echo adminH($source['limitlv']); ?></span><?php } ?><?php if ($source['schedule'] !== '') { ?><span>活动时间：<?php echo adminH($source['schedule']); ?></span><?php } ?></div></td>
				<td><a class="btn secondary" href="tasks.php?edit=<?php echo intval($source['task_id']); ?>">编辑任务</a></td>
			</tr>
		<?php } ?>
		</tbody></table></div><?php } ?>
			</div>
		</details>
		</div>
		<?php } ?>
	</section>
	<?php } ?>
<?php adminPageEnd(); ?>
