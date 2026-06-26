
function sel(obj)
{
	for(var i=1;i<4;i++)
	{	
		try{
			$('i'+i).style.filter="alpha(opacity=50)";
		}catch(e){continue;}
	}
	obj.filter="alpha(opacity=100)";
}

function Display(bid,obj)
{
	var objsty = obj.style;
	sel(objsty);
	window.parent.$('gw').src="./function/Pets_Mod.php?pid="+bid;
}

function Setbb(bid,obj,mbid)	// Get pets.
{
	if(bid == mbid)
	{
		return;
	}
	var opt = {
	method: 'get',
	onSuccess: function(t) {
					var tt = t.responseText;
					if(tt == 10){
						if(!confirm("您当前接有不可切换主宠的任务，如果您要切换主宠任务将消失，您确定要切换吗？")){
							return false;
						}
						var opt = {
							method: 'get',
							onSuccess: function(n) {
										if(n.responseText!='') {window.parent.Alert(n.responseText);}
										Display(bid,obj);
									},
							asynchronous:true        
						}
						var ajax=new Ajax.Request('../function/mcGate.php?op=change&id='+bid, opt);
					}else if(tt != "" && tt != 10){
						window.parent.Alert(tt);
						Display(bid,obj);
					}
    		 	},
     	asynchronous:true        
	}
	var ajax=new Ajax.Request('../function/mcGate.php?op=z&id='+bid, opt);
}


function Tag(a,b,c)
{
	if (a == 3) Hide('','none','none');
	else if(b==3) Hide('none','','none');
	else if(c==3) Hide('none','none','');	
	
	$('ta').style.zIndex=a;
	$('tb').style.zIndex=b;
	$('tc').style.zIndex=c;
}
function Hide(a,b,c)
{
	$('cet1').style.display=a;
	$('cet2').style.display=b;
	$('cet3').style.display=c;
}
function sjJn(sid)
{
	if (sid<1 || !validInt(sid))
	{
		window.parent.Alert('不可用的技能！');return;
	}
	var opt = {
    		 	method: 'get',
				 onSuccess: function(t) {
					if (parseInt(t.responseText)==0) window.parent.Alert('升级失败！');
					else if (parseInt(t.responseText)==2)
					{
						window.parent.Alert('缺少升级卷轴！');return;
					}
					else if (parseInt(t.responseText)==3)
					{
						window.parent.Alert('宝宝的等级太低，还不能领悟高层技能！');return;
					}
					else if (parseInt(t.responseText)==1)
					{
						window.parent.Alert('恭喜！您消耗了一个技能升级卷轴书，技能升级成功!');
						window.location.reload();
					}
    		 	},
    		 	on404: function(t) {
    		 	},
    		 	onFailure: function(t) {
    		 	},
    		 	asynchronous:true        
			}
	//window.status=		'../function/get.sjSkill.php?id='+sid+'&pid='+pid;
	var ajax=new Ajax.Request('../function/get.sjSkill.php?id='+sid+'&pid='+pid, opt);
}

function getJn()
{
	if ($('jlist').value<1 || !validInt($('jlist').value))
	{
		window.parent.Alert('请选择要学习的技能书！');return;
	}

	var opt = {
    		 	method: 'get',
				 onSuccess: function(t) {
					if (parseInt(t.responseText)==0) alert('学习失败');
					else if (parseInt(t.responseText)==2)
					{
						window.parent.Alert('缺少技能书！');return;
					}
					else if (parseInt(t.responseText)==3)
					{
						window.parent.Alert('宝宝的等级太低，还不能领悟高层技能！');return;
					}
					else if (parseInt(t.responseText)==4)
					{
						window.parent.Alert('技能与宝宝的五行不匹配！');return;
					}
					else if (parseInt(t.responseText)==10)
					{
						window.parent.Alert('您已经学习了该技能！');return;
					}
					else if (parseInt(t.responseText)==11)
					{
						window.parent.Alert('该技能是专属技能，此宠物不能学习哦');return;
					}
					else if (parseInt(t.responseText)==1)
					{
						window.parent.Alert('您消耗了一本技能书，学习技能成功!');
						window.location.reload();
					}
    		 	},
    		 	on404: function(t) {
    		 	},
    		 	onFailure: function(t) {
    		 	},
    		 	asynchronous:true        
			}
	var ajax=new Ajax.Request('../function/get.Skill.php?id='+$('jlist').value+'&pid='+pid, opt);
}
