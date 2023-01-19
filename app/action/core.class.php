<?php 

namespace action;

abstract class core extends base{

		/*表别名需要作为关联类表*/
		protected $_aliasTable  = "";
		/*查询debug调试*/
		protected $_listDebug   = false;
		/*查询列表页码*/
		protected $_listPage    = false;
		/*查询列表每页显示数量*/
		protected $_listLimit   = false;
		/*查询列表关联字段*/
		protected $_listField   = "";
		protected $_listFieldCount="";
		/*查询列表关联表*/
		protected $_listJoin    = "";
		/*查询列表条件*/
		protected $_listWhere   = "";
		/*查询列表排序*/
		protected $_listOrder   = "";
		/*查询列表分组*/
		protected $_listGroup   = "";

		protected $_listHaving  = "";
		/*查询列表自动初始化查询*/
		protected $_listQuery   = false;

		/*添加数据*/
		protected $_addData 	= "";
		protected $_addDebug    = false;
		/*更新条件*/
		protected $_saveWhere   = "";
		/*更新数据*/
		protected $_saveData    = "";
		protected $_saveDebug   = false;

		/*查询字段*/
		protected $_findField   = "";
		/*查询关联*/
		protected $_findJoin    = "";
		/*查询优先记录*/
		protected $_findOrder   = "";
		/*查询附加关联条件,只支持数组条件,可覆盖默认条件*/
		protected $_findWhere = "";
		protected $_findDebug = "";


		public function __construct()
		{
				$this->right();
				$this->_init();
		}

		private function right(){
				$login = C('login','account');
				$cTrace = cTrace();
				if(empty($login) && empty( $cTrace ) ){
					    redirect(cUri('login','index'));
				}
				//active访问权限
				$activeTrace = cTrace('active');
				if( !empty( $activeTrace ) ){
					foreach( $activeTrace as $key => $val ){
						$activeKey 		= isset( $val[0] ) ? $val[0] : false;
						$activeValue 	= isset( $val[1] ) ? $val[1] : false;
						$apiRight 		= C('navigate','apiRight',$activeKey,$activeValue );
						if( !$apiRight ){
							die('no access!');
						}	
					}
				}
				//C调用权限
				if( !empty( $cTrace ) ){
					foreach( $cTrace as $key => $val ){
						$cKey 		= isset( $val[0] ) ? $val[0] : false;
						$cValue 	= isset( $val[1] ) ? $val[1] : false;
						$apiRight 	= C('navigate','apiRight',$cKey,$cValue );
						if( !$apiRight ){
							die('no access!');
						}	
					}
				}
		}

		/** 
		 * _init 子类初始化信息
		 * $this->_table 	    = $table;
		 * $this->_tableField 	= $this->tableField();
	     * $this->_tableIndex   = $this->tableIndex();
	     * $this->_tableUnique  = $this->tableUnique();
		 * @return [type] [description]
		 */
		abstract protected function _init();

