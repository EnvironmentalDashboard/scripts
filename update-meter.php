<?php
#!/usr/local/bin/php
chdir(__DIR__);
require '../includes/db.php';
require '../includes/class.Meter.php';
require '../includes/class.BuildingOS.php';
$options = getopt('', array('user_id:', 'meter_id:', 'meter_uuid:', 'meter_url:', 'res:'));
$ok = true;
foreach (array('user_id', 'meter_id', 'meter_uuid', 'meter_url', 'res') as $required_option) {
  if (!array_key_exists($required_option, $options)) {
    $ok = false;
    break;
  }
}
if ($ok) {
  $bos = new BuildingOS($db, $options['user_id']);
  var_dump($bos->updateMeter($options['meter_id'], $options['meter_uuid'], $options['meter_url'] . '/data', $options['res'], new Meter($db)));
} else {
  echo 'Usage: php update-meter.php --user_id="1" --meter_id="800" --meter_uuid="oberlin_city_prospect_main_e" --meter_url="https://api.buildingos.com/meters/oberlin_city_prospect_main_e" --res="live"';
  echo "\n";
}
?>