<?php
require '../includes/db.php';
require '../includes/class.BuildingOS.php';
$meter_id = 455;
$meter_url = 'https://api.buildingos.com/meters/oberlin_jhouse_main_e/data';
$res = 'quarterhour';
$api_id = 1;
$from = strtotime('-2 hours');
$to = strtotime('now');
$stmt = $db->prepare('SELECT `value`, FROM_UNIXTIME(recorded, \'%W, %M %D, %h:%i %p\') AS `localtime` FROM meter_data WHERE meter_id = ? AND resolution = ? AND recorded > ? AND recorded < ? ORDER BY recorded ASC');
$stmt->execute(array($meter_id, $res, $from, $to));
$db_data = $stmt->fetchAll();

$bos = new BuildingOS($db, 1);
$api_data = json_decode($bos->getMeter($meter_url, $res, $from, $to, true), true)['data'];
// $api_data2 = json_decode($bos->getMeter($meter_url, 'live', $from, $to), true)['data'];
echo "Showing data from ".date('F j, Y, g:i a', $from)." ({$from}) to ".date('F j, Y, g:i a', $to)." ({$to})\n";
echo "Our data\n";
print_r($db_data);
echo "\n\nData returned from Lucid\n";
print_r($api_data);
// echo "Live lucid data\n";
// print_r($api_data2);
?>