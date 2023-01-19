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
/*
 *  chooser 选择器控件
 */

;(function(root,factory){
	if(typeof define ==='function' && define.amd ){  //amd
		define(factory);
	}else if(typeof exports ==='object' ){ //umd
		module.exports = factory();
	}else{
		root.chooser = factory();
	}
})(this,function(){
		var doc = document, win = window, $={};
		var $Q  = function (selector,content) {
        		content = content || document;
        		return selector.nodeType ? selector : content.querySelector(selector);
    	};
    	var chooser = function(elem,options){
    			var opts = typeof (options) === "function" ? options() : options;
        	return new chooserPick(elem,opts);
    	}
    	var class2type = {};
        Array.prototype.forEach.call(['Boolean', 'Number', 'String', 'Function', 'Array', 'Date', 'RegExp', 'Object', 'Error'], function(item, index) {
            class2type["[object " + item + "]"] = item.toLowerCase();
        });
        /*事件注册代理*/
        var delegates = {};
		var _mid = 1;
		var muid  = function(obj) {
			return obj && (obj._mid || (obj._mid = _mid++));
		};

    	//控件版本
	    chooser.version = "V1.0";
	    //用一个或多个其他对象来扩展一个对象，返回被扩展的对象
	    chooser.extend = $.extend = function () {
	        var options, name, src, copy,deep = false, target = arguments[0], i = 1, length = arguments.length;
	        if (typeof (target) === "boolean") deep = target, target = arguments[1] || {}, i = 2;
	        if (typeof (target) !== "object" && typeof (target) !== "function") target = {};
	        if (length === i) target = this, --i;
	        for (; i < length; i++) {
	            if ((options = arguments[i]) != null) {
	                for (name in options) {
	                    src = target[name], copy = options[name];
	                    if (target === copy) continue;
	                    if (copy !== undefined) target[name] = copy;
	                }
	            }
	        }
	        return target;
	    };
	    /*扩展通用方法*/
	    $.extend($,{
	    		isType : function (obj,type) {
		            var firstUper = function (str) {
		                str = str.toLowerCase();
		                return str.replace(/\b(\w)|\s(\w)/g, function (m) {
		                    return m.toUpperCase();
		                });
		            }
		            return Object.prototype.toString.call(obj) == "[object " + firstUper(type) + "]";
		        },
		        each : function (obj, callback, args){
		            var length, i = 0;
					if ( $.isArrayLike( obj ) ) {
						length = obj.length;
						for ( ; i < length; i++ ) {
							if ( callback.call( obj[ i ], i, obj[ i ] ) === false ) {
								break;
							}
						}
					} else {
						for ( i in obj ) {
							if ( callback.call( obj[ i ], i, obj[ i ] ) === false ) {
								break;
							}
						}
					}
					return obj;
		        },
		        type : function(obj) {
                        return obj == null ? String(obj) : class2type[{}.toString.call(obj)] || "object";
                },
                isWindow: function(obj) {return obj != null && obj === obj.window;},
		        isArray : Array.isArray || function(object) { return object instanceof Array; },
		        isArrayLike : function(obj) {
                        var length = !!obj && "length" in obj && obj.length;
                        var type = $.type(obj);
                        if (type === "function" || $.isWindow(obj)) {
                            return false;
                        }
                        return type === "array" || length === 0 ||
                            typeof length === "number" && length > 0 && (length - 1) in obj;
                },
		        addEvent : function( eventThis,event,callback,selector){
						muid( eventThis );
						var mid = eventThis._mid || false;
						if( mid==false||mid==null){
							return false;
						}
						if( !$.isObj(delegates[mid]) ){
								delegates[mid] = { self:eventThis,event:event,callback:callback };
								if (eventThis.addEventListener) {
				                		eventThis.addEventListener(event, delegates[mid].callback, false);//DOM2.0
					            }else if (eventThis.attachEvent) {
					                	eventThis.attachEvent("on" + event, delegates[mid].callback);//IE5+
					            }else {
					                	eventThis["on" + event] = delegates[mid].callback;	//DOM 0
					            }	
						}else{
								$.removeEvent(delegates[mid]['self'],delegates[mid]['event'],delegates[mid]['callback']);
								$.addEvent(eventThis,event,callback);
						}

				},
				removeEvent : function (eventThis,event,callback ){
						var mid = eventThis._mid || false;
						if( mid==false||mid==null){
							return false;
						}
						if( $.isObj(delegates[mid]) ){
			                if (eventThis.removeEventListener) {
				                 eventThis.removeEventListener(event, delegates[mid].callback, false);//DOM2.0
				            }else if (eventThis.detachEvent) {
				                 eventThis.detachEvent("on" + event, delegates[mid].callback);//IE5+
				            }else {
				                eventThis["on" + event] = null;//DOM 0
				            }
			                var delegatesCopy = {};
			                for(var i in delegates){
			                        if(i==mid) continue;
			                        delegatesCopy[i] = delegates[i];
			                }
			                delegates = {};
			                delegates = delegatesCopy;
						}
				},
		        on : function (pNode,cls,type,fn) {
		        	var nType = pNode.nodeType || 0;
		        	if( nType !=1 && nType!=9 ){
		        			console.log('pNode not node');
		        		return false;
		        	} 
		        	var selectorElm = pNode.querySelectorAll(cls) || {};
		        	if( !$.isObj( selectorElm ) ){
		        			return false;
		        	}
		        	$.each(selectorElm,function(idx,elm){
			        		$.addEvent(elm,type,fn);
		        	});
		        },
		        off:function(pNode,cls,type,fn){
		        	var nType = pNode.nodeType || 0;
		        	if( nType !=1 && nType!=9 ){
		        			console.log('pNode not node');
		        		return false;
		        	} 
		        	var selectorElm = pNode.querySelectorAll(cls) || {};
		        	if( !$.isObj( selectorElm ) ){
		        			return false;
		        	}
		        	$.each(selectorElm,function(idx,elm){
			        		$.removeEvent(elm,type,fn);
		        	});
		        },
		        isObj : function (obj){
		            for(var i in obj){return true;}
		            return false;
		        },
		        trim : function (str){ return str.replace(/(^\s*)|(\s*$)/g, ""); }, 
		        equals : function (arrA,arrB) {
		            if (!arrB) return false;
		            if (arrA.length != arrB.length) return false;
		            for (var i = 0, l = arrA.length; i < l; i++) {
		                if (arrA[i] instanceof Array && arrB[i] instanceof Array) {
		                    if (!arrA[i].equals(arrB[i])) return false;
		                } else if (arrA[i] != arrB[i]) {
		                    return false;
		                }
		            }
		            return true;
		        },
		        //补齐数位
		        digit : function(num) {
		            return num < 10 ? "0" + (num | 0) :num;
		        },
		        //判断是否为数字
		        isNum : function(value){
		            return /^[+-]?\d*\.?\d*$/.test(value) ? true : false;
		        },
		        setCss:function(elem,obj) {
		            for (var x in obj) elem.style[x] = obj[x];
		        },
		        html : function (elem,html) {
		            return typeof html === "undefined" ? elem && elem.nodeType === 1 ? elem.innerHTML :undefined :typeof html !== "undefined" && html == true ? elem && elem.nodeType === 1 ? elem.outerHTML :undefined : elem.innerHTML = html;
		                
		        },
		        // 读取设置节点文本内容
		        text : function(elem,value) {
		            var innText = document.all ? "innerText" :"textContent";
		            return typeof value === "undefined" ? elem && elem.nodeType === 1 ? elem[innText] :undefined : elem[innText] = value;
		        },
		        //设置值
		        val : function (elem,value) {
		            if (typeof value === "undefined") {
		                return elem && elem.nodeType === 1 && typeof elem.value !== "undefined" ? elem.value :undefined;
		            }
		            // 将value转化为string
		            value = value == null ? "" :value + "";
		            elem.value = value;
		        },
		        attr : function(elem,value){
		            return elem.getAttribute(value);
		        },
		        hasClass : function (obj, cls) {
		            return obj.className.match(new RegExp('(\\s|^)' + cls + '(\\s|$)'));
		        },
		        stopPropagation : function (ev) { 
		            (ev && ev.stopPropagation) ? ev.stopPropagation() : window.event.cancelBubble = true;  
		        },
		        isBool : function(obj){  return (obj == undefined || obj == true ?  true : false); },
		        uniqPush:function(arr,value){
		        	 for(var i in arr){
		        	 		if(value==arr[i]){
		        	 			return false;
		        	 		}
		        	 }
		        	 arr.push(value);
		        },
	    });
	    /*主函*/
	    function chooserPick(elem,options){
	    		//模块配置参数
	    		var config = {
	    			layer:1,			//选择层,请根据实际联动数组层级匹配
	    			confirmBtn:false,   //是否需要确定选项值
	    			multiCheck:false,   //选项多选
	    			trigger:'click',    //点击事件,默认都是click
	    			checked:[],         //默认选中的值 [{'value':11,'text':22},{'value':33,'text':44}]
	    			separate:',',		//多选值分隔符
	    			data:{},			//联动选项值 json{text:'',value:'',children:{}}
	    			method:{},          //自定义方法
	    			updateCallback:false,  //确认选择数据后回调 
	    		}; 
	    		this.$opts    = $.extend(config,options||{}); //配置或扩展参数
		        this.dataTree = {};  //数据树结构
		        this.selector = elem;
		        this.valCell  = $Q(elem); 
		        this.valCell != null ? this.init() : console.log(elem+"  ID\u6216\u7C7B\u540D\u4E0D\u5B58\u5728!");
		        $.extend(this,this.$opts.method);  //扩展自定义方法
		        delete this.$opts.method;
	    }
	    $.extend(chooserPick.prototype,{
	    		init:function(){
	    				var that = this, opts = that.$opts,thisNode = that.valCell,layer=opts.layer || 1,checked=opts.checked;
	    				for(var i=0;i<layer;i++){
	    					  if(typeof checked[i] == 'undefined'){
	    					  		checked[i] = {};
	    					  }
	    				}
	    				var renderHead = that.renderHead();
	    				var frow ='display:flex;flex-direction:row;flex-wrap:nowrap;overflow:hidden;justify-content: flex-start; align-items:';
	    				var frowTop = 'flex-start;'; 
	    				var template  = '<div style="'+frow+frowTop+'">'+renderHead+'</div>';
	    				var appendHead = that.clearAppend(template);
	    				that.renderData( appendHead );
	    		},
	    		initTree:function(data,layer,parent){
	    				var layer = layer||1,parent=parent||0,key=0,text='',children=false,nextLayer=layer+1;
	    				if(typeof this.dataTree[layer] == 'undefined' ){
	    						this.dataTree[layer] = {};
	    				}
	    			 	for(var i in data){
 							key      = data[i]['value'] || 0;
 							//text     = data[i]['text']  || ''; text有可是0
 							children = data[i]['children'] || false;
 							try{
 								text = (data[i]['text']===0||data[i]['text']==='0') ? 0 : (data[i]['text']  || '');
 							}catch(e){}
	    			 		if(typeof this.dataTree[layer][parent] == 'undefined' ){
	    						this.dataTree[layer][parent] = {};
	    				    }
	    			 		this.dataTree[layer][parent][key]=  text;
	    			 		if(children){
	    			 			this.initTree(children,nextLayer,key);
	    			 		}
	    			 	}
	    		},
	    		renderHead:function(){
	    				var that = this,opts = that.$opts,thisNode = that.valCell,data = opts.data,layer=opts.layer || 1,checked=opts.checked;
	    				var xsl  = '',value='',text='';
	    				that.initTree(data);
	    				var areaStyle = "position:absolute;z-index:99;background:#fcfcfc;",chooserStyle='';
	    				for(var i=1;i<=layer;i++){
	    					value = checked[i-1]['value']==null ? '': checked[i-1]['value']+'';
	    					text  = checked[i-1]['text']==null  ? '': checked[i-1]['text'] +'';
	    					chooserStyle = "margin-right:0.5rem;width:100%;";
	    					if(i==layer){ chooserStyle = "width:100%;"; }
	    					xsl += '<div class="chooser" style="'+chooserStyle+'">\
	    							<input class="chooser-checked chooser-checked-'+i+'" param-key="'+that.selector.replace(/[\.\#]/g,"")+'" param-key2d="'+i+'" value="'+text+'" type="text" placeholder="请选择.." data-id="'+i+'" readonly />\
	    							<input class="chooser-value chooser-value-'+i+'" param-key="'+that.selector.replace(/[\.\#]/g,"")+'" param-key2d="'+i+'" value="'+value+'" type="hidden" />\
	    							<div   class="chooser-area chooser-area-'+i+'" style="'+areaStyle+'" ></div>\
	    							</div>';
	    				}
	    				return xsl;
	    		},
	    		renderData:function( nodes ){
	    				var that = this,data = this.dataTree || {},opts = this.$opts;
	    				if( !$.isObj(data) ){
	    						return false;
	    				}
	    				$.on(nodes,'.chooser-checked',opts.trigger,function(e){
	    						var id = this.getAttribute('data-id')||0,parent=0,key = id-1;
	    						if(id<1) return false;
	    						//init chooser area
	    						$.each(nodes.querySelectorAll('.chooser-area'),function(idx,item){
	    							$.html(item,'');
	    						});
	    						try{
	    							parent=nodes.querySelector('.chooser-value-'+key).value || 0;
	    						}catch(e){}
	    						var width = this.offsetWidth; //设置宽度
	    						nodes.querySelector('.chooser-area-'+id).style.width = width+'px';
	    						var append = that.append(that.dataOption( data[id][parent]||{},key ),nodes.querySelector('.chooser-area-'+id));
	    						var valueArr = [],textArr=[];
	    						$.on(append,'.chooser-item',opts.trigger,function(e){
	    								if(!opts.multiCheck){
	    									$.each(this.parentNode.querySelectorAll('.chooser-item.on'),function(){
	    											this.classList.remove('on');
	    									});
	    								}
	    								if(this.classList.contains('on')){
	    									this.classList.remove('on');
	    								}else{
	    									this.classList.add('on');
	    								}
	    								var chooserItem = {};
	    								if(opts.multiCheck){
	    									chooserItem = this.parentNode.querySelectorAll('.chooser-item.on');
	    								}else{
	    									chooserItem = this.parentNode.querySelector('.chooser-item.on');
	    								}
	    								chooserItem = $.isArrayLike( chooserItem ) ? chooserItem : chooserItem!=null ? [chooserItem] : [];
	    								var onValue=[],onText=[];
	    								$.each(chooserItem,function(idx,item){
	    										$.uniqPush(onValue,item.getAttribute('data-value')||'');
	    										$.uniqPush(onText,item.getAttribute('data-text')||'');
	    								});
	    								if(!opts.confirmBtn){
	    										opts.checked[key]['value'] = onValue.join(opts.separate);
	    										opts.checked[key]['text']  = onText.join(opts.separate);
	    										that.renderUpdate(nodes,id);
	    								}else{
	    										valueArr=onValue;textArr=onText;
	    								}
	    						});
	    						if(opts.confirmBtn){
	    							$.on(append,'.chooser-area-confirm',opts.trigger,function(){
	    								opts.checked[key]['value'] = valueArr.join(opts.separate);
	    								opts.checked[key]['text']  = textArr.join(opts.separate);
	    								that.renderUpdate(nodes,id);
	    							});
	    						}
	    						that.renderDataEvent( nodes );
	    				});
	    		},
	    		renderDataEvent:function(nodes){
	    			var that = this,opts = this.$opts;
	    			var chooserArea = function(e){
	    						/* //多实例弹出框无法有效初始化
	    						var targetNode = e.target;
	    						if(targetNode.classList.contains('chooser-item') || 
	    							  targetNode.classList.contains('chooser-checked') || 
	    							  	targetNode.classList.contains('chooser-area-confirm')
	    						){
	    							 return false;
	    						}else{
	    							$.each(nodes.querySelectorAll('.chooser-area'),function(idx,item){
			    							$.html(item,'');
			    					});
	    							$.off(document,'body',opts.trigger,chooserArea);
	    						}
	    						*/
	    						/*兼容多实例*/
	    						$.each(document.querySelectorAll('.chooser-area'),function(idx,item){
	    									var isDel = false;
	    									$.each(nodes.querySelectorAll('.chooser-area'),function(idx1,item1){
	    											if(isDel==true)
	    													return false;
	    											if(item==item1)
	    													isDel=true;
	    									});
	    									if(isDel==false){
	    											$.html(item,'');	
	    									}
    							});
    							var targetNode = e.target;
	    						if(targetNode.classList.contains('chooser-item') || 
	    							  targetNode.classList.contains('chooser-checked') || 
	    							  	targetNode.classList.contains('chooser-area-confirm')
	    						){
	    							 return false;
	    						}else{
	    							 $.each(nodes.querySelectorAll('.chooser-area'),function(idx,item){
			    							$.html(item,'');
			    					});
	    						}
    							$.off(document,'body',opts.trigger,chooserArea);
	    				}
	    				$.on(document,'body',opts.trigger,chooserArea);
	    		},
	    		/*
	    		 * 更新数据值
	    		 * nodes chooser 选择区域根节点
	    		 * id 选项所属层级
	    		 */
	    		renderUpdate:function(nodes,id){
	    				var data = data || {},opts = this.$opts,checked=opts.checked;
	    				$.each(document.querySelectorAll('.chooser-area'),function(idx,item){
	    							$.html(item,'');
	    				});
	    				$.each(checked,function(idx,item){
	    					var text='',value='';
	    					if(idx+1>id){
	    						checked[idx] = {};
	    					}else{
	    						try{
 									 text  = item.text == null ? "" :item.text + "";
 									 value = item.value== null ? "" :item.value + "";
 								}catch(e){}
	    					}
	    					nodes.querySelector('.chooser-checked-'+(idx+1)).value = text;
	    					nodes.querySelector('.chooser-value-'+(idx+1)).value   = value;
	    				});	 
	    				typeof opts.updateCallback == 'function' ? opts.updateCallback(this.valCell,checked) : false; 				
	    		},
	    		dataOption:function(data,key){
	    				var data = data || {},opts = this.$opts,checked=opts.checked;
	    				if( !$.isObj(data)){
	    						return '<div class="chooser-item" data-value="0" data-text="0" >请选择</div>';
	    				}
	    				var xsl  = '<div style="padding-top:0.3rem;">',on='',valueArr = [],values='';
	    					try{
	    						values   = checked[key]['value'].toString()|| '';  
	    						valueArr = values!=null && values!='' ? values.split(opts.separate) : [];
	    					}catch(e){ } 
	    					xsl += '<div class="scrollbar" style="max-height:10rem;overflow-y:auto;" >';
	    				for(var i in data){
	    					on='';
	    					for(var j in valueArr){
	    						  if(valueArr[j] == i) on=' on';
	    					}
	    					xsl += '<div class="chooser-item'+on+'" style="padding:0.2rem 0.5rem;border-bottom:1px dashed #fff;" data-value="'+i+'" data-text="'+data[i]+'" >'+data[i]+'</div>';
	    				}
	    					xsl += '</div>';
	    				//多选情况使用确定
	    				if(opts.confirmBtn){
	    					xsl += '<div class="chooser-area-confirm" style="text-align:center;">确定</div>';
	    				}
	    				xsl += '</div>';	
	    				return xsl;
	    		},
	    		clearAppend:function(html,nodeobj){
	    				var that = this,thisNode = nodeobj || that.valCell;
	    				$.html(thisNode,''); 
	    				return that.append(html,thisNode);
	    		},
	    		append : function(html,nodeobj){ 
		    			var that = this,thisNode = nodeobj || that.valCell;
				        var frag = document.createDocumentFragment(); 
				        var d    = document.createElement('div');
				        $.html(d,html); 
				        $.each(d.childNodes,function(index,item){ 
				        		frag.appendChild(item.cloneNode(true)); 
				        });
				        var appendChild = {};
				        thisNode = $.isArray(thisNode) ? thisNode : [thisNode];
				        $.each(thisNode,function(index,item){
				               item.appendChild(frag);
				               appendChild = item.lastElementChild;
				        }); 
				        return appendChild;
			    },
			    parentAppend : function(html,nodes){
			    	var that = this,thisNode = nodes || that.valCell;
			        var frag = document.createDocumentFragment(); 
			        var d    = document.createElement('div');
			        $.html(d,html);
			        $.each(d.childNodes,function(index,item){
			               frag.appendChild(item.cloneNode(true));
			        }); 
			        var appendChild = {};
			        thisNode = $.isArray(thisNode) ? thisNode : [thisNode];
			        $.each(thisNode,function(index,item){
			               item.parentNode.appendChild(frag);
			               appendChild = item.parentNode.lastElementChild;
			        }); 
			        return appendChild;
			    },
			    prepend : function(html,refererNode,nodes){
			    	var that = this,thisNode = nodes || that.valCell;
			    	var frag = document.createDocumentFragment(); 
			        var d    = document.createElement('div');
			        $.html(d,html);
			        $.each(d.childNodes,function(index,item){
			               frag.appendChild(item.cloneNode(true));
			        }); 
			        var appendChild = {};
			        thisNode = $.isArray(thisNode) ? thisNode : [thisNode];
			        $.each(thisNode,function(index,item){
			               var existingItem = refererNode || item.childNodes[0]||null;
			               item.insertBefore( frag,existingItem );
			               if( existingItem != null && existingItem!='' ){
			                    appendChild  = existingItem.previousSibling;
			               }else{
			                    appendChild  = item.firstElementChild;
			               }     
			        }); 
			        return appendChild;
			    },
			    remove : function( childNode ){
			    	var that = this,thisNode = that.valCell;
		            var node = childNode || {};
		            if( node==''||node==null ){
		                    return false;
		            }
		            thisNode = $.isArray(thisNode) ? thisNode : [thisNode];
		            $.each(thisNode,function(index,item){
		            	  if( !$.isObj( node )){
		            	  		item.parentNode.removeChild(item);
		            	  }else{
		            	  		item.removeChild(node);
		            	  } 
		            });
			    }

	    });
    	return chooser;
});