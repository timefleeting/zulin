<?php

include('core/main.php');
$system = array(
		'apply'		=>array('value'=>'app'),
		'controller'=>array('value'=>'action'),
		'database'  =>array(
				'0' => array( 	 //
						'title'		=>'可写数据库配置',
						'type'		=> 'mysql',
						'charset'   => 'utf8',
			            'host'      => 'localhost',
			            'prefix'    => '',
			            'name'      => 'lease',
			            'user'      => 'xroot',
			            'pwd'       => 'xxxxxx',
			            'port'      =>  '3306',
	    		),
	    		'1' => array( 	 //
						'title'		=>'可写数据库配置',
						'type'		=> 'mysql',
						'charset'   => 'utf8',
			            'host'      => 'localhost',
			            'prefix'    => '',
			            'name'      => 'lease',
			            'user'      => 'xroot',
			            'pwd'       => 'xxxxxx',
			            'port'      =>  '3306',
	    		),
		),
);
main::run( $system );