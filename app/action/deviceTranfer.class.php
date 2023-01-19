<?php

namespace action;

class deviceTranfer extends core{

		protected function _init(){
		    		$this->_table 	    = $this->df;
		        	$this->_tableField 	= $this->tableField();
	        		$this->_tableIndex  = $this->tableIndex();
	        		$this->_tableUnique = $this->tableUnique();
		}

		public function deviceStatus($key=false){
				$list = array(
					'0'=>'发货',
					'1'=>'确认完成',
				);
				return !empty( $key )||is_numeric($key) ? (isset( $list[$key] ) ? $list[$key] : false) : $list;
		}

		/*
		 * 取最后一条有效信息
		 */
		public function findOrderDevice( $orderSn, $device ){
				$field = "t1.*,t2.Name as fromWarehouseName,t3.Name as toWarehouseName";
				$join[] = "left join {$this->warehouse} t2 on t2.Uuid=t1.FromWarehouse";
				$join[] = "left join {$this->warehouse} t3 on t3.Uuid=t1.ToWarehouse";
				$where['t1.OrderSn'] = $orderSn;
				$where['t1.Uuid'] = $device;
				$where['t1.Status'] = 1; //有效记录
				$this->_aliasTable = 't1';
				$this->_listField = $field;
				$this->_listJoin  = $join;
				$this->_listWhere = $where;
				$this->_listOrder = "Id desc";
				//$this->_listDebug = true;
				$res = parent::lists();
				$data = isset( $res['lists'][0] ) ? $res['lists'][0] : false;
				return $data;
		}
		/*
		 * 取最后一条有效流转信息
		 */
		public function findDevice( $device ){
				$field  = "t1.*,t2.Name as fromWarehouseName,t3.Name as toWarehouseName";
				$join[] = "left join {$this->warehouse} t2 on t2.Uuid=t1.FromWarehouse";
				$join[] = "left join {$this->warehouse} t3 on t3.Uuid=t1.ToWarehouse";
				$where['t1.Uuid'] = $device;
				$where['t1.Status'] = 1; //有效记录 一般都是唯一的
				$this->_aliasTable = 't1';
				$this->_listField = $field;
				$this->_listJoin  = $join;
				$this->_listWhere = $where;
				$this->_listOrder = "Id desc";
				//$this->_listDebug = true;
				$res = parent::lists();
				$data = isset( $res['lists'][0] ) ? $res['lists'][0] : false;
				return $data;
		}

		/*
		 * 获取设备实际所在仓
		 */
		public function deviceWarehouse( $device ){
					Q('uuid',$device);
					$deviceInfoRes   	 = C('device','find'); //获取设备信息根据更新时间与流转时间信息判断
					$deviceInfo      	 = isset($deviceInfoRes['data']) ? $deviceInfoRes['data'] : false; //设备信息
					if(empty( $deviceInfo ))
							return false;
					return array('warehouse'=>$deviceInfo['targetWh'],
								 'warehouseName'=>$deviceInfo['targetWhName'],
								 'sourceWarehouse'=>$deviceInfo['warehouse'],
								 'sourceWarehouseName'=>$deviceInfo['warehouseName'],
							);
		}

