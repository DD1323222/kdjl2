<?php
/**
@Usage: Array class
@Copyright:www.webgame.com.cn
@Version:1.0
*/
class arrays{

	private $errMsg	=	'';

	function __construct(){
	
	}

	// return error info.
	public function getError(){
		return $this->errMsg;
	}
	
	public function addArray($src, $des){
		if(!is_array($src)) return false;

		if(!is_array($des))
		{
			$des = array(0 => $src);
		}
		else
		{
			$des = array_merge($des, array((count($des)+1) => $src) );
		}
		return $des;
	}
	
	// Get find $rs array.
	public function dataGet($arr, $des){
		if(!is_array($arr) || !is_array($des)) return false;
		
		$ret = 0;
		foreach($des as $k => $rs)
		{
			eval($arr['v']);
			if (is_array($ret)) return $ret;
		}
		return false;
	}
	
	// Get find $rs array.
	public function dataGetAll($arr, $des){
		if(!is_array($arr) || !is_array($des)) return false;

		$ret = 0;
		$r=array();
		$i=0;
		foreach($des as $k => $rs)
		{
			eval($arr['v']);
			if (is_array($ret)) $r[$i++]=$ret;
			unset($ret);
		}
		return $r;
	}

	function __destruct(){
	
	}
}
?>