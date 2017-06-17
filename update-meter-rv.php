<?php
#!/usr/local/bin/php
chdir(__DIR__);
require '../includes/db.php';
require '../includes/class.Meter.php';
require '../includes/class.BuildingOS.php';
$ok = true;
if (php_sapi_name() === 'cli') { // script is being run from command line
  $options = getopt('', array('meter_id:', 'grouping:', 'current:'));
  foreach (array('meter_id', 'grouping') as $required_option) {
    if (!array_key_exists($required_option, $options)) {
      $ok = false;
      break;
    }
  }
  if ($ok) {
    $meter = new Meter($db);
    if (array_key_exists('current', $options)) {
      $meter->updateRelativeValueOfMeter($options['meter_id'], $options['grouping'], $options['current'], true);
    } else {
      $meter->updateRelativeValueOfMeter($options['meter_id'], $options['grouping'], null, true);
    }
  } else {
    echo 'Usage: php update-meter-rv.php --meter_id="800" --grouping=\'[{"days":[1,2,3,4,5],"npoints":8},{"days":[1,7],"npoints":5}]\'';
    echo "\n";
  }
} else { // Take parameters via GET
  header('Content-Type: application/json;charset=utf-8');
  $options = array();
  foreach (array('meter_id', 'grouping') as $required_option) {
    if (isset($_GET[$required_option])) {
      $options[$required_option] = $_GET[$required_option];
    } else {
      $ok = false;
      break;
    }
  }
  if ($ok) {
    $meter = new Meter($db);
    $meter->updateRelativeValueOfMeter($options['meter_id'], $options['grouping'], null, true);
  } else {
    echo json_encode(array('Error'=>'Must provide a meter id and grouping'));
  }
}
?>