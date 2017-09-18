<!DOCTYPE html>
<html lang="en" style="height: 100%">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="cache-control" content="max-age=0" />
	<meta http-equiv="cache-control" content="no-cache" />
	<meta http-equiv="expires" content="0" />
	<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
	<meta http-equiv="pragma" content="no-cache" />
	<title>Document</title>
	<style>
body {
  font-family: "Open Sans", sans-serif;
  line-height: 1.25;
}
table {
  border: 1px solid #ccc;
  border-collapse: collapse;
  margin: 0;
  padding: 0;
  width: 100%;
  table-layout: fixed;
}
table caption {
  font-size: 1.5em;
  margin: .5em 0 .75em;
}
table tr {
  background: #f8f8f8;
  border: 1px solid #ddd;
  padding: .35em;
}
table th,
table td {
  padding: .625em;
  text-align: center;
}
table th {
  font-size: .85em;
  letter-spacing: .1em;
  text-transform: uppercase;
}
@media screen and (max-width: 600px) {
  table {
    border: 0;
  }
  table caption {
    font-size: 1.3em;
  }
  table thead {
    border: none;
    clip: rect(0 0 0 0);
    height: 1px;
    margin: -1px;
    overflow: hidden;
    padding: 0;
    position: absolute;
    width: 1px;
  }
  table tr {
    border-bottom: 3px solid #ddd;
    display: block;
    margin-bottom: .625em;
  }
  table td {
    border-bottom: 1px solid #ddd;
    display: block;
    font-size: .8em;
    text-align: right;
  }
  table td:before {
    /*
    * aria-label has no advantage, it won't be read inside a table
    content: attr(aria-label);
    */
    content: attr(data-label);
    float: left;
    font-weight: bold;
    text-transform: uppercase;
  }
  table td:last-child {
    border-bottom: 0;
  }
}
	</style>
</head>
<body style="margin: 0px;padding: 0px;height: 100%;margin-right: 5vw;margin-left: 5vw;padding-bottom: 10vw;">
<?php
require '../includes/db.php';
require '../includes/class.Meter.php';
error_reporting(-1);
ini_set('display_errors', 'On');
define('DATA_OK', 0); // green
define('DATA_NULL', 1); // orange
define('DATA_MISSING', 2); // red
define('RV_DATA', 3); // blue
define('STORAGE_DURATIONS', array('live' => 7200, 'quarterhour' => 1209600, 'hour' => 5184000, 'month' => 63113904));
define('INCREMENTS', array('live' => 60, 'quarterhour' => (60*15), 'hour' => (60*60), 'month' => (60*60*24*30)));
date_default_timezone_set('America/New_York');
if (!isset($_GET['meter_id']) || !isset($_GET['res'])) {
	die('Must provide meter_id and res parameters');
}
$time = time();
$weekend = in_array(date('w'), array(0, 6));
$days = ($weekend) ? '1, 7' : '2, 3, 4, 5, 6';
$limit = ($weekend) ? 5 : 7;
$stmt = $db->prepare("SELECT id FROM meter_data WHERE meter_id = ? AND value IS NOT NULL AND resolution = 'hour' AND HOUR(FROM_UNIXTIME(recorded)) = HOUR(NOW()) AND DAYOFWEEK(FROM_UNIXTIME(recorded)) IN ({$days}) ORDER BY recorded DESC LIMIT {$limit}");
$stmt->execute(array($_GET['meter_id']));
$rv_data = array_column($stmt->fetchAll(), 'id');
$increment = INCREMENTS[$_GET['res']];
$start = $time - STORAGE_DURATIONS[$_GET['res']];
while ($start % $increment !== 0) {
	++$start;
}
$expected_time = array();
$actual_time = array();
$value = array();
$status = array();
$stmt = $db->prepare('SELECT id, value, recorded FROM meter_data WHERE meter_id = ? AND resolution = ? AND recorded >= ? ORDER BY recorded ASC');
$stmt->execute(array($_GET['meter_id'], $_GET['res'], $start));
$data = $stmt->fetchAll();
$count = count($data);
$i = 0;
// while ($start > $data[$i++]['recorded']); // advance index to first point required by data
while ($start < $time) {
	if (($start + $increment) < $data[$i]['recorded']) { // if the next data point does not fall in the span of the currently considered interval
		$expected_time[] = $start;
		$actual_time[] = 0;
		$value[] = null;
		$status[] = DATA_MISSING;
	} else { // this data point falls within the current interval
		$expected_time[] = $start;
		$actual_time[] = $data[$i]['recorded'];
		$value[] = $data[$i]['value'];
		if (in_array($data[$i]['id'], $rv_data)) {
			$status[] = RV_DATA;
		} else {
			if ($data[$i]['value'] === null) {
				$status[] = DATA_NULL;
			} else {
				$status[] = DATA_OK;
			}
		}
		$i++;
		if ($i === $count) {
			break;
		}
	}
	$start += $increment;
}
if (!isset($_GET['view']) || $_GET['view'] === 'gradient') {
	$linear_gradient = 'linear-gradient(to right, ';
	foreach ($status as $s) {
		switch ($s) {
			case 0:
				$linear_gradient .= ' green,';
				break;
			case 1:
				$linear_gradient .= ' orange,';
				break;
			case 2:
				$linear_gradient .= ' red,';
				break;
			case 3:
				$linear_gradient .= ' blue,';
				break;
		}
	}
	$linear_gradient = substr($linear_gradient, 0, -1) . ');';
	echo "<div style='height:100%;width:100%;background: {$linear_gradient}'></div>";
} elseif ($_GET['view'] === 'table') {
	echo '<table class="table">
  <thead>
    <tr>
      <th>Expected point</th>
      <th>Existing point</th>
      <th>Value</th>
      <th>Typical</th>
    </tr>
  </thead>
  <tbody>';
  // $values = array_column($data, 'value', 'id');
  // $times = array_column($data, 'recorded', 'id');
  $typical = array();
  for ($j = 0; $j < count($expected_time); $j++) { 
  	echo "<tr><th scope='row'>";
  	echo date('n/j/y g:i.s a', $expected_time[$j]);
  	echo "</th><td>";
  	echo ($status[$j] === DATA_MISSING) ? 'None' : date('n/j/y g:i.s a', $actual_time[$j]);
  	echo ($status[$j] === DATA_MISSING || $status[$j] === DATA_NULL) ? "</td><td>NULL</td>" : "</td><td>{$value[$j]}</td>";
  	if ($status[$j] === RV_DATA) {
  		echo "<td>Yes</td>";
  		$typical[] = $value[$j];
  	} else {
  		echo "<td>No</td>";
  	}
    echo "</tr>";
  }
  echo '</tbody></table>';
  if ($_GET['res'] === 'hour') {
  	$stmt = $db->prepare('SELECT current FROM meters WHERE id = ?');
	  $stmt->execute(array($_GET['meter_id']));
	  $current = $stmt->fetchColumn();
	  $meter = new Meter($db);
	  $rv = $meter->relativeValue($typical, $current);
	  sort($typical);
	  echo "<h3>Relative value calculation</h3><p>Current: {$current}</p><p>Typical data: ".implode(', ', $typical)."</p><p>Relative value: {$rv}</p>";
  }
}
// print_r($processed_data);
// $image = new Imagick();
// $image->newImage(400, 100, new ImagickPixel('white'));
// $image->setImageFormat('png');
// header('Content-type: image/png');
// echo $image;
?>
</body>
</html>
