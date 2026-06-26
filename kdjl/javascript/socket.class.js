/**
@Usage: comet interface for iframe.
@Version: 1.0.1
@Copyright: www.webgame.com.cn
@Write date: 2008.03.17.
*/

//检查年月日是否是合法日期
function isdate(intYear,intMonth,intDay){ 
	if(isNaN(intYear)||isNaN(intMonth)||isNaN(intDay)) return false;     
	if(intMonth>12||intMonth<1) return false;  
	if ( intDay<1||intDay>31)return false;  
	if((intMonth==4||intMonth==6||intMonth==9||intMonth==11)&&(intDay>30)) return false;  
	if(intMonth==2){  
	if(intDay>29) return false;    
	if((((intYear%100==0)&&(intYear%400!=0))||(intYear%4!=0))&&(intDay>28))return false;  
	}  
	return true;  
}  

//检查身份证是否是正确格式
function checkCard(dcardid) 
{
	var pattern;
	if (dcardid.length==15)
	{
		pattern= /^\d{15}$/;//正则表达式,15位且全是数字
		if (pattern.exec(dcardid)==null)
		{
			alert("15位身份证号码必须为数字！")
			
			return false;
		}
		if (!isdate("19"+dcardid.substring(6,8),dcardid.substring(8,10),dcardid.substring(10,12)))
		{
			alert("身份证号码中所含日期不正确") 
			return false;
		}
	
	}
	else     if (dcardid.length==18)
	{
		pattern= /^\d{17}(\d|x|X)$/;//正则表达式,18位且前17位全是数字，最后一位只能数字,x,X
		if (pattern.exec(dcardid)==null)
		{
			alert("18位身份证号码必须为数字和x！")
			return false;
		}
		if (!isdate(dcardid.substring(6,10),dcardid.substring(10,12),dcardid.substring(12,14)))
		{
			alert("身份证号码中所含日期不正确")  
			return false;
		}
		var strJiaoYan  =[  "1", "0", "X", "9", "8", "7", "6", "5", "4", "3", "2"];
		var intQuan =[7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2, 1];
		var intTemp=0;
		for(i = 0; i < dcardid.length - 1; i++)
			intTemp +=  dcardid.substring(i, i + 1)  * intQuan[i];  
		intTemp %= 11;
		if(dcardid.substring(dcardid.length - 1,dcardid.length).toUpperCase()!=strJiaoYan[intTemp])
		{
			alert("身份证末位验证码失败！")
			return false;
		}
	}
	else
	{
		alert("身份证号长度必须为15或18！")
		return false;
	}
	return true;   
}

function fcminput()
{
	var oj=document.createElement("div");
	oj.id="fcmform";
	oj.style.cssText="position:absolute;width:150px;height:18px;color:#ff0000;border:1px solid #f00;left:845px;top:590px;padding-top:3px;z-index:1000;padding-left:2px;background-color:#CCCCCC;";
	oj.innerHTML='<form id="form1" name="form1" method="post" action="/function/fcminput.php" target="_blank" style="background-color:#006666;border:2px solid #003366; padding:3px">\
请填写身份证号：<input type="text" name="card_no" id="card_no"/>\
<input type="button" name="Submit" value="提交" onclick="if(checkCard($(\'card_no\').value)){form.submit();}" />\
<input type="button" value="关闭" onclick="$(\'fcmform\').style.display=\'none\'">\
</form>';
	document.body.appendChild(oj);
}
 
