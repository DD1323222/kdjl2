<?php
require_once(dirname(__FILE__) . '/_bootstrap.php');
require_once(dirname(__FILE__) . '/_layout.php');

$channel = isset($adminDefaultChannel) ? $adminDefaultChannel : adminRequest('channel', 'yb');
$shops = array(
	'yb' => array('field' => 'yb', 'title' => '神秘商店管理', 'unit' => '元宝'),
	'sj' => array('field' => 'sj', 'title' => '水晶商店管理', 'unit' => '水晶'),
	'vip' => array('field' => 'vip', 'title' => 'VIP商城管理', 'unit' => 'VIP')
);
if (!isset($shops[$channel])) $channel = 'yb';
$shop = $shops[$channel];
$field = $shop['field'];
$returnUrl = $channel === 'yb' ? 'index.php' : 'shop.php?channel=' . $channel;
$categoryNames = array(1 => '热卖', 2 => '进化合成', 3 => '宠物相关', 4 => '装备相关');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST')
{
	$action = adminPost('action');
	if ($action === 'batch_take_down')
	{
		$selectedIds = adminSelectedIds(isset($_POST['selected_ids']) ? $_POST['selected_ids'] : array());
		if (count($selectedIds) === 0)
		{
			adminSetFlash('error', '请先选择要下架的商品。');
			adminRedirect($returnUrl);
		}
		$limited = array('Id' => 0, 'value2' => '', 'contents' => '');
		$limitedItems = array();
		$limitedChangedIds = array();
		$idList = implode(',', $selectedIds);
		if (!adminStartTransaction($adminDb))
		{
			adminSetFlash('error', '批量下架失败：无法开始数据库事务。');
			adminRedirect($returnUrl);
		}
		$batchRows = $adminDb->getRecords("SELECT id,yb,sj,vip,zhekouyb FROM props WHERE id IN ({$idList}) FOR UPDATE");
		$batchOk = is_array($batchRows);
		$lockedLimited = $adminDb->getOneRecord("SELECT Id,value2,contents FROM welcome WHERE code='timelimitbuy' LIMIT 1 FOR UPDATE");
		if (is_array($lockedLimited))
		{
			$limited = $lockedLimited;
			$limitedItems = adminParseLimitedItems($limited['contents']);
		}
		$changed = 0;
		if ($batchOk)
		{
			foreach ($batchRows as $batchRow)
			{
				$id = intval($batchRow['id']);
				if (intval($batchRow[$field]) < 1) continue;
				if (!$adminDb->query("UPDATE props SET {$field}=0 WHERE id={$id}"))
				{
					$batchOk = false;
					break;
				}
				$batchRow[$field] = 0;
				$keepShelf = adminSalePriceActive($batchRow['yb']) || adminSalePriceActive($batchRow['sj']) ||
					adminSalePriceActive($batchRow['vip']) || (adminSalePriceActive($batchRow['zhekouyb']) && isset($limitedItems[$id]));
				if (!$keepShelf)
				{
					if (isset($limitedItems[$id]))
					{
						unset($limitedItems[$id]);
						$limitedChangedIds[] = $id;
					}
					if (!$adminDb->query("UPDATE props SET yb=0,sj=0,vip=0,zhekouyb=0,stime=0,timelimit='' WHERE id={$id}"))
					{
						$batchOk = false;
						break;
					}
				}
				$changed++;
			}
		}
		if ($batchOk && count($limitedChangedIds) > 0)
		{
			$batchOk = adminSaveLimitedConfig($adminDb, $limited, $limited['value2'], $limitedItems) ? true : false;
		}
		if (!$batchOk || !$adminDb->query('COMMIT'))
		{
			$adminDb->query('ROLLBACK');
			adminSetFlash('error', '批量下架失败：' . $adminDb->getError());
			adminRedirect($returnUrl);
		}
		foreach ($limitedChangedIds as $limitedId) $adminMem->del('zhekou_' . intval($limitedId) . '_num');
		$cacheOk = adminRefreshPropsCache($adminDb, $adminMem);
		if (count($limitedChangedIds) > 0) $cacheOk = adminRefreshWelcomeCache($adminDb, $adminMem) && $cacheOk;
		adminSetFlash($cacheOk ? 'success' : 'warning', '已从' . $shop['title'] . '批量下架 ' . $changed . ' 件商品' . ($cacheOk ? '。' : '，但道具缓存刷新失败。'));
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
		$limited = array('Id' => 0, 'value2' => '', 'contents' => '');
		$limitedItems = array();
		if (!adminStartTransaction($adminDb))
		{
			adminSetFlash('error', '下架失败：无法开始数据库事务。');
			adminRedirect($returnUrl);
		}
		$lockedProp = $adminDb->getOneRecord("SELECT * FROM props WHERE id={$propId} FOR UPDATE");
		if (!is_array($lockedProp))
		{
			$adminDb->query('ROLLBACK');
			adminSetFlash('error', '下架失败：道具记录不存在。');
			adminRedirect($returnUrl);
		}
		$lockedLimited = $adminDb->getOneRecord("SELECT Id,value2,contents FROM welcome WHERE code='timelimitbuy' LIMIT 1 FOR UPDATE");
		if (is_array($lockedLimited))
		{
			$limited = $lockedLimited;
			$limitedItems = adminParseLimitedItems($limited['contents']);
		}
		$prop = $lockedProp;
		if (!$adminDb->query("UPDATE props SET {$field}=0 WHERE id={$propId}"))
		{
			$adminDb->query('ROLLBACK');
			adminSetFlash('error', '下架失败：' . $adminDb->getError());
			adminRedirect($returnUrl);
		}
		$prop[$field] = 0;
		$keepShelf = adminSalePriceActive($prop['yb']) || adminSalePriceActive($prop['sj']) ||
			adminSalePriceActive($prop['vip']) || (adminSalePriceActive($prop['zhekouyb']) && isset($limitedItems[$propId]));
		$limitedChanged = false;
		if (!$keepShelf)
		{
			if (isset($limitedItems[$propId]))
			{
				unset($limitedItems[$propId]);
				$limitedChanged = true;
			}
			if (!$adminDb->query("UPDATE props SET yb=0,sj=0,vip=0,zhekouyb=0,stime=0,timelimit='' WHERE id={$propId}") ||
				($limitedChanged && !adminSaveLimitedConfig($adminDb, $limited, $limited['value2'], $limitedItems)))
			{
				$adminDb->query('ROLLBACK');
				adminSetFlash('error', '下架失败：' . $adminDb->getError());
				adminRedirect($returnUrl);
			}
		}
		if (!$adminDb->query('COMMIT'))
		{
			$adminDb->query('ROLLBACK');
			adminSetFlash('error', '下架提交失败。');
			adminRedirect($returnUrl);
		}
		if ($limitedChanged) $adminMem->del('zhekou_' . $propId . '_num');
		$cacheOk = adminRefreshPropsCache($adminDb, $adminMem);
		if ($limitedChanged) $cacheOk = adminRefreshWelcomeCache($adminDb, $adminMem) && $cacheOk;
		adminSetFlash($cacheOk ? 'success' : 'warning', '已从' . $shop['title'] . '下架 id=' . $propId . ' ' . $prop['name'] . ($cacheOk ? '。' : '，但道具缓存刷新失败。'));
		adminRedirect($returnUrl);
	}

	if ($action === 'publish')
	{
		$price = intval(adminPost('price', 0));
		$category = intval(adminPost('category', 0));
		$sortSuffix = adminPost('sort_suffix');
		if ($price < 1 || $price >= 99999)
		{
			adminSetFlash('error', '售价必须在 1 至 99998 之间。');
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
		$start = adminNormalizeDate(adminPost('start_time'));
		$end = adminNormalizeDate(adminPost('end_time'));
		if ($start === false || $end === false || ($start !== '' && $end !== '' && $start > $end))
		{
			adminSetFlash('error', '上架时间范围无效。');
			adminRedirect($returnUrl);
		}
		$timelimit = $start === '' && $end === '' ? '' : $start . '|' . $end;
		$timeSql = $adminDb->escape($timelimit);
		$limited = array('Id' => 0, 'value2' => '', 'contents' => '');
		$limitedItems = array();
		if (!adminStartTransaction($adminDb))
		{
			adminSetFlash('error', '上架失败：无法开始数据库事务。');
			adminRedirect($returnUrl);
		}
		$lockedProp = $adminDb->getOneRecord("SELECT * FROM props WHERE id={$propId} FOR UPDATE");
		if (!is_array($lockedProp))
		{
			$adminDb->query('ROLLBACK');
			adminSetFlash('error', '上架失败：道具记录不存在。');
			adminRedirect($returnUrl);
		}
		$lockedLimited = $adminDb->getOneRecord("SELECT Id,value2,contents FROM welcome WHERE code='timelimitbuy' LIMIT 1 FOR UPDATE");
		if (is_array($lockedLimited))
		{
			$limited = $lockedLimited;
			$limitedItems = adminParseLimitedItems($limited['contents']);
		}
		$otherChannels = adminOtherActiveChannels($lockedProp, $channel, $limitedItems);
		if (count($otherChannels) > 0 &&
			(intval($lockedProp['stime']) !== $stime || adminStoredSchedule($lockedProp['timelimit']) !== $timelimit))
		{
			$adminDb->query('ROLLBACK');
			adminSetFlash('error', '该道具同时在' . implode('、', $otherChannels) . '上架，分类、排序和单品时间需保持一致。');
			adminRedirect($returnUrl);
		}
		$setParts = array("{$field}={$price}", "stime={$stime}", "timelimit='{$timeSql}'");
		$limitedRemoved = false;
		if (adminCategory($lockedProp['stime']) === 0)
		{
			foreach (array('yb', 'sj', 'vip') as $otherField)
			{
				if ($otherField !== $field && adminSalePriceActive($lockedProp[$otherField])) $setParts[] = $otherField . '=0';
			}
			if (isset($limitedItems[$propId]))
			{
				unset($limitedItems[$propId]);
				$setParts[] = 'zhekouyb=0';
				$limitedRemoved = true;
			}
		}
		$publishOk = $adminDb->query('UPDATE props SET ' . implode(',', $setParts) . " WHERE id={$propId}");
		if ($publishOk && $limitedRemoved) $publishOk = adminSaveLimitedConfig($adminDb, $limited, $limited['value2'], $limitedItems);
		if (!$publishOk || !$adminDb->query('COMMIT'))
		{
			$adminDb->query('ROLLBACK');
			adminSetFlash('error', '上架失败：' . $adminDb->getError());
			adminRedirect($returnUrl);
		}
		if ($limitedRemoved) $adminMem->del('zhekou_' . $propId . '_num');
		$cacheOk = adminRefreshPropsCache($adminDb, $adminMem);
		if ($limitedRemoved) $cacheOk = adminRefreshWelcomeCache($adminDb, $adminMem) && $cacheOk;
		adminSetFlash($cacheOk ? 'success' : 'warning', '已上架 id=' . $propId . ' ' . $prop['name'] . '，售价 ' . $price . ' ' . $shop['unit'] . ($cacheOk ? '。' : '，但道具缓存刷新失败。'));
		adminRedirect($returnUrl);
	}
}

$categoryFilter = intval(adminGet('category', 0));
if (!isset($categoryNames[$categoryFilter])) $categoryFilter = 0;
$scope = adminGet('scope') === 'configured' ? 'configured' : 'current';
$saleRows = $adminDb->getRecords("SELECT id,name,{$field} AS price,stime,timelimit,varyname,propslock FROM props WHERE stime>0 AND {$field}>0 AND {$field}<99999 ORDER BY stime,id");
if (!is_array($saleRows)) $saleRows = array();
$visibleRows = array();
foreach ($saleRows as $row)
{
	$category = adminCategory($row['stime']);
	if ($category === 0 || ($categoryFilter > 0 && $category !== $categoryFilter)) continue;
	$state = adminScheduleState($row['timelimit']);
	if ($scope === 'current' && $state !== 'active') continue;
	$row['_category'] = $category;
	$row['_state'] = $state;
	$visibleRows[] = $row;
}

$search = trim(adminGet('q'));
$searchRows = adminSearchProps($adminDb, $search);

adminPageStart($shop['title'], $channel);
?>
	<section class="band">
		<div class="section-head"><div><h2>当前在售</h2><div class="subtle"><?php echo count($visibleRows); ?> 件商品</div></div></div>
		<div class="filters" style="margin-bottom:12px">
			<div class="segmented">
			<?php $allCategories = array(0 => '全部分类') + $categoryNames; foreach ($allCategories as $key => $label) { $query = http_build_query(array('channel' => $channel, 'category' => $key, 'scope' => $scope)); ?>
				<a<?php echo $categoryFilter === $key ? ' class="active"' : ''; ?> href="<?php echo $channel === 'yb' ? 'index.php?' : 'shop.php?'; ?><?php echo adminH($query); ?>"><?php echo adminH($label); ?></a>
			<?php } ?>
			</div>
			<div class="segmented">
				<a<?php echo $scope === 'current' ? ' class="active"' : ''; ?> href="<?php echo adminH($returnUrl . (strpos($returnUrl, '?') === false ? '?' : '&') . http_build_query(array('category' => $categoryFilter, 'scope' => 'current'))); ?>">当前有效</a>
				<a<?php echo $scope === 'configured' ? ' class="active"' : ''; ?> href="<?php echo adminH($returnUrl . (strpos($returnUrl, '?') === false ? '?' : '&') . http_build_query(array('category' => $categoryFilter, 'scope' => 'configured'))); ?>">含定时商品</a>
			</div>
		</div>
		<?php if (count($visibleRows) === 0) { ?><div class="empty">暂无商品</div><?php } else { $batchGroup = 'shop-' . $channel; $batchForm = 'batch-' . $channel; ?>
		<form id="<?php echo adminH($batchForm); ?>" method="post" data-confirm="确认批量下架选中的商品？"><input type="hidden" name="action" value="batch_take_down" /><input type="hidden" name="channel" value="<?php echo adminH($channel); ?>" /></form>
		<div class="batch-bar"><label class="batch-check"><input type="checkbox" data-select-all="<?php echo adminH($batchGroup); ?>" />全选</label><button class="btn danger" type="submit" form="<?php echo adminH($batchForm); ?>" data-batch-submit="<?php echo adminH($batchGroup); ?>" disabled="disabled">批量下架</button></div>
		<div class="table-wrap"><table><thead><tr><th class="select-cell">选择</th><th>道具</th><th>分类</th><th>售价</th><th>上架编码</th><th>时间状态</th><th>操作</th></tr></thead><tbody>
		<?php foreach ($visibleRows as $row) { ?><tr>
			<td class="select-cell"><input type="checkbox" name="selected_ids[]" value="<?php echo intval($row['id']); ?>" form="<?php echo adminH($batchForm); ?>" data-select-item="<?php echo adminH($batchGroup); ?>" /></td>
			<td><?php adminPropLabel($row); ?></td>
			<td><span class="badge success"><?php echo adminH($categoryNames[$row['_category']]); ?></span></td>
			<td><span class="badge <?php echo adminH($channel); ?>"><?php echo intval($row['price']); ?> <?php echo adminH($shop['unit']); ?></span></td>
			<td class="code"><?php echo intval($row['stime']); ?></td>
			<td><?php adminScheduleBadge($row['_state']); ?></td>
			<td><div class="actions"><a class="btn secondary" href="<?php echo adminH($returnUrl . (strpos($returnUrl, '?') === false ? '?' : '&')); ?>q=<?php echo intval($row['id']); ?>#search-results">编辑</a><form method="post" data-confirm="确认从<?php echo adminH($shop['title']); ?>下架该商品？"><input type="hidden" name="action" value="take_down" /><input type="hidden" name="prop_id" value="<?php echo intval($row['id']); ?>" /><input type="hidden" name="channel" value="<?php echo adminH($channel); ?>" /><button class="btn danger" type="submit">下架</button></form></div></td>
		</tr><?php } ?>
		</tbody></table></div>
		<?php } ?>
	</section>

	<section class="band" id="search-results">
		<div class="section-head"><div><h2>搜索并上架</h2><?php if ($search !== '') { ?><div class="subtle">找到 <?php echo count($searchRows); ?> 条结果</div><?php } ?></div><form class="form-row" method="get" action="<?php echo $channel === 'yb' ? 'index.php' : 'shop.php'; ?>#search-results"><?php if ($channel !== 'yb') { ?><input type="hidden" name="channel" value="<?php echo adminH($channel); ?>" /><?php } ?><input class="input" style="width:360px" type="search" name="q" value="<?php echo adminH($search); ?>" placeholder="道具 ID 或名称" /><button class="btn primary" type="submit">搜索</button></form></div>
		<?php if ($search !== '' && count($searchRows) === 0) { ?><div class="empty">没有匹配的道具</div><?php } else if (count($searchRows) > 0) { ?>
		<div class="results">
		<?php foreach ($searchRows as $row) { $category = adminCategory($row['stime']); if ($category === 0) $category = 1; $parts = explode('|', (string)$row['timelimit']); ?>
			<div class="edit-row"><form class="edit-form" method="post">
				<input type="hidden" name="action" value="publish" /><input type="hidden" name="prop_id" value="<?php echo intval($row['id']); ?>" /><input type="hidden" name="channel" value="<?php echo adminH($channel); ?>" />
				<div><?php adminPropLabel($row); ?></div>
				<div class="field"><label>售价（<?php echo adminH($shop['unit']); ?>）</label><input class="input" type="number" min="1" max="99998" name="price" value="<?php echo intval($row[$field]) > 0 && intval($row[$field]) < 99999 ? intval($row[$field]) : 1; ?>" required="required" /></div>
				<div class="field"><label>分类</label><select class="select" name="category"><?php foreach ($categoryNames as $key => $label) { ?><option value="<?php echo $key; ?>"<?php echo $category === $key ? ' selected="selected"' : ''; ?>><?php echo adminH($label); ?></option><?php } ?></select></div>
				<div class="field"><label>分类内排序号</label><input class="input" name="sort_suffix" value="<?php echo adminH(adminSortSuffix($row['stime'])); ?>" placeholder="自动" /></div>
				<div class="datetime-pair"><div class="field"><label>单品开始（共享）</label><input class="input" type="datetime-local" name="start_time" value="<?php echo adminH(isset($parts[0]) ? adminCompactDateInput($parts[0]) : ''); ?>" /></div><div class="field"><label>单品结束（共享）</label><input class="input" type="datetime-local" name="end_time" value="<?php echo adminH(isset($parts[1]) ? adminCompactDateInput($parts[1]) : ''); ?>" /></div></div>
				<button class="btn primary" type="submit"><?php echo intval($row['stime']) > 0 && intval($row[$field]) > 0 ? '更新' : '上架'; ?></button>
			</form></div>
		<?php } ?>
		</div>
		<?php } ?>
	</section>
<?php adminPageEnd(); ?>
