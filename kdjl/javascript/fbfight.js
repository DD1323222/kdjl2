
// Load img preges
try{ 
		if(typeof window.parent.autoack==false){}
	}catch(e){window.setTimeout('window.parent.location.reload()',1000)};

var imgw = 155;
var waittime=window.parent.waittime; // default;
var fubenend = 0;
var wx_type = "";
function createLeft(){
	// Team player 0 start.
	var sp = document.createElement('DIV'); //hp font
	sp.style.cssText='position:absolute;left:106px;top:27px;color:#000000;z-index:100000;font-size:0.8em';
	sp.id='bhpf';
	sp.innerHTML=bb[5]+'/'+bb[12];
	$('team0').appendChild(sp);
	
	//create bb hp.
	var initw = parseInt(imgw*bb[5]/bb[12]);
	$('php').style.width=initw+'px';
		
	var msp = document.createElement('DIV');// mp font
	msp.style.cssText='position:absolute;left:106px;top:42px;color:#000000;z-index:100000;font-size:0.8em';
	msp.id='bmpf';
	msp.innerHTML=bb[6]+'/'+bb[13];
	$('team0').appendChild(msp);

	// create bb mp
	var initw = parseInt(imgw*bb[6]/bb[13]);
	$('pmp').style.width=initw+'px';
		
	var msp = document.createElement('DIV');// exp font
	msp.style.cssText='position:absolute;left:106px;top:59px;color:#000000;z-index:100000;font-size:0.8em;padding-left:2px';
	msp.id='pfexp';
	msp.innerHTML=bb[14]+'/'+bb[15];
	$('team0').appendChild(msp);
	
	var initw = parseInt(imgw*bb[14]/bb[15]);
	$('pexp').style.width=initw+'px';

	//-------------- create bb part
	var petsdiv = document.createElement('IMG');
	petsdiv.style.cssText='position:absolute;left:10px;top:120px;z-index:10;z-index:2';
	petsdiv.id='pimg';
	petsdiv.src=''+IMAGE_SRC_URL+'/bb/'+bb[8];
	$('fm').appendChild(petsdiv);
	var cf = document.createElement('DIV');
	cf.style.cssText='position:absolute;left:30px;top:90px;font-size:12px;text-align:center;color:#0393D5;z-index:1000;';
	cf.id='cf1';
	cf.innerHTML =bb[0]+'<br /><font color=#097603>'+bb[1]+'级</font>';
	$('fm').appendChild(cf);
	//-------------- create bb part end.

	
	//---------------add bb jn list.
	
	var jnbk = document.createElement('DIV');
	jnbk.style.cssText='position:absolute;left:230px;top:110px;width:352px;height:136px;border:0px;color:#7a9303;font-size:12px;font-size-adjust:0.33;padding:3px;display:none;';
	jnbk.id='jntool';
	$('fm').appendChild(jnbk);

	var sp = document.createElement('DIV'); //hp font
	sp.style.cssText='position:absolute;left:620px;top:49px;color:#338800;z-index:2;font-size:0.9em;';
	sp.id='ghpf';
	sp.innerHTML="";
	$('fm').appendChild(sp);
	
	hp = document.createElement('DIV'); // hp gif
	hp.style.cssText='width:110px;height:11px;position:absolute;left:620px;top:65px; z-index:3; padding-left:13px';
	var hpsrc = '<div id="ghpbkvalue" style="width:96px;height:11px;padding-left:18px;left:13px;position:absolute;color:#412804;font-size:10px">'+gg[5]+'/'+gg[5]+'</div><div id="ghpbk" style="width:96px;height:11px;padding-left:18px;background-repeat:no-repeat;background-position:right top;background-image:url(../images/ui/newmap/dr03.gif);overflow:hidden"><div id="ghp" style="background-image:url(../images/ui/newmap/dr04.gif);width:91px;background-repeat:repeat-x;height:11px;"></div></div>';

	hp.innerHTML = hpsrc;
	//hp.id='ghp';
	$('fm').appendChild(hp);
	
	var lot = document.createElement('div');
	lot.style.cssText='position:absolute;left:620px;top:49px;color:#338800;font-size:0.8em;z-index:3';
	var lots = '<div style="width:31px; height:37px; z-index:4; top:0px; left:0px; position:absolute;background:url(../images/ui/newmap/dr02.gif);"><div style="z-index:4; top:10px; left:15px; position:absolute"><font color="#2A9E49" size="2.5"><b> '+gg[2]+'</b></font></div></div><div style="width:114px; height:11; z-index:5; top:0px;left:13px; position:absolute; padding-left:6px;padding-top:2px;">&nbsp;&nbsp;<span onclick="copyWord(\'怪物-'+gg[0].replace("精英","<font color=blue>精英</font>")+'\');">'+gg[0].replace("精英","<font color=blue>精英</font>")+'</span>&nbsp;LV：'+gg[1]+'</div><div style="width:114px; height:11; z-index:5; top:15px;left:13px; position:absolute"></div>';
	lot.innerHTML=lots;
	$('fm').appendChild(lot);

	var xbg = document.createElement('div');
	xbg.style.cssText='position:absolute;left:620px;top:49px;color:#338800;font-size:0.8em;z-index:1';
	xbg.innerHTML='<div style="width:114px; height:28px;  top:0px; left:13px; position:absolute"><img src="../images/ui/newmap/dr01.gif" style="opacity:0.7;filter: progid:DXImageTransform.Microsoft.Alpha(style=0,opacity=70,finishOpacity=100)" /></div>';
	$('fm').appendChild(xbg);

	var ph = document.createElement('IMG'); // gw gif.
	ph.style.cssText='position:absolute;left:530px;top:120px;width:250px;height:180px;z-index:1';
	ph.src=''+IMAGE_SRC_URL+'/gpc/'+gg[8];
	ph.id='gyg';
	$('fm').appendChild(ph);
	
	// add right head##########################


	// add jn name.
	var jnfont = document.createElement('DIV');
	jnfont.style.cssText='position:absolute;left:600px;top:100px;font-weight:bold;width:250px;height:30px;font-family:华文新魏;font-size:16px;color:yellow';
	jnfont.id='pfont';
	$('fm').appendChild(jnfont);
	
	// add yao value.
	var yao = document.createElement('DIV');
	yao.style.cssText='position:absolute;z-index:1000;width:130px;display:block;filter:alpha(opacity=0);opacity:0;left:170px;top:120px;font-size:18px;color:#3AE131;font-weight:bold';
	yao.id='yaovid';
	$('fm').appendChild(yao);
}

