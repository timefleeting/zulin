<?php

/**
*   数据缓存
*
*/
namespace main;


class Cache{

		/**
		 * [$_obj 储存Cache模式对象]
		 * @var object
		 */
		private $_obj = false;

		public function __construct( $mode=false ){
				$this->init(  $mode   );
		}

		public function init( $mode=false ){
				if( empty($mode) ){
						$this->_obj = new \main\Cache\CacheFile();
				}else{
						$class ="\main\Cache\\".$mode;
						$this->_obj = new $class();
				}
                return $this;
        }

        private function call( $method,$args=[] ){ 
        		if( is_object( $this->_obj ) && method_exists( $this->_obj,$method ) ){
        				return call_user_func_array( array( $this->_obj,$method ), $args );
        		}
        }
        public function read($key,$timeout=7){ 
				$contents = $this->call('read',func_get_args());
				return $contents;
		}
		public function write($key,$data){
				return $this->call('write',func_get_args());
		}
		public function delete($key){
				return $this->call('delete',func_get_args());
		}
		public function clear($key,$mode=false){
				return $this->call('clear',func_get_args());
		}
		public function clearAll( $mode= false ){
				return $this->call('clearAll',func_get_args());
		}


}