<?php
require_once(dirname(__FILE__) . '/_bootstrap.php');
require_once(dirname(__FILE__) . '/_layout.php');

$weekdayLabels = array(1 => '一', 2 => '二', 3 => '三', 4 => '四', 5 => '五', 6 => '六', 7 => '日');
$activities = array(
	'gpc' => array(
		'name' => '天降宝盒',
		'icon_title' => '天降宝盒',
		'pic' => './images/ui/bag/xt04.jpg',
		'admin_pic' => '../images/ui/bag/xt04.jpg',
		'mode' => 'days',
		'default_days' => array(1, 3),
		'default_start' => '19:00',
		'default_end' => '20:00'
	),
	'guild_battle' => array(
		'name' => '家族战场',
		'icon_title' => '家族战场',
		'pic' => './images/ui/bag/xt07.jpg',
		'admin_pic' => '../images/ui/bag/xt07.jpg',
		'mode' => 'days',
		'default_days' => array(2, 5),
		'default_start' => '20:00',
		'default_end' => '21:00'
	),
	'battle' => array(
		'name' => '神圣战场',
		'icon_title' => '神圣战场',
		'pic' => './images/ui/bag/xt08.jpg',
		'admin_pic' => '../images/ui/bag/xt08.jpg',
		'mode' => 'days',
		'default_days' => array(3, 6),
		'default_start' => '20:00',
		'default_end' => '21:00'
	),
	'exp1' => array(
		'name' => '三倍经验',
		'icon_title' => '三倍经验',
		'pic' => './images/ui/bag/xt06.jpg',
		'admin_pic' => '../images/ui/bag/xt06.jpg',
		'mode' => 'weekly_range',
		'default_days' => array(1, 2, 3, 4, 5, 6, 7),
		'default_start' => '01:00',
		'default_end' => '23:00',
		'default_start_day' => 5,
		'default_end_day' => 7
	)
);

function adminSaveTimeConfigRow($db, $title, $days, $starttime, $endtime)
{
	$titleSql = $db->escape($title);
	$daysSql = $db->escape($days);
	$startSql = $db->escape($starttime);
	$endSql = $db->escape($endtime);
	$rows = $db->getRecords("SELECT Id FROM timeconfig WHERE titles='{$titleSql}' ORDER BY Id FOR UPDATE");
	if (is_array($rows) && count($rows) > 0)
	{
		$keepId = intval($rows[0]['Id']);
		if (!$db->query("UPDATE timeconfig SET days='{$daysSql}',starttime='{$startSql}',endtime='{$endSql}' WHERE Id={$keepId}")) return false;
		$removeIds = array();
		for ($i = 1; $i < count($rows); $i++) $removeIds[] = intval($rows[$i]['Id']);
		if (count($removeIds) > 0 && !$db->query('DELETE FROM timeconfig WHERE Id IN (' . implode(',', $removeIds) . ')')) return false;
		return true;
	}
	$allRows = $db->getRecords("SELECT Id FROM timeconfig ORDER BY Id FOR UPDATE");
	if (!is_array($allRows)) return false;
	$newId = adminNextFreeNumericId($allRows, 'Id');
	if ($newId === false) return false;
	return $db->query("INSERT INTO timeconfig(Id,titles,days,starttime,endtime) VALUES({$newId},'{$titleSql}','{$daysSql}','{$startSql}','{$endSql}')") ? true : false;
}

function adminActivityClockFromMinutes($minutes)
{
	$minutes = max(0, min(1439, intval($minutes)));
	return sprintf('%02d:%02d', floor($minutes / 60), $minutes % 60);
}

function adminActivityIconRowsFromDays($days, $starttime, $endtime)
{
	$rows = array();
	foreach ($days as $day)
	{
		$day = intval($day);
		if ($day >= 1 && $day <= 7) $rows[] = array('day' => $day, 'start' => $starttime, 'end' => $endtime);
	}
	return $rows;
}

function adminActivityIconRowsFromWeeklyRange($startDay, $starttime, $endDay, $endtime)
{
	$rows = array();
	$start = weeklyTimeToMinutes($startDay, $starttime);
	$end = weeklyTimeToMinutes($endDay, $endtime);
	if ($start === false || $end === false) return $rows;
	if ($end < $start) $end += 10080;
	$firstDayStart = floor($start / 1440) * 1440;
	$lastDayStart = floor($end / 1440) * 1440;
	for ($dayStart = $firstDayStart; $dayStart <= $lastDayStart; $dayStart += 1440)
	{
		$day = (intval(floor($dayStart / 1440)) % 7) + 1;
		$rowStart = max($start, $dayStart) - $dayStart;
		$rowEnd = min($end, $dayStart + 1439) - $dayStart;
		if ($rowEnd < $rowStart) continue;
		$rows[] = array(
			'day' => $day,
			'start' => adminActivityClockFromMinutes($rowStart),
			'end' => adminActivityClockFromMinutes($rowEnd)
		);
	}
	return $rows;
}

