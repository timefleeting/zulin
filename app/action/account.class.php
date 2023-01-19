<?php

namespace action;

class account extends core{

			/*
			 * 初始化数据结构
			 */
		    protected function _init(){
		    		$this->_table 	    = $this->account;
		        	$this->_tableField 	= $this->tableField();
	        		$this->_tableIndex  = $this->tableIndex();
	        		$this->_tableUnique = $this->tableUnique();
	        		$this->_listQuery = true;
		    }

		    public function getSupperMobile(){
		    		return $this->supperMobile;
		    }

		    public function getRight($uuid){
		    		return parent::getAccountRight( $uuid );
		    }

		    public function saveRight( $where=false,$right=false ){
		    		$where = !empty( $where ) ? $where : Q('where');
					$right = !empty( $right ) ? $right : Q('right');
					$uuid = isset( $where['uuid'] ) ? $where['uuid'] : false;
					if(empty( $uuid )){
							return array('status'=>0,'msg'=>'保存权限人员不能为空');
					}
					Q('uuid',$uuid);
					$infoRes =  C('account','find');
					$info 	 =  isset( $infoRes['data'] ) ? $infoRes['data'] : false;
					if(empty( $info )){
							return array('status'=>0,'msg'=>'权限人员不存在!');
					}
					if(empty( $right )){
							return array('status'=>0,'msg'=>'保存权限不能为空!');
					}
					$rightStr 	= implode(',',$right);
					$rs 		= $this->setAccountRight( $uuid, $rightStr );
					if( !empty( $rs )){
							return array('status'=>1,'msg'=>'权限保存成功');
					}else{
							return array('status'=>0,'msg'=>'保存失败');
					}
		    }
		    /*
		     * 添加帐号
		     * 密码单独处理
		     */
		    public function add(){
		    		
		    		$account = C('login','account');
					$accountUuid = isset( $account['uuid'] ) ? $account['uuid'] : '';
					if(empty( $accountUuid )){
							return array('status'=>0,'msg'=>'登录帐号异常');
					}
		    		$data = Q('data');
		    		if( isset( $data['password'] )){
		    				$data['password'] = parent::passwdEncrypt( $data['password'] );
		    		}
		    		$ip = M('Service','userIp');
		    		$data['ip'] = $ip;
		    		$data['accountUuid'] = $accountUuid;
		    		$data['sort']       = !empty( $data['sort'] ) ? $data['sort'] : 0;
		    		$this->_addData = $data;
	    			return parent::add();
		    }

		    public function save(){
		    		$where 	    =  Q('where');
					$data  		=  Q('data');
					//注意超级管理员密码只能本身修改
    				$account = C('login','account');
					$accountmobile = isset( $account['mobile'] ) ? $account['mobile'] : '';
					if(empty( $accountmobile )){
							return array('status'=>0,'msg'=>'登录帐号异常');
					}
					$uuid = isset( $where['uuid'] ) ? $where['uuid'] : false;
					Q('uuid',$uuid);
					$infoRes =  C('account','find');
					$info 	 =  isset( $infoRes['data'] ) ? $infoRes['data'] : false;
					if(empty( $info )){
							return array('status'=>0,'msg'=>'帐号不存在无法更新');
					}
					if( $info['mobile']==$this->supperMobile && $accountmobile!=$this->supperMobile){
							return array('status'=>0,'msg'=>'无权限修改超级管理员');
					}
					/*end*/
		    		if( isset( $data['password'] )){ //更新密码
		    				$password   = isset( $data['password'] ) ? trim($data['password']) : '';
				    		$confirm    = isset( $data['confirm'] )  ? trim($data['confirm']) : '';
				    		$res = $this->returnRes();
				    		if(empty( $password )){
				    				$res['msg'] = "密码不能为空";
				    				return $res;
				    		}
				    		if(md5($password)!=md5($confirm)){
				    				$res['msg'] = "密码不一致";
				    				return $res;
				    		}
				    		$data = array();
				    		$data['password'] = parent::passwdEncrypt( $password );
		    		}
		    		$data['sort']       = !empty( $data['sort'] ) ? $data['sort'] : 0;
		    		$this->_saveWhere = $where;
		    		$this->_saveData  = $data;
		    		$res = parent::save();
		    		return $res;
		    }

		    public function find(){

		    		$uuid = Q('uuid');
		    		if(empty( $uuid ))
		    				return false;
		    		return parent::find();
		    }

