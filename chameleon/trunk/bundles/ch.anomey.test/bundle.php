<?php

class AnomeyTestBundle extends Bundle {
	public function invoke() {		
		$this->getBundle('ch.anomey.view')->display('ch.anomey.view.example');
	}
}

?>
