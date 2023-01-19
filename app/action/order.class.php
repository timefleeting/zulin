<?php

namespace action;

class order extends core{

		private $startTime = "20180101";
		private $maxDeviceNum = 10;  //一次最大租用设备数量

		protected function _init(){
		    		$this->_table 	    = $this->order;
		        	$this->_tableField 	= $this->tableField();
	        		$this->_tableIndex  = $this->tableIndex();
	        		$this->_tableUnique = $this->tableUnique();
		}
		/*
		 * 订单列表筛选类型
		 */
		public function searchStatus(){
				$list = array(
					'0'=>'所有',
					'1'=>'需调仓',
					'2'=>'需补仓',
					'13'=>'需发货',
					'14'=>'需确认收货',
					'4'=>'未提货',
					'3'=>'异常',
					'5'=>'超期未归还',
					'6'=>'归还故障',
					'7'=>'超期归还故障',
					'8'=>'已提货',
					'9'=>'已归还',
					'10'=>'超期归还',
					'11'=>'订单完成',
					'12'=>'已取消',
				);
				return $list;
		}

		public function deviceStatus( $key=false,$way=0 ){
				$list = array(
					'1'	=>	'已分配',
					'2'	=>	'未提货',
					'3' =>  '已提货',
					'4'	=>	'超期未归还',
					'5' =>	'已归还',
					'6' =>	'超期归还',
					'7' =>  '归还故障',
					'8' =>  '超期归还故障',
					'9' =>  '已取消',
				);
				if( $way==1 ){
						$list['2'] = '未发货';
						$list['3'] = '已发货';
				}

				return !empty( $key )|| is_numeric($key) ? (isset( $list[$key] ) ? $list[$key] : false) : $list;
		}
		public function orderStatus( $key=false ){
				$list = array(
					//'0' => '无效',
					'1' => '正常',
					'2' => '已取消',
					'3' => '异常',
					'5' => '订单完成',
				);
				return !empty( $key )||is_numeric($key) ? (isset( $list[$key] ) ? $list[$key] : false) : $list;
		}

		/*异常状态*/
		public function exceptionStatus( $key=false ){
				$lists = array('2','4','7','8');
				return $lists;
		}

		/*
		 * 归还的设备状态
		 * 必须对应设备状态
		 */
		public function givebackStatus( $key=false ){
				$list = array(
					'1' => '正常',
					'3' => '故障维修',
				);
				return !empty( $key )||is_numeric($key) ? (isset( $list[$key] ) ? $list[$key] : false) : $list;
		}
		/*
		 * 判断可调仓状态 已分配 未提货的,可调仓
		 */
		public function beBackStatus( $status ){
				$status = (int)$status;
				$list = array(1,2);
				if( in_array( $status,$list ) )
						return true;
				return false;
		}

		/*订单地址提交数据*/
		private function reqAddress(){
				$text        = Q('text');
				$select      = Q('select');
				$areacode    = Q('areacode');
				$mobile	     = isset( $text['mobile'] ) ? trim($text['mobile']) : '';
				$name	     = isset( $text['name'] )   ? trim($text['name'])   : '';
				$provinceName= isset( $select['liveArea'][1] )    ? $select['liveArea'][1] : '';
				$cityName	 = isset( $select['liveArea'][2] )    ? $select['liveArea'][2] : '';
				$areaName	 = isset( $select['liveArea'][3] )    ? $select['liveArea'][3] : '';
				$province	 = isset( $areacode['liveArea'][1] )  ? $areacode['liveArea'][1] : '';
				$city		 = isset( $areacode['liveArea'][2] )  ? $areacode['liveArea'][2] : '';
				$area		 = isset( $areacode['liveArea'][3] )  ? $areacode['liveArea'][3] : '';
				$address  	 = isset( $text['address'] ) ? trim( $text['address'] ) : '';
				$data = array(
						'mobile'  => $mobile,
						'name'    => $name,
						'province'=> $province,
						'city'	  => $city,
						'area'    => $area,
						'provinceName' => $provinceName,
						'cityName' => $cityName,
						'areaName' => $areaName,
						'address'  => $address,
						'status'   => 1
				);
				return array('status'=>1,'data'=>$data);
		}
		/*订单产品提交信息*/
		private function reqOrderProduct(){
				$text     = Q('text');
				$select   = Q('select');
				$rationStartTime= isset($text['rationStartTime'])&&!empty($text['rationStartTime'])? 
									strtotime(date('Y-m-d',strtotime($text['rationStartTime'])))   : 0;
				$rationEndTime	= isset($text['rationEndTime'] )&&!empty($text['rationEndTime'])   ? 
									strtotime(date('Y-m-d',strtotime($text['rationEndTime'])))+(24*3600) -1  : 0;
				$deliveryTime   = isset($text['deliveryTime'] )&&!empty($text['deliveryTime'])     ? strtotime($text['deliveryTime'])    : 0;
				$pretime	    = isset($select['beginPreday'][1])? (int)$select['beginPreday'][1] : 0;
				$backtime		= isset($select['endPreday'][1] ) ? (int)$select['endPreday'][1]   : 0;
				if(empty( $rationStartTime )) return array('status'=>0,'msg'=>'请选择租期开始时间');
				if(empty( $rationEndTime ))   return array('status'=>0,'msg'=>'请选择租期结束时间');
				if(empty( $deliveryTime ))    return array('status'=>0,'msg'=>'请选择提货时间');
				$pretime   = $pretime*24*3600;
				$backtime  = $backtime*24*3600;
				$startTime = $rationStartTime - $pretime;  //[00:00]
				$endTime   = $rationEndTime	  + $backtime; //[23:59]
				$data = array(
						'rationStartTime' => $rationStartTime,
						'rationEndTime'	  => $rationEndTime,
						'deliveryTime'	  => $deliveryTime,
						'pretime'	      => $pretime,
						'backtime'        => $backtime,
						'startTime'		  => $startTime,
						'endTime'		  => $endTime
				);
				return array('status'=>1,'data'=>$data);
		}
		/*
		 * $num 总共需补仓数量
		 */
		private function adjustProduct($classify,$startTime,$endTime,$num){
				$adjustnum = Q('adjustnum');
				if(empty( $adjustnum ) || !is_array($adjustnum) ){
						return array('status'=>0,'msg'=>'无可调仓库');
				}
				$data = array();
				foreach( $adjustnum as $key => $val ){
						$val = (int)$val;
						if(empty( $val )) continue;
						$deviceNum = $val > $num ? $num : $val; //需要调配的数量
						$allotDevice= C('warehouse','warehouseProduct',$key,$classify,$startTime,$endTime,$deviceNum);
						if( !empty( $allotDevice )  ){
							$data =  array_merge($data,$allotDevice);
						}	
				}
				$status = !empty( $data ) ? 1 : 0;
				return array('status'=> $status,'data'=>$data );
		}
		/*订单产品分配数据*/
		private function orderProduct( $orderSn,$warehouse,$classify,$deviceNum=1 ){
				if( $deviceNum > $this->maxDeviceNum){
						return array('status'=>0,'msg'=>'设备数量不得超过'+$this->maxDeviceNum );
				}
				$reqOrderProduct = $this->reqOrderProduct();
				if( $reqOrderProduct['status'] != 1 ){
						return $reqOrderProduct;
				}
				$reqOrderProduct['data']['orderSn'] = $orderSn;
				$orderProductRes = C('orderProduct','tableModel',isset($reqOrderProduct['data'])?$reqOrderProduct['data']:false );
				if( $orderProductRes['status'] != 1 ) return $orderProductRes; //数据结构验证失败
				$orderProduct = $reqOrderProduct['data'];
				$startTime = $orderProduct['startTime'];
				$endTime   = $orderProduct['endTime'];
				$allotDevice= C('warehouse','warehouseProduct',$warehouse,$classify,$startTime,$endTime,$deviceNum);
				$allotDeviceNum = !empty( $allotDevice ) ? count($allotDevice) : 0;
				$surplusNum = $allotDeviceNum - $deviceNum;  //需补仓数量
				if(empty( $allotDevice) || $surplusNum < 0 ){ //主仓不足,启动调仓机制
						$adjustProductRes = $this->adjustProduct($classify,$startTime,$endTime,abs($surplusNum));
						if($adjustProductRes['status']!=1){
								$assemblyProduct = $this->assemblyProduct( $allotDevice,$orderProduct );
								return array('status'=>0,'msg'=>'提货仓库库存不足,是否直接提交!','msgcode'=>1,'data'=>$assemblyProduct['data'],'price'=>$assemblyProduct['price'],'hasData'=>$assemblyProduct['hasData']);	
						}else{
								$adjustProduct  = $adjustProductRes['data'];
								$allotDevice    = !empty( $allotDevice ) ? array_merge($allotDevice,$adjustProduct) : $adjustProduct;
								/*再次判定数量是否一致*/
								$allotDeviceNum = !empty( $allotDevice ) ? count($allotDevice) : 0;
								$surplusNum 	= $allotDeviceNum - $deviceNum;  //还需补仓数量
								if( $surplusNum < 0 ){
								$assemblyProduct = $this->assemblyProduct( $allotDevice,$orderProduct );
								return array('status'=>0,'msg'=>"还需设备数量".abs($surplusNum).",是否直接提交!",'msgcode'=>1,'data'=>$assemblyProduct['data'],'price'=>$assemblyProduct['price'],'hasData'=>$assemblyProduct['hasData']);	
								}
						}

				}
				return $this->assemblyProduct( $allotDevice,$orderProduct );
		}
		/*
		 * 组装订单产品数据信息
		 * hasData 则不组装信息，可以下单，但是需要后续补货
		 */
		private function assemblyProduct( $allotDevice,$orderProduct ){
				if(empty( $allotDevice)){
						return array('status'=>0,'data'=>array($orderProduct),'price'=>0,'hasData'=>0); //状态0异常,data原路返回注意转变为二级数组
				}
				$addOrderProduct  = array();
				$timer = time();
				$price = 0;
				$days  = ceil( ($orderProduct['rationEndTime'] - $orderProduct['rationStartTime'] )/3600/24 );
				foreach( $allotDevice as $key => $val ){
						$orderProduct['warehouse'] 	= $val['warehouse'];
						$orderProduct['classify']  	= $val['classify'];
						$orderProduct['device'] 	= $val['uuid'];
						$orderProduct['name']   	= $val['name'];
						$orderProduct['price']  	= (int)$val['price'];
						$orderProduct['ostatus']	= 1;
						$orderProduct['status'] 	= 1;	
						$orderProduct['ctime']  	= $timer;
						$orderProduct['mtime']  	= $timer;	
						$addOrderProduct[] = $orderProduct;	
						$price += (int)$val['price'] * $days;
				}
				return array('status'=>1,'data'=>$addOrderProduct,'price'=>$price,'hasData'=>1);
		}

