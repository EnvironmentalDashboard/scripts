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
  updateMeter($meter_id, $meter_uuid, $meter_url, $res, $meterClass);
  $bos->updateMeter($options['meter_id'], $options['meter_uuid'], $options['meter_url'], $options['res'], new Meter($db));
} else {
  echo 'Usage: php update-meter.php --user_id="0" --meter_id="0" --meter_uuid="0" --meter_url="..." --res="live"';
  echo "\n";
}
?>