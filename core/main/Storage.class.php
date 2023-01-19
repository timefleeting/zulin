<?php
namespace main;

/*
 * 数据仓储
 * 键值数据存储空间
 */

class Storage{
		private $_obj = false;

		public function __construct( $accessKey, $mode=false ){
				$this->init(  $accessKey,$mode   );
		}

		public function init( $accessKey,$mode=false ){
				if( empty($mode) ){
						$this->_obj = new \main\Storage\StorageFile($accessKey);
				}else{
						$class ="\main\Storage\\".$mode;
						$this->_obj = new $class($accessKey);
				}
                return $this;
        }
        private function call( $method,$args=[] ){ 
        		if( is_object( $this->_obj ) && method_exists( $this->_obj,$method ) ){
        				return call_user_func_array( array( $this->_obj,$method ), $args );
        		}
        }
        public function read(){ 
				$contents = $this->call('read',func_get_args());
				return $contents;
		}
		public function write($key,$data){
				return $this->call('write',func_get_args());
		}
		public function delete($key){
				return $this->call('delete',func_get_args());
		}
		public function clear(){
				return $this->call('clear',func_get_args());
		}
}