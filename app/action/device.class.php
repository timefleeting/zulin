<?php

namespace action;

class device extends core{

		protected function _init(){
		    		$this->_table 	    = $this->device;
		        	$this->_tableField 	= $this->tableField();
	        		$this->_tableIndex  = $this->tableIndex();
	        		$this->_tableUnique = $this->tableUnique();
	        		$this->_listQuery   = true;
	        		$this->_listOrder   = "t1.Sort asc,t1.Id desc";
	        		$this->_aliasTable  = 't1';
	        		$this->_listField   = 't1.*,t2.Name as classifyName,t3.Name as warehouseName,t4.Name as targetWhName';
	        		$join[] = "left join {$this->classify} t2 on t1.Classify=t2.Uuid";
	        		$join[] = "left join {$this->warehouse} t3 on t1.warehouse=t3.Uuid";
	        		$join[] = "left join {$this->warehouse} t4 on t1.targetWh=t4.Uuid";
	        		$this->_listJoin = $join;
	        		//$this->_listDebug = true;
		}
		public function statusList(){
				$list = array(
					'1'	=>	'正常',
					'2' =>  '占用',
					'3'	=>	'故障维修',
					'0'	=>	'报废',
				);
				return $list;	
		}
		/*
		 * 设备状态 1正常 2占用 3故障维修
		 */
		public function deviceStatus( $key=false ){
				$list = $this->statusList();
				return isset( $list[$key] ) ? $list[$key] : '--';
		}

		public function find(){
				$uuid = Q('uuid');
				if( empty( $uuid )) return false;
				$where['t1.Uuid'] = $uuid; 
				$this->_listWhere = $where;
				$this->_listPage = 1;
				$this->_listLimit = 1;
				$res = parent::lists();
				$data = isset( $res['lists'][0] ) ? $res['lists'][0] : false;
				if( !empty( $data )){
						return array('status'=>1,'data'=>$data);
				}else{
						return array('status'=>0,'msg'=>'数据异常');
				}
		}
		public function findById( $id ){
				if(empty( $id )) return false;
				$where['t1.Id'] = $id;
				$this->_listWhere  = $where;
				$this->_listPage   = 1;
				$this->_listLimit  = 1;
				$res = parent::lists();
				$data = isset( $res['lists'][0] ) ? $res['lists'][0] : false;
				return $data;
		}
		/*
		 * 获取主仓类型设备库存
		 */
		public function warehouseNum($warehouse,$classify){
					$where['t1.Warehouse'] = $warehouse;
					$where['t1.Classify']  = $classify;
				    $where['t1.Status']    = 1;
				 	$this->_listField = "count(t1.Id) num,t2.Name,t2.Uuid";
					$this->_listJoin  = "right join {$this->warehouse} t2 on t2.Uuid=t1.Warehouse and t2.Status=1"; //
					$this->_listWhere = $where;
					$this->_listGroup = "t1.Warehouse";
					$this->_listDebug = true;
					$this->_listOrder = false;
					$res = parent::lists();
					$lists = isset( $res['lists'][0] ) ? $res['lists'][0] : false;
					return $lists;
		}
		/*
		 *  获取关联副仓类型设备总库存数量
		 *  @param $classify 设备类型
		 *  @param $warehouse 主仓
		 */
		public function belongWarehouseNum($warehouse,$classify){
					$field 	 = "count(t1.Id) num,t3.Name,t3.Uuid";
					$join[]  = "inner join {$this->wb} t2 on t1.Warehouse=t2.Buuid and t2.Uuid='{$warehouse}'"; //关联可调仓库
					$join[]  = "inner join {$this->warehouse} t3 on t3.Uuid=t2.Buuid and t3.Status=1"; //有效仓库
					$where['t1.Classify'] = $classify;
					$where['t1.Status']   = 1;
					$this->_listField = $field;
					$this->_listJoin  = $join;
					$this->_listWhere = $where;
					$this->_listGroup = "t1.Warehouse";
					$this->_listOrder = false;
					//$this->_listDebug = true;
					$res = parent::lists();
					$lists = isset( $res['lists'] ) ? $res['lists'] : false;
					return $lists;
		}
		/*
		 * $uuids 数组
		 */
		public function listsByUuids( $uuids ){
					$where['Uuid'] = array('in',$uuids);
					$this->_listWhere = $where;
					$this->_aliasTable= false;
					$this->_listField = false;
					$this->_listJoin  = false;
					$this->_listGroup = false;
					$this->_listOrder = false;
					//$this->_listDebug = true;
					$res = parent::lists();
					$lists = isset( $res['lists'] ) ? $res['lists'] : false;
					return $lists;
		}
		/*
		 * 更新设备状态
		 */
		public function statusByUuids( $uuids,$status ){
					if(empty( $uuids )) return false;
					$where['uuid']  = array('in',$uuids);
					$data['status']   = $status;
					$this->_saveData  = $data;
					$this->_saveWhere = $where;
					//$this->_saveDebug =true;
					return parent::save();
		}

