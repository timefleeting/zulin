/*IE9和IE9以前的版本不支持classList属性*/
if(!("classList" in document.documentElement)) {  
    Object.defineProperty(HTMLElement.prototype, 'classList', {  
        get: function() {  
            var self = this;  
            function update(fn) {  
                return function(value) {  
                    var classes = self.className.split(/\s+/g),  
                        index = classes.indexOf(value);  
                    fn(classes, index, value);  
                    self.className = classes.join(" ");  
                }  
            }  
            return {                      
                add: update(function(classes, index, value) {  
                    if (!~index) classes.push(value);  
                }),   
                remove: update(function(classes, index) {  
                    if (~index) classes.splice(index, 1);  
                }),  
                toggle: update(function(classes, index, value) {  
                    if (~index)  
                        classes.splice(index, 1);  
                    else  
                        classes.push(value);  
                }),    
                contains: function(value) {  
                    return !!~self.className.split(/\s+/g).indexOf(value);  
                },  
                item: function(i) {  
                    return self.className.split(/\s+/g)[i] || null;  
                }  
            };  
        }  
    });  
} 

/*核心JS*/
;(function (document) {
    "use strict";
    var model = (function(document){
                    var readyRE           = /complete|loaded|interactive/; //匹配document.readyState中的状态
                    var idSelectorRE      = /^#([\w-]+)$/;                 //匹配id               
                    var classSelectorRE   = /^\.([\w-]+)$/;                //匹配class[.(\w及-)]
                    var tagSelectorRE     = /^[\w-]+$/;                    //匹配tag标签
                    var translateRE       = /translate(?:3d)?\((.+?)\)/;   //transform样式(key),(value)匹配
                    var translateMatrixRE = /matrix(3d)?\((.+?)\)/;        //transform中的Matrix(矩阵)
                    /** var obj = model(selector,context);
                     *  selector[
                     *      1,节点对象
                     *     |2,回调函数闭包
                     *     |3,节点选择器]
                     *  context [默认:document,节点容器(作用于selecotr3)]
                    */
                    var $ = function(selector, context){
                            context = context || document;
                            if (!selector){
                                return wrap();
                            }
                            if (typeof selector === 'object')
                                if ($.isArrayLike(selector)) {
                                    return wrap($.slice.call(selector), null);
                                } else { //不像数组的对象，将包装成单个数组
                                    return wrap([selector], null);
                                }
                            if (typeof selector === 'function')
                                return $.ready(selector);
                            if (typeof selector === 'string') {
                                try {
                                    selector = selector.trim();
                                    if (idSelectorRE.test(selector)) {
                                        var found = document.getElementById(RegExp.$1);
                                        return wrap(found ? [found] : []);
                                    }
                                    return wrap($.qsa(selector, context), selector);
                                } catch (e) {}
                            }
                            return wrap();
                    };
                    
                    var wrap = function(dom, selector) {
                        dom = dom || [];
                        /* Object.setPrototypeOf(obj, prototype)方法设置一个指定的对象的原型 ( 即, 内部[[Prototype]]属性）到另一个对象或  null。
                           obj:要设置其原型的对象。prototype:该对象的新原型(一个对象 或 null).
                           相对于 Object.prototype.__proto__ ，它被认为是修改对象原型更合适的方法
                         */
                        Object.setPrototypeOf(dom, $.fn);
                        dom.selector = selector || '';
                        return dom;
                    };
                    $.fn = { //实例对象
                        each: function(callback) { // $ -> dom(likeArray) 遍历
                            [].every.call(this, function(el, idx) { //every 检测数组所有元素是否都符合指定条件(只是作为遍历使用)
                                return callback.call(el, idx, el) !== false;
                            });
                            return this;
                        },
                    };
                    var class2type = {};
                    Array.prototype.forEach.call(['Boolean', 'Number', 'String', 'Function', 'Array', 'Date', 'RegExp', 'Object', 'Error'], function(item, index) {
                        class2type["[object " + item + "]"] = item.toLowerCase();
                    });
                    if (window.JSON) {
                        $.parseJSON = JSON.parse;
                    }
                    /**
                     * extend( target<args[0]>,args[1],args[2],args[3],... )  //合并args[...]数据到target中修改target结构(不想修改结构args[0]可为{}再赋值),key相同，后者覆盖前者
                     * 如果只有一个参数,则该参数合并到调用extend方法的对象($或fn)中去[扩展函数要使用对象{'a':function(){}}]
                     * @param args[0] boolen值(true:深度合并),args[1]为target.否则args[0]为target
                     * @param {obj|functionObj} target  目标合并数据
                     * @param {type} deep    深度合并
                     * @returns {unresolved}
                     */
                    $.extend = function() { //jquery3
                        var options, name, src, copy, copyIsArray, clone,
                            target = arguments[ 0 ] || {},
                            i = 1,
                            length = arguments.length,
                            deep = false;
                        if ( typeof target === "boolean" ) {// Handle a deep copy situation
                            deep = target;
                            target = arguments[ i ] || {}; // Skip the boolean and the target
                            i++;
                        }
                        if ( typeof target !== "object" && !$.isFunction( target ) ) {// Handle case when target is a string or something (possible in deep copy)
                            target = {};
                        }
                        if ( i === length ) {// Extend jQuery itself if only one argument is passed(如果只有一个参数,则该参数合并到调用extend方法的对象($或fn)中去)
                            target = this;i--;
                        }
                        for ( ; i < length; i++ ) {
                            if ( ( options = arguments[ i ] ) != null ) { // Only deal with non-null/undefined values
                                for ( name in options ) { // Extend the base object
                                    src = target[ name ];
                                    copy = options[ name ];
                                    if ( target === copy ) { // Prevent never-ending loop
                                        continue;
                                    }
                                    // Recurse if we're merging plain objects or arrays (deep:true 深度递归合并的对象与数组)
                                    if ( deep && copy && ( $.isPlainObject( copy ) || ( copyIsArray = $.isArray( copy ) ) ) ) {
                                        if ( copyIsArray ) {
                                            copyIsArray = false;
                                            clone = src && $.isArray( src ) ? src : [];
                                        } else {
                                            clone = src && jQuery.isPlainObject( src ) ? src : {};
                                        } 
                                        target[ name ] = $.extend( deep, clone, copy );// Never move original objects, clone them(不覆盖原始数据,只是克隆)
                                    } else if ( copy !== undefined ) {// Don't bring in undefined values
                                        target[ name ] = copy;
                                    }
                                }
                            }
                        }
                        return target; // Return the modified object
                    };
                    $.noop   = function() {}; //空函数对象
                    $.slice  = [].slice;
                    $.filter = [].filter;
                    $.type = function(obj) {
                        return obj == null ? String(obj) : class2type[{}.toString.call(obj)] || "object";
                    };
                    $.isArray = Array.isArray || function(object) { return object instanceof Array; };
                    $.isArrayLike = function(obj) {
                        var length = !!obj && "length" in obj && obj.length;
                        var type = $.type(obj);
                        if (type === "function" || $.isWindow(obj)) {
                            return false;
                        }
                        return type === "array" || length === 0 ||
                            typeof length === "number" && length > 0 && (length - 1) in obj;
                    };
                    $.isWindow = function(obj) {return obj != null && obj === obj.window;};
                    $.isObject = function(obj) {return $.type(obj) === "object";};
                    $.isPlainObject = function(obj) {return $.isObject(obj) && !$.isWindow(obj) && Object.getPrototypeOf(obj) === Object.prototype;};
                    $.isEmptyObject = function(o) {  //是否为空对象
                        for (var p in o) {
                            if (p !== undefined) {
                                return false;
                            }
                        }
                        return true;
                    };
                    $.isFunction = function(value) {return $.type(value) === "function";};//是否为方法
                    $.ready = function(callback) { 
                        if (readyRE.test(document.readyState)) {
                                callback($);
                        } else {
                            document.addEventListener('DOMContentLoaded', function() {
                                callback($);
                            }, false);
                        }
                        return this;
                    };
                    /**
                     * each
                     * @param {type} elements
                     * @param {type} callback 回调函数 function(index,item){}
                     * @param hasOwnProperty
                     * @returns 
                     */
                    $.each = function(elements, callback, hasOwnProperty){
                        if (!elements) {
                            return this;
                        }
                        if (typeof elements.length === 'number') {
                            [].every.call(elements, function(el, idx) {
                                return callback.call(el, idx, el) !== false;
                            });
                        } else {
                            for (var key in elements) {
                                if (hasOwnProperty) {
                                    if (elements.hasOwnProperty(key)) {
                                        if (callback.call(elements[key], key, elements[key]) === false) return elements;
                                    }
                                } else {
                                        if (callback.call(elements[key], key, elements[key]) === false) return elements;
                                }
                            }
                        }
                        return this;
                    };
                    $.focus = function(element){
                         setTimeout(function() {element.focus();}, 0);
                    };
                    $.getStyles = function(element, property) {
                        var styles = element.ownerDocument.defaultView.getComputedStyle(element, null);
                        if (property) {
                            return styles.getPropertyValue(property) || styles[property];
                        }
                        return styles;
                    };
                    /**
                     * parseTranslate
                     * @param {type} translateString [translate样式属性值]
                     * @param {type} position
                     * @returns {Object}
                     */
                    $.parseTranslate = function(translateString, position) {
                        var result = translateString.match(translateRE || '');
                        if (!result || !result[1]) {
                            result = ['', '0,0,0'];
                        }
                        result = result[1].split(",");
                        result = {
                            x: parseFloat(result[0]),
                            y: parseFloat(result[1]),
                            z: parseFloat(result[2])
                        };
                        if (position && result.hasOwnProperty(position)) {
                            return result[position];
                        }
                        return result;
                    };
                    /**
                     * parseTranslateMatrix
                     * @param {type} translateString
                     * @param {type} position
                     * @returns {Object}
                     */
                    $.parseTranslateMatrix = function(translateString, position) {
                        var matrix = translateString.match(translateMatrixRE);
                        var is3D = matrix && matrix[1];
                        if (matrix) {
                            matrix = matrix[2].split(",");
                            if (is3D === "3d")
                                matrix = matrix.slice(12, 15);
                            else {
                                matrix.push(0);
                                matrix = matrix.slice(4, 7);
                            }
                        } else {
                            matrix = [0, 0, 0];
                        }
                        var result = {
                            x: parseFloat(matrix[0]),
                            y: parseFloat(matrix[1]),
                            z: parseFloat(matrix[2])
                        };
                        if (position && result.hasOwnProperty(position)) {
                            return result[position];
                        }
                        return result;
                    };
                    $.qsa = function(selector, context) {
                        context = context || document;
                        return $.slice.call(classSelectorRE.test(selector) ? context.getElementsByClassName(RegExp.$1) : tagSelectorRE.test(selector) ? context.getElementsByTagName(selector) : context.querySelectorAll(selector));
                    };

                    var preservedScriptAttributes = {
                        type: true,
                        src: true,
                        noModule: true
                    };

                    $.DOMEval = function( code, doc, node ) {
                        doc = doc || document;
                        var i,
                            script = doc.createElement( "script" );
                        script.text = code;
                        if ( node ) {
                            for ( i in preservedScriptAttributes ) {
                                if ( node[ i ] ) {
                                    script[ i ] = node[ i ];
                                }
                            }
                        }
                        doc.head.appendChild( script ).parentNode.removeChild( script );
                    }

                    $.jsEval = function(content){
                        var reJs  = new RegExp('<script[\\s\\S]*?>([\\s\\S]*?)<\/script>','ig');
                        var reRes;
                        while( (reRes = reJs.exec(content))!==null ){
                        		$.DOMEval(reRes[1]);
                        }
                    }

         return $;
    })(document);
    window.model=model; //内:原型对象操作原型属性。外:实例后操作fn属性
}(document));
/*
 * 系统探测
 */
