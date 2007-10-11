<?php

class AnomeyActionWebProcessor extends AnomeyWebProcessor {
	
	public function process() {
		$trail = $this->getRequest()->getTrail();
		
		echo $this->trail;
	}
}

?>
