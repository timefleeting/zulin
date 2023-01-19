<?php
namespace main;

class Regx{

		/*
		 * 手机号码或电话号码正则
		 */
		public static function reMobile(){
	    		$re = '/^1[34578]\d{9}$|^0\d{2,3}-?\d{6,8}$|^\d{6,8}$/';
	    		$msg = '手机号码错误';
	    		return array('re'=>$re,'msg'=>$msg);
	    }

	    public static function reString($m,$n){
	    		$min = $m ? $m : 1;
	    		$max = $n ? $n : 9;
	    		if($max<$min){
	    			$re  = '/^[a-zA-Z0-9_]{'.$min.'}/';
	    			$msg = "下划线,数字,字母{$min}位";
	    		}else{
	    			$re  = '/^[a-zA-Z0-9_]{'.$min.','.$max.'}/';
	    			$msg = "下划线,数字,字母{$min}-{$max}位";
	    		}
	    		return array('re'=>$re,'msg'=>$msg);
	    }
	    /*
	     *  注意:正则中，一个中文占3个字节
	     */
	    public static function reChina($m,$n){
	    		$min = $m ? $m : 1;
	    		$max = $n ? $n : 9;
	    		if($max<$min){
	    			$re = '/^[a-zA-Z0-9_\x7f-\xff]{'.$min.'}[a-zA-Z0-9_\x7f-\xff]$/';
	    			$msg = "下划线、数字、字母、中文".($min+1)."位";
	    		}else{
	    			$re = '/^[a-zA-Z0-9_\x7f-\xff]{'.$min.','.$max.'}[a-zA-Z0-9_\x7f-\xff]$/';
	    			$msg = "下划线、数字、字母、中文".($min+1)."-".($max+1)."位";
	    		}
	    		return array('re'=>$re,'msg'=>$msg);
		}
		/*
		 * 匹配所有字符不含中文
		 */
		public static function reStringAll($m,$n){
	    		$min = $m ? $m : 0;
	    		$max = $n ? $n : 9;
	    		if($max<$min){
	    			$re  = '/^[a-zA-Z0-9_\S]{'.$min.'}/';
	    			$msg = "{$min}位字符";
	    		}else{
	    			$re  = '/^[a-zA-Z0-9_\S]{'.$min.','.$max.'}/';
	    			$msg = "{$min}-{$max}位字符";
	    		}
	    		return array('re'=>$re,'msg'=>$msg);
	    }
	    /*
	     * 匹配所有字符含英文
	     */
	    public static function reChinaAll($m,$n){
	    		$min = $m ? $m : 0;
	    		$max = $n ? $n : 9;
	    		if($max<$min){
	    			$re = '/^[a-zA-Z0-9_\x7f-\xff\S]{'.$min.'}[a-zA-Z0-9_\x7f-\xff]$/';
	    			$msg = "下划线、数字、字母、中文".($min+1)."位";
	    		}else{
	    			$re = '/^[a-zA-Z0-9_\x7f-\xff\S]{'.$min.','.$max.'}[a-zA-Z0-9_\x7f-\xff]$/';
	    			$msg = "下划线、数字、字母、中文".($min+1)."-".($max+1)."位";
	    		}
	    		return array('re'=>$re,'msg'=>$msg);
		}
		public static function reCardNo(){
				$re = '/^(\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$/';
	    		$msg = "15或18位身份证号";
	    		return array('re'=>$re,'msg'=>$msg);
		}
		public static function reEmail(){
				$re = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/';
				$msg = "邮箱错误";
				return array('re'=>$re,'msg'=>$msg);
		}
		/*
		 * 正则数字范围 暂不支持负数
		 */
		public static function reNumRange($begin,$end){  
				$re = '/^\d{'.$begin.','.$end.'}$/';
				$msg = "{$begin}-{$end}数字";
				return array('re'=>$re,'msg'=>$msg);
		}

}