		/*
		 * 根据订单编号批量更新关联的设备状态
		 * 超期未归还的订单,需要将设备状态更新为2占用状态
		 * @param $orderSn 订单编号
		 * @param $status 设备状态  2占用 目前只需要这个状态
		 * 
		 */
		public function deviceStatusByOrder( $orderSn, $status=2 ){
				if(empty( $orderSn ) || !is_array($orderSn ))
						return false;	
				$orderIds = implode("','",$orderSn);
				$orderIds = "'".$orderIds."'";
				$sql = "update {$this->device} set Status={$status} where Status=1 and Uuid in (select Device from {$this->op} where OrderSn in ({$orderIds}))";
				$rs = db($this->device)->query( $sql );
				return $rs;
		}


		protected function listQuery(){
					$query = Q('query');
					$keyfield = !empty( $query['keyfield'] ) ? trim($query['keyfield']) : '';
					$keyword  = !empty( $query['keyword']  ) ? trim($query['keyword']) : '';
					if(empty( $keyword)) return false;
					$where = array();
					switch( $keyfield ){
							case 'warehouseName':
								$where['t3.Name'] = array('like',$keyword.'%');
							break;
							case 'classifyName':
								$where['t2.Name'] = array('like',$keyword.'%');
							break;
							case 'name':
								$where['t1.Name'] = array('like',$keyword.'%');
							break;
							case 'barcode':
								$where['t1.Barcode'] = array('like',$keyword.'%');
							break;
					}
					$this->_listWhere = $where;
		}

		private function reqData(){
					$account = C('login','account');
					$accountUuid = isset( $account['uuid'] ) ? $account['uuid'] : '';
					if(empty( $accountUuid )){
							echo json_encode( array('status'=>0,'msg'=>'登录帐号异常'));die;
					}
					$data = Q('data');
					if(!empty( $data['price'] ) ){
							if( !is_numeric( $data['price'] )){
								echo json_encode( array('status'=>0,'msg'=>'价格必须为数字') );die;
							} 
							$data['price'] = intval($data['price']*100);
					}else{
							$data['price'] = 0;
					}
					if( !empty( $data['buyerTime'] ) ){
							$data['buyerTime'] = strtotime( $data['buyerTime'] );
					}else{
							$data['buyerTime'] = 0;
					}
					$data['name']    = trim( $data['name'] );
					$data['barcode'] = trim( $data['barcode'] );
					$data['accountUuid'] = $accountUuid;
					$data['sort']       = !empty( $data['sort'] ) ? $data['sort'] : 0;
					return $data;
		}

		public function add(){
					$data = $this->reqData();
					$data['targetWh'] = $data['warehouse'];
					$data['targetWhStatus'] = 1;
					$this->_addData = $data;
					//$this->_addDebug = true;
					$res = parent::add();
					if( $res['status'] == -3 ){
						 $res['msg'] = "已存在相同记录,请检查设备编号是否唯一";
					}
					return $res;
		}
		public function save(){
					$data = $this->reqData();
					if(isset( $data['warehouse'] ))  unset( $data['warehouse']);
					if(isset( $data['targetWh'] ))  unset( $data['targetWh']);
					if(isset( $data['targetWhStatus'] ))  unset( $data['targetWhStatus']);
					if(isset( $data['classify'] ) )  unset( $data['classify'] );
					if(isset( $data['accountUuid'])) unset( $data['accountUuid']);
					//if(isset( $data['name'] ) ) 	 unset( $data['name'] );
					//if(isset( $data['barcode'] ) )   unset( $data['barcode'] );
					$this->_saveData = $data;
					$res = parent::save(); 
					if( $res['status'] ==1 && $res['data'] == 0 ){
						 $res['msg'] = "无数据更新,请检查设备编号是否唯一";
					}
					return $res;
		}

		/*
		 *  更新当前仓所在仓
		 */
		public function updateTargetWh($uuid,$targetWh,$targetWhStatus){
				if(empty( $uuid ) || empty( $targetWh )){
						return array('status'=>0,'msg'=>'更新数据错误');
				}
				$data['targetWh'] = $targetWh;
				$data['targetWhStatus'] = (int)$targetWhStatus;
				$where['uuid'] = $uuid;
				$this->_saveWhere = $where;
				$this->_saveData  = $data;
				return parent::save();
		}

