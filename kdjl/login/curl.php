<?php
function curl_get($url, $port = 0)
{
    if(!function_exists('curl_init')) return false;
    if(!is_string($url) || !preg_match('#^https?://#i', $url)) return false;
    $returntransfer = true;
    $ch = curl_init();

    $options = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => $returntransfer,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
    );
    $port = intval($port);
    if($port > 0 && $port <= 65535) $options[CURLOPT_PORT] = $port;

    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
	$status = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));

    if ($result === false || $status < 200 || $status >= 300) {
        curl_close($ch);
        return false;
    }

    curl_close($ch);
    return $result;
}

function curl_post($url, $data, $port = 0)
{
    if(!function_exists('curl_init')) return false;
    if(!is_string($url) || !preg_match('#^https?://#i', $url)) return false;
    $post = true;
    $returntransfer = true;
    $ch = curl_init();

    $options = array(
        CURLOPT_URL => $url,
        CURLOPT_POST => $post,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_RETURNTRANSFER => $returntransfer,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
    );
    $port = intval($port);
    if($port > 0 && $port <= 65535) $options[CURLOPT_PORT] = $port;

    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
	$status = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));

    if ($result === false || $status < 200 || $status >= 300) {
        curl_close($ch);
        return false;
    }

    curl_close($ch);
    return $result;
}
// function curl_post($url,$data,$port=80)
// {
// 	$post = 1;
// 	$returntransfer = 1;
// 	$ch = curl_init();
// 	$options = array(	CURLOPT_URL => $url,
// 						CURLOPT_PORT => $port,
// 						CURLOPT_POST => $post,
// 						CURLOPT_POSTFIELDS => $data,
// 						CURLOPT_RETURNTRANSFER => $returntransfer,
// 						);
// 	curl_setopt_array($ch, $options);
// 	$result = curl_exec($ch);
// 	curl_close($ch);
// 	return $result;
// }
// function curl_get($url,$port=80)
// {
// 	$post = 1;
// 	$returntransfer = 1;
// 	$header = 0;
// 	$nobody = 0;
// 	$followlocation = 1;

// 	$ch = curl_init();
// 	$options = array(CURLOPT_URL => $url,
// 						CURLOPT_HEADER => $header,
// 						CURLOPT_NOBODY => $nobody,
// 						CURLOPT_PORT => $port,
// 						CURLOPT_POST => 0,
// 						CURLOPT_POSTFIELDS => $request,
// 						CURLOPT_RETURNTRANSFER => $returntransfer,
// 						CURLOPT_FOLLOWLOCATION => $followlocation,
// 						CURLOPT_COOKIEJAR => $cookie_jar,
// 						CURLOPT_COOKIEFILE => $cookie_jar,
// 						CURLOPT_REFERER => $url
// 						);
// 	curl_setopt_array($ch, $options);
// 	$result = curl_exec($ch);
// 	curl_close($ch);
// 	return $result;
// }
?>
