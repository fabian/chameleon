#!/usr/bin/php
<?php

if (isset($_SERVER) && array_key_exists('REQUEST_METHOD', $_SERVER) ) {
	echo "This script must be run from the command line.\n";
} else {
	require_once 'loader.php';
	
	// initialize the chameleon and invoke bundles
	$chameleon = new Chameleon();
	$chameleon->invoke();
}

?>
