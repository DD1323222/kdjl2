<?php
require_once(dirname(__FILE__).'/legacy_expression.v1.php');

/**
@Usage: Array class
@Copyright:www.webgame.com.cn
@Version:1.0
*/
class arrays
{
	private $errMsg = '';

	function __construct()
	{
	}

	public function getError()
	{
		return $this->errMsg;
	}

	public function addArray($src, $des)
	{
		if(!is_array($src)) return false;
		if(!is_array($des)) $des = array();
		$des[] = $src;
		return $des;
	}

	public function dataGet($arr, $des)
	{
		if(!is_array($arr) || !isset($arr['v']) || !is_array($des)) return false;
		foreach($des as $rs)
		{
			if(is_array($rs) && $this->rowMatches($rs, $arr['v'])) return $rs;
		}
		return false;
	}

	public function dataGetAll($arr, $des)
	{
		if(!is_array($arr) || !isset($arr['v']) || !is_array($des)) return false;
		$result = array();
		foreach($des as $rs)
		{
			if(is_array($rs) && $this->rowMatches($rs, $arr['v'])) $result[] = $rs;
		}
		return $result;
	}

	private function rowMatches($row, $expression)
	{
		$error = '';
		$matched = KdjlLegacyExpression::matches($row, $expression, $error);
		if($error !== '') $this->errMsg = $error;
		return $matched;
	}

	function __destruct()
	{
	}
}
?>
