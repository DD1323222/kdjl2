<?php
require_once(dirname(__FILE__) . '/_bootstrap.php');
require_once(dirname(__FILE__) . '/_catalog_helpers.php');
require_once(dirname(__FILE__) . '/_layout.php');

function adminItemDefaults()
{
	return array(
		'id' => 0, 'name' => '', 'requires' => '', 'usages' => '', 'effect' => '0', 'sell' => 0,
		'prestige' => 0, 'buy' => 0, 'yb' => 0, 'sj' => 0, 'stime' => 0, 'endtime' => 0,
		'img' => '', 'vary' => 1, 'varyname' => 1, 'postion' => 0, 'pluseffect' => '',
		'plusflag' => 0, 'pluspid' => 0, 'plusget' => '', 'plusnum' => 0, 'propscolor' => '1',
		'propslock' => 0, 'series' => '', 'serieseffect' => '', 'expire' => null, 'note' => '',
		'timelimit' => '', 'merge' => 0, 'vip' => null, 'honor' => null, 'contribution' => null,
		'guild_level' => null, 'zhekouyb' => 0
	);
}

function adminItemCollectForm(&$errors)
{
	$row = adminItemDefaults();
	$row['id'] = adminCatalogInteger('id', 0, 2147483647, false, $errors);
	$row['name'] = adminCatalogText('name', 100, true, $errors);
	foreach (array('requires' => 100, 'usages' => 255, 'effect' => 1000, 'img' => 50,
		'pluseffect' => 100, 'plusget' => 255, 'propscolor' => 7, 'series' => 255,
		'serieseffect' => 255, 'timelimit' => 25) as $field => $max)
		$row[$field] = adminCatalogText($field, $max, false, $errors);
	$row['note'] = adminCatalogText('note', 65535, false, $errors, false);
	foreach (array('sell', 'buy', 'yb', 'sj', 'stime', 'endtime', 'pluspid', 'zhekouyb') as $field)
		$row[$field] = adminCatalogInteger($field, 0, 2147483647, false, $errors);
	$row['prestige'] = adminCatalogInteger('prestige', 0, 65535, false, $errors);
	$row['vary'] = adminCatalogInteger('vary', 1, 2, false, $errors);
	$row['varyname'] = adminCatalogInteger('varyname', 0, 255, false, $errors);
	$row['postion'] = adminCatalogInteger('postion', 0, 255, false, $errors);
	$row['plusflag'] = adminCatalogInteger('plusflag', 0, 1, false, $errors);
	$row['plusnum'] = adminCatalogInteger('plusnum', 0, 255, false, $errors);
	$row['propslock'] = adminCatalogInteger('propslock', 0, 1, false, $errors);
	$row['merge'] = adminCatalogInteger('merge', 0, 255, false, $errors);
	$row['expire'] = adminCatalogInteger('expire', 0, 2147483647, true, $errors);
	$row['vip'] = adminCatalogInteger('vip', 0, 2147483647, true, $errors);
	$row['honor'] = adminCatalogInteger('honor', 0, 2147483647, true, $errors);
	$row['contribution'] = adminCatalogInteger('contribution', 0, 2147483647, true, $errors);
	$row['guild_level'] = adminCatalogInteger('guild_level', 0, 127, true, $errors);
	if (!preg_match('/^[1-6]$/D', $row['propscolor'])) $errors[] = '道具品质颜色必须是 1 到 6。';
	if ($row['img'] !== '' && (basename($row['img']) !== $row['img'] || !preg_match('/^[A-Za-z0-9_.-]+$/D', $row['img'])))
		$errors[] = '图片文件名只能包含英文字母、数字、点、横线和下划线，不能包含目录。';
	return $row;
}

