<?php
error_reporting(-1);
ini_set('display_errors', 'On');
date_default_timezone_set('America/New_York');
require '/var/www/html/oberlin/includes/db.php'; // Has $db
require '/var/www/html/oberlin/includes/class.BuildingOS.php';
require '/var/www/html/oberlin/includes/class.Meter.php';
require '/var/www/html/oberlin/scripts/cron.php';
$res = 'live';
$amount = strtotime('-2 hours');
echo '<pre>';
$bos = new BuildingOS($db);
$meter = new Meter($db);
cron($db, $bos, $meter, $res, $amount, true, false, false);

// Custom scrips
// $interval = 'minute';
// foreach ($db->query('SELECT id, url FROM meters WHERE active_gauges > 0 AND source = \'user\'') as $meter) {
//   $id = $meter['id'];
//   include 'user/' . $meter['url'];
// }
?>