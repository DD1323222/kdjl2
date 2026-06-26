<?php
/**
 * 查询玩家信息
*/
require_once('../config/config.game.php');

?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=uft-8" />
<title>
<?=$cmd['title']?>
</title>
<script language=javascript src='/javascript/prototype.js'></script>
<style>

</style>
<body>
<h1 style='text-align:center;'>查询玩家信息</h1>
<div style="text-align:center;">
    <label >玩家名称：</label>
    <input type="text" id="userName" placeholder="请输入要查询的玩家名称">
    <input type="button" onclick="selectPlayer();" value="查询玩家信息">
</div>
</br>
<div style='text-align:center;margin:auto;'>
    <a href='./adminIndex.php?'>返回</a>
</div>
</body>
</html>


<script>
    function selectPlayer(){
        var name = '';
        name = document.getElementById('userName').value;
        if(name != ''){
            window.location.href = "./selectPlayerQuery.php?name="+name;
        }else{
            alert("请输入需要查询的玩家名称!");
        }
    }
</script>