		/******* 统一通用增改查 **********/
		/*
		 *   自动添加字段 uuid编号生成  ctime创建时间等
		 */
		protected function addAutoData(){
	    			$regxTable = $this->regxTable();
					if(empty($regxTable))
							return false;
					$data = array();
					foreach( $regxTable as $key => $val ){
							$autoKey = $val['auto']; 
							if( $autoKey==1 ){  //自动添加字段
									if( $key=='Uuid'){
											$data[$key] = 'UUID()';
									}elseif($key=='Ctime' || $key=='Mtime'){
											$data[$key] = time();
									}
							}
					}
					return $data;
		}
		/*
		 * 校验添加字段
		 */
		protected function addData( $addData ){
					if(empty( $addData )){
							return array('status'=>0,'msg'=>'add data empty!');
					}
					$modelRes 	= $this->tableModel($addData);
		    		if($modelRes['status']!=1){
	    					return $modelRes;
	    			}
	    			$data = $modelRes['data']; //标准验证已通过
		    		$autoData  = $this->addAutoData();
		    		$data = array_merge( $data, $autoData );
		    		return array('status'=>1,'data'=>$data);	
		}
		/*
		 *  添加数据,支持批量添加
		 */
		public function add(){
		    		$addData 	= !empty( $this->_addData ) ? $this->_addData : Q('data'); //注意:批量添加情况
		    		if( isset( $addData[0] ) && is_array( $addData[0] )){
		    			$data = array();
		    			$state = 1;  //默认正常
		    			$msg   = '';
		    			$msgcode=0;
		    			$cnt   = 0;
		    			foreach( $addData as $key => $val ){  //批量添加,校验不通过则忽略
		    					$dataRes = $this->addData( $val );
		    					if( $dataRes['status'] != 1 ){
		    						if( $state==1 ){
		    								$state=0;
		    								$msg  = $dataRes['msg']; //记录第一次出错的错误
		    								$msgcode = $cnt;  //第几个出错
		    						}
		    						//continue;
		    						break; //出错退出
		    					}
		    					$data[] = $dataRes['data'];
		    					$cnt++;
		    			}
		    			if( $state != 1){
		    					return array('status'=>0,'msg'=>$msg,'msgcode'=>$msgcode );
		    			}
		    		}else{  //正常添加
		    			$dataRes = $this->addData( $addData );
			    		if( $dataRes['status'] != 1 ){
			    				return $dataRes;
			    		}
			    		$data = $dataRes['data'];
		    		}
		    		$debug = !empty( $this->_addDebug ) ? $this->_addDebug : false;
		    		$res  = parent::baseAdd( $data,$debug );
		    		$this->_addData = '';
	   			return parent::statusMsg( $res );
		}
		/*
		 * 保存更新默认条件
		 */
		protected function saveWhere( $fields ){
					$modelRes = $this->tableModel($fields,true); 
					if($modelRes['status']!=1){
			    			return false;
			    	}
			    	$data = $modelRes['data'];
			    	$where = array();
			    	foreach( $data as $key => $val ){
			    			$where[$key] = $val;
			    	}
			    	return $where;
		}
		/*
		 *  unsetData 去除系统自动添加字段
		 */
		protected function unsetData( $data ){
					$regxTable = $this->regxTable();
					if(empty($regxTable))
							return false;
					foreach( $regxTable as $key => $val ){
							$autoKey = $val['auto'];   //去除自动添加字段
							if( $autoKey==1 ){
									unset( $data[$key] );
							}
					}
					return $data;
		}
		/*
		 *  saveData 保存系统自添加数据或自定义覆盖
		 */
		protected function saveAutoData(){
					$regxTable = $this->regxTable();
					if(empty($regxTable))
							return false;
					$data = array();
					foreach( $regxTable as $key => $val ){
							$autoKey = $val['auto']; 
							if( $autoKey==1 ){  //更新,自动添加字段
									if($key=='Mtime'){
											$data[$key] = time();
									}
							}
					}
					return $data;
		}
		/*
		 * 校验:where字段,data数组字段
		 */
		protected function saveData( $where,$data ){
					$modelRes 	= $this->tableModel($data,true);
		    		if($modelRes['status']!=1){
	    				return $modelRes;
	    			}
	    			$saveWhere = $this->saveWhere( $where );
	    			$saveData  = $this->unsetData( $modelRes['data'] ); //剔除系统自生成字段
		    		if(empty( $saveWhere)){
		    				return array('status'=>0,'msg'=>'更新条件不能为空!');
		    		}
		    		$autoData = $this->saveAutoData();				//加入需要系统自生成字段
		    		$saveData = array_merge($saveData,$autoData);
		    		return array('status'=>1,'msg'=>'完成','data'=>array('where'=>$saveWhere,'data'=>$saveData));
		}
		/*
		 *  save 默认通用保存数据信息
		 *  @parent $where 更新条件
		 *  @parent $data  要更新的数据
		 */
		public function save(){
					$where 	    = !empty($this->_saveWhere) ? $this->_saveWhere : Q('where');
					$data  		= !empty($this->_saveData)  ? $this->_saveData  : Q('data');
		    		$debug      = !empty($this->_saveDebug)  ? $this->_saveDebug : false;
		    		$saveDataRes= $this->saveData( $where, $data );
		    		if( $saveDataRes['status'] != 1 ){
		    				return $saveDataRes;
		    		}
		    		$saveData  = $saveDataRes['data']['data'];
		    		$saveWhere = $saveDataRes['data']['where'];
	    			$res = parent::baseSave($saveData,$saveWhere,$debug);
	    			$res = parent::statusMsg($res);
	    			$res['msg'] = $res['data']==0 ? '无数据更新' : $res['msg'];
	    			$this->_saveWhere = '';
	    			$this->_saveData  = '';
				return $res;
		}
		/*
		 *	根据字段别名获取数据库字段名	
		 */
		protected function regxKeyField( $keyfield ){
				if(empty( $keyfield )) return false;
				$regxTable = $this->regxTable();
				foreach( $regxTable as $key => $val ){
						if( $val['name'] == $keyfield ){
								return $key;
						}
				}
				return false;
		}
		/*
		 *  初始化自定义列表查询条件
		 *  暂不支持关联查询的接口,关联查询需子类重新组装定义
		 */
		protected function listQuery(){
				if( !empty($this->_aliasTable ) )
						return false;
				$init     = Q('init');  //初始化的查询条件
				$query    = Q('query'); 
        		$keyfield = isset( $query['keyfield'] )? trim($query['keyfield']) : false;
			    $keyword  = isset( $query['keyword'] ) ? trim($query['keyword']) : false;
				$where    = array();   
			    $queryKey = $this->regxKeyField( $keyfield );
			    if( !empty( $queryKey ) && !empty( $keyword )){
			    		$where[$queryKey] = array('like',$keyword.'%'); //前面不能为%才会用到索引
			    		$this->_listWhere = $where;
			    }
		}
		/*
		 *  @param $page   页数
		 *  @param $limit  每页显示数量
		 *  @param $debug  打印调试信息
		 *  @param $custom 自定义条件
		 **
		 ** listMaxLimit  一次请求最大条数
		 ** paginglimit   分页数量
		 ** listField     列表字段查询
		 ** listJoin      关联的表
		 ** listWhere     列表条件
		 ** listOrder     列表排序
		 ** listGroup	  分组条件
		 ** listTable     查询表,无定义默认类表
		 *  
		 */
		public function lists(){
					if( !empty( $this->_listQuery )){
							$this->listQuery();
					}
					$page  = !empty( $this->_listPage )  ? $this->_listPage  : Q('page');
					$limit = !empty( $this->_listLimit ) ? $this->_listLimit : Q('limit');
					$debug = !empty( $this->_listDebug)  ? $this->_listDebug : false;
					$page  = !empty( $page )  ? $page  : 1;
					$maxLimit = isset( $this->listMaxLimit ) ? $this->listMaxLimit : 100;
					$limit =  $limit > $maxLimit ? $maxLimit : ( $limit ? $limit : $this->paginglimit );
					$field = !empty($this->_listField) ? $this->_listField : '';
					$fieldCount = !empty($this->_listFieldCount) ? $this->_listFieldCount : '';
					$join  = !empty($this->_listJoin)  ? $this->_listJoin : '';
					$where = !empty($this->_listWhere) ? $this->_listWhere : '';
					$order = !empty($this->_listOrder) ? $this->_listOrder : '';
					$group = !empty($this->_listGroup) ? $this->_listGroup : '';
					$having= !empty($this->_listHaving) ? $this->_listHaving : '';
					$option['field'] = $field;
					$option['fieldCount'] = $fieldCount;
					$option['join']  = $join;
					$option['where'] = $where; //where 需要根据实际情况拼装(考虑join)
					$option['order'] = $order;
					$option['group'] = $group;
					$option['having']= $having; 
					$res 		= parent::baseLists($option,$page,$limit,$debug);
					$regxTable  = $this->regxTable();
					if(isset( $res['data']['lists'] ) && !empty( $res['data']['lists'] )){
							foreach( $res['data']['lists'] as $key => &$val ){
									$val = $this->tableItem( $val,$regxTable );
							}
					}
	    			return isset( $res['data'] ) ? $res['data'] : false;
		}
		/*
		 * 数据库字段别名转换
		 * 也可以用此方法给字段加权限
		 * 注意,多表关联查询的情况
		 */
		private function tableItem( $item,$regxTable ){
				if(empty( $item ) || empty( $regxTable ) ){
						return false;
				}
				$data = array();
				foreach( $item as $key => $val ){
						$aliasKey = isset( $regxTable[$key]['name'] ) ? $regxTable[$key]['name'] : $key;
						$data[$aliasKey] = $val;
				}
				unset($item);
				return $data;
		}


