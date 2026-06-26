<!DOCTYPE html>
<html lang="en">
<script>
//if(window.external && window.external.browserCheck && window.external.browserCheck()==true)
//{
//}
//else
//{
//	location.href="/error.html"
//}

</script>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"  />
	
	<title>游戏选区</title>
	
<style type="text/css" >

.selServer{
	widht:100%;
	height:130px;
	font-size:40px;
	text-align:center;
	font-weight:bold;
}
.serverList{
	margin-top:40px;
}

  a:link {
    color:#FFE7BA;
    text-decoration:none;
    }
    a:visited {
    color:#FFE7BA;
    text-decoration:none;
    }
    a:hover {
    color:#FF8C00;
    text-decoration:none;
    }
    a:active {
    color:#FFDAB9;
    text-decoration:none;
    }
</style>

</head>

<body background="./bg.gif">

<div class="selServer">
	<div class="serverList">
		
		<a href="http://127.0.0.1:8001/passport/login.php?from=<?php echo $_GET['from']; ?>">口袋1服</a>
		
	</div>
		
</div>

</body>
</html>

