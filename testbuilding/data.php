<?php
header('Content-Type: application/json');
error_reporting(-1);
ini_set('display_errors', 'On');
date_default_timezone_set('America/New_York');

// Multiplies the hour of the day by the % through the data we're outputting
// Data will get smaller as time goes on
// function test_data($now, $start, $end) {
//   $total_amt = $end - $start;
//   $amt_left = $end - $now;
//   return date('G', $now) * ($amt_left/$total_amt);
// }

$data = array();
$start = strtotime($_GET['start']);
$static_start = $start;
$end = strtotime($_GET['end']);

if ($_GET['resolution'] === 'live') {
  $interval = 60;
} else if ($_GET['resolution'] === 'quarterhour') {
  $interval = 900;
} else if ($_GET['resolution'] === 'hour') {
  $interval = 3600;
} else if ($_GET['resolution'] === 'month') {
  $interval = 2628002; // seconds in month
} 
while ($start % $interval !== 0) { // Find the closest minute/quarterhour/hour/month
  $start--;
}
while ($start < $end) { // Fill the array until we hit $end
  // $data[] = array('value' => test_data($start, $static_start, $end), 'localtime' => date('c', $start));
  $data[] = array('value' => 7, 'localtime' => date('c', $start));
  $start += $interval;
}

$output = array(
  'meta' => array(
    'units' => array(
      'value' => array(
        'id' => '0',
        'displayName' => 'Kilowatts',
        'shortName' => 'Kw'
      )
    ),
    'definitions' => array(
      'unitsCost' => null,
      'resolution' => null,
      'unitsValue' => null,
    ),
    'resolution' => array(
      'displayName' => $_GET['resolution'],
      'slug' => $_GET['resolution'],
      'id' => '0'
    )
  ),
  'data' => $data
  );
echo json_encode($output, JSON_PRETTY_PRINT);
?>