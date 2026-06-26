document.write("<script language=javascript src='/config/client.js'></script>");
// Chat_Tool
function $(element){return document.getElementById(element)?document.getElementById(element):element;}
function initSelect(div, input){
	var lt=false;
	if(div=='select_lt') lt = true;
	div=$(div);
	var on=false;
	var t;
	var ul=div.getElementsByTagName("ul")[0];
	var text=div.getElementsByTagName("span")[0];
	div.onclick=function(){
		clearTimeout(t);
		on=(on)?false:true;
		ul.className=(on)?"hidden":"";};
		div.onmouseover=function(){
		clearTimeout(t);
		on=true;
	}
	div.onmouseout=function(){
		on=false;
		t=setTimeout(function(){ul.className="hidden";}, 1000);
	};
	var a=ul.getElementsByTagName("a");
	for(i=0;i<a.length;i++){
		if(lt)
			a[i].onclick=function(){
						try{thisMovie('socketChatswf').setChatType(this.name);}catch(e){alert(e);}
						on=false;
						ul.className="hidden";
						$(input).value=this.name;
						text.innerHTML=this.innerHTML;						
						return false;
					};
		else
			a[i].onclick=function(){
						on=false;
						ul.className="hidden";
						$(input).value=this.name;
						text.innerHTML=this.innerHTML;						
						return false;
					};
	}
}

function sc(i)
{
	div=$('select_lt');
	on=false;
	$('tknew').value=this.name;
	var ul=div.getElementsByTagName("ul")[0];
	
	var text=div.getElementsByTagName("span")[0];	
	var _this=ul.getElementsByTagName("a")[i];
	text.innerHTML=_this.innerHTML;
	$('cmsg').value=_this.name;
	
	return false;
}
