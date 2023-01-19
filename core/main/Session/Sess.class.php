<?php

namespace main\Session;

interface Sess{
		public function open($path,$name);
		public function read($sessId);
		public function write($sessId,$data);
		public function close();
		public function destroy($sessId);
		public function gc($maxlifetime);
}
