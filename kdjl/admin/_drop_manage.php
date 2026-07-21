<?php
require_once(dirname(__FILE__) . '/_bootstrap.php');
require_once(dirname(__FILE__) . '/_layout.php');
require_once(dirname(__FILE__) . '/_drop_helpers.php');

$dropTitle = $adminDropConfig['title'];
$dropActive = $adminDropConfig['active'];
$dropField = $adminDropConfig['field'];
$dropFieldLabel = $adminDropConfig['field_label'];
$dropPage = $adminDropConfig['page'];
$dropStateKey = 'admin_drop_state_' . $dropField;
$catalogGroups = adminDropCatalog($adminDb, $fbinfo);
$catalog = $catalogGroups['all'];

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST')
{
	$action = adminPost('action');
	$scopeIds = adminDropSelectedScopes(isset($_POST['scope_ids']) ? $_POST['scope_ids'] : array(), $catalog);
	$gpcIds = adminDropSelectedMonsterIds(
		isset($_POST['selected_gpc_ids']) ? $_POST['selected_gpc_ids'] : array(),
		isset($_POST['selected_gpc_csv']) ? $_POST['selected_gpc_csv'] : ''
	);
	$search = trim(adminPost('q'));
	$propId = intval(adminPost('prop_id', 0));
	$denominator = intval(adminPost('denominator', 0));
	$_SESSION[$dropStateKey] = array('scopes' => $scopeIds, 'gpcs' => $gpcIds, 'q' => $search, 'denominator' => $denominator);
	if ($action === 'search_props') adminRedirect($dropPage . '?restore=1');
	if ($action !== 'add_drop' && $action !== 'delete_drop')
	{
		adminSetFlash('error', '操作参数未提交，请刷新页面后重试。');
		adminRedirect($dropPage . '?restore=1');
	}
	if (count($gpcIds) === 0)
	{
		adminSetFlash('error', '没有收到怪物选择，请重新勾选怪物。');
		adminRedirect($dropPage . '?restore=1');
	}
	$prop = $propId > 0 ? $adminDb->getOneRecord("SELECT id,name FROM props WHERE id={$propId} LIMIT 1") : false;
	if (!is_array($prop))
	{
		adminSetFlash('error', '请选择有效的掉落道具。');
		adminRedirect($dropPage . '?restore=1');
	}
	if ($action === 'add_drop' && ($denominator < 1 || $denominator > 1000000000))
	{
		adminSetFlash('error', '掉落分母必须在 1 至 1000000000 之间。');
		adminRedirect($dropPage . '?restore=1');
	}
	$result = adminDropUpdate($adminDb, $gpcIds, $propId, $denominator, $action === 'delete_drop', $dropField);
	if (!$result[0])
	{
		adminSetFlash('error', '掉落配置保存失败：' . $result[2]);
		adminRedirect($dropPage . '?restore=1');
	}
	$changedIds = $result[1];
	$cacheOk = count($changedIds) === 0 ? true : adminRefreshGpcCache($adminDb, $adminMem, $changedIds);
	$verb = $action === 'delete_drop' ? '删除' : '添加';
	if (count($changedIds) === 0)
	{
		$message = $action === 'delete_drop' ? '所选怪物均未配置该道具的' . $dropFieldLabel . '。' : '所选怪物均已配置该道具的' . $dropFieldLabel . '，没有重复添加。';
	}
	else
	{
		$message = '已为 ' . count($changedIds) . ' 只怪物' . $verb . $dropFieldLabel . '：id=' . $propId . ' ' . $prop['name'] . '。';
	}
	if (!$cacheOk) $message .= ' 数据库已保存，但怪物缓存刷新失败。';
	adminSetFlash($cacheOk ? 'success' : 'warning', $message);
	adminRedirect($dropPage . '?restore=1');
}

