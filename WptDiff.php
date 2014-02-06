<?php
/*
*
* This file is part of the GpxDiff project and licensed under the terms of the BSD license.
*
* Feel free to use/modify/redistribute this. I would appreciate, if you would give feedback on your usage or even contribute improvements.
*
*/

class WptDiff {
	// Compares two instances of WptDiff and returns the time difference in seconds ($a - $b)
	static function diffComp ($a, $b) {
		return $a -> time - $b -> time;
	}

	public function __construct ($time, $lat, $lon, $ele = 0) {
		$this -> time = $time;
		$this -> lat = $lat;
		$this -> lon = $lon;
		$this -> ele = $ele;
	}
}
?>