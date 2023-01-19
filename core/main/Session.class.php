<?php

namespace main;
/**
 *  session 保存与读取
 *  
 *  模式: 1，保存于文件缓存
 *  	  2, 保存于数据库
 *     	  3, 保存于php内存
 * 
 */


class Session{

		private $_sessId = false;
		
		private $_maxlifetime = 0; //过期时间 1440默认24分钟
		/**
		 * [$_obj 储存session模式对象]
		 * @var object
		 */
		private $_obj = false;
		/**
		 * [$_sessName 会话key标识]
		 * @var string
		 */
		private $_sessName = false;
		/**
		 * [$_sessPath 会话默认保存路径]
		 * @var string
		 */
		private $_sessPath = false;

		/**
		 * [$_cookExpire Cookie 的过期时间。 这是个 Unix 时间戳，即 Unix 纪元以来（格林威治时间 1970 年 1 月 1 日 00:00:00）的秒数。 也就是说，基本可以用 time() 函数的结果加上希望过期的秒数。 或者也可以用 mktime()。 time()+60*60*24*30 就是设置 Cookie 30 天后过期。 如果设置成0，或者忽略参数， Cookie 会在会话结束时过期（也就是关掉浏览器时）。]
		 * @var boolean
		 */
		private $_cookExpire = 0; //随改随变固定配置
		/**
		 * [$_cookPath Cookie 有效的服务器路径。 设置成 '/' 时，Cookie 对整个域名 domain 有效。 如果设置成 '/foo/'， Cookie 仅仅对 domain 中 /foo/ 目录及其子目录有效（比如 /foo/bar/）。 默认值是设置 Cookie 时的当前目录。]
		 * @var string
		 */
		private $_cookPath   = '/';
		/**
		 * [$_cookDomain Cookie 的有效域名/子域名。设置成子域名（例如 'www.example.com'），会使 Cookie 对这个子域名和它的三级域名有效（例如 w2.www.example.com）。 要让 Cookie 对整个域名有效（包括它的全部子域名），只要设置成域名就可以了（这个例子里是 'example.com'）。]
		 * @var string
		 */
		private $_cookDomain = '';
		/**
		 * [$_cookSecure 设置这个 Cookie 是否仅仅通过安全的 HTTPS 连接传给客户端。 设置成 TRUE 时，只有安全连接存在时才会设置 Cookie。 如果是在服务器端处理这个需求，程序员需要仅仅在安全连接上发送此类 Cookie （通过 $_SERVER["HTTPS"] 判断）。]
		 * @var boolean
		 */
		private $_cookSecure = false;
		/**
		 * [$_cookHttponly 设置成 TRUE，Cookie 仅可通过 HTTP 协议访问。 这意思就是 Cookie 无法通过类似 JavaScript 这样的脚本语言访问。 要有效减少 XSS 攻击时的身份窃取行为，可建议用此设置（虽然不是所有浏览器都支持），不过这个说法经常有争议。 PHP 5.2.0 中添加。 TRUE 或 FALSE]
		 * @var boolean
		 */
		private $_cookHttponly=false;


		public function __construct( $mode=false,$cookieCfg=false ){
				$this->init(  $mode   );
				$this->start( $cookieCfg );
		}
		/**
		 * [init 会话预处理]
		 * [objclass]  $objclass [会话预处理对象]
		 * [obj]  $this [this]
		 */
        public function init( $mode=false ){
        		if( empty($mode) ){
						$this->_obj = new \main\Session\SessFile();
				}else{
						$class ="\main\Session\\".$mode;
						$this->_obj = new $class();
				}
        		//$this->_obj = $sessObj;	
        		//$this->_maxlifetime = ini_get('session.gc_maxlifetime');	//gc回收垃圾的最大生命周期
        		//ini_set('session.cookie_lifetime' ,  20 );  //
        		//ini_set('session.gc_maxlifetime'  , $this->_maxlifetime );  //session生命周期
        		/* 初始化配置 */
				ini_set('session.use_cookies','1'); //使用Cookie储存SessionID
        		if( is_object( $this->_obj ) ){
	        			ini_set('session.save_handler','user');
	        			session_set_save_handler(
							    array(&$this, 'open'),
							    array(&$this, 'close'),
							    array(&$this, 'read'),
							    array(&$this, 'write'),
							    array(&$this, 'destroy'),
							    array(&$this, 'gc')
				    	);
			    //注册一个会在php中止时执行的函数.(经测试无效，需在destroy方法手动执行)
        		//register_shutdown_function('session_write_close');//防止使用对象作为会话保存管理器时可能引发的非预期行为
        		}
                return $this;
        }
        /**
         * [init 开始会话]
         * [obj]  $this [this]
         */
        public function start( ){

        		if( !empty( $this->_sessId ) ){
        				$_sessId = session_id( $this->_sessId );
        		}else{
        				$_sessId = session_id();
        		}
                if( empty( $_sessId ) ){ 
                	//会创建新会话或者重用现有会话。 如果通过 GET 或者 POST 方式，或者使用 cookie 提交了会话 ID， 则会重用现有会话
                	    session_start(); 
               	} 
                $this->_sessId = session_id();  
                //cookie中的session_name的配置
                return $this;
		}

