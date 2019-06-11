<?php
require '/var/www/repos/includes/db.php';
date_default_timezone_set('America/New_York');
$time = time();
$key = 'aac6c14f6ed11c4787ed18ed20a5c18b';
$meters = [
    'Oberlin Air Temperature' => ['lat' => '41.29442793429477', 'lon' => '-82.21714374667339'],
    'Cleveland Air Temperature' => ['lat' => '41.4993', 'lon' => '-81.6944']
];

foreach ($meters as $meter => $coords) {
    $url = "https://api.openweathermap.org/data/2.5/weather?lat={$coords['lat']}&lon={$coords['lon']}&appid={$key}";
    $weather = json_decode(file_get_contents($url), true);
    $current_temp = kelvin_to_fahrenheit($weather['main']['temp']);

    $stmt = $db->prepare('SELECT id FROM meters WHERE name = ? LIMIT 1');
    $stmt->execute([$meter]);
    $id = $stmt->fetchColumn();
    if ($id == false) {
        $stmt = $db->prepare('INSERT INTO meters (building_id, source, scope, resource, name, calculated, units) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([1, 'openweathermap', 'Other', 'Undefined', $meter, 1, 'Fahrenheit']);
        $id = $db->lastInsertId();
    }
    $stmt = $db->prepare('REPLACE INTO meter_data (meter_id, value, recorded, resolution) VALUES (?, ?, ?, ?)');
    $stmt->execute([$id, $current_temp, $time, 'hour']);
    $stmt = $db->prepare('UPDATE meters SET current = ? WHERE id = ?');
    $stmt->execute([$current_temp, $id]);
}

function kelvin_to_fahrenheit($temp) {
    return (($temp - 273.15) * 1.8) + 32;
}