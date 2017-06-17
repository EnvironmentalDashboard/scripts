<?php
// This script serves as an API for anyone who wants to access the buoy data we're caching.
// It is not used by our system because meter data is inserted directly into meter_data by the other scripts in this directory
header('Content-Type: application/json');
date_default_timezone_set('America/New_York');
error_reporting(-1);
ini_set('display_errors', 'On');
require '../../includes/db.php';

$data = array();
$start = strtotime($_GET['start']);
$static_start = $start;
$end = strtotime($_GET['end']);
if (!isset($_GET['id'])) {
  throw new Exception("No meter id provided\n");
} else {
  $meter_id = $_GET['id'];
}

if ($_GET['resolution'] === 'live') {
  $interval = 120; // new buoy data is collected every 2 mins (see cron)
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
$stmt = $db->prepare('SELECT value FROM meter_data WHERE meter_id = ? AND resolution = ? AND recorded >= ? ORDER BY recorded ASC');
$stmt->execute(array($meter_id, $_GET['resolution'], $static_start));
$data = $stmt->fetchAll();
$i = 0;
while ($start < $end) { // Fill the array until we hit $end
  $data[] = array('value' => $data[$i++]['value'], 'localtime' => date('c', $start));
  $start += $interval;
}
$stmt = $db->prepare('SELECT units FROM meters WHERE meter_id = ?');
$stmt->execute(array($meter_id));
$units = $stmt->fetchColumn();
$output = array(
  'meta' => array(
    'units' => array(
      'value' => array(
        'id' => '0',
        'displayName' => $units,
        'shortName' => $units
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