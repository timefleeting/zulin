<?php

namespace main;

/** 
 * 	
 *  CURL通用配置类【单线程】
 * 
 *  @author hebin
 *  @version v1.0
 * 
 */

if (!function_exists('curl_setopt_array')) {
	   function curl_setopt_array(&$ch, $curl_options)
	   {
	       foreach ($curl_options as $option => $value) {
	       		$option = strtoupper( $option );
	           if(!curl_setopt($ch, $option, $value)) {
	               return false;
	           } 
	       }
	       return true;
	   }
}

class Curl{


		//private static $_instance;

		private 	$_stdfp  	= 	false;  			//CURLOPT_STDERR 调试文件句柄 
		private 	$_stdtrack 	= 	false;				//CURLOPT_VERBOSE 开启 详细调试追踪信息
		private 	$_options 	= 	array();			//CURL选项配置
		
		private  	$_cookie     = 	[];					//cookie参数,键值数组,通过header头set-cookie获取
		private  	$_jarfile    = 	false;				//cookiejar文件名
		private  	$_cookiejar  = 	false;				//cookiejar文件内容

		private 	$_ch		=	false;	   			//开启curl句柄，结束时释放
		private  	$_jarhandle = 	false;    			//临时cookiejar文件句柄，结束时释放

		private 	$_data		=	[];					//参数
		private 	$_response	= 	false; 				//响应结果
		private 	$_header	= 	false;				//页面头信息
		private     $_location  =   false;				//跳转地址
		private 	$_body		= 	false;				//页面内容
		private 	$_errno  	= 	false;				//返回最后一次的错误代码
		private 	$_errmsg 	= 	false;				//返回当前会话最后一次错误的字符串
		private 	$_info  	= 	false;         		//是否开启调试
		private     $_httpcode  =   false;				//请求状态码


