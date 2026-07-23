<?php
require_once(dirname(__FILE__) . '/_bootstrap.php');
require_once(dirname(__FILE__) . '/_layout.php');

$categoryNames = array(1 => '热卖', 2 => '进化合成', 3 => '宠物相关', 4 => '装备相关');
$returnUrl = 'limited.php';

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST')
{
	$action = adminPost('action');
	$config = adminGetLimitedConfig($adminDb);
	$items = adminParseLimitedItems($config['contents']);

	if ($action === 'save_window')
	{
		$start = adminNormalizeDate(adminPost('start_time'));
		$end = adminNormalizeDate(adminPost('end_time'));
		if ($start === false || $end === false || $start === '' || $end === '' || $start >= $end)
		{
			adminSetFlash('error', '抢购活动必须填写有效的开始和结束时间。');
			adminRedirect($returnUrl);
		}
		$value2 = adminSqlDateTime($start) . '|' . adminSqlDateTime($end);
		$resetSales = isset($_POST['reset_sales']);
		if (!adminStartTransaction($adminDb))
		{
			adminSetFlash('error', '活动时间保存失败：无法开始数据库事务。');
			adminRedirect($returnUrl);
		}
		$lockedConfig = $adminDb->getOneRecord("SELECT Id,value2,contents FROM welcome WHERE code='timelimitbuy' LIMIT 1 FOR UPDATE");
		if (is_array($lockedConfig))
		{
			$config = $lockedConfig;
			$items = adminParseLimitedItems($config['contents']);
		}
		if (!adminSaveLimitedConfig($adminDb, $config, $value2, $items))
		{
			$adminDb->query('ROLLBACK');
			adminSetFlash('error', '活动时间保存失败：' . $adminDb->getError());
			adminRedirect($returnUrl);
		}
		if (!$adminDb->query('COMMIT'))
		{
			$adminDb->query('ROLLBACK');
			adminSetFlash('error', '抢购活动时间提交失败。');
			adminRedirect($returnUrl);
		}
		if ($resetSales)
		{
			foreach ($items as $itemId => $stock) $adminMem->del('zhekou_' . intval($itemId) . '_num');
		}
		$cacheOk = adminRefreshWelcomeCache($adminDb, $adminMem);
		adminSetFlash($cacheOk ? 'success' : 'warning', '抢购活动时间已保存' . ($resetSales ? '，已售数量已重置' : '') . ($cacheOk ? '。' : '，但活动缓存刷新失败。'));
		adminRedirect($returnUrl);
	}

	if ($action === 'batch_take_down')
	{
		$selectedIds = adminSelectedIds(isset($_POST['selected_ids']) ? $_POST['selected_ids'] : array());
		if (count($selectedIds) === 0)
		{
			adminSetFlash('error', '请先选择要下架的抢购商品。');
			adminRedirect($returnUrl);
		}
		$idList = implode(',', $selectedIds);
		if (!adminStartTransaction($adminDb))
		{
			adminSetFlash('error', '抢购商品批量下架失败：无法开始数据库事务。');
			adminRedirect($returnUrl);
		}
		$batchRows = $adminDb->getRecords("SELECT id,yb,sj,vip FROM props WHERE id IN ({$idList}) FOR UPDATE");
		$batchOk = is_array($batchRows);
		if (!$batchOk) $batchRows = array();
		$lockedConfig = $adminDb->getOneRecord("SELECT Id,value2,contents FROM welcome WHERE code='timelimitbuy' LIMIT 1 FOR UPDATE");
		if (is_array($lockedConfig))
		{
			$config = $lockedConfig;
			$items = adminParseLimitedItems($config['contents']);
		}
		$changedIds = array();
		foreach ($selectedIds as $id)
		{
			if (isset($items[$id]))
			{
				unset($items[$id]);
				$changedIds[] = $id;
			}
		}
		foreach ($batchRows as $batchRow)
		{
			$id = intval($batchRow['id']);
			if (!$adminDb->query("UPDATE props SET zhekouyb=0 WHERE id={$id}"))
			{
				$batchOk = false;
				break;
			}
			if (!adminSalePriceActive($batchRow['yb']) && !adminSalePriceActive($batchRow['sj']) &&
				!adminSalePriceActive($batchRow['vip']) &&
				!$adminDb->query("UPDATE props SET yb=0,sj=0,vip=0,zhekouyb=0,stime=0,timelimit='' WHERE id={$id}"))
			{
				$batchOk = false;
				break;
			}
		}
		if ($batchOk) $batchOk = adminSaveLimitedConfig($adminDb, $config, $config['value2'], $items) ? true : false;
		if (!$batchOk || !$adminDb->query('COMMIT'))
		{
			$adminDb->query('ROLLBACK');
			adminSetFlash('error', '抢购商品批量下架失败：' . $adminDb->getError());
			adminRedirect($returnUrl);
		}
		foreach ($changedIds as $id) $adminMem->del('zhekou_' . intval($id) . '_num');
		$cacheOk = adminRefreshPropsCache($adminDb, $adminMem) && adminRefreshWelcomeCache($adminDb, $adminMem);
		adminSetFlash($cacheOk ? 'success' : 'warning', '已从抢购商城批量下架 ' . count($changedIds) . ' 件商品' . ($cacheOk ? '。' : '，但缓存刷新失败。'));
		adminRedirect($returnUrl);
	}

	$propId = intval(adminPost('prop_id', 0));
	$prop = $propId > 0 ? $adminDb->getOneRecord("SELECT * FROM props WHERE id={$propId} LIMIT 1") : false;
	if (!is_array($prop))
	{
		adminSetFlash('error', '没有找到该道具。');
		adminRedirect($returnUrl);
	}

	if ($action === 'take_down')
	{
		if (!adminStartTransaction($adminDb))
		{
			adminSetFlash('error', '抢购下架失败：无法开始数据库事务。');
			adminRedirect($returnUrl);
		}
		$lockedProp = $adminDb->getOneRecord("SELECT * FROM props WHERE id={$propId} FOR UPDATE");
		if (!is_array($lockedProp))
		{
			$adminDb->query('ROLLBACK');
			adminSetFlash('error', '抢购下架失败：道具记录不存在。');
			adminRedirect($returnUrl);
		}
		$lockedConfig = $adminDb->getOneRecord("SELECT Id,value2,contents FROM welcome WHERE code='timelimitbuy' LIMIT 1 FOR UPDATE");
		if (is_array($lockedConfig))
		{
			$config = $lockedConfig;
			$items = adminParseLimitedItems($config['contents']);
		}
		$prop = $lockedProp;
		unset($items[$propId]);
		if (!$adminDb->query("UPDATE props SET zhekouyb=0 WHERE id={$propId}") ||
			!adminSaveLimitedConfig($adminDb, $config, $config['value2'], $items))
		{
			$adminDb->query('ROLLBACK');
			adminSetFlash('error', '抢购下架失败：' . $adminDb->getError());
			adminRedirect($returnUrl);
		}
		if (!adminSalePriceActive($prop['yb']) && !adminSalePriceActive($prop['sj']) &&
			!adminSalePriceActive($prop['vip']) &&
			!$adminDb->query("UPDATE props SET yb=0,sj=0,vip=0,zhekouyb=0,stime=0,timelimit='' WHERE id={$propId}"))
		{
			$adminDb->query('ROLLBACK');
			adminSetFlash('error', '抢购下架失败：' . $adminDb->getError());
			adminRedirect($returnUrl);
		}
		if (!$adminDb->query('COMMIT'))
		{
			$adminDb->query('ROLLBACK');
			adminSetFlash('error', '抢购下架提交失败。');
			adminRedirect($returnUrl);
		}
		$adminMem->del('zhekou_' . $propId . '_num');
		$cacheOk = adminRefreshPropsCache($adminDb, $adminMem) && adminRefreshWelcomeCache($adminDb, $adminMem);
		adminSetFlash($cacheOk ? 'success' : 'warning', '已从抢购商城下架 id=' . $propId . ' ' . $prop['name'] . ($cacheOk ? '。' : '，但缓存刷新失败。'));
		adminRedirect($returnUrl);
	}

	if ($action === 'publish')
	{
		$price = intval(adminPost('price', 0));
		$stock = intval(adminPost('stock', 0));
		$category = intval(adminPost('category', 0));
		$sortSuffix = adminPost('sort_suffix');
		if ($price < 1 || $price >= 99999 || $stock < 1)
		{
			adminSetFlash('error', '抢购价必须在 1 至 99998 之间，总库存必须大于 0。');
			adminRedirect($returnUrl);
		}
		if (!isset($categoryNames[$category]))
		{
			adminSetFlash('error', '商品分类无效。');
			adminRedirect($returnUrl);
		}
		$stime = adminStoreCode($category, $sortSuffix, $adminDb);
		if ($stime === false)
		{
			adminSetFlash('error', '排序编号必须是 1 至 6 位数字。');
			adminRedirect($returnUrl);
		}
		$start = adminNormalizeDate(adminPost('item_start'));
		$end = adminNormalizeDate(adminPost('item_end'));
		if ($start === false || $end === false || ($start !== '' && $end !== '' && $start > $end))
		{
			adminSetFlash('error', '单品上架时间范围无效。');
			adminRedirect($returnUrl);
		}
		$timelimit = $start === '' && $end === '' ? '' : $start . '|' . $end;
		$timeSql = $adminDb->escape($timelimit);
		if (!adminStartTransaction($adminDb))
		{
			adminSetFlash('error', '抢购上架失败：无法开始数据库事务。');
			adminRedirect($returnUrl);
		}
		$lockedProp = $adminDb->getOneRecord("SELECT * FROM props WHERE id={$propId} FOR UPDATE");
		if (!is_array($lockedProp))
		{
			$adminDb->query('ROLLBACK');
			adminSetFlash('error', '抢购上架失败：道具记录不存在。');
			adminRedirect($returnUrl);
		}
		$lockedConfig = $adminDb->getOneRecord("SELECT Id,value2,contents FROM welcome WHERE code='timelimitbuy' LIMIT 1 FOR UPDATE");
		if (is_array($lockedConfig))
		{
			$config = $lockedConfig;
			$items = adminParseLimitedItems($config['contents']);
		}
		$prop = $lockedProp;
		$otherChannels = adminOtherActiveChannels($prop, 'limit', $items);
		if (count($otherChannels) > 0 &&
			(intval($prop['stime']) !== $stime || adminStoredSchedule($prop['timelimit']) !== $timelimit))
		{
			$adminDb->query('ROLLBACK');
			adminSetFlash('error', '该道具同时在' . implode('、', $otherChannels) . '上架，分类、排序和单品时间需保持一致。');
			adminRedirect($returnUrl);
		}
		$setParts = array("zhekouyb={$price}", "stime={$stime}", "timelimit='{$timeSql}'");
		if (adminCategory($prop['stime']) === 0)
		{
			foreach (array('yb', 'sj', 'vip') as $normalField)
			{
				if (adminSalePriceActive($prop[$normalField])) $setParts[] = $normalField . '=0';
			}
		}
		$wasLimited = isset($items[$propId]);
		$items[$propId] = $stock;
		if (!$adminDb->query('UPDATE props SET ' . implode(',', $setParts) . " WHERE id={$propId}") ||
			!adminSaveLimitedConfig($adminDb, $config, $config['value2'], $items) ||
			!$adminDb->query('COMMIT'))
		{
			$adminDb->query('ROLLBACK');
			adminSetFlash('error', '抢购上架失败：' . $adminDb->getError());
			adminRedirect($returnUrl);
		}
		if (!$wasLimited) $adminMem->del('zhekou_' . $propId . '_num');
		$cacheOk = adminRefreshPropsCache($adminDb, $adminMem) && adminRefreshWelcomeCache($adminDb, $adminMem);
		adminSetFlash($cacheOk ? 'success' : 'warning', '已上架 id=' . $propId . ' ' . $prop['name'] . '，抢购价 ' . $price . ' 元宝，总库存 ' . $stock . ($cacheOk ? '。' : '，但缓存刷新失败。'));
		adminRedirect($returnUrl);
	}
}

