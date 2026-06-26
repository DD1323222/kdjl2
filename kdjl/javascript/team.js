

var optt = {
		method: 'get',
		
		asynchronous:true        
	}

function createTeam()
{
	optt.onSuccess= function(n) {
					if(n.responseText!='OK')
					{
						window.parent.Alert(n.responseText);
					}
					else
					{
						window.location.reload();
					}					
				};
	var ajax=new Ajax.Request('/function/team.php?rd='+Math.random()+'&act=create', optt);
}

function permitTeam(id)
{
	optt.onSuccess= function(n) {
					if(n.responseText!='OK')
					{
						window.parent.Alert(n.responseText);
					}
				};
	var ajax=new Ajax.Request('/function/team.php?rd='+Math.random()+'&act=permit&id='+id, optt);
}

function unPermitTeam(id)
{
	optt.onSuccess= function(n) {
					if(n.responseText!='OK')
					{
						window.parent.Alert(n.responseText);
					}
				};
	var ajax=new Ajax.Request('/function/team.php?rd='+Math.random()+'&act=unpermit&id='+id, optt);
}

function kickMember(id)
{
	optt.onSuccess= function(n) {
					if(n.responseText!='OK')
					{
						window.parent.Alert(n.responseText);
					}
				};
	var ajax=new Ajax.Request('/function/team.php?rd='+Math.random()+'&act=kickMember&id='+id, optt);
}

function applyTeam(id)
{
	optt.onSuccess= function(n) {
					if(n.responseText!='OK')
					{
						window.parent.Alert(n.responseText);
					}
					else
					{
						window.parent.Alert('申请成功！');
					}
				};
	var ajax=new Ajax.Request('/function/team.php?rd='+Math.random()+'&act=apply&id='+id, optt);
}
function leaveTeam()
{
	optt.onSuccess= function(n) {
					if(n.responseText!='OK')
					{
						window.parent.Alert(n.responseText);
					}else{
						location.reload();
					}							
				};
	var ajax=new Ajax.Request('/function/team.php?rd='+Math.random()+'&act=leave', optt);
}
function swapState()
{
	optt.onSuccess= function(n) {
					if(n.responseText!='OK')
					{
						window.parent.Alert(n.responseText);
					}
				};
	var ajax=new Ajax.Request('/function/team.php?rd='+Math.random()+'&act=swapState', optt);
}

function updateMyTeamInfo()
{
	document.getElementById('teamlistifr').contentWindow.location.reload();
}
function disbandTeam()
{
	optt.onSuccess= function(n) {
					if(n.responseText!='OK')
					{
						window.parent.Alert(n.responseText);
					}
					else
					{
						window.location.reload();
					}
				};
	var ajax=new Ajax.Request('/function/team.php?rd='+Math.random()+'&act=disbandTeam', optt);
}
function getTeamFightMod()
{
	window.location='/function/Fight_Mod.php';
}
