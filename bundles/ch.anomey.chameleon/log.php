<?php

class Log {
	
	private $file = '';
	
	private $level = self::TRACE;
	
	const ERROR = 1;
	
	const WARN = 3;
	
	const INFO = 7;
	
	const TRACE = 15;

	public function __construct($file, $level = self::TRACE) {
		$this->file = $file;
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
		if(!file_exists($this->file)) {
			touch($this->file);
		}
		
		file_put_contents($this->file, date('c') . ' '.$type.' ' . $message . "\n", FILE_APPEND);
	}
}

?>