$config = adminGetLimitedConfig($adminDb);
$items = adminParseLimitedItems($config['contents']);
$state = adminLimitedState($config['value2']);
$window = explode('|', $config['value2']);
$startInput = adminSqlDateInput(isset($window[0]) ? $window[0] : '');
$endInput = adminSqlDateInput(isset($window[1]) ? $window[1] : '');
$saleRows = array();
if (count($items) > 0)
{
	$ids = array_keys($items);
	$records = $adminDb->getRecords('SELECT id,name,varyname,zhekouyb,stime,timelimit,propslock FROM props WHERE id IN (' . implode(',', $ids) . ')');
	$byId = array();
	if (is_array($records)) foreach ($records as $row) $byId[intval($row['id'])] = $row;
	foreach ($items as $id => $stock)
	{
		$row = isset($byId[$id]) ? $byId[$id] : array('id' => $id, 'name' => '道具不存在', 'varyname' => 0, 'zhekouyb' => 0, 'stime' => 0, 'timelimit' => '', 'propslock' => null);
		$sold = kdjlSafeMemValue($adminMem->get('zhekou_' . intval($id) . '_num'), 0);
		$row['_stock'] = intval($stock);
		$row['_sold'] = max(0, intval($sold));
		$row['_remaining'] = max(0, intval($stock) - intval($sold));
		$row['_state'] = adminScheduleState($row['timelimit']);
		$saleRows[] = $row;
	}
}
$search = trim(adminGet('q'));
$searchRows = adminSearchProps($adminDb, $search);