function Usejn(n){
	var cookie_tax = "pm_skill_"+n;
	var timestamp = Date.parse(new Date());
	var tump=0;
	var cold_skill = new Array(319,320,321,322,323);	//冷却技能，此为技能id,写死，不能更改，并保持与fight.js,fbfight.js,fbfightGate.php,FightGate.php的数组一致，否则会出现不可预知的错误！
	switch (n)
	{
		case 319 :
		{
			var need_time = 300000;	//技能id:319冷却时间，单位(毫秒)
			break;
		}
		case 320 :
		{
			var need_time = 300000;	//技能id:320冷却时间，单位(毫秒)
			break;
		}
		case 321 :
		{
			var need_time = 180000;	//技能id:321冷却时间，单位(毫秒)
			break;
		}
		case 322 :
		{
			var need_time = 180000;	//技能id:322冷却时间，单位(毫秒)
			break;
		}
		case 323 :
		{
			var need_time = 120000;	//技能id:323冷却时间，单位(毫秒)
			break;
		}
	}

	for( i=0; i < cold_skill.length; i++ )
	{
		if( n == cold_skill[i] )
		{
			var cookie_val = document.cookie;
			var arrcookie=cookie_val.split("; ");
			for(var i=0;i<arrcookie.length;i++)
			{
　　			var arr=arrcookie[i].split("=");
				if( cookie_tax == arr[0] )
				{
					var val = arr[1];
					break;
				}
			}
			break;
		}
	}
	if ( typeof(need_time) != 'undefined')
	{
		if( typeof(val) == 'undefined' )	//第一次使用
		{
			document.cookie = cookie_tax+'='+timestamp;
		}
		else
		{
			var wait_time = timestamp - parseInt(val);
			if(wait_time <= need_time)
			{
				var time = (need_time-wait_time)/1000;
				window.parent.Alert("还需等待"+time+"秒");
				return;
			}
			else
			{
				document.cookie = cookie_tax+'='+timestamp;
			}
		}
	}
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
	if ( n!=1 && (tump>bbmcur || bbmcur==0 ))
	{n=1;}//alert('您的魔法值不足，无法使用魔法技能！');return;}
	
	if(using==true) return;
 	using=true;
		
	window.clearTimeout(readH);
	$('timev').innerHTML='PK';
	
	$('tooldiv').style.display='none';
	if(n!=1) gimg='s';
	else gimg='g';
	
	
	// Get ack by jn.
	getAckOfBB(n);
}

