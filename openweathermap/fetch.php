<?php
require '/var/www/repos/includes/db.php';
date_default_timezone_set('America/New_York');

$key = 'aac6c14f6ed11c4787ed18ed20a5c18b';
$lat = '41.29442793429477';
$lon = '-82.21714374667339';
$url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$key}";
$weather = json_decode(file_get_contents($url), true);
$current_temp = $weather['main']['temp'];
$time = time();

$meter_name = 'OpenWeatherMap air temperature';
$stmt = $db->prepare('SELECT id FROM meters WHERE name = ? LIMIT 1');
$stmt->execute([$meter_name]);
$id = $stmt->fetchColumn();
if ($id == false) {
    $stmt = $db->prepare('INSERT INTO meters (building_id, source, scope, resource, name, calculated, units) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([1, 'openweathermap', 'Other', 'Undefined', $meter_name, 1, 'Kelvin']);
    $id = $db->lastInsertId();
}
$stmt = $db->prepare('REPLACE INTO meter_data (meter_id, value, recorded, resolution) VALUES (?, ?, ?, ?)');
$stmt->execute([$id, $current_temp, $time, 'hour']);