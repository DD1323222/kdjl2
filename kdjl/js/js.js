//document.write("<script language=javascript src='/config/client.js'></script>");
// JavaScript Document
	function setTab(name,cursel,n){
	for(i=1;i<=n;i++){
	  var menu=document.getElementById(name+i);
	  var con=document.getElementById("con_"+name+"_"+i);
	  menu.className=i==cursel?"on":"";
	  con.style.display=i==cursel?"block":"none";
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
		document.getElementById('tasktip').style.top=y+"px";
		document.getElementById('tasktip').style.left=x+"px";
		}
	function CloseLogin(){
		document.getElementById('light').style.display='none'; 	
		document.getElementById('tasktip').style.display='none'; 
		}
