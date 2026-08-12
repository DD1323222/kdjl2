<?php
require_once(dirname(__FILE__) . '/_bootstrap.php');
require_once(dirname(__FILE__) . '/_layout.php');
require_once(dirname(__FILE__) . '/_catalog_helpers.php');
require_once(dirname(__FILE__) . '/_gift_helpers.php');

$sourceQuery = trim((string)adminRequest('q'));
$sourceType = (string)adminRequest('source_type', 'all');
if (!in_array($sourceType, array('all', '12', '22'), true)) $sourceType = 'all';
$sourceId = intval(adminRequest('source_id', 0));
$itemQuery = trim((string)adminRequest('item_q'));
$outputId = intval(adminRequest('output_id', 0));
$page = max(1, intval(adminRequest('page', 1)));

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST')
{
	$action = adminPost('action');
	$sourceId = intval(adminPost('source_id'));
	$sourceQuery = trim((string)adminPost('q'));
	$sourceType = (string)adminPost('source_type', 'all');
	if (!in_array($sourceType, array('all', '12', '22'), true)) $sourceType = 'all';
	$itemQuery = trim((string)adminPost('item_q'));
	$outputId = intval(adminPost('output_id'));
	$page = max(1, intval(adminPost('page', 1)));
	$backUrl = adminGiftBackUrl($sourceId, $sourceQuery, $sourceType, $page, $itemQuery, $outputId);
	$errors = array();
	if (!adminCatalogVerifyCsrf()) $errors[] = '页面校验已失效，请刷新后重试。';
	if (!in_array($action, array('save_entry', 'delete_entry', 'add_entry', 'move_up', 'move_down'), true)) $errors[] = '操作参数无效。';
	if ($sourceId < 1) $errors[] = '请选择需要管理的礼包或使用道具。';

	$transaction = false;
	if (count($errors) === 0)
	{
		$transaction = adminStartTransaction($adminDb);
		if (!$transaction) $errors[] = '无法开启数据库事务。';
	}
	$source = false;
	if ($transaction)
	{
		$source = $adminDb->getOneRecord('SELECT id,name,varyname,effect FROM props WHERE id=' . $sourceId . ' FOR UPDATE');
		if (!is_array($source) || !in_array(intval($source['varyname']), array(12, 22), true))
			$errors[] = '所选道具不存在，或不是礼包/魔法石类型。';
	}

	$parsed = is_array($source) ? adminGiftParseSource($source) : false;
	$postedHash = (string)adminPost('effect_hash');
	if (is_array($source) && ($postedHash === '' || $postedHash !== sha1((string)$source['effect'])))
		$errors[] = '该道具效果已被其他操作修改，请刷新页面后重试。';
	$entries = is_array($parsed) && isset($parsed['entries']) ? $parsed['entries'] : array();
	if (count($errors) === 0 && !is_array($parsed)) $errors[] = '无法读取当前道具效果。';
	if (count($errors) === 0 && !$parsed['supported'])
	{
		if ($action !== 'add_entry' || $parsed['status'] !== 'empty')
			$errors[] = '当前效果协议不能在本页结构化修改。';
		else
		{
			$parsed = adminGiftInitializeParsed((string)adminPost('new_mode'));
			if (!is_array($parsed)) $errors[] = '请选择固定奖励或随机奖励模式。';
			else $entries = array();
		}
	}

	$entryIndex = intval(adminPost('entry_index', -1));
	if (count($errors) === 0 && $action === 'delete_entry')
	{
		if (!isset($entries[$entryIndex])) $errors[] = '要删除的奖励项已不存在。';
		else if (count($entries) <= 1) $errors[] = '至少要保留一个奖励；若要清空效果，请在道具修改管理中处理。';
		else array_splice($entries, $entryIndex, 1);
	}
	if (count($errors) === 0 && ($action === 'move_up' || $action === 'move_down'))
	{
		if ($parsed['mode'] !== 'random') $errors[] = '固定奖励不需要调整判定顺序。';
		else if (!isset($entries[$entryIndex])) $errors[] = '要移动的奖励项已不存在。';
		else
		{
			$targetIndex = $action === 'move_up' ? $entryIndex - 1 : $entryIndex + 1;
			if (!isset($entries[$targetIndex])) $errors[] = '奖励项已经位于可移动范围的边缘。';
			else
			{
				$swapEntry = $entries[$targetIndex];
				$entries[$targetIndex] = $entries[$entryIndex];
				$entries[$entryIndex] = $swapEntry;
			}
		}
	}

	if (count($errors) === 0 && ($action === 'save_entry' || $action === 'add_entry'))
	{
		if ($action === 'save_entry' && !isset($entries[$entryIndex])) $errors[] = '要修改的奖励项已不存在。';
		if ($outputId < 1) $errors[] = '请选择产出道具。';
		$output = $outputId > 0 ? $adminDb->getOneRecord('SELECT id,name FROM props WHERE id=' . $outputId . ' LIMIT 1') : false;
		if (!is_array($output)) $errors[] = '选择的产出道具不存在。';
		$countText = trim((string)adminPost('count'));
		$count = preg_match('/^[0-9]+$/D', $countText) ? intval($countText) : 0;
		if ($count < 1) $errors[] = '产出数量必须是大于 0 的整数。';
		$entry = array('pid' => $outputId, 'count' => $count);
		if (is_array($parsed) && $parsed['mode'] === 'random')
		{
			$denominatorText = trim((string)adminPost('denominator'));
			$denominator = preg_match('/^[0-9]+$/D', $denominatorText) ? intval($denominatorText) : 0;
			$notice = intval(adminPost('notice', 1));
			if ($denominator < 1) $errors[] = '随机分母必须是大于 0 的整数。';
			if ($notice !== 1 && $notice !== 2) $errors[] = '公告标记只能选择 1 或 2。';
			$entry['denominator'] = $denominator;
			$entry['notice'] = $notice;
			$entry['notice_explicit'] = true;
			if ($action === 'save_entry' && isset($entries[$entryIndex]) &&
				isset($entries[$entryIndex]['notice_explicit']) && !$entries[$entryIndex]['notice_explicit'] &&
				$notice === intval($entries[$entryIndex]['notice']))
			{
				$entry['notice_explicit'] = false;
			}
		}
		if (count($errors) === 0)
		{
			if ($action === 'save_entry') $entries[$entryIndex] = $entry;
			else $entries[] = $entry;
		}
	}

	$newEffect = count($errors) === 0 ? adminGiftBuildEffect($parsed, $entries) : false;
	if (count($errors) === 0 && ($newEffect === false || strlen($newEffect) > 1000))
		$errors[] = '保存后的 effect 无效或超过 props.effect 的 1000 字符上限。';
	if (count($errors) === 0 && !$adminDb->query("UPDATE props SET effect='" . $adminDb->escape($newEffect) . "' WHERE id={$sourceId} LIMIT 1"))
		$errors[] = '礼包奖励保存失败。';
	if (count($errors) === 0 && !$adminDb->query('COMMIT')) $errors[] = '礼包奖励提交失败。';
	if (count($errors) > 0)
	{
		if ($transaction) $adminDb->query('ROLLBACK');
		adminSetFlash('error', implode(' ', $errors));
		adminRedirect($backUrl);
	}

	$cacheOk = adminRefreshPropsCache($adminDb, $adminMem);
	$message = '已更新 id=' . $sourceId . ' ' . $source['name'] . ' 的奖励清单。';
	if (!$cacheOk) $message .= ' 数据库已保存，但 props 缓存刷新失败，请访问 vm1.php 更新缓存。';
	adminSetFlash($cacheOk ? 'success' : 'warning', $message);
	adminRedirect(adminGiftBackUrl($sourceId, $sourceQuery, $sourceType, $page, '', 0));
}