        /**/
        public function call( $method,$args=[] ){ 
        		if( is_object( $this->_obj ) && method_exists( $this->_obj,$method ) ){
        				return call_user_func_array( array( $this->_obj,$method ), $args );
        		}
        }

		/**
		 * [open 自动开始会话或者通过调用 session_start() 手动开始会话 之后第一个被调用的回调函数]
		 * 
		 * @return [bool] [回调函数操作成功返回 TRUE，反之返回 FALSE]
		 */
		public function open($savePath,$sessionName){  /** @1  **/
				/* 打开数据库连接，或，创建文件路径，或缓存句柄 */
				$this->_sessPath = $savePath;
				$this->_sessName = $sessionName;
				$this->call('open',func_get_args());
				return true;
		}
		/**
		 * [read 如果会话中有数据，read 回调函数必须返回将会话数据编码（序列化）后的字符串。 如果会话中没有数据，read 回调函数返回空字符串。]
		 * @param  [type] $sessionId [会话ID]
		 * @return [string]     [sessionId数据]
		 */
		public function read($sessionId){       /** @2  **/
				/*根据 sessionId 获取数据 return (string)@file_get_contents("$this->savePath/sess_$id");*/
				/*验证逻辑参考: 1,根据sessId查询结果,为空,删除$this->destroy($sessId); 
				2,根据sessionId,userIp,userAgent等验证sessId的有效性,无效,删除$this->destroy($sessId);*/
				$contents = $this->call('read',func_get_args());
				return $contents;
		}
		/**
		 * [write 在会话保存数据时会调用 write 回调函数]
		 * PHP 会在脚本执行完毕或调用 session_write_close() 函数之后调用
		 * Note:
		 * PHP 会在输出流写入完毕并且关闭之后 才调用 write 回调函数， 所以在 write 回调函数中的调试信息不会输出到浏览器中。 如果需要在 write 回调函数中使用调试输出， 建议将调试输出写入到文件。
		 * 
		 * @param  [string] $sessionId   [当前会话 ID]
		 * @param  [string] $data 	     [由 PHP 根据 session.serialize_handler 设定值的格式来完成]
		 * @return [bool]       		 [true|false]
		 */
		public function write($sessId,$data){  /** @4  **/
				/*保存sessionId的数据 return file_put_contents("$this->savePath/sess_$id", $data) === false ? false : true;*/
				$this->call('write',func_get_args());
				return true;
		}
		/**
		 * [close 在 write 回调函数调用之后调用。 当调用 session_write_close() 函数之后，也会调用 close 回调函数]
		 * @return [type] [此回调函数操作成功返回 TRUE，反之返回 FALSE]
		 */
		public function close(){              /** @5  **/
				/*关闭数据库连接，或，文件名柄 mysqli_close($link);*/
				$this->call('close',func_get_args());
				return true;
		}
		/**
		 * [destroy 当调用 session_destroy() 函数]
		 * 当调用 session_destroy() 函数， 或者调用 session_regenerate_id() 函数并且设置 destroy 参数为 TRUE 时， 会调用此回调函数。此回调函数操作成功返回 TRUE，反之返回 FALSE.
		 * @param  [type] $sessionId [会话ID]
		 * @return [type]     [成功返回 TRUE，反之返回 FALSE]
		 */
		public function destroy( $sessId ){   /** @3  **/
				session_write_close(); //即时关闭session锁,已注册不需要手动关闭
				$_SESSION = array();   //注销$_SESSION数据 封装方法防止类外部可直接$_SESSION访问
				/*删除sessionId数据*/
				$this->call('destroy',func_get_args());
				return true;
		}