		    /*
		     *  根据帐号密码获取信息
		     */
		    public function mobilePwdInfo( $mobile,$password ){  
		    		if(empty($mobile) || empty($password)){
		    				return false;
		    		}
		    		Q('mobile',$mobile);
		    		Q('password',parent::passwdEncrypt( $password ));
		    		return parent::find();
		    }

		    /*创建超级管理员*/
		    public function createSuperAccount(){
		    		$account = array(
		    				'name'    => 'administrator',
		    				'mobile'  => $this->supperMobile,
		    				'password'=> 'AnHuu93256',
		    				'status'  => 1,
		    		);
		    		Q('data',$account);
		    		return $this->add();
		    }

		    /*
		     * 字段校验规则
		     */
		    public function regxTable(){ 
		    		$reName    = M('Regx','reChina',1,20);
		    		$reMobile  = M('Regx','reMobile');
		    		$rePwd     = M('Regx','reString',6,20);
		    		$reEmail   = M('Regx','reEmail');
		    		$reSort    = M('Regx','reNumRange',0,5);
		    		$reStatus  = M('Regx','reNumRange',0,1);
		    		$reIp      = M('Regx','reStringAll',4,20);
		    		$reUuid    = M('Regx','reStringAll',36);
		    		$regxTable = array(
			           'Id'     	=> parent::regxField('id','ID',1,0,0,false,false),
			           'Uuid'   	=> parent::regxField('uuid','编号',1,0,0,false,false),
			           'Name'   	=> parent::regxField('name','名称',0,0,0,$reName['re'],$reName['msg']),
			           'Mobile'   	=> parent::regxField('mobile','手机号',0,1,1,$reMobile['re'],$reMobile['msg']),
			           'Password' 	=> parent::regxField('password','密码',0,1,1,$rePwd['re'],'密码:'.$rePwd['msg']),
			           'Gender' 	=> parent::regxField('gender','性别',0,0,0,'/^[0-2]$/',''),
			           'Email' 		=> parent::regxField('email','电子邮箱',0,0,0,$reEmail['re'],$reEmail['msg']),
			           'Sort' 		=> parent::regxField('sort','排序',0,0,0,$reSort['re'],$reSort['msg']),
			           'Status' 	=> parent::regxField('status','排序',0,0,0,$reStatus['re'],$reStatus['msg']),
			           'Ctime'     	=> parent::regxField('ctime','创建时间',1,0,0,false,false),
			           'Mtime'   	=> parent::regxField('mtime','更新时间',1,0,0,false,false),
			           'Ip'   	    => parent::regxField('ip','注册IP',0,0,0,$reIp['re'],$reIp['msg']),
			           'AccountUuid' => parent::regxField('accountUuid','操作人员',0,0,0,$reUuid['re'],'操作人员:'.$reUuid['msg']),	
			       );
			        return $regxTable;
		    }
		    private function tableField()
		    {
			        $field = array(
			            'Id'    	=> "int(8) unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT", 
			            'Uuid'  	=> "char(36) not null comment '帐号UUID'",  
			            'Name'  	=> "varchar(25) not null comment '用户名'",   
			            'Mobile'    => "varchar(12) not null comment '帐号'",
			            'Password'  => "varchar(32) not null comment '密码'",
			            'Gender'    => "tinyint(2) default '0' COMMENT '1男性 2女性'",
			            'Email'     => "varchar(25) DEFAULT '0' COMMENT '邮箱'",
			            'Sort'  	=> "mediumint(8) DEFAULT '0' COMMENT '排序'",
			            'Status'	=> "tinyint(2) default '0' comment '1有效 0无效'",
			            'Ctime' 	=> "int(11) unsigned DEFAULT '0'",
			            'Mtime' 	=> "int(11) unsigned default '0'",
			            'Ip'		=> "varchar(16) not null comment '注册IP'",
			            'AccountUuid'=> "char(36) default '' comment '操作人员' ",
			        );
			        return $field;
		    }
		    private function tableIndex()
		    {
		        $index = array(
		            'index_un' => array('Uuid', 'Name'),
		            'index_nu' => array('Name', 'Uuid'),
		            'index_mp' => array('Mobile','Password'),
		            'index_cm' => array('Ctime', 'Mtime'),
		            'index_mc' => array('Mtime', 'Ctime'),
		        );
		        return $index;
		    }
		    private function tableUnique()
		    {
		        $unique = array(
		            'unique_uid'    => array('Uuid'),
		            'unique_mobile' => array('Mobile'),
		        );
		        return $unique;
		    }
		    public function createTable()
		    {
		        return parent::createTable('帐号信息');
		    }


}