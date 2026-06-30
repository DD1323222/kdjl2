<?php
require_once(dirname(__FILE__) . '/_bootstrap.php');
require_once(dirname(__FILE__) . '/_layout.php');

$a = isset($_GET['a']) ? trim($_GET['a']) : '';
$b = isset($_GET['b']) ? trim($_GET['b']) : '';
$c = isset($_GET['c']) ? trim($_GET['c']) : '';
$searched = isset($_GET['search']);
$rows = array();
if ($searched)
{
	$sql = "SELECT z.Id AS formula_id,z.aid,z.bid,z.mid,a.name AS a_name,b.name AS b_name,c.name AS c_name
			  FROM zs z
		 LEFT JOIN bb a ON a.id=z.aid
		 LEFT JOIN bb b ON b.id=z.bid
		 LEFT JOIN bb c ON c.id=z.mid
			 WHERE 1";
	$sql .= adminPetMatchSql($adminDb, 'z.aid', 'a.name', $a);
	$sql .= adminPetMatchSql($adminDb, 'z.bid', 'b.name', $b);
	$sql .= adminPetMatchSql($adminDb, 'z.mid', 'c.name', $c);
	$sql .= ' ORDER BY z.Id';
	$result = $adminDb->getRecords($sql);
	if (is_array($result)) $rows = $result;
}

adminPageStart('涅槃查询', 'nirvana');
?>
	<section class="band">
		<div class="section-head"><div><h2>A + B = C</h2><?php if ($searched) { ?><div class="subtle"><?php echo count($rows); ?> 条匹配公式</div><?php } ?></div></div>
		<form class="search-form three" method="get">
			<div class="field"><label>A 主宠</label><input class="input" name="a" value="<?php echo adminH($a); ?>" placeholder="id 或宠物名" /></div>
			<div class="field"><label>B 副宠</label><input class="input" name="b" value="<?php echo adminH($b); ?>" placeholder="id 或宠物名" /></div>
			<div class="field"><label>C 产物</label><input class="input" name="c" value="<?php echo adminH($c); ?>" placeholder="id 或宠物名" /></div>
			<button class="btn primary" type="submit" name="search" value="1">开始查询</button>
		</form>
	</section>
	<?php if ($searched) { ?><section class="band">
		<?php if (count($rows) === 0) { ?><div class="empty">没有满足条件的涅槃公式</div><?php } else { ?>
		<div class="table-wrap"><table><thead><tr><th>公式 ID</th><th>A 主宠</th><th>+</th><th>B 副宠</th><th>=</th><th>C 产物</th></tr></thead><tbody>
		<?php foreach ($rows as $row) { ?><tr><td class="code"><?php echo intval($row['formula_id']); ?></td><td><?php adminPetCell($row['aid'], $row['a_name']); ?></td><td>+</td><td><?php adminPetCell($row['bid'], $row['b_name']); ?></td><td>=</td><td><?php adminPetCell($row['mid'], $row['c_name']); ?></td></tr><?php } ?>
		</tbody></table></div>
		<?php } ?>
	</section><?php } ?>
<?php adminPageEnd(); ?>
