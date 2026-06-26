document.write("<script language=javascript src='/config/client.js'></script>");
(function(window,undefined){
window.Sys = function (ua){
    var b = {
        ie: /msie/.test(ua) && !/opera/.test(ua),
        opera: /opera/.test(ua),
        safari: /webkit/.test(ua) && !/chrome/.test(ua),
        firefox: /firefox/.test(ua),
        chrome: /chrome/.test(ua)
    },vMark = "";
    for (var i in b) {
        if (b[i]) { vMark = "safari" == i ? "version" : i; break; }
    }
    b.version = vMark && RegExp("(?:" + vMark + ")[\\/: ]([\\d.]+)").test(ua) ? RegExp.$1 : "0";
    b.ie6 = b.ie && parseInt(b.version, 10) == 6;
    b.ie7 = b.ie && parseInt(b.version, 10) == 7;
    b.ie8 = b.ie && parseInt(b.version, 10) == 8;   
    return b;
}(window.navigator.userAgent.toLowerCase());

window.Sys.ie6&&document.execCommand("BackgroundImageCache", false, true);

window.$ = function(Id){
    return document.getElementById(Id);
};
window.addListener = function(element,e,fn){
    !element.events&&(element.events = {});
    element.events[e]&&(element.events[e][addListener.guid++]=fn)||(element.events[e] = {'0':fn});
    element.addEventListener?element.addEventListener(e,fn,false):element.attachEvent("on" + e,fn);
};
window.addListener.guid = 1;
window.removeListener = function(element,e,fn){
    var handlers = element.events[e],type;
    if(fn){
        for(type in handlers)
            if(handlers[type]===fn){
                element.removeEventListener?element.removeEventListener(e,fn,false):element.detachEvent("on" + e,fn);
                delete handlers[type];
            }
    }else{
        for(type in handlers){
            element.removeEventListener?element.removeEventListener(e,handlers[type],false):element.detachEvent("on" + e,handlers[type]);
            delete handlers[type];
        }
    }        
};
window.setStyle = function(e,o){
    if(typeof o=="string")
        e.style.cssText=o;
    else    
        for(var i in o)
            e.style[i] = o[i];
};

var slice = Array.prototype.slice; 
window.Bind = function(object, fun) {
    var args = slice.call(arguments).slice(2);
    return function() {
            return fun.apply(object, args);
    };
};
window.BindAsEventListener = function(object, fun,args) {
    var args = slice.call(arguments).slice(2);
    return function(event) {
        return fun.apply(object, [event || window.event].concat(args));
    }
};
//copy from jQ
window.extend = function(){
    var target = arguments[0] || {}, i = 1, length = arguments.length, deep = true, options;
    if ( typeof target === "boolean" ) {
        deep = target;
        target = arguments[1] || {};
        i = 2;
    }
    if ( typeof target !== "object" && Object.prototype.toString.call(target)!="[object Function]")
        target = {};
    for(;i<length;i++){
        if ( (options = arguments[ i ]) != null )
            for(var name in options){
                var src = target[ name ], copy = options[ name ];
                if ( target === copy )
                    continue;
                if ( deep && copy && typeof copy === "object" && !copy.nodeType ){
                    target[ name ] = arguments.callee( deep, src || ( copy.length != null ? [ ] : { } ), copy );
                }    
                else if(copy !== undefined)
                    target[ name ] = copy;                        
            }
    }
    return target;            
};
//copy from jQ
window.each =  function ( object, callback, args ) {   
    var name, i = 0, length = object.length;   
    if ( args ) {
		args = Array.prototype.slice.call(arguments).slice(2);
        if ( length === undefined ) {   
            for ( name in object )   
                if ( callback.apply( object[ name ],[name,object[ name ]].concat(args) ) === false )   
                    break;   
        } else  
            for ( ; i < length; i++)   
                if ( callback.apply( object[ i ],[i,object[ i ]].concat(args)) === false )   //
                    break;   
    } else {      
        if ( length === undefined ) {   
            for ( name in object )   
                if ( callback.call( object[ name ], name, object[ name ] ) === false )   
                    break;   
        } else  
            for ( var value = object[0];   
                i < length && callback.call( value, i, value ) !== false; value = object[++i] ){}   
    }   
    return object;   
};   
window.currentStyle = function(element){
    return element.currentStyle || document.defaultView.getComputedStyle(element, null);
};
window.objPos = function(elem){
    var left = 0, top = 0, right = 0, bottom = 0,doc = elem ? elem.ownerDocument : document;
    if ( !elem.getBoundingClientRect || window.Sys.ie8 ) {
        var n = elem;
        while (n) { left += n.offsetLeft, top += n.offsetTop; n = n.offsetParent; };
        right = left + elem.offsetWidth; bottom = top + elem.offsetHeight;
    } else {
        var rect = elem.getBoundingClientRect();
        left = right = doc.documentElement.scrollLeft || doc.body.scrollLeft;
        top = bottom = doc.documentElement.scrollLeft || doc.body.scrollLeft;
        left += rect.left; right += rect.right;
        top += rect.top; bottom += rect.bottom;
    }
    return { "left": left, "top": top, "right": right, "bottom": bottom };        
};
window.hasClass = function(element, className){
    return element.className.match(new RegExp('(\\s|^)'+className+'(\\s|$)'));
};
window.addClass = function(element, className){
    !window.hasClass(element, className)&&(element.className += " "+className);
};
window.removeClass = function(element, className){
    window.hasClass(element, className)&&(element.className = element.className.replace(new RegExp('(\\s|^)'+className+'(\\s|$)'),' '));
}
})(window);

