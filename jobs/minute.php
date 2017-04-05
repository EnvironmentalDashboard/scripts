<?php
error_reporting(-1);
ini_set('display_errors', 'On');
date_default_timezone_set('America/New_York');
chdir(__DIR__);
require '../../includes/db.php'; // Has $db
require '../../includes/class.BuildingOS.php';
require '../../includes/class.Meter.php';
require '../../scripts/cron.php';
$res = 'live';
$amount = strtotime('-2 hours');
echo '<pre>';
$meter = new Meter($db);
foreach ($db->query('SELECT api_id FROM users ORDER BY RAND()') as $user) {
  $bos = new BuildingOS($db, $user['api_id']);
  cron($db, $bos, $meter, $res, $amount, true, false, true);
}

// Custom scrips
// $interval = 'minute';
// foreach ($db->query('SELECT id, url FROM meters WHERE active_gauges > 0 AND source = \'user\'') as $meter) {
//   $id = $meter['id'];
//   include 'user/' . $meter['url'];
// }
?>