		/*
		 * 查询信息条件转换
		 */
		protected function findWhere( $isWhere ){
				if(!is_array($isWhere)){ //不是数组,需要拼装出一个有效条件
						$option = array('uuid' => $isWhere );
				}else{
						$option = $isWhere;
				}
				$modelRes = $this->tableModel($option,true);
				if($modelRes['status']!=1){
		    			return false;
		    	}
		    	$data = $modelRes['data'];
		    	$where = array();
		    	$aliasTable = !empty($this->_aliasTable) ? $this->_aliasTable : ''; //表别名
		    	foreach( $data as $key => $val ){
		    			$key = !empty($aliasTable) ? $aliasTable.'.'.$key: $key;
		    			$where[$key] = $val;
		    	}
		    	return $where;
		}
		/*
		 * 查询信息
		 * 注意关联查询条件
		 */
		public function find(){
				$where = $this->findWhere( Q(true) );
				if(empty( $where ) ){
						return false;
				}
				$joinWhere = !empty( $this->_findJoin ) ? $this->_findJoin : '';
				if( is_array( $joinWhere ) ){ //只支持数组
						$where = array_merge( $where, $joinWhere );
				}
				$debug = !empty( $this->_findDebug) ? $this->_findDebug : false;
				$res = parent::baseFind($where,$debug);
				$regxTable = $this->regxTable(); 
				$data =  isset( $res['data'] ) ? $res['data'] : false;
				$resData = 	!empty( $data ) ? $this->tableItem( $data,$regxTable ) : false;
				return !empty( $resData ) ? array('status'=>1,'data'=>$resData) : array('status'=>0);
		}




}