		/*
		public static function getInstance( ){
	        if(!(self::$_instance instanceof self)){
	            self::$_instance = new self();
	        }
	        return self::$_instance;
    	}

		private function __construct(){
	
		}
		*/
		public function __clone(){
                	trigger_error('Clone is not allow!',E_USER_ERROR);
        }
		/**
		 * [_init 判断并初始化curl]
		 * @return [type] [this]
		 */
		private function _init( ){
				if(!function_exists("curl_init")){
					return false;
				}
				$this->free();
				$this->_ch = curl_init( );
					/* cookie 模式*/
					if( !isset( $this->_options[CURLOPT_COOKIE] ) ){ 
							$this->cookiejar();
					}else{
							$this->cookie();
					}
					/** end **/
					/*初始化配置*/
					if( !isset( $this->_options[CURLOPT_RETURNTRANSFER] ) ) $this->returntransfer();
					if( !isset( $this->_options[CURLOPT_HTTPHEADER] ) ) 	$this->header();
					if( !isset( $this->_options[CURLOPT_HEADER] ) ) 		$this->head();
					if( !isset( $this->_options[CURLOPT_USERAGENT] ) ) 		$this->useragent();
					if( !isset( $this->_options[CURLOPT_FOLLOWLOCATION] ) ) $this->follow();
					if( !isset( $this->_options[CURLOPT_AUTOREFERER] ) ) 	$this->autoreferer();
					if( !isset( $this->_options[CURLOPT_REFERER] ) ) 		$this->referer();
					if( !isset( $this->_options[CURLOPT_CONNECTTIMEOUT] ) ) $this->conntimeout();
					if( !isset( $this->_options[CURLOPT_TIMEOUT] ) ) 		$this->timeout();
					if( !isset( $this->_options[CURLOPT_SSL_VERIFYPEER] ) ) $this->verify();
					if( !isset( $this->_options[CURLOPT_COOKIESESSION] ) ) 	$this->cookiesession();
					/* *end* */
				return $this;
		}
		/**
		 * [url 配置请求URL]
		 * @param  [type] $url [网址]
		 * @return [type]      [this]
		 */
		public function url( $url ){
					if( !empty( $url ) ){
							$this->_options[CURLOPT_URL] = $url;
					}		
			return $this;
		}
		/**
		 * [_returntransfer 获取的信息以字符串返回，而不是直接输出. true以字符串返回]
		 * @param  boolean $isReturn [是否以字符串返回]
		 * @return [type]            [this]
		 */
		public function returntransfer( $isReturn=true ){
					$this->_options[CURLOPT_RETURNTRANSFER] = $isReturn;
			return $this;
		}
		/**
		 * [_interface 发送的网络接口（interface），可以是一个接口名、IP 地址或者是一个主机名]
		 * @param  [type] $ip [服务器IP出口]
		 * @return [type]     [this]
		 */
		public function interWayout( $ip ){
					$this->_options[CURLOPT_INTERFACE] = $ip;//多IP服务器，指定IP出口
			return $this;
		}
		/**
		 * [header 设置默认的curl头部]
		 * *一个用来设置HTTP头字段的数组
		 * @param  array  $options [一维数据 'key'=>$value]
		 * @return [type]          [this]
		 */
		public function header( $options=[] ){
				$headers = [];
				//$headers['User-Agent'] = "Mozilla/5.0 (X11; U; FreeBSD i386; en-US; rv:1.8.1.14) Gecko/20080609 Firefox/2.0.0.14";
				$headers['Accept'] = "text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,text/png,*/*;q=0.5";
		        //$headers['Accept-Language'] = "en-us,en;q=0.5";
		        //$headers['Accept-Charset'] 	= "ISO-8859-1";
		        //$headers['Content-type'] 	= "text/html"; //注意：当使用post数组传递参数时会出错
		        //$headers['Host'] = "m.facebook.com";  //当使用域名host指向访问的时候很有用.设置HOST很方便的解决了访问外网接口的问题
		        $headers 	= array_merge( $headers, $options );
		        $headerArr 	= array();
		        foreach ($headers as $n => $v) {
		            $headerArr[] = $n . ':' . $v;
		        }
		            $this->_options[CURLOPT_HTTPHEADER] = $headerArr;
		    return  $this;
		}
		/**
		 * [head 把一个头包含在输出中 默认打开]
		 * ** 如果需要cookie请必须打开此选择。cookie将从头文件中获取
		 * @param  array  $openhead [true为开启头输出]
		 * @return [type]          [this]
		 */
		public function head( $openhead=true ){
					$this->_options[CURLOPT_HEADER] = $openhead;
			return $this;
		}
		/**
		 * [_useragent 在HTTP请求中包含一个"User-Agent: "头的字符串]
		 * @param  [type] $user_agent [description]
		 * @return [type]             [this]
		 */
		public function useragent( $type='pc' ){
				$userAgent['pc'] 		= 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.80 Safari/537.36';
        		$userAgent['iphone'] 	= 'Mozilla/5.0 (iPad; U; CPU OS 3_2_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B500 Safari/531.21.10';
        		$userAgent['android'] 	= 'Mozilla/5.0 (Linux; U; Android 2.2; en-us; Nexus One Build/FRF91) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1';
		        if (array_key_exists($type, $userAgent)) {
		            	$this->_options[CURLOPT_USERAGENT] = $userAgent[$type];
		        } 
		        else {
		            	$this->_options[CURLOPT_USERAGENT] = $type;
		        }
			return $this;
		}
		/**
		 * [_follow TRUE 时将会根据服务器返回 HTTP 头中的 "Location: " 重定向]
		 * *该选项的一个好处是在一些反盗链的链接中会用到
		 * *CURLOPT_MAXREDIRS ： 允许你定义跳转请求的最大次数，当超过了这个数，将不再获取往后的内容。
		 * *CURLOPT_AUTOREFERER  ：curl 会自动添加 Referer header 在每一个跳转链接，也就是一跟到底。
		 * @param  boolean $followlocation [是否重定向]
		 * @return [type]                  [this]
		 */
		public function follow( $followlocation=true,$maxredirs=0 ){
						$this->_options[CURLOPT_FOLLOWLOCATION] = $followlocation;
				if( $followlocation==true && $maxredirs > 0 ){
						$this->_options[CURLOPT_MAXREDIRS] = $maxredirs;
				}
			return $this;
		}
		/**
		 * [autoreferer TRUE 时将根据 Location: 重定向时，自动设置 header 中的Referer:信息]
		 * @param  boolean $autoreferer [是否自动设置referer]
		 * @return [type]               [this]
		 */
		public function autoreferer( $autoreferer=true ){
						$this->_options[CURLOPT_AUTOREFERER] = $autoreferer;
			return $this;
		}
		/**
		*  [referer 手动设置 header 中的Referer:信息]
		*/
		public function referer( $referer=false ){
					if( !empty( $referer )){
						$this->_options[CURLOPT_REFERER] = $referer;
					}
			return $this;
		}
		/**
		 * [_conntimeout 在尝试连接时等待的秒数。设置为0，则无限等待。]
		 * *用来告诉 PHP 在成功连接服务器前等待多久（连接成功之后就会开始缓冲输出），这个参数是为了应对目标服务器的过载，下线，或者崩溃等可能状况
		 * @param  integer $timeout [description]
		 * @return [type]           [this]
		 */
		public function conntimeout( $timeout=15 ){
						$this->_options[CURLOPT_CONNECTTIMEOUT] = $timeout;
			return $this;
		}
		/**
		 * [_timeout 连接成功后资源的等待超时]
		 * 用来告诉成功 PHP 从服务器接收缓冲完成前需要等待多长时间，如果目标是个巨大的文件，生成内容速度过慢或者链路速度过慢，这个参数就会很有用。
		 * CURLOPT_TIMEOUT 默认为0，意思是永远不会断开链接。所以不设置的话，可能因为链接太慢，会把 HTTP 资源用完
		 * @param  integer $timeout [description]
		 * @return [type]           [this]
		 */
		public function timeout( $timeout=15 ){
						$this->_options[CURLOPT_TIMEOUT] = $timeout;
			return $this;
		}
		/**
		 * [cookiesession 设为 TRUE 时将开启新的一次 cookie 会话]
		 * 启用时curl会仅仅传递一个session cookie，忽略其他的cookie，默认状况下cURL会将所有的cookie返回给服务端。session cookie是指那些用来判断服务器端的session是否有效而存在的cookie。
		 * 注意: 开启代表一次性cookie, cookie文件跟随时就不要随意开启此配置
		 * @param  boolean $isopen [是否开启新的会话]
		 * @return [type]          [this]
		 */
		public function cookiesession( $isopen=false ){
						$this->_options[CURLOPT_COOKIESESSION] = $isopen;
			return $this;
		}

