<?php

// Get Current Time
$mtime = microtime();
// Split Seconds and Microseconds
$mtime = explode (" ", $mtime);
// Create a single value for start time
$mtime = $mtime[1] + $mtime[0];
// Write Start Time Into A Variable
$tstart = $mtime;

require_once 'loader.php';

// initialize the chameleon and invoke bundles
$chameleon = new Chameleon('profiles');
$chameleon->invoke();

// Get current time (Like above) to get end time
$mtime = microtime();
$mtime = explode (" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
// Store end time in a variable
$tend = $mtime;
// Calculate Difference
$totaltime = ($tend - $tstart);
// Output the result
printf ("<br/>Page loaded in %f seconds!", $totaltime);

?>
