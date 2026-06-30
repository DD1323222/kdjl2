<?php
class socketmsg{
	var $connected=false;
	var $socket=NULL;
	var $conn=NULL;
	var $ip='';
	function __construct(){}
	function connect(){
		global $server_ip,$socket_port;
		$this->dbg=false;
		$this->socket = @socket_create (AF_INET, SOCK_STREAM, SOL_TCP);		  // 创建一个SOCKET
		//if($this->ip=='')  $this->ip=$this->get_real_ip();
		//if($this->ip=='125.69.81.43') $this->dbg=true;
		
		if (!$this->socket){ 
			if($this->dbg)
			{
				echo "socket_create() failed:(".$server_ip.",".$socket_port.")".socket_strerror ($this->socket)."\n";
			}else{
				echo $this->ip.'=ip';
			}
			return false;
		}
		
		//@stream_set_timeout($this->socket, 1);
		socket_set_option($this->socket,SOL_SOCKET, SO_SNDTIMEO,  array(		   "sec"=>3, 		   "usec"=>0  		   )		  );
		socket_set_option($this->socket,SOL_SOCKET, SO_RCVTIMEO,  array(		   "sec"=>3, 		   "usec"=>0  		   )		  );
		socket_set_option($this->socket,SOL_SOCKET,SO_REUSEADDR,1); 
		//echo 'timeout=3';
		$this->conn = @socket_connect ($this->socket, $server_ip, $socket_port);// 建立SOCKET的连接
		if (!$this->conn){
			if($this->dbg)echo "socket_connect() failed:".socket_strerror($this->conn)."\n";
			return false;
		}
		
		$this->connected=true;
	}
	
	function sendMsg($msg,$users=array('__ALL__'))
	{
		if(empty($users)) return;
		global $pwd;
		if(!is_array($users)) $users=array($users);
		$command=chr(1).$pwd.' '.implode(',',$users).'|'.$msg;
		if(!$this->connected) $this->connect();
		@socket_write ($this->socket, $command, strlen ($command));
		$msg = @trim (socket_read ($this->socket, 1024));
		return $msg;		
	}

	function __destruct(){
		if($this->socket)
			socket_close ($this->socket);
	}
	
	function get_real_ip(){
		$ip=false;
	
		if(!empty($_SERVER["HTTP_CLIENT_IP"])){
			$ip = $_SERVER["HTTP_CLIENT_IP"];
		}
		
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
			if ($ip) { 
				array_unshift($ips, $ip); $ip = FALSE; 
			}
			for ($i = 0; $i < count($ips); $i++) {
				if (!preg_match("/^(10|172\.16|192\.168)\./i", $ips[$i])) {
					$ip = $ips[$i];
					break;
				}
			}
		}
		return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
	}
}
?>
