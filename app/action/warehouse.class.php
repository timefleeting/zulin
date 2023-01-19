<?php

namespace action;

class warehouse extends core{

		protected function _init(){
		    		$this->_table 	    = $this->warehouse;
		        	$this->_tableField 	= $this->tableField();
	        		$this->_tableIndex  = $this->tableIndex();
	        		$this->_tableUnique = $this->tableUnique();
	        		$this->_listQuery   = true;
	        		$this->_listOrder   = "Sort asc,Id desc";
		}
		public function findInfo( $uuid ){
					if(empty( $uuid )) return false;
					Q('uuid',$uuid);
					$res = parent::find();
					return isset( $res['data'] ) ? $res['data'] : false;
		}
		/*
		 * 获取主仓类型设备库存
		 */
		public function warehouseNum($warehouse,$classify){
				    $where['t1.Uuid']      = $warehouse;
				    $where['t1.Status']    = 1; //主仓必为有效仓库
				 	$this->_aliasTable= 't1';
				 	$this->_listField = "count(t2.Id) num,t1.Name as name,t1.Uuid as uuid";
					$this->_listJoin  = "left join {$this->device} t2 on t1.Uuid=t2.Warehouse and t2.Classify='{$classify}' and t2.Status=1 "; //
					$this->_listWhere = $where;
					$this->_listGroup = "t1.Uuid";
					//$this->_listDebug = true;
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
					$field 	 = "count(distinct t3.Uuid) as num,t1.Name as name,t1.Uuid as uuid";
					$join[]  = "inner join {$this->wb} t2 on t2.Buuid=t1.Uuid and t2.Uuid='{$warehouse}' and t2.Status=1"; //关联可调仓库
					$join[]  = "left join {$this->device} t3 on t3.Warehouse=t1.Uuid and t3.Classify='{$classify}' and t3.Status=1"; //有效设备
					$where['t1.Status'] = 1;	//有效仓库
					$this->_aliasTable= 't1';
					$this->_listField = $field;
					$this->_listJoin  = $join;
					$this->_listWhere = $where;
					$this->_listGroup = "t1.Uuid";
					$this->_listOrder = false;
					//$this->_listDebug = true;
					$res = parent::lists();
					$lists = isset( $res['lists'] ) ? $res['lists'] : false;
					return $lists;
		}

		/*
		 * 获取仓库未占用设备
		 * 有分页
		 *
		 */
		public function warehouseProducts($warehouse,$classify,$startTime,$endTime,$num=0){
					$field  = "t1.Uuid as warehouse,t1.Name as warehouseName,t2.Classify as classify,t2.Uuid as uuid,t2.Name as name,t2.Price as price,t2.Status as deviceStatus,t3.Id as t3id,t4.Name as targetWhName";
					$join[] = "left join {$this->device} t2 on t2.Warehouse=t1.Uuid and t2.Classify='{$classify}' and t2.Status=1"; //有效仓库设备
					$join[] = "left join {$this->op} t3 on t3.Device=t2.Uuid and t3.Status=1 and ( (t3.StartTime<={$startTime} and t3.EndTime>={$startTime}) OR (t3.StartTime<={$endTime} and t3.EndTime>={$endTime}) OR (t3.StartTime BETWEEN {$startTime} AND {$endTime}) OR (t3.EndTime BETWEEN {$startTime} AND {$endTime})  )";  //占用设备
					$join[] = "left join {$this->warehouse} t4 on t4.Uuid=t2.targetWh";
					$where['t1.Uuid']      = $warehouse;
				    $where['t1.Status']    = 1; //必为有效仓库
				    $where['t2.Status']    = 1; //必为空闲设备
				    $this->_aliasTable= 't1';
				 	$this->_listField = $field;
					$this->_listJoin  = $join; //
					$this->_listWhere = $where;
					$this->_listGroup = "t2.Uuid";
					$this->_listHaving= "t3.Id is null";
					$this->_listFieldCount ="t3.Id";
					$this->_listOrder = "(case when t2.Warehouse=t2.TargetWh then 1 else 0 end) desc";
					if(!empty( $num )){
						$this->_listPage  = 1;
						$this->_listLimit = (int)$num;
					}
					//$this->_listDebug = true;
					//$this->_listOrder = false;
					$res   = parent::lists(); 
					return $res;
		}

		/*
		 * 获取仓库未占用设备
		*/
		public function warehouseProduct($warehouse,$classify,$startTime,$endTime,$num){
					$res   =  $this->warehouseProducts($warehouse,$classify,$startTime,$endTime,$num);
					$lists = isset( $res['lists'] ) ? $res['lists'] : false;
					return $lists;
		}


