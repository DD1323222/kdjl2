function gm(action)
{
	switch (action) {
		case 'JY' :
		case 'YZ' :
		case 'FH' :
		case 'JJ' :
		case 'KC' :
			cmd='#!'+action+wpUserName;
			break;
		case 'AN' :
			if($('cmsg').value.length<10)
			{
				$('cmsg').value='@'+$('cmsg').value;
				$('cmsg').focus();
				return;
			}
			cmd='@'+$('cmsg').value;
			$(menuname).style.display="none";
			break;
		case 'RC':
		case 'OL':
		case 'TR':
			cmd='#!'+action;
			break;
		case 'View' :
			var url='/function/gmViewUser.php?u='+wpUserName;
			break;
	}
	$(menuname).style.display="none";
	sendToActionScript(cmd);
}

var overmenu=false;
var menuname;
var hmto=false;
var _rmaddx=0;
if(document.all){
	var _rmaddy=368;
}
else
{
	var _rmaddy=0;
}
var wpUserName="";

function addrm(name,mname)   
{   
    var o=document.createElement("ul");
	o.id="rmenu";
	//o.style.cssText='display:none; z-index: 222222; position: absolute; background-color: #ffffff; display: block; top: 491px; left: 235px; line-height: 18px; width: 100px; padding-bottom: 4px; padding-left: 4px; padding-right: 4px; padding-top: 4px;';
	o.style.position="absolute";
	o.style.width="100px";
	o.style.zIndex='222222';
	o.style.backgroundColor='#ffffff';
	document.body.appendChild(o);
	
	$(name).oncontextmenu=function(ev){   
        if(document.all) ev=event;     
        return drm(ev);   
    }
	
	//_rmaddx=parseInt($(name).style.left);
	//_rmaddy=parseInt($(name).style.top);
	
    menuname=mname;   
    $(mname).onmouseover=function(){overmenu=true;}
    $(mname).onmouseout =function(){overmenu=false;hide();}   
}

function getMenu(str)
{
	var menu1="<li onclick=\"gm('JY')\">禁言</li>"+
    "<li onclick=\"gm('YZ')\">永久禁言</li>"+
    "<li onclick=\"gm('FH')\">封号</li>"+  
    "<li onclick=\"gm('JJ')\">解禁</li>"+  
    "<li onclick=\"gm('KC')\">踢下线</li>"+  
    "<li onclick=\"gm('View')\">查看信息</li>";
	
    var menu2="<li onclick=\"gm('RC')\">重载配置</li>"+
    "<li onclick=\"gm('AN')\">发公告</li>"+
	"<li onclick=\"gm('OL')\">在线人数</li>"+
	"<li onclick=\"gm('TR')\">流量统计</li>"+
    "<li onclick=\"$(menuname).style.display='none';\">关闭</li>";
	wpUserName = "";
	$(menuname).innerHTML=menu2;
	try{
		var findstr = "javascript:$('cmsg').value='//";
		//str = str.innerHTML;
		if(str.parentNode.name.substr(0,10).toLowerCase()=='javascript'&&str.parentNode.name.indexOf(findstr)!=-1)
		{
			//str = decodeURI(str).replace(findstr,"");
			wpUserName = str.innerHTML;
			$(menuname).innerHTML=menu1+menu2;
		}
	}catch(e){}
}

function drm(ev)   
{
	if(document.all){   
        var x=ev.x;   
        var y=ev.y;
		getMenu(ev.srcElement);
    }else{   
        if(ev.button!=2) return;   
        var x=ev.clientX;   
        var y=ev.clientY;   
		getMenu(ev.target);
    }
    $(menuname).style.left=x+_rmaddx+3+"px";   
    $(menuname).style.top=y+_rmaddy+3+"px";   
    $(menuname).style.display="block";   
    hmto=setTimeout("hide();",2000);   
    return false;   
}

function hide()   
{   
    if(hmto) window.clearTimeout(hmto);   
    if(arguments.length==0){   
        hmto=setTimeout("hide(1);",500);   
    }else if(arguments.length==1){         
        hmto=setTimeout("hide(1,2);",500);   
    }else if(arguments.length==2){   
        if(!overmenu)   
        {   
            $(menuname).style.display="none";
        }else{   
            hmto=setTimeout("hide(1);",300);   
        }   
    }   
}   
//addrm(右键点的id,菜单id)
addrm("chatDiv","rmenu"); 