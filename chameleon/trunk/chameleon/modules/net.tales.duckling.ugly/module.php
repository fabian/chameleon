<?php

class UglyDuckingProcessor implements AnomeyProcessor {
	public function __construct(Request $request) {
		
	}
	
	public function process() {
		echo 'Quack quack quack!';
	}
}

?>