		/**
		 * [verbose 追踪curl请求日志]
		 * @param  boolean $isopen [是否开启. 默认 false]
		 * @return [type]          [this]
		 */
		public function verbose( $isopen=false ){
						if( !empty( $isopen ) ){
								$this->_options[CURLOPT_VERBOSE] = true;
								$this->_stdfp = fopen('php://temp', 'w+');
								$this->_options[CURLOPT_STDERR]  = $this->_stdfp;
						}else{
								if( isset( $this->_options[CURLOPT_VERBOSE] ) ) unset( $this->_options[CURLOPT_VERBOSE] );
								if( isset( $this->_options[CURLOPT_STDERR] ) ) unset( $this->_options[CURLOPT_STDERR] );
						}
					return $this;
		}
		/**
		 * [cookiejar 是否开启cookiejar 或 直接传入cookiejar文件内容]
		 * @param  boolean $cookie [true 或 false 或 $cookiejar]
		 * @return [type]          [this]
		 */
		public function cookiejar( $cookie= true ){
						if( !empty( $cookie ) ){	
								$cookie = is_string( $cookie ) ? $cookie : $this->_cookiejar;
								$this->_jarhandle 	= tmpfile(); //创建临时文件句柄，并可以在脚本结束时自动删除
								$metaDatas 			= stream_get_meta_data( $this->_jarhandle );
								$this->_jarfile 	= $metaDatas['uri']; 

								if( !empty( $cookie )){
										$this->_cookiejar = $cookie;
										fwrite( $this->_jarhandle, $cookie );
								}
								$this->_options[CURLOPT_COOKIEJAR]  = $this->_jarfile;
								$this->_options[CURLOPT_COOKIEFILE] = $this->_jarfile;
								if( isset( $this->_options[CURLOPT_COOKIE] ) ){
											unset($this->_options[CURLOPT_COOKIE]);
								}
						}else{
								$this->cookie();
						}
					return $this;
		}

