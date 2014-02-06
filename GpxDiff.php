<?php
require 'GpxDocument.php';

$debug = false;

$ifname = '';
$idoc = new GpxDocument();
$rfname = '';
$rdoc = new GpxDocument();
$ofname = '';

// Convert Errors into catchable Exceptions
function exception_error_handler ($errno, $errstr, $errfile, $errline ) {
	throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("exception_error_handler");

// First, parse command line arguments
for ($i = 1; $i < $argc; $i++) {
	switch ($argv[$i]) {
	case '--debug':
		$debug = true;
		print '$argv = ' . print_r($argv, true);
		break;
	case '-i':
	case '--input':
		if ($i + 1 == $argc || $argv[$i + 1][0] == '-') {
			echo 'Warning: No input file specified' . "\n";
			exit();
		} else {
			$ifname = $argv[++$i];
		}
		break;
	case '-r':
	case '--reference':
		if ($i + 1 == $argc || $argv[$i + 1][0] == '-') {
			echo 'Warning: No reference file specified' . "\n";
		} else {
			$rfname = $argv[++$i];
		}
		break;
	case '-o':
	case '--output':
		$ofname = $argv[++$i];
		break;
	}
}

// Now, try to open the files
if (!empty($ifname)) {
	try {
		print 'Load input file ' . $ifname . "\n";
		$idoc -> fromFile($ifname);
	} catch (Exception $e) {
		print 'Warning: Could not open input file ' . $ifname . "\n";
		exit();
	}
}
if (!empty($rfname)) {
	try {
		$rdoc -> fromFile($rfname);
	} catch (Exception $e) {
		print 'Warning: Could not open reference file ' . $ifname . "\n";
		exit();
	}
}

print 'Done loading files' . "\n\n";

$ref = $rdoc -> totalAvg(true);
$diffs = $rdoc -> totalDiff($ref);
$rdoc -> applyDiff($diffs);

if (!empty($ofname)) {
	try {
		$rdoc -> toFile($ofname);
	} catch (Exception $e) {
		print 'Warning: Could not write output file ' . $ifname . "\n";
		exit();
	}
}
?>