$restored = isset($_GET['restore']) && isset($_SESSION[$dropStateKey]) && is_array($_SESSION[$dropStateKey]);
if ($restored)
{
	$state = $_SESSION[$dropStateKey];
	unset($_SESSION[$dropStateKey]);
	$scopeInput = isset($state['scopes']) ? $state['scopes'] : array();
	$selectedGpcInput = isset($state['gpcs']) ? $state['gpcs'] : array();
	$search = isset($state['q']) ? $state['q'] : '';
	$denominatorInput = isset($state['denominator']) && intval($state['denominator']) > 0 ? intval($state['denominator']) : 100;
}
else
{
	$scopeInput = isset($_GET['scope_ids']) ? $_GET['scope_ids'] : array();
	$selectedGpcInput = adminDropSelectedMonsterIds(
		isset($_GET['selected_gpc_ids']) ? $_GET['selected_gpc_ids'] : array(),
		isset($_GET['selected_gpc_csv']) ? $_GET['selected_gpc_csv'] : ''
	);
	$search = trim(adminGet('q'));
	$denominatorInput = intval(adminGet('denominator', 0)) > 0 ? intval(adminGet('denominator', 0)) : 100;
}
$selectedScopes = adminDropSelectedScopes($scopeInput, $catalog);
$queried = $restored || isset($_GET['query']) || isset($_GET['search_props']);
$propSearched = $restored ? $search !== '' : isset($_GET['search_props']);
$monsters = $queried ? adminDropResolveMonsters($adminDb, $selectedScopes, $catalog) : array();
$monsterMap = array();
foreach ($monsters as $monster) $monsterMap[intval($monster['id'])] = true;
$selectedGpcIds = array();
foreach (adminSelectedIds($selectedGpcInput) as $id) if (isset($monsterMap[$id])) $selectedGpcIds[$id] = $id;
$props = adminPropsMap($adminDb);
$searchRows = $propSearched ? adminDropSearchProps($adminDb, $search) : array();
$pageError = $queried && count($selectedScopes) === 0 ? '请至少选择一个地图或副本。' : '';

