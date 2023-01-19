<?php

namespace action;

/**
 * 后台导航功能模块
 * 只有超级管理员(系统开发者)有权限获取所有
 */

class navigate extends common{


		/*
		 * 导航树
		 */
		public function navTree(){
				$lists = $this->setting();
				$trees = array();
				foreach( $lists as $key => $val ){
						if( $val['nav']!=1 )
								continue;
						$parent = $val['parent'];
						$trees[$parent][] = $val;
				}
				return $trees;
		}

		/*
		 * 权限配置树
		 */
		public function rightTree(){
				$lists = $this->setting();
				if(empty( $lists ))
						return false;
				$trees = array();
				foreach( $lists as $key => $val ){
						$parentKey = (string)$val['parent'];
						$trees[$parentKey][] = array(
								'id'   => (string)$val['sort'],
								'name' => $val['name'],
								'class'=> $val['class'],
								'method'=>$val['method'],
						);
				}
			return $trees;
		}
		/*
		 *  当前帐号权限接口
		 *  是否在权限接口范围,是则是否已配置权限
		 */
		public function apiRight( $uriKey,$uriValue ){
				if(empty( $uriKey ) || empty( $uriValue ))
						return false;
				$allSetting = $this->setting(true);
				$allApi     = $this->rightApi( $allSetting );
				if( isset( $allApi[$uriKey][$uriValue] )){
						$setting = $this->setting();
						$api     = $this->rightApi( $setting );
						if(isset( $api[$uriKey][$uriValue] )){
								return true;
						}else{
								return false;
						}
				}else{
						return true;
				}
		}
		/*
		 *  @param $cls 父来源接口类
		 *  @param $mth 父来源接口方法
		 *  @param $navType 导航接口类型 1,主导航 2,操作导航(添加) 3列表操作导航(编辑)
		 */
		public function navFields( $cls,$mth,$navType ){
				$class 	= !empty( $cls ) ? $cls : Q('class');
				$method	= !empty( $mth ) ? $mth : Q('method');
				$navType= !empty( $navType ) ? $navType : Q('type');
				$lists  = $this->setting();
				$parent = -1;
				foreach( $lists as $key => $val ){
						if( $val['class']== $class && $val['method'] == $method ){
								$parent = $val['sort'];
								break;
						}
				}
				$fields = array();
				foreach( $lists as $key=>$val ){
						if( $val['parent'] == $parent && $val['nav']==$navType){
								$fields[] = $val;
						}
				}
				return $fields;
		}
		/*
		 *  根据setting配置信息,获取注册的接口部分
		 */
		private function rightApi( $lists ){
				$rightApi = array();
				if(empty( $lists )){
						return $rightApi;
				}
				foreach( $lists as $key => $val ){
						$IBusiness = isset( $val['IBusiness'] ) ? $val['IBusiness'] : '';
						if( !empty( $IBusiness )){
								foreach( $IBusiness as $key1=> $val1 ){
										$view = isset( $val1['view'] ) ? $val1['view'] : false;
										$api  = isset( $val1['api'] ) ? $val1['api'] : false;
										if( !empty( $view )){
												$uri = explode('/',$view );
												$rightApi[$uri[0]][$uri[1]] = 1;
										}
										if( !empty( $api )){
												$uri = explode('/',$api );
												$rightApi[$uri[0]][$uri[1]] = 1;
										}
								}
						}
				}
				return $rightApi;
		}

