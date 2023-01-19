<?php

namespace action;

class orderLog extends core{

		protected function _init(){
		    		$this->_table 	    = $this->ol;
		        	$this->_tableField 	= $this->tableField();
	        		$this->_tableIndex  = $this->tableIndex();
	        		$this->_tableUnique = $this->tableUnique();
	
		}

		public function addLog($orderSn,$type,$class,$method,$title,$desc,$time,$ostatus,$logKey='',$logValue=''){
				$account = C('login','account');
				$accountUuid = isset( $account['uuid'] ) ? $account['uuid'] : '';
				if(empty( $accountUuid )){
						return false;
				}
				$str2time = strtotime( $time );
				if($str2time){
						$time = $str2time;
				}
				if(empty( $time)) $time=0;
				$timer = time();
				$data = array(
					'accountUuid' => $accountUuid,
					'orderSn'	  => $orderSn,
					'type'		  => $type,
					'title'		  => $title,
					'class'       => $class,
					'method'	  => $method,
					'descript'    => $desc,
					'time'        => $time,
					'ostatus'     => $ostatus, //订单设备状态
					'status'      => 1,
					'ctime'       => $timer,
					'logKey'	  => $logKey,
					'logValue'    => $logValue,
				);
				$this->_addData = $data;
				//$this->_addDebug = true;
				return parent::add();
		}
		public function add(){}
		public function save(){}
		public function lists(){}

		public function logLists($orderSn,$method=false){
				if(empty( $orderSn )) return false;
				$field   = "t1.*,t2.Name as accountName";
				$join[]  = "left join {$this->account} t2 on t2.Uuid=t1.AccountUuid";
				$where['t1.OrderSn']  = $orderSn;
				$where['t1.Status']   = 1;
				if(!empty( $method)){
					$where['t1.Method'] = $method;
				}
				$this->_aliasTable = "t1";
				$this->_listField  = $field;
				$this->_listJoin   = $join;
				$this->_listWhere  = $where;
				$this->_listOrder  = "t1.Id desc";
				//$this->_listDebug  = true;
				$res = parent::lists();
				return isset( $res['lists'] ) ? $res['lists'] : false;
		}

		public function regxTable(){ 
				$reUuid    = M('Regx','reStringAll',36);
				$reName    = M('Regx','reChinaAll',1,60);
				$reStr     = M('Regx','reStringAll',1,36);
				$reSn	   = M('Regx','reSn',10,16);
				$reDesc     = M('Regx','reStringAll',0,360);
				$reTime    = M('Regx','reNumRange',10,11);
	    		$reSort    = M('Regx','reNumRange',0,5);
	    		$reStatus  = M('Regx','reNumRange',0,1);
	    		$regxTable = array(
		           'Id'     	=> parent::regxField('id','ID',1,0,0,false,false),
		           'AccountUuid'=> parent::regxField('accountUuid','操作人员',0,1,1,$reUuid['re'],'编号:'.$reUuid['msg']),
		           'OrderSn'	=> parent::regxField('orderSn','订单编号',0,1,1,$reSn['re'],'订单编号:'.$reSn['msg']),
		           'Type'   	=> parent::regxField('type','日志类型',0,1,1,$reStatus['re'],'日志类型:'.$reStatus['msg']),
		           'Title'   	=> parent::regxField('title','日志标题',0,1,1,$reName['re'],'设备编号:'.$reName['msg']),
		           'Class'   	=> parent::regxField('class','请求类',0,1,1,$reStr['re'],'请求类:'.$reStr['msg']),
		           'Method'   	=> parent::regxField('method','请求方法',0,1,1,$reStr['re'],'请求方法:'.$reStr['msg']),
		           'Descript'   => parent::regxField('descript','备注信息',0,0,0,$reDesc['re'],'备注信息:'.$reUuid['msg']),
		           'LogKey'     => parent::regxField('logKey','日志key',0,0,0,$reName['re'],'日志key:'.$reName['msg']),
		           'LogValue'   => parent::regxField('logValue','日志value',0,0,0,$reDesc['re'],'日志value:'.$reUuid['msg']),
		           'Time'   	=> parent::regxField('time','扩展时间',0,0,0,false,false),
		           'Ostatus' 	=> parent::regxField('ostatus','订单状态',0,0,0,$reStatus['re'],'订单状态:'.$reStatus['msg']),
		           'Status' 	=> parent::regxField('status','状态',0,0,0,$reStatus['re'],'状态:'.$reStatus['msg']),
		           'Ctime'     	=> parent::regxField('ctime','创建时间',1,0,0,false,false),
		       );
		        return $regxTable;
	    }
	    private function tableField()
	    {
		        $field = array(
					'Id'          => "int(8) unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT",
            		'AccountUuid' => "char(36) not null comment '操作人员' ",
            		'OrderSn'	  => "varchar(16) not null comment '订单编号'",
            		'Type'        => "tinyint(2) default '1' comment '日志类型1常规可追踪展示操作2隐式记录'",
            		'Title'		  => "varchar(30) not null comment '日志标题'",
            		'Class'       => "varchar(30) not null comment '请求类'",
            		'Method'      => "varchar(30) not null comment '请求方法'",
            		'Descript'	  => "varchar(120) default '' comment '日志备注信息'",
            		'LogKey'	  => "varchar(30) default '' comment '日志key'",
            		'LogValue'	  => "varchar(120) default '' comment '日志value'",
            		'Time'		  => "int(11) unsigned DEFAULT '0' comment '扩展时间' ",
            		'Ostatus'	  => "tinyint(2) default '1' comment  '订单状态'",
		            'Status'	  => "tinyint(2) default '1' comment  '1有效 0无效'",
		            'Ctime' 	  => "int(11) unsigned DEFAULT '0'",
		        );
		        return $field;
	    }
	    private function tableIndex()
	    {
	        $index = array(
	            'index_u'    => array('AccountUuid'),
	            'index_sn'   => array('OrderSn'),
	            'index_t'    => array('Type'),
	            'index_cls'  => array('Class'),
	            'index_mtd'  => array('Method'),
	            'index_os'   => array('Ostatus'),
	            'index_s'    => array('Status'),
	            'index_c'    => array('Ctime'),
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
	        return parent::createTable('订单日志');
	    }


}