		/**
		 * [cookie 设定 HTTP 请求中"Cookie: "部分的内容。多个 cookie 用分号分隔，分号后带一个空格(例如， "fruit=apple; colour=red")]
		 * 注意当 CURLOPT_COOKIE 设置了cookie,不可在CURLOPT_HTTPHEADER 里包含Cookie参数，否则会重叠，造成cookie不可预见的情况发生.
		 * @param  [array] $cookie [键值对cookie关联数组]
		 * @return [type]          [this]
		 */
		public function cookie( $cookie=true ){
						if( !empty( $cookie ) ){
								$cookie = is_array( $cookie ) ? $cookie : $this->_cookie;
								$cookieParse = true;
								if( is_array( $cookie ) ){
										$cookie = array_merge($this->_cookie, $cookie );
										$this->_cookie 	=  $cookie;
										$cookieParse 	=  http_build_query( $cookie );
										$cookieParse 	=  str_ireplace('&','; ',$cookieParse);
								}
								$this->_options[CURLOPT_COOKIE] = $cookieParse;
								if( isset( $this->_options[CURLOPT_COOKIEJAR] ) ){
											unset($this->_options[CURLOPT_COOKIEJAR]);
								} 		
								if( isset( $this->_options[CURLOPT_COOKIEFILE] )){
											unset($this->_options[CURLOPT_COOKIEFILE]);
								}			
						}else{
								$this->cookiejar( );
						}
			return $this;
		}
		/**
		 * [parseSetCookie 解析头部set-cookie值]
		 * @param  [string] $header 			 [通过CURLOPT_HEADER开启值]
		 * @return [array]  $this->_cookie       [成员变量_cookie]
		 */
		private function parseSetCookie( $header ){
					if( empty( $header )) 
								return false;
					preg_match_all("/set\-cookie:([^\r\n]*)/i", $header, $cookiematches); 
					$cookiePreg	= isset( $cookiematches[1] ) ? $cookiematches[1] : false;
					if(empty( $cookiePreg) || !is_array( $cookiePreg ) ){
								return false;
					}
					foreach( $cookiePreg as $key => $val ){
								$cookieItem = trim( str_ireplace('; ','&',$val )); 
								parse_str( $cookieItem,$itemArr );
								if(empty( $itemArr )|| !is_array( $itemArr )) continue;
								$this->_cookie = array_merge( $this->_cookie, $itemArr );
					}
				return $this->_cookie;
		}
		private function parseSetLocation( $header ){
					if(empty( $header ))
								return false;
					preg_match_all("/Location:([^\r\n]*)/i", $header, $locationmatches); 
					$locationmatches	= isset( $locationmatches[1] ) ? $locationmatches[1] : false;
					if(empty( $locationmatches) || !is_array( $locationmatches ) ){
								return false;
					}
					foreach( $locationmatches as $key => $val ){
								$this->_location = trim( $val );
					}
				return $this->_location;
		}

