<?php

namespace main\Cache;

interface Cache{
		/*获取key缓存数据*/
		public function read($key,$timeout);
		/*缓存数据$data写入$key*/
		public function write($key,$data);
		/*清除key缓存数据*/
		public function delete($key);
		/*清除key缓存所处的目录下的同级缓存*/
		public function clear($key);
		/*清除workspace应用下所有缓存*/
		public function clearAll();
}
