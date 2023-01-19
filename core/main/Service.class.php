<?php

namespace main;

/**
 *  系统服务类 信息与配置
 *
 */
class Service{

    private $_sysRoot 	= 	 false; //系统所在相对路径,相对于域名
    private $_uriKey  	= 	'index';
    private $_uriValue	=	'index';

    public function __construct(){ 
    		$this->_sysRoot = dirname( $_SERVER['PHP_SELF'] );
    }
    private function __clone(){
        trigger_error('Clone is not allow!',E_USER_ERROR);
    }

    /**
     * [initURI 资源请求初始化配置]
     * @param  boolean $uriKey   [默认请求类key名称]
     * @param  boolean $uriValue [默认请求类value方法]
     * @return [void]            [empty]
     */
    public function initURI( $uriKey=false,$uriValue=false ){
        if( !empty( $uriKey ) ){
            $this->_uriKey = $uriKey;
        }
        if( !empty( $uriValue ) ){
            $this->_uriValue = $uriValue;
        }
        return $this;
    }

    function sizecount($size) {
        if($size >= 1073741824) {
            $size = round($size / 1073741824 * 100) / 100 . ' GB';
        } elseif($size >= 1048576) {
            $size = round($size / 1048576 * 100) / 100 . ' MB';
        } elseif($size >= 1024) {
            $size = round($size / 1024 * 100) / 100 . ' KB';
        } else {
            $size = intval($size) . ' Bytes';
        }
        return $size;
    }
    /**
     * [env 获取web服务变量]
     * @param  [string] $var_name [环境变量key]
     * @return [string|boolen]    [环境变量value]
     */
    public function env( $var_name ){
        if (isset($_SERVER[$var_name])) {
            return $_SERVER[$var_name];
        } elseif (isset($_ENV[$var_name])) {
            return $_ENV[$var_name];
        } elseif (getenv($var_name)) {
            return getenv($var_name);
        } elseif (function_exists('apache_getenv') && apache_getenv($var_name, true)) {
            return apache_getenv($var_name, true);
        }
        return false;
    }

    public function scheme(){
        $scheme = $this->env('REQUEST_SCHEME') ? $this->env('REQUEST_SCHEME') : 'http';
        return $scheme;
    }
    /***/
    public function host(){
        $host   = $this->env('HTTP_X_FORWARDED_HOST') ? $this->env('HTTP_X_FORWARDED_HOST') :
            (
            $this->env('HTTP_HOST') ? $this->env('HTTP_HOST') : $this->env('SERVER_NAME')
            );
        return $host;
    }
    public function port(){
        $port   = $this->env('SERVER_PORT')=="80" ? "" : ":".$this->env('SERVER_PORT');
        return $port;
    }

    /**
     * [host 获取当前域名]
     * @return [string] [当前系统域名]
     */
    public function fullHost(){
        $scheme = $this->scheme();
        $host   = $this->host();
        $port   = $this->port();
        return $scheme.'://'.$host.$port;
    }

    /**
     * [serviceURI 获取请求资源定位]
     * @param  boolean $uri [description]
     * @return [string] [请求路径]
     */
    public function serviceURI( $uri = false ){
        $request_uri = $this->env('REQUEST_URI') ? $this->env('REQUEST_URI') : $this->env('REDIRECT_URL');
        $request_uri = !empty( $uri ) ? $uri : $request_uri;
        $request_arr = array();
        if(!empty($request_uri)){
            $request_arr = parse_url( $request_uri );
        }
        $redirect_uri = isset( $request_arr['path'] ) && $request_arr['path'] != '/' ? $request_arr['path']: false ;
        return $redirect_uri;
    }
    /**
     * [relativeURI 相对网站路径资源定位]
     * @param  boolean $uri [description]
     * @return [type]       [description]
     */
    public function relativeURI( $uri=false ){
        $redirect_url = $this->serviceURI( $uri );
        if( empty( $redirect_url ) ){
            return false;
        }
        $redirect_url 	= 	str_replace('?','/',$redirect_url);
        $redirect_url 	= 	str_replace('//','/',$redirect_url);
        $uri 			= 	trim($redirect_url,'/');
        $uri_arr 		= 	explode('/',$uri);
        if( !empty( $this->_sysRoot ) ){
	            $sysroot	 = 	trim( $this->_sysRoot,'/');
	            $sysroot_arr = 	explode('/',$sysroot );
	            $uri_arr 	 = 	array_diff( $uri_arr,$sysroot_arr );
        }
        $uri_arr  = array_values( array_filter( $uri_arr ));
        return !empty( $uri_arr ) ? implode('/',$uri_arr) : false;
    }

    /**
     * [parseURI 解析请求资源]
     * @desc   [除非系统相对域名根路径下，否则需配置$_sysRoot]
     * @param  boolean $uri  [有效的url]
     * @return [array]       [二级资源路径]
     */
    public function parseURI( $uri = false ){
        $relativeUri = $this->relativeURI( $uri );
        $uri_arr  = !empty( $relativeUri ) ? explode('/',$relativeUri) : array();
        $uriKey   = isset( $uri_arr[0] ) && !empty( $uri_arr[0] ) ? $uri_arr[0] : $this->_uriKey;
        $uriValue = isset( $uri_arr[1] ) && !empty( $uri_arr[1] ) ? $uri_arr[1] : $this->_uriValue;
        return array('uriKey'=>$uriKey,'uriValue'=>$uriValue );
    }

