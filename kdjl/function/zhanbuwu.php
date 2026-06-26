<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=gb2312" />
<title>魔法屋</title>
<script type="text/javascript" src="/javascript/prototype.js"></script>
</head>
<style>
td,body{font-size:12px}
td{align:center}
</style>
<style type="text/css">
body,div,dl,dt,dd,ul,ol,li,h1,h2,h3,h4,h5,h6,pre,form,fieldset,input,textarea,p,blockquote,th,td,em {padding:0; margin:0; outline:none} 
.task{width:788px;height:319px;background:#f2ebc5; color:#B06A01; font-size:12px;}
.task_left{width:138px; height:319px; float:left;}
.task_right{width:650px; height:319px;float:left; background-image:url(../images/fly_bg.jpg);}
#Layer1 {
	position:absolute;
	width:39px;
	height:17px;
	z-index:1;
	left: 89px;
	top: 281px;
	background-image: url(images/cangku04.jpg);
}
.bodyr_l{width:130px; float:left; color:#630; line-height:20px; padding-top:25px;}
.bodyr_r{width:520px; float:left; padding-top:25px;}

</style>
<script type="text/javascript">
	function setTab(name,cursel,n){
	for(i=1;i<=n;i++){
	  var menu=document.getElementById(name+i);
	  var con=document.getElementById("con_"+name+"_"+i);
	  menu.className=i==cursel?"on":"";
	  con.style.display=i==cursel?"block":"none";
	}
	}
</script>
<script language="javascript">
var SacrificeId=0;
function doSacrifice()
{
	if(!SacrificeId){
		alert('请选择你的魔法石!');
		return;
	}
	var opt = {
			method: 'get',
			onSuccess: function(t) {
						parent.Alert(t.responseText);
			},
		asynchronous:true
	}
	var ajax=new Ajax.Request('/function/usedProps.php?pid='+SacrificeId+'&js', opt);	
}
function doselect(obj)
{
	var objs=document.getElementsByTagName('td');

	for(var i=0;i<objs.length;i++)
	{
		if(objs[i].id!='left_info') {
			objs[i].style.cssText='cursor:pointer;color:#B06A01;opacity: 1; filter : progid:DXImageTransform.Microsoft.Alpha(style=0,opacity=100,finishOpacity=100);';
		}
	}
	obj.style.cssText='cursor:pointer;color:#ff0000;opacity: 0.5; filter : progid:DXImageTransform.Microsoft.Alpha(style=0,opacity=50,finishOpacity=100);';
}
function Sacrifice()
{
	try{
		var opt = {
				method: 'get',
				onSuccess: function(t) {
					data=t.responseText;
					var html='';
					datas=data.split('#|#');
					for(i=0;i<datas.length;i++)
					{
						if(i%6==0)
						{
							html+='<tr>';
						}
						var tmp=datas[i].split('|');
						html+='<td align="center" height="155" style="cursor:pointer; " onmouseout="window.parent.UnTip();" onmouseover="window.parent.showTipEquip(\''+tmp[0]+'&frzb\',1,event);" onclick="SacrificeId='+tmp[0]+';doselect(this)" valign="top"><img src="../images/pai/'+tmp[0]+'.gif" /><br />'+tmp[1].replace("石","")+'</td>';
						if(i%6==5)
						{
							html+='</tr>';
						}
					}
					document.getElementById('zhanbu_card').innerHTML='<table width="480" border="0" cellspacing="0" cellpadding="0">'+html+'</table>';
    		 	},
    	 	asynchronous:true
		}
		var ajax=new Ajax.Request('/function/getBagOfVary.php', opt);	
	}catch(e){
		
	}
}
</script>
<body leftmargin="0" topmargin="0" onload="Sacrifice()">
<div class="task">
  <div class="task_left"><img src="../images/fly.jpg" width="138" height="319" /></div>
	<div id="Layer1">
	  <label></label>
  	</div>
	<div class="task_right">
		<div class="bodyr_l">
       	  <table width="110" border="0" align="center" cellpadding="0" cellspacing="0">
        	  <tr>
        	    <td id="left_info">　　你好，我是魔法屋的芙蕾娅，我可以用魔法给你诠释你所携带的魔法石，每种不同的魔法都有其对应的魔法石，每次使用将会消耗一个魔法石。<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p></td>
      	    </tr>
        	  <tr>
        	    <td height="30" align="center"><img src="../images/fly_cion.jpg" width="82" height="17" style="cursor:pointer" onclick="doSacrifice();" /></td>
      	    </tr>
      	  </table>
		</div>
        <div class="bodyr_r">
       	  <table width="520" border="0" cellspacing="0" cellpadding="0">
        	  <tr>
        	    <td><div style="width:492px; height:290px; overflow-x:hidden;overflow-y:auto;" id="zhanbu_card">


		
	</div></td>
      	    </tr>
      	  </table>
        </div>
	</div>
</div>
</div>

</body>
</html>
