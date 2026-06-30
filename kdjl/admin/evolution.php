<?php
require_once(dirname(__FILE__) . '/_bootstrap.php');
require_once(dirname(__FILE__) . '/_layout.php');

$a = isset($_GET['a']) ? trim($_GET['a']) : '';
$b = isset($_GET['b']) ? trim($_GET['b']) : '';
$c = isset($_GET['c']) ? trim($_GET['c']) : '';
$d = isset($_GET['d']) ? trim($_GET['d']) : '';
$searched = isset($_GET['search']);
$matched = array();
if ($searched)
{
	$pets = adminPetMap($adminDb);
	$props = adminPropsMap($adminDb);
	$routes = adminEvolutionRoutes($pets);
	foreach ($routes as $route)
	{
		$sourceName = isset($pets[$route['source_id']]) ? $pets[$route['source_id']]['name'] : '';
		$targetName = isset($pets[$route['target_id']]) ? $pets[$route['target_id']]['name'] : '';
		if (!adminFuzzyMatch($a, $route['source_id'], $sourceName)) continue;
		if (!adminMaterialsMatch($b, $route['material_ids'], $props)) continue;
		if (!adminMaterialsMatch($c, $route['material_ids'], $props)) continue;
		if (!adminFuzzyMatch($d, $route['target_id'], $targetName)) continue;
		$matched[] = $route;
	}
}

adminPageStart('进化查询', 'evolution');
?>
	<section class="band">
		<div class="section-head"><div><h2>A + B / C = D</h2><?php if ($searched) { ?><div class="subtle"><?php echo count($matched); ?> 条匹配进化分支</div><?php } ?></div></div>
		<form class="search-form" method="get">
			<div class="field"><label>A 原宠物</label><input class="input" name="a" value="<?php echo adminH($a); ?>" placeholder="id 或宠物名" /></div>
			<div class="field"><label>B 进化道具</label><input class="input" name="b" value="<?php echo adminH($b); ?>" placeholder="id 或道具名" /></div>
			<div class="field"><label>C 进化道具</label><input class="input" name="c" value="<?php echo adminH($c); ?>" placeholder="id 或道具名" /></div>
			<div class="field"><label>D 目标宠物</label><input class="input" name="d" value="<?php echo adminH($d); ?>" placeholder="id 或宠物名" /></div>
			<button class="btn primary" type="submit" name="search" value="1">开始查询</button>
		</form>
	</section>
	<?php if ($searched) { ?><section class="band">
		<?php if (count($matched) === 0) { ?><div class="empty">没有满足条件的进化分支</div><?php } else { ?>
		<div class="table-wrap"><table><thead><tr><th>分支</th><th>A 原宠物</th><th>+</th><th>B / C 可用道具</th><th>=</th><th>D 目标宠物</th><th>所需等级</th></tr></thead><tbody>
		<?php foreach ($matched as $route) { ?><tr>
			<td class="code"><?php echo intval($route['source_id']); ?>-<?php echo intval($route['branch']); ?></td>
			<td><?php adminPetCell($route['source_id'], isset($pets[$route['source_id']]) ? $pets[$route['source_id']]['name'] : null); ?></td>
			<td>+</td>
			<td><div class="form-row"><?php $first = true; foreach ($route['material_ids'] as $materialId) { if (!$first) echo '<span>/</span>'; adminPropCell($materialId, isset($props[$materialId]) ? $props[$materialId]['name'] : null); $first = false; } ?></div></td>
			<td>=</td>
			<td><?php adminPetCell($route['target_id'], isset($pets[$route['target_id']]) ? $pets[$route['target_id']]['name'] : null); ?></td>
			<td><?php echo intval($route['level']); ?> 级</td>
		</tr><?php } ?>
		</tbody></table></div>
		<?php } ?>
	</section><?php } ?>
<?php adminPageEnd(); ?>
