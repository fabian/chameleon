<?php

class Profile {
	
	private $name;
	
	/**
	 * @var Vector
	 */
	private $hosts;
	
	public function __construct($name) {
		$this->name = $name;
		$this->hosts = new Vector();
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getHosts() {
		return $this->hosts;
	}
}

?>