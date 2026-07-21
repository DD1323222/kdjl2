<?php
require_once(dirname(__FILE__) . '/_bootstrap.php');
require_once(dirname(__FILE__) . '/_catalog_helpers.php');
require_once(dirname(__FILE__) . '/_layout.php');

function adminPetDefaults()
{
	return array(
		'id' => 0, 'name' => '', 'wx' => 1, 'ac' => 0, 'mc' => 0, 'hp' => 0, 'mp' => 0,
		'speed' => 0, 'hits' => 0, 'miss' => 0, 'imgstand' => '', 'imgack' => '', 'imgdie' => '',
		'skillist' => '1:1', 'czl' => '1.0,1.0', 'kx' => '0', 'remakelevel' => '0',
		'remakeid' => '0', 'remakepid' => '0', 'nowexp' => 0, 'lexp' => 0,
		'subyl' => 0, 'subsl' => 0, 'subxl' => 0, 'subdl' => 0, 'subfl' => 0, 'subhl' => 0, 'subkl' => 0,
		'headimg' => '', 'cardimg' => '', 'effectimg' => '', 'bbdesc' => ''
	);
}

function adminPetCollectForm(&$errors)
{
	$row = adminPetDefaults();
	$row['id'] = adminCatalogInteger('id', 0, 2147483647, false, $errors);
	$row['name'] = adminCatalogText('name', 50, true, $errors);
	$row['wx'] = adminCatalogInteger('wx', 1, 7, false, $errors);
	foreach (array('ac', 'mc', 'hp', 'mp', 'speed', 'hits', 'miss', 'nowexp', 'lexp') as $field)
		$row[$field] = adminCatalogInteger($field, 0, 2147483647, false, $errors);
	foreach (array('subyl', 'subsl', 'subxl', 'subdl', 'subfl', 'subhl', 'subkl') as $field)
		$row[$field] = adminCatalogInteger($field, 0, 65535, false, $errors);
	$row['skillist'] = adminCatalogText('skillist', 255, false, $errors);
	$row['czl'] = adminCatalogText('czl', 50, true, $errors);
	$row['kx'] = adminCatalogText('kx', 255, true, $errors);
	$row['remakelevel'] = adminCatalogText('remakelevel', 30, true, $errors);
	$row['remakeid'] = adminCatalogText('remakeid', 30, true, $errors);
	$row['remakepid'] = adminCatalogText('remakepid', 30, true, $errors);
	foreach (adminPetResourceSpecs() as $field => $spec)
		$row[$field] = adminCatalogText($field, 50, false, $errors);
	$row['bbdesc'] = adminCatalogText('bbdesc', 255, false, $errors);

	if ($row['skillist'] !== '' && $row['skillist'] !== '0' &&
		!preg_match('/^[0-9]+:[0-9]+(?:,[0-9]+:[0-9]+)*$/D', $row['skillist']))
		$errors[] = '技能列表格式应为 技能id:等级，多个技能用英文逗号分隔。';
	if (!preg_match('/^[0-9]+(?:\.[0-9]+)?(?:,[0-9]+(?:\.[0-9]+)?)*$/D', $row['czl']))
		$errors[] = '成长率只能包含非负小数和英文逗号。';
	if (!preg_match('/^-?[0-9]+(?:,-?[0-9]+)*$/D', $row['kx']))
		$errors[] = '抗性只能包含整数和英文逗号。';
	if (!preg_match('/^[0-9]+(?:,[0-9]+)*$/D', $row['remakelevel']))
		$errors[] = '进化等级只能包含整数和英文逗号。';
	if (!preg_match('/^[0-9]+(?:,[0-9]+)*$/D', $row['remakeid']))
		$errors[] = '目标宠物只能包含整数和英文逗号。';
	if (!preg_match('/^[0-9]+(?:\|[0-9]+)*(?:,[0-9]+(?:\|[0-9]+)*)*$/D', $row['remakepid']))
		$errors[] = '进化道具格式应为同路线道具用 |、不同路线用英文逗号分隔。';
	if (count(explode(',', $row['remakelevel'])) !== count(explode(',', $row['remakeid'])) ||
		count(explode(',', $row['remakeid'])) !== count(explode(',', $row['remakepid'])))
		$errors[] = '进化等级、目标宠物、进化道具的路线段数量必须一致。';
	return $row;
}

