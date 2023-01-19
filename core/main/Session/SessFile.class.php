<?php

namespace main\Session;

/**
 *  session 文件存储
 */
class SessFile implements Sess{

		private $_sessPath = 'sess/';
		private $_path;
		private $_name;
		private $_pre	    = "sess_";   //sess文件前缀
		private $_clear     = "sess.txt"; 
		private $_cleartime = 36000;     //定时清理时间
		private $_lifetime  = 0;         //sessId 生存期 0,关闭生存期结束


        private function __clone(){
            	trigger_error('Clone is not allow!',E_USER_ERROR);
        }

		public function __construct( ){
                $upload = MAININFO;  //默认为当前脚本下的目录
                $this->_sessPath = $upload.DIRECTORY_SEPARATOR.'SessFile'.DIRECTORY_SEPARATOR;
		}
		
		//获取存储路径
		function sessPath( $mode = 0777 ){
				//chdir( $this->_syscwd );  //session_set_save_handler -> write，多次调用将影响工作目录
 				$path = $this->_sessPath;
 				$path = $path.DIRECTORY_SEPARATOR.config('workspace');
 				$path = str_replace(array('\\\\\\','\\\\','\\','///','//','/'),DIRECTORY_SEPARATOR,$path );
				if( !empty($path ) && !is_dir( $path ) ){ 
						$mkdir = mkdir( $path,$mode,true );
						if( !$mkdir ){
								die("session path can not create!");
								return false;
						}	
				}
				return $path;
		}

		//存储路径与文件
		function pathFile( $sessId,$mode=0777 ){
				$path = $this->sessPath();
				if( !$path ){
						return false;
				}
				//$levelDir = $sessId[0].$sessId[1].DIRECTORY_SEPARATOR.$sessId[2].$sessId[3]; 有定时清理不需要多级存放
				$levelDir = '';
				$sessPath = $path.DIRECTORY_SEPARATOR.$levelDir;
    			$sessPath = str_replace(array('\\\\\\','\\\\','\\','///','//','/'),DIRECTORY_SEPARATOR,$sessPath );
    			if( !is_dir( $sessPath ) ){
    				    $mkdir  = mkdir( $sessPath,$mode,true );
		            	if( !$mkdir ){
		            			die("session pathFile can not create!");
									return false;
						}	
            	}
				$fileName = $this->_pre.$sessId;
				$pathFile = $sessPath.DIRECTORY_SEPARATOR.$fileName;
				return $pathFile;
		}

		function open( $path,$name ){
				$this->_path = $path;
				$this->_name = $name;
		}

		function read($sessId){
				$pathFile = $this->pathFile( $sessId );
				if( !$pathFile ){
						return false;
				} //return false;
				$contents =  (string)@file_get_contents( $pathFile );
				  //var_dump($contents ); 
				return $contents;	
		}
		function write($sessId,$data){ 
				$pathFile = $this->pathFile( $sessId );
				if( !$pathFile ){
						return false;
				} 
				//return false;
				return file_put_contents( $pathFile, $data ) === false ? false : true;
		}
		/**
		 *  [close 关闭并处理过期文件]
		 *  
		 */
		function close(){
				$sessPath = $this->sessPath();
				$sessFile = $sessPath.DIRECTORY_SEPARATOR.$this->_clear;	//执行定时标识
				$sessFile = str_replace(array('\\\\\\','\\\\','\\','///','//','/'),DIRECTORY_SEPARATOR,$sessFile );
				//$ax = array('a'=>$sessFile,'b'=>filemtime( $sessFile ),'c'=>$this->_cleartime,'d'=>date('Y-m-d H:i:s',filemtime( $sessFile ) ));
				//file_put_contents('/alidata/log/sessfile12312313131313.text',var_export($ax,true),FILE_APPEND);
				clearstatcache();
				if( !file_exists( $sessFile ) || ( filemtime( $sessFile ) + $this->_cleartime < time() ) ){
						file_put_contents( $sessFile, time());
						$this->gc( $this->_lifetime );
				}
		}
		
		function destroy( $sessId ){

		}
		function gc( $maxlifetime ){
				$sessPath = $this->sessPath();
				if( !$sessPath ){
						return false;
				}
				$globMode = $this->_pre.'*'; 
				$globModeFile = $sessPath.DIRECTORY_SEPARATOR.$globMode;
				$globModeFile = str_replace(array('\\\\\\','\\\\','\\','///','//','/'),DIRECTORY_SEPARATOR,$globModeFile );
				foreach (glob( $globModeFile ) as $file) { 
            			if ( (filemtime($file) + $maxlifetime) < time() && file_exists($file)) {
                				unlink($file);
            			}
        		}
		}

}