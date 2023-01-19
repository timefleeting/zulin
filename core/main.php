<?php
/**
 *  核心程序引挚
 *  php版本 >=5.3
 *  大道至简
 *  @author hewenbin
 *  @version v3.0
 */
if (version_compare("5.2", PHP_VERSION, ">")) {
     	die("PHP 5.3 or greater is required!!!");
 }
/* defined Config */
date_default_timezone_set('PRC');
header("Content-Type:text/html;charset=utf-8");
define('DEBUG', true);
define('DS', DIRECTORY_SEPARATOR);
define('PS', PATH_SEPARATOR);
define('MAINSPACE',dirname(__FILE__) );
define('MAININFO',MAINSPACE.DS.'info'); //资源公共空间
/*  -- end defined -- */

/* debug msg show */
if(DEBUG){
    ini_set('html_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}else{
    ini_set('html_errors', 0);
    ini_set('error_reporting', 0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}
/*  --  end debug msg show -- */
//返回当前的毫秒时间戳
function msectime() {
   list($msec, $sec) = explode(' ', microtime());
    $msectime =  sprintf('%.0f', (floatval($msec) + floatval($sec)) * 10000);
    $msectime =  substr($msectime,0,-4).'.'.substr($msectime,-4);
    return $msectime;
}

/****核心方法****/
/*
 * M:调用main::中的静态public方法
 */
function M($class,$method=false){
	if(empty($class))
			return false;
	$args 	   = array_slice( func_get_args(),2 );
	$callArgs  = array($class,$method);
	$callArgs  = !empty( $args ) ? array_merge( $callArgs,$args ) : $callArgs ;
	return call_user_func_array( array('main','mainfad58de7366495db4650cfefac2fcd61'), $callArgs );
}
/*
 * V:调用应用模板主题
 */
function V($dir,$file){
	$args 	   = array_slice( func_get_args(),2 );
	$callArgs  = array($dir,$file);
	$callArgs  = !empty( $args ) ? array_merge( $callArgs,$args ) : $callArgs ;
	return call_user_func_array( array('main','view'), $callArgs );
}
/*
 * C:调用应用业务类包
 */
function C($class,$method){
	$args 	   = array_slice( func_get_args(),2 );
	$callArgs  = array($class,$method);
	$callArgs  = !empty( $args ) ? array_merge( $callArgs,$args ) : $callArgs ;
	return call_user_func_array( array('main','apply'), $callArgs );
}
/**
 * [Q 请求参数过滤]
 * @param bool $key   [参数类型 get,post,put,request,file...]
 * @param bool $value [默认true正常获取前端数据, false删除(unset)get,post,request全局数据，else modifier]
 * @return bool|mixed
 */
function Q( $key=false, $value=true ){
    	$query = array();
    	if($pos = strpos($key, '.')) {
            // 指定参数来源
            list($method, $key) = explode('.', $key, 2);
            if (!in_array($method, ['get', 'post', 'put', 'delete', 'param', 'request', 'file'])) {
                $key    = $method . '.' . $key;
                $method = 'param';
            }
        } else {
            // 默认为自动判断
            	$method = 'param';
        }
        return M('Request',$method, $key,$value );
}
/*
 * 公共配置项
 */
function config( $key ){
		$callArgs  = array($key);
		return call_user_func_array( array('main','configfad58de7366495db4650cfefac2fcd61'), $callArgs );
}
/*通用方法*/
/**
 * URL重定向
 * @param string $url 重定向的URL地址
 * @param integer $time 重定向的等待时间（秒）
 * @param string $msg 重定向前的提示信息
 * @return void
 */
function redirect($url, $time=0, $msg=''){
    //多行URL地址支持
    $url = str_replace(array("\n", "\r"), '', $url);
    if (empty($msg))
        $msg    = "系统将在{$time}秒之后自动跳转！";
    if (!headers_sent()){
        if (0 === $time) {
            header('Location: ' . $url);
        }else{
            header("refresh:{$time};url={$url}");
            echo($msg);
        }
        exit();
    } else {
        $str = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
        if ($time != 0)
            $str .= $msg;
        exit($str);
    }
}
/**
 * storage 数据存储
 * @param  [type]  $accessKey [hash密钥唯一,最好使用注册机制校验]
 * @param  [type]  $key       [获取数据key.true:获取所有.false:删除所有]
 * @param  boolean $value     [数据值.null:删除该元素]
 * @return [type]             [description]
 */
function storage($accessKey,$key=true,$value=false){
		 if(empty($accessKey)||!is_string($accessKey))
		 		return false;
		 static $storageObj;
		 if( !isset( $storageObj[$accessKey] )){
		 		 $storageObj[$accessKey] = M('Storage',false,$accessKey); 
		 }
		 if( $key===null && $value===null ){
		 		return $storageObj[$accessKey]->clear();
		 }
		 if( !empty($key) && $value===null){
		 		return $storageObj[$accessKey]->delete($key);
		 }
		 if( $key!==true && !empty($key) && !empty($value )){
		 		return $storageObj[$accessKey]->write($key,$value);
		 }
		 if( !empty($key)){
		 		return $storageObj[$accessKey]->read($key);
		 }else{
		 		return false;
		 }
}
/**
*  [session 会话操作]
*  @param	[string|bool] 		$key	[string:会话键, false:清空会话 true:获取所有]
*  @param   [string|bool|null] 	$value 	[默认false:获取, null:删除]
*/
function session($key=true,$value=false){
	   static $sessObj;
	   if( !isset( $sessObj )){
	   			$lifeTime = 0;
	   			$lifePath = config('workspace');
	   			$host     = M('Service','host');
	   			$sessObj  = M('Session',false);
	   			$sessObj->cookieInit( $lifeTime,$lifePath,$host );
	   }   
	  return $sessObj->session( $key,$value );
}
/**
*  [cached 缓存类操作]
*  @param  [string|boolen]		$key	[缓存key'.'路径规则（例：a.b.c) null:清除缓存目录下的所有缓存]
*  @param  [string|boolen|null] $value [默认false:获取缓存，string|array:设置缓存值, null删除缓存<目录>]
*  @param  [int]    $lifeTime   [获取有效期缓存]
*/
function cache( $key, $value=false, $lifeTime=3 ){
			 static $cacheObj;
			 if( !isset( $cacheObj ) ){
			 		$cacheObj = M('Cache',false);
			 }
			 if( $key ===null && $value===null ){
			 		return $cacheObj->clearAll(true);
			 }
			 if(empty($key)){
			 		return false;
			 }
			 if( $value === false ){
			 		$rs = $cacheObj->read($key,$lifeTime);
			 }elseif( $value === null ){
			 		$rs = $cacheObj->clear($key,true);
			 }else{
			 		$rs = $cacheObj->write($key,$value);
			 }
			return $rs;
}
/*
*  [db 数据库操作]
*  @param  [string|boolen] $table  [表名]
*  @param  [int|string]    $db     [配置选项选择]
*  @return [obj]  $dbObject        [数据库操作对象]
*/
function db( $table=false, $db=false ){
		  static $dbConn;
		  $workspace= config('workspace');
		  $config   = config('database');
		  $db 	    = $db ? $db : 0;
		  $dbConfig = isset( $config[$db] ) ? $config[$db] : false;
		  $dbKey    = md5($workspace.'.'.$db.'.'.$table);
		  if( !isset( $dbConn[$dbKey] ) ){
						$dbConn[$dbKey] = M('Sql','start',$dbConfig);
		  }
	return $dbConn[$dbKey]->table( $table );
}
/*
 * [page 生成页码操作]
 * @param  [int] $nowPage    [当前页数]
 * @param  [int] $totalRows  [总记录数]
 * @param  [int] $listRows   [每页显示数量]
 * @param  [int] $rowsArr    [每页显示数量分组]
 */
function page($nowPage,$totalRows,$listRows=0,$rowsArr=[]){
		$listRows = !empty( $listRows ) ? $listRows : 20;
		$rowsArr  = !empty( $rowsArr )  ? $rowsArr  : [20,40,60];
		$page     = M('Page',false,$nowPage, $totalRows,$listRows,$rowsArr);
		return $page->show();
}
/*
 * cUri controller应用控制业务层uri路径
 */
function cUri($class,$method){
		if(empty($class)||empty($method)) return false;
		$serverspace= dirname($_SERVER['PHP_SELF']);
		return cleares($serverspace.DIRECTORY_SEPARATOR.$class.DIRECTORY_SEPARATOR.$method);
}

function cTrace($ckey='C'){
		$backtrace = debug_backtrace();
    	array_shift($backtrace); //去除当前执行点
    	if(empty($backtrace)) return false;
    	$cTrace = array();
    	foreach( $backtrace as $key => $item ){
    		$func = $item['function'];
    		if($func===$ckey){ // active会包含模板调用
    				$cTrace[] = $item['args'];
    		}
    	}
    	return $cTrace;
}
/*
 * clear excess separator
 */
function cleares($uri){
		return str_replace( array('\\\\\\','\\\\','\\','////','///','//'), DIRECTORY_SEPARATOR, $uri );
}

//应用业务统一入口规则.htaccess
function htaccess(){
$htaccess = <<<EOF
<IfModule mod_rewrite.c>
RewriteEngine on
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-l
RewriteRule ^(.*)$ index.php
</IfModule>
EOF;
return $htaccess;
}

class main{

	public static function run( $custom=array() ){
    		$startTime = msectime();
    		self::system($custom);
    		$uri 	= M('Service','parseURI');
    		$result = self::active( $uri['uriKey'],$uri['uriValue'] );
    		print( $result );
    		$endTime = msectime()-$startTime;
    		//echo "<br />".$endTime."<br />";
    }

    /** system 系统结构配置*/
    private static function system($custom=array()){
    		static $system = array(
	    				'apply'=>array(
	    						'title'=>'应用系统包(M-V-C系统包所属目录)',
	    						'value'=>'/',
	    				),
	    				'view' 	=> array(
	    						'title'  =>'应用视图模板层',
	    						'value'  =>'view',       	// 模板目录
	    						'theme'  =>'default',    	// 模板主题
	    						'assets' =>'assets',        // 模板资源
	    						'suffix' =>'html',	     	// 模板扩展名
	    				),
	    				'controller'=> array(
	    						'title'  => '应用控制业务层', 
	    						'value'  => 'controller',    // 应用控制业务类包
	    				),
	    				'database' => array( //支持跨库切换
	    					'0' => array( 	 //可写
	    							'title'		=>'可写数据库配置',
		    						'type'		=> 'mysql',
		    						'charset'   => 'utf8',
						            'host'      => '',
						            'prefix'    => '',
						            'name'      => '',
						            'user'      => '',
						            'pwd'       => '',
						            'port'      =>  '3306',
	    					),
	    					'1' => array(   //只读  
									'title'		=>'只读数据库配置',
		    						'type'		=> 'mysql',
		    						'charset'   => 'utf8',
						            'host'      => '',
						            'prefix'    => '',
						            'dbname'    => '',
						            'user'      => '',
						            'pwd'       => '',
						            'port'      =>  '3306',
	    					),
	    						
	    				),
    		);
    		if(!empty($custom)){
    				foreach( $system as $key => &$item ){
    					foreach( $item as $systemKey => &$systemValue ){
    							if(isset($custom[$key][$systemKey])){
    									$systemValue = $custom[$key][$systemKey];
    							}
    					}	
    				}
    		}
    		return $system;
    }
	/** config 系统配置参数
	 *  主要作为工具包公共参数配置
	 */
	public static function configfad58de7366495db4650cfefac2fcd61( $key=false ){
			static $config;
				   $system = self::system();
				   $config['workspace']	= dirname( $_SERVER['PHP_SELF'] ); //请求调用应用的空间根路径(谁发起调用谁的路径)
				   $config['database']  = $system['database'];
			return isset($config[$key]) ? $config[$key] : false;
	}

    public static function active($uriKey,$uriValue){
    		if(empty($uriKey) || empty($uriValue ))
    				return false;
    		$controller = self::apply($uriKey,$uriValue);
    		$viewArgs   = array( $uriKey,$uriValue );
    		if( !empty($controller )){
    				$viewArgs = array_merge( $viewArgs, array($controller) );
    		}
    		$view = call_user_func_array( array('self','view'), $viewArgs );
    		if( empty( $view ) ){
				if( is_array( $controller )){
					$controller =  json_encode( $controller );
				}elseif( is_object( $controller ) ){
						$controller =  json_encode( get_object_vars( $controller ) );
				}
					return $controller;
			}else{
					return $view;
			}
    }
    /*
     * apply 应用接口调用
     * @param $class [默认使用controller空间调用,支持跨包调用:pack1.pack2.namespace.$class(pack1与controller同级)在包空间pack1/pack2/命名空间类包namespace下调用$class]
     * @param $method[调用方式]
     */
    public static function apply($class,$method){
    		$args = array_slice( func_get_args(),2 );
    		$callArgs = self::applyPath($class,$method);
    		if(empty($callArgs)||!is_array($callArgs)){
    				return false;
    		}
			$callArgs = !empty( $args ) ? array_merge( $callArgs,$args ) : $callArgs;
			return call_user_func_array( array('self','reflect'), $callArgs );
    }
    public static function applyPath($class,$method,$returnPath=false){
    		    	if(empty($class)||empty($method)){
		    				return false;
		    		}
		    		$args 	    = array_slice( func_get_args(),2 );
		    		$parseClass = explode('.',$class);
		    		$parseCount = count($parseClass);
		    		$system     = self::system();
		    		$packspace  = $system['apply']['value'];
		    		$namespace  = $system['controller']['value'];
		    		$className  = $class;
		    		if($parseCount<=1){ //调用业务控制包
		    				$className = $parseClass[0];
		    		}elseif($parseCount==2){
		    				$namespace = $parseClass[0];
		    				$className = $parseClass[1];
		    		}else{
		    				$sliceClass = array_slice($parseClass,0,$parseCount-2);
		    				$packspace = $packspace.'/'.implode('/',$sliceClass);
		    				$namespace = $parseClass[$parseCount-2];
		    				$className = $parseClass[$parseCount-1];
		    		}
		    		if( !empty( $returnPath )){
		    				$serverspace= dirname($_SERVER['PHP_SELF']);
							$returnPath = $serverspace.DIRECTORY_SEPARATOR.$packspace.DIRECTORY_SEPARATOR.$namespace.DIRECTORY_SEPARATOR.$className.DIRECTORY_SEPARATOR.$method;
							return cleares($returnPath);
		    		}
		    		return array($packspace,$namespace,$className,$method);
    }
    /*
     *  view 应用模板主题调用
     *  @param $dir   模板视图目录
     *  @param $file  模板视图文件
     *  @param $paramFlag 模板变量前缀
     */
    public static function view($dir,$file){
    		if(empty($dir)||empty($file))
    				return false;
    		$args 	    = array_slice( func_get_args(),2 );
    		$system     = self::system();
    		$workspace  = dirname($_SERVER['SCRIPT_FILENAME']);
    		$packspace  = $system['apply']['value'];
    		$viewspace  = $system['view']['value'];
    		$themespace = $system['view']['theme']; //主题
    		$suffix     = $system['view']['suffix'];//文件名后缀
    		$file = explode('.',$file); //注意如果加了后缀？
    		$file = end($file);
    		$tplFile   = $workspace.DIRECTORY_SEPARATOR.$packspace.DIRECTORY_SEPARATOR.$viewspace.DIRECTORY_SEPARATOR.$themespace.DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.$file.'.'.$suffix;
    		$tplFile  = cleares( $tplFile);
    		$content = false;
			if(is_file( $tplFile )){
				if( !empty($args) ){
    				$argv = array();
    				foreach( $args as $key=>$val ){
    						$key +=1;
    						$key = 'var'.$key;
    						$argv[$key] = $val;
    				}
    				extract($argv);
	    		}
				 // 页面缓存
	        	ob_start();
	        	ob_implicit_flush(0);
					include( $tplFile );
				 // 获取并清空缓存
	        	$content = ob_get_clean();
			}else{
				return false;
			} 
	        return self::viewEngine($content);
    }
    /***/
    public static function mainfad58de7366495db4650cfefac2fcd61($class,$method){
    		if(empty($class)){
    				return false;
    		} 
    		$args   = array_slice( func_get_args(),2 );
    		$parseClass = explode('.',$class);
    		$parseCount = count($parseClass);
    		if($parseCount>1){
    				$class = implode('\\',$parseClass);
    		}
    		$callArgs = array($class,$method);
			$callArgs = !empty( $args ) ? array_merge( $callArgs,$args ) : $callArgs;
			return call_user_func_array( array('self','core'), $callArgs );
    }

    private static function viewEngine($html){
    		$system 	= self::system();
    		$serverspace= dirname($_SERVER['PHP_SELF']);
    		$packspace  = $system['apply']['value'];
    		$viewspace  = $system['view']['value'];
    		$themespace = $system['view']['theme'];
    		$assetspace = $system['view']['assets'];
    		$viewspaceDir = cleares( $serverspace.DIRECTORY_SEPARATOR.$packspace.DIRECTORY_SEPARATOR.$viewspace);
			$themespaceDir= cleares( $serverspace.DIRECTORY_SEPARATOR.$packspace.DIRECTORY_SEPARATOR.$viewspace.DIRECTORY_SEPARATOR.$themespace);
			$assetspaceDir= cleares( $serverspace.DIRECTORY_SEPARATOR.$packspace.DIRECTORY_SEPARATOR.$viewspace.DIRECTORY_SEPARATOR.$assetspace);
			$timer   = msectime();
			$html = preg_replace_callback('/({__(.*?)__})/s',function($matches){
						$relativeUrl = isset( $matches[2] ) ? $matches[2] : '/';
						$uri= dirname($_SERVER['PHP_SELF']).DIRECTORY_SEPARATOR.$relativeUrl;
						$uri = str_replace('//','/',$uri);
					    return $uri;
			},$html);
			$html = str_replace(array('__VIEW__','__THEME__','__ASSETS__','{THEME}','{TIME}'),array($viewspaceDir,$themespaceDir,$assetspaceDir,$themespace,$timer),$html);
			return $html;
    }
	/*
	 * reflect  应用业务系统装载调用
	 * @param $packspace 工作区与类包之间的目录
	 * @param $namespace 类所在命名空间(也就是类所对应的目录包)
	 * @param $className 类名
	 * @param $classMethod 方法名
	 * 
	 */
	private static function reflect( $packspace, $namespace, $className,$classMethod=false ){
				$args 	= array_slice( func_get_args(),4 ); 
				if(empty($namespace))
						return false;
				$workspace = dirname($_SERVER['SCRIPT_FILENAME']);
				$callArgs = array($workspace,$packspace,$namespace,$className,$classMethod);
				$callArgs = !empty( $args ) ? array_merge( $callArgs,$args ) : $callArgs;
				return call_user_func_array( array('self','call'), $callArgs );
	}
	/*
	 * [core 主程类接口]
	 * [string]  			$class_name 	[主程类名]
	 * [string | boolen]	$class_method	[默认false调用构造]
	 * 
	 */
	private static function core( $className,$classMethod=false ){
				$args 	   = array_slice( func_get_args(),2 );
				$workspace  = dirname(__FILE__);
				$packspace = '/';
				$namespace = __CLASS__;
				$callArgs  = array($workspace,$packspace,$namespace,$className,$classMethod);
				$callArgs  = !empty( $args ) ? array_merge( $callArgs,$args ) : $callArgs ;
				return call_user_func_array( array('self','call'), $callArgs );
	}
	/*
	 * [call  请求加载类]
	 * @param $workspace 系统工作区(1,框架系统:真实文件路径 2，业务系统:相对于请求调用的脚本为根工作区)
	 * @param $packspace 工作区与类包之间的目录(如:类包属于工作区的下级,则为空或/)
	 * @param $namespace 类所在命名空间(也就是请求类所对应的目录包)
	 * @param $className 类名
	 * @param $classMethod 方法名
	 */
	private static function call( $workspace,$packspace, $namespace, $className,$classMethod=false ){
			$args 	= array_slice( func_get_args(),5 ); 
			if(empty($workspace) || empty($namespace))
					return false;
			$rs = false; 
			$callArgs = array( $workspace,$packspace,$namespace,$className,false );
			if( empty( $classMethod ) ){ 
					$callArgs= !empty( $args ) ? array_merge( $callArgs,$args ) : $callArgs ;
					$rs      = call_user_func_array( array('self','callClass'), $callArgs );
			}else{
					$oop 	 = call_user_func_array( array('self','callClass'),$callArgs );
					$mArgs 	 = [$oop,$classMethod];
					$mArgs 	 = !empty( $args ) ? array_merge( $mArgs,$args ) : $mArgs ;
					$rs  	 = call_user_func_array( array('self','callMethod'),$mArgs );
			}
		return $rs;
	}
	/*
	* [callClass 装载包中的类]
	* 通用实例对象，静态类，单例类
	* @param  [string]  $workspace      [系统工作区]
	* @param  [string]	$packspace   	[工作区与类包之间的目录]
	* @param  [string]	$namespace 	    [类所在命名空间]
	* @param  [string]	$className 	    [类名]
	* @param  [string]	$suffix 	    [调用类文件后缀]
	* @return [obj] $instance			[对象]
	*/
	private static function callClass($workspace,$packspace,$namespace,$className,$suffix=false){
			if(empty($workspace) || empty( $namespace )){
					return false;
			}
			$objclass = "\\".$namespace."\\{$className}"; 
			$args = array_slice( func_get_args(),5 );
			self::autoload( $workspace,$packspace,$suffix ); 
			try{	
					$class = new ReflectionClass( $objclass );
			} catch (LogicException $Exception) { //Not gonna make it in here
		        	return false;
		    } catch (ReflectionException $Exception){ //class does not exist
		        	return false;
		    }
			$classInstantiable = $class->IsInstantiable(); 
			if( !$classInstantiable ){  //静态或单例,慎用:调用单例模式构造器无法传参
					static $refClass;
					$refKey = md5( $workspace.'/'.$packspace.'/'.$className.$suffix );
					if( !isset( $refClass[$refKey] )){
							//$ctor = $class->getConstructor();
							$instance = $refClass[$refKey] = $class->newInstanceWithoutConstructor();
					}else{
							$instance = $refClass[$refKey];
					}
			}else{ 
							$instance = $class->newInstanceArgs( $args );
			}
			return $instance;
	}
	/**
	 * [callMethod 接口方法调用]
	 * @param  [string]	$class_instance [实例类]
	 * @param  [string]	$class_method 	[类方法,false则为构造方法]
	 * @param  [string | array] ...     [(类|构造)方法参数]
	 */
	private static function callMethod( $class_instance,$class_method ){
			if( !is_object( $class_instance ) || empty( $class_method ) ){
					return false;
			}
			$args 	= array_slice( func_get_args(),2 );
			$result = @call_user_func_array( array( $class_instance,$class_method ), $args );//可判断方法是否存在
			return $result;
	}
	/*
	 * [autoload]
	 * @param  string  $workspace [工作区路径目录名]
	 * @param  string  $packspace [工作区下的程序包目录空间]
	 * @param  boolean $suffix    [类文件后缀]
	 * @return [object]           [description]
	 */
	public static function autoload( $workspace=false,$packspace=false, $suffix=false ){
			static $autoload_obj;
			if(empty( $suffix )){
					$key = md5($workspace.'/'.$packspace.'autoload');
			}else{
					$key = md5($workspace.'/'.$packspace.$suffix );
			}
			if(!isset( $autoload_obj[$key] )){
					$autoload_obj[$key] = new ComposerAutoloaderInit( $workspace,$packspace,$suffix );
			}
			$autoload_obj[$key]->register();
			return $autoload_obj[$key];
	}

}
/***/
class ComposerAutoloaderInit{
		private $_workspace = false;
		private $_packspace = false;
		private $_suffix    = ".class.php";
		/* $workspace  应用工作区空间
		 * $packspace  应用捆绑包空间(介于工作区与命名空间类包之间目录层)
		 */
        public function __construct( $workspace,$packspace,$suffix=false ){
        			$this->_workspace  = $workspace;
        			$this->_packspace  = $packspace;
        		if( !empty( $suffix ) ) $this->_suffix = $suffix;
        }
        public function __clone(){
                trigger_error('Clone is not allow!',E_USER_ERROR);
        }
		public function register($prepend = false)
        {
               spl_autoload_register(array($this, 'loadClass'), false, $prepend);
        }
        public function unregister()
        {
               spl_autoload_unregister( array($this, 'loadClass') );
        }
        /**  装载规则 main工作区 + [以工作区为根路径(可为空)]工作区与类包(命名空间目录)之间的包层 + 命名空间[所在目录+类名] */
        public function loadClass( $class )
        {	   
                if(empty( $class )) return false;
                $workspace = rtrim( $this->_workspace,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
                $packspace = rtrim( $this->_packspace,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR; 
                $fullSpace = $workspace.$packspace.$class;
                $file  = cleares($fullSpace);
        		$file .= $this->_suffix; 
        		if( !class_exists( $class,false ) && file_exists( $file ) ){ // false: 是否默认调用 __autoload,default:true;
        			 include( $file );			
        		}
        		return true;
        }
}

