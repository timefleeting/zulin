<?php

namespace main\Sql;


class MySql implements Sql{

		protected $dbhost   = false;
        protected $dbuser   = false;
        protected $dbpwd    = false;
        protected $dbname   = false;
        protected $port     = '3306';
        protected $charset  = 'utf8';
        protected $conn = null;
        public static $errors = array();

        public function __construct( $config=false ){ 
        			$this->init($config);
        }

        public function init($config){
        		$this->dbhost 	= isset( $config['host'] ) ? $config['host'] : false;
    			$this->dbuser 	= isset( $config['user'] ) ? $config['user'] : false;
    			$this->dbpwd 	= isset( $config['pwd'] )  ? $config['pwd'] : false;
    			$this->dbname 	= isset( $config['name'] ) ? $config['name'] : false;
    			$port 			= isset( $config['port'] ) ? $config['port'] : false;
    			if( !empty($port )){
    					$this->port = $port;
    			}
    			$charset  = isset( $config['charset'] ) ? $config['charset'] : false;
    			if( !empty( $charset )){
    					$this->charset = $charset;
    			}
    			return $this;
        }

		 //   数据库初始化连接
        public function connect(){
                    $dbhost 	= 	$this->dbhost.':'.$this->port;
                    $dbuser 	= 	$this->dbuser;
                    $dbpwd 	    = 	$this->dbpwd;
                    $dbname 	= 	$this->dbname;
                    $charset 	= 	$this->charset; 
                    if($this->conn){  //已存在连接
                    			return $this;
                    }
                    try{	
                            if(!function_exists('mysql_connect')){
                                    throw new Exception("no mysql.");
                            }
                            if(!$dbhost||!$dbuser||!$dbpwd){
                                    throw new \Exception('host,user,pass not empty!');
                            }elseif( !$this->conn= @mysql_connect($dbhost,$dbuser,$dbpwd) ){
                                    throw new \Exception('db not connect.');
                            }
                            if( !$dbname || !@mysql_select_db($dbname,$this->conn) ){ 
		                            throw new \Exception('selectdb select db error.');
		                    }
		                    if(!empty($charset)){
		                                    $charset  = strtolower(str_replace("-","",$charset));
		                                    $charsets  = array();
		                                    $result    = @mysql_query("SHOW CHARACTER SET");
		                                    while($row = mysql_fetch_array($result,MYSQL_ASSOC)){
		                                            $charsets[] = $row["Charset"];
		                                    }
		                                    if(in_array($charset,$charsets)){
		                                        @mysql_query("SET NAMES '".$charset."'");                      
		                                    }
		                    }
                    }catch(Exception $e){
                                    echo  $e->getMessage()." ";die;
                    }
                    return $this;
            }

            //  原来mysql语句执行
        	public function query($query){
        	 			 	$query = trim($query);
        	 			 	$res = @mysql_query($query,$this->conn);
            				if ( $err_msg = @mysql_error($this->conn) ){ 
            						self::$errors[] = $err_msg;
		                           	$res = $this->reset_query($err_msg,$query);   
		                    }
		                    if(empty($res ))
		                    		return false;
		                     if ( preg_match("/^(insert|delete|update|replace|truncate|drop|create|alter|set|lock|unlock)\s+/i",$query) ){
				                            $rows_affected = @mysql_affected_rows( $this->conn );
				                            if ( preg_match("/^(insert|replace)\s+/i",$query) )
				                            {
				                                $insert_id = @mysql_insert_id( $this->conn );
				                                return $insert_id;
				                            }
                                             if ( preg_match("/^(update)\s+/i",$query) )
                                             {
                                                 if( $rows_affected <1 ){
                                                     $rows_affected = $rows_affected;
                                                 }
                                             }
				                            if ( preg_match("/^(create|alter|drop|delete)\s+/i",$query)&& empty( $rows_affected ))
				                                    $rows_affected = $res;
				                            /*错误信息记录*/
				                            if ( $err_msg = @mysql_error($this->conn) ){ 
				            						self::$errors[] = $err_msg; 
						                    }
				                            return $rows_affected;        
		                    }else{
				                            $result = array();
				                            while ( $row = @mysql_fetch_array($res,MYSQL_ASSOC) )
				                            {
				                                $result[] = $row;
				                            }
				                            if ( $err_msg = @mysql_error($this->conn) ){ 
				            						self::$errors[] = $err_msg; 
						                    }
				                            @mysql_free_result($res);
				                            return $result;
		                    }    
        	 }

        	 public function commitStart(){
        			if(! isset($this->conn) || ! $this->conn)
                    {
                        $this->connect();
                    }
                    	mysql_query("BEGIN");//开始一个事务  
						mysql_query("SET AUTOCOMMIT=0"); //设置事务不自动commit
        	}

        	public function commitEnd( $state=true ){
        			if($state==true){
        					mysql_query("COMMIT");
        			}else{
        					mysql_query("ROLLBACK");
        			}
        			mysql_query("SET AUTOCOMMIT=1");//恢复autocommit模式  
        	}

        	// 连接丢失错误，重置查询
			private function reset_query($err_msg,$query){
					if(stripos($err_msg, 'MySQL server has gone away')!==false){
							$this->conn = null;
							$this->connect();
							$res = @mysql_query($query,$this->conn);
							return $res;
					}else{
							return false;
					}
			}
			/* 关闭连接
			 * 通常不需要使用 mysql_close()，因为已打开的非持久连接会在脚本执行完毕后自动关闭。
			 * mysql_close() 不会关闭由 mysql_pconnect() 建立的持久连接。
			*/
			public function close_connect(){
                    @mysql_close($this->conn);
                    $this->conn 	= null;
					self::$errors   = array();
			}
			public function escape($str)
            {
                    if(! isset($this->conn) || ! $this->conn)
                    {
                        $this->connect();
                    }
                    if (get_magic_quotes_gpc()) 
					{
						 $str = stripslashes($str);
					}
						 $str = mysql_real_escape_string($str);
                    return $str;
            }
            public function errors(){
            		return self::$errors;
            }


}