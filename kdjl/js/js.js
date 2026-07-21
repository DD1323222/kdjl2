//document.write("<script language=javascript src='/config/client.js'></script>");
// JavaScript Document
	function setTab(name,cursel,n){
	for(var i=1;i<=n;i++){
	  var menu=document.getElementById(name+i);
	  var con=document.getElementById("con_"+name+"_"+i);
	  if(menu) menu.className=i==cursel?"on":"";
	  if(con) con.style.display=i==cursel?"block":"none";
	}
	}
	
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
		var SSS = sHeight > xHeight ? sHeight : xHeight;
		var light=document.getElementById('light');
		var tasktip=document.getElementById('tasktip');
		if(!light || !tasktip) return false;
		light.style.display='block';
		light.style.width=sWidth+"px";
		light.style.height=SSS+"px";
		tasktip.style.display='block';
		tasktip.style.top=y+"px";
		tasktip.style.left=x+"px";
		}
	function CloseLogin(){
		var light=document.getElementById('light');
		var tasktip=document.getElementById('tasktip');
		if(light) light.style.display='none';
		if(tasktip) tasktip.style.display='none';
		}
