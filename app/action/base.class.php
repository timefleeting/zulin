<?php
namespace action;

abstract class base{
		private   $loginKey     = 'loginKey';
		protected $paginglimit  = 20;  //每页数量

		protected $account 		= "lease.account";  			//平台管理员帐号
		protected $warehouse    = "lease.warehouse"; 
		protected $wb           = "lease.warehouse_belong";      //关联仓库          
		protected $classify     = "lease.classify";   			//类目
		protected $device  		= "lease.device";     			//设备信息
		protected $df           = "lease.device_tranfer";		//设备流转记录
		protected $order        = "lease.order";      			//订单信息
		protected $op           = "lease.order_product";        //订单产品时间占用
		protected $oa           = "lease.order_address";        //订单地址
		protected $ol 			= "lease.order_log";			//订单日志

    	protected $_table;
    	protected $_tableField;
    	protected $_tableIndex;
    	protected $_tableUnique;

    	protected $supperMobile = "18123456789";

    	protected function isSupperAccount(){
		    		$account = C('login','account');
		    		if(empty( $account )) return false;
		    		$mobile = isset( $account['mobile'] ) ? $account['mobile'] : false;
		    		if( intval( $mobile ) === intval( $this->supperMobile ) ){
		    				return true;
		    		}
		    		return false;
		}
		/*帐号权限*/
		protected function accountRight(){
	    		$loginInfo = C('login','account');
				$uuid 	   = isset( $loginInfo['uuid'] ) ? $loginInfo['uuid'] : '';
				return $this->getAccountRight( $uuid );
	    }
	    protected function accountRightKey( $uuid ){
	    		if(empty( $uuid )) return false;
	    		return md5('account-'.$uuid);
	    }
	    protected function getAccountRight( $uuid ){
	    		$storageKey = $this->accountRightKey( $uuid );
	    		return storage( $storageKey,'right');
	    }
	    protected function setAccountRight( $uuid,$rightStr ){
	    		$storageKey = $this->accountRightKey( $uuid );
	    		return storage( $storageKey,'right',$rightStr);
	    }

		/**
	     * [createTable 创建数据模型表]
	     * @param string $comment   [表备注说明]
	     * @param string $engine    [数据表使用引挚]
	     * @param string $charset   [字符集]
	     * @return array
	     */
	    protected function createTable()
	    {
	        $argv    = func_get_args();
	        $comment = isset( $argv[0] ) ? $argv[0] : '';
	        $engine  = isset( $argv[1] ) ? $argv[1] : 'InnoDB';
	        $charset = isset( $argv[2] ) ? $argv[2] : 'utf8';

	        $option['comment'] = $comment;
	        $option['engine']  = $engine;
	        $option['charset'] = $charset;
	        $db  = db();
	        $res =  $db->createTable($this->_table, $this->_tableField, $option);
	        if ($res['status'] == '1') {
	            $indexField = $this->_tableIndex;
	            if (!empty($indexField)) {
	                foreach ($indexField as $i_key => $i_val) {
	                    $rs = $db->setTableIndex($this->_table, $i_key, $i_val);
	                }
	            }
	            $uniqueField = $this->_tableUnique;
	            if (!empty($uniqueField)) {
	                foreach ($uniqueField as $u_key => $u_val) {
	                    $rs = $db->setTableUnique($this->_table, $u_key, $u_val);
	                }
	            }
	        }
	        return $res;
	    }


	    /*
	     *  模型字段标准格式
	     */
	    protected function regxModel(){
	    		$field = array(
	    				'name'		=> 'id',
           				'title'		=> '编号',
           				'auto'		=> 1,   //是否为系统自建值
           				'require'	=> '', 	//是否为必要值 当auto为不为1时,必填项
           				'notNull'	=> '',  //是否为空 0:可为空.否则不可为空
           				'regex' 	=> '', 	//验证
           				'regexMsg'  => ''   //验证提示
	    		);
	    		return $field;
	    }
	    /*
	     *  regxField 建立标准模型字段校验规则 2,3,2
	     */
	    protected function regxField($name,$title,$auto,$require,$notNull,$regex,$regexMsg){
	    		 $regxModel = $this->regxModel();
	    		 foreach( $regxModel as $key => &$val ){ 
	    		 		   $val = $$key;
	    		 }
	    		 return $regxModel;
	    }