		/*
		 * 根据登录帐号获取接口列表
		 * 数据结构与逻辑
		 * 导航接口 --> 业务接口 (一对多,一个导航接口存在多个业务接口)
		 *  	业务接口的调用需要判定来源的导航接口依据
		 */
		private function setting( $right=false ){

			$setting = array();

			$setting[] 		= $this->field('adminSys','帐号系统','','',0,'&#xe66c;','',1);
			$accountIndex 	= '[{"view":"account/index"},{"api":"account/lists"}]';
			$setting[] 		= $this->field('accountIndex','帐号管理','account','index','adminSys','&#xe77f;','',1,$accountIndex);
			$accountNew 	= '[{"view":"account/new"},{"api":"account/add"}]';
			$setting[] 		= $this->field('accountNew','添加','account','new','accountIndex','&#xe69e;','',2,$accountNew);
			$accountEdit	='[{"view":"account/edit"},{"api":"account/find"},{"api":"account/save"}]';
			$setting[] 		= $this->field('accountEdit','编辑','account','edit','accountIndex','&#xe69e;','',3,$accountEdit);
			$accountPasswd 	= '[{"view":"account/password"},{"api":"account/save"}]';
			$setting[] 		= $this->field('accountPasswd','修改密码','account','password','accountIndex','&#xe69e;','',3,$accountPasswd);
			$accountRight 	= '[{"view":"account/right"},{"api":"account/saveRight"}]';
			$setting[] 		= $this->field('accountRight','帐号权限','account','right','accountIndex','&#xe69e;','',3,'',$accountRight);
			$accountInfo 	= '[{"view":"account/info"},{"api":"account/find"}]';
			$setting[] 		= $this->field('accountInfo','个人信息','account','info','adminSys','&#xe77f;','',1,$accountInfo);
			$setting[] 		= $this->field('accountEdit1','编辑','account','edit','accountInfo','&#xe69e;','',3,$accountEdit);
			$setting[] 		= $this->field('accountPasswd1','修改密码','account','password','accountInfo','&#xe69e;','',3,$accountPasswd);

			$setting[] 		= $this->field('orderSys','订单系统','order','',0,'&#xe64b;','',1);
			$setting[] 		= $this->field('orderIndex','订单管理','order','index','orderSys','&#xe64b;','',1);
			$orderNew 	= '[{"view":"order/new"},{"api":"order/add"}]';
			$setting[] 		= $this->field('orderNew','我要下单','order','new','orderIndex','&#xe69e;','',2,$orderNew);

			$setting[] 		= $this->field('deviceSys','设备系统','device','',0,'&#xe651;','',1);
			$setting[] 		= $this->field('warehouseIndex','仓库管理','warehouse','index','deviceSys','&#xe635;','',1);
			$warehouseNew 	= '[{"view":"warehouse/new"},{"api":"warehouse/add"}]';
			$setting[] 		= $this->field('warehouseNew','添加','warehouse','new','warehouseIndex','&#xe69e;','',2,$warehouseNew);
			$warehouseEdit	='[{"view":"warehouse/edit"},{"api":"warehouse/find"},{"api":"warehouse/save"}]';
			$setting[] 		= $this->field('warehouseEdit','编辑','warehouse','edit','warehouseIndex','&#xe69e;','',3,$warehouseEdit);
			$warehouseBelong = '[{"view":"warehouse/edit"},{"api":"warehouse/find"},{"api":"warehouseBelong/lists"},{"api":"warehouseBelong/save"}]';
			$setting[] 	    = $this->field('warehouseBelong','关联仓库','warehouse','warehouseBelong','warehouseIndex','&#xe69e;','',3,$warehouseBelong);
			$warehouseStock = '[{"api":"warehouse/stockLists"}]';
			$setting[] 		= $this->field('warehouseStockIndex','仓库库存','warehouse','stock','deviceSys','&#xe635;','',1,$warehouseStock);

			$setting[] 		= $this->field('classifyIndex','设备类目','classify','index','deviceSys','&#xe622;','',1);
			$classifyNew 	= '[{"view":"classify/new"},{"api":"classify/add"}]';
			$setting[] 		= $this->field('classifyNew','添加','classify','new','classifyIndex','&#xe69e;','',2,$classifyNew);
			$classifyEdit	='[{"view":"classify/edit"},{"api":"classify/find"},{"api":"classify/save"}]';
			$setting[] 		= $this->field('classifyEdit','编辑','classify','edit','classifyIndex','&#xe69e;','',3,$classifyEdit);

			$setting[] 		= $this->field('deviceIndex','设备管理','device','index','deviceSys','&#xe8b1;','',1);
			$deviceNew 		= '[{"view":"device/new"},{"api":"device/add"}]';
			$setting[] 		= $this->field('deviceNew','添加','device','new','deviceIndex','&#xe69e;','',2,$deviceNew);
			$deviceEdit		='[{"view":"device/edit"},{"api":"device/find"},{"api":"device/save"}]';
			$setting[] 		= $this->field('deviceEdit','编辑','device','edit','deviceIndex','&#xe69e;','',3,$deviceEdit);
			$deviceTransfer	='[{"view":"device/transfer"},{"api":"device/find"},{"api":"device/save"}]';
			$setting[] 		= $this->field('deviceTransfer','调仓','device','transfer','deviceIndex','&#xe69e;','',3,$deviceTransfer);

			if( $right===true ){
					return $setting;
			}
			$isSupperAccount = parent::isSupperAccount();
			if($isSupperAccount==true){
					return $setting;
			}else{
					$right = parent::accountRight();
					if(empty($right)){
							return false;
					}
					$rightArr = explode(',',$right);
					$rightSetting = array();
					foreach( $setting as $key => $val ){
							if( in_array($val['sort'],$rightArr)){
									$rightSetting[] = $val;
							}
					}
					return $rightSetting;
			}
		}

		private function field($sort,$name,$class,$method,$parent=0,$icon='',$describe='',$nav=1,$IBusiness=false){
				$uuid  = M('Service','encrypt',$class.'.'.$method.'.'.$parent );
				$uri = '';
				if( !empty( $class ) && !empty( $method ) ){
						$uri = cUri($class,$method);
				}
				$field = array(
					'sort' 			=> 	$sort,  	//权重，排序，编号 必须作为唯一排序有先后
					'name'  		=>	$name,
				    'class' 		=>	$class,		//接口类
					'method'		=>	$method,	//接口方法
					'parent'		=>	$parent,	
					'describe'		=>	$describe,  //描述
					'icon'  		=> 	$icon,		//图标
					'uuid'       	=>  $uuid,		//唯一编号
					'nav'           =>  $nav,
					'uri'			=>  $uri,
					'IBusiness'		=>  !empty($IBusiness) ? json_decode($IBusiness,true) : false,	//业务接口json
				);
				return $field;
		}

}