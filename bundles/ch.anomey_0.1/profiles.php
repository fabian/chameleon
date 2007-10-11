<?php

class Profile {
	
	private $name;
	
	private $path;
	
	/**
	 * @var Vector
	 */
	private $hosts;
	
	public function __construct($name, $path) {
		$this->name = $name;
		$this->path = $path;
		$this->hosts = new Vector();
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getPath() {
		return $this->path;
	}
	
	/**
	 * @return Vector
	 */
	public function getHosts() {
		return $this->hosts;
	}
}

?>