		public function regxTable(){ 
				$reUuid    = M('Regx','reStringAll',36);
				$reUuid1   = M('Regx','reStringAll',0,36);
				$reName    = M('Regx','reChinaAll',1,60);
				$reStr     = M('Regx','reStringAll',1,36);
				$rePrice   = M('Regx','reNumRange',0,8);
				$reTime    = M('Regx','reNumRange',10,11);
	    		$reSort    = M('Regx','reNumRange',0,5);
	    		$reStatus  = M('Regx','reNumRange',0,1);
	    		$regxTable = array(
		           'Id'     		=> parent::regxField('id','ID',1,0,0,false,false),
		           'Uuid'   		=> parent::regxField('uuid','编号',1,0,0,$reUuid['re'],'编号:'.$reUuid['msg']),
		           'Name'   		=> parent::regxField('name','设备名称',0,1,1,$reName['re'],'设备名称:'.$reName['msg']),
		           'Barcode'   		=> parent::regxField('barcode','设备编号',0,1,1,$reStr['re'],'设备编号:'.$reStr['msg']),
		           'Price'   		=> parent::regxField('price','价格',0,0,0,$rePrice['re'],'价格:最多6位整数,2位小数'),
		           'Classify'   	=> parent::regxField('classify','所属类目',0,1,1,$reUuid['re'],'所属类目:'.$reUuid['msg']),
		           'Warehouse'   	=> parent::regxField('warehouse','所属仓库',0,1,1,$reUuid['re'],'所属仓库:'.$reUuid['msg']),
		           'TargetWh'   	=> parent::regxField('targetWh','当前所在仓',0,0,0,$reUuid1['re'],'当前所在仓:'.$reUuid1['msg']),
				   'TargetWhStatus' => parent::regxField('targetWhStatus','所在仓状态',0,0,0,$reStatus['re'],'所在仓状态:'.$reStatus['msg']),
		           'BuyerTime'   	=> parent::regxField('buyerTime','采购时间',0,0,0,false,false),
		           'Sort' 			=> parent::regxField('sort','排序',0,0,0,$reSort['re'],'排序:'.$reSort['msg']),
		           'Status' 		=> parent::regxField('status','状态',0,0,0,$reStatus['re'],'状态:'.$reStatus['msg']),
		           'Ctime'     		=> parent::regxField('ctime','创建时间',1,0,0,false,false),
		           'Mtime'   		=> parent::regxField('mtime','更新时间',1,0,0,false,false),
		           'AccountUuid'    => parent::regxField('accountUuid','操作人员',0,1,1,$reUuid['re'],'操作人员:'.$reUuid['msg']),
		       );
		        return $regxTable;
	    }
	    private function tableField()
	    {
		        $field = array(
					'Id'        => "int(8) unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT",
            		'Uuid'      => "char(36) not null comment '编号' ",
            		'Name'      => "varchar(30) not null comment '设备名称'",
            		'Barcode'   => "varchar(36) not null comment '设备编码'",
            		'Price'     => "int(8) default '0' comment '价格单位分/天' ",
            		'Classify'  => "char(36) not null comment '所属类目' ",
		            'Warehouse' => "char(36) not null comment '所属仓库' ",
		            'TargetWh'  =>"char(36) not null comment '当前所在仓' ",
		            'TargetWhStatus'=>"tinyint(2) default '1' comment '所在仓状态 0流转中 1已完成'",
		            'BuyerTime' => "int(11) unsigned DEFAULT '0' comment '采购时间' ",
		            'Sort'      => "mediumint(8) DEFAULT '0' COMMENT '排序'",
		            'Status'	=> "tinyint(2) default '1' comment '设备状态 1正常 2占用 3故障维修'",
		            'Ctime' 	=> "int(11) unsigned DEFAULT '0'",
		            'Mtime' 	=> "int(11) unsigned default '0'",
		            'AccountUuid'    => "char(36) not null comment '操作人员' ",
		        );
		        return $field;
	    }
	    private function tableIndex()
	    {
	        $index = array(
	            'index_u'   => array('Uuid'),
	            'index_n'   => array('Name'),
	            'index_b'   => array('Barcode'),
	            'index_p'   => array('Price'),
	            'index_c'   => array('Classify'),
	            'index_w'   => array('Warehouse'),
	            'index_bt'  => array('BuyerTime'),
	            'index_s'   => array('Status'),
	            'index_c'   => array('Ctime'),
	        );
	        return $index;
	    }
	    private function tableUnique()
	    {
	        $unique = array(
	            'unique_uu'    => array('Uuid'),
	            'unique_bb'    => array('Barcode'),
	        );
	        return $unique;
	    }
	    public function createTable()
	    {
	        return parent::createTable('设备信息');
	    }


}