<?php
/**
 * 显示宠物当前技能的倍率、回复量或被动效果。
 */
require_once('../../config/config.game.php');

function skillInfoNumber($value)
{
    $number = floatval($value);
    if(floor($number) == $number) return strval(intval($number));
    return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
}

function skillInfoPassiveEffect($effect)
{
    $effect = trim(strval($effect));
    if($effect === '') return '';

    $labels = array(
        'addhp' => '生命上限',
        'addmp' => '魔法上限',
        'addac' => '攻击',
        'addmc' => '防御',
        'addhits' => '命中',
        'addmiss' => '闪避',
        'dxsh' => '伤害抵销',
        'hitshp' => '伤害吸取',
        'shjs' => '造成伤害'
    );
    $parts = preg_split('/[|,]+/', $effect);
    $texts = array();
    foreach($parts as $part)
    {
        if(!preg_match('/^([a-z]+):(-?[0-9]+(?:\.[0-9]+)?%?)$/i', trim($part), $match)) continue;
        $key = strtolower($match[1]);
        if(!isset($labels[$key])) continue;
        $value = $match[2];
        if($value !== '' && $value[0] !== '-') $value = '+'.$value;
        $texts[] = $labels[$key].' '.$value;
    }
    return implode('；', $texts);
}

function skillInfoLevelValue($values, $level)
{
    $items = explode(',', strval($values));
    if(count($items) === 0) return '';
    $index = max(0, intval($level) - 1);
    if(isset($items[$index])) return trim($items[$index]);
    return trim($items[count($items) - 1]);
}

$bid = (isset($_REQUEST['bid']) && !is_array($_REQUEST['bid'])) ? intval($_REQUEST['bid']) : 0;
$sid = (isset($_REQUEST['sid']) && !is_array($_REQUEST['sid'])) ? intval($_REQUEST['sid']) : 0;
if($bid < 1 || $sid < 1)
{
    echo '技能效果：参数错误';
    exit;
}

$skill = $_pm['mysql']->getOneRecord(
    'SELECT sk.level,sk.vary,sk.value,sk.plus,sk.img,sk.uhp,sk.ump,ss.imgeft AS system_imgeft '.
    'FROM skill sk LEFT JOIN skillsys ss ON ss.id=sk.sid '.
    'WHERE sk.bid='.$bid.' AND sk.sid='.$sid.' LIMIT 1'
);
if(!is_array($skill))
{
    echo '技能效果：未找到技能数据';
    exit;
}

$vary = isset($skill['vary']) ? intval($skill['vary']) : 0;
$plus = isset($skill['plus']) ? trim(strval($skill['plus'])) : '';
$uhp = isset($skill['uhp']) ? intval($skill['uhp']) : 0;
$ump = isset($skill['ump']) ? intval($skill['ump']) : 0;
$effectTexts = array();

if(preg_match('/^hp:(-?[0-9]+(?:\.[0-9]+)?)%$/i', $plus, $match))
{
    $effectTexts[] = '技能倍率：'.skillInfoNumber(100 + floatval($match[1])).'%';
}
else if(preg_match('/^super:([0-9]+(?:\.[0-9]+)?)$/i', $plus, $match))
{
    $effectTexts[] = '固定伤害：敌方生命上限的 '.skillInfoNumber($match[1]).'%（无视防御）';
}
else if($plus !== '')
{
    $effectTexts[] = '附加效果：'.htmlspecialchars($plus, ENT_QUOTES, 'UTF-8');
}

if($vary === 3)
{
    if($uhp < 0) $effectTexts[] = '回复生命：'.abs($uhp);
    else if($uhp > 0) $effectTexts[] = '消耗生命：'.$uhp;
    if($ump < 0) $effectTexts[] = '回复魔法：'.abs($ump);
    else if($ump > 0) $effectTexts[] = '消耗魔法：'.$ump;
}

if($vary === 4)
{
    $passiveText = skillInfoPassiveEffect(isset($skill['img']) ? $skill['img'] : '');
    if($passiveText === '' && isset($skill['system_imgeft']))
    {
        $passiveText = skillInfoPassiveEffect(skillInfoLevelValue($skill['system_imgeft'], $skill['level']));
    }
    $effectTexts[] = $passiveText !== '' ? '被动效果：'.$passiveText : '被动效果：无额外倍率';
}

if(count($effectTexts) === 0)
{
    if($vary === 1) $effectTexts[] = '技能倍率：100%';
    else if($vary === 3) $effectTexts[] = '回复效果：无数值变化';
    else $effectTexts[] = '技能效果：无额外倍率';
}

echo implode('；', $effectTexts);
?>