		/**
		 * [_verify 是否开启安全证书验证 默认，都不开启]
		 * @param  boolean $verifypeer [ https请求 是否验证对等证书和hosts ]
		 * 禁用后cURL将终止从服务端进行验证。
		 * 1,使用CURLOPT_CAINFO选项设置证书 或 2,使用CURLOPT_CAPATH选项设置证书目录 
		 * 如果CURLOPT_SSL_VERIFYPEER被启用，CURLOPT_SSL_VERIFYHOST(默认值为2)需要被设置成TRUE否则设置为FALSE。
		 * 
		 * @param  integer $verifyhost [是否检查服务器SSL证书名称]
		 * 设为0表示不检查证书
		 * 设为1表示检查证书中是否有CN(common name)字段
		 * 设为2表示在1的基础上校验当前的域名是否与CN匹配
		 * ( libcurl_7.28.1之后的版本 )这个调试选项由于经常被开发者用错，被去掉了.目前也不支持1了，只有0/2两种取值。
		 * 
		 * @param  string  $cadir      [一个保存着多个CA证书的目录]
		 * @param  string  $cainfo     [一个保存着1个或多个用来让服务端验证的证书的文件名]
		 * 只支持 pem格式的CA证书
		 * 可能需要绝对路径.(当没有配置CURLOPT_CAPATH时)
		 * 
		 * @return [type]              [this]
		 */
		public function verify( $verifypeer=false,$verifyhost=0,$cadir='',$cainfo='' ){
						$this->_options[CURLOPT_SSL_VERIFYPEER] = $verifypeer;
						$this->_options[CURLOPT_SSL_VERIFYHOST] = $verifyhost;
				if( $verifypeer==true ){
						if( !empty( $cadir ) ){
							$this->_options[CURLOPT_CAPATH] = $cadir;	
						}
						if( !empty( $cainfo )){
							$this->_options[CURLOPT_CAINFO] = $cainfo;//dirname(__FILE__).'/cacert.pem'
						}	
				}
			return $this;
		}
		public function nobody( $nobody=true ){
					$this->_options[CURLOPT_NOBODY] = $nobody;
			return $this;
		}
		/**
		 * [setOption 扩展配置curl选项]
		 * @param array $optArray [KEY=>$value]
		 * @return [type]          [this]
		 */
		public function setOption($optArray=[]) {
				if(	empty($optArray)||!is_array( $optArray ) ){
						return false;
				}
				foreach($optArray as $opt_key => $opt_value ) {
						$opt_key = strtoupper( $opt_key );
						$this->_options[$opt_key] = $opt_value;
				}
				return $this;
		}
		/**
		 * [curlInfo   获取一个cURL连接资源信息]
		 * @param  boolean $isOpen [是否开启]
		 * @return [type]          [this]
		 */
		public function curlInfo( ){
				return $this->_info;
		}
		/**
		 * [curlError 获取CURL执行错误信息]
		 * @return [string] [错误信息]
		 */
		public function curlError(){
					$result = "errno:{$this->_errno} & errmsg:{$this->_errmsg}";
				return $result;
		}
		/**
		 * [curlTrack 获取CURL追踪日志]
		 * @return [string] [追踪信息]
		 */
		public function curlTrack(){
					return $this->_stdtrack;
		}
		/**
		 * [curlCookie 获取setCookie]
		 * @return [array] [cookie]
		 */
		public function curlCookie(){
					return $this->_cookie;
		}
		/**
		 * [curlCookiejar 获取]
		 * @return [string] [cookiejar]
		 */
		public function curlCookiejar(){
					return $this->_cookiejar;
		}
		/**
		 * [curlHeader 获取响应head头信息,需要开启CURLOPT_HEADER]
		 * @return [string] [header]
		 */
		public function curlHeader(){
					return $this->_header;
		}
		/**
		 * [curlLocation 获取301,302跳转URL信息]
		 * @return [string] [location]
		 */
		public function curlLocation(){
					return $this->_location;
		}
		/**
		 * [parseData 解析传输数组,支持多维数组]
		 * @return [string] [location]
		 */
		public function parseData( $data=[],$encode=true ){
					if( empty( $data) || !is_array( $data )){
							return false;
					}
					foreach( $data as $key => $value ){
						if( is_array( $value )){
								$this->parseData( $value,$encode );
						}else{
							$encode_key   = $encode ? urlencode($key) : $key;
							$encode_value = $encode ? urlencode( $value ) : $value;
							if( $encode_key != $key ) unset( $data[$key] );
							$data[$encode_key] = $encode_value;
						}
						
					}
					return $data;
		}
		/**
		 * [data 请求参数]
		 * @param  array  $data [参数键值]
		 * @return [type]       [this]
		 */
		public function data( $data=[],$encode=true ){
					$this->_data = $this->parseData( $data,$encode );
				return $this; 
		}
		/**
		 * [resetpost 重置post传值选项]
		 * @return [type] [this]
		 */
		public function resetpost(){
					if(isset($this->_options[CURLOPT_POST])) unset( $this->_options[CURLOPT_POST] );
					if(isset($this->_options[CURLOPT_POSTFIELDS])) unset( $this->_options[CURLOPT_POSTFIELDS] );
				return $this;
		}
		/**
		 * [get GET请求数据]
		 * @return [type] [body]
		 */
		public function get( $url=false,$data=array() ){ 
				$this->resetpost();
				$this->url( $url );
				$getData = !empty( $data ) ? $data : $this->_data;
				if( !empty( $getData )){
						$url  = isset( $this->_options[CURLOPT_URL] ) ? $this->_options[CURLOPT_URL] : '';
						$url .= (stripos($url, '?') === false) ? '?' : '&';  
            			$url .= http_build_query($getData);
            			$this->url( $url ); 
				}
				$result = $this->execute();
				$this->close();
				return $result;
		}
		/**
		 * [post post请求数据]
		 * @return [type] [body]
		 */
		public function post( $url=false,$data=array() ){
				$this->url( $url );
				$this->resetpost();
				$postData = !empty( $data ) ? $data : $this->_data;
				if( !empty( $postData )){ 
						$options[CURLOPT_POST] 		 = true;
						$options[CURLOPT_POSTFIELDS] = http_build_query( $postData ); 
						//http_build_query为了可传多维数组,注意？曾经post http_build_query为了可传多维数组使用出现bug
						$this->setOption( $options );
				}
				$result = $this->execute();
				$this->close();
				return $result;
		}
		/**
		 * [curlFile 上传文件]
		 * @param  [type] $filepath [文件路径+名字]
		 * @return [type]           [\CURLFile|string 返回可直接用于Curl发送的模式]
		 * 无论是调用CURLFile还是用class_exists()判断CURLFile的存在性，都推荐写成\CURLFile明确指定顶层空间，防止代码包裹在命名空间内的时候崩掉
     	 * PHP5.5以后，将废弃以@文件名的方式上传文件。
		 */
		public function curlFile( $filepath ){

				/*从可靠的角度，推荐指定CURL_SAFE_UPLOAD的值，明确告知php是容忍还是禁止旧的@语法。注意在低版本PHP中CURLOPT_SAFE_UPLOAD常量本身可能不存在，需要判断
		        if (class_exists('\CURLFile')) {
		                curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
		        } else {
		            if (defined('CURLOPT_SAFE_UPLOAD')) {
		                curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
		            }
		        }
		        */

				 $filepath = realpath( $filepath );
				 return class_exists('\CURLFile') ? new \CURLFile($filepath) : '@'.$filepath;
		}


