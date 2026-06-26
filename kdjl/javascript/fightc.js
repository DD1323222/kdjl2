// JavaScript Document
// Load img preges
try{ 
		if(typeof window.parent.autoack==false){}
	}catch(e){window.parent.location.reload();}

var imgw = 55;
var waittime=window.parent.waittime; // default;
var wx_type = "";
if (fuser!='') var gpcpath=''+IMAGE_SRC_URL+'/bb/';
else var gpcpath=''+IMAGE_SRC_URL+'/gpc/';

function createLeft(){
	// Team player 0 start.
	var sp = document.createElement('DIV'); //hp font
	sp.style.cssText='position:absolute;left:60px;top:5px;color:#592e10;z-index:2;font-size:0.8em';
	sp.id='bhpf';
	sp.innerHTML=bb[5]+'/'+bb[12];
	$('team0').appendChild(sp);
	
	//create bb hp.
	var initw = parseInt(imgw*bb[5]/bb[12]);
	if(isNaN(initw)) initw=0;
	$('php').style.width=initw+'px';
		
	var msp = document.createElement('DIV');// mp font
	msp.style.cssText='position:absolute;left:60px;top:18px;color:#592e10;z-index:2;font-size:0.8em';
	msp.id='bmpf';
	msp.innerHTML=bb[6]+'/'+bb[13];
	$('team0').appendChild(msp);

	// create bb mp
	var initw = parseInt(imgw*bb[6]/bb[13]);
	$('pmp').style.width=initw+'px';
		
	var msp = document.createElement('DIV');// exp font
	msp.style.cssText='position:absolute;left:60px;top:30px;color:#592e10;z-index:2;font-size:0.8em;padding-left:2px';
	msp.id='pfexp';
	msp.innerHTML=bb[14]+'/'+bb[15];
	$('team0').appendChild(msp);
	
	var initw = parseInt(imgw*bb[14]/bb[15]);
	$('pexp').style.width=initw+'px';

	//-------------- create bb part
	var petsdiv = document.createElement('IMG');
	petsdiv.style.cssText='position:absolute;left:10px;top:120px;';
	petsdiv.id='pimg';
	petsdiv.src=''+IMAGE_SRC_URL+'/bb/'+bb[8];
	$('fm').appendChild(petsdiv);
	var cf = document.createElement('DIV');
	cf.style.cssText='position:absolute;left:100px;top:290px;font-size:12px;text-align:center;color:#fffbdc;';
	cf.id='cf1';
	cf.innerHTML =bb[0]+' '+bb[1]+'级';
	$('fm').appendChild(cf);
	//-------------- create bb part end.

	
	//---------------add bb jn list.
	
	var jnbk = document.createElement('DIV');
	jnbk.style.cssText='position:absolute;left:230px;top:170px;width:312px;height:100px;border:1px solid #144c04;background-color:#047c34;filter:alpha(opacity=60);padding:3px;color:#ffffff;display:none;';
	jnbk.id='jntool';
	$('fm').appendChild(jnbk);

	var sp = document.createElement('DIV'); //hp font
	sp.style.cssText='position:absolute;left:660px;top:62px;color:#592e10;z-index:2;font-size:0.8em;';
	sp.id='ghpf';
	sp.innerHTML=gg[5]+'/'+gg[5];
	$('fm').appendChild(sp);
	
	var hp = document.createElement('IMG'); // hp gif
	hp.style.cssText='width:100px;height:9px;position:absolute;left:640px;top:62px;';
	hp.src=''+IMAGE_SRC_URL+'/ui/fight/zdean15.gif';
	hp.id='ghp';
	$('fm').appendChild(hp);
	
	var lot = document.createElement('div');
	var tempname='';
	var imgpos='';
	lot.style.cssText='position:absolute;left:640px;top:30px;color:#ffffff;z-index:2;font-size:1.0em';
	if (fuser!='') 
	{tempname= fuser+'的';imgpos=" filter: FlipH; -moz-transform: matrix(-1, 0, 0, 1, 0, 0); -webkit-transform: matrix(-1, 0, 0, 1, 0, 0);";}
	if(gg[0]=='要塞怪物')tempname='';
	lot.innerHTML=tempname+' '+gg[0].replace("精英","<font color=blue>精英</font>")+' '+gg[2]+'.LV：'+gg[1];
	$('fm').appendChild(lot);
	
	var ph = document.createElement('IMG'); // gw gif.
	ph.style.cssText='position:absolute;left:530px;top:120px;width:250px;height:180px;'+imgpos;
	ph.src=gpcpath+gg[8];
	ph.id='gyg';
	$('fm').appendChild(ph);
	
	// add right head##########################


	// add jn name.
	var jnfont = document.createElement('DIV');
	jnfont.style.cssText='position:absolute;left:547px;top:150px;font-weight:bold;width:250px;height:30px;font-family:华文新魏;font-size:16px;color:yellow';
	jnfont.id='pfont';
	$('fm').appendChild(jnfont);
	
	// add yao value.
	var yao = document.createElement('DIV');
	yao.style.cssText='position:absolute;z-index:1000;width:130px;display:block;filter:alpha(opacity=0);opacity:0;left:170px;top:120px;font-size:18px;color:#3AE131;font-weight:bold';
	yao.id='yaovid';
	$('fm').appendChild(yao);
}

