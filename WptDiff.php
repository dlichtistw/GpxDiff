<?php
class WptDiff {
	public function __construct ($time, $lat, $lon, $ele = 0) {
		$this -> time = $time;
		$this -> lat = $lat;
		$this -> lon = $lon;
		$this -> ele = $ele;
	}
}
?>