		private function reqData(){
					$account = C('login','account');
					$accountUuid = isset( $account['uuid'] ) ? $account['uuid'] : '';
					if(empty( $accountUuid )){
							echo json_encode( array('status'=>0,'msg'=>'登录帐号异常'));die;
					}
					$data = Q('data');
					$where = Q('where');
					$orderSn = isset( $where['ordersn'] ) ? $where['ordersn'] : false;
					$device  = isset( $where['device'] ) ? $where['device'] : false;
					/*订单基本信息*/
					Q('ordersn',$orderSn);
					$infoRes 	=  C('order','find');
					$info 	 	=  isset( $infoRes['data'] ) ? $infoRes['data'] : false; //1，订单信息,其中包含最后一次有效流转信息
					$orderWarehouse = isset( $info['warehouse'] ) ? $info['warehouse'] : false;
					if(empty( $orderWarehouse)){
							echo json_encode( array('status'=>0,'msg'=>'订单异常'));die;
					}
					/*验证是否存在该订单设备*/
					$devices = array( $device );
					$opInfoRes = C('orderProduct','listsByUuids',$orderSn,$devices); 
					$opInfo    = isset( $opInfoRes[0] ) ? $opInfoRes[0] : false;
					if(empty( $opInfo )){
							echo json_encode( array('status'=>0,'msg'=>'订单产品异常'));die;
					}
					/* end */
					/*设备信息*/
					Q('uuid',$device);
					$deviceInfoRes   	 = C('device','find'); //获取设备信息根据更新时间与流转时间信息判断
					$deviceInfo      	 = isset($deviceInfoRes['data']) ? $deviceInfoRes['data'] : false; //设备信息
					if(empty( $deviceInfo )){
							echo json_encode( array('status'=>0,'msg'=>'设备数据异常'));die;
					}
					if( $deviceInfo['targetWh']== $orderWarehouse && $deviceInfo['targetWhStatus'] == 1 ){
							echo json_encode( array('status'=>0,'msg'=>'调仓与所在仓相同,不需要调仓'));die;
					}

					$data['fromWarehouse'] = $deviceInfo['targetWh'];
					$data['toWarehouse']   = $orderWarehouse;
					$data['uuid']    = $device;
					$data['orderSn'] = $orderSn;
					$data['status']  = 1;
					$data['accountUuid'] = $accountUuid;
					return $data;
		}
		/*
		 * 用于订单调仓
		 */
		public function add(){
					$data = $this->reqData();
					/*先更新为无效状态*/
					$upWhere['uuid'] = $data['uuid'];
					//$upWhere['orderSn'] = $data['orderSn']; //针对设备不能对于订单
					$upData['status'] = 0;
					$this->_saveWhere = $upWhere;
					$this->_saveData  = $upData;
					$upRes = parent::save();
					if($upRes['status'] ==1 ){
						$this->_addData = $data;
						$res = parent::add();
						if( $res['status']==1 ){ //更新该设备的当前所在仓
								$deviceRes = C('device','updateTargetWh',$data['uuid'],$data['toWarehouse'],$data['dstatus']);
						}		
						return $res;
					}else{
						return $upRes;
					}
					
		}
		public function save(){}
		/*
		 * 通过设备入口调仓
		 */
		public function addition(){
				$account = C('login','account');
				$accountUuid = isset( $account['uuid'] ) ? $account['uuid'] : '';
				if(empty( $accountUuid )){
						return array('status'=>0,'msg'=>'登录帐号异常');
				}
				$where = Q('where');
				$data  = Q('data');
				$uuid  = isset( $where['uuid'] ) ? $where['uuid'] : false;
				Q('uuid',$uuid);
				$deviceRes  = C('device','find');
				$deviceInfo = isset( $deviceRes['data'] ) ? $deviceRes['data'] : false;
				if(empty( $deviceInfo )){
						return array('status'=>0,'msg'=>'设备数据异常');
				}
				/*调仓信息*/
				$warehouse = isset( $data['warehouse'] ) ? $data['warehouse'] : '';
				$tracking  = isset( $data['tracking'] ) ? $data['tracking'] : '';
				$descript  = isset( $data['descript'] ) ? $data['descript'] : '';
				$status    = isset( $data['dstatus'] ) ? $data['dstatus'] : 0;
				/*所在仓*/
				$targetWh = isset( $deviceInfo['targetWh'] ) ? $deviceInfo['targetWh'] : false;
				/*流转信息*/
				$transfer   = C('deviceTranfer','findDevice',$uuid); //是否正在流转
				$targetWhStatus = $deviceInfo['targetWhStatus']; //流转状态
				$dstatus        = isset( $transfer['dstatus'] ) ? $transfer['dstatus'] : false;
				$toWarehouse    = isset( $transfer['toWarehouse'] ) ? $transfer['toWarehouse'] : false;
				$fromWarehouse  = isset( $transfer['fromWarehouse'] ) ? $transfer['fromWarehouse'] : false;
				if( isset( $transfer['dstatus'] ) && $dstatus==0 ){
						//还在流转中,需要确认完成上一次流转
						$transfer['tracking']    = $tracking;
						$transfer['descript']    = $descript;
						$transfer['dstatus']     = $status;
						$transfer['accountUuid'] = $accountUuid;
						$transfer['status']      = 1;
						$data = $transfer;
				}else{ //
					if( $targetWh==$warehouse ){
							return array('status'=>0,'msg'=>'调整仓与当前所在仓一致,不需要调仓');
					}
					if(empty( $targetWh )){
							$targetWh = $deviceInfo['warehouse'];
					}
					$data = array(
							'uuid' => $uuid,
							'fromWarehouse'=> $targetWh,
							'toWarehouse'  => $warehouse,
							'tracking'	   => $tracking,
							'descript'	   => $descript,
							'dstatus'      => $status,
							'accountUuid'  => $accountUuid,
							'status'       => 1
					);
				}

				$upWhere['uuid'] = $uuid;
				$upData['status'] = 0;
				$this->_saveWhere = $upWhere;
				$this->_saveData  = $upData;
				$upRes = parent::save();
				if($upRes['status'] ==1 ){
					$this->_addData = $data;
					$res = parent::add();
					if( $res['status']==1 ){ //更新该设备的当前所在仓
							$deviceRes = C('device','updateTargetWh',$uuid,$data['toWarehouse'],$status );
					}		
					return $res;
				}else{
					return $upRes;
				}
		}