function adminItemAssignments($db, $row)
{
	$textFields = array('name', 'requires', 'usages', 'effect', 'img', 'pluseffect', 'plusget', 'propscolor', 'series', 'serieseffect', 'note', 'timelimit');
	$numberFields = array('sell', 'prestige', 'buy', 'yb', 'sj', 'stime', 'endtime', 'vary', 'varyname', 'postion', 'plusflag', 'pluspid', 'plusnum', 'propslock', 'merge', 'zhekouyb');
	$nullableNumberFields = array('expire', 'vip', 'honor', 'contribution', 'guild_level');
	$assignments = array();
	foreach ($textFields as $field) $assignments[] = '`' . $field . '`=' . adminCatalogSqlText($db, $row[$field]);
	foreach ($numberFields as $field) $assignments[] = '`' . $field . '`=' . intval($row[$field]);
	foreach ($nullableNumberFields as $field) $assignments[] = '`' . $field . '`=' . adminCatalogSqlNumber($row[$field]);
	return implode(',', $assignments);
}

function adminItemInput($row, $field)
{
	return isset($row[$field]) && !is_array($row[$field]) ? $row[$field] : '';
}

function adminItemImagePreview($fileName)
{
	$fileName = trim((string)$fileName);
	if ($fileName === '' || basename($fileName) !== $fileName || !preg_match('/^[A-Za-z0-9_.-]+$/D', $fileName)) return false;
	foreach (array('props', 'card_Mod', 'tarot') as $directory)
	{
		$path = dirname(__FILE__) . '/../images/' . $directory . '/' . $fileName;
		if (is_file($path)) return array('directory' => $directory, 'path' => $path, 'url' => '../images/' . $directory . '/' . rawurlencode($fileName));
	}
	return false;
}

$errors = array();
$editor = false;
$isNew = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && adminPost('action') === 'save_item')
{
	if (!adminCatalogVerifyCsrf()) $errors[] = '页面校验已失效，请刷新后重试。';
	$editor = adminItemCollectForm($errors);
	$itemId = intval($editor['id']);
	$isNew = $itemId < 1;
	if (!$isNew)
	{
		$oldRow = $adminDb->getOneRecord('SELECT id FROM props WHERE id=' . $itemId . ' LIMIT 1');
		if (!is_array($oldRow)) $errors[] = '要修改的道具不存在。';
	}
	if (count($errors) === 0)
	{
		$transaction = adminStartTransaction($adminDb);
		if (!$transaction) $errors[] = '无法开启数据库事务。';
		if ($transaction && !$isNew)
		{
			$locked = $adminDb->getOneRecord('SELECT id FROM props WHERE id=' . $itemId . ' FOR UPDATE');
			if (!is_array($locked)) $errors[] = '道具记录已不存在。';
		}
		if ($transaction && count($errors) === 0 && $isNew)
		{
			if (!$adminDb->query('INSERT INTO props(name,yb,stime) VALUES(NULL,0,0)')) $errors[] = '无法创建道具记录。';
			else
			{
				$itemId = intval($adminDb->last_id());
				$editor['id'] = $itemId;
				if ($itemId < 1) $errors[] = '无法取得新道具 id。';
			}
		}
		if ($transaction && count($errors) === 0 &&
			!$adminDb->query('UPDATE props SET ' . adminItemAssignments($adminDb, $editor) . ' WHERE id=' . $itemId . ' LIMIT 1'))
			$errors[] = '道具数据保存失败。';
		if ($transaction && count($errors) === 0 && !$adminDb->query('COMMIT')) $errors[] = '道具数据提交失败。';
		if (count($errors) > 0)
		{
			if ($transaction) $adminDb->query('ROLLBACK');
		}
		else
		{
			$cacheOk = adminRefreshPropsCache($adminDb, $adminMem);
			adminSetFlash($cacheOk ? 'success' : 'warning', $cacheOk ? '道具已保存，数据库与缓存均已更新。' : '道具已保存，但缓存刷新失败；请访问 vm1.php 刷新 props。');
			adminRedirect('items.php?edit=' . $itemId);
		}
	}
}