function font(str,fun){
	$('pfont').innerHTML=str;
	window.setTimeout(fun,3000);
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
	var dxarr = str.split('<dx>');
	var gwr = dxarr[0].split(',');
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
	$('gyg').style.zIndex='100';
	$('gyg').src=''+IMAGE_SRC_URL+'/gpc/'+gg[8].replace('z',ag);
	
	$('pfont').style.left='50px';
	$('pfont').style.fontSize='16px';
	// view jn.
	var strings = gwr[2]+fatValue;
	if(typeof(dxarr[1]) != "undefined")
	{
		strings += "<br /><font color='#ffffff'>"+dxarr[1]+"</font>"
	}
	font(strings,"fontgHide();");
	//font(gwr[2]+fatValue,"fontgHide();");

	//#################################
	hpimg('php');
	//###############################
	//window.setTimeout("fontgHide();",2000);
}

function fontgHide(){
	$('gyg').style.left='547px';
	$('gyg').style.zIndex='10';
	$('gyg').src=''+IMAGE_SRC_URL+'/gpc/'+gg[8];
	
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
	   var initw =155*bb[14]/bb[15];
	   $('pfexp').innerHTML=bb[14]+'/'+bb[15];
	   $('pexp').style.width=initw+'px';
	   //explode_start(2);
	}
	else if (str.indexOf('严重伤害')!=-1)
	{
		//explode_start(1);
	}
	var canAutoFlag = true;
	if(fubenend == 1)
	{
		$('result').innerHTML=str+' <BR/>'+
		"<span onclick=\"var mm=event.offsetX*event.offsetY;auto1(mm);\""+' style="cursor:pointer;"></span> 恭喜您，完成该副本<br /><span onclick="window.parent.$(\'gw\').src=\'/function/City_Mod.php?mapid='+inmap+'\';" style="cursor:pointer;"><b>退出副本</b></span>';
		canAutoFlag = false;
	}
	else
	{
		$('result').innerHTML=str+' <BR/>'+
		"<span onclick=\"var mm=event.offsetX*event.offsetY;auto1(mm);\""+' style="cursor:pointer;"><b>继续探险</b></span> <span onclick="window.parent.$(\'gw\').src=\'/function/fb_Mod.php?mapid='+inmap+'\';" style="cursor:pointer;"><b>返回村庄</b></span>';		
	}
	$('result').style.display='';
	$('timev').innerHTML='KO';
	using=true;
	if(window.parent.autoack==true&&canAutoFlag){
		window.setTimeout("auto();",1000);
	}
}
var lastusetime = 0;
function auto1(a){
	now = (new Date()).getTime();
	if(now-lastusetime<2000){		
		return;
	}
	lastusetime = now;
	//window.parent.$('gw').src='/function/fbfight_Mod.php?p="+petsid+"&bid='+mm;
	//window.parent.$('gw').src='/function/fbfight_Mod.php?p="+petsid+"&bid='+mm;
	window.setTimeout("window.parent.$('gw').src='function/fbfight_Mod.php?p="+petsid+"&bid="+a+"'",1000);
	//window.setTimeout("window.parent.$('gw').src='function/Fight_Mod.php?p="+petsid+"&bid="+a,1000);
	//window.setTimeout("window.parent.$('gw').src='function/Fight_Mod.php?p="+petsid+"&bid="+a+"'",1000);
	//window.parent.$('gw').src='/function/Fight_Mod.php?p="+petsid+"&bid='+mm;
}

