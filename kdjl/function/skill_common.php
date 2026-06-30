<?php
function skillFlowFail($message)
{
	global $_pm;
	$_pm['mysql']->query('ROLLBACK');
	die($message);
}

function skillFlowCommit()
{
	global $_pm;
	if (!$_pm['mysql']->query('COMMIT'))
	{
		$_pm['mysql']->query('ROLLBACK');
		die('0');
	}
}

function skillFlowValue($values, $index)
{
	$parts = explode(',', strval($values));
	while (count($parts) > 1 && $parts[count($parts) - 1] === '') array_pop($parts);
	$index = intval($index);
	if ($index < 0) $index = 0;
	if (isset($parts[$index])) return $parts[$index];
	if (count($parts) > 0) return $parts[count($parts) - 1];
	return '';
}

function skillFlowApplyPermanent($uid, $pet, $effect, &$storedEffect)
{
	global $_pm;
	$storedEffect = $effect;
	$parts = explode(':', strval($effect), 2);
	$fields = array(
		'addmc'=>'mc',
		'addac'=>'ac',
		'addhp'=>'srchp',
		'addmp'=>'srcmp',
		'addhits'=>'hits',
		'addmiss'=>'miss'
	);
	if (!isset($parts[0]) || !isset($fields[$parts[0]])) return true;
	if (!isset($parts[1])) return false;
	$percentText = str_replace('%', '', $parts[1]);
	if (!is_numeric($percentText)) return false;

	$field = $fields[$parts[0]];
	$current = floatval($pet[$field]);
	$newValue = sprintf('%.0f', round($current * floatval($percentText) / 100) + $current);
	$sql = 'UPDATE userbb SET '.$field.'='.$newValue.' WHERE uid='.intval($uid).' AND id='.
		intval($pet['id']).' AND muchang IN (0,1) AND tgflag=0';
	if (!$_pm['mysql']->query($sql)) return false;
	$storedEffect = '0';
	return true;
}
?>
