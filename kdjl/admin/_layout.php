<?php
function adminPageStart($title, $active)
{
	$flash = adminGetFlash();
	$shopOpen = in_array($active, array('yb', 'sj', 'vip', 'limited'));
	$queryOpen = in_array($active, array('evolution', 'slime', 'nirvana', 'merge'));
	$dropOpen = in_array($active, array('monster_drops', 'item_drops', 'activity_drops'));
	$contentOpen = in_array($active, array('pets', 'items'));
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title><?php echo adminH($title); ?> - 游戏管理</title>
	<link rel="stylesheet" href="admin.css?v=<?php echo intval(@filemtime(dirname(__FILE__) . '/admin.css')); ?>" />
</head>
<body>
	<div class="admin-shell">
		<aside class="sidebar">
			<div class="brand">游戏管理</div>
			<nav>
				<details class="nav-group"<?php echo $contentOpen ? ' open="open"' : ''; ?>>
					<summary>内容定制</summary>
					<a<?php echo $active === 'pets' ? ' class="active"' : ''; ?> href="pets.php">宠物定制管理</a>
					<a<?php echo $active === 'items' ? ' class="active"' : ''; ?> href="items.php">道具修改管理</a>
				</details>
				<details class="nav-group"<?php echo $dropOpen ? ' open="open"' : ''; ?>>
					<summary>掉落管理</summary>
					<a<?php echo $active === 'monster_drops' ? ' class="active"' : ''; ?> href="drops.php">怪物掉落管理</a>
					<a<?php echo $active === 'item_drops' ? ' class="active"' : ''; ?> href="item_drops.php">道具掉落管理</a>
					<a<?php echo $active === 'activity_drops' ? ' class="active"' : ''; ?> href="activity_drops.php">活动掉落管理</a>
				</details>
				<a class="nav-direct<?php echo $active === 'activities' ? ' active' : ''; ?>" href="activities.php">活动管理</a>
				<a class="nav-direct<?php echo $active === 'tasks' ? ' active' : ''; ?>" href="tasks.php">任务管理</a>
				<a class="nav-direct<?php echo $active === 'grant' ? ' active' : ''; ?>" href="grant.php">物品发放</a>
				<details class="nav-group"<?php echo $shopOpen ? ' open="open"' : ''; ?>>
					<summary>神秘商店管理</summary>
					<a<?php echo $active === 'yb' ? ' class="active"' : ''; ?> href="index.php">神秘商店管理</a>
					<a<?php echo $active === 'sj' ? ' class="active"' : ''; ?> href="shop.php?channel=sj">水晶商店管理</a>
					<a<?php echo $active === 'vip' ? ' class="active"' : ''; ?> href="shop.php?channel=vip">VIP商城管理</a>
					<a<?php echo $active === 'limited' ? ' class="active"' : ''; ?> href="limited.php">抢购商城管理</a>
				</details>
				<details class="nav-group"<?php echo $queryOpen ? ' open="open"' : ''; ?>>
					<summary>查询</summary>
					<a<?php echo $active === 'evolution' ? ' class="active"' : ''; ?> href="evolution.php">进化查询</a>
					<a<?php echo $active === 'slime' ? ' class="active"' : ''; ?> href="slime_routes.php">波姆进化路线</a>
					<a<?php echo $active === 'nirvana' ? ' class="active"' : ''; ?> href="nirvana.php">涅槃查询</a>
					<a<?php echo $active === 'merge' ? ' class="active"' : ''; ?> href="merge.php">合成查询</a>
				</details>
			</nav>
		</aside>
		<main class="main">
			<header class="page-header"><h1><?php echo adminH($title); ?></h1></header>
			<?php if (is_array($flash)) { ?><div class="flash <?php echo adminH($flash['type']); ?>"><?php echo adminH($flash['message']); ?></div><?php } ?>
<?php
}

function adminPageEnd()
{
?>
		</main>
	</div>
	<script src="admin.js?v=<?php echo intval(@filemtime(dirname(__FILE__) . '/admin.js')); ?>"></script>
</body>
</html>
<?php
}

function adminPropLabel($row)
{
?>
	<div class="item"><img src="../images/ui/bag/<?php echo intval($row['varyname']); ?>.gif" alt="" /><div><strong><?php echo adminH($row['name']); ?></strong><div class="prop-meta"><span>id=<?php echo intval($row['id']); ?></span><?php adminPropTradeBadge($row); ?></div></div></div>
<?php
}

function adminPropTradeState($row)
{
	if (!is_array($row) || !array_key_exists('propslock', $row) || $row['propslock'] === null) return null;
	return intval($row['propslock']) === 1;
}

function adminPropTradeText($row)
{
	$state = adminPropTradeState($row);
	if ($state === null) return '交易状态未知';
	return $state ? '可交易' : '不可交易';
}

function adminPropTradeBadge($row)
{
	$state = adminPropTradeState($row);
	$class = $state === null ? 'warning' : ($state ? 'success' : 'muted');
	echo '<span class="badge ' . $class . '">' . adminH(adminPropTradeText($row)) . '</span>';
}

function adminScheduleBadge($state)
{
	if ($state === 'active') echo '<span class="badge success">有效</span>';
	else if ($state === 'scheduled') echo '<span class="badge warning">待上架</span>';
	else echo '<span class="badge muted">已过期</span>';
}

function adminPetCell($id, $name)
{
	$name = $name === null || $name === '' ? '宠物定义不存在' : $name;
?>
	<div class="query-pet"><strong><?php echo adminH($name); ?></strong><span>id=<?php echo intval($id); ?></span></div>
<?php
}

function adminPropCell($id, $name, $propslock = null)
{
	$name = $name === null || $name === '' ? '道具定义不存在' : $name;
	$row = array();
	if ($propslock !== null) $row['propslock'] = $propslock;
?>
	<div class="query-pet"><strong><?php echo adminH($name); ?></strong><div class="prop-meta"><span>id=<?php echo intval($id); ?></span><?php adminPropTradeBadge($row); ?></div></div>
<?php
}
