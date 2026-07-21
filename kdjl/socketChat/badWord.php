<?php
$badArr = array();
$badWordFile = dirname(__FILE__).'/badWord.txt';
$badWordContents = @file_get_contents($badWordFile);
if($badWordContents !== false)
{
	$badArr = preg_split('/\r\n|\r|\n/', $badWordContents, -1, PREG_SPLIT_NO_EMPTY);
	if(!is_array($badArr)) $badArr = array();
	if(isset($badArr[0])) $badArr[0] = preg_replace('/^\xEF\xBB\xBF/', '', $badArr[0]);
}

if(!function_exists('kdjlFindBlockedWord'))
{
	function kdjlFindBlockedWord($value)
	{
		global $badArr;
		$value = (string)$value;
		if(!is_array($badArr)) return false;
		foreach($badArr as $blockedWord)
		{
			$blockedWord = trim((string)$blockedWord);
			if($blockedWord !== '' && strpos($value, $blockedWord) !== false) return $blockedWord;
		}
		return false;
	}
}
?>