		protected function listQuery(){
					$init  = Q('init');
					$query = Q('query');
					$uuid  = isset( $init['uuid'] ) ? $init['uuid'] : false;
					$status = isset( $query['status'] ) ? $query['status'] : 0; //0全部,1已选
					if( !empty( $uuid ) ){
							$keyfield = isset( $query['keyfield'] ) ? trim($query['keyfield']) : false;
						    $keyword  = isset( $query['keyword'] ) ? trim($query['keyword']) : false;
							$where    = array();   
						    $queryKey = '';
						    if($keyfield == 'name'){
						    	    $queryKey  = 't1.Name';
						    }elseif( $keyfield == 'provinceName' ){
						    	    $queryKey  = 't2.ProvinceName';
						    }elseif( $keyfield == 'cityName' ){
						    	    $queryKey  = 't2.CityName';
						    }elseif( $keyfield == 'areaName' ){
						    	    $queryKey  = 't2.AreaName';
						    }
						    if( !empty( $queryKey) && !empty( $keyword )){
						    		$where[$queryKey] = array('like',$keyword.'%'); //前面不能为%才会用到索引
						    }
						    $field  ="t1.*,t2.Status as checked";
						    if($status==1){
						    	$join[] = "inner join {$this->wb} t2 on t1.Uuid=t2.Buuid and t2.Status=1 and t2.Uuid='".$uuid."'";
						    }else{
						    	$join[] = "left join {$this->wb} t2 on t1.Uuid=t2.Buuid and t2.Status=1 and t2.Uuid='".$uuid."'";	
						    }
						    
							$this->_aliasTable = 't1';
							$this->_listField  = $field;
							$this->_listJoin   = $join;
						    $this->_listWhere  = $where;
						    $this->_listOrder  = "t1.Sort asc,t1.Id desc";

					}else{
							parent::listQuery();
					}
		}


		public function chooserList(){
					$this->_listLimit = 100;
					$where['Status']  = 1;
					$this->_listWhere = $where;
					$res = parent::lists();
					$lists = isset( $res['lists'] ) ? $res['lists'] : array();
					return $lists;
		}

		private function reqData(){
				$data = Q('data');
				$areaName = Q('areaname');
				$areaCode = Q('areacode');
				$areaName = isset( $areaName['warehouseArea'] ) ? $areaName['warehouseArea'] : false;
				$areaCode = isset( $areaCode['warehouseArea'] ) ? $areaCode['warehouseArea'] : false;
				$data['provinceName'] = !empty( $areaName[1] ) ? $areaName[1] : 0;
				$data['cityName'] 	= !empty( $areaName[2] ) ? $areaName[2] : 0;
				$data['areaName'] 	= !empty( $areaName[3] ) ? $areaName[3] : 0;
				$data['province'] 	= !empty( $areaCode[1] ) ? $areaCode[1] : 0;
				$data['city'] 		= !empty( $areaCode[2] ) ? $areaCode[2] : 0;
				$data['area'] 		= !empty( $areaCode[3] ) ? $areaCode[3] : 0; 
				$data['sort']       = !empty( $data['sort'] ) ? $data['sort'] : 0;
				return $data;
		}

		public function add(){
				$account = C('login','account');
				$accountUuid = isset( $account['uuid'] ) ? $account['uuid'] : '';
				if(empty( $accountUuid )){
						return array('status'=>0,'msg'=>'登录帐号异常');
				}
				$data = $this->reqData();
				$data['accountUuid'] = $accountUuid;
				$this->_addData = $data;
				//$this->_addDebug=true;
				return parent::add();
		}
		public function save(){
				$data = $this->reqData();
				$this->_saveData = $data;
				return parent::save();
		}
		/*
		 * 仓库库存总览
		 */
		public function stockLists($classify,$startTime,$endTime){
				/*库存设备数*/
				$stockNumLists = $this->stockNumLists( $classify ); 
				/*指定时间段占用设备库存数*/
				if( isset( $stockNumLists['lists'] ) && !empty( $stockNumLists['lists'] ) ){
						$usedStockNumLists = C('orderProduct','stockNumLists',$classify,$startTime,$endTime);
						foreach( $stockNumLists['lists'] as $key => &$val ){
								$used = 0;
								if( isset( $usedStockNumLists[$val['uuid']]['num'] )){
									$used = $usedStockNumLists[$val['uuid']]['num'];
								}
								$val['used'] = $used;
						}
				}
			return $stockNumLists;
		}
		/*
		 * 仓库库存设备数
		 */
		public function stockNumLists($classify){
				$field 	 = "count(distinct t2.Uuid) as num,t1.Name as name,t1.Uuid as uuid,t1.ProvinceName as provinceName,t1.CityName as cityName,t1.AreaName as areaName";
				$join[]  = "left join {$this->device} t2 on t2.Warehouse=t1.Uuid and t2.Classify='{$classify}' and t2.Status=1"; //有效仓库
				$where['t1.Status'] = 1;	//有效仓库
				$this->_aliasTable= 't1';
				$this->_listField = $field;
				$this->_listJoin  = $join;
				$this->_listWhere = $where;
				$this->_listGroup = "t1.Uuid";
				$this->_listOrder = false;
				//$this->_listDebug = true;
				return parent::lists();
		}

