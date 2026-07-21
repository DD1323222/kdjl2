<?php
require_once(dirname(__FILE__) . '/_bootstrap.php');
require_once(dirname(__FILE__) . '/_layout.php');

$a = trim(adminGet('a'));
$b = trim(adminGet('b'));
$c = trim(adminGet('c'));
$d = trim(adminGet('d'));
$godOnly = isset($_GET['god']);
$searched = isset($_GET['search']) || $godOnly;
$rows = array();
if ($searched)
{
	$sql = "SELECT m.id AS formula_id,m.aid,m.bid,m.maid,m.mbid,m.limits,
				 a.name AS a_name,b.name AS b_name,c.name AS c_name,d.name AS d_name
			  FROM merge m
		 LEFT JOIN bb a ON a.id=m.aid
		 LEFT JOIN bb b ON b.id=m.bid
		 LEFT JOIN bb c ON c.id=m.maid
		 LEFT JOIN bb d ON d.id=m.mbid
			 WHERE 1";
	if ($godOnly) $sql .= ' AND (c.wx=6 OR d.wx=6) AND m.maid<>m.aid AND m.mbid<>m.aid';
	$sql .= adminPetMatchSql($adminDb, 'm.aid', 'a.name', $a);
	$sql .= adminPetMatchSql($adminDb, 'm.bid', 'b.name', $b);
	$sql .= adminPetMatchSql($adminDb, 'm.maid', 'c.name', $c);
	$sql .= adminPetMatchSql($adminDb, 'm.mbid', 'd.name', $d);
	$sql .= ' ORDER BY m.id';
	$result = $adminDb->getRecords($sql);
	if (is_array($result)) $rows = $result;
}

adminPageStart('合成查询', 'merge');
?>
	<section class="band">
		<div class="section-head"><div><h2>A + B = C / D</h2><?php if ($searched) { ?><div class="subtle"><?php echo count($rows); ?> 条匹配公式</div><?php } ?></div><span class="badge warning">D 稀有路线</span></div>
		<form class="search-form" method="get">
			<div class="field"><label>A 主宠</label><input class="input" name="a" value="<?php echo adminH($a); ?>" placeholder="id 或宠物名" /></div>
			<div class="field"><label>B 副宠</label><input class="input" name="b" value="<?php echo adminH($b); ?>" placeholder="id 或宠物名" /></div>
			<div class="field"><label>C 目标宠物</label><input class="input" name="c" value="<?php echo adminH($c); ?>" placeholder="id 或宠物名" /></div>
			<div class="field"><label>D 稀有目标宠物</label><input class="input" name="d" value="<?php echo adminH($d); ?>" placeholder="id 或宠物名" /></div>
			<button class="btn primary" type="submit" name="search" value="1">开始查询</button>
			<a class="btn secondary" href="merge.php?god=1">列出神系目标路线</a>
		</form>
	</section>
	<?php if ($searched) { ?><section class="band">
		<?php if (count($rows) === 0) { ?><div class="empty">没有满足条件的合成公式</div><?php } else { ?>
		<div class="table-wrap"><table><thead><tr><th>公式 ID</th><th>A 主宠</th><th>+</th><th>B 副宠</th><th>=</th><th>C 目标宠物</th><th>/</th><th>D 稀有目标宠物</th><th>成长限制</th></tr></thead><tbody>
		<?php foreach ($rows as $row) { ?><tr><td class="code"><?php echo intval($row['formula_id']); ?></td><td><?php adminPetCell($row['aid'], $row['a_name']); ?></td><td>+</td><td><?php adminPetCell($row['bid'], $row['b_name']); ?></td><td>=</td><td><?php adminPetCell($row['maid'], $row['c_name']); ?></td><td>/</td><td><?php adminPetCell($row['mbid'], $row['d_name']); ?></td><td class="code"><?php echo trim((string)$row['limits']) === '' ? '-' : adminH($row['limits']); ?></td></tr><?php } ?>
		</tbody></table></div>
		<?php } ?>
	</section><?php } ?>
<?php adminPageEnd(); ?>