(function($, window) {
    function detect(ua) {
        this.os = {};  //原型属性添加
        var funcs = [
            function() { //wechat
                var wechat = ua.match(/(MicroMessenger)\/([\d\.]+)/i);
                if (wechat) { //wechat
                    this.os.wechat = {
                        version: wechat[2].replace(/_/g, '.')
                    };
                }
                return false;
            },
            function() { //android
                var android = ua.match(/(Android);?[\s\/]+([\d.]+)?/);
                if (android) {
                    this.os.android = true;
                    this.os.version = android[2];

                    this.os.isBadAndroid = !(/Chrome\/\d/.test(window.navigator.appVersion));
                }
                return this.os.android === true;
            },
            function() { //ios
                var iphone = ua.match(/(iPhone\sOS)\s([\d_]+)/);
                if (iphone) { //iphone
                    this.os.ios = this.os.iphone = true;
                    this.os.version = iphone[2].replace(/_/g, '.');
                } else {
                    var ipad = ua.match(/(iPad).*OS\s([\d_]+)/);
                    if (ipad) { //ipad
                        this.os.ios = this.os.ipad = true;
                        this.os.version = ipad[2].replace(/_/g, '.');
                    }
                }
                return this.os.ios === true;
            },
            function(){
                 this.os.ua = ua;
                 return false;
            }
        ];
        [].every.call(funcs, function(func) {
            return !func.call($);
        });
    }
    detect.call($, navigator.userAgent);
})(model, window);