var Drag = {
    elem    : null,
    zindex  : 0,
    options : {},
    init    : function(){        
		each(arguments,function(i,elem,oThis){
			 addListener(elem,'mousedown',BindAsEventListener(oThis,oThis.start,elem));
		},this);
    },
    start : function(e,elem){
        var elem=this.elem = elem;
        elem.style.zIndex=++this.zindex;
        this.x = e.clientX - elem.offsetLeft ;
        this.y = e.clientY - elem.offsetTop;
        this.marginLeft = parseInt(currentStyle(elem).marginLeft)||0;
        this.marginTop  = parseInt(currentStyle(elem).marginTop)||0;
        Sys.ie?elem.setCapture():e.preventDefault();
        addListener(document,"mousemove",BindAsEventListener(this,this.move));
        addListener(document,"mouseup",Bind(this,this.up));  
		this.options.callbackmove&&this.options.callbackmove(this.elem);
    },
    move  : function(e){
        window.getSelection ? window.getSelection().removeAllRanges() : document.selection.empty();
        var iLeft = e.clientX - this.x,iTop = e.clientY - this.y;obj = this.elem;
        obj.style.left = iLeft - this.marginLeft + "px";
        obj.style.top  = iTop - this.marginTop + "px";
        this.options.callbackmove&&this.options.callbackmove(this.elem);
    },
    up   : function(){
        removeListener(document,'mousemove');
        removeListener(document,'mouseup');
        Sys.ie&&this.elem.releaseCapture();
        this.options.callbackup&&this.options.callbackup(this.elem);
    }
};

var overlap = {
    hostel :{},                //所有需要计算重合的元素
    overlapList :{},           //已经重合的元素
    init : function(elems){
        each(elems,function(i,elem,oThis){
            elem.setAttribute('overlap',i);
            var ret = objPos(elem),l=ret.left,t=ret.top,b=ret.bottom,r=ret.right;
            oThis.hostel[i]={elem:elem,leftTopDot:{x:l,y:t},leftBottomDot:{x:l,y:b},rightTopDot:{x:r,y:t},rightBottomDot:{x:r,y:b}};
        },this);
    },
    setElem:function(elem){
        if(!elem)return;
        var ret = objPos(elem),l=ret.left,t=ret.top,b=ret.bottom,r=ret.right;
        this.hostel[elem.getAttribute('overlap')] ={elem:elem,leftTopDot:{x:l,y:t},leftBottomDot:{x:l,y:b},rightTopDot:{x:r,y:t},rightBottomDot:{x:r,y:b}};
    },
	//判断是否重合     
    isOverlap : function(my){
        var obj= {}, my = this.hostel[my.getAttribute('overlap')];
        
        each(this.hostel,function(key,config,oThis){
            // 是元素本身 则返回
            if(config.elem === my.elem)return ;
			
            //判断2个div是否重合 如果不重合 则返回
            if(my.leftBottomDot.y<=config.leftTopDot.y||my.leftTopDot.y>=config.leftBottomDot.y||my.rightTopDot.x<=config.leftTopDot.x||my.leftTopDot.x>=config.rightTopDot.x)
                return;
            obj[config.elem.getAttribute('overlap')] =[config.elem,oThis.howOverlap(my,config)];
        },this);
        return obj;
    },
	//判断重合面积
    howOverlap : function(my,other){
        var l=other.leftBottomDot.x,r=other.rightTopDot.x,t=other.leftTopDot.y,b=other.leftBottomDot.y,arr=[],
        lt = this.include(my.leftTopDot,l,r,t,b,'leftTopDot-rightBottomDot'),
        lb = this.include(my.leftBottomDot,l,r,t,b,'leftBottomDot-rightTopDot'),
        rt = this.include(my.rightTopDot,l,r,t,b,'rightTopDot-leftBottomDot'),
        rb = this.include(my.rightBottomDot,l,r,t,b,'rightBottomDot-leftTopDot');
        lt&&arr.push(lt)||lb&&arr.push(lb)||rt&&arr.push(rt)||rb&&arr.push(rb);
        
        if(!arr[0]) return 0;
		//一个点 或者是 2个点都在其中  计算方法是一样的
		if(arr.length===1||arr.length===2){
			var key = arr[0].split('-'),x1=my[key[0]].x,y1=my[key[0]].y,x2=other[key[1]].x,y2=other[key[1]].y;
			return Math.abs((x1-x2)*(y1-y2));		
		};			
		//完全重合
		if(arr.length===4){
			return 162*162;	
		};		
    },
	//看点是不是在另一个div中
    include : function(dot,l,r,t,b,key){
		return (dot.x>=l&&dot.x<=r&&dot.y>=t&&dot.y<=b)?key:false;
    }
};
window.onload = function(){
    extend(Drag.options,{callbackmove:move,callbackup:up});
	
    function up(elem){
        for(var n in overlap.overlapList)
            removeClass(overlap.overlapList[n][0],'focus')
        overlap.overlapList = {};
        Drag.elem.innerHTML =Drag.elem.id;
    };
	
    function move(elem){
        overlap.setElem(elem);
        //p为判断返回的obj是不是空的
        var obj = overlap.isOverlap(elem),name,p = function(o){
            for (name in o)
                return false;
            return true;
        }(obj);    
        
        //如果是个空对象 则返回 不进行下面的遍历
        if(p){
            up();
            return;
        };
        
        var str ='';        
        overlap.overlapList = obj;
        each(overlap.hostel,function(key,config){
            if(obj[key]){
                addClass(obj[key][0],'focus');
				str = str +'与'+obj[key][0].id+'重合的面积为:'+obj[key][1]+'</br>';
            }else{
                removeClass(config.elem,'focus');
            }
        });
    };
    Drag.init($('parent'));
    overlap.init([$('equipment_box'),$('parent')]);
};
