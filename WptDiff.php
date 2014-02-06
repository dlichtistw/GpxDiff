<?php
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