if ($editor === false)
{
	$editId = intval(adminGet('edit'));
	$copyId = intval(adminGet('copy'));
	if ($editId > 0) $editor = $adminDb->getOneRecord('SELECT * FROM props WHERE id=' . $editId . ' LIMIT 1');
	else if ($copyId > 0)
	{
		$editor = $adminDb->getOneRecord('SELECT * FROM props WHERE id=' . $copyId . ' LIMIT 1');
		if (is_array($editor))
		{
			$editor['id'] = 0;
			$editor['name'] .= '（副本）';
			$editor['buy'] = 0;
			$editor['prestige'] = 0;
			$editor['yb'] = 0;
			$editor['sj'] = 0;
			$editor['vip'] = null;
			$editor['honor'] = null;
			$editor['contribution'] = null;
			$editor['guild_level'] = null;
			$editor['zhekouyb'] = 0;
			$editor['stime'] = 0;
			$editor['timelimit'] = '';
			$isNew = true;
		}
	}
	else if (adminGet('new') === '1')
	{
		$editor = adminItemDefaults();
		$isNew = true;
	}
}
if ($editor !== false && !is_array($editor))
{
	adminSetFlash('error', '道具不存在。');
	adminRedirect('items.php');
}

$q = trim((string)adminGet('q'));
$page = max(1, intval(adminGet('page', 1)));
$pageSize = 100;
$where = '1=1';
if ($q !== '')
{
	$escaped = $adminDb->escape($q);
	$where = "name LIKE '%{$escaped}%'";
	if (preg_match('/^[0-9]+$/D', $q)) $where = '(id=' . intval($q) . " OR {$where})";
}
$countRow = $adminDb->getOneRecord('SELECT COUNT(*) AS total FROM props WHERE ' . $where);
$total = is_array($countRow) ? intval($countRow['total']) : 0;
$maxPage = max(1, intval(ceil($total / $pageSize)));
if ($page > $maxPage) $page = $maxPage;
$offset = ($page - 1) * $pageSize;
$rows = $adminDb->getRecords('SELECT id,name,vary,varyname,img,effect,buy,yb,sj,vip,stime,zhekouyb,propslock FROM props WHERE ' . $where . ' ORDER BY id LIMIT ' . $offset . ',' . $pageSize);
if (!is_array($rows)) $rows = array();