function Usejn(n){
	var tump=0;
	if(n>1)
	{
		for(var i=0;i<bbjn.lenght;i++)
		{
			var ttarr = bbjn[i];
			if (n==bbjn[9])
			{
				tump=ttarr[8];break;
			}
		}
	}
	n=parseInt(n);
	if ( n!=1 && (tump>bbmcur || bbmcur==0 ))
	{n=1;}//alert('您的魔法值不足，无法使用魔法技能！');return;}
	
	if(using==true) return;
 	using=true;
		
	window.clearTimeout(readH);
	$('timev').innerHTML='PK';
	
	$('tooldiv').style.display='none';
	if(n!=1) gimg='s';
	else gimg='g';
	
	//Move pets and view jn name.
	
	$('pimg').src=''+IMAGE_SRC_URL+'/bb/'+bb[8].replace('z',gimg);
	
	// Get ack by jn.
	getAckOfBB(n);
}

function font(str,fun){
	$('pfont').innerHTML=str;
	window.setTimeout(fun,2000);
}

function fontHide(str){ // Pets return stand position.
	switch (wx_type)
	{
		case "1" :
		{
			str += "<font color=#CC0033>抗</font>";
			break;
		}
		case "2" :
		{
			str += "<font color=#CC99FF>加深</font>";
			break;
		}
		case "3" :
		{
			str +="<font color=blue>减免</font>";
		}
	}
	$('pfont').innerHTML='';
	$('pimg').style.left='10px';
	$('pimg').src=''+IMAGE_SRC_URL+'/bb/'+bb[8];
	if(fc==-1)
	{
		fatEnd();return;
	}
	window.setTimeout("gwF('"+str+"');", 1000);
}

function gwF(str){
	var gwr = str.split(',');
	if( gwr[1] == '0' )
	{
		gwr[1] = 'miss';
		var fatValue='<font color=red><i>'+gwr[1]+'</i> </font>!';
	}
	else
	{
		var fatValue='<font color=red><i>-'+gwr[1]+'</i> </font>!!';
	}
	var ag='g';
	if (gwr[0]!='普通攻击')
	{
		ag='s';
	}

	$('gyg').style.left='120px';
	$('gyg').src=gpcpath+gg[8].replace('z',ag);
	
	$('pfont').style.left='50px';
	
	if(gwr[2].indexOf('<dx>')!=-1)
	{
		var tmp1=gwr[2].split('<dx>');
		gwr[2]	= tmp1[0];
		fatValue+= '<br/>'+tmp1[1];
	}
	// view jn.
	font(gwr[2]+fatValue,"fontgHide();");

//#################################
	hpimg('php');
//###############################
	window.setTimeout("fontgHide();",2000);
}

function fontgHide(){
	$('gyg').style.left='547px';
	$('gyg').src=gpcpath+gg[8];
	
	$('pfont').innerHTML='';
	using=false;
	//一回合结束。
	if(fc<0) {fatEnd();return;}
	fc++;
	loadtime(waittime);	
}

