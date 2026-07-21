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
				echo "socket_create() failed:(".$server_ip.",".$socket_port.")".socket_strerror(socket_last_error())."\n";
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
			if($this->dbg)echo "socket_connect() failed:".socket_strerror(socket_last_error($this->socket))."\n";
			if($this->socket)
			{
				@socket_close($this->socket);
				$this->socket = NULL;
			}
			$this->connected=false;
			return false;
		}

		$this->connected=true;
		return true;
	}

	function sendMsg($msg,$users=array('__ALL__'))
	{
		if(empty($users)) return false;
		global $pwd;
		if(!is_array($users)) $users=array($users);
		if(!isset($pwd) || !is_string($pwd) || $pwd === '') return false;
		$safeUsers=array();
		$userCandidates=array();
		foreach($users as $userValue)
		{
			if($userValue === '__ALL__') $userCandidates[]='__ALL__';
			else foreach(explode(',',strval($userValue)) as $userId) $userCandidates[]=trim($userId);
		}
		foreach($userCandidates as $userId)
		{
			if($userId === '__ALL__') $safeUsers['__ALL__']='__ALL__';
			else if(ctype_digit(strval($userId)) && intval($userId)>0) $safeUsers[intval($userId)]=intval($userId);
		}
		if(empty($safeUsers)) return false;
		$command=chr(1).$pwd.' '.implode(',',$safeUsers).'|'.$msg;
		if(!$this->connected && $this->connect() === false) return false;
		if(!$this->connected || !$this->socket) return false;
		$commandLength = strlen($command);
		$writtenTotal = 0;
		while($writtenTotal < $commandLength)
		{
			$written = @socket_write($this->socket, substr($command,$writtenTotal), $commandLength-$writtenTotal);
			if($written === false || $written < 1) return false;
			$writtenTotal += $written;
		}
		$reply = @socket_read ($this->socket, 1024);
		if($reply === false) return false;
		return trim($reply);
	}

	function __destruct(){
		if($this->socket)
			@socket_close ($this->socket);
	}

	function get_real_ip(){
		$ip=false;

		if(!empty($_SERVER["HTTP_CLIENT_IP"]) && filter_var($_SERVER["HTTP_CLIENT_IP"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)){
			$ip = $_SERVER["HTTP_CLIENT_IP"];
		}

		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ips = explode(",", $_SERVER['HTTP_X_FORWARDED_FOR']);
			if ($ip) {
				array_unshift($ips, $ip); $ip = FALSE;
			}
			for ($i = 0; $i < count($ips); $i++) {
				$ipCandidate = trim($ips[$i]);
				if (filter_var($ipCandidate, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && !preg_match("/^(10|172\.(1[6-9]|2[0-9]|3[0-1])|192\.168)\./i", $ipCandidate)) {
					$ip = $ipCandidate;
					break;
				}
			}
		}
		$remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
		return ($ip ? $ip : (filter_var($remoteAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? $remoteAddr : ''));
	}
}
?>
