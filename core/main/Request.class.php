<?php


namespace main;


class Request{

			public function post( $key=false,$value=true ){
					if(empty( $key ) ){
						 	unset( $_POST );
						 	return false;
					}
					$data = array();
					if( $value ===false ){
							unset( $_POST[$key] );
					}elseif( $value!==true && !empty( $value )){
						  $_POST[$key] = $value;
					}
					$data = isset( $_POST ) && !empty( $_POST ) ? $_POST : false;
					return $this->parse($data,$key);
			}

			public function get( $key=false,$value=true ){
                    if(empty( $key ) ){
						 	unset( $_GET );
						 	return false;
					}
					$data = array();
					if( $value ===false ){
							unset( $_GET[$key] );
					}elseif( $value!==true && !empty( $value )){
						  $_GET[$key] = $value;
					}
					$data = isset( $_GET ) && !empty( $_GET ) ? $_GET : false;
					return $this->parse($data,$key);
			}

			public function request( $key=false,$value=true ){
                    if(empty( $key ) ){
						 	unset( $_REQUEST );
						 	return false;
					}
					$data = array();
					if( $value ===false ){
							unset( $_REQUEST[$key] );
					}elseif( $value!==true && !empty( $value )){
						  $_REQUEST[$key] = $value;
					}
					$data = isset( $_REQUEST ) && !empty( $_REQUEST ) ? $_REQUEST : false;
					return $this->parse($data,$key);
			}
			public function requestPayload( $key=false,$value=true  ){
					$data = array();
					if ( isset($GLOBALS['HTTP_RAW_POST_DATA'] )) {
						    $final = $GLOBALS['HTTP_RAW_POST_DATA'];
					} else {
						    $final = file_get_contents('php://input');
					}
					if( !empty( $final )){
							$finalArray = $this->fromXml( $final );
							if( $value===false){
								unset( $finalArray[$key] );
							}elseif( $value !==true && !empty($value ) ){
								$finalArray[$key] = $value;
							}
		                    $data = $finalArray;
					}
					return $this->parse($data,$key);
			}
			/**
			 * [input description]
			 * php://input不能用于enctype=multipart/form-data
			 * @param  boolean $key [description]
			 * @return [type]       [description]
			 */
			public function put( $key=false ){
					parse_str(file_get_contents('php://input'), $data);
					return $this->parse( $data,$key );
			}

			public function param( $key=false, $value=true ){
					$method = strtoupper( $_SERVER['REQUEST_METHOD'] );
					$data   = array();
					switch( $method ) {
			                case 'POST':
			                    	$data  =  $this->post( $key,$value );
			                    break;
			                case 'GET':
			                		$data  =  $this->get( $key,$value );
			                	break;
			                case 'PUT':
			                    	$data  =  $this->put( $key );
			                    break;
			                default:
			                    	$data  =  $this->request( $key,$value );
			                    	break;
			       }
			       if(empty( $data )){
			       		$data = $this->requestPayload( $key,$value );
			       }
			       return $data;
			}

			public function delete( $key=false ){

			}
			public function file( $key=false ){

			}

			public function parse( $data=[], $key=false ){
					$data = $this->filter( $data );
					if ($key===true) {
			            return $data;
			        }
			        if($key===false){
			        	return false;
			        }
			        $key = (string) $key;
			        return isset( $data[$key] ) ? $data[$key] : false;
			}

			/**
		     * 过滤数据
		     * @param mixed $data 需要过滤的数据
		     * @return array
		     */
		    public function filter( $data=[] )
		    {
		        	return $data;	
		    }

		    function array_map_recursive($filter, $data) {
			    $result = array();
			    foreach ($data as $key => $val) {
			        $result[$key] = is_array($val)
			         ? array_map_recursive($filter, $val)
			         : call_user_func($filter, $val);
			    }
			    return $result;
			}

			/**
			 * 输出xml字符
			 * @throws WxPayException
			**/
			public function toXml($datas)
			{
				if(!is_array($datas) 
					|| count($datas) <= 0)
				{
		    		return false;
		    	}
		    	
		    	$xml = "<xml>";
		    	foreach ($datas as $key=>$val)
		    	{
		    		if (is_numeric($val)){
		    			$xml.="<".$key.">".$val."</".$key.">";
		    		}else{
		    			$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
		    		}
		        }
		        $xml.="</xml>";
		        return $xml; 
			}
			/**
		     * 判断是否为xml并将xml转为array
		     * @param string $xml
		     * @throws WxPayException
		     */
			public function fromXml($xml)
			{	
				if(!$xml){
					return false;
				}
				 $xml_parser = xml_parser_create(); //建立一个新的 XML 解析器并返回可被其它 XML 函数使用的资源句柄
				 if(!xml_parse($xml_parser,$xml,true)){  
				 		xml_parser_free($xml_parser); 
				 		$data =json_decode( $xml ,true);
            			return $data; 
				 }
		        //将XML转为array
		        //禁止引用外部xml实体
		        libxml_disable_entity_loader(true);
		        $datas = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);		
				return $datas;
			}


}