$selectedSource = $sourceId > 0 ? $adminDb->getOneRecord(
	'SELECT id,name,varyname,effect,requires,usages,propslock FROM props WHERE id=' . $sourceId . ' LIMIT 1'
) : false;
$parsedSource = is_array($selectedSource) ? adminGiftParseSource($selectedSource) : false;
$contentProps = array();
$actualProbabilities = array();
if (is_array($parsedSource))
{
	$ids = array();
	foreach ($parsedSource['entries'] as $entry) $ids[] = intval($entry['pid']);
	$contentProps = adminGiftLoadProps($adminDb, $ids);
	if ($parsedSource['mode'] === 'random') $actualProbabilities = adminGiftActualProbabilities($parsedSource['entries']);
}

$sourceWhere = $sourceType === 'all' ? 'varyname IN(12,22)' : 'varyname=' . intval($sourceType);
if ($sourceQuery !== '')
{
	$escapedQuery = $adminDb->escape($sourceQuery);
	$nameWhere = "name LIKE '%{$escapedQuery}%'";
	if (preg_match('/^[0-9]+$/D', $sourceQuery)) $nameWhere = '(id=' . intval($sourceQuery) . ' OR ' . $nameWhere . ')';
	$sourceWhere .= ' AND ' . $nameWhere;
}
$pageSize = 100;
$countRow = $adminDb->getOneRecord('SELECT COUNT(*) AS total FROM props WHERE ' . $sourceWhere);
$sourceTotal = is_array($countRow) ? intval($countRow['total']) : 0;
$maxPage = max(1, intval(ceil($sourceTotal / $pageSize)));
if ($page > $maxPage) $page = $maxPage;
$offset = ($page - 1) * $pageSize;
$sourceRows = $adminDb->getRecords('SELECT id,name,varyname,effect,propslock FROM props WHERE ' . $sourceWhere .
	' ORDER BY varyname,id LIMIT ' . $offset . ',' . $pageSize);