function adminPetAssignments($db, $row)
{
	$textFields = array('name', 'imgstand', 'imgack', 'imgdie', 'skillist', 'czl', 'kx', 'remakelevel', 'remakeid', 'remakepid', 'headimg', 'cardimg', 'effectimg', 'bbdesc');
	$numberFields = array('wx', 'ac', 'mc', 'hp', 'mp', 'speed', 'hits', 'miss', 'nowexp', 'lexp', 'subyl', 'subsl', 'subxl', 'subdl', 'subfl', 'subhl', 'subkl');
	$assignments = array();
	foreach ($textFields as $field) $assignments[] = '`' . $field . '`=' . adminCatalogSqlText($db, $row[$field]);
	foreach ($numberFields as $field) $assignments[] = '`' . $field . '`=' . intval($row[$field]);
	return implode(',', $assignments);
}

function adminPetInput($row, $field)
{
	return isset($row[$field]) && !is_array($row[$field]) ? $row[$field] : '';
}

$errors = array();
$editor = false;
$isNew = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && adminPost('action') === 'save_pet')
{
	if (!adminCatalogVerifyCsrf()) $errors[] = '页面校验已失效，请刷新后重试。';
	$editor = adminPetCollectForm($errors);
	$petId = intval($editor['id']);
	$isNew = $petId < 1;
	$oldRow = $isNew ? false : $adminDb->getOneRecord('SELECT * FROM bb WHERE id=' . $petId . ' LIMIT 1');
	if (!$isNew && !is_array($oldRow)) $errors[] = '要修改的宠物不存在。';
	$uploads = adminValidatePetUploads($errors);
	foreach (adminPetResourceSpecs() as $field => $spec)
	{
		if (isset($uploads[$field])) continue;
		$fileName = adminPetImageFileName($editor[$field]);
		if ($fileName === false)
		{
			$errors[] = $spec['label'] . '文件名必须是 50 字符以内的安全 GIF 文件名。';
			continue;
		}
		$path = adminPetImagePath($fileName);
		$changed = $isNew || !is_array($oldRow) || !isset($oldRow[$field]) || $oldRow[$field] !== $fileName;
		if ($changed && !is_file($path)) $errors[] = $spec['label'] . '引用的文件不存在；请上传新文件或填写已有文件名。';
	}
	if (count($errors) === 0)
	{
		$transaction = adminStartTransaction($adminDb);
		$fileState = array('operations' => array());
		if (!$transaction) $errors[] = '无法开启数据库事务。';
		if ($transaction && !$isNew)
		{
			$locked = $adminDb->getOneRecord('SELECT id FROM bb WHERE id=' . $petId . ' FOR UPDATE');
			if (!is_array($locked)) $errors[] = '宠物记录已不存在。';
		}
		if ($transaction && count($errors) === 0 && $isNew)
		{
			if (!$adminDb->query("INSERT INTO bb(name) VALUES('')")) $errors[] = '无法创建宠物记录。';
			else
			{
				$petId = intval($adminDb->last_id());
				$editor['id'] = $petId;
				if ($petId < 1) $errors[] = '无法取得新宠物 id。';
			}
		}
		if ($transaction && count($errors) === 0)
		{
			$fileError = '';
			$fileState = adminInstallPetUploads($uploads, $petId, $fileError);
			if ($fileState === false) $errors[] = $fileError;
			else
			{
				foreach (adminInstalledPetFileNames($fileState) as $field => $fileName) $editor[$field] = $fileName;
			}
		}
		if ($transaction && count($errors) === 0 &&
			!$adminDb->query('UPDATE bb SET ' . adminPetAssignments($adminDb, $editor) . ' WHERE id=' . $petId . ' LIMIT 1'))
			$errors[] = '宠物数据保存失败。';
		if ($transaction && count($errors) === 0 && !$adminDb->query('COMMIT')) $errors[] = '宠物数据提交失败。';
		if (count($errors) > 0)
		{
			if ($transaction) $adminDb->query('ROLLBACK');
			adminRestorePetUploads($fileState);
		}
		else
		{
			adminFinalizePetUploads($fileState);
			$cacheOk = adminRefreshBbCache($adminDb, $adminMem, array($petId));
			adminSetFlash($cacheOk ? 'success' : 'warning', $cacheOk ? '宠物已保存，数据库与缓存均已更新。' : '宠物已保存，但缓存刷新失败；请访问 vm1.php 刷新 bb。');
			adminRedirect('pets.php?edit=' . $petId);
		}
	}
}