(function($){

	var delegates = {};

	var _mid = 1;

	var muid  = function(obj) {
			return obj && (obj._mid || (obj._mid = _mid++));
	};

    var removeEvent = function (eventThis,event,callback ){
            var mid = eventThis._mid || false;
            if( mid==false||mid==null){
                return false;
            }
            if( !$.isEmptyObject(delegates[mid]) ){
                eventThis.removeEventListener(event,delegates[mid].callback);
                var delegatesCopy = {};
                for(var i in delegates){
                        if(i==mid) continue;
                        delegatesCopy[i] = delegates[i];
                }
                delegates = {};
                delegates = delegatesCopy;
            }
    }
   // var test = [];
	var addEvent = function( eventThis,event,callback,selector){
			muid( eventThis );
			var mid = eventThis._mid || false;
			if( mid==false||mid==null){
				return false;
			}
			if( $.isEmptyObject(delegates[mid]) ){
					delegates[mid] = { self:eventThis,event:event,callback:callback };
					eventThis.addEventListener(event,delegates[mid].callback);
			}else{ //存在相同则注销,并重新绑定执行
					removeEvent(delegates[mid]['self'],delegates[mid]['event'],delegates[mid]['callback']);
                    addEvent(eventThis,event,callback);
			}
	}

    /*
     * 注意callback内使用外部变量的作用域,例如在循环体内调用的情况
     */
    $.fn.on = function(event,selector,callback){
        return this.each(function(inx,item){ 
        			var elements = {};
        			if(typeof selector=='function'){
        				elements =  [item];
        				callback = selector;
        			}else{
        				elements =  item.querySelectorAll(selector) || {};
        			}
                    $.each(elements,function(){addEvent(this,event,callback,selector);});     
        });
    };

    /*off待优化,应该需要注销delegates事件,作成注册形式*/
    $.fn.off =function(event,selector,callback){
        return this.each(function(inx,item){
                     var elements =  item.querySelectorAll(selector) || {};
                    try{
                        $.each(elements,function(){
                               removeEvent(this,event);
                        });
                    }catch(e){}    
        });
    };

    $.fn.html = function(content){
        var html = content || '';
        if(html===true){
	 			this.each(function(inx,item){
	                try{html = item.innerHTML;}catch(e){}    
	            });
	            return html;
        }else{
	        	this.each(function(inx,item){
	                try{item.innerHTML = content;$.jsEval(content);}catch(e){}    
	            });
	            return html;	
        }
    }
    /*
     * 获取父级元素集层级中含有某class名的指定父级元素
     * 容器只取第一个
     * @params cls 元素class名,只支持以class作为查找定位
     */
    $.fn.parents = function(cls){
    		var self = this[0] || '',cls=cls || '';selfParent=self.parentNode;
    		if(cls && $.isObject(self)){
    			while(selfParent){
    				 selfParent = selfParent.parentNode;
    				 if(selfParent==document)
    				 		break;
    				 if(selfParent.classList.contains(cls)){
    				 		return selfParent;
    				 }
    			}	
    		}
    		return '';
    }

})(model);

;(function($,window){
	var _jsArr 	= {};
	$.jsLoader  = function(scriptName,callback){
		if(!_jsArr[scriptName]) {
			_jsArr[scriptName] = true;
			var body 		= document.getElementsByTagName('body')[0];
			var script 		= document.createElement('script');
			script.type 	= 'text/javascript';
			script.src 		= scriptName;
			// then bind the event to the callback function
			// there are several events for cross browser compatibility
			// script.onreadystatechange = callback;
			script.onload = callback;
			// fire the loading
			body.appendChild(script);

		}else if(callback){
			callback();
		}
	}
	var _cssArr = {};
	$.cssLoader = function(cssUrl){
		if(!_cssArr[cssUrl]) {
			_cssArr[cssUrl] = true;
			var head 		= document.getElementsByTagName('head')[0];
			var link 		= document.createElement('link');
			link.type 	    = 'text/css';
			link.rel 		= 'stylesheet';
			link.href = cssUrl;
            head.appendChild(link);
		}
	}
})(model,window);


