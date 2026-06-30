<?php
require_once(dirname(__FILE__) . '/_bootstrap.php');
require_once(dirname(__FILE__) . '/_layout.php');

$pets = adminPetMap($adminDb);
$props = adminPropsMap($adminDb);
$routes = adminEvolutionRoutes($pets);
$routesBySource = array();
foreach ($routes as $route) $routesBySource[$route['source_id']][] = $route;
$families = array(1 => '金', 13 => '木', 23 => '水', 32 => '火', 42 => '土');
$familyRoutes = array();
foreach ($families as $startId => $element)
{
	$steps = array();
	$queue = array(array('id' => $startId, 'depth' => 0));
	$offset = 0;
	$visited = array($startId => true);
	$seenEdges = array();
	while (isset($queue[$offset]))
	{
		$current = $queue[$offset++];
		$sourceId = intval($current['id']);
		if (!isset($routesBySource[$sourceId])) continue;
		foreach ($routesBySource[$sourceId] as $route)
		{
			$targetId = intval($route['target_id']);
			if ($targetId === $sourceId) continue;
			$edgeKey = $sourceId . ':' . $targetId . ':' . intval($route['branch']);
			if (!isset($seenEdges[$edgeKey]))
			{
				$route['_depth'] = intval($current['depth']) + 1;
				$steps[] = $route;
				$seenEdges[$edgeKey] = true;
			}
			if (!isset($visited[$targetId]) && isset($pets[$targetId]))
			{
				$visited[$targetId] = true;
				$queue[] = array('id' => $targetId, 'depth' => intval($current['depth']) + 1);
			}
		}
	}
	$familyRoutes[$startId] = $steps;
}

adminPageStart('波姆进化路线', 'slime');
?>
	<?php foreach ($families as $startId => $element) { $steps = $familyRoutes[$startId]; ?>
	<section class="band">
		<div class="section-head"><div><h2><?php echo adminH($element); ?>系波姆</h2><div class="subtle"><?php echo count($steps); ?> 条进化分支</div></div><?php adminPetCell($startId, isset($pets[$startId]) ? $pets[$startId]['name'] : null); ?></div>
		<?php if (count($steps) === 0) { ?><div class="empty">没有可用进化路线</div><?php } else { ?>
		<div class="table-wrap"><table><thead><tr><th>步骤</th><th>当前宠物</th><th>所需道具</th><th>目标宠物</th><th>所需等级</th></tr></thead><tbody>
		<?php foreach ($steps as $route) { ?><tr>
			<td class="code"><?php echo intval($route['_depth']); ?></td>
			<td><?php adminPetCell($route['source_id'], isset($pets[$route['source_id']]) ? $pets[$route['source_id']]['name'] : null); ?></td>
			<td><div class="form-row"><?php $first = true; foreach ($route['material_ids'] as $materialId) { if (!$first) echo '<span>/</span>'; adminPropCell($materialId, isset($props[$materialId]) ? $props[$materialId]['name'] : null); $first = false; } ?></div></td>
			<td><?php adminPetCell($route['target_id'], isset($pets[$route['target_id']]) ? $pets[$route['target_id']]['name'] : null); ?></td>
			<td><?php echo intval($route['level']); ?> 级</td>
		</tr><?php } ?>
		</tbody></table></div>
		<?php } ?>
	</section>
	<?php } ?>
<?php adminPageEnd(); ?>
