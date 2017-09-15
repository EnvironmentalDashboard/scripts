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
</head>
<body style="margin: 0px;padding: 0px;height: 100%">
<?php
require '../includes/db.php';
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
  for ($j = 0; $j < count($expected_time); $j++) { 
  	echo "<tr><th scope='row'>";
  	echo date('n/j/y g:i.s a', $expected_time[$j]);
  	echo "</th><td>";
  	echo ($status[$j] === DATA_MISSING) ? 'None' : date('n/j/y g:i.s a', $actual_time[$j]);
  	echo ($status[$j] === DATA_MISSING || $status[$j] === DATA_NULL) ? "</td><td>NULL</td>" : "</td><td>{$value[$j]}</td>";
  	echo ($status[$j] === RV_DATA) ? "<td>Yes</td>" : "<td>No</td>";
    echo "</tr>";
  }
  echo '</tbody></table>';
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
