<?php
/**
 * 给玩家派送道具
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
<body style="background-color:#98FB98;">
<h1 style='text-align:center;'>后台管理</h1>
<h2 style='text-align:center;'>派送道具</h2>
<div style="text-align:center;">
    <label >玩家ID：</label>
    <input type="text" id="userID">
    <input type="button" onclick="selectUserName();" value="验证玩家昵称">
    <label id="userName"></label><br><br>
    <label >道具ID：</label>
    <input type="text" id="propID">
    <input type="button" onclick="selectPropName();" value="验证道具名称">
    <label id="propName"></label><br><br>
    <label >数&nbsp;&nbsp;量：</label>
    <input type="text" id="num"><br><br>
    <font size="1px" color="#FF0000">注：请输入需要派送的道具 / 元宝 / 水晶数量！</font><br><br>
    <input type="button" onclick="addPToU();" value="派送道具">&nbsp;&nbsp;
    <input type="button" onclick="addYBToU();" value="派送元宝">&nbsp;&nbsp;
    <input type="button" onclick="addSJToU();" value="派送水晶">&nbsp;&nbsp;
</div>
<div style="text-align:center;">
    <h3><a href='./selectProps.php'>查询道具信息</a></h3>
    <h3><a href='./selectPlayer.php'>查询玩家信息</a></h3>
    <h3><a href='./selectBB.php'>查询宠物ID</a></h3>
    <h3><a href='./selectMerge.php'>查询合成公示表</a></h3>
</div>
</br>
</body>
</html>


<script>
    async function addPToU(site){
        var userID = 0;
        var propID = 0;
        var num = 0;
        userID = document.getElementById('userID').value;
        propID = document.getElementById('propID').value;
        num = document.getElementById('num').value;
        
        if(!isNaN(num) && num.slice(0,1) != ' ' && num != ''){
            if(typeof parseInt(num) === 'number' && parseInt(num) == parseFloat(num)){
                if(num > 0){
                    if(confirm("确定要给该玩家派送道具吗？")){
            			var opt = {
            				method: 'get',
            				onSuccess: function(t) {
            					var ret = parseInt(t.responseText);
            					if (ret == 0) {
            						alert('派送成功!');
            					}else{
            						alert('error!'+ret);
            					}
            				},
            				asynchronous:true        
            			}
            			var ajax=new Ajax.Request('./addPropToUserGate.php?act=addProp&userID='+userID+'&propID='+propID+'&num='+num, opt);
            		}
                }else{
                    alert("请输入大于0的整数！！！");
                }
            }else{
                alert("请输入大于0的整数！！！");
            }
        }else{
            alert("请输入大于0的整数！！！");
        }
    }
    
    async function addYBToU(site){
        var userID = 0;
        var num = 0;
        userID = document.getElementById('userID').value;
        num = document.getElementById('num').value;
        
        if(!isNaN(num) && num.slice(0,1) != ' ' && num != ''){
            if(typeof parseInt(num) === 'number' && parseInt(num) == parseFloat(num)){
                if(num > 0){
                    if(confirm("确定要给该玩家派送元宝吗？")){
                		var opt = {
                			method: 'get',
                			onSuccess: function(t) {
                				var ret = parseInt(t.responseText);
                				if (ret == 0) {
                					alert('派送成功!');
                				}else{
                					alert('error!'+ret);
                				}
                			},
                			asynchronous:true        
                		}
                		var ajax=new Ajax.Request('./addPropToUserGate.php?act=addYB&userID='+userID+'&num='+num, opt);
                	}
                }else{
                    alert("请输入大于0的整数！！！");
                }
            }else{
                alert("请输入大于0的整数！！！");
            }
        }else{
            alert("请输入大于0的整数！！！");
        }
    }
    
    async function addSJToU(site){
        var userID = 0;
        var num = 0;
        userID = document.getElementById('userID').value;
        num = document.getElementById('num').value;
        
        if(!isNaN(num) && num.slice(0,1) != ' ' && num != ''){
            if(typeof parseInt(num) === 'number' && parseInt(num) == parseFloat(num)){
                if(num > 0){
                    if(confirm("确定要给该玩家派送水晶吗？")){
                		var opt = {
                			method: 'get',
                			onSuccess: function(t) {
                				var ret = parseInt(t.responseText);
                				if (ret == 0) {
                					alert('派送成功!');
                				}else{
                					alert('error!'+ret);
                				}
                			},
                			asynchronous:true        
                		}
                		var ajax=new Ajax.Request('./addPropToUserGate.php?act=addSJ&userID='+userID+'&num='+num, opt);
                	}
                }else{
                    alert("请输入大于0的整数！！！");
                }
            }else{
                alert("请输入大于0的整数！！！");
            }
        }else{
            alert("请输入大于0的整数！！！");
        }
    }
    
    function selectUserName(){
        var userID = 0;
        var propID = 0;
        var num = 0;
        userID = document.getElementById('userID').value;
        propID = document.getElementById('propID').value;
        num = document.getElementById('num').value;
        var opt = {
        	method: 'get',
        	onSuccess: function(t) {
        		var ret = t.responseText;
        		if (ret.slice(0,8) == 'username') {
        			document.getElementById('userName').innerHTML = "玩家昵称："+ret.slice(8);
        		}else{
        			alert('error：'+ret+"，你查询的玩家不存在，请输入正确的玩家ID！");
        		}
        	},
        	asynchronous:true        
        }
        var ajax=new Ajax.Request('./addPropToUserGate.php?act=selectUserName&userID='+userID+'&propID='+propID+'&num='+num, opt);
        //document.getElementById('userName').innerHTML = "玩家昵称：";
    }
    
    function selectPropName(){
        var userID = 0;
        var propID = 0;
        var num = 0;
        userID = document.getElementById('userID').value;
        propID = document.getElementById('propID').value;
        num = document.getElementById('num').value;
        var opt = {
        	method: 'get',
        	onSuccess: function(t) {
        		var ret = t.responseText;
        		if (ret.slice(0,8) == 'propname') {
        			document.getElementById('propName').innerHTML = "道具名称："+ret.slice(8);
        		}else{
        			alert('error：'+ret+"，你查询的道具不存在，请输入正确的道具ID！");
        		}
        	},
        	asynchronous:true        
        }
        var ajax=new Ajax.Request('./addPropToUserGate.php?act=selectPropName&userID='+userID+'&propID='+propID+'&num='+num, opt);
    }
</script>