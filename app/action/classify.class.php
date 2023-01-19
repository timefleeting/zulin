<?php

namespace action;

class classify extends core{

		protected function _init(){
		    		$this->_table 	    = $this->classify;
		        	$this->_tableField 	= $this->tableField();
	        		$this->_tableIndex  = $this->tableIndex();
	        		$this->_tableUnique = $this->tableUnique();
	        		$this->_listQuery   = true;
	        		$this->_listOrder   = "Sort asc,Id desc";
		}

		public function chooserList(){
					$this->_listLimit = 100;
					$res = parent::lists();
					$lists = isset( $res['lists'] ) ? $res['lists'] : array();
					return $lists;
		}

		public function add(){
				$account = C('login','account');
				$accountUuid = isset( $account['uuid'] ) ? $account['uuid'] : '';
				if(empty( $accountUuid )){
						return array('status'=>0,'msg'=>'登录帐号异常');
				}
				$data = Q('data');
				$data['accountUuid'] = $accountUuid;
				$data['sort']       = !empty( $data['sort'] ) ? $data['sort'] : 0;
	    		$this->_addData = $data;
    			return parent::add();
		}

		public function regxTable(){ 
				$reUuid    = M('Regx','reStringAll',36);
				$reName    = M('Regx','reChinaAll',1,60);
				$reStr     = M('Regx','reStringAll',3,240);
	    		$reSort    = M('Regx','reNumRange',0,5);
	    		$reStatus  = M('Regx','reNumRange',0,1);
	    		$regxTable = array(
		           'Id'     		=> parent::regxField('id','ID',1,0,0,false,false),
		           'Uuid'   		=> parent::regxField('uuid','编号',1,0,0,$reUuid['re'],'编号:'.$reUuid['msg']),
		           'Name'   		=> parent::regxField('name','类目名称',0,1,1,$reName['re'],'类目名称:'.$reName['msg']),
		           'Descript'  	    => parent::regxField('descript','简单描述',0,0,0,$reStr['re'],'简单描述:'.$reStr['msg']),
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
            		'Name'      => "varchar(30) not null comment '类目名称'",
		            'Descript'  => "varchar(120) default '' comment  '简单描述' ",
		            'Sort'      => "mediumint(8) DEFAULT '0' COMMENT '排序'",
		            'Status'	=> "tinyint(2) default '0' comment '1有效 0无效'",
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
	            'index_c'   => array('Ctime'),
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
	        return parent::createTable('类目信息');
	    }


}