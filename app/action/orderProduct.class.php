<?php

namespace action;

class orderProduct extends core{


		protected function _init(){
		    		$this->_table 	    = $this->op;
		        	$this->_tableField 	= $this->tableField();
	        		$this->_tableIndex  = $this->tableIndex();
	        		$this->_tableUnique = $this->tableUnique();
		}
		public function addOrderProduct( $data ){
					$this->_addData = $data;
					return parent::add();
		}
		/**/
		public function updateOrderProduct( $orderSn,$data ){
				   if(empty( $orderSn) || empty( $data )){
				   		return array('status'=>0,'msg'=>'更新数据异常');
				   }
				   $where['orderSn'] = $orderSn;
				   $where['status']  = 1;
				   $this->_saveWhere = $where;
				   $this->_saveData  = $data;
				   return parent::save();
		}
		public function add(){}
		public function save(){}
		public function lists(){
				$orderSn  = Q('orderSn');
				$where['t1.OrderSn'] = $orderSn;
				$where['t1.Status']  = 1;
				$field   = "t1.*,t2.Status as deviceStatus,t3.Name as warehouseName,t4.Name as targetWhName";
				$join[]  = "left join {$this->device} t2 on t2.Uuid=t1.Device";
				$join[]  = "left join {$this->warehouse} t3 on t3.Uuid=t2.Warehouse";
				$join[]  = "left join {$this->warehouse} t4 on t4.Uuid=t2.targetWh";
				$this->_aliasTable= 't1';
				$this->_listField = $field;
				$this->_listJoin  = $join;
				$this->_listWhere = $where;
				//$this->_listDebug = true;
				$res = parent::lists();
				return isset($res['lists']) ? $res['lists'] : array();
		}
		
		/*
		 * 更新指定设备状态为无效
		 */
		public function disableByUuids( $orderSn,$uuids ){
					if(empty( $orderSn )) return false;
					$where['orderSn'] = $orderSn;
					$where['device']  = array('in',$uuids);
					$data['status']   = 0;
					$this->_saveData  = $data;
					$this->_saveWhere = $where;
					//$this->_saveDebug =true;
					return parent::save();
		}
		/*
		 * 根据订单编号更新订单产品状态
		 */
		public function cancelOrderProduct( $orderSn ){
					if(empty( $orderSn )) return false;
					$where['orderSn'] = $orderSn;
					$where['status']  = 1;
					$data['status']   = 0;
					$data['ostatus']  = 9;
					$this->_saveData  = $data;
					$this->_saveWhere = $where;
					//$this->_saveDebug =true;
					return parent::save();
		}
		/*
		 * 更新有效订单设备状态
		 */
		public function ostatusByUuids( $orderSn,$uuids,$status ){
					if(empty( $orderSn )) return false;
					$where['orderSn'] = $orderSn;
					$where['device']  = array('in',$uuids);
					$where['status']  = 1;
					$data['ostatus']  = $status;
					$this->_saveData  = $data;
					$this->_saveWhere = $where;
					//$this->_saveDebug =true;
					return parent::save();
		}
		/*
		 * 更新订单设备状态 与订单的设备状态相对应
		 *  2未提货【系统提醒】
		 *  5超期未归还【系统提醒】
		 */
		public function updateOStatus( $orderSn, $ostatus ){
				if(empty( $orderSn ) || !is_array($orderSn ))
						return false;
				if($ostatus!=2 && $ostatus!=4)
						return false;
				$where['orderSn'] = array('in',$orderSn );
				/*
				if($ostatus==2){
					$where['ostatus'] = 1; //已分配,未提货的
				}elseif( $ostatus==4 ){
					$where['ostatus'] = 3; //已提货,未归还
				}
				*/
				$data['ostatus']  = $ostatus; 
				$this->_saveWhere = $where;
				$this->_saveData  = $data;
				return parent::save();
		}