		public function regxTable(){ 
				$reUuid    = M('Regx','reStringAll',36);
				$reOrder   = M('Regx','reString',8,16);
				$reTrack   = M('Regx','reString',0,36);
				$reDesc    = M('Regx','reStringAll',0,360);
				$reTime    = M('Regx','reNumRange',10,11);
	    		$reStatus  = M('Regx','reNumRange',0,1);
	    		$regxTable = array(
		           'Id'     		=> parent::regxField('id','ID',1,0,0,false,false),
		           'Uuid'   		=> parent::regxField('uuid','编号',0,1,1,$reUuid['re'],'编号:'.$reUuid['msg']),
		           'FromWarehouse'  => parent::regxField('fromWarehouse','源仓库编号',0,1,1,$reUuid['re'],'源仓库编号:'.$reUuid['msg']),
		           'ToWarehouse'   	=> parent::regxField('toWarehouse','调转仓库编号',0,1,1,$reUuid['re'],'调转仓库编号:'.$reUuid['msg']),
		           
		           'OrderSn'   		=> parent::regxField('orderSn','订单号',0,0,0,$reOrder['re'],'订单号:'.$reOrder['msg']),
		           'Tracking'   	=> parent::regxField('tracking','运单号',0,0,0,$reTrack['re'],'运单号:'.$reTrack['msg']),
		           'Descript'   	=> parent::regxField('descript','备注',0,0,0,$reDesc['re'],'备注:'.$reDesc['msg']),
		           'Dstatus'   		=> parent::regxField('dstatus','流转状态',0,0,0,$reStatus['re'],'流转状态:'.$reStatus['msg']),
		           'Status' 		=> parent::regxField('status','状态',0,0,0,$reStatus['re'],'状态:'.$reStatus['msg']),
		           'Ctime'     		=> parent::regxField('ctime','创建时间',1,0,0,false,false),
		           'AccountUuid'    => parent::regxField('accountUuid','操作人员',0,1,1,$reUuid['re'],'操作人员:'.$reUuid['msg']),
		       );
		        return $regxTable;
	    }
	    private function tableField()
	    {
		        $field = array(
					'Id'        	=> "int(8) unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT",
            		'Uuid'     	 	=> "char(36) not null comment '设备编号' ",
            		'FromWarehouse' => "char(36) not null comment '源仓库编号' ",
            		'ToWarehouse'   => "char(36) not null comment '调转仓库编号' ",
            		
            		'OrderSn'   	=> "char(16) default '' comment '订单编号' ",
            		'Tracking'   	=> "char(36) default '' comment '运单号' ",
            		'Descript'  	=> "varchar(120) default '' comment '流转备注' ",
		            'Dstatus'   	=> "tinyint(2) default '0' comment '0流转中1流转完成' ",
		            'Status'		=> "tinyint(2) default '1' comment '状态0无效1有效'",
		            'Ctime' 		=> "int(11) unsigned DEFAULT '0'",
		            'AccountUuid'    => "char(36) not null comment '操作人员' ",
		        );
		        return $field;
	    }
	    private function tableIndex()
	    {
	        $index = array(
	            'index_u'   => array('Uuid'),
	            'index_f'   => array('FromWarehouse'),
	            'index_t'   => array('ToWarehouse'),
	            
	            'index_d'   => array('Dstatus'),
	            'index_s'   => array('Status'),
	            'index_c'   => array('Ctime'),
	        );
	        return $index;
	    }
	    private function tableUnique()
	    {
	        $unique = array();
	        return $unique;
	    }
	    public function createTable()
	    {
	        return parent::createTable('设备调仓流转信息');
	    }


}