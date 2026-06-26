<?php
/**
@Usage: use some other storage method(mysql or memcache) instead of php sessoin
@Copyright:www.webgame.com.cn
@Version:1.0
*/
class session{
	
	//session data
	private $data;
	//engine,mysql or memcache
	private $engine;
	//php session expire time
	private $sessionexpiredTime;
	//current user's session cookie value
	private $sessionID;
	//session coolie name
	private $sessionCookieName;
	public function session($engineBase=NULL,$engineName='mysql',$storage_name='php_session'){
		try{
			$this->sessionexpiredTime = intval(ini_get("session.cache_expire"))*60;
		}catch(Exception $Exception){
			$this->sessionexpiredTime = 1200;
		}
		try{
			$this->sessionCookieName = ini_get("session.name");
		}catch(Exception $Exception){
			$this->sessionCookieName = 'PHPSESSID';
		}
		
		if(!isset($_COOKIE[$this->sessionCookieName])){
			@session_start();
			$this->sessionID=session_id();
		}else{
			$this->sessionID=$_COOKIE[$this->sessionCookieName];
		}
		$className = $engineName."SessionEngine";
		$this->engine = new $className(
									array(
										  'storage_name'=>$storage_name,//mysql table name or memcahce key which stores data;
										  'expire_time'=>$this->sessionexpiredTime,
										  'data_too_long_instead_value' => '{__DATA IS *$* TO LONG__}'
										  ),
									$this->sessionID,
									&$engineBase
									);
		$this->init();
		$this->loadFromSession();
		$this->engine->refresh();
		$this->engine->cleanup();		
	}
	private function init()
	{
		$this->data = $this->engine->get();
		if(empty($this->data)){
			@session_start();
			if(!empty($_SESSION)){
				$this->data = $_SESSION;
				$this->engine->create(false, $this->data);
			}
			else
			{
				$this->engine->create(false, "");
			}
		}
	}
	public function loadFromSession($flagStartSession = false){
		$flag=false;
		if($flagStartSession){
			@session_start();
		}
		if($_SESSION&&is_array($_SESSION)){
			foreach($_SESSION as $k=>$v){
				if(!isset($this->data[$k])){
					$this->data[$k] = $v;
					$flag=true;
				}
			}
		}
		if($flag){
			$this->engine->set(false, $this->data);
		}
	}
	private function __get($nm)
    {
		if (isset($this->data[$nm])) {
            $r = $this->data[$nm];           
            return $r;
        } 
		else 
		{
            return NULL;
        }
    }
    private function __set($nm, $val)
    {
	   $this->data[$nm] =  $val;
	   $this->engine->set(false, $this->data);
    }
	
	private function __isset($nm)
    {
        return isset($this->data[$nm]);
    }

    private function __unset($nm)
    {
        unset($this->data[$nm]);
		$this->engine->set(false, $this->data);
    }
	
	function __destruct(){
		$this->data = NULL;
		$this->engine->close();
		$this->engine = NULL;
	}
}

interface SessionEngine
{
	/*
	 * set varibles
	 * @param $arr array,array(varible name=>varible value,...)
	 */
	public function setVariable($arr);
	/*
	 * get session value
	 * @param $key string
	 */
    public function get($key="");
	/*
	 * set session value
	 * @param $key string
	 * @param $value string
	 */
	public function set($key="",$value="");
	/*
	 * set session value
	 * @param $key string
	 * @param $value string
	 */
	public function create($key="",$value="");
	/*
	 * update the session's invalid time
	 * @param $key string
	 */
	public function refresh($key="");
	/*
	 * close mysql or memcache connection
	 */
	public function close();
	/*
	 * delete expired sessions
	 */
	public function cleanup();
}