		/*
		 * 获取订单产品中指定ids订单设备
		 * $orderSn 订单编号
		 * $uuids 订单产品device编号
		 */
		public function listsByUuids( $orderSn, $uuids ){
					if(empty( $orderSn ) || empty( $uuids )) return false;
					$where['OrderSn'] = $orderSn;	
					if( count( $uuids ) ==1 ){
						$where['Device']  = $uuids[0];
					}else{
						$where['Device']  = array('in',$uuids);
					}
					$where['Status']  = 1;  //有效订单设备
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
		 * 仓库设备库存
		 *  添加: 自动分配数量
		 */
		public function productTimes(){
				$data 	  		= Q(true);
				$classify 		= isset( $data['classify'] )  ? $data['classify']  : '';
				$warehouse 		= isset( $data['warehouse'] ) ? $data['warehouse'] : '';
				$deviceNum 		= isset( $data['deviceNum'] ) ? (int)$data['deviceNum'] : '';
				$rationStartTime= !empty( $data['rationStartTime'] ) ? strtotime(date('Y-m-d',strtotime($data['rationStartTime']))) : '';
				$rationEndTime 	= !empty( $data['rationEndTime'] ) ? strtotime(date('Y-m-d',strtotime($data['rationEndTime']))) : '';
				$beginPreday 	= isset( $data['beginPreday'] ) ? (int)$data['beginPreday'] : '';
				$endPreday 		= isset( $data['endPreday'] ) ? (int)$data['endPreday'] : '';
				$adjust         = isset( $data['adjust'] ) ? $data['adjust'] : '';
				if(empty( $classify)){
						return array('status'=>0,'msg'=>'请选择设备类目');
				}elseif(empty($warehouse)){
						return array('status'=>0,'msg'=>'请选择提货仓库');
				}elseif(empty($deviceNum)){
						return array('status'=>0,'msg'=>'请选择设备数量');
				}elseif(empty($rationStartTime)){
						return array('status'=>0,'msg'=>'请选择租期开始时间');
				}elseif(empty($rationEndTime)){
						return array('status'=>0,'msg'=>'请选择租期结束时间');
				}
				$startTime = $rationStartTime - ($beginPreday*24*3600);
				$endTime   = $rationEndTime	  + ($endPreday*24*3600); 
				/*主仓设备库存*/
				$warehouseRs = C('warehouse','warehouseNum',$warehouse,$classify);
				$opRs = $this->warehouseNum( $warehouse,$classify,$startTime,$endTime);

				//可调仓设备库存
				$belongWarehouseRs = C('warehouse','belongWarehouseNum',$warehouse,$classify);
				$bopRs	  = $this->belongWarehouseNum( $warehouse,$classify,$startTime,$endTime);
				$mainNum  = isset( $warehouseRs['num'] ) ? $warehouseRs['num'] : 0;
				$mainUsed = isset( $opRs['num'] ) ? $opRs['num'] : 0;
				$surplus  = $mainNum - $mainUsed;  //剩余库存
				$surplus  = $surplus<0 ? 0 : $surplus;  //小于0都是0库存
				$adjustStatus  = 0; //调仓状态 0,不需要调仓 1开启调仓
				$common        = '';
				$mustAdjustNum = $surplus - $deviceNum;  //负数需要调仓
				if( $mustAdjustNum < 0 ){ //主仓库存不足
						$adjustStatus = 1;
						$common = "提货仓库存不足";
				}else{
						$common = "--";
				}
				$res = array();
				$res[] = array(
						'uuid'  => isset( $warehouseRs['uuid'] ) ? $warehouseRs['uuid'] : 0,
						'name'  => isset( $warehouseRs['name'] ) ? $warehouseRs['name']: 0,
						'num'   => isset( $warehouseRs['num'] ) ? $warehouseRs['num'] : 0,
						'used'  => isset( $opRs['num'] ) ? $opRs['num'] : 0,
						'main'  => 1,
						'adjustStatus'=> $adjustStatus,
						'adjustNum'   => $surplus,    //有多少数量
						'common'	  => $common,
				);
				if( !empty( $belongWarehouseRs )){
					$mustAdjustNum = abs($mustAdjustNum);  //待补仓数量
					foreach( $belongWarehouseRs as $key => $val ){
						$adjustNum = 0;
						//剩余数量
						$num  =isset( $val['num'] )  ? $val['num']   : 0;
						$used = isset( $bopRs[$val['uuid']]['num'] )  ? $bopRs[$val['uuid']]['num'] : 0;
						$surplusNum = $num - $used; //注意库存为负
						if( $adjustStatus ==1 ){  //调仓库
								$adjustNum = isset( $adjust[$val['uuid']] ) ? abs($adjust[$val['uuid']]) : false;//输入补仓数量 1 如果为false自动给货
								if( $adjustNum ===false ){
										$adjustNum = $mustAdjustNum; //系统自动调数
								}

								$adjustNum = $adjustNum > $mustAdjustNum ? $mustAdjustNum : $adjustNum; //大于待补仓数量   2
								if( $surplusNum>0 ){ //必须有剩余库存
									$adjustNum = $adjustNum > $surplusNum ? $surplusNum : $adjustNum;       //大于本仓剩余数量 3最终实际数量
								}else{
									$adjustNum = 0;	
								}
								$mustAdjustNum = $mustAdjustNum - $adjustNum;  //最终还需补仓数量
								$common        = '数量:'.$adjustNum;
						}else{
								$common = "--";
						}
						
						$res[] = array(
							'uuid'=> isset( $val['uuid'] ) ? $val['uuid'] : 0,
							'name'=> isset( $val['name'] ) ? $val['name'] : 0,
							'num' => $num,
							'used'=> $used,
							'main'=> 0,
							'adjustStatus'=> $adjustStatus,
							'adjustNum'   => $adjustNum,   //调仓数量
							'common'=> $common,
						);
					}
				}
				return $res;

		}

		/*
		 * 获取主仓类型设备占用库存 
		 * 注意取消单
		 */
		public function warehouseNum($warehouse,$classify,$startTime,$endTime){
					$field   = "count(t1.Id) num,t3.Name as name,t3.Uuid as uuid";
					$join[]  = "left join {$this->device} t2 on t2.Uuid=t1.Device";
					$join[]  = "left join {$this->warehouse} t3 on t3.Uuid=t2.Warehouse";//不需要限定仓库Status
					/*$where1['t1.StartTime'] = array('between',array($startTime,$endTime));
					$where1['t1.EndTime']   = array('between',array($startTime,$endTime));
					$where1['_logic'] = 'OR';
					*/
					/*正向区间*/
					$where1['t1.StartTime'] = array('elt',$startTime);
					$where1['t1.EndTime']   = array('egt',$startTime);
					$where2['t1.StartTime'] = array('elt',$endTime);
					$where2['t1.EndTime']   = array('egt',$endTime);
					/*反向区间*/
					$where3['t1.StartTime'] = array('between',array( $startTime,$endTime )  );
					$where4['t1.EndTime']   = array('between',array( $startTime,$endTime )  );
					$where5 		  		= array($where1,$where2,$where3,$where4,'_logic'=>'OR');
					$where[0] 		  		= $where5;
					$where['t1.Status']    	= 1;
					$where['t2.Warehouse'] 	= $warehouse;
					$where['t2.Classify']  	= $classify; 
					$this->_aliasTable= 't1';
					$this->_listField = $field;
					$this->_listJoin  = $join;
					$this->_listWhere = $where;
					$this->_listGroup = "t2.Warehouse";
					//$this->_listDebug = true;
					$res = parent::lists();
					$lists = isset( $res['lists'][0] ) ? $res['lists'][0] : false;
					return $lists;
		}
		/*
		 *  获取关联副仓类型设备占用库存
		 *  @param $classify 设备类型
		 *  @param $warehouse 主仓
		 */
		public function belongWarehouseNum($warehouse,$classify,$startTime,$endTime){
					$field 	 = "count(t1.Id) num,t4.Name as name,t4.Uuid as uuid";
					$join[]  = "left  join {$this->device} t2 on t2.Uuid=t1.Device"; //设备
					$join[]  = "inner join {$this->wb} t3 on t3.Buuid=t2.Warehouse and t3.Uuid='{$warehouse}' and t3.Status=1"; //关联可调仓库占用情况
					$join[]  = "inner join {$this->warehouse} t4 on t4.Uuid=t3.Buuid"; //具体仓库(Status无关)
					/*
					$where1['t1.StartTime'] = array('between',array($startTime,$endTime));
					$where1['t1.EndTime']   = array('between',array($startTime,$endTime));
					$where1['_logic'] = 'OR';
					$where[0] = $where1;
					*/
					$where1['t1.StartTime'] = array('elt',$startTime);
					$where1['t1.EndTime']   = array('egt',$startTime);
					$where2['t1.StartTime'] = array('elt',$endTime);
					$where2['t1.EndTime']   = array('egt',$endTime);
					/*反向区间*/
					$where3['t1.StartTime'] = array('between',array( $startTime,$endTime )  );
					$where4['t1.EndTime']   = array('between',array( $startTime,$endTime )  );
					$where5 		  		= array($where1,$where2,$where3,$where4,'_logic'=>'OR');
					$where[0] 		  		= $where5;
					$where['t1.Status']    = 1;
					$where['t2.Classify']  = $classify;
					$this->_aliasTable  = 't1';
					$this->_listField = $field;
					$this->_listJoin  = $join;
					$this->_listWhere = $where;
					$this->_listGroup = "t2.Warehouse";
					$this->_listOrder = false;
					//$this->_listDebug = true;
					$res   = parent::lists();
					$lists = isset( $res['lists'] ) ? $res['lists'] : false;
					if( !empty( $lists )){
						$data = array();
						foreach( $lists as $key => $val ){
							$data[$val['uuid']] = $val;	
						}
						return $data;
					}
					return $lists;
		}
		/*
		 * 统计仓库类型设备占用库存
		 * 指定时间段占用设备库存数
		 */
		public function stockNumLists($classify,$startTime,$endTime){
					$field 	 = "count(t1.Id) num,t4.Name as name,t4.Uuid as uuid";
					$join[]  = "left  join {$this->device} t2 on t2.Uuid=t1.Device"; //设备
					$join[]  = "inner join {$this->warehouse} t4 on t4.Uuid=t2.Warehouse";
					/*
					$where1['t1.StartTime'] = array('between',array($startTime,$endTime));
					$where1['t1.EndTime']   = array('between',array($startTime,$endTime));
					$where1['_logic'] = 'OR';
					$where[0] = $where1;
					*/
					$where1['t1.StartTime'] = array('elt',$startTime);
					$where1['t1.EndTime']   = array('egt',$startTime);
					$where2['t1.StartTime'] = array('elt',$endTime);
					$where2['t1.EndTime']   = array('egt',$endTime);
					/*反向区间*/
					$where3['t1.StartTime'] = array('between',array( $startTime,$endTime )  );
					$where4['t1.EndTime']   = array('between',array( $startTime,$endTime )  );
					$where5 		  		= array($where1,$where2,$where3,$where4,'_logic'=>'OR');
					$where[0] 		  		= $where5;
					$where['t1.Status']    = 1;
					$where['t2.Classify']  = $classify;
					$this->_aliasTable  = 't1';
					$this->_listField = $field;
					$this->_listJoin  = $join;
					$this->_listWhere = $where;
					$this->_listGroup = "t2.Warehouse"; //注意是以原始设备所属仓库为准
					$this->_listOrder = false;
					//$this->_listDebug = true;
					$res   = parent::lists();
					$lists = isset( $res['lists'] ) ? $res['lists'] : false;
					if( !empty( $lists )){
						$data = array();
						foreach( $lists as $key => $val ){
							$data[$val['uuid']] = $val;	
						}
						return $data;
					}
					return $lists;
		}

		protected function regxTable(){ 
				$reOrder   = M('Regx','reString',8,16);
				$reUuid    = M('Regx','reStringAll',36);
				$reName    = M('Regx','reChinaAll',1,60);
	    		$reAmount  = M('Regx','reNumRange',1,10);
	    		$reTime    = M('Regx','reNumRange',10,11);
	    		$reTime1   = M('Regx','reNumRange',1,11);
	    		$reStatus  = M('Regx','reNumRange',0,1);
	    		$regxTable = array(
		           'Id'     		=> parent::regxField('id','ID',1,0,0,false,false),
		           'OrderSn'   		=> parent::regxField('orderSn','订单编号',0,1,1,$reOrder['re'],'订单编号:'.$reOrder['msg']),
		           'Warehouse'   	=> parent::regxField('warehouse','设备仓库',0,0,0,$reUuid['re'],'设备仓库:'.$reUuid['msg']),
		           'Classify'   	=> parent::regxField('classify','设备类型',0,0,0,$reUuid['re'],'设备类型:'.$reUuid['msg']),
		           'Device'   		=> parent::regxField('device','设备编号',0,0,0,$reUuid['re'],'设备编号:'.$reUuid['msg']),
		           'Name'   		=> parent::regxField('name','设备名称',0,0,0,$reName['re'],'设备名称:'.$reName['msg']),
		           'RationStartTime'=> parent::regxField('rationStartTime','租期开始时间',0,1,1,$reTime['re'],'租期开始时间:'.$reTime['msg']),
		           'RationEndTime'  => parent::regxField('rationEndTime','租期结束时间',0,1,1,$reTime['re'],'租期结束时间:'.$reTime['msg']),
		           'Pretime'   		=> parent::regxField('pretime','发货占时',0,0,0,$reTime1['re'],'发货占时:'.$reTime1['msg']),
		           'Backtime' 		=> parent::regxField('backtime','回库占时',0,0,0,$reTime1['re'],'回库占时:'.$reTime1['msg']),
		           'DeliveryTime'   => parent::regxField('deliveryTime','提货时间',0,1,1,$reTime['re'],'提货时间:'.$reTime['msg']),
		           'StartTime'  	=> parent::regxField('startTime','库存开始时间',0,1,1,$reTime['re'],'库存开始时间:'.$reTime['msg']),
		           'EndTime' 		=> parent::regxField('endTime','库存结束时间',0,1,1,$reTime['re'],'库存结束时间:'.$reTime['msg']),
		           'Price' 			=> parent::regxField('price','价格',0,0,0,$reAmount['re'],'价格:'.$reAmount['msg']),

		           'Ostatus' 		=> parent::regxField('ostatus','设备状态',0,0,0,$reStatus['re'],'设备状态:'.$reStatus['msg']),
		           'Status' 		=> parent::regxField('status','状态',0,0,0,$reStatus['re'],'状态:'.$reStatus['msg']),
		           'Ctime'     		=> parent::regxField('ctime','创建时间',1,0,0,false,false),
		           'Mtime'   		=> parent::regxField('mtime','更新时间',1,0,0,false,false),
		       );
		        return $regxTable;
	    }
	    private function tableField()
	    {
		        $field = array(
					'Id'         	 => "int(8) unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT",
            		'OrderSn'    	 => "varchar(16) not null comment '订单编号' ",
            		'Warehouse'	     => "char(36) default '' comment '设备仓库' ",
            		'Classify'	     => "char(36) default '' comment '设备类型' ",
            		'Device'     	 => "char(36) default '' comment '订单设备编号' ",
            		'Name'      	 => "varchar(30) not null comment '设备名称'",
            		'RationStartTime'=> "int(11) unsigned not null comment '租期开始时间' ",
            		'RationEndTime'  => "int(11) unsigned not null comment '租期结束时间' ",
            		'Pretime'    	 => "int(11) unsigned DEFAULT '0' comment '发货占时单位秒' ",
            		'Backtime'   	 => "int(11) unsigned DEFAULT '0' comment '回库占时单位秒' ",
            		'DeliveryTime'	 => "int(11) unsigned not null comment '提货时间,开始时间秒' ",
            		'StartTime' 	 => "int(11) unsigned not null comment '占用库存开始时间 提货时间-发货占时' ",
            		'EndTime'		 => "int(11) unsigned not null comment '占用库存结束时间 提货时间+天数占时+回库占时' ",
            		'Price'     	 => "int(8)  unsigned default '0' comment '价格单位分/天' ",
            		'Ostatus'   	 => "tinyint(2) default '0' comment '设备状态,1已分配2未提货3未归还4已归还5超期未还0维修异常' ",
		            'Status'		 => "tinyint(2) default '0' comment '1正常 0无效' ",
		            'Ctime' 		 => "int(11) unsigned DEFAULT '0' ",
		            'Mtime' 		 => "int(11) unsigned default '0' ",
		        );
		        return $field;
	    }
	    private function tableIndex()
	    {
	        $index = array(
	            'index_osn' =>  array('OrderSn'),
	            'index_w'	=>	array('Warehouse'),
	            'index_c'	=>	array('Classify'),
	            'index_de'	=>	array('Device'),
	            'index_rs'	=>	array('RationStartTime'),
	            'index_re'  =>  array('RationEndTime'),
	            'index_dt'  =>  array('DeliveryTime'),
	            'index_st'	=>  array('StartTime'),
	            'index_et'	=>  array('EndTime'),
	            'index_os'	=>	array('Ostatus'),
	            'index_s'	=>	array('Status'),
	            'index_ctime'=> array('Ctime'),
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
	        return parent::createTable('订单设备信息');
	    }


}