	    protected function regxTable(){}

	    /*
	     * tableModel 数据模型验证
	     * @param $info  校验信息
	     * @param $miss  校验值为空是否忽略 true:是 false:否
	     * @return $data  校验有效模型数据
	     */
	    public function tableModel( $info=false,$miss=false ){ 
	    		$regxTable  = $this->regxTable();
	    		return $this->modelField($regxTable,$info,$miss);
	    }
	    /*
	     * modelField 根据校验规则表校验信息数据
	     * @param $regxTable 校验信息表 array($key=>$regxModel)
	     * @param $info 待校验信息数据
	     * @param $miss  true可忽略关键验证
	     * @return $data 匹配验证成功的数据
	     */
	    protected function modelField($regxTable=false,$info=false,$miss=false){
	    		$res 		= array('status' => 0, 'data'=>false,'msg' =>false);
	    		if(empty($regxTable)){
	    				$res['msg'] = '校验规则不能为空';
	    				return $res;
	    		}
	    		foreach( $regxTable as $key => $val ){
	    				$name = isset($val['name'])?$val['name']:false;
	    				$auto = isset($val['auto'])?$val['auto']:0;
	    				if(empty( $name ) || (empty($miss)&&$auto == 1) ) continue;
	    				$title    = $val['title'];
	    				$require  = $val['require'];
	    				$notNull  = $val['notNull'];
	    				$regex    = $val['regex'];
	    				$regexMsg = $val['regexMsg'];
		               // $value 	= isset( $info[$name] ) ? $info[$name] : Q($name); //Q小心被请求参数污染   
		                $value 	= isset( $info[$name] ) ? $info[$name] : false; 
		                if(is_numeric( $value )){
		                		if( !empty($require) && $value!==0 && empty($value) ){
			                		$res['msg'] = $title.' 必须';
			                		$res['data'] = $name;
			                		return $res;
			                	}elseif(!empty( $notNull ) && $value!==0 && empty( $value ) ){
			                		$res['msg'] = $title.' 不能为空';
			                		$res['data'] = $name;
			                		return $res;	
			                	}
			                	if( !empty($regex) && !preg_match($regex,$value) ){
			                		$res['msg'] = $regexMsg;
			                		$res['data'] = $name;
			                		return $res;
		                		}
		                }else{
							if( empty($value) ){
			                		if(!empty($miss)){
			                				continue;
			                		}
				                	if( !empty($require) ){
				                		$res['msg'] = $title.' 必须';
				                		$res['data'] = $name;
				                		return $res;
				                	}elseif(!empty( $notNull ) ){
				                		$res['msg'] = $title.' 不能为空';
				                		$res['data'] = $name;
				                		return $res;	
				                	}
			                }else{ //value 可能为数组 array('in|eq|net',$string);
			                	if(is_array( $value )){
			                		$value_key = isset( $value[0] ) ? $value[0] : false;
			                		$value_val = isset( $value[1] ) ? $value[1] : false;
			                		if( !empty($regex) ){
			                			if( $value_key=='in' ){
		                					$value_arr = explode(',',$value_val );
		                					foreach( $value_arr as $v_key => $v_val ){
		                						$v_val = trim($v_val,'"');
		                						$v_val = trim($v_val,"'");
		                						$v_val = trim($v_val,'`');
	                							if(!preg_match( $regex,$v_val )){
	                								$res['msg'] = $regexMsg;
		                							$res['data'] = $name;
		                							return $res;
	                							}
		                					}
			                			}else{
			                					if(!preg_match( $regex,$value_val )){
	                								$res['msg'] = $regexMsg;
		                							$res['data'] = $name;
		                							return $res;
	                							}
			                			}
			                		}

			                	}else if( !empty($regex) && !preg_match($regex,$value) ){
			                		$res['msg'] = $regexMsg;
			                		$res['data'] = $name;
			                		return $res;
			                	}
			                }
		                }
		                $data[$key] = $value;
	    		}
	    		$res['status'] = !empty($data)?1:0;
	    		$res['data']   = $data;
	    		return $res;
	    }

