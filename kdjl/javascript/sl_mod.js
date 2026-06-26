// JavaScript Document
/*usages:扫雷前端js功能函数*/
var gonggao = "<div class='sm'><b>点击 <font color=red>?</font> 试试您的运气吧!</b></div>";
var flash_time = 0;
var move;
var settime1;
var settime2;
var settime3;
var settime4;
var settime5;
var settime6;
function move_status(para)
{
	move = para==1?1:2;
}
function can_move()
{
	if(move == 1)
	{
		var theEvent = window.event || arguments.callee.caller.arguments[0];
		document.getElementById('tishi').style.top = theEvent.clientY-100+"px";
		document.getElementById('tishi').style.left = theEvent.clientX-600+"px";
	}
}
function sl_restart(para)
{
	tishi = document.getElementById('panel').getElementsByTagName('div');
	for(i=0;i<tishi.length;i++)
	{
		if(tishi[i].id == 'tishi')
		{
			display(tishi[i]);
		}
	}
	div = document.createElement('div');
	div.className = "tishi";
	div.style.top = "250px";
	div.style.left = "100px";
	div.onmousedown  = function()
	{
		move_status(1);
	}
	div.onmouseup  = function()
	{
		move_status(2);
	}
	div.onmousemove = function()
	{
		can_move();
	}
	div.id = "tishi";
	div_child = document.createElement('div');
	div_child.className='font';
	img1 = document.createElement('img');
	img1.className='sure btn tishi_btn';
	img1.src='../images/ui/sl_mod/sl11.gif';
	img2 = document.createElement('img');
	img2.className='chanel btn tishi_btn';
	img2.src='../images/ui/sl_mod/sl12.gif';
	switch(para)
	{
		case 'sx' :
		{
			str = "刷新一次需要消耗<font color='red'>一张扫雷刷新券</font> 是否继续?";
			img1.onclick=function()
			{
				sl_restart_request();
				display('tishi');
			}
			img2.onclick=function()
			{
				display('tishi');
			}
			div_child.innerHTML = str;
			div_child.appendChild(img1);
			div_child.appendChild(img2);
			div.appendChild(div_child);
			break;
		}
		case 'fh' :
		{
			img1.onclick=function()
			{
				die_c('used');
				display('tishi');
			}
			str = "复活需要消耗<font color='red'>一张复活券</font> 是否继续?";
			img2.onclick=function()
			{
				display('tishi');
				die_c('cancel');
			}
			div_child.innerHTML = str;
			div_child.appendChild(img1);
			div_child.appendChild(img2);
			div.appendChild(div_child);
			
			break;
		}
		case 'getfh':
		{
			img1.className='btn tishi_btn';
			img1.onclick=function()
			{
				display('tishi');
			}
			str = "系统赠送您一张复活卡<br>";
			div_child.innerHTML = str;
			div_child.appendChild(img1);
			div.appendChild(div_child);
			settime1 = setTimeout("display('tishi')",2000);
			break;
		}
		case 'sl_card':
		{
			img1.onclick=function()
			{
				display('tishi');
				use_sl_card();
				sl_restart_request();
			}
			str = "系统检测到您进入扫雷必须消耗一张扫雷闯关卡(主宠成长率在65及以上的玩家每天有一次免费进入的机会),是否使用一张扫雷闯关卡?";
			img2.onclick=function()
			{
				display('tishi');
			}
			div_child.innerHTML = str;
			div_child.appendChild(img1);
			div_child.appendChild(img2);
			div.appendChild(div_child);
		}
	}


	document.getElementById('panel').appendChild(div);
}
function sl_restart_request()
{
	var opt = {
		method: 'get',
		onSuccess: function(t)
		{
			var respone =  t.responseText;
			if(respone == 1)
			{
				Alert("您的扫雷刷新券数量不足,不能刷新");
			}
			else
			{
				document.getElementById('prize').innerHTML = respone;
				document.getElementById('sm').innerHTML += "<br>刷新成功";
				scrollWindow();
			}
		},
		on404: function(t)
		{
		},
		onFailure: function(t)
		{
    	},
    	asynchronous:true        
	}
	var ajax=new Ajax.Request('../function/sl_restart.php', opt);
}
function display(id)
{
	obj = document.getElementById(id);
	obj.parentNode.removeChild(obj);
}
function choose(ob)
{
	var id = ob.id.replace("lq_",'');
	var opt = {
		method: 'get',
		onSuccess: function(t)
		{
			var respone =  t.responseText;
			var responeinfo = respone.split('<Boundaries>');
			ob.innerHTML = responeinfo[1];
			ob.className = (responeinfo[1].indexOf('bob.gif') == -1)?'open':'open_lei';
			document.getElementById('sm').innerHTML += '<br>'+responeinfo[2];
			document.getElementById('fhtime').innerHTML = responeinfo[3];
			if(responeinfo[4]!=0)
			{
				sl_restart('getfh');
			}
			if(responeinfo[1].indexOf('bob.gif') != -1)
			{
				settime2 = setTimeout("bob_die()",1000);
				settime3 = setTimeout("table_view('"+responeinfo[0]+"',1)",8000);
			}
			else
			{
				settime2 = setTimeout("table_view('"+responeinfo[0]+"')",3000);
			}
			scrollWindow();
		},
		on404: function(t)
		{
		},
		onFailure: function(t)
		{
		},
		asynchronous:true        
	}
	var ajax=new Ajax.Request('../function/sl_start.php?id='+id, opt);
}
function flash(id,type)
{
	var ob = document.getElementById(id);
	for(i=1;i<=9;i++)
	{
		document.getElementById("lq_"+i).onclick = '';
	}
	if(typeof(ob.filters) == "undefined")	//fox
	{
		if(type == 1)
		{
			ob.style.opacity -= 0.1;
			if(ob.style.opacity <= 0)
			{
				settime4 = setTimeout("flash('"+ob.id+"',0)",10);
			}
			else
			{
				settime4 = setTimeout("flash('"+ob.id+"',1)",10);
			}
		}		
		else
		{
			ob.style.opacity = parseFloat(ob.style.opacity)+0.1;
			
			if(ob.style.opacity >= 1)
			{
				flash_time++;
				if(2 == flash_time)
				{
					flash_time = 0;
					choose(ob);
				}
				else
				{
					settime4 = setTimeout("flash('"+ob.id+"',1)",10);
				}
			}
			else
			{
				settime4 = setTimeout("flash('"+ob.id+"',0)",10);
			}				

		}		
	}
	else	//ie
	{
		if(type == 1)
		{
			ob.filters.Alpha.Opacity -= 10;
			if(ob.filters.Alpha.Opacity <= 0)
			{
				settime4 = setTimeout("flash('"+ob.id+"',0)",10);
			}
			else
			{
				settime4 = setTimeout("flash('"+ob.id+"',1)",10);
			}
		}		
		else
		{
			ob.filters.Alpha.Opacity = parseFloat(ob.filters.Alpha.Opacity)+10;
			
			if(ob.filters.Alpha.Opacity >= 100)
			{
				flash_time++;
				if(2 == flash_time)
				{
					flash_time = 0;
					choose(ob);
				}
				else
				{
					settime4 = setTimeout("flash('"+ob.id+"',1)",10);
				}
			}
			else
			{
				settime4 = setTimeout("flash('"+ob.id+"',0)",10);
			}				
		}	
	}
}
function table_view(para,type)
{
	document.getElementById('leiqu01').innerHTML = para;
	if(type != 1)
	{
		settime5 = setTimeout("auto()",5000);
	}
	else
	{
		settime5 = setTimeout("sl_restart('fh')",2000);
	}
}
function auto()
{
	var opt = {
		method: 'get',
		onSuccess: function(t)
		{
			var respone =  t.responseText;
			var responeinfo = respone.split('<Boundaries>');
			document.getElementById('gs').innerHTML = "<b>"+responeinfo[0]+"</b>";
			document.getElementById('leiqu01').innerHTML = responeinfo[1];
			document.getElementById('leinum').innerHTML = responeinfo[0]-1;
			document.getElementById('fhtime').innerHTML = responeinfo[2];
			scrollWindow();
		},
		on404: function(t)
		{
		},
		onFailure: function(t)
		{
		},
		asynchronous:true        
	}
	var ajax=new Ajax.Request('../function/auto_start.php', opt);	
}
function bob_die()
{
	divd = document.createElement('div');
	divd.className = "donghua";
	divd.id = "donghua";
	pic = document.createElement('img');
	pic.src = "../images/props/bob_broken.gif";
	divd.appendChild(pic);
	document.getElementById('panel').appendChild(divd);
	settime6 = setTimeout("display('donghua')",5000);
	
}
function die_c(para)
{
	var opt = {
		method: 'get',
		onSuccess: function(t)
		{
			
		},
		on404: function(t)
		{
		},
		onFailure: function(t)
		{
		},
		asynchronous:false      
	}
	var ajax=new Ajax.Request('../function/die_deal.php?cmd='+para, opt);
	auto();
}
function scrollWindow()
{
	var t = document.getElementById('sm');
	t.scrollTop = t.scrollHeight;
}
function close_sl_mod()
{
	obj = document.getElementById('saolei_index');
	obj.parentNode.removeChild(obj);
	clearTimeout(settime1);
	clearTimeout(settime2);
	clearTimeout(settime3); 
	clearTimeout(settime4); 
	clearTimeout(settime5); 
	clearTimeout(settime6); 
}
function canntplay()
{
	sl_restart('sl_card');
}
function use_sl_card()
{
	opt = {
		method: 'get',
		onSuccess: function(t)
		{
			if(t.responseText != 'ok')
			{
				Alert(t.responseText);
			}
			else
			{
				Alert("扫雷卡使用成功");
			}
			auto();
		},
		on404: function(t)
		{
		},
		onFailure: function(t)
		{
		},
		asynchronous:false         
	}
	ajax=new Ajax.Request('../function/use_sl_card.php', opt);
}
function open_info()
{
	objarr = document.getElementById('panel').getElementsByTagName('div');
	times = 0;
	for(i=0;i<objarr.length;i++)
	{
		if(objarr[i].id == 'info')
		times++;
	}
	if(times != 0)
	{
		return false;
	}
	div = document.createElement('div');
	divp = document.createElement('div');
	font = document.createElement('font');
	font.innerHTML = "<b>【扫雷开启】</b><p>1、每日宠物成长≥65的玩家可免费开启扫雷闯关<br>一次；</p><p>2、	可使用神秘商店贩卖的扫雷闯关卡开启。</p><b>【扫雷规则】</b><p>1、扫雷闯关共有九层关卡，您可以在右下方的展<br>示栏看到每关卡隐藏的宝物； </p><p>2、每一关分为9个密室，您可选择其中一个密室<br>来探索宝物；</p><p>3、每关雷数=当前关卡-1（从第二关起）；</p><p>4、玩家完成所有关卡或在某一关卡遇雷死亡后无<br>法复活即宣告扫雷闯关结束。</p><b>【扫雷功能】</b><p>1、	扫雷闯关卡：可以再次获得进行扫雷闯关的<br>权限，每张限使用一次;</p><p>2、	扫雷复活卡：遇雷死亡后使用，原关复活;<br>(通关扫雷3关、6关、9关后有几率获得)</p><p>3、扫雷刷新券：可用于刷新关卡奖励，每张限使<br>用一次。</p>";
	div.className = "info";
	div.id = "info";
	divp.style.width='290px';
	divp.style.height='360px';
	divp.id = 'divp';

	div.appendChild(divp);
	font.className = "infofont";
	divp.appendChild(font);
	img = document.createElement('img');
	img.src = '../images/ui/sl_mod/sl11.gif';
	img.className = "infosure btn";
	img.onclick = function()
	{
		display("info");
	}
	divp.appendChild(img);
	document.getElementById('panel').appendChild(div);
	
}