		/*订单地址信息*/
		public function orderAddress( $orderSn ){
				$reqAddress   = $this->reqAddress();
				if(isset( $reqAddress['data'] )){
						$reqAddress['data']['orderSn'] = $orderSn; 
				}
				$addressRes = C('orderAddress','tableModel',isset($reqAddress['data'])?$reqAddress['data']:false );
				if( $addressRes['status'] != 1 ) return $addressRes;
				$orderAddress = $reqAddress['data'];
				return array('status'=>1,'data'=>$orderAddress );
		}
		/*添加订单*/
		public function add(){
				$account = C('login','account');
				$accountUuid = isset( $account['uuid'] ) ? $account['uuid'] : '';
				if(empty( $accountUuid )){
						return array('status'=>0,'msg'=>'登录帐号异常');
				}
				$text     	= Q('text');
				$select   	= Q('select');
				$areacode 	= Q('areacode');
				$again      = Q('again');    //是否再次确认提交
				$classify 	= isset( $text['classify'] ) ? $text['classify'] : '';
				$warehouse	= isset( $text['warehouse'] )? $text['warehouse']: '';
				$deviceNum	= isset( $select['deviceNum'][1] ) ? (int)$select['deviceNum'][1] : 1;
				$way        = isset( $text['way'] ) ? $text['way'] : 0; //提货方式
				$orderSn    = $this->orderSn();
				//订单产品信息
				$orderProductRes = $this->orderProduct( $orderSn,$warehouse,$classify,$deviceNum );
				if($orderProductRes['status']!=1 && $again!=1 ){ //again提交则允许跳过
					  return $orderProductRes;
				} 
				$orderProduct = $orderProductRes['data'];
				//订单地址信息
				$orderAddressRes = $this->orderAddress( $orderSn );
				if($orderAddressRes['status']!=1) return $orderAddressRes;
				$orderAddress = $orderAddressRes['data'];
				/*订单汇总信息*/
				$timer = time();
				$orderData = array(
						'orderSn'   		=> $orderSn,
						'userUuid'  		=> '',
						'warehouse' 		=> $warehouse,
						'classify'  		=> $classify,
						'num'				=> $deviceNum,
						'way'				=> $way,
						'rationStartTime'	=> $orderProduct[0]['rationStartTime'],
						'rationEndTime' 	=> $orderProduct[0]['rationEndTime'],
						'pretime' 			=> $orderProduct[0]['pretime'],
						'backtime' 			=> $orderProduct[0]['backtime'],
						'deliveryTime' 		=> $orderProduct[0]['deliveryTime'],
						'originPrice' 		=> $orderProductRes['price'],
						'salePrice' 		=> $orderProductRes['price'],
						'amountPayable' 	=> $orderProductRes['price'],
						'ostatus'	    	=> 1,
						'status'			=> 1,
						'ctime'				=> $timer,
						'mtime'         	=> $timer,
						'accountUuid'       => $accountUuid
				);
				$this->_addData = $orderData; 
				$orderRes = parent::add();
				$msg = $orderRes['msg'];
				if( $orderRes['status'] ==1 ){ //order success
					if( isset( $orderProductRes['hasData'] ) && $orderProductRes['hasData']==1 ){
						 $addOrderProduct = C('orderProduct','addOrderProduct',$orderProduct);
						if( $addOrderProduct['status']!=1){
							$msg .= '.订单产品保存:'.$addOrderProduct['msg'];
						} 
					}
					$addOrderAddress = C('orderAddress','addOrderAddress',$orderAddress);				
					if( $addOrderAddress['status']!=1){
							$msg .= '.订单地址保存:'.$addOrderAddress['msg'];
					}
					$logRes = C('orderLog','addLog',$orderSn,1,'order','add','新下订单','','',1);
					//var_dump( $logRes );die;
				}
				$orderRes['msg'] = $msg;
				return $orderRes;
		}

