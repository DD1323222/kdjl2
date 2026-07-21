<?php
function rep($msg)
{
	return str_replace(']]>', ']]]]><![CDATA[>', htmlspecialchars(
							stripslashes(
								str_replace(
										array('|',','),array('｜','，'),$msg
									)
								)
							, ENT_QUOTES, 'UTF-8'));
}

function sendToSoap($msg){
	$chatAuditBaseUrl = function_exists('kdjlConfiguredServiceBaseUrl') ? kdjlConfiguredServiceBaseUrl('KDJL_CHAT_AUDIT_BASE_URL') : '';
	if($chatAuditBaseUrl === '' || !function_exists('curl_init')) return '1';
	$requestUrl=$chatAuditBaseUrl.'/scc/gamewords.do?test=pm1';
	$t=time();
	$uid = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
	$username = (isset($_SESSION['username']) && !is_array($_SESSION['username'])) ? $_SESSION['username'] : '';
	$nickname = (isset($_SESSION['nickname']) && !is_array($_SESSION['nickname'])) ? $_SESSION['nickname'] : '';
	$httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
	if(!preg_match('/^[A-Za-z0-9.-]{1,255}(:[0-9]{1,5})?$/', $httpHost)) $httpHost = '';
	$md5=md5($uid.'3'.$t.'http://'.$httpHost.'/315sad');
	$params ='<?xml version="1.0"?>
	<methodCall>
	<methodName>message</methodName>
	<params>
	<param>
	<value>
	<string><![CDATA['.$md5.'|'.rep($username).','.$uid.','.rep($nickname).',3,'.rep($msg).','.$t.',http://'.$httpHost.'/|]]></string>
	</value>
	</param>
	</params>
	</methodCall>';
	$encoded = $params;

	//$url = parse_url($requestUrl);
	#if (!$url) return "couldn't parse url";

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
		curl_setopt($curl_handle, CURLOPT_URL, $requestUrl);
		curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Content-Type: text/xml; charset=utf-8'));
		curl_setopt($curl_handle, CURLOPT_POST, 1);
		curl_setopt($curl_handle, CURLOPT_POSTFIELDS, "$encoded");
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($curl_handle, CURLOPT_TIMEOUT, 3);
		$results = curl_exec($curl_handle);
		curl_close($curl_handle);
	#	echo $results;
	#}
	return '1';
}
?>
