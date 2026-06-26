<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"  />
	<meta name="viewport" content="user-scalable=no, width=device-width,  initial-scale=0.3/" /> 
	
	<title>口袋精灵2</title>
	
	<style type="text/css" >

* {
    margin: 0;
    padding: 0;
}

body {
    font-family: Georgia, serif;
    background: url(bg7.gif) top center no-repeat #c4c4c4;
    background-size: cover ;
    color: #3a3a3a;
}

.clear {
    clear: both;
}

form {
    width: 406px;
    margin: 300px auto 0;
}

legend {
    display: none;
}

fieldset {
    border: 0;
}

label {
	font-weight: bold;
    width: 115px;
    text-align: right;
    float: left;
    margin: 0 10px 0 0;
    padding: 9px 0 0 0;
    font-size: 16px;
}

input {
    width: 220px;
    display: block;
    padding: 4px;
    margin: 0 0 10px 0;
    font-size: 18px;
    color: #3a3a3a;
    font-family: Georgia, serif;
}

input[type=checkbox] {
    width: 20px;
    margin: 0;
    display: inline-block;
}

.button {
	/*background: url(images/button-bg.png) repeat-x top center;*/
    border: 1px solid #999;
    -moz-border-radius: 5px;
    padding: 5px;
    color: black;
    font-weight: bold;
    -webkit-border-radius: 5px;
    font-size: 13px;
    width: 70px;
}

.button:hover {
    background: white;
    color: black;
} 
	
	</style>

<script>
function gotoreg(){
window.location = '../login/reg1.php';
}
</script>

</head>

<body background="bg.gif">
<!--<div id="demo-top-bar">
    <div id="demo-bar-inside">
    	<h2>&nbsp;</h2>
    	<h2>&nbsp;</h2>
        <h2 id="demo-bar-badge">
        	口袋精灵2
        </h2>
        
        <div id="demo-bar-buttons">
        </div>
        
        <div id="demo-bar-ad">
        </div>
    </div>
</div>-->
	<form id="login-form" action="dealPc.php" method="post">
		<fieldset style="position:relative">
		
			<legend>Log in</legend>
			
			<label for="username"><!--用户名--></label>
			<input type="text" id="username" onclick="chose(this)" style=" position:absolute; top:-60px; left:120px" name="username" value="请输入用户名"/>
			<div class="clear"></div>
			
			<label for="password"><!--密码--></label>
			<input type="text" id="password1" onfocus="chose(this)"  value="请输入密码" style=" position:absolute; top:-10px; left:120px" name="password"/>
			<input type="password" id="password"  value="" style="display:none;position:absolute; top:-10px; left:120px" name="password" onkeydown="enterLogin(event)"/>
			<div class="clear"></div>
			<br />
			<div style="position:relative">
			<img src="./regBtn.png" onclick="gotoreg()" style="position:absolute;left:120px" />
			<img src="./loginBtn.png" onclick="checkUser()" style="position:absolute;left:280px" />
			<img src="./mbBtn.png" onclick="javascript:window.location = 'mb.php'" style="position:absolute;left:200px" />
			<!--<input type="submit" style=" position:absolute;left:280px" class="button"
			name="commit2" value="登录"/>-->
			<!--<input type="submit" style="position:absolute;left:200px" class="button"
			name="commit" onclick="window.open('passreset.php')" value="修改密码"/>-->
			</div>
			
		</fieldset>
	</form>
	<script>
function enterLogin(e){
    var evt = window.event || e;
	if(evt.keyCode == 13){
		checkUser();
	}
}