if ($editor === false)
{
	$editId = intval(adminGet('edit'));
	$copyId = intval(adminGet('copy'));
	if ($editId > 0) $editor = $adminDb->getOneRecord('SELECT * FROM bb WHERE id=' . $editId . ' LIMIT 1');
	else if ($copyId > 0)
	{
		$editor = $adminDb->getOneRecord('SELECT * FROM bb WHERE id=' . $copyId . ' LIMIT 1');
		if (is_array($editor))
		{
			$editor['id'] = 0;
			$editor['name'] .= '（副本）';
			$isNew = true;
		}
	}
	else if (adminGet('new') === '1')
	{
		$editor = adminPetDefaults();
		$isNew = true;
	}
}
if ($editor !== false && !is_array($editor))
{
	adminSetFlash('error', '宠物不存在。');
	adminRedirect('pets.php');
}

$q = trim((string)adminGet('q'));
$page = max(1, intval(adminGet('page', 1)));
$pageSize = 80;
$where = '1=1';
if ($q !== '')
{
	$escaped = $adminDb->escape($q);
	$where = "name LIKE '%{$escaped}%'";
	if (preg_match('/^[0-9]+$/D', $q)) $where = '(id=' . intval($q) . " OR {$where})";
}
$countRow = $adminDb->getOneRecord('SELECT COUNT(*) AS total FROM bb WHERE ' . $where);
$total = is_array($countRow) ? intval($countRow['total']) : 0;
$maxPage = max(1, intval(ceil($total / $pageSize)));
if ($page > $maxPage) $page = $maxPage;
$offset = ($page - 1) * $pageSize;
$rows = $adminDb->getRecords('SELECT id,name,wx,ac,mc,hp,mp,speed,hits,miss,czl,skillist,headimg,cardimg FROM bb WHERE ' . $where . ' ORDER BY id LIMIT ' . $offset . ',' . $pageSize);
if (!is_array($rows)) $rows = array();

