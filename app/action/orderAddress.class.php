<?php

namespace action;

class orderAddress extends core{

		protected function _init(){
		    		$this->_table 	    = $this->oa;
		        	$this->_tableField 	= $this->tableField();
	        		$this->_tableIndex  = $this->tableIndex();
	        		$this->_tableUnique = $this->tableUnique();
		}
		public function addOrderAddress($data){
					$this->_addData = $data;
					return parent::add();
		}
		public function add(){}
		public function save(){}

		public function regxTable(){ 
	    		$reOrder   = M('Regx','reString',8,16);
	    		$reUuid    = M('Regx','reStringAll',36);
	    		$reMobile  = M('Regx','reMobile');
	    		$reName    = M('Regx','reChinaAll',1,60);
	    		$reCode    = M('Regx','reNumRange',6,8);
	    		$reAddr    = M('Regx','reStringAll',3,240);

	    		$reStatus  = M('Regx','reNumRange',0,1);
	    		$regxTable = array(
		           'Id'     		=> parent::regxField('id','ID',1,0,0,false,false),
		           'OrderSn'   		=> parent::regxField('orderSn','订单编号',0,1,1,$reOrder['re'],'订单编号:'.$reOrder['msg']),
		           'UserUuid'   	=> parent::regxField('userUuid','会员编号',0,0,0,$reUuid['re'],'会员编号:'.$reUuid['msg']),
		           'Mobile'   		=> parent::regxField('mobile','手机号码',0,1,1,$reMobile['re'],'手机号码:'.$reMobile['msg']),
		           'Name'   		=> parent::regxField('name','联系人',0,1,1,$reName['re'],'联系人:'.$reName['msg']),
		           'Gender'   		=> parent::regxField('gender','性别',0,0,0,$reStatus['re'],'性别:'.$reStatus['msg']),
		           'Province'  		=> parent::regxField('province','省',0,1,1,$reCode['re'],'省:'.$reCode['msg']),
		           'City'   		=> parent::regxField('city','市',0,1,1,$reCode['re'],'市:'.$reCode['msg']),
		           'Area' 			=> parent::regxField('area','区',0,1,1,$reCode['re'],'区:'.$reCode['msg']),
		           'ProvinceName'  	=> parent::regxField('provinceName','省名',0,0,0,$reName['re'],'省:'.$reName['msg']),
		           'CityName'   	=> parent::regxField('cityName','市名',0,0,0,$reName['re'],'市:'.$reName['msg']),
		           'AreaName' 		=> parent::regxField('areaName','区名',0,0,0,$reName['re'],'区:'.$reName['msg']),
		           'Address'  	    => parent::regxField('address','地址',0,0,0,$reAddr['re'],'地址:'.$reAddr['msg']),
		           
		           'Status' 		=> parent::regxField('status','状态',0,0,0,$reStatus['re'],'状态:'.$reStatus['msg']),
		           'Ctime'     		=> parent::regxField('ctime','创建时间',1,0,0,false,false),
		       );
		        return $regxTable;
	    }
	    private function tableField()
	    {
		        $field = array(
		            'Id'          => 'int(8) unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT',  
		            'OrderSn'     => "varchar(16) not null comment '订单编号' ", 
		            'UserUuid'    => "varchar(36) default '' comment '会员编号' ",   
		            'Mobile'      => "varchar(16) not null comment '手机号'",
		            'Name'        => "varchar(20) not null comment '姓名'", 
		            'Gender'      => "tinyint(2) default '0' comment '性别 0未知 1男 2女'",     
		            'Province'    => "int(8) unsigned not null comment  '省'",   
		            'City'        => "int(8) unsigned not null comment  '市'", 
		            'Area'        => "int(8) unsigned not null comment  '区'", 
		            'ProvinceName'=> "varchar(16) default '' comment  '省名'",   
		            'CityName'    => "varchar(16) default '' comment  '市名'", 
		            'AreaName'    => "varchar(16) default '' comment  '区名'", 
		            'Address'     => "varchar(80) default '' comment  '地址' ",

		            'Status'  => "tinyint(2) default '0' comment '1有效 0无效'",
		            'Ctime'   => "int(11) unsigned DEFAULT '0'",
		        );
		        return $field;
	    }
	    private function tableIndex()
	    {
	        $index = array(
	            'index_u' 		 => array('OrderSn'),
	            'index_mobile'	 => array('Mobile'),
            	'index_name' 	 => array('Name'),
	            'index_province' => array('Province'),
	            'index_city'	 => array('City'),
	            'index_area'	 => array('Area'),
	            'index_provincen'=> array('ProvinceName'),
	            'index_cityn'	 => array('CityName'),
	            'index_arean'	 => array('AreaName'),

	            'index_gd' 	  => array('Gender'),
	            'index_status'=> array('Status'),
	            'index_c' 	  => array('Ctime'),
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
	        return parent::createTable('订单地址');
	    }


}