function checkUser()
	{
		var userName = document.getElementById("username").value;
		if(userName=="")
		{
			alert("用户名不能为空");
			return false;
		}

		document.getElementById('login-form').submit();
	}


	function chose(obj)
	{
		obj.value = '';
		obj.onfocus = '';
		if(obj.id == 'password1')
		{
			//obj.style.display = 'none';
			obj.onfocus = "";
			obj.blur();
			document.getElementById('password1').parentNode.removeChild(obj);
			document.getElementById('password').style.display = 'block';
			document.getElementById('password').focus();

		}
		//obj.select();
	}
	</script>
	 <style type="text/css" style="display: none !important;">
	* {
		margin: 0;
		padding: 0;
	}
	body {
		overflow-x: hidden;
	}
	.bsa_it_ad {
		padding: 8px 4px 8px 12px !important;
		position: relative;
		border: 0 !important;
		background: #D6D5D5 !important;
		border-top: 0 !important;
		box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.1);
		font: 11px "Lucida Grande", Sans-Serif !important;
	}
	.bsa_it_ad:before, .bsa_it_ad:after {
		content: "";
		position: absolute;
		top: 0;
		left: 6px;
		width: 100%;
		height: 100%;
		background: #989898;
		border-bottom: 6px solid #989898;
		z-index: -1;
		box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.1);
	}
	.bsa_it_ad:before {
		top: 0;
		left: 12px;
		z-index: -2;
		background: #6C6666;
		border-bottom: 12px solid #6C6666;
	}

	.bsa_it_ad a {
	  margin: 0 !important;
		padding: 0 !important;
	}
	.bsa_it_ad a img {
	  border: 0 !important;
		position: static !important;
	}
	.bsa_it_ad a:hover img {
		margin: 0 !important;
	}
	.bsa_it_ad a:hover {
	  background: none !important;
	}
	.bsa_it_i {
		margin: 0 15px 0 0 !important;
	}
	.bsa_it_t {
		font-size: 14px !important;
		margin: 12px 0 0 0 !important;
	}
	.bsa_it_d {
		padding-right: 10px;
	}
	.bsa_it_p{
		display: none !important;
	}
	#demo-bar-ad {
		width: 416px;
		position: absolute;
		right: 0;
		top: -20px;
		font: 11px "Lucida Grande", Sans-Serif !important;
	}
	#bsap_aplink {
		position: absolute;
		color: #999;
		text-decoration: none;
		bottom: 8px !important;
		right: 8px !important;
		padding: 0 !important;
	}
	.bsa_it_p a:hover {
		background:none !important;
	}
	#demo-top-bar {
		text-align: left;
		background: #e18728;
		position: relative;
		zoom: 1;
		width: 100% !important;
		z-index: 6000;
		box-shadow: 0 0 10px black;
		padding: 20px 0 15px;
	}
	#demo-bar-inside {
		width: 960px;
		margin: 0 auto;
		position: relative;
	}
	#demo-top-bar:before, #demo-top-bar:after {
		content: "";
		position: absolute;
		top: 0;
		left: 6px;
		width: 100%;
		height: 100%;
		background: #e18728;
		border-bottom: 6px solid #8F5314;
		z-index: -1;
		box-shadow: 2px 2px 2px rgba(0, 0, 0, 0.1);
	}
	#demo-top-bar:before {
		top: 0;
		left: 12px;
		background: #6C6666;
		border-bottom: 12px solid #62390E;
	}

	#demo-bar-buttons {
		display: inline-block;
		width: 236px;
		text-align: center;
		vertical-align: top;
		font-size: 0;
	}
	#demo-bar-buttons a {
		font-size: 12px;
		color: white;
		display: block;
		margin: 2px 0;
		text-decoration: none;
		font: 14px "Lucida Grande", Sans-Serif !important;
	}
	#demo-bar-buttons a:hover,
	#demo-bar-buttons a:focus {
		color: #333;
	}

	#demo-bar-badge {
		display: inline-block;
		width: 302px;
		padding: 0 !important;
		margin: 0 !important;
		background-color: transparent !important;
	}
	#demo-bar-badge a {
		display: block;
		width: 100%;
		height: 38px;
		border-radius: 0;
		bottom: auto;
		margin: 0;
		/*background: url(/images/examples-logo.png) no-repeat;*/
		background-size: 100%;
		overflow: hidden;
		text-indent: -9999px;
	}
	#demo-bar-badge:before, #demo-bar-badge:after {
		display: none !important;
	}
</style>

</body>

</html>

