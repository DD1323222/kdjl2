<?php
/**
@Usage: memory class
@Copyright:www.webgame.com.cn
@Version:1.0
*/
class memory{
	private $handle	=	FALSE;
	
	private $errMsg	=	'';

	function __construct(){
		// In here Add memory  env check.
		$this->memConnect();
	}
	
	// Memory connect
	public function memConnect($retry=0){
		global $_mem;
		$this->handle = new Memcache;	// Init memcache.
		if (@$this->handle->connect($_mem['host'], $_mem['port']) === FALSE)
		{
			if($retry>5){
				if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])){					
					die('服务器忙请重试(mem).');
				}else{
					
					die('服务器忙请重试(mem).<script language="javascript">setTimeout("window.location.reload()",3000);</script>mem error');
				}
				$this->handle = FALSE;
			}else{
				sleep($retry+1);
				$this->memConnect(++$retry);
			}
		}
	}
	
	public function getHandle()
	{
		return $this->handle;
	}
	
	// mem connect status. false or object resouce
	public function getStats()
	{
		return $this->handle->getStats();
	}
	// return error info.
	public function getError(){
		return $this->errMsg;
	}

	// Memory close.
	public function memClose(){
		$this->handle->close();
	}

	// Memory add.
	// key,value(no serialize),compressed vary,default is MEMCACHE_COMPRESSED, timeout time,default 0.
	// return TRUE or FALSE;
	public function add($arr){
		return $this->handle->add($arr['k'], serialize($arr['v']), MEMCACHE_COMPRESSED, 0);
	}
	
	// return TRUE or FALSE;
	public function addnosl($arr){
		return $this->handle->add($arr['k'], $arr['v'], MEMCACHE_COMPRESSED, 0);
	}
	
	// Memory set.
	// key,value(no serialize),compressed vary,default is MEMCACHE_COMPRESSED, timeout time,default 0.
	// return TRUE or FALSE;
	public function set($arr){
		return $this->handle->set($arr['k'], serialize($arr['v']), MEMCACHE_COMPRESSED, 0);
	}
	//不序列化保存
	public function setArr($arr){
		return $this->handle->set($arr['k'], $arr['v'], MEMCACHE_COMPRESSED, 0);
	}

	//设置过期时间
	public function setexpire($arr,$expire=0){
		return $this->handle->set($arr['k'], serialize($arr['v']), MEMCACHE_COMPRESSED, $expire);
	}
	
	//加入时不序列化
	public function setns($k,$v,$expire=0){
		return $this->handle->set($k, $v, MEMCACHE_COMPRESSED, $expire);
	}
	
	//加入时不序列化,不压缩
	public function setnsnc($k,$v){
		return $this->handle->set($k, $v, 0, 0);
	}

	// return TRUE or FALSE;
	public function setnosl($arr){
		return $this->handle->set($arr['k'], $arr['v'], MEMCACHE_COMPRESSED, 0);
	}
	
	public function rpl($arr){
		return $this->handle->replace($arr['k'], serialize($arr['v']), MEMCACHE_COMPRESSED, 0);
	}
	
	public function rplnosl($arr){
		return $this->handle->replace($arr['k'], $arr['v'], MEMCACHE_COMPRESSED, 0);
	}
	// Memory get
	// key.is string or array.
	// return FALSE or string.
	public function get($key){
		//$this->countMem($key);
		return $this->handle->get($key);
	}
	
	private function countMem($key){
		$countM = $this->handle->get('battle_count');
		if($countM == false){
			$countM = array();
		}
		if(isset($countM[$key])){
			$countM[$key] += 1;
		}else{
			$countM[$key] = 1;
		}
		$this->handle->set('battle_count',$countM, MEMCACHE_COMPRESSED, 0);
	}
	
	// Memory get
	// key.is string or array.
	// return FALSE or string.
	public function getnosl($key){
		return $this->handle->get($key);
	}
	
	public function replace($arr){
		return $this->handle->replace($arr['k'], serialize($arr['v']), MEMCACHE_COMPRESSED, 0);
	}

	// Memory del,default is 0 of timeout time
	// return FALSE or TRUE;
	public function del($key){
		return $this->handle->delete($key);
	}
	
	// Clear memory data
	public function clearAll(){
		return $this->handle->flush();
	}
	
	// Add new data and update memory.
	// @Param: k => v, one record and include auto id.
	public function addArray($arr){
		if(!is_array($arr) || !is_array($arr['v'])) return false;
		// Get memory
		$now = unserialize($this->get($arr['k']));
		if(!is_array($now))
		{
			$new = array(0 => $arr['v']);
		}
		else
		{
			$new = array_merge($now, array((count($now)+1) => $arr['v']) );
		}
		$this->set( array('k'=>$arr['k'], 'v'=>$new) );
	}
	
	// Update data and update memory.
	// @Param: k => memory key
	//         wh => where field 
    //		   field => replace field.
	// ex: array('k' => '1bag',
	//           'v' => 'eval string';
	// Notice eval string format.
	public function updateArray($arr){
		if(!is_array($arr)) return false;
		
		// Get now.
		$now = unserialize($this->get($arr['k']));
		if(!is_array($now)) return false;
		$update = false;
		foreach ($now as $k => $rs)
		{
			eval($arr['v']);
			$now[$k] = $rs;
		}
		//Update to memory.
		$this->set( array('k'=>$arr['k'], 'v'=>$now) );
		// Return for some time
		return $update;
	}
	
	public function updateArrayd($arr){
		if(!is_array($arr)) return false;
		
		// Get now.
		$now = unserialize($this->get($arr['k']));
		if(!is_array($now)) return false;
		$update = false;
		foreach ($now as $k => $rs)
		{
			eval($arr['v']);
			$now[$k] = $rs;
		}
		
		//Update to memory.
		//echo $arr['k'];
		$this->del($arr['k']);
		$this->set( array('k'=>$arr['k'], 'v'=>$now) );
		// Return for some time
		return $update;
	}
	
	public function updateArray1($arr){
		if(!is_array($arr)) return false;
		
		// Get now.
		$now = unserialize($this->get($arr['k']));
		if(!is_array($now)) return false;
		$update = false;
		foreach ($now as $k => $rs)
		{
			eval($arr['v']);
			$now[$k] = $rs;
		}
		//Update to memory.
		$this->rpl( array('k'=>$arr['k'], 'v'=>$now) );
		// Return for some time
		return $update;
	}
	// return true or false;
	// k,
	// wh,ex: if($rs['uid']=='1' and $rs['pid']==1) $ret =1;
	// return true or false.
	public function dataExists($arr){
		if(!is_array($arr)) return false;
		$now = unserialize($this->get($arr['k']));
		if(!is_array($now)) return false;
		$ret = 0;

		foreach($now as $k => $rs)
		{
			eval($arr['v']);
			if($ret == 1) return true;
		}
		return false;
	}
	// Get find $rs array.
	public function dataGet($arr){
		if(!is_array($arr)) return false;
		$now = unserialize($this->get($arr['k']));
		if(!is_array($now)) return false;
		$ret = 0;

		foreach($now as $k => $rs)
		{
			eval($arr['v']);
			if(is_array($ret)) return $ret;
		}
		return false;
	}
	
	// Get find $rs array.
	public function dataGetAll($arr){
		if(!is_array($arr)) return false;
		$now = unserialize($this->get($arr['k']));
		if(!is_array($now)) return false;
		$ret = 0;
		$r=array();
		$i=0;
		foreach($now as $k => $rs)
		{
			eval($arr['v']);
			if(is_array($ret)) $r[$i++]=$ret;
			unset($ret);
		}
		return $r;
	}
	
	// @param: k,v
	// @Return: price.
	public function delArray($arr){
		if(!is_array($arr)) return false;
		
		// Get now.
		$now = unserialize($this->get($arr['k']));
		if(!is_array($now)) return false;
		$ret=-1;
		foreach ($now as $k => $rs)
		{
			eval($arr['v']);
			if($ret==-1)
			{
				$now[$k]=$rs;
			}
			else 
			{unset($now[$k]);$ret=-1;}
		}
		//Update to memory.
		$this->set( array('k'=>$arr['k'], 'v'=>$now) );
	}

	function __destruct(){
	
	}
}
?>