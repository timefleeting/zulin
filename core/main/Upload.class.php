<?php

namespace main;

/*
 * 上传类,图片,视频资源等...
 */

class Upload{
		private $_path = ''; 	//绝对完整路径
		private $_imageSuffix = array('jpg','png','gif','jpeg');
		private $_imageSize   = 1024*1024*2; //2M 最大限制
		public function __construct( ){
                $upload = MAININFO;  //默认为当前脚本下的目录
                $relativePath = DS.'upload'.DS.config('workspace');//upload空间应用路径
                $this->_path  = $upload.$relativePath;//upload空间应用路径
		}
		/*
		 *  uploadBase64Image 保存base64图片
		 *  @param $name 图片名称(无后缀)
		 *  @param $base64 图片base64编吗
		 *  @return $imageFile 图片相对路径
		 */
		public function uploadBase64Image($name,$base64){
				$uploadFileRes = $this->filePath( $name );
				if( $uploadFileRes['status']!=1 ){
						return $uploadFileRes;
				}
				$uploadFile = $uploadFileRes['data'];
				$imageInfoRes= $this->base64ToImage( $base64 );
				if( $imageInfoRes['status'] != 1 ){
						return $imageInfoRes;
				}
				$imageInfo  = $imageInfoRes['data'];
				$suffix 	= isset( $imageInfo['type'] ) 	 ? $imageInfo['type'] 	 : '';
				$content 	= isset( $imageInfo['content'] ) ? $imageInfo['content'] : '';
				if(empty($suffix)){
						return array('status'=>0,'msg'=>'未知图片格式','msgcode'=>'1');
				}
				$imageFile  	= $uploadFile.'.'.$suffix;
				$size 			= file_put_contents( $imageFile, $content );
		        if( $size > 0 ){
		        	$imageFile 	= str_replace(MAININFO,'',$imageFile);
		            return array('status'=>1,'data'=>$imageFile,'msg'=>'','mscode'=>0);
		        }else{
		            return array('status'=>0,'msg'=>'图片写入失败','msgcode'=>'2');
		        }
		}
		/*
		 * 根据路径名 删除图片
		 */
		public function deloadBase64Image($name){
				if(empty($name)){
						return array('status'=>0,'msg'=>'图片名路径不能为空','mscode'=>31);
				}
				$uploadFileRes = $this->filePath( $name );
				if( $uploadFileRes['status']!=1 ){
						return $uploadFileRes;
				}
				$uploadFile = $uploadFileRes['data'];  //无后缀图片名
				$glob = glob( $uploadFile.'.*' );  //路径数组
				$cnt = 0;
				if( !empty( $glob )){
						foreach( $glob as $key => $val ){
								$rs = unlink( $val ) ? 1: 0;
								$cnt++;
						}
				}
				return array('status'=>1,'data'=>$cnt);
		}
		/*根据相对路径获取图片
		 *  @param $uploadImage  图片相对路径
		 *  @param $show         是否显示图片
		*/
		public function uploadImageBase64( $uploadImage,$show=false ){
				if(empty($uploadImage)){
						return array('status'=>0,'msg'=>'图片地址不能为空','mscode'=>31);
				}
				$fullImagePath = MAININFO.$uploadImage;
				$type = pathinfo($fullImagePath, PATHINFO_EXTENSION);
				if(empty( $type )){
						return array('status'=>0,'msg'=>'图片信息异常','mscode'=>32);
				}
				$type = strtolower( $type );
				$imageTypeList = $this->_imageSuffix;
				if( !in_array( $type,$imageTypeList ) ){
						return array('status'=>0,'msg'=>'图片格式错误,仅支持png,jpeg,gif,jpg','mscode'=>33);
				}
				if($show==true){
						$headerType = "Content-type:image/{$type};";
						header($headerType);
						$base64Image = file_get_contents($fullImagePath);
						echo $base64Image;
				}else{
						$imageType   = "data:image/{$type};base64,";
						$base64Image = $imageType.base64_encode( file_get_contents($fullImagePath) );
						return $base64Image;
				}
		}
		

