// Chat_Tool
function $(element){
	return typeof element == "string" ? document.getElementById(element) : element;
}
function initSelect(div, input){
	var lt=false;
	if(div=='select_lt') lt = true;
	div=$(div);
	var inputElement=$(input);
	if(!div || !inputElement) return false;
	var on=false;
	var t;
	var ul=div.getElementsByTagName("ul")[0];
	var text=div.getElementsByTagName("span")[0];
	if(!ul || !text) return false;
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
	for(var i=0;i<a.length;i++){
		if(lt)
			a[i].onclick=function(){
						//try{thisMovie('socketChatswf').setChatType(this.name);}catch(e){alert(e);}
						on=false;
						ul.className="hidden";
						inputElement.value=this.name;
						text.innerHTML=this.innerHTML;						
						return false;
					};
		else
			a[i].onclick=function(){
						on=false;
						ul.className="hidden";
						inputElement.value=this.name;
						text.innerHTML=this.innerHTML;						
						return false;
					};
	}
}

function sc(i)
{
	var div=$('select_lt');
	if(!div) return false;
	var ul=div.getElementsByTagName("ul")[0];
	var text=div.getElementsByTagName("span")[0];	
	if(!ul || !text) return false;
	var option=ul.getElementsByTagName("a")[i];
	if(!option) return false;
	var input=$('tknew');
	var message=$('cmsg');
	if(input) input.value=option.name;
	text.innerHTML=option.innerHTML;
	if(message) message.value=option.name;
	
	return false;
}
