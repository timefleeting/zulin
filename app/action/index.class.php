<?php

namespace action;

class index extends core{
		
		function _init(){}
		public function index(){
				redirect(cUri('order','index'));
		}
		
}