		/**
		 * [execute 执行CURL]
		 * @return [type] [this]
		 */
		private function execute( ) {
					$this->_init();
					if( !isset( $this->_options[CURLOPT_URL]) ){
							return false;
					}
					curl_setopt_array($this->_ch,$this->_options);
					$this->_response 	=    curl_exec( $this->_ch );
					$this->_errno 	  	=   curl_errno( $this->_ch );
					$this->_errmsg   	=   curl_error( $this->_ch );
					$this->_info        = curl_getinfo( $this->_ch );
					$this->_httpcode 	= curl_getinfo( $this->_ch, CURLINFO_HTTP_CODE);
					$result = $this->_response;
					if( isset( $this->_info['http_code'] ) && $this->_info['http_code'] =='200'){
							if( !empty( $this->_options[CURLOPT_HEADER] )){
									$headsize 		= isset( $this->_info['header_size'] ) ? $this->_info['header_size'] : 0;
									$this->_header  = substr($this->_response, 0, $headsize);
    								$this->_body 	= substr($this->_response, $headsize);
    								$result = $this->_body;
							}
					}
					$this->parseSetCookie( $this->_header ); 
					$this->parseSetLocation( $this->_header );
					
					if(is_resource( $this->_stdfp ) && isset( $this->_options[CURLOPT_VERBOSE] ) ){
							rewind($this->_stdfp);
							$this->_stdtrack .= stream_get_contents($this->_stdfp);
					}
				return $result;
		}

		/**
		 * [close 关闭并释放curl资源]
		 * @return [type] [void]
		 */
		private function close(){
				if( $this->_ch ){
					curl_close( $this->_ch );
				}
				if( is_resource( $this->_jarhandle) ){
							rewind( $this->_jarhandle );
							$this->_cookiejar = stream_get_contents($this->_jarhandle);
							fclose( $this->_jarhandle );
				}
		}
		/**
		 * [free 释放一个URL对应的请求结果]
		 * @return [type] [description]
		 */
		private function free(){
				$this->_data    = false;
				$this->_response= false;
				$this->_header  = false;
				$this->_body    = false;
				$this->_errno 	= false;
				$this->_errmsg	= false;
				$this->_info    = false;
				$this->_httpcode= false;
		}


}