		public function regxTable(){ 
				$reUuid    = M('Regx','reStringAll',36);
				$reName    = M('Regx','reChinaAll',1,60);
				$reCode    = M('Regx','reNumRange',6,8);
				$reAddr    = M('Regx','reStringAll',3,240);
				$reMobile  = M('Regx','reMobile');
				$reEmail   = M('Regx','reEmail');
	    		$reSort    = M('Regx','reNumRange',0,5);
	    		$reTime    = M('Regx','reNumRange',10,11);
	    		$reStatus  = M('Regx','reNumRange',0,1);
	    		$regxTable = array(
		           'Id'     		=> parent::regxField('id','ID',1,0,0,false,false),
		           'Uuid'   		=> parent::regxField('uuid','编号',1,0,0,$reUuid['re'],'编号:'.$reUuid['msg']),
		           'Name'   		=> parent::regxField('name','仓库名称',0,1,1,$reName['re'],'仓库名称:'.$reName['msg']),
		           'Province'  		=> parent::regxField('province','省',0,1,1,$reCode['re'],'省:'.$reCode['msg']),
		           'City'   		=> parent::regxField('city','市',0,1,1,$reCode['re'],'市:'.$reCode['msg']),
		           'Area' 			=> parent::regxField('area','区',0,1,1,$reCode['re'],'区:'.$reCode['msg']),
		           'Address'  	    => parent::regxField('address','地址',0,0,0,$reAddr['re'],'地址:'.$reAddr['msg']),
		           'LeaderMan'  	=> parent::regxField('leaderMan','负责人',0,1,1,$reName['re'],'负责人:'.$reName['msg']),
		           'LeaderMobile'   => parent::regxField('leaderMobile','负责人手机号',0,1,1,$reMobile['re'],'负责人手机号:'.$reMobile['msg']),
		           'LeaderEmail' 	=> parent::regxField('leaderEmail','负责人电子邮箱',0,0,0,$reEmail['re'],'负责人电子邮箱:'.$reEmail['msg']),
		           'ProvinceName'  	=> parent::regxField('provinceName','省名',0,0,0,$reName['re'],'省:'.$reName['msg']),
		           'CityName'   	=> parent::regxField('cityName','市名',0,0,0,$reName['re'],'市:'.$reName['msg']),
		           'AreaName' 		=> parent::regxField('areaName','区名',0,0,0,$reName['re'],'区:'.$reName['msg']),
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
            		'Name'      => "varchar(30) not null comment '仓库名称'",
            		'Province'  => "int(8) unsigned not null comment '省'",   
		            'City'      => "int(8) unsigned not null comment '市'", 
		            'Area'      => "int(8) unsigned not null comment '区'", 
		            'Address'   => "varchar(80) default '' comment  '地址' ",
		            'LeaderMan' => "varchar(30)  not null comment  '负责人'",
		            'LeaderMobile'=>"varchar(16) not null comment   '负责人手机号'",  
		            'LeaderEmail'=> "varchar(36) default '' comment '负责人电子邮箱' ",
		            'ProvinceName'  => "varchar(16) default '' comment	'省名'",   
		            'CityName'      => "varchar(16) default '' comment  '市名'", 
		            'AreaName'      => "varchar(16) default '' comment  '区名'", 
		            'Sort'    	=> "mediumint(8) DEFAULT '0' COMMENT '排序'",
		            'Status'	=> "tinyint(2) default '0' comment '1有效 0无效'",
		            'Ctime' 	=> "int(11) unsigned DEFAULT '0'",
		            'Mtime' 	=> "int(11) unsigned default '0'",
		            'AccountUuid'=> "char(36) not null comment '操作人员' ",
		        );
		        return $field;
	    }
	    private function tableIndex()
	    {
	        $index = array(
	            'index_u'   => array('Uuid'),
	            'index_n'   => array('Name'),
	            'index_province'=> array('Province'),
	            'index_city'   => array('City'),
	            'index_area'   => array('Area'),
	            'index_c'      => array('Ctime'),
	        );
	        return $index;
	    }
	    private function tableUnique()
	    {
	        $unique = array(
	            'unique_uu'    => array('Uuid'),
	        );
	        return $unique;
	    }
	    public function createTable()
	    {
	        return parent::createTable('仓库信息');
	    }


}