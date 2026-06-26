<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"  />
	<meta name="viewport" content="user-scalable=no, width=device-width,  initial-scale=0.3/" /> 
	
	<title>口袋宠物</title>
	<script src="../js/jquery-1.2.4a.js"></script>
</head>
<body>
<input type="button" onclick="javascript:window.location='login.php'" value="返回"><br /><br />
<font>输入用户名：</font><input type="text" name="passport" /><br />
<input type="button" id="pass" value="提交" />
</body>
</html>
<script>
$("#pass").click(function()
{
	if($("[name='passport']").val() != '')
	{
		$.ajax( {    
	    url:'mbGate.php', 
	    data:{"passport":encodeURIComponent($("[name='passport']").val())},    
	    type:'post',    
	    cache:false, 
	    dataType:'html',    
	    success:function(data) {
			if(data.substring(0,2) != "OK")
			{    
	        	alert(data);
			}
			else if(data.substring(0,3) == "OK1")
			{
				$("#pass").remove();
				$("[name='passport']").attr("readonly",true);
				alert("你还没有设置密保，请设置");
				var input = $("<br /><font>输入密码：</font><input type='text' name='password' /><br /><br /><font color='red'>※密保问题一经设置不能修改</font><br /><div><font>请输入密保问题：</font><input type='text' name='qu' /><br /><br /><font>请输入密保答案：</font><input type='text' name='an' /><br /><br /><input type='button' onclick='setmb()' id='setmb' value= '设置'></div>");
				$("body").append(input);
				
			}
			else if(data.substring(0,3) == "OK2")
			{
				$("#pass").remove();
				$("[name='passport']").attr("readonly",true);
				var input = $("<br /><div><font>密保问题：</font>"+data.substring(4)+"<br /><br /><font>请输入密保答案：</font><input type='text' name='anS' /><br /><br /><input type='button' id='ref' onclick='setan()' value= '验证'></div>");
				$("body").append(input);
			}
	    },    
	    error : function() {    
	          alert("异常！");    
	     }    
	});
	}
});
function setmb()
{
	if($("[name='qu']").val() != '' && $("[name='an']").val() != ''  )
	{
		$.ajax({    
	    url:'mbGate.php', 
	    data:{"passport":encodeURIComponent($("[name='passport']").val()),"an":encodeURIComponent($("[name='an']").val()),"qu":encodeURIComponent($("[name='qu']").val()),"pass":encodeURIComponent($("[name='password']").val())},    
	    type:'post',    
	    cache:false, 
	    dataType:'html',    
	    success:function(data) {
			if(data.substring(0,2) == "OK")
			{
				alert("设置成功");
				window.location = "login.php";
			}
			else
			{
				alert(data);
			}
	    },    
	    error : function() {    
	          alert("异常！");    
	     }   
		 }); 
	}
	else
	{
		alert("问题与答案不能为空");
	}
}
function setan()
{
	if($("[name='anS']").val() != '' )
	{
		$.ajax({    
	    url:'mbGate.php', 
	    data:{"passport":encodeURIComponent($("[name='passport']").val()),"anS":encodeURIComponent($("[name='anS']").val())},    
	    type:'post',    
	    cache:false, 
	    dataType:'html',    
	    success:function(data) {
		
			if(data.substring(0,2) == "OK")
			{
				$("#ref").remove();
				$("[name='anS']").attr("readonly",true);
				var input = $("<br /><div><font>请输入新密码：</font><input type='text' name='newPass' /><br /><br /><input type='button' onclick='setpass()' value= '设置'></div><br /><div><font>请输入想要修改的名称：</font><input type='text' id='newName' /><br /><br /><input type='button' onclick='setName()' value= '修改名称'></div>");
				$("body").append(input);
			}
			else
			{
				alert(data);
			}
	    },    
	    error : function() {    
	          alert("异常！");    
	     }   
		 }); 
	}
	else
	{
		alert("问题与答案不能为空");
	}
}
function setpass()
{
	if($("[name='newPass']").val() != '' )
	{
		$.ajax({    
	    url:'mbGate.php', 
	    data:{"passport":encodeURIComponent($("[name='passport']").val()),"anS":encodeURIComponent($("[name='anS']").val()),"newPass":encodeURIComponent($("[name='newPass']").val())},    
	    type:'post',    
	    cache:false, 
	    dataType:'html',    
	    success:function(data) {
			if(data.substring(0,2) == "OK")
			{
				alert("设置成功");
				window.location = "login.php";
			}
			else
			{
				alert(data);
			}
	    },    
	    error : function() {    
	          alert("异常！");    
	     }   
		 }); 
	}
}

function setName()
{
    var name = document.getElementById('newName').value;
	if(name != '' )
	{
		$.ajax({    
	    url:'mbNameGate.php', 
	    data:{"passport":encodeURIComponent($("[name='passport']").val()),"anS":encodeURIComponent($("[name='anS']").val()),"newName":name},    
	    type:'post',    
	    cache:false, 
	    dataType:'html',    
	    success:function(data) {
			if(data.substring(0,2) == "OK")
			{
				alert("名称修改成功");
				window.location = "login.php";
			}
			else
			{
				alert(data);
			}
	    },    
	    error : function() {    
	          alert("异常！");    
	     }   
		 }); 
	}else{
	    alert("请输入需要修改的名称！");
	}
}


</script>
