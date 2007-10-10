<?php

class AnomeyHTTPMedia implements AnomeyMedia {

	private $chameleon;
	
	public function __construct(Chameleon $chameleon) {
		$this->chameleon = $chameleon;
	}
	
	public function isActive() {
		return isset($_SERVER);
	}
	
	public function handle() {
		var_dump($_SERVER);
	}
}

?>