function displayResult(str){
   // add exp.
   if(str.indexOf('经验：')!=-1)
   {
		var lstr = str.split('经验：');
		var lnum = lstr[1].split('<br/>');
		bb[14]=parseInt(bb[14])+parseInt(lnum[0]);
		if (bb[14]>bb[15])
		{
			bb[14] = bb[14]-parseInt(bb[14]);
		}
	   var initw =55*bb[14]/bb[15];
	   $('pfexp').innerHTML=bb[14]+'/'+bb[15];
	   $('pexp').style.width=initw+'px';
	   //explode_start(2);
	}
	else if (str.indexOf('严重伤害')!=-1)
	{
		//explode_start(1);
	}
	if(!guildFight&&typeof(fortressFight)=='undefined'){
		$('result').innerHTML=str+' <BR/>'+
		' <span onclick="window.parent.$(\'gw\').src=\'/function/Team_Mod.php?n='+inmap+'\';" style="cursor:pointer;"><b>返回村庄</b></span>';
	}else if(typeof(fortressFight)!='undefined'){
		$('result').innerHTML=str+' <BR/>'+
		' <span onclick="this.style.display=\'none\';window.parent.$(\'gw\').src=\'/function/fortressCard_Mod.php\';" style="cursor:pointer;"><b>返回要塞</b></span>';
		setTimeout('window.location="/function/fortressCard_Mod.php"',5000);
	}else{
		$('result').innerHTML=str+' <BR/>'+
		' <span onclick="window.parent.$(\'gw\').src=\'/function/guild_battle_mod.php\';" style="cursor:pointer;"><b>返回家族</b></span> <span onclick="window.location=\'/function/Challenge_Mod.php?guild_fight=1\'" style="cursor:pointer"><strong>继续战斗</strong></span>';
	}
	$('result').style.display='';
	$('timev').innerHTML='KO';
	using=true;
	if(window.parent.autoack==true){
		window.setTimeout("auto();",1000);
	}
}
function auto()
{
	if(!guildFight)
	{
		window.parent.$('gw').src='./function/Fight_Mod.php?p='+petsid;
	}else{
		
	}
}

function loadtime(m){
	if(using==true){
		window.clearTimeout(readH);
		return;
	}
	
	$('tooldiv').style.display='';
	$('timev').innerHTML = m--;
	if(m==-1) 
	{	
		window.clearTimeout(readH);
		Usejn(window.parent.usejn);
		return;
	}
	else{
		readH=window.setTimeout("loadtime("+m+");", 1000);
	}
}
createLeft();
loadtime(waittime);

// Server part.###########################
// @Get bb ack.
// @Now, for demo, simple test in localhost.
function getAckOfBB(id){
	var opt = {
    		 method: 'get',
    		 onSuccess: function(t){
			 	if(t.responseText == 0 || t.responseText=='') return;
			 	else {splits(t.responseText);}
    		 },
    		 on404: function(t) {
    		 },
    		 onFailure: function(t) {
    		 },
    		 asynchronous:true        
		}
			//window.status='../function/ChallengeGate.php?id='+id+'&g='+gg[11];
	var ajax=new Ajax.Request('../function/ChallengeGate.php?id='+id+'&g='+gg[11]+(guildFight?'&guildFight=1':''), opt);
}

