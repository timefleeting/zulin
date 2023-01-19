<?php

namespace main\Storage;

interface Storage{
		public function read();
		public function write($key,$data);
		public function delete($key);
		public function clear();
}