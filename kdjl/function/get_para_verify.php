<?php
function get_para_verify_map($para)
{
	if(!isset($para) || !is_string($para) || $para === '' || strlen($para) > 120) return false;
	// The old byte range was for GBK and rejects valid UTF-8 continuation bytes.
	return preg_match('/^[\x{3400}-\x{4DBF}\x{4E00}-\x{9FFF}]+$/uD', $para) === 1;
}
function get_para_verify_prize($para)
{
	if(!isset($para) || !is_string($para) || $para === '' || strlen($para) > 20) return false;
	return preg_match('/^[0-9]+$/D', $para) === 1;
}
function get_para_verify_title($para)
{
	if(!isset($para) || !is_string($para) || $para === '' || strlen($para) > 64) return false;
	return preg_match('/^[a-zA-Z_]+$/D', $para) === 1;
}
?>