// Split server info.
function splits(str)
{
	var crit = str.split('*');
	str = crit[0];
	wx_type = crit[2];
	var ackstr = "";
	if(str.indexOf("#<tgtend>") != -1){
		tgtend = 1;
		if(str.indexOf("<ack>") != -1)
		{
			var ackstr2 = str.split("#<tgtend>");
			ackstr = ackstr2[0].split("<ack>");
		}
	}
	else if(str.indexOf("#challengeend") != -1)
	{
		challengeend = 1;
		if(str.indexOf("<ack>") != -1)
		{
			var ackstr1 = str.split("#challengeend");
			ackstr = ackstr1[0].split("<ack>");
		}
	}else{
		if(str.indexOf("<ack>") != -1)
		{
			ackstr = str.split("<ack>");
		}
	}
	
	mMonsterFighting = false;
	//alert(str)
	if(str == 'autoend') {autoFitStart(2);return;}
	var tt = str.split('#');
	if(str.indexOf("逃跑了！！！")!=-1){
		displayResult(tt[2]);
	}
	var bbr = tt[0].split(',');
	if(bbr[2] == '0')
	{
		bbr[2] = 'miss';
	}
	var useInfo = tt[4].split(',');
	//alert(bbr[4]);
	
	//得到吸血和吸魔的数据
	var hpinfo = "";
	var mpinfo = "";
	if(bbr[4] != null)
	{
		if(bbr[4].indexOf('吸血') != -1)
		{
			var infos = bbr[4].split("==");
			hpinfo = infos[0];
			mpinfo = infos[1];
		}
		else if(bbr[4].indexOf('吸魔')!= -1)
		{
			mpinfo = bbr[4];	
		}
	}
	
    //结束
	
	var gwr = tt[1].split(',');
	if(gwr[1] == '0')
	{
		gwr[1] = 'miss';
	}
	bbcur = bbr[0];
	bbmcur=bbr[1];
	gwcur = gwr[0];
	//alert(gwcur)
    endtips = tt[2];
	//alert(tt[5]);
	if(useprops == 0)
	{
		//alert('a1')
		var iw = parseInt((imgw/bbmpmax)*bbr[1]);	
		
		$('pmp').style.width=iw+'px';
		$('bmpf').innerHTML=bbr[1]+'/'+bb[13];
		var fontValue='';
	
		//攻击
		if(useInfo[0]!=undefined&&useInfo[0]=="3"){//959658,8925,0,加血三,3,-20,25#91,15,普通攻击##
			/*if(endtips.length>0){
				
			}else{*/
				if(useInfo[1]<=0){//生命改变
					fontValue+=bbr[3]+'<font color="#00AF00"><i>'+(useInfo[1]>0?"":"+")+(-1*useInfo[1])+'</i> </font>';	
				}
				if(useInfo[2]<0){//魔法改变
					fontValue+='<br/>'+bbr[3]+'<font color="#0000AF"><i>'+(useInfo[2]>0?"":"+")+(-1*useInfo[2])+'</i> </font>';	
				}
				setTimeout("hpimg('php')",3000);
				$('pfont').style.top='200px';
				$('pfont').style.left='80px';
				$('pfont').style.zIndex='1000';
				font(fontValue,"fontHide('"+tt[1]+tt[3]+"');");
				fontValue="";
			//}
		}else{
			
			//Move pets and view jn name.
			$('pimg').style.left='470px';
			$('pimg').style.zIndex='100';
			$('pimg').src=''+IMAGE_SRC_URL+'/bb/'+bb[8].replace('z',gimg);
			if( bbr[2] == 'miss')
			{
				var fontValue='<font color=red><i>'+bbr[2]+'</i> </font>!';
			}
			else
			{
				var fontValue='<font color=red><i>-'+bbr[2]+'</i> </font>!!';
			}
			$('pfont').style.left='620px';
			
			
			//判断吸血和吸魔的值是否为空和是否显示
			var strings;
			if(hpinfo != null && mpinfo != null)
			{
				strings = bbr[3]+'! '+fontValue+"<font color='#14FD10'>"+hpinfo+"</font><font color='#0067CB'>"+mpinfo+"</font>";
			}
			else if(hpinfo != null && mpinfo == null)
			{
				strings = bbr[3]+'! '+fontValue+"<font color='#14FD10'>"+hpinfo+"</font>";
			}
			else if(hpinfo == null && mpinfo != null)
			{
				strings = bbr[3]+'! '+fontValue+"<font color='#0067CB'>"+mpinfo+"</font>";
			}
			else
			{
				strings = bbr[3]+'! '+fontValue;
			}
			switch (crit[2])
			{
				case "1" :
				{
					strings = "<font color=red>加强</font>"+strings;
					break;
				}
				case "2" :
				{
					strings = "<font color=red>减弱</font>"+strings;
					break;
				}
				case "3" :
				{
					strings = "<font color=red>恐吓</font>"+strings;
				}
			}
			if(crit[1] ==  '1')	//暴击
			{
				strings = "<p><font color=red><b>暴击  </b></font></p>" + strings;
			}
			if(ackstr[1] != null)
			{
				var ack = "<font color='#9900FF'>"+ackstr[1]+"</font>";
				if(strings != null)
				{
					strings += "<br />"+ack;
				}
				else
				{
					strings = ack;
				}
			}
	
			//结束判断
			//攻击怪物
			if(tt[5]=='MULTI_MONSTRTER_CONTINUE'){
				mMonsterFighting = true;				
				hpimg('ghp');
				font(strings,"fontHide('"+tt[1]+tt[3]+"');");//$('tooldiv').style.display='block';};
				setTimeout("if(teamfightlock=='NONE'||!teamfightlock){loadtime(waittime);}",6500);				
			}else{
				hpimg('ghp');
				font(strings,"fontHide('"+tt[1]+tt[3]+"');");
			}
		}
	}
	else
	{
		useprops = 0;
		//gwF(tt[1]);
		window.setTimeout("gwF('"+tt[1]+tt[3]+"');", 1000);
	}
	if (tt[3]!='') word(tt[3]);
}

