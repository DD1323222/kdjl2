<?php
/**
@Usage: submit data filter.
@Copyright:www.webgame.com.cn
@Version:1.0
*/
class filter{

	private $on		=	1;
	private $off	=	0;
	private $magicQuotesOn = false;
	private $postEscaped = false;
	private $requestEscaped = false;

	// default construct.
	function __construct(){
		$this->magicQuotesCheck();
	}

	// Check magic for env.
	public function magicQuotesCheck(){
		$this->magicQuotesOn = function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc();
		return $this->magicQuotesOn;
	}

	// Add slashes.
	public function addSlash(&$par){
		if (is_array($par))
		{
			foreach ($par as $k => $v)
			{
				if (is_array($v)) $this->addSlash($par[$k]);
				else $par[$k] = addslashes($v);
			}
		}
		else $par = addslashes($par);
	}

	public function getPost(){
		if (!$this->magicQuotesOn && !$this->postEscaped)
		{
			$this->addSlash($_POST);
			$this->postEscaped = true;
		}
		return $_POST;
	}

	public function getRequest(){
		if (!$this->magicQuotesOn && !$this->requestEscaped)
		{
			$this->addSlash($_REQUEST);
			$this->requestEscaped = true;
		}
		return $_REQUEST;
	}
    // default descrutc.
	function __destruct(){

	}
}
?>
