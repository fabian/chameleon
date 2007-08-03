#!/usr/bin/php

<?php

if (isset( $_SERVER ) && array_key_exists( 'REQUEST_METHOD', $_SERVER ) ) {
	echo "This script must be run from the command line\n";
} else {
	
	$auto = false;
	
	if(!$auto) {
		echo "The following bundles will be upgraded:\n";
		echo "  ch.anomey.framework ch.anomey.security\n";
		echo "Do you want to continue [Y/n]? ";
	
		$answer = "";
		fscanf(STDIN, "%c\n", $answer);
		if(empty($answer)) {
			$answer = "Y";
		}
	} else {
		$answer = "Y";
	}
	
	if(strtoupper($answer) == "Y") {
		echo "Updating.";
		for($i = 0; $i < 10; $i++) {
			usleep(250000);
			echo ".";
		}
		echo " Finished!\n";
	} else {
		echo "Aborted!\n";
	}
}

?>