		public function lists(){
				$field  = "t1.*,t2.Mobile as userMobile,t2.Name as userName,t2.ProvinceName as provinceName,t2.CityName as cityName,t2.AreaName as areaName,t2.Address as address,t6.Name as warehouseName,t11.Name as accountName,t12.Ostatus as pickupStatus,t5.Name as classifyName,group_concat(  
		  								CONCAT( 
		  									'{\"warehouse\":\"',t3.Warehouse,
		  									'\",\"warehouseName\":\"', t4.Name,
		  									'\",\"device\":\"', t3.Device,
		  									'\",\"name\":\"', t3.Name,
		  									'\",\"barcode\":\"', t7.Barcode,
		  									'\",\"price\":\"', t3.Price,
		  									'\",\"ostatus\":\"',t3.Ostatus,
		  									'\",\"status\":\"',t3.Status,
		  									'\",\"dstatus\":\"',t7.Status,
		  									'\",\"targetWh\":\"', t7.TargetWh,
		  									'\",\"targetWhName\":\"', t10.Name,
		  									'\",\"targetWhStatus\":\"', t7.TargetWhStatus,
		  									'\"}'
		  								)  
		  							   SEPARATOR '&&&&' 
		  							 ) as item_data,
		  							 
		  							 count(t3.Id) as opNum,
		  							 SUM(CASE WHEN t7.TargetWh!=t1.Warehouse AND t7.targetWhStatus=1 THEN 1 ELSE 0 END) AS state11,
		  							 SUM(CASE WHEN t7.TargetWh=t1.Warehouse AND t7.targetWhStatus=0 THEN 1 ELSE 0 END) AS state12
		  							 ";
				$join[] = "left join {$this->oa} t2 on t2.OrderSn=t1.OrderSn and t2.Status=1";
				$join[] = "left join {$this->op} t3 on t3.OrderSn=t1.OrderSn and (t3.Status=1 or t3.Status=9)"; //1占用 9取消
				$join[] = "left join {$this->warehouse} t4 on t4.Uuid=t3.Warehouse";
				$join[] = "left join {$this->classify} t5 on t5.Uuid=t1.Classify";
				$join[] = "left join {$this->warehouse} t6 on t6.Uuid=t1.Warehouse";
				$join[] = "left join {$this->device} t7 on t7.Uuid=t3.Device";
				//$join[] = "left join {$this->df} t8 on t8.OrderSn=t1.OrderSn and t8.Status=1";
				$join[] = "left join {$this->warehouse} t10 on t10.Uuid=t7.TargetWh";
				$join[] = "left join {$this->account} t11 on t11.Uuid=t1.AccountUuid";
				$join[] = "left join (select OrderSn,Ostatus from {$this->ol} where Method='pickupStatus' and Status=1 group by OrderSn) t12 on t12.OrderSn=t1.OrderSn";
				$this->_aliasTable= 't1';
				$this->_listField = $field;
				$this->_listJoin  = $join;
				$this->_listGroup = 't1.OrderSn';
				$this->_listOrder = 't1.Id desc';
				//$this->_listDebug = true;
				$res   = parent::lists();
				return $res;
		}
		/*
		 * 列表状态查询条件
		 */
		private function searchstatusQuery( ){ 
				$query  = Q('query');
				$status = isset( $query['searchstatus'] ) ? $query['searchstatus'] : false;
				if( $status ==1 ){ //需调仓
						$this->_listHaving = "state11>0 or state12>0";
						$this->_listFieldCount = "SUM(CASE WHEN t7.TargetWh!=t1.Warehouse AND t7.targetWhStatus=1 THEN 1 ELSE 0 END) AS state11,SUM(CASE WHEN t7.TargetWh=t1.Warehouse AND t7.targetWhStatus=0 THEN 1 ELSE 0 END) AS state12";
				}elseif( $status == 2 ){ //需补仓
						$this->_listFieldCount = "t1.Num,count(t3.Id) as opNum";
						$this->_listHaving = "t1.Num>opNum";
						$where['t1.Ostatus'] = array('in',array('1','2')); //需补仓状态
						$where['t1.Status']  = 1;
						$this->_listWhere = $where;
				}elseif( $status == 3 ){ //异常
						$exceptionStatus = $this->exceptionStatus();
						$where['t1.Ostatus'] = array('in',$exceptionStatus); //异常设备状态
						$where['t7.Status']  = array('neq',1);
						$where['_logic'] = "or";
						$where1['t1.Status'] = array('in',array(1,3));
						$where2 = array($where,$where1);
						$this->_listWhere = $where2;
				}elseif( $status==4){  //未提货
						$where['t1.Ostatus'] = array('in',array(1,2));
						$where['t1.Status']  = 1;
						$where['t1.Way']     = 0;
						$this->_listWhere = $where;
				}elseif( $status==5){ //超期未归还
						$where['t1.Ostatus'] = 4;
						$this->_listWhere = $where;
				}elseif( $status==6){ //超期未归还
						$where['t1.Ostatus'] = 7;
						$this->_listWhere = $where;
				}elseif( $status==7){ //超期归还故障
						$where['t1.Ostatus'] = 8;
						$this->_listWhere = $where;
				}elseif( $status==8){ //已提货
						$where['t1.Ostatus'] = 3;
						$this->_listWhere = $where;
				}elseif( $status==9){ //已归还
						$where['t1.Ostatus'] = 5;
						$this->_listWhere = $where;
				}elseif( $status==10){ //超期归还
						$where['t1.Ostatus'] = 6;
						$this->_listWhere = $where;
				}elseif( $status==11){ //订单完成
						$where['t1.Status'] = 5;
						$this->_listWhere = $where;
				}elseif( $status==12){ //已取消
						$where['t1.Status'] = 2;
						$this->_listWhere = $where;
				}elseif( $status==13){ //需发货
						$where['t1.Ostatus'] = array('in',array(1,2));
						$where['t1.Status']  = 1;
						$where['t1.Way']     = 1;
						$where[0]= "t12.Ostatus is NULL";
						$this->_listWhere = $where;
						//$this->_listDebug = true;
				}elseif( $status==14 ){//需确认收货
						$where['t1.Ostatus'] = array('in',array(1,2));
						$where['t1.Status']  = 1;
						$where['t1.Way']     = 1;
						$where['t12.Ostatus']= array('egt',1);
						$this->_listWhere = $where;
				}
		}
		private function keyfieldQuery(){
				$query = Q('query');
				$keyField  = isset( $query['keyfield'] )? trim($query['keyfield'] )  : false;
				$keyword   = isset( $query['keyword'] ) ? trim($query['keyword']  )  : false;
				if( !empty( $keyword )){
					if( $keyField =='mobile' ){
						$where = $this->_listWhere;
						$where['t2.Mobile'] = array('like',$keyword.'%');
						$this->_listWhere   = $where;
					}elseif( $keyField=='device'){
						$where = $this->_listWhere;
						$where['t7.Barcode'] = array('like',$keyword.'%');
						$this->_listWhere   = $where;	
					}elseif( $keyField=='orderSn'){
						$where = $this->_listWhere;
						$where['t1.OrderSn'] = array('like',$keyword.'%');
						$this->_listWhere   = $where;
					}
				}
		}
		private function timefieldQuery(){
				$query = Q('query');
				$timefield = isset( $query['timefield'] )  ? $query['timefield'] : false;
				$stime     = isset( $query['querystime'] ) ? $query['querystime']  : false;
				$etime     = isset( $query['queryetime'] ) ? $query['queryetime']  : false;
				$stimeStamp = false;
				if( !empty( $stime )){
						$stimeStamp = strtotime( $stime );
						$stimeStamp = $stimeStamp ? $stimeStamp : $stime;
				}
				$etimeStamp = false;
				if( !empty( $etime )){
						$etimeStamp = strtotime( $etime );
						$etimeStamp = $etimeStamp ? $etimeStamp : $etime;
						$etimeStamp = $etimeStamp + (3600*24) -1;
						$etimeStamp = (!empty($stimeStamp) && $etimeStamp < $stimeStamp) ? $stimeStamp : $etimeStamp;
				}
				$whereTime = false;
				if( !empty( $stimeStamp ) && !empty( $etimeStamp ) ){
						$whereTime = array('between',array($stimeStamp,$etimeStamp));
				}elseif(!empty( $stimeStamp )){
						$whereTime = array('egt',$stimeStamp);
				}elseif(!empty($etimeStamp )){
						$whereTime = array('elt',$etimeStamp);
				}
				if( !empty( $whereTime )){
						if($timefield =='deliveryTime'){
								$where = $this->_listWhere;
								$where['t1.DeliveryTime'] = $whereTime;
								$this->_listWhere   = $where;	
						}elseif( $timefield =='rationStartTime'){
								$where = $this->_listWhere;
								$where['t1.RationStartTime'] = $whereTime;
								$this->_listWhere   = $where;
						}elseif( $timefield =='rationEndTime'){
								$where = $this->_listWhere;
								$where['t1.RationEndTime'] = $whereTime;
								$this->_listWhere   = $where;
						}elseif( $timefield =='ctime'){
								$where = $this->_listWhere;
								$where['t1.Ctime'] = $whereTime;
								$this->_listWhere  = $where;
						}
				}
		}
		public function orderlists(){
				/*查询条件*/
				$this->searchstatusQuery();
				$this->keyfieldQuery();
				$this->timefieldQuery(); //$this->_listDebug = true;
				$res = $this->lists();
				/*系统状态处理*/
				$lists = isset( $res['lists'] ) ? $res['lists'] : false;
				if( !empty( $lists )){
						$status2 = array(); //未提货【系统提醒】(提货时间到了) 订单设备状态未提货，设备状态正常
						$status4 = array(); //超期未归还 订单设备状态超期未归还, 修改设备状态为占用
						$timer   = time();
						foreach( $lists as $key => $val ){
								if( $val['ostatus']==1 && $val['rationStartTime']<=$timer ){
										$status2[] = $val['orderSn'];
								}
								if( $val['ostatus']==3 && $val['rationEndTime']<=$timer && $val['status']==1 ){
										$status4[] = $val['orderSn'];
								}
								$itemData = explode('&&&&',$rows['item_data']); 
			   					foreach( $itemData as $key => $item_val ){
			   						$item_val   = str_replace( array("\t","\r","\n"),'',trim($item_val) ); 
			   						$item = json_decode( $item_val,true);
						        	if(empty($item))
						        				continue;
			   					}	  	
						}
						$this->updateOStatus($status2,2);
						$this->updateOStatus($status4,4);
						if( !empty( $status2 ) || !empty( $status4 ) ){
								$res = $this->lists();
								return $res;
						}
				}
				return $res;
		}
		/*
		 * 根据订单uuids更新状态 
		 * 2未提货【系统提醒】
		 * 5超期未归还【系统提醒】 并将设备置为占用
		 */
		private function updateOStatus( $uuids, $ostatus ){
				if(empty( $uuids ) || !is_array($uuids ))
						return false;
				if($ostatus!=2 && $ostatus!=4)
						return false;
				$where['orderSn'] = array('in',$uuids );
				$data['ostatus']  = $ostatus;
				$this->_saveWhere = $where;
				$this->_saveData  = $data;
				//$this->_saveDebug = true;
				$res = parent::save();
				if( $res['status'] ==1 ){
						$opRes = C('orderProduct','updateOStatus',$uuids,$ostatus);
						/*超期未归还,设备为占用*/
						C('device','deviceStatusByOrder',$orderSn);
						return $opRes;
				}else{
						return $res;
				}
		}

		public function find(){
				$orderSn = Q('ordersn');
				$orderSn1= Q('orderSn');
				if(empty( $orderSn)){
						$orderSn = $orderSn1;
				}
				if(empty( $orderSn )){
						return array('status'=>0,'msg'=>'订单编号异常');
				}
				$where['t1.OrderSn'] = $orderSn;
				$this->_listPage = 1;
				$this->_listLimit= 1;
				$this->_listWhere= $where;
				//$this->_listDebug =true;
				$res = $this->lists();
				$status = 0;
				$data = isset( $res['lists'][0] ) ? $res['lists'][0] : false;
				if( !empty( $data )){
						$status = 1;
				}
				return array('status'=>$status,'data'=>$data);
		}

		public function cancelOrder(){
				$where  	= Q('where');
				$orderSn    = isset( $where['ordersn'] ) ? $where['ordersn'] : false;
				if(empty( $orderSn )){
						return array('status'=>0,'msg'=>'订单异常,请重试');
				}
				$orderWhere['orderSn']  = $orderSn;
				$orderWhere['ostatus']   = array('in',array('1','2')); //设备状态,已分配，未提货才可取消
				$orderData = array(
						'status' 		=> 2,
				);
				$this->_saveData  = $orderData;
				$this->_saveWhere = $orderWhere;
				//$this->_saveDebug = true;
				$statusRes = parent::save();
				/*订单设备状态 end*/
				if( $statusRes['status'] ==1 ){
						$opRes = C('orderProduct','cancelOrderProduct',$orderSn);
						$title = "取消订单";
						$logRes = C('orderLog','addLog',$orderSn,1,'order','cancelOrder',$title,'','',2,'','');
						return $opRes;
				}else{
						return $statusRes;
				}
		}
		public function completeOrder(){
				$where  	= Q('where');
				$orderSn    = isset( $where['ordersn'] ) ? $where['ordersn'] : false;
				if(empty( $orderSn )){
						return array('status'=>0,'msg'=>'订单异常,请重试');
				}
				$orderWhere['orderSn']  = $orderSn;
				$orderData = array(
						'status' 		=> 5,
				);
				$this->_saveData  = $orderData;
				$this->_saveWhere = $orderWhere;
				//$this->_saveDebug = true;
				$statusRes = parent::save();
				if( $statusRes['status'] ==1 ){
						$title = "订单完成";
						$logRes = C('orderLog','addLog',$orderSn,1,'order','completeOrder',$title,'','',5,'','');
				}
				return $statusRes;
		}

		/*
		 * 调换设备  已归还,未归还 设备不能调配
		 */
		public function saveOrderProduct(){
				$where  	  = Q('where');
				$orderProduct = Q('orderproduct');
				$exchange     = Q('exchange');
				$orderSn      = isset( $where['ordersn'] ) ? $where['ordersn'] : false;
				if(empty( $orderSn )){
						return array('status'=>0,'msg'=>'订单异常,请重试');
				}
				if(empty( $orderProduct )){
						return array('status'=>0,'msg'=>'请选择需要替换的订单设备');
				}
				if(empty( $exchange )){
						return array('status'=>0,'msg'=>'请选择要调换的设备');
				}
				if(count($orderProduct)!=count($exchange)){
						return array('status'=>0,'msg'=>'订单设备与调换设备数量不一致.');
				}
				$orderProduct 	 = array_values($orderProduct);
				$orderProducts   = C('orderProduct','listsByUuids',$orderSn,$orderProduct); //订单设备
				$exchange        = array_values($exchange);
				$exchangeDevices = C('device','listsByUuids',$exchange); //调换设备
				if(empty( $orderProducts ) || empty( $exchangeDevices ) || count($orderProducts)!=count($exchangeDevices)){
						return array('status'=>0,'msg'=>'订单设备数据异常,请刷新重试');
				}
				$oTotal = 0;  //总金额
				$eTotal = 0;  //调换设备总额
				$data 	    = array();
				$timer 	    = time();
				foreach( $orderProducts as $okey => &$oval ){
						$exchangeData = $exchangeDevices[$okey];
						if(!isset( $exchangeData['uuid'] )) return array('status'=>0,'msg'=>'调换设备数据异常,请重试');
						if( $oval['ostatus'] !=1 && $oval['ostatus'] !=2 ){
								    $ostatusMsg = $this->deviceStatus( $oval['ostatus'] );
									return array('status'=>0,'msg'=>$ostatusMsg.'订单设备不能调换');
						}
						$days  			   = ceil( ($oval['rationEndTime'] - $oval['rationStartTime'] )/3600/24 );
						$oTotal			  += (int)$oval['price'] * $days;
						$eTotal 	      += (int)$exchangeData['price'] * $days;
						$oval['warehouse'] = $exchangeData['warehouse'];
						$oval['classify']  = $exchangeData['classify'];
						$oval['device']    = $exchangeData['uuid'];
						$oval['name']      = $exchangeData['name'];
						$oval['price']     = $exchangeData['price'];
						$oval['ostatus']   = 1;
						$oval['status']    = 1;
						$oval['ctime']     = $timer;
						$oval['mtime']     = $timer;		
				}
				$diffTotal = $eTotal - $oTotal; //调换差额
				/*订单信息*/
				Q('orderSn',$orderSn);
				$infoRes 	=  C('order','find');
				$info 	 	=  isset( $infoRes['data'] ) ? $infoRes['data'] : false; 
				if(empty( $info )) return array('status'=>0,'msg'=>'订单信息异常,请重试');
				/**/
				if( $info['status'] == 5){
						return array('status'=>0,'msg'=>'订单已完成不能调配');
				}
				$orderPrice =  (int)$info['originPrice'] + $diffTotal;
				$updateRes = C('orderProduct','disableByUuids',$orderSn,$orderProduct);//更新订单设备无效状态 
				if($updateRes['status'] ==1 ){
						$addOrderProduct = C('orderProduct','addOrderProduct',$orderProducts);
						if($addOrderProduct['status']==1){
							//更新订单金额
							$this->updateAmount( $orderSn,$orderPrice );
							$title="设备调换";
						$logRes = C('orderLog','addLog',$info['orderSn'],1,'order','saveOrderProduct',$title,'','',$info['status'],'','');
						}
						return $addOrderProduct;
				}else{
						return $updateRes;
				}
				
		}
		private function updateAmount( $orderSn,$devicePrice ){
				if(empty( $orderSn )) return array('status'=>0,'msg'=>'订单异常');
				$devicePrice = (int)$devicePrice;
				$where['orderSn'] = $orderSn;
				$orderData = array(
						'originPrice' 		=> $devicePrice,
						'salePrice' 		=> $devicePrice,
						'amountPayable' 	=> $devicePrice,
				);
				$this->_saveData  = $orderData;
				$this->_saveWhere = $where;
				//$this->_saveDebug = true;
				return parent::save();
		}
		/*
		 *   订单补货并分配
		 */
		public function addDevice(){
				$where = Q('where'); 
				$data  = Q('data');
				$orderSn = isset( $where['ordersn'] ) ? $where['ordersn'] : false;
				if(empty( $orderSn )){
						return array('status'=>0,'msg'=>'补货订单异常');
				}
				Q('ordersn',$orderSn);
				$orderInfo = $this->find();
				$info = isset( $orderInfo['data'] ) ? $orderInfo['data'] : false;
				if( empty( $info )){
						return array('status'=>0,'msg'=>'订单异常!');
				}
				$num   = $info['num'];   //下单数量
				$opNum = $info['opNum']; //订单产品数量
				if( $opNum >= $num ){    //不需要补仓
						return array('status'=>0,'msg'=>'该订单不需要补仓');
				}
				$timer = time();
				$data['classify'] = $info['classify'];
				$data['warehouse']= $info['warehouse'];
				$data['ctime']    = $timer;
				$data['mtime']    = $timer;
				$data['status']   = 1;
				Q('data',$data);
				$addRes = C('device','add');    //添加设备入库
				if( $addRes['status'] == -3 ){
						$addRes['status'] = 0;
						return $addRes;
				}
				if( $addRes['status'] ==1 ){ //设备添加成功
						$id = $addRes['data'];
						$deviceInfo = C('device','findById',$id );
						if(empty($deviceInfo)){
								return array('status'=>0,'msg'=>'出错,订单设备异常!');
						}
						$pretime   = $info['pretime'];
						$backtime  = $info['backtime'];
						$startTime = $info['rationStartTime'] - $pretime;  //[00:00]
						$endTime   = $info['rationEndTime']	  + $backtime; //[23:59]
						$days  = ceil( ($orderProduct['rationEndTime'] - $orderProduct['rationStartTime'] )/3600/24 );
						$opData = array(
							'orderSn'	     => $orderSn,
							'warehouse' 	 => $info['warehouse'],
							'classify'  	 => $info['classify'],
							'device'    	 => $deviceInfo['uuid'],
							'name'      	 => $deviceInfo['name'],
							'rationStartTime'=> $info['rationStartTime'],
							'rationEndTime'  => $info['rationEndTime'],
							'pretime'		 => $info['pretime'],
							'backtime'		 => $info['backtime'],
							'deliveryTime'	 => $info['deliveryTime'],
							'startTime'		 => $startTime,
							'endTime'		 => $endTime,
							'price'			 => $deviceInfo['price'],
							'ostatus'		 => 1,
							'status'		 => 1,
							'ctime'		     => $timer,
							'mtime'			 => $mtimer,
						);
						$opRes = C('orderProduct','addOrderProduct',$opData);
						/*更新订单金额*/
						$days  		= ceil( ( $info['rationEndTime'] - $info['rationStartTime'] )/3600/24 );
						$totalPrice = intval( $info['originPrice'] ) + intval( $deviceInfo['price'] ) * $days;
						if( $opRes['status'] !=1 ){
								return $opRes;
						}
						$orderAmountRes = $this->updateAmount( $orderSn,$totalPrice );
						return $orderAmountRes;
				}else{
						return array('status'=>0,'msg'=>'设备添加失败');
				}
		}

		/*
		 * 更新订单状态
		 * 修改:提前归还，要更新回库占时
		 */
		public function saveStatus( $ostatus=false ){
				$ostatusVal =  $this->deviceStatus( $ostatus );
				if(empty( $ostatusVal )){
						return array('status'=>0,'msg'=>'更新状态异常');
				}
				$where = Q('where'); 
				$data  = Q('data');
				$orderSn = isset( $where['ordersn'] ) ? $where['ordersn'] : false;
				if(empty( $orderSn )){
						return array('status'=>0,'msg'=>'订单异常');
				}
				Q('ordersn',$orderSn);
				$orderInfo = $this->find();
				$info = isset( $orderInfo['data'] ) ? $orderInfo['data'] : false;
				if( empty( $info )){
						return array('status'=>0,'msg'=>'订单编号异常!');
				}

				$checkRes = $this->saveStatusCheck( $ostatus,$info );
				if( $checkRes['status'] == 0 ){
						return $checkRes;
				}
				$way = $info['way']; //提货方式
				$tracking='';
				$xstatus = false;
				if( $way==1 ){
					$tracking = isset( $data['tracking'] ) ? trim( $data['tracking'] ) : ''; //发货时,运单号
					$xstatus = isset( $data['xstatus'] ) ? $data['xstatus'] : false;
					if(empty( $tracking ) ){
							return array('status'=>0,'msg'=>'请输入运单号');
					}
				}

				$time  = isset( $data['time'] ) ? $data['time'] : '';
				$desc  = isset( $data['desc'] ) ? $data['desc'] : '';
				if(!empty( $desc ) && strlen($desc) > 120 ){
						return array('status'=>0,'msg'=>'备注信息最多120字');
				}
				Q('orderSn',$orderSn);
				$orderProduct = C('orderProduct','lists'); //订单产品
				if(empty( $orderProduct)){
						return array('status'=>0,'msg'=>'订单设备异常');
				}

				/*3:已发货 5:已归还 6:超期归还*/
				$backTime = false;
				if( $ostatus==5 || $ostatus==6 ){
					$deviceStatus = isset( $data['status'] ) ? $data['status'] : false; //归还的设备状态
					$statusRs = $this->givebackStatus( $deviceStatus );
					if(empty( $statusRs ) || is_array( $statusRs )){
							return array('status'=>0,'msg'=>'无效设备状态,请确认设备状态');
					}
					if( ($way==1&&$xstatus==1) || $way==0 ){ //确认状态,提前归还，更新回库占时
							$nowDayTime    = strtotime(date('Y-m-d',time()));
							$rationEndTime = $info['rationEndTime'];
							$factbackTime  = $nowDayTime-$rationEndTime; //实际归还时间
							if($factbackTime<0){ //提前确认归还
									$backTime = 0;
							}else{ //超过归还时间,计算超过多少天(整天计算)
									$backDay  = ceil( $factbackTime/(3600*24));
									$backTime = $backDay * 3600*24;
							}
					}
					if( $ostatus==5 ){  //归还
							if($deviceStatus==3){ //归还故障
									$ostatus  = 7; 
							}else{ //正常归还,设备没有问题 (设备状态是否还原?)

							}
					}elseif( $ostatus==6 ){  //超期归还
							if($deviceStatus==3){ //归还故障
								$ostatus  = 8;
							}else{ //正常归还,设备没有问题 还原设备状态
							
							}
					}		
				}
				/*修改订单状态 xstatus分支确认状态,有且确认状态及数据更新 否则只是日志记录 */
				if( $xstatus===false || $xstatus==1 ){
						$orderWhere['orderSn'] = $orderSn;
						$orderData = array(
								'ostatus' 		=> $ostatus,  //订单设备状态
						);
						if( $backTime !== false ){
								$orderData['backtime'] = $backTime;
						}
						$this->_saveData  = $orderData;
						$this->_saveWhere = $orderWhere;
						//$this->_saveDebug = true;
						$statusRes = parent::save();
						if( $statusRes['status']==1){  
								//订单更新成功,更新订单产品
								$upOrderProduct = array();
								if( $backTime !==false ){  //订单产品库存占时更新
										$endTime   = $info['rationEndTime'] + $backTime; 
										$upOrderProduct = array(
												'backtime' =>  $backTime,
												'endTime'  =>  $endTime,
										);
								}
								$upOrderProduct['ostatus'] = $ostatus;
								$opRes = C('orderProduct','updateOrderProduct',$orderSn,$upOrderProduct);
								//更新故障设备
								if( $ostatus==7 || $ostatus==8 ){
										$opUuids = array();
										foreach( $orderProduct as $key => $val ){
												$opUuids[] = $val['device'];
										}
										$deviceRes = C('device','statusByUuids',$opUuids,3); //设备维修状态	
								}
						}		
				}else{
						$statusRes = array('status'=>1,'msg'=>'成功');
				}
				$statusRes['orderSn'] = $orderSn;
				$statusRes['info'] 	  = $info;
				$statusRes['orderProduct'] = $orderProduct;
				$statusRes['time'] = $time;
				$statusRes['desc'] = $desc;
				$statusRes['tracking'] = $tracking;
				$statusRes['ostatus']  = $ostatus;
				return $statusRes;
		}
		/*
		 * 刷新补货
		 */
		public function fillupRefresh(){
				$orderSn = Q('ordersn');
				$orderInfo = $this->find();
				$info = isset( $orderInfo['data'] ) ? $orderInfo['data'] : false;
				if( empty( $info )){
						return array('status'=>0,'msg'=>'订单编号异常!');
				}
				/*是否需要补仓*/
				$diffNum = $info['opNum']-$info['num'];
				if( $diffNum >= 0 ) return array('status'=>0,'msg'=>'该订单不需要补仓');
				if( $info['status'] != 1 ) return array('status'=>0,'msg'=>'该订单状态不需要补仓');
				/*开始自动补仓*/
				//1,获取仓库存
				Q('classify',$info['classify']);
				Q('warehouse',$info['warehouse']);
				Q('deviceNum', abs( $diffNum ) );
				Q('rationStartTime',date('Y-m-d',$info['rationStartTime']) );
				Q('rationEndTime',date('Y-m-d',$info['rationEndTime']) );
				Q('beginPreday',$info['pretime']/3600/24  );
				Q('endPreday',$info['backtime']/3600/24);
				$warehouseStock = C('orderProduct','productTimes');
				//adjustProduct
				$adjustnum = array();
				if( !empty( $warehouseStock ) ){
						foreach( $warehouseStock as $key => $val ){
								if( $val['adjustNum'] > 0){
										$adjustnum[$val['uuid']] = $val['adjustNum'];
								}
						}
				}
				Q('adjustnum',$adjustnum);
				$startTime = $info['rationStartTime'] - $info['pretime'];  //[00:00]
				$endTime   = $info['rationEndTime']	  + $info['backtime']; //[23:59]
				$adjustRes = $this->adjustProduct($info['classify'],$startTime,$endTime,abs( $diffNum ));
				if( $adjustRes['status'] == 0){
						return $adjustRes;
				}
				/*订单产品通用数据*/
				$orderProduct = array(
						'orderSn'         => $orderSn,
						'rationStartTime' => $info['rationStartTime'],
						'rationEndTime'	  => $info['rationEndTime'],
						'deliveryTime'	  => $info['deliveryTime'],
						'pretime'	      => $info['pretime'],
						'backtime'        => $info['backtime'],
						'startTime'		  => $startTime,
						'endTime'		  => $endTime
				);
				$adjustData = isset( $adjustRes['data'] ) ? $adjustRes['data'] : false;
				$assemblyProduct = $this->assemblyProduct( $adjustData, $orderProduct );
				if($assemblyProduct['status']!=1){
						return $assemblyProduct;
				}
				$orderPrice = $assemblyProduct['price'];
				$orderProducts = $assemblyProduct['data'];
				$opRes = C('orderProduct','addOrderProduct',$orderProducts);
				/*更新订单金额*/
				$totalPrice = intval( $info['originPrice'] ) + intval( $orderPrice );
				if( $opRes['status'] !=1 ){
						return $opRes;
				}
				$orderAmountRes = $this->updateAmount( $orderSn,$totalPrice );
				return $orderAmountRes;
		}
		/* 
		 *订单流程条件验证
		 * $status  订单设备状态
		 * $info    订单详情信息
		*/
		public function saveStatusCheck( $status,$info ){
				if( empty( $info )){
						return array('status'=>0,'msg'=>'订单信息异常!');
				}
				if( $status==3 ){
					$diffNum = $info['opNum']-$info['num'];
					if( $info['state11']>0 || $info['state12']>0 ){
						return array('status'=>0,'msg'=>'还不能提货,请先确认调仓设备');
					}
					if( $diffNum<0 ){
						return array('status'=>0,'msg'=>'还不能提货,请先确认需要补仓设备');	
					}
				}
				return array('status'=>1);
		}
		/*
		 *  提货
		 */
		public function pickupStatus(){
				$statusRes = $this->saveStatus(3); //已提货或发货
				/*订单设备状态 end*/
				if( $statusRes['status'] ==1 ){
							$info     = $statusRes['info'];
							$title    = "确认提货";
							$logKey   = "";
							$logValue = "";
							if( $info['way']==1 ){
									$data     = Q('data');
									$xstatus  = isset( $data['xstatus'] ) ? $data['xstatus'] : false;
									$logValue = $statusRes['tracking'];
									$logKey   = !empty( $logValue ) ? "运单号" : '';
									if( $xstatus == 1 ){
											$title = "确认货到";
									}else{
											$title = "发货";
									}
							}
							//日志记录
							$logRes = C('orderLog','addLog',$statusRes['orderSn'],1,'order','pickupStatus',$title,$statusRes['desc'],$statusRes['time'],$info['ostatus'],$logKey,$logValue);

						return $statusRes;
				}else{
						return $statusRes;
				}
		}
		/*
		 * 归还设备
		 * 
		 */
		public function givebackOrder(){
				$statusRes = $this->saveStatus(5); //已归还
				if( $statusRes['status'] ==1 ){
							/*快递发货日志记录*/
							$info  = $statusRes['info'];
							$title = "确认归还";
							$logKey = "";
							$logValue= "";
							if( $info['way']==1 ){
									$data  = Q('data');
									$xstatus = isset( $data['xstatus'] ) ? $data['xstatus'] : false;
									$logValue = $statusRes['tracking'];
									$logKey = !empty( $logValue ) ? "运单号" : '';
									if( $xstatus==1 ){
											$title = "确认归还";
									}else{
											$title = "发货归还";
									}
							}
							if( $statusRes['ostatus']==7 || $statusRes['ostatus']==8 ){
									$title .= "--故障";
							}
							/*end*/
							//日志记录
							$logRes = C('orderLog','addLog',$statusRes['orderSn'],1,'order','givebackOrder',$title,$statusRes['desc'],$statusRes['time'],$info['ostatus'],$logKey,$logValue);
						return $statusRes;
				}else{
						return $statusRes;
				}
		}
		/*
		 * 超期归还设备
		 */
		public function givebackOutOrder(){
				$statusRes = $this->saveStatus(6); //已归还
				if( $statusRes['status'] ==1 ){
						/*快递发货日志记录*/
						$info  = $statusRes['info'];
						$title = "确认超期归还";
						$logKey = "";
						$logValue= "";
						if( $info['way']==1 ){
								$data  = Q('data');
								$xstatus = isset( $data['xstatus'] ) ? $data['xstatus'] : false;
								$logValue = $statusRes['tracking'];
								$logKey = !empty( $logValue ) ? "运单号" : '';
								if( $xstatus==1 ){
										$title = "确认超期归还";
								}else{
										$title = "发货超期归还";
								}
						}
						if( $statusRes['ostatus']==7 || $statusRes['ostatus']==8 ){
									$title .= "--故障";
						}
						/*end*/
						//日志记录
						$logRes = C('orderLog','addLog',$statusRes['orderSn'],1,'order','givebackOutOrder',$title,$statusRes['desc'],$statusRes['time'],$info['ostatus'],$logKey,$logValue);
						return $statusRes;

				}else{
						return $statusRes;
				}
		}

		/*生成订单编号*/
		private function orderSn(){
				date_default_timezone_set("PRC");
				$startTime = strtotime($this->startTime); 
				$diffTime  = time()-$startTime; 
				$diffDay   = ceil($diffTime/3600/24);
				$nowTime   = time() - strtotime( date('Y-m-d'),time() );
				$microtime = substr( microtime(),2,2 );
				$randStart = sprintf("%01d",mt_rand(1,9));
				$randEnd   = sprintf("%01d",mt_rand(0,9));
				$sn 	   = '2'.$randStart.$diffDay.$nowTime.$microtime.$randEnd; 
				return strlen($sn)>10 ? $sn : false;
		}

		protected function regxTable(){ 
				$reOrder   = M('Regx','reString',8,16);
				$reUuid    = M('Regx','reStringAll',36);
	    		$reAmount  = M('Regx','reNumRange',1,10);
	    		$reTime    = M('Regx','reNumRange',10,11);
	    		$reTime1   = M('Regx','reNumRange',1,11);
	    		$reStatus  = M('Regx','reNumRange',0,1);
	    		$reNum     = M('Regx','reNumRange',1,6);
	    		$regxTable = array(
		           'Id'     		=> parent::regxField('id','ID',1,0,0,false,false),
		           'OrderSn'   		=> parent::regxField('orderSn','订单编号',0,1,1,$reOrder['re'],'订单编号:'.$reOrder['msg']),
		           'UserUuid'   	=> parent::regxField('userUuid','会员编号',0,0,0,$reUuid['re'],'会员编号:'.$reUuid['msg']),
		           'Warehouse'   	=> parent::regxField('warehouse','仓库编号',0,1,1,$reUuid['re'],'仓库编号:'.$reUuid['msg']),
		           'Classify'   	=> parent::regxField('classify','类目编号',0,1,1,$reUuid['re'],'类目编号:'.$reUuid['msg']),
		           'Num' 		    => parent::regxField('num','设备数量',0,1,1,$reNum['re'],'设备数量:'.$reNum['msg']),
		           'Way' 			=> parent::regxField('way','提货方式',0,0,0,$reStatus['re'],'提货方式:'.$reStatus['msg']),
		           'RationStartTime'=> parent::regxField('rationStartTime','租期开始时间',0,1,1,$reTime['re'],'租期开始时间:'.$reTime['msg']),
		           'RationEndTime' 	=> parent::regxField('rationEndTime','租期结束时间',0,1,1,$reTime['re'],'租期结束时间:'.$reTime['msg']),
		           'Pretime' 		=> parent::regxField('pretime','发货占时',0,0,0,$reTime1['re'],'发货占时:'.$reTime1['msg']),
		           'Backtime' 		=> parent::regxField('backtime','回库占时',0,0,0,$reTime1['re'],'回库占时:'.$reTime1['msg']),
		           'DeliveryTime' 	=> parent::regxField('deliveryTime','提货时间',0,1,1,$reTime['re'],'提货时间:'.$reTime['msg']),

		           'OriginPrice'   	=> parent::regxField('originPrice','订单金额',0,0,0,$reAmount['re'],'订单金额:'.$reAmount['msg']),
		           'MarketingSale'  => parent::regxField('marketingSale','营销减价',0,0,0,$reAmount['re'],'营销减价:'.$reAmount['msg']),
		           'SalePrice'   	=> parent::regxField('salePrice','销售金额',0,0,0,$reAmount['re'],'销售金额:'.$reAmount['msg']),
		           'AmountDiscount' => parent::regxField('amountDiscount','抵扣金额',0,0,0,$reAmount['re'],'抵扣金额:'.$reAmount['msg']),
		           'AmountPayable'  => parent::regxField('amountPayable','应付金额',0,0,0,$reAmount['re'],'应付金额:'.$reAmount['msg']),
		           'OnlinePayment'  => parent::regxField('onlinePayment','线上支付',0,0,0,$reAmount['re'],'线上支付:'.$reAmount['msg']),
		           'OfflinePayment' => parent::regxField('offlinePayment','线下支付',0,0,0,$reAmount['re'],'线下支付:'.$reAmount['msg']),
		           'PayTime' 		=> parent::regxField('payTime','付款时间',0,0,0,$reTime['re'],'付款时间:'.$reTime['msg']),
		           'PayStatus' 		=> parent::regxField('payStatus','付款状态',0,0,0,$reStatus['re'],'付款状态:'.$reStatus['msg']),
		           'Ostatus' 		=> parent::regxField('ostatus','订单状态',0,0,0,$reStatus['re'],'订单状态:'.$reStatus['msg']),
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
					'Id'             => "int(8) unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT",
            		'OrderSn'        => "varchar(16) not null comment '订单编号' ",
            		'UserUuid'       => "varchar(36) default '' comment '会员编号' ",
            		'Warehouse'      => "char(36) not null comment '仓库编号' ",
            		'Classify'       => "char(36) not null comment '类目编号' ", 
            		'Num'			 => "smallint(6) not null comment '设备数量' ",
            		'Way'			 => "tinyint(2) default '0' comment '提货方式0自提1快递'",
            		'RationStartTime'=> "int(11) unsigned not null comment '租期开始时间' ",
            		'RationEndTime'  => "int(11) unsigned not null comment '租期结束时间' ",
            		'Pretime'   	 => "int(11) unsigned default '0' comment '发货占时天转化为秒' ",
            		'Backtime'     	 => "int(11) unsigned default '0' comment '回库占时天转化为秒' ",
            		'DeliveryTime'   => "int(11) unsigned not null comment '提货时间秒' ",

            		'OriginPrice'    => "int(10) unsigned default '0' comment '订单金额(原始订单产品金额)' ",
            		'MarketingSale'  => "int(10) unsigned default '0' comment '营销减价金额(前置类,以订单金额为抵扣基础)' ",
            		'SalePrice'      => "int(10) unsigned default '0' comment '销售金额(营销减价后金额)' ",
            		'AmountDiscount' => "int(10) unsigned default '0' comment '优惠抵扣金额(后置类,以销售金额为抵扣基础)' ",
            		'AmountPayable'  => "int(10) unsigned default '0' comment '应付金额' ",
            		'OnlinePayment'  => "int(10) unsigned default '0' comment '线上支付金额' ",
            		'OfflinePayment' => "int(10) unsigned default '0' comment '线下支付金额' ",
            		'PayTime'		 => "int(11) unsigned DEFAULT '0' comment '付款状态' ",
            		'PayStatus'		 => "tinyint(2) default '0' comment '付款时间' ",
		            'Ostatus'   	 => "tinyint(2) default '0' comment '设备状态,1已分配2未提货3已提货4超期未归还5已归还6超期归还' ",
		            'Status'		 => "tinyint(2) default '0' comment '1正常 0无效 2取消' ",
		            'Ctime' 		 => "int(11) unsigned DEFAULT '0'",
		            'Mtime' 		 => "int(11) unsigned default '0'",
		            'AccountUuid'    => "char(36) not null comment '操作人员' ",
		        );
		        return $field;
	    }
	    private function tableIndex()
	    {
	        $index = array(
	            'index_osn'   => array('OrderSn'),
	            'index_uu'	=>	array('UserUuid'),
	            'index_uc'	=>	array('Classify'),
	            'index_uw'	=>	array('Warehouse'),
	            'index_op'	=>	array('OriginPrice'),
	            'index_ms'  =>  array('MarketingSale'),
	            'index_sp'  =>  array('SalePrice'),
	            'index_ad'	=>  array('AmountDiscount'),
	            'index_ap'	=>  array('AmountPayable'),
	            'index_op'	=>	array('OnlinePayment'),
	            'index_ofp' =>	array('OfflinePayment'),
	            'index_ctime'=> array('Ctime'),
	        );
	        return $index;
	    }
	    private function tableUnique()
	    {
	        $unique = array(
	            'unique_uu'    => array('OrderSn'),
	        );
	        return $unique;
	    }
	    public function createTable()
	    {
	        return parent::createTable('订单信息');
	    }


}