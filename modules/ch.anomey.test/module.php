<?php

class AnomeyTestModule extends Module {
	public function invoke() {		
		$this->getModule('ch.anomey.view')->display('ch.anomey.view.example');
	}
}

?>