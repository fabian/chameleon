<?php

class Log {
	
	private $bundle = '';
	
	private $level = self::TRAIL;

	const TMP = 'tmp';

	const FILE = 'tmp/chameleon.log';
	
	const ERROR = 1;
	
	const WARN = 3;
	
	const INFO = 7;
	
	const TRAIL = 15;

	public function __construct($bundle, $level = self::TRAIL) {
		$this->bundle = $bundle;
		$this->level = $level;
	}
	
	public function getLevel() {
		return $this->level;
	}
	
	public function error($message) {
		if($this->level & 1) {
			$this->write('ERROR', $message);
		}
	}
	
	public function warn($message) {
		if($this->level & 2) {
			$this->write('WARN ', $message);
		}
	}
	
	public function info($message) {
		if($this->level & 4) {
			$this->write('INFO ', $message);
		}
	}
	
	public function trail($message) {
		if($this->level & 8) {
			$this->write('TRAIL', $message);
		}
	}
	
	private function write($type, $message) {
		if(!file_exists(self::TMP)) {
			mkdir(self::TMP);
		}
		
		if(!file_exists(self::FILE)) {
			touch(self::FILE);
		}
		
		file_put_contents(self::FILE, date('c') . ' '.$type.' [' . $this->bundle . '] ' . $message . "\n", FILE_APPEND);
	}
}

?>