		//获取存储路径
		private function getPath( $mode = 0777 ){
 				$path = $this->_path;
 				$path = str_replace(array('\\\\\\','\\\\','\\','///','//','/'),DIRECTORY_SEPARATOR,$path );
				if( !empty($path ) && !is_dir( $path ) ){ 
						$mkdir = mkdir( $path,$mode,true );
						if( !$mkdir ){
								return array('status'=>0,'msg'=>'存放空间异常','msgcode'=>11);
						}	
				}
				return array('status'=>1,'msg'=>'','data'=>$path,'msgcode'=>0);
		}
		/*
		 *名称存放路径编码
		 * @param $key 存放文件名称(无后缀) 使用.路径结构存放,否则根据md5 hash存放
		 * @return $uploadFile 无后缀文件名路径
		*/
		private function filePath( $key,$mode = 0777 ){
				$pathRes = $this->getPath();
				if( $pathRes['status']!=1 ){
						return $pathRes;
				}
				$path = $pathRes['data'];
				if( !$path || empty( $key ) ){
						return array('status'=>0,'msg'=>'存放路径异常','msgcode'=>21);
				}
				$parseKey   = explode('.',$key);
    			$count      = count( $parseKey );
    			if($count>2){
    					$hashKey  = array_pop($parseKey);//弹出最后一个
    					$levelDir = implode(DIRECTORY_SEPARATOR,$parseKey);
    			}else{
    					$hashKey    = md5( $key );
    					$levelDir = $hashKey[0].$hashKey[1].DIRECTORY_SEPARATOR.$hashKey[2].$hashKey[3];
    			}
    			$uploadPath = $path.DIRECTORY_SEPARATOR.$levelDir;
    			$uploadPath = str_replace(array('\\\\\\','\\\\','\\','///','//','/'),DIRECTORY_SEPARATOR,$uploadPath );
    			if( !empty($uploadPath ) && !is_dir( $uploadPath ) ){
    				    $status  = mkdir( $uploadPath,$mode,true );
		            	if( !$status ){
							return array('status'=>0,'msg'=>'存放目录无权限生成','msgcode'=>22);
						}	
            	}
    			$uploadFile = $uploadPath.DIRECTORY_SEPARATOR.$hashKey;
            	return array('status'=>1,'msg'=>'成功','data'=>$uploadFile,'msgcode'=>0);
		}

	    /**
	     * [base64ToImage 根据uuid,图片目录路径，base64string 保存为图片]
	     * @param $base64String [html图片base64编码 data:image/jpg;base64,数据]
	     * @param $imagePath    [要保存的图片目录路径相对于网站根路径,图片后缀由base64编码获取]
	     * @return bool|string  [成功返回图片地址，失败false]
	     */
		private function base64ToImage( $base64String ){
		        if(empty( $base64String ) ){
		            return array('status'=>0,'msg'=>'图片内容为空!','msgcode'=>31);
		        }
		        $base64Len = strlen( $base64String );
		        if( $base64Len > $this->_imageSize){
		        	$sizeM = round($this->_imageSize/1024/1024,2);
		        	return array('status'=>0,'msg'=>"图片内容不得超过{$sizeM}M",'mscode'=>32);
		        }
		        preg_match('/^data:image\/(.*);base64,(.*)/i',$base64String,$imageMatch );
		        $imageType     = isset( $imageMatch[1] ) ? strtolower( $imageMatch[1] ) : ''; //图片后缀类型
		        $imageContent  = isset( $imageMatch[2] ) ? $imageMatch[2] : '';
		        $imageTypeList = $this->_imageSuffix;
		        if( empty( $imageType ) || !in_array($imageType,$imageTypeList) || empty( $imageContent ) ){
		            return array('status'=>0,'msg'=>'图片格式错误,仅支持png,jpeg,gif,jpg');
		        }
		        $imageContent = base64_decode( $imageContent );
		        return array('status'=>1,'data'=>array('type'=>$imageType,'content'=>$imageContent),'msg'=>'','msgcode'=>0);
    	}



}