final class mysqlSessionEngine implements SessionEngine{
	private $id="";
	private $storage_name='php_session';
	private $storage_name_slow='php_session_slow';
	private $data_too_long_instead_value = '{__DATA IS ~ TO LONG__}';//if data is longer than $max_session_data_length and you are using mysql 4 or below,insert this value into memery table instead.
	private $expire_time=1200;
	private $max_session_data_length = 2048;
	private $conn;
	private $mysql_version;
	public function mysqlSessionEngine($arr=array(),$key="",&$_conn){
		$this->setVariable($arr);
		$this->id = $key;
		if(empty($this->id)||strlen($this->id)!=32){
			throw new Exception(__FILE__."->".__LINE__.": Session's cookie name can't be empty and it must have just 32 charactors!");
		}
		$this->conn = $_conn;
		if(!$this->conn||!is_resource($this->conn)){
			throw new Exception(__FILE__."->".__LINE__.": Need a mysql connection!");
		}
		$this->mysql_version = $this->getOne("select floor(version())");
		if($this->mysql_version<5){
			$this->max_session_data_length = 255;
		}
	}	
	public function setVariable($arr){
		if(!empty($arr)&&is_array($arr)){
			foreach($arr as $k=>$v){
				$this->$k = $v;				
				if($k=='storage_name'){
					$this->storage_name_slow = $v.'_slow';
				}
			}
		}
	}
	public function get($key=""){
		if($key=="") $key = $this->id;
		$return = $this->getOne('select value from '.$this->storage_name.' where id="'.$key.'"');
		if($return==$this->data_too_long_instead_value)
		{
			$return = $this->getOne('select value from '.$this->storage_name_slow.' where id="'.$key.'"');
		}
		if(!$return)
		{
			$mysqlError = mysql_error($this->conn);
			if(strpos($mysqlError,"doesn't exist")!==false)
			{
				$this->initTable();
			}
			$return = array();
		}
		else
		{
			$return = unserialize($return);
		}
		return $return;
	}
	public function close(){
		@mysql_close($this->conn);
	}
	public function cleanup(){
		if($this->mysql_version>4){
			$sql = 'delete from '.$this->storage_name.' where date_add(`time`,INTERVAL '.$this->expire_time.' SECOND)<CURRENT_TIMESTAMP()';
		}else{
			$sql = 'delete from '.$this->storage_name_slow.' where `time`+'.$this->expire_time.'<unix_timestamp()';
			$this->execute($sql);
			$sql = 'delete from '.$this->storage_name.' where `time`+'.$this->expire_time.'<unix_timestamp()';
		}
		$this->execute($sql);
	}
	public function refresh($key=""){
		if($this->mysql_version>4){
			$sql = 'update '.$this->storage_name.' set `time`=CURRENT_TIMESTAMP() where id="'.$key.'"';
		}else{
			$sql = 'update '.$this->storage_name.' set `time`=unix_timestamp() where id="'.$key.'"';
		}
		$return = $this->execute($sql);
		if(!$return){
			$this->initTable();
			$return = $this->execute($sql,true);
		}
		return $return;
	}
	public function create($key="",$value=""){
		if($key=="") $key = $this->id;
		if($value != "") $value = mysql_real_escape_string(serialize($value),$this->conn);
		if(strlen($value)>$this->max_session_data_length)
		{
			if($this->mysql_version>4){
				throw new Exception(__FILE__."->".__LINE__.": Session data is long than max allow length(".$this->max_session_data_length.")!");
			}
		}
		if($this->mysql_version>4){
			$sql = 'replace into '.$this->storage_name.' set value=\''.$value.'\',id="'.$key.'",`time`=CURRENT_TIMESTAMP()';
		}else{
			$sql = 'replace into '.$this->storage_name.' set value=\''.$value.'\',id="'.$key.'",`time`=unix_timestamp()';
		}
		$return = $this->execute($sql);
		if(!$return){
			$this->initTable();
			$return = $this->execute($sql,true);
		}
		return $return;
	}
	public function set($key="",$value=""){		
		if($key=="") $key = $this->id;
		if($value != "") $value = mysql_real_escape_string(serialize($value),$this->conn);
		$sql = 'update '.$this->storage_name.' set value=\''.$value.'\' where id="'.$key.'"';
		if(strlen($value)>$this->max_session_data_length)
		{
			if($this->mysql_version>4){
				throw new Exception(__FILE__."->".__LINE__.": Session data is long than max allow length(".$this->max_session_data_length.")!");
			}
			$sql = 'replace into '.$this->storage_name_slow.' set value=\''.$value.'\',id="'.$key.'",`time`=unix_timestamp()';
			$this->execute($sql,true);
			$sql = 'update '.$this->storage_name.' set value=\''.$this->data_too_long_instead_value.'\' where id="'.$key.'"';	
		}
		$return = $this->execute($sql);
		if(!$return){
			$this->initTable();
			$return = $this->execute($sql,true);
		}
		return $return;
	}
	private function initTable(){		
		if($this->mysql_version>4){
			$sql = "
				CREATE TABLE if not exists `".$this->storage_name."` (
				  `id` char(32) NOT NULL default 'ERR',
				  `value` VARBINARY(".$this->max_session_data_length.") NULL,
				  `time` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
				  PRIMARY KEY  (`id`),
				  KEY `time` (`time`)
				) ENGINE=MEMORY;
				";
		}else{			
			$sqlSlow = "
				CREATE TABLE if not exists `".$this->storage_name."_slow` (
				  `id` char(32) NOT NULL default 'ERR',
				  `value` text NULL,
				  `time` int(10) not null default '0',
				  PRIMARY KEY  (`id`),
				  KEY `time` (`time`)
				) ENGINE=MyISAM;
				";
			$this->execute($sqlSlow,true);
			
			$sql = "
				CREATE TABLE if not exists `".$this->storage_name."` (
				  `id` char(32) NOT NULL default 'ERR',
				  `value` VARCHAR(255) NULL,
				  `time` int(10) not null default '0',
				  PRIMARY KEY  (`id`),
				  KEY `time` (`time`)
				) ENGINE=MEMORY;
				";
		}
		return $this->execute($sql,true);
	}
	private function execute($sql,$die=false)
	{
		if($die)
		{
			mysql_query($sql,$this->conn) or die("exe Sql error:<br>".mysql_error()."<br>".$sql."<hr>");			
		}
		else
		{
			mysql_query($sql,$this->conn);
			if(mysql_error()){
				return false;
			}else{
				return true;
			}
		}
	}
	private function getOne($sql,$die=false){
		$rs = $this->query($sql,$die);
		if($rs && ($one = mysql_fetch_row($rs)) ){
			return $one[0];
		}else{
			return false;
		}
	}
	private function query($sql,$die=false){
		if($die)
			$rs = mysql_query($sql,$this->conn) or die("query Sql error:<br>".mysql_error()."<br>".$sql."<hr>");
		else
			$rs = mysql_query($sql,$this->conn);
		return $rs;
	}
}

?>