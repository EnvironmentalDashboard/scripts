<?php
#!/usr/local/bin/php
chdir(__DIR__);
require '../includes/db.php';
require '../includes/class.BuildingOS.php';
$options = getopt('', array('user_id:', 'meter_id:', 'res:', 'amount:'));
$ok = true;
foreach (array('user_id', 'meter_id', 'res', 'amount') as $required_option) {
  if (!array_key_exists($required_option, $options)) {
    $ok = false;
    break;
  }
}
if ($ok) {
  $bos = new BuildingOS($db, $options['user_id']);
  $bos->update_meter($options['meter_id'], $options['res'], $options['amount']);
} else {
  var_dump(array_intersect($options, $required_options));
  echo 'Usage: php update-meter.php --user_id="0" --meter_id="0" --res="live" --amount="0"';
  echo "\n";
}
?>