    public function userAgent(){
        $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
        if( strlen($ua) < 10 )
            return false;
        return $ua;
    }
    /**
     *  [userIp 获取客户端IP]
     *
     *   1，没有使用代理服务器:
     *         REMOTE_ADDR = 客户端IP  HTTP_X_FORWARDED_FOR = 没数值或不显示
     *   2，使用透明代理服务器的情况(Transparent Proxies):
     *        REMOTE_ADDR = 最后一个代理服务器 IP
     *        HTTP_X_FORWARDED_FOR = 客户端真实 IP(经过多个代理服务器时,这个值类似：221.5.252.160, 203.98.182.163, 203.129.72.215）
     *   3,使用普通匿名代理服务器的PHP获取客户端IP情况(Anonymous Proxies):
     *        REMOTE_ADDR = 最后一个代理服务器 IP
     *        HTTP_X_FORWARDED_FOR = 代理服务器 IP （经过多个代理服务器时，这个值类似：203.98.182.163, 203.98.182.163, 203.129.72.215）
     *      (隐藏了客户端的真实IP,但是向访问对象透露了客户端是使用代理服务器访问)
     *   4,使用欺骗性代理服务器的情况：Distorting Proxies
     *        REMOTE_ADDR = 代理服务器 IP
     *        HTTP_X_FORWARDED_FOR = 随机的 IP（经过多个代理服务器时,这个值类似：220.4.251.159, 203.98.182.163, 203.129.72.215）
     *      (同样透露了客户端是使用了代理服务器,但编造了一个虚假的随机IP)
     *   5,使用高匿名代理服务器的PHP获取客户端IP情况：High Anonymity Proxies (Elite proxies)
     *        REMOTE_ADDR = 代理服务器 IP
     *        HTTP_X_FORWARDED_FOR = 没数值或不显示
     */
    public function userIp(){
        $headers_ip = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        );
        $client_ip = false;
        foreach( $headers_ip as $header ){
            $header_value = $this->env( $header );
            if( !empty( $header_value ) && strcasecmp($header_value,'unknown') ){
                $client_ip = $header_value;
                break;
            }
        }
        if( !empty( $client_ip ) && false !== strpos($client_ip,',' ) ){
            $client_ip = reset( explode( ',', $client_ip ) );
        }
        return $client_ip;
    }

    /* *
    * 对变量进行 JSON 编码
    * @param mixed value 待编码的 value ，除了resource 类型之外，可以为任何数据类型，该函数只能接受 UTF-8 编码的数据
    * @return string 返回 value 值的 JSON 形式
    */
    public function json_encode( $value)
    {
        if ( version_compare( PHP_VERSION,'5.4.0','<'))
        {
            $str = json_encode( $value);
            $str =  preg_replace_callback( "#\\\u([0-9a-f]{4})#i",function( $matchs){ return  iconv('UCS-2BE','UTF-8',pack('H4',$matchs[1]));}, $str );
            return  $str;
        } else {
            return json_encode( $value, JSON_UNESCAPED_UNICODE);
        }
    }

    public function encode64($data) { 
	    return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); 
	} 

	public function decode64($data) { 
	    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT)); 
	} 

    public function encrypt( $code ){
	    if(empty( $code ))
	            return $code;
	    $code = (string)$code;
	    for( $i=0;$i<strlen( $code );$i++){
	        $code[$i] = chr( ord($code[$i])+$i );
	    }
	    $encode = str_replace(array('==','='),'',$this->encode64( ~$code ));
	    return $encode;
	}

	public function decrypt( $encode ){
	    if(empty( $encode))
	            return $encode;
	    $decode = ~$this->decode64( $encode );
	    for( $i=0;$i<strlen( $decode );$i++){
	        $decode[$i] = chr( ord($decode[$i])-$i );
	    }
	    return $decode;
	}

    /**
     * 检测是否使用手机访问
     * @access public
     * @return bool
     */
    public function isMobile()
    {
        if (isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], "wap")) {
            return true;
        } elseif (isset($_SERVER['HTTP_ACCEPT']) && strpos(strtoupper($_SERVER['HTTP_ACCEPT']), "VND.WAP.WML")) {
            return true;
        } elseif (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])) {
            return true;
        } elseif (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $_SERVER['HTTP_USER_AGENT'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 当前是否ssl
     * @access public
     * @return bool
     */
    public function isSsl()
    {
        $server = array_merge($_SERVER, $this->server);
        if (isset($server['HTTPS']) && ('1' == $server['HTTPS'] || 'on' == strtolower($server['HTTPS']))) {
            return true;
        } elseif (isset($server['REQUEST_SCHEME']) && 'https' == $server['REQUEST_SCHEME']) {
            return true;
        } elseif (isset($server['SERVER_PORT']) && ('443' == $server['SERVER_PORT'])) {
            return true;
        } elseif (isset($server['HTTP_X_FORWARDED_PROTO']) && 'https' == $server['HTTP_X_FORWARDED_PROTO']) {
            return true;
        } elseif (Config::get('https_agent_name') && isset($server[Config::get('https_agent_name')])) {
            return true;
        }
        return false;
    }


}