(function($,window,undefined){
    /*
     * 将html内容添加至节点
     * 返回添加的节点,正常不会有多个,如果有,只取最后一个
     * 注意 appendChild 是先剪切再插入.
     */    
    $.fn.append  = function(html){
        var frag = document.createDocumentFragment(); 
        var d    = document.createElement('div');
        $(d).html(html);
        $.each(d.childNodes,function(index,item){
               frag.appendChild(item.cloneNode(true));
        }); 
        var appendChild = {};
        $.each(this,function(index,item){
               item.appendChild(frag);
               appendChild = item.lastElementChild;
        }); 
        return appendChild;
    }
    $.fn.prepend = function(html,refererNode){
    	var frag = document.createDocumentFragment(); 
        var d    = document.createElement('div');
        $(d).html(html);
        $.each(d.childNodes,function(index,item){
               frag.appendChild(item.cloneNode(true));
        }); 
        var appendChild = {};
        $.each(this,function(index,item){
               var existingItem = refererNode || item.childNodes[0]||null;
               item.insertBefore( frag,existingItem );
               if(existingItem!=null&&existingItem!=''){
                    appendChild = existingItem.previousSibling;
               }else{
                     appendChild = item.firstElementChild;
               }     
        }); 
        return appendChild;
    }
    $.fn.parentAppend  = function(html){
        var frag = document.createDocumentFragment(); 
        var d    = document.createElement('div');
        $(d).html(html);
        $.each(d.childNodes,function(index,item){
               frag.appendChild(item.cloneNode(true));
        }); 
        var appendChild = {};
        $.each(this,function(index,item){
               item.parentNode.appendChild(frag);
               appendChild = item.parentNode.lastElementChild;
        }); 
        return appendChild;
    }
    /*
     * 移除空间内节点对象,否则移除自身对象
     */
    $.fn.remove = function( childNode ){
            var node = childNode || {};
            if( node==''||node==null ){
                    return false;
            }
            $.each(this,function(index,item){
            	  if( $.isEmptyObject(node )){
            	  		item.parentNode.removeChild(item);
            	  }else{
            	  		item.removeChild(node);
            	  } 
            });
    }
})(model,window);

/*打开新地址*/
(function($,window,undefined){
    $.open = function(url,data,createNew){
           var param = '';
            for( var i in data ){
                    param += i+'='+data[i]+'&';
            }
            location.href = url + (param!=''? '?'+this.trim(param,'&'):''); 
    }
})(model,window);

/*
 * 所在节点对象
 * 获取节点对象(param)*-属性数据
 */
(function($,window,undefined){
    $.fn.queryParam = function(key){
            var data = {};
            var key = key ? key : 'param';
            this.each(function(){
                   var self = this;
                   $.each(self.attributes,function(index,item){
                            var matches= new RegExp("^"+key+"-(.*)$","i").exec(item.name),param;
                            //var matches= /^popups-(.*)$/i.exec(item.name),param;
                            if( matches != null && matches != '' ){
                                    param = matches[1] || false;
                                    data[param] = item.value;
                            }
                   });
                   
            });
            return data;
    }
})(model,window);
/*
* 自定义获取表单数据
* formEle 项目选择器   **必须
* param-key            **必须
* data-type            (表单元素节点类型)
* param-key2d          (扩展第二层数据元素)
* param-key3d          (扩展第三层数据元素) 
* data-value                           
 */
(function($,window,undefined){
    $.fn.formData = function(){
            var data = {};
            this.each(function(){
                    var self = this;
                    var nodeName  = self.nodeName || false; //节点名称
                    if( nodeName == false ) return true;
                    var key  = self.getAttribute('param-key');
                    var key,key2d,key3d,type=self.getAttribute('data-type'),value=self.value,fieldvalue=false;
                        try{
                            key   = self.getAttribute('param-key'); 
                            key2d = self.getAttribute('param-key2d');
                            key3d = self.getAttribute('param-key3d');
                            if( type == '' || type == null || type == 'undefined') {
                                type = nodeName.toLocaleLowerCase();
                            }
                            if( value == '' || value == null || value == 'undefined'){
                                value = self.getAttribute('data-value');
                            }
                            value = value ? value : '';
                            switch( type ){
                                case 'text':case 'input':case 'select':case 'textarea':case 'password':case 'div':case 'span':
                                            fieldvalue = value;
                                break;
                                case 'radio':case 'checkbox':
                                    if( self.classList.contains('on') ){
                                            fieldvalue = value;
                                    }else{
                                            fieldvalue = self.checked?value:false;
                                    }
                                    break;
                                default:
                                    fieldvalue = false;
                                    break;
                            }
                        }catch(e){}
                        if( fieldvalue === false ) return true;
                        if( typeof data[key] == 'undefined' ){
                                data[key] = {};
                        }
                        if(key2d!=''&&key2d!=null){
                        		if( typeof data[key][key2d] == 'undefined' ){
                                  		data[key][key2d] = {}; //注意会被初始化
                        		}
                                if( key3d!='' && key3d!=null ){ 
                                        data[key][key2d][key3d] = fieldvalue;
                                }else{
                                        data[key][key2d] = fieldvalue;
                                }
                        }else{
                                data[key] = fieldvalue;
                        }
            });
            return data;
    }
})(model,window);

