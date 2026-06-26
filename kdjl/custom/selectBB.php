<?php
/**
 * 查询宠物信息
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
<h1 style='text-align:center;'>查询宠物ID</h1>
<div style="text-align:center;">
    <label >宠物名称：</label>
    <input type="text" id="bbName" placeholder="请输入要查询的宠物名称">
    <input type="button" onclick="selectBB();" value="查询宠物信息">
</div>
</br>
<div style='text-align:center;margin:auto;'>
    <a href='./adminIndex.php?'>返回</a>
</div>
</body>
</html>


<script>
    function selectBB(){
        var name = '';
        name = document.getElementById('bbName').value;
        if(name != ''){
            window.location.href = "./selectBBQuery.php?name="+name;
        }else{
            alert("请输入需要查询的宠物名称!");
        }
    }
</script>