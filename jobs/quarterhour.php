<?php
error_reporting(-1);
ini_set('display_errors', 'On');
date_default_timezone_set('America/New_York');
chdir(__DIR__);
require '../../includes/db.php'; // Has $db
require '../../includes/class.BuildingOS.php';
require '../../includes/class.Meter.php';
require '../../scripts/cron.php';
$res = 'quarterhour';
$amount = strtotime('-2 weeks');
echo '<pre>';
$bos = new BuildingOS($db);
$meter = new Meter($db);
cron($db, $bos, $meter, $res, $amount, false, false, true);

// Custom scrips
// $interval = 'quarterhour';
// foreach ($db->query('SELECT id, url FROM meters WHERE active_gauges > 0 AND source = \'user\'') as $meter) {
//   $id = $meter['id'];
//   include 'user/' . $meter['url'];
// }
?>