/**
* @Make random number.
*/
function rand(under, over){ 
        switch(arguments.length){ 
            case 1: return parseInt(Math.random()*under+1); 
            case 2: return parseInt(Math.random()*(over-under+1) + under);  
            default: return 0; 
        } 
    }  // shawl.qiu script 
// get jn
function jnstr(str){
	return eval(str);
}

/**
* hp img view
*/
function hpimg(imgid)
{
	  var hpmax;
	  var cur;
	  var imgw;
		if(imgid == 'php') // gw ack, view bb.
		{
			imgw   = 55;
			$('bhpf').innerHTML=bbcur+'/'+bb[12];
			hpmax=bb[12];
			if (bbcur<=0) {fc=-2;bbcur=0;}
			cur = bbcur;
		}
		else {
			imgw = 100;
			$('ghpf').innerHTML=gwcur+'/'+gg[5];
			hpmax=gg[5];
			if (gwcur<=0) {fc=-1;gwcur=0;}
			cur = gwcur;
		}

		var iw = parseInt((imgw/hpmax)*cur);	
		if(isNaN(iw)) iw=0;
		$(imgid).style.width=iw+'px';
		$(imgid).style.border='0px';
}

function fatEnd(){
	displayResult(endtips); // Only test.
}
// Create yao window.

function yaoWin(n){
	var d = document.createElement("DIV");
	if(n == 1) // hp.
	{
		d.style.cssText = "position:absolute;border:1px solid #ccc;left:515px;top:190px; background-color:red;display:none;";
		d.id = 'ywin';
	}
	$('fm').appendChild(d);

}
function viewyw(n)
{
	if(n==1) $('ywin').style.top = '249px';
	else $('ywin').style.top = '249px';
	loadYW(n);
	$('ywin').style.display = '';
}

function UseYao(n)
{
	if(using == true) {window.parent.Alert('战斗状态您不能吃药！');return;}
	var opt = {
    		 method: 'get',
    		 onSuccess: function(t){
			 	if(t.responseText != '0')
				{	
					if(t.responseText != 'hasusemedbuff')
					{
						var arr = t.responseText.split(',');
						arr[0] = parseInt(arr[0]);
						arr[1] = parseInt(arr[1]);
						if (arr[0]!=0)
						{
							bbcur= parseInt(bbcur)+parseInt(arr[0]);
							if (bbcur>bb[12]) bbcur = bb[12];
							var initw =  parseInt((imgw/bb[12])*bbcur);	; // init img width.
							$('php').style.width=initw+'px';
							$('bhpf').innerHTML=bbcur+'/'+bb[12];
						}
						if (arr[1]!=0)
						{
							bbmcur= parseInt(bbmcur)+parseInt(arr[1]);			
							if (bbmcur>bb[13]) bbmcur = bb[13];
							
							var initw =  parseInt((imgw/bb[13])*bbmcur);	; // init img width.
							$('pmp').style.width=initw+'px';
							$('bmpf').innerHTML=bbmcur+'/'+bb[13];
						}
						window.clearTimeout(readH);
						useprops = 1;
						using = true;
						var tip='';
						
						if (arr[0]!=0) tip=arr[0]+'hp ';
						if (arr[1]!=0) tip+=arr[1]+'mp';
						if ( arr[2]!=0 )
						{	
							tip+=arr[2]+'攻击';
							buff_div('攻击',arr[2]);
						}
						if (arr[3]!=0)
						{
							tip+=arr[3]+'防御';
							buff_div('防御',arr[3]);
						}
						yaoValue(tip,0);
						//window.parent.$('gw').src='../function/fbfightGate.php?id='+id+'&g='+gg[11]+'&checkwg=checked';
						//getAckOfBB(1);
					}
					else
					{
						window.parent.Alert("你已经使用过类似的临时状态药了哦");
					}
				}
    		 },
    		 on404: function(t) {
    		 },
    		 onFailure: function(t) {
    		 },
    		 asynchronous:true        
		}
	var ajax=new Ajax.Request('../function/getProps.php?id='+n, opt);
}

//yaoWin(1);

/**left:80,top:200*/

