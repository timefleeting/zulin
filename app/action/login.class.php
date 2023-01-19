<?php

namespace action;

class login extends common{

		private $identifyKey = 'identify';

		public function index(){ 
				$login = $this->account(); 
				if(!empty($login)){
						redirect(cUri('index','index'));
				}
		}
		/*
		 * @param mobile
		 * @param password
		 * @param verify
		 */
		public function doLogin(){  
				$checkRes = $this->loginCheck();
				if (empty($checkRes['status'])) {
            			return $checkRes;
        		}
        		$mobile   = Q('mobile');
        		$password = Q('password');
        		if(empty($mobile) || empty($password)){
        				$checkRes['status'] = 0;
        				$checkRes['msg'] = "帐号密码不能为空!";
        				return $checkRes;
        		}
        		$accountRes = C('account','mobilePwdInfo',$mobile,$password);
        		if( $accountRes['status'] != 1 ){
        				$accountRes['msg'] = !empty($accountRes['msg'] ) ? $accountRes['msg'] : '帐号或密码错误';
        				return $accountRes;
        		}
        		$ua = M('Service','userAgent');
        		$ip = M('Service','userIp');
        		$accountRes['data']['ua'] = $ua;
        		$accountRes['data']['ip'] = $ip;
        		$accountRes['data']['loginTime'] = time();
        		$account = $this->loginIdentify( $accountRes['data'],true );
        		$rs = session($this->loginKey,$account);
        		session('loginNum', null );
        		$res['status'] = $rs ?  1: 0;
        		$res['msg']   = !empty( $rs ) ? '登录成功':'登录失败';
        		return $res;
		}
		/**
		 * [account 登录帐号信息]
		 * @param  boolean $key  [帐号字段key信息]
		 * @return [array]       [帐号]
		 */
		public function account( $key=false ){
				$loginSession = session($this->loginKey);
		        $account = $this->loginVerify($loginSession,false);
		        if( !empty( $key )){
		                return isset( $account[$key] ) ? $account[$key] : false;
		        }else{
		                return $account;
		        }
		}
		/**
		 * [out 退出登录]
		 * @return [type] [description]
		 */
		public function out(){
				session($this->loginKey,null);
				redirect(cUri('login','index'));
		}
		
		private function loginCheck(){ 
				$res        = $this->returnRes(); 
				$loginNum   = $this->tryLogin();
            	$verifyCode = Q('verify');
            	if (md5($verifyCode) != session('verifyImg')) {
                    $res['msg']       = '验证码错误';
                    $res['try_num']   = $loginNum;
                    return $res;
                }
            	if( $loginNum > 100 ){
            			$res['msg'] = '请求太多!';
            			$res['loginNum'] = $loginNum;
            			return $res;
            	}
            	session('verifyImg',null);
            	$data = array(
            			'loginNum' => $loginNum,
            	);
            	$res['status'] = 1;
            	$res['data']   = $data;
            	return $res;
		}
		/*登录次数*/
		private function tryLogin(){
				$loginNum = session('loginNum');
				if (empty($loginNum)) {
		            $loginNum = 0;
		            $loginNum++;
		            session('loginNum', $loginNum);
		        } else {
		            $loginNum = intval($loginNum) + 1;
		            session('loginNum', $loginNum);
		        }
		        return $loginNum;
		}
		/** 生成登录信息签名
		 */
		private function loginIdentify($loginInfo,$fill=false)
	    {
	        $identifyKey = $this->identifyKey;
	        if(isset( $loginInfo[$identifyKey] )){
	                unset( $loginInfo[$identifyKey] );
	        }
	        if (empty($loginInfo) || !is_array($loginInfo)) {
	            return false;
	        }
	        $identify = '';
	        ksort($loginInfo);
	        foreach( $loginInfo as $key => $val ){
	                if( empty( $val )){
	                        continue;
	                }
	                $identify .= $val;
	        }
	        if(empty( $identify ) ){
	            return false;
	        }
	        $login_identify = md5($identify);
	        if($fill===true){
	        		$loginInfo[$identifyKey] = $login_identify;
	        		return $loginInfo;
	        }
	        return $login_identify;
	    }
	    /**  验证登录信息中的签名*/
	    private function loginVerify($login)
	    {
	        $identifyKey = $this->identifyKey;
	        if (empty($login)|| !is_array( $login ) )
	                    return false;
	        $login_identify = isset($login[$identifyKey] ) ? $login[$identifyKey] : '';
	        if (empty($login_identify)) return false;
	        $identify = $this->loginIdentify($login);
	        if ($login_identify == $identify) {
	            return $login;
	        } else {
	            return false;
	        }
	    }
}