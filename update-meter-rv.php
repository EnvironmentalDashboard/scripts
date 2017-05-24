<?php
#!/usr/local/bin/php
chdir(__DIR__);
require '../includes/db.php';
require '../includes/class.Meter.php';
require '../includes/class.BuildingOS.php';
$options = getopt('', array('meter_id:', 'grouping:'));
$ok = true;
foreach (array('meter_id', 'grouping') as $required_option) {
  if (!array_key_exists($required_option, $options)) {
    $ok = false;
    break;
  }
}
if ($ok) {
  $meter = new Meter($db);
  var_dump(updateRelativeValueOfMeter($options['meter_id'], $options['grouping']));
} else {
  echo 'Usage: php update-meter-rv.php --meter_id="800" --grouping=\'[{"days":[1,2,3,4,5],"npoints":8},{"days":[1,7],"npoints":5}]\'';
  echo "\n";
}
?>