function auto()
{
	now = (new Date()).getTime();
	if(now-lastusetime<1000){		
		return;
	}
	lastusetime = now;
	window.setTimeout("window.parent.$('gw').src='./function/fbfight_Mod.php?p='+petsid+'&auto=2'",1000);
	//window.parent.$('gw').src='./function/fbfight_Mod.php?p='+petsid;
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
			 if(t.responseText == 'SKILLCOLD')
			 {
			 	parent.Alert("你使用的技能尚在冷却中");window.location="/function/Team_Mod.php";return;
			 }
			 if(t.responseText == 'Unauthorized access to copy')
			 {
				 parent.Alert("系统检测到您非法操作");window.location="/function/Team_Mod.php";return;
			 }
			 	if(t.responseText == 0 || t.responseText=='') return;
			 	else {splits(t.responseText);}
    		 },
    		 on404: function(t) {
    		 },
    		 onFailure: function(t) {
    		 },
    		 asynchronous:true        
		}
//window.status = '../function/fbfightGate.php?id='+id+'&g='+gg[11];
	var ajax=new Ajax.Request('../function/fbfightGate.php?id='+id+'&g='+gg[11]+'&checkwg=checked', opt);
}

// Split server info.
function splits(str)
{
	var crit = str.split('*');
	wx_type = crit[2];
	str = crit[0];
	var ackstr = "";
	if(str.indexOf("<ack>") != -1)
	{
		ackstr = str.split("<ack>");
	}
	if(str.indexOf("#end") != -1)
	{
		fubenend = 1;
	}
	else
	{
		fubenend = 0;	
	}
	if(str == 'autoend') {autoFitStart(2);return;}
	var tt = str.split('#');
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
    endtips = tt[2];
	if(useprops == 0)
	{
		var iw = parseInt((imgw/bbmpmax)*bbr[1]);	
		$('pmp').style.width=iw+'px';
		$('bmpf').innerHTML=bbr[1]+'/'+bb[13];
		var fontValue="";
		//攻击
		if(useInfo[0]!=undefined&&useInfo[0]=="3"){//999658,8925,0,加血三,3,-20,25#91,15,普通攻击##			
			if(useInfo[1]<=0){//生命改变
				fontValue+=bbr[3]+'<font color="#00AF00"><i>'+(useInfo[1]>0?"":"+")+(-1*useInfo[1])+'</i> </font>';	
			}
			if(useInfo[2]<0){//魔法改变
				fontValue+='<br/>'+bbr[3]+'<font color="#0000AF"><i>'+(useInfo[2]>0?"":"+")+(-1*useInfo[2])+'</i> </font>';	
			}
			setTimeout("hpimg('php')",3000);
			$('pfont').style.top='200px';
			$('pfont').style.left='80px';
			font(fontValue,"fontHide('"+tt[1]+tt[3]+"');");
			fontValue="";
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
			hpimg('ghp');
			
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
			font(strings,"fontHide('"+tt[1]+tt[3]+"');");	
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
			imgw   = 155;
			$('bhpf').innerHTML=bbcur+'/'+bb[12];
			hpmax=bb[12];
			if (bbcur<=0) {fc=-2;bbcur=0;}
			cur = bbcur;
		}
		else {
			imgw = 91;
			$('ghpbkvalue').innerHTML=gwcur+'/'+gg[5];
			hpmax=gg[5];
			if (gwcur<=0) {fc=-1;gwcur=0;}
			cur = gwcur;
			$('ghpbk').style.width=(parseInt((imgw/hpmax)*cur)+5)+'px';
		}

		var iw = parseInt((imgw/hpmax)*cur);	
	
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
	if( $('timev').innerHTML <=1 )
	{
		window.parent.Alert('现在不能吃药');return;
	}
	if(using == true) {window.parent.Alert('战斗状态您不能吃药！');return;}
	var opt = {
    		 method: 'get',
    		 onSuccess: function(t){//window.parent.Alert(t.responseText);
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
	var ajax=new Ajax.Request('../function/getProps.php?type=fb&id='+n, opt);
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
					window.setTimeout('window.location.reload()',1000);
				}
				else if(n==12){window.parent.Alert('选择道具对当前怪物无效！');}
				else if(n==13){window.parent.Alert('失败，没能获得任何道具！');}
				else if(n==15){
					window.parent.Alert('啊！恭喜您，获得道具！');
					window.setTimeout('window.location.reload()',1000);
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
function tbs(){
	var str = '<table border="0" cellspacing="0" cellpadding="0"><tr><td width="17"><img src="../images/ui/newmap/bk01.gif" width="17" height="123" /></td><td background="../images/ui/newmap/bk04.gif">';
	return str;
}
function tbe(){
	return '</td><td width="31"><table width="100%" border="0" cellspacing="0"cellpadding="0"><tr><td width="31"><img src="../images/ui/newmap/bk02.gif" width="31" height="31" onclick="$(\'jntool\').style.display=\'none\';" style="cursor:pointer;" /></td></tr><tr><td><img src="../images/ui/newmap/bk03.gif" width="31" height="92" /></td></tr></table></td></tr></table><table width="100" border="0" align="left" cellpadding="0" cellspacing="0" style="margin-left:10px"><tr><td><img src="../images/ui/newmap/jiantou.gif" width="17" height="16" /></td></tr></table>';
}
function tbs1(){
	return '<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr>';
}
function tbe1(){
	return '</tr></table>';
}
function foreach(str){
	return '<td height="28px"><table border="0" cellspacing="0" cellpadding="0"><tr><td width="10"><img src="../images/ui/newmap/bk05.gif" width="3" height="22" /></td><td align="center" background="../images/ui/newmap/bk.jpg">'+str+'</td><td width="4"><img src="../images/ui/newmap/bk06.gif" width="4" height="22" /></td></tr></table></td>';
}
function loadtool(n)
{
	var toolshd = $('jntool');
	toolshd.style.display='';
	toolshd.innerHTML='';
	if (n == 1) // auto
	{
		var tl = tbs()+tbs1()+foreach('<span onclick="autoFitStart(2);" style="cursor:pointer;">开始金币版自动攻击(1.2倍经验)</span>')+foreach('<span onclick="autoFitStart(4);" style="cursor:pointer;">关闭自动战斗</span>')+'</tr><tr>'+foreach('<span onclick="autoFitStart(1);" style="cursor:pointer;">开始元宝版自动攻击(1.5倍经验)</span>')+foreach('<span onclick="autoFitStart(3);" style="cursor:pointer;">关闭自动战斗</span>')+tbe1()+tbe();
		toolshd.innerHTML = tl;
	}
	else if(n==2)	// ack set.
	{
		var toolshda = tbs()+tbs1();
		var j = 1;
		var checked = 2;
		for(var i=0;i<=7; i++)
		{
			try{
				var tt = bbjn[i];
				if ( typeof bbjn[i] == 'undefined') continue;
				if(j % 3 == 0){
					toolshda += '<tr>';
				}
				toolshda += foreach("<span onclick=\"window.parent.usejn='"+tt[9]+"';closetl();\" style='cursor:pointer;'>"+ tt[0] +"</span>");
				j++;
				if(j % 3 == 0){
					toolshda += '<tr>';
				}
			}catch(e){continue;}
		}
		toolshda += tbe1()+tbe();
		toolshd.innerHTML = toolshda;
	}
	else if(n==3)	// common ack
	{
		closetl();Usejn(1);
	}
	else if(n==4) // skill
	{
		var toolshda = tbs()+tbs1();
		var j = 1;
		for(var i=0;i<=7; i++)
		{
			try{
				var tt = bbjn[i];
				if ( typeof bbjn[i] == 'undefined') continue;
				if(j % 3 == 0){
					toolshda += '<tr>';
				}
				toolshda += foreach('<span onclick="Usejn('+tt[9]+');closetl();" style="cursor:pointer;">'+tt[0]+'</span>');
				j++;
				if(j % 3 == 0){
					toolshda += '<tr>';
				}
			}catch(e){continue;}
		}
		toolshda += tbe1()+tbe();
		toolshd.innerHTML = toolshda;
	}
	else if(n==5) // help
	{
		var toolshda = tbs()+tbs1();
		var j = 1;
		var checked = 2;
		if(bbfzp[0]==0) {toolshd.innerHTML='';return;}
		for(var i=0;i<=bbfzp.length; i++)
		{
			try{
				var tt = bbfzp[i];
				if ( typeof bbfzp[i] == 'undefined') continue;
				if(j % 3 == 0){
					toolshda += '<tr>';
				}
				toolshda += foreach('<span onclick="UseYao('+tt[2]+');closetl();" style="cursor:pointer;">'+tt[0]+'</span>');
				j++;
				if(j % 3 == 0){
					toolshda += '<tr>';
				}
				checked = 1;
				//toolshd.innerHTML+="<span onclick=\"UseYao("+tt[2]+");closetl();\" style='border:1px solid #ccc;cursor:pointer;'>"+ tt[0] +"</span>";
				
			}catch(e){continue;}
		}
		toolshda += tbe1()+tbe();
		if(checked == 1){
			toolshd.innerHTML = toolshda;
		}else{
			toolshd.innerHTML = tbs()+tbs1()+foreach('暂时没有辅助道具')+tbe1()+tbe();
		}
	}
	else if(n==6) // catch
	{
		var toolshda = tbs()+tbs1();
		var j = 0;
		var checked = 2;
		if(catcharr[0]==0) {toolshd.innerHTML='';return;}
		for(var i=0;i<=catcharr.length; i++)
		{
			try{
				var tt = catcharr[i];
				if ( typeof catcharr[i] == 'undefined') continue;
				if(j % 3 == 0){
					toolshda += '<tr>';
				}
				toolshda += foreach('<span onclick="viewCatch('+tt[2]+');closetl();" style="cursor:pointer;">'+tt[0]+'</span>');
				j++;
				if(j % 3 == 0){
					toolshda += '</tr>';
				}
				
				checked = 1;
			}catch(e){continue;}
		}
		toolshda += tbe1()+tbe();
		if(checked == 1){
			toolshd.innerHTML = toolshda;
		}else{
			toolshd.innerHTML = tbs()+tbs1()+foreach('暂时没有精灵球')+tbe1()+tbe();
		}
		
		//viewCatch();
		
	}
	else if(n==7) // props.
	{
		toolshd.innerHTML = tbs()+tbs1()+foreach('使用道具')+tbe1()+tbe();
	}
	else if(n==8)
	{
		window.parent.$('gw').src='./function/Expore_Mod.php';
	}
	else {toolshd.style.display='none';return false;}
}
function closetl()
{
	$('jntool').style.display='none';
}

function autoFitStart(ns)
{
	var af = "";
	var wt = "";
	var mt = "";
	var tip = "";
	if(ns == 1)//开启元宝版自动战斗
	{
		af = true;
		mt = "open";
		tip = '开启';
	}
	else if(ns == 2)//开启金币版自动战斗
	{
		af = true;
		mt = "open";
		tip = '开启';
	}
	else if(ns == 3)//关闭自动战斗
	{
		af = false;
		mt = "close";
		tip = '关闭';
	}
	else if(ns == 4)//关闭元宝战斗
	{
		af = false;
		mt = "close";
		tip = '关闭';
	}
	
	/*var af = ns==1?true:false;
	var wt = ns==1?3:10;
	var mt = ns==1?'open':'close';
	var tip= ns==1?'开启':'关闭';*/
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
					//window.location.reload();
					window.setTimeout('window.location.reload()',1000);
				 }
				 else {
					if(ns==1) {closetl();window.parent.Alert('开启自动战斗失败,剩余次数为：0');}
					else if(ns==2) {closetl();window.parent.Alert('剩余次数为：0, 关闭自动战斗!');}
					window.parent.autoack=false;
					//window.location.reload();
					window.setTimeout('window.location.reload()',1000);
					//window.parent.waittime=fttime;
				 }
    		 },
    		 on404: function(t) {
    		 },
    		 onFailure: function(t) {
    		 },
    		 asynchronous:true        
		}
	var ajax=new Ajax.Request('../function/ext_Fight.php?op='+ns, opt);
}
