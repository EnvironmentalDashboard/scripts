<?php
error_reporting(-1);
ini_set('display_errors', 'On');
date_default_timezone_set('America/New_York');
set_time_limit(1800);
require '/var/www/html/oberlin/includes/db.php'; // Has $db
require '/var/www/html/oberlin/includes/class.BuildingOS.php';
require '/var/www/html/oberlin/scripts/cron.php';
$res = 'hour';
$amount = strtotime('-2 months');
echo '<pre>';
$bos = new BuildingOS($db);
cron($db, $bos, $res, $amount, false, false);

// Custom scrips
// $interval = 'hour';
// foreach ($db->query('SELECT id, url FROM meters WHERE active_gauges > 0 AND source = \'user\'') as $meter) {
//   $id = $meter['id'];
//   include 'user/' . $meter['url'];
// }
?>