<?php
#!/usr/local/bin/php
error_reporting(-1);
set_time_limit(0);
ini_set('display_errors', 'On');
date_default_timezone_set('America/New_York');
chdir(__DIR__);
require '../../includes/db.php'; // Has $db
require '../../includes/class.BuildingOS.php';
require '../../includes/class.Meter.php';
// require '../../scripts/cron.php';
$res = 'quarterhour';
$amount = strtotime('-2 weeks');
// $meter = new Meter($db);
// foreach ($db->query('SELECT id, api_id FROM users ORDER BY RAND()') as $user) {
//   $bos = new BuildingOS($db, $user['api_id']); // BuildingOS class contains methods that fetch data from API using API credentials associated with the api_id
//   cron($db, $bos, $meter, $res, $amount, $user['id'], false, false, false); // Will update all the meters associated with the $user_id and id IN (SELECT meter_id FROM saved_chart_meters) OR id IN (SELECT meter_id FROM gauges) OR bos_uuid IN (SELECT elec_uuid FROM orbs) OR bos_uuid IN (SELECT water_uuid FROM orbs) OR bos_uuid IN (SELECT DISTINCT meter_uuid FROM relative_values WHERE permission = 'orb_server')
// }

$options = getopt('', array('user_id:'));
if (array_key_exists('user_id', $options)) {
  echo "quarterhour.php: Updating meters with {$res}-res data for user #{$options['user_id']}\n";
  echo date('F j, Y, g:i a') . "\n";
  $bos = new BuildingOS($db, $options['user_id']);
  $meter = new Meter($db);
  $bos->cron($meter, $res, $amount);
} else {
  echo "quarterhour.php: Please provide a user_id\n";
}
?>