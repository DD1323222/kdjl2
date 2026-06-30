// JavaScript Document
var dtips = document.createElement('DIV');

function sel(obj)
{
	if(selid!=0) selid.style.backgroundColor='#fff';
	selid=obj;
	obj.style.backgroundColor='#DFD496';
	try
	{
		var pro = $(obj.id.replace("t","s")).innerHTML;
		$('num').value= parseInt(pro);
	}
	catch(e)
	{
		$('num').value = "";
	}
}
function buy(channel){
	//window.parent.Alert('神秘商店暂时未开门!');
    // return;
	//
	var nums = $('num').value;
	if(nums < 1){
		nums = $('limitnum').value;
	}
	if(bid == 0 ){window.parent.window.parent.Alert('请先选择要购买的物品。');return;}
	else if(!validInt(nums)){window.parent.Alert('数量必须是数字!');return;}
	else if(nums<1 || nums>100){window.parent.Alert('购买数量范围是1-100之间的整数！');return;}
	else{
		var sump = price*parseInt(nums);
		
		if (sump==0) {window.parent.Alert('数据错误！');return;}
		if(confirm('你确定购买选中的物品吗? 总共将花费您 '+sump+'元宝！'))
		{
			var opt = {
    		 	method: 'get',
				 onSuccess: function(t) {
			 		var n=parseInt(t.responseText);
					if(n==10) {window.parent.Alert('您的元宝不够，可点右下角的游戏充值增加元宝！');return;}
					else if(n==4) {window.parent.Alert('您的包裹空间不足！');return;}
					else if(n==101) {window.parent.Alert('该物品还没上架或者已经下架！');return;}
					else if(n==0) {ajaxfun();window.parent.Alert('买入物品成功!');return;}//refresh bag.
					else {window.parent.Alert(t.responseText);return;}
    		 	},
    		 	on404: function(t) {
    		 	},
    		 	onFailure: function(t) {
    		 	},
    		 	asynchronous:true        
			}
			//window.status='../function/smbuyGate.php?bid='+bid+'&n='+nums;
			channel = channel == 'limit' ? 'limit' : 'yb';
			var ajax=new Ajax.Request('../function/smbuyGate.php?bid='+bid+'&n='+nums+'&channel='+channel, opt);
		}
		else return;
	}
}

function ajaxfun()
{
	var opt = {
     	method: 'get',
		onSuccess: function(t) {
			 		$('mybag').innerHTML=t.responseText;
    		 	},
     	asynchronous:true        
	}
	var ajax=new Ajax.Request('../function/getBag.php', opt);
}

function sell(){
	var nums = $('num').value; 
	if(bid == 0 ){window.parent.Alert('请先选择要卖出的物品。');return;}
	else{
	    if(!validInt(nums)) {window.parent.Alert('数量只能是数字！');return false;}
		else if(nums<1 || nums>100){window.parent.Alert('购买数量范围是1-100之间的整数！');return;}
		
		if (types==2 && nums!=1)
		{
			window.parent.Alert('不可叠加物品只能单一卖出');return;
		}

		var sump = price*parseInt(nums);
		if(confirm('你确定卖出选中的物品吗? 总共将获得 '+sump+' 金币！'))
		{
			var opt = {
    		 	method: 'get',
				 onSuccess: function(t) {
			 		var n=parseInt(t.responseText);
					if(n==0) {ajaxfun();window.parent.Alert('卖出物品成功!');}
					else if(n==10) window.parent.Alert('您的背包中没有足够的物品可以卖出！');
					else window.parent.Alert('卖出失败!');
    		 	},
    		 	on404: function(t) {
    		 	},
    		 	onFailure: function(t) {
    		 	},
    		 	asynchronous:true        
			}
			var ajax=new Ajax.Request('../function/sellBag.php?bid='+bid+'&n='+nums, opt);
		}
		else return;
	}
}
