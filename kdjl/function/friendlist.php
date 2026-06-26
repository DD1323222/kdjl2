<?php
header('Content-Type:text/html;charset=gbk');
require_once('../config/config.game.php');
secStart($_pm['mem']);
$user = $_pm['mysql'] -> getOneRecord("SELECT friendlist FROM player WHERE id = {$_SESSION['id']}");

$arr = explode(',',$user['friendlist']);
if(is_array($arr)){
	foreach($arr as $v){
		if(empty($v)){
			continue;
		}
		$friend = '';
		$friend = $_pm['mysql'] -> getOneRecord("SELECT id FROM player WHERE nickname = '$v'");
		if(empty($friend['id'])){
			continue;
		}
		$ftime = time() - 120;
		$flarr = unserialize($_pm['mem'] -> get('last_visit_'.$friend['id']));
		if($flarr < $ftime){//10分钟没有活动的就判断为不在线
			$leftarr[] = $v;
		}else{//在线用红色
			$inarr[] = $v;
		}
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=gbk" />
<title>好友列表</title>
<style>
body,div,dl,dt,dd,ul,ol,li,h1,h2,h3,h4,h5,h6,pre,form,fieldset,input,textarea,p,blockquote,th,td {padding:0; margin:0; outline:none} 
table {border-collapse:collapse; border-spacing:0} 
fieldset,img {border:0} 
address,caption,cite,code,dfn,em,strong,th,var {font-weight:normal; font-style:normal} 
h1,h2,h3,h4,h5,h6 {font-weight:normal; font-size:100%} 
body {font:12px/normal "宋体"; color:#0076ba; overflow-x:hidden;}
a,a:visited {color:#004c6d; text-decoration:none}
a:hover {color:#004c6d; text-decoration:none}
.main {width:160px; margin:auto}
.title {height:43px; background:url(../images/51_new/title.gif)}
.title h2 {float:right; width:127px; height:43px; background:url(../images/51_new/logo.jpg)}
.title h2 a {display:block; height:43px; text-indent:-9999px; overflow:hidden}
.title p {width:238px; height:43px; background:url(../images/51_new/title.jpg)}
.content { position:relative}
.content .bg img {display:block}
ul.list {position:absolute; left:280px; top:187px; width:450px}
ul.list li {float:left; width:138px; height:33px; overflow:hidden; margin:10px 12px 0 0; display:inline; background:url(../images/51_new/li.gif)}
ul.list li a {display:block; height:33px; font-weight:bold; cursor:pointer}
ul.list li span {float:left; margin:11px 0 0 13px; display:inline}
ul.list li em {float:right; width:30px; height:14px; margin:9px 9px 0 0; display:inline; text-align:center; overflow:hidden; line-height:15px; color:#36b6ed; background:url(../images/51_new/em.gif)}
.im {position:absolute; width:157px; height:26px; left:0px;  }
.im_con { position:relative; height:26px; background:url(../images/im_title.gif) no-repeat}
.im_con h2 {padding-left:20px; height:26px; line-height:29px; font-weight:bold; overflow:hidden; cursor:pointer}
.im_list {display:none; bottom:28px; width:157px; background:#000}
.im_list .t {height:23px; background:url(../images/im_t.gif)}
.im_list .t a {float:right; width:16px; height:15px; margin:4px 6px 0 0; display:inline; background:url(../images/51_new/add.gif); text-indent:-9999px; overflow:hidden}
.im_list .b {height:6px; overflow:hidden; background:url(../images/51_new/im_b.gif)}
#ScroLeft li {width:127px; height:26px; margin-top:2px}
#ScroLeft li a {display:block; width:125px; height:24px; padding:1px; line-height:26px; overflow:hidden}
#ScroLeft li a:hover {padding:0; background:#e8f9ff; border:1px solid #d4edff}
#ScroLeft li img {float:left; margin:2px 7px 0 3px; display:inline}
#Scroll{width:157px; height:140px; padding:5px 0; background:url(../images/51_new/im_c.gif)}
#ScroLeft{float:left; height:100%; margin-left:9px; display:inline; overflow:hidden}
#ScroRight{position:relative; float:right; margin-right:5px; display:inline; height:100%; width:7px; background:url(../images/51_new/Scro.gif) center top;overflow:hidden}
#ScroLine{position:absolute; z-index:1; top:0px; left:0px; width:7px; height:19px; background:url(../images/51_new/Scro_on.gif); overflow:hidden; cursor:pointer}
</style>
<script type="text/javascript">
	function OpenList(){
		var fl=parent.document.getElementById('friendlist');
		fl.style.height=parseInt(fl.style.height)=='207px'?'50px':'207px';
		if(document.getElementById('Im_list').style.display=='block'){
			document.getElementById('Im_list').style.display='none';
			fl.style.height='50px';
		}else{
			document.getElementById('Im_list').style.display='block';
			fl.style.height='207px';
		}
		//document.getElementById('Im_list').style.display='block'; 	
		}
	
	var Scrolling=false;
	function $(o){return document.getElementById(o)}
	function ScroMove(){Scrolling=true}
	document.onmousemove=function(e){if(Scrolling==false)return;ScroNow(e)}
	document.onmouseup=function(e){Scrolling=false}
	function ScroNow(event){
		var event=event?event:(window.event?window.event:null);
		var Y=event.clientY-$("Scroll").getBoundingClientRect().top-$("ScroLine").clientHeight/2;
		var H=$("ScroRight").clientHeight-$("ScroLine").clientHeight;
		var SH=Y/H*($("ScroLeft").scrollHeight-$("ScroLeft").clientHeight);
		if (Y<0)Y=0;if (Y>H)Y=H;
		$("ScroLine").style.top=Y+"px";
		$("ScroLeft").scrollTop=SH;
		}
	function ScrollWheel(){
		var Y=$("ScroLeft").scrollTop;
		var H=$("ScroLeft").scrollHeight-$("ScroLeft").clientHeight;
		if (event.wheelDelta >=120){Y=Y-80}else{Y=Y+80}
		if(Y<0)Y=0;if(Y>H)Y=H;
		$("ScroLeft").scrollTop=Y;
		var SH=Y/H*$("ScroRight").clientHeight-$("ScroLine").clientHeight;
		if(SH<0)SH=0;
		$("ScroLine").style.top=SH+"px";
	}
	
	
	
	function goto(url){
		var obj =document.getElementById('form1');
		obj.action = url;
		obj.submit();
	}
</script>
</head>

<body>
<div class="content">
<div class="im">
<div class="im_con" onclick="OpenList()">
<h2>点击查看好友</h2>
<div class="im_list" id="Im_list" onclick="OpenList()">
<div class="t"></div>
<div id="Scroll" onselectstart="return false" onmousewheel="ScrollWheel()"><div id="ScroLeft">
<ul>
	<?php
		if(!is_array($inarr) && !is_array($leftarr)){
			$str = '<li><img src="../images/51_new/face.gif" />您没添加好友</li>';
		}else{
			if(is_array($inarr)){
				foreach($inarr as $v){
					if(empty($v)){
						continue;
					}
					$str .= '<li><a href="javascript:parent.$(\'cmsg\').value=\'/'.$v.' \';void(0)"><img src="../images/51_new/face.gif" />'.$v.'</a></li>';
				}
			}
			if(is_array($leftarr)){
				foreach($leftarr as $v){
					if(empty($v)){
						continue;
					}
					$str .= '<li><a href="javascript:parent.$(\'cmsg\').value=\'/'.$v.' \';void(0)"><img src="../images/51_new/face.gif" /><font color=#999999>'.$v.'</font></a></li>';
				}
			}
		}
		echo $str;
	?>
    <!--<li><a href="javascript:"><img src="../images/51_new/face.gif" />呵呵小飞是</a></li>-->
</ul>
</div><div id="ScroRight" onclick="ScroNow(event)"><div id="ScroLine" OnMouseDown="ScroMove()"></div></div></div>
<div class="b"></div>
</div>

</div>
</div>
</div>
</body>
</html>
