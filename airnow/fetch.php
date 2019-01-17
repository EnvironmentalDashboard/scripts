<?php
require '/var/www/repos/includes/db.php';
require 'key.php';
date_default_timezone_set('America/New_York');
// https://stackoverflow.com/a/1241751/2624391
set_error_handler(function ($errno, $errstr, $errfile, $errline, array $errcontext) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

try {
    $url = "http://www.airnowapi.org/aq/observation/zipCode/current/?format=text/csv&zipCode=44074&distance=25&API_KEY={$api_key}";
    $res = file($url);
    $data = array_map('str_getcsv', $res);
    for ($i = 1; $i < count($data); $i++) { // first row is header; skip
        $time = strtotime($data[$i][0] . $data[$i][1] . ':00:00');
        $val = floatval($data[$i][8]);
        $meter_name = "{$data[$i][7]} (GT Craig)";
        $stmt = $db->prepare('SELECT id FROM meters WHERE name = ? LIMIT 1');
        $stmt->execute([$meter_name]);
        $id = $stmt->fetchColumn();
        if ($id == false) {
            $stmt = $db->prepare('INSERT INTO meters (building_id, source, scope, resource, name, calculated, units) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([1, 'airnow', 'Other', 'Undefined', $meter_name, 1, '']);
            $id = $db->lastInsertId();
        }
        $stmt = $db->prepare('REPLACE INTO meter_data (meter_id, value, recorded, resolution) VALUES (?, ?, ?, ?)');
        $stmt->execute([$id, $val, $time, 'hour']);
    }
} catch (ErrorException $e) {
    openlog("AirNow API", LOG_PID, LOG_CRON);
    syslog(LOG_WARNING, $e->getMessage());
    closelog();
}
