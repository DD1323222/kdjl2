<?php
function rep($msg)
{
	return htmlspecialchars(
							stripslashes(
								str_replace(
										array('|',','),array('｜','，'),$msg
									)
								)
							, ENT_QUOTES);
}

function sendToSoap($msg){
	$msg=rep($msg);
	#$url='http://192.168.21.243:8081/CustomerCenter/gamewords.do';
	$url='http://cs.webgame.com.cn:81/scc/gamewords.do?test=pm1';
	$t=time();
	$md5=md5($_SESSION['id'].'3'.$t.'http://'.$_SERVER['HTTP_HOST'].'/315sad');
	$params ='<?xml version="1.0"?>
	<methodCall>
	<methodName>message</methodName>
	<params>
	<param>
	<value>
	<string><![CDATA['.$md5.'|'.rep($_SESSION['username']).','.$_SESSION['id'].','.rep($_SESSION['nickname']).',3,'.rep($msg).','.$t.',http://'.$_SERVER['HTTP_HOST'].'/|]]></string>
	</value>
	</param>
	</params>
	</methodCall>';	
	
	//$url = parse_url($url);	
	#if (!$url) return "couldn't parse url";
	if (!isset($url['port'])) { $url['port'] = ""; }
	if (!isset($url['query'])) { $url['query'] = ""; }

	/*
	$fp = fsockopen($url['host'], $url['port'] ? $url['port'] : 80);
	fputs($fp, sprintf("POST %s%s%s HTTP/1.0\r\n", $url['path'], $url['query'] ? "?" : "", $url['query']));
	fputs($fp, "Host: $url[host]\r\n");
	fputs($fp, "text/xml; charset=utf-8\r\n");
	fputs($fp, "Content-length: ".strlen($encoded)."\r\n\r\n");
	
	fputs($fp, "$encoded\n");
	//$line = fgets($fp,1024);
	#return true;
	$results = ""; $inheader = 1;
	while(!feof($fp)) {
		$line = fgets($fp,1024);
		if($_SESSION['username']=='ifree'){
			echo ($line);
		}
		if ($inheader && ($line == "\n" || $line == "\r\n")) {
			$inheader = 0;
		}
		elseif (!$inheader) {
			$results .= $line;
		}
	}
	fclose($fp);
	 */
	$results="";
	#if($_SESSION['username']=='ifree'){
		$curl_handle = curl_init();
		curl_setopt($curl_handle, CURLOPT_URL, $url);
		curl_setopt($curl_handle, CURLOPT_VERBOSE, 1);
		curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
		curl_setopt($curl_handle, CURLOPT_POST, 1);
		curl_setopt($curl_handle, CURLOPT_POSTFIELDS, "$encoded");
		$results = curl_exec($curl_handle) or die("Connection error.");
		curl_close($curl_handle);  
	#	echo $results;
	#}
	return '1';
}
?>