adminPageStart($dropTitle, $dropActive);
?>
	<form id="drop-admin-form" method="post" action="<?php echo adminH($dropPage); ?>" data-drop-form>
	<input type="hidden" name="selected_gpc_csv" value="<?php echo adminH(implode(',', array_values($selectedGpcIds))); ?>" data-selected-gpc-csv />
	<section class="band">
		<div class="section-head"><div><h2>地图与副本</h2><div class="subtle">选择需要查询的区域</div></div><button class="btn primary" type="submit" name="query" value="1" formmethod="get" data-scope-query>查询怪物</button></div>
		<details class="scope-picker"<?php echo count($selectedScopes) === 0 ? ' open="open"' : ''; ?>>
			<summary><span>选择地图与副本</span><strong>已选择 <b data-scope-selected-count><?php echo count($selectedScopes); ?></b> 个</strong></summary>
			<div class="scope-dropdown">
				<input class="input" type="search" placeholder="地图、副本 id 或名称" data-scope-filter />
				<div class="scope-select-actions"><label class="batch-check"><input type="checkbox" data-select-all="drop-maps" />全选地图</label><label class="batch-check"><input type="checkbox" data-select-all="drop-dungeons" />全选副本</label></div>
				<div class="scope-section">
					<div class="scope-heading"><strong>地图</strong></div>
					<div class="scope-grid">
					<?php foreach ($catalogGroups['maps'] as $scope) { $checked = in_array($scope['key'], $selectedScopes); ?>
						<label class="scope-option" data-scope-option data-scope-search="<?php echo adminH($scope['name'] . ' ' . $scope['id'] . ' 地图'); ?>"><input type="checkbox" name="scope_ids[]" value="<?php echo adminH($scope['key']); ?>" data-select-item="drop-maps" data-scope-item<?php echo $checked ? ' checked="checked"' : ''; ?> /><span><strong><?php echo adminH($scope['name']); ?></strong><small>id=<?php echo intval($scope['id']); ?><?php if (intval($scope['multi_monsters']) === 1) { ?> · 挑战地图<?php } else if (intval($scope['multi_monsters']) === 2) { ?> · 通关塔<?php } else if (intval($scope['multi_monsters']) === 3) { ?> · 组队副本<?php } else if (intval($scope['multi_monsters']) === 4) { ?> · 神宠地图<?php } else { ?> · 等级 <?php echo adminH($scope['level']); ?><?php } ?></small></span></label>
					<?php } ?>
					</div>
				</div>
				<div class="scope-section">
					<div class="scope-heading"><strong>副本</strong></div>
					<div class="scope-grid">
					<?php foreach ($catalogGroups['dungeons'] as $scope) { $checked = in_array($scope['key'], $selectedScopes); ?>
						<label class="scope-option" data-scope-option data-scope-search="<?php echo adminH($scope['name'] . ' ' . $scope['id'] . ' 副本'); ?>"><input type="checkbox" name="scope_ids[]" value="<?php echo adminH($scope['key']); ?>" data-select-item="drop-dungeons" data-scope-item<?php echo $checked ? ' checked="checked"' : ''; ?> /><span><strong><?php echo adminH($scope['name']); ?></strong><small>id=<?php echo intval($scope['id']); ?> · <?php echo count($scope['monster_ids']); ?> 只怪物</small></span></label>
					<?php } ?>
					</div>
				</div>
			</div>
		</details>
	</section>

	<?php if ($queried && count($monsters) > 0) { ?>
	<section class="band" id="drop-actions">
		<div class="section-head"><div><h2>添加或删除<?php echo adminH($dropFieldLabel); ?></h2><div class="subtle">已选择 <strong data-selected-count="drop-monsters"><?php echo count($selectedGpcIds); ?></strong> 只怪物<?php if ($propSearched) { ?> · 找到 <?php echo count($searchRows); ?> 件道具<?php } ?></div></div>
			<div class="form-row"><input class="input drop-search" type="search" name="q" value="<?php echo adminH($search); ?>" placeholder="道具 id 或名称" /><button class="btn secondary" type="submit" name="action" value="search_props">搜索</button></div>
		</div>
		<?php if ($propSearched && count($searchRows) === 0) { ?><div class="empty">没有匹配的道具</div>
		<?php } else if (count($searchRows) > 0) { ?>
		<div class="table-wrap prop-picker"><table><thead><tr><th class="select-cell">选择</th><th>道具</th><th>类型</th></tr></thead><tbody>
		<?php foreach ($searchRows as $row) { ?><tr><td class="select-cell"><input type="radio" name="prop_id" value="<?php echo intval($row['id']); ?>" data-drop-prop /></td><td><?php adminPropLabel($row); ?></td><td><?php echo adminH(adminPropTypeName($row)); ?></td></tr><?php } ?>
		</tbody></table></div>
		<div class="drop-action-bar"><div class="field"><label>掉落分母（1/N）</label><input class="input" type="number" name="denominator" min="1" max="1000000000" value="<?php echo intval($denominatorInput); ?>" /></div><button class="btn primary" type="submit" name="action" value="add_drop" data-drop-action>添加掉落</button><button class="btn danger" type="submit" name="action" value="delete_drop" data-drop-action data-confirm-action="确认从所选怪物删除该道具的全部<?php echo adminH($dropFieldLabel); ?>配置？">删除掉落</button></div>
		<?php } ?>
	</section>
	<?php } ?>

	<?php if ($queried) { ?>
	<section class="band">
		<div class="section-head"><div><h2>怪物与<?php echo adminH($dropFieldLabel); ?></h2><div class="subtle"><?php echo count($monsters); ?> 只怪物</div></div></div>
		<?php if ($pageError !== '') { ?><div class="empty error-text"><?php echo adminH($pageError); ?></div>
		<?php } else if (count($monsters) === 0) { ?><div class="empty">所选区域没有可管理的怪物</div>
		<?php } else { ?>
		<div class="batch-bar"><label class="batch-check"><input type="checkbox" data-select-all="drop-monsters" />全选怪物</label><span class="subtle">已选择 <strong data-selected-count="drop-monsters"><?php echo count($selectedGpcIds); ?></strong> 只</span></div>
		<div class="table-wrap"><table class="drop-table single"><thead><tr><th class="select-cell">选择</th><th>怪物</th><th>出现区域</th><th>等级</th><th><?php echo adminH($dropFieldLabel); ?></th></tr></thead><tbody>
		<?php foreach ($monsters as $monster) { $gpcId = intval($monster['id']); $groups = adminDropDisplayGroups($monster[$dropField]); ?>
			<tr><td class="select-cell"><input type="checkbox" name="selected_gpc_ids[]" value="<?php echo $gpcId; ?>" data-select-item="drop-monsters"<?php echo isset($selectedGpcIds[$gpcId]) ? ' checked="checked"' : ''; ?> /></td>
			<td><div class="query-pet"><strong><?php echo adminH($monster['name']); ?></strong><span>id=<?php echo $gpcId; ?></span></div></td>
			<td><div class="source-list"><?php foreach ($monster['_sources'] as $source) { ?><span><?php echo adminH($source); ?></span><?php } ?></div></td>
			<td><span class="badge muted"><?php echo intval($monster['level']); ?>级</span></td>
			<td><?php if (count($groups) === 0) { ?><span class="subtle">暂无掉落</span><?php } else { ?><div class="drop-list">
			<?php foreach ($groups as $group) { if (!$group['valid']) { ?><span class="drop-entry invalid"><strong>格式异常</strong><span><?php echo adminH($group['raw']); ?></span></span><?php } else { $propName = isset($props[$group['id']]) ? $props[$group['id']]['name'] : '道具不存在'; ?>
				<span class="drop-entry<?php echo $dropField === 'activedroplist' ? ' active' : ''; ?>"><strong><?php echo adminH($propName); ?></strong><span>id=<?php echo intval($group['id']); ?></span><span>1/<?php echo intval($group['denominator']); ?> · <?php echo adminH(adminDropPercent($group['denominator'])); ?>%</span><?php if ($group['count'] > 1) { ?><em>重复 <?php echo intval($group['count']); ?> 次</em><?php } ?></span>
			<?php } } ?></div><?php } ?></td></tr>
		<?php } ?>
		</tbody></table></div>
		<?php } ?>
	</section>
	<?php } ?>
	</form>
<?php adminPageEnd(); ?>