function adminSaveActivityIcons($db, $definition, $iconRows)
{
	$titleSql = $db->escape($definition['icon_title']);
	if (!$db->query("DELETE FROM system_activity WHERE title='{$titleSql}'")) return false;
	$idRows = $db->getRecords('SELECT id FROM system_activity ORDER BY id FOR UPDATE');
	if (!is_array($idRows)) return false;
	$picSql = $db->escape($definition['pic']);
	foreach ($iconRows as $row)
	{
		$id = adminNextFreeNumericId($idRows, 'id');
		if ($id === false) return false;
		$idRows[] = array('id' => $id);
		$timeSql = $db->escape($row['start'] . '|' . $row['end']);
		if (!$db->query("INSERT INTO system_activity(id,title,time,week,pic) VALUES({$id},'{$titleSql}','{$timeSql}'," . intval($row['day']) . ",'{$picSql}')")) return false;
	}
	return true;
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST')
{
	$key = adminPost('activity_key');
	if (!isset($activities[$key]))
	{
		adminSetFlash('error', '活动类型无效。');
		adminRedirect('activities.php');
	}
	$definition = $activities[$key];

	if ($definition['mode'] === 'days')
	{
		$actualDays = adminPostedDays(isset($_POST['actual_days']) ? $_POST['actual_days'] : array());
		$actualStart = adminNormalizeClockInput(adminPost('actual_start'));
		$actualEnd = adminNormalizeClockInput(adminPost('actual_end'));
		if (count($actualDays) === 0 || $actualStart === false || $actualEnd === false ||
			clockTimeToMinutes($actualStart) >= clockTimeToMinutes($actualEnd))
		{
			adminSetFlash('error', '请完整设置活动星期和同一天内有效的开始、结束时间。');
			adminRedirect('activities.php');
		}
		$timeDays = implode('|', $actualDays);
		$timeStart = $actualStart;
		$timeEnd = $actualEnd;
		$iconRows = adminActivityIconRowsFromDays($actualDays, $actualStart, $actualEnd);
	}
	else
	{
		$actualStartDay = intval(adminPost('actual_start_day', 0));
		$actualEndDay = intval(adminPost('actual_end_day', 0));
		$actualStart = adminNormalizeClockInput(adminPost('actual_start'));
		$actualEnd = adminNormalizeClockInput(adminPost('actual_end'));
		$startPoint = weeklyTimeToMinutes($actualStartDay, $actualStart);
		$endPoint = weeklyTimeToMinutes($actualEndDay, $actualEnd);
		if ($startPoint === false || $endPoint === false || $startPoint === $endPoint)
		{
			adminSetFlash('error', '请完整设置三倍经验的开始、结束星期和时间。');
			adminRedirect('activities.php');
		}
		$timeDays = '3';
		$timeStart = $actualStartDay . '|' . str_replace(':', '', $actualStart);
		$timeEnd = $actualEndDay . '|' . str_replace(':', '', $actualEnd);
		$iconRows = adminActivityIconRowsFromWeeklyRange($actualStartDay, $actualStart, $actualEndDay, $actualEnd);
		if (count($iconRows) === 0)
		{
			adminSetFlash('error', '活动图标时间生成失败。');
			adminRedirect('activities.php');
		}
	}

	if (!adminStartTransaction($adminDb))
	{
		adminSetFlash('error', $definition['name'] . '保存失败：无法开始数据库事务。');
		adminRedirect('activities.php');
	}
	$ok = adminSaveTimeConfigRow($adminDb, $key, $timeDays, $timeStart, $timeEnd);
	if ($ok) $ok = adminSaveActivityIcons($adminDb, $definition, $iconRows);
	if (!$ok || !$adminDb->query('COMMIT'))
	{
		$adminDb->query('ROLLBACK');
		adminSetFlash('error', $definition['name'] . '保存失败：' . $adminDb->getError());
		adminRedirect('activities.php');
	}
	$cacheOk = adminRefreshTimeConfigCache($adminDb, $adminMem);
	adminSetFlash($cacheOk ? 'success' : 'warning', $definition['name'] . '已保存' . ($cacheOk ? '。' : '，但活动时间缓存刷新失败。'));
	adminRedirect('activities.php');
}

$timeRows = $adminDb->getRecords("SELECT * FROM timeconfig WHERE titles IN ('gpc','guild_battle','battle','exp1') ORDER BY Id");
$timeByTitle = array();
if (is_array($timeRows)) foreach ($timeRows as $row) $timeByTitle[$row['titles']][] = $row;

$states = array();
foreach ($activities as $key => $definition)
{
	$state = array(
		'actual_days' => $definition['default_days'],
		'actual_start' => $definition['default_start'],
		'actual_end' => $definition['default_end'],
		'actual_start_day' => isset($definition['default_start_day']) ? $definition['default_start_day'] : 1,
		'actual_end_day' => isset($definition['default_end_day']) ? $definition['default_end_day'] : 1
	);
	if (isset($timeByTitle[$key]) && count($timeByTitle[$key]) > 0)
	{
		if ($definition['mode'] === 'days')
		{
			$actualDayMap = array();
			foreach ($timeByTitle[$key] as $index => $row)
			{
				foreach (weeklyDayList($row['days']) as $day) $actualDayMap[$day] = $day;
				if ($index === 0)
				{
					$start = adminClockInput($row['starttime']);
					$end = adminClockInput($row['endtime']);
					if ($start !== '') $state['actual_start'] = $start;
					if ($end !== '') $state['actual_end'] = $end;
				}
			}
			ksort($actualDayMap);
			if (count($actualDayMap) > 0) $state['actual_days'] = array_values($actualDayMap);
		}
		else
		{
			$row = $timeByTitle[$key][0];
			$startParts = explode('|', $row['starttime'], 2);
			$endParts = explode('|', $row['endtime'], 2);
			if (count($startParts) === 2 && intval($startParts[0]) >= 1 && intval($startParts[0]) <= 7)
			{
				$state['actual_start_day'] = intval($startParts[0]);
				$start = adminClockInput($startParts[1]);
				if ($start !== '') $state['actual_start'] = $start;
			}
			if (count($endParts) === 2 && intval($endParts[0]) >= 1 && intval($endParts[0]) <= 7)
			{
				$state['actual_end_day'] = intval($endParts[0]);
				$end = adminClockInput($endParts[1]);
				if ($end !== '') $state['actual_end'] = $end;
			}
		}
	}
	$states[$key] = $state;
}

adminPageStart('活动管理', 'activities');
?>
	<section class="band">
		<div class="section-head"><div><h2>活动时间</h2></div></div>
		<div class="activity-list">
		<?php foreach ($activities as $key => $definition) { $state = $states[$key]; ?>
			<form class="activity-editor" method="post">
				<input type="hidden" name="activity_key" value="<?php echo adminH($key); ?>" />
				<div class="activity-editor-head">
					<div class="activity-title"><img src="<?php echo adminH($definition['admin_pic']); ?>" alt="" /><div><strong><?php echo adminH($definition['name']); ?></strong><span><?php echo adminH($key); ?></span></div></div>
					<button class="btn primary" type="submit">保存</button>
				</div>
				<div class="activity-columns single">
					<div class="schedule-panel">
						<h3>活动持续时间</h3>
						<?php if ($definition['mode'] === 'days') { ?>
						<div class="weekday-list">
						<?php foreach ($weekdayLabels as $day => $label) { ?><label class="weekday-option"><input type="checkbox" name="actual_days[]" value="<?php echo $day; ?>"<?php echo in_array($day, $state['actual_days'], true) ? ' checked="checked"' : ''; ?> />周<?php echo $label; ?></label><?php } ?>
						</div>
						<div class="time-row">
							<div class="field"><label>开始时间</label><input class="input" type="time" name="actual_start" value="<?php echo adminH($state['actual_start']); ?>" required="required" /></div>
							<div class="field"><label>结束时间</label><input class="input" type="time" name="actual_end" value="<?php echo adminH($state['actual_end']); ?>" required="required" /></div>
						</div>
						<?php } else { ?>
						<div class="weekly-range">
							<div class="field"><label>开始星期</label><select class="select" name="actual_start_day"><?php foreach ($weekdayLabels as $day => $label) { ?><option value="<?php echo $day; ?>"<?php echo $day === $state['actual_start_day'] ? ' selected="selected"' : ''; ?>>周<?php echo $label; ?></option><?php } ?></select></div>
							<div class="field"><label>开始时间</label><input class="input" type="time" name="actual_start" value="<?php echo adminH($state['actual_start']); ?>" required="required" /></div>
							<div class="field"><label>结束星期</label><select class="select" name="actual_end_day"><?php foreach ($weekdayLabels as $day => $label) { ?><option value="<?php echo $day; ?>"<?php echo $day === $state['actual_end_day'] ? ' selected="selected"' : ''; ?>>周<?php echo $label; ?></option><?php } ?></select></div>
							<div class="field"><label>结束时间</label><input class="input" type="time" name="actual_end" value="<?php echo adminH($state['actual_end']); ?>" required="required" /></div>
						</div>
						<?php } ?>
					</div>
				</div>
			</form>
		<?php } ?>
		</div>
	</section>
<?php adminPageEnd(); ?>
