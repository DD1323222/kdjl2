var O = function(m){return String.fromCharCode(Math.floor(m/10000)/99);} 
/**
@Recive remote data.
@Usage: Recive remote server data and view.
@Notice: Will update action.
**/
function messageRecive(msg){
	this.svrMsg = msg;
	this.chat=new Chat();
	
	//hn,n,x,y,d,s
	this.msgSplit=function(){
		if(this.svrMsg=='' || typeof this.svrMsg=='undefined') return;
		if(this.svrMsg.toString().indexOf('#loudspeak#')>0){
			var lounds = this.svrMsg.toString().split('#loudspeak#');
			this.svrMsg = lounds[0];
			lounds = lounds[1];
			lounds = lounds.split('#`#');
			if(lounds.length>1||(lounds.length==1&&lounds[0].length>0)){
				loudSpeaksMsg = {'99999999999':lounds[lounds.length-1]};
			}else{
				loudSpeaksMsg = {};
			}			
		}
		var ar = this.svrMsg.toString().split('#msg#');
		//save chat msg.
		if(ar[1]!='' && ar[1]!='undefined') this.chat.msgStr=ar[1];
		this.chat.displayMsg();
		
		//#team##word# ==> ar[0];
		var tm=ar[0].split('#word#');
		if(tm[1]!='' && tm[1]!='undefined') this.displayPlayer(tm[1]);
		var lt=tm[0].split('#team#');
		if(lt[0]!=0) {
			var MSG1 = new CLASS_GAME_MESSAGE("Team",200,120,500,"口袋精灵游戏信息提示：",lt[0]+"邀请您组队!",
				"您已经加入到队伍中！"); 
			MSG1.rect(null,null,null,screen.height-50); 
//			MSG1.speed = 1; 
//			MSG1.step = 1; 
			MSG1.show(); 
			//Update user status.加入显示玩家到组队页面中。
			addPlayer();

		} // team
		if(lt[1]!='' && lt[1]!='undefined') adwords(lt[1]);	
	}
	
	this.displayPlayer=function(msg){
		if(msg == 1)
		{
			var opt = {
				 method: 'get',
				 onSuccess: function(t) {
				 },
				 on404: function(t) {
				 },
				 onFailure: function(t) {
				 },
				 asynchronous:true        
			}
			//var ajax=new Ajax.Request('./function/exit.php', opt);
		}else if (adtimes==false && msg.length>2)
		{
			if (parseInt(msg)==0) return false;
		    // Tips ad info.
			var ar = msg.split('#');
			var par='';
			adurl=ar[1];
			
			if (ar[0].indexOf('.jpg')!=-1 || ar[0].indexOf('.gif')!=-1)
			{
				par="<img src="+IMAGE_SRC_URL+"/ad/"+ar[0]+" border=0 onclick='parent.popad();' style='cursor:pointer;'>";//img
			}
			else{par="<span onclick='parent.popad();' style='cursor:pointer;'>"+ar[0]+"</span>";}
			
			popFlag=false; // recovery pop event
			var MSG1 = new CLASS_GAME_MESSAGE("Team",280,195,2000,'<b>口袋精灵二系统消息</b>',
				                              par,
				                              "&nbsp;"
											 ); 
			MSG1.rect(null,null,null,screen.height-50); 
//			MSG1.speed = 1; 
//			MSG1.step = 1; 
			MSG1.show(); 
			adtimes=true;
		}else if(msg==2)
		{adtimes=false;}
	}

} //end class.

function popad()
{
	if (popFlag==true)
	{
		return;
	}
	var purl=adurl;
	var w=760;
	var h=480;

	var adPopup = window.open('about:blank', '_blank');//,'width='+w+',height='+h+', ...');
	
	if (adPopup==null)
	{
		Event.observe(document.body,'click',popad.bindAsEventListener(adPopup));
	}
	else
	{
		adPopup.blur();
		adPopup.opener.focus();
		adPopup.location = purl;
		popFlag=true;
		Event.stopObserving(document.body,'click');
	}
}

/**
*@ Chat Class
*/
function msgtips(msg){
	ymPrompt.alert({title:'口袋精灵系统消息',message:msg,fixPosition:true,winPos:'rb',showMask:false,width:260,height:180})
}