/* 
 * text   需左右清除字符串
 * symbol 符号 默认是空格
 * arrow  方向[left:左,right:右]
 */
(function($,window){
    $.trim = function(text,symbol,arrow){
            var symbol = (text.length>0) ? symbol : '\s*';
            var pattern = new RegExp("(^"+symbol+")|("+symbol+"$)","g"); 
            if(arrow=='left'){
                    pattern = new RegExp("(^"+symbol+")","g");
            }else if(arrow=='right'){
                    pattern = new RegExp("("+symbol+"$)","g");
            }
            return text.replace( pattern,'' );
    }
})(model,window);
/*
 * 提示信息
 */
(function($,window){
    $.notice = function(isMsg,options){
        var config = {
               mask_index:1000, 
               msg:"异常",
               type: 0,
               timeout:1500,
               width:'100%',
               top  : '35%',
               color: '#FFFFFF',
               callback:false,
        };
        config['msg'] = isMsg ? isMsg : '异常';
        for (var i in options) {
                config[i] = options[i];
        }
        config['notice_index'] = parseInt( config.mask_index ) + 1;
        var errorImg   = "data:image/png;base64,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";
        var successImg = "data:image/png;base64,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";
        var toastImage;
        if(config.type==0){
                toastImage = errorImg;
        }else{
                toastImage = successImg;
        }
        var html = '';
            html+='<div class="mask_transparent" style="position:fixed;z-index:'+config.mask_index+';top:0;right:0;left:0;bottom:0;"></div>';
            html+='<div class="notice" style="position:fixed;z-index:'+config.notice_index+';width:'+config.width+';top:'+config.top+';margin:0;text-align:center;color:'+config.color+';">';
            html+='<div style="background:rgba(17,17,17,0.7);border-radius:5px;padding:0.5rem;width:50%;margin:0 auto;">';
            html+='<i class="loading icon_toast" style="background-size: 100%;display: inline-block;margin: 30px 0 0;width: 38px;height: 38px;vertical-align: baseline;background-image:url('+toastImage+');"></i>';
            html+='<p class="toast_content" style="margin: 15px 0 15px;padding:0;color: #FFFFFF;">'+config.msg+'</p>';
            html+='</div>';
            html+='</div>';
            var frag = document.createDocumentFragment(); 
            var d=document.createElement('div');
            d.setAttribute('id','statusToast');
            //d.innerHTML=html;
            $(d).html(html);
            frag.appendChild(d);
            document.body.appendChild(frag); 
            setTimeout(function(){
                    try{
                        document.body.removeChild(d);  
                        config.callback && config.callback.apply(this, arguments);
                    }catch(e){}
            },config.timeout);  
    }
})(model,window);
/*
 * 弹出层
 */
