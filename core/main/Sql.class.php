<?php

/**
*   关系数据库查询接口
*
*/
namespace main;

class Sql{

		private $_obj = false;

		public function __construct( $mode=false ){
				$this->mode( $mode   );
		}
		/*
		 * mode 模式对象初始化
		 */
		public function mode( $mode=false ){
				if( empty($mode) ){
						$this->_obj = new \main\Sql\MySql\Model();
				}else{
						$mode  = str_replace('.','\\',$mode);
						$class ="\main\Sql\\".$mode;
						$this->_obj = new $class();
				}
				return $this;
		}
		/*
		 * init 对象配置初始化
		 */
		public function init( $config ){
				/*连接初始化*/
				$this->call('init',func_get_args());
                return $this->_obj;
        }
        /*
         * connect 连接对象
         */
        public function connect(){
        		$this->call('connect',func_get_args());
				return $this->_obj;
        }
        /*
         * start 准备就绪
         */
        public function start( $config ){
        		$this->init($config);
        		$this->connect(); 
        		return $this->_obj;
        }

        private function call( $method,$args=[] ){ 
        		if( is_object( $this->_obj ) && method_exists( $this->_obj,$method ) ){
        				return call_user_func_array( array( $this->_obj,$method ), $args );
        		}
        }


}