adminPageStart('抢购商城管理', 'limited');
?>
	<section class="band">
		<div class="section-head"><div><h2>活动设置</h2><div class="subtle"><?php echo count($items); ?> 件商品</div></div><span class="badge <?php echo $state === '进行中' ? 'success' : 'warning'; ?>"><?php echo adminH($state); ?></span></div>
		<form class="form-row activity-form" method="post"><input type="hidden" name="action" value="save_window" /><div class="field"><label>开始时间</label><input class="input" type="datetime-local" name="start_time" value="<?php echo adminH($startInput); ?>" required="required" /></div><div class="field"><label>结束时间</label><input class="input" type="datetime-local" name="end_time" value="<?php echo adminH($endInput); ?>" required="required" /></div><label class="activity-check"><input type="checkbox" name="reset_sales" value="1" />重置已售数量</label><button class="btn primary" type="submit">保存活动时间</button></form>
	</section>

	<section class="band">
		<div class="section-head"><div><h2>抢购商品</h2><div class="subtle"><?php echo count($saleRows); ?> 件配置</div></div></div>
		<?php if (count($saleRows) === 0) { ?><div class="empty">暂无抢购商品</div><?php } else { $batchGroup = 'limited'; $batchForm = 'batch-limited'; ?>
		<form id="<?php echo $batchForm; ?>" method="post" data-confirm="确认批量下架选中的抢购商品？"><input type="hidden" name="action" value="batch_take_down" /></form>
		<div class="batch-bar"><label class="batch-check"><input type="checkbox" data-select-all="<?php echo $batchGroup; ?>" />全选</label><button class="btn danger" type="submit" form="<?php echo $batchForm; ?>" data-batch-submit="<?php echo $batchGroup; ?>" disabled="disabled">批量下架</button></div>
		<div class="table-wrap"><table><thead><tr><th class="select-cell">选择</th><th>道具</th><th>抢购价</th><th>总库存</th><th>已售</th><th>剩余</th><th>状态</th><th>操作</th></tr></thead><tbody>
		<?php foreach ($saleRows as $row) { ?><tr><td class="select-cell"><input type="checkbox" name="selected_ids[]" value="<?php echo intval($row['id']); ?>" form="<?php echo $batchForm; ?>" data-select-item="<?php echo $batchGroup; ?>" /></td><td><?php if (intval($row['varyname']) > 0) adminPropLabel($row); else { ?><div class="query-pet"><strong><?php echo adminH($row['name']); ?></strong><span>id=<?php echo intval($row['id']); ?></span></div><?php } ?></td><td><span class="badge limit"><?php echo intval($row['zhekouyb']); ?> 元宝</span></td><td><?php echo intval($row['_stock']); ?></td><td><?php echo intval($row['_sold']); ?></td><td><strong><?php echo intval($row['_remaining']); ?></strong></td><td><?php if (intval($row['stime']) < 1 || intval($row['zhekouyb']) < 1) { ?><span class="badge muted">配置无效</span><?php } else if ($row['_state'] !== 'active') { adminScheduleBadge($row['_state']); } else { ?><span class="badge <?php echo $state === '进行中' ? 'success' : 'warning'; ?>"><?php echo adminH($state); ?></span><?php } ?></td><td><div class="actions"><a class="btn secondary" href="limited.php?q=<?php echo intval($row['id']); ?>#search-results">编辑</a><form method="post" data-confirm="确认从抢购商城下架该商品？"><input type="hidden" name="action" value="take_down" /><input type="hidden" name="prop_id" value="<?php echo intval($row['id']); ?>" /><button class="btn danger" type="submit">下架</button></form></div></td></tr><?php } ?>
		</tbody></table></div>
		<?php } ?>
	</section>

	<section class="band" id="search-results">
		<div class="section-head"><div><h2>搜索并上架</h2><?php if ($search !== '') { ?><div class="subtle">找到 <?php echo count($searchRows); ?> 条结果</div><?php } ?></div><form class="form-row" method="get" action="limited.php#search-results"><input class="input" style="width:360px" type="search" name="q" value="<?php echo adminH($search); ?>" placeholder="道具 ID 或名称" /><button class="btn primary" type="submit">搜索</button></form></div>
		<?php if ($search !== '' && count($searchRows) === 0) { ?><div class="empty">没有匹配的道具</div><?php } else if (count($searchRows) > 0) { ?><div class="results">
		<?php foreach ($searchRows as $row) { $category = adminCategory($row['stime']); if ($category === 0) $category = 1; $parts = explode('|', (string)$row['timelimit']); $stock = isset($items[intval($row['id'])]) ? intval($items[intval($row['id'])]) : 1; ?>
		<div class="edit-row"><form class="edit-form limited" method="post"><input type="hidden" name="action" value="publish" /><input type="hidden" name="prop_id" value="<?php echo intval($row['id']); ?>" /><div><?php adminPropLabel($row); ?></div><div class="field"><label>抢购价（元宝）</label><input class="input" type="number" min="1" max="99998" name="price" value="<?php echo intval($row['zhekouyb']) > 0 && intval($row['zhekouyb']) < 99999 ? intval($row['zhekouyb']) : 1; ?>" required="required" /></div><div class="field"><label>总库存</label><input class="input" type="number" min="1" name="stock" value="<?php echo $stock; ?>" required="required" /></div><div class="field"><label>分类</label><select class="select" name="category"><?php foreach ($categoryNames as $key => $label) { ?><option value="<?php echo $key; ?>"<?php echo $category === $key ? ' selected="selected"' : ''; ?>><?php echo adminH($label); ?></option><?php } ?></select></div><div class="datetime-pair"><div class="field"><label>单品开始（共享）</label><input class="input" type="datetime-local" name="item_start" value="<?php echo adminH(isset($parts[0]) ? adminCompactDateInput($parts[0]) : ''); ?>" /></div><div class="field"><label>单品结束（共享）</label><input class="input" type="datetime-local" name="item_end" value="<?php echo adminH(isset($parts[1]) ? adminCompactDateInput($parts[1]) : ''); ?>" /></div></div><div class="field"><label>分类内排序号</label><input class="input" name="sort_suffix" value="<?php echo adminH(adminSortSuffix($row['stime'])); ?>" placeholder="自动" /></div><button class="btn primary" type="submit"><?php echo isset($items[intval($row['id'])]) ? '更新' : '上架'; ?></button></form></div>
		<?php } ?></div><?php } ?>
	</section>
<?php adminPageEnd(); ?>