$wxLabels = array(1 => '金', 2 => '木', 3 => '水', 4 => '火', 5 => '土', 6 => '神', 7 => '神圣');
adminPageStart('宠物定制管理', 'pets');
?>
	<?php if (count($errors) > 0) { ?><div class="flash error"><?php foreach ($errors as $error) { ?><div><?php echo adminH($error); ?></div><?php } ?></div><?php } ?>
	<section class="band">
		<div class="section-head"><div><h2>宠物定义</h2><div class="subtle">修改的是 bb 基础模板；已生成到玩家账号的宠物多数属性仍保留其 userbb 副本。</div></div><a class="btn primary" href="pets.php?new=1">新增宠物</a></div>
		<form class="filters" method="get" action="pets.php">
			<input class="input catalog-search" type="text" name="q" value="<?php echo adminH($q); ?>" placeholder="输入 id 或宠物名" />
			<button class="btn secondary" type="submit">查询</button>
			<?php if ($q !== '') { ?><a class="btn secondary" href="pets.php">清除</a><?php } ?>
			<span class="subtle">共 <?php echo $total; ?> 条，第 <?php echo $page; ?>/<?php echo $maxPage; ?> 页</span>
		</form>
	</section>

	<?php if (is_array($editor)) { $isNew = intval($editor['id']) < 1; ?>
	<section class="band catalog-editor">
		<div class="section-head"><div><h2><?php echo $isNew ? '新增宠物' : '修改宠物 id=' . intval($editor['id']); ?></h2><div class="subtle">协议字段会完整保存；上传资源时文件名自动改为对应前缀加宠物 id。</div></div><a class="btn secondary" href="pets.php<?php echo $q === '' ? '' : '?q=' . urlencode($q); ?>">关闭编辑</a></div>
		<form method="post" action="pets.php" enctype="multipart/form-data">
			<input type="hidden" name="action" value="save_pet" />
			<input type="hidden" name="csrf_token" value="<?php echo adminH(adminCatalogCsrfToken()); ?>" />
			<input type="hidden" name="id" value="<?php echo intval($editor['id']); ?>" />
			<input type="hidden" name="MAX_FILE_SIZE" value="4194304" />
			<h3 class="catalog-heading">基本信息与初始属性</h3>
			<div class="catalog-grid four">
				<div class="field span-2"><label>宠物名称</label><input class="input" name="name" maxlength="50" required value="<?php echo adminH(adminPetInput($editor, 'name')); ?>" /></div>
				<div class="field"><label>五行</label><select class="select" name="wx"><?php foreach ($wxLabels as $id => $label) { ?><option value="<?php echo $id; ?>"<?php echo intval(adminPetInput($editor, 'wx')) === $id ? ' selected="selected"' : ''; ?>><?php echo adminH($label); ?> (<?php echo $id; ?>)</option><?php } ?></select></div>
				<div class="field"><label>成长率范围</label><input class="input code-input" name="czl" maxlength="50" value="<?php echo adminH(adminPetInput($editor, 'czl')); ?>" /></div>
				<?php foreach (array('ac' => '攻击', 'mc' => '防御', 'hp' => '生命', 'mp' => '魔法', 'speed' => '速度', 'hits' => '命中', 'miss' => '躲避', 'nowexp' => '初始经验', 'lexp' => '升级经验') as $field => $label) { ?>
				<div class="field"><label><?php echo adminH($label); ?></label><input class="input" type="number" min="0" max="2147483647" name="<?php echo $field; ?>" value="<?php echo intval(adminPetInput($editor, $field)); ?>" /></div>
				<?php } ?>
			</div>

			<h3 class="catalog-heading">技能、抗性与进化协议</h3>
			<div class="catalog-grid three">
				<div class="field"><label>技能列表 <span class="code">技能id:等级,...</span></label><input class="input code-input" name="skillist" maxlength="255" value="<?php echo adminH(adminPetInput($editor, 'skillist')); ?>" /></div>
				<div class="field span-2"><label>五行抗性 <span class="code">整数,整数,...</span></label><input class="input code-input" name="kx" maxlength="255" value="<?php echo adminH(adminPetInput($editor, 'kx')); ?>" /></div>
				<div class="field"><label>各路线进化等级 <span class="code">15,25</span></label><input class="input code-input" name="remakelevel" maxlength="30" value="<?php echo adminH(adminPetInput($editor, 'remakelevel')); ?>" /></div>
				<div class="field"><label>各路线目标宠物 id <span class="code">2,3</span></label><input class="input code-input" name="remakeid" maxlength="30" value="<?php echo adminH(adminPetInput($editor, 'remakeid')); ?>" /></div>
				<div class="field"><label>各路线所需道具 id <span class="code">94,95|1406</span></label><input class="input code-input" name="remakepid" maxlength="30" value="<?php echo adminH(adminPetInput($editor, 'remakepid')); ?>" /></div>
			</div>
			<div class="catalog-grid seven compact-grid">
				<?php foreach (array('subyl' => '抗晕', 'subsl' => '抗睡', 'subxl' => '抗虚', 'subdl' => '抗毒', 'subfl' => '抗防降', 'subhl' => '抗缓', 'subkl' => '抗性削减') as $field => $label) { ?>
				<div class="field"><label><?php echo adminH($label); ?></label><input class="input" type="number" min="0" max="65535" name="<?php echo $field; ?>" value="<?php echo intval(adminPetInput($editor, $field)); ?>" /></div>
				<?php } ?>
			</div>
			<div class="field catalog-description"><label>宠物介绍</label><textarea class="textarea" name="bbdesc" maxlength="255" rows="3"><?php echo adminH(adminPetInput($editor, 'bbdesc')); ?></textarea></div>

			<h3 class="catalog-heading">图片资源</h3>
			<div class="upload-notice">只接收结构完整、文件尾无附加内容的 GIF；单文件最大 4 MB、宽高最大 600 像素。战斗三态必须是至少 2 帧的动态 GIF。宠物在战斗中位于左侧，所以动作应面向右侧。</div>
			<div class="resource-grid">
			<?php foreach (adminPetResourceSpecs() as $field => $spec) {
				$fileName = adminPetImageFileName(adminPetInput($editor, $field));
				$path = $fileName === false ? false : adminPetImagePath($fileName);
				$exists = $path !== false && is_file($path);
				$info = $exists ? adminGifInfo($path) : false;
			?>
				<div class="resource-editor">
					<div class="resource-preview"><?php if ($exists) { ?><img src="../images/bb/<?php echo rawurlencode($fileName); ?>?v=<?php echo intval(@filemtime($path)); ?>" alt="" /><?php } else { ?><span>资源不存在</span><?php } ?></div>
					<div class="field"><label><?php echo adminH($spec['label']); ?>文件名</label><input class="input code-input" name="<?php echo $field; ?>" maxlength="50" value="<?php echo adminH(adminPetInput($editor, $field)); ?>" /></div>
					<div class="field"><label>上传新的 <?php echo adminH($spec['prefix']); ?>*.gif</label><input class="file-input" type="file" name="<?php echo adminH($spec['input']); ?>" accept="image/gif,.gif" /></div>
					<div class="subtle"><?php echo adminH($spec['recommended']); ?><?php if (is_array($info)) { ?><br /><span class="code"><?php echo $info['width']; ?>×<?php echo $info['height']; ?>，<?php echo $info['frames']; ?> 帧，<?php echo round($info['bytes'] / 1024, 1); ?> KB</span><?php } ?></div>
				</div>
			<?php } ?>
			</div>
			<div class="catalog-actions"><button class="btn primary" type="submit">保存宠物</button><a class="btn secondary" href="pets.php">取消</a></div>
		</form>
	</section>
	<?php } ?>

	<section class="band">
		<div class="table-wrap"><table class="catalog-table pet-table"><thead><tr><th>宠物</th><th>五行</th><th>初始属性</th><th>成长</th><th>技能</th><th>操作</th></tr></thead><tbody>
		<?php foreach ($rows as $row) {
			$thumb = adminPetImageFileName($row['headimg']);
			if ($thumb === false || !is_file(adminPetImagePath($thumb))) $thumb = adminPetImageFileName($row['cardimg']);
		?>
		<tr>
			<td><div class="catalog-entity"><?php if ($thumb !== false && is_file(adminPetImagePath($thumb))) { ?><img src="../images/bb/<?php echo rawurlencode($thumb); ?>" alt="" /><?php } ?><div><strong><?php echo adminH($row['name']); ?></strong><span>id=<?php echo intval($row['id']); ?></span></div></div></td>
			<td><?php echo isset($wxLabels[intval($row['wx'])]) ? adminH($wxLabels[intval($row['wx'])]) : '未知'; ?> <span class="code">(<?php echo intval($row['wx']); ?>)</span></td>
			<td class="code">HP <?php echo intval($row['hp']); ?> / MP <?php echo intval($row['mp']); ?><br />攻 <?php echo intval($row['ac']); ?> / 防 <?php echo intval($row['mc']); ?> / 速 <?php echo intval($row['speed']); ?></td>
			<td class="code"><?php echo adminH($row['czl']); ?></td>
			<td class="code catalog-ellipsis"><?php echo adminH($row['skillist']); ?></td>
			<td><div class="actions"><a class="btn secondary" href="pets.php?edit=<?php echo intval($row['id']); ?>">编辑</a><a class="btn secondary" href="pets.php?copy=<?php echo intval($row['id']); ?>">复制新增</a></div></td>
		</tr><?php } ?>
		</tbody></table></div>
		<?php if (count($rows) === 0) { ?><div class="empty">没有匹配的宠物。</div><?php } ?>
		<?php if ($maxPage > 1) { ?><div class="catalog-pager"><?php if ($page > 1) { ?><a class="btn secondary" href="pets.php?q=<?php echo urlencode($q); ?>&amp;page=<?php echo $page - 1; ?>">上一页</a><?php } ?><?php if ($page < $maxPage) { ?><a class="btn secondary" href="pets.php?q=<?php echo urlencode($q); ?>&amp;page=<?php echo $page + 1; ?>">下一页</a><?php } ?></div><?php } ?>
	</section>
<?php adminPageEnd(); ?>