var swfmsgarr = new Array();
var friendinarr = new Array();
var friendoutarr = new Array();
function Chat(){
	this.msgStr='';
	this.oldmsg='';
	this.msgArr=new Array();
	
	this.sendMsg=function(){	//	send message.,in here add ajax to server connect code.
		var sndMsg = $('cmsg').value;
		var myReg = /(\<+)|(\>+)|(\#+)|(\$+)|(\^+)|(\&+)|(\'+)|(\"+)|(\s{3,})|(\]\[{1,})|(\)\({1,})|(艹)|(妈)|(逼)|(日)|(曰)|(爸)|(爹)|(媽)|(麻痹)|(爺)|(龜)|(爷)|(毛)|(龟)|(崽)|(死)|(屎)|(擦)|(靠)|(傻)|(ｆｕｃｋ)|(ＦＵＣＫ)|(ｓｈｉｔ)|(ＳＨＩＴ)|(fuck)|(FUCK)|(shit)|(SHIT)|(\.+)|(\,+)|(\;+)|(\\n+)|(\\r+)|(\\s+)|(\\t+)|(\{+)|(\\0+)|(\}+)/g;
		sndMsg = sndMsg.replace(myReg,"*");
		var node = sndMsg.split("%");
		var newSndMsg = "";
		for(var i = 0;i < node.length;i++)
		{
			newSndMsg += node[i].replace("%",'');
		}
		if(newSndMsg=='' || newSndMsg=='undefined') {Alert('聊天内容必须输入才能发送噢！');
			return false;
		};
		if(newSndMsg.length > 30) {
			newSndMsg = newSndMsg.substr(0,30);
		};
		
		var opt = {
    		 method: 'get',
    		 onSuccess: function(t) {
				 
    		 },
    		 on404: function(t) {
    		 },
    		 onFailure: function(t) {
    		 },
    		 asynchronous:true
		}
		if(newSndMsg.indexOf("//")==0){
			thisMovie('socketChatswf').setChatType(1);
			sc(1);
			if(parseInt(newSndMsg.substr(2,1))>0&&newSndMsg.indexOf(" ")>0){
				newSndMsg = newSndMsg.substr(0,newSndMsg.indexOf(" ")+41);
			}else{
				newSndMsg = newSndMsg.substr(0,42);
			}
		}
		try{
			$('iframechat').contentWindow.sendToActionScript(newSndMsg);
		}catch(e){}
		try{
			sendToActionScript(newSndMsg);
		}catch(e){}
		var ajax=new Ajax.Request('./function/chatGate.php?msg='+newSndMsg.replace('#!',''), opt);
		$('cmsg').value='';
		//$('buttons').value = '发送中…';
		//$('buttons').disabled= true;
		
	}
	
	
	this.displayMsg=function(){
		if(this.msgStr.length==this.oldmsg.length) return;
		
		
	
		
		//alert(this.msgStr);linend
	    $('chatDiv').innerHTML = this.msgStr.toString().replace(/linendlinend/ig,"<BR>").replace(/linend/ig,"<BR>");
		$('chatDiv').scrollTop = parseInt($('chatDiv').scrollHeight)<400?400:$('chatDiv').scrollHeight;
		this.oldmsg=this.msgStr;
	}
}

function removech(){
	//setTimeout("document.body.removeChild('"+divid+"')",10000);
	document.body.removeChild($('swfmsgs'));
	//$('swfmsgs').style.display="none";
}

function swfshow(swfm){
			var swfmsg = document.createElement('DIV');
			swfmsg.style.cssText='position:absolute;left:340px;top:392px;z-index:100000;width:320px;height:150px;border:none;background-color:transparent;';
			swfmsg.align = "center";
			swfmsg.id='swfmsgs';
			document.body.appendChild(swfmsg);
			var msgarr = swfm.split('%');
			var swftype =  msgarr[1].split('.');
			var swfstr = '';
			if(swftype[1] == 'swf'){
				swfstr='<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,19,0" width="320" height="150"><param name="movie" value="../images/ui/swfmotion/'+msgarr[1]+'"><param name="quality" value="high"><param name="wmode" value="transparent"><embed src="../images/ui/swfmotion/'+msgarr[1]+'" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" width="320" height="150" wmode="transparent"></embed></object>';
			}else{
				swfstr=	'<img src="../images/ui/swfmotion/'+msgarr[1]+'"  align="middle"/>';
			}
			$('swfmsgs').innerHTML=swfstr;
			setTimeout("removech()",10000);
}

// init chat part.
var chatH=new Chat();
var adtimes=false;
var popFlag=false;
var adurl=false;
/**
@Usage: add player info to team gui.
*/
function addPlayer()
{
	var opt = {
    		 method: 'get',
    		 onSuccess: function(t) {
				 var curObj=$('gw').src;
				 if(curObj.toString().indexOf('function/Team_Mod.php')!=-1)
				 {
					var dd = document.all('gw').contentWindow.document;
					dd.getElementById('tmember').innerHTML=t.responseText;
				 }
				 else alert('玩家不在对应地图！');
    		 },
    		 on404: function(t) {
    		 },
    		 onFailure: function(t) {
    		 },
    		 asynchronous:true        
		}
	var ajax=new Ajax.Request('./function/getTeaminfo.php', opt);
}

function loudSpeakVar(msg){
	loudSpeaksMsg = msg
}


var chatDataStr=[];
var smi=null;

function recvMsg(msg1)
{
	if(msg1 == ''){
		return;
	}
	msg1=msg1.replace("http://gimages.webgame.com.cn/poke/","");
	var type = msg1.substring(0,2);
	//去除用户进入和离开的消息
	if(type == 'UL')
	{
		updateForBOl(msg1.substring(3),0);
		//msg1='SI|<font color="#c0c0c0"> '+msg1.substring(3)+' 离开了游戏。</font>';
		return;
		type='SI';
	}else if(type == 'UA'){
		updateForBOl(msg1.substring(3),1);
		//msg1='SI|<font color="#006600"> <span style="cursor:pointer;font-weight:bold" onclick="$(\'cmsg\').value=\'//\'+this.innerHTML+\' \';$(\'cmsg\').focus()">'+msg1.substring(3)+'</span> 加入了游戏。</font>';
		return;
		type='SI';
	}
	else if(type == 'SM')
	{
		document.getElementById('sysstatmsgs').style.display='block';
		document.getElementById('sysstatmsgs').innerHTML = msg1.substring(3);
		window.clearTimeout(smi);
		smi=setTimeout("document.getElementById('sysstatmsgs').style.display='none';",5000);
		return;
	}
	
	//频道
	var len = chatDataStr.length;
	
	//$('swfdbg').value =msg1+"\r\n"+$('swfdbg').value;
	
	//最多显示多少条在这里控制
	if(len > 50){
		var c = chatDataStr.shift();
	}
	chatDataStr.push(msg1);
	showMsg1();
}

function showMsg1(){
	var len = chatDataStr.length;
	var chatData=chatDataStr;
	if(talkType != ''){
		var newData1 = [];
		for(var j = 0;j < len; j++){
			var tt = chatDataStr[j].substring(0,2);
			if(tt == talkType){
				newData1.push(chatDataStr[j]);
			}
		}
		if(newData1.length > 0){
			chatData = newData1;
		}else{
			$('chatDiv').innerHTML = '';
			return;
		}		
	}
	len = chatData.length;
	var newStr = '';
	for(var i = 0;i < len;i++){
		if(typeof(chatData[i]) == "undefined"){
			continue;
		}
		newStr += chatData[i].substring(3)+'<br />';
	}
	$('chatDiv').innerHTML = newStr;
	//滚动条于底部
	$('chatDiv').scrollTop = parseInt($('chatDiv').scrollHeight)<400?400:$('chatDiv').scrollHeight;
}

var talkType = "";

function showSpecialMsg(str){
	talkType = str;
	showMsg1();
	$('chatDiv').scrollTop = parseInt($('chatDiv').scrollHeight)<400?400:$('chatDiv').scrollHeight;
}


function loudSpeakVar(msg){
	loudSpeaksMsg = msg
}