$varyLabels = isset($_props['vary']) && is_array($_props['vary']) ? $_props['vary'] : array(1 => '可叠加', 2 => '不可叠加');
$typeLabels = isset($_props['varyname']) && is_array($_props['varyname']) ? $_props['varyname'] : array();
$positionLabels = isset($_props['postion']) && is_array($_props['postion']) ? $_props['postion'] : array();
$colorLabels = array(1 => '白', 2 => '蓝', 3 => '紫', 4 => '绿', 5 => '黄', 6 => '橙');
adminPageStart('道具修改管理', 'items');
?>
	<?php if (count($errors) > 0) { ?><div class="flash error"><?php foreach ($errors as $error) { ?><div><?php echo adminH($error); ?></div><?php } ?></div><?php } ?>
	<section class="band">
		<div class="section-head"><div><h2>道具定义</h2><div class="subtle">完整维护 props 字段；效果协议按原字符串保存，不会丢失未展示的片段。</div></div><a class="btn primary" href="items.php?new=1">新增道具</a></div>
		<form class="filters" method="get" action="items.php">
			<input class="input catalog-search" type="text" name="q" value="<?php echo adminH($q); ?>" placeholder="输入 id 或道具名" />
			<button class="btn secondary" type="submit">查询</button>
			<?php if ($q !== '') { ?><a class="btn secondary" href="items.php">清除</a><?php } ?>
			<span class="subtle">共 <?php echo $total; ?> 条，第 <?php echo $page; ?>/<?php echo $maxPage; ?> 页</span>
		</form>
	</section>

	<?php if (is_array($editor)) { $isNew = intval($editor['id']) < 1; $preview = adminItemImagePreview(adminItemInput($editor, 'img')); ?>
	<section class="band catalog-editor">
		<div class="section-head"><div><h2><?php echo $isNew ? '新增道具' : '修改道具 id=' . intval($editor['id']); ?></h2><div class="subtle">新增和复制新增默认不上架；商店价格与 stime 可继续在商店管理中维护。</div></div><a class="btn secondary" href="items.php<?php echo $q === '' ? '' : '?q=' . urlencode($q); ?>">关闭编辑</a></div>
		<form method="post" action="items.php">
			<input type="hidden" name="action" value="save_item" />
			<input type="hidden" name="csrf_token" value="<?php echo adminH(adminCatalogCsrfToken()); ?>" />
			<input type="hidden" name="id" value="<?php echo intval($editor['id']); ?>" />

			<h3 class="catalog-heading">基本信息</h3>
			<div class="catalog-grid four">
				<div class="field span-2"><label>道具名称</label><input class="input" name="name" maxlength="100" required value="<?php echo adminH(adminItemInput($editor, 'name')); ?>" /></div>
				<div class="field"><label>叠加类型</label><select class="select" name="vary"><?php foreach ($varyLabels as $id => $label) { ?><option value="<?php echo intval($id); ?>"<?php echo intval(adminItemInput($editor, 'vary')) === intval($id) ? ' selected="selected"' : ''; ?>><?php echo adminH($label); ?> (<?php echo intval($id); ?>)</option><?php } ?></select></div>
				<div class="field"><label>道具分类 varyname</label><select class="select" name="varyname"><?php $currentType = intval(adminItemInput($editor, 'varyname')); if (!isset($typeLabels[$currentType])) { ?><option value="<?php echo $currentType; ?>" selected="selected">未知分类 <?php echo $currentType; ?></option><?php } foreach ($typeLabels as $id => $label) { ?><option value="<?php echo intval($id); ?>"<?php echo $currentType === intval($id) ? ' selected="selected"' : ''; ?>><?php echo adminH($label); ?> (<?php echo intval($id); ?>)</option><?php } ?></select></div>
				<div class="field"><label>装备位置 postion</label><select class="select" name="postion"><?php $currentPosition = intval(adminItemInput($editor, 'postion')); if (!isset($positionLabels[$currentPosition])) { ?><option value="<?php echo $currentPosition; ?>" selected="selected">未知位置 <?php echo $currentPosition; ?></option><?php } foreach ($positionLabels as $id => $label) { ?><option value="<?php echo intval($id); ?>"<?php echo $currentPosition === intval($id) ? ' selected="selected"' : ''; ?>><?php echo adminH($label); ?> (<?php echo intval($id); ?>)</option><?php } ?></select></div>
				<div class="field"><label>品质颜色 propscolor</label><select class="select" name="propscolor"><?php foreach ($colorLabels as $id => $label) { ?><option value="<?php echo $id; ?>"<?php echo intval(adminItemInput($editor, 'propscolor')) === $id ? ' selected="selected"' : ''; ?>><?php echo adminH($label); ?> (<?php echo $id; ?>)</option><?php } ?></select></div>
				<div class="field"><label>默认是否可交易</label><select class="select" name="propslock"><option value="0"<?php echo intval(adminItemInput($editor, 'propslock')) === 0 ? ' selected="selected"' : ''; ?>>不可交易 (0)</option><option value="1"<?php echo intval(adminItemInput($editor, 'propslock')) === 1 ? ' selected="selected"' : ''; ?>>可交易 (1)</option></select></div>
				<div class="field catalog-image-field"><label>图片文件名 img</label><div class="catalog-image-input"><?php if (is_array($preview)) { ?><img src="<?php echo adminH($preview['url']); ?>?v=<?php echo intval(@filemtime($preview['path'])); ?>" alt="" /><?php } ?><input class="input code-input" name="img" maxlength="50" value="<?php echo adminH(adminItemInput($editor, 'img')); ?>" /></div><span class="subtle"><?php echo is_array($preview) ? '当前来自 images/' . adminH($preview['directory']) : '不同分类会从 props、card_Mod 或 tarot 等目录读取'; ?></span></div>
				<div class="field span-2"><label>使用说明 usages</label><textarea class="textarea" name="usages" maxlength="255" rows="2"><?php echo adminH(adminItemInput($editor, 'usages')); ?></textarea></div>
				<div class="field span-2"><label>使用要求 requires</label><input class="input code-input" name="requires" maxlength="100" value="<?php echo adminH(adminItemInput($editor, 'requires')); ?>" /></div>
			</div>

			<h3 class="catalog-heading">使用效果与装备属性</h3>
			<div class="protocol-notice">`effect` 的语法由道具分类和调用模块决定，例如回复、开蛋、地图、装备、礼包、进化道具的协议均不同。请以同类型现有道具为模板；本页面只校验长度并完整保存原协议。</div>
			<div class="catalog-grid two">
				<div class="field"><label>使用效果 effect</label><textarea class="textarea code-input" name="effect" maxlength="1000" rows="4"><?php echo adminH(adminItemInput($editor, 'effect')); ?></textarea></div>
				<div class="field"><label>附加属性 pluseffect</label><textarea class="textarea code-input" name="pluseffect" maxlength="100" rows="4"><?php echo adminH(adminItemInput($editor, 'pluseffect')); ?></textarea></div>
				<div class="field"><label>强化等级效果 plusget</label><textarea class="textarea code-input" name="plusget" maxlength="255" rows="3"><?php echo adminH(adminItemInput($editor, 'plusget')); ?></textarea></div>
				<div class="field"><label>套装效果 serieseffect</label><textarea class="textarea code-input" name="serieseffect" maxlength="255" rows="3"><?php echo adminH(adminItemInput($editor, 'serieseffect')); ?></textarea></div>
			</div>
			<div class="catalog-grid five compact-grid">
				<div class="field"><label>可强化 plusflag</label><select class="select" name="plusflag"><option value="0"<?php echo intval(adminItemInput($editor, 'plusflag')) === 0 ? ' selected="selected"' : ''; ?>>否</option><option value="1"<?php echo intval(adminItemInput($editor, 'plusflag')) === 1 ? ' selected="selected"' : ''; ?>>是</option></select></div>
				<div class="field"><label>强化材料 id</label><input class="input" type="number" min="0" name="pluspid" value="<?php echo intval(adminItemInput($editor, 'pluspid')); ?>" /></div>
				<div class="field"><label>镶嵌孔 plusnum</label><input class="input" type="number" min="0" max="255" name="plusnum" value="<?php echo intval(adminItemInput($editor, 'plusnum')); ?>" /></div>
				<div class="field"><label>合成标记 merge</label><input class="input" type="number" min="0" max="255" name="merge" value="<?php echo intval(adminItemInput($editor, 'merge')); ?>" /></div>
				<div class="field"><label>套装 series</label><input class="input code-input" name="series" maxlength="255" value="<?php echo adminH(adminItemInput($editor, 'series')); ?>" /></div>
			</div>

			<h3 class="catalog-heading">价格、商店与时效</h3>
			<div class="catalog-grid five compact-grid">
				<?php foreach (array('sell' => '卖出金币', 'buy' => '购买金币', 'prestige' => '威望价', 'yb' => '元宝价', 'sj' => '水晶价', 'vip' => 'VIP 点价', 'honor' => '荣誉价', 'contribution' => '贡献价', 'guild_level' => '家族等级', 'zhekouyb' => '抢购元宝价') as $field => $label) { ?>
				<div class="field"><label><?php echo adminH($label); ?></label><input class="input" type="number" min="0" name="<?php echo $field; ?>" value="<?php echo adminH(adminItemInput($editor, $field)); ?>" /></div>
				<?php } ?>
				<div class="field"><label>商店排序编码 stime</label><input class="input" type="number" min="0" name="stime" value="<?php echo intval(adminItemInput($editor, 'stime')); ?>" /></div>
				<div class="field"><label>旧结束值 endtime</label><input class="input" type="number" min="0" name="endtime" value="<?php echo intval(adminItemInput($editor, 'endtime')); ?>" /></div>
				<div class="field"><label>单品时间 timelimit</label><input class="input code-input" name="timelimit" maxlength="25" value="<?php echo adminH(adminItemInput($editor, 'timelimit')); ?>" /></div>
				<div class="field"><label>获得后有效秒数 expire</label><input class="input" type="number" min="0" name="expire" value="<?php echo adminH(adminItemInput($editor, 'expire')); ?>" /></div>
			</div>
			<div class="subtle catalog-help">`stime=0` 表示不在四个神秘商店渠道上架；直接改变价格或排序编码会影响商店状态，日常上下架建议仍使用对应商店管理页面。</div>

			<h3 class="catalog-heading">详情内容</h3>
			<div class="field"><label>道具详情 note（保留原 HTML）</label><textarea class="textarea" name="note" rows="8"><?php echo adminH(adminItemInput($editor, 'note')); ?></textarea></div>
			<div class="catalog-actions"><button class="btn primary" type="submit">保存道具</button><a class="btn secondary" href="items.php">取消</a></div>
		</form>
	</section>
	<?php } ?>

	<section class="band">
		<div class="table-wrap"><table class="catalog-table item-table"><thead><tr><th>道具</th><th>分类</th><th>叠加</th><th>是否可交易</th><th>使用效果</th><th>价格摘要</th><th>商店编码</th><th>操作</th></tr></thead><tbody>
		<?php foreach ($rows as $row) { $preview = adminItemImagePreview($row['img']); ?>
		<tr>
			<td><div class="catalog-entity"><?php if (is_array($preview)) { ?><img src="<?php echo adminH($preview['url']); ?>" alt="" /><?php } ?><div><strong><?php echo adminH($row['name']); ?></strong><span>id=<?php echo intval($row['id']); ?></span></div></div></td>
			<td><?php echo isset($typeLabels[intval($row['varyname'])]) ? adminH($typeLabels[intval($row['varyname'])]) : '类型 ' . intval($row['varyname']); ?></td>
			<td><?php echo isset($varyLabels[intval($row['vary'])]) ? adminH($varyLabels[intval($row['vary'])]) : intval($row['vary']); ?></td>
			<td><?php adminPropTradeBadge($row); ?></td>
			<td class="code catalog-ellipsis"><?php echo adminH($row['effect']); ?></td>
			<td class="code">金币 <?php echo intval($row['buy']); ?> / 元宝 <?php echo intval($row['yb']); ?> / 水晶 <?php echo intval($row['sj']); ?><br />VIP <?php echo intval($row['vip']); ?> / 抢购 <?php echo intval($row['zhekouyb']); ?></td>
			<td class="code"><?php echo intval($row['stime']); ?></td>
			<td><div class="actions"><a class="btn secondary" href="items.php?edit=<?php echo intval($row['id']); ?>">编辑</a><a class="btn secondary" href="items.php?copy=<?php echo intval($row['id']); ?>">复制新增</a></div></td>
		</tr><?php } ?>
		</tbody></table></div>
		<?php if (count($rows) === 0) { ?><div class="empty">没有匹配的道具。</div><?php } ?>
		<?php if ($maxPage > 1) { ?><div class="catalog-pager"><?php if ($page > 1) { ?><a class="btn secondary" href="items.php?q=<?php echo urlencode($q); ?>&amp;page=<?php echo $page - 1; ?>">上一页</a><?php } ?><?php if ($page < $maxPage) { ?><a class="btn secondary" href="items.php?q=<?php echo urlencode($q); ?>&amp;page=<?php echo $page + 1; ?>">下一页</a><?php } ?></div><?php } ?>
	</section>
<?php adminPageEnd(); ?>
