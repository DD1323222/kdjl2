<?php
class instance{
	private $className = NULL;
	private $x=null;
	function __construct($name)
	{
		$this->instance($name);
	}

	function instance($name)
	{
		$this->className = $name;
	}
	function __call($m, $a){
        if($this->x==null) {
			$this->x = new $this->className();
		}
		return call_user_func_array(array($this->x, $m), $a);
    }
}
?>
