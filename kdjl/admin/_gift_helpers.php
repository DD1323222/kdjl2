<?php

function adminGiftParseSource($row)
{
	$result = array(
		'supported' => false,
		'status' => 'unsupported',
		'mode' => '',
		'mode_label' => '不支持的效果协议',
		'prefix' => '',
		'entries' => array(),
		'errors' => array(),
		'effect' => is_array($row) && isset($row['effect']) ? trim((string)$row['effect']) : ''
	);
	$effect = $result['effect'];
	if ($effect === '' || $effect === '0')
	{
		$result['status'] = 'empty';
		$result['mode_label'] = '尚未配置奖励';
		return $result;
	}

	$givePosition = strpos($effect, 'giveitems:');
	$randomPosition = strpos($effect, 'randitem:');
	if ($givePosition !== false && $randomPosition !== false)
	{
		$result['errors'][] = '同一效果同时包含 giveitems 和 randitem，不能结构化编辑。';
		return $result;
	}

	if ($givePosition !== false)
	{
		$result['mode'] = 'fixed';
		$result['mode_label'] = '固定全部开出';
		$result['prefix'] = substr($effect, 0, $givePosition);
		$payload = substr($effect, $givePosition + strlen('giveitems:'));
		foreach (explode(',', $payload) as $token)
		{
			$token = trim($token);
			if ($token === '') continue;
			$parts = explode(':', $token);
			if (count($parts) !== 2 || !ctype_digit(trim($parts[0])) || !ctype_digit(trim($parts[1])) ||
				intval($parts[0]) < 1 || intval($parts[1]) < 1)
			{
				$result['errors'][] = '无法识别固定奖励片段：' . $token;
				continue;
			}
			$result['entries'][] = array('pid' => intval($parts[0]), 'count' => intval($parts[1]));
		}
	}
	else if ($randomPosition !== false)
	{
		$result['mode'] = 'random';
		$result['mode_label'] = '随机顺序开出';
		$result['prefix'] = substr($effect, 0, $randomPosition);
		$payload = substr($effect, $randomPosition + strlen('randitem:'));
		foreach (explode('|', $payload) as $token)
		{
			$token = trim($token);
			if ($token === '') continue;
			$parts = explode(':', $token);
			if ((count($parts) !== 3 && count($parts) !== 4) ||
				!ctype_digit(trim($parts[0])) || !ctype_digit(trim($parts[1])) || !ctype_digit(trim($parts[2])) ||
				intval($parts[0]) < 1 || intval($parts[1]) < 1 || intval($parts[2]) < 1)
			{
				$result['errors'][] = '无法识别随机奖励片段：' . $token;
				continue;
			}
			$notice = isset($parts[3]) && ctype_digit(trim($parts[3])) ? intval($parts[3]) : 1;
			if ($notice !== 1 && $notice !== 2)
			{
				$result['errors'][] = '随机奖励公告标记只能为 1 或 2：' . $token;
				continue;
			}
			$result['entries'][] = array(
				'pid' => intval($parts[0]),
				'count' => intval($parts[1]),
				'denominator' => intval($parts[2]),
				'notice' => $notice,
				'notice_explicit' => isset($parts[3])
			);
		}
	}
	else
	{
		return $result;
	}

	if (count($result['errors']) > 0 || count($result['entries']) === 0) return $result;
	$result['supported'] = true;
	$result['status'] = 'supported';
	return $result;
}

function adminGiftBuildEffect($parsed, $entries)
{
	if (!is_array($parsed) || !isset($parsed['mode']) || !is_array($entries) || count($entries) === 0) return false;
	$prefix = isset($parsed['prefix']) ? (string)$parsed['prefix'] : '';
	$tokens = array();
	if ($parsed['mode'] === 'fixed')
	{
		foreach ($entries as $entry) $tokens[] = intval($entry['pid']) . ':' . intval($entry['count']);
		return $prefix . 'giveitems:' . implode(',', $tokens);
	}
	if ($parsed['mode'] === 'random')
	{
		foreach ($entries as $entry)
		{
			$token = intval($entry['pid']) . ':' . intval($entry['count']) . ':' . intval($entry['denominator']);
			if (!isset($entry['notice_explicit']) || $entry['notice_explicit']) $token .= ':' . intval($entry['notice']);
			$tokens[] = $token;
		}
		return $prefix . 'randitem:' . implode('|', $tokens);
	}
	return false;
}

function adminGiftInitializeParsed($mode)
{
	if ($mode !== 'fixed' && $mode !== 'random') return false;
	return array(
		'supported' => true,
		'status' => 'supported',
		'mode' => $mode,
		'mode_label' => $mode === 'fixed' ? '固定全部开出' : '随机顺序开出',
		'prefix' => '',
		'entries' => array(),
		'errors' => array(),
		'effect' => ''
	);
}

function adminGiftActualProbabilities($entries)
{
	$probabilities = array();
	$survival = 1.0;
	foreach ($entries as $entry)
	{
		$denominator = isset($entry['denominator']) ? intval($entry['denominator']) : 0;
		if ($denominator < 1)
		{
			$probabilities[] = 0;
			continue;
		}
		$probabilities[] = $survival * (100 / $denominator);
		$survival *= ($denominator - 1) / $denominator;
	}
	return $probabilities;
}

function adminGiftProbabilityText($value)
{
	$value = floatval($value);
	if ($value <= 0) return '0%';
	$digits = $value >= 0.01 ? 4 : 8;
	return rtrim(rtrim(number_format($value, $digits, '.', ''), '0'), '.') . '%';
}

function adminGiftSourceTypeLabel($row)
{
	$varyname = is_array($row) && isset($row['varyname']) ? intval($row['varyname']) : 0;
	if ($varyname === 12) return '礼包/宝箱';
	if ($varyname === 22) return '魔法石';
	return '类型 ' . $varyname;
}

function adminGiftLoadProps($db, $ids)
{
	$clean = array();
	foreach ($ids as $id)
	{
		$id = intval($id);
		if ($id > 0) $clean[$id] = $id;
	}
	if (count($clean) === 0) return array();
	$rows = $db->getRecords('SELECT id,name,varyname,propslock FROM props WHERE id IN(' . implode(',', array_values($clean)) . ')');
	$result = array();
	if (is_array($rows)) foreach ($rows as $row) $result[intval($row['id'])] = $row;
	return $result;
}

function adminGiftBackUrl($sourceId, $sourceQuery, $sourceType, $page, $itemQuery, $outputId)
{
	$url = 'gift_items.php?source_id=' . intval($sourceId) . '&q=' . rawurlencode((string)$sourceQuery) .
		'&source_type=' . rawurlencode((string)$sourceType) . '&page=' . max(1, intval($page));
	if ($itemQuery !== '') $url .= '&item_q=' . rawurlencode((string)$itemQuery);
	if (intval($outputId) > 0) $url .= '&output_id=' . intval($outputId);
	return $url;
}
