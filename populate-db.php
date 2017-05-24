<?php
#!/usr/local/bin/php
error_reporting(-1);
ini_set('display_errors', 'On');
chdir(__DIR__);
require '../includes/db.php';
require '../includes/class.BuildingOS.php';
$options = getopt('', array('user_id:', 'api_id:'));
$ok = true;
foreach (array('user_id', 'api_id') as $required_option) {
  if (!array_key_exists($required_option, $options)) {
    $ok = false;
    break;
  }
}
if ($ok) {
  $bos = new BuildingOS($db, $options['api_id']);
  $bos->populateDB();
}
?>