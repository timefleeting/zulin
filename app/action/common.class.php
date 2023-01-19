<?php
namespace action;

class common extends base{
		
		/**
	     * [imageVerify 验证码图片]
	     * @return bool|mixed
	     */
	    public function imageVerify() 
	    {	
	     return M('Image', 'buildImageVerify');
	    }

	    public function orderStatus( $key=false){
	    		$list = array(
	    			'0'=>'维修',
	    			'1'=>'已分配设备',
	    			'2'=>'未提货',
	    			'3'=>'未归还',
	    			'4'=>'已归还',
	    			'5'=>'超期未还',
	    		);
	    		return !empty( $key ) ? isset( $list[$key] ) ? $list[$key] : false : $list;
	    }


}