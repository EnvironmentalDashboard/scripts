<?php
require '../includes/db.php';
require '../includes/class.BuildingOS.php';
$meter_id = 787;
$meter_url = 'https://api.buildingos.com/meters/oberlincity_omlps_total_city_e_per_person/data';
$res = 'quarterhour';
$api_id = 1;
$from =  strtotime('-2 hours');
$to = time();
$stmt = $db->prepare('SELECT `value`, FROM_UNIXTIME(recorded, \'%W, %M %D, %h:%i %p\') AS `localtime` FROM meter_data WHERE meter_id = ? AND resolution = ? AND recorded > ? AND recorded < ? ORDER BY recorded ASC');
$stmt->execute(array($meter_id, $res, $from, $to));
$db_data = $stmt->fetchAll();

$bos = new BuildingOS($db, 1);
$api_data = json_decode($bos->getMeter($meter_url, $res, $from, $to), true)['data'];
$api_data2 = json_decode($bos->getMeter($meter_url, 'live', $from, $to), true)['data'];

echo "Our data\n";
print_r($db_data);
echo "\n\nData returned from Lucid\n";
print_r($api_data);
echo "Live lucid data\n";
print_r($api_data2);
?>