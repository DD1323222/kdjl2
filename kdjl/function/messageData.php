<?php
	header("Last-Modified: Mon, 20 Apr 2009 09:22:46 GMT");
	//header("Cache-Control: max-age=3600");
	header("ETag: 8a90b0-152-0985ce0e");
	$displayedId=(isset($_COOKIE["displayedMsgId"]) && !is_array($_COOKIE["displayedMsgId"]))?intval($_COOKIE["displayedMsgId"]):0;
	$httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
	if(!preg_match('/^[A-Za-z0-9.-]{1,255}(:[0-9]{1,5})?$/', $httpHost)) $httpHost = '';
	$cookieDomain = preg_replace('/:[0-9]{1,5}$/', '', $httpHost);
	if($cookieDomain !== '') setcookie('displayedMsgId',1239094760,365*24*3600+time(),'/', $cookieDomain);
	else setcookie('displayedMsgId',1239094760,365*24*3600+time(),'/');
	?>
	setTimeout("re();",3000);
	if(typeof(loudSpeaksMsg)=="undefined"){var loudSpeaksMsg={};}
	loudSpeaksMsg={};//覆盖
	try{


			loudSpeaksMsg["1239094760"]="<font color=\"#B48D03\">公告</font>：<font color=\"#ff0000\"><b>请勿相信游戏内玩家发布的元宝销售信息，切勿点击他们发出的木马链接！</b></font>";

	}catch(e){}