function fcmlink(){
	try{
		if(!document.getElementById("fcmdiv"))
		{
			var oj=document.createElement("div");
			oj.id="fcmdiv";
			oj.style.cssText="position:absolute;width:150px;height:18px;color:#ff0000;border:1px solid #f00;left:845px;top:605px;padding-top:3px;z-index:1000;padding-left:2px;background-color:#CCCCCC";
			
			if(window.location.host.indexOf('webgame.com.cn')!=-1&&window.location.host!='pmtest.webgame.com.cn')
			{
				oj.innerHTML="<a href='http://passport.webgame.com.cn/com_login.jsp?forward=http://passport.webgame.com.cn/v3/fcminfo.jsp' target='_blank'>请完善防沉迷信息</a>";
			}else{
				oj.innerHTML="<a href='javascript:fcminput();void(0)'>请完善防沉迷信息</a>";
			}
			document.body.appendChild(oj);
		}else{
			if(window.location.host.indexOf('webgame.com.cn')!=-1&&window.location.host!='pmtest.webgame.com.cn'){
				document.getElementById("fcmdiv").innerHTML="<a href='http://passport.webgame.com.cn/com_login.jsp?forward=http://passport.webgame.com.cn/v3/fcminfo.jsp' target='_blank'>请完善防沉迷信息!!</a>";
			}else{
				document.getElementById("fcmdiv").innerHTML="<a href='javascript:fcminput();void(0)'>请完善防沉迷信息!!</a>";
			}
		}
	}
	catch(e)
	{
		//alert(e);
	}
}

function fcmalert(h)
{	
	var o=parent.document.getElementById("fcmdiv");
	omsgfcm=o.innerHTML;
	var msg="你累计在线已满 <strong>"+h+" </strong>小时!";
	o.innerHTML=msg;
	setTimeout('fcmlink()',60000);	
}
CURSOR_HAND='pointor';

//alert(1)
var comet_initialize=false;
var _comet = function(){
  this.connection   = false,
  this.iframediv    = false,
  this.initStatus   = false,
  this.msgHandle    = false,
  
  this.initialize= function() {
	
	if(typeof(arguments[0])!='undefined')
	{
		setTimeout(function(){comet.initialize},1000);
	}
	
	if(comet_initialize) return;
	comet_initialize=true;
    if (navigator.appVersion.indexOf("MSIE") != -1) {
	  var o=document.createElement('iframe');
	  o.src='/function/serverGate.php';
	  o.className='wgframe';
	  o.width='100px';
	  o.height='100px';
	  o.style.display='none';
	  o.id='servergate';
	  document.body.appendChild(o);
	  //alert(document.getElementById('servergate'));
    } else if (navigator.appVersion.indexOf("KHTML") != -1) {

      // for KHTML browsers
      comet.connection = document.createElement('iframe');
      comet.connection.setAttribute('id',     'comet_iframe');
      comet.connection.setAttribute('src',    './function/serverGate.php');
      with (comet.connection.style) {
        position   = "absolute";
        left       = top   = "-100px";
        height     = width = "1px";
        visibility = "hidden";
      }
      document.body.appendChild(comet.connection);

    } else {
    
      // For other browser (Firefox...)
      comet.connection = document.createElement('iframe');
      comet.connection.setAttribute('id',     'comet_iframe');
      with (comet.connection.style) {
        left       = top   = "-100px";
        height     = width = "1px";
        visibility = "hidden";
        display    = 'none';
      }
      comet.iframediv = document.createElement('iframe');
      comet.iframediv.setAttribute('src', './function/serverGate.php');
      comet.connection.appendChild(comet.iframediv);
      document.body.appendChild(comet.connection);
    }
  };

/**
* @Usage: listen msg from server and output to gui of game.
* @Return: void.
* @Notice:
   There have some msg vary for listen, about detail please expore document.
*/  
  this.socketRcvMsg= function(msg) {
      if(comet.initStatus==false) {comet.msgHandle=new messageRecive(msg);comet.initStatus=true;}
	  else {comet.msgHandle.svrMsg=msg;}
	  comet.msgHandle.msgSplit();	
  };

  this.onUnload= function() {
    if (comet.connection) {
      comet.connection = false; // release the iframe to prevent problems with IE when reloading the page
    }
  }
}

comet=new _comet();
setTimeout('comet.initialize();',5000);
try{
	Event.observe(window, "load",   comet.initialize);
	Event.observe(window, "unload", comet.onUnload);
}
catch(e)
{

}
setTimeout(function(){comet.initialize()},5000);