/*
$error_no=array(
		[1] => 'CURLE_UNSUPPORTED_PROTOCOL', 
		[2] => 'CURLE_FAILED_INIT', 
		[3] => 'CURLE_URL_MALFORMAT', 
		[4] => 'CURLE_URL_MALFORMAT_USER', 
		[5] => 'CURLE_COULDNT_RESOLVE_PROXY', 
		[6] => 'CURLE_COULDNT_RESOLVE_HOST', 
		[7] => 'CURLE_COULDNT_CONNECT', 
		[8] => 'CURLE_FTP_WEIRD_SERVER_REPLY',
		[9] => 'CURLE_REMOTE_ACCESS_DENIED',
		[11] => 'CURLE_FTP_WEIRD_PASS_REPLY',
		[13] => 'CURLE_FTP_WEIRD_PASV_REPLY',
		[14] =>'CURLE_FTP_WEIRD_227_FORMAT',
		[15] => 'CURLE_FTP_CANT_GET_HOST',
		[17] => 'CURLE_FTP_COULDNT_SET_TYPE',
		[18] => 'CURLE_PARTIAL_FILE',
		[19] => 'CURLE_FTP_COULDNT_RETR_FILE',
		[21] => 'CURLE_QUOTE_ERROR',
		[22] => 'CURLE_HTTP_RETURNED_ERROR',
		[23] => 'CURLE_WRITE_ERROR',
		[25] => 'CURLE_UPLOAD_FAILED',
		[26] => 'CURLE_READ_ERROR',
		[27] => 'CURLE_OUT_OF_MEMORY',
		[28] => 'CURLE_OPERATION_TIMEDOUT',
		[30] => 'CURLE_FTP_PORT_FAILED',
		[31] => 'CURLE_FTP_COULDNT_USE_REST',
		[33] => 'CURLE_RANGE_ERROR',
		[34] => 'CURLE_HTTP_POST_ERROR',
		[35] => 'CURLE_SSL_CONNECT_ERROR',
		[36] => 'CURLE_BAD_DOWNLOAD_RESUME',
		[37] => 'CURLE_FILE_COULDNT_READ_FILE',
		[38] => 'CURLE_LDAP_CANNOT_BIND',
		[39] => 'CURLE_LDAP_SEARCH_FAILED',
		[41] => 'CURLE_FUNCTION_NOT_FOUND',
		[42] => 'CURLE_ABORTED_BY_CALLBACK',
		[43] => 'CURLE_BAD_FUNCTION_ARGUMENT',
		[45] => 'CURLE_INTERFACE_FAILED',
		[47] => 'CURLE_TOO_MANY_REDIRECTS',
		[48] => 'CURLE_UNKNOWN_TELNET_OPTION',
		[49] => 'CURLE_TELNET_OPTION_SYNTAX',
		[51] => 'CURLE_PEER_FAILED_VERIFICATION',
		[52] => 'CURLE_GOT_NOTHING',
		[53] => 'CURLE_SSL_ENGINE_NOTFOUND',
		[54] => 'CURLE_SSL_ENGINE_SETFAILED',
		[55] => 'CURLE_SEND_ERROR',
		[56] => 'CURLE_RECV_ERROR',
		[58] => 'CURLE_SSL_CERTPROBLEM',
		[59] => 'CURLE_SSL_CIPHER',
		[60] => 'CURLE_SSL_CACERT',
		[61] => 'CURLE_BAD_CONTENT_ENCODING',
		[62] => 'CURLE_LDAP_INVALID_URL',
		[63] => 'CURLE_FILESIZE_EXCEEDED',
		[64] => 'CURLE_USE_SSL_FAILED',
		[65] => 'CURLE_SEND_FAIL_REWIND',
		[66] => 'CURLE_SSL_ENGINE_INITFAILED',
		[67] => 'CURLE_LOGIN_DENIED',
		[68] => 'CURLE_TFTP_NOTFOUND',
		[69] => 'CURLE_TFTP_PERM',
		[70] => 'CURLE_REMOTE_DISK_FULL',
		[71] => 'CURLE_TFTP_ILLEGAL',
		[72] => 'CURLE_TFTP_UNKNOWNID',
		[73] => 'CURLE_REMOTE_FILE_EXISTS',
		[74] => 'CURLE_TFTP_NOSUCHUSER',
		[75] => 'CURLE_CONV_FAILED',
		[76] => 'CURLE_CONV_REQD',
		[77] => 'CURLE_SSL_CACERT_BADFILE',
		[78] => 'CURLE_REMOTE_FILE_NOT_FOUND',
		[79] => 'CURLE_SSH',
		[80] => 'CURLE_SSL_SHUTDOWN_FAILED',
		[81] => 'CURLE_AGAIN',
		[82] => 'CURLE_SSL_CRL_BADFILE',
		[83] => 'CURLE_SSL_ISSUER_ERROR',
		[84] => 'CURLE_FTP_PRET_FAILED',
		[84] => 'CURLE_FTP_PRET_FAILED',
		[85] => 'CURLE_RTSP_CSEQ_ERROR',
		[86] => 'CURLE_RTSP_SESSION_ERROR',
		[87] => 'CURLE_FTP_BAD_FILE_LIST',
		[88] => 'CURLE_CHUNK_FAILED',
);
*/