<?php
require_once("../config/config.game.php");

@session_start();

$_SESSION['manager'] = 1;

$gharr = $_pm['mysql'] -> getOneRecord("SELECT days FROM timeconfig WHERE titles = 'ghcard'");
if(!empty($gharr['days'])){
	$ghflag = $gharr['days'];
}else{
	$ghflag = '0';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>口袋宠物角色选择</title>
<style type="text/css">
body,input,form,img,h1,h2,h3,h4,h5,h6,ul,dl,dd,dt,li,p,center,dd,dl,dt,li,ul,label,p,td{margin:0;padding:0;}
img{border:0 none;}
input{font-family:Arial, Helvetica, sans-serif;font-size:12px}
li{list-style:none}
iframe{border:0 none;}
.clear{clear:both;overflow:hidden;height:0;}
.w{width:1000px;margin-left:auto;margin-right:auto;overflow:hidden}
.clearfix:after {content: "."; display: block; height: 0; clear: both; visibility: hidden; }
.clearfix{zoom:1;}
body{background:url(../images/login/bg.gif) repeat-x}
.layout{margin-left:auto;margin-right:auto;background:url(../images/login/bg.jpg) no-repeat center center;height:600px;}
.wrap table{border-collapse:collapse;width:968px;}
.sb{background:url(../images/login/r05.jpg) no-repeat;height:72px;width:690px;}
.rolebg{background:url(../images/login/role.jpg) no-repeat;width:707px;height:382px;}
.rolebg table{width:707px}
.rolebg a{display:block;width:117px;height:382px;}
.role01 a:hover{background:url(../images/login/role_hover.jpg) no-repeat -1px 0;cursor:pointer;width:118px;height:382px;}
.role03 a:hover{background:url(../images/login/role_hover.jpg) no-repeat -119px 0;cursor:pointer;width:118px;height:382px;}
.role05 a:hover{background:url(../images/login/role_hover.jpg) no-repeat -237px 0;cursor:pointer;width:118px;height:382px;}
.role02 a:hover{background:url(../images/login/role_hover.jpg) no-repeat -354px 0;cursor:pointer;width:118px;height:382px;}
.role04 a:hover{background:url(../images/login/role_hover.jpg) no-repeat -473px 0;cursor:pointer;width:116px;height:382px;}
.role06 a:hover{background:url(../images/login/role_hover.jpg) no-repeat -590px 0;cursor:pointer;width:117px;height:382px;}
.intxt{position:absolute;left:236px;top:-26px;border:#6bb111 1px solid;padding:2px;width:150px;}
.sbtn{position:absolute;left:10px;top:-5px;background:url(../images/login/okbtn.jpg) no-repeat;width:88px;height:52px;cursor:pointer;border:0 none}
.sbb{position:relative;}
.arole01{background:url(../images/login/role_hover.jpg) no-repeat -1px 0;cursor:pointer;width:118px;height:382px;}
.arole03{background:url(../images/login/role_hover.jpg) no-repeat -119px 0;cursor:pointer;width:118px;height:382px;}
.arole05{background:url(../images/login/role_hover.jpg) no-repeat -237px 0;cursor:pointer;width:118px;height:382px;}
.arole02{background:url(../images/login/role_hover.jpg) no-repeat -354px 0;cursor:pointer;width:118px;height:382px;}
.arole04{background:url(../images/login/role_hover.jpg) no-repeat -473px 0;cursor:pointer;width:116px;height:382px;}
.arole06{background:url(../images/login/role_hover.jpg) no-repeat -590px 0;cursor:pointer;width:117px;height:382px;}
.wrap{width:1000px;margin-left:auto;margin-right:auto}
.backbg{display:none;width:100%;height:100%;left:0;position:absolute;z-index:99;background:#000; filter:alpha(opacity=50); -moz-opacity:0.5; opacity:0.5}
.taskinfo{width:431px;color:#237200;z-index:100;position:absolute;}
.tasktop{position:relative;padding:0 20px 0 20px;height:42px;line-height:20px;background:url(../images/taskinfot.gif) no-repeat 0 top;}
.taskmid{padding:15px 20px 10px 20px;background:url(../images/taskinfobg.gif) repeat-y;line-height:20px;}
.taskbot{padding:15px 20px 10px 20px;background:url(../images/taskinfob.gif) no-repeat;height:30px;}
.taskbtn{ text-align:center;padding:5px 0 0 0;}
.close{width:35px;height:35px;position:absolute;right:0;top:5px;cursor:pointer}
.backbg{display:none;width:100%;height:100%;left:0;position:absolute;z-index:99;background:#000; filter:alpha(opacity=50); -moz-opacity:0.5; opacity:0.5}
.tip02{background:url(../images/taskinfobg.gif) repeat-y;padding:0 20px 5px 20px;line-height:20px;color:#f00}
</style>
<script language="javascript" src="../javascript/prototype.js"></script>
<script type="text/javascript">
var flag=false;
var opbak="";
var sex = '';
var head = '';
var user  = '';
var no_word = ['','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','',''];  //限制字符集

function C(obj,id)
{
	if(flag){
		return;
	}
	var op='';
	if(id=='cuser') op='u='+obj.value;
    else op='n='+obj.value;
	var opt = {
    		 method: 'get',
    		 onSuccess: function(t) {
				$(id).innerHTML=t.responseText;
    		 },
    		 on404: function(t) {
    		 },
    		 onFailure: function(t) {
    		 },
    		 asynchronous:true
		}
		var ajax=new Ajax.Request('loginCheck.php?'+op, opt);
}

function nextstep(ids)
{
	//var sid = ids.replace(/[^\d]/g,"")
	var sid = ids.replace(/lr/g,"")
	if(sid < 1 || sid > 6){
		return;
	}
	head = sid;
	if(sid == 1 || sid == 3 || sid == 5){//男
		sex = '1';
	}else{
		sex = '2';
	}
	for(i=1;i<7;i++)
	{
		$("lr"+i).className = "role0"+i;
		//alert($("lr"+i).className)
	}
	$(ids).className = "arole0"+ids.replace(/[^\d]/g,"");
}
function nexta(){
	var user = document.getElementById('bname').value;
	//alert(user);
	for(var i=0;i<no_word.length;i++)
	{
		if(user.indexOf(no_word[i]) != -1)
		{
			alert('昵称中包含特殊字符，请重新输入');
			return;
		}
	}
	var op = 'n='+encodeURIComponent(user);
	var opt = {
    		 method: 'get',
    		 onSuccess: function(t) {
				var result = t.responseText;
				if(result == 'ok' || result == 'OK'){
					if(sex == '' || head == ''){
						sex = 1;
						head = 5;
					}
					opbak='bname='+user+'&sex='+sex+'&head='+head;
					OpenLogin();
					//document.getElementById('reg2').style.display='';
				}else{
					alert('请检查用户名或者验证码!');
					return;
				}
    		 },
    		 on404: function(t) {
    		 },
    		 onFailure: function(t) {
    		 },
    		 asynchronous:true
		}
		var ajax=new Ajax.Request('loginCheck.php?'+op, opt);
}

function reg2(id){
	if(opbak == ""){
		returnreg()
	}else{
		var username = document.getElementById('username').value;
		var pass = document.getElementById('pass').value;
		var rop = opbak+'&bc='+id+'&username='+username+'&pass='+pass;;

		if(!isNaN(username)){
			alert('用户名不能全为数字！');
			return;
		}

		var opt = {
    		 method: 'get',
    		 onSuccess: function(t) {
				var r = t.responseText;
				if (r=='OK1')
				{
					alert('恭喜您，注册成功！点确定将立即进入游戏！');
					document.location.href="login.php";
				}
				else
				{
					alert(r);
					CloseLogin();
				}
    		 },
    		 on404: function(t) {
    		 },
    		 onFailure: function(t) {
    		 },
    		 asynchronous:true
		}

		var ajax=new Ajax.Request('register.php?'+rop, opt);
	}
}

function returnreg()
{
	flag=false;
	window.location.reload();
}


function ghfun(){
var ghflag = "<?=$ghflag?>";
if(ghflag == '0'){
	return false;
}
var gh = $('gh').value;
if(gh!=''){
		var opt = {
			method: 'get',
			onSuccess: function(t) {
				var n = t.responseText;
				if(n == '不好意思，可能是您太倒霉了，没有得到任何奖励！'){
					$('ghtips').innerHTML='激活成功！';
				}
				if(n != '' && n != '不好意思，可能是您太倒霉了，没有得到任何奖励！'){
					$('ghtips').innerHTML=n;
					return false;
				}
			},
			asynchronous:true
		}
		//window.status = '../function/getTaskinfo.php?n='+n+'&t='+tid;
		var ajax=new Ajax.Request('../function/newcard.php?id='+ghflag+'&cardid='+gh+'&pwd=', opt);
	}
}
function unghfun(){
	$('ghtips').innerHTML='';
}
</script>
</head>

<body>
<div class="backbg" id="light"></div>
<div class="layout">
<div class="wrap">
<table width="968">
  <tr>
    <td colspan="3"><img src="../images/login/top.jpg" alt=""  /></td>
    </tr>
  <tr>
    <td><img src="../images/login/r01.jpg" alt="" /></td>
    <td class="rolebg">
    <table height="382">
  <tr>
    <td class="role01" id="lr1" onclick="nextstep(this.id)"><a href="#"></a></td>
    <td class="role03" id="lr3" onclick="nextstep(this.id)"><a href="#"></a></td>
    <td class="arole05" id="lr5" onclick="nextstep(this.id)"><a href="#"></a></td>
    <td class="role02" id="lr2" onclick="nextstep(this.id)"><a href="#"></a></td>
    <td class="role04" id="lr4" onclick="nextstep(this.id)"><a href="#"></a></td>
    <td class="role06" id="lr6" onclick="nextstep(this.id)"><a href="#"></a></td>
  </tr>
</table>    </td>
    <td><img src="../images/login/r02.jpg" alt="" /></td>
  </tr>
  <tr>
    <td><img src="../images/login/r03.jpg" alt="" /></td>
    <td class="sb"><div style="position:absolute; top:540px;" class="sbb">
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	  用户名：<input type="text" style="width:100px;" class="username" name="username"  id="username"  />
	  密码：<input type="password" style="width:100px;" class="pass" name="pass"  id="pass"  />
	  角色名： <input type="text" style="width:120px;" class="bname" name="bname"  id="bname"  />
	  <!--注册码： <input type="text" style="width:60px;" class="reg_code" name="reg_code"  id="reg_code" onblur="validateRegCode()" />-->

      <input  name='button' class='sbtn' type="button"  onclick="nexta();" style="position:absolute;left:580px; top:-10px;" value="" />
<br/><br/><br/>
    </div>
    <div style="position:absolute; font-size:12px; z-index:2; left:809px; top:540px; width:300px;margin-left:10px" align="left"><span id="cname"></span><span id="ghtips"></span></div>
	</td>
    <td><img src="../images/login/r04.jpg" alt="" /></td>
  </tr>
</table>
</div>
</div>

<!--注册码-->
<script>  
function validateRegCode() {  
    var regCodeInput = document.getElementById('reg_code');  
    var regCode = regCodeInput.value.trim(); // 去除首尾空格  
    var errorCodeElement = document.getElementById('reg_code_error');  
  
    // 清空之前的错误信息  
    errorCodeElement.textContent = '';  
  
    // 检查注册码是否为空  
    if (regCode === '') {  
        errorCodeElement.textContent = '注册码不能为空！';  
        regCodeInput.focus(); // 设置焦点到输入框  
        return false;  
    }  
  
    // 检查注册码长度是否满足要求（例如，至少10个字符）  
    if (regCode.length < 10) {  
        errorCodeElement.textContent = '注册码长度至少为10个字符！';  
        regCodeInput.focus();  
        return false;  
    }  

    errorCodeElement.style.display = 'none';  
    return true;  
}  
</script>

<div class="taskinfo" id="tasktip" style="display:none">
    <table width="535" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td width="112"><img src="../images/login/zc06.gif"

width="112" height="47"></td>
    <td background="../images/login/zc07.gif">&nbsp;</td>
     <td width="66"><img src="../images/login/zc08.gif"

width="66" height="47" style="cursor:pointer" onClick="CloseLogin()"></td>
  </tr>
</table>
<table width="535" border="0" align="center" cellpadding="0" cellspacing="0">
 <tr>
    <td width="15"><img src="../images/login/zc16.jpg"

width="15" height="224"></td>
    <td width="94"><img src="../images/login/zc17.jpg"

style="cursor:pointer" width="94" height="224"  onClick="reg2(2)"></td>
    <td width="13"><img src="../images/login/zc18.jpg"

width="13" height="224"></td>
    <td width="92"><img src="../images/login/zc19.jpg"

style="cursor:pointer" width="92" height="224"  onClick="reg2(4)"></td>
    <td width="10"><img src="../images/login/zc20.jpg"

width="10" height="224"></td>
    <td width="92"><img src="../images/login/zc21.jpg"

style="cursor:pointer" width="92" height="224"  onClick="reg2(1)"></td>
    <td width="12"><img src="../images/login/zc22.jpg"

width="12" height="224"></td>
    <td width="93"><img src="../images/login/zc23.jpg"

style="cursor:pointer" width="92" height="224"  onClick="reg2(5)"></td>
    <td width="9"><img src="../images/login/zc24.jpg" width="9"

height="224"></td>
    <td width="93"><img src="../images/login/zc25.jpg"

style="cursor:pointer" width="92" height="224"  onClick="reg2(3)"></td>
    <td><img src="../images/login/zc26.jpg" width="14"

height="224"></td>
  </tr>
</table>
<table width="535" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td width="15"><img src="../images/login/zc09.gif"

width="15" height="26"></td>
    <td width="505"

background="../images/login/zc27.jpg">&nbsp;</td>
    <td width="15"><img src="../images/login/zc10.gif"

width="15" height="26"></td>
  </tr>
</table>

</div>
<script type="text/javascript">
	var dBody = null;
	function getBody(){
		if(!dBody)dBody=(document.compatMode&&document.compatMode.indexOf('CSS')>-1)?document.documentElement:document.body;return dBody;
		}
	function getScrollX(){
		return window.pageXOffset||window.scrollX||getBody().scrollLeft||0;
		}
	function getScrollY(){
		return window.pageYOffset||window.scrollY||getBody().scrollTop||0;
		}
function OpenLogin(){
	var x=document.documentElement.clientWidth/2+getScrollX()-446/2;
	var y=document.documentElement.clientHeight/2+getScrollY()-264/2;
	var sWidth=document.body.scrollWidth;
	var sHeight=document.body.scrollHeight;
	var xHeight=document.documentElement.clientHeight;
	if (sHeight > xHeight)
	{
		var SSS = sHeight
	}
	else
	{
		var SSS = xHeight
	}
	var sHH = document.documentElement.clientHeight;
	document.getElementById('light').style.display='block';
	document.getElementById('light').style.width=sWidth+"px";
	document.getElementById('light').style.height=SSS+"px";
	document.getElementById('tasktip').style.display='block';
	document.getElementById('tasktip').style.display='block';
	document.getElementById('tasktip').style.top=y-150+"px";
	document.getElementById('tasktip').style.left=x+"px";
}
	function CloseLogin(){
		document.getElementById('light').style.display='none';
		document.getElementById('tasktip').style.display='none';
		}

</script>
</body>
</html>