(function($,window){
    $.popups = function(options){
                var config   = {};
                var flag     = 'popups';
                var minIndex = 900;
                var maxIndex = 1000;
                /*更多效果参考 animate.css*/
                var animated      ='.animated {-webkit-animation-duration:1s;animation-duration: 1s;-webkit-animation-fill-mode: both;animation-fill-mode: both;}';
                var bounceInDown  ='@-webkit-keyframes bounceInDown {from,60%,75%,90%,to {-webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);} 0% {opacity: 0;-webkit-transform: translate3d(0, -3000px, 0);transform: translate3d(0, -3000px, 0);}60% {opacity: 1;-webkit-transform: translate3d(0, 25px, 0);transform: translate3d(0, 25px, 0);}75% {-webkit-transform: translate3d(0, -10px, 0);transform: translate3d(0, -10px, 0);}90% {-webkit-transform: translate3d(0, 5px, 0);transform: translate3d(0, 5px, 0);}to {-webkit-transform: translate3d(0, 0, 0);transform: translate3d(0, 0, 0);}}';
                    bounceInDown +='@keyframes bounceInDown {from,60%,75%,90%,to {-webkit-animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);animation-timing-function: cubic-bezier(0.215, 0.61, 0.355, 1);}0% {opacity: 0; -webkit-transform: translate3d(0, -3000px, 0);transform: translate3d(0, -3000px, 0);}60% {opacity: 1;-webkit-transform: translate3d(0, 25px, 0);transform: translate3d(0, 25px, 0);}75% {-webkit-transform: translate3d(0, -10px, 0);transform: translate3d(0, -10px, 0);}90% { -webkit-transform: translate3d(0, 5px, 0);transform: translate3d(0, 5px, 0);}to {-webkit-transform: translate3d(0, 0, 0);transform: translate3d(0, 0, 0);}}';
                    bounceInDown +='.bounceInDown {-webkit-animation-name: bounceInDown;animation-name: bounceInDown;}';
                var bounceOutDown ='@-webkit-keyframes bounceOutDown{20% {-webkit-transform: translate3d(0, 10px, 0);transform: translate3d(0, 10px, 0);}40%,45% {opacity: 1;-webkit-transform: translate3d(0, -20px, 0);transform: translate3d(0, -20px, 0);}to{opacity: 0;-webkit-transform: translate3d(0, 2000px, 0);transform: translate3d(0, 2000px, 0);}}';
                    bounceOutDown+='@keyframes bounceOutDown {20% {-webkit-transform: translate3d(0, 10px, 0);transform: translate3d(0, 10px, 0);}40%,45% {opacity: 1;-webkit-transform: translate3d(0, -20px, 0);transform: translate3d(0, -20px, 0);}to {opacity: 0;-webkit-transform: translate3d(0, 2000px, 0);transform: translate3d(0, 2000px, 0);}}';
                    bounceOutDown+='.bounceOutDown {-webkit-animation-name: bounceOutDown;animation-name: bounceOutDown;}';
                var effects='<style>'+animated+bounceInDown+bounceOutDown+'</style>';
                /*css end*/
                var init = function(options){
                        this.options = {
                                width:'100%',
                                height:'100%',
                                top:'0',
                                left:'0',
                                background:'#ffffff',
                                maskBackground:'rgba(17,17,17,0.7)',
                        }
                        for(var i in options){
                                 this.options[i] = options[i];
                        }
                        config = this.options;
                        return this;
                }
                init.prototype.open = function( html ){
                        var self = this;
                        var count = document.querySelectorAll('.'+flag).length;
                        var zIndex=minIndex+count*10;
                        if(zIndex>maxIndex){
                                $.fn.notice({msg:'窗口太多了！'});
                                return false;
                        }
                        var maskzindex=zIndex;
                        var bodyzindex=maskzindex+1;
                        var modalStyle = "";
                            for(var i in config){
                            		if(i=='maskBackground') continue;
                                    modalStyle += i+":"+config[i]+";";
                            }
                        var maskBackground = config['maskBackground'];
                        /*弹出模板框*/
                        var modalBox = '';
                            modalBox+=effects;
                            modalBox+= '<div class="modal-mask" style="position:fixed;z-index:'+maskzindex+';top:0;right:0;left:0;bottom:0;background:'+maskBackground+';opacity:0.8;" ></div>';
                            modalBox+= '<div class="modal-body" style="position:fixed;overflow:auto;z-index:'+bodyzindex+';'+modalStyle+'">';
                            modalBox+= html;
                            modalBox+= '</div>';
                            var frag = document.createDocumentFragment(); 
                            var d    = document.createElement('div');
                            d.classList.add(flag);
                            d.classList.add(flag+'-'+maskzindex);
                            d.innerHTML = modalBox;
                            frag.appendChild(d);
                            document.body.appendChild(frag);
                            var modalMask = d.querySelector('.modal-mask'); 
                            var modalBody = d.querySelector('.modal-body'); 
                            modalBody.classList.add('bounceInDown');
                            modalBody.classList.add('animated');
                            ['webkitAnimationEnd','mozAnimationEnd','MSAnimationEnd','oanimationend','animationend'].forEach(function(item,index){
                                    modalBody.addEventListener(item, function(){this.classList.remove('animated');this.classList.remove('bounceInDown');}, {passive:false});
                            });
                            var translateEndY = 0, translateEndYTemp  = 0;
                            //mui(modalBody.querySelector('.mui-scroll-wrapper')).scroll({indicators: true});
                            var modalClose    = d.querySelector('.modal-close') || {};
                            var modalCloseAll = d.querySelector('.modal-close-all')||{};
                            try{
                                ['tap','click'].forEach(function(item,index){
                                     modalClose.addEventListener(item, function(){ self.close( d );},{passive:false});
                                });
                            }catch(e){}
                            try{
                                ['tap','click'].forEach(function(item,index){
                                     modalCloseAll.addEventListener(item, function(){self.closeAll();},{passive:false})
                                });
                            }catch(e){}
                            $.jsEval(html);
                            return d;
                }
                /*
                 * d 弹出层对象
                 */
                init.prototype.close = function( d ){
                        var m = d.querySelector('.modal-mask');
                        var b = d.querySelector('.modal-body');
                        m.classList.add('bounceOutDown');
                        m.classList.add('animated');
                        b.classList.add('bounceOutDown');
                        b.classList.add('animated');
                        ['webkitAnimationEnd','mozAnimationEnd','MSAnimationEnd','oanimationend','animationend'].forEach(function(item,index){
                                    m.addEventListener(item, function(){
                                        this.classList.remove('animated');this.classList.remove('bounceInDown');
                                    },{passive:false});
                                    b.addEventListener(item, function(){
                                        this.classList.remove('animated');this.classList.remove('bounceInDown');
                                        try{d.parentNode.removeChild(d);}catch(e){}
                                    },{passive:false});
                        }); 
                }
                init.prototype.closeAll = function(){
                        var self = this;
                        Array.prototype.forEach.call(document.querySelectorAll('.'+flag),function(item,index){
                                    self.close(item);
                        });
                }
            return new init(options);   
    }
})(model,window);

/*
 * 加载提示
 */
(function($,window){
        $.loading = function(options){ 

                var config = {
                       mask_index:1000,
                       load_index:1001,
                       msg:"数据加载中",
                       timeout:5000,     //超时自动关闭
                       width:'8em',
                       top  : '30%',
                       left : '45%',
                       margin:'0',
                };
                var init = function(options){
                        for (var i in options) {
                                config[i] = options[i];
                        }
                        config['load_index'] = parseInt( config.mask_index ) + 1;
                        return this;
                }
                init.prototype.open = function(){
                        var loadingImage = "data:image/png;base64,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";
                        var html='';
                        html+='<style>@-webkit-keyframes spin {from {-webkit-transform: rotate(0deg);} to{-webkit-transform: rotate(360deg);} }@keyframes spin {from {transform: rotate(0deg);} to {transform: rotate(360deg);}}</style>';
                        html+='<div class="mask_transparent" style="position:fixed;z-index:'+config.mask_index+';top:0;right:0;left:0;bottom:0;" ></div>';
                        html+='<div class="loading" style="-moz-user-select:none;position:fixed;z-index:'+config.load_index+';width:'+config.width+';min-height:'+config.width+';top:'+config.top+';left:'+config.left+';margin:'+config.margin+';background:rgba(17,17,17,0.7);text-align:center;border-radius:5px;color: #FFFFFF;">';
                        html+='<i class="icon_loading" style="background-size: 100%;display: inline-block;margin: 30px 0 0;width: 38px;height: 38px;vertical-align: baseline;-webkit-animation: spin 1s linear infinite;animation: spin 1000ms infinite linear;background-image:url('+loadingImage+');"></i>';
                        html+='<p class="loading_content" style="margin: 15px 0 15px;padding:0;color: #FFFFFF;">'+config.msg+'</p>';
                        html+='</div>';
                        var frag = document.createDocumentFragment(); 
                        var d=document.createElement('div');
                        d.setAttribute('id','loadingToast');
                        //d.innerHTML=html;
                        $(d).html(html);
                        frag.appendChild(d);
                        document.body.appendChild(frag); 
                        return this;
                }
                init.prototype.close = function(){
                        var loading;
                        try{loading=document.getElementById('loadingToast');document.body.removeChild(loading);}catch(e){}
                }
                return new init(options);
        }
})(model,window);