if (!is_array($sourceRows)) $sourceRows = array();

$itemRows = array();
if ($itemQuery !== '')
{
	$escapedItemQuery = $adminDb->escape($itemQuery);
	$itemWhere = "name LIKE '%{$escapedItemQuery}%'";
	if (preg_match('/^[0-9]+$/D', $itemQuery)) $itemWhere = '(id=' . intval($itemQuery) . ' OR ' . $itemWhere . ')';
	$itemRows = $adminDb->getRecords('SELECT id,name,varyname,propslock FROM props WHERE ' . $itemWhere . ' ORDER BY id LIMIT 100');
	if (!is_array($itemRows)) $itemRows = array();
}
$selectedOutput = $outputId > 0 ? $adminDb->getOneRecord(
	'SELECT id,name,varyname,propslock FROM props WHERE id=' . $outputId . ' LIMIT 1'
) : false;

adminPageStart('礼包物品管理', 'gift_items');
?>
	<section class="band">
		<div class="section-head"><div><h2>查询礼包或使用道具</h2><div class="subtle">管理礼包、宝箱和魔法石使用后固定或随机开出的道具；按 id 或名称模糊搜索。</div></div></div>
		<form class="filters" method="get" action="gift_items.php">
			<select class="select gift-type-select" name="source_type"><option value="all"<?php echo $sourceType === 'all' ? ' selected="selected"' : ''; ?>>礼包与魔法石</option><option value="12"<?php echo $sourceType === '12' ? ' selected="selected"' : ''; ?>>礼包/宝箱</option><option value="22"<?php echo $sourceType === '22' ? ' selected="selected"' : ''; ?>>魔法石</option></select>
			<input class="input drop-search" type="search" name="q" value="<?php echo adminH($sourceQuery); ?>" placeholder="来源道具 id 或名称（留空列出全部）" />
			<button class="btn primary" type="submit">查询</button><span class="subtle">共 <?php echo $sourceTotal; ?> 项，第 <?php echo $page; ?>/<?php echo $maxPage; ?> 页</span>
		</form>
		<div class="table-wrap gift-source-results"><table class="gift-source-table"><thead><tr><th>来源道具</th><th>来源类型</th><th>奖励模式</th><th>奖励项</th><th>操作</th></tr></thead><tbody>
		<?php foreach ($sourceRows as $row) { $sourceParsed = adminGiftParseSource($row); ?>
		<tr><td><?php adminPropLabel($row); ?></td><td><?php echo adminH(adminGiftSourceTypeLabel($row)); ?></td><td><span class="badge <?php echo $sourceParsed['status'] === 'supported' ? 'success' : ($sourceParsed['status'] === 'empty' ? 'muted' : 'warning'); ?>"><?php echo adminH($sourceParsed['mode_label']); ?></span></td><td><?php echo count($sourceParsed['entries']); ?></td><td><a class="btn secondary" href="gift_items.php?source_id=<?php echo intval($row['id']); ?>&amp;q=<?php echo rawurlencode($sourceQuery); ?>&amp;source_type=<?php echo adminH($sourceType); ?>&amp;page=<?php echo $page; ?>">管理内容</a></td></tr>
		<?php } ?>
		</tbody></table></div>
		<?php if (count($sourceRows) === 0) { ?><div class="empty">没有匹配的礼包或魔法石。</div><?php } ?>
		<?php if ($maxPage > 1) { ?><div class="catalog-pager"><?php if ($page > 1) { ?><a class="btn secondary" href="gift_items.php?q=<?php echo rawurlencode($sourceQuery); ?>&amp;source_type=<?php echo adminH($sourceType); ?>&amp;page=<?php echo $page - 1; ?>">上一页</a><?php } ?><?php if ($page < $maxPage) { ?><a class="btn secondary" href="gift_items.php?q=<?php echo rawurlencode($sourceQuery); ?>&amp;source_type=<?php echo adminH($sourceType); ?>&amp;page=<?php echo $page + 1; ?>">下一页</a><?php } ?></div><?php } ?>
	</section>

	<?php if ($sourceId > 0) { ?>
	<section class="band">
		<?php if (!is_array($selectedSource)) { ?><div class="empty error-text">所选来源道具不存在。</div>
		<?php } else { ?>
		<div class="section-head"><div><h2><?php echo adminH($selectedSource['name']); ?> 的开出内容</h2><div class="prop-meta"><span>id=<?php echo intval($selectedSource['id']); ?></span><?php adminPropTradeBadge($selectedSource); ?><span><?php echo adminH(adminGiftSourceTypeLabel($selectedSource)); ?></span><span><?php echo adminH($parsedSource['mode_label']); ?></span></div></div><a class="btn secondary" href="items.php?edit=<?php echo intval($selectedSource['id']); ?>">完整编辑道具</a></div>
		<?php if (count($parsedSource['errors']) > 0) { ?><div class="protocol-notice error-text"><?php echo adminH(implode(' ', $parsedSource['errors'])); ?></div><?php } ?>
		<?php if ($parsedSource['mode'] === 'random') { ?><div class="protocol-notice">随机奖励严格按表格顺序逐项判定，某项命中后停止；“实际顺序概率”已计入前面项目未命中的概率。</div><?php } ?>
		<?php if ($parsedSource['prefix'] !== '') { ?><div class="protocol-notice">保留的前置效果：<span class="code"><?php echo adminH(rtrim($parsedSource['prefix'], ',')); ?></span></div><?php } ?>

		<?php if ($parsedSource['supported']) { ?>
		<div class="table-wrap"><table class="gift-content-table"><thead><tr><th>顺序</th><th>开出道具</th><th>配置与操作</th></tr></thead><tbody>
		<?php foreach ($parsedSource['entries'] as $index => $entry) { $content = isset($contentProps[intval($entry['pid'])]) ? $contentProps[intval($entry['pid'])] : false; ?>
		<tr><td><?php echo $index + 1; ?></td><td><?php if (is_array($content)) adminPropLabel($content); else { ?><div class="query-pet"><strong class="error-text">道具定义不存在</strong><span>id=<?php echo intval($entry['pid']); ?></span></div><?php } ?></td><td><form class="gift-entry-form" method="post" action="gift_items.php">
			<input type="hidden" name="csrf_token" value="<?php echo adminH(adminCatalogCsrfToken()); ?>" /><input type="hidden" name="source_id" value="<?php echo intval($sourceId); ?>" /><input type="hidden" name="q" value="<?php echo adminH($sourceQuery); ?>" /><input type="hidden" name="source_type" value="<?php echo adminH($sourceType); ?>" /><input type="hidden" name="page" value="<?php echo $page; ?>" /><input type="hidden" name="effect_hash" value="<?php echo sha1((string)$selectedSource['effect']); ?>" /><input type="hidden" name="entry_index" value="<?php echo $index; ?>" /><input type="hidden" name="output_id" value="<?php echo intval($entry['pid']); ?>" />
			<div class="field gift-count"><label>数量</label><input class="input" type="number" min="1" name="count" value="<?php echo intval($entry['count']); ?>" /></div>
			<?php if ($parsedSource['mode'] === 'random') { ?><div class="field gift-denominator"><label>随机分母（1/N）</label><input class="input" type="number" min="1" name="denominator" value="<?php echo intval($entry['denominator']); ?>" /></div><div class="field gift-notice"><label>系统公告</label><select class="select" name="notice"><option value="1"<?php echo intval($entry['notice']) === 1 ? ' selected="selected"' : ''; ?>>不公告 (1)</option><option value="2"<?php echo intval($entry['notice']) === 2 ? ' selected="selected"' : ''; ?>>公告 (2)</option></select></div><div class="gift-probability"><span>配置 1/<?php echo intval($entry['denominator']); ?></span><strong>实际 <?php echo adminH(adminGiftProbabilityText(isset($actualProbabilities[$index]) ? $actualProbabilities[$index] : 0)); ?></strong></div><?php } else { ?><div class="gift-probability"><strong>固定发放</strong></div><?php } ?>
			<div class="actions"><?php if ($parsedSource['mode'] === 'random') { ?><button class="btn secondary compact" type="submit" name="action" value="move_up"<?php echo $index === 0 ? ' disabled="disabled"' : ''; ?>>上移</button><button class="btn secondary compact" type="submit" name="action" value="move_down"<?php echo $index === count($parsedSource['entries']) - 1 ? ' disabled="disabled"' : ''; ?>>下移</button><?php } ?><button class="btn secondary" type="submit" name="action" value="save_entry">保存</button><button class="btn danger" type="submit" name="action" value="delete_entry" onclick="return confirm('确定删除这一项开出内容吗？');">删除</button></div>
		</form></td></tr><?php } ?>
		</tbody></table></div>
		<?php } else if ($parsedSource['status'] === 'empty') { ?><div class="empty">这个道具尚未配置开出内容。选择第一项道具时可同时建立固定或随机奖励池。</div>
		<?php } else { ?><div class="empty warning-text">当前 effect 不是可安全编辑的固定礼包或随机礼包协议，请使用“完整编辑道具”查看原始配置。</div><?php } ?>

		<?php if ($parsedSource['supported'] || $parsedSource['status'] === 'empty') { ?>
		<div class="gift-add-panel"><h3>添加开出道具</h3>
			<form class="form-row" method="get" action="gift_items.php"><input type="hidden" name="source_id" value="<?php echo intval($sourceId); ?>" /><input type="hidden" name="q" value="<?php echo adminH($sourceQuery); ?>" /><input type="hidden" name="source_type" value="<?php echo adminH($sourceType); ?>" /><input type="hidden" name="page" value="<?php echo $page; ?>" /><input class="input drop-search" type="search" name="item_q" value="<?php echo adminH($itemQuery); ?>" placeholder="产出道具 id 或名称" required="required" /><button class="btn secondary" type="submit">搜索道具</button></form>
			<?php if ($itemQuery !== '') { ?><div class="table-wrap prop-picker gift-item-results"><table><thead><tr><th>道具</th><th>操作</th></tr></thead><tbody><?php foreach ($itemRows as $row) { ?><tr><td><?php adminPropLabel($row); ?></td><td><a class="btn secondary" href="<?php echo adminH(adminGiftBackUrl($sourceId, $sourceQuery, $sourceType, $page, $itemQuery, $row['id'])); ?>">选择</a></td></tr><?php } ?></tbody></table></div><?php if (count($itemRows) === 0) { ?><div class="empty">没有匹配的产出道具。</div><?php } ?><?php } ?>
			<?php if (is_array($selectedOutput)) { ?><form class="gift-new-entry" method="post" action="gift_items.php">
				<input type="hidden" name="action" value="add_entry" /><input type="hidden" name="csrf_token" value="<?php echo adminH(adminCatalogCsrfToken()); ?>" /><input type="hidden" name="source_id" value="<?php echo intval($sourceId); ?>" /><input type="hidden" name="q" value="<?php echo adminH($sourceQuery); ?>" /><input type="hidden" name="source_type" value="<?php echo adminH($sourceType); ?>" /><input type="hidden" name="page" value="<?php echo $page; ?>" /><input type="hidden" name="item_q" value="<?php echo adminH($itemQuery); ?>" /><input type="hidden" name="effect_hash" value="<?php echo sha1((string)$selectedSource['effect']); ?>" /><input type="hidden" name="output_id" value="<?php echo intval($selectedOutput['id']); ?>" />
				<div class="gift-selected-output"><?php adminPropLabel($selectedOutput); ?></div>
				<?php if ($parsedSource['status'] === 'empty') { ?><div class="field"><label>新奖励池模式</label><select class="select" name="new_mode"><option value="fixed">固定全部开出</option><option value="random">随机顺序开出</option></select></div><?php } ?>
				<div class="field"><label>数量</label><input class="input" type="number" min="1" name="count" value="1" /></div>
				<?php if ($parsedSource['mode'] === 'random' || $parsedSource['status'] === 'empty') { ?><div class="field"><label>随机分母（随机模式使用）</label><input class="input" type="number" min="1" name="denominator" value="1" /></div><div class="field"><label>系统公告（随机模式使用）</label><select class="select" name="notice"><option value="1">不公告 (1)</option><option value="2">公告 (2)</option></select></div><?php } ?>
				<button class="btn primary" type="submit">添加到奖励池</button>
			</form><?php } ?>
		</div><?php } ?>
		<details class="gift-raw-effect"><summary>查看原始 effect</summary><div class="code"><?php echo adminH($selectedSource['effect']); ?></div></details>
		<?php } ?>
	</section>
	<?php } ?>
<?php adminPageEnd(); ?>