function yaoValue(n,c)
{
	if (c==0)
	{$('yaovid').innerHTML = '+'+n;
	}
	var obj=$('yaovid').style;
	if( typeof(obj.filter) != 'undefined' )
	{
		var str=obj.filter.replace("\"","");
		var c=parseInt(str.replace("alpha(opacity=","").replace(")",""));
		c+=10;

    	if(c>=100)
		{ 
			window.setTimeout("flashYao('"+n+"',100);", 1000);
			c=0;
			return;
		}
		else
		{ 
			obj.filter="alpha(opacity="+c+")";
			window.setTimeout("yaoValue('"+n+"','"+c+"');",5);
		}
	}
	else
	{
		var str = obj.opacity;
		c = parseFloat(str);
		c += 0.1;
		if(c >= 1)
		{
			c = 1;
			window.setTimeout("flashYao('"+n+"',100);", 1000);
			c = 0;
			return;
		}
		else
		{
			obj.opacity = c;
			window.setTimeout("yaoValue('"+n+"','"+c+"');",5);
		}
	}
}
function buff_div(para,val)
{
	var med_buff = $('med_buff');
	switch (para)
	{
		case '攻击' :
		{
			med_buff.innerHTML += '<img src="../new_images/ui/buff_ac.jpg" title = "攻击增加:'+val+'" />';
			break;
		}
		case '防御' :
		{
			med_buff.innerHTML += '<img src="../new_images/ui/buff_mc.jpg" title = "防御增加:'+val+'" />';
			break;
		}
	}
}
function flashYao(n,c)
{
	var obj=$('yaovid').style;
	if( typeof(obj.filter) != 'undefined' )
	{
		var str=obj.filter.replace("\"","");
		var c=parseInt(str.replace("alpha(opacity=","").replace(")",""));
		c-=10;

    	if (c<=0)
		{	obj.filter="alpha(opacity=0)";
			$('yaovid').innerHTML = '';
			window.setTimeout("getAckOfBB(1);", 1000);
		}
		else{
			obj.filter="alpha(opacity="+c+")";
			window.setTimeout("flashYao('"+n+"','"+c+"');",5);
		}
	}
	else
	{
		var str = obj.opacity;
		c = parseFloat(str);
		c -= 0.1;
		if(c <= 0 )
		{
			c = 0;
			obj.opacity = 0;
			$('yaovid').innerHTML = '';
			window.setTimeout("getAckOfBB(1);", 1000);
		}
		else
		{
			obj.opacity = c;
			window.setTimeout("flashYao('"+n+"','"+c+"');",5);
		}
	}
}

// Catch part.
function viewCatch(p)
{
//	window.parent.Alert('暂未开放！');
//	return;
	if(catchs==1){window.parent.Alert('等待下一次机会吧！');return;}
	var opt = {
    		 method: 'get',
    		 onSuccess: function(t){
			 	var n = parseInt(t.responseText);
				if (n==2){window.parent.Alert('捕捉道具与捕捉宝宝五行不同！');}
				else if(n==3){window.parent.Alert('此宝宝不能捕捉！');}
				else if(n==4){window.parent.Alert('宝宝很灵活，你的道具太差了!');}
				else if(n==6){window.parent.Alert('牧场已经放不下了，等扩大了再来吧!');}
				else if(n==7){window.parent.Alert('不能用这个精灵球捕捉这个宝宝喔~');}
				else if(n==10){
					window.parent.Alert('啊！恭喜您，捕捉到一个宝宝！');
					window.location.reload();
				}
				else if(n==12){window.parent.Alert('选择道具对当前怪物无效！');}
				else if(n==13){window.parent.Alert('失败，没能获得任何道具！');}
				else if(n==15){
					window.parent.Alert('啊！恭喜您，获得道具！');
					window.location.reload();
				}
				else {window.parent.Alert('捕捉失败!');}
				catchs=1;
				//if(n!=10) getAckOfBB(1);
    		 },
    		 on404: function(t) {
    		 },
    		 onFailure: function(t) {
    		 },
    		 asynchronous:true        
		}
	var ajax=new Ajax.Request('../function/get.Catch.php?pid='+p, opt);
}

function word(str)
{
	var oldstr = $('word').innerHTML;
	if (oldstr == str) return;
	$('word').style.display='';
	$('word').innerHTML = str;
	window.setTimeout("hideword();",5000);
}
function hideword()
{
	$('word').style.display='none';
}

