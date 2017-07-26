<?php
#!/usr/local/bin/php
chdir(__DIR__);
require '../includes/db.php';
require '../includes/class.Meter.php';
require '../includes/class.BuildingOS.php';
$ok = true;
if (php_sapi_name() === 'cli') { // script is being run from command line
  $options = getopt('', array('api_id:', 'meter_id:', 'meter_uuid:', 'meter_url:', 'res:'));
  foreach (array('api_id', 'meter_id', 'meter_uuid', 'meter_url', 'res') as $required_option) {
    if (!array_key_exists($required_option, $options)) {
      $ok = false;
      break;
    }
  }
  if ($ok) {
    $bos = new BuildingOS($db, $options['api_id']);
    var_dump($bos->updateMeter($options['meter_id'], $options['meter_uuid'], $options['meter_url'] . '/data', $options['res'], new Meter($db), true));
  } else {
    echo 'Usage: php update-meter.php --api_id="1" --meter_id="800" --meter_uuid="oberlin_city_prospect_main_e" --meter_url="https://api.buildingos.com/meters/oberlin_city_prospect_main_e" --res="live"';
    echo "\n";
  }
} else { // Take parameters via GET
  header('Content-Type: application/json;charset=utf-8');
  $options = array();
  foreach (array('api_id', 'meter_id', 'meter_uuid', 'meter_url', 'res') as $required_option) {
    if (isset($_GET[$required_option])) {
      $options[$required_option] = $_GET[$required_option];
    } else {
      $ok = false;
      break;
    }
  }
  if ($ok) {
    $bos = new BuildingOS($db, $options['api_id']);
    $bos->updateMeter($options['meter_id'], $options['meter_uuid'], $options['meter_url'] . '/data', $options['res'], new Meter($db), true);
  } else {
    echo json_encode(array('Error'=>'Not all required options given'));
  }
}
?>