<?php
require_once(dirname(__FILE__).'/legacy_expression.v1.php');
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
		$host = isset($_mem['host']) ? $_mem['host'] : '';
		$port = isset($_mem['port']) ? intval($_mem['port']) : 0;
		$this->handle = new Memcache;	// Init memcache.
		if ($host === '' || $port < 1 || @$this->handle->connect($host, $port) === FALSE)
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
		if (!is_object($this->handle)) return false;
		return $this->handle->getStats();
	}
	// return error info.
	public function getError(){
		return $this->errMsg;
	}

	// Memory close.
	public function memClose(){
		if (!is_object($this->handle)) return false;
		$this->handle->close();
	}

	// Memory add.
	// key,value(no serialize),compressed vary,default is MEMCACHE_COMPRESSED, timeout time,default 0.
	// return TRUE or FALSE;
	public function add($arr){
		if (!is_object($this->handle)) return false;
		return $this->handle->add($arr['k'], serialize($arr['v']), MEMCACHE_COMPRESSED, 0);
	}

	// return TRUE or FALSE;
	public function addnosl($arr){
		if (!is_object($this->handle)) return false;
		return $this->handle->add($arr['k'], $arr['v'], MEMCACHE_COMPRESSED, 0);
	}

	// Memory set.
	// key,value(no serialize),compressed vary,default is MEMCACHE_COMPRESSED, timeout time,default 0.
	// return TRUE or FALSE;
	public function set($arr){
		if (!is_object($this->handle)) return false;
		return $this->handle->set($arr['k'], serialize($arr['v']), MEMCACHE_COMPRESSED, 0);
	}
	//不序列化保存
	public function setArr($arr){
		if (!is_object($this->handle)) return false;
		return $this->handle->set($arr['k'], $arr['v'], MEMCACHE_COMPRESSED, 0);
	}

	//设置过期时间
	public function setexpire($arr,$expire=0){
		if (!is_object($this->handle)) return false;
		return $this->handle->set($arr['k'], serialize($arr['v']), MEMCACHE_COMPRESSED, $expire);
	}

	//加入时不序列化
	public function setns($k,$v,$expire=0){
		if (!is_object($this->handle)) return false;
		return $this->handle->set($k, $v, MEMCACHE_COMPRESSED, $expire);
	}

	//加入时不序列化,不压缩
	public function setnsnc($k,$v){
		if (!is_object($this->handle)) return false;
		return $this->handle->set($k, $v, 0, 0);
	}

	// return TRUE or FALSE;
	public function setnosl($arr){
		if (!is_object($this->handle)) return false;
		return $this->handle->set($arr['k'], $arr['v'], MEMCACHE_COMPRESSED, 0);
	}

	public function rpl($arr){
		if (!is_object($this->handle)) return false;
		return $this->handle->replace($arr['k'], serialize($arr['v']), MEMCACHE_COMPRESSED, 0);
	}

	public function rplnosl($arr){
		if (!is_object($this->handle)) return false;
		return $this->handle->replace($arr['k'], $arr['v'], MEMCACHE_COMPRESSED, 0);
	}
	// Memory get
	// key.is string or array.
	// return FALSE or string.
	public function get($key){
		if (!is_object($this->handle)) return false;
		//$this->countMem($key);
		return $this->handle->get($key);
	}

	private function safeUnserialize($raw){
		if ($raw === false || $raw === null || $raw === '') {
			return false;
		}
		if (!is_string($raw)) {
			return $raw;
		}
		$parsed = @unserialize($raw);
		if ($parsed === false && $raw !== serialize(false)) {
			return false;
		}
		return $parsed;
	}

	private function countMem($key){
		if (!is_object($this->handle)) return false;
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
		if (!is_object($this->handle)) return false;
		return $this->handle->get($key);
	}

	public function replace($arr){
		if (!is_object($this->handle)) return false;
		return $this->handle->replace($arr['k'], serialize($arr['v']), MEMCACHE_COMPRESSED, 0);
	}

	// Memory del,default is 0 of timeout time
	// return FALSE or TRUE;
	public function del($key){
		if (!is_object($this->handle)) return false;
		return $this->handle->delete($key);
	}

	// Clear memory data
	public function clearAll(){
		if (!is_object($this->handle)) return false;
		return $this->handle->flush();
	}

	// Add new data and update memory.
	// @Param: k => v, one record and include auto id.
	public function addArray($arr){
		if(!is_array($arr) || !isset($arr['k']) || !isset($arr['v']) || !is_array($arr['v'])) return false;
		// Get memory
		$now = $this->safeUnserialize($this->get($arr['k']));
		if(!is_array($now)) $now = array();
		$now[] = $arr['v'];
		return $this->set(array('k'=>$arr['k'], 'v'=>$now));
	}

	// Update data and update memory.
	// @Param: k => memory key
	//         wh => where field
    //		   field => replace field.
	// ex: array('k' => '1bag',
	//           'v' => 'eval string';
	// Notice eval string format.
	public function updateArray($arr){
		if(!is_array($arr) || !isset($arr['k']) || !isset($arr['v'])) return false;

		// Get now.
		$now = $this->safeUnserialize($this->get($arr['k']));
		if(!is_array($now)) return false;
		$update = false;
		foreach ($now as $k => $rs)
		{
			if(is_array($rs) && $this->applyRowUpdate($rs, $arr['v']))
			{
				$now[$k] = $rs;
				$update = true;
			}
		}
		//Update to memory.
		if(!$this->set(array('k'=>$arr['k'], 'v'=>$now))) return false;
		// Return for some time
		return $update;
	}

	public function updateArrayd($arr){
		if(!is_array($arr) || !isset($arr['k']) || !isset($arr['v'])) return false;

		// Get now.
		$now = $this->safeUnserialize($this->get($arr['k']));
		if(!is_array($now)) return false;
		$update = false;
		foreach ($now as $k => $rs)
		{
			if(is_array($rs) && $this->applyRowUpdate($rs, $arr['v']))
			{
				$now[$k] = $rs;
				$update = true;
			}
		}

		//Update to memory.
		//echo $arr['k'];
		$this->del($arr['k']);
		if(!$this->set(array('k'=>$arr['k'], 'v'=>$now))) return false;
		// Return for some time
		return $update;
	}

	public function updateArray1($arr){
		if(!is_array($arr) || !isset($arr['k']) || !isset($arr['v'])) return false;

		// Get now.
		$now = $this->safeUnserialize($this->get($arr['k']));
		if(!is_array($now)) return false;
		$update = false;
		foreach ($now as $k => $rs)
		{
			if(is_array($rs) && $this->applyRowUpdate($rs, $arr['v']))
			{
				$now[$k] = $rs;
				$update = true;
			}
		}
		//Update to memory.
		if(!$this->rpl(array('k'=>$arr['k'], 'v'=>$now))) return false;
		// Return for some time
		return $update;
	}
	// return true or false;
	// k,
	// wh,ex: if($rs['uid']=='1' and $rs['pid']==1) $ret =1;
	// return true or false.
	public function dataExists($arr){
		if(!is_array($arr) || !isset($arr['k']) || !isset($arr['v'])) return false;
		$now = $this->safeUnserialize($this->get($arr['k']));
		if(!is_array($now)) return false;
		foreach($now as $rs)
		{
			if(is_array($rs) && $this->rowMatches($rs, $arr['v'])) return true;
		}
		return false;
	}
	// Get find $rs array.
	public function dataGet($arr){
		if(!is_array($arr) || !isset($arr['k']) || !isset($arr['v'])) return false;
		$now = $this->safeUnserialize($this->get($arr['k']));
		if(!is_array($now)) return false;
		foreach($now as $rs)
		{
			if(is_array($rs) && $this->rowMatches($rs, $arr['v'])) return $rs;
		}
		return false;
	}

	// Get find $rs array.
	public function dataGetAll($arr){
		if(!is_array($arr) || !isset($arr['k']) || !isset($arr['v'])) return false;
		$now = $this->safeUnserialize($this->get($arr['k']));
		if(!is_array($now)) return false;
		$r=array();
		foreach($now as $rs)
		{
			if(is_array($rs) && $this->rowMatches($rs, $arr['v'])) $r[]=$rs;
		}
		return $r;
	}

	// @param: k,v
	// @Return: price.
	public function delArray($arr){
		if(!is_array($arr) || !isset($arr['k']) || !isset($arr['v'])) return false;

		// Get now.
		$now = $this->safeUnserialize($this->get($arr['k']));
		if(!is_array($now)) return false;
		$deleted = false;
		foreach ($now as $k => $rs)
		{
			if(is_array($rs) && $this->rowMatches($rs, $arr['v']))
			{
				unset($now[$k]);
				$deleted = true;
			}
		}
		//Update to memory.
		if(!$this->set(array('k'=>$arr['k'], 'v'=>$now))) return false;
		return $deleted;
	}

	private function rowMatches($row, $expression)
	{
		$error = '';
		$matched = KdjlLegacyExpression::matches($row, $expression, $error);
		if($error !== '') $this->errMsg = $error;
		return $matched;
	}

	private function applyRowUpdate(&$row, $expression)
	{
		$error = '';
		$updated = KdjlLegacyExpression::applyUpdate($row, $expression, $error);
		if($error !== '') $this->errMsg = $error;
		return $updated;
	}

	function __destruct(){

	}
}
?>
