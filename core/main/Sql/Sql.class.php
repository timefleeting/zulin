<?php

namespace main\Sql;

interface Sql{
		public function init($config);
		public function connect();
		public function query($query);
}