		/***** 不靠谱请自行写个清理业务
		 * [gc 为了清理会话中的旧数据，PHP 会不时的调用垃圾收集回调函数]
		 * @param  [type] $maxlifetime [ lifetime 参数由 session.gc_maxlifetime 设置]
		 * @return [bool]              [操作成功返回 TRUE，反之返回 FALSE]
		 */
		public function gc( $maxlifetime ){
				/*超过最大生命周期的时候删除数据*/
				/* foreach (glob("$this->savePath/sess_*") as $file) {
            			if (filemtime($file) + $maxlifetime < time() && file_exists($file)) {
                			unlink($file);
            			}
        			}
				*/
				$this->call('gc',func_get_args());
				return true;
		}
		/**
		 * [create_sid 当需要新的会话 ID 时被调用的回调函数]
		 * session_set_save_handler 最后一个回调参数
		 * @return [string] [返回值应该是一个字符串格式的、有效的会话 ID]
		 */
		public function create_sid(){
				//return session_create_id('pre_');
		}
		/**
		 * [cookieInit 配置初始化cookie]
		 * @param  array  $option [$expire,$path,$domain,$secure,$httponly]
		 * @return [obj]         [this]
		 */
		public function cookieInit( ){
					$argv = func_get_args();
					$argv = isset( $argv[0] ) && is_array( $argv[0] ) ? $argv[0] : $argv;
					if( isset( $argv[0] ) && !empty( $argv[0] ) ) 	$this->_cookExpire   = time()+$argv[0];
					if( isset( $argv[1] ) && !empty( $argv[1] ) ) 	$this->_cookPath     = $argv[1];
					if( isset( $argv[2] ) && !empty( $argv[2] ) ) 	$this->_cookDomain   = $argv[2];
					if( isset( $argv[3] ) && !empty( $argv[3] ) ) 	$this->_cookSecure   = $argv[3];
					if( isset( $argv[4] ) && !empty( $argv[4] ) ) 	$this->_cookHttponly = $argv[4];
					$sessName 			= session_name();
					$sessValue			= $this->_sessId;
					setCookie( $sessName,'', 0,'/' ); //删除默认sessName配置
					//$_COOKIE['test1'] = $sessValue;    
					setcookie( $sessName, $sessValue, $this->_cookExpire, $this->_cookPath, $this->_cookDomain, $this->_cookSecure );
				return $this;
		}
		/**
		 * [cookieFree 恢复初始配置]
		 * @return [obj] [this]
		 */
		public function cookieFree(){
					$this->_cookExpire   = 0;
					$this->_cookPath     = '/';
					$this->_cookDomain 	 = '';
					$this->_cookSecure 	 = false;
					$this->_cookHttponly = false;
				return $this;
		}
		/**
		 * [cookie 获取或保存cookie,支持保存value数组]
		 * @param  [type]  	$key   	[cookie键]
		 * @param  [string] $value 	[cookie值]
		 * @param  [list|array] ...	[setcookie配置参数:expire,path,domain,secure]
		 * @return [string]         [description]
		 */
		public function cookie( $key, $value=false ){
				$argv = array_slice( func_get_args(),2 );
				$argv = isset( $argv[0] ) && is_array( $argv[0] ) ? $argv[0] : $argv ;
				$this->cookieInit( $argv );
				if( $value===false ){ //获取cookie	
					$cookieValue =  isset( $_COOKIE[$key] ) ? $_COOKIE[$key] : false;
					if( !empty( $cookieValue ) && !is_array( $cookieValue ) && !is_null(json_decode($cookieValue)) ){
							return json_decode( $cookieValue,true );
					}else{
							return $cookieValue;
					}
				}else{	//设置cookie
					if( is_array( $value ) ){
							$value = json_encode( $value );
					}
					$_COOKIE[$key] = $value;
					return setcookie( $key, $value,$this->_cookExpire,$this->_cookPath,$this->_cookDomain,$this->_cookSecure );
				}
		}
		public function removeCookie( $key ){
				   unset( $_COOKIE[$key] );
				   return setcookie( $key,'', 0);
		}
		/**
		 * [session 获取或设置session]
		 * @param  [string]  		$key   [会话key,false:清空会话 true:获取所有]
		 * @param  [boolean|string|null] $value [会话value,false代表取数据,null代表删除]
		 * @param  [obj instance]   $obj   [自定义会话处理类] 			
		 * @return [boolean|string]        [option result]
		 */
		public function session( $key,$value=false ){

				if($key===false){
						$_SESSION = array();
						return true;
				}elseif($key===true){
						return $_SESSION;
				}
				$result = false;
				if( $value===false ){
						$result = isset( $_SESSION[$key] ) ? $_SESSION[$key] : false;
				}else if( $value===null){
						unset( $_SESSION[$key] );
						$result = true;
				}else{
						$_SESSION[$key] = $value;
						$result = true;
				}
				return $result;
		}

		/***/
		public function free(){ 
                             session_destroy();//清空内存中的cookie或者是$_SESSION = array();
        					 session_unset();//删除服务器端的session文件
        }
        /**/
        public function __destruct(){
        					 $this->free();
        }


}