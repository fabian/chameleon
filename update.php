#!/usr/bin/php
<?php

echo "The following bundles will be upgraded:\n";
echo "  ch.anomey.framework ch.anomey.security\n";
echo "Do you want to continue [Y/n]? ";

$answer = "";
fscanf(STDIN, "%c\n", $answer);
if(empty($answer)) {
	$answer = "Y";
}

if(strtoupper($answer) == "Y") {
	echo "Updating.";
	for($i = 0; $i < 10; $i++) {
		usleep(250000);
		echo ".";
	}
	echo " Finished!";
} else {
	echo "Aborted!";
}

?>
