<?php
class instance{
	private $className = NULL;
	private $x=null;
	function instance($name)
	{
		$this->className = $name;
	}
	function __call($m, $a){
        if($this->x==null) {
			$this->x = new $this->className();
		}
		$length = count($a);
		if($length == 0)
			return   call_user_func(array($this->x, $m));
		else if($length == 1)
			return   call_user_func(array($this->x, $m),$a[0]);
		else if($length == 2) 
			return   call_user_func(array($this->x, $m),$a[0],$a[1]);
		else if($length == 3)
			return   call_user_func(array($this->x, $m),$a[0],$a[1],$a[2]);
    }
}
?>