/*
 * model ajax
 */
(function($, window, undefined) {
    var jsonType = 'application/json';
    var htmlType = 'text/html';
    var rscript = /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi;
    var scriptTypeRE = /^(?:text|application)\/javascript/i;
    var xmlTypeRE = /^(?:text|application)\/xml/i;
    var blankRE = /^\s*$/;

    $.ajaxSettings = {
        type: 'GET',
        beforeSend: $.noop,
        success: $.noop,
        error: $.noop,
        complete: $.noop,
        context: null,
        xhr: function(protocol) {
            return new window.XMLHttpRequest();
        },
        accepts: {
            script: 'text/javascript, application/javascript, application/x-javascript',
            json: jsonType,
            xml: 'application/xml, text/xml',
            html: htmlType,
            text: 'text/plain'
        },
        timeout: 0,
        processData: true,
        cache: true
    };
    var ajaxBeforeSend = function(xhr, settings) {
        var context = settings.context
        if(settings.beforeSend.call(context, xhr, settings) === false) {
            return false;
        }
    };
    var ajaxSuccess = function(data, xhr, settings) {
        settings.success.call(settings.context, data, 'success', xhr);
        ajaxComplete('success', xhr, settings);
    };
    // type: "timeout", "error", "abort", "parsererror"
    var ajaxError = function(error, type, xhr, settings) {
        settings.error.call(settings.context, xhr, type, error);
        ajaxComplete(type, xhr, settings);
    };
    // status: "success", "notmodified", "error", "timeout", "abort", "parsererror"
    var ajaxComplete = function(status, xhr, settings) {
        settings.complete.call(settings.context, xhr, status);
    };
    var serialize = function(params, obj, traditional, scope) {
        var type, array = $.isArray(obj),
            hash = $.isPlainObject(obj);
        $.each(obj, function(key, value) {
            type = $.type(value);
            if(scope) {
                key = traditional ? scope :
                    scope + '[' + (hash || type === 'object' || type === 'array' ? key : '') + ']';
            }
            // handle data in serializeArray() format
            if(!scope && array) {
                params.add(value.name, value.value);
            }
            // recurse into nested objects
            else if(type === "array" || (!traditional && type === "object")) {
                serialize(params, value, traditional, key);
            } else {
                params.add(key, value);
            }
        });
    };
    var serializeData = function(options) {
        if(options.processData && options.data && typeof options.data !== "string") {
            var contentType = options.contentType;
            if(!contentType && options.headers) {
                contentType = options.headers['Content-Type'];
            }
            if(contentType && ~contentType.indexOf(jsonType)) { //application/json
                options.data = JSON.stringify(options.data);
            } else {
                options.data = $.param(options.data, options.traditional);
            }
        }
        if(options.data && (!options.type || options.type.toUpperCase() === 'GET')) {
            options.url = appendQuery(options.url, options.data);
            options.data = undefined;
        }
    };
    var appendQuery = function(url, query) {
        if(query === '') {
            return url;
        }
        return(url + '&' + query).replace(/[&?]{1,2}/, '?');
    };
    var mimeToDataType = function(mime) {
        if(mime) {
            mime = mime.split(';', 2)[0];
        }
        return mime && (mime === htmlType ? 'html' :
            mime === jsonType ? 'json' :
            scriptTypeRE.test(mime) ? 'script' :
            xmlTypeRE.test(mime) && 'xml') || 'text';
    };
    var parseArguments = function(url, data, success, dataType) {
        if($.isFunction(data)) {
            dataType = success, success = data, data = undefined;
        }
        if(!$.isFunction(success)) {
            dataType = success, success = undefined;
        }
        return {
            url: url,
            data: data,
            success: success,
            dataType: dataType
        };
    };
    $.ajax = function(url, options) {
        if(typeof url === "object") {
            options = url;
            url = undefined;
        }
        var settings = options || {};
        settings.url = url || settings.url;
        for(var key in $.ajaxSettings) {
            if(settings[key] === undefined) {
                settings[key] = $.ajaxSettings[key];
            }
        }
        serializeData(settings);
        var dataType = settings.dataType;

        if(settings.cache === false || ((!options || options.cache !== true) && ('script' === dataType))) {
            settings.url = appendQuery(settings.url, '_=' + $.now());
        }
        var mime = settings.accepts[dataType && dataType.toLowerCase()];
        var headers = {};
        var setHeader = function(name, value) {
            headers[name.toLowerCase()] = [name, value];
        };
        var protocol = /^([\w-]+:)\/\//.test(settings.url) ? RegExp.$1 : window.location.protocol;
        var xhr = settings.xhr(settings);
        var nativeSetHeader = xhr.setRequestHeader;
        var abortTimeout;

        setHeader('X-Requested-With', 'XMLHttpRequest');
        setHeader('Accept', mime || '*/*');
        if(!!(mime = settings.mimeType || mime)) {
            if(mime.indexOf(',') > -1) {
                mime = mime.split(',', 2)[0];
            }
            xhr.overrideMimeType && xhr.overrideMimeType(mime);
        }
        if(settings.contentType || (settings.contentType !== false && settings.data && settings.type.toUpperCase() !== 'GET')) {
            setHeader('Content-Type', settings.contentType || 'application/x-www-form-urlencoded');
        }
        if(settings.headers) {
            for(var name in settings.headers)
                setHeader(name, settings.headers[name]);
        }
        xhr.setRequestHeader = setHeader;

        xhr.onreadystatechange = function() {
            if(xhr.readyState === 4) {
                xhr.onreadystatechange = $.noop;
                clearTimeout(abortTimeout);
                var result, error = false;
                var isLocal = protocol === 'file:';
                if((xhr.status >= 200 && xhr.status < 300) || xhr.status === 304 || (xhr.status === 0 && isLocal && xhr.responseText)) {
                    dataType = dataType || mimeToDataType(settings.mimeType || xhr.getResponseHeader('content-type'));
                    result = xhr.responseText;
                    try {
                        // http://perfectionkills.com/global-eval-what-are-the-options/
                        if(dataType === 'script') {
                            (1, eval)(result);
                        } else if(dataType === 'xml') {
                            result = xhr.responseXML;
                        } else if(dataType === 'json') {
                            result = blankRE.test(result) ? null : $.parseJSON(result);
                        }
                    } catch(e) {
                        error = e;
                    }

                    if(error) {
                        ajaxError(error, 'parsererror', xhr, settings);
                    } else {
                        ajaxSuccess(result, xhr, settings);
                    }
                } else {
                    var status = xhr.status ? 'error' : 'abort';
                    var statusText = xhr.statusText || null;
                    if(isLocal) {
                        status = 'error';
                        statusText = '404';
                    }
                    ajaxError(statusText, status, xhr, settings);
                }
            }
        };
        if(ajaxBeforeSend(xhr, settings) === false) {
            xhr.abort();
            ajaxError(null, 'abort', xhr, settings);
            return xhr;
        }

        if(settings.xhrFields) {
            for(var name in settings.xhrFields) {
                xhr[name] = settings.xhrFields[name];
            }
        }

        var async = 'async' in settings ? settings.async : true;

        try{
        	xhr.open(settings.type.toUpperCase(), settings.url, async, settings.username, settings.password);
        }catch(e){}
        
        for(var name in headers) {
            if(headers.hasOwnProperty(name)) {
                nativeSetHeader.apply(xhr, headers[name]);
            }
        }
        if(settings.timeout > 0) {
            abortTimeout = setTimeout(function() {
                xhr.onreadystatechange = $.noop;
                xhr.abort();
                ajaxError(null, 'timeout', xhr, settings);
            }, settings.timeout);
        }
        try{
        	xhr.send(settings.data ? settings.data : null);
        }catch(e){}
        
        return xhr;
    };

    $.param = function(obj, traditional) {
        var params = [];
        params.add = function(k, v) {
            this.push(encodeURIComponent(k) + '=' + encodeURIComponent(v));
        };
        serialize(params, obj, traditional);
        return params.join('&').replace(/%20/g, '+');
    };
    $.get = function( /* url, data, success, dataType */ ) {
        return $.ajax(parseArguments.apply(null, arguments));
    };

    $.post = function( /* url, data, success, dataType */ ) {
        var options = parseArguments.apply(null, arguments);
        options.type = 'POST';
        return $.ajax(options);
    };

    $.getJSON = function( /* url, data, success */ ) {
        var options = parseArguments.apply(null, arguments);
        options.dataType = 'json';
        return $.ajax(options);
    };
// text-> true:文本返回 false:(默认)节点对象返回
// callback 回调
    $.ajaxHtml = function(url,d,text,callback){
        var result=false;
        $.ajax(url,{data:d,dataType:'html',async:true,type:'post',timeout:10000,
                success:function(content){
                    if(content!=''&&content!=null){
                            if(text||false){
                                result=content;
                            }else{
                                var fragment = document.createDocumentFragment(),group,list;
                                group = document.createElement('div');
                                group.innerHTML = content;
                                list  = group.childNodes;
                                for(var i=0; i<list.length;i++){
                                    if( list[i].nodeType != 1 ){continue;}
                                    fragment.appendChild( list[i] );
                                }
                                result=fragment;
                            }
                        	(typeof callback=='function')?callback(result):false;
                    }else{
                    		(typeof callback=='function')?callback(false,1):false;
                    }
                },
                error:function(xhr,type,errorThrown){
                		(typeof callback=='function')?callback(false,2):false;
                        return false;
                }
        });
        return result;
    };

    $.fn.load = function(url, data, success) {
        if(!this.length)
            return this;
        var self = this,
            parts = url.split(/\s/),
            selector,
            options = parseArguments(url, data, success),
            callback = options.success;
        if(parts.length > 1)
            options.url = parts[0], selector = parts[1];
        options.success = function(response) {
            if(selector) {
                var div = document.createElement('div');
                div.innerHTML = response.replace(rscript, "");
                //$(div).html(response.replace(rscript, ""));
                var selectorDiv = document.createElement('div');
                var childs = div.querySelectorAll(selector);
                if(childs && childs.length > 0) {
                    for(var i = 0, len = childs.length; i < len; i++) {
                        selectorDiv.appendChild(childs[i]);
                    }
                }
                self[0].innerHTML = selectorDiv.innerHTML;
                //$(self[0]).html(selectorDiv.innerHTML);
            } else {
                self[0].innerHTML = response;
                //$(self[0]).html( response );
            }
            callback && callback.apply(self, arguments);
        };
        $.ajax(options);
        return this;
    };

})(model, window);




