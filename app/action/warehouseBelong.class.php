<?php

namespace action;


class warehouseBelong extends core{

			protected $addMax = 10;

			protected function _init(){
		    		$this->_table 	    = $this->wb;
		        	$this->_tableField 	= $this->tableField();
	        		$this->_tableIndex  = $this->tableIndex();
	        		$this->_tableUnique = $this->tableUnique();
			}

			/*
			 * 获取下属关联库
			 */
			public function listsByUuid( $uuid ){
					$field = "t1.*,t2.Name as warehouseName";
					$join  = "left join {$this->warehouse} t2 on t2.Uuid=t1.Buuid";
					$where['t1.Uuid'] = $uuid;
					$where['t1.Status'] = 1;
					$this->_aliasTable = 't1';
					$this->_listField = $field;
					$this->_listJoin  = $join;
					$this->_listWhere = $where;
					$res = parent::lists();
					return isset( $res['lists'] ) ? $res['lists'] : array();
			}

			public function find(){}
			public function save(){
					$uuids 		= Q('uuids');
					$resetUuids = Q('resetuuids');
					$where 		= Q('where');
					$uuid  		= isset( $where['uuid'] ) ? $where['uuid'] : false;
					if( empty( $uuid )){
							return array('status'=>0,'msg'=>'更新数据异常');
					}
					if(empty( $resetUuids)||!is_array($resetUuids)){
							return array('status'=>0,'msg'=>'更新数据记录不能为空');
					}
					/*if( empty( $uuids )||!is_array($uuids) ){
							return array('status'=>0,'msg'=>'请选择下属分类','msgcode'=>1);
					}*/
					$data  = array();
					$timer = time();
					foreach( $uuids as $key => $val ){
						$data[] = array(
								'uuid'  => $uuid,
								'buuid' => $val,
								'status'=> 1,
								'ctime' => $timer,
						);
					}
					if( count($data) > $this->addMax ){
		    				return array('status'=>0,'msg'=>"最多只能添加{$this->addMax}条记录");
		    		}
		    		if( count( $resetUuids ) > $this->addMax ){
		    				return array('status'=>0,'msg'=>"更新记录不得超出{$this->addMax}条");
		    		}
		    		$resetData['status'] = 0;
		    		$resetWhere['buuid'] = array('in',"'".implode("','",$resetUuids)."'"); 
		    		$this->_saveWhere = $resetWhere;
		    		$this->_saveData  = $resetData;
		    		//$this->_saveDebug = true;
		    		$resetRes = parent::save();
		    		if( $resetRes['status']!=1){
		    				return $resetRes;
		    		}
		    		if(empty( $data )){ //不更新，只重置
		    				return $resetRes;
		    		}
		    		$this->_addData = $data;
		    		$addRes = parent::add();
		    		return $addRes;
			}

			public function add(){}
			public function lists(){}

			public function regxTable(){ 
		    		$reUuid    = M('Regx','reStringAll',36);
		    		$reStatus  = M('Regx','reNumRange',0,1);
		    		$regxTable = array(
			           'Id'     		=> parent::regxField('id','ID',1,0,0,false,false),
			           'Uuid'   		=> parent::regxField('uuid','仓库编号',0,1,1,$reUuid['re'],'仓库编号:'.$reUuid['msg']),
			           'Buuid'   		=> parent::regxField('buuid','下属仓库编号',0,1,1,$reUuid['re'],'下属仓库编号:'.$reUuid['msg']),
			           'Status' 		=> parent::regxField('status','状态',0,0,0,$reStatus['re'],'状态:'.$reStatus['msg']),
			           'Ctime'     		=> parent::regxField('ctime','创建时间',1,0,0,false,false),
			       );
			        return $regxTable;
		    }

		    private function tableField()
		    {
			        $field = array(
			            'Id'    		=> "int(8) unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT", 
			            'Uuid'  		=> "char(36) not null comment '仓库编号'",  
			            'Buuid'  		=> "char(36) not null comment '下属仓库编号'",
			            'Status'		=> "tinyint(2) default '0' comment '1有效 0无效'",
			            'Ctime' 		=> "int(11) unsigned DEFAULT '0'",
			        );
			        return $field;
		    }
		    private function tableIndex()
		    {
		        $index = array(
		            'index_u' 		=> array('Uuid'),
		            'index_b'		=> array('Buuid'),    
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
		        return parent::createTable('下属仓库');
		    }

}