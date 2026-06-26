<?php
/**
@Usage: submit data filter.
@Copyright:www.webgame.com.cn
@Version:1.0
*/
class filter{

	private $on		=	1;
	private $off	=	0;

	// default construct.
	function __construct(){
		$this->magicQuotesCheck();
	}

	// Check magic for env.
	public function magicQuotesCheck(){
		if (@get_magic_quotes_runtime() == $this->off) {
			@set_magic_quotes_runtime($this->on);
		}
	}

	// Add slashes.
	public function addSlash(&$par){
		if (is_array($par))
		{
			foreach ($par as $k => $v)
			{
				if (is_array($v)) $this->addSlash($v);
				else $par[$k] = addslashes($v);
			}
		}
		else $par[$k] = addslashes($par);
	}

	public function getPost(){
		if (@get_magic_quotes_runtime() == $this->off)
		{
			$this->addSlash($_POST);
		}
		return $_POST;
	}

	public function getRequest(){
		if (@get_magic_quotes_runtime() == $this->off)
		{
			$this->addSlash($_REQUEST);
		}
		return $_REQUEST;
	}
    // default descrutc.
	function __destruct(){
	
	}
}
?>