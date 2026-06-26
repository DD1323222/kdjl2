
function g(url,id,script)
{
	if($('gw').src!=url) $('gw').src=url;
	if(typeof($('gw').contentWindow.document.getElementById(id))=='undefined')
	{
		setTimeout('g("'+url+'","'+id+'","'+script+'")',500);
	}else{
		eval(script);
	}
}

function robot()
{
	g('/function/Team_Mod.php?n=1','teamlistifr','checkExistsTeam()');
}
var teamApplyTimes=0;
function checkExistsTeam()
{
	try{
		var o=$('gw').contentWindow.document.getElementById('teamlistifr').contentWindow;
		if(o.inteam)
		{
			teamApplyTimes=0;
			if(o.isleader)
			{
				var datas=o.datas;
				var membersCt=0;
				for(i=0;i<datas.length;i++)
				{
					if(datas[i].length<1) continue;
					tmp=datas[i].split('|');		
					if(tmp.length==3)
					{
						if(tmp[2]=='-1')
						{
							$('gw').contentWindow.permitTeam(tmp[0]);	
						}else if(tmp[2]!='-2'){
							membersCt++;
						}
					}
				}
	
				if(membersCt>=3)
				{
					autoack=true;
					$('gw').src='/function/Fight_Mod.php?team_auto=1&team_auto=1&setteamauto=1&rd='+Math.random();
					setTimeout('checkExistsTeam()',3000);
					return;
				}
				setTimeout('checkExistsTeam()',3000);
			}else{
				var datas=o.datas;			
				for(i=0;i<datas.length;i++)
				{
					if(datas[i].length<1) continue;
					tmp=datas[i].split('|');
					if(myUid==tmp[0]&&tmp[2]=='0')
					{
						$('gw').contentWindow.swapState();
						setTimeout('checkExistsTeam()',3000);
						return;
					}
				}
				setTimeout('checkExistsTeam()',3000);
			}
		}else{
			var datas=o.datas;
			if(datas.length>0&&datas[0].length>0&&teamApplyTimes<8){
				for(i=0;i<datas.length;i++)
				{
					if(datas[i].length<1) continue;
					var tmp=datas[i].split('|');
					if(Math.random()<0.3)
					{
						$('gw').contentWindow.applyTeam(tmp[0]);
					}	
				}
				teamApplyTimes++;
				setTimeout('checkExistsTeam()',5000);
			}else{
				$('gw').contentWindow.createTeam();
				setTimeout('checkExistsTeam()',5000);
			}
		}
	}
	catch(e)
	{
		setTimeout('checkExistsTeam()',15000);
	}
}