	    protected function returnRes()
		{
	        $res = array('status' => 0, 'data'=>false,'msg' =>false);
	        return $res;
		}

	    /**
	     * @param  $data  添加的数据
	     * @param  $debug 调试
	     * @param  $aliasTable 别名(默认为当前对象的_table )
	     * @return bool|mixed|void
	     */
	    protected function baseAdd( $data,$debug=false )
	    {	
	    		//$aliasTable = isset($this->_aliasTable) ? $this->_aliasTable : '';
				$table = $this->_table;
	   			return db($table)->add( $data,$debug );
	    }

	    /*
	     * [save 改->保存|更新数据]
	     * @param  $data  保存数据
	     * @param  $where 限制条件
	     * @param  $debug 调试
	     * @param  $aliasTable 别名(默认为当前对象的_table )
	     */
	    protected function baseSave($data,$where,$debug=false)
	    {
	    		//$aliasTable = isset($this->_aliasTable) ? $this->_aliasTable : '';
				$table = $this->_table;
	   			return db($table)->save( $data,$where,$debug );
	    }

	    /**
	     * [find 查->查询单条数据]
	     * @param  $where 查询条件
	     * @param  $order 排序优先
	     * @param  $debug 调试
	     * @param  $aliasTable 别名(默认为当前对象的_table )
	     * @return array  $res
	     */
	    protected function baseFind( $where,$debug=false ){
	        	$option['where'] = $where;  //主
	        	$field      = !empty( $this->_findField )   ? $this->_findField : '';
	        	$join  		= !empty( $this->_findJoin )    ? $this->_findJoin : '';
				$order 		= !empty( $this->_findOrder )   ? $this->_findOrder : '';
				/*次,且可固定*/
		        $option['field'] = $field;
		        $option['join']  = $join;
		        $option['order'] = $order;
		        $listRes = $this->baseLists( $option,1,1,$debug );
		        $list    = isset( $listRes['data']['lists'] ) ?   $listRes['data']['lists'] : false;
		        $data    = isset( $list[0] ) ? $list[0] : false;
		        $listRes['data'] = $data;
		        return $listRes;
	    }
	    /**
	     * [lists 查->查询数据列表]
	     * @param  $table	表名
	     * @param  $page	页数
	     * @param  $limit	每页数量
	     * @param  $option	查询条件 array('join','where','order','group');
	     * @param  $debug	调试
	     * @return array    $res['data']->lists
	     */
	    protected function baseLists($option,$page=1,$limit=false,$debug=false)
	    {
	   			$aliasTable = isset($this->_aliasTable) ? $this->_aliasTable : '';
				$table 		= !empty($aliasTable) ? $this->_table.' '.$aliasTable : $this->_table;
		        $page   	= !empty($page)  ? $page : 1;
		        $limit  	= !empty($limit) ? $limit : $this->paginglimit;
		        return db($table)->lists( $page,$limit,$option,$debug );
	    }

	    /**
	     * [statusMsg 根据状态信息，返回具体状态信息]
	     * @param $res  [操作数据库返回的状态码信息]
	     * @return mixed
	     */
	    protected function statusMsg( $res ){
	        $modelField = $this->modelField();
	        if( empty( $modelField ) || empty( $res ) )
	                return $res;
	        $res['msg'] = db()->statusMsg( $res['status'] );
	        $field 		= !empty( $res['field'] ) ? $modelField[$res['field']]['title'] : '';
	        $res['msg'] = $field.$res['msg'];
	        return $res;
	    }

	    /*帐号密码加密*/
	    protected function passwdEncrypt($code)
	    {
	        if (empty($code)) return md5(time());
	        $code = (string)$code;
	        $encode = M('Service','encrypt',$code);
	        return md5(md5($encode));
	    }

}