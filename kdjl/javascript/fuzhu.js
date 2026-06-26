
function openFuzhu(){
    var fuzhuDiv = document.getElementById("fuzhuDiv");
    if(fuzhuDiv.style.visibility=='visible') fuzhuDiv.style.visibility='hidden';
    else fuzhuDiv.style.visibility='visible';
}

var intervalId=null;

function startOrStop(){
    var mytime = new Date();
    var mytimeStr = mytime.getHours()+":"+mytime.getMinutes()+":"+mytime.getSeconds();
    
    var autoCatchInput = document.getElementById("autoCatchInput");
    var stopAutoCatchInput = document.getElementById("stopAutoCatchInput");
    if(autoCatchInput.style.display == 'block'){
        autoCatchInput.style.display = 'none';
        stopAutoCatchInput.style.display = 'block';
        
		var fzContentDiv = document.getElementById("fuzhuContents")
        var fzContentstr = fzContentDiv.innerText;
        fzContentDiv.innerText = mytimeStr + ' 开始自动抓涅槃兽' + '\n' + fzContentstr;
    }else{
        autoCatchInput.style.display = 'block';
        stopAutoCatchInput.style.display = 'none';
        if(intervalId!=null){
            clearInterval(intervalId);
            intervalId = null;
        }
        
		var fzContentDiv = document.getElementById("fuzhuContents")
        var fzContentstr = fzContentDiv.innerText;
        fzContentDiv.innerText = mytimeStr + ' 停止抓涅槃兽' + '\n' + fzContentstr;
    }
}

function autoCatch(){
    startOrStop();
    if(intervalId!=null){
        clearInterval(intervalId);
        intervalId = null;
    }
    intervalId = setInterval(catchBB,37000);
}

function catchBB(){
    var opt = {
     	method: 'get',
		onSuccess: function(t) {
            var mytime = new Date();
            var mytimeStr = mytime.getHours()+":"+mytime.getMinutes()+":"+mytime.getSeconds();
            
		    var resStr = '';
		    if(t.responseText != '1' && t.responseText !='2' && t.responseText !='7'){
		        if(t.responseText == '3'){
		            resStr = "牧场已满，请留出足够的位置！";
		        }else if(t.responseText == '4'){
		            resStr = "没有可用的精灵球！";
		        }else if(t.responseText == '5'){
		            resStr = "请开启冰滩地图后再尝试！";
		        }else if(t.responseText == '6'){
		            resStr = "对不起，你还不能使用该功能！";
		        }else{
		            resStr = 'error'+t.responseText;
		        }
                if(intervalId!=null){
                    clearInterval(intervalId);
                    intervalId = null;
                }
                startOrStop();
            }else if(t.responseText == '1'){
                resStr = '++++捕捉成功++++';
		    }else if(t.responseText == '2'){
                resStr = '----捕捉失败----';
		    }else if(t.responseText == '7'){
		        resStr = "未遇到涅槃兽！";
		    }
		    var fzContentDiv = document.getElementById("fuzhuContents")
            var fzContentstr = fzContentDiv.innerText;
            fzContentDiv.innerText = mytimeStr + ' ' + resStr + '\n' + fzContentstr;
    	},
     	asynchronous:true        
	}
	var ajax=new Ajax.Request('../function/myFunction/autoCatch.php?', opt);
}