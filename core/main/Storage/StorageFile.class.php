<?php
namespace main\Storage;

class StorageFile implements Storage{

		private $accessKey = false;
	
		private $_path   = false; //路径

		private $_ext    = 'db';  //文件扩展名

		public function __construct( $accessKey ){
				if(!empty($accessKey)&&is_string($accessKey)){
					$this->accessKey = $accessKey;
				}
				$upload = MAININFO;  //默认为当前脚本下的目录
                $this->_path = $upload.DIRECTORY_SEPARATOR.'StorageFile'.DIRECTORY_SEPARATOR;
		}
		private function __clone(){
            	trigger_error('Clone is not allow!',E_USER_ERROR);
        }

        //获取存储路径
		function storagePath( $mode = 0777 ){
				$accessKey = $this->accessKey;
				if(empty($accessKey))
						return false;
 				$path = $this->_path;
 				$path = $path.DIRECTORY_SEPARATOR.config('workspace');
 				$path = str_replace(array('\\\\\\','\\\\','\\','///','//','/'),DIRECTORY_SEPARATOR,$path );
				if( !empty($path ) && !is_dir( $path ) ){ 
						$mkdir = mkdir( $path,$mode,true );
						if( !$mkdir ){
								die("storage path can not create!");
								return false;
						}	
				}
				return $path;
		}
		/*
		 * 存储文件路径
		 * @param $accessKey [缓存文件accessKey, 使用.表示多层级]
		 * @param  [boolen] $mkdir	 [是否创建目录,只有写入操作才有创建]
		 * @param $mode[建立目录的模式]
		 * @return $storageFile  [完整缓存文件路径]
		 */
		function storageFile( $accessKey,$mkdir=false,$mode = 0777 ){
				$path = $this->storagePath();
				if( !$path || empty( $accessKey ) ){
						return false;
				}
				$parseKey   = explode('.',$accessKey); 
    			$count      = count($parseKey); 
    			$hashKey    = md5( $accessKey );
    			if($count>1){
    					array_pop($parseKey);
    					$levelDir = implode(DIRECTORY_SEPARATOR,$parseKey);
    			}else{
    					$levelDir = $hashKey[0].$hashKey[1].DIRECTORY_SEPARATOR.$hashKey[2].$hashKey[3];
    			}
    			$storagePath = $path.DIRECTORY_SEPARATOR.$levelDir;
    			$storagePath = str_replace(array('\\\\\\','\\\\','\\','///','//','/'),DIRECTORY_SEPARATOR,$storagePath );
    			if( $mkdir == true && !is_dir( $storagePath ) ){
            				    $status  = mkdir( $storagePath,$mode,true );
				            	if( !$status ){
				            			die("storageFile can not create!");
											return false;
								}	
            	}
    			$storageName  = $hashKey.'.'.$this->_ext;
    			$storageFile  = $storagePath.DIRECTORY_SEPARATOR.$storageName;
            	return $storageFile;
		}

		public function read( $key=true ){
				$accessKey = $this->accessKey;
				if(empty($accessKey) || empty($key) )
						return false;
				$storageFile = $this->storageFile( $accessKey );	
				if( empty( $storageFile ) || !file_exists( $storageFile ) ){
							return false;
				}
                $data  = file_get_contents( $storageFile );
                if( !is_null(json_decode( $data ) ) ){
                		$data = json_decode( $data,true );
                }else{
                		return false;
                }
                if( $key!==true && !empty($key ) ){
                		return isset( $data[$key] ) ? $data[$key] : false;
                }
                return $data;	
		}
		public function write($key,$data){ 
				$accessKey = $this->accessKey;
				if(empty($accessKey) || empty($key) ||!is_string( $key ) )
						return false;
				$storageFile = $this->storageFile( $accessKey,true );	
				if( empty( $storageFile ) ){
							return false;
				}
				$storage = $this->read();
				if( empty( $storage )){
					$storage = array();
				}elseif( !is_array($storage )){
						return false;
				}
				$storage[$key] = $this->warningData( $data );
				return $this->pushStorage($storage);
		}
		public function delete($key){
				$accessKey = $this->accessKey;
				if(empty($accessKey) || empty( $key ) )
						return false;
				$storage = $this->read();
				if( isset( $storage[$key] ) ){
						unset($storage[$key] );
				}
				if(empty($storage)){ //为空则删除
						$this->clear();
				}
				return $this->pushStorage($storage);
		}
		public function clear(){
				$accessKey = $this->accessKey;
				if(empty($accessKey))
						return false;
				$storageFile = $this->storageFile( $accessKey );
				$info = pathinfo( $storageFile );
				$ext  = isset( $info['extension'] ) ? $info['extension'] : '';
				if( !empty( $ext ) && $ext == $this->_ext ){
						return unlink( $storageFile );
				}
				return true;
		}
		/*
		 * warningData 异常数据检测
		 */
		private function warningData( $value ){
				return $value;
		}
		private function pushStorage($storage){
				$accessKey = $this->accessKey;
				if(empty($accessKey)|| empty( $storage ) )
						return false;
				$storageFile = $this->storageFile( $accessKey );
				if( empty( $storageFile) ){
						return false;
				}
				$str = json_encode( $storage );
				$fp  = @fopen($storageFile, 'w');
			    $rs  = @fwrite($fp, $str);
			           @fclose($fp);
			    return $rs;
		}
}