
// Load img preges
window.parent.autoack=false;
window.parent.waittime=10;
try{ 
		if(typeof window.parent.autoack==false){}
	}catch(e){window.parent.location.reload();}

var imgw = 155;
var waittime=window.parent.waittime; // default;
if (fuser!='') var gpcpath=''+IMAGE_SRC_URL+'/bb/';
else var gpcpath=''+IMAGE_SRC_URL+'/gpc/';
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
	petsdiv.style.cssText='position:absolute;left:10px;top:120px;';
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
	lot.style.cssText='position:absolute;left:640px;top:50px;color:#ffffff;z-index:2;font-size:1.0em';
	if (fuser!='') 
	{tempname= fuser+'的';imgpos=" filter: FlipH; -moz-transform: matrix(-1, 0, 0, 1, 0, 0); -webkit-transform: matrix(-1, 0, 0, 1, 0, 0);";}
	
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
	jnfont.style.cssText='position:absolute;left:600px;top:150px;font-weight:bold;width:250px;height:30px;z-index:1000000;font-family:华文新魏;font-size:1.5em;color:yellow';
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
		/*for(var i=0;i<bbjn.lenght;i++)
		{
			var ttarr = bbjn[i];
			if (n==bbjn[9])
			{
				tump=ttarr[8];break;
			}
		}*/
		return false;
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
	$('pimg').style.left='470px';
	$('pimg').style.zIndex='3';
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
	$('pimg').style.zIndex='2';
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
	$('gyg').style.zIndex='3';
	$('gyg').src=gpcpath+gg[8].replace('z',ag);
	
	$('pfont').style.left='50px';
	
	// view jn.
	font(gwr[2]+fatValue,"fontgHide();");

//#################################
	hpimg('php');
//###############################
	window.setTimeout("fontgHide();",2000);
}

function fontgHide(){
	$('gyg').style.left='547px';
	$('gyg').style.zIndex='1';
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
	   var initw =155*bb[14]/bb[15];
	   $('pfexp').innerHTML=bb[14]+'/'+bb[15];
	   $('pexp').style.width=initw+'px';
	   //explode_start(2);
	}
	else if (str.indexOf('严重伤害')!=-1)
	{
		//explode_start(1);
	}
	$('result').innerHTML=str+' <BR/>'+' <span onclick="window.parent.$(\'gw\').src=\'/function/BattleInfo_Mod.php\';" style="cursor:pointer;"><b>返回阵营</b></span> <span onclick="window.parent.$(\'gw\').src=\'/function/BattleFight_Mod.php?bcode=\'+petsid;" style="cursor:pointer;"><b>继续战斗</b></span>';
	$('result').style.display='';
	$('timev').innerHTML='KO';
	using=true;
	if(window.parent.autoack==true){
		window.setTimeout("auto();",1000);
	}
}
function auto()
{
	window.parent.$('gw').src='./function/BattleFight_Mod.php?bcode='+petsid;
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
	var ajax=new Ajax.Request('../function/battleFightGate.php?id='+id+'&g='+gg[11]+'&checkwg=checked', opt);
}

// Split server info.
function splits(str)
{
	var crit = str.split('*');
	wx_type = crit[2];
	str = crit[0];
	if(str == 'autoend') {autoFitStart(2);return;}
	var tt = str.split('#');
	var bbr = tt[0].split(',');
	if(bbr[2] == '0')
	{
		bbr[2] = 'miss';
	}
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
		else
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
		if( bbr[2] == 'miss')
		{
			var fontValue='<font color=red><i>'+bbr[2]+'</i> </font>!';
		}
		else
		{
			var fontValue='<font color=red><i>-'+bbr[2]+'</i> </font>!!';
		}
		$('pfont').style.zIndex='10000000';
		$('pfont').style.left='620px';
		hpimg('ghp');
		
		//判断吸血和吸魔的值是否为空和是否显示
		if(hpinfo != null && mpinfo != null)
		{
			var strings = bbr[3]+'! '+fontValue+"<font color='#14FD10'>"+hpinfo+"</font><font color='#0067CB'>"+mpinfo+"</font>";
		}
		else if(hpinfo != null && mpinfo == null)
		{
			var strings = bbr[3]+'! '+fontValue+"<font color='#14FD10'>"+hpinfo+"</font>";
		}
		else if(hpinfo == null && mpinfo != null)
		{
			var strings = bbr[3]+'! '+fontValue+"<font color='#0067CB'>"+mpinfo+"</font>";
		}
		else
		{
			var strings = bbr[3]+'! '+fontValue;
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
		//结束判断
		//攻击怪物
		//alert(strings);
		font(strings,"fontHide('"+tt[1]+"');");	
	}
	else
	{
		useprops = 0;
		//gwF(tt[1]);
		window.setTimeout("gwF('"+tt[1]+"');", 1000);
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
			imgw = 100;
			$('ghpf').innerHTML=gwcur+'/'+gg[5];
			hpmax=gg[5];
			if (gwcur<=0) {fc=-1;gwcur=0;}
			cur = gwcur;
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
	if(using == true) {window.parent.Alert('战斗状态您不能吃药！');return;}
	var opt = {
    		 method: 'get',
    		 onSuccess: function(t){
			 	if(t.responseText != 0)
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
					yaoValue(tip,0);
					//getAckOfBB(1);
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
	return;
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
		var tl = tbs()+tbs1()+foreach('战场的时候不能使用自动攻击！')+tbe1()+tbe();
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
		return false;
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
		return false;
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
	return;
}
