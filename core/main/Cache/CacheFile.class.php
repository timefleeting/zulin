<?php

namespace main\Cache;


/**
 *  cache 文件模式缓存
 */
class CacheFile implements Cache{

		/**
		 * [$_maxlife 最大生命周期]
		 * @var [int]
		 */
		private $_maxlife;

		private $_cachePath = false; //缓存路径

		private $_ext = 'cached';    //文件扩展名

		public function __construct( ){
                $upload = MAININFO;  //默认为当前脚本下的目录
                $this->_cachePath = $upload.DIRECTORY_SEPARATOR.'CacheFile'.DIRECTORY_SEPARATOR;
		}
		private function __clone(){
            	trigger_error('Clone is not allow!',E_USER_ERROR);
        }
		//获取存储路径
		function cachePath( $mode = 0777 ){
 				$path = $this->_cachePath;
 				$path = $path.DIRECTORY_SEPARATOR.config('workspace');
 				$path = str_replace(array('\\\\\\','\\\\','\\','///','//','/'),DIRECTORY_SEPARATOR,$path );
				if( !empty($path ) && !is_dir( $path ) ){ 
						$mkdir = mkdir( $path,$mode,true );
						if( !$mkdir ){
								die("cache path can not create!");
								return false;
						}	
				}
				return $path;
		}
		/*
		 * 存储文件路径
		 * @param $key [缓存文件key, 使用.表示多层级]
		 * @param  [boolen] $mkdir	 [是否创建目录,只有写入操作才有创建]
		 * @param $mode[建立目录的模式]
		 * @return $cacheFile  [完整缓存文件路径]
		 */
		function cacheFile( $key,$mkdir=false,$mode = 0777 ){
				$path = $this->cachePath();
				if( !$path || empty( $key ) ){
						return false;
				}
				$parseKey   = explode('.',$key); 
    			$count      = count($parseKey); 
    			$hashKey    = md5( $key );
    			if($count>1){
    					array_pop($parseKey);
    					$levelDir = implode(DIRECTORY_SEPARATOR,$parseKey);
    			}else{
    					$levelDir = $hashKey[0].$hashKey[1].DIRECTORY_SEPARATOR.$hashKey[2].$hashKey[3];
    			}
    			$cachePath = $path.DIRECTORY_SEPARATOR.$levelDir;
    			$cachePath = str_replace(array('\\\\\\','\\\\','\\','///','//','/'),DIRECTORY_SEPARATOR,$cachePath );

    			if( $mkdir == true && !is_dir( $cachePath ) ){
            				    $status  = mkdir( $cachePath,$mode,true );
				            	if( !$status ){
				            			die("cache cacheFile can not create!");
											return false;
								}	
            	}
    			$cacheName  = $hashKey.'.'.$this->_ext;
    			$cacheFile = $cachePath.DIRECTORY_SEPARATOR.$cacheName;
            	return $cacheFile;
		}
		/**
		 * [read 读取文件缓存]
		 * @param  [string]   $key     [缓存路径规则]
		 * @param  [integer]  $timeout [缓存读取数据时效(秒),0或false不失效,永久读取]
		 * @return [string|arrary]     [数组或字符串]
		 */
		function read($key, $timeout=false){
				$cacheFile = $this->cacheFile( $key );		
				if( empty( $cacheFile ) || !file_exists( $cacheFile ) ){
							return false;
				}
				clearstatcache(); //清除文件状态缓存
				if(!empty($timeout) && (time()-filemtime( $cacheFile)) >= (int)$timeout  ){
                            return false;
                }
                $str  = file_get_contents( $cacheFile );
                if( !is_null(json_decode( $str ) ) ){
                		$str = json_decode( $str,true );
                }
                return $str;	
		}
		/**
		 * [write 写入文件缓存]
		 * @param  [string] $key  [缓存路径规则]
		 * @param  [array]  $data [缓存数据,支持数组，对象，字符串，数字]
		 * @return [int]          [写入字数]
		 */
		function write( $key,$data ){
			  	$cacheFile = $this->cacheFile( $key,true );	
				if( empty( $cacheFile ) ){
							return false;
				}
				$str = '';
				if( is_array( $data ) ){
						$str = json_encode( $data );
				}else if( is_object($data ) ){
						$data_arr = get_object_vars($data);
						$str = json_encode( $data_arr );
				}else if( is_string( $data ) || is_numeric( $data ) ){
						$str = $data;
				}else{
						return false;
				} 
				$fp = @fopen($cacheFile, 'w');
			    $rs = @fwrite($fp, $str);
			          @fclose($fp);
			    return $rs;
		}
		/**
		 * [delete 删除缓存文件]
		 * @param  [string]  $key  [缓存key文件]
		 * @return [boolen]        [删除文件状态]
		 */
		function delete($key){
				$cacheFile = $this->cacheFile( $key );
				if( empty( $cacheFile ) ){
						return false;
				}
				$info = pathinfo( $cacheFile );
				$ext  = isset( $info['extension'] ) ? $info['extension'] : '';
				if( !empty( $ext ) && $ext == $this->_ext ){
						return unlink( $cacheFile );
				}
				return true;
		}
		/**
		 * [clear 删除所属缓存目录]
		 * @param  [string]  $key  [缓存key并获取目录结构]
		 * @param  [boolen]  $mode  [返回值模式,true:返回删除的文件]
		 * @return [array|boolen]   $unlinkArr   [删除的文件数组|boolen]
		 */
		function clear($key,$mode=false){
				$cacheFile = $this->cacheFile( $key );
				if( empty( $cacheFile ) ){
						return false;
				}
				$cachedDir = dirname( $cacheFile );
				if( !is_dir( $cachedDir )){
						return false;
				}
				if( empty($this->_cachePath ) || false === stripos($cachedDir,$this->_cachePath) ){
						return false;
				}
				 $rs = $this->rm( $cachedDir,$mode );
				return $rs;
		}
		/**
		 * [clearAll 清除所有缓存]
		 * @param  [boolen] $mode  [返回值模式,true:返回删除的文件]
		 * @return [array|boolen]   $unlinkArr   [删除的文件数组|boolen]
		 */
		public function clearAll( $mode= false ){
				 $cachePath = $this->cachePath();
				 if( !$cachePath ){
						return false;
				 }
				 $rs = $this->rm( $cachePath,$mode );
				return $rs;
		}
		/**
		 * [rm 删除目录,必须是指定格式文件的目录，防止误删除]
		 * @param  [string] $dir [目录路径]
		 * @param  [boolen] $mode [返回值模式]
		 * @return [array|boolen]   $unlinkArr   [删除的文件数组|boolen]
		 */
		public function rm( $dir,$mode=false ){
				if( !is_dir( $dir )){
				 		return false;
				}
				$dir = rtrim( $dir,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
				$unlinkArr = true;
				if( $mode ==true ){
					 $unlinkArr = array();
					 $unlinkArr[] = $dir;
				}
				foreach( glob( $dir."*") as $file ){
						if( is_dir( $file ) ){
								$rs = $this->rm( $file,$mode );
								if( $mode == true ){
										$unlinkArr = array_merge($unlinkArr,$rs );
								}
						}else{
								$info = pathinfo( $file );
								$ext  = isset( $info['extension'] ) ? $info['extension'] : '';
								if( !empty( $ext ) && $ext == $this->_ext ){
										unlink( $file );
										if( $mode == true ){
												$unlinkArr[] = $file;
										}
								}
						}
				}
				@rmdir( $dir );
				return $unlinkArr;
		}


}