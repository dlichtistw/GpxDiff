<?php
require_once 'WptDiff.php';

class GpxDocument {
	public $wpts = array();
	public $rtes = array();
	public $trks = array();
	
	private $document;
	
	public function __construct () {
		$this -> document = new DOMDocument();
	}
	
	public function fromFile ($filename) {
		$this -> document -> load($filename);
	}
	public function toFile ($filename) {
		$this -> document -> save($filename);
	}
	
	public function totalAvg ($addAsWpt = true, $track = true, $segment = true) {
		$sum = array(
			'lat' => 0,
			'latc' => 0,
			'lon' => 0,
			'lonc' => 0,
			'ele' => 0,
			'elec' => 0,
			'count' => 0
		);
		
		print 'Calculating average ...' . "\n";

		$trks = $this -> document -> getElementsByTagName('trk');
		if ($track === true) {
			for ($i = 0; $i < $trks -> length; $i++) {
				print "\t" . '... on track ' . ($i + 1) . "\n";
				$this -> trkSum($trks -> item($i), $sum, $segment);
			}
		} else {
			print "\t" . '... on track ' . ($track + 1) . "\n";
			$this -> trkSum($trks -> item($track), $sum, $segment);
		}
		
		$avg = array(
			'lat' => 0,
			'lon' => 0
		);
		if ($sum['latc'] > 0) {
			$avg['lat'] = $sum['lat'] / $sum['latc'];
		}
		if ($sum['lonc'] > 0) {
			$avg['lon'] = $sum['lon'] / $sum['lonc'];
		}
		
		// Add a waypoint at the average position to the end of the GPX document if so requested
		if ($addAsWpt) {
			$rangeDesc = '';
			if ($segment === true) {
				$rangeDesc .= 'all segments';
			} else {
				$rangeDesc .= 'segment ' . $segment;
			}
			if ($track === true) {
				$rangeDesc .= ' of all tracks';
			} else {
				$rangeDesc .= ' of track ' . $track;
			}
		
			$average = $this -> document -> createElement('wpt');
			$average -> setAttribute('lat', $avg['lat']);
			$average -> setAttribute('lon', $avg['lon']);
			$average -> appendChild($this -> document -> createElement('src', 'generated'));
			$average -> appendChild($this -> document -> createElement('name', 'average over ' . $rangeDesc . ' (' . $sum['count'] . ' points)'));
			$this -> document -> getElementsByTagName('gpx') -> item(0) -> appendChild($average);
		}
		
		print 'Done calculating average:' . "\n";
		print "\t" . 'Latitude  = ' . $avg['lat'] . "\n";
		print "\t" . 'Longitude = ' . $avg['lon'] . "\n";
		print "\n";
		
		return $avg;
	}
	private function trkSum ($trk, &$sum, $segment = true) {
		$trksegs = $trk -> getElementsByTagName('trkseg');
		if ($segment === true) {
			for ($i = 0; $i < $trksegs -> length; $i++) {
				print "\t" . '... on segment ' . ($i + 1) . "\n";
				$this -> trksegSum($trksegs -> item($i), $sum);
			}
		} else {
			print "\t" . '... on segment ' . ($segment + 1) . "\n";
			$this -> trksegSum($trksegs -> item($segment), $sum);
		}
	}
	private function trksegSum ($trkseg, &$sum) {
		$trkpts = $trkseg -> getElementsByTagName('trkpt');
		foreach ($trkpts as $trkpt) {
			$lat = $trkpt -> getAttribute('lat');
			if (!empty($lat)) {
				$sum['lat'] += $lat;
				$sum['latc']++;
			}
			$lon = $trkpt -> getAttribute('lon');
			if (!empty($lon)) {
				$sum['lon'] += $lon;
				$sum['lonc']++;
			}
			$sum['count']++;
		}
	}
	
	public function totalDiff ($ref, $track = true, $segment = true) {
		$diffs = array();
		$sum = array(
			'latd' => 0,
			'lond' => 0,
			'eled' => 0,
			'count' => 0,
		);
		
		print 'Extracting differences ...' . "\n";

		$trks = $this -> document -> getElementsByTagName('trk');
		if ($track === true) {
			for ($i = 0; $i < $trks -> length; $i++) {
				print "\t" . '... from track ' . ($i + 1) . "\n";
				$this -> trkDiff($trks -> item($i), $ref, $diffs, $sum, $segment);
			}
		} else {
			print "\t" . '... from track ' . ($track + 1) . "\n";
			$this -> trkDiff($trks -> item($track), $ref, $diffs, $sum, $segment);
		}
		
		$err = array(
			'latd' => 0,
			'lond' => 0,
			'eled' => 0,
			'totd' => 0
		);
		if ($sum['count'] > 0) {
			$err['latd'] = sqrt($sum['latd']) / $sum['count'];
			$err['lond'] = sqrt($sum['lond']) / $sum['count'];
			$err['eled'] = sqrt($sum['eled']) / $sum['count'];
			$err['totd'] = sqrt($sum['latd'] + $sum['lond'] + $sum['eled']) / (3 * $sum['count']);
		}
		
		print 'Done extracting differences:' . "\n";
		print "\t" . '... Latitude error  = ' . $err['latd'] . "\n";
		print "\t" . '... Longitude error = ' . $err['lond'] . "\n";
		print "\t" . '... Total error     = ' . $err['totd'] . "\n";
		
		return $diffs;
	}
	private function trkDiff ($trk, $ref, &$diffs, &$sum, $segment) {
		$trksegs = $trk -> getElementsByTagName('trkseg');
		if ($segment === true) {
			for ($i = 0; $i < $trksegs -> length; $i++) {
				print "\t" . '... from segment ' . ($i + 1) . "\n";
				$this -> trksegDiff($trksegs -> item($i), $ref, $diffs, $sum);
			}
		} else {
			print "\t" . '... from segment ' . ($segment + 1) . "\n";
			$this -> trksegDiff($trksegs -> item($segment), $ref, $diffs, $sum);
		}
	}
	private function trksegDiff ($trkseg, $ref, &$diffs, &$sum) {
		$trkpts = $trkseg -> getElementsByTagName('trkpt');
		foreach ($trkpts as $trkpt) {
			$timeEls = $trkpt -> getElementsByTagName('time');
			if ($timeEls -> length) {
				$timeStr = $timeEls -> item(0) -> nodeValue;
				if ($time = date_create($timeStr, new DateTimeZone('UTC')) === false) {
					continue;
				}
			} else {
				continue;
			}
			$lat = $trkpt -> getAttribute('lat');
			if (!empty($lat)) {
				$latd = $lat - $ref['lat'];
			} else {
				continue;
			}
			$lon = $trkpt -> getAttribute('lon');
			if (!empty($lon)) {
				$lond = $lat - $ref['lon'];
			} else {
				continue;
			}
			
			$diffs[] = new WptDiff($time, $latd, $lond);
			$sum['latd'] += $latd * $latd;
			$sum['lond'] += $lond * $lond;
			$sum['count']++;
		}
	}
	
	public function __destruct () {
	}
}
?>