// Load tools
function loadtool(n)
{
	var toolshd = $('jntool');
	toolshd.style.display='';
	toolshd.innerHTML='';
	if (n == 1) // auto
	{
		toolshd.innerHTML="<span onclick=\"autoFitStart(1);\" style='border:1px solid #ccc;cursor:hand;'>开始自动攻击</span>";
		toolshd.innerHTML+="<span onclick=\"autoFitStart(2);\" style='border:1px solid #ccc;cursor:hand;'>关闭自动攻击</span>";
		//window.parent.$('gw').src='./function/Fight_Mod.php?p='+petsid;
	}
	else if(n==2)	// ack set.
	{
		for(var i=0;i<=7; i++)
		{
			try{
				var tt = bbjn[i];
				if ( typeof bbjn[i] == 'undefined') continue;
				toolshd.innerHTML+="<span onclick=\"window.parent.usejn='"+tt[9]+"';closetl();\" style='border:1px solid #ccc;cursor:hand;'>"+ tt[0] +"</span>";
			}catch(e){continue;}
		}
	}
	else if(n==3)	// common ack
	{
		closetl();Usejn(1);
	}
	else if(n==4) // skill
	{
		//window.parent.Alert($('bbskill').innerHTML);
		//toolshd.innerHTML=$('skills').innerHTML;
		for(var i=0;i<=7; i++)
		{
			try{
				var tt = bbjn[i];
				if ( typeof bbjn[i] == 'undefined') continue;
				toolshd.innerHTML+="<span onclick=\"Usejn("+tt[9]+");closetl();\" style='border:1px solid #ccc;cursor:pointer;'>"+ tt[0] +"</span>";
			}catch(e){continue;}
		}
	}
	else if(n==5) // help
	{
		if(bbfzp[0]==0) {toolshd.innerHTML='';return;}
		for(var i=0;i<=bbfzp.length; i++)
		{
			try{
				var tt = bbfzp[i];
				if ( typeof bbfzp[i] == 'undefined') continue;
				
				toolshd.innerHTML+="<span onclick=\"UseYao("+tt[2]+");closetl();\" style='border:1px solid #ccc;cursor:hand;'>"+ tt[0] +"</span>";
			}catch(e){continue;}
		}
	}
	else if(n==6) // catch
	{
		//viewCatch();
		if(catcharr[0]==0) {toolshd.innerHTML='';return;}
		for(var i=0;i<=catcharr.length; i++)
		{
			try{
				var tt = catcharr[i];
				if ( typeof catcharr[i] == 'undefined') continue;
				
				toolshd.innerHTML+="<span onclick=\"viewCatch("+tt[2]+");closetl();\" style='border:1px solid #ccc;cursor:hand;'>"+ tt[0] +"</span>";
			}catch(e){continue;}
		}
	}
	else if(n==7) // props.
	{
		toolshd.innerHTML='使用道具';
	}
	else if(n==8)
	{
		window.parent.$('gw').src='./function/Expore_Mod.php';
	}
	else {toolshd.style.display='none';return false;}
	toolshd.innerHTML+='<div style="float:right;padding:0px;z-index:2;position:absolute;left:280px;"><img src="'+IMAGE_SRC_URL+'/ui/fight/close.gif" onclick="$(\'jntool\').style.display=\'none\';" style="cursor:hand;"></div>';
}
function closetl()
{
	$('jntool').style.display='none';
}

function autoFitStart(ns)
{
	var af = ns==1?true:false;
	var wt = ns==1?3:10;
	var mt = ns==1?'open':'close';
	var tip= ns==1?'开启':'关闭';
	var opt = {
    		 method: 'get',
    		 onSuccess: function(t){
				 if(parseInt(t.responseText)>0)
				 { 	
					window.parent.autoack=af;
					window.parent.waittime=wt;
					closetl();
					Usejn(window.parent.usejn);
					alert(tip+'自动战斗成功！剩余自动战斗次数：'+t.responseText+' 次！');
				 }
				 else {
					if(ns==1) {closetl();window.parent.Alert('开启自动战斗失败,剩余次数为：0');}
					else if(ns==2) {closetl();window.parent.Alert('剩余次数为：0, 关闭自动战斗!');}
					window.parent.autoack=false;
					window.parent.waittime=10;
				 }
    		 },
    		 on404: function(t) {
    		 },
    		 onFailure: function(t) {
    		 },
    		 asynchronous:true        
		}
	var ajax=new Ajax.Request('../function/ext_Fight.php?op='+mt, opt);
}
